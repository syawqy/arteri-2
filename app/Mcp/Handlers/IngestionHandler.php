<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\{AccessGuard, OcrService, DocumentParser, DuplicateDetector, ConfidenceScorer};
use App\Mcp\Models\{AiIngestionQueueModel, AiVerificationQueueModel};
use App\Models\ArsipModel;
use PhpMcp\Server\Attributes\{McpTool, Schema};

class IngestionHandler
{
    private AccessGuard $accessGuard;
    private OcrService $ocrService;
    private DocumentParser $documentParser;
    private DuplicateDetector $duplicateDetector;
    private AiIngestionQueueModel $ingestionModel;
    private AiVerificationQueueModel $verificationModel;

    public function __construct()
    {
        $this->accessGuard      = new AccessGuard();
        $this->ocrService       = new OcrService();
        $this->documentParser   = new DocumentParser();
        $this->duplicateDetector = new DuplicateDetector();
        $this->ingestionModel   = new AiIngestionQueueModel();
        $this->verificationModel = new AiVerificationQueueModel();
    }

    /**
     * Upload and process a new document through the ingestion pipeline.
     * Performs OCR, extracts metadata with confidence scores, checks for duplicates.
     */
    #[McpTool(name: 'ingest_document')]
    public function ingestDocument(
        #[Schema(type: 'string', description: 'Path to the uploaded file')]
        string $filePath,
        #[Schema(type: 'string', description: 'MIME type of the file')]
        string $mimeType,
        #[Schema(type: 'string', description: 'Original filename')]
        string $filename,
        #[Schema(type: 'integer', description: 'File size in bytes')]
        int $fileSize,
        #[Schema(type: 'string', description: 'Username of the uploader')]
        string $uploadedBy
    ): array {
        $this->accessGuard->requireModule('arsip');

        $queueId = $this->ingestionModel->insert([
            'filename'    => $filename,
            'original_path' => $filePath,
            'mime_type'   => $mimeType,
            'file_size'   => $fileSize,
            'status'      => 'processing',
            'uploaded_by' => $uploadedBy,
        ]);

        if (!$queueId) {
            return ['status' => 'error', 'message' => 'Failed to create ingestion queue entry'];
        }

        try {
            $ocrResult = $this->ocrService->extractText($filePath, $mimeType);
            $this->ingestionModel->update($queueId, ['ocr_text' => $ocrResult['text']]);

            $parseResult = $this->documentParser->parseFromText($ocrResult['text']);
            $metadata    = $parseResult['metadata'];
            $confidences = $parseResult['confidences'];

            $ocrQuality = ConfidenceScorer::ocrQualityScore($ocrResult['text']);
            $duplicates = $this->duplicateDetector->findDuplicates($metadata);
            $overallConfidence = ConfidenceScorer::overallConfidence(array_values($confidences));

            $needsVerification = ConfidenceScorer::needsVerification($overallConfidence)
                || $ocrQuality < 0.5
                || !empty($duplicates);

            $status = $needsVerification ? 'queued_verification' : 'completed';

            $lowConfidenceFields = ConfidenceScorer::getLowConfidenceFields($metadata, $confidences);
            foreach ($lowConfidenceFields as $field) {
                $this->verificationModel->insert([
                    'ingestion_id'  => $queueId,
                    'field_name'    => $field['field'],
                    'ai_value'      => $field['ai_value'],
                    'ai_confidence' => $field['confidence'],
                    'status'        => 'pending',
                ]);
            }

            $this->ingestionModel->update($queueId, [
                'raw_metadata' => $metadata,
                'status'       => $status,
                'processed_by' => 'ai_agent',
            ]);

            return [
                'ingestion_id'        => $queueId,
                'status'              => $status,
                'ocr_quality'         => $ocrQuality,
                'ocr_method'          => $ocrResult['method'],
                'extracted_metadata'  => $metadata,
                'confidences'         => $confidences,
                'overall_confidence'  => $overallConfidence,
                'duplicates'          => array_map(fn($d) => [
                    'arsip_id'   => $d['arsip']['id'],
                    'noarsip'    => $d['arsip']['noarsip'],
                    'match_type' => $d['match_type'],
                    'confidence' => $d['confidence'],
                ], $duplicates),
                'needs_verification'  => $needsVerification,
                'low_confidence_fields' => $lowConfidenceFields,
            ];
        } catch (\Throwable $e) {
            $this->ingestionModel->markFailed($queueId, $e->getMessage());
            return ['status' => 'error', 'message' => 'Ingestion failed: ' . $e->getMessage()];
        }
    }

    /**
     * Re-extract metadata from an already-ingested document.
     */
    #[McpTool(name: 'extract_metadata')]
    public function extractMetadata(
        #[Schema(type: 'integer', description: 'The ingestion queue ID')]
        int $ingestionId
    ): array {
        $this->accessGuard->requireModule('arsip');

        $item = $this->ingestionModel->find($ingestionId);
        if (!$item) {
            return ['status' => 'error', 'message' => 'Ingestion item not found'];
        }

        $ocrText     = $item['ocr_text'] ?? '';
        $parseResult = $this->documentParser->parseFromText($ocrText);

        return [
            'ingestion_id'       => $ingestionId,
            'metadata'           => $parseResult['metadata'],
            'confidences'        => $parseResult['confidences'],
            'overall_confidence' => ConfidenceScorer::overallConfidence(
                array_values($parseResult['confidences'])
            ),
        ];
    }

