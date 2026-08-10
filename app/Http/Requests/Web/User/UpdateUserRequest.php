<?php

namespace App\Http\Requests\Web\User;

use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use App\Models\Cabang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = AuthenticationUser::query()->where('guid', $this->route('guid'))->first();

        return [
            'username' => ['required', 'email', 'max:255', Rule::unique(AuthenticationUser::class, 'username')->ignore($user?->id)],
            'role_guid' => ['required', 'string', Rule::exists(AuthenticationRole::class, 'guid')],
            'guid_cabang' => ['nullable', 'string', Rule::exists(Cabang::class, 'guid')],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
            'confirm_password' => ['nullable', 'same:password'],
        ];
    }
}
