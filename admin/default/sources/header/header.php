<?php
$adminUserName = isset($userName) ? (string)$userName : '';
$adminUserAvatar = isset($userAvatar) ? (string)$userAvatar : ($base_url . 'uploads/avatars/no_gender.png');
$userID = isset($userID) ? (int)$userID : 0;
$userType = isset($userType) ? (string)$userType : '';
$baseAdminUrl = rtrim((string)$base_url, '/') . '/admin/';

$canAccessPage = static function (string $slug) use ($iN, $userID, $userType): bool {
    $slug = trim($slug);
    if ($slug === '') {
        return false;
    }

    if ($userType === '3') {
        return (bool)$iN->iN_CanModeratorAccessAdminPage($userID, $slug);
    }

    return true;
};

$tableExists = static function (string $tableName): bool {
    $tableName = trim($tableName);
    if ($tableName === '') {
        return false;
    }

    try {
        $exists = DB::col(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$tableName]
        );
        return (int)$exists > 0;
    } catch (Throwable $e) {
        return false;
    }
};

$alertOverdueScheduledTpl = $LANG['admin_alert_overdue_scheduled'] ?? '{count} scheduled posts are overdue.';
$alertPendingWithdrawalsTpl = $LANG['admin_alert_pending_withdrawals'] ?? '{count} withdrawal requests are waiting.';
$alertDeclinedWithdrawalsTpl = $LANG['admin_alert_declined_withdrawals'] ?? '{count} payout requests were declined recently.';
$alertFailedWebhooksTpl = $LANG['admin_alert_failed_webhooks'] ?? '{count} webhook events failed verification.';
$alertStorageLowSpaceTpl = $LANG['admin_alert_storage_low_space'] ?? 'Storage free space is low ({percent} left).';
$alertEmailDisabledText = $LANG['admin_alert_email_delivery_disabled'] ?? 'Email sending is disabled.';

$awaitingApprovalCount = (int)$iN->iN_CalculateNonApprovedPosts();
$reportedPostsCount = (int)$iN->iN_GetTotalReportedPost($userID);
$reportedCommentsCount = (int)$iN->iN_GetTotalReportedComment($userID);
$reportedMessagesCount = (int)$iN->iN_GetTotalReportedMessage($userID);
$totalUnreadReports = $reportedPostsCount + $reportedCommentsCount + $reportedMessagesCount;
$unreadTicketsCount = (int)$iN->iN_CalculateAllUnreadQuestions();
$verificationRequestsCount = (int)$iN->iN_TotalVerificationRequests();

$pendingWithdrawalsCount = 0;
$declinedWithdrawalsCount = 0;
if ($tableExists('i_user_payouts')) {
    try {
        $pendingWithdrawalsCount = (int)DB::col(
            "SELECT COUNT(*) FROM i_user_payouts WHERE payment_type = 'withdrawal' AND status = 'pending'"
        );
        $declinedWithdrawalsCount = (int)DB::col(
            "SELECT COUNT(*) FROM i_user_payouts WHERE payment_type = 'withdrawal' AND status = 'declined' AND payout_time >= ?",
            [time() - 2592000]
        );
    } catch (Throwable $e) {
        $pendingWithdrawalsCount = 0;
        $declinedWithdrawalsCount = 0;
    }
}

$reportTaskPages = [];
if ($reportedPostsCount > 0 && $canAccessPage('reported_posts')) {
    $reportTaskPages[] = [
        'label' => $LANG['reported_posts'] ?? 'Reported Posts',
        'count' => $reportedPostsCount,
        'url' => $baseAdminUrl . 'reported_posts',
    ];
}
if ($reportedCommentsCount > 0 && $canAccessPage('reported_comments')) {
    $reportTaskPages[] = [
        'label' => $LANG['reported_comments'] ?? 'Reported Comments',
        'count' => $reportedCommentsCount,
        'url' => $baseAdminUrl . 'reported_comments',
    ];
}
if ($reportedMessagesCount > 0 && $canAccessPage('reported_messages')) {
    $reportTaskPages[] = [
        'label' => $LANG['reported_messages'] ?? 'Reported Messages',
        'count' => $reportedMessagesCount,
        'url' => $baseAdminUrl . 'reported_messages',
    ];
}
$reportTaskTotal = 0;
foreach ($reportTaskPages as $reportTaskPage) {
    $reportTaskTotal += (int)($reportTaskPage['count'] ?? 0);
}

