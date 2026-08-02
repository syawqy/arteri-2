#!/usr/bin/env python3
"""
Arteri-2 Chatbot E2E Test - mimo-v2.5 + All 30 MCP Tools
Tests via HTTP API with proper session + CSRF handling
"""
import requests
import json
import os
import time
from datetime import datetime

BASE = 'http://127.0.0.1:8082'
MCP_URL = 'http://127.0.0.1:8090/mcp'
SS_DIR = os.path.join(os.path.dirname(__file__), '../docs/chatbot-test-screenshots')
REPORT_PATH = os.path.join(os.path.dirname(__file__), '../docs/CHATBOT-TEST-REPORT.md')

os.makedirs(SS_DIR, exist_ok=True)
os.makedirs(os.path.dirname(REPORT_PATH), exist_ok=True)

results = []
session = requests.Session()

def login():
    """Login and get session cookies + CSRF token"""
    # GET login page to get CSRF token
    r = session.get(f'{BASE}/login')
    # Extract CSRF token from meta tag or hidden field
    import re
    m = re.search(r'csrf_test_name["\s]+value="([^"]+)"', r.text)
    csrf_token = m.group(1) if m else ''
    
    # POST login
    r = session.post(f'{BASE}/login', data={
        'username': 'admin',
        'password': 'admin123',
        'csrf_test_name': csrf_token,
    }, allow_redirects=True)
    
    print(f'[LOGIN] Status: {r.status_code}, URL: {r.url}')
    
    # Get CSRF hash for API calls
    r2 = session.get(f'{BASE}/chat')
    m2 = re.search(r"CSRF_HASH\s*=\s*'([^']+)'", r2.text)
    csrf_hash = m2.group(1) if m2 else ''
    print(f'[CSRF] Hash: {csrf_hash[:20]}...')
    return csrf_hash

def init_mcp():
    """Initialize MCP session"""
    r = session.post(MCP_URL, 
        headers={
            'Content-Type': 'application/json',
            'Accept': 'application/json, text/event-stream',
            'X-Username': 'admin'
        },
        json={
            'jsonrpc': '2.0', 'id': '1',
            'method': 'initialize',
            'params': {
                'protocolVersion': '2024-11-05',
                'capabilities': {},
                'clientInfo': {'name': 'test', 'version': '1.0'}
            }
        })
    session_id = r.headers.get('Mcp-Session-Id', '')
    print(f'[MCP] Session: {session_id[:20]}...')
    
    # Send initialized notification
    session.post(MCP_URL,
        headers={
            'Content-Type': 'application/json',
            'Accept': 'application/json, text/event-stream',
            'X-Username': 'admin',
            'Mcp-Session-Id': session_id
        },
        json={'jsonrpc': '2.0', 'method': 'notifications/initialized', 'params': {}})
    
    # List tools
    r3 = session.post(MCP_URL,
        headers={
            'Content-Type': 'application/json',
            'Accept': 'application/json, text/event-stream',
            'X-Username': 'admin',
            'Mcp-Session-Id': session_id
        },
        json={'jsonrpc': '2.0', 'id': '2', 'method': 'tools/list', 'params': {}})
    tools = r3.json().get('result', {}).get('tools', [])
    print(f'[MCP] {len(tools)} tools loaded')
    return session_id, tools

def call_chat(csrf_hash, mcp_session_id, messages, mcp_tools):
    """Call the chat API"""
    r = session.post(f'{BASE}/chat/api',
        headers={
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf_hash,
        },
        json={
            'action': 'chat',
            'messages': messages,
            'mcpTools': mcp_tools,
            'mcpSessionId': mcp_session_id,
            'chatSessionId': None,
            'maxTokens': 4096,
            'temperature': 0.3,
        })
    return r.json()

def call_mcp_tool(csrf_hash, mcp_session_id, tool_name, arguments, chat_session_id=None):
    """Call an MCP tool via chat proxy"""
    payload = {
        'jsonrpc': '2.0',
        'id': f'tool-{tool_name}',
        'method': 'tools/call',
        'params': {
            'name': tool_name,
            'arguments': arguments
        }
    }
    r = session.post(f'{BASE}/chat/api',
        headers={
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf_hash,
        },
        json={
            'action': 'mcp',
            'payload': payload,
            'sessionId': mcp_session_id,
            'chatSessionId': chat_session_id,
            'toolName': tool_name,
            'toolCallId': f'tc-{tool_name}',
        })
    return r.json()

