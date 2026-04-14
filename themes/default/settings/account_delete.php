<?php
$csrfToken = function_exists('csrf_get_token')
	? csrf_get_token()
	: (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');

$pendingDeletion = method_exists($iN, 'iN_GetPendingAccountDeletion')
	? $iN->iN_GetPendingAccountDeletion((int)$userID)
	: null;

$latestExport = method_exists($iN, 'iN_GetLatestAccountExport')
	? $iN->iN_GetLatestAccountExport((int)$userID)
	: null;

$latestExportDownloadUrl = '';
$latestExportExpiresAt = 0;
$latestExportCreatedAt = 0;
$latestExportSizeBytes = 0;
$latestExportGenerationSeconds = 0;
if (is_array($latestExport) && !empty($latestExport['export_token'])) {
	$latestExportDownloadUrl = iN_HelpSecure($base_url) . 'requests/request.php?f=downloadAccountExport&token=' . rawurlencode((string)$latestExport['export_token']);
	$latestExportExpiresAt = (int)($latestExport['expires_at'] ?? 0);
	$latestExportCreatedAt = (int)($latestExport['created_at'] ?? 0);
	$latestExportSizeBytes = (int)($latestExport['file_size'] ?? 0);
	$latestExportGenerationSeconds = (int)($latestExport['generation_seconds'] ?? 0);
}

$pendingDeleteAfter = 0;
$pendingDaysLeft = 0;
$pendingHoursLeft = 0;
if (is_array($pendingDeletion)) {
	$pendingDeleteAfter = (int)($pendingDeletion['delete_after'] ?? 0);
	if ($pendingDeleteAfter > time()) {
		$remainingSeconds = max(0, $pendingDeleteAfter - time());
		$pendingDaysLeft = (int)floor($remainingSeconds / 86400);
		$pendingHoursLeft = (int)ceil(($remainingSeconds % 86400) / 3600);
		if ($pendingHoursLeft >= 24) {
			$pendingDaysLeft++;
			$pendingHoursLeft = 0;
		}
	}
}

$formatExportBytes = static function ($bytes) {
	$bytes = (int)$bytes;
	if ($bytes <= 0) {
		return '0 B';
	}
	$units = ['B', 'KB', 'MB', 'GB', 'TB'];
	$pow = (int)floor(log($bytes, 1024));
	$pow = max(0, min($pow, count($units) - 1));
	$value = $bytes / pow(1024, $pow);
	$precision = $pow === 0 ? 0 : 2;
	return number_format($value, $precision) . ' ' . $units[$pow];
};

$latestExportSizeText = $latestExportSizeBytes > 0 ? $formatExportBytes($latestExportSizeBytes) : '-';
$latestExportGenerationText = $latestExportGenerationSeconds > 0 ? $latestExportGenerationSeconds . 's' : '-';
$pendingCountdownTemplate = (string)($LANG['account_delete_pending_banner_countdown'] ?? '{days} days {hours} hours left');
$pendingCountdownText = str_replace(
	['{days}', '{hours}'],
	[(string)$pendingDaysLeft, (string)$pendingHoursLeft],
	$pendingCountdownTemplate
);
?>
<div class="settings_main_wrapper account-delete-page">
	<div class="i_settings_wrapper_in inTable">
		<div class="i_settings_wrapper_title account-delete-page-header">
			<div class="i_settings_wrapper_title_txt flex_"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?><span><?php echo iN_HelpSecure($LANG['account_delete_title']);?></span></div>
			<div class="account-delete-page-note"><?php echo iN_HelpSecure($LANG['account_delete_login_cancel_note']);?></div>
		</div>
		<div class="i_settings_wrapper_items">
			<?php if ($pendingDeleteAfter > 0) { ?>
			<div class="account-delete-sticky-status">
				<div class="account-delete-sticky-status-icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('12'));?></div>
				<div class="account-delete-sticky-status-body">
					<div class="account-delete-sticky-status-title"><?php echo iN_HelpSecure($LANG['account_delete_pending_banner_title']);?></div>
					<div class="account-delete-sticky-status-desc"><?php echo iN_HelpSecure($LANG['account_delete_pending_banner_desc']);?></div>
					<div class="account-delete-sticky-status-count"><?php echo iN_HelpSecure($pendingCountdownText);?></div>
				</div>
				<div class="account-delete-sticky-status-actions">
					<button
						type="button"
						class="i_nex_btn_btn transition cancelAccountDeletion account-delete-page-btn account-delete-page-btn-primary account-delete-sticky-cancel-btn"
						data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
					><?php echo iN_HelpSecure($LANG['account_delete_cancel_button']);?></button>
				</div>
			</div>
			<?php } ?>
			<div class="payouts_form_container account-delete-panels">
				<div class="i_set_subscription_fee_box account-delete-panel account-delete-panel-export">
					<div class="account-delete-panel-head">
						<div class="account-delete-panel-icon account-delete-panel-icon-export"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('93'));?></div>
						<div class="account-delete-panel-title-wrap">
							<div class="i_sub_not i_preference account-delete-panel-title"><?php echo iN_HelpSecure($LANG['account_export_title']);?></div>
							<div class="account-delete-panel-desc"><?php echo iN_HelpSecure($LANG['account_export_desc']);?></div>
							<div class="account-export-delete-notice"><?php echo iN_HelpSecure($LANG['account_export_delete_after_notice']);?></div>
						</div>
					</div>
					<?php if ($latestExportDownloadUrl) { ?>
						<div class="account-delete-meta-row">
							<div class="account-delete-meta-pill"><?php echo iN_HelpSecure($LANG['account_export_latest']);?></div>
						</div>
						<div class="account-export-stats">
							<div class="account-export-stat-item">
								<span class="account-export-stat-label"><?php echo iN_HelpSecure($LANG['account_export_latest_generated_at']);?></span>
								<strong class="account-export-created-value" data-created-ts="<?php echo iN_HelpSecure($latestExportCreatedAt);?>"><?php echo $latestExportCreatedAt > 0 ? iN_HelpSecure(date('Y-m-d H:i', $latestExportCreatedAt)) : '-';?></strong>
							</div>
							<div class="account-export-stat-item">
								<span class="account-export-stat-label"><?php echo iN_HelpSecure($LANG['account_export_latest_size']);?></span>
								<strong class="account-export-size-value" data-file-bytes="<?php echo iN_HelpSecure($latestExportSizeBytes);?>"><?php echo iN_HelpSecure($latestExportSizeText);?></strong>
							</div>
							<div class="account-export-stat-item">
								<span class="account-export-stat-label"><?php echo iN_HelpSecure($LANG['account_export_latest_generated_in']);?></span>
								<strong class="account-export-duration-value" data-generation-seconds="<?php echo iN_HelpSecure($latestExportGenerationSeconds);?>"><?php echo iN_HelpSecure($latestExportGenerationText);?></strong>
							</div>
						</div>
						<?php if ($latestExportExpiresAt > 0) { ?>
							<div class="account-delete-meta-text"><?php echo iN_HelpSecure($LANG['account_export_expires_at']);?>: <strong class="account-export-expires-value"><?php echo iN_HelpSecure(date('Y-m-d H:i', $latestExportExpiresAt));?></strong></div>
						<?php } ?>
					<?php } else { ?>
						<div class="account-export-empty-note"><?php echo iN_HelpSecure($LANG['account_export_latest_not_found']);?></div>
					<?php } ?>
					<div class="i_become_creator_box_footer tabing account-delete-panel-actions">
						<button
							type="button"
							class="i_nex_btn_btn transition createAccountExport account-delete-page-btn account-delete-page-btn-primary"
							data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
							data-default-label="<?php echo iN_HelpSecure($LANG['account_export_button']);?>"
							data-generating-label="<?php echo iN_HelpSecure($LANG['account_export_generating']);?>"
							data-cooldown-template="<?php echo iN_HelpSecure($LANG['account_export_cooldown_text']);?>"
							data-cooldown-seconds="45"
						><?php echo iN_HelpSecure($LANG['account_export_button']);?></button>
						<a
							href="<?php echo iN_HelpSecure($latestExportDownloadUrl ?: '#'); ?>"
							class="i_nex_btn_btn transition account-export-download account-delete-page-btn account-delete-page-btn-secondary"
							<?php echo $latestExportDownloadUrl ? '' : 'hidden="hidden"'; ?>
						><?php echo iN_HelpSecure($LANG['account_export_download_button']);?></a>
					</div>
					<div class="i_settings_wrapper_item account-export-feedback" hidden="hidden"></div>
				</div>

				<div class="i_set_subscription_fee_box pref_top account-delete-panel account-delete-panel-danger">
					<div class="account-delete-panel-head">
						<div class="account-delete-panel-icon account-delete-panel-icon-danger"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?></div>
						<div class="account-delete-panel-title-wrap">
							<div class="i_sub_not i_preference account-delete-panel-title"><?php echo iN_HelpSecure($LANG['account_delete_title']);?></div>
							<div class="account-delete-panel-desc"><?php echo iN_HelpSecure($LANG['account_delete_desc']);?></div>
						</div>
					</div>
					<div class="account-delete-danger-note"><?php echo iN_HelpSecure($LANG['account_delete_login_cancel_note']);?></div>
					<?php if ($pendingDeleteAfter > 0) { ?>
						<div class="account-delete-meta-row">
							<div class="account-delete-meta-pill account-delete-meta-pill-danger"><?php echo iN_HelpSecure($LANG['account_delete_pending_days']);?>: <?php echo iN_HelpSecure($pendingDaysLeft);?></div>
							<div class="account-delete-meta-pill"><?php echo iN_HelpSecure($LANG['account_delete_pending_time']);?>: <?php echo iN_HelpSecure(date('Y-m-d H:i', $pendingDeleteAfter));?></div>
						</div>
						<div class="i_become_creator_box_footer tabing account-delete-panel-actions">
							<button
								type="button"
								class="i_nex_btn_btn transition cancelAccountDeletion account-delete-page-btn account-delete-page-btn-secondary"
								data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
							><?php echo iN_HelpSecure($LANG['account_delete_cancel_button']);?></button>
						</div>
					<?php } else { ?>
						<div class="account-delete-danger-warning"><?php echo iN_HelpSecure($LANG['account_delete_warning']);?></div>
						<div class="i_become_creator_box_footer tabing account-delete-panel-actions">
							<button
								type="button"
								class="i_nex_btn_btn transition openDeleteAccountModal account-delete-page-btn account-delete-page-btn-danger"
								data-csrf="<?php echo iN_HelpSecure($csrfToken); ?>"
							><?php echo iN_HelpSecure($LANG['account_delete_button']);?></button>
						</div>
					<?php } ?>
					<div class="i_settings_wrapper_item account-delete-feedback" hidden="hidden"></div>
				</div>
			</div>
			<div class="account-delete-faq">
				<div class="account-delete-faq-title"><?php echo iN_HelpSecure($LANG['account_delete_faq_title']);?></div>
				<div class="account-delete-faq-list">
					<div class="account-delete-faq-item">
						<div class="account-delete-faq-q"><?php echo iN_HelpSecure($LANG['account_delete_faq_q1']);?></div>
						<div class="account-delete-faq-a"><?php echo iN_HelpSecure($LANG['account_delete_faq_a1']);?></div>
					</div>
					<div class="account-delete-faq-item">
						<div class="account-delete-faq-q"><?php echo iN_HelpSecure($LANG['account_delete_faq_q2']);?></div>
						<div class="account-delete-faq-a"><?php echo iN_HelpSecure($LANG['account_delete_faq_a2']);?></div>
					</div>
					<div class="account-delete-faq-item">
						<div class="account-delete-faq-q"><?php echo iN_HelpSecure($LANG['account_delete_faq_q3']);?></div>
						<div class="account-delete-faq-a"><?php echo iN_HelpSecure($LANG['account_delete_faq_a3']);?></div>
					</div>
					<div class="account-delete-faq-item">
						<div class="account-delete-faq-q"><?php echo iN_HelpSecure($LANG['account_delete_faq_q4']);?></div>
						<div class="account-delete-faq-a"><?php echo iN_HelpSecure($LANG['account_delete_faq_a4']);?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php if ($pendingDeleteAfter <= 0) { ?>
<div class="i_modal_bg_in account-delete-password-modal" aria-hidden="true">
	<div class="i_modal_in_in account-delete-modal-card">
		<div class="i_modal_content account-delete-modal-content">
			<div class="i_modal_g_header account-delete-modal-header">
				<span><?php echo iN_HelpSecure($LANG['account_delete_modal_title']);?></span>
				<button type="button" class="accountDeleteModalClose transition account-delete-modal-close-btn"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?></button>
			</div>
			<div class="account-delete-modal-body">
				<div class="account-delete-modal-warning">
					<?php echo html_entity_decode($iN->iN_SelectedMenuIcon('12')); ?>
					<span><?php echo iN_HelpSecure($LANG['account_delete_modal_warning_14']);?></span>
				</div>
				<div class="box_not"><?php echo iN_HelpSecure($LANG['account_delete_modal_desc']);?></div>
				<form id="accountDeleteConfirmForm" method="post">
					<input type="hidden" name="f" value="requestAccountDeletion">
					<input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
					<label class="account-delete-password-title" for="delete_password"><?php echo iN_HelpSecure($LANG['account_delete_modal_password_label']);?></label>
					<div class="account-delete-password-input-wrap">
						<input type="password" name="delete_password" id="delete_password" class="account-delete-password-input account-delete-password-field" autocomplete="current-password" placeholder="<?php echo iN_HelpSecure($LANG['account_delete_modal_password_placeholder']);?>">
						<button
							type="button"
							class="account-delete-password-toggle transition"
							data-show-text="<?php echo iN_HelpSecure($LANG['account_delete_modal_show_password']);?>"
							data-hide-text="<?php echo iN_HelpSecure($LANG['account_delete_modal_hide_password']);?>"
						><?php echo iN_HelpSecure($LANG['account_delete_modal_show_password']);?></button>
					</div>
					<label class="account-delete-password-title" for="delete_confirm_text"><?php echo iN_HelpSecure($LANG['account_delete_modal_confirm_text_label']);?></label>
					<div class="account-delete-password-input-wrap">
						<input type="text" name="delete_confirm_text" id="delete_confirm_text" class="account-delete-password-input account-delete-confirm-input" autocomplete="off" placeholder="<?php echo iN_HelpSecure($LANG['account_delete_modal_confirm_text_placeholder']);?>">
					</div>
					<input type="hidden" name="delete_confirm_keyword" value="DELETE">
					<input type="hidden" class="delete-confirm-invalid-text" value="<?php echo iN_HelpSecure($LANG['account_delete_modal_confirm_text_invalid']);?>">
					<div class="account-delete-modal-error" hidden="hidden"></div>
					<div class="account-delete-modal-actions">
						<button type="button" class="transition accountDeleteModalClose account-delete-btn account-delete-btn-secondary"><?php echo iN_HelpSecure($LANG['account_delete_modal_cancel']);?></button>
						<button type="submit" class="transition account-delete-btn account-delete-btn-primary"><?php echo iN_HelpSecure($LANG['account_delete_modal_confirm']);?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php } ?>
