<?php

namespace App\Http\Requests\Cabang;

use App\Http\Requests\ApiFormRequest;
use App\Models\Cabang;
use Illuminate\Validation\Rule;

class UpdateCabangRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $cabang = Cabang::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(Cabang::class, 'guid')],
            'kode' => ['required', 'string', 'max:50', Rule::unique(Cabang::class, 'kode')->ignore($cabang?->id)],
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
