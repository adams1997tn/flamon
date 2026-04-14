<div class="i_modal_bg_in community_moderator_modal" role="dialog" aria-modal="true" aria-labelledby="communityModeratorAddTitle" data-added-text="<?php echo iN_HelpSecure($LANG['community_moderation_added'] ?? 'Added'); ?>">
    <div class="i_modal_in_in community_moderation_modal_inner">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="communityModeratorAddTitle">
                <?php echo iN_HelpSecure($LANG['community_moderator_add_btn'] ?? 'Add moderator'); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="community_moderation_body">
                <div class="community_moderation_search_wrapper">
                    <input type="text"
                           class="community_moderator_search_input"
                           placeholder="<?php echo iN_HelpSecure($LANG['community_moderation_search_placeholder'] ?? 'Search'); ?>"
                           aria-label="<?php echo iN_HelpSecure($LANG['community_moderation_search_placeholder'] ?? 'Search'); ?>">
                </div>
                <div class="community_moderation_lists">
                    <div class="community_moderation_user_list community_moderator_default_list" data-community="<?php echo (int)$communityID; ?>">
                        <?php if (!empty($displayUsers)) { ?>
                            <?php foreach ($displayUsers as $candidate) {
                                $candidateUserId = (int)($candidate['user_id'] ?? $candidate['iuid'] ?? 0);
                                if ($candidateUserId <= 0 || $candidateUserId === (int)($communityData['owner_user_id'] ?? 0)) {
                                    continue;
                                }
                                $candidateUserName = $candidate['i_user_fullname'] ?: ($candidate['i_username'] ?? '');
                                $candidateUserAvatarUrl = $iN->iN_UserAvatar($candidateUserId, $base_url);
                                $isAdded = isset($moderatorUserIds[$candidateUserId]);
                                include __DIR__ . '/../community/communityModeratorUserCard.php';
                            } ?>
                        <?php } else { ?>
                            <div class="community_moderation_empty">
                                <?php echo iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.'); ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="community_moderation_user_list community_moderation_search_results community_moderator_search_results"
                         data-community="<?php echo (int)$communityID; ?>"
                         data-empty-text="<?php echo iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.'); ?>">
                    </div>
                </div>
                <div class="community_moderator_options_modal" aria-hidden="true">
                    <div class="community_moderator_options_panel" role="dialog" aria-modal="true">
                        <div class="community_moderator_options_header">
                            <div class="community_moderator_options_title">
                                <?php echo iN_HelpSecure($LANG['community_moderator_add_btn'] ?? 'Add moderator'); ?>
                            </div>
                            <button type="button" class="community_moderator_options_close" aria-label="Close">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                            </button>
                        </div>
                        <form class="community_moderator_form community_moderator_assign_form" method="post" data-error-text="<?php echo iN_HelpSecure($LANG['community_moderation_add_failed'] ?? 'Unable to add user.'); ?>">
                            <input type="hidden" name="f" value="communityModeratorAdd">
                            <input type="hidden" name="community_id" value="<?php echo (int)$communityID; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">
                            <input type="hidden" name="moderator_user_ids" value="">
                            <div class="community_moderator_options">
                                <label class="community_moderator_option">
                                    <input type="checkbox" name="perm_manage_members" value="1">
                                    <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_members'] ?? 'Member management'); ?></span>
                                </label>
                                <label class="community_moderator_option">
                                    <input type="checkbox" name="perm_manage_posts" value="1">
                                    <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_posts'] ?? 'Posts'); ?></span>
                                </label>
                                <label class="community_moderator_option">
                                    <input type="checkbox" name="perm_manage_comments" value="1">
                                    <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_comments'] ?? 'Comments'); ?></span>
                                </label>
                                <label class="community_moderator_option">
                                    <input type="checkbox" name="perm_manage_reshare" value="1">
                                    <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_reshare'] ?? 'Reshare'); ?></span>
                                </label>
                                <label class="community_moderator_option">
                                    <input type="checkbox" name="perm_manage_view_timeout" value="1">
                                    <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_timeout'] ?? 'View timeout'); ?></span>
                                </label>
                                <label class="community_moderator_option">
                                    <input type="checkbox" name="perm_manage_media" value="1">
                                    <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_media'] ?? 'Avatar/cover'); ?></span>
                                </label>
                            </div>
                            <div class="community_moderator_options_note">
                                <?php echo iN_HelpSecure($LANG['community_moderator_options_note'] ?? 'Choose what this moderator can manage in the community (members, posts, comments, reshares, view timeouts, avatar/cover).'); ?>
                            </div>
                            <div class="community_moderator_form_actions">
                                <button type="submit" class="i_nex_btn community_moderator_submit_btn">
                                    <?php echo iN_HelpSecure($LANG['ok'] ?? 'Ok'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
