<?php

namespace Database\Seeders;

use App\Models\AuthenticationRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AuthenticationRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Superadmin', 'is_default' => false],
            ['name' => 'Users', 'is_default' => true],
        ] as $role) {
            AuthenticationRole::query()->firstOrCreate(
                ['name' => $role['name']],
                [
                    'guid' => (string) Str::uuid(),
                    'is_default' => $role['is_default'],
                ],
            );
        }
    }
}
