<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'project_path' => $this->project_path,
            'status' => $this->status,
            'token_budget' => $this->token_budget,
            'tokens_used' => $this->tokens_used,
            'llm_provider' => $this->llm_provider,
            'llm_model' => $this->llm_model,
            'started_at' => $this->started_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'messages_count' => $this->whenCounted('messages'),
        ];
    }
}
