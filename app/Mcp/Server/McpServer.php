<?php

declare(strict_types=1);

namespace App\Mcp\Server;

use App\Mcp\Config\McpConfig;
use App\Mcp\Handlers\{IngestionHandler, ClassificationHandler, SearchHandler, RetentionHandler, ComplianceHandler, MigrationHandler};
use App\Mcp\Resources\{ArsipResource, SystemResource};
use App\Mcp\Prompts\{SearchPrompts, IngestionPrompts, ClassificationPrompts};
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;
use Psr\Container\ContainerInterface;

class McpServer
{
    private Server $server;

    private function __construct()
    {
        $container = $this->createContainer();

        $builder = Server::make()
            ->withServerInfo(
                McpConfig::SERVER_NAME,
                McpConfig::SERVER_VERSION
            )
            ->withPaginationLimit(McpConfig::PAGINATION_LIMIT)
            ->withContainer($container);

        $builder = $this->registerTools($builder);
        $builder = $this->registerResources($builder);
        $builder = $this->registerPrompts($builder);

        $this->server = $builder->build();
    }

    public static function create(): self
    {
        return new self();
    }

    private function createContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            private array $instances = [];

            public function get(string $id)
            {
                if (!isset($this->instances[$id])) {
                    $this->instances[$id] = new $id();
                }
                return $this->instances[$id];
            }

            public function has(string $id): bool
            {
                return class_exists($id);
            }
        };
    }

    private function registerTools($builder): object
    {
        $handlers = [
            IngestionHandler::class,
            ClassificationHandler::class,
            SearchHandler::class,
            RetentionHandler::class,
            ComplianceHandler::class,
            MigrationHandler::class,
        ];

        foreach ($handlers as $handlerClass) {
            $rc = new \ReflectionClass($handlerClass);
            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $rc->getName()) {
                    continue;
                }
                foreach ($method->getAttributes(\PhpMcp\Server\Attributes\McpTool::class) as $attr) {
                    $tool = $attr->newInstance();
                    $builder = $builder->withTool(
                        handler: [$handlerClass, $method->getName()],
                        name: $tool->name ?? $method->getName(),
                        description: $tool->description ?? $this->extractDescription($method),
                        annotations: $tool->annotations
                    );
                }
            }
        }

        return $builder;
    }

    private function registerResources($builder): object
    {
        $resourceHandlers = [ArsipResource::class, SystemResource::class];
        foreach ($resourceHandlers as $handlerClass) {
            $rc = new \ReflectionClass($handlerClass);
            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $rc->getName()) {
                    continue;
                }
                foreach ($method->getAttributes(\PhpMcp\Server\Attributes\McpResource::class) as $attr) {
                    $resource = $attr->newInstance();
                    $builder = $builder->withResource(
                        handler: [$handlerClass, $method->getName()],
                        uri: $resource->uri,
                        name: $resource->name ?? $method->getName(),
                        description: $resource->description ?? $this->extractDescription($method),
                        mimeType: $resource->mimeType
                    );
                }
                foreach ($method->getAttributes(\PhpMcp\Server\Attributes\McpResourceTemplate::class) as $attr) {
                    $template = $attr->newInstance();
                    $builder = $builder->withResourceTemplate(
                        handler: [$handlerClass, $method->getName()],
                        uriTemplate: $template->uriTemplate,
                        name: $template->name ?? $method->getName(),
                        description: $template->description ?? $this->extractDescription($method),
                        mimeType: $template->mimeType
                    );
                }
            }
        }

        return $builder;
    }

    private function registerPrompts($builder): object
    {
        $promptHandlers = [SearchPrompts::class, IngestionPrompts::class, ClassificationPrompts::class];
        foreach ($promptHandlers as $handlerClass) {
            $rc = new \ReflectionClass($handlerClass);
            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $rc->getName()) {
                    continue;
                }
                foreach ($method->getAttributes(\PhpMcp\Server\Attributes\McpPrompt::class) as $attr) {
                    $prompt = $attr->newInstance();
                    $builder = $builder->withPrompt(
                        handler: [$handlerClass, $method->getName()],
                        name: $prompt->name ?? $method->getName(),
                        description: $prompt->description ?? $this->extractDescription($method)
                    );
                }
            }
        }

        return $builder;
    }

    private function extractDescription(\ReflectionMethod $method): string
    {
        $doc = $method->getDocComment();
        if ($doc) {
            if (preg_match('/\/\*\*\s*\n?\s*\*\s*(.+?)(?:\n|\*\/)/', $doc, $m)) {
                return trim($m[1]);
            }
        }
        return $method->getName();
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
