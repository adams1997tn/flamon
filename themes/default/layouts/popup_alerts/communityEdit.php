<?php
$communityCategories = isset($communityCategories) && is_array($communityCategories) ? $communityCategories : [];
$communityTitle = isset($communityTitle) ? $communityTitle : '';
$communityCategory = isset($communityCategory) ? $communityCategory : '';
$communityDescription = isset($communityDescription) ? $communityDescription : '';
$communityLimit = isset($communityLimit) ? (int)$communityLimit : 0;
$communityPrice = isset($communityPrice) ? $communityPrice : 0;
$subscriptionTypeValue = isset($subscriptionTypeValue) ? (string)$subscriptionTypeValue : (string)$subscriptionType;
$communityCreationSettings = isset($communityCreationSettings) && is_array($communityCreationSettings) ? $communityCreationSettings : [];
$communityCreationPolicy = isset($communityCreationSettings['policy']) ? (string)$communityCreationSettings['policy'] : 'paid_only';
$communityFreeMemberLimit = isset($communityCreationSettings['free_limit']) ? (int)$communityCreationSettings['free_limit'] : 10;
if ($communityFreeMemberLimit < 1) {
    $communityFreeMemberLimit = 1;
}
$communitySubscriptionRequired = isset($communitySubscriptionRequired) ? (string)$communitySubscriptionRequired : '1';
$communityPostingPolicy = isset($communityPostingPolicy) ? (string)$communityPostingPolicy : 'owner_admin';
if (!in_array($communityPostingPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
    $communityPostingPolicy = 'owner_admin';
}
$communityCommentPolicy = isset($communityCommentPolicy) ? (string)$communityCommentPolicy : 'members';
if (!in_array($communityCommentPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
    $communityCommentPolicy = 'members';
}
$communityAccessPolicy = isset($communityAccessPolicy) ? (string)$communityAccessPolicy : 'members_only';
if (!in_array($communityAccessPolicy, ['members_only', 'public'], true)) {
    $communityAccessPolicy = 'members_only';
}
$communityAccessType = $communitySubscriptionRequired === '0' ? 'free' : 'paid';
$selectedAccessType = $communityCreationPolicy === 'free_only' ? 'free' : ($communityCreationPolicy === 'paid_only' ? 'paid' : $communityAccessType);
$freeLimitNote = str_replace(
    '{limit}',
    (string)$communityFreeMemberLimit,
    $LANG['community_free_limit_note'] ?? 'Free communities are limited to {limit} members.'
);
?>
<div class="i_modal_bg_in community_modal" role="dialog" aria-modal="true" aria-labelledby="communityEditTitle">
    <div class="i_modal_in_in i_sf_box">
        <div class="i_modal_content">
            <div class="popClose transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['close'] ?? 'Close'); ?>">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
            </div>
            <div class="community_modal_header">
                <div class="community_modal_title" id="communityEditTitle">
                    <?php echo iN_HelpSecure($LANG['community_edit_title'] ?? 'Edit Community'); ?>
                </div>
            </div>

            <form id="communityManageForm" class="community_form" method="post" enctype="multipart/form-data" data-free-limit="<?php echo (int)$communityFreeMemberLimit; ?>">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="f" value="communityUpdate">
                <input type="hidden" name="community_id" value="<?php echo (int)$communityID; ?>">
                <div class="community_form_group">
                    <label class="form_label" for="community_title">
                        <?php echo iN_HelpSecure($LANG['community_name']); ?>
                    </label>
                    <input type="text" id="community_title" name="community_title" class="inora_user_input" maxlength="160" value="<?php echo iN_HelpSecure($communityTitle); ?>" required>
                </div>
                <div class="community_form_group">
                    <label class="form_label" for="community_category">
                        <?php echo iN_HelpSecure($LANG['community_category']); ?>
                    </label>
                    <select id="community_category" name="community_category" class="inora_user_input" required>
                        <option value=""><?php echo iN_HelpSecure($LANG['community_category_select'] ?? 'Select category'); ?></option>
                        <?php foreach ($communityCategories as $categoryRow) {
                            $categoryKey = $categoryRow['category_key'] ?? '';
                            if ($categoryKey === '') {
                                continue;
                            }
                            $label = $LANG[$categoryKey] ?? $categoryKey;
                        ?>
                            <option value="<?php echo iN_HelpSecure($categoryKey); ?>" <?php echo $communityCategory === $categoryKey ? 'selected' : ''; ?>>
                                <?php echo iN_HelpSecure($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="community_form_group">
                    <label class="form_label" for="community_description">
                        <?php echo iN_HelpSecure($LANG['community_description']); ?>
                    </label>
                    <textarea id="community_description" name="community_description" class="inora_user_input" rows="4" maxlength="2000"><?php echo iN_HelpSecure($communityDescription); ?></textarea>
                </div>
                <div class="community_form_group">
                    <label class="form_label" for="community_posting_policy">
                        <?php echo iN_HelpSecure($LANG['community_posting_policy_label'] ?? 'Who can post?'); ?>
                    </label>
                    <select id="community_posting_policy" name="community_posting_policy" class="inora_user_input">
                        <option value="owner_admin" <?php echo $communityPostingPolicy === 'owner_admin' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_posting_policy_owner_admin'] ?? 'Only owner & admin'); ?>
                        </option>
                        <option value="owner_admin_moderators" <?php echo $communityPostingPolicy === 'owner_admin_moderators' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_posting_policy_owner_admin_moderators'] ?? 'Owner, admin & moderators'); ?>
                        </option>
                        <option value="members" <?php echo $communityPostingPolicy === 'members' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_posting_policy_members'] ?? 'All community members'); ?>
                        </option>
                    </select>
                </div>
                <div class="community_form_group">
                    <label class="form_label" for="community_comment_policy">
                        <?php echo iN_HelpSecure($LANG['community_comment_policy_label'] ?? 'Who can comment?'); ?>
                    </label>
                    <select id="community_comment_policy" name="community_comment_policy" class="inora_user_input">
                        <option value="owner_admin" <?php echo $communityCommentPolicy === 'owner_admin' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_comment_policy_owner_admin'] ?? 'Only owner & admin'); ?>
                        </option>
                        <option value="owner_admin_moderators" <?php echo $communityCommentPolicy === 'owner_admin_moderators' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_comment_policy_owner_admin_moderators'] ?? 'Owner, admin & moderators'); ?>
                        </option>
                        <option value="members" <?php echo $communityCommentPolicy === 'members' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_comment_policy_members'] ?? 'All community members'); ?>
                        </option>
                    </select>
                </div>
                <div class="community_form_group community_access_group">
                    <label class="form_label">
                        <?php echo iN_HelpSecure($LANG['community_access_type'] ?? 'Access Type'); ?>
                    </label>
                    <?php if ($communityCreationPolicy === 'both') { ?>
                        <div class="community_access_options">
                            <label class="community_access_option">
                                <input type="radio" name="community_access_type" value="paid" <?php echo $selectedAccessType === 'paid' ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_access_paid'] ?? 'Paid'); ?></span>
                            </label>
                            <label class="community_access_option">
                                <input type="radio" name="community_access_type" value="free" <?php echo $selectedAccessType === 'free' ? 'checked' : ''; ?>>
                                <span><?php echo iN_HelpSecure($LANG['community_access_free'] ?? 'Free'); ?></span>
                            </label>
                        </div>
                    <?php } else { ?>
                        <input type="hidden" name="community_access_type" value="<?php echo iN_HelpSecure($selectedAccessType); ?>">
                        <div class="community_access_static">
                            <span class="community_access_badge">
                                <?php echo iN_HelpSecure($selectedAccessType === 'free' ? ($LANG['community_access_free'] ?? 'Free') : ($LANG['community_access_paid'] ?? 'Paid')); ?>
                            </span>
                        </div>
                    <?php } ?>
                </div>
                <div class="community_form_group">
                    <label class="form_label" for="community_access_policy">
                        <?php echo iN_HelpSecure($LANG['community_access_policy_label'] ?? 'Access policy'); ?>
                    </label>
                    <select id="community_access_policy" name="community_access_policy" class="inora_user_input">
                        <option value="members_only" <?php echo $communityAccessPolicy === 'members_only' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_access_policy_members_only'] ?? 'Membership required'); ?>
                        </option>
                        <option value="public" <?php echo $communityAccessPolicy === 'public' ? 'selected' : ''; ?>>
                            <?php echo iN_HelpSecure($LANG['community_access_policy_public'] ?? 'Everyone can view'); ?>
                        </option>
                    </select>
                </div>
                <div class="community_form_group community_price_group_wrap<?php echo $selectedAccessType === 'free' ? ' is-hidden' : ''; ?>">
                    <label class="form_label" for="community_monthly_price">
                        <?php echo iN_HelpSecure($LANG['community_monthly_price']); ?>
                    </label>
                    <div class="community_price_group">
                        <span class="community_currency">
                            <?php echo $subscriptionTypeValue === '2' ? html_entity_decode($iN->iN_SelectedMenuIcon('40')) : iN_HelpSecure($currencys[$defaultCurrency]); ?>
                        </span>
                        <input type="text" id="community_monthly_price" name="community_monthly_price" class="inora_user_input" inputmode="decimal" value="<?php echo iN_HelpSecure($communityPrice); ?>" required>
                    </div>
                </div>
                <div class="community_form_group">
                    <label class="form_label" for="community_member_limit">
                        <?php echo iN_HelpSecure($LANG['community_member_limit']); ?>
                    </label>
                    <select id="community_member_limit" name="community_member_limit" class="inora_user_input">
                        <option value="" <?php echo $communityLimit === 0 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['community_unlimited']); ?></option>
                        <?php foreach ([50, 100, 200, 500, 1000] as $limitOption) { ?>
                            <option value="<?php echo $limitOption; ?>" <?php echo $communityLimit === $limitOption ? 'selected' : ''; ?>>
                                <?php echo iN_HelpSecure($limitOption); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="community_free_limit_note<?php echo $selectedAccessType === 'free' ? '' : ' is-hidden'; ?>" data-free-limit-note>
                        <?php echo iN_HelpSecure($freeLimitNote); ?>
                    </div>
                </div>
                <div class="community_form_group">
                    <label class="form_label">
                        <?php echo iN_HelpSecure($LANG['community_avatar_image'] ?? 'Avatar Image'); ?> /
                        <?php echo iN_HelpSecure($LANG['community_cover_image'] ?? 'Cover Image'); ?>
                    </label>
                    <button type="button" class="i_nex_btn editCommunityAvatarCover transition" data-community="<?php echo (int)$communityID; ?>">
                        <?php echo iN_HelpSecure($LANG['modify_avatar_cover'] ?? 'Modify Avatar & Cover'); ?>
                    </button>
                </div>
                <div class="community_form_message" aria-live="polite"></div>
                <div class="community_form_actions">
                    <button type="submit" class="i_nex_btn communityManageSubmit transition">
                        <?php echo iN_HelpSecure($LANG['save_changes'] ?? 'Save changes'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
