<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include("layouts/header/meta.php");
        include("layouts/header/css.php");
        include("layouts/header/javascripts.php");
        $csrfToken = function_exists('csrf_get_token')
            ? csrf_get_token()
            : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
        $ageVerified = isset($userAgeVerifyStatus) && (string)$userAgeVerifyStatus === '1';
        $statusLabel = $ageVerified ? $LANG['age_verification_status_verified'] : $LANG['age_verification_status_unverified'];
        $statusClass = $ageVerified ? 'age-verif-badge--ok' : 'age-verif-badge--warn';
        $flashMessage = '';
        $flashType = 'error';
        if (isset($ageVerifFlash) && is_array($ageVerifFlash)) {
            $flashMessage = (string)($ageVerifFlash['message'] ?? '');
            $flashType = (string)($ageVerifFlash['type'] ?? 'error');
        }
        $flashClass = $flashType === 'success' ? 'age-verif-message--success' : 'age-verif-message--error';
    ?>
</head>
<body>
<?php include("layouts/header/header.php"); ?>
<div class="wrapper bCreatorBg age-verif-page">
    <div class="i_become_creator_container age-verif-card">
        <div class="i_modal_content age-verif-content">
            <div class="age-verif-layout">
                <div class="age-verif-intro">
                    <div class="i_login_box_header age-verif-header">
                        <div class="i_login_box_wellcome_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('14')); ?></div>
                        <div class="i_welcome_back">
                            <div class="i_lBack"><?php echo iN_HelpSecure($LANG['age_verification_title']); ?></div>
                            <div class="i_lnot"><?php echo iN_HelpSecure($LANG['age_verification_desc']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="i_direct_register i_register_box_ age-verif-container">
                    <?php if ($flashMessage !== '') { ?>
                        <div class="i_settings_wrapper_item age-verif-message <?php echo iN_HelpSecure($flashClass); ?>">
                            <?php echo iN_HelpSecure($flashMessage); ?>
                        </div>
                    <?php } ?>
                    <div class="age-verif-panel">
                        <div class="age-verif-status-row flex_ tabing_non_justify">
                            <div class="age-verif-status-label"><?php echo iN_HelpSecure($LANG['age_verification_status_label']); ?></div>
                            <div class="age-verif-badge <?php echo iN_HelpSecure($statusClass); ?>">
                                <?php echo iN_HelpSecure($statusLabel); ?>
                            </div>
                        </div>
                        <?php if (!empty($ageVerifRequiredNotice)) { ?>
                            <div class="box_not age-verif-required">
                                <?php echo iN_HelpSecure($LANG['age_verification_required']); ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="form_group age-verif-action">
                        <div class="i_login_button i_res_button">
                            <button
                                type="button"
                                class="ageVerifStart"
                                data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
                                data-error="<?php echo iN_HelpSecure($LANG['age_verification_error_generic']); ?>"
                                data-error-csrf="<?php echo iN_HelpSecure($LANG['invalid_csrf_token'] ?? 'invalid_csrf_token'); ?>"
                                <?php echo $ageVerified ? 'disabled="disabled"' : ''; ?>
                            >
                                <?php echo iN_HelpSecure($LANG['age_verification_button']); ?>
                            </button>
                        </div>
                        <div class="box_not age-verif-inline-message"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="footer_container_out"><?php include("layouts/footer.php");?></div>
</body>
</html>
