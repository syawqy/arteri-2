<?php

declare(strict_types=1);

namespace App\Commands;

use App\Mcp\Config\McpConfig;
use App\Mcp\Server\McpServer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class McpServe extends BaseCommand
{
    protected $group       = 'MCP';
    protected $name        = 'mcp:serve';
    protected $description = 'Start the Arteri MCP server for AI assistant integration';

    protected $usage    = 'mcp:serve [--transport=stdio|http] [--port=8090]';
    protected $options  = [
        '--transport' => 'Transport type: stdio (default) or http',
        '--port'      => 'Port for HTTP transport (default: 8090)',
    ];

    public function run(array $params)
    {
        $transport = $params['transport'] ?? 'stdio';
        $port      = (int) ($params['port'] ?? 8090);

        if (!McpConfig::isValidTransport($transport)) {
            CLI::error("Invalid transport: {$transport}. Use 'stdio' or 'http'.");
            return;
        }

        $this->printBanner($transport, $port);

        $mcpServer = McpServer::create();

        if ($transport === McpConfig::TRANSPORT_HTTP) {
            $this->startHttpTransport($mcpServer, $port);
        } else {
            $mcpServer->listenStdio();
        }
    }

    private function printBanner(string $transport, int $port): void
    {
        CLI::write('╔══════════════════════════════════════════╗', 'cyan');
        CLI::write('║     Arteri MCP Server v' . McpConfig::SERVER_VERSION . '              ║', 'cyan');
        CLI::write('║     Transport: ' . strtoupper($transport) . str_repeat(' ', 23 - strlen($transport)) . '║', 'cyan');
        CLI::write('╚══════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        if ($transport === McpConfig::TRANSPORT_HTTP) {
            $host = McpConfig::getHttpConfig()['host'];
            CLI::write("Listening on http://{$host}:{$port}/mcp", 'green');
        } else {
            CLI::write('Listening on stdio (JSON-RPC over stdin/stdout)', 'green');
        }
        CLI::newLine();
    }

    private function startHttpTransport(McpServer $mcpServer, int $port): void
    {
        $config = McpConfig::getHttpConfig();
        $host   = $config['host'];

        $transport = new \PhpMcp\Server\Transports\StreamableHttpServerTransport(
            host: $host,
            port: $port,
            mcpPath: '/mcp'
        );

        $mcpServer->getServer()->listen($transport);
    }
}
