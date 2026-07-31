<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

class ClassificationPrompts
{
    #[McpPrompt(name: 'classify_archives')]
    public function classifyArchives(
        #[Schema(type: 'string', description: 'Scope: unclassified|low_confidence|all')]
        string $scope = 'unclassified'
    ): array {
        $desc = match ($scope) {
            'unclassified'  => 'Find all archives without classification codes and suggest appropriate codes',
            'low_confidence' => 'Review classification suggestions with low confidence and provide better alternatives',
            default          => 'Review all pending classification suggestions',
        };

        return [['role' => 'user', 'content' => $desc]];
    }
}
