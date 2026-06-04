<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Database\Seeder;

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
                        'is_active' => true,
                    ],
                ),
            ]);

        $products = [
            ['guid' => '33333333-3333-4333-8333-000000000001', 'name' => 'Nasi Goreng Special', 'category' => 'makanan', 'group' => 'nasi', 'price' => 28000],
            ['guid' => '33333333-3333-4333-8333-000000000002', 'name' => 'Nasi Goreng Kampung', 'category' => 'makanan', 'group' => 'nasi', 'price' => 24000],
            ['guid' => '33333333-3333-4333-8333-000000000003', 'name' => 'Nasi Ayam Geprek', 'category' => 'makanan', 'group' => 'ayam', 'price' => 26000],
            ['guid' => '33333333-3333-4333-8333-000000000004', 'name' => 'Nasi Ayam Katsu', 'category' => 'makanan', 'group' => 'ayam', 'price' => 32000],
            ['guid' => '33333333-3333-4333-8333-000000000005', 'name' => 'Nasi Telur Sambal Matah', 'category' => 'makanan', 'group' => 'nasi', 'price' => 22000],
            ['guid' => '33333333-3333-4333-8333-000000000006', 'name' => 'Mi Goreng Jawa', 'category' => 'makanan', 'group' => 'mi', 'price' => 25000],
            ['guid' => '33333333-3333-4333-8333-000000000007', 'name' => 'Mi Rebus Soto', 'category' => 'makanan', 'group' => 'mi', 'price' => 23000],
            ['guid' => '33333333-3333-4333-8333-000000000008', 'name' => 'Spaghetti Aglio Olio', 'category' => 'makanan', 'group' => 'pasta', 'price' => 36000],
            ['guid' => '33333333-3333-4333-8333-000000000009', 'name' => 'Spaghetti Bolognese', 'category' => 'makanan', 'group' => 'pasta', 'price' => 38000],
            ['guid' => '33333333-3333-4333-8333-000000000010', 'name' => 'Chicken Popcorn', 'category' => 'snack', 'group' => 'ayam', 'price' => 24000],
            ['guid' => '33333333-3333-4333-8333-000000000011', 'name' => 'Kentang Goreng', 'category' => 'gorengan', 'group' => 'combo', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000012', 'name' => 'Pisang Goreng Coklat', 'category' => 'gorengan', 'group' => 'kue', 'price' => 17000],
            ['guid' => '33333333-3333-4333-8333-000000000013', 'name' => 'Tahu Crispy', 'category' => 'gorengan', 'group' => 'combo', 'price' => 15000],
            ['guid' => '33333333-3333-4333-8333-000000000014', 'name' => 'Roti Bakar Coklat', 'category' => 'snack', 'group' => 'roti', 'price' => 19000],
            ['guid' => '33333333-3333-4333-8333-000000000015', 'name' => 'Roti Bakar Keju', 'category' => 'snack', 'group' => 'roti', 'price' => 21000],
            ['guid' => '33333333-3333-4333-8333-000000000016', 'name' => 'Brownies Slice', 'category' => 'dessert', 'group' => 'kue', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000017', 'name' => 'Cheesecake Mini', 'category' => 'dessert', 'group' => 'kue', 'price' => 26000],
            ['guid' => '33333333-3333-4333-8333-000000000018', 'name' => 'Kopi Espresso', 'category' => 'minuman', 'group' => 'kopi', 'price' => 16000],
            ['guid' => '33333333-3333-4333-8333-000000000019', 'name' => 'Kopi Esspresso', 'category' => 'minuman', 'group' => 'kopi', 'price' => 16000],
            ['guid' => '33333333-3333-4333-8333-000000000020', 'name' => 'Kopi Americano', 'category' => 'minuman', 'group' => 'kopi', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000021', 'name' => 'Kopi Latte', 'category' => 'minuman', 'group' => 'kopi', 'price' => 24000],
            ['guid' => '33333333-3333-4333-8333-000000000022', 'name' => 'Kopi Cappuccino', 'category' => 'minuman', 'group' => 'kopi', 'price' => 25000],
            ['guid' => '33333333-3333-4333-8333-000000000023', 'name' => 'Es Kopi Susu Gula Aren', 'category' => 'minuman', 'group' => 'kopi', 'price' => 23000],
            ['guid' => '33333333-3333-4333-8333-000000000024', 'name' => 'Teh Manis Panas', 'category' => 'minuman', 'group' => 'teh', 'price' => 9000],
            ['guid' => '33333333-3333-4333-8333-000000000025', 'name' => 'Es Teh Lemon', 'category' => 'minuman', 'group' => 'teh', 'price' => 14000],
            ['guid' => '33333333-3333-4333-8333-000000000026', 'name' => 'Thai Tea', 'category' => 'minuman', 'group' => 'teh', 'price' => 19000],
            ['guid' => '33333333-3333-4333-8333-000000000027', 'name' => 'Jus Alpukat', 'category' => 'minuman', 'group' => 'jus', 'price' => 22000],
            ['guid' => '33333333-3333-4333-8333-000000000028', 'name' => 'Jus Jeruk', 'category' => 'minuman', 'group' => 'jus', 'price' => 18000],
            ['guid' => '33333333-3333-4333-8333-000000000029', 'name' => 'Paket Ayam Geprek', 'category' => 'paket hemat', 'group' => 'combo', 'price' => 35000],
            ['guid' => '33333333-3333-4333-8333-000000000030', 'name' => 'Paket Nasi Goreng Kopi', 'category' => 'paket hemat', 'group' => 'combo', 'price' => 42000],
            ['guid' => '33333333-3333-4333-8333-000000000031', 'name' => 'Paket Katsu Tea', 'category' => 'paket hemat', 'group' => 'combo', 'price' => 45000],
        ];

        foreach ($products as $item) {
            Product::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'guid' => $item['guid'],
                    'category_guid' => $categories[$item['category']]->guid,
                    'group_guid' => $groups[$item['group']]->guid,
                    'price' => $item['price'],
                    'is_active' => true,
                ],
            );
        }
    }
}
