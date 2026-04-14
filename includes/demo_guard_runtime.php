<?php
/**
 * Backward compatibility wrapper.
 * Runtime now lives in demo_guard.php (single-file management).
 */

include_once __DIR__ . '/demo_guard.php';

if (!function_exists('demo_guard_runtime_init')) {
    return;
}

$demoGuardMessage = demo_guard_runtime_init(
    isset($userID) ? (int)$userID : null,
    $pdo ?? null
);
