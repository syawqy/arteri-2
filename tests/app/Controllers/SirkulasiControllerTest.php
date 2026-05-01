<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class SirkulasiControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGetSirkulasiRequiresAuth(): void
    {
        $this->get('sirkulasi')->assertRedirectTo('/login');
    }
}
