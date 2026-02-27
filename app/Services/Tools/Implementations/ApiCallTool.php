<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

class ApiCallTool implements StanToolInterface
{
    public function name(): string
    {
        return 'api_call';
    }

    public function description(): string
    {
        return 'Make an HTTP API call. Supports GET, POST, PUT, PATCH, DELETE methods with custom headers and body.';
    }

    public function handle(Request $request): string
    {
        $method = strtoupper($request->get('method', 'GET'));
        $url = $request->get('url');
        $headers = $request->get('headers', []);
        $body = $request->get('body');

        try {
            $httpRequest = Http::timeout(30)->withHeaders($headers);

            $response = match ($method) {
                'GET' => $httpRequest->get($url),
                'POST' => $httpRequest->post($url, $body ? json_decode($body, true) : []),
                'PUT' => $httpRequest->put($url, $body ? json_decode($body, true) : []),
                'PATCH' => $httpRequest->patch($url, $body ? json_decode($body, true) : []),
                'DELETE' => $httpRequest->delete($url),
                default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            $responseBody = $response->body();
            if (strlen($responseBody) > 50_000) {
                $responseBody = substr($responseBody, 0, 50_000) . "\n... (truncated)";
            }

            return "API {$method} {$url}\nStatus: {$response->status()}\n---\n{$responseBody}";
        } catch (\Exception $e) {
            return "Error calling {$url}: {$e->getMessage()}";
        }
    }

    public function schema($schema): array
    {
        return [
            'url' => $schema->string()->description('The API endpoint URL'),
            'method' => $schema->string()->description('HTTP method: GET, POST, PUT, PATCH, DELETE'),
            'headers' => $schema->string()->description('JSON string of headers'),
            'body' => $schema->string()->description('JSON request body (for POST/PUT/PATCH)'),
        ];
    }

    public function riskLevel(): ToolRiskLevel
    {
        return ToolRiskLevel::Critical;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::Api;
    }

    public function sandboxRequirements(): SandboxConfig
    {
        return new SandboxConfig(networkAccess: true);
    }
}
