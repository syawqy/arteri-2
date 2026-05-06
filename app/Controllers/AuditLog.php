<?php

namespace App\Controllers;

use App\Models\SystemLogModel;

class AuditLog extends BaseController
{
    private int $perPage = 50;

    public function index()
    {
        if (! isAdmin()) {
            return redirect()->to('/')->with('error', 'Akses ditolak. Hanya admin yang dapat melihat audit log.');
        }

        $logModel = new SystemLogModel();
        $page = (int) ($this->request->getGet('page') ?? 1);
        $offset = ($page - 1) * $this->perPage;

        $filters = [
            'aksi'    => $this->request->getGet('aksi') ?? '',
            'tabel'   => $this->request->getGet('tabel') ?? '',
            'keyword' => $this->request->getGet('keyword') ?? '',
            'tgl_dari' => $this->request->getGet('tgl_dari') ?? '',
            'tgl_sampai' => $this->request->getGet('tgl_sampai') ?? '',
        ];

        $builder = $logModel->builder()->orderBy('tgl_transaksi', 'DESC');

        if ($filters['aksi'] !== '') {
            $builder->like('aksi', $filters['aksi']);
        }
        if ($filters['tabel'] !== '') {
            $builder->where('tabel', $filters['tabel']);
        }
        if ($filters['keyword'] !== '') {
            $builder->groupStart()
                ->like('username_transaksi', $filters['keyword'])
                ->orLike('kode_transaksi', $filters['keyword'])
                ->orLike('detail', $filters['keyword'])
                ->groupEnd();
        }
        if ($filters['tgl_dari'] !== '') {
            $builder->where('tgl_transaksi >=', $filters['tgl_dari'] . ' 00:00:00');
        }
        if ($filters['tgl_sampai'] !== '') {
            $builder->where('tgl_transaksi <=', $filters['tgl_sampai'] . ' 23:59:59');
        }

        $total = $builder->countAllResults(false);
        $logs = $builder->limit($this->perPage, $offset)->get()->getResultArray();

        $aksiList = $logModel->builder()
            ->select('aksi')->distinct()
            ->where('aksi IS NOT NULL')
            ->where("aksi != ''")
            ->orderBy('aksi', 'ASC')
            ->get()->getResultArray();

        $tabelList = $logModel->builder()
            ->select('tabel')->distinct()
            ->where('tabel IS NOT NULL')
            ->where("tabel != ''")
            ->orderBy('tabel', 'ASC')
            ->get()->getResultArray();

        $totalPages = (int) ceil($total / $this->perPage);

        $this->logPageView('admin/auditlog');

        $data = [
            'title'      => 'Audit Log',
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'filters'    => $filters,
            'aksiList'   => $aksiList,
            'tabelList'  => $tabelList,
        ];

        return view('layout/header', $data)
            . view('audit/index', $data)
            . view('layout/footer');
    }

    public function detail($id)
    {
        if (! isAdmin()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak']);
        }

        $logModel = new SystemLogModel();
        $log = $logModel->find($id);

        if ($log === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $log]);
    }
}
