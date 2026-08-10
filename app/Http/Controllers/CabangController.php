<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cabang\IndexCabangRequest;
use App\Http\Requests\Cabang\StoreCabangRequest;
use App\Http\Requests\Cabang\UpdateCabangRequest;
use App\Models\Cabang;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CabangController extends Controller
{
    use Filterable;

    public function index(IndexCabangRequest $request): JsonResponse
    {
        $query = Cabang::query();
        $this->applyFilter($request, $query, ['guid', 'kode', 'is_active']);

        $cabangs = $query->get()
            ->map(fn (Cabang $cabang): array => $this->cabangData($cabang));

        return $this->apiResponse('00', 'success', $cabangs);
    }

    public function store(StoreCabangRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cabang = Cabang::query()->create([
            'guid' => (string) Str::uuid(),
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->apiResponse('00', 'success', $this->cabangData($cabang), 'Branch created successfully.', 'Cabang berhasil dibuat.', 201);
    }

    public function show(string $guid): JsonResponse
    {
        $cabang = $this->findCabang($guid);

        if (! $cabang) {
            return $this->apiResponse('01', 'failed', null, 'Branch not found.', 'Cabang tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->cabangData($cabang));
    }

    public function update(UpdateCabangRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $cabang = $this->findCabang($validated['guid']);

        if (! $cabang) {
            return $this->apiResponse('01', 'failed', null, 'Branch not found.', 'Cabang tidak ditemukan.', 404);
        }

        $cabang->update([
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'] ?? null,
            'is_active' => $validated['is_active'] ?? $cabang->is_active,
        ]);

        return $this->apiResponse('00', 'success', $this->cabangData($cabang->refresh()), 'Branch updated successfully.', 'Cabang berhasil diperbarui.');
    }

    public function destroy(string $guid): JsonResponse
    {
        $cabang = $this->findCabang($guid);

        if (! $cabang) {
            return $this->apiResponse('01', 'failed', null, 'Branch not found.', 'Cabang tidak ditemukan.', 404);
        }

        $cabang->update(['is_active' => false]);

        return $this->apiResponse('00', 'success', null, 'Branch deactivated successfully.', 'Cabang berhasil dinonaktifkan.');
    }

    private function findCabang(string $guid): ?Cabang
    {
        return Cabang::query()->where('guid', $guid)->first();
    }

    private function cabangData(Cabang $cabang): array
    {
        return [
            'guid' => $cabang->guid,
            'kode' => $cabang->kode,
            'nama' => $cabang->nama,
            'alamat' => $cabang->alamat,
            'is_active' => $cabang->is_active,
            'created_at' => $cabang->created_at?->toISOString(),
            'updated_at' => $cabang->updated_at?->toISOString(),
        ];
    }
}
