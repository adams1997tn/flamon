<?php
// Lightweight cron entrypoint for scheduled posts
$appRoot = dirname(__DIR__);
require_once $appRoot . '/includes/inc.php';
require_once $appRoot . '/includes/phpmailer/vendor/autoload.php';

function iN_safeMailSend(PHPMailer\PHPMailer\PHPMailer $mail, string $mode, string $context = 'mail'): bool {
    try {
        return $mail->send();
    } catch (Throwable $e) {
        if ($mode === 'smtp') {
            try {
                $mail->smtpClose();
                $mail->isMail();
                return $mail->send();
            } catch (Throwable $inner) {
                error_log('[MAIL] ' . $context . ' mail() fallback failure: ' . $inner->getMessage());
            }
        }
        error_log('[MAIL] ' . $context . ' send failure: ' . $e->getMessage());
        return false;
    }
}

$envToken = getenv('SCHEDULE_PUBLISHER_TOKEN') ?: '';
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
$now = time();

$processed = 0;
$failed = 0;
$dueItems = $iN->iN_GetDueScheduledQueue($limit);

if (!empty($dueItems)) {
    foreach ($dueItems as $item) {
        $queueId = isset($item['id']) ? (int)$item['id'] : 0;
        $postId = isset($item['post_id_fk']) ? (int)$item['post_id_fk'] : 0;
        if ($postId <= 0) { continue; }
        $now = time();
        try {
            DB::exec("UPDATE i_scheduled_queue SET status = 'processing', updated_at = ?, attempts = attempts + 1 WHERE id = ?", [$now, $queueId]);
            if (!$iN->iN_PublishScheduledPost($postId, $now)) {
                $failed++;
                $iN->iN_FailScheduledPost($postId, 'publish_failed', $queueId);
                continue;
            }
            $postData = $iN->iN_GetAllPostDetails($postId);
            if ($postData) {
                $ownerId = (int)($postData['post_owner_id'] ?? 0);
                $postText = $postData['post_text'] ?? '';
                $ownerUsername = $iN->iN_GetUserName($ownerId);
                if (!empty($postText)) {
                    $iN->iN_InsertMentionedUsersForPost($ownerId, $postText, $postId, $ownerUsername, $ownerId);
                }
                if ($ataNewPostPointAmount && $ataNewPostPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($ownerId)) < str_replace(".", "",$maximumPointInADay)){
                    $iN->iN_InsertNewPoint($ownerId,$postId,$ataNewPostPointAmount);
                }
                if (isset($autoApprovePostStatus) && $autoApprovePostStatus == 'yes' && ($postData['who_can_see'] ?? '') == '4') {
                    $approveNot = $LANG['congratulations_approved'] ?? 'approved';
                    $postApprover = $iN->iN_GetAdminUserID();
                    $iN->iN_UpdateApprovePostStatusAuto($postApprover, $iN->iN_Secure($postId), $iN->iN_Secure($ownerId), $iN->iN_Secure($approveNot));
                }
                // Notify owner that their scheduled post is now live
                try {
                    $time = time();
                    DB::exec(
                        "INSERT INTO i_user_notifications (not_iuid, not_post_id, not_own_iuid, not_not_type, not_time) VALUES (?,?,?,?,?)",
                        [(int)$ownerId, (int)$postId, (int)$ownerId, 'scheduled_published', $time]
                    );
                    DB::exec("UPDATE i_users SET notification_read_status = '1' WHERE iuid = ?", [(int)$ownerId]);
                } catch (Throwable $e) { /* ignore notification failure */ }
            }
            $processed++;
        } catch (Throwable $e) {
            $failed++;
            $iN->iN_FailScheduledPost($postId, $e->getMessage(), $queueId);
        }
    }
}

$reminderProcessed = 0;
$reminderFailed = 0;
$liveReminderLeadMinutes = 15;
$reminderLimit = isset($_GET['reminder_limit']) ? (int)$_GET['reminder_limit'] : 50;
if ($reminderLimit < 1) { $reminderLimit = 50; }
if ($liveReminderLeadMinutes < 1) { $liveReminderLeadMinutes = 15; }

