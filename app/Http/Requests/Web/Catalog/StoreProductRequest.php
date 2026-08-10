<?php

namespace App\Http\Requests\Web\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Traits\StoresCatalogImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    use StoresCatalogImages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'guid_cabang' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:150', Rule::unique(Product::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
