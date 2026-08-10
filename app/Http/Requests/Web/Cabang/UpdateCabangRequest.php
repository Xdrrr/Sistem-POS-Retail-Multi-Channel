<?php

namespace App\Http\Requests\Web\Cabang;

use App\Models\Cabang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCabangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cabang = Cabang::query()->where('guid', $this->route('guid'))->first();

        return [
            'kode' => ['required', 'string', 'max:50', Rule::unique(Cabang::class, 'kode')->ignore($cabang?->id)],
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
