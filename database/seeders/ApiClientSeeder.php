<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiClientSeeder extends Seeder
{
    public function run(): void
    {
        ApiClient::query()->updateOrCreate(
            ['app_name' => 'app-dev'],
            [
                'app_key_hash' => Hash::make('app-d3V'),
                'is_active' => true,
            ],
        );
    }
}
