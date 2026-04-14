<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include("header/meta.php");
        include("header/css.php");
    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo iN_HelpSecure($base_url); ?>themes/default/scss/community-admin.css<?php echo $ver; ?>">
    <?php
        include("header/javascripts.php");
    ?>
</head>
<body>
<div class="i_admin_container flex_">
    <?php include("menu/leftMenu.php"); ?>
    <div class="i_admin_right">
        <div class="i_admin_contents_wrapper column flex_">
            <?php
                include("header/header.php");
                include("contents/community_management.php");
            ?>
        </div>
    </div>
</div>

<script src="<?php echo iN_HelpSecure($base_url); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/js/communityManagement.js?v=<?php echo iN_HelpSecure($version); ?>" defer></script>
</body>
</html>
