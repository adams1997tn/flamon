<?php
$themeName = isset($currentTheme) ? (string)$currentTheme : 'default';
$baseThemePath = rtrim((string)$base_url, '/') . '/themes/' . $themeName;
?>

<!-- Viewport upgrade: ensure every page advertises foldable-safe viewport
     (viewport-fit=cover for safe-area, and remove maximum-scale lock so
      foldable users can pinch-zoom when needed). Runs ASAP, before paint. -->
<script>
(function () {
  try {
    var vp = document.querySelector('meta[name="viewport"]');
    if (!vp) {
      vp = document.createElement('meta');
      vp.setAttribute('name', 'viewport');
      (document.head || document.documentElement).appendChild(vp);
    }
    var content = vp.getAttribute('content') || '';
    // Strip maximum-scale / minimum-scale / user-scalable for accessibility.
    content = content
      .split(',')
      .map(function (p) { return p.trim(); })
      .filter(function (p) {
        return p && !/^(maximum-scale|minimum-scale|user-scalable)\s*=/i.test(p);
      });
    var has = function (key) {
      return content.some(function (p) { return p.toLowerCase().indexOf(key) === 0; });
    };
    if (!has('width=')) content.unshift('width=device-width');
    if (!has('initial-scale=')) content.push('initial-scale=1');
    if (!has('viewport-fit=')) content.push('viewport-fit=cover');
    vp.setAttribute('content', content.join(', '));
  } catch (e) { /* no-op */ }
})();
</script>

<!-- Common Scripts -->
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/jquery-v3.7.1.min.js')); ?>"></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/jquery.form.js', (string)$version)); ?>"></script>
<!-- Auto-label every responsive table cell from its header (mobile stacked rows). -->
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/responsive-tables.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/share.js', (string)$version)); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/clipboard/clipboard.min.js')); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/lightGallery/lightgallery-all.min.js')); ?>" defer></script>
<script
  data-videojs-loader="1"
  data-videojs-src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/videojs/video.js', (string)$version)); ?>"
  data-videojs-css="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/css/videojscss/video-js.css', (string)$version)); ?>"
  src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/videojs-loader.js', (string)$version)); ?>"
  defer
></script>

