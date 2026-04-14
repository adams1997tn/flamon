<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
$maskedAiKey = '';
if (!empty($opanAiKey)) {
    $tail = substr((string)$opanAiKey, -4);
    $maskedAiKey = str_repeat('*', 12) . $tail;
}
$maskedAiKeyDisplay = $maskedAiKey !== '' ? $maskedAiKey : ($LANG['ai_key_not_set'] ?? 'Not set');
$aiModels = array(
    'gpt-4o',
    'gpt-4o-mini',
    'gpt-4.1',
    'gpt-4.1-mini',
    'gpt-4.1-nano',
    'gpt-4-turbo',
    'gpt-4',
    'gpt-3.5-turbo'
);
if (!empty($openAiModel) && !in_array($openAiModel, $aiModels, true)) {
    $aiModels[] = $openAiModel;
}
if (!empty($openAiFallbackModel) && !in_array($openAiFallbackModel, $aiModels, true)) {
    $aiModels[] = $openAiFallbackModel;
}
$aiUsageSummary = $iN->iN_GetAiUsageSummary($userID);
$lastErrorText = $openAiLastError !== '' ? $openAiLastError : ($LANG['ai_no_errors'] ?? 'No errors recorded.');
$lastErrorAt = $openAiLastErrorAt > 0 ? date('Y-m-d H:i', $openAiLastErrorAt) : '';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_generate_ai_content']); ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_general_row_box_item flex_ column tabing__justify">
                <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                    <label class="el-switch el-switch-yellow" for="ai_generator_status">
                        <input type="checkbox" name="maintenancemode" class="chmd" id="ai_generator_status" <?php echo iN_HelpSecure($openAiStatus) == '1' ? 'value="0" checked="checked"' : 'value="1"'; ?>>
                        <span class="el-switch-style"></span>
                    </label>
                    <div class="i_chck_text">
                        <?php echo iN_HelpSecure($LANG['ai_content_generation_status']); ?>
                    </div>
                    <div class="success_tick tabing flex_ sec_one ai_generator_status">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['ai_total_calls']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="rec_not box_not_padding_top">
                        <?php echo iN_HelpSecure((string)($aiUsageSummary['total_calls'] ?? 0)); ?>
                    </div>
                </div>
            </div>
            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['ai_total_credits_used']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="rec_not box_not_padding_top">
                        <?php echo iN_HelpSecure((string)($aiUsageSummary['total_credits'] ?? 0)); ?>
                    </div>
                </div>
            </div>
            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['ai_daily_usage']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="rec_not box_not_padding_top">
                        <?php echo iN_HelpSecure((string)($aiUsageSummary['daily_calls'] ?? 0)); ?>
                    </div>
                </div>
            </div>
            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['ai_last_error']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="rec_not box_not_padding_top">
                        <?php echo iN_HelpSecure($lastErrorText); ?>
                        <?php if ($lastErrorAt !== '') { ?>
                            (<?php echo iN_HelpSecure($lastErrorAt); ?>)
                        <?php } ?>
                    </div>
                </div>
            </div>

            <form enctype="multipart/form-data" method="post" id="updateAiCredit">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['openai_key']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="password" name="apiKey" class="i_input flex_" value="" placeholder="<?php echo iN_HelpSecure($LANG['openai_key_placeholder']); ?>" autocomplete="new-password">
                        <div class="rec_not box_not_padding_top">
                            <?php echo iN_HelpSecure($LANG['ai_masked_key_hint']); ?> <strong><?php echo iN_HelpSecure($maskedAiKeyDisplay); ?></strong>
                        </div>
                        <div class="rec_not box_not_padding_top">
                            <a href="https://platform.openai.com/api-keys" target="blank_"><?php echo iN_HelpSecure($LANG['get_openai_key']); ?></a>
                        </div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_model']); ?>
                    </div>
                    <div class="irow_box_right">
                        <select name="ai_model" class="i_input flex_">
                            <?php foreach ($aiModels as $model) { ?>
                                <option value="<?php echo iN_HelpSecure($model); ?>" <?php echo $model === $openAiModel ? 'selected="selected"' : ''; ?>>
                                    <?php echo iN_HelpSecure($model); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_fallback_model']); ?>
                    </div>
                    <div class="irow_box_right">
                        <select name="ai_fallback_model" class="i_input flex_">
                            <?php foreach ($aiModels as $model) { ?>
                                <option value="<?php echo iN_HelpSecure($model); ?>" <?php echo $model === $openAiFallbackModel ? 'selected="selected"' : ''; ?>>
                                    <?php echo iN_HelpSecure($model); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="rec_not box_not_padding_top">
                            <?php echo iN_HelpSecure($LANG['ai_fallback_model_note']); ?>
                        </div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_temperature']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" step="0.01" min="0" max="2" name="ai_temperature" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$openAiTemperature); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_max_tokens']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" min="1" max="4096" name="ai_max_tokens" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$openAiMaxTokens); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_prompt_max_length']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" min="50" max="2000" name="ai_prompt_max_length" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$openAiPromptMaxLength); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_rate_limit_minute']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" min="0" name="ai_rate_limit_minute" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$openAiRateLimitPerMinute); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_rate_limit_hour']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" min="0" name="ai_rate_limit_hour" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$openAiRateLimitPerHour); ?>">
                    </div>
                </div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_rate_limit_day']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" min="0" name="ai_rate_limit_day" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$openAiRateLimitPerDay); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_forbidden_terms']); ?>
                    </div>
                    <div class="irow_box_right">
                        <textarea name="ai_forbidden_terms" class="ai_more_textarea"><?php echo iN_HelpSecure($openAiForbiddenTerms); ?></textarea>
                        <div class="rec_not box_not_padding_top">
                            <?php echo iN_HelpSecure($LANG['ai_forbidden_terms_note']); ?>
                        </div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['credit_to_be_deducted_one_single_use']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" name="perAmount" class="i_input flex_" value="<?php echo iN_HelpSecure((string)$perAiUse); ?>">
                        <div class="rec_not box_not_padding_top">
                            <?php echo iN_HelpSecure($LANG['one_time_usage']); ?>
                        </div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['ai_health_check']); ?>
                    </div>
                    <div class="irow_box_right">
                        <button type="button" class="i_nex_btn_btn transition" id="aiHealthCheckBtn" data-checking="<?php echo iN_HelpSecure($LANG['ai_health_check_running']); ?>" data-error="<?php echo iN_HelpSecure($LANG['ai_health_check_failed']); ?>">
                            <?php echo iN_HelpSecure($LANG['ai_health_check_btn']); ?>
                        </button>
                        <div class="rec_not box_not_padding_top ai_health_result nonePoint"></div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot">
                    <?php echo iN_HelpSecure($LANG['updated_successfully']); ?>
                </div>

                <div class="admin_approve_post_footer">
                    <div class="i_become_creator_box_footer">
                        <input type="hidden" name="f" value="updateAiCredit">
                        <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_myprofile">
                            <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
