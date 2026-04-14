<?php
// Minimal bootstrap: DB connection + functions only (avoid inc.php output/side effects)
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
if (isset($pdo) && $pdo instanceof PDO) { DB::init($pdo); }
$configRow = DB::one("SELECT fee, default_currency, base_url, oneSignalApi, oneSignalRestApi FROM i_configurations LIMIT 1");
$adminFee = isset($configRow['fee']) ? (float) $configRow['fee'] : 0.0;
$defaultCurrency = isset($configRow['default_currency']) ? (string) $configRow['default_currency'] : 'USD';
$baseUrlConfig = isset($configRow['base_url']) ? (string) $configRow['base_url'] : '';
$oneSignalApi = isset($configRow['oneSignalApi']) ? $configRow['oneSignalApi'] : '';
$oneSignalRestApi = isset($configRow['oneSignalRestApi']) ? $configRow['oneSignalRestApi'] : '';

// Helper to respond and exit
function cp_respond(int $code, ?array $payload = null) {
    http_response_code($code);
    if ($payload !== null) {
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
    exit;
}

// Read raw body and required headers
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') { cp_respond(400, ['error' => 'Empty body']); }
$hmacHeader = $_SERVER['HTTP_HMAC'] ?? '';
$signatureV2 = $_SERVER['HTTP_X_COINPAYMENTS_SIGNATURE'] ?? ($_SERVER['HTTP_COINPAYMENTS_SIGNATURE'] ?? '');
$contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';
$isJsonBody = strpos($contentType, 'application/json') !== false;

// Load CoinPayments IPN secret and merchant id from DB
$ipnSecret = '';
$merchantId = '';
$coinPaymentMode = 'legacy';
$coinPaymentWebhookSecret = '';
$row = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1 LIMIT 1");
if ($row) {
    $ipnSecret = (string)($row['coinpayments_ipn_secret'] ?? '');
    $merchantId = (string)($row['coinpayments_merchand_id'] ?? '');
    $coinPaymentMode = (string)($row['coinpayments_mode'] ?? 'legacy');
    $coinPaymentWebhookSecret = (string)($row['coinpayments_webhook_secret'] ?? '');
}
$isV2Webhook = $coinPaymentMode === 'v2' && ($signatureV2 !== '' || $isJsonBody);

if ($isV2Webhook) {
    $jsonData = json_decode($raw, true);
    if (!is_array($jsonData)) { cp_respond(400, ['error' => 'Invalid JSON']); }
    if ($coinPaymentWebhookSecret === '') { cp_respond(400, ['error' => 'Webhook not configured']); }
    $calcHmac = hash_hmac('sha256', $raw, trim($coinPaymentWebhookSecret));
    if (!hash_equals($calcHmac, $signatureV2)) { cp_respond(403, ['error' => 'Bad signature']); }

    $statusRaw = $jsonData['status'] ?? ($jsonData['payment_status'] ?? ($jsonData['event'] ?? 0));
    $status = 0;
    if (is_numeric($statusRaw)) {
        $status = (int) $statusRaw;
    } else {
        $statusMap = [
            'complete' => 100,
            'completed' => 100,
            'paid' => 100,
            'success' => 100,
            'confirmed' => 100,
            'pending' => 1,
            'processing' => 1,
            'waiting' => 0,
            'failed' => -1,
            'canceled' => -1,
            'cancelled' => -1,
            'expired' => -1,
            'declined' => -1
        ];
        $statusKey = strtolower((string) $statusRaw);
        $status = $statusMap[$statusKey] ?? 0;
    }

    $txnID = $jsonData['txn_id'] ?? ($jsonData['transaction_id'] ?? ($jsonData['id'] ?? ($jsonData['checkout_id'] ?? ($jsonData['order_id'] ?? null))));
    if (!$txnID && isset($jsonData['metadata']['order_id'])) {
        $txnID = $jsonData['metadata']['order_id'];
    }
    if (!$txnID) { cp_respond(400, ['error' => 'Missing txn_id']); }

    $postData = [
        'status' => $status,
        'txn_id' => $txnID,
        'fiat_amount' => $jsonData['fiat_amount'] ?? ($jsonData['amount_fiat'] ?? ($jsonData['amount'] ?? null)),
        'amount' => $jsonData['amount'] ?? null
    ];

    if (isset($jsonData['metadata']) && is_array($jsonData['metadata'])) {
        $postData = array_merge($postData, $jsonData['metadata']);
    }
} else {
    if ($ipnSecret === '' || $merchantId === '') { cp_respond(400, ['error' => 'IPN not configured']); }

    // Parse POST
    parse_str($raw, $postData);
    if (empty($postData)) { $postData = $_POST; }

    // Verify IPN mode
    if (($postData['ipn_mode'] ?? '') !== 'hmac') { cp_respond(400, ['error' => 'Invalid IPN mode']); }

    // Verify merchant
    if (($postData['merchant'] ?? '') !== $merchantId) { cp_respond(403, ['error' => 'Bad merchant']); }

    // Verify HMAC signature (sha512)
    $calcHmac = hash_hmac('sha512', $raw, trim($ipnSecret));
    if (!hash_equals($calcHmac, $hmacHeader)) { cp_respond(403, ['error' => 'Bad HMAC']); }
}

// Process status
$txnID = $postData['txn_id'] ?? null;
if (!$txnID) { cp_respond(400, ['error' => 'Missing txn_id']); }
$status = (int)($postData['status'] ?? 0);

// Instantiate helper for plan lookup
$iN = new iN_UPDATES($db);

// Fetch local payment row
$row = DB::one('SELECT payment_id, payment_status, credit_plan_id, payer_iuid_fk, payed_iuid_fk, payed_profile_id_fk, payed_post_id_fk, payment_type, amount, agency_id_fk, agency_boost_duration_days FROM i_user_payments WHERE order_key = ? LIMIT 1', [$txnID]);
if (!$row) { cp_respond(404, ['error' => 'Order not found']); }

$paymentID     = (int)($row['payment_id'] ?? 0);
$currentStatus = (string)$row['payment_status'];
$creditPlanID  = (int)$row['credit_plan_id'];
$payerUserID   = (int)$row['payer_iuid_fk'];
$receiverId    = (int)($row['payed_iuid_fk'] ?? 0);
$payedPostId   = (int)($row['payed_post_id_fk'] ?? 0);
$paymentType   = (string)($row['payment_type'] ?? '');
$pendingAmount = isset($row['amount']) ? (float)$row['amount'] : 0.0;
// Idempotency: if already ok and status >=100, acknowledge
if ($currentStatus === 'ok' && $status >= 100) { cp_respond(200, ['received' => true]); }

// Use transaction for atomicity
DB::begin();
try {
	    if ($status >= 100 || $status === 2) {
	        // Completed
	        if ($paymentType === 'tips' && $receiverId > 0) {
            $amount = $pendingAmount > 0 ? $pendingAmount : (float)($postData['fiat_amount'] ?? ($postData['amount'] ?? 0));
            $amount = round($amount, 2);
            $split = $iN->iN_CalculateAgencySplit($receiverId, $amount, $adminFee);
            $adminEarning = $split['admin_earning'];
            $userEarning = $split['creator_net'];
            $agencyId = $split['agency_id'];
            $agencyFee = $split['agency_fee'];
            $agencyEarning = $split['agency_earning'];
            $amountString = number_format($amount, 2, '.', '');
            $paymentUpdated = DB::exec(
                "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, amount = ? WHERE order_key = ? AND payment_type = 'tips' AND payment_option = 'coinpayment' AND payment_status <> 'ok'",
                [
                    $agencyId,
                    number_format((float) $adminFee, 2, '.', ''),
                    number_format((float) $agencyFee, 2, '.', ''),
                    number_format((float) $adminEarning, 2, '.', ''),
                    number_format((float) $agencyEarning, 2, '.', ''),
                    number_format((float) $userEarning, 2, '.', ''),
                    $amountString,
                    $txnID
                ]
            );
            if ($paymentUpdated !== 1) {
                DB::rollBack();
                cp_respond(200, ['received' => true]);
            }
            DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $receiverId]);
            if ($paymentID > 0) {
                $iN->iN_AssignInvoiceToPayment($paymentID, $payerUserID, $amount, $defaultCurrency);
            }
            $receiverDetails = $iN->iN_GetuserDetails($receiverId);
            $oneSignalUserDeviceKey = isset($receiverDetails['device_key']) ? $receiverDetails['device_key'] : null;
            if ($oneSignalUserDeviceKey && $oneSignalApi && $oneSignalRestApi) {
                $msgBody = 'You received a tip';
                $msgTitle = 'Tip payment';
                $url = $baseUrlConfig ? $baseUrlConfig . 'settings?tab=dashboard' : '';
                $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
            }
	            if ($payedPostId > 0 && $payerUserID > 0) {
	                $communityMeta = $iN->iN_GetCommunityPostMeta($payedPostId);
	                if (!empty($communityMeta['community_id'])) {
	                    $communityData = $iN->iN_GetCommunityById((int)$communityMeta['community_id']);
	                    $communityOwnerId = $communityData ? (int)($communityData['owner_user_id'] ?? 0) : 0;
	                    if ($communityOwnerId > 0 && $communityData) {
	                        $iN->iN_InsertCommunityNotification($payerUserID, $communityOwnerId, $communityData, 'community_tip', $payedPostId);
	                    }
	                }
	            }
	        } elseif ($paymentType === 'campaign_donate' && $receiverId > 0 && $payedPostId > 0) {
	            $amount = $pendingAmount > 0 ? $pendingAmount : (float)($postData['fiat_amount'] ?? ($postData['amount'] ?? 0));
	            $amount = round($amount, 2);
	            if ($amount <= 0) {
	                DB::rollBack();
	                cp_respond(200, ['received' => true]);
	            }
	            $campaignRow = DB::one(
	                "SELECT campaign_id, owner_uid_fk FROM i_campaigns WHERE post_id_fk = ? LIMIT 1",
	                [$payedPostId]
	            );
	            if (!$campaignRow || (int)($campaignRow['owner_uid_fk'] ?? 0) !== $receiverId) {
	                DB::rollBack();
	                cp_respond(200, ['received' => true]);
	            }
	            $split = $iN->iN_CalculateAgencySplit($receiverId, $amount, $adminFee);
	            $adminEarning = $split['admin_earning'];
	            $userEarning = $split['creator_net'];
	            $agencyId = $split['agency_id'];
	            $agencyFee = $split['agency_fee'];
	            $agencyEarning = $split['agency_earning'];
	            $amountString = number_format($amount, 2, '.', '');
	            $paymentUpdated = DB::exec(
	                "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, amount = ? WHERE order_key = ? AND payment_type = 'campaign_donate' AND payment_option = 'coinpayment' AND payment_status <> 'ok'",
	                [
	                    $agencyId,
	                    number_format((float) $adminFee, 2, '.', ''),
	                    number_format((float) $agencyFee, 2, '.', ''),
	                    number_format((float) $adminEarning, 2, '.', ''),
	                    number_format((float) $agencyEarning, 2, '.', ''),
	                    number_format((float) $userEarning, 2, '.', ''),
	                    $amountString,
	                    $txnID
	                ]
	            );
	            if ($paymentUpdated !== 1) {
	                DB::rollBack();
	                cp_respond(200, ['received' => true]);
	            }
	            DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $receiverId]);
	            DB::exec("UPDATE i_campaigns SET raised_amount = raised_amount + ?, updated_at = ? WHERE post_id_fk = ? LIMIT 1", [$amountString, time(), $payedPostId]);
	            if ($paymentID > 0) {
	                $iN->iN_AssignInvoiceToPayment($paymentID, $payerUserID, $amount, $defaultCurrency);
	            }
	            $iN->iN_InsertNotificationForCampaignDonation($payerUserID, $receiverId, $payedPostId, $amount);
	            $receiverDetails = $iN->iN_GetuserDetails($receiverId);
	            $oneSignalUserDeviceKey = isset($receiverDetails['device_key']) ? $receiverDetails['device_key'] : null;
	            if ($oneSignalUserDeviceKey && $oneSignalApi && $oneSignalRestApi) {
	                $msgBody = $iN->iN_Secure($LANG['campaign_donate_send'] ?? '');
	                $msgTitle = $iN->iN_Secure($LANG['campaign_donate_title'] ?? '');
	                $url = $baseUrlConfig ? $baseUrlConfig . 'notifications' : '';
	                $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
	            }
	        } elseif ($paymentType === 'agency_boost') {
            $amount = $pendingAmount > 0 ? $pendingAmount : (float)($postData['fiat_amount'] ?? ($postData['amount'] ?? 0));
            if ($amount < 0) {
                $amount = 0.0;
            }
            $amount = round($amount, 2);
            $amountString = number_format($amount, 2, '.', '');
            $agencyId = (int) ($row['agency_id_fk'] ?? 0);
            $creatorId = (int) ($row['payed_profile_id_fk'] ?? ($row['payed_iuid_fk'] ?? 0));
            $durationDays = (int) ($row['agency_boost_duration_days'] ?? 0);
            if ($durationDays < 1 || $durationDays > 365) {
                $durationDays = (int) $iN->iN_GetSetting('agency_boost_default_days', 7);
                if ($durationDays < 1) {
                    $durationDays = 1;
                }
                if ($durationDays > 365) {
                    $durationDays = 365;
                }
            }
            $paymentUpdated = DB::exec(
                "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = '0', agency_fee = '0', admin_earning = ?, agency_earning = '0', user_earning = '0', amount = ?, payment_option = 'coinpayment' WHERE order_key = ? AND payment_type = 'agency_boost' AND payment_option = 'coinpayment' AND payment_status <> 'ok'",
                [
                    $agencyId,
                    $amountString,
                    $amountString,
                    $txnID
                ]
            );
            if ($paymentUpdated !== 1) {
                DB::rollBack();
                cp_respond(200, ['received' => true]);
            }
            $boostId = $iN->iN_CreateAgencyBoost($agencyId, $creatorId, $payerUserID, $durationDays, false);
            if (!$boostId) {
                DB::rollBack();
                cp_respond(200, ['received' => true]);
            }
            if ($paymentID > 0) {
                $iN->iN_AssignInvoiceToPayment($paymentID, $payerUserID, $amount, $defaultCurrency);
            }
        } else {
            $planData   = $iN->GetPlanDetails($creditPlanID);
            $planAmount = isset($planData['plan_amount']) ? (float)$planData['plan_amount'] : 0.0;

            $paymentUpdated = DB::exec(
                "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'coinpayment' AND payment_status <> 'ok'",
                [$txnID]
            );

            if ($paymentUpdated > 0) {
                if ($planAmount > 0 && $payerUserID > 0) {
                    $planAmountStr = (string)$planAmount; // wallet_points is varchar in schema
                    DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmountStr, $payerUserID]);
                }
                if ($paymentID > 0) {
                    $fiatAmount = isset($postData['fiat_amount']) ? (float)$postData['fiat_amount'] : (float)($postData['amount'] ?? $planAmount);
                    $iN->iN_AssignInvoiceToPayment($paymentID, $payerUserID, $fiatAmount, $defaultCurrency);
                }
            }
        }
    } elseif ($status < 0) {
        DB::exec("UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ?", [$txnID]);
    } else {
        DB::exec("UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = ?", [$txnID]);
    }

    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    cp_respond(500, ['error' => 'DB error']);
}

cp_respond(200, ['received' => true]);
?>
