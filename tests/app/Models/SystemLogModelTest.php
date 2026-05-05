<?php

namespace Tests\App\Models;

use App\Models\SystemLogModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SystemLogModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private SystemLogModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SystemLogModel();
    }

    public function testInsertAndFindLog(): void
    {
        $id = $this->model->insert([
            'kode_transaksi'     => 'LOGIN_SUCCESS',
            'username_transaksi' => 'admin',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => 'LOGIN_SUCCESS',
            'tabel'              => 'master_user',
            'record_id'          => '1',
            'detail'             => json_encode(['ip' => '127.0.0.1']),
            'ip_address'         => '127.0.0.1',
        ]);

        $this->assertIsInt($id);

        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('LOGIN_SUCCESS', $row['aksi']);
        $this->assertSame('admin', $row['username_transaksi']);
        $this->assertSame('master_user', $row['tabel']);
        $this->assertSame('1', (string) $row['record_id']);
    }

    public function testInsertWithMinimalFields(): void
    {
        $id = $this->model->insert([
            'kode_transaksi'     => 'CREATE',
            'username_transaksi' => 'admin',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => '',
            'tabel'              => '',
            'record_id'          => 0,
            'detail'             => '',
            'ip_address'         => '',
        ]);

        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('CREATE', $row['kode_transaksi']);
        $this->assertSame('', $row['aksi']);
        $this->assertSame('', $row['tabel']);
    }

    public function testFindReturnsNullForNonexistentId(): void
    {
        $this->assertNull($this->model->find(99999));
    }

    public function testFilterByAksi(): void
    {
        $this->model->insert([
            'kode_transaksi' => 'LOGIN_FAILED',
            'username_transaksi' => 'admin',
            'tgl_transaksi' => date('Y-m-d H:i:s'),
            'aksi' => 'LOGIN_FAILED',
        ]);
        $this->model->insert([
            'kode_transaksi' => 'CREATE',
            'username_transaksi' => 'admin',
            'tgl_transaksi' => date('Y-m-d H:i:s'),
            'aksi' => 'CREATE',
        ]);

        $builder = $this->model->builder();
        $failed = $builder->like('aksi', 'LOGIN_FAILED')->countAllResults();
        $this->assertSame(1, $failed);
    }
}
