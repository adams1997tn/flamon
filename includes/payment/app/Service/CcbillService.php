<?php

namespace App\Service;

use Exception;

class CcbillService
{
    /**
     * @var array|null
     */
    protected $configData;

    /**
     * @var array
     */
    protected $configItem = [];

    /**
     * Supported currency code mapping (ISO 4217 -> CCBill numeric)
     *
     * @var array<string,string>
     */
    protected $currencyMap = [
        'USD' => '840',
        'EUR' => '978',
        'GBP' => '826',
        'AUD' => '036',
        'CAD' => '124',
        'JPY' => '392',
        'NZD' => '554',
        'CHF' => '756'
    ];

    public function __construct()
    {
        $this->configData = configItem();
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.ccbill', []) ?: [];
    }

    /**
     * Build redirect payload for CCBill checkout (single charge).
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processCcbillRequest(array $request): array
    {
        if (empty($this->configItem) || empty($this->configItem['enable'])) {
            throw new Exception('CCBill is disabled', 1);
        }

        $account = trim((string) ($this->configItem['accountNumber'] ?? ''));
        $subaccount = trim((string) ($this->configItem['subAccountNumber'] ?? ''));
        $flexFormId = trim((string) ($this->configItem['flexFormId'] ?? ''));
        $saltKey = (string) ($this->configItem['saltKey'] ?? '');
        $currency = strtoupper((string) ($this->configItem['currency'] ?? 'USD'));

        if ($account === '' || $subaccount === '' || $flexFormId === '' || $saltKey === '') {
            throw new Exception('Missing CCBill credentials', 1);
        }

        $amount = $this->resolveAmount($request, $currency);
        $orderId = (string) ($request['order_id'] ?? '');
        if ($orderId === '') {
            throw new Exception('Order reference missing', 1);
        }

        $customerEmail = (string) ($request['payer_email'] ?? '');
        $customerName = trim((string) ($request['payer_name'] ?? ''));

        $initialPrice = number_format($amount, 2, '.', '');
        $initialPeriod = '2'; // minimum allowed by CCBill
        $currencyCode = $this->mapCurrencyCode($currency);

        $baseUrl = rtrim($this->configItem['apiBaseUrl'] ?? 'https://api.ccbill.com/wap-frontflex/flexforms', '/');
        $returnUrl = $this->buildAbsoluteUrl($this->configItem['successUrl'] ?? 'payment-response.php', [
            'paymentOption' => 'ccbill',
            'status' => 'success',
            'orderId' => $orderId,
        ]);
        $cancelUrl = $this->buildAbsoluteUrl($this->configItem['cancelUrl'] ?? 'payment-response.php', [
            'paymentOption' => 'ccbill',
            'status' => 'cancel',
            'orderId' => $orderId,
        ]);

        $paymentType = strtolower((string) ($request['payment_type'] ?? 'point'));
        if ($paymentType === '') {
            $paymentType = 'point';
        }
        $callbackType = $paymentType === 'point' ? 'wallet' : $paymentType;

        $payerId = isset($request['payer_id']) ? (int) $request['payer_id'] : null;
        $creditPlanId = isset($request['credit_plan_id']) ? (int) $request['credit_plan_id'] : (isset($request['creditPlan']) ? (int) $request['creditPlan'] : null);
        $productId = isset($request['product_id']) ? (int) $request['product_id'] : (isset($request['creditPlan']) ? (int) $request['creditPlan'] : null);
        $productOwnerId = isset($request['product_owner_id']) ? (int) $request['product_owner_id'] : null;

        $metadata = [
            'type' => $paymentType,
            'order_key' => $orderId,
            'amount' => $amount,
            'amount_formatted' => $initialPrice,
            'currency' => $currency,
            'payer_id' => $payerId,
            'credit_plan_id' => $creditPlanId,
            'product_id' => $productId,
            'product_owner_id' => $productOwnerId,
            'payment_option' => $request['paymentOption'] ?? 'ccbill',
            'created_at' => time(),
        ];

        if (isset($request['order_amount'])) {
            $metadata['order_amount'] = (float) $request['order_amount'];
        }
        if (isset($request['description'])) {
            $metadata['description'] = (string) $request['description'];
        }
        if (isset($request['taxes'])) {
            $metadata['taxes'] = (float) $request['taxes'];
        }

        $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($metadataJson === false) {
            throw new Exception('Unable to encode CCBill metadata', 1);
        }
        $customPayload = base64_encode($metadataJson);

        $query = [
            'clientAccnum'      => $account,
            'clientSubacc'      => $subaccount,
            'formName'          => $flexFormId,
            'initialPrice'      => $initialPrice,
            'initialPeriod'     => $initialPeriod,
            'currencyCode'      => $currencyCode,
            'formDigest'        => $this->buildSinglePurchaseDigest($initialPrice, $initialPeriod, $currencyCode, $saltKey),
            'customer_fname'    => substr($customerName, 0, 50),
            'customer_email'    => substr($customerEmail, 0, 100),
            'custom1'           => $paymentType,
            'custom2'           => $customPayload,
            'type'              => $callbackType,
            'user'              => $payerId ?: null,
            'amountFixed'       => $initialPrice,
            'priceOriginal'     => $initialPrice,
            'taxes'             => isset($metadata['taxes']) ? $metadata['taxes'] : 0,
            'returnURL'         => $returnUrl,
            'cancelURL'         => $cancelUrl,
        ];

        if (!empty($productOwnerId)) {
            $query['creator'] = $productOwnerId;
        }
        if (!empty($productId)) {
            $query['product'] = $productId;
        }
        if (!empty($creditPlanId)) {
            $query['plan'] = $creditPlanId;
        }

        $redirectUrl = $baseUrl . '/' . rawurlencode($flexFormId) . '?' . http_build_query($query);

        return [
            'status'        => 'success',
            'redirect_url'  => $redirectUrl,
            'paymentOption' => 'ccbill'
        ];
    }

    /**
     * Cancel an existing recurring subscription via CCBill management API.
     *
     * @param string $subscriptionId
     * @return array{status:bool,response:array,message?:string}
     * @throws Exception
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        if (empty($subscriptionId)) {
            throw new Exception('Missing subscription reference', 1);
        }
        if (empty($this->configItem) || empty($this->configItem['enable'])) {
            throw new Exception('CCBill is disabled', 1);
        }
        $account = trim((string) ($this->configItem['accountNumber'] ?? ''));
        $subaccount = trim((string) ($this->configItem['subAccountNumber'] ?? ''));
        $username = trim((string) ($this->configItem['cancelUsername'] ?? ''));
        $password = (string) ($this->configItem['cancelPassword'] ?? '');
        if ($account === '' || $subaccount === '' || $username === '' || $password === '') {
            throw new Exception('Missing CCBill cancellation credentials', 1);
        }

        $endpoint = $this->configItem['managementUrl'] ?? 'https://datalink.ccbill.com/utils/subscriptionManagement.cgi';
        $payload = [
            'clientAccnum'      => $account,
            'clientSubacc'      => $subaccount,
            'username'          => $username,
            'password'          => $password,
            'subscriptionId'    => $subscriptionId,
            'action'            => 'cancelSubscription',
        ];

        $rawResponse = $this->performHttpPost($endpoint, $payload);
        $parsed = $this->parseResponseBody($rawResponse);
        $result = strtoupper((string) ($parsed['RESULT'] ?? ''));
        if ($result === 'SUCCESS') {
            return [
                'status'   => true,
                'response' => $parsed,
            ];
        }

        $errorMessage = $parsed['ERROR_CODE'] ?? ($parsed['ERROR'] ?? 'Unknown CCBill response');
        return [
            'status'   => false,
            'response' => $parsed,
            'message'  => $errorMessage,
        ];
    }

    /**
     * Build redirect payload for CCBill recurring subscription.
     *
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function processCcbillSubscription(array $request): array
    {
        if (empty($this->configItem) || empty($this->configItem['enable'])) {
            throw new Exception('CCBill is disabled', 1);
        }

        $account = trim((string) ($this->configItem['accountNumber'] ?? ''));
        $subaccount = trim((string) ($this->configItem['subAccountNumber'] ?? ''));
        $flexFormId = trim((string) ($this->configItem['flexFormId'] ?? ''));
        $saltKey = (string) ($this->configItem['saltKey'] ?? '');
        $currency = strtoupper((string) ($this->configItem['currency'] ?? 'USD'));

        if ($account === '' || $subaccount === '' || $flexFormId === '' || $saltKey === '') {
            throw new Exception('Missing CCBill credentials', 1);
        }

        $amount = (float) ($request['amount'] ?? 0);
        if ($amount <= 0) {
            throw new Exception('Invalid subscription amount', 1);
        }

        $intervalDays = (int) ($request['interval_days'] ?? 0);
        if ($intervalDays <= 0) {
            throw new Exception('Invalid subscription interval', 1);
        }

        $orderId = (string) ($request['order_id'] ?? '');
        if ($orderId === '') {
            throw new Exception('Order reference missing', 1);
        }

        $customerEmail = (string) ($request['payer_email'] ?? '');
        $customerName = trim((string) ($request['payer_name'] ?? ''));

        $initialPrice = number_format($amount, 2, '.', '');
        $initialPeriod = (string) $intervalDays;
        $recurringPrice = $initialPrice;
        $recurringPeriod = (string) $intervalDays;
        $numRebills = '99';
        $currencyCode = $this->mapCurrencyCode($currency);

        $baseUrl = rtrim($this->configItem['apiBaseUrl'] ?? 'https://api.ccbill.com/wap-frontflex/flexforms', '/');
        $returnUrl = $this->buildAbsoluteUrl($this->configItem['successUrl'] ?? 'payment-response.php', [
            'paymentOption' => 'ccbill',
            'status' => 'success',
            'orderId' => $orderId,
            'kind' => 'subscription',
        ]);
        $cancelUrl = $this->buildAbsoluteUrl($this->configItem['cancelUrl'] ?? 'payment-response.php', [
            'paymentOption' => 'ccbill',
            'status' => 'cancel',
            'orderId' => $orderId,
            'kind' => 'subscription',
        ]);

        $metadata = $request['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $metadata['type'] = 'subscription';
        $metadata['amount'] = $amount;
        $metadata['amount_formatted'] = $initialPrice;
        $metadata['currency'] = $currency;
        $metadata['order_key'] = $orderId;
        $metadata['interval_days'] = $intervalDays;
        $metadata['created_at'] = time();
        if (isset($request['taxes']) && !isset($metadata['taxes'])) {
            $metadata['taxes'] = (float) $request['taxes'];
        }

        try {
            $customPayload = base64_encode(json_encode($metadata, JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            throw new Exception('Unable to encode CCBill metadata', 1, $exception);
        }

        $query = [
            'clientAccnum'      => $account,
            'clientSubacc'      => $subaccount,
            'formName'          => $flexFormId,
            'initialPrice'      => $initialPrice,
            'initialPeriod'     => $initialPeriod,
            'recurringPrice'    => $recurringPrice,
            'recurringPeriod'   => $recurringPeriod,
            'numRebills'        => $numRebills,
            'currencyCode'      => $currencyCode,
            'formDigest'        => $this->buildRecurringDigest($initialPrice, $initialPeriod, $recurringPrice, $recurringPeriod, $numRebills, $currencyCode, $saltKey),
            'customer_fname'    => substr($customerName, 0, 50),
            'customer_email'    => substr($customerEmail, 0, 100),
            'custom1'           => 'subscription',
            'custom2'           => $customPayload,
            'type'              => 'subscription',
            'user'              => isset($metadata['subscriber_id']) ? (int) $metadata['subscriber_id'] : null,
            'creator'           => isset($metadata['creator_id']) ? (int) $metadata['creator_id'] : null,
            'planInterval'      => $metadata['plan_type'] ?? '',
            'interval'          => $intervalDays,
            'amountFixed'       => $initialPrice,
            'priceOriginal'     => $initialPrice,
            'taxes'             => isset($metadata['taxes']) ? $metadata['taxes'] : 0,
            'returnURL'         => $returnUrl,
            'cancelURL'         => $cancelUrl,
        ];

        $redirectUrl = $baseUrl . '/' . rawurlencode($flexFormId) . '?' . http_build_query($query);

        return [
            'status'        => 'success',
            'redirect_url'  => $redirectUrl,
            'paymentOption' => 'ccbill'
        ];
    }

    /**
     * Compute CCBill digest for non-recurring purchase.
     */
    protected function buildSinglePurchaseDigest(string $initialPrice, string $initialPeriod, string $currencyCode, string $salt): string
    {
        return md5($initialPrice . $initialPeriod . $currencyCode . $salt);
    }

