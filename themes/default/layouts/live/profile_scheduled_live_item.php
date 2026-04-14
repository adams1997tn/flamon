<?php
$liveID = isset($liData['live_id']) ? (int)$liData['live_id'] : 0;
$liveName = isset($liData['live_name']) ? $liData['live_name'] : '';
$liveCreatorID = isset($liData['live_uid_fk']) ? (int)$liData['live_uid_fk'] : 0;
$liveCreatorUserName = isset($liData['i_username']) ? $liData['i_username'] : '';
$liveScheduledAt = isset($liData['scheduled_at']) ? (int)$liData['scheduled_at'] : 0;
$liveUserCover = $iN->iN_UserCover($liveCreatorID, $base_url);
$liveUrl = $base_url . 'live/' . $liveCreatorUserName;
$isOwner = ($logedIn === '1' && isset($userID) && (int)$userID === (int)$liveCreatorID);
$scheduledText = $liveScheduledAt > 0 ? date('M d, Y H:i', $liveScheduledAt) : '';
$scheduledDay = $liveScheduledAt > 0 ? date('j', $liveScheduledAt) : '--';
$scheduledMonth = $liveScheduledAt > 0 ? date('M', $liveScheduledAt) : '';
?>
<div class="profile_scheduled_live_item" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
    <a class="profile_scheduled_live_link" href="<?php echo iN_HelpSecure($liveUrl); ?>">
        <div class="profile_scheduled_live_cover" style="background-image:url('<?php echo iN_HelpSecure($liveUserCover); ?>');">
            <div class="profile_scheduled_live_date">
                <div class="profile_scheduled_live_day"><?php echo iN_HelpSecure($scheduledDay); ?></div>
                <div class="profile_scheduled_live_month"><?php echo iN_HelpSecure($scheduledMonth); ?></div>
            </div>
        </div>
        <div class="profile_scheduled_live_body">
            <div class="profile_scheduled_live_title"><?php echo iN_HelpSecure($liveName); ?></div>
            <?php if ($scheduledText !== '') { ?>
                <div class="profile_scheduled_live_time">
                    <?php echo iN_HelpSecure($LANG['live_starts_at']); ?>
                    <span><?php echo iN_HelpSecure($scheduledText); ?></span>
                </div>
            <?php } ?>
            <div class="scheduled_live_countdown" data-start="<?php echo iN_HelpSecure($liveScheduledAt); ?>">
                <div class="scheduled_live_countdown_value">--:--:--</div>
                <div class="scheduled_live_countdown_label"><?php echo iN_HelpSecure($LANG['live_starts_in']); ?></div>
            </div>
        </div>
    </a>
    <div class="profile_scheduled_live_actions">
        <a class="profile_scheduled_live_view_btn" href="<?php echo iN_HelpSecure($liveUrl); ?>">
            <?php echo iN_HelpSecure($LANG['live_scheduled_view_page']); ?>
        </a>
        <?php if ($isOwner) { ?>
            <button class="scheduled_live_delete_btn" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-confirm="<?php echo iN_HelpSecure($LANG['live_scheduled_delete_confirm']); ?>">
                <?php echo iN_HelpSecure($LANG['live_scheduled_delete']); ?>
            </button>
        <?php } ?>
    </div>
</div>
