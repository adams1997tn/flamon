<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$agencyId = isset($_GET['agency']) ? (int)$_GET['agency'] : 0;
$agency = $agencyId > 0 ? DB::one("SELECT A.*, U.i_username, U.i_user_fullname FROM i_agencies A LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk WHERE A.agency_id = ? LIMIT 1", [$agencyId]) : null;
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['agency_edit']); ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_contents_section flex_ manage_margin_bottom">
                <a href="<?php echo iN_HelpSecure($base_url); ?>admin/agencies">
                    <div class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['back_to_list']); ?></div>
                </a>
            </div>

            <?php if (!$agency) { ?>
                <div class="warning_"><?php echo iN_HelpSecure($LANG['agency_not_found']); ?></div>
            <?php } else {
                $ownerId = (int)($agency['owner_iuid_fk'] ?? 0);
                $ownerName = $agency['i_user_fullname'] ?? $agency['i_username'] ?? '';
                $ownerLabel = $ownerName !== '' ? $ownerName . ' (#' . $ownerId . ')' : '#' . $ownerId;
                $agencyFee = isset($agency['agency_fee']) ? (float)$agency['agency_fee'] : 0.0;
                $agencyFeeLabel = number_format($agencyFee, 2, '.', '') . '%';
            ?>
                <form id="agencyStatusForm" method="post">
                    <input type="hidden" name="f" value="agencyStatusUpdate">
                    <input type="hidden" name="agency_id" value="<?php echo iN_HelpSecure($agencyId); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['agency_name']); ?>
                        </div>
                        <div class="irow_box_right">
                            <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($agency['agency_name'] ?? ''); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['agency_owner']); ?>
                        </div>
                        <div class="irow_box_right">
                            <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($ownerLabel); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['agency_fee_rate']); ?>
                        </div>
                        <div class="irow_box_right">
                            <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($agencyFeeLabel); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['agency_status']); ?>
                        </div>
                        <div class="irow_box_right">
                            <select name="agency_status" class="i_input">
                                <option value="active" <?php echo ($agency['agency_status'] ?? '') === 'active' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['agency_status_active']); ?></option>
                                <option value="inactive" <?php echo ($agency['agency_status'] ?? '') === 'inactive' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['agency_status_inactive']); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"></div>
                        <div class="irow_box_right">
                            <button type="submit" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['save_changes']); ?></button>
                        </div>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</div>
