<?php

namespace App\Models;

use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'config',
        'pairing_token',
        'is_active',
        'paired_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'paired_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaired(): bool
    {
        return $this->is_active && $this->paired_at !== null;
    }
}
