<?php

declare(strict_types=1);

namespace App\Mcp\Services;

class ConfidenceScorer
{
    public const VERIFICATION_THRESHOLD = 0.75;

    public static function overallConfidence(array $fieldConfidences): float
    {
        if (empty($fieldConfidences)) {
            return 0.0;
        }
        $total = array_sum($fieldConfidences);
        $count = count($fieldConfidences);
        return round($total / $count, 4);
    }

    public static function needsVerification(float $confidence): bool
    {
        return $confidence < self::VERIFICATION_THRESHOLD;
    }

    public static function getLowConfidenceFields(array $metadata, array $confidences): array
    {
        $lowConfidence = [];
        foreach ($confidences as $field => $score) {
            if (self::needsVerification($score) && isset($metadata[$field])) {
                $lowConfidence[] = [
                    'field'      => $field,
                    'ai_value'   => $metadata[$field],
                    'confidence' => $score,
                ];
            }
        }
        return $lowConfidence;
    }

    public static function ocrQualityScore(string $text, int $originalLength = 0): float
    {
        if (empty($text)) {
            return 0.0;
        }
        $length = mb_strlen($text);
        if ($length < 10) {
            return 0.1;
        }
        $artifactPatterns = [
            '/[^\w\s.,;:!?\-()\/"\'@#\$%&*+=<>[\]{}|\\~`^]/u',
            '/(\w)\1{4,}/',
        ];
        $artifactCount = 0;
        foreach ($artifactPatterns as $pattern) {
            if (preg_match_all($pattern, $text)) {
                $artifactCount++;
            }
        }
        $lengthScore = $originalLength > 0
            ? min(1.0, $length / $originalLength)
            : min(1.0, $length / 500);
        $penalty = $artifactCount * 0.2;
        $score = max(0.0, $lengthScore - $penalty);
        return round(min(1.0, $score), 4);
    }

    public static function fieldConfidence(string $value, string $method, array $context = []): float
    {
        if (empty($value)) {
            return 0.0;
        }
        $baseConfidence = match ($method) {
            'ocr_structured'   => 0.85,
            'ocr_unstructured' => 0.60,
            'regex_pattern'    => 0.90,
            'heuristic'        => 0.70,
            'ai_extraction'    => 0.80,
            'user_input'       => 1.00,
            default            => 0.50,
        };
        if (isset($context['matches_known_format']) && $context['matches_known_format']) {
            $baseConfidence = min(1.0, $baseConfidence + 0.1);
        }
        $valueLength = mb_strlen($value);
        if ($valueLength < 2) {
            $baseConfidence *= 0.7;
        } elseif ($valueLength > 500) {
            $baseConfidence *= 0.9;
        }
        return round(min(1.0, max(0.0, $baseConfidence)), 4);
    }
}
