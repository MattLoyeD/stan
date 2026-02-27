<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodingSession extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'project_path',
        'status',
        'token_budget',
        'tokens_used',
        'llm_provider',
        'llm_model',
        'sandbox_config',
        'started_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'sandbox_config' => 'array',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SessionMessage::class)->orderBy('created_at');
    }

    public function remainingBudget(): int
    {
        return max(0, $this->token_budget - $this->tokens_used);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
