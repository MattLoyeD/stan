<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionMessage extends Model
{
    protected $fillable = [
        'coding_session_id',
        'role',
        'content',
        'tool_calls',
        'tool_results',
        'input_tokens',
        'output_tokens',
    ];

    protected function casts(): array
    {
        return [
            'tool_calls' => 'array',
            'tool_results' => 'array',
        ];
    }

    public function codingSession(): BelongsTo
    {
        return $this->belongsTo(CodingSession::class);
    }

    public function toolExecutions(): HasMany
    {
        return $this->hasMany(ToolExecution::class);
    }
}