$pendingTasks = [];
if ($awaitingApprovalCount > 0 && $canAccessPage('awaiting_approval')) {
    $pendingTasks[] = [
        'label' => $LANG['awaiting_approval_posts'] ?? 'Awaiting approval posts',
        'count' => $awaitingApprovalCount,
        'url' => $baseAdminUrl . 'awaiting_approval',
    ];
}
if ($unreadTicketsCount > 0 && $canAccessPage('contact_mails')) {
    $pendingTasks[] = [
        'label' => $LANG['questions_from_users'] ?? 'Questions from users',
        'count' => $unreadTicketsCount,
        'url' => $baseAdminUrl . 'contact_mails',
    ];
}
if ($verificationRequestsCount > 0 && $canAccessPage('creator_requests')) {
    $pendingTasks[] = [
        'label' => $LANG['creator_requests'] ?? 'Creator requests',
        'count' => $verificationRequestsCount,
        'url' => $baseAdminUrl . 'creator_requests',
    ];
}
if ($pendingWithdrawalsCount > 0 && $canAccessPage('manage_withdrawals')) {
    $pendingTasks[] = [
        'label' => $LANG['manage_withdrawals'] ?? 'Manage withdrawals',
        'count' => $pendingWithdrawalsCount,
        'url' => $baseAdminUrl . 'manage_withdrawals',
    ];
}
$pendingTaskTotal = $reportTaskTotal;
foreach ($pendingTasks as $pendingTaskItem) {
    $pendingTaskTotal += (int)($pendingTaskItem['count'] ?? 0);
}
$scheduledQueueExists = $tableExists('i_scheduled_queue');
$scheduledOverdueCount = 0;
if ($scheduledQueueExists) {
    try {
        $scheduledOverdueCount = (int)DB::col(
            "SELECT COUNT(*) FROM i_scheduled_queue WHERE status = 'pending' AND run_at <= ?",
            [time() - 600]
        );
    } catch (Throwable $e) {
        $scheduledOverdueCount = 0;
    }
}

$failedWebhookCount = 0;
if ($tableExists('i_epoch_webhook_events')) {
    try {
        $failedWebhookCount = (int)DB::col(
            "SELECT COUNT(*) FROM i_epoch_webhook_events WHERE verification_status = 'failed' AND created_at >= ?",
            [time() - 604800]
        );
    } catch (Throwable $e) {
        $failedWebhookCount = 0;
    }
}

$storageStatus = 'healthy';
$storageFreePercent = null;
$activeStorageProvider = function_exists('storage_active_provider')
    ? (string)storage_active_provider()
    : 'local';
$activeStorageProvider = trim($activeStorageProvider) !== '' ? $activeStorageProvider : 'local';

if ($activeStorageProvider === 'local') {
    $uploadsRoot = dirname(__DIR__, 4) . '/uploads';
    if (is_dir($uploadsRoot) && function_exists('disk_total_space') && function_exists('disk_free_space')) {
        $diskTotal = (float)@disk_total_space($uploadsRoot);
        $diskFree = (float)@disk_free_space($uploadsRoot);
        if ($diskTotal > 0 && $diskFree >= 0) {
            $storageFreePercent = ($diskFree / $diskTotal) * 100;
            if ($storageFreePercent <= 10) {
                $storageStatus = 'critical';
            } elseif ($storageFreePercent <= 20) {
                $storageStatus = 'warning';
            }
        } else {
            $storageStatus = 'warning';
        }
    } else {
        $storageStatus = 'warning';
    }
} else {
    $storageConfigValid = true;
    switch ($activeStorageProvider) {
        case 's3':
            $storageConfigValid = !empty($s3Bucket) && !empty($s3Key) && !empty($s3SecretKey);
            break;
        case 'spaces':
            $storageConfigValid = !empty($oceanspace_name) && !empty($oceankey) && !empty($oceansecret);
            break;
        case 'wasabi':
            $storageConfigValid = !empty($WasBucket) && !empty($WasKey) && !empty($WasSecretKey);
            break;
        case 'minio':
            $storageConfigValid = !empty($minioBucket) && !empty($minioKey) && !empty($minioSecret) && !empty($minioEndpoint);
            break;
        case 'bunny':
            $storageConfigValid = !empty($bunnyStorageZone) && !empty($bunnyStorageAccessKey);
            break;
        default:
            $storageConfigValid = false;
            break;
    }
    if (!$storageConfigValid) {
        $storageStatus = 'critical';
    }
}

