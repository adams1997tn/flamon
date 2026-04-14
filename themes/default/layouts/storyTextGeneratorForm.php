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
            <?php echo iN_HelpSecure($LANG['createyourtextstatus']); ?>
         </div>
      </div>
      <div class="create_sotry_form_container">
         <div class="create_text_story_bg_wrapper flex_">
            <div class="bgs"><?php echo iN_HelpSecure($LANG['bgs']); ?></div>
            <?php
            $bgImages = $iN->iN_GetStoryBgImages();
            if ($bgImages) {
                foreach ($bgImages as $bgData) {
                    $bgID = $bgData['st_bg_id'] ?? null;
                    $bgImage = $bgData['st_bg_img_url'] ?? null;
                    $choosedStatus = $bgData['choosed_status'] ?? null;

                    if (function_exists('storage_public_url')) {
                        $filePathUrl = storage_public_url($bgImage);
                    } else {
                        $filePathUrl = $base_url . $bgImage;
                    }
            ?>
            <div class="st_bg_cont">
                <div class="st_img_wrapper relativePosition <?php echo iN_HelpSecure($choosedStatus) == 'ok' ? 'choosed_bg' : ''; ?>"
                     data-bg="<?php echo iN_HelpSecure($filePathUrl); ?>"
                     data-img="<?php echo iN_HelpSecure($filePathUrl); ?>"
                     data-iid="<?php echo iN_HelpSecure($bgID); ?>">
                    <div class="loader"></div>
                </div>
            </div>
            <?php
                }
            }
            ?>
            <div class="typing_textarea typing_textarea_story">
               <textarea class="strt_typing" id="strt_text" placeholder="<?php echo iN_HelpSecure($LANG['start_typing']); ?>"></textarea>
            </div>
            <div class="choosed_image">
               <div class="choosed_image_or">
                  <div class="text_typed flex_ tabing"><?php echo iN_HelpSecure($LANG['start_typing']); ?></div>
                  <img id="theBg" src="<?php echo iN_HelpSecure($base_url . $iN->iN_GetChoosedBgImage()); ?>">
               </div>
            </div>
            <div class="story_options story_text_options">
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
            <div class="share_my_story">
               <div class="share_story_btn_cnt flex_ tabing transition share_text_story">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?>
                  <div class="pbtn"><?php echo iN_HelpSecure($LANG['publish']); ?></div>
               </div>
            </div>
         </div>
      </div>
      <div class="edit_created_stories"></div>
      <div class="non-shared-yet"></div>
   </div>
</div>  
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/textStoryHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/storyStickerPicker.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/storyAudioPicker.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