<?php if ($logedIn == 1): ?>
  <!-- Logged-in User Scripts -->
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/autoresize.min.js')); ?>" defer></script>
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/scrollBar/jquery.slimscroll.min.js')); ?>" defer></script>
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/character_count.js', (string)$version)); ?>" defer></script> 
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/inora.js', (string)$version, 'r=inlineaudio10_giphysearch_20260312')); ?>" defer></script>
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/giphySearchHandler.js', (string)$version, 'r=giphyhandler_20260312_2')); ?>" defer></script>
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/community.js', (string)$version)); ?>" defer></script>

  <!-- Dynamic values -->
  <script>
    <?php $csrfTokenForJs = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : ''); ?>
    window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
    window.csrfToken = "<?php echo iN_HelpSecure($csrfTokenForJs); ?>";
    window.inD = atob("<?php echo isset($fulPage) ? $fulPage : 'MQ=='; ?>");
    window.availableLength = <?php echo iN_HelpSecure($availableLength); ?>;
    window.accountDeleteAutoCancelNotice = <?php echo !empty($accountDeleteAutoCancelNotice) ? '1' : '0'; ?>;
    window.lang_no_comments_yet = "<?php echo iN_HelpSecure($LANG['no_comments_yet']); ?>";
    window.lang_comments_unavailable = "<?php echo iN_HelpSecure($LANG['comments_unavailable']); ?>";
    window.storyLang = <?php echo json_encode([
      'replyPlaceholder' => $LANG['story_reply_placeholder'] ?? '',
      'replySent' => $LANG['story_reply_sent'] ?? '',
      'replyFailed' => $LANG['story_reply_failed'] ?? '',
      'replyEmpty' => $LANG['story_reply_empty'] ?? '',
      'replySelf' => $LANG['story_reply_self'] ?? '',
      'replySendLabel' => $LANG['send_message'] ?? '',
      'accessFollowers' => $LANG['story_access_followers'] ?? '',
      'accessSubscribers' => $LANG['story_access_subscribers'] ?? '',
      'accessFollowBtn' => $LANG['follow'] ?? '',
      'accessSubscribeBtn' => $LANG['story_access_subscribe_btn'] ?? '',
      'reactionSet' => (isset($iN) && method_exists($iN, 'iN_GetStoryDefaultReactions'))
        ? implode(' ', $iN->iN_GetStoryDefaultReactions())
        : '',
      'audioMute' => $LANG['story_audio_mute'] ?? '',
      'audioUnmute' => $LANG['story_audio_unmute'] ?? '',
      'audioPlay' => $LANG['story_audio_play'] ?? '',
      'audioPause' => $LANG['story_audio_pause'] ?? '',
      'audioUnmuteTip' => $LANG['story_audio_unmute_tip'] ?? '',
      'videoExpand' => $LANG['ai_action_expand'] ?? '',
      'videoClose' => $LANG['close'] ?? '',
      'videoExpandIcon' => (isset($iN) && method_exists($iN, 'iN_SelectedMenuIcon'))
        ? html_entity_decode($iN->iN_SelectedMenuIcon('48'))
        : '',
      'textMore' => $LANG['story_text_read_more'] ?? '',
      'textLess' => $LANG['story_text_show_less'] ?? '',
      'currentUserId' => (int)$userID
    ], JSON_UNESCAPED_UNICODE); ?>;
    window.readMoreLang = {
      more: "<?php echo iN_HelpSecure($LANG['text_read_more'] ?? ($LANG['story_text_read_more'] ?? 'Read more')); ?>",
      less: "<?php echo iN_HelpSecure($LANG['text_show_less'] ?? ($LANG['story_text_show_less'] ?? 'Show less')); ?>"
    };
    window.webPushConfig = {
      enabled: <?php echo (isset($webPushStatus) && (string)$webPushStatus === '1') ? 'true' : 'false'; ?>,
      userEnabled: <?php echo (isset($userWebPushEnabled) && (string)$userWebPushEnabled === '1') ? 'true' : 'false'; ?>,
      vapidPublic: "<?php echo iN_HelpSecure($webPushVapidPublic ?? ''); ?>",
      requestUrl: "<?php echo iN_HelpSecure($base_url . 'requests/request.php'); ?>",
      workerUrl: "<?php echo iN_HelpSecure($base_url . 'webpush-sw.js'); ?>",
      workerScope: "<?php echo iN_HelpSecure($base_url . 'webpush/'); ?>",
      csrfToken: "<?php echo iN_HelpSecure($csrfTokenForJs); ?>",
      labels: {
        notSupported: "<?php echo iN_HelpSecure($LANG['web_push_not_supported'] ?? 'Your browser does not support push notifications.'); ?>",
        httpsRequired: "<?php echo iN_HelpSecure($LANG['web_push_https_required'] ?? 'Push notifications require HTTPS.'); ?>",
        denied: "<?php echo iN_HelpSecure($LANG['web_push_permission_denied'] ?? 'Notification permission is blocked in your browser.'); ?>",
        enabled: "<?php echo iN_HelpSecure($LANG['web_push_enabled_desc'] ?? 'Browser push notifications are enabled for this account.'); ?>",
        disabled: "<?php echo iN_HelpSecure($LANG['web_push_disabled_desc'] ?? 'Browser push notifications are disabled for this account.'); ?>",
        error: "<?php echo iN_HelpSecure($LANG['web_push_generic_error'] ?? 'Unable to update browser push settings.'); ?>"
      }
    };
  </script>

  <?php if ($page === 'profile'): ?>
    <script src="https://js.stripe.com/v3/" id="stripe-js-v3" defer></script>
  <?php endif; ?>

  <?php if ($oneSignalStatus === 'open'): ?>
    <link rel="manifest" href="<?php echo $base_url; ?>manifestOneSignal.json">
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async defer></script>
    <script>
      window.onesignal_app_id = "<?php echo $oneSignalApi; ?>";
      window.onesignal_user_id = "<?php echo $deviceKey; ?>";
      window.onesignal_request_url = "<?php echo $base_url . 'requests/request.php'; ?>";
    </script>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/onesignal-handler.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>

  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/webpush-handler.js', (string)$version)); ?>" defer></script>

  <?php if (isset($pageGet) && $pageGet === 'bulk_messages'): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/creatorBulkMessages.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>

  <?php if (isset($pageGet) && $pageGet === 'creator_auto_message'): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/creatorAutoMessage.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>

  <?php if (isset($pageGet) && $pageGet === 'obs_overlays'): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/obsOverlays.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>

  <?php if ((isset($agencyModuleStatus) ? $agencyModuleStatus : 'yes') === 'yes' && ((isset($pageGet) && $pageGet === 'agencies') || (isset($page) && ($page === 'agency' || $page === 'agencies')))): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/creatorAgencies.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>

  <?php if (isset($page) && $page === 'agencies'): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/agencyDirectory.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>

