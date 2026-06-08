<?php

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\SirkulasiModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;
use App\Models\UserModel;

/**
 * Trash / Recycle Bin — daftar data terhapus (soft delete), pemulihan,
 * dan penghapusan permanen. Hanya untuk admin (task 7b).
 */
class Trash extends BaseController
{
    /**
     * Registry entitas yang mendukung soft-delete.
     * `unique` = kolom unik untuk pengecekan konflik saat restore (null = tidak ada).
     */
    protected array $entities = [
        'arsip'     => ['model' => ArsipModel::class,         'table' => 'data_arsip',      'label' => 'Arsip',       'unique' => null],
        'sirkulasi' => ['model' => SirkulasiModel::class,     'table' => 'sirkulasi',       'label' => 'Sirkulasi',   'unique' => null],
        'kode'      => ['model' => MasterKodeModel::class,    'table' => 'master_kode',     'label' => 'Klasifikasi', 'unique' => 'kode'],
        'pencipta'  => ['model' => MasterPenciptaModel::class,'table' => 'master_pencipta', 'label' => 'Pencipta',    'unique' => 'nama_pencipta'],
        'pengolah'  => ['model' => MasterPengolahModel::class,'table' => 'master_pengolah', 'label' => 'Pengolah',    'unique' => 'nama_pengolah'],
        'lokasi'    => ['model' => MasterLokasiModel::class,  'table' => 'master_lokasi',   'label' => 'Lokasi',      'unique' => 'nama_lokasi'],
        'media'     => ['model' => MasterMediaModel::class,   'table' => 'master_media',    'label' => 'Media',       'unique' => 'nama_media'],
        'user'      => ['model' => UserModel::class,          'table' => 'master_user',     'label' => 'User',        'unique' => 'username'],
    ];

    public function index()
    {
        if (! isAdmin()) {
            return redirect()->to('/')->with('error', 'Akses ditolak. Hanya admin yang dapat membuka Sampah.');
        }

        $recoveryDays = config('Trash')->recoveryDays;
        $now = time();

        $groups = [];
        foreach ($this->entities as $type => $cfg) {
            $model = new $cfg['model']();
            $rows  = $model->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();

            $items = [];
            foreach ($rows as $row) {
                $deletedAt = $row['deleted_at'] ?? null;
                $daysLeft  = null;
                if ($deletedAt !== null) {
                    $elapsed  = (int) floor(($now - strtotime($deletedAt)) / 86400);
                    $daysLeft = max(0, $recoveryDays - $elapsed);
                }

                $items[] = [
                    'id'         => $row['id'],
                    'display'    => $this->displayFor($type, $row),
                    'deleted_at' => $deletedAt,
                    'days_left'  => $daysLeft,
                ];
            }

            $groups[$type] = [
                'label' => $cfg['label'],
                'type'  => $type,
                'items' => $items,
                'count' => count($items),
            ];
        }

        $this->logPageView('admin/trash');

        return view('trash/index', [
            'title'        => 'Sampah',
            'groups'       => $groups,
            'recoveryDays' => $recoveryDays,
        ]);
    }

    /**
     * Pulihkan satu item dari sampah.
     * POST: type, id
     */
    public function restore()
    {
        if (! isAdmin()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $type = (string) $this->request->getPost('type');
        $id   = (int) $this->request->getPost('id');

        $cfg = $this->entities[$type] ?? null;
        if ($cfg === null || $id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
        }

        $model = new $cfg['model']();
        $row   = $model->onlyDeleted()->find($id);
        if ($row === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan di sampah.']);
        }

        // Cegah konflik unique: jika sudah ada data aktif dengan nilai unik yang sama.
        if ($cfg['unique'] !== null) {
            $live = $model->where($cfg['unique'], $row[$cfg['unique']])->first();
            if ($live !== null) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Tidak dapat memulihkan: data dengan nama/kode yang sama sudah ada. Hapus atau ubah data tersebut terlebih dahulu.',
                ]);
            }
        }

        $model->update($id, ['deleted_at' => null]);
        $this->logAction('RESTORE', $cfg['table'], $id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Data berhasil dipulihkan.']);
    }

    /**
     * Hapus permanen satu item dari sampah.
     * POST: type, id
     */
    public function purge()
    {
        if (! isAdmin()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $type = (string) $this->request->getPost('type');
        $id   = (int) $this->request->getPost('id');

        $cfg = $this->entities[$type] ?? null;
        if ($cfg === null || $id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
        }

        $model = new $cfg['model']();
        $row   = $model->onlyDeleted()->find($id);
        if ($row === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan di sampah.']);
        }

        // Hapus file fisik milik arsip saat purge.
        if ($type === 'arsip' && ! empty($row['file'])) {
            $filePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $row['file'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        $model->delete($id, true); // purge = hard delete
        $this->logAction('PURGE', $cfg['table'], $id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Data berhasil dihapus permanen.']);
    }

    /**
     * Bangun string tampilan ringkas per entitas.
     */
    private function displayFor(string $type, array $r): string
    {
        return match ($type) {
            'arsip'     => ($r['noarsip'] ?? '') . ' - ' . mb_strimwidth((string) ($r['uraian'] ?? ''), 0, 60, '…'),
            'sirkulasi' => ($r['noarsip'] ?? '') . ' (' . ($r['username_peminjam'] ?? '') . ')',
            'kode'      => ($r['kode'] ?? '') . ' - ' . ($r['nama'] ?? ''),
            'pencipta'  => (string) ($r['nama_pencipta'] ?? ''),
            'pengolah'  => (string) ($r['nama_pengolah'] ?? ''),
            'lokasi'    => (string) ($r['nama_lokasi'] ?? ''),
            'media'     => (string) ($r['nama_media'] ?? ''),
            'user'      => (string) ($r['username'] ?? ''),
            default     => (string) ($r['id'] ?? ''),
        };
    }
}
