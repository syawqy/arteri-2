<?php

declare(strict_types=1);

namespace Tests\App\Mcp;

use App\Mcp\Services\AccessGuard;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * AccessGuard permission tests.
 *
 * Tests authenticate(), canAccessKlas(), canAccessModule(), filterArsip(),
 * canAccessArsip(), requireAuth(), requireModule(), and logAccess().
 *
 * @internal
 */
final class ChatAccessGuardTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private ?string $savedTestUsername = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Save MCP_TEST_USERNAME from all sources (env() checks $_ENV, $_SERVER, getenv)
        $this->savedTestUsername = $_ENV['MCP_TEST_USERNAME']
            ?? $_SERVER['MCP_TEST_USERNAME']
            ?? (getenv('MCP_TEST_USERNAME') ?: null);
    }

    protected function tearDown(): void
    {
        // Restore MCP_TEST_USERNAME to all sources
        $val = $this->savedTestUsername;
        if ($val !== null) {
            putenv("MCP_TEST_USERNAME={$val}");
            $_ENV['MCP_TEST_USERNAME']     = $val;
            $_SERVER['MCP_TEST_USERNAME']  = $val;
        } else {
            putenv('MCP_TEST_USERNAME');
            unset($_ENV['MCP_TEST_USERNAME'], $_SERVER['MCP_TEST_USERNAME']);
        }

        parent::tearDown();
    }

    /**
     * Override MCP_TEST_USERNAME in all env sources for testing.
     */
    private function setMcpTestUsername(?string $value): void
    {
        if ($value !== null) {
            putenv("MCP_TEST_USERNAME={$value}");
            $_ENV['MCP_TEST_USERNAME']    = $value;
            $_SERVER['MCP_TEST_USERNAME'] = $value;
        } else {
            putenv('MCP_TEST_USERNAME');
            unset($_ENV['MCP_TEST_USERNAME'], $_SERVER['MCP_TEST_USERNAME']);
        }
    }

    /**
     * Authenticate as admin via session.
     */
    public function testAuthenticateAdminViaSession(): void
    {
        $guard = new AccessGuard();
        $user  = $guard->authenticate(null, 'admin');

        $this->assertNotNull($user);
        $this->assertSame('admin', $user['username']);
        $this->assertSame('admin', $user['tipe']);
    }

    /**
     * Authenticate as regular user via session.
     */
    public function testAuthenticateUserViaSession(): void
    {
        $guard = new AccessGuard();
        $user  = $guard->authenticate(null, 'user');

        $this->assertNotNull($user);
        $this->assertSame('user', $user['username']);
        $this->assertSame('user', $user['tipe']);
    }

    /**
     * Unauthenticated (no session, no API key, no test env) returns null.
     */
    public function testAuthenticateUnauthenticatedReturnsNull(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();
        $user  = $guard->authenticate();

        $this->assertNull($user);
    }

    /**
     * Nonexistent username returns null.
     */
    public function testAuthenticateNonexistentUserReturnsNull(): void
    {
        $guard = new AccessGuard();
        $user  = $guard->authenticate(null, 'nonexistent_user_xyz');

        $this->assertNull($user);
    }

    /**
     * MCP_TEST_USERNAME env fallback authenticates as that user.
     */
    public function testAuthenticateMcpTestUsernameFallback(): void
    {
        $this->setMcpTestUsername('admin');

        $guard = new AccessGuard();
        $user  = $guard->authenticate();

        $this->assertNotNull($user);
        $this->assertSame('admin', $user['username']);
    }

    // ─────────────────────────────────────────────
    // canAccessKlas — admin bypass
    // ─────────────────────────────────────────────

    /**
     * @dataProvider klasCodeProvider
     */
    public function testAdminCanAccessAnyKlas(string $kode): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $this->assertTrue($guard->canAccessKlas($kode));
    }

    public static function klasCodeProvider(): array
    {
        return [
            'SDM.01'    => ['SDM.01'],
            'SDM.02'    => ['SDM.02'],
            'SDM.03'    => ['SDM.03'],
            'KEU.01'    => ['KEU.01'],
            'HKP.01'    => ['HKP.01'],
            'RND.01'    => ['RND.01'],
            'UMUM.01'   => ['UMUM.01'],
        ];
    }

    // ─────────────────────────────────────────────
    // canAccessKlas — user allowed (sdm, hkp)
    // ─────────────────────────────────────────────

    /**
     * @dataProvider userAllowedKlasProvider
     */
    public function testUserCanAccessAllowedKlas(string $kode): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->assertTrue($guard->canAccessKlas($kode));
    }

    public static function userAllowedKlasProvider(): array
    {
        return [
            'SDM.01'      => ['SDM.01'],
            'SDM.02'      => ['SDM.02'],
            'SDM.03'      => ['SDM.03'],
            'SDM.04'      => ['SDM.04'],
            'SDM.05'      => ['SDM.05'],
            'SDM.01.01'   => ['SDM.01.01'],
            'SDM.03.01'   => ['SDM.03.01'],
            'SDM.03.02'   => ['SDM.03.02'],
            'HKP.01'      => ['HKP.01'],
            'HKP.01.01'   => ['HKP.01.01'],
            'HKP.01.02'   => ['HKP.01.02'],
            'HKP.02'      => ['HKP.02'],
        ];
    }

    // ─────────────────────────────────────────────
    // canAccessKlas — user denied
    // ─────────────────────────────────────────────

    /**
     * @dataProvider userDeniedKlasProvider
     */
    public function testUserCannotAccessDeniedKlas(string $kode): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->assertFalse($guard->canAccessKlas($kode));
    }

    public static function userDeniedKlasProvider(): array
    {
        return [
            'KEU.01'  => ['KEU.01'],
            'KEU.02'  => ['KEU.02'],
            'KEU.03'  => ['KEU.03'],
            'RND.01'  => ['RND.01'],
            'UMUM.01' => ['UMUM.01'],
            'UMUM.02' => ['UMUM.02'],
        ];
    }

    // ─────────────────────────────────────────────
    // canAccessKlas — unauthenticated
    // ─────────────────────────────────────────────

    public function testUnauthenticatedCannotAccessAnyKlas(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();
        // No authenticate() call — user is null

        $this->assertFalse($guard->canAccessKlas('SDM.01'));
        $this->assertFalse($guard->canAccessKlas('KEU.01'));
        $this->assertFalse($guard->canAccessKlas('anything'));
    }

    // ─────────────────────────────────────────────
    // canAccessModule — admin bypass
    // ─────────────────────────────────────────────

    /**
     * @dataProvider allModulesProvider
     */
    public function testAdminCanAccessAnyModule(string $module): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $this->assertTrue($guard->canAccessModule($module));
    }

    public static function allModulesProvider(): array
    {
        return [
            'entridata'   => ['entridata'],
            'sirkulasi'   => ['sirkulasi'],
            'klasifikasi' => ['klasifikasi'],
            'pencipta'    => ['pencipta'],
            'pengolah'    => ['pengolah'],
            'lokasi'      => ['lokasi'],
            'media'       => ['media'],
            'user'        => ['user'],
            'import'      => ['import'],
        ];
    }

    // ─────────────────────────────────────────────
    // canAccessModule — user (sirkulasi=on only)
    // ─────────────────────────────────────────────

    /**
     * @dataProvider userModulesProvider
     */
    public function testUserModuleAccess(string $module, bool $expected): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->assertSame($expected, $guard->canAccessModule($module));
    }

    public static function userModulesProvider(): array
    {
        return [
            'sirkulasi=true'   => ['sirkulasi', true],
            'entridata=false'  => ['entridata', false],
            'klasifikasi=false'=> ['klasifikasi', false],
            'pencipta=false'   => ['pencipta', false],
            'pengolah=false'   => ['pengolah', false],
            'lokasi=false'     => ['lokasi', false],
            'media=false'      => ['media', false],
            'user=false'       => ['user', false],
            'import=false'     => ['import', false],
        ];
    }

    // ─────────────────────────────────────────────
    // canAccessModule — unauthenticated
    // ─────────────────────────────────────────────

    public function testUnauthenticatedCannotAccessAnyModule(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();

        $this->assertFalse($guard->canAccessModule('sirkulasi'));
        $this->assertFalse($guard->canAccessModule('entridata'));
        $this->assertFalse($guard->canAccessModule('anything'));
    }

    // ─────────────────────────────────────────────
    // filterArsip
    // ─────────────────────────────────────────────

    public function testFilterArsipAdminReturnsAll(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $records = [
            ['kode' => 'SDM.01', 'uraian' => 'Arsip SDM'],
            ['kode' => 'KEU.01', 'uraian' => 'Arsip KEU'],
            ['kode' => 'HKP.01', 'uraian' => 'Arsip HKP'],
        ];

        $filtered = $guard->filterArsip($records);
        $this->assertCount(3, $filtered);
    }

    public function testFilterArsipUserReturnsOnlyAccessible(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $records = [
            ['kode' => 'SDM.01', 'uraian' => 'Arsip SDM'],
            ['kode' => 'KEU.01', 'uraian' => 'Arsip KEU'],
            ['kode' => 'HKP.01', 'uraian' => 'Arsip HKP'],
        ];

        $filtered = $guard->filterArsip($records);
        $this->assertCount(2, $filtered);

        $kodes = array_column($filtered, 'kode');
        $this->assertContains('SDM.01', $kodes);
        $this->assertContains('HKP.01', $kodes);
        $this->assertNotContains('KEU.01', $kodes);
    }

    public function testFilterArsipEmptyArray(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $filtered = $guard->filterArsip([]);
        $this->assertEmpty($filtered);
    }

    public function testFilterArsipUserReIndexesKeys(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $records = [
            10 => ['kode' => 'SDM.01', 'uraian' => 'Allowed'],
            20 => ['kode' => 'KEU.01', 'uraian' => 'Denied'],
            30 => ['kode' => 'HKP.01', 'uraian' => 'Allowed'],
        ];

        $filtered = $guard->filterArsip($records);
        $this->assertCount(2, $filtered);
        // array_values() re-indexes to 0, 1
        $this->assertArrayHasKey(0, $filtered);
        $this->assertArrayHasKey(1, $filtered);
        $this->assertArrayNotHasKey(10, $filtered);
    }

    // ─────────────────────────────────────────────
    // canAccessArsip
    // ─────────────────────────────────────────────

    public function testCanAccessArsipAdminYes(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $this->assertTrue($guard->canAccessArsip(['kode' => 'KEU.01']));
    }

    public function testCanAccessArsipUserSdmYes(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->assertTrue($guard->canAccessArsip(['kode' => 'SDM.01']));
    }

    public function testCanAccessArsipUserKeuNo(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->assertFalse($guard->canAccessArsip(['kode' => 'KEU.01']));
    }

    // ─────────────────────────────────────────────
    // requireAuth
    // ─────────────────────────────────────────────

    public function testRequireAuthThrowsForUnauthenticated(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication required');
        $guard->requireAuth();
    }

    public function testRequireAuthPassesForAdmin(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        // Should not throw
        $guard->requireAuth();
        $this->assertNotNull($guard->getUser());
    }

    public function testRequireAuthPassesForUser(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        // Should not throw
        $guard->requireAuth();
        $this->assertNotNull($guard->getUser());
    }

    // ─────────────────────────────────────────────
    // requireModule
    // ─────────────────────────────────────────────

    public function testRequireModuleAdminPassesAll(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        // Should not throw for any module
        $guard->requireModule('entridata');
        $guard->requireModule('sirkulasi');
        $guard->requireModule('klasifikasi');
        $this->assertNotNull($guard->getUser());
    }

    public function testRequireModuleUserSirkulasiPasses(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        // Should not throw — user has sirkulasi=on
        $guard->requireModule('sirkulasi');
        $this->assertNotNull($guard->getUser());
    }

    public function testRequireModuleUserEntriDataThrows(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied to module: entridata');
        $guard->requireModule('entridata');
    }

    // ─────────────────────────────────────────────
    // logAccess
    // ─────────────────────────────────────────────

    public function testLogAccessCreatesEntryForAdmin(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $before = $this->db->table('document_access_log')->countAllResults();
        $guard->logAccess(1, 'view', ['test' => 'admin access']);
        $after = $this->db->table('document_access_log')->countAllResults();

        $this->assertSame($before + 1, $after);

        $row = $this->db->table('document_access_log')
            ->where('accessed_by', 'admin')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row['arsip_id']);
        $this->assertSame('admin', $row['accessed_by']);
        $this->assertSame('view', $row['access_type']);
    }

    public function testLogAccessCreatesEntryForUser(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $guard->logAccess(5, 'download', ['filename' => 'test.pdf']);

        $row = $this->db->table('document_access_log')
            ->where('accessed_by', 'user')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame(5, (int) $row['arsip_id']);
        $this->assertSame('user', $row['accessed_by']);
        $this->assertSame('download', $row['access_type']);
        $this->assertNotNull($row['details']);
    }

    public function testLogAccessNoOpWhenUnauthenticated(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();

        $before = $this->db->table('document_access_log')->countAllResults();
        // Should not throw and should not insert
        $guard->logAccess(1, 'view');
        $after = $this->db->table('document_access_log')->countAllResults();

        $this->assertSame($before, $after);
    }

    // ─────────────────────────────────────────────
    // getUser / getUsername
    // ─────────────────────────────────────────────

    public function testGetUserReturnsNullWhenUnauthenticated(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();

        $this->assertNull($guard->getUser());
    }

    public function testGetUsernameReturnsUnknownWhenUnauthenticated(): void
    {
        $this->setMcpTestUsername(null);

        $guard = new AccessGuard();

        $this->assertSame('unknown', $guard->getUsername());
    }

    public function testGetUsernameReturnsAuthenticatedUsername(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'admin');

        $this->assertSame('admin', $guard->getUsername());
    }

    // ─────────────────────────────────────────────
    // Case insensitive klas matching
    // ─────────────────────────────────────────────

    public function testCanAccessKlasCaseInsensitive(): void
    {
        $guard = new AccessGuard();
        $guard->authenticate(null, 'user');

        $this->assertTrue($guard->canAccessKlas('sdm.01'));
        $this->assertTrue($guard->canAccessKlas('Sdm.02'));
        $this->assertTrue($guard->canAccessKlas('hkp.01'));
    }
}
