<?php
// Lightweight cron entrypoint for agency boost expirations
$appRoot = dirname(__DIR__);
require_once $appRoot . '/includes/inc.php';

$envToken = getenv('AGENCY_BOOST_CRON_TOKEN') ?: '';
$dbToken = '';
if ($envToken === '') {
    $dbToken = (string) $iN->iN_GetSetting('agency_boost_cron_token', '');
}
$configuredToken = $envToken !== '' ? $envToken : $dbToken;
$providedToken = '';
if (PHP_SAPI !== 'cli') {
    $providedToken = isset($_GET['token']) ? (string)$_GET['token'] : '';
    if (empty($providedToken) && isset($_SERVER['HTTP_X_CRON_TOKEN'])) {
        $providedToken = (string)$_SERVER['HTTP_X_CRON_TOKEN'];
    }
    if ($configuredToken === '') {
        http_response_code(403);
        exit('forbidden');
    }
    if ($providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
        http_response_code(403);
        exit('forbidden');
    }
}

$expired = 0;
try {
    $expired = $iN->iN_ExpireAgencyBoosts();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'failed_to_expire'
    ]);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'expired' => $expired
]);
