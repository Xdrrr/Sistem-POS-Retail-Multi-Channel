<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\ApiFormRequest;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'guid' => ['required', 'string', Rule::exists(ProductInventory::class, 'guid')],
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'guid_cabang' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:20'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
