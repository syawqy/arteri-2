<?php

declare(strict_types=1);

namespace App\Mcp\Services;

class DocumentParser
{
    public function parseFromText(string $text): array
    {
        $metadata    = [];
        $confidences = [];

        $noarsip = $this->extractNoArsip($text);
        if ($noarsip !== null) {
            $metadata['noarsip'] = $noarsip;
            $confidences['noarsip'] = ConfidenceScorer::fieldConfidence($noarsip, 'regex_pattern');
        }

        $tanggal = $this->extractDate($text);
        if ($tanggal !== null) {
            $metadata['tanggal'] = $tanggal;
            $confidences['tanggal'] = ConfidenceScorer::fieldConfidence($tanggal, 'regex_pattern', [
                'matches_known_format' => true,
            ]);
        }

        $pencipta = $this->extractPencipta($text);
        if ($pencipta !== null) {
            $metadata['pencipta'] = $pencipta;
            $confidences['pencipta'] = ConfidenceScorer::fieldConfidence($pencipta, 'heuristic');
        }

        $uraian = $this->extractUraian($text);
        if ($uraian !== null) {
            $metadata['uraian'] = $uraian;
            $confidences['uraian'] = ConfidenceScorer::fieldConfidence($uraian, 'heuristic');
        }

        $unit = $this->extractUnitPengolah($text);
        if ($unit !== null) {
            $metadata['unit_pengolah'] = $unit;
            $confidences['unit_pengolah'] = ConfidenceScorer::fieldConfidence($unit, 'heuristic');
        }

        return [
            'metadata'    => $metadata,
            'confidences' => $confidences,
        ];
    }

    private function extractNoArsip(string $text): ?string
    {
        $patterns = [
            '/\b(\d{1,5}\/[A-Z]{1,10}\/\d{4})\b/',
            '/\b(ARS[-.]?\d{4}[-.]?\d{1,5})\b/i',
            '/Nomor\s*:\s*([^\n]{3,50})/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function extractDate(string $text): ?string
    {
        $patterns = [
            '/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/',
            '/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/',
            '/\b(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\b/i',
        ];
        $months = [
            'januari' => '01', 'februari' => '02', 'maret' => '03',
            'april' => '04', 'mei' => '05', 'juni' => '06',
            'juli' => '07', 'agustus' => '08', 'september' => '09',
            'oktober' => '10', 'november' => '11', 'desember' => '12',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $monthStr = strtolower($matches[2] ?? '');
                if (isset($months[$monthStr])) {
                    return sprintf('%s-%s-%02d', $matches[3], $months[$monthStr], (int) $matches[1]);
                }
                $day   = (int) $matches[1];
                $month = (int) $matches[2];
                $year  = (int) $matches[3];
                if ($month > 12 && $day <= 12) {
                    return sprintf('%04d-%02d-%02d', $year, $day, $month);
                }
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        return null;
    }

    private function extractPencipta(string $text): ?string
    {
        $patterns = [
            '/(?:Dari|Pengirim|Pencipta|Oleh)\s*:\s*([^\n]{2,100})/i',
            '/(?:Dari\s+)(PT\.|CV\.|Kementerian|Dinas|Bagian|Divisi|Sekretariat)\s*([^\n]{2,100})/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function extractUraian(string $text): ?string
    {
        $patterns = [
            '/(?:Perihal|Hal|Subjek|Re)\s*:\s*([^\n]{5,200})/i',
            '/(?:Tentang)\s*:\s*([^\n]{5,200})/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) > 20 && mb_strlen($line) < 200) {
                return $line;
            }
        }
        return null;
    }

    private function extractUnitPengolah(string $text): ?string
    {
        $patterns = [
            '/(?:Unit\s+Pengolah|Bagian|Divisi|Departemen|Sekretariat)\s*:\s*([^\n]{2,100})/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }
}
