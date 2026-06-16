@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    @include('plugins/payment::partials.form', [
        'action' => route('payments.checkout'),
        'currency' => $package->currency->title
            ? strtoupper($package->currency->title)
            : cms_currency()->getDefaultCurrency()->title,
        'amount' => $packageAmount ?? $package->price,
        'name' => $package->name . ' (' . ($billingCycle === 'one_time' ? __('One-time') : ucfirst($billingCycle ?? 'monthly')) . ')',
        'returnUrl' => route('public.account.package.subscribe', ['id' => $package->id, 'billing_cycle' => $billingCycle ?? 'one_time']),
        'callbackUrl' => route('public.account.package.subscribe.callback', $package->id),
    ])
@endsection
