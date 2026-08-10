<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use App\Models\AuthenticationUserDetail;
use App\Traits\HashesPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use HashesPasswords;
    public function index(IndexUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filter = $validated['filter'] ?? [];
        $search = $validated['search'] ?? '';
        $limit = min((int) ($validated['limit'] ?? 20), 100);
        $page = max(1, (int) ($validated['page'] ?? 1));
        $order = $validated['order'] ?? 'created_at';
        $sort = strtoupper($validated['sort'] ?? 'DESC');

        $query = AuthenticationUser::query()
            ->with(['role', 'detail']);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('authentication.users.username', 'ILIKE', "%{$search}%")
                    ->orWhere('authentication.user_details.full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('authentication.user_details.email', 'ILIKE', "%{$search}%");
            });
        }

        if (($filter['set_guid'] ?? false) && ! empty($filter['guid'])) {
            $query->where('authentication.users.guid', $filter['guid']);
        }
        if (($filter['set_role_name'] ?? false) && ! empty($filter['role_name'])) {
            $query->whereHas('role', fn ($q) => $q->where('name', $filter['role_name']));
        }
        if (($filter['set_guid_cabang'] ?? false) && ! empty($filter['guid_cabang'])) {
            $query->where('authentication.users.guid_cabang', $filter['guid_cabang']);
        }
        if (($filter['set_is_active'] ?? false) && $filter['is_active'] !== null) {
            $query->where('authentication.users.is_active', filter_var($filter['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $orderMap = [
            'username' => 'authentication.users.username',
            'full_name' => 'authentication.user_details.full_name',
            'email' => 'authentication.user_details.email',
            'role_name' => 'role_name',
            'created_at' => 'authentication.users.created_at',
        ];

        $orderCol = $orderMap[$order] ?? 'authentication.users.created_at';

        if ($order === 'role_name') {
            $query->leftJoin('authentication.roles', 'authentication.users.role_id', '=', 'authentication.roles.id')
                ->orderBy('authentication.roles.name', $sort)
                ->select('authentication.users.*');
        } else {
            $query->leftJoin('authentication.user_details', 'authentication.users.id', '=', 'authentication.user_details.user_id')
                ->orderBy($orderCol, $sort)
                ->select('authentication.users.*');
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->load(['role', 'detail'])
            ->map(fn (AuthenticationUser $u): array => $this->userData($u));

        return $this->apiResponse('00', 'success', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = AuthenticationRole::query()->where('guid', $validated['role_guid'])->first();

        $user = DB::transaction(function () use ($validated, $role): AuthenticationUser {
            $salt = base64_encode(random_bytes(16));
            $user = AuthenticationUser::query()->create([
                'guid' => (string) Str::uuid(),
                'role_id' => $role->id,
                'guid_cabang' => $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001',
                'username' => $validated['username'],
                'password' => $this->passwordHash($validated['password'], $salt),
                'salt' => $salt,
                'is_active' => $validated['is_active'] ?? true,
                'url_image' => '',
                'fcm_token' => '',
                'last_login' => null,
                'used_trial' => true,
                'is_verified' => true,
            ]);

            AuthenticationUserDetail::query()->create([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? $validated['username'],
                'phone_number' => $validated['phone_number'] ?? '',
                'gender' => $validated['gender'] ?? '',
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
            ]);

            return $user->load(['role', 'detail']);
        });

        return $this->apiResponse('00', 'success', $this->userData($user), 'User created.', 'User berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $user = AuthenticationUser::query()
            ->with(['role', 'detail'])
            ->where('guid', $guid)
            ->first();

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User not found.', 'User tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->userData($user));
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = AuthenticationUser::query()->where('guid', $validated['guid'])->first();

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User not found.', 'User tidak ditemukan.', 404);
        }

        $role = AuthenticationRole::query()->where('guid', $validated['role_guid'])->first();

        DB::transaction(function () use ($user, $validated, $role): void {
            $updateData = [
                'role_id' => $role->id,
                'guid_cabang' => $validated['guid_cabang'] ?? $user->guid_cabang,
                'username' => $validated['username'],
                'is_active' => $validated['is_active'] ?? $user->is_active,
            ];

            if (! empty($validated['password'])) {
                $salt = base64_encode(random_bytes(16));
                $updateData['password'] = $this->passwordHash($validated['password'], $salt);
                $updateData['salt'] = $salt;
            }

            $user->update($updateData);

            if ($user->detail) {
                $user->detail->update([
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'] ?? $user->detail->email,
                    'phone_number' => $validated['phone_number'] ?? $user->detail->phone_number,
                    'gender' => $validated['gender'] ?? $user->detail->gender,
                    'address' => $validated['address'] ?? $user->detail->address,
                    'city' => $validated['city'] ?? $user->detail->city,
                    'province' => $validated['province'] ?? $user->detail->province,
                ]);
            }
        });

        return $this->apiResponse('00', 'success', $this->userData($user->fresh()->load(['role', 'detail'])), 'User updated.', 'User berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $user = AuthenticationUser::query()->where('guid', $guid)->first();

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User not found.', 'User tidak ditemukan.', 404);
        }

        $user->update(['is_active' => false]);

        return $this->apiResponse('00', 'success', null, 'User deactivated.', 'User berhasil dinonaktifkan.');
    }

    private function userData(AuthenticationUser $user): array
    {
        return [
            'guid' => $user->guid,
            'username' => $user->username,
            'role' => $user->role ? [
                'guid' => $user->role->guid,
                'name' => $user->role->name,
            ] : null,
            'guid_cabang' => $user->guid_cabang,
            'is_active' => $user->is_active,
            'detail' => $user->detail ? [
                'full_name' => $user->detail->full_name,
                'email' => $user->detail->email,
                'phone_number' => $user->detail->phone_number,
                'gender' => $user->detail->gender,
                'address' => $user->detail->address,
                'city' => $user->detail->city,
                'province' => $user->detail->province,
            ] : null,
            'url_image' => $user->url_image,
            'last_login' => $user->last_login?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}
