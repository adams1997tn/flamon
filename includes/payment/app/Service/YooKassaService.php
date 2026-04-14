<?php

namespace App\Service;

use Exception;

class YooKassaService
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
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.yookassa', []);
    }

    /**
     * Create a YooKassa payment and return confirmation data.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processYooKassaRequest(array $request): array
    {
        if (empty($this->configItem)) {
            throw new Exception('Configuration Missing');
        }

        if (empty($this->configItem['enable'])) {
            return [
                'status' => 'disabled',
                'message' => 'YooKassa payment method is disabled.',
            ];
        }

        $orderId = $this->resolveOrderId($request);
        $currency = $this->resolveCurrency();
        $amount = $this->resolveAmount($request, $currency);
        if ($amount <= 0) {
            throw new Exception('Order amount is invalid for YooKassa.');
        }

        $description = trim((string) ($request['description'] ?? ''));
        if ($description === '') {
            $description = 'Order #' . $orderId;
        }

        $returnUrl = (string) ($request['return_url'] ?? $request['returnUrl'] ?? '');
        if ($returnUrl === '') {
            $returnUrl = getAppUrl($this->configItem['callbackUrl'] ?? 'payment-response.php')
                . '?paymentOption=yookassa&orderId=' . rawurlencode($orderId);
        }

        $payload = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
            ],
            'capture' => true,
            'description' => $description,
            'metadata' => [
                'order_key' => $orderId,
                'paymentOption' => 'yookassa',
                'payment_type' => $request['payment_type'] ?? null,
            ],
        ];

        if (array_key_exists('capture', $request)) {
            $payload['capture'] = (bool) $request['capture'];
        }

        $metadata = $payload['metadata'];
        if (isset($request['metadata']) && is_array($request['metadata'])) {
            $metadata = array_merge($metadata, $request['metadata']);
        }
        $payload['metadata'] = array_filter($metadata, static function ($value) {
            return $value !== null && $value !== '';
        });

        $confirmation = [
            'type' => 'redirect',
            'return_url' => $returnUrl,
        ];
        if (array_key_exists('confirmation', $request)) {
            if ($request['confirmation'] === null || $request['confirmation'] === false) {
                $confirmation = null;
            } elseif (is_array($request['confirmation'])) {
                $confirmation = $request['confirmation'];
            }
        }
        if ($confirmation !== null) {
            $payload['confirmation'] = $confirmation;
        }

        if (array_key_exists('save_payment_method', $request)) {
            $payload['save_payment_method'] = (bool) $request['save_payment_method'];
        }

        if (!empty($request['payment_method_id'])) {
            $payload['payment_method_id'] = (string) $request['payment_method_id'];
        }

        $idempotenceKey = (string) ($request['yookassa_idempotence_key'] ?? $request['idempotence_key'] ?? '');
        if ($idempotenceKey === '') {
            $idempotenceKey = $this->generateIdempotenceKey();
        }

        $endpoint = rtrim($this->resolveApiBaseUrl(), '/') . '/v3/payments';
        $response = $this->performRequest($endpoint, $payload, 'POST', $idempotenceKey);

        if (!empty($response['error'])) {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? 'Unable to initialize YooKassa payment.',
                'details' => $response['data'] ?? [],
            ];
        }

        $data = $response['data'];
        $confirmationUrl = $data['confirmation']['confirmation_url'] ?? $data['confirmation_url'] ?? null;
        if (!$confirmationUrl) {
            return [
                'status' => 'error',
                'message' => 'Confirmation URL missing in YooKassa response.',
                'raw' => $data,
            ];
        }

        return [
            'status' => 'success',
            'confirmation_url' => $confirmationUrl,
            'payment_id' => $data['id'] ?? null,
            'amount' => $amount,
            'currency' => $currency,
            'idempotence_key' => $idempotenceKey,
            'paymentOption' => 'yookassa',
        ];
    }

    /**
     * Retrieve a YooKassa payment.
     *
     * @param string $paymentId
     * @return array
     * @throws Exception
     */
    public function fetchPayment(string $paymentId): array
    {
        if ($paymentId === '') {
            throw new Exception('YooKassa payment id missing.');
        }

        $endpoint = rtrim($this->resolveApiBaseUrl(), '/') . '/v3/payments/' . rawurlencode($paymentId);
        return $this->performRequest($endpoint, null, 'GET');
    }

    /**
     * Perform YooKassa API request.
     *
     * @param string $endpoint
     * @param array|null $payload
     * @param string $method
     * @param string|null $idempotenceKey
     * @return array{error:bool,message?:string,data?:mixed,status_code?:int}
     * @throws Exception
     */
    protected function performRequest(string $endpoint, ?array $payload, string $method = 'POST', ?string $idempotenceKey = null): array
    {
        [$shopId, $secretKey] = $this->resolveCredentials();
        if ($shopId === '' || $secretKey === '') {
            throw new Exception('YooKassa credentials are incomplete.');
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey),
        ];

        if ($idempotenceKey) {
            $headers[] = 'Idempotence-Key: ' . $idempotenceKey;
        }

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
            return ['error' => true, 'message' => 'Invalid JSON response from YooKassa'];
        }

        if ($httpCode >= 400) {
            return [
                'error' => true,
                'message' => $decoded['description'] ?? $decoded['message'] ?? 'YooKassa request failed',
                'data' => $decoded,
                'status_code' => $httpCode,
            ];
        }

        return ['error' => false, 'data' => $decoded];
    }

    /**
     * Resolve the order id to use with YooKassa.
     *
     * @param array $request
     * @return string
     * @throws Exception
     */
    protected function resolveOrderId(array $request): string
    {
        $orderId = isset($request['order_id']) ? (string) $request['order_id'] : '';
        if ($orderId === '') {
            throw new Exception('Order id missing for YooKassa.');
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
        if (isset($request['amounts']) && is_array($request['amounts']) && isset($request['amounts'][$currency])) {
            return (float) $request['amounts'][$currency];
        }
        if (isset($request['order_amount']) && is_numeric($request['order_amount'])) {
            return (float) $request['order_amount'];
        }
        if (isset($request['amount']) && is_numeric($request['amount'])) {
            return (float) $request['amount'];
        }
        return 0.0;
    }

    /**
     * Resolve currency for the payment.
     *
     * @return string
     */
    protected function resolveCurrency(): string
    {
        $currency = strtoupper((string) ($this->configItem['currency'] ?? 'RUB'));
        return $currency !== '' ? $currency : 'RUB';
    }

    /**
     * Resolve API base URL for YooKassa.
     *
     * @return string
     */
    protected function resolveApiBaseUrl(): string
    {
        $baseUrl = trim((string) ($this->configItem['apiBaseUrl'] ?? 'https://api.yookassa.ru'));
        return $baseUrl !== '' ? $baseUrl : 'https://api.yookassa.ru';
    }

    /**
     * Resolve YooKassa credentials depending on mode.
     *
     * @return array{string,string}
     */
    protected function resolveCredentials(): array
    {
        $testMode = !empty($this->configItem['testMode']);
        if ($testMode) {
            $shopId = (string) ($this->configItem['testShopId'] ?? '');
            $secret = (string) ($this->configItem['testSecretKey'] ?? '');
        } else {
            $shopId = (string) ($this->configItem['liveShopId'] ?? '');
            $secret = (string) ($this->configItem['liveSecretKey'] ?? '');
        }

        return [$shopId, $secret];
    }

    /**
     * Generate an idempotence key for YooKassa requests.
     *
     * @return string
     */
    protected function generateIdempotenceKey(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return 'yookassa_' . uniqid('', true);
        }
    }
}