$mailStatus = 'healthy';
if ((string)$emailSendStatus !== '1') {
    $mailStatus = 'warning';
} elseif ((string)$smtpOrMail === 'smtp' && (trim((string)$smtpHost) === '' || trim((string)$smtpUserName) === '')) {
    $mailStatus = 'critical';
}

$schedulerStatus = 'healthy';
if ((string)$scheduledPostsStatus !== '1' || !$scheduledQueueExists) {
    $schedulerStatus = 'warning';
}
if ($scheduledOverdueCount > 0) {
    $schedulerStatus = 'critical';
}

$healthStatuses = [
    'healthy' => $LANG['admin_health_healthy'] ?? 'Healthy',
    'warning' => $LANG['admin_health_warning'] ?? 'Warning',
    'critical' => $LANG['admin_health_critical'] ?? 'Critical',
];

$systemHealth = [
    [
        'label' => $LANG['admin_health_database'] ?? 'Database',
        'status' => 'healthy',
    ],
    [
        'label' => $LANG['admin_health_mail'] ?? 'Mail',
        'status' => $mailStatus,
    ],
    [
        'label' => $LANG['admin_health_storage'] ?? 'Storage',
        'status' => $storageStatus,
    ],
    [
        'label' => $LANG['admin_health_scheduler'] ?? 'Scheduler',
        'status' => $schedulerStatus,
    ],
];
$healthPriority = ['healthy' => 0, 'warning' => 1, 'critical' => 2];
$healthSummaryStatus = 'healthy';
foreach ($systemHealth as $healthItem) {
    $itemStatus = (string)($healthItem['status'] ?? 'healthy');
    if (($healthPriority[$itemStatus] ?? 0) > ($healthPriority[$healthSummaryStatus] ?? 0)) {
        $healthSummaryStatus = $itemStatus;
    }
}
$healthSummaryLabel = $healthStatuses[$healthSummaryStatus] ?? $healthStatuses['warning'];

