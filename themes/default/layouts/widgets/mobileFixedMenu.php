<?php
/**
 * Mobile fixed bottom navigation
 * Premium creator-platform redesign (visual only) — preserves existing
 * JS hooks: .mobile_srcbtn, .g_feed, .fms
 */
$mfm_currentUri   = isset($_SERVER['REQUEST_URI']) ? strtolower(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '') : '';
$mfm_currentUri   = trim($mfm_currentUri, '/');
$mfm_username     = isset($userName) ? strtolower((string)$userName) : '';

$mfm_isHome       = ($mfm_currentUri === '' || $mfm_currentUri === 'index.php');
$mfm_isReels      = (strpos($mfm_currentUri, 'reels') === 0);
$mfm_isCreators   = (strpos($mfm_currentUri, 'creators') === 0);
$mfm_isProfile    = ($mfm_username !== '' && $mfm_currentUri === $mfm_username);

$mfm_reelsActive  = isset($reelsFeatureStatus) && (string)$reelsFeatureStatus === '1';

$mfm_L = [
    'home'     => $LANG['menu_home']     ?? ($LANG['home'] ?? 'Home'),
    'feed'     => $LANG['menu_feed']     ?? ($LANG['explore'] ?? 'Feed'),
    'reels'    => $LANG['menu_reels']    ?? ($LANG['reels'] ?? 'Reels'),
    'search'   => $LANG['menu_search']   ?? ($LANG['search'] ?? 'Search'),
    'creators' => $LANG['menu_creators'] ?? ($LANG['creators'] ?? 'Creators'),
    'profile'  => $LANG['menu_profile']  ?? ($LANG['profile'] ?? 'Profile'),
];
?>
<div class="mobile_footer_fixed_menu_container mfm_v2" role="navigation" aria-label="Primary">
    <div class="mfm_bar">
        <div class="mobile_fixed_box_wrapper mfm_grid flex_ tabing">
            <!-- HOME -->
            <div class="mobile_box mfm_item<?php echo $mfm_isHome ? ' is-active' : ''; ?>">
                <a href="<?php echo filter_var($base_url, FILTER_VALIDATE_URL); ?>" aria-label="<?php echo iN_HelpSecure($mfm_L['home']); ?>">
                    <div class="i_m_box_item transition flex_ tabing">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('99')); ?>
                    </div>
                    <span class="mfm_label"><?php echo iN_HelpSecure($mfm_L['home']); ?></span>
                </a>
            </div>

            <!-- FEED (reload/explore) -->
            <div class="mobile_box mfm_item g_feed" data-get="allPosts" data-type="moreexplore" aria-label="<?php echo iN_HelpSecure($mfm_L['feed']); ?>">
                <div class="i_m_box_item transition flex_ tabing">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('8')); ?>
                </div>
                <span class="mfm_label"><?php echo iN_HelpSecure($mfm_L['feed']); ?></span>
            </div>

            <?php if ($mfm_reelsActive) { ?>
            <!-- REELS (elevated center action) -->
            <div class="mobile_box mfm_item mfm_center<?php echo $mfm_isReels ? ' is-active' : ''; ?>">
                <a href="<?php echo filter_var($base_url, FILTER_VALIDATE_URL); ?>reels/" aria-label="<?php echo iN_HelpSecure($mfm_L['reels']); ?>">
                    <div class="i_m_box_item mfm_fab transition flex_ tabing">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('187')); ?>
                    </div>
                    <span class="mfm_label mfm_label_fab"><?php echo iN_HelpSecure($mfm_L['reels']); ?></span>
                </a>
            </div>
            <?php } ?>

            <!-- SEARCH -->
            <div class="mobile_box mfm_item fms" aria-label="<?php echo iN_HelpSecure($mfm_L['search']); ?>">
                <div class="i_m_box_item transition mobile_srcbtn flex_ tabing">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?>
                </div>
                <span class="mfm_label"><?php echo iN_HelpSecure($mfm_L['search']); ?></span>
            </div>

            <!-- CREATORS -->
            <div class="mobile_box mfm_item<?php echo $mfm_isCreators ? ' is-active' : ''; ?>">
                <a href="<?php echo filter_var($base_url, FILTER_VALIDATE_URL); ?>creators" aria-label="<?php echo iN_HelpSecure($mfm_L['creators']); ?>">
                    <div class="i_m_box_item transition flex_ tabing">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95')); ?>
                    </div>
                    <span class="mfm_label"><?php echo iN_HelpSecure($mfm_L['creators']); ?></span>
                </a>
            </div>

            <!-- PROFILE -->
            <div class="mobile_box mfm_item<?php echo $mfm_isProfile ? ' is-active' : ''; ?>">
                <a href="<?php echo filter_var($base_url, FILTER_VALIDATE_URL) . $userName; ?>" aria-label="<?php echo iN_HelpSecure($mfm_L['profile']); ?>">
                    <div class="i_m_box_item transition flex_ tabing">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('83')); ?>
                    </div>
                    <span class="mfm_label"><?php echo iN_HelpSecure($mfm_L['profile']); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================================
   Mobile Fixed Menu — Premium Creator Platform (v2)
   Scoped via .mfm_v2 to override legacy rules safely.
   ============================================================ */
