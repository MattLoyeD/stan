<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

class WebSearchTool implements StanToolInterface
{
    public function name(): string
    {
        return 'web_search';
    }

    public function description(): string
    {
        return 'Search the web for information. Returns search results with titles, URLs, and snippets.';
    }

    public function handle(Request $request): string
    {
        $query = $request->get('query');

        return "Web search for: {$query}\n\nNote: Web search requires API configuration (e.g., SerpAPI, Tavily). Configure in Settings > Providers.";
    }

    public function schema($schema): array
    {
        return [
            'query' => $schema->string()->description('Search query'),
        ];
    }

    public function riskLevel(): ToolRiskLevel
    {
        return ToolRiskLevel::Low;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::Web;
    }

    public function sandboxRequirements(): SandboxConfig
    {
        return new SandboxConfig(networkAccess: true);
    }
}
