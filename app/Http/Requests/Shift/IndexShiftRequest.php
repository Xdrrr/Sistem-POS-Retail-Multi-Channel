<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\ApiFormRequest;
use App\Models\AuthenticationUser;
use Illuminate\Validation\Rule;

class IndexShiftRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_status' => ['nullable', 'boolean'],
            'filter.status' => ['nullable', 'string', 'in:open,closed'],
            'filter.set_user_guid' => ['nullable', 'boolean'],
            'filter.user_guid' => ['nullable', 'string', Rule::exists(AuthenticationUser::class, 'guid')],
            'filter.set_from_date' => ['nullable', 'boolean'],
            'filter.from_date' => ['nullable', 'date'],
            'filter.set_to_date' => ['nullable', 'boolean'],
            'filter.to_date' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:shift_number,opened_at,closed_at,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
