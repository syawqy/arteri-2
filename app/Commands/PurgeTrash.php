<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\ArsipModel;
use App\Models\SirkulasiModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;
use App\Models\UserModel;
use App\Models\SystemLogModel;

/**
 * Hapus permanen data di sampah yang lebih lama dari masa pemulihan
 * (default: Config\Trash::$recoveryDays). Jalankan via cron harian:
 *
 *   php spark trash:purge
 *
 * Override masa: php spark trash:purge --days=7
 */
class PurgeTrash extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'trash:purge';
    protected $description = 'Hapus permanen data soft-deleted yang melewati masa pemulihan (30 hari).';
    protected $usage       = 'trash:purge [--days N]';
    protected $options     = ['--days' => 'Override masa pemulihan dalam hari.'];

    /** @var array<string,class-string> */
    private array $models = [
        'data_arsip'      => ArsipModel::class,
        'sirkulasi'       => SirkulasiModel::class,
        'master_kode'     => MasterKodeModel::class,
        'master_pencipta' => MasterPenciptaModel::class,
        'master_pengolah' => MasterPengolahModel::class,
        'master_lokasi'   => MasterLokasiModel::class,
        'master_media'    => MasterMediaModel::class,
        'master_user'     => UserModel::class,
    ];

    public function run(array $params)
    {
        $days = isset($params['days']) ? (int) $params['days'] : (int) config('Trash')->recoveryDays;
        if ($days < 0) {
            $days = 0;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        CLI::write("Menghapus permanen data sampah sebelum: {$cutoff} (>{$days} hari)", 'yellow');

        $counts = [];
        $total  = 0;

        foreach ($this->models as $table => $class) {
            /** @var \CodeIgniter\Model $model */
            $model = new $class();

            $rows = $model->onlyDeleted()
                ->where('deleted_at <', $cutoff)
                ->findAll();

            $count = 0;
            foreach ($rows as $row) {
                // Hapus file fisik milik arsip.
                if ($table === 'data_arsip' && ! empty($row['file'])) {
                    $filePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $row['file'];
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                }

                $model->delete($row['id'], true); // purge = hard delete
                $count++;
            }

            $counts[$table] = $count;
            $total += $count;
            if ($count > 0) {
                CLI::write("  {$table}: {$count} dihapus", 'green');
            }
        }

        // Catat ke system_log (tanpa session/request — konteks CLI).
        (new SystemLogModel())->insert([
            'kode_transaksi'     => 'PURGE',
            'username_transaksi' => 'system',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => 'PURGE',
            'tabel'              => 'trash',
            'record_id'          => null,
            'detail'             => json_encode(['cutoff' => $cutoff, 'days' => $days, 'counts' => $counts, 'total' => $total]),
            'ip_address'         => null,
        ], false);

        CLI::write("Selesai. Total {$total} record dihapus permanen.", 'green');

        return 0;
    }
}
