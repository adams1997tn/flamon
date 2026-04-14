<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include "layouts/header/meta.php";
        include "layouts/header/css.php";
        include "layouts/header/javascripts.php";
    ?>
</head>
<body>
<?php if ($logedIn == 0) {include 'layouts/login_form.php';}?>
<?php include "layouts/header/header.php";?>
<?php
if (!function_exists('dizzy_blog_cover_url')) {
    function dizzy_blog_cover_url(array $blog, $iN, string $baseUrl, bool $large = false): string {
        if (!empty($blog['cover_url']) && filter_var($blog['cover_url'], FILTER_VALIDATE_URL)) {
            return $blog['cover_url'];
        }
        if (!empty($blog['cover_upload_id'])) {
            $file = $iN->iN_GetUploadedFileDetails($blog['cover_upload_id']);
            if ($file) {
                $path = $file['upload_tumbnail_file_path'] ?? $file['uploaded_file_path'] ?? '';
                if ($path !== '') {
                    $public = str_replace(APP_ROOT_PATH, rtrim($baseUrl, '/'), $path);
                    return str_replace(DIRECTORY_SEPARATOR, '/', $public);
                }
            }
        }
        $placeholderId = (int) ($blog['blog_id'] ?? 1);
        $size = $large ? '1400/800' : '900/600';
        return 'https://picsum.photos/' . $size . '?random=' . $placeholderId;
    }
}
if (!function_exists('dizzy_blog_author_meta')) {
    function dizzy_blog_author_meta(array $post, $iN, string $baseUrl, array $lang, array &$cache): array {
        $authorId = (int) ($post['author_id'] ?? 0);
        if ($authorId > 0 && isset($cache[$authorId])) {
            $author = $cache[$authorId];
        } elseif ($authorId > 0) {
            $author = $iN->iN_GetUserDetails($authorId) ?: [];
            $cache[$authorId] = $author;
        } else {
            $author = [];
        }
        $name = $author['i_user_fullname'] ?? ($post['author_name'] ?? ($lang['blog'] ?? 'Blog'));
        $avatar = !empty($author['iuid']) ? $iN->iN_UserAvatar($author['iuid'], $baseUrl) : '';
        if ($avatar === '') {
            $placeholderId = $authorId > 0 ? $authorId : ((int) ($post['blog_id'] ?? 1));
            $avatar = 'https://picsum.photos/96/96?random=' . $placeholderId;
        }
        $role = $lang['blog_author_role'] ?? 'Author';
        return [
            'name' => $name,
            'avatar' => $avatar,
            'role' => $role
        ];
    }
}
$heroBlog = null;
if ($searchQuery !== '') {
    if (!empty($blogPosts) && isset($blogPosts[0])) {
        $heroBlog = $blogPosts[0];
    }
} elseif (!empty($featuredBlog) && isset($featuredBlog[0])) {
    $heroBlog = $featuredBlog[0];
} elseif (!empty($blogPosts) && isset($blogPosts[0])) {
    $heroBlog = $blogPosts[0];
}
function dizzy_blog_excerpt(string $text, int $limit = 160): string {
    $clean = trim(strip_tags($text));
    if (function_exists('mb_substr')) {
        $clean = mb_substr($clean, 0, $limit, "utf-8");
    } else {
        $clean = substr($clean, 0, $limit);
    }
    return $clean;
}
$authorCache = [];
$listingPosts = [];
if (!empty($blogPosts)) {
    foreach ($blogPosts as $postItem) {
        if ($heroBlog && isset($heroBlog['blog_id'], $postItem['blog_id']) && (int) $heroBlog['blog_id'] === (int) $postItem['blog_id']) {
            continue;
        }
        $listingPosts[] = $postItem;
    }
}
$heroAuthor = $heroBlog ? dizzy_blog_author_meta($heroBlog, $iN, $base_url, $LANG, $authorCache) : null;
?>
<div class="wrapper blog-wrapper blog-index-page">
    <div class="blog-index-shell">
        <section class="blog-index-hero">
            <div class="blog-index-head">
                <h1><?php echo iN_HelpSecure($LANG['blog_hero_title']); ?></h1>
                <p><?php echo iN_HelpSecure($LANG['blog_hero_subtitle']); ?></p>
            </div>
            <form class="blog-index-search" method="get" action="<?php echo route_url('blog'); ?>">
                <input type="text" name="q" value="<?php echo iN_HelpSecure($searchQuery); ?>" placeholder="<?php echo iN_HelpSecure($LANG['blog_search_placeholder']); ?>">
                <button type="submit" aria-label="<?php echo iN_HelpSecure($LANG['search'] ?? 'Search'); ?>">
                    <span class="blog-search-icon" aria-hidden="true"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?></span>
                </button>
            </form>
        </section>

        <?php if ($heroBlog) { ?>
            <a class="blog-featured-card" href="<?php echo route_url('blog/' . $heroBlog['slug']); ?>">
                <div class="blog-featured-media">
                    <img src="<?php echo iN_HelpSecure(dizzy_blog_cover_url($heroBlog, $iN, $base_url, true)); ?>" alt="<?php echo iN_HelpSecure($heroBlog['title']); ?>">
                    <div class="blog-featured-gradient"></div>
                    <div class="blog-featured-content">
                        <div class="blog-featured-date"><?php echo date('M d, Y', $heroBlog['published_at'] ?? $heroBlog['created_at']); ?></div>
                        <h2><?php echo iN_HelpSecure($heroBlog['title']); ?></h2>
                        <p><?php echo iN_HelpSecure(dizzy_blog_excerpt($heroBlog['excerpt'] ?: $heroBlog['content_html'], 160)); ?></p>
                        <?php if ($heroAuthor) { ?>
                            <div class="blog-featured-author">
                                <img src="<?php echo iN_HelpSecure($heroAuthor['avatar']); ?>" alt="<?php echo iN_HelpSecure($heroAuthor['name']); ?>">
                                <div>
                                    <div class="blog-featured-author-name"><?php echo iN_HelpSecure($heroAuthor['name']); ?></div>
                                    <div class="blog-featured-author-role"><?php echo iN_HelpSecure($heroAuthor['role']); ?></div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </a>
        <?php } elseif ($searchQuery !== '') { ?>
            <div class="blog-search-empty"><?php echo iN_HelpSecure($LANG['blog_no_results']); ?></div>
        <?php } ?>

        <section class="blog-list">
            <?php
            if ($listingPosts) {
                foreach ($listingPosts as $post) {
                    $postAuthor = dizzy_blog_author_meta($post, $iN, $base_url, $LANG, $authorCache);
                    ?>
                    <article class="blog-list-card">
                        <a href="<?php echo route_url('blog/' . $post['slug']); ?>" class="blog-list-thumb">
                            <img src="<?php echo iN_HelpSecure(dizzy_blog_cover_url($post, $iN, $base_url)); ?>" alt="<?php echo iN_HelpSecure($post['title']); ?>">
                        </a>
                        <div class="blog-list-body">
                            <h3><a href="<?php echo route_url('blog/' . $post['slug']); ?>"><?php echo iN_HelpSecure($post['title']); ?></a></h3>
                            <p class="blog-list-excerpt"><?php echo iN_HelpSecure(dizzy_blog_excerpt($post['excerpt'] ?: $post['content_html'], 140)); ?></p>
                            <div class="blog-list-footer">
                                <div class="blog-list-meta"><?php echo date('M d, Y', $post['published_at'] ?? $post['created_at']); ?></div>
                                <div class="blog-list-author">
                                    <img src="<?php echo iN_HelpSecure($postAuthor['avatar']); ?>" alt="<?php echo iN_HelpSecure($postAuthor['name']); ?>">
                                    <div>
                                        <div class="blog-list-author-name"><?php echo iN_HelpSecure($postAuthor['name']); ?></div>
                                        <div class="blog-list-author-role"><?php echo iN_HelpSecure($postAuthor['role']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php
                }
            } else {
                $emptyText = $searchQuery !== '' ? ($LANG['blog_no_results'] ?? $LANG['blog_no_posts']) : $LANG['blog_no_posts'];
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . iN_HelpSecure($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . iN_HelpSecure($emptyText) . '</div></div>';
            }
            ?>
        </section>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages > 1): ?>
                <ul class="pagination">
                    <?php if ($pageNumber > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo route_url('blog') . '?page=' . ($pageNumber - 1) . '&q=' . urlencode($searchQuery); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>
                    <li class="currentpage active"><a class="transition" href="<?php echo route_url('blog') . '?page=' . $pageNumber . '&q=' . urlencode($searchQuery); ?>"><?php echo iN_HelpSecure($pageNumber); ?></a></li>
                    <?php if ($pageNumber < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo route_url('blog') . '?page=' . ($pageNumber + 1) . '&q=' . urlencode($searchQuery); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="footer_container_out"><?php include("layouts/footer.php");?></div>
</body>
</html>
