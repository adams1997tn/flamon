<div class="i_modal_bg_in" role="dialog" aria-modal="true" aria-labelledby="campaignDonateModalTitle">
    <?php
    $walletPointsAmount = isset($userCurrentPoints) ? (float)$userCurrentPoints : 0.0;
    $walletPointsText = number_format($walletPointsAmount, 2, '.', '');
    $walletPointsText = rtrim(rtrim($walletPointsText, '0'), '.');
    if ($walletPointsText === '') {
        $walletPointsText = '0';
    }
    $pointValueAmount = number_format((float)$onePointEqual, 2, '.', '');
    $pointValueAmount = rtrim(rtrim($pointValueAmount, '0'), '.');
    if ($pointValueAmount === '') {
        $pointValueAmount = '0';
    }
    $pointValueCurrency = isset($currencys[$defaultCurrency]) ? (string)$currencys[$defaultCurrency] : '';
    $pointValueDisplay = $pointValueCurrency . $pointValueAmount;
    $pointValueTemplate = $LANG['campaign_point_value_note'] ?? '1 point = {value}';
    $pointValueNote = str_replace('{value}', $pointValueDisplay, $pointValueTemplate);
    ?>
    <div class="i_modal_in_in modal_tip">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="campaignDonateModalTitle">
                <?php echo iN_HelpSecure($LANG['campaign_donate_title'] ?? 'Donate to this campaign'); ?>
                <div class="shareClose transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>

            <div class="i_more_text_wrapper">
                <div class="donate_amount_group i_set_subscription_fee"
                     id="<?php echo iN_HelpSecure($tipingUserID); ?>"
                     data-pid="<?php echo iN_HelpSecure($tipPostID); ?>">
                    <div class="donate_amount_input">
                        <input type="text"
                               class="transition aval border-right-radius"
                               id="tipVal"
                               inputmode="decimal"
                               placeholder="<?php echo iN_HelpSecure($LANG['amount_in_points'] ?? ($LANG['campaign_donate_amount'] ?? 'Donation amount')); ?>"
                               aria-label="<?php echo iN_HelpSecure($LANG['amount_in_points'] ?? ($LANG['campaign_donate_amount'] ?? 'Donation amount')); ?>">
                        <div class="donate_min_hint">
                            <span class="donate_hint_icon">↑↓</span>
                            <?php echo iN_HelpSecure($LANG['campaign_donate_min'] ?? 'Enter a donation amount.'); ?>
                        </div>
                        <div class="donate_point_value_note"><?php echo iN_HelpSecure($pointValueNote); ?></div>
                    </div>
                    <div class="donate_wallet_option flex_ tabing_non_justify">
                        <div class="donate_radio selected" aria-checked="true" role="radio"></div>
                        <div class="donate_wallet_icon">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
                        </div>
                        <div class="donate_wallet_text column">
                            <div class="donate_wallet_title"><?php echo iN_HelpSecure($LANG['wallet'] ?? 'Wallet'); ?></div>
                            <div class="donate_wallet_balance">
                                <?php echo iN_HelpSecure($LANG['available_balance'] ?? 'Available balance'); ?>:
                                <strong><?php echo iN_HelpSecure($walletPointsText . ' ' . ($LANG['points'] ?? 'Points')); ?></strong>
                            </div>
                        </div>
                    </div>
                    <label class="donate_checkbox flex_ tabing_non_justify">
                        <input type="checkbox" id="donate_anonymous" class="donate_anonymous_checkbox">
                        <span><?php echo iN_HelpSecure($LANG['donate_anonymous'] ?? 'Donation anonymous'); ?></span>
                    </label>
                </div>
            </div>

            <div class="i_block_box_footer_container donate_footer_actions">
                <div class="send_tip_btn donate_send_btn"
                     role="button"
                     aria-label="<?php echo iN_HelpSecure($LANG['pay_with_wallet']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
                    <?php echo iN_HelpSecure($LANG['pay_with_wallet']); ?>
                </div>
                <div class="send_tip_methods_btn donate_methods_btn"
                     role="button"
                     aria-label="<?php echo iN_HelpSecure($LANG['choose_payment_method']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
                    <?php echo iN_HelpSecure($LANG['choose_payment_method']); ?>
                </div>
                <div class="donate_cancel_btn no-del transition shareClose" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
        </div>
    </div>
</div>
