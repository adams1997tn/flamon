<?php
// Load core application configuration and required constants
include "../includes/inc.php";

$agencyModuleEnabled = (isset($agencyModuleStatus) ? $agencyModuleStatus : 'yes') === 'yes';

// Backward-compatible defaults for optional web push settings.
$webPushStatus = isset($webPushStatus) ? (string) $webPushStatus : '0';
$webPushVapidPublic = isset($webPushVapidPublic) ? (string) $webPushVapidPublic : '';
$webPushEventLiveStarted = isset($webPushEventLiveStarted) ? (string) $webPushEventLiveStarted : '0';
$webPushEventPostLike = isset($webPushEventPostLike) ? (string) $webPushEventPostLike : '0';
$webPushEventComment = isset($webPushEventComment) ? (string) $webPushEventComment : '0';
$webPushEventFollow = isset($webPushEventFollow) ? (string) $webPushEventFollow : '0';
$webPushEventSubscription = isset($webPushEventSubscription) ? (string) $webPushEventSubscription : '0';
$webPushEventTipReceived = isset($webPushEventTipReceived) ? (string) $webPushEventTipReceived : '0';

// Keep last seen fresh for AJAX flows (prevents false "Offline" labels on newly rendered content)
if (isset($logedIn) && (int) $logedIn === 1 && isset($iN, $userID) && (int) $userID > 0) {
    $now = time();
    $previousLastSeen = isset($lastLoginTime) ? (int) $lastLoginTime : 0;
    if ($previousLastSeen <= 0 || ($now - $previousLastSeen) >= 60) {
        $iN->iN_UpdateLastSeen((int) $userID);
        $lastLoginTime = $now;
    }
}

// Include thumbnail cropping helper (used for image resizing/cropping logic)
include "../includes/thumbncrop.inc.php";
// Image resize/compress utilities (smart_resize_image)
include_once "../includes/compressImage.php";

// Keep legacy provider-specific classes available while refactoring
if ($digitalOceanStatus == '1') {
    // @include_once "../includes/spaces/spaces.php"; // legacy DO; disabled after unification
}
// Initialize a shared S3-compatible client for existing calls
if (!isset($s3)) { $s3 = storage_client(); }


// Include image filtering class (used for contrast, brightness, grayscale etc.)
include "../includes/imageFilter.php";

// Import the ImageFilter class from its namespace
use imageFilter\ImageFilter;

/* -------------------------------------------
 | Email Delivery: PHPMailer Integration
 --------------------------------------------*/

// Import PHPMailer classes into global scope
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Service\CcbillService;
use App\Service\EpochService;
use App\Service\FlutterwaveService;
use App\Service\IyzicoService;
use App\Service\YooKassaService;

// Load PHPMailer via Composer (make sure it's installed)
require '../includes/phpmailer/vendor/autoload.php';

// Instantiate PHPMailer (true enables exception handling)
$mail = new PHPMailer(true);

if (!function_exists('iN_safeMailSend')) {
    function iN_safeMailSend(PHPMailer $mail, string $mode, string $context = 'mail'): bool {
        try {
            return $mail->send();
        } catch (\Exception $e) {
            error_log('[MAIL] ' . $context . ' SMTP failure: ' . $e->getMessage());
            if ($mode === 'smtp') {
                try {
                    $mail->smtpClose();
                    $mail->isMail();
                    return $mail->send();
                } catch (Exception $inner) {
                    error_log('[MAIL] ' . $context . ' mail() fallback failure: ' . $inner->getMessage());
                }
            }
        }
        return false;
    }
}

/* -------------------------------------------
 | Define Application-Wide Constants
 --------------------------------------------*/

// Ensure AJAX responses are clean (no leading BOM/whitespace from includes)
if (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_clean();
}

// Lightweight debug logger for this request file
if (!function_exists('rq_debug')) {
    function rq_debug($msg, $ctx = []) {
        $log = __DIR__ . '/../includes/request_debug.log';
        $time = date('c');
        $line = '[' . $time . '] ' . $msg;
        if (!empty($ctx)) {
            $line .= ' | ' . json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $line .= "\n";
        @file_put_contents($log, $line, FILE_APPEND);
    }
}

/**
 * Normalize mixed-format money strings (1,200.50 or 1200,50) into a float.
 */
if (!function_exists('rq_parse_amount_value')) {
    function rq_parse_amount_value($value): float
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return 0.0;
        }

        $hasComma = strpos($raw, ',') !== false;
        $hasDot = strpos($raw, '.') !== false;

        if ($hasComma && $hasDot) {
            // Comma used as thousands separator.
            $raw = str_replace(',', '', $raw);
        } elseif ($hasComma && !$hasDot) {
            // Only comma present, treat as decimal separator.
            $raw = str_replace(',', '.', $raw);
        }

        $normalized = preg_replace('/[^0-9.\\-]/', '', $raw);
        return (float)$normalized;
    }
}

if (!function_exists('rq_normalize_bin_path')) {
    function rq_normalize_bin_path(?string $path): string
    {
        $path = trim((string)$path);
        $path = trim($path, "\"'");
        return $path;
    }
}

if (!function_exists('rq_resolve_bin_path')) {
    function rq_resolve_bin_path(?string $path, string $fallback): ?string
    {
        $path = rq_normalize_bin_path($path);
        if ($path !== '') {
            if (preg_match('~[\\\\/]~', $path)) {
                if (is_file($path)) {
                    return $path;
                }
            } else {
                return $path;
            }
        }
        if (!function_exists('shell_exec')) {
            return $path !== '' ? $path : null;
        }
        $isWindows = stripos(PHP_OS_FAMILY ?? PHP_OS, 'Windows') !== false;
        if ($isWindows) {
            $cmd = 'where.exe ' . escapeshellarg($fallback) . ' 2>NUL';
        } else {
            $cmd = 'command -v ' . escapeshellarg($fallback) . ' 2>/dev/null || which ' . escapeshellarg($fallback) . ' 2>/dev/null';
        }
        $resolved = trim((string) @shell_exec($cmd));
        // 'where' on Windows may return multiple lines; take the first
        if ($resolved !== '' && strpos($resolved, "\n") !== false) {
            $resolved = trim(strtok($resolved, "\n"));
        }
        return $resolved !== '' ? $resolved : null;
    }
}

if (!function_exists('rq_parse_ffmpeg_duration')) {
    function rq_parse_ffmpeg_duration(string $output): float
    {
        if (preg_match('/Duration:\\s*(\\d+):(\\d+):(\\d+)(?:\\.(\\d+))?/', $output, $m)) {
            $hours = (int) $m[1];
            $minutes = (int) $m[2];
            $seconds = (int) $m[3];
            $fraction = isset($m[4]) ? (float) ('0.' . $m[4]) : 0.0;
            return ($hours * 3600) + ($minutes * 60) + $seconds + $fraction;
        }
        return 0.0;
    }
}

if (!function_exists('rq_bin_basename')) {
    function rq_bin_basename(?string $path): string
    {
        $path = rq_normalize_bin_path($path);
        if ($path === '') {
            return '';
        }
        return strtolower(basename($path));
    }
}

if (!function_exists('rq_resolve_ffmpeg_bin')) {
    function rq_resolve_ffmpeg_bin(?string $path): ?string
    {
        $resolved = rq_resolve_bin_path($path, 'ffmpeg');
        if (!$resolved) {
            return null;
        }
        $base = rq_bin_basename($resolved);
        if ($base !== '' && strpos($base, 'ffprobe') !== false) {
            $fallback = rq_resolve_bin_path(null, 'ffmpeg');
            return $fallback ?: null;
        }
        return $resolved;
    }
}

if (!function_exists('rq_resolve_ffprobe_bin')) {
    function rq_resolve_ffprobe_bin(?string $path): ?string
    {
        $resolved = rq_resolve_bin_path($path, 'ffprobe');
        if (!$resolved) {
            return null;
        }
        $base = rq_bin_basename($resolved);
        if ($base !== '' && strpos($base, 'ffprobe') === false && strpos($base, 'ffmpeg') !== false) {
            $fallback = rq_resolve_bin_path(null, 'ffprobe');
            return $fallback ?: null;
        }
        return $resolved;
    }
}

if (!function_exists('rq_find_nested_field')) {
    function rq_find_nested_field($payload, string $field, bool &$found) {
        if (is_array($payload)) {
            foreach ($payload as $key => $value) {
                if ($key === $field) {
                    $found = true;
                    return $value;
                }
                if (is_array($value)) {
                    $nested = rq_find_nested_field($value, $field, $found);
                    if ($found) {
                        return $nested;
                    }
                }
            }
        }
        return null;
    }
}

if (!function_exists('rq_get_live_id_by_channel')) {
    function rq_get_live_id_by_channel(int $userId, string $channelName): int
    {
        $row = DB::one(
            "SELECT live_id FROM i_live WHERE live_uid_fk = ? AND live_channel = ? ORDER BY live_id DESC LIMIT 1",
            [(int)$userId, (string)$channelName]
        );
        if ($row && isset($row['live_id'])) {
            return (int)$row['live_id'];
        }
        $row = DB::one("SELECT live_id FROM i_live WHERE live_uid_fk = ? ORDER BY live_id DESC LIMIT 1", [(int)$userId]);
        return $row ? (int)$row['live_id'] : 0;
    }
}

if (!function_exists('rq_send_live_started_notifications')) {
    function rq_send_live_started_notifications(
        $iN,
        int $creatorId,
        string $creatorUserName,
        string $creatorFullName,
        string $audience,
        int $liveId,
        string $baseUrl,
        string $oneSignalApi,
        string $oneSignalRestApi,
        array $lang,
        array $selectedIds = []
    ): void {
        $creatorId = (int)$creatorId;
        $liveId = (int)$liveId;
        global $webPushStatus, $webPushEventLiveStarted;
        if ($creatorId <= 0 || $liveId <= 0) {
            return;
        }

        $now = time();
        $cooldownSeconds = 10 * 60;
        $recentThreshold = $now - $cooldownSeconds;
        $recentExists = DB::col(
            "SELECT 1 FROM i_user_notifications WHERE not_iuid = ? AND not_not_type = 'live_started' AND not_time >= ? LIMIT 1",
            [(int)$creatorId, (int)$recentThreshold]
        );
        if ($recentExists) {
            return;
        }

        $liveExists = DB::col(
            "SELECT 1 FROM i_user_notifications WHERE not_post_id = ? AND not_not_type = 'live_started' LIMIT 1",
            [(int)$liveId]
        );
        if ($liveExists) {
            return;
        }

        $limit = 200;
        if ($audience === 'subscribers') {
            $rows = DB::all(
                "SELECT DISTINCT U.iuid, U.i_username, U.i_user_fullname, U.device_key
                 FROM i_user_subscriptions S
                 INNER JOIN i_users U ON U.iuid = S.iuid_fk
                 WHERE S.subscribed_iuid_fk = ? AND S.status = 'active' AND S.finished = '0' AND S.subscription_scope = 'profile'
                   AND U.uStatus IN('1','3') AND U.iuid <> ?
                 LIMIT {$limit}",
                [(int)$creatorId, (int)$creatorId]
            );
        } elseif ($audience === 'selected') {
            $selectedIds = array_values(array_unique(array_map('intval', $selectedIds)));
            $selectedIds = array_filter($selectedIds, function ($id) use ($creatorId) {
                return $id > 0 && $id !== $creatorId;
            });
            $selectedIds = array_slice($selectedIds, 0, $limit);
            if (empty($selectedIds)) {
                return;
            }
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $params = array_merge([(int)$creatorId, (int)$creatorId], $selectedIds, [(int)$creatorId]);
            $rows = DB::all(
                "SELECT DISTINCT U.iuid, U.i_username, U.i_user_fullname, U.device_key
                 FROM i_users U
                 LEFT JOIN i_friends F
                   ON F.fr_one = U.iuid AND F.fr_two = ? AND F.fr_status = 'flwr'
                 LEFT JOIN i_user_subscriptions S
                   ON S.iuid_fk = U.iuid AND S.subscribed_iuid_fk = ? AND S.status = 'active' AND S.finished = '0' AND S.subscription_scope = 'profile'
                 WHERE U.iuid IN ({$placeholders}) AND U.uStatus IN('1','3') AND U.iuid <> ?
                   AND (F.fr_one IS NOT NULL OR S.subscription_id IS NOT NULL)
                 LIMIT {$limit}",
                $params
            );
        } else {
            $rows = DB::all(
                "SELECT DISTINCT U.iuid, U.i_username, U.i_user_fullname, U.device_key
                 FROM i_friends F
                 INNER JOIN i_users U ON U.iuid = F.fr_one
                 WHERE F.fr_two = ? AND F.fr_status = 'flwr'
                   AND U.uStatus IN('1','3') AND U.iuid <> ?
                 LIMIT {$limit}",
                [(int)$creatorId, (int)$creatorId]
            );
        }

        if (empty($rows)) {
            return;
        }

        $creatorDisplay = trim($creatorFullName) !== '' ? $creatorFullName : $creatorUserName;
        $pushTitleTemplate = $lang['live_started_push_title'] ?? 'Live started';
        $pushBodyTemplate = $lang['live_started_push_body'] ?? '{creator} started a live stream.';
        $pushTitle = str_replace('{creator}', $creatorDisplay, $pushTitleTemplate);
        $pushBody = str_replace('{creator}', $creatorDisplay, $pushBodyTemplate);
        $pushTitle = $iN->iN_Secure($pushTitle);
        $pushBody = $iN->iN_Secure($pushBody);
        $url = $baseUrl . 'live/' . $creatorUserName;
        $sendWebPush = ((string)$webPushStatus === '1' && (string)$webPushEventLiveStarted === '1');

        foreach ($rows as $row) {
            $recipientId = (int)($row['iuid'] ?? 0);
            if ($recipientId <= 0 || $recipientId === $creatorId) {
                continue;
            }
            DB::exec(
                "INSERT INTO i_user_notifications (not_iuid, not_post_id, not_own_iuid, not_type, not_not_type, not_time) VALUES (?,?,?,?,?,?)",
                [(int)$creatorId, (int)$liveId, (int)$recipientId, 'text', 'live_started', (int)$now]
            );
            DB::exec("UPDATE i_users SET notification_read_status = '1' WHERE iuid = ?", [(int)$recipientId]);
            $deviceKey = $row['device_key'] ?? null;
            if (!empty($deviceKey)) {
                $iN->iN_OneSignalPushNotificationSend($pushBody, $pushTitle, $url, $deviceKey, $oneSignalApi, $oneSignalRestApi);
            }
            if ($sendWebPush) {
                $iN->iN_SendWebPushToUser((int)$recipientId, [
                    'title' => $pushTitle,
                    'body' => $pushBody,
                    'url' => $url,
                    'tag' => 'live_started_' . $liveId,
                ]);
            }
        }
    }
}
if (!function_exists('rq_send_live_started_notifications_bulk')) {
    function rq_send_live_started_notifications_bulk(
        $iN,
        int $creatorId,
        string $creatorUserName,
        string $creatorFullName,
        int $liveId,
        string $baseUrl,
        string $oneSignalApi,
        string $oneSignalRestApi,
        array $lang,
        array $recipientIds
    ): void {
        $creatorId = (int)$creatorId;
        $liveId = (int)$liveId;
        global $webPushStatus, $webPushEventLiveStarted;
        if ($creatorId <= 0 || $liveId <= 0) {
            return;
        }
        $recipientIds = array_values(array_unique(array_map('intval', $recipientIds)));
        $recipientIds = array_filter($recipientIds, function ($id) use ($creatorId) {
            return $id > 0 && $id !== $creatorId;
        });
        if (empty($recipientIds)) {
            return;
        }
        $liveExists = DB::col(
            "SELECT 1 FROM i_user_notifications WHERE not_post_id = ? AND not_not_type = 'live_started' LIMIT 1",
            [(int)$liveId]
        );
        if ($liveExists) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($recipientIds), '?'));
        $rows = DB::all(
            "SELECT iuid, i_username, i_user_fullname, device_key FROM i_users
             WHERE iuid IN ({$placeholders}) AND uStatus IN('1','3')",
            $recipientIds
        );
        if (empty($rows)) {
            return;
        }
        $creatorDisplay = trim($creatorFullName) !== '' ? $creatorFullName : $creatorUserName;
        $pushTitleTemplate = $lang['live_started_push_title'] ?? 'Live started';
        $pushBodyTemplate = $lang['live_started_push_body'] ?? '{creator} started a live stream.';
        $pushTitle = str_replace('{creator}', $creatorDisplay, $pushTitleTemplate);
        $pushBody = str_replace('{creator}', $creatorDisplay, $pushBodyTemplate);
        $pushTitle = $iN->iN_Secure($pushTitle);
        $pushBody = $iN->iN_Secure($pushBody);
        $url = $baseUrl . 'live/' . $creatorUserName;
        $now = time();
        $sendWebPush = ((string)$webPushStatus === '1' && (string)$webPushEventLiveStarted === '1');

        foreach ($rows as $row) {
            $recipientId = (int)($row['iuid'] ?? 0);
            if ($recipientId <= 0 || $recipientId === $creatorId) {
                continue;
            }
            DB::exec(
                "INSERT INTO i_user_notifications (not_iuid, not_post_id, not_own_iuid, not_type, not_not_type, not_time)
                 VALUES (?,?,?,?,?,?)",
                [(int)$creatorId, (int)$liveId, (int)$recipientId, 'text', 'live_started', (int)$now]
            );
            DB::exec("UPDATE i_users SET notification_read_status = '1' WHERE iuid = ?", [(int)$recipientId]);
            $deviceKey = $row['device_key'] ?? null;
            if (!empty($deviceKey)) {
                $iN->iN_OneSignalPushNotificationSend($pushBody, $pushTitle, $url, $deviceKey, $oneSignalApi, $oneSignalRestApi);
            }
            if ($sendWebPush) {
                $iN->iN_SendWebPushToUser((int)$recipientId, [
                    'title' => $pushTitle,
                    'body' => $pushBody,
                    'url' => $url,
                    'tag' => 'live_started_' . $liveId,
                ]);
            }
        }
    }
}

if (!function_exists('iN_creator_handle_bulk_attachment_upload')) {
    function iN_creator_handle_bulk_attachment_upload(
        array $file,
        int $userID,
        int $maxSizeMb,
        string $uploadRoot,
        array $allowedExtensions,
        bool $ffmpegCanConvert,
        ?string $ffmpegPath,
        ?array $thumbnailFile = null
    ): array {
        $name = $file['name'] ?? '';
        $tmp = $file['tmp_name'] ?? '';
        $size = isset($file['size']) ? (int)$file['size'] : 0;
        if ($name === '' || $tmp === '') {
            return ['upload_failed', null];
        }

        $allowed = array_values(array_filter(array_map('strtolower', $allowedExtensions)));
        if (empty($allowed)) {
            return ['invalid_format', null];
        }

        $ext = strtolower(getExtension($name));
        if ($ext === '' || !in_array($ext, $allowed, true)) {
            return ['invalid_format', null];
        }
        if ($maxSizeMb > 0 && function_exists('convert_to_mb') && convert_to_mb($size) > $maxSizeMb) {
            return ['file_is_too_large', null];
        }

        $videoExtensions = ['mp4', 'mov', 'm4v', 'webm', 'avi', 'mpg', 'mpeg', 'mkv', 'flv', 'mp4v', 'ogv', '3gp', '3gpp', 'wmv'];
        $mimeType = $file['type'] ?? '';
        $isVideo = preg_match('/video\/*/', $mimeType) === 1 || in_array($ext, $videoExtensions, true);
        if ($isVideo && !$ffmpegCanConvert && $ext !== 'mp4') {
            return ['unsupported_video_format', null];
        }

        $todayDir = date('Y-m-d');
        $uploadDir = rtrim($uploadRoot, '/') . '/' . $todayDir . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $microtime = microtime();
        $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
        $fileBase = 'bulk_' . $removeMicrotime . '_' . $userID;
        $uploadedFileName = $fileBase . '.' . $ext;
        $uploadedPath = $uploadDir . $uploadedFileName;
        if (!move_uploaded_file($tmp, $uploadedPath)) {
            return ['upload_failed', null];
        }

        $relativePath = 'uploads/files/' . $todayDir . '/' . $uploadedFileName;
        $publishKeys = [];

        if ($isVideo) {
            if ($ffmpegCanConvert) {
                require_once '../includes/convertToMp4Format.php';
                require_once '../includes/createVideoThumbnail.php';
                $convertedPath = convertToMp4Format((string)$ffmpegPath, $uploadedPath, $uploadDir, $fileBase);
                if (!$convertedPath || !file_exists($convertedPath)) {
                    return ['upload_failed', null];
                }
                $thumbPath = createVideoThumbnailInSameDir((string)$ffmpegPath, $convertedPath);
                if (!$thumbPath) {
                    return ['upload_failed', null];
                }
                $uploadedFileName = basename($convertedPath);
                $relativePath = 'uploads/files/' . $todayDir . '/' . $uploadedFileName;
                $publishKeys[] = $relativePath;
                $publishKeys[] = 'uploads/files/' . $todayDir . '/' . $fileBase . '.jpg';
            } else {
                $thumbName = $thumbnailFile['name'] ?? '';
                $thumbTmp = $thumbnailFile['tmp_name'] ?? '';
                $thumbSize = isset($thumbnailFile['size']) ? (int)$thumbnailFile['size'] : 0;
                if ($thumbName === '' || $thumbTmp === '') {
                    return ['thumbnail_required', null];
                }
                $thumbExt = strtolower(getExtension($thumbName));
                if (!in_array($thumbExt, ['jpg', 'jpeg'], true)) {
                    return ['thumbnail_invalid', null];
                }
                if ($maxSizeMb > 0 && function_exists('convert_to_mb') && convert_to_mb($thumbSize) > $maxSizeMb) {
                    return ['file_is_too_large', null];
                }
                $thumbPath = $uploadDir . $fileBase . '.jpg';
                if (!move_uploaded_file($thumbTmp, $thumbPath)) {
                    return ['upload_failed', null];
                }
                $publishKeys[] = $relativePath;
                $publishKeys[] = 'uploads/files/' . $todayDir . '/' . $fileBase . '.jpg';
            }
        } else {
            $publishKeys[] = $relativePath;
        }

        if ($publishKeys && function_exists('storage_publish_many')) {
            storage_publish_many($publishKeys, true, true);
        }
        return [null, $relativePath];
    }
}

// Who can view (e.g., followers, subscribers, public etc.)
$whoCanSeeArrays = array('1', '2', '3', '4');

// Block types (user blocks, post blocks etc.)
$blockType = array('1', '2');

// Possible status flags
$statusValue = array('0', '1');

// Available UI themes
$themes = array('light', 'dark');

// Video formats supported if FFMPEG is not available (only MP4 allowed)
$nonFfmpegAvailableVideoFormat = array('mp4','MP4');

// Supported payout methods for the platform (enabled by admin settings)
$enabledPayoutMethodMap = array(
    'paypal' => isset($payPalPaymentStatus) && (string)$payPalPaymentStatus === '1',
    'bank' => true,
    'payoneer' => isset($payoutPayoneerStatus) && (string)$payoutPayoneerStatus === '1',
    'zelle' => isset($payoutZelleStatus) && (string)$payoutZelleStatus === '1',
    'western-union' => isset($payoutWesternUnionStatus) && (string)$payoutWesternUnionStatus === '1',
    'bitcoin' => isset($payoutBitcoinStatus) && (string)$payoutBitcoinStatus === '1',
    'mercadopago' => isset($payoutMercadoPagoStatus) && (string)$payoutMercadoPagoStatus === '1',
);
$defaultPayoutMethods = array();
foreach ($enabledPayoutMethodMap as $methodKey => $isEnabled) {
    if ($isEnabled) {
        $defaultPayoutMethods[] = $methodKey;
    }
}
if (empty($defaultPayoutMethods)) {
    $defaultPayoutMethods[] = 'bank';
}

// Available gender options for users (from includes/inc.php)
if (!isset($genders) || !is_array($genders) || empty($genders)) {
    $genders = array('male', 'couple', 'female');
}

// Common yes/no values
$yesOrNo = array('yes', 'no');

// Determine if FFMPEG can be used for conversions (must be enabled and available)
$ffmpegCanConvert = (isset($ffmpegStatus) && (string)$ffmpegStatus === '1');
if ($ffmpegCanConvert && !function_exists('shell_exec')) {
    $ffmpegCanConvert = false;
}
if ($ffmpegCanConvert) {
    $resolvedFfmpeg = rq_resolve_ffmpeg_bin($ffmpegPath);
    if ($resolvedFfmpeg) {
        $ffmpegPath = $resolvedFfmpeg;
    } else {
        $ffmpegCanConvert = false;
    }
}
if (!empty($ffprobePath)) {
    $resolvedProbe = rq_resolve_ffprobe_bin($ffprobePath);
    if ($resolvedProbe) {
        $ffprobePath = $resolvedProbe;
    }
}

/* -------------------------------------------
 | OpenAI Integration (Chat Completion API)
 --------------------------------------------*/

if ($openAiStatus == '1') {
    if (!function_exists('iN_ai_strlen')) {
        function iN_ai_strlen($text) {
            if (function_exists('mb_strlen')) {
                return mb_strlen((string)$text, 'UTF-8');
            }
            return strlen((string)$text);
        }
    }
    if (!function_exists('iN_ai_clean_text')) {
        function iN_ai_clean_text($text) {
            $text = trim(strip_tags((string)$text));
            $text = preg_replace('/[\\x00-\\x1F\\x7F]/', ' ', $text);
            $text = preg_replace('/\\s+/', ' ', $text);
            return trim($text);
        }
    }
    if (!function_exists('iN_ai_clean_output')) {
        function iN_ai_clean_output($text) {
            $text = trim(strip_tags((string)$text));
            $text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/', ' ', $text);
            $text = preg_replace("/[ \\t]+/", ' ', $text);
            $text = preg_replace("/\\n{3,}/", "\n\n", $text);
            $text = trim($text, "\"' \n\r\t");
            return trim($text);
        }
    }
    if (!function_exists('iN_ai_parse_forbidden_terms')) {
        function iN_ai_parse_forbidden_terms($raw) {
            $terms = [];
            $raw = trim((string)$raw);
            if ($raw !== '') {
                $parts = explode(',', $raw);
                foreach ($parts as $part) {
                    $term = trim($part);
                    if ($term !== '') {
                        $terms[] = $term;
                    }
                }
            }
            if (empty($terms)) {
                $terms = ['self-harm', 'suicide', 'terrorism', 'child sexual', 'csam', 'rape'];
            }
            return $terms;
        }
    }
    if (!function_exists('iN_ai_contains_forbidden')) {
        function iN_ai_contains_forbidden($text, array $terms) {
            $text = (string)$text;
            foreach ($terms as $term) {
                if ($term === '') {
                    continue;
                }
                if (function_exists('mb_stripos')) {
                    if (mb_stripos($text, $term, 0, 'UTF-8') !== false) {
                        return true;
                    }
                } else if (stripos($text, $term) !== false) {
                    return true;
                }
            }
            return false;
        }
    }
    if (!function_exists('iN_openai_request')) {
        function iN_openai_request(array $payload, string $apiKey, string $model): array {
            $url = 'https://api.openai.com/v1/chat/completions';
            $payload['model'] = $model;
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlErr) {
                $code = stripos($curlErr, 'timed out') !== false ? 'timeout' : 'curl_error';
                return [
                    'ok' => false,
                    'error' => ['code' => $code, 'message' => $curlErr],
                    'http_code' => $httpCode,
                    'model' => $model,
                ];
            }

            $result = json_decode((string)$response, true);
            if ($httpCode >= 400 || isset($result['error'])) {
                $errorMessage = $result['error']['message'] ?? 'OpenAI request failed.';
                $mappedCode = 'unknown_error';
                if ($httpCode === 401) {
                    $mappedCode = 'invalid_api_key';
                } else if ($httpCode === 429) {
                    $mappedCode = 'rate_limited';
                } else if ($httpCode >= 500) {
                    $mappedCode = 'server_error';
                } else if ($httpCode === 404) {
                    $mappedCode = 'model_not_found';
                } else if ($httpCode === 400) {
                    $mappedCode = 'bad_request';
                }
                return [
                    'ok' => false,
                    'error' => ['code' => $mappedCode, 'message' => $errorMessage],
                    'http_code' => $httpCode,
                    'model' => $model,
                ];
            }

            $choices = [];
            if (isset($result['choices']) && is_array($result['choices'])) {
                foreach ($result['choices'] as $choice) {
                    $choices[] = $choice['message']['content'] ?? '';
                }
            }
            return [
                'ok' => true,
                'choices' => $choices,
                'usage' => $result['usage'] ?? [],
                'http_code' => $httpCode,
                'model' => $model,
            ];
        }
    }
    if (!function_exists('callOpenAI')) {
        function callOpenAI(array $payload, string $apiKey, string $primaryModel, string $fallbackModel = ''): array {
            $result = iN_openai_request($payload, $apiKey, $primaryModel);
            if (!$result['ok'] && $fallbackModel !== '' && $fallbackModel !== $primaryModel) {
                if (($result['error']['code'] ?? '') !== 'invalid_api_key') {
                    $fallback = iN_openai_request($payload, $apiKey, $fallbackModel);
                    if ($fallback['ok']) {
                        return $fallback;
                    }
                    $result['fallback_error'] = $fallback['error'] ?? null;
                }
            }
            return $result;
        }
    }
}

/* -------------------------------------------
 | Watermark System (Image Branding Layer)
 --------------------------------------------*/

if ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes') {
    if (!function_exists('watermark_resolve_logo_path')) {
        function watermark_resolve_logo_path($logoPath)
        {
            $logoPath = trim((string)$logoPath);
            if ($logoPath === '') {
                return '';
            }

            $normalized = ltrim(str_replace('\\', '/', $logoPath), '/');
            $candidates = array(
                '../' . $normalized,
                $normalized,
            );

            foreach ($candidates as $candidate) {
                if (is_file($candidate) && is_readable($candidate)) {
                    return $candidate;
                }
            }

            return '';
        }
    }

    if (!function_exists('watermark_normalize_text')) {
        function watermark_normalize_text($text)
        {
            $text = trim((string)$text);
            if ($text === '') {
                return '';
            }

            $text = preg_replace('/[\x00-\x1F\x7F]+/u', '', $text);
            if (function_exists('mb_substr')) {
                $text = (string)mb_substr($text, 0, 80);
            } else {
                $text = (string)substr($text, 0, 80);
            }

            return trim($text);
        }
    }

    if (!function_exists('watermark_image')) {
        /**
         * Apply a responsive watermark logo and optional link text while preserving source format.
         */
        function watermark_image($target, $siteWatermarkLogo, $LinkWatermarkStatus, $ourl)
        {
            include_once "../includes/SimpleImage-master/src/claviska/SimpleImage.php";

            if (!is_file($target) || !is_readable($target)) {
                return false;
            }

            try {
                $image = new \claviska\SimpleImage();
                $image->fromFile($target)->autoOrient();

                $width = (int)$image->getWidth();
                $height = (int)$image->getHeight();
                if ($width <= 0 || $height <= 0) {
                    return false;
                }

                // Do not force watermarking on very small visuals.
                if ($width < 220 || $height < 220) {
                    return true;
                }

                $shortEdge = min($width, $height);
                $margin = max(10, (int)round($shortEdge * 0.028));
                $configuredSize = isset($GLOBALS['watermarkSize']) ? (int)$GLOBALS['watermarkSize'] : 19;
                if ($configuredSize < 8) {
                    $configuredSize = 8;
                }
                if ($configuredSize > 40) {
                    $configuredSize = 40;
                }
                $configuredOpacity = isset($GLOBALS['watermarkOpacity']) ? (int)$GLOBALS['watermarkOpacity'] : 78;
                if ($configuredOpacity < 10) {
                    $configuredOpacity = 10;
                }
                if ($configuredOpacity > 100) {
                    $configuredOpacity = 100;
                }
                $position = isset($GLOBALS['watermarkPosition']) ? strtolower(trim((string)$GLOBALS['watermarkPosition'])) : 'bottom_right';
                $positionMap = array(
                    'top_left' => 'top left',
                    'top_right' => 'top right',
                    'bottom_left' => 'bottom left',
                    'bottom_right' => 'bottom right',
                );
                if (!isset($positionMap[$position])) {
                    $position = 'bottom_right';
                }
                $oppositeMap = array(
                    'top left' => 'bottom right',
                    'top right' => 'bottom left',
                    'bottom left' => 'top right',
                    'bottom right' => 'top left',
                );
                $logoAnchor = $positionMap[$position];
                $textAnchor = isset($oppositeMap[$logoAnchor]) ? $oppositeMap[$logoAnchor] : 'bottom right';
                $logoMaxWidth = max(32, min(360, (int)round($shortEdge * ($configuredSize / 100))));
                $textSize = max(11, min(30, (int)round($shortEdge * (($configuredSize / 100) * 0.16))));
                $jpegQuality = 90;
                $logoApplied = false;

                $logoEnabled = isset($GLOBALS['watermarkStatus']) && (string)$GLOBALS['watermarkStatus'] === 'yes';
                if ($logoEnabled) {
                    $logoAbsolutePath = watermark_resolve_logo_path($siteWatermarkLogo);
                    if ($logoAbsolutePath !== '') {
                        $logo = new \claviska\SimpleImage();
                        $logo->fromFile($logoAbsolutePath);
                        if ($logo->getWidth() > $logoMaxWidth) {
                            $logo->resize($logoMaxWidth, null);
                        }
                        $image->overlay($logo, $logoAnchor, ((float)$configuredOpacity / 100), $margin, $margin, true);
                        $logoApplied = true;
                    }
                }

                if ($LinkWatermarkStatus == 'yes') {
                    $watermarkText = watermark_normalize_text($ourl);
                    $fontFile = '../src/droidsanschinese.ttf';
                    if ($watermarkText !== '' && is_file($fontFile)) {
                        $anchor = $logoApplied ? $textAnchor : $logoAnchor;
                        $image->text(
                            $watermarkText,
                            array(
                                'fontFile' => $fontFile,
                                'size' => $textSize,
                                'color' => '#FFFFFF',
                                'anchor' => $anchor,
                                'xOffset' => $margin,
                                'yOffset' => $margin,
                                'calculateOffsetFromEdge' => true,
                                'shadow' => array(
                                    'x' => 1,
                                    'y' => 1,
                                    'color' => '#000000',
                                ),
                            )
                        );
                    }
                }

                // Preserve original mime type (jpeg/png/webp/gif) instead of forcing jpeg.
                $image->toFile($target, null, $jpegQuality);

                return true;
            } catch (Throwable $err) {
                return $err->getMessage();
            }
        }
    }

    if (!function_exists('watermark_apply_batch')) {
        function watermark_apply_batch(array $targets, $siteWatermarkLogo, $linkWatermarkStatus, $watermarkLabel)
        {
            $processed = array();
            foreach ($targets as $target) {
                $target = trim((string)$target);
                if ($target === '' || isset($processed[$target])) {
                    continue;
                }
                $processed[$target] = true;
                if (is_file($target)) {
                    watermark_image($target, $siteWatermarkLogo, $linkWatermarkStatus, $watermarkLabel);
                }
            }
        }
    }
}

/**
 * Helper function to remove http:// or https:// from a given URL
 *
 * @param string $url
 * @return string cleaned URL without protocol
 */
function remove_http($url) {
    $parts = parse_url($url);
    if (!empty($parts['host'])) {
        $host = $parts['host'];
        if (stripos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        return $host . $path;
    }
    return $url;
}

function watermark_label($baseUrl, $userName = '')
{
    $cleanBase = rtrim(remove_http($baseUrl), '/');
    $userName = trim($userName ?? '');
    if ($userName !== '') {
        $userName = ltrim($userName, '/');
        return $cleanBase . '/' . $userName;
    }
    return $cleanBase;
}

if (!function_exists('rq_subscription_period_from_plan_type')) {
    function rq_subscription_period_from_plan_type(string $planType): array
    {
        if ($planType === 'weekly') {
            return ['interval' => 'week', 'interval_count' => 1];
        }
        if ($planType === 'yearly') {
            return ['interval' => 'year', 'interval_count' => 1];
        }
        return ['interval' => 'month', 'interval_count' => 1];
    }
}

if (!function_exists('rq_iyzico_subscription_gateway_ready')) {
    function rq_iyzico_subscription_gateway_ready(): bool
    {
        global $iyziCoPaymentStatus, $iyziCoPaymentBeta, $userType, $iyziCoPaymentMode;
        global $iyziCoPaymentTestApiKey, $iyziCoPaymentTestSecretKey, $iyziCoPaymentLiveApiKey, $iyziCoPaymentLiveApiSecret;

        $iyzicoActive = isset($iyziCoPaymentStatus) && (int)$iyziCoPaymentStatus === 1;
        if (!$iyzicoActive) {
            return false;
        }

        $betaFlag = isset($iyziCoPaymentBeta) ? (string)$iyziCoPaymentBeta : '0';
        $isAdminUser = isset($userType) && (string)$userType === '2';
        if ($betaFlag === '1' && !$isAdminUser) {
            return false;
        }

        $testMode = !isset($iyziCoPaymentMode) || (int)$iyziCoPaymentMode !== 1;
        $apiKey = $testMode ? (string)$iyziCoPaymentTestApiKey : (string)$iyziCoPaymentLiveApiKey;
        $secretKey = $testMode ? (string)$iyziCoPaymentTestSecretKey : (string)$iyziCoPaymentLiveApiSecret;

        return ($apiKey !== '' && $secretKey !== '');
    }
}

if (!function_exists('rq_start_iyzico_subscription_checkout')) {
    function rq_start_iyzico_subscription_checkout(array $intentData, array $cardData): array
    {
        global $LANG, $defaultCurrency, $iyziCoPaymentCurrency;
        if (!rq_iyzico_subscription_gateway_ready()) {
            return [
                'status' => 'error',
                'message' => $LANG['no_payment_method_available'] ?? 'No payment method available.',
            ];
        }

        $subscriberId = (int)($intentData['subscriber_id'] ?? 0);
        $subscribedId = (int)($intentData['subscribed_id'] ?? 0);
        $scope = (string)($intentData['scope'] ?? 'profile');
        $scopeRefId = isset($intentData['scope_ref_id']) ? (int)$intentData['scope_ref_id'] : null;
        $planId = (string)($intentData['plan_id'] ?? '');
        $planInterval = (string)($intentData['plan_interval'] ?? 'month');
        $planIntervalCount = (int)($intentData['plan_interval_count'] ?? 1);
        $orderKey = (string)($intentData['order_key'] ?? '');
        $subscriberName = (string)($intentData['subscriber_name'] ?? '');
        $subscriberEmail = (string)($intentData['subscriber_email'] ?? '');
        $amount = (float)($intentData['amount'] ?? 0);
        $itemId = (string)($intentData['item_id'] ?? ('ITEM' . uniqid()));

        if ($subscriberId <= 0 || $subscribedId <= 0 || $orderKey === '' || $planId === '' || $amount <= 0) {
            return [
                'status' => 'error',
                'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.',
            ];
        }

        $cardHolder = trim((string)($cardData['cardname'] ?? ''));
        $cardNumber = preg_replace('/\D+/', '', (string)($cardData['cardnumber'] ?? ''));
        $expMonth = preg_replace('/\D+/', '', (string)($cardData['expmonth'] ?? ''));
        $expYear = preg_replace('/\D+/', '', (string)($cardData['expyear'] ?? ''));
        $cvv = preg_replace('/\D+/', '', (string)($cardData['cvv'] ?? ''));
        if ($cardHolder === '' || strlen($cardNumber) < 12 || $expMonth === '' || $expYear === '' || $cvv === '') {
            return [
                'status' => 'error',
                'message' => $LANG['fill_all_credit_card_details'] ?? 'Fill all card details.',
            ];
        }

        $monthInt = (int)$expMonth;
        if ($monthInt < 1 || $monthInt > 12) {
            return [
                'status' => 'error',
                'message' => $LANG['invalid_card_details'] ?? 'Invalid card details.',
            ];
        }
        $expMonth = str_pad((string)$monthInt, 2, '0', STR_PAD_LEFT);
        if (strlen($expYear) === 2) {
            $expYear = '20' . $expYear;
        }
        if (strlen($expYear) !== 4) {
            return [
                'status' => 'error',
                'message' => $LANG['invalid_card_details'] ?? 'Invalid card details.',
            ];
        }

        if (!defined('INORA_METHODS_CONFIG')) {
            define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
        }
        require_once '../includes/payment/vendor/autoload.php';

        $currency = strtoupper((string)($iyziCoPaymentCurrency ?: $defaultCurrency ?: 'USD'));
        $amountFormatted = number_format($amount, 2, '.', '');
        $time = time();
        $scopeValue = in_array($scope, ['profile', 'community', 'community_plan'], true) ? $scope : 'profile';
        $intervalValue = in_array($planInterval, ['week', 'month', 'year'], true) ? $planInterval : 'month';
        $intervalCountValue = $planIntervalCount > 0 ? $planIntervalCount : 1;

        try {
            DB::exec(
                "INSERT INTO i_user_subscription_intents (iuid_fk, subscribed_iuid_fk, subscriber_name, subscriber_email, subscription_scope, subscription_ref_id, plan_id, plan_amount, plan_amount_currency, plan_interval, plan_interval_count, tax_rate, tax_amount, order_key, payment_option, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    (int)$subscriberId,
                    (int)$subscribedId,
                    (string)$subscriberName,
                    (string)$subscriberEmail,
                    (string)$scopeValue,
                    $scopeRefId,
                    (string)$planId,
                    (string)$amountFormatted,
                    (string)$currency,
                    (string)$intervalValue,
                    (int)$intervalCountValue,
                    '0.0000',
                    '0.00',
                    (string)$orderKey,
                    'iyzico',
                    'pending',
                    (int)$time,
                ]
            );

            DB::exec(
                "INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, currency)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    (int)$subscriberId,
                    (int)$subscribedId,
                    (string)$orderKey,
                    'subscription',
                    'iyzico',
                    (int)$time,
                    'pending',
                    (string)$amountFormatted,
                    (string)$currency,
                ]
            );
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $LANG['something_wrong'] ?? 'Something went wrong.',
            ];
        }

        $requestPayload = [
            'amounts' => [
                $currency => $amountFormatted,
            ],
            'paymentOption' => 'iyzico',
            'customer_id' => 'SUB_CUST_' . $subscriberId,
            'order_id' => (string)$orderKey,
            'item_id' => (string)$itemId,
            'payer_email' => (string)$subscriberEmail,
            'payer_name' => (string)$subscriberName,
            'description' => 'Subscription',
            'ip_address' => getUserIpAddr(),
            'address' => '3234 Godfrey Street Tigard, OR 97223',
            'city' => 'Tigard',
            'country' => 'United States',
            'cardname' => $cardHolder,
            'cardnumber' => $cardNumber,
            'expmonth' => $expMonth,
            'expyear' => $expYear,
            'cvv' => $cvv,
        ];

        try {
            $iyzicoService = new IyzicoService();
            $response = $iyzicoService->processIyzicoRequest($requestPayload);
        } catch (Throwable $e) {
            $response = [
                'status' => 'failed',
                'errorMessage' => $e->getMessage(),
            ];
        }

        if (!is_array($response) || strtolower((string)($response['status'] ?? '')) !== 'success' || empty($response['htmlContent'])) {
            DB::exec("DELETE FROM i_user_subscription_intents WHERE order_key = ? AND iuid_fk = ?", [(string)$orderKey, (int)$subscriberId]);
            DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$orderKey, (int)$subscriberId]);
            return [
                'status' => 'error',
                'message' => (string)($response['errorMessage'] ?? ($LANG['something_wrong'] ?? 'Unable to start subscription.')),
            ];
        }

        return [
            'status' => 'success',
            'htmlContent' => (string)$response['htmlContent'],
            'order_id' => (string)$orderKey,
        ];
    }
}

if (!function_exists('rq_table_has_column')) {
    function rq_table_has_column(string $table, string $column): bool
    {
        static $cache = [];
        $table = trim($table);
        $column = trim($column);
        if ($table === '' || $column === '') {
            return false;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $count = DB::col(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $column]
            );
            $cache[$key] = ((int)$count > 0);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

if (!function_exists('rq_epoch_log')) {
    function rq_epoch_log(string $message, array $context = []): void
    {
        $logDir = __DIR__ . '/../includes/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $line = date('c') . ' ' . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;
        @file_put_contents($logDir . '/epoch_webhook.log', $line, FILE_APPEND);
    }
}

if (!function_exists('rq_epoch_subscription_gateway_ready')) {
    function rq_epoch_subscription_gateway_ready(): bool
    {
        global $epochPaymentStatus, $epochBeta, $userType, $epochPiCode, $epochPaymentMode;
        global $epochTestEndpoint, $epochLiveEndpoint;

        $epochActive = isset($epochPaymentStatus) && (int)$epochPaymentStatus === 1;
        if (!$epochActive) {
            return false;
        }

        $betaFlag = isset($epochBeta) ? (string)$epochBeta : '0';
        $isAdminUser = isset($userType) && (string)$userType === '2';
        if ($betaFlag === '1' && !$isAdminUser) {
            return false;
        }

        $piCode = trim((string)($epochPiCode ?? ''));
        if ($piCode === '') {
            return false;
        }

        $testMode = !isset($epochPaymentMode) || (int)$epochPaymentMode !== 1;
        $endpoint = trim((string)($testMode ? ($epochTestEndpoint ?? '') : ($epochLiveEndpoint ?? '')));
        if ($endpoint === '') {
            $endpoint = trim((string)($epochTestEndpoint ?? ''));
        }

        return $endpoint !== '';
    }
}

if (!function_exists('rq_epoch_generate_nonce')) {
    function rq_epoch_generate_nonce(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            return 'epoch_' . uniqid('', true);
        }
    }
}

if (!function_exists('rq_epoch_build_signature')) {
    function rq_epoch_build_signature(string $orderKey, string $nonce, int $userId, string $planId, string $envMarker): string
    {
        global $epochPostbackSecret;
        $secret = trim((string)($epochPostbackSecret ?? ''));
        if ($secret === '') {
            $secret = sha1($orderKey . ':' . $nonce);
        }
        $payload = implode('|', [$orderKey, $nonce, (string)$userId, $planId, $envMarker]);
        return hash_hmac('sha256', $payload, $secret);
    }
}

if (!function_exists('rq_start_epoch_subscription_checkout')) {
    function rq_start_epoch_subscription_checkout(array $intentData): array
    {
        global $LANG, $defaultCurrency, $epochCurrency, $epochPaymentMode;

        if (!rq_epoch_subscription_gateway_ready()) {
            return [
                'status' => 'error',
                'message' => $LANG['epoch_not_available'] ?? ($LANG['no_payment_method_available'] ?? 'No payment method available.'),
            ];
        }

        $subscriberId = (int)($intentData['subscriber_id'] ?? 0);
        $subscribedId = (int)($intentData['subscribed_id'] ?? 0);
        $scope = (string)($intentData['scope'] ?? 'profile');
        $scopeRefId = isset($intentData['scope_ref_id']) ? (int)$intentData['scope_ref_id'] : null;
        $planId = (string)($intentData['plan_id'] ?? '');
        $planInterval = (string)($intentData['plan_interval'] ?? 'month');
        $planIntervalCount = (int)($intentData['plan_interval_count'] ?? 1);
        $orderKey = (string)($intentData['order_key'] ?? '');
        $subscriberName = trim((string)($intentData['subscriber_name'] ?? ''));
        $subscriberEmail = trim((string)($intentData['subscriber_email'] ?? ''));
        $amount = (float)($intentData['amount'] ?? 0);

        if ($subscriberId <= 0 || $subscribedId <= 0 || $orderKey === '' || $planId === '' || $amount <= 0) {
            return [
                'status' => 'error',
                'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.',
            ];
        }

        $scopeValue = in_array($scope, ['profile', 'community', 'community_plan'], true) ? $scope : 'profile';
        $intervalValue = in_array($planInterval, ['week', 'month', 'year'], true) ? $planInterval : 'month';
        $intervalCountValue = $planIntervalCount > 0 ? $planIntervalCount : 1;
        $planCurrency = strtoupper((string)((($epochCurrency ?? '') !== '') ? $epochCurrency : ($defaultCurrency ?? 'USD')));
        if ($planCurrency === '') {
            $planCurrency = 'USD';
        }
        $amountFormatted = number_format($amount, 2, '.', '');

        $envMarker = (!isset($epochPaymentMode) || (int)$epochPaymentMode !== 1) ? 'test' : 'live';
        $nonce = rq_epoch_generate_nonce();
        $signature = rq_epoch_build_signature($orderKey, $nonce, $subscriberId, $planId, $envMarker);
        $time = time();

        $intentColumns = [
            'iuid_fk',
            'subscribed_iuid_fk',
            'subscriber_name',
            'subscriber_email',
            'subscription_scope',
            'subscription_ref_id',
            'plan_id',
            'plan_amount',
            'plan_amount_currency',
            'plan_interval',
            'plan_interval_count',
            'tax_rate',
            'tax_amount',
            'order_key',
            'payment_option',
            'status',
            'created_at'
        ];
        $intentValues = [
            (int)$subscriberId,
            (int)$subscribedId,
            (string)$subscriberName,
            (string)$subscriberEmail,
            (string)$scopeValue,
            $scopeRefId,
            (string)$planId,
            (string)$amountFormatted,
            (string)$planCurrency,
            (string)$intervalValue,
            (int)$intervalCountValue,
            '0.0000',
            '0.00',
            (string)$orderKey,
            'epoch',
            'pending',
            (int)$time
        ];

        if (rq_table_has_column('i_user_subscription_intents', 'epoch_nonce')) {
            $intentColumns[] = 'epoch_nonce';
            $intentValues[] = (string)$nonce;
        }
        if (rq_table_has_column('i_user_subscription_intents', 'epoch_signature')) {
            $intentColumns[] = 'epoch_signature';
            $intentValues[] = (string)$signature;
        }
        if (rq_table_has_column('i_user_subscription_intents', 'epoch_env')) {
            $intentColumns[] = 'epoch_env';
            $intentValues[] = (string)$envMarker;
        }

        $intentSql = "INSERT INTO i_user_subscription_intents (" . implode(', ', $intentColumns) . ")
                      VALUES (" . implode(',', array_fill(0, count($intentColumns), '?')) . ")";

        try {
            DB::begin();
            DB::exec($intentSql, $intentValues);
            DB::exec(
                "INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, currency)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    (int)$subscriberId,
                    (int)$subscribedId,
                    (string)$orderKey,
                    'subscription',
                    'epoch',
                    (int)$time,
                    'pending',
                    (string)$amountFormatted,
                    (string)$planCurrency,
                ]
            );

            $updateFields = [];
            $updateParams = [];
            if (rq_table_has_column('i_user_payments', 'epoch_nonce')) {
                $updateFields[] = 'epoch_nonce = ?';
                $updateParams[] = (string)$nonce;
            }
            if (rq_table_has_column('i_user_payments', 'epoch_signature')) {
                $updateFields[] = 'epoch_signature = ?';
                $updateParams[] = (string)$signature;
            }
            if (!empty($updateFields)) {
                $updateParams[] = (string)$orderKey;
                $updateParams[] = (int)$subscriberId;
                DB::exec(
                    "UPDATE i_user_payments SET " . implode(', ', $updateFields) . " WHERE order_key = ? AND payer_iuid_fk = ?",
                    $updateParams
                );
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            rq_epoch_log('Failed creating epoch subscription rows', [
                'order_key' => $orderKey,
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'error',
                'message' => $LANG['something_wrong'] ?? 'Something went wrong.',
            ];
        }

        try {
            if (!defined('INORA_METHODS_CONFIG')) {
                define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
            }
            require_once '../includes/payment/vendor/autoload.php';

            $epochService = new EpochService();
            $response = $epochService->processEpochSubscription([
                'paymentOption' => 'epoch',
                'payment_type' => 'subscription',
                'order_id' => (string)$orderKey,
                'order_amount' => (string)$amountFormatted,
                'amount' => (string)$amountFormatted,
                'currency' => (string)$planCurrency,
                'amounts' => [
                    (string)$planCurrency => (string)$amountFormatted,
                ],
                'subscriber_id' => (int)$subscriberId,
                'payer_id' => (int)$subscriberId,
                'payer_email' => (string)$subscriberEmail,
                'payer_name' => (string)$subscriberName,
                'plan_id' => (string)$planId,
                'description' => 'Subscription',
                'subscription_scope' => (string)$scopeValue,
                'subscription_ref_id' => $scopeRefId,
                'epoch_nonce' => (string)$nonce,
                'epoch_signature' => (string)$signature,
                'metadata' => [
                    'subscription_scope' => (string)$scopeValue,
                    'subscription_ref_id' => $scopeRefId,
                    'plan_id' => (string)$planId,
                    'subscriber_id' => (int)$subscriberId,
                    'subscribed_id' => (int)$subscribedId,
                    'order_key' => (string)$orderKey,
                ],
            ]);
        } catch (Throwable $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        $isSuccess = is_array($response)
            && ($response['status'] ?? '') === 'success'
            && !empty($response['post_url'])
            && !empty($response['form_fields'])
            && is_array($response['form_fields']);

        if (!$isSuccess) {
            DB::exec(
                "DELETE FROM i_user_subscription_intents WHERE order_key = ? AND iuid_fk = ? AND payment_option = 'epoch'",
                [(string)$orderKey, (int)$subscriberId]
            );
            DB::exec(
                "DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ? AND payment_option = 'epoch' AND payment_type = 'subscription'",
                [(string)$orderKey, (int)$subscriberId]
            );
            $message = (string)($response['message'] ?? ($LANG['something_wrong'] ?? 'Unable to start subscription.'));
            return [
                'status' => 'error',
                'message' => $message,
            ];
        }

        return [
            'status' => 'success',
            'post_url' => (string)$response['post_url'],
            'form_fields' => (array)$response['form_fields'],
            'redirect_method' => (string)($response['redirect_method'] ?? 'post'),
            'order_id' => (string)$orderKey,
            'paymentOption' => 'epoch',
            'epoch_nonce' => (string)$nonce,
            'epoch_signature' => (string)$signature,
        ];
    }
}

$type = null;
if (isset($_POST['f'])) {
    $type = $iN->iN_Secure($_POST['f']);
} elseif (isset($_GET['f'])) {
    $type = $iN->iN_Secure($_GET['f']);
}
if ($logedIn == '1' && $type) {
    $runnerTypes = [
        'subscribeWithYookassa',
        'communitySubscribeWithYookassa',
        'moreposts',
        'moreMessage',
        'moreComment'
    ];
    if (in_array($type, $runnerTypes, true)) {
        $iN->iN_RunYooKassaRenewalRunner();
    }
}
if ($type === 'diditWebhook') {
    header('Content-Type: application/json; charset=utf-8');
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $rawBody = file_get_contents('php://input');
    $payload = json_decode((string)$rawBody, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $normalizedHeaders = [];
    foreach ($headers as $key => $value) {
        $normalizedHeaders[strtolower((string)$key)] = $value;
    }
    $signature = trim((string)($normalizedHeaders['x-signature-simple'] ?? ''));
    $timestampRaw = trim((string)($normalizedHeaders['x-timestamp'] ?? ''));
    if ($signature === '' || $timestampRaw === '' || !ctype_digit($timestampRaw)) {
        http_response_code(403);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $timestamp = (int)$timestampRaw;
    if (abs(time() - $timestamp) > 300) {
        http_response_code(403);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $diditWebhookSecret = isset($diditAgeVerifClientSecret) ? trim((string)$diditAgeVerifClientSecret) : '';
    if ($diditWebhookSecret === '') {
        http_response_code(403);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $found = false;
    $sessionId = rq_find_nested_field($payload, 'session_id', $found);
    if (!$found) {
        $sessionId = rq_find_nested_field($payload, 'sessionId', $found);
    }
    $found = false;
    $status = rq_find_nested_field($payload, 'status', $found);
    $found = false;
    $createdAt = rq_find_nested_field($payload, 'created_at', $found);
    if (!$found) {
        $createdAt = rq_find_nested_field($payload, 'createdAt', $found);
    }
    $sessionId = trim((string)$sessionId);
    $status = trim((string)$status);
    $createdAt = trim((string)$createdAt);
    if ($sessionId === '' || $status === '' || $createdAt === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $signaturePayload = $sessionId . '|' . $status . '|' . $createdAt;
    $expectedSignature = hash_hmac('sha256', $signaturePayload, $diditWebhookSecret);
    if (!hash_equals(strtolower($expectedSignature), strtolower($signature))) {
        http_response_code(403);
        echo json_encode(['status' => 'error']);
        exit;
    }
    $vendorFound = false;
    $vendorData = rq_find_nested_field($payload, 'vendor_data', $vendorFound);
    if (!$vendorFound) {
        $vendorData = rq_find_nested_field($payload, 'vendorData', $vendorFound);
    }
    $userId = 0;
    if ($vendorFound && is_numeric($vendorData)) {
        $userId = (int)$vendorData;
    }
    if ($userId <= 0) {
        $row = DB::one("SELECT iuid FROM i_users WHERE age_verify_ref = ? LIMIT 1", [$sessionId]);
        if ($row && isset($row['iuid'])) {
            $userId = (int)$row['iuid'];
        }
    }
    if ($userId <= 0) {
        echo json_encode(['status' => 'ok']);
        exit;
    }
    $normalizedStatus = strtoupper($status);
    $normalizedStatus = str_replace(' ', '_', $normalizedStatus);
    $approvedStatuses = ['APPROVED', 'VERIFIED', 'SUCCESS'];
    $inProgressStatuses = ['NOT_STARTED', 'IN_PROGRESS', 'IN_REVIEW', 'PENDING'];
    $failedStatuses = ['DECLINED', 'REJECTED', 'FAILED', 'ABANDONED', 'EXPIRED', 'CANCELED', 'CANCELLED'];
    $isApproved = in_array($normalizedStatus, $approvedStatuses, true);
    $isInProgress = in_array($normalizedStatus, $inProgressStatuses, true);
    $isFailed = in_array($normalizedStatus, $failedStatuses, true);
    if (!$isApproved && !$isInProgress && !$isFailed) {
        $isInProgress = true;
    }
    $current = DB::one(
        "SELECT age_verify_status, age_verify_ref FROM i_users WHERE iuid = ? LIMIT 1",
        [(int)$userId]
    );
    $targetStatus = $isApproved ? '1' : '0';
    $currentStatus = $current ? (string)($current['age_verify_status'] ?? '0') : '0';
    $currentRef = $current ? (string)($current['age_verify_ref'] ?? '') : '';
    if ($currentStatus === $targetStatus && $currentRef === $sessionId) {
        echo json_encode(['status' => 'ok']);
        exit;
    }
    if ($isApproved) {
        DB::exec(
            "UPDATE i_users SET age_verify_status = '1', age_verify_provider = ?, age_verified_at = ?, age_verify_ref = ? WHERE iuid = ?",
            ['didit', time(), $sessionId, (int)$userId]
        );
    } else {
        DB::exec(
            "UPDATE i_users SET age_verify_status = '0', age_verify_provider = ?, age_verified_at = NULL, age_verify_ref = ? WHERE iuid = ?",
            ['didit', $sessionId, (int)$userId]
        );
    }
    echo json_encode(['status' => 'ok']);
    exit;
}
if ($type === 'downloadAccountExport') {
	if ($logedIn != '1') {
		http_response_code(403);
		exit('404');
	}
	$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
	$export = $iN->iN_GetUserAccountExportByToken((int)$userID, $token);
	if (!$export) {
		http_response_code(404);
		exit('404');
	}
	$filePath = (string)($export['absolute_path'] ?? '');
	if ($filePath === '' || !is_file($filePath)) {
		http_response_code(404);
		exit('404');
	}
	$fileTime = (int)($export['created_at'] ?? time());
	$downloadName = 'account_export_' . (int)$userID . '_' . date('Ymd_His', $fileTime) . '.zip';
	while (ob_get_level() > 0) {
		@ob_end_clean();
	}
	header('Content-Description: File Transfer');
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . (string)filesize($filePath));
	header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('X-Content-Type-Options: nosniff');
	$iN->iN_MarkAccountExportDownloaded((int)($export['export_id'] ?? 0));
	readfile($filePath);
	exit;
}
if (isset($_POST['f']) && $logedIn == '1') {
	$loginFormClass = '';
	if ($type == 'topMenu') {
		include "../themes/$currentTheme/layouts/header/header_menu.php";
	}
    if ($type == 'topMessages') {
        $iN->iN_UpdateMessageNotificationStatus($userID);
        include "../themes/$currentTheme/layouts/header/messageNotifications.php";
    }
    if ($type == 'topNotifications') {
        $iN->iN_UpdateNotificationStatus($userID);
        include "../themes/$currentTheme/layouts/header/notifications.php";
    }
	if ($type == 'chooseLanguage') {
		include "../themes/$currentTheme/layouts/popup_alerts/chooseLanguage.php";
	}
	if ($type == "changeMyLang") {
		if (isset($_POST['id'])) {
			$langID = $iN->iN_Secure($_POST['id']);
			$updateUserLanguage = $iN->iN_UpdateLanguage($userID, $langID);
			if ($updateUserLanguage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	if ($type == 'topPoints') {
		$iN->iN_UpdateMessageNotificationStatus($userID);
		include "../themes/$currentTheme/layouts/header/points_box.php";
	}

	if ($type == 'notifications') {
		if (isset($_POST['last'])) {
			$lastID = $iN->iN_Secure($_POST['last']);
			$moreNotifications = $iN->iN_GetMoreNotificationList($userID, $scrollLimit, $lastID);
			if ($moreNotifications) {
				include "../themes/$currentTheme/layouts/loadmore/morenotifications.php";
			} else {
				echo '<div class="nomore"><div class="no_more_in">' . $LANG['no_more_notifications'] . '</div></div>';
			}
		}
	}
	if ($type == 'whoSee') {
		if (isset($_POST['who']) && in_array($_POST['who'], $whoCanSeeArrays)) {
			$whoID = $iN->iN_Secure($_POST['who']);
			$updateWhoCanSee = $iN->iN_UpdateWhoCanSeePost($userID, $whoID);
			if ($updateWhoCanSee) {
				if ($whoID == 1) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('50') . '</div> ' . $LANG['weveryone'];
				} else if ($whoID == 2) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('15') . '</div> ' . $LANG['wfollowers'];
				} else if ($whoID == 3) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('51') . '</div> ' . $LANG['wsubscribers'];
				} else if ($whoID == 4) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('9') . '</div> ' . $LANG['wpremium'];
				}
				echo html_entity_decode($UpdatedWhoCanSee);
			} else {
				echo '403';
			}
		}
	}
	if ($type == 'pw_premium') {
		echo '<div class="point_input_wrapper">
            <input type="text" name="point" class="pointIN" id="point" onkeypress="return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)" placeholder="' . $LANG['write_points'] . '">
            <div class="box_not box_not_padding_left">' . $LANG['point_wanted'] . '</div>
        </div>';
	}

    if ($type === 'blog_reaction') {
        header('Content-Type: application/json; charset=utf-8');
        if ($logedIn != '1') {
            echo json_encode(['status' => 'error', 'message' => 'login_required']);
            exit();
        }
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => 'invalid_csrf']);
            exit();
        }
        if (isset($blogFeatureStatus) && (string)$blogFeatureStatus !== '1') {
            echo json_encode(['status' => 'error', 'message' => $LANG['blog_feature_disabled'] ?? 'blog_disabled']);
            exit();
        }
        if (isset($blogReactionsStatus) && (string)$blogReactionsStatus !== '1') {
            echo json_encode(['status' => 'error', 'message' => $LANG['blog_reactions_disabled_global'] ?? 'reactions_disabled']);
            exit();
        }
        $blogId = isset($_POST['blog_id']) ? (int) $_POST['blog_id'] : 0;
        $reaction = isset($_POST['reaction']) ? trim((string) $_POST['reaction']) : 'like';
        if ($blogId < 1) {
            echo json_encode(['status' => 'error', 'message' => 'invalid']);
            exit();
        }
        $blogRow = $iN->iN_GetBlogPostById($blogId);
        if (!$blogRow || ($blogRow['status'] ?? '') !== 'published') {
            echo json_encode(['status' => 'error', 'message' => 'invalid']);
            exit();
        }
        if (isset($blogRow['allow_reactions']) && (string)$blogRow['allow_reactions'] === '0') {
            echo json_encode(['status' => 'error', 'message' => $LANG['blog_reactions_disabled'] ?? 'reactions_disabled']);
            exit();
        }
        $result = $iN->iN_ToggleBlogReaction($userID, $blogId, $reaction);
        $counts = $iN->iN_BlogReactionCounts($blogId);
        echo json_encode(['status' => 'ok', 'data' => $result, 'counts' => $counts]);
        exit();
    }


if ($type === 'uploadReel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Feature toggle: allow only if reels feature is enabled in admin limits
    if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus !== '1') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => ($LANG['feature_disabled'] ?? 'Reels feature is disabled')]);
        exit;
    }
    $__dbg_id = uniqid('uploadReel_', true);
    rq_debug('uploadReel:start', ['id' => $__dbg_id, 'uid' => $userID]);
    // Log fatals
    register_shutdown_function(function() use ($__dbg_id) {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            rq_debug('uploadReel:shutdown_fatal', ['id' => $__dbg_id, 'err' => $e]);
        }
    });
    // Defensive limits: reels processing can be CPU heavy
    @set_time_limit(300);
    @ini_set('memory_limit', '512M');

    // Ensure required server functions are available
    if (!function_exists('shell_exec')) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Server config: shell_exec is disabled. Enable it to process videos.']);
        exit;
    }
    if (!$ffmpegCanConvert) {
        header('Content-Type: application/json');
        $message = $LANG['ffmpeg_not_configured'] ?? 'FFMPEG must be enabled and configured to upload reels.';
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
    // Set duration limit from configuration (admin limits)
    if (!defined('MAX_VIDEO_DURATION')) {
        $cfgDur = isset($maxVideoDuration) ? (int)$maxVideoDuration : 17;
        define('MAX_VIDEO_DURATION', $cfgDur > 0 ? $cfgDur : 17);
    }
    header('Content-Type: application/json');

    if (
        !isset($_FILES['uploading']['name']) ||
        !is_array($_FILES['uploading']['name']) ||
        count(array_filter((array) $_FILES['uploading']['name'])) === 0
    ) {
        echo json_encode(['status' => 'error', 'message' => 'upload_cancelled']);
        exit;
    }

    foreach ($_FILES['uploading']['name'] as $iname => $value) {
        if (empty($_FILES['uploading']['name'][$iname])) {
            continue;
        }
        $name = stripslashes($_FILES['uploading']['name'][$iname]);
        $size = $_FILES['uploading']['size'][$iname];
        $ext = strtolower(getExtension($name));
        $validFormats = explode(',', $availableFileExtensions);

        $tmp = $_FILES['uploading']['tmp_name'][$iname];
        $mimeType = $_FILES['uploading']['type'][$iname];

        if (!preg_match('/video\/*/', $mimeType)) {
            echo json_encode(['status' => 'error', 'message' => $LANG['only_video_files_allowed']]);
            exit;
        }

        if (!in_array($ext, $validFormats, true)) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_file_format']]);
            exit;
        }

        if (convert_to_mb($size) > $availableUploadFileSize) {
            echo json_encode(['status' => 'error', 'message' => $LANG['file_is_too_large']]);
            exit;
        }

        $microtime = microtime();
        $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
        $uploadedFileName = 'reel_' . $removeMicrotime . '_' . $userID;
        $filenameWithExt = $uploadedFileName . '.' . $ext;
        $todayDir = date('Y-m-d');
        $uploadDir = $uploadFile . $todayDir . '/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedPath = $uploadDir . $filenameWithExt;

        if (!move_uploaded_file($tmp, $uploadedPath)) {
            rq_debug('uploadReel:move_uploaded_file_failed', ['tmp' => $tmp, 'dst' => $uploadedPath, 'err' => error_get_last()]);
            echo json_encode(['status' => 'error', 'message' => $LANG['upload_failed']]);
            exit;
        }

        require_once '../includes/convertToMp4Format.php';
        require_once '../includes/convertVideoToBlurredReelsFormat.php';
        require_once '../includes/createVideoThumbnail.php';

        // Resolve ffmpeg binary: prefer configured path, then PATH lookup, then fallback name
        $ffmpegBin = rq_resolve_ffmpeg_bin($ffmpegPath);
        if (!$ffmpegBin) { $ffmpegBin = 'ffmpeg'; }

        $filesRoot = rtrim($uploadFile, '/'); // absolute .../uploads/files
        $convertedDir = $filesRoot . '/' . $todayDir;
        if (!is_dir($convertedDir)) { @mkdir($convertedDir, 0755, true); }
        $convertedPath = $convertedDir . '/' . $uploadedFileName . '.mp4';

        // Orijinal dosya MP4 değilse dönüştür
        rq_debug('uploadReel:post-move', ['path' => $uploadedPath, 'ext' => $ext]);
        $converted = convertToMp4Format($ffmpegBin, $uploadedPath, $convertedDir, $uploadedFileName);
        if ($converted && file_exists($converted)) {
            $convertedPath = $converted;
            $checkDurationPath = $convertedPath;
        } else {
            rq_debug('uploadReel:convertToMp4Format_failed', ['src' => $uploadedPath, 'dstDir' => $convertedDir]);
            // Fall back to original placement to avoid blocking user, but warn them
            if (!file_exists($convertedDir)) { @mkdir($convertedDir, 0755, true); }
            if ($uploadedPath !== $convertedPath) { @rename($uploadedPath, $convertedPath); }
            $checkDurationPath = $convertedPath;
        }
        // ffprobe yolu: config > PATH autodetect > binary name fallback
        $probeBin = rq_resolve_ffprobe_bin($ffprobePath);
        if (!$probeBin) { $probeBin = ''; }
        // Süreyi kontrol et
        $projectRoot = dirname(__DIR__);
        $durationTarget = '';
        if (preg_match('~^(/|[A-Za-z]:[\\\\/])~', $checkDurationPath)) {
            $durationTarget = $checkDurationPath;
        } else {
            $possiblePath = $projectRoot . '/' . ltrim($checkDurationPath, './');
            $durationTarget = realpath($possiblePath) ?: $possiblePath;
        }
        $ffprobeCmd = '';
        $durationOutput = '';
        if ($probeBin !== '') {
            $ffprobeCmd = escapeshellcmd($probeBin)
                . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
                . escapeshellarg($durationTarget) . ' 2>&1';
            $durationOutput = shell_exec($ffprobeCmd);
        }
        $duration = floatval($durationOutput);
        $ffmpegDurationCmd = '';
        $ffmpegDurationOutput = '';
        if ($duration === 0.0) {
            $ffmpegDurationCmd = escapeshellcmd($ffmpegBin)
                . ' -i ' . escapeshellarg($durationTarget) . ' 2>&1';
            $ffmpegDurationOutput = shell_exec($ffmpegDurationCmd);
            $duration = rq_parse_ffmpeg_duration((string)$ffmpegDurationOutput);
        }

        // Debug log (store under includes/logs to stay consistent with other app logs)
        $logDir = __DIR__ . '/../includes/logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
        $logFile = $logDir . '/reels_conversion_debug.log';
        $logBody = "FFPROBE_CMD: $ffprobeCmd\nFFPROBE_RAW: $durationOutput\nFFMPEG_DUR_CMD: $ffmpegDurationCmd\nFFMPEG_DUR_RAW: $ffmpegDurationOutput\nDURATION: $duration\n";
        @file_put_contents($logFile, $logBody, FILE_APPEND);

        if ($duration === 0.0) {
            rq_debug('uploadReel:ffprobe_zero_duration', ['cmd' => $ffprobeCmd, 'raw' => $durationOutput]);
            echo json_encode(['status' => 'error', 'message' => $LANG['unable_to_read_video_duration']]);
            exit;
        }

        if ($duration > MAX_VIDEO_DURATION) {
            unlink($convertedPath);
            echo json_encode([
                'status' => 'error',
                'message' => str_replace('{seconds}', MAX_VIDEO_DURATION, $LANG['video_length_exceeds_limit'])
            ]);
            exit;
        }

        $reelsDir = rtrim($uploadRoot, '/') . '/reels/' . $todayDir;
        if (!file_exists($reelsDir)) {
            mkdir($reelsDir, 0755, true);
        }

        $finalReelsPath = convertVideoToBlurredReelsFormat($ffmpegBin, $convertedPath, $reelsDir);
        if (!$finalReelsPath || !file_exists($finalReelsPath)) {
            rq_debug('uploadReel:convertVideoToBlurredReelsFormat_failed', ['input' => $convertedPath, 'outdir' => $reelsDir, 'out' => $finalReelsPath]);
            echo json_encode(['status' => 'error', 'message' => $LANG['reels_conversion_failed']]);
            exit;
        }

        $toRelative = static function (string $path): string {
            if (defined('APP_ROOT_PATH') && strpos($path, APP_ROOT_PATH) === 0) {
                return ltrim(substr($path, strlen(APP_ROOT_PATH)), '/');
            }
            if (strpos($path, '../') === 0) {
                return ltrim(substr($path, 3), '/');
            }
            return ltrim($path, '/');
        };
        $relativePath = $toRelative($finalReelsPath);
        $ext = 'mp4';

        $thumbnailPath = createVideoThumbnailInSameDir($ffmpegBin, $finalReelsPath);
        // Normalize to a storage-relative path (e.g., 'uploads/...') for publishing/DB
        if ($thumbnailPath) {
            $thumbnailPath = $toRelative($thumbnailPath);
        } else {
            $thumbnailPath = 'uploads/web.png';
        }
        rq_debug('uploadReel:converted', ['video' => $finalReelsPath, 'thumb' => $thumbnailPath]);

        // Publish final reel + thumbnail via unified storage
        $reelPublishKeys = [];
        if (is_file('../' . $relativePath)) { $reelPublishKeys[] = $relativePath; }
        if ($thumbnailPath && is_file('../' . $thumbnailPath)) { $reelPublishKeys[] = $thumbnailPath; }
        if ($reelPublishKeys) {
            try { storage_publish_many($reelPublishKeys, true, true); }
            catch (Throwable $e) { rq_debug('uploadReel:storage_publish_many_exception', ['msg' => $e->getMessage()]); }
        }
        $newUploadId = $iN->iN_INSERTUploadedReelFile($userID, $relativePath, $thumbnailPath, $thumbnailPath, $ext);
        $getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $relativePath);

        $payload = [
            'status' => 'success',
            'file_id' => $getUploadedFileID ?: ['upload_id' => (int)$newUploadId],
            'uploaded_file_path' => $relativePath,
            'upload_thumbnail_file_path' => $thumbnailPath
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            rq_debug('uploadReel:json_encode_failed', ['err' => json_last_error_msg(), 'payload' => $payload]);
            echo json_encode(['status' => 'error', 'message' => 'json_encode_failed']);
        } else {
            rq_debug('uploadReel:done', ['id' => $__dbg_id, 'upload_id' => $newUploadId, 'rel' => $relativePath]);
            echo $json;
        }
        exit;
    }
}
	/*Video Custom Tumbnail*/
	if($type == 'vTumbnail'){
		if(isset($_POST['id']) && !empty($_POST['id'])){
			$dataID = $iN->iN_Secure($_POST['id']);
			$checkIDExist = $iN->iN_CheckImageIDExist($dataID, $userID);
			if($checkIDExist){
				if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
					if (
						!isset($_FILES['uploading']['name']) ||
						!is_array($_FILES['uploading']['name']) ||
						count(array_filter((array) $_FILES['uploading']['name'])) === 0
					) {
						exit;
					}
					foreach ($_FILES['uploading']['name'] as $iname => $value) {
						if (empty($_FILES['uploading']['name'][$iname])) {
							continue;
						}
						$name = stripslashes($_FILES['uploading']['name'][$iname]);
						$size = $_FILES['uploading']['size'][$iname];
						$ext = getExtension($name);
						$ext = strtolower($ext);
						$valid_formats = explode(',', $availableVerificationFileExtensions);
						if (in_array($ext, $valid_formats)) {
						if (!(convert_to_mb($size) < $availableUploadFileSize)) { echo iN_HelpSecure($size); continue; } else {
								$microtime = microtime();
								$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
								$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
								$getFilename = $UploadedFileName . "." . $ext;
								// Change the image ame
								$tmp = $_FILES['uploading']['tmp_name'][$iname];
								$mimeType = $_FILES['uploading']['type'][$iname];
								$d = date('Y-m-d');
								if (preg_match('/video\/*/', $mimeType)) {
									$fileTypeIs = 'video';
								} else if (preg_match('/image\/*/', $mimeType)) {
									$fileTypeIs = 'Image';
								}
								if (!file_exists($uploadFile . $d)) {
									$newFile = mkdir($uploadFile . $d, 0755);
								}
								if (!file_exists($xImages . $d)) {
									$newFile = mkdir($xImages . $d, 0755);
								}
								if (!file_exists($xVideos . $d)) {
									$newFile = mkdir($xVideos . $d, 0755);
								}
								if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
                                  $tumbFilePath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
								  $thePath = '../uploads/files/'.$d.'/'.$UploadedFileName . '.' . $ext;
								  if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.'.$ext;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
								}else{
									exit($LANG['upload_failed']);
								}
										// Unified publish handled below
                                // Publish new thumbnail and optional xvideos preview; then update and echo URL
                                $keys = [];
                                $k1 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                $k2 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                if (is_file('../' . $k1)) { $keys[] = $k1; }
                                if (is_file('../' . $k2)) { $keys[] = $k2; }
                                if ($keys) { storage_publish_many($keys, true, true); }
                                $UploadSourceUrl = storage_public_url($k2);

                                // Update DB record to point custom thumbnail and respond with public URL
                                $updateTumbData = $iN->iN_UpdateUploadedFiles($userID, $tumbFilePath, $dataID);
                                if($updateTumbData){ echo $UploadSourceUrl; }
								}
							}
						}
					}
				}
			}
		}
	}
if ($type == 'upload') {
    // Unified, simplified upload handler (bypasses legacy provider-specific branches)
    if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == 'POST') {
        if (
            !isset($_FILES['uploading']['name']) ||
            !is_array($_FILES['uploading']['name']) ||
            count(array_filter((array) $_FILES['uploading']['name'])) === 0
        ) {
            exit; // User cancelled or no files selected; avoid warnings.
        }
        foreach ($_FILES['uploading']['name'] as $iname => $value) {
            if (empty($_FILES['uploading']['name'][$iname])) {
                continue;
            }
            $name = stripslashes($_FILES['uploading']['name'][$iname]);
            $size = $_FILES['uploading']['size'][$iname];
            $ext = strtolower(getExtension($name));
            $valid_formats = explode(',', $availableFileExtensions);
            if (!in_array($ext, $valid_formats)) { continue; }
            if (convert_to_mb($size) >= $availableUploadFileSize) { echo iN_HelpSecure($size); continue; }

            $microtime = microtime();
            $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
            $UploadedFileName = 'image_' . $removeMicrotime . '_' . $userID;
            $getFilename = $UploadedFileName . '.' . $ext;

            $tmp = $_FILES['uploading']['tmp_name'][$iname];
            $mimeType = $_FILES['uploading']['type'][$iname];
            $d = date('Y-m-d');

            // Determine file type
            if (preg_match('/video\/*/', $mimeType) || $mimeType === 'application/octet-stream') {
                $fileTypeIs = 'video';
            } else if (preg_match('/image\/*/', $mimeType)) {
                $fileTypeIs = 'Image';
            } else if (preg_match('/audio\/*/', $mimeType)) {
                $fileTypeIs = 'audio';
            } else { $fileTypeIs = 'Image'; }
            $canUseGd = extension_loaded('gd');

            // Ensure directories
            if (!file_exists($uploadFile . $d)) { @mkdir($uploadFile . $d, 0755, true); }
            if (!file_exists($xImages . $d)) { @mkdir($xImages . $d, 0755, true); }
            if (!file_exists($xVideos . $d)) { @mkdir($xVideos . $d, 0755, true); }
            $wVideos = rtrim(UPLOAD_DIR_VIDEOS, '/') . '/';
            if (!file_exists($wVideos . $d)) { @mkdir($wVideos . $d, 0755, true); }

            if ($fileTypeIs === 'video' && !$ffmpegCanConvert && !in_array($ext, $nonFfmpegAvailableVideoFormat)) { exit('303'); }
            if (!move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) { echo $LANG['something_wrong']; continue; }

            $postTypeIcon = '';
            $pathFile = '';
            $pathXFile = '';
            $tumbnailPath = '';
            $UploadSourceUrl = '';

            if ($fileTypeIs === 'video') {
                $postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
                $sourceFs = $uploadFile . $d . '/' . $getFilename;
                if ($ffmpegCanConvert) {
                    require_once '../includes/convertToMp4Format.php';
                    require_once '../includes/createVideoThumbnail.php';
                    $convertedFs = convertToMp4Format($ffmpegPath, $sourceFs, $uploadFile . $d, $UploadedFileName);
                    if (!$convertedFs || !file_exists($convertedFs)) {
                        if (function_exists('rq_debug')) {
                            rq_debug('upload:convertToMp4Format_failed', ['src' => $sourceFs, 'ffmpeg' => $ffmpegPath]);
                        }
                        $convertedFs = $sourceFs;
                    }
                    $ffmpegExec = escapeshellcmd($ffmpegPath ?: 'ffmpeg');
                    $thumbFs = createVideoThumbnailInSameDir($ffmpegPath, $convertedFs);
                    $pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                    $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                    if (!file_exists('../uploads/xvideos/' . $d)) { @mkdir('../uploads/xvideos/' . $d, 0755, true); }
                    $xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                    $safeCmd = $ffmpegExec . ' -hide_banner -loglevel error -y'
                        . ' -ss 00:00:01 -i ' . escapeshellarg($convertedFs)
                        . ' -c copy -movflags +faststart -avoid_negative_ts make_zero'
                        . ' -t 00:00:04 ' . escapeshellarg($xVideoFirstPath) . ' 2>&1';
                    shell_exec($safeCmd);
                    $videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
                    if (file_exists($videoTumbnailPath)) {
                        try { $dir = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg'; $image = new ImageFilter(); $image->load($videoTumbnailPath)->pixelation($pixelSize)->saveFile($dir, 100, 'jpg'); } catch (Exception $e) { echo '<span class="request_warning">' . $e->getMessage() . '</span>'; }
                    }
                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                    $thePathM = '../' . $tumbnailPath;
                    if ($canUseGd && file_exists($thePathM) && ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes')) {
                        watermark_apply_batch(
                            [$thePathM],
                            $siteWatermarkLogo,
                            $LinkWatermarkStatus,
                            watermark_label($base_url, $userName)
                        );
                    }
                    $publishKeys = [];
                    $mp4Key = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                    $xclipKey = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                    $thumbJpg = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                    $thumbPng = 'uploads/files/' . $d . '/' . $UploadedFileName . '.png';
                    if (is_file('../' . $mp4Key)) { $publishKeys[] = $mp4Key; }
                    if (is_file('../' . $xclipKey)) { $publishKeys[] = $xclipKey; }
                    if (is_file('../' . $thumbJpg)) { $publishKeys[] = $thumbJpg; }
                    if (is_file('../' . $thumbPng)) { $publishKeys[] = $thumbPng; }
                    $publishedMap = !empty($publishKeys) ? storage_publish_many($publishKeys, true, true) : [];
                    $UploadSourceUrl =
                        $publishedMap[$thumbJpg] ??
                        $publishedMap[$thumbPng] ??
                        $publishedMap[$mp4Key] ??
                        storage_public_url($thumbJpg);
                    if (!$UploadSourceUrl) {
                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                        $tumbnailPath = 'uploads/web.png';
                    }
                    $ext = 'mp4';
                } else {
                    $pathFile = 'uploads/files/' . $d . '/' . $getFilename;
                    $pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
                    storage_publish_many([$pathFile], true, true);
                    $UploadSourceUrl = storage_public_url($pathFile);
                }
            } else if ($fileTypeIs === 'Image') {
                $postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
                $pathFile = 'uploads/files/' . $d . '/' . $getFilename;
                $pixelKey = 'uploads/pixel/' . $d . '/' . $getFilename;
                $resizedFileTwo = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;

                // Create a resized copy; if it fails, fall back to original
                if ($canUseGd) {
                    try {
                        $tb = new ThumbAndCrop();
                        $tb->openImg('../' . $pathFile);
                        $newHeight = $tb->getRightHeight(500);
                        $tb->creaThumb(500, $newHeight);
                        $tb->setThumbAsOriginal();
                        $tb->creaThumb(500, $newHeight);
                        $tb->saveThumb($resizedFileTwo);
                    } catch (Exception $e) {
                        // Ignore and fall back to original below
                    }
                }

                $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
                if (!$canUseGd) {
                    $tumbnailPath = $pathFile;
                }
                if (!is_file('../' . $tumbnailPath)) {
                    // Resized file missing; use original path as thumbnail
                    $tumbnailPath = $pathFile;
                }

                if ($canUseGd && $ext !== 'gif') {
                    $thePathM = '../' . $pathFile;
                    if ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes') {
                        watermark_apply_batch(
                            [$thePathM, '../' . $tumbnailPath],
                            $siteWatermarkLogo,
                            $LinkWatermarkStatus,
                            watermark_label($base_url, $userName)
                        );
                    }
                }

                // Generate pixelated preview (best-effort)
                if ($canUseGd) {
                    try {
                        $dir = '../' . $pixelKey;
                        if (!file_exists(dirname($dir))) { @mkdir(dirname($dir), 0755, true); }
                        $image = new ImageFilter();
                        $image->load('../' . $pathFile)->pixelation($pixelSize)->saveFile($dir, 100, 'jpg');
                    } catch (Exception $e) {
                        echo '<span class="request_warning">' . $e->getMessage() . '</span>';
                    }
                }

                // Publish available files and pick a valid URL to show
                storage_publish_many([$pathFile, $pixelKey, $tumbnailPath], true, true);
                $UploadSourceUrl = storage_publish_pick_url([$tumbnailPath, $pathFile]) ?? ($base_url . 'uploads/web.png');
                $pathXFile = $pixelKey;
            } else { // audio
                $postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
                $pathFile = 'uploads/files/' . $d . '/' . $getFilename;
                $tumbnailPath = 'src/audio.png';
                $pathXFile = 'src/audio.png';
                storage_publish_many([$pathFile], true, true);
                $UploadSourceUrl = storage_public_url($pathFile);
            }

            // Save and render
            $insertFileFromUploadTable = $iN->iN_INSERTUploadedFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
            $getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
            $uploadTumbnail = '';
            if ($fileTypeIs == 'video') {
                $uploadTumbnail = '<div class="v_custom_tumb"><label for="vTumb_' . $getUploadedFileID['upload_id'] . '"><div class="i_image_video_btn"><div class="pbtn pbtn_plus">' . $LANG['custom_tumbnail'] . '</div></div><input type="file" id="vTumb_' . $getUploadedFileID['upload_id'] . '" class="imageorvideo cTumb editAds_file" data-id="' . $getUploadedFileID['upload_id'] . '" name="uploading[]" data-id="tupload"></label></div>';
            }
            if ($fileTypeIs == 'video' || $fileTypeIs == 'Image') {
                echo '<div class="i_uploaded_item iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '" id="' . $getUploadedFileID['upload_id'] . '">' . $postTypeIcon . '<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">' . $iN->iN_SelectedMenuIcon('5') . '</div><div class="i_uploaded_file" id="viTumb' . $getUploadedFileID['upload_id'] . '" style="background-image:url(' . $UploadSourceUrl . ');"><img class="i_file" id="viTumbi' . $getUploadedFileID['upload_id'] . '" src="' . $UploadSourceUrl . '" alt="tumbnail"></div>' . $uploadTumbnail . '</div>';
            } else {
                echo '<div id="playing_' . $getUploadedFileID['upload_id'] . '" class="green-audio-player"><div class="i_uploaded_item nonePoint iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '"  id="' . $getUploadedFileID['upload_id'] . '"></div><audio crossorigin="" preload="none"><source src="' . $UploadSourceUrl . '" type="audio/mp3" /></audio><script>$(function(){ new GreenAudioPlayer("#playing_' . $getUploadedFileID['upload_id'] . '", { stopOthersOnPlay: true, showTooltips: true, showDownloadButton: false, enableKeystrokes: true });});</script></div>';
            }
        }
        // Stop executing legacy upload code below
        exit;
    }
}
		/*DELETE UPLOADED FILE BEFORE PUBLISH*/
	if ($type == 'delete_file') {
		if (isset($_POST['file'])) {
			$fileID = $iN->iN_Secure($_POST['file']);
			$deleteFileFromData = $iN->iN_DeleteFile($userID, $fileID);
			if ($deleteFileFromData) {
				echo '200';
			} else {
				// If the file is already gone, treat as success to avoid blocking UX after a cancel.
				$fileExists = $iN->iN_CheckFileIDExist($fileID);
				echo $fileExists ? '404' : '200';
			}
		}
	}
	/*Insert New Reels*/
	if($type == 'insertNewReel'){
	    if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus !== '1') { exit('reels_disabled'); }
	    rq_debug('insertNewReel:start', ['uid' => $userID, 'raw_txt' => isset($_POST['txt']) ? (string)$_POST['txt'] : null, 'raw_file' => isset($_POST['file']) ? (string)$_POST['file'] : null]);
	    if(isset($_POST['txt']) && isset($_POST['file'])){
	        $text = $iN->iN_Secure($_POST['txt']);
	        $file = $iN->iN_Secure($_POST['file']);
	        if(empty($iN->iN_Secure($text)) && empty($file)){
	           echo '200';
	           exit();
	        }
	        if($file != '' && !empty($file) && $file != 'undefined'){
				$trimValue = rtrim($file, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach($explodeFiles as $explodeFile){
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
					if(isset($uploadedFileID)){
                       $updateUploadStatus = $iN->iN_UpdateUploadStatus($uploadedFileID);
					}
					if(empty($uploadedFileID)){
					   exit('204');
					}
				}
			}

	        if (!empty($text)) {
				$slug = $iN->url_slugies(mb_substr($text, 0, 55, "utf-8"));
			} else {
				$slug = $iN->random_code(8);
			}
			if ($userWhoCanSeePost == '4') {
				$premiumPointAmount = $iN->iN_Secure($_POST['point']);
				if ($premiumPointAmount == '' || !isset($premiumPointAmount) || empty($premiumPointAmount)) {
					exit('201');
				}
				$number = preg_match("/^(?!\.)(?!.*\.$)(?!.*?\.\.)[0-9.]+$/", $premiumPointAmount, $m);

				$premiumPointAmount = isset($m[0]) ? $m[0] : NULL;
				if(!$premiumPointAmount){
                   exit('201');
				}
			} else { $premiumPointAmount = '';}
			$hashT = $iN->iN_hashtag($text);
			$postFromData = $iN->iN_InsertNewReelsPost($userID, $iN->iN_Secure($text), $slug, $file, $userWhoCanSeePost, $iN->url_Hash($hashT), $iN->iN_Secure($premiumPointAmount), $autoApprovePostStatus);
	        if ($postFromData) {
	            rq_debug('insertNewReel:success', ['post_id' => $postFromData['post_id'] ?? null, 'file' => $file]);
	            echo 'REELS_ID:' . $file;
                exit();
	        }
	        rq_debug('insertNewReel:failed');
	    }
	}
	/*INSERT NEW POST*/
	if ($type == 'newPost') {
		if (isset($_POST['txt']) && isset($_POST['file'])) {
			$isPollRequest = isset($_POST['is_poll']) && $_POST['is_poll'] === '1';
			$pollOptions = isset($_POST['poll_options']) ? (array)$_POST['poll_options'] : [];
            $isScheduled = isset($_POST['is_scheduled']) && $_POST['is_scheduled'] === '1';
			$communityID = isset($_POST['community_id']) ? (int)$iN->iN_Secure($_POST['community_id']) : 0;
			$communityData = null;
			$communityBadge = '';
			if ($communityID > 0) {
				if ($logedIn != 1) {
					exit('community_post_forbidden');
				}
				$communityData = $iN->iN_GetCommunityById($communityID);
				if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
					exit('community_not_found');
				}
				if ((string)($communityData['posting_enabled'] ?? '1') !== '1') {
					exit('community_posting_disabled');
				}
				$isAdmin = isset($userType) && (string)$userType === '2';
				$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
				$postingPolicy = (string)($communityData['posting_policy'] ?? 'owner_admin');
				if (!in_array($postingPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
					$postingPolicy = 'owner_admin';
				}
				$accessPolicy = (string)($communityData['access_policy'] ?? 'members_only');
				if (!in_array($accessPolicy, ['members_only', 'public'], true)) {
					$accessPolicy = 'members_only';
				}
				$isModerator = false;
				if (!$isOwner && !$isAdmin && $postingPolicy === 'owner_admin_moderators') {
					$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
					$isModerator = !empty($moderatorData);
				}
				$canPostToCommunity = $isOwner || $isAdmin;
				if (!$canPostToCommunity && $postingPolicy === 'owner_admin_moderators' && $isModerator) {
					$canPostToCommunity = true;
				}
				if (!$canPostToCommunity && $postingPolicy === 'members') {
					$canPostToCommunity = $iN->iN_IsCommunityAccessMember($communityData, $userID);
				}
				if (!$canPostToCommunity) {
					exit('community_post_forbidden');
				}
				if (!$isOwner && !$isAdmin && $iN->iN_IsCommunityPostDisabled($communityID, $userID)) {
					exit('community_post_forbidden');
				}
				if ($accessPolicy === 'members_only'
					&& (string)($communityData['subscription_required'] ?? '1') === '0'
					&& $userWhoCanSeePost == '3') {
					$userWhoCanSeePost = '1';
				}
			}
			if ($isPollRequest || $isScheduled) {
				include_once __DIR__ . '/../includes/csrf.php';
				if (!csrf_validate_from_request()) {
					exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
				}
			}
			$text = $iN->iN_Secure($_POST['txt']);
			$file = $iN->iN_Secure($_POST['file']);
			if ($file === 'undefined') {
				$file = '';
			}
			if (!$isPollRequest && empty($iN->iN_Secure($text)) && empty($file)) {
				echo '200';
				exit();
			}
			if ($isPollRequest) {
				if (empty($text)) {
					echo 'poll_question_required';
					exit();
				}
				if (empty($pollOptions)) {
					echo 'poll_options_required';
					exit();
				}
			}
            $scheduleTimestamp = null;
            if ($isScheduled) {
                $rawSchedule = isset($_POST['scheduled_at']) ? trim((string)$_POST['scheduled_at']) : '';
                if ($rawSchedule !== '') {
                    if (is_numeric($rawSchedule)) {
                        $scheduleTimestamp = (int)$rawSchedule;
                    } else {
                        $parsedTime = strtotime($rawSchedule);
                        if ($parsedTime !== false) {
                            $scheduleTimestamp = $parsedTime;
                        }
                    }
                }
                $now = time();
                $maxDays = isset($scheduledMaxDelayDays) ? (int)$scheduledMaxDelayDays : 30;
                if ($maxDays < 1) { $maxDays = 30; }
                if (!$scheduleTimestamp || $scheduleTimestamp <= $now || $scheduleTimestamp > $now + ($maxDays * 86400)) {
                    echo 'invalid_time';
                    exit();
                }
                if (!isset($scheduledPostsStatus) || (string)$scheduledPostsStatus !== '1') {
                    echo 'schedule_disabled';
                    exit();
                }
            }
			if($file != '' && !empty($file) && $file != 'undefined'){
				$trimValue = rtrim($file, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach($explodeFiles as $explodeFile){
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
					if(isset($uploadedFileID)){
                       $updateUploadStatus = $iN->iN_UpdateUploadStatus($uploadedFileID);
					}
					if(empty($uploadedFileID)){
					   exit('204');
					}
				}
			}
			if (!empty($text)) {
				$slug = $iN->url_slugies(mb_substr($text, 0, 55, "utf-8"));
			} else {
				$slug = $iN->random_code(8);
			}
			if ($userWhoCanSeePost == '4') {
				$premiumPointAmount = $iN->iN_Secure($_POST['point']);
				if ($premiumPointAmount == '' || !isset($premiumPointAmount) || empty($premiumPointAmount)) {
					exit('201');
				}
				$number = preg_match("/^(?!\.)(?!.*\.$)(?!.*?\.\.)[0-9.]+$/", $premiumPointAmount, $m);

				$premiumPointAmount = isset($m[0]) ? $m[0] : NULL;
				if(!$premiumPointAmount){
                   exit('201');
				}
			} else { $premiumPointAmount = '';}
			$hashT = $iN->iN_hashtag($text);
            $scheduleStatus = $isScheduled ? 'pending' : 'published';
			if ($isPollRequest) {
				$postFromData = $iN->iN_InsertNewPollPost($userID, $iN->iN_Secure($text), $slug, $pollOptions, $userWhoCanSeePost, $iN->url_Hash($hashT), $iN->iN_Secure($premiumPointAmount), $autoApprovePostStatus, $file, $scheduleTimestamp, $scheduleStatus);
				if (!$postFromData || isset($postFromData['error'])) {
					$pollError = isset($postFromData['error']) ? $postFromData['error'] : 'poll_create_failed';
					echo 'poll_' . $pollError;
					exit();
				}
			} else {
				$postFromData = $iN->iN_InsertNewPost($userID, $iN->iN_Secure($text), $slug, $file, $userWhoCanSeePost, $iN->url_Hash($hashT), $iN->iN_Secure($premiumPointAmount), $autoApprovePostStatus, 'normal', $scheduleTimestamp, $scheduleStatus);
			}

			if ($postFromData) {
				$userPostID = $postFromData['post_id'];
				$userPostOwnerID = $postFromData['post_owner_id'];
				$linkPreview = null;
				$hasMedia = !empty($file) && $file !== 'undefined';
				if (!empty($text) && !$hasMedia) {
					$linkPreview = $iN->iN_GetLinkPreviewFromText($text);
					if ($linkPreview) {
						$iN->iN_SavePostLinkPreview((int)$userPostID, $linkPreview);
					}
				}
				if ($communityID > 0) {
					$iN->iN_CreateCommunityPost($communityID, (int)$userPostID, $userID);
				}
                if ($isScheduled) {
                    $scheduledResponse = $iN->iN_SaveScheduledQueue((int)$userPostID, (int)$scheduleTimestamp);
                    if (!$scheduledResponse) {
                        echo 'schedule_failed';
                        exit();
                    }
                    $formattedScheduled = date('M d, Y H:i', (int)$scheduleTimestamp);
                    $payload = array(
                        'status' => 'scheduled',
                        'post_id' => (int)$userPostID,
                        'scheduled_for' => (int)$scheduleTimestamp,
                        'scheduled_for_text' => $formattedScheduled,
                        'message' => isset($LANG['schedule_created']) ? preg_replace('/\{time\}/', $formattedScheduled, $LANG['schedule_created']) : 'scheduled'
                    );
                    echo json_encode($payload);
                    exit();
                }
				if($ataNewPostPointAmount && $ataNewPostPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPoint($userID,$userPostID,$ataNewPostPointAmount);
				}
				$userPostText = isset($postFromData['post_text']) ? $postFromData['post_text'] : NULL;
				$userPostLinkUrl = $linkPreview['link_url'] ?? ($postFromData['link_url'] ?? null);
				$userPostLinkDomain = $linkPreview['link_domain'] ?? ($postFromData['link_domain'] ?? null);
				$userPostLinkTitle = $linkPreview['link_title'] ?? ($postFromData['link_title'] ?? null);
				$userPostLinkDescription = $linkPreview['link_description'] ?? ($postFromData['link_description'] ?? null);
				$userPostLinkImage = $linkPreview['link_image'] ?? ($postFromData['link_image'] ?? null);
                if($userPostText){
                   $iN->iN_InsertMentionedUsersForPost($userID, $userPostText, $userPostID, $userName,$userPostOwnerID);
				}
				$userPostFile = $postFromData['post_file'];
				$userPostCreatedTime = $postFromData['post_created_time'];
				$crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
				$userPostWhoCanSee = $postFromData['who_can_see'];
				if($autoApprovePostStatus == 'yes' && $userPostWhoCanSee == '4'){
					$approveNot = $LANG['congratulations_approved'];
					$postApprover = $iN->iN_GetAdminUserID();
					$approveUpdate = $iN->iN_UpdateApprovePostStatusAuto($postApprover, $iN->iN_Secure($userPostID), $iN->iN_Secure($userPostOwnerID), $iN->iN_Secure($approveNot));
				}
				$planIcon  = NULL;
				$checkPostBoosted=  NULL;
				$userPostWantStatus = $postFromData['post_want_status'];
				$userPostWantedCredit = $postFromData['post_wanted_credit'];
				$userPostStatus = $postFromData['post_status'];
				$userPostOwnerUsername = $postFromData['i_username'];
				$userPostOwnerUserFullName = $postFromData['i_user_fullname'];
				$userPostOwnerUserGender = $postFromData['user_gender'];
				$userPostHashTags = isset($postFromData['hashtags']) ? $postFromData['hashtags'] : NULL;
				$getUserPaymentMethodStatus = isset($postFromData['payout_method']) ? $postFromData['payout_method'] : NULL;
				$userPostCommentAvailableStatus = $postFromData['comment_status'];
				$userPostOwnerUserLastLogin = $postFromData['last_login_time'];
                $userProfileCategory = isset($postFromData['profile_category']) ? $postFromData['profile_category'] : NULL;
				$userPostType = isset($postFromData['post_type']) ? $postFromData['post_type'] : 'normal';
				$pollData = null;
				if ($userPostType === 'poll' && $iN->iN_CanViewPollForPost((int) $userPostID, ($logedIn ? (int) $userID : null))) {
					$pollData = $iN->iN_GetPollDetailsByPostId($userPostID, ($logedIn ? $userID : null));
				}
				$lastSeen = date("c", $userPostOwnerUserLastLogin);
            	$OnlineStatus = date("c", time());
	                $onlineWindowSeconds = 180;
	                $oStatus = time() - $onlineWindowSeconds;
	                if ((int) $userPostOwnerUserLastLogin > $oStatus) {
	                  $timeStatus = '<div class="userIsOnline flex_ tabing">'.$LANG['online'].'</div>';
	                } else {
	                  $timeStatus = '<div class="userIsOffline flex_ tabing">'.$LANG['offline'].'</div>';
	                }
				$userPostPinStatus = $postFromData['post_pined'];
				$slugUrl = $base_url . 'post/' . $postFromData['url_slug'] . '_' . $userPostID;
				$userPostSharedID = isset($postFromData['shared_post_id']) ? $postFromData['shared_post_id'] : NULL;
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				if ($communityID > 0 && $communityData) {
					$communityName = $communityData['title'] ?? '';
					$communitySlug = $communityData['slug'] ?? '';
					$communityUrl = $communitySlug ? $base_url . 'community/' . $communitySlug : $base_url . 'communities';
					$badgeLabel = $LANG['community_post_badge'] ?? 'Community';
					if ($communityName !== '') {
						$communityBadge = '<a class="community_post_badge" href="' . iN_HelpSecure($communityUrl) . '">' .
							iN_HelpSecure($badgeLabel) . ': ' . iN_HelpSecure($communityName) .
							'</a>';
					}
				}
				$userPostUserVerifiedStatus = $postFromData['user_verified_status'];
				$userProfileFrame = isset($postFromData['user_frame']) ? $postFromData['user_frame'] : NULL;
				if ($userPostOwnerUserGender == 'male') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($userPostOwnerUserGender == 'female') {
					$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($userPostOwnerUserGender == 'couple') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$userVerifiedStatus = '';
				if ($userPostUserVerifiedStatus == '1') {
					$userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$profileCategory = $pCt = $profileCategoryLink = '';
                if($userProfileCategory && $userPostUserVerifiedStatus == '1'){
                    $profileCategory = $userProfileCategory;
                    if(isset($PROFILE_CATEGORIES[$userProfileCategory])){
                        $pCt = isset($PROFILE_CATEGORIES[$userProfileCategory]) ? $PROFILE_CATEGORIES[$userProfileCategory] : NULL;
                    }else if(isset($PROFILE_SUBCATEGORIES[$userProfileCategory])){
                        $pCt = isset($PROFILE_SUBCATEGORIES[$userProfileCategory]) ? $PROFILE_SUBCATEGORIES[$userProfileCategory] : NULL;
                    }
                    $profileCategoryLink = '<a class="i_p_categoryp flex_ tabing_non_justify" href="'.$base_url.'creators?creator='.$userProfileCategory.'">'.$iN->iN_SelectedMenuIcon('65').$pCt.'</a>- ';
                }
				$onlySubs = '';
				$premiumPost = '';
				$onlySubsAction = '<div class="fr_subs uSubsModal transition" data-u="' . $userPostOwnerID . '">' .
					$iN->iN_SelectedMenuIcon('51') . $LANG['free_for_subscribers'] . '</div>';
				if ($communityID > 0 && $communityData) {
					$joinLabel = $LANG['join_community'] ?? 'Join Community';
					$joinIcon = $iN->iN_SelectedMenuIcon('51');
					$subscriptionRequiredValue = (string)($communityData['subscription_required'] ?? '1');
					if ($logedIn == 0) {
						$onlySubsAction = '<div class="fr_subs loginForm transition">' . $joinIcon . $joinLabel . '</div>';
					} elseif ($subscriptionRequiredValue === '0') {
						$onlySubsAction = '<div class="fr_subs communityJoinFree transition" data-community="' . (int)$communityID . '">' .
							$joinIcon . $joinLabel . '</div>';
					} else {
						$onlySubsAction = '<div class="fr_subs communityJoinModal transition" data-community="' . (int)$communityID . '">' .
							$joinIcon . $joinLabel . '</div>';
					}
				}
                if($userPostWhoCanSee == '1'){
                   $onlySubs = '';
                   $premiumPost = '';
                   $subPostTop = '';
                   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
                }else if($userPostWhoCanSee == '2'){
                   $subPostTop = '';
                   $premiumPost = '';
                   $wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
                   $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
                }else if($userPostWhoCanSee == '3'){
                   $subPostTop = 'extensionPost';
                   $premiumPost = '';
                   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
                   $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div>' . $onlySubsAction . '</div></div>';
                }else if($userPostWhoCanSee == '4'){
                  $subPostTop = 'extensionPost';
                  $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
                  $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
                  $onlySubs = '<div class="com_min_height"></div><div class="onlyPremium onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
                }
				$postStyle = '';
				if (empty($userPostText)) {
					$postStyle = 'nonePoint';
				}
				/*Comment*/
				$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
				$c = '';
				$TotallyPostComment = '';
				if ($c) {
					if ($getUserComments > 0) {
						$CountTheUniqComment = count($CountUniqPostCommentArray);
						$SecondUniqComment = $CountTheUniqComment - 5;
						if ($CountTheUniqComment > 5) {
							$getUserComments = $iN->iN_GetPostComments($userPostID, 5);
						}
					}
				}
				if ($logedIn == 0) {
					$getFriendStatusBetweenTwoUser = '1';
					$checkPostLikedBefore = '';
					$checkUserPurchasedThisPost = '0';
				} else {
					$getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
					$checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
					$checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
				}
				if ($checkPostLikedBefore) {
					$likeIcon = $iN->iN_SelectedMenuIcon('18');
					$likeClass = 'in_unlike';
				} else {
					$likeIcon = $iN->iN_SelectedMenuIcon('17');
					$likeClass = 'in_like';
				}
				if ($userPostCommentAvailableStatus == '1') {
					$commentStatusText = $LANG['disable_comment'];
				} else {
					$commentStatusText = $LANG['enable_comments'];
				}
				$pPinStatus = '';
				$pPinStatusBtn = $iN->iN_SelectedMenuIcon('29') . $LANG['pin_on_my_profile'];
				if ($userPostPinStatus == '1') {
					$pPinStatus = '<div class="i_pined_post" id="i_pined_post_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('62') . '</div>';
					$pPinStatusBtn = $iN->iN_SelectedMenuIcon('29') . $LANG['post_pined_on_your_profile'];
				}
				$pSaveStatusBtn = $iN->iN_SelectedMenuIcon('22');
				if ($iN->iN_CheckPostSavedBefore($userID, $userPostID) == '1') {
					$pSaveStatusBtn = $iN->iN_SelectedMenuIcon('63');
				}
				$likeSum = $iN->iN_TotalPostLiked($userPostID);
				if ($likeSum > '0') {
					$likeSum = $likeSum;
				} else {
					$likeSum = '';
				}
				$waitingApprove = '';
				if ($userPostStatus == '2') {
					$waitingApprove = '<div class="waiting_approve flex_">' . $iN->iN_SelectedMenuIcon('10') . $LANG['waiting_for_approve'] . '</div>';
					if ($logedIn == 0) {
						echo '<div class="i_post_body nonePoint body_' . $userPostID . '" id="' . $userPostID . '" data-last="' . $userPostID . '" ></div>';
					} else {
						if ($userID == $userPostOwnerID) {
							if (empty($userPostFile)) {
								include "../themes/$currentTheme/layouts/posts/textPost.php";
							} else {
								include "../themes/$currentTheme/layouts/posts/ImagePost.php";
							}
						} else {
							echo '<div class="i_post_body nonePoint body_' . $userPostID . '" id="' . $userPostID . '" data-last="' . $userPostID . '"></div>';
						}
					}
				} else {
					if (empty($userPostFile)) {
						include "../themes/$currentTheme/layouts/posts/textPost.php";
					} else {
						include "../themes/$currentTheme/layouts/posts/ImagePost.php";
					}
				}
			}
		} else {
			echo '15';
		}
	}

    /*INSERT NEW CAMPAIGN*/
    if ($type == 'newCampaign') {
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
        }
        $communityID = isset($_POST['community_id']) ? (int)$iN->iN_Secure($_POST['community_id']) : 0;
        $communityData = null;
        if ($communityID > 0) {
            if ($logedIn != 1) {
                exit('community_post_forbidden');
            }
            $communityData = $iN->iN_GetCommunityById($communityID);
            if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
                exit('community_not_found');
            }
            if ((string)($communityData['posting_enabled'] ?? '1') !== '1') {
                exit('community_posting_disabled');
            }
            $isAdmin = isset($userType) && (string)$userType === '2';
            if ((int)($communityData['owner_user_id'] ?? 0) !== (int)$userID && !$isAdmin) {
                exit('community_post_forbidden');
            }
        }
        $campaignSettings = $iN->iN_GetCampaignSettings();
        if (($campaignSettings['status'] ?? '0') !== '1') {
            exit('campaign_disabled');
        }
        if ($logedIn != '1' || !$iN->iN_CanUserCreateCampaign($userID)) {
            exit('campaign_permission');
        }
        $title = isset($_POST['title']) ? $iN->iN_Secure($_POST['title']) : '';
        $summary = isset($_POST['summary']) ? $iN->iN_Secure($_POST['summary']) : '';
        $goal = isset($_POST['goal']) ? $iN->iN_Secure($_POST['goal']) : '';
        $minAmount = isset($_POST['min_amount']) ? $iN->iN_Secure($_POST['min_amount']) : '';
        $maxAmount = isset($_POST['max_amount']) ? $iN->iN_Secure($_POST['max_amount']) : '';
        $deadline = isset($_POST['deadline']) ? $iN->iN_Secure($_POST['deadline']) : '';
        $coverUploadId = isset($_POST['cover_upload_id']) ? (int)$iN->iN_Secure($_POST['cover_upload_id']) : 0;

        $campaignData = array(
            'title' => $title,
            'summary' => $summary,
            'goal' => $goal,
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
            'deadline' => $deadline,
            'cover_upload_id' => $coverUploadId
        );
        $campaignResponse = $iN->iN_InsertNewCampaignPost($userID, $campaignData, $autoApprovePostStatus);
        if (!$campaignResponse || isset($campaignResponse['error'])) {
            $errorCode = isset($campaignResponse['error']) ? $campaignResponse['error'] : 'campaign_create_failed';
            exit($errorCode);
        }
        if ($communityID > 0) {
            $postId = (int)($campaignResponse['post_id'] ?? 0);
            if ($postId > 0) {
                $iN->iN_CreateCommunityPost($communityID, $postId, $userID);
            }
        }
        $payload = array(
            'status' => 'ok',
            'post_id' => (int)($campaignResponse['post_id'] ?? 0),
            'campaign_id' => (int)($campaignResponse['campaign_id'] ?? 0),
            'slug' => isset($campaignResponse['url_slug'], $campaignResponse['post_id']) ? $campaignResponse['url_slug'] . '_' . $campaignResponse['post_id'] : '',
            'message' => $LANG['campaign_created'] ?? 'campaign_created'
        );
        echo json_encode($payload);
        exit();
    }

	if ($type == 'poll_vote') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$pollId = isset($_POST['poll_id']) ? (int)$iN->iN_Secure($_POST['poll_id']) : 0;
		$optionId = isset($_POST['option_id']) ? (int)$iN->iN_Secure($_POST['option_id']) : 0;
		if (!$pollId || !$optionId) {
			exit('poll_vote_invalid');
		}
		$voteResponse = $iN->iN_SubmitPollVote($pollId, $optionId, $userID);
		if (!$voteResponse) {
			exit('poll_vote_failed');
		}
		if (isset($voteResponse['error'])) {
			echo 'poll_' . $voteResponse['error'];
			exit();
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['status' => 'success', 'poll' => $voteResponse], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'p_like') {
		if (isset($_POST['post'])) {
			$postID = $iN->iN_Secure($_POST['post']);
			$likePost = $iN->iN_LikePost($userID, $postID);
			$status = 'in_like';
			$pLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePost) {
				$status = 'in_unlike';
				$pLike = $iN->iN_SelectedMenuIcon('18');
				if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewPostLikePointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPostLikePoint($userID,$postID,$ataNewPostLikePointAmount);
				}
			}
			if($status == 'in_like'){
				if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewPostLikePointSatus == 'yes'){
					$iN->iN_RemovePointPostLikeIfExist($userID,$postID,$ataNewPostLikePointAmount);
				}
			}
			$likeSum = $iN->iN_TotalPostLiked($postID);
			if ($likeSum == 0) {
				$likeSum = '';
			} else {
				$likeSum = $likeSum;
			}
			$data = array(
				'status' => $status,
				'like' => $pLike,
				'likeCount' => $likeSum,
			);
			$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($postID);
			$likedPostOwnerID = $GetPostOwnerIDFromPostDetails['post_owner_id'] ?? 0;
			$communityMeta = $iN->iN_GetCommunityPostMeta($postID);
			$communityData = null;
			$communityOwnerId = 0;
			if ($communityMeta) {
				$communityID = (int)($communityMeta['community_id'] ?? 0);
				$communityData = $communityID > 0 ? $iN->iN_GetCommunityById($communityID) : null;
				$communityOwnerId = $communityData ? (int)($communityData['owner_user_id'] ?? 0) : 0;
			}
			$skipDefaultLikeNotification = $communityOwnerId > 0 && (int)$communityOwnerId === (int)$likedPostOwnerID;
			if (!$skipDefaultLikeNotification || $status === 'in_like') {
				$iN->iN_insertPostLikeNotification($userID, $postID);
			}
			if ($status === 'in_unlike' && $communityOwnerId > 0 && $communityData) {
				$iN->iN_InsertCommunityNotification(
					$userID,
					$communityOwnerId,
					$communityData,
					'community_like',
					(int)$postID
				);
			}
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			$likedPostOwnerID = (int)$likedPostOwnerID;
			$uData = $iN->iN_GetUserDetails($likedPostOwnerID);
			$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
			$lUsername = $uData['i_username'];
			$lUserFullName = $uData['i_user_fullname'];
			$emailNotificationStatus = $uData['email_notification_status'];
			$notQualifyDocument = $LANG['not_qualify_document'];
			$slugUrl = $base_url . 'post/' . $GetPostOwnerIDFromPostDetails['url_slug'] . '_' . $postID;
			if (
				(string)$webPushStatus === '1'
				&& (string)$webPushEventPostLike === '1'
				&& $userID != $likedPostOwnerID
				&& $status == 'in_unlike'
			) {
				$actorName = trim((string)$userFullName) !== '' ? (string)$userFullName : (string)$userName;
				$pushTitle = $iN->iN_Secure($LANG['someone_liked_yourpost'] ?? 'Someone liked your post');
				$pushBody = $iN->iN_Secure($actorName . ' ' . ($LANG['liked_your_post'] ?? 'liked your post'));
				$iN->iN_SendWebPushToUser((int)$likedPostOwnerID, [
					'title' => $pushTitle,
					'body' => $pushBody,
					'url' => (string)$slugUrl,
					'tag' => 'post_like_' . (int)$postID,
				]);
			}
			if ($emailSendStatus == '1' && $userID != $likedPostOwnerID && $emailNotificationStatus == '1' && $status == 'in_unlike') {
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$someoneLikedYourPost = $iN->iN_Secure($LANG['someone_liked_yourpost']);
				$clickGoPost = $iN->iN_Secure($LANG['click_go_post']);
				$likedYourPost = $iN->iN_Secure($LANG['liked_your_post']);
				include_once '../includes/mailTemplates/postLikeEmailTemplate.php';
				$body = $bodyPostLikeEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['someone_liked_yourpost']);
				$mail->CharSet = 'utf-8';
				$mail->Body    = $body;
				if (iN_safeMailSend($mail, $smtpOrMail, 'post_like_notification')) {
					$mail->ClearAddresses();
					return true;
				}
			}
		}
	}
	if ($type == 'p_share') {
		if (isset($_POST['sp'])) {
			$postID = $iN->iN_Secure($_POST['sp']);
			$checkPostIDExist = $iN->iN_CheckPostIDExist($postID);
			if ($checkPostIDExist == '1') {
				$postFromData = $iN->iN_GetAllPostDetails($postID);
				$communityMeta = $iN->iN_GetCommunityPostMeta($postID);
				if ($communityMeta) {
					$communityID = (int)($communityMeta['community_id'] ?? 0);
					$communityData = $communityID > 0 ? $iN->iN_GetCommunityById($communityID) : null;
					$moderationEnabled = $communityData && (string)($communityData['moderation_enabled'] ?? '0') === '1';
					$isAdmin = isset($userType) && (string)$userType === '2';
					$isOwner = $communityData && (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
					if ((string)($communityMeta['is_hidden'] ?? '0') === '1') {
						echo '404';
						exit;
					}
					if ($logedIn == 1 && $moderationEnabled && !$isAdmin && !$isOwner) {
						if ($iN->iN_IsCommunityReshareDisabled($communityID, $userID)) {
							echo '404';
							exit;
						}
					}
				}
				$userPostID = $postFromData['post_id'];
				$userPostOwnerID = $postFromData['post_owner_id'];
				$userPostText = isset($postFromData['post_text']) ? $postFromData['post_text'] : NULL;
				$userPostFile = $postFromData['post_file'];
				$userPostCreatedTime = $postFromData['post_created_time'];
				$crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
				$userPostWhoCanSee = $postFromData['who_can_see'];
				$userPostWantStatus = $postFromData['post_want_status'];
				$userPostWantedCredit = $postFromData['post_wanted_credit'];
				$userPostStatus = $postFromData['post_status'];
                $userPostType = isset($postFromData['post_type']) ? $postFromData['post_type'] : 'normal';
				$userPostOwnerUsername = $postFromData['i_username'];
				$userPostOwnerUserFullName = $postFromData['i_user_fullname'];
				if($fullnameorusername == 'no'){
					$userPostOwnerUserFullName = $userPostOwnerUsername;
				}
				$userPostOwnerUserGender = $postFromData['user_gender'];
				$userPostCommentAvailableStatus = $postFromData['comment_status'];
				$userPostOwnerUserLastLogin = $postFromData['last_login_time'];
				$userPostHashTags = isset($postFromData['hashtags']) ? $postFromData['hashtags'] : NULL;
				$userPostSharedID = isset($postFromData['shared_post_id']) ? $postFromData['shared_post_id'] : NULL;
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostUserVerifiedStatus = $postFromData['user_verified_status'];
				if ($userPostOwnerUserGender == 'male') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($userPostOwnerUserGender == 'female') {
					$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($userPostOwnerUserGender == 'couple') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$userVerifiedStatus = '';
				if ($userPostUserVerifiedStatus == '1') {
					$userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$onlySubs = '';
				if($userPostWhoCanSee == '1'){
					$onlySubs = '';
					$subPostTop = '';
					$wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
				 }else if($userPostWhoCanSee == '2'){
					$subPostTop = '';
					$wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
					$onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
				 }else if($userPostWhoCanSee == '3'){
					$subPostTop = 'extensionPost';
					$wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
					$onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div></div></div>';
				 }else if($userPostWhoCanSee == '4'){
				   $subPostTop = 'extensionPost';
				   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
				   $onlySubs = '<div class="onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
				 }
				$likeSum = $iN->iN_TotalPostLiked($userPostID);
				if ($likeSum > '0') {
					$likeSum = $likeSum;
				} else {
					$likeSum = '1';
				}
				$checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
				/*Comment*/
				$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
				$c = '';
				$TotallyPostComment = '';
				if ($c) {
					if ($getUserComments > 0) {
						$CountTheUniqComment = count($CountUniqPostCommentArray);
						$SecondUniqComment = $CountTheUniqComment - 5;
						if ($CountTheUniqComment > 5) {
							$getUserComments = $iN->iN_GetPostComments($userPostID, 5);
						}
					}
				}
				if ($logedIn == 0) {
					$getFriendStatusBetweenTwoUser = '1';
					$checkPostLikedBefore = '';
				} else {
					$getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
					$checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
				}
                $campaignMetaShare = null;
                if (isset($userPostType) && $userPostType === 'campaign') {
                    $campaignMetaShare = $iN->iN_GetCampaignByPostId((int)$userPostID);
                }
				include "../themes/$currentTheme/layouts/posts/sharePost.php";
			} else {
				echo '404';
			}
		}
	}
	/*Insert Re-Share Post*/
	if ($type == 'p_rshare') {
		if (isset($_POST['sp']) && isset($_POST['pt'])) {
			$reSharePostID = $iN->iN_Secure($_POST['sp']);
			$reSharePostNewText = $iN->iN_Secure($_POST['pt']);
			$communityMeta = $iN->iN_GetCommunityPostMeta($reSharePostID);
			if ($communityMeta) {
				$communityID = (int)($communityMeta['community_id'] ?? 0);
				$communityData = $communityID > 0 ? $iN->iN_GetCommunityById($communityID) : null;
				$moderationEnabled = $communityData && (string)($communityData['moderation_enabled'] ?? '0') === '1';
				$isAdmin = isset($userType) && (string)$userType === '2';
				$isOwner = $communityData && (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
				if ((string)($communityMeta['is_hidden'] ?? '0') === '1') {
					echo '404';
					exit;
				}
				if ($moderationEnabled && !$isAdmin && !$isOwner) {
					if ($iN->iN_IsCommunityReshareDisabled($communityID, $userID)) {
						echo '404';
						exit;
					}
				}
			}
			$insertReShare = $iN->iN_ReShare_Post($userID, $reSharePostID, $iN->iN_Secure($reSharePostNewText));
			if ($insertReShare) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*Show PopUps*/
	if ($type == 'ialert') {
		if (isset($_POST['al'])) {
			$alertType = $iN->iN_Secure($_POST['al']);
			include "../themes/$currentTheme/layouts/popup_alerts/popup_alerts.php";
		}
	}
    /*Show campaign donors list*/
    if ($type == 'campaign_donors') {
        $postID = isset($_POST['post']) ? (int) $iN->iN_Secure($_POST['post']) : 0;
        $campaignMeta = $iN->iN_GetCampaignByPostId($postID);
        if (!$campaignMeta) {
            exit('404');
        }
        $donorsData = $iN->iN_GetCampaignDonorList($postID, 200, 0);
        $campaignOwnerID = isset($campaignMeta['owner_uid_fk']) ? (int)$campaignMeta['owner_uid_fk'] : 0;
        $currencySign = isset($currencys[$defaultCurrency]) ? $currencys[$defaultCurrency] : '';
        include "../themes/$currentTheme/layouts/posts/campaignDonors.php";
    }
	/*Show Who Can See Settings In PopUp*/
	if ($type == 'wcs') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$whoSee = $iN->iN_GetAllPostDetails($postID);
			if ($whoSee) {
				$whoCSee = $whoSee['who_can_see'];
				include "../themes/$currentTheme/layouts/posts/whoCanSee.php";
			}
		}
	}
	/*Show Who Can See Settings In PopUp*/
	if ($type == 'whcStory') {
		$checkUserIDExist = $iN->iN_CheckUserExist($userID);
		if ($checkUserIDExist) {
		    include "../themes/$currentTheme/layouts/popup_alerts/chooseWhichStory.php";
		}
	}
	if ($type === 'story_public_by_user') {
		header('Content-Type: application/json; charset=utf-8');
		include_once "../includes/csrf.php";
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error']);
			exit();
		}
		$ownerId = isset($_POST['user_id']) ? (int)$iN->iN_Secure($_POST['user_id']) : 0;
		if ($ownerId <= 0 || $iN->iN_CheckUserExist($ownerId) != 1) {
			echo json_encode(['status' => 'error']);
			exit();
		}
		if (!$iN->iN_UserHasPublicStory($ownerId)) {
			echo json_encode(['status' => 'empty']);
			exit();
		}
		$storyItems = $iN->iN_GetPublicStoryItemsByUser($ownerId);
		if (empty($storyItems)) {
			echo json_encode(['status' => 'empty']);
			exit();
		}
		$storyOwnerUserName = $iN->iN_GetUserName($ownerId);
		$storieOwnerFullName = $iN->iN_UserFullName($ownerId);
		$storyOwnerAvatar = $iN->iN_UserAvatar($ownerId, $base_url);
		$displayName = $storieOwnerFullName;
		$lastStoryId = 0;
		$lastStoryUrl = '';
		foreach ($storyItems as $itemData) {
			$itemId = (int)($itemData['id'] ?? 0);
			if ($itemId > $lastStoryId) {
				$lastStoryId = $itemId;
				$lastStoryUrl = $itemData['thumbnail'] ?: $itemData['src'];
			}
		}
		if ($lastStoryUrl === '') {
			$lastStoryUrl = $storyOwnerAvatar;
		}
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($displayName, 'UTF-8') > 8) {
				$displayName = mb_substr($displayName, 0, 8, 'UTF-8') . '...';
			}
		} else {
			if (strlen($displayName) > 8) {
				$displayName = substr($displayName, 0, 8) . '...';
			}
		}
		ob_start();
		?>
		<div class="story-view-item swiper-slide" data-story-id="<?php echo iN_HelpSecure($ownerId); ?>" data-last-story-id="<?php echo iN_HelpSecure($lastStoryId); ?>" data-background-image="<?php echo iN_HelpSecure($lastStoryUrl); ?>" data-profile-image="<?php echo iN_HelpSecure($storyOwnerAvatar); ?>" data-profile-name="<?php echo iN_HelpSecure($storieOwnerFullName); ?>" data-profile-username="<?php echo iN_HelpSecure($storyOwnerUserName); ?>" style="--story-bg:url('<?php echo iN_HelpSecure($lastStoryUrl); ?>');">
			<div class="story-bubble">
				<div class="story-ring">
					<div class="story-view-pr-avatar" data-avatar="<?php echo iN_HelpSecure($storyOwnerAvatar); ?>"></div>
				</div>
			</div>
			<span class="name truncate"><?php echo iN_HelpSecure($displayName); ?></span>
			<ul class="media">
				<?php
				foreach ($storyItems as $itemData) {
					$storieID = $itemData['id'];
					$storieText = $itemData['text'] ?? '';
					$storieTextStyle = $itemData['text_style'] ?? 'not';
					$exts = $itemData['uploaded_file_ext'] ?? '';
					$final_Image = $itemData['src'] ?? '';
					$StoryCreatedTime = $itemData['created'] ?? 0;
					$privacy = $itemData['privacy'] ?? 'everyone';
					$canView = $itemData['can_view'] ?? '1';
					$accessReason = $itemData['access_reason'] ?? '';
					$overlayLink = $itemData['overlay_link'] ?? '';
					$overlayMention = $itemData['overlay_mention'] ?? '';
					$overlaySticker = $itemData['overlay_sticker'] ?? '';
					$audioUrl = $itemData['audio_url'] ?? '';
					$audioTitle = $itemData['audio_title'] ?? '';
					$audioArtist = $itemData['audio_artist'] ?? '';
					$quickReplies = $itemData['quick_replies'] ?? [];
					$quickRepliesJson = '';
					if (!empty($quickReplies)) {
						$quickRepliesJson = htmlspecialchars(json_encode($quickReplies, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
					}
					$overlayLinkAttr = iN_HelpSecure($overlayLink);
					$overlayMentionAttr = iN_HelpSecure($overlayMention);
					$overlayStickerAttr = iN_HelpSecure($overlaySticker);
					$overlayAudioAttr = iN_HelpSecure($audioUrl, FILTER_VALIDATE_URL);
					$audioTitleAttr = iN_HelpSecure($audioTitle);
					$audioArtistAttr = iN_HelpSecure($audioArtist);
					$audioDataAttrs = $overlayAudioAttr !== ''
						? ' data-overlay-audio="' . $overlayAudioAttr . '" data-audio-title="' . $audioTitleAttr . '" data-audio-artist="' . $audioArtistAttr . '"'
						: '';
					if (in_array($exts, ['mp4', 'MP4'])) {
						echo '<li class="move_' . $storieID . '" data-id="' . $storieID . '" data-sid="' . $storieID . '" data-duration="" data-time="' . $StoryCreatedTime . '">
								<video src="' . $final_Image . '" id="video_' . $storieID . '" alt="' . $storieText . '" data-id="' . $storieID . '" data-privacy="' . iN_HelpSecure($privacy) . '" data-can-view="' . iN_HelpSecure($canView) . '" data-access-reason="' . iN_HelpSecure($accessReason) . '" data-overlay-link="' . $overlayLinkAttr . '" data-overlay-mention="' . $overlayMentionAttr . '" data-overlay-sticker="' . $overlayStickerAttr . '" data-quick-replies="' . $quickRepliesJson . '" type="video/mp4"></video>
							</li>';
					} else {
						echo '<li data-duration="7" data-id="' . $storieID . '" data-sid="' . $storieID . '" data-time="' . $StoryCreatedTime . '">
								<img src="' . $final_Image . '" data-id="' . $storieID . '" data-ts="' . $storieTextStyle . '" data-privacy="' . iN_HelpSecure($privacy) . '" data-can-view="' . iN_HelpSecure($canView) . '" data-access-reason="' . iN_HelpSecure($accessReason) . '" data-overlay-link="' . $overlayLinkAttr . '" data-overlay-mention="' . $overlayMentionAttr . '" data-overlay-sticker="' . $overlayStickerAttr . '"' . $audioDataAttrs . ' data-quick-replies="' . $quickRepliesJson . '" alt="' . $storieText . '">
							</li>';
					}
				}
				?>
			</ul>
		</div>
		<?php
		$html = ob_get_clean();
		echo json_encode(['status' => 'ok', 'html' => $html], JSON_UNESCAPED_UNICODE);
		exit();
	}
	/*Update Post Who Can See Status*/
	if ($type == 'uwcs') {
		if (isset($_POST['wci']) && in_array($_POST['wci'], $whoCanSeeArrays) && isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$WhoCS = $iN->iN_Secure($_POST['wci']);
			$updatePostWhoCanSeeStatus = $iN->iN_UpdatePostWhoCanSee($userID, $postID, $WhoCS);
			if ($updatePostWhoCanSeeStatus) {
				if ($WhoCS == 1) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('50');
				} else if ($WhoCS == 2) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('15');
				} else if ($WhoCS == 3) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('51');
				}
				echo html_entity_decode($UpdatedWhoCanSee);
			} else {
				echo '404';
			}
		}
	}
	/*Show Edit Post In PopUp*/
	if ($type == 'c_editPost') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$getPData = $iN->iN_GetAllPostDetails($postID);
			if ($getPData) {
				$posText = isset($getPData['post_text']) ? $getPData['post_text'] : null;
				$postType = isset($getPData['post_type']) ? $getPData['post_type'] : 'normal';
				if (!function_exists('csrf_get_token')) {
					include_once "../includes/csrf.php";
				}
				$pollDetails = null;
				if ($postType === 'poll') {
					$pollDetails = $iN->iN_GetPollDetailsByPostId($postID, $userID);
				}
				include "../themes/$currentTheme/layouts/posts/editPost.php";
			} else {
				echo '404';
			}
		}
	}
	/*Save Edited Post*/
	if ($type == 'editS') {
		if (isset($_POST['id']) && isset($_POST['text'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$editedText = $iN->iN_Secure($_POST['text']);
			$editedTextTwo = $iN->iN_Secure($_POST['text']);
			$isPollEdit = isset($_POST['is_poll']) && $_POST['is_poll'] === '1';
			if (empty($editedText)) {
				$status = 'no';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$editSlug = $iN->url_slugies($editedText);
			$hashT = $iN->iN_hashtag($editedText);
			$saveEditedPost = false;
			$pollPayload = null;
			if ($isPollEdit) {
				include_once "../includes/csrf.php";
				if (!csrf_validate_from_request()) {
					$data = array(
						'status' => 'poll_invalid_csrf',
						'text' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				$pollOptions = array();
				if (isset($_POST['poll_options']) && is_array($_POST['poll_options'])) {
					foreach ($_POST['poll_options'] as $option) {
						if (is_array($option)) {
							$pollOptions[] = array(
								'id' => isset($option['id']) ? (int)$iN->iN_Secure($option['id']) : null,
								'text' => isset($option['text']) ? $iN->iN_Secure($option['text']) : '',
							);
						} else {
							$pollOptions[] = $iN->iN_Secure($option);
						}
					}
				}
				$updatePoll = $iN->iN_UpdatePollPost($userID, $postID, $editedTextTwo, $iN->url_Hash($editedText), $editSlug, $pollOptions);
				if (!$updatePoll || isset($updatePoll['error'])) {
					$status = isset($updatePoll['error']) ? $updatePoll['error'] : 'poll_update_failed';
					$data = array(
						'status' => $status,
						'text' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				$pollPayload = isset($updatePoll['poll']) ? $updatePoll['poll'] : null;
				$saveEditedPost = true;
			} else {
				$saveEditedPost = $iN->iN_UpdatePost($userID, $postID, $editedTextTwo, $iN->url_Hash($editedText), $editSlug);
			}
			if ($saveEditedPost) {
				$postFileRow = DB::one("SELECT post_file FROM i_posts WHERE post_id = ? LIMIT 1", [(int)$postID]);
				$postFileValue = $postFileRow['post_file'] ?? '';
				$hasMedia = trim((string)$postFileValue) !== '';
				if ($hasMedia) {
					$iN->iN_ClearPostLinkPreview((int)$postID);
				} else {
					$linkPreview = $iN->iN_GetLinkPreviewFromText($editedText);
					if ($linkPreview) {
						$iN->iN_SavePostLinkPreview((int)$postID, $linkPreview);
					} else {
						$iN->iN_ClearPostLinkPreview((int)$postID);
					}
				}
				$getNewPostFromData = $iN->iN_GetAllPostDetails($postID);
				$status = '200';
				$updatedText = $iN->sanitize_output_preserve_linebreaks($getNewPostFromData['post_text'], $base_url);
				$updatedText = nl2br($updatedText, false);
				$data = array(
					'status' => $status,
					'text' => $updatedText,
				);
				if ($pollPayload) {
					$data['poll'] = $pollPayload;
				}
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$status = '404';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		}
	}
	/*Delete Post Call AlertBox*/
	if ($type == 'ddelPost') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteAlert.php";
		}
	}
	/*Delete Post Call AlertBox*/
	if ($type == 'finishLiveStreaming') {
		include "../themes/$currentTheme/layouts/popup_alerts/closeLiveStreaming.php";
	}
	/*Delete Conversation Call AlertBox*/
	if ($type == 'ddelConv') {
		if (isset($_POST['id'])) {
			$conversationID = $iN->iN_Secure($_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteConversationAlert.php";
		}
	}
	/*Delete Message Call AlertBox*/
	if ($type == 'ddelMesage') {
		if (isset($_POST['id'])) {
			$messageID = $iN->iN_Secure($_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteMessageAlert.php";
		}
	}
	/*Delete Story From Database*/
	if($type == 'deleteStorie'){
       if(isset($_POST['id'])){
          $storieID = $iN->iN_Secure($_POST['id']);
		  $checkStorieIDExist = $iN->iN_CheckStorieIDExist($userID, $storieID);
		  if($checkStorieIDExist){
              $sData = $iN->iN_GetUploadedStoriesData($userID, $storieID);
			  $uploadedFileID = $sData['s_id'];
			  $uploadedFilePath = $sData['uploaded_file_path'];
			  $uploadedTumbnailFilePath = $sData['upload_tumbnail_file_path'];
			  $uploadedFilePathX = $sData['uploaded_x_file_path'];
			  $uploadedStoryType = $sData['story_type'];
              if($uploadedStoryType != 'textStory'){
                if ($uploadedFileID) {
                    if (storage_is_remote()) {
                        @storage_delete($uploadedFilePath);
                        @storage_delete($uploadedFilePathX);
                        @storage_delete($uploadedTumbnailFilePath);
                    } else {
                        @unlink('../' . $uploadedFilePath);
                        @unlink('../' . $uploadedFilePathX);
                        @unlink('../' . $uploadedTumbnailFilePath);
                    }
                    $affected = DB::exec("DELETE FROM i_user_stories WHERE s_id = ? AND uid_fk = ?", [(int)$uploadedFileID, (int)$userID]);
                    echo $affected ? '200' : '404';
                } else {
                    $affected = DB::exec("DELETE FROM i_user_stories WHERE s_id = ? AND uid_fk = ?", [(int)$uploadedFileID, (int)$userID]);
                    echo $affected ? '200' : '404';
                }
              }else{
                $affected = DB::exec("DELETE FROM i_user_stories WHERE s_id = ? AND uid_fk = ?", [(int)$uploadedFileID, (int)$userID]);
                echo $affected ? '200' : '404';
              }

		  }
	   }
	}
    if ($type === 'deleteReelUpload') {
        if (isset($_POST['id']) && $_POST['id'] !== '') {
            $reelID = $iN->iN_Secure($_POST['id']);
            $reelData = $iN->iN_GetReelsVideoDetailsByID($userID, $reelID);
            if ($reelData) {
                $paths = [];
                if (!empty($reelData['uploaded_file_path'])) { $paths[] = $reelData['uploaded_file_path']; }
                if (!empty($reelData['upload_tumbnail_file_path'])) { $paths[] = $reelData['upload_tumbnail_file_path']; }
                if (!empty($reelData['uploaded_x_file_path'])) { $paths[] = $reelData['uploaded_x_file_path']; }

                $useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
                foreach ($paths as $path) {
                    if (!$path) { continue; }
                    if ($useRemote && function_exists('storage_delete')) {
                        @storage_delete($path);
                    } else {
                        @unlink('../' . ltrim($path, '/'));
                    }
                }
                DB::exec("DELETE FROM i_user_uploads WHERE upload_id = ? AND iuid_fk = ?", [(int)$reelID, (int)$userID]);
                echo '200';
            } else {
                echo '404';
            }
        }
        exit;
    }
	/*Delete Post From Database*/
		if ($type == 'deletePost') {
			if (isset($_POST['id'])) {
				$postID = $iN->iN_Secure($_POST['id']);
	            if(!empty($postID)){
	                $getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
	                $communityMeta = $iN->iN_GetCommunityPostMeta($postID);
                if ($communityMeta) {
                    $communityID = (int)($communityMeta['community_id'] ?? 0);
                    $communityData = $communityID > 0 ? $iN->iN_GetCommunityById($communityID) : null;
                    $isAdmin = isset($userType) && (string)$userType === '2';
                    $isOwner = $communityData && (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
                    $moderationEnabled = $communityData && (string)($communityData['moderation_enabled'] ?? '0') === '1';
                    if ($communityData && !$isOwner && !$isAdmin && $moderationEnabled) {
                        $moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
                        if ($moderatorData && (string)($moderatorData['can_manage_posts'] ?? '0') === '1') {
                            $previousHidden = (string)($communityMeta['is_hidden'] ?? '0');
                            if ($previousHidden !== '1') {
                                $iN->iN_HideCommunityPost($communityID, $postID, $userID);
                                $postOwnerId = (int)($getPostFileIDs['post_owner_id'] ?? 0);
                                $iN->iN_LogCommunityModeratorAction(
                                    $communityID,
                                    $userID,
                                    'post_hide',
                                    ['user_id' => $postOwnerId, 'post_id' => (int)$postID],
                                    [
                                        'previous' => [
                                            'is_hidden' => $previousHidden,
                                            'hidden_by' => $communityMeta['hidden_by'] ?? null,
                                            'hidden_at' => $communityMeta['hidden_at'] ?? null
                                        ],
                                        'next' => ['is_hidden' => '1']
                                    ]
                                );
                            }
                            echo '200';
                            exit;
	                        }
	                    }
	                }
	                if ((string)$userType === '3' && (int)($getPostFileIDs['post_owner_id'] ?? 0) !== (int)$userID) {
	                    echo '404';
	                    exit;
	                }
	                $postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
	                $trimValue = rtrim((string)$postFileIDs, ',');
	                $explodeFiles = $trimValue !== '' ? explode(',', $trimValue) : [];
                $explodeFiles = array_unique($explodeFiles);
                foreach ($explodeFiles as $explodeFile) {
                    $theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
                    if($theFileID){
                        $uploadedFileID = $theFileID['upload_id'];
                        $uploadedFilePath = $theFileID['uploaded_file_path'];
                        $uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
                        $uploadedFilePathX = $theFileID['uploaded_x_file_path'];
                        if (storage_is_remote()) {
                            @storage_delete($uploadedFilePath);
                            @storage_delete($uploadedFilePathX);
                            @storage_delete($uploadedTumbnailFilePath);
                        } else {
                            @unlink('../' . $uploadedFilePath);
                            @unlink('../' . $uploadedFilePathX);
                            @unlink('../' . $uploadedTumbnailFilePath);
                        }
                        DB::exec("DELETE FROM i_user_uploads WHERE upload_id = ? AND iuid_fk = ?", [(int)$uploadedFileID, (int)$userID]);
                    }
                }
                $deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
                $deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
                if($deleteStoragePost){
                    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
                    echo '200';
                    exit;
                }else{
                    echo '404';
                    exit;
                }
            }else if(!empty($postID)){
                $deletePostFromData = $iN->iN_DeletePost($userID, $postID);
                $deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
                if ($deletePostFromData) {
                    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
                    echo '200';
                    exit;
                } else {
                    echo '404';
                    exit;
                }
            }
        }
	}
	/*Share My Storie*/
	if($type == 'shareMyStorie'){
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		if(isset($_POST['id'])){
			$storieID = (int)$iN->iN_Secure($_POST['id']);
			$storieText = isset($_POST['txt']) ? $iN->iN_Secure($_POST['txt']) : '';
			$privacy = isset($_POST['privacy']) ? $iN->iN_Secure($_POST['privacy']) : 'followers';
			$overlayLink = isset($_POST['overlay_link']) ? (string)$_POST['overlay_link'] : '';
			$overlayMention = isset($_POST['overlay_mention']) ? (string)$_POST['overlay_mention'] : '';
			$overlaySticker = isset($_POST['overlay_sticker']) ? (string)$_POST['overlay_sticker'] : '';
			$overlayAudio = isset($_POST['overlay_audio']) ? (int)$iN->iN_Secure($_POST['overlay_audio']) : 0;
			$quickRepliesRaw = $_POST['quick_replies'] ?? '';
			$quickReplies = $iN->iN_NormalizeStoryQuickReplies($quickRepliesRaw);
			if ($iN->iN_CheckUserIsCreator($userID) != 1) {
				$quickReplies = [];
			}
			if($iN->iN_CheckStorieIDExist($userID, $storieID) == 1){
				$storyData = $iN->iN_GetUploadedStoriesData($userID, $storieID);
				$storyExt = strtolower((string)($storyData['uploaded_file_ext'] ?? ''));
				if ($storyExt === 'mp4') {
					$overlayAudio = 0;
				}
				$insertStorie = $iN->iN_InsertMyStorie(
					$userID,
					$storieID,
					$storieText,
					$privacy,
					$overlayLink,
					$overlayMention,
					$overlaySticker,
					$overlayAudio,
					$quickReplies
				);
				if($insertStorie){
					echo '200';
				}else{
					echo '404';
				}
			}
		}
	}
    /*Story Highlight Manager Modal*/
    if ($type == 'highlight_manage') {
        if ($logedIn == 0) {
            exit('403');
        }
        $highlightId = isset($_POST['id']) ? (int)$iN->iN_Secure($_POST['id']) : 0;
        $highlightData = null;
        $selectedStoryIds = [];
        if ($highlightId > 0) {
            $highlightData = $iN->iN_GetStoryHighlight($userID, $highlightId);
            if ($highlightData) {
                $selectedStoryIds = $iN->iN_GetHighlightStoryIds($highlightId);
            }
        }
        $storyArchive = $iN->iN_GetUserStoryArchive($userID, 200);
        include "../themes/$currentTheme/layouts/popup_alerts/highlightManager.php";
        exit;
    }

    /*Create Story Highlight*/
    if ($type == 'highlight_create') {
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if ($logedIn == 0) {
            echo json_encode(['status' => 'error', 'message' => '403']);
            exit;
        }
        $title = isset($_POST['title']) ? $iN->iN_Secure($_POST['title']) : '';
        $coverStoryId = isset($_POST['cover_story_id']) ? (int)$iN->iN_Secure($_POST['cover_story_id']) : 0;
        $storyIds = [];
        if (isset($_POST['stories'])) {
            if (is_array($_POST['stories'])) {
                $storyIds = $_POST['stories'];
            } else {
                $raw = trim((string)$_POST['stories']);
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $storyIds = $decoded;
                    } else {
                        $storyIds = explode(',', $raw);
                    }
                }
            }
        }
        $coverImagePath = null;
        if (isset($_FILES['highlight_cover']['name']) && $_FILES['highlight_cover']['name'] !== '') {
            $coverName = $_FILES['highlight_cover']['name'];
            $coverTmp = $_FILES['highlight_cover']['tmp_name'];
            $coverSize = isset($_FILES['highlight_cover']['size']) ? (int)$_FILES['highlight_cover']['size'] : 0;
            $ext = strtolower(getExtension($coverName));
            $allowedCoverExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedCoverExt, true)) {
                echo json_encode(['status' => 'error', 'message' => $LANG['invalid_file_format'] ?? 'Invalid file format.']);
                exit;
            }
            if (function_exists('convert_to_mb') && convert_to_mb($coverSize) > $availableUploadFileSize) {
                echo json_encode(['status' => 'error', 'message' => $LANG['file_is_too_large'] ?? 'File too large.']);
                exit;
            }
            $uploadHighlights = rtrim($uploadRoot, '/') . '/highlights/';
            $todayDir = date('Y-m-d');
            $uploadDir = $uploadHighlights . $todayDir . '/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $microtime = microtime();
            $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
            $fileBase = 'highlight_cover_' . $removeMicrotime . '_' . $userID;
            $filename = $fileBase . '.' . $ext;
            $uploadedPath = $uploadDir . $filename;
            if (!move_uploaded_file($coverTmp, $uploadedPath)) {
                echo json_encode(['status' => 'error', 'message' => $LANG['upload_failed'] ?? 'Upload failed.']);
                exit;
            }
            $coverImagePath = 'uploads/highlights/' . $todayDir . '/' . $filename;
            if (function_exists('storage_publish_and_url')) {
                storage_publish_and_url($coverImagePath, [$coverImagePath], true);
            }
        }
        $highlightId = $iN->iN_CreateStoryHighlight($userID, $title, $storyIds, $coverStoryId, $coverImagePath);
        if ($highlightId) {
            echo json_encode(['status' => 'success', 'highlight_id' => (int)$highlightId]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $LANG['highlight_save_failed'] ?? 'Unable to save highlight.']);
        }
        exit;
    }

    /*Update Story Highlight*/
    if ($type == 'highlight_update') {
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if ($logedIn == 0) {
            echo json_encode(['status' => 'error', 'message' => '403']);
            exit;
        }
        $highlightId = isset($_POST['highlight_id']) ? (int)$iN->iN_Secure($_POST['highlight_id']) : 0;
        $highlightData = $iN->iN_GetStoryHighlight($userID, $highlightId);
        if (!$highlightData) {
            echo json_encode(['status' => 'error', 'message' => $LANG['highlight_save_failed'] ?? 'Unable to save highlight.']);
            exit;
        }
        $title = isset($_POST['title']) ? $iN->iN_Secure($_POST['title']) : '';
        $coverStoryId = isset($_POST['cover_story_id']) ? (int)$iN->iN_Secure($_POST['cover_story_id']) : 0;
        $storyIds = [];
        if (isset($_POST['stories'])) {
            if (is_array($_POST['stories'])) {
                $storyIds = $_POST['stories'];
            } else {
                $raw = trim((string)$_POST['stories']);
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $storyIds = $decoded;
                    } else {
                        $storyIds = explode(',', $raw);
                    }
                }
            }
        }
        $coverImagePath = null;
        $oldCoverPath = $highlightData['cover_image'] ?? '';
        if (isset($_FILES['highlight_cover']['name']) && $_FILES['highlight_cover']['name'] !== '') {
            $coverName = $_FILES['highlight_cover']['name'];
            $coverTmp = $_FILES['highlight_cover']['tmp_name'];
            $coverSize = isset($_FILES['highlight_cover']['size']) ? (int)$_FILES['highlight_cover']['size'] : 0;
            $ext = strtolower(getExtension($coverName));
            $allowedCoverExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedCoverExt, true)) {
                echo json_encode(['status' => 'error', 'message' => $LANG['invalid_file_format'] ?? 'Invalid file format.']);
                exit;
            }
            if (function_exists('convert_to_mb') && convert_to_mb($coverSize) > $availableUploadFileSize) {
                echo json_encode(['status' => 'error', 'message' => $LANG['file_is_too_large'] ?? 'File too large.']);
                exit;
            }
            $uploadHighlights = rtrim($uploadRoot, '/') . '/highlights/';
            $todayDir = date('Y-m-d');
            $uploadDir = $uploadHighlights . $todayDir . '/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $microtime = microtime();
            $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
            $fileBase = 'highlight_cover_' . $removeMicrotime . '_' . $userID;
            $filename = $fileBase . '.' . $ext;
            $uploadedPath = $uploadDir . $filename;
            if (!move_uploaded_file($coverTmp, $uploadedPath)) {
                echo json_encode(['status' => 'error', 'message' => $LANG['upload_failed'] ?? 'Upload failed.']);
                exit;
            }
            $coverImagePath = 'uploads/highlights/' . $todayDir . '/' . $filename;
            if (function_exists('storage_publish_and_url')) {
                storage_publish_and_url($coverImagePath, [$coverImagePath], true);
            }
        }
        $updated = $iN->iN_UpdateStoryHighlight($userID, $highlightId, $title, $storyIds, $coverStoryId, $coverImagePath);
        if ($updated) {
            $oldCoverPath = trim((string)$oldCoverPath);
            if ($coverImagePath !== null && $oldCoverPath !== '' && $oldCoverPath !== $coverImagePath && strpos($oldCoverPath, 'uploads/') === 0) {
                $useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
                if ($useRemote && function_exists('storage_delete')) {
                    @storage_delete($oldCoverPath);
                } else {
                    $full = dirname(__DIR__) . '/' . ltrim($oldCoverPath, '/');
                    @unlink($full);
                }
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $LANG['highlight_save_failed'] ?? 'Unable to save highlight.']);
        }
        exit;
    }

    /*Delete Story Highlight*/
    if ($type == 'highlight_delete') {
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if ($logedIn == 0) {
            echo json_encode(['status' => 'error', 'message' => '403']);
            exit;
        }
        $highlightId = isset($_POST['highlight_id']) ? (int)$iN->iN_Secure($_POST['highlight_id']) : 0;
        $deleted = $iN->iN_DeleteStoryHighlight($userID, $highlightId);
        if ($deleted) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $LANG['highlight_delete_failed'] ?? 'Unable to delete highlight.']);
        }
        exit;
    }
	/*Show More Posts*/
	if ($type == 'moreposts') {
		if (isset($_POST['last'])) {
			$page = $type;
            $randomBoxes = array('suggestedusers', 'ads');
            shuffle($randomBoxes);
            foreach ($randomBoxes as $randomBox) {
                $boxPath = "../themes/$currentTheme/layouts/random_boxs/$randomBox.php";
                if (is_file($boxPath)) {
                    ob_start();
                    include $boxPath;
                    $boxHtml = trim(ob_get_clean());
                    if ($boxHtml !== '') {
                        echo $boxHtml;
                        break;
                    }
                }
            }
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Community Posts*/
	if ($type == 'community') {
		if (isset($_POST['last'], $_POST['community_id'])) {
			$communityID = (int)$iN->iN_Secure($_POST['community_id']);
			$communityData = $iN->iN_GetCommunityById($communityID);
			if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
				exit();
			}
			$accessPolicy = (string)($communityData['access_policy'] ?? 'members_only');
			if (!in_array($accessPolicy, ['members_only', 'public'], true)) {
				$accessPolicy = 'members_only';
			}
			$isAdmin = isset($userType) && (string)$userType === '2';
			$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
			$moderationEnabled = (string)($communityData['moderation_enabled'] ?? '0') === '1';
			$isMember = $logedIn == '1' ? $iN->iN_IsCommunityAccessMember($communityData, $userID) : false;
			$isModerator = false;
			$isModerationListed = false;
			if ($logedIn == '1' && !$isOwner && !$isAdmin) {
				$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
				$isModerator = !empty($moderatorData);
				$isModerationListed = $iN->iN_IsCommunityModerationListed($communityID, $userID);
			}
			$canAccessCommunity = $logedIn == '1' ? $iN->iN_CanAccessCommunity($communityData, $userID, $isAdmin, $isModerator) : false;
			$canViewCommunity = $accessPolicy === 'public' ? true : ($canAccessCommunity || $isModerationListed);
			if ($accessPolicy !== 'public' && ($logedIn != '1' || (!$canAccessCommunity && !$isModerationListed))) {
				exit();
			}
			if ($moderationEnabled && !$isAdmin && (int)($communityData['owner_user_id'] ?? 0) !== (int)$userID) {
				$viewRestriction = $iN->iN_GetCommunityViewRestriction($communityID, $userID);
				if (!empty($viewRestriction['restricted'])) {
					exit();
				}
			}
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Saved Posts*/
	if ($type == 'savedpost') {
		if (isset($_POST['last'])) {
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
		/*Show More Profile Posts*/
		if ($type == 'profile') {
			if (isset($_POST['last']) && isset($_POST['p'])) {
				$p_profileID = $iN->iN_Secure($_POST['p']);
				$pCat = '';
				if (isset($_POST['pcat'])) {
					$pCat = $iN->iN_Secure($_POST['pcat']);
				}
				$allowedPCats = array('photos', 'videos', 'audios', 'products', 'followers', 'following', 'subscribers', 'reels', 'polls');
				if ($pCat === 'active_page_menu' || !in_array($pCat, $allowedPCats, true)) {
					$pCat = '';
				}
	            $productFilter = 'all';
	            if (isset($_POST['pf'])) {
	                $pfParam = $iN->iN_Secure($_POST['pf']);
	                $allowed = array('all','bookazoom','digitaldownload','liveeventticket','artcommission','joininstagramclosefriends');
	                if (in_array($pfParam, $allowed, true)) {
	                    $productFilter = $pfParam;
	                }
	            }
				$page = $type;
				include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
				exit();
			}
		}
	/*Show More Profile Posts*/
	if ($type == 'hashtag') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$pageFor = $iN->iN_Secure($_POST['p']);
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Update Post Comment Status*/
	if ($type == 'updateComentStatus') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$updatePostCommentStatus = $iN->iN_UpdatePostCommentStatus($userID, $postID);
			if ($updatePostCommentStatus == '1') {
				$status = '200';
				$text = $iN->iN_SelectedMenuIcon('31') . $LANG['disable_comment'];
			} else {
				$status = '404';
				$text = $iN->iN_SelectedMenuIcon('31') . $LANG['enable_comments'];
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Update Post Comment Status*/
	if ($type == 'pinpost') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$updatePostPinedStatus = $iN->iN_UpdatePostPinedStatus($userID, $postID);
			if ($updatePostPinedStatus == '1') {
				$status = '200';
				$text = '<div class="i_pined_post" id="i_pined_post_' . $postID . '">' . $iN->iN_SelectedMenuIcon('62') . '</div>';
				$btnText = $iN->iN_SelectedMenuIcon('29') . $LANG['post_pined_on_your_profile'];
			} else {
				$status = '404';
				$text = '';
				$btnText = $iN->iN_SelectedMenuIcon('29') . $LANG['pin_on_my_profile'];
			}
			$data = array(
				'status' => $status,
				'text' => $text,
				'btn' => $btnText,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Report Post*/
	if ($type == 'reportPost') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$insertPostReport = $iN->iN_InsertReportedPost($userID, $postID);
			if ($insertPostReport) {
				if ($insertPostReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_this_post'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Save Post From Saved List*/
	if ($type == 'savePost') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$insertPostSave = $iN->iN_SavePostInSavedList($userID, $postID);
			if ($insertPostSave) {
				if ($insertPostSave == 'svp') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('63');
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('22');
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Insert a New Comment*/
	if ($type == 'comment') {
		if (isset($_POST['id']) && isset($_POST['val'])) {
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$postID = $iN->iN_Secure($_POST['id']);
			$value = $iN->iN_Secure($_POST['val']);
			$sticker = $iN->iN_Secure($_POST['sticker']);
			$Gif = $iN->iN_Secure($_POST['gf']);
			$replyTo = isset($_POST['reply_to']) ? (int)$iN->iN_Secure($_POST['reply_to']) : 0;
			$replyTargetUserName = '';
			$replyTargetUserFullName = '';
			if ($replyTo > 0) {
				$replyTargetComment = $iN->iN_GetCommentDetails($replyTo);
				if ($replyTargetComment) {
					$replyTargetUser = $iN->iN_GetUserDetails((int)$replyTargetComment['comment_uid_fk']);
					if ($replyTargetUser) {
						$replyTargetUserName = $replyTargetUser['i_username'] ?? '';
						$replyTargetUserFullName = $replyTargetUser['i_user_fullname'] ?? '';
						if ($fullnameorusername == 'no') {
							$replyTargetUserFullName = $replyTargetUserName;
						} elseif ($replyTargetUserFullName === '') {
							$replyTargetUserFullName = $replyTargetUserName;
						}
					}
				}
			}
			$communityMeta = $iN->iN_GetCommunityPostMeta($postID);
			if ($communityMeta) {
				$communityID = (int)($communityMeta['community_id'] ?? 0);
				$communityData = $iN->iN_GetCommunityById($communityID);
				if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
					exit('404');
				}
				$isAdmin = isset($userType) && (string)$userType === '2';
				$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
				$isModerator = false;
				if (!$isOwner && !$isAdmin) {
					$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
					$isModerator = !empty($moderatorData);
				}
				$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
				if ($restrictionStatus === 'blocked' || $restrictionStatus === 'restricted') {
					exit('404');
				}
				$commentPolicy = (string)($communityData['comment_policy'] ?? 'members');
				if (!in_array($commentPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
					$commentPolicy = 'members';
				}
				$canComment = $isOwner || $isAdmin;
				if (!$canComment) {
					if ($commentPolicy === 'owner_admin') {
						$canComment = false;
					} elseif ($commentPolicy === 'owner_admin_moderators') {
						$canComment = $isModerator;
					} else {
						$canComment = $isModerator || $iN->iN_IsCommunityAccessMember($communityData, $userID);
					}
				}
				if (!$canComment) {
					exit('comment_restricted');
				}
				if (!$isOwner && !$isAdmin && $iN->iN_IsCommunityCommentDisabled($communityID, $userID)) {
					exit('comment_restricted');
				}
				$isModerationListed = $iN->iN_IsCommunityModerationListed($communityID, $userID);
				$canAccessCommunity = $iN->iN_CanAccessCommunity($communityData, $userID, $isAdmin, $isModerator);
				if (!$canAccessCommunity && !$isModerationListed) {
					exit('404');
				}
			}
			if (empty($value) && empty($sticker) && empty($Gif)) {
				$status = '404';
			} else {
				$insertNewComment = $iN->iN_insertNewComment(
					$userID,
					$postID,
					$iN->iN_Secure($value),
					$iN->iN_Secure($sticker),
					$iN->iN_Secure($Gif),
					$replyTo
				);
				if ($insertNewComment) {
					$commentID = $insertNewComment['com_id'];
					$commentedUserID = $insertNewComment['comment_uid_fk'];
					$Usercomment = $insertNewComment['comment'];
					$commentTime = isset($insertNewComment['comment_time']) ? $insertNewComment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($insertNewComment['comment_file']) ? $insertNewComment['comment_file'] : NULL;
					$stickerUrl = isset($insertNewComment['sticker_url']) ? $insertNewComment['sticker_url'] : NULL;
					$gifUrl = isset($insertNewComment['gif_url']) ? $insertNewComment['gif_url'] : NULL;
					$commentedUserIDFk = isset($insertNewComment['iuid']) ? $insertNewComment['iuid'] : NULL;
					$commentedUserName = isset($insertNewComment['i_username']) ? $insertNewComment['i_username'] : NULL;
					$userPostID = $insertNewComment['comment_post_id_fk'];
					$commentParentId = isset($insertNewComment['parent_comment_id']) ? (int)$insertNewComment['parent_comment_id'] : 0;
					$replyParentUserName = $replyTargetUserName;
					$replyParentUserFullName = $replyTargetUserFullName;
					if ($commentParentId > 0) {
						$parentComment = $iN->iN_GetCommentDetails($commentParentId);
						if ($parentComment) {
							$parentUser = $iN->iN_GetUserDetails((int)$parentComment['comment_uid_fk']);
							if ($parentUser) {
								if ($replyParentUserName === '') {
									$replyParentUserName = $parentUser['i_username'] ?? '';
								}
								if ($replyParentUserFullName === '') {
									$replyParentUserFullName = $parentUser['i_user_fullname'] ?? '';
									if ($fullnameorusername == 'no' || $replyParentUserFullName === '') {
										$replyParentUserFullName = $replyParentUserName;
									}
								}
							}
						}
					}
					if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewCommentPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
						$iN->iN_InsertNewCommentPoint($userID,$userPostID,$ataNewCommentPointAmount);
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
                       $cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					$commentedUserFullName = isset($insertNewComment['i_user_fullname']) ? $insertNewComment['i_user_fullname'] : NULL;
					if($fullnameorusername == 'no'){
						$commentedUserFullName = $commentedUserName;
					}
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($insertNewComment['user_gender']) ? $insertNewComment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($insertNewComment['last_login_time']) ? $insertNewComment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($insertNewComment['user_verified_status']) ? $insertNewComment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($checkCommentLikedBefore == '1') {
						$commentLikeBtnClass = 'c_in_unlike';
						$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					$commentLinkUrl = $insertNewComment['link_url'] ?? '';
					$commentLinkDomain = $insertNewComment['link_domain'] ?? '';
					$commentLinkTitle = $insertNewComment['link_title'] ?? '';
					$commentLinkDescription = $insertNewComment['link_description'] ?? '';
					$commentLinkImage = $insertNewComment['link_image'] ?? '';
					$isReply = $commentParentId > 0;
					include "../themes/$currentTheme/layouts/posts/comments.php";
					$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($userPostID);
					$commentedPostOwnerID = $GetPostOwnerIDFromPostDetails['post_owner_id'];
					$communityOwnerId = 0;
					if (!empty($communityMeta) && !empty($communityData)) {
						$communityOwnerId = (int)($communityData['owner_user_id'] ?? 0);
					}
					$skipDefaultCommentNotification = $communityOwnerId > 0 && (int)$communityOwnerId === (int)$commentedPostOwnerID;
					if ($userID != $commentedPostOwnerID && !$skipDefaultCommentNotification) {
						$iN->iN_InsertNotificationForCommented($commentedUserID, $userPostID);
					}
					if ($commentParentId > 0) {
						$iN->iN_InsertNotificationForCommentReply($userID, $userPostID, $commentID, $commentParentId);
					}
					if (!empty($communityMeta) && $communityOwnerId > 0 && $userID != $communityOwnerId) {
						$commentPreview = trim(strip_tags((string)$Usercomment));
						if ($commentPreview !== '') {
							$commentPreview = mb_substr($commentPreview, 0, 120, 'UTF-8');
						}
						$payload = [];
						if ($commentPreview !== '') {
							$payload['comment'] = $commentPreview;
						}
						$iN->iN_InsertCommunityNotification(
							$userID,
							$communityOwnerId,
							$communityData,
							'community_comment',
							(int)$userPostID,
							$payload
						);
					}
					if($Usercomment){
						$iN->iN_InsertMentionedUsersForComment($userID, $Usercomment, $userPostID, $commentedUserName,$commentedPostOwnerID);
					 }
					$commentPushPreview = trim(strip_tags((string)$Usercomment));
					if ($commentPushPreview !== '') {
						$commentPushPreview = mb_substr($commentPushPreview, 0, 120, 'UTF-8');
					}
					$commentPushUrl = $base_url . 'post/' . ($GetPostOwnerIDFromPostDetails['url_slug'] ?? '') . '_' . (int)$userPostID;
					if ((string)$webPushStatus === '1' && (string)$webPushEventComment === '1') {
						$commentActorName = trim((string)$commentedUserFullName) !== '' ? (string)$commentedUserFullName : (string)$commentedUserName;
						$postCommentTitle = $iN->iN_Secure($LANG['commented_on_your_post'] ?? 'Commented on your post');
						$postCommentBody = $commentActorName . ' ' . ($LANG['commented_below'] ?? 'Commented below');
						if ($commentPushPreview !== '') {
							$postCommentBody .= ': ' . $commentPushPreview;
						}
						if ($userID != $commentedPostOwnerID && !$skipDefaultCommentNotification) {
							$iN->iN_SendWebPushToUser((int)$commentedPostOwnerID, [
								'title' => $postCommentTitle,
								'body' => $iN->iN_Secure($postCommentBody),
								'url' => (string)$commentPushUrl,
								'tag' => 'comment_post_' . (int)$userPostID,
							]);
						}
						if ($commentParentId > 0) {
							$parentComment = $iN->iN_GetCommentDetails((int)$commentParentId);
							$replyRecipientId = isset($parentComment['comment_uid_fk']) ? (int)$parentComment['comment_uid_fk'] : 0;
							if ($replyRecipientId > 0 && $replyRecipientId !== (int)$userID && $replyRecipientId !== (int)$commentedPostOwnerID) {
								$replyTitle = $iN->iN_Secure($LANG['replied_to_your_comment'] ?? 'Replied to your comment');
								$iN->iN_SendWebPushToUser($replyRecipientId, [
									'title' => $replyTitle,
									'body' => $iN->iN_Secure($postCommentBody),
									'url' => (string)$commentPushUrl,
									'tag' => 'comment_reply_' . (int)$commentID,
								]);
							}
						}
					}
					$uData = $iN->iN_GetUserDetails($commentedPostOwnerID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$emailNotificationStatus = $uData['email_notification_status'];
					$notQualifyDocument = $LANG['not_qualify_document'];
					if ($emailSendStatus == '1' && $userID != $commentedPostOwnerID && $emailNotificationStatus == '1') {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$commentedBelow = $iN->iN_Secure($LANG['commented_below']);
						$commentE = $iN->iN_Secure($Usercomment);
						include_once '../includes/mailTemplates/commentEmailTemplate.php';
						$body = $bodyCommentEmail;
						$mail->setFrom($smtpUserName, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['commented_on_your_post']);
			$mail->CharSet = 'utf-8';
			$mail->MsgHTML($body);
			if (iN_safeMailSend($mail, $smtpOrMail, 'comment_like_notification')) {
				$mail->ClearAddresses();
				return true;
			}
					}

				} else {
					echo '404';
				}
			}
		}
	}

	/*Comment Like*/
	if ($type == 'pc_like') {
		if (isset($_POST['post']) && isset($_POST['com'])) {
			$postID = $iN->iN_Secure($_POST['post']);
			$postCommentID = $iN->iN_Secure($_POST['com']);
			$likePostComment = $iN->iN_LikePostComment($userID, $postID, $postCommentID);
			$status = 'c_in_like';
			$pcLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePostComment) {
				$status = 'c_in_unlike';
				$pcLike = $iN->iN_SelectedMenuIcon('18');
				$commentLikedSum = $iN->iN_TotalCommentLiked($postCommentID);
				if($iN->iN_CheckCommentOwner($userID, $postID) === false && $ataNewPostCommentLikePointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPostCommentLikePoint($userID,$postID,$ataNewPostCommentLikePointAmount);
				}
			}
			if($status == 'c_in_like'){
				if($iN->iN_CheckCommentOwner($userID, $postID) === false && $ataNewPostCommentLikePointSatus == 'yes'){
					$iN->iN_RemovePointPostCommentLikeIfExist($userID,$postID,$ataNewPostCommentLikePointAmount);
				}
			}
			$data = array(
				'status' => $status,
				'like' => $pcLike,
				'totalLike' => isset($commentLikedSum) ? $commentLikedSum : '0',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			$cLData = $iN->iN_GetUserIDFromLikedPostID($postCommentID);
			$commendOwnerID = $cLData['comment_uid_fk'];
			if ($userID != $commendOwnerID) {
				$iN->iN_insertCommentLikeNotification($userID, $postID, $postCommentID);
			}
			$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($postID);
			$uData = $iN->iN_GetUserDetails($commendOwnerID);
			$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
			$lUsername = $uData['i_username'];
			$lUserFullName = $uData['i_user_fullname'];
			$emailNotificationStatus = $uData['email_notification_status'];
			$notQualifyDocument = $LANG['not_qualify_document'];
			$slugUrl = $base_url . 'post/' . $GetPostOwnerIDFromPostDetails['url_slug'] . '_' . $postID;
			if ($emailSendStatus == '1' && $userID != $commendOwnerID && $emailNotificationStatus == '1' && $status == 'c_in_unlike') {
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$someoneLikedYourPost = $iN->iN_Secure($LANG['someone_liked_your_comment']);
				$clickGoPost = $iN->iN_Secure($LANG['click_go_comment']);
				$likedYourPost = $iN->iN_Secure($LANG['liked_your_comment']);
				include_once '../includes/mailTemplates/postLikeEmailTemplate.php';
				$body = $bodyPostLikeEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['someone_liked_your_comment']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if (iN_safeMailSend($mail, $smtpOrMail, 'comment_notification')) {
					$mail->ClearAddresses();
					return true;
				}
			}
		}
	}
	/*Delete Comment Call AlertBox*/
	if ($type == 'ddelComment') {
		if (isset($_POST['id']) && isset($_POST['pid'])) {
			$commentID = $iN->iN_Secure($_POST['id']);
			$postID = $iN->iN_Secure($_POST['pid']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteCommentAlert.php";
		}
	}
	/*Delete Comment*/
		if ($type == 'deletecomment') {
			if (isset($_POST['cid']) && isset($_POST['pid'])) {
				include_once __DIR__ . '/../includes/csrf.php';
				if (!csrf_validate_from_request()) {
					exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$commentID = $iN->iN_Secure($_POST['cid']);
			$postID = $iN->iN_Secure($_POST['pid']);
			$commentRow = DB::one(
				"SELECT com_id, comment_uid_fk, comment_post_id_fk, is_hidden, hidden_by, hidden_at FROM i_post_comments WHERE com_id = ? LIMIT 1",
				[(int)$commentID]
			);
			if ($commentRow && (int)($commentRow['comment_post_id_fk'] ?? 0) === (int)$postID) {
				$communityMeta = $iN->iN_GetCommunityPostMeta($postID);
				if ($communityMeta) {
					$communityID = (int)($communityMeta['community_id'] ?? 0);
					$communityData = $communityID > 0 ? $iN->iN_GetCommunityById($communityID) : null;
					$isAdmin = isset($userType) && (string)$userType === '2';
					$isOwner = $communityData && (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
					$moderationEnabled = $communityData && (string)($communityData['moderation_enabled'] ?? '0') === '1';
					if ($communityData && !$isOwner && !$isAdmin && $moderationEnabled) {
						$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
						if ($moderatorData && (string)($moderatorData['can_manage_comments'] ?? '0') === '1') {
							$previousHidden = (string)($commentRow['is_hidden'] ?? '0');
							if ($previousHidden !== '1') {
								$iN->iN_HideCommunityComment($commentID, $userID);
								$commentOwnerId = (int)($commentRow['comment_uid_fk'] ?? 0);
								$iN->iN_LogCommunityModeratorAction(
									$communityID,
									$userID,
									'comment_hide',
									[
										'user_id' => $commentOwnerId,
										'post_id' => (int)$postID,
										'comment_id' => (int)$commentID
									],
									[
										'previous' => [
											'is_hidden' => $previousHidden,
											'hidden_by' => $commentRow['hidden_by'] ?? null,
											'hidden_at' => $commentRow['hidden_at'] ?? null
										],
										'next' => ['is_hidden' => '1']
									]
								);
							}
							echo '200';
							exit;
						}
					}
					}
				}
				if ((string)$userType === '3' && (int)($commentRow['comment_uid_fk'] ?? 0) !== (int)$userID) {
					echo '404';
					exit;
				}
				$deleteComment = $iN->iN_DeleteComment($userID, $commentID, $postID);
	                if ($deleteComment) {
                    if($ataNewCommentPointSatus == 'yes'){$iN->iN_RemovePointCommentIfExist($userID, $postID, $ataNewCommentPointAmount);}
                    echo '200';
                    exit;
                } else {
                    echo '404';
                    exit;
                }
            }
        }
	/*Report Comment*/
	if ($type == 'reportComment') {
		if (isset($_POST['id']) && isset($_POST['pid'])) {
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
				exit;
			}
			$commentID = $iN->iN_Secure($_POST['id']);
			$postID = $iN->iN_Secure($_POST['pid']);
			$insertCommentReport = $iN->iN_InsertReportedComment($userID, $commentID, $postID);
			if ($insertCommentReport) {
				if ($insertCommentReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Report Message*/
	if ($type == 'reportMessage') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
				exit;
			}
			$messageID = $iN->iN_Secure($_POST['id']);
			$chatID = $iN->iN_Secure($_POST['cid']);
			$insertMessageReport = $iN->iN_InsertReportedMessage($userID, $messageID, $chatID);
			if ($insertMessageReport) {
				if ($insertMessageReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_message'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Show Edit Comment In PopUp*/
	if ($type == 'c_editComment') {
		if (isset($_POST['cid']) && isset($_POST['pid'])) {
			$commentID = $iN->iN_Secure($_POST['cid']);
			$postID = $iN->iN_Secure($_POST['pid']);
			$getCData = $iN->iN_GetCommentFromID($userID, $commentID, $postID);
			if ($getCData) {
				$commentText = isset($getCData['comment']) ? $getCData['comment'] : NULL;
				include "../themes/$currentTheme/layouts/posts/editComment.php";
			} else {
				echo '404';
			}
		}
	}
	/*Save Edited Comment*/
	if ($type == 'editSC') {
		if (isset($_POST['cid']) && isset($_POST['pid']) && isset($_POST['text'])) {
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
				exit;
			}
			$commentID = $iN->iN_Secure($_POST['cid']);
			$postID = $iN->iN_Secure($_POST['pid']);
			$editedText = $iN->iN_Secure($_POST['text']);
			if (empty($editedText)) {
				$status = 'no';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$saveEditedComment = $iN->iN_UpdateComment($userID, $postID, $commentID, $iN->iN_Secure($editedText));
			if ($saveEditedComment) {
				$getNewPostFromData = $iN->iN_GetCommentFromID($userID, $commentID, $postID);
				$status = '200';
				$updatedComment = $iN->sanitize_output_preserve_linebreaks($getNewPostFromData['comment'], $base_url);
				$updatedComment = nl2br($updatedComment, false);
				$data = array(
					'status' => $status,
					'text' => $updatedComment,
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$status = '404';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		}
	}
	/*Get Emojis*/
	if ($type == 'emoji') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			$ec = $iN->iN_Secure($_POST['ec']);
			$importID = '';
			if (!empty($ec)) {
				$importID = 'data-id="' . $ec . '"';
			}
			if ($id == 'emojiBox') {
				$importClass = 'emoji_item';
			} else if ($id == 'emojiBoxC') {
				$importClass = 'emoji_item_c';
			}
			include "../themes/$currentTheme/layouts/widgets/emojis.php";
		}
	}
	/*Get Stickers*/
	if ($type == 'stickers') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			include "../themes/$currentTheme/layouts/widgets/stickers.php";
		}
	}
	/*Get Story Sticker Picker*/
	if ($type == 'story_stickers') {
		include "../themes/$currentTheme/layouts/widgets/story_stickers.php";
	}
	/*Get Story Audio Picker*/
	if ($type == 'story_audios') {
		include "../themes/$currentTheme/layouts/widgets/story_audios.php";
	}
	/*Get Gifs*/
	if ($type == 'gifList') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			include "../themes/$currentTheme/layouts/widgets/gifs.php";
		}
	}
	/*Add Sticker*/
	if ($type == 'addSticker') {
		if (isset($_POST['id'])) {
			$stickerID = $iN->iN_Secure($_POST['id']);
			$ID = $iN->iN_Secure($_POST['pi']);
			$getStickerUrlandID = $iN->iN_getSticker($stickerID);
			if ($getStickerUrlandID) {
				$data = array(
					'stickerUrl' => '<div class="in_sticker_wrapper" id="stick_id_' . $getStickerUrlandID['sticker_id'] . '"><img src="' . $getStickerUrlandID['sticker_url'] . '"></div><div class="removeSticker" id="' . $ID . '">' . $iN->iN_SelectedMenuIcon('5') . '</div>',
					'st_id' => $getStickerUrlandID['sticker_id'],
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			}
		}
	}
	/*Trending Hashtags Top 100 PopUp*/
	if ($type == 'trend_hashtags_top100') {
		$trendHashtagsDays = isset($showingTrendPostLimitDay) ? (int)$showingTrendPostLimitDay : 1;
		if ($trendHashtagsDays < 1) { $trendHashtagsDays = 1; }
		$trendTags = $iN->iN_GetTrendingHashtags(100, $trendHashtagsDays);
		include "../themes/$currentTheme/layouts/popup_alerts/trend_hashtags_top100.php";
	}
	/*Get Free Follow PopUP*/
	if ($type == 'follow_free_not') {
		if (isset($_POST['id'])) {
			$uID = $iN->iN_Secure($_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				$userDetail = $iN->iN_GetUserDetails($uID);
				$f_userID = $userDetail['iuid'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				include "../themes/$currentTheme/layouts/popup_alerts/free_follow_popup.php";
			}
		}
	}
	/*Follow Profile Free*/
	if ($type == 'freeFollow') {
		if (isset($_POST['follow'])) {
			$uID = $iN->iN_Secure($_POST['follow']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				$checkUserFollowing = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
				if ($checkUserFollowing != 'me') {
					$insertNewFollowingList = $iN->iN_insertNewFollow($userID, $uID);
					if ($insertNewFollowingList == 'flw') {
						$status = '200';
						$not = $insertNewFollowingList;
						$btn = $iN->iN_SelectedMenuIcon('66') . $LANG['unfollow'];
						$iN->iN_InsertNotificationForFollow($userID, $uID);
						if ((string)$webPushStatus === '1' && (string)$webPushEventFollow === '1' && (int)$uID !== (int)$userID) {
							$actorName = trim((string)$userFullName) !== '' ? (string)$userFullName : (string)$userName;
							$pushTitle = $iN->iN_Secure($LANG['now_following_your_profile'] ?? 'Started following your profile');
							$pushBody = $iN->iN_Secure($actorName . ' ' . ($LANG['is_now_following_your_profile'] ?? 'is now following your profile'));
							$iN->iN_SendWebPushToUser((int)$uID, [
								'title' => $pushTitle,
								'body' => $pushBody,
								'url' => $base_url . $userName,
								'tag' => 'follow_' . (int)$userID,
							]);
						}
					} else if ($insertNewFollowingList == 'unflw') {
						$status = '200';
						$not = $insertNewFollowingList;
						$btn = $iN->iN_SelectedMenuIcon('66') . $LANG['follow'];
						$iN->iN_RemoveNotificationForFollow($userID, $uID);
					} else {
						$status = '404';
						$not = '';
						$btn = '';
					}
					$data = array(
						'status' => $status,
						'text' => $not,
						'btn' => $btn,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					$uData = $iN->iN_GetUserDetails($uID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$lUsername = $uData['i_username'];
					$fuserAvatar = $iN->iN_UserAvatar($uID, $base_url);
					$lUserFullName = $userFullName;
					$emailNotificationStatus = $uData['email_notification_status'];
					$notQualifyDocument = $LANG['not_qualify_document'];
					$slugUrl = $base_url . $lUsername;
					if ($emailSendStatus == '1' && $emailNotificationStatus == '1' && $insertNewFollowingList == 'flw') {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
						include_once '../includes/mailTemplates/userFollowingEmailTemplate.php';
						$body = $bodyUserFollowEmailTemplate;
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if (iN_safeMailSend($mail, $smtpOrMail, 'follow_notification')) {
							$mail->ClearAddresses();
							return true;
						}
					}
				}
			}
		}
	}
	/*Block User PopUp Call*/
	if ($type == 'uBlockNotice') {
		if (isset($_POST['id'])) {
			$iuID = $iN->iN_Secure($_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			if ($checkUserExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userfullname = $userDetail['i_user_fullname'];
				include "../themes/$currentTheme/layouts/popup_alerts/userBlockAlert.php";
			}
		}
	}
	/*Block User*/
	if ($type == 'ublock') {
		if (isset($_POST['id']) && in_array($_POST['blckt'], $blockType)) {
			$uID = $iN->iN_Secure($_POST['id']);
			$uBlockType = $iN->iN_Secure($_POST['blckt']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$friendsStatusTwo = $iN->iN_GetRelationsipBetweenTwoUsers($uID, $userID);
					$addBlockList = $iN->iN_InsertBlockList($userID, $uID, $uBlockType);
					if ($addBlockList == 'bAdded') {
						$status = '200';
						$redirect = $base_url . 'settings?tab=blocked';
					} else if ($addBlockList == 'bRemoved') {
						$status = '200';
						$redirect = $base_url . 'settings?tab=blocked';
					} else {
						$status = '404';
						$redirect = '';
					}
					if ($addBlockList == 'bAdded' && $uBlockType == '2') {
						if ($friendsStatus == 'subscriber') {
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($userID, $uID);
						} else if ($friendsStatus == 'flwr') {
							$iN->iN_insertNewFollow($userID, $uID);
						}
						if ($friendsStatusTwo == 'subscriber') {
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($uID, $userID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($uID, $userID);
						} else if ($friendsStatusTwo == 'flwr') {
							$iN->iN_insertNewFollow($uID, $userID);
						}
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	/*Subscribe Modal with Methods*/
	if ($type == 'subsModal') {
		if (isset($_POST['id'])) {
			$iuID = $iN->iN_Secure($_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			$p_friend_status = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $iuID);
			if ($checkUserExist && $p_friend_status != 'subscriber') {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userID = $userDetail['iuid'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				if($subscriptionType == '2'){
					include "../themes/$currentTheme/layouts/popup_alerts/becomeSubscriberWithPoint.php";
				}else if($subscriptionType == '1'){
					include "../themes/$currentTheme/layouts/popup_alerts/becomeSubscriber.php";
				}
			}
		}
	}
	/*Credit Card popUp*/
	if ($type == 'creditCard') {
		if (isset($_POST['plan']) && isset($_POST['id'])) {
			$planID = $iN->iN_Secure($_POST['plan']);
			$iuID = $iN->iN_Secure($_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			if ($checkPlanExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userID = $userDetail['iuid'];
				$f_PlanAmount = $checkPlanExist['amount'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				include "../themes/$currentTheme/layouts/popup_alerts/payWithCreditCard.php";
			}
		}
	}
	/*Credit Card popUp*/
	/*Community Create Modal*/
	if ($type == 'communityCreateModal') {
		if ($logedIn != 1) {
			exit;
		}
		include_once __DIR__ . '/../includes/csrf.php';
		$isCreator = $iN->iN_CheckUserIsCreator($userID) == 1;
		$hasCommunityPlan = $iN->iN_HasActiveCommunityPlan($userID);
		$communityPlanAmount = ((string)$subscriptionType === '2') ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
		$communityCategories = $iN->iN_GetCommunityCategories(true);
		$communityCreationSettings = $iN->iN_GetCommunityCreationSettings();
		include "../themes/$currentTheme/layouts/popup_alerts/communityCreate.php";
	}
	/*Community Create*/
	if ($type == 'communityCreate') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || $iN->iN_CheckUserIsCreator($userID) != 1) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_creator_only'] ?? 'Creator only.']);
			exit;
		}
		if (!$iN->iN_HasActiveCommunityPlan($userID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_plan_required'] ?? 'Community plan required.']);
			exit;
		}
		$creationSettings = $iN->iN_GetCommunityCreationSettings();
		$creationPolicy = isset($creationSettings['policy']) ? (string)$creationSettings['policy'] : 'paid_only';
		if (!in_array($creationPolicy, ['paid_only', 'free_only', 'both'], true)) {
			$creationPolicy = 'paid_only';
		}
		$freeMemberLimit = isset($creationSettings['free_limit']) ? (int)$creationSettings['free_limit'] : 10;
		if ($freeMemberLimit < 1) {
			$freeMemberLimit = 1;
		}
		$title = isset($_POST['community_title']) ? $iN->iN_Secure($_POST['community_title'], 1, false) : '';
		$category = isset($_POST['community_category']) ? $iN->iN_Secure($_POST['community_category'], 1, false) : '';
		$description = isset($_POST['community_description']) ? $iN->iN_Secure($_POST['community_description']) : '';
		$accessRaw = isset($_POST['community_access_type']) ? strtolower(trim((string)$iN->iN_Secure($_POST['community_access_type']))) : '';
		$subscriptionRequired = '1';
		if ($accessRaw !== '') {
			if (in_array($accessRaw, ['free', '0'], true)) {
				$subscriptionRequired = '0';
			}
			if (in_array($accessRaw, ['paid', '1'], true)) {
				$subscriptionRequired = '1';
			}
		}
		if ($creationPolicy === 'paid_only' && $subscriptionRequired === '0') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_free_creation_disabled'] ?? 'Free communities are disabled.']);
			exit;
		}
		if ($creationPolicy === 'free_only' && $subscriptionRequired === '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_paid_creation_disabled'] ?? 'Paid communities are disabled.']);
			exit;
		}
		$memberLimitRaw = isset($_POST['community_member_limit']) ? $iN->iN_Secure($_POST['community_member_limit']) : '';
		$memberLimit = ($memberLimitRaw !== '' && $memberLimitRaw !== '0') ? (int)$memberLimitRaw : null;
		$monthlyPrice = isset($_POST['community_monthly_price']) ? rq_parse_amount_value($_POST['community_monthly_price']) : 0;
		if (mb_strlen($title, 'UTF-8') < 3 || mb_strlen($title, 'UTF-8') > 160) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_name_required'] ?? 'Invalid community name.']);
			exit;
		}
		if ($category === '' || !$iN->iN_IsCommunityCategoryEnabled($category)) {
			$message = $LANG['community_category_required'] ?? 'Invalid category.';
			if ($category !== '' && !$iN->iN_IsCommunityCategoryEnabled($category)) {
				$message = $LANG['community_category_disabled'] ?? $message;
			}
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		if (!empty($description) && mb_strlen(strip_tags($description), 'UTF-8') > 2000) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_description_too_long'] ?? 'Description too long.']);
			exit;
		}
		if ($subscriptionRequired === '1') {
			if ($monthlyPrice <= 0) {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_price_required'] ?? 'Invalid monthly price.']);
				exit;
			}
		} else {
			$monthlyPrice = 0;
		}
		if ($subscriptionRequired === '0') {
			if ($memberLimit === null || $memberLimit < 1) {
				$memberLimit = $freeMemberLimit;
			}
			if ($memberLimit > $freeMemberLimit) {
				$message = $LANG['community_free_limit_exceeded'] ?? 'Free member limit exceeded.';
				$message = str_replace('{limit}', (string)$freeMemberLimit, $message);
				echo json_encode(['status' => 'error', 'message' => $message]);
				exit;
			}
		} elseif ($memberLimit !== null && $memberLimit < 1) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_limit_invalid'] ?? 'Invalid member limit.']);
			exit;
		}

		$coverImagePath = null;
		if (isset($_FILES['community_cover']['name']) && $_FILES['community_cover']['name'] !== '') {
			$coverName = $_FILES['community_cover']['name'];
			$coverTmp = $_FILES['community_cover']['tmp_name'];
			$coverSize = isset($_FILES['community_cover']['size']) ? (int)$_FILES['community_cover']['size'] : 0;
			$ext = strtolower(getExtension($coverName));
			$allowedCoverExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			if (!in_array($ext, $allowedCoverExt, true)) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_file_format'] ?? 'Invalid file format.']);
				exit;
			}
			if (function_exists('convert_to_mb') && convert_to_mb($coverSize) > $availableUploadFileSize) {
				echo json_encode(['status' => 'error', 'message' => $LANG['file_is_too_large'] ?? 'File too large.']);
				exit;
			}
			$uploadCommunity = rtrim($uploadRoot, '/') . '/communities/';
			$todayDir = date('Y-m-d');
			$uploadDir = $uploadCommunity . $todayDir . '/';
			if (!is_dir($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$fileBase = 'community_' . $removeMicrotime . '_' . $userID;
			$filename = $fileBase . '.' . $ext;
			$uploadedPath = $uploadDir . $filename;
			if (!move_uploaded_file($coverTmp, $uploadedPath)) {
				echo json_encode(['status' => 'error', 'message' => $LANG['upload_failed'] ?? 'Upload failed.']);
				exit;
			}
			$coverImagePath = 'uploads/communities/' . $todayDir . '/' . $filename;
			if (function_exists('storage_publish_and_url')) {
				storage_publish_and_url($coverImagePath, [$coverImagePath], true);
			}
		}

		$communityId = $iN->iN_CreateCommunity($userID, $title, $category, $description, $coverImagePath, $subscriptionRequired, $monthlyPrice, $memberLimit);
		if ($communityId) {
			$communityData = $iN->iN_GetCommunityById($communityId);
			$slug = $communityData['slug'] ?? '';
			$redirect = $slug ? ($base_url . 'community/' . $slug) : $base_url . 'communities';
			echo json_encode(['status' => 'success', 'redirect' => $redirect]);
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['something_went_wrong'] ?? 'Something went wrong.']);
		}
		exit;
	}
	/*Community Update (Owner)*/
	if ($type == 'communityUpdate') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_update_forbidden'] ?? 'Forbidden.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (int)($communityData['owner_user_id'] ?? 0) !== (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_update_forbidden'] ?? 'Forbidden.']);
			exit;
		}
		if ((string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
			exit;
		}
		$creationSettings = $iN->iN_GetCommunityCreationSettings();
		$creationPolicy = isset($creationSettings['policy']) ? (string)$creationSettings['policy'] : 'paid_only';
		if (!in_array($creationPolicy, ['paid_only', 'free_only', 'both'], true)) {
			$creationPolicy = 'paid_only';
		}
		$freeMemberLimit = isset($creationSettings['free_limit']) ? (int)$creationSettings['free_limit'] : 10;
		if ($freeMemberLimit < 1) {
			$freeMemberLimit = 1;
		}
		$title = isset($_POST['community_title']) ? $iN->iN_Secure($_POST['community_title'], 1, false) : '';
		$category = isset($_POST['community_category']) ? $iN->iN_Secure($_POST['community_category'], 1, false) : '';
		$description = isset($_POST['community_description']) ? $iN->iN_Secure($_POST['community_description']) : '';
		$accessRaw = isset($_POST['community_access_type']) ? strtolower(trim((string)$iN->iN_Secure($_POST['community_access_type']))) : '';
		$subscriptionRequired = (string)($communityData['subscription_required'] ?? '1');
		$previousSubscriptionRequired = $subscriptionRequired;
		if ($accessRaw !== '') {
			if (in_array($accessRaw, ['free', '0'], true)) {
				$subscriptionRequired = '0';
			}
			if (in_array($accessRaw, ['paid', '1'], true)) {
				$subscriptionRequired = '1';
			}
		}
		$postingPolicy = (string)($communityData['posting_policy'] ?? 'owner_admin');
		$postingPolicyRaw = isset($_POST['community_posting_policy'])
			? strtolower(trim((string)$iN->iN_Secure($_POST['community_posting_policy'])))
			: '';
		$allowedPostingPolicies = ['owner_admin', 'owner_admin_moderators', 'members'];
		if ($postingPolicyRaw !== '' && in_array($postingPolicyRaw, $allowedPostingPolicies, true)) {
			$postingPolicy = $postingPolicyRaw;
		}
		$commentPolicy = (string)($communityData['comment_policy'] ?? 'members');
		$commentPolicyRaw = isset($_POST['community_comment_policy'])
			? strtolower(trim((string)$iN->iN_Secure($_POST['community_comment_policy'])))
			: '';
		$allowedCommentPolicies = ['owner_admin', 'owner_admin_moderators', 'members'];
		if ($commentPolicyRaw !== '' && in_array($commentPolicyRaw, $allowedCommentPolicies, true)) {
			$commentPolicy = $commentPolicyRaw;
		}
		$accessPolicy = (string)($communityData['access_policy'] ?? 'members_only');
		$accessPolicyRaw = isset($_POST['community_access_policy'])
			? strtolower(trim((string)$iN->iN_Secure($_POST['community_access_policy'])))
			: '';
		$allowedAccessPolicies = ['members_only', 'public'];
		if ($accessPolicyRaw !== '' && in_array($accessPolicyRaw, $allowedAccessPolicies, true)) {
			$accessPolicy = $accessPolicyRaw;
		}
		$upgradeToPaid = ($previousSubscriptionRequired === '0' && $subscriptionRequired === '1');
		if ($creationPolicy === 'paid_only' && $subscriptionRequired === '0') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_free_creation_disabled'] ?? 'Free communities are disabled.']);
			exit;
		}
		if ($creationPolicy === 'free_only' && $subscriptionRequired === '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_paid_creation_disabled'] ?? 'Paid communities are disabled.']);
			exit;
		}
		$memberLimitRaw = isset($_POST['community_member_limit']) ? $iN->iN_Secure($_POST['community_member_limit']) : '';
		$memberLimit = ($memberLimitRaw !== '' && $memberLimitRaw !== '0') ? (int)$memberLimitRaw : null;
		$monthlyPrice = isset($_POST['community_monthly_price']) ? rq_parse_amount_value($_POST['community_monthly_price']) : 0;
		if (mb_strlen($title, 'UTF-8') < 3 || mb_strlen($title, 'UTF-8') > 160) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_name_required'] ?? 'Invalid community name.']);
			exit;
		}
		if ($category === '' || !$iN->iN_IsCommunityCategoryEnabled($category)) {
			$message = $LANG['community_category_required'] ?? 'Invalid category.';
			if ($category !== '' && !$iN->iN_IsCommunityCategoryEnabled($category)) {
				$message = $LANG['community_category_disabled'] ?? $message;
			}
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		if (!empty($description) && mb_strlen(strip_tags($description), 'UTF-8') > 2000) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_description_too_long'] ?? 'Description too long.']);
			exit;
		}
		if ($subscriptionRequired === '1') {
			if ($monthlyPrice <= 0) {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_price_required'] ?? 'Invalid monthly price.']);
				exit;
			}
		} else {
			$monthlyPrice = 0;
		}
		if ($subscriptionRequired === '0') {
			if ($memberLimit === null || $memberLimit < 1) {
				$memberLimit = $freeMemberLimit;
			}
			if ($memberLimit > $freeMemberLimit) {
				$message = $LANG['community_free_limit_exceeded'] ?? 'Free member limit exceeded.';
				$message = str_replace('{limit}', (string)$freeMemberLimit, $message);
				echo json_encode(['status' => 'error', 'message' => $message]);
				exit;
			}
		} elseif ($memberLimit !== null && $memberLimit < 1) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_limit_invalid'] ?? 'Invalid member limit.']);
			exit;
		}

		$avatarImagePath = null;
		if (isset($_FILES['community_avatar']['name']) && $_FILES['community_avatar']['name'] !== '') {
			$avatarName = $_FILES['community_avatar']['name'];
			$avatarTmp = $_FILES['community_avatar']['tmp_name'];
			$avatarSize = isset($_FILES['community_avatar']['size']) ? (int)$_FILES['community_avatar']['size'] : 0;
			$ext = strtolower(getExtension($avatarName));
			$allowedAvatarExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			if (!in_array($ext, $allowedAvatarExt, true)) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_file_format'] ?? 'Invalid file format.']);
				exit;
			}
			if (function_exists('convert_to_mb') && convert_to_mb($avatarSize) > $availableUploadFileSize) {
				echo json_encode(['status' => 'error', 'message' => $LANG['file_is_too_large'] ?? 'File too large.']);
				exit;
			}
			$uploadCommunity = rtrim($uploadRoot, '/') . '/communities/';
			$todayDir = date('Y-m-d');
			$uploadDir = $uploadCommunity . $todayDir . '/';
			if (!is_dir($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$fileBase = 'community_avatar_' . $removeMicrotime . '_' . $userID;
			$filename = $fileBase . '.' . $ext;
			$uploadedPath = $uploadDir . $filename;
			if (!move_uploaded_file($avatarTmp, $uploadedPath)) {
				echo json_encode(['status' => 'error', 'message' => $LANG['upload_failed'] ?? 'Upload failed.']);
				exit;
			}
			$avatarImagePath = 'uploads/communities/' . $todayDir . '/' . $filename;
			if (function_exists('storage_publish_and_url')) {
				storage_publish_and_url($avatarImagePath, [$avatarImagePath], true);
			}
		}

		$coverImagePath = null;
		if (isset($_FILES['community_cover']['name']) && $_FILES['community_cover']['name'] !== '') {
			$coverName = $_FILES['community_cover']['name'];
			$coverTmp = $_FILES['community_cover']['tmp_name'];
			$coverSize = isset($_FILES['community_cover']['size']) ? (int)$_FILES['community_cover']['size'] : 0;
			$ext = strtolower(getExtension($coverName));
			$allowedCoverExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			if (!in_array($ext, $allowedCoverExt, true)) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_file_format'] ?? 'Invalid file format.']);
				exit;
			}
			if (function_exists('convert_to_mb') && convert_to_mb($coverSize) > $availableUploadFileSize) {
				echo json_encode(['status' => 'error', 'message' => $LANG['file_is_too_large'] ?? 'File too large.']);
				exit;
			}
			$uploadCommunity = rtrim($uploadRoot, '/') . '/communities/';
			$todayDir = date('Y-m-d');
			$uploadDir = $uploadCommunity . $todayDir . '/';
			if (!is_dir($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$fileBase = 'community_cover_' . $removeMicrotime . '_' . $userID;
			$filename = $fileBase . '.' . $ext;
			$uploadedPath = $uploadDir . $filename;
			if (!move_uploaded_file($coverTmp, $uploadedPath)) {
				echo json_encode(['status' => 'error', 'message' => $LANG['upload_failed'] ?? 'Upload failed.']);
				exit;
			}
			$coverImagePath = 'uploads/communities/' . $todayDir . '/' . $filename;
			if (function_exists('storage_publish_and_url')) {
				storage_publish_and_url($coverImagePath, [$coverImagePath], true);
			}
		}

		$updateData = [
			'title' => $title,
			'category' => $category,
			'description' => $description !== '' ? $description : null,
			'monthly_price' => (string)$monthlyPrice,
			'member_limit' => $memberLimit,
			'subscription_required' => $subscriptionRequired,
			'posting_policy' => $postingPolicy,
			'comment_policy' => $commentPolicy,
			'access_policy' => $accessPolicy
		];
		if ($avatarImagePath !== null) {
			$updateData['avatar_image'] = $avatarImagePath;
		}
		if ($coverImagePath !== null) {
			$updateData['cover_image'] = $coverImagePath;
		}

		$updated = $iN->iN_UpdateCommunity($communityID, $updateData);
		if ($updated) {
			if ($upgradeToPaid) {
				$iN->iN_ExpireCommunityFreeMembers($communityID);
			}
			echo json_encode(['status' => 'success', 'message' => $LANG['community_update_success'] ?? 'Community updated.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_update_failed'] ?? 'Update failed.']);
		}
		exit;
	}
	/*Community Delete (Owner)*/
	if ($type == 'communityDelete') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_delete_failed'] ?? 'Delete failed.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (int)($communityData['owner_user_id'] ?? 0) !== (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_delete_failed'] ?? 'Delete failed.']);
			exit;
		}
		$updated = $iN->iN_UpdateCommunity($communityID, ['status' => 'inactive']);
		if ($updated) {
			echo json_encode([
				'status' => 'success',
				'message' => $LANG['community_delete_success'] ?? 'Community deleted.',
				'redirect' => $base_url . 'communities'
			]);
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_delete_failed'] ?? 'Delete failed.']);
		}
		exit;
	}
	/*Community Join (Free)*/
	if ($type == 'communityJoinFree') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_join_failed'] ?? 'Join failed.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
			exit;
		}
		if ((string)($communityData['subscription_required'] ?? '1') !== '0') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_subscription_required'] ?? 'Subscription required.']);
			exit;
		}
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
		if ($restrictionStatus === 'blocked') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_blocked'] ?? 'Community access blocked.']);
			exit;
		}
		if ((int)$communityData['owner_user_id'] === (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_owner_only'] ?? 'Owner only.']);
			exit;
		}
		if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
			$slug = $communityData['slug'] ?? '';
			$redirect = $slug ? ($base_url . 'community/' . $slug) : $base_url . 'communities';
			echo json_encode(['status' => 'success', 'redirect' => $redirect]);
			exit;
		}
		$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
		$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
		if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_full'] ?? 'Community is full.']);
			exit;
		}
		$now = date('Y-m-d H:i:s');
		$iN->iN_CreateCommunityMembership($communityID, $userID, null, 'active', $now, null);
		$ownerId = (int)($communityData['owner_user_id'] ?? 0);
		if ($ownerId > 0) {
			$iN->iN_InsertCommunityNotification($userID, $ownerId, $communityData, 'community_subscribe');
		}
		$slug = $communityData['slug'] ?? '';
		$redirect = $slug ? ($base_url . 'community/' . $slug) : $base_url . 'communities';
		echo json_encode(['status' => 'success', 'redirect' => $redirect]);
		exit;
	}
	/*Community Post Create*/
	if ($type == 'communityPostCreate') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_post_failed'] ?? 'Post failed.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
			exit;
		}
		if ((string)($communityData['posting_enabled'] ?? '1') !== '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_posting_disabled'] ?? 'Posting disabled.']);
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$postingPolicy = (string)($communityData['posting_policy'] ?? 'owner_admin');
		if (!in_array($postingPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
			$postingPolicy = 'owner_admin';
		}
		$isModerator = false;
		if (!$isOwner && !$isAdmin && $postingPolicy === 'owner_admin_moderators') {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
			$isModerator = !empty($moderatorData);
		}
		$canPostToCommunity = $isOwner || $isAdmin;
		if (!$canPostToCommunity && $postingPolicy === 'owner_admin_moderators' && $isModerator) {
			$canPostToCommunity = true;
		}
		if (!$canPostToCommunity && $postingPolicy === 'members') {
			$canPostToCommunity = $iN->iN_IsCommunityAccessMember($communityData, $userID);
		}
		if (!$canPostToCommunity) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_post_forbidden'] ?? 'Forbidden.']);
			exit;
		}
		if (!$isOwner && !$isAdmin && $iN->iN_IsCommunityPostDisabled($communityID, $userID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_post_forbidden'] ?? 'Forbidden.']);
			exit;
		}
		$text = isset($_POST['community_post_text']) ? $iN->iN_Secure($_POST['community_post_text']) : '';
		if (trim((string)$text) === '') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_post_text_required'] ?? 'Post text is required.']);
			exit;
		}
		$slug = $iN->url_slugies(mb_substr($text, 0, 55, "utf-8"));
		if ($slug === '') {
			$slug = $iN->random_code(8);
		}
		$hashT = $iN->iN_hashtag($text);
		$postFromData = $iN->iN_InsertNewPost(
			$userID,
			$iN->iN_Secure($text),
			$slug,
			'',
			'1',
			$iN->url_Hash($hashT),
			'',
			$autoApprovePostStatus,
			'normal',
			null,
			'published'
		);
		if ($postFromData && isset($postFromData['post_id'])) {
			$iN->iN_CreateCommunityPost($communityID, (int)$postFromData['post_id'], $userID);
			echo json_encode(['status' => 'success', 'message' => $LANG['community_post_success'] ?? 'Post created.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_post_failed'] ?? 'Post failed.']);
		}
		exit;
	}
	/*Community Member Moderation*/
	if ($type == 'communityMemberModerate') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['member_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$memberID = (int)$iN->iN_Secure($_POST['member_id']);
		$status = isset($_POST['status']) ? $iN->iN_Secure($_POST['status']) : null;
		$postDisabled = isset($_POST['post_disabled']) ? $iN->iN_Secure($_POST['post_disabled']) : null;
		$commentDisabled = isset($_POST['comment_disabled']) ? $iN->iN_Secure($_POST['comment_disabled']) : null;
		$reshareDisabled = isset($_POST['reshare_disabled']) ? $iN->iN_Secure($_POST['reshare_disabled']) : null;
		$viewTimeout = isset($_POST['view_timeout']) ? $iN->iN_Secure($_POST['view_timeout']) : null;
		$communityData = $iN->iN_GetCommunityById($communityID);
		$isAdmin = isset($userType) && (string)$userType === '2';
		if (!$communityData) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
			exit;
		}
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$moderationEnabled = (string)($communityData['moderation_enabled'] ?? '0') === '1';
		$timeoutEnabled = (string)($communityData['moderation_timeout_enabled'] ?? '0') === '1';
		$moderatorData = null;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
		}
		$isModerator = !empty($moderatorData);
		$canManageMembers = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_members'] ?? '0') === '1');
		$canManagePosts = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_posts'] ?? '0') === '1');
		$canManageComments = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_comments'] ?? '0') === '1');
		$canManageReshare = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_reshare'] ?? '0') === '1');
		$canManageTimeout = $timeoutEnabled && ($isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_view_timeout'] ?? '0') === '1'));
		if (!$canManageMembers && !$canManageReshare && !$canManageTimeout && !$canManagePosts && !$canManageComments) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
			exit;
		}
		if ($memberID <= 0 || (int)($communityData['owner_user_id'] ?? 0) === $memberID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
			exit;
		}
		if (!$iN->iN_CheckUserExist($memberID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
			exit;
		}
		$membership = $iN->iN_GetCommunityMembership($communityID, $memberID);
		$isMemberActive = $membership && (string)($membership['status'] ?? '') === 'active';
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $memberID);
		$hasConnection = $iN->iN_CheckUserIsInFLWR($userID, $memberID)
			|| $iN->iN_CheckUserIsInFLWR($memberID, $userID);
		if (!$isAdmin && !$isOwner && !$isMemberActive && !$hasConnection && $restrictionStatus === null) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
			exit;
		}
		$changes = 0;
		$shouldLog = $isModerator && !$isOwner && !$isAdmin;
		$restrictionDetails = [];
		if ($status !== null) {
			if (!$canManageMembers) {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
				exit;
			}
			$status = (string)$status;
			if ($status === 'active') {
				if ($restrictionStatus !== null) {
					$iN->iN_ClearCommunityRestriction($communityID, $memberID);
					$changes++;
					if ($shouldLog) {
						$iN->iN_LogCommunityModeratorAction(
							$communityID,
							$userID,
							'member_status',
							['user_id' => $memberID],
							['previous' => ['status' => $restrictionStatus], 'next' => ['status' => 'active']]
						);
					}
					$memberControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
					if (!$memberControl) {
						$iN->iN_SetCommunityMemberControl($communityID, $memberID, [], $userID);
					}
				}
			} elseif (in_array($status, ['blocked', 'restricted'], true)) {
				if ($restrictionStatus !== $status) {
					$iN->iN_SetCommunityRestriction($communityID, $memberID, $status, $userID);
					$changes++;
					if ($shouldLog) {
						$iN->iN_LogCommunityModeratorAction(
							$communityID,
							$userID,
							'member_status',
							['user_id' => $memberID],
							['previous' => ['status' => $restrictionStatus], 'next' => ['status' => $status]]
						);
					}
					$restrictionDetails['member_status'] = $status;
				}
			} else {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
				exit;
			}
		}
		if ($postDisabled !== null) {
			if (!$canManagePosts) {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
				exit;
			}
			$postDisabled = in_array((string)$postDisabled, ['0', '1'], true) ? (string)$postDisabled : '0';
			$currentControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
			$previousPost = (string)($currentControl['post_disabled'] ?? '0');
			$previousExists = $currentControl ? '1' : '0';
			if ($previousPost !== $postDisabled) {
				$iN->iN_SetCommunityMemberControl($communityID, $memberID, ['post_disabled' => $postDisabled], $userID);
				$changes++;
				if ($shouldLog) {
					$iN->iN_LogCommunityModeratorAction(
						$communityID,
						$userID,
						'post_control',
						['user_id' => $memberID],
						[
							'previous' => ['post_disabled' => $previousPost, 'exists' => $previousExists],
							'next' => ['post_disabled' => $postDisabled]
						]
					);
				}
				if ($postDisabled === '1') {
					$restrictionDetails['posts'] = 'disabled';
				}
			}
		}
		if ($commentDisabled !== null) {
			if (!$canManageComments) {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
				exit;
			}
			$commentDisabled = in_array((string)$commentDisabled, ['0', '1'], true) ? (string)$commentDisabled : '0';
			$currentControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
			$previousComment = (string)($currentControl['comment_disabled'] ?? '0');
			$previousExists = $currentControl ? '1' : '0';
			if ($previousComment !== $commentDisabled) {
				$iN->iN_SetCommunityMemberControl($communityID, $memberID, ['comment_disabled' => $commentDisabled], $userID);
				$changes++;
				if ($shouldLog) {
					$iN->iN_LogCommunityModeratorAction(
						$communityID,
						$userID,
						'comment_control',
						['user_id' => $memberID],
						[
							'previous' => ['comment_disabled' => $previousComment, 'exists' => $previousExists],
							'next' => ['comment_disabled' => $commentDisabled]
						]
					);
				}
				if ($commentDisabled === '1') {
					$restrictionDetails['comments'] = 'disabled';
				}
			}
		}
		if ($reshareDisabled !== null) {
			if (!$canManageReshare) {
				echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
				exit;
			}
			$reshareDisabled = in_array((string)$reshareDisabled, ['0', '1'], true) ? (string)$reshareDisabled : '0';
			$currentControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
			$previousReshare = (string)($currentControl['reshare_disabled'] ?? '0');
			$previousExists = $currentControl ? '1' : '0';
			if ($previousReshare !== $reshareDisabled) {
				$iN->iN_SetCommunityMemberControl($communityID, $memberID, ['reshare_disabled' => $reshareDisabled], $userID);
				$changes++;
				if ($shouldLog) {
					$iN->iN_LogCommunityModeratorAction(
						$communityID,
						$userID,
						'reshare_control',
						['user_id' => $memberID],
						[
							'previous' => ['reshare_disabled' => $previousReshare, 'exists' => $previousExists],
							'next' => ['reshare_disabled' => $reshareDisabled]
						]
					);
				}
				if ($reshareDisabled === '1') {
					$restrictionDetails['reshare'] = 'disabled';
				}
			}
		}
		if ($viewTimeout !== null) {
			$viewTimeout = trim((string)$viewTimeout);
			if ($viewTimeout !== 'keep') {
				if (!$canManageTimeout) {
					echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
					exit;
				}
				$timeoutOptions = $iN->iN_GetCommunityTimeoutOptions($communityData);
				$currentControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
				$previousExists = $currentControl ? '1' : '0';
				$previousUntil = $currentControl['view_until'] ?? null;
				$previousPermanent = (string)($currentControl['view_permanent'] ?? '0') === '1' ? '1' : '0';
				$newUntil = null;
				$newPermanent = '0';
				if ($viewTimeout === 'none') {
					$newUntil = null;
					$newPermanent = '0';
				} elseif ($viewTimeout === 'permanent') {
					if (!in_array('permanent', $timeoutOptions, true)) {
						echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
						exit;
					}
					$newUntil = null;
					$newPermanent = '1';
				} elseif (in_array($viewTimeout, $timeoutOptions, true)) {
					$days = (int)$viewTimeout;
					$newUntil = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
					$newPermanent = '0';
				} else {
					echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
					exit;
				}
				if ($previousUntil !== $newUntil || $previousPermanent !== $newPermanent) {
					$iN->iN_SetCommunityMemberControl(
						$communityID,
						$memberID,
						['view_until' => $newUntil, 'view_permanent' => $newPermanent],
						$userID
					);
					$changes++;
					if ($shouldLog) {
						$iN->iN_LogCommunityModeratorAction(
							$communityID,
							$userID,
							'view_timeout',
							['user_id' => $memberID],
							[
								'previous' => ['view_until' => $previousUntil, 'view_permanent' => $previousPermanent, 'exists' => $previousExists],
								'next' => ['view_until' => $newUntil, 'view_permanent' => $newPermanent]
							]
						);
					}
					if ($newPermanent === '1' || !empty($newUntil)) {
						$restrictionDetails['view_timeout'] = [
							'permanent' => $newPermanent,
							'until' => $newUntil
						];
					}
				}
			}
		}
		if ($changes > 0) {
			if ($shouldLog && !empty($restrictionDetails)) {
				$iN->iN_InsertCommunityNotification(
					$userID,
					$memberID,
					$communityData,
					'community_restriction',
					null,
					['fields' => $restrictionDetails]
				);
			}
			echo json_encode(['status' => 'success', 'message' => $LANG['community_member_action_success'] ?? 'Updated.']);
			exit;
		}
		echo json_encode(['status' => 'error', 'message' => $LANG['community_member_action_failed'] ?? 'Action failed.']);
		exit;
	}
	/*Community Moderation Revert*/
	if ($type == 'communityModerationRevert') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['action_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_revert_failed'] ?? 'Action failed.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$actionID = (int)$iN->iN_Secure($_POST['action_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_revert_failed'] ?? 'Action failed.']);
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		if (!$isOwner && !$isAdmin) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_revert_failed'] ?? 'Action failed.']);
			exit;
		}
		$actionRow = DB::one(
			"SELECT * FROM community_moderator_actions WHERE id = ? AND community_id = ? LIMIT 1",
			[$actionID, $communityID]
		);
		if (!$actionRow || !empty($actionRow['reverted_at'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_revert_failed'] ?? 'Action failed.']);
			exit;
		}
		$actionType = $actionRow['action_type'] ?? '';
		$actionData = [];
		if (!empty($actionRow['action_data'])) {
			$decoded = json_decode($actionRow['action_data'], true);
			if (is_array($decoded)) {
				$actionData = $decoded;
			}
		}
		$previous = $actionData['previous'] ?? [];
		$targetUserID = isset($actionRow['target_user_id']) ? (int)$actionRow['target_user_id'] : 0;
		$targetPostID = isset($actionRow['target_post_id']) ? (int)$actionRow['target_post_id'] : 0;
		$targetCommentID = isset($actionRow['target_comment_id']) ? (int)$actionRow['target_comment_id'] : 0;
		switch ($actionType) {
			case 'member_status':
				$prevStatus = $previous['status'] ?? null;
				if ($prevStatus === null || $prevStatus === 'active') {
					$iN->iN_ClearCommunityRestriction($communityID, $targetUserID);
				} else {
					$iN->iN_SetCommunityRestriction($communityID, $targetUserID, $prevStatus, $userID);
				}
				break;
			case 'reshare_control':
				$prevReshare = (string)($previous['reshare_disabled'] ?? '0');
				$iN->iN_SetCommunityMemberControl($communityID, $targetUserID, ['reshare_disabled' => $prevReshare], $userID);
				break;
			case 'post_control':
				$prevPostDisabled = (string)($previous['post_disabled'] ?? '0');
				$iN->iN_SetCommunityMemberControl($communityID, $targetUserID, ['post_disabled' => $prevPostDisabled], $userID);
				break;
			case 'comment_control':
				$prevCommentDisabled = (string)($previous['comment_disabled'] ?? '0');
				$iN->iN_SetCommunityMemberControl($communityID, $targetUserID, ['comment_disabled' => $prevCommentDisabled], $userID);
				break;
			case 'view_timeout':
				$prevUntil = $previous['view_until'] ?? null;
				$prevPermanent = (string)($previous['view_permanent'] ?? '0') === '1' ? '1' : '0';
				$iN->iN_SetCommunityMemberControl(
					$communityID,
					$targetUserID,
					['view_until' => $prevUntil, 'view_permanent' => $prevPermanent],
					$userID
				);
				break;
			case 'post_hide':
			case 'post_unhide':
				$prevHidden = (string)($previous['is_hidden'] ?? '0');
				if ($prevHidden === '1') {
					DB::exec(
						"UPDATE community_posts SET is_hidden = '1', hidden_by = ?, hidden_at = ? WHERE community_id = ? AND post_id = ?",
						[
							$previous['hidden_by'] ?? null,
							$previous['hidden_at'] ?? null,
							$communityID,
							$targetPostID
						]
					);
				} else {
					$iN->iN_UnhideCommunityPost($communityID, $targetPostID);
				}
				break;
			case 'comment_hide':
			case 'comment_unhide':
				$prevHidden = (string)($previous['is_hidden'] ?? '0');
				if ($prevHidden === '1') {
					DB::exec(
						"UPDATE i_post_comments SET is_hidden = '1', hidden_by = ?, hidden_at = ? WHERE com_id = ?",
						[
							$previous['hidden_by'] ?? null,
							$previous['hidden_at'] ?? null,
							$targetCommentID
						]
					);
				} else {
					$iN->iN_UnhideCommunityComment($targetCommentID);
				}
				break;
			default:
				echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_revert_failed'] ?? 'Action failed.']);
				exit;
		}
		$now = date('Y-m-d H:i:s');
		DB::exec(
			"UPDATE community_moderator_actions SET reverted_by = ?, reverted_at = ? WHERE id = ?",
			[(int)$userID, $now, (int)$actionID]
		);
		echo json_encode(['status' => 'success', 'message' => $LANG['community_moderation_revert_success'] ?? 'Updated.']);
		exit;
	}
	/*Community Moderator Add*/
	if ($type == 'communityModeratorAdd') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$isAdmin = isset($userType) && (string)$userType === '2';
		if (!$isOwner && !$isAdmin) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$moderatorIDs = [];
		$rawIds = isset($_POST['moderator_user_ids']) ? (string)$iN->iN_Secure($_POST['moderator_user_ids']) : '';
		if ($rawIds !== '') {
			foreach (explode(',', $rawIds) as $rawId) {
				$rawId = trim($rawId);
				if ($rawId !== '' && ctype_digit($rawId)) {
					$moderatorIDs[] = (int)$rawId;
				}
			}
		}
		$moderatorInput = isset($_POST['moderator_user']) ? trim((string)$iN->iN_Secure($_POST['moderator_user'])) : '';
		if (empty($moderatorIDs) && $moderatorInput !== '') {
			$moderatorID = 0;
			if (ctype_digit($moderatorInput)) {
				$moderatorID = (int)$moderatorInput;
			}
			if ($moderatorID <= 0) {
				$userRow = $iN->iN_GetUserDetailsFromUsername($moderatorInput);
				$moderatorID = $userRow ? (int)$userRow['iuid'] : 0;
			}
			if ($moderatorID > 0) {
				$moderatorIDs[] = $moderatorID;
			}
		}
		$moderatorIDs = array_values(array_unique(array_filter($moderatorIDs)));
		if (empty($moderatorIDs)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$permissions = [
			'can_manage_members' => isset($_POST['perm_manage_members']) ? '1' : '0',
			'can_manage_posts' => isset($_POST['perm_manage_posts']) ? '1' : '0',
			'can_manage_comments' => isset($_POST['perm_manage_comments']) ? '1' : '0',
			'can_manage_reshare' => isset($_POST['perm_manage_reshare']) ? '1' : '0',
			'can_manage_view_timeout' => isset($_POST['perm_manage_view_timeout']) ? '1' : '0',
			'can_manage_media' => isset($_POST['perm_manage_media']) ? '1' : '0'
		];
		$assigned = 0;
		$assignedIds = [];
		$assignedRows = [];
		foreach ($moderatorIDs as $moderatorID) {
			if ($moderatorID <= 0 || !$iN->iN_CheckUserExist($moderatorID)) {
				continue;
			}
			if ((int)($communityData['owner_user_id'] ?? 0) === $moderatorID) {
				continue;
			}
			$iN->iN_SetCommunityModerator($communityID, $moderatorID, $permissions, $userID);
			$assigned++;
			$assignedIds[] = $moderatorID;
			$userRow = $iN->iN_GetUserDetails($moderatorID);
			if ($userRow) {
				$moderatorRow = [
					'user_id' => $moderatorID,
					'can_manage_members' => $permissions['can_manage_members'],
					'can_manage_posts' => $permissions['can_manage_posts'],
					'can_manage_comments' => $permissions['can_manage_comments'],
					'can_manage_reshare' => $permissions['can_manage_reshare'],
					'can_manage_view_timeout' => $permissions['can_manage_view_timeout'],
					'can_manage_media' => $permissions['can_manage_media'],
					'i_username' => $userRow['i_username'] ?? '',
					'i_user_fullname' => $userRow['i_user_fullname'] ?? ''
				];
				$moderatorId = $moderatorID;
				$moderatorName = $moderatorRow['i_user_fullname'] ?: ($moderatorRow['i_username'] ?? '');
				$moderatorAvatarUrl = $iN->iN_UserAvatar($moderatorID, $base_url);
				ob_start();
				include "../themes/$currentTheme/layouts/community/communityModeratorRow.php";
				$assignedRows[$moderatorID] = ob_get_clean();
			}
		}
		if ($assigned < 1) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		echo json_encode([
			'status' => 'success',
			'assigned_ids' => $assignedIds,
			'rows' => $assignedRows
		]);
		exit;
	}
	/*Community Moderator Remove*/
	if ($type == 'communityModeratorRemove') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['moderator_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$moderatorID = (int)$iN->iN_Secure($_POST['moderator_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$isAdmin = isset($userType) && (string)$userType === '2';
		if (!$isOwner && !$isAdmin) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		if ($moderatorID <= 0 || (int)($communityData['owner_user_id'] ?? 0) === $moderatorID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$iN->iN_RemoveCommunityModerator($communityID, $moderatorID);
		echo json_encode(['status' => 'success']);
		exit;
	}
	/*Community Moderator Update*/
	if ($type == 'communityModeratorUpdate') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['moderator_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$moderatorID = (int)$iN->iN_Secure($_POST['moderator_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$isAdmin = isset($userType) && (string)$userType === '2';
		if (!$isOwner && !$isAdmin) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$moderatorData = $iN->iN_GetCommunityModerator($communityID, $moderatorID);
		if (!$moderatorData) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$permissions = [
			'can_manage_members' => isset($_POST['perm_manage_members']) ? '1' : '0',
			'can_manage_posts' => isset($_POST['perm_manage_posts']) ? '1' : '0',
			'can_manage_comments' => isset($_POST['perm_manage_comments']) ? '1' : '0',
			'can_manage_reshare' => isset($_POST['perm_manage_reshare']) ? '1' : '0',
			'can_manage_view_timeout' => isset($_POST['perm_manage_view_timeout']) ? '1' : '0',
			'can_manage_media' => isset($_POST['perm_manage_media']) ? '1' : '0'
		];
		$iN->iN_SetCommunityModerator($communityID, $moderatorID, $permissions, $userID);
		echo json_encode(['status' => 'success']);
		exit;
	}
	/*Community Join Modal*/
	if ($type == 'communityJoinModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			exit;
		}
		$communityID = (int) $iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		if ((string)($communityData['subscription_required'] ?? '1') === '0') {
			exit;
		}
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
		if ($restrictionStatus === 'blocked') {
			exit;
		}
		if ((int)$communityData['owner_user_id'] === (int)$userID) {
			exit;
		}
		$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
		$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
		if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
			exit;
		}
		if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
			exit;
		}
		include_once __DIR__ . '/../includes/csrf.php';
		$communityPaymentScope = 'community';
		$communityOwner = $iN->iN_GetUserDetails((int)$communityData['owner_user_id']);
		include "../themes/$currentTheme/layouts/popup_alerts/communityPayment.php";
	}
	/*Community Plan Modal*/
	if ($type == 'communityPlanModal') {
		if ($logedIn != 1) {
			exit;
		}
		include_once __DIR__ . '/../includes/csrf.php';
		$communityPaymentScope = 'community_plan';
		$communityPlanAmount = ((string)$subscriptionType === '2') ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
		include "../themes/$currentTheme/layouts/popup_alerts/communityPayment.php";
	}
	/*Community Subscribe (Stripe)*/
	if ($type == 'communitySubscribe') {
		if (isset($_POST['community_id'], $_POST['name'], $_POST['email'], $_POST['t'])) {
			header('Content-Type: application/json; charset=utf-8');
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$communityID = (int)$iN->iN_Secure($_POST['community_id']);
			$subscriberName = $iN->iN_Secure($_POST['name']);
			$subscriberEmail = $iN->iN_Secure($_POST['email']);
			$stripeTokenID = $iN->iN_Secure($_POST['t']);
			$communityData = $iN->iN_GetCommunityById($communityID);
			if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
				exit($LANG['community_not_found'] ?? 'Community not found.');
			}
			if ((string)($communityData['subscription_required'] ?? '1') === '0') {
				exit($LANG['community_subscription_not_required'] ?? 'Subscription not required.');
			}
			$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
			if ($restrictionStatus === 'blocked') {
				exit($LANG['community_blocked'] ?? 'Community access blocked.');
			}
			if ((int)$communityData['owner_user_id'] === (int)$userID) {
				exit($LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.');
			}
			if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
				exit($LANG['already_subscribed'] ?? 'Already subscribed.');
			}
			$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
			$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
			if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
				exit($LANG['community_full'] ?? 'Community is full.');
			}
			$amount = (float)($communityData['monthly_price'] ?? 0);
			if ($amount <= 0) {
				exit($LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.');
			}
			if (empty($stripeTokenID) || $stripeTokenID == 'undefined') {
				exit($LANG['fill_all_credit_card_details'] ?? 'Fill all card details.');
			}
			\Stripe\Stripe::setApiKey($stripeKey);
			$api_error = '';
			try {
				$customer = \Stripe\Customer::create(array(
					'email' => $subscriberEmail,
					'source' => $stripeTokenID,
				));
			} catch (Exception $e) {
				$api_error = $e->getMessage();
			}
			if (empty($api_error) && $customer) {
				$priceCents = round($amount * 100);
				$planName = 'Community - ' . $communityData['title'];
				try {
					$plan = \Stripe\Plan::create(array(
						"product" => [
							"name" => $planName,
						],
						"amount" => $priceCents,
						"currency" => $stripeCurrency,
						"interval" => "month",
						"interval_count" => 1,
					));
				} catch (Exception $e) {
					$api_error = $e->getMessage();
				}
				if (empty($api_error) && $plan) {
					try {
						$subscription = \Stripe\Subscription::create(array(
							"customer" => $customer->id,
							"items" => array(
								array(
									"plan" => $plan->id,
								),
							),
						));
					} catch (Exception $e) {
						$api_error = $e->getMessage();
					}
					if (empty($api_error) && $subscription) {
						$subsData = $subscription->jsonSerialize();
						if ($subsData['status'] == 'active') {
							$subscrID = $subsData['id'];
							$custID = $subsData['customer'];
							$planIDs = $subsData['plan']['id'];
							$planAmount = ($subsData['plan']['amount'] / 100);
							$planCurrency = $subsData['plan']['currency'];
							$planinterval = $subsData['plan']['interval'];
							$planIntervalCount = $subsData['plan']['interval_count'];
							$plancreated = date("Y-m-d H:i:s", $subsData['created']);
							$current_period_start = date("Y-m-d H:i:s", $subsData['current_period_start']);
							$current_period_end = date("Y-m-d H:i:s", $subsData['current_period_end']);
							$planStatus = $subsData['status'];
							$adminEarning = ($adminFee * $planAmount) / 100;
							$userNetEarning = $planAmount - $adminEarning;
							$ownerID = (int)$communityData['owner_user_id'];
								$insertSubscription = $iN->iN_InsertCommunitySubscription(
								$userID,
								$communityID,
								$ownerID,
								$subscriberName,
								'stripe',
								$subscrID,
								$custID,
								$planIDs,
								$planAmount,
								$adminEarning,
								$userNetEarning,
								$planCurrency,
								$planinterval,
								$planIntervalCount,
								$subscriberEmail,
								$plancreated,
								$current_period_start,
								$current_period_end,
								$planStatus
								);
								if ($insertSubscription) {
									$orderKey = $iN->iN_GetLastSubscriptionPaymentOrderKey();
									$successUrl = $base_url . 'payment-success?payment_type=subscription';
									if ($orderKey) {
										$successUrl .= '&order_id=' . urlencode($orderKey);
									}
									echo json_encode([
										'status' => 'success',
										'order_id' => $orderKey,
										'redirect' => $successUrl
									], JSON_UNESCAPED_UNICODE);
									exit;
								} else {
									exit($LANG['something_went_wrong'] ?? 'Something went wrong.');
								}
						} else {
							exit($LANG['subscription_activation_failed'] ?? 'Subscription activation failed.');
						}
					} else {
						exit($LANG['subscription_creation_failed'] ?? 'Subscription creation failed.');
					}
				} else {
					exit($LANG['plan_creation_failed'] ?? 'Plan creation failed.');
				}
			} else {
				exit($LANG['invalid_card_details'] ?? 'Invalid card details.');
			}
		}
	}
	/*Community Plan Subscribe (Stripe)*/
	if ($type == 'communityPlanSubscribe') {
		if (isset($_POST['name'], $_POST['email'], $_POST['t'])) {
			header('Content-Type: application/json; charset=utf-8');
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$subscriberName = $iN->iN_Secure($_POST['name']);
			$subscriberEmail = $iN->iN_Secure($_POST['email']);
			$stripeTokenID = $iN->iN_Secure($_POST['t']);
			$planAmount = $subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
			if ($planAmount <= 0) {
				exit($LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.');
			}
			if (empty($stripeTokenID) || $stripeTokenID == 'undefined') {
				exit($LANG['fill_all_credit_card_details'] ?? 'Fill all card details.');
			}
			\Stripe\Stripe::setApiKey($stripeKey);
			$api_error = '';
			try {
				$customer = \Stripe\Customer::create(array(
					'email' => $subscriberEmail,
					'source' => $stripeTokenID,
				));
			} catch (Exception $e) {
				$api_error = $e->getMessage();
			}
			if (empty($api_error) && $customer) {
				$priceCents = round($planAmount * 100);
				$planName = 'Community Plan';
				try {
					$plan = \Stripe\Plan::create(array(
						"product" => [
							"name" => $planName,
						],
						"amount" => $priceCents,
						"currency" => $stripeCurrency,
						"interval" => "month",
						"interval_count" => 1,
					));
				} catch (Exception $e) {
					$api_error = $e->getMessage();
				}
				if (empty($api_error) && $plan) {
					try {
						$subscription = \Stripe\Subscription::create(array(
							"customer" => $customer->id,
							"items" => array(
								array(
									"plan" => $plan->id,
								),
							),
						));
					} catch (Exception $e) {
						$api_error = $e->getMessage();
					}
					if (empty($api_error) && $subscription) {
						$subsData = $subscription->jsonSerialize();
						if ($subsData['status'] == 'active') {
							$subscrID = $subsData['id'];
							$custID = $subsData['customer'];
							$planIDs = $subsData['plan']['id'];
							$planCurrency = $subsData['plan']['currency'];
							$planinterval = $subsData['plan']['interval'];
							$planIntervalCount = $subsData['plan']['interval_count'];
							$plancreated = date("Y-m-d H:i:s", $subsData['created']);
							$current_period_start = date("Y-m-d H:i:s", $subsData['current_period_start']);
							$current_period_end = date("Y-m-d H:i:s", $subsData['current_period_end']);
							$planStatus = $subsData['status'];
							$adminEarning = $planAmount;
								$insertSubscription = $iN->iN_InsertCommunityPlanSubscription(
								$userID,
								$subscriberName,
								'stripe',
								$subscrID,
								$custID,
								$planIDs,
								$planAmount,
								$adminEarning,
								$planCurrency,
								$planinterval,
								$planIntervalCount,
								$subscriberEmail,
								$plancreated,
								$current_period_start,
								$current_period_end,
								$planStatus
								);
								if ($insertSubscription) {
									$orderKey = $iN->iN_GetLastSubscriptionPaymentOrderKey();
									$successUrl = $base_url . 'payment-success?payment_type=subscription';
									if ($orderKey) {
										$successUrl .= '&order_id=' . urlencode($orderKey);
									}
									echo json_encode([
										'status' => 'success',
										'order_id' => $orderKey,
										'redirect' => $successUrl
									], JSON_UNESCAPED_UNICODE);
									exit;
								} else {
									exit($LANG['something_went_wrong'] ?? 'Something went wrong.');
								}
						} else {
							exit($LANG['subscription_activation_failed'] ?? 'Subscription activation failed.');
						}
					} else {
						exit($LANG['subscription_creation_failed'] ?? 'Subscription creation failed.');
					}
				} else {
					exit($LANG['plan_creation_failed'] ?? 'Plan creation failed.');
				}
			} else {
				exit($LANG['invalid_card_details'] ?? 'Invalid card details.');
			}
		}
	}
	/*Community Subscribe (CCBill)*/
	if ($type == 'communitySubscribeWithCcbill') {
		header('Content-Type: application/json');
		if ($ccbill_Status != '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['ccbill_not_available'] ?? 'CCBill is not enabled.']);
			exit;
		}
		if (!isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
			exit;
		}
		$communityID = (int) $iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
			exit;
		}
		if ((string)($communityData['subscription_required'] ?? '1') === '0') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_subscription_not_required'] ?? 'Subscription not required.']);
			exit;
		}
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
		if ($restrictionStatus === 'blocked') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_blocked'] ?? 'Community access blocked.']);
			exit;
		}
		if ((int)$communityData['owner_user_id'] === (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
			exit;
		}
		if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
			exit;
		}
		$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
		$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
		if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_full'] ?? 'Community is full.']);
			exit;
		}
		$amount = (float) ($communityData['monthly_price'] ?? 0);
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		try {
			$orderKey = bin2hex(random_bytes(8));
		} catch (\Exception $e) {
			$orderKey = uniqid('ccbill_', true);
		}
		$metadata = [
			'subscription_scope' => 'community',
			'community_id' => $communityID,
			'subscriber_id' => $userID,
			'amount' => $amount,
			'currency' => $ccbill_Currency,
			'interval' => 'month',
			'interval_days' => 30,
			'order_key' => $orderKey,
		];
		try {
			$ccbillService = new CcbillService();
			$payload = [
				'order_id' => $orderKey,
				'amount' => $amount,
				'interval_days' => 30,
				'payer_email' => $userEmail,
				'payer_name' => $userFullName,
				'metadata' => $metadata,
			];
			$response = $ccbillService->processCcbillSubscription($payload);
			$response['order_id'] = $orderKey;
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
		} catch (\Exception $e) {
			echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
		exit;
	}
	/*Community Plan Subscribe (CCBill)*/
	if ($type == 'communityPlanSubscribeWithCcbill') {
		header('Content-Type: application/json');
		if ($ccbill_Status != '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['ccbill_not_available'] ?? 'CCBill is not enabled.']);
			exit;
		}
		$amount = $subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		try {
			$orderKey = bin2hex(random_bytes(8));
		} catch (\Exception $e) {
			$orderKey = uniqid('ccbill_', true);
		}
		$metadata = [
			'subscription_scope' => 'community_plan',
			'subscriber_id' => $userID,
			'amount' => $amount,
			'currency' => $ccbill_Currency,
			'interval' => 'month',
			'interval_days' => 30,
			'order_key' => $orderKey,
		];
		try {
			$ccbillService = new CcbillService();
			$payload = [
				'order_id' => $orderKey,
				'amount' => $amount,
				'interval_days' => 30,
				'payer_email' => $userEmail,
				'payer_name' => $userFullName,
				'metadata' => $metadata,
			];
			$response = $ccbillService->processCcbillSubscription($payload);
			$response['order_id'] = $orderKey;
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
		} catch (\Exception $e) {
			echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
		exit;
	}
	/*Community Subscribe (YooKassa)*/
	if ($type == 'communitySubscribeWithYookassa') {
		header('Content-Type: application/json');
			$yookassaActive = (isset($yookassaPaymentStatus) && (int)$yookassaPaymentStatus === 1);
			$yookassaBetaFlag = isset($yookassaPaymentBeta) ? (string)$yookassaPaymentBeta : '0';
			$isAdminUser = isset($userType) && $userType == '2';
			$yookassaTestMode = !isset($yookassaPaymentMode) || (int)$yookassaPaymentMode !== 1;
			$yookassaShopId = $yookassaTestMode ? (string)($yookassaTestShopId ?? '') : (string)($yookassaLiveShopId ?? '');
			$yookassaSecretKey = $yookassaTestMode ? (string)($yookassaTestSecretKey ?? '') : (string)($yookassaLiveSecretKey ?? '');
			$yookassaKeysReady = ($yookassaShopId !== '' && $yookassaSecretKey !== '');
		if (!$yookassaActive || !$yookassaKeysReady || ($yookassaBetaFlag === '1' && !$isAdminUser)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['yookassa_not_available'] ?? 'YooKassa payment is not available.']);
			exit;
		}
		if (!isset($_POST['community_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
			exit;
		}
		$communityID = (int) $iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
			exit;
		}
		if ((string)($communityData['subscription_required'] ?? '1') === '0') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_subscription_not_required'] ?? 'Subscription not required.']);
			exit;
		}
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
		if ($restrictionStatus === 'blocked') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_blocked'] ?? 'Community access blocked.']);
			exit;
		}
		if ((int)$communityData['owner_user_id'] === (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
			exit;
		}
		if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
			exit;
		}
		$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
		$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
		if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_full'] ?? 'Community is full.']);
			exit;
		}
		$amount = (float) ($communityData['monthly_price'] ?? 0);
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		$orderKey = '';
		$idempotenceKey = '';
		try {
			$orderKey = bin2hex(random_bytes(8));
			$idempotenceKey = bin2hex(random_bytes(16));
		} catch (\Exception $e) {
			$orderKey = uniqid('yks_', true);
			$idempotenceKey = uniqid('yks_idem_', true);
		}
		$planCurrency = strtoupper((string) ($yookassaCurrency ?? 'RUB'));
		if ($planCurrency === '') {
			$planCurrency = 'RUB';
		}
		$amountFormatted = number_format($amount, 2, '.', '');
		$planInterval = 'month';
		$planIntervalCount = 1;
		$ownerID = (int) ($communityData['owner_user_id'] ?? 0);
		$subscriberName = $userFullName;
		$subscriberEmail = $userEmail;
		$time = time();
		try {
			DB::exec(
				"INSERT INTO i_user_subscription_intents (iuid_fk, subscribed_iuid_fk, subscriber_name, subscriber_email, subscription_scope, subscription_ref_id, plan_id, plan_amount, plan_amount_currency, plan_interval, plan_interval_count, tax_rate, tax_amount, order_key, payment_option, yookassa_idempotence_key, status, created_at)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
				[
					(int)$userID,
					(int)$ownerID,
					(string)$subscriberName,
					(string)$subscriberEmail,
					'community',
					(int)$communityID,
					'community_' . (int)$communityID,
					(string)$amountFormatted,
					(string)$planCurrency,
					(string)$planInterval,
					(int)$planIntervalCount,
					'0.0000',
					'0.00',
					(string)$orderKey,
					'yookassa',
					(string)$idempotenceKey,
					'pending',
					(int)$time
				]
			);
			DB::exec(
				"INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, currency)
				 VALUES (?,?,?,?,?,?,?, ?, ?)",
				[
					(int)$userID,
					(int)$ownerID,
					(string)$orderKey,
					'subscription',
					'yookassa',
					(int)$time,
					'pending',
					(string)$amountFormatted,
					(string)$planCurrency
				]
			);
		} catch (Throwable $e) {
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Something went wrong.']);
			exit;
		}
		try {
			require_once '../includes/payment/vendor/autoload.php';
			$yookassaService = new YooKassaService();
			$returnUrl = rtrim($base_url, '/') . '/communities';
			$metadata = [
				'subscription_scope' => 'community',
				'community_id' => $communityID,
				'subscriber_id' => $userID,
				'owner_id' => $ownerID,
				'plan_id' => 'community_' . (int)$communityID,
				'plan_type' => 'monthly',
				'order_key' => $orderKey
			];
			$response = $yookassaService->processYooKassaRequest([
				'order_id' => $orderKey,
				'order_amount' => $amountFormatted,
				'description' => 'Community Subscription',
				'payment_type' => 'subscription',
				'save_payment_method' => true,
				'return_url' => $returnUrl,
				'metadata' => $metadata,
				'yookassa_idempotence_key' => $idempotenceKey
			]);
		} catch (\Exception $e) {
			$response = ['status' => 'error', 'message' => $e->getMessage()];
		}
		if (!is_array($response) || empty($response['status']) || $response['status'] !== 'success') {
			DB::exec("DELETE FROM i_user_subscription_intents WHERE order_key = ? AND iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			$message = $response['message'] ?? ($LANG['something_wrong'] ?? 'Unable to start subscription.');
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		$paymentId = $response['payment_id'] ?? null;
		if (!$paymentId) {
			DB::exec("DELETE FROM i_user_subscription_intents WHERE order_key = ? AND iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Unable to start subscription.']);
			exit;
		}
		DB::exec(
			"UPDATE i_user_subscription_intents SET yookassa_payment_id = ?, yookassa_idempotence_key = ?, updated_at = ? WHERE order_key = ? AND iuid_fk = ?",
			[(string)$paymentId, (string)($response['idempotence_key'] ?? $idempotenceKey), (int)time(), (string)$orderKey, (int)$userID]
		);
		DB::exec(
			"UPDATE i_user_payments SET yookassa_payment_id = ?, yookassa_idempotence_key = ? WHERE order_key = ? AND payer_iuid_fk = ?",
			[(string)$paymentId, (string)($response['idempotence_key'] ?? $idempotenceKey), (string)$orderKey, (int)$userID]
		);
		echo json_encode([
			'status' => 'success',
			'confirmation_url' => $response['confirmation_url'] ?? '',
			'order_id' => $orderKey
		], JSON_UNESCAPED_UNICODE);
		exit;
	}
	/*Community Subscribe (Flutterwave)*/
	if ($type == 'communitySubscribeWithFlutterwave') {
		header('Content-Type: application/json');
		$flutterwaveActive = (isset($flutterWavePaymentStatus) && (int)$flutterWavePaymentStatus === 1);
		$flutterwaveKeysReady = !empty($flutterWaveLivePublicKey) || !empty($flutterWaveTestPublicKey);
		$flutterwaveBetaFlag = isset($flutterWavePaymentBeta) ? (string)$flutterWavePaymentBeta : '0';
		$isAdminUser = isset($userType) && $userType == '2';
		if (!$flutterwaveActive || !$flutterwaveKeysReady || ($flutterwaveBetaFlag === '1' && !$isAdminUser)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['flutterwave_not_available'] ?? 'Flutterwave payment is not available.']);
			exit;
		}
		if (!isset($_POST['community_id'], $_POST['tx_ref'], $_POST['transaction_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
			exit;
		}
		$communityID = (int) $iN->iN_Secure($_POST['community_id']);
		$txRef = $iN->iN_Secure($_POST['tx_ref']);
		$transactionId = $iN->iN_Secure($_POST['transaction_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
			exit;
		}
		if ((string)($communityData['subscription_required'] ?? '1') === '0') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_subscription_not_required'] ?? 'Subscription not required.']);
			exit;
		}
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
		if ($restrictionStatus === 'blocked') {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_blocked'] ?? 'Community access blocked.']);
			exit;
		}
		if ((int)$communityData['owner_user_id'] === (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
			exit;
		}
		if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
			exit;
		}
		$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
		$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
		if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_full'] ?? 'Community is full.']);
			exit;
		}
		$amount = (float) ($communityData['monthly_price'] ?? 0);
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		try {
			$flutterwaveService = new FlutterwaveService();
			$verification = $flutterwaveService->processFlutterwaveRequest([
				'flutterwaveTransactionId' => $transactionId,
				'flutterwaveTxRef' => $txRef,
				'flutterwaveAmount' => $amount
			]);
		} catch (\Exception $e) {
			$verification = ['status' => 'error', 'errorMessage' => $e->getMessage()];
		}
		if (!is_array($verification) || empty($verification['status']) || $verification['status'] !== true) {
			$message = $verification['errorMessage'] ?? ($LANG['something_wrong'] ?? 'Payment verification failed.');
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		$payment_Type = 'flutterwave';
		$plancreated = date('Y-m-d H:i:s');
		$current_period_start = $plancreated;
		$current_period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
		$planCurrency = $flutterWaveCurrency ?? ($defaultCurrency ?? 'USD');
		$planStatus = 'active';
		$planIDs = 'flutterwave_community_' . $communityID;
		$subscrID = 'flw_' . $transactionId;
		$custID = 'flw_user_' . $userID;
		$adminEarning = ($adminFee * $amount) / 100;
		$userNetEarning = $amount - $adminEarning;
		$subscriberName = $userFullName;
		$subscriberEmail = $userEmail;
		$ownerID = (int)$communityData['owner_user_id'];
		$insertSubscription = $iN->iN_InsertCommunitySubscription(
			$userID,
			$communityID,
			$ownerID,
			$subscriberName,
			$payment_Type,
			$subscrID,
			$custID,
			$planIDs,
			$amount,
			$adminEarning,
			$userNetEarning,
			$planCurrency,
			'month',
			1,
			$subscriberEmail,
			$plancreated,
			$current_period_start,
			$current_period_end,
			$planStatus
		);
			if ($insertSubscription) {
				$orderKey = $iN->iN_GetLastSubscriptionPaymentOrderKey();
				$successUrl = $base_url . 'payment-success?payment_type=subscription';
				if ($orderKey) {
					$successUrl .= '&order_id=' . urlencode($orderKey);
				}
				echo json_encode([
					'status' => 'success',
					'order_id' => $orderKey,
					'redirect' => $successUrl
				], JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Unable to activate subscription.']);
			}
		exit;
	}
	/*Community Plan Subscribe (Flutterwave)*/
	if ($type == 'communityPlanSubscribeWithFlutterwave') {
		header('Content-Type: application/json');
		$flutterwaveActive = (isset($flutterWavePaymentStatus) && (int)$flutterWavePaymentStatus === 1);
		$flutterwaveKeysReady = !empty($flutterWaveLivePublicKey) || !empty($flutterWaveTestPublicKey);
		$flutterwaveBetaFlag = isset($flutterWavePaymentBeta) ? (string)$flutterWavePaymentBeta : '0';
		$isAdminUser = isset($userType) && $userType == '2';
		if (!$flutterwaveActive || !$flutterwaveKeysReady || ($flutterwaveBetaFlag === '1' && !$isAdminUser)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['flutterwave_not_available'] ?? 'Flutterwave payment is not available.']);
			exit;
		}
		if (!isset($_POST['tx_ref'], $_POST['transaction_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
			exit;
		}
		$txRef = $iN->iN_Secure($_POST['tx_ref']);
		$transactionId = $iN->iN_Secure($_POST['transaction_id']);
		$amount = $subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		try {
			$flutterwaveService = new FlutterwaveService();
			$verification = $flutterwaveService->processFlutterwaveRequest([
				'flutterwaveTransactionId' => $transactionId,
				'flutterwaveTxRef' => $txRef,
				'flutterwaveAmount' => $amount
			]);
		} catch (\Exception $e) {
			$verification = ['status' => 'error', 'errorMessage' => $e->getMessage()];
		}
		if (!is_array($verification) || empty($verification['status']) || $verification['status'] !== true) {
			$message = $verification['errorMessage'] ?? ($LANG['something_wrong'] ?? 'Payment verification failed.');
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		$payment_Type = 'flutterwave';
		$plancreated = date('Y-m-d H:i:s');
		$current_period_start = $plancreated;
		$current_period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
		$planCurrency = $flutterWaveCurrency ?? ($defaultCurrency ?? 'USD');
		$planStatus = 'active';
		$planIDs = 'flutterwave_community_plan';
		$subscrID = 'flw_' . $transactionId;
		$custID = 'flw_user_' . $userID;
		$adminEarning = $amount;
		$subscriberName = $userFullName;
		$subscriberEmail = $userEmail;
		$insertSubscription = $iN->iN_InsertCommunityPlanSubscription(
			$userID,
			$subscriberName,
			$payment_Type,
			$subscrID,
			$custID,
			$planIDs,
			$amount,
			$adminEarning,
			$planCurrency,
			'month',
			1,
			$subscriberEmail,
			$plancreated,
			$current_period_start,
			$current_period_end,
			$planStatus
		);
			if ($insertSubscription) {
				$orderKey = $iN->iN_GetLastSubscriptionPaymentOrderKey();
				$successUrl = $base_url . 'payment-success?payment_type=subscription';
				if ($orderKey) {
					$successUrl .= '&order_id=' . urlencode($orderKey);
				}
				echo json_encode([
					'status' => 'success',
					'order_id' => $orderKey,
					'redirect' => $successUrl
				], JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Unable to activate subscription.']);
			}
		exit;
	}
	/*Community Subscribe (Points)*/
	if ($type == 'communitySubscribeWithPoints') {
		if (isset($_POST['community_id'])) {
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$communityID = (int)$iN->iN_Secure($_POST['community_id']);
			$communityData = $iN->iN_GetCommunityById($communityID);
			if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
				exit($LANG['community_not_found'] ?? 'Community not found.');
			}
			if ((string)($communityData['subscription_required'] ?? '1') === '0') {
				exit($LANG['community_subscription_not_required'] ?? 'Subscription not required.');
			}
			$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
			if ($restrictionStatus === 'blocked') {
				exit($LANG['community_blocked'] ?? 'Community access blocked.');
			}
			if ((int)$communityData['owner_user_id'] === (int)$userID) {
				exit($LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.');
			}
			if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
				exit($LANG['already_subscribed'] ?? 'Already subscribed.');
			}
			$memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
			$currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
			if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
				exit($LANG['community_full'] ?? 'Community is full.');
			}
			$planAmount = (float)($communityData['monthly_price'] ?? 0);
			$pointValue = (float)str_replace(',', '.', (string)$onePointEqual);
			$pointAmount = $planAmount;
			if ((string)$subscriptionType !== '2') {
				if ($pointValue <= 0) {
					exit('404');
				}
				$pointAmount = (int)ceil($planAmount / $pointValue);
			}
			if ($pointAmount <= 0 || (float)$userCurrentPoints < (float)$pointAmount) {
				exit('302');
			}
			$adminEarning = $adminFee * $pointAmount * $pointValue / 100;
			$userNetEarning = $pointAmount * $pointValue - $adminEarning;
			$planInterval = 'month';
			$planIntervalCount = '1';
			$plancreated = date("Y-m-d H:i:s");
			$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
			$current_period_end = date("Y-m-d H:i:s", strtotime('+1 month', time()));
			$subscriberName = $userFullName;
			$subscriberEmail = $userEmail;
			$planCurrency = $defaultCurrency;
			$planStatus = 'active';
			$ownerID = (int)$communityData['owner_user_id'];
				$insertSubscription = $iN->iN_InsertCommunitySubscriptionWithPoint(
				$userID,
				$communityID,
				$ownerID,
				$subscriberName,
				$pointAmount,
				$adminEarning,
				$userNetEarning,
				$planCurrency,
				$planInterval,
				$planIntervalCount,
				$subscriberEmail,
				$plancreated,
				$current_period_start,
				$current_period_end,
				$planStatus
				);
				if ($insertSubscription) {
					$orderKey = $iN->iN_GetLastSubscriptionPaymentOrderKey();
					$successUrl = $base_url . 'payment-success?payment_type=subscription';
					if ($orderKey) {
						$successUrl .= '&order_id=' . urlencode($orderKey);
					}
					echo json_encode([
						'status' => 'success',
						'order_id' => $orderKey,
						'redirect' => $successUrl
					], JSON_UNESCAPED_UNICODE);
					exit;
				} else {
					exit('404');
				}
		}
	}
	/*Community Plan Subscribe (Points)*/
	if ($type == 'communityPlanSubscribeWithPoints') {
		if ($logedIn != 1) {
			exit;
		}
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$planAmount = (string)$subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
		$pointValue = (float)str_replace(',', '.', (string)$onePointEqual);
		$pointAmount = $planAmount;
		if ((string)$subscriptionType !== '2') {
			if ($pointValue <= 0) {
				exit('404');
			}
			$pointAmount = (int)ceil($planAmount / $pointValue);
		}
		if ($pointAmount <= 0 || (float)$userCurrentPoints < (float)$pointAmount) {
			exit('302');
		}
		$planInterval = 'month';
		$planIntervalCount = '1';
		$plancreated = date("Y-m-d H:i:s");
		$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
		$current_period_end = date("Y-m-d H:i:s", strtotime('+1 month', time()));
		$planCurrency = $defaultCurrency;
		$planStatus = 'active';
		$adminEarning = $pointAmount * $pointValue;
		try {
			DB::begin();
			DB::exec("UPDATE i_users SET wallet_points = wallet_points - ? WHERE iuid = ?", [(string)$pointAmount, (int)$userID]);
			$insertSubscription = $iN->iN_InsertCommunityPlanSubscription(
				$userID,
				$userFullName,
				'point',
				'point_' . time(),
				'point_' . $userID,
				'point_community_plan',
				$pointAmount,
				$adminEarning,
				$planCurrency,
				$planInterval,
				$planIntervalCount,
				$userEmail,
				$plancreated,
				$current_period_start,
				$current_period_end,
				$planStatus
			);
				if (!$insertSubscription) {
					throw new Exception('subscription_failed');
				}
				DB::commit();
				$orderKey = $iN->iN_GetLastSubscriptionPaymentOrderKey();
				$successUrl = $base_url . 'payment-success?payment_type=subscription';
				if ($orderKey) {
					$successUrl .= '&order_id=' . urlencode($orderKey);
				}
					echo json_encode([
						'status' => 'success',
						'order_id' => $orderKey,
						'redirect' => $successUrl
					], JSON_UNESCAPED_UNICODE);
					exit;
				} catch (Throwable $e) {
					DB::rollBack();
					exit('404');
		}
	}
	if ($type == 'creditPoint') {
		if (isset($_POST['plan']) && isset($_POST['id'])) {
			$planID = $iN->iN_Secure($_POST['plan']);
			$iuID = $iN->iN_Secure($_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			if ($checkPlanExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$planType = $checkPlanExist['plan_type'];
				$f_userID = $userDetail['iuid'];
				$f_PlanAmount = $checkPlanExist['amount'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;

				include "../themes/$currentTheme/layouts/popup_alerts/payWithPoint.php";
			}
		}
	}
    if ($type == 'subscribeWithEpoch') {
        header('Content-Type: application/json');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if (!rq_epoch_subscription_gateway_ready()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['epoch_not_available'] ?? ($LANG['no_payment_method_available'] ?? 'No payment method available.')]);
            exit;
        }
        if (!isset($_POST['u'], $_POST['pl'])) {
            echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
            exit;
        }
        $creatorID = (int) $iN->iN_Secure($_POST['u']);
        $planID = (int) $iN->iN_Secure($_POST['pl']);
        if ($creatorID <= 0 || $planID <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
            exit;
        }
        if ($creatorID == (int) $userID) {
            echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
            exit;
        }
        $planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
        if (!$planDetails) {
            echo json_encode(['status' => 'error', 'message' => $LANG['plan_not_found'] ?? 'Subscription plan not found.']);
            exit;
        }
        $relationStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $creatorID);
        if ($relationStatus === 'subscriber') {
            echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
            exit;
        }
        $planType = (string)($planDetails['plan_type'] ?? 'monthly');
        $amount = (float) $planDetails['amount'];
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        $period = rq_subscription_period_from_plan_type($planType);
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $orderKey = uniqid('epoch_sub_', true);
        }
        $response = rq_start_epoch_subscription_checkout([
            'subscriber_id' => (int)$userID,
            'subscribed_id' => (int)$creatorID,
            'scope' => 'profile',
            'scope_ref_id' => null,
            'plan_id' => (string)$planID,
            'plan_interval' => (string)$period['interval'],
            'plan_interval_count' => (int)$period['interval_count'],
            'order_key' => (string)$orderKey,
            'subscriber_name' => (string)$userFullName,
            'subscriber_email' => (string)$userEmail,
            'amount' => (float)$amount,
        ]);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($type == 'communitySubscribeWithEpoch') {
        header('Content-Type: application/json');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if (!rq_epoch_subscription_gateway_ready()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['epoch_not_available'] ?? ($LANG['no_payment_method_available'] ?? 'No payment method available.')]);
            exit;
        }
        if (!isset($_POST['community_id'])) {
            echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
            exit;
        }
        $communityID = (int) $iN->iN_Secure($_POST['community_id']);
        $communityData = $iN->iN_GetCommunityById($communityID);
        if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
            exit;
        }
        if ((string)($communityData['subscription_required'] ?? '1') === '0') {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_subscription_not_required'] ?? 'Subscription not required.']);
            exit;
        }
        $restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
        if ($restrictionStatus === 'blocked') {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_blocked'] ?? 'Community access blocked.']);
            exit;
        }
        if ((int)$communityData['owner_user_id'] === (int)$userID) {
            echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
            exit;
        }
        if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
            echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
            exit;
        }
        $memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
        $currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
        if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_full'] ?? 'Community is full.']);
            exit;
        }
        $amount = (float)($communityData['monthly_price'] ?? 0);
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        $ownerID = (int)($communityData['owner_user_id'] ?? 0);
        if ($ownerID <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
            exit;
        }
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $orderKey = uniqid('epoch_com_sub_', true);
        }
        $response = rq_start_epoch_subscription_checkout([
            'subscriber_id' => (int)$userID,
            'subscribed_id' => (int)$ownerID,
            'scope' => 'community',
            'scope_ref_id' => (int)$communityID,
            'plan_id' => 'community_' . (int)$communityID,
            'plan_interval' => 'month',
            'plan_interval_count' => 1,
            'order_key' => (string)$orderKey,
            'subscriber_name' => (string)$userFullName,
            'subscriber_email' => (string)$userEmail,
            'amount' => (float)$amount,
        ]);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($type == 'communityPlanSubscribeWithEpoch') {
        header('Content-Type: application/json');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if (!rq_epoch_subscription_gateway_ready()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['epoch_not_available'] ?? ($LANG['no_payment_method_available'] ?? 'No payment method available.')]);
            exit;
        }
        $amount = $subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            $orderKey = uniqid('epoch_com_plan_', true);
        }
        $response = rq_start_epoch_subscription_checkout([
            'subscriber_id' => (int)$userID,
            'subscribed_id' => (int)$userID,
            'scope' => 'community_plan',
            'scope_ref_id' => null,
            'plan_id' => 'community_plan',
            'plan_interval' => 'month',
            'plan_interval_count' => 1,
            'order_key' => (string)$orderKey,
            'subscriber_name' => (string)$userFullName,
            'subscriber_email' => (string)$userEmail,
            'amount' => (float)$amount,
        ]);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($type == 'subscribeWithIyzico') {
        header('Content-Type: application/json');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if (!rq_iyzico_subscription_gateway_ready()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['no_payment_method_available'] ?? 'No payment method available.']);
            exit;
        }
        if (!isset($_POST['u'], $_POST['pl'], $_POST['cardname'], $_POST['cardnumber'], $_POST['expmonth'], $_POST['expyear'], $_POST['cvv'])) {
            echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
            exit;
        }
        $creatorID = (int) $iN->iN_Secure($_POST['u']);
        $planID = (int) $iN->iN_Secure($_POST['pl']);
        if ($creatorID <= 0 || $planID <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
            exit;
        }
        if ($creatorID == (int) $userID) {
            echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
            exit;
        }
        $planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
        if (!$planDetails) {
            echo json_encode(['status' => 'error', 'message' => $LANG['plan_not_found'] ?? 'Subscription plan not found.']);
            exit;
        }
        $relationStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $creatorID);
        if ($relationStatus === 'subscriber') {
            echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
            exit;
        }
        $planType = (string)($planDetails['plan_type'] ?? 'monthly');
        $amount = (float) $planDetails['amount'];
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        $period = rq_subscription_period_from_plan_type($planType);
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $orderKey = uniqid('iyzico_sub_', true);
        }
        $response = rq_start_iyzico_subscription_checkout(
            [
                'subscriber_id' => (int)$userID,
                'subscribed_id' => (int)$creatorID,
                'scope' => 'profile',
                'scope_ref_id' => null,
                'plan_id' => (string)$planID,
                'plan_interval' => (string)$period['interval'],
                'plan_interval_count' => (int)$period['interval_count'],
                'order_key' => (string)$orderKey,
                'subscriber_name' => (string)$userFullName,
                'subscriber_email' => (string)$userEmail,
                'amount' => (float)$amount,
                'item_id' => 'SUB_PROFILE_' . $planID,
            ],
            [
                'cardname' => $iN->iN_Secure($_POST['cardname']),
                'cardnumber' => $iN->iN_Secure($_POST['cardnumber']),
                'expmonth' => $iN->iN_Secure($_POST['expmonth']),
                'expyear' => $iN->iN_Secure($_POST['expyear']),
                'cvv' => $iN->iN_Secure($_POST['cvv']),
            ]
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($type == 'communitySubscribeWithIyzico') {
        header('Content-Type: application/json');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if (!rq_iyzico_subscription_gateway_ready()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['no_payment_method_available'] ?? 'No payment method available.']);
            exit;
        }
        if (!isset($_POST['community_id'], $_POST['cardname'], $_POST['cardnumber'], $_POST['expmonth'], $_POST['expyear'], $_POST['cvv'])) {
            echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
            exit;
        }
        $communityID = (int) $iN->iN_Secure($_POST['community_id']);
        $communityData = $iN->iN_GetCommunityById($communityID);
        if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_not_found'] ?? 'Community not found.']);
            exit;
        }
        if ((string)($communityData['subscription_required'] ?? '1') === '0') {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_subscription_not_required'] ?? 'Subscription not required.']);
            exit;
        }
        $restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
        if ($restrictionStatus === 'blocked') {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_blocked'] ?? 'Community access blocked.']);
            exit;
        }
        if ((int)$communityData['owner_user_id'] === (int)$userID) {
            echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
            exit;
        }
        if ($iN->iN_IsCommunityAccessMember($communityData, $userID)) {
            echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
            exit;
        }
        $memberLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
        $currentMembers = $iN->iN_GetCommunityMembersCount($communityID, 'active');
        if ($memberLimit > 0 && $currentMembers >= $memberLimit) {
            echo json_encode(['status' => 'error', 'message' => $LANG['community_full'] ?? 'Community is full.']);
            exit;
        }
        $amount = (float) ($communityData['monthly_price'] ?? 0);
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        $ownerID = (int)($communityData['owner_user_id'] ?? 0);
        if ($ownerID <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
            exit;
        }
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $orderKey = uniqid('iyzico_com_sub_', true);
        }
        $response = rq_start_iyzico_subscription_checkout(
            [
                'subscriber_id' => (int)$userID,
                'subscribed_id' => (int)$ownerID,
                'scope' => 'community',
                'scope_ref_id' => (int)$communityID,
                'plan_id' => 'community_' . (int)$communityID,
                'plan_interval' => 'month',
                'plan_interval_count' => 1,
                'order_key' => (string)$orderKey,
                'subscriber_name' => (string)$userFullName,
                'subscriber_email' => (string)$userEmail,
                'amount' => (float)$amount,
                'item_id' => 'SUB_COMMUNITY_' . (int)$communityID,
            ],
            [
                'cardname' => $iN->iN_Secure($_POST['cardname']),
                'cardnumber' => $iN->iN_Secure($_POST['cardnumber']),
                'expmonth' => $iN->iN_Secure($_POST['expmonth']),
                'expyear' => $iN->iN_Secure($_POST['expyear']),
                'cvv' => $iN->iN_Secure($_POST['cvv']),
            ]
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($type == 'communityPlanSubscribeWithIyzico') {
        header('Content-Type: application/json');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if (!rq_iyzico_subscription_gateway_ready()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['no_payment_method_available'] ?? 'No payment method available.']);
            exit;
        }
        if (!isset($_POST['cardname'], $_POST['cardnumber'], $_POST['expmonth'], $_POST['expyear'], $_POST['cvv'])) {
            echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
            exit;
        }
        $amount = $subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $orderKey = uniqid('iyzico_com_plan_', true);
        }
        $response = rq_start_iyzico_subscription_checkout(
            [
                'subscriber_id' => (int)$userID,
                'subscribed_id' => (int)$userID,
                'scope' => 'community_plan',
                'scope_ref_id' => null,
                'plan_id' => 'community_plan',
                'plan_interval' => 'month',
                'plan_interval_count' => 1,
                'order_key' => (string)$orderKey,
                'subscriber_name' => (string)$userFullName,
                'subscriber_email' => (string)$userEmail,
                'amount' => (float)$amount,
                'item_id' => 'SUB_COMMUNITY_PLAN',
            ],
            [
                'cardname' => $iN->iN_Secure($_POST['cardname']),
                'cardnumber' => $iN->iN_Secure($_POST['cardnumber']),
                'expmonth' => $iN->iN_Secure($_POST['expmonth']),
                'expyear' => $iN->iN_Secure($_POST['expyear']),
                'cvv' => $iN->iN_Secure($_POST['cvv']),
            ]
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($type == 'subscribeWithCcbill') {
        header('Content-Type: application/json');
        if ($ccbill_Status != '1') {
            echo json_encode(['status' => 'error', 'message' => $LANG['ccbill_not_available'] ?? 'CCBill is not enabled.']);
            exit;
        }
        if (!isset($_POST['u'], $_POST['pl'])) {
            echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
            exit;
        }
        $creatorID = (int) $iN->iN_Secure($_POST['u']);
        $planID = (int) $iN->iN_Secure($_POST['pl']);
        if ($creatorID <= 0 || $planID <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
            exit;
        }
        if ($creatorID == (int) $userID) {
            echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
            exit;
        }
        $planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
        if (!$planDetails) {
            echo json_encode(['status' => 'error', 'message' => $LANG['plan_not_found'] ?? 'Subscription plan not found.']);
            exit;
        }
        $relationStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $creatorID);
        if ($relationStatus === 'subscriber') {
            echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
            exit;
        }
        $planType = $planDetails['plan_type'];
        $amount = (float) $planDetails['amount'];
        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
            exit;
        }
        $intervalDays = 0;
        $planInterval = '';
        if ($planType === 'weekly') {
            $intervalDays = 7;
            $planInterval = 'week';
        } else if ($planType === 'monthly') {
            $intervalDays = 30;
            $planInterval = 'month';
        } else if ($planType === 'yearly') {
            $intervalDays = 365;
            $planInterval = 'year';
        } else {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_plan'] ?? 'Unsupported subscription plan type.']);
            exit;
        }
        try {
            $orderKey = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $orderKey = uniqid('ccbill_', true);
        }
        $metadata = [
            'plan_id'        => $planID,
            'creator_id'     => $creatorID,
            'subscriber_id'  => $userID,
            'amount'         => $amount,
            'currency'       => $ccbill_Currency,
            'interval'       => $planInterval,
            'interval_days'  => $intervalDays,
            'plan_type'      => $planType,
            'order_key'      => $orderKey,
        ];
        try {
            $ccbillService = new CcbillService();
            $payload = [
                'order_id'      => $orderKey,
                'amount'        => $amount,
                'interval_days' => $intervalDays,
                'payer_email'   => $userEmail,
                'payer_name'    => $userFullName,
                'metadata'      => $metadata,
            ];
            $response = $ccbillService->processCcbillSubscription($payload);
            $response['order_id'] = $orderKey;
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
	if ($type == 'subscribeWithYookassa') {
		header('Content-Type: application/json');
			$yookassaActive = (isset($yookassaPaymentStatus) && (int)$yookassaPaymentStatus === 1);
			$yookassaBetaFlag = isset($yookassaPaymentBeta) ? (string)$yookassaPaymentBeta : '0';
			$isAdminUser = isset($userType) && $userType == '2';
			$yookassaTestMode = !isset($yookassaPaymentMode) || (int)$yookassaPaymentMode !== 1;
			$yookassaShopId = $yookassaTestMode ? (string)($yookassaTestShopId ?? '') : (string)($yookassaLiveShopId ?? '');
			$yookassaSecretKey = $yookassaTestMode ? (string)($yookassaTestSecretKey ?? '') : (string)($yookassaLiveSecretKey ?? '');
			$yookassaKeysReady = ($yookassaShopId !== '' && $yookassaSecretKey !== '');
		if (!$yookassaActive || !$yookassaKeysReady || ($yookassaBetaFlag === '1' && !$isAdminUser)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['yookassa_not_available'] ?? 'YooKassa payment is not available.']);
			exit;
		}
		if (!isset($_POST['u'], $_POST['pl'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
			exit;
		}
		$creatorID = (int) $iN->iN_Secure($_POST['u']);
		$planID = (int) $iN->iN_Secure($_POST['pl']);
		if ($creatorID <= 0 || $planID <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
			exit;
		}
		if ($creatorID == (int) $userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
			exit;
		}
		$planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
		if (!$planDetails) {
			echo json_encode(['status' => 'error', 'message' => $LANG['plan_not_found'] ?? 'Subscription plan not found.']);
			exit;
		}
		$relationStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $creatorID);
		if ($relationStatus === 'subscriber') {
			echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
			exit;
		}
		$planType = $planDetails['plan_type'];
		$amount = (float) $planDetails['amount'];
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		$planInterval = '';
		$planIntervalCount = 1;
		if ($planType === 'weekly') {
			$planInterval = 'week';
		} else if ($planType === 'monthly') {
			$planInterval = 'month';
		} else if ($planType === 'yearly') {
			$planInterval = 'year';
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_plan'] ?? 'Unsupported subscription plan type.']);
			exit;
		}
		$orderKey = '';
		$idempotenceKey = '';
		try {
			$orderKey = bin2hex(random_bytes(8));
			$idempotenceKey = bin2hex(random_bytes(16));
		} catch (\Exception $e) {
			$orderKey = uniqid('yks_', true);
			$idempotenceKey = uniqid('yks_idem_', true);
		}
		$planCurrency = strtoupper((string) ($yookassaCurrency ?? 'RUB'));
		if ($planCurrency === '') {
			$planCurrency = 'RUB';
		}
		$amountFormatted = number_format($amount, 2, '.', '');
		$subscriberName = $userFullName;
		$subscriberEmail = $userEmail;
		$time = time();
		try {
			DB::exec(
				"INSERT INTO i_user_subscription_intents (iuid_fk, subscribed_iuid_fk, subscriber_name, subscriber_email, subscription_scope, subscription_ref_id, plan_id, plan_amount, plan_amount_currency, plan_interval, plan_interval_count, tax_rate, tax_amount, order_key, payment_option, yookassa_idempotence_key, status, created_at)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
				[
					(int)$userID,
					(int)$creatorID,
					(string)$subscriberName,
					(string)$subscriberEmail,
					'profile',
					null,
					(string)$planID,
					(string)$amountFormatted,
					(string)$planCurrency,
					(string)$planInterval,
					(int)$planIntervalCount,
					'0.0000',
					'0.00',
					(string)$orderKey,
					'yookassa',
					(string)$idempotenceKey,
					'pending',
					(int)$time
				]
			);
			DB::exec(
				"INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, currency)
				 VALUES (?,?,?,?,?,?,?, ?, ?)",
				[
					(int)$userID,
					(int)$creatorID,
					(string)$orderKey,
					'subscription',
					'yookassa',
					(int)$time,
					'pending',
					(string)$amountFormatted,
					(string)$planCurrency
				]
			);
		} catch (Throwable $e) {
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Something went wrong.']);
			exit;
		}
		try {
			require_once '../includes/payment/vendor/autoload.php';
			$yookassaService = new YooKassaService();
			$returnUrl = rtrim($base_url, '/') . '/settings?tab=subscriptions';
			$metadata = [
				'subscription_scope' => 'profile',
				'plan_id' => $planID,
				'plan_type' => $planType,
				'creator_id' => $creatorID,
				'subscriber_id' => $userID,
				'order_key' => $orderKey
			];
			$response = $yookassaService->processYooKassaRequest([
				'order_id' => $orderKey,
				'order_amount' => $amountFormatted,
				'description' => 'Subscription',
				'payment_type' => 'subscription',
				'save_payment_method' => true,
				'return_url' => $returnUrl,
				'metadata' => $metadata,
				'yookassa_idempotence_key' => $idempotenceKey
			]);
		} catch (\Exception $e) {
			$response = ['status' => 'error', 'message' => $e->getMessage()];
		}
		if (!is_array($response) || empty($response['status']) || $response['status'] !== 'success') {
			DB::exec("DELETE FROM i_user_subscription_intents WHERE order_key = ? AND iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			$message = $response['message'] ?? ($LANG['something_wrong'] ?? 'Unable to start subscription.');
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		$paymentId = $response['payment_id'] ?? null;
		if (!$paymentId) {
			DB::exec("DELETE FROM i_user_subscription_intents WHERE order_key = ? AND iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$orderKey, (int)$userID]);
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Unable to start subscription.']);
			exit;
		}
		DB::exec(
			"UPDATE i_user_subscription_intents SET yookassa_payment_id = ?, yookassa_idempotence_key = ?, updated_at = ? WHERE order_key = ? AND iuid_fk = ?",
			[(string)$paymentId, (string)($response['idempotence_key'] ?? $idempotenceKey), (int)time(), (string)$orderKey, (int)$userID]
		);
		DB::exec(
			"UPDATE i_user_payments SET yookassa_payment_id = ?, yookassa_idempotence_key = ? WHERE order_key = ? AND payer_iuid_fk = ?",
			[(string)$paymentId, (string)($response['idempotence_key'] ?? $idempotenceKey), (string)$orderKey, (int)$userID]
		);
		echo json_encode([
			'status' => 'success',
			'confirmation_url' => $response['confirmation_url'] ?? '',
			'order_id' => $orderKey
		], JSON_UNESCAPED_UNICODE);
		exit;
	}
	if ($type == 'subscribeWithFlutterwave') {
		header('Content-Type: application/json');
		$flutterwaveActive = (isset($flutterWavePaymentStatus) && (int)$flutterWavePaymentStatus === 1);
		$flutterwaveKeysReady = !empty($flutterWaveLivePublicKey) || !empty($flutterWaveTestPublicKey);
		$flutterwaveBetaFlag = isset($flutterWavePaymentBeta) ? (string)$flutterWavePaymentBeta : '0';
		$isAdminUser = isset($userType) && $userType == '2';
		if (!$flutterwaveActive || !$flutterwaveKeysReady || ($flutterwaveBetaFlag === '1' && !$isAdminUser)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['flutterwave_not_available'] ?? 'Flutterwave payment is not available.']);
			exit;
		}
		if (!isset($_POST['u'], $_POST['pl'], $_POST['tx_ref'], $_POST['transaction_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['missing_subscription_details'] ?? 'Missing subscription details.']);
			exit;
		}
		$creatorID = (int) $iN->iN_Secure($_POST['u']);
		$planID = (int) $iN->iN_Secure($_POST['pl']);
		$txRef = $iN->iN_Secure($_POST['tx_ref']);
		$transactionId = $iN->iN_Secure($_POST['transaction_id']);
		if ($creatorID <= 0 || $planID <= 0 || empty($txRef) || empty($transactionId)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_request'] ?? 'Invalid subscription request.']);
			exit;
		}
		if ($creatorID == (int) $userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['cant_subscribe_self'] ?? 'You cannot subscribe to yourself.']);
			exit;
		}
		$planDetails = $iN->iN_CheckPlanExist($planID, $creatorID);
		if (!$planDetails) {
			echo json_encode(['status' => 'error', 'message' => $LANG['plan_not_found'] ?? 'Subscription plan not found.']);
			exit;
		}
		$relationStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $creatorID);
		if ($relationStatus === 'subscriber') {
			echo json_encode(['status' => 'error', 'message' => $LANG['already_subscribed'] ?? 'Already subscribed.']);
			exit;
		}
		$planType = $planDetails['plan_type'];
		$amount = (float) $planDetails['amount'];
		if ($amount <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_plan_amount'] ?? 'Invalid subscription amount.']);
			exit;
		}
		$planInterval = '';
		$planIntervalCount = 1;
		$current_period_end = null;
		if ($planType === 'weekly') {
			$planInterval = 'week';
			$current_period_end = date('Y-m-d H:i:s', strtotime('+7 days'));
		} else if ($planType === 'monthly') {
			$planInterval = 'month';
			$current_period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
		} else if ($planType === 'yearly') {
			$planInterval = 'year';
			$current_period_end = date('Y-m-d H:i:s', strtotime('+1 year'));
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_subscription_plan'] ?? 'Unsupported subscription plan type.']);
			exit;
		}
		try {
			$flutterwaveService = new FlutterwaveService();
			$verification = $flutterwaveService->processFlutterwaveRequest([
				'flutterwaveTransactionId' => $transactionId,
				'flutterwaveTxRef' => $txRef,
				'flutterwaveAmount' => $amount
			]);
		} catch (\Exception $e) {
			$verification = ['status' => 'error', 'errorMessage' => $e->getMessage()];
		}
		if (!is_array($verification) || empty($verification['status']) || $verification['status'] !== true) {
			$message = $verification['errorMessage'] ?? ($LANG['something_wrong'] ?? 'Payment verification failed.');
			echo json_encode(['status' => 'error', 'message' => $message]);
			exit;
		}
		$payment_Type = 'flutterwave';
		$plancreated = date('Y-m-d H:i:s');
		$current_period_start = $plancreated;
		$planCurrency = $flutterWaveCurrency ?? ($defaultCurrency ?? 'USD');
		$planStatus = 'active';
		$planIDs = 'flutterwave_' . $planID;
		$subscrID = 'flw_' . $transactionId;
		$custID = 'flw_user_' . $userID;
		$adminEarning = ($adminFee * $amount) / 100;
		$userNetEarning = $amount - $adminEarning;
		$subscriberName = $userFullName;
		$subscriberEmail = $userEmail;
		$insertSubscription = $iN->iN_InsertUserSubscription(
			$userID,
			$creatorID,
			$payment_Type,
			$subscriberName,
			$subscrID,
			$custID,
			$planIDs,
			$amount,
			$adminEarning,
			$userNetEarning,
			$planCurrency,
			$planInterval,
			$planIntervalCount,
			$subscriberEmail,
			$plancreated,
			$current_period_start,
			$current_period_end,
			$planStatus
		);
		if ($insertSubscription) {
			$iN->iN_InsertNotificationForSubscribe($userID, $creatorID);
			if ((string)$webPushStatus === '1' && (string)$webPushEventSubscription === '1' && (int)$creatorID !== (int)$userID) {
				$actorName = trim((string)$userFullName) !== '' ? (string)$userFullName : (string)$userName;
				$pushTitle = $iN->iN_Secure($LANG['got_new_subscriber'] ?? 'You got new subscriber');
				$pushBody = $iN->iN_Secure($actorName . ' ' . ($LANG['is_subscribed_your_profile'] ?? 'is now subscribing your profile'));
				$iN->iN_SendWebPushToUser((int)$creatorID, [
					'title' => $pushTitle,
					'body' => $pushBody,
					'url' => $base_url . 'settings?tab=subscribers',
					'tag' => 'subscription_' . (int)$userID,
				]);
			}
			$uData = $iN->iN_GetUserDetails($creatorID);
			$sendEmail = $uData['i_user_email'] ?? null;
			$emailNotificationStatus = $uData['email_notification_status'] ?? null;
			if ($emailSendStatus == '1' && $emailNotificationStatus == '1' && $sendEmail) {
				$gotNewSubscriber = $LANG['got_new_subscriber'] ?? 'You have a new subscriber';
				try {
					if ($smtpOrMail == 'mail') {
						$mail->IsMail();
					} else if ($smtpOrMail == 'smtp') {
						$mail->isSMTP();
						$mail->Host = $smtpHost;
						$mail->SMTPAuth = true;
						$mail->SMTPKeepAlive = true;
						$mail->Username = $smtpUserName;
						$mail->Password = $smtpPassword;
						$mail->SMTPSecure = $smtpEncryption;
						$mail->Port = $smtpPort;
						$mail->SMTPOptions = array(
							'ssl' => array(
								'verify_peer' => false,
								'verify_peer_name' => false,
								'allow_self_signed' => true,
							),
						);
					}
					$instagramIcon = $iN->iN_SelectedMenuIcon('88');
					$facebookIcon = $iN->iN_SelectedMenuIcon('90');
					$twitterIcon = $iN->iN_SelectedMenuIcon('34');
					$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
					$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
					include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
					$body = $bodyNewSubscriberEmailTemplate;
					$mail->setFrom($smtpEmail, $siteName);
					$mail->IsHTML(true);
					$mail->addAddress($sendEmail, '');
					$mail->Subject = $gotNewSubscriber;
					$mail->CharSet = 'utf-8';
					$mail->MsgHTML($body);
					iN_safeMailSend($mail, $smtpOrMail, 'new_subscriber_notification');
					$mail->ClearAddresses();
				} catch (Exception $e) {
					error_log('Flutterwave subscribe mail error: ' . $e->getMessage());
				}
			}
			echo json_encode(['status' => 'success']);
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'Unable to activate subscription.']);
		}
		exit;
	}
	/*Subscribe User (SEND STRIPE AND SAVE DATA)*/
	if ($type == 'subscribeMe') {
		if (isset($_POST['u']) && isset($_POST['pl']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['t'])) {
			$iuID = $iN->iN_Secure($_POST['u']);
			$planID = $iN->iN_Secure($_POST['pl']);
			$subscriberName = $iN->iN_Secure($_POST['name']);
			$subscriberEmail = $iN->iN_Secure($_POST['email']);
			$stripeTokenID = $iN->iN_Secure($_POST['t']);
			$planDetails = $iN->iN_CheckPlanExist($planID, $iuID);
			$payment_id = $statusMsg = $api_error = '';
			$checkAlreadySubscribed = $iN->iN_CheckUserIsInSubscriber($userID, $iuID);
			$p_friend_status = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $iuID);
			if($p_friend_status == 'subscriber'){
               exit($LANG['already_subscribed']);
			}
			if ($planDetails && $p_friend_status != 'subscriber') {
				$planType = $planDetails['plan_type'];
                $planPrimaryID = $planDetails['plan_id'] ?? null;
                $stripePlanId = $planDetails['stripe_plan_id'] ?? null;
                $stripeProductId = $planDetails['stripe_product_id'] ?? null;
				$amount = $planDetails['amount'];
				$payment_Type = 'stripe';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
				}
				if (empty($stripeTokenID) || $stripeTokenID == '' || !isset($stripeTokenID) || $stripeTokenID == 'undefined') {
					exit($LANG['fill_all_credit_card_details']);
				}
				// Set API key
				\Stripe\Stripe::setApiKey($stripeKey);
				// Add customer to stripe
				try {
					$customer = \Stripe\Customer::create(array(
						'email' => $subscriberEmail,
						'source' => $stripeTokenID,
					));
				} catch (Exception $e) {
					$api_error = $e->getMessage();
				}
				/******/
				if (empty($api_error) && $customer) {
					// Convert price to cents
					$priceCents = round($amount * 100);

					// Create a plan
                    if (empty($stripePlanId)) {
    					try {
    						$plan = \Stripe\Plan::create(array(
    							"product" => [
    								"name" => $planName,
    							],
    							"amount" => $priceCents,
    							"currency" => $stripeCurrency,
    							"interval" => $planInterval,
    							"interval_count" => 1,
    						));
    					} catch (Exception $e) {
    						$api_error = $e->getMessage();
    					}
                        if (empty($api_error) && $plan) {
                            $stripePlanId = $plan->id;
                            $stripeProductId = is_object($plan->product) ? ($plan->product->id ?? null) : $plan->product;
                            if (!empty($planPrimaryID) && !empty($stripePlanId)) {
                                $iN->iN_UpdateSubscriptionPlanStripeIds($planPrimaryID, $stripePlanId, $stripeProductId);
                            }
                        }
                    }

					if (empty($api_error) && $stripePlanId) {

						// Creates a new subscription
						try {
							$subscription = \Stripe\Subscription::create(array(
								"customer" => $customer->id,
								"items" => array(
									array(
										"plan" => $stripePlanId,
									),
								),
							));
						} catch (Exception $e) {
							$api_error = $e->getMessage();
						}
						if (empty($api_error) && $subscription) {
							// Retrieve subscription data
							$subsData = $subscription->jsonSerialize();
							// Check whether the subscription activation is successful
							if ($subsData['status'] == 'active') {
								// Subscription info
								$subscrID = $subsData['id'];
								$custID = $subsData['customer'];
								$planIDs = $subsData['plan']['id'];
								$planAmount = ($subsData['plan']['amount'] / 100);
								$planCurrency = $subsData['plan']['currency'];
								$planinterval = $subsData['plan']['interval'];
								$planIntervalCount = $subsData['plan']['interval_count'];
								$plancreated = date("Y-m-d H:i:s", $subsData['created']);
								$current_period_start = date("Y-m-d H:i:s", $subsData['current_period_start']);
								$current_period_end = date("Y-m-d H:i:s", $subsData['current_period_end']);
								$planStatus = $subsData['status'];
								$adminEarning = ($adminFee * $planAmount) / 100;
								$userNetEarning = $planAmount - $adminEarning;
								$insertSubscription = $iN->iN_InsertUserSubscription($userID, $iuID, $payment_Type, $subscriberName, $subscrID, $custID, $planIDs, $planAmount, $adminEarning, $userNetEarning, $planCurrency, $planinterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus);
								if ($insertSubscription) {
									echo '200';
									$uData = $iN->iN_GetUserDetails($iuID);
									$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
									$lUsername = $uData['i_username'];
									$iN->iN_InsertNotificationForSubscribe($userID, $iuID);
									if ((string)$webPushStatus === '1' && (string)$webPushEventSubscription === '1' && (int)$iuID !== (int)$userID) {
										$actorName = trim((string)$userFullName) !== '' ? (string)$userFullName : (string)$userName;
										$pushTitle = $iN->iN_Secure($LANG['got_new_subscriber'] ?? 'You got new subscriber');
										$pushBody = $iN->iN_Secure($actorName . ' ' . ($LANG['is_subscribed_your_profile'] ?? 'is now subscribing your profile'));
										$iN->iN_SendWebPushToUser((int)$iuID, [
											'title' => $pushTitle,
											'body' => $pushBody,
											'url' => $base_url . 'settings?tab=subscribers',
											'tag' => 'subscription_' . (int)$userID,
										]);
									}
									$fuserAvatar = $iN->iN_UserAvatar($iuID, $base_url);
									$lUserFullName = $uData['i_user_fullname'];
									$emailNotificationStatus = $uData['email_notification_status'];
									$morePostForSubscriber = $LANG['share_something_for_subscriber'];
									$slugUrl = $base_url . $lUsername;
									$gotNewSubscriber = $LANG['got_new_subscriber'];
									if ($emailSendStatus == '1' && $emailNotificationStatus == '1') {

										if ($smtpOrMail == 'mail') {
											$mail->IsMail();
										} else if ($smtpOrMail == 'smtp') {
											$mail->isSMTP();
											$mail->Host = $smtpHost; // Specify main and backup SMTP servers
											$mail->SMTPAuth = true;
											$mail->SMTPKeepAlive = true;
											$mail->Username = $smtpUserName; // SMTP username
											$mail->Password = $smtpPassword; // SMTP password
											$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
											$mail->Port = $smtpPort;
											$mail->SMTPOptions = array(
												'ssl' => array(
													'verify_peer' => false,
													'verify_peer_name' => false,
													'allow_self_signed' => true,
												),
											);
										} else {
											return false;
										}
										$instagramIcon = $iN->iN_SelectedMenuIcon('88');
										$facebookIcon = $iN->iN_SelectedMenuIcon('90');
										$twitterIcon = $iN->iN_SelectedMenuIcon('34');
										$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
										$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
										include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
										$body = $bodyNewSubscriberEmailTemplate;
										$mail->setFrom($smtpEmail, $siteName);
										$send = false;
										$mail->IsHTML(true);
										$mail->addAddress($sendEmail, ''); // Add a recipient
										$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if (iN_safeMailSend($mail, $smtpOrMail, 'post_purchase_notification')) {
					$mail->ClearAddresses();
					return true;
				}

									}
								} else if (false) {
									echo iN_HelpSecure($LANG['contact_site_administrator']);
								}
							} else {
								echo iN_HelpSecure($LANG['subscription_activation_failed']);
							}
						} else {
							echo iN_HelpSecure($LANG['subscription_creation_failed']) . $api_error;
						}
					} else {
						echo iN_HelpSecure($LANG['plan_creation_failed']) . $api_error;
					}
				} else {
					echo iN_HelpSecure($LANG['invalid_card_details']) . $api_error;
				}
				/******/
			}
		}
	}
	/*Subscribe User (SUBSCRIBE WITH UPLOADED POINTS)*/
	if($type == 'subWithPoints'){
        if(isset($_POST['pl']) && $_POST['pl'] != '' && !empty($_POST['pl']) && isset($_POST['id']) && $_POST['id'] != '' && !empty($_POST['id'])){
			$planID = $iN->iN_Secure($_POST['pl']);
			$iuID = $iN->iN_Secure($_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			$planType = isset($checkPlanExist['plan_type']) ? $checkPlanExist['plan_type'] : NULL;
			$planAmount = isset($checkPlanExist['amount']) ? $checkPlanExist['amount'] : NULL;
			if($checkPlanExist && ($userCurrentPoints >= $planAmount)){
				$payment_Type = 'point';
				$adminEarning = $adminFee * $planAmount * $onePointEqual / 100;
				$userNetEarning = $planAmount * $onePointEqual - $adminEarning;
				$planIntervalCount = '1';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
					$thisTime = strtotime('+7 day', time());
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", $thisTime);
				    $current_period_end = date("Y-m-d H:i:s", $thisTime);
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 year', time()));
				}
				$uDetails = $iN->iN_GetUserDetails($iuID);
				$subscriberName = $iN->iN_Secure($uDetails['i_user_fullname']);
			    $subscriberEmail = $iN->iN_Secure($uDetails['i_user_email']);
				$UpdateCurrentPoint = $userCurrentPoints - $planAmount;
				$planCurrency = $defaultCurrency;
				$planStatus = 'active';
				$insertSubscription = $iN->iN_InsertUserSubscriptionWithPoint($userID, $iuID, $payment_Type, $subscriberName, $planAmount, $adminEarning, $userNetEarning, $planCurrency, $planInterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus,$UpdateCurrentPoint);
			    if ($insertSubscription) {
					echo '200';
					$uData = $iN->iN_GetUserDetails($iuID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$lUsername = $uData['i_username'];
					$iN->iN_InsertNotificationForSubscribe($userID, $iuID);
					if ((string)$webPushStatus === '1' && (string)$webPushEventSubscription === '1' && (int)$iuID !== (int)$userID) {
						$actorName = trim((string)$userFullName) !== '' ? (string)$userFullName : (string)$userName;
						$pushTitle = $iN->iN_Secure($LANG['got_new_subscriber'] ?? 'You got new subscriber');
						$pushBody = $iN->iN_Secure($actorName . ' ' . ($LANG['is_subscribed_your_profile'] ?? 'is now subscribing your profile'));
						$iN->iN_SendWebPushToUser((int)$iuID, [
							'title' => $pushTitle,
							'body' => $pushBody,
							'url' => $base_url . 'settings?tab=subscribers',
							'tag' => 'subscription_' . (int)$userID,
						]);
					}
					$fuserAvatar = $iN->iN_UserAvatar($iuID, $base_url);
					$lUserFullName = $uData['i_user_fullname'];
					$emailNotificationStatus = $uData['email_notification_status'];
					$morePostForSubscriber = $LANG['share_something_for_subscriber'];
					$slugUrl = $base_url . $lUsername;
					$gotNewSubscriber = $LANG['got_new_subscriber'];
					if ($emailSendStatus == '1' && $emailNotificationStatus == '1') {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
						include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
						$body = $bodyNewSubscriberEmailTemplate;
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if (iN_safeMailSend($mail, $smtpOrMail, 'new_subscriber_notification')) {
							$mail->ClearAddresses();
							return true;
						}
					}
				}else{
					exit('404');
				}
			} else{
				exit('302');
			}
		}
	}

	if ($type == 'uploadVerificationFiles') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == 'POST') {
			// Unified stories uploader (mirrors 'upload' flow) and exits early
			if (!empty($_FILES['storieimg']['name'])) {
				foreach ($_FILES['storieimg']['name'] as $iname => $value) {
					$name = stripslashes($_FILES['storieimg']['name'][$iname]);
					$size = $_FILES['storieimg']['size'][$iname];
					$ext = strtolower(getExtension($name));
					$valid_formats = explode(',', $availableFileExtensions);
					if (!in_array($ext, $valid_formats)) { echo iN_HelpSecure($LANG['invalid_file_format']); continue; }
					if (convert_to_mb($size) >= $availableUploadFileSize) { echo iN_HelpSecure($size); continue; }

					$microtime = microtime();
					$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
					$UploadedFileName = 'image_' . $removeMicrotime . '_' . $userID;
					$getFilename = $UploadedFileName . '.' . $ext;
					$tmp = $_FILES['storieimg']['tmp_name'][$iname];
					$mimeType = $_FILES['storieimg']['type'][$iname];
					$d = date('Y-m-d');

					// Determine type (stories allow image or video)
					if (preg_match('/video\/*/', $mimeType) || $mimeType === 'application/octet-stream') { $fileTypeIs = 'video'; }
					else if (preg_match('/image\/*/', $mimeType)) { $fileTypeIs = 'Image'; }
					else { echo iN_HelpSecure($LANG['invalid_file_format']); continue; }

					// Ensure directories
					if (!file_exists($uploadFile . $d)) { @mkdir($uploadFile . $d, 0755, true); }
					if (!file_exists($xImages . $d)) { @mkdir($xImages . $d, 0755, true); }
					if (!file_exists($xVideos . $d)) { @mkdir($xVideos . $d, 0755, true); }
					$wVideos = rtrim(UPLOAD_DIR_VIDEOS, '/') . '/';
					if (!file_exists($wVideos . $d)) { @mkdir($wVideos . $d, 0755, true); }

					if ($fileTypeIs === 'video' && !$ffmpegCanConvert && !in_array($ext, $nonFfmpegAvailableVideoFormat)) { exit('303'); }
					if (!move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) { echo $LANG['upload_failed']; continue; }

					$pathFile = '';
					$pathXFile = '';
					$tumbnailPath = '';
					$UploadSourceUrl = '';
                    if ($fileTypeIs === 'video') {
                        if ($ffmpegCanConvert) {
                            require_once '../includes/convertToMp4Format.php';
                            require_once '../includes/createVideoThumbnail.php';

                            // Resolve ffmpeg binary path if not explicitly configured
                            $ffmpegBin = !empty($ffmpegPath) ? trim((string)$ffmpegPath) : '';
                            if ($ffmpegBin === '' && function_exists('shell_exec')) {
                                $ffmpegBin = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null || which ffmpeg 2>/dev/null'));
                            }
                            if ($ffmpegBin === '') { $ffmpegBin = 'ffmpeg'; }

                            $sourceFs = $uploadFile . $d . '/' . $getFilename;
                            $convertedFs = convertToMp4Format($ffmpegBin, $sourceFs, $uploadFile . $d, $UploadedFileName);
                            if (!$convertedFs || !file_exists($convertedFs)) {
                                if (function_exists('rq_debug')) {
                                    rq_debug('upload:convertToMp4Format_failed', ['src' => $sourceFs, 'ffmpeg' => $ffmpegBin]);
                                }
                                $convertedFs = $sourceFs;
                            }

								// 4-second preview and poster
								if (!file_exists('../uploads/xvideos/' . $d)) { @mkdir('../uploads/xvideos/' . $d, 0755, true); }
	                            $xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
	                            $videoTumbnailFs = createVideoThumbnailInSameDir($ffmpegBin, $convertedFs);
	                            $ffmpegExec = escapeshellcmd($ffmpegBin);
	                            $safeClip = $ffmpegExec . ' -hide_banner -loglevel error -y'
	                                . ' -ss 00:00:01 -i ' . escapeshellarg($convertedFs)
	                                . ' -c copy -movflags +faststart -avoid_negative_ts make_zero'
	                                . ' -t 00:00:04 ' . escapeshellarg($xVideoFirstPath) . ' 2>&1';
	                            shell_exec($safeClip);

							$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
							$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
							$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
							$thePathM = '../' . $tumbnailPath;
								if (file_exists($thePathM) && ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes')) {
									watermark_apply_batch(
										[$thePathM],
										$siteWatermarkLogo,
										$LinkWatermarkStatus,
										watermark_label($base_url, $userName)
									);
								}

							// Publish keys and choose URL
							$publishKeys = [];
							$mp4Key = $pathFile;
							$xclipKey = $pathXFile;
							$thumbJpg = $tumbnailPath;
							if (is_file('../' . $mp4Key)) { $publishKeys[] = $mp4Key; }
							if (is_file('../' . $xclipKey)) { $publishKeys[] = $xclipKey; }
							if (is_file('../' . $thumbJpg)) { $publishKeys[] = $thumbJpg; }
                            // Publish and prefer thumbnail URL; avoid local is_file() after cleanup
                            $published = $publishKeys ? storage_publish_many($publishKeys, true, true) : [];
                            $UploadSourceUrl = $published[ltrim($thumbJpg, '/')] ?? ($published[ltrim($mp4Key, '/')] ?? ($base_url . 'uploads/web.png'));
                            if ($UploadSourceUrl === $base_url . 'uploads/web.png') { $tumbnailPath = 'uploads/web.png'; }
							$ext = 'mp4';
						} else {
							// No ffmpeg: treat as-is
							$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
							$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
							storage_publish_many([$pathFile], true, true);
							$UploadSourceUrl = storage_public_url($pathFile);
						}
					} else if ($fileTypeIs === 'Image') {
						$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
							$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$tumbnailPath = $pathFile;
							// Optional watermark on image
							$thePathM = '../' . $pathFile;
							if ($ext !== 'gif' && ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes')) {
								watermark_apply_batch(
									[$thePathM, '../' . $tumbnailPath],
									$siteWatermarkLogo,
									$LinkWatermarkStatus,
									watermark_label($base_url, $userName)
								);
							}
						// Pixelated copy
						try {
							$dir = '../' . $pathXFile;
							if (!file_exists(dirname($dir))) { @mkdir(dirname($dir), 0755, true); }
							$image = new ImageFilter();
							$image->load('../' . $pathFile)->pixelation($pixelSize)->saveFile($dir, 100, 'jpg');
						} catch (Exception $e) { echo '<span class="request_warning">' . $e->getMessage() . '</span>'; }

						storage_publish_many([$pathFile, $pathXFile], true, true);
						$UploadSourceUrl = storage_public_url($tumbnailPath);
					} else {
						echo iN_HelpSecure($LANG['invalid_file_format']);
						continue;
					}

					// Persist and render the story item
					$insertFileFromUploadTable = $iN->iN_insertUploadedSotieFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
					$getUploadedFileID = $iN->iN_GetUploadedStoriesFilesIDs($userID, $pathFile);
					$isStoryCreator = $iN->iN_CheckUserIsCreator($userID) == 1;
					$quickDefaults = [
						iN_HelpSecure($LANG['story_quick_reply_default_1'] ?? 'Nice!'),
						iN_HelpSecure($LANG['story_quick_reply_default_2'] ?? 'Love this!'),
						iN_HelpSecure($LANG['story_quick_reply_default_3'] ?? 'Tell me more')
					];
					$quickRepliesHtml = '';
					if ($isStoryCreator) {
						$quickRepliesHtml = '<div class="story_option_group story_quick_replies_toggle">'
							. '<div class="story_option_label">' . iN_HelpSecure($LANG['story_quick_replies_label']) . '</div>'
							. '<button type="button" class="story_quick_replies_trigger" aria-expanded="false"'
							. ' data-add-label="' . iN_HelpSecure($LANG['story_quick_replies_add']) . '"'
							. ' data-remove-label="' . iN_HelpSecure($LANG['story_quick_replies_hide']) . '">'
							. iN_HelpSecure($LANG['story_quick_replies_add'])
							. '</button>'
							. '</div>'
							. '<div class="story_option_group story_quick_replies_block is-hidden" data-enabled="0">'
							. '<div class="story_option_label">' . iN_HelpSecure($LANG['story_quick_replies_label']) . '</div>'
							. '<div class="story_quick_reply_inputs">'
							. '<input type="text" class="story_quick_reply_input" maxlength="120" value="' . $quickDefaults[0] . '">'
							. '<input type="text" class="story_quick_reply_input" maxlength="120" value="' . $quickDefaults[1] . '">'
							. '<input type="text" class="story_quick_reply_input" maxlength="120" value="' . $quickDefaults[2] . '">'
							. '<input type="text" class="story_quick_reply_input" maxlength="120" value="">'
							. '<input type="text" class="story_quick_reply_input" maxlength="120" value="">'
							. '</div>'
							. '<div class="rec_not box_not_padding_top">' . iN_HelpSecure($LANG['story_quick_replies_note']) . '</div>'
							. '</div>';
					}
					$storyAudioHtml = '';
					if ($fileTypeIs == 'Image') {
						$storyAudioHtml = '<div class="story_option_group story_audio_block">'
							. '<div class="story_option_label">' . iN_HelpSecure($LANG['story_audio_label']) . '</div>'
							. '<div class="story_audio_field">'
							. '<input type="hidden" class="story_overlay_audio" value="">'
							. '<div class="story_audio_preview">'
							. '<span class="story_audio_selected"></span>'
							. '<button type="button" class="story_audio_clear" title="' . iN_HelpSecure($LANG['delete']) . '" aria-label="' . iN_HelpSecure($LANG['delete']) . '">'
							. html_entity_decode($iN->iN_SelectedMenuIcon('5'))
							. '</button>'
							. '</div>'
							. '<button type="button" class="story_audio_trigger" title="' . iN_HelpSecure($LANG['story_audio_choose']) . '" aria-label="' . iN_HelpSecure($LANG['story_audio_choose']) . '">'
							. iN_HelpSecure($LANG['story_audio_choose'])
							. '</button>'
							. '</div>'
							. '<div class="story_audio_list"></div>'
							. '</div>';
					}
					$storyOptionsHtml = '<div class="story_options">'
						. '<div class="story_option_group">'
						. '<div class="story_option_label">' . iN_HelpSecure($LANG['story_privacy_label']) . '</div>'
						. '<select class="story_privacy">'
						. '<option value="followers">' . iN_HelpSecure($LANG['story_privacy_followers']) . '</option>'
						. '<option value="subscribers">' . iN_HelpSecure($LANG['story_privacy_subscribers']) . '</option>'
						. '<option value="everyone">' . iN_HelpSecure($LANG['story_privacy_everyone']) . '</option>'
						. '</select>'
						. '</div>'
						. '<div class="story_option_group">'
						. '<div class="story_option_label">' . iN_HelpSecure($LANG['story_overlay_label']) . '</div>'
						. '<div class="story_overlay_fields">'
						. '<input type="text" class="story_overlay_link" placeholder="' . iN_HelpSecure($LANG['story_overlay_link_placeholder']) . '">'
						. '<input type="text" class="story_overlay_mention" placeholder="' . iN_HelpSecure($LANG['story_overlay_mention_placeholder']) . '">'
						. '<div class="story_overlay_sticker_field">'
						. '<input type="hidden" class="story_overlay_sticker" value="">'
						. '<div class="story_sticker_preview">'
						. '<img class="story_sticker_img" src="" alt="">'
						. '<button type="button" class="story_sticker_clear" title="' . iN_HelpSecure($LANG['delete']) . '" aria-label="' . iN_HelpSecure($LANG['delete']) . '">'
						. html_entity_decode($iN->iN_SelectedMenuIcon('5'))
						. '</button>'
						. '</div>'
						. '<button type="button" class="story_sticker_trigger" title="' . iN_HelpSecure($LANG['chs_sticker_send']) . '" aria-label="' . iN_HelpSecure($LANG['chs_sticker_send']) . '">'
						. html_entity_decode($iN->iN_SelectedMenuIcon('24'))
						. '</button>'
						. '</div>'
						. '</div>'
						. '<div class="story_sticker_list"></div>'
						. '</div>'
						. $storyAudioHtml
						. $quickRepliesHtml
						. '</div>';
					if ($fileTypeIs == 'Image') {
						echo '
						<!--Storie-->
						<div class="uploaded_storie_container nonePoint body_' . $getUploadedFileID['s_id'] . '">
						<div class="dmyStory" id="' . $getUploadedFileID['s_id'] . '"><div class="i_h_in flex_ ownTooltip" data-label="' . iN_HelpSecure($LANG['delete']) . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('28')) . '</div></div>
						<div class="uploaded_storie_image border_one tabing flex_">
								<img src="' . $UploadSourceUrl . '" id="img' . $getUploadedFileID['s_id'] . '">
						</div>
						<div class="add_a_text">
							<textarea class="add_my_text st_txt_' . $getUploadedFileID['s_id'] . '" placeholder="Do you want to write something about this storie?"></textarea>
						</div>
						' . $storyOptionsHtml . '
						<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="' . $getUploadedFileID['s_id'] . '">
							' . html_entity_decode($iN->iN_SelectedMenuIcon('26')) . '<div class="pbtn">' . iN_HelpSecure($LANG['publish']) . '</div>
						</div>
						</div>
						<!--/Storie-->
						<script type="text/javascript">(function($){"use strict";setTimeout(()=>{var img=document.getElementById("img' . $getUploadedFileID['s_id'] . '"); if(img && img.height>img.width){$("#img' . $getUploadedFileID['s_id'] . '").css("height","100%");} else {$("#img' . $getUploadedFileID['s_id'] . '").css("width","100%");} $(".uploaded_storie_container").show();},2000);})(jQuery);</script>
					';
					} else if ($fileTypeIs == 'video') {
						echo '
						<!--Storie-->
						<div class="uploaded_storie_container body_' . $getUploadedFileID['s_id'] . '">
						<div class="dmyStory" id="' . $getUploadedFileID['s_id'] . '"><div class="i_h_in flex_ ownTooltip" data-label="' . iN_HelpSecure($LANG['delete']) . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('28')) . '</div></div>
						<div class="uploaded_storie_image border_one tabing flex_">
								<video class="lg-video-object" id="v' . $getUploadedFileID['s_id'] . '" controls preload="none" poster="' . $UploadSourceUrl . '">
									<source src="' . storage_public_url($getUploadedFileID['uploaded_file_path']) . '" preload="metadata" type="video/mp4">
									Your browser does not support HTML5 video.
								</video>
						</div>
						<div class="add_a_text">
							<textarea class="add_my_text st_txt_' . $getUploadedFileID['s_id'] . '" placeholder="Do you want to write something about this storie?"></textarea>
						</div>
						' . $storyOptionsHtml . '
						<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="' . $getUploadedFileID['s_id'] . '">
							' . html_entity_decode($iN->iN_SelectedMenuIcon('26')) . '<div class="pbtn">' . iN_HelpSecure($LANG['publish']) . '</div>
						</div>
						</div>
						<!--/Storie-->
					';
					}
				}
				exit; // prevent falling into legacy stories handler
			}
			$theValidateType = $iN->iN_Secure($_POST['c']);
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit($LANG['upload_failed']);
								}
								// Unified publish for verification image + pixel copy
								$pixelKey = 'uploads/pixel/' . $d . '/' . $getFilename;
								$keysToPublish = [$pathFile, $pixelKey];
                                $UploadSourceUrl = storage_publish_and_url($pathFile, $keysToPublish, true);
                            }
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFilesForVerification($userID, $pathFile, NULL, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo '
                    <div class="i_uploaded_item in_' . $theValidateType . ' iu_f_' . $getUploadedFileID['upload_id'] . '" id="' . $getUploadedFileID['upload_id'] . '">
                      ' . $postTypeIcon . '
                      <div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
                          ' . $iN->iN_SelectedMenuIcon('5') . '
                      </div>
                      <div class="i_uploaded_file" style="background-image:url(' . $UploadSourceUrl . ');">
                            <img class="i_file" src="' . $UploadSourceUrl . '" alt="' . $UploadSourceUrl . '">
                      </div>
                    </div>
                ';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Send Account Verificatoun Request*/
	if ($type == 'verificationRequest') {
		if (isset($_POST['cID']) && isset($_POST['cP'])) {
			$cardIDPhoto = $iN->iN_Secure($_POST['cID']);
			$Photo = $iN->iN_Secure($_POST['cP']);
			$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
			$checkPhotoExist = $iN->iN_CheckImageIDExist($Photo, $userID);
			if (empty($cardIDPhoto) && empty($Photo) && empty($checkCardIDPhotoExist) && empty($checkPhotoExist)) {
				echo 'both';
				return false;
			}
			if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
				echo 'card';
				return false;
			}
			if (empty($Photo) && empty($checkPhotoExist)) {
				echo 'photo';
				return false;
			}
			if ($checkCardIDPhotoExist == '1' && $checkPhotoExist == '1') {
				$InsertNewVerificationRequest = $iN->iN_InsertNewVerificationRequest($userID, $cardIDPhoto, $Photo);
				if ($InsertNewVerificationRequest) {
					echo '200';
				}
			} else {
				echo 'both';
			}
		}
	}
	/*Accept Conditions by Clicking Next Button*/
	if ($type == 'acceptConditions') {
		$instagramUrl = trim((string)($iN->iN_Secure($_POST['instagram_url'] ?? '')));
		$tiktokUrl = trim((string)($iN->iN_Secure($_POST['tiktok_url'] ?? '')));

		// At least one social account required
		if ($instagramUrl === '' && $tiktokUrl === '') {
			echo 'social_required';
			exit();
		}

		// Validate URLs
		if ($instagramUrl !== '' && !filter_var($instagramUrl, FILTER_VALIDATE_URL)) {
			echo 'invalid_url';
			exit();
		}
		if ($tiktokUrl !== '' && !filter_var($tiktokUrl, FILTER_VALIDATE_URL)) {
			echo 'invalid_url';
			exit();
		}

		$conditionsAccept = $iN->iN_AcceptConditions($userID, $instagramUrl, $tiktokUrl);
		if ($conditionsAccept) {
			echo '200';
		}
	}
	if($type == 'vldcd'){
		if(isset($_POST['code']) && $_POST['code'] != '' && !empty($_POST['code'])){
			$cosCode = $iN->iN_Secure($_POST['code']);
			$vcodeCheck = $iN->iN_PurUCheck($userID, $cosCode, $base_url);
			if($vcodeCheck == base64_decode('b2s=')){
				if($iN->iN_LegDone($cosCode)){
					exit(base64_decode('bmV4dA=='));
				}else{
					exit(base64_decode('RHVyaW5nIHRoZSBpbnN0YWxsYXRpb24gcHJvY2VzcywgYW4gaXNzdWUgaGFzIGFyaXNlbiBjb25jZXJuaW5nIHRoZSBzZXJ2ZXIuIFBsZWFzZSBjcmVhdGUgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1jcmVhdGVUaWNrZXQiPnRpY2tldDwvYT4gZm9yIHByb21wdCBhc3Npc3RhbmNlLiBCZWZvcmUgY3JlYXRpbmcgYSB0aWNrZXQsIGtpbmRseSB0YWtlIGEgbW9tZW50IHRvIHJldmlldyBmb3IgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1mYXFzIj5xdWljayByZXNwb25zZTwvYT4u'));
				}
			} else{
				exit(base64_decode('RHVyaW5nIHRoZSBpbnN0YWxsYXRpb24gcHJvY2VzcywgYW4gaXNzdWUgaGFzIGFyaXNlbiBjb25jZXJuaW5nIHRoZSBzZXJ2ZXIuIFBsZWFzZSBjcmVhdGUgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1jcmVhdGVUaWNrZXQiPnRpY2tldDwvYT4gZm9yIHByb21wdCBhc3Npc3RhbmNlLiBCZWZvcmUgY3JlYXRpbmcgYSB0aWNrZXQsIGtpbmRseSB0YWtlIGEgbW9tZW50IHRvIHJldmlldyBmb3IgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1mYXFzIj5xdWljayByZXNwb25zZTwvYT4u'));
			}
		}
	}
	/*Insert Subscription Amount if Amounts are not empty*/
	if ($type == 'setSubscriptionPayments') {
		if (in_array($_POST['wStatus'], $statusValue) && in_array($_POST['mStatus'], $statusValue) && in_array($_POST['yStatus'], $statusValue)) {
			$SubWeekAmount = $iN->iN_Secure($_POST['wSubWeekAmount']);
			$SubMonthAmount = $iN->iN_Secure($_POST['mSubMonthAmount']);
			$SubYearAmount = $iN->iN_Secure($_POST['mSubYearAmount']);
			$weeklySubStatus = $iN->iN_Secure($_POST['wStatus']);
			$monthlySubStatus = $iN->iN_Secure($_POST['mStatus']);
			$yearlySubStatus = $iN->iN_Secure($_POST['yStatus']);
			if (!empty($SubWeekAmount) && $SubWeekAmount !== '') {
				if ($SubWeekAmount >= $subscribeWeeklyMinimumAmount) {
					$iN->iN_InsertWeeklySubscriptionAmountAndStatus($userID, $SubWeekAmount, $weeklySubStatus);
				}
			}
			if (!empty($SubMonthAmount) && $SubMonthAmount !== '') {
				if ($SubMonthAmount >= $subscribeMonthlyMinimumAmount) {
					$iN->iN_InsertMonthlySubscriptionAmountAndStatus($userID, $SubMonthAmount, $monthlySubStatus);
				}
			}
			if (!empty($SubYearAmount) && $SubYearAmount !== '') {
				if ($SubYearAmount >= $subscribeYearlyMinimumAmount) {
					$iN->iN_InsertYearlySubscriptionAmountAndStatus($userID, $SubYearAmount, $yearlySubStatus);
				}
			}
			$updateFeeStatus = $iN->iN_UpdateUserFeeStatus($userID);
			if ($updateFeeStatus) {
				echo '200';
			}
		}
	}
		/*Save Payout Details*/
		if ($type == 'payoutSet') {
			if (isset($_POST['method']) && in_array($_POST['method'], $defaultPayoutMethods, true)) {
				$defaultMethod = $iN->iN_Secure($_POST['method']);
				$paypalEmail = isset($_POST['paypalEmail']) ? rawurldecode($iN->iN_Secure($_POST['paypalEmail'])) : '';
				$re_paypalEmail = isset($_POST['paypalReEmail']) ? rawurldecode($iN->iN_Secure($_POST['paypalReEmail'])) : '';
				$bankAccount = isset($_POST['bank']) ? $iN->iN_Secure($_POST['bank']) : '';
				$payoneerEmail = isset($_POST['payoneerEmail']) ? rawurldecode($iN->iN_Secure($_POST['payoneerEmail'])) : '';
				$payoneerReEmail = isset($_POST['payoneerReEmail']) ? rawurldecode($iN->iN_Secure($_POST['payoneerReEmail'])) : '';
				$zelleEmail = isset($_POST['zelleEmail']) ? rawurldecode($iN->iN_Secure($_POST['zelleEmail'])) : '';
				$zelleReEmail = isset($_POST['zelleReEmail']) ? rawurldecode($iN->iN_Secure($_POST['zelleReEmail'])) : '';
				$westernUnionFullName = isset($_POST['westernUnionFullName']) ? $iN->iN_Secure($_POST['westernUnionFullName']) : '';
				$westernUnionDocumentId = isset($_POST['westernUnionDocumentId']) ? $iN->iN_Secure($_POST['westernUnionDocumentId']) : '';
				$bitcoinWallet = isset($_POST['bitcoinWallet']) ? $iN->iN_Secure($_POST['bitcoinWallet']) : '';
				$mercadoPagoAlias = isset($_POST['mercadoPagoAlias']) ? $iN->iN_Secure($_POST['mercadoPagoAlias']) : '';
				$mercadoPagoCvu = isset($_POST['mercadoPagoCvu']) ? $iN->iN_Secure($_POST['mercadoPagoCvu']) : '';
				$payoutMethodData = array();

				if ($defaultMethod === 'paypal') {
					if ($paypalEmail == '' || empty($paypalEmail)) {
						echo 'paypal_warning';
						exit();
					}
					if ($paypalEmail != $re_paypalEmail) {
						echo 'email_warning';
						exit();
					}
					if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
						echo 'not_valid_email';
						exit();
					}
				} elseif ($defaultMethod === 'payoneer') {
					if ($payoneerEmail == '' || empty($payoneerEmail)) {
						echo 'paypal_warning';
						exit();
					}
					if ($payoneerEmail != $payoneerReEmail) {
						echo 'email_warning';
						exit();
					}
					if (!filter_var($payoneerEmail, FILTER_VALIDATE_EMAIL)) {
						echo 'not_valid_email';
						exit();
					}
					$payoutMethodData['payoneer_email'] = $payoneerEmail;
				} elseif ($defaultMethod === 'zelle') {
					if ($zelleEmail == '' || empty($zelleEmail)) {
						echo 'paypal_warning';
						exit();
					}
					if ($zelleEmail != $zelleReEmail) {
						echo 'email_warning';
						exit();
					}
					if (!filter_var($zelleEmail, FILTER_VALIDATE_EMAIL)) {
						echo 'not_valid_email';
						exit();
					}
					$payoutMethodData['zelle_email'] = $zelleEmail;
				} elseif ($defaultMethod === 'western-union') {
					if (trim((string)$westernUnionFullName) === '' || trim((string)$westernUnionDocumentId) === '') {
						echo 'bank_warning';
						exit();
					}
					$payoutMethodData['western_union_full_name'] = $westernUnionFullName;
					$payoutMethodData['western_union_document_id'] = $westernUnionDocumentId;
				} elseif ($defaultMethod === 'bitcoin') {
					if (trim((string)$bitcoinWallet) === '') {
						echo 'bank_warning';
						exit();
					}
					$payoutMethodData['bitcoin_wallet_address'] = $bitcoinWallet;
				} elseif ($defaultMethod === 'mercadopago') {
					if (trim((string)$mercadoPagoAlias) === '' || trim((string)$mercadoPagoCvu) === '') {
						echo 'bank_warning';
						exit();
					}
					$payoutMethodData['mercadopago_alias'] = $mercadoPagoAlias;
					$payoutMethodData['mercadopago_cvu'] = $mercadoPagoCvu;
				} else {
					if (trim((string)$bankAccount) === '') {
						echo 'bank_warning';
						exit();
					}
					// Premium structured bank transfer fields
					$bankCountry       = trim((string)($_POST['bank_country'] ?? ''));
					$ibanNumber        = strtoupper(preg_replace('/\s+/', '', (string)($_POST['iban_number'] ?? '')));
					$routingNumber     = trim((string)($_POST['routing_number'] ?? ''));
					$accountNumber     = trim((string)($_POST['account_number'] ?? ''));
					$confirmAccount    = trim((string)($_POST['confirm_account_number'] ?? ''));
					$accountHolderName = trim((string)($_POST['account_holder_name'] ?? ''));
					$phoneCountryCode  = trim((string)($_POST['phone_country_code'] ?? ''));
					$phoneNumber       = trim((string)($_POST['phone_number'] ?? ''));
					$streetAddress     = trim((string)($_POST['street_address'] ?? ''));
					$btCountry         = trim((string)($_POST['country'] ?? ''));
					$btState           = trim((string)($_POST['state'] ?? ''));
					$btCity            = trim((string)($_POST['city'] ?? ''));
					$btPostal          = trim((string)($_POST['postal_code'] ?? ''));
					if ($bankCountry === '' || $accountNumber === '' || $accountHolderName === ''
						|| $phoneCountryCode === '' || $phoneNumber === '' || $streetAddress === ''
						|| $btCountry === '' || $btState === '' || $btCity === '' || $btPostal === '') {
						echo 'bank_warning';
						exit();
					}
					if ($accountNumber !== $confirmAccount) {
						echo 'bank_warning';
						exit();
					}
					$payoutMethodData['bank_country']         = $iN->iN_Secure($bankCountry);
					$payoutMethodData['iban_number']          = $iN->iN_Secure($ibanNumber);
					$payoutMethodData['routing_number']       = $iN->iN_Secure($routingNumber);
					$payoutMethodData['account_number']       = $iN->iN_Secure($accountNumber);
					$payoutMethodData['account_holder_name']  = $iN->iN_Secure($accountHolderName);
					$payoutMethodData['phone_country_code']   = $iN->iN_Secure($phoneCountryCode);
					$payoutMethodData['phone_number']         = $iN->iN_Secure($phoneNumber);
					$payoutMethodData['street_address']       = $iN->iN_Secure($streetAddress);
					$payoutMethodData['country']              = $iN->iN_Secure($btCountry);
					$payoutMethodData['state']                = $iN->iN_Secure($btState);
					$payoutMethodData['city']                 = $iN->iN_Secure($btCity);
					$payoutMethodData['postal_code']          = $iN->iN_Secure($btPostal);
				}

				$insertPayout = $iN->iN_SetPayout($userID, $paypalEmail, $bankAccount, $defaultMethod, $payoutMethodData);
				if ($insertPayout) {
					echo '200';
				}
			}
		}
	/*Check Username Exist*/
	if ($type == 'checkusername') {
		if (isset($_POST['username']) && $_POST['username'] != '' && !empty($_POST['username'])) {
			$new_username = $iN->iN_Secure($_POST['username']);
			$checkUsernameExist = $iN->iN_CheckUsernameExist($new_username);
			if ($new_username == $userName) {
				exit();
			} else if (strlen($new_username) < 5) {
				echo '4';
			} else if (!preg_match('/^[\w]+$/', $_POST['username'])) {
				echo '3';
			} else if ($checkUsernameExist == 'no') {
				echo '1';
			} else if ($checkUsernameExist == 'yes') {
				echo '2';
			}
		}
	}
	/*Edit May Page*/
	if ($type == 'editMyPage') {
		$fullname = $iN->iN_Secure($_POST['flname']);
		$newUsername = $iN->iN_Secure($_POST['uname']);
		$gender = $iN->iN_Secure($_POST['gender']);
		$bio = $iN->iN_Secure($_POST['bio']);
		if(isset($_POST['tnot']) && !empty($_POST['tnot']) && $_POST['tnot'] != ''){
			$tipNot = $iN->iN_Secure($_POST['tnot']);
		}else{
			$tipNot = '';
		}
		   $socialNet = $iN->iN_ShowUserSocialSitesList($userID);
           if($socialNet){
               foreach($socialNet as $snet){
                 $sKey = $snet['skey'];
				 $slID = $snet['id'];
				 if(isset($_POST[$sKey]) && !empty($_POST[$sKey]) && $_POST[$sKey] != ''){
					 $mySkey = trim($_POST[$sKey]);
                     if($iN->iN_IsUrl($mySkey) == '1'){
						$exist = DB::one("SELECT 1 FROM i_social_user_profiles WHERE uid_fk = ? AND isw_id_fk = ? LIMIT 1", [(int)$userID, (int)$slID]);
					    if($exist){
						    DB::exec("UPDATE i_social_user_profiles SET s_link = ? WHERE uid_fk = ? AND isw_id_fk = ?", [$mySkey, (int)$userID, (int)$slID]);
					    } else {
						    DB::exec("INSERT INTO i_social_user_profiles(s_link,isw_id_fk, uid_fk) VALUES (?,?,?)", [$mySkey, (int)$slID, (int)$userID]);
					    }
					}
				 }else{
					DB::exec("UPDATE i_social_user_profiles SET s_link = NULL WHERE uid_fk = ? AND isw_id_fk = ?", [(int)$userID, (int)$slID]);
				 }
		       }
	       }
		$birthDay = $iN->iN_Secure($_POST['birthdate']);
		$profileCategory = $iN->iN_Secure($_POST['ctgry']);
		$checkUsernameExist = $iN->iN_CheckUsernameExist($newUsername);
		if (strlen($fullname) < 3 || strlen($fullname) > 30 || empty($fullname)) {
			exit('3');
		}
		if (strlen($newUsername) < 5) {
			$newUsername = $userName;
		} else if (!preg_match('/^[\w]+$/', $newUsername)) {
			$newUsername = $userName;
		} else if ($checkUsernameExist == 'yes') {
			$newUsername = $userName;
		}
		if (strlen($fullname) < 5 || strlen($fullname) > 30) {
			$fullname = $userFullName;
		}
		if(!empty($birthDay) && $birthDay != '' && $birthDay != 'undefined'){
			if ($iN->iN_CalculateUserAge($birthDay) < 18) {
				exit('2');
			}
	    }
		if(!empty($birthDay) && $birthDay != '' && $birthDay != 'undefined'){
           if(!$iN->isDate($birthDay)){
               exit('1');
		   }
		}else{
			$birthDay = NULL;
		}

		if (in_array($gender, $genders) && isset($newUsername) && !empty($newUsername) && $newUsername != '' && isset($fullname) && !empty($fullname) && $fullname != '') {
			$updateMyProfile = $iN->iN_UpdateProfile($userID, $iN->iN_Secure($fullname), $iN->iN_Secure($bio), $iN->iN_Secure($newUsername), $iN->iN_Secure($birthDay), $iN->iN_Secure($profileCategory), $iN->iN_Secure($gender),$iN->iN_Secure($tipNot));
			if ($updateMyProfile) {
				echo '1';
			}
		}
	}
	/*Call Avatar and Cover PopUP*/
	if ($type == 'updateAvatarCover') {
		include "../themes/$currentTheme/layouts/popup_alerts/uploadAvatarCoverPhoto.php";
	}
	/*Call Community Avatar and Cover PopUP*/
	if ($type == 'communityUpdateAvatarCover') {
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$moderatorData = null;
		$canManageMedia = false;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
			$canManageMedia = !empty($moderatorData) && (string)($moderatorData['can_manage_media'] ?? '0') === '1';
		}
		if (!$isOwner && !$isAdmin && !$canManageMedia) {
			exit;
		}
		$communityCoverUrl = $base_url . 'uploads/web.png';
		if (!empty($communityData['cover_image'])) {
			if (function_exists('storage_public_url')) {
				$communityCoverUrl = storage_public_url($communityData['cover_image']);
			} else {
				$communityCoverUrl = $base_url . $communityData['cover_image'];
			}
		}
		$ownerAvatar = $iN->iN_UserAvatar((int)$communityData['owner_user_id'], $base_url);
		$communityAvatarUrl = $ownerAvatar;
		if (!empty($communityData['avatar_image'])) {
			if (function_exists('storage_public_url')) {
				$communityAvatarUrl = storage_public_url($communityData['avatar_image']);
			} else {
				$communityAvatarUrl = $base_url . $communityData['avatar_image'];
			}
		}
		include "../themes/$currentTheme/layouts/popup_alerts/communityAvatarCoverPhoto.php";
	}
	/*Call Community Edit PopUP*/
	if ($type == 'communityEditModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$moderationEnabled = (string)($communityData['moderation_enabled'] ?? '0') === '1';
		$moderatorData = null;
		$canManageMembers = false;
		$canManageMedia = false;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
			$canManageMembers = !empty($moderatorData) && (string)($moderatorData['can_manage_members'] ?? '0') === '1';
			$canManageMedia = !empty($moderatorData) && (string)($moderatorData['can_manage_media'] ?? '0') === '1';
		}
		if (!$isOwner && !$isAdmin && !$canManageMembers && !$canManageMedia) {
			exit;
		}
		$communityTitle = $communityData['title'] ?? '';
		$communityCategory = $communityData['category'] ?? '';
		$communityDescription = $communityData['description'] ?? '';
		$communityLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
		$communityPrice = isset($communityData['monthly_price']) ? (float)$communityData['monthly_price'] : 0;
		$communitySubscriptionRequired = (string)($communityData['subscription_required'] ?? '1');
		$communityPostingPolicy = (string)($communityData['posting_policy'] ?? 'owner_admin');
		if (!in_array($communityPostingPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
			$communityPostingPolicy = 'owner_admin';
		}
		$communityCommentPolicy = (string)($communityData['comment_policy'] ?? 'members');
		if (!in_array($communityCommentPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
			$communityCommentPolicy = 'members';
		}
		$communityAccessPolicy = (string)($communityData['access_policy'] ?? 'members_only');
		if (!in_array($communityAccessPolicy, ['members_only', 'public'], true)) {
			$communityAccessPolicy = 'members_only';
		}
		$communityCategories = $iN->iN_GetCommunityCategories(true);
		$subscriptionTypeValue = (string)$subscriptionType;
		$communityCreationSettings = $iN->iN_GetCommunityCreationSettings();
		include "../themes/$currentTheme/layouts/popup_alerts/communityEdit.php";
	}
	/*Call Community Moderation PopUP*/
	if ($type == 'communityModerationModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		if ((int)($communityData['owner_user_id'] ?? 0) !== (int)$userID && !$isAdmin) {
			exit;
		}
		$moderationUsers = $iN->iN_GetCommunityModerationUsers($communityID, 50);
		$moderationUserIds = [];
		foreach ($moderationUsers as $moderationUser) {
			$moderationUserId = (int)($moderationUser['user_id'] ?? 0);
			if ($moderationUserId > 0) {
				$moderationUserIds[$moderationUserId] = true;
			}
		}
		$displayUsers = $moderationUsers;
		if (empty($displayUsers)) {
			$displayUsers = $iN->iN_GetCommunityModerationCandidates($userID, 24);
		}
		include "../themes/$currentTheme/layouts/popup_alerts/communityModerationUsers.php";
		exit;
	}
	/*Call Community Moderation Member Edit PopUP*/
	if ($type == 'communityModerationMemberModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['member_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$memberID = (int)$iN->iN_Secure($_POST['member_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$moderationEnabled = (string)($communityData['moderation_enabled'] ?? '0') === '1';
		$timeoutEnabled = (string)($communityData['moderation_timeout_enabled'] ?? '0') === '1';
		$moderatorData = null;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
		}
		$canManageMembers = $isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_members'] ?? '0') === '1');
		$canManagePosts = $isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_posts'] ?? '0') === '1');
		$canManageComments = $isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_comments'] ?? '0') === '1');
		$canManageReshare = $isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_reshare'] ?? '0') === '1');
		$canManageTimeout = $timeoutEnabled && ($isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_view_timeout'] ?? '0') === '1'));
		if (!$canManageMembers && !$canManageReshare && !$canManageTimeout && !$canManagePosts && !$canManageComments) {
			exit;
		}
		$memberData = $iN->iN_GetUserDetails($memberID);
		if (!$memberData) {
			exit;
		}
		$memberName = $memberData['i_user_fullname'] ?: ($memberData['i_username'] ?? '');
		$memberAvatarUrl = $iN->iN_UserAvatar($memberID, $base_url);
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $memberID);
		$memberStatus = 'active';
		if ($restrictionStatus === 'blocked') {
			$memberStatus = 'blocked';
		} elseif ($restrictionStatus === 'restricted') {
			$memberStatus = 'restricted';
		}
		$memberControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
		$memberPostDisabled = (string)($memberControl['post_disabled'] ?? '0') === '1';
		$memberCommentDisabled = (string)($memberControl['comment_disabled'] ?? '0') === '1';
		$memberReshareDisabled = $iN->iN_IsCommunityReshareDisabled($communityID, $memberID);
		$viewRestriction = $iN->iN_GetCommunityViewRestriction($communityID, $memberID);
		$memberViewUntil = $viewRestriction['view_until'] ?? null;
		$memberViewPermanent = !empty($viewRestriction['view_permanent']);
		$timeoutOptions = $timeoutEnabled ? $iN->iN_GetCommunityTimeoutOptions($communityData) : [];
		$currentTimeoutLabel = $LANG['community_moderation_timeout_none'] ?? 'No timeout';
		if ($memberViewPermanent) {
			$currentTimeoutLabel = $LANG['community_moderation_timeout_permanent'] ?? 'Permanent';
		} elseif (!empty($memberViewUntil)) {
			$currentTimeoutLabel = str_replace('{date}', date('M d, Y', strtotime((string)$memberViewUntil)), $LANG['community_moderation_timeout_until'] ?? 'Until {date}');
		}
		include "../themes/$currentTheme/layouts/popup_alerts/communityModerationMemberEdit.php";
		exit;
	}
	/*Call Community Members PopUP*/
	if ($type == 'communityMembersModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$isMember = $iN->iN_IsCommunityAccessMember($communityData, $userID);
		$moderationEnabled = (string)($communityData['moderation_enabled'] ?? '0') === '1';
		$moderatorData = null;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
		}
		$isModerator = !empty($moderatorData);
		$canManageMembers = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_members'] ?? '0') === '1');
		$canManagePosts = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_posts'] ?? '0') === '1');
		$canManageComments = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_comments'] ?? '0') === '1');
		$canManageReshare = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_reshare'] ?? '0') === '1');
		$canManageTimeout = (string)($communityData['moderation_timeout_enabled'] ?? '0') === '1'
			&& ($isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_view_timeout'] ?? '0') === '1'));
		$canModerateMemberControls = $canManageMembers || $canManagePosts || $canManageComments || $canManageReshare || $canManageTimeout;
		$accessPolicy = (string)($communityData['access_policy'] ?? 'members_only');
		if (!in_array($accessPolicy, ['members_only', 'public'], true)) {
			$accessPolicy = 'members_only';
		}
		$isModerationListed = false;
		if (!$isOwner && !$isAdmin) {
			$isModerationListed = $iN->iN_IsCommunityModerationListed($communityID, $userID);
		}
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
		$isBlocked = ($restrictionStatus === 'blocked');
		$isViewRestricted = false;
		if ($moderationEnabled && !$isOwner && !$isAdmin) {
			$viewRestriction = $iN->iN_GetCommunityViewRestriction($communityID, $userID);
			$isViewRestricted = !empty($viewRestriction['restricted']);
		}
		$canAccessCommunity = $iN->iN_CanAccessCommunity($communityData, $userID, $isAdmin, $isModerator);
		$canViewCommunity = $canAccessCommunity || $isModerationListed;
		if (($isBlocked || $isViewRestricted) && !$isOwner && !$isAdmin) {
			$canViewCommunity = false;
		}
		if (!$canViewCommunity) {
			exit;
		}
		if ($accessPolicy === 'public' && !$canManageMembers && !$isOwner && !$isAdmin) {
			exit;
		}
		$communityMembers = $iN->iN_GetCommunityMembers($communityID, 0);
		include "../themes/$currentTheme/layouts/popup_alerts/communityMembers.php";
		exit;
	}
	/*Call Community Moderator PopUP*/
	if ($type == 'communityModeratorModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		if ((int)($communityData['owner_user_id'] ?? 0) !== (int)$userID && !$isAdmin) {
			exit;
		}
		$moderatorUsers = $iN->iN_GetCommunityModerators($communityID, 200);
		$moderatorUserIds = [];
		foreach ($moderatorUsers as $moderatorRow) {
			$moderatorUserId = (int)($moderatorRow['user_id'] ?? 0);
			if ($moderatorUserId > 0) {
				$moderatorUserIds[$moderatorUserId] = true;
			}
		}
		$displayUsers = $iN->iN_GetCommunityModerationCandidates($userID, 24);
		include "../themes/$currentTheme/layouts/popup_alerts/communityModeratorAdd.php";
		exit;
	}
	/*Call Community Moderator Edit PopUP*/
	if ($type == 'communityModeratorEditModal') {
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['moderator_id'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$moderatorId = (int)$iN->iN_Secure($_POST['moderator_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		if ((int)($communityData['owner_user_id'] ?? 0) !== (int)$userID && !$isAdmin) {
			exit;
		}
		$moderatorData = $iN->iN_GetCommunityModerator($communityID, $moderatorId);
		if (!$moderatorData) {
			exit;
		}
		$moderatorUser = $iN->iN_GetUserDetails($moderatorId);
		if (!$moderatorUser) {
			exit;
		}
		$moderatorName = $moderatorUser['i_user_fullname'] ?: ($moderatorUser['i_username'] ?? '');
		$moderatorAvatarUrl = $iN->iN_UserAvatar($moderatorId, $base_url);
		include "../themes/$currentTheme/layouts/popup_alerts/communityModeratorEdit.php";
		exit;
	}
	/*Community Moderation Search*/
	if ($type == 'communityModerationSearch') {
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['key'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		if ((int)($communityData['owner_user_id'] ?? 0) !== (int)$userID && !$isAdmin) {
			exit;
		}
		$searchKey = $iN->iN_Secure($_POST['key']);
		if (mb_strlen($searchKey) < 2) {
			exit;
		}
		$moderationUsers = $iN->iN_GetCommunityModerationUsers($communityID, 200);
		$moderationUserIds = [];
		foreach ($moderationUsers as $moderationUser) {
			$moderationUserId = (int)($moderationUser['user_id'] ?? 0);
			if ($moderationUserId > 0) {
				$moderationUserIds[$moderationUserId] = true;
			}
		}
		$displayUsers = $iN->iN_SearchCommunityModerationCandidates($userID, $searchKey, 24);
		if (!empty($displayUsers)) {
			foreach ($displayUsers as $candidate) {
				$candidateUserId = (int)($candidate['iuid'] ?? 0);
				if ($candidateUserId <= 0) {
					continue;
				}
				$candidateUserName = $candidate['i_user_fullname'] ?: ($candidate['i_username'] ?? '');
				$candidateUserAvatarUrl = $iN->iN_UserAvatar($candidateUserId, $base_url);
				$isAdded = isset($moderationUserIds[$candidateUserId]);
				include "../themes/$currentTheme/layouts/community/communityModerationUserCard.php";
			}
		} else {
		echo '<div class="community_moderation_empty">' .
			iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.') .
			'</div>';
	}
	/*Community Moderator Search*/
	if ($type == 'communityModeratorSearch') {
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['key'])) {
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit;
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		if ((int)($communityData['owner_user_id'] ?? 0) !== (int)$userID && !$isAdmin) {
			exit;
		}
		$searchKey = $iN->iN_Secure($_POST['key']);
		if (mb_strlen($searchKey) < 2) {
			exit;
		}
		$moderatorUsers = $iN->iN_GetCommunityModerators($communityID, 200);
		$moderatorUserIds = [];
		foreach ($moderatorUsers as $moderatorRow) {
			$moderatorUserId = (int)($moderatorRow['user_id'] ?? 0);
			if ($moderatorUserId > 0) {
				$moderatorUserIds[$moderatorUserId] = true;
			}
		}
		$searchResults = $iN->iN_SearchCommunityModeratorCandidates($searchKey, 24);
		$rendered = false;
		if (!empty($searchResults)) {
			foreach ($searchResults as $candidate) {
				$candidateUserId = (int)($candidate['iuid'] ?? 0);
				if ($candidateUserId <= 0 || $candidateUserId === (int)($communityData['owner_user_id'] ?? 0)) {
					continue;
				}
				$candidateUserName = $candidate['i_user_fullname'] ?: ($candidate['i_username'] ?? '');
				$candidateUserAvatarUrl = $iN->iN_UserAvatar($candidateUserId, $base_url);
				$isAdded = isset($moderatorUserIds[$candidateUserId]);
				include "../themes/$currentTheme/layouts/community/communityModeratorUserCard.php";
				$rendered = true;
			}
		}
		if ($rendered) {
			exit;
		}
		echo '<div class="community_moderation_empty">' .
			iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.') .
			'</div>';
	}
		exit;
	}
	/*Community Moderation Add*/
	if ($type == 'communityModerationAdd') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit;
		}
		if ($logedIn != 1 || !isset($_POST['community_id'], $_POST['user_id'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$memberID = (int)$iN->iN_Secure($_POST['user_id']);
		$status = isset($_POST['status']) ? $iN->iN_Secure($_POST['status']) : 'restricted';
		if (!in_array($status, ['restricted', 'blocked'], true)) {
			$status = 'restricted';
		}
		$communityData = $iN->iN_GetCommunityById($communityID);
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$moderatorData = null;
		$canManageMembers = false;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
			$canManageMembers = !empty($moderatorData) && (string)($moderatorData['can_manage_members'] ?? '0') === '1';
		}
		if (!$communityData || (!$isOwner && !$isAdmin && !$canManageMembers)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		if ($memberID <= 0 || (int)($communityData['owner_user_id'] ?? 0) === $memberID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		if (!$iN->iN_CheckUserExist($memberID)) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$membership = $iN->iN_GetCommunityMembership($communityID, $memberID);
		$isMemberActive = $membership && (string)($membership['status'] ?? '') === 'active';
		$restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $memberID);
		$hasConnection = $iN->iN_CheckUserIsInFLWR($userID, $memberID)
			|| $iN->iN_CheckUserIsInFLWR($memberID, $userID);
		if (!$isAdmin && !$isMemberActive && !$hasConnection && $restrictionStatus === null) {
			echo json_encode(['status' => 'error', 'message' => $LANG['community_moderation_add_failed'] ?? 'Unable to add user.']);
			exit;
		}
		$iN->iN_SetCommunityRestriction($communityID, $memberID, $status, $userID);
		$memberControl = $iN->iN_GetCommunityMemberControl($communityID, $memberID);
		if (!$memberControl) {
			$iN->iN_SetCommunityMemberControl($communityID, $memberID, [], $userID);
		}
		if (!$isOwner && !$isAdmin && $canManageMembers) {
			$iN->iN_LogCommunityModeratorAction(
				$communityID,
				$userID,
				'member_status',
				['user_id' => $memberID],
				['previous' => ['status' => $restrictionStatus], 'next' => ['status' => $status]]
			);
			$iN->iN_InsertCommunityNotification(
				$userID,
				$memberID,
				$communityData,
				'community_restriction',
				null,
				['fields' => ['member_status' => $status]]
			);
		}
		$memberData = $iN->iN_GetUserDetails($memberID);
		$memberName = $memberData ? ($memberData['i_user_fullname'] ?: $memberData['i_username']) : '';
		$memberAvatarUrl = $iN->iN_UserAvatar($memberID, $base_url);
		$memberStatus = $status;
		$canModerateMembers = $isOwner || $isAdmin || $canManageMembers;
		$canModerateReshare = $isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_reshare'] ?? '0') === '1');
		$canModerateTimeout = (string)($communityData['moderation_timeout_enabled'] ?? '0') === '1'
			&& ($isOwner || $isAdmin || (!empty($moderatorData) && (string)($moderatorData['can_manage_view_timeout'] ?? '0') === '1'));
		$timeoutOptions = $canModerateTimeout ? $iN->iN_GetCommunityTimeoutOptions($communityData) : [];
		$memberReshareDisabled = false;
		$memberViewUntil = null;
		$memberViewPermanent = false;
		ob_start();
		include "../themes/$currentTheme/layouts/community/communityModerationRow.php";
		$moderationRow = ob_get_clean();
		echo json_encode([
			'status' => 'success',
			'message' => $LANG['community_moderation_add_success'] ?? 'User added.',
			'user_id' => $memberID,
			'row' => $moderationRow
		]);
		exit;
	}
	/*Upload Croped Image*/
	if ($type == 'communityCoverUpload') {
		if ($logedIn != 1 || !isset($_POST['image'], $_POST['community_id'])) {
			exit('404');
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit('404');
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$canManageMedia = false;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
			$canManageMedia = !empty($moderatorData) && (string)($moderatorData['can_manage_media'] ?? '0') === '1';
		}
		if (!$isOwner && !$isAdmin && !$canManageMedia) {
			exit('404');
		}
		$dataImage = $iN->iN_Secure($_POST['image']);
		$image_array_1 = explode(";", $dataImage);
		if (!isset($image_array_1[1])) {
			exit('404');
		}
		$image_array_2 = explode(",", $image_array_1[1]);
		if (!isset($image_array_2[1])) {
			exit('404');
		}
		$data = base64_decode($image_array_2[1]);
		$microtime = microtime();
		$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
		$UploadedFileName = "community_cover_" . $removeMicrotime . '_' . $communityID;
		$getFilename = $UploadedFileName . ".png";
		$ext = getExtension($getFilename);
		$valid_formats = explode(',', $availableFileExtensions);
		if (strlen($getFilename) && in_array($ext, $valid_formats, true)) {
			$d = date('Y-m-d');
			$uploadCommunity = rtrim($uploadRoot, '/') . '/communities/';
			$uploadDir = $uploadCommunity . $d . '/';
			if (!file_exists($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			if (file_put_contents($uploadDir . $getFilename, $data)) {
				$pathFile = 'uploads/communities/' . $d . '/' . $getFilename;
				if (function_exists('storage_publish_and_url')) {
					storage_publish_and_url($pathFile, [$pathFile], true);
				}
				$oldCoverPath = $communityData['cover_image'] ?? '';
				if (!empty($oldCoverPath) && $oldCoverPath !== $pathFile && strpos((string)$oldCoverPath, 'uploads/') === 0) {
					$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
					if ($useRemote && function_exists('storage_delete')) {
						@storage_delete((string)$oldCoverPath);
					} else {
						@unlink(dirname(__DIR__) . '/' . ltrim((string)$oldCoverPath, '/'));
					}
				}
				$iN->iN_UpdateCommunity($communityID, ['cover_image' => $pathFile]);
				$imgUrl = function_exists('storage_public_url') ? storage_public_url($pathFile) : $base_url . $pathFile;
				echo $imgUrl;
				exit;
			}
		}
		exit('404');
	}
	/*Upload Croped Image*/
	if ($type == 'communityAvatarUpload') {
		if ($logedIn != 1 || !isset($_POST['image'], $_POST['community_id'])) {
			exit('404');
		}
		$communityID = (int)$iN->iN_Secure($_POST['community_id']);
		$communityData = $iN->iN_GetCommunityById($communityID);
		if (!$communityData || (string)($communityData['status'] ?? '') !== 'active') {
			exit('404');
		}
		$isAdmin = isset($userType) && (string)$userType === '2';
		$isOwner = (int)($communityData['owner_user_id'] ?? 0) === (int)$userID;
		$canManageMedia = false;
		if (!$isOwner && !$isAdmin) {
			$moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
			$canManageMedia = !empty($moderatorData) && (string)($moderatorData['can_manage_media'] ?? '0') === '1';
		}
		if (!$isOwner && !$isAdmin && !$canManageMedia) {
			exit('404');
		}
		$dataImage = $iN->iN_Secure($_POST['image']);
		$image_array_1 = explode(";", $dataImage);
		if (!isset($image_array_1[1])) {
			exit('404');
		}
		$image_array_2 = explode(",", $image_array_1[1]);
		if (!isset($image_array_2[1])) {
			exit('404');
		}
		$data = base64_decode($image_array_2[1]);
		$microtime = microtime();
		$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
		$UploadedFileName = "community_avatar_" . $removeMicrotime . '_' . $communityID;
		$getFilename = $UploadedFileName . ".png";
		$ext = getExtension($getFilename);
		$valid_formats = explode(',', $availableFileExtensions);
		if (strlen($getFilename) && in_array($ext, $valid_formats, true)) {
			$d = date('Y-m-d');
			$uploadCommunity = rtrim($uploadRoot, '/') . '/communities/';
			$uploadDir = $uploadCommunity . $d . '/';
			if (!file_exists($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			if (file_put_contents($uploadDir . $getFilename, $data)) {
				$pathFile = 'uploads/communities/' . $d . '/' . $getFilename;
				if (function_exists('storage_publish_and_url')) {
					storage_publish_and_url($pathFile, [$pathFile], true);
				}
				$oldAvatarPath = $communityData['avatar_image'] ?? '';
				if (!empty($oldAvatarPath) && $oldAvatarPath !== $pathFile && strpos((string)$oldAvatarPath, 'uploads/') === 0) {
					$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
					if ($useRemote && function_exists('storage_delete')) {
						@storage_delete((string)$oldAvatarPath);
					} else {
						@unlink(dirname(__DIR__) . '/' . ltrim((string)$oldAvatarPath, '/'));
					}
				}
				$iN->iN_UpdateCommunity($communityID, ['avatar_image' => $pathFile]);
				$imgUrl = function_exists('storage_public_url') ? storage_public_url($pathFile) : $base_url . $pathFile;
				echo $imgUrl;
				exit;
			}
		}
		exit('404');
	}
	if ($type == 'coverUpload') {
		if (isset($_POST['image']) && $_POST['image'] != '' && !empty($_POST['image'])) {
			$dataImage = $iN->iN_Secure($_POST['image']);
			$image_array_1 = explode(";", $dataImage);
			$image_array_2 = explode(",", $image_array_1[1]);
			$data = base64_decode($image_array_2[1]);
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$UploadedFileName = "cover_" . $removeMicrotime . '_' . $userID;
			$getFilename = $UploadedFileName . ".png";
			$ext = getExtension($getFilename);
			$valid_formats = explode(',', $availableFileExtensions);
			if (strlen($getFilename)) {
				if (in_array($ext, $valid_formats)) {
					$d = date('Y-m-d');
					if (!file_exists($uploadCover . $d)) {
						$newFile = mkdir($uploadCover . $d, 0755);
					}
						if (file_put_contents($uploadCover . $d . '/' . $getFilename, $data)) {
							$pathFile = 'uploads/covers/' . $d . '/' . $getFilename;
							// Unified: publish and build URL for cover
							$relCover = 'uploads/covers/' . $d . '/' . $getFilename;
							$UploadSourceUrl = storage_publish_and_url($relCover, [$relCover], true);
							$previousCoverId = (int) (DB::col("SELECT user_cover FROM i_users WHERE iuid = ? LIMIT 1", [(int) $userID]) ?: 0);
							$coverData = $iN->iN_INSERTUploadedCoverPhoto($userID, $pathFile);
							if ($coverData) {
								if ($previousCoverId > 0 && $previousCoverId !== (int) $coverData) {
									$oldCoverPath = DB::col(
										"SELECT cover_path FROM i_user_covers WHERE iuid_fk = ? AND cover_id = ? LIMIT 1",
										[(int) $userID, (int) $previousCoverId]
									);
									if (!empty($oldCoverPath) && strpos((string) $oldCoverPath, 'uploads/') === 0) {
										$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
										if ($useRemote && function_exists('storage_delete')) {
											@storage_delete((string) $oldCoverPath);
										} else {
											@unlink(dirname(__DIR__) . '/' . ltrim((string) $oldCoverPath, '/'));
										}
									}
									DB::exec(
										"DELETE FROM i_user_covers WHERE iuid_fk = ? AND cover_id = ?",
										[(int) $userID, (int) $previousCoverId]
									);
								}
								$getUploadedFileID = $iN->iN_GetUploadedCoverURL($userID, $coverData);
								$imgUrl = storage_public_url($getUploadedFileID);
								echo $imgUrl;
							} else {
                exit($LANG['something_went_wrong']);
						}
					}
				}
			}

		}
	}
	/*Upload Croped Image*/
		if ($type == 'avatarUpload') {
			if (isset($_POST['image']) && $_POST['image'] != '' && !empty($_POST['image'])) {
				$dataImage = $iN->iN_Secure($_POST['image']);
			$image_array_1 = explode(";", $dataImage);
			$image_array_2 = explode(",", $image_array_1[1]);
			$data = base64_decode($image_array_2[1]);
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$UploadedFileName = "avatar_" . $removeMicrotime . '_' . $userID;
			$getFilename = $UploadedFileName . ".png";
			$ext = getExtension($getFilename);
			$valid_formats = explode(',', $availableFileExtensions);
			if (strlen($getFilename)) {
				if (in_array($ext, $valid_formats)) {
					$d = date('Y-m-d');
					if (!file_exists($uploadAvatar . $d)) {
						$newFile = mkdir($uploadAvatar . $d, 0755);
					}
	                        if (file_put_contents($uploadAvatar . $d . '/' . $getFilename, $data)) {
	                            $pathFile = 'uploads/avatars/' . $d . '/' . $getFilename;
	                            // Unified publish to active storage provider
	                            $relAvatar = 'uploads/avatars/' . $d . '/' . $getFilename;
	                            $UploadSourceUrl = storage_publish_and_url($relAvatar, [$relAvatar], true);
								$previousAvatarId = (int) (DB::col("SELECT user_avatar FROM i_users WHERE iuid = ? LIMIT 1", [(int) $userID]) ?: 0);
							$coverData = $iN->iN_INSERTUploadedAvatarPhoto($userID, $pathFile);
							if ($coverData) {
								if ($previousAvatarId > 0 && $previousAvatarId !== (int) $coverData) {
									$oldAvatarPath = DB::col(
										"SELECT avatar_path FROM i_user_avatars WHERE iuid_fk = ? AND avatar_id = ? LIMIT 1",
										[(int) $userID, (int) $previousAvatarId]
									);
									if (!empty($oldAvatarPath) && strpos((string) $oldAvatarPath, 'uploads/') === 0) {
										$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
										if ($useRemote && function_exists('storage_delete')) {
											@storage_delete((string) $oldAvatarPath);
										} else {
											@unlink(dirname(__DIR__) . '/' . ltrim((string) $oldAvatarPath, '/'));
										}
									}
									DB::exec(
										"DELETE FROM i_user_avatars WHERE iuid_fk = ? AND avatar_id = ?",
										[(int) $userID, (int) $previousAvatarId]
									);
								}
								$getUploadedFileID = $iN->iN_GetUploadedAvatarURL($userID, $coverData);
	                            $imgUrl = storage_public_url($getUploadedFileID);
								echo $imgUrl;
							} else {
                exit($LANG['something_went_wrong']);
						}
					}
				}
			}

		}
	}
	/*Check Email Valid or Exist*/
	if ($type == 'checkemail') {
		if (isset($_POST['newEmail']) && $_POST['newEmail'] != '' && !empty($_POST['newEmail'])) {
			$newEmail = $iN->iN_Secure($_POST['newEmail']);
			if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
				echo 'no';
				exit();
			} else {
				$checkEmail = $iN->iN_CheckEmail($userID, $newEmail);
				if ($checkEmail) {
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
	/*Update Email Address*/
	if ($type == 'editMyEmail') {
		if (isset($_POST['newEmail']) && $_POST['newEmail'] != '' && !empty($_POST['newEmail']) && isset($_POST['currentPass']) && $_POST['currentPass'] != '' && !empty($_POST['currentPass'])) {
			$newEmail = $iN->iN_Secure($_POST['newEmail']);
			$currentPassword = $iN->iN_Secure($_POST['currentPass']);
			if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
				echo 'no';
				exit();
			}
			if ($newEmail === $userEmail) {
				echo 'same';
				exit();
			}
			$checkEmail = $iN->iN_CheckEmail($userID, $newEmail);
			if (!$checkEmail) {
				echo '404';
				exit();
			}
			$Change = $iN->iN_CheckUserPasswordAndUpdateIfIsValid($userID, $currentPassword, $newEmail);
			if ($Change) {
				echo '200';
			} else {
				echo 'password';
			}
		}
	}
	if ($type == 'updatePayoutSet') {
		if (isset($_POST['method']) && in_array($_POST['method'], $defaultPayoutMethods, true)) {
			$defaultMethod = $iN->iN_Secure($_POST['method']);
			$paypalEmail = isset($_POST['paypalEmail']) ? rawurldecode($iN->iN_Secure($_POST['paypalEmail'])) : '';
			$re_paypalEmail = isset($_POST['paypalReEmail']) ? rawurldecode($iN->iN_Secure($_POST['paypalReEmail'])) : '';
			$bankAccount = isset($_POST['bank']) ? $iN->iN_Secure($_POST['bank']) : '';
			$payoneerEmail = isset($_POST['payoneerEmail']) ? rawurldecode($iN->iN_Secure($_POST['payoneerEmail'])) : '';
			$payoneerReEmail = isset($_POST['payoneerReEmail']) ? rawurldecode($iN->iN_Secure($_POST['payoneerReEmail'])) : '';
			$zelleEmail = isset($_POST['zelleEmail']) ? rawurldecode($iN->iN_Secure($_POST['zelleEmail'])) : '';
			$zelleReEmail = isset($_POST['zelleReEmail']) ? rawurldecode($iN->iN_Secure($_POST['zelleReEmail'])) : '';
			$westernUnionFullName = isset($_POST['westernUnionFullName']) ? $iN->iN_Secure($_POST['westernUnionFullName']) : '';
			$westernUnionDocumentId = isset($_POST['westernUnionDocumentId']) ? $iN->iN_Secure($_POST['westernUnionDocumentId']) : '';
			$bitcoinWallet = isset($_POST['bitcoinWallet']) ? $iN->iN_Secure($_POST['bitcoinWallet']) : '';
			$mercadoPagoAlias = isset($_POST['mercadoPagoAlias']) ? $iN->iN_Secure($_POST['mercadoPagoAlias']) : '';
			$mercadoPagoCvu = isset($_POST['mercadoPagoCvu']) ? $iN->iN_Secure($_POST['mercadoPagoCvu']) : '';
			$payoutMethodData = array();

			if ($defaultMethod === 'paypal') {
				if ($paypalEmail == '' || empty($paypalEmail)) {
					echo 'paypal_warning';
					exit();
				}
				if ($paypalEmail != $re_paypalEmail) {
					echo 'email_warning';
					exit();
				}
				if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
					echo 'not_valid_email';
					exit();
				}
			} elseif ($defaultMethod === 'payoneer') {
				if ($payoneerEmail == '' || empty($payoneerEmail)) {
					echo 'paypal_warning';
					exit();
				}
				if ($payoneerEmail != $payoneerReEmail) {
					echo 'email_warning';
					exit();
				}
				if (!filter_var($payoneerEmail, FILTER_VALIDATE_EMAIL)) {
					echo 'not_valid_email';
					exit();
				}
				$payoutMethodData['payoneer_email'] = $payoneerEmail;
			} elseif ($defaultMethod === 'zelle') {
				if ($zelleEmail == '' || empty($zelleEmail)) {
					echo 'paypal_warning';
					exit();
				}
				if ($zelleEmail != $zelleReEmail) {
					echo 'email_warning';
					exit();
				}
				if (!filter_var($zelleEmail, FILTER_VALIDATE_EMAIL)) {
					echo 'not_valid_email';
					exit();
				}
				$payoutMethodData['zelle_email'] = $zelleEmail;
			} elseif ($defaultMethod === 'western-union') {
				if (trim((string)$westernUnionFullName) === '' || trim((string)$westernUnionDocumentId) === '') {
					echo 'bank_warning';
					exit();
				}
				$payoutMethodData['western_union_full_name'] = $westernUnionFullName;
				$payoutMethodData['western_union_document_id'] = $westernUnionDocumentId;
			} elseif ($defaultMethod === 'bitcoin') {
				if (trim((string)$bitcoinWallet) === '') {
					echo 'bank_warning';
					exit();
				}
				$payoutMethodData['bitcoin_wallet_address'] = $bitcoinWallet;
			} elseif ($defaultMethod === 'mercadopago') {
				if (trim((string)$mercadoPagoAlias) === '' || trim((string)$mercadoPagoCvu) === '') {
					echo 'bank_warning';
					exit();
				}
				$payoutMethodData['mercadopago_alias'] = $mercadoPagoAlias;
				$payoutMethodData['mercadopago_cvu'] = $mercadoPagoCvu;
			} else {
				if (trim((string)$bankAccount) === '') {
					echo 'bank_warning';
					exit();
				}
				// Premium structured bank transfer fields
				$bankCountry       = trim((string)($_POST['bank_country'] ?? ''));
				$ibanNumber        = strtoupper(preg_replace('/\s+/', '', (string)($_POST['iban_number'] ?? '')));
				$routingNumber     = trim((string)($_POST['routing_number'] ?? ''));
				$accountNumber     = trim((string)($_POST['account_number'] ?? ''));
				$confirmAccount    = trim((string)($_POST['confirm_account_number'] ?? ''));
				$accountHolderName = trim((string)($_POST['account_holder_name'] ?? ''));
				$phoneCountryCode  = trim((string)($_POST['phone_country_code'] ?? ''));
				$phoneNumber       = trim((string)($_POST['phone_number'] ?? ''));
				$streetAddress     = trim((string)($_POST['street_address'] ?? ''));
				$btCountry         = trim((string)($_POST['country'] ?? ''));
				$btState           = trim((string)($_POST['state'] ?? ''));
				$btCity            = trim((string)($_POST['city'] ?? ''));
				$btPostal          = trim((string)($_POST['postal_code'] ?? ''));
				if ($bankCountry === '' || $accountNumber === '' || $accountHolderName === ''
					|| $phoneCountryCode === '' || $phoneNumber === '' || $streetAddress === ''
					|| $btCountry === '' || $btState === '' || $btCity === '' || $btPostal === '') {
					echo 'bank_warning';
					exit();
				}
				if ($accountNumber !== $confirmAccount) {
					echo 'bank_warning';
					exit();
				}
				$payoutMethodData['bank_country']         = $iN->iN_Secure($bankCountry);
				$payoutMethodData['iban_number']          = $iN->iN_Secure($ibanNumber);
				$payoutMethodData['routing_number']       = $iN->iN_Secure($routingNumber);
				$payoutMethodData['account_number']       = $iN->iN_Secure($accountNumber);
				$payoutMethodData['account_holder_name']  = $iN->iN_Secure($accountHolderName);
				$payoutMethodData['phone_country_code']   = $iN->iN_Secure($phoneCountryCode);
				$payoutMethodData['phone_number']         = $iN->iN_Secure($phoneNumber);
				$payoutMethodData['street_address']       = $iN->iN_Secure($streetAddress);
				$payoutMethodData['country']              = $iN->iN_Secure($btCountry);
				$payoutMethodData['state']                = $iN->iN_Secure($btState);
				$payoutMethodData['city']                 = $iN->iN_Secure($btCity);
				$payoutMethodData['postal_code']          = $iN->iN_Secure($btPostal);
			}

			$insertPayout = $iN->iN_UpdatePayout($userID, $paypalEmail, $bankAccount, $defaultMethod, $payoutMethodData);
			if ($insertPayout) {
				echo '200';
			}
		}
	}
/*Insert Subscription Amount if Amounts are not empty*/
if ($type === 'updateSubscriptionPayments') {
    $normalizeAmount = function ($value) use ($iN) {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $normalized = str_replace(',', '.', $value);
        if (!is_numeric($normalized)) {
            return null;
        }
        return $iN->iN_Secure($normalized, 1, false);
    };

    $toFloat = function ($value) {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace(',', '.', (string)$value);
        return is_numeric($normalized) ? (float)$normalized : null;
    };

    $weeklySubStatus = isset($_POST['wStatus']) && in_array($_POST['wStatus'], $statusValue, true) ? $_POST['wStatus'] : null;
    $monthlySubStatus = isset($_POST['mStatus']) && in_array($_POST['mStatus'], $statusValue, true) ? $_POST['mStatus'] : null;
    $yearlySubStatus = isset($_POST['yStatus']) && in_array($_POST['yStatus'], $statusValue, true) ? $_POST['yStatus'] : null;

    $SubWeekAmountRaw = isset($_POST['wSubWeekAmount']) ? $_POST['wSubWeekAmount'] : null;
    $SubMonthAmountRaw = isset($_POST['mSubMonthAmount']) ? $_POST['mSubMonthAmount'] : null;
    $SubYearAmountRaw = isset($_POST['mSubYearAmount']) ? $_POST['mSubYearAmount'] : null;

    $SubWeekAmount = $normalizeAmount($SubWeekAmountRaw);
    $SubMonthAmount = $normalizeAmount($SubMonthAmountRaw);
    $SubYearAmount = $normalizeAmount($SubYearAmountRaw);

    $SubWeekAmountFloat = $toFloat($SubWeekAmount ?? $SubWeekAmountRaw);
    $SubMonthAmountFloat = $toFloat($SubMonthAmount ?? $SubMonthAmountRaw);
    $SubYearAmountFloat = $toFloat($SubYearAmount ?? $SubYearAmountRaw);

    $existingWeeklyPlan = $iN->iN_GetUserSubscriptionPlanDetails($userID, 'weekly');
    $existingMonthlyPlan = $iN->iN_GetUserSubscriptionPlanDetails($userID, 'monthly');
    $existingYearlyPlan = $iN->iN_GetUserSubscriptionPlanDetails($userID, 'yearly');

    $weeklyResponse = $monthlyResponse = $yearlyResponse = null;
    $anySuccess = false;

    $weeklyMin = $subscriptionType === '2' ? (float)$minPointFeeWeekly : (float)$subscribeWeeklyMinimumAmount;
    $monthlyMin = $subscriptionType === '2' ? (float)$minPointFeeMonthly : (float)$subscribeMonthlyMinimumAmount;
    $yearlyMin = $subscriptionType === '2' ? (float)$minPointFeeYearly : (float)$subscribeYearlyMinimumAmount;

    if ($weeklySubStatus !== null) {
        if ($weeklySubStatus === '1') {
            if ($SubWeekAmountFloat !== null && $SubWeekAmountFloat >= $weeklyMin) {
                if ($iN->iN_UpdateWeeklySubscriptionAmountAndStatus($userID, $SubWeekAmount, $weeklySubStatus)) {
                    $weeklyResponse = '200';
                    $anySuccess = true;
                }
            } else {
                $weeklyResponse = '404';
            }
        } else {
            $amountToPersist = $SubWeekAmount ?? ($existingWeeklyPlan['amount'] ?? null);
            if ($amountToPersist !== null && $iN->iN_UpdateWeeklySubscriptionAmountAndStatus($userID, $amountToPersist, $weeklySubStatus)) {
                $weeklyResponse = '200';
                $anySuccess = true;
            }
        }
    }

    if ($monthlySubStatus !== null) {
        if ($monthlySubStatus === '1') {
            if ($SubMonthAmountFloat !== null && $SubMonthAmountFloat >= $monthlyMin) {
                if ($iN->iN_UpdateMonthlySubscriptionAmountAndStatus($userID, $SubMonthAmount, $monthlySubStatus)) {
                    $monthlyResponse = '200';
                    $anySuccess = true;
                }
            } else {
                $monthlyResponse = '404';
            }
        } else {
            $amountToPersist = $SubMonthAmount ?? ($existingMonthlyPlan['amount'] ?? null);
            if ($amountToPersist !== null && $iN->iN_UpdateMonthlySubscriptionAmountAndStatus($userID, $amountToPersist, $monthlySubStatus)) {
                $monthlyResponse = '200';
                $anySuccess = true;
            }
        }
    }

    if ($yearlySubStatus !== null) {
        if ($yearlySubStatus === '1') {
            if ($SubYearAmountFloat !== null && $SubYearAmountFloat >= $yearlyMin) {
                if ($iN->iN_UpdateYearlySubscriptionAmountAndStatus($userID, $SubYearAmount, $yearlySubStatus)) {
                    $yearlyResponse = '200';
                    $anySuccess = true;
                }
            } else {
                $yearlyResponse = '404';
            }
        } else {
            $amountToPersist = $SubYearAmount ?? ($existingYearlyPlan['amount'] ?? null);
            if ($amountToPersist !== null && $iN->iN_UpdateYearlySubscriptionAmountAndStatus($userID, $amountToPersist, $yearlySubStatus)) {
                $yearlyResponse = '200';
                $anySuccess = true;
            }
        }
    }

    if ($anySuccess) {
        $iN->iN_UpdateUserFeeStatus($userID);
    }

    $response = array_filter([
        'weekly' => $weeklyResponse,
        'monthly' => $monthlyResponse,
        'yearly' => $yearlyResponse,
    ], function ($value) {
        return $value !== null;
    });

    echo json_encode($response);
}
	/*Inser Withdrawal*/
		if ($type == 'makewithDraw') {
			if (isset($_POST['amount']) && !empty($_POST['amount']) && $_POST['amount'] != '') {
				$withdrawalAmount = $iN->iN_Secure($_POST['amount']);
				$checkHavePendingWithdrawal = $iN->iN_CheckUserHavePendingWithdrawal($userID);
			if ($checkHavePendingWithdrawal) {
				echo '5';
				exit();
				}
				if ($withdrawalAmount >= $minimumWithdrawalAmount) {
					if ($userWallet >= $withdrawalAmount) {
						$selectedPayoutMethod = isset($payoutMethod) ? (string)$payoutMethod : '';
						if (!in_array($selectedPayoutMethod, $defaultPayoutMethods, true)) {
							$selectedPayoutMethod = in_array('bank', $defaultPayoutMethods, true)
								? 'bank'
								: (isset($defaultPayoutMethods[0]) ? (string)$defaultPayoutMethods[0] : '');
						}
							if ($selectedPayoutMethod === '') {
								echo '4';
								exit();
							}
							$payoutAddressData = array();
							if ($selectedPayoutMethod === 'paypal') {
								$payoutAddressData['paypal_email'] = isset($paypalEmail) ? (string)$paypalEmail : '';
							} elseif ($selectedPayoutMethod === 'bank') {
								$payoutAddressData['bank_account'] = isset($bankAccount) ? (string)$bankAccount : '';
							} elseif ($selectedPayoutMethod === 'payoneer') {
								$payoutAddressData['payoneer_email'] = isset($payoneerEmail) ? (string)$payoneerEmail : '';
							} elseif ($selectedPayoutMethod === 'zelle') {
								$payoutAddressData['zelle_email'] = isset($zelleEmail) ? (string)$zelleEmail : '';
							} elseif ($selectedPayoutMethod === 'western-union') {
								$payoutAddressData['western_union_full_name'] = isset($westernUnionFullName) ? (string)$westernUnionFullName : '';
								$payoutAddressData['western_union_document_id'] = isset($westernUnionDocumentId) ? (string)$westernUnionDocumentId : '';
							} elseif ($selectedPayoutMethod === 'bitcoin') {
								$payoutAddressData['bitcoin_wallet_address'] = isset($bitcoinWalletAddress) ? (string)$bitcoinWalletAddress : '';
							} elseif ($selectedPayoutMethod === 'mercadopago') {
								$payoutAddressData['mercadopago_alias'] = isset($mercadoPagoAlias) ? (string)$mercadoPagoAlias : '';
								$payoutAddressData['mercadopago_cvu'] = isset($mercadoPagoCvu) ? (string)$mercadoPagoCvu : '';
							}
							$insertWithdrawal = $iN->iN_InsertWithdrawal($userID, $withdrawalAmount, $selectedPayoutMethod, 'withdrawal', $payoutAddressData);
							if ($insertWithdrawal) {
								echo '1';
							} else {
							echo '4';
						}
				} else {
					echo '3';
				}
			} else {
				echo '2';
			}
		}
	}
	if ($type == 'pPurchase') {
		if (isset($_POST['purchase']) && $_POST['purchase'] != '' && !empty($_POST['purchase'])) {
			$purchaseingPostID = $iN->iN_Secure($_POST['purchase']);
			$getPurchasingPostDetails = $iN->iN_GetAllPostDetails($purchaseingPostID);
			if ($getPurchasingPostDetails) {
				$userPostID = $getPurchasingPostDetails['post_id'];
				$userPostFile = $getPurchasingPostDetails['post_file'];
				$userPostOwnerID = $getPurchasingPostDetails['post_owner_id'];
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostOwnerUsername = $getPurchasingPostDetails['i_username'];
				$userPostOwnerUserFullName = $getPurchasingPostDetails['i_user_fullname'];
				$userPostWantedCredit = $getPurchasingPostDetails['post_wanted_credit'];
				include "../themes/$currentTheme/layouts/popup_alerts/purchase_premium_post.php";
			}
		}
	}
/*Purchase Post*/
	if ($type == 'goWallet') {
		if (isset($_POST['p'])) {
			$PurchasePostID = $iN->iN_Secure($_POST['p']);
			$checkPostID = $iN->iN_CheckPostIDExist($PurchasePostID);
			if ($checkPostID) {
				$getPurchasingPostDetails = $iN->iN_GetAllPostDetails($PurchasePostID);
				$userPostID = $getPurchasingPostDetails['post_id'];
				$userPostWantedCredit = $getPurchasingPostDetails['post_wanted_credit'];
				$userPostOwnerID = $getPurchasingPostDetails['post_owner_id'];
				$postType = $getPurchasingPostDetails['post_type'];

				$translatePointToMoney = $userPostWantedCredit * $onePointEqual;
				$adminEarning = $translatePointToMoney * ($adminFee / 100);
				$userEarning = $translatePointToMoney - $adminEarning;

				if ($userCurrentPoints >= $userPostWantedCredit && $userID != $userPostOwnerID) {
					$buyPost = $iN->iN_BuyPost($userID, $userPostOwnerID, $PurchasePostID, $translatePointToMoney, $adminEarning, $userEarning, $adminFee, $userPostWantedCredit);
					if ($buyPost) {
						$approveNot = $LANG['congratulations_you_sold'];
						$iN->iN_SendNotificationForPurchasedPost($userID, $PurchasePostID, $userPostOwnerID,  $approveNot);
						$uData = $iN->iN_GetUserDetails($userPostOwnerID);
						$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
						$lUsername = $uData['i_username'];
						$lUserFullName = $uData['i_user_fullname'];
						$emailNotificationStatus = $uData['email_notification_status'];
						$notQualifyDocument = $LANG['not_qualify_document'];

						if($postType === 'reels'){
						    $slugUrl = $base_url . 'reels/' . $getPurchasingPostDetails['url_slug'];
						    echo iN_HelpSecure($slugUrl);
						}else{
						    $slugUrl = $base_url . 'post/' . $getPurchasingPostDetails['url_slug'] . '_' . $userPostID;
						    echo iN_HelpSecure($slugUrl);
						}
						if ($emailSendStatus == '1' && $userID != $userPostOwnerID && $emailNotificationStatus == '1') {

							if ($smtpOrMail == 'mail') {
								$mail->IsMail();
							} else if ($smtpOrMail == 'smtp') {
								$mail->isSMTP();
								$mail->Host = $smtpHost; // Specify main and backup SMTP servers
								$mail->SMTPAuth = true;
								$mail->SMTPKeepAlive = true;
								$mail->Username = $smtpUserName; // SMTP username
								$mail->Password = $smtpPassword; // SMTP password
								$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
								$mail->Port = $smtpPort;
								$mail->SMTPOptions = array(
									'ssl' => array(
										'verify_peer' => false,
										'verify_peer_name' => false,
										'allow_self_signed' => true,
									),
								);
							} else {
								return false;
							}
							$instagramIcon = $iN->iN_SelectedMenuIcon('88');
							$facebookIcon = $iN->iN_SelectedMenuIcon('90');
							$twitterIcon = $iN->iN_SelectedMenuIcon('34');
							$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
							$someoneBoughtYourPost = $iN->iN_Secure($LANG['someone_bought_your_post']);
							$clickGoPost = $iN->iN_Secure($LANG['click_go_post']);
							$youEarnMoney = $iN->iN_Secure($LANG['you_earn_money']);
							include_once '../includes/mailTemplates/postBoughtEmailTemplate.php';
							$body = $bodyPostBoughtEmailTemplate;
							$mail->setFrom($smtpEmail, $siteName);
							$send = false;
							$mail->IsHTML(true);
							$mail->addAddress($sendEmail, ''); // Add a recipient
							$mail->Subject = $iN->iN_Secure($LANG['someone_bought_your_post']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if (iN_safeMailSend($mail, $smtpOrMail, 'post_purchase_notification')) {
					$mail->ClearAddresses();
					return true;
				}

						}
					} else {
						echo $LANG['something_wrong'];
					}
				} else {
					exit (iN_HelpSecure($base_url) . 'purchase/purchase_point');
				}
			}
		}
	}
    /*Choose Payment Method*/
	if ($type == 'choosePaymentMethod') {
		if (isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type'])) {
			$planID = $iN->iN_Secure($_POST['type']);
			$checkPlanExist = $iN->CheckPlanExist($planID);
			if ($checkPlanExist) {
				$planData = $iN->GetPlanDetails($planID);
				$planAmount = $planData['amount'];
				$planPoint = $planData['plan_amount'];
				if($stripePaymentCurrency == 'JPY'){
                     $planAmount = $planAmount / 100;
				}
				require_once '../includes/payment/vendor/autoload.php';
				if (!defined('INORA_METHODS_CONFIG')) {
					define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
				}
				$configData = configItem();
					$DataUserDetails = [
						'amounts' => [ // at least one currency amount is required
							$payPalCurrency => $planAmount,
							$iyziCoPaymentCurrency => $planAmount,
							$bitPayPaymentCurrency => $planAmount,
							$autHorizePaymentCurrency => $planAmount,
							$payStackPaymentCurrency => $planAmount,
							$stripePaymentCurrency => $planAmount,
							$razorPayPaymentCurrency => $planAmount,
							$mercadoPagoCurrency => $planAmount,
							$paysafecardCurrency => $planAmount,
							$flutterWaveCurrency => $planAmount
						],
					'order_id' => 'ORDS' . uniqid(), // required in instamojo, Iyzico, Paypal, Paytm gateways
					'customer_id' => 'CUSTOMER' . uniqid(), // required in Iyzico, Paytm gateways
					'item_name' => $LANG['point_purchasing'], // required in Paypal gateways
					'item_qty' => 1,
					'item_id' => 'ITEM' . uniqid(), // required in Iyzico, Paytm gateways
					'payer_email' => $userEmail, // required in instamojo, Iyzico, Stripe gateways
					'payer_name' => $userFullName, // required in instamojo, Iyzico gateways
					'description' => $LANG['point_purchasing_from'], // Required for stripe
					'ip_address' => getUserIpAddr(), // required only for iyzico
					'address' => '3234 Godfrey Street Tigard, OR 97223', // required in Iyzico gateways
					'city' => 'Tigard', // required in Iyzico gateways
					'country' => 'United States', // required in Iyzico gateways
					'payment_type' => 'point',
					'payer_id' => (int) $userID,
					'credit_plan_id' => (int) $planID,
					'order_amount' => $planAmount,
				];
				$PublicConfigs = getPublicConfigItem();

				$configItem = $configData['payments']['gateway_configuration'];

				// Get config data
				$configa = getPublicConfigItem();
				// Get app URL
				$paymentPagePath = getAppUrl();

				$gatewayConfiguration = $configData['payments']['gateway_configuration'];
				// get paystack config data
				$paystackConfigData = $gatewayConfiguration['paystack'];
				// Get paystack callback ur
				$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

				// Get stripe config data
				$stripeConfigData = $gatewayConfiguration['stripe'];
				// Get stripe callback ur
				$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

				// Get razorpay config data
				$razorpayConfigData = $gatewayConfiguration['razorpay'];
				// Get razorpay callback url
				$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

				// Get Authorize.Net config Data
				$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
				// Get Authorize.Net callback url
				$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

				// Get Flutterwave config data
				$flutterwaveConfigData = $gatewayConfiguration['flutterwave'];
				$flutterwaveCallbackUrl = getAppUrl($flutterwaveConfigData['callbackUrl']);

				// Individual payment gateway url
				$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
				// User Details Configurations FINISHED
				include "../themes/$currentTheme/layouts/popup_alerts/paymentMethods.php";
			}
		}
	}
	if ($type == 'process') {
		require_once '../includes/payment/vendor/autoload.php';
		if (!defined('INORA_METHODS_CONFIG')) {
			define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
		}
		include "../includes/payment/payment-process.php";
	}
	    if ($type == 'tip_payment_methods' || $type == 'campaign_payment_methods') {
	        if (isset($_POST['tip_u']) && isset($_POST['tipVal'])) {
	            $isCampaignPayment = $type === 'campaign_payment_methods';
	            $tipingUserID = (int) $iN->iN_Secure($_POST['tip_u']);
	            $tipPostID = isset($_POST['tpid']) ? (int) $iN->iN_Secure($_POST['tpid']) : 0;
	            $tipAmountRaw = (string) $_POST['tipVal'];
	            $tipAmount = rq_parse_amount_value($tipAmountRaw);
	            $isAnonymous = isset($_POST['donate_anonymous']) && (string) $iN->iN_Secure($_POST['donate_anonymous']) === '1' ? 1 : 0;

	            $paymentType = 'tips';
	            $orderKey = 'TIP' . uniqid();
	            $orderAmount = (float) $tipAmount * (float) $onePointEqual;
	            $tipItemName = $LANG['send_your_tip'];
	            $tipDescription = $LANG['send_your_tip'];
	            $tipReceiverDetails = null;

                if ($isCampaignPayment) {
                    if ($tipPostID <= 0 || $tipAmount <= 0) {
                        exit('404');
                    }
                    $tipAmountMoney = (float) $tipAmount * (float) $onePointEqual;
                    $campaignRow = DB::one(
                        "SELECT campaign_id, owner_uid_fk, title, min_amount, max_amount, status, deadline_at FROM i_campaigns WHERE post_id_fk = ? LIMIT 1",
                        [$tipPostID]
	                );
	                if (!$campaignRow) {
	                    exit('404');
	                }
	                $campaignOwnerId = (int) ($campaignRow['owner_uid_fk'] ?? 0);
	                if ($campaignOwnerId <= 0 || $campaignOwnerId === (int) $userID) {
	                    exit('404');
	                }
	                $campaignStatusRow = isset($campaignRow['status']) ? (string)$campaignRow['status'] : '';
	                $campaignDeadlineTs = isset($campaignRow['deadline_at']) ? (int)$campaignRow['deadline_at'] : 0;
	                if ($campaignStatusRow === 'expired' || ($campaignDeadlineTs > 0 && $campaignDeadlineTs < time())) {
	                    exit('404');
                    }
                    $minAllowed = isset($campaignRow['min_amount']) && $campaignRow['min_amount'] !== null ? rq_parse_amount_value($campaignRow['min_amount']) : 0.0;
                    $maxAllowed = isset($campaignRow['max_amount']) && $campaignRow['max_amount'] !== null ? rq_parse_amount_value($campaignRow['max_amount']) : 0.0;
                    if (($minAllowed > 0 && $tipAmountMoney < $minAllowed) || ($maxAllowed > 0 && $tipAmountMoney > $maxAllowed)) {
                        exit('404');
                    }

	                $tipingUserID = $campaignOwnerId;
	                $tipReceiverDetails = $iN->iN_GetUserDetails($tipingUserID);
	                if (!$tipReceiverDetails) {
	                    exit('404');
                    }
                    $paymentType = 'campaign_donate';
                    $orderKey = 'CMD' . uniqid();
                    $orderAmount = (float) $tipAmountMoney;
                    $campaignTitle = trim(strip_tags((string)($campaignRow['title'] ?? '')));
                    $tipItemName = $LANG['campaign_donate_title'] ?? 'Donate to this campaign';
                    if ($campaignTitle !== '') {
	                    $tipItemName = $campaignTitle;
	                }
	                $tipDescription = $LANG['campaign_donate_send'] ?? 'Send donation';
	            } else {
	                $tipReceiverDetails = $iN->iN_GetUserDetails($tipingUserID);
	                if (!$tipReceiverDetails || $tipAmount <= 0 || ($minimumTipAmount && $tipAmount < $minimumTipAmount)) {
	                    exit('404');
	                }
	                $tipReceiverName = trim(strip_tags($iN->iN_UserFullName($tipingUserID)));
	                if ($tipReceiverName !== '') {
	                    if (isset($LANG['payment_item_tip_to_user'])) {
	                        $tipItemName = str_replace('{receiver}', $tipReceiverName, $LANG['payment_item_tip_to_user']);
	                    }
	                    if (isset($LANG['payment_desc_tip_to_user'])) {
	                        $tipDescription = str_replace('{receiver}', $tipReceiverName, $LANG['payment_desc_tip_to_user']);
	                    }
	                }
	            }

	            $planID = ($isCampaignPayment ? 'campaign-' : 'tip-') . $tipingUserID . '-' . uniqid();
	            $planPoint = $tipAmount;
	            $planAmount = $orderAmount;

	            require_once '../includes/payment/vendor/autoload.php';
	            if (!defined('INORA_METHODS_CONFIG')) {
	                define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
	            }
	            require_once INORA_METHODS_CONFIG;
	            $configData = configItem();

	            if ($stripePaymentCurrency == 'JPY') {
	                $orderAmount = round($orderAmount);
	                $planAmount = $orderAmount;
	            } else {
	                $orderAmount = (float) number_format($orderAmount, 2, '.', '');
	                $planAmount = $orderAmount;
	            }

	            $DataUserDetails = [
	                'amounts' => [
	                    $payPalCurrency => $planAmount,
	                    $iyziCoPaymentCurrency => $planAmount,
	                    $bitPayPaymentCurrency => $planAmount,
	                    $autHorizePaymentCurrency => $planAmount,
	                    $payStackPaymentCurrency => $planAmount,
	                    $stripePaymentCurrency => $planAmount,
	                    $razorPayPaymentCurrency => $planAmount,
	                    $mercadoPagoCurrency => $planAmount,
	                    $paysafecardCurrency => $planAmount,
	                    $flutterWaveCurrency => $planAmount
	                ],
	                'order_id' => $orderKey,
	                'customer_id' => 'CUSTOMER' . uniqid(),
	                'item_name' => $tipItemName,
	                'item_qty' => 1,
	                'item_id' => 'ITEM' . uniqid(),
	                'payer_email' => $userEmail,
	                'payer_name' => $userFullName,
	                'description' => $tipDescription,
	                'ip_address' => getUserIpAddr(),
	                'address' => '3234 Godfrey Street Tigard, OR 97223',
	                'city' => 'Tigard',
	                'country' => 'United States',
	                'payment_type' => $paymentType,
	                'payed_user_id' => (int) $tipingUserID,
	                'payed_post_id' => $tipPostID > 0 ? (int) $tipPostID : null,
	                'payer_id' => (int) $userID,
	                'order_amount' => $planAmount,
	                'is_anonymous' => $isCampaignPayment ? $isAnonymous : 0
	            ];
	            $PublicConfigs = getPublicConfigItem();

	            $configItem = $configData['payments']['gateway_configuration'];
	            $paymentPagePath = getAppUrl();

	            $gatewayConfiguration = $configData['payments']['gateway_configuration'];
	            $paystackConfigData = $gatewayConfiguration['paystack'];
	            $paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

	            $stripeConfigData = $gatewayConfiguration['stripe'];
	            $stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

	            $razorpayConfigData = $gatewayConfiguration['razorpay'];
	            $razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

	            $authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
	            $authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

	            $flutterwaveConfigData = $gatewayConfiguration['flutterwave'];
	            $flutterwaveCallbackUrl = getAppUrl($flutterwaveConfigData['callbackUrl']);

	            $individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
	            include "../themes/$currentTheme/layouts/popup_alerts/paymentMethods.php";
	        }
	    }
/*Get Gifs*/
	if ($type == 'chat_gifs') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			include "../themes/$currentTheme/layouts/chat/gifs.php";
		}
	}
/*Get Stickers*/
	if ($type == 'chat_stickers') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			include "../themes/$currentTheme/layouts/chat/stickers.php";
		}
	}
/*Get Stickers*/
	if ($type == 'chat_btns') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			include "../themes/$currentTheme/layouts/chat/chat_btns.php";
		}
	}
/*Get Emojis*/
	if ($type == 'memoji') {
		if (isset($_POST['id'])) {
			$id = $iN->iN_Secure($_POST['id']);
			$importID = '';
			$importClass = 'emoji_item_m';
			include "../themes/$currentTheme/layouts/chat/emojis.php";
		}
	}
/*Insert New Message*/
	if ($type == 'nmessage') {
		if (isset($_POST['id']) && isset($_POST['val'])) {
			$message = $iN->iN_Secure($_POST['val']);
			$chatID = $iN->iN_Secure($_POST['id']);
			$sticker = $iN->iN_Secure($_POST['sticker']);
			$gifSrc = $iN->iN_Secure($_POST['gif']);
			$fileIDs = $iN->iN_Secure($_POST['fl'] ?? '');
			$trimMoney = $iN->iN_Secure($_POST['mo']);
			$mMoney = trim($trimMoney);
			$file = isset($fileIDs) ? $fileIDs : NULL;
			$checkChatIDExist = $iN->iN_CheckChatIDExist($chatID);
			$getStickerURL = $iN->iN_getSticker($sticker);
			$stickerURL = isset($getStickerURL['sticker_url']) ? $getStickerURL['sticker_url'] : NULL;
			$gifUrl = isset($gifSrc) ? $gifSrc : NULL;
			if(!empty($mMoney) || $mMoney != ''){
				if(empty($message) && empty($file)){
                   exit('403');
				}
				if($minimumPointLimit > $mMoney){
				  exit('404');
				}
			 }
			if (empty($message)) {
				if (empty($stickerURL)) {
					if (empty($gifUrl)) {
						if (empty($file)) {
							exit('404');
						}
					}
				}
			}

			if ($checkChatIDExist) {
				$insertData = $iN->iN_InsertNewMessage($userID, $chatID, $iN->iN_Secure($message), $iN->iN_Secure($stickerURL), $iN->iN_Secure($gifUrl), $iN->iN_Secure($file), $iN->iN_Secure($mMoney));
				/**/
				if ($insertData) {
					$cMessageID = $insertData['con_id'];
					$cUserOne = $insertData['user_one'];
					$cUserTwo = $insertData['user_two'];
					$cMessage = $insertData['message'];
					$mSeenStatus = $insertData['seen_status'];
					$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
					$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				    $privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
					$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
					$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
					$cMessageTime = $insertData['time'];
					$ip = $iN->iN_GetIPAddress();
					$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
					if ($query && $query['status'] == 'success') {
						date_default_timezone_set($query['timezone']);
					}
					$message_time = date("c", $cMessageTime);
					$convertMessageTime = strtotime($message_time);
					$netMessageHour = date('H:i', $convertMessageTime);
					$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
					$msgDots = '';
					$imStyle = '';
					$seenStatus = '';
					if ($cUserOne == $userID) {
						$mClass = 'me';
						$msgOwnerID = $cUserOne;
						$lastM = '';
						$timeStyle = 'msg_time_me';
						if (!empty($cFile)) {
							$imStyle = 'mmi_i';
						}
						$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						if ($mSeenStatus == '1') {
							$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						}
					} else {
						$mClass = 'friend';
						$msgOwnerID = $cUserOne;
						$lastM = 'mm_' . $msgOwnerID;
						if (!empty($cFile)) {
							$imStyle = 'mmi_if';
						}
						$timeStyle = 'msg_time_fri';
					}
					$styleFor = '';
					if ($cStickerUrl) {
						$styleFor = 'msg_with_sticker';
						$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
					}
					if ($cGifUrl) {
						$styleFor = 'msg_with_gif';
						$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
					}
					$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
					include "../themes/$currentTheme/layouts/chat/newMessage.php";
				}
				/**/
			} else {
				echo '404';
			}
		}
	}
	/* Insert Live Message */
	if ($type == 'livemessage') {
		if ((isset($liveChatStatus) ? (string)$liveChatStatus : '1') !== '1') {
			exit('404');
		}
	    if (
	        isset($_POST['val']) && !empty($_POST['val']) &&
	        isset($_POST['id']) && !empty($_POST['id']) &&
        trim($_POST['val']) !== '' && trim($_POST['id']) !== ''
    ) {
        $liveID = $iN->iN_Secure($_POST['id']);
	        $liveMessageRaw = rawurldecode((string) $_POST['val']);
        $liveMessage = $iN->iN_Secure($liveMessageRaw);

        if (empty($liveMessage) || trim($liveMessage) == '') {
            exit('404');
        }

        $lmData = $iN->iN_InsertLiveMessage($liveID, $iN->iN_Secure($liveMessage), $userID);

        if ($lmData) {
            $messageID = $lmData['cm_id'];
            $messageLiveID = $lmData['cm_live_id'];
            $messageLiveUserID = $lmData['cm_iuid_fk'];
            $messageLiveTime = $lmData['cm_time'];
	            $liveMessage = rawurldecode((string) ($lmData['cm_message'] ?? '')); // decode again from DB (if needed)

            $msgData = $iN->iN_GetUserDetails($messageLiveUserID);
            $msgUserName = $msgData['i_username'];
            $msgUserFullName = $msgData['i_user_fullname'];

            // Only return message block, but avoid double-append in JS
            echo '
            <div class="gElp9 flex_ tabing_non_justify eo2As cUq_' . iN_HelpSecure($messageID) . '" id="' . iN_HelpSecure($messageID) . '">
                <a href="' . iN_HelpSecure($msgUserName) . '">' . iN_HelpSecure($msgUserFullName) . '</a>' . $iN->sanitize_output($liveMessage, $base_url) . '
            </div>';
        }
    }
}
/*Add Sticker*/
	if ($type == 'message_sticker') {
		if (isset($_POST['id']) && isset($_POST['pi'])) {
			$stickerID = $iN->iN_Secure($_POST['id']);
			$chatID = $iN->iN_Secure($_POST['pi']);
			$getStickerUrlandID = $iN->iN_getSticker($stickerID);
			if ($getStickerUrlandID) {
				$data = array(
					'st_id' => $getStickerUrlandID['sticker_id'],
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			}
		}
	}
	if ($type == 'message_image_upload') {
		// Unified message/chat upload (removes provider-specific putObject/SpacesConnect)
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == 'POST') {
			foreach ($_FILES['ciuploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['ciuploading']['name'][$iname]);
				$size = $_FILES['ciuploading']['size'][$iname];
				$conID = $iN->iN_Secure($_POST['c']);
				$ext = strtolower(getExtension($name));
				$valid_formats = explode(',', $availableFileExtensions);
				$maxUploadSizeInBytes = $availableUploadFileSize * 1048576;
				if (!in_array($ext, $valid_formats)) { continue; }
				if (!($size > 0 && $size <= $maxUploadSizeInBytes)) { echo iN_HelpSecure($size); continue; }

				$microtime = microtime();
				$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
				$UploadedFileName = 'image_' . $removeMicrotime . '_' . $userID;
				$getFilename = $UploadedFileName . '.' . $ext;
				$tmp = $_FILES['ciuploading']['tmp_name'][$iname];
				$mimeType = $_FILES['ciuploading']['type'][$iname];
				$d = date('Y-m-d');

				if (preg_match('/video\/*/', $mimeType) || $mimeType === 'application/octet-stream') { $fileTypeIs = 'video'; }
				else if (preg_match('/image\/*/', $mimeType)) { $fileTypeIs = 'Image'; }
				else { $fileTypeIs = 'Image'; }

				if (!file_exists($uploadFile . $d)) { @mkdir($uploadFile . $d, 0755, true); }
				if (!file_exists($xImages . $d)) { @mkdir($xImages . $d, 0755, true); }
				if (!file_exists($xVideos . $d)) { @mkdir($xVideos . $d, 0755, true); }

				if (!move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) { continue; }

				$pathFile = '';
				$pathXFile = '';

				if ($fileTypeIs === 'video') {
					if ($ffmpegCanConvert) {
						require_once '../includes/convertToMp4Format.php';
						require_once '../includes/createVideoThumbnail.php';
						$sourceFs = $uploadFile . $d . '/' . $getFilename;
							$convertedFs = convertToMp4Format($ffmpegPath, $sourceFs, $uploadFile . $d, $UploadedFileName);
							if (!$convertedFs || !file_exists($convertedFs)) {
								if (function_exists('rq_debug')) {
									rq_debug('message_upload:convertToMp4Format_failed', ['src' => $sourceFs, 'ffmpeg' => $ffmpegPath]);
								}
								$convertedFs = $sourceFs;
							}
							$thumbFs = createVideoThumbnailInSameDir($ffmpegPath, $convertedFs);
							$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
							$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
							if (!file_exists('../uploads/xvideos/' . $d)) { @mkdir('../uploads/xvideos/' . $d, 0755, true); }
							$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
							$ffmpegExec = escapeshellcmd($ffmpegPath ?: 'ffmpeg');
							$safeCmd = $ffmpegExec . ' -hide_banner -loglevel error -y'
								 . ' -ss 00:00:01 -i ' . escapeshellarg($convertedFs)
							  . ' -c copy -movflags +faststart -avoid_negative_ts make_zero'
							  . ' -t 00:00:04 ' . escapeshellarg($xVideoFirstPath) . ' 2>&1';
							shell_exec($safeCmd);
						$publishKeys = [];
						$mp4Key = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
						$xclipKey = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
						$thumbJpg = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
						$thumbPng = 'uploads/files/' . $d . '/' . $UploadedFileName . '.png';
						if (is_file('../' . $mp4Key)) { $publishKeys[] = $mp4Key; }
						if (is_file('../' . $xclipKey)) { $publishKeys[] = $xclipKey; }
						if (is_file('../' . $thumbJpg)) { $publishKeys[] = $thumbJpg; }
						if (is_file('../' . $thumbPng)) { $publishKeys[] = $thumbPng; }
						if ($publishKeys) { storage_publish_many($publishKeys, true, true); }
						$ext = 'mp4';
					} else {
						$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
						$pathXFile = $pathFile;
						storage_publish_many([$pathFile], true, true);
					}
				} else { // image
					$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
					$pathXFile = $pathFile;
					storage_publish_many([$pathFile], true, true);
				}

				$insertFileFromUploadTable = $iN->iN_INSERTUploadedMessageFiles($userID, $conID, $pathFile, $pathXFile, $ext);
				$getUploadedFileID = $iN->iN_GetUploadedMessageFilesIDs($userID, $pathFile);
				echo iN_HelpSecure($getUploadedFileID['upload_id']) . ',';
			}
			// Stop executing legacy message upload code below
			exit;
		}
	}

/*Load More Messages*/
	if ($type == 'moreMessage') {
		if (isset($_POST['ch']) && isset($_POST['last'])) {
			$chatID = $iN->iN_Secure($_POST['ch']);
			$lastMessageID = $iN->iN_Secure($_POST['last']);
			$conversationData = $iN->iN_GetChatMessages($userID, $chatID, $lastMessageID, $scrollLimit);
			include "../themes/$currentTheme/layouts/chat/loadMoreMessages.php";
		}
	}
/*Get new Message*/
	if ($type == 'getNewMessage') {
		if (isset($_POST['ci']) && isset($_POST['to']) && isset($_POST['lm'])) {
			$conversationID = $iN->iN_Secure($_POST['ci']);
			$toUser = $iN->iN_Secure($_POST['to']);
			$lastMessage = $iN->iN_Secure($_POST['lm']);
			$insertData = $iN->iN_GetUserNewMessage($userID, $conversationID, $toUser, $lastMessage);
			/**/
			if ($insertData) {
				$cMessageID = $insertData['con_id'];
				$cUserOne = $insertData['user_one'];
				$cUserTwo = $insertData['user_two'];
				$cMessage = $insertData['message'];
				$mSeenStatus = $insertData['seen_status'];
				$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
				$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				$privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
				$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
				$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
				$cMessageTime = $insertData['time'];
				$ip = $iN->iN_GetIPAddress();
				$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
				if ($query && $query['status'] == 'success') {
					date_default_timezone_set($query['timezone']);
				}
				$message_time = date("c", $cMessageTime);
				$convertMessageTime = strtotime($message_time);
				$netMessageHour = date('H:i', $convertMessageTime);
				$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
				$msgDots = '';
				$imStyle = '';
				$seenStatus = '';
				if ($cUserOne == $userID) {
					$mClass = 'me';
					$msgOwnerID = $cUserOne;
					$lastM = '';
					$timeStyle = 'msg_time_me';
					if (!empty($cFile)) {
						$imStyle = 'mmi_i';
					}
					$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					if ($mSeenStatus == '1') {
						$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					}
					if($gifMoney){
                        $SGifMoneyText = preg_replace( '/{.*?}/', $cMessage, $LANG['youSendGifMoney']);
                    }
				} else {
					$mClass = 'friend';
					$msgOwnerID = $cUserOne;
					$lastM = 'mm_' . $msgOwnerID;
					if (!empty($cFile)) {
						$imStyle = 'mmi_if';
					}
					if($gifMoney){
                        $msgOwnerFullName = $iN->iN_UserFullName($msgOwnerID);
                        $SGifMoneyText = $iN->iN_TextReaplacement($LANG['sendedGifMoney'],[$msgOwnerFullName , $cMessage]);
                    }
					$timeStyle = 'msg_time_fri';
				}
				$styleFor = '';
				if ($cStickerUrl) {
					$styleFor = 'msg_with_sticker';
					$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
				}
				if ($cGifUrl) {
					$styleFor = 'msg_with_gif';
					$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
				}
				$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
				if($privatePrice && $privateStatus == 'closed' && $mClass != 'me'){
                    include "../themes/$currentTheme/layouts/chat/privateMessage.php";
				}else{
					include "../themes/$currentTheme/layouts/chat/newMessage.php";
				}
			}
			/**/
		}
	}
/*Send User Typing*/
	if ($type == 'utyping') {
		if (isset($_POST['ci']) && isset($_POST['to'])) {
			$conversationID = $iN->iN_Secure($_POST['ci']);
			$toUserID = $iN->iN_Secure($_POST['to']);
			$time = time() . '_' . $userID;
			$updateTypingStatus = $iN->iN_UpdateTypingStatus($userID, $conversationID, $time);
		}
	}
/*Check Typeing*/
	if ($type == 'typing') {
		if (isset($_POST['ci']) && isset($_POST['to']) && $_POST['ci'] !== '' && $_POST['to'] !== '' && !empty($_POST['ci']) && !empty($_POST['to'])) {
			$conversationID = $iN->iN_Secure($_POST['ci']);
			$toUser = $iN->iN_Secure($_POST['to']);
			$getTypingStatus = $iN->iN_GetTypingStatus($toUser, $conversationID);
			$typingPayload = $getTypingStatus ?? '';
			$messageSeenStatus = $iN->iN_CheckLastMessageSeenOrNot($conversationID, $toUser, $userID);
			$iN->iN_UpdateMessageSeenStatus($conversationID, $toUser, $userID);
			$beforeUnderscore = 0;
			$afterUnderscore = 0;
			$firstUnderscorePos = ($typingPayload !== '') ? strpos($typingPayload, "_") : false;
			if ($firstUnderscorePos !== false) {
				$beforeUnderscore = (int)substr($typingPayload, 0, $firstUnderscorePos);
				$lastUnderscorePos = strrpos($typingPayload, '_');
				if ($lastUnderscorePos !== false) {
					$afterUnderscore = (int)substr($typingPayload, $lastUnderscorePos + 1);
				}
			}
			$ip = $iN->iN_GetIPAddress();
			$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
			if ($query && $query['status'] == 'success') {
				date_default_timezone_set($query['timezone']);
			}
			$getToUserData = $iN->iN_GetUserDetails($toUser);
			$toUserLastLoginTime = $getToUserData['last_login_time'];
			$lastSeen = date("c", $toUserLastLoginTime);
			$OnlineStatus = date("c", $toUserLastLoginTime);
			/*10 Second Ago for Typing*/
			$SecondBefore = time() - 10;
			/*180 Second Ago for Online - Offline Status*/
			$oStatus = time() - 35;
			$timeStatus = '';
			if ($afterUnderscore != $userID) {
				if ($beforeUnderscore > $SecondBefore) {
					$timeStatus = $LANG['typing'];
				} else {
					if ($toUserLastLoginTime > $oStatus) {
						$timeStatus = $LANG['online'];
					} else {
						$timeStatus = $LANG['last_seen'] . date('H:i', strtotime($OnlineStatus));
					}
				}
			} else {
				$timeStatus = $LANG['last_seen'] . date('H:i', strtotime($OnlineStatus));
			}
			$data = array(
				'timeStatus' => $timeStatus,
				'seenStatus' => $messageSeenStatus,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	if ($type == 'allPosts' || $type == 'moreexplore' || $type == 'premiums' || $type == 'morepremium' || $type == 'friends' || $type == 'morepurchased' || $type == 'purchasedpremiums' || $type == 'moreboostedposts' || $type == 'boostedposts' || $type == 'trendposts' || $type == 'moretrendposts') {
		$page = $type;
		include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
	}
	if ($type == 'creators') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$pageCreator = $iN->iN_Secure($_POST['p']);
			$lastPostID = $iN->iN_Secure($_POST['last']);
			include "../themes/$currentTheme/layouts/loadmore/moreCreator.php";
		}
	}
/*More Comment*/
	if ($type == 'moreComment') {
		if (isset($_POST['id'])) {
			$userPostID = $iN->iN_Secure($_POST['id']);
			$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
			if ($getUserComments) {
				foreach ($getUserComments as $comment) {
					$commentID = $comment['com_id'];
					$commentedUserID = $comment['comment_uid_fk'];
					$Usercomment = $comment['comment'];
					$commentTime = isset($comment['comment_time']) ? $comment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($comment['comment_file']) ? $comment['comment_file'] : NULL;
					$stickerUrl = isset($comment['sticker_url']) ? $comment['sticker_url'] : NULL;
					$gifUrl = isset($comment['gif_url']) ? $comment['gif_url'] : NULL;
					$commentedUserIDFk = isset($comment['iuid']) ? $comment['iuid'] : NULL;
					$commentedUserName = isset($comment['i_username']) ? $comment['i_username'] : NULL;
					$commentedUserFullName = isset($comment['i_user_fullname']) ? $comment['i_user_fullname'] : NULL;
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($comment['user_gender']) ? $comment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($comment['last_login_time']) ? $comment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($comment['user_verified_status']) ? $comment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$commentParentId = (int)($comment['parent_comment_id'] ?? 0);
					$commentReplies = $iN->iN_GetCommentReplies($commentID);
					$commentReplyCount = $commentReplies ? count($commentReplies) : 0;
					$isReply = false;
					$replyParentUserName = '';
					$replyParentUserFullName = '';
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($logedIn != 0) {
						$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
						$checkCommentReportedBefore = $iN->iN_CheckCommentReportedBefore($userID, $commentID);
						if ($checkCommentLikedBefore == '1') {
							$commentLikeBtnClass = 'c_in_unlike';
							$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						}
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
						$cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					$commentLinkUrl = $comment['link_url'] ?? '';
					$commentLinkDomain = $comment['link_domain'] ?? '';
					$commentLinkTitle = $comment['link_title'] ?? '';
					$commentLinkDescription = $comment['link_description'] ?? '';
					$commentLinkImage = $comment['link_image'] ?? '';
					include "../themes/$currentTheme/layouts/posts/comments.php";
				}
			}
		}
	}
		if ($type == 'searchCreator') {
			if (isset($_POST['s'])) {
				$searchValue = $iN->iN_Secure($_POST['s']);
				$searchValueFromData = $iN->iN_GetSearchResult($iN->iN_Secure($searchValue), $showingNumberOfPost, $whicUsers, $userID ?? null, $viewerCountryCode ?? null);
				include "../themes/$currentTheme/layouts/header/searchResults.php";
				exit();
			}
		}
/*Story Reply Message*/
	if ($type == 'story_reply') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit();
		}
		if (!isset($logedIn) || (int)$logedIn !== 1 || !isset($userID) || (int)$userID <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['you_should_login_first'] ?? 'login_required']);
			exit();
		}
		if (!isset($_POST['story_id'], $_POST['story_uid'], $_POST['message'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_failed'] ?? 'Message could not be sent.']);
			exit();
		}

		$storyId = (int)$iN->iN_Secure($_POST['story_id']);
		$storyOwnerId = (int)$iN->iN_Secure($_POST['story_uid']);
		$rawMessage = rawurldecode((string)$_POST['message']);
		$message = $iN->iN_Secure($rawMessage);

		if (trim($message) === '') {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_empty'] ?? 'Please enter a message.']);
			exit();
		}
		if ($storyId <= 0 || $storyOwnerId <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_expired'] ?? 'This story is no longer available.']);
			exit();
		}
		if ($storyOwnerId === (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_self'] ?? 'You cannot reply to your own story.']);
			exit();
		}

		$storyData = $iN->iN_GetUploadedStoriesDataS($storyId);
		if (!$storyData || (int)($storyData['uid_fk'] ?? 0) !== $storyOwnerId) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_expired'] ?? 'This story is no longer available.']);
			exit();
		}
		$createdAt = (int)($storyData['created'] ?? 0);
		if ($createdAt <= 0 || (time() - $createdAt) > 86400) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_expired'] ?? 'This story is no longer available.']);
			exit();
		}
		$accessInfo = $iN->iN_GetStoryAccessInfo($userID, $storyData);
		if (empty($accessInfo['allowed'])) {
			$reason = (string)($accessInfo['reason'] ?? '');
			$accessMessage = $LANG['story_access_followers'] ?? 'Only followers can view this story';
			if ($reason === 'subscribers') {
				$accessMessage = $LANG['story_access_subscribers'] ?? 'Only subscribers can view this story';
			}
			echo json_encode(['status' => 'error', 'message' => $accessMessage]);
			exit();
		}

		$recipient = DB::one(
			"SELECT iuid, uStatus, userType, who_can_message, who_can_send_message, message_status FROM i_users WHERE iuid = ? LIMIT 1",
			[(int)$storyOwnerId]
		);
		if (
			!$recipient ||
			!in_array((string)($recipient['uStatus'] ?? ''), ['1', '3'], true)
		) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_disabled'] ?? 'This user does not accept messages.']);
			exit();
		}

		if ($iN->iN_CheckUserBlocked($userID, $storyOwnerId) == '1' || $iN->iN_CheckUserBlockedVisitor($storyOwnerId, $userID) == '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_disabled'] ?? 'This user does not accept messages.']);
			exit();
		}

		$messageStatus = (string)($recipient['message_status'] ?? '1');
		$whoCanSend = (string)($recipient['who_can_send_message'] ?? '0');
		$whoCanMessage = (string)($recipient['who_can_message'] ?? '0');
		if ($whoCanSend !== $whoCanMessage) {
			$whoCanMessage = $whoCanSend;
		}
		if ($messageStatus !== '1') {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_disabled'] ?? 'This user does not accept messages.']);
			exit();
		}
		if ($whoCanSend === '1' || $whoCanMessage === '1') {
			$relation = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $storyOwnerId);
			if ($relation !== 'subscriber') {
				echo json_encode(['status' => 'error', 'message' => $LANG['only_subscriber_can_send_message'] ?? 'Only subscribers can send me messages.']);
				exit();
			}
		}

		$chatId = $iN->iN_GetConverationID($userID, $storyOwnerId);
		$sent = false;
		$messageWithContext = '[story_reply:' . $storyId . '] ' . $message;
		if ($chatId) {
			$insert = $iN->iN_InsertNewMessage($userID, (int)$chatId, $messageWithContext, '', '', '', '');
			$sent = $insert ? true : false;
		} else {
			$newChatId = $iN->iN_CreateConverationAndInsertFirstMessage($userID, $storyOwnerId, $messageWithContext);
			$sent = $newChatId ? true : false;
		}

		if ($sent) {
			$iN->iN_LogStoryReply($storyId, $userID, $storyOwnerId);
			echo json_encode(['status' => 'success', 'message' => $LANG['story_reply_sent'] ?? 'Message sent.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_failed'] ?? 'Message could not be sent.']);
		}
		exit();
	}
/*Create new Conversation*/
	if ($type == 'newMessageMe') {
		if (isset($_POST['user'])) {
			$iuID = $iN->iN_Secure($_POST['user']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			if ($checkUserExist) {
				$getToUserData = $iN->iN_GetUserDetails($iuID);
				$f_userfullname = $getToUserData['i_user_fullname'];
				$f_userAvatar = $iN->iN_UserAvatar($iuID, $base_url);
				$checkConversationStartedBeforeBetweenTheseUsers = $iN->iN_CheckConversationStartedBeforeBetweenUsers($userID, $iuID);
				if (empty($checkConversationStartedBeforeBetweenTheseUsers) || $checkConversationStartedBeforeBetweenTheseUsers = '' || !isset($checkConversationStartedBeforeBetweenTheseUsers)) {
					include "../themes/$currentTheme/layouts/popup_alerts/createMessage.php";
				}
			}
		}
	}
/*Createa New First Message Between Two User*/
	if ($type == 'newfirstMessage') {
		if (isset($_POST['u']) && isset($_POST['fm'])) {
			$user = $iN->iN_Secure($_POST['u']);
			$firstMessage = $iN->iN_Secure($_POST['fm']);
			if (empty($firstMessage) || $firstMessage == '' || !isset($firstMessage) || strlen(trim($firstMessage)) == 0) {
				exit('404');
			}
			$insertNewMessageAndCreateConversation = $iN->iN_CreateConverationAndInsertFirstMessage($userID, $user, $iN->iN_Secure($firstMessage));
			if ($insertNewMessageAndCreateConversation) {
				echo iN_HelpSecure($base_url) . 'chat?chat_width=' . $insertNewMessageAndCreateConversation;
				$userDeviceKey = $iN->iN_GetuserDetails($user);
				$toUserName = $userDeviceKey['i_username'];
				$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
				$msgTitle = $iN->iN_Secure($LANG['you_have_a_new_message']);
				$msgBody = $iN->iN_Secure($LANG['click_to_continue_conversation']);
				$URL = iN_HelpSecure($base_url) . 'chat?chat_width=' . $insertNewMessageAndCreateConversation;
				if($oneSignalUserDeviceKey){
				  $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
				}
			} else {
				echo '404';
			}
		}
	}
/*Update Dark to Light or Light to Dark*/
	if ($type == 'updateTheme') {
		if (isset($_POST['theme']) && in_array($_POST['theme'], $themes)) {
			$uTheme = $iN->iN_Secure($_POST['theme']);
			$updateTheme = $iN->iN_UpdateUserTheme($userID, $uTheme);
			if ($updateTheme) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Get Fixed Mobile Footer Menu*/
	if ($type == 'fixedMenu') {
		include "../themes/$currentTheme/layouts/widgets/mobileFixedMenu.php";
	}
/*Delete Message*/
	if ($type == 'deleteMessage') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			$messageID = $iN->iN_Secure($_POST['id']);
			$conversationID = $iN->iN_Secure($_POST['cid']);
			$deleteMessage = $iN->iN_DeleteMessageFromData($userID, $messageID, $conversationID);
			if ($deleteMessage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Delete Conversion*/
	if ($type == 'deleteConversation') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			$messageID = $iN->iN_Secure($_POST['id']);
			$conversationID = $iN->iN_Secure($_POST['cid']);
			$deleteMessage = $iN->iN_DeleteConversationFromData($userID, $conversationID);
			if ($deleteMessage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Search User From Chat*/
	if ($type == 'searchUser') {
		if (isset($_POST['key'])) {
			$sKey = $iN->iN_Secure($_POST['key']);
			$searchUser = $iN->iN_SearchChatUsers($userID, $iN->iN_Secure($sKey));
			if ($searchUser) {
				foreach ($searchUser as $sResult) {
					$resultUserID = $sResult['iuid'];
					$resultUserName = $sResult['i_username'];
					$resultUserFullName = $sResult['i_user_fullname'];
					$profileUrl = $base_url . $resultUserName;
					$resultUserAvatar = $iN->iN_UserAvatar($resultUserID, $base_url);
					include "../themes/$currentTheme/layouts/chat/chatSearch.php";
				}
			}
		}
	}
/*Hide Notification*/
	if ($type == 'hideNotification') {
		if (isset($_POST['id'])) {
			$hideID = $iN->iN_Secure($_POST['id']);
			$hideNot = $iN->iN_HideNotification($userID, $hideID);
			if ($hideNot) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*UN Block User*/
	if ($type == 'unblock') {
		if (isset($_POST['id']) && isset($_POST['u'])) {
			$unBlockID = $iN->iN_Secure($_POST['id']);
			$unBlockUserID = $iN->iN_Secure($_POST['u']);
			$unBlock = $iN->iN_UnBlockUser($userID, $unBlockID, $unBlockUserID);
			if ($unBlock) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Edit May Page*/
	if ($type == 'editMyPass') {
			$currentPassword = isset($_POST['crn_password']) ? (string)$_POST['crn_password'] : '';
			$newPassword = isset($_POST['nw_password']) ? (string)$_POST['nw_password'] : '';
			$confirmNewPassword = isset($_POST['confirm_pass']) ? (string)$_POST['confirm_pass'] : '';
			if ($currentPassword !== '') {
				$userCurrentPass = $iN->iN_GetUserDetails($userID);
				$storedHash = isset($userCurrentPass['i_password']) ? (string)$userCurrentPass['i_password'] : '';
				if (preg_match('/\s/', $currentPassword) || preg_match('/\s/', $newPassword) || preg_match('/\s/', $confirmNewPassword)) {
					exit('6');
				}
				$isValidCurrent = false;
				if ($storedHash !== '') {
					if (password_verify($currentPassword, $storedHash)) {
						$isValidCurrent = true;
					} else {
						$legacyHash = sha1(md5($currentPassword));
						$legacySanitizedHash = sha1(md5($iN->iN_Secure($currentPassword)));
						if (hash_equals($storedHash, $legacyHash) || hash_equals($storedHash, $legacySanitizedHash)) {
							$isValidCurrent = true;
						}
					}
				}
				if (!$isValidCurrent) {
					exit('1');
				}
				if (strlen($newPassword) < 6 || strlen($confirmNewPassword) < 6 || strlen($currentPassword) < 6) {
					exit('5');
				}
				if ($newPassword === '' || $confirmNewPassword === '') {
					exit('4');
				}
				if ($newPassword !== $confirmNewPassword) {
					exit('2');
				}
				$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
				$updateNewPassword = $iN->iN_UpdatePassword($userID, $newPasswordHash);
				if ($updateNewPassword) {
					echo iN_HelpSecure($base_url) . 'logout.php';
				} else {
					exit('404');
				}
			} else {
				exit('3');
			}
		}
	/*Create account export*/
	if ($type == 'createAccountExport') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'
			]);
			exit;
		}
		$result = $iN->iN_CreateAccountExport((int)$userID, (string)$base_url);
		$status = (string)($result['status'] ?? 'error');
		if ($status === 'ok') {
			echo json_encode([
				'status' => 'ok',
				'message' => $LANG['account_export_ready'] ?? 'Your data export is ready.',
				'download_url' => (string)($result['download_url'] ?? ''),
				'file_size' => (int)($result['file_size'] ?? 0),
				'generation_seconds' => (int)($result['generation_seconds'] ?? 0),
				'created_at' => (int)($result['created_at'] ?? time()),
				'expires_at' => (int)($result['expires_at'] ?? 0)
			]);
			exit;
		}
		if ($status === 'schema_missing') {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_export_schema_missing'] ?? 'Please run newSQL.sql first.'
			]);
			exit;
		}
		if ($status === 'zip_unavailable') {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_export_zip_unavailable'] ?? 'ZIP extension is not available.'
			]);
			exit;
		}
		echo json_encode([
			'status' => 'error',
			'message' => $LANG['account_export_failed'] ?? 'Unable to generate data export right now.'
		]);
		exit;
	}
	/*Request account deletion*/
	if ($type == 'requestAccountDeletion') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'
			]);
			exit;
		}
		$deletePassword = isset($_POST['delete_password']) ? (string)$_POST['delete_password'] : '';
		$deleteConfirmText = isset($_POST['delete_confirm_text']) ? trim((string)$_POST['delete_confirm_text']) : '';
		if ($deletePassword === '') {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_delete_password_required'] ?? 'Please enter your password.'
			]);
			exit;
		}
		if (strtoupper($deleteConfirmText) !== 'DELETE') {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_delete_modal_confirm_text_invalid'] ?? 'Please type DELETE to confirm.'
			]);
			exit;
		}
		$userCurrentPass = $iN->iN_GetUserDetails($userID);
		$storedHash = isset($userCurrentPass['i_password']) ? (string)$userCurrentPass['i_password'] : '';
		$isValidCurrent = false;
		if ($storedHash !== '') {
			if (password_verify($deletePassword, $storedHash)) {
				$isValidCurrent = true;
			} else {
				$legacyHash = sha1(md5($deletePassword));
				$legacySanitizedHash = sha1(md5($iN->iN_Secure($deletePassword)));
				if (hash_equals($storedHash, $legacyHash) || hash_equals($storedHash, $legacySanitizedHash)) {
					$isValidCurrent = true;
				}
			}
		}
		if (!$isValidCurrent) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_delete_password_invalid'] ?? 'Password is incorrect.'
			]);
			exit;
		}
		$result = $iN->iN_RequestAccountDeletion((int)$userID, 14);
		$status = (string)($result['status'] ?? 'error');
		if ($status === 'ok' || $status === 'already_pending') {
			if (isset($_COOKIE[$cookieName])) {
				setcookie($cookieName, '', time() - 3600, '/');
			}
			unset($_SESSION['iuid']);
			echo json_encode([
				'status' => 'ok',
				'message' => $LANG['account_delete_request_success'] ?? 'Deletion request created. Your account is suspended for 14 days.',
				'logout_url' => iN_HelpSecure($base_url) . 'logout.php'
			]);
			exit;
		}
		if ($status === 'schema_missing') {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_delete_schema_missing'] ?? 'Please run newSQL.sql first.'
			]);
			exit;
		}
		if ($status === 'not_allowed') {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['account_delete_not_allowed'] ?? 'This account cannot be deleted from settings.'
			]);
			exit;
		}
		echo json_encode([
			'status' => 'error',
			'message' => $LANG['account_delete_request_failed'] ?? 'Unable to create deletion request.'
		]);
		exit;
	}
	/*Cancel account deletion*/
	if ($type == 'cancelAccountDeletion') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'
			]);
			exit;
		}
		$cancelled = $iN->iN_CancelAccountDeletion((int)$userID, 'manual');
		if ($cancelled) {
			echo json_encode([
				'status' => 'ok',
				'message' => $LANG['account_delete_cancel_success'] ?? 'Deletion request cancelled.'
			]);
			exit;
		}
		echo json_encode([
			'status' => 'error',
			'message' => $LANG['account_delete_cancel_failed'] ?? 'No active deletion request found.'
		]);
		exit;
	}
	/*Update Preferences*/
	if ($type == 'p_preferences') {
		if (isset($_POST['notit']) && isset($_POST['sType'])) {
			$setValue = $iN->iN_Secure($_POST['notit']);
			$setType = $iN->iN_Secure($_POST['sType']);
			if ($setType == 'email_not') {
				$updateEmailStatus = $iN->iN_UpdateEmailNotificationStatus($userID, $setValue);
				if ($updateEmailStatus) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'message_not') {
				$updateMessageStatus = $iN->iN_UpdateMessageSendStatus($userID, $setValue);
				if ($updateMessageStatus) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'show_hide_profile') {
				$updateShowHideProfile = $iN->iN_UpdateShowHidePostsStatus($userID, $setValue);
				if ($updateShowHideProfile) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'connections_visibility') {
				$updateConnectionsVisibility = $iN->iN_UpdateConnectionsVisibility($userID, $setValue === '1' ? '1' : '0');
				if ($updateConnectionsVisibility) {
					echo '200';
				} else {
					echo '404';
				}
			} else if (in_array($setType, ['show_profile_gender', 'show_profile_age', 'show_profile_birthdate', 'show_profile_category', 'show_profile_likes', 'show_profile_comments', 'show_profile_bio', 'show_profile_social'], true)) {
				$updateProfileInfoVisibility = $iN->iN_UpdateProfileInfoVisibility($userID, $setType, $setValue);
				if ($updateProfileInfoVisibility) {
					echo '200';
				} else {
					echo '404';
				}
			} else if($setType == 'who_send_message_not'){
				$updateWhoCanSendMessage = $iN->iN_UpdateWhoCanSendYouAMessage($userID, $setValue);
				if ($updateWhoCanSendMessage) {
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
    /*Browser Web Push Preference*/
    if ($type == 'webpush_toggle') {
        header('Content-Type: application/json; charset=utf-8');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if ($logedIn != 1) {
            echo json_encode(['status' => 'error', 'message' => $LANG['no_permisson'] ?? 'no_permission']);
            exit;
        }
        $enabled = (isset($_POST['enabled']) && (string)$_POST['enabled'] === '1') ? '1' : '0';
        if (!$iN->iN_UpdateUserWebPushStatus((int)$userID, $enabled)) {
            echo json_encode(['status' => 'error', 'message' => $LANG['web_push_update_failed'] ?? 'web_push_update_failed']);
            exit;
        }
        echo json_encode(['status' => 'ok', 'enabled' => $enabled]);
        exit;
    }
    if ($type == 'webpush_subscribe') {
        header('Content-Type: application/json; charset=utf-8');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if ($logedIn != 1) {
            echo json_encode(['status' => 'error', 'message' => $LANG['no_permisson'] ?? 'no_permission']);
            exit;
        }
        $endpoint = isset($_POST['endpoint']) ? trim((string)$_POST['endpoint']) : '';
        $p256dh = isset($_POST['p256dh']) ? trim((string)$_POST['p256dh']) : '';
        $auth = isset($_POST['auth']) ? trim((string)$_POST['auth']) : '';
        $contentEncoding = isset($_POST['content_encoding']) ? trim((string)$_POST['content_encoding']) : 'aes128gcm';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? trim((string)$_SERVER['HTTP_USER_AGENT']) : '';
        $deviceLabel = isset($_POST['device_label']) ? trim((string)$_POST['device_label']) : '';

        $saved = $iN->iN_SaveWebPushSubscription((int)$userID, [
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'content_encoding' => $contentEncoding,
            'user_agent' => $userAgent,
            'device_label' => $deviceLabel,
        ]);
        if (!$saved) {
            echo json_encode(['status' => 'error', 'message' => $LANG['web_push_subscribe_failed'] ?? 'web_push_subscribe_failed']);
            exit;
        }
        echo json_encode(['status' => 'ok']);
        exit;
    }
    if ($type == 'webpush_unsubscribe') {
        header('Content-Type: application/json; charset=utf-8');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit;
        }
        if ($logedIn != 1) {
            echo json_encode(['status' => 'error', 'message' => $LANG['no_permisson'] ?? 'no_permission']);
            exit;
        }
        $endpoint = isset($_POST['endpoint']) ? trim((string)$_POST['endpoint']) : '';
        $removed = $iN->iN_RemoveWebPushSubscription((int)$userID, $endpoint !== '' ? $endpoint : null);
        if (!$removed) {
            echo json_encode(['status' => 'error', 'message' => $LANG['web_push_unsubscribe_failed'] ?? 'web_push_unsubscribe_failed']);
            exit;
        }
        echo json_encode(['status' => 'ok']);
        exit;
    }
    if ($type == 'webpush_state') {
        header('Content-Type: application/json; charset=utf-8');
        if ($logedIn != 1) {
            echo json_encode(['status' => 'error']);
            exit;
        }
        $enabled = $iN->iN_GetUserWebPushStatus((int)$userID);
        echo json_encode([
            'status' => 'ok',
            'enabled' => $enabled,
            'global' => (string)$webPushStatus === '1' ? '1' : '0',
            'public' => (string)$webPushVapidPublic,
        ]);
        exit;
    }
	/*Start Age Verification*/
	if ($type == 'ageVerifStart') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'
			]);
			exit;
		}
		if ($logedIn != 1) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['age_verification_error_generic']
			]);
			exit;
		}
		$now = time();
		$rateWindow = 60;
		$rateLimit = 3;
		$attempts = isset($_SESSION['ageverif_start_attempts']) && is_array($_SESSION['ageverif_start_attempts'])
			? $_SESSION['ageverif_start_attempts']
			: [];
		$attempts = array_values(array_filter($attempts, function ($timestamp) use ($now, $rateWindow) {
			return (int)$timestamp > ($now - $rateWindow);
		}));
		if (count($attempts) >= $rateLimit) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['age_verification_error_rate_limited']
			]);
			exit;
		}
		$attempts[] = $now;
		$_SESSION['ageverif_start_attempts'] = $attempts;
		$_SESSION['ageverif_last_start'] = $now;
		$simEnabled = filter_var(ini_get('dizzy_ageverif_sim'), FILTER_VALIDATE_BOOLEAN);
		$simStatus = '';
		if ($simEnabled) {
			$simStatusRaw = isset($_POST['sim_status']) ? (string)$_POST['sim_status'] : '';
			$simStatusRaw = strtolower(trim($simStatusRaw));
			$simAllowed = ['approved', 'declined', 'in_progress', 'expired', 'abandoned', 'failed', 'success'];
			$simStatus = in_array($simStatusRaw, $simAllowed, true) ? $simStatusRaw : 'approved';
			$_SESSION['ageverif_sim_status'] = $simStatus;
		} else {
			unset($_SESSION['ageverif_sim_status']);
		}
		$provider = isset($ageVerificationProvider) ? (string)$ageVerificationProvider : 'ageverif';
		$allowedProviders = isset($ageVerificationProviders) && is_array($ageVerificationProviders)
			? $ageVerificationProviders
			: ['ageverif', 'yoti', 'didit'];
		if (!in_array($provider, $allowedProviders, true)) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['age_verification_error_config_incomplete']
			]);
			exit;
		}
		$configColumnsOk = method_exists($iN, 'iN_ConfigColumnsExist');
		if ($configColumnsOk) {
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
			$configColumnsOk = $iN->iN_ConfigColumnsExist($providerColumns[$provider] ?? []);
		}
		if (!$configColumnsOk) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['age_verification_error_columns_missing']
			]);
			exit;
		}
		$clientId = trim((string)$ageVerificationClientId);
		$clientSecret = trim((string)$ageVerificationClientSecret);
		if ((string)$ageVerificationStatus !== '1') {
			$configMessage = $LANG['age_verification_error_provider_disabled'] ?? $LANG['age_verification_error_config_incomplete'];
			echo json_encode([
				'status' => 'error',
				'message' => $configMessage
			]);
			exit;
		}
		if ($provider === 'didit') {
			$apiKey = trim((string)$ageVerificationClientId);
			$webhookSecret = trim((string)$ageVerificationClientSecret);
			$workflowId = trim((string)$ageVerificationAuthorizeUrl);
			$isUuid = $workflowId !== '' && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $workflowId);
			if ($apiKey === '' || $webhookSecret === '' || !$isUuid) {
				$configMessage = $LANG['age_verification_error_config_incomplete'];
				if (in_array((string)$userType, ['2', '3'], true) && isset($LANG['age_verification_error_config_incomplete_admin'])) {
					$configMessage = $LANG['age_verification_error_config_incomplete_admin'];
				}
				echo json_encode([
					'status' => 'error',
					'message' => $configMessage
				]);
				exit;
			}

			if ($simEnabled) {
				try {
					$sessionId = 'sim_didit_' . bin2hex(random_bytes(8));
				} catch (Throwable $e) {
					$sessionId = 'sim_didit_' . bin2hex(openssl_random_pseudo_bytes(8));
				}
				$callbackUrl = function_exists('route_url')
					? route_url('age-verification-callback')
					: rtrim((string)$base_url, '/') . '/age-verification-callback';
				$query = [
					'session_id' => $sessionId
				];
				if ($simStatus !== '') {
					$query['sim_status'] = $simStatus;
				}
				$redirectUrl = $callbackUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
				unset($_SESSION['ageverif_state']);
				$_SESSION['ageverif_user_id'] = (int)$userID;
				$_SESSION['ageverif_time'] = $now;
				$_SESSION['ageverif_provider'] = $provider;
				$_SESSION['ageverif_session_id'] = $sessionId;
				DB::exec(
					"UPDATE i_users SET age_verify_status = '0', age_verify_provider = ?, age_verified_at = NULL, age_verify_ref = ? WHERE iuid = ?",
					['didit', $sessionId, (int)$userID]
				);
				echo json_encode([
					'status' => 'success',
					'url' => $redirectUrl,
					'redirect_url' => $redirectUrl
				]);
				exit;
			}

			$callbackUrl = function_exists('route_url')
				? route_url('age-verification-callback')
				: rtrim((string)$base_url, '/') . '/age-verification-callback';
			$baseUrlOverride = isset($diditAgeVerifTokenUrl) ? trim((string)$diditAgeVerifTokenUrl) : '';
			$diditBaseUrl = 'https://verification.didit.me/v3';
			if ($baseUrlOverride !== '' && filter_var($baseUrlOverride, FILTER_VALIDATE_URL)) {
				$diditBaseUrl = rtrim($baseUrlOverride, '/');
			}

			$payload = [
				'workflow_id' => $workflowId,
				'callback' => $callbackUrl,
				'vendor_data' => (string)$userID
			];
			$metadata = [];
			if (!empty($base_url)) {
				$metadata['site'] = rtrim((string)$base_url, '/');
			}
			if (!empty($metadata)) {
				$payload['metadata'] = $metadata;
			}

			$endpoint = rtrim($diditBaseUrl, '/') . '/session/';
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Content-Type: application/json',
				'X-Api-Key: ' . $apiKey
			]);
			$response = curl_exec($ch);
			$curlErr = curl_error($ch);
			$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($curlErr !== '' || $response === false || $httpCode >= 400) {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			$sessionData = json_decode((string)$response, true);
			if (!is_array($sessionData)) {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			$sessionId = isset($sessionData['session_id']) ? trim((string)$sessionData['session_id']) : '';
			if ($sessionId === '' && isset($sessionData['sessionId'])) {
				$sessionId = trim((string)$sessionData['sessionId']);
			}
			$redirectUrl = isset($sessionData['url']) ? trim((string)$sessionData['url']) : '';
			if ($redirectUrl === '' && isset($sessionData['redirect_url'])) {
				$redirectUrl = trim((string)$sessionData['redirect_url']);
			}
			if ($sessionId === '' || $redirectUrl === '') {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			$parsedUrl = parse_url($redirectUrl);
			$redirectHost = isset($parsedUrl['host']) ? strtolower((string)$parsedUrl['host']) : '';
			$redirectScheme = isset($parsedUrl['scheme']) ? strtolower((string)$parsedUrl['scheme']) : '';
			if ($redirectScheme !== 'https' || !in_array($redirectHost, ['verify.didit.me', 'verification.didit.me'], true)) {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			unset($_SESSION['ageverif_state']);
			$_SESSION['ageverif_user_id'] = (int)$userID;
			$_SESSION['ageverif_time'] = $now;
			$_SESSION['ageverif_provider'] = $provider;
			$_SESSION['ageverif_session_id'] = $sessionId;
			DB::exec(
				"UPDATE i_users SET age_verify_status = '0', age_verify_provider = ?, age_verified_at = NULL, age_verify_ref = ? WHERE iuid = ?",
				['didit', $sessionId, (int)$userID]
			);

			echo json_encode([
				'status' => 'success',
				'url' => $redirectUrl,
				'redirect_url' => $redirectUrl
			]);
			exit;
		}
		if ($provider === 'yoti') {
			if ($clientId === '' || $clientSecret === '') {
				$configMessage = $LANG['age_verification_error_config_incomplete'];
				if (in_array((string)$userType, ['2', '3'], true) && isset($LANG['age_verification_error_config_incomplete_admin'])) {
					$configMessage = $LANG['age_verification_error_config_incomplete_admin'];
				}
				echo json_encode([
					'status' => 'error',
					'message' => $configMessage
				]);
				exit;
			}

			if ($simEnabled) {
				try {
					$sessionId = 'sim_yoti_' . bin2hex(random_bytes(8));
				} catch (Throwable $e) {
					$sessionId = 'sim_yoti_' . bin2hex(openssl_random_pseudo_bytes(8));
				}
				$_SESSION['ageverif_last_start'] = $now;
				unset($_SESSION['ageverif_state'], $_SESSION['ageverif_session_id']);
				$_SESSION['ageverif_user_id'] = (int)$userID;
				$_SESSION['ageverif_time'] = $now;
				$_SESSION['ageverif_provider'] = $provider;
				$_SESSION['ageverif_session_id'] = $sessionId;

				$callbackUrl = function_exists('route_url')
					? route_url('age-verification-callback')
					: rtrim((string)$base_url, '/') . '/age-verification-callback';
				$query = [
					'sessionId' => $sessionId
				];
				if ($simStatus !== '') {
					$query['sim_status'] = $simStatus;
				}
				$redirectUrl = $callbackUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
				echo json_encode([
					'status' => 'success',
					'url' => $redirectUrl,
					'redirect_url' => $redirectUrl
				]);
				exit;
			}

			$_SESSION['ageverif_last_start'] = $now;
			unset($_SESSION['ageverif_state'], $_SESSION['ageverif_session_id']);
			$_SESSION['ageverif_user_id'] = (int)$userID;
			$_SESSION['ageverif_time'] = $now;
			$_SESSION['ageverif_provider'] = $provider;

			$minAge = (int)$ageVerificationMinAge;
			if ($minAge < 19) {
				$minAge = 19;
			} elseif ($minAge > 25) {
				$minAge = 25;
			}

			$callbackUrl = function_exists('route_url')
				? route_url('age-verification-callback')
				: rtrim((string)$base_url, '/') . '/age-verification-callback';
			$cancelUrl = function_exists('route_url')
				? route_url('age-verification')
				: rtrim((string)$base_url, '/') . '/age-verification';

			$payload = [
				'type' => 'OVER',
				'ttl' => 300,
				'age_estimation' => [
					'allowed' => true,
					'threshold' => $minAge,
					'level' => 'PASSIVE',
					'retry_limit' => 1
				],
				'digital_id' => [
					'allowed' => true,
					'threshold' => 18,
					'age_estimation_allowed' => true,
					'age_estimation_threshold' => $minAge,
					'level' => 'NONE',
					'retry_limit' => 1
				],
				'doc_scan' => [
					'allowed' => true,
					'threshold' => 18,
					'authenticity' => 'AUTO',
					'level' => 'PASSIVE',
					'retry_limit' => 1
				],
				'callback' => [
					'auto' => true,
					'url' => $callbackUrl
				],
				'cancel_url' => $cancelUrl,
				'synchronous_checks' => true
			];

			$ch = curl_init('https://age.yoti.com/api/v1/sessions');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Bearer ' . $clientSecret,
				'Yoti-SDK-Id: ' . $clientId
			]);
			$response = curl_exec($ch);
			$curlErr = curl_error($ch);
			$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($curlErr !== '' || $response === false || $httpCode >= 400) {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			$sessionData = json_decode((string)$response, true);
			if (!is_array($sessionData)) {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			$sessionId = isset($sessionData['id']) ? trim((string)$sessionData['id']) : '';
			if ($sessionId === '') {
				echo json_encode([
					'status' => 'error',
					'message' => $LANG['age_verification_error_generic']
				]);
				exit;
			}

			$_SESSION['ageverif_session_id'] = $sessionId;

			$redirectUrl = 'https://age.yoti.com?' . http_build_query([
				'sessionId' => $sessionId,
				'sdkId' => $clientId
			], '', '&', PHP_QUERY_RFC3986);

			echo json_encode([
				'status' => 'success',
				'url' => $redirectUrl
			]);
			exit;
		}

		$authorizeUrl = trim((string)$ageVerificationAuthorizeUrl);
		$tokenUrl = trim((string)$ageVerificationTokenUrl);
		if (
			$clientId === ''
			|| $clientSecret === ''
			|| $authorizeUrl === ''
			|| $tokenUrl === ''
			|| filter_var($authorizeUrl, FILTER_VALIDATE_URL) === false
			|| filter_var($tokenUrl, FILTER_VALIDATE_URL) === false
		) {
			$configMessage = $LANG['age_verification_error_config_incomplete'];
			if (in_array((string)$userType, ['2', '3'], true) && isset($LANG['age_verification_error_config_incomplete_admin'])) {
				$configMessage = $LANG['age_verification_error_config_incomplete_admin'];
			}
			echo json_encode([
				'status' => 'error',
				'message' => $configMessage
			]);
			exit;
		}

		$_SESSION['ageverif_last_start'] = $now;

		$redirectUri = function_exists('route_url')
			? route_url('age-verification-callback')
			: rtrim((string)$base_url, '/') . '/age-verification-callback';

		try {
			$state = bin2hex(random_bytes(16));
		} catch (Throwable $e) {
			echo json_encode([
				'status' => 'error',
				'message' => $LANG['age_verification_error_generic']
			]);
			exit;
		}

		unset($_SESSION['ageverif_session_id']);
		$_SESSION['ageverif_state'] = $state;
		$_SESSION['ageverif_user_id'] = (int)$userID;
		$_SESSION['ageverif_time'] = $now;
		$_SESSION['ageverif_provider'] = $provider;

		$params = [
			'response_type' => 'code',
			'client_id' => $clientId,
			'redirect_uri' => $redirectUri,
			'state' => $state
		];
		$scope = trim((string)$ageVerificationScope);
		if ($scope !== '') {
			$params['scope'] = $scope;
		}
		$separator = (strpos($authorizeUrl, '?') === false) ? '?' : '&';
		$authUrl = $authorizeUrl . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

		echo json_encode([
			'status' => 'success',
			'url' => $authUrl
		]);
		exit;
	}
/*Call Paid Live Streaming Box*/
	if ($type == 'paidLive') {
		$liveStreamNotForNonCreators = '<div class="ll_live_not flex_ alignItem">' . html_entity_decode($iN->iN_SelectedMenuIcon('32')) . ' ' . iN_HelpSecure($LANG['only_creators_']) . '</div>';
		if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
			include "../themes/$currentTheme/layouts/popup_alerts/createaPaidLiveStreaming.php";
		} else {
			$currentTime = time();
			$finishTime = $currentTime + 60 * $freeLiveTime;
			$l_Time = $iN->iN_GetLastLiveFinishTime($userID);
			include "../themes/$currentTheme/layouts/popup_alerts/createaFreeLiveStreaming.php";
		}
	}
/*Call Free Live Streaming Box*/
	if ($type == 'freeLive') {
		$currentTime = time();
		$finishTime = $currentTime + 60 * $freeLiveTime;
		$l_Time = $iN->iN_GetLastLiveFinishTime($userID);
		$liveStreamNotForNonCreators = '';
		include "../themes/$currentTheme/layouts/popup_alerts/createaFreeLiveStreaming.php";
	}
/*Live Notify Audience Picker*/
	if ($type == 'liveNotifyAudience') {
		if ($logedIn != 1) {
			exit('404');
		}
		$selectedRaw = isset($_POST['selected']) ? (string)$_POST['selected'] : '';
		$selectedIds = array_filter(array_map('intval', explode(',', preg_replace('/[^0-9,]/', '', $selectedRaw))));
		$selectedMap = [];
		foreach ($selectedIds as $selectedId) {
			if ($selectedId > 0) {
				$selectedMap[$selectedId] = true;
			}
		}
		$listLimit = 200;
		$subscribersList = [];
		try {
			$subscribersList = DB::all(
				"SELECT DISTINCT U.iuid, U.i_username, U.i_user_fullname, S.subscription_id
                 FROM i_user_subscriptions S
                 INNER JOIN i_users U ON U.iuid = S.iuid_fk
                 WHERE S.subscribed_iuid_fk = ? AND S.status = 'active' AND S.finished = '0' AND S.subscription_scope = 'profile'
                   AND U.uStatus IN('1','3')
                 ORDER BY S.subscription_id DESC
                 LIMIT {$listLimit}",
				[(int)$userID]
			);
		} catch (Throwable $e) {
			rq_debug('liveNotifyAudience subscribers query failed', ['error' => $e->getMessage()]);
			try {
				$subscribersList = DB::all(
					"SELECT DISTINCT U.iuid, U.i_username, U.i_user_fullname, S.subscription_id
                     FROM i_user_subscriptions S
                     INNER JOIN i_users U ON U.iuid = S.iuid_fk
                     WHERE S.subscribed_iuid_fk = ? AND S.status = 'active'
                       AND U.uStatus IN('1','3')
                     ORDER BY S.subscription_id DESC
                     LIMIT {$listLimit}",
					[(int)$userID]
				);
			} catch (Throwable $inner) {
				rq_debug('liveNotifyAudience subscribers fallback failed', ['error' => $inner->getMessage()]);
				$subscribersList = [];
			}
		}
		$subscriberIds = [];
		if (!empty($subscribersList)) {
			foreach ($subscribersList as $subscriberRow) {
				$subscriberId = (int)($subscriberRow['iuid'] ?? 0);
				if ($subscriberId > 0) {
					$subscriberIds[$subscriberId] = true;
				}
			}
		}
		$followersList = [];
		try {
			$followersList = DB::all(
				"SELECT DISTINCT U.iuid, U.i_username, U.i_user_fullname, F.fr_id
                 FROM i_friends F
                 INNER JOIN i_users U ON U.iuid = F.fr_one
                 WHERE F.fr_two = ? AND F.fr_status = 'flwr'
                   AND U.uStatus IN('1','3')
                 ORDER BY F.fr_id DESC
                 LIMIT {$listLimit}",
				[(int)$userID]
			);
		} catch (Throwable $e) {
			rq_debug('liveNotifyAudience followers query failed', ['error' => $e->getMessage()]);
			$followersList = [];
		}
		if (!empty($followersList) && !empty($subscriberIds)) {
			$followersList = array_values(array_filter($followersList, function ($row) use ($subscriberIds) {
				$id = (int)($row['iuid'] ?? 0);
				return $id > 0 && !isset($subscriberIds[$id]);
			}));
		}
		include "../themes/$currentTheme/layouts/popup_alerts/liveNotifyAudience.php";
		exit();
	}
/*Create a Free Live Streaming*/
	if ($type == 'createFreeLiveStream') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			$data = array(
				'status' => 'csrf',
				'message' => $LANG['live_stream_invalid_csrf'] ?? ($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'),
				'start' => '',
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
		if (isset($_POST['lTitle']) && !empty($_POST['lTitle'])) {
			$liveStreamingTitle = $iN->iN_Secure($_POST['lTitle']);
			$notifyLive = isset($_POST['notify_live']) && $_POST['notify_live'] == '1' ? 1 : 0;
			$notifyAudience = isset($_POST['notify_audience']) ? $iN->iN_Secure($_POST['notify_audience']) : 'followers';
			if (!in_array($notifyAudience, ['followers', 'subscribers', 'selected'], true)) {
				$notifyAudience = 'followers';
			}
			$notifySelectedRaw = isset($_POST['notify_selected_ids']) ? (string)$_POST['notify_selected_ids'] : '';
			$notifySelectedIds = array_filter(array_map('intval', explode(',', preg_replace('/[^0-9,]/', '', $notifySelectedRaw))));
			$scheduledAt = isset($_POST['scheduled_at']) ? (int)$_POST['scheduled_at'] : 0;
			$isScheduled = $scheduledAt > 0;
			$rand = rand(1111111, 9999999);
			$channelName = "stream_" . $userID . "_" . $rand;
			if (strlen($liveStreamingTitle) < 5 || strlen($liveStreamingTitle) > 32) {
				$data = array(
					'status' => '4',
					'start' => '',
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			if ($isScheduled) {
				if (!isset($scheduledPostsStatus) || (string)$scheduledPostsStatus !== '1') {
					$data = array(
						'status' => 'schedule_disabled',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				$now = time();
				$minLead = 5 * 60;
				$maxDays = isset($scheduledMaxDelayDays) ? (int)$scheduledMaxDelayDays : 30;
				if ($maxDays < 1) { $maxDays = 30; }
				if ($maxDays > 30) { $maxDays = 30; }
				if ($scheduledAt < ($now + $minLead)) {
					$data = array(
						'status' => 'schedule_invalid',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				if ($scheduledAt > ($now + ($maxDays * 86400))) {
					$data = array(
						'status' => 'schedule_limit',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				if ($iN->iN_CheckUserHasActiveOrScheduledLive($userID)) {
					$data = array(
						'status' => 'live_exists',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				$createScheduled = $iN->iN_CreateScheduledLive($userID, $liveStreamingTitle, $channelName, 'free', null, $scheduledAt, $notifyLive, $notifyAudience, $notifySelectedIds);
				if ($createScheduled) {
					$scheduledLiveId = (int)$createScheduled;
					$scheduledText = date('M d, Y H:i', (int)$scheduledAt);
					$liveUrl = $base_url . 'live/' . $userName;
					$postTemplate = $LANG['live_scheduled_post_text'] ?? 'Scheduled live: {title} - {time} {url}';
					$postText = str_replace(
						['{title}', '{time}', '{url}'],
						[(string)$liveStreamingTitle, (string)$scheduledText, '__LIVE_URL__'],
						$postTemplate
					);
					$postText = str_replace('__LIVE_URL__', "\n" . (string)$liveUrl, $postText);
					$postText = str_replace(" \n", "\n", $postText);
					$postSlug = $iN->url_slugies(mb_substr($postText, 0, 55, 'utf-8'));
					$postHashTags = $iN->iN_hashtag($postText);
					$postData = $iN->iN_InsertNewPost(
						$userID,
						$iN->iN_Secure($postText),
						$postSlug,
						'',
						'1',
						$iN->url_Hash($postHashTags),
						'',
						$autoApprovePostStatus,
						'normal',
						null,
						'published'
					);
					if ($scheduledLiveId > 0 && $postData && isset($postData['post_id'])) {
						$iN->iN_SetScheduledLivePost($scheduledLiveId, (int)$userID, (int)$postData['post_id']);
					}
					$data = array(
						'status' => 'scheduled',
						'start' => $base_url . 'live/' . $userName,
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				} else {
					$data = array(
						'status' => '404',
						'start' => '',
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
			} else {
				if ($iN->iN_CheckUserHasActiveOrScheduledLive($userID)) {
					$data = array(
						'status' => 'live_exists',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				$createFreeLiveStreaming = $iN->iN_CreateAFreeLiveStreaming($userID, $liveStreamingTitle, $freeLiveTime, $channelName);
				if ($createFreeLiveStreaming) {
					$liveID = rq_get_live_id_by_channel((int)$userID, (string)$channelName);
					if ($notifyLive === 1 && $liveID > 0) {
						rq_send_live_started_notifications(
							$iN,
							(int)$userID,
							(string)$userName,
							(string)($userFullName ?? ''),
							(string)$notifyAudience,
							(int)$liveID,
							(string)$base_url,
							(string)$oneSignalApi,
							(string)$oneSignalRestApi,
							$LANG,
							$notifySelectedIds
						);
					}
					if ($s3Status == 1) {
						//$rect = $iN->iN_StartCloudRecording(1, $s3Region, $s3Bucket, $s3Key, $s3SecretKey, $streamingName, $uid, $liveID, $agoraAppID, $agoraCustomerID, $agoraCertificate);
					}
					$data = array(
						'status' => '200',
						'start' => $base_url . 'live/' . $userName,
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				} else {
					$data = array(
						'status' => '404',
						'start' => '',
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
			}
		} else {
			$data = array(
				'status' => 'require',
				'start' => '',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
	}
	if ($type == 'startScheduledLive') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			$data = array(
				'status' => 'csrf',
				'message' => $LANG['live_stream_invalid_csrf'] ?? ($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'),
				'start' => '',
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($liveID);
		if (!$liveDetails || (int)($liveDetails['live_uid_fk'] ?? 0) !== (int)$userID) {
			$data = array(
				'status' => '404',
				'start' => '',
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
		$startOk = $iN->iN_StartScheduledLive($userID, $liveID, $freeLiveTime);
		if ($startOk) {
			$notifyLive = (string)($liveDetails['notify_live'] ?? '0') === '1';
			$notifyAudience = $liveDetails['notify_audience'] ?? 'followers';
			$notifySelectedRaw = (string)($liveDetails['notify_selected_ids'] ?? '');
			$notifySelectedIds = array_filter(array_map('intval', explode(',', preg_replace('/[^0-9,]/', '', $notifySelectedRaw))));
			$recipientIds = [];
			$limit = 200;
			if ($notifyLive) {
				if ($notifyAudience === 'subscribers') {
					$rows = DB::all(
						"SELECT DISTINCT U.iuid
                         FROM i_user_subscriptions S
                         INNER JOIN i_users U ON U.iuid = S.iuid_fk
                         WHERE S.subscribed_iuid_fk = ? AND S.status = 'active' AND S.finished = '0' AND S.subscription_scope = 'profile'
                           AND U.uStatus IN('1','3') AND U.iuid <> ?
                         LIMIT {$limit}",
						[(int)$userID, (int)$userID]
					);
				} elseif ($notifyAudience === 'selected') {
					$notifySelectedIds = array_values(array_unique(array_map('intval', $notifySelectedIds)));
					$notifySelectedIds = array_filter($notifySelectedIds, function ($id) use ($userID) {
						return $id > 0 && $id !== (int)$userID;
					});
					$notifySelectedIds = array_slice($notifySelectedIds, 0, $limit);
					$rows = [];
					if (!empty($notifySelectedIds)) {
						$placeholders = implode(',', array_fill(0, count($notifySelectedIds), '?'));
						$params = array_merge([(int)$userID, (int)$userID], $notifySelectedIds, [(int)$userID]);
						$rows = DB::all(
							"SELECT DISTINCT U.iuid
                             FROM i_users U
                             LEFT JOIN i_friends F
                               ON F.fr_one = U.iuid AND F.fr_two = ? AND F.fr_status = 'flwr'
                             LEFT JOIN i_user_subscriptions S
                               ON S.iuid_fk = U.iuid AND S.subscribed_iuid_fk = ? AND S.status = 'active' AND S.finished = '0' AND S.subscription_scope = 'profile'
                             WHERE U.iuid IN ({$placeholders}) AND U.uStatus IN('1','3') AND U.iuid <> ?
                               AND (F.fr_one IS NOT NULL OR S.subscription_id IS NOT NULL)
                             LIMIT {$limit}",
							$params
						);
					}
				} else {
					$rows = DB::all(
						"SELECT DISTINCT U.iuid
                         FROM i_friends F
                         INNER JOIN i_users U ON U.iuid = F.fr_one
                         WHERE F.fr_two = ? AND F.fr_status = 'flwr'
                           AND U.uStatus IN('1','3') AND U.iuid <> ?
                         LIMIT {$limit}",
						[(int)$userID, (int)$userID]
					);
				}
				if (!empty($rows)) {
					foreach ($rows as $row) {
						$rid = (int)($row['iuid'] ?? 0);
						if ($rid > 0 && $rid !== (int)$userID) {
							$recipientIds[] = $rid;
						}
					}
				}
			}
			$reminderIds = $iN->iN_GetLiveReminderUserIds($liveID);
			if (!empty($reminderIds)) {
				$recipientIds = array_merge($recipientIds, $reminderIds);
			}
			$recipientIds = array_values(array_unique(array_filter($recipientIds, function ($id) use ($userID) {
				return $id > 0 && $id !== (int)$userID;
			})));
			if (!empty($recipientIds)) {
				rq_send_live_started_notifications_bulk(
					$iN,
					(int)$userID,
					(string)$userName,
					(string)($userFullName ?? ''),
					(int)$liveID,
					(string)$base_url,
					(string)$oneSignalApi,
					(string)$oneSignalRestApi,
					$LANG,
					$recipientIds
				);
			}
			$data = array(
				'status' => '200',
				'start' => $base_url . 'live/' . $userName,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
		$data = array(
			'status' => '404',
			'start' => '',
		);
		$result = json_encode($data, JSON_UNESCAPED_UNICODE);
		echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		exit();
	}
	if ($type == 'liveScheduleStatus') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($liveID);
		if (!$liveDetails) {
			echo json_encode(['status' => '404'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$now = time();
		$startedAt = (int)($liveDetails['started_at'] ?? 0);
		$finishTime = (int)($liveDetails['finish_time'] ?? 0);
		$scheduledAt = (int)($liveDetails['scheduled_at'] ?? 0);
		$state = 'scheduled';
		if ($startedAt > 0 && $finishTime > 0 && $finishTime >= $now) {
			$state = 'live';
		} elseif ($finishTime > 0 && $finishTime < $now) {
			$state = 'finished';
		}
		echo json_encode(
			[
				'status' => $state,
				'started_at' => $startedAt,
				'finish_time' => $finishTime,
				'scheduled_at' => $scheduledAt
			],
			JSON_UNESCAPED_UNICODE
		);
		exit();
	}
	if ($type == 'deleteScheduledLive') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$deleted = $iN->iN_DeleteScheduledLive((int)$userID, (int)$liveID);
		echo json_encode(['status' => $deleted ? '200' : '404'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'liveReminderToggle') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$enabled = isset($_POST['enabled']) && $_POST['enabled'] == '1';
		$set = $iN->iN_SetLiveReminder($liveID, $userID, $enabled);
		echo json_encode(['status' => $set ? '200' : '404', 'enabled' => $enabled ? 1 : 0], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'livePinProductModal') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit('csrf');
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		if ($logedIn != 1 || !$iN->iN_CheckLiveIDExistAndOwner($userID, $liveID)) {
			exit('404');
		}
		$products = DB::all(
			"SELECT pr_id, pr_name, pr_price, pr_files, pr_name_slug, product_type
             FROM i_user_product_posts FORCE INDEX(ixProduct)
             WHERE iuid_fk = ? AND pr_status IN('1')
             ORDER BY pr_id DESC LIMIT 50",
			[(int)$userID]
		);
		include "../themes/$currentTheme/layouts/popup_alerts/livePinProduct.php";
		exit();
	}
	if ($type == 'livePinProduct') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$productID = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
		$set = $iN->iN_SetLivePinnedProduct($liveID, $productID, $userID);
		$pinnedHtml = '';
		$pinnedId = '';
		if ($set) {
			$pinnedProduct = $iN->iN_GetLivePinnedProduct($liveID);
			if ($pinnedProduct) {
				$pinnedId = (int)($pinnedProduct['pr_id'] ?? 0);
				$isLiveCreator = true;
				ob_start();
				include "../themes/$currentTheme/layouts/live/live_pinned_product.php";
				$pinnedHtml = ob_get_clean();
			}
		}
		echo json_encode(['status' => $set ? '200' : '404', 'pinned' => $pinnedHtml, 'pinnedId' => $pinnedId], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'liveUnpinProduct') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$removed = $iN->iN_RemoveLivePinnedProduct($liveID, $userID);
		echo json_encode(['status' => $removed ? '200' : '404', 'pinned' => '', 'pinnedId' => ''], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'livePollFetch') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit('csrf');
		}
		if ((isset($livePollStatus) ? (string)$livePollStatus : '1') !== '1') {
			exit('');
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($liveID);
		if (!$liveDetails) {
			exit('404');
		}
		$isLiveCreator = ((int)$userID === (int)($liveDetails['live_uid_fk'] ?? 0));
		$pollData = $iN->iN_GetLivePollDetails($liveID, $userID);
		include "../themes/$currentTheme/layouts/live/live_poll_module.php";
		exit();
	}
	if ($type == 'livePollCreate') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		if ((isset($livePollStatus) ? (string)$livePollStatus : '1') !== '1') {
			echo json_encode(['status' => 'error', 'error' => 'feature_disabled'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$question = isset($_POST['question']) ? $iN->iN_Secure($_POST['question']) : '';
		$options = isset($_POST['options']) && is_array($_POST['options']) ? $_POST['options'] : [];
		$cleanOptions = [];
		foreach ($options as $opt) {
			$cleanOptions[] = $iN->iN_Secure($opt);
		}
		$result = $iN->iN_CreateLivePoll($liveID, $userID, $question, $cleanOptions);
		if (isset($result['error'])) {
			echo json_encode(['status' => 'error', 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
			exit();
		}
		echo json_encode(['status' => '200'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'livePollVote') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		if ((isset($livePollStatus) ? (string)$livePollStatus : '1') !== '1') {
			echo json_encode(['status' => 'error', 'error' => 'feature_disabled'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
		$optionId = isset($_POST['option_id']) ? (int)$_POST['option_id'] : 0;
		$result = $iN->iN_SubmitLivePollVote($pollId, $optionId, $userID);
		if (isset($result['error'])) {
			echo json_encode(['status' => 'error', 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
			exit();
		}
		echo json_encode(['status' => '200'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'livePollClose') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		if ((isset($livePollStatus) ? (string)$livePollStatus : '1') !== '1') {
			echo json_encode(['status' => '404'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
		$closed = $iN->iN_CloseLivePoll($liveID, $pollId, $userID);
		echo json_encode(['status' => $closed ? '200' : '404'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'liveQuestionFetch') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit('csrf');
		}
		if ((isset($liveQAStatus) ? (string)$liveQAStatus : '1') !== '1') {
			exit('');
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($liveID);
		if (!$liveDetails) {
			exit('404');
		}
		$isLiveCreator = ((int)$userID === (int)($liveDetails['live_uid_fk'] ?? 0));
		$questions = $iN->iN_GetLiveQuestions($liveID, $isLiveCreator);
		include "../themes/$currentTheme/layouts/live/live_qa_module.php";
		exit();
	}
	if ($type == 'liveQuestionAsk') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		if ((isset($liveQAStatus) ? (string)$liveQAStatus : '1') !== '1') {
			echo json_encode(['status' => 'error', 'error' => 'feature_disabled'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$question = isset($_POST['question']) ? $iN->iN_Secure($_POST['question']) : '';
		$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($liveID);
		if (!$liveDetails) {
			echo json_encode(['status' => '404'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$isLiveCreator = ((int)$userID === (int)($liveDetails['live_uid_fk'] ?? 0));
		$result = $iN->iN_CreateLiveQuestion($liveID, $userID, $question, $isLiveCreator);
		if (isset($result['error'])) {
			echo json_encode(['status' => 'error', 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
			exit();
		}
		echo json_encode(['status' => '200', 'approval' => $result['status'] ?? 'pending'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'liveQuestionUpdate') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'csrf'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		if ((isset($liveQAStatus) ? (string)$liveQAStatus : '1') !== '1') {
			echo json_encode(['status' => '404'], JSON_UNESCAPED_UNICODE);
			exit();
		}
		$liveID = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
		$questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
		$status = isset($_POST['status']) ? (string)$_POST['status'] : '';
		$updated = $iN->iN_UpdateLiveQuestionStatus($liveID, $questionId, $userID, $status);
		echo json_encode(['status' => $updated ? '200' : '404'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	if ($type == 'l_like') {
		if (isset($_POST['post'])) {
			$postID = $iN->iN_Secure($_POST['post']);
			$likePost = $iN->iN_LiveLike($userID, $postID);
			$status = 'lin_like';
			$pLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePost) {
				$status = 'lin_unlike';
				$pLike = $iN->iN_SelectedMenuIcon('18');
			}
			$likeSum = $iN->iN_TotalLiveLiked($postID);
			if ($likeSum == 0) {
				$likeSum = '';
			} else {
				$likeSum = $likeSum;
			}
			$data = array(
				'status' => $status,
				'like' => $pLike,
				'likeCount' => $likeSum,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
/*Create a Free Live Streaming*/
	if ($type == 'createPaidLiveStream') {
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			$data = array(
				'status' => 'csrf',
				'message' => $LANG['live_stream_invalid_csrf'] ?? ($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'),
				'start' => '',
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
		if (isset($_POST['lTitle']) && !empty($_POST['lTitle']) && isset($_POST['pointfee']) && !empty($_POST['pointfee'])) {
			$liveStreamingTitle = $iN->iN_Secure($_POST['lTitle']);
			$liveStreamFee = $iN->iN_Secure($_POST['pointfee']);
			$notifyLive = isset($_POST['notify_live']) && $_POST['notify_live'] == '1' ? 1 : 0;
			$notifyAudience = isset($_POST['notify_audience']) ? $iN->iN_Secure($_POST['notify_audience']) : 'followers';
			if (!in_array($notifyAudience, ['followers', 'subscribers', 'selected'], true)) {
				$notifyAudience = 'followers';
			}
			$notifySelectedRaw = isset($_POST['notify_selected_ids']) ? (string)$_POST['notify_selected_ids'] : '';
			$notifySelectedIds = array_filter(array_map('intval', explode(',', preg_replace('/[^0-9,]/', '', $notifySelectedRaw))));
			$scheduledAt = isset($_POST['scheduled_at']) ? (int)$_POST['scheduled_at'] : 0;
			$isScheduled = $scheduledAt > 0;
			$rand = rand(1111111, 9999999);
			$channelName = "stream_" . $userID . "_" . $rand;
			if (empty($liveStreamFee) || $liveStreamFee < $minimumLiveStreamingFee) {
				$data = array(
					'status' => 'point',
					'start' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			if ($isScheduled) {
				if (!isset($scheduledPostsStatus) || (string)$scheduledPostsStatus !== '1') {
					$data = array(
						'status' => 'schedule_disabled',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				$now = time();
				$minLead = 5 * 60;
				$maxDays = isset($scheduledMaxDelayDays) ? (int)$scheduledMaxDelayDays : 30;
				if ($maxDays < 1) { $maxDays = 30; }
				if ($maxDays > 30) { $maxDays = 30; }
				if ($scheduledAt < ($now + $minLead)) {
					$data = array(
						'status' => 'schedule_invalid',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				if ($scheduledAt > ($now + ($maxDays * 86400))) {
					$data = array(
						'status' => 'schedule_limit',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				if ($iN->iN_CheckUserHasActiveOrScheduledLive($userID)) {
					$data = array(
						'status' => 'live_exists',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
					$createScheduled = $iN->iN_CreateScheduledLive($userID, $liveStreamingTitle, $channelName, 'paid', $liveStreamFee, $scheduledAt, $notifyLive, $notifyAudience, $notifySelectedIds);
					if ($createScheduled) {
						$scheduledLiveId = (int)$createScheduled;
						$scheduledText = date('M d, Y H:i', (int)$scheduledAt);
						$liveUrl = $base_url . 'live/' . $userName;
						$postTemplate = $LANG['live_scheduled_post_text'] ?? 'Scheduled live: {title} - {time} {url}';
						$postText = str_replace(
							['{title}', '{time}', '{url}'],
							[(string)$liveStreamingTitle, (string)$scheduledText, '__LIVE_URL__'],
							$postTemplate
						);
						$postText = str_replace('__LIVE_URL__', "\n" . (string)$liveUrl, $postText);
						$postText = str_replace(" \n", "\n", $postText);
						$postSlug = $iN->url_slugies(mb_substr($postText, 0, 55, 'utf-8'));
						$postHashTags = $iN->iN_hashtag($postText);
						$postData = $iN->iN_InsertNewPost(
							$userID,
							$iN->iN_Secure($postText),
							$postSlug,
							'',
							'1',
							$iN->url_Hash($postHashTags),
							'',
							$autoApprovePostStatus,
							'normal',
							null,
							'published'
						);
						if ($scheduledLiveId > 0 && $postData && isset($postData['post_id'])) {
							$iN->iN_SetScheduledLivePost($scheduledLiveId, (int)$userID, (int)$postData['post_id']);
						}
						$data = array(
							'status' => 'scheduled',
							'start' => $base_url . 'live/' . $userName,
						);
						$result = json_encode($data, JSON_UNESCAPED_UNICODE);
						echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					} else {
						$data = array(
							'status' => '404',
							'start' => '',
						);
						$result = json_encode($data);
						echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
						exit();
					}
				} else {
					$data = array(
						'status' => '404',
						'start' => '',
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
			} else {
				if ($iN->iN_CheckUserHasActiveOrScheduledLive($userID)) {
					$data = array(
						'status' => 'live_exists',
						'start' => '',
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
				if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
					$createPaidLiveStreaming = $iN->iN_CreateAPaidLiveStreaming($userID, $liveStreamingTitle, $freeLiveTime, $channelName, $liveStreamFee);
					if ($createPaidLiveStreaming) {
						$liveID = rq_get_live_id_by_channel((int)$userID, (string)$channelName);
						if ($notifyLive === 1 && $liveID > 0) {
							rq_send_live_started_notifications(
								$iN,
								(int)$userID,
								(string)$userName,
								(string)($userFullName ?? ''),
								(string)$notifyAudience,
								(int)$liveID,
								(string)$base_url,
								(string)$oneSignalApi,
								(string)$oneSignalRestApi,
								$LANG,
								$notifySelectedIds
							);
						}
						$data = array(
							'status' => '200',
							'start' => $base_url . 'live/' . $userName,
						);
						$result = json_encode($data, JSON_UNESCAPED_UNICODE);
						echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					} else {
						$data = array(
							'status' => '404',
							'start' => '',
						);
						$result = json_encode($data);
						echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
						exit();
					}
				} else {
					$data = array(
						'status' => '404',
						'start' => '',
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
			}
		} else {
			$data = array(
				'status' => 'require',
				'start' => '',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
	}
/*Purchase Post*/
	if ($type == 'goWalletLive') {
		if (isset($_POST['p']) && isset($_POST['p'])) {
			$purchaseLiveStreamID = $iN->iN_Secure($_POST['p']);
			$checkLiveID = $iN->iN_CheckLiveIDExist($purchaseLiveStreamID);
			if ($checkLiveID) {
				$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($purchaseLiveStreamID);
				$liveID = $liveDetails['live_id'];
				$liveCreatorWantedCredit = $liveDetails['live_credit'];
				$liveCreator = $liveDetails['live_uid_fk'];
				$liveCreatorDetail = $iN->iN_GetUserDetails($liveCreator);
				$liveCreatorUserName = $liveCreatorDetail['i_username'];

				$translatePointToMoney = $liveCreatorWantedCredit * $onePointEqual;
				$adminEarning = $translatePointToMoney * ($adminFee / 100);
				$userEarning = $translatePointToMoney - $adminEarning;

				if ($userCurrentPoints >= $liveCreatorWantedCredit && $userID != $liveCreator) {
					$buyLiveStream = $iN->iN_BuyLiveStreaming($userID, $liveCreator, $liveID, $translatePointToMoney, $adminEarning, $userEarning, $adminFee, $liveCreatorWantedCredit);
					if ($buyLiveStream) {
						echo iN_HelpSecure($base_url) . 'live/' . $liveCreatorUserName;
					} else {
						echo $LANG['something_wrong'];
					}
				} else {
					echo iN_HelpSecure($base_url) . 'purchase/purchase_point';
				}
			}
		}
	}
/*More Paid Live Streamins or Free Paid Live Streamins*/
	if ($type == 'paid' || $type == 'free') {
		if (isset($_POST['last'])) {
			$liveListType = $type;
			include "../themes/$currentTheme/layouts/live/live_list.php";
		}
	}
	if ($type == 'pLivePurchase') {
		if (isset($_POST['purchase']) && $_POST['purchase'] != '' && !empty($_POST['purchase'])) {
			$liveID = $iN->iN_Secure($_POST['purchase']);
			$checkliveExist = $iN->iN_CheckLiveIDExist($liveID);
			if ($checkliveExist) {
				$liData = $iN->iN_GetLiveStreamingDetailsByID($liveID);
				$liveCreatorID = $liData['live_uid_fk'];
				$liveCreatorAvatar = $iN->iN_UserAvatar($liveCreatorID, $base_url);
				$liveCredit = isset($liData['live_credit']) ? $liData['live_credit'] : NULL;
				if ($userID != $liveCreatorID) {
					include "../themes/$currentTheme/layouts/popup_alerts/purchaseLiveStream.php";
				}
			}
		}
	}
	if ($type == 'unSub') {
		if (isset($_POST['u']) && !empty($_POST['u'])) {
			$ui = $iN->iN_Secure($_POST['u']);
			$checkUserExist = $iN->iN_CheckUserExist($ui);
			$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $ui);
			if ($friendsStatus == 'subscriber') {
				include "../themes/$currentTheme/layouts/popup_alerts/sureUnSubscribe.php";
			}
		}
	}
	if ($type == 'communityUnsubModal') {
		if ($logedIn != 1) {
			exit;
		}
		if (isset($_POST['community_id']) && !empty($_POST['community_id'])) {
			include_once __DIR__ . '/../includes/csrf.php';
			$communityID = (int) $iN->iN_Secure($_POST['community_id']);
			$membership = $iN->iN_GetCommunityMembership($communityID, $userID);
			if ($membership && ($membership['status'] ?? '') === 'active') {
				$communityData = $iN->iN_GetCommunityById($communityID);
				if ($communityData) {
					include "../themes/$currentTheme/layouts/popup_alerts/communityUnsubscribe.php";
				}
			}
		}
	}
	if ($type == 'unSubscribe') {
		if (isset($_POST['id'])) {
			$uID = $iN->iN_Secure($_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$status = '404';
                    $errorMessage = '';
					$redirect = $base_url . 'settings?tab=subscriptions';
					if ($friendsStatus == 'subscriber') {
						$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
						if ($getSubsData) {
							$paymentSubscriptionID = isset($getSubsData['payment_subscription_id']) ? $getSubsData['payment_subscription_id'] : null;
							$subscriptionID = isset($getSubsData['subscription_id']) ? $getSubsData['subscription_id'] : null;
							$paymentMethod = isset($getSubsData['payment_method']) ? $getSubsData['payment_method'] : '';
							try {
								if ($paymentMethod === 'stripe') {
									\Stripe\Stripe::setApiKey($stripeKey);
									if (!empty($paymentSubscriptionID)) {
										$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
										$subscription->cancel();
									}
									if ($subscriptionID) {
										$iN->iN_UpdateSubscriptionStatus($subscriptionID);
									}
									$iN->iN_UnSubscriberUser($userID, $uID, $unSubscribeStyle);
									$status = '200';
								} else if ($paymentMethod === 'authorizenet') {
									include_once("../includes/authorizeCancelSubs.php");
									if ($subscriptionID) {
										$iN->iN_UpdateSubscriptionStatus($subscriptionID);
									}
									$iN->iN_UnSubscriberUser($userID, $uID, $unSubscribeStyle);
									if (!defined('DONT_RUN_SAMPLES') && !empty($paymentSubscriptionID)) {
										cancelSubscription($paymentSubscriptionID, $autName, $autKey);
									}
									$status = '200';
								} else if ($paymentMethod === 'ccbill') {
									if (!empty($paymentSubscriptionID)) {
										$ccbillService = new CcbillService();
										$cancelResponse = $ccbillService->cancelSubscription($paymentSubscriptionID);
										if (empty($cancelResponse['status'])) {
											$message = isset($cancelResponse['message']) ? $cancelResponse['message'] : 'CCBill cancellation failed.';
											throw new Exception($message, 1);
										}
									}
									if ($subscriptionID) {
										$iN->iN_UpdateSubscriptionStatus($subscriptionID);
									}
									$iN->iN_UnSubscriberUser($userID, $uID, $unSubscribeStyle);
									$status = '200';
								} else if ($paymentMethod === 'flutterwave') {
									if (!empty($paymentSubscriptionID) && strpos((string)$paymentSubscriptionID, 'flw_') !== 0) {
										$flutterwaveService = new FlutterwaveService();
										$cancelResponse = $flutterwaveService->cancelSubscription($paymentSubscriptionID);
										if (empty($cancelResponse['status'])) {
											$message = $cancelResponse['errorMessage'] ?? 'Flutterwave cancellation failed.';
											throw new Exception($message, 1);
										}
									}
									if ($subscriptionID) {
										$iN->iN_UpdateSubscriptionStatus($subscriptionID);
									}
									$iN->iN_UnSubscriberUser($userID, $uID, $unSubscribeStyle);
									$status = '200';
								} else {
									if ($subscriptionID) {
										$iN->iN_UpdateSubscriptionStatus($subscriptionID);
									}
									$iN->iN_UnSubscriberUser($userID, $uID, $unSubscribeStyle);
									$status = '200';
								}
							} catch (\Exception $e) {
								$errorMessage = $e->getMessage();
							}
						}
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
                    if (!empty($errorMessage)) {
                        $data['message'] = $errorMessage;
                    }
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	if ($type == 'communityUnsubscribe') {
		if (isset($_POST['community_id'])) {
			header('Content-Type: application/json; charset=utf-8');
			if ($logedIn != 1) {
				echo json_encode(['status' => 'error', 'message' => $LANG['not_logged_in'] ?? 'Not logged in.']);
				exit;
			}
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
				exit;
			}
			$communityID = (int) $iN->iN_Secure($_POST['community_id']);
			$membership = $iN->iN_GetCommunityMembership($communityID, $userID);
			$status = '404';
			$errorMessage = '';
			$redirect = $base_url . 'communities';
			if ($membership && ($membership['status'] ?? '') === 'active') {
				$subscriptionID = isset($membership['subscription_id']) ? (int)$membership['subscription_id'] : 0;
				$subscriptionData = $subscriptionID > 0 ? DB::one("SELECT * FROM i_user_subscriptions WHERE subscription_id = ? LIMIT 1", [$subscriptionID]) : null;
				if ($subscriptionData) {
					$paymentSubscriptionID = $subscriptionData['payment_subscription_id'] ?? null;
					$paymentMethod = $subscriptionData['payment_method'] ?? '';
					try {
						if ($paymentMethod === 'stripe') {
							\Stripe\Stripe::setApiKey($stripeKey);
							if (!empty($paymentSubscriptionID)) {
								$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
								$subscription->cancel();
							}
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$status = '200';
						} else if ($paymentMethod === 'ccbill') {
							if (!empty($paymentSubscriptionID)) {
								$ccbillService = new CcbillService();
								$cancelResponse = $ccbillService->cancelSubscription($paymentSubscriptionID);
								if (empty($cancelResponse['status'])) {
									$message = isset($cancelResponse['message']) ? $cancelResponse['message'] : 'CCBill cancellation failed.';
									throw new Exception($message, 1);
								}
							}
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$status = '200';
						} else if ($paymentMethod === 'authorizenet') {
							include_once("../includes/authorizeCancelSubs.php");
							if (!defined('DONT_RUN_SAMPLES') && !empty($paymentSubscriptionID)) {
								cancelSubscription($paymentSubscriptionID, $autName, $autKey);
							}
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$status = '200';
						} else if ($paymentMethod === 'flutterwave') {
							if (!empty($paymentSubscriptionID) && strpos((string)$paymentSubscriptionID, 'flw_') !== 0) {
								$flutterwaveService = new FlutterwaveService();
								$cancelResponse = $flutterwaveService->cancelSubscription($paymentSubscriptionID);
								if (empty($cancelResponse['status'])) {
									$message = $cancelResponse['errorMessage'] ?? 'Flutterwave cancellation failed.';
									throw new Exception($message, 1);
								}
							}
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$status = '200';
						} else {
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$status = '200';
						}
					} catch (\Exception $e) {
						$errorMessage = $e->getMessage();
					}
				}
				if ($status === '200') {
					$iN->iN_UpdateCommunityMembershipStatus($communityID, $userID, 'canceled', date('Y-m-d H:i:s'));
				}
			}
			$data = array(
				'status' => $status,
				'redirect' => $redirect,
			);
			if (!empty($errorMessage)) {
				$data['message'] = $errorMessage;
			}
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	if ($type == 'unSubP') {
		if (isset($_POST['u']) && !empty($_POST['u'])) {
			$ui = $iN->iN_Secure($_POST['u']);
			$checkUserExist = $iN->iN_CheckUserExist($ui);
			$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $ui);
			if ($friendsStatus == 'subscriber') {
				include "../themes/$currentTheme/layouts/popup_alerts/sureUnSubscribePoint.php";
			}
		}
	}
	if ($type == 'unSubscribePoint') {
		if (isset($_POST['id'])) {
			$uID = $iN->iN_Secure($_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$status = '404';
					$redirect = $base_url . 'settings?tab=subscriptions';
					if ($friendsStatus == 'subscriber') {
						$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
						$subscriptionID = $getSubsData['subscription_id'];
						$iN->iN_UpdateSubscriptionStatus($subscriptionID);
						$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
						$status = '200';
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	/*Finish Live Streaming*/
	if($type == 'finishLive'){
      if(isset($_POST['lid']) && !empty($_POST['lid']) && $_POST['lid'] != ''){
         $liveID = $iN->iN_Secure($_POST['lid']);
		 $finishLiveStreaming = $iN->iN_FinishLiveStreaming($userID, $liveID);
		 if($finishLiveStreaming){
             echo 'finished';
		 }
	  }
	}
	/*Block Country*/
	if($type == 'bCountry'){
      if(isset($_POST['c']) && array_key_exists($_POST['c'],$COUNTRIES)){
         $blockingCountryCode = $iN->iN_Secure($_POST['c']);
		 $checkCountryCodeBlockedBefore = $iN->iN_CheckCountryBlocked($userID, $blockingCountryCode);
		 if(!$checkCountryCodeBlockedBefore){
            $insertCountryCodeInBlockedList = $iN->iN_InsertCountryInBlockList($userID, $iN->iN_Secure($blockingCountryCode));
			if($insertCountryCodeInBlockedList){
              echo '1';
			}
		 }else{
			$removeCountryCodeInBlockedList = $iN->iN_RemoveCountryInBlockList($userID, $iN->iN_Secure($blockingCountryCode));
			if($removeCountryCodeInBlockedList){
              echo '0';
			}
		 }
	  }
	}
	/*Open Tip Box*/
	if($type == 'p_tips'){
		if(isset($_POST['tip_u']) && !empty($_POST['tip_u']) && $_POST['tip_u'] !== ''){
			$tipingUserID = $iN->iN_Secure($_POST['tip_u']);
			$tipPostID = $iN->iN_Secure($_POST['tpid']);
            $tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
			$f_userfullname = $tipingUserDetails['i_user_fullname'];
			include "../themes/$currentTheme/layouts/popup_alerts/sendTipPoint.php";
		}
	}
    /*Open Campaign Donate Box*/
    if($type == 'p_campaign_donate'){
        if(isset($_POST['tip_u']) && !empty($_POST['tip_u']) && $_POST['tip_u'] !== ''){
            $tipingUserID = $iN->iN_Secure($_POST['tip_u']);
            $tipPostID = $iN->iN_Secure($_POST['tpid']);
            $tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
            $f_userfullname = $tipingUserDetails['i_user_fullname'];
            include "../themes/$currentTheme/layouts/popup_alerts/sendCampaignDonate.php";
        }
    }
	/*Send Tip*/
	if($type == 'p_sendTip'){
      if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
         $tiSendingUserID = $iN->iN_Secure($_POST['tip_u']);
		 $tipAmount = $iN->iN_Secure($_POST['tipVal']);
		 $tipPostID = $iN->iN_Secure($_POST['tpid']);
		 $redirect = '';
		 $emountnot = '';
		 $status = '';
		 if($tipAmount < $minimumTipAmount){
            $emountnot = 'notEnough';
		 }else{
			if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

				$netUserEarning = $tipAmount * $onePointEqual;
                $adminEarning = ($adminFee * $netUserEarning) / 100;
				$userNetEarning = $netUserEarning - $adminEarning;

				$UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
				if($UpdateUsersWallet){
                   $status = 'ok';
				}else{
				   $status = '404';
				}
			 }else{
				$status = '';
				$emountnot = 'notEnouhCredit';
				$redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
			 }
		 }
		 $data = array(
			'status' => $status,
			'redirect' => $redirect,
			'enamount' => $emountnot
		 );
		 $result = json_encode($data);
		 echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		 if($status == 'ok'){
            $iN->iN_InsertNotificationForTip($userID, $tiSendingUserID, (int)$tipPostID);
			$userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
			$toUserName = $userDeviceKey['i_username'];
			$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
			$msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
			$msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
			$URL = $base_url.'settings?tab=dashboard';
			if($oneSignalUserDeviceKey){
			  $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $URL, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
			}
            if ((string)$webPushStatus === '1' && (string)$webPushEventTipReceived === '1') {
                $iN->iN_SendWebPushToUser((int)$tiSendingUserID, [
                    'title' => $msgTitle,
                    'body' => $msgBody,
                    'url' => $URL,
                    'tag' => 'tip_received_' . (int)$tipPostID,
                ]);
            }
			$tipPostID = (int)$tipPostID;
			if ($tipPostID > 0) {
				$communityMeta = $iN->iN_GetCommunityPostMeta($tipPostID);
				if (!empty($communityMeta['community_id'])) {
					$communityData = $iN->iN_GetCommunityById((int)$communityMeta['community_id']);
					$communityOwnerId = $communityData ? (int)($communityData['owner_user_id'] ?? 0) : 0;
					if ($communityOwnerId > 0 && $communityData) {
						$iN->iN_InsertCommunityNotification($userID, $communityOwnerId, $communityData, 'community_tip', $tipPostID);
					}
				}
			}
		 }
	  }
    }
    /*Send Campaign Donation (wallet points)*/
    if ($type == 'p_campaign_donate_send') {
        if (isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' && $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])) {
            $tiSendingUserID = $iN->iN_Secure($_POST['tip_u']);
            $tipAmountRaw = (string)$_POST['tipVal'];
            $tipAmount = rq_parse_amount_value($tipAmountRaw);
            $tipAmountMoney = (float)$tipAmount * (float)$onePointEqual;
            $tipPostID = $iN->iN_Secure($_POST['tpid']);
            $redirect = '';
            $emountnot = '';
            $status = '';
            $dbErrorDetail = '';

            if ($tipAmount <= 0) {
                $emountnot = 'notEnough';
            } else {
                // Fetch campaign to respect min/max/goal
                $campaignRow = DB::one("SELECT campaign_id, owner_uid_fk, goal_amount, min_amount, max_amount, raised_amount, deadline_at, status FROM i_campaigns WHERE post_id_fk = ? LIMIT 1", [(int)$tipPostID]);

                if (!$campaignRow) {
                    $status = 'error';
                    $emountnot = 'notFound';
                } else {
                    $tiSendingUserID = (int)$campaignRow['owner_uid_fk'];
                    $campaignStatusRow = isset($campaignRow['status']) ? (string)$campaignRow['status'] : '';
                    $campaignDeadlineTs = isset($campaignRow['deadline_at']) ? (int)$campaignRow['deadline_at'] : 0;
                    if ($campaignStatusRow === 'expired' || ($campaignDeadlineTs > 0 && $campaignDeadlineTs < time())) {
                        $status = '';
                        $emountnot = 'expired';
                    } else {
                    $minAllowed = isset($campaignRow['min_amount']) && $campaignRow['min_amount'] !== null ? rq_parse_amount_value($campaignRow['min_amount']) : 0.0;
                    $maxAllowed = isset($campaignRow['max_amount']) && $campaignRow['max_amount'] !== null ? rq_parse_amount_value($campaignRow['max_amount']) : 0.0;

                    if ($minAllowed > 0 && $tipAmountMoney < $minAllowed) {
                        $emountnot = 'minLimit';
                    } elseif ($maxAllowed > 0 && $tipAmountMoney > $maxAllowed) {
                        $emountnot = 'maxLimit';
                    } else {
                        $walletPointsRaw = DB::col("SELECT wallet_points FROM i_users WHERE iuid = ? LIMIT 1", [(int)$userID]);
                        $walletPoints = rq_parse_amount_value($walletPointsRaw);

                        if ($walletPoints >= $tipAmount) {
                            $tipAmountPointDb = number_format($tipAmount, 2, '.', '');
                            $tipAmountMoneyDb = number_format($tipAmountMoney, 2, '.', '');
                            $split = $iN->iN_CalculateAgencySplit($tiSendingUserID, $tipAmountMoneyDb, $adminFee);
                            $adminEarning = $split['admin_earning'];
                            $userNetEarning = $split['creator_net'];
                            $agencyId = $split['agency_id'];
                            $agencyFee = $split['agency_fee'];
                            $agencyEarning = $split['agency_earning'];
                            $currentRaised = isset($campaignRow['raised_amount']) ? rq_parse_amount_value($campaignRow['raised_amount']) : 0.0;
                            $goalAmount = isset($campaignRow['goal_amount']) ? rq_parse_amount_value($campaignRow['goal_amount']) : 0.0;
                            $raisedAfter = $currentRaised + (float)$tipAmountMoneyDb;
                            if ($goalAmount > 0) {
                                $progressAfter = min(100, round(($raisedAfter / $goalAmount) * 100, 2));
                            } else {
                                $progressAfter = 0;
                            }

                            try {
                                DB::begin();
                                DB::exec("UPDATE i_users SET wallet_points = wallet_points - ? WHERE iuid = ?", [(string)$tipAmountPointDb, (int)$userID]);
                                DB::exec("UPDATE i_users SET wallet_money = wallet_money + ? WHERE iuid = ?", [(string)$userNetEarning, (int)$tiSendingUserID]);
                                DB::exec(
                                    "INSERT INTO i_user_payments(payer_iuid_fk, payed_iuid_fk, agency_id_fk, payment_type, payment_option, payment_time, payment_status, amount, user_earning, admin_earning, agency_earning, fee, agency_fee, payed_post_id_fk, is_anonymous)
                                         VALUES(?, ?, ?, 'campaign_donate', 'wallet', ?, 'ok', ?, ?, ?, ?, ?, ?, ?, ?)",
                                    [
                                        (int)$userID,
                                        (int)$tiSendingUserID,
                                        $agencyId,
                                        time(),
                                        (string)$tipAmountMoneyDb,
                                        (string)$userNetEarning,
                                        (string)$adminEarning,
                                        (string)$agencyEarning,
                                        (string)$adminFee,
                                        (string)$agencyFee,
                                        (int)$tipPostID,
                                        isset($_POST['donate_anonymous']) ? (int)$iN->iN_Secure($_POST['donate_anonymous']) : 0
                                    ]
                                );
                                DB::exec(
                                    "UPDATE i_campaigns SET raised_amount = raised_amount + ?, updated_at = ? WHERE post_id_fk = ? LIMIT 1",
                                    [(string)$tipAmountMoneyDb, time(), (int)$tipPostID]
                                );
                            DB::commit();
                            $status = 'ok';
                            // Notify campaign owner about the donation
                            if (function_exists('iN') || isset($iN)) {
                                $iN->iN_InsertNotificationForCampaignDonation((int)$userID, (int)$tiSendingUserID, (int)$tipPostID, (float)$tipAmountMoneyDb);
                            }
                        } catch (Throwable $e) {
                            DB::rollBack();
                            $status = 'error';
                            $emountnot = 'db_error';
                            $redirect = '';
                                $dbErrorDetail = $e->getMessage();
                                error_log('[campaign_donate] ' . $e->getMessage());
                            }
                        } else {
                            $status = '';
                            $emountnot = 'notEnough';
                            $redirect = iN_HelpSecure($base_url) . 'purchase/purchase_point';
                        }
                    }
                    }
                }
            }

            $data = array(
                'status' => $status,
                'redirect' => $redirect,
                'enamount' => $emountnot,
                'error' => $dbErrorDetail,
                'raised' => isset($raisedAfter) ? number_format($raisedAfter, 2, '.', '') : null,
                'progress' => isset($progressAfter) ? $progressAfter : null,
                'currency' => isset($currencys[$defaultCurrency]) ? $currencys[$defaultCurrency] : ''
            );
            $result = json_encode($data);
            echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
            if ($status == 'ok') {
                $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
                $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : null;
                if ($oneSignalUserDeviceKey) {
                    $msgBody = $iN->iN_Secure($LANG['campaign_donate_send'] ?? '');
                    $msgTitle = $iN->iN_Secure($LANG['campaign_donate_title'] ?? '');
                    $url = $base_url . 'notifications';
                    $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
                }
            }
        }
    }
	/*Coin Payment*/
		if($type == 'cop'){
	      if(isset($_POST['p']) && !empty($_POST['p']) && $_POST['p'] != ''){
	         $paymentTypeForCoin = isset($_POST['payment_type']) ? strtolower((string) $iN->iN_Secure($_POST['payment_type'])) : 'point';
	         $pointTypeID = $iN->iN_Secure($_POST['p']);
		 $planAmount = null;
		 $planPoint = null;
         $tipAmount = null;
         $payedUserId = null;
	         $payedPostId = null;
	         $orderKey = null;
	         $agencyId = null;
	         $creatorId = null;
	         $durationDays = null;
	         $isAnonymous = 0;
	         if ($paymentTypeForCoin === 'tips') {
	            $tipAmount = isset($_POST['order_amount']) ? rq_parse_amount_value($_POST['order_amount']) : null;
	            $payedUserId = isset($_POST['payed_user_id']) ? (int) $iN->iN_Secure($_POST['payed_user_id']) : null;
	            $payedPostId = isset($_POST['payed_post_id']) ? (int) $iN->iN_Secure($_POST['payed_post_id']) : null;
	            $orderKey = isset($_POST['order_id']) ? $iN->iN_Secure($_POST['order_id']) : null;
	            $planAmount = $tipAmount;
	         } elseif ($paymentTypeForCoin === 'campaign_donate') {
	            $campaignAmount = isset($_POST['order_amount']) ? rq_parse_amount_value($_POST['order_amount']) : null;
	            $payedUserId = isset($_POST['payed_user_id']) ? (int) $iN->iN_Secure($_POST['payed_user_id']) : null;
	            $payedPostId = isset($_POST['payed_post_id']) ? (int) $iN->iN_Secure($_POST['payed_post_id']) : null;
	            $orderKey = isset($_POST['order_id']) ? $iN->iN_Secure($_POST['order_id']) : null;
	            $isAnonymous = isset($_POST['is_anonymous']) && (string) $iN->iN_Secure($_POST['is_anonymous']) === '1' ? 1 : 0;
	            if (!$payedUserId || !$payedPostId || $campaignAmount === null || $campaignAmount <= 0 || (int) $payedUserId === (int) $userID) {
	                exit('404');
	            }
	            $campaignRow = DB::one(
	                "SELECT campaign_id, owner_uid_fk, min_amount, max_amount, status, deadline_at FROM i_campaigns WHERE post_id_fk = ? LIMIT 1",
	                [(int)$payedPostId]
	            );
	            if (!$campaignRow || (int)($campaignRow['owner_uid_fk'] ?? 0) !== (int) $payedUserId) {
	                exit('404');
	            }
	            $campaignStatusRow = isset($campaignRow['status']) ? (string)$campaignRow['status'] : '';
	            $campaignDeadlineTs = isset($campaignRow['deadline_at']) ? (int)$campaignRow['deadline_at'] : 0;
	            if ($campaignStatusRow === 'expired' || ($campaignDeadlineTs > 0 && $campaignDeadlineTs < time())) {
	                exit('404');
	            }
	            $minAllowed = isset($campaignRow['min_amount']) && $campaignRow['min_amount'] !== null ? rq_parse_amount_value($campaignRow['min_amount']) : 0.0;
	            $maxAllowed = isset($campaignRow['max_amount']) && $campaignRow['max_amount'] !== null ? rq_parse_amount_value($campaignRow['max_amount']) : 0.0;
	            if (($minAllowed > 0 && $campaignAmount < $minAllowed) || ($maxAllowed > 0 && $campaignAmount > $maxAllowed)) {
	                exit('404');
	            }
	            $planAmount = $campaignAmount;
	         } elseif ($paymentTypeForCoin === 'agency_boost') {
            if (!$agencyModuleEnabled) {
                exit($LANG['agency_module_disabled']);
            }
            $agencyId = isset($_POST['agency_id']) ? (int) $iN->iN_Secure($_POST['agency_id']) : 0;
            $creatorId = isset($_POST['creator_id']) ? (int) $iN->iN_Secure($_POST['creator_id']) : 0;
            $durationRaw = isset($_POST['duration_days']) ? trim((string) $_POST['duration_days']) : '';
            if ($agencyId <= 0 || $creatorId <= 0) {
                exit($LANG['agency_boost_invalid']);
            }
            if (!$iN->iN_IsAgencyOwnerForAgency($agencyId, $userID)) {
                exit($LANG['noway_desc']);
            }
            $agency = DB::one("SELECT agency_status FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
            if (!$agency) {
                exit($LANG['agency_not_found']);
            }
            if (($agency['agency_status'] ?? '') !== 'active') {
                exit($LANG['agency_inactive']);
            }
            if ($iN->iN_IsAgencyMember($agencyId, $creatorId) !== true || $iN->iN_CheckUserIsCreator($creatorId) != 1) {
                exit($LANG['agency_boost_member_only']);
            }
            $durationDays = 0;
            if ($durationRaw !== '') {
                if (!preg_match('/^[0-9]+$/', $durationRaw)) {
                    exit($LANG['agency_boost_invalid_duration']);
                }
                $durationDays = (int) $durationRaw;
                if ($durationDays < 1 || $durationDays > 365) {
                    exit($LANG['agency_boost_invalid_duration']);
                }
            }
            $iN->iN_ExpireAgencyBoosts($agencyId);
            $now = time();
            $maxActive = (int) $iN->iN_GetSetting('agency_boost_max_active', 3);
            if ($maxActive > 0) {
                $activeCount = (int) DB::col(
                    "SELECT COUNT(*) FROM i_agency_boosts WHERE agency_id_fk = ? AND status = 'active' AND end_at > ?",
                    [$agencyId, $now]
                );
                if ($activeCount >= $maxActive) {
                    exit($LANG['agency_boost_limit_reached']);
                }
            }
            $existingActive = DB::col(
                "SELECT 1 FROM i_agency_boosts WHERE agency_id_fk = ? AND creator_iuid_fk = ? AND status = 'active' AND end_at > ? LIMIT 1",
                [$agencyId, $creatorId, $now]
            );
            if ($existingActive) {
                exit($LANG['agency_boost_already_active']);
            }
            $agencyBoostPrice = (float) $iN->iN_GetSetting('agency_boost_price', 0.0);
            if ($agencyBoostPrice <= 0) {
                exit($LANG['agency_boost_invalid']);
            }
            if ($durationDays === null || $durationDays < 1) {
                $durationDays = (int) $iN->iN_GetSetting('agency_boost_default_days', 7);
            }
            if ($durationDays < 1) {
                $durationDays = 1;
            }
            if ($durationDays > 365) {
                $durationDays = 365;
            }
            $planAmount = $agencyBoostPrice;
            $payedUserId = $creatorId;
            $orderKey = isset($_POST['order_id']) ? $iN->iN_Secure($_POST['order_id']) : null;
         } else {
			$planData = $iN->GetPlanDetails($pointTypeID);
			$planAmount = isset($planData['amount']) ? $planData['amount'] : NULL;
			$planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : NULL;
         }
		 if($planAmount){
            $currency1 = $defaultCurrency;
			$currency2 = $coinPaymentCryptoCurrency;
			$coinPayMode = isset($coinPaymentMode) ? $coinPaymentMode : 'legacy';
            $ipnUrl = rtrim($base_url, '/') . '/cp_webhook.php';
            $cancelUrl = rtrim($base_url, '/') . '/purchase/purchase_point';
			try {
                if ($coinPayMode === 'v2') {
                    if (empty($coinPaymentClientId) || empty($coinPaymentClientSecret) || empty($coinPaymentWebhookSecret)) {
                        exit($LANG['check_coinpayment_settings']);
                    }
                    require_once('../includes/coinPayment/CoinpaymentsV2Client.php');
                    if (!$orderKey) {
                        try {
                            $orderKey = bin2hex(random_bytes(16));
                        } catch (Throwable $e) {
                            $orderKey = uniqid('cp_', true);
                        }
                    }
                    $client = new CoinpaymentsV2Client($coinPaymentClientId, $coinPaymentClientSecret, $coinPaymentWebhookSecret, $coinPaymentApiBase);
                    $payload = array(
                        'amount' => (float) $planAmount,
                        'currency' => $currency1,
                        'target_currency' => $currency2,
                        'order_id' => $orderKey,
                        'buyer_email' => $userEmail,
                        'success_url' => $cancelUrl,
                        'cancel_url' => $cancelUrl,
                        'webhook_url' => $ipnUrl,
	                        'metadata' => array(
	                            'payment_type' => $paymentTypeForCoin,
	                            'payed_user_id' => $payedUserId,
	                            'payed_post_id' => $payedPostId,
	                            'is_anonymous' => $isAnonymous,
	                            'agency_id' => $agencyId,
	                            'creator_id' => $creatorId,
	                            'duration_days' => $durationDays,
	                            'payed_profile_id' => $creatorId
	                        )
                    );
                    $payResponse = $client->createCheckout($payload);
                    $txnID = isset($payResponse['transaction_id']) ? $payResponse['transaction_id'] : $orderKey;
                    $redirectUrl = isset($payResponse['checkout_url']) ? $payResponse['checkout_url'] : '';
                    $status = $redirectUrl ? '200' : '404';
                    $time = time();
                    if ($txnID) {
	                        $finalPaymentType = $paymentTypeForCoin === 'tips' ? 'tips' : ($paymentTypeForCoin === 'campaign_donate' ? 'campaign_donate' : ($paymentTypeForCoin === 'agency_boost' ? 'agency_boost' : 'point'));
	                        if ($finalPaymentType === 'agency_boost') {
	                            DB::exec(
	                                "INSERT INTO i_user_payments(payer_iuid_fk,payed_iuid_fk,payed_profile_id_fk,agency_id_fk,order_key,payment_type,payment_option,payment_time,payment_status,agency_boost_duration_days,amount) VALUES (?,?,?,?,?,?,?,'pending',?,?)",
	                                [(int)$userID, $payedUserId, (int)$creatorId, (int)$agencyId, (string)$txnID, (string)$finalPaymentType, 'coinpayment', (int)$time, (int)$durationDays, (string) number_format((float) $planAmount, 2, '.', '')]
	                            );
	                        } else {
	                            DB::exec(
	                                "INSERT INTO i_user_payments(payer_iuid_fk,payed_iuid_fk,payed_post_id_fk,order_key,payment_type,payment_option,payment_time,payment_status,credit_plan_id,amount,is_anonymous) VALUES (?,?,?,?,?,?,?,'pending',?,?,?)",
	                                [(int)$userID, $payedUserId, $payedPostId, (string)$txnID, (string)$finalPaymentType, 'coinpayment', (int)$time, $finalPaymentType === 'point' ? (int)$pointTypeID : null, (string) number_format((float) $planAmount, 2, '.', ''), $finalPaymentType === 'campaign_donate' ? $isAnonymous : 0]
	                            );
	                        }
                    } else {
                        exit($LANG['check_coinpayment_settings']);
                    }
                } else {
                    require_once('../includes/coinPayment/vendor/autoload.php');
                    $cps_api = new CoinpaymentsAPI($coinPaymentPrivateKey, $coinPaymentPublicKey, 'json');
                    $information = $cps_api->GetBasicInfo();
                    $payBtc = $cps_api->CreateSimpleTransactionWithConversion($planAmount, $currency1, $currency2, $userEmail, $ipnUrl, $cancelUrl);
                    $txnID = isset($payBtc['result']['txn_id']) ? $payBtc['result']['txn_id'] : NULL;
                    $time = time();
                    if($txnID){
	                        $finalPaymentType = $paymentTypeForCoin === 'tips' ? 'tips' : ($paymentTypeForCoin === 'campaign_donate' ? 'campaign_donate' : ($paymentTypeForCoin === 'agency_boost' ? 'agency_boost' : 'point'));
	                        if ($finalPaymentType === 'agency_boost') {
	                            DB::exec("INSERT INTO i_user_payments(payer_iuid_fk,payed_iuid_fk,payed_profile_id_fk,agency_id_fk,order_key,payment_type,payment_option,payment_time,payment_status,agency_boost_duration_days,amount) VALUES (?,?,?,?,?,?,?,'pending',?,?)",
	                                [(int)$userID, $payedUserId, (int)$creatorId, (int)$agencyId, (string)$txnID, (string)$finalPaymentType, 'coinpayment', (int)$time, (int)$durationDays, (string) number_format((float) $planAmount, 2, '.', '')]
	                            );
	                        } else {
	                            DB::exec("INSERT INTO i_user_payments(payer_iuid_fk,payed_iuid_fk,payed_post_id_fk,order_key,payment_type,payment_option,payment_time,payment_status,credit_plan_id,amount,is_anonymous) VALUES (?,?,?,?,?,?,?,'pending',?,?,?)",
	                                [(int)$userID, $payedUserId, $payedPostId, (string)$txnID, (string)$finalPaymentType, 'coinpayment', (int)$time, $finalPaymentType === 'point' ? (int)$pointTypeID : null, (string) number_format((float) $planAmount, 2, '.', ''), $finalPaymentType === 'campaign_donate' ? $isAnonymous : 0]
	                            );
	                        }
	                    }else{
                        exit($LANG['check_coinpayment_settings']);
                    }

                    if ($information['error'] == 'ok') {
                        $redirectUrl = $payBtc['result']['checkout_url'];
                        $status = '200';
                    }else{
                        $redirectUrl = '';
                        $status = '404';
                    }
                }

			} catch (Exception $e) {
				echo str_replace('{error}', $e->getMessage(), $LANG['generic_error_prefixed']);
				exit();
			}
			$data = array(
				'status' => $status,
				'redirect' => $redirectUrl,
                'order_key' => isset($txnID) ? $txnID : $orderKey
			 );
			 $result = json_encode($data);
			 echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		 }
	  }
	}
	if ($type == 'subscribeMeAut') {
		// Manual card subscription flow is deprecated and must not be used.
		exit(iN_HelpSecure($LANG['noway_desc']));
		if (isset($_POST['u']) && isset($_POST['pl']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['card'])) {
			$iuID = $iN->iN_Secure($_POST['u']);
			$planID = $iN->iN_Secure($_POST['pl']);
			$subscriberName = $iN->iN_Secure($_POST['name']);
			$subscriberEmail = $iN->iN_Secure($_POST['email']);
			$creditCardNumber = $iN->iN_Secure($_POST['card']);
			$expMonth = $iN->iN_Secure($_POST['exm']);
			$expYear = $iN->iN_Secure($_POST['exy']);
			$CardCCV = $iN->iN_Secure($_POST['cccv']);
			$planDetails = $iN->iN_CheckPlanExist($planID, $iuID);
			$expiredData = $expYear.'-'.$expMonth;
			$payment_id = $statusMsg = $api_error = '';
			if ($planDetails) {
				$planType = $planDetails['plan_type'];
				$amount = $planDetails['amount'];
				$planCurrency = $autHorizePaymentCurrency;
				$adminEarning = ($adminFee * $amount) / 100;
				$userNetEarning = $amount - $adminEarning;
				$subscriptionCompleted = $LANG['subscription_description_authorize'];
				$payment_Type = 'authorizenet';
				$planIntervalCount = '1';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
					$intervalLength = '7';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+7 days'));
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
					$intervalLength = '30';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 month'));
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
					$intervalLength = '365';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 year'));
				}

define("AUTHORIZENET_LOG_FILE", "phplog");

function createSubscription($userID,$iuID,$payment_Type,$planID,$planCurrency, $planInterval,$planIntervalCount,$subscriberEmail,$autName, $autKey, $subscriberName,$userName,$intervalLength,$interval_dmy,$creditCardNumber,$expiredData,$amount,$plancreated,$current_period_start,$current_period_end,$adminEarning,$userNetEarning,$subscriptionCompleted)
{
	global $iN;
	/* Create a merchantAuthenticationType object with authentication details
	retrieved from the constants file */
	$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
	$merchantAuthentication->setName($autName);
	$merchantAuthentication->setTransactionKey($autKey);

	// Set the transaction's refId
	$refId = 'ref' . time();

	// Subscription Type Info
	$subscription = new AnetAPI\ARBSubscriptionType();
	$subscription->setName("Sample Subscription");

	$interval = new AnetAPI\PaymentScheduleType\IntervalAType();
	$interval->setLength($intervalLength);
	$interval->setUnit($interval_dmy);

	$paymentSchedule = new AnetAPI\PaymentScheduleType();
	$paymentSchedule->setInterval($interval);
	$paymentSchedule->setStartDate(new DateTime('now'));
	$paymentSchedule->setTotalOccurrences("12");
	$paymentSchedule->setTrialOccurrences("1");

	$subscription->setPaymentSchedule($paymentSchedule);
	$subscription->setAmount($amount);
	$subscription->setTrialAmount("0.00");

	$creditCard = new AnetAPI\CreditCardType();

	$creditCard->setCardNumber($creditCardNumber);
	$creditCard->setExpirationDate($expiredData);

	$payment = new AnetAPI\PaymentType();
	$payment->setCreditCard($creditCard);
	$subscription->setPayment($payment);

	$order = new AnetAPI\OrderType();
	$order->setInvoiceNumber("1234354");
	$order->setDescription($subscriptionCompleted);
	$subscription->setOrder($order);

	$billTo = new AnetAPI\NameAndAddressType();
	$billTo->setFirstName($subscriberName);
	$billTo->setLastName($userName);

	$subscription->setBillTo($billTo);

	$request = new AnetAPI\ARBCreateSubscriptionRequest();
	$request->setmerchantAuthentication($merchantAuthentication);
	$request->setRefId($refId);
	$request->setSubscription($subscription);
	$controller = new AnetController\ARBCreateSubscriptionController($request);

	$response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

	if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
	{
		$custID = $response->getSubscriptionId();
		$planStatus = 'active';
		$insertSubscription = $iN->iN_InsertUserSubscription($userID, $iuID, $payment_Type, $subscriberName, $custID, $custID, $planID, $amount, $adminEarning, $userNetEarning, $planCurrency, $planInterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus);

		 if ($insertSubscription) {
			echo '200';
		} else {
			echo iN_HelpSecure($LANG['contact_site_administrator']);
		}
	}
	else
	{
	echo iN_HelpSecure($LANG['error_invalid_response']) . "\n";
		$errorMessages = $response->getMessages()->getMessage();
	echo iN_HelpSecure($LANG['response_prefix']) . $errorMessages[0]->getText() . "\n";
	}

	return $response;
}

if(!defined('DONT_RUN_SAMPLES'))
	createSubscription($userID,$iuID,$payment_Type,$planID,$planCurrency, $planInterval,$planIntervalCount,$subscriberEmail,$autName, $autKey,$subscriberName,$userName,$intervalLength,$interval_dmy,$creditCardNumber,$expiredData,$amount,$plancreated,$current_period_start,$current_period_end,$adminEarning,$userNetEarning,$subscriptionCompleted);
    }
 }
}
	/*Send Tip*/
	if($type == 'p_sendGift'){
		if ((isset($liveGiftStatus) ? (string)$liveGiftStatus : '1') !== '1') {
			$data = array(
				'status' => 'disabled',
				'message' => isset($LANG['live_feature_disabled']) ? $LANG['live_feature_disabled'] : ''
			);
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			exit();
		}
		if(isset($_POST['tip_u']) && isset($_POST['tipTyp']) && isset($_POST['lid'])){
	   $giftLiveOwnerUserID = $iN->iN_Secure($_POST['tip_u']);
	   $giftTypeID = $iN->iN_Secure($_POST['tipTyp']);
	   $cLiveID = $iN->iN_Secure($_POST['lid']);
	   if($iN->CheckLivePlanExist($giftTypeID) == '1' && $iN->iN_CheckLiveIDExist($cLiveID) == '1'){
	   $getLiveGiftDataFromID = $iN->GetLivePlanDetails($giftTypeID);
	   $liveWantedCoin = isset($getLiveGiftDataFromID['gift_point']) ? $getLiveGiftDataFromID['gift_point'] : NULL;
	   $liveWantedMoney = isset($getLiveGiftDataFromID['gift_money_equal']) ? $getLiveGiftDataFromID['gift_money_equal'] : NULL;
	   $liveAnimationImage = isset($getLiveGiftDataFromID['gift_money_animation_image']) ? $getLiveGiftDataFromID['gift_money_animation_image'] : NULL;
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   $liveGiftAnimationUrl = '';
		if ($userCurrentPoints >= $liveWantedCoin && $userID != $giftLiveOwnerUserID) {
			$translatePointToMoney = $liveWantedMoney;
			$adminEarning = $translatePointToMoney * ($adminFee / 100);
			$userEarning = $translatePointToMoney - $adminEarning;
			$liveGiftAnimation = $base_url.$liveAnimationImage;
			$liveGiftAnimationUrl = '<div class="live_animation_wrapper"><div class="live_an_img"><img src="'.$liveGiftAnimation.'"></div></div>';
			$UpdateUsersWallet = $iN->iN_UpdateUsersWalletsForLiveGift($userID, $cLiveID, $giftLiveOwnerUserID, $giftTypeID, $liveWantedCoin, $adminFee, $liveWantedMoney);
			$liveOwnUserData = $iN->iN_GetUserDetails($userID);
		    $userCurrentPoints = isset($liveOwnUserData['wallet_points']) ? $liveOwnUserData['wallet_points'] : '0';
			if($UpdateUsersWallet){
				$status = 'ok';
			}else{
				$status = '404';
			}
		}else{
			$status = '';
			$emountnot = 'notEnouhCredit';
			$redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		}
	   $data = array(
		  'status' => $status,
		  'redirect' => $redirect,
		  'enamount' => $emountnot,
		  'giftAnimation' => $liveGiftAnimationUrl,
		  'current_balance' => number_format($userCurrentPoints)
	   );
	   $result = json_encode($data);
	   echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
	   if($status == 'ok'){
           $userDeviceKey = $iN->iN_GetuserDetails($giftLiveOwnerUserID);
		   $toUserName = $userDeviceKey['i_username'];
		   $oneSignalUserDeviceKey = $userDeviceKey['device_key'];
		   $msgBody = $iN->iN_Secure($LANG['send_you_a_gift']);
		   $msgTitle = $iN->iN_Secure($LANG['your_gift_is']).$currencys[$defaultCurrency]. $userEarning;
		   $URL = $base_url.'live'.$toUserName;
		   if($oneSignalUserDeviceKey){
			 $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		   }
	   }
	}
   }
  }
  if($type == 'sndAgCon'){
     /*SEND CONFIRMATIN EMAIL STARTED*/
	 $code = md5(rand(1111, 9999) . time());

		if ($emailSendStatus == '1') {
			$insertNewCode = $iN->iN_InsertNewVerificationCode($iN->iN_Secure($userID), $iN->iN_Secure($code));
			if ($insertNewCode)
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
				$theCode = $base_url.'verify?v='.$code;
				include_once '../includes/mailTemplates/verificationTemplate.php';
				$body = $bodyVerifyEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($userEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['confirm_email']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if (iN_safeMailSend($mail, $smtpOrMail, 'email_confirmation')) {
					$mail->ClearAddresses();
					echo '8';
					return true;
				}
				echo '9';
				return false;

			}
		}
	 /*SEND CONFIRMATION EMAIL FINISHED*/
  /*Insert OneSignal Device Key*/
  if($type == 'device_key'){
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$userDeviceOneSignalKey = $iN->iN_Secure($_GET['id']);
		$InsertOneSignalDeviceKey = $iN->iN_OneSignalDeviceKey($userID, $userDeviceOneSignalKey);
		if($InsertOneSignalDeviceKey){
		   echo '1';
		}else{
		   echo '2';
		}
	}
  }
  /*Remove OneSignal Device key*/
  if($type == 'remove_device_key'){
	$InsertOneSignalDeviceKey = $iN->iN_OneSignalDeviceKeyRemove($userID);
  }
  /*Generate a QR Code*/
  if($type == 'generateQRCode'){
    include("../includes/qr.php");
  }
  // Get Mention Users
	if ($type == 'mfriends') {
		if (isset($_POST['menFriend'])) {
			$searchmUser = $iN->iN_Secure($_POST['menFriend']);
			$GetResultMentionedUser = $iN->iN_SearchMention($userID, $searchmUser);
			if ($GetResultMentionedUser) {
				foreach ($GetResultMentionedUser as $um) {
					 $mentionResultUserID = $um['iuid'];
                     $mentionResultUserUsername = $um['i_username'];
					 $mentionResultUserUserFullName = $um['i_user_fullname'];
					 $mentionResultUserAvatar = $iN->iN_UserAvatar($mentionResultUserID, $base_url);
					echo '
					<div class="i_message_wrapper transition mres_u" data-user="'.$mentionResultUserUsername.'">
						<div class="i_message_owner_avatar">
							<div class="i_message_avatar"><img src="'.$mentionResultUserAvatar.'" alt="newuserhere"></div>
						</div>
						<div class="i_message_info_container">
							<div class="i_message_owner_name">'.$mentionResultUserUserFullName.'</div>
							<div class="i_message_i">@'.$mentionResultUserUsername.'</div>
						</div>
					</div>
					 ';
				}
			}
		}
	}
if ($type == 'stories') {
    // Unified stories uploader: mirror 'upload' flow, avoid provider-specific putObject/SpacesConnect
    if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!empty($_FILES['storieimg']['name'])) {
            foreach ($_FILES['storieimg']['name'] as $iname => $value) {
                $name = stripslashes($_FILES['storieimg']['name'][$iname]);
                $size = $_FILES['storieimg']['size'][$iname];
                $ext = strtolower(getExtension($name));
                $valid_formats = explode(',', $availableFileExtensions);
                if (!in_array($ext, $valid_formats)) { echo iN_HelpSecure($LANG['invalid_file_format']); continue; }
                // Safer numeric comparison (convert_to_mb returns formatted string)
                if ((float)convert_to_mb($size) >= (float)$availableUploadFileSize) { echo iN_HelpSecure($size); continue; }

                $microtime = microtime();
                $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
                $UploadedFileName = 'image_' . $removeMicrotime . '_' . $userID;
                $getFilename = $UploadedFileName . '.' . $ext;
                $tmp = $_FILES['storieimg']['tmp_name'][$iname];
                $err = isset($_FILES['storieimg']['error'][$iname]) ? (int)$_FILES['storieimg']['error'][$iname] : UPLOAD_ERR_OK;
                if ($err !== UPLOAD_ERR_OK) { echo iN_HelpSecure($LANG['upload_failed']); continue; }
                $mimeType = $_FILES['storieimg']['type'][$iname];
                $d = date('Y-m-d');

                // Determine type (stories allow image or video)
                if (preg_match('/video\/*/', $mimeType) || $mimeType === 'application/octet-stream') { $fileTypeIs = 'video'; }
                else if (preg_match('/image\/*/', $mimeType)) { $fileTypeIs = 'Image'; }
                else { echo iN_HelpSecure($LANG['invalid_file_format']); continue; }

                // Ensure project-local uploads directories (align with uploadReel and storage helpers)
                $projUploadsRoot = dirname(__DIR__) . '/uploads';
                $projFilesDir   = $projUploadsRoot . '/files/' . $d;
                $projXImgsDir   = $projUploadsRoot . '/pixel/' . $d;
                $projXVideosDir = $projUploadsRoot . '/xvideos/' . $d;
                if (!is_dir($projFilesDir)) { @mkdir($projFilesDir, 0755, true); }
                if (!is_dir($projXImgsDir)) { @mkdir($projXImgsDir, 0755, true); }
                if (!is_dir($projXVideosDir)) { @mkdir($projXVideosDir, 0755, true); }

                if ($fileTypeIs === 'video' && !$ffmpegCanConvert && !in_array($ext, $nonFfmpegAvailableVideoFormat)) { exit('303'); }
                if (!move_uploaded_file($tmp, $projFilesDir . '/' . $getFilename)) { echo $LANG['upload_failed']; continue; }

                $pathFile = '';
                $pathXFile = '';
                $tumbnailPath = '';
                $UploadSourceUrl = '';

                if ($fileTypeIs === 'video') {
                    if ($ffmpegCanConvert) {
                        require_once '../includes/convertToMp4Format.php';
                        require_once '../includes/createVideoThumbnail.php';

                        // Resolve ffmpeg binary path if not configured
                        $ffmpegBin = !empty($ffmpegPath) ? trim((string)$ffmpegPath) : '';
                        if ($ffmpegBin === '' && function_exists('shell_exec')) {
                            $ffmpegBin = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null || which ffmpeg 2>/dev/null'));
                        }
                        if ($ffmpegBin === '') { $ffmpegBin = 'ffmpeg'; }
                        rq_debug('stories:ffmpeg_bin', ['bin' => $ffmpegBin]);

                        $sourceFs = $projFilesDir . '/' . $getFilename;
                        rq_debug('stories:move_done', ['src' => $tmp, 'dst' => $sourceFs, 'exists' => file_exists($sourceFs), 'size' => @filesize($sourceFs)]);
                        $convertedFs = convertToMp4Format($ffmpegBin, $sourceFs, $projFilesDir, $UploadedFileName);
                        // If conversion failed, check if original was already MP4; otherwise abort to mirror 'uploadReel'
                        if (!$convertedFs || !file_exists($convertedFs)) {
                            $srcExt = strtolower(pathinfo($sourceFs, PATHINFO_EXTENSION));
                            if ($srcExt === 'mp4') {
                                $convertedFs = $sourceFs;
                            } else {
                                rq_debug('stories:convert_failed', ['source' => $sourceFs]);
                                echo iN_HelpSecure($LANG['mp4_conversion_failed'] ?? 'mp4_conversion_failed');
                                // Skip this file but continue with other uploads
                                continue;
                            }
                        }
                        rq_debug('stories:convert_ok', ['out' => $convertedFs, 'exists' => file_exists($convertedFs), 'size' => @filesize($convertedFs)]);

                        // 4-second preview and poster
                        if (!file_exists($projXVideosDir)) { @mkdir($projXVideosDir, 0755, true); }
                        $xVideoFirstPath = $projXVideosDir . '/' . $UploadedFileName . '.mp4';
                        $videoTumbnailFs = createVideoThumbnailInSameDir($ffmpegBin, $convertedFs);
                        rq_debug('stories:thumb', ['thumb' => $videoTumbnailFs, 'exists' => $videoTumbnailFs && file_exists($videoTumbnailFs)]);
	                        $ffmpegExec = escapeshellcmd($ffmpegBin);
	                        $safeClip = $ffmpegExec . ' -hide_banner -loglevel error -y'
	                            . ' -ss 00:00:01 -i ' . escapeshellarg($convertedFs)
	                            . ' -c copy -movflags +faststart -avoid_negative_ts make_zero'
	                            . ' -t 00:00:04 ' . escapeshellarg($xVideoFirstPath) . ' 2>&1';
                        $clipOut = shell_exec($safeClip);
                        rq_debug('stories:clip', ['cmd' => $safeClip, 'xclip' => $xVideoFirstPath, 'exists' => file_exists($xVideoFirstPath)]);

                        // Determine actual file path and extension from the resulting file
                        $convertedBaseName = basename($convertedFs);
                        $pathFile = 'uploads/files/' . $d . '/' . $convertedBaseName;
                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                        $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                        $thePathM = dirname(__DIR__) . '/' . $tumbnailPath;
                        if (file_exists($thePathM) && ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes')) {
                            watermark_apply_batch(
                                [$thePathM],
                                $siteWatermarkLogo,
                                $LinkWatermarkStatus,
                                watermark_label($base_url, $userName)
                            );
                        }

                        // Publish keys using returned mapping instead of is_file (works with remote cleanup)
                        $publishKeys = [];
                        $mp4Key = $pathFile;
                        $xclipKey = $pathXFile;
                        $thumbJpg = $tumbnailPath;
                        if (is_file(dirname(__DIR__) . '/' . $mp4Key)) { $publishKeys[] = $mp4Key; }
                        if (is_file(dirname(__DIR__) . '/' . $xclipKey)) { $publishKeys[] = $xclipKey; }
                        if (is_file(dirname(__DIR__) . '/' . $thumbJpg)) { $publishKeys[] = $thumbJpg; }
                        $published = $publishKeys ? storage_publish_many($publishKeys, true, true) : [];
                        rq_debug('stories:publish', ['keys' => $publishKeys, 'map' => $published]);
                        $UploadSourceUrl = $published[ltrim($thumbJpg, '/')] ?? ($published[ltrim($mp4Key, '/')] ?? ($base_url . 'uploads/web.png'));
                        if ($UploadSourceUrl === $base_url . 'uploads/web.png') { $tumbnailPath = 'uploads/web.png'; }
                        // Force extension to mp4 when conversion/original is mp4
                        $ext = 'mp4';
                    } else {
                        // No ffmpeg: treat as-is
                        $pathFile = 'uploads/files/' . $d . '/' . $getFilename;
                        $pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
                        $pub = storage_publish_many([$pathFile], true, true);
                        $UploadSourceUrl = $pub[ltrim($pathFile, '/')] ?? storage_public_url($pathFile);
                        $ext = strtolower(pathinfo($pathFile, PATHINFO_EXTENSION));
                    }
                } else if ($fileTypeIs === 'Image') {
                    // Use project-local paths constructed above
                    $pathFile = 'uploads/files/' . $d . '/' . $getFilename;
                    $pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                    $tumbnailPath = $pathFile;
                    // Optional watermark on image
                    $thePathM = dirname(__DIR__) . '/' . $pathFile;
                    if ($ext !== 'gif' && ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes')) {
                        watermark_apply_batch(
                            [$thePathM, dirname(__DIR__) . '/' . $tumbnailPath],
                            $siteWatermarkLogo,
                            $LinkWatermarkStatus,
                            watermark_label($base_url, $userName)
                        );
                    }
                    // Pixelated copy
                    try {
                        $dirFs = dirname(__DIR__) . '/' . $pathXFile;
                        if (!file_exists(dirname($dirFs))) { @mkdir(dirname($dirFs), 0755, true); }
                        $image = new ImageFilter();
                        $image->load(dirname(__DIR__) . '/' . $pathFile)->pixelation($pixelSize)->saveFile($dirFs, 100, 'jpg');
                    } catch (Exception $e) { echo '<span class="request_warning">' . $e->getMessage() . '</span>'; }

                        $pub = storage_publish_many([$pathFile, $pathXFile], true, true);
                        $UploadSourceUrl = $pub[ltrim($tumbnailPath, '/')] ?? storage_public_url($tumbnailPath);
                } else {
                    echo iN_HelpSecure($LANG['invalid_file_format']);
                    continue;
                }

                // Persist and render the story item
                $insertFileFromUploadTable = $iN->iN_insertUploadedSotieFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
                $getUploadedFileID = $iN->iN_GetUploadedStoriesFilesIDs($userID, $pathFile);
                $isStoryCreator = $iN->iN_CheckUserIsCreator($userID) == 1;
                $quickDefaults = [
                    iN_HelpSecure($LANG['story_quick_reply_default_1'] ?? 'Nice!'),
                    iN_HelpSecure($LANG['story_quick_reply_default_2'] ?? 'Love this!'),
                    iN_HelpSecure($LANG['story_quick_reply_default_3'] ?? 'Tell me more')
                ];
                $quickRepliesHtml = '';
                if ($isStoryCreator) {
                    $quickRepliesHtml = '<div class="story_option_group story_quick_replies_toggle">'
                        . '<div class="story_option_label">' . iN_HelpSecure($LANG['story_quick_replies_label']) . '</div>'
                        . '<button type="button" class="story_quick_replies_trigger" aria-expanded="false"'
                        . ' data-add-label="' . iN_HelpSecure($LANG['story_quick_replies_add']) . '"'
                        . ' data-remove-label="' . iN_HelpSecure($LANG['story_quick_replies_hide']) . '">'
                        . iN_HelpSecure($LANG['story_quick_replies_add'])
                        . '</button>'
                        . '</div>'
                        . '<div class="story_option_group story_quick_replies_block is-hidden" data-enabled="0">'
                        . '<div class="story_option_label">' . iN_HelpSecure($LANG['story_quick_replies_label']) . '</div>'
                        . '<div class="story_quick_reply_inputs">'
                        . '<input type="text" class="story_quick_reply_input" maxlength="120" value="' . $quickDefaults[0] . '">'
                        . '<input type="text" class="story_quick_reply_input" maxlength="120" value="' . $quickDefaults[1] . '">'
                        . '<input type="text" class="story_quick_reply_input" maxlength="120" value="' . $quickDefaults[2] . '">'
                        . '<input type="text" class="story_quick_reply_input" maxlength="120" value="">'
                        . '<input type="text" class="story_quick_reply_input" maxlength="120" value="">'
                        . '</div>'
                        . '<div class="rec_not box_not_padding_top">' . iN_HelpSecure($LANG['story_quick_replies_note']) . '</div>'
                        . '</div>';
                }
                $storyAudioHtml = '';
                if ($fileTypeIs === 'Image') {
                    $storyAudioHtml = '<div class="story_option_group story_audio_block">'
                        . '<div class="story_option_label">' . iN_HelpSecure($LANG['story_audio_label']) . '</div>'
                        . '<div class="story_audio_field">'
                        . '<input type="hidden" class="story_overlay_audio" value="">'
                        . '<div class="story_audio_preview">'
                        . '<span class="story_audio_selected"></span>'
                        . '<button type="button" class="story_audio_clear" title="' . iN_HelpSecure($LANG['delete']) . '" aria-label="' . iN_HelpSecure($LANG['delete']) . '">'
                        . html_entity_decode($iN->iN_SelectedMenuIcon('5'))
                        . '</button>'
                        . '</div>'
                        . '<button type="button" class="story_audio_trigger" title="' . iN_HelpSecure($LANG['story_audio_choose']) . '" aria-label="' . iN_HelpSecure($LANG['story_audio_choose']) . '">'
                        . iN_HelpSecure($LANG['story_audio_choose'])
                        . '</button>'
                        . '</div>'
                        . '<div class="story_audio_list"></div>'
                        . '</div>';
                }
                $storyOptionsHtml = '<div class="story_options">'
                    . '<div class="story_option_group">'
                    . '<div class="story_option_label">' . iN_HelpSecure($LANG['story_privacy_label']) . '</div>'
                    . '<select class="story_privacy">'
                    . '<option value="followers">' . iN_HelpSecure($LANG['story_privacy_followers']) . '</option>'
                    . '<option value="subscribers">' . iN_HelpSecure($LANG['story_privacy_subscribers']) . '</option>'
                    . '<option value="everyone">' . iN_HelpSecure($LANG['story_privacy_everyone']) . '</option>'
                    . '</select>'
                    . '</div>'
                    . '<div class="story_option_group">'
                    . '<div class="story_option_label">' . iN_HelpSecure($LANG['story_overlay_label']) . '</div>'
                    . '<div class="story_overlay_fields">'
                    . '<input type="text" class="story_overlay_link" placeholder="' . iN_HelpSecure($LANG['story_overlay_link_placeholder']) . '">'
                    . '<input type="text" class="story_overlay_mention" placeholder="' . iN_HelpSecure($LANG['story_overlay_mention_placeholder']) . '">'
                    . '<div class="story_overlay_sticker_field">'
                    . '<input type="hidden" class="story_overlay_sticker" value="">'
                    . '<div class="story_sticker_preview">'
                    . '<img class="story_sticker_img" src="" alt="">'
                    . '<button type="button" class="story_sticker_clear" title="' . iN_HelpSecure($LANG['delete']) . '" aria-label="' . iN_HelpSecure($LANG['delete']) . '">'
                    . html_entity_decode($iN->iN_SelectedMenuIcon('5'))
                    . '</button>'
                    . '</div>'
                    . '<button type="button" class="story_sticker_trigger" title="' . iN_HelpSecure($LANG['chs_sticker_send']) . '" aria-label="' . iN_HelpSecure($LANG['chs_sticker_send']) . '">'
                    . html_entity_decode($iN->iN_SelectedMenuIcon('24'))
                    . '</button>'
                    . '</div>'
                    . '</div>'
                    . '<div class="story_sticker_list"></div>'
                    . '</div>'
                    . $storyAudioHtml
                    . $quickRepliesHtml
                    . '</div>';
                if ($fileTypeIs == 'Image') {
                    echo '<!--Storie--><div class="uploaded_storie_container nonePoint body_' . $getUploadedFileID['s_id'] . '"><div class="dmyStory" id="' . $getUploadedFileID['s_id'] . '"><div class="i_h_in flex_ ownTooltip" data-label="' . iN_HelpSecure($LANG['delete']) . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('28')) . '</div></div><div class="uploaded_storie_image border_one tabing flex_"><img src="' . $UploadSourceUrl . '" id="img' . $getUploadedFileID['s_id'] . '"></div><div class="add_a_text"><textarea class="add_my_text st_txt_' . $getUploadedFileID['s_id'] . '" placeholder="Do you want to write something about this storie?"></textarea></div>' . $storyOptionsHtml . '<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="' . $getUploadedFileID['s_id'] . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('26')) . '<div class="pbtn">' . iN_HelpSecure($LANG['publish']) . '</div></div></div><!--/Storie--><script type="text/javascript">(function($){"use strict";setTimeout(()=>{var img=document.getElementById("img' . $getUploadedFileID['s_id'] . '"); if(img && img.height>img.width){$("#img' . $getUploadedFileID['s_id'] . '").css("height","100%");} else {$("#img' . $getUploadedFileID['s_id'] . '").css("width","100%");} $(".uploaded_storie_container").show();},2000);})(jQuery);</script>';
                } else if ($fileTypeIs == 'video') {
                    echo '<!--Storie--><div class="uploaded_storie_container body_' . $getUploadedFileID['s_id'] . '"><div class="dmyStory" id="' . $getUploadedFileID['s_id'] . '"><div class="i_h_in flex_ ownTooltip" data-label="' . iN_HelpSecure($LANG['delete']) . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('28')) . '</div></div><div class="uploaded_storie_image border_one tabing flex_"><video class="lg-video-object" id="v' . $getUploadedFileID['s_id'] . '" controls preload="none" poster="' . $UploadSourceUrl . '"><source src="' . storage_public_url($getUploadedFileID['uploaded_file_path']) . '" preload="metadata" type="video/mp4">Your browser does not support HTML5 video.</video></div><div class="add_a_text"><textarea class="add_my_text st_txt_' . $getUploadedFileID['s_id'] . '" placeholder="Do you want to write something about this storie?"></textarea></div>' . $storyOptionsHtml . '<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="' . $getUploadedFileID['s_id'] . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('26')) . '<div class="pbtn">' . iN_HelpSecure($LANG['publish']) . '</div></div></div><!--/Storie-->';
                } else { echo iN_HelpSecure($LANG['invalid_file_format']); }
            }
            exit; // prevent falling into any legacy stories logic
        }
    }
}

    /*Delete Storie Alert*/
	if($type == 'delete_storie_alert'){
       if(isset($_POST['id']) && $_POST['id'] != ''){
		   $postID = $iN->iN_Secure($_POST['id']);
		   $alertType = $type;
		   $checkStorieIDExist = $iN->iN_CheckStorieIDExist($userID, $postID);
		   if($checkStorieIDExist){
			 include "../themes/$currentTheme/layouts/popup_alerts/deleteStoryAlert.php";
		   }
	   }
	}
    if ($type === 'delete_reel_alert') {
        if (isset($_POST['id']) && $_POST['id'] !== '') {
            $postID = $iN->iN_Secure($_POST['id']);
            $reelData = $iN->iN_GetReelsVideoDetailsByID($userID, $postID);
            if ($reelData) {
                include "../themes/$currentTheme/layouts/popup_alerts/deleteReelAlert.php";
            }
        }
    }
	/*Storie Seen*/
	if($type == 'storieSeen'){
		include_once __DIR__ . '/../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit();
		}
		if(isset($_POST['id']) && $_POST['id'] != ''){
			$storieID = (int)$iN->iN_Secure($_POST['id']);
			$checkStorieID = $iN->iN_CheckStorieIDExistJustID($userID, $storieID);
			if($checkStorieID){
				$storyData = $iN->iN_GetUploadedStoriesDataS($storieID);
				if ($storyData && $iN->iN_CanViewStory($userID, $storyData)) {
					$iN->iN_InsertStorieSeen($userID, $storieID);
				}
			}
		}
	}
	/*Story Reaction*/
	if ($type == 'story_reaction') {
		include_once __DIR__ . '/../includes/csrf.php';
		header('Content-Type: application/json; charset=utf-8');
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit();
		}
		if (!isset($logedIn) || (int)$logedIn !== 1 || !isset($userID) || (int)$userID <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['you_should_login_first'] ?? 'login_required']);
			exit();
		}
		if (!isset($_POST['story_id'], $_POST['reaction'])) {
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'error']);
			exit();
		}
		$storyId = (int)$iN->iN_Secure($_POST['story_id']);
		$reaction = (string)$_POST['reaction'];
		$storyData = $iN->iN_GetUploadedStoriesDataS($storyId);
		if (!$storyData) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_expired'] ?? 'This story is no longer available.']);
			exit();
		}
		$storyOwnerId = (int)($storyData['uid_fk'] ?? 0);
		if ($storyOwnerId <= 0) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_expired'] ?? 'This story is no longer available.']);
			exit();
		}
		if ($storyOwnerId === (int)$userID) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_self'] ?? 'You cannot reply to your own story.']);
			exit();
		}
		$createdAt = (int)($storyData['created'] ?? 0);
		if ($createdAt <= 0 || (time() - $createdAt) > 86400) {
			echo json_encode(['status' => 'error', 'message' => $LANG['story_reply_expired'] ?? 'This story is no longer available.']);
			exit();
		}
		$accessInfo = $iN->iN_GetStoryAccessInfo($userID, $storyData);
		if (empty($accessInfo['allowed'])) {
			$reason = (string)($accessInfo['reason'] ?? '');
			$accessMessage = $LANG['story_access_followers'] ?? 'Only followers can view this story';
			if ($reason === 'subscribers') {
				$accessMessage = $LANG['story_access_subscribers'] ?? 'Only subscribers can view this story';
			}
			echo json_encode(['status' => 'error', 'message' => $accessMessage]);
			exit();
		}
		$allowedReactions = $iN->iN_GetStoryDefaultReactions();
		$result = $iN->iN_SaveStoryReaction($storyId, $userID, $reaction, $allowedReactions);
		if (($result['status'] ?? '') === 'added') {
			$iN->iN_InsertNotificationForStoryReaction($userID, $storyId, $storyOwnerId, $reaction);
		}
		if (($result['status'] ?? '') === 'error' || ($result['status'] ?? '') === 'invalid') {
			echo json_encode(['status' => 'error', 'message' => $LANG['something_wrong'] ?? 'error']);
			exit();
		}
		echo json_encode([
			'status' => 'success',
			'action' => $result['status'] ?? 'updated',
			'reaction' => $result['reaction'] ?? ''
		]);
		exit();
	}
	/*Show StorieViewers*/
	if($type == 'storieViewers'){
		if(isset($_POST['id']) && $_POST['id'] != ''){
			$storieID = (int)$iN->iN_Secure($_POST['id']);
			$checkStorieID = $iN->iN_CheckStorieIDExist($userID, $storieID);
			if($checkStorieID){
				$swData = $iN->iN_GetUploadedStoriesSeenData($userID,$storieID);
				$storySeenCount = $iN->iN_GetStorySeenCount($userID, $storieID);
				$storyReplyCount = $iN->iN_GetStoryReplyCount($storieID);
				$storyReactionSummary = $iN->iN_GetStoryReactionSummary($storieID);
				$storyReactionTotal = $iN->iN_GetStoryReactionCount($storieID);
				include "../themes/$currentTheme/layouts/popup_alerts/storieViewers.php";
			}
		}

	}
	if ($type == 'pr_upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			if (
				!isset($_FILES['uploading']['name']) ||
				!is_array($_FILES['uploading']['name']) ||
				count(array_filter((array) $_FILES['uploading']['name'])) === 0
			) {
				exit; // Nothing to upload; silently bail to avoid warnings when user cancels.
			}
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				if (empty($_FILES['uploading']['name'][$iname])) {
					continue;
				}
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = rtrim(UPLOAD_DIR_VIDEOS, '/') . '/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && !$ffmpegCanConvert && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						$uploadTumbnail = '';
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								if ($ffmpegCanConvert) {
									require_once '../includes/convertToMp4Format.php';
									require_once '../includes/createVideoThumbnail.php';
									$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';

									// Use safe local file path for input (not URL)
									$localSourcePath = '../uploads/files/' . $d . '/' . $getFilename;
									$ffmpegExec = escapeshellcmd($ffmpegPath ?: 'ffmpeg');

									// Convert to MP4
									$convertedFs = convertToMp4Format($ffmpegPath, $localSourcePath, '../uploads/files/' . $d, $UploadedFileName);
									if (!$convertedFs || !file_exists($convertedFs)) {
										$convertedFs = $localSourcePath;
									}

									// Generate thumbnail using the helper
									$thumbFs = createVideoThumbnailInSameDir($ffmpegPath, $convertedFs);

									// Generate 4-second preview clip
									$safeCmd = $ffmpegExec . ' -hide_banner -loglevel error -y'
										. ' -ss 00:00:01 -i ' . escapeshellarg($convertedFs)
										. ' -c copy -movflags +faststart -avoid_negative_ts make_zero'
										. ' -t 00:00:04 ' . escapeshellarg($xVideoFirstPath) . ' 2>&1';
									@shell_exec($safeCmd);

									// Generate watermarked/drawtext version
									$up_url = watermark_label($base_url, $userName);
									if($drawTextStatus == '1'){
										$cmdText = @shell_exec($ffmpegExec . ' -hide_banner -loglevel error -y'
											. ' -i ' . escapeshellarg($convertedFs)
											. ' -vf ' . escapeshellarg('drawtext=fontfile=../src/droidsanschinese.ttf:text=' . $up_url . ':fontcolor=red:fontsize=18:x=10:y=H-th-10')
											. ' ' . escapeshellarg($textVideoPath) . ' 2>&1');
									}else{
										$cmdText = @shell_exec($ffmpegExec . ' -hide_banner -loglevel error -y'
											. ' -i ' . escapeshellarg($convertedFs)
											. ' -c:a copy -c:v libx264 -preset superfast -profile:v baseline'
											. ' ' . escapeshellarg($textVideoPath) . ' 2>&1');
									}
									if ($cmdText) {
										$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									}
									$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.jpg';
									if (file_exists($thePath)) {
										try {
											$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
											$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
											$image = new ImageFilter();
											$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");

										} catch (Exception $e) {
											echo '<span class="request_warning">' . $e->getMessage() . '</span>';
										}
									}else{
										exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
									}
								} else {
									$cmd = '';
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
								}
									if ($ffmpegCanConvert) {
	    								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
	    								$thePathM = '../' . $tumbnailPath;
										if ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes') {
											watermark_apply_batch(
												[$thePathM],
												$siteWatermarkLogo,
												$LinkWatermarkStatus,
												watermark_label($base_url, $userName)
											);
										}
									}
								// Unified object storage publish for video assets
								{
									$publishKeys = [];
									$mp4Key = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$xclipKey = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$thumbJpg = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$thumbPng = 'uploads/files/' . $d . '/' . $UploadedFileName . '.png';
									if (is_file('../' . $mp4Key)) { $publishKeys[] = $mp4Key; }
									if (is_file('../' . $xclipKey)) { $publishKeys[] = $xclipKey; }
									if (is_file('../' . $thumbJpg)) { $publishKeys[] = $thumbJpg; }
									if (is_file('../' . $thumbPng)) { $publishKeys[] = $thumbPng; }
									if (!empty($publishKeys)) { storage_publish_many($publishKeys, true, true); }
									if (is_file('../' . $thumbJpg)) {
										$tumbnailPath = $thumbJpg;
										$UploadSourceUrl = storage_public_url($thumbJpg);
									} elseif (is_file('../' . $thumbPng)) {
										$tumbnailPath = $thumbPng;
										$UploadSourceUrl = storage_public_url($thumbPng);
									} elseif (is_file('../' . $mp4Key)) {
										$UploadSourceUrl = storage_public_url($mp4Key);
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										$tumbnailPath = 'uploads/web.png';
									}
								}
								/*CHECK AMAZON S3 AVAILABLE (disabled by unified storage)*/
								if (false && $s3Status == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegCanConvert) {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>" . $LANG['s3_public_access_warning'] . "</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>" . $LANG['s3_public_access_warning'] . "</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        $uploads = [
                                            ['path' => '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/files/'],
                                        ];

                                        foreach ($uploads as $upload) {
                                            $key = basename($upload['path']);
                                            try {
                                                $result = $s3->putObject([
                                                    'Bucket' => $s3Bucket,
                                                    'Key' => $upload['target'] . $d . '/' . $key,
                                                    'Body' => fopen($upload['path'], 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                                ]);
                                                $UploadSourceUrl = $result->get('ObjectURL');
                                                @unlink($upload['path']);
                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $msg = $e->getAwsErrorMessage();
                                                echo $LANG['error_uploading_file'] . '<br>';
                                                if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                    echo "<div class='request_warning'>" . $LANG['s3_public_access_warning'] . "</div>";
                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }

								} else if (false && $WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegCanConvert) {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo $LANG['error_uploading_file'] . '<br>';
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>" . $LANG['wasabi_public_access_warning'] . "</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo $LANG['error_uploading_file'] . '<br>';
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>" . $LANG['wasabi_public_access_warning'] . "</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        $uploads = [
                                            ['path' => '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/files/'],
                                        ];

                                        foreach ($uploads as $upload) {
                                            $key = basename($upload['path']);
                                            try {
                                                $result = $s3->putObject([
                                                    'Bucket' => $WasBucket,
                                                    'Key' => $upload['target'] . $d . '/' . $key,
                                                    'Body' => fopen($upload['path'], 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                                ]);
                                                $UploadSourceUrl = $result->get('ObjectURL');
                                                @unlink($upload['path']);
                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $msg = $e->getAwsErrorMessage();
                                                echo "There was an error uploading the file: $msg<br>";
                                                if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                    echo "<div class='request_warning'>" . $LANG['wasabi_public_access_warning'] . "</div>";
                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }

                                        // Remove local temporary files
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($uploadFile . $d . '/' . $getFilename);
                            				@unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
                            			}
                            			// Unified publish keys
                            			$publishKeys = [];
                            			$mp4Key = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                            			$xclipKey = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                            			$thumbJpg = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                            			if (is_file('../' . $mp4Key)) { $publishKeys[] = $mp4Key; }
                            			if (is_file('../' . $xclipKey)) { $publishKeys[] = $xclipKey; }
                            			if (is_file('../' . $thumbJpg)) { $publishKeys[] = $thumbJpg; }
                            			if ($publishKeys) { storage_publish_many($publishKeys, true, true); }
                            			if (is_file('../' . $thumbJpg)) { $UploadSourceUrl = storage_public_url($thumbJpg); }
                            			elseif (is_file('../' . $mp4Key)) { $UploadSourceUrl = storage_public_url($mp4Key); }
                            			else { $UploadSourceUrl = $base_url . 'uploads/web.png'; $tumbnailPath = 'uploads/web.png'; }
                                } else if (false && $digitalOceanStatus == '1') {
                                	// Initialize DigitalOcean Spaces client once
                                	// removed legacy SpacesConnect client

                                	// Unified: publish original + preview + thumb via storage helpers
                                	$toPublish = [];
                                	$toPublish[] = 'uploads/files/' . $d . '/' . $getFilename;
                                	if ($cmd) {
                                		$toPublish[] = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$toPublish[] = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$toPublish[] = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                	}
                                	if (function_exists('storage_publish_many')) {
                                		storage_publish_many($toPublish, true, false);
                                		$UploadSourceUrl = storage_publish_pick_url([
                                			'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg',
                                			'uploads/files/' . $d . '/' . $getFilename,
                                		], true) ?? ($base_url . 'uploads/web.png');
                                	} else {
                                		$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
                                	}
                                } else {
									if ($cmd) {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										$tumbnailPath = 'uploads/web.png';
										$tumbnailPath = $pathFile;
										$pathXFile = 'uploads/web.png';
									}
								}
								$ext = 'mp4';
								/**/
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
									$pathFilea = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
									$pathFileC = '../uploads/files/' . $d . '/' . $getFilename;
									$width = 500;
									$height = 500;
									$file = $pathFilea;
									$thePathM = '../' . $pathFile;
										$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '_thumb_' . $userID . '.jpg';
	                                    $originalKeepPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '__' . $userID . '.' . $ext;
	                                    // Preserve original before any thumb processing
	                                    $absOriginalKeep = '../' . $originalKeepPath;
	                                    if (!file_exists($absOriginalKeep) && file_exists($pathFileC)) {
	                                        @copy($pathFileC, $absOriginalKeep);
	                                    }
										// Build a dedicated compressed thumb from the original (always jpeg, max width 480, quality 60)
										$newThumbPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '_thumb_' . $userID . '.jpg';
										$newThumbAbs = '../' . $newThumbPath;
										$origAbs = '../' . $pathFile;
										if (file_exists($newThumbAbs)) { @unlink($newThumbAbs); }
											if (file_exists($origAbs)) {
												$imgInfo = @getimagesize($origAbs);
												if ($imgInfo && isset($imgInfo[0], $imgInfo[1])) {
													$origW = (int)$imgInfo[0];
													$origH = (int)$imgInfo[1];
													$targetWidth = $origW > 480 ? 480 : ($origW > 0 ? $origW : 480);
													$targetHeight = ($origW > 0) ? (int) round($origH * ($targetWidth / $origW)) : $origH;
													smart_resize_image($origAbs, null, $targetWidth, $targetHeight, true, $newThumbAbs, false, false, 60);
														if(file_exists($newThumbAbs) && ($watermarkStatus == 'yes' || $LinkWatermarkStatus == 'yes')) {
															watermark_apply_batch(
																[$origAbs, $newThumbAbs],
																$siteWatermarkLogo,
																$LinkWatermarkStatus,
																watermark_label($base_url, $userName)
															);
														}
													if (file_exists($newThumbAbs)) {
														$tumbnailPath = $newThumbPath; // use compressed thumb
													}
												}
										}
										if (file_exists($thePathM)) {
											try {
												$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
												$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
												$image = new ImageFilter();
												$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
											} catch (Exception $e) {
												echo '<span class="request_warning">' . $e->getMessage() . '</span>';
											}
									    }
	                                    // Create untouched original copy with __<userID> suffix if missing
	                                    $absOriginalKeep = '../' . $originalKeepPath;
	                                    if (!file_exists($absOriginalKeep) && file_exists($pathFileC)) {
	                                        @copy($pathFileC, $absOriginalKeep);
	                                    }
	                                    // Re-encode thumbnail to ensure smaller size
	                                    // Re-encode thumbnail to ensure smaller size
	                                    $thumbAbsolute = '../' . $tumbnailPath;
	                                    if (file_exists($thumbAbsolute)) {
	                                        smart_resize_image($thumbAbsolute, null, 480, 0, true, $thumbAbsolute, false, false, 60);
	                                    }
                                    // Publish to active storage provider using unified helpers
	                                    $keysToPublish = [
	                                        'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext,
	                                        'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext,
	                                        'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext,
	                                        'uploads/files/' . $d . '/' . $UploadedFileName . '_thumb_' . $userID . '.jpg',
										];
	                                    $UploadSourceUrl = storage_publish_and_url(
	                                        'uploads/files/' . $d . '/' . $getFilename,
	                                        $keysToPublish,
										true
                                );
							}
							/**/
								$insertFileFromUploadTable = $iN->iN_INSERTUploadedFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
								$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
								DB::exec("UPDATE i_user_uploads SET upload_type = 'product' WHERE upload_id = ?", [(int)$getUploadedFileID['upload_id']]);
								// Remove preserved original copy to avoid extra file storage
								$absOriginalKeep = isset($absOriginalKeep) ? $absOriginalKeep : null;
								if ($absOriginalKeep && file_exists($absOriginalKeep)) {
									@unlink($absOriginalKeep);
								}
							if ($fileTypeIs == 'video') {
								$uploadTumbnail = '
								<div class="v_custom_tumb">
									<label for="vTumb_' . $getUploadedFileID['upload_id'] . '">
										<div class="i_image_video_btn"><div class="pbtn pbtn_plus">' . $LANG['custom_tumbnail'] . '</div>
										<input type="file" id="vTumb_' . $getUploadedFileID['upload_id'] . '" class="imageorvideo cTumb editAds_file" data-id="' . $getUploadedFileID['upload_id'] . '" name="uploading[]" data-id="tupload">
									</label>
								</div>
								';
							}
							if ($fileTypeIs == 'video' || $fileTypeIs == 'Image') {
								/*AMAZON S3*/
								echo '
									<div class="i_uploaded_item iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '" id="' . $getUploadedFileID['upload_id'] . '">
									' . $postTypeIcon . '
									<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
										' . $iN->iN_SelectedMenuIcon('5') . '
									</div>
									<div class="i_uploaded_file" id="viTumb' . $getUploadedFileID['upload_id'] . '" style="background-image:url(' . $UploadSourceUrl . ');">
											<img class="i_file" id="viTumbi' . $getUploadedFileID['upload_id'] . '" src="' . $UploadSourceUrl . '" alt="tumbnail">
									</div>
									' . $uploadTumbnail . '
									</div>
								';
							}
						}else{
							echo $LANG['something_wrong'];
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
/*Insert New product*/
if($type == 'createScratch' || $type == 'createBookaZoom'){
   if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals'])){
      $productName = $iN->iN_Secure($_POST['prnm']);
	  $productPrice = $iN->iN_Secure($_POST['prprc']);
	  $productDescription = $iN->iN_Secure($_POST['prdsc']);
	  $productDescriptionInfo = $iN->iN_Secure($_POST['prdscinf']);
	  $productFiles = $iN->iN_Secure($_POST['vals']);
	  $productLimitSlots = $iN->iN_Secure($_POST['lmSlot']);
	  $productAskQuestion = $iN->iN_Secure($_POST['askQ']);
	  $productFiles = implode(',',array_unique(explode(',', $productFiles)));
	    if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			$trimValue = rtrim($productFiles, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach($explodeFiles as $explodeFile){
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				if(empty($uploadedFileID)){
				    exit('204');
				}
			}
	    }
		if($productLimitSlots == 'ok'){
			$productLimSlots = $iN->iN_Secure($_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = $iN->iN_Secure($_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}

	  if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == ''){
         exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
	  }
	  if($type == 'createScratch'){
         $productType = 'scratch';
	  }else if($type == 'createBookaZoom'){
		$productType = 'bookazoom';
	  }else if($type == 'createartcommission'){
		$productType = 'artcommission';
	  }else if($type == 'createjoininstagramclosefriends'){
		$productType = 'joininstagramclosefriends';
	  }
      $slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
	  $insertNewProduct = $iN->iN_InsertNewProduct($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
	  if($insertNewProduct){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if($type == 'productStatus'){
   if(isset($_POST['mod']) && in_array($_POST['mod'], $statusValue) && isset($_POST['id'])){
        $productID = $iN->iN_Secure($_POST['id']);
	  $newStatus = $iN->iN_Secure($_POST['mod']);
	  $updateProductStatus = $iN->iN_UpdateProductStatus($userID, $productID, $newStatus);
	  if($updateProductStatus){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if($type == 'saveEditPr'){
	if(isset($_POST['prnm']) && isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf'])){
		$productID = $iN->iN_Secure($_POST['prid']);
		$productName = $iN->iN_Secure($_POST['prnm']);
		$productPrice = $iN->iN_Secure($_POST['prprc']);
		$productDescription = $iN->iN_Secure($_POST['prdsc']);
		$productDescriptionInfo = $iN->iN_Secure($_POST['prdscinf']);
		$productLimitSlots = $iN->iN_Secure($_POST['lmSlot']);
		$productAskQuestion = $iN->iN_Secure($_POST['askQ']);
		if($productLimitSlots == 'ok'){
			$productLimSlots = $iN->iN_Secure($_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = $iN->iN_Secure($_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}
		if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == ''){
		   exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
		}
		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertUpdatedProduct($userID, $iN->iN_Secure($productID),$iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($slug), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
		if($insertNewProduct){
		  exit('200');
		}else{
		  exit('404');
		}
	 }
}
/*Get Free Follow PopUP*/
if ($type == 'delete_product') {
	if (isset($_POST['id'])) {
		$productID = $iN->iN_Secure($_POST['id']);
		$checkproductExist = $iN->iN_CheckProductIDExist($userID, $productID);
		if ($checkproductExist) {
			include "../themes/$currentTheme/layouts/popup_alerts/deleteProduct.php";
		}
	}
}
/*Delete Story From Database*/
if ($type == 'deleteProduct') {
	if (isset($_POST['id'])) {
		$productID = $iN->iN_Secure($_POST['id']);
		if(!empty($productID)){
			$getPostFileIDs = $iN->iN_ProductDetails($userID, $productID);
			$idsA = isset($getPostFileIDs['pr_files']) ? $getPostFileIDs['pr_files'] : '';
			$idsB = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : '';
			$merged = trim($idsA . ',' . $idsB, ',');
			$explodeFiles = $merged !== '' ? array_unique(explode(',', rtrim($merged, ','))) : [];
			foreach ($explodeFiles as $explodeFile) {
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				if($theFileID){
					$uploadedFileID = $theFileID['upload_id'];
					$uploadedFilePath = $theFileID['uploaded_file_path'];
					$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
					$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
					if (storage_is_remote()) {
						@storage_delete($uploadedFilePath);
						@storage_delete($uploadedFilePathX);
						@storage_delete($uploadedTumbnailFilePath);
					} else {
						@unlink('../' . $uploadedFilePath);
						@unlink('../' . $uploadedFilePathX);
						@unlink('../' . $uploadedTumbnailFilePath);
					}
                    DB::exec("DELETE FROM i_user_uploads WHERE upload_id = ? AND iuid_fk = ?", [(int)$uploadedFileID, (int)$userID]);
				}
			}
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){ echo '200'; } else { echo '404'; }
		}
	}
}
/*UPload Downloadable File*/
if ($type == 'prd_upload') {
	$availableFileExtensions = 'pdf,zip,PDF,ZIP';
	//$availableFileExtensions
	if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
		foreach ($_FILES['uploading']['name'] as $iname => $value) {
			$name = stripslashes($_FILES['uploading']['name'][$iname]);
			$size = $_FILES['uploading']['size'][$iname];
			$ext = getExtension($name);
			$ext = strtolower($ext);
			$valid_formats = explode(',', $availableFileExtensions);
			if (in_array($ext, $valid_formats)) {
				if (convert_to_mb($size) < $availableUploadFileSize) {
					$microtime = microtime();
					$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
					$UploadedFileName = "file_" . $removeMicrotime . '_' . $userID;
					$getFilename = $UploadedFileName . "." . $ext;
					// Change the image ame
					$tmp = $_FILES['uploading']['tmp_name'][$iname];
					$mimeType = $_FILES['uploading']['type'][$iname];
					$d = date('Y-m-d');

					if (!file_exists($uploadFile . $d)) {
						$newFile = mkdir($uploadFile . $d, 0755);
					}
					$uploadTumbnail = '';
					if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
						/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
						$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
						$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
						$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
						$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
						// Unified publish for generic file upload
						{
							$fileKey = 'uploads/files/' . $d . '/' . $getFilename;
							$UploadSourceUrl = storage_publish_and_url($fileKey, [$fileKey], true);
							if (!$UploadSourceUrl) {
								$UploadSourceUrl = $UploadedFilePath;
							}
							$status = 'ok';
						}
						/*CHECK AMAZON S3 AVAILABLE (disabled by unified storage)*/
						if (false && $s3Status == '1') {
							/*Upload Full video*/
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							$key = basename($theName);

							try {
								$result = $s3->putObject([
									'Bucket' => $s3Bucket,
									'Key' => 'uploads/files/' . $d . '/' . $key,
									'Body' => fopen($theName, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$fullUploadedVideo = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $getFilename);
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo $LANG['error_uploading_file'] . "\n";
							}
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						}else if (false && $WasStatus == '1') {
							/*Upload Full video*/
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							$key = basename($theName);

							try {
								$result = $s3->putObject([
									'Bucket' => $WasBucket,
									'Key' => 'uploads/files/' . $d . '/' . $key,
									'Body' => fopen($theName, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$fullUploadedVideo = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $getFilename);
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo $LANG['error_uploading_file'] . "\n";
							}
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						} else if (false && $digitalOceanStatus == '1') {
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							/*IF DIGITALOCEAN AVAILABLE THEN*/
							$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
							$upload = $my_space->UploadFile($theName, "public");
							if($upload){
								@unlink($uploadFile . $d . '/' . $getFilename);
							}
							/*/IF DIGITAOCEAN AVAILABLE THEN*/
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						} else if (false) {
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						}
						/**/
						if($ext == 'pdf'){
                           $fileIcon = html_entity_decode($iN->iN_SelectedMenuIcon('166'));
						}else{
						   $fileIcon = html_entity_decode($iN->iN_SelectedMenuIcon('167'));
						}
						if($UploadSourceUrl){
							$data = array(
								'status' => $status,
								'fileUrl' => $UploadSourceUrl,
								'filePath' => $pathFile,
								'fileIcon' => $fileIcon,
								'fileName' => $getFilename
							);
							$result = json_encode($data);
							echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
						}
					}else{
						echo $LANG['something_wrong'];
					}
				} else {
					echo iN_HelpSecure($size);
				}
			}
		}
	}
}
if($type == 'createDigitalDownload'){
	if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals']) && isset($_POST['dFile'])){
		$productName = $iN->iN_Secure($_POST['prnm']);
		$productPrice = $iN->iN_Secure($_POST['prprc']);
		$productDescription = $iN->iN_Secure($_POST['prdsc']);
		$productDescriptionInfo = $iN->iN_Secure($_POST['prdscinf']);
		$productFiles = $iN->iN_Secure($_POST['vals']);
		$productDownloadableFile = $iN->iN_Secure($_POST['dFile']);
		$productFiles = implode(',',array_unique(explode(',', $productFiles)));
		  if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			  $trimValue = rtrim($productFiles, ',');
			  $explodeFiles = explode(',', $trimValue);
			  $explodeFiles = array_unique($explodeFiles);
			  foreach($explodeFiles as $explodeFile){
				  $theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				  $uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				  if(empty($uploadedFileID)){
					  exit('204');
				  }
			  }
		  }
		if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == '' || preg_replace('/\s+/', '',$productDownloadableFile) == ''){
		   exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
		}
		$productType = 'digitaldownload';

		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertNewProductDownloadable($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productDownloadableFile));
		if($insertNewProduct){
		  exit('200');
		}else{
		  exit('404');
		}
	 }
}
/*Insert New product*/
if($type == 'createliveeventticket' || $type == 'createartcommission' || $type == 'createjoininstagramclosefriends'){
	if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals'])){
	    $productName = $iN->iN_Secure($_POST['prnm']);
	    $productPrice = $iN->iN_Secure($_POST['prprc']);
	    $productDescription = $iN->iN_Secure($_POST['prdsc']);
	    $productDescriptionInfo = $iN->iN_Secure($_POST['prdscinf']);
	    $productFiles = $iN->iN_Secure($_POST['vals']);
	    $productLimitSlots = $iN->iN_Secure($_POST['lmSlot']);
	    $productAskQuestion = $iN->iN_Secure($_POST['askQ']);
	    $productFiles = implode(',',array_unique(explode(',', $productFiles)));
		if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			$trimValue = rtrim($productFiles, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach($explodeFiles as $explodeFile){
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				if(empty($uploadedFileID)){
					exit('204');
				}
			}
		}
		if($productLimitSlots == 'ok'){
			$productLimSlots = $iN->iN_Secure($_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = $iN->iN_Secure($_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}
	    if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == ''){
			exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
	    }
		if($type == 'createliveeventticket'){
			$productType = 'liveeventticket';
		} else if($type == 'createartcommission'){
			$productType = 'artcommission';
		} else if($type == 'createjoininstagramclosefriends'){
			$productType = 'joininstagramclosefriends';
		}
		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertNewProductLiveEventTicket($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
		if($insertNewProduct){
			exit('200');
		}else{
			exit('404');
		}
	}
}

if($type == 'shareMyTextStory'){
	include_once __DIR__ . '/../includes/csrf.php';
	if (!csrf_validate_from_request()) {
		exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
	}
	if(isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['stext']) && !empty($_POST['stext']) && $_POST['stext'] != ''){
		$bgID = $iN->iN_Secure($_POST['id']);
		$storyText = $iN->iN_Secure($_POST['stext']);
		$privacy = isset($_POST['privacy']) ? $iN->iN_Secure($_POST['privacy']) : 'followers';
		$overlayLink = isset($_POST['overlay_link']) ? (string)$_POST['overlay_link'] : '';
		$overlayMention = isset($_POST['overlay_mention']) ? (string)$_POST['overlay_mention'] : '';
		$overlaySticker = isset($_POST['overlay_sticker']) ? (string)$_POST['overlay_sticker'] : '';
		$overlayAudio = isset($_POST['overlay_audio']) ? (int)$iN->iN_Secure($_POST['overlay_audio']) : 0;
		$quickRepliesRaw = $_POST['quick_replies'] ?? '';
		$quickReplies = $iN->iN_NormalizeStoryQuickReplies($quickRepliesRaw);
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			$quickReplies = [];
		}
		if(preg_replace('/\s+/', '',$storyText) == ''){
			exit(iN_HelpSecure($LANG['please_add_text_in_your_story']));
		}
		$insertTextStory = $iN->iN_InsertTextStory(
			$userID,
			$iN->iN_Secure($bgID),
			$iN->iN_Secure($storyText),
			$privacy,
			$overlayLink,
			$overlayMention,
			$overlaySticker,
			$overlayAudio,
			$quickReplies
		);
		if($insertTextStory){
			exit('200');
		}else{
			exit('404');
		}
	}
}
if ($type == 'buyProduct') {
	if (isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type'])) {
		$productID = $iN->iN_Secure($_POST['type']);
	    $checkproductID = $iN->iN_CheckProductIDExistFromURL($productID);
		if($checkproductID == TRUE){
			$prData = $iN->iN_GetProductDetailsByID($productID);
			$planAmount = $prData['pr_price'];
			$ProductOwnerID = $prData['iuid_fk'];

			if($ProductOwnerID == $userID){
              exit('me');
			}
			$planPoint = '';
			if($stripePaymentCurrency == 'JPY'){
				 $planAmount = $planAmount / 100;
			}
			$productName = isset($prData['pr_name']) ? trim(strip_tags($prData['pr_name'])) : '';
			$productOwnerName = trim(strip_tags($iN->iN_UserFullName($ProductOwnerID)));
			$productItemName = $LANG['point_purchasing'];
			$productDescription = $LANG['point_purchasing_from'];
			if ($productName !== '' && isset($LANG['payment_item_product_to_seller'])) {
				$productItemName = str_replace('{product}', $productName, $LANG['payment_item_product_to_seller']);
			}
			if ($productOwnerName !== '' && isset($LANG['payment_desc_product_to_seller'])) {
				$productDescription = str_replace(
					['{product}', '{seller}'],
					[$productName !== '' ? $productName : $productItemName, $productOwnerName],
					$LANG['payment_desc_product_to_seller']
				);
			}
			require_once '../includes/payment/vendor/autoload.php';
			if (!defined('INORA_METHODS_CONFIG')) {
				define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
			}
			$configData = configItem();
					$DataUserDetails = [
						'amounts' => [ // at least one currency amount is required
							$payPalCurrency => $planAmount,
							$iyziCoPaymentCurrency => $planAmount,
							$bitPayPaymentCurrency => $planAmount,
							$autHorizePaymentCurrency => $planAmount,
							$payStackPaymentCurrency => $planAmount,
							$stripePaymentCurrency => $planAmount,
							$razorPayPaymentCurrency => $planAmount,
							$mercadoPagoCurrency => $planAmount,
							$paysafecardCurrency => $planAmount,
							$flutterWaveCurrency => $planAmount,
						],
				'order_id' => 'ORDS' . uniqid(), // required in instamojo, Iyzico, Paypal, Paytm gateways
				'customer_id' => 'CUSTOMER' . uniqid(), // required in Iyzico, Paytm gateways
				'item_name' => $productItemName, // required in Paypal gateways
				'item_qty' => 1,
				'item_id' => 'ITEM' . uniqid(), // required in Iyzico, Paytm gateways
				'payer_email' => $userEmail, // required in instamojo, Iyzico, Stripe gateways
				'payer_name' => $userFullName, // required in instamojo, Iyzico gateways
				'description' => $productDescription, // Required for stripe
				'ip_address' => getUserIpAddr(), // required only for iyzico
				'address' => '3234 Godfrey Street Tigard, OR 97223', // required in Iyzico gateways
				'city' => 'Tigard', // required in Iyzico gateways
				'country' => 'United States', // required in Iyzico gateways
				'payment_type' => 'product',
				'product_id' => (int) $productID,
				'product_owner_id' => (int) $ProductOwnerID,
				'payer_id' => (int) $userID,
				'order_amount' => $planAmount,
			];
			$PublicConfigs = getPublicConfigItem();

			$configItem = $configData['payments']['gateway_configuration'];

			// Get config data
			$configa = getPublicConfigItem();
			// Get app URL
			$paymentPagePath = getAppUrl();

			$gatewayConfiguration = $configData['payments']['gateway_configuration'];
			// get paystack config data
			$paystackConfigData = $gatewayConfiguration['paystack'];
			// Get paystack callback ur
			$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

			// Get stripe config data
			$stripeConfigData = $gatewayConfiguration['stripe'];
			// Get stripe callback ur
			$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

			// Get razorpay config data
			$razorpayConfigData = $gatewayConfiguration['razorpay'];
			// Get razorpay callback url
			$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

				// Get Authorize.Net config Data
				$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
				// Get Authorize.Net callback url
				$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

				// Get Flutterwave config data
				$flutterwaveConfigData = $gatewayConfiguration['flutterwave'];
				$flutterwaveCallbackUrl = getAppUrl($flutterwaveConfigData['callbackUrl']);

			// Individual payment gateway url
			$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
			// User Details Configurations FINISHED
			include "../themes/$currentTheme/layouts/popup_alerts/paymentMethodsForPurchaseProduct.php";
		}
	}
}
if ($type == 'processProduct') {
	require_once '../includes/payment/vendor/autoload.php';
	if (!defined('INORA_METHODS_CONFIG')) {
		define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
	}
	include "../includes/payment/payment-process-product.php";
}
if($type == 'downloadMyProduct'){
   if(isset($_POST['myp']) && !empty($_POST['myp']) && $_POST['myp'] != ''){
      $productID = $iN->iN_Secure($_POST['myp']);
	  $checkProductPurchasedBefore = $iN->iN_CheckItemPurchasedBefore($userID, $productID);
	  if($checkProductPurchasedBefore){
		$productData = $iN->iN_GetProductDetailsByID($productID);
		$uProductDownloadableFiles = $productData['pr_downlodable_files'];
		$thefile = $uProductDownloadableFiles;
		$file = $uProductDownloadableFiles;
		$ext = substr($file, strrpos($file, '.') + 1);
        $fake = 'aa.'.$ext;
		if (file_exists($thefile)) {
			$iN->download($file,$fake);
		}
	  }
   }
}
if($type == 'gotAnnouncement'){
   if(isset($_POST['aid']) && $_POST['aid'] != ''){
       $announceID = $iN->iN_Secure($_POST['aid']);
	   $announcementReaded = $iN->iN_AnnouncementAccepted($userID, $announceID);
	   if($announcementReaded){
         exit('200');
	   }else{
		 exit('404');
	   }
   }
}
if($type == 'mrProduct'){
    if(isset($_POST['last']) && isset($_POST['ty'])){
       $productID = $iN->iN_Secure($_POST['last']);
       $categoryKey = $iN->iN_Secure($_POST['ty']);
       $productData = $iN->iN_AllUserProductPosts($categoryKey, $productID, $showingNumberOfPost);
	   include "../themes/$currentTheme/layouts/loadmore/moreProduct.php";
	}
}
if($type == 'moveMyAffilateBalance'){
  if(isset($_POST['myp']) && $_POST['myp'] != '' && !empty($_POST['myp'])){
	  $moveMyPoint = $iN->iN_MoveMyPoint($userID);
  }
}
/*Open Profile Tip Box*/
if($type == 'p_p_tips'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = $iN->iN_Secure($_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendProfileTipPoint.php";
	}
}
/*Open Profile Frame Box*/
if($type == 'p_p_frame'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = $iN->iN_Secure($_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendProfileFrame.php";
	}
}
if($type == 'p_p_tips_message'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = $iN->iN_Secure($_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendMessageTipPoint.php";
	}
}
/*Send Tip*/
if($type == 'p_sendTipProfile'){
	if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
	   $tiSendingUserID = $iN->iN_Secure($_POST['tip_u']);
	   $tipAmount = $iN->iN_Secure($_POST['tipVal']);
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   if($tipAmount < $minimumTipAmount){
		  $emountnot = 'notEnough';
	   }else{
		  if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

			  $netUserEarning = $tipAmount * $onePointEqual;
			  $adminEarning = ($adminFee * $netUserEarning) / 100;
			  $userNetEarning = $netUserEarning - $adminEarning;

			  $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
			  if($UpdateUsersWallet){
				 $status = 'ok';
			  }else{
				 $status = '404';
			  }
		   }else{
			  $status = '';
			  $emountnot = 'notEnouhCredit';
			  $redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		   }
	   }
	   $data = array(
		  'status' => $status,
		  'redirect' => $redirect,
		  'enamount' => $emountnot
	   );
	   $result = json_encode($data);
	   echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
	   if($status == 'ok'){
		  $iN->iN_InsertNotificationForTip($userID, $tiSendingUserID);
		  $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
		  $toUserName = $userDeviceKey['i_username'];
		  $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
		  $msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		  $msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
		  $URL = $base_url.'settings?tab=dashboard';
		  if($oneSignalUserDeviceKey){
			$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $URL, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		  }
	    }
	}
}

/*Send Tip*/
if($type == 'p_sendTipMessage'){
	if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
	   $tiSendingUserID = $iN->iN_Secure($_POST['tip_u']);
	   $tipAmount = $iN->iN_Secure($_POST['tipVal']);
	   $chatID = $iN->iN_Secure($_POST['chID']);
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   if($tipAmount < $minimumTipAmount){
		  exit('notEnough');
	   }else{
		  if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

			  $netUserEarning = $tipAmount * $onePointEqual;
			  $adminEarning = ($adminFee * $netUserEarning) / 100;
			  $userNetEarning = $netUserEarning - $adminEarning;

			  $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
			  if($UpdateUsersWallet){
				 $status = 'ok';
			  }else{
				 exit('404');
			  }
		   }else{
			  exit('notEnouhCredit');
			  $redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		   }
	   }

	   if($status == 'ok'){
		  $iN->iN_InsertNotificationForTip($userID, $tiSendingUserID);
		  $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
		  $toUserName = $userDeviceKey['i_username'];
		  $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
		  $msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		  $msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
		  $URL = $base_url.'settings?tab=dashboard';
		  if($oneSignalUserDeviceKey){
			$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $URL, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		  }
		  $message = $userNetEarning;
		  $sendedGiftMoney = $tipAmount;
		  $insertData = $iN->iN_InsertNewTipMessage($userID, $chatID, $message, $sendedGiftMoney);
			if ($insertData) {
				$cMessageID = $insertData['con_id'];
				$cUserOne = $insertData['user_one'];
				$cUserTwo = $insertData['user_two'];
				$cMessage = $insertData['message'];
				$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
				$mSeenStatus = $insertData['seen_status'];
				$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
				$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
				$cMessageTime = $insertData['time'];
				$ip = $iN->iN_GetIPAddress();
				$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
				if ($query && $query['status'] == 'success') {
					date_default_timezone_set($query['timezone']);
				}
				$message_time = date("c", $cMessageTime);
				$convertMessageTime = strtotime($message_time);
				$netMessageHour = date('H:i', $convertMessageTime);
				$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
				$msgDots = '';
				$imStyle = '';
				$seenStatus = '';
				if ($cUserOne == $userID) {
					$mClass = 'me';
					$msgOwnerID = $cUserOne;
					$lastM = '';
					$timeStyle = 'msg_time_me';
					if (!empty($cFile)) {
						$imStyle = 'mmi_i';
					}
					$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					if ($mSeenStatus == '1') {
						$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					}
					if($gifMoney){
                        $SGifMoneyText = preg_replace( '/{.*?}/', $cMessage, $LANG['youSendGifMoney']);
                    }
				} else {
					$mClass = 'friend';
					$msgOwnerID = $cUserOne;
					$lastM = 'mm_' . $msgOwnerID;
					if (!empty($cFile)) {
						$imStyle = 'mmi_if';
					}
					if($gifMoney){
                        $msgOwnerFullName = $iN->iN_UserFullName($msgOwnerID);
                        $SGifMoneyText = $iN->iN_TextReaplacement($LANG['sendedGifMoney'],[$msgOwnerFullName , $cMessage]);
                    }
					$timeStyle = 'msg_time_fri';
				}
				$styleFor = '';
				if ($cStickerUrl) {
					$styleFor = 'msg_with_sticker';
					$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
				}
				if ($cGifUrl) {
					$styleFor = 'msg_with_gif';
					$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
				}
				$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
				include "../themes/$currentTheme/layouts/chat/newMessage.php";
			}

	   }
	}
}
  /*Buy Video Call*/
  if($type == 'buyVideoCall'){
     if(isset($_POST['calledID']) && $_POST['calledID'] !== '' && !empty($_POST['calledID']) && isset($_POST['callName']) && $_POST['callName'] !== '' && !empty($_POST['callName'])){
		$calledUserID = $iN->iN_Secure($_POST['calledID']);
		$videoCallName = $iN->iN_Secure($_POST['callName']);
		$callerDetails = $iN->iN_GetUserDetails($calledUserID);
		$callerUserFullName = $callerDetails['i_user_fullname'];
		$callerUserName = $callerDetails['i_username'];
		$videoCallPrice = $callerDetails['video_call_price'];
		$callerUserAvatar = $iN->iN_UserAvatar($calledUserID, $base_url);
		if($isVideoCallFree == 'no'){
			include "../themes/$currentTheme/layouts/popup_alerts/buyVideoCall.php";
		}else if($isVideoCallFree == 'yes'){
			$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
			include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
		}else{
			exit('404');
		}
	 }
  }
  /*Create a video call*/
  if($type == 'createVideoCall'){
      if(isset($_POST['calledID']) && $_POST['calledID'] !== '' && !empty($_POST['calledID']) && isset($_POST['callName']) && $_POST['callName'] !== '' && !empty($_POST['callName'])){
		    $calledUserID = $iN->iN_Secure($_POST['calledID']);
			$videoCallName = $iN->iN_Secure($_POST['callName']);
			$callerDetails = $iN->iN_GetUserDetails($calledUserID);
			$callerUserFullName = $callerDetails['i_user_fullname'];
			$callerUserName = $callerDetails['i_username'];
			$videoCallPrice = $callerDetails['video_call_price'];
			$callerUserAvatar = $iN->iN_UserAvatar($calledUserID, $base_url);
            $requiresPayment = ($isVideoCallFree == 'no' && $userID != $calledUserID);
            if($requiresPayment){
                $callerBalanceRow = $iN->iN_GetUserDetails($userID);
                $currentPointsForCall = isset($callerBalanceRow['wallet_points']) ? (float)$callerBalanceRow['wallet_points'] : 0;
                if($currentPointsForCall < $videoCallPrice){
                    exit('402'); // not enough balance
                }
                $netUserEarning = $videoCallPrice * $onePointEqual;
                $adminEarning = ($adminFee * $netUserEarning) / 100;
                $userNetEarning = $netUserEarning - $adminEarning;
                $UpdateUsersWallet = $iN->iN_UpdateUsersWalletsForVideoCall($userID, $calledUserID, $videoCallPrice, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
                if(!$UpdateUsersWallet){
                    exit('500'); // wallet update failed
                }
            }
			$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
            if(!$insertChannelName){
                exit('500'); // channel creation failed
            }
			include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
	  }
  }
  /*Video Call Alert*/
  if($type == 'videoCallAlert'){
      if(isset($_POST['call']) && !empty($_POST['call']) && $_POST['call'] !== ''){
          $callID = $iN->iN_Secure($_POST['call']);
		  $callDetails = $iN->iN_VideoCallDetails($callID);
		  $callerUserID = $callDetails['caller_uid_fk'];
		  $chatUrl = $callDetails['vc_id'];
		  $callerDetails = $iN->iN_GetUserDetails($callerUserID);
		  $callerUserFullName = $callerDetails['i_user_fullname'];
		  $callerUserName = $callerDetails['i_username'];
		  $callerUserAvatar = $iN->iN_UserAvatar($callerUserID, $base_url);
		  if($fullnameorusername == 'no'){
			$callerUserFullName = $callerUserName;
		  }
		  include "../themes/$currentTheme/layouts/popup_alerts/videocallalert.php";
	  }
  }
  /*Video Call Accept*/
  if($type == 'call_accepted'){
    if(isset($_POST['accID']) && !empty($_POST['accID']) && $_POST['accID'] !== ''){
		$callID = $iN->iN_Secure($_POST['accID']);
		$callDetails = $iN->iN_VideoCallAcceptDetails($callID);
		$chatUrl = $callDetails['chat_id_fk'];
		echo iN_HelpSecure($base_url) . 'chat?chat_width=' . $chatUrl;
	}
  }
if ($type == 'liveVideoMute') {
    if (isset($_POST['chName']) && !empty($_POST['chName'])) {
        $channelName = $iN->iN_Secure($_POST['chName']);

        $call = DB::one("SELECT vc_id, video_muted FROM i_video_call WHERE voice_call_name = ? LIMIT 1", [$channelName]);

        if ($call) {
            $currentStatus = (int)$call['video_muted'];
            $newStatus = $currentStatus === 1 ? 0 : 1;

            $update = DB::exec("UPDATE i_video_call SET video_muted = ? WHERE vc_id = ?", [$newStatus, (int)$call['vc_id']]);

            echo json_encode([
                'status' => $update ? 'success' : 'error',
                'muted' => $newStatus,
                'message' => $update ? 'Updated successfully' : 'Update failed'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Channel not found'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing channel name'
        ]);
    }
    exit(); // her durumda 癟覺k
}
  /*Live Call End*/
  if($type == 'liveEnd'){
     if(isset($_POST['chName']) && !empty($_POST['chName']) && $_POST['chName'] !== ''){
        $channelName = $iN->iN_Secure($_POST['chName']);
		$checkAndDeleteCall = $iN->iN_CheckAndDeleteCall($userID, $channelName);
		if($checkAndDeleteCall){
           exit('200');
		}else{
			exit('404');
		}
	 }
  }
  /*Agora: Request new RTC token (live) or call token (call context)*/
  if($type == 'agoraNewToken'){
     header('Content-Type: application/json');
     if(isset($_POST['ch']) && $_POST['ch'] !== '' && isset($_POST['host'])){
        $channelName = $iN->iN_Secure($_POST['ch']);
        $asHost = $_POST['host'] === '1' ? true : false;
        $context = isset($_POST['context']) ? strtolower(trim($_POST['context'])) : 'live';
        $agoraAppIdClean = trim((string)$agoraAppID);
        $agoraCertClean = trim((string)$agoraCertificate);
        $agoraCustomerIdClean = trim((string)$agoraCustomerID);
        $isValidHex32 = static function($value) {
            return is_string($value) && preg_match('/^[A-Fa-f0-9]{32}$/', $value);
        };
        // Ensure user is logged in
        if(!isset($userID) || empty($userID)){
            echo json_encode(['status' => 'error', 'message' => $LANG['unauthorized_error']]);
            exit();
        }
        if ($context === 'call') {
            $callProviderKey = isset($callProvider) ? $callProvider : 'agora';
            $isometrikConfigComplete = !empty($isometrikAPIKey) && !empty($isometrikAPISecret) && !empty($isometrikProjectId) && !empty($isometrikWSUrl);
            $livekitConfigCompleteCall = !empty($livekitAPIKey) && !empty($livekitAPISecret) && !empty($livekitWSUrl);
            $agoraConfigValid = $isValidHex32($agoraAppIdClean) && $isValidHex32($agoraCertClean);
            if ($callProviderKey === 'isometrik' && !$isometrikConfigComplete) {
                $callProviderKey = 'agora';
            }
            if ($callProviderKey === 'livekit' && !$livekitConfigCompleteCall) {
                $callProviderKey = 'agora';
            }
            try{
                if ($callProviderKey === 'agora' && !$agoraConfigValid) {
                    rq_debug('agoraNewToken call: invalid Agora credentials', [
                        'appIdLen' => strlen($agoraAppIdClean),
                        'certLen'  => strlen($agoraCertClean),
                        'uid'      => (int)$userID
                    ]);
                    echo json_encode(['status' => 'error', 'message' => $LANG['token_generation_failed']]);
                    exit();
                }
                $callProviderInstance = LiveProviderFactory::makeVideoCallProvider(
                    (string)$agoraAppIdClean,
                    (string)$agoraCertClean,
                    (string)$callProviderKey,
                    (string)($livekitAPIKey ?? ''),
                    (string)($livekitAPISecret ?? ''),
                    (string)($livekitWSUrl ?? ''),
                    (string)($isometrikAPIKey ?? ''),
                    (string)($isometrikAPISecret ?? ''),
                    (string)($isometrikProjectId ?? ''),
                    (string)($isometrikWSUrl ?? '')
                );
                $token = $callProviderInstance->generateCallToken($channelName, (int)$userID, $asHost);
                echo json_encode(['status' => 'ok', 'token' => $token, 'channel' => $channelName, 'uid' => (int)$userID]);
            }catch(Exception $e){
                rq_debug('agoraNewToken call: provider error', ['provider' => $callProviderKey, 'error' => $e->getMessage()]);
                echo json_encode(['status' => 'error', 'message' => 'Call provider configuration is incomplete']);
            }
        } else {
            $providerKey = isset($rtProvider) ? $rtProvider : 'agora';
            $livekitConfigComplete = !empty($livekitAPIKey) && !empty($livekitAPISecret) && !empty($livekitWSUrl);
            $agoraConfigValid = $isValidHex32($agoraAppIdClean) && $isValidHex32($agoraCertClean);
            if ($providerKey === 'livekit' && !$livekitConfigComplete) {
                echo json_encode(['status' => 'error', 'message' => 'Live provider configuration is incomplete']);
                exit();
            }
            try{
                if ($providerKey === 'agora' && !$agoraConfigValid) {
                    rq_debug('agoraNewToken live: invalid Agora credentials', [
                        'appIdLen' => strlen($agoraAppIdClean),
                        'certLen'  => strlen($agoraCertClean),
                        'uid'      => (int)$userID
                    ]);
                    echo json_encode(['status' => 'error', 'message' => $LANG['token_generation_failed']]);
                    exit();
                }
                $liveProvider = LiveProviderFactory::makeLiveProvider(
                    (string)$agoraAppIdClean,
                    (string)$agoraCertClean,
                    (string)$agoraCustomerIdClean,
                    (string)$providerKey,
                    (string)($livekitAPIKey ?? ''),
                    (string)($livekitAPISecret ?? ''),
                    (string)($livekitWSUrl ?? '')
                );
                $token = $liveProvider->generateJoinToken($channelName, (int)$userID, $asHost);
                echo json_encode(['status' => 'ok', 'token' => $token, 'channel' => $channelName, 'uid' => (int)$userID]);
            }catch(Exception $e){
                rq_debug('agoraNewToken live: provider error', ['provider' => $providerKey, 'error' => $e->getMessage()]);
                $message = $providerKey === 'livekit' ? 'Live provider configuration is incomplete' : $LANG['token_generation_failed'];
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        }
        exit();
     }
     echo json_encode(['status' => 'error', 'message' => $LANG['bad_request']]);
     exit();
  }
  /*Video Call Decline*/
  if($type == 'call_declined'){
    if(isset($_POST['accID']) && !empty($_POST['accID']) && $_POST['accID'] !== ''){
		$callID = $iN->iN_Secure($_POST['accID']);
		$callDetails = $iN->iN_VideoCallDeclineDetails($callID);
	}
  }
  /*Update Video Call fee*/
  if($type == 'vCost'){
     if(isset($_POST['vCostFee']) && !empty($_POST['vCostFee']) && $_POST['vCostFee'] !== ''){
          $videoCost = $iN->iN_Secure($_POST['vCostFee']);
		  if($videoCost == '0'){
            exit('not');
		  }
		$insertVideoCost = $iN->iN_UpdateVideoCost($userID, $videoCost);
	 }else{
		 exit('not');
	 }
  }
  if($type == 'moveMyEarnedPoints'){
	if(isset($_POST['myp']) && $_POST['myp'] != '' && !empty($_POST['myp'])){
		$totalEarned = $iN->iN_Secure($_POST['myp']);
		if($totalEarned < 1){
           exit('You don\'t have enough points to calculate yet.');
		}
		$moveMyPoint = $iN->iN_MovePointEarningsToPointBalance($userID, $totalEarned);
		if($moveMyPoint){
			exit('ok');
		}else{
			exit('me');
		}
	}else{
		exit('You don\'t have enough points to calculate yet.');
	}
  }
  /*Unlock Message*/
  if($type == 'unlockMessage'){
    include_once __DIR__ . '/../includes/csrf.php';
    if (!csrf_validate_from_request()) {
        exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
    }
    if(isset($_POST['mi']) && !empty($_POST['mi']) && $_POST['mi'] != '' && isset($_POST['ci']) && !empty($_POST['ci']) && $_POST['ci'] != ''){
       $messageID = $iN->iN_Secure($_POST['mi']);
	   $chatID = $iN->iN_Secure($_POST['ci']);
	   $getMData = $iN->iN_GetMessageDetailsByID($messageID, $chatID);
	   $messagePrice = isset($getMData['private_price']) ? $getMData['private_price'] : NULL;
	   $userOne = isset($getMData['user_one']) ? $getMData['user_one'] : NULL;
	   $userTwo = isset($getMData['user_two']) ? $getMData['user_two'] : NULL;
	   if($userOne == $userID){
         $messageOwnerID = $userTwo;
	   }else{
		 $messageOwnerID = $userOne;
	   }
	   if($userCurrentPoints >= $messagePrice){
		    $translatePointToMoney = $messagePrice * $onePointEqual;
			$adminEarning = $translatePointToMoney * ($adminFee / 100);
			$userEarning = $translatePointToMoney - $adminEarning;
			$insertData = $iN->iN_UnLockMessage($userID, $messageID, $chatID, $adminEarning, $userEarning,$messageOwnerID, $translatePointToMoney, $adminFee, $messagePrice);
			if($insertData){
					$cMessageID = $insertData['con_id'];
					$cUserOne = $insertData['user_one'];
					$cUserTwo = $insertData['user_two'];
					$cMessage = $insertData['message'];
					$mSeenStatus = $insertData['seen_status'];
					$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
					$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				    $privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
					$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
					$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
					$cMessageTime = $insertData['time'];
					$ip = $iN->iN_GetIPAddress();
					$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
					if ($query && $query['status'] == 'success') {
						date_default_timezone_set($query['timezone']);
					}
					$message_time = date("c", $cMessageTime);
					$convertMessageTime = strtotime($message_time);
					$netMessageHour = date('H:i', $convertMessageTime);
					$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
					$msgDots = '';
					$imStyle = '';
					$seenStatus = '';
					if ($cUserOne == $userID) {
						$mClass = 'me';
						$msgOwnerID = $cUserOne;
						$lastM = '';
						$timeStyle = 'msg_time_me';
						if (!empty($cFile)) {
							$imStyle = 'mmi_i';
						}
						$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						if ($mSeenStatus == '1') {
							$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						}
					} else {
						$mClass = 'friend';
						$msgOwnerID = $cUserOne;
						$lastM = 'mm_' . $msgOwnerID;
						if (!empty($cFile)) {
							$imStyle = 'mmi_if';
						}
						$timeStyle = 'msg_time_fri';
					}
					$styleFor = '';
					if ($cStickerUrl) {
						$styleFor = 'msg_with_sticker';
						$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
					}
					if ($cGifUrl) {
						$styleFor = 'msg_with_gif';
						$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
					}
					$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
					include "../themes/$currentTheme/layouts/chat/unLockedMessage.php";
			}else{
			  exit('403');
			}
	   }else{
		  exit('404');
	   }
	}
  }
	/*Show PopUps*/
	if ($type == 'camAlert') {
		if (isset($_POST['al'])) {
			$alertType = $iN->iN_Secure($_POST['al']);
			include "../themes/$currentTheme/layouts/popup_alerts/popup_alerts.php";
		}
	}
	if ($type == 'getBoostList') {
		if(isset($_POST['bp']) && !empty($_POST['bp'])){
           $boostPostID = $iN->iN_Secure($_POST['bp']);
		   include "../themes/$currentTheme/layouts/popup_alerts/getBoostList.php";
		}
	}
		if($type =='boostThisPlan'){
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			if(isset($_POST['pbID']) && !empty($_POST['bpID'])){
				$boostPlanID = $iN->iN_Secure($_POST['pbID']);
				$boostPostID = $iN->iN_Secure($_POST['bpID']);
			    $CheckboostIDExist = $iN->CheckBoostPlanExist($boostPlanID);
            if($CheckboostIDExist){
				$boostDetails = $iN->iN_GetBoostPostDetails($boostPlanID);
                $planAmount = $boostDetails['plan_amount'];
				$viewTime = $boostDetails['view_time'];
			    $checkPostBoostedeBefore = $iN->iN_CheckPostBoostedBefore($userID, $boostPostID);
				if($checkPostBoostedeBefore){
                   $getPostDetails = $iN->iN_GetAllPostDetails($boostPostID);
				   $boostedPostSlugUrl = isset($getPostDetails['url_slug']) ? $getPostDetails['url_slug'] : NULL;
				   $redirectThisURL = $base_url.'post/'.$boostedPostSlugUrl.'_'.$boostPostID;
				   echo iN_HelpSecure($redirectThisURL);
				   exit();
				}
				if($planAmount < $userCurrentPoints){
				   $boostInsert = $iN->iN_BoostInsert($userID, $boostPostID, $planAmount,$boostPlanID,$viewTime);
				   if($boostInsert){
						$getPostDetails = $iN->iN_GetAllPostDetails($boostPostID);
						$boostedPostSlugUrl = isset($getPostDetails['url_slug']) ? $getPostDetails['url_slug'] : NULL;
						$redirectThisURL = $base_url.'post/'.$boostedPostSlugUrl.'_'.$boostPostID;
				        echo iN_HelpSecure($redirectThisURL);
				   }
				}else{
					exit('404');
				}
			}
		}
	}
			/*Update Boost Status*/
			if($type == 'updateBoostStatus'){
				include_once __DIR__ . '/../includes/csrf.php';
				if (!csrf_validate_from_request()) {
					exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
				}
				if(isset($_POST['bpid']) && !empty($_POST['bpid']) && isset($_POST['mod']) && in_array($_POST['mod'], $yesOrNo)){
				   $bPostID = isset($_POST['bpid']) ? $_POST['bpid'] : NULL;
				   $bpStatus = isset($_POST['mod']) ? $_POST['mod'] : NULL;
		           $updateBoostPostStatus = $iN->iN_UpdateBoosPostStatus($userID, $bPostID, $bpStatus);
			   if($updateBoostPostStatus){
	              exit('200');
			   }else{
				  exit('404');
			   }
			}
		}
		/*Boost chart data (owner only)*/
		if ($type == 'boostChartData') {
			include_once __DIR__ . '/../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			header('Content-Type: application/json; charset=utf-8');
			if (!isset($_POST['bid']) || empty($_POST['bid'])) {
				echo json_encode(['status' => 400]);
				exit();
			}
			$boostID = (int) $iN->iN_Secure($_POST['bid']);
			if ($boostID < 1) {
				echo json_encode(['status' => 400]);
				exit();
			}
			$boostRow = DB::one(
				"SELECT boost_id, iuid_fk, view_count, started_at FROM i_boosted_posts WHERE boost_id = ? LIMIT 1",
				[(int) $boostID]
			);
			if (!$boostRow || (int) ($boostRow['iuid_fk'] ?? 0) !== (int) $userID) {
				echo json_encode(['status' => 404]);
				exit();
			}
			$now = time();
			$startedAt = (int) ($boostRow['started_at'] ?? 0);
			$rangeStart = $now - (29 * 86400);
			if ($startedAt > 0 && $startedAt > $rangeStart) {
				$rangeStart = $startedAt;
			}
			$startDay = strtotime(date('Y-m-d', (int) $rangeStart));
			$endDay = strtotime(date('Y-m-d', (int) $now));
			$labels = [];
			for ($t = $startDay; $t <= $endDay; $t += 86400) {
				$labels[] = date('Y-m-d', (int) $t);
			}
			$counts = array_fill_keys($labels, 0);
			$rows = DB::all(
				"SELECT FROM_UNIXTIME(bp_seen_time, '%Y-%m-%d') AS d, COUNT(*) AS c
				 FROM i_boosted_post_seen_counter
				 WHERE bp_id_fk = ? AND bp_seen_time BETWEEN ? AND ?
				 GROUP BY d
				 ORDER BY d ASC",
				[(int) $boostID, (int) $startDay, (int) ($endDay + 86399)]
			);
			if (!empty($rows)) {
				foreach ($rows as $row) {
					$day = (string) ($row['d'] ?? '');
					if ($day !== '' && array_key_exists($day, $counts)) {
						$counts[$day] = (int) ($row['c'] ?? 0);
					}
				}
			}
			$daily = array_values($counts);
			$cumulative = [];
			$sum = 0;
			foreach ($daily as $c) {
				$sum += (int) $c;
				$cumulative[] = $sum;
			}
			echo json_encode([
				'status' => 200,
				'data' => [
					'labels' => $labels,
					'daily' => $daily,
					'cumulative' => $cumulative,
					'view_total' => (int) ($boostRow['view_count'] ?? 0),
					'total_seen' => (int) $sum,
				],
			]);
			exit();
		}
		if ($type == 'uploadPaymentSuccessImage') {
			//$availableFileExtensions
			if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
				$theValidateType = $iN->iN_Secure($_POST['c']);
				foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit($LANG['upload_failed']);
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo $LANG['error_uploading_file'] . "\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo $LANG['error_uploading_file'] . "\n";
									}
                        }else if (false && $WasStatus == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo $LANG['error_uploading_file'] . "\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo $LANG['error_uploading_file'] . "\n";
									}
								}else if($digitalOceanStatus == '1'){
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									/**/
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $getFilename;
									 }
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedScreenShotForPaymentComplete($userID, $pathFile, NULL, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo '
								<div class="i_uploaded_item in_' . $theValidateType . ' iu_f_' . $getUploadedFileID['upload_id'] . '" id="' . $getUploadedFileID['upload_id'] . '">
								' . $postTypeIcon . '
								<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
									' . $iN->iN_SelectedMenuIcon('5') . '
								</div>
								<div class="i_uploaded_file" style="background-image:url(' . $UploadSourceUrl . ');">
										<img class="i_file" src="' . $UploadSourceUrl . '" alt="' . $UploadSourceUrl . '">
								</div>
								</div>
							';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Send Account Verificatoun Request*/
	if ($type == 'verificationRequestForBankPayment') {
		if (isset($_POST['cP']) && isset($_POST['pID'])) {
			$cardIDPhoto = $iN->iN_Secure($_POST['cP']);
			$planID = $iN->iN_Secure($_POST['pID']);
			$postedPlanId = isset($_POST['plan_id']) ? (int) $iN->iN_Secure($_POST['plan_id']) : 0;
			if ($postedPlanId > 0) {
				$planID = $postedPlanId;
			}
			$paymentType = isset($_POST['payment_type']) ? strtolower((string) $iN->iN_Secure($_POST['payment_type'])) : 'point';
			if ($paymentType === 'agency_boost') {
				if (!csrf_validate_from_request()) {
					exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
				}
				$agencyId = isset($_POST['agency_id']) ? (int) $iN->iN_Secure($_POST['agency_id']) : 0;
				$creatorId = isset($_POST['creator_id']) ? (int) $iN->iN_Secure($_POST['creator_id']) : 0;
				$durationRaw = isset($_POST['duration_days']) ? trim((string) $_POST['duration_days']) : '';
				$orderKey = isset($_POST['order_id']) ? (string) $iN->iN_Secure($_POST['order_id']) : (string) $planID;
				if ($agencyId <= 0 || $creatorId <= 0) {
					exit($LANG['agency_boost_invalid']);
				}
				if (!$iN->iN_IsAgencyOwnerForAgency($agencyId, $userID)) {
					exit($LANG['noway_desc']);
				}
				$agency = DB::one("SELECT agency_status FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
				if (!$agency) {
					exit($LANG['agency_not_found']);
				}
				if (($agency['agency_status'] ?? '') !== 'active') {
					exit($LANG['agency_inactive']);
				}
				if ($iN->iN_IsAgencyMember($agencyId, $creatorId) !== true || $iN->iN_CheckUserIsCreator($creatorId) != 1) {
					exit($LANG['agency_boost_member_only']);
				}
				$durationDays = 0;
				if ($durationRaw !== '') {
					if (!preg_match('/^[0-9]+$/', $durationRaw)) {
						exit($LANG['agency_boost_invalid_duration']);
					}
					$durationDays = (int) $durationRaw;
					if ($durationDays < 1 || $durationDays > 365) {
						exit($LANG['agency_boost_invalid_duration']);
					}
				}
				$iN->iN_ExpireAgencyBoosts($agencyId);
				$now = time();
				$maxActive = (int) $iN->iN_GetSetting('agency_boost_max_active', 3);
				if ($maxActive > 0) {
					$activeCount = (int) DB::col(
						"SELECT COUNT(*) FROM i_agency_boosts WHERE agency_id_fk = ? AND status = 'active' AND end_at > ?",
						[$agencyId, $now]
					);
					if ($activeCount >= $maxActive) {
						exit($LANG['agency_boost_limit_reached']);
					}
				}
				$existingActive = DB::col(
					"SELECT 1 FROM i_agency_boosts WHERE agency_id_fk = ? AND creator_iuid_fk = ? AND status = 'active' AND end_at > ? LIMIT 1",
					[$agencyId, $creatorId, $now]
				);
				if ($existingActive) {
					exit($LANG['agency_boost_already_active']);
				}
				$agencyBoostPrice = (float) $iN->iN_GetSetting('agency_boost_price', 0.0);
				if ($agencyBoostPrice <= 0) {
					exit($LANG['agency_boost_invalid']);
				}
				if ($durationDays < 1) {
					$durationDays = (int) $iN->iN_GetSetting('agency_boost_default_days', 7);
				}
				if ($durationDays < 1) {
					$durationDays = 1;
				}
				if ($durationDays > 365) {
					$durationDays = 365;
				}
				$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
				if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
					echo 'card';
					return false;
				}
				if ($checkCardIDPhotoExist == '1') {
					$time = time();
					if ($orderKey === '') {
						$orderKey = 'AB' . uniqid();
					}
					$amountString = number_format($agencyBoostPrice, 2, '.', '');
					try {
						DB::begin();
						DB::exec("INSERT INTO i_bank_payments (iuid_fk, screen_photo, request_time) VALUES (?,?,?)", [(int) $userID, (string) $cardIDPhoto, $time]);
						DB::exec(
							"INSERT INTO i_user_payments (payer_iuid_fk, payed_iuid_fk, payed_profile_id_fk, agency_id_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, bank_payment_image, agency_boost_duration_days)
							 VALUES (?, ?, ?, ?, ?, 'agency_boost', 'bank', ?, 'pending', ?, ?, ?)",
							[
								(int) $userID,
								(int) $creatorId,
								(int) $creatorId,
								(int) $agencyId,
								(string) $orderKey,
								$time,
								$amountString,
								(int) $cardIDPhoto,
								(int) $durationDays
							]
						);
						DB::commit();
					} catch (Throwable $e) {
						DB::rollBack();
						echo 'both';
						return false;
					}
					echo '200';
				} else {
					echo 'both';
				}
				return false;
			}
			$planData = $iN->GetPlanDetails($planID);
			$planAmount = isset($planData['amount']) ? $planData['amount'] : null;
		    $planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : null;
			if (($planAmount === null || $planAmount === '') && isset($_POST['plan_amount'])) {
				$planAmount = $iN->iN_Secure($_POST['plan_amount']);
			}
			if (($planPoint === null || $planPoint === '') && isset($_POST['plan_points'])) {
				$planPoint = $iN->iN_Secure($_POST['plan_points']);
			}
			if (empty($planData)) {
				echo 'both';
				return false;
			}
			$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
			if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
				echo 'card';
				return false;
			}
			if ($checkCardIDPhotoExist == '1') {
				$InsertNewVerificationRequest = $iN->iN_InsertNewBankPaymentVerificationRequest($userID, $cardIDPhoto, $planAmount, $planPoint,$planID);
				if ($InsertNewVerificationRequest) {
					echo '200';
				}
			} else {
				echo 'both';
			}
		}
	}
	/*Purchase The Frame*/
	if($type == 'buyFrameGift'){
	   if(isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type']) && isset($_POST['pUf']) && $_POST['pUf'] != '' && !empty($_POST['pUf'])){
	       if (!csrf_validate_from_request()) {
	           exit('404');
	       }
	       $frameID = (int)$iN->iN_Secure($_POST['type']);
	       $purchaseForThisUser = (int)$iN->iN_Secure($_POST['pUf']);
	       $checFrameExist = $iN->CheckFramePlanExist($frameID);
	       if (!$checFrameExist) {
	           exit('404');
	       }
	       $frameData = $iN->GetFramePlanDetails($frameID);
	       $framePrice = isset($frameData['f_price']) ? (float)$frameData['f_price'] : 0.0;
	       $currentPointsForFrame = isset($userCurrentPoints) ? (float)$userCurrentPoints : 0.0;
	       if($framePrice <= 0 || $currentPointsForFrame >= $framePrice){
	           $insertPurchase = $iN->iN_PurchaseFrame($userID, $purchaseForThisUser, $frameID,$onePointEqual);
	           if($insertPurchase){
	               exit('200');
	           }else{
	               exit('404');
	           }
	       } else {
	       	  exit(iN_HelpSecure($base_url) . 'purchase/purchase_point');
	       }
	   }
	}
	/*Update Frame*/
	if($type == 'UpdateMyFrame'){
	    if(isset($_POST['frameID'])){
	        $frameID = $iN->iN_Secure($_POST['frameID']);
	        $updateFrame = $iN->iN_UpdateFrame($userID, $frameID);
	        if($updateFrame){
	            exit('200');
	        }else{
	            exit('400');
	        }
	    }
	}
	if ($type == 'aiBox') {
		include "../themes/$currentTheme/layouts/popup_alerts/aiBox.php";
	}
	if ($type == 'generateAiContent' && $openAiStatus == '1') {
        header('Content-Type: application/json; charset=utf-8');
        include_once __DIR__ . '/../includes/csrf.php';
        if (!csrf_validate_from_request()) {
            echo json_encode(['status' => 'error', 'code' => 'invalid_csrf', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
            exit();
        }

        $promptRaw = $_POST['uPrompt'] ?? '';
        $sourceRaw = $_POST['ai_source'] ?? '';
        $action = strtolower(trim((string)($_POST['ai_action'] ?? 'generate')));
        $template = strtolower(trim((string)($_POST['ai_template'] ?? 'caption')));
        $tone = strtolower(trim((string)($_POST['ai_tone'] ?? 'neutral')));
        $length = strtolower(trim((string)($_POST['ai_length'] ?? 'medium')));
        $language = strtolower(trim((string)($_POST['ai_language'] ?? 'auto')));
        $variants = isset($_POST['ai_variants']) ? (int)$_POST['ai_variants'] : 1;

        $allowedActions = ['generate', 'rewrite', 'shorten', 'expand'];
        if (!in_array($action, $allowedActions, true)) {
            $action = 'generate';
        }
        $allowedTemplates = ['caption', 'product', 'hashtags', 'announcement', 'bio'];
        if (!in_array($template, $allowedTemplates, true)) {
            $template = 'caption';
        }
        $allowedTones = ['neutral', 'friendly', 'professional', 'witty', 'bold', 'playful'];
        if (!in_array($tone, $allowedTones, true)) {
            $tone = 'neutral';
        }
        $allowedLengths = ['short', 'medium', 'long'];
        if (!in_array($length, $allowedLengths, true)) {
            $length = 'medium';
        }
        $allowedLanguages = ['auto', 'english', 'spanish', 'french', 'german', 'turkish', 'italian', 'portuguese'];
        if (!in_array($language, $allowedLanguages, true)) {
            $language = 'auto';
        }
        if ($variants < 1) {
            $variants = 1;
        }
        if ($variants > 3) {
            $variants = 3;
        }

        $prompt = iN_ai_clean_text($promptRaw);
        $source = iN_ai_clean_text($sourceRaw);
        $maxPromptLength = (int)$openAiPromptMaxLength;
        if ($maxPromptLength < 50) {
            $maxPromptLength = 600;
        }

        if ($action === 'generate' && $prompt === '') {
            echo json_encode(['status' => 'error', 'code' => 'prompt_required', 'message' => $LANG['ai_prompt_required'] ?? 'prompt_required']);
            exit();
        }
        if ($action !== 'generate' && $source === '') {
            echo json_encode(['status' => 'error', 'code' => 'source_required', 'message' => $LANG['ai_source_required'] ?? 'source_required']);
            exit();
        }
        if ($prompt !== '' && iN_ai_strlen($prompt) > $maxPromptLength) {
            echo json_encode(['status' => 'error', 'code' => 'prompt_too_long', 'message' => $LANG['ai_prompt_too_long'] ?? 'prompt_too_long']);
            exit();
        }
        if ($source !== '' && iN_ai_strlen($source) > $maxPromptLength) {
            echo json_encode(['status' => 'error', 'code' => 'source_too_long', 'message' => $LANG['ai_prompt_too_long'] ?? 'prompt_too_long']);
            exit();
        }

        $forbiddenTerms = iN_ai_parse_forbidden_terms($openAiForbiddenTerms);
        if (($prompt !== '' && iN_ai_contains_forbidden($prompt, $forbiddenTerms)) || ($source !== '' && iN_ai_contains_forbidden($source, $forbiddenTerms))) {
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $openAiModel,
                'temperature' => (string)$openAiTemperature,
                'max_tokens' => (int)$openAiMaxTokens,
                'variants' => $variants,
                'status' => 'blocked',
                'error_code' => 'forbidden_terms',
                'created_at' => time(),
            ]);
            echo json_encode(['status' => 'error', 'code' => 'prompt_blocked', 'message' => $LANG['ai_prompt_blocked'] ?? 'prompt_blocked']);
            exit();
        }

        $now = time();
        $minuteLimit = (int)$openAiRateLimitPerMinute;
        $hourLimit = (int)$openAiRateLimitPerHour;
        $dayLimit = (int)$openAiRateLimitPerDay;
        if ($minuteLimit > 0 && $iN->iN_GetAiUsageCountSince($userID, $now - 60) >= $minuteLimit) {
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $openAiModel,
                'temperature' => (string)$openAiTemperature,
                'max_tokens' => (int)$openAiMaxTokens,
                'variants' => $variants,
                'status' => 'rate_limited',
                'error_code' => 'rate_limit_minute',
                'created_at' => $now,
            ]);
            echo json_encode(['status' => 'error', 'code' => 'rate_limited', 'message' => $LANG['ai_rate_limited'] ?? 'rate_limited']);
            exit();
        }
        if ($hourLimit > 0 && $iN->iN_GetAiUsageCountSince($userID, $now - 3600) >= $hourLimit) {
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $openAiModel,
                'temperature' => (string)$openAiTemperature,
                'max_tokens' => (int)$openAiMaxTokens,
                'variants' => $variants,
                'status' => 'rate_limited',
                'error_code' => 'rate_limit_hour',
                'created_at' => $now,
            ]);
            echo json_encode(['status' => 'error', 'code' => 'rate_limited', 'message' => $LANG['ai_rate_limited'] ?? 'rate_limited']);
            exit();
        }
        if ($dayLimit > 0 && $iN->iN_GetAiUsageCountSince($userID, $now - 86400) >= $dayLimit) {
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $openAiModel,
                'temperature' => (string)$openAiTemperature,
                'max_tokens' => (int)$openAiMaxTokens,
                'variants' => $variants,
                'status' => 'rate_limited',
                'error_code' => 'rate_limit_day',
                'created_at' => $now,
            ]);
            echo json_encode(['status' => 'error', 'code' => 'rate_limited', 'message' => $LANG['ai_rate_limited'] ?? 'rate_limited']);
            exit();
        }

        if (empty($opanAiKey)) {
            echo json_encode(['status' => 'error', 'code' => 'invalid_api_key', 'message' => $LANG['ai_invalid_api_key'] ?? $LANG['please_check_api_key']]);
            exit();
        }

        $templateMap = [
            'caption' => 'Create a social media caption.',
            'product' => 'Write a product description with benefits and a clear call to action.',
            'hashtags' => 'Generate a bundle of 15-25 relevant hashtags only, separated by spaces.',
            'announcement' => 'Write a short announcement.',
            'bio' => 'Write a short creator bio.',
        ];
        $actionMap = [
            'generate' => 'Create new content.',
            'rewrite' => 'Rewrite the provided content while preserving meaning.',
            'shorten' => 'Shorten the provided content while keeping key points.',
            'expand' => 'Expand the provided content with more detail.',
        ];
        $toneMap = [
            'neutral' => 'Tone: neutral.',
            'friendly' => 'Tone: friendly.',
            'professional' => 'Tone: professional.',
            'witty' => 'Tone: witty.',
            'bold' => 'Tone: bold.',
            'playful' => 'Tone: playful.',
        ];
        $lengthMap = [
            'short' => 'Length: short (1-2 sentences).',
            'medium' => 'Length: medium (3-4 sentences).',
            'long' => 'Length: long (5-6 sentences).',
        ];
        $languageMap = [
            'auto' => 'Use the same language as the input.',
            'english' => 'Write in English.',
            'spanish' => 'Write in Spanish.',
            'french' => 'Write in French.',
            'german' => 'Write in German.',
            'turkish' => 'Write in Turkish.',
            'italian' => 'Write in Italian.',
            'portuguese' => 'Write in Portuguese.',
        ];

        $systemParts = [
            'You are a content creation assistant.',
            $templateMap[$template] ?? '',
            $actionMap[$action] ?? '',
            $toneMap[$tone] ?? '',
            $lengthMap[$length] ?? '',
            $languageMap[$language] ?? '',
            'Return only the requested content without labels.',
        ];
        $systemPrompt = trim(implode(' ', array_filter($systemParts)));
        $userPrompt = '';
        if ($action === 'generate') {
            $userPrompt = $prompt;
        } else {
            $userPrompt = "Content:\n" . $source;
            if ($prompt !== '') {
                $userPrompt .= "\n\nInstructions:\n" . $prompt;
            }
        }

        $temperature = (float)$openAiTemperature;
        if ($temperature < 0) {
            $temperature = 0.0;
        }
        if ($temperature > 2) {
            $temperature = 2.0;
        }
        $maxTokens = (int)$openAiMaxTokens;
        if ($maxTokens < 1) {
            $maxTokens = 1;
        }
        if ($maxTokens > 4096) {
            $maxTokens = 4096;
        }
        $payload = [
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'n' => $variants,
        ];

        $result = callOpenAI($payload, (string)$opanAiKey, (string)$openAiModel, (string)$openAiFallbackModel);
        if (!$result['ok']) {
            $errorCode = $result['error']['code'] ?? 'unknown_error';
            $errorMessage = $result['error']['message'] ?? 'OpenAI request failed.';
            $iN->iN_UpdateAiLastError($errorMessage);
            error_log('[AI] error code=' . $errorCode . ' model=' . ($result['model'] ?? 'unknown') . ' user=' . (int)$userID);
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $result['model'] ?? $openAiModel,
                'temperature' => (string)$temperature,
                'max_tokens' => $maxTokens,
                'variants' => $variants,
                'status' => 'error',
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'created_at' => $now,
            ]);
            $errorMessageMap = [
                'invalid_api_key' => $LANG['ai_invalid_api_key'] ?? $LANG['please_check_api_key'],
                'rate_limited' => $LANG['ai_rate_limited'] ?? 'rate_limited',
                'server_error' => $LANG['ai_server_error'] ?? 'server_error',
                'timeout' => $LANG['ai_timeout'] ?? 'timeout',
                'model_not_found' => $LANG['ai_model_not_found'] ?? 'model_not_found',
                'bad_request' => $LANG['ai_request_failed'] ?? 'request_failed',
            ];
            $mappedMessage = $errorMessageMap[$errorCode] ?? ($LANG['ai_request_failed'] ?? $errorMessage);
            echo json_encode(['status' => 'error', 'code' => $errorCode, 'message' => $mappedMessage]);
            exit();
        }

        $choices = $result['choices'] ?? [];
        $items = [];
        foreach ($choices as $choiceText) {
            $cleaned = iN_ai_clean_output($choiceText);
            if ($cleaned === '') {
                continue;
            }
            if (iN_ai_contains_forbidden($cleaned, $forbiddenTerms)) {
                $iN->iN_LogAiUsage($userID, [
                    'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                    'model' => $result['model'] ?? $openAiModel,
                    'temperature' => (string)$temperature,
                    'max_tokens' => $maxTokens,
                    'variants' => $variants,
                    'status' => 'blocked',
                    'error_code' => 'forbidden_terms_output',
                    'created_at' => $now,
                ]);
                echo json_encode(['status' => 'error', 'code' => 'output_blocked', 'message' => $LANG['ai_output_blocked'] ?? 'output_blocked']);
                exit();
            }
            $items[] = ['text' => $cleaned];
        }

        if (empty($items)) {
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $result['model'] ?? $openAiModel,
                'temperature' => (string)$temperature,
                'max_tokens' => $maxTokens,
                'variants' => $variants,
                'status' => 'error',
                'error_code' => 'empty_response',
                'created_at' => $now,
            ]);
            echo json_encode(['status' => 'error', 'code' => 'empty_response', 'message' => $LANG['ai_request_failed'] ?? 'request_failed']);
            exit();
        }

        $creditCost = (int)$perAiUse;
        $walletDone = $iN->iN_AiUsed($userID, $creditCost);
        if (!$walletDone) {
            $iN->iN_LogAiUsage($userID, [
                'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
                'model' => $result['model'] ?? $openAiModel,
                'temperature' => (string)$temperature,
                'max_tokens' => $maxTokens,
                'variants' => $variants,
                'status' => 'error',
                'error_code' => 'no_enough_credit',
                'created_at' => $now,
            ]);
            echo json_encode(['status' => 'error', 'code' => 'no_enough_credit', 'message' => $LANG['no_enough_credit'] ?? 'no_enough_credit']);
            exit();
        }

        $tokensUsed = isset($result['usage']['total_tokens']) ? (int)$result['usage']['total_tokens'] : 0;
        $iN->iN_LogAiUsage($userID, [
            'prompt_length' => iN_ai_strlen($prompt !== '' ? $prompt : $source),
            'model' => $result['model'] ?? $openAiModel,
            'temperature' => (string)$temperature,
            'max_tokens' => $maxTokens,
            'variants' => $variants,
            'tokens_used' => $tokensUsed,
            'credit_used' => $creditCost,
            'status' => 'success',
            'created_at' => $now,
        ]);

        echo json_encode([
            'status' => 'success',
            'items' => $items,
        ]);
        exit();
    }
	if ($type == 'getReelsComment') {
		if (isset($_POST['id'])) {
			$userPostID = $iN->iN_Secure($_POST['id']);
			$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
			if ($getUserComments) {
				foreach ($getUserComments as $comment) {
					$commentID = $comment['com_id'];
					$commentedUserID = $comment['comment_uid_fk'];
					$Usercomment = $comment['comment'];
					$commentTime = isset($comment['comment_time']) ? $comment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($comment['comment_file']) ? $comment['comment_file'] : NULL;
					$stickerUrl = isset($comment['sticker_url']) ? $comment['sticker_url'] : NULL;
					$gifUrl = isset($comment['gif_url']) ? $comment['gif_url'] : NULL;
					$commentedUserIDFk = isset($comment['iuid']) ? $comment['iuid'] : NULL;
					$commentedUserName = isset($comment['i_username']) ? $comment['i_username'] : NULL;
					$commentedUserFullName = isset($comment['i_user_fullname']) ? $comment['i_user_fullname'] : NULL;
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($comment['user_gender']) ? $comment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($comment['last_login_time']) ? $comment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($comment['user_verified_status']) ? $comment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$commentParentId = (int)($comment['parent_comment_id'] ?? 0);
					$commentReplies = $iN->iN_GetCommentReplies($commentID);
					$commentReplyCount = $commentReplies ? count($commentReplies) : 0;
					$isReply = false;
					$replyParentUserName = '';
					$replyParentUserFullName = '';
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($logedIn != 0) {
						$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
						$checkCommentReportedBefore = $iN->iN_CheckCommentReportedBefore($userID, $commentID);
						if ($checkCommentLikedBefore == '1') {
							$commentLikeBtnClass = 'c_in_unlike';
							$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						}
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
						$cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					$commentLinkUrl = $comment['link_url'] ?? '';
					$commentLinkDomain = $comment['link_domain'] ?? '';
					$commentLinkTitle = $comment['link_title'] ?? '';
					$commentLinkDescription = $comment['link_description'] ?? '';
					$commentLinkImage = $comment['link_image'] ?? '';
					include "../themes/$currentTheme/layouts/posts/comments.php";
				}
			} else {
				echo '<div class="no_comments_msg">' . iN_HelpSecure($LANG['no_comments_yet']) . '</div>';
			}
		} else {
			echo '<div class="no_comments_msg">' . iN_HelpSecure($LANG['comments_unavailable']) . '</div>';
		}
	}
		if ($type === 'creatorAgencyCreate') {
			$canAccessAgencyOnboarding = $iN->iN_CanUserAccessAgencyOnboarding(
				$userID,
				$userSignupIntent ?? 'user',
				$registrationRoleMode ?? 'legacy'
			);
			if (!$canAccessAgencyOnboarding) {
				exit($LANG['noway_desc']);
			}
			if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		if ($iN->iN_IsAgencyOwner($userID)) {
			exit($LANG['agency_create_not_allowed']);
		}
		$alreadyMember = DB::col("SELECT 1 FROM i_agency_members WHERE creator_iuid_fk = ? LIMIT 1", [$userID]);
		if ($alreadyMember) {
			exit($LANG['agency_create_not_allowed']);
		}
		$pendingCreate = DB::col(
			"SELECT 1 FROM i_agency_create_requests WHERE owner_iuid_fk = ? AND status = 'pending' LIMIT 1",
			[$userID]
		);
		if ($pendingCreate) {
			exit($LANG['agency_create_pending']);
		}
		$agencyName = trim($iN->iN_Secure($_POST['agency_name'] ?? ''));
		if ($agencyName === '' || strlen($agencyName) > 255) {
			exit($LANG['agency_invalid_name']);
		}
		$agencyFeeRaw = $iN->iN_Secure($_POST['agency_fee'] ?? '');
		$agencyFee = $agencyFeeRaw !== '' ? (float)$agencyFeeRaw : 0.0;
		if ($agencyFee < 0 || $agencyFee > 100) {
			exit($LANG['agency_fee_invalid']);
		}
		$time = time();
		DB::exec(
			"INSERT INTO i_agency_create_requests (owner_iuid_fk, agency_name, agency_fee, status, created_at, updated_at) VALUES (?,?,?,?,?,?)",
			[(int)$userID, $agencyName, number_format($agencyFee, 2, '.', ''), 'pending', $time, $time]
		);
		exit('200');
	}
	if ($type === 'creatorAgencyRequest') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$pendingCreate = DB::col(
			"SELECT 1 FROM i_agency_create_requests WHERE owner_iuid_fk = ? AND status = 'pending' LIMIT 1",
			[$userID]
		);
		if ($pendingCreate) {
			exit($LANG['agency_create_pending']);
		}
		if ($iN->iN_IsAgencyOwner($userID)) {
			exit($LANG['agency_already_member']);
		}
		$agencyId = isset($_POST['agency_id']) ? (int)$iN->iN_Secure($_POST['agency_id']) : 0;
		if ($agencyId <= 0) {
			exit($LANG['agency_not_found']);
		}
		$agency = DB::one("SELECT agency_id, agency_status, owner_iuid_fk FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
		if (!$agency) {
			exit($LANG['agency_not_found']);
		}
		if (($agency['agency_status'] ?? '') !== 'active') {
			exit($LANG['agency_inactive']);
		}
		$agencyOwnerId = (int)($agency['owner_iuid_fk'] ?? 0);
		$alreadyMember = DB::col("SELECT 1 FROM i_agency_members WHERE creator_iuid_fk = ? LIMIT 1", [$userID]);
		if ($alreadyMember) {
			exit($LANG['agency_already_member']);
		}
		$pending = DB::col(
			"SELECT 1 FROM i_agency_requests WHERE agency_id_fk = ? AND creator_iuid_fk = ? AND status = 'pending' LIMIT 1",
			[$agencyId, $userID]
		);
		if ($pending) {
			exit($LANG['agency_request_pending']);
		}
		$recentRequest = DB::col(
			"SELECT 1 FROM i_agency_requests WHERE creator_iuid_fk = ? AND created_at >= ? LIMIT 1",
			[$userID, time() - 60]
		);
		if ($recentRequest) {
			exit($LANG['agency_request_throttle']);
		}
		$requestId = $iN->iN_CreateAgencyRequest($agencyId, $userID, 'creator');
		if (!$requestId) {
			exit($LANG['agency_request_failed']);
		}
		if ($agencyOwnerId > 0 && $agencyOwnerId !== (int)$userID) {
			$iN->iN_InsertNotificationForAgencyRequest($userID, $agencyOwnerId);
		}
		exit('200');
	}
	if ($type === 'creatorAgencyRequestStatus') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$pendingCreate = DB::col(
			"SELECT 1 FROM i_agency_create_requests WHERE owner_iuid_fk = ? AND status = 'pending' LIMIT 1",
			[$userID]
		);
		if ($pendingCreate) {
			exit($LANG['agency_create_pending']);
		}
		if ($iN->iN_IsAgencyOwner($userID)) {
			exit($LANG['agency_already_member']);
		}
		$requestId = isset($_POST['request_id']) ? (int)$iN->iN_Secure($_POST['request_id']) : 0;
		$status = $iN->iN_Secure($_POST['status'] ?? '');
		if ($requestId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
			exit($LANG['agency_request_invalid']);
		}
		$requestRow = DB::one(
			"SELECT * FROM i_agency_requests WHERE ar_id = ? AND creator_iuid_fk = ? AND requested_by = 'agency' LIMIT 1",
			[$requestId, $userID]
		);
		if (!$requestRow) {
			exit($LANG['agency_request_not_found']);
		}
		if (($requestRow['status'] ?? '') !== 'pending') {
			exit($LANG['agency_request_invalid']);
		}
		$agency = DB::one("SELECT agency_id, agency_status FROM i_agencies WHERE agency_id = ? LIMIT 1", [(int)$requestRow['agency_id_fk']]);
		if (!$agency) {
			exit($LANG['agency_not_found']);
		}
		if ($status === 'approved') {
			if (($agency['agency_status'] ?? '') !== 'active') {
				exit($LANG['agency_inactive']);
			}
			$alreadyMember = DB::col("SELECT 1 FROM i_agency_members WHERE creator_iuid_fk = ? LIMIT 1", [$userID]);
			if ($alreadyMember) {
				exit($LANG['agency_already_member']);
			}
			$added = $iN->iN_AddCreatorToAgency((int)$requestRow['agency_id_fk'], (int)$requestRow['creator_iuid_fk']);
			if (!$added) {
				exit($LANG['agency_member_add_failed']);
			}
		}
		$updated = $iN->iN_UpdateAgencyRequestStatus($requestId, $status);
		if (!$updated) {
			exit($LANG['agency_request_failed']);
		}
		exit('200');
	}
	if ($type === 'agencyOwnerRequestStatus') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$requestId = isset($_POST['request_id']) ? (int)$iN->iN_Secure($_POST['request_id']) : 0;
		$status = $iN->iN_Secure($_POST['status'] ?? '');
		if ($requestId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
			exit($LANG['agency_request_invalid']);
		}
		$requestRow = DB::one(
			"SELECT R.*, A.agency_status, A.owner_iuid_fk FROM i_agency_requests R
			 INNER JOIN i_agencies A ON A.agency_id = R.agency_id_fk
			 WHERE R.ar_id = ? AND R.requested_by = 'creator' LIMIT 1",
			[$requestId]
		);
		if (!$requestRow) {
			exit($LANG['agency_request_not_found']);
		}
		if ((int)($requestRow['owner_iuid_fk'] ?? 0) !== (int)$userID) {
			exit($LANG['noway_desc']);
		}
		if (($requestRow['status'] ?? '') !== 'pending') {
			exit($LANG['agency_request_invalid']);
		}
		if ($status === 'approved') {
			if (($requestRow['agency_status'] ?? '') !== 'active') {
				exit($LANG['agency_inactive']);
			}
			$alreadyMember = DB::col("SELECT 1 FROM i_agency_members WHERE creator_iuid_fk = ? LIMIT 1", [(int)$requestRow['creator_iuid_fk']]);
			if ($alreadyMember) {
				exit($LANG['agency_already_member']);
			}
			$added = $iN->iN_AddCreatorToAgency((int)$requestRow['agency_id_fk'], (int)$requestRow['creator_iuid_fk']);
			if (!$added) {
				exit($LANG['agency_member_add_failed']);
			}
		}
		$updated = $iN->iN_UpdateAgencyRequestStatus($requestId, $status);
		if (!$updated) {
			exit($LANG['agency_request_failed']);
		}
		exit('200');
	}
	if ($type === 'agencyProfileUpdate') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$agencyId = isset($_POST['agency_id']) ? (int)$iN->iN_Secure($_POST['agency_id']) : 0;
		if ($agencyId <= 0) {
			exit($LANG['agency_not_found']);
		}
		$agency = DB::one("SELECT * FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
		if (!$agency) {
			exit($LANG['agency_not_found']);
		}
		if ((int)($agency['owner_iuid_fk'] ?? 0) !== (int)$userID) {
			exit($LANG['noway_desc']);
		}
		$agencyProfileAboutEnabled = (isset($agencyProfileAboutStatus) ? $agencyProfileAboutStatus : 'yes') === 'yes';
		$agencyProfileServicesEnabled = (isset($agencyProfileServicesStatus) ? $agencyProfileServicesStatus : 'yes') === 'yes';
		$agencyProfileLogoEnabled = (isset($agencyProfileLogoStatus) ? $agencyProfileLogoStatus : 'yes') === 'yes';
		$agencyProfileCoverEnabled = (isset($agencyProfileCoverStatus) ? $agencyProfileCoverStatus : 'yes') === 'yes';
		$agencyProfileSocialEnabled = (isset($agencyProfileSocialStatus) ? $agencyProfileSocialStatus : 'yes') === 'yes';
		$updateFields = [];
		if ($agencyProfileAboutEnabled) {
			$agencyAbout = trim($iN->iN_Secure($_POST['agency_about'] ?? ''));
			if ($agencyAbout !== '' && mb_strlen(strip_tags($agencyAbout), 'UTF-8') > 2000) {
				exit($LANG['agency_about_too_long']);
			}
			$updateFields['agency_about'] = $agencyAbout !== '' ? $agencyAbout : null;
		}
		if ($agencyProfileServicesEnabled) {
			$agencyServices = trim($iN->iN_Secure($_POST['agency_services'] ?? ''));
			if ($agencyServices !== '' && mb_strlen(strip_tags($agencyServices), 'UTF-8') > 2000) {
				exit($LANG['agency_services_too_long']);
			}
			$updateFields['agency_services'] = $agencyServices !== '' ? $agencyServices : null;
		}
		$logoPath = null;
		if ($agencyProfileLogoEnabled && isset($_FILES['agency_logo']['name']) && $_FILES['agency_logo']['name'] !== '') {
			$logoName = $_FILES['agency_logo']['name'];
			$logoTmp = $_FILES['agency_logo']['tmp_name'];
			$logoSize = isset($_FILES['agency_logo']['size']) ? (int)$_FILES['agency_logo']['size'] : 0;
			$ext = strtolower(getExtension($logoName));
			$allowedLogoExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			if (!in_array($ext, $allowedLogoExt, true)) {
				exit($LANG['invalid_file_format'] ?? 'invalid_file_format');
			}
			if (function_exists('convert_to_mb') && convert_to_mb($logoSize) > $availableUploadFileSize) {
				exit($LANG['file_is_too_large'] ?? 'file_is_too_large');
			}
			$uploadAgency = rtrim($uploadRoot, '/') . '/agencies/';
			$todayDir = date('Y-m-d');
			$uploadDir = $uploadAgency . $todayDir . '/';
			if (!is_dir($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$fileBase = 'agency_logo_' . $removeMicrotime . '_' . $agencyId;
			$filename = $fileBase . '.' . $ext;
			$uploadedPath = $uploadDir . $filename;
			if (!move_uploaded_file($logoTmp, $uploadedPath)) {
				exit($LANG['upload_failed'] ?? 'upload_failed');
			}
			$logoPath = 'uploads/agencies/' . $todayDir . '/' . $filename;
			if (function_exists('storage_publish_and_url')) {
				storage_publish_and_url($logoPath, [$logoPath], true);
			}
		}
		$coverPath = null;
		if ($agencyProfileCoverEnabled && isset($_FILES['agency_cover']['name']) && $_FILES['agency_cover']['name'] !== '') {
			$coverName = $_FILES['agency_cover']['name'];
			$coverTmp = $_FILES['agency_cover']['tmp_name'];
			$coverSize = isset($_FILES['agency_cover']['size']) ? (int)$_FILES['agency_cover']['size'] : 0;
			$ext = strtolower(getExtension($coverName));
			$allowedCoverExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			if (!in_array($ext, $allowedCoverExt, true)) {
				exit($LANG['invalid_file_format'] ?? 'invalid_file_format');
			}
			if (function_exists('convert_to_mb') && convert_to_mb($coverSize) > $availableUploadFileSize) {
				exit($LANG['file_is_too_large'] ?? 'file_is_too_large');
			}
			$uploadAgency = rtrim($uploadRoot, '/') . '/agencies/';
			$todayDir = date('Y-m-d');
			$uploadDir = $uploadAgency . $todayDir . '/';
			if (!is_dir($uploadDir)) {
				@mkdir($uploadDir, 0755, true);
			}
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$fileBase = 'agency_cover_' . $removeMicrotime . '_' . $agencyId;
			$filename = $fileBase . '.' . $ext;
			$uploadedPath = $uploadDir . $filename;
			if (!move_uploaded_file($coverTmp, $uploadedPath)) {
				exit($LANG['upload_failed'] ?? 'upload_failed');
			}
			$coverPath = 'uploads/agencies/' . $todayDir . '/' . $filename;
			if (function_exists('storage_publish_and_url')) {
				storage_publish_and_url($coverPath, [$coverPath], true);
			}
		}
		if ($logoPath !== null) {
			$updateFields['agency_logo'] = $logoPath;
		}
		if ($coverPath !== null) {
			$updateFields['agency_cover'] = $coverPath;
		}
		if (!empty($updateFields)) {
			$setParts = [];
			$params = [];
			foreach ($updateFields as $col => $val) {
				$setParts[] = $col . ' = ?';
				$params[] = $val;
			}
			$params[] = $agencyId;
			DB::exec("UPDATE i_agencies SET " . implode(',', $setParts) . " WHERE agency_id = ?", $params);
		}
		if ($agencyProfileSocialEnabled) {
			$socialLinks = isset($_POST['social_link']) && is_array($_POST['social_link']) ? $_POST['social_link'] : [];
			$cleanLinks = [];
			foreach ($socialLinks as $networkId => $linkRaw) {
				$link = trim($iN->iN_Secure($linkRaw));
				if ($link !== '' && !$iN->iN_IsUrl($link)) {
					exit($LANG['agency_invalid_social_link']);
				}
				$cleanLinks[$networkId] = $link;
			}
			if (!empty($cleanLinks)) {
				$iN->iN_SaveAgencySocialLinks($agencyId, $cleanLinks);
			}
		}
		exit('200');
	}
	if ($type === 'agencyBoostCreate') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$paymentMethod = isset($_POST['payment_method']) ? $iN->iN_Secure($_POST['payment_method']) : 'points';
		if ($paymentMethod !== 'points') {
			exit($LANG['agency_boost_invalid']);
		}
		$agencyId = isset($_POST['agency_id']) ? (int)$iN->iN_Secure($_POST['agency_id']) : 0;
		$creatorId = isset($_POST['creator_id']) ? (int)$iN->iN_Secure($_POST['creator_id']) : 0;
		$durationRaw = isset($_POST['duration_days']) ? trim((string)$_POST['duration_days']) : '';
		if ($agencyId <= 0 || $creatorId <= 0) {
			exit($LANG['agency_boost_invalid']);
		}
		if (!$iN->iN_IsAgencyOwnerForAgency($agencyId, $userID)) {
			exit($LANG['noway_desc']);
		}
		$agency = DB::one("SELECT agency_status FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
		if (!$agency) {
			exit($LANG['agency_not_found']);
		}
		if (($agency['agency_status'] ?? '') !== 'active') {
			exit($LANG['agency_inactive']);
		}
		if ($iN->iN_IsAgencyMember($agencyId, $creatorId) !== true || $iN->iN_CheckUserIsCreator($creatorId) != 1) {
			exit($LANG['agency_boost_member_only']);
		}
		$durationDays = 0;
		if ($durationRaw !== '') {
			if (!preg_match('/^[0-9]+$/', $durationRaw)) {
				exit($LANG['agency_boost_invalid_duration']);
			}
			$durationDays = (int)$durationRaw;
			if ($durationDays < 1 || $durationDays > 365) {
				exit($LANG['agency_boost_invalid_duration']);
			}
		}
		$iN->iN_ExpireAgencyBoosts($agencyId);
		$now = time();
		$maxActive = (int)$iN->iN_GetSetting('agency_boost_max_active', 3);
		if ($maxActive > 0) {
			$activeCount = (int) DB::col(
				"SELECT COUNT(*) FROM i_agency_boosts WHERE agency_id_fk = ? AND status = 'active' AND end_at > ?",
				[$agencyId, $now]
			);
			if ($activeCount >= $maxActive) {
				exit($LANG['agency_boost_limit_reached']);
			}
		}
		$existingActive = DB::col(
			"SELECT 1 FROM i_agency_boosts WHERE agency_id_fk = ? AND creator_iuid_fk = ? AND status = 'active' AND end_at > ? LIMIT 1",
			[$agencyId, $creatorId, $now]
		);
		if ($existingActive) {
			exit($LANG['agency_boost_already_active']);
		}
		$pointsCost = (int)$iN->iN_GetSetting('agency_boost_point_price', 0);
		if ($pointsCost < 0) {
			$pointsCost = 0;
		}
		$walletUpdated = true;
		$paymentId = 0;
		$orderKey = 'AGB' . uniqid();
		$amount = (float)$pointsCost * (float)$onePointEqual;
		$amountString = number_format($amount, 2, '.', '');

		try {
			$column = DB::one("SHOW COLUMNS FROM i_user_payments LIKE 'payment_type'");
			if (!empty($column['Type']) && strpos((string) $column['Type'], "'agency_boost'") === false) {
				preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", (string) $column['Type'], $matches);
				$options = $matches[1] ?? [];
				$options[] = 'agency_boost';
				$options = array_values(array_unique($options));
				$escapedOptions = array_map(static function ($opt) {
					return str_replace("'", "\\'", $opt);
				}, $options);
				$enumList = "'" . implode("','", $escapedOptions) . "'";
				DB::exec("ALTER TABLE i_user_payments MODIFY `payment_type` enum($enumList) COLLATE utf8mb4_general_ci NOT NULL");
			}
		} catch (Throwable $e) {
			// Ignore enum update failures; insert will surface the error if unsupported.
		}

		try {
			DB::begin();
			if ($pointsCost > 0) {
				$walletUpdated = DB::exec(
					"UPDATE i_users SET wallet_points = wallet_points - ? WHERE iuid = ? AND wallet_points >= ?",
					[(string)$pointsCost, (int)$userID, (string)$pointsCost]
				) === 1;
			}
			if (!$walletUpdated) {
				DB::rollBack();
				exit($LANG['insufficient_balance'] ?? $LANG['agency_boost_invalid']);
			}

			DB::exec(
				"INSERT INTO i_user_payments(payer_iuid_fk, payed_iuid_fk, payed_profile_id_fk, agency_id_fk, order_key, payment_type, payment_option, payment_time, payment_status, amount, admin_earning, agency_earning, user_earning, fee, agency_fee, agency_boost_duration_days)
				 VALUES(?, ?, ?, ?, ?, 'agency_boost', 'wallet', ?, 'ok', ?, ?, '0', '0', '0', '0', ?)",
				[
					(int)$userID,
					(int)$creatorId,
					(int)$creatorId,
					(int)$agencyId,
					$orderKey,
					time(),
					$amountString,
					$amountString,
					(int)$durationDays
				]
			);
			$paymentId = (int) DB::lastId();

			$boostId = $iN->iN_CreateAgencyBoost($agencyId, $creatorId, $userID, $durationDays, false);
			if (!$boostId) {
				DB::rollBack();
				exit($LANG['agency_boost_create_failed']);
			}
			DB::commit();
		} catch (Throwable $e) {
			DB::rollBack();
			exit($LANG['agency_boost_create_failed']);
		}

		if ($paymentId > 0) {
			$currency = $GLOBALS['defaultCurrency'] ?? 'USD';
			$iN->iN_AssignInvoiceToPayment($paymentId, $userID, (float)$amountString, $currency);
		}
		exit('200');
	}
	if ($type === 'agencyBoostPaymentMethods') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$agencyId = isset($_POST['agency_id']) ? (int)$iN->iN_Secure($_POST['agency_id']) : 0;
		$creatorId = isset($_POST['creator_id']) ? (int)$iN->iN_Secure($_POST['creator_id']) : 0;
		$durationRaw = isset($_POST['duration_days']) ? trim((string)$_POST['duration_days']) : '';
		if ($agencyId <= 0 || $creatorId <= 0) {
			exit($LANG['agency_boost_invalid']);
		}
		if (!$iN->iN_IsAgencyOwnerForAgency($agencyId, $userID)) {
			exit($LANG['noway_desc']);
		}
		$agency = DB::one("SELECT agency_status FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
		if (!$agency) {
			exit($LANG['agency_not_found']);
		}
		if (($agency['agency_status'] ?? '') !== 'active') {
			exit($LANG['agency_inactive']);
		}
		if ($iN->iN_IsAgencyMember($agencyId, $creatorId) !== true || $iN->iN_CheckUserIsCreator($creatorId) != 1) {
			exit($LANG['agency_boost_member_only']);
		}
		$durationDays = 0;
		if ($durationRaw !== '') {
			if (!preg_match('/^[0-9]+$/', $durationRaw)) {
				exit($LANG['agency_boost_invalid_duration']);
			}
			$durationDays = (int)$durationRaw;
			if ($durationDays < 1 || $durationDays > 365) {
				exit($LANG['agency_boost_invalid_duration']);
			}
		}
		$iN->iN_ExpireAgencyBoosts($agencyId);
		$now = time();
		$maxActive = (int)$iN->iN_GetSetting('agency_boost_max_active', 3);
		if ($maxActive > 0) {
			$activeCount = (int) DB::col(
				"SELECT COUNT(*) FROM i_agency_boosts WHERE agency_id_fk = ? AND status = 'active' AND end_at > ?",
				[$agencyId, $now]
			);
			if ($activeCount >= $maxActive) {
				exit($LANG['agency_boost_limit_reached']);
			}
		}
		$existingActive = DB::col(
			"SELECT 1 FROM i_agency_boosts WHERE agency_id_fk = ? AND creator_iuid_fk = ? AND status = 'active' AND end_at > ? LIMIT 1",
			[$agencyId, $creatorId, $now]
		);
		if ($existingActive) {
			exit($LANG['agency_boost_already_active']);
		}

		$agencyBoostPrice = (float)$iN->iN_GetSetting('agency_boost_price', 0.0);
		if ($agencyBoostPrice <= 0) {
			exit($LANG['agency_boost_invalid']);
		}

		require_once '../includes/payment/vendor/autoload.php';
		if (!defined('INORA_METHODS_CONFIG')) {
			define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
		}
		require_once INORA_METHODS_CONFIG;
		$configData = configItem();

		$planAmount = $agencyBoostPrice;
		if ($stripePaymentCurrency == 'JPY') {
			$planAmount = round($planAmount);
		} else {
			$planAmount = (float) number_format($planAmount, 2, '.', '');
		}

		$orderKey = 'AB' . uniqid();
		$planID = $orderKey;
		$paymentType = 'agency_boost';
		$DataUserDetails = [
			'amounts' => [
				$payPalCurrency => $planAmount,
				$iyziCoPaymentCurrency => $planAmount,
				$bitPayPaymentCurrency => $planAmount,
				$autHorizePaymentCurrency => $planAmount,
				$payStackPaymentCurrency => $planAmount,
				$stripePaymentCurrency => $planAmount,
				$razorPayPaymentCurrency => $planAmount,
				$mercadoPagoCurrency => $planAmount,
				$paysafecardCurrency => $planAmount,
				$flutterWaveCurrency => $planAmount
			],
			'order_id' => $orderKey,
			'customer_id' => 'CUSTOMER' . uniqid(),
			'item_name' => $LANG['agency_boost_label'] ?? 'Agency Boost',
			'item_qty' => 1,
			'item_id' => 'ITEM' . uniqid(),
			'payer_email' => $userEmail,
			'payer_name' => $userFullName,
			'description' => $LANG['agency_boost_label'] ?? 'Agency Boost',
			'ip_address' => getUserIpAddr(),
			'address' => '3234 Godfrey Street Tigard, OR 97223',
			'city' => 'Tigard',
			'country' => 'United States',
			'payment_type' => 'agency_boost',
			'payer_id' => (int) $userID,
			'payed_user_id' => (int) $creatorId,
			'order_amount' => $planAmount,
			'agency_id' => (int) $agencyId,
			'creator_id' => (int) $creatorId,
			'payed_profile_id' => (int) $creatorId,
			'duration_days' => (int) $durationDays
		];
		$PublicConfigs = getPublicConfigItem();

		$configItem = $configData['payments']['gateway_configuration'];
		$paymentPagePath = getAppUrl();

		$gatewayConfiguration = $configData['payments']['gateway_configuration'];
		$paystackConfigData = $gatewayConfiguration['paystack'];
		$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

		$stripeConfigData = $gatewayConfiguration['stripe'];
		$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

		$razorpayConfigData = $gatewayConfiguration['razorpay'];
		$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

		$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
		$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

		$flutterwaveConfigData = $gatewayConfiguration['flutterwave'];
		$flutterwaveCallbackUrl = getAppUrl($flutterwaveConfigData['callbackUrl']);

		$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
		include "../themes/$currentTheme/layouts/popup_alerts/paymentMethods.php";
	}
	if ($type === 'agencyBoostDisable') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$agencyModuleEnabled) {
			exit($LANG['agency_module_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$boostId = isset($_POST['boost_id']) ? (int)$iN->iN_Secure($_POST['boost_id']) : 0;
		if ($boostId <= 0) {
			exit($LANG['agency_boost_invalid']);
		}
		$disabled = $iN->iN_DisableAgencyBoost($boostId, $userID, false);
		if (!$disabled) {
			exit($LANG['agency_boost_disable_failed']);
		}
		exit('200');
	}
	if ($type === 'creatorBulkCreateCampaign') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$iN->iN_IsCreatorBulkEnabled()) {
			exit($LANG['creator_bulk_feature_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		if (!$iN->iN_CanCreatorCreateCampaignToday($userID)) {
			exit($LANG['creator_bulk_daily_limit_reached']);
		}
		$targetType = $iN->iN_Secure($_POST['target_type'] ?? '');
		$messageText = trim($iN->iN_Secure($_POST['message_text'] ?? ''));
		$privateStatus = isset($_POST['private_status']) ? (int)$iN->iN_Secure($_POST['private_status']) : 0;
		$privatePriceRaw = isset($_POST['private_price']) ? $iN->iN_Secure($_POST['private_price']) : '';
		$privatePrice = $privatePriceRaw !== '' ? (float)$privatePriceRaw : null;
		$bulkPriceMin = isset($bulkMessagePrivatePriceMin) ? (float)$bulkMessagePrivatePriceMin : 0.0;
		$bulkPriceMax = isset($bulkMessagePrivatePriceMax) ? (float)$bulkMessagePrivatePriceMax : 0.0;
		if ($bulkPriceMin < 0) { $bulkPriceMin = 0.0; }
		if ($bulkPriceMax < 0) { $bulkPriceMax = 0.0; }
		if ($bulkPriceMax > 0 && $bulkPriceMax < $bulkPriceMin) { $bulkPriceMax = $bulkPriceMin; }
		if ($messageText === '') {
			exit($LANG['bulk_message_required']);
		}
		if (!in_array($targetType, ['followers_of_creator', 'subscribers_of_creator'], true)) {
			exit($LANG['bulk_invalid_target']);
		}
		if ($privateStatus === 1) {
			if ($privatePrice === null || $privatePrice <= 0) {
				exit($LANG['bulk_invalid_private_price']);
			}
			if ($bulkPriceMin > 0 && $privatePrice < $bulkPriceMin) {
				$msg = str_replace('{min}', number_format($bulkPriceMin, 2, '.', ''), $LANG['bulk_private_price_min_error']);
				exit($msg);
			}
			if ($bulkPriceMax > 0 && $privatePrice > $bulkPriceMax) {
				$msg = str_replace('{max}', number_format($bulkPriceMax, 2, '.', ''), $LANG['bulk_private_price_max_error']);
				exit($msg);
			}
		} else {
			$privatePrice = null;
		}
		$attachmentPath = null;
		if (isset($_FILES['bulk_attachment']) && !empty($_FILES['bulk_attachment']['name'])) {
			$bulkExtensionsRaw = $availableBulkMessageFileExtensions ?? $availableFileExtensions ?? '';
			$bulkExtensions = array_values(array_filter(array_map('trim', explode(',', (string)$bulkExtensionsRaw))));
			$thumbnailFile = isset($_FILES['bulk_video_thumbnail']) ? $_FILES['bulk_video_thumbnail'] : null;
			[$uploadError, $attachmentPath] = iN_creator_handle_bulk_attachment_upload(
				$_FILES['bulk_attachment'],
				$userID,
				(int)$availableUploadFileSize,
				(string)$uploadFile,
				$bulkExtensions,
				$ffmpegCanConvert,
				(string)$ffmpegPath,
				$thumbnailFile
			);
			if ($uploadError === 'invalid_format') {
				exit($LANG['invalid_file_format']);
			}
			if ($uploadError === 'unsupported_video_format') {
				exit($LANG['unsupported_video_format']);
			}
			if ($uploadError === 'file_is_too_large') {
				exit($LANG['file_is_too_large']);
			}
			if ($uploadError === 'thumbnail_required') {
				exit($LANG['bulk_thumbnail_required']);
			}
			if ($uploadError === 'thumbnail_invalid') {
				exit($LANG['bulk_thumbnail_invalid']);
			}
			if ($uploadError !== null || !$attachmentPath) {
				exit($LANG['upload_failed']);
			}
		}
		$data = [
			'created_by_iuid_fk' => $userID,
			'target_type' => $targetType,
			'target_creator_iuid_fk' => $targetType === 'subscribers_of_creator' ? $userID : null,
			'message_text' => $messageText,
			'attachment_path' => $attachmentPath,
			'private_price' => $privatePrice,
			'private_status' => $privateStatus === 1 ? 1 : 0,
			'rate_limit_per_run' => 25,
		];
		$campaignId = $iN->iN_CreateBulkCampaign($data);
		if (!$campaignId) {
			exit($LANG['bulk_campaign_failed']);
		}
		exit('200');
	}
	if ($type === 'creatorBulkBuildQueue') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$iN->iN_IsCreatorBulkEnabled()) {
			exit($LANG['creator_bulk_feature_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$campaignId = isset($_POST['bc_id']) ? (int)$iN->iN_Secure($_POST['bc_id']) : 0;
		if ($campaignId <= 0) {
			exit($LANG['bulk_campaign_invalid']);
		}
		$campaign = DB::one(
			"SELECT * FROM i_bulk_campaigns WHERE bc_id = ? AND created_by_iuid_fk = ? LIMIT 1",
			[$campaignId, $userID]
		);
		if (!$campaign) {
			exit($LANG['bulk_campaign_invalid']);
		}
		$status = (string)($campaign['status'] ?? 'draft');
		if (in_array($status, ['completed', 'canceled'], true)) {
			exit($LANG['bulk_campaign_invalid']);
		}
		$iN->iN_BuildBulkRecipientList($campaign);
		DB::exec("UPDATE i_bulk_campaigns SET status = 'queued', updated_at = ? WHERE bc_id = ?", [time(), $campaignId]);
		exit('200');
	}
	if ($type === 'creatorBulkUpdateCampaignStatus') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!$iN->iN_IsCreatorBulkEnabled()) {
			exit($LANG['creator_bulk_feature_disabled']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$campaignId = isset($_POST['bc_id']) ? (int)$iN->iN_Secure($_POST['bc_id']) : 0;
		$newStatus = $iN->iN_Secure($_POST['status'] ?? '');
		if ($campaignId <= 0 || !in_array($newStatus, ['paused', 'queued', 'canceled'], true)) {
			exit($LANG['bulk_campaign_invalid']);
		}
		$campaign = DB::one(
			"SELECT bc_id FROM i_bulk_campaigns WHERE bc_id = ? AND created_by_iuid_fk = ? LIMIT 1",
			[$campaignId, $userID]
		);
		if (!$campaign) {
			exit($LANG['bulk_campaign_invalid']);
		}
		DB::exec("UPDATE i_bulk_campaigns SET status = ?, updated_at = ? WHERE bc_id = ?", [$newStatus, time(), $campaignId]);
		exit('200');
	}
		if ($type === 'obsOverlayCreate') {
			if ($iN->iN_CheckUserIsCreator($userID) != 1) {
				exit($LANG['noway_desc']);
			}
			if (!$iN->iN_IsObsOverlayEnabled()) {
				exit($LANG['obs_overlay_feature_disabled']);
			}
				if (!csrf_validate_from_request()) {
					exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
				}
				$now = time();
				$maxOverlayTotalCount = $iN->iN_GetObsOverlayMaxTotal();
				$totalOverlayCount = (int)DB::col(
					"SELECT COUNT(*) FROM i_obs_overlays WHERE creator_id_fk = ?",
					[$userID]
				);
				if ($totalOverlayCount >= $maxOverlayTotalCount) {
					$totalLimitMessageTemplate = isset($LANG['obs_overlay_total_limit_reached']) ? (string)$LANG['obs_overlay_total_limit_reached'] : 'You can create up to %d overlays in total.';
					$totalLimitMessage = strpos($totalLimitMessageTemplate, '%d') !== false
						? sprintf($totalLimitMessageTemplate, $maxOverlayTotalCount)
						: str_replace('5', (string)$maxOverlayTotalCount, $totalLimitMessageTemplate);
					exit($totalLimitMessage);
				}
				$maxActiveOverlayCount = $iN->iN_GetObsOverlayMaxActive();
				$autoRevokeOldest = $iN->iN_IsObsOverlayAutoRevokeEnabled();
			if (!$autoRevokeOldest) {
				$activeOverlayCount = (int)DB::col(
					"SELECT COUNT(*) FROM i_obs_overlays WHERE creator_id_fk = ? AND is_active = '1'",
					[$userID]
				);
				if ($activeOverlayCount >= $maxActiveOverlayCount) {
					$limitMessageTemplate = isset($LANG['obs_overlay_limit_reached']) ? (string)$LANG['obs_overlay_limit_reached'] : 'You can have up to %d active overlays. Revoke one to create a new overlay.';
					$limitMessage = strpos($limitMessageTemplate, '%d') !== false
						? sprintf($limitMessageTemplate, $maxActiveOverlayCount)
						: str_replace('5', (string)$maxActiveOverlayCount, $limitMessageTemplate);
					exit($limitMessage);
				}
			} else {
				while (true) {
					$activeOverlayCount = (int)DB::col(
						"SELECT COUNT(*) FROM i_obs_overlays WHERE creator_id_fk = ? AND is_active = '1'",
						[$userID]
					);
					if ($activeOverlayCount < $maxActiveOverlayCount) {
						break;
					}
					$oldestActiveOverlayId = (int)DB::col(
						"SELECT obs_overlay_id
						 FROM i_obs_overlays
						 WHERE creator_id_fk = ? AND is_active = '1'
						 ORDER BY created_at ASC, obs_overlay_id ASC
						 LIMIT 1",
						[$userID]
					);
					if ($oldestActiveOverlayId <= 0) {
						break;
					}
					$revoked = DB::exec(
						"UPDATE i_obs_overlays
						 SET is_active = '0', updated_at = ?
						 WHERE obs_overlay_id = ? AND creator_id_fk = ? AND is_active = '1'",
						[$now, $oldestActiveOverlayId, $userID]
					);
					if (!$revoked) {
						break;
					}
				}
			}
			$token = bin2hex(random_bytes(16));
		for ($i = 0; $i < 5; $i++) {
			$exists = DB::col("SELECT 1 FROM i_obs_overlays WHERE overlay_token = ? LIMIT 1", [$token]);
			if (!$exists) {
				break;
			}
			$token = bin2hex(random_bytes(16));
		}
		$enabled = json_encode([
			'donation_total' => true,
			'alerts' => true,
			'milestone' => true,
			'cta' => true,
			'watermark' => true,
			'notification_box' => false,
			'leaderboard' => false,
			'target_goal' => false,
			'last_supporter' => false,
			'running_text' => false,
			'live_duration' => false
		], JSON_UNESCAPED_UNICODE);
		$inserted = DB::exec(
			"INSERT INTO i_obs_overlays (creator_id_fk, overlay_token, enabled_widgets, donation_mode, milestone_title, milestone_goal_amount, cta_label, cta_url, watermark_text, is_active, created_at, updated_at)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
			[(int)$userID, (string)$token, (string)$enabled, 'last24h', '', 0, '', '', '', '1', $now, $now]
		);
		if (!$inserted) {
			exit($LANG['obs_overlay_create_failed']);
		}
		exit('200');
	}
		if ($type === 'obsOverlaySave') {
			if ($iN->iN_CheckUserIsCreator($userID) != 1) {
				exit($LANG['noway_desc']);
			}
			if (!$iN->iN_IsObsOverlayEnabled()) {
				exit($LANG['obs_overlay_feature_disabled']);
			}
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
		$overlayId = isset($_POST['obs_overlay_id']) ? (int)$iN->iN_Secure($_POST['obs_overlay_id']) : 0;
		if ($overlayId <= 0) {
			exit($LANG['obs_overlay_invalid']);
		}
		$overlay = DB::one(
			"SELECT obs_overlay_id, settings_json FROM i_obs_overlays WHERE obs_overlay_id = ? AND creator_id_fk = ? LIMIT 1",
			[$overlayId, $userID]
		);
		if (!$overlay) {
			exit($LANG['obs_overlay_invalid']);
		}
		$donationMode = $iN->iN_Secure($_POST['donation_mode'] ?? 'last24h');
		$donationMode = $donationMode === 'alltime' ? 'alltime' : 'last24h';
		$milestoneTitle = trim($iN->iN_Secure($_POST['milestone_title'] ?? '', 0, false));
		$milestoneGoalRaw = $_POST['milestone_goal_amount'] ?? '0';
		$milestoneGoal = rq_parse_amount_value($milestoneGoalRaw);
		if ($milestoneGoal < 0) {
			exit($LANG['obs_overlay_invalid_amount']);
		}
		$ctaLabel = trim($iN->iN_Secure($_POST['cta_label'] ?? '', 0, false));
		$ctaUrl = trim((string)($_POST['cta_url'] ?? ''));
		$ctaUrl = strip_tags($ctaUrl);
		if ($ctaUrl !== '') {
			$parts = parse_url($ctaUrl);
			if (!filter_var($ctaUrl, FILTER_VALIDATE_URL) || !isset($parts['scheme']) || strtolower((string)$parts['scheme']) !== 'https') {
				exit($LANG['obs_overlay_invalid_url']);
			}
		}
		$watermarkText = trim($iN->iN_Secure($_POST['watermark_text'] ?? '', 0, false));
		$widgetNotification = isset($_POST['widget_notification_box']);
		$widgetLeaderboard = isset($_POST['widget_leaderboard']);
		$widgetTargetGoal = isset($_POST['widget_target_goal']);
		$widgetLastSupporter = isset($_POST['widget_last_supporter']);
		$widgetRunningText = isset($_POST['widget_running_text']);
		$widgetLiveDuration = isset($_POST['widget_live_duration']);
		$settings = [];
		$tiersMin = isset($_POST['notif_tier_min_amount']) && is_array($_POST['notif_tier_min_amount']) ? $_POST['notif_tier_min_amount'] : [];
		$tiersLabel = isset($_POST['notif_tier_label']) && is_array($_POST['notif_tier_label']) ? $_POST['notif_tier_label'] : [];
		$tiersClass = isset($_POST['notif_tier_class']) && is_array($_POST['notif_tier_class']) ? $_POST['notif_tier_class'] : [];
		$tiers = [];
		for ($tierIndex = 0; $tierIndex < 5; $tierIndex++) {
			$minRaw = $tiersMin[$tierIndex] ?? '';
			$labelRaw = $tiersLabel[$tierIndex] ?? '';
			$classRaw = $tiersClass[$tierIndex] ?? '';
			$minAmount = rq_parse_amount_value($minRaw);
			if ($minAmount < 0) {
				$minAmount = 0;
			}
			$label = trim($iN->iN_Secure($labelRaw, 0, false));
			if (strlen($label) > 60) {
				$label = substr($label, 0, 60);
			}
			$classKey = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$classRaw);
			if (strlen($classKey) > 40) {
				$classKey = substr($classKey, 0, 40);
			}
			if ($label === '' && $classKey === '' && $minAmount <= 0) {
				continue;
			}
			$tiers[] = [
				'min_amount' => (float)number_format($minAmount, 2, '.', ''),
				'label' => $label,
				'class_key' => $classKey
			];
		}
		if ($widgetNotification) {
			usort($tiers, function ($a, $b) {
				return ($a['min_amount'] ?? 0) <=> ($b['min_amount'] ?? 0);
			});
			$settings['notification_tiers'] = $tiers;
		}
		if ($widgetLeaderboard) {
			$leaderboardLimit = filter_var($_POST['leaderboard_limit'] ?? null, FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 1, 'max_range' => 10]
			]);
			$leaderboardLimit = $leaderboardLimit === false ? 5 : (int)$leaderboardLimit;
			$leaderboardMode = (string)$iN->iN_Secure($_POST['leaderboard_mode'] ?? 'last24h');
			if (!in_array($leaderboardMode, ['last24h', 'alltime', 'session'], true)) {
				$leaderboardMode = 'last24h';
			}
			$leaderboardTypes = [];
			if (isset($_POST['leaderboard_include_tips'])) {
				$leaderboardTypes[] = 'tips';
			}
			if (isset($_POST['leaderboard_include_live_gift'])) {
				$leaderboardTypes[] = 'live_gift';
			}
			if (empty($leaderboardTypes)) {
				$leaderboardTypes = ['tips', 'live_gift'];
			}
			$settings['leaderboard'] = [
				'limit' => $leaderboardLimit,
				'mode' => $leaderboardMode,
				'include_types' => $leaderboardTypes
			];
		}
		if ($widgetTargetGoal) {
			$targetGoalTitle = trim($iN->iN_Secure($_POST['target_goal_title'] ?? '', 0, false));
			if (strlen($targetGoalTitle) > 140) {
				$targetGoalTitle = substr($targetGoalTitle, 0, 140);
			}
			$targetGoalRaw = $_POST['target_goal_amount'] ?? '0';
			$targetGoalAmount = rq_parse_amount_value($targetGoalRaw);
			if ($targetGoalAmount < 0) {
				$targetGoalAmount = 0;
			}
			$targetGoalMode = (string)$iN->iN_Secure($_POST['target_goal_mode'] ?? 'last24h');
			if (!in_array($targetGoalMode, ['last24h', 'alltime', 'session'], true)) {
				$targetGoalMode = 'last24h';
			}
			$targetGoalTypes = [];
			if (isset($_POST['target_goal_include_tips'])) {
				$targetGoalTypes[] = 'tips';
			}
			if (isset($_POST['target_goal_include_live_gift'])) {
				$targetGoalTypes[] = 'live_gift';
			}
			if (empty($targetGoalTypes)) {
				$targetGoalTypes = ['tips', 'live_gift'];
			}
			$settings['target_goal'] = [
				'title' => $targetGoalTitle,
				'goal_amount' => (float)number_format($targetGoalAmount, 2, '.', ''),
				'mode' => $targetGoalMode,
				'include_types' => $targetGoalTypes
			];
		}
		if ($widgetLastSupporter) {
			$lastSupporterLabel = trim($iN->iN_Secure($_POST['last_supporter_label'] ?? '', 0, false));
			if (strlen($lastSupporterLabel) > 120) {
				$lastSupporterLabel = substr($lastSupporterLabel, 0, 120);
			}
			$lastSupporterMode = (string)$iN->iN_Secure($_POST['last_supporter_mode'] ?? 'last24h');
			if (!in_array($lastSupporterMode, ['last24h', 'alltime', 'session'], true)) {
				$lastSupporterMode = 'last24h';
			}
			$lastSupporterTypes = [];
			if (isset($_POST['last_supporter_include_tips'])) {
				$lastSupporterTypes[] = 'tips';
			}
			if (isset($_POST['last_supporter_include_live_gift'])) {
				$lastSupporterTypes[] = 'live_gift';
			}
			if (empty($lastSupporterTypes)) {
				$lastSupporterTypes = ['tips', 'live_gift'];
			}
			$settings['last_supporter'] = [
				'label' => $lastSupporterLabel,
				'show_amount' => isset($_POST['last_supporter_show_amount']) ? true : false,
				'mode' => $lastSupporterMode,
				'include_types' => $lastSupporterTypes
			];
		}
		if ($widgetRunningText) {
			$runningTextMode = (string)$iN->iN_Secure($_POST['running_text_mode'] ?? 'custom');
			if (!in_array($runningTextMode, ['custom', 'recent', 'leaderboard'], true)) {
				$runningTextMode = 'custom';
			}
			$runningTextTemplate = trim($iN->iN_Secure($_POST['running_text_template'] ?? '', 0, false));
			if (strlen($runningTextTemplate) > 200) {
				$runningTextTemplate = substr($runningTextTemplate, 0, 200);
			}
			$runningTextCustom = trim($iN->iN_Secure($_POST['running_text_custom'] ?? '', 0, false));
			if (strlen($runningTextCustom) > 220) {
				$runningTextCustom = substr($runningTextCustom, 0, 220);
			}
			$runningTextSpeed = filter_var($_POST['running_text_speed'] ?? null, FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 5, 'max_range' => 120]
			]);
			$runningTextSpeed = $runningTextSpeed === false ? 30 : (int)$runningTextSpeed;
			$settings['running_text'] = [
				'mode' => $runningTextMode,
				'template' => $runningTextTemplate,
				'custom_text' => $runningTextCustom,
				'speed' => $runningTextSpeed
			];
		}
		if ($widgetLiveDuration) {
			$liveUnitRaw = $_POST['live_extender_unit_amount'] ?? '0';
			$liveUnitAmount = rq_parse_amount_value($liveUnitRaw);
			if ($liveUnitAmount < 0) {
				$liveUnitAmount = 0;
			}
			$liveSeconds = filter_var($_POST['live_extender_seconds_per_unit'] ?? null, FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 5, 'max_range' => 600]
			]);
			$liveSeconds = $liveSeconds === false ? 60 : (int)$liveSeconds;
			$liveMaxSeconds = filter_var($_POST['live_extender_max_seconds'] ?? null, FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 0, 'max_range' => 21600]
			]);
			$liveMaxSeconds = $liveMaxSeconds === false ? 0 : (int)$liveMaxSeconds;
			$liveTypes = [];
			if (isset($_POST['live_extender_include_tips'])) {
				$liveTypes[] = 'tips';
			}
			if (isset($_POST['live_extender_include_live_gift'])) {
				$liveTypes[] = 'live_gift';
			}
			if (empty($liveTypes)) {
				$liveTypes = ['tips', 'live_gift'];
			}
			$settings['live_extender'] = [
				'unit_amount' => (float)number_format($liveUnitAmount, 2, '.', ''),
				'seconds_per_unit' => $liveSeconds,
				'max_seconds' => $liveMaxSeconds,
				'include_types' => $liveTypes
			];
		}
		$settingsJson = !empty($settings) ? json_encode($settings, JSON_UNESCAPED_UNICODE) : null;
		if ($settingsJson === null && !empty($overlay['settings_json'])) {
			$settingsJson = (string)$overlay['settings_json'];
		}
		$enabled = json_encode([
			'donation_total' => isset($_POST['widget_donation_total']) ? true : false,
			'alerts' => isset($_POST['widget_alerts']) ? true : false,
			'milestone' => isset($_POST['widget_milestone']) ? true : false,
			'cta' => isset($_POST['widget_cta']) ? true : false,
			'watermark' => isset($_POST['widget_watermark']) ? true : false,
			'notification_box' => isset($_POST['widget_notification_box']) ? true : false,
			'leaderboard' => isset($_POST['widget_leaderboard']) ? true : false,
			'target_goal' => isset($_POST['widget_target_goal']) ? true : false,
			'last_supporter' => isset($_POST['widget_last_supporter']) ? true : false,
			'running_text' => isset($_POST['widget_running_text']) ? true : false,
			'live_duration' => isset($_POST['widget_live_duration']) ? true : false
		], JSON_UNESCAPED_UNICODE);
		DB::exec(
			"UPDATE i_obs_overlays
			 SET enabled_widgets = ?, donation_mode = ?, milestone_title = ?, milestone_goal_amount = ?, cta_label = ?, cta_url = ?, watermark_text = ?, settings_json = ?, updated_at = ?
			 WHERE obs_overlay_id = ? AND creator_id_fk = ?",
			[
				(string)$enabled,
				(string)$donationMode,
				(string)$milestoneTitle,
				(float)$milestoneGoal,
				(string)$ctaLabel,
				(string)$ctaUrl,
				(string)$watermarkText,
				$settingsJson !== null ? (string)$settingsJson : null,
				time(),
				$overlayId,
				$userID
			]
		);
		exit('200');
	}
		if ($type === 'obsOverlaySaveLayout') {
			if ($iN->iN_CheckUserIsCreator($userID) != 1) {
				exit($LANG['noway_desc']);
			}
			if (!$iN->iN_IsObsOverlayEnabled()) {
				exit($LANG['obs_overlay_feature_disabled']);
			}
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
		$overlayToken = isset($_POST['overlay_token']) ? (string)$_POST['overlay_token'] : '';
		$overlayToken = preg_replace('/[^A-Za-z0-9_-]/', '', $overlayToken);
		if ($overlayToken === '') {
			exit($LANG['obs_overlay_invalid']);
		}
		$overlay = DB::one(
			"SELECT obs_overlay_id FROM i_obs_overlays WHERE overlay_token = ? AND creator_id_fk = ? LIMIT 1",
			[$overlayToken, $userID]
		);
		if (!$overlay) {
			exit($LANG['obs_overlay_invalid']);
		}
		$layoutJson = trim((string)($_POST['layout_json'] ?? ''));
		if ($layoutJson === '') {
			DB::exec(
				"UPDATE i_obs_overlays SET layout_json = NULL, updated_at = ? WHERE overlay_token = ? AND creator_id_fk = ?",
				[time(), $overlayToken, $userID]
			);
			header('Content-Type: application/json; charset=utf-8');
			exit(json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE));
		}
		if (strlen($layoutJson) > 20000) {
			exit($LANG['obs_overlay_layout_invalid']);
		}
		$layoutData = json_decode($layoutJson, true);
		if (!is_array($layoutData)) {
			exit($LANG['obs_overlay_layout_invalid']);
		}
		$allowedWidgets = ['donation_total', 'alerts', 'milestone', 'cta', 'watermark', 'notification_box', 'leaderboard', 'target_goal', 'last_supporter', 'running_text', 'live_duration'];
		$cleanLayout = [];
		foreach ($layoutData as $widgetKey => $widgetLayout) {
			if (!in_array($widgetKey, $allowedWidgets, true)) {
				continue;
			}
			if (!is_array($widgetLayout)) {
				exit($LANG['obs_overlay_layout_invalid']);
			}
			$anchor = isset($widgetLayout['anchor']) ? (string) $widgetLayout['anchor'] : '';
			$x = filter_var($widgetLayout['x'] ?? null, FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 0, 'max_range' => 1920]
			]);
			$y = filter_var($widgetLayout['y'] ?? null, FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 0, 'max_range' => 1080]
			]);
			$scaleRaw = $widgetLayout['scale'] ?? null;
			if ($x === false || $y === false || !is_numeric($scaleRaw)) {
				exit($LANG['obs_overlay_layout_invalid']);
			}
			$scale = (float)$scaleRaw;
			if ($scale < 0.5 || $scale > 2.0) {
				exit($LANG['obs_overlay_layout_invalid']);
			}
			$cleanLayout[$widgetKey] = [
				'x' => (int)$x,
				'y' => (int)$y,
				'scale' => round($scale, 2)
			];
			if ($anchor !== '') {
				if (!in_array($anchor, ['tl', 'tr', 'bl', 'br'], true)) {
					exit($LANG['obs_overlay_layout_invalid']);
				}
				$cleanLayout[$widgetKey]['anchor'] = $anchor;
			}
			if (array_key_exists('zIndex', $widgetLayout) && $widgetLayout['zIndex'] !== '') {
				$zIndex = filter_var($widgetLayout['zIndex'], FILTER_VALIDATE_INT, [
					'options' => ['min_range' => 0, 'max_range' => 999]
				]);
				if ($zIndex === false) {
					exit($LANG['obs_overlay_layout_invalid']);
				}
				$cleanLayout[$widgetKey]['zIndex'] = (int)$zIndex;
			}
		}
		$layoutJson = json_encode($cleanLayout, JSON_UNESCAPED_UNICODE);
		DB::exec(
			"UPDATE i_obs_overlays SET layout_json = ?, updated_at = ? WHERE overlay_token = ? AND creator_id_fk = ?",
			[(string)$layoutJson, time(), $overlayToken, $userID]
		);
		header('Content-Type: application/json; charset=utf-8');
		exit(json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE));
	}
		if ($type === 'obsOverlaySaveStyles') {
			if ($iN->iN_CheckUserIsCreator($userID) != 1) {
				exit($LANG['noway_desc']);
			}
			if (!$iN->iN_IsObsOverlayEnabled()) {
				exit($LANG['obs_overlay_feature_disabled']);
			}
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
		$overlayToken = isset($_POST['overlay_token']) ? (string)$_POST['overlay_token'] : '';
		$overlayToken = preg_replace('/[^A-Za-z0-9_-]/', '', $overlayToken);
		if ($overlayToken === '') {
			exit($LANG['obs_overlay_invalid']);
		}
		$overlay = DB::one(
			"SELECT obs_overlay_id FROM i_obs_overlays WHERE overlay_token = ? AND creator_id_fk = ? LIMIT 1",
			[$overlayToken, $userID]
		);
		if (!$overlay) {
			exit($LANG['obs_overlay_invalid']);
		}
		$stylesJson = trim((string)($_POST['styles_json'] ?? ''));
		if ($stylesJson === '') {
			DB::exec(
				"UPDATE i_obs_overlays SET styles_json = NULL, updated_at = ? WHERE overlay_token = ? AND creator_id_fk = ?",
				[time(), $overlayToken, $userID]
			);
			header('Content-Type: application/json; charset=utf-8');
			exit(json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE));
		}
		if (strlen($stylesJson) > 20000) {
			exit($LANG['obs_overlay_styles_invalid']);
		}
		$stylesData = json_decode($stylesJson, true);
		if (!is_array($stylesData)) {
			exit($LANG['obs_overlay_styles_invalid']);
		}
		$allowedWidgets = ['donation_total', 'alerts', 'milestone', 'cta', 'watermark', 'notification_box', 'leaderboard', 'target_goal', 'last_supporter', 'running_text', 'live_duration'];
		$allowedAlign = ['left', 'center', 'right'];
		$allowedColorsMap = [];
		if (isset($dizColors) && is_array($dizColors)) {
			foreach ($dizColors as $groupColors) {
				if (!is_array($groupColors)) {
					continue;
				}
				foreach ($groupColors as $hex) {
					$hex = strtoupper(ltrim((string) $hex, '#'));
					if (!preg_match('/^[0-9A-F]{6}$/', $hex)) {
						continue;
					}
					$allowedColorsMap['#' . $hex] = true;
				}
			}
		}
		$allowedColors = array_keys($allowedColorsMap);
		$usePalette = !empty($allowedColors);
		$cleanStyles = [];
		foreach ($stylesData as $widgetKey => $widgetStyles) {
			if (!in_array($widgetKey, $allowedWidgets, true)) {
				continue;
			}
			if (!is_array($widgetStyles)) {
				exit($LANG['obs_overlay_styles_invalid']);
			}
			$styleEntry = [];
			if (isset($widgetStyles['textColor']) && $widgetStyles['textColor'] !== '') {
				$textColor = strtoupper((string)$widgetStyles['textColor']);
				if (!preg_match('/^#[0-9A-F]{6}$/', $textColor)) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				if ($usePalette && !in_array($textColor, $allowedColors, true)) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				$styleEntry['textColor'] = $textColor;
			}
			if (isset($widgetStyles['bgColor']) && $widgetStyles['bgColor'] !== '') {
				$bgColor = strtoupper((string)$widgetStyles['bgColor']);
				if (!preg_match('/^#[0-9A-F]{6}$/', $bgColor)) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				if ($usePalette && !in_array($bgColor, $allowedColors, true)) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				$styleEntry['bgColor'] = $bgColor;
			}
			if (isset($widgetStyles['bgOpacity']) && $widgetStyles['bgOpacity'] !== '') {
				$bgOpacity = filter_var($widgetStyles['bgOpacity'], FILTER_VALIDATE_INT, [
					'options' => ['min_range' => 0, 'max_range' => 100]
				]);
				if ($bgOpacity === false) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				$styleEntry['bgOpacity'] = (int)$bgOpacity;
			}
			if (isset($widgetStyles['fontSize']) && $widgetStyles['fontSize'] !== '') {
				$fontSize = filter_var($widgetStyles['fontSize'], FILTER_VALIDATE_INT, [
					'options' => ['min_range' => 1, 'max_range' => 30]
				]);
				if ($fontSize === false) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				$styleEntry['fontSize'] = (int)$fontSize;
			}
			if (isset($widgetStyles['borderRadius']) && $widgetStyles['borderRadius'] !== '') {
				$borderRadius = filter_var($widgetStyles['borderRadius'], FILTER_VALIDATE_INT, [
					'options' => ['min_range' => 0, 'max_range' => 40]
				]);
				if ($borderRadius === false) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				$styleEntry['borderRadius'] = (int)$borderRadius;
			}
			if (isset($widgetStyles['textAlign']) && $widgetStyles['textAlign'] !== '') {
				$textAlign = (string)$widgetStyles['textAlign'];
				if (!in_array($textAlign, $allowedAlign, true)) {
					exit($LANG['obs_overlay_styles_invalid']);
				}
				$styleEntry['textAlign'] = $textAlign;
			}
			if (!empty($styleEntry)) {
				$cleanStyles[$widgetKey] = $styleEntry;
			}
		}
		$stylesJson = json_encode($cleanStyles, JSON_UNESCAPED_UNICODE);
		DB::exec(
			"UPDATE i_obs_overlays SET styles_json = ?, updated_at = ? WHERE overlay_token = ? AND creator_id_fk = ?",
			[(string)$stylesJson, time(), $overlayToken, $userID]
		);
		header('Content-Type: application/json; charset=utf-8');
		exit(json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE));
	}
		if ($type === 'obsOverlayRevoke') {
			if ($iN->iN_CheckUserIsCreator($userID) != 1) {
				exit($LANG['noway_desc']);
			}
			if (!$iN->iN_IsObsOverlayEnabled()) {
				exit($LANG['obs_overlay_feature_disabled']);
			}
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
		$overlayId = isset($_POST['obs_overlay_id']) ? (int)$iN->iN_Secure($_POST['obs_overlay_id']) : 0;
		if ($overlayId <= 0) {
			exit($LANG['obs_overlay_invalid']);
		}
		DB::exec(
			"UPDATE i_obs_overlays SET is_active = '0', updated_at = ? WHERE obs_overlay_id = ? AND creator_id_fk = ?",
			[time(), $overlayId, $userID]
		);
		exit('200');
	}
	if ($type === 'creatorAutoMessageSave') {
		if ($iN->iN_CheckUserIsCreator($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$isEnabled = isset($_POST['is_enabled']) ? (int)$iN->iN_Secure($_POST['is_enabled']) : 0;
		$messageText = trim($iN->iN_Secure($_POST['message_text'] ?? ''));
		$removeAttachment = isset($_POST['remove_attachment']) ? (int)$iN->iN_Secure($_POST['remove_attachment']) : 0;

		if ($isEnabled === 1 && $messageText === '') {
			exit($LANG['creator_auto_message_invalid']);
		}

		$current = $iN->iN_GetCreatorAutoMessage($userID);
		$attachmentPath = isset($current['attachment_path']) ? trim((string)$current['attachment_path']) : '';
		if ($removeAttachment === 1) {
			$attachmentPath = '';
		}

		if (isset($_FILES['auto_attachment']) && !empty($_FILES['auto_attachment']['name'])) {
			$bulkExtensionsRaw = $availableBulkMessageFileExtensions ?? $availableFileExtensions ?? '';
			$bulkExtensions = array_values(array_filter(array_map('trim', explode(',', (string)$bulkExtensionsRaw))));
			$thumbnailFile = isset($_FILES['auto_video_thumbnail']) ? $_FILES['auto_video_thumbnail'] : null;
			[$uploadError, $attachmentPath] = iN_creator_handle_bulk_attachment_upload(
				$_FILES['auto_attachment'],
				$userID,
				(int)$availableUploadFileSize,
				(string)$uploadFile,
				$bulkExtensions,
				$ffmpegCanConvert,
				(string)$ffmpegPath,
				$thumbnailFile
			);
			if ($uploadError === 'invalid_format') {
				exit($LANG['invalid_file_format']);
			}
			if ($uploadError === 'unsupported_video_format') {
				exit($LANG['unsupported_video_format']);
			}
			if ($uploadError === 'file_is_too_large') {
				exit($LANG['file_is_too_large']);
			}
			if ($uploadError === 'thumbnail_required') {
				exit($LANG['bulk_thumbnail_required']);
			}
			if ($uploadError === 'thumbnail_invalid') {
				exit($LANG['bulk_thumbnail_invalid']);
			}
			if ($uploadError !== null || !$attachmentPath) {
				exit($LANG['upload_failed']);
			}
		}

		$saved = $iN->iN_SaveCreatorAutoMessage($userID, $isEnabled, $messageText, $attachmentPath);
		if (!$saved) {
			exit($LANG['creator_auto_message_invalid']);
		}
		exit('200');
	}
}
elseif (isset($_POST['f'])) {
	$loginFormClass = '';
	$type = $iN->iN_Secure($_POST['f']);
		if ($type == 'searchCreator') {
			if (isset($_POST['s'])) {
				$searchValue = $iN->iN_Secure($_POST['s']);
				$searchValueFromData = $iN->iN_GetSearchResult($iN->iN_Secure($searchValue), $showingNumberOfPost, $whicUsers, $userID ?? null, $viewerCountryCode ?? null);
				include "../themes/$currentTheme/layouts/header/searchResults.php";
				exit();
			}
		}
	if ($type == 'forgotPass') {
		if (isset($_POST['email']) && !empty($_POST['email'])) {
			$sendEmail = $iN->iN_Secure($_POST['email']);
			$checkEmailExist = $iN->iN_CheckEmailExistForRegister($iN->iN_Secure($sendEmail));
			if ($checkEmailExist) {
				$code = md5(rand(1111, 9999) . time());
				if ($emailSendStatus == '1') {
					$insertNewCode = $iN->iN_InsertNewForgotPasswordCode($iN->iN_Secure($sendEmail), $iN->iN_Secure($code));
					$activateLink = $base_url . 'reset_password?active=' . $code;
					$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden;";
                    $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:30px; border-radius:3px;";
                    $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
                    $imgStyle = "width:100%; display:block;";
                    $titleStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
                    $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff;";
                    $linkStyle = "text-decoration:none; color:#ffffff; font-weight:500; font-size:18px; display:inline-block;";
					if ($insertNewCode) {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$body = '
                            <div style="' . $wrapperStyle . '">
                              <div style="' . $containerStyle . '">

                                <div style="' . $logoBoxStyle . '">
                                  <img src="' . $siteLogoUrl . '" style="' . $imgStyle . '" />
                                </div>

                                <div style="' . $titleStyle . '">
                                  <strong>Forgot your Password?</strong> reset it below:
                                </div>

                                <div style="' . $buttonBoxStyle . '">
                                  <a href="' . $activateLink . '" style="' . $linkStyle . '">
                                    Reset Password
                                  </a>
                                </div>

                              </div>
                            </div>';
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['forgot_password']);
			$mail->CharSet = 'utf-8';
			$mail->MsgHTML($body);
			if (iN_safeMailSend($mail, $smtpOrMail, 'forgot_password')) {
				$mail->ClearAddresses();
				echo '200';
				return true;
			}
					}
				} else {
					echo '3';
				}
			} else {
				echo '2';
			}
		} else {
			exit('1');
		}
	}

	/*Reset Password*/
	if ($type == 'iresetpass') {
		$activationCode = $iN->iN_Secure($_POST['ac']);
		$newPassword = $iN->iN_Secure($_POST['pnew']);
		$confirmNewPassword = $iN->iN_Secure($_POST['repnew']);
		$checkCodeExist = $iN->iN_CheckCodeExist($activationCode);
		if ($checkCodeExist) {
			if (strlen($newPassword) < 6 || strlen($confirmNewPassword) < 6) {
				exit('5');
			}
			if (!empty($newPassword) && $newPassword != '' && isset($newPassword) && !empty($confirmNewPassword) && $confirmNewPassword != '' && isset($confirmNewPassword)) {
				if ($newPassword != $confirmNewPassword) {
					exit('2');
				} else {
					$newPassword = sha1(md5($newPassword));
					$updateNewPassword = $iN->iN_ResetPassword($iN->iN_Secure($activationCode), $iN->iN_Secure($newPassword));
					if ($updateNewPassword) {
						exit('200');
					} else {
						exit('404');
					}
				}
			} else {
				exit('4');
			}
		}
	}
	/*Check Claim*/
	if ($type == 'claim') {
    	if (isset($_POST['clnm']) && !empty($_POST['clnm'])) {
    		$checkUserNameExist = $iN->iN_CheckUsernameExistForRegister($_POST['clnm']);

    		if ($checkUserNameExist) {
    			echo json_encode(['status' => '2']);
    			exit;
    		}

    		if (!preg_match('/^[\w]+$/', $_POST['clnm'])) {
    			echo json_encode(['status' => '5']);
    			exit;
    		}

    		echo json_encode(['status' => '200']);
    		exit;
    	} else {
    		echo json_encode(['status' => '3']);
    		exit;
    	}
    }
}
?>
