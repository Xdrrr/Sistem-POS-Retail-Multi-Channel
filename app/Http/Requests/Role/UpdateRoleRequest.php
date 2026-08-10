<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\ApiFormRequest;
use App\Models\AuthenticationRole;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $role = AuthenticationRole::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(AuthenticationRole::class, 'guid')],
            'name' => ['required', 'string', 'max:100', Rule::unique(AuthenticationRole::class, 'name')->ignore($role?->id)],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
