<?php

declare(strict_types=1);

namespace App\Mcp\Handlers;

use App\Mcp\Services\{AccessGuard, OcrService};
use App\Models\ArsipModel;
use PhpMcp\Server\Attributes\{McpTool, Schema};

class SearchHandler
{
    private AccessGuard $accessGuard;
    private ArsipModel $arsipModel;
    private OcrService $ocrService;

    public function __construct()
    {
        $this->accessGuard = new AccessGuard();
        $this->arsipModel  = new ArsipModel();
        $this->ocrService  = new OcrService();
    }

    /**
     * Search archives using natural language queries.
     */
    #[McpTool(name: 'natural_language_search')]
    public function naturalLanguageSearch(
        #[Schema(type: 'string', description: 'Natural language search query')]
        string $query,
        #[Schema(type: 'integer', description: 'Max results', minimum: 1, maximum: 100)]
        int $limit = 20,
        #[Schema(type: 'string', description: 'Cursor for pagination')]
        ?string $cursor = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $parsed    = $this->parseQuery($query);
        $cursorId  = $cursor !== null ? (int) $cursor : null;
        $result    = $this->arsipModel->searchWithCursor($cursorId, $parsed['keywords'], $parsed['filters'], $limit);
        $filtered  = $this->accessGuard->filterArsip($result['records']);

        $enrichedResults = [];
        foreach ($filtered as $record) {
            $enrichedResults[] = [
                'arsip'    => $record,
                'sources'  => $this->getSourceDocuments($record),
                'passages' => $this->getRelevantPassages($record, $parsed['keywords']),
            ];
        }

        return [
            'query'         => $query,
            'parsed_query'  => $parsed,
            'results'       => $enrichedResults,
            'total_found'   => count($filtered),
            'has_more'      => $result['has_more'],
            'next_cursor'   => $result['next_cursor'] ? (string) $result['next_cursor'] : null,
            'access_note'   => 'Results filtered based on your access permissions.',
        ];
    }

    /**
     * Get detailed results with source documents and supporting passages.
     */
    #[McpTool(name: 'get_search_results_with_sources')]
    public function getSearchResultsWithSources(
        #[Schema(type: 'integer', description: 'Arsip ID')]
        int $arsipId,
        #[Schema(type: 'string', description: 'Original query for context')]
        string $query = ''
    ): array {
        $this->accessGuard->requireModule('arsip');

        $arsip = $this->arsipModel->getDetail($arsipId);
        if (!$arsip) {
            return ['status' => 'error', 'message' => 'Arsip not found'];
        }
        if (!$this->accessGuard->canAccessArsip($arsip)) {
            return ['status' => 'error', 'message' => 'Access denied'];
        }

        return [
            'arsip'          => $arsip,
            'sources'        => $this->getSourceDocuments($arsip),
            'passages'       => $this->getRelevantPassages($arsip, $query),
            'file_available' => !empty($arsip['file']),
        ];
    }

    /**
     * List all archives accessible to the current user.
     */
    #[McpTool(name: 'list_accessible_archives')]
    public function listAccessibleArchives(
        #[Schema(type: 'integer', description: 'Max results', minimum: 1, maximum: 100)]
        int $limit = 20,
        #[Schema(type: 'string', description: 'Cursor')]
        ?string $cursor = null,
        #[Schema(type: 'string', description: 'Filter by classification code')]
        ?string $kode = null,
        #[Schema(type: 'string', description: 'Date from (YYYY-MM-DD)')]
        ?string $dateFrom = null,
        #[Schema(type: 'string', description: 'Date end (YYYY-MM-DD)')]
        ?string $dateTo = null
    ): array {
        $this->accessGuard->requireModule('arsip');

        $cursorId = $cursor !== null ? (int) $cursor : null;
        $filters  = array_filter([
            'kode'    => $kode,
            'tanggal' => $dateFrom ? ($dateTo ? "{$dateFrom},{$dateTo}" : $dateFrom) : null,
        ], fn($v) => $v !== null);

        $result   = $this->arsipModel->searchWithCursor($cursorId, '', $filters, $limit);
        $filtered = $this->accessGuard->filterArsip($result['records']);

        return [
            'archives'     => $filtered,
            'count'        => count($filtered),
            'has_more'     => $result['has_more'],
            'next_cursor'  => $result['next_cursor'] ? (string) $result['next_cursor'] : null,
        ];
    }

    private function parseQuery(string $query): array
    {
        $keywords = $query;
        $filters  = [];

        if (preg_match('/tahun\s+(\d{4})/i', $query, $m)) {
            $filters['tanggal'] = "{$m[1]}-01-01,{$m[1]}-12-31";
            $keywords = str_replace($m[0], '', $keywords);
        }
        if (preg_match('/sebelum\s+(\d{4})/i', $query, $m)) {
            $filters['tanggal'] = ",{$m[1]}-12-31";
            $keywords = str_replace($m[0], '', $keywords);
        }
        if (preg_match('/setelah\s+(\d{4})/i', $query, $m)) {
            $filters['tanggal'] = "{$m[1]}-01-01,";
            $keywords = str_replace($m[0], '', $keywords);
        }

        $classMap = [
            'surat masuk'  => 'SM', 'surat keluar' => 'SK', 'keputusan' => 'KP',
            'perjanjian'   => 'PJ', 'laporan'      => 'LP', 'nota dinas' => 'ND',
            'berita acara' => 'BA',
        ];
        foreach ($classMap as $term => $code) {
            if (str_contains(mb_strtolower($query), $term)) {
                $filters['kode'] = $code;
                $keywords = str_ireplace($term, '', $keywords);
                break;
            }
        }

        $stopWords = ['yang','dan','di','ke','dari','untuk','dengan','adalah','ini','itu',
                       'pada','akan','telah','sudah','belum','semua','temukan','cari','daftar'];
        $keywords = preg_replace('/\b(' . implode('|', $stopWords) . ')\b/i', '', $keywords);
        $keywords = trim(preg_replace('/\s+/', ' ', $keywords));

        return ['keywords' => $keywords, 'filters' => $filters, 'original' => $query];
    }

    private function getSourceDocuments(array $arsip): array
    {
        $sources = [];
        if (!empty($arsip['file'])) {
            $sources[] = ['type' => 'uploaded_file', 'name' => basename($arsip['file']), 'path' => $arsip['file']];
        }
        $sources[] = ['type' => 'database_record', 'name' => $arsip['noarsip'], 'id' => $arsip['id']];
        return $sources;
    }

    private function getRelevantPassages(array $arsip, string $query): array
    {
        $passages = [];
        if (!empty($arsip['uraian'])) {
            $passages[] = ['source' => 'uraian', 'content' => $arsip['uraian']];
        }
        return $passages;
    }
}
