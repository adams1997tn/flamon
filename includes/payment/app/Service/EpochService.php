<?php

namespace App\Service;

use Exception;

class EpochService
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
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.epoch', []);
    }

    /**
     * Build hosted checkout payload for standard payment flows.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processEpochRequest(array $request): array
    {
        return $this->buildHostedPayload($request, false);
    }

    /**
     * Build hosted checkout payload for subscription flows.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processEpochSubscription(array $request): array
    {
        return $this->buildHostedPayload($request, true);
    }

    /**
     * Build FlexPost style payload.
     *
     * @param array $request
     * @param bool $isSubscription
     * @return array
     * @throws Exception
     */
    protected function buildHostedPayload(array $request, bool $isSubscription): array
    {
        if (empty($this->configItem)) {
            throw new Exception('Epoch configuration is missing.');
        }

        if (empty($this->configItem['enable'])) {
            return [
                'status' => 'disabled',
                'message' => 'Epoch payment method is disabled.',
            ];
        }

        $piCode = trim((string) ($this->configItem['piCode'] ?? ''));
        if ($piCode === '') {
            throw new Exception('Epoch product code is missing.');
        }

        $endpoint = $this->resolveEndpoint();
        if ($endpoint === '') {
            throw new Exception('Epoch endpoint is missing.');
        }

        $orderId = trim((string) ($request['order_id'] ?? ''));
        if ($orderId === '') {
            throw new Exception('Order id missing for Epoch.');
        }

        $currency = strtoupper((string) ($request['currency'] ?? ($this->configItem['currency'] ?? 'USD')));
        if ($currency === '') {
            $currency = 'USD';
        }

        $amount = $this->resolveAmount($request, $currency);
        if ($amount <= 0) {
            throw new Exception('Order amount is invalid for Epoch.');
        }
        $amountFormatted = number_format($amount, 2, '.', '');

        $paymentType = trim((string) ($request['payment_type'] ?? ($isSubscription ? 'subscription' : 'point')));
        if ($paymentType === '') {
            $paymentType = $isSubscription ? 'subscription' : 'point';
        }

        $envMarker = !empty($this->configItem['testMode']) ? 'test' : 'live';
        $userId = (string) ($request['payer_id'] ?? ($request['subscriber_id'] ?? ''));
        $planId = (string) (
            $request['plan_id']
            ?? $request['credit_plan_id']
            ?? $request['creditPlan']
            ?? $request['product_id']
            ?? ''
        );

        $nonce = isset($request['epoch_nonce']) && $request['epoch_nonce'] !== ''
            ? (string) $request['epoch_nonce']
            : $this->generateNonce();
        $signature = isset($request['epoch_signature']) && $request['epoch_signature'] !== ''
            ? (string) $request['epoch_signature']
            : $this->buildSignature($orderId, $nonce, $userId, $planId, $envMarker);

        $successUrl = (string) ($request['success_url'] ?? $request['return_url'] ?? '');
        if ($successUrl === '') {
            $successUrl = $this->buildAbsoluteUrl($this->configItem['successUrl'] ?? 'payment-response.php', [
                'paymentOption' => 'epoch',
                'status' => 'success',
                'orderId' => $orderId,
            ]);
        }

        $cancelUrl = (string) ($request['cancel_url'] ?? '');
        if ($cancelUrl === '') {
            $cancelUrl = $this->buildAbsoluteUrl($this->configItem['cancelUrl'] ?? 'payment-response.php', [
                'paymentOption' => 'epoch',
                'status' => 'cancel',
                'orderId' => $orderId,
            ]);
        }

        $postbackUrl = $this->buildAbsoluteUrl($this->configItem['postbackUrl'] ?? 'epoch_webhook.php');
        $returnMode = (string) ($this->configItem['returnMode'] ?? 'pending');
        $template = trim((string) ($this->configItem['template'] ?? ''));

        $formFields = [
            'pi_code' => $piCode,
            'amount' => $amountFormatted,
            'currency' => $currency,
            'x_order_key' => $orderId,
            'x_user_id' => $userId,
            'x_plan_id' => $planId,
            'x_payment_type' => $paymentType,
            'x_env' => $envMarker,
            'x_nonce' => $nonce,
            'x_sig' => $signature,
            'x_success_url' => $successUrl,
            'x_cancel_url' => $cancelUrl,
            'x_postback_url' => $postbackUrl,
            'x_return_mode' => $returnMode,
        ];

        if ($isSubscription) {
            $formFields['x_subscription_scope'] = (string) ($request['subscription_scope'] ?? 'profile');
            $formFields['x_subscription_ref_id'] = (string) ($request['subscription_ref_id'] ?? '');
        }

        $description = trim((string) ($request['description'] ?? ''));
        if ($description !== '') {
            $formFields['x_description'] = $description;
        }
        if ($template !== '') {
            $formFields['x_template'] = $template;
        }
        if (!empty($request['metadata']) && is_array($request['metadata'])) {
            $metadataJson = json_encode($request['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($metadataJson !== false) {
                $formFields['x_meta'] = base64_encode($metadataJson);
            }
        }

        $this->log('Outgoing checkout attempt', [
            'order_key' => $orderId,
            'payment_type' => $paymentType,
            'amount' => $amountFormatted,
            'currency' => $currency,
            'is_subscription' => $isSubscription ? 1 : 0,
            'endpoint' => $endpoint,
            'env' => $envMarker,
        ]);

        return [
            'status' => 'success',
            'paymentOption' => 'epoch',
            'post_url' => $endpoint,
            'form_fields' => $formFields,
            'redirect_method' => 'post',
            'epoch_nonce' => $nonce,
            'epoch_signature' => $signature,
        ];
    }

    /**
     * Resolve endpoint according to test/live mode.
     *
     * @return string
     */
    protected function resolveEndpoint(): string
    {
        $testMode = !empty($this->configItem['testMode']);
        if ($testMode) {
            $endpoint = trim((string) ($this->configItem['testEndpoint'] ?? ''));
        } else {
            $endpoint = trim((string) ($this->configItem['liveEndpoint'] ?? ''));
        }

        if ($endpoint === '') {
            $endpoint = trim((string) ($this->configItem['defaultEndpoint'] ?? 'https://wnu.com/secure/services/'));
        }

        return $endpoint;
    }

    /**
     * Resolve amount value for configured currency.
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
     * Build HMAC signature for pass-thru verification.
     *
     * @param string $orderId
     * @param string $nonce
     * @param string $userId
     * @param string $planId
     * @param string $envMarker
     * @return string
     */
    protected function buildSignature(string $orderId, string $nonce, string $userId, string $planId, string $envMarker): string
    {
        $secret = trim((string) ($this->configItem['postbackSecret'] ?? ''));
        if ($secret === '') {
            $secret = (string) sha1($orderId . ':' . $nonce);
        }
        $payload = implode('|', [$orderId, $nonce, $userId, $planId, $envMarker]);
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Generate nonce value.
     *
     * @return string
     */
    protected function generateNonce(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return 'epoch_' . uniqid('', true);
        }
    }

    /**
     * Build absolute URL from relative callback values.
     *
     * @param string $path
     * @param array $query
     * @return string
     */
    protected function buildAbsoluteUrl(string $path, array $query = []): string
    {
        $url = getAppUrl($path);
        if (!empty($query)) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }
        return $url;
    }

    /**
     * Write gateway diagnostics log.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $message, array $context = []): void
    {
        $logDir = dirname(__DIR__, 3) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $line = date('c') . ' ' . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;
        @file_put_contents($logDir . '/epoch.log', $line, FILE_APPEND);
    }
}
