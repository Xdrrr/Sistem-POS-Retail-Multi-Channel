<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'guid',
    'product_guid',
    'guid_cabang',
    'unit',
    'current_stock',
    'minimum_stock',
    'is_active',
])]
class ProductInventory extends Model
{
    protected $table = 'product.inventories';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_guid', 'guid');
    }

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
