<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{RetentionScheduleModel, RetentionActionModel, LegalHoldModel};
use App\Models\ArsipModel;
use PhpMcp\Server\Attributes\{McpTool, Schema};

class RetentionHandler
{
    private AccessGuard $accessGuard;
    private RetentionScheduleModel $retentionModel;
    private RetentionActionModel $actionModel;
    private LegalHoldModel $legalHoldModel;
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->accessGuard    = new AccessGuard();
        $this->retentionModel = new RetentionScheduleModel();
        $this->actionModel    = new RetentionActionModel();
        $this->legalHoldModel = new LegalHoldModel();
        $this->arsipModel     = new ArsipModel();
    }

    /**
     * Get archives approaching end of retention period.
     */
    #[McpTool(name: 'get_retention_candidates')]
    public function getRetentionCandidates(
        #[Schema(type: 'integer', description: 'Days before retention end to flag', minimum: 1)]
        int $daysBefore = 90,
        #[Schema(type: 'string', description: 'Filter by classification code')]
        ?string $kode = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $cutoffDate    = date('Y-m-d', strtotime("+{$daysBefore} days"));
        $allSchedules  = $this->retentionModel->getActive();
        $candidates    = [];

        foreach ($allSchedules as $schedule) {
            if ($kode && $schedule['kode_klas'] !== $kode) continue;

            $totalYears = $this->retentionModel->getTotalRetention($schedule);
            $arsips = $this->arsipModel
                ->where('kode', $schedule['kode_klas'])
                ->where('deleted_at', null)
                ->findAll();

            foreach ($arsips as $arsip) {
                $retentionEnd = $this->retentionModel->calculateRetentionEnd($schedule, $arsip['tanggal']);
                if (!$retentionEnd || $retentionEnd > $cutoffDate) continue;

                $legalHold = $this->legalHoldModel->getActiveForArsip($arsip['id']);
                $candidates[] = [
                    'arsip_id'         => $arsip['id'],
                    'noarsip'          => $arsip['noarsip'],
                    'uraian'           => $arsip['uraian'],
                    'tanggal'          => $arsip['tanggal'],
                    'retention_end'    => $retentionEnd,
                    'total_years'      => $totalYears,
                    'has_legal_hold'   => $legalHold !== null,
                    'legal_hold_reason' => $legalHold['reason'] ?? null,
                    'jenis_disposisi'  => $schedule['jenis_disposisi'],
                    'days_remaining'   => (int) ((strtotime($retentionEnd) - strtotime('now')) / 86400),
                ];
            }
        }

        usort($candidates, fn($a, $b) => $a['days_remaining'] <=> $b['days_remaining']);

        return [
            'count'      => count($candidates),
            'candidates' => $candidates,
            'note'       => 'Human approval is required for any disposition action.',
        ];
    }

    /**
     * Get disposition proposals pending approval.
     */
    #[McpTool(name: 'get_disposition_proposals')]
    public function getDispositionProposals(
        #[Schema(type: 'string', description: 'Filter by status')]
        ?string $status = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $query = $this->actionModel->builder();
        if ($status) {
            $query->where('status', $status);
        }
        $proposals = $query->orderBy('created_at', 'DESC')->get()->getResultArray();

        $enriched = [];
        foreach ($proposals as $proposal) {
            $arsip = $this->arsipModel->getDetail($proposal['arsip_id']);
            $enriched[] = ['proposal' => $proposal, 'arsip' => $arsip];
        }

        return ['count' => count($enriched), 'proposals' => $enriched];
    }

    /**
     * Prepare disposition documentation. DOES NOT perform actual disposition.
     */
    #[McpTool(name: 'prepare_disposition_docs')]
    public function prepareDispositionDocs(
        #[Schema(type: 'integer', description: 'Retention action ID')]
        int $actionId,
        #[Schema(type: 'string', description: 'Doc type: berita_acara|assessment_form|approval_proof|summary')]
        string $docType = 'summary'
    ): array {
        $this->accessGuard->requireModule('arsip');

        $action = $this->actionModel->find($actionId);
        if (!$action) return ['status' => 'error', 'message' => 'Retention action not found'];

        $arsip = $this->arsipModel->getDetail($action['arsip_id']);
        if (!$arsip) return ['status' => 'error', 'message' => 'Associated arsip not found'];

        $schedule = $this->retentionModel->getByKode($arsip['kode']);

        $documentation = (match ($docType) {
            'berita_acara'    => $this->generateBeritaAcara($arsip, $action, $schedule),
            'assessment_form' => $this->generateAssessmentForm($arsip, $action, $schedule),
            'approval_proof'  => $this->generateApprovalProof($arsip, $action),
            default           => $this->generateDispositionSummary($arsip, $action, $schedule),
        });

        return [
            'action_id'      => $actionId,
            'doc_type'       => $docType,
            'documentation'  => $documentation,
            'note'           => 'Draft document. Human review and approval required.',
        ];
    }

    /**
     * Check if an archive has an active legal hold.
     */
    #[McpTool(name: 'check_legal_hold')]
    public function checkLegalHold(
        #[Schema(type: 'integer', description: 'Arsip ID')]
        int $arsipId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $hold = $this->legalHoldModel->getActiveForArsip($arsipId);

        return $hold ? [
            'arsip_id'    => $arsipId,
            'has_hold'    => true,
            'hold'        => $hold,
            'can_dispose' => false,
            'message'     => 'Active legal hold — CANNOT be disposed.',
        ] : [
            'arsip_id'    => $arsipId,
            'has_hold'    => false,
            'can_dispose' => true,
            'message'     => 'No active legal holds.',
        ];
    }

    private function generateBeritaAcara(array $arsip, array $action, ?array $schedule): array
    {
        return [
            'title'   => 'Berita Acara Pemusnahan/Penyerahan Arsip',
            'content' => [
                'no_berita_acara' => $action['berita_acara_no'] ?? 'BA-XXX/' . date('Y'),
                'tanggal'         => date('d-m-Y'),
                'arsip'           => [
                    'noarsip' => $arsip['noarsip'], 'uraian' => $arsip['uraian'],
                    'tanggal' => $arsip['tanggal'], 'jumlah'  => $arsip['jumlah'],
                    'media'   => $arsip['media'],   'lokasi'  => $arsip['lokasi'],
                ],
                'retensi'   => $schedule ? [
                    'aktif'     => $schedule['retensi_aktif'] . ' tahun',
                    'inaktif'   => $schedule['retensi_inaktif'] . ' tahun',
                    'disposisi' => $schedule['jenis_disposisi'],
                ] : null,
                'tindakan'  => $action['action_type'],
                'dasar'     => $schedule['dasar_hukum'] ?? '',
            ],
        ];
    }

    private function generateAssessmentForm(array $arsip, array $action, ?array $schedule): array
    {
        return [
            'title'   => 'Formulir Penilaian Arsip',
            'content' => [
                'arsip_info' => ['noarsip' => $arsip['noarsip'], 'uraian' => $arsip['uraian'], 'tanggal' => $arsip['tanggal']],
                'retention_info' => $schedule ? [
                    'retensi_aktif'   => $schedule['retensi_aktif'],
                    'retensi_inaktif' => $schedule['retensi_inaktif'],
                    'retention_end'   => $this->retentionModel->calculateRetentionEnd($schedule, $arsip['tanggal']),
                ] : null,
                'assessment' => [
                    'masih_ada_nilai_guna' => null,
                    'potensi_sengketa'     => null,
                    'ada_legal_hold'       => $this->legalHoldModel->getActiveForArsip($arsip['id']) !== null,
                    'rekomendasi'          => $action['action_type'],
                ],
            ],
        ];
    }

    private function generateApprovalProof(array $arsip, array $action): array
    {
        return [
            'title'   => 'Bukti Persetujuan Disposisi Arsip',
            'content' => [
                'action_type'   => $action['action_type'],
                'arsip_noarsip' => $arsip['noarsip'],
                'prepared_by'   => $action['prepared_by'],
                'prepared_at'   => $action['created_at'],
                'approved_by'   => $action['approved_by'] ?? 'BELUM DISETUJUI',
                'approved_at'   => $action['approved_at'] ?? null,
                'status'        => $action['status'],
            ],
        ];
    }

    private function generateDispositionSummary(array $arsip, array $action, ?array $schedule): array
    {
        return [
            'title'   => 'Ringkasan Disposisi Arsip',
            'content' => [
                'arsip'      => $arsip,
                'schedule'   => $schedule,
                'action'     => $action,
                'legal_hold' => $this->legalHoldModel->getActiveForArsip($arsip['id']),
                'checklist'  => [
                    'retensi_selesai'    => $schedule ? $this->retentionModel->calculateRetentionEnd($schedule, $arsip['tanggal']) <= date('Y-m-d') : false,
                    'ada_persetujuan'    => $action['status'] === 'approved',
                    'tidak_ada_sengketa' => $this->legalHoldModel->getActiveForArsip($arsip['id']) === null,
                    'berita_acara_siap'  => !empty($action['berita_acara_no']),
                ],
            ],
        ];
    }
}
