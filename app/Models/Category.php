<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['guid', 'name', 'description', 'image', 'is_active'])]
class Category extends Model
{
    protected $table = 'product.categories';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_guid', 'guid');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
