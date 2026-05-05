<?php

namespace Tests\App\Filters;

use App\Filters\AuthFilter;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class AuthFilterTest extends CIUnitTestCase
{
    private AuthFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new AuthFilter();
    }

    public function testBeforeWithoutSessionRedirectsToLogin(): void
    {
        $request = Services::request();

        $result = $this->filter->before($request, null);

        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $result);
        $this->assertStringContainsString('/login', $result->getHeaderLine('Location'));
    }

    public function testBeforeWithSessionDoesNotRedirect(): void
    {
        session()->set('username', 'admin');

        $request = Services::request();
        $result = $this->filter->before($request, null);

        $this->assertNull($result);
    }

    public function testAfterDoesNothing(): void
    {
        $request  = Services::request();
        $response = Services::response();

        // Should not throw — void return
        $this->filter->after($request, $response, null);
        $this->assertTrue(true);
    }
}
