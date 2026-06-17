<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['guid' => '11111111-1111-4111-8111-000000000001', 'name' => 'makanan'],
            ['guid' => '11111111-1111-4111-8111-000000000002', 'name' => 'minuman'],
            ['guid' => '11111111-1111-4111-8111-000000000003', 'name' => 'gorengan'],
            ['guid' => '11111111-1111-4111-8111-000000000004', 'name' => 'dessert'],
            ['guid' => '11111111-1111-4111-8111-000000000005', 'name' => 'paket hemat'],
            ['guid' => '11111111-1111-4111-8111-000000000006', 'name' => 'snack'],
        ])
            ->mapWithKeys(fn (array $item): array => [
                $item['name'] => Category::query()->updateOrCreate(
                    ['name' => $item['name']],
                    [
                        'guid' => $item['guid'],
                        'image' => $this->imagePath('categories', $item['name']),
                        'is_active' => true,
                    ],
                ),
            ]);

        $groups = collect([
            ['guid' => '22222222-2222-4222-8222-000000000001', 'name' => 'kopi'],
            ['guid' => '22222222-2222-4222-8222-000000000002', 'name' => 'nasi'],
            ['guid' => '22222222-2222-4222-8222-000000000003', 'name' => 'mi'],
            ['guid' => '22222222-2222-4222-8222-000000000004', 'name' => 'pasta'],
            ['guid' => '22222222-2222-4222-8222-000000000005', 'name' => 'ayam'],
            ['guid' => '22222222-2222-4222-8222-000000000006', 'name' => 'teh'],
            ['guid' => '22222222-2222-4222-8222-000000000007', 'name' => 'jus'],
            ['guid' => '22222222-2222-4222-8222-000000000008', 'name' => 'roti'],
            ['guid' => '22222222-2222-4222-8222-000000000009', 'name' => 'kue'],
            ['guid' => '22222222-2222-4222-8222-000000000010', 'name' => 'combo'],
        ])
            ->mapWithKeys(fn (array $item): array => [
                $item['name'] => ProductGroup::query()->updateOrCreate(
                    ['name' => $item['name']],
                    [
                        'guid' => $item['guid'],
                        'image' => $this->imagePath('groups', $item['name']),
                        'is_active' => true,
                    ],
                ),
            ]);

        $products = [
            ['guid' => '33333333-3333-4333-8333-000000000001', 'sku' => 'SKU-001', 'name' => 'Nasi Goreng Special', 'category' => 'makanan', 'group' => 'nasi', 'price' => 28000],
            ['guid' => '33333333-3333-4333-8333-000000000002', 'sku' => 'SKU-002', 'name' => 'Nasi Goreng Kampung', 'category' => 'makanan', 'group' => 'nasi', 'price' => 24000],
            ['guid' => '33333333-3333-4333-8333-000000000003', 'sku' => 'SKU-003', 'name' => 'Nasi Ayam Geprek', 'category' => 'makanan', 'group' => 'ayam', 'price' => 26000],
            ['guid' => '33333333-3333-4333-8333-000000000004', 'sku' => 'SKU-004', 'name' => 'Nasi Ayam Katsu', 'category' => 'makanan', 'group' => 'ayam', 'price' => 32000],
            ['guid' => '33333333-3333-4333-8333-000000000005', 'sku' => 'SKU-005', 'name' => 'Nasi Telur Sambal Matah', 'category' => 'makanan', 'group' => 'nasi', 'price' => 22000],
            ['guid' => '33333333-3333-4333-8333-000000000006', 'sku' => 'SKU-006', 'name' => 'Mi Goreng Jawa', 'category' => 'makanan', 'group' => 'mi', 'price' => 25000],
            ['guid' => '33333333-3333-4333-8333-000000000007', 'sku' => 'SKU-007', 'name' => 'Mi Rebus Soto', 'category' => 'makanan', 'group' => 'mi', 'price' => 23000],
            ['guid' => '33333333-3333-4333-8333-000000000008', 'sku' => 'SKU-008', 'name' => 'Spaghetti Aglio Olio', 'category' => 'makanan', 'group' => 'pasta', 'price' => 36000],
            ['guid' => '33333333-3333-4333-8333-000000000009', 'sku' => 'SKU-009', 'name' => 'Spaghetti Bolognese', 'category' => 'makanan', 'group' => 'pasta', 'price' => 38000],
            ['guid' => '33333333-3333-4333-8333-000000000010', 'sku' => 'SKU-010', 'name' => 'Chicken Popcorn', 'category' => 'snack', 'group' => 'ayam', 'price' => 24000],
            ['guid' => '33333333-3333-4333-8333-000000000011', 'sku' => 'SKU-011', 'name' => 'Kentang Goreng', 'category' => 'gorengan', 'group' => 'combo', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000012', 'sku' => 'SKU-012', 'name' => 'Pisang Goreng Coklat', 'category' => 'gorengan', 'group' => 'kue', 'price' => 17000],
            ['guid' => '33333333-3333-4333-8333-000000000013', 'sku' => 'SKU-013', 'name' => 'Tahu Crispy', 'category' => 'gorengan', 'group' => 'combo', 'price' => 15000],
            ['guid' => '33333333-3333-4333-8333-000000000014', 'sku' => 'SKU-014', 'name' => 'Roti Bakar Coklat', 'category' => 'snack', 'group' => 'roti', 'price' => 19000],
            ['guid' => '33333333-3333-4333-8333-000000000015', 'sku' => 'SKU-015', 'name' => 'Roti Bakar Keju', 'category' => 'snack', 'group' => 'roti', 'price' => 21000],
            ['guid' => '33333333-3333-4333-8333-000000000016', 'sku' => 'SKU-016', 'name' => 'Brownies Slice', 'category' => 'dessert', 'group' => 'kue', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000017', 'sku' => 'SKU-017', 'name' => 'Cheesecake Mini', 'category' => 'dessert', 'group' => 'kue', 'price' => 26000],
            ['guid' => '33333333-3333-4333-8333-000000000018', 'sku' => 'SKU-018', 'name' => 'Kopi Espresso', 'category' => 'minuman', 'group' => 'kopi', 'price' => 16000],
            ['guid' => '33333333-3333-4333-8333-000000000019', 'sku' => 'SKU-019', 'name' => 'Kopi Esspresso', 'category' => 'minuman', 'group' => 'kopi', 'price' => 16000],
            ['guid' => '33333333-3333-4333-8333-000000000020', 'sku' => 'SKU-020', 'name' => 'Kopi Americano', 'category' => 'minuman', 'group' => 'kopi', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000021', 'sku' => 'SKU-021', 'name' => 'Kopi Latte', 'category' => 'minuman', 'group' => 'kopi', 'price' => 24000],
            ['guid' => '33333333-3333-4333-8333-000000000022', 'sku' => 'SKU-022', 'name' => 'Kopi Cappuccino', 'category' => 'minuman', 'group' => 'kopi', 'price' => 25000],
            ['guid' => '33333333-3333-4333-8333-000000000023', 'sku' => 'SKU-023', 'name' => 'Es Kopi Susu Gula Aren', 'category' => 'minuman', 'group' => 'kopi', 'price' => 23000],
            ['guid' => '33333333-3333-4333-8333-000000000024', 'sku' => 'SKU-024', 'name' => 'Teh Manis Panas', 'category' => 'minuman', 'group' => 'teh', 'price' => 9000],
            ['guid' => '33333333-3333-4333-8333-000000000025', 'sku' => 'SKU-025', 'name' => 'Es Teh Lemon', 'category' => 'minuman', 'group' => 'teh', 'price' => 14000],
            ['guid' => '33333333-3333-4333-8333-000000000026', 'sku' => 'SKU-026', 'name' => 'Thai Tea', 'category' => 'minuman', 'group' => 'teh', 'price' => 19000],
            ['guid' => '33333333-3333-4333-8333-000000000027', 'sku' => 'SKU-027', 'name' => 'Jus Alpukat', 'category' => 'minuman', 'group' => 'jus', 'price' => 22000],
            ['guid' => '33333333-3333-4333-8333-000000000028', 'sku' => 'SKU-028', 'name' => 'Jus Jeruk', 'category' => 'minuman', 'group' => 'jus', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000029', 'sku' => 'SKU-029', 'name' => 'Paket Ayam Geprek', 'category' => 'paket hemat', 'group' => 'combo', 'price' => 35000],
            ['guid' => '33333333-3333-4333-8333-000000000030', 'sku' => 'SKU-030', 'name' => 'Paket Nasi Goreng Kopi', 'category' => 'paket hemat', 'group' => 'combo', 'price' => 42000],
            ['guid' => '33333333-3333-4333-8333-000000000031', 'sku' => 'SKU-031', 'name' => 'Paket Katsu Tea', 'category' => 'paket hemat', 'group' => 'combo', 'price' => 45000],
        ];

        foreach ($products as $item) {
            $product = Product::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'guid' => $item['guid'],
                    'sku' => $item['sku'],
                    'category_guid' => $categories[$item['category']]->guid,
                    'group_guid' => $groups[$item['group']]->guid,
                    'image' => $this->imagePath('products', $item['name']),
                    'price' => $item['price'],
                    'guid_cabang' => 'aaaaaaaa-aaaa-4000-8000-000000000001',
                    'is_active' => true,
                ],
            );

            $inventory = ProductInventory::query()->firstOrNew([
                'product_guid' => $product->guid,
                'guid_cabang' => 'aaaaaaaa-aaaa-4000-8000-000000000001',
            ]);

            if (! $inventory->exists) {
                $inventory->guid = (string) Str::uuid();
            }

            $inventory->fill([
                'unit' => 'pcs',
                'current_stock' => 0,
                'minimum_stock' => 0,
                'is_active' => true,
            ])->save();
        }
    }

    private function imagePath(string $folder, string $name): string
    {
        return "catalog/seed/{$folder}/".Str::slug($name).'.png';
    }
}
