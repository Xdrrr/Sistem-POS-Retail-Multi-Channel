<?php

namespace App\Http\Requests\Cabang;

use App\Http\Requests\ApiFormRequest;
use App\Models\Cabang;
use Illuminate\Validation\Rule;

class StoreCabangRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', Rule::unique(Cabang::class, 'kode')],
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
