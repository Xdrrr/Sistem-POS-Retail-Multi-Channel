<?php

namespace App\Http\Requests\Web\Role;

use App\Models\AuthenticationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = AuthenticationRole::query()->where('guid', $this->route('guid'))->first();

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(AuthenticationRole::class, 'name')->ignore($role?->id)],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
