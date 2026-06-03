<?php

namespace Database\Seeders;

use App\Models\AuthenticationRole;
use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthenticationUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = AuthenticationRole::query()->where('name', 'Superadmin')->firstOrFail();
        $salt = base64_encode(random_bytes(16));

        DB::transaction(function () use ($role, $salt): void {
            $user = AuthenticationUser::query()->firstOrCreate(
                ['username' => 'xander@wit.id'],
                [
                    'guid' => (string) Str::uuid(),
                    'role_id' => $role->id,
                    'password' => $this->passwordHash('wit.id', $salt),
                    'salt' => $salt,
                    'is_active' => true,
                    'url_image' => 'https://storage.googleapis.com/gavra-invest-storage-production/b55ccd10-0ab2-4462-ba78-4d47367fe3f3.jpg',
                    'fcm_token' => 'token',
                    'last_login' => null,
                    'used_trial' => true,
                    'is_verified' => true,
                ],
            );

            $user->detail()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone_number' => '02212',
                    'email' => 'xander@wit.id',
                    'full_name' => 'Xander',
                    'gender' => 'Laki-laki',
                    'address' => 'WIT',
                    'additional_address' => null,
                    'city' => '',
                    'province' => '',
                    'date_of_birth' => '2000-01-01',
                ],
            );

            AuthenticationSession::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['guid' => (string) Str::uuid()],
            );
        });
    }

    private function passwordHash(string $password, string $salt): string
    {
        return base64_encode(hash('sha256', $password.$salt, true));
    }
}
