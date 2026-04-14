<div class="community_moderation_action_row<?php echo $isReverted ? ' is-reverted' : ''; ?>">
    <div class="community_moderation_action_meta">
        <img src="<?php echo iN_HelpSecure($moderatorAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($moderatorName); ?>">
        <div class="community_moderation_action_text">
            <div class="community_moderation_action_title"><?php echo iN_HelpSecure($actionLabel); ?></div>
            <div class="community_moderation_action_subtitle">
                <?php echo iN_HelpSecure($targetLabel); ?>
                <?php if ($moderatorName !== '') { ?>
                    · <?php echo iN_HelpSecure($moderatorName); ?>
                    <span class="community_role_icon community_moderator_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?></span>
                <?php } ?>
                <?php if ($actionTimeLabel !== '') { ?>
                    · <?php echo iN_HelpSecure($actionTimeLabel); ?>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="community_moderation_action_controls">
        <?php if ($isReverted) { ?>
            <span class="community_moderation_reverted_label"><?php echo iN_HelpSecure($LANG['community_moderation_action_reverted'] ?? 'Reverted'); ?></span>
        <?php } else { ?>
            <button type="button"
                    class="community_moderation_revert transition"
                    data-action="<?php echo (int)($action['id'] ?? 0); ?>"
                    data-community="<?php echo (int)$communityID; ?>"
                    data-reverted-text="<?php echo iN_HelpSecure($LANG['community_moderation_action_reverted'] ?? 'Reverted'); ?>">
                <?php echo iN_HelpSecure($LANG['community_moderation_action_revert'] ?? 'Revert'); ?>
            </button>
        <?php } ?>
    </div>
</div>
