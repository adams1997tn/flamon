<?php
include_once "includes/inc.php";

if (!$iN->iN_IsObsOverlayEnabled()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' .
        iN_HelpSecure($LANG['page-not-found']) .
        '</title></head><body>' .
        iN_HelpSecure($LANG['sorry-this-page-not-available']) .
        '</body></html>';
    exit();
}

if (!function_exists('obs_overlay_is_https_url')) {
    function obs_overlay_is_https_url(string $url): bool {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        return isset($parts['scheme']) && strtolower((string)$parts['scheme']) === 'https';
    }
}

$overlayToken = $overlayToken ?? ($_GET['token'] ?? '');
$previewMode = isset($_GET['preview']) && (string)$_GET['preview'] === '1';
$overlayToken = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$overlayToken);
if ($overlayToken === '') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' .
        iN_HelpSecure($LANG['page-not-found']) .
        '</title></head><body>' .
        iN_HelpSecure($LANG['sorry-this-page-not-available']) .
        '</body></html>';
    exit();
}

$overlay = DB::one(
    "SELECT * FROM i_obs_overlays WHERE overlay_token = ? AND is_active = '1' LIMIT 1",
    [$overlayToken]
);
if (!$overlay) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' .
        iN_HelpSecure($LANG['page-not-found']) .
        '</title></head><body>' .
        iN_HelpSecure($LANG['sorry-this-page-not-available']) .
        '</body></html>';
    exit();
}

$enabledWidgets = json_decode((string)($overlay['enabled_widgets'] ?? ''), true);
if (!is_array($enabledWidgets)) {
    $enabledWidgets = [];
}
$widgetEnabled = function (string $key, bool $default = true) use ($enabledWidgets): bool {
    if (array_key_exists($key, $enabledWidgets)) {
        return (bool)$enabledWidgets[$key];
    }
    return $default;
};

$showDonation = $widgetEnabled('donation_total', true);
$showAlerts = $widgetEnabled('alerts', true);
$showMilestone = $widgetEnabled('milestone', true);
$showCta = $widgetEnabled('cta', true);
$showWatermark = $widgetEnabled('watermark', true);
$showNotification = $widgetEnabled('notification_box', false);
$showLeaderboard = $widgetEnabled('leaderboard', false);
$showTargetGoal = $widgetEnabled('target_goal', false);
$showLastSupporter = $widgetEnabled('last_supporter', false);
$showRunningText = $widgetEnabled('running_text', false);
$showLiveDuration = $widgetEnabled('live_duration', false);

$creatorId = (int)($overlay['creator_id_fk'] ?? 0);
$overlayId = (int)($overlay['obs_overlay_id'] ?? 0);
$initialLastAlertId = 0;
if ($overlayId > 0) {
    try {
        $initialLastAlertId = (int) DB::col(
            "SELECT MAX(id) FROM i_obs_support_events WHERE overlay_id = ?",
            [$overlayId]
        );
    } catch (Throwable $e) {
        $initialLastAlertId = 0;
    }
}

$donationMode = (string)($overlay['donation_mode'] ?? 'last24h');
$donationLabel = $donationMode === 'alltime'
    ? $LANG['obs_overlay_donation_label_alltime']
    : $LANG['obs_overlay_donation_label_last24h'];

$milestoneTitle = trim((string)($overlay['milestone_title'] ?? ''));
if ($milestoneTitle === '') {
    $milestoneTitle = $LANG['obs_overlay_milestone_default_title'];
}

$ctaLabel = trim((string)($overlay['cta_label'] ?? ''));
$ctaUrl = trim((string)($overlay['cta_url'] ?? ''));
$ctaValid = $ctaLabel !== '' && $ctaUrl !== '' && obs_overlay_is_https_url($ctaUrl);
$ctaEnabled = $showCta && $ctaValid;

$watermarkText = trim((string)($overlay['watermark_text'] ?? ''));
$watermarkEnabled = $showWatermark && $watermarkText !== '';

$stateUrl = route_url('obs/api/' . $overlayToken . '/state');
$alertsUrl = route_url('obs/api/' . $overlayToken . '/alerts');
$clickUrl = route_url('obs/api/' . $overlayToken . '/click');

