<?php

namespace Botble\JobBoard\Http\Middleware;

use Botble\Theme\Facades\AdminBar;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAccount
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('account')->check()) {
            if (Auth::guard('account')->user()->isEmployer()) {
                return redirect(route('public.account.dashboard'));
            }

            if (Auth::guard('account')->user()->isJobSeeker()) {
                return redirect()->route('public.account.dashboard');
            }

            return redirect()->route('public.account.choose-type');
        }

        AdminBar::setIsDisplay(false);

        return $next($request);
    }
}
