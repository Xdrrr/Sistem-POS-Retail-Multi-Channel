<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductInventory;
use App\Traits\StoresCatalogImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogPageController extends Controller
{
    use StoresCatalogImages;

    public function index(): Response
    {
        return Inertia::render('Catalog/Index', [
            'title' => 'Product Catalog',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'cabangs' => Cabang::listActive(),
            'categories' => Category::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category): array => $this->categoryData($category)),
            'groups' => ProductGroup::query()
                ->orderBy('name')
                ->get()
                ->map(fn (ProductGroup $group): array => $this->groupData($group)),
            'products' => Product::query()
                ->with(['category', 'group'])
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product): array => $this->productData($product)),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ]);

        Category::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'categories'),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('catalog.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function updateCategory(Request $request, string $guid): RedirectResponse
    {
        $category = Category::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'categories', $category->image),
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('catalog.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyCategory(string $guid): RedirectResponse
    {
        $category = Category::query()->where('guid', $guid)->firstOrFail();

        if (! $category->products()->exists()) {
            $this->deleteCatalogImage($category->image);
            $category->delete();
        }

        return redirect()->route('catalog.index')->with('success', 'Kategori berhasil dihapus.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ]);

        ProductGroup::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'groups'),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('catalog.index')->with('success', 'Grup berhasil dibuat.');
    }

    public function updateGroup(Request $request, string $guid): RedirectResponse
    {
        $group = ProductGroup::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')->ignore($group->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ]);

        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'groups', $group->image),
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('catalog.index')->with('success', 'Grup berhasil diperbarui.');
    }

    public function destroyGroup(string $guid): RedirectResponse
    {
        $group = ProductGroup::query()->where('guid', $guid)->firstOrFail();

        if (! $group->products()->exists()) {
            $this->deleteCatalogImage($group->image);
            $group->delete();
        }

        return redirect()->route('catalog.index')->with('success', 'Grup berhasil dihapus.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->productRules());
        $productGuid = (string) Str::uuid();
        $guidCabang = $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001';

        Product::query()->create([
            'guid' => $productGuid,
            'category_guid' => $validated['category_guid'],
            'group_guid' => $validated['group_guid'],
            'guid_cabang' => $guidCabang,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'products'),
            'price' => $validated['price'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        ProductInventory::query()->create([
            'guid' => (string) Str::uuid(),
            'product_guid' => $productGuid,
            'guid_cabang' => $guidCabang,
            'unit' => 'pcs',
            'current_stock' => 0,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        return redirect()->route('catalog.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function updateProduct(Request $request, string $guid): RedirectResponse
    {
        $product = Product::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate($this->productRules($product));
        $oldGuidCabang = $product->guid_cabang;
        $newGuidCabang = $validated['guid_cabang'] ?? $oldGuidCabang;

        $product->update([
            'category_guid' => $validated['category_guid'],
            'group_guid' => $validated['group_guid'],
            'guid_cabang' => $newGuidCabang,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $this->storeCatalogImage($request, 'products', $product->image),
            'price' => $validated['price'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        if ($newGuidCabang !== $oldGuidCabang) {
            ProductInventory::query()
                ->where('product_guid', $product->guid)
                ->update(['guid_cabang' => $newGuidCabang]);
        }

        return redirect()->route('catalog.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroyProduct(string $guid): RedirectResponse
    {
        $product = Product::query()->where('guid', $guid)->firstOrFail();
        $this->deleteCatalogImage($product->image);
        $product->delete();

        return redirect()->route('catalog.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function productRules(?Product $product = null): array
    {
        return [
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'guid_cabang' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:150', Rule::unique(Product::class, 'name')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
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
        ];
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
        ];
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
            'category_guid' => $product->category_guid,
            'group_guid' => $product->group_guid,
            'category_name' => $product->category?->name,
            'group_name' => $product->group?->name,
        ];
    }
}
