<?php

namespace Tests\App\Commands;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test untuk command `backup:database`.
 *
 * @internal
 */
final class DatabaseBackupTest extends CIUnitTestCase
{
    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = WRITEPATH . 'backups';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cleanup test backups
        if (is_dir($this->backupDir)) {
            $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }

    public function testBackupCommandCreatesCompressedFile(): void
    {
        // Skip jika mysqldump atau gzip tidak tersedia
        exec('which mysqldump 2>&1', $mysqldumpCheck, $mysqldumpCode);
        exec('which gzip 2>&1', $gzipCheck, $gzipCode);

        if ($mysqldumpCode !== 0 || $gzipCode !== 0) {
            $this->markTestSkipped('mysqldump or gzip not available in PATH.');
        }

        // Run backup command
        $result = command('backup:database --keep=3');

        // Assert command succeeded (return code 0)
        $this->assertSame(0, $result);

        // Assert backup file created
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz');
        $this->assertNotEmpty($files, 'Backup file tidak terbuat.');

        // Assert file not empty
        $latestBackup = end($files);
        $this->assertGreaterThan(0, filesize($latestBackup), 'Backup file kosong.');
    }

    public function testBackupRotationKeepsOnlySpecifiedCount(): void
    {
        exec('which mysqldump 2>&1', $mysqldumpCheck, $mysqldumpCode);
        exec('which gzip 2>&1', $gzipCheck, $gzipCode);

        if ($mysqldumpCode !== 0 || $gzipCode !== 0) {
            $this->markTestSkipped('mysqldump or gzip not available in PATH.');
        }

        // Create 5 dummy backup files with different timestamps
        for ($i = 0; $i < 5; $i++) {
            $filename = sprintf(
                'backup-%s.sql.gz',
                date('Ymd-His', strtotime("-{$i} days"))
            );
            $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

            if (! is_dir($this->backupDir)) {
                mkdir($this->backupDir, 0755, true);
            }

            file_put_contents($path, 'dummy backup ' . $i);

            // Set mtime untuk sorting
            touch($path, strtotime("-{$i} days"));
        }

        // Run with keep=3
        command('backup:database --keep=3');

        // Assert only 4 files remain (3 old + 1 new from command)
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz');
        $this->assertCount(4, $files, 'Rotation tidak berfungsi. Expected 4 files (3 kept + 1 new).');
    }
}
