# Arteri-2 MCP Architecture

## Overview

Arteri-2 implements the **Model Context Protocol (MCP)** — an open standard for connecting AI assistants to external tools, resources, and prompts. The MCP integration enables AI models (like Claude, GPT, etc.) to interact with the archive management system programmatically.

---

## MCP Server

### What is it?

The MCP Server is a **PHP-based service** that exposes Arteri-2's archive management capabilities as MCP-compatible tools, resources, and prompts. It follows the [MCP specification](https://spec.modelcontextprotocol.io/) and uses the `php-mcp/server` library.

### Architecture

```
┌─────────────────────────────────────────────────────┐
│                   MCP Server                         │
│                                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐          │
│  │ Handlers │  │Resources │  │ Prompts  │          │
│  │ (Tools)  │  │ (Data)   │  │ (Guides) │          │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘          │
│       │              │              │                │
│  ┌────▼──────────────▼──────────────▼────┐          │
│  │         AccessGuard (Auth)            │          │
│  └────────────────┬──────────────────────┘          │
│                   │                                  │
│  ┌────────────────▼──────────────────────┐          │
│  │       CodeIgniter 4 Models            │          │
│  │    (Eloquent-style DB abstraction)    │          │
│  └────────────────┬──────────────────────┘          │
│                   │                                  │
│  ┌────────────────▼──────────────────────┐          │
│  │         MySQL Database                │          │
│  └───────────────────────────────────────┘          │
│                                                      │
│  Transport: Stdio (CLI) / HTTP (API)                │
└─────────────────────────────────────────────────────┘
```

### Entry Point

```bash
# Stdio transport (for CLI integration with AI assistants)
php spark mcp:serve --transport=stdio

# HTTP transport (for web-based MCP clients)
php spark mcp:serve --transport=http --port=8090
```

### Components

#### 1. Handlers (MCP Tools)

Each handler class exposes one or more MCP tools using PHP 8 attributes:

| Handler | Tools | Purpose |
|---------|-------|---------|
| `IngestionHandler` | `ingest_document`, `get_ingestion_status`, `list_pending_verifications` | Document upload, OCR processing, metadata extraction |
| `ClassificationHandler` | `classify_document`, `get_classification_suggestions`, `approve_classification` | AI-powered document classification with confidence scoring |
| `SearchHandler` | `natural_language_search`, `search_by_metadata`, `get_document_detail` | Natural language and metadata search across archives |
| `RetentionHandler` | `get_retention_schedule`, `propose_disposition`, `apply_legal_hold` | Retention policy management and legal holds |
| `ComplianceHandler` | `check_compliance`, `get_audit_trail`, `export_audit_log` | Compliance checking and audit trail access |
| `MigrationHandler` | `create_migration_job`, `analyze_source`, `import_batch` | Bulk data import from external sources |

**Tool Registration (attribute-based):**

```php
class SearchHandler
{
    /**
     * Search archives using natural language queries.
     */
    #[McpTool(name: 'natural_language_search')]
    public function naturalLanguageSearch(
        #[Schema(type: 'string', description: 'Natural language search query')]
        string $query,
        // ...
    ): array {
        // Implementation
    }
}
```

The `McpServer` class uses **reflection** to auto-discover all `#[McpTool]` attributes and register them with the PHP MCP server.

#### 2. Resources (Data Exposure)

Resources expose read-only data that AI models can query:

| Resource | URI Pattern | Purpose |
|----------|-------------|---------|
| `ArsipResource` | `arteri://arsip/{id}`, `arteri://arsip/search` | Archive records metadata |
| `SystemResource` | `arteri://system/health`, `arteri://system/stats` | System health and statistics |

```php
class ArsipResource
{
    /**
     * Get a specific archive record by ID.
     */
    #[McpResource(uri: 'arteri://arsip/{id}', name: 'get_arsip')]
    public function getArsip(int $id): array { ... }
}
```

#### 3. Prompts (Guided Workflows)

Prompts provide pre-defined AI conversation templates:

| Prompt | Purpose |
|--------|---------|
| `SearchPrompts` | Guided archive search workflows |
| `IngestionPrompts` | Document ingestion guidance |
| `ClassificationPrompts` | Classification review workflows |

#### 4. AccessGuard (Authentication & Authorization)

Every tool call goes through `AccessGuard` which enforces role-based access control:

```php
class AccessGuard
{
    public function authenticate(?string $apiKey, ?string $sessionUser): ?array;
    public function requireAuth(): void;           // throws RuntimeException if not authenticated
    public function requireModule(string $module): void;  // throws if module access denied
    public function canAccessKlas(string $kode): bool;    // classification code check
    public function filterArsip(array $records): array;    // filter records by access
}
```

**Role hierarchy:**

| Role | Klas Access | Module Access |
|------|-------------|---------------|
| `admin` | All (SDM, KEU, HKP, RND, UMUM, ...) | All modules |
| `user` | Restricted (e.g., SDM, HKP only) | Restricted (e.g., sirkulasi only) |
| unauthenticated | None | None |

