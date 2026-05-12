<?php
/**
 * Konnect Network — initiate hosted-page payment.
 *
 * Auth required. Called from the front-end when the user picks "Konnect"
 * as the payment option. Records a pending row in i_user_payments,
 * creates a Konnect payment session, and returns the hosted payUrl.
 *
 * Request (POST):
 *   amount        decimal  required
 *   currency      string   optional (defaults to gateway currency)
 *   payment_type  string   optional, defaults to 'point'  (point|product|subscription|tip|...)
 *   payed_iuid_fk int      optional (for product/sub payments)
 *   description   string   optional
 *
 * Response (JSON):
 *   { status: 'ok',     payUrl: '...', orderKey: '...', paymentRef: '...' }
 *   { status: 'error',  message: '...' }
 */

include_once __DIR__ . '/../includes/inc.php';
require_once __DIR__ . '/../includes/payment/KonnectService.php';

header('Content-Type: application/json; charset=utf-8');

function konnect_init_fail(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if (empty($logedIn) || (int)$logedIn !== 1 || empty($userID) || (int)$userID <= 0) {
    konnect_init_fail($LANG['noway_desc'] ?? 'Unauthorized', 401);
}

// CSRF — accept the framework's csrf token if present.
if (isset($_POST['csrf_token']) && function_exists('iN_VerifyCsrfToken')) {
    if (!iN_VerifyCsrfToken((string)$_POST['csrf_token'])) {
        konnect_init_fail('CSRF token invalid', 403);
    }
}

$amount = isset($_POST['amount']) ? (float) str_replace(',', '.', (string)$_POST['amount']) : 0.0;
if ($amount <= 0) {
    konnect_init_fail($LANG['something_wrong'] ?? 'Invalid amount');
}

$paymentType = isset($_POST['payment_type']) ? preg_replace('/[^a-z_]/i', '', (string)$_POST['payment_type']) : 'point';
if ($paymentType === '') { $paymentType = 'point'; }

$payedIuid   = isset($_POST['payed_iuid_fk']) ? (int)$_POST['payed_iuid_fk'] : (int)$userID;
$description = isset($_POST['description']) ? mb_substr(trim((string)$_POST['description']), 0, 200) : '';

try {
    $service = new KonnectService();
} catch (Throwable $e) {
    konnect_init_fail('Gateway initialisation failed', 500);
}

if (!$service->isReady()) {
    konnect_init_fail($LANG['konnect_status_not'] ?? 'Konnect is not enabled', 400);
}

KonnectService::ensureUserPaymentsColumns();

$currency = strtoupper((string)($_POST['currency'] ?? $service->getCurrency()));
$orderKey = bin2hex(random_bytes(12));
$now      = time();

// User-facing return URLs.
$baseUrl    = rtrim((string)($baseurl ?? ''), '/');
$successUrl = $baseUrl . '/konnect_return.php?order_key=' . rawurlencode($orderKey) . '&result=success';
$failUrl    = $baseUrl . '/konnect_return.php?order_key=' . rawurlencode($orderKey) . '&result=fail';
$webhookUrl = $baseUrl . '/konnect_webhook.php';
$secret     = $service->getWebhookSecret();
if ($secret !== '') {
    $webhookUrl .= '?secret=' . rawurlencode($secret);
}

// Pull payer info for a richer Konnect prefill (best-effort).
$firstName = ''; $lastName = ''; $email = ''; $phone = '';
try {
    $u = DB::one("SELECT name, email, phone FROM i_users WHERE iuid = ? LIMIT 1", [(int)$userID]);
    if ($u) {
        $parts = preg_split('/\s+/', trim((string)($u['name'] ?? '')), 2);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? $firstName;
        $email     = (string)($u['email'] ?? '');
        $phone     = (string)($u['phone'] ?? '');
    }
} catch (Throwable $e) { /* non-fatal */ }

// Insert the pending payment row first.
try {
    DB::exec(
        "INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, currency)
         VALUES (?,?,?,?,?,?,?,?,?)",
        [
            (int)$userID,
            (int)$payedIuid,
            (string)$orderKey,
            (string)$paymentType,
            'konnect',
            (int)$now,
            'pending',
            (string)number_format($amount, 3, '.', ''),
            (string)$currency,
        ]
    );
} catch (Throwable $e) {
    // payment_option ENUM may not yet include 'konnect' — try to widen it.
    if (stripos($e->getMessage(), 'payment_option') !== false || stripos($e->getMessage(), 'truncated') !== false) {
        try {
            DB::exec("ALTER TABLE i_user_payments MODIFY COLUMN payment_option VARCHAR(32) NOT NULL");
            DB::exec(
                "INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, currency)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    (int)$userID, (int)$payedIuid, (string)$orderKey, (string)$paymentType, 'konnect',
                    (int)$now, 'pending', (string)number_format($amount, 3, '.', ''), (string)$currency,
                ]
            );
        } catch (Throwable $e2) {
            konnect_init_fail('DB error: ' . $e2->getMessage(), 500);
        }
    } else {
        konnect_init_fail('DB error: ' . $e->getMessage(), 500);
    }
}

// Create the Konnect session.
try {
    $payment = $service->createPayment([
        'amount'      => $amount,
        'orderId'     => $orderKey,
        'description' => $description !== '' ? $description : ('Order ' . $orderKey),
        'firstName'   => $firstName,
        'lastName'    => $lastName,
        'email'       => $email,
        'phoneNumber' => $phone,
        'webhook'     => $webhookUrl,
        'successUrl'  => $successUrl,
        'failUrl'     => $failUrl,
        'lifespan'    => 30,
    ]);
} catch (Throwable $e) {
    // Rollback our pending row so it isn't left orphaned.
    try {
        DB::exec(
            "UPDATE i_user_payments SET payment_status = 'failed' WHERE order_key = ? AND payment_option = 'konnect' AND payment_status = 'pending'",
            [(string)$orderKey]
        );
    } catch (Throwable $ignored) {}
    konnect_init_fail('Konnect error: ' . $e->getMessage(), 502);
}

// Save Konnect's payment ref against the local row.
try {
    DB::exec(
        "UPDATE i_user_payments SET konnect_payment_ref = ? WHERE order_key = ? AND payment_option = 'konnect'",
        [(string)$payment['paymentRef'], (string)$orderKey]
    );
} catch (Throwable $e) { /* column auto-ensured earlier; ignore */ }

echo json_encode([
    'status'     => 'ok',
    'payUrl'     => $payment['payUrl'],
    'paymentRef' => $payment['paymentRef'],
    'orderKey'   => $orderKey,
]);
