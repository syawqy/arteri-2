<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

class SearchPrompts
{
    #[McpPrompt(name: 'search_archives')]
    public function searchArchives(
        #[Schema(type: 'string', description: 'Type of archive to search for')]
        string $archiveType,
        #[Schema(type: 'string', description: 'Date constraint')]
        string $dateConstraint = '',
        #[Schema(type: 'string', description: 'Additional criteria')]
        string $additionalCriteria = ''
    ): array {
        $query = "Temukan seluruh {$archiveType}";
        if ($dateConstraint) $query .= " {$dateConstraint}";
        if ($additionalCriteria) $query .= " {$additionalCriteria}";

        return [['role' => 'user', 'content' => $query]];
    }
}
