<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'api_client_id',
    'device_id',
    'device_type',
    'fcm_token',
    'ip_address',
    'access_token_hash',
    'refresh_token_hash',
    'access_expires_at',
    'refresh_expires_at',
    'last_used_at',
    'revoked_at',
])]
class ApiToken extends Model
{
    protected $table = 'authentication.api_tokens';

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    public function isRefreshable(): bool
    {
        return is_null($this->revoked_at) && $this->refresh_expires_at?->isFuture();
    }

    public function isUsable(): bool
    {
        return is_null($this->revoked_at) && $this->access_expires_at?->isFuture();
    }

    protected function casts(): array
    {
        return [
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
