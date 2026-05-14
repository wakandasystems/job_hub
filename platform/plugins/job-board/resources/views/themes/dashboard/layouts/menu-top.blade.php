<div class="ps-sidebar__top">
    <div class="ps-block--user-wellcome">
        <div class="ps-block__left">
            <img
                src="{{ auth('account')->user()->avatar_url }}"
                alt="{{ auth('account')->user()->name }}"
                class="avatar avatar-lg"
            />
        </div>
        <div class="ps-block__right">
            <p>{{ trans('plugins/job-board::dashboard.hello') }}, {{ auth('account')->user()->name }}</p>
            <small>{{ trans('plugins/job-board::dashboard.joined_on', ['date' => auth('account')->user()->created_at->translatedFormat('M d, Y')]) }}</small>
            @if ($uniqueId = auth('account')->user()->unique_id)
                <small class="d-block mt-1">
                    {!!  trans('plugins/job-board::dashboard.id_label', ['id' => Html::tag('strong', $uniqueId)])  !!}
                </small>
            @endif
        </div>
        <div class="ps-block__action">
            <a
                href="{{ route('public.account.logout') }}"
                title="{{ trans('plugins/job-board::dashboard.header_logout_link') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            >
                <x-core::icon name="ti ti-logout" />
            </a>
        </div>
    </div>

    @if (JobBoardHelper::isEnabledCreditsSystem())
        <div class="ps-block--earning-count">
            <small>{{ trans('plugins/job-board::dashboard.credits') }}</small>
            <h3 class="my-2">{{ number_format(auth('account')->user()->credits) }}</h3>
            <a href="{{ route('public.account.packages') }}" target="_blank">
                {{ trans('plugins/job-board::dashboard.buy_credits') }}
            </a>
        </div>
    @endif
</div>