<?php else: ?>
  <!-- Guest User Scripts -->
  <script>
    window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
    window.lang_no_comments_yet = "<?php echo iN_HelpSecure($LANG['no_comments_yet']); ?>";
    window.lang_comments_unavailable = "<?php echo iN_HelpSecure($LANG['comments_unavailable']); ?>";
    window.storyLang = <?php echo json_encode([
      'replyPlaceholder' => $LANG['story_reply_placeholder'] ?? '',
      'replySent' => $LANG['story_reply_sent'] ?? '',
      'replyFailed' => $LANG['story_reply_failed'] ?? '',
      'replyEmpty' => $LANG['story_reply_empty'] ?? '',
      'replySelf' => $LANG['story_reply_self'] ?? '',
      'replySendLabel' => $LANG['send_message'] ?? '',
      'accessFollowers' => $LANG['story_access_followers'] ?? '',
      'accessSubscribers' => $LANG['story_access_subscribers'] ?? '',
      'accessFollowBtn' => $LANG['follow'] ?? '',
      'accessSubscribeBtn' => $LANG['story_access_subscribe_btn'] ?? '',
      'reactionSet' => (isset($iN) && method_exists($iN, 'iN_GetStoryDefaultReactions'))
        ? implode(' ', $iN->iN_GetStoryDefaultReactions())
        : '',
      'audioMute' => $LANG['story_audio_mute'] ?? '',
      'audioUnmute' => $LANG['story_audio_unmute'] ?? '',
      'audioPlay' => $LANG['story_audio_play'] ?? '',
      'audioPause' => $LANG['story_audio_pause'] ?? '',
      'audioUnmuteTip' => $LANG['story_audio_unmute_tip'] ?? '',
      'videoExpand' => $LANG['ai_action_expand'] ?? '',
      'videoClose' => $LANG['close'] ?? '',
      'videoExpandIcon' => (isset($iN) && method_exists($iN, 'iN_SelectedMenuIcon'))
        ? html_entity_decode($iN->iN_SelectedMenuIcon('48'))
        : '',
      'textMore' => $LANG['story_text_read_more'] ?? '',
      'textLess' => $LANG['story_text_show_less'] ?? '',
      'currentUserId' => 0
    ], JSON_UNESCAPED_UNICODE); ?>;
    window.readMoreLang = {
      more: "<?php echo iN_HelpSecure($LANG['text_read_more'] ?? ($LANG['story_text_read_more'] ?? 'Read more')); ?>",
      less: "<?php echo iN_HelpSecure($LANG['text_show_less'] ?? ($LANG['story_text_show_less'] ?? 'Show less')); ?>"
    };
  </script>
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/inora_do.js', 's211' . (string)$version, 'r=inlineaudio10')); ?>" defer></script>
  <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/community.js', (string)$version)); ?>" defer></script>
  <?php if (isset($page) && $page === 'agencies'): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/agencyDirectory.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>
  <?php if ((string)$ageConfirm === '1'): ?>
    <script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/age-confirm.js', (string)$version)); ?>" defer></script>
  <?php endif; ?>
<?php endif; ?>

