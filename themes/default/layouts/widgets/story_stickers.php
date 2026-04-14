<div class="story_sticker_picker_panel">
    <div class="story_sticker_picker_grid">
        <?php
        $dataStickers = $iN->iN_GetActiveStickers();
        if ($dataStickers) {
            foreach ($dataStickers as $dSticker) {
                $stickerID = $dSticker['sticker_id'];
                $stickerURL = $dSticker['sticker_url'];
                echo '
                <button type="button" class="story_sticker_item" data-sticker-id="' . $stickerID . '" data-sticker-url="' . $stickerURL . '">
                    <img src="' . $stickerURL . '" alt="">
                </button>';
            }
        }
        ?>
    </div>
</div>
