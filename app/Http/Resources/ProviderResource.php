<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'base_url' => $this->base_url,
            'default_model' => $this->default_model,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'has_api_key' => ! empty($this->api_key),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
