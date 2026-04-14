<?php
if ($iN->iN_CheckUserIsCreator($userID) != 1) {
    header('Location: ' . route_url('404'));
    exit;
}

$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
$overlays = DB::all("SELECT * FROM i_obs_overlays WHERE creator_id_fk = ? ORDER BY obs_overlay_id DESC", [$userID]);
$obsOverlayMaxTotal = $iN->iN_GetObsOverlayMaxTotal();
$obsOverlayMaxActive = $iN->iN_GetObsOverlayMaxActive();
$obsOverlayAutoRevokeOldest = $iN->iN_IsObsOverlayAutoRevokeEnabled();
$obsOverlayFormatLimitMessage = static function (string $template, int $max): string {
    if ($template === '') {
        return '';
    }
    if (strpos($template, '%d') !== false) {
        return sprintf($template, $max);
    }
    return str_replace('5', (string)$max, $template);
};
$obsOverlaySecondaryNoteTemplate = $obsOverlayAutoRevokeOldest
    ? (string)($LANG['obs_overlay_auto_revoke_note'] ?? '')
    : (string)($LANG['obs_overlay_limit_reached'] ?? '');
$obsOverlaySecondaryNote = $obsOverlayFormatLimitMessage($obsOverlaySecondaryNoteTemplate, $obsOverlayMaxActive);
$obsOverlayTotalLimitNoteTemplate = (string)($LANG['obs_overlay_total_limit_note'] ?? '');
$obsOverlayTotalLimitNote = $obsOverlayFormatLimitMessage($obsOverlayTotalLimitNoteTemplate, $obsOverlayMaxTotal);
$colorPaletteMap = [];
if (isset($dizColors) && is_array($dizColors)) {
    foreach ($dizColors as $groupColors) {
        if (!is_array($groupColors)) {
            continue;
        }
        foreach ($groupColors as $hex) {
            $hex = strtoupper(ltrim((string)$hex, '#'));
            if (!preg_match('/^[0-9A-F]{6}$/', $hex)) {
                continue;
            }
            $colorPaletteMap['#' . $hex] = true;
        }
    }
}
$colorPalette = array_keys($colorPaletteMap);
unset($colorPaletteMap);
if (!function_exists('obs_overlay_render_color_picker')) {
    function obs_overlay_render_color_picker(
        string $widgetKey,
        string $styleKey,
        string $label,
        array $palette,
        string $selected,
        string $defaultLabel
    ): void {
        $selected = strtoupper(trim($selected));
        if ($selected !== '' && $selected[0] !== '#') {
            $selected = '#' . $selected;
        }
        if ($selected !== '' && !in_array($selected, $palette, true)) {
            $selected = '';
        }
        $displayLabel = $selected !== '' ? $selected : $defaultLabel;
        echo '<label class="obs-style-color">';
        echo '<span>' . iN_HelpSecure($label) . '</span>';
        echo '<div class="obs-color-picker" data-default-label="' . iN_HelpSecure($defaultLabel) . '" data-default-color="#FFFFFF">';
        echo '<input type="hidden" class="obs-style-input obs-color-value" data-widget="' . iN_HelpSecure($widgetKey) . '" data-style="' . iN_HelpSecure($styleKey) . '" value="' . iN_HelpSecure($selected) . '">';
        echo '<div class="obs-color-options">';
        echo '<button type="button" class="obs-color-option obs-color-default" data-color="">' . iN_HelpSecure($defaultLabel) . '</button>';
        foreach ($palette as $color) {
            $colorAttr = iN_HelpSecure($color);
            echo '<button type="button" class="obs-color-option" data-color="' . $colorAttr . '" aria-label="' . $colorAttr . '"></button>';
        }
        echo '</div>';
        echo '<div class="obs-color-selected"><span class="obs-color-swatch"></span><span class="obs-color-code">' . iN_HelpSecure($displayLabel) . '</span></div>';
        echo '</div>';
        echo '</label>';
    }
}
if (!function_exists('obs_overlay_render_font_size_select')) {
    function obs_overlay_render_font_size_select(
        string $widgetKey,
        string $selected,
        string $defaultLabel
    ): void {
        $selectedValue = (int)$selected;
        if ($selectedValue < 1 || $selectedValue > 30) {
            $selectedValue = 0;
        }
        echo '<select class="obs-style-input" data-widget="' . iN_HelpSecure($widgetKey) . '" data-style="fontSize">';
        echo '<option value="">' . iN_HelpSecure($defaultLabel) . '</option>';
        for ($size = 1; $size <= 30; $size++) {
            $isSelected = $selectedValue === $size ? ' selected' : '';
            echo '<option value="' . $size . '"' . $isSelected . '>' . $size . '</option>';
        }
        echo '</select>';
    }
}
?>
<div class="settings_main_wrapper obs_overlays_page">
    <div class="i_settings_wrapper_in i_inline_table">
        <div class="i_settings_wrapper_title">
            <div class="i_settings_wrapper_title_txt flex_">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('194'));?>
                <?php echo iN_HelpSecure($LANG['obs_overlays_title']);?>
            </div>
        </div>
        <div class="obs-overlay-search-wrap">
            <div class="obs-overlay-search-title"><?php echo iN_HelpSecure(($LANG['search'] ?? 'Search')); ?></div>
            <div class="obs-overlay-search-note"><?php echo iN_HelpSecure($LANG['obs_overlays_description']); ?></div>
            <div class="obs-overlay-search-field">
                <span class="obs-overlay-search-icon" aria-hidden="true"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?></span>
                <input
                    type="text"
                    class="flnm obs-overlay-search-input"
                    placeholder="<?php echo iN_HelpSecure(($LANG['search'] ?? 'Search')); ?>"
                    autocomplete="off"
                >
                <div class="obs-overlay-search-suggestions"></div>
            </div>
        </div>
        <div class="i_settings_wrapper_items">
            <div class="i_settings_wrapper_item">
                <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlays_description']);?></div>
                <div class="i_settings_item_title_for">
                    <form id="obsOverlayCreateForm" method="post">
                        <div class="i_warning obs_overlay_create_notice"></div>
                        <input type="hidden" name="f" value="obsOverlayCreate">
                        <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                        <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['obs_overlay_create_button']);?></button>
                    </form>
                    <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_create_note']);?></div>
                    <?php if ($obsOverlaySecondaryNote !== '') { ?>
                        <div class="box_not"><?php echo iN_HelpSecure($obsOverlaySecondaryNote);?></div>
                    <?php } ?>
                    <?php if ($obsOverlayTotalLimitNote !== '') { ?>
                        <div class="box_not"><?php echo iN_HelpSecure($obsOverlayTotalLimitNote);?></div>
                    <?php } ?>
                </div>
            </div>
            <div class="i_settings_wrapper_item">
                <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_docs_title']);?></div>
                <div class="i_settings_item_title_for">
                    <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_docs_obs']);?></div>
                    <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_docs_layout']);?></div>
                    <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_docs_revoke']);?></div>
                    <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_docs_test_mode']);?></div>
                </div>
            </div>

            <?php if (!empty($overlays)) { ?>
                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlays_title']);?></div>
                    <div class="i_settings_item_title_for">
                        <div class="obs-overlay-tabs" role="tablist" aria-label="<?php echo iN_HelpSecure($LANG['obs_overlays_title']); ?>">
                            <?php foreach ($overlays as $overlayIndex => $overlayTab) {
                                $tabOverlayId = (int)($overlayTab['obs_overlay_id'] ?? 0);
                                if ($tabOverlayId <= 0) {
                                    continue;
                                }
                                $tabToken = (string)($overlayTab['overlay_token'] ?? '');
                                $tabTokenShort = $tabToken !== '' ? strtoupper(substr($tabToken, 0, 8)) : '';
                                $tabIsActive = (string)($overlayTab['is_active'] ?? '1') === '1';
                            ?>
                                <button
                                    type="button"
                                    class="obs-overlay-tab<?php echo $overlayIndex === 0 ? ' is-active' : ''; ?>"
                                    data-overlay-tab="<?php echo $tabOverlayId; ?>"
                                    role="tab"
                                    aria-selected="<?php echo $overlayIndex === 0 ? 'true' : 'false'; ?>"
                                >
                                    <span class="obs-overlay-tab-label"><?php echo iN_HelpSecure($LANG['obs_overlay_token_label']);?> #<?php echo $overlayIndex + 1; ?></span>
                                    <?php if ($tabTokenShort !== '') { ?>
                                        <span class="obs-overlay-tab-token"><?php echo iN_HelpSecure($tabTokenShort); ?></span>
                                    <?php } ?>
                                    <span class="obs-overlay-tab-status"><?php echo iN_HelpSecure($tabIsActive ? $LANG['obs_overlay_active'] : $LANG['obs_overlay_inactive']); ?></span>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="obs-overlay-panels">
                <?php foreach ($overlays as $overlayIndex => $overlay) {
                    $enabled = json_decode((string)($overlay['enabled_widgets'] ?? ''), true);
                    if (!is_array($enabled)) { $enabled = []; }
                    $widgetEnabled = function (string $key, bool $default = true) use ($enabled): bool {
                        if (array_key_exists($key, $enabled)) {
                            return (bool)$enabled[$key];
                        }
                        return $default;
                    };
                    $overlayId = (int)($overlay['obs_overlay_id'] ?? 0);
                    $token = (string)($overlay['overlay_token'] ?? '');
                    $overlayUrl = $token !== '' ? route_url('obs/overlay/' . $token) : '';
                    $isActive = (string)($overlay['is_active'] ?? '1') === '1';
                    $statusLabel = $isActive ? $LANG['obs_overlay_active'] : $LANG['obs_overlay_inactive'];
                    $donationMode = (string)($overlay['donation_mode'] ?? 'last24h');
                    $donationMode = $donationMode === 'alltime' ? 'alltime' : 'last24h';
                    $layoutDefaults = [
                        'watermark' => ['x' => 30, 'y' => 30, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tl'],
                        'donation_total' => ['x' => 30, 'y' => 520, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tl'],
                        'alerts' => ['x' => 30, 'y' => 700, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tl'],
                        'milestone' => ['x' => 30, 'y' => 1056, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'bl'],
                        'cta' => ['x' => 1896, 'y' => 1056, 'scale' => 1, 'zIndex' => 3, 'anchor' => 'br'],
                        'notification_box' => ['x' => 1896, 'y' => 160, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tr'],
                        'leaderboard' => ['x' => 30, 'y' => 160, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tl'],
                        'last_supporter' => ['x' => 1896, 'y' => 360, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tr'],
                        'target_goal' => ['x' => 1896, 'y' => 560, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tr'],
                        'running_text' => ['x' => 30, 'y' => 980, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'bl'],
                        'live_duration' => ['x' => 1896, 'y' => 30, 'scale' => 1, 'zIndex' => 2, 'anchor' => 'tr']
                    ];
                    $layoutJson = json_decode((string)($overlay['layout_json'] ?? ''), true);
                    if (!is_array($layoutJson)) {
                        $layoutJson = [];
                    }
                    if (isset($layoutJson['cta']) && is_array($layoutJson['cta'])) {
                        $ctaX = isset($layoutJson['cta']['x']) ? (int)$layoutJson['cta']['x'] : null;
                        $ctaY = isset($layoutJson['cta']['y']) ? (int)$layoutJson['cta']['y'] : null;
                        $ctaAnchor = (string)($layoutJson['cta']['anchor'] ?? '');
                        if (($ctaAnchor === '' || $ctaAnchor === 'tl') && (($ctaX === 1760 && $ctaY === 960) || ($ctaX === 1800 && $ctaY === 1010))) {
                            $layoutJson['cta']['x'] = 1896;
                            $layoutJson['cta']['y'] = 1056;
                            $layoutJson['cta']['anchor'] = 'br';
                        }
                    }
                    if (isset($layoutJson['milestone']) && is_array($layoutJson['milestone'])) {
                        $milestoneX = isset($layoutJson['milestone']['x']) ? (int)$layoutJson['milestone']['x'] : null;
                        $milestoneY = isset($layoutJson['milestone']['y']) ? (int)$layoutJson['milestone']['y'] : null;
                        $milestoneAnchor = (string)($layoutJson['milestone']['anchor'] ?? '');
                        if (($milestoneAnchor === '' || $milestoneAnchor === 'tl') && $milestoneX === 30 && $milestoneY === 960) {
                            $layoutJson['milestone']['y'] = 1056;
                            $layoutJson['milestone']['anchor'] = 'bl';
                        }
                    }
                    $stylesJson = json_decode((string)($overlay['styles_json'] ?? ''), true);
                    if (!is_array($stylesJson)) {
                        $stylesJson = [];
                    }
                    $styleValue = function (string $widget, string $key) use ($stylesJson): string {
                        return (string)($stylesJson[$widget][$key] ?? '');
                    };
                    $settingsJson = json_decode((string)($overlay['settings_json'] ?? ''), true);
                    if (!is_array($settingsJson)) {
                        $settingsJson = [];
                    }
                    $notificationTiers = isset($settingsJson['notification_tiers']) && is_array($settingsJson['notification_tiers'])
                        ? $settingsJson['notification_tiers']
                        : [];
                    $notificationClassOptions = [
                        '' => $LANG['obs_overlay_notification_class_default'],
                        'obs-tier-slate' => $LANG['obs_overlay_notification_class_slate'],
                        'obs-tier-mint' => $LANG['obs_overlay_notification_class_mint'],
                        'obs-tier-sunrise' => $LANG['obs_overlay_notification_class_sunrise'],
                        'obs-tier-rose' => $LANG['obs_overlay_notification_class_rose'],
                        'obs-tier-ice' => $LANG['obs_overlay_notification_class_ice'],
                        'obs-tier-gold' => $LANG['obs_overlay_notification_class_gold']
                    ];
                    $leaderboardConfig = isset($settingsJson['leaderboard']) && is_array($settingsJson['leaderboard'])
                        ? $settingsJson['leaderboard']
                        : [];
                    $leaderboardLimit = isset($leaderboardConfig['limit']) ? (int)$leaderboardConfig['limit'] : 5;
                    $leaderboardMode = isset($leaderboardConfig['mode']) ? (string)$leaderboardConfig['mode'] : 'last24h';
                    $leaderboardTypes = isset($leaderboardConfig['include_types']) && is_array($leaderboardConfig['include_types'])
                        ? $leaderboardConfig['include_types']
                        : ['tips', 'live_gift'];
                    $targetGoalConfig = isset($settingsJson['target_goal']) && is_array($settingsJson['target_goal'])
                        ? $settingsJson['target_goal']
                        : [];
                    $targetGoalTitle = (string)($targetGoalConfig['title'] ?? '');
                    $targetGoalAmount = (string)($targetGoalConfig['goal_amount'] ?? '');
                    $targetGoalMode = isset($targetGoalConfig['mode']) ? (string)$targetGoalConfig['mode'] : 'last24h';
                    $targetGoalTypes = isset($targetGoalConfig['include_types']) && is_array($targetGoalConfig['include_types'])
                        ? $targetGoalConfig['include_types']
                        : ['tips', 'live_gift'];
                    $lastSupporterConfig = isset($settingsJson['last_supporter']) && is_array($settingsJson['last_supporter'])
                        ? $settingsJson['last_supporter']
                        : [];
                    $lastSupporterLabel = (string)($lastSupporterConfig['label'] ?? '');
                    $lastSupporterShowAmount = !empty($lastSupporterConfig['show_amount']);
                    $lastSupporterMode = isset($lastSupporterConfig['mode']) ? (string)$lastSupporterConfig['mode'] : 'last24h';
                    $lastSupporterTypes = isset($lastSupporterConfig['include_types']) && is_array($lastSupporterConfig['include_types'])
                        ? $lastSupporterConfig['include_types']
                        : ['tips', 'live_gift'];
                    $runningTextConfig = isset($settingsJson['running_text']) && is_array($settingsJson['running_text'])
                        ? $settingsJson['running_text']
                        : [];
                    $runningTextMode = isset($runningTextConfig['mode']) ? (string)$runningTextConfig['mode'] : 'custom';
                    $runningTextTemplate = (string)($runningTextConfig['template'] ?? '');
                    $runningTextCustom = (string)($runningTextConfig['custom_text'] ?? '');
                    $runningTextSpeed = isset($runningTextConfig['speed']) ? (int)$runningTextConfig['speed'] : 30;
                    $liveExtenderConfig = isset($settingsJson['live_extender']) && is_array($settingsJson['live_extender'])
                        ? $settingsJson['live_extender']
                        : [];
                    $liveExtenderUnitAmount = (string)($liveExtenderConfig['unit_amount'] ?? '');
                    $liveExtenderSeconds = isset($liveExtenderConfig['seconds_per_unit']) ? (int)$liveExtenderConfig['seconds_per_unit'] : 60;
                    $liveExtenderMaxSeconds = isset($liveExtenderConfig['max_seconds']) ? (int)$liveExtenderConfig['max_seconds'] : 0;
                    $liveExtenderTypes = isset($liveExtenderConfig['include_types']) && is_array($liveExtenderConfig['include_types'])
                        ? $liveExtenderConfig['include_types']
                        : ['tips', 'live_gift'];
                ?>
                <div class="obs-overlay-panel<?php echo $overlayIndex === 0 ? ' is-active' : ''; ?>" data-overlay-panel="<?php echo $overlayId; ?>">
                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_token_label']);?></div>
                    <div class="i_settings_item_title_for">
                        <input type="text" class="flnm" value="<?php echo iN_HelpSecure($token); ?>" readonly>
                        <div class="box_not"><?php echo iN_HelpSecure($statusLabel);?></div>
                        <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_token_note']);?></div>
                    </div>
                </div>
                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_url_label']);?></div>
                    <div class="i_settings_item_title_for">
                        <input type="text" class="flnm" value="<?php echo iN_HelpSecure($overlayUrl); ?>" readonly>
                        <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_url_note']);?></div>
                    </div>
                </div>
                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_preview_label']);?></div>
                    <div class="i_settings_item_title_for">
                        <a class="i_nex_btn_btn transition" href="<?php echo iN_HelpSecure($overlayUrl); ?>?preview=1" target="_blank" rel="noopener">
                            <?php echo iN_HelpSecure($LANG['obs_overlay_preview_button']);?>
                        </a>
                        <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_preview_note']);?></div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_layout_title']);?></div>
                    <div class="i_settings_item_title_for">
                        <div class="i_warning obs_overlay_layout_notice"></div>
                        <div class="obs-layout-editor"
                             data-overlay-token="<?php echo iN_HelpSecure($token); ?>"
                             data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
                             data-layout="<?php echo htmlspecialchars(json_encode($layoutJson, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                             data-default-layout="<?php echo htmlspecialchars(json_encode($layoutDefaults, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                             data-saved-message="<?php echo iN_HelpSecure($LANG['obs_overlay_layout_saved']); ?>">
                            <div class="obs-layout-toolbar">
                                <button type="button" class="i_nex_btn_btn transition obs-layout-save">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_layout_save_button']);?>
                                </button>
                                <button type="button" class="i_nex_btn_btn transition obs-layout-reset">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_layout_reset_button']);?>
                                </button>
                            </div>
                            <div class="obs-layout-canvas" data-width="1920" data-height="1080">
                                <div class="obs-layout-widget<?php echo $widgetEnabled('donation_total', true) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="donation_total">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_donation_total']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('alerts', true) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="alerts">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_alerts']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('milestone', true) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="milestone">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_milestone']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('cta', true) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="cta">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_cta']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('watermark', true) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="watermark">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_watermark']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('notification_box', false) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="notification_box">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_notification_box']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('leaderboard', false) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="leaderboard">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_leaderboard']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('last_supporter', false) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="last_supporter">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_last_supporter']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('target_goal', false) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="target_goal">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_target_goal']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('running_text', false) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="running_text">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_running_text']);?>
                                </div>
                                <div class="obs-layout-widget<?php echo $widgetEnabled('live_duration', false) ? '' : ' obs-layout-widget-disabled'; ?>" data-widget="live_duration">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_widget_live_duration']);?>
                                </div>
                            </div>
                            <div class="obs-layout-controls">
                                <div class="obs-layout-control" data-widget="donation_total">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_donation_total']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="alerts">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_alerts']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="milestone">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_milestone']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="cta">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_cta']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="watermark">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_watermark']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="notification_box">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_notification_box']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="leaderboard">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_leaderboard']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="last_supporter">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_last_supporter']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="target_goal">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_target_goal']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="running_text">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_running_text']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                                <div class="obs-layout-control" data-widget="live_duration">
                                    <div class="obs-layout-control-title"><?php echo iN_HelpSecure($LANG['obs_overlay_widget_live_duration']);?></div>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_scale_label']);?></span>
                                        <input type="number" class="obs-layout-scale" min="0.5" max="2" step="0.1">
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_zindex_label']);?></span>
                                        <input type="number" class="obs-layout-zindex" min="0" max="999" step="1">
                                    </label>
                                    <div class="obs-layout-coords">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_layout_position_label']);?></span>
                                        <span class="obs-layout-x">0</span>, <span class="obs-layout-y">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_layout_note']);?></div>
                        </div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_styles_title']);?></div>
                    <div class="i_settings_item_title_for">
                        <div class="i_warning obs_overlay_styles_notice"></div>
                        <div class="obs-styles-editor"
                             data-overlay-token="<?php echo iN_HelpSecure($token); ?>"
                             data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
                             data-saved-message="<?php echo iN_HelpSecure($LANG['obs_overlay_styles_saved']); ?>">
                            <details class="obs-style-section" open>
                                <summary><?php echo iN_HelpSecure($LANG['obs_overlay_widget_donation_total']);?></summary>
                                <div class="obs-style-fields">
                                    <?php obs_overlay_render_color_picker('donation_total', 'textColor', $LANG['obs_overlay_style_text_color'], $colorPalette, $styleValue('donation_total', 'textColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php obs_overlay_render_color_picker('donation_total', 'bgColor', $LANG['obs_overlay_style_bg_color'], $colorPalette, $styleValue('donation_total', 'bgColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php $bgOpacityValue = $styleValue('donation_total', 'bgOpacity'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_bg_opacity']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="donation_total" data-style="bgOpacity" value="<?php echo iN_HelpSecure($bgOpacityValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="100" step="1" value="<?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_font_size']);?></span>
                                        <?php obs_overlay_render_font_size_select('donation_total', $styleValue('donation_total', 'fontSize'), $LANG['obs_overlay_style_default']); ?>
                                    </label>
                                    <?php $borderRadiusValue = $styleValue('donation_total', 'borderRadius'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_border_radius']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="donation_total" data-style="borderRadius" value="<?php echo iN_HelpSecure($borderRadiusValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="40" step="1" value="<?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_text_align']);?></span>
                                        <select class="obs-style-input" data-widget="donation_total" data-style="textAlign">
                                            <option value=""><?php echo iN_HelpSecure($LANG['obs_overlay_style_default']);?></option>
                                            <option value="left" <?php echo $styleValue('donation_total', 'textAlign') === 'left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_left']);?></option>
                                            <option value="center" <?php echo $styleValue('donation_total', 'textAlign') === 'center' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_center']);?></option>
                                            <option value="right" <?php echo $styleValue('donation_total', 'textAlign') === 'right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_right']);?></option>
                                        </select>
                                    </label>
                                </div>
                            </details>
                            <details class="obs-style-section">
                                <summary><?php echo iN_HelpSecure($LANG['obs_overlay_widget_alerts']);?></summary>
                                <div class="obs-style-fields">
                                    <?php obs_overlay_render_color_picker('alerts', 'textColor', $LANG['obs_overlay_style_text_color'], $colorPalette, $styleValue('alerts', 'textColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php obs_overlay_render_color_picker('alerts', 'bgColor', $LANG['obs_overlay_style_bg_color'], $colorPalette, $styleValue('alerts', 'bgColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php $bgOpacityValue = $styleValue('alerts', 'bgOpacity'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_bg_opacity']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="alerts" data-style="bgOpacity" value="<?php echo iN_HelpSecure($bgOpacityValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="100" step="1" value="<?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_font_size']);?></span>
                                        <?php obs_overlay_render_font_size_select('alerts', $styleValue('alerts', 'fontSize'), $LANG['obs_overlay_style_default']); ?>
                                    </label>
                                    <?php $borderRadiusValue = $styleValue('alerts', 'borderRadius'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_border_radius']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="alerts" data-style="borderRadius" value="<?php echo iN_HelpSecure($borderRadiusValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="40" step="1" value="<?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_text_align']);?></span>
                                        <select class="obs-style-input" data-widget="alerts" data-style="textAlign">
                                            <option value=""><?php echo iN_HelpSecure($LANG['obs_overlay_style_default']);?></option>
                                            <option value="left" <?php echo $styleValue('alerts', 'textAlign') === 'left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_left']);?></option>
                                            <option value="center" <?php echo $styleValue('alerts', 'textAlign') === 'center' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_center']);?></option>
                                            <option value="right" <?php echo $styleValue('alerts', 'textAlign') === 'right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_right']);?></option>
                                        </select>
                                    </label>
                                </div>
                            </details>
                            <details class="obs-style-section">
                                <summary><?php echo iN_HelpSecure($LANG['obs_overlay_widget_milestone']);?></summary>
                                <div class="obs-style-fields">
                                    <?php obs_overlay_render_color_picker('milestone', 'textColor', $LANG['obs_overlay_style_text_color'], $colorPalette, $styleValue('milestone', 'textColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php obs_overlay_render_color_picker('milestone', 'bgColor', $LANG['obs_overlay_style_bg_color'], $colorPalette, $styleValue('milestone', 'bgColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php $bgOpacityValue = $styleValue('milestone', 'bgOpacity'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_bg_opacity']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="milestone" data-style="bgOpacity" value="<?php echo iN_HelpSecure($bgOpacityValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="100" step="1" value="<?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_font_size']);?></span>
                                        <?php obs_overlay_render_font_size_select('milestone', $styleValue('milestone', 'fontSize'), $LANG['obs_overlay_style_default']); ?>
                                    </label>
                                    <?php $borderRadiusValue = $styleValue('milestone', 'borderRadius'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_border_radius']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="milestone" data-style="borderRadius" value="<?php echo iN_HelpSecure($borderRadiusValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="40" step="1" value="<?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_text_align']);?></span>
                                        <select class="obs-style-input" data-widget="milestone" data-style="textAlign">
                                            <option value=""><?php echo iN_HelpSecure($LANG['obs_overlay_style_default']);?></option>
                                            <option value="left" <?php echo $styleValue('milestone', 'textAlign') === 'left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_left']);?></option>
                                            <option value="center" <?php echo $styleValue('milestone', 'textAlign') === 'center' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_center']);?></option>
                                            <option value="right" <?php echo $styleValue('milestone', 'textAlign') === 'right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_right']);?></option>
                                        </select>
                                    </label>
                                </div>
                            </details>
                            <details class="obs-style-section">
                                <summary><?php echo iN_HelpSecure($LANG['obs_overlay_widget_cta']);?></summary>
                                <div class="obs-style-fields">
                                    <?php obs_overlay_render_color_picker('cta', 'textColor', $LANG['obs_overlay_style_text_color'], $colorPalette, $styleValue('cta', 'textColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php obs_overlay_render_color_picker('cta', 'bgColor', $LANG['obs_overlay_style_bg_color'], $colorPalette, $styleValue('cta', 'bgColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php $bgOpacityValue = $styleValue('cta', 'bgOpacity'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_bg_opacity']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="cta" data-style="bgOpacity" value="<?php echo iN_HelpSecure($bgOpacityValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="100" step="1" value="<?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_font_size']);?></span>
                                        <?php obs_overlay_render_font_size_select('cta', $styleValue('cta', 'fontSize'), $LANG['obs_overlay_style_default']); ?>
                                    </label>
                                    <?php $borderRadiusValue = $styleValue('cta', 'borderRadius'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_border_radius']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="cta" data-style="borderRadius" value="<?php echo iN_HelpSecure($borderRadiusValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="40" step="1" value="<?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_text_align']);?></span>
                                        <select class="obs-style-input" data-widget="cta" data-style="textAlign">
                                            <option value=""><?php echo iN_HelpSecure($LANG['obs_overlay_style_default']);?></option>
                                            <option value="left" <?php echo $styleValue('cta', 'textAlign') === 'left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_left']);?></option>
                                            <option value="center" <?php echo $styleValue('cta', 'textAlign') === 'center' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_center']);?></option>
                                            <option value="right" <?php echo $styleValue('cta', 'textAlign') === 'right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_right']);?></option>
                                        </select>
                                    </label>
                                </div>
                            </details>
                            <details class="obs-style-section">
                                <summary><?php echo iN_HelpSecure($LANG['obs_overlay_widget_watermark']);?></summary>
                                <div class="obs-style-fields">
                                    <?php obs_overlay_render_color_picker('watermark', 'textColor', $LANG['obs_overlay_style_text_color'], $colorPalette, $styleValue('watermark', 'textColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php obs_overlay_render_color_picker('watermark', 'bgColor', $LANG['obs_overlay_style_bg_color'], $colorPalette, $styleValue('watermark', 'bgColor'), $LANG['obs_overlay_style_default']); ?>
                                    <?php $bgOpacityValue = $styleValue('watermark', 'bgOpacity'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_bg_opacity']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="watermark" data-style="bgOpacity" value="<?php echo iN_HelpSecure($bgOpacityValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="100" step="1" value="<?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($bgOpacityValue !== '' ? $bgOpacityValue : '100'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_font_size']);?></span>
                                        <?php obs_overlay_render_font_size_select('watermark', $styleValue('watermark', 'fontSize'), $LANG['obs_overlay_style_default']); ?>
                                    </label>
                                    <?php $borderRadiusValue = $styleValue('watermark', 'borderRadius'); ?>
                                    <label class="obs-style-range">
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_border_radius']);?></span>
                                        <div class="obs-style-range-field">
                                            <input type="hidden" class="obs-style-input obs-style-range-input" data-widget="watermark" data-style="borderRadius" value="<?php echo iN_HelpSecure($borderRadiusValue); ?>">
                                            <input type="range" class="obs-style-range-slider" min="0" max="40" step="1" value="<?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?>">
                                            <span class="obs-style-range-value"><?php echo iN_HelpSecure($borderRadiusValue !== '' ? $borderRadiusValue : '0'); ?></span>
                                        </div>
                                    </label>
                                    <label>
                                        <span><?php echo iN_HelpSecure($LANG['obs_overlay_style_text_align']);?></span>
                                        <select class="obs-style-input" data-widget="watermark" data-style="textAlign">
                                            <option value=""><?php echo iN_HelpSecure($LANG['obs_overlay_style_default']);?></option>
                                            <option value="left" <?php echo $styleValue('watermark', 'textAlign') === 'left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_left']);?></option>
                                            <option value="center" <?php echo $styleValue('watermark', 'textAlign') === 'center' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_center']);?></option>
                                            <option value="right" <?php echo $styleValue('watermark', 'textAlign') === 'right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['obs_overlay_style_right']);?></option>
                                        </select>
                                    </label>
                                </div>
                            </details>
                            <div class="obs-styles-actions">
                                <button type="button" class="i_nex_btn_btn transition obs-styles-save">
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_styles_save_button']);?>
                                </button>
                            </div>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_styles_note']);?></div>
                        </div>
                    </div>
                </div>

                <form class="obsOverlayForm" method="post">
                    <div class="i_warning obs_overlay_notice"></div>
                    <input type="hidden" name="f" value="obsOverlaySave">
                    <input type="hidden" name="obs_overlay_id" value="<?php echo $overlayId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_widgets_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <label class="box_not">
                                <input type="checkbox" name="widget_donation_total" value="1" <?php echo $widgetEnabled('donation_total', true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_donation_total']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_alerts" value="1" <?php echo $widgetEnabled('alerts', true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_alerts']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_milestone" value="1" <?php echo $widgetEnabled('milestone', true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_milestone']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_cta" value="1" <?php echo $widgetEnabled('cta', true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_cta']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_watermark" value="1" <?php echo $widgetEnabled('watermark', true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_watermark']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_notification_box" value="1" <?php echo $widgetEnabled('notification_box', false) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_notification_box']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_leaderboard" value="1" <?php echo $widgetEnabled('leaderboard', false) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_leaderboard']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_target_goal" value="1" <?php echo $widgetEnabled('target_goal', false) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_target_goal']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_last_supporter" value="1" <?php echo $widgetEnabled('last_supporter', false) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_last_supporter']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_running_text" value="1" <?php echo $widgetEnabled('running_text', false) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_running_text']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="widget_live_duration" value="1" <?php echo $widgetEnabled('live_duration', false) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_widget_live_duration']);?>
                            </label>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_widgets_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_label']);?></div>
                        <div class="i_settings_item_title_for">
                            <select name="donation_mode" class="flnm">
                                <option value="last24h" <?php echo $donationMode === 'last24h' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_last24h']);?>
                                </option>
                                <option value="alltime" <?php echo $donationMode === 'alltime' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_alltime']);?>
                                </option>
                            </select>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_milestone_title_label']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="milestone_title" class="flnm" value="<?php echo iN_HelpSecure($overlay['milestone_title'] ?? ''); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_milestone_title_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_milestone_title_note']);?></div>
                        </div>
                    </div>
                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_milestone_goal_label']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="milestone_goal_amount" class="flnm" value="<?php echo iN_HelpSecure($overlay['milestone_goal_amount'] ?? '0'); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_milestone_goal_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_milestone_goal_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_cta_label_label']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="cta_label" class="flnm" value="<?php echo iN_HelpSecure($overlay['cta_label'] ?? ''); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_cta_label_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_cta_label_note']);?></div>
                        </div>
                    </div>
                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_cta_url_label']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="cta_url" class="flnm" value="<?php echo iN_HelpSecure($overlay['cta_url'] ?? ''); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_cta_url_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_cta_url_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_watermark_label']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="watermark_text" class="flnm" value="<?php echo iN_HelpSecure($overlay['watermark_text'] ?? ''); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_watermark_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_watermark_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_notification_tiers_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <?php for ($tierIndex = 0; $tierIndex < 5; $tierIndex++) {
                                $tier = $notificationTiers[$tierIndex] ?? [];
                                $tierMin = (string)($tier['min_amount'] ?? '');
                                $tierLabel = (string)($tier['label'] ?? '');
                                $tierClass = (string)($tier['class_key'] ?? '');
                            ?>
                                <div class="obs-overlay-tier-row">
                                    <div class="obs-overlay-tier-field">
                                        <input type="text" name="notif_tier_min_amount[]" class="flnm" value="<?php echo iN_HelpSecure($tierMin); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_notification_min_amount']); ?>">
                                        <div class="obs-overlay-field-note"><?php echo iN_HelpSecure($LANG['obs_overlay_notification_min_amount_note']);?></div>
                                    </div>
                                    <div class="obs-overlay-tier-field">
                                        <input type="text" name="notif_tier_label[]" class="flnm" value="<?php echo iN_HelpSecure($tierLabel); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_notification_label']); ?>">
                                        <div class="obs-overlay-field-note"><?php echo iN_HelpSecure($LANG['obs_overlay_notification_label_note']);?></div>
                                    </div>
                                    <div class="obs-overlay-tier-field">
                                        <select name="notif_tier_class[]" class="flnm">
                                            <?php foreach ($notificationClassOptions as $classValue => $classLabel) { ?>
                                                <option value="<?php echo iN_HelpSecure($classValue); ?>" <?php echo $tierClass === $classValue ? 'selected' : ''; ?>>
                                                    <?php echo iN_HelpSecure($classLabel); ?>
                                                </option>
                                            <?php } ?>
                                            <?php if ($tierClass !== '' && !array_key_exists($tierClass, $notificationClassOptions)) { ?>
                                                <option value="<?php echo iN_HelpSecure($tierClass); ?>" selected>
                                                    <?php echo iN_HelpSecure(sprintf((string)$LANG['obs_overlay_notification_class_custom'], $tierClass)); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <div class="obs-overlay-field-note"><?php echo iN_HelpSecure($LANG['obs_overlay_notification_class_key_note']);?></div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_notification_tiers_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_leaderboard_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="number" name="leaderboard_limit" class="flnm" min="1" max="10" value="<?php echo iN_HelpSecure($leaderboardLimit); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_leaderboard_limit_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_leaderboard_limit_note']);?></div>
                            <select name="leaderboard_mode" class="flnm">
                                <option value="last24h" <?php echo $leaderboardMode === 'last24h' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_last24h']);?>
                                </option>
                                <option value="alltime" <?php echo $leaderboardMode === 'alltime' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_alltime']);?>
                                </option>
                                <option value="session" <?php echo $leaderboardMode === 'session' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_mode_session']);?>
                                </option>
                            </select>
                            <label class="box_not">
                                <input type="checkbox" name="leaderboard_include_tips" value="1" <?php echo in_array('tips', $leaderboardTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_tips']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="leaderboard_include_live_gift" value="1" <?php echo in_array('live_gift', $leaderboardTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_live_gift']);?>
                            </label>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_leaderboard_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_target_goal_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="target_goal_title" class="flnm" value="<?php echo iN_HelpSecure($targetGoalTitle); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_target_goal_title_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_target_goal_title_note']);?></div>
                            <input type="text" name="target_goal_amount" class="flnm" value="<?php echo iN_HelpSecure($targetGoalAmount); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_target_goal_amount_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_target_goal_amount_note']);?></div>
                            <select name="target_goal_mode" class="flnm">
                                <option value="last24h" <?php echo $targetGoalMode === 'last24h' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_last24h']);?>
                                </option>
                                <option value="alltime" <?php echo $targetGoalMode === 'alltime' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_alltime']);?>
                                </option>
                                <option value="session" <?php echo $targetGoalMode === 'session' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_mode_session']);?>
                                </option>
                            </select>
                            <label class="box_not">
                                <input type="checkbox" name="target_goal_include_tips" value="1" <?php echo in_array('tips', $targetGoalTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_tips']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="target_goal_include_live_gift" value="1" <?php echo in_array('live_gift', $targetGoalTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_live_gift']);?>
                            </label>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_target_goal_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_last_supporter_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="last_supporter_label" class="flnm" value="<?php echo iN_HelpSecure($lastSupporterLabel); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_last_supporter_label_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_last_supporter_label_note']);?></div>
                            <label class="box_not">
                                <input type="checkbox" name="last_supporter_show_amount" value="1" <?php echo $lastSupporterShowAmount ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_last_supporter_show_amount']);?>
                            </label>
                            <select name="last_supporter_mode" class="flnm">
                                <option value="last24h" <?php echo $lastSupporterMode === 'last24h' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_last24h']);?>
                                </option>
                                <option value="alltime" <?php echo $lastSupporterMode === 'alltime' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_donation_mode_alltime']);?>
                                </option>
                                <option value="session" <?php echo $lastSupporterMode === 'session' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_mode_session']);?>
                                </option>
                            </select>
                            <label class="box_not">
                                <input type="checkbox" name="last_supporter_include_tips" value="1" <?php echo in_array('tips', $lastSupporterTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_tips']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="last_supporter_include_live_gift" value="1" <?php echo in_array('live_gift', $lastSupporterTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_live_gift']);?>
                            </label>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_last_supporter_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_running_text_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <select name="running_text_mode" class="flnm">
                                <option value="custom" <?php echo $runningTextMode === 'custom' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_running_text_mode_custom']);?>
                                </option>
                                <option value="recent" <?php echo $runningTextMode === 'recent' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_running_text_mode_recent']);?>
                                </option>
                                <option value="leaderboard" <?php echo $runningTextMode === 'leaderboard' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['obs_overlay_running_text_mode_leaderboard']);?>
                                </option>
                            </select>
                            <input type="text" name="running_text_template" class="flnm" value="<?php echo iN_HelpSecure($runningTextTemplate); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_running_text_template_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_running_text_note']);?></div>
                            <input type="text" name="running_text_custom" class="flnm" value="<?php echo iN_HelpSecure($runningTextCustom); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_running_text_custom_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_running_text_custom_note']);?></div>
                            <input type="number" name="running_text_speed" class="flnm" min="5" max="120" value="<?php echo iN_HelpSecure($runningTextSpeed); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_running_text_speed_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_running_text_speed_note']);?></div>
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item">
                        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_title']);?></div>
                        <div class="i_settings_item_title_for">
                            <input type="text" name="live_extender_unit_amount" class="flnm" value="<?php echo iN_HelpSecure($liveExtenderUnitAmount); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_unit_amount_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_unit_amount_note']);?></div>
                            <input type="number" name="live_extender_seconds_per_unit" class="flnm" min="5" max="600" value="<?php echo iN_HelpSecure($liveExtenderSeconds); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_seconds_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_seconds_note']);?></div>
                            <input type="number" name="live_extender_max_seconds" class="flnm" min="0" max="21600" value="<?php echo iN_HelpSecure($liveExtenderMaxSeconds); ?>" placeholder="<?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_max_seconds_placeholder']); ?>">
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_max_seconds_note']);?></div>
                            <label class="box_not">
                                <input type="checkbox" name="live_extender_include_tips" value="1" <?php echo in_array('tips', $liveExtenderTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_tips']);?>
                            </label>
                            <label class="box_not">
                                <input type="checkbox" name="live_extender_include_live_gift" value="1" <?php echo in_array('live_gift', $liveExtenderTypes, true) ? 'checked' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['obs_overlay_include_live_gift']);?>
                            </label>
                            <div class="box_not"><?php echo iN_HelpSecure($LANG['obs_overlay_live_extender_note']);?></div>
                        </div>
                    </div>

                    <div class="i_become_creator_box_footer">
                        <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['obs_overlay_save_button']);?></button>
                    </div>
                </form>

                <form class="obsOverlayRevokeForm" method="post" data-confirm="<?php echo iN_HelpSecure($LANG['obs_overlay_revoke_confirm']); ?>">
                    <input type="hidden" name="f" value="obsOverlayRevoke">
                    <input type="hidden" name="obs_overlay_id" value="<?php echo $overlayId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                    <div class="i_become_creator_box_footer">
                        <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['obs_overlay_revoke_button']);?></button>
                    </div>
                </form>
                </div>
                <?php } ?>
                </div>
            <?php } else { ?>
                <div class="i_settings_wrapper_item">
                    <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['obs_overlay_no_overlays']);?></div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
