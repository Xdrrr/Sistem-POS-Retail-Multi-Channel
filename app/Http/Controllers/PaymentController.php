<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\IndexPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use Filterable;

    public function index(IndexPaymentRequest $request): JsonResponse
    {
        $query = Payment::query()->with('order');
        $this->applyFilter($request, $query, ['guid', 'payment_number', 'method', 'status']);

        $payments = $query->get()
            ->map(fn (Payment $payment): array => $this->paymentData($payment));

        return $this->apiResponse('00', 'success', $payments);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $payment = DB::transaction(function () use ($validated): Payment {
            $order = Order::query()->where('guid', $validated['order_guid'])->firstOrFail();

            $payment = $order->payments()->create([
                'guid' => (string) Str::uuid(),
                'payment_number' => $this->generatePaymentNumber(),
                'method' => $validated['method'],
                'status' => $validated['status'] ?? 'paid',
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'] ?? now(),
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncPaymentStatus($order);

            return $payment->refresh()->load('order');
        });

        return $this->apiResponse('00', 'success', $this->paymentData($payment), 'Payment created successfully.', 'Pembayaran berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $payment = Payment::query()->with('order')->where('guid', $guid)->first();

        if (! $payment) {
            return $this->apiResponse('01', 'failed', null, 'Payment not found.', 'Pembayaran tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->paymentData($payment));
    }

    private function syncPaymentStatus(Order $order): void
    {
        $paidAmount = (float) $order->payments()
            ->where('status', 'paid')
            ->sum('amount');
        $totalAmount = (float) $order->total_amount;

        $paymentStatus = match (true) {
            $paidAmount <= 0 => 'unpaid',
            $paidAmount < $totalAmount => 'partial',
            default => 'paid',
        };

        $order->update(['payment_status' => $paymentStatus]);
    }

    private function generatePaymentNumber(): string
    {
        return 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    private function paymentData(Payment $payment): array
    {
        return [
            'guid' => $payment->guid,
            'payment_number' => $payment->payment_number,
            'order' => [
                'guid' => $payment->order?->guid,
                'order_number' => $payment->order?->order_number,
                'total_amount' => $payment->order?->total_amount,
                'payment_status' => $payment->order?->payment_status,
            ],
            'method' => $payment->method,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at?->toISOString(),
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'created_at' => $payment->created_at?->toISOString(),
            'updated_at' => $payment->updated_at?->toISOString(),
        ];
    }
}
