<div class="story_audio_picker_panel">
    <div class="story_audio_picker_list">
        <?php
        $storyAudios = $iN->iN_GetStoryAudioList();
        if ($storyAudios) {
            foreach ($storyAudios as $audio) {
                $audioId = $audio['id'] ?? 0;
                $audioTitle = $audio['title'] ?? '';
                $audioArtist = $audio['artist'] ?? '';
                $audioUrl = $audio['audio_url'] ?? '';
                if (function_exists('storage_public_url')) {
                    $audioPublicUrl = storage_public_url($audioUrl);
                } else {
                    $audioPublicUrl = $base_url . $audioUrl;
                }
                $displayText = trim($audioTitle);
                if ($audioArtist !== '') {
                    $displayText .= ' - ' . $audioArtist;
                }
        ?>
        <div class="story_audio_item" role="button" tabindex="0"
                data-audio-id="<?php echo iN_HelpSecure($audioId); ?>"
                data-audio-url="<?php echo iN_HelpSecure($audioPublicUrl, FILTER_VALIDATE_URL); ?>"
                data-audio-title="<?php echo iN_HelpSecure($audioTitle); ?>"
                data-audio-artist="<?php echo iN_HelpSecure($audioArtist); ?>">
            <button type="button"
                    class="story_audio_item_play"
                    aria-label="<?php echo iN_HelpSecure($LANG['story_audio_play']); ?>"
                    aria-pressed="false"
                    data-play-label="<?php echo iN_HelpSecure($LANG['story_audio_play']); ?>"
                    data-pause-label="<?php echo iN_HelpSecure($LANG['story_audio_pause']); ?>">
                <span class="story_audio_item_icon story_audio_item_icon_play" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.28-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z"/>
                    </svg>
                </span>
                <span class="story_audio_item_icon story_audio_item_icon_pause" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M7 5h4v14H7zM13 5h4v14h-4z"/>
                    </svg>
                </span>
            </button>
            <span class="story_audio_item_title"><?php echo iN_HelpSecure($displayText); ?></span>
        </div>
        <?php
            }
        }
        ?>
    </div>
</div>
