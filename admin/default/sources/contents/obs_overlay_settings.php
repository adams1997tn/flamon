<?php
if ($iN->iN_CheckIsAdmin($userID) != 1) {
    echo iN_HelpSecure($LANG['noway_desc']);
    return;
}

$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$obsOverlayModuleStatusValue = $iN->iN_IsObsOverlayEnabled() ? 'yes' : 'no';
$obsOverlayMaxTotalValue = $iN->iN_GetObsOverlayMaxTotal();
$obsOverlayMaxActiveValue = $iN->iN_GetObsOverlayMaxActive();
$obsOverlayAutoRevokeValue = $iN->iN_IsObsOverlayAutoRevokeEnabled() ? '1' : '0';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['obs_overlay_admin_settings_title']); ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="obs_admin_info_note">
                <?php echo iN_HelpSecure($LANG['obs_overlay_admin_settings_desc']); ?>
            </div>
            <form enctype="multipart/form-data" method="post" id="obsOverlaySettingsForm">
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_enable_label']); ?></div>
                    <div class="irow_box_right">
                        <select name="obs_overlay_module_status" class="i_input">
                            <option value="yes" <?php echo $obsOverlayModuleStatusValue === 'yes' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['yes']); ?></option>
                            <option value="no" <?php echo $obsOverlayModuleStatusValue === 'no' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['no']); ?></option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_max_total_label']); ?></div>
                    <div class="irow_box_right">
                        <input type="number" min="1" max="50" name="obs_overlay_max_total" class="i_input flex_" value="<?php echo iN_HelpSecure($obsOverlayMaxTotalValue); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_max_total_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_max_active_label']); ?></div>
                    <div class="irow_box_right">
                        <input type="number" min="1" max="50" name="obs_overlay_max_active" class="i_input flex_" value="<?php echo iN_HelpSecure($obsOverlayMaxActiveValue); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_max_active_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_auto_revoke_label']); ?></div>
                    <div class="irow_box_right">
                        <select name="obs_overlay_auto_revoke_oldest" class="i_input">
                            <option value="1" <?php echo $obsOverlayAutoRevokeValue === '1' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['yes']); ?></option>
                            <option value="0" <?php echo $obsOverlayAutoRevokeValue === '0' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['no']); ?></option>
                        </select>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_auto_revoke_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <input type="hidden" name="f" value="obsOverlaySettings">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['save_edit']); ?></button>
                </div>
            </form>
            <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_settings_saved']); ?></div>
            <div class="warning_wrapper warning_obs_overlay_max_total"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_invalid_max_total']); ?></div>
            <div class="warning_wrapper warning_obs_overlay_max_active"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_invalid_max_active']); ?></div>
        </div>
    </div>
</div>
