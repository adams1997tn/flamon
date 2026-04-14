<?php
$ageVerifProvider = isset($ageVerifProvider) ? (string)$ageVerifProvider : 'ageverif';
$ageVerifProvider = in_array($ageVerifProvider, ['ageverif', 'yoti', 'didit'], true) ? $ageVerifProvider : 'ageverif';
$ageVerifStatus = isset($ageVerifStatus) ? (string)$ageVerifStatus : '0';
$ageVerifForceSitewide = isset($ageVerifForceSitewide) ? (string)$ageVerifForceSitewide : '0';
$ageVerifEnvironment = isset($ageVerifEnvironment) ? (string)$ageVerifEnvironment : 'live';
$ageVerifEnvironment = $ageVerifEnvironment === 'test' ? 'test' : 'live';
$ageVerifClientIdLive = isset($ageVerifClientIdLive) ? (string)$ageVerifClientIdLive : (isset($ageVerifClientId) ? (string)$ageVerifClientId : '');
$ageVerifAuthorizeUrlLive = isset($ageVerifAuthorizeUrlLive) ? (string)$ageVerifAuthorizeUrlLive : (isset($ageVerifAuthorizeUrl) ? (string)$ageVerifAuthorizeUrl : '');
$ageVerifTokenUrlLive = isset($ageVerifTokenUrlLive) ? (string)$ageVerifTokenUrlLive : (isset($ageVerifTokenUrl) ? (string)$ageVerifTokenUrl : '');
$ageVerifVerifyUrlLive = isset($ageVerifVerifyUrlLive) ? (string)$ageVerifVerifyUrlLive : (isset($ageVerifVerifyUrl) ? (string)$ageVerifVerifyUrl : '');
$ageVerifScopeLive = isset($ageVerifScopeLive) ? (string)$ageVerifScopeLive : (isset($ageVerifScope) ? (string)$ageVerifScope : '');
$ageVerifClientSecretLive = isset($ageVerifClientSecretLive) ? (string)$ageVerifClientSecretLive : (isset($ageVerifClientSecret) ? (string)$ageVerifClientSecret : '');
$ageVerifClientIdTest = isset($ageVerifClientIdTest) ? (string)$ageVerifClientIdTest : '';
$ageVerifAuthorizeUrlTest = isset($ageVerifAuthorizeUrlTest) ? (string)$ageVerifAuthorizeUrlTest : '';
$ageVerifTokenUrlTest = isset($ageVerifTokenUrlTest) ? (string)$ageVerifTokenUrlTest : '';
$ageVerifVerifyUrlTest = isset($ageVerifVerifyUrlTest) ? (string)$ageVerifVerifyUrlTest : '';
$ageVerifScopeTest = isset($ageVerifScopeTest) ? (string)$ageVerifScopeTest : '';
$ageVerifClientSecretTest = isset($ageVerifClientSecretTest) ? (string)$ageVerifClientSecretTest : '';
$ageVerifMinAge = isset($ageVerifMinAge) ? (int)$ageVerifMinAge : 18;
if ($ageVerifMinAge < 18) {
    $ageVerifMinAge = 18;
}
$yotiAgeVerifStatus = isset($yotiAgeVerifStatus) ? (string)$yotiAgeVerifStatus : '0';
$yotiAgeVerifForceSitewide = isset($yotiAgeVerifForceSitewide) ? (string)$yotiAgeVerifForceSitewide : '0';
$yotiAgeVerifEnvironment = isset($yotiAgeVerifEnvironment) ? (string)$yotiAgeVerifEnvironment : 'live';
$yotiAgeVerifEnvironment = $yotiAgeVerifEnvironment === 'test' ? 'test' : 'live';
$yotiAgeVerifClientIdLive = isset($yotiAgeVerifClientIdLive) ? (string)$yotiAgeVerifClientIdLive : (isset($yotiAgeVerifClientId) ? (string)$yotiAgeVerifClientId : '');
$yotiAgeVerifAuthorizeUrlLive = isset($yotiAgeVerifAuthorizeUrlLive) ? (string)$yotiAgeVerifAuthorizeUrlLive : (isset($yotiAgeVerifAuthorizeUrl) ? (string)$yotiAgeVerifAuthorizeUrl : '');
$yotiAgeVerifTokenUrlLive = isset($yotiAgeVerifTokenUrlLive) ? (string)$yotiAgeVerifTokenUrlLive : (isset($yotiAgeVerifTokenUrl) ? (string)$yotiAgeVerifTokenUrl : '');
$yotiAgeVerifVerifyUrlLive = isset($yotiAgeVerifVerifyUrlLive) ? (string)$yotiAgeVerifVerifyUrlLive : (isset($yotiAgeVerifVerifyUrl) ? (string)$yotiAgeVerifVerifyUrl : '');
$yotiAgeVerifScopeLive = isset($yotiAgeVerifScopeLive) ? (string)$yotiAgeVerifScopeLive : (isset($yotiAgeVerifScope) ? (string)$yotiAgeVerifScope : '');
$yotiAgeVerifClientSecretLive = isset($yotiAgeVerifClientSecretLive) ? (string)$yotiAgeVerifClientSecretLive : (isset($yotiAgeVerifClientSecret) ? (string)$yotiAgeVerifClientSecret : '');
$yotiAgeVerifClientIdTest = isset($yotiAgeVerifClientIdTest) ? (string)$yotiAgeVerifClientIdTest : '';
$yotiAgeVerifAuthorizeUrlTest = isset($yotiAgeVerifAuthorizeUrlTest) ? (string)$yotiAgeVerifAuthorizeUrlTest : '';
$yotiAgeVerifTokenUrlTest = isset($yotiAgeVerifTokenUrlTest) ? (string)$yotiAgeVerifTokenUrlTest : '';
$yotiAgeVerifVerifyUrlTest = isset($yotiAgeVerifVerifyUrlTest) ? (string)$yotiAgeVerifVerifyUrlTest : '';
$yotiAgeVerifScopeTest = isset($yotiAgeVerifScopeTest) ? (string)$yotiAgeVerifScopeTest : '';
$yotiAgeVerifClientSecretTest = isset($yotiAgeVerifClientSecretTest) ? (string)$yotiAgeVerifClientSecretTest : '';
$yotiAgeVerifMinAge = isset($yotiAgeVerifMinAge) ? (int)$yotiAgeVerifMinAge : 18;
if ($yotiAgeVerifMinAge < 18) {
    $yotiAgeVerifMinAge = 18;
}
$diditAgeVerifStatus = isset($diditAgeVerifStatus) ? (string)$diditAgeVerifStatus : '0';
$diditAgeVerifForceSitewide = isset($diditAgeVerifForceSitewide) ? (string)$diditAgeVerifForceSitewide : '0';
$diditAgeVerifEnvironment = isset($diditAgeVerifEnvironment) ? (string)$diditAgeVerifEnvironment : 'live';
$diditAgeVerifEnvironment = $diditAgeVerifEnvironment === 'test' ? 'test' : 'live';
$diditAgeVerifClientIdLive = isset($diditAgeVerifClientIdLive) ? (string)$diditAgeVerifClientIdLive : (isset($diditAgeVerifClientId) ? (string)$diditAgeVerifClientId : '');
$diditAgeVerifAuthorizeUrlLive = isset($diditAgeVerifAuthorizeUrlLive) ? (string)$diditAgeVerifAuthorizeUrlLive : (isset($diditAgeVerifAuthorizeUrl) ? (string)$diditAgeVerifAuthorizeUrl : '');
$diditAgeVerifTokenUrlLive = isset($diditAgeVerifTokenUrlLive) ? (string)$diditAgeVerifTokenUrlLive : (isset($diditAgeVerifTokenUrl) ? (string)$diditAgeVerifTokenUrl : '');
$diditAgeVerifVerifyUrlLive = isset($diditAgeVerifVerifyUrlLive) ? (string)$diditAgeVerifVerifyUrlLive : (isset($diditAgeVerifVerifyUrl) ? (string)$diditAgeVerifVerifyUrl : '');
$diditAgeVerifScopeLive = isset($diditAgeVerifScopeLive) ? (string)$diditAgeVerifScopeLive : (isset($diditAgeVerifScope) ? (string)$diditAgeVerifScope : '');
$diditAgeVerifClientSecretLive = isset($diditAgeVerifClientSecretLive) ? (string)$diditAgeVerifClientSecretLive : (isset($diditAgeVerifClientSecret) ? (string)$diditAgeVerifClientSecret : '');
$diditAgeVerifClientIdTest = isset($diditAgeVerifClientIdTest) ? (string)$diditAgeVerifClientIdTest : '';
$diditAgeVerifAuthorizeUrlTest = isset($diditAgeVerifAuthorizeUrlTest) ? (string)$diditAgeVerifAuthorizeUrlTest : '';
$diditAgeVerifTokenUrlTest = isset($diditAgeVerifTokenUrlTest) ? (string)$diditAgeVerifTokenUrlTest : '';
$diditAgeVerifVerifyUrlTest = isset($diditAgeVerifVerifyUrlTest) ? (string)$diditAgeVerifVerifyUrlTest : '';
$diditAgeVerifScopeTest = isset($diditAgeVerifScopeTest) ? (string)$diditAgeVerifScopeTest : '';
$diditAgeVerifClientSecretTest = isset($diditAgeVerifClientSecretTest) ? (string)$diditAgeVerifClientSecretTest : '';
$diditAgeVerifMinAge = isset($diditAgeVerifMinAge) ? (int)$diditAgeVerifMinAge : 18;
if ($diditAgeVerifMinAge < 18) {
    $diditAgeVerifMinAge = 18;
}
$callbackUrlPrimary = function_exists('route_url')
    ? route_url('age-verification-callback')
    : rtrim((string)$base_url, '/') . '/age-verification-callback';