if (isset($emailSendStatus) && (string)$emailSendStatus === '1') {
    $windowEnd = $now + ($liveReminderLeadMinutes * 60);
    $retryDelay = 300;
    $maxAttempts = 3;
    $dueReminders = DB::all(
        "SELECT R.live_id, R.user_id, R.email_attempts, R.email_last_attempt,
                L.live_name, L.live_uid_fk, L.scheduled_at,
                U.i_user_email, U.i_user_fullname, U.i_username, U.email_notification_status,
                C.i_username AS creator_username, C.i_user_fullname AS creator_fullname
         FROM i_live_reminders R
         INNER JOIN i_live L ON L.live_id = R.live_id
         INNER JOIN i_users U ON U.iuid = R.user_id AND U.uStatus IN('1','3')
         INNER JOIN i_users C ON C.iuid = L.live_uid_fk AND C.uStatus IN('1','3')
         WHERE L.scheduled_status = 'scheduled'
           AND (L.started_at IS NULL OR L.started_at = 0)
           AND L.scheduled_at IS NOT NULL
           AND L.scheduled_at > ?
           AND L.scheduled_at <= ?
           AND (R.email_sent_at IS NULL OR R.email_sent_at = 0)
           AND (R.email_attempts < ?)
           AND (R.email_last_attempt IS NULL OR R.email_last_attempt <= ?)
           AND U.i_user_email IS NOT NULL AND U.i_user_email != ''
           AND U.email_notification_status = '1'
         ORDER BY L.scheduled_at ASC, R.id ASC
         LIMIT {$reminderLimit}",
        [(int)$now, (int)$windowEnd, (int)$maxAttempts, (int)($now - $retryDelay)]
    );
    if (!empty($dueReminders)) {
        foreach ($dueReminders as $row) {
            $liveId = (int)($row['live_id'] ?? 0);
            $userId = (int)($row['user_id'] ?? 0);
            $scheduledAt = (int)($row['scheduled_at'] ?? 0);
            $sendEmail = trim((string)($row['i_user_email'] ?? ''));
            if ($liveId <= 0 || $userId <= 0 || $scheduledAt <= 0 || $sendEmail === '') {
                continue;
            }
            DB::exec(
                "UPDATE i_live_reminders
                 SET email_last_attempt = ?, email_attempts = email_attempts + 1
                 WHERE live_id = ? AND user_id = ? AND (email_sent_at IS NULL OR email_sent_at = 0)",
                [(int)$now, (int)$liveId, (int)$userId]
            );
            $creatorUsername = (string)($row['creator_username'] ?? '');
            $creatorFullName = (string)($row['creator_fullname'] ?? '');
            $creatorDisplay = $creatorFullName !== '' ? $creatorFullName : $creatorUsername;
            $liveName = (string)($row['live_name'] ?? '');
            $liveUrl = $base_url . 'live/' . $creatorUsername;
            $formattedTime = date('M d, Y H:i', $scheduledAt);
            $creatorDisplaySafe = $iN->iN_Secure($creatorDisplay, 1, false);
            $liveNameSafe = $iN->iN_Secure($liveName, 1, false);
            $formattedTimeSafe = $iN->iN_Secure($formattedTime, 1, false);
            $subjectTemplate = $LANG['live_reminder_email_subject'] ?? 'Reminder: {creator} is going live soon';
            $titleTemplate = $LANG['live_reminder_email_title'] ?? '{creator} is going live soon';
            $textTemplate = $LANG['live_reminder_email_text'] ?? 'Get ready for "{title}".';
            $timeLabel = $LANG['live_reminder_email_time_label'] ?? 'Starts at';
            $buttonText = $LANG['live_reminder_email_button'] ?? 'Open live page';
            $subject = str_replace(
                ['{creator}', '{title}', '{time}'],
                [$creatorDisplaySafe, $liveNameSafe, $formattedTimeSafe],
                $subjectTemplate
            );
            $reminderTitle = str_replace(
                ['{creator}', '{title}', '{time}'],
                [$creatorDisplaySafe, $liveNameSafe, $formattedTimeSafe],
                $titleTemplate
            );
            $reminderText = str_replace(
                ['{creator}', '{title}', '{time}'],
                [$creatorDisplaySafe, $liveNameSafe, $formattedTimeSafe],
                $textTemplate
            );
            $reminderTimeLabel = $timeLabel;
            $reminderTime = $formattedTimeSafe;
            $reminderUrl = $liveUrl;
            $reminderButton = $buttonText;
            include $appRoot . '/includes/mailTemplates/liveReminderEmailTemplate.php';
            $body = $bodyLiveReminderEmail;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                if ($smtpOrMail === 'smtp' && !empty($smtpHost)) {
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
                    $mailMode = 'smtp';
                } else {
                    $mail->IsMail();
                    $mailMode = 'mail';
                }
                $fromEmail = !empty($smtpEmail) ? $smtpEmail : (!empty($siteEmail) ? $siteEmail : 'no-reply@' . parse_url($base_url, PHP_URL_HOST));
                $mail->setFrom($fromEmail, $siteName);
                $mail->IsHTML(true);
                $mail->addAddress($sendEmail, '');
                $mail->Subject = $iN->iN_Secure($subject);
                $mail->CharSet = 'utf-8';
                $mail->Body = $body;
                if (iN_safeMailSend($mail, $mailMode, 'live_reminder_email')) {
                    DB::exec(
                        "UPDATE i_live_reminders
                         SET email_sent_at = ?, updated_at = ?
                         WHERE live_id = ? AND user_id = ?",
                        [(int)$now, (int)$now, (int)$liveId, (int)$userId]
                    );
                    $reminderProcessed++;
                } else {
                    $reminderFailed++;
                }
            } catch (Throwable $e) {
                $reminderFailed++;
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status'    => 'ok',
    'processed' => $processed,
    'failed'    => $failed,
    'reminder_processed' => $reminderProcessed,
    'reminder_failed' => $reminderFailed
]);
