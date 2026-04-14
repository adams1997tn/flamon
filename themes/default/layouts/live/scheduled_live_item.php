<?php
$liveID = isset($liData['live_id']) ? (int)$liData['live_id'] : 0;
$liveName = isset($liData['live_name']) ? $liData['live_name'] : '';
$liveCreatorID = isset($liData['live_uid_fk']) ? (int)$liData['live_uid_fk'] : 0;
$liveCreatorUserName = isset($liData['i_username']) ? $liData['i_username'] : '';
$liveCreatorUserFullName = isset($liData['i_user_fullname']) ? $liData['i_user_fullname'] : '';
$liveScheduledAt = isset($liData['scheduled_at']) ? (int)$liData['scheduled_at'] : 0;
$liveType = isset($liData['live_type']) ? $liData['live_type'] : 'free';
$liveCredit = isset($liData['live_credit']) ? $liData['live_credit'] : null;
$liveUserAvatar = $iN->iN_UserAvatar($liveCreatorID, $base_url);
$liveUserCover = $iN->iN_UserCover($liveCreatorID, $base_url);
$liveUrl = $base_url . 'live/' . $liveCreatorUserName;
$isOwner = ($logedIn === '1' && isset($userID) && (int)$userID === (int)$liveCreatorID);
$scheduledText = $liveScheduledAt > 0 ? date('M d, Y H:i', $liveScheduledAt) : '';
if ($fullnameorusername === 'no') {
    $liveCreatorUserFullName = $liveCreatorUserName;
}
?>
<div class="scheduled_live_card" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
    <a class="scheduled_live_link" href="<?php echo iN_HelpSecure($liveUrl); ?>">
        <div class="scheduled_live_cover" style="background-image:url('<?php echo iN_HelpSecure($liveUserCover); ?>');">
            <span class="scheduled_live_badge"><?php echo iN_HelpSecure($LANG['live_scheduled_badge']); ?></span>
            <div class="scheduled_live_avatar" style="background-image:url('<?php echo iN_HelpSecure($liveUserAvatar); ?>');"></div>
        </div>
        <div class="scheduled_live_body">
            <div class="scheduled_live_creator"><?php echo iN_HelpSecure($liveCreatorUserFullName); ?></div>
            <div class="scheduled_live_title"><?php echo iN_HelpSecure($liveName); ?></div>
            <?php if ($scheduledText !== '') { ?>
                <div class="scheduled_live_time">
                    <span class="label"><?php echo iN_HelpSecure($LANG['live_starts_at']); ?></span>
                    <span class="value"><?php echo iN_HelpSecure($scheduledText); ?></span>
                </div>
            <?php } ?>
            <div class="scheduled_live_countdown" data-start="<?php echo iN_HelpSecure($liveScheduledAt); ?>">
                <div class="scheduled_live_countdown_value">--:--:--</div>
                <div class="scheduled_live_countdown_label"><?php echo iN_HelpSecure($LANG['live_starts_in']); ?></div>
            </div>
            <?php if ($liveType === 'paid' && !empty($liveCredit)) { ?>
                <div class="scheduled_live_price">
                    <span class="label"><?php echo iN_HelpSecure($LANG['entrace_fee']); ?></span>
                    <span class="value"><?php echo iN_HelpSecure(formatCurrency($liveCredit, $defaultCurrency)); ?></span>
                </div>
            <?php } ?>
        </div>
    </a>
    <div class="scheduled_live_actions">
        <a class="scheduled_live_view_btn" href="<?php echo iN_HelpSecure($liveUrl); ?>">
            <?php echo iN_HelpSecure($LANG['live_scheduled_view_page']); ?>
        </a>
        <?php if ($isOwner) { ?>
            <button class="scheduled_live_delete_btn" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-confirm="<?php echo iN_HelpSecure($LANG['live_scheduled_delete_confirm']); ?>">
                <?php echo iN_HelpSecure($LANG['live_scheduled_delete']); ?>
            </button>
        <?php } ?>
    </div>
</div>
