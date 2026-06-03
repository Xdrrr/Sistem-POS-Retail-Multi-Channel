<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'guid',
    'order_number',
    'customer_name',
    'customer_phone',
    'table_number',
    'order_type',
    'status',
    'payment_status',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'notes',
    'ordered_at',
])]
class Order extends Model
{
    protected $table = 'orders.orders';

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_guid', 'guid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_guid', 'guid');
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];
    }
}
