<?php
$communityTitle = isset($communityData['title']) ? $communityData['title'] : '';
$unsubNote = $LANG['community_unsubscribe_note'] ?? 'Are you sure you want to unsubscribe?';
if (!empty($communityTitle)) {
    $unsubNote = str_replace('{community}', $communityTitle, $unsubNote);
}
?>
<div class="i_modal_bg_in community_unsub_modal" role="dialog" aria-modal="true" aria-labelledby="communityUnsubTitle">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="communityUnsubTitle">
                <?php echo iN_HelpSecure($LANG['community_unsubscribe_title']); ?>
                <div class="shareClose transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['close'] ?? 'Close'); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>

            <div class="i_delete_post_description">
                <?php echo iN_HelpSecure($unsubNote); ?>
            </div>

            <input type="hidden" class="community_csrf_token" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">

            <div class="i_modal_g_footer">
                <div class="alertBtnRight communityUnsubConfirm transition"
                     data-community="<?php echo (int)$communityData['id']; ?>"
                     role="button"
                     aria-label="<?php echo iN_HelpSecure($LANG['ok']); ?>">
                    <?php echo iN_HelpSecure($LANG['ok']); ?>
                </div>
                <div class="alertBtnLeft no-del transition"
                     role="button"
                     aria-label="<?php echo iN_HelpSecure($LANG['no']); ?>">
                    <?php echo iN_HelpSecure($LANG['no']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
