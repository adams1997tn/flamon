<?php
/**
 * Konnect Network webhook endpoint.
 *
 * Konnect's "silent webhook" hits this URL with `?payment_ref=xxx`
 * (GET or POST). To prevent spoofing we ALWAYS re-fetch the payment
 * from Konnect's API before updating the database. An optional
 * shared secret can also be enforced via admin settings.
 *
 * Lifecycle:
 *   pending  -> nothing (i_user_payments stays 'pending')
 *   completed-> mark i_user_payments.payment_status = 'ok' (idempotent)
 *   failed   -> mark i_user_payments.payment_status = 'failed'
 *   expired  -> mark i_user_payments.payment_status = 'failed'
 *
 * Idempotency: based on (order_key, payment_option='konnect'); we never
 * downgrade an already-paid record.
 */

require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment/KonnectService.php';

if (isset($pdo) && $pdo instanceof PDO) {
    DB::init($pdo);
}

/* --------- helpers --------- */

function konnect_webhook_respond(int $status, string $message = 'OK'): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function konnect_webhook_log(string $message, array $context = []): void
{
    $logDir = __DIR__ . '/includes/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    @file_put_contents($logDir . '/konnect_webhook.log', $line . PHP_EOL, FILE_APPEND);
}

function konnect_webhook_param(string $key, $default = null)
{
    if (isset($_GET[$key]))  return $_GET[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    static $jsonBody = null;
    if ($jsonBody === null) {
        $raw = (string) file_get_contents('php://input');
        $jsonBody = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    }
    return $jsonBody[$key] ?? $default;
}

/* --------- main --------- */

try {
    $service = new KonnectService();
} catch (Throwable $e) {
    konnect_webhook_log('Service init failed', ['err' => $e->getMessage()]);
    konnect_webhook_respond(500, 'Service unavailable');
}

if (!$service->isReady()) {
    konnect_webhook_log('Konnect disabled or misconfigured');
    konnect_webhook_respond(400, 'Konnect disabled');
}

KonnectService::ensureUserPaymentsColumns();

$paymentRef = (string) konnect_webhook_param('payment_ref', '');
if ($paymentRef === '') {
    $paymentRef = (string) konnect_webhook_param('paymentRef', '');
}
if ($paymentRef === '' || !preg_match('/^[A-Za-z0-9_-]{6,128}$/', $paymentRef)) {
    konnect_webhook_log('Missing/invalid payment_ref', ['method' => $_SERVER['REQUEST_METHOD'] ?? '']);
    konnect_webhook_respond(400, 'Invalid payment_ref');
}

// Optional shared-secret enforcement.
$providedSecret = (string) konnect_webhook_param('secret', '');
if ($providedSecret === '') {
    // also look for X-Konnect-Secret header
    $hdr = $_SERVER['HTTP_X_KONNECT_SECRET'] ?? '';
    $providedSecret = is_string($hdr) ? $hdr : '';
}
if (!$service->validateSharedSecret($providedSecret)) {
    konnect_webhook_log('Rejected: bad shared secret', ['ref' => $paymentRef]);
    konnect_webhook_respond(403, 'Forbidden');
}

// Re-verify payment with Konnect API.
try {
    $payment = $service->verifyPayment($paymentRef);
} catch (Throwable $e) {
    konnect_webhook_log('verifyPayment failed', ['ref' => $paymentRef, 'err' => $e->getMessage()]);
    konnect_webhook_respond(502, 'Verification failed');
}

$status  = strtolower($payment['status']);
$orderId = $payment['orderId']
    ?? (string) konnect_webhook_param('order_id', '');
if ($orderId === '') {
    konnect_webhook_log('Missing orderId from Konnect payload', ['ref' => $paymentRef, 'raw' => $payment['raw']]);
    konnect_webhook_respond(400, 'Missing orderId');
}

// Look up the local i_user_payments record by order_key.
$row = DB::one(
    "SELECT i_user_payments_id, payment_status FROM i_user_payments WHERE order_key = ? AND payment_option = ? LIMIT 1",
    [$orderId, 'konnect']
);

if (!$row) {
    konnect_webhook_log('Order not found locally', ['order' => $orderId, 'ref' => $paymentRef]);
    konnect_webhook_respond(404, 'Order not found');
}

$currentStatus = (string) $row['payment_status'];

// Idempotency — don't reprocess a finalised order.
if ($currentStatus === 'ok') {
    konnect_webhook_log('Already ok – ignoring', ['order' => $orderId, 'ref' => $paymentRef]);
    konnect_webhook_respond(200, 'OK already');
}

if ($status === 'completed') {
    DB::exec(
        "UPDATE i_user_payments
            SET payment_status = 'ok', konnect_payment_ref = ?, updated_at = NOW()
          WHERE i_user_payments_id = ?
            AND payment_status <> 'ok'",
        [$paymentRef, (int) $row['i_user_payments_id']]
    );
    konnect_webhook_log('Marked OK', ['order' => $orderId, 'ref' => $paymentRef]);
    konnect_webhook_respond(200, 'OK');
}

if (in_array($status, ['failed', 'expired', 'canceled', 'cancelled'], true)) {
    DB::exec(
        "UPDATE i_user_payments
            SET payment_status = 'failed', konnect_payment_ref = ?, updated_at = NOW()
          WHERE i_user_payments_id = ?
            AND payment_status NOT IN ('ok','failed')",
        [$paymentRef, (int) $row['i_user_payments_id']]
    );
    konnect_webhook_log('Marked FAILED', ['order' => $orderId, 'ref' => $paymentRef, 'status' => $status]);
    konnect_webhook_respond(200, 'Acknowledged');
}

// pending / unknown -> ack but no DB mutation.
konnect_webhook_log('Pending status', ['order' => $orderId, 'ref' => $paymentRef, 'status' => $status]);
konnect_webhook_respond(200, 'Pending');
