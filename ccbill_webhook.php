<?php
// CCBill webhook endpoint for handling subscription and one-time events.
// Validates the dynamic pricing digest and updates local records accordingly.

require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($pdo) && $pdo instanceof PDO) {
    DB::init($pdo);
}

$iN = new iN_UPDATES($db);
$inc = $iN->iN_Configurations();

$adminFee = isset($inc['fee']) ? (float) $inc['fee'] : 0.0;
$ccbillAccount = $inc['ccbill_account_number'] ?? '';
$ccbillSubaccount = $inc['ccbill_subaccount_number'] ?? '';
$ccbillSaltKey = $inc['ccbill_salt_key'] ?? '';
$ccbillCurrency = strtoupper($inc['ccbill_currency'] ?? 'USD');
$defaultCurrency = strtoupper($inc['default_currency'] ?? $ccbillCurrency);

try {
    $paymentMethodRow = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1");
} catch (Throwable $th) {
    $paymentMethodRow = [];
}

if ($ccbillAccount === '' && isset($paymentMethodRow['ccbill_account_number'])) {
    $ccbillAccount = $paymentMethodRow['ccbill_account_number'];
}
if ($ccbillSubaccount === '' && isset($paymentMethodRow['ccbill_subaccount_number'])) {
    $ccbillSubaccount = $paymentMethodRow['ccbill_subaccount_number'];
}
if ($ccbillSaltKey === '' && isset($paymentMethodRow['ccbill_salt_key'])) {
    $ccbillSaltKey = $paymentMethodRow['ccbill_salt_key'];
}
if ((empty($ccbillCurrency) || $ccbillCurrency === 'USD') && isset($paymentMethodRow['ccbill_currency']) && $paymentMethodRow['ccbill_currency'] !== '') {
    $ccbillCurrency = strtoupper($paymentMethodRow['ccbill_currency']);
}
if ($adminFee <= 0 && isset($paymentMethodRow['fee'])) {
    $adminFee = (float) $paymentMethodRow['fee'];
}

// CCBill must be configured before accepting callbacks.
if ($ccbillAccount === '' || $ccbillSaltKey === '') {
    http_response_code(400);
    echo 'CCBill not configured';
    exit;
}

$currencyCodes = [
    'AUD' => 36,
    'CAD' => 124,
    'JPY' => 392,
    'GBP' => 826,
    'USD' => 840,
    'EUR' => 978,
];

$currencyCode = $currencyCodes[$ccbillCurrency] ?? 840;

// Convenience responder
function ccbill_respond(int $status, string $message = ''): void
{
    http_response_code($status);
    if ($message !== '') {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
    exit;
}

$payload = $_POST;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($payload)) {
    ccbill_respond(405, 'Method Not Allowed');
}

$eventType = $payload['eventType'] ?? '';
if ($eventType === '') {
    ccbill_respond(400, 'Missing event type');
}
$eventTypeNormalized = strtoupper($eventType);

// Decode metadata stored in custom2/base64 JSON.
function ccbill_decode_metadata(array $payload): array
{
    $value = $payload['custom2'] ?? ($payload['X-custom2'] ?? '');
    if ($value === '') {
        return [];
    }
    if (!is_string($value)) {
        return [];
    }
    $decoded = base64_decode($value, true);
    if ($decoded === false) {
        return [];
    }
    $json = json_decode($decoded, true);
    return is_array($json) ? $json : [];
}

function ccbill_verify_digest(array $payload, int $currencyCode, string $saltKey): bool
{
    $digest = $payload['dynamicPricingValidationDigest'] ?? ($payload['formDigest'] ?? '');
    if ($digest === '') {
        // Some management events might omit the digest; accept but log elsewhere if needed.
        return true;
    }

    $type = strtolower((string) ($payload['X-type'] ?? $payload['custom1'] ?? ''));
    $amount = $payload['subscriptionInitialPrice']
        ?? $payload['initialPrice']
        ?? $payload['amountFixed']
        ?? $payload['X-priceOriginal']
        ?? '0';

    $amount = (string) $amount;

    if ($type === 'subscription') {
        $period = $payload['X-interval'] ?? $payload['initialPeriod'] ?? $payload['recurringPeriod'] ?? $payload['initialPeriod'] ?? '0';
        $formNumRebills = '99';
        $expected = md5($amount . $period . $amount . $period . $formNumRebills . $currencyCode . $saltKey);
    } else {
        $period = $payload['initialPeriod'] ?? '2';
        $expected = md5($amount . $period . $currencyCode . $saltKey);
    }

    return hash_equals($expected, $digest);
}

