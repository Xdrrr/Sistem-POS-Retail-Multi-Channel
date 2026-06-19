<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($products->count() < 8) {
            return;
        }

        $customers = [
            ['name' => 'Budi Santoso', 'phone' => '081234567890'],
            ['name' => 'Siti Aminah', 'phone' => '081234567891'],
            ['name' => 'Raka Pratama', 'phone' => '081234567892'],
            ['name' => 'Dewi Lestari', 'phone' => '081234567893'],
            ['name' => 'Ahmad Fauzi', 'phone' => '081234567894'],
            ['name' => 'Maya Putri', 'phone' => '081234567895'],
            ['name' => 'Andi Wijaya', 'phone' => '081234567896'],
            ['name' => 'Nadia Kirana', 'phone' => '081234567897'],
            ['name' => 'Rizky Hidayat', 'phone' => '081234567898'],
            ['name' => 'Walk-in', 'phone' => null],
        ];

        $methods = ['cash', 'debit_card', 'credit_card', 'qris', 'transfer', 'e_wallet'];
        $orderTypes = ['dine_in', 'takeaway', 'delivery'];
        $statuses = ['completed', 'completed', 'completed', 'completed', 'open', 'cancelled'];
        $paymentStatuses = [
            'completed' => ['paid', 'paid', 'paid', 'partial'],
            'open' => ['unpaid', 'partial'],
            'cancelled' => ['unpaid', 'refunded'],
        ];

        for ($day = 0; $day < 45; $day++) {
            $date = now()->subDays($day);
            $ordersPerDay = 6 + ($day % 7);

            for ($slot = 1; $slot <= $ordersPerDay; $slot++) {
                $index = ($day * 12) + $slot;
                $status = $statuses[$index % count($statuses)];
                $paymentStatus = $paymentStatuses[$status][$index % count($paymentStatuses[$status])];
                $customer = $customers[$index % count($customers)];
                $orderedAt = Carbon::parse($date)
                    ->setTime(8 + ($slot % 12), ($slot * 7) % 60, 0);

                $items = $this->buildItems($products, $index);
                $subtotal = $items->sum('subtotal');
                $discount = $index % 5 === 0 ? min(10000, round($subtotal * 0.08, -2)) : 0;
                $tax = $status === 'cancelled' ? 0 : round(max(0, $subtotal - $discount) * 0.1, -2);
                $total = $status === 'cancelled' ? 0 : max(0, $subtotal - $discount + $tax);
                $orderNumber = 'ORD-RPT-'.$orderedAt->format('Ymd').'-'.str_pad((string) $slot, 3, '0', STR_PAD_LEFT);

                $order = Order::query()->updateOrCreate(
                    ['order_number' => $orderNumber],
                    [
                        'guid' => Order::query()->where('order_number', $orderNumber)->value('guid') ?? (string) Str::uuid(),
                        'guid_cabang' => 'aaaaaaaa-aaaa-4000-8000-000000000001',
                        'customer_name' => $customer['name'] === 'Walk-in' ? null : $customer['name'],
                        'customer_phone' => $customer['phone'],
                        'order_type' => $orderTypes[$index % count($orderTypes)],
                        'table_number' => in_array($orderTypes[$index % count($orderTypes)], ['takeaway', 'delivery']) ? null : ($slot % 3 === 0 ? null : chr(65 + ($slot % 5)).($slot % 12 + 1)),
                        'status' => $status,
                        'payment_status' => $paymentStatus,
                        'subtotal' => $subtotal,
                        'discount_amount' => $discount,
                        'tax_amount' => $tax,
                        'total_amount' => $total,
                        'notes' => $status === 'cancelled' ? 'Order dibatalkan untuk contoh report.' : 'Data seed report.',
                        'ordered_at' => $orderedAt,
                    ],
                );

                $order->items()->delete();
                $order->payments()->delete();
                $order->items()->createMany($items->all());
                $this->createPayments($order, $methods, $paymentStatus, $total, $orderedAt, $index);
            }
        }
    }

    private function buildItems(Collection $products, int $seed): Collection
    {
        $count = 1 + ($seed % 4);

        return collect(range(0, $count - 1))->map(function (int $offset) use ($products, $seed): array {
            $product = $products[($seed + ($offset * 3)) % $products->count()];
            $quantity = 1 + (($seed + $offset) % 3);
            $unitPrice = (float) $product->price;
            $discount = ($seed + $offset) % 9 === 0 ? 2000 : 0;

            return [
                'guid' => (string) Str::uuid(),
                'product_guid' => $product->guid,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'subtotal' => max(0, ($quantity * $unitPrice) - $discount),
                'notes' => $offset === 0 && $seed % 6 === 0 ? 'Catatan item seed.' : null,
            ];
        });
    }

    private function createPayments(Order $order, array $methods, string $paymentStatus, float $total, Carbon $orderedAt, int $seed): void
    {
        if ($total <= 0 && $paymentStatus !== 'refunded') {
            return;
        }

        $method = $methods[$seed % count($methods)];
        $paidAt = $orderedAt->copy()->addMinutes(5 + ($seed % 25));

        if ($paymentStatus === 'unpaid') {
            return;
        }

        if ($paymentStatus === 'partial') {
            $amount = round($total * 0.5, -2);
            $this->createPayment($order, $method, 'paid', $amount, $paidAt, $seed);

            return;
        }

        if ($paymentStatus === 'refunded') {
            $this->createPayment($order, $method, 'refunded', max(0, $total), $paidAt, $seed);

            return;
        }

        if ($seed % 8 === 0 && $total > 30000) {
            $cashAmount = round($total * 0.4, -2);
            $this->createPayment($order, 'cash', 'paid', $cashAmount, $paidAt, $seed);
            $this->createPayment($order, $methods[($seed + 2) % count($methods)], 'paid', $total - $cashAmount, $paidAt->copy()->addMinutes(2), $seed + 5000);

            return;
        }

        $this->createPayment($order, $method, 'paid', $total, $paidAt, $seed);
    }

    private function createPayment(Order $order, string $method, string $status, float $amount, Carbon $paidAt, int $seed): void
    {
        $order->payments()->create([
            'guid' => (string) Str::uuid(),
            'payment_number' => 'PAY-RPT-'.$paidAt->format('YmdHis').'-'.str_pad((string) ($seed % 10000), 4, '0', STR_PAD_LEFT),
            'method' => $method,
            'status' => $status,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'reference_number' => strtoupper($method).'-RPT-'.str_pad((string) $seed, 5, '0', STR_PAD_LEFT),
            'notes' => 'Payment seed report.',
        ]);
    }
}
