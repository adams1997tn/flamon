<?php
// YooKassa webhook endpoint for subscription activation and renewals.
// - Requires X-Dizzy-Webhook-Secret header
// - Refetches payment data from YooKassa API before mutating DB

require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($pdo) && $pdo instanceof PDO) {
    DB::init($pdo);
}

function yookassa_respond(int $status, string $message = ''): void
{
    http_response_code($status);
    if ($message !== '') {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
    exit;
}

function yookassa_get_header(string $name): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (isset($_SERVER[$key])) {
        return trim((string) $_SERVER[$key]);
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $headerName => $value) {
            if (strcasecmp((string) $headerName, $name) === 0) {
                return trim((string) $value);
            }
        }
    }
    return '';
}

function yookassa_fetch_payment(string $paymentId, string $shopId, string $secretKey, string $apiBaseUrl): array
{
    if ($paymentId === '') {
        return ['error' => true, 'message' => 'Missing payment id'];
    }
    $endpoint = rtrim($apiBaseUrl, '/') . '/v3/payments/' . rawurlencode($paymentId);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey),
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['error' => true, 'message' => $curlError];
    }

    $decoded = json_decode((string) $responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => true, 'message' => 'Invalid YooKassa response'];
    }

    if ($httpCode >= 400) {
        return [
            'error' => true,
            'message' => $decoded['description'] ?? $decoded['message'] ?? 'YooKassa request failed',
        ];
    }

    return ['error' => false, 'data' => $decoded];
}

function yookassa_calculate_period_end(string $start, string $interval, int $count): string
{
    $count = $count > 0 ? $count : 1;
    $interval = strtolower($interval);
    $date = new DateTime($start);
    if ($interval === 'week') {
        $days = 7 * $count;
        $date->modify('+' . $days . ' days');
    } elseif ($interval === 'year') {
        $date->modify('+' . $count . ' years');
    } else {
        $date->modify('+' . $count . ' months');
    }
    return $date->format('Y-m-d H:i:s');
}

function yookassa_is_new_period(array $row, string $newEnd): bool
{
    $currentEnd = $row['plan_period_end'] ?? '';
    if ($currentEnd === '') {
        return true;
    }
    $currentEndTs = strtotime((string) $currentEnd);
    $newEndTs = strtotime($newEnd);
    if ($currentEndTs === false || $newEndTs === false) {
        return true;
    }
    return $newEndTs > $currentEndTs;
}

$iN = new iN_UPDATES($db);
$inc = $iN->iN_Configurations();

$adminFee = isset($inc['fee']) ? (float) $inc['fee'] : 0.0;
$GLOBALS['adminFee'] = $adminFee;
$GLOBALS['taxStatus'] = isset($inc['tax_status']) ? (string) $inc['tax_status'] : '0';
$GLOBALS['taxRate'] = isset($inc['tax_rate']) ? (float) $inc['tax_rate'] : 0.0;
$GLOBALS['taxLabel'] = isset($inc['tax_label']) ? (string) $inc['tax_label'] : 'VAT';
$GLOBALS['taxRegistrationNumber'] = $inc['tax_registration_number'] ?? null;
$GLOBALS['taxCompanyName'] = $inc['tax_company_name'] ?? null;
$GLOBALS['taxCompanyAddress'] = $inc['tax_company_address'] ?? null;
$GLOBALS['taxInvoicePrefix'] = isset($inc['tax_invoice_prefix']) ? (string) $inc['tax_invoice_prefix'] : 'INV';
$GLOBALS['defaultCurrency'] = isset($inc['default_currency']) && $inc['default_currency'] !== ''
    ? (string) $inc['default_currency']
    : 'USD';

try {
    $paymentMethodRow = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1");
} catch (Throwable $th) {
    $paymentMethodRow = [];
}

$yookassaPaymentStatus = $paymentMethodRow['yookassa_active_pasive'] ?? '0';
$yookassaPaymentMode = $paymentMethodRow['yookassa_payment_mode'] ?? '0';
$yookassaTestShopId = (string) ($paymentMethodRow['yookassa_test_shop_id'] ?? '');
$yookassaTestSecretKey = (string) ($paymentMethodRow['yookassa_test_secret_key'] ?? '');
$yookassaLiveShopId = (string) ($paymentMethodRow['yookassa_live_shop_id'] ?? '');
$yookassaLiveSecretKey = (string) ($paymentMethodRow['yookassa_live_secret_key'] ?? '');
$yookassaWebhookSecret = (string) ($paymentMethodRow['yookassa_webhook_secret'] ?? '');
$yookassaCurrency = strtoupper((string) ($paymentMethodRow['yookassa_currency'] ?? 'RUB'));
$yookassaApiBase = 'https://api.yookassa.ru';

