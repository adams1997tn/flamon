<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['konnect_payment'] ?? 'Konnect Network'); ?>
        </div>

        <div class="i_general_row_box column flex_" id="general_conf">

            <div class="i_general_row_box_item flex_ column tabing__justify">
                <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                    <div class="i_chck_text admin_note_t"><?php echo iN_HelpSecure($LANG['test_mode']); ?></div>
                    <label class="el-switch el-switch-yellow" for="konnect_mode">
                        <input type="checkbox" name="konnect_mode" class="chmdPayment" id="konnect_mode" <?php echo iN_HelpSecure($konnectPaymentMode) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                        <span class="el-switch-style"></span>
                    </label>
                    <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_mode']); ?></div>
                    <div class="success_tick tabing flex_ sec_one konnect_mode">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing__justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['konnect_status'] ?? 'Konnect Status'); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="konnect_status">
                            <input type="checkbox" name="konnect_status" class="chmdPayment" id="konnect_status" <?php echo iN_HelpSecure($konnectPaymentStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="success_tick tabing flex_ sec_one konnect_status">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['konnect_status_not'] ?? 'Enable or disable Konnect Network as a payment option for your users.'); ?></div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing__justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['beta_mode']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="konnect_beta">
                            <input type="checkbox" class="chmdPayment" id="konnect_beta" data-active-value="1" data-inactive-value="0" <?php echo (isset($konnectPaymentBeta) && $konnectPaymentBeta == '1') ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="success_tick tabing flex_ sec_one konnect_beta">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['beta_mode_desc']); ?></div>
                </div>
            </div>

            <form enctype="multipart/form-data" method="post" id="updatePaymentGataway">

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['konnect_test_api_key'] ?? 'Sandbox API Key'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="konnectTestApiKey" class="i_input flex_" value="<?php echo iN_HelpSecure($konnectTestApiKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['konnect_test_wallet_id'] ?? 'Sandbox Receiver Wallet ID'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="konnectTestWalletId" class="i_input flex_" value="<?php echo iN_HelpSecure($konnectTestWalletId); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['konnect_live_api_key'] ?? 'Live API Key'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="konnectLiveApiKey" class="i_input flex_" value="<?php echo iN_HelpSecure($konnectLiveApiKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['konnect_live_wallet_id'] ?? 'Live Receiver Wallet ID'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="konnectLiveWalletId" class="i_input flex_" value="<?php echo iN_HelpSecure($konnectLiveWalletId); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['konnect_webhook_secret'] ?? 'Webhook Shared Secret'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="konnectWebhookSecret" class="i_input flex_" value="<?php echo iN_HelpSecure($konnectWebhookSecret); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['konnect_webhook_url'] ?? 'Webhook URL'); ?>: <code><?php echo iN_HelpSecure(rtrim($baseurl ?? '', '/') . '/konnect_webhook.php'); ?></code></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['authorizenet_crncy']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_box_limit flex_ column">
                            <div class="i_limit" data-type="fl_limit">
                                <span class="lmt"><?php echo iN_HelpSecure($konnectCurrency) . '(' . ($currencys[$konnectCurrency] ?? '') . ')'; ?></span>
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                            </div>
                            <div class="i_limit_list_container">
                                <div class="i_countries_list border_one column flex_">
                                    <?php foreach ($currencys as $crncy => $value) { ?>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($konnectCurrency) == '' . $crncy . '' ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($crncy); ?>" data-c="<?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>" data-type="mb_limit">
                                            <?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="konnect_crncy" id="upLimit" value="<?php echo iN_HelpSecure($konnectCurrency); ?>">
                            </div>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['make_sure_for_konnect'] ?? 'Konnect officially supports TND. Choose another currency only if your account is configured for it.'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>

                <div class="admin_approve_post_footer">
                    <div class="i_become_creator_box_footer">
                        <input type="hidden" name="f" value="updateKonnect">
                        <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_myprofile">
                            <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
