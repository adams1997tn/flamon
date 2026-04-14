<?php
$menuRequestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
$menuQueryString = (string) parse_url($menuRequestUri, PHP_URL_QUERY);
$menuQueryParams = [];

if ($menuQueryString !== '') {
    parse_str($menuQueryString, $menuQueryParams);
}

$menuBasePath = trim((string) parse_url($base_url, PHP_URL_PATH), '/');
$normalizeMenuPath = static function ($path, $basePath) {
    $normalizedPath = trim((string) $path, '/');
    if ($basePath !== '' && strpos($normalizedPath, $basePath) === 0) {
        $normalizedPath = trim(substr($normalizedPath, strlen($basePath)), '/');
    }
    return $normalizedPath;
};
$menuIsActivePath = static function ($targetUrl, $currentPath, $basePath, $normalizer) {
    $targetPath = $normalizer((string) parse_url((string) $targetUrl, PHP_URL_PATH), $basePath);
    return $targetPath !== '' && $targetPath === $currentPath;
};
$menuActiveClass = static function ($isActive) {
    return $isActive ? ' is-active' : '';
};

$menuCurrentPath = $normalizeMenuPath((string) parse_url($menuRequestUri, PHP_URL_PATH), $menuBasePath);
$profileUrl = isset($userProfileUrl) ? (string) $userProfileUrl : '';

$activeHome = $menuCurrentPath === '' || $menuCurrentPath === 'index.php';
$activeReels = strpos($menuCurrentPath, 'reels') === 0;
$activeMarketplace = strpos($menuCurrentPath, 'marketplace') === 0;
$activeProfile = $profileUrl !== '' && $menuIsActivePath($profileUrl, $menuCurrentPath, $menuBasePath, $normalizeMenuPath);
$activeDashboard = strpos($menuCurrentPath, 'settings') === 0 && (($menuQueryParams['tab'] ?? '') === 'dashboard');
$activeCreators = strpos($menuCurrentPath, 'creators') === 0;
$activeCommunities = strpos($menuCurrentPath, 'communities') === 0;
$activeAgencies = strpos($menuCurrentPath, 'agencies') === 0;
$activeLivePaid = strpos($menuCurrentPath, 'live_streams') === 0 && (($menuQueryParams['live'] ?? '') === 'paid');
$activeLiveFree = strpos($menuCurrentPath, 'live_streams') === 0 && (($menuQueryParams['live'] ?? '') === 'free');
$activeLiveScheduled = strpos($menuCurrentPath, 'live_streams') === 0 && (($menuQueryParams['live'] ?? '') === 'scheduled');

$hasPaidLiveMenu = $logedIn === '1' && $paidLiveStreamingStatus === '1' && $feesStatus === '2';
$hasFreeLiveMenu = $logedIn === '1' && $freeLiveStreamingStatus === '1';
$hasScheduledLiveMenu = $agoraStatus === '1' && $page !== 'profile';

