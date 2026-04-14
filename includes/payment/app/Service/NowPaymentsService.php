<?php

namespace App\Service;

use Exception;

class NowPaymentsService
{
    /**
     * @var array
     */
    protected $configData = [];

    /**
     * @var array
     */
    protected $configItem = [];

    /**
     * Cached raw request body for webhook validation.
     *
     * @var string|null
     */
    protected $rawWebhookBody;

    /**
     * Supported crypto currencies for pay amount conversion.
     *
     * @var string[]
     */
    private const CRYPTO_CURRENCY_ALLOWLIST = [
        'BTC',
        'ETH',
        'LTC',
        'USDT',
        'USDC',
        'BNB',
        'TRX',
        'DOGE',
        'BCH',
        'SOL',
    ];

    public function __construct()
    {
        $this->configData = configItem();
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.nowpayments', []);
    }

    /**
     * Create a NowPayments invoice and return redirect URL payload.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processNowPaymentsRequest(array $request): array
    {
        if (empty($this->configItem)) {
            throw new Exception('Configuration Missing');
        }

        if (empty($this->configItem['enable'])) {
            return [
                'status' => 'disabled',
                'message' => 'NowPayments payment method is disabled.',
            ];
        }

        $targetCryptoCurrency = strtoupper((string) ($this->configItem['currency'] ?? ''));
        if ($targetCryptoCurrency === '' || !in_array($targetCryptoCurrency, self::CRYPTO_CURRENCY_ALLOWLIST, true)) {
            throw new Exception('NowPayments currency is invalid. Please select a supported crypto currency.');
        }

        $apiKey = $this->resolveApiKey();
        $baseUrl = rtrim($this->resolveApiBaseUrl(), '/');
        $ipnSecret = trim((string) ($this->configItem['ipnSecret'] ?? ''));

        if (!$apiKey || !$baseUrl) {
            throw new Exception('NowPayments credentials are incomplete.');
        }
        if ($ipnSecret === '') {
            throw new Exception('NowPayments IPN secret is missing.');
        }

        $callbackBase = getAppUrl($this->configItem['callbackUrl'] ?? 'payment-response.php');
        $orderId = (string) ($request['order_id'] ?? '');
        if ($orderId === '') {
            throw new Exception('Order id missing for NowPayments.');
        }

        $sourceCurrency = $this->resolveSourceCurrency();
        $fiatAmount = $this->resolveFiatAmount($request);
        if ($fiatAmount <= 0) {
            throw new Exception('Order amount is invalid for NowPayments.');
        }

        $cryptoAmount = $this->estimateCryptoAmount(
            $baseUrl,
            $apiKey,
            $fiatAmount,
            $sourceCurrency,
            $targetCryptoCurrency
        );

        $successUrl = $callbackBase . '?paymentOption=nowpayments&status=success&orderId=' . rawurlencode($orderId);
        $cancelUrl = $callbackBase . '?paymentOption=nowpayments&status=cancel&orderId=' . rawurlencode($orderId);
        $ipnCallbackUrl = $callbackBase . '?paymentOption=nowpayments-ipn&orderId=' . rawurlencode($orderId);

        $description = trim((string) ($request['description'] ?? ''));
        if ($description === '') {
            $description = 'Order #' . $orderId;
        }

        $payload = [
            'price_amount' => $cryptoAmount,
            'price_currency' => $targetCryptoCurrency,
            'order_id' => $orderId,
            'order_description' => $description,
            'ipn_callback_url' => $ipnCallbackUrl,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        $endpoint = $baseUrl . '/v1/invoice';
        $response = $this->performRequest($endpoint, $payload, $apiKey);

        if (!empty($response['error'])) {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? 'Unable to initialize NowPayments invoice.',
                'details' => $response['data'] ?? [],
            ];
        }

        $data = $response['data'];
        $redirectUrl = $data['invoice_url'] ?? $data['payment_url'] ?? null;
        if (!$redirectUrl) {
            return [
                'status' => 'error',
                'message' => 'Invoice URL missing in NowPayments response.',
                'raw' => $data,
            ];
        }

        return [
            'status' => 'success',
            'redirect_url' => $redirectUrl,
            'invoice_id' => $data['id'] ?? ($data['invoice_id'] ?? null),
            'paymentOption' => 'nowpayments',
        ];
    }

    /**
     * Prepare NowPayments IPN payload for downstream processing.
     *
     * @param array $requestData
     * @return array
     */
    public function prepareIpnRequestData(array $requestData): array
    {
        $bodyData = $this->getWebhookPayload();
        $merged = array_merge($bodyData, $requestData);

        $payload = [
            'order_id' => $merged['order_id'] ?? ($merged['orderId'] ?? null),
            'status' => $merged['payment_status'] ?? ($merged['status'] ?? null),
            'amount' => $merged['price_amount'] ?? ($merged['amount'] ?? null),
            'currency' => $merged['price_currency'] ?? ($merged['currency'] ?? ($this->configItem['currency'] ?? null)),
            'transaction_id' => $merged['payment_id'] ?? ($merged['invoice_id'] ?? ($merged['id'] ?? null)),
            'signature_valid' => true,
        ];

        $secret = trim((string) ($this->configItem['ipnSecret'] ?? ''));
        $signature = $this->getSignatureHeader();

        if ($secret !== '') {
            if (!$signature) {
                $payload['signature_valid'] = false;
            } else {
                $payload['signature_valid'] = $this->validateSignature($secret, $signature);
            }
        } else {
            $payload['signature_valid'] = false;
        }

        return $payload;
    }

