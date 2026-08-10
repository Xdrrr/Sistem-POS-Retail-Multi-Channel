<?php

namespace App\Http\Requests\Web\Profile;

use App\Models\AuthenticationUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = AuthenticationUser::query()->find($this->session()->get('web_auth_user_id'));

        return [
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(AuthenticationUser::class, 'username')->ignore($user?->id)],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan', 'Tidak-Spesifik'])],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'password' => ['nullable', 'string', 'min:6'],
            'confirm_password' => ['nullable', 'required_with:password', 'same:password'],
        ];
    }
}
