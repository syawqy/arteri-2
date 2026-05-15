<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DashboardModel;
use App\Traits\JsonResponseTrait;

/**
 * DashboardController - API endpoints untuk statistik dashboard
 */
class Dashboard extends BaseController
{
    use JsonResponseTrait;

    private DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Halaman utama dashboard (HTML view)
     */
    public function index()
    {
        $this->logPageView('dashboard');

        $data['title'] = 'Dashboard';
        $data['stats'] = $this->dashboardModel->getAllStats();

        return view('layout/header', $data)
             . view('dashboard/index', $data)
             . view('layout/footer');
    }

    /**
     * Get semua statistik dalam format JSON
     * GET /dashboard/api/stats
     */
    public function apiStats()
    {
        try {
            $stats = $this->dashboardModel->getAllStats();
            return $this->successResponse($stats, 'Statistik dashboard berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiStats error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil statistik dashboard', 500);
        }
    }

    /**
     * Get statistik ringkas (untuk widget)
     * GET /dashboard/api/summary
     */
    public function apiSummary()
    {
        try {
            $summary = [
                'total_arsip' => $this->dashboardModel->countTotalArsip(),
                'sedang_dipinjam' => $this->dashboardModel->countSedangDipinjam(),
                'overdue' => $this->dashboardModel->countArsipOverdue(),
            ];
            return $this->successResponse($summary, 'Ringkasan statistik berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiSummary error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil ringkasan statistik', 500);
        }
    }

    /**
     * Get statistik per klasifikasi
     * GET /dashboard/api/by-klasifikasi
     */
    public function apiByKlasifikasi()
    {
        try {
            $stats = $this->dashboardModel->getStatistikPerKlasifikasi();
            return $this->successResponse($stats, 'Statistik per klasifikasi berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiByKlasifikasi error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil statistik per klasifikasi', 500);
        }
    }

    /**
     * Get statistik aktivitas bulanan
     * GET /dashboard/api/by-bulan
     */
    public function apiByBulan()
    {
        try {
            $months = (int) ($this->request->getGet('months') ?? 6);
            $months = max(1, min(24, $months)); // Batasi 1-24 bulan
            $stats = $this->dashboardModel->getStatistikAktivitasBulanan($months);
            return $this->successResponse($stats, 'Statistik bulanan berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiByBulan error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil statistik bulanan', 500);
        }
    }

    /**
     * Get statistik per lokasi
     * GET /dashboard/api/by-lokasi
     */
    public function apiByLokasi()
    {
        try {
            $stats = $this->dashboardModel->getStatistikPerLokasi();
            return $this->successResponse($stats, 'Statistik per lokasi berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiByLokasi error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil statistik per lokasi', 500);
        }
    }

    /**
     * Get statistik per media
     * GET /dashboard/api/by-media
     */
    public function apiByMedia()
    {
        try {
            $stats = $this->dashboardModel->getStatistikPerMedia();
            return $this->successResponse($stats, 'Statistik per media berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiByMedia error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil statistik per media', 500);
        }
    }

    /**
     * Get statistik per pencipta
     * GET /dashboard/api/by-pencipta
     */
    public function apiByPencipta()
    {
        try {
            $stats = $this->dashboardModel->getStatistikPerPencipta();
            return $this->successResponse($stats, 'Statistik per pencipta berhasil diambil');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard apiByPencipta error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil statistik per pencipta', 500);
        }
    }
}