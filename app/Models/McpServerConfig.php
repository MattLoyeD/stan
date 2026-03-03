<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpServerConfig extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'transport',
        'command',
        'args',
        'env',
        'url',
        'api_key',
        'default_risk_level',
        'tool_overrides',
        'is_active',
        'cached_tools',
        'last_connected_at',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'args' => 'array',
            'env' => 'array',
            'tool_overrides' => 'array',
            'cached_tools' => 'array',
            'is_active' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
