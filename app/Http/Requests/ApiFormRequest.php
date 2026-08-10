<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'app_name' => config('app.name'),
            'version' => config('app.version'),
            'build' => '1',
            'response' => [
                'code' => '99',
                'status' => 'failed',
                'data' => null,
                'message_en' => 'Validation failed.',
                'message_id' => 'Validasi gagal.',
            ],
        ], 422));
    }
}
