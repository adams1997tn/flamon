<?php
if ($iN->iN_CheckIsAdmin($userID) != 1) {
    echo iN_HelpSecure($LANG['noway_desc']);
    return;
}

$totalOverlays = (int) DB::col("SELECT COUNT(*) FROM i_obs_overlays");
$totalActiveOverlays = (int) DB::col("SELECT COUNT(*) FROM i_obs_overlays WHERE is_active = '1'");
$totalCreators = (int) DB::col("SELECT COUNT(DISTINCT creator_id_fk) FROM i_obs_overlays");

$perPage = (int) $paginationLimit;
if ($perPage < 1) {
    $perPage = 20;
}

$pagep = isset($_GET['page-id']) ? (int) $_GET['page-id'] : 1;
if ($pagep < 1) {
    $pagep = 1;
}

$totalPages = $perPage > 0 ? (int) ceil($totalOverlays / $perPage) : 1;
if ($totalPages < 1) {
    $totalPages = 1;
}
if ($pagep > $totalPages) {
    $pagep = $totalPages;
}

$startFrom = ($pagep - 1) * $perPage;
$overlayRows = DB::all(
    "SELECT O.*, U.i_username, U.i_user_fullname
     FROM i_obs_overlays O
     LEFT JOIN i_users U ON U.iuid = O.creator_id_fk
     ORDER BY O.obs_overlay_id DESC
     LIMIT {$startFrom}, {$perPage}"
);
?>
<div class="i_contents_container obs-overlay-page">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['obs_overlay_admin_list_title']) . ' (' . iN_HelpSecure($totalOverlays) . ')'; ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="obs_admin_info_note">
                <?php echo iN_HelpSecure($LANG['obs_overlay_admin_list_desc']); ?>
            </div>

            <div class="obs_admin_stats">
                <div class="obs_admin_stat_card border_one">
                    <div class="obs_admin_stat_title"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_total']); ?></div>
                    <div class="obs_admin_stat_value"><?php echo iN_HelpSecure($totalOverlays); ?></div>
                </div>
                <div class="obs_admin_stat_card border_one">
                    <div class="obs_admin_stat_title"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_active']); ?></div>
                    <div class="obs_admin_stat_value"><?php echo iN_HelpSecure($totalActiveOverlays); ?></div>
                </div>
                <div class="obs_admin_stat_card border_one">
                    <div class="obs_admin_stat_title"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_creators']); ?></div>
                    <div class="obs_admin_stat_value"><?php echo iN_HelpSecure($totalCreators); ?></div>
                </div>
            </div>

            <?php if (!empty($overlayRows)) { ?>
                <div class="i_overflow_x_auto obs_admin_table_wrap">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['obs_overlay_admin_creator']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['obs_overlay_admin_token']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['obs_overlay_admin_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['obs_overlay_admin_created']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['obs_overlay_admin_updated']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['obs_overlay_admin_actions']); ?></th>
                        </tr>
                        <?php foreach ($overlayRows as $overlayRow) {
                            $overlayId = (int) ($overlayRow['obs_overlay_id'] ?? 0);
                            $creatorId = (int) ($overlayRow['creator_id_fk'] ?? 0);
                            $creatorUsername = (string) ($overlayRow['i_username'] ?? '');
                            $creatorFullName = trim((string) ($overlayRow['i_user_fullname'] ?? ''));
                            $creatorLabel = $creatorFullName !== '' ? $creatorFullName : $creatorUsername;
                            if ($creatorLabel === '') {
                                $creatorLabel = '#'.$creatorId;
                            }
                            $overlayToken = (string) ($overlayRow['overlay_token'] ?? '');
                            $overlayStatus = (string) ($overlayRow['is_active'] ?? '0') === '1'
                                ? (string) $LANG['obs_overlay_active']
                                : (string) $LANG['obs_overlay_inactive'];
                            $createdAt = isset($overlayRow['created_at']) && (int)$overlayRow['created_at'] > 0
                                ? date('Y-m-d H:i:s', (int)$overlayRow['created_at'])
                                : '-';
                            $updatedAt = isset($overlayRow['updated_at']) && (int)$overlayRow['updated_at'] > 0
                                ? date('Y-m-d H:i:s', (int)$overlayRow['updated_at'])
                                : '-';
                            $overlayUrl = route_url('obs/overlay/' . $overlayToken);
                        ?>
                        <tr class="transition trhover">
                            <td><?php echo iN_HelpSecure($overlayId); ?></td>
                            <td class="see_post_details">
                                <div class="obs_admin_creator"><?php echo iN_HelpSecure($creatorLabel); ?></div>
                                <?php if ($creatorUsername !== '') { ?>
                                    <div class="obs_admin_creator_meta">@<?php echo iN_HelpSecure($creatorUsername); ?> (#<?php echo iN_HelpSecure($creatorId); ?>)</div>
                                <?php } else { ?>
                                    <div class="obs_admin_creator_meta">#<?php echo iN_HelpSecure($creatorId); ?></div>
                                <?php } ?>
                            </td>
                            <td class="see_post_details">
                                <div class="obs_admin_token"><?php echo iN_HelpSecure($overlayToken); ?></div>
                            </td>
                            <td class="see_post_details">
                                <span class="obs_admin_status_tag"><?php echo iN_HelpSecure($overlayStatus); ?></span>
                            </td>
                            <td><?php echo iN_HelpSecure($createdAt); ?></td>
                            <td><?php echo iN_HelpSecure($updatedAt); ?></td>
                            <td class="flex_ tabing_non_justify">
                                <a class="seePost c2 border_one transition tabing flex_" href="<?php echo iN_HelpSecure($overlayUrl); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')) . iN_HelpSecure($LANG['obs_overlay_admin_open']); ?>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else { ?>
                <div class="no_creator_f_wrap flex_ tabing">
                    <div class="no_c_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?></div>
                    <div class="n_c_t"><?php echo iN_HelpSecure($LANG['obs_overlay_admin_no_overlays']); ?></div>
                </div>
            <?php } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages >= 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($totalPages); ?>"><?php echo iN_HelpSecure($totalPages); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/obs_overlay_list?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
