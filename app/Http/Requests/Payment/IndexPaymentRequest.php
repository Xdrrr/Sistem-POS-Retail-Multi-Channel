<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\ApiFormRequest;

class IndexPaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:payment_number,method,status,amount,paid_at,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
