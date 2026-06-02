<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['guid', 'name', 'description', 'is_active'])]
class ProductGroup extends Model
{
    protected $table = 'product.groups';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'group_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
