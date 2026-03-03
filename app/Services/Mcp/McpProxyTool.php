<?php

namespace App\Services\Mcp;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Laravel\Ai\Tools\Request;

class McpProxyTool implements StanToolInterface
{
    public function __construct(
        private McpClient $client,
        private string $serverName,
        private string $toolName,
        private string $toolDescription,
        private array $inputSchema,
        private ToolRiskLevel $riskLevel,
    ) {}

    public function name(): string
    {
        return "mcp_{$this->serverName}_{$this->toolName}";
    }

    public function description(): string
    {
        return "[MCP:{$this->serverName}] {$this->toolDescription}";
    }

    public function handle(Request $request): string
    {
        $arguments = [];

        foreach ($this->inputSchema['properties'] ?? [] as $key => $prop) {
            $value = $request->get($key);

            if ($value !== null) {
                $arguments[$key] = $value;
            }
        }

        $result = $this->client->callTool($this->toolName, $arguments);

        if (is_string($result)) {
            return $result;
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    public function schema($schema): array
    {
        $properties = [];

        foreach ($this->inputSchema['properties'] ?? [] as $key => $prop) {
            $type = $prop['type'] ?? 'string';
            $desc = $prop['description'] ?? '';

            $properties[$key] = match ($type) {
                'integer', 'number' => $schema->number()->description($desc),
                'boolean' => $schema->boolean()->description($desc),
                'array' => $schema->string()->description($desc),
                default => $schema->string()->description($desc),
            };
        }

        return $properties;
    }

    public function riskLevel(): ToolRiskLevel
    {
        return $this->riskLevel;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::External;
    }

    public function sandboxRequirements(): SandboxConfig
    {
        return new SandboxConfig(networkAccess: true);
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }
}
