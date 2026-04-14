<?php
$searchUser = '';
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
if (!in_array($filter, ['all', 'active', 'passive', 'orphan'], true)) {
    $filter = 'all';
}

if (isset($_GET['sr'])) {
    $searchUser = isset($_GET['sr']) ? $iN->iN_Secure($_GET['sr']) : '';
}

$totalBoostedPost = $iN->iN_TotalBoostedPost($userID, $filter, $searchUser);
$totalPages = ceil($totalBoostedPost / $paginationLimit);
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_boosted_posts']) . '(' . $totalBoostedPost . ')'; ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
            <div class="i_contents_section flex_ manage_margin_bottom">
                <div class="irow_box_right irow_box_right_style">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['boost_filter']); ?></div>
                    <div class="campaign_filter_select">
                        <select class="i_input flex_" id="boostFilter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['boost_filter_all']); ?></option>
                            <option value="active" <?php echo $filter === 'active' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['boost_filter_active']); ?></option>
                            <option value="passive" <?php echo $filter === 'passive' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['boost_filter_passive']); ?></option>
                            <option value="orphan" <?php echo $filter === 'orphan' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['boost_filter_orphan']); ?></option>
                        </select>
                    </div>
                </div>
                <div class="irow_box_right irow_box_right_style">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['search']); ?></div>
                    <input type="text" class="i_input flex_" id="srcMe" value="<?php echo iN_HelpSecure($searchUser); ?>">
                </div>
                <div class="irow_box_right flex_ tabing irow_box_right_styl">
                    <div class="i_nex_btn_btn search_vl"><?php echo iN_HelpSecure($LANG['search']); ?></div>
                </div>
                <div class="irow_box_right flex_ tabing irow_box_right_styl">
                    <div class="i_nex_btn_btn cleanup_boosts"><?php echo iN_HelpSecure($LANG['cleanup_boosts']); ?></div>
                </div>
            </div>
            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>
            <?php
            $allBoostedPosts = $iN->iN_ShowAllBoostedPost($userID, $paginationLimit, $pagep, $filter, $searchUser);
            if ($allBoostedPosts) {
            ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['boosted_post']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['post_owner']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['createdat']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['ends_at']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['boost_price']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['boost_type']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['tobeshown']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['shown']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['remaining']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['action']); ?></th>
                        </tr>
                        <?php
                        foreach ($allBoostedPosts as $sbData) {
                            $boostID = $sbData['boost_id'] ?? null;
                            $boostOwner = $sbData['iuid_fk'] ?? ($sbData['post_owner_id'] ?? null);
                            $boostStatus = $sbData['status'] ?? null;
                            $boostCreated = $sbData['started_at'] ?? null;
                            $boostedPostID = $sbData['post_id_fk'] ?? ($sbData['post_id'] ?? null);
                            $boostType = $sbData['boost_type'] ?? null;
                            $boostEndAt = $sbData['end_at'] ?? null;
                            $boostTypeData = $boostType ? $iN->iN_GetBoostPostDetails($boostType) : null;
                            $planAmount = $boostTypeData['plan_amount'] ?? null;
                            $planIcon = $boostTypeData['plan_icon'] ?? null;
                            $planNameKey = $boostTypeData['plan_name_key'] ?? null;
                            $planViewTime = $boostTypeData['view_time'] ?? null;
                            $urlSlug = $sbData['url_slug'] ?? null;
                            $sSlugUrl = $base_url . 'post/' . $urlSlug . '_' . $boostedPostID;
                            $crTime = $boostCreated ? date('Y-m-d H:i:s', $boostCreated) : null;
                            $userAvatar = $iN->iN_UserAvatar($boostOwner, $base_url);
                            $userUserName = $sbData['i_username'] ?? null;
                            $userUserFullName = $sbData['i_user_fullname'] ?? null;
                            $seenCount = isset($sbData['seen_count']) ? (int) $sbData['seen_count'] : 0;
                            $totalToBeShown = isset($sbData['view_count']) ? (int) $sbData['view_count'] : (int) $planViewTime;
                            $remaining = ($totalToBeShown > 0) ? max(0, $totalToBeShown - $seenCount) : 0;
                            $endAt = (int) ($boostEndAt ?? 0);
                            if ($endAt < 1 && !empty($boostCreated) && isset($boostPostExpireDays) && (int) $boostPostExpireDays > 0) {
                                $endAt = (int) $boostCreated + ((int) $boostPostExpireDays * 86400);
                            }
                            $expiredByTime = ($endAt > 0 && $endAt <= time());
                            $expiredByViews = ($totalToBeShown > 0 && $seenCount >= $totalToBeShown);
                            $canToggle = ($boostID && !$expiredByTime && !$expiredByViews);
                        ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($boostID ?: '-'); ?></td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_">
                                            <img src="<?php echo iN_HelpSecure($userAvatar, FILTER_VALIDATE_URL); ?>">
                                        </div>
                                        <div class="t_owner_user tabing flex_">
                                            <a class="truncated" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL) . $userUserName; ?>">
                                                <?php echo iN_HelpSecure($userUserFullName); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify see_post_details_a">
                                        <?php if ($urlSlug && $boostedPostID): ?>
                                            <a href="<?php echo iN_HelpSecure($sSlugUrl, FILTER_VALIDATE_URL); ?>"><?php echo iN_HelpSecure($LANG['see_post']); ?></a>
                                        <?php else: ?>
                                            <?php echo iN_HelpSecure('-'); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing">
                                            <?php echo $crTime ? (html_entity_decode($iN->iN_SelectedMenuIcon('73')) . ' ' . TimeAgo::ago($crTime, date('Y-m-d H:i:s'))) : '-'; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing">
                                            <?php echo $endAt > 0 ? iN_HelpSecure(date('Y-m-d H:i:s', $endAt)) : '-'; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing">
                                            <?php echo iN_HelpSecure($planAmount) . $currencys[$defaultCurrency]; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="i_sub_not_check_box type_news flex_ tabing_non_justify hannib positionRelative">
                                            <?php echo html_entity_decode($planIcon) . iN_HelpSecure($planNameKey); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo iN_HelpSecure($totalToBeShown); ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo iN_HelpSecure($seenCount); ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo iN_HelpSecure($remaining); ?></div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="bootTim flex_ tabing">
                                            <?php if ($boostID): ?>
                                                <label class="el-switch el-switch-yellow" for="uPBoost<?php echo iN_HelpSecure($boostID); ?>">
                                                    <input type="checkbox" name="boostStatus" class="uPBoost" id="uPBoost<?php echo iN_HelpSecure($boostID); ?>" data-id="<?php echo iN_HelpSecure($boostID); ?>" data-type="uPBoost" <?php echo iN_HelpSecure($boostStatus) === 'yes' ? 'value="0" checked="checked"' : 'value="1"'; ?> <?php echo $canToggle ? '' : 'disabled="disabled"'; ?>>
                                                    <span class="el-switch-style"></span>
                                                </label>
                                            <?php else: ?>
                                                <?php echo iN_HelpSecure($LANG['boost_filter_orphan']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="flex_ tabing_non_justify">
                                    <div class="flex_ tabing_non_justify">
                                        <?php if ($boostID): ?>
                                            <div class="delu del_BoostPopUP border_one transition tabing flex_ delete" id="<?php echo iN_HelpSecure($boostID); ?>">
                                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . $LANG['delete']; ?>
                                            </div>
                                        <?php else: ?>
                                            <?php echo iN_HelpSecure('-'); ?>
                                        <?php endif; ?>
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
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=1&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li><?php endif; ?>
                    <?php if ($pagep - 1 > 0): ?><li class="page"><a href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li><?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep); ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li><?php endif; ?>
                    <?php if ($pagep + 2 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li><?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo $totalPages; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/manage_boosted_posts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>&sr=<?php echo iN_HelpSecure($searchUser); ?>&st=<?php echo iN_HelpSecure($filter); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/manageBoostedPostHandler.js?v=<?php echo iN_HelpSecure($version);?>" defer></script>