$smartAlerts = [];
if ($scheduledOverdueCount > 0 && $canAccessPage('scheduled_posts')) {
    $smartAlerts[] = [
        'level' => 'critical',
        'message' => str_replace('{count}', (string)$scheduledOverdueCount, $alertOverdueScheduledTpl),
        'url' => $baseAdminUrl . 'scheduled_posts',
    ];
}
if ($pendingWithdrawalsCount > 0 && $canAccessPage('manage_withdrawals')) {
    $smartAlerts[] = [
        'level' => 'warning',
        'message' => str_replace('{count}', (string)$pendingWithdrawalsCount, $alertPendingWithdrawalsTpl),
        'url' => $baseAdminUrl . 'manage_withdrawals',
    ];
}
if ($declinedWithdrawalsCount > 0 && $canAccessPage('manage_withdrawals')) {
    $smartAlerts[] = [
        'level' => 'warning',
        'message' => str_replace('{count}', (string)$declinedWithdrawalsCount, $alertDeclinedWithdrawalsTpl),
        'url' => $baseAdminUrl . 'manage_withdrawals',
    ];
}
if ($failedWebhookCount > 0 && $canAccessPage('payment_settings')) {
    $smartAlerts[] = [
        'level' => 'critical',
        'message' => str_replace('{count}', (string)$failedWebhookCount, $alertFailedWebhooksTpl),
        'url' => $baseAdminUrl . 'payment_settings',
    ];
}
if ($storageFreePercent !== null && $storageFreePercent <= 20 && $canAccessPage('storage_settings')) {
    $smartAlerts[] = [
        'level' => $storageFreePercent <= 10 ? 'critical' : 'warning',
        'message' => str_replace('{percent}', number_format($storageFreePercent, 1) . '%', $alertStorageLowSpaceTpl),
        'url' => $baseAdminUrl . 'storage_settings',
    ];
}
if ((string)$emailSendStatus !== '1' && $canAccessPage('email_settings')) {
    $smartAlerts[] = [
        'level' => 'warning',
        'message' => $alertEmailDisabledText,
        'url' => $baseAdminUrl . 'email_settings',
    ];
}
$smartAlertCount = count($smartAlerts);
?>
<div class="hitHeader border_one flex_">
    <div class="tabing flex_ border_two clps">
        <div class="collapse_left">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('100')); ?>
        </div>
    </div>

    <div class="admin_header_workspace">
        <div class="admin_header_tools">
            <button type="button" class="admin_cmd_trigger border_one" id="adminOpenCommandPalette" title="Ctrl+K">
                <span class="admin_cmd_shortcut">Ctrl+K</span>
            </button>

            <div class="header_right_item admin_pending_center">
                <button
                    type="button"
                    class="admin_tool_btn border_one admin_pending_toggle"
                    id="adminPendingToggle"
                    aria-expanded="false"
                    aria-controls="adminPendingPanel"
                >
                    <span class="admin_tool_btn_text"><?php echo iN_HelpSecure($LANG['admin_pending_tasks'] ?? 'Pending Tasks'); ?></span>
                    <?php if ($pendingTaskTotal > 0) { ?>
                        <span class="admin_tool_btn_count"><?php echo iN_HelpSecure((string)$pendingTaskTotal); ?></span>
                    <?php } ?>
                </button>
                <div class="admin_pending_panel border_one" id="adminPendingPanel">
                    <div class="admin_pending_panel_header"><?php echo iN_HelpSecure($LANG['admin_pending_tasks'] ?? 'Pending Tasks'); ?></div>
                    <div class="admin_pending_panel_body">
                        <?php if ($reportTaskTotal > 0) { ?>
                            <?php foreach ($reportTaskPages as $reportPage) { ?>
                                <a class="admin_pending_item" href="<?php echo iN_HelpSecure($reportPage['url']); ?>">
                                    <span class="admin_pending_item_left"><?php echo iN_HelpSecure($reportPage['label']); ?></span>
                                    <span class="admin_pending_item_right"><?php echo iN_HelpSecure((string)$reportPage['count']); ?></span>
                                </a>
                            <?php } ?>
                        <?php } ?>
                        <?php foreach ($pendingTasks as $task) { ?>
                            <a class="admin_pending_item" href="<?php echo iN_HelpSecure($task['url']); ?>">
                                <span class="admin_pending_item_left"><?php echo iN_HelpSecure($task['label']); ?></span>
                                <span class="admin_pending_item_right"><?php echo iN_HelpSecure((string)$task['count']); ?></span>
                            </a>
                        <?php } ?>
                        <?php if ($pendingTaskTotal <= 0) { ?>
                            <div class="admin_pending_empty"><?php echo iN_HelpSecure($LANG['admin_no_pending_tasks'] ?? 'No pending tasks.'); ?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="header_right_item admin_health_center">
                <button
                    type="button"
                    class="admin_tool_btn border_one admin_health_toggle"
                    id="adminHealthToggle"
                    aria-expanded="false"
                    aria-controls="adminHealthPanel"
                >
                    <span class="admin_health_dot status_<?php echo iN_HelpSecure($healthSummaryStatus); ?>"></span>
                    <span class="admin_tool_btn_text"><?php echo iN_HelpSecure($LANG['admin_system_health'] ?? 'System Health'); ?></span>
                    <span class="admin_tool_btn_state"><?php echo iN_HelpSecure($healthSummaryLabel); ?></span>
                </button>
                <div class="admin_health_panel border_one" id="adminHealthPanel">
                    <div class="admin_health_panel_header"><?php echo iN_HelpSecure($LANG['admin_system_health'] ?? 'System Health'); ?></div>
                    <div class="admin_health_panel_body">
                        <?php foreach ($systemHealth as $healthItem) { ?>
                            <div class="admin_health_item">
                                <span class="admin_health_dot status_<?php echo iN_HelpSecure($healthItem['status']); ?>"></span>
                                <span class="admin_health_item_name"><?php echo iN_HelpSecure($healthItem['label']); ?></span>
                                <span class="admin_health_item_state"><?php echo iN_HelpSecure($healthStatuses[$healthItem['status']] ?? $healthStatuses['warning']); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="header_right_menu flex_ tabing">
        <div class="header_right_item admin_alert_center">
            <button
                type="button"
                class="item_icon border_two flex_ tabing admin_alert_toggle"
                id="adminAlertsToggle"
                aria-expanded="false"
                aria-controls="adminAlertsPanel"
                title="<?php echo iN_HelpSecure($LANG['admin_smart_alerts'] ?? 'Smart alerts'); ?>"
            >
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?>
                <?php if ($smartAlertCount > 0) { ?>
                    <span class="admin_alert_badge"><?php echo iN_HelpSecure((string)$smartAlertCount); ?></span>
                <?php } ?>
            </button>
            <div class="admin_alert_panel border_one" id="adminAlertsPanel">
                <div class="admin_alert_panel_header">
                    <?php echo iN_HelpSecure($LANG['admin_smart_alerts'] ?? 'Smart alerts'); ?>
                </div>
                <div class="admin_alert_panel_body">
                    <?php if ($smartAlerts) { ?>
                        <?php foreach ($smartAlerts as $alert) { ?>
                            <a class="admin_alert_item level_<?php echo iN_HelpSecure($alert['level']); ?>" href="<?php echo iN_HelpSecure($alert['url']); ?>">
                                <span class="admin_alert_dot"></span>
                                <span class="admin_alert_text"><?php echo iN_HelpSecure($alert['message']); ?></span>
                            </a>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="admin_alert_empty"><?php echo iN_HelpSecure($LANG['admin_no_smart_alerts'] ?? 'No critical alerts right now.'); ?></div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="header_right_item flex_ tabing">
            <a href="<?php echo iN_HelpSecureUrl(filter_var($base_url)); ?>">
                <div class="item_icon border_two flex_ tabing">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('99')); ?>
                </div>
            </a>
        </div>
        <div class="header_right_item">
            <a href="<?php echo iN_HelpSecureUrl(filter_var($base_url . $adminUserName)); ?>">
                <div class="item_icon border_two flex_ tabing">
                    <img src="<?php echo iN_HelpSecureUrl(filter_var($adminUserAvatar)); ?>" alt="Avatar" loading="lazy">
                </div>
            </a>
        </div>
    </div>
</div>

<div
    class="admin_command_palette"
    id="adminCommandPalette"
    aria-hidden="true"
    data-empty-text="<?php echo iN_HelpSecure($LANG['admin_command_palette_no_results'] ?? 'No matching command found.'); ?>"
>
    <div class="admin_command_overlay" data-command-close="1"></div>
    <div class="admin_command_dialog border_one">
        <div class="admin_command_header flex_ tabing_non_justify">
            <div class="admin_command_title"><?php echo iN_HelpSecure($LANG['admin_command_palette_title'] ?? 'Quick command palette'); ?></div>
            <div class="admin_command_hint"><?php echo iN_HelpSecure($LANG['admin_command_palette_hint'] ?? 'Use arrow keys and Enter to navigate.'); ?></div>
        </div>
        <div class="admin_command_search_wrap">
            <input
                type="text"
                id="adminCommandInput"
                class="admin_command_input"
                autocomplete="off"
                placeholder="<?php echo iN_HelpSecure($LANG['admin_command_palette_placeholder'] ?? 'Type a page or action...'); ?>"
            >
        </div>
        <div class="admin_command_list" id="adminCommandList"></div>
        <div class="admin_command_empty" id="adminCommandEmpty"><?php echo iN_HelpSecure($LANG['admin_command_palette_no_results'] ?? 'No matching command found.'); ?></div>
    </div>
</div>
