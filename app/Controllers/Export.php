<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ArsipModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Export extends BaseController
{
    public function index(): void
    {
        // Redirect to Home::download() for the user-friendly download
        return redirect()->to('/dl');
    }
}
