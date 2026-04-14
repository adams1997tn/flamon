<div class="th_middle">
  <div class="pageMiddle myFriednsStories">
    <div class="live_item transition">
      <div class="stories_page_title flex_ tabing_non_justify">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('154'));?>
        <?php echo iN_HelpSecure($LANG['all_friends_stories']);?>
      </div>
    </div>

    <div class="my-stories-wrapper flex_ mystoriesstyle swiper-wrapper" id="story-view" data-padding-top="30"
         data-story-reply-placeholder="<?php echo iN_HelpSecure($LANG['story_reply_placeholder']); ?>"
         data-story-reply-sent="<?php echo iN_HelpSecure($LANG['story_reply_sent']); ?>"
         data-story-reply-failed="<?php echo iN_HelpSecure($LANG['story_reply_failed']); ?>"
         data-story-reply-empty="<?php echo iN_HelpSecure($LANG['story_reply_empty']); ?>"
         data-story-reply-self="<?php echo iN_HelpSecure($LANG['story_reply_self']); ?>"
         data-story-reply-send="<?php echo iN_HelpSecure($LANG['send_message']); ?>"
         data-story-reply-user="<?php echo iN_HelpSecure($userID); ?>"
         data-story-access-followers="<?php echo iN_HelpSecure($LANG['story_access_followers']); ?>"
         data-story-access-subscribers="<?php echo iN_HelpSecure($LANG['story_access_subscribers']); ?>"
         data-story-access-follow-btn="<?php echo iN_HelpSecure($LANG['follow']); ?>"
         data-story-access-subscribe-btn="<?php echo iN_HelpSecure($LANG['story_access_subscribe_btn']); ?>"
         data-story-reaction-set="<?php echo iN_HelpSecure(implode(' ', $iN->iN_GetStoryDefaultReactions())); ?>"
         data-story-audio-mute="<?php echo iN_HelpSecure($LANG['story_audio_mute']); ?>"
         data-story-audio-unmute="<?php echo iN_HelpSecure($LANG['story_audio_unmute']); ?>"
         data-story-audio-play="<?php echo iN_HelpSecure($LANG['story_audio_play']); ?>"
         data-story-audio-pause="<?php echo iN_HelpSecure($LANG['story_audio_pause']); ?>"
         data-story-audio-tip="<?php echo iN_HelpSecure($LANG['story_audio_unmute_tip']); ?>"
         data-story-text-more="<?php echo iN_HelpSecure($LANG['story_text_read_more']); ?>"
         data-story-text-less="<?php echo iN_HelpSecure($LANG['story_text_show_less']); ?>">
      <?php
      $stories = $iN->iN_FriendStoryPostListAll($userID);
      if($stories){
        foreach($stories as $mySData){
          $SotryUploaded = isset($mySData['pics']) ? $mySData['pics'] : NULL;
          $up = explode(",", $SotryUploaded);
          $storySharedOwnerID = $mySData['uid_fk'];
          $storyOwnerUserName = $mySData['i_username'] ?? $iN->iN_GetUserName($storySharedOwnerID);
          $storieOwnerFullName = $iN->iN_UserFullName($storySharedOwnerID);
          $StorySharedUserAvatar = $iN->iN_UserAvatar($storySharedOwnerID, $base_url);
          $lastStorieImage = $iN->iN_GetLastSharedStatus($storySharedOwnerID);
          $lastStoryUrl = null;
          $lastStoryId = null;
          $storyItems = [];
          $hasAccessibleStory = false;
          $displayName = $storieOwnerFullName;

          foreach ($up as $item) {
            $stD = $iN->iN_GetUploadedStoriesDataS($item);
            if (!$stD) {
              continue;
            }
            $final_Image = $stD['uploaded_file_path'];
            $storieText = $stD['text'];
            $storieID = isset($stD['s_id']) ? (int)$stD['s_id'] : null;
            $storieTextStyle = $stD['text_style'] ?? 'not';
            $storyPrivacy = $iN->iN_NormalizeStoryPrivacy($stD['story_privacy'] ?? 'followers');
            $accessInfo = $iN->iN_GetStoryAccessInfo($userID, $stD);
            $canView = !empty($accessInfo['allowed']);
            $accessReason = (string)($accessInfo['reason'] ?? '');
            $storyQuickReplies = $iN->iN_DecodeStoryQuickReplies($stD['story_quick_replies'] ?? '');
            $overlayLink = $stD['story_overlay_link'] ?? '';
            $overlayMention = $stD['story_overlay_mention'] ?? '';
            $overlayStickerUrl = '';
            $overlayStickerId = (int)($stD['story_overlay_sticker_id'] ?? 0);
            $storyAudioUrl = '';
            $storyAudioTitle = '';
            $storyAudioArtist = '';
            $storyAudioId = (int)($stD['story_audio_id'] ?? 0);
            if ($canView && $overlayStickerId > 0) {
              $stickerData = $iN->iN_GetStickerDetailsFromID($overlayStickerId);
              $overlayStickerUrl = $stickerData['sticker_url'] ?? '';
            }
            if ($canView && $storyAudioId > 0) {
              $audioData = $iN->iN_GetStoryAudioById($storyAudioId);
              if ($audioData && (string)($audioData['status'] ?? '0') === '1') {
                $audioPath = $audioData['audio_url'] ?? '';
                if ($audioPath !== '') {
                  if (function_exists('storage_public_url')) {
                    $storyAudioUrl = storage_public_url($audioPath);
                  } else {
                    $storyAudioUrl = $base_url . $audioPath;
                  }
                }
                $storyAudioTitle = $audioData['title'] ?? '';
                $storyAudioArtist = $audioData['artist'] ?? '';
              }
            }
            if ($canView) {
              $hasAccessibleStory = true;
            } else {
              $storieText = '';
              $storieTextStyle = 'not';
              $overlayLink = '';
              $overlayMention = '';
              $overlayStickerUrl = '';
              $storyAudioUrl = '';
              $storyAudioTitle = '';
              $storyAudioArtist = '';
              $storyQuickReplies = [];
            }
            $exts = pathinfo($final_Image, PATHINFO_EXTENSION);

            if (function_exists('storage_public_url')) {
              $final_Image = storage_public_url($final_Image);
            } else {
              $final_Image = $base_url . $final_Image;
            }
            if (!$canView) {
              $final_Image = $base_url . 'uploads/web.png';
              $exts = 'png';
            }
            if (in_array($exts, ['mp4', 'MP4'], true)) {
              $storyAudioUrl = '';
              $storyAudioTitle = '';
              $storyAudioArtist = '';
            }

            if ($storieID !== null) {
              if ($lastStoryId === null || $storieID > $lastStoryId) {
                $lastStoryId = $storieID;
              }
            }

            $storyItems[] = [
              'id' => $storieID,
              'time' => $mySData['created'],
              'text' => $storieText,
              'style' => $storieTextStyle,
              'ext' => $exts,
              'src' => $final_Image,
              'privacy' => $storyPrivacy,
              'can_view' => $canView ? '1' : '0',
              'access_reason' => $accessReason,
              'overlay_link' => $overlayLink,
              'overlay_mention' => $overlayMention,
              'overlay_sticker' => $overlayStickerUrl,
              'audio_url' => $storyAudioUrl,
              'audio_title' => $storyAudioTitle,
              'audio_artist' => $storyAudioArtist,
              'quick_replies' => $storyQuickReplies
            ];
          }

          if($lastStorieImage){
            if (function_exists('storage_public_url')) {
              $lastStoryUrl = storage_public_url($lastStorieImage);
            } else {
              $lastStoryUrl = $base_url . $lastStorieImage;
            }
          }
          if (!$lastStoryUrl) {
            $lastStoryUrl = $StorySharedUserAvatar;
          }
          if (!$hasAccessibleStory) {
            $lastStoryUrl = $StorySharedUserAvatar;
          }

          if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($displayName, 'UTF-8') > 8) {
              $displayName = mb_substr($displayName, 0, 8, 'UTF-8') . '...';
            }
          } else {
            if (strlen($displayName) > 8) {
              $displayName = substr($displayName, 0, 8) . '...';
            }
          }

          $StoryCreatedTime = $mySData['created'];
      ?>
      <div class="story-view-item swiper-slide" data-story-id="<?php echo iN_HelpSecure($storySharedOwnerID);?>" data-last-story-id="<?php echo iN_HelpSecure($lastStoryId); ?>" data-background-image="<?php echo $lastStoryUrl;?>" data-profile-image="<?php echo $StorySharedUserAvatar;?>" data-profile-name="<?php echo $storieOwnerFullName;?>" data-profile-username="<?php echo iN_HelpSecure($storyOwnerUserName); ?>" style="--story-bg:url('<?php echo $lastStoryUrl;?>');">
        <div class="story-bubble">
          <div class="story-ring">
            <div class="story-view-pr-avatar" data-avatar="<?php echo $StorySharedUserAvatar;?>"></div>
          </div>
        </div>
        <span class="name truncate"><?php echo iN_HelpSecure($displayName);?></span>
        <ul class="media">
          <?php
          foreach ($storyItems as $itemData) {
            $storieID = $itemData['id'];
            $storieText = $itemData['text'];
            $storieTextStyle = $itemData['style'];
            $exts = $itemData['ext'];
            $final_Image = $itemData['src'];
            $StoryCreatedTime = $itemData['time'];
            $privacy = $itemData['privacy'] ?? 'followers';
            $canView = $itemData['can_view'] ?? '1';
            $accessReason = $itemData['access_reason'] ?? '';
            $overlayLink = $itemData['overlay_link'] ?? '';
            $overlayMention = $itemData['overlay_mention'] ?? '';
            $overlaySticker = $itemData['overlay_sticker'] ?? '';
            $audioUrl = $itemData['audio_url'] ?? '';
            $audioTitle = $itemData['audio_title'] ?? '';
            $audioArtist = $itemData['audio_artist'] ?? '';
            $quickReplies = $itemData['quick_replies'] ?? [];
            $quickRepliesJson = '';
            if (!empty($quickReplies)) {
              $quickRepliesJson = htmlspecialchars(json_encode($quickReplies, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            }
            $overlayLinkAttr = iN_HelpSecure($overlayLink);
            $overlayMentionAttr = iN_HelpSecure($overlayMention);
            $overlayStickerAttr = iN_HelpSecure($overlaySticker);
            $overlayAudioAttr = iN_HelpSecure($audioUrl, FILTER_VALIDATE_URL);
            $audioTitleAttr = iN_HelpSecure($audioTitle);
            $audioArtistAttr = iN_HelpSecure($audioArtist);
            $audioDataAttrs = $overlayAudioAttr !== ''
              ? ' data-overlay-audio="' . $overlayAudioAttr . '" data-audio-title="' . $audioTitleAttr . '" data-audio-artist="' . $audioArtistAttr . '"'
              : '';

            if (in_array($exts, ['mp4', 'MP4'])) {
              echo '<li class="move_' . $storieID . '" data-id="' . $storieID . '" data-sid="' . $storieID . '" data-duration="" data-time="' . $StoryCreatedTime . '">
                      <video src="' . $final_Image . '" id="video_' . $storieID . '" alt="' . $storieText . '" data-id="' . $storieID . '" data-privacy="' . iN_HelpSecure($privacy) . '" data-can-view="' . iN_HelpSecure($canView) . '" data-access-reason="' . iN_HelpSecure($accessReason) . '" data-overlay-link="' . $overlayLinkAttr . '" data-overlay-mention="' . $overlayMentionAttr . '" data-overlay-sticker="' . $overlayStickerAttr . '" data-quick-replies="' . $quickRepliesJson . '" type="video/mp4"></video>
                    </li>';
            } else {
              echo '<li data-duration="7" data-id="' . $storieID . '" data-sid="' . $storieID . '" data-time="' . $StoryCreatedTime . '">
                      <img src="' . $final_Image . '" data-id="' . $storieID . '" data-ts="' . $storieTextStyle . '" data-privacy="' . iN_HelpSecure($privacy) . '" data-can-view="' . iN_HelpSecure($canView) . '" data-access-reason="' . iN_HelpSecure($accessReason) . '" data-overlay-link="' . $overlayLinkAttr . '" data-overlay-mention="' . $overlayMentionAttr . '" data-overlay-sticker="' . $overlayStickerAttr . '"' . $audioDataAttrs . ' data-quick-replies="' . $quickRepliesJson . '" alt="' . $storieText . '">
                    </li>';
            }
          }
          ?>
        </ul>
      </div>
      <?php }
      } else {
        echo '<div class="noPost" data-width="100%">'
          . '<div class="noPostIcon">' . $iN->iN_SelectedMenuIcon('54') . '</div>'
          . '<div class="noPostNote">' . $LANG['no_story_to_show'] . '</div>'
          . '</div>';
      }
      ?>
    </div>
  </div>
</div>
