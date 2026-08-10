<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\ApiFormRequest;

class IndexCategoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:name,description,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
