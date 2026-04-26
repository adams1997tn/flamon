<?php
$themeName = isset($currentTheme) ? (string)$currentTheme : 'default';
$styleFile = $lightDark === 'light' ? 'style' : 'night_style';
?>

<!-- Base Stylesheet -->
<link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/scss/' . $styleFile . '.css', 'ver_a' . (string)$version, 'r=inlineaudio11')); ?>">

<!-- Plugin Stylesheets -->
<link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/lightGallery/lightgallery.css')); ?>">
<link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/swiper/swiper-bundle.css')); ?>">
<link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/audioplayer.css', 'm11')); ?>">

<!-- Responsive & foldable safety layer (loaded last so it can override) -->
<link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/responsive-foldables.css', (string)$version)); ?>">

<?php if ($logedIn == 1): ?>
  <!-- Authenticated User Styles -->
  <link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/checkbox/checkbox.css')); ?>">
  <link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/crop/cropmain.css')); ?>">
<?php endif; ?>

<style type="text/css">
<?php
/* -----------------------------------------------------------
   Build all dynamic CSS as a PHP string, then echo once.
   Keeping PHP tags out of the <style> body avoids spurious
   "at-rule or selector expected" errors in IDE CSS linters
   while changing nothing about the rendered output.
   ----------------------------------------------------------- */
$dynamicCss  = "/* Custom header CSS from admin panel */\n";
$dynamicCss .= iN_HelpSecure($customHeaderCSSCode, 0, false) . "\n";

if ($lightDark === 'dark' && !empty($customNightCSSCode)) {
    $dynamicCss .= "/* Dark mode custom CSS from admin panel */\n";
    $dynamicCss .= iN_HelpSecure($customNightCSSCode, 0, false) . "\n";
}

$dynamicCss .= <<<CSS
/* General Stories Box Style */
.stories_wrapper {
  padding: 16px 0;
  background: transparent;
  border: 1px solid rgba(0, 0, 0, 0.05);
  outline: 1px solid rgba(255, 255, 255, 0.08);
  -webkit-backdrop-filter: blur(6px);
          backdrop-filter: blur(6px);
  border-radius: 20px;
}
@media (prefers-color-scheme: dark) {
  .stories_wrapper {
    background: transparent;
    border-color: rgba(255, 255, 255, 0.08);
    outline: 1px solid rgba(255, 255, 255, 0.04);
    -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
  }
}
CSS;
$dynamicCss .= "\n";

if ($headerSVGColor) {
    $c = iN_HelpSecure($headerSVGColor);
    $dynamicCss .= "\n/* Header SVG Fill Color */\n"
        . ".i_header_right .i_h_in svg,\n"
        . ".i_header_right .i_header_item_icon_box svg {\n"
        . "  fill: #{$c} !important;\n"
        . "}\n";
}

if ($headerTopColor) {
    $c = iN_HelpSecure($headerTopColor);
    $dynamicCss .= "\n/* Header Top Gradient */\n"
        . ".header:before {\n"
        . "  background: #{$c} !important;\n"
        . "}\n";
}

if ($leftMenuSVGColor) {
    $c = iN_HelpSecure($leftMenuSVGColor);
    $dynamicCss .= "\n/* Left Menu SVG Icons */\n"
        . ".i_left_menu_box svg,\n"
        . ".i_s_menu_box svg,\n"
        . ".i_settings_wrapper_title_txt svg {\n"
        . "  fill: #{$c} !important;\n"
        . "}\n";
}

if ($MenuTextColor) {
    $c = iN_HelpSecure($MenuTextColor);
    $dynamicCss .= "\n/* Left Menu Text Color */\n"
        . ".i_s_menu_wrapper a,\n"
        . ".i_s_menu_box,\n"
        . ".m_tit,\n"
        . ".live_title {\n"
        . "  color: #{$c} !important;\n"
        . "}\n";
}

if ($postSectionSVGColor) {
    $c = iN_HelpSecure($postSectionSVGColor);
    $dynamicCss .= "\n/* Post Section Icon Colors */\n"
        . ".i_image_video_btn svg,\n"
        . ".form_who_see .form_who_see_icon_set svg,\n"
        . ".i_pb_emojis_Box svg,\n"
        . ".i_post_menu_item_out:hover svg {\n"
        . "  fill: #{$c} !important;\n"
        . "}\n";
}

if ($postIconSVGColor) {
    $c = iN_HelpSecure($postIconSVGColor);
    $dynamicCss .= "\n/* Post Action Icons */\n"
        . ".i_post_menu svg,\n"
        . ".in_like svg,\n"
        . ".in_tips svg,\n"
        . ".in_comment svg,\n"
        . ".in_share svg,\n"
        . ".in_social_share svg,\n"
        . ".in_save svg {\n"
        . "  fill: #{$c} !important;\n"
        . "}\n";
}

$modalSvgColor = $postIconSVGColor ?: $postSectionSVGColor;
if ($modalSvgColor) {
    $c = iN_HelpSecure($modalSvgColor);
    $dynamicCss .= "\n/* Modal/Popup SVG Icons */\n"
        . ".popClose svg,\n"
        . ".shareClose svg,\n"
        . ".coverCropClose svg,\n"
        . ".community_manage_card .communityEditModalBtn svg {\n"
        . "  fill: #{$c} !important;\n"
        . "}\n";
}

if ($publishBTNColor) {
    $c = iN_HelpSecure($publishBTNColor);
    $dynamicCss .= "\n/* Publish Button Color */\n"
        . ".publish_btn,\n"
        . ".alertBtnRight,\n"
        . ".send_tip_btn {\n"
        . "  background-color: #{$c} !important;\n"
        . "}\n";
}

if ($createLiveStreamingsBtnColor) {
    $c = iN_HelpSecure($createLiveStreamingsBtnColor);
    $dynamicCss .= "\n/* Live Streaming Button */\n"
        . ".c_live_streaming,\n"
        . ".new_s_first,\n"
        . ".new_s_second {\n"
        . "  background-color: #{$c} !important;\n"
        . "}\n";
}

if ($textHoverColor) {
    $c = iN_HelpSecure($textHoverColor);
    $dynamicCss .= "\n/* Hover Effects (Buttons, Menus, etc.) */\n"
        . ".i_left_menu_box:hover,\n"
        . ".btest .live_item_cont .live_item:hover,\n"
        . ".i_header_btn_item:hover,\n"
        . ".i_u_details:hover,\n"
        . ".i_header_others_item:hover,\n"
        . ".i_sponsorad a:hover,\n"
        . ".i_message_wrapper:hover,\n"
        . ".i_s_menu_box:hover,\n"
        . ".form_btn:hover,\n"
        . ".form_who_see:hover,\n"
        . ".i_pb_emojis:hover,\n"
        . ".shareClose:hover,\n"
        . ".coverCropClose:hover,\n"
        . ".i_post_menu_item_out:hover {\n"
        . "  background-color: #{$c} !important;\n"
        . "}\n";
}

echo $dynamicCss;
?>
</style>
