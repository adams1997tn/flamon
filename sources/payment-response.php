<?php
require_once 'includes/inc.php';
require_once 'includes/payment/vendor/autoload.php';
if (!defined('INORA_METHODS_CONFIG')) {
	define('INORA_METHODS_CONFIG', realpath('includes/payment/paymentConfig.php'));
}
$payment_time = time();
use App\Components\Payment\BitPayResponse;
use App\Components\Payment\IyzicoResponse;
use App\Components\Payment\PaypalIpnResponse;
use App\Components\Payment\PaytmResponse;
use App\Components\Payment\StripeResponse;
use App\Components\Payment\MercadopagoResponse;
use App\Components\Payment\MonerooResponse;
use App\Components\Payment\NowPaymentsResponse;
use App\Service\PaysafecardService;
use App\Service\YooKassaService;

// Get Config Data
$configData = configItem();
// Get Request Data when payment success or failed
$requestData = $_REQUEST;

if (!function_exists('iN_LogEpochReturn')) {
	function iN_LogEpochReturn(string $message, array $context = []): void
	{
		$logDir = __DIR__ . '/../includes/logs';
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
}

function iN_CompleteTipPayment(string $orderKey, ?float $amountFromGateway = null, string $paymentOption = ''): bool
{
	global $adminFee, $defaultCurrency, $currencys, $iN, $LANG, $base_url, $oneSignalApi, $oneSignalRestApi;
	if (!$orderKey) {
		return false;
	}
	$paymentRow = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
	if (!$paymentRow || ($paymentRow['payment_type'] ?? '') !== 'tips') {
		return false;
	}
	if (isset($paymentRow['payment_status']) && $paymentRow['payment_status'] === 'ok') {
		return true;
	}

	$receiverId = (int) ($paymentRow['payed_iuid_fk'] ?? 0);
	$payerId = (int) ($paymentRow['payer_iuid_fk'] ?? 0);
	$payedPostId = (int) ($paymentRow['payed_post_id_fk'] ?? 0);
	if ($receiverId <= 0) {
		return false;
	}
	$amountFromRow = isset($paymentRow['amount']) ? (float) $paymentRow['amount'] : 0;
	$amount = $amountFromRow > 0 ? $amountFromRow : (float) $amountFromGateway;
	if ($amount <= 0) {
		return false;
	}
	$amount = round($amount, 2);
	$amountString = number_format($amount, 2, '.', '');
	$split = $iN->iN_CalculateAgencySplit($receiverId, $amount, $adminFee);
	$adminEarning = $split['admin_earning'];
	$userEarning = $split['creator_net'];
	$agencyId = $split['agency_id'];
	$agencyFee = $split['agency_fee'];
	$agencyEarning = $split['agency_earning'];
	$feeString = number_format((float) $adminFee, 2, '.', '');
	$agencyFeeString = number_format((float) $agencyFee, 2, '.', '');
	$adminEarningString = number_format((float) $adminEarning, 2, '.', '');
	$agencyEarningString = number_format((float) $agencyEarning, 2, '.', '');
	$userEarningString = number_format((float) $userEarning, 2, '.', '');

	try {
		DB::begin();
		$updated = DB::exec(
			"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, amount = ?, payment_option = ? WHERE order_key = ? AND payment_type = 'tips' AND payment_status <> 'ok'",
			[$agencyId, $feeString, $agencyFeeString, $adminEarningString, $agencyEarningString, $userEarningString, $amountString, (string) $paymentOption, $orderKey]
		);
		if ($updated !== 1) {
			DB::rollBack();
			return true;
		}
		DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [$userEarningString, $receiverId]);
		$paymentRowUpdated = DB::one("SELECT payment_id FROM i_user_payments WHERE order_key = ? AND payment_type = 'tips' LIMIT 1", [$orderKey]);
		$paymentId = (int) ($paymentRowUpdated['payment_id'] ?? 0);
		if ($paymentId > 0) {
			$iN->iN_AssignInvoiceToPayment($paymentId, $payerId, $amount, $defaultCurrency);
		}
		DB::commit();
	} catch (Throwable $th) {
		DB::rollBack();
		return false;
	}
	if ($payedPostId > 0 && $payerId > 0) {
		$communityMeta = $iN->iN_GetCommunityPostMeta($payedPostId);
		if (!empty($communityMeta['community_id'])) {
			$communityData = $iN->iN_GetCommunityById((int)$communityMeta['community_id']);
			$communityOwnerId = $communityData ? (int)($communityData['owner_user_id'] ?? 0) : 0;
			if ($communityOwnerId > 0 && $communityData) {
				$iN->iN_InsertCommunityNotification($payerId, $communityOwnerId, $communityData, 'community_tip', $payedPostId);
			}
		}
	}

	$userDeviceKey = $iN->iN_GetuserDetails($receiverId);
	$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : null;
	if ($oneSignalUserDeviceKey) {
		$msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		$msgTitle = $iN->iN_Secure($LANG['tip_earning']) . ' ' . formatCurrency($userEarning, $defaultCurrency);
		$url = $base_url . 'settings?tab=dashboard';
		$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
	}

	return true;
}

function iN_FailTipPayment(string $orderKey, bool $delete = false): void
{
	if (!$orderKey) {
		return;
	}
	if ($delete) {
		DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type = 'tips'", [$orderKey]);
		return;
	}
	DB::exec("UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ? AND payment_type = 'tips'", [$orderKey]);
}

function iN_CompleteCampaignDonationPayment(string $orderKey, ?float $amountFromGateway = null, string $paymentOption = ''): bool
{
	global $adminFee, $defaultCurrency, $iN, $LANG, $base_url, $oneSignalApi, $oneSignalRestApi;
	if ($orderKey === '') {
		return false;
	}
	$paymentRow = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
	if (!$paymentRow || ($paymentRow['payment_type'] ?? '') !== 'campaign_donate') {
		return false;
	}
	if (isset($paymentRow['payment_status']) && $paymentRow['payment_status'] === 'ok') {
		return true;
	}

	$receiverId = (int) ($paymentRow['payed_iuid_fk'] ?? 0);
	$payerId = (int) ($paymentRow['payer_iuid_fk'] ?? 0);
	$payedPostId = (int) ($paymentRow['payed_post_id_fk'] ?? 0);
	if ($receiverId <= 0 || $payerId <= 0 || $payedPostId <= 0) {
		return false;
	}
	$campaignRow = DB::one(
		"SELECT campaign_id, owner_uid_fk FROM i_campaigns WHERE post_id_fk = ? LIMIT 1",
		[$payedPostId]
	);
	if (!$campaignRow || (int)($campaignRow['owner_uid_fk'] ?? 0) !== $receiverId) {
		return false;
	}

	$amountFromRow = isset($paymentRow['amount']) ? (float)$paymentRow['amount'] : 0.0;
	$amount = $amountFromRow > 0 ? $amountFromRow : (float)$amountFromGateway;
	if ($amount <= 0) {
		return false;
	}
	$amount = round($amount, 2);
	$amountString = number_format($amount, 2, '.', '');
	$split = $iN->iN_CalculateAgencySplit($receiverId, $amount, $adminFee);
	$adminEarning = $split['admin_earning'];
	$userEarning = $split['creator_net'];
	$agencyId = $split['agency_id'];
	$agencyFee = $split['agency_fee'];
	$agencyEarning = $split['agency_earning'];

	try {
		DB::begin();
		$updated = DB::exec(
			"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, amount = ?, payment_option = ? WHERE order_key = ? AND payment_type = 'campaign_donate' AND payment_status <> 'ok'",
			[
				$agencyId,
				number_format((float)$adminFee, 2, '.', ''),
				number_format((float)$agencyFee, 2, '.', ''),
				number_format((float)$adminEarning, 2, '.', ''),
				number_format((float)$agencyEarning, 2, '.', ''),
				number_format((float)$userEarning, 2, '.', ''),
				$amountString,
				(string)$paymentOption,
				$orderKey
			]
		);
		if ($updated !== 1) {
			DB::rollBack();
			return true;
		}
		DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float)$userEarning, 2, '.', ''), $receiverId]);
		DB::exec("UPDATE i_campaigns SET raised_amount = raised_amount + ?, updated_at = ? WHERE post_id_fk = ? LIMIT 1", [$amountString, time(), $payedPostId]);
		$paymentRowUpdated = DB::one("SELECT payment_id FROM i_user_payments WHERE order_key = ? AND payment_type = 'campaign_donate' LIMIT 1", [$orderKey]);
		$paymentId = (int)($paymentRowUpdated['payment_id'] ?? 0);
		if ($paymentId > 0) {
			$iN->iN_AssignInvoiceToPayment($paymentId, $payerId, $amount, $defaultCurrency);
		}
		DB::commit();
	} catch (Throwable $th) {
		DB::rollBack();
		return false;
	}

	$iN->iN_InsertNotificationForCampaignDonation($payerId, $receiverId, $payedPostId, $amount);
	$userDeviceKey = $iN->iN_GetuserDetails($receiverId);
	$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : null;
	if ($oneSignalUserDeviceKey) {
		$msgBody = $iN->iN_Secure($LANG['campaign_donate_send'] ?? '');
		$msgTitle = $iN->iN_Secure($LANG['campaign_donate_title'] ?? '');
		$url = $base_url . 'notifications';
		$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
	}

	return true;
}

function iN_FailCampaignDonationPayment(string $orderKey, bool $delete = false): void
{
	if ($orderKey === '') {
		return;
	}
	if ($delete) {
		DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type = 'campaign_donate'", [$orderKey]);
		return;
	}
	DB::exec("UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ? AND payment_type = 'campaign_donate'", [$orderKey]);
}

function iN_CompleteAgencyBoostPayment(string $orderKey, ?float $amountFromGateway = null, string $paymentOption = ''): bool
{
	global $iN, $defaultCurrency, $LANG;
	if (!$orderKey) {
		return false;
	}
	$paymentRow = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
	if (!$paymentRow || ($paymentRow['payment_type'] ?? '') !== 'agency_boost') {
		return false;
	}
	if (isset($paymentRow['payment_status']) && $paymentRow['payment_status'] === 'ok') {
		return true;
	}
	$agencyId = (int) ($paymentRow['agency_id_fk'] ?? 0);
	$creatorId = (int) ($paymentRow['payed_profile_id_fk'] ?? ($paymentRow['payed_iuid_fk'] ?? 0));
	$payerId = (int) ($paymentRow['payer_iuid_fk'] ?? 0);
	if ($agencyId <= 0 || $creatorId <= 0 || $payerId <= 0) {
		return false;
	}
	$durationDays = (int) ($paymentRow['agency_boost_duration_days'] ?? 0);
	if ($durationDays < 1 || $durationDays > 365) {
		$durationDays = (int) $iN->iN_GetSetting('agency_boost_default_days', 7);
		if ($durationDays < 1) {
			$durationDays = 1;
		}
		if ($durationDays > 365) {
			$durationDays = 365;
		}
	}
	$amountFromRow = isset($paymentRow['amount']) ? (float) $paymentRow['amount'] : 0.0;
	$amount = $amountFromRow > 0 ? $amountFromRow : (float) $amountFromGateway;
	if ($amount < 0) {
		$amount = 0.0;
	}
	$amount = round($amount, 2);
	$amountString = number_format($amount, 2, '.', '');

	try {
		DB::begin();
		$updated = DB::exec(
			"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = ?, fee = '0', agency_fee = '0', admin_earning = ?, agency_earning = '0', user_earning = '0', amount = ?, payment_option = ? WHERE order_key = ? AND payment_type = 'agency_boost' AND payment_status <> 'ok'",
			[(int) $agencyId, $amountString, $amountString, (string) $paymentOption, $orderKey]
		);
		if ($updated !== 1) {
			DB::rollBack();
			return true;
		}
		$iN->iN_CreateAgencyBoost($agencyId, $creatorId, $payerId, $durationDays, false);
		DB::commit();
	} catch (Throwable $th) {
		DB::rollBack();
		return false;
	}

	$paymentId = (int) ($paymentRow['payment_id'] ?? 0);
	if ($paymentId > 0) {
		$iN->iN_AssignInvoiceToPayment($paymentId, $payerId, $amount, $defaultCurrency);
	}
	return true;
}

