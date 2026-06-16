<?php

namespace Botble\Pesapal\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PesapalService
{
    protected string $consumerKey;

    protected string $consumerSecret;

    protected string $baseUrl;

    protected string $mode;

    public function __construct()
    {
        $this->consumerKey = get_payment_setting('consumer_key', PESAPAL_PAYMENT_METHOD_NAME, '');
        $this->consumerSecret = get_payment_setting('consumer_secret', PESAPAL_PAYMENT_METHOD_NAME, '');
        $this->mode = get_payment_setting('mode', PESAPAL_PAYMENT_METHOD_NAME, 'live');
        $this->baseUrl = $this->mode === 'sandbox'
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';
    }

    public function getBearerToken(): string
    {
        $cacheKey = 'pesapal_token_' . md5($this->consumerKey . $this->mode);

        return Cache::remember($cacheKey, 240, function () {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/Auth/RequestToken', [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            if (! $response->successful()) {
                throw new Exception('Pesapal auth failed: ' . $response->body());
            }

            $data = $response->json();
            if (empty($data['token'])) {
                throw new Exception('Pesapal auth: no token in response');
            }

            return $data['token'];
        });
    }

    public function registerIpn(string $ipnUrl): string
    {
        $token = $this->getBearerToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/URLSetup/RegisterIPN', [
            'url' => $ipnUrl,
            'ipn_notification_type' => 'GET',
        ]);

        if (! $response->successful()) {
            throw new Exception('Pesapal IPN registration failed: ' . $response->body());
        }

        $data = $response->json();
        if (empty($data['ipn_id'])) {
            throw new Exception('Pesapal IPN: no ipn_id returned');
        }

        return $data['ipn_id'];
    }

    public function submitOrder(array $orderData): array
    {
        $token = $this->getBearerToken();

        $ipnId = get_payment_setting('ipn_id', PESAPAL_PAYMENT_METHOD_NAME, '');
        if (! $ipnId) {
            $ipnId = $this->registerIpn(route('pesapal.payment.ipn'));
            \Botble\Setting\Facades\Setting::set('payment_pesapal_ipn_id', $ipnId)->save();
        }

        $payload = [
            'id' => $orderData['merchant_reference'],
            'currency' => $orderData['currency'],
            'amount' => (float) $orderData['amount'],
            'description' => $orderData['description'],
            'callback_url' => $orderData['callback_url'],
            'notification_id' => $ipnId,
            'billing_address' => [
                'email_address' => $orderData['email'] ?? '',
                'phone_number' => $orderData['phone'] ?? '',
                'first_name' => $orderData['first_name'] ?? '',
                'last_name' => $orderData['last_name'] ?? '',
                'line_1' => '',
                'city' => '',
                'country' => 'ZM',
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', $payload);

        if (! $response->successful()) {
            throw new Exception('Pesapal SubmitOrderRequest failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getTransactionStatus(string $orderTrackingId): array
    {
        $token = $this->getBearerToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get($this->baseUrl . '/api/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $orderTrackingId,
        ]);

        if (! $response->successful()) {
            throw new Exception('Pesapal GetTransactionStatus failed: ' . $response->body());
        }

        return $response->json();
    }

    public function isPaymentCompleted(array $statusResponse): bool
    {
        return ($statusResponse['payment_status_code'] ?? -1) === 1;
    }
}
