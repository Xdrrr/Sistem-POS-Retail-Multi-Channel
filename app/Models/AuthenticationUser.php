<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'guid',
    'role_id',
    'username',
    'password',
    'salt',
    'is_active',
    'url_image',
    'fcm_token',
    'last_login',
    'used_trial',
    'is_verified',
])]
class AuthenticationUser extends Model
{
    protected $table = 'authentication.users';

    public function role(): BelongsTo
    {
        return $this->belongsTo(AuthenticationRole::class, 'role_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(AuthenticationUserDetail::class, 'user_id');
    }

    public function authentications(): HasMany
    {
        return $this->hasMany(AuthenticationSession::class, 'user_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login' => 'datetime',
            'used_trial' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }
}
