<?php
$epochWebhookUrl = rtrim((string)$base_url, '/') . '/epoch_webhook.php';
$epochWebhookUrlSafe = function_exists('iN_HelpSecure')
    ? iN_HelpSecure($epochWebhookUrl)
    : htmlspecialchars($epochWebhookUrl, ENT_QUOTES, 'UTF-8');
$epochSelectedCurrency = isset($currencys[$epochCurrency]) ? $epochCurrency : (array_key_first($currencys) ?? 'USD');
$epochCurrencyLabel = isset($currencys[$epochSelectedCurrency]) ? $currencys[$epochSelectedCurrency] : $epochSelectedCurrency;
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['epoch_payment']); ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_general_row_box_item flex_ column tabing__justify">
                <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                    <div class="i_chck_text admin_note_t"><?php echo iN_HelpSecure($LANG['epoch_test_mode']); ?></div>
                    <label class="el-switch el-switch-yellow" for="epochmode">
                        <input type="checkbox" name="epochmode" class="chmdPayment" id="epochmode" <?php echo iN_HelpSecure($epochPaymentMode) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                        <span class="el-switch-style"></span>
                    </label>
                    <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_mode']); ?></div>
                    <div class="success_tick tabing flex_ sec_one epochmode">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing__justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_status']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="epoch_status">
                            <input type="checkbox" name="epoch_status" class="chmdPayment" id="epoch_status" <?php echo iN_HelpSecure($epochPaymentStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="success_tick tabing flex_ sec_one epoch_status">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['epoch_status_note']); ?></div>
                </div>
            </div>

                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_postback_enabled']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                            <label class="el-switch el-switch-yellow" for="epoch_postback_enabled">
                                <input type="checkbox" class="chmdPayment" id="epoch_postback_enabled" <?php echo iN_HelpSecure($epochPostbackEnabled) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                                <span class="el-switch-style"></span>
                            </label>
                            <div class="success_tick tabing flex_ sec_one epoch_postback_enabled">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                            </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['epoch_postback_enabled_note']); ?></div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing__justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['beta_mode']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="epoch_beta">
                            <input type="checkbox" class="chmdPayment" id="epoch_beta" data-active-value="1" data-inactive-value="0" <?php echo (isset($epochBeta) && $epochBeta == '1') ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="success_tick tabing flex_ sec_one epoch_beta">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['beta_mode_desc']); ?></div>
                </div>
            </div>

            <form enctype="multipart/form-data" method="post" id="updatePaymentGataway">
                <?php echo csrf_token_field(); ?>

                <div class="i_general_row_box_item flex_ column tabing_non_justify guide_box">
                    <div class="guide_title flex_"><?php echo iN_HelpSecure($LANG['epoch_setup_help_title']); ?></div>
                    <div class="guide_body"><?php echo $LANG['epoch_setup_help_steps']; ?></div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_webhook_url']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" class="i_input flex_" value="<?php echo $epochWebhookUrlSafe; ?>" readonly>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['epoch_webhook_url_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_pi_code']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="epoch_pi_code" class="i_input flex_" value="<?php echo iN_HelpSecure($epochPiCode); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_currency']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_box_limit flex_ column">
                            <div class="i_limit" data-type="fl_limit">
                                <span class="lmt"><?php echo iN_HelpSecure($epochSelectedCurrency) . '(' . iN_HelpSecure($epochCurrencyLabel) . ')'; ?></span>
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                            </div>
                            <div class="i_limit_list_container">
                                <div class="i_countries_list border_one column flex_">
                                    <?php foreach ($currencys as $crncy => $value) { ?>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($epochSelectedCurrency) == $crncy ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($crncy); ?>" data-c="<?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>" data-type="mb_limit">
                                            <?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="epoch_currency" id="upLimit" value="<?php echo iN_HelpSecure($epochSelectedCurrency); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_test_endpoint']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="epoch_test_endpoint" class="i_input flex_" value="<?php echo iN_HelpSecure($epochTestEndpoint); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['epoch_checkout_endpoint_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_live_endpoint']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="epoch_live_endpoint" class="i_input flex_" value="<?php echo iN_HelpSecure($epochLiveEndpoint); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['epoch_checkout_endpoint_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_postback_secret']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="epoch_postback_secret" class="i_input flex_" value="<?php echo iN_HelpSecure($epochPostbackSecret); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['epoch_postback_secret_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_postback_allowlist']); ?></div>
                    <div class="irow_box_right">
                        <textarea name="epoch_postback_allowlist" class="i_input flex_ i_textarea"><?php echo iN_HelpSecure($epochPostbackAllowlist); ?></textarea>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['epoch_postback_allowlist_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_return_mode']); ?></div>
                    <div class="irow_box_right">
                        <?php $epochMode = in_array((string)$epochReturnMode, ['pending', 'status'], true) ? (string)$epochReturnMode : 'pending'; ?>
                        <select name="epoch_return_mode" class="i_input flex_">
                            <option value="pending" <?php echo $epochMode === 'pending' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['epoch_return_mode_pending']); ?></option>
                            <option value="status" <?php echo $epochMode === 'status' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['epoch_return_mode_status']); ?></option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['epoch_template']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="epoch_template" class="i_input flex_" value="<?php echo iN_HelpSecure($epochTemplate); ?>">
                    </div>
                </div>

                <input type="hidden" id="epoch_postback_enabled_hidden" name="epoch_postback_enabled" value="<?php echo iN_HelpSecure($epochPostbackEnabled === '1' ? '1' : '0'); ?>">

                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
                <div class="admin_approve_post_footer">
                    <div class="i_become_creator_box_footer">
                        <input type="hidden" name="f" value="updateEpoch">
                        <button type="submit" name="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['save_edit']); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
