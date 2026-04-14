<?php
if ($logedIn == 0) {
    header('Location: ' . route_url('404'));
    exit;
}

require_once 'includes/payment/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (!defined('INORA_METHODS_CONFIG')) {
    define('INORA_METHODS_CONFIG', realpath('includes/payment/paymentConfig.php'));
}
$configData = configItem();
$tipPaymentRow = null;
$tipPaymentData = null;
$subscriptionPaymentRow = null;
$paymentSummaryItems = [];
$tipOrderKey = isset($_GET['order_id']) ? $iN->iN_Secure($_GET['order_id']) : null;
$supportPaymentType = isset($_GET['payment_type']) ? strtolower((string)$iN->iN_Secure($_GET['payment_type'])) : '';
$isSubscriptionPayment = ($supportPaymentType === 'subscription');
if (in_array($supportPaymentType, ['tips', 'campaign_donate'], true)) {
    if ($tipOrderKey) {
        $tipPaymentRow = DB::one(
            "SELECT * FROM i_user_payments WHERE order_key = ? AND payment_type = ? LIMIT 1",
            [$tipOrderKey, $supportPaymentType]
        );
    } else {
        $tipPaymentRow = DB::one(
            "SELECT * FROM i_user_payments WHERE payment_type = ? AND payer_iuid_fk = ? ORDER BY payment_id DESC LIMIT 1",
            [$supportPaymentType, $userID]
		);
	}
}

if ($isSubscriptionPayment) {
	if ($tipOrderKey) {
		$subscriptionPaymentRow = DB::one(
			"SELECT * FROM i_user_payments WHERE order_key = ? AND payment_type = 'subscription' AND payer_iuid_fk = ? LIMIT 1",
			[$tipOrderKey, $userID]
		);
	} else {
		$subscriptionPaymentRow = DB::one(
			"SELECT * FROM i_user_payments WHERE payment_type = 'subscription' AND payer_iuid_fk = ? ORDER BY payment_id DESC LIMIT 1",
			[$userID]
		);
	}
}

$uData = null;
$payedUserID = null;
$purchasedPointID = null;
$paymentID = null;
$productID = null;
$invoiceToken = null;
if (!$isSubscriptionPayment) {
	$uData = $iN->iN_LatestPaymentPoint($userID);
	$payedUserID = isset($uData['payer_iuid_fk']) ? $uData['payer_iuid_fk'] : null;
	$purchasedPointID = isset($uData['credit_plan_id']) ? $uData['credit_plan_id'] : null;
	$paymentID = isset($uData['payment_id']) ? $uData['payment_id'] : null;
	$productID = isset($uData['paymet_product_id']) ? $uData['paymet_product_id'] : null;
	$invoiceToken = $uData['invoice_token'] ?? null;
}

$successIcon = html_entity_decode($iN->iN_SelectedMenuIcon('131'));
$successMessage = $LANG['your_payment_successfull'];
$successDescription = '';
$redirectToProduct = null;
$gateway = isset($_GET['gateway']) ? strtolower((string)$iN->iN_Secure($_GET['gateway'])) : '';
$gatewayStatus = isset($_GET['status']) ? strtolower((string)$iN->iN_Secure($_GET['status'])) : '';

if ($gateway === 'epoch' && $gatewayStatus === 'pending') {
    $successDescription = $LANG['epoch_return_pending'] ?? 'Payment return received. Your access will be updated after EPOCH postback verification.';
}

