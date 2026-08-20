<?php

namespace App\Http\Middleware;

use App\Models\AuthenticationUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAuthenticated
{
    /**
     * Roles that are blocked from the full dashboard.
     * They will be redirected to their designated landing page.
     */
    private const DASHBOARD_BLOCKED_ROLES = ['Cashier', 'Users'];

    /**
     * Where blocked roles should be redirected.
     */
    private const BLOCKED_ROLE_REDIRECT = '/orders';

    /**
     * Paths that blocked roles are allowed to access (besides their landing page).
     * Add paths without leading slash.
     */
    private const BLOCKED_ROLE_ALLOWED_PATHS = [
        'orders',
        'logout',
        'settings',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('web_auth_user_id')) {
            return redirect()->route('login');
        }

        $user = AuthenticationUser::query()
            ->with('role')
            ->find($request->session()->get('web_auth_user_id'));

        if (! $user) {
            $request->session()->forget('web_auth_user_id');

            return redirect()->route('login');
        }

        $roleName = $user->role?->name;

        // Cashier & Users cannot access the full dashboard.
        // Redirect them to their landing page unless they are already on an allowed path.
        if (in_array($roleName, self::DASHBOARD_BLOCKED_ROLES, true)) {
            $path = $request->path(); // no leading slash, e.g. "orders", "logout", "settings/profile"

            $isAllowed = collect(self::BLOCKED_ROLE_ALLOWED_PATHS)
                ->contains(fn (string $allowed) => $path === $allowed || str_starts_with($path, $allowed.'/'));

            if (! $isAllowed) {
                return redirect(self::BLOCKED_ROLE_REDIRECT);
            }
        }

        return $next($request);
    }
}
