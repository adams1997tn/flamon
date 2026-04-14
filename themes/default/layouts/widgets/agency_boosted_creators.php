<?php
$agencyModuleEnabled = (isset($agencyModuleStatus) ? $agencyModuleStatus : 'yes') === 'yes';
if ($agencyModuleEnabled) {
    $boostedCreators = $iN->iN_GetActiveAgencyBoostedCreators((int)$showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
    if ($boostedCreators) {
?>
    <div class="sp_wrp">
        <div class="suggested_products">
            <div class="i_right_box_header">
                <?php echo iN_HelpSecure($LANG['agency_boosted_creators']); ?>
            </div>
            <div class="i_topinoras_wrapper flex_ tabing suggested_flex_flow boosted-creators-grid">
                <?php
                foreach ($boostedCreators as $boostData) {
                    $userName = $boostData['i_username'] ?? '';
                    $fullName = $boostData['i_user_fullname'] ?? $userName;
                    if ($fullnameorusername === 'no') {
                        $fullName = $userName;
                    }
                    $userId = (int)($boostData['iuid'] ?? 0);
                    $userAvatar = $iN->iN_UserAvatar($userId, $base_url);
                    $userGender = $boostData['user_gender'] ?? '';
                    $userVerified = isset($boostData['user_verified_status']) && $boostData['user_verified_status'] == '1';
                    $boostId = (int)($boostData['ab_id'] ?? 0);
                    $followerCount = isset($boostData['follower_count']) ? (int)$boostData['follower_count'] : 0;
                    $profileUrl = $userName !== '' ? $base_url . $userName : '#';
                    if ($boostId > 0) {
                        $profileUrl .= '?ab=' . $boostId;
                    }
                ?>
                <div class="boosted-creator-card">
                    <a href="<?php echo iN_HelpSecure($profileUrl); ?>" target="_blank" rel="noopener" title="<?php echo iN_HelpSecure($fullName); ?>" class="boosted-creator-link transition">
                        <div class="boosted-avatar-wrapper">
                            <div class="boosted-avatar">
                                <img src="<?php echo iN_HelpSecure($userAvatar, FILTER_VALIDATE_URL); ?>" alt="<?php echo iN_HelpSecure($fullName); ?>">
                            </div>
                            <?php if ($userVerified) { ?>
                                <div class="boosted-verified-badge">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('11')); ?>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="boosted-name">
                            <span class="boosted-name-text"><?php echo iN_HelpSecure($fullName); ?></span>
                        </div>
                        <?php if ($userName !== '') { ?>
                            <div class="boosted-username">@<?php echo iN_HelpSecure($userName); ?></div>
                        <?php } ?>
                    </a>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php
    }
}
?>