function iN_FailAgencyBoostPayment(string $orderKey, bool $delete = false): void
{
	if (!$orderKey) {
		return;
	}
	if ($delete) {
		DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type = 'agency_boost'", [$orderKey]);
		return;
	}
	DB::exec("UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ? AND payment_type = 'agency_boost'", [$orderKey]);
}

function iN_CompleteIyzicoSubscriptionPayment(string $orderKey, array $rawResult): bool
{
	global $iN, $defaultCurrency;
	if ($orderKey === '') {
		return false;
	}

	$paymentRow = DB::one(
		"SELECT * FROM i_user_payments WHERE order_key = ? AND payment_type = 'subscription' AND payment_option = 'iyzico' LIMIT 1",
		[$orderKey]
	);
	if (!$paymentRow) {
		return false;
	}
	if (($paymentRow['payment_status'] ?? '') === 'ok') {
		return true;
	}

	$intent = DB::one(
		"SELECT * FROM i_user_subscription_intents WHERE order_key = ? AND payment_option = 'iyzico' LIMIT 1",
		[$orderKey]
	);
	if (!$intent) {
		return false;
	}

	$gatewayAmount = isset($rawResult['price']) ? (float)$rawResult['price'] : (float)($paymentRow['amount'] ?? 0);
	$intentAmount = (float)($intent['plan_amount'] ?? 0);
	if ($intentAmount > 0 && $gatewayAmount > 0 && round($intentAmount, 2) !== round($gatewayAmount, 2)) {
		return false;
	}
	$resolvedAmount = $intentAmount > 0 ? $intentAmount : $gatewayAmount;
	if ($resolvedAmount <= 0) {
		$resolvedAmount = (float)($paymentRow['amount'] ?? 0);
	}
	if ($resolvedAmount <= 0) {
		return false;
	}
	$amountFormatted = number_format($resolvedAmount, 2, '.', '');

	$planCurrency = strtoupper((string)($intent['plan_amount_currency'] ?? ($paymentRow['currency'] ?? $defaultCurrency)));
	if ($planCurrency === '') {
		$planCurrency = strtoupper((string)$defaultCurrency);
	}
	if ($planCurrency === '') {
		$planCurrency = 'USD';
	}

	$scope = (string)($intent['subscription_scope'] ?? 'profile');
	$subscriberId = (int)($intent['iuid_fk'] ?? 0);
	$subscribedId = (int)($intent['subscribed_iuid_fk'] ?? 0);
	$subscriberName = (string)($intent['subscriber_name'] ?? '');
	$subscriberEmail = (string)($intent['subscriber_email'] ?? '');
	$subscriptionRefId = (int)($intent['subscription_ref_id'] ?? 0);

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

	$paymentReference = (string)($rawResult['paymentId'] ?? ($rawResult['conversationId'] ?? $orderKey));
	$customerReference = (string)($rawResult['paymentId'] ?? $orderKey);
	if ($paymentReference === '') {
		$paymentReference = $orderKey;
	}
	if ($customerReference === '') {
		$customerReference = $paymentReference;
	}

	$now = date('Y-m-d H:i:s');
	$activated = false;

	if ($scope === 'community') {
		$communityId = $subscriptionRefId;
		if ($subscriberId > 0 && $communityId > 0) {
			$alreadyMember = (bool) DB::col(
				"SELECT 1 FROM community_memberships WHERE community_id = ? AND user_id = ? AND status = 'active' LIMIT 1",
				[$communityId, $subscriberId]
			);
			if ($alreadyMember) {
				$activated = true;
			} else {
				$communityData = $iN->iN_GetCommunityById($communityId);
				if ($communityData && (string)($communityData['status'] ?? '') === 'active') {
					$ownerId = (int)($communityData['owner_user_id'] ?? $subscribedId);
					$periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
					$result = $iN->iN_InsertCommunitySubscription(
						$subscriberId,
						$communityId,
						$ownerId,
						$subscriberName,
						'iyzico',
						$paymentReference,
						$customerReference,
						'iyzico_community_' . $communityId,
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
					$activated = (bool)$result;
				}
			}
		}
	} else if ($scope === 'community_plan') {
		if ($subscriberId > 0) {
			$hasPlan = $iN->iN_HasActiveCommunityPlan($subscriberId);
			if ($hasPlan) {
				$activated = true;
			} else {
				$periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
				$result = $iN->iN_InsertCommunityPlanSubscription(
					$subscriberId,
					$subscriberName,
					'iyzico',
					$paymentReference,
					$customerReference,
					'iyzico_community_plan',
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
				$activated = (bool)$result;
			}
		}
	} else {
		$planId = (int)($intent['plan_id'] ?? 0);
		if ($planId > 0 && $subscriberId > 0 && $subscribedId > 0) {
			$alreadySubscriber = $iN->iN_CheckUserIsInSubscriber($subscriberId, $subscribedId);
			if ($alreadySubscriber) {
				$activated = true;
			} else {
				$planDetails = $iN->iN_CheckPlanExist($planId, $subscribedId);
				if ($planDetails) {
					$planType = (string)($planDetails['plan_type'] ?? 'monthly');
					$planInterval = 'month';
					$planIntervalCount = 1;
					$periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
					if ($planType === 'weekly') {
						$planInterval = 'week';
						$periodEnd = date('Y-m-d H:i:s', strtotime('+7 days'));
					} else if ($planType === 'yearly') {
						$planInterval = 'year';
						$periodEnd = date('Y-m-d H:i:s', strtotime('+1 year'));
					}
					$result = $iN->iN_InsertUserSubscription(
						$subscriberId,
						$subscribedId,
						'iyzico',
						$subscriberName,
						$paymentReference,
						$customerReference,
						'iyzico_' . $planId,
						$amountFormatted,
						0,
						0,
						$planCurrency,
						$planInterval,
						$planIntervalCount,
						$subscriberEmail,
						$now,
						$now,
						$periodEnd,
						'active'
					);
					if ($result) {
						$iN->iN_InsertNotificationForSubscribe($subscriberId, $subscribedId);
					}
					$activated = (bool)$result;
				}
			}
		}
	}

	if (!$activated) {
		DB::exec(
			"UPDATE i_user_subscription_intents SET status = 'declined', updated_at = ? WHERE order_key = ? AND payment_option = 'iyzico'",
			[(int)time(), $orderKey]
		);
		DB::exec(
			"UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ? AND payment_type = 'subscription' AND payment_option = 'iyzico' AND payment_status <> 'ok'",
			[$orderKey]
		);
		return false;
	}

	DB::exec(
		"UPDATE i_user_subscription_intents SET status = 'ok', updated_at = ? WHERE order_key = ? AND payment_option = 'iyzico'",
		[(int)time(), $orderKey]
	);
	DB::exec(
		"UPDATE i_user_payments SET payment_status = 'ok', amount = ?, currency = ? WHERE order_key = ? AND payment_type = 'subscription' AND payment_option = 'iyzico' AND payment_status <> 'ok'",
		[$amountFormatted, $planCurrency, $orderKey]
	);

	$paymentId = (int)($paymentRow['payment_id'] ?? 0);
	if ($paymentId > 0 && $subscriberId > 0) {
		$iN->iN_AssignInvoiceToPayment($paymentId, $subscriberId, (float)$amountFormatted, $planCurrency);
	}

	return true;
}

// Check payment Method is paytm
if ($requestData['paymentOption'] == 'paytm') {
	// Get Payment Response instance
	$paytmResponse = new PaytmResponse();

	// Fetch payment data using payment response instance
	$paytmData = $paytmResponse->getPaytmPaymentData($requestData);

	// Check if payment status is success
	if ($paytmData['STATUS'] == 'TXN_SUCCESS') {

		// Create payment success response data.
		$paymentResponseData = [
			'status' => true,
			'rawData' => $paytmData,
			'data' => preparePaymentData($paytmData['ORDERID'], $paytmData['TXNAMOUNT'], $paytmData['TXNID'], 'paytm'),
		];
		// Send data to payment response.
		paymentResponse($paymentResponseData);
	} else {
		// Create payment failed response data.
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paytmData,
			'data' => preparePaymentData($paytmData['ORDERID'], $paytmData['TXNAMOUNT'], $paytmData['TXNID'], 'paytm'),
		];
		// Send data to payment response function
		paymentResponse($paymentResponseData);
	}
// Check payment method is instamojo
} else if ($requestData['paymentOption'] == 'iyzico') {

	// Check if payment status is success for iyzico.
	if ($_REQUEST['status'] == 'success') {
		// Get iyzico response.
		$iyzicoResponse = new IyzicoResponse();

		// fetch payment data using iyzico response instance.
		$iyzicoData = $iyzicoResponse->getIyzicoPaymentData($requestData);
		$rawResult = json_decode($iyzicoData->getRawResult(), true);

		// Check if iyzico payment data is success
		// Then create a array for success data
		if ($iyzicoData->getStatus() == 'success') {
			$paymentResponseData = [
				'status' => true,
				'rawData' => (array) $iyzicoData,
				'data' => preparePaymentData($requestData['orderId'], $rawResult['price'], $rawResult['conversationId'], 'iyzico'),
			];
			$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$requestData['orderId']]);
			$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
			$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
			$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
			if ($pData && ($pData['payment_type'] ?? '') === 'subscription') {
				$subscriptionCompleted = iN_CompleteIyzicoSubscriptionPayment((string)$requestData['orderId'], is_array($rawResult) ? $rawResult : []);
				if (!$subscriptionCompleted) {
					$paymentResponseData['status'] = false;
				}
			} else if(!empty($userPayedPlanID)){
				$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
				$planAmount = $pAData['plan_amount'];
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'iyzico' AND payment_status <> 'ok'",
					[$requestData['orderId']]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
				}
			}else if(!empty($productID)){
				$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
				$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
				$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
				$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
				$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
				$adminEarning = $split['admin_earning'];
				$userEarning = $split['creator_net'];
				$agencyId = $split['agency_id'];
				$agencyFee = $split['agency_fee'];
				$agencyEarning = $split['agency_earning'];
				$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'iyzico' AND payment_status <> 'ok'",
				    [
						$productOwnerID,
						$agencyId,
						$amountString,
						number_format((float) $adminFee, 2, '.', ''),
						number_format((float) $agencyFee, 2, '.', ''),
						number_format((float) $adminEarning, 2, '.', ''),
						number_format((float) $agencyEarning, 2, '.', ''),
						number_format((float) $userEarning, 2, '.', ''),
						$payerUserID,
						$requestData['orderId']
					]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
				}
			}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
				iN_CompleteTipPayment($requestData['orderId'], (float) $rawResult['price'], 'iyzico');
			}
			if (!$pData || ($pData['payment_type'] ?? '') !== 'subscription') {
				iN_CompleteAgencyBoostPayment($requestData['orderId'], (float) $rawResult['price'], 'iyzico');
			}
			// Send data to payment response
			paymentResponse($paymentResponseData);
			// If payment failed then create data for failed
		} else {
			$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$requestData['orderId']]);
			if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
				iN_FailTipPayment($requestData['orderId']);
			} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
				iN_FailAgencyBoostPayment($requestData['orderId']);
			} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'subscription') {
				DB::exec("UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ? AND payment_option = 'iyzico'", [$requestData['orderId']]);
				DB::exec("UPDATE i_user_subscription_intents SET status = 'declined', updated_at = ? WHERE order_key = ? AND payment_option = 'iyzico'", [time(), $requestData['orderId']]);
			} else {
				DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$requestData['orderId']]);
			}
			// Prepare failed payment data
			$paymentResponseData = [
				'status' => false,
				'rawData' => (array) $iyzicoData,
				'data' => preparePaymentData($requestData['orderId'], $rawResult['price'], $rawResult['conversationId'], 'iyzico'),
			];
			// Send data to payment response
			paymentResponse($paymentResponseData);
		}
		// Check before 3d payment process payment failed
	} else {
		// Prepare failed payment data
		$failedPrice = isset($rawResult['price']) ? $rawResult['price'] : null;
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($requestData['orderId'], $failedPrice, null, 'iyzico'),
		];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$requestData['orderId']]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'subscription') {
			DB::exec("UPDATE i_user_payments SET payment_status = 'declined' WHERE order_key = ? AND payment_option = 'iyzico'", [$requestData['orderId']]);
			DB::exec("UPDATE i_user_subscription_intents SET status = 'declined', updated_at = ? WHERE order_key = ? AND payment_option = 'iyzico'", [time(), $requestData['orderId']]);
		} else {
			iN_FailAgencyBoostPayment($requestData['orderId']);
		}
		// Send data to process response
		paymentResponse($paymentResponseData);
	}

