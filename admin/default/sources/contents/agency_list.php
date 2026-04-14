<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$totalAgencies = (int) DB::col("SELECT COUNT(*) FROM i_agencies");
$totalPages = $paginationLimit > 0 ? (int) ceil($totalAgencies / $paginationLimit) : 1;

if (isset($_GET["page-id"])) {
    $pagep = (int)$_GET["page-id"];
    if ($pagep <= 0) { $pagep = 1; }
} else {
    $pagep = 1;
}
$startFrom = ($pagep - 1) * $paginationLimit;

$agencies = DB::all(
    "SELECT A.*, U.i_username, U.i_user_fullname FROM i_agencies A LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk ORDER BY A.agency_id DESC LIMIT $startFrom, $paginationLimit"
);
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['agency_module_title']) . '(' . iN_HelpSecure($totalAgencies) . ')'; ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_contents_section flex_ manage_margin_bottom">
                <a href="<?php echo iN_HelpSecure($base_url); ?>admin/agencies?requests=1">
                    <div class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['agency_create_requests']); ?></div>
                </a>
            </div>

            <?php if (!empty($agencies)) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_name']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_owner']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['agency_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['registered_time']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php foreach ($agencies as $agency) {
                            $agencyId = (int)($agency['agency_id'] ?? 0);
                            $ownerId = (int)($agency['owner_iuid_fk'] ?? 0);
                            $ownerName = $agency['i_user_fullname'] ?? $agency['i_username'] ?? '';
                            $createdAt = isset($agency['agency_created_at']) ? date('Y-m-d H:i:s', (int)$agency['agency_created_at']) : '';
                            $statusLabel = ($agency['agency_status'] ?? '') === 'active' ? $LANG['agency_status_active'] : $LANG['agency_status_inactive'];
                            $editUrl = $base_url . 'admin/agencies?agency=' . $agencyId;
                        ?>
                        <tr class="transition trhover">
                            <td><?php echo iN_HelpSecure($agencyId); ?></td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify sim truncated"><?php echo iN_HelpSecure($agency['agency_name'] ?? ''); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify sim"><?php echo iN_HelpSecure($ownerName); ?> (#<?php echo iN_HelpSecure($ownerId); ?>)</div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify">
                                    <?php echo iN_HelpSecure($createdAt); ?>
                                </div>
                            </td>
                            <td class="flex_ tabing_non_justify">
                                <a class="seePost c2 border_one transition tabing flex_" href="<?php echo iN_HelpSecure($editUrl, FILTER_VALIDATE_URL); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')) . iN_HelpSecure($LANG['edit']); ?>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . html_entity_decode($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['agency_no_agencies'] . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages >= 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/agencies?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
