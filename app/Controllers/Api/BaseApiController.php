<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ApiKeyService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Throttle\Throttler;

/**
 * Base controller for REST API endpoints.
 * Provides common response formatting and authentication helpers.
 */
class BaseApiController extends BaseController
{
    /**
     * HTTP status codes
     */
    protected const HTTP_OK = 200;
    protected const HTTP_CREATED = 201;
    protected const HTTP_BAD_REQUEST = 400;
    protected const HTTP_UNAUTHORIZED = 401;
    protected const HTTP_FORBIDDEN = 403;
    protected const HTTP_NOT_FOUND = 404;
    protected const HTTP_TOO_MANY_REQUESTS = 429;
    protected const HTTP_UNPROCESSABLE_ENTITY = 422;
    protected const HTTP_INTERNAL_ERROR = 500;

    protected ?array $apiKeyRecord = null;

    private ?ApiKeyService $apiKeyService = null;

    /**
     * Return a success response with data.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = self::HTTP_OK
    ): ResponseInterface {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'success' => true,
                'message' => $message,
                'data'    => $data,
            ]);
    }

    /**
     * Return a paginated response with cursor pagination metadata.
     */
    protected function paginatedResponse(
        array $records,
        ?int $nextCursor,
        bool $hasMore,
        string $message = 'Success'
    ): ResponseInterface {
        return $this->response
            ->setStatusCode(self::HTTP_OK)
            ->setJSON([
                'success'     => true,
                'message'     => $message,
                'data'        => $records,
                'pagination'  => [
                    'next_cursor' => $nextCursor,
                    'has_more'    => $hasMore,
                ],
            ]);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(
        string $message,
        int $statusCode = self::HTTP_BAD_REQUEST,
        array $errors = []
    ): ResponseInterface {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $body['errors'] = $errors;
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($body);
    }

    /**
     * Validate X-API-Key header against database and apply rate limiting.
     */
    protected function validateApiKey(): ?ResponseInterface
    {
        $apiKey = $this->request->getHeader('X-API-Key')?->getValue();

        if (empty($apiKey)) {
            return $this->errorResponse(
                'API key is required',
                self::HTTP_UNAUTHORIZED
            );
        }

        $record = $this->getApiKeyService()->validate($apiKey);

        if ($record === null) {
            return $this->errorResponse(
                'Invalid or expired API key',
                self::HTTP_UNAUTHORIZED
            );
        }

        $throttler = service('throttler');
        $limit     = (int) ($record['rate_limit'] ?? 60);
        $key       = 'api_key_' . $record['id'];

        if (! $throttler->check($key, $limit, MINUTE)) {
            return $this->errorResponse(
                'Rate limit exceeded. Try again in ' . $throttler->getTokenTime() . ' seconds.',
                self::HTTP_TOO_MANY_REQUESTS
            );
        }

        $this->apiKeyRecord = $record;
        $this->getApiKeyService()->touchLastUsed((int) $record['id']);

        return null;
    }

    /**
     * Require admin web session for key management endpoints.
     */
    protected function validateAdminSession(): ?ResponseInterface
    {
        $user = $this->getAuthenticatedUser();

        if ($user === null) {
            return $this->errorResponse(
                'Authentication required',
                self::HTTP_UNAUTHORIZED
            );
        }

        if (($user['tipe'] ?? '') !== 'admin') {
            return $this->errorResponse(
                'Admin access required',
                self::HTTP_FORBIDDEN
            );
        }

        return null;
    }

    /**
     * Get the authenticated user from session.
     */
    protected function getAuthenticatedUser(): ?array
    {
        $username = session('username');

        if (empty($username)) {
            return null;
        }

        return [
            'username' => $username,
            'tipe'     => session('tipe'),
        ];
    }

    protected function getApiKeyService(): ApiKeyService
    {
        if ($this->apiKeyService === null) {
            $this->apiKeyService = new ApiKeyService();
        }

        return $this->apiKeyService;
    }
}
