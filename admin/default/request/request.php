<?php
include_once "../../../includes/inc.php";
if ($s3Status == '1') {
	include "../../../includes/s3.php";
}else if($digitalOceanStatus == '1'){
    include "../../../includes/spaces/spaces.php";
}
/*PhpMailer*/
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//Load Composer's autoloader
require '../../../includes/phpmailer/vendor/autoload.php';
//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

// Ensure clean responses: remove any buffered whitespace/BOM from includes
if (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_clean();
}

if (!function_exists('storage_public_overrides_get')) {
    function storage_public_overrides_get(): array {
        $bunnyStatus = $GLOBALS['bunnyCdnStatus'] ?? '0';
        $bunnyBase = $GLOBALS['bunnyCdnBase'] ?? '';
        $bunnyStatus = in_array((string)$bunnyStatus, ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
        return [
            's3_public_base'    => $GLOBALS['s3PublicBase'] ?? '',
            'ocean_public_base' => $GLOBALS['digitalOceanPublicBase'] ?? '',
            'was_public_base'   => $GLOBALS['WasPublicBase'] ?? '',
            // Only persist Bunny CDN override when enabled
            'bunny_cdn_status'  => $bunnyStatus === '1' ? '1' : '',
            'bunny_cdn_base'    => ($bunnyStatus === '1') ? (string)$bunnyBase : '',
        ];
    }
}

if (!function_exists('storage_public_overrides_write')) {
    function storage_public_overrides_write(array $values): bool {
        $path = __DIR__ . '/../../../includes/storage_public_base.php';
        $normalized = [];
        foreach ($values as $key => $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $normalized[$key] = $value;
            }
        }
        if (empty($normalized)) {
            if (is_file($path)) {
                return @unlink($path);
            }
            return true;
        }
        $code = "<?php\n// Auto-generated storage CDN overrides on " . date('c') . "\nif (!isset(\$inc) || !is_array(\$inc)) { \$inc = []; }\n";
        foreach ($normalized as $key => $value) {
            $code .= "\$inc['{$key}'] = '" . addslashes($value) . "';\n";
        }
        $code .= "?>\n";
        return (bool)file_put_contents($path, $code, LOCK_EX);
    }
}

if (!function_exists('normalize_public_base_url')) {
    function normalize_public_base_url(?string $value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        $validated = filter_var($value, FILTER_VALIDATE_URL);
        if ($validated === false) {
            return '';
        }
        return rtrim($validated, '/') . '/';
    }
}

