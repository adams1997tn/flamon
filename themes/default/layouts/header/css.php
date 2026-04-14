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

<?php if ($logedIn == 1): ?>
  <!-- Authenticated User Styles -->
  <link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/checkbox/checkbox.css')); ?>">
  <link rel="stylesheet" href="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/crop/cropmain.css')); ?>">
<?php endif; ?>

<style type="text/css">
/* Custom header CSS from admin panel */
<?php echo iN_HelpSecure($customHeaderCSSCode, 0, false); ?>

/* Dark mode custom CSS from admin panel */
<?php if ($lightDark === 'dark' && !empty($customNightCSSCode)) {
    echo iN_HelpSecure($customNightCSSCode, 0, false);
} ?>

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

/* Header SVG Fill Color */
<?php if ($headerSVGColor): ?>
.i_header_right .i_h_in svg,
.i_header_right .i_header_item_icon_box svg {
  fill: #<?php echo iN_HelpSecure($headerSVGColor); ?> !important;
}
<?php endif; ?>

/* Header Top Gradient */
<?php if ($headerTopColor): ?>
.header:before {
  background: #<?php echo iN_HelpSecure($headerTopColor); ?> !important;
}
<?php endif; ?>

/* Left Menu SVG Icons */
<?php if ($leftMenuSVGColor): ?>
.i_left_menu_box svg,
.i_s_menu_box svg,
.i_settings_wrapper_title_txt svg {
  fill: #<?php echo iN_HelpSecure($leftMenuSVGColor); ?> !important;
}
<?php endif; ?>

/* Left Menu Text Color */
<?php if ($MenuTextColor): ?>
.i_s_menu_wrapper a,
.i_s_menu_box,
.m_tit,
.live_title {
  color: #<?php echo iN_HelpSecure($MenuTextColor); ?> !important;
}
<?php endif; ?>

/* Post Section Icon Colors */
<?php if ($postSectionSVGColor): ?>
.i_image_video_btn svg,
.form_who_see .form_who_see_icon_set svg,
.i_pb_emojis_Box svg,
.i_post_menu_item_out:hover svg {
  fill: #<?php echo iN_HelpSecure($postSectionSVGColor); ?> !important;
}
<?php endif; ?>

/* Post Action Icons */
<?php if ($postIconSVGColor): ?>
.i_post_menu svg,
.in_like svg,
.in_tips svg,
.in_comment svg,
.in_share svg,
.in_social_share svg,
.in_save svg {
  fill: #<?php echo iN_HelpSecure($postIconSVGColor); ?> !important;
}
<?php endif; ?>

/* Modal/Popup SVG Icons */
<?php
$modalSvgColor = $postIconSVGColor ?: $postSectionSVGColor;
if ($modalSvgColor):
?>
.popClose svg,
.shareClose svg,
.coverCropClose svg,
.community_manage_card .communityEditModalBtn svg {
  fill: #<?php echo iN_HelpSecure($modalSvgColor); ?> !important;
}
<?php endif; ?>

/* Publish Button Color */
<?php if ($publishBTNColor): ?>
.publish_btn,
.alertBtnRight,
.send_tip_btn {
  background-color: #<?php echo iN_HelpSecure($publishBTNColor); ?> !important;
}
<?php endif; ?>

/* Live Streaming Button */
<?php if ($createLiveStreamingsBtnColor): ?>
.c_live_streaming,
.new_s_first,
.new_s_second {
  background-color: #<?php echo iN_HelpSecure($createLiveStreamingsBtnColor); ?> !important;
}
<?php endif; ?>

/* Hover Effects (Buttons, Menus, etc.) */
<?php if ($textHoverColor): ?>
.i_left_menu_box:hover,
.btest .live_item_cont .live_item:hover,
.i_header_btn_item:hover,
.i_u_details:hover,
.i_header_others_item:hover,
.i_sponsorad a:hover,
.i_message_wrapper:hover,
.i_s_menu_box:hover,
.form_btn:hover,
.form_who_see:hover,
.i_pb_emojis:hover,
.shareClose:hover,
.coverCropClose:hover,
.i_post_menu_item_out:hover {
  background-color: #<?php echo iN_HelpSecure($textHoverColor); ?> !important;
}
<?php endif; ?>
</style>