// Check Paypal payment process
} else if ($requestData['paymentOption'] == 'paypal') {
	// Get instance of paypal
	$paypalIpnResponse = new PaypalIpnResponse();

	// fetch paypal payment data
	$paypalIpnData = $paypalIpnResponse->getPaypalPaymentData();
	$rawData = json_decode($paypalIpnData, true);
	// Note : IPN and redirects will come here
	// Check if payment status exist and it is success
	if (isset($requestData['PayerID'])) {

		// Then create a data for success paypal data
		$paymentResponseData = [
			'status' => true,
			'rawData' => (array) $paypalIpnData,
			'data' => preparePaymentData($rawData['invoice'], $rawData['payment_gross'], $rawData['txn_id'], 'paypal'),
		];
		// Send data to payment response function for further process
		paymentResponse($paymentResponseData);
		$pData = DB::one("SELECT * FROM i_user_payments WHERE payment_type IN('point','product','tips') AND payment_status = 'pending' AND payment_option = 'paypal' AND payer_iuid_fk = ?", [$userID]);
		$orderKey = $rawData['invoice'] ?? null;
		if ($pData) {
			$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
			$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
			$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
			$orderKey = $pData['order_key'] ?? $orderKey;
			if(!empty($userPayedPlanID)){
				$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
				$planAmount = $pAData['plan_amount'];
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'paypal' AND payment_status <> 'ok'",
					[$orderKey]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $userID]);
				}
			}else if(!empty($productID)){
				$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
				$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
				$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
				$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
				$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
				$adminEarning = $split['admin_earning'];
				$userEarning = $split['creator_net'];
				$agencyId = $split['agency_id'];
				$agencyFee = $split['agency_fee'];
				$agencyEarning = $split['agency_earning'];
				$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_status = 'pending' AND payment_option = 'paypal'",
				    [
						$productOwnerID,
						$agencyId,
						$amountString,
						number_format((float) $adminFee, 2, '.', ''),
						number_format((float) $agencyFee, 2, '.', ''),
						number_format((float) $adminEarning, 2, '.', ''),
						number_format((float) $agencyEarning, 2, '.', ''),
						number_format((float) $userEarning, 2, '.', ''),
						$payerUserID,
						$orderKey
					]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
				}
			}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
				iN_CompleteTipPayment($pData['order_key'], (float) $rawData['payment_gross'], 'paypal');
			}
		}
		if (!empty($orderKey)) {
			iN_CompleteAgencyBoostPayment($orderKey, (float) $rawData['payment_gross'], 'paypal');
		}
		// Check if payment not successfull
	} else {
		iN_FailTipPayment($rawData['invoice'] ?? '');
		iN_FailAgencyBoostPayment($rawData['invoice'] ?? '');
		DB::exec("DELETE FROM i_user_payments WHERE payer_iuid_fk = ? AND payment_option = 'paypal' AND payment_type IN('point','product','tips') AND payment_status = 'pending'", [$userID]);
		// Prepare payment failed data
		$paymentResponseData = [
			'status' => false,
			'rawData' => [],
			'data' => preparePaymentData($rawData['invoice'], $rawData['payment_gross'], null, 'paypal'),
		];
		// Send data to payment response function for further process
		paymentResponse($paymentResponseData);
	}

// Check Paystack payment process
} else if ($requestData['paymentOption'] == 'paystack') {

	$requestData = json_decode($requestData['response'], true);

	// Check if status key exists and payment is successfully completed
	if (isset($requestData['status']) and $requestData['status'] == "success") {
		// Create data for payment success
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($requestData['data']['reference'], $requestData['data']['amount'], $requestData['data']['reference'], 'paystack'),
		];
		$orderKey = isset($requestData['data']['reference']) ? (string) $requestData['data']['reference'] : '';
		$pData = null;
		if ($orderKey !== '') {
			$pData = DB::one(
				"SELECT * FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ? AND payment_status = 'pending' AND payment_option = 'paystack' AND payment_type IN('point','product','tips')",
				[$orderKey, $userID]
			);
		}
		$amountFromGateway = isset($requestData['data']['amount']) ? (float) $requestData['data']['amount'] / 100 : null;
		if ($pData) {
			$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
			$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
			$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
			$orderKey = $pData['order_key'] ?? null;
			if(!empty($userPayedPlanID)){
				$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
				$planAmount = $pAData['plan_amount'];
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'paystack' AND payment_status <> 'ok'",
					[$orderKey]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
				}
			}else if(!empty($productID)){
				$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
				$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
				$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
				$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
				$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
	            $adminEarning = $split['admin_earning'];
	            $userEarning = $split['creator_net'];
	            $agencyId = $split['agency_id'];
	            $agencyFee = $split['agency_fee'];
	            $agencyEarning = $split['agency_earning'];
				$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_status = 'pending' AND payment_option = 'paystack' AND payment_type = 'product'",
				    [
						$productOwnerID,
						$agencyId,
						$amountString,
						number_format((float) $adminFee, 2, '.', ''),
						number_format((float) $agencyFee, 2, '.', ''),
						number_format((float) $adminEarning, 2, '.', ''),
						number_format((float) $agencyEarning, 2, '.', ''),
						number_format((float) $userEarning, 2, '.', ''),
						$payerUserID,
						$orderKey
					]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
				}
			}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
				iN_CompleteTipPayment($pData['order_key'], $amountFromGateway, 'paystack');
			}
		}
		if (!empty($orderKey)) {
			iN_CompleteAgencyBoostPayment($orderKey, $amountFromGateway, 'paystack');
		}
		// Send data to payment response for further process
		paymentResponse($paymentResponseData);
		// If paystack payment is failed
	} else {
		// Prepare data for failed payment
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($requestData['data']['reference'], $requestData['data']['amount'], $requestData['data']['reference'], 'paystack'),
		];
		$referenceKey = isset($requestData['data']['reference']) ? $requestData['data']['reference'] : '';
		iN_FailTipPayment($referenceKey);
		iN_FailAgencyBoostPayment($referenceKey);
		DB::exec("DELETE FROM i_user_payments WHERE payer_iuid_fk = ? AND payment_option = 'paystack' AND payment_type IN('point','product','tips') AND payment_status = 'pending'", [$userID]);
		// Send data to payment response to further process
		paymentResponse($paymentResponseData);
	}

// Check Flutterwave payment process
} else if ($requestData['paymentOption'] == 'flutterwave') {

	$flutterwaveResponse = json_decode($requestData['response'], true);
	$orderId = isset($requestData['orderId']) ? $requestData['orderId'] : ($requestData['order_id'] ?? '');
	$flutterwaveData = isset($flutterwaveResponse['data']) ? $flutterwaveResponse['data'] : [];
	$amount = isset($flutterwaveData['amount']) ? $flutterwaveData['amount'] : ($requestData['amount'] ?? 0);
	$transactionId = isset($flutterwaveData['id']) ? $flutterwaveData['id'] : ($flutterwaveData['tx_ref'] ?? $orderId);

	if (isset($flutterwaveResponse['status']) && $flutterwaveResponse['status'] === true) {
		$paymentResponseData = [
			'status' => true,
			'rawData' => $flutterwaveResponse,
			'data' => preparePaymentData($orderId, $amount, $transactionId, 'flutterwave'),
		];
		$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderId]);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		$amountFromStripe = isset($stripeData->amount) ? ((float) $stripeData->amount / 100) : null;
		if(!empty($userPayedPlanID)){
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'flutterwave' AND payment_status <> 'ok'",
				[$orderId]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
			}
		}else if(!empty($productID)){
			$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
			$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
			$adminEarning = $split['admin_earning'];
			$userEarning = $split['creator_net'];
			$agencyId = $split['agency_id'];
			$agencyFee = $split['agency_fee'];
			$agencyEarning = $split['agency_earning'];
			$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE order_key = ? AND payment_type = 'product' AND payment_option = 'flutterwave' AND payment_status <> 'ok'",
			    [
					$productOwnerID,
					$agencyId,
					$amountString,
					number_format((float) $adminFee, 2, '.', ''),
					number_format((float) $agencyFee, 2, '.', ''),
					number_format((float) $adminEarning, 2, '.', ''),
					number_format((float) $agencyEarning, 2, '.', ''),
					number_format((float) $userEarning, 2, '.', ''),
					$orderId
				]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
			}
		}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			iN_CompleteTipPayment($orderId, (float) $amount, 'flutterwave');
		}
		iN_CompleteAgencyBoostPayment($orderId, (float) $amount, 'flutterwave');
		paymentResponse($paymentResponseData);
	} else {
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderId]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($orderId);
		} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		}
		$paymentResponseData = [
			'status' => false,
			'rawData' => $flutterwaveResponse,
			'data' => preparePaymentData($orderId, $amount, $transactionId, 'flutterwave'),
		];
		paymentResponse($paymentResponseData);
	}

// Check Stripe payment process
} else if ($requestData['paymentOption'] == 'stripe') {

	$stripeResponse = new StripeResponse();

	$stripeData = $stripeResponse->retrieveStripePaymentData($requestData['stripe_session_id']);

	// Check if payment charge status key exist in stripe data and it success
	if (isset($stripeData['status']) and $stripeData['status'] == "succeeded") {
		// Prepare data for success
		$paymentResponseData = [
			'status' => true,
			'rawData' => $stripeData,
			'data' => preparePaymentData($stripeData->charges->data[0]['balance_transaction'], $stripeData->amount, $stripeData->charges->data[0]['balance_transaction'], 'stripe'),
		];
		$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$requestData['orderId']]);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		$amountFromStripe = isset($stripeData->amount) ? ((float) $stripeData->amount / 100) : null;
		if(!empty($userPayedPlanID)){
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'point' AND payment_option = 'stripe' AND payment_status <> 'ok'",
				[$payerUserID, $requestData['orderId']]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
			}
		}else if(!empty($productID)){
			$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
			$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
            $adminEarning = $split['admin_earning'];
            $userEarning = $split['creator_net'];
            $agencyId = $split['agency_id'];
            $agencyFee = $split['agency_fee'];
            $agencyEarning = $split['agency_earning'];
			$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'stripe' AND payment_status <> 'ok'",
			    [
					$productOwnerID,
					$agencyId,
					$amountString,
					number_format((float) $adminFee, 2, '.', ''),
					number_format((float) $agencyFee, 2, '.', ''),
					number_format((float) $adminEarning, 2, '.', ''),
					number_format((float) $agencyEarning, 2, '.', ''),
					number_format((float) $userEarning, 2, '.', ''),
					$payerUserID,
					$requestData['orderId']
				]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
			}
		}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			iN_CompleteTipPayment($requestData['orderId'], $amountFromStripe, 'stripe');
		}
		iN_CompleteAgencyBoostPayment($requestData['orderId'], $amountFromStripe, 'stripe');

		// Check if stripe data is failed
	} else {
		// Prepare failed payment data
		$paymentResponseData = [
			'status' => false,
			'rawData' => $stripeData,
			'data' => preparePaymentData($requestData['orderId'], $stripeData->amount, null, 'stripe'),
		];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$requestData['orderId']]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($requestData['orderId']);
		} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($requestData['orderId']);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$requestData['orderId']]);
		}
	}
	// Send data to payment response for further process
	paymentResponse($paymentResponseData);

