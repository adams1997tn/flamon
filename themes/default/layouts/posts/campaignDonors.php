<div class="i_modal_bg_in campaign_donors_modal">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['campaign_donors_title'] ?? 'Donors'); ?>
                <div class="shareClose transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?></div>
            </div>
            <div class="campaign_donors_wrap">
                <div class="campaign_donors_summary">
                    <img src="<?php echo iN_HelpSecure($base_url . 'img/donor.png', FILTER_VALIDATE_URL); ?>" class="campaign_donor_cover" alt="<?php echo iN_HelpSecure($LANG['campaign_donors_title'] ?? 'Donors'); ?>">
                    <strong><?php echo iN_HelpSecure($donorsData['total'] ?? 0); ?></strong>
                </div>
                <div class="campaign_donors_search">
                    <div class="donor_search_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('34')); ?></div>
                    <input type="text" class="campaign_donor_filter" placeholder="<?php echo iN_HelpSecure($LANG['search'] ?? 'Search'); ?>">
                </div>
                <?php
                    $donorItems = $donorsData['items'] ?? [];
                    $currencySuffix = $currencySign ? ' ' . iN_HelpSecure($currencySign) : '';
                ?>
                <div class="campaign_donors_grid">
                    <?php if (!empty($donorItems)) {
                        foreach ($donorItems as $donor) {
                        $avatar = $donor['avatar'] ?? '';
                        $fullName = $donor['full_name'] ?? '';
                        $username = $donor['username'] ?? '';
                        $isAnon = isset($donor['anonymous']) && $donor['anonymous'];
                        $displayName = $isAnon ? ($LANG['campaign_anonymous'] ?? 'Anonymous') : ($fullName ?: $username);
                        $amount = $donor['amount'] ?? '0.00';
                        ?>
                        <div class="campaign_donor_tile" data-name="<?php echo iN_HelpSecure(mb_strtolower($isAnon ? 'anonymous' : ($displayName . ' ' . $username))); ?>">
                            <div class="campaign_donor_avatar">
                                <img src="<?php echo iN_HelpSecure($avatar); ?>" alt="<?php echo iN_HelpSecure($displayName); ?>">
                            </div>
                            <div class="campaign_donor_name"><?php echo iN_HelpSecure($displayName); ?></div>
                                <div class="campaign_donor_amount_small"><?php echo iN_HelpSecure($amount . $currencySuffix); ?></div>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="campaign_donor_empty">
                            <?php echo iN_HelpSecure($LANG['campaign_no_donors'] ?? 'No donations yet.'); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
