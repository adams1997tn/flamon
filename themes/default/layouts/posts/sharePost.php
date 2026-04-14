<div class="i_modal_bg_in">
    <!--SHARE-->
   <div class="i_modal_in_in">
       <div class="i_modal_content">
            <!--Modal Header-->
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['re_share_title']);?>
                <div class="shareClose transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?></div>
            </div>
            <!--/Modal Header-->
            <!--Share More Text-->
            <div class="i_more_text_wrapper">
                <textarea class="more_textarea" id="" placeholder="<?php echo iN_HelpSecure($LANG['write_your_comment']);?>"></textarea>
            </div>
            <!--/Share More Text-->
            <!--Sharing POST DETAILS-->
            <div class="i_sharing_post_wrapper">
                <div class="i_sharing_post_wrapper_in">
                 <!--POST HEADER-->
                <div class="i_post_body_header">
                    <?php
                        $publicStoryFlag = $iN->iN_UserHasPublicStory($userPostOwnerID) ? '1' : '0';
                        $anyStoryFlag = $iN->iN_UserHasAnyStory($userPostOwnerID) ? '1' : '0';
                        $publicStoryClass = $publicStoryFlag === '1' ? ' has-story' : '';
                    ?>
                    <div class="i_post_user_avatar js-story-avatar<?php echo $publicStoryClass; ?>" data-story-user-id="<?php echo iN_HelpSecure($userPostOwnerID); ?>" data-story-username="<?php echo iN_HelpSecure($userPostOwnerUsername); ?>" data-has-story="<?php echo iN_HelpSecure($publicStoryFlag); ?>" data-has-any-story="<?php echo iN_HelpSecure($anyStoryFlag); ?>">
                        <img src="<?php echo iN_HelpSecure($userPostOwnerUserAvatar);?>"/>
                    </div>
                    <div class="i_post_i">
                        <div class="i_post_username"><a href="<?php echo iN_HelpSecure($base_url).$userPostOwnerUsername;?>"><?php echo iN_HelpSecure($userPostOwnerUserFullName);?> <?php echo html_entity_decode($publisherGender);?> <?php echo html_entity_decode($userVerifiedStatus);?> <?php echo html_entity_decode($wCanSee);?></a></div>
                        <div class="i_post_shared_time"><?php echo TimeAgo::ago($crTime , date('Y-m-d H:i:s'));?></div>
                    </div>
                </div>
                <!--/POST HEADER-->
                <?php if (isset($userPostType) && $userPostType === 'campaign' && isset($campaignMetaShare) && $campaignMetaShare) { 
                    $campaignShare = $campaignMetaShare;
                    $currencyCode = $campaignShare['currency'] ?? $defaultCurrency;
                    $goalDisplay = formatCurrency($campaignShare['goal_amount'] ?? 0, $currencyCode);
                    $raisedDisplay = formatCurrency($campaignShare['raised_amount'] ?? 0, $currencyCode);
                    $deadlineText = isset($campaignShare['deadline_ts']) && $campaignShare['deadline_ts'] ? date('M d, Y H:i', (int)$campaignShare['deadline_ts']) : ($LANG['not_anything'] ?? '');
                    $coverUrl = '';
                    if (!empty($campaignShare['cover_upload_id'])) {
                        $coverFile = $iN->iN_GetUploadedFileDetails((int)$campaignShare['cover_upload_id']);
                        $coverPath = $coverFile['upload_tumbnail_file_path'] ?? ($coverFile['uploaded_file_path'] ?? '');
                        if (!empty($coverPath)) {
                            $coverUrl = $base_url . iN_HelpSecure($coverPath, FILTER_VALIDATE_URL);
                        }
                    }
                    $daysLeft = null;
                    if (!empty($campaignShare['deadline_ts'])) {
                        $diffSeconds = (int)$campaignShare['deadline_ts'] - time();
                        $daysLeft = $diffSeconds > 0 ? (int)ceil($diffSeconds / 86400) : 0;
                    }
                ?>
                <div class="campaign_card shared_campaign" data-postid="<?php echo iN_HelpSecure($userPostID); ?>">
                    <?php if (!empty($coverUrl)) { ?>
                        <div class="campaign_card_cover">
                            <img src="<?php echo iN_HelpSecure($coverUrl, FILTER_VALIDATE_URL); ?>" alt="<?php echo iN_HelpSecure($campaignShare['title'] ?? 'campaign'); ?>">
                        </div>
                    <?php } ?>
                    <div class="campaign_card_body">
                        <div class="campaign_card_header">
                            <?php if (!empty($campaignShare['title'])) { ?>
                                <div class="campaign_card_title"><?php echo iN_HelpSecure($campaignShare['title']); ?></div>
                            <?php } ?>
                            <span class="campaign_card_status status_<?php echo iN_HelpSecure($campaignShare['status_resolved'] ?? $campaignShare['status'] ?? ''); ?>">
                                <?php echo iN_HelpSecure($campaignShare['status_resolved'] ?? $campaignShare['status'] ?? ''); ?>
                            </span>
                        </div>
                        <?php if (!empty($campaignShare['summary'])) { ?>
                            <div class="campaign_card_summary"><?php echo nl2br(iN_HelpSecure($campaignShare['summary'])); ?></div>
                        <?php } ?>
                        <div class="campaign_card_figures">
                            <div class="campaign_figure">
                                <div class="figure_label"><?php echo iN_HelpSecure($LANG['campaign_raised_label'] ?? 'Raised till now'); ?></div>
                                <div class="figure_value campaign_raised_value"><?php echo iN_HelpSecure($raisedDisplay); ?></div>
                            </div>
                            <div class="campaign_figure align_end">
                                <div class="figure_label"><?php echo iN_HelpSecure($LANG['campaign_card_goal'] ?? 'Goal'); ?></div>
                                <div class="figure_value"><?php echo iN_HelpSecure($goalDisplay); ?></div>
                            </div>
                        </div>
                        <div class="campaign_card_meta">
                            <div class="campaign_stat">
                                <div class="label"><?php echo iN_HelpSecure($LANG['campaign_card_deadline'] ?? 'Deadline'); ?></div>
                                <div class="value"><?php echo iN_HelpSecure($deadlineText); ?></div>
                            </div>
                            <div class="campaign_stat">
                                <div class="label"><?php echo iN_HelpSecure($LANG['campaign_card_progress'] ?? 'Progress'); ?></div>
                                <div class="value campaign_progress_value"><?php echo number_format((float)($campaignShare['progress'] ?? 0), 2); ?>%</div>
                            </div>
                        </div>
                        <div class="campaign_card_progress_bar">
                            <span class="campaign_progress_bar_fill" style="width:<?php echo (float)($campaignShare['progress'] ?? 0); ?>%"></span>
                        </div>
                        <div class="campaign_meta_row">
                            <?php if ($daysLeft !== null) { ?>
                                <div class="campaign_meta_item">
                                    <span class="meta_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?></span>
                                    <span class="meta_text"><?php echo iN_HelpSecure($daysLeft); ?> <?php echo iN_HelpSecure($LANG['campaign_days_left'] ?? 'Days left'); ?></span>
                                </div>
                            <?php } ?>
                            <div class="campaign_meta_item">
                                <span class="meta_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></span>
                                <span class="meta_text"><?php echo iN_HelpSecure($LANG['campaign_card_status'] ?? 'Status'); ?>: <?php echo iN_HelpSecure($campaignShare['status_resolved'] ?? $campaignShare['status'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } else if($userPostText){ ?>
                <!--POST CONTAINER-->
                <div class="i_post_container">
                    <!--POST TEXT-->
                    <div class="i_post_text">
                    <?php
                    $pStatus = '1'; 
                    if($userPostWhoCanSee != '1'){
                        if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3'){
                            $pStatus = '0';
                        }else if($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me'){
                            if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                                $pStatus = '0';
                            }
                        } else if($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr'){
                            $pStatus = '0';
                        }
                    }
                    if($pStatus == '1'){
                        if(!empty($userPostText)){
                            $cleanedText = $iN->sanitize_output_preserve_linebreaks(
                                $iN->iN_RemoveYoutubelink($userPostText),
                                $base_url
                            );
                            $highlightedText = $urlHighlight->highlightUrls($cleanedText);
                            $highlightedText = $iN->iN_TruncateLinkText($highlightedText, 50);
                            echo '<div class="i_post_text_content js-text-truncate" data-max-lines="6">' .
                                nl2br($highlightedText, false) .
                                '</div>';
                        }
                        if (empty($userPostFile)) {
                            $linkPreviewUrl = $userPostLinkUrl ?? '';
                            $linkPreviewDomain = $userPostLinkDomain ?? '';
                            $linkPreviewTitle = $userPostLinkTitle ?? '';
                            $linkPreviewDescription = $userPostLinkDescription ?? '';
                            $linkPreviewImage = $userPostLinkImage ?? '';
                            if ($linkPreviewUrl !== '') {
                                include __DIR__ . '/linkPreview.php';
                            } else {
                                $regexUrl = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
                                $totalUrl = preg_match_all($regexUrl, $userPostText, $matches);

                                $urls = $matches[0];
                                $embedRendered = false;
                                $firstUrl = '';
                                // go over all links
                                foreach($urls as $url)
                                {
                                    if ($firstUrl === '') {
                                        $firstUrl = $url;
                                    }
                                    $em = new Url_Expand($url);
                                    // Get the link size
                                    $site = $em->get_site();

                                    if ($site != "") {
                                        // If code is iframe then show the link in iframe
                                        $code = $em->get_iframe();
                                        if ($code == "") {
                                            // If code is embed then show the link in embed
                                            $code = $em->get_embed();
                                            if ($code == "") {
                                                // If code is thumb then show the link medium
                                                $codesrc = $em->get_thumb("medium");
                                            }
                                        }
                                        if ($code != '') {
                                            echo $code;
                                            $embedRendered = true;
                                        }
                                        break;
                                    }
                                }
                                if (!$embedRendered && $firstUrl !== '') {
                                    $fallbackPreview = $iN->iN_BuildFallbackPreviewFromUrl($firstUrl);
                                    if ($fallbackPreview) {
                                        $linkPreviewUrl = $fallbackPreview['link_url'];
                                        $linkPreviewDomain = $fallbackPreview['link_domain'];
                                        $linkPreviewTitle = $fallbackPreview['link_title'];
                                        $linkPreviewDescription = $fallbackPreview['link_description'];
                                        $linkPreviewImage = $fallbackPreview['link_image'];
                                        include __DIR__ . '/linkPreview.php';
                                    }
                                }
                            }
                        }
                    }else{
                        if(!$userPostFile){
                            echo '<div class="onlySubs">'.html_entity_decode($onlySubs).'</div>';
                        }
                    } 
                    ?>
                    </div>
                    <!--/POST TEXT-->
                </div>
                <!--/POST CONTAINER-->
                <?php } ?>
                <?php if (!($userPostType === 'campaign' && isset($campaignMetaShare) && $campaignMetaShare)) { ?>
                    <!--POST IMAGES-->
                    <div class="i_post_u_images">
                        <?php
                            if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostWhoCanSee == '3') {
                                if($userPostFile){
                                   echo html_entity_decode($onlySubs);
                                }
                            }else if($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me'){
                                if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                                    if($userPostFile){
                                        echo html_entity_decode($onlySubs);
                                     }
                                }
                            }else if($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr'){
                                if($userPostFile){
                                    echo html_entity_decode($onlySubs);
                                 }
                            }
                            $trimValue = rtrim($userPostFile,',');
                            $explodeFiles = explode(',', $trimValue);
                            $countExplodedFiles = count($explodeFiles);
                            if ($countExplodedFiles == 1) {
                                $container = 'i_image_one';
                            } else if ($countExplodedFiles == 2) {
                                $container = 'i_image_two';
                            } else if ($countExplodedFiles == 3) {
                                $container = 'i_image_three';
                            } else if ($countExplodedFiles == 4) {
                                $container = 'i_image_four';
                            } else if($countExplodedFiles >= 5) {
                                $container = 'i_image_five';
                            }
                        foreach($explodeFiles as $explodeVideoFile){
                                $VideofileData = $iN->iN_GetUploadedFileDetails($explodeVideoFile);
                                if($VideofileData){
                                    $VideofileUploadID = $VideofileData['upload_id'] ?? null;
                                    $VideofileExtension = $VideofileData['uploaded_file_ext'] ?? null;
                                    $VideofilePath = $VideofileData['uploaded_file_path'] ?? null;
                                    $VideofilePathTumbnail = $VideofileData['upload_tumbnail_file_path'] ?? null;
                                    if($userPostWhoCanSee != '1'){
                                        if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3'){
                                            $VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
                                        }else if($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me'){
                                            if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                                                $VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
                                            }
                                        }else if($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr'){
                                            $VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
                                        }
                                    }
                                    $VideofilePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $VideofilePath);
                                    if($VideofileExtension == 'mp4'){
                                        $VideoPathExtension = '.png';
                                        if(function_exists('storage_public_url')){
                                            $VideofilePathUrl = storage_public_url($VideofilePath);
                                            $VideofileTumbnailUrl = storage_public_url($VideofilePathTumbnail);
                                        }else{
                                            $VideofilePathUrl = $base_url . $VideofilePath;
                                            $VideofileTumbnailUrl = $base_url . $VideofilePathTumbnail;
                                        }
                                        echo '
                                        <div class="nonePoint" id="video'.$VideofileUploadID.'">
                                            <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls preload="none" onended="videoEnded()">
                                                <source src="'.$VideofilePathUrl.'" type="video/mp4">
                                                Your browser does not support HTML5 video.
                                            </video>
                                        </div>
                                        ';
                                    }
                            }
                        }
                        echo '<div class="'.$container.'" id="lightgallery'.$userPostID.'">';
                            foreach($explodeFiles  as $dataFile){
                                $fileData = $iN->iN_GetUploadedFileDetails($dataFile);
                                if($fileData){
                                $fileUploadID = $fileData['upload_id'] ?? null;
                                $fileExtension = $fileData['uploaded_file_ext'] ?? null;
                                $filePath = $fileData['uploaded_file_path'] ?? null;
                                $filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? null;
                                if($userPostWhoCanSee != '1'){
                                    if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3'){
                                          $filePath = $fileData['uploaded_x_file_path'] ?? null;
                                    }else if($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me'){
                                        if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                                          $filePath = $fileData['uploaded_x_file_path'] ?? null;
                                        }
                                    } else if($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr'){
                                        $filePath = $fileData['uploaded_x_file_path'] ?? null;
                                    }
                                }
                                $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                                if(function_exists('storage_public_url')){
                                    $filePathUrl = storage_public_url($filePath);
                                }else{
                                    $filePathUrl = $base_url . $filePath;
                                }
                                $videoPlaybutton ='';
                                if($fileExtension == 'mp4'){
                                    $videoPlaybutton = '<div class="playbutton">'.$iN->iN_SelectedMenuIcon('55').'</div>';
                                    $PathExtension = '.png';
                                    if(function_exists('storage_public_url')){
                                        $filePathUrl = storage_public_url($filePathTumbnail);
                                    }else{
                                        $filePathUrl = $base_url . $filePathTumbnail;
                                    }
                                    $fileisVideo = 'data-poster="'.$filePathUrl.'" data-html="#video'.$fileUploadID.'"';
                                }else{
                                    $fileisVideo = 'data-src="'.$filePathUrl.'"';
                                }
                            ?>
                                <div class="i_post_image_swip_wrapper" data-bg="<?php echo iN_HelpSecure($filePathUrl); ?>" <?php echo html_entity_decode($fileisVideo);?>>
                                    <?php echo html_entity_decode($videoPlaybutton);?>
                                    <img class="i_p_image" src="<?php echo iN_HelpSecure($filePathUrl);?>">
                                </div>
                            <?php }
                            }
                            echo '</div>';
                            ?>
                    </div>
                    <!--POST IMAGES-->
                <?php } ?>
                <?php
                $canRenderPoll = isset($pStatus) ? $pStatus === '1' : true;
                if ($canRenderPoll && isset($userPostType) && $userPostType === 'poll') {
                    $canRenderPoll = $iN->iN_CanViewPollForPost((int) $userPostID, ($logedIn ? (int) $userID : null));
                    $pollRender = $canRenderPoll ? $iN->iN_GetPollDetailsByPostId($userPostID, ($logedIn ? $userID : null)) : null;
                    if ($canRenderPoll && $pollRender && !empty($pollRender['options'])) {
                        $pollEnabled = isset($pollRender['enabled']) ? $pollRender['enabled'] : true;
                        $userHasVoted = isset($pollRender['user_vote']) && $pollRender['user_vote'];
                        $pollTotalLabel = $LANG['poll_total_votes'] ?? '{count} votes';
                        $totalVotesText = preg_replace('/{count}/', (int)$pollRender['total_votes'], $pollTotalLabel);
                        ?>
                        <div class="poll_wrapper" data-enabled="<?php echo $pollEnabled ? '1' : '0'; ?>" data-poll="<?php echo iN_HelpSecure($pollRender['poll_id']); ?>" data-post="<?php echo iN_HelpSecure($userPostID); ?>" data-total-label="<?php echo iN_HelpSecure($pollTotalLabel); ?>">
                            <?php if (!$pollEnabled) { ?>
                                <div class="poll_disabled_note"><?php echo iN_HelpSecure($LANG['poll_disabled_now'] ?? ''); ?></div>
                            <?php } ?>
                            <?php foreach ($pollRender['options'] as $option) { ?>
                                <div class="poll_option_item transition <?php echo !empty($option['voted']) ? 'poll_option_voted' : ''; ?>" data-option="<?php echo iN_HelpSecure($option['option_id']); ?>">
                                    <div class="poll_option_top flex_ tabing_non_justify">
                                        <div class="poll_option_text truncated_two"><?php echo iN_HelpSecure($option['option_text']); ?></div>
                                        <div class="poll_option_stats flex_ tabing_non_justify">
                                            <div class="poll_option_avatars flex_">
                                                <?php
                                                if (!empty($option['recent_voters'])) {
                                                    foreach ($option['recent_voters'] as $voter) {
                                                        $avatar = isset($voter['avatar']) ? $voter['avatar'] : '';
                                                        if ($avatar) {
                                                            echo '<span class="poll_avatar"><img src="' . iN_HelpSecure($avatar) . '" alt=""></span>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="poll_option_count"><?php echo iN_HelpSecure($option['votes_label'] ?? $option['votes']); ?></div>
                                            <div class="poll_option_percent"><?php echo iN_HelpSecure($option['percentage']); ?>%</div>
                                        </div>
                                    </div>
                                    <div class="poll_option_bar">
                                        <div class="poll_option_bar_fill" style="width: <?php echo iN_HelpSecure($option['percentage']); ?>%;"></div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="poll_meta flex_ tabing_non_justify">
                                <div class="poll_votes"><?php echo iN_HelpSecure($totalVotesText); ?></div>
                                <?php if ($userHasVoted) { ?>
                                    <div class="poll_voted_text"><?php echo iN_HelpSecure($LANG['poll_you_voted'] ?? ''); ?></div>
                                <?php } ?>
                                <?php if (!empty($pollRender['has_removed_options'])) { ?>
                                    <div class="poll_voted_text poll_removed_note"><?php echo iN_HelpSecure($LANG['poll_option_removed'] ?? ''); ?></div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php
                    } elseif (!$canRenderPoll) {
                        $lockedText = $onlySubs !== '' ? html_entity_decode($onlySubs) : iN_HelpSecure($LANG['poll_locked'] ?? '');
                        echo '<div class="poll_wrapper poll_empty flex_ tabing_non_justify">' . $lockedText . '</div>';
                    } elseif ($canRenderPoll) {
                        echo '<div class="poll_wrapper poll_empty flex_ tabing_non_justify">' . iN_HelpSecure($LANG['poll_options_missing'] ?? '') . '</div>';
                    }
                }
                ?>
            </div>
            </div>
            <!--/Sharing POST DETAILS-->
            <!--Modal Header-->
            <div class="i_modal_g_footer">
                <div class="shareBtn re-share transition" id="<?php echo iN_HelpSecure($userPostID);?>"><?php echo iN_HelpSecure($LANG['share']);?></div>
            </div>
            <!--/Modal Header-->
       </div>
   </div>
   <!--/SHARE-->
</div>
