<?php
$statuses = ['pending', 'failed'];
$totalScheduled = $iN->iN_CountScheduledPosts($statuses);
$totalPages = ceil($totalScheduled / $paginationLimit);
if (isset($_GET["page-id"])) {
    $pagep = isset($_GET["page-id"]) ? (int)$_GET["page-id"] : 1;
    if (!preg_match('/^[0-9]+$/', $pagep)) {
        $pagep = 1;
    }
} else {
    $pagep = 1;
}
$scheduledPosts = $iN->iN_ListScheduledPosts($statuses, $paginationLimit, $pagep);
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : '';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['scheduled_badge']); ?> (<?php echo (int)$totalScheduled; ?>)
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf" data-scheduled-token="<?php echo iN_HelpSecure($csrfToken); ?>">
            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>
            <?php if ($scheduledPosts) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['post_owner']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['schedule_time']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['attempts'] ?? 'Attempts'); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php foreach ($scheduledPosts as $row) {
                            $postId = $row['post_id_fk'] ?? null;
                            $ownerId = $row['post_owner_id'] ?? null;
                            $ownerAvatar = $iN->iN_UserAvatar($ownerId, $base_url);
                            $ownerName = $row['i_username'] ?? '';
                            $ownerFullName = $row['i_user_fullname'] ?? '';
                            $runAt = isset($row['run_at']) ? date('M d, Y H:i', (int)$row['run_at']) : '';
                            $status = $row['status'] ?? 'pending';
                            $statusBadge = '<div class="p_active flex_ tabing">'.$iN->iN_SelectedMenuIcon('69').iN_HelpSecure($status).'</div>';
                            if ($status === 'failed') {
                                $statusBadge = '<div class="pe_active flex_ tabing">'.$iN->iN_SelectedMenuIcon('28').iN_HelpSecure($status).'</div>';
                            } elseif ($status === 'processing') {
                                $statusBadge = '<div class="p_active flex_ tabing">'.$iN->iN_SelectedMenuIcon('73').iN_HelpSecure($status).'</div>';
                            }
                            $attempts = isset($row['attempts']) ? (int)$row['attempts'] : 0;
                            $lastError = '';
                            if (!empty($row['last_error'])) {
                                $lastError = '<div class="rec_not box_not_padding_top">'.iN_HelpSecure(mb_substr($row['last_error'], 0, 120)).'</div>';
                            }
                            ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($postId); ?></td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_"><img src="<?php echo iN_HelpSecure($ownerAvatar); ?>"></div>
                                        <div class="t_owner_user tabing flex_">
                                            <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . $ownerName; ?>"><?php echo iN_HelpSecure($ownerFullName); ?></a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex_ tabing_non_justify">
                                        <div class="tim flex_ tabing"><?php echo iN_HelpSecure($runAt); ?></div>
                                    </div>
                                    <?php echo $lastError; ?>
                                </td>
                                <td><?php echo $statusBadge; ?></td>
                                <td><?php echo $attempts; ?></td>
                                <td class="flex_ tabing">
                                    <div class="flex_ tabing_non_justify">
                                        <div class="seePost c2 border_one transition publishScheduledNow" data-post="<?php echo iN_HelpSecure($postId); ?>">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?> <?php echo iN_HelpSecure($LANG['publish_now']); ?>
                                        </div>
                                        <div class="delp border_one transition cancelScheduledPost" data-post="<?php echo iN_HelpSecure($postId); ?>">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?> <?php echo iN_HelpSecure($LANG['cancel_schedule']); ?>
                                        </div>
                                        <div class="delp border_one transition deleteScheduledPost" data-post="<?php echo iN_HelpSecure($postId); ?>">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?> <?php echo iN_HelpSecure($LANG['delete_post']); ?>
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
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>">
                                <?php echo iN_HelpSecure($LANG['preview_page']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (iN_HelpSecure($pagep) > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if (iN_HelpSecure($pagep) - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/scheduled_posts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
