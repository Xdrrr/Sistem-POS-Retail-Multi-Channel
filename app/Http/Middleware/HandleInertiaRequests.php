<?php

namespace App\Http\Middleware;

use App\Models\AuthenticationUser;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn (): ?array => $this->userData($request),
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
            ],
        ];
    }

    private function userData(Request $request): ?array
    {
        $userId = $request->session()->get('web_auth_user_id');

        if (! $userId) {
            return null;
        }

        $user = AuthenticationUser::query()
            ->with(['role', 'detail'])
            ->find($userId);

        if (! $user) {
            $request->session()->forget('web_auth_user_id');

            return null;
        }

        return [
            'guid' => $user->guid,
            'username' => $user->username,
            'role' => $user->role?->name,
            'url_image' => $user->url_image,
            'detail' => [
                'full_name' => $user->detail?->full_name ?? $user->username,
                'email' => $user->detail?->email ?? $user->username,
                'phone_number' => $user->detail?->phone_number,
                'gender' => $user->detail?->gender,
                'address' => $user->detail?->address,
                'city' => $user->detail?->city,
                'province' => $user->detail?->province,
                'date_of_birth' => $user->detail?->date_of_birth?->format('Y-m-d'),
            ],
        ];
    }
}
