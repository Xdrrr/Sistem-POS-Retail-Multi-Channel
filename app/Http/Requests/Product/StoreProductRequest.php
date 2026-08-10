<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiFormRequest;
use App\Models\Cabang;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Traits\StoresCatalogImages;
use Illuminate\Validation\Rule;

class StoreProductRequest extends ApiFormRequest
{
    use StoresCatalogImages;

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:50', Rule::unique(Product::class, 'sku')],
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:150', Rule::unique(Product::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'price' => ['nullable', 'numeric', 'min:0'],
            'guid_cabang' => ['nullable', 'string', Rule::exists(Cabang::class, 'guid')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
