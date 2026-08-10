<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\Filterable;
use App\Traits\StoresCatalogImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use Filterable;
    use StoresCatalogImages;

    public function index(IndexCategoryRequest $request): JsonResponse
    {
        $query = Category::query();
        $this->applyFilter($request, $query, ['guid']);

        $categories = $query->get()
            ->map(fn (Category $category): array => $this->categoryData($category));

        return $this->apiResponse('00', 'success', $categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $category = Category::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'categories'),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->apiResponse('00', 'success', $this->categoryData($category), 'Category created successfully.', 'Kategori berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $category = $this->findCategory($guid);

        if (! $category) {
            return $this->apiResponse('01', 'failed', null, 'Category not found.', 'Kategori tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->categoryData($category));
    }

    public function update(UpdateCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $category = $this->findCategory($validated['guid']);

        if (! $category) {
            return $this->apiResponse('01', 'failed', null, 'Category not found.', 'Kategori tidak ditemukan.', 404);
        }

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'categories', $category->image),
            'is_active' => $validated['is_active'] ?? $category->is_active,
        ]);

        return $this->apiResponse('00', 'success', $this->categoryData($category->refresh()), 'Category updated successfully.', 'Kategori berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $category = $this->findCategory($guid);

        if (! $category) {
            return $this->apiResponse('01', 'failed', null, 'Category not found.', 'Kategori tidak ditemukan.', 404);
        }

        if ($category->products()->exists()) {
            return $this->apiResponse('02', 'failed', null, 'Category is used by product data.', 'Kategori masih digunakan oleh data produk.', 409);
        }

        $this->deleteCatalogImage($category->image);
        $category->delete();

        return $this->apiResponse('00', 'success', null, 'Category deleted successfully.', 'Kategori berhasil dihapus.');
    }

    private function findCategory(string $guid): ?Category
    {
        return Category::query()->where('guid', $guid)->first();
    }

    private function categoryData(Category $category): array
    {
        return [
            'guid' => $category->guid,
            'name' => $category->name,
            'description' => $category->description,
            'image' => $category->image,
            'image_url' => $this->catalogImageUrl($category->image),
            'is_active' => $category->is_active,
            'created_at' => $category->created_at?->toISOString(),
            'updated_at' => $category->updated_at?->toISOString(),
        ];
    }
}
