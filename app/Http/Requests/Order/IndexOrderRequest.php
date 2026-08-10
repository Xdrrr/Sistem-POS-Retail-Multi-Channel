<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiFormRequest;

class IndexOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:order_number,customer_name,order_type,status,payment_status,total_amount,ordered_at,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
