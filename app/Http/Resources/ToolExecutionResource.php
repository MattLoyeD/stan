<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tool_name' => $this->tool_name,
            'tool_category' => $this->tool_category,
            'risk_level' => $this->risk_level,
            'input' => $this->input,
            'output' => $this->output,
            'was_sandboxed' => $this->was_sandboxed,
            'was_approved' => $this->was_approved,
            'approval_method' => $this->approval_method,
            'guardian_passed' => $this->guardian_passed,
            'guardian_reason' => $this->guardian_reason,
            'duration_ms' => $this->duration_ms,
            'exit_code' => $this->exit_code,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
