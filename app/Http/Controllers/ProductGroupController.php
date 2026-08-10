<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductGroup\IndexProductGroupRequest;
use App\Http\Requests\ProductGroup\StoreProductGroupRequest;
use App\Http\Requests\ProductGroup\UpdateProductGroupRequest;
use App\Models\ProductGroup;
use App\Traits\Filterable;
use App\Traits\StoresCatalogImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductGroupController extends Controller
{
    use Filterable;
    use StoresCatalogImages;

    public function index(IndexProductGroupRequest $request): JsonResponse
    {
        $query = ProductGroup::query();
        $this->applyFilter($request, $query, ['guid']);

        $groups = $query->get()
            ->map(fn (ProductGroup $group): array => $this->groupData($group));

        return $this->apiResponse('00', 'success', $groups);
    }

    public function store(StoreProductGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();
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

    public function update(UpdateProductGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $group = $this->findGroup($validated['guid']);

        if (! $group) {
            return $this->apiResponse('01', 'failed', null, 'Group not found.', 'Group tidak ditemukan.', 404);
        }

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
