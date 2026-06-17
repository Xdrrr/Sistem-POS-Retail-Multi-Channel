<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventoryPageController extends Controller
{
    public function index(): Response
    {
        $inventories = ProductInventory::query()
            ->with(['product.category', 'product.group'])
            ->orderBy('id_cabang')
            ->orderBy(
                Product::query()
                    ->select('name')
                    ->whereColumn('product.products.guid', 'product.inventories.product_guid')
                    ->limit(1),
            )
            ->get()
            ->map(fn (ProductInventory $inventory): array => $this->inventoryData($inventory));

        return Inertia::render('Inventory/Index', [
            'title' => 'Inventory',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'inventories' => $inventories,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['guid', 'name', 'price'])
                ->map(fn (Product $product): array => [
                    'guid' => $product->guid,
                    'name' => $product->name,
                    'price' => $product->price,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $idCabang = $validated['id_cabang'] ?? 'PUSAT';

        $exists = ProductInventory::query()
            ->where('product_guid', $validated['product_guid'])
            ->where('id_cabang', $idCabang)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'product_guid' => 'Inventory untuk produk dan cabang ini sudah ada.',
            ])->withInput();
        }

        ProductInventory::query()->create([
            'guid' => (string) Str::uuid(),
            'product_guid' => $validated['product_guid'],
            'id_cabang' => $idCabang,
            'unit' => $validated['unit'] ?? 'pcs',
            'current_stock' => $validated['current_stock'] ?? 0,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('inventory.index');
    }

    public function update(Request $request, string $guid): RedirectResponse
    {
        $inventory = ProductInventory::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate($this->rules($inventory));
        $idCabang = $validated['id_cabang'] ?? 'PUSAT';

        $duplicate = ProductInventory::query()
            ->where('product_guid', $validated['product_guid'])
            ->where('id_cabang', $idCabang)
            ->where('id', '!=', $inventory->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'product_guid' => 'Inventory untuk produk dan cabang ini sudah ada.',
            ])->withInput();
        }

        $inventory->update([
            'product_guid' => $validated['product_guid'],
            'id_cabang' => $idCabang,
            'unit' => $validated['unit'] ?? 'pcs',
            'current_stock' => $validated['current_stock'] ?? 0,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('inventory.index');
    }

    public function destroy(string $guid): RedirectResponse
    {
        ProductInventory::query()->where('guid', $guid)->firstOrFail()->delete();

        return redirect()->route('inventory.index');
    }

    private function rules(?ProductInventory $inventory = null): array
    {
        return [
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'id_cabang' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:20'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function inventoryData(ProductInventory $inventory): array
    {
        return [
            'guid' => $inventory->guid,
            'product_guid' => $inventory->product_guid,
            'product_name' => $inventory->product?->name ?? '-',
            'product_price' => $inventory->product?->price ?? 0,
            'category_name' => $inventory->product?->category?->name,
            'group_name' => $inventory->product?->group?->name,
            'id_cabang' => $inventory->id_cabang,
            'unit' => $inventory->unit,
            'current_stock' => $inventory->current_stock,
            'minimum_stock' => $inventory->minimum_stock,
            'is_low_stock' => (float) $inventory->current_stock <= (float) $inventory->minimum_stock,
            'is_active' => $inventory->is_active,
            'created_at' => $inventory->created_at?->toISOString(),
            'updated_at' => $inventory->updated_at?->toISOString(),
        ];
    }
}
