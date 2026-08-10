<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\User\StoreUserRequest;
use App\Http\Requests\Web\User\UpdateUserRequest;
use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use App\Models\AuthenticationUserDetail;
use App\Models\Cabang;
use App\Traits\HashesPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserPageController extends Controller
{
    use HashesPasswords;
    public function index(): Response
    {
        $users = AuthenticationUser::query()
            ->with(['role', 'detail'])
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(fn (AuthenticationUser $u): array => [
                'guid' => $u->guid,
                'username' => $u->username,
                'role' => $u->role ? [
                    'guid' => $u->role->guid,
                    'name' => $u->role->name,
                ] : null,
                'guid_cabang' => $u->guid_cabang,
                'is_active' => $u->is_active,
                'detail' => $u->detail ? [
                    'full_name' => $u->detail->full_name,
                    'email' => $u->detail->email,
                    'phone_number' => $u->detail->phone_number,
                ] : null,
                'last_login' => $u->last_login?->toISOString(),
                'created_at' => $u->created_at?->toISOString(),
            ]);

        $roles = AuthenticationRole::query()->orderBy('name')->get(['guid', 'name']);
        $cabangs = Cabang::query()->where('is_active', true)->orderBy('kode')->get(['guid', 'kode', 'nama']);

        return Inertia::render('Users/Index', [
            'title' => 'Users',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'users' => $users,
            'roles' => $roles,
            'cabangs' => $cabangs,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = AuthenticationRole::query()->where('guid', $validated['role_guid'])->firstOrFail();

        DB::transaction(function () use ($validated, $role): void {
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
                'gender' => '',
                'address' => null,
                'city' => null,
                'province' => null,
            ]);
        });

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function update(UpdateUserRequest $request, string $guid): RedirectResponse
    {
        $user = AuthenticationUser::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validated();

        $role = AuthenticationRole::query()->where('guid', $validated['role_guid'])->firstOrFail();

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
                ]);
            }
        });

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        AuthenticationUser::query()->where('guid', $guid)->firstOrFail()->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'User dinonaktifkan.');
    }
}
