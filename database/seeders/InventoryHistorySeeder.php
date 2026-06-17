<?php

namespace Database\Seeders;

use App\Models\AuthenticationUser;
use App\Models\InventoryHistory;
use App\Models\ProductInventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryHistorySeeder extends Seeder
{
    private const STOCK_MAP = [
        '33333333-3333-4333-8333-000000000001' => 35, // Nasi Goreng Special
        '33333333-3333-4333-8333-000000000002' => 25, // Nasi Goreng Kampung
        '33333333-3333-4333-8333-000000000003' => 40, // Nasi Ayam Geprek
        '33333333-3333-4333-8333-000000000004' => 20, // Nasi Ayam Katsu
        '33333333-3333-4333-8333-000000000005' => 25, // Nasi Telur Sambal Matah
        '33333333-3333-4333-8333-000000000006' => 30, // Mi Goreng Jawa
        '33333333-3333-4333-8333-000000000007' => 25, // Mi Rebus Soto
        '33333333-3333-4333-8333-000000000008' => 15, // Spaghetti Aglio Olio
        '33333333-3333-4333-8333-000000000009' => 15, // Spaghetti Bolognese
        '33333333-3333-4333-8333-000000000010' => 30, // Chicken Popcorn
        '33333333-3333-4333-8333-000000000011' => 25, // Kentang Goreng
        '33333333-3333-4333-8333-000000000012' => 20, // Pisang Goreng Coklat
        '33333333-3333-4333-8333-000000000013' => 25, // Tahu Crispy
        '33333333-3333-4333-8333-000000000014' => 15, // Roti Bakar Coklat
        '33333333-3333-4333-8333-000000000015' => 15, // Roti Bakar Keju
        '33333333-3333-4333-8333-000000000016' => 12, // Brownies Slice
        '33333333-3333-4333-8333-000000000017' => 10, // Cheesecake Mini
        '33333333-3333-4333-8333-000000000018' => 50, // Kopi Espresso
        '33333333-3333-4333-8333-000000000019' => 50, // Kopi Esspresso
        '33333333-3333-4333-8333-000000000020' => 45, // Kopi Americano
        '33333333-3333-4333-8333-000000000021' => 40, // Kopi Latte
        '33333333-3333-4333-8333-000000000022' => 40, // Kopi Cappuccino
        '33333333-3333-4333-8333-000000000023' => 60, // Es Kopi Susu Gula Aren
        '33333333-3333-4333-8333-000000000024' => 50, // Teh Manis Panas
        '33333333-3333-4333-8333-000000000025' => 35, // Es Teh Lemon
        '33333333-3333-4333-8333-000000000026' => 30, // Thai Tea
        '33333333-3333-4333-8333-000000000027' => 20, // Jus Alpukat
        '33333333-3333-4333-8333-000000000028' => 25, // Jus Jeruk
        '33333333-3333-4333-8333-000000000029' => 8,  // Paket Ayam Geprek
        '33333333-3333-4333-8333-000000000030' => 5,  // Paket Nasi Goreng Kopi
        '33333333-3333-4333-8333-000000000031' => 5,  // Paket Katsu Tea
    ];

    public function run(): void
    {
        $user = AuthenticationUser::query()->whereHas('role', fn ($q) => $q->where('name', 'Superadmin'))->first();

        if (! $user) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $inventories = ProductInventory::query()
                ->with('product')
                ->where('guid_cabang', 'aaaaaaaa-aaaa-4000-8000-000000000001')
                ->get();

            foreach ($inventories as $inventory) {
                $qty = self::STOCK_MAP[$inventory->product_guid] ?? 10;

                if ($inventory->current_stock > 0) {
                    continue;
                }

                InventoryHistory::query()->create([
                    'guid' => (string) Str::uuid(),
                    'inventory_id' => $inventory->guid,
                    'product_guid' => $inventory->product_guid,
                    'guid_cabang' => $inventory->guid_cabang,
                    'type' => 'in',
                    'qty' => $qty,
                    'stock_before' => 0,
                    'stock_after' => $qty,
                    'reference_type' => 'manual_adjustment',
                    'reference_id' => null,
                    'notes' => 'Stok awal dari seeder',
                    'is_active' => true,
                    'created_by' => $user->guid,
                ]);

                $inventory->update(['current_stock' => $qty]);
            }
        });
    }
}
