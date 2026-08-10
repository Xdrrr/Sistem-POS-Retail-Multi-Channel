<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Traits\Filterable;
use App\Traits\StoresCatalogImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use Filterable;
    use StoresCatalogImages;

    public function index(IndexProductRequest $request): JsonResponse
    {
        $query = Product::query()->with(['category', 'group']);
        $this->applyFilter($request, $query, ['guid', 'sku', 'category_guid', 'group_guid', 'guid_cabang', 'is_active']);

        $products = $query->get()
            ->map(fn (Product $product): array => $this->productData($product));

        return $this->apiResponse('00', 'success', $products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::query()->create([
            'guid' => (string) Str::uuid(),
            'sku' => $validated['sku'] ?? null,
            'category_guid' => $validated['category_guid'],
            'group_guid' => $validated['group_guid'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'products'),
            'price' => $validated['price'] ?? 0,
            'guid_cabang' => $validated['guid_cabang'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->apiResponse('00', 'success', $this->productData($product->load(['category', 'group'])), 'Product created successfully.', 'Produk berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $product = $this->findProduct($guid);

        if (! $product) {
            return $this->apiResponse('01', 'failed', null, 'Product not found.', 'Produk tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->productData($product));
    }

    public function update(UpdateProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $product = $this->findProduct($validated['guid']);

        if (! $product) {
            return $this->apiResponse('01', 'failed', null, 'Product not found.', 'Produk tidak ditemukan.', 404);
        }

        $product->update([
            'sku' => $validated['sku'] ?? $product->sku,
            'category_guid' => $validated['category_guid'],
            'group_guid' => $validated['group_guid'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'products', $product->image),
            'price' => $validated['price'] ?? $product->price,
            'guid_cabang' => $validated['guid_cabang'] ?? $product->guid_cabang,
            'is_active' => $validated['is_active'] ?? $product->is_active,
        ]);

        return $this->apiResponse('00', 'success', $this->productData($product->refresh()->load(['category', 'group'])), 'Product updated successfully.', 'Produk berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $product = $this->findProduct($guid);

        if (! $product) {
            return $this->apiResponse('01', 'failed', null, 'Product not found.', 'Produk tidak ditemukan.', 404);
        }

        $this->deleteCatalogImage($product->image);
        $product->delete();

        return $this->apiResponse('00', 'success', null, 'Product deleted successfully.', 'Produk berhasil dihapus.');
    }

    private function findProduct(string $guid): ?Product
    {
        return Product::query()
            ->with(['category', 'group'])
            ->where('guid', $guid)
            ->first();
    }

    private function productData(Product $product): array
    {
        return [
            'guid' => $product->guid,
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'image' => $product->image,
            'image_url' => $this->catalogImageUrl($product->image),
            'price' => $product->price,
            'guid_cabang' => $product->guid_cabang,
            'is_active' => $product->is_active,
            'category' => [
                'guid' => $product->category?->guid,
                'name' => $product->category?->name,
            ],
            'group' => [
                'guid' => $product->group?->guid,
                'name' => $product->group?->name,
            ],
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }
}
