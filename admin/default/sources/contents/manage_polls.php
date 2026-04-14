<?php
$totalPollPosts = $iN->iN_CalculateAllPollPosts();
$totalPages = $paginationLimit > 0 ? ceil($totalPollPosts / $paginationLimit) : 1;
if (isset($_GET["page-id"])) {
    $pagep = isset($_GET["page-id"]) ? (int)$_GET["page-id"] : 1;
    if (!preg_match('/^[0-9]+$/', $pagep)) {
        $pagep = 1;
    }
} else {
    $pagep = 1;
}
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['poll_system']) . '(' . $totalPollPosts . ')'; ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>
            <?php
            $polls = $iN->iN_AllPollPostsList($userID, $paginationLimit, $pagep);
            if ($polls) {
                ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['post_owner']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['question']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['poll_total_votes']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php
                        foreach ($polls as $pollPost) {
                            $postID = $pollPost['post_id'];
                            $postOwnerID = $pollPost['post_owner_id'];
                            $postOwnerAvatar = $iN->iN_UserAvatar($postOwnerID, $base_url);
                            $postOwnerUserName = $pollPost['i_username'];
                            $postOwnerUserFullName = $pollPost['i_user_fullname'];
                            $postCreatedTime = $pollPost['post_created_time'];
                            $pollQuestion = $pollPost['post_text'];
                            $pollStatus = $pollPost['post_status'];
                            $pollDetails = $iN->iN_GetPollDetailsByPostId($postID, $userID);
                            $pollTotalVotes = isset($pollDetails['total_votes']) ? (int)$pollDetails['total_votes'] : 0;
                            $pollState = isset($pollDetails['status']) ? $pollDetails['status'] : 'active';

                            $crTime = date('Y-m-d H:i:s', $postCreatedTime);
                            $postStatusText = '<div class="p_active flex_ tabing">' . $iN->iN_SelectedMenuIcon('69') . $LANG['active'] . '</div>';
                            if ($pollStatus == '2') {
                                $postStatusText = '<div class="pe_active flex_ tabing">' . $iN->iN_SelectedMenuIcon('115') . $LANG['pending_approve'] . '</div>';
                            }
                            if ($pollState !== 'active') {
                                $postStatusText = '<div class="pe_active flex_ tabing">' . $iN->iN_SelectedMenuIcon('10') . iN_HelpSecure($pollState) . '</div>';
                            }

                            $seePostButton = $base_url . 'post/' . $pollPost['url_slug'] . '_' . $postID;
                            ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($postID); ?></td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_"><img src="<?php echo iN_HelpSecure($postOwnerAvatar); ?>"></div>
                                        <div class="t_owner_user tabing flex_">
                                            <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . $postOwnerUserName; ?>"><?php echo iN_HelpSecure($postOwnerUserFullName); ?></a>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify truncated_two"><?php echo iN_HelpSecure(mb_strimwidth($pollQuestion, 0, 120, '...')); ?></div>
                                    <div class="tim flex_ tabing"><?php echo iN_HelpSecure($iN->iN_SelectedMenuIcon('73')) . ' ' . TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo html_entity_decode($postStatusText); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($pollTotalVotes); ?></div>
                                </td>
                                <td class="flex_ tabing">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="delp border_one transition" id="<?php echo iN_HelpSecure($postID); ?>">
                                            <?php echo iN_HelpSecure($iN->iN_SelectedMenuIcon('28')) . $LANG['delete']; ?>
                                        </div>
                                        <div class="seePost c2 border_one transition">
                                            <a href="<?php echo iN_HelpSecure($seePostButton); ?>" target="_blank">
                                                <?php echo iN_HelpSecure($iN->iN_SelectedMenuIcon('27')) . $LANG['edit_post']; ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . iN_HelpSecure($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['no_post_will_be_shown'] . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages > 0): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev">
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>">
                                <?php echo iN_HelpSecure($LANG['preview_page']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (iN_HelpSecure($pagep) > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php
                    $lastNumbers = [];
                    if ($pagep >= 3) {
                        $pagep2 = $pagep - 1;
                        $pagep3 = $pagep - 2;
                        $lastNumbers[] = $pagep3;
                        $lastNumbers[] = $pagep2;
                    }
                    if (!empty($lastNumbers)) {
                        foreach ($lastNumbers as $lastNumber) {
                            if ($lastNumber > 0) {
                                ?>
                                <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($lastNumber); ?>"><?php echo iN_HelpSecure($lastNumber); ?></a></li>
                                <?php
                            }
                        }
                    }
                    ?>
                    <li class="currentpage"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep != $totalPages): ?>
                        <li class="page">
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($pagep + 1); ?>">
                                <?php echo iN_HelpSecure($pagep + 1); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (($pagep + 1) < $totalPages): ?>
                        <li class="page">
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($pagep + 2); ?>">
                                <?php echo iN_HelpSecure($pagep + 2); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end">
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($totalPages); ?>">
                                <?php echo iN_HelpSecure($totalPages); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($pagep < $totalPages): ?>
                        <li class="next">
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/manage_polls?page-id=<?php echo iN_HelpSecure($pagep + 1); ?>">
                                <?php echo iN_HelpSecure($LANG['next_page']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>

    </div>
</div>