if ($tipPaymentRow && ($tipPaymentRow['payment_status'] ?? '') === 'ok') {
    $isCampaignDonation = ($tipPaymentRow['payment_type'] ?? '') === 'campaign_donate';
    $successMessage = $isCampaignDonation ? ($LANG['thanks_for_donation'] ?? ($LANG['thanks_for_tip'] ?? '')) : ($LANG['thanks_for_tip'] ?? '');
    $formattedAmount = isset($tipPaymentRow['amount']) ? formatCurrency($tipPaymentRow['amount'], $defaultCurrency) : null;
    if ($formattedAmount !== null) {
        $successDescription = $successMessage . ' ' . $formattedAmount;
    }
    $tipPaymentData = [
        'order_id' => $tipPaymentRow['order_key'] ?? null,
        'payed_user_id' => $tipPaymentRow['payed_iuid_fk'] ?? null,
        'payed_post_id' => $tipPaymentRow['payed_post_id_fk'] ?? null,
        'payment_type' => $tipPaymentRow['payment_type'] ?? null
    ];
	if ($tipPaymentRow['invoice_token'] ?? null) {
		$invoiceToken = $tipPaymentRow['invoice_token'];
	}
} elseif ($isSubscriptionPayment && $subscriptionPaymentRow) {
	$paymentStatus = strtolower((string)($subscriptionPaymentRow['payment_status'] ?? ''));
	if ($paymentStatus === 'ok') {
		$amountValue = isset($subscriptionPaymentRow['amount']) ? (float)$subscriptionPaymentRow['amount'] : 0;
		$currencyCode = (string)($subscriptionPaymentRow['currency'] ?? $defaultCurrency);
			if ($amountValue > 0) {
				$paymentSummaryItems[] = [
					'label' => $LANG['amount'] ?? '',
					'value' => formatCurrency($amountValue, $currencyCode),
				];
			}
		$paymentMethod = trim((string)($subscriptionPaymentRow['payment_option'] ?? ''));
			if ($paymentMethod !== '') {
				$paymentSummaryItems[] = [
					'label' => $LANG['payment_method'] ?? '',
					'value' => ucwords(str_replace(['_', '-'], ' ', $paymentMethod)),
				];
			}
		$paymentTime = isset($subscriptionPaymentRow['payment_time']) ? (int)$subscriptionPaymentRow['payment_time'] : 0;
			if ($paymentTime > 0) {
				$paymentSummaryItems[] = [
					'label' => $LANG['date'] ?? '',
					'value' => date('Y-m-d H:i', $paymentTime),
				];
			}
		$reference = trim((string)($subscriptionPaymentRow['invoice_number'] ?? ''));
		if ($reference === '') {
			$reference = trim((string)($subscriptionPaymentRow['order_key'] ?? ''));
		}
			if ($reference !== '') {
				$paymentSummaryItems[] = [
					'label' => $LANG['invoice'] ?? '',
					'value' => $reference,
				];
			}
		if (!empty($subscriptionPaymentRow['invoice_token'])) {
			$invoiceToken = $subscriptionPaymentRow['invoice_token'];
		}
	} else {
		$successDescription = $LANG['payment_waiting_to_be_complete'] ?? '';
	}
} elseif (!empty($purchasedPointID)) {
    $planData = $iN->GetPlanDetails($purchasedPointID);
    $planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : null;
    $planMoney = isset($planData['amount']) ? $planData['amount'] : null;
    $successDescription = $iN->iN_TextReaplacement($LANG['thank_you_for_purchase_not'], [$planPoint, $planMoney]);
    $iN->iN_UpdatePaymentSuccessStatusAmount($userID, $paymentID, $planMoney);
} elseif (!empty($productID)) {
    $prData = $iN->iN_GetProductDetailsByID($productID);
    $productSlug = isset($prData['pr_name_slug']) ? $prData['pr_name_slug'] : null;
    $planMoney = isset($prData['pr_price']) ? $prData['pr_price'] : null;
    $successDescription = $LANG['thank_you_for_purchase_product_not'];
    if ($productSlug) {
        $redirectToProduct = route_url('product/' . $productSlug . '_' . $productID);
    }
}

if (!$isSubscriptionPayment && $paymentID) {
	$iN->iN_UpdatePaymentSuccessStatus($userID, $paymentID);
}

if (!$isSubscriptionPayment) {
	$getPayedUserDetails = $iN->iN_GetUserDetails($payedUserID);
	$sendEmail = $getPayedUserDetails['i_user_email'] ?? null;
	if ($tipPaymentRow) {
		$sendEmail = null;
	}

	if ($sendEmail) {
		include_once 'includes/mail/vendor/autoload.php';
		$mail = new PHPMailer;

		if ($smtpOrMail == 'mail') {
			$mail->IsMail();
		} else if ($smtpOrMail == 'smtp') {
			$mail->isSMTP();
			$mail->Host = $smtpHost;
			$mail->SMTPAuth = true;
			$mail->SMTPKeepAlive = true;
			$mail->Username = $smtpUserName;
			$mail->Password = $smtpPassword;
			$mail->SMTPSecure = $smtpEncryption;
			$mail->Port = $smtpPort;
			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				),
			);
		}

		$notQualifyDocument = $LANG['not_qualify_document'];
		$instagramIcon = $iN->iN_SelectedMenuIcon('88');
		$facebookIcon = $iN->iN_SelectedMenuIcon('90');
		$twitterIcon = $iN->iN_SelectedMenuIcon('34');
		$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
		if (!empty($productID)) {
			include_once "includes/mailTemplates/productPurchaseMailTemplate.php";
		} else {
			include_once "includes/mailTemplates/pointPurchaseMailTemplate.php";
		}
		$body = $bodyPointPurchased;
		$mail->setFrom($smtpUserName, $siteName);
		$mail->IsHTML(true);
		$mail->addAddress($sendEmail);
		$mailSubject = !empty($productID) ? ($LANG['you_bought_a_product'] ?? 'You bought a product') : ($LANG['you_bought_points'] ?? 'You bought points');
		$mail->Subject = preg_replace('/{.*?}/', $planMoney, $mailSubject);
		$mail->CharSet = 'utf-8';
		$mail->MsgHTML($body);
		$mail->send();
	}
}

if ($redirectToProduct) {
    header('Location: ' . $redirectToProduct);
    exit;
}

include("themes/$currentTheme/payment-success.php");
?>
