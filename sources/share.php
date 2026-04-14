<?php
include_once "includes/inc.php";

$shareToken = $shareToken ?? ($_GET['token'] ?? null);
$shareToken = $shareToken ? preg_replace('/[^A-Za-z0-9_-]/', '', $shareToken) : null;

$postID = $shareToken ? $iN->iN_GetPostIdFromShareToken($shareToken) : null;
$postData = $postID ? $iN->iN_GetPostForSharePreview($postID) : null;

if (!$shareToken || !$postID || !$postData) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head><body>Not found</body></html>';
    exit();
}

$redirectURL = $base_url . 'post/' . $postData['url_slug'] . '_' . $postData['post_id'];
$isLocked = ($postData['who_can_see'] && $postData['who_can_see'] !== '1') || ($postData['post_want_status'] ?? null) == '1';

$metaImage = $metaBaseUrl;
$metaTitle = $siteTitle;
$metaDescription = $siteDescription;

// Build meta content for unlocked posts
if (!$isLocked) {
    $postText = trim(strip_tags(html_entity_decode($postData['post_text'] ?? '')));
    $lenFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
    if ($postText !== '' && $lenFn($postText) > 200) {
        $postText = (function_exists('mb_substr') ? mb_substr($postText, 0, 197) : substr($postText, 0, 197)) . '...';
    }
    if ($postText === '') {
        $postText = $LANG['share_default_description'] ?? $siteDescription;
    }
    $name = $postData['i_user_fullname'] ?? ($postData['i_username'] ?? '');
    $metaTitle = trim($name) ? ($name . ' • ' . $siteTitle) : $siteTitle;
    $metaDescription = $postText;

    // Try to use a media thumbnail as OG image
    $fileList = array_filter(explode(',', rtrim($postData['post_file'] ?? '', ',')));
    foreach ($fileList as $fileID) {
        $fileData = $iN->iN_GetUploadedFileDetails($fileID);
        if (!$fileData) { continue; }
        $ext = strtolower($fileData['uploaded_file_ext'] ?? '');
        $path = $fileData['upload_tumbnail_file_path'] ?? ($fileData['uploaded_file_path'] ?? '');
        if (!$path) { continue; }
        if (in_array($ext, ['mp4','mov','mkv','webm'])) {
            $path = $fileData['upload_tumbnail_file_path'] ?? $path;
        }
        if (!$path) { continue; }
        $metaImage = function_exists('storage_public_url') ? storage_public_url($path) : ($base_url . ltrim($path, '/'));
        break;
    }
} else {
    $metaTitle = $LANG['share_locked_title'] ?? $siteTitle;
    $metaDescription = $LANG['share_locked_description'] ?? $siteDescription;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo iN_HelpSecure($metaTitle); ?></title>

    <meta name="title" content="<?php echo iN_HelpSecure($metaTitle); ?>">
    <meta name="description" content="<?php echo iN_HelpSecure($metaDescription); ?>">
    <meta name="keywords" content="<?php echo iN_HelpSecure($siteKeyWords); ?>">

    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo iN_HelpSecure($redirectURL); ?>">
    <meta property="og:title" content="<?php echo iN_HelpSecure($metaTitle); ?>">
    <meta property="og:description" content="<?php echo iN_HelpSecure($metaDescription); ?>">
    <meta property="og:image" content="<?php echo iN_HelpSecure($metaImage); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo iN_HelpSecure($redirectURL); ?>">
    <meta name="twitter:title" content="<?php echo iN_HelpSecure($metaTitle); ?>">
    <meta name="twitter:description" content="<?php echo iN_HelpSecure($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo iN_HelpSecure($metaImage); ?>">

    <link rel="canonical" href="<?php echo iN_HelpSecure($redirectURL); ?>">
</head>
<body>
    <div style="display:flex;align-items:center;justify-content:center;min-height:80vh;font-family:Arial,sans-serif;color:#555;">
        <?php echo iN_HelpSecure($LANG['share_redirecting'] ?? 'Redirecting...'); ?>
    </div>
    <script type="text/javascript">
        setTimeout(function(){ window.location.replace("<?php echo iN_HelpSecure($redirectURL); ?>"); }, 300);
    </script>
</body>
</html>
