<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class McpServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'transport' => $this->transport,
            'command' => $this->command,
            'args' => $this->args,
            'url' => $this->url,
            'has_api_key' => ! empty($this->api_key),
            'default_risk_level' => $this->default_risk_level,
            'tool_overrides' => $this->tool_overrides,
            'is_active' => $this->is_active,
            'cached_tools' => $this->cached_tools,
            'last_connected_at' => $this->last_connected_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
