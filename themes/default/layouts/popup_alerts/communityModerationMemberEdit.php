<div class="i_modal_bg_in community_moderation_member_modal" role="dialog" aria-modal="true" aria-labelledby="communityModerationMemberTitle">
    <div class="i_modal_in_in community_moderation_modal_inner">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="communityModerationMemberTitle">
                <?php echo iN_HelpSecure($LANG['community_moderation_member_title'] ?? 'Member settings'); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="community_moderation_body">
                <div class="community_moderator_edit_header">
                    <div class="community_moderator_avatar">
                        <img src="<?php echo iN_HelpSecure($memberAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($memberName); ?>">
                    </div>
                    <div class="community_moderator_name"><?php echo iN_HelpSecure($memberName); ?></div>
                </div>
                <form class="community_member_form" method="post">
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="f" value="communityMemberModerate">
                    <input type="hidden" name="community_id" value="<?php echo (int)$communityID; ?>">
                    <input type="hidden" name="member_id" value="<?php echo (int)$memberID; ?>">
                    <div class="community_member_actions">
                        <?php if ($canManageMembers) { ?>
                            <select name="status" class="inora_user_input">
                                <option value="active" <?php echo $memberStatus === 'active' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_member_active'] ?? 'Active'); ?></option>
                                <option value="restricted" <?php echo $memberStatus === 'restricted' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_member_restricted'] ?? 'Restricted'); ?></option>
                                <option value="blocked" <?php echo $memberStatus === 'blocked' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_member_blocked'] ?? 'Blocked'); ?></option>
                            </select>
                        <?php } ?>
                        <?php if ($canManagePosts) { ?>
                            <select name="post_disabled" class="inora_user_input">
                                <option value="0" <?php echo !$memberPostDisabled ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_moderation_posts_enabled'] ?? 'Posts enabled'); ?></option>
                                <option value="1" <?php echo $memberPostDisabled ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_moderation_posts_disabled'] ?? 'Posts disabled'); ?></option>
                            </select>
                        <?php } ?>
                        <?php if ($canManageComments) { ?>
                            <select name="comment_disabled" class="inora_user_input">
                                <option value="0" <?php echo !$memberCommentDisabled ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_moderation_comments_enabled'] ?? 'Comments enabled'); ?></option>
                                <option value="1" <?php echo $memberCommentDisabled ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_moderation_comments_disabled'] ?? 'Comments disabled'); ?></option>
                            </select>
                        <?php } ?>
                        <?php if ($canManageReshare) { ?>
                            <select name="reshare_disabled" class="inora_user_input">
                                <option value="0" <?php echo !$memberReshareDisabled ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_moderation_reshare_enabled'] ?? 'Reshare enabled'); ?></option>
                                <option value="1" <?php echo $memberReshareDisabled ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_moderation_reshare_disabled'] ?? 'Reshare disabled'); ?></option>
                            </select>
                        <?php } ?>
                        <?php if ($canManageTimeout) { ?>
                            <div class="community_member_timeout">
                                <div class="community_member_timeout_label">
                                    <?php echo iN_HelpSecure($LANG['community_moderation_timeout_current'] ?? 'Current'); ?>:
                                    <?php echo iN_HelpSecure($currentTimeoutLabel); ?>
                                </div>
                                <select name="view_timeout" class="inora_user_input">
                                    <option value="keep" selected><?php echo iN_HelpSecure($LANG['community_moderation_timeout_keep'] ?? 'Keep current'); ?></option>
                                    <option value="none"><?php echo iN_HelpSecure($LANG['community_moderation_timeout_none'] ?? 'No timeout'); ?></option>
                                    <?php if (in_array('1', $timeoutOptions, true)) { ?>
                                        <option value="1"><?php echo iN_HelpSecure($LANG['community_moderation_timeout_1d'] ?? '1 day'); ?></option>
                                    <?php } ?>
                                    <?php if (in_array('3', $timeoutOptions, true)) { ?>
                                        <option value="3"><?php echo iN_HelpSecure($LANG['community_moderation_timeout_3d'] ?? '3 days'); ?></option>
                                    <?php } ?>
                                    <?php if (in_array('7', $timeoutOptions, true)) { ?>
                                        <option value="7"><?php echo iN_HelpSecure($LANG['community_moderation_timeout_7d'] ?? '7 days'); ?></option>
                                    <?php } ?>
                                    <?php if (in_array('30', $timeoutOptions, true)) { ?>
                                        <option value="30"><?php echo iN_HelpSecure($LANG['community_moderation_timeout_30d'] ?? '30 days'); ?></option>
                                    <?php } ?>
                                    <?php if (in_array('permanent', $timeoutOptions, true)) { ?>
                                        <option value="permanent"><?php echo iN_HelpSecure($LANG['community_moderation_timeout_permanent'] ?? 'Permanent'); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if ($canManageMembers || $canManageReshare || $canManageTimeout || $canManagePosts || $canManageComments) { ?>
                        <div class="community_moderator_form_actions">
                            <button type="submit" class="i_nex_btn communityMemberSubmit transition">
                                <?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?>
                            </button>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>
</div>
