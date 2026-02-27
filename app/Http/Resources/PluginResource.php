<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
            'source' => $this->source,
            'description' => $this->description,
            'required_permissions' => $this->required_permissions,
            'is_active' => $this->is_active,
            'installed_at' => $this->installed_at?->toIso8601String(),
        ];
    }
}
