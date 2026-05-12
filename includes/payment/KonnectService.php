<?php
/**
 * KonnectService — Standalone Konnect Network gateway client.
 *
 * Reads its configuration directly from the i_payment_methods table so it
 * stays decoupled from the framework's internal configItem() pipeline
 * (the ccbill / yookassa_webhook style). This makes the integration
 * portable and easy to use from webhooks, init endpoints and admin code.
 *
 * Konnect API reference: https://docs.konnect.network
 *   POST /api/v2/payments/init-payment   (header: x-api-key)
 *   GET  /api/v2/payments/{paymentRef}   (verify status)
 *
 * Amounts are expressed in millimes (1 TND = 1000 millimes).
 */

if (!class_exists('KonnectService', false)) {

class KonnectService
{
    public const SANDBOX_BASE = 'https://api.preprod.konnect.network/api/v2';
    public const LIVE_BASE    = 'https://api.konnect.network/api/v2';

    /** @var array<string,mixed> */
    private array $cfg = [];

    public function __construct(?array $configRow = null)
    {
        if ($configRow === null) {
            $configRow = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1");
        }
        $configRow = is_array($configRow) ? $configRow : [];

        $isLive = ((string)($configRow['konnect_payment_mode'] ?? '0')) === '1';

        $this->cfg = [
            'enabled'        => ((string)($configRow['konnect_active_pasive'] ?? '0')) === '1',
            'live'           => $isLive,
            'api_key'        => $isLive
                ? (string)($configRow['konnect_live_api_key'] ?? '')
                : (string)($configRow['konnect_test_api_key'] ?? ''),
            'wallet_id'      => $isLive
                ? (string)($configRow['konnect_live_wallet_id'] ?? '')
                : (string)($configRow['konnect_test_wallet_id'] ?? ''),
            'webhook_secret' => (string)($configRow['konnect_webhook_secret'] ?? ''),
            'currency'       => strtoupper((string)($configRow['konnect_currency'] ?? 'TND')),
            'beta'           => ((string)($configRow['konnect_beta'] ?? '0')) === '1',
            'base'           => $isLive ? self::LIVE_BASE : self::SANDBOX_BASE,
        ];
    }

    public function isReady(): bool
    {
        return $this->cfg['enabled']
            && $this->cfg['api_key'] !== ''
            && $this->cfg['wallet_id'] !== '';
    }

    /**
     * Idempotently ensure auxiliary i_user_payments columns we need.
     * Safe to call on every request — only runs ALTER once per process.
     */
    public static function ensureUserPaymentsColumns(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $cols = [
                'konnect_payment_ref' => "VARCHAR(128) NULL",
                'updated_at'          => "DATETIME NULL DEFAULT NULL",
            ];
            foreach ($cols as $col => $def) {
                $exists = DB::col(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'i_user_payments'
                        AND COLUMN_NAME = ?",
                    [$col]
                );
                if (!$exists) {
                    DB::exec("ALTER TABLE i_user_payments ADD COLUMN {$col} {$def}");
                }
            }
        } catch (\Throwable $e) {
            // ignore — admins can manually add the column if needed
        }
    }

    public function getConfig(): array
    {
        $safe = $this->cfg;
        if (isset($safe['api_key']))        $safe['api_key']        = $safe['api_key']        !== '' ? '***' : '';
        if (isset($safe['webhook_secret'])) $safe['webhook_secret'] = $safe['webhook_secret'] !== '' ? '***' : '';
        return $safe;
    }

    public function getCurrency(): string
    {
        return $this->cfg['currency'];
    }

    public function getWebhookSecret(): string
    {
        return $this->cfg['webhook_secret'];
    }

    /**
     * Convert a decimal amount (e.g. 12.345 TND) to millimes.
     */
    public static function toMillimes(float $amount): int
    {
        return (int) round($amount * 1000);
    }

    /**
     * Initialise a Konnect payment.
     *
     * @param array $params {
     *   amount       float   required (decimal, in $currency)
     *   orderId      string  required
     *   description  string  optional
     *   firstName    string  optional
     *   lastName     string  optional
     *   email        string  optional
     *   phoneNumber  string  optional
     *   webhook      string  optional (defaults to current host)
     *   successUrl   string  optional
     *   failUrl      string  optional
     *   lifespan     int     optional (minutes, default 30)
     * }
     * @return array{payUrl:string,paymentRef:string,raw:array}
     * @throws Exception
     */
    public function createPayment(array $params): array
    {
        if (!$this->isReady()) {
            throw new \Exception('Konnect gateway is not configured.');
        }

        $amount = (float)($params['amount'] ?? 0);
        if ($amount <= 0) {
            throw new \Exception('Konnect payment amount must be > 0.');
        }
        $orderId = (string)($params['orderId'] ?? '');
        if ($orderId === '') {
            throw new \Exception('Konnect payment requires an orderId.');
        }

        $description = trim((string)($params['description'] ?? ('Order #' . $orderId)));
        if (mb_strlen($description) > 250) {
            $description = mb_substr($description, 0, 247) . '...';
        }

        $body = [
            'receiverWalletId'        => $this->cfg['wallet_id'],
            'token'                   => $this->cfg['currency'],
            'amount'                  => self::toMillimes($amount),
            'type'                    => 'immediate',
            'description'             => $description,
            'lifespan'                => (int)($params['lifespan'] ?? 30),
            'checkoutForm'            => true,
            'addPaymentFeesToAmount'  => false,
            'silentWebhook'           => true,
            'theme'                   => 'light',
            'orderId'                 => $orderId,
        ];

        foreach (['firstName','lastName','email','phoneNumber'] as $k) {
            if (!empty($params[$k])) {
                $body[$k] = (string)$params[$k];
            }
        }

        if (!empty($params['webhook']))    { $body['webhook']    = (string)$params['webhook']; }
        if (!empty($params['successUrl'])) { $body['successUrl'] = (string)$params['successUrl']; }
        if (!empty($params['failUrl']))    { $body['failUrl']    = (string)$params['failUrl']; }

        $resp = $this->httpRequest('POST', '/payments/init-payment', $body);
        $data = $resp['json'] ?? [];

        if ($resp['status'] < 200 || $resp['status'] >= 300 || empty($data['payUrl']) || empty($data['paymentRef'])) {
            $msg = is_array($data) ? json_encode($data) : (string)$resp['raw'];
            throw new \Exception('Konnect init-payment failed (' . $resp['status'] . '): ' . $msg);
        }

        return [
            'payUrl'     => (string)$data['payUrl'],
            'paymentRef' => (string)$data['paymentRef'],
            'raw'        => $data,
        ];
    }

    /**
     * Re-fetch payment from Konnect — used by webhook to authenticate
     * status updates instead of trusting webhook payload.
     *
     * @return array{status:string, amount:int, reachedAmount:int, raw:array}
     * @throws Exception
     */
    public function verifyPayment(string $paymentRef): array
    {
        $paymentRef = trim($paymentRef);
        if ($paymentRef === '' || !preg_match('/^[A-Za-z0-9_-]{6,128}$/', $paymentRef)) {
            throw new \Exception('Konnect payment reference is invalid.');
        }

        $resp = $this->httpRequest('GET', '/payments/' . rawurlencode($paymentRef));
        $data = $resp['json'] ?? [];
        if ($resp['status'] < 200 || $resp['status'] >= 300 || empty($data['payment'])) {
            throw new \Exception('Konnect verifyPayment failed (' . $resp['status'] . ').');
        }
        $p = $data['payment'];
        return [
            'status'        => (string)($p['status'] ?? 'unknown'),
            'amount'        => (int)($p['amount'] ?? 0),
            'reachedAmount' => (int)($p['reachedAmount'] ?? 0),
            'token'         => (string)($p['token'] ?? $this->cfg['currency']),
            'orderId'       => (string)($p['orderId'] ?? ''),
            'raw'           => $p,
        ];
    }

    /**
     * Validate webhook authenticity. Konnect's silent webhook is a GET to
     * <webhook_url>?payment_ref=xxx with no signature; some setups also
     * accept a shared-secret query param. We:
     *   1) optionally enforce a shared secret if admin configured one;
     *   2) always re-verify status by calling Konnect's API.
     */
    public function validateSharedSecret(?string $providedSecret): bool
    {
        $expected = $this->cfg['webhook_secret'];
        if ($expected === '') {
            return true; // not configured -> signature check skipped
        }
        if (!is_string($providedSecret) || $providedSecret === '') {
            return false;
        }
        return hash_equals($expected, $providedSecret);
    }

    // ---------------------------------------------------------------- HTTP

    private function httpRequest(string $method, string $path, ?array $body = null): array
    {
        $url = $this->cfg['base'] . $path;
        $ch  = curl_init();
        $headers = [
            'x-api-key: ' . $this->cfg['api_key'],
            'Accept: application/json',
        ];
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $err    = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new \Exception('Konnect HTTP error: ' . $err);
        }

        $json = is_string($raw) ? json_decode($raw, true) : null;
        return [
            'status' => $status,
            'raw'    => is_string($raw) ? $raw : '',
            'json'   => is_array($json) ? $json : [],
        ];
    }
}

} // class_exists guard