    /**
     * Check if a document is a potential duplicate of existing archives.
     */
    #[McpTool(name: 'detect_duplicates')]
    public function detectDuplicates(
        #[Schema(type: 'string', description: 'Archive number (noarsip)')]
        string $noarsip,
        #[Schema(type: 'string', description: 'Subject/uraian')]
        string $uraian = '',
        #[Schema(type: 'string', description: 'Creator/pencipta')]
        string $pencipta = '',
        #[Schema(type: 'string', description: 'Date in YYYY-MM-DD format')]
        string $tanggal = ''
    ): array {
        $this->accessGuard->requireModule('arsip');

        $metadata = array_filter([
            'noarsip'  => $noarsip,
            'uraian'   => $uraian,
            'pencipta' => $pencipta,
            'tanggal'  => $tanggal,
        ], fn($v) => !empty($v));

        $duplicates = $this->duplicateDetector->findDuplicates($metadata);

        return [
            'duplicates_found' => count($duplicates) > 0,
            'count'            => count($duplicates),
            'duplicates'       => array_map(fn($d) => [
                'arsip_id'   => $d['arsip']['id'],
                'noarsip'    => $d['arsip']['noarsip'],
                'uraian'     => $d['arsip']['uraian'],
                'pencipta'   => $d['arsip']['pencipta'],
                'match_type' => $d['match_type'],
                'confidence' => $d['confidence'],
            ], $duplicates),
        ];
    }

    /**
     * Detect if a scanned document is blank or too poor quality.
     */
    #[McpTool(name: 'detect_scan_quality')]
    public function detectScanQuality(
        #[Schema(type: 'string', description: 'Path to the file')]
        string $filePath,
        #[Schema(type: 'string', description: 'MIME type')]
        string $mimeType
    ): array {
        $this->accessGuard->requireModule('arsip');

        try {
            $ocrResult  = $this->ocrService->extractText($filePath, $mimeType);
            $quality    = $ocrResult['quality'];
            $textLength = mb_strlen(trim($ocrResult['text']));
            $isBlank    = $textLength < 5;
            $isPoor     = $quality < 0.3 && !$isBlank;

            $verdict = match (true) {
                $isBlank  => 'blank',
                $isPoor   => 'poor_quality',
                default   => 'readable',
            };

            return [
                'quality_score'   => $quality,
                'ocr_method'      => $ocrResult['method'],
                'text_length'     => $textLength,
                'verdict'         => $verdict,
                'is_blank'        => $isBlank,
                'is_poor_quality' => $isPoor,
                'recommendation'  => match ($verdict) {
                    'blank'        => 'Document appears blank. Verify the file is not corrupted.',
                    'poor_quality' => 'OCR quality is poor. Consider re-scanning at higher resolution.',
                    default        => 'Document is readable and suitable for processing.',
                },
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Quality check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Suggest metadata for a document based on its content and existing patterns.
     */
    #[McpTool(name: 'suggest_metadata')]
    public function suggestMetadata(
        #[Schema(type: 'string', description: 'OCR text or document content')]
        string $documentText
    ): array {
        $this->accessGuard->requireModule('arsip');

        $parseResult = $this->documentParser->parseFromText($documentText);
        $masterData  = $this->matchMasterData($parseResult['metadata']);

        return [
            'suggested_metadata'  => array_merge($parseResult['metadata'], $masterData),
            'confidences'         => $parseResult['confidences'],
            'overall_confidence'  => ConfidenceScorer::overallConfidence(
                array_values($parseResult['confidences'])
            ),
        ];
    }

    /**
     * Group related documents based on metadata similarity.
     */
    #[McpTool(name: 'group_related_documents')]
    public function groupRelatedDocuments(
        #[Schema(type: 'array', description: 'List of arsip IDs to analyze')]
        array $arsipIds
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsipModel = new ArsipModel();
        $records    = [];

        foreach ($arsipIds as $id) {
            $detail = $arsipModel->getDetail($id);
            if ($detail && $this->accessGuard->canAccessArsip($detail)) {
                $records[] = $detail;
            }
        }

        $groups = [
            'by_noarsip_prefix' => $this->groupByPrefix($records, 'noarsip', '/'),
            'by_pencipta'       => $this->groupByField($records, 'pencipta'),
            'by_unit_pengolah'  => $this->groupByField($records, 'unit_pengolah'),
            'by_kode'           => $this->groupByField($records, 'kode'),
        ];

        return ['total_documents' => count($records), 'groups' => $groups];
    }

    /**
     * Get items in the human verification queue.
     */
    #[McpTool(name: 'get_verification_queue')]
    public function getVerificationQueue(
        #[Schema(type: 'integer', description: 'Max items', minimum: 1, maximum: 100)]
        int $limit = 20
    ): array {
        $this->accessGuard->requireModule('arsip');
        $items = $this->verificationModel->getAllPending($limit);
        return ['count' => count($items), 'items' => $items];
    }

    private function matchMasterData(array $metadata): array
    {
        $suggestions = [];
        if (!empty($metadata['noarsip'])) {
            $parts = explode('/', $metadata['noarsip']);
            if (count($parts) >= 2) {
                $suggestions['suggested_kode'] = $parts[1];
            }
        }
        return $suggestions;
    }

    private function groupByPrefix(array $records, string $field, string $delimiter): array
    {
        $groups = [];
        foreach ($records as $record) {
            $parts  = explode($delimiter, $record[$field] ?? '');
            $prefix = $parts[0] ?? 'unknown';
            $groups[$prefix][] = $record['id'];
        }
        return $groups;
    }

    private function groupByField(array $records, string $field): array
    {
        $groups = [];
        foreach ($records as $record) {
            $value = $record[$field] ?? 'unknown';
            $groups[$value][] = $record['id'];
        }
        return $groups;
    }
}
