<?php

namespace Botble\JobBoard\Http\Middleware;

use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\Theme\Facades\AdminBar;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class RedirectIfNotAccount
{
    public function handle(Request $request, Closure $next, $type = null)
    {
        if (! Auth::guard('account')->check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }

            return redirect()->guest(route('public.account.login'));
        }

        $account = Auth::guard('account')->user();

        if (
            ! in_array($account->type?->getValue(), [AccountTypeEnum::JOB_SEEKER, AccountTypeEnum::EMPLOYER], true) &&
            ! in_array(Route::currentRouteName(), ['public.account.choose-type', 'public.account.choose-type.save', 'public.account.logout'], true)
        ) {
            return redirect()->route('public.account.choose-type');
        }

        if ($type && $account->type != $type) {
            if ($account->isJobSeeker()) {
                return redirect()->route('public.account.dashboard');
            }

            if ($account->isEmployer()) {
                return redirect()->route('public.account.dashboard');
            }

            return redirect()->route('public.account.choose-type');
        }

        AdminBar::setIsDisplay(false);

        return $next($request);
    }
}
