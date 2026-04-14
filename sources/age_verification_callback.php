<?php
if (!isset($iN)) {
    include_once __DIR__ . '/../includes/inc.php';
}

if ($logedIn != 1) {
    header('Location: ' . route_url(''));
    exit;
}

if (!function_exists('age_verif_clear_state')) {
    function age_verif_clear_state(): void {
        unset(
            $_SESSION['ageverif_state'],
            $_SESSION['ageverif_user_id'],
            $_SESSION['ageverif_time'],
            $_SESSION['ageverif_provider'],
            $_SESSION['ageverif_session_id'],
            $_SESSION['ageverif_sim_status']
        );
    }
}

if (!function_exists('age_verif_set_flash')) {
    function age_verif_set_flash(string $message, string $type = 'error'): void {
        $_SESSION['ageverif_flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('age_verif_http_request')) {
    function age_verif_http_request(string $url, array $headers = [], ?string $body = null): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'ok' => $error === '',
            'status' => $httpCode,
            'body' => $response !== false ? (string)$response : '',
            'error' => $error
        ];
    }
}

if (!function_exists('age_verif_find_field')) {
    function age_verif_find_field($payload, string $field, bool &$found) {
        if (is_array($payload)) {
            foreach ($payload as $key => $value) {
                if ($key === $field) {
                    $found = true;
                    return $value;
                }
                if (is_array($value)) {
                    $nested = age_verif_find_field($value, $field, $found);
                    if ($found) {
                        return $nested;
                    }
                }
            }
        }
        return null;
    }
}

if (!function_exists('age_verif_table_has_columns')) {
    function age_verif_table_has_columns(string $table, array $columns): bool {
        try {
            $table = trim($table);
            if ($table === '' || empty($columns)) {
                return false;
            }
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $params = array_merge([$table], $columns);
            $count = DB::col(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN ($placeholders)",
                $params
            );
            return (int)$count === count($columns);
        } catch (Throwable $e) {
            return false;
        }
    }
}

