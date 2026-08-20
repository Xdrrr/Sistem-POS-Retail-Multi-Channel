<?php

namespace App\Http\Middleware;

use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = null;
        $userId = $request->session()->get('web_auth_user_id');

        if ($userId) {
            $user = AuthenticationUser::with('role')->find($userId);
        }

        if (! $user) {
            $apiToken = $request->attributes->get('api_token');
            if ($apiToken) {
                $session = AuthenticationSession::query()
                    ->where('api_token_id', $apiToken->id)
                    ->with('user.role')
                    ->first();
                $user = $session?->user;
            }
        }

        if (! $user || ! $user->role) {
            return $this->forbidden($request);
        }

        if (! $user->role->hasPermission($permission)) {
            return $this->forbidden($request);
        }

        return $next($request);
    }

    private function forbidden(Request $request): Response
    {
        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'response' => [
                    'code' => '03',
                    'status' => 'failed',
                    'data' => null,
                    'message_en' => 'Forbidden.',
                    'message_id' => 'Akses ditolak.',
                ],
            ], 403);
        }

        // For web requests, redirect back or to home with an error flash
        return redirect()->back()->withErrors(['permission' => 'Anda tidak memiliki akses ke halaman ini.'])
            ->fallback(redirect('/'));
    }
}