    /**
     * Perform NowPayments API request.
     *
     * @param string $endpoint
     * @param array $payload
     * @param string $apiKey
     * @return array{error:bool,message?:string,data?:mixed,status_code?:int}
     */
    protected function performRequest(string $endpoint, array $payload, string $apiKey): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['error' => true, 'message' => $curlError];
        }

        $decoded = json_decode((string) $responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => true, 'message' => 'Invalid JSON response from NowPayments'];
        }

        if ($httpCode >= 400) {
            return [
                'error' => true,
                'message' => $decoded['message'] ?? 'NowPayments request failed',
                'data' => $decoded,
                'status_code' => $httpCode,
            ];
        }

        return ['error' => false, 'data' => $decoded];
    }

    /**
     * Perform NowPayments API GET request.
     *
     * @param string $endpoint
     * @param string $apiKey
     * @return array{error:bool,message?:string,data?:mixed,status_code?:int}
     */
    protected function performGetRequest(string $endpoint, string $apiKey): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'x-api-key: ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['error' => true, 'message' => $curlError];
        }

        $decoded = json_decode((string) $responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => true, 'message' => 'Invalid JSON response from NowPayments'];
        }

        if ($httpCode >= 400) {
            return [
                'error' => true,
                'message' => $decoded['message'] ?? 'NowPayments request failed',
                'data' => $decoded,
                'status_code' => $httpCode,
            ];
        }

        return ['error' => false, 'data' => $decoded];
    }

    protected function resolveApiKey(): ?string
    {
        if (!empty($this->configItem['testMode'])) {
            return $this->configItem['testApiKey'] ?? null;
        }

        return $this->configItem['liveApiKey'] ?? null;
    }

    protected function resolveApiBaseUrl(): string
    {
        if (!empty($this->configItem['testMode'])) {
            return (string) ($this->configItem['sandboxApiBaseUrl'] ?? 'https://api-sandbox.nowpayments.io');
        }

        return (string) ($this->configItem['productionApiBaseUrl'] ?? 'https://api.nowpayments.io');
    }

    protected function resolveSourceCurrency(): string
    {
        global $defaultCurrency;

        $currency = strtoupper((string) ($defaultCurrency ?? 'USD'));
        if ($currency === '') {
            return 'USD';
        }

        return $currency;
    }

    /**
     * Resolve fiat order amount using the existing payment flow (default currency).
     *
     * @param array $request
     * @return float
     */
    protected function resolveFiatAmount(array $request): float
    {
        $orderAmount = $request['order_amount'] ?? null;
        if ($orderAmount !== null && is_numeric($orderAmount)) {
            return (float) $orderAmount;
        }

        return 0.0;
    }

    /**
     * Estimate crypto amount for a fiat amount.
     *
     * @param string $apiBaseUrl
     * @param string $apiKey
     * @param float $amount
     * @param string $currencyFrom
     * @param string $currencyTo
     * @return string
     * @throws Exception
     */
    protected function estimateCryptoAmount(
        string $apiBaseUrl,
        string $apiKey,
        float $amount,
        string $currencyFrom,
        string $currencyTo
    ): string {
        $query = http_build_query([
            'amount' => number_format($amount, 2, '.', ''),
            'currency_from' => strtolower($currencyFrom),
            'currency_to' => strtolower($currencyTo),
        ]);

        $endpoint = rtrim($apiBaseUrl, '/') . '/v1/estimate?' . $query;
        $response = $this->performGetRequest($endpoint, $apiKey);
        if (!empty($response['error'])) {
            throw new Exception($response['message'] ?? 'Unable to estimate NowPayments amount.');
        }

        $data = $response['data'];
        $estimated = $data['estimated_amount'] ?? $data['amount'] ?? $data['estimatedAmount'] ?? null;
        if ($estimated === null || !is_numeric($estimated) || (float) $estimated <= 0) {
            throw new Exception('Unable to estimate crypto amount for NowPayments.');
        }

        $estimatedString = (string) $estimated;
        $estimatedString = trim($estimatedString);
        if ($estimatedString === '') {
            throw new Exception('Unable to estimate crypto amount for NowPayments.');
        }

        return $estimatedString;
    }

    protected function getSignatureHeader(): ?string
    {
        $header = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? null;
        if ($header && is_string($header)) {
            return $header;
        }

        $header = $_SERVER['HTTP_X_NOWPAYMENT_SIG'] ?? null;
        if ($header && is_string($header)) {
            return $header;
        }

        return null;
    }

    protected function validateSignature(string $secret, string $signature): bool
    {
        if ($this->rawWebhookBody === null) {
            $this->rawWebhookBody = file_get_contents('php://input');
        }

        if ($this->rawWebhookBody === false) {
            return false;
        }

        $expected = hash_hmac('sha512', $this->rawWebhookBody, $secret);
        return hash_equals($expected, $signature);
    }

    protected function getWebhookPayload(): array
    {
        if ($this->rawWebhookBody === null) {
            $this->rawWebhookBody = file_get_contents('php://input');
        }

        if (!$this->rawWebhookBody) {
            return [];
        }

        $decoded = json_decode($this->rawWebhookBody, true);
        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
}
