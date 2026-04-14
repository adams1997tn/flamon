<?php
if (!function_exists('dizzy_blog_cover_url')) {
    function dizzy_blog_cover_url(array $blog, $iN, string $baseUrl): string
    {
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
        return $baseUrl . 'img/placeholder.png';
    }
}

function dizzy_blog_excerpt(string $text, int $limit = 140): string
{
    $clean = trim(strip_tags($text));
    if (function_exists('mb_substr')) {
        $clean = mb_substr($clean, 0, $limit, 'utf-8');
    } else {
        $clean = substr($clean, 0, $limit);
    }
    return $clean;
}

if (!function_exists('dizzy_blog_author_meta')) {
    function dizzy_blog_author_meta(array $post, $iN, string $baseUrl, array $lang, array &$cache): array
    {
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

$coverUrl = dizzy_blog_cover_url($blogPost, $iN, $base_url);
$publishedDate = date('M d, Y', $blogPost['published_at'] ?? $blogPost['created_at']);
$authorName = isset($blogAuthor['i_user_fullname']) ? $blogAuthor['i_user_fullname'] : ($LANG['blog'] ?? 'Blog');
$authorAvatar = isset($blogAuthor['iuid']) ? $iN->iN_UserAvatar($blogAuthor['iuid'], $base_url) : $base_url . 'img/placeholder.png';
$csrfBlog = csrf_get_token();
$authorCache = [];
$blogViewCount = isset($blogViewCount) ? (int) $blogViewCount : 0;
$shareUrl = route_url('blog/' . $blogPost['slug']);
$shareTitle = (string) ($blogPost['title'] ?? '');
$metaUrl = $shareUrl;
$metaImage = $coverUrl;
$canonicalUrl = $metaUrl;
$blogShareEnabled = !isset($blogShareStatus) || (string)$blogShareStatus === '1';
$blogReactionsEnabled = (!isset($blogReactionsStatus) || (string)$blogReactionsStatus === '1') && (!isset($blogPost['allow_reactions']) || (string)$blogPost['allow_reactions'] !== '0');
$blogSidebarAdsEnabled = !isset($blogSidebarAdsStatus) || (string)$blogSidebarAdsStatus === '1';
$shareLinks = [
    'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl),
    'twitter' => 'https://twitter.com/intent/tweet?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($shareTitle),
    'whatsapp' => 'https://wa.me/?text=' . rawurlencode(trim($shareTitle . ' - ' . $shareUrl)),
];

if (!function_exists('dizzy_trim_text')) {
    function dizzy_trim_text(?string $text, int $limit = 120): string
    {
        $clean = trim(strip_tags((string) $text));
        if (function_exists('mb_strlen')) {
            if (mb_strlen($clean, 'utf-8') > $limit) {
                return mb_substr($clean, 0, $limit, 'utf-8') . '...';
            }
            return $clean;
        }
        return strlen($clean) > $limit ? substr($clean, 0, $limit) . '...' : $clean;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <meta name="description" content="<?php echo iN_HelpSecure($blogPost['meta_description'] ?: $blogPost['excerpt']); ?>">
    <meta property="og:title" content="<?php echo iN_HelpSecure($siteTitle); ?>">
    <meta property="og:description" content="<?php echo iN_HelpSecure($blogPost['meta_description'] ?: $blogPost['excerpt']); ?>">
    <meta property="og:image" content="<?php echo iN_HelpSecure($coverUrl ?? ''); ?>">
    <?php
        include 'layouts/header/meta.php';
        include 'layouts/header/css.php';
        include 'layouts/header/javascripts.php';
    ?>
</head>
<body>
<?php if ($logedIn == 0) {include 'layouts/login_form.php';}?>
<?php include 'layouts/header/header.php';?>
<div class="wrapper blog-wrapper blog-detail-page hero-layout">
    <div class="blog-detail-shell">
        <article class="blog-main">
            <a class="blog-back-link" href="<?php echo route_url('blog'); ?>">
                <span class="blog-back-icon" aria-hidden="true">←</span>
                <span><?php echo iN_HelpSecure($LANG['back'] ?? 'Back'); ?></span>
            </a>
            <div class="blog-hero-card">
                <div class="blog-hero-text">
                    <h1><?php echo iN_HelpSecure($blogPost['title']); ?></h1>
                    <?php if (!empty($blogPost['excerpt'])) { ?>
                        <p class="blog-lead"><?php echo iN_HelpSecure($blogPost['excerpt']); ?></p>
                    <?php } ?>
                    <div class="blog-meta">
                        <div class="blog-author">
                            <img src="<?php echo iN_HelpSecure($authorAvatar); ?>" alt="<?php echo iN_HelpSecure($authorName); ?>">
                            <div>
                                <div class="blog-author-name"><?php echo iN_HelpSecure($authorName); ?></div>
                                <div class="blog-author-role"><?php echo iN_HelpSecure($LANG['blog']); ?></div>
                            </div>
                        </div>
                        <div class="blog-meta-stats">
                            <div class="blog-date"><?php echo iN_HelpSecure($publishedDate); ?></div>
                            <div class="blog-views"><?php echo iN_HelpSecure(number_format(max($blogViewCount, 0))); ?> <?php echo iN_HelpSecure($LANG['blog_views_label'] ?? 'views'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="blog-hero-media">
                    <img src="<?php echo iN_HelpSecure($coverUrl); ?>" alt="<?php echo iN_HelpSecure($blogPost['title']); ?>">
                </div>
            </div>
            <div class="blog-content rich-content">
                <?php echo $blogPost['content_html']; ?>
            </div>
            <?php if ($blogShareEnabled) { ?>
                <div class="blog-share">
                    <div class="blog-share-label"><?php echo iN_HelpSecure($LANG['blog_share_label'] ?? 'Share'); ?></div>
                    <div class="blog-share-buttons">
                        <a class="blog-share-btn blog-share-facebook" href="<?php echo iN_HelpSecure($shareLinks['facebook']); ?>" target="_blank" rel="noopener noreferrer"><?php echo iN_HelpSecure($LANG['blog_share_facebook'] ?? 'Facebook'); ?></a>
                        <a class="blog-share-btn blog-share-twitter" href="<?php echo iN_HelpSecure($shareLinks['twitter']); ?>" target="_blank" rel="noopener noreferrer"><?php echo iN_HelpSecure($LANG['blog_share_twitter'] ?? 'Twitter'); ?></a>
                        <a class="blog-share-btn blog-share-whatsapp" href="<?php echo iN_HelpSecure($shareLinks['whatsapp']); ?>" target="_blank" rel="noopener noreferrer"><?php echo iN_HelpSecure($LANG['blog_share_whatsapp'] ?? 'WhatsApp'); ?></a>
                    </div>
                </div>
            <?php } ?>
            <?php if ($blogReactionsEnabled) { ?>
                <div class="blog-reactions blog-reactions-compact">
                    <button type="button" class="blog-react <?php echo $userBlogReaction === 'like' ? 'active' : ''; ?>" data-reaction="like">
                        <?php echo iN_HelpSecure($LANG['blog_like']); ?> <span id="blog-like-count"><?php echo iN_HelpSecure($reactionCounts['like']); ?></span>
                    </button>
                    <button type="button" class="blog-react <?php echo $userBlogReaction === 'dislike' ? 'active' : ''; ?>" data-reaction="dislike">
                        <?php echo iN_HelpSecure($LANG['blog_dislike']); ?> <span id="blog-dislike-count"><?php echo iN_HelpSecure($reactionCounts['dislike']); ?></span>
                    </button>
                </div>
            <?php } ?>
        </article>
        <aside class="blog-aside">
            <div class="aside-section">
                <div class="aside-title"><?php echo iN_HelpSecure($LANG['blog_latest_posts']); ?></div>
                <ul class="aside-list">
                    <?php if ($latestBlogs) { foreach ($latestBlogs as $item) { ?>
                        <li>
                            <a href="<?php echo route_url('blog/' . $item['slug']); ?>">
                                <div class="aside-item-title"><?php echo iN_HelpSecure($item['title']); ?></div>
                                <div class="aside-item-snippet"><?php echo iN_HelpSecure(dizzy_trim_text($item['excerpt'] ?? $item['content_html'] ?? '', 110)); ?></div>
                            </a>
                        </li>
                    <?php } } ?>
                </ul>
            </div>
            <?php
                $blogDetailAds = $blogSidebarAdsEnabled ? $iN->iN_ShowAds(1) : [];
                if (!empty($blogDetailAds)) {
            ?>
                <div class="aside-section blog-aside-ads">
                    <div class="aside-title"><?php echo iN_HelpSecure($LANG['advertisement_'] ?? 'Advertisement'); ?></div>
                    <div class="sp_wrp blog-detail-ads-wrap">
                        <?php foreach ($blogDetailAds as $aAds) {
                            $activeAdsTitle = $aAds['ads_title'] ?? '';
                            $activeAdsImage = $aAds['ads_image'] ?? '';
                            $activeAdsUrl = $aAds['ads_url'] ?? '';
                            $activeAdsDescription = $aAds['ads_desc'] ?? '';
                            $adsImageUrl = $activeAdsImage;
                        ?>
                            <a href="<?php echo html_entity_decode($activeAdsUrl); ?>" target="_blank" rel="noopener noreferrer" class="transition">
                                <div class="i_sponsored_card">
                                    <div class="i_sponsored_media">
                                        <img src="<?php echo html_entity_decode($adsImageUrl); ?>" alt="<?php echo iN_HelpSecure($activeAdsTitle); ?>"/>
                                    </div>
                                    <div class="i_sponsored_body">
                                        <div class="i_sponsored_title">
                                            <?php echo iN_HelpSecure($activeAdsTitle); ?>
                                        </div>
                                        <div class="i_sponsored_desc">
                                            <?php echo iN_HelpSecure($activeAdsDescription); ?>
                                        </div>
                                        <div class="i_sponsored_ads_link">
                                            <?php echo iN_HelpSecure($iN->iN_getHost($activeAdsUrl)); ?>
                                        </div>
                                    </div>
                                    <div class="i_sponsored_action">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
            <?php if (!empty($readMoreBlogs)) { ?>
                <div class="aside-section">
                    <div class="aside-title"><?php echo iN_HelpSecure($LANG['blog_most_read'] ?? 'Most read'); ?></div>
                    <ul class="aside-list alt">
                        <?php foreach ($readMoreBlogs as $post) { ?>
                            <li>
                                <a href="<?php echo route_url('blog/' . $post['slug']); ?>">
                                    <div class="aside-item-title"><?php echo iN_HelpSecure($post['title']); ?></div>
                                    <div class="aside-item-snippet"><?php echo iN_HelpSecure(dizzy_trim_text($post['excerpt'] ?? $post['content_html'] ?? '', 90)); ?></div>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
        </aside>
    </div>

    <?php if (!empty($readMoreBlogs)) { ?>
        <section class="blog-read-more">
            <h3><?php echo iN_HelpSecure($LANG['read_more']); ?></h3>
            <div class="blog-grid readmore-grid">
                <?php foreach ($readMoreBlogs as $post) { ?>
                    <?php
                        $postAuthor = dizzy_blog_author_meta($post, $iN, $base_url, $LANG, $authorCache);
                        $readMoreDate = date('M d, Y', $post['published_at'] ?? $post['created_at']);
                        $readMoreExcerpt = dizzy_blog_excerpt($post['excerpt'] ?? ($post['content_html'] ?? ''), 140);
                    ?>
                    <article class="readmore-card blog-list-card">
                        <a href="<?php echo route_url('blog/' . $post['slug']); ?>" class="blog-list-thumb">
                            <img src="<?php echo iN_HelpSecure(dizzy_blog_cover_url($post, $iN, $base_url)); ?>" alt="<?php echo iN_HelpSecure($post['title']); ?>">
                        </a>
                        <div class="blog-list-body">
                            <h3><a href="<?php echo route_url('blog/' . $post['slug']); ?>"><?php echo iN_HelpSecure($post['title']); ?></a></h3>
                            <p class="blog-list-excerpt"><?php echo iN_HelpSecure($readMoreExcerpt); ?></p>
                            <div class="blog-list-footer">
                                <div class="blog-list-meta"><?php echo iN_HelpSecure($readMoreDate); ?></div>
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
                <?php } ?>
            </div>
        </section>
    <?php } ?>
</div>
<script>
<?php if ($blogReactionsEnabled) { ?>
(function() {
    const blogId = <?php echo (int) $blogPost['blog_id']; ?>;
    const csrf = "<?php echo iN_HelpSecure($csrfBlog); ?>";
    const isLoggedIn = <?php echo $logedIn == '1' ? 'true' : 'false'; ?>;
    const url = "<?php echo iN_HelpSecure($base_url); ?>requests/request.php";
    document.querySelectorAll('.blog-react').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!isLoggedIn) {
                alert('Please login to react.');
                return;
            }
            const reaction = this.getAttribute('data-reaction');
            const formData = new URLSearchParams();
            formData.append('f', 'blog_reaction');
            formData.append('blog_id', blogId);
            formData.append('reaction', reaction);
            formData.append('csrf_token', csrf);
            fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(res => res.json()).then(res => {
                if (res.status === 'ok' && res.counts) {
                    document.getElementById('blog-like-count').textContent = res.counts.like || 0;
                    document.getElementById('blog-dislike-count').textContent = res.counts.dislike || 0;
                    document.querySelectorAll('.blog-react').forEach(function(el) { el.classList.remove('active'); });
                    if (res.data && res.data.status !== 'removed') {
                        document.querySelector('.blog-react[data-reaction=\"' + reaction + '\"]').classList.add('active');
                    }
                } else if (res.message) {
                    alert(res.message);
                }
            }).catch(() => {});
        });
    });
})();
<?php } ?>
</script>
<div class="footer_container_out"><?php include 'layouts/footer.php';?></div>
</body>
</html>
