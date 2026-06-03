<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalog/Index', [
            'title' => 'Product Catalog',
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
            'is_active' => ['boolean'],
        ]);

        Category::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('catalog.index');
    }

    public function updateCategory(Request $request, string $guid): RedirectResponse
    {
        $category = Category::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('catalog.index');
    }

    public function destroyCategory(string $guid): RedirectResponse
    {
        $category = Category::query()->where('guid', $guid)->firstOrFail();

        if (! $category->products()->exists()) {
            $category->delete();
        }

        return redirect()->route('catalog.index');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        ProductGroup::query()->create([
            'guid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('catalog.index');
    }

    public function updateGroup(Request $request, string $guid): RedirectResponse
    {
        $group = ProductGroup::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')->ignore($group->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('catalog.index');
    }

    public function destroyGroup(string $guid): RedirectResponse
    {
        $group = ProductGroup::query()->where('guid', $guid)->firstOrFail();

        if (! $group->products()->exists()) {
            $group->delete();
        }

        return redirect()->route('catalog.index');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->productRules());
        $category = Category::query()->where('guid', $validated['category_guid'])->firstOrFail();
        $group = ProductGroup::query()->where('guid', $validated['group_guid'])->firstOrFail();

        Product::query()->create([
            'guid' => (string) Str::uuid(),
            'category_id' => $category->id,
            'group_id' => $group->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('catalog.index');
    }

    public function updateProduct(Request $request, string $guid): RedirectResponse
    {
        $product = Product::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate($this->productRules($product));
        $category = Category::query()->where('guid', $validated['category_guid'])->firstOrFail();
        $group = ProductGroup::query()->where('guid', $validated['group_guid'])->firstOrFail();

        $product->update([
            'category_id' => $category->id,
            'group_id' => $group->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('catalog.index');
    }

    public function destroyProduct(string $guid): RedirectResponse
    {
        Product::query()->where('guid', $guid)->firstOrFail()->delete();

        return redirect()->route('catalog.index');
    }

    private function productRules(?Product $product = null): array
    {
        return [
            'category_guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'group_guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:150', Rule::unique(Product::class, 'name')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
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
            'is_active' => $category->is_active,
        ];
    }

    private function groupData(ProductGroup $group): array
    {
        return [
            'guid' => $group->guid,
            'name' => $group->name,
            'description' => $group->description,
            'is_active' => $group->is_active,
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
            'category_guid' => $product->category?->guid,
            'group_guid' => $product->group?->guid,
            'category_name' => $product->category?->name,
            'group_name' => $product->group?->name,
        ];
    }
}
