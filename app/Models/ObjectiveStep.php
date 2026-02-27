<?php

namespace App\Models;

use App\Enums\StepStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjectiveStep extends Model
{
    protected $fillable = [
        'objective_id',
        'sequence',
        'status',
        'reasoning',
        'tool_name',
        'tool_input',
        'tool_output',
        'observation',
        'input_tokens',
        'output_tokens',
        'duration_ms',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'status' => StepStatus::class,
            'tool_input' => 'array',
        ];
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class);
    }

    public function toolExecutions(): HasMany
    {
        return $this->hasMany(ToolExecution::class);
    }

    public function totalTokens(): int
    {
        return $this->input_tokens + $this->output_tokens;
    }
}
