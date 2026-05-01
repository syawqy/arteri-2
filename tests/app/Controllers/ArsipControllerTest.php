<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ArsipControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGetArsipNewRequiresAuth(): void
    {
        $this->get('arsip/new')->assertRedirectTo('/login');
    }

    public function testPostArsipCreateRequiresAuth(): void
    {
        $this->post('arsip', [
            'noarsip' => 'TEST-001',
            'tanggal' => '2025-01-01',
        ])->assertRedirectTo('/login');
    }
}
