<?php

namespace Botble\JobBoard\Http\Middleware;

use Botble\JobBoard\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateAccountApi
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof Account) {
            Auth::guard('account')->setUser($user);
        }

        return $next($request);
    }
}
