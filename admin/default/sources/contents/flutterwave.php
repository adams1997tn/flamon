<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['flutterwave_payment']); ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_general_row_box_item flex_ column tabing__justify">
                <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                    <div class="i_chck_text admin_note_t"><?php echo iN_HelpSecure($LANG['test_mode']); ?></div>
                    <label class="el-switch el-switch-yellow" for="flutterwave_mode">
                        <input type="checkbox" name="flutterwave_mode" class="chmdPayment" id="flutterwave_mode" <?php echo iN_HelpSecure($flutterWavePaymentMode) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                        <span class="el-switch-style"></span>
                    </label>
                    <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_mode']); ?></div>
                    <div class="success_tick tabing flex_ sec_one flutterwave_mode">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing__justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_status']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="flutterwave_status">
                            <input type="checkbox" name="flutterwave_status" class="chmdPayment" id="flutterwave_status" <?php echo iN_HelpSecure($flutterWavePaymentStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="success_tick tabing flex_ sec_one flutterwave_status">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['flutterwave_status_not']); ?></div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing__justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['beta_mode']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="flutterwave_beta">
                            <input type="checkbox" class="chmdPayment" id="flutterwave_beta" data-active-value="1" data-inactive-value="0" <?php echo (isset($flutterWavePaymentBeta) && $flutterWavePaymentBeta == '1') ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="success_tick tabing flex_ sec_one flutterwave_beta">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['beta_mode_desc']); ?></div>
                </div>
            </div>

            <form enctype="multipart/form-data" method="post" id="updatePaymentGataway">
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_testing_public_key']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="flutterwaveTestPublicKey" class="i_input flex_" value="<?php echo iN_HelpSecure($flutterWaveTestPublicKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_testing_secret_key']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="flutterwaveTestSecretKey" class="i_input flex_" value="<?php echo iN_HelpSecure($flutterWaveTestSecretKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_live_public_key']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="flutterwaveLivePublicKey" class="i_input flex_" value="<?php echo iN_HelpSecure($flutterWaveLivePublicKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_live_secret_key']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="flutterwaveLiveSecretKey" class="i_input flex_" value="<?php echo iN_HelpSecure($flutterWaveLiveSecretKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_encryption_key']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="flutterwaveEncryptionKey" class="i_input flex_" value="<?php echo iN_HelpSecure($flutterWaveEncryptionKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_secret_hash']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="flutterwaveSecretHash" class="i_input flex_" value="<?php echo iN_HelpSecure($flutterWaveSecretHash); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['flutterwave_crncy']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_box_limit flex_ column">
                            <div class="i_limit" data-type="fl_limit">
                                <span class="lmt"><?php echo iN_HelpSecure($flutterWaveCurrency) . '(' . $currencys[$flutterWaveCurrency] . ')'; ?></span>
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                            </div>
                            <div class="i_limit_list_container">
                                <div class="i_countries_list border_one column flex_">
                                    <?php foreach ($currencys as $crncy => $value) { ?>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($flutterWaveCurrency) == $crncy ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($crncy); ?>" data-c="<?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>" data-type="mb_limit"><?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?></div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="flutterwave_crncy" id="upLimit" value="<?php echo iN_HelpSecure($flutterWaveCurrency); ?>">
                            </div>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['make_sure_for_flutterwave']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
                <div class="admin_approve_post_footer">
                    <div class="i_become_creator_box_footer">
                        <input type="hidden" name="f" value="updateFlutterwave">
                        <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_flutterwave">
                            <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
