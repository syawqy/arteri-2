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
}
