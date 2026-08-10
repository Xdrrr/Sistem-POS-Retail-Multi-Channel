<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\ApiFormRequest;
use App\Models\Shift;
use Illuminate\Validation\Rule;

class CloseShiftRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'guid' => ['required', 'string', Rule::exists(Shift::class, 'guid')],
            'closed_at' => ['required', 'date'],
            'work_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
