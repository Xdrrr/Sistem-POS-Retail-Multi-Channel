<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }
}
