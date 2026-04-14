<?php
$audioID = $audioData['id'] ?? 0;
$audioTitle = $audioData['title'] ?? '';
$audioArtist = $audioData['artist'] ?? '';
$audioUrl = $audioData['audio_url'] ?? '';
if (function_exists('storage_public_url')) {
    $filePathUrl = storage_public_url($audioUrl);
} else {
    $filePathUrl = $base_url . $audioUrl;
}
$storyAudioUploadModeLocal = isset($storyAudioUploadMode) ? (string)$storyAudioUploadMode : 'mp3_only';
if (!in_array($storyAudioUploadModeLocal, ['mp3_only', 'ffmpeg_mp3'], true)) {
    $storyAudioUploadModeLocal = 'mp3_only';
}
$storyAudioUploadHint = $storyAudioUploadModeLocal === 'ffmpeg_mp3'
    ? ($LANG['story_audio_upload_hint_convert'] ?? ($LANG['story_audio_upload_hint'] ?? ''))
    : ($LANG['story_audio_upload_hint_mp3'] ?? ($LANG['story_audio_upload_hint'] ?? ''));
$storyAudioAccept = $storyAudioUploadModeLocal === 'ffmpeg_mp3'
    ? '.mp3,.wav,.m4a,.ogg,.oga,.mp4,audio/*,video/mp4'
    : '.mp3,audio/mpeg';
$storyAudioInvalidFormatText = $storyAudioUploadModeLocal === 'ffmpeg_mp3'
    ? ($LANG['story_audio_upload_invalid_convert'] ?? ($LANG['invalid_file_format'] ?? ''))
    : ($LANG['story_audio_upload_invalid_mp3_only'] ?? ($LANG['invalid_file_format'] ?? ''));
?>
<div class="i_modal_bg_in">
    <div class="i_modal_in_in">
        <div class="i_modal_content" id="storyAudioEditContainer">
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['edit_story_audio']); ?>
                <div class="shareClose transition">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="story-audio-edit-preview">
                <div class="story-audio-preview" data-audio-id="<?php echo iN_HelpSecure($audioID);?>">
                    <button type="button"
                            class="story-audio-play-btn"
                            aria-label="<?php echo iN_HelpSecure($LANG['story_audio_play']);?>"
                            aria-pressed="false"
                            data-play-label="<?php echo iN_HelpSecure($LANG['story_audio_play']);?>"
                            data-pause-label="<?php echo iN_HelpSecure($LANG['story_audio_pause']);?>">
                        <span class="story-audio-icon story-audio-icon-play">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.28-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z"/>
                            </svg>
                        </span>
                        <span class="story-audio-icon story-audio-icon-pause">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M7 5h4v14H7zM13 5h4v14h-4z"/>
                            </svg>
                        </span>
                    </button>
                    <div class="story-audio-wave" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="story-audio-time">
                        <span class="story-audio-time-total">00:00</span>
                        <span class="story-audio-time-remaining">-00:00</span>
                    </div>
                    <audio preload="metadata" class="story-audio-element">
                        <source src="<?php echo iN_HelpSecure($filePathUrl, FILTER_VALIDATE_URL);?>" type="audio/mpeg">
                    </audio>
                </div>
            </div>
            <form enctype="multipart/form-data" method="post" id="storyAudioEditForm">
                <div class="story-audio-edit-grid">
                    <div class="story-audio-field">
                        <label for="story_audio_title_edit"><?php echo iN_HelpSecure($LANG['story_audio_title']);?></label>
                        <input type="text" id="story_audio_title_edit" name="audio_title" value="<?php echo iN_HelpSecure($audioTitle);?>" placeholder="<?php echo iN_HelpSecure($LANG['story_audio_title_placeholder']); ?>">
                    </div>
                    <div class="story-audio-field">
                        <label for="story_audio_artist_edit"><?php echo iN_HelpSecure($LANG['story_audio_artist']);?></label>
                        <input type="text" id="story_audio_artist_edit" name="audio_artist" value="<?php echo iN_HelpSecure($audioArtist);?>" placeholder="<?php echo iN_HelpSecure($LANG['story_audio_artist_placeholder']); ?>">
                    </div>
                </div>
                <div class="story-audio-edit-upload">
                    <label for="story_audio_edit_file" class="story-audio-upload-drop-area story-audio-upload-inline">
                        <span class="story-audio-upload-icon">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91'));?>
                        </span>
                        <span class="story-audio-upload-text"><?php echo iN_HelpSecure($LANG['story_audio_replace_optional']);?></span>
                        <span class="story-audio-upload-subtext"><?php echo iN_HelpSecure($storyAudioUploadHint);?></span>
                        <input type="file" id="story_audio_edit_file" name="uploading[]" class="story-audio-file-input" accept="<?php echo iN_HelpSecure($storyAudioAccept); ?>">
                    </label>
                    <div class="story-audio-selected-file" id="storyAudioEditSelectedFile"></div>
                </div>
                <div class="warning_wrapper warning_story_audio_title box_not_padding_left">
                    <?php echo iN_HelpSecure($LANG['story_audio_title_required']); ?>
                </div>
                <div class="warning_wrapper warning_story_audio_format box_not_padding_left">
                    <?php echo iN_HelpSecure($storyAudioInvalidFormatText); ?>
                </div>
                <div class="warning_wrapper warning_story_audio_size box_not_padding_left">
                    <?php echo iN_HelpSecure($LANG['file_is_too_large']); ?>
                </div>
                <div class="i_modal_g_footer flex_">
                    <input type="hidden" name="id" value="<?php echo iN_HelpSecure($audioID); ?>">
                    <input type="hidden" name="f" value="editStoryAudio">
                    <div class="popupSaveButton transition">
                        <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateGeneralSettings">
                            <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                        </button>
                    </div>
                    <div class="alertBtnLeft no-del transition">
                        <?php echo iN_HelpSecure($LANG['no']); ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/editStoryAudioHandler.js?v=<?php echo iN_HelpSecure($version);?>" defer></script>
</div>
