<?php
?>
<button type="button" class="community_moderator_card" data-community="<?php echo (int)$communityID; ?>" data-user="<?php echo (int)$moderatorId; ?>">
    <div class="community_moderator_avatar">
        <img src="<?php echo iN_HelpSecure($moderatorAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($moderatorName); ?>">
    </div>
    <div class="community_moderator_name">
        <?php echo iN_HelpSecure($moderatorName); ?>
        <span class="community_role_icon community_moderator_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?></span>
    </div>
</button>