$callbackUrlIndex = rtrim((string)$base_url, '/') . '/index.php/age-verification-callback';
$callbackUrlPrimarySafe = function_exists('iN_HelpSecure')
    ? iN_HelpSecure($callbackUrlPrimary)
    : htmlspecialchars($callbackUrlPrimary, ENT_QUOTES, 'UTF-8');
$callbackUrlIndexSafe = function_exists('iN_HelpSecure')
    ? iN_HelpSecure($callbackUrlIndex)
    : htmlspecialchars($callbackUrlIndex, ENT_QUOTES, 'UTF-8');
$diditWebhookUrl = rtrim((string)$base_url, '/') . '/requests/request.php?f=diditWebhook';
$diditWebhookUrlSafe = function_exists('iN_HelpSecure')
    ? iN_HelpSecure($diditWebhookUrl)
    : htmlspecialchars($diditWebhookUrl, ENT_QUOTES, 'UTF-8');
$redirectNoteKey = ($callbackUrlPrimarySafe === $callbackUrlIndexSafe)
    ? 'age_verification_note_redirect_uri_single'
    : 'age_verification_note_redirect_uri_dual';
$redirectNoteKeyGeneric = ($callbackUrlPrimarySafe === $callbackUrlIndexSafe)
    ? 'age_verification_note_redirect_uri_generic_single'
    : 'age_verification_note_redirect_uri_generic_dual';
