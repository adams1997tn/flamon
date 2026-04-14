<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$statusFilter = isset($_GET['status']) ? (string)$_GET['status'] : '';
$allowedStatuses = ['pending', 'approved', 'rejected'];
$where = '';
$params = [];
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $where = 'WHERE R.status = ?';
    $params[] = $statusFilter;
}
$requests = DB::all(
    "SELECT R.*, U.i_username, U.i_user_fullname FROM i_agency_create_requests R LEFT JOIN i_users U ON U.iuid = R.owner_iuid_fk $where ORDER BY R.acr_id DESC",
    $params
);
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['agency_create_requests']); ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_contents_section flex_ manage_margin_bottom">
                <a href="<?php echo iN_HelpSecure($base_url); ?>admin/agencies">
                    <div class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['back_to_list']); ?></div>
                </a>
            </div>

            <?php if (!empty($requests)) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_name']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_owner']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_fee_rate']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_request_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php foreach ($requests as $request) {
                            $requestId = (int)($request['acr_id'] ?? 0);
                            $ownerId = (int)($request['owner_iuid_fk'] ?? 0);
                            $ownerName = $request['i_user_fullname'] ?? $request['i_username'] ?? '';
                            $requestStatus = $request['status'] ?? '';
                            $agencyName = $request['agency_name'] ?? '';
                            $agencyFee = isset($request['agency_fee']) ? (float)$request['agency_fee'] : 0.0;
                            $agencyFeeLabel = number_format($agencyFee, 2, '.', '') . '%';
                        ?>
                        <tr class="transition trhover">
                            <td><?php echo iN_HelpSecure($requestId); ?></td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify sim truncated"><?php echo iN_HelpSecure($agencyName); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify sim truncated"><?php echo iN_HelpSecure($ownerName); ?> (#<?php echo iN_HelpSecure($ownerId); ?>)</div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($agencyFeeLabel); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($LANG['agency_request_status_' . $requestStatus] ?? $requestStatus); ?></div>
                            </td>
                            <td class="flex_ tabing_non_justify">
                                <?php if ($requestStatus === 'pending') { ?>
                                    <div class="seePost c2 border_one transition tabing flex_ agencyCreateRequestAction" data-request="<?php echo iN_HelpSecure($requestId); ?>" data-status="approved">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')) . iN_HelpSecure($LANG['approve']); ?>
                                    </div>
                                    <div class="delu border_one transition tabing flex_ agencyCreateRequestAction" data-request="<?php echo iN_HelpSecure($requestId); ?>" data-status="rejected">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['reject']); ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="seePost c3 border_one transition tabing flex_">
                                        <?php echo iN_HelpSecure($LANG['no_actions']); ?>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . html_entity_decode($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['agency_no_requests'] . '</div></div>';
            } ?>
        </div>
    </div>
</div>
