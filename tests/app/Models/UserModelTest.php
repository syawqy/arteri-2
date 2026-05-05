<?php

namespace Tests\App\Models;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private UserModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserModel();
    }

    // ── Existing tests (pertahankan) ──

    public function testAttemptLoginWithValidCredentials(): void
    {
        $user = $this->model->attemptLogin('admin', 'admin');

        $this->assertIsArray($user);
        $this->assertSame('admin', $user['username']);
        $this->assertSame('admin', $user['tipe']);
    }

    public function testAttemptLoginWithInvalidPassword(): void
    {
        $user = $this->model->attemptLogin('admin', 'wrongpassword');
        $this->assertNull($user);
    }

    public function testAttemptLoginWithNonexistentUsername(): void
    {
        $user = $this->model->attemptLogin('nobody', 'anything');
        $this->assertNull($user);
    }

    // ── Additional tests ──

    public function testInsertAndFindUser(): void
    {
        $id = $this->model->insert([
            'username'    => 'newuser',
            'password'    => password_hash('secret', PASSWORD_BCRYPT),
            'tipe'        => 'user',
            'akses_klas'  => 'SDM,KEU',
            'akses_modul' => json_encode(['entridata' => 'on']),
        ], true);

        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('newuser', $row['username']);
        $this->assertSame('user', $row['tipe']);
        $this->assertSame('SDM,KEU', $row['akses_klas']);
    }

    public function testUpdateUser(): void
    {
        $id = $this->model->insert([
            'username'    => 'updatable',
            'password'    => password_hash('pass', PASSWORD_BCRYPT),
            'tipe'        => 'user',
            'akses_klas'  => '',
            'akses_modul' => '{}',
        ], true);

        $this->model->update($id, ['tipe' => 'admin']);

        $row = $this->model->find($id);
        $this->assertSame('admin', $row['tipe']);
    }

    public function testDeleteUser(): void
    {
        $id = $this->model->insert([
            'username'    => 'deletable',
            'password'    => password_hash('pass', PASSWORD_BCRYPT),
            'tipe'        => 'user',
            'akses_klas'  => '',
            'akses_modul' => '{}',
        ], true);

        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testAllowedFieldsOnlyInserted(): void
    {
        $id = $this->model->insert([
            'username'    => 'testfields',
            'password'    => password_hash('pass', PASSWORD_BCRYPT),
            'tipe'        => 'user',
            'akses_klas'  => '',
            'akses_modul' => '{}',
            'nonexistent' => 'should-not-persist',
        ], true);

        $row = $this->model->find($id);
        $this->assertArrayNotHasKey('nonexistent', $row);
    }
}