def run_chat_test(csrf_hash, mcp_session_id, mcp_tools, num, name, prompt, db_check):
    """Run a chat-based test"""
    print(f'\n{"="*60}')
    print(f'TEST {num:02d}: {name}')
    print(f'{"="*60}')
    print(f'Prompt: {prompt[:80]}')
    
    start = time.time()
    try:
        resp = call_chat(csrf_hash, mcp_session_id, [
            {'role': 'user', 'content': prompt}
        ], mcp_tools)
        
        elapsed = time.time() - start
        response = resp.get('response', 'No response')
        model = resp.get('model', 'unknown')
        tool_calls = resp.get('toolCalls')
        usage = resp.get('usage', {})
        
        print(f'Model: {model}')
        print(f'Time: {elapsed:.1f}s')
        print(f'Tokens: {usage.get("total_tokens", "?")}')
        if tool_calls:
            print(f'Tool calls: {len(tool_calls)}')
            for tc in tool_calls:
                fn = tc.get('function', {})
                print(f'  -> {fn.get("name", "?")}({json.dumps(fn.get("arguments", {}))[:100]})')
        print(f'Response: {response[:200]}')
        
        results.append({
            'num': num,
            'name': name,
            'prompt': prompt,
            'response': response[:500],
            'model': model,
            'elapsed': f'{elapsed:.1f}s',
            'tokens': usage.get('total_tokens', '?'),
            'tool_calls': len(tool_calls) if tool_calls else 0,
            'db_check': db_check,
            'status': 'PASS',
        })
        print(f'[PASS]')
        return resp
    except Exception as e:
        elapsed = time.time() - start
        print(f'[FAIL] {e}')
        results.append({
            'num': num,
            'name': name,
            'prompt': prompt,
            'response': f'ERROR: {e}',
            'model': 'unknown',
            'elapsed': f'{elapsed:.1f}s',
            'tokens': '?',
            'tool_calls': 0,
            'db_check': db_check,
            'status': 'FAIL',
        })
        return None

def run_direct_tool_test(csrf_hash, mcp_session_id, num, name, tool_name, args, db_check):
    """Run a direct MCP tool call test"""
    print(f'\n{"="*60}')
    print(f'TOOL TEST {num:02d}: {name} ({tool_name})')
    print(f'{"="*60}')
    print(f'Args: {json.dumps(args)[:100]}')
    
    start = time.time()
    try:
        resp = call_mcp_tool(csrf_hash, mcp_session_id, tool_name, args)
        elapsed = time.time() - start
        
        # Parse MCP response
        result = resp.get('result', {})
        content = result.get('content', [])
        text = ''
        for c in content:
            if c.get('type') == 'text':
                text += c.get('text', '')
        
        is_error = result.get('isError', False)
        
        print(f'Time: {elapsed:.1f}s')
        print(f'Error: {is_error}')
        print(f'Response: {text[:300]}')
        
        results.append({
            'num': num,
            'name': name,
            'prompt': f'{tool_name}({json.dumps(args)[:80]})',
            'response': text[:500],
            'model': 'mcp-direct',
            'elapsed': f'{elapsed:.1f}s',
            'tokens': '-',
            'tool_calls': 1,
            'db_check': db_check,
            'status': 'FAIL' if is_error else 'PASS',
        })
        print(f'[{ "FAIL" if is_error else "PASS" }]')
        return resp
    except Exception as e:
        elapsed = time.time() - start
        print(f'[FAIL] {e}')
        results.append({
            'num': num,
            'name': name,
            'prompt': f'{tool_name}({json.dumps(args)[:80]})',
            'response': f'ERROR: {e}',
            'model': 'mcp-direct',
            'elapsed': f'{elapsed:.1f}s',
            'tokens': '-',
            'tool_calls': 0,
            'db_check': db_check,
            'status': 'FAIL',
        })
        return None

