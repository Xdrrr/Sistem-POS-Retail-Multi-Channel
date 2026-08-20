<?php

namespace Database\Seeders;

use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use App\Models\Order;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $cashierRole = AuthenticationRole::query()->where('name', 'Cashier')->first();

        if (! $cashierRole) {
            return;
        }

        $ahmad = $this->cashier('ahmad@gmail.com', 'Ahmad Fauzi', '123456', $cashierRole);
        $dewi = $this->cashier('dewi@gmail.com', 'Dewi Lestari', '123456', $cashierRole);

        DB::transaction(function () use ($ahmad, $dewi): void {
            $closedShift = $this->makeShift(
                user: $ahmad,
                number: 'SH-SEED-'.now()->subDay()->format('Ymd').'-001',
                openedAt: now()->subDay()->setTime(8, 0),
                closedAt: now()->subDay()->setTime(16, 0),
                workHours: 8,
                openingBalance: 500000,
                status: 'closed',
            );

            $activeShift = $this->makeShift(
                user: $dewi,
                number: 'SH-SEED-'.now()->format('Ymd').'-001',
                openedAt: now()->setTime(8, 0),
                closedAt: null,
                workHours: 8,
                openingBalance: 400000,
                status: 'open',
            );

            $this->attachOrders($closedShift, $ahmad, now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 10);
            $this->attachOrders($activeShift, $dewi, now()->startOfDay(), now()->endOfDay(), 8);
            $this->refreshBalance($closedShift);
            $this->refreshBalance($activeShift);
        });
    }

    private function cashier(string $email, string $name, string $password, AuthenticationRole $role): AuthenticationUser
    {
        $salt = base64_encode(random_bytes(16));
        $user = AuthenticationUser::query()->firstOrCreate(
            ['username' => $email],
            [
                'guid' => (string) Str::uuid(),
                'role_id' => $role->id,
                'password' => $this->passwordHash($password, $salt),
                'salt' => $salt,
                'is_active' => true,
                'url_image' => '',
                'fcm_token' => '',
                'last_login' => null,
                'used_trial' => true,
                'is_verified' => true,
            ],
        );

        $user->detail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone_number' => '',
                'email' => $email,
                'full_name' => $name,
                'gender' => 'Tidak-Spesifik',
                'address' => null,
                'additional_address' => null,
                'city' => '',
                'province' => '',
                'date_of_birth' => null,
            ],
        );

        return $user->refresh();
    }

    private function makeShift(
        AuthenticationUser $user,
        string $number,
        Carbon $openedAt,
        ?Carbon $closedAt,
        float $workHours,
        float $openingBalance,
        string $status,
    ): Shift {
        return Shift::query()->updateOrCreate(
            ['shift_number' => $number],
            [
                'guid' => Shift::query()->where('shift_number', $number)->value('guid') ?? (string) Str::uuid(),
                'user_id' => $user->id,
                'user_guid' => $user->guid,
                'guid_cabang' => 'aaaaaaaa-aaaa-4000-8000-000000000001',
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'work_hours' => $workHours,
                'opening_balance' => $openingBalance,
                'closing_balance' => $status === 'closed' ? $openingBalance : null,
                'expected_balance' => $openingBalance,
                'difference' => $status === 'closed' ? 0 : null,
                'notes' => 'Seed shift demo.',
                'status' => $status,
            ],
        );
    }

    private function attachOrders(Shift $shift, AuthenticationUser $user, Carbon $from, Carbon $to, int $limit): void
    {
        Order::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->orderBy('ordered_at')
            ->limit($limit)
            ->get()
            ->each(fn (Order $order) => $order->update([
                'shift_id' => $shift->id,
                'user_id' => $user->id,
            ]));
    }

    private function refreshBalance(Shift $shift): void
    {
        $cashSales = (float) DB::table('orders.orders as o')
            ->join('orders.payments as p', 'p.order_guid', '=', 'o.guid')
            ->where('o.shift_id', $shift->id)
            ->where('p.method', 'cash')
            ->where('p.status', 'paid')
            ->sum('p.amount');
        $expected = (float) $shift->opening_balance + $cashSales;
        $closing = $shift->status === 'closed' ? $expected : null;

        $shift->update([
            'expected_balance' => $expected,
            'closing_balance' => $closing,
            'difference' => $closing === null ? null : $closing - $expected,
        ]);
    }

    private function passwordHash(string $password, string $salt): string
    {
        return base64_encode(hash('sha256', $password.$salt, true));
    }
}