**Authentication methods:**
1. API Key (via `ApiKeyService`)
2. Session username (from HTTP session)
3. `MCP_TEST_USERNAME` environment variable (dev/testing fallback)

#### 5. Supporting Services

| Service | Purpose |
|---------|---------|
| `OcrService` | Tesseract-based OCR for scanned documents |
| `DocumentParser` | Extract metadata (date, nomor arsip) from text |
| `DuplicateDetector` | Check for duplicate archive entries |
| `ConfidenceScorer` | Score AI extraction confidence (0.0-1.0) |

### Database Schema (MCP-specific tables)

```
ai_ingestion_queue     → Document upload/processing queue
ai_verification_queue  → Human verification queue for AI-extracted fields
ai_classifications     → AI classification suggestions with confidence
retention_schedules    → Retention policies per classification code
retention_actions      → Disposition proposals and legal holds
legal_holds            → Active legal holds on archive records
document_access_log    → Audit trail of all document access
migration_jobs         → Bulk import job tracking
migration_items        → Individual items in migration batches
```

---

## MCP Client

### What is it?

The MCP Client is the **AI assistant's interface** to the Arteri-2 MCP Server. In this architecture, the "client" is typically an AI model (Claude, GPT, etc.) running in an environment that supports MCP (like Claude Desktop, VS Code with Copilot, or Hermes Agent).

### Connection Flow

```
┌──────────────┐         ┌──────────────────┐         ┌──────────────┐
│   AI Model   │         │   MCP Client     │         │  MCP Server  │
│  (Claude/    │◄───────►│  (Transport)     │◄───────►│  (Arteri-2)  │
│   GPT/etc)   │  JSON-RPC│                  │  Stdio/ │              │
└──────────────┘         └──────────────────┘  HTTP    └──────────────┘
```

### Transport Modes

#### 1. Stdio Transport (CLI Integration)

Used when AI assistants run as CLI tools (e.g., Claude Desktop, Hermes Agent):

```bash
# Server side
php spark mcp:serve --transport=stdio

# Client config (e.g., claude_desktop_config.json)
{
  "mcpServers": {
    "arteri": {
      "command": "php",
      "args": ["/path/to/arteri-2/spark", "mcp:serve", "--transport=stdio"],
      "cwd": "/path/to/arteri-2"
    }
  }
}
```

**Communication:** JSON-RPC 2.0 over stdin/stdout

```
Client → Server: {"jsonrpc":"2.0","method":"tools/call","params":{"name":"natural_language_search","arguments":{"query":"surat keputusan 2026"}}}
Server → Client: {"jsonrpc":"2.0","result":{"content":[{"type":"text","text":"Found 3 results..."}]}}
```

#### 2. HTTP Transport (Web Integration)

Used for web-based or remote MCP clients:

```bash
# Server side
php spark mcp:serve --transport=http --port=8090

# Client connects via HTTP POST
curl -X POST http://localhost:8090/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <api-key>" \
  -d '{"jsonrpc":"2.0","method":"tools/list","id":1}'
```

### Client Interaction Flow

```
1. Discovery
   Client → Server: methods/list (get available tools)
   Client → Server: resources/list (get available resources)
   Client → Server: prompts/list (get available prompts)

2. Authentication
   Client → Server: tools/call with API key or session
   Server: validates via AccessGuard
   Server: returns error if unauthorized

3. Tool Execution
   Client → Server: tools/call {name: "natural_language_search", arguments: {query: "..."}}
   Server: AccessGuard.requireAuth()
   Server: AccessGuard.requireModule('search')
   Server: executes tool logic
   Server: returns result

4. Resource Access
   Client → Server: resources/read {uri: "arteri://arsip/123"}
   Server: fetches from database
   Server: returns JSON data
```

### Example: AI Model Using MCP Tools

When a user asks an AI assistant: *"Find all archive documents about financial reports from 2026"*

```
AI Model internal reasoning:
1. User wants to search archives
2. I have access to MCP tool "natural_language_search"
3. I should call it with appropriate query

AI → MCP Server:
{
  "method": "tools/call",
  "params": {
    "name": "natural_language_search",
    "arguments": {
      "query": "laporan keuangan 2026",
      "limit": 10
    }
  }
}

MCP Server:
1. AccessGuard.requireAuth() → validates session/API key
2. AccessGuard.filterArsip() → filters by user's allowed klas
3. SearchHandler executes query
4. Returns filtered results

MCP Server → AI:
{
  "result": {
    "content": [{
      "type": "text",
      "text": "Found 5 archive documents matching 'laporan keuangan 2026':\n1. [SDM.01.001] Laporan Keuangan Q1 2026\n2. [KEU.01.003] Laporan Keuangan Tahunan 2026\n..."
    }]
  }
}

AI → User:
"Found 5 archive documents about financial reports from 2026:
1. SDM.01.001 - Laporan Keuangan Q1 2026
2. KEU.01.003 - Laporan Keuangan Tahunan 2026
..."
```

### MCP Client Configuration Examples

#### Claude Desktop

