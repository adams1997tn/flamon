<?php
// EPOCH FlexPost webhook endpoint
// - POST only
// - IP allowlist + pass-through nonce/signature verification
// - Idempotent event handling
// - Subscription and non-subscription payment reconciliation

require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($pdo) && $pdo instanceof PDO) {
    DB::init($pdo);
}

$iN = new iN_UPDATES($db);
$inc = $iN->iN_Configurations();
$adminFee = isset($inc['fee']) ? (float)$inc['fee'] : 0.0;
$defaultCurrency = isset($inc['default_currency']) && $inc['default_currency'] !== ''
    ? (string)$inc['default_currency']
    : 'USD';

function epoch_webhook_respond(int $status, string $message = 'OK'): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function epoch_webhook_log(string $message, array $context = []): void
{
    $logDir = __DIR__ . '/includes/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $line = date('c') . ' ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    @file_put_contents($logDir . '/epoch_webhook.log', $line, FILE_APPEND);
}

function epoch_webhook_payload_value(array $payload, array $keys): string
{
    foreach ($keys as $key) {
        if (isset($payload[$key]) && $payload[$key] !== null && $payload[$key] !== '') {
            return trim((string)$payload[$key]);
        }
    }
    return '';
}

function epoch_webhook_get_client_ip(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        if (!empty($forwarded[0])) {
            return trim($forwarded[0]);
        }
    }
    return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
}

function epoch_webhook_ip_in_cidr(string $ip, string $cidr): bool
{
    $ip = trim($ip);
    $cidr = trim($cidr);
    if ($ip === '' || $cidr === '') {
        return false;
    }
    if (strpos($cidr, '/') === false) {
        return strcasecmp($ip, $cidr) === 0;
    }

    [$subnet, $maskBits] = array_pad(explode('/', $cidr, 2), 2, '');
    if (!is_numeric($maskBits)) {
        return false;
    }

    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    $maskBits = (int)$maskBits;
    if ($ipLong === false || $subnetLong === false || $maskBits < 0 || $maskBits > 32) {
        return false;
    }

    $mask = $maskBits === 0 ? 0 : (~0 << (32 - $maskBits));
    return (($ipLong & $mask) === ($subnetLong & $mask));
}

function epoch_webhook_allowlist_match(string $ip, string $allowlistRaw): bool
{
    $allowlistRaw = trim($allowlistRaw);
    if ($allowlistRaw === '') {
        return true;
    }
    $tokens = preg_split('/[\r\n,\s]+/', $allowlistRaw);
    if (!is_array($tokens)) {
        return false;
    }
    foreach ($tokens as $token) {
        $token = trim((string)$token);
        if ($token === '') {
            continue;
        }
        if (epoch_webhook_ip_in_cidr($ip, $token)) {
            return true;
        }
    }
    return false;
}

