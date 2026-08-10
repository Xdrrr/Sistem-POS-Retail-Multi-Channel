<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\Cabang\StoreCabangRequest;
use App\Http\Requests\Web\Cabang\UpdateCabangRequest;
use App\Models\Cabang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CabangPageController extends Controller
{
    public function index(): Response
    {
        $cabangs = Cabang::query()
            ->orderBy('kode')
            ->get()
            ->map(fn (Cabang $c): array => [
                'guid' => $c->guid,
                'kode' => $c->kode,
                'nama' => $c->nama,
                'alamat' => $c->alamat,
                'is_active' => $c->is_active,
                'created_at' => $c->created_at?->toISOString(),
                'updated_at' => $c->updated_at?->toISOString(),
            ]);

        return Inertia::render('Cabang/Index', [
            'title' => 'Cabang',
            'server_time' => now()->format('l, d F Y at h:i A'),
            'cabangs' => $cabangs,
        ]);
    }

    public function store(StoreCabangRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Cabang::query()->create([
            'guid' => (string) Str::uuid(),
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil dibuat.');
    }

    public function update(UpdateCabangRequest $request, string $guid): RedirectResponse
    {
        $cabang = Cabang::query()->where('guid', $guid)->firstOrFail();
        $validated = $request->validated();

        $cabang->update($validated);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        Cabang::query()->where('guid', $guid)->firstOrFail()->update(['is_active' => false]);

        return redirect()->route('cabang.index')->with('success', 'Cabang dinonaktifkan.');
    }
}
