<div class="i_modal_bg_in community_moderation_modal" role="dialog" aria-modal="true" aria-labelledby="communityModerationAddTitle">
    <div class="i_modal_in_in community_moderation_modal_inner">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="communityModerationAddTitle">
                <?php echo iN_HelpSecure($LANG['community_moderation_add_title'] ?? 'Add moderation user'); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="community_moderation_body">
                <div class="community_moderation_search_wrapper">
                    <input type="text"
                           class="community_moderation_search_input"
                           placeholder="<?php echo iN_HelpSecure($LANG['community_moderation_search_placeholder'] ?? 'Search'); ?>"
                           aria-label="<?php echo iN_HelpSecure($LANG['community_moderation_search_placeholder'] ?? 'Search'); ?>">
                </div>
                <div class="community_moderation_lists">
                    <div class="community_moderation_user_list community_moderation_default_list" data-community="<?php echo (int)$communityID; ?>">
                        <?php if (!empty($displayUsers)) { ?>
                            <?php foreach ($displayUsers as $candidate) {
                                $candidateUserId = (int)($candidate['user_id'] ?? $candidate['iuid'] ?? 0);
                                if ($candidateUserId <= 0) {
                                    continue;
                                }
                                $candidateUserName = $candidate['i_user_fullname'] ?: ($candidate['i_username'] ?? '');
                                $candidateUserAvatarUrl = $iN->iN_UserAvatar($candidateUserId, $base_url);
                                $isAdded = isset($moderationUserIds[$candidateUserId]);
                                include __DIR__ . '/../community/communityModerationUserCard.php';
                            } ?>
                        <?php } else { ?>
                            <div class="community_moderation_empty">
                                <?php echo iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.'); ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="community_moderation_user_list community_moderation_search_results"
                         data-community="<?php echo (int)$communityID; ?>"
                         data-empty-text="<?php echo iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.'); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
