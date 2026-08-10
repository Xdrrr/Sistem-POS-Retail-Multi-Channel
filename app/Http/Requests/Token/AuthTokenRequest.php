<?php

namespace App\Http\Requests\Token;

use App\Http\Requests\ApiFormRequest;

class AuthTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'app_key' => ['required', 'string', 'max:255'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'max:100'],
            'fcm_token' => ['nullable', 'string'],
            'ip_address' => ['required', 'ip'],
        ];
    }
}