$secretPlaceholderLive = !empty($ageVerifClientSecretLive) ? '********' : '';
$secretPlaceholderTest = !empty($ageVerifClientSecretTest) ? '********' : '';
$secretPlaceholderYotiLive = !empty($yotiAgeVerifClientSecretLive) ? '********' : '';
$secretPlaceholderYotiTest = !empty($yotiAgeVerifClientSecretTest) ? '********' : '';
$secretPlaceholderDiditLive = !empty($diditAgeVerifClientSecretLive) ? '********' : '';
$secretPlaceholderDiditTest = !empty($diditAgeVerifClientSecretTest) ? '********' : '';
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['age_verification_settings_title']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <form enctype="multipart/form-data" method="post" id="ageVerifSettingsForm">
                <input type="hidden" name="f" value="ageVerifSettings">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="rec_not"><?php echo iN_HelpSecure($LANG['age_verification_settings_desc']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_provider_label']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_box_limit flex_ column">
                            <select name="age_verif_provider" class="i_input">
                                <option value="ageverif" <?php echo $ageVerifProvider === 'ageverif' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['age_verification_provider_ageverif']); ?>
                                </option>
                                <option value="yoti" <?php echo $ageVerifProvider === 'yoti' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['age_verification_provider_yoti']); ?>
                                </option>
                                <option value="didit" <?php echo $ageVerifProvider === 'didit' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['age_verification_provider_didit']); ?>
                                </option>
                            </select>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_provider_note']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="ageverif-provider-section" data-provider="ageverif">
                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_provider_ageverif']); ?></div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_docs_placeholder']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="ageVerifStatus">
                            <input type="hidden" name="age_verif_status" value="0">
                            <input
                                type="checkbox"
                                name="age_verif_status"
                                id="ageVerifStatus"
                                value="1"
                                <?php echo $ageVerifStatus === '1' ? 'checked="checked"' : ''; ?>
                            >
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['age_verification_status_label']); ?></div>
                    </div>
                    <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_status']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="ageVerifForce">
                            <input type="hidden" name="age_verif_force_sitewide" value="0">
                            <input
                                type="checkbox"
                                name="age_verif_force_sitewide"
                                id="ageVerifForce"
                                value="1"
                                <?php echo $ageVerifForceSitewide === '1' ? 'checked="checked"' : ''; ?>
                            >
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['age_verification_force_sitewide']); ?></div>
                    </div>
                    <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_force_sitewide']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_environment_label']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_box_limit flex_ column">
                            <select name="age_verif_environment" class="i_input ageverif-env-select" data-provider="ageverif">
                                <option value="live" <?php echo $ageVerifEnvironment === 'live' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['age_verification_environment_live']); ?>
                                </option>
                                <option value="test" <?php echo $ageVerifEnvironment === 'test' ? 'selected' : ''; ?>>
                                    <?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>
                                </option>
                            </select>
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_environment_note']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="ageverif-env-section ageverif-env-live" data-provider="ageverif" data-env="live">
                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_section_live']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_client_id']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_client_id" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifClientIdLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_client_id']); ?></div>
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode(str_replace(['{url}', '{url_index}'], [$callbackUrlPrimarySafe, $callbackUrlIndexSafe], $LANG[$redirectNoteKey])); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_client_secret']); ?></div>
                        <div class="irow_box_right">
                            <input type="password" name="age_verif_client_secret" class="i_input flex_" value="" autocomplete="new-password" placeholder="<?php echo iN_HelpSecure($secretPlaceholderLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_client_secret']); ?></div>
                            <?php if (!empty($ageVerifClientSecretLive)) { ?>
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_secret_saved']); ?></div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_authorize_url']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_authorize_url" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifAuthorizeUrlLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_authorize_url']); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_token_url']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_token_url" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifTokenUrlLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_token_url']); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_verify_url']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_verify_url" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifVerifyUrlLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_verify_url']); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_scope']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_scope" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifScopeLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_scope']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="ageverif-env-section ageverif-env-test" data-provider="ageverif" data-env="test">
                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_section_test']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_client_id']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_client_id_test" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifClientIdTest); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_client_id']); ?></div>
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode(str_replace(['{url}', '{url_index}'], [$callbackUrlPrimarySafe, $callbackUrlIndexSafe], $LANG[$redirectNoteKey])); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_client_secret']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                        <div class="irow_box_right">
                            <input type="password" name="age_verif_client_secret_test" class="i_input flex_" value="" autocomplete="new-password" placeholder="<?php echo iN_HelpSecure($secretPlaceholderTest); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_client_secret']); ?></div>
                            <?php if (!empty($ageVerifClientSecretTest)) { ?>
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_secret_saved']); ?></div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_authorize_url']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                        <div class="irow_box_right">
                            <input type="text" name="age_verif_authorize_url_test" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifAuthorizeUrlTest); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_authorize_url']); ?></div>
                        </div>
                    </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_token_url']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                    <div class="irow_box_right">
                        <input type="text" name="age_verif_token_url_test" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifTokenUrlTest); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_token_url']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_verify_url']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                    <div class="irow_box_right">
                        <input type="text" name="age_verif_verify_url_test" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifVerifyUrlTest); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_verify_url']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_scope']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                    <div class="irow_box_right">
                        <input type="text" name="age_verif_scope_test" class="i_input flex_" value="<?php echo iN_HelpSecure($ageVerifScopeTest); ?>">
                        <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_scope']); ?></div>
                    </div>
                </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_min_age']); ?></div>
                    <div class="irow_box_right">
                        <?php
                        $minAgeValue = (int)$ageVerifMinAge;
                        if ($minAgeValue < 18 || $minAgeValue > 60) {
                            $minAgeValue = 18;
                        }
                        ?>
                        <select name="age_verif_min_age" class="i_input flex_">
                            <?php for ($age = 18; $age <= 60; $age++) { ?>
                                <option value="<?php echo (int)$age; ?>"<?php echo $minAgeValue === $age ? ' selected="selected"' : ''; ?>><?php echo (int)$age; ?></option>
                            <?php } ?>
                        </select>
                        <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_min_age']); ?></div>
                    </div>
                </div>
                </div>

                <div class="ageverif-provider-section" data-provider="yoti">
                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_provider_yoti']); ?></div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_docs_placeholder']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                            <label class="el-switch el-switch-yellow" for="yotiAgeVerifStatus">
                                <input type="hidden" name="yoti_age_verif_status" value="0">
                                <input
                                    type="checkbox"
                                    name="yoti_age_verif_status"
                                    id="yotiAgeVerifStatus"
                                    value="1"
                                    <?php echo $yotiAgeVerifStatus === '1' ? 'checked="checked"' : ''; ?>
                                >
                                <span class="el-switch-style"></span>
                            </label>
                            <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['age_verification_status_label']); ?></div>
                        </div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_status_generic']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                            <label class="el-switch el-switch-yellow" for="yotiAgeVerifForce">
                                <input type="hidden" name="yoti_age_verif_force_sitewide" value="0">
                                <input
                                    type="checkbox"
                                    name="yoti_age_verif_force_sitewide"
                                    id="yotiAgeVerifForce"
                                    value="1"
                                    <?php echo $yotiAgeVerifForceSitewide === '1' ? 'checked="checked"' : ''; ?>
                                >
                                <span class="el-switch-style"></span>
                            </label>
                            <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['age_verification_force_sitewide']); ?></div>
                        </div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_force_sitewide_generic']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_min_age']); ?></div>
                        <div class="irow_box_right">
                            <?php
                            $yotiMinAgeValue = (int)$yotiAgeVerifMinAge;
                            if ($yotiMinAgeValue < 19 || $yotiMinAgeValue > 25) {
                                $yotiMinAgeValue = 19;
                            }
                            ?>
                            <select name="yoti_age_verif_min_age" class="i_input flex_">
                                <?php for ($age = 19; $age <= 25; $age++) { ?>
                                    <option value="<?php echo (int)$age; ?>"<?php echo $yotiMinAgeValue === $age ? ' selected="selected"' : ''; ?>><?php echo (int)$age; ?></option>
                                <?php } ?>
                            </select>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_min_age_generic']); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_yoti_sdk_id_label']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" name="yoti_age_verif_client_id" class="i_input flex_" value="<?php echo iN_HelpSecure($yotiAgeVerifClientIdLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_id_generic']); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_yoti_api_key_label']); ?></div>
                        <div class="irow_box_right">
                            <input type="password" name="yoti_age_verif_client_secret" class="i_input flex_" value="" autocomplete="new-password" placeholder="<?php echo iN_HelpSecure($secretPlaceholderYotiLive); ?>">
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_secret_generic']); ?></div>
                            <?php if (!empty($yotiAgeVerifClientSecretLive)) { ?>
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_secret_saved']); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="ageverif-provider-section" data-provider="didit">
                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_provider_didit']); ?></div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_docs_placeholder']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                            <label class="el-switch el-switch-yellow" for="diditAgeVerifStatus">
                                <input type="hidden" name="didit_age_verif_status" value="0">
                                <input
                                    type="checkbox"
                                    name="didit_age_verif_status"
                                    id="diditAgeVerifStatus"
                                    value="1"
                                    <?php echo $diditAgeVerifStatus === '1' ? 'checked="checked"' : ''; ?>
                                >
                                <span class="el-switch-style"></span>
                            </label>
                            <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['age_verification_status_label']); ?></div>
                        </div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_status_generic']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ column tabing__justify">
                        <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                            <label class="el-switch el-switch-yellow" for="diditAgeVerifForce">
                                <input type="hidden" name="didit_age_verif_force_sitewide" value="0">
                                <input
                                    type="checkbox"
                                    name="didit_age_verif_force_sitewide"
                                    id="diditAgeVerifForce"
                                    value="1"
                                    <?php echo $diditAgeVerifForceSitewide === '1' ? 'checked="checked"' : ''; ?>
                                >
                                <span class="el-switch-style"></span>
                            </label>
                            <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['age_verification_force_sitewide']); ?></div>
                        </div>
                        <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_force_sitewide_generic']); ?></div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_webhook_url_label']); ?></div>
                        <div class="irow_box_right">
                            <input type="text" class="i_input flex_" value="<?php echo $diditWebhookUrlSafe; ?>" readonly>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_didit_webhook_url']); ?></div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_environment_label']); ?></div>
                        <div class="irow_box_right">
                            <div class="i_box_limit flex_ column">
                                <select name="didit_age_verif_environment" class="i_input ageverif-env-select" data-provider="didit">
                                    <option value="live" <?php echo $diditAgeVerifEnvironment === 'live' ? 'selected' : ''; ?>>
                                        <?php echo iN_HelpSecure($LANG['age_verification_environment_live']); ?>
                                    </option>
                                    <option value="test" <?php echo $diditAgeVerifEnvironment === 'test' ? 'selected' : ''; ?>>
                                        <?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>
                                    </option>
                                </select>
                                <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_environment_note']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="ageverif-env-section ageverif-env-live" data-provider="didit" data-env="live">
                        <div class="i_general_row_box_item flex_ column tabing__justify">
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_section_live']); ?></div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_api_key_label']); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="didit_age_verif_client_id" class="i_input flex_" value="<?php echo iN_HelpSecure($diditAgeVerifClientIdLive); ?>">
                                <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_didit_api_key']); ?></div>
                            </div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_webhook_secret_label']); ?></div>
                            <div class="irow_box_right">
                                <input type="password" name="didit_age_verif_client_secret" class="i_input flex_" value="" autocomplete="new-password" placeholder="<?php echo iN_HelpSecure($secretPlaceholderDiditLive); ?>">
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_didit_webhook_secret']); ?></div>
                                <?php if (!empty($diditAgeVerifClientSecretLive)) { ?>
                                    <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_secret_saved']); ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_workflow_id_label']); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="didit_age_verif_authorize_url" class="i_input flex_" value="<?php echo iN_HelpSecure($diditAgeVerifAuthorizeUrlLive); ?>">
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_didit_workflow_id']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="ageverif-env-section ageverif-env-test" data-provider="didit" data-env="test">
                        <div class="i_general_row_box_item flex_ column tabing__justify">
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_section_test']); ?></div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_api_key_label']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                            <div class="irow_box_right">
                                <input type="text" name="didit_age_verif_client_id_test" class="i_input flex_" value="<?php echo iN_HelpSecure($diditAgeVerifClientIdTest); ?>">
                                <div class="rec_not box_not_padding_top"><?php echo html_entity_decode($LANG['age_verification_note_didit_api_key']); ?></div>
                            </div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_webhook_secret_label']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                            <div class="irow_box_right">
                                <input type="password" name="didit_age_verif_client_secret_test" class="i_input flex_" value="" autocomplete="new-password" placeholder="<?php echo iN_HelpSecure($secretPlaceholderDiditTest); ?>">
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_didit_webhook_secret']); ?></div>
                                <?php if (!empty($diditAgeVerifClientSecretTest)) { ?>
                                    <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_client_secret_saved']); ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_didit_workflow_id_label']); ?> (<?php echo iN_HelpSecure($LANG['age_verification_environment_test']); ?>)</div>
                            <div class="irow_box_right">
                                <input type="text" name="didit_age_verif_authorize_url_test" class="i_input flex_" value="<?php echo iN_HelpSecure($diditAgeVerifAuthorizeUrlTest); ?>">
                                <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_didit_workflow_id']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_verification_min_age']); ?></div>
                        <div class="irow_box_right">
                            <?php
                            $diditMinAgeValue = (int)$diditAgeVerifMinAge;
                            if ($diditMinAgeValue < 18 || $diditMinAgeValue > 60) {
                                $diditMinAgeValue = 18;
                            }
                            ?>
                            <select name="didit_age_verif_min_age" class="i_input flex_">
                                <?php for ($age = 18; $age <= 60; $age++) { ?>
                                    <option value="<?php echo (int)$age; ?>"<?php echo $diditMinAgeValue === $age ? ' selected="selected"' : ''; ?>><?php echo (int)$age; ?></option>
                                <?php } ?>
                            </select>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['age_verification_note_didit_min_age']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
                <div class="warning_ warning_ageverif_incomplete"><?php echo iN_HelpSecure($LANG['age_verification_error_config_incomplete_admin'] ?? $LANG['age_verification_error_config_incomplete']); ?></div>
                <div class="warning_ warning_ageverif_missing_client_id"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_id']); ?></div>
                <div class="warning_ warning_ageverif_missing_client_secret"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_secret']); ?></div>
                <div class="warning_ warning_ageverif_missing_authorize_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_authorize_url']); ?></div>
                <div class="warning_ warning_ageverif_missing_token_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_token_url']); ?></div>
                <div class="warning_ warning_ageverif_invalid_authorize_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_authorize_url']); ?></div>
                <div class="warning_ warning_ageverif_invalid_token_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_token_url']); ?></div>
                <div class="warning_ warning_ageverif_invalid_verify_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_verify_url']); ?></div>
                <div class="warning_ warning_ageverif_missing_client_id_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_id_test']); ?></div>
                <div class="warning_ warning_ageverif_missing_client_secret_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_secret_test']); ?></div>
                <div class="warning_ warning_ageverif_missing_authorize_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_authorize_url_test']); ?></div>
                <div class="warning_ warning_ageverif_missing_token_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_token_url_test']); ?></div>
                <div class="warning_ warning_ageverif_invalid_authorize_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_authorize_url_test']); ?></div>
                <div class="warning_ warning_ageverif_invalid_token_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_token_url_test']); ?></div>
                <div class="warning_ warning_ageverif_invalid_verify_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_verify_url_test']); ?></div>
                <div class="warning_ warning_yoti_missing_client_id"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_id']); ?></div>
                <div class="warning_ warning_yoti_missing_client_secret"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_secret']); ?></div>
                <div class="warning_ warning_yoti_missing_authorize_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_authorize_url']); ?></div>
                <div class="warning_ warning_yoti_missing_token_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_token_url']); ?></div>
                <div class="warning_ warning_yoti_invalid_authorize_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_authorize_url']); ?></div>
                <div class="warning_ warning_yoti_invalid_token_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_token_url']); ?></div>
                <div class="warning_ warning_yoti_invalid_verify_url"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_verify_url']); ?></div>
                <div class="warning_ warning_yoti_missing_client_id_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_id_test']); ?></div>
                <div class="warning_ warning_yoti_missing_client_secret_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_client_secret_test']); ?></div>
                <div class="warning_ warning_yoti_missing_authorize_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_authorize_url_test']); ?></div>
                <div class="warning_ warning_yoti_missing_token_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_token_url_test']); ?></div>
                <div class="warning_ warning_yoti_invalid_authorize_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_authorize_url_test']); ?></div>
                <div class="warning_ warning_yoti_invalid_token_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_token_url_test']); ?></div>
                <div class="warning_ warning_yoti_invalid_verify_url_test"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_verify_url_test']); ?></div>
                <div class="warning_ warning_didit_missing_api_key"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_didit_api_key']); ?></div>
                <div class="warning_ warning_didit_invalid_api_key"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_didit_api_key']); ?></div>
                <div class="warning_ warning_didit_missing_webhook_secret"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_didit_webhook_secret']); ?></div>
                <div class="warning_ warning_didit_invalid_webhook_secret"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_didit_webhook_secret']); ?></div>
                <div class="warning_ warning_didit_missing_workflow_id"><?php echo iN_HelpSecure($LANG['age_verification_warning_missing_didit_workflow_id']); ?></div>
                <div class="warning_ warning_didit_invalid_workflow_id"><?php echo iN_HelpSecure($LANG['age_verification_warning_invalid_didit_workflow_id']); ?></div>
                <div class="warning_ warning_ageverif_columns_missing"><?php echo iN_HelpSecure($LANG['age_verification_error_columns_missing']); ?></div>
                <div class="warning_ warning_ageverif_failed"><?php echo iN_HelpSecure($LANG['save_failed']); ?></div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition">
                        <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
