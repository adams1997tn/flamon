<?php
/**
 * DIZZY: Points → Currency-Only Migration Script
 * ================================================
 * Run this ONCE via browser: https://yoursite.com/admin/migrate_currency_only.php
 * Then DELETE this file immediately after successful run.
 *
 * What this does:
 * 1. Reads the current one_point conversion rate
 * 2. Converts all user wallet_points balances from points to currency
 * 3. Normalizes premium plan packages (plan_amount = amount in $)
 * 4. Normalizes live gift packages
 * 5. Converts creator subscription plan pricing to currency
 * 6. Alters i_user_subscribe_plans.amount to decimal
 * 7. Forces currency-only mode in configuration
 * 8. Disables activity point earnings
 */

// Require admin authentication
include_once __DIR__ . "/../includes/inc.php";
if (!isset($logedIn) || $logedIn != 1 || !isset($userType) || $userType != '2') {
    die('Access denied. Admin login required.');
}

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>Dizzy: Points → Currency-Only Migration</h1><pre>";

try {
    // Step 0: Read current conversion rate
    $configRow = DB::one("SELECT one_point, subscription_type FROM i_configurations WHERE configuration_id = 1 LIMIT 1");
    $currentRate = (float)($configRow['one_point'] ?? 0.1);
    $currentSubType = $configRow['subscription_type'] ?? '1';
    echo "Current one_point rate: $currentRate\n";
    echo "Current subscription_type: $currentSubType\n\n";

    if ($currentRate <= 0) {
        die("ERROR: one_point is zero or negative. Cannot proceed.");
    }

    // Step 1: Backup check - count affected users
    $usersWithPoints = DB::col("SELECT COUNT(*) FROM i_users WHERE CAST(wallet_points AS DECIMAL(15,2)) > 0");
    echo "Users with positive wallet_points: $usersWithPoints\n";

    // Step 2: Convert user wallet_points from points to currency
    // Formula: new_balance = old_points * one_point_rate
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_users SET wallet_points = ROUND(CAST(wallet_points AS DECIMAL(15,4)) * ?, 2) WHERE CAST(wallet_points AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected user balances (multiplied by $currentRate)\n";
    } else {
        echo "Rate is already 1.0 — no balance conversion needed\n";
    }

    // Step 3: Normalize premium top-up packages (plan_amount = dollar price)
    $affected = DB::exec("UPDATE i_premium_plans SET plan_amount = amount");
    echo "Normalized $affected premium plan packages (plan_amount = amount in \$)\n";

    // Step 4: Normalize live gift packages
    $affected = DB::exec("UPDATE i_live_gift_point SET gift_point = gift_money_equal WHERE gift_money_equal IS NOT NULL AND gift_money_equal != ''");
    echo "Normalized $affected live gift packages\n";

    // Step 5: Alter subscription plans column to decimal
    DB::exec("ALTER TABLE i_user_subscribe_plans MODIFY COLUMN amount DECIMAL(10,2) DEFAULT 0.00");
    echo "Altered i_user_subscribe_plans.amount to DECIMAL(10,2)\n";

    // Step 6: Convert existing subscription plan prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_user_subscribe_plans SET amount = ROUND(amount * ?, 2) WHERE amount > 0",
            [$currentRate]
        );
        echo "Converted $affected subscription plan prices to currency\n";
    }

    // Step 7: Force currency-only mode
    DB::exec("UPDATE i_configurations SET one_point = '1', subscription_type = '1' WHERE configuration_id = 1");
    echo "Set one_point = 1, subscription_type = 1 (currency-only mode)\n";

    // Step 8: Disable activity point earnings
    DB::exec("UPDATE i_configuration_affilate SET i_af_status = 'no'");
    echo "Disabled all activity point earning rules\n";

    // Step 9: Convert affiliate earnings to currency too
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_users SET affilate_earnings = ROUND(CAST(affilate_earnings AS DECIMAL(15,4)) * ?, 2) WHERE CAST(affilate_earnings AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected affiliate earnings to currency\n";
    }

    // Step 10: Convert post prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_posts SET post_wanted_credit = ROUND(CAST(post_wanted_credit AS DECIMAL(15,4)) * ?, 2) WHERE CAST(post_wanted_credit AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected post prices to currency\n";
    }

    // Step 11: Convert live stream prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_live_streamings SET live_credit = ROUND(CAST(live_credit AS DECIMAL(15,4)) * ?, 2) WHERE CAST(live_credit AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected live stream prices to currency\n";
    }

    // Step 12: Convert video call prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_users SET video_call_price = ROUND(CAST(video_call_price AS DECIMAL(15,4)) * ?, 2) WHERE CAST(video_call_price AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected video call prices to currency\n";
    }

    // Step 13: Convert frame prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_user_frames_data SET f_price = ROUND(CAST(f_price AS DECIMAL(15,4)) * ?, 2) WHERE CAST(f_price AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected frame prices to currency\n";
    }

    // Step 14: Convert chat message prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_chat_conversations SET private_price = ROUND(CAST(private_price AS DECIMAL(15,4)) * ?, 2) WHERE CAST(private_price AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected chat message prices to currency\n";
    }

    // Step 15: Convert boost plan prices from points to currency
    if ($currentRate != 1.0) {
        $affected = DB::exec(
            "UPDATE i_boost_plans SET plan_amount = ROUND(CAST(plan_amount AS DECIMAL(15,4)) * ?, 2) WHERE CAST(plan_amount AS DECIMAL(15,2)) > 0",
            [$currentRate]
        );
        echo "Converted $affected boost plan prices to currency\n";
    }

    echo "\n========================================\n";
    echo "MIGRATION COMPLETE\n";
    echo "========================================\n";
    echo "\nNEXT STEPS:\n";
    echo "1. Deploy the code patches (inc.php, functions.php, request.php, etc.)\n";
    echo "2. Clear any caches\n";
    echo "3. DELETE THIS FILE: admin/migrate_currency_only.php\n";
    echo "4. Test all payment flows\n";

} catch (Throwable $e) {
    echo "\n\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\nMigration may be partially applied. Check data carefully.\n";
}
echo "</pre>";
