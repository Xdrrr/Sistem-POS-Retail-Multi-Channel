<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfilePageController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $this->currentUser($request);

        return Inertia::render('Settings/Profile', [
            'title' => 'Profile Settings',
            'profile' => [
                'fullname' => $user->detail?->full_name ?? '',
                'email' => $user->detail?->email ?? $user->username,
                'phone_number' => $user->detail?->phone_number ?? '',
                'gender' => $user->detail?->gender ?? 'Tidak-Spesifik',
                'address' => $user->detail?->address ?? '',
                'city' => $user->detail?->city ?? '',
                'province' => $user->detail?->province ?? '',
                'date_of_birth' => $user->detail?->date_of_birth?->format('Y-m-d'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(AuthenticationUser::class, 'username')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan', 'Tidak-Spesifik'])],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'password' => ['nullable', 'string', 'min:6'],
            'confirm_password' => ['nullable', 'required_with:password', 'same:password'],
        ]);

        $user->update([
            'username' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $salt = base64_encode(random_bytes(16));
            $user->update([
                'password' => $this->passwordHash($validated['password'], $salt),
                'salt' => $salt,
            ]);
        }

        $user->detail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone_number' => $validated['phone_number'] ?? '',
                'email' => $validated['email'],
                'full_name' => $validated['fullname'],
                'gender' => $validated['gender'],
                'address' => $validated['address'] ?? null,
                'additional_address' => null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
            ],
        );

        return redirect()->route('settings.profile')->with('success', 'Profile berhasil diperbarui.');
    }

    private function currentUser(Request $request): AuthenticationUser
    {
        return AuthenticationUser::query()
            ->with(['role', 'detail'])
            ->findOrFail($request->session()->get('web_auth_user_id'));
    }

    private function passwordHash(string $password, string $salt): string
    {
        return base64_encode(hash('sha256', $password.$salt, true));
    }
}
