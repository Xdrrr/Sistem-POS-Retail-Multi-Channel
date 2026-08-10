<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\Role\StoreRoleRequest;
use App\Http\Requests\Web\Role\UpdateRoleRequest;
use App\Models\AuthenticationRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RolePageController extends Controller
{
    public function index(): Response
    {
        $roles = AuthenticationRole::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (AuthenticationRole $r): array => [
                'guid' => $r->guid,
                'name' => $r->name,
                'is_default' => $r->is_default,
                'users_count' => $r->users_count,
                'created_at' => $r->created_at?->toISOString(),
                'updated_at' => $r->updated_at?->toISOString(),
            ]);

        return Inertia::render('Roles/Index', [
            'title' => 'Roles',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'roles' => $roles,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AuthenticationRole::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
    }

    public function update(UpdateRoleRequest $request, string $guid): RedirectResponse
    {
        $role = AuthenticationRole::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validated();

        $role->update($validated);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        $role = AuthenticationRole::query()->where('guid', $guid)->firstOrFail();

        if ($role->users()->exists()) {
            return redirect()->route('roles.index')->with('error', 'Role masih memiliki user, tidak dapat dihapus.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
