<?php
$isStoryCreator = $iN->iN_CheckUserIsCreator($userID) == 1;
$storyQuickDefaults = [
  $LANG['story_quick_reply_default_1'] ?? 'Nice!',
  $LANG['story_quick_reply_default_2'] ?? 'Love this!',
  $LANG['story_quick_reply_default_3'] ?? 'Tell me more'
];
?>
<div class="th_middle">
  <div class="pageMiddle">
    <div class="live_item transition">
      <div class="live_title_page create_stories flex_">
        <?php echo $iN->iN_SelectedMenuIcon('154'); ?>
        <?php echo iN_HelpSecure($LANG['createyourstatus']); ?>
      </div>
    </div>

    <div class="create_sotry_form_container flex_ tabing">
      <div class="upload_story_image">
        <form id="storiesform" method="post" enctype="multipart/form-data" action="<?php echo $base_url; ?>requests/request.php">
          <label class="label_storyUpload" data-id="stories" for="storie_img">
            <input type="file" name="storieimg[]" id="storie_img" data-id="stories" multiple>
            <div class="story-view-item story_margin_right_zero" style="--story-bg:url('<?php echo iN_HelpSecure($userAvatar); ?>');" data-background-image="<?php echo iN_HelpSecure($userAvatar); ?>">
              <div class="story-bubble story-add">
                <div class="story-ring">
                  <div class="story-view-pr-avatar" data-avatar="<?php echo iN_HelpSecure($userAvatar); ?>"></div>
                </div>
                <div class="story-add-cta">
                  <div class="plstr"><?php echo $iN->iN_SelectedMenuIcon('153'); ?></div>
                </div>
              </div>
              <div class="newSto">
                <?php echo iN_HelpSecure($LANG['upload_storie_files']); ?>
              </div>
            </div>
          </label>
        </form>
      </div>
      <div class="i_uploading_not_story flex_ tabing nonePoint">
        <?php echo iN_HelpSecure($LANG['uploading_please_wait']); ?>
      </div>
    </div>

    <div class="edit_created_stories"></div>

    <div class="live_item transition">
      <div class="live_title_page non-shared-title-style create_stories flex_">
        <?php echo $iN->iN_SelectedMenuIcon('115') . iN_HelpSecure($LANG['non_shared_stories']); ?>
      </div>
    </div>

    <div class="non-shared-yet">
      <?php
        $nonSharedStoriesData = $iN->iN_GetNonSharedStories($userID);
        if ($nonSharedStoriesData) {
          foreach ($nonSharedStoriesData as $stData) {
            $storieID = $stData['s_id'];
            $storiOwnerID = $stData['uid_fk'];
            $storieUploadedFilePath = $stData['uploaded_file_path'];
            $storieUploadedfileExtension = $stData['uploaded_file_ext'];
            $storieUploadedFileTumbnail = $stData['upload_tumbnail_file_path'];
            $storieText = $stData['text'];
            $createdTime = $stData['created'];
            $crTime = date('Y-m-d H:i:s', $createdTime);

            if (in_array($storieUploadedfileExtension, ['mp4'])) {
              // Build proper public URLs for video src and poster (thumbnail)
              if (function_exists('storage_public_url')) {
                $videoUrl  = storage_public_url($storieUploadedFilePath);
                $posterUrl = !empty($storieUploadedFileTumbnail)
                  ? storage_public_url($storieUploadedFileTumbnail)
                  : $base_url . 'uploads/web.png';
              } else {
                $videoUrl  = $base_url . $storieUploadedFilePath;
                $posterUrl = !empty($storieUploadedFileTumbnail) ? $base_url . $storieUploadedFileTumbnail : $base_url . 'uploads/web.png';
              }
      ?>
      <div class="uploaded_storie_container body_<?php echo iN_HelpSecure($storieID); ?>">
        <div class="shared_storie_time flex_">
          <?php echo $iN->iN_SelectedMenuIcon('115') . ' ' . TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?>
        </div>
        <div class="dmyStory dmyStory_extra" id="<?php echo iN_HelpSecure($storieID); ?>">
          <div class="i_h_in flex_ ownTooltip" data-label="<?php echo iN_HelpSecure($LANG['delete']); ?>">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?>
          </div>
        </div>
        <div class="uploaded_storie_image uploaded_storie_before border_one tabing flex_">
          <video class="lg-video-object" id="v<?php echo iN_HelpSecure($storieID); ?>" controls preload="none" poster="<?php echo $posterUrl; ?>">
            <source src="<?php echo $videoUrl; ?>" preload="metadata" type="video/mp4">
          </video>
        </div>
        <div class="add_a_text">
          <textarea class="add_my_text st_txt_<?php echo iN_HelpSecure($storieID); ?>" placeholder="Do you want to write something about this storie?"></textarea>
        </div>
        <div class="story_options">
          <div class="story_option_group">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_privacy_label']); ?></div>
            <select class="story_privacy">
              <option value="followers"><?php echo iN_HelpSecure($LANG['story_privacy_followers']); ?></option>
              <option value="subscribers"><?php echo iN_HelpSecure($LANG['story_privacy_subscribers']); ?></option>
              <option value="everyone"><?php echo iN_HelpSecure($LANG['story_privacy_everyone']); ?></option>
            </select>
          </div>
          <div class="story_option_group">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_overlay_label']); ?></div>
            <div class="story_overlay_fields">
              <input type="text" class="story_overlay_link" placeholder="<?php echo iN_HelpSecure($LANG['story_overlay_link_placeholder']); ?>">
              <input type="text" class="story_overlay_mention" placeholder="<?php echo iN_HelpSecure($LANG['story_overlay_mention_placeholder']); ?>">
              <div class="story_overlay_sticker_field">
                <input type="hidden" class="story_overlay_sticker" value="">
                <div class="story_sticker_preview">
                  <img class="story_sticker_img" src="" alt="">
                  <button type="button" class="story_sticker_clear" title="<?php echo iN_HelpSecure($LANG['delete']); ?>" aria-label="<?php echo iN_HelpSecure($LANG['delete']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                  </button>
                </div>
                <button type="button" class="story_sticker_trigger" title="<?php echo iN_HelpSecure($LANG['chs_sticker_send']); ?>" aria-label="<?php echo iN_HelpSecure($LANG['chs_sticker_send']); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('24')); ?>
                </button>
              </div>
            </div>
            <div class="story_sticker_list"></div>
          </div>
          <?php if ($isStoryCreator) { ?>
          <div class="story_option_group story_quick_replies_toggle">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_quick_replies_label']); ?></div>
            <button type="button"
                    class="story_quick_replies_trigger"
                    aria-expanded="false"
                    data-add-label="<?php echo iN_HelpSecure($LANG['story_quick_replies_add']); ?>"
                    data-remove-label="<?php echo iN_HelpSecure($LANG['story_quick_replies_hide']); ?>">
              <?php echo iN_HelpSecure($LANG['story_quick_replies_add']); ?>
            </button>
          </div>
          <div class="story_option_group story_quick_replies_block is-hidden" data-enabled="0">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_quick_replies_label']); ?></div>
            <div class="story_quick_reply_inputs">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="<?php echo iN_HelpSecure($storyQuickDefaults[0] ?? ''); ?>">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="<?php echo iN_HelpSecure($storyQuickDefaults[1] ?? ''); ?>">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="<?php echo iN_HelpSecure($storyQuickDefaults[2] ?? ''); ?>">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="">
            </div>
            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['story_quick_replies_note']); ?></div>
          </div>
          <?php } ?>
        </div>
        <div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="<?php echo iN_HelpSecure($storieID); ?>">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?>
          <div class="pbtn"><?php echo iN_HelpSecure($LANG['publish']); ?></div>
        </div>
      </div>
      <?php
            } else {
              if (function_exists('storage_public_url')) {
                $filePathUrl = storage_public_url($storieUploadedFileTumbnail ?: $storieUploadedFilePath);
              } else {
                $filePathUrl = $base_url . ($storieUploadedFileTumbnail ?: $storieUploadedFilePath);
              }
      ?>
      <div class="uploaded_storie_container body_<?php echo iN_HelpSecure($storieID); ?>">
        <div class="shared_storie_time flex_">
          <?php echo $iN->iN_SelectedMenuIcon('115') . ' ' . TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?>
        </div>
        <div class="dmyStory dmyStory_extra" id="<?php echo iN_HelpSecure($storieID); ?>">
          <div class="i_h_in flex_ ownTooltip" data-label="<?php echo iN_HelpSecure($LANG['delete']); ?>">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?>
          </div>
        </div>
        <div class="uploaded_storie_image uploaded_storie_before border_one tabing flex_">
          <img src="<?php echo $filePathUrl; ?>" id="img<?php echo iN_HelpSecure($storieID); ?>">
        </div>
        <div class="add_a_text">
          <textarea class="add_my_text st_txt_<?php echo iN_HelpSecure($storieID); ?>" placeholder="Do you want to write something about this storie?"></textarea>
        </div>
        <div class="story_options">
          <div class="story_option_group">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_privacy_label']); ?></div>
            <select class="story_privacy">
              <option value="followers"><?php echo iN_HelpSecure($LANG['story_privacy_followers']); ?></option>
              <option value="subscribers"><?php echo iN_HelpSecure($LANG['story_privacy_subscribers']); ?></option>
              <option value="everyone"><?php echo iN_HelpSecure($LANG['story_privacy_everyone']); ?></option>
            </select>
          </div>
          <div class="story_option_group">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_overlay_label']); ?></div>
            <div class="story_overlay_fields">
              <input type="text" class="story_overlay_link" placeholder="<?php echo iN_HelpSecure($LANG['story_overlay_link_placeholder']); ?>">
              <input type="text" class="story_overlay_mention" placeholder="<?php echo iN_HelpSecure($LANG['story_overlay_mention_placeholder']); ?>">
              <div class="story_overlay_sticker_field">
                <input type="hidden" class="story_overlay_sticker" value="">
                <div class="story_sticker_preview">
                  <img class="story_sticker_img" src="" alt="">
                  <button type="button" class="story_sticker_clear" title="<?php echo iN_HelpSecure($LANG['delete']); ?>" aria-label="<?php echo iN_HelpSecure($LANG['delete']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                  </button>
                </div>
                <button type="button" class="story_sticker_trigger" title="<?php echo iN_HelpSecure($LANG['chs_sticker_send']); ?>" aria-label="<?php echo iN_HelpSecure($LANG['chs_sticker_send']); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('24')); ?>
                </button>
              </div>
            </div>
            <div class="story_sticker_list"></div>
          </div>
          <div class="story_option_group story_audio_block">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_audio_label']); ?></div>
            <div class="story_audio_field">
              <input type="hidden" class="story_overlay_audio" value="">
              <div class="story_audio_preview">
                <span class="story_audio_selected"></span>
                <button type="button" class="story_audio_clear" title="<?php echo iN_HelpSecure($LANG['delete']); ?>" aria-label="<?php echo iN_HelpSecure($LANG['delete']); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </button>
              </div>
              <button type="button" class="story_audio_trigger" title="<?php echo iN_HelpSecure($LANG['story_audio_choose']); ?>" aria-label="<?php echo iN_HelpSecure($LANG['story_audio_choose']); ?>">
                <?php echo iN_HelpSecure($LANG['story_audio_choose']); ?>
              </button>
            </div>
            <div class="story_audio_list"></div>
          </div>
          <?php if ($isStoryCreator) { ?>
          <div class="story_option_group story_quick_replies_block">
            <div class="story_option_label"><?php echo iN_HelpSecure($LANG['story_quick_replies_label']); ?></div>
            <div class="story_quick_reply_inputs">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="<?php echo iN_HelpSecure($storyQuickDefaults[0] ?? ''); ?>">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="<?php echo iN_HelpSecure($storyQuickDefaults[1] ?? ''); ?>">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="<?php echo iN_HelpSecure($storyQuickDefaults[2] ?? ''); ?>">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="">
              <input type="text" class="story_quick_reply_input" maxlength="120" value="">
            </div>
            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['story_quick_replies_note']); ?></div>
          </div>
          <?php } ?>
        </div>
        <div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="<?php echo iN_HelpSecure($storieID); ?>">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?>
          <div class="pbtn"><?php echo iN_HelpSecure($LANG['publish']); ?></div>
        </div>
      </div>
      <?php
            }
          }
        }
      ?>
    </div>
  </div>
</div>

<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/storyImageGeneratorHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/storyStickerPicker.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/storyAudioPicker.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
