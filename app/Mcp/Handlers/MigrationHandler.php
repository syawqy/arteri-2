<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{MigrationJobModel, MigrationItemModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class MigrationHandler
{
    private AccessGuard $accessGuard;
    private MigrationJobModel $jobModel;
    private MigrationItemModel $itemModel;

    public function __construct()
    {
        $this->accessGuard = new AccessGuard();
        $this->jobModel    = new MigrationJobModel();
        $this->itemModel   = new MigrationItemModel();
    }

    /**
     * Analyze a source (folder, drive, etc.) for documents to migrate.
     */
    #[McpTool(name: 'analyze_source')]
    public function analyzeSource(
        #[Schema(type: 'string', description: 'Source type: folder|gdrive|email|spreadsheet|legacy_app|scan')]
        string $sourceType,
        #[Schema(type: 'object', description: 'Source configuration')]
        array $sourceConfig
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $jobId = $this->jobModel->insert([
            'source_type'   => $sourceType,
            'source_config' => json_encode($sourceConfig),
            'status'        => 'analyzing',
            'started_by'    => $this->accessGuard->getUsername(),
        ]);

        if (!$jobId) return ['status' => 'error', 'message' => 'Failed to create migration job'];

        try {
            $items = match ($sourceType) {
                'folder'      => $this->analyzeFolder($sourceConfig['path'] ?? ''),
                'spreadsheet' => ['files' => [], 'total' => 0, 'error' => 'Spreadsheet analysis not yet implemented'],
                default       => ['files' => [], 'total' => 0, 'error' => "Source type '{$sourceType}' not yet implemented"],
            };

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
                'job_id'      => $jobId,
                'status'      => 'analyzed',
                'source'      => $sourceType,
                'total_items' => $items['total'],
                'summary'     => $items['summary'] ?? null,
                'error'       => $items['error'] ?? null,
            ];
        } catch (\Throwable $e) {
            $this->jobModel->update($jobId, ['status' => 'failed', 'error_log' => $e->getMessage()]);
            return ['status' => 'error', 'job_id' => $jobId, 'message' => 'Analysis failed: ' . $e->getMessage()];
        }
    }

    /**
     * Preview what will be migrated.
     */
    #[McpTool(name: 'preview_migration')]
    public function previewMigration(
        #[Schema(type: 'integer', description: 'Migration job ID')]
        int $jobId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $job = $this->jobModel->find($jobId);
        if (!$job) return ['status' => 'error', 'message' => 'Migration job not found'];

        $items = $this->itemModel->getByJob($jobId);
        $stats = $this->itemModel->getStats($jobId);
        $sample = array_slice($items, 0, 10);

        return [
            'job_id'       => $jobId,
            'status'       => $job['status'],
            'total_items'  => $stats['total'],
            'stats'        => $stats,
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
     * Start the migration process.
     */
    #[McpTool(name: 'start_migration')]
    public function startMigration(
        #[Schema(type: 'integer', description: 'Migration job ID')]
        int $jobId
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $job = $this->jobModel->find($jobId);
        if (!$job) return ['status' => 'error', 'message' => 'Migration job not found'];
        if (!in_array($job['status'], ['mapped', 'analyzed'])) {
            return ['status' => 'error', 'message' => 'Job must be analyzed or mapped to start'];
        }

        $this->jobModel->updateStatus($jobId, 'importing');
        $items     = $this->itemModel->getByJob($jobId, 'pending');
        $processed = 0;
        $errors    = 0;

        foreach ($items as $item) {
            try {
                $destPath = WRITEPATH . 'uploads/migration/' . $item['filename'];
                if (!is_dir(dirname($destPath))) {
                    mkdir(dirname($destPath), 0755, true);
                }
                if (file_exists($item['source_path'])) {
                    copy($item['source_path'], $destPath);
                }
                $this->itemModel->update($item['id'], ['status' => 'imported']);
                $processed++;
            } catch (\Throwable $e) {
                $this->itemModel->update($item['id'], ['status' => 'failed', 'error_message' => $e->getMessage()]);
                $errors++;
            }
            $this->jobModel->update($jobId, ['processed_items' => $processed, 'error_items' => $errors]);
        }

        $this->jobModel->update($jobId, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);

        return ['job_id' => $jobId, 'status' => 'completed', 'processed' => $processed, 'errors' => $errors, 'total' => count($items)];
    }

    /**
     * Get current migration status.
     */
    #[McpTool(name: 'get_migration_status')]
    public function getMigrationStatus(
        #[Schema(type: 'integer', description: 'Job ID (omit for all active)')]
        ?int $jobId = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        if ($jobId) {
            $job = $this->jobModel->find($jobId);
            if (!$job) return ['status' => 'error', 'message' => 'Migration job not found'];
            return ['job' => $job, 'stats' => $this->itemModel->getStats($jobId)];
        }

        $jobs = $this->jobModel->getActive();
        return ['active_jobs' => $jobs, 'count' => count($jobs)];
    }

    /**
     * Map source fields to Arteri fields.
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
        if (!$job) return ['status' => 'error', 'message' => 'Migration job not found'];

        $validFields = ['noarsip','pencipta','unit_pengolah','tanggal','uraian','ket','kode','jumlah','nobox','lokasi','media'];

        $invalid = [];
        foreach ($fieldMapping as $source => $target) {
            if (!in_array($target, $validFields)) {
                $invalid[] = "{$source} -> {$target} (not valid)";
            }
        }
        if (!empty($invalid)) {
            return ['status' => 'error', 'message' => 'Invalid mappings', 'invalid' => $invalid, 'valid_fields' => $validFields];
        }

        $this->jobModel->update($jobId, ['field_mapping' => json_encode($fieldMapping), 'status' => 'mapped']);

        $items = $this->itemModel->getByJob($jobId);
        foreach ($items as $item) {
            $rawMetadata = json_decode($item['raw_metadata'] ?? '{}', true);
            $mapped = [];
            foreach ($fieldMapping as $sourceField => $arteriField) {
                if (isset($rawMetadata[$sourceField])) {
                    $mapped[$arteriField] = $rawMetadata[$sourceField];
                }
            }
            $this->itemModel->update($item['id'], ['mapped_metadata' => json_encode($mapped)]);
        }

        return ['job_id' => $jobId, 'status' => 'mapped', 'field_mapping' => $fieldMapping, 'items_updated' => count($items)];
    }

    private function analyzeFolder(string $path): array
    {
        if (!is_dir($path)) {
            return ['files' => [], 'total' => 0, 'error' => "Directory not found: {$path}"];
        }

        $extensions = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','tiff','bmp','txt','csv'];
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
                    'metadata' => ['folder' => $file->getPathinfo(\PATHINFO_DIRNAME), 'extension' => $ext],
                ];
            }
        }

        $totalSize = array_sum(array_column($files, 'size'));
        return [
            'files'   => $files,
            'total'   => count($files),
            'summary' => [
                'path'            => $path,
                'total_files'     => count($files),
                'total_size'      => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
            ],
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) { $size /= 1024; $i++; }
        return round($size, 2) . ' ' . $units[$i];
    }
}
