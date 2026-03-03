<?php

namespace App\Services\Mcp;

use App\Enums\ToolRiskLevel;
use App\Models\McpServerConfig;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Log;

class McpManager
{
    /** @var array<int, McpClient> */
    private array $clients = [];

    public function __construct(
        private ToolRegistry $toolRegistry,
    ) {}

    public function loadAll(int $userId): void
    {
        $configs = McpServerConfig::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        foreach ($configs as $config) {
            try {
                $this->loadServer($config);
            } catch (\Exception $e) {
                Log::warning("Failed to load MCP server '{$config->name}': {$e->getMessage()}");
            }
        }
    }

    public function loadServer(McpServerConfig $config): array
    {
        $client = new McpClient($config);
        $client->connect();

        $tools = $client->listTools();

        $config->update(['cached_tools' => $tools]);

        $this->clients[$config->id] = $client;

        $registered = [];
        foreach ($tools as $tool) {
            $proxyTool = $this->createProxyTool($client, $config, $tool);
            $this->toolRegistry->register($proxyTool);
            $registered[] = $proxyTool->name();
        }

        return $registered;
    }

    public function disconnectServer(int $configId): void
    {
        if (isset($this->clients[$configId])) {
            $this->clients[$configId]->disconnect();
            unset($this->clients[$configId]);
        }
    }

    public function disconnectAll(): void
    {
        foreach ($this->clients as $client) {
            $client->disconnect();
        }

        $this->clients = [];
    }

    public function getClient(int $configId): ?McpClient
    {
        return $this->clients[$configId] ?? null;
    }

    private function createProxyTool(McpClient $client, McpServerConfig $config, array $tool): McpProxyTool
    {
        $toolName = $tool['name'];
        $overrides = $config->tool_overrides ?? [];
        $riskLevel = ToolRiskLevel::from(
            $overrides[$toolName]['risk_level']
                ?? $config->default_risk_level
                ?? config('stan.mcp.default_risk_level', 'high')
        );

        return new McpProxyTool(
            client: $client,
            serverName: $config->name,
            toolName: $toolName,
            toolDescription: $tool['description'] ?? '',
            inputSchema: $tool['inputSchema'] ?? [],
            riskLevel: $riskLevel,
        );
    }
}
