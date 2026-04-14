<?php
if ($logedIn == 0) {
    header('Location: ' . route_url('404'));
    exit;
}

$token = isset($_GET['token']) ? $iN->iN_Secure($_GET['token']) : null;
if (!$token) {
    header('Location: ' . route_url('404'));
    exit;
}

$invoiceData = $iN->iN_GetInvoiceByToken($token);
if (!$invoiceData) {
    header('Location: ' . route_url('404'));
    exit;
}

$payerId = (int) ($invoiceData['payer_iuid_fk'] ?? 0);
if ($userType != '2' && $payerId !== (int)$userID) {
    header('Location: ' . route_url('404'));
    exit;
}

$payerDetails = $iN->iN_GetUserDetails($payerId);
$taxSettings = $iN->iN_GetTaxSettings();
$currencyCode = $invoiceData['currency'] ?? ($defaultCurrency ?? 'USD');
$currencySymbol = $currencys[$currencyCode] ?? ($currencys[$defaultCurrency] ?? '$');
$taxAmount = isset($invoiceData['tax_amount']) ? (float)$invoiceData['tax_amount'] : 0;
$grossAmount = isset($invoiceData['amount']) ? (float)$invoiceData['amount'] : 0;
$netAmount = round($grossAmount - $taxAmount, 2);
$invoiceNumber = $invoiceData['invoice_number'] ?? ('INV-' . $invoiceData['payment_id']);
$billingName = $invoiceData['billing_name'] ?? ($payerDetails['i_user_fullname'] ?? '');
$billingEmail = $invoiceData['billing_email'] ?? ($payerDetails['i_user_email'] ?? '');
$billingAddress = $invoiceData['billing_address'] ?? '';
$invoiceDate = isset($invoiceData['payment_time']) ? gmdate("d/m/Y H:i", $invoiceData['payment_time']) : date("d/m/Y H:i");

include("themes/$currentTheme/invoice.php");
