<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductGroupController extends Controller
{
    public function index(): JsonResponse
    {
        $groups = ProductGroup::query()
            ->orderBy('name')
            ->get()
            ->map(fn (ProductGroup $group): array => $this->groupData($group));

        return $this->apiResponse('00', 'success', $groups);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('product_groups', 'name')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $group = ProductGroup::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->apiResponse('00', 'success', $this->groupData($group), 'Group created successfully.', 'Group berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $group = $this->findGroup($guid);

        if (! $group) {
            return $this->apiResponse('01', 'failed', null, 'Group not found.', 'Group tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->groupData($group));
    }

    public function update(Request $request, string $guid): JsonResponse
    {
        $group = $this->findGroup($guid);

        if (! $group) {
            return $this->apiResponse('01', 'failed', null, 'Group not found.', 'Group tidak ditemukan.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('product_groups', 'name')->ignore($group->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $group->is_active,
        ]);

        return $this->apiResponse('00', 'success', $this->groupData($group->refresh()), 'Group updated successfully.', 'Group berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $group = $this->findGroup($guid);

        if (! $group) {
            return $this->apiResponse('01', 'failed', null, 'Group not found.', 'Group tidak ditemukan.', 404);
        }

        if ($group->products()->exists()) {
            return $this->apiResponse('02', 'failed', null, 'Group is used by product data.', 'Group masih digunakan oleh data produk.', 409);
        }

        $group->delete();

        return $this->apiResponse('00', 'success', null, 'Group deleted successfully.', 'Group berhasil dihapus.');
    }

    private function findGroup(string $guid): ?ProductGroup
    {
        return ProductGroup::query()->where('guid', $guid)->first();
    }

    private function groupData(ProductGroup $group): array
    {
        return [
            'guid' => $group->guid,
            'name' => $group->name,
            'description' => $group->description,
            'is_active' => $group->is_active,
            'created_at' => $group->created_at?->toISOString(),
            'updated_at' => $group->updated_at?->toISOString(),
        ];
    }
}
