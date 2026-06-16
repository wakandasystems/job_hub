<?php

namespace Botble\DpoGroup\Services;

use Exception;
use Illuminate\Support\Facades\Config;
use SimpleXMLElement;

class DpoGroupService
{
    protected string $companyToken;

    protected string $serviceType;

    protected string $baseUrl;

    public function __construct()
    {
        $this->companyToken = get_payment_setting('company_token', DPO_GROUP_PAYMENT_METHOD_NAME, '');
        $this->serviceType = get_payment_setting('service_type', DPO_GROUP_PAYMENT_METHOD_NAME, '5525');
        $mode = get_payment_setting('mode', DPO_GROUP_PAYMENT_METHOD_NAME, 'live');
        $this->baseUrl = $mode === 'sandbox'
            ? 'https://secure1.sandbox.directpay.online/API/v6/'
            : 'https://secure.3gdirectpay.com/API/v6/';
    }

    public function createToken(array $data): array
    {
        $companyRef = $data['company_ref'];
        $amount = number_format((float) $data['amount'], 2, '.', '');
        $currency = $data['currency'];
        $redirectUrl = $data['redirect_url'];
        $backUrl = $data['back_url'];
        $serviceDate = now()->format('Ymd');
        $serviceDescription = $data['description'] ?? 'Wakanda Jobs Payment';

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<API3G>
  <CompanyToken>{$this->companyToken}</CompanyToken>
  <Request>createToken</Request>
  <Transaction>
    <PaymentAmount>{$amount}</PaymentAmount>
    <PaymentCurrency>{$currency}</PaymentCurrency>
    <CompanyRef>{$companyRef}</CompanyRef>
    <RedirectURL>{$redirectUrl}</RedirectURL>
    <BackURL>{$backUrl}</BackURL>
    <CompanyRefUnique>0</CompanyRefUnique>
    <PTL>5</PTL>
  </Transaction>
  <Services>
    <Service>
      <ServiceType>{$this->serviceType}</ServiceType>
      <ServiceDescription>{$serviceDescription}</ServiceDescription>
      <ServiceDate>{$serviceDate}</ServiceDate>
    </Service>
  </Services>
</API3G>
XML;

        return $this->sendRequest($xml);
    }

    public function verifyToken(string $transToken): array
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<API3G>
  <CompanyToken>{$this->companyToken}</CompanyToken>
  <Request>verifyToken</Request>
  <TransactionToken>{$transToken}</TransactionToken>
</API3G>
XML;

        return $this->sendRequest($xml);
    }

    public function getPaymentUrl(string $transToken): string
    {
        return 'https://secure.3gdirectpay.com/payv2.php?ID=' . $transToken;
    }

    protected function sendRequest(string $xml): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('DPO Group cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('DPO Group API returned HTTP ' . $httpCode);
        }

        return $this->parseXmlResponse($responseBody);
    }

    protected function parseXmlResponse(string $xml): array
    {
        $parsed = simplexml_load_string($xml);
        if ($parsed === false) {
            throw new Exception('DPO Group: failed to parse XML response');
        }

        return json_decode(json_encode($parsed), true);
    }

    public function isSuccessResult(string $resultCode): bool
    {
        return $resultCode === '000';
    }

    public function isPaidResult(string $resultCode): bool
    {
        return $resultCode === '000';
    }
}
