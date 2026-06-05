<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature test untuk laporan: export Excel & export PDF (print-to-PDF).
 *
 * @internal
 */
final class ReportControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private array $masterIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();

        $this->masterIds = [
            'kode'     => $db->table('master_kode')->get(1)->getRowArray()['id'],
            'pencipta' => $db->table('master_pencipta')->get(1)->getRowArray()['id'],
            'pengolah' => $db->table('master_pengolah')->get(1)->getRowArray()['id'],
            'lokasi'   => $db->table('master_lokasi')->get(1)->getRowArray()['id'],
            'media'    => $db->table('master_media')->get(1)->getRowArray()['id'],
        ];
    }

    private function getAdminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => ['entridata' => 'on'],
            'menu_master' => true,
        ];
    }

    private function insertArsipInDb(array $overrides = []): int
    {
        $db = \Config\Database::connect();
        $db->table('data_arsip')->insert(array_merge([
            'noarsip'       => 'RPT-001',
            'pencipta'      => $this->masterIds['pencipta'],
            'unit_pengolah' => $this->masterIds['pengolah'],
            'tanggal'       => '2025-06-01',
            'uraian'        => 'Report test arsip',
            'ket'           => 'asli',
            'kode'          => $this->masterIds['kode'],
            'jumlah'        => 1,
            'nobox'         => 'R-01',
            'lokasi'        => $this->masterIds['lokasi'],
            'media'         => $this->masterIds['media'],
            'username'      => 'admin',
        ], $overrides));

        return (int) $db->insertID();
    }

    private function skipIfNotMysql(): void
    {
        $db = \Config\Database::connect();
        if ($db->DBDriver === 'SQLite3') {
            $this->markTestSkipped(
                'Report uses MySQL functions via ArsipModel/SirkulasiModel. Run against a MySQL test DB.'
            );
        }
    }

    // ── Auth gate ──

    public function testIndexRequiresAuth(): void
    {
        $this->get('report')->assertRedirectTo('/login');
    }

    public function testArsipPrintRequiresAuth(): void
    {
        $this->get('report/arsip/print')->assertRedirectTo('/login');
    }

    public function testSirkulasiPrintRequiresAuth(): void
    {
        $this->get('report/sirkulasi/print')->assertRedirectTo('/login');
    }

    // ── Index page ──

    public function testIndexReturns200(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('report')->assertStatus(200);
    }

    // ── Export PDF (print-to-PDF) ──

    public function testArsipPrintReturns200AndHtml(): void
    {
        $this->skipIfNotMysql();
        $this->insertArsipInDb(['noarsip' => 'RPT-PRINT-001']);

        $this->withSession($this->getAdminSession());
        $response = $this->get('report/arsip/print');
        $response->assertStatus(200);

        $body = (string) $response->getBody();
        $this->assertStringContainsString('LAPORAN ARSIP', $body);
        $this->assertStringContainsString('window.print()', $body);
        $this->assertStringContainsString('RPT-PRINT-001', $body);
    }

    public function testSirkulasiPrintReturns200AndHtml(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        $response = $this->get('report/sirkulasi/print');
        $response->assertStatus(200);

        $body = (string) $response->getBody();
        $this->assertStringContainsString('LAPORAN SIRKULASI', $body);
        $this->assertStringContainsString('window.print()', $body);
    }

    public function testArsipPrintEmptyShowsPlaceholder(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        $body = (string) $this->get('report/arsip/print?katakunci=NO_MATCH_ZZZ')->getBody();
        $this->assertStringContainsString('Tidak ada data', $body);
    }

    // ── Export Excel ──

    public function testArsipExportExcelReturnsSpreadsheet(): void
    {
        $this->skipIfNotMysql();
        $this->insertArsipInDb(['noarsip' => 'RPT-XLS-001']);

        $this->withSession($this->getAdminSession());
        ob_start();
        $response = $this->get('report/arsip/export-excel');
        ob_end_clean();
        $response->assertStatus(200);

        $contentType = $response->response()->getHeaderLine('Content-Type');
        $this->assertStringContainsString('spreadsheet', $contentType);
    }

    public function testSirkulasiExportExcelReturnsSpreadsheet(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        ob_start();
        $response = $this->get('report/sirkulasi/export-excel');
        ob_end_clean();
        $response->assertStatus(200);

        $contentType = $response->response()->getHeaderLine('Content-Type');
        $this->assertStringContainsString('spreadsheet', $contentType);
    }
}
