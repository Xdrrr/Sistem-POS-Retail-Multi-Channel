<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiFormRequest;

class IndexProductRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:name,sku,description,price,guid_cabang,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
