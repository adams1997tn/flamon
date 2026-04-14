<?php
if ($iN->iN_CheckUserIsCreator($userID) != 1) {
    header('Location: ' . route_url('404'));
    exit;
}

$autoMessage = $iN->iN_GetCreatorAutoMessage($userID);
$isEnabled = (int)($autoMessage['is_enabled'] ?? 0);
$messageText = (string)($autoMessage['message_text'] ?? '');
$attachmentPath = isset($autoMessage['attachment_path']) ? trim((string)$autoMessage['attachment_path']) : '';
$ffmpegEnabled = (isset($ffmpegStatus) && (string)$ffmpegStatus === '1' && !empty($ffmpegPath));
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');

$attachmentLabel = $LANG['creator_bulk_attachment_none'];
$attachmentClass = 'creator_bulk_attachment_none';
if ($attachmentPath !== '') {
    $extension = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $videoExtensions = ['mp4', 'mov', 'webm', 'mkv', 'avi', 'mpeg', 'mpg', '3gp'];
    $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
    if (in_array($extension, $imageExtensions, true)) {
        $attachmentLabel = $LANG['creator_bulk_attachment_image'];
        $attachmentClass = 'creator_bulk_attachment_image';
    } elseif (in_array($extension, $videoExtensions, true)) {
        $attachmentLabel = $LANG['creator_bulk_attachment_video'];
        $attachmentClass = 'creator_bulk_attachment_video';
    } elseif (in_array($extension, $audioExtensions, true)) {
        $attachmentLabel = $LANG['creator_bulk_attachment_audio'];
        $attachmentClass = 'creator_bulk_attachment_audio';
    } else {
        $attachmentLabel = $LANG['creator_bulk_attachment_file'];
        $attachmentClass = 'creator_bulk_attachment_file';
    }
}
?>
<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_">
         <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('38'));?>
         <?php echo iN_HelpSecure($LANG['creator_auto_message_title']);?>
       </div>
    </div>
    <div class="i_settings_wrapper_items">
      <form id="creatorAutoMessageForm" method="post" enctype="multipart/form-data" data-ffmpeg="<?php echo $ffmpegEnabled ? '1' : '0'; ?>">
        <div class="i_warning creator_auto_message_notice"></div>
        <input type="hidden" name="f" value="creatorAutoMessageSave">
        <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['creator_auto_message_enable']);?></div>
          <div class="i_settings_item_title_for">
            <select name="is_enabled" class="flnm creator_bulk_select creator_auto_message_select">
              <option value="0" <?php echo $isEnabled === 1 ? '' : 'selected'; ?>><?php echo iN_HelpSecure($LANG['no']);?></option>
              <option value="1" <?php echo $isEnabled === 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['yes']);?></option>
            </select>
          </div>
        </div>

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['creator_auto_message_text_label']);?></div>
          <div class="i_settings_item_title_for">
            <textarea name="message_text" class="description_" placeholder="<?php echo iN_HelpSecure($LANG['creator_auto_message_text_label']);?>"><?php echo iN_HelpSecure($messageText);?></textarea>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['creator_auto_message_note']);?></div>
          </div>
        </div>

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['creator_auto_message_attachment_label']);?></div>
          <div class="i_settings_item_title_for">
            <div class="creator_bulk_file_wrap">
              <label class="creator_bulk_file_label">
                <span class="creator_bulk_file_text" data-default="<?php echo iN_HelpSecure($LANG['creator_bulk_choose_file']);?>">
                  <?php echo iN_HelpSecure($LANG['creator_bulk_choose_file']);?>
                </span>
                <input type="file" name="auto_attachment" class="creator_bulk_file_input creator_auto_message_file_input" accept="image/*,video/*,audio/*">
              </label>
            </div>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['bulk_attachment_note']);?></div>
            <?php if ($attachmentPath !== '') {
                if (function_exists('storage_public_url')) {
                    $attachmentUrl = storage_public_url($attachmentPath);
                } else {
                    $attachmentUrl = $base_url . $attachmentPath;
                }
            ?>
              <div class="creator_auto_message_current">
                <a class="creator_bulk_attachment <?php echo iN_HelpSecure($attachmentClass); ?>" href="<?php echo iN_HelpSecure($attachmentUrl); ?>" target="_blank" rel="noopener">
                  <?php echo iN_HelpSecure($attachmentLabel); ?>
                </a>
                <label class="creator_auto_message_remove">
                  <input type="checkbox" name="remove_attachment" value="1">
                  <?php echo iN_HelpSecure($LANG['creator_auto_message_remove_attachment']);?>
                </label>
              </div>
            <?php } ?>
          </div>
        </div>

        <div class="i_settings_wrapper_item creator_bulk_thumb_field creator_bulk_thumb_hidden">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['creator_bulk_video_thumbnail']);?></div>
          <div class="i_settings_item_title_for">
            <div class="creator_bulk_file_wrap">
              <label class="creator_bulk_file_label">
                <span class="creator_bulk_file_text" data-default="<?php echo iN_HelpSecure($LANG['creator_bulk_choose_thumbnail']);?>">
                  <?php echo iN_HelpSecure($LANG['creator_bulk_choose_thumbnail']);?>
                </span>
                <input type="file" name="auto_video_thumbnail" class="creator_bulk_file_input creator_bulk_thumb_input" accept="image/jpeg,image/jpg">
              </label>
            </div>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['creator_bulk_thumbnail_note']);?></div>
          </div>
        </div>

        <div class="i_become_creator_box_footer">
          <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['save_changes']);?></button>
        </div>
      </form>
    </div>
  </div>
</div>
