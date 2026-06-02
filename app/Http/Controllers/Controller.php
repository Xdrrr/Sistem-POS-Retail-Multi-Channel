<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function apiResponse(
        string $code,
        string $status,
        mixed $data = null,
        string $messageEn = 'Success',
        string $messageId = 'Sukses',
        int $httpStatus = 200,
    ): JsonResponse {
        return response()->json([
            'app_name' => config('app.name'),
            'version' => config('app.version'),
            'build' => '1',
            'response' => [
                'code' => $code,
                'status' => $status,
                'data' => $data,
                'message_en' => $messageEn,
                'message_id' => $messageId,
            ],
        ], $httpStatus);
    }

    protected function apiTokenFromRequest(Request $request): ?ApiToken
    {
        $token = $request->header('token') ?: $request->bearerToken();

        if (! $token) {
            return null;
        }

        $apiToken = ApiToken::query()
            ->where('access_token_hash', hash('sha256', $token))
            ->first();

        return $apiToken?->isUsable() ? $apiToken : null;
    }
}
