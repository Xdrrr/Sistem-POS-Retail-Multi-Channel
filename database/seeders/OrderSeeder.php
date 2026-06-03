<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()
            ->whereIn('name', ['Nasi Goreng Special', 'Kopi Americano'])
            ->get()
            ->keyBy('name');

        if ($products->count() < 2) {
            return;
        }

        $order = Order::query()->updateOrCreate(
            ['order_number' => 'ORD-SEED-0001'],
            [
                'guid' => Order::query()->where('order_number', 'ORD-SEED-0001')->value('guid') ?? (string) Str::uuid(),
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081234567890',
                'table_number' => 'A1',
                'order_type' => 'dine_in',
                'status' => 'completed',
                'payment_status' => 'paid',
                'subtotal' => 50000,
                'discount_amount' => 5000,
                'tax_amount' => 4500,
                'total_amount' => 49500,
                'notes' => 'Contoh order seed lunas.',
                'ordered_at' => now(),
            ],
        );

        $order->items()->delete();
        $order->payments()->delete();

        $order->items()->createMany([
            [
                'guid' => (string) Str::uuid(),
                'product_guid' => $products['Nasi Goreng Special']->guid,
                'product_name' => $products['Nasi Goreng Special']->name,
                'quantity' => 2,
                'unit_price' => 20000,
                'discount_amount' => 5000,
                'subtotal' => 35000,
                'notes' => 'Pedas sedang.',
            ],
            [
                'guid' => (string) Str::uuid(),
                'product_guid' => $products['Kopi Americano']->guid,
                'product_name' => $products['Kopi Americano']->name,
                'quantity' => 1,
                'unit_price' => 15000,
                'discount_amount' => 0,
                'subtotal' => 15000,
                'notes' => null,
            ],
        ]);

        $order->payments()->create([
            'guid' => (string) Str::uuid(),
            'payment_number' => 'PAY-SEED-0001',
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 49500,
            'paid_at' => now(),
            'reference_number' => 'CASH-SEED-0001',
            'notes' => 'Pembayaran tunai contoh.',
        ]);
    }
}
