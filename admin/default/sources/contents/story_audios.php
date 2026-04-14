<?php
$totalStoryAudios = $iN->iN_TotalStoryAudio();
$totalPages = ceil($totalStoryAudios / $paginationLimit);
if (isset($_GET["page-id"])) {
    $pagep  = isset($_GET["page-id"]) ? (int)$_GET["page-id"] : 1;
    if(preg_match('/^[0-9]+$/', $pagep)){
        $pagep = $pagep;
    }else{
        $pagep = '1';
    }
}else{
    $pagep = '1';
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
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
          <?php echo iN_HelpSecure($LANG['story_audio_library']).'('.$totalStoryAudios.')';?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="new_svg_icon_wrapper margin_bottom_custom_css_js">
                <div class="story-audio-upload-card border_one">
                    <div class="story-audio-upload-header">
                        <div class="story-audio-upload-title">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91'));?>
                            <?php echo iN_HelpSecure($LANG['story_audio_upload']);?>
                        </div>
                        <div class="story-audio-upload-meta">
                            <?php echo iN_HelpSecure($LANG['max_size']);?>
                        </div>
                    </div>
                    <form id="storyAudioUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/request/request.php">
                        <div class="story-audio-upload-grid">
                            <div class="story-audio-upload-fields">
                                <div class="story-audio-field">
                                    <label for="story_audio_title"><?php echo iN_HelpSecure($LANG['story_audio_title']);?></label>
                                    <input type="text" id="story_audio_title" name="audio_title" placeholder="<?php echo iN_HelpSecure($LANG['story_audio_title_placeholder']); ?>">
                                </div>
                                <div class="story-audio-field">
                                    <label for="story_audio_artist"><?php echo iN_HelpSecure($LANG['story_audio_artist']);?></label>
                                    <input type="text" id="story_audio_artist" name="audio_artist" placeholder="<?php echo iN_HelpSecure($LANG['story_audio_artist_placeholder']); ?>">
                                </div>
                            </div>
                            <div class="story-audio-upload-drop">
                                <label for="story_audio_file" class="story-audio-upload-drop-area">
                                    <span class="story-audio-upload-icon">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91'));?>
                                    </span>
                                    <span class="story-audio-upload-text"><?php echo iN_HelpSecure($LANG['story_audio_upload']);?></span>
                                    <span class="story-audio-upload-subtext"><?php echo iN_HelpSecure($storyAudioUploadHint);?></span>
                                    <input type="file" id="story_audio_file" name="uploading[]" data-id="storyAudioFile" data-type="sec_two" class="story-audio-file-input" accept="<?php echo iN_HelpSecure($storyAudioAccept); ?>">
                                </label>
                                <div class="story-audio-selected-file" id="storyAudioSelectedFile"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']);?></div>
        <?php
        $allAudios = $iN->iN_AllStoryAudioList($userID, $paginationLimit, $pagep);
        if($allAudios){
        ?>
        <div class="i_overflow_x_auto">
            <table class="border_one">
                <tr>
                    <th><?php echo iN_HelpSecure($LANG['id']);?></th>
                    <th><?php echo iN_HelpSecure($LANG['story_audio_preview']);?></th>
                    <th><?php echo iN_HelpSecure($LANG['story_audio_title']);?></th>
                    <th><?php echo iN_HelpSecure($LANG['story_audio_artist']);?></th>
                    <th><?php echo iN_HelpSecure($LANG['status']);?></th>
                    <th><?php echo iN_HelpSecure($LANG['actions']);?></th>
                </tr>
        <?php
        foreach($allAudios as $aData){
            $audioID = $aData['id'];
            $audioTitle = $aData['title'] ?? '';
            $audioArtist = $aData['artist'] ?? '';
            $audioStatus = $aData['status'] ?? '0';
            $audioUrl = $aData['audio_url'] ?? '';
            if (function_exists('storage_public_url')) {
                $filePathUrl = storage_public_url($audioUrl);
            } else {
                $filePathUrl = $base_url . $audioUrl;
            }
        ?>
        <tr class="transition trhover body_<?php echo iN_HelpSecure($audioID);?>">
            <td><?php echo iN_HelpSecure($audioID);?></td>
            <td class="see_post_details">
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
            </td>
            <td><?php echo iN_HelpSecure($audioTitle);?></td>
            <td><?php echo iN_HelpSecure($audioArtist);?></td>
            <td class="see_post_details">
               <div class="flex_ tabing_non_justify">
               <label class="el-switch el-switch-yellow" for="upStoryAudio<?php echo iN_HelpSecure($audioID);?>">
                   <input type="checkbox" name="audioStatus" class="upStick" id="upStoryAudio<?php echo iN_HelpSecure($audioID);?>" data-id="<?php echo iN_HelpSecure($audioID);?>" data-type="upStoryAudio" <?php echo iN_HelpSecure($audioStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"';?>>
                   <span class="el-switch-style"></span>
                </label>
               <div class="success_tick tabing flex_ sec_one upStick<?php echo iN_HelpSecure($audioID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
            </td>
            <td class="flex_ tabing_non_justify">
                <div class="flex_ tabing_non_justify">
                    <div class="edit_story_audio border_one transition tabing flex_" id="<?php echo iN_HelpSecure($audioID);?>" title="<?php echo iN_HelpSecure($LANG['edit_story_audio']);?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')).$LANG['edit_this'];?>
                    </div>
                    <div class="delu del_story_audio border_one transition tabing flex_" id="<?php echo iN_HelpSecure($audioID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')).$LANG['delete'];?></div>
                </div>
            </td>
        </tr>
        <?php }?>
        </table>
            </div>
        <?php }else{echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">'.html_entity_decode($iN->iN_SelectedMenuIcon('54')).'</div><div class="n_c_t">'.$LANG['no_user_found'].'</div></div>';}?>

        </div>
    <div class="i_become_creator_box_footer tabing">
        <?php if (ceil($totalStoryAudios / $paginationLimit) > 1): ?>
            <ul class="pagination">
                <?php if ($pagep > 1): ?>
                <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep)-1 ?>"><?php echo iN_HelpSecure($LANG['preview_page']);?></a></li>
                <?php endif; ?>

                <?php if ($pagep > 3): ?>
                <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=1">1</a></li>
                <li class="dots">...</li>
                <?php endif; ?>

                <?php if ($pagep-2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep)-2; ?>"><?php echo iN_HelpSecure($pagep)-2; ?></a></li><?php endif; ?>
                <?php if ($pagep-1 > 0): ?><li class="page"><a href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep)-1; ?>"><?php echo iN_HelpSecure($pagep)-1; ?></a></li><?php endif; ?>

                <li class="currentpage active"><a  class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                <?php if ($pagep+1 < ceil($totalStoryAudios / $paginationLimit)+1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep)+1; ?>"><?php echo iN_HelpSecure($pagep)+1; ?></a></li><?php endif; ?>
                <?php if ($pagep+2 < ceil($totalStoryAudios / $paginationLimit)+1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep)+2; ?>"><?php echo iN_HelpSecure($pagep)+2; ?></a></li><?php endif; ?>

                <?php if ($pagep < ceil($totalStoryAudios / $paginationLimit)-2): ?>
                <li class="dots">...</li>
                <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo ceil($totalStoryAudios / $paginationLimit); ?>"><?php echo ceil($totalStoryAudios / $paginationLimit); ?></a></li>
                <?php endif; ?>

                <?php if ($pagep < ceil($totalStoryAudios / $paginationLimit)): ?>
                <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/story_audios?page-id=<?php echo iN_HelpSecure($pagep)+1; ?>"><?php echo iN_HelpSecure($LANG['next_page']);?></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
     </div>
    </div>

</div>
<script type="text/javascript">
(function($) {
    "use strict";
$(document).on("change", "#story_audio_file", function(e) {
    e.preventDefault();
    var fileName = this.files && this.files.length ? this.files[0].name : "";
    $("#storyAudioSelectedFile").text(fileName);
    var id = $(this).attr("data-id");
    var type = $(this).attr("data-type");
    var data = { f: id, c: type };
    $("#storyAudioUploadForm").ajaxForm({
        type: "POST",
        data: data,
        delegation: true,
        cache: false,
        beforeSubmit: function() {
            $(".story-audio-upload-card").append('<div class="i_upload_progress"></div>');
        },
        uploadProgress: function(e, position, total, percentageComplete) {
            $('.i_upload_progress').width(percentageComplete + '%');
        },
        success: function(response) {
             if(response == '200'){
                location.reload();
             }else{
                $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                setTimeout(() => {
                    $(".nnauthority").remove();
                }, 5000);
             }
        },
        error: function() {}
    }).submit();
});

function formatStoryAudioTime(totalSeconds) {
    if (!isFinite(totalSeconds) || totalSeconds < 0) {
        totalSeconds = 0;
    }
    var minutes = Math.floor(totalSeconds / 60);
    var seconds = Math.floor(totalSeconds % 60);
    var paddedMinutes = minutes < 10 ? "0" + minutes : minutes;
    var paddedSeconds = seconds < 10 ? "0" + seconds : seconds;
    return paddedMinutes + ":" + paddedSeconds;
}

function updateStoryAudioTimes($preview, audioElement) {
    var duration = isFinite(audioElement.duration) ? audioElement.duration : 0;
    var remaining = Math.max(duration - (audioElement.currentTime || 0), 0);
    $preview.find(".story-audio-time-total").text(formatStoryAudioTime(duration));
    $preview.find(".story-audio-time-remaining").text("-" + formatStoryAudioTime(remaining));
}

function setStoryAudioState($preview, isPlaying) {
    var $button = $preview.find(".story-audio-play-btn");
    var label = isPlaying ? $button.data("pause-label") : $button.data("play-label");
    $preview.toggleClass("is-playing", isPlaying);
    $button.attr("aria-pressed", isPlaying ? "true" : "false");
    if (label) {
        $button.attr("aria-label", label);
    }
}

function pauseOtherStoryAudios(currentAudio) {
    $(".story-audio-element").each(function() {
        if (this !== currentAudio && !this.paused) {
            this.pause();
        }
    });
}

function initStoryAudioPreview($preview) {
    if ($preview.data("story-audio-init")) {
        return;
    }
    var audioElement = $preview.find(".story-audio-element").get(0);
    if (!audioElement) {
        return;
    }
    $preview.data("story-audio-init", true);

    audioElement.addEventListener("loadedmetadata", function() {
        updateStoryAudioTimes($preview, audioElement);
    });

    audioElement.addEventListener("timeupdate", function() {
        updateStoryAudioTimes($preview, audioElement);
    });

    audioElement.addEventListener("play", function() {
        setStoryAudioState($preview, true);
    });

    audioElement.addEventListener("pause", function() {
        setStoryAudioState($preview, false);
    });

    audioElement.addEventListener("ended", function() {
        audioElement.currentTime = 0;
        updateStoryAudioTimes($preview, audioElement);
        setStoryAudioState($preview, false);
    });

    updateStoryAudioTimes($preview, audioElement);
}

$(".story-audio-preview").each(function() {
    initStoryAudioPreview($(this));
});

$(document).on("click", ".story-audio-play-btn", function(e) {
    e.preventDefault();
    var $preview = $(this).closest(".story-audio-preview");
    initStoryAudioPreview($preview);
    var audioElement = $preview.find(".story-audio-element").get(0);
    if (!audioElement) {
        return;
    }
    if (audioElement.paused) {
        pauseOtherStoryAudios(audioElement);
        var playPromise = audioElement.play();
        if (playPromise && playPromise.catch) {
            playPromise.catch(function() {
                audioElement.pause();
            });
        }
    } else {
        audioElement.pause();
    }
});
})(jQuery);
</script>
