<?php

namespace App\Http\Requests\Web\Table;

use App\Models\RestaurantTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
