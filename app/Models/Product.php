<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['guid', 'category_guid', 'group_guid', 'name', 'description', 'price', 'is_active'])]
class Product extends Model
{
    protected $table = 'product.products';

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_guid', 'guid');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'group_guid', 'guid');
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
