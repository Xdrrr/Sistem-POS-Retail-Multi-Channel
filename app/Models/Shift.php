<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'guid',
    'user_id',
    'user_guid',
    'guid_cabang',
    'shift_number',
    'opened_at',
    'closed_at',
    'work_hours',
    'opening_balance',
    'closing_balance',
    'expected_balance',
    'difference',
    'notes',
    'status',
])]
class Shift extends Model
{
    protected $table = 'orders.shifts';

    public function user(): BelongsTo
    {
        return $this->belongsTo(AuthenticationUser::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shift_id');
    }

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'work_hours' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'expected_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }
}
