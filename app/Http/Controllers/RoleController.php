<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\IndexRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\AuthenticationRole;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use Filterable;

    public function index(IndexRoleRequest $request): JsonResponse
    {
        $query = AuthenticationRole::query();
        $this->applyFilter($request, $query, ['guid', 'name', 'is_default']);

        $roles = $query->get()
            ->map(fn (AuthenticationRole $r): array => $this->roleData($r));

        return $this->apiResponse('00', 'success', $roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = AuthenticationRole::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return $this->apiResponse('00', 'success', $this->roleData($role), 'Role created.', 'Role berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $role = AuthenticationRole::query()->where('guid', $guid)->first();

        if (! $role) {
            return $this->apiResponse('01', 'failed', null, 'Role not found.', 'Role tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->roleData($role));
    }

    public function update(UpdateRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = AuthenticationRole::query()->where('guid', $validated['guid'])->first();

        if (! $role) {
            return $this->apiResponse('01', 'failed', null, 'Role not found.', 'Role tidak ditemukan.', 404);
        }

        $role->update([
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? $role->is_default,
        ]);

        return $this->apiResponse('00', 'success', $this->roleData($role->refresh()), 'Role updated.', 'Role berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $role = AuthenticationRole::query()->where('guid', $guid)->first();

        if (! $role) {
            return $this->apiResponse('01', 'failed', null, 'Role not found.', 'Role tidak ditemukan.', 404);
        }

        if ($role->users()->exists()) {
            return $this->apiResponse('02', 'failed', null, 'Role has users assigned, cannot delete.', 'Role masih memiliki user, tidak dapat dihapus.', 409);
        }

        $role->delete();

        return $this->apiResponse('00', 'success', null, 'Role deleted.', 'Role berhasil dihapus.');
    }

    private function roleData(AuthenticationRole $role): array
    {
        return [
            'guid' => $role->guid,
            'name' => $role->name,
            'is_default' => $role->is_default,
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }
}
