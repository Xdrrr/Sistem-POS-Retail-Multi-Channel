<?php

namespace App\Traits;

use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use Illuminate\Http\Request;

trait ResolvesAuthUserGuid
{
    protected function authUserGuid(Request $request): ?string
    {
        $apiToken = $request->attributes->get('api_token');

        if ($apiToken) {
            return AuthenticationSession::query()
                ->with('user')
                ->where('api_token_id', $apiToken->id)
                ->latest('last_login_at')
                ->latest('id')
                ->first()?->user?->guid;
        }

        $userId = $request->session()->get('web_auth_user_id');

        if (! $userId) {
            return null;
        }

        return AuthenticationUser::query()->where('id', $userId)->value('guid');
    }
}
