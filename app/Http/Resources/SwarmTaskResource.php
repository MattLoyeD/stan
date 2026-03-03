<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SwarmTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'objective_id' => $this->objective_id,
            'role' => $this->role,
            'instructions' => $this->instructions,
            'goal' => $this->goal,
            'allowed_tools' => $this->allowed_tools,
            'status' => $this->status,
            'token_budget' => $this->token_budget,
            'tokens_used' => $this->tokens_used,
            'sequence' => $this->sequence,
            'depends_on' => $this->depends_on,
            'result' => $this->result,
            'error' => $this->error,
            'llm_provider' => $this->llm_provider,
            'llm_model' => $this->llm_model,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
