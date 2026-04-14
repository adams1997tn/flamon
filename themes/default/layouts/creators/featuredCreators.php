<div class="creators_container">
    <div class="creators_list_container">
        <?php
            $featuredCreators = $iN->iN_PopularUsersFromLastWeekInExplorePage($userID ?? null, $viewerCountryCode ?? null);
            $featuredCreatorIds = [];
            if ($featuredCreators) {
                foreach ($featuredCreators as $featuredCreatorRow) {
                    $featuredUID = isset($featuredCreatorRow['post_owner_id']) ? (int)$featuredCreatorRow['post_owner_id'] : 0;
                    if ($featuredUID > 0) {
                        $featuredCreatorIds[$featuredUID] = $featuredUID;
                    }
                }
            }
            $featuredCreatorIds = array_values($featuredCreatorIds);
            if (!empty($featuredCreatorIds)) {
                $iN->iN_PreloadUserMediaPathMaps($featuredCreatorIds);
            }
            $featuredProfileMap = !empty($featuredCreatorIds) ? $iN->iN_GetUsersBasicProfileMap($featuredCreatorIds) : [];
            $featuredTotalPostMap = !empty($featuredCreatorIds) ? $iN->iN_TotalPostsMap($featuredCreatorIds) : [];
            $featuredTotalImageMap = !empty($featuredCreatorIds) ? $iN->iN_TotalImagePostsMap($featuredCreatorIds) : [];
            $featuredTotalVideoMap = !empty($featuredCreatorIds) ? $iN->iN_TotalVideoPostsMap($featuredCreatorIds) : [];
            $featuredTotalReelsMap = !empty($featuredCreatorIds) ? $iN->iN_TotalReelsPostsMap($featuredCreatorIds) : [];
            $featuredTotalAudioMap = !empty($featuredCreatorIds) ? $iN->iN_TotalAudioPostsMap($featuredCreatorIds) : [];
            $featuredTotalProductMap = !empty($featuredCreatorIds) ? $iN->iN_TotalProductByUserMap($featuredCreatorIds) : [];
            if ($featuredCreators) {
                echo '<div class="creator_pate_title">' . iN_HelpSecure($LANG['best_creators_of_last_week']) . '</div>';
                foreach ($featuredCreators as $td) {
                    $popularuserID = $td['post_owner_id'];
                    $popularUserIntID = (int)$popularuserID;
                    $profileRow = isset($featuredProfileMap[$popularUserIntID]) ? $featuredProfileMap[$popularUserIntID] : null;
                    $popularUserAvatar = $iN->iN_UserAvatar($popularuserID, $base_url);
                    $creatorCover = $iN->iN_UserCover($popularuserID, $base_url);
                    $popularUserName = $td['i_username'];
                    $popularUserFullName = $td['i_user_fullname'];
                    $userProfileFrame = isset($td['user_frame']) ? $td['user_frame'] : null;
                    if ($fullnameorusername == 'no') {
                        $popularUserFullName = $popularUserName;
                    }
                    $uPCategory = isset($profileRow['profile_category']) ? $profileRow['profile_category'] : null;
                    $totalPost = isset($featuredTotalPostMap[$popularUserIntID]) ? (int)$featuredTotalPostMap[$popularUserIntID] : (int)$iN->iN_TotalPosts($popularuserID);
                    $totalImagePost = isset($featuredTotalImageMap[$popularUserIntID]) ? (int)$featuredTotalImageMap[$popularUserIntID] : (int)$iN->iN_TotalImagePosts($popularuserID);
                    $totalVideoPosts = isset($featuredTotalVideoMap[$popularUserIntID]) ? (int)$featuredTotalVideoMap[$popularUserIntID] : (int)$iN->iN_TotalVideoPosts($popularuserID);
                    $totalReelsPosts = isset($featuredTotalReelsMap[$popularUserIntID]) ? (int)$featuredTotalReelsMap[$popularUserIntID] : (int)$iN->iN_TotalReelsPosts($popularuserID);
                    $totalAudioPosts = isset($featuredTotalAudioMap[$popularUserIntID]) ? (int)$featuredTotalAudioMap[$popularUserIntID] : (int)$iN->iN_TotalAudioPosts($popularuserID);
                    $totalProducts = isset($featuredTotalProductMap[$popularUserIntID]) ? (int)$featuredTotalProductMap[$popularUserIntID] : (int)$iN->iN_TotalProductByUser($popularuserID);
                    $pCt = null;
                    if (isset($PROFILE_CATEGORIES[$uPCategory])) {
                        $pCt = isset($PROFILE_CATEGORIES[$uPCategory]) ? $PROFILE_CATEGORIES[$uPCategory] : null;
                    } else if (isset($PROFILE_SUBCATEGORIES[$uPCategory])) {
                        $pCt = isset($PROFILE_SUBCATEGORIES[$uPCategory]) ? $PROFILE_SUBCATEGORIES[$uPCategory] : null;
                    }
                    $categoryBadge = '';
                    if ($uPCategory && isset($pCt)) {
                        $categoryLabel = iN_HelpSecure($pCt);
                        $categoryHref = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($uPCategory);
                        $categoryBadge = '<a class="creator_category_badge" href="' . $categoryHref . '">' . html_entity_decode($iN->iN_SelectedMenuIcon('65')) . $categoryLabel . '</a>';
                    }
                    $profileUrl = iN_HelpSecure($base_url . $popularUserName);
        ?>
        <div class="creator_list_box_wrp">
            <div class="creator_l_box flex_ creator_card">
                <div class="creator_l_cover creator_card_cover" style="background-image:url(<?php echo iN_HelpSecure($creatorCover);?>);">
                    <div class="creator_cover_layer">
                        <span class="creator_card_tag"><?php echo iN_HelpSecure($LANG['best_creators_last_week']);?></span>
                    </div>
                </div>
                <div class="creator_l_avatar_name">
                    <div class="creator_identity_row">
                        <div class="creator_avatar_container">
                            <?php if ($userProfileFrame) { ?>
                                <div class="frame_out_container_creator"><div class="frame_container_creator"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
                            <?php } ?>
                            <div class="creator_avatar"><img src="<?php echo iN_HelpSecure($popularUserAvatar);?>"></div>
                        </div>
                        <div class="creator_identity">
                            <div class="creator_nm truncated"><a href="<?php echo $profileUrl;?>"><?php echo iN_HelpSecure($iN->iN_Secure($popularUserFullName));?></a></div>
                            <div class="creator_handle">@<?php echo iN_HelpSecure($popularUserName);?></div>
                        </div>
                    </div>
                    <?php if ($categoryBadge) { ?>
                        <div class="creator_category_row"><?php echo $categoryBadge; ?></div>
                    <?php } ?>
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
                    <div class="i_p_items_box_ creator_stats">
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
                            $renderedPosts = 0;
                            $placeholder = $base_url . 'img/no_image.png';
                            if ($getLatestFivePost) {
                                foreach ($getLatestFivePost as $suggestedData) {
                                    if ($renderedPosts >= 4) {
                                        break;
                                    }
                                    $postType = isset($suggestedData['post_type']) ? $suggestedData['post_type'] : 'normal';
                                    $userPostID = $suggestedData['post_id'];
                                    $userPostFile = $suggestedData['post_file'] ?? '';
                                    $slugUrl = $base_url . 'post/' . ($suggestedData['url_slug'] ?? $userPostID) . '_' . $userPostID;
                                    $userPostWhoCanSee = $suggestedData['who_can_see'] ?? '1';
                                    $getFriendStatusBetweenTwoUser = '';
                                    if ($logedIn == '1') {
                                        $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $popularuserID);
                                    }
                                    $trimValue = rtrim((string)$userPostFile, ',');
                                    $explodeFiles = array_values(array_filter(array_unique(explode(',', $trimValue))));
                                    $fileUploadID = $explodeFiles[0] ?? null;
                                    $filePathUrl = $placeholder;
                                    $onlySubs = '';
                                    $displayPath = '';
                                    $fileExtension = '';
                                    $hasMedia = false;
                                    $hasAccess = ($userPostWhoCanSee === '1');
                                    if ($logedIn == '1') {
                                        if ($userPostWhoCanSee === '2') {
                                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'flwr', 'subscriber'], true);
                                        } elseif ($userPostWhoCanSee === '3') {
                                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'subscriber'], true);
                                        } elseif ($userPostWhoCanSee === '4') {
                                            $purchased = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
                                            $hasAccess = in_array($getFriendStatusBetweenTwoUser, ['me', 'subscriber'], true) || $purchased;
                                        }
                                    }
                                    if ($fileUploadID) {
                                        $fileData = $iN->iN_GetUploadedFileDetails($fileUploadID);
                                        if ($fileData) {
                                            $fileExtension = strtolower($fileData['uploaded_file_ext'] ?? '');
                                            $filePath = $fileData['uploaded_file_path'] ?? '';
                                            $filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? '';
                                            $fileLocked = $fileData['uploaded_x_file_path'] ?? '';
                                            $allowedImages = array('jpg','jpeg','png','gif','webp','bmp');
                                            $allowedVideos = array('mp4','mkv','webm','mov');
                                            if ($fileExtension && in_array($fileExtension, array_merge($allowedImages, $allowedVideos), true)) {
                                                $displayPath = $filePath ?: $filePathTumbnail;
                                                if (!$hasAccess) {
                                                    $pixelPath = $fileLocked;
                                                    if (!$pixelPath && $filePath) {
                                                        $pixelPath = preg_replace('~/uploads/(?!pixel/)~', '/uploads/pixel/', $filePath, 1);
                                                    }
                                                    $displayPath = $pixelPath ?: ($filePathTumbnail ?: $filePath);
                                                } else {
                                                    if (in_array($fileExtension, $allowedVideos, true) && $filePathTumbnail) {
                                                        $displayPath = $filePathTumbnail;
                                                    }
                                                }
                                                $hasMedia = !empty($displayPath);
                                            }
                                        }
                                    }
                                    if (!$displayPath) {
                                        $displayPath = $placeholder;
                                    }
                                    if (function_exists('storage_public_url')) {
                                        $filePathUrl = storage_public_url($displayPath);
                                    } else {
                                        $filePathUrl = $base_url . $displayPath;
                                    }
                                    if (!$filePathUrl) {
                                        $filePathUrl = $placeholder;
                                    }
                                    if ($userPostWhoCanSee == '1') {
                                        $onlySubs = '';
                                    } else if ($userPostWhoCanSee == '2') {
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">' . $iN->iN_SelectedMenuIcon('56') . '</div></div></div>';
                                    } else if ($userPostWhoCanSee == '3') {
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">' . $iN->iN_SelectedMenuIcon('56') . '</div></div></div>';
                                    } else if ($userPostWhoCanSee == '4') {
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">' . $iN->iN_SelectedMenuIcon('40') . '</div></div></div>';
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
                                                    if ($shouldHideContent) {
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
                        <?php $renderedPosts++; }} else { ?>
                            <div class="no_content tabing flex_"><?php echo iN_HelpSecure($LANG['no_posts_yet']);?></div>
                        <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
                }
            }
        ?>
    </div>
</div>
