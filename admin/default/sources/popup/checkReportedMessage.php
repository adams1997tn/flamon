<div class="i_modal_bg_in">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['report_message_check_popup_title']); ?>
                <div class="shareClose transition">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>

            <div class="i_delete_post_description">
                <div class="rm_check_desc"><?php echo iN_HelpSecure($LANG['report_message_check_popup_desc']); ?></div>
            </div>

            <div class="rm_check_modal_body">
                <textarea
                    class="rm_check_note_input"
                    id="rmCheckNoteText<?php echo iN_HelpSecure($postID); ?>"
                    placeholder="<?php echo iN_HelpSecure($LANG['report_message_note_placeholder']); ?>"
                ><?php echo iN_HelpSecure($defaultNote); ?></textarea>
            </div>

            <div class="i_modal_g_footer flex_ tabing_non_justify rm_check_footer">
                <div class="rm_check_submit_btn check_report_m_submit transition flex_ tabing_non_justify" data-id="<?php echo iN_HelpSecure($postID); ?>">
                    <span class="rm_check_submit_text"><?php echo iN_HelpSecure($LANG['report_message_mark_checked']); ?></span>
                    <span class="rm_check_submit_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('37')); ?></span>
                </div>
                <div class="rm_check_cancel_btn no-del transition">
                    <?php echo iN_HelpSecure($LANG['no']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
