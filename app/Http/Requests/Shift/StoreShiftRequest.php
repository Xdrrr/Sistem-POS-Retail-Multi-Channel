<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\ApiFormRequest;

class StoreShiftRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'opened_at' => ['required', 'date'],
            'work_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
