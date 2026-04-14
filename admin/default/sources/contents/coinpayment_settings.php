<div class="i_contents_container">
  <div class="i_general_white_board border_one column flex_ tabing__justify">
    <div class="i_general_title_box">
      <?php echo iN_HelpSecure($LANG['coinpayment_settings']); ?>
    </div>

    <div class="i_general_row_box column flex_" id="general_conf">
      <div class="i_general_row_box_item flex_ tabing__justify">
        <div class="irow_box_left tabing flex_">
          <?php echo iN_HelpSecure($LANG['coinpayment_status']); ?>
        </div>
        <div class="irow_box_right">
          <div class="i_checkbox_wrapper flex_ tabing_non_justify">
            <label class="el-switch el-switch-yellow" for="coinpayment_status">
              <input type="checkbox" name="coinpayment_status" class="chmdPayment" id="coinpayment_status" <?php echo iN_HelpSecure($coinPaymentStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
              <span class="el-switch-style"></span>
            </label>
            <div class="success_tick tabing flex_ sec_one coinpayment_status">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
            </div>
          </div>
          <div class="rec_not box_not_padding_left">
            <?php echo iN_HelpSecure($LANG['coinpayment_status_not']); ?>
          </div>
        </div>
      </div>

      <div class="i_general_row_box_item flex_ tabing__justify">
        <div class="irow_box_left tabing flex_">
          <?php echo iN_HelpSecure($LANG['beta_mode']); ?>
        </div>
        <div class="irow_box_right">
          <div class="i_checkbox_wrapper flex_ tabing_non_justify">
            <label class="el-switch el-switch-yellow" for="coinpayment_beta">
              <input type="checkbox" class="chmdPayment" id="coinpayment_beta" data-active-value="1" data-inactive-value="0" <?php echo (isset($coinPaymentBeta) && $coinPaymentBeta == '1') ? 'value="0" checked="checked"' : 'value="1"'; ?>>
              <span class="el-switch-style"></span>
            </label>
            <div class="success_tick tabing flex_ sec_one coinpayment_beta">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
            </div>
          </div>
          <div class="rec_not box_not_padding_left">
            <?php echo iN_HelpSecure($LANG['beta_mode_desc']); ?>
          </div>
        </div>
      </div>

      <form enctype="multipart/form-data" method="post" id="updatePaymentGataway">
        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayment_mode']); ?>
          </div>
          <div class="irow_box_right">
            <select name="coinpayment_mode" class="i_input flex_">
              <option value="legacy" <?php echo iN_HelpSecure($coinPaymentMode) == 'legacy' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['coinpayment_mode_legacy']); ?></option>
              <option value="v2" <?php echo iN_HelpSecure($coinPaymentMode) == 'v2' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['coinpayment_mode_v2']); ?></option>
            </select>
            <div class="rec_not box_not_padding_left">
              <?php echo iN_HelpSecure($LANG['coinpayment_mode_desc']); ?>
            </div>
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_private_key']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cprivatekey" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentPrivateKey); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_public_key']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cpublickey" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentPublicKey); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_merchand_id']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cmerchandid" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentMerchandID); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayment_ipn_secret']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cipnsecret" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentIPNSecret); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayment_debug_email']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cdebugemail" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentDebugEmail); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_client_id']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cclientid" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentClientId); ?>">
            <div class="rec_not box_not_padding_left">
              <?php echo iN_HelpSecure($LANG['coinpayment_v2_hint']); ?>
            </div>
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_client_secret']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cclientsecret" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentClientSecret); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_webhook_secret']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="cwebhooksecret" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentWebhookSecret); ?>">
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['coinpayments_api_base']); ?>
          </div>
          <div class="irow_box_right">
            <input type="text" name="capibase" class="i_input flex_" value="<?php echo iN_HelpSecure($coinPaymentApiBase); ?>" placeholder="https://api.coinpayments.net">
            <div class="rec_not box_not_padding_left">
              <?php echo iN_HelpSecure($LANG['coinpayments_api_base_desc']); ?>
            </div>
          </div>
        </div>

        <div class="i_general_row_box_item flex_ tabing_non_justify">
          <div class="irow_box_left tabing flex_">
            <?php echo iN_HelpSecure($LANG['cryptocurrencies']); ?>
          </div>
          <div class="irow_box_right">
            <div class="i_box_limit flex_ column">
              <div class="i_limit" data-type="fl_limit">
                <span class="lmt"><?php echo iN_HelpSecure($coinPaymentCryptoCurrency) . '(' . $crpytoCurrency[$coinPaymentCryptoCurrency] . ')'; ?></span>
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
              </div>
              <div class="i_limit_list_container max_width_limit">
                <div class="i_countries_list border_one column flex_">
                  <?php foreach ($crpytoCurrency as $crncy => $value) { ?>
                    <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($coinPaymentCryptoCurrency) == $crncy ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($crncy); ?>" data-c="<?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>" data-type="mb_limit">
                      <?php echo iN_HelpSecure($crncy) . '(' . $value . ')'; ?>
                    </div>
                  <?php } ?>
                </div>
                <input type="hidden" name="crpCurrency" id="upLimit" value="<?php echo iN_HelpSecure($coinPaymentCryptoCurrency); ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="i_settings_wrapper_item successNot">
          <?php echo iN_HelpSecure($LANG['updated_successfully']); ?>
        </div>

        <div class="admin_approve_post_footer">
          <div class="i_become_creator_box_footer">
            <input type="hidden" name="f" value="updateCoinPayment">
            <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_myprofile">
              <?php echo iN_HelpSecure($LANG['save_edit']); ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
