<?php

declare(strict_types=1);

namespace App\Mcp\Config;

class McpConfig
{
    public const SERVER_NAME = 'Arteri Archive Management';
    public const SERVER_VERSION = '1.0.0';

    public const TRANSPORT_STDIO = 'stdio';
    public const TRANSPORT_HTTP = 'http';

    public const SCAN_DIRS = [
        'Handlers',
        'Resources',
        'Prompts',
    ];

    public const EXCLUDE_DIRS = [
        'Config',
        'Services',
        'Models',
        'Server',
    ];

    public const PAGINATION_LIMIT = 50;

    public static function getBasePath(): string
    {
        return APPPATH . 'Mcp';
    }

    public static function isValidTransport(string $transport): bool
    {
        return in_array($transport, [
            self::TRANSPORT_STDIO,
            self::TRANSPORT_HTTP,
        ], true);
    }

    public static function getHttpConfig(): array
    {
        return [
            'host' => env('MCP_HTTP_HOST', '127.0.0.1'),
            'port' => (int) env('MCP_HTTP_PORT', 8090),
        ];
    }

    public static function getApiKey(): ?string
    {
        $key = env('MCP_API_KEY', null);
        return !empty($key) ? $key : null;
    }
}
