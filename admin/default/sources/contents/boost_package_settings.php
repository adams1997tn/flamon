<div class="i_contents_container boost-packages-page">
  <div class="i_general_white_board border_one column flex_ tabing__justify">
    <div class="i_general_title_box">
      <?php echo iN_HelpSecure($LANG['point_packages_settings']); ?>
    </div>

    <div class="i_general_row_box column flex_ box_not_padding_top_package" id="general_conf">
      <?php
	      $csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
	      $currentExpireDays = isset($boostPostExpireDays) ? (int) $boostPostExpireDays : 30;
	      if ($currentExpireDays < 1) {
	          $currentExpireDays = 30;
	      }
	      $currentBoostedStatus = isset($boostedPostEnableDisable) ? (string) $boostedPostEnableDisable : 'no';
	      if (!in_array($currentBoostedStatus, ['yes', 'no'], true)) {
	          $currentBoostedStatus = 'no';
	      }
	      $boostExpireDaysLabel = isset($LANG['boost_post_expire_days']) ? (string) $LANG['boost_post_expire_days'] : 'Boost post expire days';
	      $boostedVisibilityLabel = isset($LANG['boosted_posts_visibility']) ? (string) $LANG['boosted_posts_visibility'] : 'Boosted posts visibility';
	      $boostedVisibilityDesc = isset($LANG['boosted_posts_visibility_desc']) ? (string) $LANG['boosted_posts_visibility_desc'] : 'Enable to show boosted posts in feeds and discovery placements.';
	      $boostExpireDaysDesc = isset($LANG['boost_post_expire_days_desc']) ? (string) $LANG['boost_post_expire_days_desc'] : 'Set how many days a boosted post stays active. Minimum: 1 day.';
	      ?>
	      <div class="i_contents_section flex_ manage_margin_bottom">
	        <div class="irow_box_right irow_box_right_style">
	          <form method="post" id="boostGeneralSettingsForm">
	            <input type="hidden" name="f" value="boostGeneralSettings">
	            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
	            <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($boostedVisibilityLabel); ?></div>
	            <select class="i_input flex_" name="boosted_post_status">
	              <option value="yes" <?php echo $currentBoostedStatus === 'yes' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['enabled']); ?></option>
	              <option value="no" <?php echo $currentBoostedStatus === 'no' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['disabled']); ?></option>
	            </select>
	            <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($boostedVisibilityDesc); ?></div>
	            <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($boostExpireDaysLabel); ?></div>
	            <input type="number" class="i_input flex_" name="boost_post_expire_days" min="1" step="1" value="<?php echo iN_HelpSecure((string) $currentExpireDays); ?>">
	            <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($boostExpireDaysDesc); ?></div>
	            <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['save_edit']); ?></button>
	          </form>
	          <div class="i_settings_wrapper_item successNot boostSettingsSaved"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
	        </div>
	      </div>

      <div class="new_svg_icon_wrapper">
        <div class="inline_block">
          <div class="flex_ tabing_non_justify newSvgCode newCreate border_one" data-type="newBoostPackage">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91')); ?>
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('178')); ?>
            <?php echo iN_HelpSecure($LANG['create_a_new_package']); ?>
          </div>
        </div>
      </div>

      <div class="buyCreditWrapper flex_ tabing">
        <?php
        $boostPlanList = $iN->iN_BoostPlanList();
        if ($boostPlanList) {
          foreach ($boostPlanList as $planData) {
            $planID = $planData['plan_id'] ?? null;
            $planName = $planData['plan_name_key'] ?? null;
            $planIcon = $planData['plan_icon'] ?? null;
            $planCreditAmount = $planData['plan_amount'] ?? null;
            $planAmount = $planData['amount'] ?? null;
            $editPlanLink = $base_url . 'admin/boost_package_settings?id=' . $planID;
            $planStatus = $planData['plan_status'] ?? null;
            $planViewTime = $planData['view_time'] ?? null;
        ?>
        <div class="credit_plan_box" id="<?php echo iN_HelpSecure($planID); ?>">
          <div class="plan_box tabing flex_" id="p_i_<?php echo iN_HelpSecure($planID); ?>">
            <div class="plan_name flex_">
              <?php echo isset($LANG[$planName]) ? $LANG[$planName] : $planName; ?>
            </div>
            <div class="plan_value">
              <div class="plan_price tabing">
                <div class="positionRelative display_initial">
                  <?php echo number_format($planViewTime); ?>
                  <span class="plan_boost_icon">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('122')); ?>
                  </span>
                </div>
              </div>
              <div class="plan_point tabing flex_">
                <?php echo iN_HelpSecure($LANG['ppcansee']); ?>
              </div>
              <div class="purchaseButton flex_ tabing">
                <?php echo iN_HelpSecure($LANG['purchase']); ?>
                <strong class="tabing flex_ i_inline_flex">
                  <?php echo iN_HelpSecure($currencys[$defaultCurrency]) . number_format($planCreditAmount); ?>
                  <span class="prcsic">
                    <?php echo html_entity_decode($planIcon); ?>
                  </span>
                </strong>
              </div>
            </div>

            <div class="tabing flex_ edit_active_delete">
              <div class="ecd_item">
                <div class="ecd_item_in flex_ tabing">
                  <div class="i_checkbox_wrapper flex_ tabing_non_justify box_padding_d">
                    <label class="el-switch el-switch-yellow" for="planBoostStatus<?php echo iN_HelpSecure($planID); ?>">
                      <input type="checkbox" class="pstatBoost" id="planBoostStatus<?php echo iN_HelpSecure($planID); ?>" data-id="<?php echo iN_HelpSecure($planID); ?>" data-type="planBoostStatus" <?php echo iN_HelpSecure($planStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                      <span class="el-switch-style"></span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="ecd_item flex_ tabing">
                <a href="<?php echo iN_HelpSecure($editPlanLink, FILTER_VALIDATE_URL); ?>">
                  <div class="ecd_item_in flex_ tabing edit_plan border_one c2">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?>
                    <?php echo iN_HelpSecure($LANG['edit_plan']); ?>
                  </div>
                </a>
              </div>

              <div class="ecd_item flex_ tabing">
                <div class="ecd_item_in flex_ tabing delete_boost_plan border_one c3" id="<?php echo iN_HelpSecure($planID); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?>
                  <?php echo iN_HelpSecure($LANG['delete_plan']); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php
          }
        }
        ?>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/js/boostPackageSettingsHandler.js?v=<?php echo iN_HelpSecure($version); ?>" defer></script>