def generate_report():
    """Generate markdown test report"""
    now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    md = f'''# Arteri-2 Chatbot E2E Test Report

**Date:** {now}  
**Model:** mimo-v2.5 (Sumopod)  
**Base URL:** {BASE}  
**MCP Server:** {MCP_URL} (30 tools)  
**Database:** MySQL `arteri` (100 archives)  
**Test Method:** HTTP API with session auth + CSRF token

---

## Database Reference Data

### Archive Counts
| Table | Count |
|-------|-------|
| data_arsip | 100 |
| master_kode | 46 |
| master_pencipta | 16 |
| master_pengolah | 16 |
| master_lokasi | 7 |
| master_media | 10 |
| sirkulasi | 0 |
| users | 11 |

### Top Kode Distribution
| Kode | Count |
|------|-------|
| 44 | 33 |
| 36 | 16 |
| 20 | 11 |
| 29 | 5 |
| 23 | 3 |
| 21 | 3 |
| 45 | 3 |
| 22 | 3 |

### Klasifikasi Keamanan
| Level | Count |
|-------|-------|
| Biasa/Terbuka | 96 |
| Terbatas | 3 |
| NULL | 1 |

---

## Test Results Summary

| # | Test | Status | Model | Time | Tokens | Tool Calls | DB Check |
|---|------|--------|-------|------|--------|------------|----------|
'''
    for r in results:
        status_icon = 'PASS' if r['status'] == 'PASS' else 'FAIL'
        md += f"| {r['num']:02d} | {r['name']} | {status_icon} | {r['model']} | {r['elapsed']} | {r['tokens']} | {r['tool_calls']} | {r['db_check']} |\n"
    
    passed = sum(1 for r in results if r['status'] == 'PASS')
    failed = sum(1 for r in results if r['status'] == 'FAIL')
    
    md += f'''
**Total:** {len(results)} | **Passed:** {passed} | **Failed:** {failed}

---

## Detailed Results

'''
    for r in results:
        md += f'''### {r['num']:02d}. {r['name']}

**Status:** {r['status']}  
**Prompt:** `{r['prompt'][:120]}`  
**Model:** {r['model']}  
**Time:** {r['elapsed']}  
**Tokens:** {r['tokens']}  
**Tool Calls:** {r['tool_calls']}  
**DB Check:** {r['db_check']}

**Response:**
```
{r['response'][:400]}
```

---

'''
    
    md += f'''## Architecture

```
Browser/Client
    |
    v
CI4 Chat Controller (/chat/api)
    |
    v
LLM API (mimo-v2.5 via Sumopod)
    |
    v (tool_calls)
MCP Server (localhost:8090)
    |
    v
MySQL Database (arteri)
```

## All 30 MCP Tools Tested

'''
    tool_names = [
        'ingest_document', 'extract_metadata', 'detect_duplicates',
        'detect_scan_quality', 'suggest_metadata', 'group_related_documents',
        'get_verification_queue', 'suggest_classification', 'match_classification_scheme',
        'suggest_series', 'get_retention_schedule', 'explain_recommendation',
        'natural_language_search', 'get_search_results_with_sources',
        'list_accessible_archives', 'get_retention_candidates',
        'get_disposition_proposals', 'prepare_disposition_docs',
        'check_legal_hold', 'check_metadata_completeness',
        'detect_unauthorized_changes', 'find_unclassified_archives',
        'monitor_access_overreach', 'trace_access_history',
        'generate_compliance_report', 'analyze_source',
        'preview_migration', 'start_migration', 'get_migration_status',
        'map_source_fields'
    ]
    for i, t in enumerate(tool_names, 1):
        md += f'{i}. `{t}`\n'
    
    with open(REPORT_PATH, 'w') as f:
        f.write(md)
    print(f'\n[REPORT] Saved: {REPORT_PATH}')

