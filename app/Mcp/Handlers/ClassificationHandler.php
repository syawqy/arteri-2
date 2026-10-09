<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\AccessGuard;
use App\Mcp\Models\{AiClassificationModel, RetentionScheduleModel};
use App\Models\{ArsipModel, MasterKodeModel};
use PhpMcp\Server\Attributes\{McpTool, Schema};

class ClassificationHandler
{
    private AccessGuard $accessGuard;
    private AiClassificationModel $classificationModel;
    private RetentionScheduleModel $retentionModel;
    private MasterKodeModel $kodeModel;
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->accessGuard        = new AccessGuard();
        $this->classificationModel = new AiClassificationModel();
        $this->retentionModel     = new RetentionScheduleModel();
        $this->kodeModel          = new MasterKodeModel();
        $this->arsipModel         = new ArsipModel();
    }

    /**
     * Suggest archive classification codes based on document content.
     */
    #[McpTool(name: 'suggest_classification')]
    public function suggestClassification(
        #[Schema(type: 'integer', description: 'Arsip ID to classify')]
        int $arsipId,
        #[Schema(type: 'string', description: 'Document content or uraian for context')]
        string $context = ''
    ): array {
        $this->accessGuard->requireModule('arsip');
        $this->accessGuard->requireAuth();

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }

        $allCodes    = $this->kodeModel->where('deleted_at', null)->findAll();
        $content     = !empty($context) ? $context : ($arsip['uraian'] ?? '');
        $suggestions = $this->analyzeForClassification($content, $allCodes);

        $results = [];
        foreach ($suggestions as $suggestion) {
            $classification = [
                'suggested_kode'  => $suggestion['kode'],
                'nama_klas'       => $suggestion['nama'],
                'confidence'      => $suggestion['confidence'],
                'reasoning'       => $suggestion['reasoning'],
                'matched_rules'   => $suggestion['rules'],
            ];

            $retention = $this->retentionModel->getByKode($suggestion['kode']);
            if ($retention) {
                $classification['retention'] = [
                    'retensi_aktif'   => $retention['retensi_aktif'],
                    'retensi_inaktif' => $retention['retensi_inaktif'],
                    'jenis_disposisi' => $retention['jenis_disposisi'],
                    'total_years'     => $this->retentionModel->getTotalRetention($retention),
                ];
            }

            $this->classificationModel->insert([
                'arsip_id'         => $arsipId,
                'suggested_kode'   => $suggestion['kode'],
                'confidence'       => $suggestion['confidence'],
                'reasoning'        => $suggestion['reasoning'],
                'matched_rules'    => json_encode($suggestion['rules']),
                'suggested_series' => $suggestion['series'] ?? null,
                'status'           => 'suggested',
            ]);

            $results[] = $classification;
        }

        return [
            'arsip_id'         => $arsipId,
            'noarsip'          => $arsip['noarsip'],
            'uraian'           => $arsip['uraian'],
            'suggestions'      => $results,
            'total_suggestions' => count($results),
        ];
    }

    /**
     * Match a document against the organization's classification scheme.
     */
    #[McpTool(name: 'match_classification_scheme')]
    public function matchClassificationScheme(
        #[Schema(type: 'string', description: 'Text to match')]
        string $documentText,
        #[Schema(type: 'string', description: 'Current code if any')]
        string $currentKode = ''
    ): array {
        $this->accessGuard->requireModule('arsip');
        $allCodes = $this->kodeModel->where('deleted_at', null)->findAll();
        $matches  = $this->analyzeForClassification($documentText, $allCodes);

        return ['current_kode' => $currentKode, 'matches' => $matches, 'total_matches' => count($matches)];
    }

    /**
     * Suggest archive series for an arsip.
     */
    #[McpTool(name: 'suggest_series')]
    public function suggestSeries(
        #[Schema(type: 'integer', description: 'Arsip ID')]
        int $arsipId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }

        $content = $arsip['uraian'] ?? '';
        $series  = $this->deriveSeries($content);

        return ['arsip_id' => $arsipId, 'noarsip' => $arsip['noarsip'], 'series' => $series];
    }

    /**
     * Get detailed retention schedule for a classification code.
     */
    #[McpTool(name: 'get_retention_schedule')]
    public function getRetentionSchedule(
        #[Schema(type: 'string', description: 'Classification code')]
        string $kodeKlas
    ): array {
        $this->accessGuard->requireModule('arsip');

        $schedule = $this->retentionModel->getByKode($kodeKlas);
        if (!$schedule) {
            return ['status' => 'not_found', 'message' => "No retention schedule for: {$kodeKlas}"];
        }

        return ['schedule' => $schedule, 'total_retention' => $this->retentionModel->getTotalRetention($schedule)];
    }

    /**
     * Get explanation for a classification recommendation.
     */
    #[McpTool(name: 'explain_recommendation')]
    public function explainRecommendation(
        #[Schema(type: 'integer', description: 'Classification ID from ai_classifications')]
        int $classificationId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $classification = $this->classificationModel->find($classificationId);
        if (!$classification) {
            return ['status' => 'error', 'message' => 'Classification not found'];
        }

        $arsip    = $this->arsipModel->getDetail($classification['arsip_id']);
        $schedule = $this->retentionModel->getByKode($classification['suggested_kode']);

        return [
            'classification' => $classification,
            'arsip_context'  => $arsip ? [
                'noarsip'  => $arsip['noarsip'],
                'uraian'   => $arsip['uraian'],
                'pencipta' => $arsip['pencipta'],
            ] : null,
            'retention_rule' => $schedule,
            'reasoning'      => $classification['reasoning'] ?? 'No reasoning provided',
            'matched_rules'  => json_decode($classification['matched_rules'] ?? '[]', true),
        ];
    }

    private function analyzeForClassification(string $content, array $allCodes): array
    {
        $contentLower = mb_strtolower($content);
        $matches = [];

        foreach ($allCodes as $code) {
            $score     = 0.0;
            $rules     = [];
            $codeLower = mb_strtolower($code['nama']);

            $keywords = explode(' ', $code['nama']);
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword) < 3) continue;
                if (str_contains($contentLower, mb_strtolower($keyword))) {
                    $score += 0.25;
                    $rules[] = "Contains keyword: '{$keyword}'";
                }
            }

            if (str_contains($contentLower, 'surat masuk') && str_contains($codeLower, 'masuk')) {
                $score += 0.3; $rules[] = 'Document is an incoming letter';
            }
            if (str_contains($contentLower, 'surat keluar') && str_contains($codeLower, 'keluar')) {
                $score += 0.3; $rules[] = 'Document is an outgoing letter';
            }
            if (str_contains($contentLower, 'keputusan') && str_contains($codeLower, 'keputusan')) {
                $score += 0.3; $rules[] = 'Document is a decision';
            }
            if (str_contains($contentLower, 'perjanjian') && str_contains($codeLower, 'perjanjian')) {
                $score += 0.3; $rules[] = 'Document is an agreement';
            }
            if (str_contains($contentLower, 'laporan') && str_contains($codeLower, 'laporan')) {
                $score += 0.3; $rules[] = 'Document is a report';
            }

            if ($score > 0) {
                $matches[] = [
                    'kode'       => $code['kode'],
                    'nama'       => $code['nama'],
                    'confidence' => min(1.0, $score),
                    'reasoning'  => implode('; ', $rules),
                    'rules'      => $rules,
                    'series'     => $this->deriveSeriesFromCode($code['kode']),
                ];
            }
        }

        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        return array_slice($matches, 0, 5);
    }

    private function deriveSeries(string $content): array
    {
        $c = mb_strtolower($content);
        return match (true) {
            str_contains($c, 'surat masuk')   => ['type' => 'Surat Masuk', 'subseries' => null],
            str_contains($c, 'surat keluar')  => ['type' => 'Surat Keluar', 'subseries' => null],
            str_contains($c, 'keputusan')     => ['type' => 'Keputusan', 'subseries' => null],
            str_contains($c, 'perjanjian')    => ['type' => 'Perjanjian', 'subseries' => null],
            str_contains($c, 'berita acara')  => ['type' => 'Berita Acara', 'subseries' => null],
            str_contains($c, 'nota dinas')    => ['type' => 'Nota Dinas', 'subseries' => null],
            default                            => ['type' => 'Umum', 'subseries' => null],
        };
    }

    private function deriveSeriesFromCode(string $kode): string
    {
        return match (true) {
            str_contains($kode, 'SM') => 'Surat Masuk',
            str_contains($kode, 'SK') => 'Surat Keluar',
            str_contains($kode, 'KP') => 'Keputusan',
            str_contains($kode, 'PJ') => 'Perjanjian',
            str_contains($kode, 'BA') => 'Berita Acara',
            str_contains($kode, 'ND') => 'Nota Dinas',
            str_contains($kode, 'LP') => 'Laporan',
            default                   => 'Umum',
        };
    }
}
