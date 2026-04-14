<?php
if($logedIn == 0){
    header('Location: ' . route_url('404'));
}else{ 
 require_once 'includes/payment/vendor/autoload.php';
 if (!defined('INORA_METHODS_CONFIG')) {
     define('INORA_METHODS_CONFIG', realpath('includes/payment/paymentConfig.php'));
 }
// Get config data
$configData = configItem();
    $failedTitle = $LANG['payment_failed_title'];
    $failedDescription = $LANG['payment_failed_desc'];
    $gateway = isset($_GET['gateway']) ? strtolower((string)$iN->iN_Secure($_GET['gateway'])) : '';
    $status = isset($_GET['status']) ? strtolower((string)$iN->iN_Secure($_GET['status'])) : '';
    if ($gateway === 'epoch' && $status === 'pending') {
        $failedDescription = $LANG['epoch_return_pending_status_mode'] ?? $failedDescription;
    } elseif ($gateway === 'epoch') {
        $failedDescription = $LANG['epoch_return_failed'] ?? $failedDescription;
    }
    include("themes/$currentTheme/payment-failed.php");
}
?>
