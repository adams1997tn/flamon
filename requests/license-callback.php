<?php
include_once __DIR__ . '/../includes/inc.php';

if (!function_exists('respond_and_exit')) {
    function respond_and_exit(string $msg): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo $msg;
        exit();
    }
}

if ($logedIn !== '1' || $userType !== '2') {
    http_response_code(403);
    respond_and_exit('login_required');
}

$data = $_POST ?: $_GET;
$status = isset($data['status']) ? trim((string) $data['status']) : '';
$message = isset($data['message']) ? trim((string) $data['message']) : '';
$licenseToken = isset($data['license_token']) ? trim((string) $data['license_token']) : '';
$state = isset($data['state']) ? trim((string) $data['state']) : '';
$nonce = isset($data['nonce']) ? trim((string) $data['nonce']) : '';
$envatoItemId = isset($data['envato_item_id']) ? (int) $data['envato_item_id'] : 0;
$issuedAt = isset($data['issued_at']) ? (int) $data['issued_at'] : 0;
$nextCheckAt = isset($data['next_check_at']) ? (int) $data['next_check_at'] : 0;
$signature = isset($data['signature']) ? (string) $data['signature'] : '';

if ($status === '' || $state === '' || $nonce === '') {
    respond_and_exit('missing_params');
}

$payload = [
    'license_token' => $licenseToken,
    'message' => $message,
    'next_check_at' => $nextCheckAt,
    'nonce' => $nonce,
    'envato_item_id' => $envatoItemId,
    'state' => $state,
    'status' => $status,
    'issued_at' => $issuedAt,
];

// Signature validation disabled when no central key is configured; verifySignature already permits empty key.
LicenseHelper::verifySignature($payload, $signature);

$pendingOk = LicenseHelper::validatePending($state, $nonce, true);

$successStatuses = ['ok', 'active'];
$passthroughStatuses = ['grace', 'locked', 'revoked', 'failed', 'inactive'];
$normalizedStatus = 'failed';
if (in_array($status, $successStatuses, true)) {
    $normalizedStatus = 'active';
} elseif (in_array($status, $passthroughStatuses, true)) {
    $normalizedStatus = $status;
}

if (!$pendingOk) {
    $_SESSION['license_notice'] = 'license_activation_failed';
    $_SESSION['license_message'] = $message ?: 'Activation failed.';
    $redirectTo = route_url('admin/license_activation');
    header('Location: ' . $redirectTo);
    exit();
}

if ($normalizedStatus === 'active' && !empty($licenseToken)) {
    $stored = LicenseHelper::storeToken($licenseToken, 'active');
    if ($stored) {
        $_SESSION['license_notice'] = 'license_activation_success';
        $_SESSION['license_message'] = $message ?: 'Activation successful.';
    } else {
        $_SESSION['license_notice'] = 'license_activation_failed';
        $_SESSION['license_message'] = $message ?: 'Activation failed.';
    }
} else {
    LicenseHelper::updateStatus($normalizedStatus);
    $_SESSION['license_notice'] = 'license_activation_failed';
    $_SESSION['license_message'] = $message ?: 'Activation failed.';
}

$redirectTo = route_url('admin/license_activation');
header('Location: ' . $redirectTo);
exit();
