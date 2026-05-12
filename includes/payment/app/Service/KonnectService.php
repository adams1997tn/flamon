<?php

namespace App\Service;

use Exception;

/**
 * Konnect Network gateway adapter for the App\Service dispatcher.
 *
 * Reads its config from payments.gateway_configuration.konnect (paymentConfig.php)
 * and exposes a single processKonnectRequest() that returns the same shape as
 * MonerooService — { status: 'success', redirect_url: ..., paymentOption: 'konnect' }.
 *
 * The actual HTTP/init logic lives in the global \KonnectService helper.
 */
class KonnectService
{
    /** @var array */
    protected $configData = [];

    /** @var array */
    protected $configItem = [];

    public function __construct()
    {
        $this->configData = configItem();
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.konnect', []);
    }

    /**
     * Initialise a Konnect hosted payment session.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processKonnectRequest(array $request): array
    {
        if (empty($this->configItem)) {
            throw new Exception('Configuration Missing');
        }

        if (empty($this->configItem['enable'])) {
            return [
                'status'  => 'disabled',
                'message' => 'Konnect payment method is disabled.',
            ];
        }

        $isTestMode = !empty($this->configItem['testMode']);
        $apiKey   = $isTestMode ? (string) ($this->configItem['testApiKey'] ?? '')   : (string) ($this->configItem['liveApiKey'] ?? '');
        $walletId = $isTestMode ? (string) ($this->configItem['testWalletId'] ?? '') : (string) ($this->configItem['liveWalletId'] ?? '');
        $apiBase  = $isTestMode
            ? rtrim((string) ($this->configItem['sandboxApiBaseUrl'] ?? 'https://api.preprod.konnect.network/api/v2'), '/')
            : rtrim((string) ($this->configItem['productionApiBaseUrl'] ?? 'https://api.konnect.network/api/v2'), '/');

        if ($apiKey === '' || $walletId === ''
            || stripos($apiKey, 'PLACEHOLDER') !== false
            || stripos($walletId, 'PLACEHOLDER') !== false) {
            return [
                'status'  => 'error',
                'message' => 'Konnect credentials are incomplete. Set the API key and Receiver Wallet ID in admin → Konnect.',
            ];
        }

        $currency = strtoupper((string) ($this->configItem['currency'] ?? 'TND'));
        $orderId  = (string) ($request['order_id'] ?? '');
        if ($orderId === '') {
            throw new Exception('Konnect requires an order_id.');
        }

        $amountDecimal = $this->resolveAmount($request, $currency);
        if ($amountDecimal <= 0) {
            return [
                'status'  => 'error',
                'message' => 'Konnect amount is invalid.',
            ];
        }

        $description = trim((string) ($request['description'] ?? ''));
        if ($description === '') {
            $description = 'Order #' . $orderId;
        }
        if (function_exists('mb_strlen') && mb_strlen($description) > 250) {
            $description = mb_substr($description, 0, 247) . '...';
        }

        $fullName = trim((string) ($request['payer_name'] ?? ''));
        if ($fullName !== '') {
            $parts = preg_split('/\s+/', $fullName, 2);
            $firstName = $parts[0] ?? 'Customer';
            $lastName  = $parts[1] ?? $firstName;
        } else {
            $firstName = 'Customer';
            $lastName  = 'User';
        }

        $callbackBase = (string) ($this->configItem['callbackUrl'] ?? 'payment-response.php');
        $successUrl   = getAppUrl($callbackBase) . '?paymentOption=konnect&status=paid&orderId=' . rawurlencode($orderId);
        $failUrl      = getAppUrl($callbackBase) . '?paymentOption=konnect&status=cancelled&orderId=' . rawurlencode($orderId);
        $webhookUrl   = getAppUrl((string) ($this->configItem['webhookUrl'] ?? 'konnect_webhook.php'));
        $secret       = (string) ($this->configItem['webhookSecret'] ?? '');
        if ($secret !== '') {
            $webhookUrl .= (strpos($webhookUrl, '?') === false ? '?' : '&') . 'secret=' . rawurlencode($secret);
        }

        $payload = [
            'receiverWalletId'        => $walletId,
            'token'                   => $currency,
            'amount'                  => (int) round($amountDecimal * 1000), // millimes
            'type'                    => 'immediate',
            'description'             => $description,
            'lifespan'                => 30,
            'checkoutForm'            => true,
            'addPaymentFeesToAmount'  => false,
            'silentWebhook'           => true,
            'theme'                   => 'light',
            'orderId'                 => $orderId,
            'firstName'               => $firstName,
            'lastName'                => $lastName,
            'webhook'                 => $webhookUrl,
            'successUrl'              => $successUrl,
            'failUrl'                 => $failUrl,
        ];

        if (!empty($request['payer_email']))     { $payload['email']       = (string) $request['payer_email']; }
        if (!empty($request['payer_phone']))     { $payload['phoneNumber'] = (string) $request['payer_phone']; }
        if (!empty($request['phoneNumber']))     { $payload['phoneNumber'] = (string) $request['phoneNumber']; }

        $endpoint = $apiBase . '/payments/init-payment';
        $resp     = $this->httpRequest('POST', $endpoint, $payload, $apiKey);

        if (!empty($resp['error'])) {
            return [
                'status'  => 'error',
                'message' => $resp['message'] ?? 'Unable to initialise Konnect payment.',
            ];
        }

        $data = $resp['data'] ?? [];
        $payUrl     = (string) ($data['payUrl'] ?? '');
        $paymentRef = (string) ($data['paymentRef'] ?? '');

        if ($payUrl === '' || $paymentRef === '') {
            return [
                'status'  => 'error',
                'message' => 'Konnect response missing payUrl/paymentRef.',
                'raw'     => $data,
            ];
        }

        return [
            'status'        => 'success',
            'redirect_url'  => $payUrl,
            'payment_ref'   => $paymentRef,
            'paymentOption' => 'konnect',
        ];
    }

    /**
     * Resolve decimal amount in $currency from the dispatcher payload.
     */
    protected function resolveAmount(array $request, string $currency): float
    {
        if (isset($request['amounts']) && is_array($request['amounts'])) {
            $amounts = $request['amounts'];
            if (isset($amounts[$currency])) {
                return (float) $amounts[$currency];
            }
            if (isset($amounts[strtoupper($currency)])) {
                return (float) $amounts[strtoupper($currency)];
            }
            // Fallback: first numeric value
            foreach ($amounts as $value) {
                if (is_numeric($value)) {
                    return (float) $value;
                }
            }
        }
        if (isset($request['order_amount']) && is_numeric($request['order_amount'])) {
            return (float) $request['order_amount'];
        }
        if (isset($request['amount']) && is_numeric($request['amount'])) {
            return (float) $request['amount'];
        }
        return 0.0;
    }

    protected function httpRequest(string $method, string $url, array $payload, string $apiKey): array
    {
        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'x-api-key: ' . $apiKey,
        ];
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        ];
        if (strtoupper($method) !== 'GET') {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $err    = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return ['error' => true, 'message' => 'Konnect HTTP error: ' . $err];
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if ($status < 200 || $status >= 300) {
            $msg = is_array($decoded) ? json_encode($decoded) : (string) $raw;
            return ['error' => true, 'message' => 'Konnect API ' . $status . ': ' . $msg];
        }

        return ['error' => false, 'data' => is_array($decoded) ? $decoded : []];
    }
}
