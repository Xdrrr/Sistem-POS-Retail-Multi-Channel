<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\ApiFormRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'order_guid' => ['required', 'string', Rule::exists(Order::class, 'guid')],
            'method' => ['required', 'string', 'in:cash,debit_card,credit_card,qris,transfer,e_wallet'],
            'status' => ['nullable', 'string', 'in:pending,paid,failed,refunded'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
