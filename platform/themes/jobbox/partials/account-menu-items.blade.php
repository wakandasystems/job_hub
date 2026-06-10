@php
    $linkClass = $linkClass ?? '';
    $dividerClass = $dividerClass ?? 'my-1';
@endphp

@if ($account->isEmployer())
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.dashboard') }}">{{ __('Employer Dashboard') }}</a></li>
@else
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.settings') }}">{{ __('My Profile') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.security') }}">{{ __('Security') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.overview') }}">{{ __('Overview') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.experiences.index') }}">{{ __('Experiences') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.educations.index') }}">{{ __('Educations') }}</a></li>
    <li><hr class="dropdown-divider {{ $dividerClass }}"></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.jobs.saved') }}">{{ __('Saved Jobs') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.jobs.applied-jobs') }}">{{ __('Applied Jobs') }}</a></li>
    <li><hr class="dropdown-divider {{ $dividerClass }}"></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.career-services') }}">{{ __('Career Services') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.job-alerts.index') }}">{{ __('Job Alerts') }}</a></li>
    <li><a @class([$linkClass => $linkClass]) href="{{ route('public.account.job-alert.packages.index') }}">{{ __('Alert Packages') }}</a></li>
@endif
<li><hr class="dropdown-divider {{ $dividerClass }}"></li>
<li>
    <a @class([$linkClass => $linkClass, 'text-danger' => $linkClass]) href="#"
       onclick="event.preventDefault(); document.getElementById('{{ $logoutFormId }}').submit();">
        {{ __('Logout') }}
    </a>
</li>
