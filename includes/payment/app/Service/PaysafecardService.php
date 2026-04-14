<?php

namespace App\Service;

use Exception;

class PaysafecardService
{
    /**
     * @var array
     */
    protected $configData = [];

    /**
     * @var array
     */
    protected $configItem = [];

    public function __construct()
    {
        $this->configData = configItem();
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.paysafecard', []);
    }

    /**
     * Initiate a Paysafecard payment and return redirect data.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processPaysafecardRequest(array $request): array
    {
        if (empty($this->configItem)) {
            throw new Exception($this->translate('paysafecard_config_missing', 'Configuration Missing'));
        }

        if (empty($this->configItem['enable'])) {
            return [
                'status' => 'disabled',
                'message' => $this->translate('paysafecard_disabled', 'Paysafecard payment method is disabled.'),
            ];
        }

        $apiKey = $this->resolveApiKey();
        $baseUrl = $this->resolveApiBaseUrl();
        $currency = $this->resolveCurrency();
        $orderId = $this->resolveOrderId($request);

        $amount = $this->resolveAmount($request, $currency);
        $amountMinor = (int) round($amount * 100);
        if ($amountMinor <= 0) {
            throw new Exception($this->translate('paysafecard_amount_invalid', 'Order amount is invalid for Paysafecard.'));
        }

        $callbackBase = getAppUrl($this->configItem['callbackUrl'] ?? 'payment-response.php');
        $successUrl = $callbackBase . '?paymentOption=paysafecard&status=success&orderId=' . rawurlencode($orderId);
        $cancelUrl = $callbackBase . '?paymentOption=paysafecard&status=cancel&orderId=' . rawurlencode($orderId);

        $payload = [
            'type' => 'PAYSAFECARD',
            'amount' => $amountMinor,
            'currency' => $currency,
            'customer' => [
                'id' => (string) ($request['payer_id'] ?? $orderId),
                'email' => isset($request['payer_email']) ? (string) $request['payer_email'] : null,
            ],
            'redirect' => [
                'success_url' => $successUrl,
                'failure_url' => $cancelUrl,
            ],
        ];

        // Remove null customer fields to avoid API validation errors
        $payload['customer'] = array_filter($payload['customer'], static function ($value) {
            return $value !== null && $value !== '';
        });

        $endpoint = rtrim($baseUrl, '/') . '/v1/payments';
        $response = $this->performRequest($endpoint, $payload, $apiKey, 'POST');

        if (!empty($response['error'])) {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? $this->translate('paysafecard_request_failed', 'Unable to initialize Paysafecard payment.'),
                'details' => $response['data'] ?? [],
            ];
        }

        $data = $response['data'];
        $paymentId = $data['id'] ?? ($data['payment_id'] ?? null);

        $redirectUrl = $data['redirect']['auth_url'] ?? $data['redirect']['authentication_url'] ?? ($data['redirect_url'] ?? null);
        if (!$redirectUrl) {
            $redirectUrl = $data['redirect']['url'] ?? null;
        }

        if (!$redirectUrl) {
            return [
                'status' => 'error',
            'message' => $this->translate('paysafecard_redirect_missing', 'Redirect URL missing in Paysafecard response.'),
            'raw' => $data,
        ];
        }

        return [
            'status' => 'success',
            'redirect_url' => $redirectUrl,
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'currency' => $currency,
            'amount' => $amount,
            'paymentOption' => 'paysafecard',
        ];
    }

    /**
     * Retrieve a Paysafecard payment.
     *
     * @param string $paymentId
     * @return array
     * @throws Exception
     */
    public function fetchPayment(string $paymentId): array
    {
        if ($paymentId === '') {
            throw new Exception($this->translate('paysafecard_id_missing', 'Paysafecard payment id missing.'));
        }
        $apiKey = $this->resolveApiKey();
        $baseUrl = $this->resolveApiBaseUrl();
        $endpoint = rtrim($baseUrl, '/') . '/v1/payments/' . rawurlencode($paymentId);

        return $this->performRequest($endpoint, null, $apiKey, 'GET');
    }

