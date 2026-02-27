<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'tool_calls' => $this->tool_calls,
            'tool_results' => $this->tool_results,
            'input_tokens' => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
