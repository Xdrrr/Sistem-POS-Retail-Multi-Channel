<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ApiToken;
use App\Models\AuthenticationRole;
use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use App\Traits\HashesPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthenticationController extends Controller
{
    use HashesPasswords;
    public function login(LoginRequest $request): JsonResponse
    {
        $apiToken = $this->apiTokenFromRequest($request);

        if (! $apiToken) {
            return $this->apiResponse('01', 'failed', null, 'Invalid or expired token.', 'Token tidak valid atau sudah kedaluwarsa.', 401);
        }

        $validated = $request->validated();
        $user = AuthenticationUser::query()
            ->with(['role', 'detail'])
            ->where('username', $validated['username'])
            ->first();

        if (! $user || ! $user->is_active || ! hash_equals($user->password, $this->passwordHash($validated['password'], $user->salt))) {
            return $this->apiResponse('01', 'failed', null, 'Invalid username or password.', 'Username atau password tidak valid.', 401);
        }

        $session = DB::transaction(function () use ($user, $apiToken): AuthenticationSession {
            $now = now();

            $user->update(['last_login' => $now]);
            $apiToken->update(['last_used_at' => $now]);

            return AuthenticationSession::query()->create([
                'guid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'api_token_id' => $apiToken->id,
                'last_login_at' => $now,
            ]);
        });

        return $this->apiResponse('00', 'success', $this->userResponseData($user->refresh()->load(['role', 'detail']), $session));
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $apiToken = $this->apiTokenFromRequest($request);

        if (! $apiToken) {
            return $this->apiResponse('01', 'failed', null, 'Invalid or expired token.', 'Token tidak valid atau sudah kedaluwarsa.', 401);
        }

        $validated = $request->validated();

        if (AuthenticationUser::query()->where('username', $validated['email'])->exists()) {
            return $this->apiResponse('02', 'failed', null, 'Email already registered.', 'Email sudah terdaftar.', 409);
        }

        $role = AuthenticationRole::query()->where('name', 'Users')->first()
            ?? AuthenticationRole::query()->where('is_default', true)->first();

        if (! $role) {
            return $this->apiResponse('99', 'failed', null, 'Default user role is not configured.', 'Role default user belum dikonfigurasi.', 500);
        }

        [$user, $session] = DB::transaction(function () use ($validated, $role, $apiToken): array {
            $salt = base64_encode(random_bytes(16));
            $user = AuthenticationUser::query()->create([
                'guid' => (string) Str::uuid(),
                'role_id' => $role->id,
                'username' => $validated['email'],
                'password' => $this->passwordHash($validated['password'], $salt),
                'salt' => $salt,
                'is_active' => true,
                'url_image' => '',
                'fcm_token' => '',
                'last_login' => null,
                'used_trial' => true,
                'is_verified' => true,
            ]);

            $user->detail()->create([
                'phone_number' => $validated['phone_number'] ?? '',
                'email' => $validated['email'],
                'full_name' => $validated['fullname'],
                'gender' => $validated['gender'],
                'address' => null,
                'additional_address' => $validated['additional_address'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'date_of_birth' => $validated['birth_date'] ?? null,
            ]);

            $session = AuthenticationSession::query()->create([
                'guid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'api_token_id' => $apiToken->id,
                'last_login_at' => null,
            ]);

            $apiToken->update(['last_used_at' => now()]);

            return [$user->load(['role', 'detail']), $session];
        });

        return $this->apiResponse('00', 'success', $this->userResponseData($user, $session, true));
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

    private function userResponseData(AuthenticationUser $user, AuthenticationSession $session, bool $includePassword = false): array
    {
        $detail = $user->detail;
        $data = [
            'authentication_guid' => $session->guid,
            'users_guid' => $user->guid,
            'username' => $user->username,
            'role' => [
                'guid' => $user->role->guid,
                'name' => $user->role->name,
            ],
            'is_active' => $user->is_active,
            'url_image' => $user->url_image,
            'fcm_token' => $user->fcm_token,
            'last_login' => $user->last_login?->toISOString() ?? '0001-01-01T00:00:00Z',
            'detail_data' => [
                'phone_number' => $detail?->phone_number ?? '',
                'email' => $detail?->email ?? $user->username,
                'full_name' => $detail?->full_name ?? '',
                'gender' => $detail?->gender ?? '',
                'address' => $detail?->address,
                'additional_address' => $detail?->additional_address,
                'city' => $detail?->city,
                'province' => $detail?->province,
                'date_of_birth' => $detail?->date_of_birth?->format('Y-m-d'),
            ],
            'used_trial' => $user->used_trial,
            'is_verified' => $user->is_verified,
        ];

        if ($includePassword) {
            $data = [
                ...array_slice($data, 0, 3, true),
                'password' => $user->password,
                'salt' => $user->salt,
                ...array_slice($data, 3, null, true),
            ];
        }

        return $data;
    }
}
