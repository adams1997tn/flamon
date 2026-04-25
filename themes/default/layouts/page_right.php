<?php if ($logedIn === '0') { ?>
<style>
.wrapper.NotLoginYet .rightSticky .footer_container{position:relative!important;bottom:auto!important;}
</style>
<?php } ?>
<div class="rightSticky">
    <div class="i_right_container">
        <div class="rightSidebar_in">
            <div class="leftSidebarWrapper leftSidebarWrapper_mobile">
                <div class="btest">
                    <?php if ($logedIn === '1') { ?>
                        <?php
                        $isCreator = (string) $feesStatus === '2';
                        $userVerifiedStatus = isset($userData['user_verified_status']) ? (string) $userData['user_verified_status'] : '0';
                        $profileCategoryValue = trim((string) ($userData['profile_category'] ?? $userProfileCategory ?? ''));
                        $profileCategoryKey = strtolower($profileCategoryValue);
                        $profileCategoryLabel = '';
                        if ($profileCategoryKey !== '' && $profileCategoryKey !== 'normal_user') {
                            if (isset($PROFILE_CATEGORIES[$profileCategoryKey])) {
                                $profileCategoryLabel = $PROFILE_CATEGORIES[$profileCategoryKey];
                            } elseif (isset($PROFILE_SUBCATEGORIES[$profileCategoryKey])) {
                                $profileCategoryLabel = $PROFILE_SUBCATEGORIES[$profileCategoryKey];
                            } else {
                                $profileCategoryLabel = ucwords(str_replace(['_', '-'], ' ', $profileCategoryValue));
                            }
                        }

                        $weeklyFeeEnabled = isset($WeeklySubDetail['plan_status'])
                            && (string) $WeeklySubDetail['plan_status'] === '1';
                        $monthlyFeeEnabled = isset($MonthlySubDetail['plan_status'])
                            && (string) $MonthlySubDetail['plan_status'] === '1';
                        $yearlyFeeEnabled = isset($YearlySubDetail['plan_status'])
                            && (string) $YearlySubDetail['plan_status'] === '1';
                        $hasActiveFee = $weeklyFeeEnabled || $monthlyFeeEnabled || $yearlyFeeEnabled;
                        $needsFeesSetup = $isCreator && !$hasActiveFee;
                        $needsProfileInfo = $isCreator && empty($userBirthDay);
                        $showCreatorAlerts = $needsFeesSetup || $needsProfileInfo;

                        $creatorStatusKey = '';
                        $creatorStatusClass = '';
                        if ($isCreator) {
                            if ($hasActiveFee) {
                                $creatorStatusKey = 'creator_status_active';
                                $creatorStatusClass = 'is-active';
                            } else {
                                $creatorStatusKey = 'creator_status_fees_off';
                                $creatorStatusClass = 'is-warning';
                            }
                        } elseif (
                            (string) $conditionStatus === '1'
                            || (string) $validationStatus === '1'
                            || (string) $certificationStatus === '1'
                        ) {
                            $creatorStatusKey = 'creator_status_pending';
                            $creatorStatusClass = 'is-pending';
                        }

                        $bioPreview = trim(strip_tags((string) $userBio));
                        if ($bioPreview !== '') {
                            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                                if (mb_strlen($bioPreview) > 90) {
                                    $bioPreview = mb_substr($bioPreview, 0, 90) . '...';
                                }
                            } elseif (strlen($bioPreview) > 90) {
                                $bioPreview = substr($bioPreview, 0, 90) . '...';
                            }
                        }

                        $profileCompletionItems = [
                            'avatar' => !empty($userData['user_avatar']),
                            'cover' => !empty($userData['user_cover']),
                            'bio' => $bioPreview !== '',
                            'category' => $profileCategoryKey !== '' && $profileCategoryKey !== 'normal_user',
                            'birthday' => !empty($userBirthDay),
                        ];
                        $profileCompletionTotal = count($profileCompletionItems);
                        $profileCompletionDone = count(array_filter($profileCompletionItems));
                        $profileCompletionPercent = $profileCompletionTotal > 0
                            ? (int) round(($profileCompletionDone / $profileCompletionTotal) * 100)
                            : 0;

                        $monthlyTotalEarnings = null;
                        if ($isCreator) {
                            $monthlySubEarning = (float) $iN->iN_CalculateSubEarnings($userID);
                            $monthlyPremiumEarning = (float) $iN->iN_CurrentMonthTotalPremiumEarningUser($userID);
                            $monthlyTotalEarnings = $monthlySubEarning + $monthlyPremiumEarning;
                        }
                        ?>
                        <div class="i_sidebar_profile_card">
                            <div class="i_sidebar_profile_cover" style="background-image: url('<?php echo iN_HelpSecure($userCover); ?>');"></div>
                            <div class="i_sidebar_profile_body">
                                <div class="i_sidebar_profile_header">
                                    <a class="i_sidebar_profile_avatar" href="<?php echo iN_HelpSecure($userProfileUrl); ?>">
                                        <img src="<?php echo iN_HelpSecure($userAvatar); ?>" alt="<?php echo iN_HelpSecure($userFullName); ?>">
                                    </a>
                                </div>
                                <div class="i_sidebar_profile_meta">
                                    <a class="i_sidebar_profile_name" href="<?php echo iN_HelpSecure($userProfileUrl); ?>">
                                        <?php echo iN_HelpSecure($userFullName); ?>
                                        <?php if ($userVerifiedStatus === '1') { ?>
                                            <span class="i_sidebar_profile_verified"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('11')); ?></span>
                                        <?php } ?>
                                    </a>
                                    <div class="i_sidebar_profile_handle">@<?php echo iN_HelpSecure($userName); ?></div>
                                    <?php if ($profileCategoryLabel !== '') { ?>
                                        <div class="i_sidebar_profile_category">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('65')); ?>
                                            <span><?php echo iN_HelpSecure($profileCategoryLabel); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php if ($creatorStatusKey !== '') { ?>
                                    <div class="i_sidebar_profile_badges">
                                        <span class="i_sidebar_profile_badge <?php echo iN_HelpSecure($creatorStatusClass); ?>">
                                            <?php echo iN_HelpSecure($LANG[$creatorStatusKey]); ?>
                                        </span>
                                    </div>
                                <?php } ?>
                                <div class="i_sidebar_profile_bio">
                                    <?php if ($bioPreview !== '') { ?>
                                        <?php echo iN_HelpSecure($bioPreview); ?>
                                    <?php } else { ?>
                                        <a class="i_sidebar_profile_bio_empty" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=my_profile">
                                            <?php echo iN_HelpSecure($LANG['add_short_bio']); ?>
                                        </a>
                                    <?php } ?>
                                </div>
                                <div class="i_sidebar_profile_quick">
                                    <a class="i_sidebar_profile_quick_btn" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=my_profile">
                                        <?php echo iN_HelpSecure($LANG['edit_profile_short']); ?>
                                    </a>
                                    <?php if ($isCreator) { ?>
                                        <a class="i_sidebar_profile_quick_btn" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=fees">
                                            <?php echo iN_HelpSecure($LANG['edit_fees_short']); ?>
                                        </a>
                                    <?php } ?>
                                    <button type="button" class="i_sidebar_profile_quick_btn copyUrl ownTooltip" data-label="<?php echo iN_HelpSecure($LANG['copy_profile_url']); ?>" data-clipboard-text="<?php echo iN_HelpSecure($userProfileUrl); ?>">
                                        <?php echo iN_HelpSecure($LANG['share_profile']); ?>
                                    </button>
                                </div>
                                <div class="i_sidebar_profile_stats">
                                    <a class="i_sidebar_profile_stat" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=im_following">
                                        <span class="stat_value"><?php echo iN_HelpSecure(number_format((int) $totalFollowingUsers)); ?></span>
                                        <span class="stat_label"><?php echo iN_HelpSecure($LANG['im_following']); ?></span>
                                    </a>
                                    <a class="i_sidebar_profile_stat" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=my_followers">
                                        <span class="stat_value"><?php echo iN_HelpSecure(number_format((int) $totalFollowerUsers)); ?></span>
                                        <span class="stat_label"><?php echo iN_HelpSecure($LANG['my_followers']); ?></span>
                                    </a>
                                    <?php if ($isCreator) { ?>
                                        <a class="i_sidebar_profile_stat" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=subscriptions">
                                            <span class="stat_value"><?php echo iN_HelpSecure(number_format((int) $totalSubscriptions)); ?></span>
                                            <span class="stat_label"><?php echo iN_HelpSecure($LANG['subscriptions']); ?></span>
                                        </a>
                                    <?php } ?>
                                </div>
                                <?php if ($isCreator && $monthlyTotalEarnings !== null) { ?>
                                    <div class="i_sidebar_profile_earnings">
                                        <span class="earnings_label"><?php echo iN_HelpSecure($LANG['earnings_this_month']); ?></span>
                                        <span class="earnings_value"><?php echo iN_HelpSecure(formatCurrency($monthlyTotalEarnings, $defaultCurrency)); ?></span>
                                    </div>
                                <?php } ?>
                                <div class="i_sidebar_profile_progress">
                                    <div class="i_sidebar_profile_progress_header">
                                        <span class="progress_title"><?php echo iN_HelpSecure($LANG['profile_completion']); ?></span>
                                        <span class="progress_value"><?php echo iN_HelpSecure($profileCompletionPercent); ?>%</span>
                                    </div>
                                    <div class="i_sidebar_profile_progress_bar">
                                        <span class="i_sidebar_profile_progress_fill" style="width: <?php echo iN_HelpSecure($profileCompletionPercent); ?>%;"></span>
                                    </div>
                                    <div class="i_sidebar_profile_progress_list">
                                        <span class="i_sidebar_profile_progress_item <?php echo $profileCompletionItems['avatar'] ? 'is-done' : ''; ?>">
                                            <?php echo iN_HelpSecure($LANG['profile_completion_avatar']); ?>
                                        </span>
                                        <span class="i_sidebar_profile_progress_item <?php echo $profileCompletionItems['cover'] ? 'is-done' : ''; ?>">
                                            <?php echo iN_HelpSecure($LANG['profile_completion_cover']); ?>
                                        </span>
                                        <span class="i_sidebar_profile_progress_item <?php echo $profileCompletionItems['bio'] ? 'is-done' : ''; ?>">
                                            <?php echo iN_HelpSecure($LANG['profile_completion_bio']); ?>
                                        </span>
                                        <span class="i_sidebar_profile_progress_item <?php echo $profileCompletionItems['category'] ? 'is-done' : ''; ?>">
                                            <?php echo iN_HelpSecure($LANG['profile_completion_category']); ?>
                                        </span>
                                        <span class="i_sidebar_profile_progress_item <?php echo $profileCompletionItems['birthday'] ? 'is-done' : ''; ?>">
                                            <?php echo iN_HelpSecure($LANG['profile_completion_birthday']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($isCreator && $showCreatorAlerts) { ?>
                                    <div class="i_sidebar_profile_alerts">
                                        <?php if ($needsFeesSetup) { ?>
                                            <a class="i_sidebar_profile_alert is-critical" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=fees">
                                                <?php echo iN_HelpSecure($LANG['setup_subscribers_fee']); ?>
                                            </a>
                                        <?php } ?>
                                        <?php if ($needsProfileInfo) { ?>
                                            <a class="i_sidebar_profile_alert is-warning" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=my_profile">
                                                <?php echo iN_HelpSecure($LANG['complete_profile_info']); ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                                <div class="i_sidebar_profile_actions">
                                    <a class="i_sidebar_profile_btn transition" href="<?php echo iN_HelpSecure($userProfileUrl); ?>">
                                        <?php echo iN_HelpSecure($LANG['go_profile_page']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php
                    // Show 'Become a Creator' box if eligible
                    if ($conditionStatus === '0' && $beaCreatorStatus === 'request') {
                        include __DIR__ . '/widgets/becomeCreator.php';
                    }

                    // Top Inoras widget
                    include __DIR__ . '/widgets/topinoras.php';
                    include __DIR__ . '/widgets/trending_hashtags.php';

                    // Friends activity only if user is logged in
                    if ($logedIn === '1') {
                        include __DIR__ . '/widgets/friendsActivity.php';
                    }

                    // Other public widgets
                    if ($logedIn === '1') {
                        include __DIR__ . '/widgets/inviteWithEmail.php';
                    }
                    include __DIR__ . '/widgets/sponsored.php';
                    include __DIR__ . '/widgets/suggestedProducts.php';
                    include __DIR__ . '/widgets/agency_boosted_creators.php';
                    include __DIR__ . '/widgets/suggested_creators.php';
                    include_once __DIR__ . '/widgets/ads_helper.php';
                    $legacySidebar = [
                        'client' => $adsenseClientId ?? '',
                        'slot' => $adsenseSlotSidebar ?? ($adsenseSlotTop ?? ''),
                        'size' => $adsenseSidebarSize ?? ($adsenseTopSize ?? 'responsive')
                    ];
                    echo iN_render_ad_with_legacy('sidebar', 'sidebar', $legacySidebar);
                    ?>

                    <div class="footer_container">
                        <?php include __DIR__ . '/footer.php'; ?>
                    </div>

                   
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll Mouse Icon -->
    <div class="i_yesScrollable">
        <div class="mouse_scroll">
            <div class="mouse">
                <div class="wheel"></div>
            </div>
            <div>
                <span class="m_scroll_arrows unu"></span>
                <span class="m_scroll_arrows doi"></span>
                <span class="m_scroll_arrows trei"></span>
            </div>
        </div>
    </div>
</div>

<script>
    // Expose login and creator status to JS
    window.userIsLoggedIn = <?php echo $logedIn === '1' ? 'true' : 'false'; ?>;
    window.userIsCreator = <?php echo ($logedIn === '1' && $userType === '2') ? 'true' : 'false'; ?>;
</script>

<!-- Right Sidebar JS Handlers -->
<?php if ($logedIn === '1') { ?>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/rightSidebarHandler.js?v=<?php echo iN_HelpSecure($version); ?>&r=rslock13"></script>
<?php } ?>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/greenaudioplayer/audioplayer.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
