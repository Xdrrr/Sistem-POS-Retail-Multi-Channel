<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\Reservation\StoreReservationRequest;
use App\Http\Requests\Web\Reservation\UpdateReservationRequest;
use App\Models\Cabang;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TableReservationPageController extends Controller
{
    public function index(): Response
    {
        $reservations = TableReservation::query()
            ->where('is_active', true)
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->get()
            ->map(fn (TableReservation $r): array => $this->reservationData($r));

        $tables = RestaurantTable::query()
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get()
            ->map(function (RestaurantTable $t): array {
                $status = $t->resolveStatus();
                $data = [
                    'guid' => $t->guid,
                    'table_number' => $t->table_number,
                    'capacity' => $t->capacity,
                    'location' => $t->location,
                    'status' => $status,
                    'reservation_guid' => null,
                    'order_guid' => null,
                    'reservation_time' => null,
                    'end_time' => null,
                ];
                if (in_array($status, ['occupied', 'reserved'])) {
                    $statuses = $status === 'occupied' ? ['occupied'] : ['pending', 'confirmed'];
                    $reservation = TableReservation::query()
                        ->where('table_guid', $t->guid)
                        ->where('reservation_date', now()->format('Y-m-d'))
                        ->whereIn('status', $statuses)
                        ->where('is_active', true)
                        ->first();
                    if ($reservation) {
                        $data['reservation_guid'] = $reservation->guid;
                        $data['reservation_time'] = $reservation->reservation_time;
                        $data['end_time'] = $reservation->end_time;
                    } elseif ($status === 'occupied') {
                        $order = Order::query()
                            ->where('table_number', $t->table_number)
                            ->where('status', 'open')
                            ->first(['guid', 'table_number']);
                        if ($order) {
                            $data['order_guid'] = $order->guid;
                        }
                    }
                }
                return $data;
            });

        return Inertia::render('TableReservation/Index', [
            'title' => 'Reservasi Meja',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'reservations' => $reservations,
            'tables' => $tables,
            'cabangs' => Cabang::listActive(),
        ]);
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $table = RestaurantTable::query()->where('table_number', $validated['table_number'])->first();

        TableReservation::query()->create([
            'guid' => (string) Str::uuid(),
            'table_guid' => $table?->guid,
            'table_number' => $validated['table_number'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'guest_count' => $validated['guest_count'] ?? 1,
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'end_time' => $validated['end_time'] ?? null,
            'type' => $validated['type'] ?? 'booking',
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'guid_cabang' => $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001',
            'is_active' => true,
        ]);

        return redirect()->route('reservations.index')->with('success', 'Reservasi berhasil dibuat.');
    }

    public function update(UpdateReservationRequest $request, string $guid): RedirectResponse
    {
        $reservation = TableReservation::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validated();

        $table = RestaurantTable::query()->where('table_number', $validated['table_number'])->first();
        $reservation->update(array_merge($validated, ['table_guid' => $table?->guid]));

        return redirect()->route('reservations.index')->with('success', 'Reservasi berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        TableReservation::query()->where('guid', $guid)->firstOrFail()->update(['is_active' => false]);

        return redirect()->route('reservations.index')->with('success', 'Reservasi dibatalkan.');
    }

    public function release(string $guid): RedirectResponse
    {
        $reservation = TableReservation::query()->where('guid', $guid)->firstOrFail();
        $reservation->update(['status' => 'completed']);

        Order::query()
            ->where('table_number', $reservation->table_number)
            ->where('status', 'open')
            ->update(['table_number' => null]);

        return redirect()->route('reservations.index')->with('success', 'Meja berhasil dikosongkan.');
    }

    public function releaseOrderTable(string $guid): RedirectResponse
    {
        $order = Order::query()->where('guid', $guid)->where('status', 'open')->firstOrFail();
        $order->update(['table_number' => null]);

        return redirect()->route('reservations.index')->with('success', 'Meja berhasil dikosongkan.');
    }

    private function reservationData(TableReservation $r): array
    {
        return [
            'guid' => $r->guid,
            'table_number' => $r->table_number,
            'customer_name' => $r->customer_name,
            'customer_phone' => $r->customer_phone,
            'guest_count' => $r->guest_count,
            'reservation_date' => $r->reservation_date?->format('Y-m-d'),
            'reservation_time' => $r->reservation_time,
            'end_time' => $r->end_time,
            'type' => $r->type,
            'notes' => $r->notes,
            'status' => $r->status,
            'guid_cabang' => $r->guid_cabang,
            'is_active' => $r->is_active,
            'created_at' => $r->created_at?->toISOString(),
            'updated_at' => $r->updated_at?->toISOString(),
        ];
    }
}
