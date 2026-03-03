<?php

namespace App\Models;

use App\Enums\ObjectiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Objective extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'goal',
        'constraints',
        'allowed_tools',
        'status',
        'token_budget',
        'tokens_used',
        'llm_provider',
        'llm_model',
        'result_summary',
        'is_swarm',
        'swarm_config',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ObjectiveStatus::class,
            'constraints' => 'array',
            'allowed_tools' => 'array',
            'is_swarm' => 'boolean',
            'swarm_config' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ObjectiveStep::class)->orderBy('sequence');
    }

    public function swarmTasks(): HasMany
    {
        return $this->hasMany(SwarmTask::class)->orderBy('sequence');
    }

    public function remainingBudget(): int
    {
        return max(0, $this->token_budget - $this->tokens_used);
    }

    public function isRunning(): bool
    {
        return $this->status === ObjectiveStatus::Running;
    }
}
