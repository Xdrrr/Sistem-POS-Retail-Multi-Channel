<?php

namespace App\Http\Controllers;

use App\Http\Requests\Table\IndexTableRequest;
use App\Http\Requests\Table\StoreTableRequest;
use App\Http\Requests\Table\UpdateTableRequest;
use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RestaurantTableController extends Controller
{
    public function index(IndexTableRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filter = $validated['filter'] ?? [];
        $limit = min((int) ($validated['limit'] ?? 20), 100);
        $page = max(1, (int) ($validated['page'] ?? 1));
        $order = $validated['order'] ?? 'table_number';
        $sort = $validated['sort'] ?? 'ASC';

        $query = RestaurantTable::query();

        if (($filter['set_guid'] ?? false) && ! empty($filter['guid'])) {
            $query->where('guid', $filter['guid']);
        }
        if (($filter['set_table_number'] ?? false) && ! empty($filter['table_number'])) {
            $query->where('table_number', $filter['table_number']);
        }
        if (($filter['set_location'] ?? false) && ! empty($filter['location'])) {
            $query->where('location', $filter['location']);
        }
        if (($filter['set_status'] ?? false) && ! empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }
        if (($filter['set_guid_cabang'] ?? false) && ! empty($filter['guid_cabang'])) {
            $query->where('guid_cabang', $filter['guid_cabang']);
        }

        $total = $query->count();
        $items = $query->orderBy($order, $sort)
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn (RestaurantTable $t): array => $this->tableData($t));

        return $this->apiResponse('00', 'success', $items);
    }

    public function store(StoreTableRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $table = RestaurantTable::query()->create([
            'guid' => (string) Str::uuid(),
            'table_number' => $validated['table_number'],
            'capacity' => $validated['capacity'] ?? 4,
            'location' => $validated['location'] ?? 'indoor',
            'status' => $validated['status'] ?? 'available',
            'guid_cabang' => $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001',
            'is_active' => true,
        ]);

        return $this->apiResponse('00', 'success', $this->tableData($table), 'Table created.', 'Meja berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $table = RestaurantTable::query()->where('guid', $guid)->first();

        if (! $table) {
            return $this->apiResponse('01', 'failed', null, 'Table not found.', 'Meja tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->tableData($table));
    }

    public function update(UpdateTableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $table = RestaurantTable::query()->where('guid', $validated['guid'])->first();

        if (! $table) {
            return $this->apiResponse('01', 'failed', null, 'Table not found.', 'Meja tidak ditemukan.', 404);
        }

        $table->update($validated);

        return $this->apiResponse('00', 'success', $this->tableData($table->fresh()), 'Table updated.', 'Meja berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $table = RestaurantTable::query()->where('guid', $guid)->first();

        if (! $table) {
            return $this->apiResponse('01', 'failed', null, 'Table not found.', 'Meja tidak ditemukan.', 404);
        }

        TableReservation::query()->where('table_guid', $table->guid)->update(['table_guid' => null]);
        $table->delete();

        return $this->apiResponse('00', 'success', null, 'Table deleted.', 'Meja berhasil dihapus.');
    }

    public function statusAll(): JsonResponse
    {
        $tables = RestaurantTable::query()
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get()
            ->map(fn (RestaurantTable $t): array => $this->tableData($t, resolveStatus: true));

        return $this->apiResponse('00', 'success', $tables);
    }

    private function tableData(RestaurantTable $t, bool $resolveStatus = false): array
    {
        return [
            'guid' => $t->guid,
            'table_number' => $t->table_number,
            'capacity' => $t->capacity,
            'location' => $t->location,
            'status' => $resolveStatus ? $t->resolveStatus() : $t->status,
            'guid_cabang' => $t->guid_cabang,
            'is_active' => $t->is_active,
            'created_at' => $t->created_at?->toISOString(),
            'updated_at' => $t->updated_at?->toISOString(),
        ];
    }
}
