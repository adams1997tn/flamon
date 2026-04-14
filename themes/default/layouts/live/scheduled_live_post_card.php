<?php
$liveID = isset($scheduledLiveData['live_id']) ? (int)$scheduledLiveData['live_id'] : 0;
$liveName = isset($scheduledLiveData['live_name']) ? $scheduledLiveData['live_name'] : '';
$liveCreatorID = isset($scheduledLiveData['live_uid_fk']) ? (int)$scheduledLiveData['live_uid_fk'] : (isset($userPostOwnerID) ? (int)$userPostOwnerID : 0);
$liveScheduledAt = isset($scheduledLiveData['scheduled_at']) ? (int)$scheduledLiveData['scheduled_at'] : 0;
$liveCreatorUserName = isset($userPostOwnerUsername) ? $userPostOwnerUsername : $iN->iN_GetUserName($liveCreatorID);
$liveUserCover = $iN->iN_UserCover($liveCreatorID, $base_url);
$liveUrl = $liveCreatorUserName ? $base_url . 'live/' . $liveCreatorUserName : '#';
$scheduledText = $liveScheduledAt > 0 ? date('M d, Y H:i', $liveScheduledAt) : '';
$scheduledDay = $liveScheduledAt > 0 ? date('j', $liveScheduledAt) : '--';
$scheduledMonth = $liveScheduledAt > 0 ? date('M', $liveScheduledAt) : '';
?>
<div class="scheduled_post_card" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
    <a class="scheduled_post_link" href="<?php echo iN_HelpSecure($liveUrl); ?>">
        <div class="scheduled_post_cover" style="background-image:url('<?php echo iN_HelpSecure($liveUserCover); ?>');">
            <div class="scheduled_post_date">
                <div class="scheduled_post_day"><?php echo iN_HelpSecure($scheduledDay); ?></div>
                <div class="scheduled_post_month"><?php echo iN_HelpSecure($scheduledMonth); ?></div>
            </div>
        </div>
        <div class="scheduled_post_body">
            <div class="scheduled_post_title"><?php echo iN_HelpSecure($liveName); ?></div>
            <?php if ($scheduledText !== '') { ?>
                <div class="scheduled_post_time"><?php echo iN_HelpSecure($scheduledText); ?></div>
            <?php } ?>
        </div>
    </a>
</div>
