<?php
$isEditHighlight = !empty($highlightData);
$highlightId = $isEditHighlight ? (int)$highlightData['id'] : 0;
$highlightTitle = $isEditHighlight ? (string)($highlightData['title'] ?? '') : '';
$selectedStoryIds = $selectedStoryIds ?? [];
$highlightCoverPath = $isEditHighlight ? (string)($highlightData['cover_image'] ?? '') : '';
$highlightCoverUrl = '';
if ($highlightCoverPath !== '') {
    if (filter_var($highlightCoverPath, FILTER_VALIDATE_URL)) {
        $highlightCoverUrl = $highlightCoverPath;
    } elseif (function_exists('storage_public_url')) {
        $highlightCoverUrl = storage_public_url($highlightCoverPath);
    } else {
        $highlightCoverUrl = $base_url . $highlightCoverPath;
    }
}
?>
<div class="i_modal_bg_in highlight-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="highlightModalTitle"
     data-highlight-title-required="<?php echo iN_HelpSecure($LANG['highlight_title_required']); ?>"
     data-highlight-story-required="<?php echo iN_HelpSecure($LANG['highlight_story_required']); ?>"
     data-highlight-delete-confirm="<?php echo iN_HelpSecure($LANG['highlight_delete_confirm']); ?>">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="highlightModalTitle">
                <?php echo iN_HelpSecure($isEditHighlight ? $LANG['highlight_edit_title'] : $LANG['highlight_create_title']); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="highlight-modal-body">
                <div class="highlight-form-field">
                    <label class="highlight-label" for="highlightTitleInput">
                        <?php echo iN_HelpSecure($LANG['highlight_title_label']); ?>
                    </label>
                    <input type="text"
                           id="highlightTitleInput"
                           class="highlight-title-input"
                           maxlength="120"
                           value="<?php echo iN_HelpSecure($highlightTitle); ?>"
                           placeholder="<?php echo iN_HelpSecure($LANG['highlight_title_placeholder']); ?>">
                </div>
                <div class="highlight-form-field">
                    <div class="highlight-label">
                        <?php echo iN_HelpSecure($LANG['highlight_cover_label']); ?>
                    </div>
                    <div class="highlight-cover-uploader">
                        <div class="highlight-cover-preview<?php echo $highlightCoverUrl !== '' ? ' has-cover' : ''; ?>"
                             data-cover-url="<?php echo iN_HelpSecure($highlightCoverUrl); ?>"
                             <?php echo $highlightCoverUrl !== '' ? 'style="background-image:url(\'' . iN_HelpSecure($highlightCoverUrl) . '\');"' : ''; ?>>
                            <span class="highlight-cover-placeholder">
                                <?php echo iN_HelpSecure($LANG['highlight_cover_empty']); ?>
                            </span>
                        </div>
                        <label class="highlight-cover-button">
                            <input type="file" class="highlight-cover-input" accept="image/*">
                            <?php echo iN_HelpSecure($LANG['highlight_cover_upload']); ?>
                        </label>
                    </div>
                    <div class="highlight-cover-hint">
                        <?php echo iN_HelpSecure($LANG['highlight_cover_hint']); ?>
                    </div>
                </div>
                <div class="highlight-form-field">
                    <div class="highlight-label">
                        <?php echo iN_HelpSecure($LANG['highlight_choose_stories']); ?>
                    </div>
                    <?php if (!empty($storyArchive)) { ?>
                        <div class="highlight-story-grid">
                            <?php foreach ($storyArchive as $storyItem) {
                                $storyId = (int)($storyItem['s_id'] ?? 0);
                                $thumbPath = $storyItem['upload_tumbnail_file_path'] ?? $storyItem['uploaded_file_path'] ?? '';
                                $thumbUrl = '';
                                if ($thumbPath !== '') {
                                    if (function_exists('storage_public_url')) {
                                        $thumbUrl = storage_public_url($thumbPath);
                                    } else {
                                        $thumbUrl = $base_url . $thumbPath;
                                    }
                                }
                                if ($thumbUrl === '') {
                                    $thumbUrl = $base_url . 'uploads/web.png';
                                }
                                $isChecked = in_array($storyId, $selectedStoryIds, true);
                            ?>
                                <label class="highlight-story-option">
                                    <input type="checkbox"
                                           class="highlight-story-checkbox"
                                           value="<?php echo iN_HelpSecure($storyId); ?>"
                                           <?php echo $isChecked ? 'checked="checked"' : ''; ?>>
                                    <span class="highlight-story-thumb" style="background-image:url('<?php echo iN_HelpSecure($thumbUrl); ?>');"></span>
                                </label>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="highlight-empty-note">
                            <?php echo iN_HelpSecure($LANG['highlight_no_stories']); ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="highlight-error" role="status" aria-live="polite"></div>
            </div>
            <div class="i_modal_g_footer highlight-modal-footer">
                <?php if ($isEditHighlight) { ?>
                    <div class="alertBtnLeft highlight-delete transition" data-highlight-id="<?php echo iN_HelpSecure($highlightId); ?>">
                        <?php echo iN_HelpSecure($LANG['highlight_delete']); ?>
                    </div>
                <?php } ?>
                <div class="alertBtnRight highlight-save transition" data-highlight-id="<?php echo iN_HelpSecure($highlightId); ?>">
                    <?php echo iN_HelpSecure($LANG['highlight_save']); ?>
                </div>
                <div class="alertBtnLeft no-del transition">
                    <?php echo iN_HelpSecure($LANG['highlight_cancel']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
