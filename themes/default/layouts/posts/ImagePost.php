<div class="i_post_body body_<?php echo iN_HelpSecure($userPostID); ?> <?php echo iN_HelpSecure($subPostTop); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>" data-last="<?php echo iN_HelpSecure($userPostID); ?>">
<?php echo html_entity_decode($waitingApprove ?? '');
echo html_entity_decode($pPinStatus ?? '');
echo isset($scheduledBadge) ? $scheduledBadge : ''; ?>
    <!--POST HEADER-->
    <div class="i_post_body_header">
	    <?php 
        echo html_entity_decode($planIcon ?? '');
        echo html_entity_decode($premiumPost ?? '');
        ?>

	    <div class="user_post_user_avatar_plus">
	        <?php if($userProfileFrame){ ?>
                <div class="frame_out_container"><div class="frame_container"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
            <?php }?>
            <?php
                $publicStoryFlag = $iN->iN_UserHasPublicStory($userPostOwnerID) ? '1' : '0';
                $anyStoryFlag = $iN->iN_UserHasAnyStory($userPostOwnerID) ? '1' : '0';
                $publicStoryClass = $publicStoryFlag === '1' ? ' has-story' : '';
            ?>
            <div class="i_post_user_avatar js-story-avatar<?php echo $publicStoryClass; ?>" data-story-user-id="<?php echo iN_HelpSecure($userPostOwnerID); ?>" data-story-username="<?php echo iN_HelpSecure($userPostOwnerUsername); ?>" data-has-story="<?php echo iN_HelpSecure($publicStoryFlag); ?>" data-has-any-story="<?php echo iN_HelpSecure($anyStoryFlag); ?>">
                <img src="<?php echo iN_HelpSecure($userPostOwnerUserAvatar); ?>"/>
                <!---->
                <div class="i_thanks_bubble_cont tip_<?php echo iN_HelpSecure($userPostID); ?>">
                    <div class="i_bubble">
                        <?php
                            $postTipText = isset($userTextForPostTip) && $userTextForPostTip !== '' ? $userTextForPostTip : ($LANG['thanks_for_tip'] ?? '');
                            echo iN_HelpSecure($postTipText);
                        ?>
                    </div>
                </div>
                <!---->
            </div>
        </div>
        <div class="i_post_i">
            <div class="i_post_username">
                <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>">
                <?php echo iN_HelpSecure($userPostOwnerUserFullName); ?>
                <?php echo html_entity_decode($userVerifiedStatus); ?>
                <?php echo html_entity_decode($timeStatus);?></a></div>
            <div class="i_post_shared_time">
                <?php if($userPostWhoCanSee == '4'){echo '<div class="premium_amount_he flex_ tabing">'.html_entity_decode($iN->iN_SelectedMenuIcon('40')).$userPostWantedCredit.'</div>';} ;?>
                <?php if(!empty($communityBadge)){echo $communityBadge;}?>
                <?php echo html_entity_decode($profileCategoryLink);?>
                <a href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>">@
                    <?php echo iN_HelpSecure($userPostOwnerUsername); ?>
            </a> - <?php echo date('H:i', strtotime($crTime)); ?>
            <?php if(!empty($scheduledMeta)){ echo $scheduledMeta; } ?></div>
            <?php
            $isOwnerOrAdmin = ($logedIn != 0 && ($userPostOwnerID == $userID || $userType == '2'));
            $canModeratePost = ($logedIn != 0 && isset($communityModCanManagePosts) && $communityModCanManagePosts && isset($page) && $page === 'community');
            ?>
            <div class="i_post_menu">
                <div class="i_post_menu_dot openPostMenu transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16')); ?>
                    <!--POST MENU-->
                    <div class="i_post_menu_container mnoBox mnoBox<?php echo iN_HelpSecure($userPostID); ?>">
                       <div class="i_post_menu_item_wrapper">
                           <?php if ($isOwnerOrAdmin) {?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out wcs transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <span><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?></span> <?php echo iN_HelpSecure($LANG['whocanseethis']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out edtp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?> <?php echo iN_HelpSecure($LANG['edit_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out pcl transition" id="dc_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('31')); ?> <?php echo html_entity_decode($commentStatusText); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out delp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?> <?php echo iN_HelpSecure($LANG['delete_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <?php } elseif ($canModeratePost) { ?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out delp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?> <?php echo iN_HelpSecure($LANG['delete_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <?php }?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out transition copyUrl" data-clipboard-text="<?php echo $slugUrl; ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('30')); ?> <?php echo iN_HelpSecure($LANG['copy_post_url']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <a class="i_opennewtab" href="<?php echo $slugUrl; ?>" target="blank_">
                           <div class="i_post_menu_item_out transition">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('183')); ?> <?php echo iN_HelpSecure($LANG['open_in_new_tab']); ?>
                           </div>
                           </a>
                           <!--/MENU ITEM-->
                           <?php if ($logedIn != 0 && ($userPostOwnerID != $userID)) {?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out transition rpp rpp<?php echo iN_HelpSecure($userPostID); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?> <?php echo iN_HelpSecure($LANG['report_this_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <?php }?>
						   <div class="arrow"></div>
						   <?php if ($logedIn != 0 && ($userPostOwnerID == $userID)) {?>
						   <!--MENU ITEM-->
                           <div class="i_post_menu_item_out i_pnp transition pbtn_<?php echo iN_HelpSecure($userPostID); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($pPinStatusBtn); ?>
                           </div>
                           <!--/MENU ITEM-->
						   <?php }?>
						   <?php if ($logedIn != 0 && ($userPostOwnerID == $userID) && !$checkPostBoosted) {?>
						   <!--MENU ITEM-->
                           <div class="i_post_menu_item_out transition boostThisPost" id="<?php echo iN_HelpSecure($userPostID);?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('177')); ?> <?php echo iN_HelpSecure($LANG['boost_this_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
						   <?php }?>
                       </div>
                    </div>
                    <!--/POST MENU-->
                </div>
            </div>
        </div>
    </div>
    <!--/POST HEADER-->
    <?php if (!empty($userPostText) && (!isset($userPostType) || $userPostType !== 'campaign')) {
	?>
    <!--POST CONTAINER-->
    <div class="i_post_container <?php echo iN_HelpSecure($postStyle); ?>" id="i_post_container_<?php echo iN_HelpSecure($userPostID); ?>">
        <!--POST TEXT-->
        <div class="i_post_text" id="i_post_text_<?php echo iN_HelpSecure($userPostID); ?>">
            <?php
                $pStatus = '1';
    
                if ($userPostWhoCanSee != '1') {
                    if (
                        $getFriendStatusBetweenTwoUser != 'me' &&
                        $getFriendStatusBetweenTwoUser != 'subscriber' &&
                        $userPostStatus != '2' &&
                        $userPostWhoCanSee == '3'
                    ) {
                        $pStatus = '0';
                    } elseif (
                        $userPostWhoCanSee == '4' &&
                        $getFriendStatusBetweenTwoUser != 'me'
                    ) {
                        if (
                            $checkUserPurchasedThisPost == '0' &&
                            $getFriendStatusBetweenTwoUser != 'subscriber'
                        ) {
                            $pStatus = '0';
                        }
                    } elseif (
                        $userPostWhoCanSee == '2' &&
                        $getFriendStatusBetweenTwoUser != 'me' &&
                        $getFriendStatusBetweenTwoUser != 'flwr'
                    ) {
                        $pStatus = '0';
                    }
                }
    
                if ($pStatus == '1') {
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
    
                    if (empty($userPostFile)) {
                        $linkPreviewUrl = $userPostLinkUrl ?? '';
                        $linkPreviewDomain = $userPostLinkDomain ?? '';
                        $linkPreviewTitle = $userPostLinkTitle ?? '';
                        $linkPreviewDescription = $userPostLinkDescription ?? '';
                        $linkPreviewImage = $userPostLinkImage ?? '';
                        if ($linkPreviewUrl !== '') {
                            include __DIR__ . '/linkPreview.php';
                        } else {
                            $regexUrl = '/\\b(https?|ftp|file):\\/\\/[\\-A-Z0-9+&@#\\/\\%?=~_|$!:,.;]*[A-Z0-9+&@#\\/%=~_|$]/i';
                            $totalUrl = preg_match_all($regexUrl, $userPostText, $matches);

                            $urls = $matches[0];
                            $embedRendered = false;
                            $firstUrl = '';

                            // Go over all links
                            foreach ($urls as $url) {
                                if ($firstUrl === '') {
                                    $firstUrl = $url;
                                }
                                $em = new Url_Expand($url);
                                // Get the link site
                                $site = $em->get_site();

                                if ($site != '') {
                                    // If code is iframe then show the link in iframe
                                    $code = $em->get_iframe();
                                    if ($code == '') {
                                        // If code is embed then show the link in embed
                                        $code = $em->get_embed();
                                        if ($code == '') {
                                            // If code is thumb then show the link medium
                                            $codesrc = $em->get_thumb('medium');
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
                }
            ?>
        </div>
        <!--/POST TEXT-->
    </div>
    <!--/POST CONTAINER-->
    <?php }?>
    <!--POST IMAGES-->
    <div class="i_post_u_images <?php echo iN_HelpSecure($loginFormClass); ?>">
        <?php
            if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostWhoCanSee == '3') {
            	echo html_entity_decode($onlySubs);
            } else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
            	if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
            		echo html_entity_decode($onlySubs);
            	}
            } else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
            	echo html_entity_decode($onlySubs);
            }
            $trimValue = rtrim($userPostFile, ',');
            $explodeFiles = explode(',', $trimValue);
            $explodeFiles = array_unique($explodeFiles);
            $countExplodedFiles = $iN->iN_CheckCountFile($userPostFile);
            $container = '';
            $fullSingleImage = '';
            if ($countExplodedFiles == 1) {
            	$container = 'i_image_one';
                $fullSingleImage = 'i_image_one_full';
            } else if ($countExplodedFiles == 2) {
            	$container = 'i_image_two';
            } else if ($countExplodedFiles == 3) {
            	$container = 'i_image_three';
            } else if ($countExplodedFiles == 4) {
            	$container = 'i_image_four';
            } else if ($countExplodedFiles >= 5) {
            	$container = 'i_image_five';
            }
            foreach ($explodeFiles as $explodeVideoFile) {
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
			if ($VideofileExtension == 'mp4') {
				$VideoPathExtension = '.jpg';
				if (function_exists('storage_public_url')) {
					$VideofilePathUrl = storage_public_url($VideofilePath);
					$VideofileTumbnailUrl = storage_public_url($VideofilePathWithoutExt . $VideoPathExtension);
				} else {
					$VideofilePathUrl = $base_url . $VideofilePath;
					$VideofileTumbnailUrl = $base_url . $VideofilePathWithoutExt . $VideoPathExtension;
				}
				echo '
                                    <div class="nonePoint" id="video' . $VideofileUploadID . '">
                                        <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls preload="none" onended="videoEnded()">
                                            <source src="' . $VideofilePathUrl . '" type="video/mp4">
                                            Your browser does not support HTML5 video.
                                        </video>
                                    </div>
                                    ';
			}
		}
            }
        echo '<div class="' . trim($container . ' ' . $fullSingleImage) . '" id="lightgallery' . $userPostID . '">'; 
        foreach ($explodeFiles as $dataFile) {
        	$fileData = $iN->iN_GetUploadedFileDetails($dataFile);
        	if ($fileData) {
        		$fileUploadID = $fileData['upload_id'] ?? null;
        		$fileExtension = $fileData['uploaded_file_ext'] ?? null;
        		$filePath = $fileData['uploaded_file_path'] ?? null;
        		$filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? null;
        		if ($filePathTumbnail) {
        			$imageTumbnail = $filePathTumbnail;
        		} else {
        			$imageTumbnail = $filePath;
        		}
        		if ($userPostWhoCanSee != '1') {
        			if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
        				$filePath = $fileData['uploaded_x_file_path'];
        			} else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
        				if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
        					$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        				} else {
        					$filePath = $fileData['uploaded_file_path'] ?? NULL;
        				}
        			} else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
        				$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        			} else {
        				if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['uploaded_file_path'] ?? NULL;
        				} else {
        					if ($getFriendStatusBetweenTwoUser == 'subscriber' && $userPostWhoCanSee == '3') {
        						$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					} else {
        						if ($getFriendStatusBetweenTwoUser == 'flwr' || $getFriendStatusBetweenTwoUser == 'subscriber') {
        							$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        						} else {
        							$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        						}
        					}
        				}
        			}
        		} else {
        			$filePath = $fileData['uploaded_file_path'];
        		}
        		$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
			if (function_exists('storage_public_url')) {
				$filePathUrl = storage_public_url($filePathTumbnail ? $imageTumbnail : $filePath);
			} else {
				$filePathUrl = $base_url . ($filePathTumbnail ? $imageTumbnail : $filePath);
			}
        
        		$videoPlaybutton = '';
        		if ($fileExtension == 'mp4') {
        			$videoPlaybutton = '<div class="playbutton">' . $iN->iN_SelectedMenuIcon('55') . '</div>';
        			$PathExtension = '.jpg';
        			if ($s3Status == 1) {
        				if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
        				} else if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
        				} else {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
        				}
				if (function_exists('storage_public_url')) {
					$filePathUrl = storage_public_url($filePath);
					$filePathTumbnailUrl = storage_public_url($filePath);
				} else {
					$filePathUrl = $base_url . $filePath;
					$filePathTumbnailUrl = $base_url . $filePath;
				}
        			}else if($WasStatus == 1){
        				if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
        				} else if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
        				} else {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
        				}
				if (function_exists('storage_public_url')) {
					$filePathUrl = storage_public_url($filePath);
					$filePathTumbnailUrl = storage_public_url($filePath);
				} else {
					$filePathUrl = $base_url . $filePath;
					$filePathTumbnailUrl = $base_url . $filePath;
				}
        			} else if ($digitalOceanStatus == '1') {
        				if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
        					$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        				} else if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        				} else {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        				}
				if (function_exists('storage_public_url')) {
					$filePathUrl = storage_public_url($filePath);
					$filePathTumbnailUrl = storage_public_url($filePath);
				} else {
					$filePathUrl = $base_url . $filePath;
					$filePathTumbnailUrl = $base_url . $filePath;
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
        			$fileisVideo = 'data-poster="' . $filePathUrl . '" data-html="#video' . $fileUploadID . '"';
        		} else {
			/* Use unified storage URLs for images */
			if (function_exists('storage_public_url')) {
				$filePathUrl = storage_public_url($filePath);
				$filePathTumbnailUrl = storage_public_url($fileData['uploaded_file_path']);
			} else {
				$filePathUrl = $base_url . $filePath;
				$filePathTumbnailUrl = $base_url . $fileData['uploaded_file_path'];
			}
        			if (($userPostWhoCanSee == '3' || $userPostWhoCanSee == '4' || $userPostWhoCanSee == '2') && $getFriendStatusBetweenTwoUser != 'me' && $checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'flwr') {
				if (function_exists('storage_public_url')) {
					$filePathTumbnailUrl = storage_public_url($getFriendStatusBetweenTwoUser == 'subscriber' ? ($fileData['uploaded_file_path'] ?? '') : ($fileData['uploaded_x_file_path'] ?? ''));
				} else {
					$filePathTumbnailUrl = $base_url . ($getFriendStatusBetweenTwoUser == 'subscriber' ? ($fileData['uploaded_file_path'] ?? '') : ($fileData['uploaded_x_file_path'] ?? ''));
				}
        				/**/
        			} else {
				if (function_exists('storage_public_url')) {
					$filePathTumbnailUrl = storage_public_url($fileData['uploaded_file_path'] ?? '');
				} else {
					$filePathTumbnailUrl = $base_url . ($fileData['uploaded_file_path'] ?? '');
				}
        			}
        			$fileisVideo = 'data-src="' . $filePathTumbnailUrl . '"';
        		}
        		?>
        		<?php if($fileExtension != 'mp3'){?>
                    <div class="i_post_image_swip_wrapper" data-bg="<?php echo iN_HelpSecure($filePathUrl); ?>" <?php echo html_entity_decode($fileisVideo); ?>>
                        <?php echo html_entity_decode($videoPlaybutton); ?>
                        <img class="i_p_image" src="<?php echo iN_HelpSecure($filePathUrl); ?>">
                    </div>
        		<?php }?>
                    <?php }
        }
        echo '</div>';
        ?> 
    </div>
    <!--POST IMAGES-->
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
            <?php if (!empty($coverUrl) && empty($userPostFile)) { ?>
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
    <?php }
    } ?>
	<?php
