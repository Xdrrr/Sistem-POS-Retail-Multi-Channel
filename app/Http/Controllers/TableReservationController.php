<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TableReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_table_number' => ['nullable', 'boolean'],
            'filter.table_number' => ['nullable', 'string', 'max:30'],
            'filter.set_status' => ['nullable', 'boolean'],
            'filter.status' => ['nullable', 'string', 'in:occupied,pending,confirmed,seated,completed,cancelled'],
            'filter.set_reservation_date' => ['nullable', 'boolean'],
            'filter.reservation_date' => ['nullable', 'date'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string'],
            'filter.set_is_active' => ['nullable', 'boolean'],
            'filter.is_active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:table_number,customer_name,reservation_date,reservation_time,status,guest_count,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        $filter = $validated['filter'] ?? [];
        $limit = min((int) ($validated['limit'] ?? 20), 100);
        $page = max(1, (int) ($validated['page'] ?? 1));
        $order = $validated['order'] ?? 'reservation_date';
        $sort = $validated['sort'] ?? 'ASC';

        $query = TableReservation::query();

        if (($filter['set_guid'] ?? false) && ! empty($filter['guid'])) {
            $query->where('guid', $filter['guid']);
        }
        if (($filter['set_table_number'] ?? false) && ! empty($filter['table_number'])) {
            $query->where('table_number', $filter['table_number']);
        }
        if (($filter['set_status'] ?? false) && ! empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }
        if (($filter['set_reservation_date'] ?? false) && ! empty($filter['reservation_date'])) {
            $query->whereDate('reservation_date', $filter['reservation_date']);
        }
        if (($filter['set_guid_cabang'] ?? false) && ! empty($filter['guid_cabang'])) {
            $query->where('guid_cabang', $filter['guid_cabang']);
        }
        if (($filter['set_is_active'] ?? false) && $filter['is_active'] !== null) {
            $query->where('is_active', filter_var($filter['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $total = $query->count();
        $items = $query->orderBy($order, $sort)
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn (TableReservation $r): array => $this->reservationData($r));

        return $this->apiResponse('00', 'success', $items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_guid' => ['nullable', 'string', Rule::exists(RestaurantTable::class, 'guid')],
            'table_number' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date'],
            'reservation_time' => ['required', 'string'],
            'end_time' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:booking,walkin'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:occupied,pending,confirmed,seated,completed,cancelled'],
            'guid_cabang' => ['nullable', 'string'],
        ]);

        $tableGuid = $validated['table_guid'] ?? null;

        if (! $tableGuid) {
            $table = \App\Models\RestaurantTable::where('table_number', $validated['table_number'])->first();
            $tableGuid = $table?->guid;
        }

        $reservation = TableReservation::query()->create([
            'guid' => (string) Str::uuid(),
            'table_guid' => $tableGuid,
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

        return $this->apiResponse('00', 'success', $this->reservationData($reservation), 'Reservation created.', 'Reservasi berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $reservation = TableReservation::query()->where('guid', $guid)->first();

        if (! $reservation) {
            return $this->apiResponse('01', 'failed', null, 'Reservation not found.', 'Reservasi tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->reservationData($reservation));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'guid' => ['required', 'string', Rule::exists(TableReservation::class, 'guid')],
        ]);

        $reservation = TableReservation::query()->where('guid', $request->string('guid')->toString())->first();

        if (! $reservation) {
            return $this->apiResponse('01', 'failed', null, 'Reservation not found.', 'Reservasi tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'guid' => ['required', 'string'],
            'table_guid' => ['nullable', 'string', Rule::exists(RestaurantTable::class, 'guid')],
            'table_number' => ['nullable', 'string', 'max:30'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'reservation_date' => ['nullable', 'date'],
            'reservation_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:booking,walkin'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:occupied,pending,confirmed,seated,completed,cancelled'],
            'guid_cabang' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $reservation->update($validated);

        return $this->apiResponse('00', 'success', $this->reservationData($reservation->fresh()), 'Reservation updated.', 'Reservasi berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $reservation = TableReservation::query()->where('guid', $guid)->first();

        if (! $reservation) {
            return $this->apiResponse('01', 'failed', null, 'Reservation not found.', 'Reservasi tidak ditemukan.', 404);
        }

        $reservation->update(['is_active' => false]);

        return $this->apiResponse('00', 'success', null, 'Reservation cancelled.', 'Reservasi dibatalkan.');
    }

    private function reservationData(TableReservation $r): array
    {
        return [
            'guid' => $r->guid,
            'table_guid' => $r->table_guid,
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
