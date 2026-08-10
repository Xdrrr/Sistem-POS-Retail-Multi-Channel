<?php

namespace App\Http\Requests\Cabang;

use App\Http\Requests\ApiFormRequest;

class IndexCabangRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:kode,nama,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
