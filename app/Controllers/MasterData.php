<?php

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;

class MasterData extends BaseController
{
    /**
     * Maps entity to ACL module name.
     */
    private const ACL_MODULE_MAP = [
        'kode'     => 'klasifikasi',
        'pencipta' => 'pencipta',
        'pengolah' => 'pengolah',
        'lokasi'   => 'lokasi',
        'media'    => 'media',
    ];

    /**
     * Check module access, send JSON error if denied.
     */
    private function requireAccess(string $entity): bool
    {
        $module = self::ACL_MODULE_MAP[$entity] ?? $entity;
        if (! hasModuleAccess($module)) {
            return false;
        }
        return true;
    }

    /**
     * Check module access for view pages, redirect to / if denied.
     */
    private function requireViewAccess(string $entity): bool
    {
        if (! $this->requireAccess($entity)) {
            return false;
        }
        return true;
    }

    /**
     * Entity configuration map.
     */
    protected array $entities = [
        'kode' => [
            'model'       => MasterKodeModel::class,
            'table'       => 'master_kode',
            'arsip_field' => 'kode',
            'label'       => 'Klasifikasi',
            'title'       => 'Data Klasifikasi',
            'order_by'    => 'kode ASC',
            'search_cols' => ['kode', 'nama'],
            'fields'      => [
                'kode'    => ['label' => 'Kode',    'rules' => 'required'],
                'nama'    => ['label' => 'Nama',    'rules' => 'required'],
                'retensi' => ['label' => 'Retensi', 'rules' => 'required|integer'],
            ],
            'view'        => 'klasifikasi',
            'var'         => 'items',
            'display_col' => 'nama', // used in reload table
        ],
        'pencipta' => [
            'model'       => MasterPenciptaModel::class,
            'table'       => 'master_pencipta',
            'arsip_field' => 'pencipta',
            'label'       => 'Pencipta Arsip',
            'title'       => 'Data Pencipta Arsip',
            'order_by'    => 'nama_pencipta ASC',
            'search_cols' => ['nama_pencipta'],
            'fields'      => [
                'nama' => ['label' => 'Nama', 'rules' => 'required'],
            ],
            'view'        => 'pencipta',
            'var'         => 'items',
            'display_col' => 'nama_pencipta',
        ],
        'pengolah' => [
            'model'       => MasterPengolahModel::class,
            'table'       => 'master_pengolah',
            'arsip_field' => 'unit_pengolah',
            'label'       => 'Unit Pengolah Arsip',
            'title'       => 'Data Unit Pengolah Arsip',
            'order_by'    => 'nama_pengolah ASC',
            'search_cols' => ['nama_pengolah'],
            'fields'      => [
                'nama' => ['label' => 'Nama', 'rules' => 'required'],
            ],
            'view'        => 'pengolah',
            'var'         => 'items',
            'display_col' => 'nama_pengolah',
        ],
        'lokasi' => [
            'model'       => MasterLokasiModel::class,
            'table'       => 'master_lokasi',
            'arsip_field' => 'lokasi',
            'label'       => 'Lokasi Arsip',
            'title'       => 'Data Lokasi Arsip',
            'order_by'    => 'nama_lokasi ASC',
            'search_cols' => ['nama_lokasi'],
            'fields'      => [
                'nama' => ['label' => 'Nama', 'rules' => 'required'],
            ],
            'view'        => 'lokasi',
            'var'         => 'items',
            'display_col' => 'nama_lokasi',
        ],
        'media' => [
            'model'       => MasterMediaModel::class,
            'table'       => 'master_media',
            'arsip_field' => 'media',
            'label'       => 'Media Arsip',
            'title'       => 'Data Media Arsip',
            'order_by'    => 'nama_media ASC',
            'search_cols' => ['nama_media'],
            'fields'      => [
                'nama' => ['label' => 'Nama', 'rules' => 'required'],
            ],
            'view'        => 'media',
            'var'         => 'items',
            'display_col' => 'nama_media',
        ],
    ];

    /**
     * Get model instance for an entity type.
     */
    protected function getModel(string $type)
    {
        $class = $this->entities[$type]['model'];
        return new $class();
    }

    /**
     * Build validation rules for an entity type.
     */
    protected function getValidationRules(string $type): array
    {
        $rules = [];
        foreach ($this->entities[$type]['fields'] as $field => $config) {
            $rules[$field] = $config['rules'];
        }
        return $rules;
    }

