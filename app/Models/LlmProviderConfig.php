<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmProviderConfig extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'api_key',
        'base_url',
        'default_model',
        'is_default',
        'is_active',
        'extra_config',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'extra_config' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
