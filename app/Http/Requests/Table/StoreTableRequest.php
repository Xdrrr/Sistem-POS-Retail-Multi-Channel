<?php

namespace App\Http\Requests\Table;

use App\Http\Requests\ApiFormRequest;
use App\Models\RestaurantTable;
use Illuminate\Validation\Rule;

class StoreTableRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'table_number' => ['required', 'string', 'max:30', Rule::unique(RestaurantTable::class, 'table_number')],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'status' => ['nullable', 'string', 'in:available,maintenance'],
            'guid_cabang' => ['nullable', 'string'],
        ];
    }
}
