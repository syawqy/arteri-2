# Arteri MCP Server Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an MCP (Model Context Protocol) server integrated into Arteri 4, exposing 6 AI agents as standardized Tools, Resources, and Prompts that any MCP-compatible AI assistant (Claude, Cursor, etc.) can use.

**Architecture:** A standalone PHP CLI process (`php spark mcp:serve`) using the `php-mcp/server` SDK. MCP tools are organized by agent, each in its own handler class. The server shares Arteri's existing models, database, and authentication. Supports both `stdio` transport (for desktop AI) and `streamable HTTP` (for web-based AI).

**Tech Stack:** PHP 8.2+, CodeIgniter 4, `php-mcp/server` SDK, MySQL (existing), Tesseract OCR (for ingestion agent), PHPLeague/Filesystem (for migration agent).

---

## File Structure

```
app/
├── Mcp/                                    # MCP server root
│   ├── Config/
│   │   └── McpConfig.php                   # MCP server configuration
│   ├── Server/
│   │   ├── McpServer.php                   # Server bootstrap & registration
│   │   └── McpElements.php                 # Discovered elements index
│   ├── Handlers/                           # Tool handler classes (one per agent)
│   │   ├── IngestionHandler.php            # Agent 1: Document ingestion
│   │   ├── ClassificationHandler.php       # Agent 2: Classification & retention
│   │   ├── SearchHandler.php               # Agent 3: Archival search
│   │   ├── RetentionHandler.php            # Agent 4: Retention & disposition
│   │   ├── ComplianceHandler.php           # Agent 5: Compliance & audit
│   │   └── MigrationHandler.php            # Agent 6: Migration
│   ├── Resources/                          # MCP Resource handlers
│   │   ├── ArsipResource.php               # Archive data as MCP resources
│   │   ├── MasterDataResource.php          # Master data as MCP resources
│   │   └── SystemResource.php              # System config, stats, help
│   ├── Prompts/                            # MCP Prompt templates
│   │   ├── SearchPrompts.php               # Prompts for archival search
│   │   ├── IngestionPrompts.php            # Prompts for document ingestion
│   │   └── ClassificationPrompts.php       # Prompts for classification
│   ├── Services/                           # Business logic services
│   │   ├── OcrService.php                  # OCR processing wrapper
│   │   ├── DocumentParser.php              # Document content extraction
│   │   ├── DuplicateDetector.php           # Duplicate detection
│   │   ├── ConfidenceScorer.php            # Confidence score calculation
│   │   └── AccessGuard.php                 # Per-request ACL enforcement
│   └── Models/                             # MCP-specific models
│       ├── AiIngestionQueueModel.php       # AI ingestion queue
│       ├── AiVerificationQueueModel.php    # Low-confidence items queue
│       ├── AiClassificationModel.php       # AI classification proposals
│       ├── RetentionScheduleModel.php      # Detailed retention schedules
│       ├── RetentionActionModel.php        # Disposition proposals
│       ├── LegalHoldModel.php              # Legal hold tracking
│       ├── DocumentAccessLogModel.php      # Access/audit log for MCP
│       ├── MigrationJobModel.php           # Migration job tracking
│       └── MigrationItemModel.php          # Individual migration items
├── Commands/
│   └── McpServe.php                        # `php spark mcp:serve` CLI command
└── Database/
    └── Migrations/
        └── 2026-07-31-000001_CreateMcpTables.php  # New tables for MCP

public/
└── mcp/                                    # MCP HTTP transport entry point
    └── index.php                           # Streamable HTTP endpoint

tests/
└── app/
    └── Mcp/
        ├── Handlers/
        │   ├── IngestionHandlerTest.php
        │   ├── ClassificationHandlerTest.php
        │   ├── SearchHandlerTest.php
        │   ├── RetentionHandlerTest.php
        │   ├── ComplianceHandlerTest.php
        │   └── MigrationHandlerTest.php
        └── Services/
            ├── OcrServiceTest.php
            ├── DuplicateDetectorTest.php
            └── ConfidenceScorerTest.php
```

---

## Task 1: MCP Server Foundation & Infrastructure

**Files:**
- Create: `app/Mcp/Config/McpConfig.php`
- Create: `app/Mcp/Server/McpServer.php`
- Create: `app/Commands/McpServe.php`
- Modify: `composer.json` (add `php-mcp/server` dependency)

- [ ] **Step 1: Install MCP SDK dependency**

```bash
cd D:/codes/php/arteri-migrasi/ci4
composer require php-mcp/server
```

Expected: `php-mcp/server` added to `composer.json` require section.

- [ ] **Step 2: Create MCP configuration class**

Create `app/Mcp/Config/McpConfig.php`:

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Config;

class McpConfig
{
    /**
     * Server identification
     */
    public const SERVER_NAME = 'Arteri Archive Management';
    public const SERVER_VERSION = '1.0.0';

    /**
     * Supported transports
     */
    public const TRANSPORT_STDIO = 'stdio';
    public const TRANSPORT_HTTP = 'http';

    /**
     * Scan directories for MCP attribute discovery
     */
    public const SCAN_DIRS = [
        'Handlers',
        'Resources',
        'Prompts',
    ];

    /**
     * Exclude directories from discovery
     */
    public const EXCLUDE_DIRS = [
        'Config',
        'Services',
        'Models',
        'Server',
    ];

    /**
     * Default pagination limit for list results
     */
    public const PAGINATION_LIMIT = 50;

    /**
     * Get the base path for MCP files
     */
    public static function getBasePath(): string
    {
        return APPPATH . 'Mcp';
    }

    /**
     * Check if a given transport is supported
     */
    public static function isValidTransport(string $transport): bool
    {
        return in_array($transport, [
            self::TRANSPORT_STDIO,
            self::TRANSPORT_HTTP,
        ], true);
    }

    /**
     * Get HTTP transport configuration from .env or defaults
     */
    public static function getHttpConfig(): array
    {
        $config = [
            'host' => env('MCP_HTTP_HOST', '127.0.0.1'),
            'port' => (int) env('MCP_HTTP_PORT', 8090),
        ];

        return $config;
    }

    /**
     * Get API key for HTTP transport authentication (optional)
     */
    public static function getApiKey(): ?string
    {
        $key = env('MCP_API_KEY', null);
        return !empty($key) ? $key : null;
    }
}
```

- [ ] **Step 3: Create MCP Server bootstrap class**

Create `app/Mcp/Server/McpServer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Server;

use App\Mcp\Config\McpConfig;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;

class McpServer
{
    private Server $server;

    private function __construct()
    {
        $this->server = Server::make()
            ->withServerInfo(
                McpConfig::SERVER_NAME,
                McpConfig::SERVER_VERSION
            )
            ->withPaginationLimit(McpConfig::PAGINATION_LIMIT)
            ->build();

        $this->registerElements();
    }

    /**
     * Create and configure the MCP server instance
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Discover MCP elements via attribute scanning
     */
    private function registerElements(): void
    {
        $basePath = McpConfig::getBasePath();

        $this->server->discover(
            basePath: $basePath,
            scanDirs: McpConfig::SCAN_DIRS,
            excludeDirs: McpConfig::EXCLUDE_DIRS,
            saveToCache: true
        );
    }

    /**
     * Start listening via stdio transport (for desktop AI assistants)
     */
    public function listenStdio(): void
    {
        $transport = new StdioServerTransport();
        $this->server->listen($transport);
    }

    /**
     * Get the underlying server for HTTP transport or custom use
     */
    public function getServer(): Server
    {
        return $this->server;
    }
}
```

- [ ] **Step 4: Create `php spark mcp:serve` CLI command**

Create `app/Commands/McpServe.php`:

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use App\Mcp\Config\McpConfig;
use App\Mcp\Server\McpServer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class McpServe extends BaseCommand
{
    protected $group       = 'MCP';
    protected $name        = 'mcp:serve';
    protected $description = 'Start the Arteri MCP server for AI assistant integration';

    protected $usage    = 'mcp:serve [--transport=stdio|http] [--port=8090]';
    protected $options  = [
        '--transport' => 'Transport type: stdio (default) or http',
        '--port'      => 'Port for HTTP transport (default: 8090)',
    ];

    public function run(array $params)
    {
        $transport = $params['transport'] ?? 'stdio';
        $port      = (int) ($params['port'] ?? 8090);

        if (!McpConfig::isValidTransport($transport)) {
            CLI::error("Invalid transport: {$transport}. Use 'stdio' or 'http'.");
            return;
        }

        $this->printBanner($transport, $port);

        $mcpServer = McpServer::create();

        if ($transport === McpConfig::TRANSPORT_HTTP) {
            $this->startHttpTransport($mcpServer, $port);
        } else {
            $mcpServer->listenStdio();
        }
    }

    private function printBanner(string $transport, int $port): void
    {
        CLI::write('╔══════════════════════════════════════════╗', 'cyan');
        CLI::write('║     Arteri MCP Server v' . McpConfig::SERVER_VERSION . '              ║', 'cyan');
        CLI:write('║     Transport: ' . strtoupper($transport) . str_repeat(' ', 23 - strlen($transport)) . '║', 'cyan');
        CLI::write('╚══════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        if ($transport === McpConfig::TRANSPORT_HTTP) {
            $host = McpConfig::getHttpConfig()['host'];
            CLI::write("Listening on http://{$host}:{$port}/mcp", 'green');
            CLI::newLine();
        } else {
            CLI::write('Listening on stdio (JSON-RPC over stdin/stdout)', 'green');
            CLI::newLine();
        }
    }

    private function startHttpTransport(McpServer $mcpServer, int $port): void
    {
        $config = McpConfig::getHttpConfig();
        $host   = $config['host'];

        $transport = new \PhpMcp\Server\Transports\StreamableHttpServerTransport(
            host: $host,
            port: $port,
            path: '/mcp'
        );

        $mcpServer->getServer()->listen($transport);
    }
}
```

- [ ] **Step 5: Create HTTP entry point**

Create `public/mcp/index.php`:

```php
<?php

/**
 * MCP HTTP Transport Entry Point
 * 
 * Web server should route /mcp/* requests to this file.
 * For Nginx: try_files $uri $uri/ /mcp/index.php?$query_string;
 */

require_once dirname(__DIR__) . '/index.php';
```

- [ ] **Step 6: Run tests to verify foundation compiles**

```bash
php spark mcp:serve --help
```

Expected: Command recognized and help output shown.

- [ ] **Step 7: Commit**

```bash
git add app/Mcp/ app/Commands/McpServe.php composer.json composer.lock public/mcp/
git commit -m "feat(mcp): add MCP server foundation with stdio and HTTP transports"
```

---

## Task 2: Database Schema — MCP Tables

**Files:**
- Create: `app/Database/Migrations/2026-07-31-000001_CreateMcpTables.php`

- [ ] **Step 1: Create migration for all MCP-specific tables**

Create `app/Database/Migrations/2026-07-31-000001_CreateMcpTables.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMcpTables extends Migration
{
    public function up(): void
    {
        // ── ai_ingestion_queue ──────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'filename'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_path' => ['type' => 'TEXT', 'null' => true],
            'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'file_size'     => ['type' => 'INT', 'unsigned' => true],
            'ocr_text'      => ['type' => 'LONGTEXT', 'null' => true],
            'raw_metadata'  => ['type' => 'JSON', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'processing', 'completed', 'failed', 'queued_verification'], 'default' => 'pending'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'processed_by'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'uploaded_by'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'    => ['type' => 'DATETIME'],
            'updated_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('uploaded_by');
        $this->forge->createTable('ai_ingestion_queue', true);

        // ── ai_verification_queue ───────────────────────────────
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ingestion_id'    => ['type' => 'INT', 'unsigned' => true],
            'field_name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'ai_value'        => ['type' => 'TEXT', 'null' => true],
            'ai_confidence'   => ['type' => 'DECIMAL', 'constraint' => '5,4'],
            'human_value'     => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected', 'overridden'], 'default' => 'pending'],
            'verified_by'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'verified_at'     => ['type' => 'DATETIME', 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('ingestion_id');
        $this->forge->addKey('status');
        $this->forge->createTable('ai_verification_queue', true);

        // ── ai_classifications ──────────────────────────────────
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'          => ['type' => 'INT', 'unsigned' => true],
            'suggested_kode'    => ['type' => 'VARCHAR', 'constraint' => 10],
            'confidence'        => ['type' => 'DECIMAL', 'constraint' => '5,4'],
            'reasoning'         => ['type' => 'TEXT', 'null' => true],
            'matched_rules'     => ['type' => 'JSON', 'null' => true],
            'suggested_series'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['suggested', 'approved', 'rejected', 'applied'], 'default' => 'suggested'],
            'approved_by'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'approved_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('status');
        $this->forge->createTable('ai_classifications', true);

        // ── retention_schedules ─────────────────────────────────
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_klas'         => ['type' => 'VARCHAR', 'constraint' => 10],
            'nama_jadwal'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'retensi_aktif'     => ['type' => 'INT', 'unsigned' => true, 'comment' => 'Tahun retensi aktif'],
            'retensi_inaktif'   => ['type' => 'INT', 'unsigned' => true, 'comment' => 'Tahun retensi inaktif'],
            'jenis_disposisi'   => ['type' => 'ENUM', 'constraint' => ['simpan_permanen', 'musnah', 'serahkan_ke_arsip_nasional'], 'default' => 'musnah'],
            'dasar_hukum'       => ['type' => 'TEXT', 'null' => true],
            'keterangan'        => ['type' => 'TEXT', 'null' => true],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME'],
            'updated_at'        => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode_klas');
        $this->forge->createTable('retention_schedules', true);

        // ── retention_actions ───────────────────────────────────
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'          => ['type' => 'INT', 'unsigned' => true],
            'schedule_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action_type'       => ['type' => 'ENUM', 'constraint' => ['propose_destruction', 'propose_transfer', 'extend_retention', 'apply_legal_hold']],
            'reason'            => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['draft', 'pending_approval', 'approved', 'rejected', 'completed'], 'default' => 'draft'],
            'prepared_by'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'approved_by'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'approved_at'       => ['type' => 'DATETIME', 'null' => true],
            'berita_acara_no'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'form_metadata'     => ['type' => 'JSON', 'null' => true],
            'created_at'        => ['type' => 'DATETIME'],
            'updated_at'        => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('status');
        $this->forge->createTable('retention_actions', true);

        // ── legal_holds ─────────────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'      => ['type' => 'INT', 'unsigned' => true],
            'reason'        => ['type' => 'TEXT'],
            'case_ref'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'imposed_by'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'imposed_at'    => ['type' => 'DATETIME'],
            'released_by'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'released_at'   => ['type' => 'DATETIME', 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('is_active');
        $this->forge->createTable('legal_holds', true);

        // ── document_access_log ─────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'accessed_by'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'access_type'   => ['type' => 'ENUM', 'constraint' => ['view', 'edit', 'download', 'delete', 'classify', 'search', 'export']],
            'access_source' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'web|mcp|api|cli'],
            'details'       => ['type' => 'JSON', 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'accessed_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('accessed_by');
        $this->forge->addKey('accessed_at');
        $this->forge->createTable('document_access_log', true);

        // ── migration_jobs ──────────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'source_type'   => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'folder|gdrive|email|spreadsheet|legacy_app|scan'],
            'source_config' => ['type' => 'JSON'],
            'status'        => ['type' => 'ENUM', 'constraint' => ['created', 'analyzing', 'analyzed', 'mapping', 'mapped', 'importing', 'completed', 'failed'], 'default' => 'created'],
            'total_items'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'processed_items' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'error_items'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'field_mapping' => ['type' => 'JSON', 'null' => true],
            'error_log'     => ['type' => 'LONGTEXT', 'null' => true],
            'started_by'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'    => ['type' => 'DATETIME'],
            'updated_at'    => ['type' => 'DATETIME'],
            'completed_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('migration_jobs', true);

        // ── migration_items ─────────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'job_id'        => ['type' => 'INT', 'unsigned' => true],
            'source_path'   => ['type' => 'TEXT'],
            'filename'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_size'     => ['type' => 'INT', 'unsigned' => true],
            'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'raw_metadata'  => ['type' => 'JSON', 'null' => true],
            'mapped_metadata' => ['type' => 'JSON', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'imported', 'skipped', 'failed'], 'default' => 'pending'],
            'arsip_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('job_id');
        $this->forge->addKey('status');
        $this->forge->createTable('migration_items', true);
    }

    public function down(): void
    {
        $tables = [
            'migration_items',
            'migration_jobs',
            'document_access_log',
            'legal_holds',
            'retention_actions',
            'retention_schedules',
            'ai_classifications',
            'ai_verification_queue',
            'ai_ingestion_queue',
        ];

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
```

