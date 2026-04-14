<div class="i_modal_bg_in community_moderator_edit_modal" role="dialog" aria-modal="true" aria-labelledby="communityModeratorEditTitle">
    <div class="i_modal_in_in community_moderation_modal_inner">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="communityModeratorEditTitle">
                <?php echo iN_HelpSecure($LANG['community_moderator_edit_title'] ?? 'Moderator settings'); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="community_moderation_body">
                <div class="community_moderator_edit_header">
                    <div class="community_moderator_avatar">
                        <img src="<?php echo iN_HelpSecure($moderatorAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($moderatorName); ?>">
                    </div>
                    <div class="community_moderator_name">
                        <?php echo iN_HelpSecure($moderatorName); ?>
                        <span class="community_role_icon community_moderator_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?></span>
                    </div>
                </div>
                <form class="community_moderator_update_form" method="post" data-error-text="<?php echo iN_HelpSecure($LANG['community_moderation_add_failed'] ?? 'Unable to add user.'); ?>">
                    <input type="hidden" name="f" value="communityModeratorUpdate">
                    <input type="hidden" name="community_id" value="<?php echo (int)$communityID; ?>">
                    <input type="hidden" name="moderator_id" value="<?php echo (int)$moderatorId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">
                    <div class="community_moderator_options">
                        <label class="community_moderator_option">
                            <input type="checkbox" name="perm_manage_members" value="1" <?php echo (string)($moderatorData['can_manage_members'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_members'] ?? 'Member management'); ?></span>
                        </label>
                        <label class="community_moderator_option">
                            <input type="checkbox" name="perm_manage_posts" value="1" <?php echo (string)($moderatorData['can_manage_posts'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_posts'] ?? 'Posts'); ?></span>
                        </label>
                        <label class="community_moderator_option">
                            <input type="checkbox" name="perm_manage_comments" value="1" <?php echo (string)($moderatorData['can_manage_comments'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_comments'] ?? 'Comments'); ?></span>
                        </label>
                        <label class="community_moderator_option">
                            <input type="checkbox" name="perm_manage_reshare" value="1" <?php echo (string)($moderatorData['can_manage_reshare'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_reshare'] ?? 'Reshare'); ?></span>
                        </label>
                        <label class="community_moderator_option">
                            <input type="checkbox" name="perm_manage_view_timeout" value="1" <?php echo (string)($moderatorData['can_manage_view_timeout'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_timeout'] ?? 'View timeout'); ?></span>
                        </label>
                        <label class="community_moderator_option">
                            <input type="checkbox" name="perm_manage_media" value="1" <?php echo (string)($moderatorData['can_manage_media'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_media'] ?? 'Avatar/cover'); ?></span>
                        </label>
                    </div>
                    <div class="community_moderator_options_note">
                        <?php echo iN_HelpSecure($LANG['community_moderator_options_note'] ?? 'Choose what this moderator can manage in the community (members, posts, comments, reshares, view timeouts, avatar/cover).'); ?>
                    </div>
                    <div class="community_moderator_form_actions community_moderator_form_actions_split">
                        <button type="button"
                                class="i_btn_unsubscribe community_moderator_remove_btn"
                                data-community="<?php echo (int)$communityID; ?>"
                                data-user="<?php echo (int)$moderatorId; ?>"
                                data-confirm="<?php echo iN_HelpSecure($LANG['community_moderator_remove_confirm'] ?? 'Remove this moderator?'); ?>">
                            <?php echo iN_HelpSecure($LANG['community_moderator_remove_btn'] ?? 'Remove'); ?>
                        </button>
                        <button type="submit" class="i_nex_btn community_moderator_submit_btn">
                            <?php echo iN_HelpSecure($LANG['community_moderator_update_btn'] ?? 'Update'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
