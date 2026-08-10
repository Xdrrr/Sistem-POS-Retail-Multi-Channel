<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\ApiFormRequest;

class HistoryInventoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.set_inventory_guid' => ['nullable', 'boolean'],
            'filter.inventory_guid' => ['nullable', 'string'],
            'filter.set_product_guid' => ['nullable', 'boolean'],
            'filter.product_guid' => ['nullable', 'string'],
            'filter.set_type' => ['nullable', 'boolean'],
            'filter.type' => ['nullable', 'string', 'in:in,out,adjustment'],
            'filter.set_reference_type' => ['nullable', 'boolean'],
            'filter.reference_type' => ['nullable', 'string', 'in:order,manual_adjustment'],
            'filter.set_from_date' => ['nullable', 'boolean'],
            'filter.from_date' => ['nullable', 'date'],
            'filter.set_to_date' => ['nullable', 'boolean'],
            'filter.to_date' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:created_at,type,qty,stock_before,stock_after,reference_type'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
