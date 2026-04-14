<div class="i_modal_bg_in">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['delete_story_audio']); ?>
                <div class="shareClose transition">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>

            <div class="i_delete_post_description">
                <?php echo iN_HelpSecure($LANG['sure_to_delete_this_story_audio']); ?>
            </div>

            <div class="i_modal_g_footer flex_">
                <div class="alertBtnRight delete_story_audio transition" id="<?php echo iN_HelpSecure($delAudioID); ?>">
                    <?php echo iN_HelpSecure($LANG['delete_story_audio']); ?>
                </div>
                <div class="alertBtnLeft no-del transition">
                    <?php echo iN_HelpSecure($LANG['no']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
