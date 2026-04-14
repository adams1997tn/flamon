<?php
$isLiveCreator = ((int)$userID === (int)$liveCreator);
$liveScheduledAt = isset($liveScheduledAt) ? (int)$liveScheduledAt : (int)($liveDetails['scheduled_at'] ?? 0);
$scheduledText = $liveScheduledAt > 0 ? date('M d, Y H:i', $liveScheduledAt) : '';
$hasReminder = $iN->iN_HasLiveReminder($liveID, $userID);
$pinnedProduct = $iN->iN_GetLivePinnedProduct($liveID);
$checkUserPurchasedThisLiveStream = '1';
if ($liveType === 'paid' && $userID != $liveCreator) {
    $checkUserPurchasedThisLiveStream = $iN->iN_CheckUserPurchasedThisLiveStream($userID, $liveID);
}
?>
<div class="live_wrapper_tik live_scheduled_wrapper" id="<?php echo iN_HelpSecure($liveID); ?>">
    <div class="live_left">
        <div class="live_left_in_wrapper">
            <div class="live_left_in_holder">
                <a href="<?php echo iN_HelpSecure($base_url); ?>">
                    <div class="i_left_menu_box transition">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('99')); ?>
                        <div class="m_tit"><?php echo iN_HelpSecure($LANG['home_page']); ?></div>
                    </div>
                </a>
                <div class="i_left_menu_box transition g_feed" data-get="friends" data-type="moreposts">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('7')); ?>
                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['newsfeed']); ?></div>
                </div>
                <div class="i_left_menu_box transition g_feed" data-get="allPosts" data-type="moreexplore">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('8')); ?>
                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['explore']); ?></div>
                </div>
                <div class="i_left_menu_box transition g_feed" data-get="premiums" data-type="morepremium">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('9')); ?>
                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['premium']); ?></div>
                </div>
                <a href="<?php echo iN_HelpSecure($base_url); ?>creators">
                    <div class="i_left_menu_box transition">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95')); ?>
                        <div class="m_tit"><?php echo iN_HelpSecure($LANG['our_creators']); ?></div>
                    </div>
                </a>
                <div class="live_suggested_lives_wrapper">
                    <?php include "live_list_widget.php"; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="live_right">
        <div class="live_right_in_wrapper">
            <div class="live_right_in_left">
                <div class="live_video_header">
                    <div class="live_creator_avatar_live flex_ tabing">
                        <a class="flex_ alignItem" href="<?php echo $base_url . $liveCreatorUserName; ?>" target="blank_">
                            <img src="<?php echo iN_HelpSecure($liveCreatorAvatar); ?>">
                        </a>
                    </div>
                    <div class="live_creator_live_name_live_username">
                        <div class="live_creator_live_username">
                            <a class="flex_ alignItem exen loi" href="<?php echo $base_url . $liveCreatorUserName; ?>" target="blank_">
                                <?php echo iN_HelpSecure($liveCreatorFullname); ?>
                            </a>
                        </div>
                        <div class="live_creator_live_name flex_ tabing">
                            <?php echo iN_HelpSecure($siteTitle); ?>
                            <span class="live_schedule_badge"><?php echo iN_HelpSecure($LANG['live_scheduled_badge']); ?></span>
                        </div>
                    </div>
                    <div class="live_header_in_right flex_ tabing">
                        <div class="live_owner_flw_btn">
                            <?php if ($p_friend_status != 'subscriber' && $p_friend_status != 'me' && $p_friend_status != 'flwr') { ?>
                                <div class="i_fw<?php echo iN_HelpSecure($liveCreator); ?> transition <?php echo iN_HelpSecure($flwrBtn); ?>" id="i_btn_like_item" data-u="<?php echo iN_HelpSecure($liveCreator); ?>">
                                    <?php echo html_entity_decode($flwBtnIconText); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="live__live_video_holder live_scheduled_holder">
                    <div class="live_scheduled_panel">
                        <div class="live_scheduled_title"><?php echo iN_HelpSecure($LANG['live_scheduled_title']); ?></div>
                        <div class="live_scheduled_stream_name"><?php echo iN_HelpSecure($siteTitle); ?></div>
                        <div class="live_scheduled_countdown" data-start="<?php echo iN_HelpSecure($liveScheduledAt); ?>">
                            <div class="live_countdown_value">--:--:--</div>
                            <div class="live_countdown_label"><?php echo iN_HelpSecure($LANG['live_starts_in']); ?></div>
                        </div>
                        <?php if ($scheduledText !== '') { ?>
                            <div class="live_scheduled_time">
                                <?php echo iN_HelpSecure($LANG['live_starts_at']); ?>
                                <strong><?php echo iN_HelpSecure($scheduledText); ?></strong>
                            </div>
                        <?php } ?>
                        <?php if ($liveType === 'paid' && $userID != $liveCreator) { ?>
                            <div class="live_scheduled_fee">
                                <span><?php echo iN_HelpSecure($LANG['entrace_fee']); ?>:</span>
                                <strong><?php echo iN_HelpSecure(formatCurrency($liveCredit, $defaultCurrency)); ?></strong>
                            </div>
                            <?php if (!$checkUserPurchasedThisLiveStream) { ?>
                                <div class="live_scheduled_purchase">
                                    <div class="purchaseLiveButton flex_ tabing" id="<?php echo iN_HelpSecure($liveID); ?>">
                                        <?php echo iN_HelpSecure($LANG['buy_now']); ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="live_scheduled_purchase live_scheduled_owned">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('60')); ?>
                                    <?php echo iN_HelpSecure($LANG['already_purchased_product']); ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                        <div class="live_scheduled_actions">
                            <?php if ($isLiveCreator) { ?>
                                <button class="live_start_now_btn transition" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
                                    <?php echo iN_HelpSecure($LANG['live_start_now']); ?>
                                </button>
                            <?php } else { ?>
                                <button class="live_reminder_btn transition<?php echo $hasReminder ? ' is-active' : ''; ?>" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-enabled="<?php echo $hasReminder ? '1' : '0'; ?>">
                                    <?php echo iN_HelpSecure($hasReminder ? $LANG['live_reminder_set'] : $LANG['live_remind_me']); ?>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="live_pinned_product_slot" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-pinned-id="<?php echo $pinnedProduct ? iN_HelpSecure($pinnedProduct['pr_id'] ?? '') : ''; ?>">
                        <button type="button" class="live_pinned_product_toggle" aria-expanded="false">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?>
                            <?php echo iN_HelpSecure($LANG['live_offer']); ?>
                        </button>
                        <div class="live_pinned_product_inner">
                            <?php
                            if ($pinnedProduct) {
                                $isLiveCreator = $isLiveCreator;
                                include "live_pinned_product.php";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="live_right_in_right relativePosition">
                <div class="live_scheduled_side_card">
                    <div class="live_scheduled_side_title"><?php echo iN_HelpSecure($LANG['live_schedule_side_title']); ?></div>
                    <div class="live_scheduled_side_note"><?php echo iN_HelpSecure($LANG['live_schedule_side_note']); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
    window.theLiveID = "<?php echo iN_HelpSecure($liveID); ?>";
    window.liveScheduledAt = "<?php echo iN_HelpSecure($liveScheduledAt); ?>";
    window.liveCreator = "<?php echo iN_HelpSecure($liveCreator); ?>";
    window.liveUserID = "<?php echo iN_HelpSecure($userID); ?>";
    window.liveIsCreator = "<?php echo $isLiveCreator ? '1' : '0'; ?>";
    window.LANG_LIVE_STARTS_SOON = "<?php echo iN_HelpSecure($LANG['live_starting_soon']); ?>";
    window.LANG_LIVE_REMINDER_SET = "<?php echo iN_HelpSecure($LANG['live_reminder_set']); ?>";
    window.LANG_LIVE_REMIND_ME = "<?php echo iN_HelpSecure($LANG['live_remind_me']); ?>";
</script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/liveScheduledHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
