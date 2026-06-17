<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationUser;
use App\Models\InventoryHistory;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\Inventory\InventoryService;
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
        $initialStock = (float) ($validated['current_stock'] ?? 0);

        $exists = ProductInventory::query()
            ->where('product_guid', $validated['product_guid'])
            ->where('id_cabang', $idCabang)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'product_guid' => 'Inventory untuk produk dan cabang ini sudah ada.',
            ])->withInput();
        }

        $inventory = ProductInventory::query()->create([
            'guid' => (string) Str::uuid(),
            'product_guid' => $validated['product_guid'],
            'id_cabang' => $idCabang,
            'unit' => $validated['unit'] ?? 'pcs',
            'current_stock' => $initialStock,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($initialStock > 0) {
            $service = app(InventoryService::class);
            $service->adjustStock(
                inventory: $inventory,
                type: 'in',
                qty: $initialStock,
                referenceType: 'manual_adjustment',
                notes: 'add stok',
                createdBy: $this->authUserGuid($request),
                userGuidReff: $this->authUserGuid($request),
            );
        }

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

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'type' => ['required', 'string', 'in:in,out'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $inventory = ProductInventory::query()
            ->where('product_guid', $validated['product_guid'])
            ->where('id_cabang', 'PUSAT')
            ->first();

        if (! $inventory) {
            return back()->withErrors(['product_guid' => 'Inventory untuk produk ini belum ada.'])->withInput();
        }

        $userGuid = $this->authUserGuid($request);
        $service = app(InventoryService::class);
        $service->adjustStock(
            inventory: $inventory,
            type: $validated['type'],
            qty: (float) $validated['qty'],
            referenceType: 'manual_adjustment',
            notes: $validated['notes'] ?? ($validated['type'] === 'in' ? 'add stok' : 'kurang stok'),
            createdBy: $userGuid,
            userGuidReff: $userGuid,
        );

        $msg = $validated['type'] === 'in' ? 'Stok berhasil ditambahkan.' : 'Stok berhasil dikurangi.';

        return redirect()->route('inventory.index')->with('success', $msg);
    }

    private function authUserGuid(Request $request): ?string
    {
        $userId = $request->session()->get('web_auth_user_id');

        if (! $userId) {
            return null;
        }

        return AuthenticationUser::query()->where('id', $userId)->value('guid');
    }

    public function historyIndex(Request $request): Response
    {
        $productGuid = $request->input('filter.product_guid');
        $type = $request->input('filter.type');
        $referenceType = $request->input('filter.reference_type');
        $search = $request->input('filter.search');
        $fromDate = $request->input('filter.from_date');
        $toDate = $request->input('filter.to_date');
        $limit = (int) ($request->input('limit', 20));
        $sort = strtoupper($request->input('sort', 'DESC'));

        $query = InventoryHistory::query()
            ->with(['inventory.product.category', 'inventory.product.group', 'createdBy.detail']);

        if ($productGuid) {
            $query->where('product_guid', $productGuid);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($referenceType) {
            $query->where('reference_type', $referenceType);
        }

        if ($search) {
            $query->whereHas('inventory.product', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $history = $query->orderBy('created_at', $sort)
            ->paginate(min(max($limit, 1), 100))
            ->through(fn (InventoryHistory $record): array => [
                'guid' => $record->guid,
                'inventory_guid' => $record->inventory_id,
                'product_guid' => $record->product_guid,
                'product_name' => $record->inventory?->product?->name ?? '-',
                'category_name' => $record->inventory?->product?->category?->name,
                'group_name' => $record->inventory?->product?->group?->name,
                'id_cabang' => $record->id_cabang,
                'type' => $record->type,
                'qty' => (float) $record->qty,
                'stock_before' => (float) $record->stock_before,
                'stock_after' => (float) $record->stock_after,
                'reference_type' => $record->reference_type,
                'reference_id' => $record->reference_id,
                'notes' => $record->notes,
                'created_by' => $record->createdBy?->detail?->full_name
                    ?? $record->createdBy?->username
                    ?? '-',
                'created_at' => $record->created_at?->toISOString(),
            ]);

        $products = Product::query()
            ->whereHas('inventories')
            ->orderBy('name')
            ->get(['guid', 'name']);

        return Inertia::render('Inventory/History', [
            'title' => 'Riwayat Stok',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'history' => $history->items(),
            'pagination' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
            ],
            'products' => $products->map(fn (Product $p): array => [
                'guid' => $p->guid,
                'name' => $p->name,
            ]),
            'filters' => [
                'product_guid' => $productGuid ?? '',
                'type' => $type ?? '',
                'reference_type' => $referenceType ?? '',
                'search' => $search ?? '',
                'from_date' => $fromDate ?? '',
                'to_date' => $toDate ?? '',
                'limit' => $limit,
                'sort' => $sort,
            ],
        ]);
    }

    public function history(string $guid): Response
    {
        $inventory = ProductInventory::query()
            ->with('product.category', 'product.group')
            ->where('guid', $guid)
            ->firstOrFail();

        $history = InventoryHistory::query()
            ->with('createdBy.detail')
            ->where('inventory_id', $guid)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (InventoryHistory $record): array => [
                'guid' => $record->guid,
                'type' => $record->type,
                'qty' => (float) $record->qty,
                'stock_before' => (float) $record->stock_before,
                'stock_after' => (float) $record->stock_after,
                'reference_type' => $record->reference_type,
                'reference_id' => $record->reference_id,
                'notes' => $record->notes,
                'created_by' => $record->createdBy?->detail?->full_name
                    ?? $record->createdBy?->username
                    ?? '-',
                'created_at' => $record->created_at?->toISOString(),
            ]);

        return Inertia::render('Inventory/History', [
            'title' => 'Riwayat Stok',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'inventory' => $this->inventoryData($inventory),
            'history' => $history,
        ]);
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
