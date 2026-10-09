<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{DocumentAccessLogModel, AiClassificationModel, LegalHoldModel};
use App\Models\{ArsipModel, UserModel, SystemLogModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class ComplianceHandler
{
    private AccessGuard $accessGuard;
    private DocumentAccessLogModel $accessLogModel;
    private AiClassificationModel $classificationModel;
    private LegalHoldModel $legalHoldModel;
    private ArsipModel $arsipModel;
    private UserModel $userModel;
    private SystemLogModel $systemLogModel;

    public function __construct()
    {
        $this->accessGuard         = new AccessGuard();
        $this->accessLogModel      = new DocumentAccessLogModel();
        $this->classificationModel = new AiClassificationModel();
        $this->legalHoldModel      = new LegalHoldModel();
        $this->arsipModel          = new ArsipModel();
        $this->userModel           = new UserModel();
        $this->systemLogModel      = new SystemLogModel();
    }

    /**
     * Check metadata completeness across archives.
     */
    #[McpTool(name: 'check_metadata_completeness')]
    public function checkMetadataCompleteness(
        #[Schema(type: 'integer', description: 'Arsip ID to check, or 0 for all')]
        int $arsipId = 0,
        #[Schema(type: 'string', description: 'Filter by classification code')]
        ?string $kode = null
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $requiredFields = ['noarsip', 'uraian', 'tanggal', 'pencipta', 'unit_pengolah', 'kode', 'lokasi', 'media'];

        if ($arsipId > 0) {
            $arsips = [$this->arsipModel->getDetail($arsipId)];
            $arsips = array_filter($arsips);
        } else {
            $query = $this->arsipModel->where('deleted_at', null);
            if ($kode) $query->where('kode', $kode);
            $arsips = $query->findAll();
        }

        $results = [];
        $summary = ['total' => 0, 'complete' => 0, 'incomplete' => 0, 'missing_fields' => []];

        foreach ($arsips as $arsip) {
            if (!$this->accessGuard->canAccessArsip($arsip)) continue;
            $missing = [];
            foreach ($requiredFields as $field) {
                if (empty($arsip[$field]) || trim((string) $arsip[$field]) === '') {
                    $missing[] = $field;
                    $summary['missing_fields'][$field] = ($summary['missing_fields'][$field] ?? 0) + 1;
                }
            }
            $summary['total']++;
            if (empty($missing)) {
                $summary['complete']++;
            } else {
                $summary['incomplete']++;
                $results[] = [
                    'arsip_id'       => $arsip['id'],
                    'noarsip'        => $arsip['noarsip'],
                    'missing_fields' => $missing,
                    'completeness'   => round((count($requiredFields) - count($missing)) / count($requiredFields) * 100, 1),
                ];
            }
        }

        return [
            'summary'            => $summary,
            'issues'             => array_slice($results, 0, 50),
            'completeness_rate'  => $summary['total'] > 0 ? round($summary['complete'] / $summary['total'] * 100, 1) : 0,
        ];
    }

    /**
     * Detect unauthorized changes to archives.
     */
    #[McpTool(name: 'detect_unauthorized_changes')]
    public function detectUnauthorizedChanges(
        #[Schema(type: 'string', description: 'Check since this date (YYYY-MM-DD)')]
        ?string $sinceDate = null,
        #[Schema(type: 'integer', description: 'Arsip ID, or 0 for all')]
        int $arsipId = 0
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $sinceDate = $sinceDate ?? date('Y-m-d', strtotime('-7 days'));

        $logs = $this->systemLogModel
            ->where('tgl_transaksi >=', $sinceDate . ' 00:00:00')
            ->where('tabel', 'data_arsip')
            ->where('aksi', 'UPDATE')
            ->orderBy('tgl_transaksi', 'DESC')
            ->findAll();

        if ($arsipId > 0) {
            $logs = array_filter($logs, fn($log) => $log['record_id'] == $arsipId);
        }

        $changes = [];
        foreach ($logs as $log) {
            $changes[] = [
                'log_id'     => $log['id'],
                'arsip_id'   => $log['record_id'],
                'changed_by' => $log['username_transaksi'],
                'changed_at' => $log['tgl_transaksi'],
                'changes'    => json_decode($log['detail'] ?? '{}', true),
                'ip_address' => $log['ip_address'] ?? null,
            ];
        }

        return ['since_date' => $sinceDate, 'total_changes' => count($changes), 'changes' => array_slice($changes, 0, 50)];
    }

    /**
     * Find archives without classification codes.
     */
    #[McpTool(name: 'find_unclassified_archives')]
    public function findUnclassifiedArchives(): array
    {
        $this->accessGuard->requireModule('arsip');

        $unclassified = $this->arsipModel
            ->where('deleted_at', null)
            ->groupStart()
            ->where('kode', '')
            ->orWhere('kode IS NULL', null, false)
            ->groupEnd()
            ->findAll();

        $filtered = $this->accessGuard->filterArsip($unclassified);

        return [
            'count'    => count($filtered),
            'archives' => array_map(fn($a) => [
                'id' => $a['id'], 'noarsip' => $a['noarsip'], 'uraian' => $a['uraian'], 'tanggal' => $a['tanggal'],
            ], $filtered),
            'recommendation' => 'Run suggest_classification on these archives.',
        ];
    }

    /**
     * Monitor for overly broad access permissions.
     */
    #[McpTool(name: 'monitor_access_overreach')]
    public function monitorAccessOverreach(): array
    {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $users = $this->userModel->where('deleted_at', null)->findAll();
        $overreach = [];

        foreach ($users as $user) {
            if ($user['tipe'] === 'admin') continue;

            $aksesKlas  = json_decode($user['akses_klas'], true) ?? [];
            $aksesModul = json_decode($user['akses_modul'], true) ?? [];
            $issues = [];

            if (count($aksesKlas) > 10) {
                $issues[] = 'User has access to ' . count($aksesKlas) . ' classifications (unusually high)';
            }
            if (in_array('*', $aksesKlas)) {
                $issues[] = 'User has wildcard (*) access to all classifications';
            }
            if (in_array('*', $aksesModul)) {
                $issues[] = 'User has wildcard (*) access to all modules';
            }

            if (!empty($issues)) {
                $overreach[] = [
                    'user' => $user['username'], 'akses_klas' => $aksesKlas,
                    'akses_modul' => $aksesModul, 'issues' => $issues,
                ];
            }
        }

        return ['users_checked' => count($users), 'overreach_found' => count($overreach), 'issues' => $overreach];
    }

    /**
     * Trace who accessed or modified a specific archive.
     */
    #[McpTool(name: 'trace_access_history')]
    public function traceAccessHistory(
        #[Schema(type: 'integer', description: 'Arsip ID')]
        int $arsipId,
        #[Schema(type: 'integer', description: 'Max records', minimum: 1, maximum: 200)]
        int $limit = 50
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) return ['status' => 'error', 'message' => 'Arsip not found'];

        $systemLogs = $this->systemLogModel
            ->where('tabel', 'data_arsip')
            ->where('record_id', $arsipId)
            ->orderBy('tgl_transaksi', 'DESC')
            ->limit($limit)
            ->findAll();

        $accessLogs = $this->accessLogModel->getByArsip($arsipId, $limit);

        return [
            'arsip'         => ['id' => $arsip['id'], 'noarsip' => $arsip['noarsip'], 'uraian' => $arsip['uraian']],
            'system_logs'   => $systemLogs,
            'access_logs'   => $accessLogs,
            'total_entries' => count($systemLogs) + count($accessLogs),
        ];
    }

    /**
     * Generate a comprehensive compliance report.
     */
    #[McpTool(name: 'generate_compliance_report')]
    public function generateComplianceReport(
        #[Schema(type: 'string', description: 'Report period start (YYYY-MM-DD)')]
        ?string $dateFrom = null,
        #[Schema(type: 'string', description: 'Report period end (YYYY-MM-DD)')]
        ?string $dateTo = null
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo   = $dateTo ?? date('Y-m-d');

        $completeness = $this->checkMetadataCompleteness(0);
        $unclassified = $this->findUnclassifiedArchives();
        $overreach    = $this->monitorAccessOverreach();
        $activeHolds  = $this->legalHoldModel->getAllActive();
        $pending      = $this->classificationModel->getPendingSuggestions(100);

        return [
            'report_period' => ['from' => $dateFrom, 'to' => $dateTo],
            'generated_at'  => date('Y-m-d H:i:s'),
            'sections' => [
                'metadata_completeness' => [
                    'total_archives'    => $completeness['summary']['total'],
                    'completeness_rate' => $completeness['completeness_rate'],
                    'incomplete_count'  => $completeness['summary']['incomplete'],
                    'top_missing_fields' => $completeness['summary']['missing_fields'],
                ],
                'unclassified'          => ['count' => $unclassified['count']],
                'access_overreach'      => ['users_flagged' => $overreach['overreach_found']],
                'active_legal_holds'    => ['count' => count($activeHolds), 'holds' => $activeHolds],
                'pending_classifications' => ['count' => count($pending)],
            ],
            'recommendations' => $this->generateRecommendations($completeness, $unclassified, $overreach, $activeHolds),
        ];
    }

    private function generateRecommendations(array $completeness, array $unclassified, array $overreach, array $holds): array
    {
        $recs = [];
        if ($completeness['completeness_rate'] < 90) {
            $recs[] = 'Metadata completeness below 90%. Review and complete missing fields.';
        }
        if ($unclassified['count'] > 0) {
            $recs[] = "{$unclassified['count']} archives lack classification codes.";
        }
        if ($overreach['overreach_found'] > 0) {
            $recs[] = "{$overreach['overreach_found']} users have overly broad access.";
        }
        if (count($holds) > 0) {
            $recs[] = count($holds) . ' active legal holds in place.';
        }
        return $recs;
    }
}
