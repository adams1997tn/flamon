<?php
$communityCategories = $iN->iN_GetCommunityCategories();
$communities = $iN->iN_GetCommunitiesForAdmin(200);
$communityCreationSettings = $iN->iN_GetCommunityCreationSettings();
$communityCreationPolicy = $communityCreationSettings['policy'] ?? 'paid_only';
$communityFreeLimit = isset($communityCreationSettings['free_limit']) ? (int)$communityCreationSettings['free_limit'] : 10;
$selectedCommunityId = isset($_GET['community_id']) ? (int)$iN->iN_Secure($_GET['community_id']) : 0;
$selectedCommunity = $selectedCommunityId > 0 ? $iN->iN_GetCommunityById($selectedCommunityId) : null;
$selectedCommunityOwner = null;
if ($selectedCommunity) {
    $selectedCommunityOwner = $iN->iN_GetUserDetails((int)($selectedCommunity['owner_user_id'] ?? 0));
}
$adminRequestUrl = iN_HelpSecure($base_url) . 'admin/' . iN_HelpSecure($adminTheme) . '/request/request.php';
?>

<div class="i_contents_container community-management-page general_top">
    <div class="i_general_white_board border_one column flex_ tabing__justify community_admin_section">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['community_categories_title'] ?? 'Community Categories'); ?>
        </div>
        <form class="community_admin_form community_admin_ajax" method="post" action="<?php echo $adminRequestUrl; ?>">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="f" value="communityCategoriesUpdate">
            <div class="community_admin_list">
                <?php foreach ($communityCategories as $categoryRow) {
                    $categoryKey = $categoryRow['category_key'] ?? '';
                    if ($categoryKey === '') {
                        continue;
                    }
                    $label = $LANG[$categoryKey] ?? $categoryKey;
                    $descKey = $categoryKey . '_desc';
                    $description = $LANG[$descKey] ?? $categoryKey;
                    $isEnabled = (string)($categoryRow['status'] ?? '0') === '1';
                ?>
                    <div class="community_admin_row">
                        <div class="community_admin_meta">
                            <strong><?php echo iN_HelpSecure($label); ?></strong>
                            <span><?php echo iN_HelpSecure($description); ?></span>
                        </div>
                        <div class="community_admin_actions">
                            <label class="el-switch el-switch-yellow">
                                <input type="checkbox" name="community_categories[]" value="<?php echo iN_HelpSecure($categoryKey); ?>" <?php echo $isEnabled ? 'checked' : ''; ?>>
                                <span class="el-switch-style"></span>
                            </label>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="i_general_row_box flex_">
                <button type="submit" class="i_nex_btn"><?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?></button>
            </div>
        </form>
    </div>

    <div class="i_general_white_board border_one column flex_ tabing__justify community_admin_section">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['community_creation_settings_title'] ?? 'Community Creation Settings'); ?>
        </div>
        <form class="community_admin_form community_admin_ajax" method="post" action="<?php echo $adminRequestUrl; ?>">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="f" value="communityCreationSettingsUpdate">
            <div class="i_general_row_box column flex_">
                <label class="form_label"><?php echo iN_HelpSecure($LANG['community_creation_policy_label'] ?? 'Creation policy'); ?></label>
                <select name="community_creation_policy" class="i_input">
                    <option value="paid_only" <?php echo $communityCreationPolicy === 'paid_only' ? 'selected' : ''; ?>>
                        <?php echo iN_HelpSecure($LANG['community_creation_policy_paid_only'] ?? 'Paid only'); ?>
                    </option>
                    <option value="free_only" <?php echo $communityCreationPolicy === 'free_only' ? 'selected' : ''; ?>>
                        <?php echo iN_HelpSecure($LANG['community_creation_policy_free_only'] ?? 'Free only'); ?>
                    </option>
                    <option value="both" <?php echo $communityCreationPolicy === 'both' ? 'selected' : ''; ?>>
                        <?php echo iN_HelpSecure($LANG['community_creation_policy_both'] ?? 'Free and paid'); ?>
                    </option>
                </select>
            </div>
            <div class="i_general_row_box column flex_">
                <label class="form_label"><?php echo iN_HelpSecure($LANG['community_free_member_limit_label'] ?? 'Free member limit'); ?></label>
                <input type="number" name="community_free_member_limit" class="i_input" min="1" value="<?php echo (int)$communityFreeLimit; ?>" required>
                <div class="i_chck_text admin_note_t">
                    <?php echo iN_HelpSecure($LANG['community_free_member_limit_note'] ?? 'Applies to free communities only.'); ?>
                </div>
            </div>
            <div class="i_general_row_box flex_">
                <button type="submit" class="i_nex_btn"><?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?></button>
            </div>
        </form>
    </div>

    <?php if ($selectedCommunity) {
        $communityTitle = $selectedCommunity['title'] ?? '';
        $communityDescription = $selectedCommunity['description'] ?? '';
        $communityCover = $selectedCommunity['cover_image'] ?? '';
        $communityAvatar = $selectedCommunity['avatar_image'] ?? '';
        $communityCoverUrl = '';
        $communityAvatarUrl = '';
        if (!empty($communityCover)) {
            $communityCoverUrl = function_exists('storage_public_url') ? storage_public_url($communityCover) : ($base_url . $communityCover);
        }
        if (!empty($communityAvatar)) {
            $communityAvatarUrl = function_exists('storage_public_url') ? storage_public_url($communityAvatar) : ($base_url . $communityAvatar);
        }
        $ownerLabel = '';
        if ($selectedCommunityOwner) {
            $ownerName = $selectedCommunityOwner['i_user_fullname'] ?: $selectedCommunityOwner['i_username'];
            $ownerLabel = $ownerName ? ' (' . $ownerName . ')' : '';
        }
        $moderationEnabled = (string)($selectedCommunity['moderation_enabled'] ?? '0');
        $timeoutEnabled = (string)($selectedCommunity['moderation_timeout_enabled'] ?? '0');
        $allTimeoutOptions = ['1', '3', '7', '30', 'permanent'];
        $rawTimeoutOptions = trim((string)($selectedCommunity['moderation_timeout_options'] ?? ''));
        if ($rawTimeoutOptions !== '') {
            $selectedTimeoutOptions = array_filter(array_map('trim', explode(',', $rawTimeoutOptions)));
            $selectedTimeoutOptions = array_values(array_intersect($allTimeoutOptions, $selectedTimeoutOptions));
        } else {
            $selectedTimeoutOptions = $allTimeoutOptions;
        }
        $selectedTimeoutMap = array_fill_keys($selectedTimeoutOptions, true);
        $communityModerators = $iN->iN_GetCommunityModerators($selectedCommunityId, 50);
    ?>
        <div class="i_general_white_board border_one column flex_ tabing__justify community_admin_section">
            <div class="i_general_title_box">
                <?php echo iN_HelpSecure($LANG['community_edit_title'] ?? 'Edit Community'); ?>
                <?php echo iN_HelpSecure($ownerLabel); ?>
            </div>
            <form class="community_admin_form community_admin_ajax" method="post" enctype="multipart/form-data" action="<?php echo $adminRequestUrl; ?>">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="f" value="communityAdminEdit">
                <input type="hidden" name="community_id" value="<?php echo (int)$selectedCommunityId; ?>">
                <div class="i_general_row_box column flex_">
                    <label class="form_label"><?php echo iN_HelpSecure($LANG['community_name']); ?></label>
                    <input type="text" name="community_title" class="i_input" value="<?php echo iN_HelpSecure($communityTitle); ?>" required>
                </div>
                <div class="i_general_row_box column flex_">
                    <label class="form_label"><?php echo iN_HelpSecure($LANG['community_description']); ?></label>
                    <textarea name="community_description" class="i_textarea"><?php echo iN_HelpSecure($communityDescription); ?></textarea>
                </div>
                <div class="i_general_row_box column flex_">
                    <label class="form_label"><?php echo iN_HelpSecure($LANG['community_avatar_image'] ?? 'Avatar Image'); ?></label>
                    <input type="file" name="community_avatar" class="i_input" accept="image/*">
                    <?php if ($communityAvatarUrl !== '') { ?>
                        <img src="<?php echo iN_HelpSecure($communityAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>" class="admin_preview_image">
                    <?php } ?>
                </div>
                <div class="i_general_row_box column flex_">
                    <label class="form_label"><?php echo iN_HelpSecure($LANG['community_cover_image']); ?></label>
                    <input type="file" name="community_cover" class="i_input" accept="image/*">
                    <?php if ($communityCoverUrl !== '') { ?>
                        <img src="<?php echo iN_HelpSecure($communityCoverUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>" class="admin_preview_image">
                    <?php } ?>
                </div>
                <div class="i_general_row_box flex_">
                    <button type="submit" class="i_nex_btn"><?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?></button>
                    <a class="i_nex_btn" href="<?php echo iN_HelpSecure($base_url); ?>admin/community_management"><?php echo iN_HelpSecure($LANG['back_to_list'] ?? 'Back to list'); ?></a>
                </div>
            </form>
        </div>
        <div class="i_general_white_board border_one column flex_ tabing__justify community_admin_section">
            <div class="i_general_title_box">
                <?php echo iN_HelpSecure($LANG['community_moderation_settings_title'] ?? 'Moderation Settings'); ?>
            </div>
            <form class="community_admin_form community_admin_ajax" method="post" action="<?php echo $adminRequestUrl; ?>">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="f" value="communityAdminModerationSettingsUpdate">
                <input type="hidden" name="community_id" value="<?php echo (int)$selectedCommunityId; ?>">
                <div class="community_admin_list">
                    <div class="community_admin_row">
                        <div class="community_admin_meta">
                            <strong><?php echo iN_HelpSecure($LANG['community_moderation_enable_label'] ?? 'Enable moderation'); ?></strong>
                        </div>
                        <div class="community_admin_actions">
                            <label class="el-switch el-switch-yellow">
                                <input type="checkbox" name="moderation_enabled" value="1" <?php echo $moderationEnabled === '1' ? 'checked' : ''; ?>>
                                <span class="el-switch-style"></span>
                            </label>
                        </div>
                    </div>
                    <div class="community_admin_row">
                        <div class="community_admin_meta">
                            <strong><?php echo iN_HelpSecure($LANG['community_moderation_timeout_enable_label'] ?? 'Enable view timeouts'); ?></strong>
                        </div>
                        <div class="community_admin_actions">
                            <label class="el-switch el-switch-yellow">
                                <input type="checkbox" name="moderation_timeout_enabled" value="1" <?php echo $timeoutEnabled === '1' ? 'checked' : ''; ?>>
                                <span class="el-switch-style"></span>
                            </label>
                        </div>
                    </div>
                    <div class="community_admin_row">
                        <div class="community_admin_meta">
                            <strong><?php echo iN_HelpSecure($LANG['community_moderation_timeout_options_label'] ?? 'Allowed timeouts'); ?></strong>
                        </div>
                        <div class="community_admin_actions community_admin_permissions">
                            <label class="community_permission_item">
                                <input type="checkbox" name="timeout_options[]" value="1" <?php echo isset($selectedTimeoutMap['1']) ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_moderation_timeout_1d'] ?? '1 day'); ?></span>
                            </label>
                            <label class="community_permission_item">
                                <input type="checkbox" name="timeout_options[]" value="3" <?php echo isset($selectedTimeoutMap['3']) ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_moderation_timeout_3d'] ?? '3 days'); ?></span>
                            </label>
                            <label class="community_permission_item">
                                <input type="checkbox" name="timeout_options[]" value="7" <?php echo isset($selectedTimeoutMap['7']) ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_moderation_timeout_7d'] ?? '7 days'); ?></span>
                            </label>
                            <label class="community_permission_item">
                                <input type="checkbox" name="timeout_options[]" value="30" <?php echo isset($selectedTimeoutMap['30']) ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_moderation_timeout_30d'] ?? '30 days'); ?></span>
                            </label>
                            <label class="community_permission_item">
                                <input type="checkbox" name="timeout_options[]" value="permanent" <?php echo isset($selectedTimeoutMap['permanent']) ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_moderation_timeout_permanent'] ?? 'Permanent'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="i_general_row_box flex_">
                    <button type="submit" class="i_nex_btn"><?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?></button>
                </div>
            </form>
        </div>
        <div class="i_general_white_board border_one column flex_ tabing__justify community_admin_section">
            <div class="i_general_title_box">
                <?php echo iN_HelpSecure($LANG['community_moderators_title'] ?? 'Moderators'); ?>
            </div>
            <form class="community_admin_form community_admin_ajax" method="post" action="<?php echo $adminRequestUrl; ?>">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="f" value="communityAdminModeratorAdd">
                <input type="hidden" name="community_id" value="<?php echo (int)$selectedCommunityId; ?>">
                <div class="community_admin_row">
                    <div class="community_admin_meta">
                        <label class="form_label"><?php echo iN_HelpSecure($LANG['community_moderator_user_label'] ?? 'Username or ID'); ?></label>
                        <input type="text" name="moderator_user" class="i_input" placeholder="<?php echo iN_HelpSecure($LANG['community_moderator_user_placeholder'] ?? 'username'); ?>" required>
                    </div>
                    <div class="community_admin_actions community_admin_permissions">
                        <label class="community_permission_item">
                            <input type="checkbox" name="perm_manage_members" value="1">
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_members'] ?? 'Member management'); ?></span>
                        </label>
                        <label class="community_permission_item">
                            <input type="checkbox" name="perm_manage_posts" value="1">
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_posts'] ?? 'Posts'); ?></span>
                        </label>
                        <label class="community_permission_item">
                            <input type="checkbox" name="perm_manage_comments" value="1">
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_comments'] ?? 'Comments'); ?></span>
                        </label>
                        <label class="community_permission_item">
                            <input type="checkbox" name="perm_manage_reshare" value="1">
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_reshare'] ?? 'Reshare'); ?></span>
                        </label>
                        <label class="community_permission_item">
                            <input type="checkbox" name="perm_manage_view_timeout" value="1">
                            <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_timeout'] ?? 'View timeout'); ?></span>
                        </label>
                        <button type="submit" class="i_nex_btn"><?php echo iN_HelpSecure($LANG['community_moderator_add_btn'] ?? 'Add moderator'); ?></button>
                    </div>
                </div>
            </form>
            <div class="community_admin_list">
                <?php if (!empty($communityModerators)) { ?>
                    <?php foreach ($communityModerators as $moderatorRow) {
                        $moderatorID = (int)($moderatorRow['user_id'] ?? 0);
                        $moderatorName = $moderatorRow['i_user_fullname'] ?: ($moderatorRow['i_username'] ?? '');
                    ?>
                        <form class="community_admin_form community_admin_ajax" method="post" action="<?php echo $adminRequestUrl; ?>">
                            <?php echo csrf_token_field(); ?>
                            <input type="hidden" name="f" value="communityAdminModeratorUpdate">
                            <input type="hidden" name="community_id" value="<?php echo (int)$selectedCommunityId; ?>">
                            <input type="hidden" name="moderator_user_id" value="<?php echo (int)$moderatorID; ?>">
                            <div class="community_admin_row">
                                <div class="community_admin_meta">
                                    <strong><?php echo iN_HelpSecure($moderatorName); ?></strong>
                                    <span>#<?php echo (int)$moderatorID; ?></span>
                                </div>
                                <div class="community_admin_actions community_admin_permissions">
                                    <label class="community_permission_item">
                                        <input type="checkbox" name="perm_manage_members" value="1" <?php echo (string)($moderatorRow['can_manage_members'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_members'] ?? 'Member management'); ?></span>
                                    </label>
                                    <label class="community_permission_item">
                                        <input type="checkbox" name="perm_manage_posts" value="1" <?php echo (string)($moderatorRow['can_manage_posts'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_posts'] ?? 'Posts'); ?></span>
                                    </label>
                                    <label class="community_permission_item">
                                        <input type="checkbox" name="perm_manage_comments" value="1" <?php echo (string)($moderatorRow['can_manage_comments'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_comments'] ?? 'Comments'); ?></span>
                                    </label>
                                    <label class="community_permission_item">
                                        <input type="checkbox" name="perm_manage_reshare" value="1" <?php echo (string)($moderatorRow['can_manage_reshare'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_reshare'] ?? 'Reshare'); ?></span>
                                    </label>
                                    <label class="community_permission_item">
                                        <input type="checkbox" name="perm_manage_view_timeout" value="1" <?php echo (string)($moderatorRow['can_manage_view_timeout'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span><?php echo iN_HelpSecure($LANG['community_moderator_permission_timeout'] ?? 'View timeout'); ?></span>
                                    </label>
                                    <button type="submit" class="i_nex_btn" name="moderator_action" value="update"><?php echo iN_HelpSecure($LANG['community_moderator_update_btn'] ?? 'Update'); ?></button>
                                    <button type="submit" class="i_btn_unsubscribe" name="moderator_action" value="remove"><?php echo iN_HelpSecure($LANG['community_moderator_remove_btn'] ?? 'Remove'); ?></button>
                                </div>
                            </div>
                        </form>
                    <?php } ?>
                <?php } else { ?>
                    <div class="community_admin_row">
                        <?php echo iN_HelpSecure($LANG['community_moderator_empty'] ?? 'No moderators assigned.'); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="i_general_white_board border_one column flex_ tabing__justify community_admin_section">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['community_management_title'] ?? 'Community Management'); ?>
        </div>
        <div class="community_admin_list">
            <?php if (!empty($communities)) { ?>
	                <?php foreach ($communities as $communityRow) {
	                    $communityID = (int)($communityRow['id'] ?? 0);
	                    $communityTitle = $communityRow['title'] ?? '';
	                    $communityOwnerName = $communityRow['i_user_fullname'] ?: ($communityRow['i_username'] ?? '');
	                    $postingEnabled = (string)($communityRow['posting_enabled'] ?? '1');
	                    $subscriptionRequired = (string)($communityRow['subscription_required'] ?? '1');
	                    $monthlyPrice = isset($communityRow['monthly_price']) ? (float)$communityRow['monthly_price'] : 0;
	                    $subscriptionAmountLabel = $subscriptionRequired === '1'
	                        ? formatCurrency($monthlyPrice, $defaultCurrency)
	                        : $LANG['community_subscription_not_required'];
	                ?>
	                    <div class="community_admin_row">
	                        <div class="community_admin_meta">
	                            <strong><?php echo iN_HelpSecure($communityTitle); ?></strong>
	                            <span><?php echo iN_HelpSecure($communityOwnerName); ?></span>
	                            <span><?php echo iN_HelpSecure($LANG['amount']); ?>: <?php echo iN_HelpSecure($subscriptionAmountLabel); ?></span>
	                        </div>
                        <div class="community_admin_actions">
                            <form class="community_admin_form community_admin_ajax" method="post" action="<?php echo $adminRequestUrl; ?>">
                                <?php echo csrf_token_field(); ?>
                                <input type="hidden" name="f" value="communityAdminSettingsUpdate">
                                <input type="hidden" name="community_id" value="<?php echo (int)$communityID; ?>">
                                <select name="posting_enabled" class="i_input">
                                    <option value="1" <?php echo $postingEnabled === '1' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_posting_enabled'] ?? 'Posting enabled'); ?></option>
                                    <option value="0" <?php echo $postingEnabled === '0' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_posting_disabled'] ?? 'Posting disabled'); ?></option>
                                </select>
                                <select name="subscription_required" class="i_input">
                                    <option value="1" <?php echo $subscriptionRequired === '1' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_subscription_required'] ?? 'Subscription required'); ?></option>
                                    <option value="0" <?php echo $subscriptionRequired === '0' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_subscription_not_required'] ?? 'Subscription not required'); ?></option>
                                </select>
                                <button type="submit" class="i_nex_btn"><?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?></button>
                                <a class="i_nex_btn" href="<?php echo iN_HelpSecure($base_url); ?>admin/community_management?community_id=<?php echo (int)$communityID; ?>">
                                    <?php echo iN_HelpSecure($LANG['community_edit'] ?? 'Edit'); ?>
                                </a>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="community_admin_row">
                    <?php echo iN_HelpSecure($LANG['community_empty_admin'] ?? 'No communities found.'); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
