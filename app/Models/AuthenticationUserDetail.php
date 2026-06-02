<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'phone_number',
    'email',
    'full_name',
    'gender',
    'address',
    'additional_address',
    'city',
    'province',
    'date_of_birth',
])]
class AuthenticationUserDetail extends Model
{
    protected $table = 'authentication.user_details';

    public function user(): BelongsTo
    {
        return $this->belongsTo(AuthenticationUser::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'additional_address' => 'array',
            'date_of_birth' => 'date:Y-m-d',
        ];
    }
}