function epoch_webhook_has_table(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $count = DB::col(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        $cache[$table] = ((int)$count > 0);
    } catch (Throwable $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

function epoch_webhook_has_column(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $count = DB::col(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
        $cache[$key] = ((int)$count > 0);
    } catch (Throwable $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function epoch_webhook_add_interval(string $start, string $interval, int $count): string
{
    $count = $count > 0 ? $count : 1;
    $interval = strtolower($interval);
    $date = new DateTime($start);
    if ($interval === 'week') {
        $date->modify('+' . ($count * 7) . ' days');
    } elseif ($interval === 'year') {
        $date->modify('+' . $count . ' years');
    } else {
        $date->modify('+' . $count . ' months');
    }
    return $date->format('Y-m-d H:i:s');
}

function epoch_webhook_sync_community_membership(array $row, string $status, ?string $start = null, ?string $end = null): void
{
    if (($row['subscription_scope'] ?? '') !== 'community') {
        return;
    }
    $communityId = (int)($row['subscription_ref_id'] ?? 0);
    $userId = (int)($row['iuid_fk'] ?? 0);
    $subscriptionId = (int)($row['subscription_id'] ?? 0);
    if ($communityId <= 0 || $userId <= 0) {
        return;
    }
    $now = date('Y-m-d H:i:s');
    $startedAt = $start ?: $now;
    DB::exec(
        "INSERT INTO community_memberships (community_id, user_id, subscription_id, status, started_at, ended_at, created_at)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE subscription_id = VALUES(subscription_id), status = VALUES(status), started_at = VALUES(started_at), ended_at = VALUES(ended_at)",
        [
            $communityId,
            $userId,
            $subscriptionId > 0 ? $subscriptionId : null,
            $status,
            $startedAt,
            $end !== null ? $end : null,
            $now
        ]
    );
}

function epoch_webhook_map_event(string $eventRaw, string $statusRaw, string $responseRaw): string
{
    $merged = strtolower(trim($eventRaw . ' ' . $statusRaw . ' ' . $responseRaw));
    if (preg_match('/chargeback|charge_back|cbk/', $merged)) {
        return 'chargeback';
    }
    if (preg_match('/refund|credited|credit/', $merged)) {
        return 'refund';
    }
    if (preg_match('/cancel|cancellation|void|terminate/', $merged)) {
        return 'cancel';
    }
    if (preg_match('/expir|ended|lapse/', $merged)) {
        return 'expiration';
    }
    if (preg_match('/deny|denied|declin|fail|rejected|error|cancelled/', $merged)) {
        return 'denial';
    }
    if (preg_match('/rebill|renew|recurr/', $merged)) {
        return 'rebill';
    }
    return 'initial_sale';
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    epoch_webhook_log('Rejected non-POST request', ['method' => $_SERVER['REQUEST_METHOD'] ?? '']);
    epoch_webhook_respond(405, 'Method Not Allowed');
}

$rawBody = (string)file_get_contents('php://input');
$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$payload = [];
if (strpos($contentType, 'application/json') !== false) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
} else {
    $payload = $_POST;
    if (empty($payload) && $rawBody !== '') {
        parse_str($rawBody, $parsed);
        if (is_array($parsed)) {
            $payload = $parsed;
        }
    }
}

if (empty($payload) || !is_array($payload)) {
    epoch_webhook_log('Rejected invalid payload', ['content_type' => $contentType]);
    epoch_webhook_respond(400, 'Invalid payload');
}

$paymentMethodRow = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1 LIMIT 1");
if (!$paymentMethodRow || (int)($paymentMethodRow['epoch_active_pasive'] ?? 0) !== 1) {
    epoch_webhook_log('Rejected because EPOCH gateway is disabled');
    epoch_webhook_respond(400, 'EPOCH disabled');
}

$postbackEnabled = (int)($paymentMethodRow['epoch_postback_enabled'] ?? 1) === 1;
if (!$postbackEnabled) {
    epoch_webhook_log('Rejected because EPOCH postback is disabled');
    epoch_webhook_respond(403, 'Postback disabled');
}

$clientIp = epoch_webhook_get_client_ip();
$allowlist = (string)($paymentMethodRow['epoch_postback_allowlist'] ?? '');
if (!epoch_webhook_allowlist_match($clientIp, $allowlist)) {
    epoch_webhook_log('Rejected by IP allowlist', ['ip' => $clientIp]);
    epoch_webhook_respond(403, 'IP not allowed');
}

$orderKey = epoch_webhook_payload_value($payload, ['x_order_key', 'order_key', 'order_id', 'orderId']);
$payloadUserId = epoch_webhook_payload_value($payload, ['x_user_id', 'user_id', 'userId']);
$payloadPlanId = epoch_webhook_payload_value($payload, ['x_plan_id', 'plan_id', 'planId']);
$payloadEnv = epoch_webhook_payload_value($payload, ['x_env', 'env']);
$payloadNonce = epoch_webhook_payload_value($payload, ['x_nonce', 'nonce']);
$payloadSignature = epoch_webhook_payload_value($payload, ['x_sig', 'signature', 'sig']);
$transactionId = epoch_webhook_payload_value($payload, ['transaction_id', 'transactionId', 'txn_id', 'subscriptionId', 'id']);
$statusRaw = epoch_webhook_payload_value($payload, ['status', 'result', 'response', 'approval_indicator']);
$responseRaw = epoch_webhook_payload_value($payload, ['response', 'responsetext', 'message', 'status']);
$eventRaw = epoch_webhook_payload_value($payload, ['event', 'eventType', 'type', 'transaction_type', 'action', 'reason']);

if ($orderKey === '' || $payloadNonce === '' || $payloadSignature === '') {
    epoch_webhook_log('Rejected missing required verification fields', [
        'order_key_present' => $orderKey !== '' ? 1 : 0,
        'nonce_present' => $payloadNonce !== '' ? 1 : 0,
        'signature_present' => $payloadSignature !== '' ? 1 : 0
    ]);
    epoch_webhook_respond(400, 'Missing required fields');
}

$testMarker = strtoupper((string)$responseRaw);
$rawEncoded = strtoupper($rawBody);
if ($testMarker === 'YGOODTEST' || strpos($rawEncoded, 'YGOODTEST') !== false) {
    epoch_webhook_log('Received YGOODTEST postback; ignored for account actions', [
        'order_key' => $orderKey,
        'transaction_id' => $transactionId
    ]);
    epoch_webhook_respond(200, 'TEST OK');
}

$paymentRow = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
$intentRow = DB::one("SELECT * FROM i_user_subscription_intents WHERE order_key = ? AND payment_option = 'epoch' LIMIT 1", [$orderKey]);
if (!$paymentRow && !$intentRow) {
    epoch_webhook_log('Order not found', ['order_key' => $orderKey, 'transaction_id' => $transactionId]);
    epoch_webhook_respond(404, 'Order not found');
}

$secret = trim((string)($paymentMethodRow['epoch_postback_secret'] ?? ''));
if ($secret === '') {
    $secret = sha1($orderKey . ':' . $payloadNonce);
}
$signaturePayload = implode('|', [$orderKey, $payloadNonce, (string)$payloadUserId, (string)$payloadPlanId, (string)$payloadEnv]);
$expectedSignature = hash_hmac('sha256', $signaturePayload, $secret);
if (!hash_equals(strtolower($expectedSignature), strtolower($payloadSignature))) {
    epoch_webhook_log('Rejected invalid signature', [
        'order_key' => $orderKey,
        'transaction_id' => $transactionId
    ]);
    epoch_webhook_respond(403, 'Invalid signature');
}

if ($paymentRow) {
    $storedNonce = trim((string)($paymentRow['epoch_nonce'] ?? ''));
    $storedSignature = trim((string)($paymentRow['epoch_signature'] ?? ''));
    if ($storedNonce !== '' && !hash_equals($storedNonce, $payloadNonce)) {
        epoch_webhook_log('Rejected nonce mismatch with payment row', ['order_key' => $orderKey]);
        epoch_webhook_respond(403, 'Invalid nonce');
    }
    if ($storedSignature !== '' && !hash_equals(strtolower($storedSignature), strtolower($payloadSignature))) {
        epoch_webhook_log('Rejected signature mismatch with payment row', ['order_key' => $orderKey]);
        epoch_webhook_respond(403, 'Invalid signature');
    }
}

if ($intentRow) {
    $intentNonce = trim((string)($intentRow['epoch_nonce'] ?? ''));
    $intentSignature = trim((string)($intentRow['epoch_signature'] ?? ''));
    if ($intentNonce !== '' && !hash_equals($intentNonce, $payloadNonce)) {
        epoch_webhook_log('Rejected nonce mismatch with subscription intent', ['order_key' => $orderKey]);
        epoch_webhook_respond(403, 'Invalid nonce');
    }
    if ($intentSignature !== '' && !hash_equals(strtolower($intentSignature), strtolower($payloadSignature))) {
        epoch_webhook_log('Rejected signature mismatch with subscription intent', ['order_key' => $orderKey]);
        epoch_webhook_respond(403, 'Invalid signature');
    }
}

if ($paymentRow && $payloadUserId !== '' && (int)$payloadUserId > 0) {
    $payerId = (int)($paymentRow['payer_iuid_fk'] ?? 0);
    if ($payerId > 0 && $payerId !== (int)$payloadUserId) {
        epoch_webhook_log('Rejected user mismatch', ['order_key' => $orderKey]);
        epoch_webhook_respond(403, 'Invalid user');
    }
}

if ($intentRow && $payloadPlanId !== '') {
    $intentPlanId = trim((string)($intentRow['plan_id'] ?? ''));
    if ($intentPlanId !== '' && !hash_equals((string)$intentPlanId, (string)$payloadPlanId)) {
        epoch_webhook_log('Rejected plan mismatch', ['order_key' => $orderKey]);
        epoch_webhook_respond(403, 'Invalid plan');
    }
}

$eventType = epoch_webhook_map_event($eventRaw, $statusRaw, $responseRaw);
$eventHash = hash(
    'sha256',
    implode('|', [
        (string)$orderKey,
        (string)$transactionId,
        (string)$eventType,
        (string)$statusRaw,
        (string)$responseRaw
    ])
);

if (epoch_webhook_has_table('i_epoch_webhook_events')) {
    try {
        DB::exec(
            "INSERT INTO i_epoch_webhook_events (event_hash, order_key, transaction_id, event_type, verification_status, payload, created_at)
             VALUES (?,?,?,?,?,?,?)",
            [
                $eventHash,
                (string)$orderKey,
                (string)$transactionId,
                (string)$eventType,
                'verified',
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int)time()
            ]
        );
    } catch (Throwable $e) {
        $duplicate = stripos($e->getMessage(), 'Duplicate') !== false || stripos($e->getMessage(), '1062') !== false;
        if ($duplicate) {
            epoch_webhook_log('Duplicate event ignored', [
                'order_key' => $orderKey,
                'transaction_id' => $transactionId,
                'event_type' => $eventType
            ]);
            epoch_webhook_respond(200, 'Duplicate');
        }
    }
}

$paymentType = (string)($paymentRow['payment_type'] ?? 'subscription');
$amountFromRow = isset($paymentRow['amount']) ? (float)$paymentRow['amount'] : 0.0;
$currencyFromRow = isset($paymentRow['currency']) && $paymentRow['currency'] !== ''
    ? (string)$paymentRow['currency']
    : $defaultCurrency;

if ($eventType === 'denial' || $eventType === 'cancel' || $eventType === 'expiration' || $eventType === 'refund' || $eventType === 'chargeback') {
    if ($intentRow && ($intentRow['status'] ?? '') === 'pending') {
        DB::exec(
            "UPDATE i_user_subscription_intents SET status = 'declined', updated_at = ? WHERE intent_id = ?",
            [(int)time(), (int)$intentRow['intent_id']]
        );
    }
    if ($paymentRow && ($paymentRow['payment_status'] ?? '') !== 'declined') {
        DB::exec(
            "UPDATE i_user_payments SET payment_status = 'declined' WHERE payment_id = ?",
            [(int)$paymentRow['payment_id']]
        );
    }

    if ($paymentType === 'subscription') {
        $targetSubscriptions = [];
        if ($transactionId !== '') {
            $subByTxn = DB::one("SELECT * FROM i_user_subscriptions WHERE payment_subscription_id = ? LIMIT 1", [$transactionId]);
            if ($subByTxn) {
                $targetSubscriptions[] = $subByTxn;
            }
        }
        if (empty($targetSubscriptions) && $intentRow) {
            $scope = (string)($intentRow['subscription_scope'] ?? 'profile');
            if ($scope === 'community') {
                $targetSubscriptions = DB::all(
                    "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND subscription_scope = 'community' AND iuid_fk = ? AND subscription_ref_id = ? ORDER BY subscription_id DESC LIMIT 1",
                    [(int)$intentRow['iuid_fk'], (int)$intentRow['subscription_ref_id']]
                );
            } elseif ($scope === 'community_plan') {
                $targetSubscriptions = DB::all(
                    "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND subscription_scope = 'community_plan' AND iuid_fk = ? ORDER BY subscription_id DESC LIMIT 1",
                    [(int)$intentRow['iuid_fk']]
                );
            } else {
                $targetSubscriptions = DB::all(
                    "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND subscription_scope = 'profile' AND iuid_fk = ? AND plan_id = ? ORDER BY subscription_id DESC LIMIT 1",
                    [(int)$intentRow['iuid_fk'], (string)$intentRow['plan_id']]
                );
            }
        }

        foreach ($targetSubscriptions as $subscriptionRow) {
            $subscriptionId = (int)($subscriptionRow['subscription_id'] ?? 0);
            if ($subscriptionId <= 0) {
                continue;
            }
            DB::exec(
                "UPDATE i_user_subscriptions SET status = 'declined', finished = '1', in_status = '1' WHERE subscription_id = ?",
                [$subscriptionId]
            );
            if (($subscriptionRow['subscription_scope'] ?? 'profile') === 'profile') {
                DB::exec(
                    "UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = ? AND fr_two = ?",
                    [(int)$subscriptionRow['iuid_fk'], (int)$subscriptionRow['subscribed_iuid_fk']]
                );
            } elseif (($subscriptionRow['subscription_scope'] ?? '') === 'community') {
                epoch_webhook_sync_community_membership($subscriptionRow, 'canceled', null, date('Y-m-d H:i:s'));
            }
        }
    }

    epoch_webhook_log('Applied non-success event', [
        'order_key' => $orderKey,
        'transaction_id' => $transactionId,
        'event_type' => $eventType,
        'payment_type' => $paymentType
    ]);
    epoch_webhook_respond(200, 'OK');
}

if ($paymentType === 'subscription') {
    $now = date('Y-m-d H:i:s');
    $activated = false;

    if ($intentRow && ($intentRow['status'] ?? '') !== 'ok') {
        $scope = (string)($intentRow['subscription_scope'] ?? 'profile');
        $subscriberId = (int)($intentRow['iuid_fk'] ?? 0);
        $subscribedId = (int)($intentRow['subscribed_iuid_fk'] ?? 0);
        $subscriberName = (string)($intentRow['subscriber_name'] ?? '');
        $subscriberEmail = (string)($intentRow['subscriber_email'] ?? '');
        if ($subscriberName === '' || $subscriberEmail === '') {
            $subscriberData = $subscriberId > 0 ? $iN->iN_GetUserDetails($subscriberId) : null;
            if ($subscriberData) {
                if ($subscriberName === '') {
                    $subscriberName = (string)($subscriberData['i_user_fullname'] ?: $subscriberData['i_username']);
                }
                if ($subscriberEmail === '') {
                    $subscriberEmail = (string)$subscriberData['i_user_email'];
                }
            }
        }

        $planAmount = (float)($intentRow['plan_amount'] ?? $amountFromRow);
        $planCurrency = (string)($intentRow['plan_amount_currency'] ?? $currencyFromRow);
        $planInterval = (string)($intentRow['plan_interval'] ?? 'month');
        $planIntervalCount = (int)($intentRow['plan_interval_count'] ?? 1);
        if ($planIntervalCount < 1) {
            $planIntervalCount = 1;
        }
        $currentPeriodEnd = epoch_webhook_add_interval($now, $planInterval, $planIntervalCount);
        $paymentSubscriptionId = $transactionId !== '' ? $transactionId : $orderKey;
        $customerId = $paymentSubscriptionId;

        if ($scope === 'community') {
            $communityId = (int)($intentRow['subscription_ref_id'] ?? 0);
            $alreadyMember = (bool)DB::col(
                "SELECT 1 FROM community_memberships WHERE community_id = ? AND user_id = ? AND status = 'active' LIMIT 1",
                [$communityId, $subscriberId]
            );
            if ($alreadyMember) {
                $activated = true;
            } else {
                $communityData = $iN->iN_GetCommunityById($communityId);
                if ($communityData && (string)($communityData['status'] ?? '') === 'active') {
                    $ownerId = (int)($communityData['owner_user_id'] ?? $subscribedId);
                    $inserted = $iN->iN_InsertCommunitySubscription(
                        $subscriberId,
                        $communityId,
                        $ownerId,
                        $subscriberName,
                        'epoch',
                        $paymentSubscriptionId,
                        $customerId,
                        (string)($intentRow['plan_id'] ?? ('epoch_community_' . $communityId)),
                        $planAmount,
                        0,
                        0,
                        $planCurrency,
                        $planInterval,
                        $planIntervalCount,
                        $subscriberEmail,
                        $now,
                        $now,
                        $currentPeriodEnd,
                        'active'
                    );
                    $activated = (bool)$inserted;
                }
            }
        } elseif ($scope === 'community_plan') {
            $hasPlan = $iN->iN_HasActiveCommunityPlan($subscriberId);
            if ($hasPlan) {
                $activated = true;
            } else {
                $inserted = $iN->iN_InsertCommunityPlanSubscription(
                    $subscriberId,
                    $subscriberName,
                    'epoch',
                    $paymentSubscriptionId,
                    $customerId,
                    (string)($intentRow['plan_id'] ?? 'epoch_community_plan'),
                    $planAmount,
                    $planAmount,
                    $planCurrency,
                    $planInterval,
                    $planIntervalCount,
                    $subscriberEmail,
                    $now,
                    $now,
                    $currentPeriodEnd,
                    'active'
                );
                $activated = (bool)$inserted;
            }
        } else {
            $planId = (int)($intentRow['plan_id'] ?? 0);
            $alreadySubscriber = $iN->iN_CheckUserIsInSubscriber($subscriberId, $subscribedId);
            if ($alreadySubscriber) {
                $activated = true;
            } else {
                $planDetails = $iN->iN_CheckPlanExist($planId, $subscribedId);
                if ($planDetails) {
                    $planType = (string)($planDetails['plan_type'] ?? 'monthly');
                    $periodInterval = 'month';
                    $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
                    if ($planType === 'weekly') {
                        $periodInterval = 'week';
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+7 days'));
                    } elseif ($planType === 'yearly') {
                        $periodInterval = 'year';
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+1 year'));
                    }
                    $inserted = $iN->iN_InsertUserSubscription(
                        $subscriberId,
                        $subscribedId,
                        'epoch',
                        $subscriberName,
                        $paymentSubscriptionId,
                        $customerId,
                        'epoch_' . $planId,
                        $planAmount,
                        0,
                        0,
                        $planCurrency,
                        $periodInterval,
                        1,
                        $subscriberEmail,
                        $now,
                        $now,
                        $periodEnd,
                        'active'
                    );
                    if ($inserted) {
                        $iN->iN_InsertNotificationForSubscribe($subscriberId, $subscribedId);
                    }
                    $activated = (bool)$inserted;
                }
            }
        }
    } else {
        $scopeHint = epoch_webhook_payload_value($payload, ['x_subscription_scope', 'subscription_scope']);
        $refHint = epoch_webhook_payload_value($payload, ['x_subscription_ref_id', 'subscription_ref_id']);
        $subscriberHint = (int)epoch_webhook_payload_value($payload, ['x_user_id', 'user_id']);
        $planHint = epoch_webhook_payload_value($payload, ['x_plan_id', 'plan_id']);

        $existingSubscription = null;
        if ($transactionId !== '') {
            $existingSubscription = DB::one(
                "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND payment_subscription_id = ? LIMIT 1",
                [$transactionId]
            );
        }
        if (!$existingSubscription && $subscriberHint > 0 && $scopeHint !== '') {
            if ($scopeHint === 'community') {
                $existingSubscription = DB::one(
                    "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND subscription_scope = 'community' AND iuid_fk = ? AND subscription_ref_id = ? ORDER BY subscription_id DESC LIMIT 1",
                    [$subscriberHint, (int)$refHint]
                );
            } elseif ($scopeHint === 'community_plan') {
                $existingSubscription = DB::one(
                    "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND subscription_scope = 'community_plan' AND iuid_fk = ? ORDER BY subscription_id DESC LIMIT 1",
                    [$subscriberHint]
                );
            } else {
                $existingSubscription = DB::one(
                    "SELECT * FROM i_user_subscriptions WHERE payment_method = 'epoch' AND subscription_scope = 'profile' AND iuid_fk = ? AND plan_id = ? ORDER BY subscription_id DESC LIMIT 1",
                    [$subscriberHint, $planHint]
                );
            }
        }

        if ($existingSubscription) {
            $subscriptionId = (int)($existingSubscription['subscription_id'] ?? 0);
            $interval = (string)($existingSubscription['plan_interval'] ?? 'month');
            $intervalCount = (int)($existingSubscription['plan_interval_count'] ?? 1);
            if ($intervalCount < 1) {
                $intervalCount = 1;
            }
            $periodStartTs = strtotime((string)($existingSubscription['plan_period_end'] ?? ''));
            $nowTs = time();
            if ($periodStartTs === false || $periodStartTs < $nowTs) {
                $periodStartTs = $nowTs;
            }
            $periodStart = date('Y-m-d H:i:s', $periodStartTs);
            $periodEnd = epoch_webhook_add_interval($periodStart, $interval, $intervalCount);

            DB::exec(
                "UPDATE i_user_subscriptions SET plan_period_start = ?, plan_period_end = ?, status = 'active', in_status = '0', finished = '0' WHERE subscription_id = ?",
                [$periodStart, $periodEnd, $subscriptionId]
            );
            if (($existingSubscription['subscription_scope'] ?? '') === 'community') {
                epoch_webhook_sync_community_membership($existingSubscription, 'active', $periodStart, $periodEnd);
            }

            $scope = (string)($existingSubscription['subscription_scope'] ?? 'profile');
            $userNet = isset($existingSubscription['user_net_earning']) ? (float)$existingSubscription['user_net_earning'] : 0.0;
            if (in_array($scope, ['profile', 'community'], true) && $userNet > 0) {
                DB::exec(
                    "UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?",
                    [number_format($userNet, 2, '.', ''), (int)$existingSubscription['subscribed_iuid_fk']]
                );
            }
            $activated = true;
        }
    }

    if (!$activated) {
        epoch_webhook_log('Subscription event could not be mapped to activation/renewal', [
            'order_key' => $orderKey,
            'transaction_id' => $transactionId,
            'event_type' => $eventType
        ]);
        epoch_webhook_respond(422, 'Unable to activate subscription');
    }

    if ($intentRow) {
        DB::exec(
            "UPDATE i_user_subscription_intents SET status = 'ok', updated_at = ? WHERE intent_id = ?",
            [(int)time(), (int)$intentRow['intent_id']]
        );
    }
    if ($paymentRow) {
        $updateSql = "UPDATE i_user_payments SET payment_status = 'ok', amount = ?, currency = ? WHERE payment_id = ? AND payment_status <> 'ok'";
        DB::exec(
            $updateSql,
            [
                number_format($amountFromRow > 0 ? $amountFromRow : (float)($intentRow['plan_amount'] ?? 0), 2, '.', ''),
                (string)($intentRow['plan_amount_currency'] ?? $currencyFromRow),
                (int)$paymentRow['payment_id']
            ]
        );
        if ($transactionId !== '' && epoch_webhook_has_column('i_user_payments', 'epoch_transaction_id')) {
            DB::exec(
                "UPDATE i_user_payments SET epoch_transaction_id = ? WHERE payment_id = ?",
                [(string)$transactionId, (int)$paymentRow['payment_id']]
            );
        }
        $payerId = (int)($paymentRow['payer_iuid_fk'] ?? 0);
        if ($payerId > 0) {
            $iN->iN_AssignInvoiceToPayment(
                (int)$paymentRow['payment_id'],
                $payerId,
                (float)($intentRow['plan_amount'] ?? $amountFromRow),
                (string)($intentRow['plan_amount_currency'] ?? $currencyFromRow)
            );
        }
    }

    epoch_webhook_log('Subscription event applied', [
        'order_key' => $orderKey,
        'transaction_id' => $transactionId,
        'event_type' => $eventType
    ]);
    epoch_webhook_respond(200, 'OK');
}

if (!$paymentRow) {
    epoch_webhook_log('Non-subscription payment row missing', ['order_key' => $orderKey]);
    epoch_webhook_respond(404, 'Payment not found');
}

if (($paymentRow['payment_status'] ?? '') === 'ok' && in_array($eventType, ['initial_sale', 'rebill'], true)) {
    epoch_webhook_log('Non-subscription payment already processed', [
        'order_key' => $orderKey,
        'payment_type' => $paymentType
    ]);
    epoch_webhook_respond(200, 'Already processed');
}

$paymentId = (int)($paymentRow['payment_id'] ?? 0);
$payerId = (int)($paymentRow['payer_iuid_fk'] ?? 0);

if ($paymentType === 'point') {
    $planId = (int)($paymentRow['credit_plan_id'] ?? 0);
    $planData = $planId > 0 ? DB::one("SELECT plan_amount FROM i_premium_plans WHERE plan_id = ? LIMIT 1", [$planId]) : null;
    $points = isset($planData['plan_amount']) ? (float)$planData['plan_amount'] : 0;
    $updated = DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0', payment_option = 'epoch' WHERE payment_id = ? AND payment_status <> 'ok'",
        [$paymentId]
    );
    if ($updated > 0 && $points > 0 && $payerId > 0) {
        DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$points, $payerId]);
    }
} elseif ($paymentType === 'product') {
    $productId = (int)($paymentRow['paymet_product_id'] ?? 0);
    $productData = $productId > 0 ? DB::one("SELECT pr_price, iuid_fk FROM i_user_product_posts WHERE pr_id = ? LIMIT 1", [$productId]) : null;
    $productPrice = isset($productData['pr_price']) ? round((float)$productData['pr_price'], 2) : 0.0;
    $productOwnerId = (int)($productData['iuid_fk'] ?? 0);
    $split = $iN->iN_CalculateAgencySplit($productOwnerId, $productPrice, $adminFee);
    $updated = DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, payment_option = 'epoch'
         WHERE payment_id = ? AND payment_status <> 'ok'",
        [
            $productOwnerId,
            $split['agency_id'],
            number_format($productPrice, 2, '.', ''),
            number_format((float)$adminFee, 2, '.', ''),
            number_format((float)$split['agency_fee'], 2, '.', ''),
            number_format((float)$split['admin_earning'], 2, '.', ''),
            number_format((float)$split['agency_earning'], 2, '.', ''),
            number_format((float)$split['creator_net'], 2, '.', ''),
            $paymentId
        ]
    );
    if ($updated > 0 && $productOwnerId > 0) {
        DB::exec(
            "UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?",
            [number_format((float)$split['creator_net'], 2, '.', ''), $productOwnerId]
        );
    }
} elseif ($paymentType === 'tips') {
    $receiverId = (int)($paymentRow['payed_iuid_fk'] ?? 0);
    $tipAmount = $amountFromRow > 0 ? $amountFromRow : 0.0;
    $split = $iN->iN_CalculateAgencySplit($receiverId, $tipAmount, $adminFee);
    $updated = DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, amount = ?, payment_option = 'epoch'
         WHERE payment_id = ? AND payment_status <> 'ok'",
        [
            $split['agency_id'],
            number_format((float)$adminFee, 2, '.', ''),
            number_format((float)$split['agency_fee'], 2, '.', ''),
            number_format((float)$split['admin_earning'], 2, '.', ''),
            number_format((float)$split['agency_earning'], 2, '.', ''),
            number_format((float)$split['creator_net'], 2, '.', ''),
            number_format((float)$tipAmount, 2, '.', ''),
            $paymentId
        ]
    );
    if ($updated > 0 && $receiverId > 0) {
        DB::exec(
            "UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?",
            [number_format((float)$split['creator_net'], 2, '.', ''), $receiverId]
        );
    }
} elseif ($paymentType === 'campaign_donate') {
    $receiverId = (int)($paymentRow['payed_iuid_fk'] ?? 0);
    $campaignPostId = (int)($paymentRow['payed_post_id_fk'] ?? 0);
    $donateAmount = $amountFromRow > 0 ? $amountFromRow : 0.0;
    $split = $iN->iN_CalculateAgencySplit($receiverId, $donateAmount, $adminFee);
    $updated = DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, amount = ?, payment_option = 'epoch'
         WHERE payment_id = ? AND payment_status <> 'ok'",
        [
            $split['agency_id'],
            number_format((float)$adminFee, 2, '.', ''),
            number_format((float)$split['agency_fee'], 2, '.', ''),
            number_format((float)$split['admin_earning'], 2, '.', ''),
            number_format((float)$split['agency_earning'], 2, '.', ''),
            number_format((float)$split['creator_net'], 2, '.', ''),
            number_format((float)$donateAmount, 2, '.', ''),
            $paymentId
        ]
    );
    if ($updated > 0) {
        DB::exec(
            "UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?",
            [number_format((float)$split['creator_net'], 2, '.', ''), $receiverId]
        );
        if ($campaignPostId > 0) {
            DB::exec(
                "UPDATE i_campaigns SET raised_amount = raised_amount + ?, updated_at = ? WHERE post_id_fk = ? LIMIT 1",
                [number_format((float)$donateAmount, 2, '.', ''), time(), $campaignPostId]
            );
        }
    }
} elseif ($paymentType === 'agency_boost') {
    $agencyId = (int)($paymentRow['agency_id_fk'] ?? 0);
    $creatorId = (int)($paymentRow['payed_profile_id_fk'] ?? ($paymentRow['payed_iuid_fk'] ?? 0));
    $durationDays = (int)($paymentRow['agency_boost_duration_days'] ?? 0);
    if ($durationDays < 1 || $durationDays > 365) {
        $durationDays = (int)$iN->iN_GetSetting('agency_boost_default_days', 7);
        if ($durationDays < 1) {
            $durationDays = 1;
        }
        if ($durationDays > 365) {
            $durationDays = 365;
        }
    }
    $boostAmount = $amountFromRow > 0 ? $amountFromRow : 0.0;
    $updated = DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', amount = ?, admin_earning = ?, payment_option = 'epoch' WHERE payment_id = ? AND payment_status <> 'ok'",
        [
            number_format((float)$boostAmount, 2, '.', ''),
            number_format((float)$boostAmount, 2, '.', ''),
            $paymentId
        ]
    );
    if ($updated > 0) {
        $iN->iN_CreateAgencyBoost($agencyId, $creatorId, $payerId, $durationDays, false);
    }
} else {
    DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', payment_option = 'epoch' WHERE payment_id = ? AND payment_status <> 'ok'",
        [$paymentId]
    );
}

if ($paymentId > 0 && $payerId > 0) {
    $iN->iN_AssignInvoiceToPayment(
        $paymentId,
        $payerId,
        (float)$amountFromRow,
        (string)$currencyFromRow
    );
}
if ($transactionId !== '' && epoch_webhook_has_column('i_user_payments', 'epoch_transaction_id')) {
    DB::exec(
        "UPDATE i_user_payments SET epoch_transaction_id = ? WHERE payment_id = ?",
        [(string)$transactionId, $paymentId]
    );
}

epoch_webhook_log('Non-subscription payment applied', [
    'order_key' => $orderKey,
    'transaction_id' => $transactionId,
    'event_type' => $eventType,
    'payment_type' => $paymentType
]);

epoch_webhook_respond(200, 'OK');
