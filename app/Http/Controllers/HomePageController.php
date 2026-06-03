<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Inertia\Inertia;
use Inertia\Response;

class HomePageController extends Controller
{
    public function index(): Response
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $todayOrders = Order::query()
            ->with('payments')
            ->whereBetween('ordered_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->get();

        $paidPayments = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $salesTotal = (float) $todayOrders->sum('total_amount');
        $cashTotal = (float) $paidPayments->where('method', 'cash')->sum('amount');
        $digitalTotal = (float) $paidPayments->where('method', '!=', 'cash')->sum('amount');

        return Inertia::render('Home/Index', [
            'title' => 'Home',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'dashboard' => [
                'sales_total' => $salesTotal,
                'cash_total' => $cashTotal,
                'digital_total' => $digitalTotal,
                'transactions_today' => $todayOrders->count(),
                'active_shift' => $this->activeShiftDuration($start),
                'pending_payments' => $todayOrders->whereIn('payment_status', ['unpaid', 'partial'])->count(),
                'completed_orders' => $todayOrders->where('status', 'completed')->count(),
                'hourly_sales' => $this->hourlySales($todayOrders),
                'recent_orders' => Order::query()
                    ->with('payments')
                    ->latest('ordered_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Order $order): array => $this->orderData($order)),
            ],
        ]);
    }

    private function activeShiftDuration(CarbonInterface $start): string
    {
        $seconds = $start->diffInSeconds(now());

        return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
    }

    private function hourlySales($orders): array
    {
        $hours = range(8, 15);
        $totals = collect($hours)
            ->mapWithKeys(fn (int $hour): array => [$hour => (float) $orders
                ->filter(fn (Order $order): bool => (int) $order->ordered_at?->format('G') === $hour)
                ->sum('total_amount')]);
        $max = max($totals->max(), 1);

        return $totals
            ->map(fn (float $amount, int $hour): array => [
                'time' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'amount' => $amount,
                'height' => max(8, round(($amount / $max) * 100)).'%',
                'tone' => $amount <= 0 ? 'muted' : ($amount >= $max ? 'strong' : 'medium'),
            ])
            ->values()
            ->all();
    }

    private function orderData(Order $order): array
    {
        $lastPayment = $order->payments->sortByDesc('paid_at')->first();

        return [
            'guid' => $order->guid,
            'code' => '#'.$order->order_number,
            'meta' => trim(($order->ordered_at?->diffForHumans() ?? '-').' - '.($lastPayment?->method ?? $order->payment_status)),
            'amount' => $order->total_amount,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'voided' => $order->status === 'cancelled',
        ];
    }
}
