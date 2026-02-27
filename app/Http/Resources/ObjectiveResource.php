<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjectiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'goal' => $this->goal,
            'constraints' => $this->constraints,
            'allowed_tools' => $this->allowed_tools,
            'status' => $this->status,
            'token_budget' => $this->token_budget,
            'tokens_used' => $this->tokens_used,
            'llm_provider' => $this->llm_provider,
            'llm_model' => $this->llm_model,
            'result_summary' => $this->result_summary,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'steps_count' => $this->whenCounted('steps'),
            'steps' => StepResource::collection($this->whenLoaded('steps')),
        ];
    }
}
