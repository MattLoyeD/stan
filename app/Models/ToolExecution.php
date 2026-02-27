<?php

namespace App\Models;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolExecution extends Model
{
    protected $fillable = [
        'objective_step_id',
        'session_message_id',
        'tool_name',
        'tool_category',
        'risk_level',
        'input',
        'output',
        'was_sandboxed',
        'was_approved',
        'approval_method',
        'guardian_passed',
        'guardian_reason',
        'duration_ms',
        'exit_code',
    ];

    protected function casts(): array
    {
        return [
            'tool_category' => ToolCategory::class,
            'risk_level' => ToolRiskLevel::class,
            'input' => 'array',
            'was_sandboxed' => 'boolean',
            'was_approved' => 'boolean',
            'guardian_passed' => 'boolean',
        ];
    }

    public function objectiveStep(): BelongsTo
    {
        return $this->belongsTo(ObjectiveStep::class);
    }

    public function sessionMessage(): BelongsTo
    {
        return $this->belongsTo(SessionMessage::class);
    }
}
