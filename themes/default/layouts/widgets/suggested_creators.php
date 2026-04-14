<?php
if($logedIn != 0){
    $suggestedCreators = $iN->iN_SuggestionCreatorsList($userID, $showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
}else{
    $suggestedCreators = $iN->iN_SuggestionCreatorsListOut($showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
}
if ($suggestedCreators) {
    $suggestedCreatorIds = [];
    foreach ($suggestedCreators as $sgCreatorData) {
        $creatorUID = isset($sgCreatorData['iuid']) ? (int)$sgCreatorData['iuid'] : 0;
        if ($creatorUID > 0) {
            $suggestedCreatorIds[$creatorUID] = $creatorUID;
        }
    }
    if (!empty($suggestedCreatorIds)) {
        $iN->iN_PreloadUserMediaPathMaps(array_values($suggestedCreatorIds));
    }
}
if($suggestedCreators){?>
    <div class="sp_wrp">
        <div class="suggested_products">
            <div class="i_right_box_header">
            <?php echo iN_HelpSecure($LANG['suggested_creators']);?>
            </div>
            <div class="i_topinoras_wrapper flex_ tabing suggested_flex_flow suggested-creators-grid">
                <?php
                    foreach($suggestedCreators as $sgCreatorData){
                        $sgcreatorUserName = $sgCreatorData['i_username'];
                        $sgCreatorUserfullName = $sgCreatorData['i_user_fullname'];
                        $sgUserGender = $sgCreatorData['user_gender'];
                        $sgUserVerifiedStatus = isset($sgCreatorData['user_verified_status']) && $sgCreatorData['user_verified_status'] == '1';
                        $publisherGender = '';
                		if ($sgUserGender == 'male') {
                			$publisherGender = '<span class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</span>';
                		} else if ($sgUserGender == 'female') {
                			$publisherGender = '<span class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</span>';
                		} else if ($sgUserGender == 'couple') {
                			$publisherGender = '<span class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</span>';
                		}
                        if($fullnameorusername == 'no'){
                            $sgCreatorUserfullName = $sgcreatorUserName;
                        }
                        $sgcreatorUserID = $sgCreatorData['iuid'];
                        $sgCreatorUserAvatar = $iN->iN_UserAvatar($sgcreatorUserID, $base_url);
                ?>
                 <div class="suggested-creator-card">
                    <a href="<?php echo $base_url.$sgcreatorUserName;?>" target="_blank" rel="noopener" title="<?php echo iN_HelpSecure($sgCreatorUserfullName);?>" class="suggested-creator-link transition">
                        <div class="suggested-avatar-wrapper">
                            <div class="suggested-avatar">
                                <img src="<?php echo $sgCreatorUserAvatar;?>" alt="<?php echo iN_HelpSecure($sgCreatorUserfullName);?>">
                            </div>
                            <?php if($sgUserVerifiedStatus){?>
                                <div class="suggested-verified-badge">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('11'));?>
                                </div>
                            <?php }?>
                        </div>
                        <div class="suggested-name">
                            <span class="suggested-name-text"><?php echo iN_HelpSecure($sgCreatorUserfullName);?></span><?php echo html_entity_decode($publisherGender); ?>
                        </div>
                    </a>
                 </div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php } else { 
    // Fallback: show popular creators from last week or a helpful empty state
    $popularCreators = $iN->iN_PopularUsersFromLastWeekInExplorePageLanding(3, $userID ?? null, $viewerCountryCode ?? null);
    if ($popularCreators) {
        $popularCreatorIds = [];
        foreach ($popularCreators as $td) {
            $popularUID = isset($td['iuid']) ? (int)$td['iuid'] : 0;
            if ($popularUID > 0) {
                $popularCreatorIds[$popularUID] = $popularUID;
            }
        }
        if (!empty($popularCreatorIds)) {
            $iN->iN_PreloadUserMediaPathMaps(array_values($popularCreatorIds));
        }
    }
    if($popularCreators){ ?>
        <div class="sp_wrp">
            <div class="suggested_products">
                <div class="i_right_box_header">
                    <?php echo iN_HelpSecure($LANG['best_creators_last_week'] ?? 'Top Creators Last Week'); ?>
                </div>
                <div class="i_topinoras_wrapper flex_ tabing suggested_flex_flow suggested-creators-grid">
                    <?php foreach($popularCreators as $td){
                        $pUserName = $td['i_username'] ?? '';
                        $pFullName = ($fullnameorusername === 'no') ? $pUserName : ($td['i_user_fullname'] ?? $pUserName);
                        $pUID = $td['iuid'] ?? 0;
                        $pAvatar = $iN->iN_UserAvatar($pUID, $base_url);
                        $pGender = '';
                        if (($td['user_gender'] ?? null) === 'male') {
                            $pGender = '<span class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</span>';
                        } elseif (($td['user_gender'] ?? null) === 'female') {
                            $pGender = '<span class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</span>';
                        } elseif (($td['user_gender'] ?? null) === 'couple') {
                            $pGender = '<span class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</span>';
                        }
                        $pVerified = isset($td['user_verified_status']) && $td['user_verified_status'] == '1';
                    ?>
                        <div class="suggested-creator-card">
                            <a href="<?php echo $base_url.$pUserName;?>" target="_blank" rel="noopener" title="<?php echo iN_HelpSecure($pFullName);?>" class="suggested-creator-link transition">
                                <div class="suggested-avatar-wrapper">
                                    <div class="suggested-avatar">
                                        <img src="<?php echo iN_HelpSecure($pAvatar);?>" alt="<?php echo iN_HelpSecure($pFullName);?>">
                                    </div>
                                    <?php if($pVerified){ ?>
                                        <div class="suggested-verified-badge">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('11'));?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="suggested-name">
                                    <span class="suggested-name-text"><?php echo iN_HelpSecure($pFullName);?></span><?php echo html_entity_decode($pGender);?>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="sp_wrp">
            <?php include __DIR__ . '/becomeCreator.php'; ?>
        </div>
    <?php }
}?>
