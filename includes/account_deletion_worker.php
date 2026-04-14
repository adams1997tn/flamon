<?php
$appRoot = dirname(__DIR__);
require_once $appRoot . '/includes/inc.php';

$envToken = getenv('ACCOUNT_DELETION_WORKER_TOKEN') ?: '';
$dbToken = '';
$workerStatus = method_exists($iN, 'iN_GetSetting')
	? (string)$iN->iN_GetSetting('account_deletion_worker_status', '1')
	: '1';
$workerStatus = $workerStatus === '0' ? '0' : '1';
if ($envToken === '' && method_exists($iN, 'iN_GetSetting')) {
	$dbToken = (string)$iN->iN_GetSetting('account_deletion_worker_token', '');
}
$configuredToken = $envToken !== '' ? $envToken : $dbToken;
$providedToken = '';
if (PHP_SAPI !== 'cli') {
	$providedToken = isset($_GET['token']) ? (string)$_GET['token'] : '';
	if (empty($providedToken) && isset($_SERVER['HTTP_X_CRON_TOKEN'])) {
		$providedToken = (string)$_SERVER['HTTP_X_CRON_TOKEN'];
	}
	if ($workerStatus !== '1') {
		http_response_code(403);
		exit('disabled');
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

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
if ($limit < 1) {
	$limit = 25;
}
if ($limit > 250) {
	$limit = 250;
}

if ($workerStatus !== '1') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode([
		'status' => 'disabled',
		'processed' => 0,
		'failed' => 0,
		'time' => time()
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = ['processed' => 0, 'failed' => 0];
if (method_exists($iN, 'iN_ProcessDueAccountDeletions')) {
	$result = $iN->iN_ProcessDueAccountDeletions($limit);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
	'status' => 'ok',
	'processed' => (int)($result['processed'] ?? 0),
	'failed' => (int)($result['failed'] ?? 0),
	'time' => time()
], JSON_UNESCAPED_UNICODE);
exit;
