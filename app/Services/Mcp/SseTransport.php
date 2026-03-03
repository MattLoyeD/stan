<?php

namespace App\Services\Mcp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SseTransport implements McpTransport
{
    private bool $connected = false;

    private int $requestId = 0;

    private ?string $sessionId = null;

    public function __construct(
        private string $url,
        private ?string $apiKey = null,
        private int $timeoutMs = 30000,
    ) {}

    public function connect(): void
    {
        $response = $this->sendRequest('initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities' => new \stdClass(),
            'clientInfo' => ['name' => 'stan', 'version' => '1.0.0'],
        ]);

        if (isset($response['result']['protocolVersion'])) {
            $this->connected = true;
        }

        $this->sendNotification('notifications/initialized');
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->sessionId = null;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function sendRequest(string $method, array $params = []): array
    {
        $this->requestId++;

        $body = [
            'jsonrpc' => '2.0',
            'id' => $this->requestId,
            'method' => $method,
            'params' => empty($params) ? new \stdClass() : $params,
        ];

        $request = Http::timeout($this->timeoutMs / 1000)
            ->withHeaders($this->buildHeaders())
            ->post($this->url, $body);

        if (! $request->successful()) {
            throw new RuntimeException("MCP SSE request failed: HTTP {$request->status()}");
        }

        $responseBody = $request->body();

        if ($sessionId = $request->header('Mcp-Session-Id')) {
            $this->sessionId = $sessionId;
        }

        $decoded = json_decode($responseBody, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid JSON response from MCP server');
        }

        return $decoded;
    }

    public function listTools(): array
    {
        $response = $this->sendRequest('tools/list');

        return $response['result']['tools'] ?? [];
    }

    public function callTool(string $name, array $arguments = []): mixed
    {
        $response = $this->sendRequest('tools/call', [
            'name' => $name,
            'arguments' => empty($arguments) ? new \stdClass() : $arguments,
        ]);

        if (isset($response['error'])) {
            throw new RuntimeException("MCP tool error: " . json_encode($response['error']));
        }

        $content = $response['result']['content'] ?? [];

        if (empty($content)) {
            return '';
        }

        if (count($content) === 1 && ($content[0]['type'] ?? '') === 'text') {
            return $content[0]['text'];
        }

        return $content;
    }

    private function sendNotification(string $method, array $params = []): void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => empty($params) ? new \stdClass() : $params,
        ];

        Http::timeout($this->timeoutMs / 1000)
            ->withHeaders($this->buildHeaders())
            ->post($this->url, $body);
    }

    private function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        if ($this->sessionId) {
            $headers['Mcp-Session-Id'] = $this->sessionId;
        }

        return $headers;
    }
}
