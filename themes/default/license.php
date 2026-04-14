<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
       include("layouts/header/meta.php");
       include("layouts/header/css.php");
       include("layouts/header/javascripts.php");
    ?>
</head>
<body>
<div class="license_gate">
    <div class="license_card">
        <div class="license_card_header">
            <div class="license_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('171')); ?></div>
            <div class="license_status_chip"><?php echo iN_HelpSecure($LANG['license_activation_required']); ?></div>
        </div>
        <div class="license_card_body">
            <h1 class="license_title"><?php echo iN_HelpSecure($LANG['license_activation']); ?></h1>
            <p class="license_sub"><?php echo iN_HelpSecure($LANG['license_not_activated_message']); ?></p>
            <div class="license_meta_row">
                <div class="license_meta_item">
                    <span class="label">Site</span>
                    <span class="value"><?php echo iN_HelpSecure(parse_url($base_url, PHP_URL_HOST) ?? ''); ?></span>
                </div>
                <div class="license_meta_item">
                    <span class="label">Path</span>
                    <span class="value"><?php echo iN_HelpSecure(parse_url($base_url, PHP_URL_PATH) ?? '/'); ?></span>
                </div>
            </div>
        </div>
        <div class="license_actions_row">
            <?php if (($logedIn ?? '0') !== '1') : ?>
                <div class="license_note"><?php echo iN_HelpSecure($LANG['please_login_to_continue'] ?? 'Please login to continue.'); ?></div>
            <?php elseif ((string)($userType ?? '0') === '2') : ?>
                <a class="license_primary_btn" href="<?php echo iN_HelpSecure($base_url); ?>admin/license_activation">
                    <?php echo iN_HelpSecure($LANG['license_activation']); ?>
                </a>
            <?php else : ?>
                <div class="license_note"><?php echo iN_HelpSecure($LANG['contact_admin_for_activation'] ?? 'Please contact the site administrator to activate the license.'); ?></div>
            <?php endif; ?>
        </div>
        <div class="license_note"><?php echo iN_HelpSecure($LANG['license_not_activated_message']); ?></div>
    </div>
</div>
</body>
</html>
