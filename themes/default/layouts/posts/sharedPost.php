<?php
$userSharedPostID = $sharedPostData['post_id'] ?? null;
$userSharedPostOwnerID = $sharedPostData['post_owner_id'] ?? null;
$userSharedPostText = $sharedPostData['post_text'] ?? null;
$userSharedLinkUrl = $sharedPostData['link_url'] ?? null;
$userSharedLinkDomain = $sharedPostData['link_domain'] ?? null;
$userSharedLinkTitle = $sharedPostData['link_title'] ?? null;
$userSharedLinkDescription = $sharedPostData['link_description'] ?? null;
$userSharedLinkImage = $sharedPostData['link_image'] ?? null;
$userSharedPostFile = $sharedPostData['post_file'] ?? null;
$userSharedPostCreatedTime = $sharedPostData['post_created_time'] ?? null;
$UserSharedcrTime = date('Y-m-d H:i:s',$userSharedPostCreatedTime);
$userSharedPostWantStatus = $sharedPostData['post_want_status'] ?? null;
$userPostWantedCredit = $sharedPostData['post_wanted_credit'] ?? null;
$userSharedPostStatus = $sharedPostData['post_status'] ?? null;
$userSharedPostOwnerUsername = $sharedPostData['i_username'] ?? null;
$userSharedPostOwnerUserFullName = $sharedPostData['i_user_fullname'] ?? null;
$userProfileFrame = $sharedPostData['user_frame'] ?? null;
if($fullnameorusername == 'no'){
    $userSharedPostOwnerUserFullName = $userSharedPostOwnerUsername;
}
$userSharedPostOwnerUserGender = $sharedPostData['user_gender'];
$userPostHashTags = $sharedPostData['hashtags'] ?? null;
$userSharedPostCommentAvailableStatus = $sharedPostData['comment_status'] ?? null;
$userSharedPostType = $sharedPostData['post_type'] ?? 'normal';
$userSharedPostOwnerUserLastLogin = $sharedPostData['last_login_time'] ?? null;
$userSharedProfileCategory = $postFromData['profile_category'] ?? null;
$lastSeen = date("c", $userSharedPostOwnerUserLastLogin);
$OnlineStatus = date("c", time());
$onlineWindowSeconds = 180;
$oStatus = time() - $onlineWindowSeconds;
if ((int) $userSharedPostOwnerUserLastLogin > $oStatus) {
 $timeStatus = '<div class="userIsOnline flex_ tabing"></div>';
} else {
 $timeStatus = '<div class="userIsOffline flex_ tabing"></div>';
}
$userSharedPostSharedID = $sharedPostData['shared_post_id'] ?? null;
$userSharedPostWhoCanSee = $iN->iN_CheckWhoCanSeePost($userPostSharedID);
$getPostOwnerUserID = $iN->iN_GetPostOwnerIDFromPostID($userPostID);
$userSharedPostOwnerUserAvatar = $iN->iN_UserAvatar($userSharedPostOwnerID, $base_url);
$userSharedPostUserVerifiedStatus = $sharedPostData['user_verified_status'] ?? null;
$getFriendStatusBetweenTwoUser = '';
$getFriendStatusBetweenTwoUserShared = '';
if($logedIn == '1'){
    $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userSharedPostOwnerID);
    $getFriendStatusBetweenTwoUserShared = $iN->iN_GetRelationsipBetweenTwoUsers($userPostOwnerID, $userSharedPostOwnerID);
}
// Campaign meta for shared posts
$sharedCampaignMeta = null;
if ($userSharedPostType === 'campaign') {
    $sharedCampaignMeta = $iN->iN_GetCampaignByPostId((int)$userSharedPostID);
}
if($userSharedPostOwnerUserGender == 'male'){
   $publisherGender = '<div class="i_plus_g">'.$iN->iN_SelectedMenuIcon('12').'</div>';
}else if($userSharedPostOwnerUserGender == 'female'){
   $publisherGender = '<div class="i_plus_gf">'.$iN->iN_SelectedMenuIcon('13').'</div>';
}else if($userSharedPostOwnerUserGender == 'couple'){
   $publisherGender = '<div class="i_plus_g">'.$iN->iN_SelectedMenuIcon('58').'</div>';
}
$userSharedVerifiedStatus = '';
if($userSharedPostUserVerifiedStatus == '1'){
   $userSharedVerifiedStatus = '<div class="i_plus_s">'.$iN->iN_SelectedMenuIcon('11').'</div>';
}
$profileCategory = $pCt = $profileCategoryLink = '';
if($userProfileCategory && $userSharedPostUserVerifiedStatus == '1'){
    $profileCategory = $userProfileCategory;
if(isset($PROFILE_CATEGORIES[$userProfileCategory])){
    $pCt = isset($PROFILE_CATEGORIES[$userProfileCategory]) ? $PROFILE_CATEGORIES[$userProfileCategory] : NULL;
}else if(isset($PROFILE_SUBCATEGORIES[$userProfileCategory])){
    $pCt = isset($PROFILE_SUBCATEGORIES[$userProfileCategory]) ? $PROFILE_SUBCATEGORIES[$userProfileCategory] : NULL;
}
    $profileCategoryLink = '<a class="i_p_categoryp flex_ tabing_non_justify" href="'.$base_url.'creators?creator='.$userProfileCategory.'">'.$iN->iN_SelectedMenuIcon('65').$pCt.'</a> ';
}
$onlySubs = '';
if($userSharedPostWhoCanSee == '1'){
    $onlySubs = '';
    $subPostTop = '';
    $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostSharedID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
 }else if($userSharedPostWhoCanSee == '2'){
    $subPostTop = '';
    $wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostSharedID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
    $onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
 }else if($userSharedPostWhoCanSee == '3'){
    $subPostTop = 'extensionPost';
    $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostSharedID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
    $onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div></div></div>';
 }else if($userSharedPostWhoCanSee == '4'){
   $subPostTop = 'extensionPost';
   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostSharedID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
   $onlySubs = '<div class="onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostSharedID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostSharedID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userSharedPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
 }