.mobile_footer_fixed_menu_container.mfm_v2 {
    background: transparent !important;
    padding: 0 !important;
    pointer-events: none;
    padding-bottom: env(safe-area-inset-bottom, 0) !important;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_bar {
    pointer-events: auto;
    margin: 0 12px 10px 12px;
    border-radius: 22px;
    padding: 2px 19px;
    background: rgba(255, 255, 255, 0.82);
    -webkit-backdrop-filter: saturate(160%) blur(18px);
            backdrop-filter: saturate(160%) blur(18px);
    border: 1px solid rgba(255, 255, 255, 0.55);
    box-shadow:
        0 10px 30px rgba(15, 17, 23, 0.18),
        0 2px 6px rgba(15, 17, 23, 0.08),
        inset 0 0 0 1px rgba(255, 255, 255, 0.4);
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_grid {
    max-width: 520px;
    margin: 0 auto;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
        -ms-flex-align: center;
            align-items: center;
    -webkit-box-pack: justify;
        -ms-flex-pack: justify;
            justify-content: space-between;
    gap: 2px;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item {
    flex: 1 1 0;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: center;
        -ms-flex-pack: center;
            justify-content: center;
    width: auto;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item > a,
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.g_feed,
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.fms {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    flex-direction: column;
    -webkit-box-align: center;
        -ms-flex-align: center;
            align-items: center;
    gap: 2px;
    text-decoration: none;
    width: 100%;
    padding: 6px 2px 4px 2px;
    border-radius: 14px;
    position: relative;
    -webkit-tap-highlight-color: transparent;
    cursor: pointer;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item .i_m_box_item {
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 12px;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: center;
        -ms-flex-pack: center;
            justify-content: center;
    -webkit-box-align: center;
        -ms-flex-align: center;
            align-items: center;
    margin: 0;
    background: transparent;
    -webkit-transition: transform 0.2s ease, background 0.25s ease;
    transition: transform 0.2s ease, background 0.25s ease;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item .i_m_box_item svg {
    width: 22px;
    height: 22px;
    fill: #3f4454;
    -webkit-transition: fill 0.2s ease;
    transition: fill 0.2s ease;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item:active .i_m_box_item {
    transform: scale(0.92);
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_label {
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #5b6275;
    font-family: system-ui, -apple-system, sans-serif;
    line-height: 1;
    -webkit-transition: color 0.2s ease;
    transition: color 0.2s ease;
}

/* Active state — gradient pill indicator */
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.is-active .i_m_box_item {
    background: -webkit-gradient(linear, left top, right top, from(rgba(246,81,105,0.14)), to(rgba(250,180,41,0.14)));
    background: linear-gradient(90deg, rgba(246,81,105,0.14) 0%, rgba(250,180,41,0.14) 100%);
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.is-active .i_m_box_item svg {
    fill: #f65169;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.is-active .mfm_label {
    color: #f65169;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.is-active::before {
    content: "";
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 22px;
    height: 3px;
    border-radius: 999px;
    background: -webkit-gradient(linear, left top, right top, from(#f65169), to(#fab429));
    background: linear-gradient(90deg, #f65169, #fab429);
}

/* Elevated FAB for Reels (center) */
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_center { position: relative; }
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_center a { padding-top: 0; }
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_fab {
    width: 52px !important;
    height: 52px !important;
    border-radius: 18px !important;
    background: -webkit-gradient(linear, left top, right bottom, from(#f65169), to(#fab429)) !important;
    background: linear-gradient(135deg, #f65169 0%, #fab429 100%) !important;
    box-shadow:
        0 10px 22px rgba(246, 81, 105, 0.38),
        0 2px 6px rgba(250, 180, 41, 0.28);
    transform: translateY(-14px);
    border: 3px solid rgba(255,255,255,0.9);
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_fab svg {
    width: 24px !important;
    height: 24px !important;
    fill: #ffffff !important;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_label_fab {
    margin-top: -10px;
    color: #f65169 !important;
    font-weight: 700;
}
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_center.is-active::before { display: none; }

/* Dark mode (system + app-level) */
@media (prefers-color-scheme: dark) {
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_bar {
        background: rgba(24, 25, 26, 0.82);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow:
            0 14px 34px rgba(0, 0, 0, 0.55),
            0 2px 6px rgba(0, 0, 0, 0.35),
            inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    }
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_item .i_m_box_item svg { fill: #c8ccd6; }
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_label { color: #a8aebd; }
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_fab { border-color: #18191a; }
}
body.night .mobile_footer_fixed_menu_container.mfm_v2 .mfm_bar,
.body_dark .mobile_footer_fixed_menu_container.mfm_v2 .mfm_bar {
    background: rgba(24, 25, 26, 0.82);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow:
        0 14px 34px rgba(0, 0, 0, 0.55),
        0 2px 6px rgba(0, 0, 0, 0.35),
        inset 0 0 0 1px rgba(255, 255, 255, 0.04);
}
body.night .mobile_footer_fixed_menu_container.mfm_v2 .mfm_item .i_m_box_item svg,
.body_dark .mobile_footer_fixed_menu_container.mfm_v2 .mfm_item .i_m_box_item svg { fill: #c8ccd6; }
body.night .mobile_footer_fixed_menu_container.mfm_v2 .mfm_label,
.body_dark .mobile_footer_fixed_menu_container.mfm_v2 .mfm_label { color: #a8aebd; }
body.night .mobile_footer_fixed_menu_container.mfm_v2 .mfm_fab,
.body_dark .mobile_footer_fixed_menu_container.mfm_v2 .mfm_fab { border-color: #18191a; }

/* Keep search visible on mobile (legacy .fms was display:none) */
.mobile_footer_fixed_menu_container.mfm_v2 .fms { display: flex !important; }

/* Compact layout for very small screens */
@media (max-width: 360px) {
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_label:not(.mfm_label_fab) { display: none; }
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_item > a,
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.g_feed,
    .mobile_footer_fixed_menu_container.mfm_v2 .mfm_item.fms { padding-bottom: 8px; }
}

/* Neutralize legacy hover */
.mobile_footer_fixed_menu_container.mfm_v2 .i_m_box_item:hover { background-color: transparent; }
.mobile_footer_fixed_menu_container.mfm_v2 .mfm_item:hover .i_m_box_item { background: rgba(246,81,105,0.08); }
</style>
