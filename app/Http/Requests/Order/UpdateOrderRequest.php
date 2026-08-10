<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiFormRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $order = Order::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(Order::class, 'guid')],
            'order_number' => ['nullable', 'string', 'max:30', Rule::unique(Order::class, 'order_number')->ignore($order?->id)],
            'shift_guid' => ['nullable', 'string'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'table_number' => ['nullable', 'string', 'max:30'],
            'order_type' => ['nullable', 'string', 'in:dine_in,takeaway,delivery'],
            'status' => ['nullable', 'string', 'in:draft,open,completed,cancelled'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'ordered_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