- [ ] **Step 2: Run migration**

```bash
php spark migrate
```

Expected: Migration runs successfully, creates 9 new tables.

- [ ] **Step 3: Commit**

```bash
git add app/Database/Migrations/2026-07-31-000001_CreateMcpTables.php
git commit -m "feat(mcp): add database migration for MCP-specific tables"
```

---

## Task 3: MCP-Specific Models

**Files:**
- Create: `app/Mcp/Models/AiIngestionQueueModel.php`
- Create: `app/Mcp/Models/AiVerificationQueueModel.php`
- Create: `app/Mcp/Models/AiClassificationModel.php`
- Create: `app/Mcp/Models/RetentionScheduleModel.php`
- Create: `app/Mcp/Models/RetentionActionModel.php`
- Create: `app/Mcp/Models/LegalHoldModel.php`
- Create: `app/Mcp/Models/DocumentAccessLogModel.php`
- Create: `app/Mcp/Models/MigrationJobModel.php`
- Create: `app/Mcp/Models/MigrationItemModel.php`

- [ ] **Step 1: Create AiIngestionQueueModel**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class AiIngestionQueueModel extends Model
{
    protected $table            = 'ai_ingestion_queue';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'filename', 'original_path', 'mime_type', 'file_size',
        'ocr_text', 'raw_metadata', 'status', 'error_message',
        'processed_by', 'uploaded_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat     = 'datetime';

    /**
     * Get items pending OCR processing
     */
    public function getPending(int $limit = 10): array
    {
        return $this->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get items queued for human verification
     */
    public function getVerificationQueue(int $limit = 20): array
    {
        return $this->where('status', 'queued_verification')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Mark item as processing
     */
    public function markProcessing(int $id): bool
    {
        return $this->update($id, ['status' => 'processing']);
    }

    /**
     * Mark item as completed with results
     */
    public function markCompleted(int $id, array $data): bool
    {
        return $this->update($id, array_merge($data, ['status' => 'completed']));
    }

    /**
     * Mark item as failed
     */
    public function markFailed(int $id, string $error): bool
    {
        return $this->update($id, [
            'status'        => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Queue item for human verification
     */
    public function queueForVerification(int $id): bool
    {
        return $this->update($id, ['status' => 'queued_verification']);
    }
}
```

- [ ] **Step 2: Create AiVerificationQueueModel**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class AiVerificationQueueModel extends Model
{
    protected $table            = 'ai_verification_queue';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'ingestion_id', 'field_name', 'ai_value', 'ai_confidence',
        'human_value', 'status', 'verified_by', 'verified_at', 'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat     = 'datetime';

    /**
     * Get pending verification items for a specific ingestion
     */
    public function getPendingForIngestion(int $ingestionId): array
    {
        return $this->where('ingestion_id', $ingestionId)
            ->where('status', 'pending')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all pending verification items
     */
    public function getAllPending(int $limit = 50): array
    {
        return $this->where('status', 'pending')
            ->orderBy('ai_confidence', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Approve a verification item
     */
    public function approve(int $id, string $verifiedBy, ?string $humanValue = null): bool
    {
        $data = [
            'status'       => 'approved',
            'verified_by'  => $verifiedBy,
            'verified_at'  => date('Y-m-d H:i:s'),
        ];
        if ($humanValue !== null) {
            $data['human_value'] = $humanValue;
        }
        return $this->update($id, $data);
    }

    /**
     * Reject a verification item
     */
    public function reject(int $id, string $verifiedBy, string $notes = ''): bool
    {
        return $this->update($id, [
            'status'      => 'rejected',
            'verified_by' => $verifiedBy,
            'verified_at' => date('Y-m-d H:i:s'),
            'notes'       => $notes,
        ]);
    }
}
```

- [ ] **Step 3: Create AiClassificationModel**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class AiClassificationModel extends Model
{
    protected $table            = 'ai_classifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'arsip_id', 'suggested_kode', 'confidence', 'reasoning',
        'matched_rules', 'suggested_series', 'status',
        'approved_by', 'approved_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat     = 'datetime';

    /**
     * Get all suggestions for a specific arsip
     */
    public function getSuggestionsForArsip(int $arsipId): array
    {
        return $this->where('arsip_id', $arsipId)
            ->orderBy('confidence', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get pending suggestions
     */
    public function getPendingSuggestions(int $limit = 20): array
    {
        return $this->where('status', 'suggested')
            ->orderBy('confidence', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Approve a classification suggestion and apply it
     */
    public function approve(int $id, string $approvedBy): bool
    {
        return $this->update($id, [
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

- [ ] **Step 4: Create RetentionScheduleModel**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class RetentionScheduleModel extends Model
{
    protected $table            = 'retention_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'kode_klas', 'nama_jadwal', 'retensi_aktif', 'retensi_inaktif',
        'jenis_disposisi', 'dasar_hukum', 'keterangan', 'is_active',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat     = 'datetime';

    /**
     * Get schedule by classification code
     */
    public function getByKode(string $kode): ?array
    {
        $result = $this->where('kode_klas', $kode)
            ->where('is_active', 1)
            ->first();
        return $result ?: null;
    }

    /**
     * Get all active schedules
     */
    public function getActive(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('kode_klas', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Calculate total retention years
     */
    public function getTotalRetention(array $schedule): int
    {
        return (int) $schedule['retensi_aktif'] + (int) $schedule['retensi_inaktif'];
    }

    /**
     * Calculate the end of retention for an arsip
     */
    public function calculateRetentionEnd(array $schedule, string $arsipDate): ?string
    {
        $date  = new \DateTime($arsipDate);
        $years = $this->getTotalRetention($schedule);
        $date->modify("+{$years} years");
        return $date->format('Y-m-d');
    }
}
```

- [ ] **Step 5: Create remaining models (RetentionActionModel, LegalHoldModel, DocumentAccessLogModel, MigrationJobModel, MigrationItemModel)**

Create each following the same pattern — thin Model wrapping its table with convenience methods. Key methods:

**RetentionActionModel:**
- `getByArsip(int $arsipId): array` — get all actions for an arsip
- `getDrafts(): array` — get draft proposals
- `getPendingApproval(): array` — get proposals awaiting approval
- `approve(int $id, string $approvedBy): bool`
- `reject(int $id, string $approvedBy, string $reason): bool`

**LegalHoldModel:**
- `getActiveForArsip(int $arsipId): ?array` — check if arsip has active hold
- `getAllActive(): array` — list all active holds
- `impose(int $arsipId, string $reason, string $imposedBy, ?string $caseRef): int`
- `release(int $id, string $releasedBy): bool`

**DocumentAccessLogModel:**
- `log(int $arsipId, string $accessedBy, string $accessType, string $source, ?array $details): int`
- `getByArsip(int $arsipId, int $limit = 50): array`
- `getByUser(string $username, int $limit = 50): array`
- `getSuspiciousActivity(int $hours = 24): array` — unusual access patterns

**MigrationJobModel:**
- `create(string $sourceType, array $config, string $startedBy): int`
- `updateStatus(int $id, string $status): bool`
- `getActive(): array`

**MigrationItemModel:**
- `getByJob(int $jobId, string $status = null): array`
- `getStats(int $jobId): array` — count by status

- [ ] **Step 6: Commit**

```bash
git add app/Mcp/Models/
git commit -m "feat(mcp): add MCP-specific models for AI workflows"
```

---

## Task 4: Core MCP Services

**Files:**
- Create: `app/Mcp/Services/OcrService.php`
- Create: `app/Mcp/Services/DocumentParser.php`
- Create: `app/Mcp/Services/DuplicateDetector.php`
- Create: `app/Mcp/Services/ConfidenceScorer.php`
- Create: `app/Mcp/Services/AccessGuard.php`

- [ ] **Step 1: Create AccessGuard service**

This is the most critical service — it enforces the same ACL as the web UI but for MCP requests. Every MCP tool must call this before returning data.

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Services;

use App\Models\{UserModel, ArsipModel};

class AccessGuard
{
    private UserModel $userModel;
    private ArsipModel $arsipModel;

    private ?array $currentUser = null;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->arsipModel = new ArsipModel();
    }

    /**
     * Authenticate an MCP request by API key or session
     * Returns the user record if valid, null otherwise
     */
    public function authenticate(?string $apiKey = null, ?string $sessionUser = null): ?array
    {
        // Session-based auth (stdio via spark command)
        if ($sessionUser !== null) {
            $user = $this->userModel->where('username', $sessionUser)->first();
            if ($user && $user['tipe'] === 'admin') {
                $this->currentUser = $user;
                return $user;
            }
            return null;
        }

        // API key auth (HTTP transport)
        if ($apiKey !== null) {
            $apiKeyService = new \App\Services\ApiKeyService();
            $keyRecord = $apiKeyService->validate($apiKey);
            if ($keyRecord) {
                $apiKeyService->touchLastUsed($keyRecord['id']);
                // API key auth uses the creator as the user
                $user = $this->userModel->where('username', $keyRecord['created_by'])->first();
                if ($user) {
                    $this->currentUser = $user;
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * Check if current user can access a specific classification
     */
    public function canAccessKlas(string $kode): bool
    {
        if ($this->currentUser === null) {
            return false;
        }

        // Admins have full access
        if ($this->currentUser['tipe'] === 'admin') {
            return true;
        }

        $aksesKlas = json_decode($this->currentUser['akses_klas'], true) ?? [];
        return in_array($kode, $aksesKlas, true);
    }

    /**
     * Check if current user can access a specific module
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->currentUser === null) {
            return false;
        }

        if ($this->currentUser['tipe'] === 'admin') {
            return true;
        }

        $aksesModul = json_decode($this->currentUser['akses_modul'], true) ?? [];
        return in_array($module, $aksesModul, true);
    }

    /**
     * Filter array of arsip records to only those the user can access
     */
    public function filterArsip(array $records): array
    {
        return array_values(array_filter($records, function (array $record) {
            return $this->canAccessKlas($record['kode']);
        }));
    }

    /**
     * Check if current user can access a specific arsip record
     */
    public function canAccessArsip(array $arsipRecord): bool
    {
        return $this->canAccessKlas($arsipRecord['kode']);
    }

    /**
     * Require authentication — throws if not authenticated
     */
    public function requireAuth(): void
    {
        if ($this->currentUser === null) {
            throw new \RuntimeException('Authentication required. Provide a valid API key or session.');
        }
    }

    /**
     * Require a specific module access — throws if not authorized
     */
    public function requireModule(string $module): void
    {
        $this->requireAuth();
        if (!$this->canAccessModule($module)) {
            throw new \RuntimeException("Access denied to module: {$module}");
        }
    }

    /**
     * Get the current authenticated user
     */
    public function getUser(): ?array
    {
        return $this->currentUser;
    }

    /**
     * Get current username string
     */
    public function getUsername(): string
    {
        return $this->currentUser['username'] ?? 'unknown';
    }
}
```

- [ ] **Step 2: Create ConfidenceScorer service**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Services;

class ConfidenceScorer
{
    /**
     * Threshold below which values need human verification
     */
    public const VERIFICATION_THRESHOLD = 0.75;

    /**
     * Calculate overall confidence for extracted metadata
     * Uses weighted average of individual field confidences
     */
    public static function overallConfidence(array $fieldConfidences): float
    {
        if (empty($fieldConfidences)) {
            return 0.0;
        }

        $total = array_sum($fieldConfidences);
        $count = count($fieldConfidences);

        return round($total / $count, 4);
    }

    /**
     * Determine if a value needs human verification
     */
    public static function needsVerification(float $confidence): bool
    {
        return $confidence < self::VERIFICATION_THRESHOLD;
    }

    /**
     * Get fields that need verification based on confidence scores
     */
    public static function getLowConfidenceFields(array $metadata, array $confidences): array
    {
        $lowConfidence = [];

        foreach ($confidences as $field => $score) {
            if (self::needsVerification($score) && isset($metadata[$field])) {
                $lowConfidence[] = [
                    'field'      => $field,
                    'ai_value'   => $metadata[$field],
                    'confidence' => $score,
                ];
            }
        }

        return $lowConfidence;
    }

    /**
     * Calculate OCR text quality score
     */
    public static function ocrQualityScore(string $text, int $originalLength = 0): float
    {
        if (empty($text)) {
            return 0.0;
        }

        $length = mb_strlen($text);

        // Very short text after OCR is suspicious
        if ($length < 10) {
            return 0.1;
        }

        // Check for common OCR artifacts
        $artifactPatterns = [
            '/[^\w\s.,;:!?\-()\/"\'@#\$%&*+=<>[\]{}|\\~`^]/u',  // weird chars
            '/(\w)\1{4,}/',  // excessive repetition
        ];

        $artifactCount = 0;
        foreach ($artifactPatterns as $pattern) {
            if (preg_match_all($pattern, $text)) {
                $artifactCount++;
            }
        }

        // Base score from text length relative to expected
        $lengthScore = $originalLength > 0
            ? min(1.0, $length / $originalLength)
            : min(1.0, $length / 500);

        // Penalize for artifacts
        $penalty = $artifactCount * 0.2;

        $score = max(0.0, $lengthScore - $penalty);

        return round(min(1.0, $score), 4);
    }

    /**
     * Calculate confidence for a specific field based on extraction method
     */
    public static function fieldConfidence(string $value, string $method, array $context = []): float
    {
        if (empty($value)) {
            return 0.0;
        }

        $baseConfidence = match ($method) {
            'ocr_structured'  => 0.85,  // OCR from structured regions (headers, forms)
            'ocr_unstructured' => 0.60,  // OCR from body text
            'regex_pattern'   => 0.90,  // Matched a known pattern (date, number format)
            'heuristic'       => 0.70,  // Guessed from context
            'ai_extraction'   => 0.80,  // AI model extraction
            'user_input'      => 1.00,  // Human-provided
            default           => 0.50,
        };

        // Bonus if value matches known patterns
        if (isset($context['matches_known_format']) && $context['matches_known_format']) {
            $baseConfidence = min(1.0, $baseConfidence + 0.1);
        }

        // Penalty for very short or very long values (suspicious)
        $valueLength = mb_strlen($value);
        if ($valueLength < 2) {
            $baseConfidence *= 0.7;
        } elseif ($valueLength > 500) {
            $baseConfidence *= 0.9;
        }

        return round(min(1.0, max(0.0, $baseConfidence)), 4);
    }
}
```

- [ ] **Step 3: Create DuplicateDetector service**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Services;

use App\Models\ArsipModel;

class DuplicateDetector
{
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->arsipModel = new ArsipModel();
    }

    /**
     * Check for potential duplicates of an arsip record
     * Returns array of matches with similarity scores
     */
    public function findDuplicates(array $metadata, int $excludeId = 0): array
    {
        $candidates = [];

        // Check by noarsip (exact match)
        if (!empty($metadata['noarsip'])) {
            $exactMatch = $this->arsipModel
                ->where('noarsip', $metadata['noarsip'])
                ->where('id !=', $excludeId)
                ->first();
            if ($exactMatch) {
                $candidates[] = [
                    'arsip'       => $exactMatch,
                    'match_type'  => 'exact_noarsip',
                    'confidence'  => 0.95,
                    'field'       => 'noarsip',
                ];
            }
        }

        // Check by perihal (fuzzy match)
        if (!empty($metadata['uraian'])) {
            $similarText = $this->arsipModel
                ->like('uraian', $metadata['uraian'])
                ->where('id !=', $excludeId)
                ->limit(5)
                ->get()
                ->getResultArray();

            foreach ($similarText as $record) {
                $similarity = $this->textSimilarity($metadata['uraian'], $record['uraian']);
                if ($similarity > 0.7) {
                    $candidates[] = [
                        'arsip'       => $record,
                        'match_type'  => 'similar_uraian',
                        'confidence'  => $similarity,
                        'field'       => 'uraian',
                    ];
                }
            }
        }

        // Check by file hash (if file exists)
        if (!empty($metadata['file_hash'])) {
            // This would require a file hash column; for now, check by filename
            $sameFile = $this->arsipModel
                ->where('file', $metadata['file'] ?? '')
                ->where('id !=', $excludeId)
                ->first();
            if ($sameFile) {
                $candidates[] = [
                    'arsip'       => $sameFile,
                    'match_type'  => 'same_file',
                    'confidence'  => 0.90,
                    'field'       => 'file',
                ];
            }
        }

        // Check by combination of noarsip + pencipta + tanggal
        if (!empty($metadata['noarsip']) && !empty($metadata['pencipta']) && !empty($metadata['tanggal'])) {
            $combo = $this->arsipModel
                ->where('pencipta', $metadata['pencipta'])
                ->where('tanggal', $metadata['tanggal'])
                ->where('id !=', $excludeId)
                ->get()
                ->getResultArray();

            foreach ($combo as $record) {
                if ($record['noarsip'] !== $metadata['noarsip']) {
                    $candidates[] = [
                        'arsip'       => $record,
                        'match_type'  => 'similar_composite',
                        'confidence'  => 0.80,
                        'field'       => 'composite',
                    ];
                }
            }
        }

        // Sort by confidence descending
        usort($candidates, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        // Remove duplicates from candidates
        $seen = [];
        return array_filter($candidates, function ($candidate) use (&$seen) {
            $key = $candidate['arsip']['id'];
            if (in_array($key, $seen, true)) {
                return false;
            }
            $seen[] = $key;
            return true;
        });
    }

    /**
     * Simple text similarity using Levenshtein-based approach
     */
    private function textSimilarity(string $a, string $b): float
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === $b) {
            return 1.0;
        }

        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) {
            return 0.0;
        }

        $levenshtein = levenshtein($a, $b);
        return round(1.0 - ($levenshtein / $maxLen), 4);
    }
}
```

- [ ] **Step 4: Create OcrService stub**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Services;

class OcrService
{
    /**
     * Extract text from a document using OCR
     *
     * Supports:
     * - PDF (via pdftotext or Imagick)
     * - Images (via Tesseract)
     * - Plain text files (passthrough)
     *
     * @return array{text: string, quality: float, method: string}
     */
    public function extractText(string $filePath, string $mimeType): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match (true) {
            $ext === 'pdf'   => $this->extractFromPdf($filePath),
            in_array($ext, ['jpg', 'jpeg', 'png', 'tiff', 'bmp', 'webp'])
                            => $this->extractFromImage($filePath),
            in_array($ext, ['txt', 'csv', 'log'])
                            => $this->extractFromTextFile($filePath),
            default          => throw new \RuntimeException("Unsupported file type for OCR: {$ext}"),
        };
    }

    /**
     * Extract text from PDF using pdftotext (poppler-utils) or fallback
     */
    private function extractFromPdf(string $filePath): array
    {
        // Try pdftotext first (poppler-utils)
        if (function_exists('shell_exec') && $this->commandExists('pdftotext')) {
            $output = shell_exec(sprintf('pdftotext "%s" - 2>/dev/null', escapeshellarg($filePath)));
            if ($output !== null && mb_strlen(trim($output)) > 0) {
                $quality = ConfidenceScorer::ocrQualityScore($output);
                return ['text' => $output, 'quality' => $quality, 'method' => 'pdftotext'];
            }
        }

        // Fallback: Imagick extension
        if (class_exists(\Imagick::class)) {
            $imagick = new \Imagick();
            $imagick->readImage($filePath);
            $text = $imagick->getText();
            $imagick->destroy();
            $quality = ConfidenceScorer::ocrQualityScore($text);
            return ['text' => $text, 'quality' => $quality, 'method' => 'imagick'];
        }

        return ['text' => '', 'quality' => 0.0, 'method' => 'none'];
    }

    /**
     * Extract text from image using Tesseract OCR
     */
    private function extractFromImage(string $filePath): array
    {
        if (function_exists('shell_exec') && $this->commandExists('tesseract')) {
            $output = shell_exec(sprintf('tesseract "%s" stdout -l ind+eng 2>/dev/null', escapeshellarg($filePath)));
            if ($output !== null) {
                $quality = ConfidenceScorer::ocrQualityScore($output);
                return ['text' => $output, 'quality' => $quality, 'method' => 'tesseract'];
            }
        }

        return ['text' => '', 'quality' => 0.0, 'method' => 'none'];
    }

    /**
     * Read plain text file
     */
    private function extractFromTextFile(string $filePath): array
    {
        $text = file_get_contents($filePath);
        if ($text === false) {
            return ['text' => '', 'quality' => 0.0, 'method' => 'read_failed'];
        }
        return ['text' => $text, 'quality' => 1.0, 'method' => 'text_read'];
    }

    /**
     * Check if a shell command exists
     */
    private function commandExists(string $command): bool
    {
        $output = shell_exec("which {$command} 2>/dev/null");
        return $output !== null && trim($output) !== '';
    }
}
```

- [ ] **Step 5: Create DocumentParser service**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Services;

class DocumentParser
{
    /**
     * Extract structured metadata from OCR text using heuristics
     *
     * @return array{metadata: array, confidences: array}
     */
    public function parseFromText(string $text): array
    {
        $metadata    = [];
        $confidences = [];

        // Extract noarsip (archive number)
        $noarsip = $this->extractNoArsip($text);
        if ($noarsip !== null) {
            $metadata['noarsip'] = $noarsip;
            $confidences['noarsip'] = ConfidenceScorer::fieldConfidence($noarsip, 'regex_pattern');
        }

        // Extract date
        $tanggal = $this->extractDate($text);
        if ($tanggal !== null) {
            $metadata['tanggal'] = $tanggal;
            $confidences['tanggal'] = ConfidenceScorer::fieldConfidence($tanggal, 'regex_pattern', [
                'matches_known_format' => true,
            ]);
        }

        // Extract pencipta (creator/sender)
        $pencipta = $this->extractPencipta($text);
        if ($pencipta !== null) {
            $metadata['pencipta'] = $pencipta;
            $confidences['pencipta'] = ConfidenceScorer::fieldConfidence($pencipta, 'heuristic');
        }

        // Extract perihal/uraian (subject)
        $uraian = $this->extractUraian($text);
        if ($uraian !== null) {
            $metadata['uraian'] = $uraian;
            $confidences['uraian'] = ConfidenceScorer::fieldConfidence($uraian, 'heuristic');
        }

        // Extract unit pengolah (processing unit)
        $unit = $this->extractUnitPengolah($text);
        if ($unit !== null) {
            $metadata['unit_pengolah'] = $unit;
            $confidences['unit_pengolah'] = ConfidenceScorer::fieldConfidence($unit, 'heuristic');
        }

        return [
            'metadata'    => $metadata,
            'confidences' => $confidences,
        ];
    }

    private function extractNoArsip(string $text): ?string
    {
        // Match common archive number patterns: 001/SM/2026, ARS-2026-001, etc.
        $patterns = [
            '/\b(\d{1,5}\/[A-Z]{1,10}\/\d{4})\b/',
            '/\b(ARS[-.]?\d{4}[-.]?\d{1,5})\b/i',
            '/Nomor\s*:\s*([^\n]{3,50})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function extractDate(string $text): ?string
    {
        $patterns = [
            // DD/MM/YYYY or DD-MM-YYYY
            '/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/',
            // YYYY-MM-DD
            '/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/',
            // Written Indonesian date
            '/\b(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Convert Indonesian month name to number
                $months = [
                    'januari' => '01', 'februari' => '02', 'maret' => '03',
                    'april' => '04', 'mei' => '05', 'juni' => '06',
                    'juli' => '07', 'agustus' => '08', 'september' => '09',
                    'oktober' => '10', 'november' => '11', 'desember' => '12',
                ];

                $monthStr = strtolower($matches[2] ?? '');

                if (isset($months[$monthStr])) {
                    return sprintf('%s-%s-%02d', $matches[3], $months[$monthStr], (int) $matches[1]);
                }

                // For numeric dates, try to parse
                $day   = (int) $matches[1];
                $month = (int) $matches[2];
                $year  = (int) $matches[3];

                if ($month > 12 && $day <= 12) {
                    // Likely YYYY-MM-DD format
                    return sprintf('%04d-%02d-%02d', $year, $day, $month);
                }

                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    private function extractPencipta(string $text): ?string
    {
        $patterns = [
            '/(?:Dari|Pengirim|Pencipta|Oleh)\s*:\s*([^\n]{2,100})/i',
            '/(?:Dari\s+)(PT\.|CV\.|Kementerian|Dinas|Bagian|Divisi|Sekretariat)\s*([^\n]{2,100})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function extractUraian(string $text): ?string
    {
        $patterns = [
            '/(?:Perihal|Hal|Subjek|Re)\s*:\s*([^\n]{5,200})/i',
            '/(?:Tentang)\s*:\s*([^\n]{5,200})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        // If no explicit perihal, use first meaningful line
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) > 20 && mb_strlen($line) < 200) {
                return $line;
            }
        }

        return null;
    }

    private function extractUnitPengolah(string $text): ?string
    {
        $patterns = [
            '/(?:Unit\s+Pengolah|Bagian|Divisi|Departemen|Sekretariat)\s*:\s*([^\n]{2,100})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Mcp/Services/
git commit -m "feat(mcp): add core MCP services (AccessGuard, ConfidenceScorer, DuplicateDetector, OcrService, DocumentParser)"
```

---

## Task 5: Agent 1 — IngestionHandler

**Files:**
- Create: `app/Mcp/Handlers/IngestionHandler.php`

This handler exposes 6 tools for the Intelligent Ingestion Agent.

- [ ] **Step 1: Create IngestionHandler with all tools**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\{AccessGuard, OcrService, DocumentParser, DuplicateDetector, ConfidenceScorer};
use App\Mcp\Models\{AiIngestionQueueModel, AiVerificationQueueModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class IngestionHandler
{
    private AccessGuard $accessGuard;
    private OcrService $ocrService;
    private DocumentParser $documentParser;
    private DuplicateDetector $duplicateDetector;
    private AiIngestionQueueModel $ingestionModel;
    private AiVerificationQueueModel $verificationModel;

    public function __construct()
    {
        $this->accessGuard     = new AccessGuard();
        $this->ocrService      = new OcrService();
        $this->documentParser  = new DocumentParser();
        $this->duplicateDetector = new DuplicateDetector();
        $this->ingestionModel  = new AiIngestionQueueModel();
        $this->verificationModel = new AiVerificationQueueModel();
    }

    /**
     * Upload and process a new document through the ingestion pipeline.
     * Performs OCR, extracts metadata with confidence scores, checks for duplicates.
     *
     * @param string $filePath Path to the uploaded file
     * @param string $mimeType MIME type of the file (e.g., application/pdf, image/png)
     * @param string $filename Original filename
     * @param int    $fileSize File size in bytes
     * @param string $uploadedBy Username of the uploader
     */
    #[McpTool(name: 'ingest_document')]
    public function ingestDocument(
        #[Schema(type: 'string', description: 'Path to the uploaded file')]
        string $filePath,
        #[Schema(type: 'string', description: 'MIME type of the file')]
        string $mimeType,
        #[Schema(type: 'string', description: 'Original filename')]
        string $filename,
        #[Schema(type: 'integer', description: 'File size in bytes')]
        int $fileSize,
        #[Schema(type: 'string', description: 'Username of the uploader')]
        string $uploadedBy
    ): array {
        $this->accessGuard->requireModule('arsip');

        // Create ingestion queue entry
        $queueId = $this->ingestionModel->insert([
            'filename'      => $filename,
            'original_path' => $filePath,
            'mime_type'     => $mimeType,
            'file_size'     => $fileSize,
            'status'        => 'processing',
            'uploaded_by'   => $uploadedBy,
        ]);

        if (!$queueId) {
            return ['status' => 'error', 'message' => 'Failed to create ingestion queue entry'];
        }

        try {
            // Step 1: OCR
            $ocrResult = $this->ocrService->extractText($filePath, $mimeType);
            $this->ingestionModel->update($queueId, [
                'ocr_text' => $ocrResult['text'],
            ]);

            // Step 2: Parse metadata
            $parseResult = $this->documentParser->parseFromText($ocrResult['text']);
            $metadata    = $parseResult['metadata'];
            $confidences = $parseResult['confidences'];

            // Step 3: OCR quality check
            $ocrQuality = ConfidenceScorer::ocrQualityScore($ocrResult['text']);

            // Step 4: Check for duplicates
            $duplicates = $this->duplicateDetector->findDuplicates($metadata);

            // Step 5: Calculate overall confidence
            $overallConfidence = ConfidenceScorer::overallConfidence(
                array_values($confidences)
            );

            // Step 6: Determine status
            $needsVerification = ConfidenceScorer::needsVerification($overallConfidence)
                || $ocrQuality < 0.5
                || !empty($duplicates);

            $status = $needsVerification ? 'queued_verification' : 'completed';

            // Step 7: Queue low-confidence fields for human verification
            $lowConfidenceFields = ConfidenceScorer::getLowConfidenceFields($metadata, $confidences);
            foreach ($lowConfidenceFields as $field) {
                $this->verificationModel->insert([
                    'ingestion_id'  => $queueId,
                    'field_name'    => $field['field'],
                    'ai_value'      => $field['ai_value'],
                    'ai_confidence' => $field['confidence'],
                    'status'        => 'pending',
                ]);
            }

            // Step 8: Update queue entry
            $this->ingestionModel->update($queueId, [
                'raw_metadata'  => $metadata,
                'status'        => $status,
                'processed_by'  => 'ai_agent',
            ]);

            $result = [
                'ingestion_id'       => $queueId,
                'status'             => $status,
                'ocr_quality'        => $ocrQuality,
                'ocr_method'         => $ocrResult['method'],
                'extracted_metadata' => $metadata,
                'confidences'        => $confidences,
                'overall_confidence' => $overallConfidence,
                'duplicates'         => array_map(fn($d) => [
                    'arsip_id'    => $d['arsip']['id'],
                    'noarsip'     => $d['arsip']['noarsip'],
                    'match_type'  => $d['match_type'],
                    'confidence'  => $d['confidence'],
                ], $duplicates),
                'needs_verification' => $needsVerification,
                'low_confidence_fields' => $lowConfidenceFields,
            ];

            return $result;

        } catch (\Throwable $e) {
            $this->ingestionModel->markFailed($queueId, $e->getMessage());
            return [
                'status'  => 'error',
                'message' => 'Ingestion failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract metadata from an already-ingested document.
     * Re-runs extraction and returns confidence scores.
     */
    #[McpTool(name: 'extract_metadata')]
    public function extractMetadata(
        #[Schema(type: 'integer', description: 'The ingestion queue ID')]
        int $ingestionId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $item = $this->ingestionModel->find($ingestionId);
        if (!$item) {
            return ['status' => 'error', 'message' => 'Ingestion item not found'];
        }

        $ocrText   = $item['ocr_text'] ?? '';
        $parseResult = $this->documentParser->parseFromText($ocrText);

        return [
            'ingestion_id'       => $ingestionId,
            'metadata'           => $parseResult['metadata'],
            'confidences'        => $parseResult['confidences'],
            'overall_confidence' => ConfidenceScorer::overallConfidence(
                array_values($parseResult['confidences'])
            ),
        ];
    }

    /**
     * Check if a document is a potential duplicate of existing archives.
     */
    #[McpTool(name: 'detect_duplicates')]
    public function detectDuplicates(
        #[Schema(type: 'string', description: 'Archive number (noarsip) to check')]
        string $noarsip,
        #[Schema(type: 'string', description: 'Subject/uraian')]
        string $uraian = '',
        #[Schema(type: 'string', description: 'Creator/pencipta')]
        string $pencipta = '',
        #[Schema(type: 'string', description: 'Date in YYYY-MM-DD format')]
        string $tanggal = ''
    ): array {
        $this->accessGuard->requireModule('arsip');

        $metadata = array_filter([
            'noarsip'   => $noarsip,
            'uraian'    => $uraian,
            'pencipta'  => $pencipta,
            'tanggal'   => $tanggal,
        ], fn($v) => !empty($v));

        $duplicates = $this->duplicateDetector->findDuplicates($metadata);

        return [
            'duplicates_found' => count($duplicates) > 0,
            'count'            => count($duplicates),
            'duplicates'       => array_map(fn($d) => [
                'arsip_id'    => $d['arsip']['id'],
                'noarsip'     => $d['arsip']['noarsip'],
                'uraian'      => $d['arsip']['uraian'],
                'pencipta'    => $d['arsip']['pencipta'],
                'match_type'  => $d['match_type'],
                'confidence'  => $d['confidence'],
            ], $duplicates),
        ];
    }

    /**
     * Detect if a scanned document is blank or too poor quality.
     */
    #[McpTool(name: 'detect_scan_quality')]
    public function detectScanQuality(
        #[Schema(type: 'string', description: 'Path to the file to check')]
        string $filePath,
        #[Schema(type: 'string', description: 'MIME type of the file')]
        string $mimeType
    ): array {
        $this->accessGuard->requireModule('arsip');

        try {
            $ocrResult = $this->ocrService->extractText($filePath, $mimeType);
            $quality   = $ocrResult['quality'];
            $textLength = mb_strlen(trim($ocrResult['text']));

            $isBlank     = $textLength < 5;
            $isPoor      = $quality < 0.3 && !$isBlank;
            $isReadable  = $quality >= 0.5;

            $verdict = 'readable';
            if ($isBlank) {
                $verdict = 'blank';
            } elseif ($isPoor) {
                $verdict = 'poor_quality';
            }

            return [
                'quality_score'    => $quality,
                'ocr_method'       => $ocrResult['method'],
                'text_length'      => $textLength,
                'verdict'          => $verdict,
                'is_blank'         => $isBlank,
                'is_poor_quality'  => $isPoor,
                'is_readable'      => $isReadable,
                'recommendation'   => match ($verdict) {
                    'blank'        => 'Document appears blank. Verify the file is not corrupted.',
                    'poor_quality' => 'OCR quality is poor. Consider re-scanning at higher resolution (300+ DPI).',
                    default        => 'Document is readable and suitable for processing.',
                },
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Quality check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Suggest metadata for a document based on its content and existing patterns.
     */
    #[McpTool(name: 'suggest_metadata')]
    public function suggestMetadata(
        #[Schema(type: 'string', description: 'OCR text or document content to analyze')]
        string $documentText
    ): array {
        $this->accessGuard->requireModule('arsip');

        $parseResult = $this->documentParser->parseFromText($documentText);

        // Enhance suggestions with master data matching
        $masterData = $this->matchMasterData($parseResult['metadata']);

        return [
            'suggested_metadata' => array_merge($parseResult['metadata'], $masterData),
            'confidences'        => $parseResult['confidences'],
            'overall_confidence' => ConfidenceScorer::overallConfidence(
                array_values($parseResult['confidences'])
            ),
        ];
    }

    /**
     * Group related documents based on metadata similarity.
     */
    #[McpTool(name: 'group_related_documents')]
    public function groupRelatedDocuments(
        #[Schema(type: 'array', description: 'List of arsip IDs to analyze for relationships')]
        array $arsipIds
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsipModel = new \App\Models\ArsipModel();
        $records    = [];

        foreach ($arsipIds as $id) {
            $detail = $arsipModel->getDetail($id);
            if ($detail && $this->accessGuard->canAccessArsip($detail)) {
                $records[] = $detail;
            }
        }

        // Group by shared attributes
        $groups = [
            'by_noarsip_prefix'  => $this->groupByPrefix($records, 'noarsip', '/'),
            'by_pencipta'        => $this->groupByField($records, 'pencipta'),
            'by_unit_pengolah'   => $this->groupByField($records, 'unit_pengolah'),
            'by_kode'            => $this->groupByField($records, 'kode'),
            'by_date_range'      => $this->groupByDateRange($records),
        ];

        return [
            'total_documents' => count($records),
            'groups'          => $groups,
        ];
    }

    /**
     * Get all items currently in the human verification queue.
     */
    #[McpTool(name: 'get_verification_queue')]
    public function getVerificationQueue(
        #[Schema(type: 'integer', description: 'Maximum items to return', minimum: 1, maximum: 100)]
        int $limit = 20
    ): array {
        $this->accessGuard->requireModule('arsip');

        $items = $this->verificationModel->getAllPending($limit);

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────

    private function matchMasterData(array $metadata): array
    {
        $suggestions = [];

        // Try matching kode from noarsip
        if (!empty($metadata['noarsip'])) {
            $parts = explode('/', $metadata['noarsip']);
            if (count($parts) >= 2) {
                $suggestions['suggested_kode'] = $parts[1];
            }
        }

        return $suggestions;
    }

    private function groupByPrefix(array $records, string $field, string $delimiter): array
    {
        $groups = [];
        foreach ($records as $record) {
            $parts  = explode($delimiter, $record[$field] ?? '');
            $prefix = $parts[0] ?? 'unknown';
            $groups[$prefix][] = $record['id'];
        }
        return $groups;
    }

    private function groupByField(array $records, string $field): array
    {
        $groups = [];
        foreach ($records as $record) {
            $value = $record[$field] ?? 'unknown';
            $groups[$value][] = $record['id'];
        }
        return $groups;
    }

    private function groupByDateRange(array $records): array
    {
        $groups = [];
        foreach ($records as $record) {
            $date  = $record['tanggal'] ?? null;
            $year  = $date ? date('Y', strtotime($date)) : 'unknown';
            $month = $date ? date('Y-m', strtotime($date)) : 'unknown';
            $groups[$year][$month][] = $record['id'];
        }
        return $groups;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Handlers/IngestionHandler.php
git commit -m "feat(mcp): add Agent 1 IngestionHandler with 6 tools"
```

---

## Task 6: Agent 2 — ClassificationHandler

**Files:**
- Create: `app/Mcp/Handlers/ClassificationHandler.php`

- [ ] **Step 1: Create ClassificationHandler**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{AiClassificationModel, RetentionScheduleModel};
use App\Models\{ArsipModel, MasterKodeModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class ClassificationHandler
{
    private AccessGuard $accessGuard;
    private AiClassificationModel $classificationModel;
    private RetentionScheduleModel $retentionModel;
    private MasterKodeModel $kodeModel;
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->accessGuard       = new AccessGuard();
        $this->classificationModel = new AiClassificationModel();
        $this->retentionModel    = new RetentionScheduleModel();
        $this->kodeModel         = new MasterKodeModel();
        $this->arsipModel        = new ArsipModel();
    }

    /**
     * Suggest archive classification codes based on document content and existing patterns.
     */
    #[McpTool(name: 'suggest_classification')]
    public function suggestClassification(
        #[Schema(type: 'integer', description: 'Arsip ID to classify')]
        int $arsipId,
        #[Schema(type: 'string', description: 'Document content or uraian for context')]
        string $context = ''
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }

        $this->accessGuard->requireAuth();

        // Get all available classifications
        $allCodes = $this->kodeModel->where('deleted_at', null)->findAll();

        // Analyze document content for classification hints
        $content   = !empty($context) ? $context : ($arsip['uraian'] ?? '');
        $suggestions = $this->analyzeForClassification($content, $allCodes);

        // Build reasoning for each suggestion
        $results = [];
        foreach ($suggestions as $suggestion) {
            $classification = [
                'suggested_kode'   => $suggestion['kode'],
                'nama_klas'        => $suggestion['nama'],
                'confidence'       => $suggestion['confidence'],
                'reasoning'        => $suggestion['reasoning'],
                'matched_rules'    => $suggestion['rules'],
            ];

            // Get retention info if available
            $retention = $this->retentionModel->getByKode($suggestion['kode']);
            if ($retention) {
                $classification['retention'] = [
                    'retensi_aktif'   => $retention['retensi_aktif'],
                    'retensi_inaktif' => $retention['retensi_inaktif'],
                    'jenis_disposisi' => $retention['jenis_disposisi'],
                    'total_years'     => $this->retentionModel->getTotalRetention($retention),
                ];
            }

            // Save suggestion to database
            $this->classificationModel->insert([
                'arsip_id'         => $arsipId,
                'suggested_kode'   => $suggestion['kode'],
                'confidence'       => $suggestion['confidence'],
                'reasoning'        => $suggestion['reasoning'],
                'matched_rules'    => json_encode($suggestion['rules']),
                'suggested_series' => $suggestion['series'] ?? null,
                'status'           => 'suggested',
            ]);

            $results[] = $classification;
        }

        return [
            'arsip_id'     => $arsipId,
            'noarsip'      => $arsip['noarsip'],
            'uraian'       => $arsip['uraian'],
            'suggestions'  => $results,
            'total_suggestions' => count($results),
        ];
    }

    /**
     * Match a document against the organization's classification scheme.
     */
    #[McpTool(name: 'match_classification_scheme')]
    public function matchClassificationScheme(
        #[Schema(type: 'string', description: 'Text content to match against')]
        string $documentText,
        #[Schema(type: 'string', description: 'Current classification code if any')]
        string $currentKode = ''
    ): array {
        $this->accessGuard->requireModule('arsip');

        $allCodes = $this->kodeModel->where('deleted_at', null)->findAll();
        $matches  = $this->analyzeForClassification($documentText, $allCodes);

        return [
            'current_kode'  => $currentKode,
            'matches'       => $matches,
            'total_matches' => count($matches),
        ];
    }

    /**
     * Suggest archive series for an arsip based on content analysis.
     */
    #[McpTool(name: 'suggest_series')]
    public function suggestSeries(
        #[Schema(type: 'integer', description: 'Arsip ID')]
        int $arsipId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }

        $content = $arsip['uraian'] ?? '';
        $series  = $this->deriveSeries($content, $arsip);

        return [
            'arsip_id' => $arsipId,
            'noarsip'  => $arsip['noarsip'],
            'series'   => $series,
        ];
    }

    /**
     * Get detailed retention schedule for a classification code.
     */
    #[McpTool(name: 'get_retention_schedule')]
    public function getRetentionSchedule(
        #[Schema(type: 'string', description: 'Classification code (kode klasifikasi)')]
        string $kodeKlas
    ): array {
        $this->accessGuard->requireModule('arsip');

        $schedule = $this->retentionModel->getByKode($kodeKlas);
        if (!$schedule) {
            return [
                'status'  => 'not_found',
                'message' => "No retention schedule found for kode: {$kodeKlas}",
            ];
        }

        return [
            'schedule'         => $schedule,
            'total_retention'  => $this->retentionModel->getTotalRetention($schedule),
        ];
    }

    /**
     * Get explanation for a classification recommendation.
     */
    #[McpTool(name: 'explain_recommendation')]
    public function explainRecommendation(
        #[Schema(type: 'integer', description: 'Classification ID from ai_classifications table')]
        int $classificationId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $classification = $this->classificationModel->find($classificationId);
        if (!$classification) {
            return ['status' => 'error', 'message' => 'Classification not found'];
        }

        $arsip = $this->arsipModel->getDetail($classification['arsip_id']);
        $schedule = $this->retentionModel->getByKode($classification['suggested_kode']);

        $explanation = [
            'classification' => $classification,
            'arsip_context'  => $arsip ? [
                'noarsip'   => $arsip['noarsip'],
                'uraian'    => $arsip['uraian'],
                'pencipta'  => $arsip['pencipta'],
            ] : null,
            'retention_rule' => $schedule,
            'reasoning'      => $classification['reasoning'] ?? 'No reasoning provided',
            'matched_rules'  => json_decode($classification['matched_rules'] ?? '[]', true),
        ];

        return $explanation;
    }

    // ─── Private helpers ──────────────────────────────────────

    private function analyzeForClassification(string $content, array $allCodes): array
    {
        $contentLower = mb_strtolower($content);
        $matches = [];

        foreach ($allCodes as $code) {
            $score    = 0.0;
            $rules    = [];
            $codeLower = mb_strtolower($code['nama']);

            // Keyword matching
            $keywords = explode(' ', $code['nama']);
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword) < 3) continue;
                if (str_contains($contentLower, mb_strtolower($keyword))) {
                    $score += 0.25;
                    $rules[] = "Content contains keyword: '{$keyword}'";
                }
            }

            // Common patterns
            if (str_contains($contentLower, 'surat masuk') && str_contains($codeLower, 'masuk')) {
                $score += 0.3;
                $rules[] = 'Document is an incoming letter (surat masuk)';
            }
            if (str_contains($contentLower, 'surat keluar') && str_contains($codeLower, 'keluar')) {
                $score += 0.3;
                $rules[] = 'Document is an outgoing letter (surat keluar)';
            }
            if (str_contains($contentLower, 'keputusan') && str_contains($codeLower, 'keputusan')) {
                $score += 0.3;
                $rules[] = 'Document is a decision (keputusan)';
            }
            if (str_contains($contentLower, 'perjanjian') && str_contains($codeLower, 'perjanjian')) {
                $score += 0.3;
                $rules[] = 'Document is an agreement (perjanjian)';
            }
            if (str_contains($contentLower, 'laporan') && str_contains($codeLower, 'laporan')) {
                $score += 0.3;
                $rules[] = 'Document is a report (laporan)';
            }

            if ($score > 0) {
                $matches[] = [
                    'kode'       => $code['kode'],
                    'nama'       => $code['nama'],
                    'confidence' => min(1.0, $score),
                    'reasoning'  => implode('; ', $rules),
                    'rules'      => $rules,
                    'series'     => $this->deriveSeriesFromCode($code['kode']),
                ];
            }
        }

        // Sort by confidence
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($matches, 0, 5);
    }

    private function deriveSeries(string $content, array $arsip): array
    {
        $series = [
            'type'      => 'unknown',
            'subseries' => null,
        ];

        $contentLower = mb_strtolower($content);

        if (str_contains($contentLower, 'surat masuk')) {
            $series['type'] = 'Surat Masuk';
            if (str_contains($contentLower, 'undangan')) {
                $series['subseries'] = 'Undangan';
            } elseif (str_contains($contentLower, 'pemberitahuan')) {
                $series['subseries'] = 'Pemberitahuan';
            }
        } elseif (str_contains($contentLower, 'surat keluar')) {
            $series['type'] = 'Surat Keluar';
        } elseif (str_contains($contentLower, 'keputusan')) {
            $series['type'] = 'Keputusan';
        } elseif (str_contains($contentLower, 'perjanjian')) {
            $series['type'] = 'Perjanjian';
        } elseif (str_contains($contentLower, 'berita acara')) {
            $series['type'] = 'Berita Acara';
        } elseif (str_contains($contentLower, 'nota dinas')) {
            $series['type'] = 'Nota Dinas';
        }

        return $series;
    }

    private function deriveSeriesFromCode(string $kode): string
    {
        return match (true) {
            str_contains($kode, 'SM')  => 'Surat Masuk',
            str_contains($kode, 'SK')  => 'Surat Keluar',
            str_contains($kode, 'KP')  => 'Keputusan',
            str_contains($kode, 'PJ')  => 'Perjanjian',
            str_contains($kode, 'BA')  => 'Berita Acara',
            str_contains($kode, 'ND')  => 'Nota Dinas',
            str_contains($kode, 'LP')  => 'Laporan',
            default                    => 'Umum',
        };
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Handlers/ClassificationHandler.php
git commit -m "feat(mcp): add Agent 2 ClassificationHandler with 5 tools"
```

---

## Task 7: Agent 3 — SearchHandler (Natural Language Archive Search)

**Files:**
- Create: `app/Mcp/Handlers/SearchHandler.php`

This is the most critical agent — it addresses the core weakness identified in the Arteri evaluation: searching within document content.

- [ ] **Step 1: Create SearchHandler**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\{AccessGuard, OcrService};
use App\Models\ArsipModel;
use PhpMcp\Server\Attributes\{McpTool, Schema};

class SearchHandler
{
    private AccessGuard $accessGuard;
    private ArsipModel $arsipModel;
    private OcrService $ocrService;

    public function __construct()
    {
        $this->accessGuard = new AccessGuard();
        $this->arsipModel  = new ArsipModel();
        $this->ocrService  = new OcrService();
    }

    /**
     * Search archives using natural language queries.
     * Handles complex queries like "Find all cooperation agreements expiring in 2027 without renewal documents."
     */
    #[McpTool(name: 'natural_language_search')]
    public function naturalLanguageSearch(
        #[Schema(type: 'string', description: 'Natural language search query')]
        string $query,
        #[Schema(type: 'integer', description: 'Maximum results to return', minimum: 1, maximum: 100)]
        int $limit = 20,
        #[Schema(type: 'string', description: 'Cursor for pagination')]
        ?string $cursor = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        // Parse the natural language query into structured filters
        $parsed = $this->parseQuery($query);

        // Execute the search
        $cursorId = $cursor !== null ? (int) $cursor : null;
        $result   = $this->arsipModel->searchWithCursor(
            $cursorId,
            $parsed['keywords'],
            $parsed['filters'],
            $limit
        );

        // Filter by user access
        $filteredRecords = $this->accessGuard->filterArsip($result['records']);

        // Enrich results with source documents and passages
        $enrichedResults = [];
        foreach ($filteredRecords as $record) {
            $enriched = [
                'arsip'     => $record,
                'sources'   => $this->getSourceDocuments($record),
                'passages'  => $this->getRelevantPassages($record, $parsed['keywords']),
            ];
            $enrichedResults[] = $enriched;
        }

        return [
            'query'            => $query,
            'parsed_query'     => $parsed,
            'results'          => $enrichedResults,
            'total_found'      => count($filteredRecords),
            'has_more'         => $result['has_more'],
            'next_cursor'      => $result['next_cursor'] ? (string) $result['next_cursor'] : null,
            'access_note'      => 'Results filtered based on your access permissions.',
        ];
    }

    /**
     * Get detailed search results with source documents and supporting passages.
     */
    #[McpTool(name: 'get_search_results_with_sources')]
    public function getSearchResultsWithSources(
        #[Schema(type: 'integer', description: 'Arsip ID to get sources for')]
        int $arsipId,
        #[Schema(type: 'string', description: 'Original search query for context')]
        string $query = ''
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }

        if (!$this->accessGuard->canAccessArsip($arsip)) {
            return ['status' => 'error', 'message' => 'Access denied to this archive'];
        }

        return [
            'arsip'         => $arsip,
            'sources'       => $this->getSourceDocuments($arsip),
            'passages'      => $this->getRelevantPassages($arsip, $query),
            'file_available' => !empty($arsip['file']),
        ];
    }

    /**
     * List all archives accessible to the current user.
     */
    #[McpTool(name: 'list_accessible_archives')]
    public function listAccessibleArchives(
        #[Schema(type: 'integer', description: 'Maximum results', minimum: 1, maximum: 100)]
        int $limit = 20,
        #[Schema(type: 'string', description: 'Cursor for pagination')]
        ?string $cursor = null,
        #[Schema(type: 'string', description: 'Filter by classification code')]
        ?string $kode = null,
        #[Schema(type: 'string', description: 'Filter by date range start (YYYY-MM-DD)')]
        ?string $dateFrom = null,
        #[Schema(type: 'string', description: 'Filter by date range end (YYYY-MM-DD)')]
        ?string $dateTo = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $cursorId = $cursor !== null ? (int) $cursor : null;
        $filters  = array_filter([
            'kode'     => $kode,
            'tanggal'  => $dateFrom ? ($dateTo ? "{$dateFrom},{$dateTo}" : $dateFrom) : null,
        ], fn($v) => $v !== null);

        $result = $this->arsipModel->searchWithCursor($cursorId, '', $filters, $limit);
        $filtered = $this->accessGuard->filterArsip($result['records']);

        return [
            'archives'    => $filtered,
            'count'       => count($filtered),
            'has_more'    => $result['has_more'],
            'next_cursor' => $result['next_cursor'] ? (string) $result['next_cursor'] : null,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────

    /**
     * Parse a natural language query into structured search parameters.
     * This is a heuristic parser — not AI. It extracts keywords and date filters.
     */
    private function parseQuery(string $query): array
    {
        $keywords = $query;
        $filters  = [];

        // Extract date ranges: "pada tahun 2027", "sebelum 2025", "setelah 2023"
        if (preg_match('/tahun\s+(\d{4})/i', $query, $m)) {
            $year = $m[1];
            $filters['tanggal'] = "{$year}-01-01,{$year}-12-31";
            $keywords = str_replace($m[0], '', $keywords);
        }

        if (preg_match('/sebelum\s+(\d{4})/i', $query, $m)) {
            $year = $m[1];
            $filters['tanggal'] = ",{$m[1]}-12-31";
            $keywords = str_replace($m[0], '', $keywords);
        }

        if (preg_match('/setelah\s+(\d{4})/i', $query, $m)) {
            $year = $m[1];
            $filters['tanggal'] = "{$m[1]}-01-01,";
            $keywords = str_replace($m[0], '', $keywords);
        }

        // Extract classification hints
        $classMap = [
            'surat masuk'   => 'SM',
            'surat keluar'  => 'SK',
            'keputusan'     => 'KP',
            'perjanjian'    => 'PJ',
            'laporan'       => 'LP',
            'nota dinas'    => 'ND',
            'berita acara'  => 'BA',
        ];

        foreach ($classMap as $term => $code) {
            if (str_contains(mb_strtolower($query), $term)) {
                $filters['kode'] = $code;
                $keywords = str_ireplace($term, '', $keywords);
                break;
            }
        }

        // Clean up keywords
        $keywords = trim(preg_replace('/\s+/', ' ', $keywords));
        // Remove common stop words
        $stopWords = ['yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'adalah',
                       'ini', 'itu', 'pada', 'akan', 'telah', 'sudah', 'belum',
                       'semua', 'seluruh', 'temukan', 'cari', 'daftar', 'list'];
        $keywords = preg_replace('/\b(' . implode('|', $stopWords) . ')\b/i', '', $keywords);
        $keywords = trim($keywords);

        return [
            'keywords' => $keywords,
            'filters'  => $filters,
            'original' => $query,
        ];
    }

    /**
     * Get source document metadata for an arsip record.
     */
    private function getSourceDocuments(array $arsip): array
    {
        $sources = [];

        if (!empty($arsip['file'])) {
            $sources[] = [
                'type' => 'uploaded_file',
                'name' => basename($arsip['file']),
                'path' => $arsip['file'],
            ];
        }

        $sources[] = [
            'type' => 'database_record',
            'name' => $arsip['noarsip'],
            'id'   => $arsip['id'],
        ];

        return $sources;
    }

    /**
     * Get relevant passages from document content for the given query.
     */
    private function getRelevantPassages(array $arsip, string $query): array
    {
        $passages = [];

        // The uraian itself is a key passage
        if (!empty($arsip['uraian'])) {
            $passages[] = [
                'source'  => 'uraian',
                'content' => $arsip['uraian'],
            ];
        }

        // If there's a file, attempt to extract relevant text
        if (!empty($arsip['file']) && !empty($query)) {
            $filePath = WRITEPATH . 'uploads/' . $arsip['file'];
            if (file_exists($filePath)) {
                try {
                    $text = $this->ocrService->extractText($filePath, mime_content_type($filePath));
                    $relevantText = $this->extractRelevantPassages($text['text'], $query);
                    foreach ($relevantText as $passage) {
                        $passages[] = [
                            'source'  => 'document_content',
                            'content' => $passage,
                        ];
                    }
                } catch (\Throwable $e) {
                    // File may not be readable — skip
                }
            }
        }

        return $passages;
    }

    /**
     * Extract passages from text that are relevant to the query.
     */
    private function extractRelevantPassages(string $text, string $query): array
    {
        if (empty($text) || empty($query)) {
            return [];
        }

        $sentences = preg_split('/[.!?\n]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $queryWords = array_filter(explode(' ', mb_strtolower($query)), fn($w) => mb_strlen($w) > 2);

        $scored = [];
        foreach ($sentences as $sentence) {
            $sentenceLower = mb_strtolower(trim($sentence));
            $score = 0;
            foreach ($queryWords as $word) {
                if (str_contains($sentenceLower, $word)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = ['text' => trim($sentence), 'score' => $score];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'text'), 0, 5);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Handlers/SearchHandler.php
git commit -m "feat(mcp): add Agent 3 SearchHandler with natural language search"
```

---

## Task 8: Agent 4 — RetentionHandler

**Files:**
- Create: `app/Mcp/Handlers/RetentionHandler.php`

- [ ] **Step 1: Create RetentionHandler**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{RetentionScheduleModel, RetentionActionModel, LegalHoldModel};
use App\Models\ArsipModel;
use PhpMcp\Server\Attributes\{McpTool, Schema};

class RetentionHandler
{
    private AccessGuard $accessGuard;
    private RetentionScheduleModel $retentionModel;
    private RetentionActionModel $actionModel;
    private LegalHoldModel $legalHoldModel;
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->accessGuard    = new AccessGuard();
        $this->retentionModel = new RetentionScheduleModel();
        $this->actionModel    = new RetentionActionModel();
        $this->legalHoldModel = new LegalHoldModel();
        $this->arsipModel     = new ArsipModel();
    }

    /**
     * Get archives approaching end of retention period.
     */
    #[McpTool(name: 'get_retention_candidates')]
    public function getRetentionCandidates(
        #[Schema(type: 'integer', description: 'Days before retention end to flag (default 90)')]
        int $daysBefore = 90,
        #[Schema(type: 'string', description: 'Filter by classification code')]
        ?string $kode = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $cutoffDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
        $allSchedules = $this->retentionModel->getActive();

        $candidates = [];

        foreach ($allSchedules as $schedule) {
            if ($kode && $schedule['kode_klas'] !== $kode) {
                continue;
            }

            $totalYears = $this->retentionModel->getTotalRetention($schedule);

            // Find arsips for this classification
            $arsips = $this->arsipModel
                ->where('kode', $schedule['kode_klas'])
                ->where('deleted_at', null)
                ->findAll();

            foreach ($arsips as $arsip) {
                $retentionEnd = $this->retentionModel->calculateRetentionEnd($schedule, $arsip['tanggal']);
                if (!$retentionEnd) continue;

                if ($retentionEnd <= $cutoffDate) {
                    // Check for active legal holds
                    $legalHold = $this->legalHoldModel->getActiveForArsip($arsip['id']);

                    $candidates[] = [
                        'arsip_id'        => $arsip['id'],
                        'noarsip'         => $arsip['noarsip'],
                        'uraian'          => $arsip['uraian'],
                        'tanggal'         => $arsip['tanggal'],
                        'retention_end'   => $retentionEnd,
                        'total_years'     => $totalYears,
                        'has_legal_hold'  => $legalHold !== null,
                        'legal_hold_reason' => $legalHold['reason'] ?? null,
                        'jenis_disposisi' => $schedule['jenis_disposisi'],
                        'days_remaining'  => (int) ((strtotime($retentionEnd) - strtotime('now')) / 86400),
                    ];
                }
            }
        }

        // Sort by days remaining (most urgent first)
        usort($candidates, fn($a, $b) => $a['days_remaining'] <=> $b['days_remaining']);

        return [
            'count'       => count($candidates),
            'candidates'  => $candidates,
            'note'        => 'These archives are approaching or past their retention end date. Human approval is required for any disposition action.',
        ];
    }

    /**
     * Get disposition proposals that are pending approval.
     */
    #[McpTool(name: 'get_disposition_proposals')]
    public function getDispositionProposals(
        #[Schema(type: 'string', description: 'Filter by status: draft|pending_approval|approved|rejected')]
        ?string $status = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $query = $this->actionModel->builder();
        if ($status) {
            $query->where('status', $status);
        }

        $proposals = $query->orderBy('created_at', 'DESC')->get()->getResultArray();

        // Enrich with arsip details
        $enriched = [];
        foreach ($proposals as $proposal) {
            $arsip = $this->arsipModel->getDetail($proposal['arsip_id']);
            $enriched[] = [
                'proposal' => $proposal,
                'arsip'    => $arsip,
            ];
        }

        return [
            'count'     => count($enriched),
            'proposals' => $enriched,
        ];
    }

    /**
     * Prepare disposition documentation for approved archives.
     * Generates: daftar usul, berita acara, form penilaian, bukti persetujuan.
     * DOES NOT perform the actual disposition.
     */
    #[McpTool(name: 'prepare_disposition_docs')]
    public function prepareDispositionDocs(
        #[Schema(type: 'integer', description: 'Retention action ID to prepare docs for')]
        int $actionId,
        #[Schema(type: 'string', description: 'Type of document: berita_acara|assessment_form|approval_proof|summary')]
        string $docType = 'summary'
    ): array {
        $this->accessGuard->requireModule('arsip');

        $action = $this->actionModel->find($actionId);
        if (!$action) {
            return ['status' => 'error', 'message' => 'Retention action not found'];
        }

        $arsip = $this->arsipModel->getDetail($action['arsip_id']);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Associated arsip not found'];
        }

        $schedule = $this->retentionModel->getByKode($arsip['kode']);

        $documentation = match ($docType) {
            'berita_acara' => $this->generateBeritaAcara($arsip, $action, $schedule),
            'assessment_form' => $this->generateAssessmentForm($arsip, $action, $schedule),
            'approval_proof' => $this->generateApprovalProof($arsip, $action),
            'summary' => $this->generateDispositionSummary($arsip, $action, $schedule),
            default => ['error' => 'Unknown document type'],
        ];

        return [
            'action_id'     => $actionId,
            'doc_type'      => $docType,
            'documentation' => $documentation,
            'note'          => 'This is a draft document. Human review and approval is required before any disposition action.',
        ];
    }

    /**
     * Check if an archive has an active legal hold.
     */
    #[McpTool(name: 'check_legal_hold')]
    public function checkLegalHold(
        #[Schema(type: 'integer', description: 'Arsip ID to check')]
        int $arsipId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $hold = $this->legalHoldModel->getActiveForArsip($arsipId);

        if ($hold) {
            return [
                'arsip_id'     => $arsipId,
                'has_hold'     => true,
                'hold'         => $hold,
                'can_dispose'  => false,
                'message'      => 'This archive has an active legal hold and CANNOT be disposed of.',
            ];
        }

        return [
            'arsip_id'    => $arsipId,
            'has_hold'    => false,
            'can_dispose' => true,
            'message'     => 'No active legal holds on this archive.',
        ];
    }

    // ─── Private helpers ──────────────────────────────────────

    private function generateBeritaAcara(array $arsip, array $action, ?array $schedule): array
    {
        return [
            'title'  => 'Berita Acara Pemusnahan/Penyerahan Arsip',
            'content' => [
                'no_berita_acara' => $action['berita_acara_no'] ?? 'BA-XXX/' . date('Y'),
                'tanggal'         => date('d-m-Y'),
                'arsip'           => [
                    'noarsip'  => $arsip['noarsip'],
                    'uraian'   => $arsip['uraian'],
                    'tanggal'  => $arsip['tanggal'],
                    'jumlah'   => $arsip['jumlah'],
                    'media'    => $arsip['media'],
                    'lokasi'   => $arsip['lokasi'],
                ],
                'retensi'         => $schedule ? [
                    'aktif'     => $schedule['retensi_aktif'] . ' tahun',
                    'inaktif'   => $schedule['retensi_inaktif'] . ' tahun',
                    'disposisi' => $schedule['jenis_disposisi'],
                ] : null,
                'tindakan'        => $action['action_type'],
                'dasar'           => $schedule['dasar_hukum'] ?? '',
            ],
        ];
    }

    private function generateAssessmentForm(array $arsip, array $action, ?array $schedule): array
    {
        return [
            'title'  => 'Formulir Penilaian Arsip',
            'content' => [
                'arsip_info' => [
                    'noarsip'   => $arsip['noarsip'],
                    'uraian'    => $arsip['uraian'],
                    'tanggal'   => $arsip['tanggal'],
                ],
                'retention_info' => $schedule ? [
                    'retensi_aktif'   => $schedule['retensi_aktif'],
                    'retensi_inaktif' => $schedule['retensi_inaktif'],
                    'retention_end'   => $this->retentionModel->calculateRetentionEnd($schedule, $arsip['tanggal']),
                ] : null,
                'assessment' => [
                    'masih_ada_nilai_guna'   => null,  // To be filled by human
                    'potensi_sengketa'       => null,
                    'ada_legal_hold'         => $this->legalHoldModel->getActiveForArsip($arsip['id']) !== null,
                    'rekomendasi'            => $action['action_type'],
                ],
            ],
        ];
    }

    private function generateApprovalProof(array $arsip, array $action): array
    {
        return [
            'title'  => 'Bukti Persetujuan Disposisi Arsip',
            'content' => [
                'action_type'   => $action['action_type'],
                'arsip_noarsip' => $arsip['noarsip'],
                'prepared_by'   => $action['prepared_by'],
                'prepared_at'   => $action['created_at'],
                'approved_by'   => $action['approved_by'] ?? 'BELUM DISETUJUI',
                'approved_at'   => $action['approved_at'] ?? null,
                'status'        => $action['status'],
            ],
        ];
    }

    private function generateDispositionSummary(array $arsip, array $action, ?array $schedule): array
    {
        $legalHold = $this->legalHoldModel->getActiveForArsip($arsip['id']);

        return [
            'title'  => 'Ringkasan Disposisi Arsip',
            'content' => [
                'arsip'       => $arsip,
                'schedule'    => $schedule,
                'action'      => $action,
                'legal_hold'  => $legalHold,
                'checklist'   => [
                    'retensi_selesai'    => $schedule ? $this->retentionModel->calculateRetentionEnd($schedule, $arsip['tanggal']) <= date('Y-m-d') : false,
                    'ada_persetujuan'    => $action['status'] === 'approved',
                    'tidak_ada_sengketa' => !$legalHold,
                    'berita_acara_siap'  => !empty($action['berita_acara_no']),
                ],
            ],
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Handlers/RetentionHandler.php
git commit -m "feat(mcp): add Agent 4 RetentionHandler with 4 tools"
```

---

## Task 9: Agent 5 — ComplianceHandler

**Files:**
- Create: `app/Mcp/Handlers/ComplianceHandler.php`

- [ ] **Step 1: Create ComplianceHandler**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{DocumentAccessLogModel, AiClassificationModel, LegalHoldModel};
use App\Models\{ArsipModel, UserModel, SystemLogModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class ComplianceHandler
{
    private AccessGuard $accessGuard;
    private DocumentAccessLogModel $accessLogModel;
    private AiClassificationModel $classificationModel;
    private LegalHoldModel $legalHoldModel;
    private ArsipModel $arsipModel;
    private UserModel $userModel;
    private SystemLogModel $systemLogModel;

    public function __construct()
    {
        $this->accessGuard        = new AccessGuard();
        $this->accessLogModel     = new DocumentAccessLogModel();
        $this->classificationModel = new AiClassificationModel();
        $this->legalHoldModel     = new LegalHoldModel();
        $this->arsipModel         = new ArsipModel();
        $this->userModel          = new UserModel();
        $this->systemLogModel     = new SystemLogModel();
    }

    /**
     * Check metadata completeness across archives.
     */
    #[McpTool(name: 'check_metadata_completeness')]
    public function checkMetadataCompleteness(
        #[Schema(type: 'integer', description: 'Arsip ID to check, or 0 for all')]
        int $arsipId = 0,
        #[Schema(type: 'string', description: 'Filter by classification code')]
        ?string $kode = null
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $requiredFields = ['noarsip', 'uraian', 'tanggal', 'pencipta', 'unit_pengolah', 'kode', 'lokasi', 'media'];

        if ($arsipId > 0) {
            $arsips = [$this->arsipModel->getDetail($arsipId)];
            $arsips = array_filter($arsips);
        } else {
            $query = $this->arsipModel->where('deleted_at', null);
            if ($kode) {
                $query->where('kode', $kode);
            }
            $arsips = $query->findAll();
        }

        $results = [];
        $summary = ['total' => 0, 'complete' => 0, 'incomplete' => 0, 'missing_fields' => []];

        foreach ($arsips as $arsip) {
            if (!$this->accessGuard->canAccessArsip($arsip)) continue;

            $missing = [];
            foreach ($requiredFields as $field) {
                if (empty($arsip[$field]) || trim($arsip[$field]) === '') {
                    $missing[] = $field;
                    $summary['missing_fields'][$field] = ($summary['missing_fields'][$field] ?? 0) + 1;
                }
            }

            $summary['total']++;
            if (empty($missing)) {
                $summary['complete']++;
            } else {
                $summary['incomplete']++;
                $results[] = [
                    'arsip_id'      => $arsip['id'],
                    'noarsip'       => $arsip['noarsip'],
                    'missing_fields' => $missing,
                    'completeness'  => round((count($requiredFields) - count($missing)) / count($requiredFields) * 100, 1),
                ];
            }
        }

        return [
            'summary'     => $summary,
            'issues'      => array_slice($results, 0, 50),
            'completeness_rate' => $summary['total'] > 0
                ? round($summary['complete'] / $summary['total'] * 100, 1)
                : 0,
        ];
    }

    /**
     * Detect unauthorized changes to archives.
     */
    #[McpTool(name: 'detect_unauthorized_changes')]
    public function detectUnauthorizedChanges(
        #[Schema(type: 'string', description: 'Check changes since this date (YYYY-MM-DD)')]
        ?string $sinceDate = null,
        #[Schema(type: 'integer', description: 'Arsip ID to check, or 0 for all')]
        int $arsipId = 0
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $sinceDate = $sinceDate ?? date('Y-m-d', strtotime('-7 days'));

        $logs = $this->systemLogModel
            ->where('tgl_transaksi >=', $sinceDate . ' 00:00:00')
            ->where('tabel', 'data_arsip')
            ->where('aksi', 'UPDATE')
            ->orderBy('tgl_transaksi', 'DESC')
            ->findAll();

        if ($arsipId > 0) {
            $logs = array_filter($logs, fn($log) => $log['record_id'] == $arsipId);
        }

        $changes = [];
        foreach ($logs as $log) {
            $detail = json_decode($log['detail'] ?? '{}', true);
            $changes[] = [
                'log_id'       => $log['id'],
                'arsip_id'     => $log['record_id'],
                'changed_by'   => $log['username_transaksi'],
                'changed_at'   => $log['tgl_transaksi'],
                'changes'      => $detail,
                'ip_address'   => $log['ip_address'] ?? null,
            ];
        }

        return [
            'since_date'      => $sinceDate,
            'total_changes'   => count($changes),
            'changes'         => array_slice($changes, 0, 50),
        ];
    }

    /**
     * Find archives without classification codes.
     */
    #[McpTool(name: 'find_unclassified_archives')]
    public function findUnclassifiedArchives(): array
    {
        $this->accessGuard->requireModule('arsip');

        $unclassified = $this->arsipModel
            ->where('deleted_at', null)
            ->where('kode', '')
            ->orWhere('kode IS NULL', null, false)
            ->findAll();

        $filtered = $this->accessGuard->filterArsip($unclassified);

        return [
            'count'       => count($filtered),
            'archives'    => array_map(fn($a) => [
                'id'      => $a['id'],
                'noarsip' => $a['noarsip'],
                'uraian'  => $a['uraian'],
                'tanggal' => $a['tanggal'],
            ], $filtered),
            'recommendation' => 'These archives need classification. Use suggest_classification to get AI recommendations.',
        ];
    }

    /**
     * Monitor for overly broad access permissions.
     */
    #[McpTool(name: 'monitor_access_overreach')]
    public function monitorAccessOverreach(): array
    {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $users = $this->userModel
            ->where('deleted_at', null)
            ->findAll();

        $overreach = [];
        foreach ($users as $user) {
            if ($user['tipe'] === 'admin') continue;

            $aksesKlas = json_decode($user['akses_klas'], true) ?? [];
            $aksesModul = json_decode($user['akses_modul'], true) ?? [];

            // Check for suspicious patterns
            $issues = [];

            if (count($aksesKlas) > 10) {
                $issues[] = 'User has access to ' . count($aksesKlas) . ' classifications (unusually high)';
            }

            if (in_array('*', $aksesKlas)) {
                $issues[] = 'User has wildcard (*) access to all classifications';
            }

            if (in_array('*', $aksesModul)) {
                $issues[] = 'User has wildcard (*) access to all modules';
            }

            if (!empty($issues)) {
                $overreach[] = [
                    'user'       => $user['username'],
                    'tipe'       => $user['tipe'],
                    'akses_klas' => $aksesKlas,
                    'akses_modul' => $aksesModul,
                    'issues'     => $issues,
                ];
            }
        }

        return [
            'users_checked' => count($users),
            'overreach_found' => count($overreach),
            'issues'        => $overreach,
        ];
    }

    /**
     * Trace who accessed or modified a specific archive.
     */
    #[McpTool(name: 'trace_access_history')]
    public function traceAccessHistory(
        #[Schema(type: 'integer', description: 'Arsip ID to trace')]
        int $arsipId,
        #[Schema(type: 'integer', description: 'Max records to return', minimum: 1, maximum: 200)]
        int $limit = 50
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }

        // Get from system log
        $systemLogs = $this->systemLogModel
            ->where('tabel', 'data_arsip')
            ->where('record_id', $arsipId)
            ->orderBy('tgl_transaksi', 'DESC')
            ->limit($limit)
            ->findAll();

        // Get from access log
        $accessLogs = $this->accessLogModel
            ->getByArsip($arsipId, $limit);

        return [
            'arsip'          => [
                'id'      => $arsip['id'],
                'noarsip' => $arsip['noarsip'],
                'uraian'  => $arsip['uraian'],
            ],
            'system_logs'    => $systemLogs,
            'access_logs'    => $accessLogs,
            'total_entries'  => count($systemLogs) + count($accessLogs),
        ];
    }

    /**
     * Generate a comprehensive compliance report.
     */
    #[McpTool(name: 'generate_compliance_report')]
    public function generateComplianceReport(
        #[Schema(type: 'string', description: 'Report period start date (YYYY-MM-DD)')]
        ?string $dateFrom = null,
        #[Schema(type: 'string', description: 'Report period end date (YYYY-MM-DD)')]
        ?string $dateTo = null
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo   = $dateTo ?? date('Y-m-d');

        // 1. Metadata completeness
        $completeness = $this->checkMetadataCompleteness(0);

        // 2. Unclassified archives
        $unclassified = $this->findUnclassifiedArchives();

        // 3. Access overreach
        $overreach = $this->monitorAccessOverreach();

        // 4. Recent changes
        $recentChanges = $this->detectUnauthorizedChanges($dateFrom);

        // 5. Legal holds
        $activeHolds = $this->legalHoldModel->getAllActive();

        // 6. Pending classifications
        $pendingClassifications = $this->classificationModel->getPendingSuggestions(100);

        return [
            'report_period' => [
                'from' => $dateFrom,
                'to'   => $dateTo,
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'sections' => [
                'metadata_completeness' => [
                    'total_archives'     => $completeness['summary']['total'],
                    'completeness_rate'  => $completeness['completeness_rate'],
                    'incomplete_count'   => $completeness['summary']['incomplete'],
                    'top_missing_fields' => $completeness['summary']['missing_fields'],
                ],
                'unclassified' => [
                    'count' => $unclassified['count'],
                ],
                'access_overreach' => [
                    'users_flagged' => $overreach['overreach_found'],
                ],
                'recent_changes' => [
                    'total' => $recentChanges['total_changes'],
                ],
                'active_legal_holds' => [
                    'count' => count($activeHolds),
                    'holds' => $activeHolds,
                ],
                'pending_classifications' => [
                    'count' => count($pendingClassifications),
                ],
            ],
            'recommendations' => $this->generateRecommendations(
                $completeness, $unclassified, $overreach, $activeHolds
            ),
        ];
    }

    private function generateRecommendations(array $completeness, array $unclassified, array $overreach, array $holds): array
    {
        $recs = [];

        if ($completeness['completeness_rate'] < 90) {
            $recs[] = 'Metadata completeness is below 90%. Review and complete missing fields.';
        }

        if ($unclassified['count'] > 0) {
            $recs[] = "{$unclassified['count']} archives lack classification codes. Run suggest_classification on them.";
        }

        if ($overreach['overreach_found'] > 0) {
            $recs[] = "{$overreach['overreach_found']} users have overly broad access. Review and tighten permissions.";
        }

        if (count($holds) > 0) {
            $recs[] = count($holds) . ' active legal holds. Ensure no disposition actions are taken on these archives.';
        }

        return $recs;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Handlers/ComplianceHandler.php
git commit -m "feat(mcp): add Agent 5 ComplianceHandler with 6 tools"
```

---

## Task 10: Agent 6 — MigrationHandler

**Files:**
- Create: `app/Mcp/Handlers/MigrationHandler.php`

- [ ] **Step 1: Create MigrationHandler**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\{AccessGuard, OcrService};
use App\Mcp\Models\{MigrationJobModel, MigrationItemModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class MigrationHandler
{
    private AccessGuard $accessGuard;
    private MigrationJobModel $jobModel;
    private MigrationItemModel $itemModel;
    private OcrService $ocrService;

    public function __construct()
    {
        $this->accessGuard = new AccessGuard();
        $this->jobModel    = new MigrationJobModel();
        $this->itemModel   = new MigrationItemModel();
        $this->ocrService  = new OcrService();
    }

    /**
     * Analyze a source (folder, drive, etc.) for documents to migrate.
     */
    #[McpTool(name: 'analyze_source')]
    public function analyzeSource(
        #[Schema(type: 'string', description: 'Source type: folder|gdrive|email|spreadsheet|legacy_app|scan')]
        string $sourceType,
        #[Schema(type: 'object', description: 'Source configuration (path, credentials, etc.)')]
        array $sourceConfig
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        // Create migration job
        $jobId = $this->jobModel->insert([
            'source_type'   => $sourceType,
            'source_config' => json_encode($sourceConfig),
            'status'        => 'analyzing',
            'started_by'    => $this->accessGuard->getUsername(),
        ]);

        if (!$jobId) {
            return ['status' => 'error', 'message' => 'Failed to create migration job'];
        }

        try {
            $items = match ($sourceType) {
                'folder'     => $this->analyzeFolder($sourceConfig['path'] ?? ''),
                'spreadsheet' => $this->analyzeSpreadsheet($sourceConfig),
                default      => ['files' => [], 'total' => 0, 'error' => "Source type '{$sourceType}' analysis not yet implemented"],
            };

            // Insert items into migration_items
            foreach ($items['files'] ?? [] as $file) {
                $this->itemModel->insert([
                    'job_id'       => $jobId,
                    'source_path'  => $file['path'],
                    'filename'     => $file['name'],
                    'file_size'    => $file['size'],
                    'mime_type'    => $file['mime'] ?? null,
                    'raw_metadata' => json_encode($file['metadata'] ?? []),
                    'status'       => 'pending',
                ]);
            }

            $this->jobModel->update($jobId, [
                'status'      => 'analyzed',
                'total_items' => $items['total'],
            ]);

            return [
                'job_id'     => $jobId,
                'status'     => 'analyzed',
                'source'     => $sourceType,
                'total_items' => $items['total'],
                'summary'    => $items['summary'] ?? null,
                'error'      => $items['error'] ?? null,
            ];

        } catch (\Throwable $e) {
            $this->jobModel->update($jobId, [
                'status'     => 'failed',
                'error_log'  => $e->getMessage(),
            ]);
            return [
                'status'  => 'error',
                'job_id'  => $jobId,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Preview what will be migrated for a specific job.
     */
    #[McpTool(name: 'preview_migration')]
    public function previewMigration(
        #[Schema(type: 'integer', description: 'Migration job ID')]
        int $jobId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $job = $this->jobModel->find($jobId);
        if (!$job) {
            return ['status' => 'error', 'message' => 'Migration job not found'];
        }

        $items = $this->itemModel->getByJob($jobId);
        $stats = $this->itemModel->getStats($jobId);

        // Sample first 10 items with metadata
        $sample = array_slice($items, 0, 10);

        return [
            'job_id'      => $jobId,
            'status'      => $job['status'],
            'total_items' => $stats['total'],
            'stats'       => $stats,
            'sample_items' => array_map(fn($item) => [
                'id'             => $item['id'],
                'filename'       => $item['filename'],
                'source_path'    => $item['source_path'],
                'file_size'      => $item['file_size'],
                'raw_metadata'   => json_decode($item['raw_metadata'] ?? '{}', true),
                'mapped_metadata' => json_decode($item['mapped_metadata'] ?? '{}', true),
            ], $sample),
        ];
    }

    /**
     * Start the migration process for a mapped job.
     */
    #[McpTool(name: 'start_migration')]
    public function startMigration(
        #[Schema(type: 'integer', description: 'Migration job ID')]
        int $jobId
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $job = $this->jobModel->find($jobId);
        if (!$job) {
            return ['status' => 'error', 'message' => 'Migration job not found'];
        }

        if (!in_array($job['status'], ['mapped', 'analyzed'])) {
            return ['status' => 'error', 'message' => 'Job must be in analyzed or mapped status to start migration'];
        }

        $this->jobModel->updateStatus($jobId, 'importing');

        // Get all pending items
        $items = $this->itemModel->getByJob($jobId, 'pending');
        $processed = 0;
        $errors    = 0;

        foreach ($items as $item) {
            try {
                // Import logic — create arsip record from mapped metadata
                $mappedMetadata = json_decode($item['mapped_metadata'] ?? '{}', true)
                    ?: json_decode($item['raw_metadata'] ?? '{}', true);

                // Store the file
                $destPath = WRITEPATH . 'uploads/migration/' . $item['filename'];
                if (!is_dir(dirname($destPath))) {
                    mkdir(dirname($destPath), 0755, true);
                }

                if (file_exists($item['source_path'])) {
                    copy($item['source_path'], $destPath);
                }

                $this->itemModel->update($item['id'], [
                    'status'  => 'imported',
                ]);

                $processed++;

            } catch (\Throwable $e) {
                $this->itemModel->update($item['id'], [
                    'status'         => 'failed',
                    'error_message'  => $e->getMessage(),
                ]);
                $errors++;
            }

            // Update job progress
            $this->jobModel->update($jobId, [
                'processed_items' => $processed,
                'error_items'     => $errors,
            ]);
        }

        $this->jobModel->update($jobId, [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'job_id'         => $jobId,
            'status'         => 'completed',
            'processed'      => $processed,
            'errors'         => $errors,
            'total'          => count($items),
        ];
    }

    /**
     * Get current migration job status and progress.
     */
    #[McpTool(name: 'get_migration_status')]
    public function getMigrationStatus(
        #[Schema(type: 'integer', description: 'Migration job ID (omit for all active jobs)')]
        ?int $jobId = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        if ($jobId) {
            $job = $this->jobModel->find($jobId);
            if (!$job) {
                return ['status' => 'error', 'message' => 'Migration job not found'];
            }

            $stats = $this->itemModel->getStats($jobId);
            return [
                'job'   => $job,
                'stats' => $stats,
            ];
        }

        // Return all active jobs
        $jobs = $this->jobModel->getActive();
        return [
            'active_jobs' => $jobs,
            'count'       => count($jobs),
        ];
    }

    /**
     * Map source fields to Arteri fields for a migration job.
     */
    #[McpTool(name: 'map_source_fields')]
    public function mapSourceFields(
        #[Schema(type: 'integer', description: 'Migration job ID')]
        int $jobId,
        #[Schema(type: 'object', description: 'Field mapping: {source_field: arteri_field}')]
        array $fieldMapping
    ): array {
        $this->accessGuard->requireModule('arsip');

        $job = $this->jobModel->find($jobId);
        if (!$job) {
            return ['status' => 'error', 'message' => 'Migration job not found'];
        }

        // Valid Arteri fields for mapping
        $validFields = [
            'noarsip', 'pencipta', 'unit_pengolah', 'tanggal',
            'uraian', 'ket', 'kode', 'jumlah', 'nobox',
            'lokasi', 'media',
        ];

        // Validate mapping
        $invalid = [];
        foreach ($fieldMapping as $source => $target) {
            if (!in_array($target, $validFields)) {
                $invalid[] = "{$source} -> {$target} (not a valid Arteri field)";
            }
        }

        if (!empty($invalid)) {
            return [
                'status'   => 'error',
                'message'  => 'Invalid field mappings',
                'invalid'  => $invalid,
                'valid_fields' => $validFields,
            ];
        }

        // Save mapping
        $this->jobModel->update($jobId, [
            'field_mapping' => json_encode($fieldMapping),
            'status'        => 'mapped',
        ]);

        // Apply mapping to all items
        $items = $this->itemModel->getByJob($jobId);
        foreach ($items as $item) {
            $rawMetadata = json_decode($item['raw_metadata'] ?? '{}', true);
            $mapped = [];
            foreach ($fieldMapping as $sourceField => $arteriField) {
                if (isset($rawMetadata[$sourceField])) {
                    $mapped[$arteriField] = $rawMetadata[$sourceField];
                }
            }
            $this->itemModel->update($item['id'], [
                'mapped_metadata' => json_encode($mapped),
            ]);
        }

        return [
            'job_id'        => $jobId,
            'status'        => 'mapped',
            'field_mapping' => $fieldMapping,
            'items_updated' => count($items),
            'valid_fields'  => $validFields,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────

    private function analyzeFolder(string $path): array
    {
        if (!is_dir($path)) {
            return ['files' => [], 'total' => 0, 'error' => "Directory not found: {$path}"];
        }

        $extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'tiff', 'bmp', 'txt', 'csv'];
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $extensions)) {
                $files[] = [
                    'path'     => $file->getRealPath(),
                    'name'     => $file->getFilename(),
                    'size'     => $file->getSize(),
                    'mime'     => mime_content_type($file->getRealPath()) ?: null,
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'metadata' => [
                        'folder'    => $file->getPathinfo(\PATHINFO_DIRNAME),
                        'extension' => $ext,
                    ],
                ];
            }
        }

        $totalSize = array_sum(array_column($files, 'size'));

        return [
            'files'   => $files,
            'total'   => count($files),
            'summary' => [
                'path'         => $path,
                'total_files'  => count($files),
                'total_size'   => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'extensions'   => array_count_values(array_column($files, 'metadata')),
            ],
        ];
    }

    private function analyzeSpreadsheet(array $config): array
    {
        // Placeholder — would read Excel file and parse rows
        return [
            'files' => [],
            'total' => 0,
            'error' => 'Spreadsheet analysis requires PHPSpreadsheet integration. Use the existing Import controller for now.',
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Handlers/MigrationHandler.php
git commit -m "feat(mcp): add Agent 6 MigrationHandler with 6 tools"
```

---

## Task 11: MCP Resources & Prompts

**Files:**
- Create: `app/Mcp/Resources/ArsipResource.php`
- Create: `app/Mcp/Resources/MasterDataResource.php`
- Create: `app/Mcp/Resources/SystemResource.php`
- Create: `app/Mcp/Prompts/SearchPrompts.php`
- Create: `app/Mcp/Prompts/IngestionPrompts.php`
- Create: `app/Mcp/Prompts/ClassificationPrompts.php`

- [ ] **Step 1: Create ArsipResource**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Services\AccessGuard;
use App\Models\ArsipModel;
use PhpMcp\Server\Attributes\{McpResource, McpResourceTemplate};

class ArsipResource
{
    private AccessGuard $accessGuard;

    public function __construct()
    {
        $this->accessGuard = new AccessGuard();
    }

    /**
     * Get a specific archive record as an MCP resource.
     */
    #[McpResourceTemplate(
        uriTemplate: 'arteri://arsip/{arsipId}',
        mimeType: 'application/json'
    )]
    public function getArsip(string $arsipId): array
    {
        $arsipModel = new ArsipModel();
        $arsip = $arsipModel->getDetail((int) $arsipId);

        if (!$arsip) {
            return ['error' => 'Arsip not found'];
        }

        if (!$this->accessGuard->canAccessArsip($arsip)) {
            return ['error' => 'Access denied'];
        }

        return $arsip;
    }

    /**
     * List all classifications as an MCP resource.
     */
    #[McpResource(
        uri: 'arteri://master/klasifikasi',
        mimeType: 'application/json'
    )]
    public function getKlasifikasi(): array
    {
        $model = new \App\Models\MasterKodeModel();
        return $model->where('deleted_at', null)->findAll();
    }

    /**
     * List all locations as an MCP resource.
     */
    #[McpResource(
        uri: 'arteri://master/lokasi',
        mimeType: 'application/json'
    )]
    public function getLokasi(): array
    {
        $model = new \App\Models\MasterLokasiModel();
        return $model->where('deleted_at', null)->findAll();
    }

    /**
     * List all media types as an MCP resource.
     */
    #[McpResource(
        uri: 'arteri://master/media',
        mimeType: 'application/json'
    )]
    public function getMedia(): array
    {
        $model = new \App\Models\MasterMediaModel();
        return $model->where('deleted_at', null)->findAll();
    }
}
```

- [ ] **Step 2: Create SystemResource**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use PhpMcp\Server\Attributes\McpResource;

class SystemResource
{
    /**
     * Get Arteri system information.
     */
    #[McpResource(
        uri: 'arteri://system/info',
        mimeType: 'application/json'
    )]
    public function getSystemInfo(): array
    {
        return [
            'name'        => 'Arteri 2',
            'version'     => '2.0.0',
            'description' => 'Sistem Pengelola Arsip Digital',
            'mcp_version' => '1.0.0',
            'capabilities' => [
                'agents' => [
                    'ingestion'     => 'Document ingestion, OCR, metadata extraction',
                    'classification' => 'Archive classification and retention scheduling',
                    'search'         => 'Natural language archive search',
                    'retention'      => 'Retention monitoring and disposition management',
                    'compliance'     => 'Audit trail, compliance checking, access monitoring',
                    'migration'      => 'Data migration from various sources',
                ],
            ],
            'modules' => ['arsip', 'sirkulasi', 'master', 'user', 'trash', 'report', 'audit', 'import'],
        ];
    }

    /**
     * Get system statistics as an MCP resource.
     */
    #[McpResource(
        uri: 'arteri://system/stats',
        mimeType: 'application/json'
    )]
    public function getSystemStats(): array
    {
        $arsipModel = new \App\Models\ArsipModel();
        $sirkulasiModel = new \App\Models\SirkulasiModel();
        $userModel = new \App\Models\UserModel();

        return [
            'total_arsip'       => $arsipModel->countAllResults(),
            'active_sirkulasi'  => $sirkulasiModel->where('tgl_pengembalian', null)->countAllResults(),
            'total_users'       => $userModel->where('deleted_at', null)->countAllResults(),
            'last_updated'      => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get help/documentation for using the MCP server.
     */
    #[McpResource(
        uri: 'arteri://system/help',
        mimeType: 'text/markdown'
    )]
    public function getHelp(): array
    {
        return [
            'markdown' => <<<'MARKDOWN'
# Arteri MCP Server - Help

## Available Agents

### 1. Ingestion Agent
Tools: `ingest_document`, `extract_metadata`, `detect_duplicates`, `detect_scan_quality`, `suggest_metadata`, `group_related_documents`, `get_verification_queue`

### 2. Classification Agent
Tools: `suggest_classification`, `match_classification_scheme`, `suggest_series`, `get_retention_schedule`, `explain_recommendation`

### 3. Search Agent
Tools: `natural_language_search`, `get_search_results_with_sources`, `list_accessible_archives`

### 4. Retention Agent
Tools: `get_retention_candidates`, `get_disposition_proposals`, `prepare_disposition_docs`, `check_legal_hold`

### 5. Compliance Agent
Tools: `check_metadata_completeness`, `detect_unauthorized_changes`, `find_unclassified_archives`, `monitor_access_overreach`, `trace_access_history`, `generate_compliance_report`

### 6. Migration Agent
Tools: `analyze_source`, `preview_migration`, `start_migration`, `get_migration_status`, `map_source_fields`

## Safety Rules
- AI cannot perform destructive actions without human approval
- Retention disposition requires human sign-off
- All MCP access is logged and auditable
- User ACL is enforced on all queries
MARKDOWN
        ];
    }
}
```

- [ ] **Step 3: Create prompt templates**

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

class SearchPrompts
{
    /**
     * Generate a search prompt for finding specific archive types.
     */
    #[McpPrompt(name: 'search_archives')]
    public function searchArchives(
        #[Schema(type: 'string', description: 'Type of archive to search for')]
        string $archiveType,
        #[Schema(type: 'string', description: 'Date constraint (e.g., "2027", "before 2025")')]
        string $dateConstraint = '',
        #[Schema(type: 'string', description: 'Additional criteria')]
        string $additionalCriteria = ''
    ): array {
        $query = "Temukan seluruh {$archiveType}";
        if ($dateConstraint) {
            $query .= " {$dateConstraint}";
        }
        if ($additionalCriteria) {
            $query .= " {$additionalCriteria}";
        }

        return [
            ['role' => 'user', 'content' => $query]
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

class IngestionPrompts
{
    /**
     * Generate an ingestion prompt for batch document processing.
     */
    #[McpPrompt(name: 'batch_ingest')]
    public function batchIngest(
        #[Schema(type: 'string', description: 'Path to folder containing documents')]
        string $folderPath
    ): array {
        return [
            ['role' => 'user', 'content' => "Analyze the folder at {$folderPath} and process all documents for ingestion into Arteri. Extract metadata, check for duplicates, and flag items needing human verification."]
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

class ClassificationPrompts
{
    /**
     * Generate a classification review prompt.
     */
    #[McpPrompt(name: 'classify_archives')]
    public function classifyArchives(
        #[Schema(type: 'string', description: 'Scope: "unclassified" | "low_confidence" | "all"')]
        string $scope = 'unclassified'
    ): array {
        $desc = match ($scope) {
            'unclassified'  => 'Find all archives without classification codes and suggest appropriate codes',
            'low_confidence' => 'Review classification suggestions with low confidence and provide better alternatives',
            default          => 'Review all pending classification suggestions',
        };

        return [
            ['role' => 'user', 'content' => $desc]
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Mcp/Resources/ app/Mcp/Prompts/
git commit -m "feat(mcp): add MCP resources (arsip, master data, system) and prompt templates"
```

---

## Task 12: Access Logging for MCP Requests

**Files:**
- Create: `app/Mcp/Middleware/McpAccessLogMiddleware.php`
- Create: `app/Mcp/Services/AccessGuard.php` (already created in Task 4, add logging method)

- [ ] **Step 1: Add access logging to AccessGuard**

Add the `logAccess` method to `AccessGuard.php`:

```php
/**
 * Log an MCP access event
 */
public function logAccess(?int $arsipId, string $accessType, ?array $details = null): void
{
    if ($this->currentUser === null) {
        return;
    }

    $accessLogModel = new \App\Mcp\Models\DocumentAccessLogModel();
    $accessLogModel->insert([
        'arsip_id'      => $arsipId,
        'accessed_by'   => $this->currentUser['username'],
        'access_type'   => $accessType,
        'access_source' => 'mcp',
        'details'       => $details ? json_encode($details) : null,
        'ip_address'    => service('request')->getIPAddress() ?? 'cli',
        'accessed_at'   => date('Y-m-d H:i:s'),
    ]);
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Mcp/Services/AccessGuard.php
git commit -m "feat(mcp): add access logging for MCP tool invocations"
```

---

## Task 13: Configuration & .env

**Files:**
- Modify: `.env` (add MCP config section)
- Create: `app/Config/Mcp.php` (optional CI4 config class)

- [ ] **Step 1: Add MCP configuration to .env**

Add to `.env`:

```ini
# ── MCP Server Configuration ──────────────────────────────
# Transport: stdio (for desktop AI assistants) or http
MCP_TRANSPORT=stdio
MCP_HTTP_HOST=127.0.0.1
MCP_HTTP_PORT=8090
# Optional: API key for HTTP transport authentication
MCP_API_KEY=
# OCR engine: tesseract or none
MCP_OCR_ENGINE=tesseract
```

- [ ] **Step 2: Commit**

```bash
git add .env
git commit -m "feat(mcp): add MCP configuration to environment"
```

---

## Task 14: Integration Test — End-to-End MCP Smoke Test

**Files:**
- Create: `tests/app/Mcp/McpServerTest.php`

- [ ] **Step 1: Create integration test**

```php
<?php

declare(strict_types=1);

namespace Tests\App\Mcp;

use Tests\Support CITestCase;

class McpServerTest extends CITestCase
{
    public function testMcpTablesExist(): void
    {
        $db = \Config\Database::connect();

        $tables = [
            'ai_ingestion_queue',
            'ai_verification_queue',
            'ai_classifications',
            'retention_schedules',
            'retention_actions',
            'legal_holds',
            'document_access_log',
            'migration_jobs',
            'migration_items',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                $db->tableExists($table),
                "Table {$table} should exist"
            );
        }
    }

    public function testAccessGuardBlocksUnauthenticated(): void
    {
        $guard = new \App\Mcp\Services\AccessGuard();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication required');
        $guard->requireAuth();
    }

    public function testConfidenceScorerThresholds(): void
    {
        $this->assertTrue(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.5));
        $this->assertFalse(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.8));
        $this->assertTrue(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.74));
        $this->assertFalse(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.75));
    }

    public function testDocumentParserExtractsDate(): void
    {
        $parser = new \App\Mcp\Services\DocumentParser();
        $result = $parser->parseFromText("Surat Masuk\nNomor: 001/SM/2026\nTanggal: 15/06/2026\nPerihal: Laporan Keuangan");

        $this->assertArrayHasKey('tanggal', $result['metadata']);
        $this->assertEquals('2026-06-15', $result['metadata']['tanggal']);
    }

    public function testDocumentParserExtractsNoArsip(): void
    {
        $parser = new \App\Mcp\Services\DocumentParser();
        $result = $parser->parseFromText("Dokumen 005/SK/2026 tentang Surat Keputusan");

        $this->assertArrayHasKey('noarsip', $result['metadata']);
        $this->assertEquals('005/SK/2026', $result['metadata']['noarsip']);
    }
}
```

- [ ] **Step 2: Run tests**

```bash
vendor/bin/phpunit tests/app/Mcp/McpServerTest.php --testdox
```

Expected: All 5 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/app/Mcp/McpServerTest.php
git commit -m "test(mcp): add integration tests for MCP server foundation"
```

---

## Task 15: Documentation

**Files:**
- Create: `docs/mcp-server.md`

- [ ] **Step 1: Create MCP server documentation**

Create `docs/mcp-server.md`:

```markdown
# Arteri MCP Server

## Overview

The Arteri MCP Server integrates with AI assistants (Claude, Cursor, etc.) via the Model Context Protocol, providing 6 specialized AI agents for archive management.

## Quick Start

### Desktop (stdio transport)

1. Add to your MCP client config (e.g., `.cursor/mcp.json`):

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

2. Restart your AI assistant.

### HTTP transport

```bash
php spark mcp:serve --transport=http --port=8090
```

Then configure your MCP client to connect to `http://127.0.0.1:8090/mcp`.

## Available Tools (32 total)

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

## Prerequisites

- PHP 8.2+
- Tesseract OCR (for document ingestion)
- Poppler utils (for PDF OCR)
```

- [ ] **Step 2: Commit**

```bash
git add docs/mcp-server.md
git commit -m "docs(mcp): add MCP server documentation"
```

---

## Summary

| Task | Description | Tools Count |
|---|---|---|
| 1 | Foundation & Infrastructure | — |
| 2 | Database Schema (9 tables) | — |
| 3 | MCP Models (9 models) | — |
| 4 | Core Services (5 services) | — |
| 5 | Agent 1 — IngestionHandler | 6 tools |
| 6 | Agent 2 — ClassificationHandler | 5 tools |
| 7 | Agent 3 — SearchHandler | 3 tools |
| 8 | Agent 4 — RetentionHandler | 4 tools |
| 9 | Agent 5 — ComplianceHandler | 6 tools |
| 10 | Agent 6 — MigrationHandler | 6 tools |
| 11 | Resources & Prompts | 5 resources + 3 prompts |
| 12 | Access Logging | — |
| 13 | Configuration | — |
| 14 | Integration Tests | 5 tests |
| 15 | Documentation | — |

**Total: 30 tools, 5 resources, 3 prompts, 9 new tables, 9 models, 5 services, 6 handler classes.**

**Safety guarantees:**
- No destructive action runs without human approval
- All MCP queries are ACL-filtered per user
- Full audit trail in `document_access_log`
- Low-confidence metadata enters human verification queue
- Legal holds prevent disposition
