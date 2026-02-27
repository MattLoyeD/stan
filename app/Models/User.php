<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class);
    }

    public function codingSessions(): HasMany
    {
        return $this->hasMany(CodingSession::class);
    }

    public function toolPermissions(): HasMany
    {
        return $this->hasMany(ToolPermission::class);
    }

    public function plugins(): HasMany
    {
        return $this->hasMany(Plugin::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function llmProviderConfigs(): HasMany
    {
        return $this->hasMany(LlmProviderConfig::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }
}
