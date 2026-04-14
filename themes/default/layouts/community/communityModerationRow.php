<?php
?>
<button type="button" class="community_moderation_member_card" data-community="<?php echo (int)$communityID; ?>" data-user="<?php echo (int)$memberID; ?>">
    <div class="community_moderation_member_avatar">
        <img src="<?php echo iN_HelpSecure($memberAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($memberName); ?>">
    </div>
    <div class="community_moderation_member_name"><?php echo iN_HelpSecure($memberName); ?></div>
</button>
