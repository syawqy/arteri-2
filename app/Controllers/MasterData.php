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
        $katakunci = $this->request->getGet('katakunci') ?? '';
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
        if (! $this->validate($this->getValidationRules('kode'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterKodeModel())->insert([
            'kode'    => $this->request->getPost('kode'),
            'nama'    => $this->request->getPost('nama'),
            'retensi' => $this->request->getPost('retensi'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateKode()
    {
        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('kode'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterKodeModel())->update($id, [
            'kode'    => $this->request->getPost('kode'),
            'nama'    => $this->request->getPost('nama'),
            'retensi' => $this->request->getPost('retensi'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteKode()
    {
        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('kode', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode sedang digunakan oleh data arsip.']);
        }
        (new MasterKodeModel())->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getKode()
    {
        $id   = (int) $this->request->getPost('id');
        $row  = (new MasterKodeModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadKode()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('kode', $katakunci);
        return $this->buildTableHtml('kode', $items);
    }

    /* ================================================================
     * PENCIPTA
     * ================================================================ */

    public function penc()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
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
        if (! $this->validate($this->getValidationRules('pencipta'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterPenciptaModel())->insert([
            'nama_pencipta' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updatePenc()
    {
        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('pencipta'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterPenciptaModel())->update($id, [
            'nama_pencipta' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deletePenc()
    {
        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('pencipta', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pencipta sedang digunakan oleh data arsip.']);
        }
        (new MasterPenciptaModel())->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getPenc()
    {
        $id  = (int) $this->request->getPost('id');
        $row = (new MasterPenciptaModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadPenc()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('pencipta', $katakunci);
        return $this->buildTableHtml('pencipta', $items);
    }

    /* ================================================================
     * PENGOLAH
     * ================================================================ */

    public function pengolah()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
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
        if (! $this->validate($this->getValidationRules('pengolah'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterPengolahModel())->insert([
            'nama_pengolah' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updatePengolah()
    {
        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('pengolah'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterPengolahModel())->update($id, [
            'nama_pengolah' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deletePengolah()
    {
        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('pengolah', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unit pengolah sedang digunakan oleh data arsip.']);
        }
        (new MasterPengolahModel())->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getPengolah()
    {
        $id  = (int) $this->request->getPost('id');
        $row = (new MasterPengolahModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadPengolah()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('pengolah', $katakunci);
        return $this->buildTableHtml('pengolah', $items);
    }

    /* ================================================================
     * LOKASI
     * ================================================================ */

    public function lokasi()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
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
        if (! $this->validate($this->getValidationRules('lokasi'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterLokasiModel())->insert([
            'nama_lokasi' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateLokasi()
    {
        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('lokasi'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterLokasiModel())->update($id, [
            'nama_lokasi' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteLokasi()
    {
        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('lokasi', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Lokasi sedang digunakan oleh data arsip.']);
        }
        (new MasterLokasiModel())->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getLokasi()
    {
        $id  = (int) $this->request->getPost('id');
        $row = (new MasterLokasiModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadLokasi()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
        $items     = $this->fetchList('lokasi', $katakunci);
        return $this->buildTableHtml('lokasi', $items);
    }

    /* ================================================================
     * MEDIA
     * ================================================================ */

    public function media()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';
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
        if (! $this->validate($this->getValidationRules('media'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterMediaModel())->insert([
            'nama_media' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateMedia()
    {
        $id = (int) $this->request->getPost('id');
        if (! $this->validate($this->getValidationRules('media'))) {
            return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        (new MasterMediaModel())->update($id, [
            'nama_media' => $this->request->getPost('nama'),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteMedia()
    {
        $id = (int) $this->request->getPost('id');
        if ($this->countArsipUsage('media', $id) > 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Media sedang digunakan oleh data arsip.']);
        }
        (new MasterMediaModel())->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getMedia()
    {
        $id  = (int) $this->request->getPost('id');
        $row = (new MasterMediaModel())->find($id);
        return $this->response->setJSON($row ?? []);
    }

    public function reloadMedia()
    {
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
            $html .= '<td><a data-toggle="modal" data-target="#edit' . $type . '" class="ed' . $type . '" href="#" id="' . esc($u['id'], 'attr') . '" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>';
            $html .= '<td><a data-toggle="modal" data-target="#del' . $type . '" class="del' . $type . '" href="#" id="' . esc($u['id'], 'attr') . '" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>';
            $html .= '</tr>';
            $no++;
        }

        $html .= '</table>';
        return $html;
    }
}
