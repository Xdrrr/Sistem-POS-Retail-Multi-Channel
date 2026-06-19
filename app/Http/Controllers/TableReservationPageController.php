<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->map(fn (RestaurantTable $t): array => [
                'guid' => $t->guid,
                'table_number' => $t->table_number,
                'capacity' => $t->capacity,
                'location' => $t->location,
                'status' => $t->resolveStatus(),
            ]);

        return Inertia::render('TableReservation/Index', [
            'title' => 'Reservasi Meja',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'reservations' => $reservations,
            'tables' => $tables,
            'cabangs' => Cabang::listActive(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date'],
            'reservation_time' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,seated,completed,cancelled'],
            'guid_cabang' => ['nullable', 'string'],
        ]);

        TableReservation::query()->create([
            'guid' => (string) Str::uuid(),
            'table_number' => $validated['table_number'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'guest_count' => $validated['guest_count'] ?? 1,
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'guid_cabang' => $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001',
            'is_active' => true,
        ]);

        return redirect()->route('reservations.index')->with('success', 'Reservasi berhasil dibuat.');
    }

    public function update(Request $request, string $guid): RedirectResponse
    {
        $reservation = TableReservation::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date'],
            'reservation_time' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,seated,completed,cancelled'],
        ]);

        $reservation->update($validated);

        return redirect()->route('reservations.index')->with('success', 'Reservasi berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        TableReservation::query()->where('guid', $guid)->firstOrFail()->update(['is_active' => false]);

        return redirect()->route('reservations.index')->with('success', 'Reservasi dibatalkan.');
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
            'notes' => $r->notes,
            'status' => $r->status,
            'guid_cabang' => $r->guid_cabang,
            'is_active' => $r->is_active,
            'created_at' => $r->created_at?->toISOString(),
            'updated_at' => $r->updated_at?->toISOString(),
        ];
    }
}
