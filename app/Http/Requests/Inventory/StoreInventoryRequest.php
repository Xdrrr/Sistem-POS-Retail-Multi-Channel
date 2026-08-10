<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\ApiFormRequest;
use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'guid_cabang' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:20'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
