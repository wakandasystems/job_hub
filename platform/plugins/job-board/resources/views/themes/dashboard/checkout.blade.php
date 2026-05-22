@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    @include('plugins/payment::partials.form', [
        'action' => route('payments.checkout'),
        'currency' => $package->currency->title
            ? strtoupper($package->currency->title)
            : cms_currency()->getDefaultCurrency()->title,
        'amount' => $packageAmount ?? $package->price,
        'name' => $package->name . ' (' . ucfirst($billingCycle ?? 'monthly') . ')',
        'returnUrl' => route('public.account.package.subscribe', ['id' => $package->id, 'billing_cycle' => $billingCycle ?? 'monthly']),
        'callbackUrl' => route('public.account.package.subscribe.callback', $package->id),
    ])
@endsection
