<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
       include("header/meta.php");
       include("header/css.php");
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
                echo '<div class="i_contents_container column flex_ tabing">';
                echo '<div class="nauthority_svg flex_ tabing">' . $iN->iN_SelectedMenuIcon('113') . '</div>';
                echo '<div class="no_authority">' . iN_HelpSecure($LANG['do_not_have_this_authority']) . '</div>';
                echo '</div>';
            ?>
        </div>
    </div>
</div>
</body>
</html>
