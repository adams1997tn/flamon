<?php

namespace App\Service;

use Exception;

class MonerooService
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

    public function __construct()
    {
        $this->configData = configItem();
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.moneroo', []);
    }

    /**
     * Create a Moneroo checkout and return redirect URL payload.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processMonerooRequest(array $request): array
    {
        if (empty($this->configItem)) {
            throw new Exception('Configuration Missing');
        }

        if (empty($this->configItem['enable'])) {
            return [
                'status' => 'disabled',
                'message' => 'Moneroo payment method is disabled.'
            ];
        }

        $currency = $this->configItem['currency'] ?? 'USD';
        $amount = $request['amounts'][$currency] ?? null;
        if ($amount === null) {
            throw new Exception('Currency amount mapping missing for Moneroo.');
        }

        $apiKey = $this->resolveApiKey();
        $projectId = $this->resolveProjectId();
        $baseUrl = rtrim($this->configItem['apiBaseUrl'] ?? '', '/');

        if (!$apiKey || !$projectId || !$baseUrl) {
            throw new Exception('Moneroo credentials are incomplete.');
        }

        $endpoint = $baseUrl . '/v1/payments/initialize';
        $successUrl = getAppUrl($this->configItem['callbackUrl']) . '?paymentOption=moneroo&status=paid&orderId=' . rawurlencode($request['order_id']);
        $cancelUrl = getAppUrl($this->configItem['callbackUrl']) . '?paymentOption=moneroo&status=cancelled&orderId=' . rawurlencode($request['order_id']);
        $callbackUrl = getAppUrl($this->configItem['callbackUrl']) . '?paymentOption=moneroo&status=callback&orderId=' . rawurlencode($request['order_id']);

        $fullName = trim((string) ($request['payer_name'] ?? ''));
        if ($fullName !== '') {
            $nameParts = preg_split('/\s+/', $fullName, 2);
            $firstName = $nameParts[0] ?? 'Customer';
            $lastName = $nameParts[1] ?? ($firstName !== '' ? $firstName : 'User');
        } else {
            $firstName = 'Customer';
            $lastName = 'User';
        }

        $description = trim((string) ($request['description'] ?? ''));
        if ($description === '') {
            $description = 'Order #' . $request['order_id'];
        }

        $paymentType = isset($request['payment_type']) ? (string) $request['payment_type'] : 'point';

        $payload = [
            'project_id' => $projectId,
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $request['order_id'],
            'customer' => [
                'email' => $request['payer_email'] ?? null,
                'name' => $fullName ?: null,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
            'description' => $description,
            'return_url' => $successUrl,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'paymentOption' => 'moneroo',
                'creditPlan' => $request['creditPlan'] ?? null,
                'payment_type' => $paymentType,
            ],
        ];

        $response = $this->performRequest($endpoint, $payload, $apiKey, 'POST');

        if (!empty($response['error'])) {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? 'Unable to initialize Moneroo checkout.',
                'details' => $response['data'] ?? []
            ];
        }

        $responsePayload = $response['data'];
        $redirectUrl = $responsePayload['checkout_url'] ?? ($responsePayload['data']['checkout_url'] ?? null);
        if (!$redirectUrl) {
            return [
                'status' => 'error',
                'message' => 'Checkout URL missing in Moneroo response.',
                'raw' => $responsePayload,
            ];
        }

        return [
            'status' => 'success',
            'redirect_url' => $redirectUrl,
            'reference' => $responsePayload['reference'] ?? ($responsePayload['data']['reference'] ?? null),
            'paymentOption' => 'moneroo'
        ];
    }

    /**
     * Prepare Moneroo webhook/redirect payload for downstream processing.
     *
     * @param array $requestData
     * @return array
     */
    public function prepareIpnRequestData(array $requestData): array
    {
        $bodyData = $this->getWebhookPayload();
        $merged = array_merge($bodyData, $requestData);

        $payload = [
            'order_id' => $merged['orderId'] ?? $merged['order_id'] ?? null,
            'status' => $merged['status'] ?? null,
            'amount' => $merged['amount'] ?? null,
            'currency' => $merged['currency'] ?? ($this->configItem['currency'] ?? null),
            'transaction_id' => $merged['transaction_id'] ?? $merged['reference'] ?? null,
            'signature_valid' => true,
        ];

        $secret = $this->configItem['webhookSecret'] ?? null;
        $signature = $_SERVER['HTTP_X_MONEROO_SIGNATURE'] ?? ($merged['signature'] ?? null);

        if ($secret && $signature) {
            $payload['signature_valid'] = $this->validateSignature($secret, $signature);
        }

        return $payload;
    }

    /**
     * Perform Moneroo API request.
     *
     * @param string $endpoint
     * @param array $payload
     * @param string $apiKey
     * @return array
     */
    protected function performRequest(string $endpoint, array $payload, string $apiKey, string $method = 'POST'): array
    {
        $ch = curl_init($endpoint);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: ' . 'Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ];

        if (strtoupper($method) === 'GET') {
            if (!empty($payload)) {
                $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . http_build_query($payload);
                curl_setopt($ch, CURLOPT_URL, $endpoint);
            }
            $options[CURLOPT_HTTPGET] = true;
        } else {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($ch, $options);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['error' => true, 'message' => $curlError];
        }

        $decoded = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => true, 'message' => 'Invalid JSON response from Moneroo'];
        }

        if ($httpCode >= 400) {
            return [
                'error' => true,
                'message' => $decoded['message'] ?? 'Moneroo request failed',
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

    protected function resolveProjectId(): ?string
    {
        if (!empty($this->configItem['testMode'])) {
            return $this->configItem['testProjectId'] ?? null;
        }
        return $this->configItem['liveProjectId'] ?? null;
    }

    protected function validateSignature(string $secret, string $signature): bool
    {
        if ($this->rawWebhookBody === null) {
            $this->rawWebhookBody = file_get_contents('php://input');
        }

        if ($this->rawWebhookBody === false) {
            return false;
        }

        $expected = hash_hmac('sha256', $this->rawWebhookBody, $secret);
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
