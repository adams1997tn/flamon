<?php
$campaignSettings = $iN->iN_GetCampaignSettings();
$campaignEnabled = isset($campaignSettings['status']) && (string)$campaignSettings['status'] === '1';
$isCommunityComposer = isset($page) && $page === 'community';
$canPaidLiveButton = $logedIn === '1' && $paidLiveStreamingStatus === '1' && $feesStatus === '2';
$canFreeLiveButton = $logedIn === '1' && $freeLiveStreamingStatus === '1';
$scheduleLiveType = $canFreeLiveButton ? 'freeLive' : ($canPaidLiveButton ? 'paidLive' : '');
$canScheduleLiveButton = $scheduleLiveType !== '';
?>
<div class="i_postFormContainer">
    <?php if ($agoraStatus === '1' && $page !== 'profile') : ?>
        <?php
            $liveChevronSvg = '<svg class="i_live_chevron" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            $liveIconPaid = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="#ffffff" d="M13 2.5a1 1 0 1 0-2 0V4.2c-2.6.4-4.5 2.2-4.5 4.6 0 2.8 2.4 3.7 4.5 4.4v4.4c-1.5-.3-2.5-1.2-2.5-2.4a1 1 0 1 0-2 0c0 2.5 2 4.1 4.5 4.5v1.8a1 1 0 1 0 2 0v-1.8c2.7-.4 4.7-2.2 4.7-4.7 0-2.8-2.4-3.8-4.7-4.5V6.2c1.4.3 2.3 1.1 2.3 2.2a1 1 0 1 0 2 0c0-2.3-1.8-3.9-4.3-4.3V2.5Zm-2 8.6c-1.6-.5-2.5-1.1-2.5-2.3 0-1.1.9-2 2.5-2.3v4.6Zm2 2.4c1.7.6 2.7 1.2 2.7 2.5 0 1.2-1.1 2.2-2.7 2.5v-5Z"/></svg>';
            $liveIconFree = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><g fill="none" stroke="#ffffff" stroke-width="1.9" stroke-linecap="round"><path d="M7.5 9.4a5 5 0 0 0 0 5.2"/><path d="M16.5 9.4a5 5 0 0 1 0 5.2"/><path d="M5 7.2a8 8 0 0 0 0 9.6"/><path d="M19 7.2a8 8 0 0 1 0 9.6"/></g><circle cx="12" cy="12" r="1.8" fill="#ffffff"/></svg>';
            $liveIconSchedule = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><rect x="3.5" y="5.2" width="17" height="15" rx="2.2" fill="#ffffff"/><rect x="3.5" y="5.2" width="17" height="4.6" rx="2.2" fill="#ffffff"/><path d="M8 3.5v3.4M16 3.5v3.4" stroke="#f59e0b" stroke-width="1.8" stroke-linecap="round"/><rect x="6.5" y="12" width="3" height="3" rx=".6" fill="#f59e0b"/><rect x="10.5" y="12" width="3" height="3" rx=".6" fill="#f59e0b"/><rect x="14.5" y="12" width="3" height="3" rx=".6" fill="#f59e0b"/></svg>';
        ?>
        <div class="i_postLiveStreaming flex_ tabing">
            <?php if ($canPaidLiveButton) : ?>
                <div class="i_live_ i_live_paid_card cNLive flex_ tabing_non_justify transition" data-type="paidLive">
                    <span class="i_live_icon_wrap i_live_icon_paid"><?php echo $liveIconPaid; ?></span>
                    <span class="i_live_text">
                        <span class="i_live_title"><?php echo iN_HelpSecure($LANG['live_card_paid_title'] ?? 'Paid Live'); ?></span>
                        <span class="i_live_subtitle"><?php echo iN_HelpSecure($LANG['live_card_paid_sub'] ?? 'Get paid'); ?></span>
                    </span>
                    <?php echo $liveChevronSvg; ?>
                </div>
            <?php endif; ?>

            <?php if ($canFreeLiveButton) : ?>
                <div class="i_live_ i_live_free_card cNLive flex_ tabing_non_justify transition" data-type="freeLive">
                    <span class="i_live_icon_wrap i_live_icon_free"><?php echo $liveIconFree; ?></span>
                    <span class="i_live_text">
                        <span class="i_live_title"><?php echo iN_HelpSecure($LANG['live_card_free_title'] ?? 'Free Live'); ?></span>
                        <span class="i_live_subtitle"><?php echo iN_HelpSecure($LANG['live_card_free_sub'] ?? 'Go live now'); ?></span>
                    </span>
                    <?php echo $liveChevronSvg; ?>
                </div>
            <?php endif; ?>

            <?php if ($canScheduleLiveButton) : ?>
                <div class="i_live_ i_live_schedule_card cNLive flex_ tabing_non_justify transition" data-type="<?php echo iN_HelpSecure($scheduleLiveType); ?>" data-schedule="1">
                    <span class="i_live_icon_wrap i_live_icon_schedule"><?php echo $liveIconSchedule; ?></span>
                    <span class="i_live_text">
                        <span class="i_live_title"><?php echo iN_HelpSecure($LANG['live_card_schedule_title'] ?? 'Schedule'); ?></span>
                        <span class="i_live_subtitle"><?php echo iN_HelpSecure($LANG['live_card_schedule_sub'] ?? 'Plan ahead'); ?></span>
                    </span>
                    <?php echo $liveChevronSvg; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="i_warning"><?php echo iN_HelpSecure($LANG['please_enter_a_message_or_add_a_photo_or_video']); ?></div>
    <div class="i_warning_point"><?php echo iN_HelpSecure($LANG['must_write_point_for_post']); ?></div>
    <div class="i_warning_point_two"><?php echo iN_HelpSecure($LANG['must_be_start_with_number']); ?></div>
    <div class="i_warning_prmfl"><?php echo iN_HelpSecure($LANG['must_upload_files_for_premium']); ?></div>
    <div class="i_warning_unsupported"><?php echo iN_HelpSecure($LANG['unsupported_video_format']); ?></div>
    <div class="i_upload_warning"></div>
    <div class="i_warning_poll"></div>
    <div class="i_warning_schedule"></div>
    <div
        class="i_warning_campaign"
        data-required="<?php echo iN_HelpSecure($LANG['required_field'] ?? 'Required'); ?>"
        data-permission="<?php echo iN_HelpSecure($LANG['campaign_permission'] ?? 'Not allowed'); ?>"
        data-range="<?php echo iN_HelpSecure($LANG['campaign_amount_range'] ?? 'Invalid amount range'); ?>"
        data-min-invalid="<?php echo iN_HelpSecure($LANG['campaign_min_amount_invalid'] ?? 'Invalid minimum'); ?>"
        data-max-invalid="<?php echo iN_HelpSecure($LANG['campaign_max_amount_invalid'] ?? 'Invalid maximum'); ?>"
        data-goal-min-msg="<?php echo iN_HelpSecure($LANG['campaign_goal_below_min'] ?? 'Goal is below minimum.'); ?>"
        data-goal-max-msg="<?php echo iN_HelpSecure($LANG['campaign_goal_above_max'] ?? 'Goal exceeds maximum.'); ?>"
        data-goal-min="<?php echo iN_HelpSecure($campaignSettings['goal_min'] ?? '50'); ?>"
        data-goal-max="<?php echo iN_HelpSecure($campaignSettings['goal_max'] ?? '0'); ?>"
        data-cover-invalid="<?php echo iN_HelpSecure($LANG['campaign_cover_invalid'] ?? 'Cover file is invalid.'); ?>"
    ></div>
    <input type="hidden" id="poll_max_options" value="<?php echo iN_HelpSecure($pollMaxOptions); ?>">
    <input type="hidden" id="poll_min_options" value="<?php echo iN_HelpSecure($pollMinOptions); ?>">
    <input type="hidden" id="poll_csrf_token" value="<?php echo function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''); ?>">
    <input type="hidden" id="scheduled_status_toggle" value="<?php echo iN_HelpSecure($scheduledPostsStatus ?? '0'); ?>">
    <input type="hidden" id="scheduled_max_days" value="<?php echo iN_HelpSecure($scheduledMaxDelayDays ?? 30); ?>">
    <input type="hidden" id="campaign_enabled" value="<?php echo $campaignEnabled ? '1' : '0'; ?>">
    <input type="hidden" id="campaign_title_required_flag" value="<?php echo iN_HelpSecure($campaignSettings['title_required'] ?? '1'); ?>">
    <input type="hidden" id="campaign_goal_required_flag" value="<?php echo iN_HelpSecure($campaignSettings['goal_required'] ?? '1'); ?>">
    <input type="hidden" id="campaign_deadline_required_flag" value="<?php echo iN_HelpSecure($campaignSettings['deadline_required'] ?? '1'); ?>">
    <input type="hidden" id="campaign_goal_min_flag" value="<?php echo iN_HelpSecure($campaignSettings['goal_min'] ?? '50'); ?>">
    <input type="hidden" id="campaign_goal_max_flag" value="<?php echo iN_HelpSecure($campaignSettings['goal_max'] ?? '100000'); ?>">

    <div class="i_post_form transition aft">
        <div class="i_post_creator_avatar">
            <a href="<?php echo iN_HelpSecure($base_url) . $userName; ?>">
                <img src="<?php echo iN_HelpSecure($userAvatar); ?>" alt="<?php echo iN_HelpSecure($userFullName); ?>">
            </a>
        </div>
        <div class="i_post_form_textarea">
            <textarea
                name="postText"
                id="newPostT"
                maxlength="<?php echo iN_HelpSecure($availableLength); ?>"
                class="comment commenta newPostT"
                placeholder="<?php echo iN_HelpSecure($LANG['write_message_add_photo_or_video']); ?>"></textarea>
        </div>
    </div>

    <?php if ($userWhoCanSeePost === '4') : ?>
        <div class="point_input_wrapper">
            <input
                type="text"
                name="point"
                id="point"
                class="pointIN"
                onkeypress="return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)"
                placeholder="<?php echo iN_HelpSecure($LANG['write_points']); ?>">
            <div class="box_not box_not_padding_left">
                <?php echo iN_HelpSecure($LANG['point_wanted']); ?>
            </div>
        </div>
    <?php endif; ?>

    <form id="tuploadform" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url) . 'requests/request.php'; ?>">
        <div class="i_uploaded_iv nonePoint">
            <div class="i_upload_progress"></div>
            <div class="i_uploading_not">
                <?php echo iN_HelpSecure($LANG['uploading_please_wait']); ?>
            </div>
            <div class="i_uploaded_file_box"></div>
        </div>
    </form>

    <div class="mentions_list nonePoint"></div>

    <div
        class="schedule_controls nonePoint"
        id="scheduleControls"
        data-invalid="<?php echo iN_HelpSecure($LANG['invalid_time']); ?>"
        data-disabled="<?php echo iN_HelpSecure($LANG['schedule_disabled']); ?>"
        data-created="<?php echo iN_HelpSecure($LANG['schedule_created']); ?>"
        data-max-days="<?php echo iN_HelpSecure($scheduledMaxDelayDays ?? 30); ?>"
    >
        <input type="checkbox" id="schedulePostToggle" class="nonePoint" <?php echo ($scheduledPostsStatus ?? '0') !== '1' ? 'disabled' : ''; ?>>
        <div class="schedule_selection nonePoint" id="scheduleSelection">
            <div class="schedule_selected_time" id="scheduleSelectedTime"></div>
            <div class="schedule_clear" id="scheduleClear">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
            </div>
        </div>
        <div class="schedule_popup" id="schedulePopup">
            <div class="schedule_popup_card">
                <div class="schedule_popup_head flex_ tabing_non_justify">
                    <div class="schedule_popup_title"><?php echo iN_HelpSecure($LANG['schedule_time']); ?></div>
                    <div class="schedule_popup_close" id="schedulePopupClose"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?></div>
                </div>
                <div class="schedule_time_row">
                    <input type="hidden" id="scheduleAt" min="<?php echo date('Y-m-d\TH:i'); ?>">
                    <div class="schedule_dt_inputs">
                        <label class="schedule_dt_field">
                            <span><?php echo iN_HelpSecure($LANG['schedule_time']); ?></span>
                            <div class="schedule_dt_group">
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['month'] ?? 'Month'); ?></div>
                                    <select id="scheduleMonth"></select>
                                </div>
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['day'] ?? 'Day'); ?></div>
                                    <select id="scheduleDay"></select>
                                </div>
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['year'] ?? 'Year'); ?></div>
                                    <select id="scheduleYear"></select>
                                </div>
                            </div>
                        </label>
                        <label class="schedule_dt_field">
                            <span><?php echo iN_HelpSecure($LANG['time'] ?? 'Time'); ?></span>
                            <div class="schedule_dt_group">
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['hour'] ?? 'Hour'); ?></div>
                                    <select id="scheduleHour"></select>
                                </div>
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['minute'] ?? 'Minute'); ?></div>
                                    <select id="scheduleMinute"></select>
                                </div>
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['second'] ?? 'Second'); ?></div>
                                    <select id="scheduleSecond"></select>
                                </div>
                            </div>
                            <div class="schedule_time_note"><?php echo iN_HelpSecure($LANG['time_format_hint'] ?? '24-hour format.'); ?></div>
                        </label>
                    </div>
                    <div class="schedule_info">
                        <div><?php echo iN_HelpSecure($LANG['schedule_post']); ?> — <?php echo iN_HelpSecure($LANG['schedule_hint'] ?? 'Pick a date and time for your post.'); ?></div>
                        <div class="schedule_tz"><?php echo iN_HelpSecure($LANG['timezone'] ?? 'Timezone'); ?>: <?php echo date_default_timezone_get(); ?></div>
                    </div>
                    <div class="schedule_hint">
                        <?php echo preg_replace('/\{time\}/', iN_HelpSecure($scheduledMaxDelayDays), $LANG['scheduled_for']); ?>
                    </div>
                    <div class="schedule_inline_warning nonePoint" id="scheduleInlineWarning"></div>
                </div>
                <div class="schedule_popup_actions flex_ tabing_non_justify">
                    <div class="schedule_popup_btn cancel" id="schedulePopupCancel"><?php echo iN_HelpSecure($LANG['cancel_schedule']); ?></div>
                    <div class="schedule_popup_btn ok" id="schedulePopupOk"><?php echo iN_HelpSecure($LANG['ok'] ?? 'OK'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($pollSystemStatus === '1') : ?>
        <div
            class="poll_builder nonePoint"
            id="pollBuilder"
            data-max="<?php echo iN_HelpSecure($pollMaxOptions); ?>"
            data-min="<?php echo iN_HelpSecure($pollMinOptions); ?>"
            data-msg-max="<?php echo iN_HelpSecure($LANG['poll_option_limit_reached']); ?>"
            data-msg-min="<?php echo iN_HelpSecure($LANG['poll_need_more_options']); ?>"
            data-msg-question="<?php echo iN_HelpSecure($LANG['poll_question_required']); ?>"
            data-msg-disabled="<?php echo iN_HelpSecure($LANG['poll_disabled_now']); ?>"
            data-msg-files="<?php echo iN_HelpSecure($LANG['poll_cant_add_files']); ?>"
        >
            <div class="poll_builder_head flex_ tabing_non_justify">
                <div class="poll_builder_title"><?php echo iN_HelpSecure($LANG['poll_builder_title']); ?></div>
                <div class="close_poll_builder transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?></div>
            </div>
            <div class="poll_options_wrapper">
                <div class="poll_option_input">
                    <input type="text" class="poll_option_field i_input" placeholder="<?php echo iN_HelpSecure($LANG['poll_option_placeholder']); ?>">
                </div>
                <div class="poll_option_input">
                    <input type="text" class="poll_option_field i_input" placeholder="<?php echo iN_HelpSecure($LANG['poll_option_placeholder']); ?>">
                </div>
            </div>
            <div class="poll_actions flex_ tabing_non_justify">
                <div class="add_poll_option transition">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?>
                    <span><?php echo iN_HelpSecure($LANG['poll_add_option']); ?></span>
                </div>
                <div class="poll_limit_notice">
                    <?php echo preg_replace('/{count}/', iN_HelpSecure($pollMaxOptions), $LANG['poll_option_limit_notice']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="i_form_buttons">
        <div class="i_create_new_toggle transition" id="createNewToggle">
            <span class="create_new_icon">+</span>
            <span class="create_new_label"><?php echo iN_HelpSecure($LANG['create_new']); ?></span>
        </div>

        <?php
        $quickActionsLayoutSafe = isset($quickActionsLayout) ? (string)$quickActionsLayout : 'popup';
        $quickActionsLayoutSafe = in_array($quickActionsLayoutSafe, ['popup', 'inline'], true) ? $quickActionsLayoutSafe : 'popup';
        ?>
        <div class="i_quick_actions_container qa_layout_<?php echo iN_HelpSecure($quickActionsLayoutSafe); ?>" id="quickActionsContainer" data-qa-layout="<?php echo iN_HelpSecure($quickActionsLayoutSafe); ?>" aria-hidden="true">
            <div class="qa_overlay" id="qaOverlay"></div>
            <div class="qa_popup" role="dialog" aria-modal="true" aria-labelledby="qaTitle">
                <div class="qa_header">
                    <span id="qaTitle"><?php echo iN_HelpSecure($LANG['create_new']); ?></span>
                    <div class="qa_close" id="qaClose">×</div>
                </div>
                <div class="qa_body">
                    <div class="form_btn transition qa_item" data-qa-type="image_video" data-label="<?php echo iN_HelpSecure($LANG['image_video']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['qa_image_video']); ?>">
                        <form id="uploadform" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url) . 'requests/request.php'; ?>">
                            <label for="i_image_video">
                                <div class="i_image_video_btn">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('49')); ?>
                                </div>
                                <input type="file" id="i_image_video" class="imageorvideo" name="uploading[]" data-id="upload" multiple>
                            </label>
                            <div class="qa_label"><?php echo iN_HelpSecure($LANG['qa_image_video']); ?></div>
                        </form>
                    </div>
                <!--Upload Reels-->
                <?php if (!$isCommunityComposer && isset($reelsFeatureStatus) && (string)$reelsFeatureStatus === '1') : ?>
                    <div class="form_btn transition qa_item" data-qa-type="reels" data-label="<?php echo iN_HelpSecure($LANG['create_reels']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['reels']); ?>">
                        <form id="uploadReelsform" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url) . 'requests/request.php'; ?>">
                            <label for="i_reels_video">
                                <div class="i_image_video_btn">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('187')); ?>
                                </div>
                                <input type="file" id="i_reels_video" class="imageorvideo" name="uploading[]" data-id="uploadReel">
                            </label>
                            <div class="qa_label"><?php echo iN_HelpSecure($LANG['reels']); ?></div>
                        </form>
                    </div>
                <?php endif; ?>
                <!--/Upload Reels-->
                <?php if (!$isCommunityComposer && $openAiStatus === '1') : ?>
                    <div class="i_ai_generate transition form_btn getAiBox qa_item" data-qa-type="ai" data-type="aiBox" data-label="<?php echo iN_HelpSecure($LANG['generate_ai_content']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['qa_ai']); ?>">
                        <div class="i_pb_aiBox">
                            <div class="i_ai_emojis_Box">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('184')); ?>
                            </div>
                        </div>
                        <div class="qa_label"><?php echo iN_HelpSecure($LANG['qa_ai']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($pollSystemStatus === '1') : ?>
                    <div class="form_btn transition openPollBuilderBtn qa_item" data-qa-type="poll" data-label="<?php echo iN_HelpSecure($LANG['create_poll']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['qa_poll']); ?>">
                        <div class="i_image_video_btn">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('110')); ?>
                        </div>
                        <div class="qa_label"><?php echo iN_HelpSecure($LANG['qa_poll']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($campaignEnabled) : ?>
                    <div
                        class="form_btn transition schedule_btn campaignOpenBtn qa_item"
                        id="campaignOpenBtn"
                        data-qa-type="campaign"
                        data-label="<?php echo iN_HelpSecure($LANG['campaign_toggle'] ?? 'Create as campaign'); ?>"
                        data-qa-label="<?php echo iN_HelpSecure($LANG['qa_campaign']); ?>"
                        data-msg-disabled="<?php echo iN_HelpSecure($LANG['campaign_disabled'] ?? 'Campaigns are disabled.'); ?>"
                        title="<?php echo iN_HelpSecure($LANG['campaign_settings_desc'] ?? 'Campaign settings'); ?>"
                    >
                        <div class="i_image_video_btn">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('190')); ?>
                        </div>
                        <div class="qa_label"><?php echo iN_HelpSecure($LANG['qa_campaign']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($iN->iN_ShopData($userID, 1) === 'yes') : ?>
                    <?php if ($feesStatus === '2' && $iN->iN_ShopData($userID, '8') === 'yes') : ?>
                        <div class="form_btn transition qa_item" data-qa-type="product" data-label="<?php echo iN_HelpSecure($LANG['createaProduct']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['createaProduct']); ?>">
                            <div class="i_image_video_btn">
                                <a href="<?php echo $base_url . 'settings?tab=createaProduct'; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('155')); ?>
                                </a>
                            </div>
                            <div class="qa_label"><?php echo iN_HelpSecure($LANG['createaProduct']); ?></div>
                        </div>
                    <?php elseif ($iN->iN_ShopData($userID, '8') === 'no') : ?>
                        <div class="form_btn transition qa_item" data-qa-type="product" data-label="<?php echo iN_HelpSecure($LANG['createaProduct']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['createaProduct']); ?>">
                            <div class="i_image_video_btn">
                                <a href="<?php echo $base_url . 'settings?tab=createaProduct'; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('155')); ?>
                                </a>
                            </div>
                            <div class="qa_label"><?php echo iN_HelpSecure($LANG['createaProduct']); ?></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="form_btn transition schedule_btn qa_item" data-qa-type="schedule" data-label="<?php echo iN_HelpSecure($LANG['schedule_post']); ?>" data-qa-label="<?php echo iN_HelpSecure($LANG['qa_schedule']); ?>" id="scheduleButton">
                    <div class="i_image_video_btn">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73')); ?>
                    </div>
                    <div class="qa_label"><?php echo iN_HelpSecure($LANG['qa_schedule']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($campaignEnabled) : ?>
        <div class="campaign_popup_wrapper nonePoint" id="campaignPopup">
            <div class="campaign_popup_overlay" id="campaignPopupClose"></div>
            <div class="campaign_popup_card">
                <div class="campaign_popup_head flex_ tabing_non_justify">
                    <div class="campaign_popup_title"><?php echo iN_HelpSecure($LANG['campaign_settings'] ?? 'Campaign'); ?></div>
                    <div class="campaign_popup_close" id="campaignPopupCloseBtn"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?></div>
                </div>
                <div class="campaign_fields column flex_">
                    <label class="i_input_wrapper column">
                        <span><?php echo iN_HelpSecure($LANG['campaign_label_title'] ?? 'Title'); ?><?php echo ($campaignSettings['title_required'] ?? '1') === '1' ? ' *' : ''; ?></span>
                        <input type="text" id="campaignTitle" class="i_input" maxlength="255" data-msg="<?php echo iN_HelpSecure($LANG['campaign_title_required'] ?? 'Title required'); ?>">
                    </label>
                    <label class="i_input_wrapper column">
                        <span><?php echo iN_HelpSecure($LANG['campaign_label_summary'] ?? 'Summary'); ?></span>
                        <textarea id="campaignSummary" class="i_input" rows="3"></textarea>
                    </label>
            <label class="i_input_wrapper column">
                <span><?php echo iN_HelpSecure($LANG['campaign_label_goal'] ?? 'Goal amount'); ?><?php echo ($campaignSettings['goal_required'] ?? '1') === '1' ? ' *' : ''; ?></span>
                <input type="number" step="0.01" min="0" id="campaignGoal" class="i_input campaignNumber" inputmode="decimal" data-msg="<?php echo iN_HelpSecure($LANG['campaign_goal_required'] ?? 'Goal required'); ?>" data-invalid="<?php echo iN_HelpSecure($LANG['campaign_goal_invalid'] ?? 'Invalid goal'); ?>">
                <div class="campaign_hint schedule_time_note"><?php echo iN_HelpSecure($LANG['campaign_goal_hint'] ?? 'Total amount you want to raise.'); ?></div>
            </label>
            <div class="campaign_amounts flex_ tabing_non_justify">
                <label class="i_input_wrapper column">
                    <span><?php echo iN_HelpSecure($LANG['campaign_label_min'] ?? 'Minimum contribution'); ?></span>
                    <input type="number" step="0.01" min="0" id="campaignMinAmount" class="i_input campaignNumber" inputmode="decimal">
                    <div class="campaign_hint schedule_time_note"><?php echo iN_HelpSecure($LANG['campaign_min_hint'] ?? 'Smallest single contribution allowed.'); ?></div>
                </label>
                <label class="i_input_wrapper column">
                    <span><?php echo iN_HelpSecure($LANG['campaign_label_max'] ?? 'Maximum contribution'); ?></span>
                    <input type="number" step="0.01" min="0" id="campaignMaxAmount" class="i_input campaignNumber" inputmode="decimal">
                    <div class="campaign_hint schedule_time_note"><?php echo iN_HelpSecure($LANG['campaign_max_hint'] ?? 'Largest single contribution allowed.'); ?></div>
                </label>
            </div>
            <div class="campaign_deadline schedule_dt_inputs">
                <div class="schedule_dt_field">
                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['campaign_label_deadline'] ?? 'Deadline'); ?><?php echo ($campaignSettings['deadline_required'] ?? '1') === '1' ? ' *' : ''; ?></div>
                    <div class="schedule_dt_group">
                        <div class="schedule_dt_item">
                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['month'] ?? 'Month'); ?></div>
                            <select id="campaignDeadlineMonth" class="i_input"></select>
                        </div>
                        <div class="schedule_dt_item">
                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['day'] ?? 'Day'); ?></div>
                            <select id="campaignDeadlineDay" class="i_input"></select>
                        </div>
                        <div class="schedule_dt_item">
                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['year'] ?? 'Year'); ?></div>
                            <select id="campaignDeadlineYear" class="i_input"></select>
                        </div>
                    </div>
                </div>
                <div class="schedule_dt_field">
                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['time'] ?? 'Time'); ?></div>
                    <div class="schedule_dt_group">
                        <div class="schedule_dt_item">
                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['hour'] ?? 'Hour'); ?></div>
                            <select id="campaignDeadlineHour" class="i_input"></select>
                        </div>
                        <div class="schedule_dt_item">
                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['minute'] ?? 'Minute'); ?></div>
                            <select id="campaignDeadlineMinute" class="i_input"></select>
                        </div>
                        <div class="schedule_dt_item">
                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['second'] ?? 'Second'); ?></div>
                            <select id="campaignDeadlineSecond" class="i_input"></select>
                        </div>
                    </div>
                    <div class="campaign_hint schedule_time_note"><?php echo iN_HelpSecure($LANG['campaign_deadline_hint'] ?? 'Choose a future date and time.'); ?></div>
                </div>
            </div>
            <div class="campaign_cover_block column">
                <div class="campaign_cover_header flex_ tabing_non_justify">
                    <span class="campaign_cover_title"><?php echo iN_HelpSecure($LANG['campaign_label_cover'] ?? 'Cover image'); ?></span>
                    <button type="button" class="form_btn transition campaignCoverBtn" id="campaignCoverButton">
                                <?php echo iN_HelpSecure($LANG['campaign_cover_upload_btn'] ?? 'Upload cover'); ?>
                            </button>
                        </div>
                        <div class="campaign_cover_drop" id="campaignCoverDrop">
                            <div class="campaign_cover_hint"><?php echo iN_HelpSecure($LANG['campaign_cover_hint'] ?? 'JPG/PNG recommended.'); ?></div>
                        </div>
                        <input type="hidden" id="campaignCoverId">
                        <div class="campaign_cover_preview nonePoint" id="campaignCoverPreview">
                            <img src="" alt="<?php echo iN_HelpSecure($LANG['campaign_label_cover'] ?? 'Cover'); ?>" id="campaignCoverImg">
                            <div class="campaign_cover_remove" id="campaignCoverRemove">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?> <?php echo iN_HelpSecure($LANG['campaign_cover_remove'] ?? 'Remove'); ?>
                            </div>
                        </div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['campaign_feature_desc'] ?? 'Campaign post'); ?></div>
                    </div>
                </div>
                <div class="campaign_popup_actions flex_ tabing_non_justify">
                    <button type="button" class="form_btn transition campaignSaveBtn"><?php echo iN_HelpSecure($LANG['campaign_submit_btn'] ?? 'Create campaign'); ?></button>
                    <button type="button" class="form_btn transition campaignCancelBtn"><?php echo iN_HelpSecure($LANG['cancel'] ?? 'Cancel'); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <form id="campaignCoverUploadForm" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url) . 'requests/request.php'; ?>">
        <input type="file" name="uploading[]" id="campaignCoverHiddenInput" accept="image/*" class="campaign_cover_input">
    </form>
    <div id="campaignCoverTemp" class="nonePoint"></div>

        <div class="i_pb_emojis transition">
            <div class="i_pb_emojisBox getEmojis" data-type="emojiBox">
                <div class="i_pb_emojis_Box">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('25')); ?>
                </div>
            </div>
        </div>
        <div class="i_pb_premiumPost transition">
            <div class="i_pb_premiumPostBox wsUpdate transition <?php echo $userWhoCanSeePost === 4 ? 'wselected' : ''; ?>" data-id="4" id="wsUpdate4">
                <div class="i_pb_premium_box">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('175')); ?>
                </div>
            </div>
        </div>
        <div class="ws_selected_chip transition"></div>

        <div class="form_who_see transition">
            <div class="whoSeeBox whs">
                <div class="wBox">
                    <?php echo html_entity_decode($activeWhoCanSee); ?>
                </div>
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
            </div>

            <div class="i_choose_ws_wrapper">
                <div class="whctt"><?php echo iN_HelpSecure($LANG['whocanseethis']); ?></div>

                <div class="i_whoseech_menu_item_out wsUpdate transition <?php echo $userWhoCanSeePost === 1 ? 'wselected' : ''; ?>" data-id="1" id="wsUpdate1">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('50')); ?> <?php echo iN_HelpSecure($LANG['weveryone']); ?>
                </div>

                <div class="i_whoseech_menu_item_out wsUpdate transition <?php echo $userWhoCanSeePost === 2 ? 'wselected' : ''; ?>" data-id="2" id="wsUpdate2">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?> <?php echo iN_HelpSecure($LANG['wfollowers']); ?>
                </div>

                <?php if ($feesStatus === '2' && empty($hideSubscriberVisibility)) : ?>
                    <div class="i_whoseech_menu_item_out wsUpdate transition <?php echo $userWhoCanSeePost === 3 ? 'wselected' : ''; ?>" data-id="3" id="wsUpdate3">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')); ?> <?php echo iN_HelpSecure($LANG['wsubscribers']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <input type="hidden" id="uploadVal">
        </div>

        <div class="publish_btn transition publish" id="publish_btn_main" style="display:none;">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?>
            <div class="pbtn"><?php echo iN_HelpSecure($LANG['publish']); ?></div>
        </div>
    </div>
</div>
<?php
if (!isset($disableDayMessage) || !$disableDayMessage) {
    include 'dayMessage.php';
}
?>
<script type="text/javascript">
(function(){
    function togglePublishBtn(){
        var $ta = $('#newPostT');
        if (!$ta.length) return;
        var hasText = $.trim($ta.val() || '') !== '';
        var hasFiles = $.trim($('#uploadVal').val() || '') !== '';
        var $btn = $('#publish_btn_main');
        if (hasText || hasFiles) {
            if (!$btn.is(':visible')) { $btn.stop(true, true).fadeIn(150); }
        } else {
            if ($btn.is(':visible')) { $btn.stop(true, true).fadeOut(120); }
        }
    }
    $(document).on('input keyup change paste cut', '#newPostT', togglePublishBtn);
    $(document).on('change', '#uploadVal', togglePublishBtn);
    // After publish, existing code clears textarea and triggers "change" — keep button hidden.
    $(function(){ togglePublishBtn(); });
})();
</script>
