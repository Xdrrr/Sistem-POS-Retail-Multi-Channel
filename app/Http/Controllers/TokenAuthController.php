<?php

namespace App\Http\Controllers;

use App\Http\Requests\Token\AuthTokenRequest;
use App\Models\ApiClient;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class TokenAuthController extends Controller
{
    public function auth(AuthTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $client = ApiClient::query()
            ->where('app_name', $validated['app_name'])
            ->where('is_active', true)
            ->first();

        if (! $client || ! Hash::check($validated['app_key'], $client->app_key_hash)) {
            return $this->apiResponse('01', 'failed', null, 'Invalid app credentials.', 'Kredensial aplikasi tidak valid.', 401);
        }

        [$accessToken, $refreshToken, $token] = DB::transaction(function () use ($client, $validated): array {
            $accessExpiresAt = now()->addDay();
            $refreshExpiresAt = now()->addYear();
            $payload = [
                'app_name' => $client->app_name,
                'device_id' => $validated['device_id'],
                'device_type' => $validated['device_type'],
                'ip_address' => $validated['ip_address'],
            ];
            $accessToken = $this->generateJwtToken($payload, $accessExpiresAt);
            $refreshToken = $this->generateJwtToken($payload, $refreshExpiresAt);

            $token = $client->tokens()->create([
                'device_id' => $validated['device_id'],
                'device_type' => $validated['device_type'],
                'fcm_token' => $validated['fcm_token'] ?? null,
                'ip_address' => $validated['ip_address'],
                'access_token_hash' => $this->tokenHash($accessToken),
                'refresh_token_hash' => $this->tokenHash($refreshToken),
                'access_expires_at' => $accessExpiresAt,
                'refresh_expires_at' => $refreshExpiresAt,
                'last_used_at' => now(),
            ]);

            return [$accessToken, $refreshToken, $token];
        });

        return $this->tokenResponse($accessToken, $refreshToken, $token);
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->header('refresh-token');

        if (! $refreshToken) {
            return $this->apiResponse('99', 'failed', null, 'Refresh token header is required.', 'Header refresh token wajib diisi.', 422);
        }

        $token = ApiToken::query()
            ->with('client')
            ->where('refresh_token_hash', $this->tokenHash($refreshToken))
            ->first();

        if (! $token || ! $token->isRefreshable()) {
            return $this->apiResponse('01', 'failed', null, 'Invalid or expired refresh token.', 'Refresh token tidak valid atau sudah kedaluwarsa.', 401);
        }

        [$accessToken, $newRefreshToken, $token] = DB::transaction(function () use ($token): array {
            $accessExpiresAt = now()->addDay();
            $refreshExpiresAt = now()->addYear();
            $payload = [
                'app_name' => $token->client->app_name,
                'device_id' => $token->device_id,
                'device_type' => $token->device_type,
                'ip_address' => $token->ip_address,
            ];
            $accessToken = $this->generateJwtToken($payload, $accessExpiresAt);
            $refreshToken = $this->generateJwtToken($payload, $refreshExpiresAt);

            $token->update([
                'access_token_hash' => $this->tokenHash($accessToken),
                'refresh_token_hash' => $this->tokenHash($refreshToken),
                'access_expires_at' => $accessExpiresAt,
                'refresh_expires_at' => $refreshExpiresAt,
                'last_used_at' => now(),
            ]);

            return [$accessToken, $refreshToken, $token->refresh()];
        });

        return $this->tokenResponse($accessToken, $newRefreshToken, $token);
    }

    private function tokenResponse(string $accessToken, string $refreshToken, ApiToken $token): JsonResponse
    {
        return $this->apiResponse('00', 'success', [
            'name' => $token->client->app_name,
            'device_id' => $token->device_id,
            'device_type' => $token->device_type,
            'token' => $accessToken,
            'token_expired' => $token->access_expires_at?->toISOString(),
            'refresh_token' => $refreshToken,
            'refresh_token_expired' => $token->refresh_expires_at?->toISOString(),
            'is_login' => false,
            'user_login' => '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function generateJwtToken(array $payload, Carbon $expiresAt): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode([
            ...$payload,
            'exp' => $expiresAt->timestamp,
        ], JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $this->jwtSecret(), true));

        return $header.'.'.$body.'.'.$signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function jwtSecret(): string
    {
        return (string) config('services.jwt.secret');
    }

    private function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }
}
