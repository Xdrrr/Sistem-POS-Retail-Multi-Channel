<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\ApiFormRequest;
use App\Models\AuthenticationRole;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(AuthenticationRole::class, 'name')],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