echo '<div class="myaudio">';
foreach ($explodeFiles as $dataFile) {
	$fileAudioData = $iN->iN_GetUploadedMp3FileDetails($dataFile);
	if($fileAudioData){

		$fileUploadID = $fileAudioData['upload_id'] ?? null;
		$fileExtension = $fileAudioData['uploaded_file_ext'] ?? null;
		$filePath = $fileAudioData['uploaded_file_path'] ?? null;
		$filePathTumbnail = $fileAudioData['upload_tumbnail_file_path'] ?? null;

		if ($userPostWhoCanSee != '1') {
			if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
				$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
			} else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
				if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
					$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
				} else {
					$filePath = $fileAudioData['uploaded_file_path'] ?? null;
				}
			} else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
				$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
			} else {
				if ($getFriendStatusBetweenTwoUser == 'me') {
					$filePath = $fileAudioData['uploaded_file_path'] ?? null;
				} else {
					if ($getFriendStatusBetweenTwoUser == 'subscriber' && $userPostWhoCanSee == '3') {
						$filePath = $fileAudioData['upload_tumbnail_file_path'] ?? null;
					} else {
						if ($getFriendStatusBetweenTwoUser == 'flwr' || $getFriendStatusBetweenTwoUser == 'subscriber') {
							$filePath = $fileAudioData['upload_tumbnail_file_path'] ?? null;
						} else {
							$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
						}
					}
				}
			}
		} else {
			$filePath = $fileAudioData['uploaded_file_path'] ?? null;
		}
		if($fileExtension == 'mp3'){
			/*mp3 started*/
			if (function_exists('storage_public_url')) {
				$filePathUrl = storage_public_url($filePath);
				$filePathTumbnailUrl = storage_public_url($fileAudioData['uploaded_file_path'] ?? '');
			} else {
				$filePathUrl = $base_url . $filePath;
				$filePathTumbnailUrl = $base_url . ($fileAudioData['uploaded_file_path'] ?? '');
			}
			$audShowType = '<audio  crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
			if (($userPostWhoCanSee == '3' || $userPostWhoCanSee == '4' || $userPostWhoCanSee == '2') && $getFriendStatusBetweenTwoUser != 'me' && $checkUserPurchasedThisPost == '0') {
				if (function_exists('storage_public_url')) {
					$pathChoice = ($getFriendStatusBetweenTwoUser == 'subscriber') ? ($fileAudioData['uploaded_file_path'] ?? '') : ($fileAudioData['uploaded_x_file_path'] ?? '');
					$filePathTumbnailUrl = storage_public_url($pathChoice);
					$audShowType = ($getFriendStatusBetweenTwoUser == 'subscriber')
						? '<audio  crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>'
						: '<img class="i_p_image plus_opacity" src="'.$filePathTumbnailUrl.'">';
				} else {
					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
						$filePathTumbnailUrl = $base_url . ($fileAudioData['uploaded_file_path'] ?? '');
						$audShowType = '<audio crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
					} else {
						$filePathTumbnailUrl = $base_url . ($fileAudioData['uploaded_x_file_path'] ?? '');
						$audShowType = '<img class="i_p_image plus_opacity" src="'.$filePathTumbnailUrl.'">';
					}
				}
				/**/
			} else {
				if (function_exists('storage_public_url')) {
					$filePathTumbnailUrl = storage_public_url($fileAudioData['uploaded_file_path'] ?? '');
				} else {
					$filePathTumbnailUrl = $base_url . ($fileAudioData['uploaded_file_path'] ?? '');
				}
			}
			$fileisVideo = 'data-src="' . $filePathTumbnailUrl . '"';
			/*mp3 finished*/
		}?>
                <?php if($fileExtension == 'mp3'){?>
					<div class="i_post_image_swip_wrappera" <?php echo html_entity_decode($fileisVideo); ?>>
						<div id="play_po_<?php echo iN_HelpSecure($fileUploadID);?>" class="green-audio-player">
							<?php echo html_entity_decode($audShowType);?>
						</div>
				    </div>
				<?php }?>
	<?php }
}
echo '</div>';
?>
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
            <div class="i_post_item_btn transition in_comment <?php echo iN_HelpSecure($loginFormClass); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('20')); ?></div>
        </div>
        <?php if ($allowReShare) { ?>
        <div class="i_post_footer_item">
           <div class="i_post_item_btn transition in_share <?php echo iN_HelpSecure($loginFormClass); ?>"  id="share_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('19')); ?></div>
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
	    <?php
	    $hasBoostRecord = $hasBoostRecord ?? false;
	    $checkPostBoosted = $checkPostBoosted ?? false;
	    if(isset($userID) && $hasBoostRecord && ((int)$userPostOwnerID === (int)$userID) && !empty($boostID)){
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
        <div class="i_post_comments_box<?php echo $logedIn == 0 ? ' nonePoint' : ''; ?>">
            <!--USER COMMENTS-->
            <div class="i_user_comments" name="i_user_comments_<?php echo iN_HelpSecure($userPostID); ?>" id="i_user_comments_<?php echo iN_HelpSecure($userPostID); ?>">
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
                $commentUserFrame = $comment['user_frame'] ?? null;
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
                if ($logedIn != 0) {
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

<?php /* ====================================================================
   FB-style custom photo viewer — injected ONCE per page (guarded).
   Overrides lightGallery for post images only; videos (data-html) are left
   to existing behavior. Works for dynamically loaded posts via delegation.
   ==================================================================== */ ?>
<?php if (!defined('DZ_FB_VIEWER_LOADED')): define('DZ_FB_VIEWER_LOADED', true); ?>
<style>
/* ---------- Dizzy custom photo viewer ---------- */
.dz-viewer{
    position:fixed;inset:0;z-index:99999;
    display:none;
    background:rgba(5,5,5,.96);
    opacity:0;
    transition:opacity .22s ease;
    -webkit-user-select:none;user-select:none;
    color:#fff;
    font-family:'Inter','Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif;
}
.dz-viewer.is-open{display:flex;opacity:1;}
.dz-viewer.is-closing{opacity:0;}

/* Stage holds the image */
.dz-viewer__stage{
    position:absolute;inset:0;
    display:flex;align-items:center;justify-content:center;
    padding:60px 72px;
    box-sizing:border-box;
    overflow:hidden;
}
.dz-viewer__img-wrap{
    max-width:100%;max-height:100%;
    display:flex;align-items:center;justify-content:center;
    position:relative;
}
.dz-viewer__img{
    max-width:100%;max-height:100%;
    width:auto;height:auto;
    object-fit:contain;
    display:block;
    opacity:0;transform:scale(.97);
    transition:opacity .25s ease, transform .25s ease;
    border-radius:4px;
    box-shadow:0 10px 40px rgba(0,0,0,.5);
}
.dz-viewer__img.is-ready{opacity:1;transform:scale(1);}

/* Video inside viewer */
.dz-viewer__video{
    max-width:100%;max-height:100%;
    width:auto;height:auto;
    display:block;outline:none;background:#000;
}

/* Loading spinner */
.dz-viewer__spinner{
    position:absolute;top:50%;left:50%;
    width:38px;height:38px;margin:-19px 0 0 -19px;
    border:2.5px solid rgba(255,255,255,.15);
    border-top-color:#fff;
    border-radius:50%;
    animation:dzSpin .8s linear infinite;
    pointer-events:none;
    opacity:0;transition:opacity .2s ease;
}
.dz-viewer.is-loading .dz-viewer__spinner{opacity:1;}
@keyframes dzSpin{to{transform:rotate(360deg);}}

/* Close button */
.dz-viewer__close{
    position:absolute;top:16px;right:16px;
    width:42px;height:42px;
    display:flex;align-items:center;justify-content:center;
    border:0;border-radius:50%;
    background:rgba(255,255,255,.08);
    color:#fff;cursor:pointer;
    transition:background .2s ease, transform .2s ease;
    z-index:2;
}
.dz-viewer__close:hover{background:rgba(255,255,255,.18);transform:scale(1.05);}
.dz-viewer__close svg{width:18px;height:18px;}

/* Counter */
.dz-viewer__counter{
    position:absolute;top:22px;left:22px;
    font-size:13.5px;color:rgba(255,255,255,.75);
    font-weight:500;letter-spacing:.3px;
    z-index:2;
}

/* Arrows */
.dz-viewer__nav{
    position:absolute;top:50%;transform:translateY(-50%);
    width:48px;height:48px;
    display:flex;align-items:center;justify-content:center;
    border:0;border-radius:50%;
    background:rgba(255,255,255,.08);
    color:#fff;cursor:pointer;
    transition:background .2s ease, transform .2s ease, opacity .2s ease;
    z-index:2;
}
.dz-viewer__nav:hover{background:rgba(255,255,255,.2);}
.dz-viewer__nav:active{transform:translateY(-50%) scale(.95);}
.dz-viewer__nav--prev{left:18px;}
.dz-viewer__nav--next{right:18px;}
.dz-viewer__nav[hidden],
.dz-viewer__nav[disabled]{display:none;}
.dz-viewer__nav svg{width:20px;height:20px;}

/* Responsive */
@media (max-width:640px){
    .dz-viewer__stage{padding:48px 8px;}
    .dz-viewer__nav{width:40px;height:40px;background:rgba(255,255,255,.06);}
    .dz-viewer__nav--prev{left:6px;}
    .dz-viewer__nav--next{right:6px;}
    .dz-viewer__close{top:10px;right:10px;width:38px;height:38px;}
    .dz-viewer__counter{top:16px;left:14px;font-size:12.5px;}
}
@media (prefers-reduced-motion: reduce){
    .dz-viewer,.dz-viewer__img{transition:none;}
}

/* Lock body scroll when open */
body.dz-viewer-open{overflow:hidden;}
</style>

<!-- Viewer DOM (single instance) -->
<div class="dz-viewer" id="dzViewer" role="dialog" aria-modal="true" aria-label="Photo viewer">
    <div class="dz-viewer__counter" id="dzViewerCounter"></div>
    <button type="button" class="dz-viewer__close" id="dzViewerClose" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
    <button type="button" class="dz-viewer__nav dz-viewer__nav--prev" id="dzViewerPrev" aria-label="Previous">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <button type="button" class="dz-viewer__nav dz-viewer__nav--next" id="dzViewerNext" aria-label="Next">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </button>
    <div class="dz-viewer__stage" id="dzViewerStage">
        <div class="dz-viewer__spinner"></div>
        <div class="dz-viewer__img-wrap" id="dzViewerContent"></div>
    </div>
</div>

<script>
(function(){
    'use strict';
    if (window.__dzFbViewerLoaded) return; // guard: only init once
    window.__dzFbViewerLoaded = true;

    var viewer   = document.getElementById('dzViewer');
    var stage    = document.getElementById('dzViewerStage');
    var content  = document.getElementById('dzViewerContent');
    var counter  = document.getElementById('dzViewerCounter');
    var btnClose = document.getElementById('dzViewerClose');
    var btnPrev  = document.getElementById('dzViewerPrev');
    var btnNext  = document.getElementById('dzViewerNext');

    // State
    var slides   = [];   // [{src, type}]
    var index    = 0;

    /* ---------- Build a slide array from a gallery element ---------- */
    function buildSlides(gallery, clickedItem){
        var items = Array.prototype.slice.call(
            gallery.querySelectorAll('.i_post_image_swip_wrapper')
        );
        // Dedupe swiper cloned slides
        items = items.filter(function(el){
            return !el.classList.contains('swiper-slide-duplicate');
        });
        // Build slide list (skip video items — they keep existing behavior)
        var list = [];
        var clickedIdx = 0;
        items.forEach(function(el){
            if (el.getAttribute('data-html')) return; // skip videos
            var src = el.getAttribute('data-src')
                   || el.getAttribute('data-bg')
                   || (el.querySelector('img') && el.querySelector('img').src);
            if (!src) return;
            if (el === clickedItem) clickedIdx = list.length;
            list.push({ src: src, type: 'image' });
        });
        return { slides: list, index: clickedIdx };
    }

    /* ---------- Render current slide ---------- */
    function render(){
        content.innerHTML = '';
        var item = slides[index];
        if (!item) return;

        viewer.classList.add('is-loading');

        var img = new Image();
        img.className = 'dz-viewer__img';
        img.alt = '';
        img.onload = function(){
            viewer.classList.remove('is-loading');
            requestAnimationFrame(function(){ img.classList.add('is-ready'); });
        };
        img.onerror = function(){ viewer.classList.remove('is-loading'); };
        img.src = item.src;
        content.appendChild(img);

        // Counter + nav visibility
        if (slides.length > 1){
            counter.textContent = (index + 1) + ' / ' + slides.length;
            counter.style.display = '';
            btnPrev.hidden = false; btnNext.hidden = false;
        } else {
            counter.style.display = 'none';
            btnPrev.hidden = true; btnNext.hidden = true;
        }
    }

    /* ---------- Open / close ---------- */
    function open(list, startIdx){
        if (!list || !list.length) return;
        slides = list;
        index  = Math.max(0, Math.min(startIdx || 0, list.length - 1));
        viewer.classList.add('is-open');
        document.body.classList.add('dz-viewer-open');
        render();
    }
    function close(){
        viewer.classList.add('is-closing');
        setTimeout(function(){
            viewer.classList.remove('is-open','is-closing','is-loading');
            document.body.classList.remove('dz-viewer-open');
            content.innerHTML = '';
            slides = []; index = 0;
        }, 200);
    }
    function prev(){ if (slides.length > 1){ index = (index - 1 + slides.length) % slides.length; render(); } }
    function next(){ if (slides.length > 1){ index = (index + 1) % slides.length; render(); } }

    /* ---------- Event wiring ---------- */
    btnClose.addEventListener('click', close);
    btnPrev.addEventListener('click', prev);
    btnNext.addEventListener('click', next);

    // Click outside image closes
    stage.addEventListener('click', function(e){
        if (e.target === stage || e.target === content) close();
    });

    // Keyboard
    document.addEventListener('keydown', function(e){
        if (!viewer.classList.contains('is-open')) return;
        if (e.key === 'Escape')      { e.preventDefault(); close(); }
        else if (e.key === 'ArrowLeft')  { e.preventDefault(); prev(); }
        else if (e.key === 'ArrowRight') { e.preventDefault(); next(); }
    });

    // Touch swipe
    var tStartX = 0, tStartY = 0, tMoved = false;
    stage.addEventListener('touchstart', function(e){
        if (!e.touches.length) return;
        tStartX = e.touches[0].clientX; tStartY = e.touches[0].clientY; tMoved = false;
    }, { passive:true });
    stage.addEventListener('touchmove', function(e){
        if (!e.touches.length) return;
        if (Math.abs(e.touches[0].clientX - tStartX) > 10 ||
            Math.abs(e.touches[0].clientY - tStartY) > 10) tMoved = true;
    }, { passive:true });
    stage.addEventListener('touchend', function(e){
        if (!tMoved || !e.changedTouches.length) return;
        var dx = e.changedTouches[0].clientX - tStartX;
        var dy = e.changedTouches[0].clientY - tStartY;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)){
            if (dx < 0) next(); else prev();
        }
    }, { passive:true });

    /* ---------- Capture-phase click delegation on post image items.
       Runs BEFORE lightGallery's own handler, stops propagation and opens
       our custom viewer. Video items (data-html) are ignored so existing
       video playback keeps working. ---------- */
    document.addEventListener('click', function(e){
        var item = e.target.closest && e.target.closest('.i_post_image_swip_wrapper');
        if (!item) return;
        // Only in post galleries
        var gallery = item.closest('[id^="lightgallery"]');
        if (!gallery) return;
        if (!gallery.closest('.i_post_u_images')) return;
        // Skip the play button / video items — keep original behavior
        if (item.getAttribute('data-html')) return;
        // Skip locked/blurred overlays
        if (item.closest('.only_subs') || item.closest('.i_post_u_images.only_sb')) return;

        var built = buildSlides(gallery, item);
        if (!built.slides.length) return;

        // Stop lightGallery & any other listener
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

        open(built.slides, built.index);
    }, true); // capture = true, runs first
})();
</script>
<?php endif; ?>

