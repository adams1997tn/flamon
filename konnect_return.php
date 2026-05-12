<?php
/**
 * Konnect Network — browser return URL.
 *
 * The hosted Konnect page redirects the customer here after completion
 * (success or fail). We never trust the query string — we look up the
 * local payment by order_key and re-verify status with Konnect's API.
 * The webhook is the authoritative path for marking 'ok'/'failed', but
 * we ALSO try to settle the order here so the UX is fast even if the
 * webhook is delayed.
 */

require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment/KonnectService.php';

if (isset($pdo) && $pdo instanceof PDO) {
    DB::init($pdo);
}

$orderKey   = isset($_GET['order_key']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['order_key']) : '';
$resultHint = isset($_GET['result']) && in_array($_GET['result'], ['success','fail'], true) ? $_GET['result'] : '';
$paymentRef = isset($_GET['payment_ref']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string)$_GET['payment_ref']) : '';

$base = '';
try {
    $row = DB::one("SELECT site_url FROM i_admin WHERE i_admin_id = 1 LIMIT 1");
    if ($row && !empty($row['site_url'])) {
        $base = rtrim((string)$row['site_url'], '/');
    }
} catch (Throwable $e) { /* non-fatal */ }

if ($orderKey === '') {
    header('Location: ' . ($base !== '' ? $base : '/'));
    exit;
}

try {
    $service = new KonnectService();
    if ($service->isReady() && $paymentRef !== '') {
        $payment = $service->verifyPayment($paymentRef);
        $status  = strtolower($payment['status']);
        $local = DB::one(
            "SELECT i_user_payments_id, payment_status FROM i_user_payments WHERE order_key = ? AND payment_option = 'konnect' LIMIT 1",
            [$orderKey]
        );
        if ($local && (string)$local['payment_status'] !== 'ok') {
            if ($status === 'completed') {
                DB::exec(
                    "UPDATE i_user_payments SET payment_status = 'ok', konnect_payment_ref = ? WHERE i_user_payments_id = ? AND payment_status <> 'ok'",
                    [$paymentRef, (int)$local['i_user_payments_id']]
                );
            } elseif (in_array($status, ['failed','expired','canceled','cancelled'], true)) {
                DB::exec(
                    "UPDATE i_user_payments SET payment_status = 'failed', konnect_payment_ref = ? WHERE i_user_payments_id = ? AND payment_status NOT IN ('ok','failed')",
                    [$paymentRef, (int)$local['i_user_payments_id']]
                );
            }
        }
    }
} catch (Throwable $e) { /* non-fatal — webhook is authoritative */ }

// Final user redirect — defer to existing payment-response.php if present.
$paymentResponse = $base . '/payment-response.php?paymentOption=konnect&orderId=' . rawurlencode($orderKey);
if ($resultHint !== '') {
    $paymentResponse .= '&result=' . $resultHint;
}
header('Location: ' . $paymentResponse);
exit;