if (!function_exists('iN_admin_handle_blog_cover_upload')) {
    function iN_admin_handle_blog_cover_upload(array $file, int $userID, $iN) {
        global $availableUploadFileSize, $uploadFile, $LANG;
        if (empty($file) || !isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [null, null, null];
        }
        $name = stripslashes($file['name']);
        $size = $file['size'] ?? 0;
        $tmp = $file['tmp_name'] ?? '';
        $ext = strtolower(getExtension($name));
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($ext, $allowed, true)) {
            return ['invalid_format', null, null];
        }
        if (function_exists('convert_to_mb') && convert_to_mb($size) > $availableUploadFileSize) {
            return ['file_is_too_large', null, null];
        }
        $todayDir = date('Y-m-d');
        $uploadDir = $uploadFile . $todayDir . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $microtime = microtime();
        $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
        $uploadedFileName = 'blog_' . $removeMicrotime . '_' . $userID . '.' . $ext;
        $uploadedPath = $uploadDir . $uploadedFileName;
        if (!move_uploaded_file($tmp, $uploadedPath)) {
            return ['upload_failed', null, null];
        }
        $uploadId = $iN->iN_INSERTUploadedFiles($userID, $uploadedPath, $uploadedPath, '', $ext);
        return [null, $uploadId, $uploadedPath];
    }
}
if (!function_exists('iN_admin_handle_bulk_attachment_upload')) {
    function iN_admin_handle_bulk_attachment_upload(array $file, int $userID): array {
        global $availableUploadFileSize, $uploadFile, $LANG;
        if (empty($file) || !isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [null, null];
        }
        $name = stripslashes($file['name']);
        $size = $file['size'] ?? 0;
        $tmp = $file['tmp_name'] ?? '';
        $ext = strtolower(getExtension($name));
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($ext, $allowed, true)) {
            return ['invalid_format', null];
        }
        if (function_exists('convert_to_mb') && convert_to_mb($size) > $availableUploadFileSize) {
            return ['file_is_too_large', null];
        }
        $todayDir = date('Y-m-d');
        $uploadDir = $uploadFile . $todayDir . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $microtime = microtime();
        $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
        $uploadedFileName = 'bulk_' . $removeMicrotime . '_' . $userID . '.' . $ext;
        $uploadedPath = $uploadDir . $uploadedFileName;
        if (!move_uploaded_file($tmp, $uploadedPath)) {
            return ['upload_failed', null];
        }
        $relativePath = 'uploads/files/' . $todayDir . '/' . $uploadedFileName;
        if (function_exists('storage_publish_many')) {
            storage_publish_many([$relativePath], true, true);
        }
        return [null, $relativePath];
    }
}
$statusValue = array('0', '1');
$yesNo = array('no', 'yes');
$beACreatorArray = array('request', 'admin_accept','auto_approve');
$statusTrueFalse = array('false', 'true');
$announcementTypes = array('creators', 'everyone');
$statusSubOneTwo = array('1', '2');
if (!function_exists('iN_admin_send_announcement_web_push')) {
    function iN_admin_send_announcement_web_push(
        $iN,
        int $announcementId,
        string $announcementText,
        string $announcementType,
        string $baseUrl,
        array $lang
    ): void {
        if ($announcementId <= 0) {
            return;
        }
        if ((string)$iN->iN_GetSetting('web_push_status', '0') !== '1') {
            return;
        }
        if ((string)$iN->iN_GetSetting('web_push_event_announcement', '1') !== '1') {
            return;
        }

        $title = trim((string)($lang['announcement_title'] ?? 'Announcement'));
        if ($title === '') {
            $title = 'Announcement';
        }
        $body = trim((string)preg_replace('/\s+/', ' ', strip_tags((string)$announcementText)));
        if ($body === '') {
            return;
        }
        if (function_exists('mb_substr')) {
            $body = (string)mb_substr($body, 0, 180);
        } else {
            $body = (string)substr($body, 0, 180);
        }

        $payloadTitle = $iN->iN_Secure($title);
        $payloadBody = $iN->iN_Secure($body);
        $url = rtrim((string)$baseUrl, '/') . '/';
        $tag = 'announcement_' . $announcementId;
        $onlyCreators = ((string)$announcementType === 'creators');
        $batchSize = 300;
        $lastId = 0;

        while (true) {
            if ($onlyCreators) {
                $rows = DB::all(
                    "SELECT iuid
                     FROM i_users
                     WHERE iuid > ? AND uStatus IN('1','3') AND condition_status = '2'
                     ORDER BY iuid ASC
                     LIMIT {$batchSize}",
                    [(int)$lastId]
                );
            } else {
                $rows = DB::all(
                    "SELECT iuid
                     FROM i_users
                     WHERE iuid > ? AND uStatus IN('1','3')
                     ORDER BY iuid ASC
                     LIMIT {$batchSize}",
                    [(int)$lastId]
                );
            }

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $recipientId = (int)($row['iuid'] ?? 0);
                if ($recipientId <= 0) {
                    continue;
                }
                $iN->iN_SendWebPushToUser($recipientId, [
                    'title' => $payloadTitle,
                    'body' => $payloadBody,
                    'url' => $url,
                    'tag' => $tag,
                ]);
            }

            $lastRow = end($rows);
            $lastId = (int)($lastRow['iuid'] ?? $lastId);
            if (count($rows) < $batchSize) {
                break;
            }
        }
    }
}
if (!function_exists('iN_admin_openai_health_check')) {
    function iN_admin_openai_health_check(string $apiKey, string $model): array {
        $url = 'https://api.openai.com/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a health check endpoint. Reply with OK.'],
                ['role' => 'user', 'content' => 'OK'],
            ],
            'temperature' => 0.0,
            'max_tokens' => 5,
        ];
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
            return ['ok' => false, 'code' => 'curl_error', 'message' => $curlErr];
        }
        $result = json_decode((string)$response, true);
        if ($httpCode >= 400 || isset($result['error'])) {
            $err = $result['error']['message'] ?? 'OpenAI request failed.';
            $code = (string)($result['error']['code'] ?? $httpCode);
            return ['ok' => false, 'code' => $code, 'message' => $err];
        }
        $content = $result['choices'][0]['message']['content'] ?? '';
        if (stripos($content, 'ok') !== false) {
            return ['ok' => true, 'code' => 'ok', 'message' => 'OK'];
        }
        return ['ok' => false, 'code' => 'unexpected_response', 'message' => 'Unexpected response from API.'];
    }
}
	if (isset($_POST['f']) && $logedIn == '1' && $userType == '2') {
	    // Backwards-compatible CSRF for admin POSTs
	    if (isset($_POST['csrf_token']) || isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
	        include_once __DIR__ . '/../../../includes/csrf.php';
	        if (!csrf_validate_from_request()) {
	            exit('Invalid CSRF token.');
	        }
	    }
			$type = $iN->iN_Secure($_POST['f']);

        if ($type === 'uploadBlogImage') {
            header('Content-Type: application/json; charset=utf-8');
            if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) {
                echo json_encode(['error' => 'no_file']);
                exit();
            }
            [$uploadError, $uploadId, $uploadedPath] = iN_admin_handle_blog_cover_upload($_FILES['file'], $userID, $iN);
            if ($uploadError === 'invalid_format') {
                echo json_encode(['error' => $LANG['invalid_file_format']]);
                exit();
            }
            if ($uploadError === 'file_is_too_large') {
                echo json_encode(['error' => $LANG['file_is_too_large']]);
                exit();
            }
            if ($uploadError !== null || !$uploadedPath) {
                echo json_encode(['error' => $LANG['upload_failed']]);
                exit();
            }
            $publicPath = str_replace(APP_ROOT_PATH, rtrim($base_url, '/'), $uploadedPath);
            $publicPath = str_replace(DIRECTORY_SEPARATOR, '/', $publicPath);
            echo json_encode(['location' => $publicPath]);
            exit();
        }

        if ($type === 'agencyUpsert') {
            exit($LANG['noway_desc']);
        }

        if ($type === 'agencyStatusUpdate') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            $agencyId = isset($_POST['agency_id']) ? (int)$iN->iN_Secure($_POST['agency_id']) : 0;
            $agencyStatus = $iN->iN_Secure($_POST['agency_status'] ?? '');
            if ($agencyId <= 0 || !in_array($agencyStatus, ['active', 'inactive'], true)) {
                exit($LANG['agency_invalid_status']);
            }
            $exists = DB::one("SELECT agency_id FROM i_agencies WHERE agency_id = ? LIMIT 1", [$agencyId]);
            if (!$exists) {
                exit($LANG['agency_not_found']);
            }
            DB::exec("UPDATE i_agencies SET agency_status = ? WHERE agency_id = ?", [$agencyStatus, $agencyId]);
            exit('200');
        }

        if ($type === 'agencyCreateRequestStatus') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            include_once __DIR__ . '/../../../includes/csrf.php';
            if (!csrf_validate_from_request()) {
                exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
            }
            $requestId = isset($_POST['request_id']) ? (int)$iN->iN_Secure($_POST['request_id']) : 0;
            $status = $iN->iN_Secure($_POST['status'] ?? '');
            if ($requestId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
                exit($LANG['agency_request_invalid']);
            }
            $requestRow = DB::one("SELECT * FROM i_agency_create_requests WHERE acr_id = ? LIMIT 1", [$requestId]);
            if (!$requestRow) {
                exit($LANG['agency_request_not_found']);
            }
            if (($requestRow['status'] ?? '') !== 'pending') {
                exit($LANG['agency_request_invalid']);
            }
            $ownerId = (int)($requestRow['owner_iuid_fk'] ?? 0);
            if ($status === 'approved') {
                if ($ownerId <= 0 || $iN->iN_CheckUserExist($ownerId) != 1) {
                    exit($LANG['agency_invalid_owner']);
                }
                $ownerIsCreator = $iN->iN_CheckUserIsCreator($ownerId) == 1;
                $autoCreatorSettingRaw = strtolower(trim((string)$iN->iN_GetSetting(
                    'agency_auto_creator_on_approval',
                    isset($agencyAutoCreatorOnApproval) ? (string)$agencyAutoCreatorOnApproval : 'yes'
                )));
                if (!in_array($autoCreatorSettingRaw, ['yes', 'no'], true)) {
                    $autoCreatorSettingRaw = 'yes';
                }
                $autoCreatorOnApproval = $autoCreatorSettingRaw === 'yes';
                if (!$ownerIsCreator && !$autoCreatorOnApproval) {
                    exit($LANG['agency_invalid_owner']);
                }
                try {
                    DB::begin();
                    if (!$ownerIsCreator && $autoCreatorOnApproval) {
                        DB::exec(
                            "UPDATE i_users SET certification_status = '2', validation_status = '2', condition_status = '2', fees_status = '2', payout_status = '2', user_verified_status = '1' WHERE iuid = ?",
                            [$ownerId]
                        );
                    }
                    $ownerIsCreator = $iN->iN_CheckUserIsCreator($ownerId) == 1;
                    if (!$ownerIsCreator) {
                        DB::rollBack();
                        exit($LANG['agency_invalid_owner']);
                    }
                    $ownerAlready = DB::col("SELECT 1 FROM i_agencies WHERE owner_iuid_fk = ? LIMIT 1", [$ownerId]);
                    if ($ownerAlready) {
                        DB::rollBack();
                        exit($LANG['agency_owner_already_has_agency']);
                    }
                    $memberAlready = DB::col("SELECT 1 FROM i_agency_members WHERE creator_iuid_fk = ? LIMIT 1", [$ownerId]);
                    if ($memberAlready) {
                        DB::rollBack();
                        exit($LANG['agency_already_member']);
                    }
                    $time = time();
                    DB::exec(
                        "INSERT INTO i_agencies (owner_iuid_fk, agency_name, agency_fee, agency_status, agency_created_at) VALUES (?,?,?,?,?)",
                        [$ownerId, (string)($requestRow['agency_name'] ?? ''), (string)($requestRow['agency_fee'] ?? '0.00'), 'active', $time]
                    );
                    $agencyId = (int) DB::lastId();
                    if ($agencyId <= 0) {
                        DB::rollBack();
                        exit($LANG['agency_request_failed']);
                    }
                    $added = $iN->iN_AddCreatorToAgency($agencyId, $ownerId);
                    if (!$added && $autoCreatorOnApproval) {
                        $memberExists = DB::col(
                            "SELECT 1 FROM i_agency_members WHERE agency_id_fk = ? AND creator_iuid_fk = ? LIMIT 1",
                            [$agencyId, $ownerId]
                        );
                        if (!$memberExists) {
                            DB::exec(
                                "INSERT INTO i_agency_members (agency_id_fk, creator_iuid_fk, status, created_at) VALUES (?,?, 'active', ?)",
                                [$agencyId, $ownerId, $time]
                            );
                        }
                        $added = true;
                    }
                    if (!$added) {
                        DB::rollBack();
                        exit($LANG['agency_member_add_failed']);
                    }
                    DB::exec(
                        "UPDATE i_agency_create_requests SET status = ?, reviewed_by_iuid_fk = ?, reviewed_at = ?, updated_at = ? WHERE acr_id = ?",
                        [$status, (int)$userID, $time, $time, $requestId]
                    );
                    DB::commit();
                    exit('200');
                } catch (Throwable $e) {
                    try {
                        DB::rollBack();
                    } catch (Throwable $rollbackError) {
                    }
                    exit($LANG['agency_request_failed']);
                }
            }
            $time = time();
            DB::exec(
                "UPDATE i_agency_create_requests SET status = ?, reviewed_by_iuid_fk = ?, reviewed_at = ?, updated_at = ? WHERE acr_id = ?",
                [$status, (int)$userID, $time, $time, $requestId]
            );
            exit('200');
        }

        if ($type === 'registrationRoleSettings') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            include_once __DIR__ . '/../../../includes/csrf.php';
            if (!csrf_validate_from_request()) {
                exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
            }
            $registrationRoleModeRaw = strtolower(trim((string)($_POST['registration_role_mode'] ?? 'legacy')));
            if (!in_array($registrationRoleModeRaw, ['legacy', 'user_agency', 'user_agency_creator'], true)) {
                exit($LANG['invalid_registration_role_mode'] ?? 'invalid_registration_role_mode');
            }
            $registrationRoleMode = $iN->iN_NormalizeRegistrationRoleMode($registrationRoleModeRaw);
            $agencyAutoCreatorOnApproval = strtolower(trim((string)($_POST['agency_auto_creator_on_approval'] ?? 'yes')));
            if (!in_array($agencyAutoCreatorOnApproval, ['yes', 'no'], true)) {
                exit($LANG['invalid_agency_auto_creator_on_approval'] ?? 'invalid_agency_auto_creator_on_approval');
            }

            $savedMode = $iN->iN_SetSetting('registration_role_mode', $registrationRoleMode);
            $savedAutoCreator = $iN->iN_SetSetting('agency_auto_creator_on_approval', $agencyAutoCreatorOnApproval);
            if (!$savedMode || !$savedAutoCreator) {
                try {
                    DB::exec(
                        "CREATE TABLE IF NOT EXISTS i_settings_kv (
                            setting_key varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                            setting_value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                            updated_at int NOT NULL DEFAULT '0',
                            PRIMARY KEY (setting_key)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
                    );
                    if (!$savedMode) {
                        DB::exec(
                            "INSERT INTO i_settings_kv (setting_key, setting_value, updated_at) VALUES ('registration_role_mode', ?, ?)
                             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)",
                            [$registrationRoleMode, time()]
                        );
                        $savedMode = true;
                    }
                    if (!$savedAutoCreator) {
                        DB::exec(
                            "INSERT INTO i_settings_kv (setting_key, setting_value, updated_at) VALUES ('agency_auto_creator_on_approval', ?, ?)
                             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)",
                            [$agencyAutoCreatorOnApproval, time()]
                        );
                        $savedAutoCreator = true;
                    }
                } catch (Throwable $e) {
                }
            }
            if ($savedMode && $savedAutoCreator) {
                exit('200');
            }
            exit('404');
        }

        if ($type === 'agencyAddMember') {
            exit($LANG['noway_desc']);
        }

        if ($type === 'agencyRemoveMember') {
            exit($LANG['noway_desc']);
        }

        if ($type === 'agencyInviteCreator') {
            exit($LANG['noway_desc']);
        }

        if ($type === 'agencyRequestStatus') {
            exit($LANG['noway_desc']);
        }

        if ($type === 'bulkCreateCampaign') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            $targetType = $iN->iN_Secure($_POST['target_type'] ?? '');
            $targetCreatorId = isset($_POST['target_creator_iuid_fk']) ? (int)$iN->iN_Secure($_POST['target_creator_iuid_fk']) : 0;
            $targetAgencyId = isset($_POST['target_agency_id_fk']) ? (int)$iN->iN_Secure($_POST['target_agency_id_fk']) : 0;
            $messageText = trim($iN->iN_Secure($_POST['message_text'] ?? ''));
            $rateLimit = isset($_POST['rate_limit_per_run']) ? (int)$iN->iN_Secure($_POST['rate_limit_per_run']) : 25;
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
            if (!in_array($targetType, ['all_users', 'subscribers_of_creator', 'subscribers_of_agency_creators'], true)) {
                exit($LANG['bulk_invalid_target']);
            }
            if ($targetType === 'subscribers_of_creator' && $targetCreatorId <= 0) {
                exit($LANG['bulk_invalid_target_creator']);
            }
            if ($targetType === 'subscribers_of_agency_creators' && $targetAgencyId <= 0) {
                exit($LANG['bulk_invalid_target_agency']);
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
                [$uploadError, $attachmentPath] = iN_admin_handle_bulk_attachment_upload($_FILES['bulk_attachment'], $userID);
                if ($uploadError === 'invalid_format') {
                    exit($LANG['invalid_file_format']);
                }
                if ($uploadError === 'file_is_too_large') {
                    exit($LANG['file_is_too_large']);
                }
                if ($uploadError !== null || !$attachmentPath) {
                    exit($LANG['upload_failed']);
                }
            }
            $data = [
                'created_by_iuid_fk' => $userID,
                'target_type' => $targetType,
                'target_creator_iuid_fk' => $targetCreatorId > 0 ? $targetCreatorId : null,
                'target_agency_id_fk' => $targetAgencyId > 0 ? $targetAgencyId : null,
                'message_text' => $messageText,
                'attachment_path' => $attachmentPath,
                'private_price' => $privatePrice,
                'private_status' => $privateStatus === 1 ? 1 : 0,
                'rate_limit_per_run' => $rateLimit,
            ];
            $campaignId = $iN->iN_CreateBulkCampaign($data);
            if (!$campaignId) {
                exit($LANG['bulk_campaign_failed']);
            }
            exit('200');
        }

        if ($type === 'bulkBuildQueue') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            $campaignId = isset($_POST['bc_id']) ? (int)$iN->iN_Secure($_POST['bc_id']) : 0;
            if ($campaignId <= 0) {
                exit($LANG['bulk_campaign_invalid']);
            }
            $campaign = DB::one("SELECT * FROM i_bulk_campaigns WHERE bc_id = ? LIMIT 1", [$campaignId]);
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

        if ($type === 'bulkUpdateCampaignStatus') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            $campaignId = isset($_POST['bc_id']) ? (int)$iN->iN_Secure($_POST['bc_id']) : 0;
            $newStatus = $iN->iN_Secure($_POST['status'] ?? '');
            if ($campaignId <= 0 || !in_array($newStatus, ['paused', 'queued', 'canceled'], true)) {
                exit($LANG['bulk_campaign_invalid']);
            }
            $campaign = DB::one("SELECT bc_id FROM i_bulk_campaigns WHERE bc_id = ? LIMIT 1", [$campaignId]);
            if (!$campaign) {
                exit($LANG['bulk_campaign_invalid']);
            }
            DB::exec("UPDATE i_bulk_campaigns SET status = ?, updated_at = ? WHERE bc_id = ?", [$newStatus, time(), $campaignId]);
            exit('200');
        }

        if ($type === 'adminDeleteConversation') {
            include_once __DIR__ . '/../../../includes/csrf.php';
            if (!csrf_validate_from_request()) {
                exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
            }

            $chatID = isset($_POST['chat_id']) ? (int)$iN->iN_Secure($_POST['chat_id']) : 0;
            if ($chatID <= 0) {
                exit('404');
            }

            $chatExists = (bool)DB::col("SELECT 1 FROM i_chat_users WHERE chat_id = ? LIMIT 1", [$chatID]);
            if (!$chatExists) {
                exit('404');
            }

            try {
                DB::begin();
                DB::exec("DELETE FROM i_chat_conversations WHERE chat_id_fk = ?", [$chatID]);
                DB::exec("DELETE FROM i_chat_users WHERE chat_id = ?", [$chatID]);
                DB::commit();
                exit('200');
            } catch (Throwable $e) {
                DB::rollBack();
                exit($LANG['noway_desc']);
            }
        }

        if ($type === 'adminDeleteConversationMessage') {
            include_once __DIR__ . '/../../../includes/csrf.php';
            if (!csrf_validate_from_request()) {
                exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
            }

            $chatID = isset($_POST['chat_id']) ? (int)$iN->iN_Secure($_POST['chat_id']) : 0;
            $messageID = isset($_POST['message_id']) ? (int)$iN->iN_Secure($_POST['message_id']) : 0;
            if ($chatID <= 0 || $messageID <= 0) {
                exit('404');
            }

            $messageExists = (bool)DB::col(
                "SELECT 1 FROM i_chat_conversations WHERE con_id = ? AND chat_id_fk = ? LIMIT 1",
                [$messageID, $chatID]
            );
            if (!$messageExists) {
                exit('404');
            }

            DB::exec("DELETE FROM i_chat_conversations WHERE con_id = ? AND chat_id_fk = ?", [$messageID, $chatID]);

            $latestMessage = DB::one(
                "SELECT time FROM i_chat_conversations WHERE chat_id_fk = ? ORDER BY con_id DESC LIMIT 1",
                [$chatID]
            );
            $latestMessageTime = isset($latestMessage['time']) ? (int)$latestMessage['time'] : null;

            DB::exec(
                "UPDATE i_chat_users SET last_message_time = ? WHERE chat_id = ?",
                [$latestMessageTime, $chatID]
            );

            exit('200');
        }

        if ($type === 'creatorBulkSettings') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            $enabledRaw = $iN->iN_Secure($_POST['creator_bulk_messaging_enabled'] ?? '');
            $dailyLimitRaw = trim((string)($_POST['creator_bulk_daily_limit'] ?? ''));
            $bulkPriceMinRaw = isset($_POST['bulk_message_private_price_min']) ? trim((string)$_POST['bulk_message_private_price_min']) : '';
            $bulkPriceMaxRaw = isset($_POST['bulk_message_private_price_max']) ? trim((string)$_POST['bulk_message_private_price_max']) : '';
            if (!in_array($enabledRaw, ['0', '1'], true)) {
                $enabledRaw = '0';
            }
            if ($dailyLimitRaw === '' || !preg_match('/^[0-9]+$/', $dailyLimitRaw)) {
                exit($LANG['invalid_daily_limit']);
            }
            $dailyLimit = (int)$dailyLimitRaw;
            if ($dailyLimit < 0) {
                exit($LANG['invalid_daily_limit']);
            }
            $bulkPriceMin = is_numeric($bulkPriceMinRaw) ? (float)$bulkPriceMinRaw : 0.0;
            $bulkPriceMax = is_numeric($bulkPriceMaxRaw) ? (float)$bulkPriceMaxRaw : 0.0;
            if ($bulkPriceMin < 0) { $bulkPriceMin = 0.0; }
            if ($bulkPriceMax < 0) { $bulkPriceMax = 0.0; }
            if ($bulkPriceMax > 0 && $bulkPriceMax < $bulkPriceMin) { $bulkPriceMax = $bulkPriceMin; }
            $iN->iN_SetSetting('creator_bulk_messaging_enabled', $enabledRaw);
            $iN->iN_SetSetting('creator_bulk_daily_limit', $dailyLimit);
            $iN->iN_SetSetting('bulk_message_private_price_min', $bulkPriceMin);
            $iN->iN_SetSetting('bulk_message_private_price_max', $bulkPriceMax);
            exit('200');
        }

        if ($type === 'obsOverlaySettings') {
            if ($iN->iN_CheckIsAdmin($userID) != 1) {
                exit($LANG['noway_desc']);
            }
            if (!function_exists('csrf_validate_from_request') || !csrf_validate_from_request()) {
                exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
            }

            $moduleStatus = (string)$iN->iN_Secure($_POST['obs_overlay_module_status'] ?? 'yes');
            $maxTotalRaw = trim((string)($_POST['obs_overlay_max_total'] ?? ''));
            $maxActiveRaw = trim((string)($_POST['obs_overlay_max_active'] ?? ''));
            $autoRevokeRaw = (string)$iN->iN_Secure($_POST['obs_overlay_auto_revoke_oldest'] ?? '1');

            if (!in_array($moduleStatus, $yesNo, true)) {
                $moduleStatus = 'yes';
            }
            if (!preg_match('/^[0-9]+$/', $maxTotalRaw)) {
                exit($LANG['obs_overlay_admin_invalid_max_total'] ?? 'obs_overlay_admin_invalid_max_total');
            }
            if (!preg_match('/^[0-9]+$/', $maxActiveRaw)) {
                exit($LANG['obs_overlay_admin_invalid_max_active'] ?? 'obs_overlay_admin_invalid_max_active');
            }

            $maxTotal = (int)$maxTotalRaw;
            $maxActive = (int)$maxActiveRaw;
            if ($maxTotal < 1 || $maxTotal > 50) {
                exit($LANG['obs_overlay_admin_invalid_max_total'] ?? 'obs_overlay_admin_invalid_max_total');
            }
            if ($maxActive < 1 || $maxActive > 50) {
                exit($LANG['obs_overlay_admin_invalid_max_active'] ?? 'obs_overlay_admin_invalid_max_active');
            }

            if (!in_array($autoRevokeRaw, ['0', '1'], true)) {
                $autoRevokeRaw = '1';
            }

            $iN->iN_SetSetting('obs_overlay_module_status', $moduleStatus);
            $iN->iN_SetSetting('obs_overlay_max_total', $maxTotal);
            $iN->iN_SetSetting('obs_overlay_max_active', $maxActive);
            $iN->iN_SetSetting('obs_overlay_auto_revoke_oldest', $autoRevokeRaw);
            exit('200');
        }

			        if ($type === 'boostGeneralSettings') {
			            $rawDays = isset($_POST['boost_post_expire_days']) ? trim((string) $_POST['boost_post_expire_days']) : '';
		            if ($rawDays === '' || !preg_match('/^[0-9]+$/', $rawDays)) {
		                exit($LANG['invalid_boost_expire_days']);
		            }
		            $days = (int) $rawDays;
		            if ($days < 1 || $days > 365) {
		                exit($LANG['invalid_boost_expire_days']);
		            }
		            $visibility = isset($_POST['boosted_post_status']) ? (string) $_POST['boosted_post_status'] : '';
		            $updatedVisibility = true;
		            if ($visibility !== '') {
		                if (!in_array($visibility, $yesNo, true)) {
		                    exit($LANG['invalid_boosted_post_status']);
		                }
		                $updatedVisibility = $iN->iN_UpdateBoostedPostVisibilitySetting($userID, $visibility);
		            }

		            $updatedDays = $iN->iN_UpdateBoostExpireDaysSetting($userID, $days);
		            if ($updatedDays && $updatedVisibility) {
		                exit('200');
		            }
		            if (!$updatedDays) {
		                exit($LANG['boost_settings_sql_required']);
		            }
		            exit($LANG['boost_visibility_update_failed']);
		        }

	        if ($type === 'cleanupBoostedPosts') {
	            $result = $iN->iN_AdminCleanupBoostedPosts($userID);
	            if ($result === false) {
	                exit(iN_HelpSecure($LANG['noway_desc']));
	            }
	            header('Content-Type: application/json; charset=utf-8');
	            echo json_encode(['status' => '200', 'data' => $result]);
	            exit();
	        }

	        if ($type === 'cleanupAgencyBoosts') {
	            $result = $iN->iN_AdminCleanupAgencyBoosts($userID);
	            if ($result === false) {
	                exit(iN_HelpSecure($LANG['noway_desc']));
	            }
	            header('Content-Type: application/json; charset=utf-8');
	            echo json_encode(['status' => '200', 'data' => $result]);
	            exit();
	        }

	        if ($type === 'agencyBoostAdminDisable') {
	            $boostId = isset($_POST['boost_id']) ? (int)$iN->iN_Secure($_POST['boost_id']) : 0;
	            if ($boostId <= 0) {
	                exit($LANG['agency_boost_invalid']);
	            }
	            $disabled = $iN->iN_DisableAgencyBoost($boostId, $userID, true);
	            if (!$disabled) {
	                exit($LANG['agency_boost_disable_failed']);
	            }
	            exit('200');
	        }

	        $betaColumns = array(
	            'paypal_beta' => 'paypal_beta',
	            'bitpay_beta' => 'bitpay_beta',
	            'stripe_beta' => 'stripe_beta',
	            'authorizenet_beta' => 'authorizenet_beta',
            'iyzico_beta' => 'iyzico_beta',
            'razorpay_beta' => 'razorpay_beta',
            'paystack_beta' => 'paystack_beta',
            'flutterwave_beta' => 'flutterwave_beta',
            'ccbill_beta' => 'ccbill_beta',
            'coinpayment_beta' => 'coinpayments_beta',
            'mercadopago_beta' => 'mercadopago_beta',
            'moneroo_beta' => 'moneroo_beta',
            'nowpayments_beta' => 'nowpayments_beta',
            'paysafecard_beta' => 'paysafecard_beta',
            'bank_beta' => 'bank_beta'
        );
        if (isset($betaColumns[$type])) {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
            $mod = $iN->iN_Secure($_POST['mod']);
            $column = $betaColumns[$type];
            if ($iN->iN_UpdatePaymentBeta($userID, $column, $mod)) {
                exit('200');
            }
            echo iN_HelpSecure($LANG['noway_desc']);
        }
    }
        if ($type == 'license_init') {
            $envatoUsername = isset($_POST['envato_username']) ? trim((string) $_POST['envato_username']) : '';
            $purchaseCode = isset($_POST['purchase_code']) ? trim((string) $_POST['purchase_code']) : '';
            $envatoItemId = isset($_POST['envato_item_id']) ? (int) $_POST['envato_item_id'] : (int) LicenseHelper::ENVATO_ITEM_ID;
            if ($envatoUsername === '' || $purchaseCode === '') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'missing_fields']);
                exit();
            }
            $pending = LicenseHelper::createPendingState();
            if (!$pending) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'state_error']);
                exit();
            }
            $meta = LicenseHelper::siteMeta($base_url);
            $callbackUrl = route_url('requests/license-callback.php');
            $ts = time();
            $payload = [
                'envato_item_id' => $envatoItemId,
                'envato_username' => $envatoUsername,
                'purchase_code' => $purchaseCode,
                'site_domain' => $meta['domain'],
                'site_path' => $meta['path'],
                'callback_url' => $callbackUrl,
                'state' => $pending['state'],
                'nonce' => $pending['nonce'],
                'ts' => $ts,
            ];
            $sig = hash_hmac('sha256', LicenseHelper::canonicalize($payload), $pending['nonce']);
            $payload['sig'] = $sig;
            $activationUrl = LicenseCore::endpoint() . '?' . http_build_query($payload);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'ok', 'url' => $activationUrl]);
            exit();
        }
        if ($type == 'license_deactivate') {
            if (LicenseHelper::clearToken()) {
                unset($_SESSION['license_cache'], $_SESSION['license_fallback']);
                exit('200');
            }
            exit('error');
        }
        if ($type == 'updateCampaignStatus') {
            $campaignId = isset($_POST['campaign_id']) ? (int)$iN->iN_Secure($_POST['campaign_id']) : 0;
            $newStatus = isset($_POST['status']) ? (string)$iN->iN_Secure($_POST['status']) : '';
            $allowedStatus = array('active', 'rejected');
            if (!$campaignId || !in_array($newStatus, $allowedStatus, true)) {
                echo json_encode(array('status' => 'error'));
                exit;
            }
            $campaignRow = DB::one("SELECT campaign_id, post_id_fk, owner_uid_fk FROM i_campaigns WHERE campaign_id = ? LIMIT 1", [$campaignId]);
            if (!$campaignRow) {
                echo json_encode(array('status' => 'error'));
                exit;
            }
            $updated = $iN->iN_UpdateCampaignStatus($campaignId, $newStatus);
            if ($updated) {
                if (!empty($campaignRow['post_id_fk'])) {
                    if ($newStatus === 'active') {
                        DB::exec("UPDATE i_posts SET post_status = '1' WHERE post_id = ?", [(int)$campaignRow['post_id_fk']]);
                    } elseif ($newStatus === 'rejected') {
                        DB::exec("UPDATE i_posts SET post_status = '0' WHERE post_id = ?", [(int)$campaignRow['post_id_fk']]);
                    }
                }
                $iN->iN_InsertNotificationForCampaignDecision(
                    (int)$userID,
                    (int)$campaignRow['owner_uid_fk'],
                    (int)$campaignRow['campaign_id'],
                    $newStatus === 'active',
                    (int)$campaignRow['post_id_fk']
                );
                echo json_encode(array('status' => 'ok', 'label' => $newStatus));
            } else {
                echo json_encode(array('status' => 'error'));
            }
            exit;
        }
	if ($type == 'logoFile' || $type == 'faviconFile' || $type == 'nightLogoFile') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
				$theValidateType = $iN->iN_Secure($_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			if (is_array($fileReq) && !empty($fileReq)) {
				foreach ($fileReq as $iname => $value) {
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
								if (!is_uploaded_file($tmp)) {
									echo iN_HelpSecure($LANG['upload_failed']);
									continue;
								}
								$mimeType = $_FILES['uploading']['type'][$iname];
								$d = date('Y-m-d');
								$fileTypeIs = '';
								if (preg_match('/video\/*/', $mimeType)) {
									$fileTypeIs = 'video';
								} else if (preg_match('/image\/*/', $mimeType)) {
									$fileTypeIs = 'Image';
								}
								$primaryUploadDir = rtrim($uploadIconLogo, '/\\') . '/' . $d . '/';
								$fallbackUploadDir = rtrim($uploadFile, '/\\') . '/' . $d . '/';
								$targetUploadDir = $primaryUploadDir;
								$targetPublicPath = 'img/' . $d . '/';
								if (!is_dir($primaryUploadDir) && !@mkdir($primaryUploadDir, 0755, true) && !is_dir($primaryUploadDir)) {
									$targetUploadDir = $fallbackUploadDir;
									$targetPublicPath = 'uploads/files/' . $d . '/';
									if (!is_dir($fallbackUploadDir) && !@mkdir($fallbackUploadDir, 0755, true) && !is_dir($fallbackUploadDir)) {
										echo iN_HelpSecure($LANG['upload_failed']);
										continue;
									}
								}
								$pixelUploadDir = rtrim($xImages, '/\\') . '/' . $d . '/';
								if (!is_dir($pixelUploadDir)) {
									@mkdir($pixelUploadDir, 0755, true);
								}
								if (move_uploaded_file($tmp, $targetUploadDir . $getFilename)) {
									/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
									if ($fileTypeIs == 'Image') {
										$pathFile = $targetPublicPath . $getFilename;
										$UploadSourceUrl = $base_url . $targetPublicPath . $getFilename;
									}
									/* Auto-persist logo/favicon on successful upload so the admin does not need to click Save separately */
									if ($type == 'nightLogoFile') {
										$iN->iN_SetSetting('site_night_logo', $targetPublicPath . $getFilename);
									} else if ($type == 'logoFile') {
										try { DB::exec("UPDATE i_configurations SET site_logo = ? WHERE configuration_id = 1", [$targetPublicPath . $getFilename]); } catch (Throwable $e) {}
									} else if ($type == 'faviconFile') {
										try { DB::exec("UPDATE i_configurations SET site_favicon = ? WHERE configuration_id = 1", [$targetPublicPath . $getFilename]); } catch (Throwable $e) {}
									}
									echo $targetPublicPath . $getFilename;
								} else {
									echo iN_HelpSecure($LANG['upload_failed']);
								}
						} else {
							echo iN_HelpSecure($size);
						}
					}
				}
			}
		}
	}
		/*Update Site General Settings*/
		if ($type == 'updateGeneral') {
				$updateSiteLogo = $iN->iN_Secure($_POST['logo']);
				$updateSiteNightLogo = isset($_POST['night_logo']) ? $iN->iN_Secure($_POST['night_logo']) : '';
				$updateSiteFavicon = $iN->iN_Secure($_POST['favicon']);
				$updateWAtermark = $iN->iN_Secure($_POST['walogo']);
				$updateSiteKeywords = $iN->iN_Secure($_POST['site_keywords']);
				$updateSiteDescription = $iN->iN_Secure($_POST['site_description']);
				$updateSiteTitle = $iN->iN_Secure($_POST['site_title']);
				$updateSiteName = $iN->iN_Secure($_POST['site_name']);

                $allowedWatermarkPositions = array('top_left', 'top_right', 'bottom_left', 'bottom_right');
                $watermarkPosition = isset($_POST['watermark_position']) ? strtolower(trim((string)$_POST['watermark_position'])) : 'bottom_right';
                if (!in_array($watermarkPosition, $allowedWatermarkPositions, true)) {
                    $watermarkPosition = 'bottom_right';
                }

                $watermarkOpacity = isset($_POST['watermark_opacity']) ? (int)$_POST['watermark_opacity'] : 78;
                if ($watermarkOpacity < 10) {
                    $watermarkOpacity = 10;
                }
                if ($watermarkOpacity > 100) {
                    $watermarkOpacity = 100;
                }

                $watermarkSize = isset($_POST['watermark_size']) ? (int)$_POST['watermark_size'] : 19;
                if ($watermarkSize < 8) {
                    $watermarkSize = 8;
                }
                if ($watermarkSize > 40) {
                    $watermarkSize = 40;
                }

			$updateSiteConfirugarion = $iN->iN_UpdateSiteConfiguration(
                    $userID,
                    $iN->iN_Secure($updateWAtermark),
                    $iN->iN_Secure($updateSiteLogo),
                    $iN->iN_Secure($updateSiteFavicon),
                    $iN->iN_Secure($updateSiteKeywords),
                    $iN->iN_Secure($updateSiteDescription),
                    $iN->iN_Secure($updateSiteTitle),
                    $iN->iN_Secure($updateSiteName),
                    $iN->iN_Secure($watermarkPosition),
                    $iN->iN_Secure((string)$watermarkOpacity),
                    $iN->iN_Secure((string)$watermarkSize),
                    $iN->iN_Secure($updateSiteNightLogo)
                );
			if ($updateSiteConfirugarion) {
				exit('200');
			} else {
				echo '404';
			}
	}
	/*Update Site Business Informations*/
	if ($type == 'updateBusiness') {
		$updateSiteCampanyName = $iN->iN_Secure($_POST['site_campany']);
		$updateSiteCountry = $iN->iN_Secure($_POST['country_code']);
		$updateSiteCity = $iN->iN_Secure($_POST['site_city']);
		$updateSiteBusinessAddress = $iN->iN_Secure($_POST['site_business_address']);
		$updateSitePostCode = $iN->iN_Secure($_POST['site_post_code']);
		$updateSiteVAT = $iN->iN_Secure($_POST['site_vat']);
		if (empty($updateSiteCampanyName) || empty($updateSiteCountry) || empty($updateSiteCity) || empty($updateSiteBusinessAddress) || empty($updateSitePostCode) || empty($updateSiteVAT)) {
			exit('1');
		}
		$updateSiteBusinessInformations = $iN->iN_UpdateSiteBusinessInformations($userID, $iN->iN_Secure($updateSiteCampanyName), $iN->iN_Secure($updateSiteCountry), $iN->iN_Secure($updateSiteCity), $iN->iN_Secure($updateSiteBusinessAddress), $iN->iN_Secure($updateSitePostCode), $iN->iN_Secure($updateSiteVAT));
		if ($updateSiteBusinessInformations) {
			exit('200');
		} else {
			echo '404';
		}
	}
    if ($type == 'updateGenderOptions') {
        $rawInput = isset($_POST['gender_options']) ? trim($_POST['gender_options']) : '';
        $hasCustomInput = $rawInput !== '';
        $lines = preg_split('/\r\n|\r|\n/', $rawInput);
        $parsed = [];
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') { continue; }
                $parts = array_map('trim', explode('|', $line));
                $key = strtolower(preg_replace('/[^a-z0-9_]/', '', $parts[0] ?? ''));
                if ($key === '' || isset($parsed[$key])) { continue; }
                $label = $parts[1] ?? ucfirst($key);
                $label = trim($label) !== '' ? trim($label) : ucfirst($key);
                $label = strip_tags($label);
                if (strlen($label) > 60) {
                    $label = substr($label, 0, 60);
                }
                $icon = $parts[2] ?? '';
                $icon = preg_replace('/[^0-9]/', '', $icon);
                $statusRaw = $parts[3] ?? '1';
                $statusRaw = strtolower(trim($statusRaw));
                $status = in_array($statusRaw, ['0', 'off', 'no', 'false'], true) ? '0' : '1';
                $parsed[$key] = [
                    'key'   => $key,
                    'label' => $label,
                    'icon'  => $icon,
                    'status'=> $status,
                ];
            }
        }
        if (empty($parsed)) {
            if ($hasCustomInput) {
                exit('invalid');
            }
            $parsed = [];
            foreach ($defaultGenderOptions as $option) {
                $parsed[$option['key']] = $option;
            }
        }
        $parsed = array_values($parsed);
        $genderOptionsPath = __DIR__ . '/../../../includes/gender_options.php';
        $export = "<?php\nreturn " . var_export($parsed, true) . ";\n";
        if (@file_put_contents($genderOptionsPath, $export, LOCK_EX) === false) {
            exit('500');
        }
        $genderOptions = $parsed;
        $genders = array_column($genderOptions, 'key');
        if (empty($genders)) {
            $genderOptions = $defaultGenderOptions;
            $genders = array_column($genderOptions, 'key');
        }
        exit('200');
    }
	if ($type == 'ageVerifSettings') {
		$selectedProviderRaw = trim((string)($_POST['age_verif_provider'] ?? 'ageverif'));
		$selectedProvider = in_array($selectedProviderRaw, ['ageverif', 'yoti', 'didit'], true) ? $selectedProviderRaw : 'ageverif';

		$ageStatusRaw = isset($_POST['age_verif_status']) ? (string)$_POST['age_verif_status'] : '0';
		$ageStatus = in_array($ageStatusRaw, ['0', '1'], true) ? $ageStatusRaw : '0';
		$forceRaw = isset($_POST['age_verif_force_sitewide']) ? (string)$_POST['age_verif_force_sitewide'] : '0';
		$forceSitewide = in_array($forceRaw, ['0', '1'], true) ? $forceRaw : '0';
		$environmentRaw = isset($_POST['age_verif_environment']) ? (string)$_POST['age_verif_environment'] : 'live';
		$environment = $environmentRaw === 'test' ? 'test' : 'live';
		$clientIdLive = trim((string)($_POST['age_verif_client_id'] ?? ''));
		$clientSecretInputLive = trim((string)($_POST['age_verif_client_secret'] ?? ''));
		$authorizeUrlLive = trim((string)($_POST['age_verif_authorize_url'] ?? ''));
		$tokenUrlLive = trim((string)($_POST['age_verif_token_url'] ?? ''));
		$verifyUrlLive = trim((string)($_POST['age_verif_verify_url'] ?? ''));
		$scopeLive = trim((string)($_POST['age_verif_scope'] ?? ''));
		$clientIdTest = trim((string)($_POST['age_verif_client_id_test'] ?? ''));
		$clientSecretInputTest = trim((string)($_POST['age_verif_client_secret_test'] ?? ''));
		$authorizeUrlTest = trim((string)($_POST['age_verif_authorize_url_test'] ?? ''));
		$tokenUrlTest = trim((string)($_POST['age_verif_token_url_test'] ?? ''));
		$verifyUrlTest = trim((string)($_POST['age_verif_verify_url_test'] ?? ''));
		$scopeTest = trim((string)($_POST['age_verif_scope_test'] ?? ''));
		$minAge = isset($_POST['age_verif_min_age']) ? (int)$_POST['age_verif_min_age'] : 18;
		if ($minAge < 18) {
			$minAge = 18;
		} elseif ($minAge > 60) {
			$minAge = 60;
		}
		$currentSecretLive = isset($ageVerifClientSecretLive) ? (string)$ageVerifClientSecretLive : (isset($ageVerifClientSecret) ? (string)$ageVerifClientSecret : '');
		$currentSecretTest = isset($ageVerifClientSecretTest) ? (string)$ageVerifClientSecretTest : '';
		$effectiveSecretLive = $clientSecretInputLive !== '' ? $clientSecretInputLive : $currentSecretLive;
		$effectiveSecretTest = $clientSecretInputTest !== '' ? $clientSecretInputTest : $currentSecretTest;

		$yotiStatusRaw = isset($_POST['yoti_age_verif_status']) ? (string)$_POST['yoti_age_verif_status'] : '0';
		$yotiStatus = in_array($yotiStatusRaw, ['0', '1'], true) ? $yotiStatusRaw : '0';
		$yotiForceRaw = isset($_POST['yoti_age_verif_force_sitewide']) ? (string)$_POST['yoti_age_verif_force_sitewide'] : '0';
		$yotiForceSitewide = in_array($yotiForceRaw, ['0', '1'], true) ? $yotiForceRaw : '0';
		$yotiEnvironment = 'live';
		$yotiClientIdLive = trim((string)($_POST['yoti_age_verif_client_id'] ?? ''));
		$yotiClientSecretInputLive = trim((string)($_POST['yoti_age_verif_client_secret'] ?? ''));
		$yotiAuthorizeUrlLive = '';
		$yotiTokenUrlLive = '';
		$yotiVerifyUrlLive = '';
		$yotiScopeLive = '';
		$yotiClientIdTest = '';
		$yotiClientSecretInputTest = '';
		$yotiAuthorizeUrlTest = '';
		$yotiTokenUrlTest = '';
		$yotiVerifyUrlTest = '';
		$yotiScopeTest = '';
		$yotiMinAge = isset($_POST['yoti_age_verif_min_age']) ? (int)$_POST['yoti_age_verif_min_age'] : 19;
		if ($yotiMinAge < 19) {
			$yotiMinAge = 19;
		} elseif ($yotiMinAge > 25) {
			$yotiMinAge = 25;
		}
		$yotiCurrentSecretLive = isset($yotiAgeVerifClientSecretLive) ? (string)$yotiAgeVerifClientSecretLive : (isset($yotiAgeVerifClientSecret) ? (string)$yotiAgeVerifClientSecret : '');
		$yotiCurrentSecretTest = isset($yotiAgeVerifClientSecretTest) ? (string)$yotiAgeVerifClientSecretTest : '';
		$yotiEffectiveSecretLive = $yotiClientSecretInputLive !== '' ? $yotiClientSecretInputLive : $yotiCurrentSecretLive;
		$yotiEffectiveSecretTest = $yotiClientSecretInputTest !== '' ? $yotiClientSecretInputTest : $yotiCurrentSecretTest;

		$diditStatusRaw = isset($_POST['didit_age_verif_status']) ? (string)$_POST['didit_age_verif_status'] : '0';
		$diditStatus = in_array($diditStatusRaw, ['0', '1'], true) ? $diditStatusRaw : '0';
		$diditForceRaw = isset($_POST['didit_age_verif_force_sitewide']) ? (string)$_POST['didit_age_verif_force_sitewide'] : '0';
		$diditForceSitewide = in_array($diditForceRaw, ['0', '1'], true) ? $diditForceRaw : '0';
		$diditEnvironmentRaw = isset($_POST['didit_age_verif_environment']) ? (string)$_POST['didit_age_verif_environment'] : 'live';
		$diditEnvironment = $diditEnvironmentRaw === 'test' ? 'test' : 'live';
		$diditClientIdLive = trim((string)($_POST['didit_age_verif_client_id'] ?? ''));
		$diditClientSecretInputLive = trim((string)($_POST['didit_age_verif_client_secret'] ?? ''));
		$diditAuthorizeUrlLive = trim((string)($_POST['didit_age_verif_authorize_url'] ?? ''));
		$diditClientIdTest = trim((string)($_POST['didit_age_verif_client_id_test'] ?? ''));
		$diditClientSecretInputTest = trim((string)($_POST['didit_age_verif_client_secret_test'] ?? ''));
		$diditAuthorizeUrlTest = trim((string)($_POST['didit_age_verif_authorize_url_test'] ?? ''));
		$diditMinAge = isset($_POST['didit_age_verif_min_age']) ? (int)$_POST['didit_age_verif_min_age'] : 18;
		if ($diditMinAge < 18) {
			$diditMinAge = 18;
		} elseif ($diditMinAge > 60) {
			$diditMinAge = 60;
		}
		$diditCurrentSecretLive = isset($diditAgeVerifClientSecretLive) ? (string)$diditAgeVerifClientSecretLive : (isset($diditAgeVerifClientSecret) ? (string)$diditAgeVerifClientSecret : '');
		$diditCurrentSecretTest = isset($diditAgeVerifClientSecretTest) ? (string)$diditAgeVerifClientSecretTest : '';
		$diditEffectiveSecretLive = $diditClientSecretInputLive !== '' ? $diditClientSecretInputLive : $diditCurrentSecretLive;
		$diditEffectiveSecretTest = $diditClientSecretInputTest !== '' ? $diditClientSecretInputTest : $diditCurrentSecretTest;

		$configColumnsOk = method_exists($iN, 'iN_ConfigColumnsExist') && $iN->iN_ConfigColumnsExist([
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
			'age_verif_min_age',
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
			'yoti_age_verif_min_age',
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
		]);
		$userColumnsOk = true;
		try {
			$userColumns = ['age_verify_status', 'age_verify_provider', 'age_verified_at', 'age_verify_ref'];
			$placeholders = implode(',', array_fill(0, count($userColumns), '?'));
			$params = array_merge(['i_users'], $userColumns);
			$count = DB::col(
				"SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN ($placeholders)",
				$params
			);
			$userColumnsOk = (int)$count === count($userColumns);
		} catch (Throwable $e) {
			$userColumnsOk = false;
		}

		if (!$configColumnsOk || !$userColumnsOk) {
			exit('columns_missing');
		}

		$providers = [
			'ageverif' => [
				'status' => $ageStatus,
				'environment' => $environment,
				'live' => [
					'client_id' => $clientIdLive,
					'client_secret' => $effectiveSecretLive,
					'authorize_url' => $authorizeUrlLive,
					'token_url' => $tokenUrlLive,
					'verify_url' => $verifyUrlLive
				],
				'test' => [
					'client_id' => $clientIdTest,
					'client_secret' => $effectiveSecretTest,
					'authorize_url' => $authorizeUrlTest,
					'token_url' => $tokenUrlTest,
					'verify_url' => $verifyUrlTest
				]
			],
			'yoti' => [
				'status' => $yotiStatus,
				'environment' => $yotiEnvironment,
				'live' => [
					'client_id' => $yotiClientIdLive,
					'client_secret' => $yotiEffectiveSecretLive,
					'authorize_url' => $yotiAuthorizeUrlLive,
					'token_url' => $yotiTokenUrlLive,
					'verify_url' => $yotiVerifyUrlLive
				],
				'test' => [
					'client_id' => $yotiClientIdTest,
					'client_secret' => $yotiEffectiveSecretTest,
					'authorize_url' => $yotiAuthorizeUrlTest,
					'token_url' => $yotiTokenUrlTest,
					'verify_url' => $yotiVerifyUrlTest
				]
			],
			'didit' => [
				'status' => $diditStatus,
				'environment' => $diditEnvironment,
				'live' => [
					'client_id' => $diditClientIdLive,
					'client_secret' => $diditEffectiveSecretLive,
					'authorize_url' => $diditAuthorizeUrlLive
				],
				'test' => [
					'client_id' => $diditClientIdTest,
					'client_secret' => $diditEffectiveSecretTest,
					'authorize_url' => $diditAuthorizeUrlTest
				]
			]
		];
		$validateProvider = function (string $providerKey, array $providerData) use ($selectedProvider): ?string {
			$isSelected = $providerKey === $selectedProvider;
			$environment = $providerData['environment'] === 'test' ? 'test' : 'live';
			$envData = $environment === 'test' ? $providerData['test'] : $providerData['live'];
			$suffix = $environment === 'test' ? '_test' : '';
			if ($providerKey === 'yoti') {
				if ($isSelected && $providerData['status'] === '1') {
					if ($envData['client_id'] === '') {
						return $providerKey . '_missing_client_id';
					}
					if ($envData['client_secret'] === '') {
						return $providerKey . '_missing_client_secret';
					}
				}
				return null;
			}
			if ($providerKey === 'didit') {
				if ($isSelected && $providerData['status'] === '1') {
					if ($envData['client_id'] === '') {
						return 'didit_missing_api_key';
					}
					if (strlen($envData['client_id']) < 20) {
						return 'didit_invalid_api_key';
					}
					if ($envData['client_secret'] === '') {
						return 'didit_missing_webhook_secret';
					}
					if (strlen($envData['client_secret']) < 16) {
						return 'didit_invalid_webhook_secret';
					}
					if ($envData['authorize_url'] === '') {
						return 'didit_missing_workflow_id';
					}
					if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $envData['authorize_url'])) {
						return 'didit_invalid_workflow_id';
					}
				}
				return null;
			}
			if ($isSelected && $providerData['status'] === '1') {
				if ($envData['client_id'] === '') {
					return $providerKey . '_missing_client_id' . $suffix;
				}
				if ($envData['client_secret'] === '') {
					return $providerKey . '_missing_client_secret' . $suffix;
				}
				if ($envData['authorize_url'] === '') {
					return $providerKey . '_missing_authorize_url' . $suffix;
				}
				if ($envData['token_url'] === '') {
					return $providerKey . '_missing_token_url' . $suffix;
				}
				if (filter_var($envData['authorize_url'], FILTER_VALIDATE_URL) === false) {
					return $providerKey . '_invalid_authorize_url' . $suffix;
				}
				if (filter_var($envData['token_url'], FILTER_VALIDATE_URL) === false) {
					return $providerKey . '_invalid_token_url' . $suffix;
				}
				if ($envData['verify_url'] !== '' && filter_var($envData['verify_url'], FILTER_VALIDATE_URL) === false) {
					return $providerKey . '_invalid_verify_url' . $suffix;
				}
			} else {
				if ($envData['authorize_url'] !== '' && filter_var($envData['authorize_url'], FILTER_VALIDATE_URL) === false) {
					return $providerKey . '_invalid_authorize_url' . $suffix;
				}
				if ($envData['token_url'] !== '' && filter_var($envData['token_url'], FILTER_VALIDATE_URL) === false) {
					return $providerKey . '_invalid_token_url' . $suffix;
				}
				if ($envData['verify_url'] !== '' && filter_var($envData['verify_url'], FILTER_VALIDATE_URL) === false) {
					return $providerKey . '_invalid_verify_url' . $suffix;
				}
			}
			return null;
		};
		$validationError = $validateProvider($selectedProvider, $providers[$selectedProvider]);
		if ($validationError !== null) {
			exit($validationError);
		}

		$updated = $iN->iN_SetSetting('age_verif_provider', $selectedProvider)
			&& $iN->iN_SetSetting('age_verif_status', $ageStatus)
			&& $iN->iN_SetSetting('age_verif_force_sitewide', $forceSitewide)
			&& $iN->iN_SetSetting('age_verif_environment', $environment)
			&& $iN->iN_SetSetting('age_verif_client_id', $clientIdLive)
			&& $iN->iN_SetSetting('age_verif_authorize_url', $authorizeUrlLive)
			&& $iN->iN_SetSetting('age_verif_token_url', $tokenUrlLive)
			&& $iN->iN_SetSetting('age_verif_verify_url', $verifyUrlLive)
			&& $iN->iN_SetSetting('age_verif_scope', $scopeLive)
			&& $iN->iN_SetSetting('age_verif_client_id_test', $clientIdTest)
			&& $iN->iN_SetSetting('age_verif_authorize_url_test', $authorizeUrlTest)
			&& $iN->iN_SetSetting('age_verif_token_url_test', $tokenUrlTest)
			&& $iN->iN_SetSetting('age_verif_verify_url_test', $verifyUrlTest)
			&& $iN->iN_SetSetting('age_verif_scope_test', $scopeTest)
			&& $iN->iN_SetSetting('age_verif_min_age', $minAge)
			&& $iN->iN_SetSetting('yoti_age_verif_status', $yotiStatus)
			&& $iN->iN_SetSetting('yoti_age_verif_force_sitewide', $yotiForceSitewide)
			&& $iN->iN_SetSetting('yoti_age_verif_environment', $yotiEnvironment)
			&& $iN->iN_SetSetting('yoti_age_verif_client_id', $yotiClientIdLive)
			&& $iN->iN_SetSetting('yoti_age_verif_authorize_url', $yotiAuthorizeUrlLive)
			&& $iN->iN_SetSetting('yoti_age_verif_token_url', $yotiTokenUrlLive)
			&& $iN->iN_SetSetting('yoti_age_verif_verify_url', $yotiVerifyUrlLive)
			&& $iN->iN_SetSetting('yoti_age_verif_scope', $yotiScopeLive)
			&& $iN->iN_SetSetting('yoti_age_verif_client_id_test', $yotiClientIdTest)
			&& $iN->iN_SetSetting('yoti_age_verif_authorize_url_test', $yotiAuthorizeUrlTest)
			&& $iN->iN_SetSetting('yoti_age_verif_token_url_test', $yotiTokenUrlTest)
			&& $iN->iN_SetSetting('yoti_age_verif_verify_url_test', $yotiVerifyUrlTest)
			&& $iN->iN_SetSetting('yoti_age_verif_scope_test', $yotiScopeTest)
			&& $iN->iN_SetSetting('yoti_age_verif_min_age', $yotiMinAge)
			&& $iN->iN_SetSetting('didit_age_verif_status', $diditStatus)
			&& $iN->iN_SetSetting('didit_age_verif_force_sitewide', $diditForceSitewide)
			&& $iN->iN_SetSetting('didit_age_verif_environment', $diditEnvironment)
			&& $iN->iN_SetSetting('didit_age_verif_client_id', $diditClientIdLive)
			&& $iN->iN_SetSetting('didit_age_verif_authorize_url', $diditAuthorizeUrlLive)
			&& $iN->iN_SetSetting('didit_age_verif_client_id_test', $diditClientIdTest)
			&& $iN->iN_SetSetting('didit_age_verif_authorize_url_test', $diditAuthorizeUrlTest)
			&& $iN->iN_SetSetting('didit_age_verif_min_age', $diditMinAge);

		if ($clientSecretInputLive !== '') {
			$updated = $updated && $iN->iN_SetSetting('age_verif_client_secret', $clientSecretInputLive);
		}
		if ($clientSecretInputTest !== '') {
			$updated = $updated && $iN->iN_SetSetting('age_verif_client_secret_test', $clientSecretInputTest);
		}
		if ($yotiClientSecretInputLive !== '') {
			$updated = $updated && $iN->iN_SetSetting('yoti_age_verif_client_secret', $yotiClientSecretInputLive);
		}
		if ($yotiClientSecretInputTest !== '') {
			$updated = $updated && $iN->iN_SetSetting('yoti_age_verif_client_secret_test', $yotiClientSecretInputTest);
		}
		if ($diditClientSecretInputLive !== '') {
			$updated = $updated && $iN->iN_SetSetting('didit_age_verif_client_secret', $diditClientSecretInputLive);
		}
		if ($diditClientSecretInputTest !== '') {
			$updated = $updated && $iN->iN_SetSetting('didit_age_verif_client_secret_test', $diditClientSecretInputTest);
		}

		if ($updated) {
			exit('200');
		}
		exit('404');
	}
	if ($type == 'updateLimits') {
            $fileLimit = $iN->iN_Secure($_POST['file_limit']);
		$lengthLimit = $iN->iN_Secure($_POST['length_limit']);
		$postShowLimit = $iN->iN_Secure($_POST['post_show_limit']);
		$paginatonLimit = $iN->iN_Secure($_POST['pagination_limit']);
		$approvalFileExtension = $iN->iN_Secure($_POST['available_verification_file_extensions']);
		$availableUploadFileExtensions = $iN->iN_Secure($_POST['available_file_extensions']);
		$bulkMessageFileExtensions = isset($_POST['available_bulk_message_file_extensions']) ? $iN->iN_Secure($_POST['available_bulk_message_file_extensions']) : '';
		$bulkMessagePriceMinRaw = isset($_POST['bulk_message_private_price_min'])
			? $iN->iN_Secure($_POST['bulk_message_private_price_min'])
			: (string)($bulkMessagePrivatePriceMin ?? '0');
		$bulkMessagePriceMaxRaw = isset($_POST['bulk_message_private_price_max'])
			? $iN->iN_Secure($_POST['bulk_message_private_price_max'])
			: (string)($bulkMessagePrivatePriceMax ?? '0');
		$unavailableUsernames = $iN->iN_Secure($_POST['unavailable_usernames']);
            $ffmpeg_path = $iN->iN_Secure($_POST['ffmpeg_path']);
            $ffprobe_path = isset($_POST['ffprobe_path']) ? $iN->iN_Secure($_POST['ffprobe_path']) : '';
            $storyAudioUploadMode = isset($_POST['story_audio_upload_mode'])
                ? (string)$iN->iN_Secure($_POST['story_audio_upload_mode'])
                : (string)($storyAudioUploadMode ?? 'mp3_only');
            if (!in_array($storyAudioUploadMode, ['mp3_only', 'ffmpeg_mp3'], true)) {
                $storyAudioUploadMode = 'mp3_only';
            }
		$postCreateStatus = $iN->iN_Secure($_POST['postCreateStatus']);
			$reCaptchaStatusRaw = isset($_POST['reCreateStatus']) ? (string)$_POST['reCreateStatus'] : 'no';
			$reCaptchaStatusRaw = strtolower(trim(html_entity_decode($reCaptchaStatusRaw, ENT_QUOTES, 'UTF-8')));
			$reCaptchaStatusRaw = str_replace('\\', '', $reCaptchaStatusRaw);
			$reCaptchaStatusRaw = trim($reCaptchaStatusRaw, "\"' \t\n\r\0\x0B");
			$reCaptchaStatus = in_array($reCaptchaStatusRaw, ['yes', 'no'], true) ? $reCaptchaStatusRaw : 'no';
		$blockCountryStatus = $iN->iN_Secure($_POST['blockCountriesStatus']);
		$reCaptchaSiteKey = $iN->iN_Secure($_POST['rsitekey']);
		$reCaptchaSecretKey = $iN->iN_Secure($_POST['rseckey']);
		$oneSignalApiKey = $iN->iN_Secure($_POST['onesignalapikey']);
		$oneSignalRestApiKey = $iN->iN_Secure($_POST['onesignalrestapikey']);
		$oneSignalStatus = isset($_POST['oneSignalStatus']) ? $_POST['oneSignalStatus'] : 'close';
		$reelsFeatureStatus = isset($_POST['reels_feature_status']) ? $iN->iN_Secure($_POST['reels_feature_status']) : '0';
		$maxVideoDuration = isset($_POST['max_video_duration']) ? $iN->iN_Secure($_POST['max_video_duration']) : '15';
		$messageLimit = $iN->iN_Secure($_POST['message_show_limit']);
		$adsShowLimit = $iN->iN_Secure($_POST['ads_show_limit']);
		$sugUserShowLimit = $iN->iN_Secure($_POST['suggu_show_limit']);
		$sugProductShowLimit = $iN->iN_Secure($_POST['prod_show_limit']);
		$TrendPostShowLimit = $iN->iN_Secure($_POST['trend_show_limit']);
		$friendActivityShowLimit = $iN->iN_Secure($_POST['activity_show_limit']);
		$friendActivityShowTimeLimit = $iN->iN_Secure($_POST['activity_show_time_limit']);
        $pollSystemStatus = isset($_POST['poll_system_status']) ? $iN->iN_Secure($_POST['poll_system_status']) : '1';
        $pollMaxOptions = isset($_POST['poll_max_options']) ? (int)$iN->iN_Secure($_POST['poll_max_options']) : 6;
        $pollMinOptions = isset($_POST['poll_min_options']) ? (int)$iN->iN_Secure($_POST['poll_min_options']) : 2;
        if ($pollMinOptions < 2) { $pollMinOptions = 2; }
        if ($pollMaxOptions < $pollMinOptions) { $pollMaxOptions = $pollMinOptions; }
        $scheduledPostsStatus = isset($_POST['scheduled_posts_status']) ? $iN->iN_Secure($_POST['scheduled_posts_status']) : '0';
        $scheduledMaxDelayDays = isset($_POST['scheduled_max_delay_days']) ? (int)$iN->iN_Secure($_POST['scheduled_max_delay_days']) : 30;
        if ($scheduledMaxDelayDays < 1) { $scheduledMaxDelayDays = 30; }
        if ($scheduledMaxDelayDays > 30) { $scheduledMaxDelayDays = 30; }
        $quickActionsLayout = isset($_POST['quick_actions_layout']) ? strtolower(trim((string)$iN->iN_Secure($_POST['quick_actions_layout']))) : 'popup';
        if (!in_array($quickActionsLayout, ['popup', 'inline'], true)) {
            $quickActionsLayout = 'popup';
        }
        $campaignFeatureStatus = isset($_POST['campaign_feature_status']) ? (string)$iN->iN_Secure($_POST['campaign_feature_status']) : '0';
        $campaignTitleRequired = isset($_POST['campaign_title_required']) ? (string)$iN->iN_Secure($_POST['campaign_title_required']) : '1';
        $campaignGoalRequired = isset($_POST['campaign_goal_required']) ? (string)$iN->iN_Secure($_POST['campaign_goal_required']) : '1';
        $campaignDeadlineRequired = isset($_POST['campaign_deadline_required']) ? (string)$iN->iN_Secure($_POST['campaign_deadline_required']) : '1';
        $campaignCreatorPolicy = isset($_POST['campaign_creator_policy']) ? strtolower(trim((string)$iN->iN_Secure($_POST['campaign_creator_policy']))) : 'all';
        if (!in_array($campaignCreatorPolicy, ['all', 'verified', 'admin_only'], true)) {
            $campaignCreatorPolicy = 'all';
        }
        $campaignGoalMin = isset($_POST['campaign_goal_min']) ? (float)$iN->iN_Secure($_POST['campaign_goal_min']) : 50.00;
        if ($campaignGoalMin < 50) { $campaignGoalMin = 50.00; }
        $campaignGoalMax = isset($_POST['campaign_goal_max']) ? (float)$iN->iN_Secure($_POST['campaign_goal_max']) : 100000.00;
        if ($campaignGoalMax < 100) { $campaignGoalMax = 100.00; }
        $blogFeatureStatus = isset($_POST['blog_feature_status']) ? (string)$iN->iN_Secure($_POST['blog_feature_status']) : '1';
        $blogFeatureStatus = $blogFeatureStatus === '1' ? '1' : '0';
        $blogReactionsStatus = isset($_POST['blog_reactions_status']) ? (string)$iN->iN_Secure($_POST['blog_reactions_status']) : '1';
        $blogReactionsStatus = $blogReactionsStatus === '1' ? '1' : '0';
        $blogShareStatus = isset($_POST['blog_share_status']) ? (string)$iN->iN_Secure($_POST['blog_share_status']) : '1';
        $blogShareStatus = $blogShareStatus === '1' ? '1' : '0';
        $blogSidebarAdsStatus = isset($_POST['blog_sidebar_ads_status']) ? (string)$iN->iN_Secure($_POST['blog_sidebar_ads_status']) : '1';
        $blogSidebarAdsStatus = $blogSidebarAdsStatus === '1' ? '1' : '0';
        $trendHashtagsStatus = isset($_POST['trend_hashtags_status']) ? (string)$iN->iN_Secure($_POST['trend_hashtags_status']) : '1';
        $trendHashtagsStatus = $trendHashtagsStatus === '1' ? '1' : '0';
        $trendHashtagsLimit = isset($_POST['trend_hashtags_limit']) ? (int)$iN->iN_Secure($_POST['trend_hashtags_limit']) : 10;
        if ($trendHashtagsLimit < 1) { $trendHashtagsLimit = 1; }
        if ($trendHashtagsLimit > 30) { $trendHashtagsLimit = 30; }
        $storyDefaultReactionsRaw = isset($_POST['story_default_reactions']) ? trim((string)$_POST['story_default_reactions']) : '';
        $storyDefaultReactions = $iN->iN_StoryReactionSetToString(
            $iN->iN_NormalizeStoryReactionSet($storyDefaultReactionsRaw, $iN->iN_GetStoryDefaultReactions())
        );
        $webPushStatus = isset($_POST['web_push_status']) ? (string)$iN->iN_Secure($_POST['web_push_status']) : (string)$iN->iN_GetSetting('web_push_status', '0');
        $webPushStatus = $webPushStatus === '1' ? '1' : '0';
        $webPushVapidSubject = isset($_POST['web_push_vapid_subject'])
            ? trim((string)$iN->iN_Secure($_POST['web_push_vapid_subject']))
            : trim((string)$iN->iN_GetSetting('web_push_vapid_subject', 'mailto:admin@localhost'));
        if ($webPushVapidSubject === '') {
            $webPushVapidSubject = 'mailto:admin@localhost';
        }
        $subjectIsMailto = false;
        if (stripos($webPushVapidSubject, 'mailto:') === 0) {
            $mailTarget = trim((string)substr($webPushVapidSubject, 7));
            $subjectIsMailto = $mailTarget !== ''
                && (filter_var($mailTarget, FILTER_VALIDATE_EMAIL) !== false
                    || preg_match('/^[a-zA-Z0-9._%+\-]+@localhost$/i', $mailTarget));
        }
        $subjectIsHttps = (stripos($webPushVapidSubject, 'https://') === 0)
            && filter_var($webPushVapidSubject, FILTER_VALIDATE_URL) !== false;
        $subjectIsHttp = (stripos($webPushVapidSubject, 'http://localhost') === 0);
        if (!$subjectIsMailto && !$subjectIsHttps && !$subjectIsHttp) {
            exit('webpush_subject_invalid');
        }
        $webPushTtl = isset($_POST['web_push_ttl']) ? (int)$iN->iN_Secure($_POST['web_push_ttl']) : (int)$iN->iN_GetSetting('web_push_ttl', 60);
        if ($webPushTtl < 30) { $webPushTtl = 30; }
        if ($webPushTtl > 86400) { $webPushTtl = 86400; }
        $webPushEventLiveStarted = isset($_POST['web_push_event_live_started']) ? (string)$iN->iN_Secure($_POST['web_push_event_live_started']) : (string)$iN->iN_GetSetting('web_push_event_live_started', '1');
        $webPushEventTipReceived = isset($_POST['web_push_event_tip_received']) ? (string)$iN->iN_Secure($_POST['web_push_event_tip_received']) : (string)$iN->iN_GetSetting('web_push_event_tip_received', '1');
        $webPushEventPostLike = isset($_POST['web_push_event_post_like']) ? (string)$iN->iN_Secure($_POST['web_push_event_post_like']) : (string)$iN->iN_GetSetting('web_push_event_post_like', '1');
        $webPushEventComment = isset($_POST['web_push_event_comment']) ? (string)$iN->iN_Secure($_POST['web_push_event_comment']) : (string)$iN->iN_GetSetting('web_push_event_comment', '1');
        $webPushEventFollow = isset($_POST['web_push_event_follow']) ? (string)$iN->iN_Secure($_POST['web_push_event_follow']) : (string)$iN->iN_GetSetting('web_push_event_follow', '1');
        $webPushEventSubscription = isset($_POST['web_push_event_subscription']) ? (string)$iN->iN_Secure($_POST['web_push_event_subscription']) : (string)$iN->iN_GetSetting('web_push_event_subscription', '1');
        $webPushEventAnnouncement = isset($_POST['web_push_event_announcement']) ? (string)$iN->iN_Secure($_POST['web_push_event_announcement']) : (string)$iN->iN_GetSetting('web_push_event_announcement', '1');
        $webPushEventLiveStarted = $webPushEventLiveStarted === '1' ? '1' : '0';
        $webPushEventTipReceived = $webPushEventTipReceived === '1' ? '1' : '0';
        $webPushEventPostLike = $webPushEventPostLike === '1' ? '1' : '0';
        $webPushEventComment = $webPushEventComment === '1' ? '1' : '0';
        $webPushEventFollow = $webPushEventFollow === '1' ? '1' : '0';
        $webPushEventSubscription = $webPushEventSubscription === '1' ? '1' : '0';
        $webPushEventAnnouncement = $webPushEventAnnouncement === '1' ? '1' : '0';
        $webPushSchemaReady = method_exists($iN, 'iN_WebPushSchemaReady') && $iN->iN_WebPushSchemaReady();
        if ($webPushStatus === '1') {
            if (!$webPushSchemaReady) {
                exit('webpush_schema_missing');
            }
            $webPushPublic = trim((string)$iN->iN_GetSetting('web_push_vapid_public', ''));
            $webPushPrivate = trim((string)$iN->iN_GetSetting('web_push_vapid_private', ''));
            if ($webPushPublic === '' || $webPushPrivate === '') {
                exit('webpush_keys_missing');
            }
        }


        if (empty($availableUploadFileExtensions) || $availableUploadFileExtensions == '') {
            exit('1');
        }
		if ($bulkMessageFileExtensions === '') {
			$bulkMessageFileExtensions = $availableUploadFileExtensions;
		}
		$bulkMessagePriceMin = is_numeric($bulkMessagePriceMinRaw) ? (float)$bulkMessagePriceMinRaw : 0.0;
		$bulkMessagePriceMax = is_numeric($bulkMessagePriceMaxRaw) ? (float)$bulkMessagePriceMaxRaw : 0.0;
		if ($bulkMessagePriceMin < 0) { $bulkMessagePriceMin = 0.0; }
		if ($bulkMessagePriceMax < 0) { $bulkMessagePriceMax = 0.0; }
		if ($bulkMessagePriceMax > 0 && $bulkMessagePriceMax < $bulkMessagePriceMin) {
			$bulkMessagePriceMax = $bulkMessagePriceMin;
		}
		if (empty($approvalFileExtension) || $approvalFileExtension == '') {
			exit('2');
		}
		$unavailableUsernames = strtolower($unavailableUsernames);
            $updateLimitValues = $iN->iN_UpdateLimitValues($userID,
                $iN->iN_Secure($friendActivityShowTimeLimit),
                $iN->iN_Secure($friendActivityShowLimit),
                $iN->iN_Secure($TrendPostShowLimit),
                $iN->iN_Secure($sugProductShowLimit),
                $iN->iN_Secure($sugUserShowLimit),
                $iN->iN_Secure($oneSignalStatus),
                $iN->iN_Secure($oneSignalApiKey),
                $iN->iN_Secure($oneSignalRestApiKey),
                $iN->iN_Secure($reCaptchaStatus),
                $iN->iN_Secure($reCaptchaSiteKey),
                $iN->iN_Secure($reCaptchaSecretKey),
                $iN->iN_Secure($postCreateStatus),
                $iN->iN_Secure($blockCountryStatus),
                $iN->iN_Secure($fileLimit),
                $iN->iN_Secure($lengthLimit),
                $iN->iN_Secure($postShowLimit),
                $iN->iN_Secure($paginatonLimit),
                $iN->iN_Secure($approvalFileExtension),
                $iN->iN_Secure($availableUploadFileExtensions),
                $iN->iN_Secure($bulkMessageFileExtensions),
                (string)$bulkMessagePriceMin,
                (string)$bulkMessagePriceMax,
                $iN->iN_Secure($ffmpeg_path),
                $iN->iN_Secure($ffprobe_path),
                $iN->iN_Secure($storyAudioUploadMode),
                $iN->iN_Secure($unavailableUsernames),
                $iN->iN_Secure($messageLimit),
                $iN->iN_Secure($adsShowLimit),
                $iN->iN_Secure($reelsFeatureStatus),
                $iN->iN_Secure($maxVideoDuration),
                $iN->iN_Secure($pollSystemStatus),
                (int) $pollMaxOptions,
                (int) $pollMinOptions,
                $iN->iN_Secure($scheduledPostsStatus),
                (int) $scheduledMaxDelayDays,
                (string)$quickActionsLayout,
                (string)$campaignFeatureStatus,
                (string)$campaignTitleRequired,
                (string)$campaignGoalRequired,
                (string)$campaignDeadlineRequired,
                (string)$campaignCreatorPolicy,
                (string)$campaignGoalMin,
                (string)$campaignGoalMax,
                (string)$blogFeatureStatus,
                (string)$blogReactionsStatus,
                (string)$blogShareStatus,
                (string)$blogSidebarAdsStatus,
                (string)$storyDefaultReactions,
                (string)$trendHashtagsStatus,
                (int)$trendHashtagsLimit
            );
		if ($updateLimitValues) {
            $webPushSaved = true;
            if ($webPushSchemaReady) {
                $webPushSaved = $iN->iN_SetSetting('web_push_status', $webPushStatus)
                    && $iN->iN_SetSetting('web_push_vapid_subject', $webPushVapidSubject)
                    && $iN->iN_SetSetting('web_push_ttl', (string)$webPushTtl)
                    && $iN->iN_SetSetting('web_push_event_live_started', $webPushEventLiveStarted)
                    && $iN->iN_SetSetting('web_push_event_tip_received', $webPushEventTipReceived)
                    && $iN->iN_SetSetting('web_push_event_post_like', $webPushEventPostLike)
                    && $iN->iN_SetSetting('web_push_event_comment', $webPushEventComment)
                    && $iN->iN_SetSetting('web_push_event_follow', $webPushEventFollow)
                    && $iN->iN_SetSetting('web_push_event_subscription', $webPushEventSubscription)
                    && $iN->iN_SetSetting('web_push_event_announcement', $webPushEventAnnouncement);
            }
            if (!$webPushSaved) {
                exit('webpush_save_failed');
            }
			exit('200');
		} else {
			echo '404';
        }
    }
    if ($type == 'adsense_settings') {
        $adsenseStatus = isset($_POST['adsense_status']) ? (string)$iN->iN_Secure($_POST['adsense_status']) : '0';
        $adsenseClientId = isset($_POST['adsense_client_id']) ? trim((string)$iN->iN_Secure($_POST['adsense_client_id'])) : '';
        $adsenseSlotTop = isset($_POST['adsense_slot_top']) ? trim((string)$iN->iN_Secure($_POST['adsense_slot_top'])) : '';
        $adsenseSlotInline = isset($_POST['adsense_slot_inline']) ? trim((string)$iN->iN_Secure($_POST['adsense_slot_inline'])) : '';
        $adsenseSlotFooter = isset($_POST['adsense_slot_footer']) ? trim((string)$iN->iN_Secure($_POST['adsense_slot_footer'])) : '';
        $adsenseSlotSidebar = isset($_POST['adsense_slot_sidebar']) ? trim((string)$iN->iN_Secure($_POST['adsense_slot_sidebar'])) : '';
        $adsenseInlineFrequency = isset($_POST['adsense_inline_frequency']) ? (int)$iN->iN_Secure($_POST['adsense_inline_frequency']) : 4;
        $adsenseInlineOffset = isset($_POST['adsense_inline_offset']) ? (int)$iN->iN_Secure($_POST['adsense_inline_offset']) : 1;
        $adsenseTopSize = isset($_POST['adsense_top_size']) ? (string)$iN->iN_Secure($_POST['adsense_top_size']) : 'responsive';
        $adsenseInlineSize = isset($_POST['adsense_inline_size']) ? (string)$iN->iN_Secure($_POST['adsense_inline_size']) : 'responsive';
        $adsenseFooterSize = isset($_POST['adsense_footer_size']) ? (string)$iN->iN_Secure($_POST['adsense_footer_size']) : 'responsive';
        $adsenseSidebarSize = isset($_POST['adsense_sidebar_size']) ? (string)$iN->iN_Secure($_POST['adsense_sidebar_size']) : 'responsive';

        $adsenseStatus = in_array($adsenseStatus, ['0', '1'], true) ? $adsenseStatus : '0';
        $clientPattern = '/^ca-pub-[0-9]{10,}$/';
        if ($adsenseStatus === '1' && ($adsenseClientId === '' || !preg_match($clientPattern, $adsenseClientId))) {
            exit('invalid_client');
        }
        $slotPattern = '/^[0-9]{6,}$/';
        foreach (['adsenseSlotTop', 'adsenseSlotInline', 'adsenseSlotFooter', 'adsenseSlotSidebar'] as $slotVar) {
            if (!empty(${$slotVar}) && !preg_match($slotPattern, ${$slotVar})) {
                ${$slotVar} = '';
            }
        }
        $adsenseInlineFrequency = ($adsenseInlineFrequency < 1 || $adsenseInlineFrequency > 50) ? 4 : $adsenseInlineFrequency;
        $adsenseInlineOffset = ($adsenseInlineOffset < 1 || $adsenseInlineOffset > 50) ? 1 : $adsenseInlineOffset;
        $allowedSizes = ['responsive', '300x250', '336x280', '728x90', '970x90', '320x100'];
        if (!in_array($adsenseTopSize, $allowedSizes, true)) { $adsenseTopSize = 'responsive'; }
        if (!in_array($adsenseInlineSize, $allowedSizes, true)) { $adsenseInlineSize = 'responsive'; }
        if (!in_array($adsenseFooterSize, $allowedSizes, true)) { $adsenseFooterSize = 'responsive'; }
        if (!in_array($adsenseSidebarSize, $allowedSizes, true)) { $adsenseSidebarSize = 'responsive'; }

        $adsensePayload = [
            'status' => $adsenseStatus,
            'client_id' => $adsenseClientId,
            'slot_top' => $adsenseSlotTop,
            'slot_inline' => $adsenseSlotInline,
            'slot_footer' => $adsenseSlotFooter,
            'slot_sidebar' => $adsenseSlotSidebar,
            'inline_frequency' => $adsenseInlineFrequency,
            'inline_offset' => $adsenseInlineOffset,
            'top_size' => $adsenseTopSize,
            'inline_size' => $adsenseInlineSize,
            'footer_size' => $adsenseFooterSize,
            'sidebar_size' => $adsenseSidebarSize,
        ];
        $updated = $iN->iN_UpdateAdsenseSettings($userID, $adsensePayload);
        if ($updated) {
            exit('200');
        }
        exit('404');
    }
    if ($type == 'ads_create_placement') {
        $placementKey = isset($_POST['placement_key']) ? strtolower(trim((string)$_POST['placement_key'])) : '';
        $placementTitle = isset($_POST['placement_title']) ? trim((string)$iN->iN_Secure($_POST['placement_title'])) : '';
        $placementDesc = isset($_POST['placement_desc']) ? trim((string)$iN->iN_Secure($_POST['placement_desc'])) : '';
        $placementStatus = isset($_POST['placement_status']) && $_POST['placement_status'] === '1' ? '1' : '0';
        $inlineFrequency = isset($_POST['inline_frequency']) ? (int)$iN->iN_Secure($_POST['inline_frequency']) : 0;
        $inlineOffset = isset($_POST['inline_offset']) ? (int)$iN->iN_Secure($_POST['inline_offset']) : 0;
        $created = $iN->iN_CreateAdPlacement($userID, [
            'placement_key' => $placementKey,
            'placement_title' => $placementTitle,
            'placement_desc' => $placementDesc,
            'placement_status' => $placementStatus,
            'inline_frequency' => $inlineFrequency,
            'inline_offset' => $inlineOffset
        ]);
        if ($created) {
            exit('200');
        }
        $existing = $iN->iN_GetAdPlacementByKey($placementKey);
        if (!empty($existing)) {
            exit('exists');
        }
        exit('error');
    }
    if ($type == 'earnings_chart') {
        header('Content-Type: application/json; charset=utf-8');
        $range = isset($_POST['range']) ? (int)$iN->iN_Secure($_POST['range']) : 30;
        if ($range !== 7 && $range !== 30) { $range = 30; }
        $startTs = strtotime('-' . ($range - 1) . ' days');
        $startDay = date('Y-m-d', $startTs);
        $labels = [];
        for ($i = 0; $i < $range; $i++) {
            $labels[] = date('M j', strtotime($startDay . " +{$i} day"));
        }
        $subs = array_fill(0, $range, 0);
        $premium = array_fill(0, $range, 0);
        $boost = array_fill(0, $range, 0);
        try {
            $sqlSubs = "SELECT DATE(FROM_UNIXTIME(created)) as d, SUM(admin_earning) as total
                        FROM i_user_subscriptions
                        WHERE created >= UNIX_TIMESTAMP(?) AND status IN('active','inactive') AND in_status IN('1','0') AND finished='0'
                          AND subscription_scope = 'profile'
                        GROUP BY d";
            $rowsSubs = DB::all($sqlSubs, [$startDay]);
            foreach ($rowsSubs as $row) {
                $idx = (int)floor((strtotime($row['d']) - strtotime($startDay)) / 86400);
                if (isset($subs[$idx])) { $subs[$idx] = (float)$row['total']; }
            }
        } catch (Throwable $e) {}
        try {
            $premiumTypes = ['post','profile','live_stream','videoCall','tips','live_gift','product','frame'];
            $inPlaceholders = implode(',', array_fill(0, count($premiumTypes), '?'));
            $params = array_merge([$startDay], $premiumTypes);
            $sqlPrem = "SELECT DATE(FROM_UNIXTIME(payment_time)) as d, SUM(admin_earning) as total
                        FROM i_user_payments
                        WHERE payment_time >= UNIX_TIMESTAMP(?) AND payment_status='ok' AND payment_type IN ($inPlaceholders)
                        GROUP BY d";
            $rowsPrem = DB::all($sqlPrem, $params);
            foreach ($rowsPrem as $row) {
                $idx = (int)floor((strtotime($row['d']) - strtotime($startDay)) / 86400);
                if (isset($premium[$idx])) { $premium[$idx] = (float)$row['total']; }
            }
        } catch (Throwable $e) {}
        try {
            $sqlBoost = "SELECT DATE(FROM_UNIXTIME(payment_time)) as d, SUM(admin_earning) as total
                        FROM i_user_payments
                        WHERE payment_time >= UNIX_TIMESTAMP(?) AND payment_status='ok' AND payment_type='boostPost'
                        GROUP BY d";
            $rowsBoost = DB::all($sqlBoost, [$startDay]);
            foreach ($rowsBoost as $row) {
                $idx = (int)floor((strtotime($row['d']) - strtotime($startDay)) / 86400);
                if (isset($boost[$idx])) { $boost[$idx] = (float)$row['total']; }
            }
        } catch (Throwable $e) {}
        echo json_encode([
            'labels' => $labels,
            'subscription' => $subs,
            'premium' => $premium,
            'boost' => $boost,
            'currency' => $currencys[$defaultCurrency] ?? '$',
            'labelSub' => $LANG['subscription_earnings'] ?? 'Subscriptions',
            'labelPremium' => $LANG['premium_earnings'] ?? 'Premium/Posts',
            'labelBoost' => $LANG['boost_earnings'] ?? 'Boost earnings'
        ]);
        exit;
    }
    if ($type == 'posts_chart') {
        header('Content-Type: application/json; charset=utf-8');
        $period = isset($_POST['period']) ? strtolower(trim((string)$iN->iN_Secure($_POST['period']))) : 'daily';
        $allowed = ['daily','weekly','monthly','yearly'];
        if (!in_array($period, $allowed, true)) { $period = 'daily'; }

        $limitMap = [
            'daily' => 7,
            'weekly' => 12,
            'monthly' => 12,
            'yearly' => 5
        ];
        $bucketExpr = '';
        $labels = [];
        $limit = $limitMap[$period];
        $startTs = time();
        if ($period === 'daily') {
            $startTs = strtotime('-' . ($limit - 1) . ' days');
            for ($i = 0; $i < $limit; $i++) { $labels[] = date('M j', strtotime("+{$i} day", $startTs)); }
            $bucketExpr = "DATE(FROM_UNIXTIME(post_created_time))";
        } elseif ($period === 'weekly') {
            $startTs = strtotime('-' . ($limit - 1) . ' weeks');
            for ($i = 0; $i < $limit; $i++) { $labels[] = 'W' . date('W', strtotime("+{$i} week", $startTs)) . ' ' . date('Y', strtotime("+{$i} week", $startTs)); }
            $bucketExpr = "YEARWEEK(FROM_UNIXTIME(post_created_time), 1)";
        } elseif ($period === 'monthly') {
            $startTs = strtotime('-' . ($limit - 1) . ' months');
            for ($i = 0; $i < $limit; $i++) { $labels[] = date('M Y', strtotime("+{$i} month", $startTs)); }
            $bucketExpr = "DATE_FORMAT(FROM_UNIXTIME(post_created_time), '%Y-%m')";
        } else {
            $startTs = strtotime('-' . ($limit - 1) . ' years');
            for ($i = 0; $i < $limit; $i++) { $labels[] = date('Y', strtotime("+{$i} year", $startTs)); }
            $bucketExpr = "YEAR(FROM_UNIXTIME(post_created_time))";
        }

        $types = ['image','video','audio','poll','reels','scheduled','other'];
        $series = [];
        foreach ($types as $t) {
            $series[$t] = array_fill(0, $limit, 0);
        }

        try {
            $sql = "
                SELECT $bucketExpr AS bucket, post_type, scheduled_status, COUNT(*) AS total
                FROM i_posts
                WHERE (post_status = '1' OR scheduled_status = 'pending')
                  AND post_created_time >= UNIX_TIMESTAMP(?)
                GROUP BY bucket, post_type, scheduled_status
                ORDER BY bucket
            ";
            $rows = DB::all($sql, [date('Y-m-d', $startTs)]);
            foreach ($rows as $row) {
                $bucket = (string)$row['bucket'];
                $total = (int)$row['total'];
                $type = strtolower((string)$row['post_type']);
                $scheduled = (string)($row['scheduled_status'] ?? '');
                $idx = array_search($bucket, array_map(function($l) use ($period) {
                    return $l;
                }, $labels), true);
                // map bucket to index
                if ($idx === false) {
                    // fallback mapping by date difference
                    if ($period === 'daily') {
                        $idx = (int)floor((strtotime($bucket) - $startTs) / 86400);
                    } elseif ($period === 'weekly') {
                        $idx = (int)floor((strtotime($bucket . ' Monday') - $startTs) / (86400 * 7));
                    } elseif ($period === 'monthly') {
                        $idx = (int)floor((strtotime($bucket . '-01') - $startTs) / (86400 * 30));
                    } else {
                        $idx = (int)floor((strtotime($bucket . '-01-01') - $startTs) / (86400 * 365));
                    }
                }
                if ($idx < 0 || $idx >= $limit) { continue; }
                $key = in_array($type, ['image','video','audio','poll','reels'], true) ? $type : 'other';
                if ($scheduled === 'pending') { $key = 'scheduled'; }
                $series[$key][$idx] += $total;
            }
        } catch (Throwable $e) {
            // ignore
        }

        echo json_encode([
            'labels' => $labels,
            'series' => $series,
            'legend' => [
                'image' => $LANG['image'] ?? 'Image',
                'video' => $LANG['video'] ?? 'Video',
                'audio' => $LANG['audio'] ?? 'Audio',
                'poll' => $LANG['poll'] ?? 'Poll',
                'reels' => $LANG['reels'] ?? 'Reels',
                'scheduled' => $LANG['scheduled'] ?? 'Scheduled',
                'other' => $LANG['other'] ?? 'Other'
            ]
        ]);
        exit;
    }
    /* Dashboard Advanced Charts Extension: start */
    if ($type == 'dashboard_advanced_charts') {
        header('Content-Type: application/json; charset=utf-8');

        $days = 30;
        $startDay = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $dateLabels = [];
        $dateIndex = [];
        for ($i = 0; $i < $days; $i++) {
            $dateKey = date('Y-m-d', strtotime($startDay . " +{$i} day"));
            $dateLabels[] = date('M j', strtotime($dateKey));
            $dateIndex[$dateKey] = $i;
        }

        $contentTypeLabels = [
            'image' => $LANG['image'] ?? 'Image',
            'video' => $LANG['video'] ?? 'Video',
            'audio' => $LANG['audio'] ?? 'Audio',
            'poll' => $LANG['poll'] ?? 'Poll',
            'reels' => $LANG['reels'] ?? 'Reels'
        ];

        $paymentOptionLabels = [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'razorpay' => 'Razorpay',
            'iyzico' => 'Iyzico',
            'authorize-net' => 'Authorize.net',
            'paystack' => 'Paystack',
            'flutterwave' => 'Flutterwave',
            'bitpay' => 'BitPay',
            'coinpayment' => 'CoinPayment',
            'mercadopago' => 'MercadoPago',
            'bank' => $LANG['bank'] ?? 'Bank',
            'moneroo' => 'Moneroo',
            'ccbill' => 'CCBill',
            'wallet' => $LANG['wallet'] ?? 'Wallet',
            'nowpayments' => 'NOWPayments',
            'paysafecard' => 'Paysafecard'
        ];

        $statusLabels = [
            'ok' => $LANG['dashboard_status_ok'] ?? 'OK',
            'pending' => $LANG['pending'] ?? 'Pending',
            'declined' => $LANG['declined'] ?? 'Declined'
        ];

        $paymentTypeLabels = [
            'point' => $LANG['all_point_earning'] ?? 'Point',
            'product' => $LANG['products'] ?? 'Products',
            'subscription' => $LANG['dashboard_payment_type_subscription'] ?? 'Subscription',
            'campaign_donate' => $LANG['campaign_donations'] ?? 'Campaign Donation',
            'tips' => $LANG['tips'] ?? 'Tips',
            'post' => $LANG['posts'] ?? 'Posts',
            'live_stream' => $LANG['live_streamings'] ?? 'Live Stream',
            'live_gift' => $LANG['gift'] ?? 'Live Gift',
            'videocall' => $LANG['video_call'] ?? 'Video Call',
            'boostpost' => $LANG['boost_earnings'] ?? 'Boost',
            'profile' => $LANG['profile'] ?? 'Profile',
            'frame' => $LANG['frame'] ?? 'Frame',
            'unlockmessage' => $LANG['dashboard_payment_type_unlock_message'] ?? 'Unlock Message',
            'agency_boost' => $LANG['dashboard_payment_type_agency_boost'] ?? 'Agency Boost'
        ];

        $paymentMethodTotals = [];
        try {
            $rows = DB::all(
                "SELECT LOWER(payment_option) AS option_key, LOWER(payment_status) AS status_key, COUNT(*) AS total
                 FROM i_user_payments
                 WHERE payment_time >= UNIX_TIMESTAMP(?)
                 GROUP BY option_key, status_key",
                [$startDay]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $optionKey = (string)($row['option_key'] ?? '');
                    $statusKey = (string)($row['status_key'] ?? '');
                    $count = (int)($row['total'] ?? 0);
                    if ($optionKey === '' || !isset($statusLabels[$statusKey])) {
                        continue;
                    }
                    if (!isset($paymentMethodTotals[$optionKey])) {
                        $paymentMethodTotals[$optionKey] = [
                            'ok' => 0,
                            'pending' => 0,
                            'declined' => 0,
                            'total' => 0
                        ];
                    }
                    $paymentMethodTotals[$optionKey][$statusKey] += $count;
                    $paymentMethodTotals[$optionKey]['total'] += $count;
                }
            }
        } catch (Throwable $e) {
            $paymentMethodTotals = [];
        }

        uasort($paymentMethodTotals, static function ($a, $b) {
            return ((int)$b['total']) <=> ((int)$a['total']);
        });
        $paymentMethodTotals = array_slice($paymentMethodTotals, 0, 8, true);
        $paymentMethodLabels = [];
        $paymentMethodSeries = ['ok' => [], 'pending' => [], 'declined' => []];
        foreach ($paymentMethodTotals as $methodKey => $methodData) {
            $methodLabel = $paymentOptionLabels[$methodKey] ?? ucwords(str_replace(['-', '_'], ' ', $methodKey));
            $paymentMethodLabels[] = $methodLabel;
            $paymentMethodSeries['ok'][] = (int)($methodData['ok'] ?? 0);
            $paymentMethodSeries['pending'][] = (int)($methodData['pending'] ?? 0);
            $paymentMethodSeries['declined'][] = (int)($methodData['declined'] ?? 0);
        }

        $weekCount = 12;
        $weekStartTs = strtotime('monday this week -' . ($weekCount - 1) . ' weeks');
        $weekLabels = [];
        $weekIndex = [];
        for ($i = 0; $i < $weekCount; $i++) {
            $weekTs = strtotime("+{$i} week", $weekStartTs);
            $weekKey = (int)date('oW', $weekTs);
            $weekLabels[] = 'W' . date('W', $weekTs);
            $weekIndex[$weekKey] = $i;
        }
        $healthActive = array_fill(0, $weekCount, 0);
        $healthInactive = array_fill(0, $weekCount, 0);
        $healthDeclined = array_fill(0, $weekCount, 0);
        $healthDeclineRate = array_fill(0, $weekCount, 0);
        try {
            $rows = DB::all(
                "SELECT YEARWEEK(created, 1) AS week_key, LOWER(status) AS status_key, COUNT(*) AS total
                 FROM i_user_subscriptions
                 WHERE created >= ?
                 GROUP BY week_key, status_key
                 ORDER BY week_key",
                [date('Y-m-d', $weekStartTs)]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $weekKey = (int)($row['week_key'] ?? 0);
                    $statusKey = (string)($row['status_key'] ?? '');
                    $count = (int)($row['total'] ?? 0);
                    if (!isset($weekIndex[$weekKey])) {
                        continue;
                    }
                    $idx = (int)$weekIndex[$weekKey];
                    if ($statusKey === 'active') {
                        $healthActive[$idx] += $count;
                    } elseif ($statusKey === 'inactive') {
                        $healthInactive[$idx] += $count;
                    } elseif ($statusKey === 'declined') {
                        $healthDeclined[$idx] += $count;
                    }
                }
            }
        } catch (Throwable $e) {
            $healthActive = array_fill(0, $weekCount, 0);
            $healthInactive = array_fill(0, $weekCount, 0);
            $healthDeclined = array_fill(0, $weekCount, 0);
        }
        for ($i = 0; $i < $weekCount; $i++) {
            $totalWeek = (int)$healthActive[$i] + (int)$healthInactive[$i] + (int)$healthDeclined[$i];
            $healthDeclineRate[$i] = $totalWeek > 0 ? round(((float)$healthDeclined[$i] / (float)$totalWeek) * 100, 2) : 0;
        }

        $registeredUsers = array_fill(0, $days, 0);
        $payingUsers = array_fill(0, $days, 0);
        try {
            $rows = DB::all(
                "SELECT DATE(FROM_UNIXTIME(registered)) AS d, COUNT(*) AS total
                 FROM i_users
                 WHERE registered >= UNIX_TIMESTAMP(?) AND uStatus IN('1', '3')
                 GROUP BY d",
                [$startDay]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $dateKey = (string)($row['d'] ?? '');
                    if (!isset($dateIndex[$dateKey])) {
                        continue;
                    }
                    $registeredUsers[(int)$dateIndex[$dateKey]] = (int)($row['total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $registeredUsers = array_fill(0, $days, 0);
        }
        try {
            $rows = DB::all(
                "SELECT DATE(FROM_UNIXTIME(payment_time)) AS d, COUNT(DISTINCT payer_iuid_fk) AS total
                 FROM i_user_payments
                 WHERE payment_time >= UNIX_TIMESTAMP(?) AND payment_status = 'ok' AND payer_iuid_fk IS NOT NULL
                 GROUP BY d",
                [$startDay]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $dateKey = (string)($row['d'] ?? '');
                    if (!isset($dateIndex[$dateKey])) {
                        continue;
                    }
                    $payingUsers[(int)$dateIndex[$dateKey]] = (int)($row['total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $payingUsers = array_fill(0, $days, 0);
        }

        $revenueByTypeTotals = [];
        try {
            $rows = DB::all(
                "SELECT LOWER(payment_type) AS type_key, SUM(CAST(COALESCE(NULLIF(admin_earning, ''), '0') AS DECIMAL(18,4))) AS total
                 FROM i_user_payments
                 WHERE payment_status = 'ok' AND payment_time >= UNIX_TIMESTAMP(?)
                 GROUP BY type_key",
                [$startDay]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $typeKey = (string)($row['type_key'] ?? '');
                    if ($typeKey === '') {
                        continue;
                    }
                    $revenueByTypeTotals[$typeKey] = (float)($row['total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $revenueByTypeTotals = [];
        }
        try {
            $subRevenue = (float)DB::col(
                "SELECT SUM(CAST(COALESCE(NULLIF(admin_earning, ''), '0') AS DECIMAL(18,4)))
                 FROM i_user_subscriptions
                 WHERE created >= ? AND status IN('active', 'inactive')",
                [$startDay]
            );
            if ($subRevenue > 0) {
                $revenueByTypeTotals['subscription'] = ($revenueByTypeTotals['subscription'] ?? 0) + $subRevenue;
            }
        } catch (Throwable $e) {
            // do nothing
        }
        arsort($revenueByTypeTotals);
        $revenueByTypeTotals = array_slice($revenueByTypeTotals, 0, 10, true);
        $revenueTypeLabels = [];
        $revenueTypeValues = [];
        foreach ($revenueByTypeTotals as $typeKey => $typeValue) {
            $label = $paymentTypeLabels[$typeKey] ?? ucwords(str_replace(['_', '-'], ' ', $typeKey));
            $revenueTypeLabels[] = $label;
            $revenueTypeValues[] = round((float)$typeValue, 2);
        }

        $payoutStatuses = [
            'pending' => $LANG['pending'] ?? 'Pending',
            'payed' => $LANG['dashboard_status_paid'] ?? 'Paid',
            'declined' => $LANG['declined'] ?? 'Declined'
        ];
        $payoutCounts = [
            'pending' => 0,
            'payed' => 0,
            'declined' => 0
        ];
        try {
            $rows = DB::all(
                "SELECT LOWER(status) AS status_key, COUNT(*) AS total
                 FROM i_user_payouts
                 WHERE payout_time >= UNIX_TIMESTAMP(?)
                 GROUP BY status_key",
                [date('Y-m-d', strtotime('-89 days'))]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $statusKey = (string)($row['status_key'] ?? '');
                    if (!isset($payoutCounts[$statusKey])) {
                        continue;
                    }
                    $payoutCounts[$statusKey] = (int)($row['total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $payoutCounts = [
                'pending' => 0,
                'payed' => 0,
                'declined' => 0
            ];
        }

        $postCountsByType = [
            'image' => 0,
            'video' => 0,
            'audio' => 0,
            'poll' => 0,
            'reels' => 0
        ];
        try {
            $rows = DB::all(
                "SELECT LOWER(post_type) AS type_key, COUNT(*) AS total
                 FROM i_posts
                 WHERE post_created_time >= UNIX_TIMESTAMP(?) AND post_status = '1'
                 GROUP BY type_key",
                [$startDay]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $typeKey = (string)($row['type_key'] ?? '');
                    if (!isset($postCountsByType[$typeKey])) {
                        continue;
                    }
                    $postCountsByType[$typeKey] = (int)($row['total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $postCountsByType = [
                'image' => 0,
                'video' => 0,
                'audio' => 0,
                'poll' => 0,
                'reels' => 0
            ];
        }

        $contentRevenueByType = [
            'image' => 0.0,
            'video' => 0.0,
            'audio' => 0.0,
            'poll' => 0.0,
            'reels' => 0.0
        ];
        $contentPaymentsByType = [
            'image' => 0,
            'video' => 0,
            'audio' => 0,
            'poll' => 0,
            'reels' => 0
        ];
        try {
            $rows = DB::all(
                "SELECT LOWER(p.post_type) AS type_key,
                        COUNT(up.payment_id) AS payment_count,
                        SUM(CAST(COALESCE(NULLIF(up.admin_earning, ''), '0') AS DECIMAL(18,4))) AS revenue_total
                 FROM i_user_payments up
                 INNER JOIN i_posts p ON p.post_id = up.payed_post_id_fk
                 WHERE up.payment_status = 'ok'
                   AND up.payment_type = 'post'
                   AND up.payment_time >= UNIX_TIMESTAMP(?)
                 GROUP BY type_key",
                [$startDay]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $typeKey = (string)($row['type_key'] ?? '');
                    if (!isset($contentRevenueByType[$typeKey])) {
                        continue;
                    }
                    $contentRevenueByType[$typeKey] = round((float)($row['revenue_total'] ?? 0), 2);
                    $contentPaymentsByType[$typeKey] = (int)($row['payment_count'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $contentRevenueByType = [
                'image' => 0.0,
                'video' => 0.0,
                'audio' => 0.0,
                'poll' => 0.0,
                'reels' => 0.0
            ];
            $contentPaymentsByType = [
                'image' => 0,
                'video' => 0,
                'audio' => 0,
                'poll' => 0,
                'reels' => 0
            ];
        }

        $bubblePoints = [];
        foreach ($contentTypeLabels as $typeKey => $typeLabel) {
            $payments = (int)($contentPaymentsByType[$typeKey] ?? 0);
            $radius = $payments > 0 ? (int)round(sqrt((float)$payments) * 3 + 6) : 6;
            if ($radius < 6) {
                $radius = 6;
            } elseif ($radius > 24) {
                $radius = 24;
            }
            $bubblePoints[] = [
                'label' => $typeLabel,
                'x' => (int)($postCountsByType[$typeKey] ?? 0),
                'y' => (float)($contentRevenueByType[$typeKey] ?? 0),
                'r' => $radius,
                'payments' => $payments
            ];
        }

        $funnelValues = [
            'verification_submitted' => 0,
            'verification_approved' => 0,
            'creators_enabled' => 0,
            'creators_with_sales' => 0,
            'creators_with_subscribers' => 0
        ];
        try {
            $funnelValues['verification_submitted'] = (int)DB::col("SELECT COUNT(*) FROM i_verification_requests");
        } catch (Throwable $e) {
            $funnelValues['verification_submitted'] = 0;
        }
        try {
            $funnelValues['verification_approved'] = (int)DB::col("SELECT COUNT(*) FROM i_verification_requests WHERE request_status = '1'");
        } catch (Throwable $e) {
            $funnelValues['verification_approved'] = 0;
        }
        try {
            $funnelValues['creators_enabled'] = (int)DB::col("SELECT COUNT(*) FROM i_users WHERE condition_status = '2'");
        } catch (Throwable $e) {
            $funnelValues['creators_enabled'] = 0;
        }
        try {
            $funnelValues['creators_with_sales'] = (int)DB::col(
                "SELECT COUNT(DISTINCT payed_iuid_fk) FROM i_user_payments WHERE payment_status = 'ok' AND payed_iuid_fk IS NOT NULL"
            );
        } catch (Throwable $e) {
            $funnelValues['creators_with_sales'] = 0;
        }
        try {
            $funnelValues['creators_with_subscribers'] = (int)DB::col(
                "SELECT COUNT(DISTINCT subscribed_iuid_fk) FROM i_user_subscriptions WHERE status IN('active', 'inactive')"
            );
        } catch (Throwable $e) {
            $funnelValues['creators_with_subscribers'] = 0;
        }

        $radarPostTotal = array_sum($postCountsByType);
        $radarRevenueTotal = array_sum($contentRevenueByType);
        $radarPostShare = [];
        $radarRevenueShare = [];
        foreach ($contentTypeLabels as $typeKey => $typeLabel) {
            $postValue = (int)($postCountsByType[$typeKey] ?? 0);
            $revenueValue = (float)($contentRevenueByType[$typeKey] ?? 0);
            $radarPostShare[] = $radarPostTotal > 0 ? round(($postValue / $radarPostTotal) * 100, 2) : 0;
            $radarRevenueShare[] = $radarRevenueTotal > 0 ? round(($revenueValue / $radarRevenueTotal) * 100, 2) : 0;
        }

        $responsePayload = [
            'paymentMethods' => [
                'labels' => $paymentMethodLabels,
                'series' => $paymentMethodSeries,
                'legend' => $statusLabels
            ],
            'subscriptionHealth' => [
                'labels' => $weekLabels,
                'active' => $healthActive,
                'inactive' => $healthInactive,
                'declined' => $healthDeclined,
                'declineRate' => $healthDeclineRate
            ],
            'userConversion' => [
                'labels' => $dateLabels,
                'registered' => $registeredUsers,
                'paying' => $payingUsers
            ],
            'revenueByType' => [
                'labels' => $revenueTypeLabels,
                'values' => $revenueTypeValues,
                'currency' => $currencys[$defaultCurrency] ?? '$'
            ],
            'payoutPipeline' => [
                'labels' => array_values($payoutStatuses),
                'counts' => array_values($payoutCounts)
            ],
            'contentMonetization' => [
                'points' => $bubblePoints,
                'currency' => $currencys[$defaultCurrency] ?? '$'
            ],
            'creatorFunnel' => [
                'labels' => [
                    $LANG['dashboard_funnel_step_verification_submitted'] ?? 'Verification submitted',
                    $LANG['dashboard_funnel_step_verification_approved'] ?? 'Verification approved',
                    $LANG['dashboard_funnel_step_creators_enabled'] ?? 'Creators enabled',
                    $LANG['dashboard_funnel_step_creators_with_sales'] ?? 'Creators with sales',
                    $LANG['dashboard_funnel_step_creators_with_subscribers'] ?? 'Creators with subscribers'
                ],
                'values' => array_values($funnelValues)
            ],
            'contentRadar' => [
                'labels' => array_values($contentTypeLabels),
                'postShare' => $radarPostShare,
                'revenueShare' => $radarRevenueShare
            ],
            'datasets' => [
                'active' => $LANG['dashboard_dataset_active'] ?? 'Active',
                'inactive' => $LANG['dashboard_dataset_inactive'] ?? 'Inactive',
                'declined' => $LANG['dashboard_dataset_declined'] ?? 'Declined',
                'declineRate' => $LANG['dashboard_dataset_decline_rate'] ?? 'Decline rate',
                'registeredUsers' => $LANG['dashboard_dataset_registered_users'] ?? 'Registered users',
                'payingUsers' => $LANG['dashboard_dataset_paying_users'] ?? 'Paying users',
                'postShare' => $LANG['dashboard_dataset_post_share'] ?? 'Post share %',
                'revenueShare' => $LANG['dashboard_dataset_revenue_share'] ?? 'Revenue share %'
            ],
            'labelsMeta' => [
                'postsAxis' => $LANG['dashboard_axis_posts'] ?? 'Posts',
                'revenueAxis' => $LANG['dashboard_axis_revenue'] ?? 'Revenue',
                'paymentsSuffix' => $LANG['dashboard_payments_suffix'] ?? 'payments'
            ]
        ];
        $jsonResponse = json_encode($responsePayload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($jsonResponse === false) {
            echo '{"paymentMethods":{"labels":[],"series":{"ok":[],"pending":[],"declined":[]},"legend":{"ok":"OK","pending":"Pending","declined":"Declined"}},"subscriptionHealth":{"labels":[],"active":[],"inactive":[],"declined":[],"declineRate":[]},"userConversion":{"labels":[],"registered":[],"paying":[]},"revenueByType":{"labels":[],"values":[],"currency":"$"},"payoutPipeline":{"labels":[],"counts":[]},"contentMonetization":{"points":[],"currency":"$"},"creatorFunnel":{"labels":[],"values":[]},"contentRadar":{"labels":[],"postShare":[],"revenueShare":[]},"datasets":{"active":"Active","inactive":"Inactive","declined":"Declined","declineRate":"Decline rate","registeredUsers":"Registered users","payingUsers":"Paying users","postShare":"Post share %","revenueShare":"Revenue share %"},"labelsMeta":{"postsAxis":"Posts","revenueAxis":"Revenue","paymentsSuffix":"payments"}}';
        } else {
            echo $jsonResponse;
        }
        exit;
    }
    /* Dashboard Advanced Charts Extension: end */
    if ($type == 'ads_update_placement') {
        $placementId = isset($_POST['placement_id']) ? (int)$iN->iN_Secure($_POST['placement_id']) : 0;
        if ($placementId < 1) { exit('error'); }
        $placementKey = isset($_POST['placement_key']) ? strtolower(trim((string)$_POST['placement_key'])) : '';
        $placementTitle = isset($_POST['placement_title']) ? trim((string)$iN->iN_Secure($_POST['placement_title'])) : '';
        $placementDesc = isset($_POST['placement_desc']) ? trim((string)$iN->iN_Secure($_POST['placement_desc'])) : '';
        $placementStatus = isset($_POST['placement_status']) && $_POST['placement_status'] === '1' ? '1' : '0';
        $inlineFrequency = isset($_POST['inline_frequency']) ? (int)$iN->iN_Secure($_POST['inline_frequency']) : 0;
        $inlineOffset = isset($_POST['inline_offset']) ? (int)$iN->iN_Secure($_POST['inline_offset']) : 0;
        $updated = $iN->iN_UpdateAdPlacement($userID, $placementId, [
            'placement_key' => $placementKey,
            'placement_title' => $placementTitle,
            'placement_desc' => $placementDesc,
            'placement_status' => $placementStatus,
            'inline_frequency' => $inlineFrequency,
            'inline_offset' => $inlineOffset
        ]);
        exit($updated ? '200' : 'error');
    }
    if ($type == 'ads_delete_placement') {
        $placementId = isset($_POST['placement_id']) ? (int)$iN->iN_Secure($_POST['placement_id']) : 0;
        if ($placementId < 1) { exit('error'); }
        $deleted = $iN->iN_DeleteAdPlacement($userID, $placementId);
        exit($deleted ? '200' : 'error');
    }
    if ($type == 'ads_create_code') {
        $placementId = isset($_POST['placement_id']) ? (int)$iN->iN_Secure($_POST['placement_id']) : 0;
        $providerName = isset($_POST['provider_name']) ? trim((string)$iN->iN_Secure($_POST['provider_name'])) : '';
        $codeSnippet = isset($_POST['code_snippet']) ? (string)$_POST['code_snippet'] : '';
        $status = isset($_POST['status']) && $_POST['status'] === '1' ? '1' : '0';
        $isDefault = isset($_POST['is_default']) && $_POST['is_default'] === '1' ? '1' : '0';
        $created = $iN->iN_CreateAdCode($userID, [
            'placement_id' => $placementId,
            'provider_name' => $providerName,
            'code_snippet' => $codeSnippet,
            'status' => $status,
            'is_default' => $isDefault
        ]);
        exit($created ? '200' : 'error');
    }
    if ($type == 'ads_update_code') {
        $codeId = isset($_POST['code_id']) ? (int)$iN->iN_Secure($_POST['code_id']) : 0;
        if ($codeId < 1) { exit('error'); }
        $placementId = isset($_POST['placement_id']) ? (int)$iN->iN_Secure($_POST['placement_id']) : 0;
        $providerName = isset($_POST['provider_name']) ? trim((string)$iN->iN_Secure($_POST['provider_name'])) : '';
        $codeSnippet = isset($_POST['code_snippet']) ? (string)$_POST['code_snippet'] : '';
        $status = isset($_POST['status']) && $_POST['status'] === '1' ? '1' : '0';
        $isDefault = isset($_POST['is_default']) && $_POST['is_default'] === '1' ? '1' : '0';
        $updated = $iN->iN_UpdateAdCode($userID, $codeId, [
            'placement_id' => $placementId,
            'provider_name' => $providerName,
            'code_snippet' => $codeSnippet,
            'status' => $status,
            'is_default' => $isDefault
        ]);
        exit($updated ? '200' : 'error');
    }
    if ($type == 'ads_delete_code') {
        $codeId = isset($_POST['code_id']) ? (int)$iN->iN_Secure($_POST['code_id']) : 0;
        if ($codeId < 1) { exit('error'); }
        $deleted = $iN->iN_DeleteAdCode($userID, $codeId);
        exit($deleted ? '200' : 'error');
    }
    if ($type == 'ads_set_active_code') {
        $codeId = isset($_POST['code_id']) ? (int)$iN->iN_Secure($_POST['code_id']) : 0;
        if ($codeId < 1) { exit('error'); }
        $done = $iN->iN_SetActiveAdCode($userID, $codeId);
        exit($done ? '200' : 'error');
    }
    if ($type == 'scheduled_post_action') {
        $postId = isset($_POST['post_id']) ? (int)$iN->iN_Secure($_POST['post_id']) : 0;
        $action = isset($_POST['action']) ? $iN->iN_Secure($_POST['action']) : '';
        if ($postId < 1 || $action === '') {
            exit('404');
        }
        if ($action === 'publish') {
            $result = $iN->iN_PublishScheduledPost($postId);
            exit($result ? '200' : '500');
        } elseif ($action === 'cancel') {
            $result = $iN->iN_CancelScheduledPost($postId);
            exit($result ? '200' : '500');
        } elseif ($action === 'delete') {
            $result = $iN->iN_DeletePostAdmin($userID, $postId);
            exit($result ? '200' : '500');
        }
        exit('404');
    }

	if ($type == 'updateDefaultLang') {
		if (isset($_POST['lang'])) {
				$lang = $iN->iN_Secure($_POST['lang']);
			$updateDefaultLang = $iN->iN_UpdateDefaultLanguage($userID, $iN->iN_Secure($lang));
			if ($updateDefaultLang) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Default Theme Style*/
	if ($type == 'default_style_mode') {
		if (isset($_POST['mod'])) {
			$mod = $iN->iN_Secure($_POST['mod']);
			if (in_array($mod, ['light', 'dark'], true)) {
				$updateDefaultStyle = $iN->iN_UpdateDefaultStyle($userID, $mod);
				if ($updateDefaultStyle) {
					exit('200');
				}
				echo '404';
			} else {
				echo '404';
			}
		}
	}
	/*Update Maintenance Mode Status*/
	if ($type == 'maintenance_status') {
		if (in_array($_POST['mod'], $statusValue)) {
				$mod = $iN->iN_Secure($_POST['mod']);
			$updateMaintenanceStatus = $iN->iN_UpdateMaintenanceStatus($userID, $iN->iN_Secure($mod));
			if ($updateMaintenanceStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Email Send Mode Status*/
	if ($type == 'email_verification_status') {
		if (in_array($_POST['mod'], $statusValue)) {
				$mod = $iN->iN_Secure($_POST['mod']);
			$updateEmailSendStatus = $iN->iN_UpdateEmailSendStatus($userID, $iN->iN_Secure($mod));
			if ($updateEmailSendStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Register Status*/
	if ($type == 'register_new') {
		if (in_array($_POST['mod'], $statusValue)) {
				$mod = $iN->iN_Secure($_POST['mod']);
			$updateRegisterStatus = $iN->iN_UpdateRegisterStatus($userID, $iN->iN_Secure($mod));
			if ($updateRegisterStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update ip Limit Status*/
	if ($type == 'ipLimit') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateipLimitStatus = $iN->iN_UpdateIpLimitStatus($userID, $iN->iN_Secure($mod));
			if ($updateipLimitStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Email Settings*/
	if ($type == 'emailSettings') {
			$updateSmtpMail = $iN->iN_Secure($_POST['smtpmail']);
			$updateSmtpEncription = $iN->iN_Secure($_POST['smtpecript']);
			$updateSmtpHost = $iN->iN_Secure($_POST['smtp_host']);
			$updateSmtpUsername = $iN->iN_Secure($_POST['smtp_username']);
			$updateSmtpPassword = $iN->iN_Secure($_POST['smtp_password']);
			$updateSmtpPort = $iN->iN_Secure($_POST['smtp_port']);
			$updateSmtpEmail = $iN->iN_Secure($_POST['smtp_host_email']);
		if (empty($updateSmtpHost) || empty($updateSmtpUsername) || empty($updateSmtpPassword) || empty($updateSmtpPort)) {
			exit('1');
		}
		$updateEmailSettings = $iN->iN_UpdateEmailSettings($userID, $iN->iN_Secure($updateSmtpEmail), $iN->iN_Secure($updateSmtpMail), $iN->iN_Secure($updateSmtpEncription), $iN->iN_Secure($updateSmtpHost), $iN->iN_Secure($updateSmtpUsername), $iN->iN_Secure($updateSmtpPassword), $iN->iN_Secure($updateSmtpPort));
		if ($updateEmailSettings) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Amazon S3 Storage Details*/
	if ($type == 's3Settings') {
			$updateS3Region = $iN->iN_Secure($_POST['s3region']);
			$updateS3Bucket = $iN->iN_Secure($_POST['s3Bucket']);
			$updateS3Key = $iN->iN_Secure($_POST['s3Key']);
			$updateS3SecretKey = $iN->iN_Secure($_POST['s3sKey']);
			$updateS3Status = $iN->iN_Secure($_POST['s3Status']);
			$s3PublicBaseRaw = isset($_POST['s3PublicBase']) ? $_POST['s3PublicBase'] : '';
			$s3PublicBase = normalize_public_base_url($s3PublicBaseRaw);
		$updateS3Settings = $iN->iN_UpdateAmazonS3Details($userID, $iN->iN_Secure($updateS3Region), $iN->iN_Secure($updateS3Bucket), $iN->iN_Secure($updateS3Key), $iN->iN_Secure($updateS3SecretKey), $iN->iN_Secure($updateS3Status));
		if ($updateS3Settings) {
			$overrides = storage_public_overrides_get();
			$overrides['s3_public_base'] = $s3PublicBase;
			if (!storage_public_overrides_write($overrides)) {
				exit('500');
			}
			$GLOBALS['s3PublicBase'] = $s3PublicBase !== '' ? $s3PublicBase : null;
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Amazon S3 Storage Details*/
	if ($type == 'WasSettings') {
			$updateWasRegion = $iN->iN_Secure($_POST['wasregion']);
			$updateWasBucket = $iN->iN_Secure($_POST['wasBucket']);
			$updateWasKey = $iN->iN_Secure($_POST['wasKey']);
			$updateWasSecretKey = $iN->iN_Secure($_POST['wassKey']);
			$updateWasStatus = $iN->iN_Secure($_POST['wasStatus']);
			$wasPublicBaseRaw = isset($_POST['wasPublicBase']) ? $_POST['wasPublicBase'] : '';
			$wasPublicBase = normalize_public_base_url($wasPublicBaseRaw);
		$updateWasSettings = $iN->iN_UpdateWasabiDetails($userID, $iN->iN_Secure($updateWasRegion), $iN->iN_Secure($updateWasBucket), $iN->iN_Secure($updateWasKey), $iN->iN_Secure($updateWasSecretKey), $iN->iN_Secure($updateWasStatus));
		if ($updateWasSettings) {
			$overrides = storage_public_overrides_get();
			$overrides['was_public_base'] = $wasPublicBase;
			if (!storage_public_overrides_write($overrides)) {
				exit('500');
			}
			$GLOBALS['WasPublicBase'] = $wasPublicBase !== '' ? $wasPublicBase : null;
			exit('200');
		} else {
			echo '404';
		}
	}
	/* Update MinIO (S3-compatible) settings */
	if ($type == 'MinioSettings') {
		$minioStatus     = isset($_POST['minioStatus']) ? $iN->iN_Secure($_POST['minioStatus']) : '0';
		$minioEndpoint   = isset($_POST['minioEndpoint']) ? trim($iN->iN_Secure($_POST['minioEndpoint'])) : '';
		$minioRegion     = isset($_POST['minioRegion']) ? trim($iN->iN_Secure($_POST['minioRegion'])) : 'us-east-1';
		$minioBucket     = isset($_POST['minioBucket']) ? trim($iN->iN_Secure($_POST['minioBucket'])) : '';
		$minioKey        = isset($_POST['minioKey']) ? trim($iN->iN_Secure($_POST['minioKey'])) : '';
		$minioSecret     = isset($_POST['minioSecret']) ? trim($iN->iN_Secure($_POST['minioSecret'])) : '';
		$minioPublicBase = isset($_POST['minioPublicBase']) ? trim($iN->iN_Secure($_POST['minioPublicBase'])) : '';
		$minioPathStyle  = isset($_POST['minioPathStyle']) ? '1' : '0';
		$minioSslVerify  = isset($_POST['minioSslVerify']) ? '1' : '0';

		// Prefer DB if columns exist; fallback to file config
		$updated = false;
		if (method_exists($iN, 'iN_UpdateMinioDetails')) {
			$updated = $iN->iN_UpdateMinioDetails($userID, $minioEndpoint, $minioRegion, $minioBucket, $minioKey, $minioSecret, $minioPublicBase, $minioPathStyle, $minioSslVerify, $minioStatus);
		}
		if ($updated) { exit('200'); }

		$cfgFile = __DIR__ . '/../../../includes/minio_config.php';
		$php = "<?php\n// Generated by admin MinIO settings on ".date('c')."\nif (!isset(\$inc) || !is_array(\$inc)) { \$inc = []; }\n".
			"\$inc['minio_status'] = '" . addslashes($minioStatus) . "';\n".
			"\$inc['minio_bucket'] = '" . addslashes($minioBucket) . "';\n".
			"\$inc['minio_region'] = '" . addslashes($minioRegion) . "';\n".
			"\$inc['minio_key'] = '" . addslashes($minioKey) . "';\n".
			"\$inc['minio_secret_key'] = '" . addslashes($minioSecret) . "';\n".
			"\$inc['minio_endpoint'] = '" . addslashes($minioEndpoint) . "';\n".
			"\$inc['minio_public_base'] = '" . addslashes($minioPublicBase) . "';\n".
			"\$inc['minio_path_style'] = '" . addslashes($minioPathStyle) . "';\n".
			"\$inc['minio_ssl_verify'] = '" . addslashes($minioSslVerify) . "';\n";
		$ok = @file_put_contents($cfgFile, $php) !== false;
		if ($ok) { exit('200'); } else { echo '404'; }
	}
	/* Bunny CDN (Pull Zone) settings */
	if ($type == 'BunnyCdnSettings') {
		$bunnyStatusRaw = isset($_POST['bunnyCdnStatus']) ? $iN->iN_Secure($_POST['bunnyCdnStatus']) : '0';
		$bunnyStatus = in_array((string) $bunnyStatusRaw, ['0', '1'], true) ? (string) $bunnyStatusRaw : '0';

		$bunnyBaseRaw = isset($_POST['bunnyCdnBase']) ? (string) $_POST['bunnyCdnBase'] : '';
		$bunnyBase = normalize_public_base_url($bunnyBaseRaw);

		$bunnyStorageStatusRaw = isset($_POST['bunnyStorageStatus']) ? $iN->iN_Secure($_POST['bunnyStorageStatus']) : '0';
		$bunnyStorageStatus = in_array((string) $bunnyStorageStatusRaw, ['0', '1'], true) ? (string) $bunnyStorageStatusRaw : '0';
		$bunnyStorageZone = isset($_POST['bunnyStorageZone']) ? trim((string) $iN->iN_Secure($_POST['bunnyStorageZone'])) : '';
		$bunnyStorageHost = isset($_POST['bunnyStorageHost']) ? trim((string) $iN->iN_Secure($_POST['bunnyStorageHost'])) : 'storage.bunnycdn.com';
		$bunnyStorageAccessKeyRaw = isset($_POST['bunnyStorageAccessKey']) ? trim((string) $_POST['bunnyStorageAccessKey']) : '';
		$existingAccessKey = isset($GLOBALS['bunnyStorageAccessKey']) ? (string) $GLOBALS['bunnyStorageAccessKey'] : '';
		$bunnyStorageAccessKey = $bunnyStorageAccessKeyRaw !== '' ? $bunnyStorageAccessKeyRaw : $existingAccessKey;

		if ($bunnyStorageHost === '') {
			$bunnyStorageHost = 'storage.bunnycdn.com';
		}

		// Validation & convenience:
		// - CDN-only: when CDN is enabled, require a valid Pull Zone URL.
		// - Storage: require zone + access key and a valid Pull Zone URL for public delivery.
		//   If Storage is enabled and a valid Pull Zone URL is provided, auto-enable Bunny CDN.
		if ($bunnyStatus === '1' && $bunnyBase === '') {
			exit($LANG['bunny_cdn_url_required']);
		}
		if ($bunnyStorageStatus === '1') {
			if ($bunnyStorageZone === '' || $bunnyStorageAccessKey === '') {
				exit($LANG['bunny_storage_required']);
			}
			if ($bunnyBase === '') {
				exit($LANG['bunny_cdn_url_required']);
			}
			// Ensure public delivery is enabled when using Bunny Storage
			$bunnyStatus = '1';
		}

		// Prefer DB persistence (Envato-friendly). Fallback to file-based config if columns are missing.
		$dbSaved = false;
		try {
			DB::exec(
				"UPDATE i_configurations
				 SET bunny_cdn_status = ?,
				     bunny_cdn_base = ?,
				     bunny_storage_status = ?,
				     bunny_storage_zone = ?,
				     bunny_storage_access_key = ?,
				     bunny_storage_host = ?
				 WHERE configuration_id = 1",
				[
					$bunnyStatus,
					($bunnyStatus === '1') ? $bunnyBase : null,
					$bunnyStorageStatus,
					($bunnyStorageZone !== '') ? $bunnyStorageZone : null,
					($bunnyStorageAccessKey !== '') ? $bunnyStorageAccessKey : null,
					($bunnyStorageHost !== '') ? $bunnyStorageHost : null,
				]
			);
			$dbSaved = true;
		} catch (Throwable $e) {
			$dbSaved = false;
		}

		if (!$dbSaved) {
			$overrides = storage_public_overrides_get();
			if ($bunnyStatus === '1') {
				$overrides['bunny_cdn_status'] = '1';
				$overrides['bunny_cdn_base'] = $bunnyBase;
			} else {
				unset($overrides['bunny_cdn_status'], $overrides['bunny_cdn_base']);
			}

			if (!storage_public_overrides_write($overrides)) {
				exit($LANG['bunny_settings_write_permission_error']);
			}

			// Persist Bunny Storage credentials in a separate local config file
			$bunnyCfgFile = __DIR__ . '/../../../includes/bunny_config.php';
			$bunnyCode = "<?php\n// Auto-generated Bunny Storage settings on " . date('c') . "\nif (!isset(\$inc) || !is_array(\$inc)) { \$inc = []; }\n";
			$bunnyCode .= "\$inc['bunny_storage_status'] = '" . addslashes($bunnyStorageStatus) . "';\n";
			$bunnyCode .= "\$inc['bunny_storage_zone'] = '" . addslashes($bunnyStorageZone) . "';\n";
			$bunnyCode .= "\$inc['bunny_storage_host'] = '" . addslashes($bunnyStorageHost) . "';\n";
			if ($bunnyStorageAccessKey !== '') {
				$bunnyCode .= "\$inc['bunny_storage_access_key'] = '" . addslashes($bunnyStorageAccessKey) . "';\n";
			}
			$bunnyCode .= "?>\n";
			if (@file_put_contents($bunnyCfgFile, $bunnyCode, LOCK_EX) === false) {
				exit($LANG['bunny_settings_write_permission_error']);
			}
		}

		$GLOBALS['bunnyCdnStatus'] = $bunnyStatus;
		$GLOBALS['bunnyCdnBase'] = ($bunnyStatus === '1') ? $bunnyBase : null;
		$GLOBALS['bunnyStorageStatus'] = $bunnyStorageStatus;
		$GLOBALS['bunnyStorageZone'] = $bunnyStorageZone;
		$GLOBALS['bunnyStorageHost'] = $bunnyStorageHost;
		$GLOBALS['bunnyStorageAccessKey'] = $bunnyStorageAccessKey !== '' ? $bunnyStorageAccessKey : null;

		exit('200');
	}
	/*Approve / Decline / Reject Pot*/
	if ($type == "postApprove") {
		$postDescription = $iN->iN_Secure($_POST['newpostDesc']);
		$postNewPoint = $iN->iN_Secure($_POST['newPostPoint']);
		$postApproveStat = $iN->iN_Secure($_POST['postApproveStatus']);
		$approvePostOwnerID = $iN->iN_Secure($_POST['postOwnerID']);
		$approvePostID = $iN->iN_Secure($_POST['postID']);
		$postApproveNot = $iN->iN_Secure($_POST['approve_not']);
		if (!isset($postApproveStat) || empty($postApproveStat) || $postApproveStat == '') {
			exit('You should Select the Post Status Approve, Decline or Reject');
		}
		if ($postNewPoint < $minimumPointLimit) {
			exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
		}
		$approveUpdate = $iN->iN_UpdateApprovePostStatus($userID, $iN->iN_Secure($postDescription), $iN->iN_Secure($postNewPoint), $iN->iN_Secure($postApproveStat), $iN->iN_Secure($approvePostID), $iN->iN_Secure($approvePostOwnerID), $iN->iN_Secure($postApproveNot));
		if ($approveUpdate) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
	/*Delete Post*/
	if ($type == 'deletePost') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			if (!empty($postID)) {
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? (string)$getPostFileIDs['post_file'] : '';
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = $trimValue !== '' ? array_unique(explode(',', $trimValue)) : [];

				$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if (!$theFileID) { continue; }
					$uploadedFileID = $theFileID['upload_id'] ?? null;
					$uploadedFilePath = $theFileID['uploaded_file_path'] ?? '';
					$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'] ?? '';
					$uploadedFilePathX = $theFileID['uploaded_x_file_path'] ?? '';

					if ($useRemote && function_exists('storage_delete')) {
						@storage_delete($uploadedFilePath);
						@storage_delete($uploadedFilePathX);
						@storage_delete($uploadedTumbnailFilePath);
					} else {
						@unlink('../../../' . ltrim((string)$uploadedFilePath, '/'));
						@unlink('../../../' . ltrim((string)$uploadedFilePathX, '/'));
						@unlink('../../../' . ltrim((string)$uploadedTumbnailFilePath, '/'));
					}

					if (!empty($uploadedFileID)) {
						DB::exec("DELETE FROM i_user_uploads WHERE upload_id = ?", [(int)$uploadedFileID]);
					}
				}

				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorageAdmin($userID, $postID);
				echo $deleteStoragePost ? '200' : '404';
			}
		}
	}
	/*Delete Question*/
	if ($type == 'deleteQuest') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$deletePost = $iN->iN_DeleteQuestion($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Report*/
	if ($type == 'deleteReport') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$deletePost = $iN->iN_DeleteReport($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Message Report*/
	if ($type == 'deleteMReport') {
		if (isset($_POST['id'])) {
			include_once __DIR__ . '/../../../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$reportID = $iN->iN_Secure($_POST['id']);
			$note = isset($_POST['note']) ? trim((string)$iN->iN_Secure($_POST['note'])) : '';
			$reportDetails = $iN->iN_GetMessageReportDetails($userID, $iN->iN_Secure($reportID));
			$autoNote = $LANG['report_message_auto_note_deleted'] ?? 'Your report has been finalized. The reported message was removed by our moderation team.';
			$notificationNote = $note !== '' ? $note : $autoNote;
				if ($reportDetails) {
					try {
						$iN->iN_InsertMessageReportUpdateNotification((int)$userID, $reportDetails, 'deleted', $notificationNote);
					} catch (Throwable $e) {
						// Keep delete flow stable even if notification persistence fails on older schemas.
					}
				}
			$deleteReport = $iN->iN_DeleteMessageReport($userID, $iN->iN_Secure($reportID));
			if ($deleteReport) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Edit Post*/
	if ($type == "editPostDetails") {
		$postDescription = $iN->iN_Secure($_POST['newpostDesc']);
		$editedPostOwnerID = $iN->iN_Secure($_POST['postOwnerID']);
		$editedPostID = $iN->iN_Secure($_POST['postID']);
        $postUpdate = $iN->iN_UpdatePostDetailsAdmin($userID, $iN->iN_Secure($postDescription), $iN->iN_Secure($editedPostID), $iN->iN_Secure($editedPostOwnerID));
        $postData = $iN->iN_GetAllPostDetails($editedPostID);
        if ($postData && ($postData['post_type'] ?? '') === 'campaign') {
            $campaignPayload = array(
                'title' => $_POST['campaign_title'] ?? '',
                'summary' => $_POST['campaign_summary'] ?? '',
                'goal' => $_POST['campaign_goal'] ?? '',
                'min' => $_POST['campaign_min'] ?? '',
                'max' => $_POST['campaign_max'] ?? '',
                'deadline' => '',
                'status' => $_POST['campaign_status'] ?? '',
                'cover' => $_POST['campaign_cover'] ?? ''
            );
            $cm = $_POST['campaign_deadline_month'] ?? '';
            $cd = $_POST['campaign_deadline_day'] ?? '';
            $cy = $_POST['campaign_deadline_year'] ?? '';
            $ch = $_POST['campaign_deadline_hour'] ?? '00';
            $cmi = $_POST['campaign_deadline_minute'] ?? '00';
            $cs = $_POST['campaign_deadline_second'] ?? '00';
            if ($cm !== '' && $cd !== '' && $cy !== '') {
                $campaignPayload['deadline'] = $cy . '-' . $cm . '-' . $cd . ' ' . $ch . ':' . $cmi . ':' . $cs;
            }
            $iN->iN_UpdateCampaignAdmin((int)$userID, (int)$editedPostID, $campaignPayload);
        }
		if ($postUpdate) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Edit Post*/
	if ($type == "customCodes") {
		$customCssCode = $iN->iN_Secure($_POST['customCss']);
		$customHeaderJsCode = $iN->iN_Secure($_POST['customHeaderJs']);
		$customFooterJsCode = $iN->iN_Secure($_POST['customFooterJs']);
		$updateCustomCssCode = $iN->iN_UpdateCustomCodes($userID, $customCssCode, '1');
		$updateCustomHeaderJSCode = $iN->iN_UpdateCustomCodes($userID, $customHeaderJsCode, '2');
		$updateCustomFooterJsCode = $iN->iN_UpdateCustomCodes($userID, $customFooterJsCode, '3');
		exit('200');
	}
	/*Update robots.txt*/
	if ($type == 'robotsTxt') {
		$robotsContent = isset($_POST['robots_txt']) ? (string) $_POST['robots_txt'] : '';
		$robotsContent = str_replace(["\r\n", "\r"], "\n", $robotsContent);
		$robotsContent = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $robotsContent);
		if (strlen($robotsContent) > 50000) {
			$robotsContent = substr($robotsContent, 0, 50000);
		}
		$robotsPath = rtrim(APP_ROOT_PATH, '/\\') . '/robots.txt';
		$robotsDir = dirname($robotsPath);
		if (!is_dir($robotsDir) || !is_writable($robotsDir)) {
			exit('404');
		}
		if (is_file($robotsPath) && !is_writable($robotsPath)) {
			exit('404');
		}
		$writeOk = file_put_contents($robotsPath, $robotsContent, LOCK_EX);
		if ($writeOk === false) {
			exit('404');
		}
		exit('200');
	}
	/*Edited SVG*/
	if ($type == 'editedSVG') {
		$svgCode = $iN->iN_Secure($_POST['svgcode']);
        $decodedSvg = html_entity_decode($svgCode, ENT_QUOTES);
		$iconID = $iN->iN_Secure($_POST['iconid']);
		if (!substr_count(strtolower($decodedSvg), '<svg')) {
			exit('2');
		}
		if (empty($svgCode) || $svgCode == '') {
			exit('1');
		}
		$updateSvgCode = $iN->iN_UpdateSVGCode($userID, $iN->iN_Secure($iconID), $svgCode);
		if ($updateSvgCode) {
			exit('200');
		} else {
			exit($LANG['save_failed']);
		}
	}
	/*Update Icon SVG Status*/
	if ($type == 'iconSVGStatus') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$iconID = $iN->iN_Secure($_POST['svg']);
			$updateIconSVGStatus = $iN->iN_UpdateSVGIconStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($iconID));
			if ($updateIconSVGStatus) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		}
	}
	/*Save New Svg Code*/
	if ($type == 'newSVG') {
		if (isset($_POST['newsvgcode']) && !empty($_POST['newsvgcode']) && $_POST['newsvgcode'] != '') {
			$newSVGCode = $iN->iN_Secure($_POST['newsvgcode']);
            $decodedNewSVG = html_entity_decode($newSVGCode, ENT_QUOTES);
			if (!substr_count(strtolower($decodedNewSVG), '<svg')) {
				exit('2');
			}
			$insertNewSVGCode = $iN->iN_InsertNewSVGCode($userID, $newSVGCode);
			if ($insertNewSVGCode) {
				exit('200');
			} else {
				exit($LANG['save_failed']);
			}
		} else {
			exit('1');
		}
	}
	/*Edit Plan*/
	if ($type == 'editPlan') {
		if (isset($_POST['planKey']) && isset($_POST['planPoint']) && isset($_POST['pointAmount']) && isset($_POST['planid'])) {
			$planKey = $iN->iN_Secure($_POST['planKey']);
			$planPoint = $iN->iN_Secure($_POST['planPoint']);
			$planAmount = $iN->iN_Secure($_POST['pointAmount']);
			$planID = $iN->iN_Secure($_POST['planid']);
			$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
			if (ctype_space($planPoint) || empty($planPoint)) {
				exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
			}
			if (ctype_space($planAmount) || empty($planAmount)) {
				exit(preg_replace('/{.*?}/', $maximumPointAmountLimit, $LANG['plan_point_amount_warning']));
			}
			if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
				exit($LANG['plan_key_warning']);
			}
			if (empty($removeAllSpaceFromKey) || $removeAllSpaceFromKey == '' || empty($removeAllSpaceFromKey) || strlen($removeAllSpaceFromKey) == '0' || ctype_space($removeAllSpaceFromKey)) {
				exit('404');
			} else {
				$updatePlan = $iN->iN_UpdatePlanFromID($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planPoint), $iN->iN_Secure($planAmount), $iN->iN_Secure($planID));
				if ($updatePlan) {
					exit('200');
				} else {
					exit($LANG['noway_desc']);
				}
			}
		}
	}

	/*Add New Point Plan*/
	if ($type == 'newPackageForm') {
		if (isset($_POST['planKey']) && isset($_POST['planPoint']) && isset($_POST['pointAmount'])) {
			$planKey = $iN->iN_Secure($_POST['planKey']);
			$planPoint = $iN->iN_Secure($_POST['planPoint']);
			$planAmount = $iN->iN_Secure($_POST['pointAmount']);
			$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
			if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
				exit('4');
			}
			if ($planPoint < $minimumPointLimit || ctype_space($planPoint)) {
				exit('1');
			}
			if ($planAmount > $maximumPointAmountLimit || ctype_space($planAmount) || empty($planAmount)) {
				exit('3');
			}
			$updatePlan = $iN->iN_InsertNewPointPlan($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planPoint), $iN->iN_Secure($planAmount));
			if ($updatePlan) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		} else {
			echo '5';
		}
	}
	/*Change Plan Status*/
	if ($type == 'planStatus') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$planID = $iN->iN_Secure($_POST['id']);
			$updatePlanStatus = $iN->iN_UpdatePlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
			if ($updatePlanStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Post*/
	if ($type == 'deleteThisPlan') {
		if (isset($_POST['id'])) {
			$planID = $iN->iN_Secure($_POST['id']);
			$deletePlan = $iN->iN_DeletePlanFromData($userID, $iN->iN_Secure($planID));
			if ($deletePlan) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Language Status*/
	if ($type == 'upLang') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$langID = $iN->iN_Secure($_POST['id']);
			$updateLanguageStatus = $iN->iN_UpdateLanguageStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($langID));
			if ($updateLanguageStatus) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		}
	}
	/*Add New Point Plan*/
	if ($type == 'editLanguage') {
		if (isset($_POST['langabbreviationName']) && isset($_POST['id'])) {
			$langKey = $iN->iN_Secure($_POST['langabbreviationName']);
			$langID = $iN->iN_Secure($_POST['id']);
			$removeSpaceFromLangKEY = preg_replace('/\s+/', '', $langKey);
			if (ctype_space($langKey) || !isset($langKey)) {
				exit('1');
			}
			if (!array_key_exists($langKey, $LANGNAME)) {
				exit('3');
			}
			$updateLanguage = $iN->iN_UpdateLanguageByID($userID, $iN->iN_Secure($langKey), $iN->iN_Secure($langID));
			if ($updateLanguage) {
				exit('200');
			} else {
				echo '404';
			}
		} else {
			echo '2';
		}
	}
	/*Add New Language*/
	if ($type == 'addNewLanguage') {
		if (isset($_POST['newLangAbbreviation'])) {
			$langKey = $iN->iN_Secure($_POST['newLangAbbreviation']);
			if (ctype_space($langKey) || !isset($langKey) || empty($langKey)) {
				exit('1');
			}
			if (!array_key_exists($langKey, $LANGNAME)) {
				exit('2');
			}
			$addNewLanguage = $iN->iN_AddNewLanguageFromData($userID, $iN->iN_Secure($langKey));
			if ($addNewLanguage) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Language*/
	if ($type == 'deleteThisLanguage') {
		if (isset($_POST['id'])) {
			$langID = $iN->iN_Secure($_POST['id']);
			if (ctype_space($langID) || !isset($langID) || empty($langID)) {
				exit('1');
			}
			$deleteLanguage = $iN->iN_DeleteLanguage($userID, $iN->iN_Secure($langID));
			if ($deleteLanguage) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Edit User Details*/
	if ($type == 'editUserDetails') {
		if (isset($_POST['verification']) && isset($_POST['usertype']) && isset($_POST['uwallet']) && isset($_POST['u'])) {
			$updateVerification = $iN->iN_Secure($_POST['verification']);
			$updateUserType = $iN->iN_Secure($_POST['usertype']);
			$updateUserWallet = $iN->iN_Secure($_POST['uwallet']);
			$updatedUser = $iN->iN_Secure($_POST['u']);

			if (empty($updateUserWallet)) {
				$updateUserWallet = '0';
			}
			$update = $iN->iN_UpdateUserProfile($userID, $iN->iN_Secure($updatedUser), $iN->iN_Secure($updateVerification), $iN->iN_Secure($updateUserType), $iN->iN_Secure($updateUserWallet));
			if ($update) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete User*/
	if ($type == 'deleteUser') {
		if (isset($_POST['id'])) {
			$deleteUserID = $iN->iN_Secure($_POST['id']);
			$deleteUser = $iN->iN_DeleteUser($userID, $iN->iN_Secure($deleteUserID));
			if ($deleteUser) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete User Verification Request*/
	if ($type == 'deleteUserVerification') {
		if (isset($_POST['id'])) {
			$verificationRequestID = $iN->iN_Secure($_POST['id']);
			$deleteVRequest = $iN->iN_DeleteVerificationRequest($userID, $iN->iN_Secure($verificationRequestID));
			if ($deleteVRequest) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Approve or Reject Verification Request*/
	if ($type == 'updateVerificationStatus') {
		if (isset($_POST['vID']) && isset($_POST['vApproveStatus'])) {
			$answerType = $iN->iN_Secure($_POST['vApproveStatus']);
			$answerValue = $iN->iN_Secure($_POST['approve_not']);
			$answeringVerificationID = $iN->iN_Secure($_POST['vID']);
			if (empty($answerType)) {
				exit('1');
			}
			if($answerType == '1'){
               $emailBody = $iN->iN_Secure($LANG['verification_accepted_email_not']);
			   $emailTitle = $iN->iN_Secure($LANG['your_confirmation_accepted_email_title']);
			   $finishButton = $iN->iN_Secure($LANG['finish_your_confirmation']);
			}else{
               $emailBody = $iN->iN_Secure($LANG['verification_declined_email_not']);
			   $emailTitle = $iN->iN_Secure($LANG['your_confirmation_declined_email_title']);
			   $finishButton = $iN->iN_Secure($LANG['re_send_your_verification_request']);
			}
			$InsertAnswer = $iN->iN_UpdateVerificationProfileStatus($userID, $iN->iN_Secure($answerType), $iN->iN_Secure($answerValue), $iN->iN_Secure($answeringVerificationID));
			if ($InsertAnswer) {
				$dataV = $iN->iN_GetVerificationRequestFromID($answeringVerificationID);
				$iuIDfk = $dataV['iuid_fk'];
				// Notify the user about the verification decision (in-app notification)
				try { $iN->iN_InsertNotificationForVerificationDecision($userID, (int)$iuIDfk, $answerType == '1'); } catch (Throwable $e) { /* ignore */ }
                $dataEmail = $iN->iN_GetUserDetails($iuIDfk);
                $sendEmail = $dataEmail['i_user_email'];

                // Push notification via OneSignal (only if configured and device key exists)
                try {
                    $oneSignalUserDeviceKey = isset($dataEmail['device_key']) ? $dataEmail['device_key'] : null;
                    if (
                        isset($oneSignalStatus) && $oneSignalStatus === 'open' &&
                        !empty($oneSignalApi) && !empty($oneSignalRestApi) &&
                        !empty($oneSignalUserDeviceKey)
                    ) {
                        $msgTitle = $emailTitle;
                        $msgBody  = $answerType == '1' ? ($LANG['verification_accepted_email_not'] ?? 'Your verification was approved')
                                                       : ($LANG['verification_declined_email_not'] ?? 'Your verification was declined');
                        $urlPush  = $base_url . 'creator/becomeCreator';
                        $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $urlPush, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
                    }
                } catch (Throwable $e) { /* ignore push errors */ }
				$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden; display:flex; display:-webkit-flex;";
                $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:15px; border-radius:3px;";
                $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
                $imgStyle = "width:100%; overflow:hidden;";
                $contentStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
                $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff !important;";
                $linkStyle = "text-decoration:none; color:#ffffff !important; font-weight:500; font-size:14px; position:relative;";

				if ($emailSendStatus == '1') {
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

                        <div style="' . $contentStyle . '">
                          ' . $emailBody . '
                        </div>

                        <div style="' . $buttonBoxStyle . '">
                          <a href="' . $base_url . '" style="' . $linkStyle . '">' . $finishButton . '</a>
                        </div>

                      </div>
                    </div>';
					$mail->setFrom($smtpEmail, $siteName);
					$send = false;
					$mail->IsHTML(true);
					$mail->addAddress($sendEmail, ''); // Add a recipient
					$mail->Subject = $emailTitle;
					$mail->CharSet = 'utf-8';
					$mail->MsgHTML($body);
					try {
						if ($mail->send()) {
							$mail->ClearAddresses();
							echo '200';
							return true;
						}
						echo "Mailer Error: " . $mail->ErrorInfo;
					} catch (Throwable $e) {
						// Prevent raw stack traces; log and show concise error.
						error_log('[MAIL][verification_decision] ' . $e->getMessage());
						echo "Mailer Error: " . $e->getMessage();
					}
					/***********************************/
				}
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Page Details*/
	if ($type == 'editPage') {
		if (isset($_POST['page_title']) && isset($_POST['page_seo_url']) && isset($_POST['editor']) && isset($_POST['pageID'])) {
			$pageTitle = $iN->iN_Secure($_POST['page_title']);
			$pageSeoUrl = $iN->iN_Secure($_POST['page_seo_url']);
			$pageEditor = isset($_POST['editor']) ? (string)$_POST['editor'] : '';
            // Sanitize but keep page markup intact for design pages
			$pageEditor = $iN->xss_clean($pageEditor);
			$pageID = $iN->iN_Secure($_POST['pageID']);
			if (empty($pageTitle)) {
				exit('1');
			}
			if (empty($pageSeoUrl)) {
				exit('2');
			}
			$savePageEdit = $iN->iN_SavePageEdit(
                $userID,
                $iN->iN_Secure($pageTitle),
                $iN->iN_Secure($iN->url_slugies($pageSeoUrl)),
                $pageEditor,
                $iN->iN_Secure($pageID)
            );
			if ($savePageEdit) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Create a New Page*/
	if ($type == 'createNewPage') {
		if (isset($_POST['page_title']) && isset($_POST['page_seo_url']) && isset($_POST['editor'])) {
			$pageTitle = $iN->iN_Secure($_POST['page_title']);
			$pageSeoUrl = $iN->iN_Secure($_POST['page_seo_url']);
			$pageEditor = isset($_POST['editor']) ? (string)$_POST['editor'] : '';
            // Preserve page content while sanitizing unsafe markup
			$pageEditor = $iN->xss_clean($pageEditor);
			if (empty($pageTitle)) {
				exit('1');
			}
			if (empty($pageSeoUrl)) {
				exit('2');
			}
			$createANewPage = $iN->iN_CreateANewPage(
                $userID,
                $iN->iN_Secure($pageTitle),
                $iN->iN_Secure($iN->url_slugies($pageSeoUrl)),
                $pageEditor
            );
			if ($createANewPage) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
    /*Create or Update Blog Post*/
    if ($type == 'createBlogPost' || $type == 'updateBlogPost') {
        $blogID = isset($_POST['blog_id']) ? (int) $iN->iN_Secure($_POST['blog_id']) : 0;
        $title = isset($_POST['blog_title']) ? $iN->iN_Secure($_POST['blog_title']) : '';
        $slugInput = isset($_POST['blog_slug']) ? $iN->iN_Secure($_POST['blog_slug']) : $title;
        $excerpt = isset($_POST['blog_excerpt']) ? $iN->iN_Secure($_POST['blog_excerpt']) : '';
        $content = isset($_POST['blog_content']) ? $_POST['blog_content'] : '';
        $status = isset($_POST['blog_status']) ? $_POST['blog_status'] : 'draft';
        $isFeatured = isset($_POST['blog_featured']) && $_POST['blog_featured'] === '1' ? '1' : '0';
        $allowReactions = isset($_POST['blog_allow_reactions']) && $_POST['blog_allow_reactions'] === '0' ? '0' : '1';
        $publishDateRaw = isset($_POST['blog_publish_date']) ? trim((string) $_POST['blog_publish_date']) : '';
        $publishedAt = null;
        if (!empty($publishDateRaw)) {
            $ts = strtotime($publishDateRaw);
            if ($ts !== false) {
                $publishedAt = $ts;
            }
        }
        $coverUrl = isset($_POST['blog_cover_url']) ? trim((string) $_POST['blog_cover_url']) : '';
        $metaTitle = isset($_POST['blog_meta_title']) ? trim((string) $_POST['blog_meta_title']) : '';
        $metaDescription = isset($_POST['blog_meta_description']) ? trim((string) $_POST['blog_meta_description']) : '';
        if (trim($title) === '') {
            exit('1');
        }
        if (trim($content) === '') {
            exit('2');
        }
        $coverUploadId = null;
        if (!empty($_FILES['blog_cover_file']['name'])) {
            [$uploadError, $uploadId] = iN_admin_handle_blog_cover_upload($_FILES['blog_cover_file'], $userID, $iN);
            if ($uploadError === 'invalid_format') {
                exit($LANG['invalid_file_format']);
            }
            if ($uploadError === 'file_is_too_large') {
                exit($LANG['file_is_too_large']);
            }
            if ($uploadError !== null) {
                exit($LANG['upload_failed']);
            }
            $coverUploadId = $uploadId;
        }
        $payload = array(
            'title' => $title,
            'slug' => $slugInput,
            'excerpt' => $excerpt,
            'content_html' => $content,
            'status' => $status,
            'is_featured' => $isFeatured,
            'allow_reactions' => $allowReactions,
            'published_at' => $publishedAt,
            'cover_url' => $coverUrl,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription
        );
        if ($coverUploadId) {
            $payload['cover_upload_id'] = $coverUploadId;
        }
        $result = false;
        if ($type === 'createBlogPost') {
            $result = $iN->iN_CreateBlogPost($userID, $payload);
        } else if ($type === 'updateBlogPost' && $blogID > 0) {
            $result = $iN->iN_UpdateBlogPost($userID, $blogID, $payload);
        }
        if ($result) {
            exit('200');
        }
        echo iN_HelpSecure($LANG['noway_desc']);
    }
	/*Delete Post*/
	if ($type == 'deletePage') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$deletePost = $iN->iN_DeletePage($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
    }
    if ($type == 'deleteBlogPost') {
        if (isset($_POST['id'])) {
            $blogID = (int) $iN->iN_Secure($_POST['id']);
            $deleted = $iN->iN_DeleteBlogPost($userID, $blogID);
            if ($deleted) {
                exit('200');
            } else {
                if ($iN->iN_CheckIsAdmin($userID) != 1) {
                    echo iN_HelpSecure($LANG['noway_desc']);
                } elseif (!$iN->iN_CheckBlogExistsById($blogID)) {
                    echo iN_HelpSecure($LANG['blog_not_found']);
                } else {
                    echo iN_HelpSecure($LANG['noway_desc']);
                }
            }
        }
    }
	/*Delete QA*/
	if ($type == 'deleteQA') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$deletePost = $iN->iN_DeleteQA($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}

	/*Edited Sticker URL*/
	if ($type == 'stickerEdit') {
		if (isset($_POST['stickerURL']) && isset($_POST['sid'])) {
			$stickerUrl = $iN->iN_Secure($_POST['stickerURL']);
			$sID = $iN->iN_Secure($_POST['sid']);
			if (ctype_space($stickerUrl) || !isset($stickerUrl) || empty($stickerUrl)) {
				exit('1');
			}
			if (filter_var($stickerUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (!preg_match('/\.(jpeg|jpg|png|gif)$/i', $stickerUrl)) {
				exit('3');
			}
			$updateStickerURL = $iN->iN_UpdateStickerURL($userID, $iN->iN_Secure($stickerUrl), $iN->iN_Secure($sID));
			if ($updateStickerURL) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete User*/
	if ($type == 'deleteSticker') {
		if (isset($_POST['id'])) {
			$deleteStickerID = $iN->iN_Secure($_POST['id']);
			$deleteSTicker = $iN->iN_DeleteSticker($userID, $iN->iN_Secure($deleteStickerID));
			if ($deleteSTicker) {
				exit('200');
			} else {
				exit($LANG['sticker_id_not_available']);
			}
		}
	}
/*Add New Sticker Url*/
	if ($type == 'stickerNew') {
		if (isset($_POST['stickerURL'])) {
			$newStickerUrl = $iN->iN_Secure($_POST['stickerURL']);
			if (ctype_space($newStickerUrl) || !isset($newStickerUrl) || empty($newStickerUrl)) {
				exit('1');
			}
			if (filter_var($newStickerUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (!preg_match('/\.(jpeg|jpg|png|gif)$/i', $newStickerUrl)) {
				exit('3');
			}
			$insertNewSticker = $iN->iN_InsertNewStickerURL($userID, $iN->iN_Secure($newStickerUrl));
			if ($insertNewSticker) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		} else {
			exit('1');
		}
	}
/*Update Sticker Status*/
	if ($type == 'upStick') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$langID = $iN->iN_Secure($_POST['id']);
			$updateStickerStatus = $iN->iN_UpdateStickerStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($langID));
			if ($updateStickerStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Payment Settings*/
	if ($type == 'paymentSettings') {
		if (isset($_POST['default_currency']) && isset($_POST['fee_comission']) && isset($_POST['min_point_amount']) && isset($_POST['min_sub_weekly']) && isset($_POST['min_sub_monthly']) && isset($_POST['min_sub_yearly']) && isset($_POST['min_point_amount']) && isset($_POST['max_point_amount']) && isset($_POST['point_to_dolar']) && isset($_POST['min_withdrawl_amount']) && isset($_POST['currency_symbol_position']) && isset($_POST['currency_decimal_places']) && isset($_POST['currency_thousand_separator']) && isset($_POST['currency_decimal_separator'])) {
			$defaultCurrency = $iN->iN_Secure($_POST['default_currency']);
			$defaultSubType = $iN->iN_Secure($_POST['choose_sub_type']);
			$comissionFee = $iN->iN_Secure($_POST['fee_comission']);
			$minimumSubscriptionAmountWeekly = $iN->iN_Secure($_POST['min_sub_weekly']);
			$minimumSubscriptionAmountMonthly = $iN->iN_Secure($_POST['min_sub_monthly']);
			$minimumSubscriptionAmountYearly = $iN->iN_Secure($_POST['min_sub_yearly']);
			$minimumPointAmount = $iN->iN_Secure($_POST['min_point_amount']);
			$maximumPointAmount = $iN->iN_Secure($_POST['max_point_amount']);
            $pointToMoney = $iN->iN_Secure($_POST['point_to_dolar']);
            // Normalize decimal separator to dot to ensure correct math
            $pointToMoney = str_replace(',', '.', $pointToMoney);
            // Validate and normalize precision for point->money ratio
            if (!is_numeric($pointToMoney)) {
                exit('1');
            }
            $pointToMoney = (float)$pointToMoney;
            // Enforce minimum 0.001 to keep calculations meaningful
            if ($pointToMoney < 0.001) {
                exit('1');
            }
            // Limit precision to 3 decimals to match UI step and avoid float artifacts
            $pointToMoney = round($pointToMoney, 3);
				$minWihDrawlAmount = $iN->iN_Secure($_POST['min_withdrawl_amount']);
	            $minFeePointWeekly = $iN->iN_Secure($_POST['min_point_fee_weekly']);
				$minFeePointMonthly = $iN->iN_Secure($_POST['min_point_fee_monthly']);
				$minFeePointYearly = $iN->iN_Secure($_POST['min_point_fee_yearly']);
				$minTipAmount = $iN->iN_Secure($_POST['min_tip_amount']);
				$communityPlanGatewayAmount = (float)str_replace(',', '.', (string)$minimumSubscriptionAmountMonthly);
				$communityPlanPointsAmount = (float)str_replace(',', '.', (string)$minFeePointMonthly);
				if ($communityPlanGatewayAmount <= 0 || $communityPlanPointsAmount <= 0) {
					exit('1');
				}
				$currencySymbolPosition = $iN->iN_Secure($_POST['currency_symbol_position']);
			$currencySymbolPosition = in_array($currencySymbolPosition, ['left', 'right'], true) ? $currencySymbolPosition : 'left';
			$currencyDecimalPlaces = (int)$iN->iN_Secure($_POST['currency_decimal_places']);
			if ($currencyDecimalPlaces < 0 || $currencyDecimalPlaces > 4) {
				exit('1');
			}
			$currencyThousandSeparator = $iN->iN_Secure($_POST['currency_thousand_separator']);
			$allowedThousands = ['comma', 'dot', 'space', 'none'];
			if (!in_array($currencyThousandSeparator, $allowedThousands, true)) {
				$currencyThousandSeparator = 'comma';
			}
			$currencyDecimalSeparator = $iN->iN_Secure($_POST['currency_decimal_separator']);
			$allowedDecimalSeparators = ['dot', 'comma'];
			if (!in_array($currencyDecimalSeparator, $allowedDecimalSeparators, true)) {
				$currencyDecimalSeparator = 'dot';
			}
			if (empty($minFeePointWeekly) || empty($minTipAmount) || empty($minFeePointMonthly) || empty($minFeePointYearly) ||empty($minimumSubscriptionAmountMonthly) || empty($minimumSubscriptionAmountWeekly) || empty($minimumSubscriptionAmountYearly) || empty($minimumPointAmount) || empty($maximumPointAmount) || empty($pointToMoney) || empty($minWihDrawlAmount)) {
				exit('1');
			}
			$updatePaymentSettings = $iN->iN_UpdatePaymentSettings($userID, $iN->iN_Secure($minTipAmount), $iN->iN_Secure($defaultSubType), $iN->iN_Secure($defaultCurrency), $iN->iN_Secure($comissionFee), $iN->iN_Secure($minimumSubscriptionAmountWeekly), $iN->iN_Secure($minimumSubscriptionAmountMonthly), $iN->iN_Secure($minimumSubscriptionAmountYearly), $iN->iN_Secure($minimumPointAmount), $iN->iN_Secure($maximumPointAmount), $iN->iN_Secure($pointToMoney), $iN->iN_Secure($minWihDrawlAmount), $iN->iN_Secure($minFeePointWeekly), $iN->iN_Secure($minFeePointMonthly), $iN->iN_Secure($minFeePointYearly), $currencySymbolPosition, $currencyDecimalPlaces, $currencyThousandSeparator, $currencyDecimalSeparator);
			if ($updatePaymentSettings) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayPal Mode Status*/
	if ($type == 'sendboxmode') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePayPalSendBoxMode = $iN->iN_UpdatePayPalSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updatePayPalSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayPal Status*/
	if ($type == 'paypal_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePayPalStatus = $iN->iN_UpdatePayPalStatus($userID, $iN->iN_Secure($mod));
			if ($updatePayPalStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayPal Business And Sandbox Email Address*/
	if ($type == 'updatePaypal') {
		if (isset($_POST['sndbox_email']) && isset($_POST['product_email']) && isset($_POST['paypal_currency'])) {
			$sandBoxEmail = $iN->iN_Secure($_POST['sndbox_email']);
			$paypalProductEmail = $iN->iN_Secure($_POST['product_email']);
			$paypalCurrency = $iN->iN_Secure($_POST['paypal_currency']);
			$updatePayPalDetails = $iN->iN_UpdatePayPalDetails($userID, $iN->iN_Secure($sandBoxEmail), $iN->iN_Secure($paypalProductEmail), $iN->iN_Secure($paypalCurrency));
			if ($updatePayPalDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update BitPay Mode Status*/
	if ($type == 'bitpay_mode') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateBitPaySendBoxMode = $iN->iN_UpdateBitPaySendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateBitPaySendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update BitPay Status*/
	if ($type == 'bitpay_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateBitPayStatus = $iN->iN_UpdateBitPayStatus($userID, $iN->iN_Secure($mod));
			if ($updateBitPayStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update BitPay Business And Sandbox Email Address*/
	if ($type == 'updateBitPay') {
		if (isset($_POST['notification_email']) && isset($_POST['bit_password']) && isset($_POST['pairinccode']) && isset($_POST['bitLabel']) && isset($_POST['bitpay_currency'])) {
			$bitNotificationEmail = $iN->iN_Secure($_POST['notification_email']);
			$bitPassword = $iN->iN_Secure($_POST['bit_password']);
			$bitPairingCode = $iN->iN_Secure($_POST['pairinccode']);
			$bitLabel = $iN->iN_Secure($_POST['bitLabel']);
			$bitCurrency = $iN->iN_Secure($_POST['bitpay_currency']);
			$updateBitPayDetails = $iN->iN_UpdateBitPayDetails($userID, $iN->iN_Secure($bitNotificationEmail), $iN->iN_Secure($bitPassword), $iN->iN_Secure($bitPairingCode), $iN->iN_Secure($bitLabel), $iN->iN_Secure($bitCurrency));
			if ($updateBitPayDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Stripe Mode Status*/
	if ($type == 'stripe_mode') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateStripeSendBoxMode = $iN->iN_UpdateStripeSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateStripeSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Stripe Status*/
	if ($type == 'stripe_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateStripeStatus = $iN->iN_UpdateStripeStatus($userID, $iN->iN_Secure($mod));
			if ($updateStripeStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update StripeDetails */
	if ($type == 'updateStripe') {
		if (isset($_POST['testSecretKey']) && isset($_POST['testPublicKey']) && isset($_POST['liveSecretKey']) && isset($_POST['livePublicKey']) && isset($_POST['stripe_currency'])) {
			$stTestSecretKey = $iN->iN_Secure($_POST['testSecretKey']);
			$stTestPublicKey = $iN->iN_Secure($_POST['testPublicKey']);
			$stLiveSecretKey = $iN->iN_Secure($_POST['liveSecretKey']);
			$stLivePublicKey = $iN->iN_Secure($_POST['livePublicKey']);
			$stCurrency = $iN->iN_Secure($_POST['stripe_currency']);
			$updateStripeDetails = $iN->iN_UpdateStripeDetails($userID, $iN->iN_Secure($stTestSecretKey), $iN->iN_Secure($stTestPublicKey), $iN->iN_Secure($stLiveSecretKey), $iN->iN_Secure($stLivePublicKey), $iN->iN_Secure($stCurrency));
			if ($updateStripeDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update authorizenet Mode Status*/
	if ($type == 'authorize_mode') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateAuthorizeNetSendBoxMode = $iN->iN_UpdateAuthorizeNetSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateAuthorizeNetSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update authorizenet Status*/
	if ($type == 'authorizenet_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateAuthorizeNetStatus = $iN->iN_UpdateAuthorizeNetStatus($userID, $iN->iN_Secure($mod));
			if ($updateAuthorizeNetStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update AuthorizeNet*/
	if ($type == 'updateAuthorizeNet') {
		if (isset($_POST['testAppID']) && isset($_POST['testTransactionKEY']) && isset($_POST['liveAppID']) && isset($_POST['liveTransactionKEY']) && isset($_POST['authorizenet_currency'])) {
			$autTestAppID = $iN->iN_Secure($_POST['testAppID']);
			$autTestTransactionKey = $iN->iN_Secure($_POST['testTransactionKEY']);
			$autLiveAppID = $iN->iN_Secure($_POST['liveAppID']);
			$autLiveTransactionKey = $iN->iN_Secure($_POST['liveTransactionKEY']);
			$autCurrency = $iN->iN_Secure($_POST['authorizenet_currency']);

			$updateAuthorizeNetDetails = $iN->iN_UpdateAuthorizeNetDetails($userID, $iN->iN_Secure($autTestAppID), $iN->iN_Secure($autTestTransactionKey), $iN->iN_Secure($autLiveAppID), $iN->iN_Secure($autLiveTransactionKey), $iN->iN_Secure($autCurrency));
			if ($updateAuthorizeNetDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update IyziCo Mode Status*/
	if ($type == 'iyzico_mode') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateIyziCoSendBoxMode = $iN->iN_UpdateIyziCoSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateIyziCoSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update IyziCo Status*/
	if ($type == 'iyzico_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateIyziCoStatus = $iN->iN_UpdateIyziCoStatus($userID, $iN->iN_Secure($mod));
			if ($updateIyziCoStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update IyziCo*/
	if ($type == 'updateIyziCo') {
		if (isset($_POST['iyziTestSecretKey']) && isset($_POST['iyziTestApiKey']) && isset($_POST['iyziLiveApiKey']) && isset($_POST['iyziLiveApiSeckretKey']) && isset($_POST['iyzico_crncy'])) {
			$iyziTestSecretKey = $iN->iN_Secure($_POST['iyziTestSecretKey']);
			$iyziTestApiKey = $iN->iN_Secure($_POST['iyziTestApiKey']);
			$iyziLiveApiKey = $iN->iN_Secure($_POST['iyziLiveApiKey']);
			$iyziLiveApiSeckretKey = $iN->iN_Secure($_POST['iyziLiveApiSeckretKey']);
			$iyziCurrency = $iN->iN_Secure($_POST['iyzico_crncy']);
			$updateIyziCoDetails = $iN->iN_UpdateIyziCoDetails($userID, $iN->iN_Secure($iyziTestSecretKey), $iN->iN_Secure($iyziTestApiKey), $iN->iN_Secure($iyziLiveApiKey), $iN->iN_Secure($iyziLiveApiSeckretKey), $iN->iN_Secure($iyziCurrency));
			if ($updateIyziCoDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update RazorPay Mode Status*/
	if ($type == 'razorpay_mode') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateRazorPaySendBoxMode = $iN->iN_UpdateRazorPaySendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateRazorPaySendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update RazorPay Status*/
	if ($type == 'razorpay_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateRazorPayStatus = $iN->iN_UpdateRazorPayStatus($userID, $iN->iN_Secure($mod));
			if ($updateRazorPayStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update RazorPay*/
	if ($type == 'updateRazorPay') {
		if (isset($_POST['razorTestKey']) && isset($_POST['razorTestSecret']) && isset($_POST['razorLiveKey']) && isset($_POST['razorLiveSecret']) && isset($_POST['razorpay_crncy'])) {
			$razorTestKey = $iN->iN_Secure($_POST['razorTestKey']);
			$razorTestSecret = $iN->iN_Secure($_POST['razorTestSecret']);
			$razorLiveKey = $iN->iN_Secure($_POST['razorLiveKey']);
			$razorLiveSecret = $iN->iN_Secure($_POST['razorLiveSecret']);
			$razorCurrency = $iN->iN_Secure($_POST['razorpay_crncy']);
			$updateRazorPayDetails = $iN->iN_UpdateRazorPayDetails($userID, $iN->iN_Secure($razorTestKey), $iN->iN_Secure($razorTestSecret), $iN->iN_Secure($razorLiveKey), $iN->iN_Secure($razorLiveSecret), $iN->iN_Secure($razorCurrency));
			if ($updateRazorPayDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayStack Mode Status*/
	if ($type == 'paystack_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePayStackSendBoxMode = $iN->iN_UpdatePayStackSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updatePayStackSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayStack Status*/
	if ($type == 'paystack_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePayStackStatus = $iN->iN_UpdatePayStackStatus($userID, $iN->iN_Secure($mod));
			if ($updatePayStackStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayStack*/
	if ($type == 'updatePayStack') {
		if (isset($_POST['paystackTestSecret']) && isset($_POST['paystackTestPublic']) && isset($_POST['paystackLiveSecretKey']) && isset($_POST['paystackLivePublicKey']) && isset($_POST['paystack_crncy'])) {
			$payStackTestSecret = $iN->iN_Secure($_POST['paystackTestSecret']);
			$payStackTestPublic = $iN->iN_Secure($_POST['paystackTestPublic']);
			$payStackLiveSecret = $iN->iN_Secure($_POST['paystackLiveSecretKey']);
			$payStackLivePublic = $iN->iN_Secure($_POST['paystackLivePublicKey']);
			$payStackCurrency = $iN->iN_Secure($_POST['paystack_crncy']);
			$updatePayStackDetails = $iN->iN_UpdatePayStackDetails($userID, $iN->iN_Secure($payStackTestSecret), $iN->iN_Secure($payStackTestPublic), $iN->iN_Secure($payStackLiveSecret), $iN->iN_Secure($payStackLivePublic), $iN->iN_Secure($payStackCurrency));
			if ($updatePayStackDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Flutterwave Mode*/
	if ($type == 'flutterwave_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateFlutterwaveMode = $iN->iN_UpdateFlutterwaveSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateFlutterwaveMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Flutterwave Status*/
	if ($type == 'flutterwave_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateFlutterwaveStatus = $iN->iN_UpdateFlutterwaveStatus($userID, $iN->iN_Secure($mod));
			if ($updateFlutterwaveStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Flutterwave*/
	if ($type == 'updateFlutterwave') {
		if (
			isset($_POST['flutterwaveTestPublicKey']) &&
			isset($_POST['flutterwaveTestSecretKey']) &&
			isset($_POST['flutterwaveLivePublicKey']) &&
			isset($_POST['flutterwaveLiveSecretKey']) &&
			isset($_POST['flutterwaveEncryptionKey']) &&
			isset($_POST['flutterwaveSecretHash']) &&
			isset($_POST['flutterwave_crncy'])
		) {
			$testPublic = $iN->iN_Secure($_POST['flutterwaveTestPublicKey']);
			$testSecret = $iN->iN_Secure($_POST['flutterwaveTestSecretKey']);
			$livePublic = $iN->iN_Secure($_POST['flutterwaveLivePublicKey']);
			$liveSecret = $iN->iN_Secure($_POST['flutterwaveLiveSecretKey']);
			$encryptionKey = $iN->iN_Secure($_POST['flutterwaveEncryptionKey']);
			$secretHash = $iN->iN_Secure($_POST['flutterwaveSecretHash']);
			$currency = $iN->iN_Secure($_POST['flutterwave_crncy']);
			$updateFlutterwaveDetails = $iN->iN_UpdateFlutterwaveDetails(
				$userID,
				$testPublic,
				$testSecret,
				$livePublic,
				$liveSecret,
				$currency,
				$secretHash,
				$encryptionKey
			);
			if ($updateFlutterwaveDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Tax Status*/
	if ($type == 'tax_status') {
		if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateStatus = $iN->iN_UpdateTaxStatus($userID, $mod);
			if ($updateStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Tax Settings*/
	if ($type == 'updateTaxSettings') {
		if (isset($_POST['tax_rate']) && isset($_POST['tax_label'])) {
			$taxRate = $iN->iN_Secure($_POST['tax_rate']);
			$taxLabel = $iN->iN_Secure($_POST['tax_label']);
			$taxRegistration = isset($_POST['tax_registration']) ? $iN->iN_Secure($_POST['tax_registration']) : NULL;
			$taxCompany = isset($_POST['tax_company']) ? $iN->iN_Secure($_POST['tax_company']) : NULL;
			$taxCompanyAddress = isset($_POST['tax_company_address']) ? $iN->iN_Secure($_POST['tax_company_address']) : NULL;
			$taxPrefix = isset($_POST['tax_invoice_prefix']) ? $iN->iN_Secure($_POST['tax_invoice_prefix']) : 'INV';
			$updateTax = $iN->iN_UpdateTaxSettings($userID, $taxRate, $taxLabel, $taxRegistration, $taxCompany, $taxCompanyAddress, $taxPrefix);
			if ($updateTax) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Setting social Login Status*/
	if ($type == 'sLoginSet') {
		$GoogleCliendID = $iN->iN_Secure($_POST['google_cliend_id']);
		$TwitterCliendID = $iN->iN_Secure($_POST['twitter_cliend_id']);
		$GoogleIcon = $iN->iN_Secure($_POST['google_icon']);
		$TwitterIcon = $iN->iN_Secure($_POST['twitter_icon']);
		$GoogleCliendSecret = $iN->iN_Secure($_POST['google_cliend_secret']);
		$TwitterCliendSecret = $iN->iN_Secure($_POST['twitter_cliend_secret']);
		$GoogleSocialLoginStatus = $iN->iN_Secure($_POST['google_status']);
		$TwitterSocialLoginStatus = $iN->iN_Secure($_POST['twitter_status']);

		if ($GoogleSocialLoginStatus == '1') {
			if (empty($GoogleCliendID) || empty($GoogleCliendSecret)) {
				exit($LANG['fill_all_google_requirements']);
			}
		}
		if ($TwitterSocialLoginStatus == '1') {
			if (empty($TwitterCliendID) || empty($TwitterCliendSecret)) {
				exit($LANG['fill_all_twitter_requirements']);
			}
		}
		$UpdateSocialLoginDetails = $iN->iN_UpdateSocialLoginDetails($userID, $iN->iN_Secure($GoogleCliendID), $iN->iN_Secure($TwitterCliendID), $iN->iN_Secure($GoogleIcon), $iN->iN_Secure($TwitterIcon), $iN->iN_Secure($GoogleCliendSecret), $iN->iN_Secure($TwitterCliendSecret), $iN->iN_Secure($GoogleSocialLoginStatus), $iN->iN_Secure($TwitterSocialLoginStatus));
		if ($UpdateSocialLoginDetails) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
/*Mark As Paid*/
	if ($type == 'paid') {
		if (isset($_POST['id']) && !empty($_POST['id'])) {
			$paymentID = $iN->iN_Secure($_POST['id']);
			$updatePayoutStatus = $iN->iN_UpdatePayoutStatus($userID, $paymentID);
			if ($updatePayoutStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
/*Yes Decline Payment Request*/
	if ($type == 'yesDecline') {
		if (isset($_POST['id']) && !empty($_POST['id'])) {
			$declinedID = $iN->iN_Secure($_POST['id']);
			$checkPaymentRequestID = $iN->iN_CheckPaymentRequestIDExist($userID, $declinedID);
			if ($checkPaymentRequestID) {
				$okDecline = $iN->iN_DeclineRequest($userID, $iN->iN_Secure($declinedID));
				if ($okDecline) {
					exit('200');
				} else {
					echo '404';
				}
			} else {
				exit($LANG['payment_request_no_longer_available']);
			}
		}
	}
/*Yes Delete Payout From Data*/
	if ($type == 'deletePayoutt') {
		if (isset($_POST['id']) && !empty($_POST['id'])) {
			$deleteID = $iN->iN_Secure($_POST['id']);
			$checkPaymentRequestID = $iN->iN_CheckPaymentRequestIDExist($userID, $deleteID);
			if ($checkPaymentRequestID) {
				$okDelete = $iN->iN_DeletePayoutRequest($userID, $iN->iN_Secure($deleteID));
				if ($okDelete) {
					exit('200');
				} else {
					echo '404';
				}
			} else {
				exit($LANG['payment_request_no_longer_available']);
			}
		}
	}
	if ($type == 'adsFile') {
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
						if (!file_exists($uploadAdsImage . $d)) {
							$newFile = mkdir($uploadAdsImage . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadAdsImage . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/spImages/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'uploads/spImages/' . $d . '/' . $getFilename;
							}
							echo $UploadSourceUrl;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
/*Insert New Ads*/
	if ($type == 'adsDForm') {
		if (isset($_POST['adsFile']) && isset($_POST['ads_title']) && isset($_POST['ads_description']) && isset($_POST['ads_url'])) {
			$adsImage = $iN->iN_Secure($_POST['adsFile']);
			$adsTitle = $iN->iN_Secure($_POST['ads_title']);
			$adsDescription = $iN->iN_Secure($_POST['ads_description']);
			$adsRedirectUrl = $iN->iN_Secure($_POST['ads_url']);
			if (empty($adsImage)) {
				exit('3');
			}
			if (filter_var($adsRedirectUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (empty($adsTitle)) {
				exit('4');
			}
			if (!empty($adsImage) && !empty($adsTitle) && !empty($adsRedirectUrl)) {
				$insertNewAds = $iN->iN_InsertNewAdvertisement($userID, $iN->iN_Secure($adsImage), $iN->iN_Secure($adsTitle), $iN->iN_Secure($adsDescription), $iN->iN_Secure($adsRedirectUrl));
				if ($insertNewAds) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
/*Change Ads Status*/
	if ($type == 'adsStatus') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$adsID = $iN->iN_Secure($_POST['id']);
			$updateAdsStatus = $iN->iN_UpdateAdsStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($adsID));
			if ($updateAdsStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
/*Insert New Ads*/
	if ($type == 'adsUForm') {
		if (isset($_POST['adsFile']) && isset($_POST['ads_title']) && isset($_POST['ads_description']) && isset($_POST['ads_url']) && isset($_POST['adsi'])) {
			$adsImage = $iN->iN_Secure($_POST['adsFile']);
			$adsTitle = $iN->iN_Secure($_POST['ads_title']);
			$adsDescription = $iN->iN_Secure($_POST['ads_description']);
			$adsRedirectUrl = $iN->iN_Secure($_POST['ads_url']);
			$editingAdsID = $iN->iN_Secure($_POST['adsi']);
			if (empty($adsImage)) {
				exit('3');
			}
			if (filter_var($adsRedirectUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (empty($adsTitle)) {
				exit('4');
			}
			if (!empty($adsImage) && !empty($adsTitle) && !empty($adsDescription) && !empty($adsRedirectUrl) && trim($adsTitle) != '' && trim($adsDescription) != '') {
				$insertNewAds = $iN->iN_UpdateAdvertisement($userID, $iN->iN_Secure($editingAdsID), $iN->iN_Secure($adsImage), $iN->iN_Secure($adsTitle), $iN->iN_Secure($adsDescription), $iN->iN_Secure($adsRedirectUrl));
				if ($insertNewAds) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
/*Delete Ads*/
	if ($type == 'deleteThisAds') {
		if (isset($_POST['id'])) {
			$adID = $iN->iN_Secure($_POST['id']);
			$deleteAds = $iN->iN_DeleteAdsFromData($userID, $iN->iN_Secure($adID));
			if ($deleteAds) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
/*Update Stripe Subscription Status*/
	if ($type == 'stripe_sub_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusSubOneTwo, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateStripeStatus = $iN->iN_UpdateStripeSubStatus($userID, $mod);
			if ($updateStripeStatus) {
				exit('200');
			}
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
/*Update CCBill Subscription Status*/
	if ($type == 'ccbill_sub_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateCCBillStatus = $iN->iN_UpdateCCBILLSubStatus($userID, $mod);
			if ($updateCCBillStatus) {
				exit('200');
			}
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
/*Update Subscription StripeDetails */
    if ($type == 'updateSubStripe') {
        if (isset($_POST['subSecretKey']) && isset($_POST['subPublicKey']) && isset($_POST['stripe_currency'])) {
            $stSubSecretKey = $iN->iN_Secure($_POST['subSecretKey']);
            $stSubPublicKey = $iN->iN_Secure($_POST['subPublicKey']);
            $stSubCurrency  = $iN->iN_Secure($_POST['stripe_currency']);
            $stWebhook      = isset($_POST['stripeWebhookSecret']) ? $iN->iN_Secure($_POST['stripeWebhookSecret']) : NULL;
            $updateStripeDetails = $iN->iN_UpdateSubStripeDetails(
                $userID,
                $iN->iN_Secure($stSubSecretKey),
                $iN->iN_Secure($stSubPublicKey),
                $iN->iN_Secure($stSubCurrency),
                $stWebhook
            );
            if ($updateStripeDetails) {
                exit('200');
            } else {
                echo iN_HelpSecure($LANG['noway_desc']);
            }
        }
    }
/*Update Giphy Api Key*/
	if ($type == 'updateGiphy') {
		if (isset($_POST['giphyKey']) && !empty($_POST['giphyKey'])) {
			$giphyKey = $iN->iN_Secure($_POST['giphyKey']);
			$updateGiphyKey = $iN->iN_UpdateGiphyAPIKey($userID, $iN->iN_Secure($giphyKey));
			if ($updateGiphyKey) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		} else {
			exit($LANG['enter_valid_giphy_key']);
		}
	}
	/*Update Ai Generator Api Data*/
	if ($type == 'updateAiCredit') {
		include_once __DIR__ . '/../../../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
		}
		$aiApiKeyRaw = trim((string)($_POST['apiKey'] ?? ''));
		$aiPerAmountRaw = trim((string)($_POST['perAmount'] ?? ''));
		if ($aiPerAmountRaw === '' || !is_numeric($aiPerAmountRaw)) {
			exit($LANG['invalid_ai_credit'] ?? $LANG['all_fields_must_be_filled']);
		}
		$aiPerAmount = (int)$aiPerAmountRaw;
		if ($aiPerAmount < 0) {
			$aiPerAmount = 0;
		}

		$aiModel = trim((string)($_POST['ai_model'] ?? $openAiModel));
		$aiFallbackModel = trim((string)($_POST['ai_fallback_model'] ?? $openAiFallbackModel));
		$aiTemperature = isset($_POST['ai_temperature']) ? (float)$_POST['ai_temperature'] : (float)$openAiTemperature;
		$aiMaxTokens = isset($_POST['ai_max_tokens']) ? (int)$_POST['ai_max_tokens'] : (int)$openAiMaxTokens;
		$aiPromptMaxLength = isset($_POST['ai_prompt_max_length']) ? (int)$_POST['ai_prompt_max_length'] : (int)$openAiPromptMaxLength;
		$aiRateLimitMinute = isset($_POST['ai_rate_limit_minute']) ? (int)$_POST['ai_rate_limit_minute'] : (int)$openAiRateLimitPerMinute;
		$aiRateLimitHour = isset($_POST['ai_rate_limit_hour']) ? (int)$_POST['ai_rate_limit_hour'] : (int)$openAiRateLimitPerHour;
		$aiRateLimitDay = isset($_POST['ai_rate_limit_day']) ? (int)$_POST['ai_rate_limit_day'] : (int)$openAiRateLimitPerDay;
		$aiForbiddenTerms = trim(strip_tags((string)($_POST['ai_forbidden_terms'] ?? $openAiForbiddenTerms)));

		if ($aiTemperature < 0 || $aiTemperature > 2) {
			exit($LANG['invalid_ai_temperature'] ?? $LANG['all_fields_must_be_filled']);
		}
		if ($aiMaxTokens < 1) {
			exit($LANG['invalid_ai_max_tokens'] ?? $LANG['all_fields_must_be_filled']);
		}
		if ($aiPromptMaxLength < 50) {
			exit($LANG['invalid_ai_prompt_length'] ?? $LANG['all_fields_must_be_filled']);
		}
		if ($aiRateLimitMinute < 0 || $aiRateLimitHour < 0 || $aiRateLimitDay < 0) {
			exit($LANG['invalid_ai_rate_limits'] ?? $LANG['all_fields_must_be_filled']);
		}

		$aiApiKey = '';
		if ($aiApiKeyRaw !== '' && !preg_match('/\\*{3,}/', $aiApiKeyRaw)) {
			$aiApiKey = $iN->iN_Secure($aiApiKeyRaw);
		} else if (empty($opanAiKey)) {
			exit($LANG['enter_valid_openai_key'] ?? $LANG['all_fields_must_be_filled']);
		}

		$updatedApiInfo = $iN->iN_UpdateAiAPIData(
			$userID,
			$aiApiKey,
			$aiPerAmount,
			[
				'model' => $aiModel,
				'fallback_model' => $aiFallbackModel,
				'temperature' => $aiTemperature,
				'max_tokens' => $aiMaxTokens,
				'prompt_max_length' => $aiPromptMaxLength,
				'rate_limit_minute' => $aiRateLimitMinute,
				'rate_limit_hour' => $aiRateLimitHour,
				'rate_limit_day' => $aiRateLimitDay,
				'forbidden_terms' => $aiForbiddenTerms,
			]
		);
		if ($updatedApiInfo) {
			exit('200');
		}
		echo iN_HelpSecure($LANG['noway_desc']);
	}
	if ($type == 'testAiHealthCheck') {
		header('Content-Type: application/json; charset=utf-8');
		include_once __DIR__ . '/../../../includes/csrf.php';
		if (!csrf_validate_from_request()) {
			echo json_encode(['status' => 'error', 'message' => $LANG['invalid_csrf_token'] ?? 'invalid_csrf_token']);
			exit();
		}
		if ($iN->iN_CheckIsAdmin($userID) != 1) {
			echo json_encode(['status' => 'error', 'message' => $LANG['noway_desc']]);
			exit();
		}
		$apiKeyRaw = trim((string)($_POST['apiKey'] ?? ''));
		$apiKey = $apiKeyRaw !== '' && !preg_match('/\\*{3,}/', $apiKeyRaw) ? $apiKeyRaw : (string)$opanAiKey;
		if ($apiKey === '') {
			echo json_encode(['status' => 'error', 'message' => $LANG['enter_valid_openai_key'] ?? 'missing_api_key']);
			exit();
		}
		$model = trim((string)($_POST['ai_model'] ?? $openAiModel));
		if ($model === '') {
			$model = (string)$openAiModel;
		}
		$result = iN_admin_openai_health_check($apiKey, $model);
		if (!$result['ok'] && !empty($openAiFallbackModel) && $openAiFallbackModel !== $model) {
			$result = iN_admin_openai_health_check($apiKey, (string)$openAiFallbackModel);
		}
		if ($result['ok']) {
			echo json_encode(['status' => 'ok', 'message' => $LANG['ai_health_check_ok'] ?? 'OK']);
			exit();
		}
		$errMessage = $result['message'] ?? 'OpenAI request failed.';
		$iN->iN_UpdateAiLastError($errMessage);
		error_log('[AI] health_check_error code=' . ($result['code'] ?? 'unknown') . ' model=' . $model);
		echo json_encode([
			'status' => 'error',
			'message' => $LANG['ai_health_check_failed'] ?? $errMessage,
		]);
		exit();
	}
	/*Email Settings*/
	if ($type == 'updateLiveSettings') {
		$liveStatus = $iN->iN_Secure($_POST['s3Status']);
		$freeLiveLimit = $iN->iN_Secure($_POST['post_show_limit']);
		$agora_AppID = $iN->iN_Secure($_POST['appID']);
		$agora_Certificate = $iN->iN_Secure($_POST['appCertificate']);
		$agora_CustomerID = $iN->iN_Secure($_POST['appCustomerID']);
		$liveMinimumFee = $iN->iN_Secure($_POST['liveMinPrice']);
		$freeLiveStreamingStatus = $iN->iN_Secure($_POST['sPlStatus']);
		$paidLiveStreamingStatus = $iN->iN_Secure($_POST['sflStatus']);
		$rtProvider = isset($_POST['rt_provider']) ? strtolower($iN->iN_Secure($_POST['rt_provider'])) : 'agora';
		$livekitAPIKey = isset($_POST['livekit_api_key']) ? $iN->iN_Secure($_POST['livekit_api_key']) : '';
		$livekitAPISecret = isset($_POST['livekit_api_secret']) ? $iN->iN_Secure($_POST['livekit_api_secret']) : '';
		$livekitWSUrl = isset($_POST['livekit_ws_url']) ? $iN->iN_Secure($_POST['livekit_ws_url']) : '';
			$callProvider = isset($_POST['call_provider']) ? strtolower($iN->iN_Secure($_POST['call_provider'])) : 'agora';
			$isometrikAPIKey = isset($_POST['isometrik_api_key']) ? $iN->iN_Secure($_POST['isometrik_api_key']) : '';
			$isometrikAPISecret = isset($_POST['isometrik_api_secret']) ? $iN->iN_Secure($_POST['isometrik_api_secret']) : '';
			$isometrikProjectId = isset($_POST['isometrik_project_id']) ? $iN->iN_Secure($_POST['isometrik_project_id']) : '';
			$isometrikWSUrl = isset($_POST['isometrik_ws_url']) ? $iN->iN_Secure($_POST['isometrik_ws_url']) : '';
			$livePollStatus = isset($_POST['livePollStatus']) ? $iN->iN_Secure($_POST['livePollStatus']) : '1';
			$liveGiftStatus = isset($_POST['liveGiftStatus']) ? $iN->iN_Secure($_POST['liveGiftStatus']) : '1';
			$liveQAStatus = isset($_POST['liveQAStatus']) ? $iN->iN_Secure($_POST['liveQAStatus']) : '1';
			$liveChatStatus = isset($_POST['liveChatStatus']) ? $iN->iN_Secure($_POST['liveChatStatus']) : '1';
			$livePollStatus = $livePollStatus === '1' ? '1' : '0';
			$liveGiftStatus = $liveGiftStatus === '1' ? '1' : '0';
			$liveQAStatus = $liveQAStatus === '1' ? '1' : '0';
			$liveChatStatus = $liveChatStatus === '1' ? '1' : '0';
			$livekitConfigComplete = !empty($livekitAPIKey) && !empty($livekitAPISecret) && !empty($livekitWSUrl);
		if (!in_array($rtProvider, ['agora', 'livekit'], true)) {
			$rtProvider = 'agora';
		}
		if ($rtProvider === 'livekit' && !$livekitConfigComplete) {
			$rtProvider = 'agora';
		}
		if (!in_array($callProvider, ['agora', 'livekit', 'isometrik'], true)) {
			$callProvider = 'agora';
		}
		if ($callProvider === 'isometrik' && (empty($isometrikAPIKey) || empty($isometrikAPISecret) || empty($isometrikProjectId) || empty($isometrikWSUrl))) {
			$callProvider = 'agora';
		}
		if ($callProvider === 'livekit' && !$livekitConfigComplete) {
			$callProvider = 'agora';
		}
		if ($liveStatus == '1') {
			if (empty($freeLiveLimit)) {
				exit($LANG['all_information_need_filled']);
			}
			if ($rtProvider === 'agora' && (empty($agora_AppID) || empty($agora_Certificate) || empty($agora_CustomerID))) {
				exit($LANG['all_information_need_filled']);
			}
			if ($rtProvider === 'livekit' && !$livekitConfigComplete) {
				exit($LANG['all_information_need_filled']);
			}
		}
		$updateLiveSettings = $iN->iN_UpdateAgoraLiveStreamingSettings(
			$userID,
			$iN->iN_Secure($freeLiveStreamingStatus),
			$iN->iN_Secure($paidLiveStreamingStatus),
			$iN->iN_Secure($liveStatus),
			$iN->iN_Secure($freeLiveLimit),
			$iN->iN_Secure($agora_AppID),
			$iN->iN_Secure($agora_Certificate),
			$iN->iN_Secure($agora_CustomerID),
			$iN->iN_Secure($liveMinimumFee),
			$iN->iN_Secure($rtProvider),
			$iN->iN_Secure($livekitAPIKey),
			$iN->iN_Secure($livekitAPISecret),
			$iN->iN_Secure($livekitWSUrl),
				$iN->iN_Secure($callProvider),
				$iN->iN_Secure($isometrikAPIKey),
				$iN->iN_Secure($isometrikAPISecret),
				$iN->iN_Secure($isometrikProjectId),
				$iN->iN_Secure($isometrikWSUrl),
				$iN->iN_Secure($livePollStatus),
				$iN->iN_Secure($liveGiftStatus),
				$iN->iN_Secure($liveQAStatus),
				$iN->iN_Secure($liveChatStatus)
			);
		if ($updateLiveSettings) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
	/*Update Page*/
	if ($type == 'updateMainPage') {
		if (isset($_POST['tm']) && !empty($_POST['tm'])) {
			$theme = $iN->iN_Secure($_POST['tm']);
			$updateTheme = $iN->iN_UpdateTheme($userID, $iN->iN_Secure($theme));
			if ($updateTheme) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	if($type == 'wall'){
		//$iN->iN_Sen($mycd, $mycdStatus,$base_url);
		echo $iN->iN_Sen($mycd, $mycdStatus,$base_url);
	}

	/*Update Landing Page Images*/
	if ($type == 'imageOne' || $type == 'imageTwo' || $type == 'imageThree' || $type == 'imageFour' || $type == 'imageFive' || $type == 'imageSix' || $type == 'imageSeventh' || $type == 'imageBg' || $type == 'imageFrnt') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = $iN->iN_Secure($_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			foreach ($fileReq as $iname => $value) {
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
						if (!file_exists($uploadIconLogo . 'landingImages/' . $d)) {
							$newFile = mkdir($uploadIconLogo . 'landingImages/' . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadIconLogo . 'landingImages/' . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'img/landingImages/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'img/landingImages/' . $d . '/' . $getFilename;
								if ($type == 'imageOne') {
									$iN->iN_UpdateFirstLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageTwo') {
									$iN->iN_UpdateSecondLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageThree') {
									$iN->iN_UpdateThirdLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageFour') {
									$iN->iN_UpdateFourthLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageFive') {
									$iN->iN_UpdateFifthLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageSix') {
									$iN->iN_UpdateSixthLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageSeventh') {
									$iN->iN_UpdateSeventhLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageBg') {
									$iN->iN_UpdateBgLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageFrnt') {
									$iN->iN_UpdateFrntLandingPageImage($userID, $pathFile);
								}
							}
							echo 'img/landingImages/' . $d . '/' . $getFilename;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Save New Question Answer*/
	if ($type == 'newQA') {
		if (isset($_POST['newq']) && isset($_POST['newqa'])) {
			$newQusetion = $iN->iN_Secure($_POST['newq']);
			$newQusetionAnswer = $iN->iN_Secure($_POST['newqa']);
			if (empty($newQusetion) || empty($newQusetionAnswer)) {
				exit('2');
			}
			$insertNewQuestionAnsser = $iN->iN_InsertNewQuestionAnswer($userID, $iN->iN_Secure($newQusetionAnswer), $iN->iN_Secure($newQusetion));
			if ($insertNewQuestionAnsser) {
				exit('200');
			} else {
				exit($LANG['save_failed']);
			}
		} else {
			exit('2');
		}
	}
	/*Save New Question Answer*/
	if ($type == 'edQA') {
		if (isset($_POST['newq']) && isset($_POST['newqa']) && isset($_POST['qid'])) {
			$newQusetion = $iN->iN_Secure($_POST['newq']);
			$newQusetionAnswer = $iN->iN_Secure($_POST['newqa']);
			$QAID = $iN->iN_Secure($_POST['qid']);
			if (empty($newQusetion) || empty($newQusetionAnswer) || empty($QAID)) {
				exit('2');
			}
			$updateQuestionAnswer = $iN->iN_UpdateLandingQA($userID, $iN->iN_Secure($newQusetionAnswer), $iN->iN_Secure($newQusetion), $iN->iN_Secure($QAID));
			if ($updateQuestionAnswer) {
				exit('200');
			} else {
				exit($LANG['save_failed']);
			}
		} else {
			exit('2');
		}
	}
	/*Update CCBILL Details */
	if ($type == 'updateSubStripeCCBILL') {
		if (isset($_POST['accountNumber']) && isset($_POST['subAccountNumber']) && isset($_POST['flexFormID']) && isset($_POST['saltKey']) && isset($_POST['ccbill_currency'])) {
			$accountNumber = $iN->iN_Secure($_POST['accountNumber']);
			$subAccountNumber = $iN->iN_Secure($_POST['subAccountNumber']);
			$flexFormID = $iN->iN_Secure($_POST['flexFormID']);
			$saltKey = $iN->iN_Secure($_POST['saltKey']);
			$ccbillCurrency = $iN->iN_Secure($_POST['ccbill_currency']);
            $cancelUsername = isset($_POST['cancelUsername']) ? $iN->iN_Secure($_POST['cancelUsername']) : '';
            $cancelPassword = isset($_POST['cancelPassword']) ? $iN->iN_Secure($_POST['cancelPassword']) : '';
			$updateCCBILLDetails = $iN->iN_UpdateSubCCBILLDetails(
                $userID,
                $iN->iN_Secure($accountNumber),
                $iN->iN_Secure($subAccountNumber),
                $iN->iN_Secure($flexFormID),
                $iN->iN_Secure($saltKey),
                $iN->iN_Secure($ccbillCurrency),
                $cancelUsername,
                $cancelPassword
            );
			if ($updateCCBILLDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update DigitalOceal Storage Details*/
	if ($type == 'DigitalOceanSettings') {
		$dOceanRegion = $iN->iN_Secure($_POST['oceanregion']);
		$dOgeanBucket = $iN->iN_Secure($_POST['docean_ducket']);
		$dOceanKey = $iN->iN_Secure($_POST['docean_key']);
		$dOceanSecretKey = $iN->iN_Secure($_POST['oceansecret_key']);
		$dOceanStatus = $iN->iN_Secure($_POST['s3Status']);
		$oceanPublicBaseRaw = isset($_POST['oceanPublicBase']) ? $_POST['oceanPublicBase'] : '';
		$oceanPublicBase = normalize_public_base_url($oceanPublicBaseRaw);
		$updateDigitalOceanSettings = $iN->iN_UpdateDigitalOceanDetails($userID, $iN->iN_Secure($dOceanRegion), $iN->iN_Secure($dOgeanBucket), $iN->iN_Secure($dOceanKey), $iN->iN_Secure($dOceanSecretKey), $iN->iN_Secure($dOceanStatus));
		if ($updateDigitalOceanSettings) {
			$overrides = storage_public_overrides_get();
			$overrides['ocean_public_base'] = $oceanPublicBase;
			if (!storage_public_overrides_write($overrides)) {
				exit('500');
			}
			$GLOBALS['digitalOceanPublicBase'] = $oceanPublicBase !== '' ? $oceanPublicBase : null;
			exit('200');
		} else {
			echo '404';
		}
	}
	/*ffmpeg status*/
	if ($type == 'ffmpegMode') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateffmpegSendStatus = $iN->iN_UpdateFFMPEGSendStatus($userID, $iN->iN_Secure($mod));
			if ($updateffmpegSendStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Post Creator status*/
	if ($type == 'postCreateStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdatePostCretaeStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Block Countries status*/
	if ($type == 'blockCountriesStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateBlockCountriesStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Auto Approve Post status*/
	if ($type == 'autoApprovePost') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateAutoApprovePostStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Affilate System status*/
	if ($type == 'affilateSystemStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateAffilateSystemStatus = $iN->iN_UpdateAffilateSystemStatus($userID, $iN->iN_Secure($mod));
			if ($updateAffilateSystemStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Question Answer status*/
	if ($type == 'questionAnswerStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['qid'])) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$qid = $iN->iN_Secure($_POST['qid']);
			$updatePostCreateStatus = $iN->iN_UpdateQuestionAnswerStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($qid));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Post Report status*/
	if ($type == 'rCheckStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['rid'])) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$rid = $iN->iN_Secure($_POST['rid']);
			$updatePostCheckedStatus = $iN->iN_UpdateReportedPostCheckedStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($rid));
			if ($updatePostCheckedStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Comment Report status*/
	if ($type == 'rcCheckStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['rid'])) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$rid = $iN->iN_Secure($_POST['rid']);
			$updatePostCheckedStatus = $iN->iN_UpdateReportedCommentCheckedStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($rid));
			if ($updatePostCheckedStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Message Report status*/
	if ($type == 'rmCheckStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['rid'])) {
			include_once __DIR__ . '/../../../includes/csrf.php';
			if (!csrf_validate_from_request()) {
				exit($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token');
			}
			$mod = $iN->iN_Secure($_POST['mod']);
			$rid = $iN->iN_Secure($_POST['rid']);
			$note = isset($_POST['note']) ? trim((string)$iN->iN_Secure($_POST['note'])) : '';
			$autoNote = $LANG['report_message_auto_note_checked'] ?? 'Your report has been reviewed by our moderation team.';
			$reportDetails = $iN->iN_GetMessageReportDetails($userID, $iN->iN_Secure($rid));
			$adminNoteToSave = $note;
			if ((string)$mod === '1' && $adminNoteToSave === '') {
				$adminNoteToSave = $autoNote;
			} elseif ((string)$mod !== '1' && $adminNoteToSave === '' && $reportDetails) {
				$adminNoteToSave = trim((string)($reportDetails['admin_note'] ?? ''));
			}
			$updatePostCheckedStatus = $iN->iN_UpdateReportedMessageCheckedStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($rid), $adminNoteToSave);
				if ($updatePostCheckedStatus) {
					if ((string)$mod === '1' && $reportDetails) {
						try {
							$iN->iN_InsertMessageReportUpdateNotification((int)$userID, $reportDetails, 'checked', $adminNoteToSave);
						} catch (Throwable $e) {
							// Keep status update stable even if notification persistence fails on older schemas.
						}
					}
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Comment Report*/
	if ($type == 'deleteCReport') {
		if (isset($_POST['id'])) {
			$postID = $iN->iN_Secure($_POST['id']);
			$deletePost = $iN->iN_DeleteCommentReport($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Stripe Status*/
	if ($type == 'coinpayment_status') {
        if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateStripeStatus = $iN->iN_UpdateCoinPaymentStatus($userID, $iN->iN_Secure($mod));
			if ($updateStripeStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update StripeDetails */
	if ($type == 'updateCoinPayment') {
		if (isset($_POST['cprivatekey']) && isset($_POST['cpublickey']) && isset($_POST['cmerchandid']) && isset($_POST['cipnsecret']) && isset($_POST['cdebugemail']) && isset($_POST['crpCurrency'])) {
			$cpPrivateKey = $iN->iN_Secure($_POST['cprivatekey']);
			$cpPublicKey = $iN->iN_Secure($_POST['cpublickey']);
			$cpMerchandID = $iN->iN_Secure($_POST['cmerchandid']);
			$cpIPNSecret = $iN->iN_Secure($_POST['cipnsecret']);
			$cpDebugEmail = $iN->iN_Secure($_POST['cdebugemail']);
			$cpCurrency = $iN->iN_Secure($_POST['crpCurrency']);
			$cpMode = isset($_POST['coinpayment_mode']) ? $iN->iN_Secure($_POST['coinpayment_mode']) : 'legacy';
			$cpClientId = isset($_POST['cclientid']) ? $iN->iN_Secure($_POST['cclientid']) : null;
			$cpClientSecret = isset($_POST['cclientsecret']) ? $iN->iN_Secure($_POST['cclientsecret']) : null;
			$cpWebhookSecret = isset($_POST['cwebhooksecret']) ? $iN->iN_Secure($_POST['cwebhooksecret']) : null;
			$cpApiBase = isset($_POST['capibase']) ? $iN->iN_Secure($_POST['capibase']) : null;
			$allowedCpModes = array('legacy', 'v2');
			if (!in_array($cpMode, $allowedCpModes, true)) {
				$cpMode = 'legacy';
			}
			$updateStripeDetails = $iN->iN_UpdateCoinPaymentDetails(
				$userID,
				$iN->iN_Secure($cpPrivateKey),
				$iN->iN_Secure($cpPublicKey),
				$iN->iN_Secure($cpMerchandID),
				$iN->iN_Secure($cpIPNSecret),
				$iN->iN_Secure($cpDebugEmail),
				$iN->iN_Secure($cpCurrency),
				$iN->iN_Secure($cpMode),
				$iN->iN_Secure($cpClientId),
				$iN->iN_Secure($cpClientSecret),
				$iN->iN_Secure($cpWebhookSecret),
				$iN->iN_Secure($cpApiBase)
			);
			if ($updateStripeDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
		if ($type == 'WatlogoFile') {
			//$availableFileExtensions
			if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
				$theValidateType = $iN->iN_Secure($_POST['c']);
				$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
				if (is_array($fileReq) && !empty($fileReq)) {
					$allowedImageExtensions = array('jpg', 'jpeg', 'png', 'webp');
					$allowedMimeByExtension = array(
						'jpg' => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'png' => 'image/png',
						'webp' => 'image/webp',
					);
					foreach ($fileReq as $iname => $value) {
						$name = stripslashes($_FILES['uploading']['name'][$iname]);
						$size = $_FILES['uploading']['size'][$iname];
						$ext = getExtension($name);
						$ext = strtolower($ext);
						if (!in_array($ext, $allowedImageExtensions, true)) {
							echo iN_HelpSecure($LANG['invalid_file_format']);
							continue;
						}

						if (convert_to_mb($size) >= $availableUploadFileSize) {
							echo iN_HelpSecure($LANG['file_is_too_large'] ?? $size);
							continue;
						}

						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						$tmp = $_FILES['uploading']['tmp_name'][$iname];

						if (!is_uploaded_file($tmp)) {
							echo iN_HelpSecure($LANG['upload_failed']);
							continue;
						}

						$detectedMime = '';
						if (function_exists('finfo_open')) {
							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							if ($finfo) {
								$detectedMime = (string)finfo_file($finfo, $tmp);
								finfo_close($finfo);
							}
						}
						$imgMeta = @getimagesize($tmp);
						if (is_array($imgMeta) && isset($imgMeta['mime'])) {
							$detectedMime = (string)$imgMeta['mime'];
						}
						$detectedMime = strtolower(trim($detectedMime));

						$expectedMime = isset($allowedMimeByExtension[$ext]) ? $allowedMimeByExtension[$ext] : '';
						if ($detectedMime === '' || $expectedMime === '' || $detectedMime !== $expectedMime) {
							echo iN_HelpSecure($LANG['invalid_file_format']);
							continue;
						}

						$d = date('Y-m-d');
						$primaryUploadDir = rtrim($uploadIconLogo, '/\\') . '/' . $d . '/';
						$fallbackUploadDir = rtrim($uploadFile, '/\\') . '/' . $d . '/';
						$targetUploadDir = $primaryUploadDir;
						$targetPublicPath = 'img/' . $d . '/';
						if (!is_dir($primaryUploadDir) && !@mkdir($primaryUploadDir, 0755, true) && !is_dir($primaryUploadDir)) {
							$targetUploadDir = $fallbackUploadDir;
							$targetPublicPath = 'uploads/files/' . $d . '/';
							if (!is_dir($fallbackUploadDir) && !@mkdir($fallbackUploadDir, 0755, true) && !is_dir($fallbackUploadDir)) {
								echo iN_HelpSecure($LANG['upload_failed']);
								continue;
							}
						}

						if (move_uploaded_file($tmp, $targetUploadDir . $getFilename)) {
							echo $targetPublicPath . $getFilename;
						} else {
							echo iN_HelpSecure($LANG['upload_failed']);
						}
					}
				}
			}
		}
	if ($type == 'GiftFile' || $type == 'GiftAnimationFile') {
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
                        $uploadGiftImage = $serverDocumentRoot . '/img/gifts/';
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadGiftImage . $d)) {
							$newFile = mkdir($uploadGiftImage . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadGiftImage . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'img/gifts/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'img/gifts/' . $d . '/' . $getFilename;
							}
							echo $pathFile;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	if ($type == 'newGiftCardForm') {
		if (isset($_POST['giftFile']) && isset($_POST['GiftAnimationFile']) && isset($_POST['gift_name']) && isset($_POST['giftPoint'])) {
			$giftImage = $iN->iN_Secure($_POST['giftFile']);
			$GiftAnimationFile = $iN->iN_Secure($_POST['GiftAnimationFile']);
			$giftName = $iN->iN_Secure($_POST['gift_name']);
			$giftPoint = $iN->iN_Secure($_POST['giftPoint']);
			$giftAmount = $giftPoint * $onePointEqual;
			if (empty($giftImage) || empty($GiftAnimationFile)) {
				exit('3');
			}
			if (empty($giftPoint)) {
				exit('3');
			}
			if (empty($giftName)) {
				exit('4');
			}
			if (!empty($giftImage) && !empty($giftName) && !empty($giftAmount) && !empty($giftPoint) && !empty($GiftAnimationFile)) {
				$insertNewAds = $iN->iN_InsertNewGiftCard($userID, $iN->iN_Secure($giftImage), $iN->iN_Secure($giftName), $iN->iN_Secure($giftPoint), $iN->iN_Secure($giftAmount), $iN->iN_Secure($GiftAnimationFile));
				if ($insertNewAds) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
	/*Edit Plan*/
	if ($type == 'editLivePlan') {
		if (isset($_POST['planKey']) && isset($_POST['planPoint']) && isset($_POST['pointAmount']) && isset($_POST['planid']) && isset($_POST['giftFile']) && isset($_POST['GiftAnimationFile'])) {
			$giftName = $iN->iN_Secure($_POST['planKey']);
			$giftPoint = $iN->iN_Secure($_POST['planPoint']);
			$giftAmount = $iN->iN_Secure($_POST['pointAmount']);
			$giftID = $iN->iN_Secure($_POST['planid']);
			$giftAvatar = $iN->iN_Secure($_POST['giftFile']);
			$giftAnimationFile = $iN->iN_Secure($_POST['GiftAnimationFile']);
			$removeAllSpaceFromKey = preg_replace('/\s+/', '', $giftName);
			if ($giftPoint < $minimumPointLimit || ctype_space($giftPoint) || empty($giftPoint)) {
				exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
			}
			if ($giftAmount > $maximumPointAmountLimit || ctype_space($giftAmount) || empty($giftAmount)) {
				exit(preg_replace('/{.*?}/', $maximumPointAmountLimit, $LANG['plan_point_amount_warning']));
			}

				$updateLivePlan = $iN->iN_UpdateLivePlanFromID($userID, $iN->iN_Secure($giftName),$giftAvatar,$giftAnimationFile, $iN->iN_Secure($giftPoint), $iN->iN_Secure($giftAmount), $iN->iN_Secure($giftID));
				if ($updateLivePlan) {
					exit('200');
				} else {
					exit($LANG['noway_desc']);
				}
		}
	}
	/*Change Plan Status*/
	if ($type == 'liveplanStatus') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$planID = $iN->iN_Secure($_POST['id']);
			$updatePlanStatus = $iN->iN_UpdateLivePlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
			if ($updatePlanStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Plan Status*/
	if ($type == 'frameplanStatus') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$planID = $iN->iN_Secure($_POST['id']);
			$updatePlanStatus = $iN->iN_UpdateFramePlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
			if ($updatePlanStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Post*/
	if ($type == 'deleteThisLivePlan') {
		if (isset($_POST['id'])) {
			$planID = $iN->iN_Secure($_POST['id']);
			$deletePlan = $iN->iN_DeleteLivePlanFromData($userID, $iN->iN_Secure($planID));
			if ($deletePlan) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Frame Plan*/
	if ($type == 'deleteThisFramePlan') {
		if (isset($_POST['id'])) {
			$planID = $iN->iN_Secure($_POST['id']);
			$deletePlan = $iN->iN_DeleteFramePlanFromData($userID, $iN->iN_Secure($planID));
			if ($deletePlan) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Weekly Subscription Status*/
	if ($type == 'weeklySubStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateWeeklySubStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Weekly Subscription Status*/
	if ($type == 'monthlySubStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateMonthlySubStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Weekly Subscription Status*/
	if ($type == 'yearlySubStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateYearlySubStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change WaterMark Image Status*/
	if ($type == 'watermarkStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateWatermarkStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Watermark Text Status*/
	if ($type == 'lwatermarkStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateLinkWatermarkStatus = $iN->iN_UpdateLinkWatermarkStatus($userID, $iN->iN_Secure($mod));
			if ($updateLinkWatermarkStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Watermark Text Status*/
	if ($type == 'fullnamestatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updateShowFullNameStatus = $iN->iN_UpdateShowFullNameStatus($userID, $iN->iN_Secure($mod));
			if ($updateShowFullNameStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Age Confirm Status*/
	if ($type == 'ageConfirmStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$dbValue = $mod === 'yes' ? '1' : '0';
			$updateAgeConfirmStatus = $iN->iN_UpdateAgeConfirmStatus($userID, $iN->iN_Secure($dbValue));
			if ($updateAgeConfirmStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Agency Module Status*/
	if ($type == 'agencyModuleStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updated = $iN->iN_UpdateAgencyModuleStatus($userID, $iN->iN_Secure($mod));
			if ($updated) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Agency Profile About Status*/
	if ($type == 'agencyProfileAboutStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updated = $iN->iN_UpdateAgencyProfileAboutStatus($userID, $iN->iN_Secure($mod));
			if ($updated) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Agency Profile Services Status*/
	if ($type == 'agencyProfileServicesStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updated = $iN->iN_UpdateAgencyProfileServicesStatus($userID, $iN->iN_Secure($mod));
			if ($updated) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Agency Profile Logo Status*/
	if ($type == 'agencyProfileLogoStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updated = $iN->iN_UpdateAgencyProfileLogoStatus($userID, $iN->iN_Secure($mod));
			if ($updated) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Agency Profile Cover Status*/
	if ($type == 'agencyProfileCoverStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updated = $iN->iN_UpdateAgencyProfileCoverStatus($userID, $iN->iN_Secure($mod));
			if ($updated) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Agency Profile Social Status*/
	if ($type == 'agencyProfileSocialStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updated = $iN->iN_UpdateAgencyProfileSocialStatus($userID, $iN->iN_Secure($mod));
			if ($updated) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Agency Boost Settings*/
	if ($type == 'agencyBoostSettings') {
		if ($iN->iN_CheckIsAdmin($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		$priceRaw = isset($_POST['agency_boost_price']) ? trim((string) $_POST['agency_boost_price']) : '';
		$pointPriceRaw = isset($_POST['agency_boost_point_price']) ? trim((string) $_POST['agency_boost_point_price']) : '';
		$daysRaw = isset($_POST['agency_boost_default_days']) ? trim((string) $_POST['agency_boost_default_days']) : '';

		$priceRaw = preg_replace('/[^0-9,.\-]/', '', $priceRaw);
		$hasComma = strpos($priceRaw, ',') !== false;
		$hasDot = strpos($priceRaw, '.') !== false;
		if ($hasComma && $hasDot) {
			$lastComma = strrpos($priceRaw, ',');
			$lastDot = strrpos($priceRaw, '.');
			$decimalSeparator = $lastComma > $lastDot ? ',' : '.';
			$thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
			$priceRaw = str_replace($thousandSeparator, '', $priceRaw);
			if ($decimalSeparator === ',') {
				$priceRaw = str_replace(',', '.', $priceRaw);
			}
		} elseif ($hasComma) {
			$priceRaw = str_replace(',', '.', $priceRaw);
		}

		if ($priceRaw === '' || !is_numeric($priceRaw)) {
			$logDir = __DIR__ . '/../../../includes/logs';
			if (!is_dir($logDir)) {
				@mkdir($logDir, 0755, true);
			}
			$logPath = $logDir . '/agency_boost_settings.log';
			$logContext = [
				'timestamp' => date('c'),
				'user_id' => $userID,
				'reason' => 'invalid_price',
				'price_raw' => $priceRaw,
				'point_price_raw' => $pointPriceRaw,
				'days_raw' => $daysRaw
			];
			error_log(json_encode($logContext) . PHP_EOL, 3, $logPath);
			exit('404');
		}
		if ($pointPriceRaw === '' || !preg_match('/^[0-9]+$/', $pointPriceRaw)) {
			$logDir = __DIR__ . '/../../../includes/logs';
			if (!is_dir($logDir)) {
				@mkdir($logDir, 0755, true);
			}
			$logPath = $logDir . '/agency_boost_settings.log';
			$logContext = [
				'timestamp' => date('c'),
				'user_id' => $userID,
				'reason' => 'invalid_point_price',
				'price_raw' => $priceRaw,
				'point_price_raw' => $pointPriceRaw,
				'days_raw' => $daysRaw
			];
			error_log(json_encode($logContext) . PHP_EOL, 3, $logPath);
			exit('404');
		}
		if ($daysRaw === '' || !preg_match('/^[0-9]+$/', $daysRaw)) {
			$logDir = __DIR__ . '/../../../includes/logs';
			if (!is_dir($logDir)) {
				@mkdir($logDir, 0755, true);
			}
			$logPath = $logDir . '/agency_boost_settings.log';
			$logContext = [
				'timestamp' => date('c'),
				'user_id' => $userID,
				'reason' => 'invalid_days',
				'price_raw' => $priceRaw,
				'point_price_raw' => $pointPriceRaw,
				'days_raw' => $daysRaw
			];
			error_log(json_encode($logContext) . PHP_EOL, 3, $logPath);
			exit('404');
		}

		$price = (float) $priceRaw;
		if ($price < 0) {
			$price = 0.0;
		}
		$pointPrice = (int) $pointPriceRaw;
		if ($pointPrice < 0) {
			$pointPrice = 0;
		}
		$days = (int) $daysRaw;
		if ($days < 1) {
			$days = 1;
		}
		if ($days > 365) {
			$days = 365;
		}

		$updated = $iN->iN_SetSetting('agency_boost_price', $price)
			&& $iN->iN_SetSetting('agency_boost_point_price', $pointPrice)
			&& $iN->iN_SetSetting('agency_boost_default_days', $days);
		if ($updated) {
			exit('200');
		} else {
			$logDir = __DIR__ . '/../../../includes/logs';
			if (!is_dir($logDir)) {
				@mkdir($logDir, 0755, true);
			}
			$columnChecks = [];
			try {
				foreach (['agency_boost_price', 'agency_boost_point_price', 'agency_boost_default_days'] as $columnName) {
					$exists = DB::col(
						"SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'i_configurations' AND COLUMN_NAME = ?",
						[$columnName]
					);
					$columnChecks[$columnName] = $exists ? true : false;
				}
			} catch (Throwable $e) {
				$columnChecks['error'] = $e->getMessage();
			}
			$logPath = $logDir . '/agency_boost_settings.log';
			$logContext = [
				'timestamp' => date('c'),
				'user_id' => $userID,
				'reason' => 'update_failed',
				'price' => $price,
				'point_price' => $pointPrice,
				'days' => $days,
				'columns' => $columnChecks
			];
			error_log(json_encode($logContext) . PHP_EOL, 3, $logPath);
			exit('404');
		}
	}
	/*Change Affilate Status*/
	if ($type == 'affilateStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateAffiliateStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Site Business Informations*/
	if ($type == 'updateAffilate') {
		$minimumPointTransferAmount = $iN->iN_Secure($_POST['minpointtransfer']);
		$affilateEarnAmount = $iN->iN_Secure($_POST['affilateamount']);

		if (empty($minimumPointTransferAmount) || empty($affilateEarnAmount)) {
			exit('1');
		}
		$updateAffilateInfos = $iN->iN_UpdateAffilateInfos($userID, $iN->iN_Secure($minimumPointTransferAmount), $iN->iN_Secure($affilateEarnAmount));
		if ($updateAffilateInfos) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Point Earning Informations*/
	if($type == 'epdSettings'){
	   $maxPointinaDay = isset($_POST['max_point_amount']) ? trim($_POST['max_point_amount']) : null;
	   $maxPointinaDay = str_replace(',', '.', (string)$maxPointinaDay);
	   $maxPointinaDay = is_numeric($maxPointinaDay) ? $maxPointinaDay : null;

       $epdRegisterStatus = (isset($_POST['registerSystemStatus']) && $_POST['registerSystemStatus'] === 'yes') ? 'yes' : 'no';
	   $epdCommentStatus = (isset($_POST['commentSystemStatus']) && $_POST['commentSystemStatus'] === 'yes') ? 'yes' : 'no';
	   $epdNewPostStatus = (isset($_POST['new_postSystemStatus']) && $_POST['new_postSystemStatus'] === 'yes') ? 'yes' : 'no';
	   $epdCommetLikeStatus = (isset($_POST['comment_likeSystemStatus']) && $_POST['comment_likeSystemStatus'] === 'yes') ? 'yes' : 'no';
	   $epdPostLikeStatus = (isset($_POST['post_likeSystemStatus']) && $_POST['post_likeSystemStatus'] === 'yes') ? 'yes' : 'no';

	   $epdRegisterAmount = isset($_POST['register_amount']) ? str_replace(',', '.', trim((string)$_POST['register_amount'])) : null;
	   $epdCommendAmount = isset($_POST['comment_amount']) ? str_replace(',', '.', trim((string)$_POST['comment_amount'])) : null;
	   $epdCommentLikeAmount = isset($_POST['comment_like_amount']) ? str_replace(',', '.', trim((string)$_POST['comment_like_amount'])) : null;
	   $epdNewPostAmount = isset($_POST['new_post_amount']) ? str_replace(',', '.', trim((string)$_POST['new_post_amount'])) : null;
	   $epdPostLikeAmount = isset($_POST['post_like_amount']) ? str_replace(',', '.', trim((string)$_POST['post_like_amount'])) : null;

	   $numericFields = [
            $maxPointinaDay,
            $epdRegisterAmount,
            $epdCommendAmount,
            $epdCommentLikeAmount,
            $epdNewPostAmount,
            $epdPostLikeAmount
       ];
	   foreach ($numericFields as $value) {
            if ($value === null || !is_numeric($value)) {
                exit('1');
            }
       }

        $epdLogDir = __DIR__ . '/../../../includes/logs';
        if (!is_dir($epdLogDir)) {
            @mkdir($epdLogDir, 0755, true);
        }
        $epdLogPath = $epdLogDir . '/epd_settings.log';
        $logContext = [
            'timestamp' => date('c'),
            'user_id' => $userID,
            'max_point_amount' => $maxPointinaDay,
            'register_status' => $epdRegisterStatus,
            'comment_status' => $epdCommentStatus,
            'new_post_status' => $epdNewPostStatus,
            'comment_like_status' => $epdCommetLikeStatus,
            'post_like_status' => $epdPostLikeStatus,
            'register_amount' => $epdRegisterAmount,
            'comment_amount' => $epdCommendAmount,
            'comment_like_amount' => $epdCommentLikeAmount,
            'new_post_amount' => $epdNewPostAmount,
            'post_like_amount' => $epdPostLikeAmount,
        ];
	   $epdSave = $iN->iN_EPDUpdate(
            $userID,
            $iN->iN_Secure($maxPointinaDay),
            $iN->iN_Secure($epdRegisterStatus),
            $iN->iN_Secure($epdCommentStatus),
            $iN->iN_Secure($epdNewPostStatus),
            $iN->iN_Secure($epdCommetLikeStatus),
            $iN->iN_Secure($epdPostLikeStatus),
            $iN->iN_Secure($epdRegisterAmount),
            $iN->iN_Secure($epdCommendAmount),
            $iN->iN_Secure($epdCommentLikeAmount),
            $iN->iN_Secure($epdNewPostAmount),
            $iN->iN_Secure($epdPostLikeAmount)
        );
        $logContext['result'] = $epdSave;
        @file_put_contents($epdLogPath, json_encode($logContext, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
	   if($epdSave === true){
          exit('200');
	   }else{
		echo $epdSave;
	   }
	}
	/*Fake User Generator*/
	if ($type == 'fake_generaator') {
		if (isset($_POST['n']) && isset($_POST['p'])) {
			$fakeUserNumber = $iN->iN_Secure($_POST['n']);
			$fakeUserPasswords = $iN->iN_Secure($_POST['p']);
			require "../../../includes/fake-users/vendor/autoload.php";
			$faker = Faker\Factory::create();
			$count_users = $fakeUserNumber;
			$password = $fakeUserPasswords;

			for ($i = 0; $i < $count_users; $i++) {
				$genders = array("male", "female");
				$random_keys = array_rand($genders, 1);
				$gender = array_rand(array("male", "female"), 1);
				$gender = $genders[$random_keys];
				$random_countries = array_rand($COUNTRIES);
				$randomProfileCategories = array_rand($PROFILE_CATEGORIES);
				$fakeUserEmail = $faker->userName . '_' . rand(111, 999) . "@yahoo.com";
				$fakeUserUsername = $faker->userName . '_' . rand(111, 999);
				$fakeUserPassword = sha1(md5(trim($password)));
				$fakeUserGender = $gender;
				$fakeUserRegisterTime = time();
				$fakeUserLastSeen = time();
				$fakeUserFullName = $faker->firstName($gender) . ' ' . $faker->lastName;
				$fakeuserBithYear = $faker->year($max = 'now');
				$fakeUserBirthMonth = $faker->month($max = 'now');
				$fakeUserBirthDay = ltrim($faker->dayOfMonth($max = 'now'), '0');
				$fakeUserCountry = $faker->countryCode;
				$fakeUserLatitude = $faker->latitude($min = -90, $max = 90);
				$fakeUserLongitude = $faker->longitude($min = -180, $max = 180);
				$fakerBithYearMonthDay = $fakeuserBithYear.'-'.$fakeUserBirthMonth.'-'.$fakeUserBirthDay;
				$GenerateFakeUser = $iN->iN_GenerateFakeUsers($userID, $fakeUserEmail, $fakeUserUsername, $fakeUserFullName, $fakeUserGender, $fakeUserPassword, $fakerBithYearMonthDay, $fakeUserRegisterTime, $fakeUserLatitude, $fakeUserLongitude,$random_countries, $randomProfileCategories);
			}
			if ($GenerateFakeUser) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}else{
			echo iN_HelpSecure($LANG['please_fill_all_requirements']);
		}
	}
	/*Change Affilate Status*/
	if ($type == 'pointSystemStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$UserCanEarnPointStatus = $iN->iN_UpdateUserCanEarnPointStatus($userID, $iN->iN_Secure($mod));
			if ($UserCanEarnPointStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Affilate Status*/
	if ($type == 'becomecreatortypestatus') {
		if (in_array($_POST['mod'], $beACreatorArray)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$UserCanEarnPointStatus = $iN->iN_UpdateBecomeACreatorTypeStatus($userID, $iN->iN_Secure($mod));
			if ($UserCanEarnPointStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Story From Database*/
	if($type == 'deleteStorie'){
		if(isset($_POST['id'])){
		   $storieID = $iN->iN_Secure($_POST['id']);
		   $checkStorieIDExist = $iN->iN_CheckStorieIDExistForAdmin($userID, $storieID);
		   if($checkStorieIDExist){
			   $sData = $iN->iN_GetUploadedStoriesDataForAdmin($storieID);
			   $uploadedFileID = $sData['s_id'];
			   $uploadedFilePath = $sData['uploaded_file_path'];
			   $uploadedTumbnailFilePath = $sData['upload_tumbnail_file_path'];
			   $uploadedFilePathX = $sData['uploaded_x_file_path'];
			   if($uploadedFileID){
					$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
					if ($useRemote && function_exists('storage_delete')) {
						@storage_delete($uploadedFilePath);
						@storage_delete($uploadedFilePathX);
						@storage_delete($uploadedTumbnailFilePath);
					} else {
						@unlink('../../../' . ltrim((string)$uploadedFilePath, '/'));
						@unlink('../../../' . ltrim((string)$uploadedFilePathX, '/'));
						@unlink('../../../' . ltrim((string)$uploadedTumbnailFilePath, '/'));
					}
					$deleted = DB::exec("DELETE FROM i_user_stories WHERE s_id = ?", [(int)$uploadedFileID]);
					exit($deleted ? '200' : '404');
			   }
		   }
		}
	 }
	 if ($type == 'stBgImage') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = $iN->iN_Secure($_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			if (is_array($fileReq) && !empty($fileReq)) {
				foreach ($fileReq as $iname => $value) {
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
							if (preg_match('/image\/*/', $mimeType)) {
								$fileTypeIs = 'Image';
							}
							if (!file_exists($uploadFile . $d)) {
								$newFile = mkdir($uploadFile . $d, 0755);
							}
							if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
								/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
								if ($fileTypeIs == 'Image') {
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$InsertNewBg = $iN->iN_InsertNewStoryBg($userID, $pathFile);
									if($InsertNewBg){
										exit('200');
									} else{
										exit('404');
									}
								}
							}else{
								exit('Check your file permission');
							}
						} else {
							echo iN_HelpSecure($size);
						}
					}
				}
			}
		}
	}
	/*Update Sticker Status*/
	if ($type == 'upStoryBg') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$bgID = $iN->iN_Secure($_POST['id']);
			$updateStoryBgStatus = $iN->iN_UpdateStoryBgStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($bgID));
			if ($updateStoryBgStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete User*/
	if ($type == 'deleteStoryBg') {
		if (isset($_POST['id'])) {
			$deleteStickerID = $iN->iN_Secure($_POST['id']);
			$deleteSTicker = $iN->iN_DeleteStoryBg($userID, $iN->iN_Secure($deleteStickerID));
			if ($deleteSTicker) {
				exit('200');
			} else {
				exit($LANG['storybg_id_not_available']);
			}
		}
	}
	if ($type == 'storyAudioFile') {
		if ($iN->iN_CheckIsAdmin($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : null;
			$title = isset($_POST['audio_title']) ? $iN->iN_Secure($_POST['audio_title']) : '';
			$artist = isset($_POST['audio_artist']) ? $iN->iN_Secure($_POST['audio_artist']) : '';
			$storyAudioUploadModeLocal = isset($storyAudioUploadMode) ? (string)$storyAudioUploadMode : 'mp3_only';
			if (!in_array($storyAudioUploadModeLocal, ['mp3_only', 'ffmpeg_mp3'], true)) {
				$storyAudioUploadModeLocal = 'mp3_only';
			}
			$audioExtensions = $storyAudioUploadModeLocal === 'ffmpeg_mp3'
				? ['mp3', 'wav', 'm4a', 'ogg', 'oga', 'mp4']
				: ['mp3'];
			$invalidFormatMessage = $storyAudioUploadModeLocal === 'ffmpeg_mp3'
				? ($LANG['story_audio_upload_invalid_convert'] ?? $LANG['invalid_file_format'])
				: ($LANG['story_audio_upload_invalid_mp3_only'] ?? $LANG['invalid_file_format']);
			$convertFailedMessage = $LANG['story_audio_convert_failed'] ?? $LANG['noway_desc'];
			$uploadError = '';
			$ffmpegAvailable = false;
			$ffmpegBin = '';
			if ($storyAudioUploadModeLocal === 'ffmpeg_mp3' && (string)$ffmpegStatus === '1' && function_exists('shell_exec')) {
				$ffmpegBin = trim((string)($ffmpegPath ?? ''));
				if ($ffmpegBin === '') {
					$ffmpegBin = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null || which ffmpeg 2>/dev/null'));
				}
				$ffmpegAvailable = $ffmpegBin !== '';
			}
			if (is_array($fileReq) && !empty($fileReq)) {
				foreach ($fileReq as $iname => $value) {
					$name = stripslashes($_FILES['uploading']['name'][$iname]);
					$size = $_FILES['uploading']['size'][$iname];
					$ext = strtolower(getExtension($name));
					if (!in_array($ext, $audioExtensions, true)) {
						$uploadError = $invalidFormatMessage;
						continue;
					}
					if (function_exists('convert_to_mb') && convert_to_mb($size) >= $availableUploadFileSize) {
						$uploadError = $LANG['file_is_too_large'] ?? $LANG['noway_desc'];
						continue;
					}
					$microtime = microtime();
					$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
					$baseName = "story_audio_" . $removeMicrotime . '_' . $userID;
					$tmp = $_FILES['uploading']['tmp_name'][$iname];
					$d = date('Y-m-d');
					$audioUploadRoot = rtrim($uploadRoot, '/') . '/story_audios/';
					if (!file_exists($audioUploadRoot . $d)) {
						@mkdir($audioUploadRoot . $d, 0755, true);
					}
					$sourcePath = $audioUploadRoot . $d . '/' . $baseName . '.' . $ext;
					if (!move_uploaded_file($tmp, $sourcePath)) {
						$uploadError = $LANG['noway_desc'];
						continue;
					}
					$finalExtension = $storyAudioUploadModeLocal === 'ffmpeg_mp3' ? 'mp3' : $ext;
					$relativePath = 'uploads/story_audios/' . $d . '/' . $baseName . '.' . $finalExtension;

					if ($storyAudioUploadModeLocal === 'ffmpeg_mp3' && $ext !== 'mp3') {
						if (!$ffmpegAvailable) {
							@unlink($sourcePath);
							$uploadError = $LANG['ffmpeg_not_configured'] ?? $LANG['noway_desc'];
							continue;
						}
						require_once __DIR__ . '/../../../includes/convertToMp3Format.php';
						$convertedPath = convertToMp3Format($ffmpegBin, $sourcePath, $audioUploadRoot . $d, $baseName);
						if ($convertedPath === null) {
							@unlink($sourcePath);
							$uploadError = $convertFailedMessage;
							continue;
						}
						if ($sourcePath !== $convertedPath && is_file($sourcePath)) {
							@unlink($sourcePath);
						}
					}

					if (function_exists('storage_publish_many')) {
						storage_publish_many([$relativePath], true, true);
					}
					$cleanTitle = trim((string)$title);
					if ($cleanTitle === '') {
						$cleanTitle = pathinfo($name, PATHINFO_FILENAME);
					}
					$insertNewAudio = $iN->iN_InsertNewStoryAudio($userID, $cleanTitle, trim((string)$artist), $relativePath, 0);
					if ($insertNewAudio) {
						exit('200');
					}
				}
			}
		}
		if ($uploadError !== '') {
			exit($uploadError);
		}
		exit('404');
	}
	if ($type == 'editStoryAudio') {
		if ($iN->iN_CheckIsAdmin($userID) != 1) {
			exit($LANG['noway_desc']);
		}
		if (!isset($_POST['id'])) {
			exit('404');
		}
		$audioID = (int)$iN->iN_Secure($_POST['id']);
		if ($iN->iN_CheckStoryAudioIdExist($audioID) != 1) {
			exit($LANG['story_audio_not_available'] ?? $LANG['noway_desc']);
		}
		$title = isset($_POST['audio_title']) ? trim((string)$iN->iN_Secure($_POST['audio_title'])) : '';
		$artist = isset($_POST['audio_artist']) ? trim((string)$iN->iN_Secure($_POST['audio_artist'])) : '';
		if ($title === '') {
			exit('1');
		}
		$storyAudioUploadModeLocal = isset($storyAudioUploadMode) ? (string)$storyAudioUploadMode : 'mp3_only';
		if (!in_array($storyAudioUploadModeLocal, ['mp3_only', 'ffmpeg_mp3'], true)) {
			$storyAudioUploadModeLocal = 'mp3_only';
		}
		$audioExtensions = $storyAudioUploadModeLocal === 'ffmpeg_mp3'
			? ['mp3', 'wav', 'm4a', 'ogg', 'oga', 'mp4']
			: ['mp3'];
		$ffmpegAvailable = false;
		$ffmpegBin = '';
		if ($storyAudioUploadModeLocal === 'ffmpeg_mp3' && (string)$ffmpegStatus === '1' && function_exists('shell_exec')) {
			$ffmpegBin = trim((string)($ffmpegPath ?? ''));
			if ($ffmpegBin === '') {
				$ffmpegBin = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null || which ffmpeg 2>/dev/null'));
			}
			$ffmpegAvailable = $ffmpegBin !== '';
		}
		$audioData = $iN->iN_GetStoryAudioById($audioID);
		$newAudioPath = null;
		$deleteOldAudio = false;
		$fileName = $_FILES['uploading']['name'][0] ?? '';
		if ($fileName !== '') {
			$size = $_FILES['uploading']['size'][0] ?? 0;
			$ext = strtolower(getExtension($fileName));
			if (!in_array($ext, $audioExtensions, true)) {
				exit('2');
			}
			if (function_exists('convert_to_mb') && convert_to_mb($size) >= $availableUploadFileSize) {
				exit('3');
			}
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$baseName = "story_audio_" . $removeMicrotime . '_' . $userID;
			$tmp = $_FILES['uploading']['tmp_name'][0] ?? '';
			$d = date('Y-m-d');
			$audioUploadRoot = rtrim($uploadRoot, '/') . '/story_audios/';
			if (!file_exists($audioUploadRoot . $d)) {
				@mkdir($audioUploadRoot . $d, 0755, true);
			}
			$sourcePath = $audioUploadRoot . $d . '/' . $baseName . '.' . $ext;
			if (!move_uploaded_file($tmp, $sourcePath)) {
				exit('404');
			}
			$finalExtension = $storyAudioUploadModeLocal === 'ffmpeg_mp3' ? 'mp3' : $ext;
			$relativePath = 'uploads/story_audios/' . $d . '/' . $baseName . '.' . $finalExtension;
			if ($storyAudioUploadModeLocal === 'ffmpeg_mp3' && $ext !== 'mp3') {
				if (!$ffmpegAvailable) {
					@unlink($sourcePath);
					exit($LANG['ffmpeg_not_configured'] ?? $LANG['noway_desc']);
				}
				require_once __DIR__ . '/../../../includes/convertToMp3Format.php';
				$convertedPath = convertToMp3Format($ffmpegBin, $sourcePath, $audioUploadRoot . $d, $baseName);
				if ($convertedPath === null) {
					@unlink($sourcePath);
					exit($LANG['story_audio_convert_failed'] ?? $LANG['noway_desc']);
				}
				if ($sourcePath !== $convertedPath && is_file($sourcePath)) {
					@unlink($sourcePath);
				}
			}
			if (function_exists('storage_publish_many')) {
				storage_publish_many([$relativePath], true, true);
			}
			$newAudioPath = $relativePath;
			$deleteOldAudio = true;
		}
		if ($fileName !== '' && $newAudioPath === null) {
			exit('404');
		}
		$updated = $iN->iN_UpdateStoryAudio($userID, $audioID, $title, $artist, $newAudioPath);
		if ($updated) {
			if ($deleteOldAudio) {
				$oldAudioPath = $audioData['audio_url'] ?? '';
				if ($oldAudioPath !== '') {
					$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
					if ($useRemote && function_exists('storage_delete')) {
						@storage_delete($oldAudioPath);
					} else {
						@unlink('../../../' . ltrim((string)$oldAudioPath, '/'));
					}
				}
			}
			exit('200');
		}
		exit($LANG['noway_desc']);
	}
	if ($type == 'upStoryAudio') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$audioID = $iN->iN_Secure($_POST['id']);
			$updateStoryAudioStatus = $iN->iN_UpdateStoryAudioStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($audioID));
			if ($updateStoryAudioStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	if ($type == 'deleteStoryAudio') {
		if (isset($_POST['id'])) {
			$deleteAudioID = $iN->iN_Secure($_POST['id']);
			$audioData = $iN->iN_GetStoryAudioById($deleteAudioID);
			$deleteAudio = $iN->iN_DeleteStoryAudio($userID, $iN->iN_Secure($deleteAudioID));
			if ($deleteAudio) {
				$audioPath = $audioData['audio_url'] ?? '';
				if ($audioPath !== '') {
					$useRemote = function_exists('storage_is_remote') ? storage_is_remote() : false;
					if ($useRemote && function_exists('storage_delete')) {
						@storage_delete($audioPath);
					} else {
						@unlink('../../../' . ltrim((string)$audioPath, '/'));
					}
				}
				exit('200');
			} else {
				exit($LANG['story_audio_not_available'] ?? $LANG['noway_desc']);
			}
		}
	}
	/*Shop Feature status*/
	if ($type == 'shopFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '1';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Scratch Feature status*/
	if ($type == 'shopScratchStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '2';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Book a Zoom Feature status*/
	if ($type == 'shopBookaZoomStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '3';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Digital Download Feature status*/
	if ($type == 'shopDigitalDownloadStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '4';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Live Event Ticket Feature status*/
	if ($type == 'shopLiveEventTicketStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '5';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Art Commission Feature status*/
	if ($type == 'shopArtCommissionStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '6';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Join Instagram Close Friends Feature status*/
	if ($type == 'shopInstagramGloseFriendsStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '7';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Who can create a product*/
	if ($type == 'whoCanCretaProduct') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '8';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Who can create a product*/
	if ($type == 'storyFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '1';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Story Image Feature Status*/
	if ($type == 'storyImageFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '2';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Video Call FEature Status*/
	if ($type == 'videoCallFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '2';
			$updatePostCreateStatus = $iN->iN_UpdateVideoCallFeatureStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Story Text Feature Status*/
	if ($type == 'storyTextFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '3';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Who Can Create Status*/
	if ($type == 'whoCanCretaStory') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$ID = '4';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Add New Sticker Url*/
	if ($type == 'createNewAnnouncement') {
		if(isset($_POST['announcementText']) && isset($_POST['announcementStatus']) && isset($_POST['announcementType']) && in_array($_POST['announcementStatus'], $yesNo) && in_array($_POST['announcementType'], $announcementTypes)){

		    $announcementText = $iN->iN_Secure($_POST['announcementText']);
			$annoucementStatus = $iN->iN_Secure($_POST['announcementStatus']);
			$announcementType = $iN->iN_Secure($_POST['announcementType']);
			if(preg_replace('/\s+/', '',$announcementText) == ''){
                exit('2');
			}
			$insertAnnouncement = $iN->iN_InsertAnnouncement($userID, $iN->iN_Secure($announcementText), $iN->iN_Secure($annoucementStatus), $iN->iN_Secure($announcementType));
			if($insertAnnouncement){
                $announcementId = (int)$insertAnnouncement;
                if ($announcementId > 0 && $annoucementStatus === 'yes') {
                    try {
                        iN_admin_send_announcement_web_push(
                            $iN,
                            $announcementId,
                            (string)$announcementText,
                            (string)$announcementType,
                            (string)$base_url,
                            $LANG
                        );
                    } catch (Throwable $e) {
                        // Keep admin save flow stable even if push delivery fails.
                    }
                }
                exit('200');
			}else{
				exit('404');
			}
		}
	}
	/*Update Sticker Status*/
	if ($type == 'upAnnon') {
        if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $yesNo, true)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$anID = $iN->iN_Secure($_POST['id']);
			$updateAnnouncementStatus = $iN->iN_UpdateAnnouncementStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($anID));
			if ($updateAnnouncementStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete Announcement*/
	if ($type == 'deleteAnnouncement') {
		if (isset($_POST['id'])) {
			$annunceID = $iN->iN_Secure($_POST['id']);
			$deleteAnnounce = $iN->iN_DeleteAnnouncement($userID, $iN->iN_Secure($annunceID));
			if ($deleteAnnounce) {
				exit('200');
			} else {
				exit($LANG['announcement_not_founded']);
			}
		}
	}
	/*Edited Sticker URL*/
	if ($type == 'announcementEdit') {
		if (isset($_POST['announcementText']) && isset($_POST['announcementStatus']) && isset($_POST['announcementType']) && in_array($_POST['announcementStatus'], $yesNo) && in_array($_POST['announcementType'], $announcementTypes) && isset($_POST['aid'])) {
			$announcementText = $iN->iN_Secure($_POST['announcementText']);
			$annoucementStatus = $iN->iN_Secure($_POST['announcementStatus']);
			$announcementType = $iN->iN_Secure($_POST['announcementType']);

			$aID = $iN->iN_Secure($_POST['aid']);

			if(preg_replace('/\s+/', '',$announcementText) == ''){
                exit('2');
			}
			$insertAnnouncement = $iN->iN_UpdateAnnouncement($userID, $iN->iN_Secure($aID),$iN->iN_Secure($announcementText), $iN->iN_Secure($annoucementStatus), $iN->iN_Secure($announcementType));
			if($insertAnnouncement){
                exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Yes Delete Product From Data*/
if ($type == 'deleteProductt') {
	if (isset($_POST['id']) && !empty($_POST['id'])) {
		$productID = $iN->iN_Secure($_POST['id']);
		$checkProductIDExist = $iN->iN_CheckProductIDExistFromURL($productID);
		if ($checkProductIDExist) {
			$okDelete = $iN->iN_DeleteProductAdmin($userID, $productID);
			if ($okDelete) {
				exit('200');
			} else {
				echo '404';
			}
		} else {
			exit($LANG['payment_request_no_longer_available']);
		}
	}
}
/*Add New Sticker Url*/
if ($type == 'newSocialSite') {
	if(isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){

		$newSocialSite = $iN->iN_Secure($_POST['social_site']);
		$newSocialSiteKey = $iN->iN_Secure($_POST['socail_key']);
        $newSocialSiteSVGCodeRaw = isset($_POST['socialsvgcode']) ? (string)$_POST['socialsvgcode'] : '';
        $newSocialSiteSVGCode = $iN->iN_SanitizeSvgIcon($newSocialSiteSVGCodeRaw);
		$newSocialSiteStatus = $iN->iN_Secure($_POST['socialsitestatus']);
		if (stripos($newSocialSiteSVGCodeRaw, '<svg') === false) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCodeRaw) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_InsertNewSocialSite($userID, $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Update Sticker Status*/
if ($type == 'upSocial') {
if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $yesNo, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$sID = $iN->iN_Secure($_POST['id']);
		$updateSocialSiteStatus = $iN->iN_UpdateSocialSiteStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($sID));
		if ($updateSocialSiteStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
if ($type == 'upwSocial') {
if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $yesNo, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$sID = $iN->iN_Secure($_POST['id']);
		$updateSocialSiteStatus = $iN->iN_UpdateWebsiteSocialSiteStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($sID));
		if ($updateSocialSiteStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Add New Sticker Url*/
if ($type == 'editnewSocialSite') {
	if(isset($_POST['ssid']) && isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){
		$socialSiteID = $iN->iN_Secure($_POST['ssid']);
		$newSocialSite = $iN->iN_Secure($_POST['social_site']);
		$newSocialSiteKey = $iN->iN_Secure($_POST['socail_key']);
        $newSocialSiteSVGCodeRaw = isset($_POST['socialsvgcode']) ? (string)$_POST['socialsvgcode'] : '';
		$newSocialSiteSVGCode = $iN->iN_SanitizeSvgIcon($newSocialSiteSVGCodeRaw);
		$newSocialSiteStatus = $iN->iN_Secure($_POST['socialsitestatus']);
		if (stripos($newSocialSiteSVGCodeRaw, '<svg') === false) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCodeRaw) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_UpdateSocialSite($userID, $iN->iN_Secure($socialSiteID), $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Delete Question*/
if ($type == 'deleteSocialSit') {
	if (isset($_POST['id'])) {
		$sSite = $iN->iN_Secure($_POST['id']);
		$deletesSite = $iN->iN_DeleteSocialSite($userID, $iN->iN_Secure($sSite));
		if ($deletesSite) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Who Can Create Status*/
if ($type == 'whoCanCreateVideoCall') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updatePostCreateStatus = $iN->iN_UpdateWhoCanCreateVideoCallFeatureStatus($userID, $iN->iN_Secure($mod));
		if ($updatePostCreateStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Who Can Create Status*/
if ($type == 'isVideoCallFree') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updatePostCreateStatus = $iN->iN_UpdateIsVideoCallPaidStatus($userID, $iN->iN_Secure($mod));
		if ($updatePostCreateStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}

/*Search Result Aupdate*/
if ($type == 'searchResultUpdate') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updatePostCreateStatus = $iN->iN_UpdateSarchResultStatus($userID, $iN->iN_Secure($mod));
		if ($updatePostCreateStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Add New Sticker Url*/
if ($type == 'editnewWebsiteSocialSite') {
	if(isset($_POST['ssid']) && isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){
		$socialSiteID = $iN->iN_Secure($_POST['ssid']);
		$newSocialSite = $iN->iN_Secure($_POST['social_site']);
		$newSocialSiteKey = $iN->iN_Secure($_POST['socail_key']);
        $newSocialSiteSVGCodeRaw = isset($_POST['socialsvgcode']) ? (string)$_POST['socialsvgcode'] : '';
		$newSocialSiteSVGCode = $iN->iN_SanitizeSvgIcon($newSocialSiteSVGCodeRaw);
		$newSocialSiteStatus = $iN->iN_Secure($_POST['socialsitestatus']);
		if (stripos($newSocialSiteSVGCodeRaw, '<svg') === false) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCodeRaw) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_UpdateWebsiteSocialSite($userID, $iN->iN_Secure($socialSiteID), $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Delete Question*/
if ($type == 'deleteSocialSitW') {
	if (isset($_POST['id'])) {
		$sSite = $iN->iN_Secure($_POST['id']);
		$deletesSite = $iN->iN_DeleteWebsiteSocialSite($userID, $iN->iN_Secure($sSite));
		if ($deletesSite) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Search Result Aupdate*/
if ($type == 'autoAcceptPremiumPostStatus') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateAutoUpdatePremiumPostStatus = $iN->iN_UpdateAutoAcceptPremiumPostStatus($userID, $iN->iN_Secure($mod));
		if ($updateAutoUpdatePremiumPostStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Mercadopago Business And Sandbox Email Address*/
if ($type == 'updateMercadoPago') {
	if (isset($_POST['mercadopagotesttoken']) && isset($_POST['mercadopagolivetoken']) && isset($_POST['mercadopago_currency'])) {
		$testTokenID = $iN->iN_Secure($_POST['mercadopagotesttoken']);
		$liveTokenID = $iN->iN_Secure($_POST['mercadopagolivetoken']);
		$mercadoPago_Currency = $iN->iN_Secure($_POST['mercadopago_currency']);
		$updateMercadoPagoDetails = $iN->iN_UpdateMercadoPagoDetails($userID, $iN->iN_Secure($testTokenID), $iN->iN_Secure($liveTokenID), $iN->iN_Secure($mercadoPago_Currency));
		if ($updateMercadoPagoDetails) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Moneroo configuration*/
if ($type == 'updateMoneroo') {
	if (
		isset($_POST['moneroo_test_project_id']) &&
		isset($_POST['moneroo_test_api_key']) &&
		isset($_POST['moneroo_live_project_id']) &&
		isset($_POST['moneroo_live_api_key']) &&
		isset($_POST['moneroo_currency'])
	) {
		$testProjectId = $iN->iN_Secure($_POST['moneroo_test_project_id']);
		$testApiKey = $iN->iN_Secure($_POST['moneroo_test_api_key']);
		$liveProjectId = $iN->iN_Secure($_POST['moneroo_live_project_id']);
		$liveApiKey = $iN->iN_Secure($_POST['moneroo_live_api_key']);
		$webhookSecret = isset($_POST['moneroo_webhook_secret']) ? $iN->iN_Secure($_POST['moneroo_webhook_secret']) : '';
		$currency = $iN->iN_Secure($_POST['moneroo_currency']);
		$updateMoneroo = $iN->iN_UpdateMonerooDetails($userID, $testProjectId, $testApiKey, $liveProjectId, $liveApiKey, $webhookSecret, $currency);
		if ($updateMoneroo) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update NowPayments configuration*/
if ($type == 'updateNowPayments') {
	if (
		isset($_POST['nowpayments_test_api_key']) &&
		isset($_POST['nowpayments_live_api_key']) &&
		isset($_POST['nowpayments_ipn_secret']) &&
		isset($_POST['nowpayments_currency'])
	) {
		$testApiKey = $iN->iN_Secure($_POST['nowpayments_test_api_key']);
		$liveApiKey = $iN->iN_Secure($_POST['nowpayments_live_api_key']);
		$ipnSecret = $iN->iN_Secure($_POST['nowpayments_ipn_secret']);
		$currency = $iN->iN_Secure($_POST['nowpayments_currency']);
		$updateNowPayments = $iN->iN_UpdateNowPaymentsDetails($userID, $testApiKey, $liveApiKey, $ipnSecret, $currency);
		if ($updateNowPayments) {
			exit('200');
		}
		echo iN_HelpSecure($LANG['noway_desc']);
	}
}
/*Update Paysafecard configuration*/
if ($type == 'updatePaysafecard') {
	if (isset($_POST['paysafecard_api_key']) && isset($_POST['paysafecard_currency'])) {
		$apiKey = $iN->iN_Secure($_POST['paysafecard_api_key']);
		$currency = $iN->iN_Secure($_POST['paysafecard_currency']);
		$updated = $iN->iN_UpdatePaysafecardDetails($userID, $apiKey, $currency);
		if ($updated) {
			exit('200');
		}
		echo iN_HelpSecure($LANG['noway_desc']);
	}
}
/*Update MercadoPago Mode Status*/
if ($type == 'mercadomode') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateMercadoPagoMode = $iN->iN_UpdateMercadoPagoMode($userID, $iN->iN_Secure($mod));
		if ($updateMercadoPagoMode) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Moneroo Mode Status*/
if ($type == 'moneroomode') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateMonerooMode = $iN->iN_UpdateMonerooMode($userID, $iN->iN_Secure($mod));
		if ($updateMonerooMode) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update NowPayments Mode Status*/
if ($type == 'nowpaymentsmode') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateNowPaymentsMode = $iN->iN_UpdateNowPaymentsMode($userID, $iN->iN_Secure($mod));
		if ($updateNowPaymentsMode) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Paysafecard Mode Status*/
if ($type == 'paysafecard_mode') {
	if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updatePaysafecardMode = $iN->iN_UpdatePaysafecardMode($userID, $mod);
		if ($updatePaysafecardMode) {
			exit('200');
		}
		echo iN_HelpSecure($LANG['noway_desc']);
	}
}
/*Update MercadoPago Status*/
if ($type == 'mercadopago_status') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateMercadopagoStatus = $iN->iN_UpdateMercadoPagoStatus($userID, $iN->iN_Secure($mod));
		if ($updateMercadopagoStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Moneroo Status*/
if ($type == 'moneroo_status') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateMonerooStatus = $iN->iN_UpdateMonerooStatus($userID, $iN->iN_Secure($mod));
		if ($updateMonerooStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Paysafecard Status*/
if ($type == 'paysafecard_status') {
	if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updatePaysafecardStatus = $iN->iN_UpdatePaysafecardStatus($userID, $mod);
		if ($updatePaysafecardStatus) {
			exit('200');
		}
		echo iN_HelpSecure($LANG['noway_desc']);
	}
}
/*Update NowPayments Status*/
if ($type == 'nowpayments_status') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateNowPaymentsStatus = $iN->iN_UpdateNowPaymentsStatus($userID, $iN->iN_Secure($mod));
		if ($updateNowPaymentsStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*drawTextMode status*/
if ($type == 'drawTextMode') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateffmpegSendStatus = $iN->iN_UpdateFFMPEGDrawTextStatus($userID, $iN->iN_Secure($mod));
		if ($updateffmpegSendStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Search Result Aupdate*/
if ($type == 'subCatMod') {
	if (in_array($_POST['mod'], $statusValue) && isset($_POST['sID']) && $_POST['sID'] !== '') {
		$mod = $iN->iN_Secure($_POST['mod']);
		$iD = $iN->iN_Secure($_POST['sID']);
		$updateSubCategoryStatus = $iN->iN_UpdateSubCategoryStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($iD));
		if ($updateSubCategoryStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}

/*Update Sub Kategory Key*/
if($type == 'upSubKey'){
   if(isset($_POST['skey']) && isset($_POST['sid']) && strlen(trim($_POST['skey'])) != 0){
		$newKey = $iN->iN_Secure($_POST['skey']);
		$id = $iN->iN_Secure($_POST['sid']);
		$updateSubCategoryKey = $iN->iN_UpdateSubCategoryKey($userID, $iN->iN_Secure($newKey), $iN->iN_Secure($id));
		if ($updateSubCategoryKey) {
			exit('200');
		} else {
			echo '404';
		}
   }
}

/*CREATE NEW SUB CATEGORY*/
if($type == 'addNewSubCat'){
   if(isset($_POST['nkey']) && isset($_POST['addTo']) && strlen(trim($_POST['nkey'])) != 0){
      $newSubCatKey = $iN->iN_Secure($_POST['nkey']);
	  $addToThisCategory = $iN->iN_Secure($_POST['addTo']);
	  $addNewCategoryKey = $iN->iN_CreateNewSubCategory($userID, $newSubCatKey, $addToThisCategory);
	  if($addNewCategoryKey){
        $subCategoryKey = $addNewCategoryKey['sc_key'];
		$scID = $addNewCategoryKey['sc_id'];
		$scStatus = $addNewCategoryKey['sc_status'];
		include("../sources/contents/newSubCategory.php");
	  }
   }
}
if($type == 'delSubCat'){
   if(isset($_POST['id']) && strlen(trim($_POST['id'])) != 0){
	   $subCID = $iN->iN_Secure($_POST['id']);
	   $deleteSubCategory = $iN->iN_DeleteSubCat($userID,$subCID);
	   if($deleteSubCategory){
		   exit('200');
	   }else{
		   exit('404');
	   }
   }
}

/*Update Category Status*/
if ($type == 'catModStatus') {
	if (in_array($_POST['mod'], $statusValue) && isset($_POST['Cid']) && $_POST['Cid'] !== '') {
		$mod = $iN->iN_Secure($_POST['mod']);
		$iD = $iN->iN_Secure($_POST['Cid']);
		$updateSubCategoryStatus = $iN->iN_UpdateCategoryStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($iD));
		if ($updateSubCategoryStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}

/*Update Sub Kategory Key*/
if($type == 'upCatKey'){
	if(isset($_POST['ckey']) && isset($_POST['cid'])){
		 $newKey = $iN->iN_Secure($_POST['ckey']);
		 $id = $iN->iN_Secure($_POST['cid']);
		 $updateCategoryKey = $iN->iN_UpdateCategoryKey($userID, $iN->iN_Secure($newKey), $iN->iN_Secure($id));
		 if ($updateCategoryKey) {
			 exit('200');
		 } else {
			 echo '404';
		 }
	}
}
if($type == 'delCatt'){
	if(isset($_POST['id']) && strlen(trim($_POST['id'])) != 0){
		$subCID = $iN->iN_Secure($_POST['id']);
		$deleteSubCategory = $iN->iN_DeleteCat($userID,$subCID);
		if($deleteSubCategory){
			exit('200');
		}else{
			exit('404');
		}
	}
}
if($type == 'cNewCatP'){
	if(isset($_POST['ky']) && strlen(trim($_POST['ky'])) != 0){
		$newCategoryKey = $iN->iN_Secure($_POST['ky']);
		$insertNewProfileCategory = $iN->iN_InsertNewProfileCategory($userID, $newCategoryKey);
		if ($insertNewProfileCategory) {
			exit('200');
		} else {
			exit($LANG['save_failed']);
		}

	}
}
/*Community Categories Update*/
if ($type === 'communityCategoriesUpdate') {
    $selected = isset($_POST['community_categories']) ? (array)$_POST['community_categories'] : [];
    $selectedKeys = [];
    foreach ($selected as $key) {
        $cleanKey = trim((string)$iN->iN_Secure($key));
        if ($cleanKey !== '') {
            $selectedKeys[] = $cleanKey;
        }
    }
    $existingRows = $iN->iN_GetCommunityCategories();
    $existingKeys = [];
    foreach ($existingRows as $row) {
        if (!empty($row['category_key'])) {
            $existingKeys[] = (string)$row['category_key'];
        }
    }
    if (!empty($existingKeys)) {
        DB::exec("UPDATE community_categories SET status = '0'");
        $filtered = array_values(array_intersect($existingKeys, $selectedKeys));
        if (!empty($filtered)) {
            $placeholders = implode(',', array_fill(0, count($filtered), '?'));
            DB::exec(
                "UPDATE community_categories SET status = '1' WHERE category_key IN ({$placeholders})",
                $filtered
            );
        }
    }
    exit('200');
}
/*Community Creation Settings*/
if ($type === 'communityCreationSettingsUpdate') {
    $policy = isset($_POST['community_creation_policy'])
        ? strtolower(trim((string)$iN->iN_Secure($_POST['community_creation_policy'])))
        : 'paid_only';
    if (!in_array($policy, ['paid_only', 'free_only', 'both'], true)) {
        $policy = 'paid_only';
    }
    $freeLimit = isset($_POST['community_free_member_limit'])
        ? (int)$iN->iN_Secure($_POST['community_free_member_limit'])
        : 10;
    if ($freeLimit < 1) {
        $freeLimit = 1;
    }
    $policyUpdated = $iN->iN_SetSetting('community_creation_policy', $policy);
    $limitUpdated = $iN->iN_SetSetting('community_free_member_limit', $freeLimit);
    exit(($policyUpdated || $limitUpdated) ? '200' : '404');
}
/*Community Admin Settings*/
if ($type === 'communityAdminSettingsUpdate') {
    if (!isset($_POST['community_id'])) {
        exit('404');
    }
    $communityID = (int)$iN->iN_Secure($_POST['community_id']);
    $postingEnabled = isset($_POST['posting_enabled']) ? (string)$iN->iN_Secure($_POST['posting_enabled']) : '1';
    $subscriptionRequired = isset($_POST['subscription_required']) ? (string)$iN->iN_Secure($_POST['subscription_required']) : '1';
    $postingEnabled = in_array($postingEnabled, ['0', '1'], true) ? $postingEnabled : '1';
    $subscriptionRequired = in_array($subscriptionRequired, ['0', '1'], true) ? $subscriptionRequired : '1';
    $updated = $iN->iN_UpdateCommunity($communityID, [
        'posting_enabled' => $postingEnabled,
        'subscription_required' => $subscriptionRequired
    ]);
    exit($updated ? '200' : '404');
}
/*Community Moderation Settings*/
if ($type === 'communityAdminModerationSettingsUpdate') {
    if (!isset($_POST['community_id'])) {
        exit('404');
    }
    $communityID = (int)$iN->iN_Secure($_POST['community_id']);
    $moderationEnabled = isset($_POST['moderation_enabled']) ? '1' : '0';
    $timeoutEnabled = isset($_POST['moderation_timeout_enabled']) ? '1' : '0';
    $rawOptions = isset($_POST['timeout_options']) ? (array)$_POST['timeout_options'] : [];
    $allowedOptions = ['1', '3', '7', '30', 'permanent'];
    $selected = [];
    foreach ($rawOptions as $option) {
        $option = trim((string)$iN->iN_Secure($option));
        if ($option !== '' && in_array($option, $allowedOptions, true)) {
            $selected[] = $option;
        }
    }
    $optionsValue = !empty($selected) ? implode(',', array_values(array_unique($selected))) : '';
    $updated = $iN->iN_UpdateCommunity($communityID, [
        'moderation_enabled' => $moderationEnabled,
        'moderation_timeout_enabled' => $timeoutEnabled,
        'moderation_timeout_options' => $optionsValue
    ]);
    exit($updated ? '200' : '404');
}
/*Community Moderator Add*/
if ($type === 'communityAdminModeratorAdd') {
    if (!isset($_POST['community_id'], $_POST['moderator_user'])) {
        exit('404');
    }
    $communityID = (int)$iN->iN_Secure($_POST['community_id']);
    $communityData = $iN->iN_GetCommunityById($communityID);
    if (!$communityData) {
        exit('404');
    }
    $moderatorInput = trim((string)$iN->iN_Secure($_POST['moderator_user']));
    $moderatorID = 0;
    if ($moderatorInput !== '' && ctype_digit($moderatorInput)) {
        $moderatorID = (int)$moderatorInput;
    }
    if ($moderatorID <= 0 && $moderatorInput !== '') {
        $userRow = $iN->iN_GetUserDetailsFromUsername($moderatorInput);
        $moderatorID = $userRow ? (int)$userRow['iuid'] : 0;
    }
    if ($moderatorID <= 0 || !$iN->iN_CheckUserExist($moderatorID)) {
        exit($LANG['community_moderator_user_not_found'] ?? 'User not found.');
    }
    if ((int)($communityData['owner_user_id'] ?? 0) === $moderatorID) {
        exit($LANG['community_moderator_owner_forbidden'] ?? 'Owner cannot be a moderator.');
    }
    $permissions = [
        'can_manage_members' => isset($_POST['perm_manage_members']) ? '1' : '0',
        'can_manage_posts' => isset($_POST['perm_manage_posts']) ? '1' : '0',
        'can_manage_comments' => isset($_POST['perm_manage_comments']) ? '1' : '0',
        'can_manage_reshare' => isset($_POST['perm_manage_reshare']) ? '1' : '0',
        'can_manage_view_timeout' => isset($_POST['perm_manage_view_timeout']) ? '1' : '0'
    ];
    $iN->iN_SetCommunityModerator($communityID, $moderatorID, $permissions, $userID);
    exit('200');
}
/*Community Moderator Update/Remove*/
if ($type === 'communityAdminModeratorUpdate') {
    if (!isset($_POST['community_id'], $_POST['moderator_user_id'])) {
        exit('404');
    }
    $communityID = (int)$iN->iN_Secure($_POST['community_id']);
    $moderatorID = (int)$iN->iN_Secure($_POST['moderator_user_id']);
    $communityData = $iN->iN_GetCommunityById($communityID);
    if (!$communityData || $moderatorID <= 0) {
        exit('404');
    }
    if ((int)($communityData['owner_user_id'] ?? 0) === $moderatorID) {
        exit($LANG['community_moderator_owner_forbidden'] ?? 'Owner cannot be a moderator.');
    }
    $action = isset($_POST['moderator_action']) ? $iN->iN_Secure($_POST['moderator_action']) : 'update';
    if ($action === 'remove') {
        $iN->iN_RemoveCommunityModerator($communityID, $moderatorID);
        exit('200');
    }
    $permissions = [
        'can_manage_members' => isset($_POST['perm_manage_members']) ? '1' : '0',
        'can_manage_posts' => isset($_POST['perm_manage_posts']) ? '1' : '0',
        'can_manage_comments' => isset($_POST['perm_manage_comments']) ? '1' : '0',
        'can_manage_reshare' => isset($_POST['perm_manage_reshare']) ? '1' : '0',
        'can_manage_view_timeout' => isset($_POST['perm_manage_view_timeout']) ? '1' : '0'
    ];
    $iN->iN_SetCommunityModerator($communityID, $moderatorID, $permissions, $userID);
    exit('200');
}
/*Community Admin Edit*/
if ($type === 'communityAdminEdit') {
    if (!isset($_POST['community_id'])) {
        exit('404');
    }
    $communityID = (int)$iN->iN_Secure($_POST['community_id']);
    $communityData = $iN->iN_GetCommunityById($communityID);
    if (!$communityData) {
        exit('404');
    }
    $title = isset($_POST['community_title']) ? $iN->iN_Secure($_POST['community_title'], 1, false) : '';
    $description = isset($_POST['community_description']) ? $iN->iN_Secure($_POST['community_description']) : '';
    if (mb_strlen($title, 'UTF-8') < 3 || mb_strlen($title, 'UTF-8') > 160) {
        exit($LANG['community_name_required'] ?? 'Invalid community name.');
    }
    if (!empty($description) && mb_strlen(strip_tags($description), 'UTF-8') > 2000) {
        exit($LANG['community_description_too_long'] ?? 'Description too long.');
    }
    $avatarImagePath = null;
    if (isset($_FILES['community_avatar']['name']) && $_FILES['community_avatar']['name'] !== '') {
        $avatarName = $_FILES['community_avatar']['name'];
        $avatarTmp = $_FILES['community_avatar']['tmp_name'];
        $avatarSize = isset($_FILES['community_avatar']['size']) ? (int)$_FILES['community_avatar']['size'] : 0;
        $ext = strtolower(getExtension($avatarName));
        $allowedAvatarExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowedAvatarExt, true)) {
            exit($LANG['invalid_file_format'] ?? 'Invalid file format.');
        }
        if (function_exists('convert_to_mb') && convert_to_mb($avatarSize) > $availableUploadFileSize) {
            exit($LANG['file_is_too_large'] ?? 'File too large.');
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
            exit($LANG['upload_failed'] ?? 'Upload failed.');
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
            exit($LANG['invalid_file_format'] ?? 'Invalid file format.');
        }
        if (function_exists('convert_to_mb') && convert_to_mb($coverSize) > $availableUploadFileSize) {
            exit($LANG['file_is_too_large'] ?? 'File too large.');
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
            exit($LANG['upload_failed'] ?? 'Upload failed.');
        }
        $coverImagePath = 'uploads/communities/' . $todayDir . '/' . $filename;
        if (function_exists('storage_publish_and_url')) {
            storage_publish_and_url($coverImagePath, [$coverImagePath], true);
        }
    }
    $updateData = [
        'title' => $title,
        'description' => $description !== '' ? $description : null
    ];
    if ($avatarImagePath !== null) {
        $updateData['avatar_image'] = $avatarImagePath;
    }
    if ($coverImagePath !== null) {
        $updateData['cover_image'] = $coverImagePath;
    }
    $updated = $iN->iN_UpdateCommunity($communityID, $updateData);
    exit($updated ? '200' : '404');
}
/*Add New Sticker Url*/
if ($type == 'newWebSocialSite') {
	if(isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){

		$newSocialSite = $iN->iN_Secure($_POST['social_site']);
		$newSocialSiteKey = $iN->iN_Secure($_POST['socail_key']);
        $newSocialSiteSVGCodeRaw = isset($_POST['socialsvgcode']) ? (string)$_POST['socialsvgcode'] : '';
		$newSocialSiteSVGCode = $iN->iN_SanitizeSvgIcon($newSocialSiteSVGCodeRaw);
		$newSocialSiteStatus = $iN->iN_Secure($_POST['socialsitestatus']);
		if (stripos($newSocialSiteSVGCodeRaw, '<svg') === false) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCodeRaw) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_InsertNewWebSocialSite($userID, $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Update Boost Status*/
if ($type == 'uPBoost') {
if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$bgID = $iN->iN_Secure($_POST['id']);
		$updateStoryBgStatus = $iN->iN_UpdateBoostPostStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($bgID));
		if ($updateStoryBgStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Yes Delete Product From Data*/
if ($type == 'deleteBoostedPost') {
	if (isset($_POST['id']) && !empty($_POST['id'])) {
		$productID = $iN->iN_Secure($_POST['id']);
		$checkProductIDExist = $iN->iN_CheckBoostExist($productID);
		if ($checkProductIDExist) {
			$okDelete = $iN->iN_DeleteBoostedPost($userID, $productID);
			if ($okDelete) {
				exit('200');
			} else {
				echo '404';
			}
		} else {
			exit($LANG['this_pos_no_longer_available']);
		}
	}
}
/*Change Plan Status*/
if ($type == 'planBoostStatus') {
if (isset($_POST['id'], $_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$planID = $iN->iN_Secure($_POST['id']);
		$updatePlanStatus = $iN->iN_UpdateBoostPlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
		if ($updatePlanStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Delete Post*/
if ($type == 'deleteThisBoostPlan') {
	if (isset($_POST['id'])) {
		$planID = $iN->iN_Secure($_POST['id']);
		$deletePlan = $iN->iN_DeleteBoostPlanFromData($userID, $iN->iN_Secure($planID));
		if ($deletePlan) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Edit Plan*/
if ($type == 'editBoostPlan') {
	if (isset($_POST['planKey']) && isset($_POST['planViewTime']) && isset($_POST['planAmount']) && isset($_POST['newsvgcode']) && isset($_POST['planid'])) {
		$planKey = $iN->iN_Secure($_POST['planKey']);
		$planViewTime = $iN->iN_Secure($_POST['planViewTime']);
		$planAmount = $iN->iN_Secure($_POST['planAmount']);
		$planID = $iN->iN_Secure($_POST['planid']);
		$planSVGIcon = $iN->iN_Secure($_POST['newsvgcode']);
		$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
		if (ctype_space($planViewTime) || empty($planViewTime)) {
			exit(iN_HelpSecure($LANG['please_fill_in_all_fields']));
		}
		if (ctype_space($planAmount) || empty($planAmount)) {
			exit(iN_HelpSecure($LANG['please_fill_in_all_fields']));
		}
		if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
			exit(iN_HelpSecure($LANG['plan_key_warning']));
		}
		if (ctype_space($planSVGIcon) || !isset($planSVGIcon) || empty($planSVGIcon)) {
			exit(iN_HelpSecure($LANG['mustwritesvgcode']));
		}
		if (empty($removeAllSpaceFromKey) || $removeAllSpaceFromKey == '' || empty($removeAllSpaceFromKey) || strlen($removeAllSpaceFromKey) == '0' || ctype_space($removeAllSpaceFromKey)) {
			exit('404');
		} else {
			$updatePlan = $iN->iN_UpdateBoostPlanFromID($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planViewTime), $iN->iN_Secure($planAmount), $planSVGIcon, $iN->iN_Secure($planID));
			if ($updatePlan) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		}
	}
}
/*Add New Point Plan*/
if ($type == 'newBoostPackageForm') {
	if (isset($_POST['planKey']) && isset($_POST['planViewTime']) && isset($_POST['planAmount']) && isset($_POST['newsvgcode'])) {
		$planKey = $iN->iN_Secure($_POST['planKey']);
		$planViewTime = $iN->iN_Secure($_POST['planViewTime']);
		$planAmount = $iN->iN_Secure($_POST['planAmount']);
		$planSVGIcon = $iN->iN_Secure($_POST['newsvgcode']);
		$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
		if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
			exit('4');
		}
		if (ctype_space($planViewTime)) {
			exit('1');
		}
		if (ctype_space($planAmount) || empty($planAmount)) {
			exit('3');
		}
		$updatePlan = $iN->iN_InsertNewBOOSTPlan($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planViewTime), $iN->iN_Secure($planAmount), $planSVGIcon);
		if ($updatePlan) {
			exit('200');
		} else {
			exit($LANG['noway_desc']);
		}
	} else {
		echo '5';
	}
}
/*Approve Bank Payment*/
if($type == 'approveBankPayment'){
    if(isset($_POST['payerid']) && isset($_POST['planID']) && isset($_POST['imID']) && isset($_POST['paymentID'])){
        $payerID = $iN->iN_Secure($_POST['payerid']);
        $imageID = $iN->iN_Secure($_POST['imID']);
        $planID = $iN->iN_Secure($_POST['planID']);
        $paymentIDD = $iN->iN_Secure($_POST['paymentID']);
		$insertApprove = $iN->iN_InsertApprove($userID, $payerID, $planID, $imageID,$paymentIDD);
		/****/
		if($insertApprove){
			$dataEmail = $iN->iN_GetUserDetails($payerID);
			$emailBody = iN_HelpSecure($LANG['bank_payment_accepted']);
			$sendEmail = isset($dataEmail['i_user_email']) ? $dataEmail['i_user_email'] : NULL;
			$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden; display:flex; display:-webkit-flex;";
            $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:15px; border-radius:3px;";
            $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
            $imgStyle = "width:100%; overflow:hidden;";
            $contentStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
            $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff !important;";
            $linkStyle = "text-decoration:none; color:#ffffff !important; font-weight:500; font-size:14px; position:relative;";
			if ($emailSendStatus == '1') {
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

                    <div style="' . $contentStyle . '">
                      ' . $emailBody . '
                    </div>

                    <div style="' . $buttonBoxStyle . '">
                      <a href="' . $base_url . '" style="' . $linkStyle . '">' . iN_HelpSecure($LANG['gotowebsite']) . '</a>
                    </div>

                  </div>
                </div>';
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $emailTitle;
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					echo '200';
					return true;
				}
			}
			exit('200');
		}
		/****/
	}
}
/*Decline Bank Payment*/
if($type == 'declineBankPayment'){
	if(isset($_POST['payerid']) && isset($_POST['planID']) && isset($_POST['imID']) && isset($_POST['paymentID'])){
        $payerID = $iN->iN_Secure($_POST['payerid']);
        $imageID = $iN->iN_Secure($_POST['imID']);
        $planID = $iN->iN_Secure($_POST['planID']);
        $paymentIDD = $iN->iN_Secure($_POST['paymentID']);
		$declineBankPayment = $iN->iN_DeclineBankPaymentRequest($userID, $payerID, $planID, $imageID,$paymentIDD);
	    /****/
		if($declineBankPayment){
			$dataEmail = $iN->iN_GetUserDetails($payerID);
			$emailBody = iN_HelpSecure($LANG['bank_payment_declined']);
			$sendEmail = isset($dataEmail['i_user_email']) ? $dataEmail['i_user_email'] : NULL;
			$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden; display:flex; display:-webkit-flex;";
            $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:15px; border-radius:3px;";
            $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
            $imgStyle = "width:100%; overflow:hidden;";
            $contentStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
            $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff !important;";
            $linkStyle = "text-decoration:none; color:#ffffff !important; font-weight:500; font-size:14px; position:relative;";

			if ($emailSendStatus == '1') {
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

                    <div style="' . $contentStyle . '">
                      ' . $emailBody . '
                    </div>

                    <div style="' . $buttonBoxStyle . '">
                      <a href="' . $base_url . '" style="' . $linkStyle . '">' . iN_HelpSecure($LANG['gotowebsite']) . '</a>
                    </div>

                  </div>
                </div>';
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $emailTitle;
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					echo '200';
					return true;
				}
			}
			exit('200');
		}
	}
}
/*Update Auto Detect Language Status*/
if ($type == 'detect_lang_status') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateDetectLanguageStatus = $iN->iN_UpdateDetectLanguageStatus($userID, $iN->iN_Secure($mod));
		if ($updateDetectLanguageStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Ai Generator Status*/
if ($type == 'ai_generator_status') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateAiGeneratorStatus = $iN->iN_UpdateAiGeneratorStatus($userID, $iN->iN_Secure($mod));
		if ($updateAiGeneratorStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Giphy Status*/
if ($type == 'giphy_status') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateGiphyStatus = $iN->iN_UpdateGiphyStatus($userID, $iN->iN_Secure($mod));
		if ($updateGiphyStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Email Send Mode Status*/
if ($type == 'send__email') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateEmailSendStatus = $iN->iN_UpdateEmailSendStatusForCPP($userID, $iN->iN_Secure($mod));
		if ($updateEmailSendStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update MercadoPago Status*/
if ($type == 'bankPaymentStatus') {
if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$updateBankPaymentStatus = $iN->iN_UpdateBankPaymentPagoStatus($userID, $iN->iN_Secure($mod));
		if ($updateBankPaymentStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Payout Method Statuses*/
$payoutMethodStatusColumns = array(
	'payout_payoneer_status' => 'payout_payoneer_status',
	'payout_zelle_status' => 'payout_zelle_status',
	'payout_western_union_status' => 'payout_western_union_status',
	'payout_bitcoin_status' => 'payout_bitcoin_status',
	'payout_mercadopago_status' => 'payout_mercadopago_status'
);
if (isset($payoutMethodStatusColumns[$type])) {
	if (isset($_POST['mod']) && in_array($_POST['mod'], $statusValue, true)) {
		$mod = $iN->iN_Secure($_POST['mod']);
		$column = $payoutMethodStatusColumns[$type];
		$updated = $iN->iN_UpdatePayoutMethodStatus($userID, $column, $mod);
		if ($updated) {
			exit('200');
		}
		echo iN_HelpSecure($LANG['noway_desc']);
	}
}
/*Update Bankpayment*/
if ($type == 'bankPaymentStatusa') {
	if (isset($_POST['bankpaymentpercentagefee']) && isset($_POST['bankpaymentfixedcharge']) && isset($_POST['bank_description'])) {
		$percentageFee = $iN->iN_Secure($_POST['bankpaymentpercentagefee']);
		$fixedCharge = $iN->iN_Secure($_POST['bankpaymentfixedcharge']);
		$bankDescription = $iN->iN_Secure($_POST['bank_description']);
		$updateBankPaymentDetails = $iN->iN_UpdateBankPaymentDetails($userID, $iN->iN_Secure($percentageFee), $iN->iN_Secure($fixedCharge), $bankDescription);
		if ($updateBankPaymentDetails) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
if ($type == 'frameFile') {
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
                        $uploadGiftImage = $serverDocumentRoot . '/img/frames/';
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadGiftImage . $d)) {
							$newFile = mkdir($uploadGiftImage . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadGiftImage . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'img/frames/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'img/frames/' . $d . '/' . $getFilename;
							}
							echo $pathFile;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
    /*Edit Frame Plan*/
	if ($type == 'editFramePlan') {
		if (isset($_POST['planPoint']) && isset($_POST['planid']) && isset($_POST['frameFile'])) {
			$framePrice = $iN->iN_Secure($_POST['planPoint']);
			$frameID = $iN->iN_Secure($_POST['planid']);
			$frameFile = $iN->iN_Secure($_POST['frameFile']);

			if ($framePrice < $minimumPointLimit || ctype_space($framePrice) || empty($framePrice)) {
				exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
			}
			if ($framePrice > $maximumPointAmountLimit || ctype_space($framePrice) || empty($framePrice)) {
				exit(preg_replace('/{.*?}/', $maximumPointAmountLimit, $LANG['plan_point_amount_warning']));
			}

		    $updateLivePlan = $iN->iN_UpdateFramePlanFromID($userID, $framePrice, $frameFile,$frameID);
				if ($updateLivePlan) {
					exit('200');
				} else {
					exit($LANG['noway_desc']);
				}
		}
	}
	/*Add new Frame Card*/
	if ($type == 'newFrameCardForm') {
		if (isset($_POST['frameFile']) && isset($_POST['framePoint'])) {
			$giftImage = $iN->iN_Secure($_POST['frameFile']);
			$giftPoint = $iN->iN_Secure($_POST['framePoint']);
			$giftAmount = $giftPoint * $onePointEqual;
			if (empty($giftImage)) {
				exit('3');
			}
			if (empty($giftPoint)) {
				exit('3');
			}

			if (!empty($giftImage) && !empty($giftPoint)) {
				$insertNewFrame = $iN->iN_InsertNewFrameCard($userID, $iN->iN_Secure($giftImage), $iN->iN_Secure($giftPoint));
				if ($insertNewFrame) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
	/*Save Color Change*/
	if($type == 'changeColor'){
	    if(isset($_POST['data']) && isset($_POST['clr'])){
	        $dataRow = $iN->iN_Secure($_POST['data']);
	        $dataColor = $iN->iN_Secure($_POST['clr']);
	        $dataColor = str_replace('#', '', $dataColor);
	        $checkDataRowExist = $iN->iN_CheckRowExist($dataRow);
	        if($checkDataRowExist){
	             $updateColor = $iN->iN_ChangeColor($userID, $dataRow, $dataColor);
	             if($updateColor){
	                 exit('200');
	             }else{
	                 exit('404');
	             }
	        }else{
	            exit('4042');
	        }
	    }
	}
	/*Set Default Color*/
	if($type == 'setDefaultColor'){
	    if(isset($_POST['data'])){
	        $dataRow = $iN->iN_Secure($_POST['data']);
	        $checkDataRowExist = $iN->iN_CheckRowExist($dataRow);
	        if($checkDataRowExist){
	             $updateColor = $iN->iN_UpdateDefaultColor($userID, $dataRow);
	             if($updateColor){
	                 exit('200');
	             }else{
	                 exit('404');
	             }
	        }else{
	            exit('404');
	        }
	    }
	}
	/*Change Subscription Mode*/
	if ($type == 'renewalsubs') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = $iN->iN_Secure($_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateSubscriptionType($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Search Result Aupdate*/
    if ($type == 'autoFollowAdmin') {
    	if (in_array($_POST['mod'], $yesNo)) {
    		$mod = $iN->iN_Secure($_POST['mod']);
    		$updatePostCreateStatus = $iN->iN_UpdateAutoFollowAdminStatus($userID, $iN->iN_Secure($mod));
    		if ($updatePostCreateStatus) {
    			exit('200');
    		} else {
    			echo '404';
    		}
    	}
    }
} else {
	echo $LANG['test_admin_account_limited'];
}
?>
