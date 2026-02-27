<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'objective_id' => $this->objective_id,
            'sequence' => $this->sequence,
            'status' => $this->status,
            'reasoning' => $this->reasoning,
            'tool_name' => $this->tool_name,
            'tool_input' => $this->tool_input,
            'tool_output' => $this->tool_output,
            'observation' => $this->observation,
            'input_tokens' => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'duration_ms' => $this->duration_ms,
            'error' => $this->error,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