$errorParam = isset($_GET['error']) ? trim((string)$_GET['error']) : '';
if ($errorParam !== '') {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_denied']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

$state = isset($_GET['state']) ? trim((string)$_GET['state']) : '';
$code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
$sessionState = isset($_SESSION['ageverif_state']) ? (string)$_SESSION['ageverif_state'] : '';
$sessionUserId = isset($_SESSION['ageverif_user_id']) ? (int)$_SESSION['ageverif_user_id'] : 0;
$sessionTime = isset($_SESSION['ageverif_time']) ? (int)$_SESSION['ageverif_time'] : 0;
$sessionProvider = isset($_SESSION['ageverif_provider']) ? (string)$_SESSION['ageverif_provider'] : '';
$sessionYotiId = isset($_SESSION['ageverif_session_id']) ? trim((string)$_SESSION['ageverif_session_id']) : '';
$callbackSessionId = isset($_GET['sessionId']) ? trim((string)$_GET['sessionId']) : '';
$activeProvider = isset($ageVerificationProvider) ? (string)$ageVerificationProvider : 'ageverif';
$allowedProviders = isset($ageVerificationProviders) && is_array($ageVerificationProviders)
    ? $ageVerificationProviders
    : ['ageverif', 'yoti', 'didit'];
$ttlSeconds = 15 * 60;
$simEnabled = filter_var(ini_get('dizzy_ageverif_sim'), FILTER_VALIDATE_BOOLEAN);
$simStatus = '';
if ($simEnabled) {
    $simStatusRaw = isset($_GET['sim_status']) ? (string)$_GET['sim_status'] : (string)($_SESSION['ageverif_sim_status'] ?? '');
    $simStatusRaw = strtolower(trim($simStatusRaw));
    $simAllowed = ['approved', 'declined', 'in_progress', 'expired', 'abandoned', 'failed', 'success'];
    $simStatus = in_array($simStatusRaw, $simAllowed, true) ? $simStatusRaw : 'approved';
}

if ($activeProvider === 'didit') {
    if ($sessionProvider !== 'didit' || !in_array($activeProvider, $allowedProviders, true)) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    if ($sessionUserId !== (int)$userID || $sessionTime <= 0 || (time() - $sessionTime) > $ttlSeconds) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    $callbackDiditSessionId = isset($_GET['session_id']) ? trim((string)$_GET['session_id']) : '';
    if ($callbackDiditSessionId === '' && isset($_GET['sessionId'])) {
        $callbackDiditSessionId = trim((string)$_GET['sessionId']);
    }
    $sessionDiditId = $sessionYotiId !== '' ? $sessionYotiId : $callbackDiditSessionId;
    if ($sessionDiditId === '') {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    if ($callbackDiditSessionId !== '' && $sessionYotiId !== '' && !hash_equals($sessionYotiId, $callbackDiditSessionId)) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    if ($ageVerificationStatus !== '1' || $ageVerificationClientId === '') {
        $configMessage = $LANG['age_verification_error_config_incomplete'];
        if (in_array((string)$userType, ['2', '3'], true) && isset($LANG['age_verification_error_config_incomplete_admin'])) {
            $configMessage = $LANG['age_verification_error_config_incomplete_admin'];
        }
        age_verif_clear_state();
        age_verif_set_flash($configMessage);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    $configColumnsOk = false;
    if (method_exists($iN, 'iN_ConfigColumnsExist')) {
        $providerColumns = [
            'didit' => [
                'age_verif_provider',
                'didit_age_verif_status',
                'didit_age_verif_force_sitewide',
                'didit_age_verif_client_id',
                'didit_age_verif_client_secret',
                'didit_age_verif_authorize_url',
                'didit_age_verif_token_url',
                'didit_age_verif_verify_url',
                'didit_age_verif_scope',
                'didit_age_verif_environment',
                'didit_age_verif_client_id_test',
                'didit_age_verif_client_secret_test',
                'didit_age_verif_authorize_url_test',
                'didit_age_verif_token_url_test',
                'didit_age_verif_verify_url_test',
                'didit_age_verif_scope_test',
                'didit_age_verif_min_age'
            ]
        ];
        $configColumnsOk = $iN->iN_ConfigColumnsExist($providerColumns['didit']);
    }
    $userColumnsOk = age_verif_table_has_columns('i_users', [
        'age_verify_status',
        'age_verify_provider',
        'age_verified_at',
        'age_verify_ref'
    ]);
    if (!$configColumnsOk || !$userColumnsOk) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_columns_missing']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    $decisionStatus = '';
    if ($simEnabled) {
        if ($simStatus === 'approved' || $simStatus === 'success') {
            $decisionStatus = 'APPROVED';
        } elseif ($simStatus === 'in_progress') {
            $decisionStatus = 'IN_PROGRESS';
        } else {
            $decisionStatus = 'DECLINED';
        }
    } else {
        $baseUrlOverride = isset($diditAgeVerifTokenUrl) ? trim((string)$diditAgeVerifTokenUrl) : '';
        $diditBaseUrl = 'https://verification.didit.me/v3';
        if ($baseUrlOverride !== '' && filter_var($baseUrlOverride, FILTER_VALIDATE_URL)) {
            $diditBaseUrl = rtrim($baseUrlOverride, '/');
        }
        $decisionUrl = rtrim($diditBaseUrl, '/') . '/session/' . rawurlencode($sessionDiditId) . '/decision';
        $decisionResponse = age_verif_http_request(
            $decisionUrl,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Api-Key: ' . $ageVerificationClientId
            ]
        );
        if (!$decisionResponse['ok'] || $decisionResponse['status'] >= 400 || $decisionResponse['body'] === '') {
            age_verif_clear_state();
            age_verif_set_flash($LANG['age_verification_error_generic']);
            header('Location: ' . route_url('age-verification'));
            exit;
        }
        $decisionData = json_decode($decisionResponse['body'], true);
        if (!is_array($decisionData)) {
            age_verif_clear_state();
            age_verif_set_flash($LANG['age_verification_error_generic']);
            header('Location: ' . route_url('age-verification'));
            exit;
        }
        if (isset($decisionData['status'])) {
            $decisionStatus = (string)$decisionData['status'];
        } else {
            $statusFound = false;
            $statusValue = age_verif_find_field($decisionData, 'status', $statusFound);
            if ($statusFound) {
                $decisionStatus = (string)$statusValue;
            }
        }
    }
    $decisionStatus = strtoupper(trim($decisionStatus));
    $decisionStatus = str_replace(' ', '_', $decisionStatus);
    if ($decisionStatus === '') {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_generic']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    $approvedStatuses = ['APPROVED', 'VERIFIED', 'SUCCESS'];
    $inProgressStatuses = ['NOT_STARTED', 'IN_PROGRESS', 'IN_REVIEW', 'PENDING'];
    $failedStatuses = ['DECLINED', 'REJECTED', 'FAILED', 'ABANDONED', 'EXPIRED', 'CANCELED', 'CANCELLED'];
    $isApproved = in_array($decisionStatus, $approvedStatuses, true);
    $isInProgress = in_array($decisionStatus, $inProgressStatuses, true);
    $isFailed = in_array($decisionStatus, $failedStatuses, true);
    if (!$isApproved && !$isInProgress && !$isFailed) {
        $isInProgress = true;
    }

    if ($isApproved) {
        $verifiedAt = time();
        DB::exec(
            "UPDATE i_users SET age_verify_status = '1', age_verify_provider = ?, age_verified_at = ?, age_verify_ref = ? WHERE iuid = ?",
            ['didit', $verifiedAt, $sessionDiditId, (int)$userID]
        );

        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_success'], 'success');

        $settingsUrl = function_exists('route_url')
            ? route_url('settings?tab=preferences')
            : rtrim((string)$base_url, '/') . '/settings?tab=preferences';

        header('Location: ' . $settingsUrl);
        exit;
    }

    $currentStatusRow = DB::one(
        "SELECT age_verify_status, age_verify_provider FROM i_users WHERE iuid = ? LIMIT 1",
        [(int)$userID]
    );
    if ($currentStatusRow && (string)($currentStatusRow['age_verify_status'] ?? '0') === '1' && (string)($currentStatusRow['age_verify_provider'] ?? '') === 'didit') {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_success'], 'success');

        $settingsUrl = function_exists('route_url')
            ? route_url('settings?tab=preferences')
            : rtrim((string)$base_url, '/') . '/settings?tab=preferences';

        header('Location: ' . $settingsUrl);
        exit;
    }

    DB::exec(
        "UPDATE i_users SET age_verify_status = '0', age_verify_provider = ?, age_verified_at = NULL, age_verify_ref = ? WHERE iuid = ?",
        ['didit', $sessionDiditId, (int)$userID]
    );

    age_verif_clear_state();
    if ($isInProgress) {
        $progressMessage = $LANG['age_verification_status_in_progress'] ?? $LANG['age_verification_error_generic'];
        age_verif_set_flash($progressMessage);
    } else {
        age_verif_set_flash($LANG['age_verification_error_denied']);
    }
    header('Location: ' . route_url('age-verification'));
    exit;
}

if ($activeProvider === 'yoti') {
    if ($sessionProvider !== 'yoti' || !in_array($activeProvider, $allowedProviders, true)) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    if ($sessionUserId !== (int)$userID || $sessionTime <= 0 || (time() - $sessionTime) > $ttlSeconds) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    if ($callbackSessionId === '' || $sessionYotiId === '' || !hash_equals($sessionYotiId, $callbackSessionId)) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_invalid_state']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    if ($ageVerificationStatus !== '1' || $ageVerificationClientId === '' || $ageVerificationClientSecret === '') {
        $configMessage = $LANG['age_verification_error_config_incomplete'];
        if (in_array((string)$userType, ['2', '3'], true) && isset($LANG['age_verification_error_config_incomplete_admin'])) {
            $configMessage = $LANG['age_verification_error_config_incomplete_admin'];
        }
        age_verif_clear_state();
        age_verif_set_flash($configMessage);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    $configColumnsOk = false;
    if (method_exists($iN, 'iN_ConfigColumnsExist')) {
        $providerColumns = [
            'yoti' => [
                'age_verif_provider',
                'yoti_age_verif_status',
                'yoti_age_verif_force_sitewide',
                'yoti_age_verif_client_id',
                'yoti_age_verif_client_secret',
                'yoti_age_verif_authorize_url',
                'yoti_age_verif_token_url',
                'yoti_age_verif_verify_url',
                'yoti_age_verif_scope',
                'yoti_age_verif_environment',
                'yoti_age_verif_client_id_test',
                'yoti_age_verif_client_secret_test',
                'yoti_age_verif_authorize_url_test',
                'yoti_age_verif_token_url_test',
                'yoti_age_verif_verify_url_test',
                'yoti_age_verif_scope_test',
                'yoti_age_verif_min_age'
            ]
        ];
        $configColumnsOk = $iN->iN_ConfigColumnsExist($providerColumns['yoti']);
    }
    $userColumnsOk = age_verif_table_has_columns('i_users', [
        'age_verify_status',
        'age_verify_provider',
        'age_verified_at',
        'age_verify_ref'
    ]);
    if (!$configColumnsOk || !$userColumnsOk) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_columns_missing']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    $resultStatus = '';
    if ($simEnabled) {
        if ($simStatus === 'approved' || $simStatus === 'success') {
            $resultStatus = 'COMPLETE';
        } elseif ($simStatus === 'in_progress') {
            $resultStatus = 'IN_PROGRESS';
        } else {
            $resultStatus = 'FAIL';
        }
    } else {
        $resultUrl = 'https://age.yoti.com/api/v1/sessions/' . rawurlencode($callbackSessionId) . '/result';
        $resultResponse = age_verif_http_request(
            $resultUrl,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $ageVerificationClientSecret,
                'Yoti-SDK-Id: ' . $ageVerificationClientId
            ]
        );
        if (!$resultResponse['ok'] || $resultResponse['status'] >= 400 || $resultResponse['body'] === '') {
            age_verif_clear_state();
            age_verif_set_flash($LANG['age_verification_error_generic']);
            header('Location: ' . route_url('age-verification'));
            exit;
        }

        $resultData = json_decode($resultResponse['body'], true);
        if (!is_array($resultData)) {
            age_verif_clear_state();
            age_verif_set_flash($LANG['age_verification_error_generic']);
            header('Location: ' . route_url('age-verification'));
            exit;
        }

        $resultStatus = strtoupper(trim((string)($resultData['status'] ?? '')));
    }
    if ($resultStatus === 'COMPLETE') {
        $providerHost = $activeProvider;
        $verifiedAt = time();
        DB::exec(
            "UPDATE i_users SET age_verify_status = '1', age_verify_provider = ?, age_verified_at = ?, age_verify_ref = NULL WHERE iuid = ?",
            [$providerHost, $verifiedAt, (int)$userID]
        );

        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_success'], 'success');

        $settingsUrl = function_exists('route_url')
            ? route_url('settings?tab=preferences')
            : rtrim((string)$base_url, '/') . '/settings?tab=preferences';

        header('Location: ' . $settingsUrl);
        exit;
    }

    if ($resultStatus === 'IN_PROGRESS') {
        age_verif_clear_state();
        $progressMessage = $LANG['age_verification_status_in_progress'] ?? $LANG['age_verification_error_generic'];
        age_verif_set_flash($progressMessage);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    if ($resultStatus === 'FAIL') {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_denied']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }

    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_generic']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

if ($state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_invalid_state']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

if ($sessionUserId !== (int)$userID || $sessionTime <= 0 || (time() - $sessionTime) > $ttlSeconds) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_invalid_state']);
    header('Location: ' . route_url('age-verification'));
    exit;
}
if ($sessionProvider === '' || !in_array($activeProvider, $allowedProviders, true) || $sessionProvider !== $activeProvider) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_invalid_state']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

if ($code === '') {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_generic']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

if ($ageVerificationStatus !== '1' || $ageVerificationClientId === '' || $ageVerificationClientSecret === '' || $ageVerificationTokenUrl === '') {
    $configMessage = $LANG['age_verification_error_config_incomplete'];
    if (in_array((string)$userType, ['2', '3'], true) && isset($LANG['age_verification_error_config_incomplete_admin'])) {
        $configMessage = $LANG['age_verification_error_config_incomplete_admin'];
    }
    age_verif_clear_state();
    age_verif_set_flash($configMessage);
    header('Location: ' . route_url('age-verification'));
    exit;
}

$configColumnsOk = false;
if (method_exists($iN, 'iN_ConfigColumnsExist')) {
    $providerColumns = [
        'ageverif' => [
            'age_verif_provider',
            'age_verif_status',
            'age_verif_force_sitewide',
            'age_verif_client_id',
            'age_verif_client_secret',
            'age_verif_authorize_url',
            'age_verif_token_url',
            'age_verif_verify_url',
            'age_verif_scope',
            'age_verif_environment',
            'age_verif_client_id_test',
            'age_verif_client_secret_test',
            'age_verif_authorize_url_test',
            'age_verif_token_url_test',
            'age_verif_verify_url_test',
            'age_verif_scope_test',
            'age_verif_min_age'
        ],
        'yoti' => [
            'age_verif_provider',
            'yoti_age_verif_status',
            'yoti_age_verif_force_sitewide',
            'yoti_age_verif_client_id',
            'yoti_age_verif_client_secret',
            'yoti_age_verif_authorize_url',
            'yoti_age_verif_token_url',
            'yoti_age_verif_verify_url',
            'yoti_age_verif_scope',
            'yoti_age_verif_environment',
            'yoti_age_verif_client_id_test',
            'yoti_age_verif_client_secret_test',
            'yoti_age_verif_authorize_url_test',
            'yoti_age_verif_token_url_test',
            'yoti_age_verif_verify_url_test',
            'yoti_age_verif_scope_test',
            'yoti_age_verif_min_age'
        ],
        'didit' => [
            'age_verif_provider',
            'didit_age_verif_status',
            'didit_age_verif_force_sitewide',
            'didit_age_verif_client_id',
            'didit_age_verif_client_secret',
            'didit_age_verif_authorize_url',
            'didit_age_verif_token_url',
            'didit_age_verif_verify_url',
            'didit_age_verif_scope',
            'didit_age_verif_environment',
            'didit_age_verif_client_id_test',
            'didit_age_verif_client_secret_test',
            'didit_age_verif_authorize_url_test',
            'didit_age_verif_token_url_test',
            'didit_age_verif_verify_url_test',
            'didit_age_verif_scope_test',
            'didit_age_verif_min_age'
        ]
    ];
    $configColumnsOk = $iN->iN_ConfigColumnsExist($providerColumns[$activeProvider] ?? []);
}
$userColumnsOk = age_verif_table_has_columns('i_users', [
    'age_verify_status',
    'age_verify_provider',
    'age_verified_at',
    'age_verify_ref'
]);
if (!$configColumnsOk || !$userColumnsOk) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_columns_missing']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

$redirectUri = function_exists('route_url')
    ? route_url('age-verification-callback')
    : rtrim((string)$base_url, '/') . '/age-verification-callback';

$tokenPayload = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri,
    'client_id' => $ageVerificationClientId,
    'client_secret' => $ageVerificationClientSecret
], '', '&', PHP_QUERY_RFC3986);

$tokenResponse = age_verif_http_request(
    $ageVerificationTokenUrl,
    [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded'
    ],
    $tokenPayload
);

if (!$tokenResponse['ok'] || $tokenResponse['status'] >= 400 || $tokenResponse['body'] === '') {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_generic']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

$tokenData = json_decode($tokenResponse['body'], true);
if (!is_array($tokenData)) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_generic']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

if (isset($tokenData['error'])) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_denied']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

$verificationPayload = $tokenData;

if (trim((string)$ageVerificationVerifyUrl) !== '') {
    $accessToken = isset($tokenData['access_token']) ? trim((string)$tokenData['access_token']) : '';
    $tokenType = isset($tokenData['token_type']) ? trim((string)$tokenData['token_type']) : 'Bearer';
    if ($accessToken === '') {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_no_verification_data']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    $verifyResponse = age_verif_http_request(
        $ageVerificationVerifyUrl,
        [
            'Accept: application/json',
            'Authorization: ' . $tokenType . ' ' . $accessToken
        ]
    );
    if (!$verifyResponse['ok'] || $verifyResponse['status'] >= 400 || $verifyResponse['body'] === '') {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_generic']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    $verifyData = json_decode($verifyResponse['body'], true);
    if (!is_array($verifyData)) {
        age_verif_clear_state();
        age_verif_set_flash($LANG['age_verification_error_generic']);
        header('Location: ' . route_url('age-verification'));
        exit;
    }
    $verificationPayload = $verifyData;
}

$ageFound = false;
$ageValue = age_verif_find_field($verificationPayload, 'age', $ageFound);
$is18Found = false;
$is18Value = age_verif_find_field($verificationPayload, 'is_18plus', $is18Found);
$verifiedFound = false;
$verifiedValue = age_verif_find_field($verificationPayload, 'verified', $verifiedFound);

$minAge = (int)$ageVerificationMinAge;
if ($minAge < 18) {
    $minAge = 18;
}

$isVerified = null;
$hasVerificationData = false;

if ($ageFound && is_numeric($ageValue)) {
    $hasVerificationData = true;
    $isVerified = (int)$ageValue >= $minAge;
} elseif ($is18Found) {
    $hasVerificationData = true;
    $boolVal = filter_var($is18Value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($boolVal !== null && $minAge <= 18) {
        $isVerified = $boolVal;
    }
} elseif ($verifiedFound) {
    $hasVerificationData = true;
    $boolVal = filter_var($verifiedValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($boolVal !== null && $minAge <= 18) {
        $isVerified = $boolVal;
    }
}

if (!$hasVerificationData || $isVerified === null) {
    age_verif_clear_state();
    $noDataMessage = $LANG['age_verification_error_unrecognized_data'] ?? $LANG['age_verification_error_no_verification_data'];
    age_verif_set_flash($noDataMessage);
    header('Location: ' . route_url('age-verification'));
    exit;
}

if ($isVerified !== true) {
    age_verif_clear_state();
    age_verif_set_flash($LANG['age_verification_error_denied']);
    header('Location: ' . route_url('age-verification'));
    exit;
}

$providerHost = $activeProvider;
$verifiedAt = time();

DB::exec(
    "UPDATE i_users SET age_verify_status = '1', age_verify_provider = ?, age_verified_at = ?, age_verify_ref = NULL WHERE iuid = ?",
    [$providerHost, $verifiedAt, (int)$userID]
);

age_verif_clear_state();
age_verif_set_flash($LANG['age_verification_success'], 'success');

$settingsUrl = function_exists('route_url')
    ? route_url('settings?tab=preferences')
    : rtrim((string)$base_url, '/') . '/settings?tab=preferences';

header('Location: ' . $settingsUrl);
exit;
?>
