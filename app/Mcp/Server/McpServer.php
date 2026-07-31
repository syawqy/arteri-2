<?php

declare(strict_types=1);

namespace App\Mcp\Server;

use App\Mcp\Config\McpConfig;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;

class McpServer
{
    private Server $server;

    private function __construct()
    {
        $this->server = Server::make()
            ->withServerInfo(
                McpConfig::SERVER_NAME,
                McpConfig::SERVER_VERSION
            )
            ->withPaginationLimit(McpConfig::PAGINATION_LIMIT)
            ->build();

        $this->registerElements();
    }

    public static function create(): self
    {
        return new self();
    }

    private function registerElements(): void
    {
        $basePath = McpConfig::getBasePath();

        $this->server->discover(
            basePath: $basePath,
            scanDirs: McpConfig::SCAN_DIRS,
            excludeDirs: McpConfig::EXCLUDE_DIRS,
            saveToCache: true
        );
    }

    public function listenStdio(): void
    {
        $transport = new StdioServerTransport();
        $this->server->listen($transport);
    }

    public function getServer(): Server
    {
        return $this->server;
    }
}
