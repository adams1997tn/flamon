<div class="creators_container">
  <div class="creator_pate_title">
      <?php
        if(isset($PROFILE_CATEGORIES[$iN->iN_Secure($pageCreator)])){
            echo iN_HelpSecure($PROFILE_CATEGORIES[$iN->iN_Secure($pageCreator)]);
        }else if(isset($PROFILE_SUBCATEGORIES[$iN->iN_Secure($pageCreator)])){
            $catOwn = $iN->iN_GetCategoryFromSubCategory($pageCreator);
            echo iN_HelpSecure($PROFILE_CATEGORIES[$iN->iN_Secure($catOwn)]).' -> '.iN_HelpSecure($PROFILE_SUBCATEGORIES[$iN->iN_Secure($pageCreator)]);
        }
      ?>
  </div>

  <div class="creators_list_container" id="moreType" data-type="creators" data-r="<?php echo html_entity_decode($iN->iN_Secure($pageCreator));?>">
        <?php
            $lastPostID = isset($_POST['last']) ? $_POST['last'] : '';
            $creatorTypeUrl = $iN->iN_GetCreatorFromUrl($iN->iN_Secure($pageCreator), $lastPostID, $scrollLimit, $userID ?? null, $viewerCountryCode ?? null);
            $creatorTypeUserIds = [];
            if ($creatorTypeUrl) {
                foreach ($creatorTypeUrl as $creatorTypeRow) {
                    $creatorTypeUID = isset($creatorTypeRow['iuid']) ? (int)$creatorTypeRow['iuid'] : 0;
                    if ($creatorTypeUID > 0) {
                        $creatorTypeUserIds[$creatorTypeUID] = $creatorTypeUID;
                    }
                }
            }
            $creatorTypeUserIds = array_values($creatorTypeUserIds);
            if (!empty($creatorTypeUserIds)) {
                $iN->iN_PreloadUserMediaPathMaps($creatorTypeUserIds);
            }
            $creatorTypeTotalPostMap = !empty($creatorTypeUserIds) ? $iN->iN_TotalPostsMap($creatorTypeUserIds) : [];
            $creatorTypeTotalImageMap = !empty($creatorTypeUserIds) ? $iN->iN_TotalImagePostsMap($creatorTypeUserIds) : [];
            $creatorTypeTotalVideoMap = !empty($creatorTypeUserIds) ? $iN->iN_TotalVideoPostsMap($creatorTypeUserIds) : [];
            $creatorTypeTotalReelsMap = !empty($creatorTypeUserIds) ? $iN->iN_TotalReelsPostsMap($creatorTypeUserIds) : [];
            $creatorTypeTotalAudioMap = !empty($creatorTypeUserIds) ? $iN->iN_TotalAudioPostsMap($creatorTypeUserIds) : [];
            $creatorTypeTotalProductMap = !empty($creatorTypeUserIds) ? $iN->iN_TotalProductByUserMap($creatorTypeUserIds) : [];
            if($creatorTypeUrl){
                foreach($creatorTypeUrl as $td){
                    $popularuserID = $td['iuid'];
                    $popularUserIntID = (int)$popularuserID;
                    $popularUserAvatar = $iN->iN_UserAvatar($popularuserID, $base_url);
                    $creatorCover = $iN->iN_UserCover($popularuserID, $base_url);
                    $popularUserName = $td['i_username'];
                    $popularUserFullName = $td['i_user_fullname'];
                    $userProfileFrame = isset($td['user_frame']) ? $td['user_frame'] : NULL;
                    if($fullnameorusername == 'no'){
                        $popularUserFullName = $popularUserName;
                    }
                    $uPCategory = isset($td['profile_category']) ? $td['profile_category'] : NULL;
                    $totalPost = isset($creatorTypeTotalPostMap[$popularUserIntID]) ? (int)$creatorTypeTotalPostMap[$popularUserIntID] : (int)$iN->iN_TotalPosts($popularuserID);
                    $totalImagePost = isset($creatorTypeTotalImageMap[$popularUserIntID]) ? (int)$creatorTypeTotalImageMap[$popularUserIntID] : (int)$iN->iN_TotalImagePosts($popularuserID);
                    $totalVideoPosts = isset($creatorTypeTotalVideoMap[$popularUserIntID]) ? (int)$creatorTypeTotalVideoMap[$popularUserIntID] : (int)$iN->iN_TotalVideoPosts($popularuserID);
                    $totalReelsPosts = isset($creatorTypeTotalReelsMap[$popularUserIntID]) ? (int)$creatorTypeTotalReelsMap[$popularUserIntID] : (int)$iN->iN_TotalReelsPosts($popularuserID);
                    $totalAudioPosts = isset($creatorTypeTotalAudioMap[$popularUserIntID]) ? (int)$creatorTypeTotalAudioMap[$popularUserIntID] : (int)$iN->iN_TotalAudioPosts($popularuserID);
                    $totalProducts = isset($creatorTypeTotalProductMap[$popularUserIntID]) ? (int)$creatorTypeTotalProductMap[$popularUserIntID] : (int)$iN->iN_TotalProductByUser($popularuserID);
                    $pCt = NULL;
                    if(isset($PROFILE_CATEGORIES[$uPCategory])){
                        $pCt = isset($PROFILE_CATEGORIES[$uPCategory]) ? $PROFILE_CATEGORIES[$uPCategory] : NULL;
                    }else if(isset($PROFILE_SUBCATEGORIES[$uPCategory])){
                        $pCt = isset($PROFILE_SUBCATEGORIES[$uPCategory]) ? $PROFILE_SUBCATEGORIES[$uPCategory] : NULL;
                    }
                    $categoryBadge = '';
                    if ($uPCategory && isset($pCt)) {
                        $categoryLabel = iN_HelpSecure($pCt);
                        $categoryHref = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($uPCategory);
                        $categoryBadge = '<a class="creator_category_badge" href="' . $categoryHref . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('65')) . $categoryLabel . '</a>';
                    }
                    $profileUrl = iN_HelpSecure($base_url . $popularUserName);
        ?>
        <div class="creator_list_box_wrp mor body_<?php echo iN_HelpSecure($popularuserID);?>" data-last="<?php echo iN_HelpSecure($popularuserID);?>">
            <div class="creator_l_box transition flex_ creator_card">
                <div class="creator_l_cover creator_card_cover" style="background-image:url(<?php echo iN_HelpSecure($creatorCover);?>);">
                    <div class="creator_cover_layer">
                        <span class="creator_card_tag"><?php echo iN_HelpSecure($pCt ?? $LANG['our_creators']);?></span>
                    </div>
                </div>
                <div class="creator_l_avatar_name">
                <div class="creator_identity_row">
                        <div class="creator_avatar_container">
                            <?php if($userProfileFrame){ ?>
                                <div class="frame_out_container_creator"><div class="frame_container_creator"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
                            <?php }?>
                            <a href="<?php echo $profileUrl;?>"><div class="creator_avatar"><img src="<?php echo iN_HelpSecure($popularUserAvatar);?>"></div></a>
                        </div>
                        <div class="creator_identity">
                            <div class="creator_nm transition truncated"><a href="<?php echo $profileUrl;?>"><?php echo iN_HelpSecure($iN->iN_Secure($popularUserFullName));?></a></div>
                            <div class="creator_handle">@<?php echo iN_HelpSecure($popularUserName);?></div>
                        </div>
                    </div>
                    <?php if ($categoryBadge) { ?>
                        <div class="creator_category_row"><?php echo $categoryBadge; ?></div>
                    <?php } ?>
                    <div class="i_p_items_box_ creator_stats">
                        <?php
                            $statItems = array(
                                array('icon' => '67', 'count' => $totalPost, 'url' => $profileUrl),
                                array('icon' => '68', 'count' => $totalImagePost, 'url' => $base_url . $popularUserName . '?pcat=photos'),
                                array('icon' => '52', 'count' => $totalVideoPosts, 'url' => $base_url . $popularUserName . '?pcat=videos'),
                                array('icon' => '187', 'count' => $totalReelsPosts, 'url' => $base_url . $popularUserName . '?pcat=reels'),
                                array('icon' => '152', 'count' => $totalAudioPosts, 'url' => $base_url . $popularUserName . '?pcat=audios'),
                                array('icon' => '158', 'count' => $totalProducts, 'url' => $base_url . $popularUserName . '?pcat=products'),
                            );
                            $visibleStats = array_slice($statItems, 0, 3);
                            $moreStats = array_slice($statItems, 3);
                        ?>
                        <?php foreach ($visibleStats as $st) { ?>
                            <a href="<?php echo iN_HelpSecure($st['url']);?>" class="i_btn_item_box transition">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon($st['icon']));?> <?php echo iN_HelpSecure($st['count']);?>
                            </a>
                        <?php } ?>
                        <?php if (!empty($moreStats)) { ?>
                            <div class="i_btn_item_box transition creator_stats_more">
                                <button type="button" class="creator_stats_more_trigger" aria-haspopup="true" aria-expanded="false">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16'));?>
                                </button>
                                <div class="creator_stats_dropdown">
                                    <?php foreach ($moreStats as $st) { ?>
                                        <a href="<?php echo iN_HelpSecure($st['url']);?>" class="creator_stats_dropdown_item">
                                            <span class="creator_stats_dropdown_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon($st['icon']));?></span>
                                            <span class="creator_stats_dropdown_count"><?php echo iN_HelpSecure($st['count']);?></span>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="creator_gallery">
                        <div class="creator_last_two_post flex_ tabing">
                            <?php
                               $getLatestFivePost = $iN->iN_ExploreUserLatestFivePost($popularuserID);
                               $placeholder = $base_url . 'img/no_image.png';
                               $renderedPosts = 0;
                               if($getLatestFivePost){
                                   foreach($getLatestFivePost as $suggestedData){
                                    if ($renderedPosts >= 4) { break; }
                                    $postType = isset($suggestedData['post_type']) ? $suggestedData['post_type'] : 'normal';
                                    $userPostID = $suggestedData['post_id'];
                                    $userPostFile = $suggestedData['post_file'] ?? '';
                                    $slugData = isset($suggestedData['url_slug']) ? $suggestedData['url_slug'] : NULL;
                                    $slugUrl = $base_url.'post/'.$slugData.'_'.$userPostID;
                                    $userPostWhoCanSee = isset($suggestedData['who_can_see']) ? $suggestedData['who_can_see'] : '1';
                                    $getFriendStatusBetweenTwoUser = $logedIn == '1' ? $iN->iN_GetRelationsipBetweenTwoUsers($userID, $popularuserID) : '';
                                    $purchased = ($userPostWhoCanSee === '4' && $logedIn == '1') ? $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID) : false;
                                    $hasAccess = ($userPostWhoCanSee === '1');
                                    if ($logedIn == '1') {
                                        if ($userPostWhoCanSee === '2') {
                                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'flwr', 'subscriber'], true);
                                        } elseif ($userPostWhoCanSee === '3') {
                                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'subscriber'], true);
                                        } elseif ($userPostWhoCanSee === '4') {
                                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'subscriber'], true) || $purchased;
                                        }
                                    }
                                    $trimValue = rtrim((string)$userPostFile,',');
                                    $explodeFiles = array_values(array_filter(array_unique(explode(',', $trimValue))));
                                    $fileUploadID = $explodeFiles[0] ?? null;
                                    $filePathUrl = $placeholder;
                                    $onlySubs = '';
                                    $displayPath = '';
                                    $fileExtension = '';
                                    $hasMedia = false;
                                    if($fileUploadID){
                                        $fileData = $iN->iN_GetUploadedFileDetails($fileUploadID);
                                        if($fileData){
                                            $fileExtension = strtolower($fileData['uploaded_file_ext'] ?? '');
                                            $filePath = $fileData['uploaded_file_path'] ?? '';
                                            $filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? '';
                                            $fileLocked = $fileData['uploaded_x_file_path'] ?? '';
                                            $allowedImages = array('jpg','jpeg','png','gif','webp','bmp');
                                            $allowedVideos = array('mp4','mkv','webm','mov');
                                            if($fileExtension && in_array($fileExtension, array_merge($allowedImages, $allowedVideos), true)){
                                                $displayPath = $filePath ?: $filePathTumbnail;
                                                if(!$hasAccess){
                                                    $pixelPath = $fileLocked;
                                                    if(!$pixelPath && $filePath){
                                                        $pixelPath = preg_replace('~/uploads/(?!pixel/)~', '/uploads/pixel/', $filePath, 1);
                                                    }
                                                    $displayPath = $pixelPath ?: ($filePathTumbnail ?: $filePath);
                                                }else{
                                                    if(in_array($fileExtension, $allowedVideos, true) && $filePathTumbnail){
                                                        $displayPath = $filePathTumbnail;
                                                    }
                                                }
                                                $hasMedia = !empty($displayPath);
                                            }
                                        }
                                    }
                                    if(!$displayPath){
                                        $displayPath = $placeholder;
                                    }
                                    if(function_exists('storage_public_url')){
                                        $filePathUrl = storage_public_url($displayPath);
                                    }else{
                                        $filePathUrl = $base_url . $displayPath;
                                    }
                                    if(!$filePathUrl){
                                        $filePathUrl = $placeholder;
                                    }
                                    if($userPostWhoCanSee == '1'){
                                        $onlySubs = '';
                                    }else if($userPostWhoCanSee == '2'){
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div></div>';
                                    }else if($userPostWhoCanSee == '3'){
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div></div>';
                                    }else if($userPostWhoCanSee == '4'){
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('40').'</div></div></div>';
                                    }
                                    $tileClasses = 'creator_last_post_item-box';
                                    $textualLabel = 'Text';
                                    if ($postType === 'poll') { $textualLabel = $LANG['poll'] ?? 'Poll'; }
                                    if ($postType === 'campaign') { $textualLabel = $LANG['campaign'] ?? 'Campaign'; }
                                    $textSnippet = trim(strip_tags($suggestedData['post_text'] ?? ''));
                                    if (strlen($textSnippet) > 90) {
                                        $textSnippet = mb_substr($textSnippet, 0, 90) . '…';
                                    }
                                    $isTextualPreview = !$hasMedia || $postType === 'poll' || $postType === 'campaign' || $postType === 'text';
                                    $shouldHideContent = ($userPostWhoCanSee !== '1' && !$hasAccess);
                                    $isLockedText = $isTextualPreview && $shouldHideContent;
                                    if ($isTextualPreview) {
                                        $tileClasses .= ' creator_last_post_item-box--text';
                                    }
                            ?>
                                    <div class="creator_last_post_item">
                                    <div class="<?php echo iN_HelpSecure($tileClasses); ?>">
                                    <a href="<?php echo iN_HelpSecure($slugUrl);?>">
                                        <?php
                                          if($shouldHideContent){
                                            echo html_entity_decode($onlySubs);
                                          }
                                          if ($isTextualPreview) { ?>
                                            <?php if ($isLockedText) { /* overlay already printed */ } else { ?>
                                                <div class="creator_text_tile">
                                                    <div class="creator_text_tile_badge"><?php echo iN_HelpSecure($textualLabel); ?></div>
                                                    <?php if (!empty($textSnippet)) { ?>
                                                        <div class="creator_text_tile_snippet"><?php echo iN_HelpSecure($textSnippet); ?></div>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                          <?php } else { ?>
                                            <img class="creator_last_post_item-img" src="<?php echo iN_HelpSecure($filePathUrl);?>" onerror="this.onerror=null;this.src='<?php echo iN_HelpSecure($placeholder);?>';" loading="lazy" alt="">
                                          <?php } ?>
                                    </a>
                                    </div>
                                </div>
                            <?php
                                $renderedPosts++;
                                   }
                                }else{
                                    echo '<div class="no_content tabing flex_">'.$LANG['no_posts_yet'].'</div>';
                                } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!---->
    <?php  } }else{
        echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">'.$iN->iN_SelectedMenuIcon('54').'</div><div class="n_c_t">'.$LANG['not_creator_in_this_caregory'].'</div></div>';
    } ?>
    </div>
</div>
