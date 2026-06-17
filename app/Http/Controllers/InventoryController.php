<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationSession;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_product_guid' => ['nullable', 'boolean'],
            'filter.product_guid' => ['nullable', 'string'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string', 'max:50'],
            'filter.set_unit' => ['nullable', 'boolean'],
            'filter.unit' => ['nullable', 'string', 'max:20'],
            'filter.set_is_active' => ['nullable', 'boolean'],
            'filter.is_active' => ['nullable', 'boolean'],
            'filter.set_low_stock' => ['nullable', 'boolean'],
            'filter.low_stock' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:product_name,guid_cabang,unit,current_stock,minimum_stock,is_active,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        $filter = $validated['filter'] ?? [];
        $limit = $validated['limit'] ?? 20;
        $page = $validated['page'] ?? 1;
        $order = $validated['order'] ?? 'product_name';
        $sort = $validated['sort'] ?? 'ASC';

        $query = ProductInventory::query()
            ->with(['product.category', 'product.group']);

        foreach (['guid', 'product_guid', 'guid_cabang', 'unit', 'is_active'] as $field) {
            if (($filter["set_{$field}"] ?? false) === true && array_key_exists($field, $filter)) {
                $query->where($field, $filter[$field]);
            }
        }

        if (($filter['set_low_stock'] ?? false) === true && ($filter['low_stock'] ?? false) === true) {
            $query->whereColumn('current_stock', '<=', 'minimum_stock');
        }

        if ($order === 'product_name') {
            $query->orderBy(
                Product::query()
                    ->select('name')
                    ->whereColumn('product.products.guid', 'product.inventories.product_guid')
                    ->limit(1),
                $sort,
            );
        } else {
            $query->orderBy($order, $sort);
        }

        $inventories = $query
            ->limit($limit)
            ->skip(($page - 1) * $limit)
            ->get()
            ->map(fn (ProductInventory $inventory): array => $this->inventoryData($inventory));

        return $this->apiResponse('00', 'success', $inventories);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'guid_cabang' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:20'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $guidCabang = $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001';
        $initialStock = (float) ($validated['current_stock'] ?? 0);

        $exists = ProductInventory::query()
            ->where('product_guid', $validated['product_guid'])
            ->where('guid_cabang', $guidCabang)
            ->exists();

        if ($exists) {
            return $this->apiResponse('02', 'failed', null, 'Inventory already exists for this product and branch.', 'Inventory untuk produk dan cabang ini sudah ada.', 409);
        }

        $inventory = ProductInventory::query()->create([
            'guid' => (string) Str::uuid(),
            'product_guid' => $validated['product_guid'],
            'guid_cabang' => $guidCabang,
            'unit' => $validated['unit'] ?? 'pcs',
            'current_stock' => $initialStock,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($initialStock > 0) {
            $userGuid = $this->authUserGuid($request);
            $service = app(InventoryService::class);
            $service->adjustStock(
                inventory: $inventory,
                type: 'in',
                qty: $initialStock,
                referenceType: 'manual_adjustment',
                notes: 'add stok',
                createdBy: $userGuid,
                userGuidReff: $userGuid,
            );
        }

        return $this->apiResponse('00', 'success', $this->inventoryData($inventory->load(['product.category', 'product.group'])), 'Inventory created successfully.', 'Inventory berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $inventory = $this->findInventory($guid);

        if (! $inventory) {
            return $this->apiResponse('01', 'failed', null, 'Inventory not found.', 'Inventory tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->inventoryData($inventory));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'guid' => ['required', 'string', Rule::exists(ProductInventory::class, 'guid')],
        ]);

        $inventory = $this->findInventory($request->string('guid')->toString());

        if (! $inventory) {
            return $this->apiResponse('01', 'failed', null, 'Inventory not found.', 'Inventory tidak ditemukan.', 404);
        }

        $validator = Validator::make($request->all(), [
            'guid' => ['required', 'string', Rule::exists(ProductInventory::class, 'guid')],
            'product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'guid_cabang' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:20'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
        $guidCabang = $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001';

        $duplicate = ProductInventory::query()
            ->where('product_guid', $validated['product_guid'])
            ->where('guid_cabang', $guidCabang)
            ->where('id', '!=', $inventory->id)
            ->exists();

        if ($duplicate) {
            return $this->apiResponse('02', 'failed', null, 'Inventory already exists for this product and branch.', 'Inventory untuk produk dan cabang ini sudah ada.', 409);
        }

        $oldData = [
            'product_guid' => $inventory->product_guid,
            'guid_cabang' => $inventory->guid_cabang,
            'unit' => $inventory->unit,
            'minimum_stock' => $inventory->minimum_stock,
            'is_active' => $inventory->is_active,
        ];

        $inventory->update([
            'product_guid' => $validated['product_guid'],
            'guid_cabang' => $guidCabang,
            'unit' => $validated['unit'] ?? 'pcs',
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'is_active' => $validated['is_active'] ?? $inventory->is_active,
        ]);

        $changes = [];
        foreach (['guid_cabang', 'unit', 'minimum_stock', 'is_active'] as $field) {
            if ($oldData[$field] != $inventory->{$field}) {
                $changes[] = "{$field}: {$oldData[$field]} → {$inventory->{$field}}";
            }
        }

        if ($changes) {
            $userGuid = $this->authUserGuid($request);
            $service = app(InventoryService::class);
            $service->adjustStock(
                inventory: $inventory,
                type: 'adjustment',
                qty: (float) $inventory->current_stock,
                referenceType: 'manual_adjustment',
                notes: 'Update manual: '.implode(', ', $changes),
                createdBy: $userGuid,
                userGuidReff: $userGuid,
            );
        }

        return $this->apiResponse('00', 'success', $this->inventoryData($inventory->refresh()->load(['product.category', 'product.group'])), 'Inventory updated successfully.', 'Inventory berhasil diperbarui.');
    }

    private function authUserGuid(Request $request): ?string
    {
        $apiToken = $request->attributes->get('api_token');

        if (! $apiToken) {
            return null;
        }

        return AuthenticationSession::query()
            ->with('user')
            ->where('api_token_id', $apiToken->id)
            ->latest('last_login_at')
            ->latest('id')
            ->first()?->user?->guid;
    }

    public function destroy(string $guid): JsonResponse
    {
        $inventory = $this->findInventory($guid);

        if (! $inventory) {
            return $this->apiResponse('01', 'failed', null, 'Inventory not found.', 'Inventory tidak ditemukan.', 404);
        }

        if (! $inventory->is_active) {
            return $this->apiResponse('02', 'failed', null, 'Inventory is already inactive.', 'Inventory sudah tidak aktif.', 409);
        }

        $inventory->update(['is_active' => false]);

        return $this->apiResponse('00', 'success', null, 'Inventory deactivated successfully.', 'Inventory berhasil dinonaktifkan.');
    }

    private function findInventory(string $guid): ?ProductInventory
    {
        return ProductInventory::query()
            ->with(['product.category', 'product.group'])
            ->where('guid', $guid)
            ->first();
    }

    private function inventoryData(ProductInventory $inventory): array
    {
        return [
            'guid' => $inventory->guid,
            'product' => $inventory->product ? [
                'guid' => $inventory->product->guid,
                'name' => $inventory->product->name,
                'price' => $inventory->product->price,
                'category' => $inventory->product->category ? [
                    'guid' => $inventory->product->category->guid,
                    'name' => $inventory->product->category->name,
                ] : null,
                'group' => $inventory->product->group ? [
                    'guid' => $inventory->product->group->guid,
                    'name' => $inventory->product->group->name,
                ] : null,
            ] : null,
            'guid_cabang' => $inventory->guid_cabang,
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
