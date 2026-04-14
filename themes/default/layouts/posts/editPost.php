<div class="i_modal_bg_in" role="dialog" aria-modal="true" aria-labelledby="editPostModalTitle">
    <!-- Modal Container -->
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <!-- Modal Header -->
            <div class="i_modal_g_header" id="editPostModalTitle">
                <?php echo iN_HelpSecure($LANG['edit_post']); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <!-- /Modal Header -->

            <!-- Post Editing Textarea -->
            <div class="i_more_text_wrapper">
                <label for="ed_<?php echo iN_HelpSecure($postID); ?>" class="visually-hidden">
                    <?php echo iN_HelpSecure($LANG['write_something_about_the_post']); ?>
                </label>
                <textarea 
                    class="more_textarea" 
                    id="ed_<?php echo iN_HelpSecure($postID); ?>" 
                    dir="auto" 
                    rows="5" 
                    placeholder="<?php echo iN_HelpSecure($LANG['write_something_about_the_post']); ?>"
                    aria-label="<?php echo iN_HelpSecure($LANG['write_something_about_the_post']); ?>"
                ><?php 
                    if (!empty($posText)) {
                        echo iN_HelpSecure($iN->br2nl($posText)); 
                    } 
                ?></textarea>
            </div>
            <!-- /Post Editing Textarea -->

            <?php if (isset($postType) && $postType === 'poll') : ?>
                <?php
                    $pollOptions = isset($pollDetails['options']) && is_array($pollDetails['options']) ? $pollDetails['options'] : [];
                    $pollOptionCount = count($pollOptions);
                    $pollMin = isset($pollMinOptions) ? (int)$pollMinOptions : 2;
                    if ($pollOptionCount < $pollMin) {
                        for ($i = $pollOptionCount; $i < $pollMin; $i++) {
                            $pollOptions[] = ['option_id' => '', 'option_text' => ''];
                        }
                    }
                    $pollCsrf = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '');
                ?>
                <div class="i_warning_poll i_warning_poll_edit" role="alert" aria-live="polite"></div>
                <div
                    class="poll_builder active"
                    id="editPollBuilder"
                    data-poll="<?php echo iN_HelpSecure($pollDetails['poll_id'] ?? ''); ?>"
                    data-post="<?php echo iN_HelpSecure($postID); ?>"
                    data-max="<?php echo iN_HelpSecure($pollMaxOptions ?? 6); ?>"
                    data-min="<?php echo iN_HelpSecure($pollMinOptions ?? 2); ?>"
                    data-msg-max="<?php echo iN_HelpSecure($LANG['poll_option_limit_reached']); ?>"
                    data-msg-min="<?php echo iN_HelpSecure($LANG['poll_need_more_options']); ?>"
                    data-msg-disabled="<?php echo iN_HelpSecure($LANG['poll_disabled_now'] ?? ''); ?>"
                    data-csrf="<?php echo iN_HelpSecure($pollCsrf); ?>"
                >
                    <div class="poll_builder_head flex_ tabing_non_justify">
                        <div class="poll_builder_title"><?php echo iN_HelpSecure($LANG['poll_builder_title']); ?></div>
                    </div>
                    <div class="poll_options_wrapper">
                        <?php foreach ($pollOptions as $option) { ?>
                            <div class="poll_option_input" data-option-id="<?php echo iN_HelpSecure($option['option_id'] ?? ''); ?>">
                                <input
                                    type="text"
                                    class="poll_option_field i_input"
                                    placeholder="<?php echo iN_HelpSecure($LANG['poll_option_placeholder']); ?>"
                                    value="<?php echo iN_HelpSecure($option['option_text'] ?? ''); ?>"
                                >
                                <div class="remove_poll_option transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['delete'] ?? 'Delete'); ?>">&times;</div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="poll_actions flex_ tabing_non_justify">
                        <div class="add_poll_option transition">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?>
                            <span><?php echo iN_HelpSecure($LANG['poll_add_option']); ?></span>
                        </div>
                        <div class="poll_limit_notice">
                            <?php echo preg_replace('/{count}/', iN_HelpSecure($pollMaxOptions ?? 6), $LANG['poll_option_limit_notice']); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal Footer -->
            <div class="i_modal_g_footer">
                <div 
                    class="shareBtn sedt transition" 
                    id="<?php echo iN_HelpSecure($postID); ?>" 
                    role="button" 
                    aria-label="<?php echo iN_HelpSecure($LANG['save_edit']); ?>"
                >
                    <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                </div>
            </div>
            <!-- /Modal Footer -->
        </div>
    </div>
    <!-- /Modal Container -->
</div>
