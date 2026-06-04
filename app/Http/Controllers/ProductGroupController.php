<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use App\Traits\Filterable;
use App\Traits\StoresCatalogImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductGroupController extends Controller
{
    use Filterable;
    use StoresCatalogImages;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:name,description,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        $query = ProductGroup::query();
        $this->applyFilter($request, $query, ['guid']);

        $groups = $query->get()
            ->map(fn (ProductGroup $group): array => $this->groupData($group));

        return $this->apiResponse('00', 'success', $groups);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
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
            'image' => $this->storeCatalogImage($request, 'groups'),
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

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['nullable', 'boolean'],
        ]);

        $group = $this->findGroup($validated['guid']);

        if (! $group) {
            return $this->apiResponse('01', 'failed', null, 'Group not found.', 'Group tidak ditemukan.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')->ignore($group->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'groups', $group->image),
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

        $this->deleteCatalogImage($group->image);
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
            'image' => $group->image,
            'image_url' => $this->catalogImageUrl($group->image),
            'is_active' => $group->is_active,
            'created_at' => $group->created_at?->toISOString(),
            'updated_at' => $group->updated_at?->toISOString(),
        ];
    }
}
