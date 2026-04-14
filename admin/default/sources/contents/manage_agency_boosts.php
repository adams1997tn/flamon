<?php
$searchValue = '';
$filter = 'all';

if (isset($_GET["page-id"])) {
    $pagep = isset($_GET["page-id"]) ? (int)$_GET["page-id"] : 1;
    if (!preg_match('/^[0-9]+$/', $pagep)) {
        $pagep = '1';
    }
} else {
    $pagep = '1';
}

if (isset($_GET['st'])) {
    $filter = isset($_GET['st']) ? $iN->iN_Secure($_GET['st']) : 'all';
}
if (!in_array($filter, ['all', 'active', 'expired', 'disabled'], true)) {
    $filter = 'all';
}

if (isset($_GET['sr'])) {
    $searchValue = isset($_GET['sr']) ? $iN->iN_Secure($_GET['sr']) : '';
}

$totalAgencyBoosts = $iN->iN_TotalAgencyBoosts($userID, $filter, $searchValue);
$totalPages = ceil($totalAgencyBoosts / $paginationLimit);
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_agency_boosts']) . '(' . $totalAgencyBoosts . ')'; ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
            <div class="i_contents_section flex_ manage_margin_bottom">
                <div class="irow_box_right irow_box_right_style">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['boost_filter']); ?></div>
                    <div class="campaign_filter_select">
                        <select class="i_input flex_" id="agencyBoostFilter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['boost_filter_all']); ?></option>
                            <option value="active" <?php echo $filter === 'active' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['boost_filter_active']); ?></option>
                            <option value="expired" <?php echo $filter === 'expired' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['agency_boost_filter_expired']); ?></option>
                            <option value="disabled" <?php echo $filter === 'disabled' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['agency_boost_filter_disabled']); ?></option>
                        </select>
                    </div>
                </div>
                <div class="irow_box_right irow_box_right_style">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['search']); ?></div>
                    <input type="text" class="i_input flex_" id="srcMe" value="<?php echo iN_HelpSecure($searchValue); ?>">
                </div>
                <div class="irow_box_right flex_ tabing irow_box_right_styl">
                    <div class="i_nex_btn_btn search_vl"><?php echo iN_HelpSecure($LANG['search']); ?></div>
                </div>
                <div class="irow_box_right flex_ tabing irow_box_right_styl">
                    <div class="i_nex_btn_btn cleanup_agency_boosts"><?php echo iN_HelpSecure($LANG['cleanup_boosts']); ?></div>
                </div>
            </div>
            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>
            <?php
            $agencyBoosts = $iN->iN_ShowAllAgencyBoosts($userID, $paginationLimit, $pagep, $filter, $searchValue);
            if ($agencyBoosts) {
            ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_name']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['creator']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['duration_days']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['createdat']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['ends_at']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['clicks']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['action']); ?></th>
                        </tr>
                        <?php foreach ($agencyBoosts as $boost) {
                            $boostId = (int)($boost['ab_id'] ?? 0);
                            $agencyId = (int)($boost['agency_id_fk'] ?? 0);
                            $agencyName = $boost['agency_name'] ?? '';
                            $ownerId = (int)($boost['owner_iuid_fk'] ?? 0);
                            $ownerName = $boost['owner_fullname'] ?? $boost['owner_username'] ?? '';
                            $ownerAvatar = $iN->iN_UserAvatar($ownerId, $base_url);
                            $creatorName = $boost['i_user_fullname'] ?? $boost['i_username'] ?? '';
                            $creatorUsername = $boost['i_username'] ?? '';
                            $creatorId = (int)($boost['creator_iuid_fk'] ?? 0);
                            $creatorAvatar = $iN->iN_UserAvatar($creatorId, $base_url);
                            $durationDays = (int)($boost['duration_days'] ?? 0);
                            $startAt = (int)($boost['start_at'] ?? 0);
                            $endAt = (int)($boost['end_at'] ?? 0);
                            $status = (string)($boost['status'] ?? '');
                            $statusLabel = $LANG['agency_boost_status_' . $status] ?? $status;
                            $statusBadgeClass = 'pe_active';
                            if ($status === 'active') {
                                $statusBadgeClass = 'p_active';
                            } elseif ($status === 'expired') {
                                $statusBadgeClass = 'p_s_passed';
                            }
                            $clickCount = (int)($boost['click_count'] ?? 0);
                            $agencyUrl = $agencyId > 0 ? $base_url . 'agency/' . $agencyId : '';
                            $creatorUrl = $creatorUsername !== '' ? $base_url . $creatorUsername : '';
                        ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($boostId ?: '-'); ?></td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_">
                                            <img src="<?php echo iN_HelpSecure($ownerAvatar, FILTER_VALIDATE_URL); ?>">
                                        </div>
                                        <div class="t_owner_user tabing flex_">
                                            <?php if ($agencyUrl !== '') { ?>
                                                <a class="truncated" href="<?php echo iN_HelpSecure($agencyUrl); ?>">
                                                    <?php echo iN_HelpSecure($agencyName !== '' ? $agencyName : $LANG['agency_not_found']); ?>
                                                </a>
                                            <?php } else { ?>
                                                <span class="truncated"><?php echo iN_HelpSecure($agencyName !== '' ? $agencyName : $LANG['agency_not_found']); ?></span>
                                            <?php } ?>
                                            <?php if ($ownerName !== '') { ?>
                                                <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($ownerName); ?></div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_">
                                            <img src="<?php echo iN_HelpSecure($creatorAvatar, FILTER_VALIDATE_URL); ?>">
                                        </div>
                                        <div class="t_owner_user tabing flex_">
                                            <?php if ($creatorUrl !== '') { ?>
                                                <a class="truncated" href="<?php echo iN_HelpSecure($creatorUrl); ?>">
                                                    <?php echo iN_HelpSecure($creatorName !== '' ? $creatorName : '-'); ?>
                                                </a>
                                            <?php } else { ?>
                                                <span class="truncated"><?php echo iN_HelpSecure($creatorName !== '' ? $creatorName : '-'); ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo iN_HelpSecure($durationDays); ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo $startAt > 0 ? iN_HelpSecure(date('Y-m-d H:i', $startAt)) : '-'; ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo $endAt > 0 ? iN_HelpSecure(date('Y-m-d H:i', $endAt)) : '-'; ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo iN_HelpSecure($clickCount); ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="<?php echo iN_HelpSecure($statusBadgeClass); ?> flex_ tabing"><?php echo iN_HelpSecure($statusLabel); ?></div>
                                    </div>
                                </td>
                                <td class="flex_ tabing_non_justify">
                                    <div class="flex_ tabing_non_justify">
                                        <?php if ($status === 'active' && $boostId > 0) { ?>
                                            <div class="delu border_one transition tabing flex_ agencyBoostAdminDisable" data-boost="<?php echo iN_HelpSecure($boostId); ?>">
                                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . $LANG['disable']; ?>
                                            </div>
                                        <?php } else { ?>
                                            <?php echo iN_HelpSecure('-'); ?>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php
            } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . $iN->iN_SelectedMenuIcon('54') . '</div><div class="n_c_t">' . $LANG['no_user_found'] . '</div></div>';
            }
            ?>
        </div>
        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages >= 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=1&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li><?php endif; ?>
                    <?php if ($pagep - 1 > 0): ?><li class="page"><a href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li><?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep); ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li><?php endif; ?>
                    <?php if ($pagep + 2 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li><?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo $totalPages; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_agency_boosts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>&sr=<?php echo iN_HelpSecure($searchValue); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/manageAgencyBoostsHandler.js?v=<?php echo iN_HelpSecure($version);?>" defer></script>
