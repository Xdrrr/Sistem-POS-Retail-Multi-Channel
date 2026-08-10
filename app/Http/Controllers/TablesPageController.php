<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\Table\StoreTableRequest;
use App\Http\Requests\Web\Table\UpdateTableRequest;
use App\Models\Cabang;
use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TablesPageController extends Controller
{
    public function index(): Response
    {
        $tables = RestaurantTable::query()
            ->orderBy('location')
            ->orderBy('table_number')
            ->get()
            ->map(fn (RestaurantTable $t): array => $this->tableData($t, resolveStatus: true));

        return Inertia::render('Tables/Index', [
            'title' => 'Meja Restoran',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'tables' => $tables,
            'cabangs' => Cabang::listActive(),
        ]);
    }

    public function store(StoreTableRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        RestaurantTable::query()->create([
            'guid' => (string) Str::uuid(),
            'table_number' => $validated['table_number'],
            'capacity' => $validated['capacity'] ?? 4,
            'location' => $validated['location'] ?? 'indoor',
            'status' => $validated['status'] ?? 'available',
            'guid_cabang' => $validated['guid_cabang'] ?? 'aaaaaaaa-aaaa-4000-8000-000000000001',
            'is_active' => true,
        ]);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil dibuat.');
    }

    public function update(UpdateTableRequest $request, string $guid): RedirectResponse
    {
        $table = RestaurantTable::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validated();

        $table->update($validated);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        $table = RestaurantTable::query()->where('guid', $guid)->firstOrFail();
        TableReservation::query()->where('table_guid', $table->guid)->update(['table_guid' => null]);
        $table->delete();

        return redirect()->route('tables.index')->with('success', 'Meja berhasil dihapus.');
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
