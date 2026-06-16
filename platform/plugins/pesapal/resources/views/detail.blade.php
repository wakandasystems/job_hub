@if ($payment)
    <p><span>{{ trans('plugins/payment::payment.payment_id') }}: </span>
        <strong>{{ $payment->charge_id }}</strong>
    </p>
    <p>{{ trans('plugins/payment::payment.amount') }}: {{ $payment->amount }} {{ $payment->currency }}</p>
    @include('plugins/payment::partials.view-payment-source')
@endif
