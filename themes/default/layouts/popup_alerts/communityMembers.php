<div class="i_modal_bg_in community_members_modal" role="dialog" aria-modal="true" aria-labelledby="communityMembersTitle">
    <div class="i_modal_in_in community_moderation_modal_inner">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="communityMembersTitle">
                <?php echo iN_HelpSecure($LANG['community_members_title'] ?? 'Community members'); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="community_moderation_body">
                <?php if (!empty($communityMembers)) { ?>
                    <div class="community_members_grid">
                        <?php foreach ($communityMembers as $member) {
                            $memberID = (int)($member['user_id'] ?? 0);
                            if ($memberID <= 0) {
                                continue;
                            }
                            $memberName = $member['i_user_fullname'] ?: ($member['i_username'] ?? '');
                            $memberAvatarUrl = $iN->iN_UserAvatar($memberID, $base_url);
                            include __DIR__ . '/../community/communityMemberCard.php';
                        } ?>
                    </div>
                <?php } else { ?>
                    <div class="community_empty_state"><?php echo iN_HelpSecure($LANG['community_members_empty'] ?? 'No members yet.'); ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
