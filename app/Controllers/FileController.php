<?php

namespace App\Controllers;

use App\Models\ArsipModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class FileController extends BaseController
{
    public function serve(string $filename)
    {
        $arsipModel = new ArsipModel();
        $arsip = $arsipModel->where('file', $filename)->first();

        if ($arsip === null) {
            throw PageNotFoundException::forPageNotFound('File tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $filename;
        if (! is_file($filePath)) {
            throw PageNotFoundException::forPageNotFound('File tidak ditemukan.');
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
