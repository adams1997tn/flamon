<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <?php
    $metaUrl = route_url('register');
    $metaImage = $metaBaseUrl;
    $canonicalUrl = $metaUrl;
    if (isset($_GET['ref']) && $_GET['ref'] != '' && !empty($_GET['ref'])) {
        $siteTitle = $LANG['ref_title'];
        $siteDescription = $LANG['ref_description'];
        $refUserName = isset($_GET['ref']) ? $iN->iN_Secure($_GET['ref']) : '';
        $metaUrl = route_url('register') . '?ref=' . rawurlencode($refUserName);
        $canonicalUrl = $metaUrl;
        $checkuserName = $iN->iN_CheckUserName($refUserName);
        if ($checkuserName) {
            $refOwnerData = $iN->iN_GetUserDetailsFromUsername($refUserName);
            $refOwnerUserID = $iN->iN_Secure($refOwnerData['iuid']);
            $metaImage = $iN->iN_UserAvatar($refOwnerUserID, $base_url);
        }
    }
    ?>
    <title><?php echo iN_HelpSecure($siteTitle);?></title>
    <!-- Primary Meta Tags -->
    <meta name="title" content="<?php echo iN_HelpSecure($siteTitle);?>">
    <meta name="description" content="<?php echo iN_HelpSecure($siteDescription);?>">
    <meta name="keywords" content="<?php echo iN_HelpSecure($siteKeyWords);?>">
    <link rel="canonical" href="<?php echo iN_HelpSecure($canonicalUrl); ?>">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo iN_HelpSecure($metaUrl);?>">
    <meta property="og:title" content="<?php echo iN_HelpSecure($siteTitle);?>">
    <meta property="og:description" content="<?php echo iN_HelpSecure($siteDescription);?>">
    <meta property="og:image" content="<?php echo iN_HelpSecure($metaImage);?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo iN_HelpSecure($metaUrl);?>">
    <meta property="twitter:title" content="<?php echo iN_HelpSecure($siteTitle);?>">
    <meta property="twitter:description" content="<?php echo iN_HelpSecure($siteDescription);?>">
    <meta property="twitter:image" content="<?php echo iN_HelpSecure($metaImage);?>">

    <meta name="theme-color" content="#f65169">
    <link rel="shortcut icon" type="image/png" href="<?php echo iN_HelpSecure($siteFavicon);?>" sizes="128x128">
    <?php
       include("layouts/header/css.php");
       include("layouts/header/javascripts.php");
    ?>
