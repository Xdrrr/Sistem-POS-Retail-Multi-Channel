<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\ApiFormRequest;

class IndexInventoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_product_guid' => ['nullable', 'boolean'],
            'filter.product_guid' => ['nullable', 'string'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string', 'max:50'],
            'filter.set_unit' => ['nullable', 'boolean'],
            'filter.unit' => ['nullable', 'string', 'max:20'],
            'filter.set_is_active' => ['nullable', 'boolean'],
            'filter.is_active' => ['nullable', 'boolean'],
            'filter.set_low_stock' => ['nullable', 'boolean'],
            'filter.low_stock' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:product_name,guid_cabang,unit,current_stock,minimum_stock,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
