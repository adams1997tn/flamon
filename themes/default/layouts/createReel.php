<?php
// Hide create reels page if feature disabled
if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus !== '1') {
    header('Location: ' . (isset($base_url) ? $base_url : '/'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>

    <?php
    // Include meta tags, CSS files, and JavaScript files
    include("header/meta.php");
    include("header/css.php");
    include("header/javascripts.php");
    $rmlThemeName = isset($currentTheme) ? (string) $currentTheme : 'default';
    $rmlMusicCssUrl = function_exists('dizzy_asset_url')
        ? dizzy_asset_url('themes/' . $rmlThemeName . '/scss/reels_music.css', 'ver_rml_' . (string)$version)
        : ($base_url . 'themes/' . $rmlThemeName . '/scss/reels_music.css?v=' . $version);
    $rmlEditorCssUrl = function_exists('dizzy_asset_url')
        ? dizzy_asset_url('themes/' . $rmlThemeName . '/scss/reels_editor.css', 'ver_rml_' . (string)$version)
        : ($base_url . 'themes/' . $rmlThemeName . '/scss/reels_editor.css?v=' . $version);
    ?>
    <link rel="stylesheet" href="<?php echo iN_HelpSecure($rmlMusicCssUrl); ?>">
    <link rel="stylesheet" href="<?php echo iN_HelpSecure($rmlEditorCssUrl); ?>">
</head>
<body>
<?php 
// Set default page value
$page = 'moreposts'; 

// If user is not logged in, show login form
if ($logedIn == 0) {
    include('login_form.php');
}
?>

<?php 
// Include top header section
include("header/header.php"); 
?>

<div class="wrapper <?php if ($logedIn == 0) { echo 'NotLoginYet'; } ?>">
    <?php
    // If user is logged in, show left sidebar menu
    if ($logedIn != 0) {
        include("left_menu.php");
    }

    // Include story image generator form
    include("reelsGeneratorForm.php");

    // Include right sidebar content
    include("page_right.php");
    ?>
</div>
</body>
</html>
