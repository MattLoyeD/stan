<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

class WebFetchTool implements StanToolInterface
{
    public function name(): string
    {
        return 'web_fetch';
    }

    public function description(): string
    {
        return 'Fetch content from a URL. Returns the response body (HTML/text will be converted to readable text). Use for reading documentation, APIs, or web pages.';
    }

    public function handle(Request $request): string
    {
        $url = $request->get('url');

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Stan/1.0'])
                ->get($url);

            if (! $response->successful()) {
                return "Error: HTTP {$response->status()} for {$url}";
            }

            $body = $response->body();

            if (strlen($body) > 100_000) {
                $body = substr($body, 0, 100_000) . "\n... (truncated)";
            }

            $contentType = $response->header('Content-Type');
            if (str_contains($contentType, 'text/html')) {
                $body = $this->htmlToText($body);
            }

            return "URL: {$url}\nStatus: {$response->status()}\n---\n{$body}";
        } catch (\Exception $e) {
            return "Error fetching {$url}: {$e->getMessage()}";
        }
    }

    public function schema($schema): array
    {
        return [
            'url' => $schema->string()->description('The URL to fetch'),
        ];
    }

    public function riskLevel(): ToolRiskLevel
    {
        return ToolRiskLevel::Medium;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::Web;
    }

    public function sandboxRequirements(): SandboxConfig
    {
        return new SandboxConfig(networkAccess: true);
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
        $html = preg_replace('/<[^>]+>/', ' ', $html);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }
}
