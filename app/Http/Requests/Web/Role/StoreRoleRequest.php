<?php

namespace App\Http\Requests\Web\Role;

use App\Models\AuthenticationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(AuthenticationRole::class, 'name')],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
