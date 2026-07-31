<?php

declare(strict_types=1);

namespace App\Mcp\Services;

class OcrService
{
    public function extractText(string $filePath, string $mimeType): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return match (true) {
            $ext === 'pdf'                                => $this->extractFromPdf($filePath),
            in_array($ext, ['jpg','jpeg','png','tiff','bmp','webp']) => $this->extractFromImage($filePath),
            in_array($ext, ['txt','csv','log'])           => $this->extractFromTextFile($filePath),
            default                                       => throw new \RuntimeException("Unsupported file type for OCR: {$ext}"),
        };
    }

    private function extractFromPdf(string $filePath): array
    {
        if (function_exists('shell_exec') && $this->commandExists('pdftotext')) {
            $escapedPath = escapeshellarg($filePath);
            $output = shell_exec("pdftotext {$escapedPath} - 2>/dev/null");
            if ($output !== null && mb_strlen(trim($output)) > 0) {
                $quality = ConfidenceScorer::ocrQualityScore($output);
                return ['text' => $output, 'quality' => $quality, 'method' => 'pdftotext'];
            }
        }
        if (class_exists(\Imagick::class)) {
            $imagick = new \Imagick();
            $imagick->readImage($filePath);
            $text = $imagick->getText();
            $imagick->destroy();
            $quality = ConfidenceScorer::ocrQualityScore($text);
            return ['text' => $text, 'quality' => $quality, 'method' => 'imagick'];
        }
        return ['text' => '', 'quality' => 0.0, 'method' => 'none'];
    }

    private function extractFromImage(string $filePath): array
    {
        if (function_exists('shell_exec') && $this->commandExists('tesseract')) {
            $escapedPath = escapeshellarg($filePath);
            $output = shell_exec("tesseract {$escapedPath} stdout -l ind+eng 2>/dev/null");
            if ($output !== null) {
                $quality = ConfidenceScorer::ocrQualityScore($output);
                return ['text' => $output, 'quality' => $quality, 'method' => 'tesseract'];
            }
        }
        return ['text' => '', 'quality' => 0.0, 'method' => 'none'];
    }

    private function extractFromTextFile(string $filePath): array
    {
        $text = file_get_contents($filePath);
        if ($text === false) {
            return ['text' => '', 'quality' => 0.0, 'method' => 'read_failed'];
        }
        return ['text' => $text, 'quality' => 1.0, 'method' => 'text_read'];
    }

    private function commandExists(string $command): bool
    {
        $output = shell_exec("which {$command} 2>/dev/null");
        return $output !== null && trim($output) !== '';
    }
}
