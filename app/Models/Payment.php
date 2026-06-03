<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'guid',
    'order_guid',
    'payment_number',
    'method',
    'status',
    'amount',
    'paid_at',
    'reference_number',
    'notes',
])]
class Payment extends Model
{
    protected $table = 'orders.payments';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_guid', 'guid');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