if ((int) $yookassaPaymentStatus !== 1) {
    yookassa_respond(400, 'YooKassa not enabled');
}
if ($yookassaWebhookSecret === '') {
    yookassa_respond(400, 'Webhook secret missing');
}

$incomingSecret = yookassa_get_header('X-Dizzy-Webhook-Secret');
if ($incomingSecret === '' || !hash_equals($yookassaWebhookSecret, $incomingSecret)) {
    yookassa_respond(401, 'Invalid webhook secret');
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    yookassa_respond(400, 'Invalid payload');
}

$paymentId = '';
if (isset($payload['object']['id'])) {
    $paymentId = (string) $payload['object']['id'];
} elseif (isset($payload['object']['payment_id'])) {
    $paymentId = (string) $payload['object']['payment_id'];
} elseif (isset($payload['id'])) {
    $paymentId = (string) $payload['id'];
}
if ($paymentId === '') {
    yookassa_respond(400, 'Missing payment id');
}

$testMode = (int) $yookassaPaymentMode !== 1;
$shopId = $testMode ? $yookassaTestShopId : $yookassaLiveShopId;
$secretKey = $testMode ? $yookassaTestSecretKey : $yookassaLiveSecretKey;
if ($shopId === '' || $secretKey === '') {
    yookassa_respond(400, 'YooKassa credentials missing');
}

$paymentResponse = yookassa_fetch_payment($paymentId, $shopId, $secretKey, $yookassaApiBase);
if (!empty($paymentResponse['error'])) {
    yookassa_respond(400, 'Unable to verify payment');
}

$paymentInfo = $paymentResponse['data'] ?? [];
$paymentId = (string) ($paymentInfo['id'] ?? $paymentId);
$metadata = isset($paymentInfo['metadata']) && is_array($paymentInfo['metadata']) ? $paymentInfo['metadata'] : [];
$orderKey = (string) ($metadata['order_key'] ?? '');
if ($orderKey === '') {
    yookassa_respond(400, 'Missing order key');
}

$paymentMethodId = '';
if (isset($paymentInfo['payment_method']['id'])) {
    $paymentMethodId = (string) $paymentInfo['payment_method']['id'];
} elseif (isset($paymentInfo['payment_method_id'])) {
    $paymentMethodId = (string) $paymentInfo['payment_method_id'];
}
if ($paymentMethodId === '') {
    yookassa_respond(400, 'Missing payment method id');
}

$intent = DB::one(
    "SELECT * FROM i_user_subscription_intents WHERE order_key = ? AND yookassa_payment_id = ? LIMIT 1",
    [$orderKey, $paymentId]
);
$paymentRow = DB::one(
    "SELECT * FROM i_user_payments WHERE order_key = ? AND yookassa_payment_id = ? LIMIT 1",
    [$orderKey, $paymentId]
);
if (!$intent && !$paymentRow) {
    yookassa_respond(404, 'Payment not found');
}

$amountValue = isset($paymentInfo['amount']['value']) ? (float) $paymentInfo['amount']['value'] : 0.0;
$currencyValue = strtoupper((string) ($paymentInfo['amount']['currency'] ?? ''));
$expectedAmount = $intent ? (float) ($intent['plan_amount'] ?? 0) : (float) ($paymentRow['amount'] ?? 0);
$expectedCurrency = '';
if ($intent && !empty($intent['plan_amount_currency'])) {
    $expectedCurrency = strtoupper((string) $intent['plan_amount_currency']);
} elseif ($paymentRow && !empty($paymentRow['currency'])) {
    $expectedCurrency = strtoupper((string) $paymentRow['currency']);
} else {
    $expectedCurrency = $yookassaCurrency !== '' ? $yookassaCurrency : 'RUB';
}

