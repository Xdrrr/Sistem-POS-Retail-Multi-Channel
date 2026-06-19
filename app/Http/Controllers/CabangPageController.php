<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', Rule::unique(Cabang::class, 'kode')],
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Cabang::query()->create([
            'guid' => (string) Str::uuid(),
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil dibuat.');
    }

    public function update(Request $request, string $guid): RedirectResponse
    {
        $cabang = Cabang::query()->where('guid', $guid)->firstOrFail();

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', Rule::unique(Cabang::class, 'kode')->ignore($cabang->id)],
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $cabang->update($validated);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(string $guid): RedirectResponse
    {
        Cabang::query()->where('guid', $guid)->firstOrFail()->update(['is_active' => false]);

        return redirect()->route('cabang.index')->with('success', 'Cabang dinonaktifkan.');
    }
}
