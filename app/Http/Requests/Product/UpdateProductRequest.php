<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiFormRequest;
use App\Models\Cabang;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Traits\StoresCatalogImages;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends ApiFormRequest
{
    use StoresCatalogImages;

    public function rules(): array
    {
        $product = Product::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique(Product::class, 'sku')->ignore($product?->id)],
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:150', Rule::unique(Product::class, 'name')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'price' => ['nullable', 'numeric', 'min:0'],
            'guid_cabang' => ['nullable', 'string', Rule::exists(Cabang::class, 'guid')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
