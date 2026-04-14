<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>

    <?php
    // Include meta tags, CSS files, and JavaScript files
    include("header/meta.php");
    include("header/css.php");
    include("header/javascripts.php");
    ?>
</head>
<body>
<?php 
// If the user is not logged in, show the login form
if($logedIn == 0){ 
    include('login_form.php'); 
} 
?>

<?php 
// Include the top header section
include("header/header.php"); 
?>

<div class="profile_wrapper" id="prw" data-u="<?php echo iN_HelpSecure($p_profileID);?>" data-connections-visibility="<?php echo isset($p_connectionsVisibility) ? iN_HelpSecure($p_connectionsVisibility) : '1'; ?>" data-owner="<?php echo ($p_profileID == $userID) ? '1' : '0'; ?>">
    <?php
    // Set the current page variable
    $page = 'profile';

    // Define the list of allowed profile content categories
    $pCats = array('photos','videos','audios','products','followers','following','subscribers','reels','polls');

    // Check if a valid category is provided via GET and sanitize it
    if(isset($_GET['pcat']) && $_GET['pcat'] != '' && !empty($_GET['pcat']) && in_array($_GET['pcat'], $pCats)){
        $pCat = isset($_GET['pcat']) ? trim($_GET['pcat']) : 'active_page_menu';
    } else {
        $pCat = 'active_page_menu';
    }

    // Include profile information section
    include("profile/profile_infos.php");

    // If user is not logged in and posts are hidden, show access restriction message
    if($logedIn == 0 && $p_showHidePosts == '1'){
        echo '<div class="th_middle"><div class="pageMiddle"><div class="pageMiddleRow"><div class="pageMiddleInfo"></div><div id="moreType" data-type="'.$page.'">'.$LANG['just_loged_in_user'].'</div></div></div></div>';
    } else {
        // Block followers/following/subscribers when owner hides connections
        $blockedConnections = false;
        $blockedMsg = '';
        $blockedAlertKey = '';
        if ($p_profileID != $userID && $p_connectionsVisibility === '0' && in_array($pCat, array('followers','following','subscribers'), true)) {
            $blockedConnections = true;
            if ($pCat === 'followers') {
                $blockedMsg = $LANG['connections_followers_hidden'] ?? '';
                $blockedAlertKey = 'connections_followers_hidden';
            } else if ($pCat === 'following') {
                $blockedMsg = $LANG['connections_following_hidden'] ?? '';
                $blockedAlertKey = 'connections_following_hidden';
            } else if ($pCat === 'subscribers') {
                $blockedMsg = $LANG['connections_subscribers_hidden'] ?? '';
                $blockedAlertKey = 'connections_subscribers_hidden';
            }
        }

        if ($blockedConnections) {
            echo '<div class="th_middle"><div class="pageMiddle"><div class="pageMiddleRow"><div class="pageMiddleInfo"></div><div id="moreType" data-type="'.$page.'">'.iN_HelpSecure($blockedMsg).'</div></div></div></div>';
            if (!empty($blockedAlertKey)) {
                echo '<script>document.addEventListener("DOMContentLoaded",function(){ if(typeof showConnectionsAlert==="function"){ showConnectionsAlert("'.iN_HelpSecure($blockedAlertKey).'"); } else if(typeof PopUPAlerts==="function"){ PopUPAlerts("'.iN_HelpSecure($blockedAlertKey).'", "ialert"); } });</script>';
            }
        } else {
            include("posts.php");
        }
    }
    ?>
</div>

<!-- Include audio player script -->
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/greenaudioplayer/audioplayer.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<script>
window.lang_connections_followers_hidden = "<?php echo iN_HelpSecure($LANG['connections_followers_hidden'] ?? 'This user hides their followers.'); ?>";
window.lang_connections_following_hidden = "<?php echo iN_HelpSecure($LANG['connections_following_hidden'] ?? 'This user hides who they follow.'); ?>";
window.lang_connections_subscribers_hidden = "<?php echo iN_HelpSecure($LANG['connections_subscribers_hidden'] ?? 'This user hides their subscribers.'); ?>";
</script>
</body>
</html>
