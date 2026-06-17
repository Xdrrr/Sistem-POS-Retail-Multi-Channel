<?php

namespace App\Services\Inventory;

use Exception;

class InsufficientStockException extends Exception
{
    public readonly string $productName;
    public readonly float $currentStock;
    public readonly float $requiredStock;
    public readonly string $unit;

    public function __construct(
        string $productName = '',
        float $currentStock = 0,
        float $requiredStock = 0,
        string $unit = 'pcs',
    ) {
        $this->productName = $productName;
        $this->currentStock = $currentStock;
        $this->requiredStock = $requiredStock;
        $this->unit = $unit;

        parent::__construct("Insufficient stock for {$productName}: available {$currentStock}, required {$requiredStock} {$unit}");
    }
}
