<?php
$cardUserId = (int)$candidateUserId;
$cardUserName = (string)$candidateUserName;
$cardUserAvatar = (string)$candidateUserAvatarUrl;
$cardAdded = !empty($isAdded);
?>
<div class="community_moderation_user_card community_moderator_user_card<?php echo $cardAdded ? ' is-added' : ''; ?>" data-user="<?php echo $cardUserId; ?>">
    <div class="community_moderation_user_avatar">
        <img src="<?php echo iN_HelpSecure($cardUserAvatar); ?>" alt="<?php echo iN_HelpSecure($cardUserName); ?>">
    </div>
    <div class="community_moderation_user_name"><?php echo iN_HelpSecure($cardUserName); ?></div>
    <?php if ($cardAdded) { ?>
        <div class="community_moderation_added_label">
            <?php echo iN_HelpSecure($LANG['community_moderation_added'] ?? 'Added'); ?>
        </div>
    <?php } ?>
</div>
