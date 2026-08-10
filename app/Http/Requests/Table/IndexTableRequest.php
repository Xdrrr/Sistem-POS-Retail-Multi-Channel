<?php

namespace App\Http\Requests\Table;

use App\Http\Requests\ApiFormRequest;

class IndexTableRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_table_number' => ['nullable', 'boolean'],
            'filter.table_number' => ['nullable', 'string', 'max:30'],
            'filter.set_location' => ['nullable', 'boolean'],
            'filter.location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'filter.set_status' => ['nullable', 'boolean'],
            'filter.status' => ['nullable', 'string', 'in:available,occupied,reserved,maintenance'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:table_number,capacity,location,status,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
