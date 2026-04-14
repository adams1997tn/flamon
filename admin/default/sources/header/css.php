<?php
$activeAdminTheme = isset($adminTheme) && trim((string)$adminTheme) !== '' ? (string)$adminTheme : 'default';
$adminThemeRootPath = dirname(__DIR__, 3);
$adminThemeCssDir = $adminThemeRootPath . '/' . $activeAdminTheme . '/css';
if (!is_dir($adminThemeCssDir)) {
    $activeAdminTheme = 'default';
    $adminThemeCssDir = $adminThemeRootPath . '/default/css';
}
$isDarkMode = isset($lightDark) && (string)$lightDark === 'dark';
$baseAdminStyleFile = 'style';
$baseAdminStyleRelative = 'admin/' . $activeAdminTheme . '/css/' . $baseAdminStyleFile . '.css';
$baseAdminStyleMTime = dizzy_asset_resolved_mtime($baseAdminStyleRelative);
$nightAdminStyleFile = 'night_style';
$nightAdminStyleRelative = 'admin/' . $activeAdminTheme . '/css/' . $nightAdminStyleFile . '.css';
$nightAdminStyleMTime = dizzy_asset_resolved_mtime($nightAdminStyleRelative);
?>

<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url($baseAdminStyleRelative, (string)$version, $baseAdminStyleMTime !== null ? ('m=' . (string)$baseAdminStyleMTime) : '', false)); ?>">
<?php if ($isDarkMode): ?>
<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url($nightAdminStyleRelative, (string)$version, $nightAdminStyleMTime !== null ? ('m=' . (string)$nightAdminStyleMTime) : '', false)); ?>">
<?php endif; ?>
<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/css/lightGallery/lightgallery.css', (string)$version)); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/css/videojscss/video-js.css', (string)$version)); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/css/checkbox/checkbox.css', (string)$version)); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/css/crop/cropmain.css', (string)$version)); ?>">
<?php if (isset($pageFor) && $pageFor === 'story_audios'): ?>
<link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/default/scss/admin_story_audios.css', (string)$version)); ?>">
<?php endif; ?>
