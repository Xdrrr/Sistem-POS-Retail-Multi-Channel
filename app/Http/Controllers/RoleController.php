<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationRole;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use Filterable;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:name,is_default,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        $query = AuthenticationRole::query();
        $this->applyFilter($request, $query, ['guid', 'name', 'is_default']);

        $roles = $query->get()
            ->map(fn (AuthenticationRole $r): array => $this->roleData($r));

        return $this->apiResponse('00', 'success', $roles);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique(AuthenticationRole::class, 'name')],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();

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

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guid' => ['required', 'string', Rule::exists(AuthenticationRole::class, 'guid')],
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $role = AuthenticationRole::query()->where('guid', $validated['guid'])->first();

        if (! $role) {
            return $this->apiResponse('01', 'failed', null, 'Role not found.', 'Role tidak ditemukan.', 404);
        }

        $validator = Validator::make($request->all(), [
            'guid' => ['required', 'string'],
            'name' => ['required', 'string', 'max:100', Rule::unique(AuthenticationRole::class, 'name')->ignore($role->id)],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();

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
