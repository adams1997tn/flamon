<?php
// Auto-submit for verification if not yet submitted
if ($validationStatus == '0') {
    DB::exec("UPDATE i_users SET validation_status = '1' WHERE iuid = ? AND validation_status = '0'", [(int)$userID]);
    $validationStatus = '1';
}
?>
<div class="certification_terms">
    <div class="certification_terms_item verirication_timing_bg"></div>
    <div class="certification_terms_item">
        <div class="certificate_terms_item_item pendingTitle">
           <?php echo iN_HelpSecure($LANG['your_request_is_pending']);?>
        </div>
        <div class="certificate_terms_item_item">
          <?php echo iN_HelpSecure($LANG['you_will_notififed_when_it_is_processed']);?>
        </div>
    </div>
</div>
