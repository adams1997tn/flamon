<?php
chdir(__DIR__);
require 'includes/connect.php';
require 'includes/db.php';
if (isset($pdo)) DB::init($pdo);
require 'includes/functions.php';
require 'includes/payment/KonnectService.php';

echo "[konnect] running migration + activation...\n";

$iN = new iN_UPDATES($db);
$rm = new ReflectionMethod($iN, 'iN_EnsureKonnectColumns');
$rm->setAccessible(true);
$rm->invoke($iN);
echo "[konnect] i_payment_methods columns ensured\n";

KonnectService::ensureUserPaymentsColumns();
echo "[konnect] i_user_payments helper columns ensured\n";

// Activate test mode + sane defaults (does NOT overwrite existing keys).
$row = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1");
$updates = [];
$params  = [];
if (($row['konnect_payment_mode']  ?? '0') !== '0') { $updates[] = 'konnect_payment_mode = ?';  $params[] = '0'; }
if (($row['konnect_active_pasive'] ?? '0') !== '1') { $updates[] = 'konnect_active_pasive = ?'; $params[] = '1'; }
if (($row['konnect_currency']      ?? '')  === '' ||
    ($row['konnect_currency']      ?? '')  === '0') { $updates[] = 'konnect_currency = ?';      $params[] = 'TND'; }
if (empty($row['konnect_test_api_key']))            { $updates[] = 'konnect_test_api_key = ?';   $params[] = 'PLACEHOLDER_TEST_API_KEY'; }
if (empty($row['konnect_test_wallet_id']))          { $updates[] = 'konnect_test_wallet_id = ?'; $params[] = 'PLACEHOLDER_TEST_WALLET_ID'; }
if (empty($row['konnect_webhook_secret']))          { $updates[] = 'konnect_webhook_secret = ?'; $params[] = bin2hex(random_bytes(16)); }

if ($updates) {
    $params[] = 1;
    DB::exec("UPDATE i_payment_methods SET " . implode(', ', $updates) . " WHERE payment_method_id = ?", $params);
    echo "[konnect] activation/test-mode applied\n";
} else {
    echo "[konnect] already activated, no changes\n";
}

$row = DB::one("SELECT konnect_payment_mode, konnect_active_pasive, konnect_currency, konnect_test_api_key, konnect_test_wallet_id, konnect_webhook_secret FROM i_payment_methods WHERE payment_method_id = 1");
echo "[konnect] current configuration:\n";
foreach ($row as $k => $v) {
    if (in_array($k, ['konnect_test_api_key', 'konnect_webhook_secret'], true)) {
        $v = $v ? substr((string)$v, 0, 6) . '...' . substr((string)$v, -4) : '(empty)';
    }
    echo "  $k = $v\n";
}

// Make sure 'konnect' is allowed by payment_option enum (or that column is now VARCHAR).
try {
    $col = DB::one("SHOW COLUMNS FROM i_user_payments LIKE 'payment_option'");
    if ($col && stripos($col['Type'], 'enum') === 0) {
        if (stripos($col['Type'], "'konnect'") === false) {
            // Widen to VARCHAR so it auto-accepts 'konnect'.
            DB::exec("ALTER TABLE i_user_payments MODIFY COLUMN payment_option VARCHAR(32) NOT NULL");
            echo "[konnect] payment_option enum widened to VARCHAR(32)\n";
        }
    }
} catch (Throwable $e) {
    echo "[konnect] enum check error: " . $e->getMessage() . "\n";
}

echo "[konnect] DONE\n";
