<?php

namespace Database\Seeders;

use App\Models\Cabang;
use Illuminate\Database\Seeder;

class CabangSeeder extends Seeder
{
    public function run(): void
    {
        $cabang = [
            ['guid' => 'aaaaaaaa-aaaa-4000-8000-000000000001', 'kode' => 'PUSAT', 'nama' => 'Pusat'],
            ['guid' => 'aaaaaaaa-aaaa-4000-8000-000000000002', 'kode' => 'CBG1', 'nama' => 'Cabang 1'],
            ['guid' => 'aaaaaaaa-aaaa-4000-8000-000000000003', 'kode' => 'CBG2', 'nama' => 'Cabang 2'],
        ];

        foreach ($cabang as $item) {
            Cabang::query()->updateOrCreate(
                ['kode' => $item['kode']],
                $item,
            );
        }
    }
}