if ($expectedAmount > 0 && $amountValue > 0 && round($expectedAmount, 2) !== round($amountValue, 2)) {
    yookassa_respond(400, 'Amount mismatch');
}
if ($expectedCurrency !== '' && $currencyValue !== '' && $expectedCurrency !== $currencyValue) {
    yookassa_respond(400, 'Currency mismatch');
}

$subscriptionRow = null;
if ($intent) {
    $intentScope = (string) ($intent['subscription_scope'] ?? 'profile');
    $metadataScope = (string) ($metadata['subscription_scope'] ?? '');
    if ($metadataScope !== '' && $metadataScope !== $intentScope) {
        yookassa_respond(400, 'Scope mismatch');
    }
    $metaSubscriber = (int) ($metadata['subscriber_id'] ?? 0);
    if ($metaSubscriber > 0 && $metaSubscriber !== (int) ($intent['iuid_fk'] ?? 0)) {
        yookassa_respond(400, 'Subscriber mismatch');
    }
    $metaPlanId = (string) ($metadata['plan_id'] ?? '');
    if ($metaPlanId !== '' && (string) ($intent['plan_id'] ?? '') !== '' && $metaPlanId !== (string) $intent['plan_id']) {
        yookassa_respond(400, 'Plan mismatch');
    }
    if ($intentScope === 'community') {
        $metaCommunityId = (int) ($metadata['community_id'] ?? 0);
        if ($metaCommunityId > 0 && $metaCommunityId !== (int) ($intent['subscription_ref_id'] ?? 0)) {
            yookassa_respond(400, 'Community mismatch');
        }
    }
} else {
    $subscriptionId = (int) ($metadata['subscription_id'] ?? 0);
    if ($subscriptionId <= 0) {
        yookassa_respond(400, 'Missing subscription id');
    }
    $subscriptionRow = DB::one(
        "SELECT * FROM i_user_subscriptions WHERE subscription_id = ? LIMIT 1",
        [$subscriptionId]
    );
    if (!$subscriptionRow) {
        yookassa_respond(404, 'Subscription not found');
    }
    if ((string) ($subscriptionRow['payment_method'] ?? '') !== 'yookassa') {
        yookassa_respond(400, 'Invalid subscription method');
    }
    $storedPaymentMethodId = trim((string) ($subscriptionRow['payment_subscription_id'] ?? ''));
    if ($storedPaymentMethodId !== '' && $paymentMethodId !== '' && $storedPaymentMethodId !== $paymentMethodId) {
        yookassa_respond(400, 'Payment method mismatch');
    }
    $metadataScope = (string) ($metadata['subscription_scope'] ?? '');
    $subscriptionScope = (string) ($subscriptionRow['subscription_scope'] ?? 'profile');
    if ($metadataScope !== '' && $metadataScope !== $subscriptionScope) {
        yookassa_respond(400, 'Scope mismatch');
    }
    $metaSubscriber = (int) ($metadata['subscriber_id'] ?? 0);
    if ($metaSubscriber > 0 && $metaSubscriber !== (int) ($subscriptionRow['iuid_fk'] ?? 0)) {
        yookassa_respond(400, 'Subscriber mismatch');
    }
    $metaCreator = (int) ($metadata['creator_id'] ?? 0);
    if ($metaCreator > 0 && $metaCreator !== (int) ($subscriptionRow['subscribed_iuid_fk'] ?? 0)) {
        yookassa_respond(400, 'Creator mismatch');
    }
    $metaPlanId = (string) ($metadata['plan_id'] ?? '');
    if ($metaPlanId !== '' && (string) ($subscriptionRow['plan_id'] ?? '') !== '' && $metaPlanId !== (string) $subscriptionRow['plan_id']) {
        yookassa_respond(400, 'Plan mismatch');
    }
    if ($subscriptionScope === 'community') {
        $metaCommunityId = (int) ($metadata['community_id'] ?? 0);
        if ($metaCommunityId > 0 && $metaCommunityId !== (int) ($subscriptionRow['subscription_ref_id'] ?? 0)) {
            yookassa_respond(400, 'Community mismatch');
        }
    }
}

$status = strtolower((string) ($paymentInfo['status'] ?? ''));
$paid = isset($paymentInfo['paid']) ? (bool) $paymentInfo['paid'] : false;

