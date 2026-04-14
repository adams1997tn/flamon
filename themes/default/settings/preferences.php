<?php
$csrfToken = function_exists('csrf_get_token')
    ? csrf_get_token()
    : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$ageVerifEnabled = isset($ageVerificationStatus) && (string)$ageVerificationStatus === '1';
$ageVerified = isset($userAgeVerifyStatus) && (string)$userAgeVerifyStatus === '1';
$ageVerifRequired = isset($ageVerificationForceSitewide) && (string)$ageVerificationForceSitewide === '1';
$ageVerifFlash = $_SESSION['ageverif_flash'] ?? null;
if (isset($_SESSION['ageverif_flash'])) {
    unset($_SESSION['ageverif_flash']);
}
$ageVerifFlashMessage = '';
$ageVerifFlashType = 'error';
if (is_array($ageVerifFlash)) {
    $ageVerifFlashMessage = (string)($ageVerifFlash['message'] ?? '');
    $ageVerifFlashType = (string)($ageVerifFlash['type'] ?? 'error');
}
$ageVerifFlashClass = $ageVerifFlashType === 'success'
    ? 'age-verif-message--success'
    : 'age-verif-message--error';
?>
<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('105'));?><?php echo iN_HelpSecure($LANG['preferences']);?></div>
    </div>
    <div class="i_settings_wrapper_items">
    <div class="payouts_form_container">
    <div class="i_payout_methods_form_container">
    <!--SET SUBSCRIPTION FEE BOX-->
    <div class="i_set_subscription_fee_box email_not">
        <div class="i_sub_not i_preference">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('106'));?> <?php echo iN_HelpSecure($LANG['email_notification_settings']);?>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['allow_email_notifications']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="email_not">
                    <input type="checkbox" name="email_not" id="email_not" class="setChange" <?php echo iN_HelpSecure($notificationEmailStatus) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($notificationEmailStatus) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['allow_email_notification_not']);?></div>
    </div>
    <!--/SET SUBSCRIPTION FEE BOX-->
    <!-- Browser Web Push -->
    <div class="i_set_subscription_fee_box pref_top web_push_preferences">
        <div class="i_sub_not i_preference">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36'));?> <?php echo iN_HelpSecure($LANG['web_push_settings'] ?? 'Browser Push Notifications');?>
        </div>
        <div class="i_sub_not_check i_pref">
            <?php echo iN_HelpSecure($LANG['web_push_enable'] ?? 'Enable browser push notifications');?>
            <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="web_push_enabled_toggle">
                    <input
                        type="checkbox"
                        name="web_push_enabled_toggle"
                        id="web_push_enabled_toggle"
                        <?php echo (isset($userWebPushEnabled) && (string)$userWebPushEnabled === '1') ? 'checked="checked"' : ''; ?>
                    >
                    <span class="el-switch-style"></span>
                </label>
            </div>
        </div>
        <div class="box_not" id="web_push_status_text"><?php echo iN_HelpSecure($LANG['web_push_disabled_desc'] ?? 'Browser push notifications are disabled for this account.');?></div>
        <div class="box_not" id="web_push_runtime_msg"></div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['web_push_settings_desc'] ?? 'Manage browser push notifications per device. HTTPS is required except on localhost.');?></div>
    </div>
    <!--/ Browser Web Push -->
    <!--SET SUBSCRIPTION FEE BOX-->
    <div class="i_set_subscription_fee_box pref_top message_not">
        <div class="i_sub_not i_preference">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('38'));?> <?php echo iN_HelpSecure($LANG['message_notification_settings']);?>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['allow_message']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="message_not">
                    <input type="checkbox" name="message_not" id="message_not" class="setChange" <?php echo iN_HelpSecure($messageSendStatus) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($messageSendStatus) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['allow_message_not']);?></div>
        <!---->
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['only_subscriber_can_send_message']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="who_send_message_not">
                    <input type="checkbox" name="who_send_message_not" id="who_send_message_not" class="setChange" <?php echo iN_HelpSecure($whoCanSendYouMessage) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($whoCanSendYouMessage) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['only_subscriber_can_send_message_info']);?></div>
        <!---->
    </div>
    <!--/SET SUBSCRIPTION FEE BOX-->
    <!--SET SUBSCRIPTION FEE BOX-->
    <div class="i_set_subscription_fee_box pref_top show_hide_profile">
        <div class="i_sub_not i_preference">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10'));?> <?php echo iN_HelpSecure($LANG['profile_display_settings']);?>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['profile_display_settings_not']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_hide_profile">
                    <input type="checkbox" name="show_hide_profile" id="show_hide_profile" class="setChange" <?php echo iN_HelpSecure($showHidePostOnlineOffline) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showHidePostOnlineOffline) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['profile_display_settings_not_two']);?></div>
    </div>
    <!--/SET SUBSCRIPTION FEE BOX-->
    <!-- Profile info visibility -->
    <div class="i_set_subscription_fee_box pref_top profile_info_visibility">
        <div class="i_sub_not i_preference">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10'));?> <?php echo iN_HelpSecure($LANG['profile_info_visibility']);?>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_gender']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_gender">
                    <input type="checkbox" name="show_profile_gender" id="show_profile_gender" class="setChange" <?php echo iN_HelpSecure($showProfileGender) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileGender) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_age']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_age">
                    <input type="checkbox" name="show_profile_age" id="show_profile_age" class="setChange" <?php echo iN_HelpSecure($showProfileAge) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileAge) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_birthdate']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_birthdate">
                    <input type="checkbox" name="show_profile_birthdate" id="show_profile_birthdate" class="setChange" <?php echo iN_HelpSecure($showProfileBirthdate) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileBirthdate) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_category']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_category">
                    <input type="checkbox" name="show_profile_category" id="show_profile_category" class="setChange" <?php echo iN_HelpSecure($showProfileCategory) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileCategory) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_likes']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_likes">
                    <input type="checkbox" name="show_profile_likes" id="show_profile_likes" class="setChange" <?php echo iN_HelpSecure($showProfileLikes) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileLikes) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_comments']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_comments">
                    <input type="checkbox" name="show_profile_comments" id="show_profile_comments" class="setChange" <?php echo iN_HelpSecure($showProfileComments) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileComments) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_bio']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_bio">
                    <input type="checkbox" name="show_profile_bio" id="show_profile_bio" class="setChange" <?php echo iN_HelpSecure($showProfileBio) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileBio) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['show_profile_social']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="show_profile_social">
                    <input type="checkbox" name="show_profile_social" id="show_profile_social" class="setChange" <?php echo iN_HelpSecure($showProfileSocial) == '1' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($showProfileSocial) == '1' ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['profile_info_visibility_note']);?></div>
    </div>
    <!--/ Profile info visibility -->
    <!-- Connections visibility -->
    <div class="i_set_subscription_fee_box pref_top">
        <div class="i_sub_not i_preference">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('140'));?> <?php echo iN_HelpSecure($LANG['connections_visibility']);?>
        </div>
        <div class="i_sub_not_check i_pref">
        <?php echo iN_HelpSecure($LANG['connections_visibility_not']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="connections_visibility">
                    <input type="checkbox" name="connections_visibility" id="connections_visibility" class="setChange" <?php echo (isset($connectionsVisibility) && (string)$connectionsVisibility === '1') ? 'checked="checked"' : ''; ?> value="<?php echo (isset($connectionsVisibility) && (string)$connectionsVisibility === '1') ? '0' : '1';?>">
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
    </div>
    <!--/ Connections visibility -->
    <?php if ($ageVerifEnabled) { ?>
    <div class="i_set_subscription_fee_box pref_top age_verification_block">
        <div class="i_sub_not i_preference">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('14'));?> <?php echo iN_HelpSecure($LANG['age_verification_title']);?>
        </div>
        <?php if ($ageVerifFlashMessage !== '') { ?>
            <div class="i_settings_wrapper_item age-verif-message <?php echo iN_HelpSecure($ageVerifFlashClass); ?>">
                <?php echo iN_HelpSecure($ageVerifFlashMessage); ?>
            </div>
        <?php } ?>
        <div class="i_sub_not_check i_pref age-verif-status-row">
            <div class="age-verif-status-label"><?php echo iN_HelpSecure($LANG['age_verification_status_label']);?></div>
            <div class="age-verif-badge <?php echo $ageVerified ? 'age-verif-badge--ok' : 'age-verif-badge--warn'; ?>">
                <?php echo iN_HelpSecure($ageVerified ? $LANG['age_verification_status_verified'] : $LANG['age_verification_status_unverified']);?>
            </div>
        </div>
        <div class="i_sub_not_check i_pref age-verif-action-row">
            <button
                type="button"
                class="i_nex_btn_btn transition ageVerifStart"
                data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
                data-error="<?php echo iN_HelpSecure($LANG['age_verification_error_generic']); ?>"
                data-error-csrf="<?php echo iN_HelpSecure($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'); ?>"
                <?php echo $ageVerified ? 'disabled="disabled"' : ''; ?>
            >
                <?php echo iN_HelpSecure($LANG['age_verification_button']);?>
            </button>
        </div>
        <div class="box_not"><?php echo iN_HelpSecure($LANG['age_verification_desc']);?></div>
        <?php if ($ageVerifRequired && !$ageVerified) { ?>
            <div class="box_not age-verif-required"><?php echo iN_HelpSecure($LANG['age_verification_required']);?></div>
        <?php } ?>
        <div class="box_not age-verif-inline-message"></div>
    </div>
    <?php } ?>
   </div>
</div>
    </div>
    <div class="i_settings_wrapper_item successNot">
        <?php echo iN_HelpSecure($LANG['payment_settings_updated_success'])?>
    </div>
  </div>
</div>
