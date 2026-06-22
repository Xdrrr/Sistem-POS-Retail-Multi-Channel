<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationRole;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionPageController extends Controller
{
    public function index(): Response
    {
        $roles = AuthenticationRole::query()
            ->orderBy('name')
            ->get()
            ->map(fn (AuthenticationRole $r): array => [
                'guid' => $r->guid,
                'name' => $r->name,
            ]);

        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group')
            ->map(fn ($perms, string $group): array => [
                'group' => $group,
                'type' => $perms->first()->type,
                'items' => $perms->map(fn (Permission $p): array => [
                    'guid' => $p->guid,
                    'name' => $p->name,
                    'display_name' => $p->display_name,
                ])->values(),
            ])
            ->values();

        $rolePerms = [];
        foreach (AuthenticationRole::all() as $role) {
            $rolePerms[$role->guid] = $role->permissions()->pluck('name')->toArray();
        }

        return Inertia::render('Permissions/Index', [
            'title' => 'Permissions',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'roles' => $roles,
            'permissions' => $permissions,
            'role_permissions' => $rolePerms,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_guid' => ['required', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = AuthenticationRole::query()->where('guid', $validated['role_guid'])->firstOrFail();
        $permNames = $validated['permissions'] ?? [];

        $guids = Permission::query()
            ->whereIn('name', $permNames)
            ->pluck('guid')
            ->toArray();

        $role->permissions()->sync($guids);

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diperbarui.');
    }
}
