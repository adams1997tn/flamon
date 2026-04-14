<?php
if($logedIn != 0){
    $suggestedCreators = $iN->iN_SuggestionCreatorsList($userID, $showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
}else{
    $suggestedCreators = $iN->iN_SuggestionCreatorsListOut($showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
}
if($suggestedCreators){
    $suggestedCreatorIds = [];
    foreach ($suggestedCreators as $creatorRow) {
        $creatorUID = isset($creatorRow['iuid']) ? (int)$creatorRow['iuid'] : 0;
        if ($creatorUID > 0) {
            $suggestedCreatorIds[$creatorUID] = $creatorUID;
        }
    }
    $suggestedCreatorIds = array_values($suggestedCreatorIds);
    if (!empty($suggestedCreatorIds)) {
        $iN->iN_PreloadUserMediaPathMaps($suggestedCreatorIds);
    }
    $suggestedTotalPostsMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalImageMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalImagePostsMap($suggestedCreatorIds) : [];
    $suggestedTotalVideoMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalVideoPostsMap($suggestedCreatorIds) : [];
?>
<div class="i_postFormContainer_swiper">
    <div class="i_right_box_header">
    <?php echo iN_HelpSecure($LANG['suggested_creators']);?>
    </div>
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
        <?php
            foreach($suggestedCreators as $sgCreatorData){
                $sgcreatorUserName = $sgCreatorData['i_username'] ?? null;
                $sgCreatorUserfullName = $sgCreatorData['i_user_fullname'] ?? null;
                $sgcreatorUserID = $sgCreatorData['iuid'] ?? null;
                $sgCreatorUserAvatar = $iN->iN_UserAvatar($sgcreatorUserID, $base_url);
                $sgCreatorUserCover = $iN->iN_UserCover($sgcreatorUserID, $base_url);
                $sgCreatorIntID = (int)$sgcreatorUserID;
                $sgtotalPost = isset($suggestedTotalPostsMap[$sgCreatorIntID]) ? (int)$suggestedTotalPostsMap[$sgCreatorIntID] : (int)$iN->iN_TotalPosts($sgcreatorUserID);
                $sgtotalImagePost = isset($suggestedTotalImageMap[$sgCreatorIntID]) ? (int)$suggestedTotalImageMap[$sgCreatorIntID] : (int)$iN->iN_TotalImagePosts($sgcreatorUserID);
                $sgtotalVideoPosts = isset($suggestedTotalVideoMap[$sgCreatorIntID]) ? (int)$suggestedTotalVideoMap[$sgCreatorIntID] : (int)$iN->iN_TotalVideoPosts($sgcreatorUserID);
            ?>
            <!--ITEM-->
            <div class="swiper-slide">
            <div class="i_sub_u_cov" data-bg="<?php echo iN_HelpSecure($sgCreatorUserCover); ?>"></div>
                <div class="i_sub_u_det">
                    <div class="i_sub_u_det_container">
                            <!---->
                            <div class="i_sub_u_ava">
                                <div class="i_post_user_avatar">
                                    <img src="<?php echo $sgCreatorUserAvatar;?>" alt="<?php echo $sgCreatorUserfullName;?>">
                                </div>
                            </div>
                            <!---->
                            <div class="i_sub_u_d">
                                <div class="i_sub_u_name"><a href="<?php echo $base_url.$sgcreatorUserName;?>" target="_blank" title="<?php echo $sgCreatorUserfullName;?>"><?php echo $sgCreatorUserfullName;?></a></div>
                                <div class="i_sub_u_men"><a href="<?php echo $base_url.$sgcreatorUserName;?>" target="_blank" title="<?php echo $sgCreatorUserfullName;?>">@<?php echo $sgcreatorUserName;?></a></div>
                                <!---->
                                <div class="i_p_items_box_">
                                    <div class="i_btn_item_box transition">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('67')); ?> <?php echo iN_HelpSecure($sgtotalPost); ?>
                                    </div>
                                    <div class="i_btn_item_box transition">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')); ?> <?php echo iN_HelpSecure($sgtotalImagePost); ?>
                                    </div>
                                    <div class="i_btn_item_box transition">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('52')); ?> <?php echo iN_HelpSecure($sgtotalVideoPosts); ?>
                                    </div>
                                </div>
                                <!---->
                            </div>
                    </div>
                </div>
            </div>
            <!--/ITEM-->
            <?php }?>
        </div>
        </div>
        <div class="horizontal_arrow"><?php echo $iN->iN_SelectedMenuIcon('141');?></div>
    </div>
<?php }?>