if (!ccbill_verify_digest($payload, $currencyCode, $ccbillSaltKey)) {
    ccbill_respond(400, 'Invalid digest');
}

$metadata = ccbill_decode_metadata($payload);
$type = strtolower((string) ($metadata['type'] ?? ($payload['custom1'] ?? $payload['X-type'] ?? '')));
$orderKey = $metadata['order_key'] ?? ($payload['orderId'] ?? '');
ccbill_log('Webhook received', [
    'eventType' => $eventType,
    'type' => $type,
    'orderKey' => $orderKey,
    'status' => $payload['status'] ?? null
]);

function ccbill_log(string $message, array $context = []): void
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
    @file_put_contents($logDir . '/ccbill_webhook.log', $line, FILE_APPEND);
}

// Helper for wallet or product purchases
function ccbill_handle_wallet_or_product(string $type, string $orderKey, array $metadata, array $payload, float $adminFee): bool
{
    global $iN, $defaultCurrency, $inc;
    if ($orderKey === '') {
        ccbill_log('Missing order key for wallet/product event', compact('type', 'metadata'));
        return false;
    }

    $pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderKey]);
    if (!$pData) {
        ccbill_log('Payment row not found', ['orderKey' => $orderKey]);
        return false;
    }

    $currentStatus = (string) ($pData['payment_status'] ?? '');
    if ($currentStatus === 'ok') {
        ccbill_log('Payment already marked ok', ['orderKey' => $orderKey]);
        return true;
    }

    ccbill_log('Payment row loaded', [
        'orderKey' => $orderKey,
        'payment_id' => $pData['payment_id'] ?? null,
        'payment_status' => $currentStatus,
        'credit_plan_id' => $pData['credit_plan_id'] ?? null,
        'paymet_product_id' => $pData['paymet_product_id'] ?? null,
        'payer_iuid_fk' => $pData['payer_iuid_fk'] ?? null
    ]);

    $userPayedPlanID = $pData['credit_plan_id'] ?? null;
    $payerUserID = $pData['payer_iuid_fk'] ?? null;
    $productID = $pData['paymet_product_id'] ?? null;
    $receiverId = $pData['payed_iuid_fk'] ?? null;
    $paymentType = $pData['payment_type'] ?? '';
    $orderAmount = null;

    if (isset($metadata['order_amount'])) {
        $orderAmount = (float) $metadata['order_amount'];
    } elseif (isset($payload['order_amount'])) {
        $orderAmount = (float) $payload['order_amount'];
    } elseif (isset($payload['initialPrice'])) {
        $orderAmount = (float) $payload['initialPrice'];
    } elseif (isset($payload['subscriptionInitialPrice'])) {
        $orderAmount = (float) $payload['subscriptionInitialPrice'];
    }

    if ($paymentType === 'campaign_donate') {
        $amount = $orderAmount !== null ? $orderAmount : (float) ($pData['amount'] ?? 0);
        $payedPostId = (int) ($pData['payed_post_id_fk'] ?? 0);
        if (!$receiverId || $amount <= 0 || $payedPostId <= 0) {
            ccbill_log('Campaign donation missing receiver/post/amount', ['orderKey' => $orderKey, 'receiverId' => $receiverId, 'payedPostId' => $payedPostId, 'amount' => $amount]);
            return false;
        }
        $campaignRow = DB::one(
            "SELECT campaign_id, owner_uid_fk FROM i_campaigns WHERE post_id_fk = ? LIMIT 1",
            [$payedPostId]
        );
        if (!$campaignRow || (int)($campaignRow['owner_uid_fk'] ?? 0) !== (int) $receiverId) {
            ccbill_log('Campaign donation campaign row mismatch', ['orderKey' => $orderKey, 'receiverId' => $receiverId, 'payedPostId' => $payedPostId]);
            return false;
        }
        $adminFeePercent = max(0.0, (float) $adminFee);
        $split = $iN->iN_CalculateAgencySplit($receiverId, $amount, $adminFeePercent);
        $adminEarning = $split['admin_earning'];
        $userEarning = $split['creator_net'];
        $agencyId = $split['agency_id'];
        $agencyFee = $split['agency_fee'];
        $agencyEarning = $split['agency_earning'];
        $amountString = number_format($amount, 2, '.', '');

        DB::begin();
        try {
            $updated = DB::exec(
                "UPDATE i_user_payments
                 SET payment_status = 'ok',
                     agency_id_fk = ?,
                     amount = ?,
                     fee = ?,
                     agency_fee = ?,
                     admin_earning = ?,
                     agency_earning = ?,
                     user_earning = ?
                 WHERE order_key = ? AND payment_type = 'campaign_donate' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
                [
                    $agencyId,
                    $amountString,
                    number_format($adminFeePercent, 2, '.', ''),
                    number_format((float) $agencyFee, 2, '.', ''),
                    number_format((float) $adminEarning, 2, '.', ''),
                    number_format((float) $agencyEarning, 2, '.', ''),
                    number_format((float) $userEarning, 2, '.', ''),
                    $orderKey
                ]
            );
            if ($updated !== 1) {
                DB::rollBack();
                ccbill_log('Campaign donation update affected zero rows', ['orderKey' => $orderKey]);
                return true;
            }
            DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $receiverId]);
            DB::exec("UPDATE i_campaigns SET raised_amount = raised_amount + ?, updated_at = ? WHERE post_id_fk = ? LIMIT 1", [$amountString, time(), $payedPostId]);
            if (!empty($pData['payment_id'])) {
                $iN->iN_AssignInvoiceToPayment((int) $pData['payment_id'], (int) $payerUserID, (float) $amountString, $defaultCurrency);
            }
            DB::commit();
        } catch (Throwable $th) {
            DB::rollBack();
            ccbill_log('DB error while processing campaign donation', ['orderKey' => $orderKey, 'error' => $th->getMessage()]);
            return false;
        }

        $iN->iN_InsertNotificationForCampaignDonation((int)$payerUserID, (int)$receiverId, (int)$payedPostId, (float)$amountString);
        return true;
    }

    if ($paymentType === 'tips') {
        $amount = $orderAmount !== null ? $orderAmount : (float) ($pData['amount'] ?? 0);
        if (!$receiverId || $amount <= 0) {
            ccbill_log('Tip payment missing receiver or amount', ['orderKey' => $orderKey, 'receiverId' => $receiverId, 'amount' => $amount]);
            return false;
        }
        $adminFeePercent = max(0.0, (float) $adminFee);
        $split = $iN->iN_CalculateAgencySplit($receiverId, $amount, $adminFeePercent);
        $adminEarning = $split['admin_earning'];
        $userEarning = $split['creator_net'];
        $agencyId = $split['agency_id'];
        $agencyFee = $split['agency_fee'];
        $agencyEarning = $split['agency_earning'];
        $amountString = number_format($amount, 2, '.', '');

        DB::begin();
        try {
            $updated = DB::exec(
                "UPDATE i_user_payments
                 SET payment_status = 'ok',
                     agency_id_fk = ?,
                     amount = ?,
                     fee = ?,
                     agency_fee = ?,
                     admin_earning = ?,
                     agency_earning = ?,
                     user_earning = ?
                 WHERE order_key = ? AND payment_type = 'tips' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
                [
                    $agencyId,
                    $amountString,
                    number_format($adminFeePercent, 2, '.', ''),
                    number_format((float) $agencyFee, 2, '.', ''),
                    number_format((float) $adminEarning, 2, '.', ''),
                    number_format((float) $agencyEarning, 2, '.', ''),
                    number_format((float) $userEarning, 2, '.', ''),
                    $orderKey
                ]
            );
            if ($updated !== 1) {
                DB::rollBack();
                ccbill_log('Tip payment update affected zero rows', ['orderKey' => $orderKey]);
                return true;
            }
            DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [$userEarning, $receiverId]);
            if (!empty($pData['payment_id'])) {
                $iN->iN_AssignInvoiceToPayment((int) $pData['payment_id'], (int) $payerUserID, (float) $amountString, $defaultCurrency);
            }
            DB::commit();
        } catch (Throwable $th) {
            DB::rollBack();
            ccbill_log('DB error while processing tip', ['orderKey' => $orderKey, 'error' => $th->getMessage()]);
            return false;
        }
        $receiverDetails = $iN->iN_GetuserDetails((int) $receiverId);
        $oneSignalUserDeviceKey = isset($receiverDetails['device_key']) ? $receiverDetails['device_key'] : null;
        if ($oneSignalUserDeviceKey && !empty($inc['oneSignalApi']) && !empty($inc['oneSignalRestApi'])) {
            $msgBody = 'You received a tip';
            $msgTitle = 'Tip payment';
            $url = ($inc['site_url'] ?? '') . 'settings?tab=dashboard';
            $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $inc['oneSignalApi'], $inc['oneSignalRestApi']);
        }

        return true;
    }

    if ($type === 'product' || (!empty($productID) && empty($userPayedPlanID))) {
        ccbill_log('Processing product branch', ['orderKey' => $orderKey, 'productID' => $productID, 'userPayedPlanID' => $userPayedPlanID]);
        if (!$productID || !$payerUserID) {
            ccbill_log('Missing product/payment owner data', ['orderKey' => $orderKey, 'productID' => $productID, 'payerUserID' => $payerUserID]);
            return false;
        }
        $productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
        if (!$productData) {
            ccbill_log('Product data not found', ['productID' => $productID]);
            return false;
        }
        $productPrice = $productData['pr_price'] ?? null;
        $productOwnerID = $productData['iuid_fk'] ?? null;
        if ($productPrice === null || $productOwnerID === null) {
            ccbill_log('Incomplete product data', ['productID' => $productID]);
            return false;
        }

        $productPrice = (float) $productPrice;
        if ($orderAmount !== null && $orderAmount > 0) {
            $productPrice = $orderAmount;
        }
        $adminFeePercent = max(0.0, (float) $adminFee);
        $split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFeePercent);
        $adminEarning = $split['admin_earning'];
        $userEarning = $split['creator_net'];
        $agencyId = $split['agency_id'];
        $agencyFee = $split['agency_fee'];
        $agencyEarning = $split['agency_earning'];
        $amountString = $productPrice > 0 ? number_format($productPrice, 2, '.', '') : '0.00';

        DB::begin();
        try {
            $updated = DB::exec(
                "UPDATE i_user_payments 
                 SET payment_status = 'ok',
                     payed_iuid_fk = ?,
                     agency_id_fk = ?,
                     amount = ?,
                     fee = ?,
                     agency_fee = ?,
                     admin_earning = ?,
                     agency_earning = ?,
                     user_earning = ?
                 WHERE order_key = ? AND payment_type = 'product' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
                [
                    $productOwnerID,
                    $agencyId,
                    $amountString,
                    number_format($adminFeePercent, 2, '.', ''),
                    number_format((float) $agencyFee, 2, '.', ''),
                    number_format((float) $adminEarning, 2, '.', ''),
                    number_format((float) $agencyEarning, 2, '.', ''),
                    number_format((float) $userEarning, 2, '.', ''),
                    $orderKey
                ]
            );
            if ($updated === 0) {
                ccbill_log('Payment row update affected zero rows', ['orderKey' => $orderKey]);
                DB::rollBack();
                return true;
            }

            $credited = DB::exec(
                "UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?",
                [number_format($userEarning, 2, '.', ''), $productOwnerID]
            );
            if ($credited === 0) {
                ccbill_log('Wallet credit affected zero rows', ['orderKey' => $orderKey, 'productOwnerID' => $productOwnerID]);
                throw new RuntimeException('Unable to credit seller wallet');
            }

            DB::commit();
            ccbill_log('Product payment updated', [
                'orderKey' => $orderKey,
                'productPrice' => $productPrice,
                'userEarning' => $userEarning
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            ccbill_log('Product payment update failed', ['orderKey' => $orderKey, 'error' => $th->getMessage()]);
            return false;
        }

        return true;
    }

    if ($userPayedPlanID && $payerUserID) {
        ccbill_log('Processing wallet branch', ['orderKey' => $orderKey, 'planID' => $userPayedPlanID, 'payerUserID' => $payerUserID]);
        $planData = DB::one("SELECT plan_amount, amount FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
        if (!$planData) {
            ccbill_log('Plan data not found', ['plan_id' => $userPayedPlanID]);
            return false;
        }
        $planPoints = (int) ($planData['plan_amount'] ?? 0);
        if ($planPoints <= 0) {
            ccbill_log('Plan points invalid', ['plan_id' => $userPayedPlanID, 'planPoints' => $planPoints]);
            return false;
        }
        $planPrice = isset($planData['amount']) ? (float) $planData['amount'] : null;
        if ($planPrice === null && $orderAmount !== null) {
            $planPrice = $orderAmount;
        }
        $amountString = $planPrice !== null ? number_format($planPrice, 2, '.', '') : '0.00';

        DB::begin();
        try {
            $paymentUpdated = DB::exec(
                "UPDATE i_user_payments 
                 SET payment_status = 'ok',
                     agency_id_fk = NULL,
                     amount = ?,
                     fee = '0',
                     agency_fee = '0',
                     admin_earning = '0',
                     agency_earning = '0',
                     user_earning = '0'
                 WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
                [$amountString, $orderKey]
            );
            if ($paymentUpdated === 0) {
                ccbill_log('Payment row update affected zero rows (wallet)', ['orderKey' => $orderKey]);
                DB::rollBack();
                return true;
            }

            $walletUpdated = DB::exec(
                "UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?",
                [$planPoints, (int) $payerUserID]
            );
            if ($walletUpdated === 0) {
                ccbill_log('Wallet points update affected zero rows', ['orderKey' => $orderKey, 'payerUserID' => $payerUserID]);
                throw new RuntimeException('Unable to credit buyer wallet points');
            }

            DB::commit();
            ccbill_log('Wallet payment updated', [
                'orderKey' => $orderKey,
                'planPoints' => $planPoints,
                'planPrice' => $planPrice
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            ccbill_log('Wallet payment update failed', ['orderKey' => $orderKey, 'error' => $th->getMessage()]);
            return false;
        }

        return true;
    }

    ccbill_log('Wallet/product payment type not handled', [
        'orderKey' => $orderKey,
        'type' => $type,
        'userPayedPlanID' => $userPayedPlanID,
        'productID' => $productID
    ]);
    return false;
}

function ccbill_handle_agency_boost(string $orderKey, array $metadata, array $payload): bool
{
    global $iN, $defaultCurrency;
    if ($orderKey === '') {
        ccbill_log('Missing order key for agency boost', ['orderKey' => $orderKey]);
        return false;
    }
    $pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
    if (!$pData) {
        ccbill_log('Agency boost payment row not found', ['orderKey' => $orderKey]);
        return false;
    }
    if (($pData['payment_type'] ?? '') !== 'agency_boost') {
        ccbill_log('Order key is not agency_boost', ['orderKey' => $orderKey, 'payment_type' => $pData['payment_type'] ?? null]);
        return false;
    }
    if (($pData['payment_status'] ?? '') === 'ok') {
        return true;
    }
    $amount = null;
    if (isset($metadata['amount'])) {
        $amount = (float) $metadata['amount'];
    } elseif (isset($payload['initialPrice'])) {
        $amount = (float) $payload['initialPrice'];
    } elseif (isset($payload['subscriptionInitialPrice'])) {
        $amount = (float) $payload['subscriptionInitialPrice'];
    } elseif (isset($pData['amount'])) {
        $amount = (float) $pData['amount'];
    }
    if ($amount === null) {
        $amount = 0.0;
    }
    if ($amount < 0) {
        $amount = 0.0;
    }
    $amount = round($amount, 2);
    $amountString = number_format($amount, 2, '.', '');
    $agencyId = (int) ($pData['agency_id_fk'] ?? 0);
    $creatorId = (int) ($pData['payed_profile_id_fk'] ?? ($pData['payed_iuid_fk'] ?? 0));
    $payerUserID = (int) ($pData['payer_iuid_fk'] ?? 0);
    if ($agencyId <= 0 || $creatorId <= 0 || $payerUserID <= 0) {
        ccbill_log('Agency boost missing agency/creator/payer', ['orderKey' => $orderKey, 'agency_id' => $agencyId, 'creator_id' => $creatorId]);
        return false;
    }
    $durationDays = (int) ($pData['agency_boost_duration_days'] ?? 0);
    if ($durationDays < 1 || $durationDays > 365) {
        $durationDays = (int) $iN->iN_GetSetting('agency_boost_default_days', 7);
        if ($durationDays < 1) {
            $durationDays = 1;
        }
        if ($durationDays > 365) {
            $durationDays = 365;
        }
    }

    DB::begin();
    try {
        $updated = DB::exec(
            "UPDATE i_user_payments 
             SET payment_status = 'ok',
                 agency_id_fk = ?,
                 fee = '0',
                 agency_fee = '0',
                 admin_earning = ?,
                 agency_earning = '0',
                 user_earning = '0',
                 amount = ?,
                 payment_option = 'ccbill'
             WHERE order_key = ? AND payment_type = 'agency_boost' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
            [
                $agencyId,
                $amountString,
                $amountString,
                $orderKey
            ]
        );
        if ($updated !== 1) {
            DB::rollBack();
            return true;
        }
        $boostId = $iN->iN_CreateAgencyBoost($agencyId, $creatorId, $payerUserID, $durationDays, false);
        if (!$boostId) {
            DB::rollBack();
            return false;
        }
        DB::commit();
    } catch (Throwable $th) {
        DB::rollBack();
        ccbill_log('Agency boost processing error', ['orderKey' => $orderKey, 'error' => $th->getMessage()]);
        return false;
    }

    $paymentId = (int) ($pData['payment_id'] ?? 0);
    if ($paymentId > 0) {
        $iN->iN_AssignInvoiceToPayment($paymentId, $payerUserID, $amount, $defaultCurrency);
    }
    return true;
}

function ccbill_sync_community_membership(array $row, string $status, ?string $start = null, ?string $end = null): void
{
    if (($row['subscription_scope'] ?? '') !== 'community') {
        return;
    }
    $communityId = (int) ($row['subscription_ref_id'] ?? 0);
    $userId = (int) ($row['iuid_fk'] ?? 0);
    $subscriptionId = (int) ($row['subscription_id'] ?? 0);
    if ($communityId <= 0 || $userId <= 0) {
        return;
    }
    $now = date('Y-m-d H:i:s');
    $startedAt = $start ?: $now;
    $endedAt = $end;
    DB::exec(
        "INSERT INTO community_memberships (community_id, user_id, subscription_id, status, started_at, ended_at, created_at)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE subscription_id = VALUES(subscription_id), status = VALUES(status), started_at = VALUES(started_at), ended_at = VALUES(ended_at)",
        [
            $communityId,
            $userId,
            $subscriptionId > 0 ? $subscriptionId : null,
            (string) $status,
            (string) $startedAt,
            $endedAt !== null ? (string) $endedAt : null,
            $now
        ]
    );
}

function ccbill_handle_community_subscription(array $metadata, array $payload, float $adminFee, string $ccbillCurrency, iN_UPDATES $iN, string $orderKey, string $defaultTransactionId): bool
{
    $subscriberID = (int) ($metadata['subscriber_id'] ?? 0);
    $communityID = (int) ($metadata['community_id'] ?? 0);
    if ($subscriberID <= 0 || $communityID <= 0) {
        return false;
    }

    $community = DB::one("SELECT id, owner_user_id, monthly_price, member_limit, status FROM communities WHERE id = ? LIMIT 1", [$communityID]);
    if (!$community || (string) ($community['status'] ?? '') !== 'active') {
        return false;
    }
    $ownerID = (int) ($community['owner_user_id'] ?? 0);
    if ($ownerID <= 0) {
        return false;
    }

    $alreadyMember = (bool) DB::col(
        "SELECT 1 FROM community_memberships WHERE community_id = ? AND user_id = ? AND status = 'active' LIMIT 1",
        [$communityID, $subscriberID]
    );
    if ($alreadyMember) {
        return true;
    }

    $planAmount = (float) ($community['monthly_price'] ?? 0);
    if ($planAmount <= 0) {
        return false;
    }

    $planInterval = 'month';
    $planIntervalCount = '1';
    $currentPeriodStart = date('Y-m-d H:i:s');
    $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));

    $adminEarning = ($adminFee * $planAmount) / 100;
    $userNetEarning = $planAmount - $adminEarning;

    $subscriberData = $iN->iN_GetUserDetails($subscriberID);
    $subscriberName = '';
    $subscriberEmail = '';
    if ($subscriberData) {
        $subscriberName = $subscriberData['i_user_fullname'] ?: $subscriberData['i_username'];
        $subscriberEmail = $subscriberData['i_user_email'];
    }

    $transactionId = $payload['subscriptionId'] ?? $defaultTransactionId;
    $inserted = $iN->iN_InsertCommunitySubscription(
        $subscriberID,
        $communityID,
        $ownerID,
        $subscriberName,
        'ccbill',
        $transactionId ?: $orderKey,
        $transactionId ?: $orderKey,
        'ccbill_community_' . $communityID,
        $planAmount,
        $adminEarning,
        $userNetEarning,
        $metadata['currency'] ?? $ccbillCurrency,
        $planInterval,
        $planIntervalCount,
        $subscriberEmail,
        $currentPeriodStart,
        $currentPeriodStart,
        $currentPeriodEnd,
        'active'
    );

    return (bool) $inserted;
}

function ccbill_handle_community_plan_subscription(array $metadata, array $payload, float $adminFee, string $ccbillCurrency, iN_UPDATES $iN, string $orderKey, string $defaultTransactionId): bool
{
    $subscriberID = (int) ($metadata['subscriber_id'] ?? 0);
    if ($subscriberID <= 0) {
        return false;
    }
    $planAmount = (float) ($metadata['amount'] ?? ($payload['subscriptionInitialPrice'] ?? 0));
    if ($planAmount <= 0) {
        return false;
    }
    $planInterval = 'month';
    $planIntervalCount = '1';
    $currentPeriodStart = date('Y-m-d H:i:s');
    $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
    $adminEarning = ($adminFee * $planAmount) / 100;

    $subscriberData = $iN->iN_GetUserDetails($subscriberID);
    $subscriberName = '';
    $subscriberEmail = '';
    if ($subscriberData) {
        $subscriberName = $subscriberData['i_user_fullname'] ?: $subscriberData['i_username'];
        $subscriberEmail = $subscriberData['i_user_email'];
    }

    $transactionId = $payload['subscriptionId'] ?? $defaultTransactionId;
    $inserted = $iN->iN_InsertCommunityPlanSubscription(
        $subscriberID,
        $subscriberName,
        'ccbill',
        $transactionId ?: $orderKey,
        $transactionId ?: $orderKey,
        'ccbill_community_plan',
        $planAmount,
        $adminEarning,
        $metadata['currency'] ?? $ccbillCurrency,
        $planInterval,
        $planIntervalCount,
        $subscriberEmail,
        $currentPeriodStart,
        $currentPeriodStart,
        $currentPeriodEnd,
        'active'
    );

    return (bool) $inserted;
}

function ccbill_handle_subscription(array $metadata, array $payload, float $adminFee, string $ccbillCurrency, iN_UPDATES $iN, string $orderKey, string $defaultTransactionId): bool
{
    $scope = strtolower((string) ($metadata['subscription_scope'] ?? $metadata['scope'] ?? 'profile'));
    if ($scope === 'community') {
        return ccbill_handle_community_subscription($metadata, $payload, $adminFee, $ccbillCurrency, $iN, $orderKey, $defaultTransactionId);
    }
    if ($scope === 'community_plan') {
        return ccbill_handle_community_plan_subscription($metadata, $payload, $adminFee, $ccbillCurrency, $iN, $orderKey, $defaultTransactionId);
    }
    $subscriberID = (int) ($metadata['subscriber_id'] ?? 0);
    $creatorID = (int) ($metadata['creator_id'] ?? 0);
    $planID = (int) ($metadata['plan_id'] ?? 0);
    if ($subscriberID <= 0 || $creatorID <= 0 || $planID <= 0) {
        return false;
    }

    $planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
    if (!$planDetails) {
        return false;
    }

    $planType = $metadata['plan_type'] ?? $planDetails['plan_type'];
    $planAmount = (float) $planDetails['amount'];
    $planInterval = '';
    $planIntervalCount = '1';
    $currentPeriodStart = date('Y-m-d H:i:s');

    switch ($planType) {
        case 'weekly':
            $planInterval = 'week';
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+7 days'));
            break;
        case 'monthly':
            $planInterval = 'month';
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
            break;
        case 'yearly':
            $planInterval = 'year';
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 year'));
            break;
        default:
            $planInterval = 'month';
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+30 days'));
            break;
    }

    $adminEarning = ($adminFee * $planAmount) / 100;
    $userNetEarning = $planAmount - $adminEarning;

    $subscriberData = $iN->iN_GetUserDetails($subscriberID);
    $subscriberName = '';
    $subscriberEmail = '';
    if ($subscriberData) {
        $subscriberName = $subscriberData['i_user_fullname'] ?: $subscriberData['i_username'];
        $subscriberEmail = $subscriberData['i_user_email'];
    }

    $alreadySubscriber = $iN->iN_CheckUserIsInSubscriber($subscriberID, $creatorID);
    $transactionId = $payload['subscriptionId'] ?? $defaultTransactionId;

    if ($alreadySubscriber) {
        return true;
    }

    $inserted = $iN->iN_InsertUserSubscription(
        $subscriberID,
        $creatorID,
        'ccbill',
        $subscriberName,
        $transactionId ?: $orderKey,
        $transactionId ?: $orderKey,
        'ccbill_' . $planID,
        $planAmount,
        $adminEarning,
        $userNetEarning,
        $metadata['currency'] ?? $ccbillCurrency,
        $planInterval,
        $planIntervalCount,
        $subscriberEmail,
        $currentPeriodStart,
        $currentPeriodStart,
        $currentPeriodEnd,
        'active'
    );

    if ($inserted) {
        $iN->iN_InsertNotificationForSubscribe($subscriberID, $creatorID);
    }

    return (bool) $inserted;
}

switch ($eventTypeNormalized) {
    case 'NEWSALESUCCESS':
        if ($type === 'subscription' || $type === 'subscribe') {
            $defaultTxn = $payload['subscriptionId'] ?? ($payload['transactionId'] ?? $orderKey);
            $ok = ccbill_handle_subscription($metadata, $payload, $adminFee, $ccbillCurrency, $iN, $orderKey, $defaultTxn);
            if (!$ok) {
                ccbill_respond(422, 'Unable to persist subscription');
            }
	        } elseif (in_array($type, ['wallet', 'point', 'credit', 'product', 'tips', 'campaign_donate'], true)) {
	            $ok = ccbill_handle_wallet_or_product($type, $orderKey, $metadata, $payload, $adminFee);
            if (!$ok) {
                ccbill_respond(422, 'Unable to persist purchase');
            }
        } elseif ($type === 'agency_boost') {
            $ok = ccbill_handle_agency_boost($orderKey, $metadata, $payload);
            if (!$ok) {
                ccbill_respond(422, 'Unable to persist purchase');
            }
        }
        break;

    case 'RENEWALSUCCESS':
        $subscriptionId = $payload['subscriptionId'] ?? '';
        if ($subscriptionId === '') {
            ccbill_respond(400, 'Missing subscriptionId');
        }
        $row = DB::one("SELECT * FROM i_user_subscriptions WHERE payment_subscription_id = ? LIMIT 1", [$subscriptionId]);
        if ($row) {
            $subscriptionIdInt = (int) $row['subscription_id'];
            $subscriberId = (int) $row['iuid_fk'];
            $creatorId = (int) $row['subscribed_iuid_fk'];
            $interval = $row['plan_interval'] ?? 'month';
            $start = date('Y-m-d H:i:s');
            switch ($interval) {
                case 'week':
                    $end = date('Y-m-d H:i:s', strtotime('+7 days'));
                    break;
                case 'year':
                    $end = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;
                default:
                    $end = date('Y-m-d H:i:s', strtotime('+1 month'));
                    break;
            }
            $currentEnd = $row['plan_period_end'] ?? '';
            $currentEndTs = $currentEnd !== '' ? strtotime($currentEnd) : 0;
            $newEndTs = strtotime($end);
            if ($newEndTs > $currentEndTs) {
                $updated = DB::exec(
                    "UPDATE i_user_subscriptions SET plan_period_start = ?, plan_period_end = ?, status = 'active', finished = '0', in_status = '0' WHERE subscription_id = ?",
                    [$start, $end, $subscriptionIdInt]
                );
                if ($updated > 0) {
                    ccbill_sync_community_membership($row, 'active', $start, $end);
                    $scope = $row['subscription_scope'] ?? 'profile';
                    $userNet = isset($row['user_net_earning']) ? (float) $row['user_net_earning'] : 0;
                    // Credit on renewals only; initial subscription inserts should not credit wallets.
                    if (in_array($scope, ['profile', 'community'], true) && $userNet > 0) {
                        DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [$userNet, $creatorId]);
                    }
                }
            }
        }
        break;

    case 'CANCELLATION':
        $subscriptionId = $payload['subscriptionId'] ?? '';
        if ($subscriptionId === '') {
            ccbill_respond(400, 'Missing subscriptionId');
        }
        $row = DB::one("SELECT * FROM i_user_subscriptions WHERE payment_subscription_id = ? LIMIT 1", [$subscriptionId]);
        if ($row) {
            $subscriberId = (int) $row['iuid_fk'];
            $creatorId = (int) $row['subscribed_iuid_fk'];
            DB::exec("UPDATE i_user_subscriptions SET status = 'declined', finished = '1', in_status = '1' WHERE payment_subscription_id = ?", [$subscriptionId]);
            if (($row['subscription_scope'] ?? 'profile') === 'profile') {
                DB::exec("UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = ? AND fr_two = ?", [$subscriberId, $creatorId]);
            } else if (($row['subscription_scope'] ?? '') === 'community') {
                $endedAt = date('Y-m-d H:i:s');
                ccbill_sync_community_membership($row, 'canceled', null, $endedAt);
            }
        }
        break;

    default:
        // Gracefully ignore unsupported events to avoid retries.
        break;
}

ccbill_respond(200, 'Webhook handled');
