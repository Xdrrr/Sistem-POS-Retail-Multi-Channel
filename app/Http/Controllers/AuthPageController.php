<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationRole;
use App\Models\AuthenticationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuthPageController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('Auth/Login', [
            'title' => 'Login',
        ]);
    }

    public function register(): Response
    {
        return Inertia::render('Auth/Register', [
            'title' => 'Register',
        ]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $user = AuthenticationUser::query()
            ->with(['role', 'detail'])
            ->where('username', $validated['username'])
            ->first();

        if (! $user || ! $user->is_active || ! hash_equals($user->password, $this->passwordHash($validated['password'], $user->salt))) {
            return back()->withErrors([
                'username' => 'Email atau password tidak valid.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('web_auth_user_id', $user->id);
        $user->update(['last_login' => now()]);

        return redirect()->route('dashboard')->with('success', 'Login berhasil.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(AuthenticationUser::class, 'username')],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:password'],
        ]);

        $role = AuthenticationRole::query()->where('name', 'Users')->first()
            ?? AuthenticationRole::query()->where('is_default', true)->first()
            ?? AuthenticationRole::query()->first();

        if (! $role) {
            return back()->withErrors([
                'email' => 'Role user belum tersedia.',
            ])->onlyInput('email');
        }

        $user = DB::transaction(function () use ($validated, $role): AuthenticationUser {
            $salt = base64_encode(random_bytes(16));
            $user = AuthenticationUser::query()->create([
                'guid' => (string) Str::uuid(),
                'role_id' => $role->id,
                'username' => $validated['email'],
                'password' => $this->passwordHash($validated['password'], $salt),
                'salt' => $salt,
                'is_active' => true,
                'url_image' => '',
                'fcm_token' => '',
                'last_login' => now(),
                'used_trial' => true,
                'is_verified' => true,
            ]);

            $user->detail()->create([
                'phone_number' => $validated['phone_number'] ?? '',
                'email' => $validated['email'],
                'full_name' => $validated['fullname'],
                'gender' => 'Tidak-Spesifik',
                'address' => null,
                'additional_address' => null,
                'city' => null,
                'province' => null,
                'date_of_birth' => null,
            ]);

            return $user;
        });

        $request->session()->regenerate();
        $request->session()->put('web_auth_user_id', $user->id);

        return redirect()->route('dashboard')->with('success', 'Registrasi berhasil.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('web_auth_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function passwordHash(string $password, string $salt): string
    {
        return base64_encode(hash('sha256', $password.$salt, true));
    }
}
