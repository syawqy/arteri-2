<?= $this->extend('layout/main') ?>

<?php $this->section('content') ?>

<style>
    * { box-sizing: border-box; }

    .chat-wrapper {
        margin: -20px -15px;
        height: calc(100vh - 60px);
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
    }

    /* Header */
    .chat-header {
        background: #fff;
        border-bottom: 1px solid #dee2e6;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .chat-header h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #2c3e50;
    }

    .chat-header h4 span { color: #95a5a6; font-weight: 400; }

    .mcp-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #7f8c8d;
        margin-left: auto;
    }

    .mcp-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #e74c3c;
    }

    .mcp-dot.green { background: #27ae60; }
    .mcp-dot.yellow { background: #f39c12; animation: pulse 1s infinite; }

    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

    /* Chat Area */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .chat-msg {
        max-width: 800px;
        margin: 0 auto 16px;
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .chat-msg .role {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .chat-msg .role.user { color: #3498db; }
    .chat-msg .role.assistant { color: #27ae60; }
    .chat-msg .role.system { color: #f39c12; }

    .chat-msg .content {
        font-size: 14px;
        line-height: 1.7;
        color: #2c3e50;
        background: #fff;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .chat-msg .content p { margin-bottom: 8px; }
    .chat-msg .content p:last-child { margin-bottom: 0; }
    .chat-msg .content h1, .chat-msg .content h2, .chat-msg .content h3,
    .chat-msg .content h4, .chat-msg .content h5, .chat-msg .content h6 {
        color: #2c3e50; margin: 12px 0 6px; line-height: 1.3;
    }
    .chat-msg .content h1 { font-size: 18px; }
    .chat-msg .content h2 { font-size: 16px; }
    .chat-msg .content h3 { font-size: 15px; }
    .chat-msg .content blockquote {
        border-left: 3px solid #3498db; padding: 4px 12px; margin: 8px 0;
        color: #7f8c8d; background: #f0f7ff; border-radius: 0 4px 4px 0;
    }
    .chat-msg .content hr { border: none; border-top: 1px solid #dee2e6; margin: 12px 0; }
    .chat-msg .content ul, .chat-msg .content ol { margin: 8px 0; padding-left: 20px; }
    .chat-msg .content li { margin-bottom: 4px; }
    .chat-msg .content del { color: #95a5a6; }
    .chat-msg .content a { color: #3498db; text-decoration: none; }
    .chat-msg .content a:hover { text-decoration: underline; }
    .chat-msg .content .md-table {
        width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px;
    }
    .chat-msg .content .md-table th, .chat-msg .content .md-table td {
        border: 1px solid #dee2e6; padding: 6px 10px; text-align: left;
    }
    .chat-msg .content .md-table th {
        background: #f0f7ff; color: #2c3e50; font-weight: 600;
    }
    .chat-msg .content .md-table td { background: #fff; }
    .chat-msg .content .md-table tr:hover td { background: #f8f9fa; }
    .chat-msg .content code {
        background: #f1f3f5;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        color: #e74c3c;
    }
    .chat-msg .content pre {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 12px;
        margin: 8px 0;
        overflow-x: auto;
        font-size: 12px;
    }
    .chat-msg .content table {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0;
        font-size: 13px;
    }
    .chat-msg .content th, .chat-msg .content td {
        padding: 6px 10px;
        border: 1px solid #dee2e6;
        text-align: left;
    }
    .chat-msg .content th { background: #f8f9fa; font-weight: 600; }

    /* Tool Call Display */
    .tool-box {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin: 8px 0;
        overflow: hidden;
    }

    .tool-box-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        font-size: 12px;
        cursor: pointer;
    }

    .tool-box-header:hover { background: #e9ecef; }

    .tool-badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
    }

    .badge-call { background: rgba(52,152,219,0.15); color: #3498db; }
    .badge-result { background: rgba(39,174,96,0.15); color: #27ae60; }
    .badge-error { background: rgba(231,76,60,0.15); color: #e74c3c; }
    .badge-thinking { background: rgba(243,156,18,0.15); color: #f39c12; }

    .tool-box-body {
        padding: 10px 12px;
        font-family: monospace;
        font-size: 11px;
        line-height: 1.5;
        color: #7f8c8d;
        max-height: 250px;
        overflow-y: auto;
        white-space: pre-wrap;
        display: none;
    }

    .tool-box-body.open { display: block; }

    /* Input Area */
    .chat-input {
        background: #fff;
        border-top: 1px solid #dee2e6;
        padding: 12px 20px;
        flex-shrink: 0;
    }

    .chat-input-inner {
        max-width: 800px;
        margin: 0 auto;
        display: flex;
        gap: 8px;
    }

    .chat-input textarea {
        flex: 1;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 14px;
        font-family: inherit;
        resize: none;
        max-height: 120px;
        min-height: 42px;
    }

    .chat-input textarea:focus { outline: none; border-color: #3498db; }

    .chat-input .btn-send {
        width: 42px;
        height: 42px;
        border-radius: 6px;
        border: none;
        background: #3498db;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .chat-input .btn-send:hover { background: #2980b9; }
    .chat-input .btn-send:disabled { opacity: 0.4; cursor: default; }

    .chat-input .hint {
        max-width: 800px;
        margin: 4px auto 0;
        font-size: 11px;
        color: #95a5a6;
    }

    /* Welcome */
    .chat-welcome {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        text-align: center;
        color: #7f8c8d;
        padding: 40px;
    }

    .chat-welcome .icon { font-size: 48px; margin-bottom: 16px; }
    .chat-welcome h3 { color: #2c3e50; margin-bottom: 8px; }
    .chat-welcome p { font-size: 13px; max-width: 500px; line-height: 1.6; }

    .chat-welcome .examples {
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
        max-width: 600px;
    }

    .chat-welcome .example-btn {
        padding: 8px 14px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        color: #2c3e50;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .chat-welcome .example-btn:hover { border-color: #3498db; color: #3498db; }
</style>

<div class="chat-wrapper">
    <!-- Header -->
    <div class="chat-header">
        <h4>🤖 AI Assistant <span>Arteri</span></h4>
        <div class="mcp-status">
            <div class="mcp-dot" id="mcpDot"></div>
            <span id="mcpStatus">Connecting...</span>
        </div>
    </div>

    <!-- Messages -->
    <div class="chat-messages" id="chatMessages">
        <div class="chat-welcome" id="chatWelcome">
            <div class="icon">🏛️</div>
            <h3>Arteri AI Assistant</h3>
            <p>Chat dengan AI yang terhubung ke sistem pengelola arsip digital. AI dapat menggunakan 30 tools untuk mengelola arsip, klasifikasi, pencarian, retensi, compliance, dan migrasi.</p>
            <div class="examples">
                <button class="example-btn" onclick="sendExample(this)">Cari arsip surat masuk tahun 2026</button>
                <button class="example-btn" onclick="sendExample(this)">Tunjukkan arsip yang perlu diverifikasi</button>
                <button class="example-btn" onclick="sendExample(this)">Buat laporan compliance bulan ini</button>
                <button class="example-btn" onclick="sendExample(this)">Cek jadwal retensi untuk klasifikasi SM</button>
            </div>
        </div>
    </div>

    <!-- Input -->
    <div class="chat-input">
        <div class="chat-input-inner">
            <textarea id="userInput" rows="1" placeholder="Ketik pesan..."
                      onkeydown="handleKeydown(event)"
                      oninput="autoResize(this)"></textarea>
            <button class="btn-send" id="sendBtn" onclick="sendMessage()">▲</button>
        </div>
        <div class="hint">
            Enter = kirim · Shift+Enter = baris baru · LLM akan gunakan MCP tools yang relevan
        </div>
    </div>
</div>

<script>
// ── State ──────────────────────────────────────
const CSRF_TOKEN = '<?= csrf_token() ?>';
const CSRF_HASH  = '<?= csrf_hash() ?>';
const USERNAME   = '<?= esc($username) ?>';

let mcpConnected = false;
let mcpSessionId = null;
let mcpTools = [];
let mcpRpcId = 0;
let chatHistory = [];
let isGenerating = false;

// ── CSRF helper ────────────────────────────────
function csrfHeaders() {
    return {
        'X-CSRF-TOKEN': CSRF_HASH,
        'Content-Type': 'application/json',
    };
}

// ── MCP Connection ─────────────────────────────
async function connectMcp() {
    setMcpStatus('yellow', 'Connecting...');

    try {
        // Initialize
        const initResp = await fetchMcp({
            jsonrpc: '2.0', id: ++mcpRpcId, method: 'initialize',
            params: { protocolVersion: '2025-03-26', capabilities: {},
                      clientInfo: { name: 'Arteri Chatbot', version: '1.0.0' } }
        });
        mcpSessionId = initResp.headers.get('Mcp-Session-Id') || initResp.sessionId;

        if (!mcpSessionId) throw new Error('No session ID');

        // Initialized notification
        await fetchMcp({ jsonrpc: '2.0', method: 'notifications/initialized', params: {} }, true);

        // Fetch tools
        const toolsResp = await mcpRequest('tools/list', {});
        mcpTools = toolsResp.result?.tools || [];

        mcpConnected = true;
        setMcpStatus('green', `${mcpTools.length} tools ready`);
    } catch (err) {
        mcpConnected = false;
        setMcpStatus('red', `Error: ${err.message}`);
    }
}

async function fetchMcp(payload, isNotification = false) {
    const resp = await fetch('/chat/api', {
        method: 'POST',
        headers: csrfHeaders(),
        body: JSON.stringify({
            action: 'mcp',
            payload: payload,
            sessionId: mcpSessionId,
        })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();

    // For initialize response, extract session ID from response
    if (payload.method === 'initialize' && data.result) {
        // Session ID is passed via header in real MCP,
        // but here we get it from the proxy
        // The controller sets it in response header
        const headerSessionId = resp.headers.get('Mcp-Session-Id');
        if (headerSessionId) {
            return { ...data, headers: { get: (h) => h === 'Mcp-Session-Id' ? headerSessionId : null }, sessionId: headerSessionId };
        }
    }

    return data;
}

async function mcpRequest(method, params) {
    const resp = await fetch('/chat/api', {
        method: 'POST',
        headers: csrfHeaders(),
        body: JSON.stringify({
            action: 'mcp',
            payload: { jsonrpc: '2.0', id: ++mcpRpcId, method, params },
            sessionId: mcpSessionId,
        })
    });

    if (!resp.ok) throw new Error(`MCP error: HTTP ${resp.status}`);
    return await resp.json();
}

async function mcpToolCall(name, args) {
    const startTime = performance.now();
    try {
        const result = await mcpRequest('tools/call', { name, arguments: args });
        const elapsed = Math.round(performance.now() - startTime);

        if (result.error) {
            return { isError: true, content: [{ type: 'text', text: `Error: ${result.error.message}` }], elapsed };
        }

        const toolResult = result.result || {};
        return {
            isError: toolResult.isError || false,
            content: toolResult.content || [],
            elapsed
        };
    } catch (err) {
        return { isError: true, content: [{ type: 'text', text: `MCP Error: ${err.message}` }], elapsed: Math.round(performance.now() - startTime) };
    }
}

// ── LLM API Call ───────────────────────────────
async function callLlm(messages) {
    const resp = await fetch('/chat/api', {
        method: 'POST',
        headers: csrfHeaders(),
        body: JSON.stringify({
            action: 'chat',
            messages: messages,
            mcpTools: mcpTools.map(t => ({
                name: t.name,
                description: t.description || '',
                inputSchema: t.inputSchema || { type: 'object', properties: {} }
            })),
            mcpSessionId: mcpSessionId,
        })
    });

    if (!resp.ok) {
        const err = await resp.json().catch(() => ({}));
        throw new Error(err.error || `HTTP ${resp.status}`);
    }

    const data = await resp.json();

    return {
        choices: [{
            message: {
                content: data.response || '',
                tool_calls: data.toolCalls || null
            },
            finish_reason: data.finishReason || 'stop'
        }],
        usage: data.usage
    };
}

// ── Main Chat Flow ─────────────────────────────
async function sendMessage(text) {
    text = text || document.getElementById('userInput').value.trim();
    if (!text || isGenerating) return;
    if (!mcpConnected) { addMsg('system', 'MCP belum terhubung. Silakan refresh halaman.'); return; }

    document.getElementById('userInput').value = '';
    autoResize(document.getElementById('userInput'));

    addMsg('user', text);
    chatHistory.push({ role: 'user', content: text });

    setGenerating(true);

    try {
        let continueLoop = true;
        let rounds = 0;

        while (continueLoop && rounds < 10) {
            rounds++;
            const response = await callLlm(chatHistory);
            const choice = response.choices?.[0];
            if (!choice) throw new Error('No response from LLM');

            const msg = choice.message;

            if (msg.tool_calls && msg.tool_calls.length > 0) {
                chatHistory.push({
                    role: 'assistant',
                    content: msg.content || null,
                    tool_calls: msg.tool_calls
                });

                const thinkEl = addToolBox(msg.tool_calls.map(tc => tc.function.name).join(', '), 'thinking', 'Thinking...');

                const toolResults = [];
                for (const tc of msg.tool_calls) {
                    let args;
                    try { args = JSON.parse(tc.function.arguments); } catch { args = {}; }

                    updateToolBox(thinkEl, tc.function.name, 'call', args);

                    const result = await mcpToolCall(tc.function.name, args);
                    const resultText = result.content.map(c => c.text || JSON.stringify(c)).join('\n');

                    addToolBox(tc.function.name, result.isError ? 'error' : 'result', resultText);

                    toolResults.push({
                        tool_call_id: tc.id,
                        role: 'tool',
                        content: resultText
                    });
                }

                chatHistory.push(...toolResults);
            } else {
                if (msg.content) {
                    addMsg('assistant', msg.content);
                    chatHistory.push({ role: 'assistant', content: msg.content });
                }
                continueLoop = false;
            }
        }
    } catch (err) {
        addMsg('system', `Error: ${err.message}`);
    } finally {
        setGenerating(false);
    }
}

// ── UI Helpers ─────────────────────────────────
function addMsg(role, text) {
    const container = document.getElementById('chatMessages');
    const welcome = document.getElementById('chatWelcome');
    if (welcome) welcome.remove();

    const div = document.createElement('div');
    div.className = 'chat-msg';
    div.innerHTML = `
        <div class="role ${role}">${role === 'user' ? 'You' : role === 'assistant' ? 'Arteri AI' : 'System'}</div>
        <div class="content">${renderMd(text)}</div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

function addToolBox(name, type, data) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'chat-msg';

    const badgeClass = type === 'call' ? 'badge-call' : type === 'error' ? 'badge-error' : type === 'thinking' ? 'badge-thinking' : 'badge-result';
    const label = type === 'call' ? 'TOOL CALL' : type === 'error' ? 'ERROR' : type === 'thinking' ? 'THINKING' : 'RESULT';

    div.innerHTML = `
        <div class="tool-box">
            <div class="tool-box-header" onclick="this.nextElementSibling.classList.toggle('open')">
                <span class="tool-badge ${badgeClass}">${label}</span>
                <strong>${name}</strong>
                <span style="margin-left:auto;font-size:10px;color:#95a5a6">▼</span>
            </div>
            <div class="tool-box-body ${type !== 'thinking' ? '' : 'open'}">${escapeHtml(typeof data === 'string' ? data : JSON.stringify(data, null, 2))}</div>
        </div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

function updateToolBox(el, name, type, data) {
    if (!el) return;
    el.querySelector('.tool-box-header strong').textContent = name;
    const badge = el.querySelector('.tool-badge');
    badge.className = `tool-badge ${type === 'call' ? 'badge-call' : 'badge-result'}`;
    badge.textContent = type === 'call' ? 'CALLING' : 'RESULT';
    const body = el.querySelector('.tool-box-body');
    body.className = 'tool-box-body open';
    body.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
}

function renderMd(text) {
    if (!text) return '';
    const codeBlocks = [];
    text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, (_, lang, code) => {
        const ph = `\x00CB${codeBlocks.length}\x00`;
        codeBlocks.push(`<pre><code class="lang-${lang || 'text'}">${escapeHtml(code.trimEnd())}</code></pre>`);
        return ph;
    });
    const inlineCodes = [];
    text = text.replace(/`([^`\n]+)`/g, (_, code) => {
        const ph = `\x00IC${inlineCodes.length}\x00`;
        inlineCodes.push(`<code>${escHtml(code)}</code>`);
        return ph;
    });

    const lines = text.split('\n');
    let html = '';
    let i = 0;

    while (i < lines.length) {
        const line = lines[i];

        // ── Tables ──────────────────────────────
        if (line.includes('|') && i + 1 < lines.length && /^\|?[\s-:|]+\|/.test(lines[i + 1])) {
            const rows = [];
            while (i < lines.length && lines[i].includes('|')) {
                const cells = lines[i].split('|').map(c => c.trim()).filter((c, idx, arr) => {
                    if (idx === 0 && c === '') return false;
                    if (idx === arr.length - 1 && c === '') return false;
                    return true;
                });
                rows.push(cells);
                i++;
            }
            if (rows.length >= 2) {
                html += '<table class="md-table"><thead><tr>';
                rows[0].forEach(c => { html += `<th>${renderInlineMd(c)}</th>`; });
                html += '</tr></thead><tbody>';
                for (let r = 2; r < rows.length; r++) {
                    html += '<tr>';
                    rows[r].forEach(c => { html += `<td>${renderInlineMd(c)}</td>`; });
                    html += '</tr>';
                }
                html += '</tbody></table>';
            }
            continue;
        }

        // ── Headers ──────────────────────────────
        const headerMatch = line.match(/^(#{1,6})\s+(.+)/);
        if (headerMatch) {
            const level = headerMatch[1].length;
            html += `<h${level}>${renderInlineMd(headerMatch[2])}</h${level}>`;
            i++;
            continue;
        }

        // ── Horizontal rule ──────────────────────
        if (/^(-{3,}|\*{3,}|_{3,})\s*$/.test(line)) {
            html += '<hr>';
            i++;
            continue;
        }

        // ── Unordered list ───────────────────────
        if (/^(\s*)[-*+]\s+(.+)/.test(line)) {
            html += '<ul>';
            while (i < lines.length) {
                const m = lines[i].match(/^(\s*)[-*+]\s+(.+)/);
                if (!m) break;
                html += `<li>${renderInlineMd(m[2])}</li>`;
                i++;
            }
            html += '</ul>';
            continue;
        }

        // ── Ordered list ─────────────────────────
        if (/^(\s*)\d+[.)]\s+(.+)/.test(line)) {
            html += '<ol>';
            while (i < lines.length) {
                const m = lines[i].match(/^(\s*)\d+[.)]\s+(.+)/);
                if (!m) break;
                html += `<li>${renderInlineMd(m[2])}</li>`;
                i++;
            }
            html += '</ol>';
            continue;
        }

        // ── Blockquote ───────────────────────────
        if (/^>\s?/.test(line)) {
            const qLines = [];
            while (i < lines.length && /^>\s?/.test(lines[i])) {
                qLines.push(lines[i].replace(/^>\s?/, ''));
                i++;
            }
            html += `<blockquote>${renderInlineMd(qLines.join('<br>'))}</blockquote>`;
            continue;
        }

        // ── Blank line ───────────────────────────
        if (line.trim() === '') { i++; continue; }

        // ── Paragraph ────────────────────────────
        const pLines = [];
        while (i < lines.length && lines[i].trim() !== '' && !lines[i].match(/^#{1,6}\s/) && !lines[i].match(/^(-{3,}|\*{3,}|_{3,})\s*$/)) {
            if (lines[i].includes('|') && i + 1 < lines.length && /^\|?[\s-:|]+\|/.test(lines[i + 1])) break;
            if (/^(\s*)[-*+]\s+/.test(lines[i])) break;
            if (/^(\s*)\d+[.)]\s+/.test(lines[i])) break;
            if (/^>\s?/.test(lines[i])) break;
            pLines.push(lines[i]);
            i++;
        }
        if (pLines.length) {
            html += `<p>${renderInlineMd(pLines.join('<br>'))}</p>`;
        }
    }

    codeBlocks.forEach((block, idx) => { html = html.replace(`\x00CB${idx}\x00`, block); });
    inlineCodes.forEach((code, idx) => { html = html.replace(`\x00IC${idx}\x00`, code); });
    return html;
}

function renderInlineMd(text) {
    return text
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/~~(.+?)~~/g, '<del>$1</del>')
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
}

function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function setMcpStatus(color, text) {
    document.getElementById('mcpDot').className = 'mcp-dot ' + color;
    document.getElementById('mcpStatus').textContent = text;
}

function setGenerating(state) {
    isGenerating = state;
    document.getElementById('sendBtn').disabled = state;
    document.getElementById('sendBtn').innerHTML = state ? '⬛' : '▲';
    document.getElementById('userInput').disabled = state;
}

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function sendExample(btn) {
    document.getElementById('userInput').value = btn.textContent;
    sendMessage(btn.textContent);
}

// ── Init ───────────────────────────────────────
connectMcp();
</script>

<?= $this->endSection() ?>
