<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationUser;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\RestaurantTable;
use App\Models\TableReservation;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Inventory\InventoryService;
use App\Traits\StoresCatalogImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderPageController extends Controller
{
    use StoresCatalogImages;

    public function index(): Response
    {
        return Inertia::render('Orders/Index', [
            'title' => 'Orders',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'tables' => RestaurantTable::query()
                ->where('is_active', true)
                ->orderBy('table_number')
                ->get(['guid', 'table_number', 'capacity', 'location']),
            'products' => Product::query()
                ->with(['category', 'group'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product): array => [
                    'guid' => $product->guid,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'image_url' => $this->catalogImageUrl($product->image),
                    'category_name' => $product->category?->name,
                    'group_name' => $product->group?->name,
                ]),
            'orders' => Order::query()
                ->with(['items.product', 'payments'])
                ->latest('ordered_at')
                ->limit(50)
                ->get()
                ->map(fn (Order $order): array => $this->orderData($order)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->orderRules());

        $order = DB::transaction(function () use ($validated): Order {
            $items = collect($validated['items'])->map(function (array $item): array {
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
            });

            $subtotal = (float) $items->sum('subtotal');
            $discountAmount = (float) ($validated['discount_amount'] ?? 0);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);
            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount);

            $order = Order::query()->create([
                'guid' => (string) Str::uuid(),
                'order_number' => 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'table_number' => $validated['table_number'] ?? null,
                'order_type' => $validated['order_type'] ?? 'dine_in',
                'status' => 'open',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'ordered_at' => now(),
            ]);

            // items() relation uses order_guid, Laravel will inject it automatically
            $items->each(fn (array $item) => $order->items()->create($item));

            if (($validated['payment_amount'] ?? 0) > 0) {
                $order->payments()->create([
                    'guid' => (string) Str::uuid(),
                    'payment_number' => 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                    'method' => $validated['payment_method'] ?? 'cash',
                    'status' => 'paid',
                    'amount' => $validated['payment_amount'],
                    'paid_at' => now(),
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => 'Created from order page.',
                ]);
            }

            $this->syncPaymentStatus($order->refresh());
            return $order->refresh();
        });

        if ($order->table_number && $order->order_type === 'dine_in') {
            $table = RestaurantTable::query()->where('table_number', $order->table_number)->first();
            TableReservation::query()->updateOrCreate(
                [
                    'table_number' => $order->table_number,
                    'reservation_date' => now()->format('Y-m-d'),
                    'type' => 'walkin',
                    'status' => 'occupied',
                ],
                [
                    'guid' => (string) Str::uuid(),
                    'table_guid' => $table?->guid,
                    'customer_name' => $order->customer_name ?? 'Walk-in',
                    'guest_count' => 1,
                    'reservation_time' => $order->ordered_at?->format('H:i') ?? now()->format('H:i'),
                    'guid_cabang' => 'aaaaaaaa-aaaa-4000-8000-000000000001',
                    'is_active' => true,
                ],
            );
        }

        return redirect()->route('orders.index')->with('success', 'Order berhasil dibuat.');
    }

    public function storePayment(Request $request, string $guid): RedirectResponse
    {
        $order = Order::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'method' => ['required', 'string', 'in:cash,debit_card,credit_card,qris,transfer,e_wallet'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $order->payments()->create([
                'guid' => (string) Str::uuid(),
                'payment_number' => 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'method' => $validated['method'],
                'status' => 'paid',
                'amount' => $validated['amount'],
                'paid_at' => now(),
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncPaymentStatus($order->refresh());
        });

        return redirect()->route('orders.index')->with('success', 'Payment berhasil ditambahkan.');
    }

    public function complete(Request $request, string $guid): RedirectResponse
    {
        $order = Order::query()->with('items')->where('guid', $guid)->firstOrFail();
        $service = app(InventoryService::class);
        $userGuid = $this->authUserGuid($request);

        DB::transaction(function () use ($order, $service, $userGuid): void {
            if ($order->status === 'completed') {
                return;
            }

            foreach ($order->items as $item) {
                $inventory = ProductInventory::query()
                    ->where('product_guid', $item->product_guid)
                    ->where('guid_cabang', 'aaaaaaaa-aaaa-4000-8000-000000000001')
                    ->first();

                if (! $inventory) {
                    continue;
                }

                $existing = InventoryHistory::query()
                    ->where('reference_type', 'order')
                    ->where('reference_id', $order->guid)
                    ->where('product_guid', $item->product_guid)
                    ->where('is_active', true)
                    ->exists();

                if ($existing) {
                    continue;
                }

                $service->adjustStock(
                    inventory: $inventory,
                    type: 'out',
                    qty: (float) $item->quantity,
                    referenceType: 'order',
                    referenceId: $order->guid,
                    notes: "Deduct stock for order {$order->order_number}",
                    createdBy: $userGuid,
                    userGuidReff: $userGuid,
                );
            }

            $order->update(['status' => 'completed']);
        });

        TableReservation::query()
            ->where('table_number', $order->table_number)
            ->where('reservation_date', now()->format('Y-m-d'))
            ->where('type', 'walkin')
            ->where('status', 'occupied')
            ->update(['status' => 'completed']);

        return redirect()->route('orders.index')->with('success', 'Order berhasil diselesaikan.');
    }

    public function cancel(Request $request, string $guid): RedirectResponse
    {
        $order = Order::query()->with('items')->where('guid', $guid)->firstOrFail();
        $service = app(InventoryService::class);
        $userGuid = $this->authUserGuid($request);

        DB::transaction(function () use ($order, $service, $userGuid): void {
            if ($order->status === 'cancelled') {
                return;
            }

            foreach ($order->items as $item) {
                $inventory = ProductInventory::query()
                    ->where('product_guid', $item->product_guid)
                    ->where('guid_cabang', 'aaaaaaaa-aaaa-4000-8000-000000000001')
                    ->first();

                if (! $inventory) {
                    continue;
                }

                $existing = InventoryHistory::query()
                    ->where('reference_type', 'order')
                    ->where('reference_id', $order->guid)
                    ->where('product_guid', $item->product_guid)
                    ->where('type', 'in')
                    ->where('is_active', true)
                    ->exists();

                if ($existing) {
                    continue;
                }

                $service->adjustStock(
                    inventory: $inventory,
                    type: 'in',
                    qty: (float) $item->quantity,
                    referenceType: 'order',
                    referenceId: $order->guid,
                    notes: "Restore stock for cancelled order {$order->order_number}",
                    createdBy: $userGuid,
                    userGuidReff: $userGuid,
                );
            }

            $order->update(['status' => 'cancelled']);
        });

        TableReservation::query()
            ->where('table_number', $order->table_number)
            ->where('reservation_date', now()->format('Y-m-d'))
            ->where('type', 'walkin')
            ->where('status', 'occupied')
            ->update(['status' => 'completed']);

        return redirect()->route('orders.index')->with('success', 'Order berhasil dibatalkan.');
    }

    private function authUserGuid(Request $request): ?string
    {
        $userId = $request->session()->get('web_auth_user_id');

        if (! $userId) {
            return null;
        }

        return AuthenticationUser::query()->where('id', $userId)->value('guid');
    }

    private function orderRules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'table_number' => ['nullable', 'string', 'max:30'],
            'order_type' => ['required', 'string', 'in:dine_in,takeaway,delivery'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_guid' => ['required', 'string', Rule::exists(Product::class, 'guid')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'in:cash,debit_card,credit_card,qris,transfer,e_wallet'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function syncPaymentStatus(Order $order): void
    {
        $paidAmount = (float) $order->payments()->where('status', 'paid')->sum('amount');
        $totalAmount = (float) $order->total_amount;

        $order->update([
            'payment_status' => match (true) {
                $paidAmount <= 0 => 'unpaid',
                $paidAmount < $totalAmount => 'partial',
                default => 'paid',
            },
        ]);
    }

    private function orderData(Order $order): array
    {
        $mutations = InventoryHistory::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->guid)
            ->where('is_active', true)
            ->get();

        return [
            'guid' => $order->guid,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'table_number' => $order->table_number,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'paid_amount' => $order->payments->where('status', 'paid')->sum('amount'),
            'ordered_at' => $order->ordered_at?->format('d M Y H:i'),
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->values(),
            'payments' => $order->payments->map(fn ($payment): array => [
                'payment_number' => $payment->payment_number,
                'method' => $payment->method,
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at?->format('d M Y H:i'),
                'reference_number' => $payment->reference_number,
            ])->values(),
            'stock_mutations' => $mutations->map(fn (InventoryHistory $m): array => [
                'guid' => $m->guid,
                'product_name' => $m->inventory?->product?->name ?? '-',
                'type' => $m->type,
                'qty' => (float) $m->qty,
            ])->values(),
        ];
    }
}
