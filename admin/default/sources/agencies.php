<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle);?></title>
    <?php
       include("header/meta.php");
       include("header/css.php");
       include("header/javascripts.php");
    ?>
</head>
<body>
<div class="i_admin_container flex_">
    <?php include("menu/leftMenu.php");?>
    <div class="i_admin_right">
        <div class="i_admin_contents_wrapper column flex_">
            <?php
                include("header/header.php");
                if (isset($_GET['requests'])) {
                    include("contents/agency_requests.php");
                } elseif (isset($_GET['agency'])) {
                    include("contents/agency_edit.php");
                } else {
                    include("contents/agency_list.php");
                }
            ?>
        </div>
    </div>
</div>

</body>
</html>
