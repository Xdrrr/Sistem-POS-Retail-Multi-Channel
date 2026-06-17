<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TableReservationSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $pusatGuid = 'aaaaaaaa-aaaa-4000-8000-000000000001';

        $tableA1 = RestaurantTable::where('table_number', 'A1')->value('guid');
        $tableB2 = RestaurantTable::where('table_number', 'B2')->value('guid');
        $tableA3 = RestaurantTable::where('table_number', 'A3')->value('guid');

        $reservations = [
            [
                'table_guid' => $tableA1,
                'table_number' => 'A1',
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081234567890',
                'guest_count' => 4,
                'reservation_date' => $today->format('Y-m-d'),
                'reservation_time' => '12:00',
                'status' => 'confirmed',
            ],
            [
                'table_guid' => $tableB2,
                'table_number' => 'B2',
                'customer_name' => 'Siti Rahayu',
                'customer_phone' => '081298765432',
                'guest_count' => 2,
                'reservation_date' => $today->format('Y-m-d'),
                'reservation_time' => '13:30',
                'status' => 'pending',
            ],
            [
                'table_guid' => $tableA3,
                'table_number' => 'A3',
                'customer_name' => 'Ahmad Fauzi',
                'customer_phone' => '085611223344',
                'guest_count' => 6,
                'reservation_date' => $today->addDay()->format('Y-m-d'),
                'reservation_time' => '19:00',
                'status' => 'confirmed',
            ],
        ];

        foreach ($reservations as $item) {
            TableReservation::query()->create([
                'guid' => (string) Str::uuid(),
                'table_guid' => $item['table_guid'],
                'table_number' => $item['table_number'],
                'customer_name' => $item['customer_name'],
                'customer_phone' => $item['customer_phone'],
                'guest_count' => $item['guest_count'],
                'reservation_date' => $item['reservation_date'],
                'reservation_time' => $item['reservation_time'],
                'notes' => null,
                'status' => $item['status'],
                'guid_cabang' => $pusatGuid,
                'is_active' => true,
            ]);
        }
    }
}
