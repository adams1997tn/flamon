<?php
if ($iN->iN_CheckIsAdmin($userID) != 1) {
    echo iN_HelpSecure($LANG['noway_desc']);
    return;
}
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$creatorBulkEnabled = $iN->iN_IsCreatorBulkEnabled() ? '1' : '0';
$creatorBulkDailyLimit = $iN->iN_GetCreatorBulkDailyLimit();
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['admin_creator_bulk_settings_title']);?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <form enctype="multipart/form-data" method="post" id="creatorBulkSettingsForm">
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['admin_creator_bulk_enable_label']);?></div>
                    <div class="irow_box_right">
                        <select name="creator_bulk_messaging_enabled" class="i_input">
                            <option value="1" <?php echo $creatorBulkEnabled === '1' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['yes']);?></option>
                            <option value="0" <?php echo $creatorBulkEnabled === '0' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['no']);?></option>
                        </select>
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['admin_creator_bulk_daily_limit_label']);?></div>
                    <div class="irow_box_right">
                        <input type="number" min="0" name="creator_bulk_daily_limit" class="i_input flex_" value="<?php echo iN_HelpSecure($creatorBulkDailyLimit);?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['bulk_message_private_price_min_label']);?></div>
                    <div class="irow_box_right">
                        <input type="text" name="bulk_message_private_price_min" class="i_input flex_" inputmode="decimal" value="<?php echo iN_HelpSecure(number_format((float)$bulkMessagePrivatePriceMin, 2, '.', ''));?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['bulk_message_private_price_note']);?></div>
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['bulk_message_private_price_max_label']);?></div>
                    <div class="irow_box_right">
                        <input type="text" name="bulk_message_private_price_max" class="i_input flex_" inputmode="decimal" value="<?php echo iN_HelpSecure(number_format((float)$bulkMessagePrivatePriceMax, 2, '.', ''));?>">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['bulk_message_private_price_note']);?></div>
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <input type="hidden" name="f" value="creatorBulkSettings">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['save_edit']); ?></button>
                </div>
            </form>
            <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['admin_creator_bulk_settings_saved']); ?></div>
        </div>
    </div>
</div>