$paidLiveSectionClass = $hasPaidLiveMenu ? ' left-menu-section-start' : '';
$freeLiveSectionClass = (!$hasPaidLiveMenu && $hasFreeLiveMenu) ? ' left-menu-section-start' : '';
$scheduledLiveSectionClass = (!$hasPaidLiveMenu && !$hasFreeLiveMenu && $hasScheduledLiveMenu) ? ' left-menu-section-start' : '';
?>
<div class="leftSticky mobile_left left-menu-pro-v1">
    <div class="i_left_container">
        <div class="leftSidebar_in">
            <div class="leftSidebarWrapper">
                <div class="btest">
                    <!-- Home Menu -->
                    <a href="<?php echo iN_HelpSecure($base_url); ?>">
                        <div class="i_left_menu_box transition<?php echo $menuActiveClass($activeHome); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('99')); ?>
                            <div class="m_tit"><?php echo iN_HelpSecure($LANG['home_page']); ?></div>
                        </div>
                    </a>
                    <!-- Reels Posts -->
                    <?php if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus === '1') : ?>
                        <a href="<?php echo iN_HelpSecure($base_url); ?>reels/">
                            <div class="i_left_menu_box transition<?php echo $menuActiveClass($activeReels); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('187')); ?>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['reels']); ?></div>
                            </div>
                        </a>
                    <?php endif; ?>
                    <!-- Trending Posts -->
                    <div class="i_left_menu_box transition g_feed" data-get="trendposts" data-type="moretrendposts">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('181')); ?>
                        <div class="m_tit"><?php echo iN_HelpSecure($LANG['trend_posts']); ?></div>
                    </div>

                    <?php if ($iN->iN_ShopStatus(1) === 'yes') { ?>
                        <!-- Marketplace -->
                        <a href="<?php echo iN_HelpSecure($base_url); ?>marketplace?cat=all">
                            <div class="i_left_menu_box transition<?php echo $menuActiveClass($activeMarketplace); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['marketplace']); ?></div>
                            </div>
                        </a>
                    <?php } ?>

                    <?php if ($logedIn === '1') { ?>
                        <!-- Profile -->
                        <a href="<?php echo iN_HelpSecure($userProfileUrl); ?>">
                            <div class="i_left_menu_box transition left-menu-section-start<?php echo $menuActiveClass($activeProfile); ?>">
                                <div class="i_left_menu_profile_avatar">
                                    <img src="<?php echo iN_HelpSecure($userAvatar); ?>" alt="<?php echo iN_HelpSecure($userFullName); ?>" />
                                </div>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['profile']); ?></div>
                            </div>
                        </a>

                        <?php if ($feesStatus === '2') { ?>
                            <!-- Dashboard -->
                            <a href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=dashboard">
                                <div class="i_left_menu_box transition<?php echo $menuActiveClass($activeDashboard); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('35')); ?>
                                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['dashboard']); ?></div>
                                </div>
                            </a>
                        <?php } ?>

                        <!-- Purchased Premium Posts -->
                        <div class="i_left_menu_box transition g_feed" data-get="purchasedpremiums" data-type="morepurchased">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('176')); ?>
                            <div class="m_tit"><?php echo iN_HelpSecure($LANG['purchased_premium_posts']); ?></div>
                        </div>

                        <?php if ($boostedPostEnableDisable === 'yes' && $iN->iN_CheckHaveBoostedPost($userID) > 0) { ?>
                            <!-- Boosted Posts -->
                            <div class="i_left_menu_box transition g_feed" data-get="boostedposts" data-type="moreboostedposts">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('178')); ?>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['boosted_posts']); ?></div>
                            </div>
                        <?php } ?>

                        <!-- News Feed -->
                        <div class="i_left_menu_box transition g_feed" data-get="friends" data-type="moreposts">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('7')); ?>
                            <div class="m_tit"><?php echo iN_HelpSecure($LANG['newsfeed']); ?></div>
                        </div>

                        <!-- Explore -->
                        <div class="i_left_menu_box transition g_feed" data-get="allPosts" data-type="moreexplore">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('8')); ?>
                            <div class="m_tit"><?php echo iN_HelpSecure($LANG['explore']); ?></div>
                        </div>

                        <!-- Premium -->
                        <div class="i_left_menu_box transition g_feed" data-get="premiums" data-type="morepremium">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('9')); ?>
                            <div class="m_tit"><?php echo iN_HelpSecure($LANG['premium']); ?></div>
                        </div>

                        <!-- Our Creators -->
                        <a href="<?php echo iN_HelpSecure($base_url); ?>creators">
                            <div class="i_left_menu_box transition left-menu-section-start<?php echo $menuActiveClass($activeCreators); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95')); ?>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['our_creators']); ?></div>
                            </div>
                        </a>

                        <!-- Communities -->
                        <a href="<?php echo iN_HelpSecure($base_url); ?>communities">
                            <div class="i_left_menu_box transition<?php echo $menuActiveClass($activeCommunities); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['communities']); ?></div>
                            </div>
                        </a>
                        <?php if ((isset($agencyModuleStatus) ? $agencyModuleStatus : 'yes') === 'yes') { ?>
                            <!-- Agencies -->
                            <a href="<?php echo iN_HelpSecure($base_url); ?>agencies">
                                <div class="i_left_menu_box transition<?php echo $menuActiveClass($activeAgencies); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('195')); ?>
                                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['agency_module_title']); ?></div>
                                </div>
                            </a>
                        <?php } ?>
                    <?php } ?>

                    <?php if ($hasScheduledLiveMenu) { ?>
                        <?php if ($hasPaidLiveMenu) { ?>
                            <!-- Paid Live Streaming -->
                            <a href="<?php echo iN_HelpSecure($base_url); ?>live_streams?live=paid">
                                <div class="i_left_menu_box transition<?php echo $paidLiveSectionClass . $menuActiveClass($activeLivePaid); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('133')); ?>
                                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['paid_live_streamings']); ?></div>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($hasFreeLiveMenu) { ?>
                            <!-- Free Live Streaming -->
                            <a href="<?php echo iN_HelpSecure($base_url); ?>live_streams?live=free">
                                <div class="i_left_menu_box transition<?php echo $freeLiveSectionClass . $menuActiveClass($activeLiveFree); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('134')); ?>
                                    <div class="m_tit"><?php echo iN_HelpSecure($LANG['free_live_streams']); ?></div>
                                </div>
                            </a>
                        <?php } ?>

                        <a href="<?php echo iN_HelpSecure($base_url); ?>live_streams?live=scheduled">
                            <div class="i_left_menu_box transition<?php echo $scheduledLiveSectionClass . $menuActiveClass($activeLiveScheduled); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73')); ?>
                                <div class="m_tit"><?php echo iN_HelpSecure($LANG['live_scheduled_list_title']); ?></div>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
