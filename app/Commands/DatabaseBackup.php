<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Backup database menggunakan mysqldump dan compress dengan gzip.
 * Simpan ke writable/backups/ dengan format: backup-YYYYMMDD-HHMMSS.sql.gz
 *
 * Usage:
 *   php spark backup:database
 *   php spark backup:database --keep=7
 */
class DatabaseBackup extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'backup:database';
    protected $description = 'Backup database ke writable/backups/ (compressed .sql.gz)';
    protected $usage       = 'backup:database [--keep N] [--offsite PATH]';
    protected $options     = [
        '--keep'    => 'Jumlah backup yang disimpan (rotation). Default: 7',
        '--offsite' => 'Copy backup ke lokasi offsite (network drive, mounted storage, dll)',
    ];

    private string $backupDir;

    public function run(array $params)
    {
        $this->backupDir = WRITEPATH . 'backups';

        // Ensure backup directory exists
        if (! is_dir($this->backupDir)) {
            if (! mkdir($this->backupDir, 0755, true)) {
                CLI::error("Gagal membuat direktori backup: {$this->backupDir}");
                return 1;
            }
        }

        CLI::write('=== Database Backup ===', 'yellow');

        // Get database config
        $db = Database::connect();
        $config = $db->getConnectInfo();

        $host     = $config['hostname'] ?? 'localhost';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $database = $config['database'] ?? '';
        $port     = $config['port'] ?? 3306;

        if (empty($database)) {
            CLI::error('Database name tidak ditemukan di config.');
            return 1;
        }

        // Generate filename
        $timestamp = date('Ymd-His');
        $filename  = "backup-{$timestamp}.sql";
        $gzFilename = "{$filename}.gz";
        $tempPath  = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        $finalPath = $this->backupDir . DIRECTORY_SEPARATOR . $gzFilename;

        CLI::write("Database: {$database}@{$host}", 'white');
        CLI::write("Target: {$gzFilename}", 'white');

        // Build mysqldump command
        $mysqldump = $this->findMysqldump();
        if ($mysqldump === null) {
            CLI::error('mysqldump tidak ditemukan di PATH. Install MySQL client tools.');
            return 1;
        }

        $passwordArg = ! empty($password) ? "--password=" . escapeshellarg($password) : '';
        $command = sprintf(
            '%s --host=%s --port=%d --user=%s %s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellcmd($mysqldump),
            escapeshellarg($host),
            (int) $port,
            escapeshellarg($username),
            $passwordArg,
            escapeshellarg($database),
            escapeshellarg($tempPath)
        );

        CLI::write('Dumping database...', 'cyan');
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            CLI::error('mysqldump gagal: ' . implode("\n", $output));
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            return 1;
        }

        // Verify dump file exists and not empty
        if (! is_file($tempPath) || filesize($tempPath) === 0) {
            CLI::error('Dump file kosong atau gagal dibuat.');
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            return 1;
        }

        $dumpSize = filesize($tempPath);
        CLI::write("Dump selesai: " . $this->formatBytes($dumpSize), 'green');

        // Compress with gzip
        CLI::write('Compressing...', 'cyan');
        $gzCommand = sprintf(
            'gzip -c %s > %s 2>&1',
            escapeshellarg($tempPath),
            escapeshellarg($finalPath)
        );

        exec($gzCommand, $gzOutput, $gzReturnCode);

        // Cleanup temp file
        @unlink($tempPath);

        if ($gzReturnCode !== 0 || ! is_file($finalPath)) {
            CLI::error('Kompresi gagal: ' . implode("\n", $gzOutput));
            return 1;
        }

        $gzSize = filesize($finalPath);
        $ratio  = $dumpSize > 0 ? round((1 - $gzSize / $dumpSize) * 100, 1) : 0;
        CLI::write("Compressed: " . $this->formatBytes($gzSize) . " ({$ratio}% reduced)", 'green');

        // Verify backup integrity
        CLI::write('Verifying backup...', 'cyan');
        if (! $this->verifyBackup($finalPath)) {
            CLI::error('Backup verification gagal! File mungkin corrupt.');
            return 1;
        }
        CLI::write('Verification passed.', 'green');

        // Copy to offsite location if specified
        if (isset($params['offsite']) && ! empty($params['offsite'])) {
            $offsitePath = rtrim($params['offsite'], DIRECTORY_SEPARATOR);
            if ($this->copyToOffsite($finalPath, $offsitePath, $gzFilename)) {
                CLI::write("Offsite copy: {$offsitePath}/{$gzFilename}", 'green');
            } else {
                CLI::error("Offsite copy gagal ke: {$offsitePath}");
                // Don't fail the whole backup if offsite fails
            }
        }

        // Rotation: keep last N backups
        $keepCount = isset($params['keep']) ? max(1, (int) $params['keep']) : 7;
        $this->rotateBackups($keepCount);

        CLI::write("Backup selesai: {$gzFilename}", 'green');
        CLI::write("Lokasi: {$finalPath}", 'white');

        // Log to system_log for monitoring
        $this->logBackup($gzFilename, $gzSize, isset($params['offsite']) ? $params['offsite'] : null);

        return 0;
    }

    /**
     * Find mysqldump executable in PATH.
     */
    private function findMysqldump(): ?string
    {
        // Try common locations
        $candidates = ['mysqldump', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // Try which/where
        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
        exec("{$which} mysqldump 2>&1", $output, $returnCode);

        if ($returnCode === 0 && ! empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Delete old backups, keep last N files.
     */
    private function rotateBackups(int $keepCount): void
    {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz');
        if ($files === false || count($files) <= $keepCount) {
            return;
        }

        // Sort by modification time descending (newest first)
        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        $toDelete = array_slice($files, $keepCount);
        $deleted  = 0;

        foreach ($toDelete as $file) {
            if (@unlink($file)) {
                $deleted++;
                CLI::write("Rotasi: hapus " . basename($file), 'yellow');
            }
        }

        if ($deleted > 0) {
            CLI::write("Rotasi selesai: {$deleted} backup lama dihapus.", 'yellow');
        }
    }

    /**
     * Log backup to system_log for monitoring.
     */
    private function logBackup(string $filename, int $filesize, ?string $offsitePath): void
    {
        $systemLog = new \App\Models\SystemLogModel();

        $detail = [
            'filename'     => $filename,
            'filesize'     => $filesize,
            'human_size'   => $this->formatBytes($filesize),
            'offsite'      => $offsitePath !== null ? $offsitePath : 'none',
        ];

        $systemLog->insert([
            'kode_transaksi'     => 'BACKUP',
            'username_transaksi' => 'system',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => 'DATABASE_BACKUP',
            'tabel'              => 'database',
            'record_id'          => null,
            'detail'             => json_encode($detail),
            'ip_address'         => null,
        ], false);
    }

    /**
     * Copy backup to offsite location.
     */
    private function copyToOffsite(string $sourcePath, string $offsiteDir, string $filename): bool
    {
        if (! is_dir($offsiteDir)) {
            CLI::write("Offsite directory tidak ditemukan: {$offsiteDir}", 'yellow');
            return false;
        }

        if (! is_writable($offsiteDir)) {
            CLI::write("Offsite directory tidak writable: {$offsiteDir}", 'yellow');
            return false;
        }

        $destPath = $offsiteDir . DIRECTORY_SEPARATOR . $filename;

        if (! copy($sourcePath, $destPath)) {
            return false;
        }

        // Verify offsite copy
        if (! is_file($destPath) || filesize($destPath) !== filesize($sourcePath)) {
            @unlink($destPath);
            return false;
        }

        return true;
    }

    /**
     * Verify backup file integrity by testing gunzip.
     */
    private function verifyBackup(string $gzPath): bool
    {
        if (! is_file($gzPath) || filesize($gzPath) === 0) {
            return false;
        }

        // Test gunzip can read the file without errors
        $testCommand = sprintf('gzip -t %s 2>&1', escapeshellarg($gzPath));
        exec($testCommand, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Format bytes to human-readable size.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
