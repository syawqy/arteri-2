<?php

declare(strict_types=1);

namespace App\Mcp\Services;

use App\Models\ArsipModel;

class DuplicateDetector
{
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->arsipModel = new ArsipModel();
    }

    public function findDuplicates(array $metadata, int $excludeId = 0): array
    {
        $candidates = [];

        if (!empty($metadata['noarsip'])) {
            $exactMatch = $this->arsipModel
                ->where('noarsip', $metadata['noarsip'])
                ->where('id !=', $excludeId)
                ->first();
            if ($exactMatch) {
                $candidates[] = [
                    'arsip'      => $exactMatch,
                    'match_type' => 'exact_noarsip',
                    'confidence' => 0.95,
                    'field'      => 'noarsip',
                ];
            }
        }

        if (!empty($metadata['uraian'])) {
            $similarText = $this->arsipModel
                ->like('uraian', $metadata['uraian'])
                ->where('id !=', $excludeId)
                ->limit(5)
                ->get()
                ->getResultArray();
            foreach ($similarText as $record) {
                $similarity = $this->textSimilarity($metadata['uraian'], $record['uraian']);
                if ($similarity > 0.7) {
                    $candidates[] = [
                        'arsip'      => $record,
                        'match_type' => 'similar_uraian',
                        'confidence' => $similarity,
                        'field'      => 'uraian',
                    ];
                }
            }
        }

        if (!empty($metadata['file']) && !empty($metadata['file_hash'])) {
            $sameFile = $this->arsipModel
                ->where('file', $metadata['file'])
                ->where('id !=', $excludeId)
                ->first();
            if ($sameFile) {
                $candidates[] = [
                    'arsip'      => $sameFile,
                    'match_type' => 'same_file',
                    'confidence' => 0.90,
                    'field'      => 'file',
                ];
            }
        }

        if (!empty($metadata['noarsip']) && !empty($metadata['pencipta']) && !empty($metadata['tanggal'])) {
            $combo = $this->arsipModel
                ->where('pencipta', $metadata['pencipta'])
                ->where('tanggal', $metadata['tanggal'])
                ->where('id !=', $excludeId)
                ->get()
                ->getResultArray();
            foreach ($combo as $record) {
                if ($record['noarsip'] !== $metadata['noarsip']) {
                    $candidates[] = [
                        'arsip'      => $record,
                        'match_type' => 'similar_composite',
                        'confidence' => 0.80,
                        'field'      => 'composite',
                    ];
                }
            }
        }

        usort($candidates, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        $seen = [];
        return array_values(array_filter($candidates, function ($candidate) use (&$seen) {
            $key = $candidate['arsip']['id'];
            if (in_array($key, $seen, true)) {
                return false;
            }
            $seen[] = $key;
            return true;
        }));
    }

    private function textSimilarity(string $a, string $b): float
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));
        if ($a === $b) {
            return 1.0;
        }
        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) {
            return 0.0;
        }
        $levenshtein = levenshtein($a, $b);
        return round(1.0 - ($levenshtein / $maxLen), 4);
    }
}
