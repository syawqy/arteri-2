<?php

namespace Tests\App\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AclHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('acl');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        session()->remove('tipe');
        session()->remove('akses_modul');
        session()->remove('akses_klas');
    }

    public function testIsAdminReturnsTrueForAdmin(): void
    {
        session()->set('tipe', 'admin');
        $this->assertTrue(isAdmin());
    }

    public function testIsAdminReturnsFalseForNonAdmin(): void
    {
        session()->set('tipe', 'user');
        $this->assertFalse(isAdmin());
    }

    public function testHasModuleAccessAdminAlwaysTrue(): void
    {
        session()->set('tipe', 'admin');
        $this->assertTrue(hasModuleAccess('anything'));
    }

    public function testHasModuleAccessUserWithEnabledModule(): void
    {
        session()->set('tipe', 'user');
        session()->set('akses_modul', ['entridata' => 'on']);
        $this->assertTrue(hasModuleAccess('entridata'));
    }

    public function testHasModuleAccessUserWithDisabledModule(): void
    {
        session()->set('tipe', 'user');
        session()->set('akses_modul', ['entridata' => 'off']);
        $this->assertFalse(hasModuleAccess('entridata'));
    }

    public function testHasModuleAccessUserWithoutModule(): void
    {
        session()->set('tipe', 'user');
        session()->set('akses_modul', []);
        $this->assertFalse(hasModuleAccess('entridata'));
    }

    public function testHasClassificationAccessAdminAlwaysTrue(): void
    {
        session()->set('tipe', 'admin');
        $this->assertTrue(hasClassificationAccess('SDM.01'));
    }

    public function testHasClassificationAccessEmptyMeansAll(): void
    {
        session()->set('tipe', 'user');
        session()->set('akses_klas', '');
        $this->assertTrue(hasClassificationAccess('SDM.01'));
    }

    public function testHasClassificationAccessWithMatchingCode(): void
    {
        session()->set('tipe', 'user');
        session()->set('akses_klas', 'SDM,HKP');
        $this->assertTrue(hasClassificationAccess('SDM.01'));
    }

    public function testHasClassificationAccessWithoutMatchingCode(): void
    {
        session()->set('tipe', 'user');
        session()->set('akses_klas', 'HKP,UMUM');
        $this->assertFalse(hasClassificationAccess('SDM.01'));
    }
}