// Check Razorpay payment process
} else if ($requestData['paymentOption'] == 'razorpay') {
	$orderId = $requestData['orderId'];

	$requestData = json_decode($requestData['response'], true);

	// Check if razorpay status exist and status is success
	if (isset($requestData['status']) and $requestData['status'] == 'captured') {
		// prepare payment data
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['id'], 'razorpay'),
		];
		$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderId]);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		$gatewayAmount = isset($requestData['amount']) ? (float) $requestData['amount'] / 100 : null;
		if(!empty($userPayedPlanID)){
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'point' AND payment_option = 'razorpay' AND payment_status <> 'ok'",
				[$payerUserID, $orderId]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
			}
		}else if(!empty($productID)){
		$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
		$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
		$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
		$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
		$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
		$adminEarning = $split['admin_earning'];
		$userEarning = $split['creator_net'];
		$agencyId = $split['agency_id'];
		$agencyFee = $split['agency_fee'];
		$agencyEarning = $split['agency_earning'];
		$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
		$paymentUpdated = DB::exec(
			"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'razorpay' AND payment_status <> 'ok'",
		    [
				$productOwnerID,
				$agencyId,
				$amountString,
				number_format((float) $adminFee, 2, '.', ''),
				number_format((float) $agencyFee, 2, '.', ''),
				number_format((float) $adminEarning, 2, '.', ''),
				number_format((float) $agencyEarning, 2, '.', ''),
				number_format((float) $userEarning, 2, '.', ''),
				$payerUserID,
				$orderId
			]
		);
		if ($paymentUpdated > 0) {
			DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
		}
		}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			iN_CompleteTipPayment($orderId, $gatewayAmount, 'razorpay');
		}
		iN_CompleteAgencyBoostPayment($orderId, $gatewayAmount, 'razorpay');
		// send data to payment response
		paymentResponse($paymentResponseData);
		// razorpay status is failed
	} else {
		// prepare payment data for failed payment
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['id'], 'razorpay'),
		];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderId]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($orderId);
		} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		}
		// send data to payment response
		paymentResponse($paymentResponseData);
	}
} else if ($requestData['paymentOption'] == 'authorize-net') {
	$orderId = $requestData['order_id'];

	$requestData = json_decode($requestData['response'], true);

	// Check if razorpay status exist and status is success
	if (isset($requestData['status']) and $requestData['status'] == 'success') {
		// prepare payment data
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['transaction_id'], 'authorize-net'),
		];
		$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$requestData['order_id']]);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		$gatewayAmount = isset($requestData['amount']) ? (float) $requestData['amount'] : null;
		if(!empty($userPayedPlanID)){
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'authorize-net' AND payment_status <> 'ok'",
				[$requestData['order_id']]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
			}
		}else if(!empty($productID)){
			$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
			$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
			$adminEarning = $split['admin_earning'];
			$userEarning = $split['creator_net'];
			$agencyId = $split['agency_id'];
			$agencyFee = $split['agency_fee'];
			$agencyEarning = $split['agency_earning'];
			$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'authorize-net' AND payment_status <> 'ok'",
			    [
					$productOwnerID,
					$agencyId,
					$amountString,
					number_format((float) $adminFee, 2, '.', ''),
					number_format((float) $agencyFee, 2, '.', ''),
					number_format((float) $adminEarning, 2, '.', ''),
					number_format((float) $agencyEarning, 2, '.', ''),
					number_format((float) $userEarning, 2, '.', ''),
					$payerUserID,
					$requestData['order_id']
				]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
			}
		}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			iN_CompleteTipPayment($requestData['order_id'], $gatewayAmount, 'authorize-net');
		}
		iN_CompleteAgencyBoostPayment($requestData['order_id'], $gatewayAmount, 'authorize-net');
		// send data to payment response
		paymentResponse($paymentResponseData);
		// razorpay status is failed
	} else {
		// prepare payment data for failed payment
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['transaction_id'], 'authorize-net'),
		];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$requestData['order_id']]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($requestData['order_id']);
		} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($requestData['order_id']);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$requestData['order_id']]);
		}
		// send data to payment response
		paymentResponse($paymentResponseData);
	}
}else if ($requestData['paymentOption'] == 'mercadopago') {
    if ($requestData['collection_status'] == 'approved') {
        $paymentResponseData = [
            'status'   => true,
            'rawData'   => $requestData,
            'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
        ];
		$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$requestData['order_id']]);
		if ($pData) {
			$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
			$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
			$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
			if(!empty($userPayedPlanID)){
				$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
				$planAmount = $pAData['plan_amount'];
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'mercadopago' AND payment_status <> 'ok'",
					[$requestData['order_id']]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
				}
	        }else if(!empty($productID)){
				$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
				$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
				$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
				$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
				$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
	            $adminEarning = $split['admin_earning'];
	            $userEarning = $split['creator_net'];
	            $agencyId = $split['agency_id'];
	            $agencyFee = $split['agency_fee'];
	            $agencyEarning = $split['agency_earning'];
				$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'mercadopago' AND payment_status <> 'ok'",
				    [
						$productOwnerID,
						$agencyId,
						$amountString,
						number_format((float) $adminFee, 2, '.', ''),
						number_format((float) $agencyFee, 2, '.', ''),
						number_format((float) $adminEarning, 2, '.', ''),
						number_format((float) $agencyEarning, 2, '.', ''),
						number_format((float) $userEarning, 2, '.', ''),
						$payerUserID,
						$requestData['order_id']
					]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
				}
			}else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
				iN_CompleteTipPayment($requestData['order_id'], (float) $requestData['amount'], 'mercadopago');
			}
		}
		iN_CompleteAgencyBoostPayment($requestData['order_id'], (float) $requestData['amount'], 'mercadopago');
    } elseif ($requestData['collection_status'] == 'pending') {
        $paymentResponseData = [
            'status'   => 'pending',
            'rawData'   => $requestData,
            'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
        ];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$requestData['order_id']]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
        	iN_FailTipPayment($requestData['order_id']);
        } else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
        	iN_FailAgencyBoostPayment($requestData['order_id']);
        } else {
        	DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$requestData['order_id']]);
        }
    } else {
        $paymentResponseData = [
            'status'   => false,
            'rawData'   => $requestData,
            'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
        ];
        $paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$requestData['order_id']]);
        if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
        	iN_FailTipPayment($requestData['order_id']);
        } else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
        	iN_FailAgencyBoostPayment($requestData['order_id']);
        } else {
        	DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$requestData['order_id']]);
        }
    }
    paymentResponse($paymentResponseData);

} else if ($requestData['paymentOption'] == 'ccbill') {
	$status = strtolower((string) ($requestData['status'] ?? ''));
	$customPayload = $requestData['custom2'] ?? ($requestData['X-custom2'] ?? '');
	$metadata = [];
	if ($customPayload !== '') {
		$decoded = base64_decode($customPayload, true);
		if ($decoded !== false) {
			$decodedJson = json_decode($decoded, true);
			if (is_array($decodedJson)) {
				$metadata = $decodedJson;
			}
		}
	}
	$orderKey = $metadata['order_key'] ?? ($requestData['orderId'] ?? '');
	$type = strtolower((string) ($metadata['type'] ?? ($requestData['custom1'] ?? '')));
	$amount = isset($metadata['amount']) ? (float) $metadata['amount'] : (float) ($requestData['initialPrice'] ?? ($requestData['amount'] ?? 0));
	$subscriptionId = $requestData['subscription_id'] ?? ($requestData['subscriptionId'] ?? ($metadata['subscription_id'] ?? ''));
	$transactionId = $requestData['transaction_id'] ?? ($requestData['transId'] ?? $subscriptionId);
	$paymentResponseData = [
		'status' => false,
		'rawData' => $requestData,
		'data' => preparePaymentData($orderKey, $amount, $subscriptionId ?: $transactionId, 'ccbill'),
	];

	if ($status === 'success') {
		switch ($type) {
			case 'subscription':
			case 'subscribe':
				$subscriberID = (int) ($metadata['subscriber_id'] ?? 0);
				$creatorID = (int) ($metadata['creator_id'] ?? 0);
				$planID = (int) ($metadata['plan_id'] ?? 0);
				if ($subscriberID > 0 && $creatorID > 0 && $planID > 0) {
					$planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
					if ($planDetails) {
						$planType = $metadata['plan_type'] ?? $planDetails['plan_type'];
						$planAmount = (float) $planDetails['amount'];
						$amount = $planAmount;
						$planInterval = '';
						$planIntervalCount = '1';
						$current_period_start = date('Y-m-d H:i:s');

						if ($planType == 'weekly') {
							$planInterval = 'week';
							$current_period_end = date('Y-m-d H:i:s', strtotime('+7 days'));
						} else if ($planType == 'monthly') {
							$planInterval = 'month';
							$current_period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
						} else if ($planType == 'yearly') {
							$planInterval = 'year';
							$current_period_end = date('Y-m-d H:i:s', strtotime('+1 year'));
						} else {
							$planInterval = 'month';
							$current_period_end = date('Y-m-d H:i:s', strtotime('+30 days'));
						}

						$plancreated = $current_period_start;
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
						if (!$alreadySubscriber) {
							$insertSubscription = $iN->iN_InsertUserSubscription(
								$subscriberID,
								$creatorID,
								'ccbill',
								$subscriberName,
								$subscriptionId ?: $transactionId ?: $orderKey,
								$transactionId ?: $subscriptionId ?: $orderKey,
								'ccbill_' . $planID,
								$planAmount,
								$adminEarning,
								$userNetEarning,
								$metadata['currency'] ?? $ccbill_Currency,
								$planInterval,
								$planIntervalCount,
								$subscriberEmail,
								$plancreated,
								$current_period_start,
								$current_period_end,
								'active'
							);
							if ($insertSubscription) {
								$paymentResponseData['status'] = true;
								$paymentResponseData['data'] = preparePaymentData($orderKey, $planAmount, $subscriptionId ?: $transactionId, 'ccbill');
								$iN->iN_InsertNotificationForSubscribe($subscriberID, $creatorID);
							}
						} else {
							$paymentResponseData['status'] = true;
							$paymentResponseData['data'] = preparePaymentData($orderKey, $planAmount, $subscriptionId ?: $transactionId, 'ccbill');
						}
					}
				}
				break;

			case 'wallet':
			case 'point':
			case 'credit':
				if (!empty($orderKey)) {
					$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderKey]);
					$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : null;
					$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : null;
					$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : null;
					if (!empty($userPayedPlanID) && $payerUserID) {
						$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
						$planAmount = isset($pAData['plan_amount']) ? $pAData['plan_amount'] : 0;
						$paymentUpdated = DB::exec(
							"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
							[$orderKey]
						);
						if ($paymentUpdated > 0) {
							DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
							$paymentResponseData['status'] = true;
							$paymentResponseData['data'] = preparePaymentData($orderKey, $planAmount, $orderKey, 'ccbill');
						}
					} elseif (!empty($productID) && $payerUserID) {
						$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
						$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : null;
						$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : null;
						if ($productPrice && $productOwnerID) {
							$productPrice = round((float) $productPrice, 2);
							$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
							$adminEarning = $split['admin_earning'];
							$userEarning = $split['creator_net'];
							$agencyId = $split['agency_id'];
							$agencyFee = $split['agency_fee'];
							$agencyEarning = $split['agency_earning'];
							$amountString = number_format($productPrice, 2, '.', '');
							$paymentUpdated = DB::exec(
								"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
								[
									$productOwnerID,
									$agencyId,
									$amountString,
									number_format((float) $adminFee, 2, '.', ''),
									number_format((float) $agencyFee, 2, '.', ''),
									number_format((float) $adminEarning, 2, '.', ''),
									number_format((float) $agencyEarning, 2, '.', ''),
									number_format((float) $userEarning, 2, '.', ''),
									$payerUserID,
									$orderKey
								]
							);
							if ($paymentUpdated > 0) {
								DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
								$paymentResponseData['status'] = true;
								$paymentResponseData['data'] = preparePaymentData($orderKey, $productPrice, $orderKey, 'ccbill');
							}
						}
					}
				}
				if (!$paymentResponseData['status'] && !empty($orderKey)) {
					if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
						iN_FailTipPayment($orderKey);
					} else if ($pData && ($pData['payment_type'] ?? '') === 'agency_boost') {
						iN_FailAgencyBoostPayment($orderKey);
					} else {
						DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderKey]);
					}
				}
				break;

			case 'product':
				if (!empty($orderKey)) {
					$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderKey]);
					$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : null;
					$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : null;
					if ($productID && $payerUserID) {
						$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
						$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : null;
						$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : null;
						if ($productPrice && $productOwnerID) {
							$productPrice = round((float) $productPrice, 2);
							$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
							$adminEarning = $split['admin_earning'];
							$userEarning = $split['creator_net'];
							$agencyId = $split['agency_id'];
							$agencyFee = $split['agency_fee'];
							$agencyEarning = $split['agency_earning'];
							$amountString = number_format($productPrice, 2, '.', '');
							$paymentUpdated = DB::exec(
								"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'ccbill' AND payment_status <> 'ok'",
								[
									$productOwnerID,
									$agencyId,
									$amountString,
									number_format((float) $adminFee, 2, '.', ''),
									number_format((float) $agencyFee, 2, '.', ''),
									number_format((float) $adminEarning, 2, '.', ''),
									number_format((float) $agencyEarning, 2, '.', ''),
									number_format((float) $userEarning, 2, '.', ''),
									$payerUserID,
									$orderKey
								]
							);
							if ($paymentUpdated > 0) {
								DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
								$paymentResponseData['status'] = true;
								$paymentResponseData['data'] = preparePaymentData($orderKey, $productPrice, $orderKey, 'ccbill');
							}
						}
					}
					if (!$paymentResponseData['status']) {
						if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
							iN_FailTipPayment($orderKey);
						} else if ($pData && ($pData['payment_type'] ?? '') === 'agency_boost') {
							iN_FailAgencyBoostPayment($orderKey);
						} else {
							DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderKey]);
						}
					}
				}
				break;

				case 'tips':
					if (!empty($orderKey)) {
						if (iN_CompleteTipPayment($orderKey, (float) $amount, 'ccbill')) {
							$paymentResponseData['status'] = true;
							$paymentResponseData['data'] = preparePaymentData($orderKey, $amount, $transactionId ?: $orderKey, 'ccbill');
						} else {
							iN_FailTipPayment($orderKey);
						}
					}
					break;
				case 'campaign_donate':
					if (!empty($orderKey)) {
						if (iN_CompleteCampaignDonationPayment($orderKey, (float) $amount, 'ccbill')) {
							$paymentResponseData['status'] = true;
							$paymentResponseData['data'] = preparePaymentData($orderKey, $amount, $transactionId ?: $orderKey, 'ccbill');
						} else {
							iN_FailCampaignDonationPayment($orderKey);
						}
					}
					break;
				case 'agency_boost':
					if (!empty($orderKey)) {
						if (iN_CompleteAgencyBoostPayment($orderKey, (float) $amount, 'ccbill')) {
							$paymentResponseData['status'] = true;
							$paymentResponseData['data'] = preparePaymentData($orderKey, $amount, $transactionId ?: $orderKey, 'ccbill');
					} else {
						iN_FailAgencyBoostPayment($orderKey);
					}
				}
				break;

			default:
				if (!empty($orderKey)) {
					$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderKey]);
					if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
						iN_FailAgencyBoostPayment($orderKey);
					} else {
						DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderKey]);
					}
				}
				$paymentResponseData['status'] = false;
		}
		} else {
			if (!empty($orderKey)) {
				$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderKey]);
				if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
					iN_FailTipPayment($orderKey);
				} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'campaign_donate') {
					iN_FailCampaignDonationPayment($orderKey);
				} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
					iN_FailAgencyBoostPayment($orderKey);
				} else {
					DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderKey]);
				}
		}
	}

	paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'paysafecard') {
	$orderId = isset($requestData['orderId']) ? $requestData['orderId'] : ($requestData['order_id'] ?? null);
	$paymentResponseData = [
		'status' => false,
		'rawData' => $requestData,
		'data' => preparePaymentData($orderId, null, null, 'paysafecard'),
	];

	if (!$orderId) {
		paymentResponse($paymentResponseData);
	}

	$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
	if (!$pData) {
		paymentResponse($paymentResponseData);
	}

	if (isset($pData['payment_status']) && $pData['payment_status'] === 'ok') {
		$paidAmount = isset($pData['amount']) ? $pData['amount'] : null;
		$paidReference = isset($pData['invoice_number']) ? $pData['invoice_number'] : $orderId;
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $paidAmount, $paidReference, 'paysafecard'),
		];
		paymentResponse($paymentResponseData);
	}

	$gatewayPaymentId = isset($pData['invoice_number']) && $pData['invoice_number'] !== '' ? $pData['invoice_number'] : (isset($requestData['payment_id']) ? $requestData['payment_id'] : null);
	$paysafecardService = new PaysafecardService();
	$paymentInfo = [];
	$pscResponse = [];

	if ($gatewayPaymentId) {
		try {
			$pscResponse = $paysafecardService->fetchPayment((string) $gatewayPaymentId);
		} catch (Throwable $th) {
			$pscResponse = ['error' => true, 'message' => $th->getMessage()];
		}
	} else {
		$pscResponse = ['error' => true, 'message' => 'missing_payment_id'];
	}

	if (!empty($pscResponse['error'])) {
		$paymentResponseData['rawData'] = $pscResponse;
		if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($orderId);
		} else if ($pData && ($pData['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		}
		paymentResponse($paymentResponseData);
	}

	$paymentInfo = $pscResponse['data'] ?? [];
	$gatewayStatus = strtoupper((string) ($paymentInfo['status'] ?? ''));
	$amountFromGateway = null;
	$gatewayCurrency = isset($paymentInfo['currency']) ? $paymentInfo['currency'] : null;

	if (isset($paymentInfo['amount']) && is_array($paymentInfo['amount'])) {
		$amountData = $paymentInfo['amount'];
		if (isset($amountData['total'])) {
			$amountFromGateway = (float) $amountData['total'];
		} else if (isset($amountData['value'])) {
			$amountFromGateway = (float) $amountData['value'];
		} else if (isset($amountData['amount'])) {
			$amountFromGateway = (float) $amountData['amount'];
		}
		$gatewayCurrency = $amountData['currency'] ?? $gatewayCurrency;
	} else if (isset($paymentInfo['amount'])) {
		$amountFromGateway = ((float) $paymentInfo['amount']) / 100;
	}

	if ($amountFromGateway === null && isset($pData['amount'])) {
		$amountFromGateway = (float) $pData['amount'];
	}

	if (!$gatewayCurrency && isset($pData['currency'])) {
		$gatewayCurrency = $pData['currency'];
	}

	$isSuccessState = in_array($gatewayStatus, ['SUCCESS', 'AUTHORIZED'], true);

	$expectedAmount = isset($pData['amount']) ? (float) $pData['amount'] : null;
	if ($isSuccessState && $expectedAmount !== null && $amountFromGateway !== null && abs($expectedAmount - $amountFromGateway) > 0.01) {
		$isSuccessState = false;
	}
	$expectedCurrency = isset($pData['currency']) && $pData['currency'] !== '' ? $pData['currency'] : ($paysafecardCurrency ?? null);
	if ($isSuccessState && $expectedCurrency && $gatewayCurrency && strcasecmp($expectedCurrency, $gatewayCurrency) !== 0) {
		$isSuccessState = false;
	}

	if ($isSuccessState) {
		$currencyToStore = $gatewayCurrency ?? $expectedCurrency;
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;

		if (!empty($userPayedPlanID)) {
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0', invoice_number = ?, currency = ? WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'paysafecard' AND payment_status <> 'ok'",
				[$gatewayPaymentId, $currencyToStore, $orderId]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
				$paymentResponseData = [
					'status' => true,
					'rawData' => $paymentInfo,
					'data' => preparePaymentData($orderId, $planAmount, $gatewayPaymentId, 'paysafecard'),
				];
			}
		} else if (!empty($productID)) {
			$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			if ($productPrice && $productOwnerID) {
				$productPrice = round((float) $productPrice, 2);
				$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
				$adminEarning = $split['admin_earning'];
				$userEarning = $split['creator_net'];
				$agencyId = $split['agency_id'];
				$agencyFee = $split['agency_fee'];
				$agencyEarning = $split['agency_earning'];
				$amountString = number_format($productPrice, 2, '.', '');
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ?, invoice_number = ?, currency = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'paysafecard' AND payment_status <> 'ok'",
					[
						$productOwnerID,
						$agencyId,
						$amountString,
						number_format((float) $adminFee, 2, '.', ''),
						number_format((float) $agencyFee, 2, '.', ''),
						number_format((float) $adminEarning, 2, '.', ''),
						number_format((float) $agencyEarning, 2, '.', ''),
						number_format((float) $userEarning, 2, '.', ''),
						$gatewayPaymentId,
						$currencyToStore,
						$payerUserID,
						$orderId
					]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
					$paymentResponseData = [
						'status' => true,
						'rawData' => $paymentInfo,
						'data' => preparePaymentData($orderId, $productPrice, $gatewayPaymentId, 'paysafecard'),
					];
				}
			} else {
				$paymentResponseData['rawData'] = $paymentInfo;
			}
		} else if ($pData && ($pData['payment_type'] ?? '') === 'agency_boost') {
			$boostAmount = $amountFromGateway !== null ? $amountFromGateway : (float) ($pData['amount'] ?? 0);
			if (iN_CompleteAgencyBoostPayment($orderId, $boostAmount, 'paysafecard')) {
				DB::exec("UPDATE i_user_payments SET invoice_number = ?, currency = ? WHERE order_key = ? AND payment_status = 'ok'", [$gatewayPaymentId, $currencyToStore, $orderId]);
				$paymentResponseData = [
					'status' => true,
					'rawData' => $paymentInfo,
					'data' => preparePaymentData($orderId, $boostAmount, $gatewayPaymentId, 'paysafecard'),
				];
			} else {
				$paymentResponseData['rawData'] = $paymentInfo;
			}
		} else if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			$tipAmount = $amountFromGateway !== null ? $amountFromGateway : (float) ($pData['amount'] ?? 0);
			if (iN_CompleteTipPayment($orderId, $tipAmount, 'paysafecard')) {
				DB::exec("UPDATE i_user_payments SET invoice_number = ?, currency = ? WHERE order_key = ? AND payment_status = 'ok'", [$gatewayPaymentId, $currencyToStore, $orderId]);
				$paymentResponseData = [
					'status' => true,
					'rawData' => $paymentInfo,
					'data' => preparePaymentData($orderId, $tipAmount, $gatewayPaymentId, 'paysafecard'),
				];
			} else {
				$paymentResponseData['rawData'] = $paymentInfo;
			}
		} else {
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', invoice_number = ?, currency = ? WHERE order_key = ? AND payment_status <> 'ok'",
				[$gatewayPaymentId, $currencyToStore, $orderId]
			);
			if ($paymentUpdated > 0) {
				$paymentResponseData = [
					'status' => true,
					'rawData' => $paymentInfo,
					'data' => preparePaymentData($orderId, $amountFromGateway, $gatewayPaymentId, 'paysafecard'),
				];
			}
		}
	} else {
		if ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($orderId);
		} else if ($pData && ($pData['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		}
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paymentInfo,
			'data' => preparePaymentData($orderId, $amountFromGateway, $gatewayPaymentId, 'paysafecard'),
		];
	}

	paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'yookassa') {
	$orderId = $requestData['orderId'] ?? ($requestData['order_id'] ?? null);
	if (!$orderId) {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData(null, null, null, 'yookassa'),
		];
		paymentResponse($paymentResponseData);
	}

	$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
	$paymentId = $pData['yookassa_payment_id'] ?? null;
	if (!$pData || !$paymentId) {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, null, null, 'yookassa'),
		];
		paymentResponse($paymentResponseData);
	}

	$yookassaService = new YooKassaService();
	$paymentResponse = $yookassaService->fetchPayment((string) $paymentId);
	if (!empty($paymentResponse['error'])) {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paymentResponse,
			'data' => preparePaymentData($orderId, null, $paymentId, 'yookassa'),
		];
		paymentResponse($paymentResponseData);
	}

	$paymentInfo = $paymentResponse['data'] ?? [];
	$metadataOrderKey = isset($paymentInfo['metadata']['order_key']) ? (string) $paymentInfo['metadata']['order_key'] : '';
	if ($metadataOrderKey !== '' && $metadataOrderKey !== (string) $orderId) {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paymentInfo,
			'data' => preparePaymentData($orderId, null, $paymentId, 'yookassa'),
		];
		paymentResponse($paymentResponseData);
	}

	$status = strtolower((string) ($paymentInfo['status'] ?? ''));
	$paid = isset($paymentInfo['paid']) ? (bool) $paymentInfo['paid'] : false;
	$amountFromGateway = isset($paymentInfo['amount']['value']) ? (float) $paymentInfo['amount']['value'] : null;
	$currencyFromGateway = isset($paymentInfo['amount']['currency']) ? (string) $paymentInfo['amount']['currency'] : null;
	$expectedCurrency = strtoupper((string) ($configData['payments']['gateway_configuration']['yookassa']['currency'] ?? 'RUB'));
	if ($currencyFromGateway && strtoupper($currencyFromGateway) !== $expectedCurrency) {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paymentInfo,
			'data' => preparePaymentData($orderId, $amountFromGateway, $paymentId, 'yookassa'),
		];
		paymentResponse($paymentResponseData);
	}

	if ($status === 'succeeded' && $paid === true) {
		$paymentResponseData = [
			'status' => true,
			'rawData' => $paymentInfo,
			'data' => preparePaymentData($orderId, $amountFromGateway, $paymentId, 'yookassa'),
		];

		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : null;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : null;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : null;

		if (!empty($userPayedPlanID)) {
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$expectedAmount = $planAmount !== null ? round((float) $planAmount, 2) : null;
			if ($expectedAmount !== null && $amountFromGateway !== null && round((float) $amountFromGateway, 2) !== $expectedAmount) {
				$paymentResponseData['status'] = false;
			} else {
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'point' AND payment_option = 'yookassa' AND payment_status <> 'ok'",
					[$payerUserID, $orderId]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
				}
			}
		} elseif (!empty($productID)) {
			$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : null;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : null;
			$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
			if ($productPrice !== null && $amountFromGateway !== null && round((float) $amountFromGateway, 2) !== $productPrice) {
				$paymentResponseData['status'] = false;
			} else {
				$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
				$adminEarning = $split['admin_earning'];
				$userEarning = $split['creator_net'];
				$agencyId = $split['agency_id'];
				$agencyFee = $split['agency_fee'];
				$agencyEarning = $split['agency_earning'];
				$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
				$paymentUpdated = DB::exec(
					"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'yookassa' AND payment_status <> 'ok'",
					[
						$productOwnerID,
						$agencyId,
						$amountString,
						number_format((float) $adminFee, 2, '.', ''),
						number_format((float) $agencyFee, 2, '.', ''),
						number_format((float) $adminEarning, 2, '.', ''),
						number_format((float) $agencyEarning, 2, '.', ''),
						number_format((float) $userEarning, 2, '.', ''),
						$payerUserID,
						$orderId
					]
				);
				if ($paymentUpdated > 0) {
					DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
				}
			}
		} elseif ($pData && ($pData['payment_type'] ?? '') === 'tips') {
			$expectedAmount = isset($pData['amount']) ? round((float) $pData['amount'], 2) : null;
			if ($expectedAmount !== null && $amountFromGateway !== null && round((float) $amountFromGateway, 2) !== $expectedAmount) {
				$paymentResponseData['status'] = false;
			} else {
				iN_CompleteTipPayment($orderId, $amountFromGateway, 'yookassa');
			}
		}

		if ($paymentResponseData['status']) {
			$boostExpectedAmount = isset($pData['amount']) ? round((float) $pData['amount'], 2) : null;
			if ($pData && ($pData['payment_type'] ?? '') === 'agency_boost' && $boostExpectedAmount !== null && $amountFromGateway !== null && round((float) $amountFromGateway, 2) !== $boostExpectedAmount) {
				$paymentResponseData['status'] = false;
			} else {
				iN_CompleteAgencyBoostPayment($orderId, $amountFromGateway, 'yookassa');
			}
		}

		if (!$paymentResponseData['status']) {
			$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
			if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
				iN_FailTipPayment($orderId);
			} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
				iN_FailAgencyBoostPayment($orderId);
			} else {
				DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
			}
		}
	} else {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paymentInfo,
			'data' => preparePaymentData($orderId, $amountFromGateway, $paymentId, 'yookassa'),
		];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
			iN_FailTipPayment($orderId);
		} else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		}
	}

	paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'epoch') {
	$orderId = $requestData['orderId'] ?? ($requestData['order_id'] ?? ($requestData['x_order_key'] ?? null));
	if (!$orderId) {
		iN_LogEpochReturn('Return missing order id', ['query' => $requestData]);
		header('Location: ' . getAppUrl('payment-failed.php?gateway=epoch&status=failed'));
		exit;
	}

	$paymentRow = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
	$intentRow = DB::one(
		"SELECT * FROM i_user_subscription_intents WHERE order_key = ? AND payment_option = 'epoch' LIMIT 1",
		[$orderId]
	);
	if (!$paymentRow && !$intentRow) {
		iN_LogEpochReturn('Return order not found', ['order_key' => $orderId]);
		header('Location: ' . getAppUrl('payment-failed.php?gateway=epoch&status=failed'));
		exit;
	}

	$statusToken = strtolower((string)($requestData['status'] ?? ($requestData['result'] ?? ($requestData['response'] ?? ''))));
	$rawReturn = strtoupper(json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$hasTestMarker = strpos((string)$rawReturn, 'YGOODTEST') !== false;
	$isReturnFailure = in_array(
		$statusToken,
		['cancel', 'cancelled', 'canceled', 'failed', 'fail', 'error', 'denied', 'declined', 'reject', 'rejected'],
		true
	);

	$paymentType = (string)($paymentRow['payment_type'] ?? 'subscription');
	$isSettled = (($paymentRow['payment_status'] ?? '') === 'ok') || (($intentRow['status'] ?? '') === 'ok');
	$returnMode = isset($epochReturnMode) && in_array((string)$epochReturnMode, ['pending', 'status'], true)
		? (string)$epochReturnMode
		: 'pending';
	$allowPendingSuccess = ($returnMode === 'pending');

	if ($hasTestMarker) {
		iN_LogEpochReturn('Return contained YGOODTEST marker; no account actions', ['order_key' => $orderId]);
		header('Location: ' . getAppUrl('payment-failed.php?gateway=epoch&status=failed'));
		exit;
	}

	if ($isReturnFailure) {
		if ($paymentRow && ($paymentRow['payment_status'] ?? '') !== 'ok') {
			DB::exec(
				"UPDATE i_user_payments SET payment_status = 'declined' WHERE payment_id = ?",
				[(int)$paymentRow['payment_id']]
			);
		}
		if ($intentRow && ($intentRow['status'] ?? '') !== 'ok') {
			DB::exec(
				"UPDATE i_user_subscription_intents SET status = 'declined', updated_at = ? WHERE intent_id = ?",
				[(int)time(), (int)$intentRow['intent_id']]
			);
		}
		iN_LogEpochReturn('Return handled as failed', [
			'order_key' => $orderId,
			'payment_type' => $paymentType,
			'status_token' => $statusToken
		]);
		header('Location: ' . getAppUrl('payment-failed.php?gateway=epoch&status=failed'));
		exit;
	}

	iN_LogEpochReturn('Return handled', [
		'order_key' => $orderId,
		'payment_type' => $paymentType,
		'settled' => $isSettled ? 1 : 0,
		'return_mode' => $returnMode
	]);

	if ($isSettled) {
		$amountValue = isset($paymentRow['amount']) ? (float)$paymentRow['amount'] : null;
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $amountValue, ($requestData['transaction_id'] ?? $orderId), 'epoch'),
		];
		paymentResponse($paymentResponseData);
	}

	if ($allowPendingSuccess) {
		header('Location: ' . getAppUrl('payment-success.php?gateway=epoch&status=pending'));
		exit;
	}

	header('Location: ' . getAppUrl('payment-failed.php?gateway=epoch&status=pending'));
	exit;
} else if ($requestData['paymentOption'] == 'moneroo') {
    $monerooResponse = new MonerooResponse();
    $monerooData = $monerooResponse->getMonerooPaymentData($requestData);

    $orderId = $monerooData['order_id'] ?? ($requestData['order_id'] ?? $requestData['orderId'] ?? null);
    $amount = $monerooData['amount'] ?? ($requestData['amount'] ?? null);
    $transactionId = $monerooData['transaction_id'] ?? ($requestData['transaction_id'] ?? $requestData['reference'] ?? null);
    $status = strtolower((string) ($monerooData['status'] ?? $requestData['status'] ?? ''));
    $signatureValid = $monerooData['signature_valid'] ?? true;

    if (!$orderId) {
        $paymentResponseData = [
            'status' => false,
            'rawData' => $monerooData,
            'data' => preparePaymentData(null, $amount, $transactionId, 'moneroo')
        ];
        paymentResponse($paymentResponseData);
    }

    $successStates = ['paid', 'completed', 'success'];

    if (in_array($status, $successStates, true) && $signatureValid) {
        $paymentResponseData = [
            'status'   => true,
            'rawData'  => $monerooData,
            'data'     => preparePaymentData($orderId, $amount, $transactionId, 'moneroo')
        ];

        $pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderId]);
        if ($pData) {
            $userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
            $payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
            $productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
            if (!empty($userPayedPlanID)) {
                $pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
                $planAmount = $pAData['plan_amount'];
                $paymentUpdated = DB::exec(
                    "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'moneroo' AND payment_status <> 'ok'",
                    [$orderId]
                );
                if ($paymentUpdated > 0) {
                    DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
                }
            } elseif (!empty($productID)) {
                $productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
                $productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
                $productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
                $productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
                $split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
                $adminEarning = $split['admin_earning'];
                $userEarning = $split['creator_net'];
                $agencyId = $split['agency_id'];
                $agencyFee = $split['agency_fee'];
                $agencyEarning = $split['agency_earning'];
                $amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
                $paymentUpdated = DB::exec(
                    "UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'moneroo' AND payment_status <> 'ok'",
                    [
                        $productOwnerID,
                        $agencyId,
                        $amountString,
                        number_format((float) $adminFee, 2, '.', ''),
                        number_format((float) $agencyFee, 2, '.', ''),
                        number_format((float) $adminEarning, 2, '.', ''),
                        number_format((float) $agencyEarning, 2, '.', ''),
                        number_format((float) $userEarning, 2, '.', ''),
                        $payerUserID,
                        $orderId
                    ]
                );
                if ($paymentUpdated > 0) {
                    DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
                }
            } elseif ($pData && ($pData['payment_type'] ?? '') === 'tips') {
                iN_CompleteTipPayment($orderId, (float) $amount, 'moneroo');
            }
        }
		iN_CompleteAgencyBoostPayment($orderId, (float) $amount, 'moneroo');
    } elseif ($status === 'pending') {
        $paymentResponseData = [
            'status'   => 'pending',
            'rawData'  => $monerooData,
            'data'     => preparePaymentData($orderId, $amount, $transactionId, 'moneroo')
        ];
        $paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderId]);
        if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
            iN_FailTipPayment($orderId);
        } else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
            iN_FailAgencyBoostPayment($orderId);
        } else {
            DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
        }
    } else {
        $paymentResponseData = [
            'status'   => false,
            'rawData'  => $monerooData,
            'data'     => preparePaymentData($orderId, $amount, $transactionId, 'moneroo')
        ];
        $paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderId]);
        if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
            iN_FailTipPayment($orderId);
        } else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
            iN_FailAgencyBoostPayment($orderId);
        } else {
            DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
        }
    }

    paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'nowpayments') {
    $orderId = $requestData['orderId'] ?? ($requestData['order_id'] ?? null);
    $status = strtolower((string) ($requestData['status'] ?? ''));

    if (!$orderId) {
        $paymentResponseData = [
            'status' => false,
            'rawData' => $requestData,
            'data' => preparePaymentData(null, null, null, 'nowpayments'),
        ];
        paymentResponse($paymentResponseData);
    }

    if (in_array($status, ['cancel', 'cancelled', 'canceled', 'failed'], true)) {
        $paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
        if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'tips') {
            iN_FailTipPayment($orderId);
        } else if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
            iN_FailAgencyBoostPayment($orderId);
        } else {
            DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
        }
        $paymentResponseData = [
            'status' => false,
            'rawData' => $requestData,
            'data' => preparePaymentData($orderId, null, $orderId, 'nowpayments'),
        ];
        paymentResponse($paymentResponseData);
    }

    $pData = DB::one("SELECT payment_status, amount, invoice_number FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
    if ($pData && ($pData['payment_status'] ?? '') === 'ok') {
        $amount = $pData['amount'] ?? null;
        $txn = $pData['invoice_number'] ?? $orderId;
        $paymentResponseData = [
            'status' => true,
            'rawData' => $requestData,
            'data' => preparePaymentData($orderId, $amount, $txn, 'nowpayments'),
        ];
        paymentResponse($paymentResponseData);
    }

    $safeOrderId = htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8');
    $refreshUrl = getAppUrl('payment-response.php') . '?paymentOption=nowpayments&status=success&orderId=' . rawurlencode((string) $orderId);
    $cancelUrl = getAppUrl('payment-response.php') . '?paymentOption=nowpayments&status=cancel&orderId=' . rawurlencode((string) $orderId);

    $title = $LANG['nowpayments_processing_title'];
    $desc = $LANG['nowpayments_processing_desc'];
    $refreshLabel = $LANG['refresh'];
    $cancelLabel = $LANG['cancel'];

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta http-equiv="refresh" content="5;url=' . htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8') . '">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:Arial,sans-serif;max-width:760px;margin:40px auto;padding:0 16px;">';
    echo '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p>' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><strong>Order:</strong> ' . $safeOrderId . '</p>';
    echo '<p><a href="' . htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($refreshLabel, ENT_QUOTES, 'UTF-8') . '</a> | ';
    echo '<a href="' . htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') . '</a></p>';
    echo '</body></html>';
    exit;
} else if ($requestData['paymentOption'] == 'mercadopago-ipn') {
    $mercadopagoResponse = new MercadopagoResponse;
    $mercadopagoIpnData = $mercadopagoResponse->getMercadopagoPaymentData($requestData);

    $rawPostData = json_decode(file_get_contents('php://input'), true);

	if(isset($rawPostData["topic"])){
		if($rawPostData["topic"] == "merchant_order"){

			$call_merchant_order_id = $rawPostData["resource"];

			$token_mp = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1");


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $call_merchant_order_id);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			$headers = array();
			$headers[] = 'Authorization: Bearer '.$token_mp["mercadopago_live_access_id"]; //
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
            if (curl_errno($ch)) {
                    echo str_replace('{error}', curl_error($ch), $LANG['generic_error_prefixed']);
            }
			curl_close($ch);

			$data = json_decode($result, true);


			if(isset($data["order_status"])){
				if($data["order_status"] == "paid"){
					$paymentResponseData = [
							'status'   => true,
							'rawData'   => $requestData,
							'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['id'], 'mercadopago')
					];
					$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$requestData['order_id']]);
					$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
					$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
					$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
						if(!empty($pData)){
							if(!empty($userPayedPlanID)){
								$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
								$planAmount = $pAData['plan_amount'];
								$paymentUpdated = DB::exec(
									"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'mercadopago' AND payment_status <> 'ok'",
									[$requestData['order_id']]
								);
								if ($paymentUpdated > 0) {
									DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
								}

							}else if(!empty($productID)){

								$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
								$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
								$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
								$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
								$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
								$adminEarning = $split['admin_earning'];
								$userEarning = $split['creator_net'];
								$agencyId = $split['agency_id'];
								$agencyFee = $split['agency_fee'];
								$agencyEarning = $split['agency_earning'];
								$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
								$paymentUpdated = DB::exec(
									"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'mercadopago' AND payment_status <> 'ok'",
								    [
										$productOwnerID,
										$agencyId,
										$amountString,
										number_format((float) $adminFee, 2, '.', ''),
										number_format((float) $agencyFee, 2, '.', ''),
										number_format((float) $adminEarning, 2, '.', ''),
										number_format((float) $agencyEarning, 2, '.', ''),
										number_format((float) $userEarning, 2, '.', ''),
										$payerUserID,
										$requestData['order_id']
									]
								);
								if ($paymentUpdated > 0) {
									DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
								}
							}

						}
						iN_CompleteAgencyBoostPayment($requestData['order_id'], (float) $requestData['amount'], 'mercadopago');

				}
				paymentResponse($paymentResponseData);
			}

		}
	}
} else if ($requestData['paymentOption'] == 'moneroo-ipn') {
	$monerooResponse = new MonerooResponse();
	$monerooData = $monerooResponse->getMonerooPaymentData($requestData);

	$orderId = $monerooData['order_id'] ?? ($requestData['order_id'] ?? $requestData['orderId'] ?? null);
	$amount = $monerooData['amount'] ?? ($requestData['amount'] ?? null);
	$transactionId = $monerooData['transaction_id'] ?? ($requestData['transaction_id'] ?? $requestData['reference'] ?? null);
	$status = strtolower((string) ($monerooData['status'] ?? $requestData['status'] ?? ''));
	$signatureValid = $monerooData['signature_valid'] ?? true;

	$successStates = ['paid', 'completed', 'success'];

	if (!$orderId) {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $monerooData,
			'data' => preparePaymentData(null, $amount, $transactionId, 'moneroo'),
		];
		paymentResponse($paymentResponseData);
	}

	$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$orderId]);
	$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
	$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
	$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;

	if (in_array($status, $successStates, true) && $signatureValid) {
		$paymentResponseData = [
			'status' => true,
			'rawData' => $monerooData,
			'data' => preparePaymentData($orderId, $amount, $transactionId, 'moneroo'),
		];

		if (!empty($userPayedPlanID)) {
			$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
			$planAmount = $pAData['plan_amount'];
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'moneroo' AND payment_status <> 'ok'",
				[$orderId]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
			}
		} elseif (!empty($productID)) {
			$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
			$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
			$adminEarning = $split['admin_earning'];
			$userEarning = $split['creator_net'];
			$agencyId = $split['agency_id'];
			$agencyFee = $split['agency_fee'];
			$agencyEarning = $split['agency_earning'];
			$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
			$paymentUpdated = DB::exec(
				"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'moneroo' AND payment_status <> 'ok'",
				[
					$productOwnerID,
					$agencyId,
					$amountString,
					number_format((float) $adminFee, 2, '.', ''),
					number_format((float) $agencyFee, 2, '.', ''),
					number_format((float) $adminEarning, 2, '.', ''),
					number_format((float) $agencyEarning, 2, '.', ''),
					number_format((float) $userEarning, 2, '.', ''),
					$payerUserID,
					$orderId
				]
			);
			if ($paymentUpdated > 0) {
				DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
			}
		}
		iN_CompleteAgencyBoostPayment($orderId, (float) $amount, 'moneroo');
	} elseif ($status === 'pending') {
		$paymentResponseData = [
			'status' => 'pending',
			'rawData' => $monerooData,
			'data' => preparePaymentData($orderId, $amount, $transactionId, 'moneroo'),
		];
		DB::exec("UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = ? AND payment_status <> 'ok'", [$orderId]);
	} else {
		$paymentResponseData = [
			'status' => false,
			'rawData' => $monerooData,
			'data' => preparePaymentData($orderId, $amount, $transactionId, 'moneroo'),
		];
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ?", [$orderId]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		}
	}

	paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'nowpayments-ipn') {
    $nowPaymentsResponse = new NowPaymentsResponse();
    $nowPaymentsData = $nowPaymentsResponse->getNowPaymentsPaymentData($requestData);

    $orderId = $nowPaymentsData['order_id'] ?? null;
    $amount = $nowPaymentsData['amount'] ?? null;
    $status = strtolower((string) ($nowPaymentsData['status'] ?? ''));
    $transactionId = $nowPaymentsData['transaction_id'] ?? null;
    $signatureValid = (bool) ($nowPaymentsData['signature_valid'] ?? false);

    if (!$orderId) {
        http_response_code(400);
        exit('missing_order_id');
    }

    if (!$signatureValid) {
        http_response_code(400);
        exit('invalid_signature');
    }

    $pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderId]);
    if (!$pData) {
        // Already handled or unknown order_id; acknowledge to stop retries.
        exit('OK');
    }
    if (($pData['payment_option'] ?? '') !== 'nowpayments') {
        exit('OK');
    }

    if (($pData['payment_status'] ?? '') === 'ok') {
        exit('OK');
    }

    $successStates = ['finished', 'confirmed'];
    $pendingStates = ['waiting', 'confirming', 'sending', 'partially_paid'];

    if (in_array($status, $successStates, true)) {
        $userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : null;
        $payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : null;
        $productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : null;
        $finalized = false;

        if (!empty($userPayedPlanID)) {
            $pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
            $planAmount = $pAData['plan_amount'];
            $paymentUpdated = DB::exec(
                "UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'nowpayments' AND payment_status <> 'ok'",
                [$orderId]
            );
            if ($paymentUpdated > 0) {
                DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
                $finalized = true;
            }
        } elseif (!empty($productID)) {
            $productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
            $productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : null;
            $productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : null;
            $productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
            $split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
            $adminEarning = $split['admin_earning'];
            $userEarning = $split['creator_net'];
            $agencyId = $split['agency_id'];
            $agencyFee = $split['agency_fee'];
            $agencyEarning = $split['agency_earning'];
            $amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
            $paymentUpdated = DB::exec(
                "UPDATE i_user_payments SET payment_status = 'ok', payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'nowpayments' AND payment_status <> 'ok'",
                [
                    $productOwnerID,
                    $agencyId,
                    $amountString,
                    number_format((float) $adminFee, 2, '.', ''),
                    number_format((float) $agencyFee, 2, '.', ''),
                    number_format((float) $adminEarning, 2, '.', ''),
                    number_format((float) $agencyEarning, 2, '.', ''),
                    number_format((float) $userEarning, 2, '.', ''),
                    $payerUserID,
                    $orderId
                ]
            );
            if ($paymentUpdated > 0) {
                DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
                $finalized = true;
            }
	        } elseif (($pData['payment_type'] ?? '') === 'tips') {
	            $finalized = iN_CompleteTipPayment($orderId, $amount !== null ? (float) $amount : null, 'nowpayments');
	        } elseif (($pData['payment_type'] ?? '') === 'campaign_donate') {
	            $finalized = iN_CompleteCampaignDonationPayment($orderId, $amount !== null ? (float) $amount : null, 'nowpayments');
	        } elseif (($pData['payment_type'] ?? '') === 'agency_boost') {
	            $finalized = iN_CompleteAgencyBoostPayment($orderId, $amount !== null ? (float) $amount : null, 'nowpayments');
	        } else {
            $paymentUpdated = DB::exec("UPDATE i_user_payments SET payment_status = 'ok' WHERE order_key = ? AND payment_status <> 'ok'", [$orderId]);
            $finalized = $paymentUpdated > 0;
        }

        if ($transactionId && $finalized) {
            DB::exec("UPDATE i_user_payments SET invoice_number = ? WHERE order_key = ?", [(string) $transactionId, $orderId]);
        }

        exit('OK');
    }

    if (in_array($status, $pendingStates, true)) {
        DB::exec("UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = ? AND payment_status <> 'ok'", [$orderId]);
        exit('OK');
    }

	    // failed/expired/refunded etc
	    if (($pData['payment_type'] ?? '') === 'tips') {
	        iN_FailTipPayment($orderId);
	        exit('OK');
	    }
	    if (($pData['payment_type'] ?? '') === 'campaign_donate') {
	        iN_FailCampaignDonationPayment($orderId);
	        exit('OK');
	    }
		if (($pData['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($orderId);
			exit('OK');
		}

		    DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$orderId]);
		    exit('OK');
} else if ($requestData['paymentOption'] == 'bitpay') {
	// prepare payment data
	$paymentResponseData = [
		'status' => true,
		'rawData' => $requestData,
		'data' => preparePaymentData($requestData['orderId'], $requestData['amount'], $requestData['orderId'], 'bitpay'),
	];
	$pData = DB::one("SELECT * FROM i_user_payments WHERE order_key = ?", [$requestData['orderId']]);
	$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
	$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
	$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
	if(!empty($userPayedPlanID)){
		$pAData = DB::one("SELECT * FROM i_premium_plans WHERE plan_id = ?", [$userPayedPlanID]);
		$planAmount = $pAData['plan_amount'];
		$paymentUpdated = DB::exec(
			"UPDATE i_user_payments SET payment_status = 'ok', agency_id_fk = NULL, fee = '0', agency_fee = '0', admin_earning = '0', agency_earning = '0', user_earning = '0' WHERE order_key = ? AND payment_type = 'point' AND payment_option = 'bitpay' AND payment_status <> 'ok'",
			[$requestData['orderId']]
		);
		if ($paymentUpdated > 0) {
			DB::exec("UPDATE i_users SET wallet_points = wallet_points + ? WHERE iuid = ?", [$planAmount, $payerUserID]);
		}
	}else if(!empty($productID)){
		$productData = DB::one("SELECT * FROM i_user_product_posts WHERE pr_id = ?", [$productID]);
		$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
		$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
		$productPrice = $productPrice !== null ? round((float) $productPrice, 2) : null;
		$split = $iN->iN_CalculateAgencySplit($productOwnerID, $productPrice, $adminFee);
		$adminEarning = $split['admin_earning'];
		$userEarning = $split['creator_net'];
		$agencyId = $split['agency_id'];
		$agencyFee = $split['agency_fee'];
		$agencyEarning = $split['agency_earning'];
		$amountString = $productPrice !== null ? number_format($productPrice, 2, '.', '') : '0.00';
		$paymentUpdated = DB::exec(
			"UPDATE i_user_payments SET payment_status = 'ok' , payed_iuid_fk = ?, agency_id_fk = ?, amount = ?, fee = ?, agency_fee = ?, admin_earning = ?, agency_earning = ?, user_earning = ? WHERE payer_iuid_fk = ? AND order_key = ? AND payment_type = 'product' AND payment_option = 'bitpay' AND payment_status <> 'ok'",
		    [
				$productOwnerID,
				$agencyId,
				$amountString,
				number_format((float) $adminFee, 2, '.', ''),
				number_format((float) $agencyFee, 2, '.', ''),
				number_format((float) $adminEarning, 2, '.', ''),
				number_format((float) $agencyEarning, 2, '.', ''),
				number_format((float) $userEarning, 2, '.', ''),
				$payerUserID,
				$requestData['orderId']
			]
		);
		if ($paymentUpdated > 0) {
			DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [number_format((float) $userEarning, 2, '.', ''), $productOwnerID]);
		}
	}
	iN_CompleteAgencyBoostPayment($requestData['orderId'], (float) $requestData['amount'], 'bitpay');
	// send data to payment response
	paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'bitpay-ipn') {
	$bitpayResponse = new BitPayResponse;
	$rawPostData = file_get_contents('php://input');
	$ipnData = $bitpayResponse->getBitPayPaymentData($rawPostData);
	if ($ipnData['status'] == 'success') {
		// code here
		$paymentRow = DB::one("SELECT payment_type, amount FROM i_user_payments WHERE order_key = ? LIMIT 1", [$requestData['orderId']]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			$amountFromRow = isset($paymentRow['amount']) ? (float) $paymentRow['amount'] : null;
			iN_CompleteAgencyBoostPayment($requestData['orderId'], $amountFromRow, 'bitpay');
		} else {
			DB::exec("UPDATE i_user_payments SET payment_status = 'ok' WHERE order_key = ? AND payment_status <> 'ok'", [$requestData['orderId']]);
		}
	} else {
		// code here
		$paymentRow = DB::one("SELECT payment_type FROM i_user_payments WHERE order_key = ? LIMIT 1", [$requestData['orderId']]);
		if ($paymentRow && ($paymentRow['payment_type'] ?? '') === 'agency_boost') {
			iN_FailAgencyBoostPayment($requestData['orderId']);
		} else {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payment_type <> 'campaign_donate'", [$requestData['orderId']]);
		}
	}
}

