<?php
// Logged-in users always see main layout
if ($logedIn == '1') {
    include("themes/$currentTheme/layouts/main.php");
} else {
    // For guests: show landing when explicitly set to '2', otherwise default to main
    if ($landingPageType == '4' && $landingPagePlugin !== '') {
        $pluginSlug = preg_replace('/[^a-z0-9_-]/i', '', $landingPagePlugin);
        $pluginLanding = "themes/$currentTheme/landing_plugins/$pluginSlug/landing.php";
        if ($pluginSlug !== '' && is_file($pluginLanding)) {
            include($pluginLanding);
        } else {
            include("themes/$currentTheme/layouts/main.php");
        }
    } else if ($landingPageType == '3') {
        include("themes/$currentTheme/landing_two.php");
    } else if ($landingPageType == '2') {
        include("themes/$currentTheme/landing.php");
    } else {
        include("themes/$currentTheme/layouts/main.php");
    }
}
?>
