<?php

namespace App\Traits;

use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use Illuminate\Http\Request;

/**
 * Resolves the authenticated user from an API token or web session.
 *
 * Use ResolvesAuthUserGuid when you only need the user's GUID string.
 * Use this trait when you need the full AuthenticationUser model instance
 * (e.g. to access shift/role/detail relations).
 */
trait ResolvesAuthUser
{
    protected function resolveAuthUser(Request $request): ?AuthenticationUser
    {
        $apiToken = $request->attributes->get('api_token');

        if (! $apiToken) {
            return null;
        }

        $session = AuthenticationSession::query()
            ->with(['user.role', 'user.detail'])
            ->where('api_token_id', $apiToken->id)
            ->latest('last_login_at')
            ->latest('id')
            ->first();

        return $session?->user;
    }
}
