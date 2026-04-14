<?php
// Lightweight cron entrypoint for bulk messaging campaigns
$appRoot = dirname(__DIR__);
require_once $appRoot . '/includes/inc.php';

$envToken = getenv('BULK_CAMPAIGN_TOKEN') ?: '';
$providedToken = '';
if (PHP_SAPI !== 'cli') {
    $providedToken = isset($_GET['token']) ? (string)$_GET['token'] : '';
    if (empty($providedToken) && isset($_SERVER['HTTP_X_CRON_TOKEN'])) {
        $providedToken = (string)$_SERVER['HTTP_X_CRON_TOKEN'];
    }
    if ($envToken !== '') {
        if ($providedToken === '' || !hash_equals($envToken, $providedToken)) {
            http_response_code(403);
            exit('forbidden');
        }
    } elseif ($providedToken === '') {
        http_response_code(403);
        exit('forbidden');
    }
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1) { $limit = 10; }

$campaigns = DB::all(
    "SELECT bc_id, rate_limit_per_run FROM i_bulk_campaigns WHERE status IN('queued','sending') ORDER BY bc_id ASC LIMIT $limit"
);

$processed = 0;
if (!empty($campaigns)) {
    foreach ($campaigns as $campaign) {
        $campaignId = (int)($campaign['bc_id'] ?? 0);
        if ($campaignId <= 0) {
            continue;
        }
        $perRun = isset($campaign['rate_limit_per_run']) ? (int)$campaign['rate_limit_per_run'] : 25;
        if ($perRun < 1) {
            $perRun = 25;
        }
        $iN->iN_ProcessBulkCampaign($campaignId, $perRun);
        $processed++;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'processed' => $processed
]);
