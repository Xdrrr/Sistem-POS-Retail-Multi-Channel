<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiFormRequest;

class IndexUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_role_name' => ['nullable', 'boolean'],
            'filter.role_name' => ['nullable', 'string'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string'],
            'filter.set_is_active' => ['nullable', 'boolean'],
            'filter.is_active' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:username,full_name,email,role_name,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
