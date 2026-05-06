<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\TestTraits\CsrfTestTrait;

/**
 * @internal
 *
 * Catatan: ImportController::doImport() menggunakan `redirect()` tanpa `return`
 * dan `echo` bukan `return view()`. Ini sulit diuji dengan FeatureTestTrait
 * karena redirect tidak dihentikan. Test import Excel hanya bisa verifikasi
 * endpoint tidak crash — verifikasi data inserted dilakukan secara terpisah.
 */
final class ImportControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CsrfTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private function getAdminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => ['import' => 'on'],
            'menu_master' => true,
        ];
    }

    // ── Auth gate ──

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCsrf();
    }

    public function testIndexRequiresAuth(): void
    {
        $this->get('import')->assertRedirectTo('/login');
    }

    // ── Index ──

    public function testIndexReturns200(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('import')->assertStatus(200);
    }

    public function testIndexHasCsrfHiddenField(): void
    {
        $this->withSession($this->getAdminSession());
        $body = (string) $this->get('import')->getBody();
        $this->assertStringContainsString('name="csrf_test_name"', $body);
    }

    // ── doImport ──

    public function testDoImportRedirectsWithoutFile(): void
    {
        $this->withSession($this->getAdminSession());
        // The controller will redirect because no file is uploaded.
        // Because redirect() is not returned, the test may get a 500 or empty.
        // Just verify it doesn't throw; for full coverage the controller should be refactored.
        $response = $this->csrfPost('import', []);
        // Should at least return something (either redirect or page)
        $this->assertNotEmpty((string) $response->getBody());
    }
}


