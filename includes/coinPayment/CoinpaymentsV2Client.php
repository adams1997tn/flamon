<?php

/**
 * Lightweight client for CoinPayments' newer API (v2/REST style).
 * Uses HMAC (sha256) signing with client secret on the raw JSON body.
 */
class CoinpaymentsV2Client
{
    public const DEFAULT_BASE_URL = 'https://api.coinpayments.net';

    /** @var string */
    private $clientId;

    /** @var string */
    private $clientSecret;

    /** @var string|null */
    private $webhookSecret;

    /** @var string */
    private $baseUrl;

    /**
     * @param string      $clientId
     * @param string      $clientSecret
     * @param string|null $webhookSecret
     * @param string|null $baseUrl
     */
    public function __construct($clientId, $clientSecret, $webhookSecret = null, $baseUrl = null)
    {
        $this->clientId = (string) $clientId;
        $this->clientSecret = (string) $clientSecret;
        $this->webhookSecret = $webhookSecret !== null ? (string) $webhookSecret : null;
        $this->baseUrl = $baseUrl ? rtrim((string) $baseUrl, '/') : self::DEFAULT_BASE_URL;
    }

    /**
     * Creates a hosted checkout and returns checkout url + transaction id.
     *
     * @param array $payload
     *
     * @return array{checkout_url: string|null, transaction_id: string|null, raw: array}
     */
    public function createCheckout(array $payload)
    {
        $endpoint = $this->baseUrl . '/v2/checkout';
        $response = $this->sendJsonRequest($endpoint, $payload);
        $result = [];

        if (isset($response['result']) && is_array($response['result'])) {
            $result = $response['result'];
        } elseif (isset($response['data']) && is_array($response['data'])) {
            $result = $response['data'];
        } elseif (is_array($response)) {
            $result = $response;
        }

        return [
            'checkout_url' => $result['checkout_url'] ?? ($result['url'] ?? null),
            'transaction_id' => $result['id'] ?? ($result['txn_id'] ?? null),
            'raw' => $response
        ];
    }

    /**
     * Exposes webhook secret for verifiers.
     *
     * @return string|null
     */
    public function getWebhookSecret()
    {
        return $this->webhookSecret;
    }

    /**
     * @param string $url
     * @param array  $payload
     *
     * @return array
     */
    private function sendJsonRequest($url, array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Failed to encode payload');
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-CoinPayments-Client' => $this->clientId,
            'X-CoinPayments-Signature' => $this->sign($body)
        ];

        $response = $this->executeCurl($url, $body, $headers);
        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Unexpected CoinPayments response');
        }

        return $decoded;
    }

    /**
     * @param string $url
     * @param string $body
     * @param array  $headers
     *
     * @return string
     */
    private function executeCurl($url, $body, array $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        $response = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errStr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $errNo !== 0) {
            throw new RuntimeException('CoinPayments connection failed: ' . $errStr);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('CoinPayments API error (HTTP ' . $httpCode . ')');
        }

        return $response;
    }

    /**
     * @param string $body
     *
     * @return string
     */
    private function sign($body)
    {
        return hash_hmac('sha256', $body, $this->clientSecret);
    }
}
