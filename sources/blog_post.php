<?php
$blogFeatureStatus = isset($blogFeatureStatus) ? (string)$blogFeatureStatus : '1';
if ($blogFeatureStatus !== '1') {
    $siteTitle = $LANG['blog_feature_disabled'] ?? ($LANG['blog_not_found'] ?? 'Blog');
    include("themes/$currentTheme/404.php");
    return;
}
$slug = isset($blogSlug) ? $iN->iN_Secure($blogSlug) : '';
$blogPost = $slug ? $iN->iN_GetBlogPostBySlug($slug, true) : null;
if (!$blogPost) {
    $siteTitle = $LANG['blog_not_found'];
    include("themes/$currentTheme/404.php");
    return;
}
$siteTitle = $blogPost['title'];
$viewerIp = $iN->iN_GetIPAddress();
$viewAdded = $iN->iN_IncrementBlogView((int) $blogPost['blog_id'], $logedIn == '1' ? (int) $userID : 0, $viewerIp);
$blogViewCount = isset($blogPost['view_count']) && is_numeric($blogPost['view_count']) ? (int) $blogPost['view_count'] : 0;
$blogViewCount = $blogViewCount + ($viewAdded ? 1 : 0);
$reactionCounts = $iN->iN_BlogReactionCounts((int) $blogPost['blog_id']);
$userBlogReaction = null;
if ($logedIn == '1') {
    $userBlogReaction = $iN->iN_UserBlogReaction($userID, (int) $blogPost['blog_id']);
}
$latestBlogs = $iN->iN_PublicBlogList(5, 1, '');
$readMoreBlogs = $iN->iN_PublicBlogList(3, 1, '');
$blogAuthor = $iN->iN_GetUserDetails((int)$blogPost['author_id']);

include("themes/$currentTheme/blog_post.php");
?>
