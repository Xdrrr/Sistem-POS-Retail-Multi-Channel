<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'guid',
    'order_guid',
    'product_guid',
    'product_name',
    'quantity',
    'unit_price',
    'discount_amount',
    'subtotal',
    'notes',
])]
class OrderItem extends Model
{
    protected $table = 'orders.order_items';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_guid', 'guid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_guid', 'guid');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }
}
