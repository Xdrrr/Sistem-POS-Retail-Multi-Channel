<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\AdjustInventoryRequest;
use App\Http\Requests\Inventory\HistoryInventoryRequest;
use App\Models\Cabang;
use App\Models\InventoryHistory;
use App\Models\ProductInventory;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Inventory\InventoryService;
use App\Traits\ResolvesAuthUserGuid;
use Illuminate\Http\JsonResponse;

class InventoryAdjustmentController extends Controller
{
    use ResolvesAuthUserGuid;

    public function adjust(AdjustInventoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $inventory = ProductInventory::query()
            ->with('product')
            ->where('guid', $validated['inventory_guid'])
            ->first();

        if (! $inventory) {
            return $this->apiResponse('01', 'failed', null, 'Inventory not found.', 'Inventory tidak ditemukan.', 404);
        }

        $userGuid = $this->authUserGuid($request);

        $refType = $validated['reference_type'] ?? null;
        $refId = $validated['reference_id'] ?? null;

        if ($refType && $refId && $refType !== 'manual_adjustment') {
            $existing = InventoryHistory::query()
                ->where('reference_type', $refType)
                ->where('reference_id', $refId)
                ->where('product_guid', $inventory->product_guid)
                ->where('type', $validated['type'])
                ->where('is_active', true)
                ->exists();

            if ($existing) {
                return $this->apiResponse('06', 'failed', null, 'Stock adjustment for this reference already exists.', 'Penyesuaian stok untuk referensi ini sudah ada.', 409);
            }
        }

        $service = app(InventoryService::class);

        try {
            $history = $service->adjustStock(
                inventory: $inventory,
                type: $validated['type'],
                qty: (float) $validated['qty'],
                referenceType: $validated['reference_type'] ?? null,
                referenceId: $validated['reference_id'] ?? null,
                notes: $validated['notes'] ?? null,
                createdBy: $userGuid,
                userGuidReff: $userGuid,
            );
        } catch (InsufficientStockException $e) {
            return $this->apiResponse('05', 'failed', [
                'product_name' => $e->productName,
                'current_stock' => $e->currentStock,
                'required_stock' => $e->requiredStock,
                'unit' => $e->unit,
            ], "Insufficient stock. Available: {$e->currentStock}, requested: {$e->requiredStock}", 'Stok tidak mencukupi.', 422);
        }

        return $this->apiResponse('00', 'success', [
            'guid' => $history->guid,
            'inventory' => [
                'guid' => $inventory->guid,
                'product_name' => $inventory->product?->name ?? '-',
            ],
            'type' => $history->type,
            'qty' => (float) $history->qty,
            'stock_before' => (float) $history->stock_before,
            'stock_after' => (float) $history->stock_after,
            'reference_type' => $history->reference_type,
            'reference_id' => $history->reference_id,
            'notes' => $history->notes,
            'created_at' => $history->created_at?->toISOString(),
        ], 'Stock adjusted successfully.', 'Stok berhasil disesuaikan.');
    }

    public function history(HistoryInventoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filter = $validated['filter'] ?? [];
        $limit = min((int) ($validated['limit'] ?? 20), 100);
        $page = max(1, (int) ($validated['page'] ?? 1));
        $order = $validated['order'] ?? 'created_at';
        $sort = $validated['sort'] ?? 'DESC';

        $query = InventoryHistory::query()
            ->with(['inventory.product.category', 'inventory.product.group', 'createdBy.detail']);

        if (($filter['set_inventory_guid'] ?? false) === true && ! empty($filter['inventory_guid'])) {
            $query->where('inventory_id', $filter['inventory_guid']);
        }

        if (($filter['set_product_guid'] ?? false) === true && ! empty($filter['product_guid'])) {
            $query->where('product_guid', $filter['product_guid']);
        }

        if (($filter['set_type'] ?? false) === true && ! empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }

        if (($filter['set_reference_type'] ?? false) === true && ! empty($filter['reference_type'])) {
            $query->where('reference_type', $filter['reference_type']);
        }

        if (($filter['set_from_date'] ?? false) === true && ! empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (($filter['set_to_date'] ?? false) === true && ! empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        $total = $query->count();
        $items = $query->orderBy($order, $sort)
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn (InventoryHistory $record): array => $this->historyData($record));

        return $this->apiResponse('00', 'success', [
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $limit,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    private function historyData(InventoryHistory $record): array
    {
        return [
            'guid' => $record->guid,
            'inventory_guid' => $record->inventory_id,
            'product_name' => $record->inventory?->product?->name ?? '-',
            'product' => $record->inventory?->product ? [
                'guid' => $record->inventory->product->guid,
                'name' => $record->inventory->product->name,
                'category' => $record->inventory->product->category ? [
                    'guid' => $record->inventory->product->category->guid,
                    'name' => $record->inventory->product->category->name,
                ] : null,
                'group' => $record->inventory->product->group ? [
                    'guid' => $record->inventory->product->group->guid,
                    'name' => $record->inventory->product->group->name,
                ] : null,
            ] : null,
            'guid_cabang' => $record->guid_cabang,
            'cabang_kode' => $this->cabangKode($record->guid_cabang),
            'type' => $record->type,
            'qty' => (float) $record->qty,
            'stock_before' => (float) $record->stock_before,
            'stock_after' => (float) $record->stock_after,
            'reference_type' => $record->reference_type,
            'reference_id' => $record->reference_id,
            'notes' => $record->notes,
            'created_by' => $record->createdBy?->detail?->full_name
                ?? $record->createdBy?->username
                ?? null,
            'user_guid_reff' => $record->user_guid_reff,
            'created_at' => $record->created_at?->toISOString(),
            'updated_at' => $record->updated_at?->toISOString(),
        ];
    }

    private ?\Illuminate\Support\Collection $cabangMap = null;

    private function cabangKode(?string $guid): string
    {
        if (! $guid) {
            return '-';
        }

        if ($this->cabangMap === null) {
            $this->cabangMap = Cabang::pluck('kode', 'guid');
        }

        return $this->cabangMap[$guid] ?? $guid;
    }

}
