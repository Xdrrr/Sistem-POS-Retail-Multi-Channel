<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\ApiFormRequest;

class AdjustInventoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'inventory_guid' => ['required', 'string'],
            'type' => ['required', 'string', 'in:in,out,adjustment'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'reference_type' => ['nullable', 'string', 'in:order,manual_adjustment'],
            'reference_id' => ['nullable', 'string', 'uuid'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