if ($status !== 'succeeded' || $paid !== true) {
    if ($intent && ($intent['status'] ?? '') === 'pending') {
        DB::exec(
            "UPDATE i_user_subscription_intents SET status = 'declined', updated_at = ? WHERE intent_id = ?",
            [(int) time(), (int) $intent['intent_id']]
        );
    }
    if ($paymentRow && ($paymentRow['payment_status'] ?? '') === 'pending') {
        DB::exec(
            "UPDATE i_user_payments SET payment_status = 'declined' WHERE payment_id = ?",
            [(int) $paymentRow['payment_id']]
        );
    }
    yookassa_respond(200, 'Ignored');
}

if ($intent && ($intent['status'] ?? '') === 'ok') {
    yookassa_respond(200, 'Already processed');
}
if (!$intent && $paymentRow && ($paymentRow['payment_status'] ?? '') === 'ok') {
    yookassa_respond(200, 'Already processed');
}

$planCurrency = $expectedCurrency !== '' ? $expectedCurrency : $yookassaCurrency;
$amountFormatted = number_format((float) $expectedAmount, 2, '.', '');
$now = date('Y-m-d H:i:s');
$activated = false;

if ($intent) {
    $scope = (string) ($intent['subscription_scope'] ?? 'profile');
    $subscriberId = (int) ($intent['iuid_fk'] ?? 0);
    $subscribedId = (int) ($intent['subscribed_iuid_fk'] ?? 0);
    $subscriberName = $intent['subscriber_name'] ?? '';
    $subscriberEmail = $intent['subscriber_email'] ?? '';

    if ($subscriberName === '' || $subscriberEmail === '') {
        $subscriberData = $subscriberId > 0 ? $iN->iN_GetUserDetails($subscriberId) : null;
        if ($subscriberData) {
            if ($subscriberName === '') {
                $subscriberName = $subscriberData['i_user_fullname'] ?: $subscriberData['i_username'];
            }
            if ($subscriberEmail === '') {
                $subscriberEmail = $subscriberData['i_user_email'];
            }
        }
    }

    if ($scope === 'community') {
        $communityId = (int) ($intent['subscription_ref_id'] ?? 0);
        if ($subscriberId > 0 && $communityId > 0) {
            $alreadyMember = (bool) DB::col(
                "SELECT 1 FROM community_memberships WHERE community_id = ? AND user_id = ? AND status = 'active' LIMIT 1",
                [$communityId, $subscriberId]
            );
            if ($alreadyMember) {
                $activated = true;
            } else {
                $communityData = $iN->iN_GetCommunityById($communityId);
                if ($communityData && (string) ($communityData['status'] ?? '') === 'active') {
                    $ownerId = (int) ($communityData['owner_user_id'] ?? $subscribedId);
                    $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
                    $result = $iN->iN_InsertCommunitySubscription(
                        $subscriberId,
                        $communityId,
                        $ownerId,
                        $subscriberName,
                        'yookassa',
                        $paymentMethodId,
                        $paymentId,
                        'yookassa_community_' . $communityId,
                        $amountFormatted,
                        0,
                        0,
                        $planCurrency,
                        'month',
                        1,
                        $subscriberEmail,
                        $now,
                        $now,
                        $periodEnd,
                        'active'
                    );
                    $activated = (bool) $result;
                }
            }
        }
    } elseif ($scope === 'community_plan') {
        if ($subscriberId > 0) {
            $hasPlan = $iN->iN_HasActiveCommunityPlan($subscriberId);
            if ($hasPlan) {
                $activated = true;
            } else {
                $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
                $result = $iN->iN_InsertCommunityPlanSubscription(
                    $subscriberId,
                    $subscriberName,
                    'yookassa',
                    $paymentMethodId,
                    $paymentId,
                    'yookassa_community_plan',
                    $amountFormatted,
                    $amountFormatted,
                    $planCurrency,
                    'month',
                    1,
                    $subscriberEmail,
                    $now,
                    $now,
                    $periodEnd,
                    'active'
                );
                $activated = (bool) $result;
            }
        }
    } else {
        $planId = (int) ($intent['plan_id'] ?? 0);
        if ($subscriberId > 0 && $subscribedId > 0 && $planId > 0) {
            $alreadySubscriber = $iN->iN_CheckUserIsInSubscriber($subscriberId, $subscribedId);
            if ($alreadySubscriber) {
                $activated = true;
            } else {
                $planDetails = $iN->iN_CheckPlanExist($planId, $subscribedId);
                if ($planDetails) {
                    $planType = (string) ($planDetails['plan_type'] ?? 'monthly');
                    $planInterval = 'month';
                    $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
                    if ($planType === 'weekly') {
                        $planInterval = 'week';
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+7 days'));
                    } elseif ($planType === 'yearly') {
                        $planInterval = 'year';
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+1 year'));
                    }
                    $result = $iN->iN_InsertUserSubscription(
                        $subscriberId,
                        $subscribedId,
                        'yookassa',
                        $subscriberName,
                        $paymentMethodId,
                        $paymentId,
                        'yookassa_' . $planId,
                        $amountFormatted,
                        0,
                        0,
                        $planCurrency,
                        $planInterval,
                        1,
                        $subscriberEmail,
                        $now,
                        $now,
                        $periodEnd,
                        'active'
                    );
                    if ($result) {
                        $iN->iN_InsertNotificationForSubscribe($subscriberId, $subscribedId);
                    }
                    $activated = (bool) $result;
                }
            }
        }
    }
} else {
    $subscriptionId = (int) ($subscriptionRow['subscription_id'] ?? 0);
    $scope = (string) ($subscriptionRow['subscription_scope'] ?? 'profile');
    $subscriberId = (int) ($subscriptionRow['iuid_fk'] ?? 0);
    $subscribedId = (int) ($subscriptionRow['subscribed_iuid_fk'] ?? 0);
    $interval = (string) ($subscriptionRow['plan_interval'] ?? 'month');
    $intervalCount = (int) ($subscriptionRow['plan_interval_count'] ?? 1);
    if ($intervalCount < 1) {
        $intervalCount = 1;
    }

    $periodStartRaw = (string) ($subscriptionRow['plan_period_end'] ?? '');
    $periodStartTs = $periodStartRaw !== '' ? strtotime($periodStartRaw) : false;
    $nowTs = time();
    if ($periodStartTs === false || $periodStartTs < $nowTs) {
        $periodStartTs = $nowTs;
    }
    $periodStart = date('Y-m-d H:i:s', $periodStartTs);
    $periodEnd = yookassa_calculate_period_end($periodStart, $interval, $intervalCount);
    $isNewPeriod = yookassa_is_new_period($subscriptionRow, $periodEnd);

    $updated = DB::exec(
        "UPDATE i_user_subscriptions SET plan_period_start = ?, plan_period_end = ?, status = 'active', in_status = '0', finished = '0' WHERE subscription_id = ?",
        [$periodStart, $periodEnd, $subscriptionId]
    );
    if ($scope === 'community') {
        $communityId = (int) ($subscriptionRow['subscription_ref_id'] ?? 0);
        if ($communityId > 0 && $subscriberId > 0) {
            $iN->iN_CreateCommunityMembership($communityId, $subscriberId, $subscriptionId, 'active', $periodStart, $periodEnd);
        }
    }
    if ($isNewPeriod && $scope !== 'community_plan') {
        $netEarning = (float) ($subscriptionRow['user_net_earning'] ?? 0);
        if ($netEarning > 0 && $subscribedId > 0) {
            DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [(string) $netEarning, $subscribedId]);
        }
    }
    $activated = $updated > 0 || !$isNewPeriod;
}

if (!$activated) {
    yookassa_respond(400, 'Subscription activation failed');
}

if ($intent) {
    DB::exec(
        "UPDATE i_user_subscription_intents SET status = 'ok', updated_at = ?, yookassa_payment_id = ? WHERE intent_id = ?",
        [(int) time(), (string) $paymentId, (int) $intent['intent_id']]
    );
}
if ($paymentRow) {
    DB::exec(
        "UPDATE i_user_payments SET payment_status = 'ok', amount = ?, currency = ? WHERE payment_id = ? AND payment_status <> 'ok'",
        [$amountFormatted, $planCurrency, (int) $paymentRow['payment_id']]
    );
    $payerId = (int) ($paymentRow['payer_iuid_fk'] ?? 0);
    if ($payerId > 0) {
        $iN->iN_AssignInvoiceToPayment(
            (int) $paymentRow['payment_id'],
            $payerId,
            (float) $amountFormatted,
            $planCurrency
        );
    }
}

yookassa_respond(200, 'OK');
