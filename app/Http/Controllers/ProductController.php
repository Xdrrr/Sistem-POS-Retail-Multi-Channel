<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use Filterable;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:name,description,price,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        $query = Product::query()->with(['category', 'group']);
        $this->applyFilter($request, $query, ['guid', 'category_id', 'group_id']);

        $products = $query->get()
            ->map(fn (Product $product): array => $this->productData($product));

        return $this->apiResponse('00', 'success', $products);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $category = Category::query()->where('guid', $validated['category_guid'])->first();
        $group = ProductGroup::query()->where('guid', $validated['group_guid'])->first();

        $product = Product::query()->create([
            'guid' => (string) Str::uuid(),
            'category_id' => $category->id,
            'group_id' => $group->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? 0,
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

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = $this->findProduct($validated['guid']);

        if (! $product) {
            return $this->apiResponse('01', 'failed', null, 'Product not found.', 'Produk tidak ditemukan.', 404);
        }

        $rules = $this->rules($product);
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $category = Category::query()->where('guid', $validated['category_guid'])->first();
        $group = ProductGroup::query()->where('guid', $validated['group_guid'])->first();

        $product->update([
            'category_id' => $category->id,
            'group_id' => $group->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? $product->price,
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

    private function rules(?Product $product = null): array
    {
        return [
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:150', Rule::unique(Product::class, 'name')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function productData(Product $product): array
    {
        return [
            'guid' => $product->guid,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
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
