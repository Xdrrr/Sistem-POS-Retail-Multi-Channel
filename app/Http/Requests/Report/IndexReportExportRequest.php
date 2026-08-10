<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\ApiFormRequest;

class IndexReportExportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
