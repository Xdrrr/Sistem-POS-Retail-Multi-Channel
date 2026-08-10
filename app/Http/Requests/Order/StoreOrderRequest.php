<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiFormRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'order_number' => ['nullable', 'string', 'max:30', Rule::unique(Order::class, 'order_number')],
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
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'string', 'in:cash,debit_card,credit_card,qris,transfer,e_wallet'],
            'payments.*.status' => ['nullable', 'string', 'in:pending,paid,failed,refunded'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.paid_at' => ['nullable', 'date'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }
}