    protected function buildRecurringDigest(string $initialPrice, string $initialPeriod, string $recurringPrice, string $recurringPeriod, string $numRebills, string $currencyCode, string $salt): string
    {
        return md5($initialPrice . $initialPeriod . $recurringPrice . $recurringPeriod . $numRebills . $currencyCode . $salt);
    }

    protected function mapCurrencyCode(string $currency): string
    {
        if (isset($this->currencyMap[$currency])) {
            return $this->currencyMap[$currency];
        }
        return '840';
    }

    protected function resolveAmount(array $request, string $currency): float
    {
        $amounts = $request['amounts'] ?? [];
        if (is_array($amounts) && isset($amounts[$currency])) {
            return (float) $amounts[$currency];
        }
        if (is_array($amounts) && !empty($amounts)) {
            return (float) reset($amounts);
        }
        if (isset($request['amount'])) {
            return (float) $request['amount'];
        }
        throw new Exception('Amount missing for CCBill request', 1);
    }

    protected function buildAbsoluteUrl(string $path, array $query = []): string
    {
        $base = getAppUrl($path);
        if (!empty($query)) {
            $base .= (strpos($base, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $base;
    }

    /**
     * Perform an HTTP POST request using cURL if available.
     *
     * @param string $endpoint
     * @param array $payload
     * @return string
     * @throws Exception
     */
    protected function performHttpPost(string $endpoint, array $payload): string
    {
        $postFields = http_build_query($payload, '', '&');
        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new Exception('CCBill cancel request failed: ' . $err, 1);
            }
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($statusCode >= 400) {
                throw new Exception('CCBill cancel request returned HTTP ' . $statusCode, 1);
            }
            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                           . "Content-Length: " . strlen($postFields) . "\r\n",
                'content' => $postFields,
                'timeout' => 30,
            ],
        ]);
        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            $error = error_get_last();
            throw new Exception('CCBill cancel request failed: ' . ($error['message'] ?? 'unknown error'), 1);
        }
        return (string) $response;
    }

    /**
     * Parse key=value response body.
     *
     * @param string $body
     * @return array
     */
    protected function parseResponseBody(string $body): array
    {
        $parsed = [];
        foreach (preg_split('/[\r\n]+/', trim($body)) as $line) {
            if ($line === '') {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $parsed[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $parsed;
    }
}
