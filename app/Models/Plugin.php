<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plugin extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'version',
        'source',
        'description',
        'required_permissions',
        'signature',
        'is_active',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'required_permissions' => 'array',
            'is_active' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromRegistry(): bool
    {
        return $this->source === 'registry';
    }
}
