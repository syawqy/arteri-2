<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Export extends BaseController
{
    public function index(): RedirectResponse
    {
        // Redirect to Home::download() for the user-friendly download
        return redirect()->to('/dl');
    }
}
