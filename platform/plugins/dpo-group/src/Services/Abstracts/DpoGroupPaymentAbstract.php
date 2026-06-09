<?php

namespace Botble\DpoGroup\Services\Abstracts;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;

abstract class DpoGroupPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected ?string $paymentCurrency = null;

    protected bool $supportRefundOnline = false;

    protected float $totalAmount = 0;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function setCurrency(string $currency): static
    {
        $this->paymentCurrency = $currency;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->paymentCurrency;
    }

    public function execute(Request $request)
    {
        try {
            return $this->makePayment($request);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);
}
