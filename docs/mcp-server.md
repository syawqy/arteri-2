# Arteri MCP Server

## Overview

The Arteri MCP Server integrates with AI assistants (Claude, Cursor, etc.) via the Model Context Protocol, providing 6 specialized AI agents for archive management.

## Quick Start

### Desktop (stdio transport)

Add to your MCP client config (e.g., `.cursor/mcp.json`):

```json
{
  "mcpServers": {
    "arteri": {
      "command": "php",
      "args": ["D:/codes/php/arteri-migrasi/ci4/spark", "mcp:serve"],
      "env": {
        "CI_ENVIRONMENT": "development"
      }
    }
  }
}
```

### HTTP transport

```bash
php spark mcp:serve --transport=http --port=8090
```

Then configure your MCP client to connect to `http://127.0.0.1:8090/mcp`.

## Available Tools (30 total)

### Agent 1 — Intelligent Ingestion (6 tools)

| Tool | Description |
|---|---|
| `ingest_document` | Process a new document with OCR and metadata extraction |
| `extract_metadata` | Re-extract metadata from an ingested document |
| `detect_duplicates` | Check for potential duplicate archives |
| `detect_scan_quality` | Check if a scanned document is readable |
| `suggest_metadata` | Suggest metadata based on document content |
| `get_verification_queue` | List items needing human verification |

### Agent 2 — Classification & Retention (5 tools)

| Tool | Description |
|---|---|
| `suggest_classification` | Suggest classification codes for an archive |
| `match_classification_scheme` | Match text to classification scheme |
| `suggest_series` | Suggest archive series |
| `get_retention_schedule` | Get retention schedule details |
| `explain_recommendation` | Get reasoning for a classification |

### Agent 3 — Archival Search (3 tools)

| Tool | Description |
|---|---|
| `natural_language_search` | Search archives using natural language |
| `get_search_results_with_sources` | Get results with source documents |
| `list_accessible_archives` | List user-accessible archives |

### Agent 4 — Retention & Disposition (4 tools)

| Tool | Description |
|---|---|
| `get_retention_candidates` | Archives approaching retention end |
| `get_disposition_proposals` | Pending disposition proposals |
| `prepare_disposition_docs` | Generate disposition documentation |
| `check_legal_hold` | Check legal hold status |

### Agent 5 — Compliance & Audit (6 tools)

| Tool | Description |
|---|---|
| `check_metadata_completeness` | Verify metadata completeness |
| `detect_unauthorized_changes` | Find unauthorized modifications |
| `find_unclassified_archives` | Find archives without classification |
| `monitor_access_overreach` | Check overly broad permissions |
| `trace_access_history` | Show who accessed archives |
| `generate_compliance_report` | Create compliance report |

### Agent 6 — Migration (6 tools)

| Tool | Description |
|---|---|
| `analyze_source` | Scan source for documents |
| `preview_migration` | Preview migration contents |
| `start_migration` | Begin migration process |
| `get_migration_status` | Check migration progress |
| `map_source_fields` | Map source fields to Arteri |

## Safety & ACL

- **Human-in-the-loop**: Destructive actions (disposition, deletion) require human approval
- **ACL enforcement**: All queries are filtered by the user's classification access permissions
- **Audit trail**: Every MCP access is logged in `document_access_log`
- **No automatic disposition**: Archives cannot be destroyed without human sign-off
- **Low-confidence queue**: Metadata with confidence < 75% enters human verification queue

## Prerequisites

- PHP 8.2+
- Tesseract OCR (for document ingestion)
- Poppler utils (for PDF OCR)

## New Database Tables

| Table | Purpose |
|---|---|
| `ai_ingestion_queue` | Document ingestion processing queue |
| `ai_verification_queue` | Low-confidence metadata verification |
| `ai_classifications` | AI classification proposals |
| `retention_schedules` | Detailed retention schedules |
| `retention_actions` | Disposition proposals |
| `legal_holds` | Legal hold tracking |
| `document_access_log` | MCP access audit log |
| `migration_jobs` | Migration job tracking |
| `migration_items` | Individual migration items |
