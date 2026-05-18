<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

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
    protected const HTTP_UNPROCESSABLE_ENTITY = 422;
    protected const HTTP_INTERNAL_ERROR = 500;

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
     * Validate that the request contains required API key header.
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

        // Validate against stored API keys (implement based on your security requirements)
        // For now, we accept any non-empty key
        // TODO: Implement full API key validation against database

        return null; // Valid
    }

    /**
     * Get the authenticated user from session/token.
     */
    protected function getAuthenticatedUser(): ?array
    {
        $username = session('username');

        if (empty($username)) {
            return null;
        }

        return [
            'username' => $username,
            'role'     => session('role'),
        ];
    }
}