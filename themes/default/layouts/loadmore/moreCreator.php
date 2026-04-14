<?php
$creatorTypeUrl = $iN->iN_GetCreatorFromUrl($iN->iN_Secure($pageCreator), $lastPostID, $scrollLimit, $userID ?? null, $viewerCountryCode ?? null);

if ($creatorTypeUrl) {
    $creatorTypeUserIds = [];
    foreach ($creatorTypeUrl as $creatorTypeRow) {
        $creatorTypeUID = isset($creatorTypeRow['iuid']) ? (int)$creatorTypeRow['iuid'] : 0;
        if ($creatorTypeUID > 0) {
            $creatorTypeUserIds[$creatorTypeUID] = $creatorTypeUID;
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

    foreach ($creatorTypeUrl as $td) {
        $popularuserID = $td['iuid'];
        $popularUserIntID = (int)$popularuserID;
        $popularUserAvatar = $iN->iN_UserAvatar($popularuserID, $base_url);
        $creatorCover = $iN->iN_UserCover($popularuserID, $base_url);
        $popularUserName = isset($td['i_username']) ? $td['i_username'] : '';
        if ($popularUserName === '') {
            continue;
        }
        $popularUserFullName = isset($td['i_user_fullname']) ? $td['i_user_fullname'] : $popularUserName;
        if ($fullnameorusername === 'no') {
            $popularUserFullName = $popularUserName;
        }
        $uPCategory = isset($td['profile_category']) ? $td['profile_category'] : null;
        $categoryLabel = '';
        if (isset($PROFILE_CATEGORIES[$uPCategory])) {
            $categoryLabel = $PROFILE_CATEGORIES[$uPCategory];
        } elseif (isset($PROFILE_SUBCATEGORIES[$uPCategory])) {
            $categoryLabel = $PROFILE_SUBCATEGORIES[$uPCategory];
        }
        $totalPost = isset($creatorTypeTotalPostMap[$popularUserIntID]) ? (int)$creatorTypeTotalPostMap[$popularUserIntID] : (int)$iN->iN_TotalPosts($popularuserID);
        $totalImagePost = isset($creatorTypeTotalImageMap[$popularUserIntID]) ? (int)$creatorTypeTotalImageMap[$popularUserIntID] : (int)$iN->iN_TotalImagePosts($popularuserID);
        $totalVideoPosts = isset($creatorTypeTotalVideoMap[$popularUserIntID]) ? (int)$creatorTypeTotalVideoMap[$popularUserIntID] : (int)$iN->iN_TotalVideoPosts($popularuserID);
        $profileUrl = iN_HelpSecure($base_url . $popularUserName);
        ?>

        <div class="creator_list_box_wrp mor" data-last="<?php echo iN_HelpSecure($popularuserID); ?>">
            <div class="creator_l_box flex_ creator_card">
                <div class="creator_l_cover creator_card_cover" style="background-image:url(<?php echo iN_HelpSecure($creatorCover); ?>);">
                    <div class="creator_cover_layer">
                        <span class="creator_card_tag"><?php echo iN_HelpSecure($LANG['best_creators_last_week']);?></span>
                    </div>
                </div>

                <div class="creator_l_avatar_name">
                    <div class="creator_identity_row">
                        <div class="creator_avatar_container">
                            <a href="<?php echo $profileUrl; ?>">
                                <div class="creator_avatar">
                                    <img src="<?php echo iN_HelpSecure($popularUserAvatar); ?>" alt="creator">
                                </div>
                            </a>
                        </div>

                        <div class="creator_identity">
                            <div class="creator_nm transition"><a href="<?php echo $profileUrl; ?>"><?php echo iN_HelpSecure($popularUserFullName); ?></a></div>
                            <div class="creator_handle">@<?php echo iN_HelpSecure($popularUserName);?></div>
                        </div>
                    </div>

                    <div class="creator_category_row">
                        <a class="creator_category_badge" href="<?php echo iN_HelpSecure($base_url . 'creators?creator=' . $uPCategory); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('65')) . iN_HelpSecure($categoryLabel); ?>
                        </a>
                    </div>

                    <?php
                        $totalReelsPosts = isset($creatorTypeTotalReelsMap[$popularUserIntID]) ? (int)$creatorTypeTotalReelsMap[$popularUserIntID] : (int)$iN->iN_TotalReelsPosts($popularuserID);
                        $totalAudioPosts = isset($creatorTypeTotalAudioMap[$popularUserIntID]) ? (int)$creatorTypeTotalAudioMap[$popularUserIntID] : (int)$iN->iN_TotalAudioPosts($popularuserID);
                        $totalProducts = isset($creatorTypeTotalProductMap[$popularUserIntID]) ? (int)$creatorTypeTotalProductMap[$popularUserIntID] : (int)$iN->iN_TotalProductByUser($popularuserID);
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
                        $placeholder = $base_url . 'img/no_image.png';
                        if ($getLatestFivePost) {
                            foreach ($getLatestFivePost as $suggestedData) {
                                $userPostID = $suggestedData['post_id'];
                                $userPostFile = $suggestedData['post_file'];
                                $slugUrl = $base_url . 'post/' . $suggestedData['url_slug'] . '_' . $userPostID;
                                $userPostWhoCanSee = $suggestedData['who_can_see'];
                                $postType = isset($suggestedData['post_type']) ? $suggestedData['post_type'] : 'normal';
                                if ($postType === 'poll' || $postType === 'campaign') { continue; }
                                $trimValue = rtrim($userPostFile, ',');
                                $nums = preg_split('/\s*,\s*/', $trimValue);
                                $lastFileID = end($nums);
                                $getFriendStatusBetweenTwoUser = ($logedIn == '1') ? $iN->iN_GetRelationsipBetweenTwoUsers($userID, $popularuserID) : '';
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
                                $fileData = $iN->iN_GetUploadedFileDetails($lastFileID);
                                $filePathUrl = $placeholder;

                                if ($fileData) {
                                    $fileExtension = strtolower($fileData['uploaded_file_ext']);
                                    $filePath = $fileData['uploaded_file_path'];
                                    $filePathTumbnail = $fileData['upload_tumbnail_file_path'];
                                    $lockedPath = $fileData['uploaded_x_file_path'] ?? '';
                                    $imageTumbnail = $filePathTumbnail ? $filePathTumbnail : $filePath;

                                    $allowedImages = array('jpg','jpeg','png','gif','webp','bmp');
                                    $allowedVideos = array('mp4','mkv','webm','mov');
                                    if (!in_array($fileExtension, array_merge($allowedImages, $allowedVideos), true)) {
                                        continue;
                                    }

                                    $displayPath = $filePath;
                                    if (!$hasAccess) {
                                        $displayPath = $lockedPath ?: ($filePathTumbnail ?: $filePath);
                                    } else {
                                        if (in_array($fileExtension, $allowedVideos, true) && $filePathTumbnail) {
                                            $displayPath = $filePathTumbnail;
                                        }
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
                                    ?>
                                    <div class="creator_last_post_item">
                                        <div class="creator_last_post_item-box" style="background-image: url('<?php echo iN_HelpSecure($filePathUrl); ?>'), url('<?php echo iN_HelpSecure($placeholder);?>');">
                                            <a href="<?php echo iN_HelpSecure($slugUrl); ?>">
                                                <?php
                                                if ($userPostWhoCanSee !== '1' && !$hasAccess) {
                                                    echo html_entity_decode($onlySubs);
                                                }
                                                ?>
                                                <img class="creator_last_post_item-img" src="<?php echo iN_HelpSecure($filePathUrl); ?>" alt="post" onerror="this.onerror=null;this.src='<?php echo iN_HelpSecure($placeholder);?>';">
                                            </a>
                                        </div>
                                    </div>
                                <?php }
                            }
                        } else {
                            echo '<div class="no_content tabing flex_">' . iN_HelpSecure($LANG['no_posts_yet']) . '</div>';
                        } ?>
                    </div>
                    </div>
                </div>
            </div>
        </div>

    <?php }
} else {
    echo '<div class="no_creator_f_wrap flex_ tabing mor"><div class="no_c_icon">' . $iN->iN_SelectedMenuIcon('54') . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['no_more_creator_will_be_shown']) . '</div></div>';
}
?>
