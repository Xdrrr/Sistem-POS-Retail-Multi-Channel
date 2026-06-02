<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('token') ?: $request->bearerToken();

        if (! $token) {
            return $this->apiResponse('01', 'failed', null, 'Invalid or expired token.', 'Token tidak valid atau sudah kedaluwarsa.', 401);
        }

        $apiToken = ApiToken::query()
            ->where('access_token_hash', hash('sha256', $token))
            ->first();

        if (! $apiToken?->isUsable()) {
            return $this->apiResponse('01', 'failed', null, 'Invalid or expired token.', 'Token tidak valid atau sudah kedaluwarsa.', 401);
        }

        $apiToken->update(['last_used_at' => now()]);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }

    private function apiResponse(
        string $code,
        string $status,
        mixed $data = null,
        string $messageEn = 'Success',
        string $messageId = 'Sukses',
        int $httpStatus = 200,
    ): Response {
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
}