/*
 * This payment used for get Success / Failed data for any payment method.
 *
 * @param array $paymentResponseData - contains : status and rawData
 *
 */
function paymentResponse($paymentResponseData) {
	$orderKey = isset($paymentResponseData['data']['order_id']) ? $paymentResponseData['data']['order_id'] : null;
	$gateway = isset($paymentResponseData['data']['payment_gatway']) ? (string)$paymentResponseData['data']['payment_gatway'] : '';
	$amount = isset($paymentResponseData['data']['amount']) ? (float)$paymentResponseData['data']['amount'] : null;
	$paymentRow = null;
	if ($orderKey) {
		$paymentRow = DB::one("SELECT payment_type, payed_post_id_fk, payed_iuid_fk FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
	}
	$paymentType = isset($paymentRow['payment_type']) ? (string)$paymentRow['payment_type'] : '';
	$isSupportPayment = in_array($paymentType, ['tips', 'campaign_donate'], true);
	$showPaymentContext = in_array($paymentType, ['tips', 'campaign_donate', 'subscription'], true);

	// payment status success
	if ($paymentResponseData['status']) {
		if ($paymentType === 'tips' && $orderKey) {
			iN_CompleteTipPayment($orderKey, $amount, $gateway);
		} elseif ($paymentType === 'campaign_donate' && $orderKey) {
			iN_CompleteCampaignDonationPayment($orderKey, $amount, $gateway);
		}
		if (!empty($paymentResponseData['data']['order_id']) && isset($paymentResponseData['data']['amount'])) {
			iN_AttachInvoiceForOrder($paymentResponseData['data']['order_id'], $paymentResponseData['data']['amount']);
		}
		// Show payment success page or do whatever you want, like send email, notify to user etc
			if ($showPaymentContext && $orderKey) {
				$queryData = [
					'payment_type' => $paymentType,
					'order_id' => $orderKey,
				];
				if ($isSupportPayment) {
					$queryData['puid'] = isset($paymentRow['payed_iuid_fk']) ? (int) $paymentRow['payed_iuid_fk'] : null;
					$queryData['ppid'] = isset($paymentRow['payed_post_id_fk']) ? (int) $paymentRow['payed_post_id_fk'] : null;
				}
				$query = http_build_query($queryData);
				header('Location: ' . getAppUrl('payment-success.php?' . $query));
				exit;
			}
		header('Location: ' . getAppUrl('payment-success.php'));
		//  var_dump($paymentResponseData);
	} else {
		if ($paymentType === 'tips' && $orderKey) {
			iN_FailTipPayment($orderKey);
		} elseif ($paymentType === 'campaign_donate' && $orderKey) {
			iN_FailCampaignDonationPayment($orderKey);
		}
		// Show payment error page or do whatever you want, like send email, notify to user etc
			if ($showPaymentContext && $orderKey) {
				$query = http_build_query([
					'payment_type' => $paymentType,
					'order_id' => $orderKey,
					'status' => 'failed',
				]);
				header('Location: ' . getAppUrl('payment-failed.php?' . $query));
				exit;
			}
		header('Location: ' . getAppUrl('payment-failed.php'));
	}
}

/*
 * Prepare Payment Data.
 *
 * @param array $paymentData
 *
 */
function preparePaymentData($orderId, $amount, $txnId, $paymentGateway) {
	return [
		'order_id' => $orderId,
		'amount' => $amount,
		'payment_reference_id' => $txnId,
		'payment_gatway' => $paymentGateway,
	];
}

function iN_AttachInvoiceForOrder($orderKey, $amount) {
	global $iN, $defaultCurrency;
	if (!$orderKey) {
		return;
	}
	$paymentRow = DB::one("SELECT payment_id, payer_iuid_fk FROM i_user_payments WHERE order_key = ? LIMIT 1", [$orderKey]);
	if (!$paymentRow) {
		return;
	}
	$currency = $defaultCurrency ?? 'USD';
	$iN->iN_AssignInvoiceToPayment(
		$paymentRow['payment_id'],
		$paymentRow['payer_iuid_fk'],
		$amount,
		$currency
	);
}
