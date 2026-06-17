<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantTableSeeder extends Seeder
{
    public function run(): void
    {
        $pusatGuid = 'aaaaaaaa-aaaa-4000-8000-000000000001';
        $nextGuid = fn () => (string) Str::uuid();

        $tables = [];

        // Indoor A1–A8
        for ($i = 1; $i <= 8; $i++) {
            $tables[] = [
                'guid' => $nextGuid(),
                'table_number' => "A{$i}",
                'capacity' => $i <= 4 ? 4 : 6,
                'location' => 'indoor',
                'status' => 'available',
                'guid_cabang' => $pusatGuid,
            ];
        }

        // Outdoor B1–B8
        for ($i = 1; $i <= 8; $i++) {
            $tables[] = [
                'guid' => $nextGuid(),
                'table_number' => "B{$i}",
                'capacity' => $i <= 4 ? 4 : 6,
                'location' => 'outdoor',
                'status' => 'available',
                'guid_cabang' => $pusatGuid,
            ];
        }

        foreach ($tables as $item) {
            RestaurantTable::query()->updateOrCreate(
                ['table_number' => $item['table_number']],
                $item,
            );
        }
    }
}
