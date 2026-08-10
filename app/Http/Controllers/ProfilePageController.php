<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\Profile\UpdateProfileRequest;
use App\Models\AuthenticationUser;
use App\Traits\HashesPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfilePageController extends Controller
{
    use HashesPasswords;
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

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $this->currentUser($request);
        $validated = $request->validated();

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
}
