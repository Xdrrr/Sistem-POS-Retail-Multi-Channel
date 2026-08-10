<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiFormRequest;
use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use App\Models\Cabang;
use Illuminate\Validation\Rule;

class StoreUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'email', 'max:255', Rule::unique(AuthenticationUser::class, 'username')],
            'password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:password'],
            'role_guid' => ['required', 'string', Rule::exists(AuthenticationRole::class, 'guid')],
            'guid_cabang' => ['nullable', 'string', Rule::exists(Cabang::class, 'guid')],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
