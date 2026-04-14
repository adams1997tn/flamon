<?php
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : 'all';
$totalBlogs = $iN->iN_GetBlogListAdminCount($search, $statusFilter);
$totalPages = ($paginationLimit > 0) ? ceil($totalBlogs / $paginationLimit) : 1;
$pagep = isset($_GET["page-id"]) && preg_match('/^[0-9]+$/', $_GET["page-id"]) ? (int) $_GET["page-id"] : 1;
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container blog-list-page">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box flex_ tabing_non_justify">
            <div class="flex_ lm"><?php echo iN_HelpSecure($LANG['blog_management']); ?> (<?php echo iN_HelpSecure($totalBlogs); ?>)</div>
            <div class="flex_ lm title-actions">
                <a href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?new=1" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['create_blog_post']); ?></a>
            </div>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
            <form method="get" class="flex_ tabing_non_justify" style="gap:10px;">
                <input type="hidden" name="page-id" value="1">
                <input type="text" name="q" class="i_input flex_" placeholder="<?php echo iN_HelpSecure($LANG['blog_search_placeholder']); ?>" value="<?php echo iN_HelpSecure($search); ?>">
                <select name="status" class="i_input flex_">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['status']); ?></option>
                    <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['blog_published']); ?></option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['blog_draft']); ?></option>
                </select>
                <button type="submit" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['search'] ?? 'Search'); ?></button>
            </form>

            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>
            <?php
            $blogList = $iN->iN_GetBlogListAdmin($userID, $paginationLimit, $pagep, $search, $statusFilter);
            if ($blogList) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['blog_title']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['blog_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['blog_publish_date']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['blog_featured']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['blog_likes']); ?> / <?php echo iN_HelpSecure($LANG['blog_dislikes']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php
                        foreach ($blogList as $blog) {
                            $blogID = $blog['blog_id'];
                            $statusLabel = $blog['status'] === 'published' ? $LANG['blog_published'] : $LANG['blog_draft'];
                            $pubDate = $blog['published_at'] ? date('Y-m-d H:i', $blog['published_at']) : '-';
                            $featuredLabel = $blog['is_featured'] === '1' ? ($LANG['yes'] ?? 'Yes') : ($LANG['no'] ?? 'No');
                            $reactions = $iN->iN_BlogReactionCounts((int)$blogID);
                            ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($blogID); ?></td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($blog['title']); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($pubDate); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($featuredLabel); ?></div>
                                </td>
                                <td class="see_post_details">
                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($reactions['like']); ?> / <?php echo iN_HelpSecure($reactions['dislike']); ?></div>
                                </td>
                                <td class="flex_ tabing">
                                    <div class="flex_ tabing_non_justify blog-actions">
                                        <a class="i_nex_btn_btn blog-edit-btn" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?id=<?php echo iN_HelpSecure($blogID); ?>">
                                            <span class="btn-icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?></span>
                                            <span><?php echo iN_HelpSecure($LANG['edit']); ?></span>
                                        </a>
                                        <?php if ($blog['status'] === 'published') { ?>
                                            <a class="ghost_btn blog-view-btn" target="_blank" href="<?php echo route_url('blog/' . $blog['slug']); ?>">
                                                <span class="btn-icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10')); ?></span>
                                                <span><?php echo iN_HelpSecure($LANG['view_site']); ?></span>
                                            </a>
                                        <?php } else { ?>
                                            <div class="ghost_btn blog-view-btn disabled"><?php echo iN_HelpSecure($LANG['blog_view_unpublished'] ?? 'Publish to view'); ?></div>
                                        <?php } ?>
                                        <div class="border_one transition deleteBlog blog-delete-btn" data-id="<?php echo iN_HelpSecure($blogID); ?>" data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>">
                                            <span class="btn-icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?></span>
                                            <span><?php echo iN_HelpSecure($LANG['delete']); ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . iN_HelpSecure($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['blog_no_posts'] . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages > 0): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev">
                            <a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>">
                                <?php echo iN_HelpSecure($LANG['preview_page']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (iN_HelpSecure($pagep) > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=1&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if (iN_HelpSecure($pagep) - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep); ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo $totalPages; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>admin/blog_posts?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>&q=<?php echo iN_HelpSecure(urlencode($search)); ?>&status=<?php echo iN_HelpSecure($statusFilter); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
