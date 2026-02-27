<?php

namespace App\Models;

use App\Enums\PermissionLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolPermission extends Model
{
    protected $fillable = [
        'user_id',
        'tool_name',
        'permission_level',
        'allowed_patterns',
        'blocked_patterns',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permission_level' => PermissionLevel::class,
            'allowed_patterns' => 'array',
            'blocked_patterns' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
