<div class="i_contents_container">
  <div class="i_general_white_board border_one column flex_ tabing__justify">
    <div class="i_general_title_box">
      <?php echo iN_HelpSecure($LANG['ccbill_subscription_settings']); ?>
    </div>

    <div class="i_general_row_box column flex_" id="general_conf">
      <div class="i_general_row_box_item flex_ tabing__justify">
        <div class="irow_box_left tabing flex_">
          <?php echo iN_HelpSecure($LANG['ccbill_status']); ?>
        </div>
        <div class="irow_box_right">
          <div class="i_checkbox_wrapper flex_ tabing_non_justify">
            <label class="el-switch el-switch-yellow" for="ccbill_sub_status">
              <input type="checkbox" name="ccbill_sub_status" class="chmdSubPayment" id="ccbill_sub_status" data-active-value="1" data-inactive-value="0" <?php echo iN_HelpSecure($ccbill_Status) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
              <span class="el-switch-style"></span>
            </label>
            <div class="success_tick tabing flex_ sec_one ccbill_sub_status">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
            </div>
          </div>
          <div class="rec_not box_not_padding_left">
            <?php echo iN_HelpSecure($LANG['no_one_will_make_new_subs']); ?>
          </div>
        </div>
      </div>

      <div class="i_general_row_box_item flex_ tabing__justify">
        <div class="irow_box_left tabing flex_">
          <?php echo iN_HelpSecure($LANG['beta_mode']); ?>
        </div>
        <div class="irow_box_right">
          <div class="i_checkbox_wrapper flex_ tabing_non_justify">
            <label class="el-switch el-switch-yellow" for="ccbill_beta">
              <input type="checkbox" class="chmdPayment" id="ccbill_beta" data-active-value="1" data-inactive-value="0" <?php echo (isset($ccbillBeta) && $ccbillBeta == '1') ? 'value="0" checked="checked"' : 'value="1"'; ?>>
              <span class="el-switch-style"></span>
            </label>
            <div class="success_tick tabing flex_ sec_one ccbill_beta">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
            </div>
          </div>
          <div class="rec_not box_not_padding_left">
            <?php echo iN_HelpSecure($LANG['beta_mode_desc']); ?>
          </div>
        </div>
      </div>

      <form enctype="multipart/form-data" method="post" id="updateSubsPaymentGatawayCCBILL">
        <div class="i_general_row_box_item flex_ column tabing_non_justify guide_box">
          <div class="guide_title flex_"><?php echo iN_HelpSecure($LANG['ccbill_setup_help_title']); ?></div>
          <div class="guide_body">
            <?php echo $LANG['ccbill_setup_help_steps']; ?>
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_account_number']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="accountNumber" class="i_input flex_" value="<?php echo iN_HelpSecure($ccbill_AccountNumber); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_subaccount_number']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="subAccountNumber" class="i_input flex_" value="<?php echo iN_HelpSecure($ccbill_SubAccountNumber); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_flexform_id']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="flexFormID" class="i_input flex_" value="<?php echo iN_HelpSecure($ccbill_FlexID); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_salt_key']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="saltKey" class="i_input flex_" value="<?php echo iN_HelpSecure($ccbill_SaltKey); ?>">
          </div>
        </div>

        <?php
          $displayCurrencyKey = isset($currencys[$ccbill_Currency]) ? $ccbill_Currency : (array_key_first($currencys) ?? '');
          $displayCurrencyLabel = $displayCurrencyKey !== '' && isset($currencys[$displayCurrencyKey]) ? $currencys[$displayCurrencyKey] : '';
          if ($displayCurrencyKey === '' && !empty($currencys)) {
              $displayCurrencyKey = array_key_first($currencys);
              $displayCurrencyLabel = isset($currencys[$displayCurrencyKey]) ? $currencys[$displayCurrencyKey] : '';
          }
        ?>
        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_currency']); ?>
          </div>
          <div class="irow_box_right">
            <div class="i_box_limit flex_ column">
              <div class="i_limit" data-type="fl_limit">
                <span class="lmt">
                  <?php
                    if ($displayCurrencyKey !== '' && $displayCurrencyLabel !== '') {
                        echo iN_HelpSecure($displayCurrencyKey) . '(' . $displayCurrencyLabel . ')';
                    } else if ($displayCurrencyKey !== '') {
                        echo iN_HelpSecure($displayCurrencyKey);
                    } else {
                        echo '---';
                    }
                  ?>
                </span>
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
              </div>
              <div class="i_limit_list_container">
                <div class="i_countries_list border_one column flex_">
                  <?php foreach ($currencys as $crncy => $value) { ?>
                    <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($displayCurrencyKey) == $crncy ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($crncy); ?>" data-c="<?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>" data-type="mb_limit">
                      <?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>
                    </div>
                  <?php } ?>
                </div>
                <input type="hidden" name="ccbill_currency" id="upLimit" value="<?php echo iN_HelpSecure($displayCurrencyKey); ?>">
              </div>
              <div class="rec_not box_not_padding_top">
                <?php echo iN_HelpSecure($LANG['make_sure_for_ccbill']); ?>
              </div>
            </div>
          </div>
        </div>
        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_datalink_username']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cancelUsername" class="i_input flex_" value="<?php echo iN_HelpSecure($ccbill_CancelUsername); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['ccbill_datalink_password']); ?>
          </div>
          <div class="irow_box_right">
            <input type="password" name="cancelPassword" class="i_input flex_" value="<?php echo iN_HelpSecure($ccbill_CancelPassword); ?>">
          </div>
        </div>
        <div class="rec_not box_not_padding_top">
          <?php echo iN_HelpSecure($LANG['ccbill_cancel_note']); ?>
        </div>

        <div class="i_settings_wrapper_item successNot">
          <?php echo iN_HelpSecure($LANG['updated_successfully']); ?>
        </div>

        <div class="admin_approve_post_footer">
          <div class="i_become_creator_box_footer">
            <input type="hidden" name="f" value="updateSubStripeCCBILL">
            <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_myprofile">
              <?php echo iN_HelpSecure($LANG['save_edit']); ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
