<?php

namespace App\Service;

use Exception;
use Unirest;

class FlutterwaveService
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
        $this->configItem = getArrayItem($this->configData, 'payments.gateway_configuration.flutterwave', []);
    }

    protected function isTestMode()
    {
        $testMode = $this->configItem['testMode'] ?? false;
        return ($testMode === true || $testMode === 'true' || (int) $testMode === 1);
    }

    protected function getSecretKey()
    {
        return $this->isTestMode()
            ? ($this->configItem['testSecretKey'] ?? '')
            : ($this->configItem['liveSecretKey'] ?? '');
    }

    protected function getVerificationUrlTemplate()
    {
        if ($this->isTestMode()) {
            return $this->configItem['sandboxVerifyUrl'] ?? 'https://api.flutterwave.com/v3/transactions/%s/verify';
        }

        return $this->configItem['productionVerifyUrl'] ?? 'https://api.flutterwave.com/v3/transactions/%s/verify';
    }

    protected function getCancelUrlTemplate()
    {
        return $this->configItem['subscriptionCancelUrl'] ?? 'https://api.flutterwave.com/v3/subscriptions/%s/cancel';
    }

    public function processFlutterwaveRequest($request)
    {
        try {
            $transactionId = $request['flutterwaveTransactionId'] ?? null;
            $txRef = $request['flutterwaveTxRef'] ?? null;
            $expectedAmount = isset($request['flutterwaveAmount']) ? (float) $request['flutterwaveAmount'] : null;

            if (empty($transactionId) || empty($txRef)) {
                throw new Exception('Invalid Flutterwave transaction data.');
            }

            $verifyUrl = sprintf($this->getVerificationUrlTemplate(), $transactionId);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getSecretKey(),
            ];

            $response = Unirest\Request::get($verifyUrl, $headers);

            if (!isset($response->body->status) || $response->body->status !== 'success') {
                $message = $response->body->message ?? 'Unable to verify Flutterwave payment.';
                throw new Exception($message);
            }

            $data = $response->body->data ?? null;
            if (!$data || $data->status !== 'successful') {
                throw new Exception('Flutterwave payment not successful.');
            }

            if ($expectedAmount !== null && (float) $data->amount !== $expectedAmount) {
                throw new Exception('Flutterwave amount mismatch.');
            }

            if ($data->tx_ref !== $txRef) {
                throw new Exception('Flutterwave reference mismatch.');
            }

            return [
                'status' => true,
                'data' => (array) $data,
                'raw' => json_decode(json_encode($response->body), true),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }

    public function cancelSubscription($subscriptionId)
    {
        try {
            $subscriptionId = trim((string) $subscriptionId);
            if ($subscriptionId === '' || strpos($subscriptionId, 'flw_') === 0) {
                return ['status' => true, 'skipped' => true];
            }

            $cancelUrl = sprintf($this->getCancelUrlTemplate(), $subscriptionId);
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getSecretKey(),
            ];
            $response = Unirest\Request::post($cancelUrl, $headers);
            if (!isset($response->body->status) || $response->body->status !== 'success') {
                $message = $response->body->message ?? 'Unable to cancel Flutterwave subscription.';
                throw new Exception($message);
            }

            return [
                'status' => true,
                'data' => (array) ($response->body->data ?? []),
                'raw' => json_decode(json_encode($response->body), true),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }
}
