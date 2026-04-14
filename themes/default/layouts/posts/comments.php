<?php
$isReply = !empty($isReply);
$commentParentId = isset($commentParentId) ? (int)$commentParentId : 0;
$replyParentUserName = isset($replyParentUserName) ? (string)$replyParentUserName : '';
$replyParentUserFullName = isset($replyParentUserFullName) ? (string)$replyParentUserFullName : '';
$replyLabel = '';
$replyTargetName = $replyParentUserFullName !== '' ? $replyParentUserFullName : $replyParentUserName;
if ($isReply && !empty($Usercomment)) {
    if (preg_match('/^\\s*@([A-Za-z0-9_\\.]+)\\b/', (string)$Usercomment, $mentionMatch)) {
        $replyTargetName = '@' . $mentionMatch[1];
    }
}
if ($isReply && $replyTargetName !== '') {
    $replyTemplate = $LANG['replying_to'] ?? 'Replying to {user}';
    $replyLabel = str_replace('{user}', $replyTargetName, $replyTemplate);
}
$commentReplyCount = isset($commentReplyCount) ? (int)$commentReplyCount : 0;
$commentReplies = isset($commentReplies) ? $commentReplies : null;
$renderReplies = !$isReply;
$commentFile = isset($commentFile) ? $commentFile : null;
$stickerUrl = isset($stickerUrl) ? $stickerUrl : null;
$gifUrl = isset($gifUrl) ? $gifUrl : null;
$commentLinkUrl = isset($commentLinkUrl) ? trim((string)$commentLinkUrl) : '';
$commentLinkDomain = isset($commentLinkDomain) ? trim((string)$commentLinkDomain) : '';
$commentLinkTitle = isset($commentLinkTitle) ? trim((string)$commentLinkTitle) : '';
$commentLinkDescription = isset($commentLinkDescription) ? trim((string)$commentLinkDescription) : '';
$commentLinkImage = isset($commentLinkImage) ? trim((string)$commentLinkImage) : '';
?>
<!--COMMENT-->
<div class="i_u_comment_body dlCm<?php echo iN_HelpSecure($commentID);?><?php echo $isReply ? ' i_u_comment_body_reply' : ''; ?>"
     id="<?php echo iN_HelpSecure($commentID);?>"
     data-comment-id="<?php echo iN_HelpSecure($commentID);?>"
     data-parent-id="<?php echo iN_HelpSecure($commentParentId);?>"
     data-post-id="<?php echo iN_HelpSecure($userPostID);?>"
     role="article"
     aria-labelledby="i_u_c_<?php echo iN_HelpSecure($commentID);?>">
    <?php
    $commentStoryFlag = $iN->iN_UserHasPublicStory($commentedUserID) ? '1' : '0';
    $commentAnyStoryFlag = $iN->iN_UserHasAnyStory($commentedUserID) ? '1' : '0';
    $commentStoryClass = $commentStoryFlag === '1' ? ' has-story' : '';
    ?>
    <div class="i_post_user_commented_avatar_out">
        <?php if(isset($commentUserFrame)){ ?>
            <div class="frame_out_container_comment" role="presentation">
                <div class="frame_container_comment">
                    <img src="<?php echo $base_url.$commentUserFrame;?>" alt=""/>
                </div>
            </div>
        <?php }?>
        <div class="i_post_user_commented_avatar js-story-avatar<?php echo $commentStoryClass; ?>" data-story-user-id="<?php echo iN_HelpSecure($commentedUserID); ?>" data-story-username="<?php echo iN_HelpSecure($commentedUserName); ?>" data-has-story="<?php echo iN_HelpSecure($commentStoryFlag); ?>" data-has-any-story="<?php echo iN_HelpSecure($commentAnyStoryFlag); ?>">
            <img src="<?php echo iN_HelpSecure($commentedUserAvatar);?>" alt="<?php echo iN_HelpSecure($commentedUserFullName);?>" />
        </div>
    </div>
    <div class="i_user_comment_header">
        <div class="i_user_commented_user_infos">
            <a href="<?php echo iN_HelpSecure($base_url).$commentedUserName;?>">
                <?php echo iN_HelpSecure($commentedUserFullName);?>
                <?php echo html_entity_decode($cpublisherGender);?>
                <?php echo html_entity_decode($cuserVerifiedStatus);?>
                <?php echo html_entity_decode($cUType);?>
            </a>
        </div>
        <?php if ($replyLabel !== '') { ?>
            <div class="i_comment_replying_to"><?php echo iN_HelpSecure($replyLabel); ?></div>
        <?php } ?>
        <?php if(!empty($Usercomment)){?>
        <div class="i_user_comment_text" id="i_u_c_<?php echo iN_HelpSecure($commentID);?>" aria-label="Comment content">
        <?php
            $cleanedComment = $iN->sanitize_output_preserve_linebreaks($Usercomment, $base_url);
            $highlightedComment = $urlHighlight->highlightUrls($cleanedComment);
            $highlightedComment = $iN->iN_TruncateLinkText($highlightedComment, 50);
            echo '<div class="i_comment_text_content js-text-truncate" data-max-lines="4">' .
                nl2br($highlightedComment, false) .
                '</div>';
            $commentHasMedia = !empty($commentFile) || !empty($stickerUrl) || !empty($gifUrl);
            if (!$commentHasMedia) {
                if ($commentLinkUrl !== '') {
                    $linkPreviewUrl = $commentLinkUrl;
                    $linkPreviewDomain = $commentLinkDomain;
                    $linkPreviewTitle = $commentLinkTitle;
                    $linkPreviewDescription = $commentLinkDescription;
                    $linkPreviewImage = $commentLinkImage;
                    include __DIR__ . '/linkPreview.php';
                } else {
                    $firstUrl = $iN->iN_ExtractFirstUrlFromText((string)$Usercomment);
                    if ($firstUrl !== null) {
                        $em = new Url_Expand($firstUrl);
                        $code = '';
                        if ($em->get_site() != '') {
                            $code = $em->get_iframe();
                            if ($code == '') {
                                $code = $em->get_embed();
                            }
                        }
                        if ($code != '') {
                            echo $code;
                        } else {
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
        <?php } ?>
        <?php echo html_entity_decode($stickerComment); echo html_entity_decode($gifComment); ?>
        <div class="i_comment_like_time" aria-label="Comment actions">
            <div class="i_comment_reply rplyComment"
                 id="<?php echo iN_HelpSecure($userPostID);?>"
                 data-post="<?php echo iN_HelpSecure($userPostID);?>"
                 data-comment="<?php echo iN_HelpSecure($commentID);?>"
                 data-who="<?php echo iN_HelpSecure($commentedUserName);?>"
                 data-fullname="<?php echo iN_HelpSecure($commentedUserFullName);?>"
                 role="button"
                 tabindex="0">
                <?php echo iN_HelpSecure($LANG['reply'] ?? 'Reply'); ?>
            </div>
            <div class="i_comment_like_btn">
                <div class="i_comment_item_btn transition c_in_l_<?php echo iN_HelpSecure($commentID);?> <?php echo html_entity_decode($commentLikeBtnClass);?>"
                     id="com_<?php echo iN_HelpSecure($commentID);?>"
                     data-id="<?php echo iN_HelpSecure($commentID);?>"
                     data-p="<?php echo iN_HelpSecure($userPostID);?>"
                     role="button"
                     aria-pressed="<?php echo $commentLikeBtnClass ? 'true' : 'false'; ?>"
                     tabindex="0">
                     <?php echo html_entity_decode($commentLikeIcon);?>
                </div>
                <div class="i_comment_like_sum" id="t_c_<?php echo iN_HelpSecure($commentID);?>">
                    <?php echo iN_HelpSecure($iN->iN_TotalCommentLiked($commentID));?>
                </div>
            </div>
            <div class="i_comment_time" aria-label="Comment time">
                <?php echo TimeAgo::ago($corTime , date('Y-m-d H:i:s'));?>
            </div>

            <?php
            $canModerateComment = ($logedIn != 0 && isset($communityModCanManageComments) && $communityModCanManageComments && isset($page) && $page === 'community');
            $canDeleteComment = ($logedIn != 0 && ($commentedUserID == $userID || $userType == '2' || $canModerateComment));
            ?>
            <!-- Comment Menu -->
            <div class="i_comment_call_popup openComMenu" id="<?php echo iN_HelpSecure($commentID);?>" role="button" tabindex="0" aria-label="Open comment menu">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16'));?>
                <div class="i_comment_menu_container comMBox comMBox<?php echo iN_HelpSecure($commentID);?>">
                    <div class="i_comment_menu_wrapper">
                        <?php if ($canDeleteComment) { ?>
                        <div class="i_post_menu_item_out delCm transition"
                             id="<?php echo iN_HelpSecure($commentID);?>"
                             data-id="<?php echo iN_HelpSecure($userPostID);?>"
                             role="button"
                             tabindex="0">
                             <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28'));?> <?php echo iN_HelpSecure($LANG['delete_comment']);?>
                        </div>
                        <?php } ?>
                        <?php if($logedIn != 0 && $commentedUserID == $userID){ ?>
                        <div class="i_post_menu_item_out cced transition"
                             id="<?php echo iN_HelpSecure($commentID);?>"
                             data-id="<?php echo iN_HelpSecure($userPostID);?>"
                             role="button"
                             tabindex="0">
                             <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27'));?> <?php echo iN_HelpSecure($LANG['edit_comment']);?>
                        </div>
                        <?php }?>
                        <?php if($logedIn != 0 && $commentedUserID != $userID){
                            $checkCommentReportedBeforeByUserID = $iN->iN_CheckCommentReportedBefore($userID, $commentID);
                            $reportText = $checkCommentReportedBeforeByUserID == '1' ? $LANG['unreport'] : $LANG['report_comment'];
                        ?>
                        <div class="i_post_menu_item_out ccp transition ccp<?php echo iN_HelpSecure($commentID);?>"
                             id="<?php echo iN_HelpSecure($commentID);?>"
                             data-id="<?php echo iN_HelpSecure($userPostID);?>"
                             role="button"
                             tabindex="0">
                             <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32'));?> <?php echo iN_HelpSecure($reportText);?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <!--/Comment Menu-->
        </div>
        <?php if ($renderReplies) {
        $viewTemplate = $LANG['view_replies'] ?? 'View replies ({count})';
        $viewText = str_replace('{count}', (string)$commentReplyCount, $viewTemplate);
        $hideText = $LANG['hide_replies'] ?? 'Hide replies';
        $toggleStyle = $commentReplyCount > 0 ? '' : 'display: none;';
    ?>
    <div class="i_comment_replies_toggle toggleReplies"
         data-id="<?php echo iN_HelpSecure($commentID);?>"
         data-count="<?php echo iN_HelpSecure($commentReplyCount);?>"
         data-open-template="<?php echo iN_HelpSecure($viewTemplate);?>"
         data-open-text="<?php echo iN_HelpSecure($viewText);?>"
         data-close-text="<?php echo iN_HelpSecure($hideText);?>"
         role="button"
         tabindex="0"
         style="<?php echo iN_HelpSecure($toggleStyle); ?>">
        <?php echo iN_HelpSecure($viewText); ?>
    </div>
    <div class="i_comment_replies" id="comment_replies_<?php echo iN_HelpSecure($commentID);?>" style="display: none;">
        <?php
        if (!empty($commentReplies)) {
            $parentCommentID = $commentID;
            foreach ($commentReplies as $reply) {
                $commentID = $reply['com_id'] ?? null;
                $commentedUserID = $reply['comment_uid_fk'] ?? null;
                $Usercomment = $reply['comment'] ?? null;
                $commentTime = $reply['comment_time'] ?? null;
                $corTime = date('Y-m-d H:i:s', $commentTime);
                $commentFile = $reply['comment_file'] ?? null;
                $stickerUrl = $reply['sticker_url'] ?? null;
                $gifUrl = $reply['gif_url'] ?? null;
                $commentedUserIDFk = $reply['iuid'] ?? null;
                $commentedUserName = $reply['i_username'] ?? null;
                $commentedUserFullName = $reply['i_user_fullname'] ?? null;
                $commentUserFrame = $reply['user_frame'] ?? null;
                if ($fullnameorusername == 'no') {
                    $commentedUserFullName = $commentedUserName;
                }
                $checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
                $cUType = '';
                if ($checkUserIsCreator) {
                    $cUType = '<div class="i_plus_public" id="ipublic_' . $commentedUserID . '">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
                }
                $commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
                $commentedUserGender = $reply['user_gender'] ?? null;
                if ($commentedUserGender == 'male') {
                    $cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
                } else if ($commentedUserGender == 'female') {
                    $cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
                } else if ($commentedUserGender == 'couple') {
                    $cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
                }
                $commentedUserLastLogin = $reply['last_login_time'] ?? null;
                $commentedUserVerifyStatus = $reply['user_verified_status'] ?? null;
                $cuserVerifiedStatus = '';
                if ($commentedUserVerifyStatus == '1') {
                    $cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
                }
                $commentParentId = (int)($reply['parent_comment_id'] ?? 0);
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
                $commentLinkUrl = $reply['link_url'] ?? '';
                $commentLinkDomain = $reply['link_domain'] ?? '';
                $commentLinkTitle = $reply['link_title'] ?? '';
                $commentLinkDescription = $reply['link_description'] ?? '';
                $commentLinkImage = $reply['link_image'] ?? '';
                $isReply = true;
                $replyParentUserName = $reply['parent_username'] ?? '';
                $replyParentUserFullName = $reply['parent_fullname'] ?? '';
                if ($fullnameorusername == 'no') {
                    $replyParentUserFullName = $replyParentUserName;
                } elseif ($replyParentUserFullName === '') {
                    $replyParentUserFullName = $replyParentUserName;
                }
                $commentReplies = null;
                $commentReplyCount = 0;
                include __DIR__ . "/comments.php";
            }
            $commentID = $parentCommentID;
        }
        ?>
    </div>
    <?php } ?>
    </div>
</div>
<!--/COMMENT-->