    /**
     * Perform Paysafecard API request.
     *
     * @param string $endpoint
     * @param array|null $payload
     * @param string $apiKey
     * @param string $method
     * @return array{error:bool,message?:string,data?:mixed,status_code?:int}
     */
    protected function performRequest(string $endpoint, ?array $payload, string $apiKey, string $method = 'POST'): array
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ':'),
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if (strtoupper($method) === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload ?? []);
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, $options);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['error' => true, 'message' => $curlError];
        }

        $decoded = json_decode((string) $responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => true, 'message' => $this->translate('paysafecard_invalid_json', 'Invalid JSON response from Paysafecard')];
        }

        if ($httpCode >= 400) {
            return [
                'error' => true,
                'message' => $decoded['message'] ?? $this->translate('paysafecard_request_failed', 'Paysafecard request failed'),
                'data' => $decoded,
                'status_code' => $httpCode,
            ];
        }

        return ['error' => false, 'data' => $decoded];
    }

    /**
     * Resolve the order id to use with Paysafecard.
     *
     * @param array $request
     * @return string
     * @throws Exception
     */
    protected function resolveOrderId(array $request): string
    {
        $orderId = isset($request['order_id']) ? (string) $request['order_id'] : '';
        if ($orderId === '') {
            throw new Exception($this->translate('paysafecard_order_missing', 'Order id missing for Paysafecard.'));
        }
        return $orderId;
    }

    /**
     * Resolve amount based on configured currency.
     *
     * @param array $request
     * @param string $currency
     * @return float
     */
    protected function resolveAmount(array $request, string $currency): float
    {
        $amount = 0;
        if (isset($request['amounts'][$currency])) {
            $amount = (float) $request['amounts'][$currency];
        } elseif (isset($request['order_amount'])) {
            $amount = (float) $request['order_amount'];
        }

        return (float) number_format($amount, 2, '.', '');
    }

    /**
     * Resolve API base url according to mode.
     *
     * @return string
     * @throws Exception
     */
    protected function resolveApiBaseUrl(): string
    {
        $isLive = isset($this->configItem['testMode']) ? !$this->configItem['testMode'] : (($this->configItem['mode'] ?? '') === 'live');
        $baseUrl = $isLive ? ($this->configItem['productionApiBaseUrl'] ?? '') : ($this->configItem['sandboxApiBaseUrl'] ?? '');
        if (!$baseUrl) {
            throw new Exception($this->translate('paysafecard_base_missing', 'Paysafecard API base URL is missing.'));
        }
        return $baseUrl;
    }

    /**
     * Resolve API key from config.
     *
     * @return string
     * @throws Exception
     */
    protected function resolveApiKey(): string
    {
        $apiKey = trim((string) ($this->configItem['apiKey'] ?? ''));
        if ($apiKey === '') {
            throw new Exception($this->translate('paysafecard_api_missing', 'Paysafecard API key is missing.'));
        }
        return $apiKey;
    }

    /**
     * Resolve configured currency.
     *
     * @return string
     * @throws Exception
     */
    protected function resolveCurrency(): string
    {
        $currency = strtoupper((string) ($this->configItem['currency'] ?? ''));
        if ($currency === '') {
            throw new Exception($this->translate('paysafecard_currency_missing', 'Paysafecard currency is missing.'));
        }
        return $currency;
    }

    /**
     * Translate helper with fallback.
     *
     * @param string $key
     * @param string $fallback
     * @return string
     */
    protected function translate(string $key, string $fallback): string
    {
        $lang = isset($GLOBALS['LANG']) && is_array($GLOBALS['LANG']) ? $GLOBALS['LANG'] : [];
        return isset($lang[$key]) ? (string) $lang[$key] : $fallback;
    }
}