?>
<!--Sharing POST DETAILS-->
<div class="i_shared_post_wrapper">
    <div class="i_sharing_post_wrapper_in">
        <!--POST HEADER-->
    <div class="i_post_body_header">
        <?php
            $publicStoryFlag = $iN->iN_UserHasPublicStory($userSharedPostOwnerID) ? '1' : '0';
            $anyStoryFlag = $iN->iN_UserHasAnyStory($userSharedPostOwnerID) ? '1' : '0';
            $publicStoryClass = $publicStoryFlag === '1' ? ' has-story' : '';
        ?>
        <div class="i_post_user_avatar js-story-avatar<?php echo $publicStoryClass; ?>" data-story-user-id="<?php echo iN_HelpSecure($userSharedPostOwnerID); ?>" data-story-username="<?php echo iN_HelpSecure($userSharedPostOwnerUsername); ?>" data-has-story="<?php echo iN_HelpSecure($publicStoryFlag); ?>" data-has-any-story="<?php echo iN_HelpSecure($anyStoryFlag); ?>">
            <img src="<?php echo iN_HelpSecure($userSharedPostOwnerUserAvatar);?>"/>
        </div>
    <div class="i_post_i">
        <div class="i_post_username"><a href="<?php echo iN_HelpSecure($base_url).$userSharedPostOwnerUsername;?>"><?php echo iN_HelpSecure($userSharedPostOwnerUserFullName);?><?php echo html_entity_decode($publisherGender);?><?php echo html_entity_decode($userSharedVerifiedStatus);?><?php echo html_entity_decode($wCanSee);?><?php echo html_entity_decode($timeStatus);?></a></div>
        <div class="i_post_shared_time"><?php echo html_entity_decode($profileCategoryLink);?><a href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>">@<?php echo iN_HelpSecure($userPostOwnerUsername); ?></a> - <?php echo TimeAgo::ago($UserSharedcrTime , date('Y-m-d H:i:s'));?></div>
    </div>
    </div>
    <!--/POST HEADER-->
    <?php if ($userSharedPostType === 'campaign') { 
        $currencyCode = $sharedCampaignMeta['currency'] ?? $defaultCurrency;
        $goalDisplay = formatCurrency($sharedCampaignMeta['goal_amount'] ?? 0, $currencyCode);
        $raisedDisplay = formatCurrency($sharedCampaignMeta['raised_amount'] ?? 0, $currencyCode);
        $deadlineText = isset($sharedCampaignMeta['deadline_ts']) && $sharedCampaignMeta['deadline_ts'] ? date('M d, Y H:i', (int)$sharedCampaignMeta['deadline_ts']) : ($LANG['not_anything'] ?? '');
        $coverUrl = '';
        if (!empty($sharedCampaignMeta['cover_upload_id'])) {
            $coverFile = $iN->iN_GetUploadedFileDetails((int)$sharedCampaignMeta['cover_upload_id']);
            $coverPath = $coverFile['upload_tumbnail_file_path'] ?? ($coverFile['uploaded_file_path'] ?? '');
            if (!empty($coverPath)) {
                $coverUrl = $base_url . iN_HelpSecure($coverPath, FILTER_VALIDATE_URL);
            }
        }
        $daysLeft = null;
        if (!empty($sharedCampaignMeta['deadline_ts'])) {
            $diffSeconds = (int)$sharedCampaignMeta['deadline_ts'] - time();
            $daysLeft = $diffSeconds > 0 ? (int)ceil($diffSeconds / 86400) : 0;
        }
    ?>
    <div class="campaign_card shared_campaign" data-postid="<?php echo iN_HelpSecure($userSharedPostID); ?>">
        <?php if (!empty($coverUrl)) { ?>
            <div class="campaign_card_cover">
                <img src="<?php echo iN_HelpSecure($coverUrl, FILTER_VALIDATE_URL); ?>" alt="<?php echo iN_HelpSecure($sharedCampaignMeta['title'] ?? 'campaign'); ?>">
            </div>
        <?php } ?>
        <div class="campaign_card_body">
            <div class="campaign_card_header">
                <?php if (!empty($sharedCampaignMeta['title'])) { ?>
                    <div class="campaign_card_title"><?php echo iN_HelpSecure($sharedCampaignMeta['title']); ?></div>
                <?php } ?>
                <span class="campaign_card_status status_<?php echo iN_HelpSecure($sharedCampaignMeta['status_resolved'] ?? $sharedCampaignMeta['status'] ?? ''); ?>">
                    <?php echo iN_HelpSecure($sharedCampaignMeta['status_resolved'] ?? $sharedCampaignMeta['status'] ?? ''); ?>
                </span>
            </div>
            <?php if (!empty($sharedCampaignMeta['summary'])) { ?>
                <div class="campaign_card_summary"><?php echo nl2br(iN_HelpSecure($sharedCampaignMeta['summary'])); ?></div>
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
                    <div class="value campaign_progress_value"><?php echo number_format((float)($sharedCampaignMeta['progress'] ?? 0), 2); ?>%</div>
                </div>
            </div>
            <?php
                $donorPreview = $iN->iN_GetCampaignDonorPreview((int)$userSharedPostID, 5);
                $donorTotal = isset($donorPreview['total']) ? (int)$donorPreview['total'] : 0;
            ?>
            <?php if ($donorTotal > 0) { ?>
            <div class="campaign_donors_preview">
                <div class="campaign_donor_stack">
                    <?php foreach (($donorPreview['items'] ?? []) as $donor) { ?>
                        <div class="campaign_donor_avatar">
                            <img src="<?php echo iN_HelpSecure($donor['avatar'] ?? ''); ?>" alt="<?php echo iN_HelpSecure($donor['full_name'] ?? $donor['username'] ?? ''); ?>">
                        </div>
                    <?php } ?>
                </div>
                <div class="campaign_donor_trigger" data-ppid="<?php echo iN_HelpSecure($userSharedPostID); ?>">
                    <?php echo iN_HelpSecure($donorTotal); ?> <?php echo iN_HelpSecure($LANG['campaign_donors_title'] ?? 'Donors'); ?>
                </div>
            </div>
            <?php } ?>
            <div class="campaign_card_progress_bar">
                <span class="campaign_progress_bar_fill" style="width:<?php echo (float)($sharedCampaignMeta['progress'] ?? 0); ?>%"></span>
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
                    <span class="meta_text"><?php echo iN_HelpSecure($LANG['campaign_card_status'] ?? 'Status'); ?>: <?php echo iN_HelpSecure($sharedCampaignMeta['status_resolved'] ?? $sharedCampaignMeta['status'] ?? ''); ?></span>
                </div>
            </div>
            <div class="campaign_cta_row">
                <?php $campaignExpired = isset($sharedCampaignMeta['status_resolved']) && $sharedCampaignMeta['status_resolved'] === 'expired'; ?>
                <a class="campaign_primary_btn in_tips <?php echo iN_HelpSecure($loginFormClass); ?><?php echo $campaignExpired ? ' campaign_disabled' : ''; ?>"
                   href="javascript:void(0);"
                   data-mode="campaign"
                   data-id="<?php echo iN_HelpSecure($userSharedPostOwnerID); ?>"
                   data-ppid="<?php echo iN_HelpSecure($userSharedPostID); ?>"
                   data-expired="<?php echo $campaignExpired ? '1' : '0'; ?>"
                   data-expired-msg="<?php echo iN_HelpSecure($LANG['campaign_deadline_expired'] ?? 'Campaign deadline has passed.'); ?>"
                   data-lang-title="<?php echo iN_HelpSecure($LANG['campaign_donate_title'] ?? 'Donate to this campaign'); ?>"
                   data-lang-send="<?php echo iN_HelpSecure($LANG['campaign_donate_send'] ?? 'Send donation'); ?>"
                   data-lang-amount="<?php echo iN_HelpSecure($LANG['campaign_donate_amount'] ?? 'Donation amount'); ?>"
                   data-lang-min="<?php echo iN_HelpSecure($LANG['campaign_donate_min'] ?? 'Enter a donation amount.'); ?>">
                    <?php echo iN_HelpSecure($LANG['campaign_donate_btn'] ?? 'Donate'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php } else { ?>
    <?php
    if($userSharedPostText){
    ?>
    <!--POST CONTAINER-->
    <div class="i_post_container">
        <!--POST TEXT-->
        <div class="i_post_text">
        <?php
        $pStatus = '1'; 
        if($userSharedPostWhoCanSee != '1'){
            if($getFriendStatusBetweenTwoUserShared != 'me' && $getFriendStatusBetweenTwoUserShared != 'subscriber' && $userPostStatus != '2' && $userSharedPostWhoCanSee == '3'){
                $pStatus = '0';
            }else if($userSharedPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUserShared != 'me'){
                if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                    $pStatus = '0';
                }
            } else if($userSharedPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUserShared != 'me' && $getFriendStatusBetweenTwoUserShared != 'flwr'){
                $pStatus = '0';
            }
        }
        if($pStatus == '1'){
            if(!empty($userSharedPostText)){
                $cleanedText = $iN->sanitize_output_preserve_linebreaks(
                    $userSharedPostText,
                    $base_url
                );
                $highlightedText = $urlHighlight->highlightUrls($cleanedText);
                $highlightedText = $iN->iN_TruncateLinkText($highlightedText, 50);
                echo '<div class="i_post_text_content js-text-truncate" data-max-lines="6">' .
                    nl2br($highlightedText, false) .
                    '</div>';
            }
            if (empty($userSharedPostFile)) {
                $linkPreviewUrl = $userSharedLinkUrl ?? '';
                $linkPreviewDomain = $userSharedLinkDomain ?? '';
                $linkPreviewTitle = $userSharedLinkTitle ?? '';
                $linkPreviewDescription = $userSharedLinkDescription ?? '';
                $linkPreviewImage = $userSharedLinkImage ?? '';
                if ($linkPreviewUrl !== '') {
                    include __DIR__ . '/linkPreview.php';
                } else {
                    $regexUrl = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
                    $totalUrl = preg_match_all($regexUrl, $userSharedPostText, $matches);

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
            if(empty($userSharedPostFile)){
                echo '<div class="onlySubs">'.html_entity_decode($onlySubs).'</div>';
            }
        } 
        ?>
        </div>
        <!--/POST TEXT-->
    </div>
    <!--/POST CONTAINER-->
    <?php }
    ?>
    <?php if($userSharedPostFile){?>
    <!--POST IMAGES-->
    <div class="i_post_u_images">
        <?php
        if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userSharedPostWhoCanSee == '3') {
            echo html_entity_decode($onlySubs);
        }else if($userSharedPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me'){
            if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                echo html_entity_decode($onlySubs);
            }
        }else if($userSharedPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr'){
            echo html_entity_decode($onlySubs);
        }
        $trimValue = rtrim($userSharedPostFile, ',');
        $explodeFiles = array_unique(explode(',', $trimValue));
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
            $videoRendered = false;
        foreach($explodeFiles as $explodeVideoFile){
                $VideofileData = $iN->iN_GetUploadedFileDetails($explodeVideoFile);
                if ($VideofileData) {
                    $VideofileUploadID = $VideofileData['upload_id'] ?? null;
                    $VideofileExtension = $VideofileData['uploaded_file_ext'] ?? null;
                    $VideofilePath = $VideofileData['uploaded_file_path'] ?? null;
                    $videoFileTumbnailHere = $VideofileData['upload_tumbnail_file_path'] ?? null;
                    if ($userPostWhoCanSee != '1') {
                        if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
                            $VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
                        } else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
                            if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
                                $VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
                            }
                        } else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
                            $VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
                        }
                    }
                    $VideofilePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $VideofilePath);
                    if ($VideofileExtension == 'mp4' && !$videoRendered) {
                        $VideoPathExtension = '.jpg';
                        if ($s3Status == 1) {
                            $VideofilePathUrl = function_exists('storage_public_url') ? storage_public_url($VideofilePath) : ($base_url . $VideofilePath);
                            $VideofileTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($VideofilePathWithoutExt . $VideoPathExtension) : ($base_url . $VideofilePathWithoutExt . $VideoPathExtension);
                        }else if($WasStatus == 1){
                            $VideofilePathUrl = function_exists('storage_public_url') ? storage_public_url($VideofilePath) : ($base_url . $VideofilePath);
                            $VideofileTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($VideofilePathWithoutExt . $VideoPathExtension) : ($base_url . $VideofilePathWithoutExt . $VideoPathExtension);
                        } else if ($digitalOceanStatus == '1') {
                            $VideofilePathUrl = function_exists('storage_public_url') ? storage_public_url($VideofilePath) : ($base_url . $VideofilePath);
                            $VideofileTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($VideofilePathWithoutExt . $VideoPathExtension) : ($base_url . $VideofilePathWithoutExt . $VideoPathExtension);
                        } else {
                            $VideofilePathUrl = $base_url . $VideofilePath;
                            $VideofileTumbnailUrl = $base_url . $VideofileExtension;
                        }
                        echo '
                <div class="nonePoint" id="video' . $userPostID . '">
                    <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls preload="none" onended="videoEnded()">
                        <source src="' . $VideofilePathUrl . '" type="video/mp4">
                        Your browser does not support HTML5 video.
                    </video>
                </div>';
                        $videoRendered = true;
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
        		if ($filePathTumbnail) {
        			$imageTumbnail = $filePathTumbnail;
        		} else {
        			$imageTumbnail = $filePath;
        		}
                if($userSharedPostWhoCanSee != '1'){
                    if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userSharedPostWhoCanSee == '3'){
                          $filePath = $fileData['uploaded_x_file_path'] ?? null;
                    }else if($userSharedPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me'){
                        if($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                          $filePath = $fileData['uploaded_x_file_path'] ?? null;
                        }
                    } else if($userSharedPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr'){
                        $filePath = $fileData['uploaded_x_file_path'] ?? null;
                    }
                }
                $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                if ($s3Status == 1) {
                    if ($filePathTumbnail) {
                        $filePathUrl = function_exists('storage_public_url') ? storage_public_url($imageTumbnail) : ($base_url . $imageTumbnail);
                    } else {
                        $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                    }
                }else if($WasStatus == 1){
                    if ($filePathTumbnail) {
                        $filePathUrl = function_exists('storage_public_url') ? storage_public_url($imageTumbnail) : ($base_url . $imageTumbnail);
                    } else {
                        $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                    }
                } else if ($digitalOceanStatus == '1') {
                    if ($filePathTumbnail) {
                        $filePathUrl = function_exists('storage_public_url') ? storage_public_url($imageTumbnail) : ($base_url . $imageTumbnail);
                    } else {
                        $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                    }
                } else {
                    if ($filePathTumbnail) {
                        $filePathUrl = $base_url . $filePath;
                    } else {
                        $filePathUrl = $base_url . $filePath;
                    }
                }
                $videoPlaybutton ='';
                if($fileExtension == 'mp4') {
                    $videoPlaybutton = '<div class="playbutton">' . $iN->iN_SelectedMenuIcon('55') . '</div>';
                    $PathExtension = '.jpg';
                    if ($s3Status == 1) {
                        if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                            $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                        } else if ($getFriendStatusBetweenTwoUser == 'me') {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                            $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                        } else {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                            $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                        }
                        if ($ffmpegStatus == '1') {
                            $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                            $filePathTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                        } else {
                            if ($s3Status == 1) {
                                $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                                $filePathTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                            } else {
                                $filePathUrl = $base_url . $filePathTumbnail;
                                $filePathTumbnailUrl = $base_url . $fileData['upload_tumbnail_file_path'];
                            }
                        }
                    }else if($WasStatus == 1){
                        if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                            $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                        } else if ($getFriendStatusBetweenTwoUser == 'me') {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                            $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                        } else {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                            $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                        }
                        if ($ffmpegStatus == '1') {
                            $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                            $filePathTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                        } else {
                            if ($WasStatus == 1) {
                                $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                                $filePathTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                            } else {
                                $filePathUrl = $base_url . $filePathTumbnail;
                                $filePathTumbnailUrl = $base_url . $fileData['upload_tumbnail_file_path'];
                            }
                        }
                    } else if ($digitalOceanStatus == '1') {
                        if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
                            $filePath = $fileData['uploaded_x_file_path'] ?? null;
                        } else if ($getFriendStatusBetweenTwoUser == 'me') {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                        } else {
                            $filePath = $fileData['upload_tumbnail_file_path'] ?? null;
                        }
                        if ($ffmpegStatus == '1') {
                            $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                            $filePathTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                        } else {
                            if ($digitalOceanStatus == '1') {
                                $filePathUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                                $filePathTumbnailUrl = function_exists('storage_public_url') ? storage_public_url($filePath) : ($base_url . $filePath);
                            } else {
                                $filePathUrl = $base_url . $filePathTumbnail;
                                $filePathTumbnailUrl = $base_url . $filePath;
                            }
                        }
                    } else {
                        if($userPostWhoCanSee == '3' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                           $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                           $filePathUrl = $base_url . $filePathWithoutExt . $PathExtension;
                           $filePathTumbnailUrl = $base_url . $filePathWithoutExt . $PathExtension;
                        }else{
                            $filePathUrl = $base_url . $fileData['upload_tumbnail_file_path'];
                            $filePathTumbnailUrl = $base_url . $fileData['upload_tumbnail_file_path'];
                        }
                    } 
                    $fileisVideo = ($fileExtension == 'mp4') ? 'data-poster="' . $filePathUrl . '" data-html="#video' . $userPostID . '"' : 'data-src="'.$filePathUrl.'"';
                } else{
                    $fileisVideo = 'data-src="'.$filePathUrl.'"';
                }
            ?>
                <div class="i_post_image_swip_wrapper" data-bg="<?php echo iN_HelpSecure($filePathUrl); ?>" <?php echo $fileisVideo;?>>
                    <?php echo html_entity_decode($videoPlaybutton);?>
                    <img class="i_p_image" src="<?php echo $filePathUrl;?>">
                </div>
            <?php }
            }
            echo '</div>';
            ?> 
    </div>
    <!--POST IMAGES-->
    <?php }?>
    <?php
    $canRenderPoll = isset($pStatus) ? $pStatus === '1' : true;
    if ($canRenderPoll && isset($userSharedPostType) && $userSharedPostType === 'poll') {
        $canRenderPoll = $iN->iN_CanViewPollForPost((int) $userSharedPostID, ($logedIn ? (int) $userID : null));
        $sharedPollData = $canRenderPoll ? $iN->iN_GetPollDetailsByPostId($userSharedPostID, ($logedIn ? $userID : null)) : null;
        if ($canRenderPoll && $sharedPollData && !empty($sharedPollData['options'])) {
            $pollEnabled = isset($sharedPollData['enabled']) ? $sharedPollData['enabled'] : true;
            $userHasVoted = !empty($sharedPollData['user_vote']);
            $pollTotalLabel = $LANG['poll_total_votes'] ?? '{count} votes';
            $totalVotesText = preg_replace('/{count}/', (int)$sharedPollData['total_votes'], $pollTotalLabel);
            ?>
            <div class="poll_wrapper" data-enabled="<?php echo $pollEnabled ? '1' : '0'; ?>" data-poll="<?php echo iN_HelpSecure($sharedPollData['poll_id']); ?>" data-post="<?php echo iN_HelpSecure($userSharedPostID); ?>" data-total-label="<?php echo iN_HelpSecure($pollTotalLabel); ?>">
                <?php if (!$pollEnabled) { ?>
                    <div class="poll_disabled_note"><?php echo iN_HelpSecure($LANG['poll_disabled_now'] ?? ''); ?></div>
                <?php } ?>
                <?php foreach ($sharedPollData['options'] as $option) { ?>
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
                    <?php if (!empty($sharedPollData['has_removed_options'])) { ?>
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
    <?php } ?>
    </div>
</div>
<!--/Sharing POST DETAILS-->
