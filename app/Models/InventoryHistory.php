<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryHistory extends Model
{
    protected $table = 'product.inventory_history';

    protected $fillable = [
        'guid',
        'inventory_id',
        'product_guid',
        'id_cabang',
        'type',
        'qty',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'notes',
        'is_active',
        'created_by',
        'user_guid_reff',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class, 'inventory_id', 'guid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_guid', 'guid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AuthenticationUser::class, 'created_by', 'guid');
    }

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
        ];
    }
}
