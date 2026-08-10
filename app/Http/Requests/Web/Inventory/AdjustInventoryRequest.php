<?php

namespace App\Http\Requests\Web\Inventory;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'type' => ['required', 'string', 'in:in,out'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