$currencyConfig = [
    'symbol' => $currencys[$defaultCurrency] ?? '$',
    'position' => $currencySymbolPosition ?? 'left',
    'decimalSeparator' => $currencyDecimalSeparator ?? '.',
    'thousandSeparator' => $currencyThousandSeparator ?? ',',
    'decimals' => (int)($currencyDecimalPlaces ?? 2),
];
$layoutConfig = json_decode((string)($overlay['layout_json'] ?? ''), true);
if (!is_array($layoutConfig)) {
    $layoutConfig = [];
}
if (isset($layoutConfig['cta']) && is_array($layoutConfig['cta'])) {
    $ctaX = isset($layoutConfig['cta']['x']) ? (int)$layoutConfig['cta']['x'] : null;
    $ctaY = isset($layoutConfig['cta']['y']) ? (int)$layoutConfig['cta']['y'] : null;
    $ctaAnchor = (string)($layoutConfig['cta']['anchor'] ?? '');
    if (($ctaAnchor === '' || $ctaAnchor === 'tl') && (($ctaX === 1760 && $ctaY === 960) || ($ctaX === 1800 && $ctaY === 1010))) {
        $layoutConfig['cta']['x'] = 1896;
        $layoutConfig['cta']['y'] = 1056;
        $layoutConfig['cta']['anchor'] = 'br';
    }
}
if (isset($layoutConfig['milestone']) && is_array($layoutConfig['milestone'])) {
    $milestoneX = isset($layoutConfig['milestone']['x']) ? (int)$layoutConfig['milestone']['x'] : null;
    $milestoneY = isset($layoutConfig['milestone']['y']) ? (int)$layoutConfig['milestone']['y'] : null;
    $milestoneAnchor = (string)($layoutConfig['milestone']['anchor'] ?? '');
    if (($milestoneAnchor === '' || $milestoneAnchor === 'tl') && $milestoneX === 30 && $milestoneY === 960) {
        $layoutConfig['milestone']['y'] = 1056;
        $layoutConfig['milestone']['anchor'] = 'bl';
    }
}
$stylesConfig = json_decode((string)($overlay['styles_json'] ?? ''), true);
if (!is_array($stylesConfig)) {
    $stylesConfig = [];
}
$settingsConfig = json_decode((string)($overlay['settings_json'] ?? ''), true);
if (!is_array($settingsConfig)) {
    $settingsConfig = [];
}
$isCreatorPreview = false;
if ($previewMode && isset($userID)) {
    $isCreatorPreview = (int)$userID === $creatorId;
}
$overlayRuntimePayload = [
    'config' => [
        'stateUrl' => $stateUrl,
        'alertsUrl' => $alertsUrl,
        'clickUrl' => $clickUrl,
        'ctaEnabled' => $ctaEnabled,
        'stateEnabled' => ($showDonation || $showMilestone || $showLeaderboard || $showTargetGoal || $showLastSupporter || $showRunningText || $showLiveDuration),
        'alertsEnabled' => ($showAlerts || $showNotification),
        'pollStateMs' => 5000,
        'pollAlertsMs' => 2500,
        'isCreatorPreview' => $isCreatorPreview,
        'layoutBase' => ['width' => 1920, 'height' => 1080],
        'widgets' => [
            'donation_total' => $showDonation,
            'alerts' => $showAlerts,
            'milestone' => $showMilestone,
            'cta' => $showCta,
            'watermark' => $showWatermark,
            'notification_box' => $showNotification,
            'leaderboard' => $showLeaderboard,
            'target_goal' => $showTargetGoal,
            'last_supporter' => $showLastSupporter,
            'running_text' => $showRunningText,
            'live_duration' => $showLiveDuration,
        ],
    ],
    'layout' => $layoutConfig,
    'styles' => $stylesConfig,
    'settings' => $settingsConfig,
    'templates' => [
        'tip' => $LANG['obs_overlay_alert_tip'],
        'subscribe' => $LANG['obs_overlay_alert_subscribe'],
        'follow' => $LANG['obs_overlay_alert_follow'],
        'live_started' => $LANG['obs_overlay_alert_live_started'],
    ],
    'currency' => $currencyConfig,
    'anonymousLabel' => $LANG['campaign_anonymous'] ?? 'Anonymous',
    'initialLastAlertId' => $initialLastAlertId,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo iN_HelpSecure($LANG['obs_overlays_title']); ?></title>
    <link rel="stylesheet" href="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/scss/obs_overlay.css?v=ver_a<?php echo iN_HelpSecure($version); ?>">
</head>
<body class="obs-overlay-body<?php echo $previewMode ? ' obs-overlay-preview' : ''; ?>">
    <div class="obs-overlay" data-token="<?php echo iN_HelpSecure($overlayToken); ?>">
        <div class="obs-donation <?php echo $showDonation ? '' : 'obs-hidden'; ?>" data-obs-widget="donation_total">
            <div class="obs-label"><?php echo iN_HelpSecure($donationLabel); ?></div>
            <div class="obs-value" id="obsDonationValue">0</div>
        </div>

        <div class="obs-milestone <?php echo $showMilestone ? '' : 'obs-hidden'; ?>" data-obs-widget="milestone">
            <div class="obs-milestone-header">
                <div class="obs-label"><?php echo iN_HelpSecure($LANG['obs_overlay_milestone_label']); ?></div>
                <div class="obs-milestone-value" id="obsMilestoneValue">0</div>
            </div>
            <div class="obs-milestone-title" id="obsMilestoneTitle"><?php echo iN_HelpSecure($milestoneTitle); ?></div>
            <div class="obs-progress">
                <div class="obs-progress-bar" id="obsMilestoneBar"></div>
            </div>
        </div>

        <div class="obs-alerts <?php echo $showAlerts ? '' : 'obs-hidden'; ?>" id="obsAlerts" data-obs-widget="alerts"></div>

        <div class="obs-notification-box <?php echo $showNotification ? '' : 'obs-hidden'; ?>" id="obsNotificationBox" data-obs-widget="notification_box">
            <div class="obs-notification-list" id="obsNotificationList"></div>
        </div>

        <div class="obs-leaderboard <?php echo $showLeaderboard ? '' : 'obs-hidden'; ?>" data-obs-widget="leaderboard">
            <div class="obs-label" id="obsLeaderboardLabel"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_leaderboard']); ?></div>
            <div class="obs-leaderboard-list" id="obsLeaderboardList"></div>
        </div>

        <div class="obs-target-goal <?php echo $showTargetGoal ? '' : 'obs-hidden'; ?>" data-obs-widget="target_goal">
            <div class="obs-target-header">
                <div class="obs-label" id="obsTargetGoalLabel"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_target_goal']); ?></div>
                <div class="obs-target-value" id="obsTargetGoalValue">0</div>
            </div>
            <div class="obs-progress">
                <div class="obs-progress-bar" id="obsTargetGoalBar"></div>
            </div>
        </div>

        <div class="obs-last-supporter <?php echo $showLastSupporter ? '' : 'obs-hidden'; ?>" data-obs-widget="last_supporter">
            <div class="obs-label" id="obsLastSupporterLabel"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_last_supporter']); ?></div>
            <div class="obs-last-supporter-value" id="obsLastSupporterValue">-</div>
        </div>

        <div class="obs-running-text <?php echo $showRunningText ? '' : 'obs-hidden'; ?>" data-obs-widget="running_text">
            <div class="obs-running-text-track">
                <span id="obsRunningTextValue"></span>
            </div>
        </div>

        <div class="obs-live-duration <?php echo $showLiveDuration ? '' : 'obs-hidden'; ?>" data-obs-widget="live_duration">
            <div class="obs-label"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_live_duration']); ?></div>
            <div class="obs-live-duration-value" id="obsLiveDurationValue">00:00</div>
        </div>

        <div class="obs-cta <?php echo $ctaEnabled ? '' : 'obs-hidden'; ?>" data-obs-widget="cta">
            <button class="obs-cta-button" id="obsCtaButton" type="button">
                <?php echo iN_HelpSecure($ctaLabel); ?>
            </button>
        </div>

        <div class="obs-watermark <?php echo $watermarkEnabled ? '' : 'obs-hidden'; ?>" id="obsWatermark" data-obs-widget="watermark">
            <?php echo iN_HelpSecure($watermarkText); ?>
        </div>
    </div>

    <?php if ($isCreatorPreview) { ?>
        <div class="obs-test-panel" id="obsTestPanel">
            <label class="obs-test-toggle">
                <input type="checkbox" id="obsTestToggle">
                <span><?php echo iN_HelpSecure($LANG['obs_overlay_test_mode']); ?></span>
            </label>
            <div class="obs-test-controls obs-hidden" id="obsTestControls">
                <button type="button" class="obs-test-button" id="obsTestNotification">
                    <?php echo iN_HelpSecure($LANG['obs_overlay_test_notification']); ?>
                </button>
                <div class="obs-test-row">
                    <input type="text" id="obsTestDonationName" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_test_name_placeholder']); ?>">
                    <input type="number" id="obsTestDonationAmount" min="1" step="1" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_test_amount_placeholder']); ?>">
                    <button type="button" class="obs-test-button" id="obsTestDonation">
                        <?php echo iN_HelpSecure($LANG['obs_overlay_test_donation']); ?>
                    </button>
                </div>
                <div class="obs-test-row">
                    <input type="number" id="obsTestMilestoneProgress" min="0" step="1" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_test_progress_placeholder']); ?>">
                    <input type="number" id="obsTestMilestoneGoal" min="1" step="1" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_test_goal_placeholder']); ?>">
                    <button type="button" class="obs-test-button" id="obsTestMilestone">
                        <?php echo iN_HelpSecure($LANG['obs_overlay_test_milestone']); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php } ?>

    <script type="application/json" id="obsOverlayConfig"><?php echo json_encode($overlayRuntimePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/obs_overlay.runtime.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
    <?php if ($isCreatorPreview) { ?>
        <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/obs_overlay.preview.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
    <?php } ?>
</body>
</html>
