<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['general_settings']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <div class="warning_">
                <?php echo iN_HelpSecure($LANG['noway_desc']); ?>
            </div>
            <?php
            $csrfToken = function_exists('csrf_get_token')
                ? csrf_get_token()
                : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');

            $accountDeletionWorkerStatus = (string)$iN->iN_GetSetting('account_deletion_worker_status', '1');
            $accountDeletionWorkerStatus = $accountDeletionWorkerStatus === '0' ? '0' : '1';
            $accountDeletionWorkerEnvToken = getenv('ACCOUNT_DELETION_WORKER_TOKEN') ?: '';
            $accountDeletionWorkerToken = $accountDeletionWorkerEnvToken !== ''
                ? $accountDeletionWorkerEnvToken
                : (string)$iN->iN_GetSetting('account_deletion_worker_token', '');

            if ($accountDeletionWorkerEnvToken === '' && $accountDeletionWorkerToken === '') {
                try {
                    $generatedWorkerToken = bin2hex(random_bytes(16));
                    if ($iN->iN_SetSetting('account_deletion_worker_token', $generatedWorkerToken)) {
                        $accountDeletionWorkerToken = $generatedWorkerToken;
                    }
                } catch (Throwable $e) {
                    $accountDeletionWorkerToken = '';
                }
            }

            $accountDeletionWorkerBase = defined('APP_ROOT_PATH') ? APP_ROOT_PATH : dirname(__DIR__, 3);
            $accountDeletionWorkerPath = rtrim((string)$accountDeletionWorkerBase, '/') . '/includes/account_deletion_worker.php';
            $accountDeletionWorkerCliCmd = 'php ' . $accountDeletionWorkerPath;
            $accountDeletionWorkerCronCmd = '*/15 * * * * ' . $accountDeletionWorkerCliCmd . ' >/dev/null 2>&1';
            $accountDeletionWorkerTokenValue = $accountDeletionWorkerToken !== '' ? $accountDeletionWorkerToken : 'YOUR_TOKEN';
            $accountDeletionWorkerUrl = $base_url . 'includes/account_deletion_worker.php?token=' . $accountDeletionWorkerTokenValue;
            $accountDeletionWorkerHttpCmd = 'curl -fsS "' . $accountDeletionWorkerUrl . '" >/dev/null 2>&1';
            ?>
            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['main_language']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="i_box_limit flex_ column">
                        <div class="i_limit" data-type="chm_limit">
                            <span class="lclt"><?php echo iN_HelpSecure($LANGNAME[$defaultLanguage]); ?></span>
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                        </div>
                        <div class="i_limit_list_cp_container">
                            <div class="i_countries_list border_one column flex_">
                                <?php foreach ($languages as $lang) { ?>
                                    <div class="i_s_limit transition border_one gsearch setDefault <?php echo iN_HelpSecure($defaultLanguage) == '' . $lang['lang_name'] . '' ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($lang['lang_id']); ?>" data-c="<?php echo iN_HelpSecure($LANGNAME[$lang['lang_name']]); ?>" data-type="language">
                                        <?php echo iN_HelpSecure($LANGNAME[$lang['lang_name']]); ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="main_lang" id="upcmLimit" value="<?php echo iN_HelpSecure($availableLength); ?>">
                        </div>
                        <div class="rec_not box_not_padding_top">
                            <?php echo iN_HelpSecure($LANG['main_lang_not']); ?>
                        </div>
                        <div class="success_tick tabing flex_ sec_one up_lng success_tick_style">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['default_theme_mode']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="i_box_limit flex_ column">
                        <select name="default_style_mode" id="default_style_mode" class="i_input">
                            <option value="light" <?php echo $defaultStyle === 'light' ? 'selected' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['theme_mode_light']); ?>
                            </option>
                            <option value="dark" <?php echo $defaultStyle === 'dark' ? 'selected' : ''; ?>>
                                <?php echo iN_HelpSecure($LANG['theme_mode_dark']); ?>
                            </option>
                        </select>
                        <div class="rec_not box_not_padding_top">
                            <?php echo iN_HelpSecure($LANG['default_theme_mode_not']); ?>
                        </div>
                        <div class="success_tick tabing flex_ sec_one default_style_mode success_tick_style">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $toggles = [
                ['id' => 'detect_lang_status', 'name' => 'maintenancemode', 'status' => $autoDetectLanguageStatus, 'label' => $LANG['auto_detect_language'], 'desc' => $LANG['auto_detect_language_not']],
                ['id' => 'maintenance_status', 'name' => 'maintenancemode', 'status' => $maintenanceMode, 'label' => $LANG['maintenance_mode'], 'desc' => $LANG['maintenance_mode_not']],
                ['id' => 'email_verification_status', 'name' => 'email_verification_status', 'status' => $emailSendStatus, 'label' => $LANG['email_verification_status'], 'desc' => $LANG['email_verification_not']],
                ['id' => 'send__email', 'name' => 'send__email', 'status' => $sendEmailForAll, 'label' => $LANG['send__email'], 'desc' => $LANG['send__email_not']],
                ['id' => 'register_new', 'name' => 'register_new', 'status' => $userCanRegister, 'label' => $LANG['register'], 'desc' => $LANG['allow_disallow_register']],
                ['id' => 'ipLimit', 'name' => 'ipLimit', 'status' => $ipLimitStatus, 'label' => $LANG['ip_limit'], 'desc' => $LANG['allow_disallow_register']],
                ['id' => 'account_deletion_worker_status', 'name' => 'account_deletion_worker_status', 'status' => $accountDeletionWorkerStatus, 'label' => $LANG['account_delete_worker_toggle_label'], 'desc' => $LANG['account_delete_worker_toggle_desc']]
            ];

            foreach ($toggles as $toggle) {
                $checked = iN_HelpSecure($toggle['status']) == '1' ? 'value="0" checked="checked"' : 'value="1"';
                ?>
                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="<?php echo $toggle['id']; ?>">
                            <input type="checkbox" name="<?php echo $toggle['name']; ?>" class="chmd" id="<?php echo $toggle['id']; ?>" <?php echo $checked; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text">
                            <?php echo iN_HelpSecure($toggle['label']); ?>
                        </div>
                        <div class="success_tick tabing flex_ sec_one <?php echo $toggle['id']; ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?>
                        </div>
                    </div>
                    <div class="rec_not box_not_padding_left">
                        <?php echo iN_HelpSecure($toggle['desc']); ?>
                    </div>
                </div>
            <?php } ?>

            <div class="i_general_row_box_item flex_ column tabing__justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['account_delete_worker_cron_title']); ?>
                </div>
                <div class="irow_box_right irow_box_right_style">
                    <div class="guide_box">
                        <div class="guide_body">
                            <ol>
                                <li>
                                    <strong><?php echo iN_HelpSecure($LANG['account_delete_worker_cron_step_1']); ?></strong>
                                    <div class="box_not_padding_top">
                                        <code class="limit-code"><?php echo iN_HelpSecure($accountDeletionWorkerCronCmd); ?></code>
                                    </div>
                                </li>
                                <li>
                                    <strong><?php echo iN_HelpSecure($LANG['account_delete_worker_cron_step_2']); ?></strong>
                                    <div class="box_not_padding_top">
                                        <code class="limit-code"><?php echo iN_HelpSecure($accountDeletionWorkerHttpCmd); ?></code>
                                    </div>
                                </li>
                                <li>
                                    <strong><?php echo iN_HelpSecure($LANG['account_delete_worker_cron_step_3']); ?></strong>
                                    <div class="box_not_padding_top">
                                        <?php echo iN_HelpSecure($LANG['account_delete_worker_token_note']); ?>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
