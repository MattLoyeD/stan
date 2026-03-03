<?php

namespace App\Models;

use App\Enums\SwarmTaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwarmTask extends Model
{
    protected $fillable = [
        'objective_id',
        'role',
        'instructions',
        'goal',
        'allowed_tools',
        'status',
        'token_budget',
        'tokens_used',
        'sequence',
        'depends_on',
        'result',
        'error',
        'llm_provider',
        'llm_model',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SwarmTaskStatus::class,
            'allowed_tools' => 'array',
            'depends_on' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class);
    }

    public function isReady(): bool
    {
        if ($this->status !== SwarmTaskStatus::Pending) {
            return false;
        }

        $dependencies = $this->depends_on ?? [];

        if (empty($dependencies)) {
            return true;
        }

        $completedCount = SwarmTask::whereIn('id', $dependencies)
            ->where('status', SwarmTaskStatus::Completed)
            ->count();

        return $completedCount === count($dependencies);
    }

    public function remainingBudget(): int
    {
        return max(0, $this->token_budget - $this->tokens_used);
    }
}