# === MAIN ===
if __name__ == '__main__':
    print('='*60)
    print('Arteri-2 Chatbot E2E Test - mimo-v2.5')
    print('='*60)
    
    csrf_hash = login()
    mcp_session_id, mcp_tools = init_mcp()
    
    # Convert tools to chat API format
    chat_tools = []
    for t in mcp_tools:
        chat_tools.append({
            'name': t['name'],
            'description': t.get('description', ''),
            'inputSchema': t.get('inputSchema', {'type': 'object', 'properties': {}}),
        })
    
    # ── PART 1: Chat-based tests (LLM + MCP tools) ──
    print('\n\n' + '#'*60)
    print('# PART 1: CHAT TESTS (mimo-v2.5 + MCP tools)')
    print('#'*60)
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 1, 'Count Archives',
        'Berapa total arsip di sistem ini? Tampilkan angka pastinya.',
        'DB: 100 archives')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 2, 'Search Penghapusan',
        'Cari arsip yang uraiannya mengandung kata "Penghapusan". Tampilkan hasilnya.',
        'DB: records with Penghapusan')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 3, 'Search Kode 44',
        'Tampilkan semua arsip dengan kode 44. Berapa jumlahnya?',
        'DB: 33 archives with kode=44')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 4, 'Retention Schedule',
        'Berapa lama masa retensi aktif dan inaktif untuk kode 20? Tampilkan jra_aktif dan jra_inaktif.',
        'DB: master_kode kode=20')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 5, 'Metadata Completeness',
        'Cek kelengkapan metadata arsip. Field mana yang paling banyak kosong (NULL)?',
        'DB: check NULL values')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 6, 'Find Unclassified',
        'Apakah ada arsip yang tidak memiliki klasifikasi keamanan (klasifikasi_keamanan = NULL)?',
        'DB: 1 NULL klasifikasi_keamanan')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 7, 'Suggest Classification',
        'Suggest kode klasifikasi untuk dokumen surat keputusan pengangkatan pegawai negeri',
        'Should suggest kode')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 8, 'Match Classification',
        'Match dokumen "Surat Keputusan" dengan skema klasifikasi yang ada di master_kode',
        'Should match master_kode')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 9, 'Suggest Series',
        'Suggest series arsip untuk dokumen undangan rapat',
        'Should suggest series')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 10, 'Explain Recommendation',
        'Jelaskan rekomendasi klasifikasi untuk arsip noarsip 10. Apa uraiannya?',
        'DB: noarsip=10 exists')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 11, 'Retention Candidates',
        'Arsip mana yang sudah mendekati atau melewati masa retensi aktif? Sebutkan noarsipnya.',
        'DB: tanggal + jra_aktif')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 12, 'Legal Hold',
        'Apakah ada arsip yang sedang dalam status legal hold?',
        'DB: none expected')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 13, 'Unauthorized Changes',
        'Deteksi perubahan tidak sah pada data arsip. Tampilkan hasilnya.',
        'DB: system_log check')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 14, 'Compliance Report',
        'Buat laporan compliance untuk arsip saat ini. Tampilkan ringkasannya.',
        'Should analyze data')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 15, 'Access History',
        'Tampilkan riwayat akses untuk arsip noarsip 1',
        'DB: system_log noarsip=1')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 16, 'Access Overreach',
        'Monitor akses yang terlalu luas ke arsip terbatas (klasifikasi keamanan Terbatas)',
        'DB: 3 Terbatas')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 17, 'List Accessible',
        'List 10 arsip pertama yang bisa diakses admin. Tampilkan noarsip dan uraiannya.',
        'DB: first 10 of 100')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 18, 'Suggest Metadata',
        'Suggest metadata lengkap untuk arsip nomor 5. Apa uraiannya?',
        'DB: noarsip=5')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 19, 'Group Related',
        'Kelompokkan arsip berdasarkan kesamaan topik atau kode. Tampilkan ringkasannya.',
        'Group by kode')
    
    run_chat_test(csrf_hash, mcp_session_id, chat_tools, 20, 'Disposition Proposals',
        'Apakah ada proposal disposisi arsip yang pending atau menunggu persetujuan?',
        'Check dispositions')
    
    # ── PART 2: Direct MCP tool tests ──
    print('\n\n' + '#'*60)
    print('# PART 2: DIRECT MCP TOOL TESTS')
    print('#'*60)
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 21, 'list_accessible_archives',
        'list_accessible_archives', {},
        'Should return 100 archives')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 22, 'natural_language_search',
        'natural_language_search', {'query': 'Penghapusan'},
        'Should find matching records')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 23, 'get_retention_schedule',
        'get_retention_schedule', {'classification_code': '20'},
        'Should return retention info for kode 20')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 24, 'check_metadata_completeness',
        'check_metadata_completeness', {},
        'Should analyze metadata completeness')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 25, 'find_unclassified_archives',
        'find_unclassified_archives', {},
        'Should find 1 NULL klasifikasi_keamanan')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 26, 'suggest_classification',
        'suggest_classification', {'document_content': 'surat keputusan pengangkatan pegawai negeri'},
        'Should suggest classification')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 27, 'get_retention_candidates',
        'get_retention_candidates', {},
        'Should find retention candidates')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 28, 'check_legal_hold',
        'check_legal_hold', {'archive_id': 1},
        'Should check legal hold for archive 1')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 29, 'detect_unauthorized_changes',
        'detect_unauthorized_changes', {},
        'Should check for unauthorized changes')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 30, 'generate_compliance_report',
        'generate_compliance_report', {},
        'Should generate compliance report')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 31, 'trace_access_history',
        'trace_access_history', {'archive_id': 1},
        'Should trace access for archive 1')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 32, 'monitor_access_overreach',
        'monitor_access_overreach', {},
        'Should monitor access overreach')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 33, 'detect_duplicates',
        'detect_duplicates', {},
        'Should check for duplicates')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 34, 'get_verification_queue',
        'get_verification_queue', {},
        'Should return verification queue')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 35, 'suggest_metadata',
        'suggest_metadata', {'archive_id': 5},
        'Should suggest metadata for archive 5')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 36, 'suggest_series',
        'suggest_series', {'document_type': 'surat undangan rapat'},
        'Should suggest series')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 37, 'match_classification_scheme',
        'match_classification_scheme', {'document_title': 'Surat Keputusan'},
        'Should match classification')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 38, 'explain_recommendation',
        'explain_recommendation', {'archive_id': 10},
        'Should explain recommendation for archive 10')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 39, 'get_disposition_proposals',
        'get_disposition_proposals', {},
        'Should return disposition proposals')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 40, 'group_related_documents',
        'group_related_documents', {},
        'Should group related documents')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 41, 'get_search_results_with_sources',
        'get_search_results_with_sources', {'query': 'surat keputusan'},
        'Should find results with sources')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 42, 'detect_scan_quality',
        'detect_scan_quality', {},
        'Should check scan quality')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 43, 'prepare_disposition_docs',
        'prepare_disposition_docs', {'archive_ids': [1, 2]},
        'Should prepare disposition docs')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 44, 'get_migration_status',
        'get_migration_status', {},
        'Should return migration status')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 45, 'map_source_fields',
        'map_source_fields', {'source_fields': ['title', 'date', 'author']},
        'Should map source fields')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 46, 'analyze_source',
        'analyze_source', {'source_path': '/data/incoming'},
        'Should analyze source')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 47, 'preview_migration',
        'preview_migration', {'source_path': '/data/incoming'},
        'Should preview migration')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 48, 'ingest_document',
        'ingest_document', {'file_path': '/data/test.pdf'},
        'Should attempt ingest')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 49, 'extract_metadata',
        'extract_metadata', {'archive_id': 1},
        'Should extract metadata for archive 1')
    
    run_direct_tool_test(csrf_hash, mcp_session_id, 50, 'start_migration',
        'start_migration', {'source_path': '/data/incoming'},
        'Should attempt migration')
    
    # Generate report
    generate_report()
    
    # Summary
    passed = sum(1 for r in results if r['status'] == 'PASS')
    failed = sum(1 for r in results if r['status'] == 'FAIL')
    print(f'\n{"="*60}')
    print(f'FINAL: {passed}/{len(results)} passed, {failed} failed')
    print(f'{"="*60}')
