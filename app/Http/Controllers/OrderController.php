<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use App\Services\Shifts\ShiftService;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use Filterable;

    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:order_number,customer_name,order_type,status,payment_status,total_amount,ordered_at,created_at,updated_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        $query = Order::query()->with(['items.product', 'payments', 'shift', 'cashier.detail']);
        $this->applyFilter($request, $query, ['guid', 'order_number', 'status', 'payment_status', 'order_type']);

        $orders = $query->get()
            ->map(fn (Order $order): array => $this->orderData($order));

        return $this->apiResponse('00', 'success', $orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules(includePayments: true));

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();

        $user = $this->authenticatedUser($request);
        $shift = null;

        if (! empty($validated['shift_guid'])) {
            if (! $user) {
                return $this->apiResponse('01', 'failed', null, 'User session not found.', 'Sesi user tidak ditemukan.', 401);
            }

            $shift = $this->shifts->attachOrder($validated['shift_guid'], $user);

            if (! $shift) {
                return $this->apiResponse('04', 'failed', null, 'Active shift not found for this user.', 'Shift aktif untuk user ini tidak ditemukan.', 409);
            }
        }

        $order = DB::transaction(function () use ($validated, $user, $shift): Order {
            $items = $this->prepareItems($validated['items']);
            $subtotal = collect($items)->sum('subtotal');
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxAmount = $validated['tax_amount'] ?? 0;
            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount);

            $order = Order::query()->create([
                'guid' => (string) Str::uuid(),
                'order_number' => $validated['order_number'] ?? $this->generateOrderNumber(),
                'shift_id' => $shift?->id,
                'user_id' => $user?->id,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'table_number' => $validated['table_number'] ?? null,
                'order_type' => $validated['order_type'] ?? 'dine_in',
                'status' => $validated['status'] ?? 'open',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'ordered_at' => $validated['ordered_at'] ?? now(),
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            foreach ($validated['payments'] ?? [] as $payment) {
                $order->payments()->create($this->paymentPayload($payment));
            }

            $this->syncPaymentStatus($order);

            return $order->refresh()->load(['items.product', 'payments']);
        });

        return $this->apiResponse('00', 'success', $this->orderData($order), 'Order created successfully.', 'Order berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $order = $this->findOrder($guid);

        if (! $order) {
            return $this->apiResponse('01', 'failed', null, 'Order not found.', 'Order tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->orderData($order));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'guid' => ['required', 'string', Rule::exists(Order::class, 'guid')],
        ]);

        $order = $this->findOrder($request->string('guid')->toString());

        if (! $order) {
            return $this->apiResponse('01', 'failed', null, 'Order not found.', 'Order tidak ditemukan.', 404);
        }

        if ($order->status === 'completed') {
            return $this->apiResponse('02', 'failed', null, 'Completed orders cannot be updated.', 'Order yang sudah selesai tidak dapat diperbarui.', 409);
        }

        $validator = Validator::make($request->all(), $this->rules($order, includePayments: false));

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();

        $order = DB::transaction(function () use ($order, $validated): Order {
            $items = $this->prepareItems($validated['items']);
            $subtotal = collect($items)->sum('subtotal');
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxAmount = $validated['tax_amount'] ?? 0;
            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount);

            $order->update([
                'order_number' => $validated['order_number'] ?? $order->order_number,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'table_number' => $validated['table_number'] ?? null,
                'order_type' => $validated['order_type'] ?? $order->order_type,
                'status' => $validated['status'] ?? $order->status,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'ordered_at' => $validated['ordered_at'] ?? $order->ordered_at,
            ]);

            $order->items()->delete();
            foreach ($items as $item) {
                $order->items()->create($item);
            }

            $this->syncPaymentStatus($order);

            return $order->refresh()->load(['items.product', 'payments']);
        });

        return $this->apiResponse('00', 'success', $this->orderData($order), 'Order updated successfully.', 'Order berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $order = $this->findOrder($guid);

        if (! $order) {
            return $this->apiResponse('01', 'failed', null, 'Order not found.', 'Order tidak ditemukan.', 404);
        }

        if ($order->payments()->where('status', 'paid')->exists()) {
            return $this->apiResponse('02', 'failed', null, 'Paid orders cannot be deleted.', 'Order yang sudah dibayar tidak dapat dihapus.', 409);
        }

        $order->delete();

        return $this->apiResponse('00', 'success', null, 'Order deleted successfully.', 'Order berhasil dihapus.');
    }

    private function findOrder(string $guid): ?Order
    {
        return Order::query()
            ->with(['items.product', 'payments', 'shift', 'cashier.detail'])
            ->where('guid', $guid)
            ->first();
    }

    private function rules(?Order $order = null, bool $includePayments = false): array
    {
        $rules = [
            'order_number' => ['nullable', 'string', 'max:30', Rule::unique(Order::class, 'order_number')->ignore($order?->id)],
            'shift_guid' => ['nullable', 'string'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'table_number' => ['nullable', 'string', 'max:30'],
            'order_type' => ['nullable', 'string', 'in:dine_in,takeaway,delivery'],
            'status' => ['nullable', 'string', 'in:draft,open,completed,cancelled'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'ordered_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];

        if (! $includePayments) {
            return $rules;
        }

        return [
            ...$rules,
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'string', 'in:cash,debit_card,credit_card,qris,transfer,e_wallet'],
            'payments.*.status' => ['nullable', 'string', 'in:pending,paid,failed,refunded'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.paid_at' => ['nullable', 'date'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }

    private function prepareItems(array $items): array
    {
        return collect($items)->map(function (array $item): array {
            $product = Product::query()->where('guid', $item['product_guid'])->firstOrFail();
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) ($item['unit_price'] ?? $product->price);
            $discountAmount = (float) ($item['discount_amount'] ?? 0);

            return [
                'guid' => (string) Str::uuid(),
                'product_guid' => $product->guid,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'subtotal' => max(0, ($quantity * $unitPrice) - $discountAmount),
                'notes' => $item['notes'] ?? null,
            ];
        })->all();
    }

    private function paymentPayload(array $payment): array
    {
        return [
            'guid' => (string) Str::uuid(),
            'payment_number' => $this->generatePaymentNumber(),
            'method' => $payment['method'],
            'status' => $payment['status'] ?? 'paid',
            'amount' => $payment['amount'],
            'paid_at' => $payment['paid_at'] ?? now(),
            'reference_number' => $payment['reference_number'] ?? null,
            'notes' => $payment['notes'] ?? null,
        ];
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

    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    private function generatePaymentNumber(): string
    {
        return 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    private function orderData(Order $order): array
    {
        $order->loadMissing(['shift', 'cashier.detail']);

        return [
            'guid' => $order->guid,
            'order_number' => $order->order_number,
            'shift' => $order->shift ? [
                'guid' => $order->shift->guid,
                'shift_number' => $order->shift->shift_number,
                'status' => $order->shift->status,
            ] : null,
            'cashier' => $order->cashier ? [
                'guid' => $order->cashier->guid,
                'full_name' => $order->cashier->detail?->full_name ?? $order->cashier->username,
            ] : null,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'table_number' => $order->table_number,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'notes' => $order->notes,
            'ordered_at' => $order->ordered_at?->toISOString(),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'guid' => $item->guid,
                'product' => [
                    'guid' => $item->product?->guid,
                    'name' => $item->product_name,
                ],
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'subtotal' => $item->subtotal,
                'notes' => $item->notes,
            ])->values(),
            'payments' => $order->payments->map(fn (Payment $payment): array => [
                'guid' => $payment->guid,
                'payment_number' => $payment->payment_number,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at?->toISOString(),
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
            ])->values(),
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
        ];
    }

    private function authenticatedUser(Request $request): ?AuthenticationUser
    {
        $apiToken = $request->attributes->get('api_token');

        if (! $apiToken) {
            return null;
        }

        $session = AuthenticationSession::query()
            ->with(['user.role', 'user.detail'])
            ->where('api_token_id', $apiToken->id)
            ->latest('last_login_at')
            ->latest('id')
            ->first();

        return $session?->user;
    }
}
