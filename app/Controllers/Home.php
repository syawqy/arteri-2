<?php

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;

class Home extends BaseController
{
    private int $perPage = 20;

    /**
     * Default route — redirect to search.
     */
    public function index()
    {
        return redirect()->to('search');
    }

    /**
     * Search and list archives with pagination.
     *
     * @param int $offset Zero-based offset for pagination
     */
    public function search($offset = 0)
    {
        helper('form');

        $arsipModel = new ArsipModel();

        // --- Search parameters ---
        $keywords = $this->request->getGet('katakunci') ?? '';

        $filters = [
            'noarsip' => $this->request->getGet('noarsip') ?? '',
            'tanggal' => $this->request->getGet('tanggal') ?? '',
            'uraian'  => $this->request->getGet('uraian') ?? '',
            'ket'     => $this->request->getGet('ket') ?? '',
            'kode'    => $this->request->getGet('kode') ?? '',
            'retensi' => $this->request->getGet('retensi') ?? '',
            'penc'    => $this->request->getGet('penc') ?? '',
            'peng'    => $this->request->getGet('peng') ?? '',
            'lok'     => $this->request->getGet('lok') ?? '',
            'med'     => $this->request->getGet('med') ?? '',
        ];
        $nobox = $this->request->getGet('nobox') ?? '';

        // --- Execute search ---
        $results = $arsipModel->search($keywords, $filters, $this->perPage, $offset);
        $total   = $arsipModel->searchCount($keywords, $filters);

        // --- Source data for view (matches CI3 shape for dropdown preselection) ---
        if ($keywords !== '') {
            $src = [
                'noarsip' => '',
                'tanggal' => '',
                'uraian'  => $keywords,
                'ket'     => '',
                'kode'    => '',
                'retensi' => '',
                'penc'    => '',
                'peng'    => '',
                'lok'     => '',
                'med'     => '',
                'nobox'   => '',
            ];
        } else {
            $src = array_merge($filters, ['nobox' => $nobox]);
        }

        // --- Master data lists for filter dropdowns ---
        $data['kode'] = (new MasterKodeModel())->orderBy('kode', 'ASC')->findAll();
        $data['penc'] = (new MasterPenciptaModel())->orderBy('nama_pencipta', 'ASC')->findAll();
        $data['peng'] = (new MasterPengolahModel())->orderBy('nama_pengolah', 'ASC')->findAll();
        $data['lok']  = (new MasterLokasiModel())->orderBy('nama_lokasi', 'ASC')->findAll();
        $data['med']  = (new MasterMediaModel())->orderBy('nama_media', 'ASC')->findAll();

        // Distinct ket values
        $db             = \Config\Database::connect();
        $data['ket']    = $db->table('data_arsip')
            ->select('ket')
            ->distinct()
            ->orderBy('ket', 'ASC')
            ->get()
            ->getResultArray();

        // --- View data ---
        $data['data']     = $results;
        $data['jml']      = $total;
        $data['src']      = $src;

        // --- Pager ---
        $page = (int) floor($offset / $this->perPage) + 1;

        $pager = service('pager');
        $pager->setPath('search');
        $pager->makeLinks($page, $this->perPage, $total, 'bootstrap3');

        $data['pager'] = $pager;
        $data['pages'] = $pager->links('default', 'bootstrap3');

        // --- Render ---
        return view('layout/header', $data)
             . view('home/search', $data)
             . view('layout/footer');
    }

    /**
     * Display a single archive detail.
     *
     * @param int|string $id
     */
    public function detail($id)
    {
        $model = new ArsipModel();
        $data  = $model->getDetail((int) $id);

        if ($data === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Arsip tidak ditemukan');
        }

        return view('layout/header', $data)
             . view('home/detail', $data)
             . view('layout/footer');
    }

    /**
     * Download current search results as Excel.
     *
     * Placeholder — requires phpoffice/phpspreadsheet.
     */
    public function download()
    {
        return 'Download feature membutuhkan PhpSpreadsheet. Install: composer require phpoffice/phpspreadsheet';
    }
}
