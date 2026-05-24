<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\ApiKeyService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Admin API key management (session auth, not API key).
 */
class ApiKeyController extends BaseApiController
{
    private ApiKeyService $apiKeyService;

    public function __construct()
    {
        $this->apiKeyService = new ApiKeyService();
    }

    /**
     * GET /api/v1/admin/api-keys
     */
    public function index(): ResponseInterface
    {
        if ($error = $this->validateAdminSession()) {
            return $error;
        }

        return $this->successResponse(
            $this->apiKeyService->listKeys(),
            'API keys retrieved'
        );
    }

    /**
     * POST /api/v1/admin/api-keys
     */
    public function create(): ResponseInterface
    {
        if ($error = $this->validateAdminSession()) {
            return $error;
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return $this->errorResponse(
                'Name is required',
                self::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $rateLimit = (int) ($data['rate_limit'] ?? 60);
        $expiresAt = ! empty($data['expires_at']) ? (string) $data['expires_at'] : null;

        $user     = $this->getAuthenticatedUser();
        $username = $user['username'] ?? 'unknown';

        $result = $this->apiKeyService->generate($name, $username, $rateLimit, $expiresAt);

        return $this->successResponse([
            'id'         => $result['record']['id'],
            'name'       => $result['record']['name'],
            'key_prefix' => $result['record']['key_prefix'],
            'api_key'    => $result['plain_key'],
            'rate_limit' => $result['record']['rate_limit'],
            'expires_at' => $result['record']['expires_at'],
            'message'    => 'Store this API key securely; it will not be shown again.',
        ], 'API key created', self::HTTP_CREATED);
    }

    /**
     * DELETE /api/v1/admin/api-keys/(:num)
     */
    public function revoke(int $id): ResponseInterface
    {
        if ($error = $this->validateAdminSession()) {
            return $error;
        }

        if ($id < 1) {
            return $this->errorResponse('Invalid API key id', self::HTTP_BAD_REQUEST);
        }

        if (! $this->apiKeyService->revoke($id)) {
            return $this->errorResponse('API key not found', self::HTTP_NOT_FOUND);
        }

        return $this->successResponse(null, 'API key revoked');
    }
}
