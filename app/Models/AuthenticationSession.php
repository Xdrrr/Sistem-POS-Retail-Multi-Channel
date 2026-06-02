<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['guid', 'user_id', 'api_token_id', 'last_login_at'])]
class AuthenticationSession extends Model
{
    protected $table = 'authentication.authentications';

    public function user(): BelongsTo
    {
        return $this->belongsTo(AuthenticationUser::class, 'user_id');
    }

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class, 'api_token_id');
    }

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }
}
