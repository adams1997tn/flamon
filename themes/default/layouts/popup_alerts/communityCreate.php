<?php
$subscriptionTypeValue = (string)$subscriptionType;
$planAmountLabel = $subscriptionTypeValue === '2'
    ? iN_HelpSecure($communityPlanAmount) . html_entity_decode($iN->iN_SelectedMenuIcon('40'))
    : iN_HelpSecure(formatCurrency($communityPlanAmount, $defaultCurrency));
$communityCategories = isset($communityCategories) && is_array($communityCategories) ? $communityCategories : [];
$communityCreationSettings = isset($communityCreationSettings) && is_array($communityCreationSettings) ? $communityCreationSettings : [];
$communityCreationPolicy = isset($communityCreationSettings['policy']) ? (string)$communityCreationSettings['policy'] : 'paid_only';
$communityFreeMemberLimit = isset($communityCreationSettings['free_limit']) ? (int)$communityCreationSettings['free_limit'] : 10;
if ($communityFreeMemberLimit < 1) {
    $communityFreeMemberLimit = 1;
}
$defaultAccessType = $communityCreationPolicy === 'free_only' ? 'free' : 'paid';
$freeLimitNote = str_replace(
    '{limit}',
    (string)$communityFreeMemberLimit,
    $LANG['community_free_limit_note'] ?? 'Free communities are limited to {limit} members.'
);
?>
<div class="i_modal_bg_in community_modal" role="dialog" aria-modal="true" aria-labelledby="communityCreateTitle">
    <div class="i_modal_in_in i_sf_box">
        <div class="i_modal_content">
            <div class="popClose transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['close'] ?? 'Close'); ?>">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
            </div>
            <div class="community_modal_header">
                <div class="community_modal_title" id="communityCreateTitle">
                    <?php echo iN_HelpSecure($LANG['create_community']); ?>
                </div>
                <div class="community_modal_note">
                    <?php echo iN_HelpSecure($LANG['community_create_note']); ?>
                </div>
            </div>

            <?php if (!$isCreator) { ?>
                <div class="community_modal_notice">
                    <?php echo iN_HelpSecure($LANG['community_creator_only']); ?>
                </div>
            <?php } else if (!$hasCommunityPlan) { ?>
                <div class="community_plan_gate">
                    <div class="community_modal_notice">
                        <?php echo iN_HelpSecure($LANG['community_plan_required']); ?>
                    </div>
                    <div class="community_plan_price">
                        <?php echo iN_HelpSecure($LANG['community_plan_price']); ?>: <?php echo $planAmountLabel; ?>
                    </div>
                    <button type="button" class="i_nex_btn communityPlanModal transition">
                        <?php echo iN_HelpSecure($LANG['choose_a_plan']); ?>
                    </button>
                </div>
            <?php } else { ?>
                <form id="communityCreateForm" class="community_form" method="post" enctype="multipart/form-data" data-free-limit="<?php echo (int)$communityFreeMemberLimit; ?>">
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="f" value="communityCreate">
                    <div class="community_form_group">
                        <label class="form_label" for="community_title">
                            <?php echo iN_HelpSecure($LANG['community_name']); ?>
                        </label>
                        <input type="text" id="community_title" name="community_title" class="inora_user_input" maxlength="160" required>
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
                                <option value="<?php echo iN_HelpSecure($categoryKey); ?>"><?php echo iN_HelpSecure($label); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="community_form_group">
                        <label class="form_label" for="community_description">
                            <?php echo iN_HelpSecure($LANG['community_description']); ?>
                        </label>
                        <textarea id="community_description" name="community_description" class="inora_user_input" rows="4" maxlength="2000"></textarea>
                    </div>
                    <div class="community_form_group community_access_group">
                        <label class="form_label">
                            <?php echo iN_HelpSecure($LANG['community_access_type'] ?? 'Access Type'); ?>
                        </label>
                        <?php if ($communityCreationPolicy === 'both') { ?>
                            <div class="community_access_options">
                                <label class="community_access_option">
                                    <input type="radio" name="community_access_type" value="paid" <?php echo $defaultAccessType === 'paid' ? 'checked' : ''; ?>>
                                    <span><?php echo iN_HelpSecure($LANG['community_access_paid'] ?? 'Paid'); ?></span>
                                </label>
                                <label class="community_access_option">
                                    <input type="radio" name="community_access_type" value="free" <?php echo $defaultAccessType === 'free' ? 'checked' : ''; ?>>
                                    <span><?php echo iN_HelpSecure($LANG['community_access_free'] ?? 'Free'); ?></span>
                                </label>
                            </div>
                        <?php } else { ?>
                            <input type="hidden" name="community_access_type" value="<?php echo iN_HelpSecure($defaultAccessType); ?>">
                            <div class="community_access_static">
                                <span class="community_access_badge">
                                    <?php echo iN_HelpSecure($defaultAccessType === 'free' ? ($LANG['community_access_free'] ?? 'Free') : ($LANG['community_access_paid'] ?? 'Paid')); ?>
                                </span>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="community_form_group community_price_group_wrap<?php echo $defaultAccessType === 'free' ? ' is-hidden' : ''; ?>">
                        <label class="form_label" for="community_monthly_price">
                            <?php echo iN_HelpSecure($LANG['community_monthly_price']); ?>
                        </label>
                        <div class="community_price_group">
                            <span class="community_currency">
                                <?php echo $subscriptionTypeValue === '2' ? html_entity_decode($iN->iN_SelectedMenuIcon('40')) : iN_HelpSecure($currencys[$defaultCurrency]); ?>
                            </span>
                            <input type="text" id="community_monthly_price" name="community_monthly_price" class="inora_user_input" inputmode="decimal" required>
                        </div>
                    </div>
                    <div class="community_form_group">
                        <label class="form_label" for="community_member_limit">
                            <?php echo iN_HelpSecure($LANG['community_member_limit']); ?>
                        </label>
                        <select id="community_member_limit" name="community_member_limit" class="inora_user_input">
                            <option value=""><?php echo iN_HelpSecure($LANG['community_unlimited']); ?></option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                        <div class="community_free_limit_note<?php echo $defaultAccessType === 'free' ? '' : ' is-hidden'; ?>" data-free-limit-note>
                            <?php echo iN_HelpSecure($freeLimitNote); ?>
                        </div>
                    </div>
                    <div class="community_form_group">
                        <label class="form_label" for="community_cover">
                            <?php echo iN_HelpSecure($LANG['community_cover_image']); ?>
                        </label>
                        <input type="file" id="community_cover" name="community_cover" class="community_file_input" accept="image/*">
                    </div>
                    <div class="community_form_message" aria-live="polite"></div>
                    <div class="community_form_actions">
                        <button type="submit" class="i_nex_btn communityCreateSubmit transition">
                            <?php echo iN_HelpSecure($LANG['create_community']); ?>
                        </button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</div>
