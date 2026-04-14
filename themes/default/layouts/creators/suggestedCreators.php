<?php
// Suggested/Popular creators rendered with the same layout as featuredCreators.php
// Aim: keep page populated in a visually consistent way

// 1) Try suggested creators (depends on login)
if ($logedIn != 0) {
    $list = $iN->iN_SuggestionCreatorsList($userID, $showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
} else {
    $list = $iN->iN_SuggestionCreatorsListOut($showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
}

// 2) If empty, fallback to popular last week (different source)
if (!$list) {
    $list = $iN->iN_PopularUsersFromLastWeekInExplorePage($userID ?? null, $viewerCountryCode ?? null);
}

    $suggestedCreatorIds = [];
    if ($list) {
        foreach ($list as $listRow) {
            $listUID = isset($listRow['iuid']) ? (int)$listRow['iuid'] : (isset($listRow['post_owner_id']) ? (int)$listRow['post_owner_id'] : 0);
            if ($listUID > 0) {
                $suggestedCreatorIds[$listUID] = $listUID;
            }
        }
    }
    $suggestedCreatorIds = array_values($suggestedCreatorIds);
    if (!empty($suggestedCreatorIds)) {
        $iN->iN_PreloadUserMediaPathMaps($suggestedCreatorIds);
    }
    $suggestedProfileMap = !empty($suggestedCreatorIds) ? $iN->iN_GetUsersBasicProfileMap($suggestedCreatorIds) : [];
    $suggestedTotalPostMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalImageMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalImagePostsMap($suggestedCreatorIds) : [];
    $suggestedTotalVideoMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalVideoPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalReelsMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalReelsPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalAudioMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalAudioPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalProductMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalProductByUserMap($suggestedCreatorIds) : [];

if ($list) { ?>
<div class="creators_container">
  <div class="creator_pate_title"><?php echo iN_HelpSecure($LANG['suggested_creators'] ?? ($LANG['best_creators_of_last_week'] ?? 'Creators'));?></div>

  <div class="creators_list_container">
    <?php foreach ($list as $row) {
        // Normalize dataset to match featuredCreators.php expectations
        $uid = $row['iuid'] ?? ($row['post_owner_id'] ?? 0);
        if (!$uid) { continue; }
        $uidInt = (int)$uid;
        $profileRow = isset($suggestedProfileMap[$uidInt]) ? $suggestedProfileMap[$uidInt] : null;
        $avatar = $iN->iN_UserAvatar($uid, $base_url);
        $cover = $iN->iN_UserCover($uid, $base_url);
        $userName = $row['i_username'] ?? '';
        if ($userName === '') { continue; }
        $fullName = $row['i_user_fullname'] ?? $userName;
        if ($fullnameorusername == 'no') { $fullName = $userName; }
        $userProfileFrame = $row['user_frame'] ?? null;
        $uPCategory = $row['profile_category'] ?? ($profileRow['profile_category'] ?? null);
        $totalPost = isset($suggestedTotalPostMap[$uidInt]) ? (int)$suggestedTotalPostMap[$uidInt] : (int)$iN->iN_TotalPosts($uid);
        $totalImagePost = isset($suggestedTotalImageMap[$uidInt]) ? (int)$suggestedTotalImageMap[$uidInt] : (int)$iN->iN_TotalImagePosts($uid);
        $totalVideoPosts = isset($suggestedTotalVideoMap[$uidInt]) ? (int)$suggestedTotalVideoMap[$uidInt] : (int)$iN->iN_TotalVideoPosts($uid);
        $totalReelsPosts = isset($suggestedTotalReelsMap[$uidInt]) ? (int)$suggestedTotalReelsMap[$uidInt] : (int)$iN->iN_TotalReelsPosts($uid);
        $totalAudioPosts = isset($suggestedTotalAudioMap[$uidInt]) ? (int)$suggestedTotalAudioMap[$uidInt] : (int)$iN->iN_TotalAudioPosts($uid);
        $totalProducts = isset($suggestedTotalProductMap[$uidInt]) ? (int)$suggestedTotalProductMap[$uidInt] : (int)$iN->iN_TotalProductByUser($uid);
        $pCt = null;
        if (isset($PROFILE_CATEGORIES[$uPCategory])) {
            $pCt = $PROFILE_CATEGORIES[$uPCategory];
        } elseif (isset($PROFILE_SUBCATEGORIES[$uPCategory])) {
            $pCt = $PROFILE_SUBCATEGORIES[$uPCategory];
        } else { $pCt = null; }
        $categoryBadge = '';
        if ($uPCategory && $pCt) {
            $categoryLabel = iN_HelpSecure($pCt);
            $categoryHref = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($uPCategory);
            $categoryBadge = '<a class="creator_category_badge" href="' . $categoryHref . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('65')) . $categoryLabel . '</a>';
        }
        $profileUrl = iN_HelpSecure($base_url . $userName);
    ?>
    <div class="creator_list_box_wrp">
        <div class="creator_l_box flex_ creator_card">
        <div class="creator_l_cover creator_card_cover" style="background-image:url(<?php echo iN_HelpSecure($cover);?>);">
            <div class="creator_cover_layer">
                <span class="creator_card_tag"><?php echo iN_HelpSecure($LANG['suggested_creators']);?></span>
            </div>
        </div>
        <div class="creator_l_avatar_name">
          <div class="creator_identity_row">
            <div class="creator_avatar_container">
              <?php if($userProfileFrame){ ?>
                <div class="frame_out_container_creator"><div class="frame_container_creator"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
              <?php }?>
              <div class="creator_avatar"><img src="<?php echo iN_HelpSecure($avatar);?>"></div>
            </div>
            <div class="creator_identity">
              <div class="creator_nm truncated"><a href="<?php echo $profileUrl;?>"><?php echo iN_HelpSecure($iN->iN_Secure($fullName));?></a></div>
              <div class="creator_handle">@<?php echo iN_HelpSecure($userName);?></div>
            </div>
          </div>
          <?php if ($categoryBadge) { ?>
            <div class="creator_category_row"><?php echo $categoryBadge; ?></div>
          <?php } ?>
          <?php
            $statItems = array(
              array('icon' => '67', 'count' => $totalPost, 'url' => $profileUrl),
              array('icon' => '68', 'count' => $totalImagePost, 'url' => $base_url . $userName . '?pcat=photos'),
              array('icon' => '52', 'count' => $totalVideoPosts, 'url' => $base_url . $userName . '?pcat=videos'),
              array('icon' => '187', 'count' => $totalReelsPosts, 'url' => $base_url . $userName . '?pcat=reels'),
              array('icon' => '152', 'count' => $totalAudioPosts, 'url' => $base_url . $userName . '?pcat=audios'),
              array('icon' => '158', 'count' => $totalProducts, 'url' => $base_url . $userName . '?pcat=products'),
            );
            $visibleStats = array_slice($statItems, 0, 3);
            $moreStats = array_slice($statItems, 3);
          ?>
          <div class="i_p_items_box_ creator_stats">
            <?php foreach ($visibleStats as $st) { ?>
              <a href="<?php echo iN_HelpSecure($st['url']);?>" class="i_btn_item_box transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon($st['icon']));?> <?php echo iN_HelpSecure($st['count']);?></a>
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
                $latestPosts = $iN->iN_ExploreUserLatestFivePost($uid);
                $renderedPosts = 0;
                $placeholder = $base_url . 'img/no_image.png';
                if ($latestPosts) {
                  foreach ($latestPosts as $p) {
                    if ($renderedPosts >= 4) { break; }
                    $userPostID = isset($p['post_id']) ? (int)$p['post_id'] : 0;
                    $userPostFile = $p['post_file'] ?? '';
                    $slugUrl = $base_url.'post/'.($p['url_slug'] ?? $userPostID).'_'.$userPostID;
                    $whoCanSee = isset($p['who_can_see']) ? (string)$p['who_can_see'] : '1';
                    $postType = isset($p['post_type']) ? $p['post_type'] : 'normal';

                    $getFriendStatusBetweenTwoUser = ($logedIn == '1') ? $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uid) : '';
                    $purchased = ($whoCanSee === '4' && $logedIn == '1') ? $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID) : false;
                    $hasAccess = ($whoCanSee === '1');
                    if ($logedIn == '1') {
                        if ($whoCanSee === '2') {
                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'flwr', 'subscriber'], true);
                        } elseif ($whoCanSee === '3') {
                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'subscriber'], true);
                        } elseif ($whoCanSee === '4') {
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

                    if ($fileUploadID) {
                        $gUFID = $iN->iN_GetUploadedFileDetails($fileUploadID);
                        if ($gUFID) {
                            $fileExtension = strtolower($gUFID['uploaded_file_ext'] ?? '');
                            $filePath = $gUFID['uploaded_file_path'] ?? '';
                            $filePathTumbnail = $gUFID['upload_tumbnail_file_path'] ?? '';
                            $lockedPath = $gUFID['uploaded_x_file_path'] ?? '';
                            $allowedImages = array('jpg','jpeg','png','gif','webp','bmp');
                            $allowedVideos = array('mp4','mkv','webm','mov');
                            if ($fileExtension && !in_array($fileExtension, array_merge($allowedImages, $allowedVideos), true)) {
                                $fileExtension = '';
                            } else {
                                $displayPath = $filePath ?: $filePathTumbnail;
                                if (!$hasAccess) {
                                    $pixelPath = $lockedPath;
                                    if (!$pixelPath && $filePath) {
                                        $pixelPath = preg_replace('~/uploads/(?!pixel/)~', '/uploads/pixel/', $filePath, 1);
                                    }
                                    $displayPath = $pixelPath ?: ($filePathTumbnail ?: $filePath);
                                } elseif (in_array($fileExtension, $allowedVideos, true) && $filePathTumbnail) {
                                    $displayPath = $filePathTumbnail;
                                }
                                $hasMedia = !empty($displayPath) && !empty($fileExtension);
                            }
                        }
                    } else {
                        // No media: keep placeholder and render textual preview
                        $displayPath = $placeholder;
                    }

                    if (!$displayPath) {
                        $displayPath = $placeholder;
                    }

                    if (function_exists('storage_public_url')) {
                        $filePathUrl = storage_public_url($displayPath);
                    } else {
                        $filePathUrl = $base_url . $displayPath;
                    }
                    if (empty($filePathUrl)) {
                        $filePathUrl = $placeholder;
                    }

                    $tileClasses = 'creator_last_post_item-box';
                    $textualLabel = 'Text';
                    if ($postType === 'poll') { $textualLabel = $LANG['poll'] ?? 'Poll'; }
                    if ($postType === 'campaign') { $textualLabel = $LANG['campaign'] ?? 'Campaign'; }
                    $textSnippet = trim(strip_tags($p['post_text'] ?? ''));
                    if (strlen($textSnippet) > 90) {
                        $textSnippet = mb_substr($textSnippet, 0, 90) . '…';
                    }
                    $isTextualPreview = !$hasMedia || $postType === 'poll' || $postType === 'campaign' || $postType === 'text';
                    $shouldHideContent = ($whoCanSee !== '1' && !$hasAccess);
                    $isLockedText = $isTextualPreview && $shouldHideContent;
                    if ($isTextualPreview) {
                        $tileClasses .= ' creator_last_post_item-box--text';
                    }

                    if ($whoCanSee === '2') {
                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div></div>';
                    } else if ($whoCanSee === '3') {
                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div></div>';
                    } else if ($whoCanSee === '4') {
                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('40').'</div></div></div>';
                    }
            ?>
                <div class="creator_last_post_item">
                  <div class="<?php echo iN_HelpSecure($tileClasses); ?>">
                    <a href="<?php echo iN_HelpSecure($slugUrl);?>">
                      <?php
                        if ($shouldHideContent) {
                            echo html_entity_decode($onlySubs);
                        }
                        if ($isTextualPreview) { ?>
                            <?php if ($isLockedText) { /* overlay already printed above */ } else { ?>
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
              <?php $renderedPosts++; ?>
            <?php } // foreach posts
              } else {
                echo '<div class="no_content tabing flex_">'.$LANG['no_posts_yet'].'</div>';
              }
            ?>
          </div>
          </div>
        </div>
      </div>
    </div>
    <?php } // foreach creators ?>
  </div>
</div>
<?php } // if list ?>