<script>
  window.pwaInstallTexts = {
    installButton: "<?php echo iN_HelpSecure($LANG['pwa_install_app'] ?? 'Install App'); ?>",
    popupTitle: "<?php echo iN_HelpSecure($LANG['pwa_popup_title'] ?? 'Install App'); ?>",
    popupDescDefault: "<?php echo iN_HelpSecure($LANG['pwa_popup_desc_default'] ?? 'Add this app to your home screen for faster access and a better full-screen experience.'); ?>",
    popupLater: "<?php echo iN_HelpSecure($LANG['pwa_popup_later'] ?? 'Maybe later'); ?>",
    iosHelp: "<?php echo iN_HelpSecure($LANG['pwa_ios_install_help'] ?? 'Open this site in Safari, tap Share, then tap Add to Home Screen.'); ?>",
    iosSafariOnly: "<?php echo iN_HelpSecure($LANG['pwa_ios_safari_only'] ?? 'On iPhone and iPad, app installation is available only in Safari.'); ?>",
    androidUnavailable: "<?php echo iN_HelpSecure($LANG['pwa_android_install_unavailable'] ?? 'Install popup is not available yet. Please use browser menu > Add to Home screen.'); ?>"
  };
</script>

<!-- PWA scripts -->
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/main.js', (string)$version, 'r=pwa_install_20260226_5s_popup_iosshare2')); ?>" defer></script>
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('src/worker.js', (string)$version)); ?>" defer></script>

<!-- Currency formatting config -->
<script>
  window.dizzyCurrency = <?php echo json_encode([
      'symbol' => $currencys[$defaultCurrency] ?? '$',
      'position' => $currencySymbolPosition ?? 'left',
      'decimalSeparator' => $currencyDecimalSeparator ?? '.',
      'thousandSeparator' => $currencyThousandSeparator ?? ',',
      'decimals' => (int)($currencyDecimalPlaces ?? 2),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  window.dizzyFormatCurrency = function(amount, decimalsOverride) {
    const cfg = window.dizzyCurrency || {};
    const symbol = typeof cfg.symbol === "string" ? cfg.symbol : "";
    const position = cfg.position === "right" ? "right" : "left";
    const thousandSeparator = typeof cfg.thousandSeparator === "string" ? cfg.thousandSeparator : ",";
    const decimalSeparator = typeof cfg.decimalSeparator === "string" ? cfg.decimalSeparator : ".";
    const parsedDecimals = typeof decimalsOverride === "number" && decimalsOverride >= 0 ? decimalsOverride : parseInt(cfg.decimals, 10);
    const decimals = Number.isFinite(parsedDecimals) ? Math.min(Math.max(parsedDecimals, 0), 4) : 2;
    const number = Number(amount) || 0;
    const fixed = number.toFixed(decimals);
    const parts = fixed.split(".");
    let integerPart = parts[0];
    const decimalPart = decimals > 0 && parts[1] ? decimalSeparator + parts[1] : "";
    if (thousandSeparator) {
      const rgx = /(\d+)(\d{3})/;
      while (rgx.test(integerPart)) {
        integerPart = integerPart.replace(rgx, "$1" + thousandSeparator + "$2");
      }
    }
    const formatted = integerPart + decimalPart;
    if (position === "right") {
      return (formatted + (symbol ? " " + symbol : "")).trim();
    }
    return (symbol + formatted).trim();
  };
</script>

<!-- Custom JS if exists -->
<?php
if (!empty($customHeaderJsCode)) {
  $customJsVersion = substr(sha1((string)$customHeaderJsCode), 0, 12);
?>
  <script id="custom-header-js-<?php echo iN_HelpSecure($customJsVersion); ?>">
<?php echo iN_HelpSecure($customHeaderJsCode, 0, false); ?>
  </script>
<?php } ?>

<?php
$hasAdsenseLegacy = (isset($adsenseStatus) && $adsenseStatus === '1' && !empty($adsenseClientId));
$hasAdsenseNew = (isset($iN) && method_exists($iN, 'iN_HasAdsenseAds') && $iN->iN_HasAdsenseAds());
$adsenseClientForScript = !empty($adsenseClientId) ? $adsenseClientId : '';
if (($hasAdsenseLegacy || $hasAdsenseNew) && !empty($adsenseClientForScript)) :
?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo iN_HelpSecure($adsenseClientForScript); ?>" crossorigin="anonymous"></script>
<?php endif; ?>

<!-- Swiper.js -->
<script src="<?php echo iN_HelpSecure(dizzy_asset_url('themes/' . $themeName . '/js/swiper/swiper-bundle.min.js')); ?>"></script>
