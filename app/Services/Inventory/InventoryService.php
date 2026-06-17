<?php

namespace App\Services\Inventory;

use App\Models\InventoryHistory;
use App\Models\ProductInventory;
use Illuminate\Support\Str;

class InventoryService
{
    public function adjustStock(
        ProductInventory $inventory,
        string $type,
        float $qty,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?string $createdBy = null,
        ?string $userGuidReff = null,
    ): InventoryHistory {
        $stockBefore = (float) $inventory->current_stock;

        if ($type === 'out' && $stockBefore < $qty) {
            throw new InsufficientStockException(
                productName: $inventory->product?->name ?? '-',
                currentStock: $stockBefore,
                requiredStock: $qty,
                unit: $inventory->unit,
            );
        }

        $stockAfter = match ($type) {
            'in' => $stockBefore + $qty,
            'out' => $stockBefore - $qty,
            'adjustment' => $qty,
            default => $stockBefore,
        };

        $inventory->update(['current_stock' => $stockAfter]);

        return InventoryHistory::query()->create([
            'guid' => (string) Str::uuid(),
            'inventory_id' => $inventory->guid,
            'product_guid' => $inventory->product_guid,
            'id_cabang' => $inventory->id_cabang,
            'type' => $type,
            'qty' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'is_active' => true,
            'created_by' => $createdBy,
            'user_guid_reff' => $userGuidReff,
        ]);
    }
}
