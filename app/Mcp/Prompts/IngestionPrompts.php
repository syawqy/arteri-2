<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

class IngestionPrompts
{
    #[McpPrompt(name: 'batch_ingest')]
    public function batchIngest(
        #[Schema(type: 'string', description: 'Path to folder')]
        string $folderPath
    ): array {
        return [['role' => 'user', 'content' => "Analyze the folder at {$folderPath} and process all documents for ingestion into Arteri."]];
    }
}
