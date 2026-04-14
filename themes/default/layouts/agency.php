<?php
$agencyIdValue = isset($agencyIdValue) ? (int)$agencyIdValue : (isset($agencyId) ? (int)$agencyId : 0);
$agencyData = $agencyIdValue > 0 ? $iN->iN_GetAgencyProfile($agencyIdValue) : null;
$agencyName = $agencyData['agency_name'] ?? '';
if ($agencyName !== '') {
    $siteTitle = $agencyName;
}
$agencyStatus = $agencyData['agency_status'] ?? '';
$agencyFee = isset($agencyData['agency_fee']) ? (float)$agencyData['agency_fee'] : 0.0;
$agencyProfileAboutEnabled = (isset($agencyProfileAboutStatus) ? $agencyProfileAboutStatus : 'yes') === 'yes';
$agencyProfileServicesEnabled = (isset($agencyProfileServicesStatus) ? $agencyProfileServicesStatus : 'yes') === 'yes';
$agencyProfileLogoEnabled = (isset($agencyProfileLogoStatus) ? $agencyProfileLogoStatus : 'yes') === 'yes';
$agencyProfileCoverEnabled = (isset($agencyProfileCoverStatus) ? $agencyProfileCoverStatus : 'yes') === 'yes';
$agencyProfileSocialEnabled = (isset($agencyProfileSocialStatus) ? $agencyProfileSocialStatus : 'yes') === 'yes';
$agencyAbout = $agencyProfileAboutEnabled ? ($agencyData['agency_about'] ?? '') : '';
$agencyServices = $agencyProfileServicesEnabled ? ($agencyData['agency_services'] ?? '') : '';
$ownerId = isset($agencyData['owner_iuid_fk']) ? (int)$agencyData['owner_iuid_fk'] : 0;
$ownerData = $ownerId > 0 ? $iN->iN_GetUserDetails($ownerId) : null;
$ownerName = $ownerData ? ($ownerData['i_user_fullname'] ?: ($ownerData['i_username'] ?? '')) : '';
$ownerUrl = $ownerData && !empty($ownerData['i_username']) ? $base_url . $ownerData['i_username'] : '';
$logoUrl = ($agencyData && $agencyProfileLogoEnabled) ? $iN->iN_AgencyLogoUrl($agencyData, $base_url) : ($base_url . 'uploads/avatars/no_gender.png');
$coverUrl = ($agencyData && $agencyProfileCoverEnabled) ? $iN->iN_AgencyCoverUrl($agencyData, $base_url) : ($base_url . 'uploads/covers/default.jpg');
$statusLabel = $agencyStatus === 'active' ? ($LANG['agency_status_active'] ?? 'Active') : ($LANG['agency_status_inactive'] ?? 'Inactive');
$feeLabel = number_format($agencyFee, 2, '.', '') . '%';
$socialLinks = ($agencyData && $agencyProfileSocialEnabled) ? $iN->iN_GetAgencySocialLinks($agencyIdValue) : null;
$boostedCreators = $agencyData ? $iN->iN_GetActiveAgencyBoosts($agencyIdValue) : null;
$viewerIsCreator = ($logedIn == 1 && $iN->iN_CheckUserIsCreator($userID) == 1);
$viewerIsOwner = ($logedIn == 1 && $iN->iN_IsAgencyOwnerForAgency($agencyIdValue, $userID));
$viewerIsMember = ($logedIn == 1 && $iN->iN_IsAgencyMember($agencyIdValue, $userID));
$viewerAgency = ($logedIn == 1) ? $iN->iN_GetActiveAgencyForCreator($userID) : null;
$viewerHasAgency = !empty($viewerAgency);
$requestStatus = '';
if ($logedIn == 1 && $viewerIsCreator && !$viewerIsOwner && !$viewerIsMember) {
    $requestRow = DB::one(
        "SELECT status FROM i_agency_requests WHERE agency_id_fk = ? AND creator_iuid_fk = ? ORDER BY ar_id DESC LIMIT 1",
        [$agencyIdValue, $userID]
    );
    $requestStatus = (string)($requestRow['status'] ?? '');
}
$canRequest = ($logedIn == 1 && $viewerIsCreator && !$viewerIsOwner && !$viewerIsMember && $agencyStatus === 'active' && !$viewerHasAgency && $requestStatus !== 'pending');
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include 'header/meta.php';
        include 'header/css.php';
        include 'header/javascripts.php';
    ?>
