<?php
if($logedIn != 0){
    $suggestedCreators = $iN->iN_SuggestionCreatorsList($userID, $numberShow, $userID ?? null, $viewerCountryCode ?? null);
}else{
    $suggestedCreators = $iN->iN_SuggestionCreatorsListOut($numberShow, $userID ?? null, $viewerCountryCode ?? null);
}
    $suggestedCreatorIds = [];
    if ($suggestedCreators) {
        foreach ($suggestedCreators as $sgCreatorData) {
            $sgCreatorID = isset($sgCreatorData['iuid']) ? (int)$sgCreatorData['iuid'] : 0;
            if ($sgCreatorID > 0) {
                $suggestedCreatorIds[$sgCreatorID] = $sgCreatorID;
            }
        }
    }
    $suggestedCreatorIds = array_values($suggestedCreatorIds);
    if (!empty($suggestedCreatorIds)) {
        $iN->iN_PreloadUserMediaPathMaps($suggestedCreatorIds);
    }
    $suggestedTotalPostMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalImageMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalImagePostsMap($suggestedCreatorIds) : [];
    $suggestedTotalVideoMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalVideoPostsMap($suggestedCreatorIds) : [];
    if($suggestedCreators){?>
    <div class="sp_wrp">
    <div class="suggested_products">
    <div class="i_right_box_header">
    <?php echo iN_HelpSecure($LANG['suggested_creators']);?>
    </div>
    <div class="i_topinoras_wrapper flex_ tabing suggested_flex_flow">
    <?php
    foreach($suggestedCreators as $sgCreatorData){
        $sgcreatorUserName = $sgCreatorData['i_username'];
        $sgCreatorUserfullName = $sgCreatorData['i_user_fullname'];
        if($fullnameorusername == 'no'){
            $sgCreatorUserfullName = $sgcreatorUserName;
        }
        $sgcreatorUserID = $sgCreatorData['iuid'];
        $sgcreatorIntID = (int)$sgcreatorUserID;
        $sgCreatorUserAvatar = $iN->iN_UserAvatar($sgcreatorUserID, $base_url);
        $sgCreatorUserCover = $iN->iN_UserCover($sgcreatorUserID, $base_url);
        $sgtotalPost = isset($suggestedTotalPostMap[$sgcreatorIntID]) ? (int)$suggestedTotalPostMap[$sgcreatorIntID] : (int)$iN->iN_TotalPosts($sgcreatorUserID);
        $sgtotalImagePost = isset($suggestedTotalImageMap[$sgcreatorIntID]) ? (int)$suggestedTotalImageMap[$sgcreatorIntID] : (int)$iN->iN_TotalImagePosts($sgcreatorUserID);
        $sgtotalVideoPosts = isset($suggestedTotalVideoMap[$sgcreatorIntID]) ? (int)$suggestedTotalVideoMap[$sgcreatorIntID] : (int)$iN->iN_TotalVideoPosts($sgcreatorUserID);
    ?>
    <div class="sugest">
    <div class="i_sug_cont">
       <div class="i_sub_u_cov" style="background-image:url(<?php echo $sgCreatorUserCover;?>);"></div>
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
                    <div class="i_sub_u_name"><a href="<?php echo $base_url.$sgcreatorUserName;?>" target="blank_" title="<?php echo $sgCreatorUserfullName;?>"><?php echo $sgCreatorUserfullName;?></a></div>
                    <div class="i_sub_u_men"><a href="<?php echo $base_url.$sgcreatorUserName;?>" target="blank_" title="<?php echo $sgCreatorUserfullName;?>">@<?php echo $sgcreatorUserName;?></a></div>
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
    </div>

    <?php } ?>
    </div></div></div>
<?php  }
?>