    /**
     * Fetch list with optional keyword search.
     */
    protected function fetchList(string $type, string $keyword = ''): array
    {
        $model   = $this->getModel($type);
        $config  = $this->entities[$type];
        $builder = $model->builder();

        // Raw builder bypasses soft-delete scoping — exclude trashed rows.
        $builder->where('deleted_at', null);

        if ($keyword !== '') {
            $builder->groupStart();
            foreach ($config['search_cols'] as $i => $col) {
                if ($i === 0) {
                    $builder->like($col, $keyword);
                } else {
                    $builder->orLike($col, $keyword);
                }
            }
            $builder->groupEnd();
        }

        $parts = explode(' ', $config['order_by'], 2);
        return $builder->orderBy($parts[0], $parts[1] ?? 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Check how many archive records reference this master record.
     */
    protected function countArsipUsage(string $type, int $id): int
    {
        $field = $this->entities[$type]['arsip_field'];
        return (new ArsipModel())->where($field, $id)->countAllResults();
    }

    /* ================================================================
     * KODE / KLASIFIKASI
     * ================================================================ */

    public function klas()
    {
        if (! $this->requireViewAccess('kode')) {
            return redirect()->to('/');
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $this->logPageView('datamaster/klasifikasi');

        $data = [
            'items'     => $this->fetchList('kode', $katakunci),
            'katakunci' => $katakunci,
            'title'     => $this->entities['kode']['title'],
        ];
        return view('layout/header', $data)
            . view('master/klasifikasi', $data)
            . view('layout/footer');
    }

    public function createKode()
    {
        if (! $this->requireAccess('kode')) return;

        if (! $this->validate($this->getValidationRules('kode'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        $kode    = $this->request->getPost('kode');
        $nama    = $this->request->getPost('nama');
        $retensi = $this->request->getPost('retensi');

        // Revive soft-deleted row dengan kode yang sama (hindari konflik unique key).
        $model = new MasterKodeModel();
        $revived = $model->onlyDeleted()->where('kode', $kode)->first();
        if ($revived !== null) {
            $model->update($revived['id'], [
                'kode' => $kode, 'nama' => $nama, 'retensi' => $retensi, 'deleted_at' => null,
            ]);
            $this->logAction('RESTORE', 'master_kode', (int) $revived['id']);
            return $this->response->setJSON(['status' => 'success']);
        }

        $model->insert([
            'kode'    => $kode,
            'nama'    => $nama,
            'retensi' => $retensi,
        ]);
        $insertId = $model->getInsertID();
        $this->logAction('CREATE', 'master_kode', $insertId);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateKode()
    {
        if (! $this->requireAccess('kode')) return;

        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('kode'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterKodeModel())->update($id, [
            'kode'    => $this->request->getPost('kode'),
            'nama'    => $this->request->getPost('nama'),
            'retensi' => $this->request->getPost('retensi'),
        ]);
        $this->logAction('UPDATE', 'master_kode', $id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteKode()
    {
        if (! $this->requireAccess('kode')) return;

        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('kode', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode sedang digunakan oleh data arsip.']);
        }
        (new MasterKodeModel())->delete($id);
        $this->logAction('DELETE', 'master_kode', $id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getKode()
    {
        if (! $this->requireAccess('kode')) return;

        $id   = (int) $this->request->getPost('id');
        $row  = (new MasterKodeModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadKode()
    {
        if (! $this->requireAccess('kode')) return '';

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('kode', $katakunci);
        return $this->buildTableHtml('kode', $items);
    }

    /* ================================================================
     * PENCIPTA
     * ================================================================ */

    public function penc()
    {
        if (! $this->requireViewAccess('pencipta')) {
            return redirect()->to('/');
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $this->logPageView('datamaster/pencipta');

        $data = [
            'items'     => $this->fetchList('pencipta', $katakunci),
            'katakunci' => $katakunci,
            'title'     => $this->entities['pencipta']['title'],
        ];
        return view('layout/header', $data)
            . view('master/pencipta', $data)
            . view('layout/footer');
    }

    public function createPenc()
    {
        if (! $this->requireAccess('pencipta')) return;

        if (! $this->validate($this->getValidationRules('pencipta'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        $model = new MasterPenciptaModel();
        $nama  = $this->request->getPost('nama');

        $revived = $model->onlyDeleted()->where('nama_pencipta', $nama)->first();
        if ($revived !== null) {
            $model->update($revived['id'], ['nama_pencipta' => $nama, 'deleted_at' => null]);
            $this->logAction('RESTORE', 'master_pencipta', (int) $revived['id']);
            return $this->response->setJSON(['status' => 'success']);
        }

        $model->insert([
            'nama_pencipta' => $nama,
        ]);
        $insertId = $model->getInsertID();
        $this->logAction('CREATE', 'master_pencipta', $insertId);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updatePenc()
    {
        if (! $this->requireAccess('pencipta')) return;

        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('pencipta'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterPenciptaModel())->update($id, [
            'nama_pencipta' => $this->request->getPost('nama'),
        ]);
        $this->logAction('UPDATE', 'master_pencipta', $id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deletePenc()
    {
        if (! $this->requireAccess('pencipta')) return;

        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('pencipta', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pencipta sedang digunakan oleh data arsip.']);
        }
        (new MasterPenciptaModel())->delete($id);
        $this->logAction('DELETE', 'master_pencipta', $id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getPenc()
    {
        if (! $this->requireAccess('pencipta')) return;

        $id  = (int) $this->request->getPost('id');
        $row = (new MasterPenciptaModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadPenc()
    {
        if (! $this->requireAccess('pencipta')) return '';

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('pencipta', $katakunci);
        return $this->buildTableHtml('pencipta', $items);
    }

    /* ================================================================
     * PENGOLAH
     * ================================================================ */

    public function pengolah()
    {
        if (! $this->requireViewAccess('pengolah')) {
            return redirect()->to('/');
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $this->logPageView('datamaster/pengolah');

        $data = [
            'items'     => $this->fetchList('pengolah', $katakunci),
            'katakunci' => $katakunci,
            'title'     => $this->entities['pengolah']['title'],
        ];
        return view('layout/header', $data)
            . view('master/pengolah', $data)
            . view('layout/footer');
    }

    public function createPengolah()
    {
        if (! $this->requireAccess('pengolah')) return;

        if (! $this->validate($this->getValidationRules('pengolah'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        $model = new MasterPengolahModel();
        $nama  = $this->request->getPost('nama');

        $revived = $model->onlyDeleted()->where('nama_pengolah', $nama)->first();
        if ($revived !== null) {
            $model->update($revived['id'], ['nama_pengolah' => $nama, 'deleted_at' => null]);
            $this->logAction('RESTORE', 'master_pengolah', (int) $revived['id']);
            return $this->response->setJSON(['status' => 'success']);
        }

        $model->insert([
            'nama_pengolah' => $nama,
        ]);
        $insertId = $model->getInsertID();
        $this->logAction('CREATE', 'master_pengolah', $insertId);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updatePengolah()
    {
        if (! $this->requireAccess('pengolah')) return;

        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('pengolah'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterPengolahModel())->update($id, [
            'nama_pengolah' => $this->request->getPost('nama'),
        ]);
        $this->logAction('UPDATE', 'master_pengolah', $id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deletePengolah()
    {
        if (! $this->requireAccess('pengolah')) return;

        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('pengolah', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unit pengolah sedang digunakan oleh data arsip.']);
        }
        (new MasterPengolahModel())->delete($id);
        $this->logAction('DELETE', 'master_pengolah', $id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getPengolah()
    {
        if (! $this->requireAccess('pengolah')) return;

        $id  = (int) $this->request->getPost('id');
        $row = (new MasterPengolahModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadPengolah()
    {
        if (! $this->requireAccess('pengolah')) return '';

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('pengolah', $katakunci);
        return $this->buildTableHtml('pengolah', $items);
    }

    /* ================================================================
     * LOKASI
     * ================================================================ */

    public function lokasi()
    {
        if (! $this->requireViewAccess('lokasi')) {
            return redirect()->to('/');
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $this->logPageView('datamaster/lokasi');

        $data = [
            'items'     => $this->fetchList('lokasi', $katakunci),
            'katakunci' => $katakunci,
            'title'     => $this->entities['lokasi']['title'],
        ];
        return view('layout/header', $data)
            . view('master/lokasi', $data)
            . view('layout/footer');
    }

    public function createLokasi()
    {
        if (! $this->requireAccess('lokasi')) return;

        if (! $this->validate($this->getValidationRules('lokasi'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        $model = new MasterLokasiModel();
        $nama  = $this->request->getPost('nama');

        $revived = $model->onlyDeleted()->where('nama_lokasi', $nama)->first();
        if ($revived !== null) {
            $model->update($revived['id'], ['nama_lokasi' => $nama, 'deleted_at' => null]);
            $this->logAction('RESTORE', 'master_lokasi', (int) $revived['id']);
            return $this->response->setJSON(['status' => 'success']);
        }

        $model->insert([
            'nama_lokasi' => $nama,
        ]);
        $insertId = $model->getInsertID();
        $this->logAction('CREATE', 'master_lokasi', $insertId);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateLokasi()
    {
        if (! $this->requireAccess('lokasi')) return;

        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('lokasi'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterLokasiModel())->update($id, [
            'nama_lokasi' => $this->request->getPost('nama'),
        ]);
        $this->logAction('UPDATE', 'master_lokasi', $id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteLokasi()
    {
        if (! $this->requireAccess('lokasi')) return;

        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('lokasi', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Lokasi sedang digunakan oleh data arsip.']);
        }
        (new MasterLokasiModel())->delete($id);
        $this->logAction('DELETE', 'master_lokasi', $id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getLokasi()
    {
        if (! $this->requireAccess('lokasi')) return;

        $id  = (int) $this->request->getPost('id');
        $row = (new MasterLokasiModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadLokasi()
    {
        if (! $this->requireAccess('lokasi')) return '';

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('lokasi', $katakunci);
        return $this->buildTableHtml('lokasi', $items);
    }

    /* ================================================================
     * MEDIA
     * ================================================================ */

    public function media()
    {
        if (! $this->requireViewAccess('media')) {
            return redirect()->to('/');
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $this->logPageView('datamaster/media');

        $data = [
            'items'     => $this->fetchList('media', $katakunci),
            'katakunci' => $katakunci,
            'title'     => $this->entities['media']['title'],
        ];
        return view('layout/header', $data)
            . view('master/media', $data)
            . view('layout/footer');
    }

    public function createMedia()
    {
        if (! $this->requireAccess('media')) return;

        if (! $this->validate($this->getValidationRules('media'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        $model = new MasterMediaModel();
        $nama  = $this->request->getPost('nama');

        $revived = $model->onlyDeleted()->where('nama_media', $nama)->first();
        if ($revived !== null) {
            $model->update($revived['id'], ['nama_media' => $nama, 'deleted_at' => null]);
            $this->logAction('RESTORE', 'master_media', (int) $revived['id']);
            return $this->response->setJSON(['status' => 'success']);
        }

        $model->insert([
            'nama_media' => $nama,
        ]);
        $insertId = $model->getInsertID();
        $this->logAction('CREATE', 'master_media', $insertId);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateMedia()
    {
        if (! $this->requireAccess('media')) return;

        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('media'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterMediaModel())->update($id, [
            'nama_media' => $this->request->getPost('nama'),
        ]);
        $this->logAction('UPDATE', 'master_media', $id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteMedia()
    {
        if (! $this->requireAccess('media')) return;

        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('media', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Media sedang digunakan oleh data arsip.']);
        }
        (new MasterMediaModel())->delete($id);
        $this->logAction('DELETE', 'master_media', $id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getMedia()
    {
        if (! $this->requireAccess('media')) return;

        $id  = (int) $this->request->getPost('id');
        $row = (new MasterMediaModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadMedia()
    {
        if (! $this->requireAccess('media')) return '';

        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('media', $katakunci);
        return $this->buildTableHtml('media', $items);
    }

    /* ================================================================
     * HTML TABLE BUILDER (for AJAX reload)
     * ================================================================ */

    /**
     * Build HTML table string for AJAX reload endpoints.
     * Matches the CI3 output structure so existing JS replacement logic works.
     */
    protected function buildTableHtml(string $type, array $items): string
    {
        $cfg   = $this->entities[$type];
        $table = $cfg['table'];
        $legacyUiType = match ($type) {
            'pencipta' => 'penc',
            'pengolah' => 'peng',
            'lokasi'   => 'lok',
            'media'    => 'med',
            default    => $type,
        };

        $html = '<table class="table table-bordered" name="v' . esc($type, 'attr') . '" id="v' . esc($type, 'attr') . '">';
        $html .= '<thead>';

        switch ($type) {
            case 'kode':
                $html .= '<th>Kode</th>';
                $html .= '<th>Nama</th>';
                $html .= '<th>Retensi</th>';
                break;
            default:
                $html .= '<th class="width-sm">No</th>';
                $html .= '<th>Nama</th>';
                break;
        }

        $html .= '<th class="width-sm"></th>';
        $html .= '<th class="width-sm"></th>';
        $html .= '</thead>';

        if (empty($items)) {
            $html .= '</table>';
            return $html;
        }

        $no = 1;
        foreach ($items as $u) {
            $html .= '<tr>';
            switch ($type) {
                case 'kode':
                    $html .= '<td>' . esc($u['kode']) . '</td>';
                    $html .= '<td>' . esc($u['nama']) . '</td>';
                    $html .= '<td>' . esc($u['retensi']) . ' Tahun</td>';
                    break;
                default:
                    $html .= '<td>' . $no . '</td>';
                    $html .= '<td>' . esc($u[$cfg['display_col']]) . '</td>';
                    break;
            }
            $html .= '<td><a data-toggle="modal" data-target="#edit' . $legacyUiType . '" class="ed' . $legacyUiType . '" href="#" id="' . esc($u['id'], 'attr') . '" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>';
            $html .= '<td><a data-toggle="modal" data-target="#del' . $legacyUiType . '" class="del' . $legacyUiType . '" href="#" id="' . esc($u['id'], 'attr') . '" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>';
            $html .= '</tr>';
            $no++;
        }

        $html .= '</table>';
        return $html;
    }
}
