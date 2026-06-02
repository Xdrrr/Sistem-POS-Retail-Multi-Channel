<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['makanan', 'minuman', 'gorengan'])
            ->mapWithKeys(fn (string $name): array => [
                $name => Category::query()->firstOrCreate(
                    ['name' => $name],
                    [
                        'guid' => (string) Str::uuid(),
                        'is_active' => true,
                    ],
                ),
            ]);

        $groups = collect(['kopi', 'nasi', 'mi', 'pasta'])
            ->mapWithKeys(fn (string $name): array => [
                $name => ProductGroup::query()->firstOrCreate(
                    ['name' => $name],
                    [
                        'guid' => (string) Str::uuid(),
                        'is_active' => true,
                    ],
                ),
            ]);

        $products = [
            ['name' => 'Nasi Goreng Special', 'category' => 'makanan', 'group' => 'nasi'],
            ['name' => 'Mi Goreng Jawa', 'category' => 'makanan', 'group' => 'mi'],
            ['name' => 'Kopi Esspresso', 'category' => 'minuman', 'group' => 'kopi'],
            ['name' => 'Kopi Americano', 'category' => 'minuman', 'group' => 'kopi'],
        ];

        foreach ($products as $item) {
            Product::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'guid' => Product::query()->where('name', $item['name'])->value('guid') ?? (string) Str::uuid(),
                    'category_id' => $categories[$item['category']]->id,
                    'group_id' => $groups[$item['group']]->id,
                    'price' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
