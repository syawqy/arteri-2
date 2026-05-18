<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UserModel;

/**
 * REST API Controller for Authentication operations.
 */
class AuthController extends BaseApiController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * POST /api/auth/login
     * Authenticate user and return session token.
     */
    public function login(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (empty($data)) {
            return $this->errorResponse(
                'Request body is required',
                self::HTTP_BAD_REQUEST
            );
        }

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($username) || empty($password)) {
            return $this->errorResponse(
                'Username and password are required',
                self::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = $this->userModel->where('username', $username)->first();

        if ($user === null) {
            return $this->errorResponse(
                'Invalid credentials',
                self::HTTP_UNAUTHORIZED
            );
        }

        // Verify password
        if (! password_verify($password, $user['password'])) {
            return $this->errorResponse(
                'Invalid credentials',
                self::HTTP_UNAUTHORIZED
            );
        }

        // Check if user is active
        if (($user['aktif'] ?? 0) !== 1) {
            return $this->errorResponse(
                'Account is not active',
                self::HTTP_FORBIDDEN
            );
        }

        // Set session
        session()->set([
            'username'      => $user['username'],
            'nama'          => $user['nama'] ?? '',
            'role'          => $user['role'] ?? 'user',
            'akses_klas'    => $user['akses_klas'] ?? '',
            'logged_in'     => true,
        ]);

        return $this->successResponse([
            'username' => $user['username'],
            'nama'     => $user['nama'] ?? '',
            'role'     => $user['role'] ?? 'user',
        ], 'Login successful');
    }

    /**
     * POST /api/auth/logout
     * End user session.
     */
    public function logout(): ResponseInterface
    {
        session()->destroy();

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * GET /api/auth/me
     * Get current authenticated user info.
     */
    public function me(): ResponseInterface
    {
        $user = $this->getAuthenticatedUser();

        if ($user === null) {
            return $this->errorResponse(
                'Not authenticated',
                self::HTTP_UNAUTHORIZED
            );
        }

        // Get full user data
        $userData = $this->userModel->where('username', $user['username'])->first();

        if ($userData === null) {
            return $this->errorResponse(
                'User not found',
                self::HTTP_NOT_FOUND
            );
        }

        // Remove password from response
        unset($userData['password']);

        return $this->successResponse($userData, 'User retrieved successfully');
    }

    /**
     * GET /api/auth/check
     * Check if current session is valid.
     */
    public function check(): ResponseInterface
    {
        $user = $this->getAuthenticatedUser();

        return $this->successResponse([
            'authenticated' => $user !== null,
            'user'         => $user,
        ], 'Session check complete');
    }
}