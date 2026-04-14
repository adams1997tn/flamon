<?php
if ($logedIn !== '1' || $userType !== '2') {
    http_response_code(403);
    ?>
    <div class="license_alert error">
        <?php echo iN_HelpSecure($LANG['license_admin_only'] ?? 'Only a signed-in administrator can request or manage the license.'); ?>
    </div>
    <?php
    return;
}
?>
<div class="i_contents_container license_wrap license-activation-page">
    <?php
        $notice = $_SESSION['license_notice'] ?? '';
        $noticeMsg = $_SESSION['license_message'] ?? '';
        unset($_SESSION['license_notice'], $_SESSION['license_message']);
        $tokenTail = !empty($lc_tok) ? substr($lc_tok, -6) : '';
        $statusKey = 'license_' . ($lc_st ?? 'inactive');
        $hostName = parse_url($base_url, PHP_URL_HOST) ?: $base_url;
        $isActive = ($lc_st ?? '') === 'active';
        $licenseDesc = $isActive ? ($LANG['license_activation_success'] ?? 'License is active.') : ($LANG['license_not_activated_message'] ?? '');
        $licenseSub = $isActive ? ($LANG['license_active'] ?? 'Active') : ($LANG['license_activation_required'] ?? '');
    ?>

    <div class="license_hero border_one">
        <div class="license_hero__info">
            <div class="license_hero__eyebrow"><?php echo iN_HelpSecure($LANG['license_activation']); ?></div>
            <div class="license_hero__title"><?php echo iN_HelpSecure($LANG['license_activation']); ?></div>
            <div class="license_hero__desc"><?php echo iN_HelpSecure($licenseDesc); ?></div>
            <div class="license_status_stack">
                <span class="license_status_pill <?php echo iN_HelpSecure(($lc_st ?? 'inactive')); ?>">
                    <?php echo iN_HelpSecure($LANG[$statusKey] ?? ($lc_st ?? 'inactive')); ?>
                </span>
                <span class="status_badge <?php echo ($lc_st ?? '') === 'active' ? 'on' : 'off'; ?>"><?php echo iN_HelpSecure($hostName); ?></span>
            </div>
        </div>
        <div class="license_hero__meta">
            <div class="license_meta_item soft">
                <span class="label"><?php echo iN_HelpSecure($LANG['license_status']); ?></span>
                <span class="value"><?php echo iN_HelpSecure($LANG[$statusKey] ?? ($lc_st ?? 'inactive')); ?></span>
            </div>
            <?php if (!empty($lc_tok)) { ?>
                <div class="license_meta_item">
                    <span class="label"><?php echo iN_HelpSecure($LANG['license_token_hint']); ?></span>
                    <span class="value">****<?php echo iN_HelpSecure($tokenTail); ?></span>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php if ($notice) { ?>
        <div class="license_alert <?php echo $notice === 'license_activation_success' ? 'success' : 'error'; ?>">
            <?php echo iN_HelpSecure($noticeMsg ?: $LANG['license_activation_success']); ?>
        </div>
    <?php } ?>

    <div class="license_grid">
        <div class="license_card">
            <div class="license_card_header">
                <div>
                    <div class="license_card_title"><?php echo iN_HelpSecure($LANG['activate_via_dizzyscripts']); ?></div>
                    <div class="license_card_sub"><?php echo iN_HelpSecure($licenseSub); ?></div>
                </div>
                <span class="status_badge <?php echo ($lc_st ?? '') === 'active' ? 'on' : 'off'; ?>">
                    <?php echo iN_HelpSecure($LANG[$statusKey] ?? ($lc_st ?? 'inactive')); ?>
                </span>
            </div>

            <form id="licenseActivationForm" class="license_form" data-redirecting="<?php echo iN_HelpSecure($LANG['license_redirecting']); ?>" data-error="<?php echo iN_HelpSecure($LANG['license_activation_failed']); ?>">
                <?php echo csrf_token_field(); ?>
                <div class="form_row">
                    <label><?php echo iN_HelpSecure($LANG['envato_username_label']); ?></label>
                    <input type="text" name="envato_username" placeholder="<?php echo iN_HelpSecure($LANG['envato_username_label']); ?>" required>
                </div>
                <div class="form_row">
                    <label><?php echo iN_HelpSecure($LANG['purchase_code_label']); ?></label>
                    <input type="text" name="purchase_code" placeholder="<?php echo iN_HelpSecure($LANG['purchase_code_label']); ?>" required>
                </div>
                <input type="hidden" name="envato_item_id" value="<?php echo iN_HelpSecure(LicenseHelper::ENVATO_ITEM_ID); ?>">
                <div class="form_actions">
                    <button type="submit" class="transition primary_btn full_width_mobile"><?php echo iN_HelpSecure($LANG['activate_via_dizzyscripts']); ?></button>
                    <?php if (!empty($lc_tok)) { ?>
                        <button type="button" id="licenseDeactivate" class="transition ghost_btn" data-csrf="<?php echo iN_HelpSecure(csrf_token()); ?>" data-error="<?php echo iN_HelpSecure($LANG['license_deactivated']); ?>">
                            <?php echo iN_HelpSecure($LANG['license_deactivate']); ?>
                        </button>
                    <?php } ?>
                </div>
                <div class="license_notice_area"></div>
            </form>
        </div>

        <div class="license_card info_card">
            <div class="info_header">
                <div>
                    <div class="info_title"><?php echo iN_HelpSecure($LANG['license_activation']); ?></div>
                    <div class="info_sub"><?php echo iN_HelpSecure($LANG['license_activation_required']); ?></div>
                </div>
                <span class="status_badge <?php echo $isActive ? 'on' : 'off'; ?>">
                    <?php echo iN_HelpSecure($LANG[$statusKey] ?? ($lc_st ?? 'inactive')); ?>
                </span>
            </div>
            <ul class="info_list check">
                <li><span class="info_check_dot"></span><span><?php echo iN_HelpSecure($LANG['envato_username_label']); ?></span></li>
                <li><span class="info_check_dot"></span><span><?php echo iN_HelpSecure($LANG['purchase_code_label']); ?></span></li>
                <li><span class="info_check_dot"></span><span><?php echo iN_HelpSecure($licenseSub); ?></span></li>
            </ul>
            <div class="info_chip_row">
                <span class="info_chip"><?php echo iN_HelpSecure($licenseDesc); ?></span>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo iN_HelpSecure($base_url); ?>admin/default/js/licenseHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
