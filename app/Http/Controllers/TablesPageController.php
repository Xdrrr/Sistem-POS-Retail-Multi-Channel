<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:30', 'unique:orders.tables,table_number'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'status' => ['nullable', 'string', 'in:available,maintenance'],
            'guid_cabang' => ['nullable', 'string'],
        ]);

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

    public function update(Request $request, string $guid): RedirectResponse
    {
        $table = RestaurantTable::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:30', 'unique:orders.tables,table_number,'.$table->id],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'in:indoor,outdoor'],
            'status' => ['nullable', 'string', 'in:available,maintenance'],
        ]);

        $table->update($validated);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        RestaurantTable::query()->where('guid', $guid)->firstOrFail()->update(['is_active' => false]);

        return redirect()->route('tables.index')->with('success', 'Meja dinonaktifkan.');
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
