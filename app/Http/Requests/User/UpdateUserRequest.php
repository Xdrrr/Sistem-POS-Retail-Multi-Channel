<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiFormRequest;
use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use App\Models\Cabang;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $user = AuthenticationUser::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(AuthenticationUser::class, 'guid')],
            'username' => ['required', 'email', 'max:255', Rule::unique(AuthenticationUser::class, 'username')->ignore($user?->id)],
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
            'password' => ['nullable', 'string', 'min:6'],
            'confirm_password' => ['nullable', 'same:password'],
        ];
    }
}
