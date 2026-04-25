<?php
// Normalize viewer context to avoid undefined notices when user is not logged in.
$userID = isset($userID) ? (int) $userID : null;
$userType = isset($userType) ? $userType : null;
$checkPostBoosted = $checkPostBoosted ?? false;
?>
<div class="i_post_body body_<?php echo iN_HelpSecure($userPostID); ?> <?php echo iN_HelpSecure($subPostTop); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>" data-last="<?php echo iN_HelpSecure($userPostID); ?>">
    <?php
        echo html_entity_decode($waitingApprove ?? '');
        echo html_entity_decode($pPinStatus ?? '');
        echo html_entity_decode($premiumPost ?? '');
        echo isset($scheduledBadge) ? $scheduledBadge : '';
    ?>

    <!--POST HEADER-->
    <div class="i_post_body_header">
        <?php echo html_entity_decode($planIcon ?? ''); ?>

        <div class="user_post_user_avatar_plus">
            <?php if (isset($userProfileFrame)) { ?>
                <div class="frame_out_container">
                    <div class="frame_container">
                        <img src="<?php echo $base_url . $userProfileFrame; ?>">
                    </div>
                </div>
            <?php } ?>

            <?php
                $publicStoryFlag = $iN->iN_UserHasPublicStory($userPostOwnerID) ? '1' : '0';
                $anyStoryFlag = $iN->iN_UserHasAnyStory($userPostOwnerID) ? '1' : '0';
                $publicStoryClass = $publicStoryFlag === '1' ? ' has-story' : '';
            ?>
            <div class="i_post_user_avatar js-story-avatar<?php echo $publicStoryClass; ?>" data-story-user-id="<?php echo iN_HelpSecure($userPostOwnerID); ?>" data-story-username="<?php echo iN_HelpSecure($userPostOwnerUsername); ?>" data-has-story="<?php echo iN_HelpSecure($publicStoryFlag); ?>" data-has-any-story="<?php echo iN_HelpSecure($anyStoryFlag); ?>">
                <img src="<?php echo iN_HelpSecure($userPostOwnerUserAvatar); ?>"/>

                <div class="i_thanks_bubble_cont tip_<?php echo iN_HelpSecure($userPostID); ?>">
                    <div class="i_bubble">
                        <?php
                            $postTipText = isset($userTextForPostTip) && $userTextForPostTip !== '' ? $userTextForPostTip : ($LANG['thanks_for_tip'] ?? '');
                            echo iN_HelpSecure($postTipText);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="i_post_i">
            <div class="i_post_username">
                <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>">
                    <?php echo iN_HelpSecure($userPostOwnerUserFullName); ?>
                    <?php echo html_entity_decode($publisherGender); ?>
                    <?php echo html_entity_decode($userVerifiedStatus); ?>
                    <?php echo html_entity_decode($wCanSee); ?>
                    <?php echo html_entity_decode($timeStatus); ?>
                </a>
            </div>

            <div class="i_post_shared_time">
                <?php
                    if ($userPostWhoCanSee === '4') {
                        echo '<div class="premium_amount_he flex_ tabing">' .
                            html_entity_decode($iN->iN_SelectedMenuIcon('40')) .
                            $userPostWantedCredit .
                            '</div>';
                    }
                    if (!empty($communityBadge)) {
                        echo $communityBadge;
                    }
                    echo html_entity_decode($profileCategoryLink);
                ?>
                <a href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>">
                    @<?php echo iN_HelpSecure($userPostOwnerUsername); ?>
                </a>
                - <?php echo TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?>
                <?php if (!empty($scheduledMeta)) { echo $scheduledMeta; } ?>
            </div>

            <?php
            $isOwnerOrAdmin = ($logedIn !== 0 && ($userPostOwnerID === $userID || $userType === '2'));
            $canModeratePost = ($logedIn !== 0 && isset($communityModCanManagePosts) && $communityModCanManagePosts && isset($page) && $page === 'community');
            ?>
            <div class="i_post_menu">
                <div class="i_post_menu_dot openPostMenu transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16')); ?>

                    <!--POST MENU-->
                    <div class="i_post_menu_container mnoBox mnoBox<?php echo iN_HelpSecure($userPostID); ?>">
                        <div class="i_post_menu_item_wrapper">
                            <?php if ($isOwnerOrAdmin) { ?>
                                <div class="i_post_menu_item_out wcs transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <span><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?></span>
                                    <?php echo iN_HelpSecure($LANG['whocanseethis']); ?>
                                </div>
                                <div class="i_post_menu_item_out edtp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?>
                                    <?php echo iN_HelpSecure($LANG['edit_post']); ?>
                                </div>
                                <div class="i_post_menu_item_out pcl transition" id="dc_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('31')); ?>
                                    <?php echo html_entity_decode($commentStatusText); ?>
                                </div>
                                <div class="i_post_menu_item_out delp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?>
                                    <?php echo iN_HelpSecure($LANG['delete_post']); ?>
                                </div>
                            <?php } elseif ($canModeratePost) { ?>
                                <div class="i_post_menu_item_out delp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?>
                                    <?php echo iN_HelpSecure($LANG['delete_post']); ?>
                                </div>
                            <?php } ?>

                            <div class="i_post_menu_item_out transition copyUrl" data-clipboard-text="<?php echo $slugUrl; ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('30')); ?>
                                <?php echo iN_HelpSecure($LANG['copy_post_url']); ?>
                            </div>

                            <a class="i_opennewtab" href="<?php echo $slugUrl; ?>" target="_blank">
                                <div class="i_post_menu_item_out transition">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('183')); ?>
                                    <?php echo iN_HelpSecure($LANG['open_in_new_tab']); ?>
                                </div>
                            </a>

                            <?php if ($logedIn !== 0 && $userPostOwnerID !== $userID) { ?>
                                <div class="i_post_menu_item_out transition rpp rpp<?php echo iN_HelpSecure($userPostID); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?>
                                    <?php echo iN_HelpSecure($LANG['report_this_post']); ?>
                                </div>
                            <?php } ?>

                            <div class="arrow"></div>

                            <?php if ($logedIn !== 0 && $userPostOwnerID === $userID) { ?>
                                <div class="i_post_menu_item_out i_pnp transition pbtn_<?php echo iN_HelpSecure($userPostID); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($pPinStatusBtn); ?>
                                </div>
                            <?php } ?>

                            <?php if ($logedIn !== 0 && $userPostOwnerID === $userID && !$checkPostBoosted) { ?>
                                <div class="i_post_menu_item_out transition boostThisPost" id="<?php echo iN_HelpSecure($userPostID); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('177')); ?>
                                    <?php echo iN_HelpSecure($LANG['boost_this_post']); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <!--/POST MENU-->
                </div>
            </div>
        </div>
    </div>
    <!--/POST HEADER-->
    <!--POST CONTAINER-->
    <div class="i_post_container" id="i_post_container_<?php echo iN_HelpSecure($userPostID); ?>">
        <!--POST TEXT-->
        <?php if (!isset($userPostType) || $userPostType !== 'campaign') { ?>
        <div class="i_post_text i_post_text_arrow" id="i_post_text_<?php echo iN_HelpSecure($userPostID); ?>">
        <?php
    $pStatus = '1'; 
    $canViewContent = true;
    $lockMarkup = $onlySubs !== '' ? '<div class="onlySubs">' . html_entity_decode($onlySubs) . '</div>' : '';
    $lockRendered = false;
    if ($userPostWhoCanSee != '1') {
    	if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
    		$pStatus = '0';
    	} else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
    		if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
    			$pStatus = '0';
    		}
    	} else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
    		$pStatus = '0';
    	}
    }
    $canViewContent = $pStatus === '1';
    if ($pStatus == '1') {
        $scheduledLiveData = $iN->iN_GetScheduledLiveByPostId((int)$userPostID);
        if (!empty($scheduledLiveData)) {
            include __DIR__ . '/../live/scheduled_live_post_card.php';
        } else {
        	if (!empty($userPostText)) {
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
        		foreach ($urls as $url) {
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
    } else {
    	echo $lockMarkup;
        $lockRendered = true;
     } 
    ?>
            </div>
        <?php } ?>
            <?php if (isset($userPostType) && $userPostType === 'campaign') {
                $campaignMeta = $iN->iN_GetCampaignByPostId((int)$userPostID);
                if ($campaignMeta) {
                    $goalLabel = $LANG['campaign_card_goal'] ?? 'Goal';
                    $progressLabel = $LANG['campaign_card_progress'] ?? 'Progress';
                    $deadlineLabel = $LANG['campaign_card_deadline'] ?? 'Deadline';
                    $statusLabel = $LANG['campaign_card_status'] ?? 'Status';
                    $deadlineText = isset($campaignMeta['deadline_ts']) && $campaignMeta['deadline_ts'] ? date('M d, Y H:i', (int)$campaignMeta['deadline_ts']) : ($LANG['not_anything'] ?? '');
                    $currencyCode = $campaignMeta['currency'] ?? $defaultCurrency;
                    $goalDisplay = formatCurrency($campaignMeta['goal_amount'] ?? 0, $currencyCode);
                    $raisedText = formatCurrency($campaignMeta['raised_amount'] ?? 0, $currencyCode);
                    $coverUrl = '';
                    if (!empty($campaignMeta['cover_upload_id'])) {
                        $coverFile = $iN->iN_GetUploadedFileDetails((int)$campaignMeta['cover_upload_id']);
                        $coverPath = $coverFile['upload_tumbnail_file_path'] ?? ($coverFile['uploaded_file_path'] ?? '');
                        if (!empty($coverPath)) {
                            $coverUrl = $base_url . iN_HelpSecure($coverPath, FILTER_VALIDATE_URL);
                        }
                    }
                    $daysLeft = null;
                    if (!empty($campaignMeta['deadline_ts'])) {
                        $diffSeconds = (int)$campaignMeta['deadline_ts'] - time();
                        $daysLeft = $diffSeconds > 0 ? (int)ceil($diffSeconds / 86400) : 0;
                    }
                    ?>
                    <div class="campaign_card" data-postid="<?php echo iN_HelpSecure($userPostID); ?>">
                        <?php if (!empty($coverUrl)) { ?>
                            <div class="campaign_card_cover">
                                <img src="<?php echo iN_HelpSecure($coverUrl, FILTER_VALIDATE_URL); ?>" alt="<?php echo iN_HelpSecure($campaignMeta['title'] ?? 'campaign'); ?>">
                            </div>
                        <?php } ?>
                        <div class="campaign_card_body">
                        <div class="campaign_card_header">
                            <?php if (!empty($campaignMeta['title'])) { ?>
                                <div class="campaign_card_title"><?php echo iN_HelpSecure($campaignMeta['title']); ?></div>
                            <?php } ?>
                            <span class="campaign_card_status status_<?php echo iN_HelpSecure($campaignMeta['status_resolved'] ?? $campaignMeta['status']); ?>">
                                <?php echo iN_HelpSecure($campaignMeta['status_resolved'] ?? $campaignMeta['status']); ?>
                            </span>
                        </div>
                        <?php if (!empty($campaignMeta['summary'])) { ?>
                            <div class="campaign_card_summary"><?php echo nl2br(iN_HelpSecure($campaignMeta['summary'])); ?></div>
                        <?php } ?>
                            <div class="campaign_card_figures">
                            <div class="campaign_figure">
                                <div class="figure_label"><?php echo iN_HelpSecure($LANG['campaign_raised_label'] ?? 'Raised till now'); ?></div>
                                <div class="figure_value campaign_raised_value"><?php echo iN_HelpSecure($raisedText); ?></div>
                            </div>
                            <div class="campaign_figure align_end">
                                <div class="figure_label"><?php echo iN_HelpSecure($goalLabel); ?></div>
                                <div class="figure_value"><?php echo iN_HelpSecure($goalDisplay); ?></div>
                            </div>
                        </div>
                        <div class="campaign_card_meta">
                            <div class="campaign_stat">
                                <div class="label"><?php echo iN_HelpSecure($deadlineLabel); ?></div>
                                <div class="value"><?php echo iN_HelpSecure($deadlineText); ?></div>
                            </div>
                            <div class="campaign_stat">
                                <div class="label"><?php echo iN_HelpSecure($progressLabel); ?></div>
                                <div class="value campaign_progress_value"><?php echo number_format((float)($campaignMeta['progress'] ?? 0), 2); ?>%</div>
                            </div>
                            <div class="campaign_stat">
                                <div class="label"><?php echo iN_HelpSecure($LANG['campaign_goal_hint'] ?? 'Total amount you want to raise.'); ?></div>
                                <div class="value"><?php echo iN_HelpSecure($goalDisplay); ?></div>
                            </div>
                        </div>
                        <?php
                            $donorPreview = $iN->iN_GetCampaignDonorPreview((int)$userPostID, 5);
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
                            <div class="campaign_donor_trigger" data-ppid="<?php echo iN_HelpSecure($userPostID); ?>">
                                <?php echo iN_HelpSecure($donorTotal); ?> <?php echo iN_HelpSecure($LANG['campaign_donors_title'] ?? 'Donors'); ?>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="campaign_card_progress_bar">
                            <span class="campaign_progress_bar_fill" style="width:<?php echo (float)($campaignMeta['progress'] ?? 0); ?>%"></span>
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
                                <span class="meta_text"><?php echo iN_HelpSecure($statusLabel); ?>: <?php echo iN_HelpSecure($campaignMeta['status_resolved'] ?? $campaignMeta['status']); ?></span>
                            </div>
                        </div>
                        <div class="campaign_cta_row">
                            <?php $campaignExpired = isset($campaignMeta['status_resolved']) && $campaignMeta['status_resolved'] === 'expired'; ?>
                            <a class="campaign_primary_btn in_tips <?php echo iN_HelpSecure($loginFormClass); ?><?php echo $campaignExpired ? ' campaign_disabled' : ''; ?>"
                               href="javascript:void(0);"
                               data-mode="campaign"
                               data-id="<?php echo iN_HelpSecure($userPostOwnerID); ?>"
                               data-ppid="<?php echo iN_HelpSecure($userPostID); ?>"
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
                <?php }
            } ?>
            <!--/POST TEXT-->
            <!--SHARED POST-->
            <?php
    if ($userPostSharedID) {
    	$sharedPostData = $iN->iN_GetAllPostDetails($userPostSharedID);
    	if ($sharedPostData) {
    		include "sharedPost.php";
    	} else {
    		echo '
                        <div class="i_shared_post_wrapper">
                           <div class="i_sharing_post_wrapper_in">
                               <div class="empty_data_container">
                                   <div class="empty_data_icon">' . $iN->iN_SelectedMenuIcon('59') . '</div>
                                   <div class="empty_data_desc_cont">
                                        <div class="empty_data_desc_title">' . $LANG['empty_shared_title'] . '</div>
                                        <div class="empty_data_desc_des">' . $LANG['empty_shared_desc'] . '</div>
                                   </div>
                               </div>
                           </div>
                        </div>
                        ';
    	}
    }
    ?>
        <!--/SHARED POST-->
    </div>
    <!--/POST CONTAINER-->
    <?php
    $canRenderPoll = isset($pStatus) ? $pStatus === '1' : true;
    if ($canRenderPoll && isset($userPostType) && $userPostType === 'poll') {
        $canRenderPoll = $iN->iN_CanViewPollForPost((int) $userPostID, ($logedIn ? (int) $userID : null));
        $pollRender = $canRenderPoll ? ($pollData ?? $iN->iN_GetPollDetailsByPostId($userPostID, ($logedIn ? $userID : null))) : null;
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
    $allowReShare = ($userPostWhoCanSee !== '4');
    if (!empty($communityReshareDisabled)) {
        $allowReShare = false;
    }
    ?>
    <!--POST LIKE/COMMENT/SHARE/SOCIAL SHARE/SAVE BUTTONS-->
    <div class="i_post_footer" id="pf_l_<?php echo iN_HelpSecure($userPostID); ?>">
    <div class="i_post_footer_item">
              <div class="i_post_item_btn transition <?php echo iN_HelpSecure($likeClass); ?> <?php echo iN_HelpSecure($loginFormClass); ?>" id="p_l_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($likeIcon); ?></div>
              <div class="lp_sum flex_ tabing" id="lp_sum_<?php echo iN_HelpSecure($userPostID); ?>"><?php echo iN_HelpSecure($likeSum); ?></div>
            </div>
        <?php if ($logedIn != 0 && $getUserPaymentMethodStatus && $userPostOwnerID != $userID) {?>
        <div class="i_post_footer_item">
           <div class="i_post_item_btn transition in_tips flex_ tabing <?php echo iN_HelpSecure($loginFormClass); ?>" data-id="<?php echo iN_HelpSecure($userPostOwnerID); ?>" data-ppid="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('144')); ?> </div>
        </div>
        <?php }?>
        <div class="i_post_footer_item">
            <div class="i_post_item_btn transition in_comment" id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('20')); ?></div>
        </div>
        <?php if ($allowReShare) { ?>
        <div class="i_post_footer_item">
           <?php if (empty($communityReshareDisabled)) { ?>
           <div class="i_post_item_btn transition in_share" id="share_<?php echo isset($userPostSharedID) ? $userPostSharedID : $userPostID; ?>" data-id="<?php echo isset($userPostSharedID) ? $userPostSharedID : $userPostID; ?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('19')); ?></div>
           <?php } ?>
        </div>
        <?php } ?>
        <div class="i_post_footer_item">
           <div class="i_post_item_btn transition in_social_share openShareMenu" id="<?php echo iN_HelpSecure($userPostID); ?>">
               <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('21')); ?>
               <!--SHARE POST-->
               <?php
                   $postShareUrl = $postShareUrl ?? $iN->iN_GetShareUrlForPost($userPostID, $base_url, $userID ?? null);
                   $shareLink = $postShareUrl ?? $slugUrl;
               ?>
               <div class="i_share_this_post mnsBox mnsBox<?php echo iN_HelpSecure($userPostID); ?>">
                   <div class="i_share_menu_wrapper">
                       <!--MENU ITEM-->
                        <div class="i_post_menu_item_out transition share-btn"
                             data-social="facebook"
                             data-url="<?php echo iN_HelpSecure($shareLink); ?>"
                             data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('33')); ?>
                            <?php echo iN_HelpSecure($LANG['share_on_facebook']); ?>
                        </div>
                        <!--/MENU ITEM-->
                        
                        <!--MENU ITEM-->
                        <div class="i_post_menu_item_out transition share-btn"
                             data-social="twitter"
                             data-url="<?php echo iN_HelpSecure($shareLink); ?>"
                             data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('34')); ?>
                            <?php echo iN_HelpSecure($LANG['share_on_twitter']); ?>
                        </div>
                        <!--/MENU ITEM-->

                        <!--MENU ITEM-->
                        <div class="i_post_menu_item_out transition share-btn"
                             data-social="whatsapp"
                             data-url="<?php echo iN_HelpSecure($shareLink); ?>"
                             data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('147')); ?>
                            <?php echo iN_HelpSecure($LANG['share_on_whatsapp']); ?>
                        </div>
                        <!--/MENU ITEM-->
                   </div>
               </div>
               <!--/SHARE POST-->
           </div>
        </div>
        <div class="i_post_footer_item">
           <div class="i_post_item_btn transition svp in_save_<?php echo iN_HelpSecure($userPostID); ?> in_save" id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($pSaveStatusBtn); ?></div>
        </div>
    </div>
	    <!--/POST LIKE/COMMENT/SHARE/SOCIAL SHARE/SAVE BUTTONS-->
	    <?php
	    $hasBoostRecord = $hasBoostRecord ?? false;
	    $checkPostBoosted = $checkPostBoosted ?? false;
	    if($hasBoostRecord && isset($userID) && ((int)$userPostOwnerID === (int)$userID) && !empty($boostID)){
	        $boostSeenCount = (int)$iN->iN_CountSeenBoostedPostbyID($userPostOwnerID, $boostID);
	        $boostTotalViews = (int)$viewCount;
	        $boostRemainingViews = ($boostTotalViews > 0) ? max(0, $boostTotalViews - $boostSeenCount) : 0;
	        $boostStartedAt = (isset($getBoostDetails) && is_array($getBoostDetails)) ? (int)($getBoostDetails['started_at'] ?? 0) : 0;
	        $boostEndAt = (isset($getBoostDetails) && is_array($getBoostDetails)) ? (int)($getBoostDetails['end_at'] ?? 0) : 0;
	        $expireDays = isset($boostPostExpireDays) ? (int)$boostPostExpireDays : 30;
	        if ($boostEndAt <= 0 && $boostStartedAt > 0 && $expireDays > 0) {
	            $boostEndAt = $boostStartedAt + ($expireDays * 86400);
	        }
	        $now = time();
	        $boostSecondsLeft = ($boostEndAt > 0) ? max(0, $boostEndAt - $now) : 0;
	        $boostDaysLeft = ($boostEndAt > 0) ? (int)ceil($boostSecondsLeft / 86400) : 0;
	        $boostDaysTotal = 0;
	        if ($boostStartedAt > 0 && $boostEndAt > 0 && $boostEndAt > $boostStartedAt) {
	            $boostDaysTotal = (int)ceil(($boostEndAt - $boostStartedAt) / 86400);
	        } elseif ($expireDays > 0) {
	            $boostDaysTotal = (int)$expireDays;
	        }
	        $boostDaysElapsed = ($boostDaysTotal > 0) ? max(0, $boostDaysTotal - $boostDaysLeft) : 0;
	        $expiredByTime = ($boostEndAt > 0 && $boostEndAt <= $now);
	        $expiredByViews = ($boostTotalViews > 0 && $boostSeenCount >= $boostTotalViews);
	        $boostIsExpired = ($expiredByTime || $expiredByViews);
            $boostShowPercentRaw = ($viewCount > 0) ? (($boostSeenCount / $viewCount) * 100) : 0;
            $boostShowPercentWidth = max(0, min(100, $boostShowPercentRaw));
            $boostShowPercentLabel = number_format($boostShowPercentRaw, 1);
            $boostProgressClass = ($boostShowPercentWidth > 0) ? 'has-progress' : '';
	    ?>
	    <!--Post BOOST Footer-->
		<div class="i_post_footer_boost bstatistick_<?php echo iN_HelpSecure($boostID);?>">
		  <!---->
		  <div class="show_hide_statistic">
	      <div class="stat_icon flex_ tabing b_p_p_<?php echo iN_HelpSecure($boostID);?>" id="<?php echo iN_HelpSecure($boostID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('174')); ?></div>
	      <div class="stat_icona flex_ tabing b_p_p_<?php echo iN_HelpSecure($boostID);?>" id="<?php echo iN_HelpSecure($boostID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10')); ?></div>
	  </div>
	  <!---->
	      <div class="i_post_footer_boost_item">
			<div class="ipf_item"><?php echo iN_HelpSecure($LANG['status']);?></div>
			<div class="ipf_item">
			    <div class="i_sub_not_check_box">
	                <label class="el-switch el-switch-yellow" for="boost_s_<?php echo iN_HelpSecure($boostID);?>">
	                    <input type="checkbox" name="boost_s_<?php echo iN_HelpSecure($boostID);?>" data-id="<?php echo iN_HelpSecure($boostID);?>" id="boost_s_<?php echo iN_HelpSecure($boostID);?>" class="boosStat" <?php echo iN_HelpSecure($boostStatus) == 'yes' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($boostStatus) == 'yes' ? 'no' : 'yes';?>" <?php echo $boostIsExpired ? 'disabled="disabled" title="'.iN_HelpSecure($LANG['boost_expired']).'"' : '';?>>
	                    <span class="el-switch-style"></span>
	                </label>
	            </div>
			</div>
		  </div>
		  <div class="i_post_footer_boost_item">
            <div class="ipf_stats_panel">
                <div class="ipf_stats_row">
                    <div class="ipf_stat_col">
                        <div class="ipf_stat_label"><?php echo iN_HelpSecure($LANG['number_of_people_show']);?></div>
                        <div class="ipf_stat_value"><?php echo iN_HelpSecure($viewCount);?></div>
                    </div>
                    <div class="ipf_stat_col">
                        <div class="ipf_stat_label"><?php echo iN_HelpSecure($LANG['view_viewed']);?></div>
                        <div class="ipf_stat_value"><?php echo iN_HelpSecure($boostSeenCount);?></div>
                    </div>
                </div>
                <div class="ipf_stats_row compact">
                    <div class="ipf_stat_col">
                        <div class="ipf_stat_label subtle"><?php echo iN_HelpSecure($LANG['boost_views_left']);?></div>
                        <div class="ipf_stat_value alt"><?php echo iN_HelpSecure($boostRemainingViews);?></div>
                    </div>
                    <div class="ipf_stat_col">
                        <div class="ipf_stat_label subtle"><?php echo iN_HelpSecure($LANG['boost_days_left']);?></div>
                        <div class="ipf_stat_value alt"><?php echo iN_HelpSecure($boostDaysLeft);?></div>
                    </div>
                </div>
            </div>
		  </div>
		  <div class="boost_charts_wrapper" id="boost_charts_<?php echo iN_HelpSecure($boostID); ?>"
		       data-boost-id="<?php echo iN_HelpSecure($boostID); ?>"
		       data-views-total="<?php echo iN_HelpSecure($boostTotalViews); ?>"
		       data-views-seen="<?php echo iN_HelpSecure($boostSeenCount); ?>"
		       data-views-left="<?php echo iN_HelpSecure($boostRemainingViews); ?>"
		       data-days-total="<?php echo iN_HelpSecure($boostDaysTotal); ?>"
		       data-days-elapsed="<?php echo iN_HelpSecure($boostDaysElapsed); ?>"
		       data-days-left="<?php echo iN_HelpSecure($boostDaysLeft); ?>">
		      <div class="boost_chart_box">
		          <canvas id="boost_line_chart_<?php echo iN_HelpSecure($boostID); ?>" height="140"></canvas>
		      </div>
		  </div>
		</div>
		<!--/Post BOOST Footer-->
	    <?php }?>
    <?php echo html_entity_decode($TotallyPostComment); ?>
    <!--COMMENT FORM COMMENTS-->
    <div class="i_post_comments_wrapper">
        <div class="i_post_comments_box<?php echo $logedIn == 0 ? ' nonePoint' : ''; ?>" id="all_comments_<?php echo iN_HelpSecure($userPostID); ?>">
            <!--USER COMMENTS-->
            <div class="i_user_comments" id="i_user_comments_<?php echo iN_HelpSecure($userPostID); ?>">
            <?php
            if ($getUserComments && $logedIn == 1) {
            	foreach ($getUserComments as $comment) {
            		$commentID = $comment['com_id'] ?? null;
            		$commentedUserID = $comment['comment_uid_fk'] ?? null;
            		$Usercomment = $comment['comment'] ?? null;
            		$commentTime = $comment['comment_time'] ?? null;
            		$corTime = date('Y-m-d H:i:s', $commentTime);
            		$commentFile = $comment['comment_file'] ?? null;
            		$stickerUrl = $comment['sticker_url'] ?? null;
            		$gifUrl = $comment['gif_url'] ?? null;
            		$commentedUserIDFk = $comment['iuid'] ?? null;
            		$commentedUserName = $comment['i_username'] ?? null;
            		$commentedUserFullName = $comment['i_user_fullname'] ?? null;
                    if($fullnameorusername == 'no'){
            			$commentedUserFullName = $commentedUserName;
            		}
                    $checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
                    $cUType = '';
                    if($checkUserIsCreator){
                        $cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
                    }
            		$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
            		$commentedUserGender = $comment['user_gender'] ?? null;
            		if ($commentedUserGender == 'male') {
            			$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
            		} else if ($commentedUserGender == 'female') {
            			$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
            		} else if ($commentedUserGender == 'couple') {
            			$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
            		}
            		$commentedUserLastLogin = $comment['last_login_time'] ?? null;
            		$commentedUserVerifyStatus = $comment['user_verified_status'] ?? null;
            		$cuserVerifiedStatus = '';
            		if ($commentedUserVerifyStatus == '1') {
            			$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
            		}
            		$commentParentId = (int)($comment['parent_comment_id'] ?? 0);
            		$commentReplies = $iN->iN_GetCommentReplies($commentID);
            		$commentReplyCount = $commentReplies ? count($commentReplies) : 0;
            		$isReply = false;
            		$replyParentUserName = '';
            		$replyParentUserFullName = '';
            		$commentLikeBtnClass = 'c_in_like';
            		$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
            		$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
            		if ($logedIn != 0) {
            			$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
            			$checkCommentReportedBefore = $iN->iN_CheckCommentReportedBefore($userID, $commentID);
            			if ($checkCommentLikedBefore == '1') {
            				$commentLikeBtnClass = 'c_in_unlike';
            				$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
            			}
            			if ($checkCommentReportedBefore == '1') {
            				$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
            			}
            		}
            		$stickerComment = '';
            		$gifComment = '';
            		if ($stickerUrl) {
            			$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
            		}
            		if ($gifUrl) {
            			$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
            		}
            		$commentLinkUrl = $comment['link_url'] ?? '';
            		$commentLinkDomain = $comment['link_domain'] ?? '';
            		$commentLinkTitle = $comment['link_title'] ?? '';
            		$commentLinkDescription = $comment['link_description'] ?? '';
            		$commentLinkImage = $comment['link_image'] ?? '';
            		include "comments.php";
            	}
    }
    ?>
</div>
            <!--/USER COMMENTS-->
            <?php
            if ($logedIn != '0') {
                if ($userPostCommentAvailableStatus === '1') {
                    include 'comment.php';
                } elseif ($userPostCommentAvailableStatus === '0') {
                    if ($userType === '2' || $userPostOwnerID === $userID) {
                        include 'comment.php';
                    } else {
                        echo '
                            <div class="i_comment_form">
                                <div class="need_login">' . iN_HelpSecure($LANG['comments_limited_for_this_post']) . '</div>
                            </div>';
                    }
                }
            } elseif ($logedIn === '0') {
                ?>
                <div class="i_comment_form">
                    <div class="need_login"><?php echo iN_HelpSecure($LANG['must_login_for_comment']); ?></div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <!--/COMMENT FORM COMMENTS-->
</div>
