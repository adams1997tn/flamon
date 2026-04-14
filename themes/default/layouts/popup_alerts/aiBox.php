<div class="i_modal_bg_in ai_generator_modal" role="dialog" aria-modal="true" aria-labelledby="aiModalTitle" data-ai-source-required="<?php echo iN_HelpSecure($LANG['ai_source_required']); ?>" data-ai-prompt-required="<?php echo iN_HelpSecure($LANG['ai_prompt_required']); ?>" data-ai-generic-error="<?php echo iN_HelpSecure($LANG['ai_request_failed']); ?>">
    <!-- MODAL CONTENT -->
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <!-- MODAL HEADER -->
            <div class="i_modal_g_header" id="aiModalTitle">
                <?php echo iN_HelpSecure($LANG['generate_ai_content']); ?>
                <div class="shareClose transition" role="button" aria-label="Close Modal">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <!-- /MODAL HEADER -->

            <!-- MODAL BODY -->
            <div class="i_more_text_wrapper">
                <div class="i_warning_ai nonePoint" role="alert">
                    <?php echo iN_HelpSecure($LANG['please_check_api_key']); ?>
                </div>
                <div class="i_warning_ai_credit nonePoint" role="alert">
                    <?php echo iN_HelpSecure($LANG['no_enough_credit']); ?>
                </div>
                <div class="i_warning_ai_error nonePoint" role="alert"></div>

                <div class="i_editai_textarea_box ai_form_grid">
                    <input type="hidden" class="ai_csrf_token" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">
                    <div class="ai_form_row">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_prompt_template']); ?></label>
                        <select class="i_input aiTemplate">
                            <option value="caption"><?php echo iN_HelpSecure($LANG['ai_template_caption']); ?></option>
                            <option value="product"><?php echo iN_HelpSecure($LANG['ai_template_product']); ?></option>
                            <option value="hashtags"><?php echo iN_HelpSecure($LANG['ai_template_hashtags']); ?></option>
                            <option value="announcement"><?php echo iN_HelpSecure($LANG['ai_template_announcement']); ?></option>
                            <option value="bio"><?php echo iN_HelpSecure($LANG['ai_template_bio']); ?></option>
                        </select>
                    </div>
                    <div class="ai_form_row">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_action']); ?></label>
                        <select class="i_input aiAction">
                            <option value="generate"><?php echo iN_HelpSecure($LANG['ai_action_generate']); ?></option>
                            <option value="rewrite"><?php echo iN_HelpSecure($LANG['ai_action_rewrite']); ?></option>
                            <option value="shorten"><?php echo iN_HelpSecure($LANG['ai_action_shorten']); ?></option>
                            <option value="expand"><?php echo iN_HelpSecure($LANG['ai_action_expand']); ?></option>
                        </select>
                    </div>
                    <div class="ai_form_row">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_tone']); ?></label>
                        <select class="i_input aiTone">
                            <option value="neutral"><?php echo iN_HelpSecure($LANG['ai_tone_neutral']); ?></option>
                            <option value="friendly"><?php echo iN_HelpSecure($LANG['ai_tone_friendly']); ?></option>
                            <option value="professional"><?php echo iN_HelpSecure($LANG['ai_tone_professional']); ?></option>
                            <option value="witty"><?php echo iN_HelpSecure($LANG['ai_tone_witty']); ?></option>
                            <option value="bold"><?php echo iN_HelpSecure($LANG['ai_tone_bold']); ?></option>
                            <option value="playful"><?php echo iN_HelpSecure($LANG['ai_tone_playful']); ?></option>
                        </select>
                    </div>
                    <div class="ai_form_row">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_length']); ?></label>
                        <select class="i_input aiLength">
                            <option value="short"><?php echo iN_HelpSecure($LANG['ai_length_short']); ?></option>
                            <option value="medium" selected="selected"><?php echo iN_HelpSecure($LANG['ai_length_medium']); ?></option>
                            <option value="long"><?php echo iN_HelpSecure($LANG['ai_length_long']); ?></option>
                        </select>
                    </div>
                    <div class="ai_form_row">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_language']); ?></label>
                        <select class="i_input aiLanguage">
                            <option value="auto" selected="selected"><?php echo iN_HelpSecure($LANG['ai_language_auto']); ?></option>
                            <option value="english"><?php echo iN_HelpSecure($LANG['ai_language_english']); ?></option>
                            <option value="spanish"><?php echo iN_HelpSecure($LANG['ai_language_spanish']); ?></option>
                            <option value="french"><?php echo iN_HelpSecure($LANG['ai_language_french']); ?></option>
                            <option value="german"><?php echo iN_HelpSecure($LANG['ai_language_german']); ?></option>
                            <option value="turkish"><?php echo iN_HelpSecure($LANG['ai_language_turkish']); ?></option>
                            <option value="italian"><?php echo iN_HelpSecure($LANG['ai_language_italian']); ?></option>
                            <option value="portuguese"><?php echo iN_HelpSecure($LANG['ai_language_portuguese']); ?></option>
                        </select>
                    </div>
                    <div class="ai_form_row">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_variants']); ?></label>
                        <select class="i_input aiVariants">
                            <option value="1">1</option>
                            <option value="2" selected="selected">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                    <div class="ai_form_row ai_form_row_full">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['write_topic_for_ai_content']); ?></label>
                        <textarea
                            class="ai_more_textarea aiContT"
                            placeholder="<?php echo iN_HelpSecure($LANG['write_topic_for_ai_content']); ?>"
                            aria-label="<?php echo iN_HelpSecure($LANG['write_topic_for_ai_content']); ?>"></textarea>
                    </div>
                    <div class="ai_form_row ai_form_row_full ai_source_wrap nonePoint">
                        <label class="ai_form_label"><?php echo iN_HelpSecure($LANG['ai_existing_content']); ?></label>
                        <textarea
                            class="ai_more_textarea aiSourceT"
                            placeholder="<?php echo iN_HelpSecure($LANG['ai_existing_content']); ?>"
                            aria-label="<?php echo iN_HelpSecure($LANG['ai_existing_content']); ?>"></textarea>
                    </div>
                </div>

                <div class="ai_variants nonePoint" data-use="<?php echo iN_HelpSecure($LANG['ai_use_variant']); ?>">
                    <div class="ai_variants_title"><?php echo iN_HelpSecure($LANG['ai_generated_options']); ?></div>
                    <div class="ai_variants_list"></div>
                </div>

                <div class="free_live_not flex_ alignItem">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?>
                    <?php echo iN_HelpSecure($LANG['generate_not']); ?>
                </div>
            </div>
            <!-- /MODAL BODY -->

            <!-- MODAL FOOTER -->
            <div class="i_block_box_footer_container">
                <div class="alertBtnRightWithIcon createAiContent transition" role="button">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91')); ?>
                    <?php echo iN_HelpSecure($LANG['create']); ?>
                </div>
                <div class="alertBtnLeft no-del transition" role="button">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>
            <!-- /MODAL FOOTER -->
        </div>
    </div>
    <!-- /MODAL CONTENT -->
</div>
