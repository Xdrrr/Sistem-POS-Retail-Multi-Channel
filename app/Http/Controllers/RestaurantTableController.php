<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_table_number' => ['nullable', 'boolean'],
            'filter.table_number' => ['nullable', 'string', 'max:30'],
            'filter.set_location' => ['nullable', 'boolean'],
            'filter.location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'filter.set_status' => ['nullable', 'boolean'],
            'filter.status' => ['nullable', 'string', 'in:available,occupied,reserved,maintenance'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:table_number,capacity,location,status,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:30', Rule::unique(RestaurantTable::class, 'table_number')],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'status' => ['nullable', 'string', 'in:available,maintenance'],
            'guid_cabang' => ['nullable', 'string'],
        ]);

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

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'guid' => ['required', 'string', Rule::exists(RestaurantTable::class, 'guid')],
        ]);

        $table = RestaurantTable::query()->where('guid', $request->string('guid')->toString())->first();

        if (! $table) {
            return $this->apiResponse('01', 'failed', null, 'Table not found.', 'Meja tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'guid' => ['required', 'string'],
            'table_number' => ['nullable', 'string', 'max:30', Rule::unique(RestaurantTable::class, 'table_number')->ignore($table->id)],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'status' => ['nullable', 'string', 'in:available,occupied,reserved,maintenance'],
            'guid_cabang' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

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