```json
{
  "mcpServers": {
    "arteri": {
      "command": "php",
      "args": ["/var/www/arteri-2/spark", "mcp:serve", "--transport=stdio"],
      "cwd": "/var/www/arteri-2",
      "env": {
        "MCP_TEST_USERNAME": "admin"
      }
    }
  }
}
```

#### Hermes Agent

```yaml
# In Hermes config.yaml
mcp:
  servers:
    arteri:
      command: php
      args: ["/var/www/arteri-2/spark", "mcp:serve", "--transport=stdio"]
      cwd: /var/www/arteri-2
```

#### HTTP Client (curl)

```bash
# List available tools
curl -X POST http://localhost:8090/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/list","id":1}'

# Call a tool
curl -X POST http://localhost:8090/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-api-key" \
  -d '{
    "jsonrpc":"2.0",
    "method":"tools/call",
    "params":{
      "name":"natural_language_search",
      "arguments":{"query":"surat masuk juni 2026"}
    },
    "id":2
  }'
```

---

## Security Model

### Authentication Layers

```
┌─────────────────────────────────────┐
│ Layer 1: Transport Security         │
│ - Stdio: local process only        │
│ - HTTP: API key / session token    │
├─────────────────────────────────────┤
│ Layer 2: AccessGuard               │
│ - authenticate() → identify user   │
│ - requireAuth() → block匿名        │
├─────────────────────────────────────┤
│ Layer 3: Module Access             │
│ - requireModule('search')          │
│ - requireModule('arsip')           │
├─────────────────────────────────────┤
│ Layer 4: Data Filtering            │
│ - filterArsip() → klas-based       │
│ - canAccessKlas() → code-based     │
├─────────────────────────────────────┤
│ Layer 5: Audit Logging             │
│ - logAccess() → document_access_log│
└─────────────────────────────────────┘
```

### Access Control Matrix

| Tool | Admin | User (restricted) | Unauthenticated |
|------|-------|-------------------|-----------------|
| `natural_language_search` | ✅ All klas | ✅ Own klas only | ❌ |
| `ingest_document` | ✅ | ❌ (requires arsip module) | ❌ |
| `classify_document` | ✅ | ❌ | ❌ |
| `get_retention_schedule` | ✅ | ❌ | ❌ |
| `check_compliance` | ✅ | ❌ | ❌ |
| `create_migration_job` | ✅ | ❌ | ❌ |

---

## Testing

### Test Structure

```
tests/app/Mcp/
├── McpServerTest.php          # 11 tests: tables, config, parsers
└── ChatAccessGuardTest.php    # 70 tests: auth, roles, filtering
```

### Running Tests

```bash
cd ~/arteri-2
vendor/bin/phpunit tests/app/Mcp/ --testdox
```

### Test Coverage

**McpServerTest (11 tests):**
- MCP table existence (9 tables)
- AccessGuard authentication
- ConfidenceScorer thresholds
- DocumentParser extraction
- McpConfig validation

**ChatAccessGuardTest (70 tests):**
- Authentication (admin, user, unauthenticated, nonexistent)
- Klas access control (admin=all, user=restricted, unauth=none)
- Module access control (admin=all, user=restricted, unauth=none)
- filterArsip (admin returns all, user returns filtered)
- requireAuth / requireModule (exception handling)
- logAccess (audit trail creation)
- Case-insensitive klas matching

---

## File Structure

```
app/Mcp/
├── Config/
│   └── McpConfig.php              # Server constants, transport config
├── Server/
│   └── McpServer.php              # Main server (tool/resource/prompt registration)
├── Handlers/
│   ├── IngestionHandler.php       # Document upload & OCR
│   ├── ClassificationHandler.php  # AI classification
│   ├── SearchHandler.php          # Archive search
│   ├── RetentionHandler.php       # Retention policies
│   ├── ComplianceHandler.php      # Compliance checks
│   └── MigrationHandler.php       # Bulk import
├── Resources/
│   ├── ArsipResource.php          # Archive data exposure
│   └── SystemResource.php         # System health/stats
├── Prompts/
│   ├── SearchPrompts.php          # Search workflow templates
│   ├── IngestionPrompts.php       # Ingestion guidance
│   └── ClassificationPrompts.php  # Classification review
├── Models/
│   ├── AiIngestionQueueModel.php
│   ├── AiVerificationQueueModel.php
│   ├── AiClassificationModel.php
│   ├── DocumentAccessLogModel.php
│   ├── LegalHoldModel.php
│   ├── MigrationJobModel.php
│   ├── MigrationItemModel.php
│   ├── RetentionScheduleModel.php
│   └── RetentionActionModel.php
├── Services/
│   ├── AccessGuard.php            # Auth & authorization
│   ├── ConfidenceScorer.php       # AI confidence scoring
│   ├── DocumentParser.php         # Metadata extraction
│   ├── DuplicateDetector.php      # Duplicate detection
│   └── OcrService.php             # Tesseract OCR wrapper
└── Commands/
    └── McpServe.php               # CLI command (spark mcp:serve)
```
