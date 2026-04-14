<?php
$highlightList = $iN->iN_GetUserHighlights($p_profileID);
$isHighlightOwner = ($logedIn == 1 && (int)$userID === (int)$p_profileID);
$profileAvatar = $p_profileAvatar ?? ($base_url . 'uploads/avatars/no_gender.png');
$profileFullName = $p_userfullname ?? '';
$profileUserName = $p_username ?? '';

if (!$highlightList && !$isHighlightOwner) {
    return;
}
?>
<div class="profile_highlights">
    <div class="profile_highlights_header">
        <div class="profile_highlights_title flex_ tabing">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('154')); ?>
            <?php echo iN_HelpSecure($LANG['highlights']); ?>
        </div>
        <?php if ($isHighlightOwner) { ?>
            <button type="button" class="profile_highlights_add highlight-add">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('153')); ?>
                <span><?php echo iN_HelpSecure($LANG['highlight_add']); ?></span>
            </button>
        <?php } ?>
    </div>
    <div class="profile_highlights_list my-stories-wrapper highlight-stories"
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
        <?php if (!$highlightList) { ?>
            <div class="profile_highlights_empty">
                <?php echo iN_HelpSecure($LANG['highlight_empty']); ?>
            </div>
        <?php } ?>
        <?php
        if ($highlightList) {
            foreach ($highlightList as $highlightData) {
                $highlightId = (int)($highlightData['id'] ?? 0);
                $highlightTitle = $highlightData['title'] ?? '';
                if ($highlightId <= 0) {
                    continue;
                }
                $highlightStories = $iN->iN_GetHighlightStories($highlightId);
                if (empty($highlightStories)) {
                    continue;
                }
                $customCoverPath = (string)($highlightData['cover_image'] ?? '');
                $customCoverUrl = '';
                if ($customCoverPath !== '') {
                    if (filter_var($customCoverPath, FILTER_VALIDATE_URL)) {
                        $customCoverUrl = $customCoverPath;
                    } elseif (function_exists('storage_public_url')) {
                        $customCoverUrl = storage_public_url($customCoverPath);
                    } else {
                        $customCoverUrl = $base_url . $customCoverPath;
                    }
                }
                $coverStoryId = (int)($highlightData['cover_story_id'] ?? 0);
                $coverUrl = $customCoverUrl;
                $lastStoryId = 0;
                $storyItems = [];
                $hasAccessibleStory = false;

                foreach ($highlightStories as $stD) {
                    $storieID = isset($stD['s_id']) ? (int)$stD['s_id'] : 0;
                    $storieText = $stD['text'] ?? '';
                    $storieTextStyle = $stD['text_style'] ?? 'not';
                    $finalImage = $stD['uploaded_file_path'] ?? '';
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

                    if ($storieID > $lastStoryId) {
                        $lastStoryId = $storieID;
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

                    $exts = $finalImage ? pathinfo($finalImage, PATHINFO_EXTENSION) : '';
                    if (function_exists('storage_public_url')) {
                        $finalImage = $finalImage !== '' ? storage_public_url($finalImage) : '';
                    } else {
                        $finalImage = $finalImage !== '' ? $base_url . $finalImage : '';
                    }

                    if (!$canView || $finalImage === '') {
                        $finalImage = $base_url . 'uploads/web.png';
                        $exts = 'png';
                    }
                    if (in_array($exts, ['mp4', 'MP4'], true)) {
                        $storyAudioUrl = '';
                        $storyAudioTitle = '';
                        $storyAudioArtist = '';
                    }

                    if ($coverUrl === '' && $storieID === $coverStoryId && $canView) {
                        $coverUrl = $finalImage;
                    }

                    $storyItems[] = [
                        'id' => $storieID,
                        'time' => $stD['created'] ?? null,
                        'text' => $storieText,
                        'style' => $storieTextStyle,
                        'ext' => $exts,
                        'src' => $finalImage,
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

                if ($coverUrl === '') {
                    foreach ($storyItems as $itemData) {
                        if ($itemData['can_view'] === '1') {
                            $coverUrl = $itemData['src'];
                            break;
                        }
                    }
                }
                if ($coverUrl === '' || ($customCoverUrl === '' && !$hasAccessibleStory)) {
                    $coverUrl = $profileAvatar;
                }
        ?>
        <div class="story-view-item highlight-item"
             data-story-id="<?php echo iN_HelpSecure($p_profileID); ?>"
             data-highlight-id="<?php echo iN_HelpSecure($highlightId); ?>"
             data-seen-id="<?php echo iN_HelpSecure($highlightId); ?>"
             data-last-story-id="<?php echo iN_HelpSecure($lastStoryId); ?>"
             data-profile-image="<?php echo iN_HelpSecure($profileAvatar); ?>"
             data-profile-name="<?php echo iN_HelpSecure($profileFullName); ?>"
             data-profile-username="<?php echo iN_HelpSecure($profileUserName); ?>"
             data-seen-scope="highlights"
             style="--story-bg:url('<?php echo iN_HelpSecure($coverUrl); ?>');">
            <div class="story-bubble">
                <div class="story-ring">
                    <div class="story-view-pr-avatar" data-avatar="<?php echo iN_HelpSecure($profileAvatar); ?>"></div>
                </div>
            </div>
            <span class="name truncate"><?php echo iN_HelpSecure($highlightTitle); ?></span>
            <?php if ($isHighlightOwner) { ?>
                <button type="button" class="highlight-edit" data-highlight-id="<?php echo iN_HelpSecure($highlightId); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?>
                </button>
            <?php } ?>
            <ul class="media">
                <?php
                foreach ($storyItems as $itemData) {
                    $storieID = $itemData['id'];
                    $storieText = $itemData['text'];
                    $storieTextStyle = $itemData['style'];
                    $exts = $itemData['ext'];
                    $finalImage = $itemData['src'];
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

                    if (in_array($exts, ['mp4', 'MP4'], true)) {
                        echo '<li class="move_' . $storieID . '" data-id="' . $storieID . '" data-sid="' . $storieID . '" data-duration="" data-time="' . $StoryCreatedTime . '">
                                <video src="' . $finalImage . '" id="video_' . $storieID . '" alt="' . $storieText . '" data-id="' . $storieID . '" data-privacy="' . iN_HelpSecure($privacy) . '" data-can-view="' . iN_HelpSecure($canView) . '" data-access-reason="' . iN_HelpSecure($accessReason) . '" data-overlay-link="' . $overlayLinkAttr . '" data-overlay-mention="' . $overlayMentionAttr . '" data-overlay-sticker="' . $overlayStickerAttr . '" data-quick-replies="' . $quickRepliesJson . '" type="video/mp4"></video>
                              </li>';
                    } else {
                        echo '<li data-duration="7" data-id="' . $storieID . '" data-sid="' . $storieID . '" data-time="' . $StoryCreatedTime . '">
                                <img src="' . $finalImage . '" data-id="' . $storieID . '" data-ts="' . $storieTextStyle . '" data-privacy="' . iN_HelpSecure($privacy) . '" data-can-view="' . iN_HelpSecure($canView) . '" data-access-reason="' . iN_HelpSecure($accessReason) . '" data-overlay-link="' . $overlayLinkAttr . '" data-overlay-mention="' . $overlayMentionAttr . '" data-overlay-sticker="' . $overlayStickerAttr . '"' . $audioDataAttrs . ' data-quick-replies="' . $quickRepliesJson . '" alt="' . $storieText . '">
                              </li>';
                    }
                }
                ?>
            </ul>
        </div>
        <?php
            }
        }
        ?>
    </div>
</div>
