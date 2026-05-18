<?php

declare(strict_types=1);

/**
 * API Response Formatter Helper
 *
 * Provides functions for consistent API response formatting.
 */

if (! function_exists('api_success')) {
    /**
     * Format a success response.
     */
    function api_success(mixed $data = null, string $message = 'OK', int $statusCode = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'status'  => $statusCode,
        ];
    }
}

if (! function_exists('api_error')) {
    /**
     * Format an error response.
     */
    function api_error(string $message, int $statusCode = 400, array $errors = []): array
    {
        $response = [
            'success' => false,
            'message' => $message,
            'status'  => $statusCode,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}

if (! function_exists('api_paginated')) {
    /**
     * Format a paginated response.
     */
    function api_paginated(
        array $records,
        ?int $nextCursor = null,
        bool $hasMore = false,
        string $message = 'OK'
    ): array {
        return [
            'success'    => true,
            'message'    => $message,
            'data'       => $records,
            'pagination' => [
                'next_cursor' => $nextCursor,
                'has_more'    => $hasMore,
            ],
            'status' => 200,
        ];
    }
}

if (! function_exists('api_validation_error')) {
    /**
     * Format a validation error response.
     */
    function api_validation_error(array $errors): array
    {
        return [
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $errors,
            'status'  => 422,
        ];
    }
}