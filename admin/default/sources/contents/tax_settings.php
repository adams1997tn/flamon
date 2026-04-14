<?php
$taxSettings = $iN->iN_GetTaxSettings();
$taxEnabled = $taxSettings['enabled'];
$currentTaxRate = isset($taxSettings['rate']) ? (float)$taxSettings['rate'] : 0;
$currentTaxLabel = $taxSettings['label'] ?? 'VAT';
$currentTaxRegistration = $taxSettings['registration_number'] ?? '';
$currentCompanyName = $taxSettings['company_name'] ?? '';
$currentCompanyAddress = $taxSettings['company_address'] ?? '';
$currentInvoicePrefix = $taxSettings['invoice_prefix'] ?? 'INV';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['tax_settings']); ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_general_row_box_item flex_ column tabing__justify">
                <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                    <div class="i_chck_text admin_note_t"><?php echo iN_HelpSecure($LANG['tax_status']); ?></div>
                    <label class="el-switch el-switch-yellow" for="tax_status">
                        <input type="checkbox" name="tax_status" class="chmdPayment" id="tax_status" <?php echo $taxEnabled ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                        <span class="el-switch-style"></span>
                    </label>
                    <div class="i_chck_text"><?php echo iN_HelpSecure($taxEnabled ? $LANG['enabled'] : $LANG['disabled']); ?></div>
                    <div class="success_tick tabing flex_ sec_one tax_status">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                    </div>
                </div>
                <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['tax_settings_note']); ?></div>
            </div>
            <form enctype="multipart/form-data" method="post" id="updatePaymentGataway">
                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['tax_rate']); ?></div>
                    <div class="irow_box_right">
                        <input type="number" name="tax_rate" step="0.01" min="0" class="i_input flex_" value="<?php echo iN_HelpSecure($currentTaxRate); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['tax_label']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="tax_label" class="i_input flex_" value="<?php echo iN_HelpSecure($currentTaxLabel); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['tax_registration_number']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="tax_registration" class="i_input flex_" value="<?php echo iN_HelpSecure($currentTaxRegistration); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['tax_company_name']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="tax_company" class="i_input flex_" value="<?php echo iN_HelpSecure($currentCompanyName); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['tax_company_address']); ?></div>
                    <div class="irow_box_right">
                        <textarea name="tax_company_address" class="i_textarea flex_" rows="4"><?php echo iN_HelpSecure($currentCompanyAddress); ?></textarea>
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing__justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['tax_invoice_prefix']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="tax_invoice_prefix" class="i_input flex_" maxlength="10" value="<?php echo iN_HelpSecure($currentInvoicePrefix); ?>">
                    </div>
                </div>
                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
                <div class="admin_approve_post_footer">
                    <div class="i_become_creator_box_footer">
                        <input type="hidden" name="f" value="updateTaxSettings">
                        <button type="submit" name="submit" class="i_nex_btn_btn transition">
                            <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
