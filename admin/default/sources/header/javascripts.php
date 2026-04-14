<?php
$activeAdminTheme = isset($adminTheme) && trim((string)$adminTheme) !== '' ? (string)$adminTheme : 'default';
$adminThemeRootPath = dirname(__DIR__, 3);
$adminThemeJsDir = $adminThemeRootPath . '/' . $activeAdminTheme . '/js';
if (!is_dir($adminThemeJsDir)) {
    $activeAdminTheme = 'default';
}
$adminCsrf = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>

<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/jquery-v3.7.1.min.js')); ?>"></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/jquery.form.js', (string)$version)); ?>"></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/share.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/autoresize.min.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/lightGallery/lightgallery-all.min.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/videojs/video.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/clipboard/clipboard.min.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/scrollBar/jquery.slimscroll.min.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/i_admin.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('admin/' . $activeAdminTheme . '/js/agencyBulkHandler.js', (string)$version)); ?>" defer></script>

<script>
  (function() {
      var root = "<?php echo iN_HelpSecure(rtrim($base_url, '/')); ?>";
      window.siteRoot = root + '/';
      window.siteurl = root + '/admin/<?php echo iN_HelpSecure($adminTheme); ?>/';
      window.siteurlRedirect = root + '/admin/';
      window.csrfToken = "<?php echo iN_HelpSecure($adminCsrf); ?>";
  })();
</script>
