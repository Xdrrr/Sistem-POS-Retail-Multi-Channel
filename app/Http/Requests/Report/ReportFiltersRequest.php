<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\ApiFormRequest;
use App\Services\Reports\ReportQueryFactory;
use Illuminate\Validation\Rule;

class ReportFiltersRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(array_keys(ReportQueryFactory::TYPES))],
            'filter' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC,asc,desc'],
            'format' => ['nullable', 'string', 'in:csv,xlsx'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('type')) {
            $this->merge(['type' => $this->route('type')]);
        }
    }
}