</head>
<body>
<?php if ($logedIn == 0) { ?>
    <?php include 'login_form.php'; ?>
<?php } ?>
<?php include 'header/header.php'; ?>
<?php if ($agencyData) { ?>
    <div class="profile_wrapper community_profile_wrapper">
        <input type="hidden" id="creatorAgencyCsrf" value="<?php echo iN_HelpSecure($csrfToken); ?>">
        <div class="i_profile_container">
            <div class="i_profile_cover_blur" data-background="<?php echo iN_HelpSecure($coverUrl); ?>" role="img" aria-label="<?php echo iN_HelpSecure($agencyName); ?>"></div>
            <div class="i_profile_i_container">
                <div class="i_profile_infos_wrapper">
                    <div class="i_profile_cover">
                        <div class="i_im_cover">
                            <img src="<?php echo iN_HelpSecure($coverUrl); ?>" alt="<?php echo iN_HelpSecure($agencyName); ?>">
                        </div>
                        <div class="i_profile_avatar_container">
                            <div class="i_profile_avatar_wrp">
                                <div class="i_profile_avatar" data-avatar="<?php echo iN_HelpSecure($logoUrl); ?>" role="img" aria-label="<?php echo iN_HelpSecure($agencyName); ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="i_u_profile_info">
                        <div class="i_u_name"><?php echo iN_HelpSecure($agencyName); ?></div>
                        <?php if ($ownerName !== '') { ?>
                            <div class="i_u_name_mention">
                                <?php if ($ownerUrl !== '') { ?>
                                    <a href="<?php echo iN_HelpSecure($ownerUrl); ?>">
                                        <?php echo iN_HelpSecure($LANG['agency_owner']); ?>: <?php echo iN_HelpSecure($ownerName); ?>
                                    </a>
                                <?php } else { ?>
                                    <?php echo iN_HelpSecure($LANG['agency_owner']); ?>: <?php echo iN_HelpSecure($ownerName); ?>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <div class="i_p_cards">
                            <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['agency_fee_rate']); ?>: <?php echo iN_HelpSecure($feeLabel); ?></div>
                            <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['agency_status']); ?>: <?php echo iN_HelpSecure($statusLabel); ?></div>
                        </div>
                        <?php if (!empty($socialLinks)) { ?>
                            <div class="i_profile_menu">
                                <div class="i_profile_menu_middle flex_ tabing">
                                    <?php foreach ($socialLinks as $social) {
                                        $socialLink = $social['s_link'] ?? '';
                                        $socialIcon = $social['social_icon'] ?? '';
                                    ?>
                                        <div class="s_m_link flex_ tabing">
                                            <a class="flex_ tabing" href="<?php echo iN_HelpSecure($socialLink, FILTER_VALIDATE_URL); ?>" target="_blank" rel="nofollow noopener">
                                                <?php echo $socialIcon; ?>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($agencyAbout !== '') { ?>
                            <div class="i_p_item_box">
                                <div class="profile_meta_bio">
                                    <div class="profile_meta_bio_title"><?php echo iN_HelpSecure($LANG['agency_about']); ?></div>
                                    <div class="profile_meta_bio_text"><?php echo nl2br(iN_HelpSecure($agencyAbout)); ?></div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($agencyServices !== '') { ?>
                            <div class="i_p_item_box">
                                <div class="profile_meta_bio">
                                    <div class="profile_meta_bio_title"><?php echo iN_HelpSecure($LANG['agency_services']); ?></div>
                                    <div class="profile_meta_bio_text"><?php echo nl2br(iN_HelpSecure($agencyServices)); ?></div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($canRequest) { ?>
                            <div class="i_p_item_box">
                                <button type="button" class="i_btn_become_fun transition creatorAgencyRequest" data-agency="<?php echo iN_HelpSecure($agencyIdValue); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['agency_join_request']); ?>
                                </button>
                            </div>
                        <?php } elseif ($requestStatus === 'pending') { ?>
                            <div class="i_p_item_box">
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_request_pending']); ?></div>
                            </div>
                        <?php } elseif ($viewerHasAgency && !$viewerIsMember && !$viewerIsOwner) { ?>
                            <div class="i_p_item_box">
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_already_member']); ?></div>
                            </div>
                        <?php } elseif ($agencyStatus !== 'active') { ?>
                            <div class="i_p_item_box">
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_inactive']); ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="profile_meta_wrapper">
                <div class="profile_meta_content">
                    <div class="profile_meta_card">
                        <div class="profile_meta_header">
                            <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['agency_boosted_creators']); ?></div>
                        </div>
                        <?php if (!empty($boostedCreators)) { ?>
                            <div class="i_overflow_x_auto">
                                <div class="i_tab_container">
                                    <div class="i_tab_header flex_">
                                        <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['creator']); ?></div>
                                        <div class="tab_item"><?php echo iN_HelpSecure($LANG['clicks']); ?></div>
                                        <div class="tab_item"><?php echo iN_HelpSecure($LANG['ends_at']); ?></div>
                                    </div>
                                    <div class="i_tab_list_item_container">
                                        <?php foreach ($boostedCreators as $boost) {
                                            $creatorId = (int)($boost['creator_iuid_fk'] ?? 0);
                                            $creatorName = $boost['i_user_fullname'] ?? $boost['i_username'] ?? '';
                                            $creatorUsername = $boost['i_username'] ?? '';
                                            $creatorAvatar = $creatorId > 0 ? $iN->iN_UserAvatar($creatorId, $base_url) : ($base_url . 'uploads/avatars/no_gender.png');
                                            $boostId = (int)($boost['ab_id'] ?? 0);
                                            $creatorUrl = $creatorUsername !== '' ? ($base_url . $creatorUsername . '?ab=' . $boostId) : '#';
                                            $clickCount = isset($boost['click_count']) ? (int)$boost['click_count'] : 0;
                                            $endAt = isset($boost['end_at']) ? (int)$boost['end_at'] : 0;
                                            $endLabel = $endAt > 0 ? date('Y-m-d H:i', $endAt) : '-';
                                        ?>
                                            <div class="i_tab_list_item flex_">
                                                <div class="tab_detail_item tab_detail_item_maxwidth">
                                                    <div class="t_od flex_ c6">
                                                        <div class="t_owner_avatar border_two tabing flex_">
                                                            <img src="<?php echo iN_HelpSecure($creatorAvatar); ?>" alt="<?php echo iN_HelpSecure($creatorName); ?>">
                                                        </div>
                                                        <div class="t_owner_user tabing flex_">
                                                            <?php if ($creatorUsername !== '') { ?>
                                                                <a class="truncated" href="<?php echo iN_HelpSecure($creatorUrl); ?>">
                                                                    <?php echo iN_HelpSecure($creatorName); ?>
                                                                </a>
                                                            <?php } else { ?>
                                                                <?php echo iN_HelpSecure($creatorName); ?>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab_detail_item">
                                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($clickCount); ?></div>
                                                </div>
                                                <div class="tab_detail_item">
                                                    <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($endLabel); ?></div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="profile_meta_bio">
                                <div class="profile_meta_bio_text"><?php echo iN_HelpSecure($LANG['agency_no_boosted_creators']); ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="i_not_found_page transition i_centered">
        <div class="noPostIcon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?></div>
        <h1><?php echo iN_HelpSecure($LANG['agency_not_found']); ?></h1>
        <?php echo iN_HelpSecure($LANG['agency_not_found_note']); ?>
    </div>
<?php } ?>
</body>
</html>
