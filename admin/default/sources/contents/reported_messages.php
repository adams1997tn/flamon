<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
$totalUnreadReports = (int)$iN->iN_CalculateAllUnReadReportedMessages();
$totalReportsAll = (int)DB::col("SELECT COUNT(*) FROM i_message_reports");
$totalPages = ceil(($totalReportsAll > 0 ? $totalReportsAll : 1) / $paginationLimit);

if (isset($_GET['page-id'])) {
    $pagep = isset($_GET['page-id']) ? (int)$_GET['page-id'] : 1;
    if (!preg_match('/^[0-9]+$/', (string)$pagep)) {
        $pagep = 1;
    }
} else {
    $pagep = 1;
}
if ($pagep < 1) {
    $pagep = 1;
}
?>
<div class="i_contents_container reported-messages-page">
    <input type="hidden" id="rmReportsCsrf" value="<?php echo iN_HelpSecure($csrfToken); ?>">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['reported_messages']); ?>
        </div>

        <div class="rm_summary_row flex_ tabing_non_justify">
            <div class="rm_stat_card">
                <div class="rm_stat_label"><?php echo iN_HelpSecure($LANG['reports']); ?></div>
                <div class="rm_stat_value"><?php echo iN_HelpSecure($totalReportsAll); ?></div>
            </div>
            <div class="rm_stat_card rm_stat_card_unread">
                <div class="rm_stat_label"><?php echo iN_HelpSecure($LANG['not_checked']); ?></div>
                <div class="rm_stat_value"><?php echo iN_HelpSecure($totalUnreadReports); ?></div>
            </div>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>
            <?php
            $reportedMessageList = $iN->iN_AllTypeReportedMessageList($userID, $paginationLimit, $pagep);
            if ($reportedMessageList) {
            ?>
                <div class="i_overflow_x_auto rm_table_wrap">
                    <table class="border_one rm_table">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['reporter']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['reported_message']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['report_time']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['report_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php
                        foreach ($reportedMessageList as $rData) {
                            $qID = (int)($rData['p_report_id'] ?? 0);
                            $reportedMessageID = (int)($rData['reported_message'] ?? 0);
                            $reportedChatID = (int)($rData['reported_chat_id_fk'] ?? 0);
                            $rUserID = (int)($rData['iuid_fk'] ?? 0);
                            $qContacttime = (int)($rData['report_time'] ?? 0);
                            $qContactReadStatus = (string)($rData['report_status'] ?? '0');
                            $adminNote = trim((string)($rData['admin_note'] ?? ''));
                            $crTime = $qContacttime > 0 ? date('Y-m-d H:i:s', $qContacttime) : date('Y-m-d H:i:s');

                            $userDetail = $iN->iN_GetUserDetails($rUserID);
                            $rPostUserAvatar = $iN->iN_UserAvatar($rUserID, $base_url);
                            $rUserName = $userDetail['i_username'] ?? null;
                            $rUserFullName = $userDetail['i_user_fullname'] ?? null;
                            $reporterAvatar = !empty($rPostUserAvatar) ? $rPostUserAvatar : ($base_url . 'uploads/avatars/no_gender.png');
                            $reporterProfileUrl = !empty($rUserName) ? (iN_HelpSecure($base_url, FILTER_VALIDATE_URL) . $rUserName) : '#';

                            $messageData = DB::one(
                                "SELECT M.message, M.file, M.sticker_url, M.gifurl, M.chat_id_fk
                                 FROM i_chat_conversations M
                                 WHERE M.con_id = ? AND M.chat_id_fk = ?
                                 LIMIT 1",
                                [$reportedMessageID, $reportedChatID]
                            );

                            $messagePreview = '-';
                            if ($messageData) {
                                $messageText = trim(strip_tags((string)($messageData['message'] ?? '')));
                                $messageFile = trim((string)($messageData['file'] ?? ''));
                                $messageSticker = trim((string)($messageData['sticker_url'] ?? ''));
                                $messageGif = trim((string)($messageData['gifurl'] ?? ''));
                                if ($messageText !== '') {
                                    $messagePreview = $messageText;
                                } elseif ($messageSticker !== '') {
                                    $messagePreview = $LANG['chat_message_type_sticker'] ?? ($LANG['sticker'] ?? $LANG['messages']);
                                } elseif ($messageGif !== '') {
                                    $messagePreview = $LANG['chat_message_type_gif'] ?? $LANG['messages'];
                                } elseif ($messageFile !== '') {
                                    $fileIds = array_values(array_filter(array_map('trim', explode(',', $messageFile)), 'strlen'));
                                    $fileCount = count($fileIds);
                                    $messagePreview = $LANG['chat_message_type_file'] ?? $LANG['messages'];
                                    if ($fileCount > 0) {
                                        $messagePreview .= ' (' . $fileCount . ')';
                                    }
                                    if (!empty($fileIds[0]) && is_numeric($fileIds[0])) {
                                        $firstFileData = $iN->iN_GetUploadedMessageFileDetails((int)$fileIds[0]);
                                        if (!empty($firstFileData['uploaded_file_ext'])) {
                                            $messagePreview .= ' .' . strtolower((string)$firstFileData['uploaded_file_ext']);
                                        }
                                    }
                                }
                            }

                            $openChatUrl = iN_HelpSecure($base_url, FILTER_VALIDATE_URL) . 'admin/manage_chats?chat=' . $reportedChatID;
                            $isChecked = $qContactReadStatus === '1';
                        ?>
                            <tr class="transition trhover rm_row rm_row<?php echo iN_HelpSecure($qID); ?><?php echo $isChecked ? ' rm_row_checked' : ''; ?>">
                                <td>
                                    <div class="rm_id_badge">#<?php echo iN_HelpSecure($qID); ?></div>
                                </td>
                                <td>
                                    <div class="rm_reporter flex_ tabing_non_justify">
                                        <div class="rm_reporter_avatar border_two tabing flex_"><img src="<?php echo iN_HelpSecure($reporterAvatar); ?>" alt="<?php echo iN_HelpSecure($rUserFullName); ?>"></div>
                                        <div class="rm_reporter_meta">
                                            <a class="rm_reporter_name truncated" href="<?php echo iN_HelpSecure($reporterProfileUrl); ?>"><?php echo iN_HelpSecure($rUserFullName); ?></a>
                                            <div class="rm_reporter_handle">@<?php echo iN_HelpSecure($rUserName); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="rm_message_box">
                                        <div class="rm_message_full"><?php echo nl2br(iN_HelpSecure($messagePreview)); ?></div>
                                        <div class="rm_message_meta flex_ tabing_non_justify">
                                            <div class="rm_meta_chip">MID: <?php echo iN_HelpSecure($reportedMessageID); ?></div>
                                            <div class="rm_meta_chip">CID: <?php echo iN_HelpSecure($reportedChatID); ?></div>
                                        </div>
                                        <?php if ($reportedChatID > 0) { ?>
                                            <div class="rm_view_all_wrap">
                                                <a class="rm_view_all_link" href="<?php echo iN_HelpSecure($openChatUrl); ?>">
                                                    <span class="rm_view_all_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10')); ?></span>
                                                    <span><?php echo iN_HelpSecure($LANG['chat_view_all_messages']); ?></span>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="tim flex_ tabing"><?php echo iN_HelpSecure($iN->iN_SelectedMenuIcon('73')) . ' ' . TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                                        <div class="i_chck_text box_not_padding_right"><?php echo iN_HelpSecure($LANG['not_checked']); ?></div>
                                        <label class="el-switch el-switch-yellow" for="rmCheckStatus<?php echo $qID; ?>">
                                            <input type="checkbox" name="rmCheckStatus" class="rmchmdReport q<?php echo $qID; ?>" id="rmCheckStatus<?php echo $qID; ?>" data-id="<?php echo $qID; ?>" data-admin-note="<?php echo iN_HelpSecure($adminNote); ?>" <?php echo $isChecked ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                                            <span class="el-switch-style"></span>
                                        </label>
                                        <input type="hidden" name="rmCheckStatus" class="rmCheckStatus" value="<?php echo iN_HelpSecure($qContactReadStatus); ?>">
                                        <div class="i_chck_text admin_note_t"><?php echo iN_HelpSecure($LANG['checked']); ?></div>
                                    </div>
                                </td>
                                <td class="flex_ tabing">
                                    <div class="rm_delete_btn delrm" id="<?php echo iN_HelpSecure($qID); ?>">
                                        <?php echo $iN->iN_SelectedMenuIcon('28'); ?>
                                        <span><?php echo iN_HelpSecure($LANG['delete']); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . iN_HelpSecure($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['no_question_pending'] . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages > 0): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $pagep - 2; ?>"><?php echo $pagep - 2; ?></a></li><?php endif; ?>
                    <?php if ($pagep - 1 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $pagep - 1; ?>"><?php echo $pagep - 1; ?></a></li><?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $pagep; ?>"><?php echo $pagep; ?></a></li>

                    <?php if ($pagep + 1 <= $totalPages): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $pagep + 1; ?>"><?php echo $pagep + 1; ?></a></li><?php endif; ?>
                    <?php if ($pagep + 2 <= $totalPages): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $pagep + 2; ?>"><?php echo $pagep + 2; ?></a></li><?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/reported_messages?page-id=<?php echo $pagep + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
