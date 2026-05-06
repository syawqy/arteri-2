<?php

namespace Tests\App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\TestTraits\CsrfTestTrait;

/**
 * @internal
 */
final class ArsipControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CsrfTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private array $masterIds = [];

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupCsrf();

        $db = \Config\Database::connect();

        $this->masterIds = [
            'kode'     => $db->table('master_kode')->get(1)->getRowArray()['id'],
            'pencipta' => $db->table('master_pencipta')->get(1)->getRowArray()['id'],
            'pengolah' => $db->table('master_pengolah')->get(1)->getRowArray()['id'],
            'lokasi'   => $db->table('master_lokasi')->get(1)->getRowArray()['id'],
            'media'    => $db->table('master_media')->get(1)->getRowArray()['id'],
        ];
    }

    private function validArsipData(): array
    {
        return [
            'noarsip'      => 'CRT-001',
            'tanggal'      => '2025-06-01',
            'pencipta'     => (string) $this->masterIds['pencipta'],
            'unitpengolah' => (string) $this->masterIds['pengolah'],
            'kode'         => (string) $this->masterIds['kode'],
            'uraian'       => 'Test create arsip',
            'lokasi'       => (string) $this->masterIds['lokasi'],
            'media'        => (string) $this->masterIds['media'],
            'ket'          => 'asli',
            'jumlah'       => '1',
            'nobox'        => 'B-01',
        ];
    }

    private function insertArsipInDb(array $overrides = []): int
    {
        $db = \Config\Database::connect();
        $db->table('data_arsip')->insert(array_merge([
            'noarsip'       => 'DB-001',
            'pencipta'      => $this->masterIds['pencipta'],
            'unit_pengolah' => $this->masterIds['pengolah'],
            'tanggal'       => '2025-06-01',
            'uraian'        => 'Direct DB insert',
            'ket'           => 'asli',
            'kode'          => $this->masterIds['kode'],
            'jumlah'        => 1,
            'nobox'         => 'B-01',
            'lokasi'        => $this->masterIds['lokasi'],
            'media'         => $this->masterIds['media'],
            'username'      => 'admin',
        ], $overrides));

        return (int) $db->insertID();
    }

    // ── Auth gate ──

    public function testCreateFormHasCsrfHiddenField(): void
    {
        $this->withSession($this->getAdminSession());
        $body = (string) $this->get('arsip/new')->getBody();
        $this->assertStringContainsString('name="csrf_test_name"', $body);
    }

    public function testGetArsipNewRequiresAuth(): void
    {
        $this->get('arsip/new')->assertRedirectTo('/login');
    }

    public function testPostArsipCreateRequiresAuth(): void
    {
        $this->csrfPost('arsip', $this->validArsipData())
            ->assertRedirectTo('/login');
    }

    public function testGetArsipEditRequiresAuth(): void
    {
        $this->get('arsip/edit/1')->assertRedirectTo('/login');
    }

    public function testPostArsipUpdateRequiresAuth(): void
    {
        $this->csrfPost('arsip/update/1', $this->validArsipData())
            ->assertRedirectTo('/login');
    }

    // ── Create ──

    public function testGetNewReturns200WhenLoggedIn(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('arsip/new')->assertStatus(200);
    }

    public function testCreateWithValidDataRedirects(): void
    {
        $this->withSession($this->getAdminSession());

        $response = $this->csrfPost('arsip', $this->validArsipData());
        $response->assertRedirect();
    }

    public function testCreateWithInvalidDataRedirectsBack(): void
    {
        $this->withSession($this->getAdminSession());

        $response = $this->csrfPost('arsip', [
            'noarsip' => '',
            'tanggal' => 'invalid-date',
        ]);
        $response->assertRedirect();
    }

    // ── Edit ──

    public function testGetEditReturns200ForExisting(): void
    {
        $id = $this->insertArsipInDb(['noarsip' => 'EDIT-001']);

        $this->withSession($this->getAdminSession());
        $this->get('arsip/edit/' . $id)->assertStatus(200);
    }

    public function testGetEditRedirectsForNonexistent(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('arsip/edit/99999')->assertRedirectTo('/');
    }

    public function testUpdateWithValidDataRedirects(): void
    {
        $id = $this->insertArsipInDb(['noarsip' => 'UPD-001']);

        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('arsip/update/' . $id, $this->validArsipData());
        $response->assertRedirect();
    }

    public function testUpdateForNonexistentRedirects(): void
    {
        $this->withSession($this->getAdminSession());
        $this->csrfPost('arsip/update/99999', $this->validArsipData())
            ->assertRedirectTo('/');
    }

    // ── Delete ──

    public function testDeleteReturnsJsonSuccess(): void
    {
        $id = $this->insertArsipInDb(['noarsip' => 'DEL-001']);

        $this->withSession($this->getAdminSession());
        $response = $this->post('arsip/delete/' . $id);
        $response->assertOK();
        $body = $response->getBody();
        $json = $this->extractJson($body);
        $this->assertNotEmpty($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('success', $decoded['status'] ?? null);
    }

    private function extractJson(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }
        if (preg_match('#\{.*\}#s', $html, $matches)) {
            return $matches[0];
        }
        return $html;
    }

    // ── FK Validation ──

    public function testCreateFailsWhenKodeNotFound(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('arsip', [
            'noarsip'      => 'FK-001',
            'tanggal'      => '2025-01-01',
            'pencipta'     => '99999',
            'unitpengolah' => (string) $this->masterIds['pengolah'],
            'kode'         => (string) $this->masterIds['kode'],
            'uraian'       => 'Test FK',
            'lokasi'       => (string) $this->masterIds['lokasi'],
            'media'        => (string) $this->masterIds['media'],
            'ket'          => 'asli',
            'jumlah'       => '1',
        ]);
        $response->assertRedirect();
    }

    public function testDeleteWithInvalidIdReturnsError(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('arsip/delete/invalid');
        $this->assertStringContainsString('error', (string) $response->getBody());
    }

    // ── File upload ──

    public function testCreateFormHasFileInput(): void
    {
        $this->withSession($this->getAdminSession());
        $body = (string) $this->get('arsip/new')->getBody();
        $this->assertStringContainsString('type="file"', $body);
        $this->assertStringContainsString('name="file"', $body);
    }

    public function testCreateWithoutFileDoesNotError(): void
    {
        $this->withSession($this->getAdminSession());

        $data = $this->validArsipData();
        $data['noarsip'] = 'CRT-NOFILE';

        $response = $this->csrfPost('arsip', $data);
        $response->assertRedirect();

        $db = \Config\Database::connect();
        $row = $db->table('data_arsip')->where('noarsip', 'CRT-NOFILE')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertEmpty($row['file']);
    }

    // ── Edit form file input ──

    public function testEditFormHasFileInput(): void
    {
        $id = $this->insertArsipInDb(['noarsip' => 'EDIT-FILE']);

        $this->withSession($this->getAdminSession());
        $body = (string) $this->get('arsip/edit/' . $id)->getBody();
        $this->assertStringContainsString('type="file"', $body);
    }

    // ── Delete file (via direct DB insert to simulate uploaded file) ──

    public function testDeleteFileReturnsJsonSuccess(): void
    {
        $id = $this->insertArsipInDb([
            'noarsip' => 'DELFILE-001',
            'file'    => 'test_uploaded_file.pdf',
        ]);

        // Create dummy file on disk
        $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR;
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        file_put_contents($uploadDir . 'test_uploaded_file.pdf', '%PDF-1.4');

        $this->withSession($this->getAdminSession());
        $response = $this->post('arsip/delfile/' . $id);
        $response->assertOK();
        $json = $this->extractJson((string) $response->getBody());
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('success', $decoded['status'] ?? null);

        // Verify DB field cleared
        $db = \Config\Database::connect();
        $row = $db->table('data_arsip')->where('id', $id)->get()->getRowArray();
        $this->assertNull($row['file']);

        // Verify file deleted from disk
        $this->assertFileDoesNotExist($uploadDir . 'test_uploaded_file.pdf');
    }

    public function testDeleteFileForNonexistentArsipReturnsError(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->post('arsip/delfile/99999');
        $response->assertOK();
        $json = $this->extractJson((string) $response->getBody());
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('error', $decoded['status'] ?? null);
    }

    public function testDeleteFileWithoutFileOnDiskDoesNotError(): void
    {
        $id = $this->insertArsipInDb([
            'noarsip' => 'DELFILE-NOFILE',
            'file'    => 'nonexistent_file.pdf',
        ]);

        $this->withSession($this->getAdminSession());
        $response = $this->post('arsip/delfile/' . $id);
        $response->assertOK();
        $json = $this->extractJson((string) $response->getBody());
        $decoded = json_decode($json, true);
        $this->assertSame('success', $decoded['status'] ?? null);
    }

    // ── Helpers ──

}


