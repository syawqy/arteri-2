<?php

namespace App\TestTraits;

use CodeIgniter\Test\FeatureTestTrait;

/**
 * Provides CSRF token for Feature tests.
 */
trait CsrfTestTrait
{
    protected ?string $csrfToken = null;
    protected ?string $csrfCookie = null;

    protected function setupCsrf(): void
    {
        $result = $this->get('/login');
        $body = (string) $result->getBody();

        if (preg_match('/name="([^"]+)"[^>]+value="([^"]+)"/', $body, $m)) {
            $this->csrfToken = $m[2];
            $this->csrfCookie = $m[1];
        }
    }

    protected function csrfPost(string $url, array $data = []): \CodeIgniter\Test\TestResponse
    {
        $data[$this->csrfCookie] = $this->csrfToken;
        return $this->post($url, $data);
    }
}
