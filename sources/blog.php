<?php
$blogFeatureStatus = isset($blogFeatureStatus) ? (string)$blogFeatureStatus : '1';
if ($blogFeatureStatus !== '1') {
    $siteTitle = $LANG['blog_feature_disabled'] ?? ($LANG['blog'] ?? 'Blog');
    include("themes/$currentTheme/404.php");
    return;
}
$siteTitle = $LANG['blog'];
$searchQuery = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$isEmptySearch = isset($_GET['q']) && $searchQuery === '';
$baseBlogUrl = route_url('blog');
if ($isEmptySearch) {
    header('Location: ' . $baseBlogUrl);
    exit;
}
$pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$pageNumber = $pageNumber > 0 ? $pageNumber : 1;
$blogPageLimit = isset($paginationLimit) ? (int) $paginationLimit : 9;
$totalBlogs = $iN->iN_GetBlogListAdminCount($searchQuery, 'published');
$totalPages = ($blogPageLimit > 0) ? ceil($totalBlogs / $blogPageLimit) : 1;
$blogPosts = $iN->iN_PublicBlogList($blogPageLimit, $pageNumber, $searchQuery);
$featuredBlog = $iN->iN_PublicBlogFeatured(1);
$latestBlogs = $iN->iN_PublicBlogList(5, 1, '');

include("themes/$currentTheme/blog.php");
?>
