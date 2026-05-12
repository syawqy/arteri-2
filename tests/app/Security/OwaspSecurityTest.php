<?php

namespace Tests\App\Security;

use App\TestTraits\CsrfTestTrait;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class OwaspSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CsrfTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCsrf();
    }

    public function testSeededPasswordsAreBcryptHashes(): void
    {
        $users = \Config\Database::connect()->table('master_user')->get()->getResultArray();

        $this->assertNotEmpty($users);
        foreach ($users as $user) {
            $this->assertNotSame($user['username'], $user['password']);
            $this->assertTrue(password_get_info($user['password'])['algoName'] === 'bcrypt');
            $this->assertTrue(password_verify($user['username'], $user['password']));
        }
    }

    public function testExternalLoginRedirectTargetIsRejected(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'admin',
            'previous' => 'https://evil.example/phishing',
        ])->assertRedirectTo('/');
    }

    public function testSecurityCookieAndCsrfDefaultsAreEnabledForAuditBaseline(): void
    {
        $cookie = config(\Config\Cookie::class);
        $security = config(\Config\Security::class);

        $this->assertTrue($cookie->httponly);
        $this->assertSame('Lax', $cookie->samesite);
        $this->assertTrue($security->tokenRandomize);
        $this->assertSame('csrf_test_name', $security->tokenName);
    }

    public function testArchiveDetailEscapesStoredXssPayload(): void
    {
        $db = \Config\Database::connect();
        $master = $this->masterIds($db);
        $payload = '<script id="owasp-server-xss">alert(1)</script>';

        $db->table('data_arsip')->insert([
            'noarsip'       => 'OWASP-XSS-SERVER',
            'pencipta'      => $master['pencipta'],
            'unit_pengolah' => $master['pengolah'],
            'tanggal'       => '2026-01-01',
            'uraian'        => $payload,
            'ket'           => 'asli',
            'kode'          => $master['kode'],
            'jumlah'        => 1,
            'nobox'         => 'OWASP',
            'lokasi'        => $master['lokasi'],
            'media'         => $master['media'],
            'username'      => 'admin',
        ]);

        $this->withSession($this->adminSession());
        $body = (string) $this->get('view/' . $db->insertID())->getBody();

        $this->assertStringNotContainsString($payload, $body);
        $this->assertStringContainsString('&lt;script id="owasp-server-xss"&gt;', $body);
    }

    public function testFileRouteRejectsPathTraversalPayload(): void
    {
        $this->withSession($this->adminSession());

        try {
            $response = $this->get('file/' . rawurlencode('../.env'));
            $this->assertNotSame(200, $response->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException) {
            $this->assertTrue(true);
        }
    }

    public function testAuditLogsDoNotPersistSubmittedPasswords(): void
    {
        $this->withSession($this->adminSession());
        $this->csrfPost('user', [
            'username'      => 'owasp_audit_user',
            'password'      => 'Secret123',
            'conf_password' => 'Secret123',
            'tipe'          => 'user',
        ]);

        $logs = \Config\Database::connect()->table('system_log')->get()->getResultArray();
        $encodedLogs = json_encode($logs);

        $this->assertIsString($encodedLogs);
        $this->assertStringNotContainsString('Secret123', $encodedLogs);
    }

    public function testExportUsesExplicitStringCellsForFormulaInjectionDefense(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/Home.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('setCellValueExplicit', $source);
        $this->assertStringContainsString('DataType::TYPE_STRING', $source);
    }

    public function testNoUserControlledOutboundHttpClientSinksArePresent(): void
    {
        $patterns = [
            'curl_init(',
            'fsockopen(',
            'stream_socket_client(',
            'file_get_contents($this->request',
            'file_get_contents($request',
        ];
        $code = $this->readAppPhpFiles();

        foreach ($patterns as $pattern) {
            $this->assertStringNotContainsString($pattern, $code, 'Unexpected outbound sink: ' . $pattern);
        }
    }

    private function adminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => [
                'entridata'   => 'on',
                'sirkulasi'   => 'on',
                'klasifikasi' => 'on',
                'pencipta'    => 'on',
                'pengolah'    => 'on',
                'lokasi'      => 'on',
                'media'       => 'on',
                'user'        => 'on',
                'import'      => 'on',
            ],
            'menu_master' => true,
        ];
    }

    private function masterIds(\CodeIgniter\Database\BaseConnection $db): array
    {
        return [
            'kode'     => $db->table('master_kode')->get(1)->getRowArray()['id'],
            'pencipta' => $db->table('master_pencipta')->get(1)->getRowArray()['id'],
            'pengolah' => $db->table('master_pengolah')->get(1)->getRowArray()['id'],
            'lokasi'   => $db->table('master_lokasi')->get(1)->getRowArray()['id'],
            'media'    => $db->table('master_media')->get(1)->getRowArray()['id'],
        ];
    }

    private function readAppPhpFiles(): string
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH));
        $code = '';

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $code .= "\n/* " . $file->getPathname() . " */\n";
            $code .= file_get_contents($file->getPathname());
        }

        return $code;
    }
}