</head>
<body>
<?php if($logedIn == 0){ include('layouts/login_form.php'); }?>
<?php include("layouts/header/header.php");?>
<div class="wrapper bCreatorBg">
<?php if($userCanRegister == '1'){
    $claimName = '';
if(isset($_GET['claim']) && $_GET['claim'] != ''){
   $claimName = isset($_GET['claim']) ? $iN->iN_Secure($_GET['claim']) : '';
   $checkUserNameExist = $iN->iN_CheckUsernameExistForRegister($iN->iN_Secure($claimName));
   if($checkUserNameExist || empty($claimName) || $claimName == ''){
      $claimName = '';
   }
}
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
$registrationRoleModeValue = isset($registrationRoleMode)
    ? $iN->iN_NormalizeRegistrationRoleMode($registrationRoleMode)
    : $iN->iN_NormalizeRegistrationRoleMode($iN->iN_GetSetting('registration_role_mode', 'legacy'));
$signupIntentOptions = ['user'];
if ($registrationRoleModeValue === 'user_agency') {
    $signupIntentOptions = ['user', 'agency'];
} elseif ($registrationRoleModeValue === 'user_agency_creator') {
    $signupIntentOptions = ['user', 'agency', 'creator'];
}
?>
    <div class="i_become_creator_container"> 
        <div class="i_modal_content">
            <!--Register Header-->
            <div class="i_login_box_header">
                <div class="i_welcome_back">
                    <div class="i_lBack"><?php echo iN_HelpSecure($LANG['sign_up']);?></div>
                    <div class="i_lnot"><?php echo iN_HelpSecure($LANG['try_site_for_free']);?></div>
                </div>
            </div>
            <!--/Register Header-->
            <!--Register With-->
            <?php
            if($socialLoginStatus == '1'){
                $socialLogins = $iN->iN_SocialLogins();
                if($socialLogins){
                    echo '<!--Modal Social Login Content-->
                    <div class="i_modal_social_login_content">
                        <div><span>'.'</span></div><div class="i_social-btns">';
                    foreach($socialLogins as $sL){
                        $sKey = $sL['s_key'] ?? null;;
                        $sIcon = $sL['s_icon'] ?? null;;
                    ?>
                    <div><a class="<?php echo iN_HelpSecure($sKey);?>-login" href="<?php echo iN_HelpSecure($base_url).$sKey;?>Login.php"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon($sIcon));?><span><?php echo iN_HelpSecure($LANG[$sKey]);?></span></a></div>
                <?php }echo '</div><div><span>'.'</span></div>'; }
                echo '</div>';
                } ?>
                <div class="i_warns">
                <div class="i_error"></div>
                </div>
            <!--/Register With-->
            <div class="i_direct_register i_register_box_">
            <form enctype="multipart/form-data" method="post" id='iregister' autocomplete="off">
                <input type="hidden" name="registration_role_mode" value="<?php echo iN_HelpSecure($registrationRoleModeValue); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                <div class="i_settings_item_title_for flex_ gender-options-row">
                    <!---->
                    <?php
                    $renderedGenderIndex = 0;
                    foreach ($genderOptions as $genderOption) {
                        $genderValue = $genderOption['key'];
                        $sanitizedId = preg_replace('/[^a-z0-9_]/i', '', (string)$genderValue);
                        $inputId = 'gender_' . ($sanitizedId !== '' ? $sanitizedId : $renderedGenderIndex);
                        $iconId = isset($genderOption['icon']) && $genderOption['icon'] !== '' ? $genderOption['icon'] : '13';
                        $iconMarkup = html_entity_decode($iN->iN_SelectedMenuIcon($iconId));
                        if (!$iconMarkup) {
                            $iconMarkup = html_entity_decode($iN->iN_SelectedMenuIcon('13'));
                        }
                        $labelFallback = isset($genderOption['label']) && $genderOption['label'] !== '' ? $genderOption['label'] : ucfirst($genderValue);
                        $displayLabel = $LANG[$genderValue] ?? $labelFallback;
                    ?>
                    <?php
                        $labelId = $renderedGenderIndex === 0 ? 'youarein' : 'youare';
                    ?>
                    <div class="flexBox flex_">
                        <label class="youare flex_" id="<?php echo iN_HelpSecure($labelId); ?>" for="<?php echo iN_HelpSecure($inputId); ?>">
                            <input type="radio" name="gender" id="<?php echo iN_HelpSecure($inputId); ?>" value="<?php echo iN_HelpSecure($genderValue); ?>" <?php echo ($genderValue === $primaryGenderKey) ? "checked='checked'" : ""; ?>>
                            <span class="flex_ transition"><?php echo $iconMarkup; ?><?php echo iN_HelpSecure($displayLabel); ?></span>
                        </label>
                    </div>
                    <?php $renderedGenderIndex++; } ?>
                    <!---->
                </div> 
                <?php if ($registrationRoleModeValue !== 'legacy') { ?>
                <div class="i_helper_title"><?php echo iN_HelpSecure($LANG['signup_intent_label']);?></div>
                <div class="i_settings_item_title_for flex_ gender-options-row">
                    <?php foreach ($signupIntentOptions as $signupIntentIndex => $signupIntentOption) {
                        $intentInputId = 'signup_intent_' . $signupIntentOption;
                        $intentLangKey = 'signup_intent_' . $signupIntentOption;
                        $intentLabel = $LANG[$intentLangKey] ?? ucfirst($signupIntentOption);
                        $intentLabelId = $signupIntentIndex === 0 ? 'signupyouarein' : 'signupyouare';
                    ?>
                    <div class="flexBox flex_">
                        <label class="youare flex_" id="<?php echo iN_HelpSecure($intentLabelId); ?>" for="<?php echo iN_HelpSecure($intentInputId); ?>">
                            <input type="radio" name="signup_intent" id="<?php echo iN_HelpSecure($intentInputId); ?>" value="<?php echo iN_HelpSecure($signupIntentOption); ?>" <?php echo $signupIntentIndex === 0 ? "checked='checked'" : ""; ?>>
                            <span class="flex_ transition"><?php echo iN_HelpSecure($intentLabel); ?></span>
                        </label>
                    </div>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <input type="hidden" name="signup_intent" value="user">
                <?php } ?>
                <div class="i_settings_item_title_for flex_ extra_style">
                    <div class="i_re_box">
                        <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['full_name']);?></div>
                        <div class="i_settings_item_title_for i_settings_item_title_for_style">
                           <input type="text" name="flname" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['your_full_name']);?>">
                       </div>
                    </div>
                    <div class="i_re_box">
                        <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['username']);?></div>
                        <div class="i_settings_item_title_for i_settings_item_title_for_style">
                           <input type="text" name="uusername" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['your_username']);?>" value="<?php echo iN_HelpSecure($claimName);?>">
                        </div>
                    </div>
                </div> 
                <?php
                if(isset($_GET['ref']) && $_GET['ref'] != '' && !empty($_GET['ref'])){?>
                  <input type="hidden" name="refuser" value="<?php echo $_GET['ref'];?>">
                <?php } ?> 
                <div class="i_settings_item_title_for flex_ extra_style">
                    <div class="i_re_box">
                        <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['your_email_address']);?></div>
                        <div class="i_settings_item_title_for i_settings_item_title_for_style">
                           <input type="text" name="y_email" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['your_email_address']);?>">
                       </div>
                    </div>
                    <div class="i_re_box">
                        <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['password']);?></div>
                        <div class="i_settings_item_title_for i_settings_item_title_for_style">
                           <input type="password" name="y_password" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['password']);?>">
                        </div>
                    </div>
                </div>
                <!--********************-->
                <div class="i_settings_item_title_for flex_ extra_style">
                    <div class="certification_file_form">
                        <div class="certification_file_box">
                            <?php echo html_entity_decode($LANG['accept_terms_of_conditions_register']);?>
                        </div>
                    </div>
                </div>
                <div class="register_warning fill_all">
                    <?php echo iN_HelpSecure($LANG['full_for_register']);?>
                </div>
                <div class="register_warning fill_pass">
                    <?php echo iN_HelpSecure($LANG['passwor_too_short']);?>
                </div>
                <div class="register_warning fill_email_used">
                    <?php echo iN_HelpSecure($LANG['email_already_in_use_warning']);?>
                </div>
                <div class="register_warning fill_email_invalid">
                    <?php echo iN_HelpSecure($LANG['invalid_email_address']);?>
                </div>
                <div class="register_warning fill_username_empty">
                    <?php echo iN_HelpSecure($LANG['username_should_not_be_empty']);?>
                </div>
                <div class="register_warning fill_username_used">
                    <?php echo iN_HelpSecure($LANG['try_different_username']);?>
                </div>
                <div class="register_warning fill_username_short">
                    <?php echo iN_HelpSecure($LANG['short_username']);?>
                </div>
                <div class="register_warning fill_username_invalid">
                    <?php echo iN_HelpSecure($LANG['invalid_username']);?>
                </div>
                <div class="register_warning fill_email_not_send">
                    <?php echo iN_HelpSecure($LANG['verification_email_failed']);?>
                </div>
                <div class="form_group">
                    <div class="i_login_button"><button type="submit"><?php echo iN_HelpSecure($LANG['register']);?></button></div>
                </div>
            </form>
            </div>
        </div> 
    </div>
<?php }else{?>
<div class="i_become_creator_container tabing">
   <div class="tabing column flex_ register_disabled">
     <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('130'));?>
     <?php echo iN_HelpSecure($LANG['register_disabled']);?>
   </div>
</div>
<?php } ?>
</div>
</body>
</html>
