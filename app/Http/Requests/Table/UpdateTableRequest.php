<?php

namespace App\Http\Requests\Table;

use App\Http\Requests\ApiFormRequest;
use App\Models\RestaurantTable;
use Illuminate\Validation\Rule;

class UpdateTableRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $table = RestaurantTable::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(RestaurantTable::class, 'guid')],
            'table_number' => ['nullable', 'string', 'max:30', Rule::unique(RestaurantTable::class, 'table_number')->ignore($table?->id)],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'status' => ['nullable', 'string', 'in:available,occupied,reserved,maintenance'],
            'guid_cabang' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
