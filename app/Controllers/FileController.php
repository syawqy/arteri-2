<?php

namespace App\Controllers;

class FileController extends BaseController
{
    public function serve(string $filename)
    {
        if ($filename !== basename($filename) || str_contains($filename, '..')) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'File tidak ditemukan.']);
        }

        $arsip = \Config\Database::connect()->table('data_arsip a')
            ->select('a.*, k.kode as kode_klasifikasi')
            ->join('master_kode k', 'k.id = a.kode', 'left')
            ->where('a.file', $filename)
            ->where('a.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($arsip === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'File tidak ditemukan.']);
        }

        if (! hasClassificationAccess((string) ($arsip['kode_klasifikasi'] ?? ''))) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'File tidak ditemukan.']);
        }

        $filePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $filename;
        if (! is_file($filePath)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'File tidak ditemukan.']);
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) filesize($filePath))
            ->setHeader('Cache-Control', 'no-store, must-revalidate')
            ->setBody(file_get_contents($filePath));
    }
}
