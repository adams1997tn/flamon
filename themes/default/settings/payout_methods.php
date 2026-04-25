<?php
$payoutMethodsText = isset($LANG['payout_methods_not_multi']) ? $LANG['payout_methods_not_multi'] : $LANG['payout_methods_not'];
$enabledPayoutMethods = array();
if (isset($payPalPaymentStatus) && (string)$payPalPaymentStatus === '1') {
    $enabledPayoutMethods['paypal'] = array(
        'label' => $LANG['paypal'],
        'note' => $LANG['payout_processor_fee_note']
    );
}
$enabledPayoutMethods['bank'] = array(
    'label' => $LANG['bank_transfer'],
    'note' => ''
);
if (isset($payoutPayoneerStatus) && (string)$payoutPayoneerStatus === '1') {
    $enabledPayoutMethods['payoneer'] = array(
        'label' => $LANG['payoneer'],
        'note' => $LANG['payout_processor_fee_note']
    );
}
if (isset($payoutZelleStatus) && (string)$payoutZelleStatus === '1') {
    $enabledPayoutMethods['zelle'] = array(
        'label' => $LANG['zelle'],
        'note' => $LANG['payout_processor_fee_note']
    );
}
if (isset($payoutWesternUnionStatus) && (string)$payoutWesternUnionStatus === '1') {
    $enabledPayoutMethods['western-union'] = array(
        'label' => $LANG['western_union'],
        'note' => $LANG['payout_processor_fee_note']
    );
}
if (isset($payoutBitcoinStatus) && (string)$payoutBitcoinStatus === '1') {
    $enabledPayoutMethods['bitcoin'] = array(
        'label' => $LANG['bitcoin_payout'],
        'note' => $LANG['payout_processor_fee_note']
    );
}
if (isset($payoutMercadoPagoStatus) && (string)$payoutMercadoPagoStatus === '1') {
    $enabledPayoutMethods['mercadopago'] = array(
        'label' => $LANG['mercadopago_payout'],
        'note' => $LANG['mercadopago_argentina_note']
    );
}
$enabledPayoutMethodKeys = array_keys($enabledPayoutMethods);
$fallbackPayoutMethod = isset($enabledPayoutMethodKeys[0]) ? $enabledPayoutMethodKeys[0] : 'bank';
$selectedPayoutMethod = isset($payoutMethod, $enabledPayoutMethods[$payoutMethod]) ? $payoutMethod : $fallbackPayoutMethod;

$safePaypalEmail = filter_var((string)$iN->iN_Secure($paypalEmail ?? ''), FILTER_SANITIZE_EMAIL);
$safePayoneerEmail = filter_var((string)$iN->iN_Secure($payoneerEmail ?? ''), FILTER_SANITIZE_EMAIL);
$safeZelleEmail = filter_var((string)$iN->iN_Secure($zelleEmail ?? ''), FILTER_SANITIZE_EMAIL);
$safeWesternUnionFullName = (string)$iN->iN_Secure($westernUnionFullName ?? '');
$safeWesternUnionDocumentId = (string)$iN->iN_Secure($westernUnionDocumentId ?? '');
$safeBitcoinWalletAddress = (string)$iN->iN_Secure($bitcoinWalletAddress ?? '');
$safeMercadoPagoAlias = (string)$iN->iN_Secure($mercadoPagoAlias ?? '');
$safeMercadoPagoCvu = (string)$iN->iN_Secure($mercadoPagoCvu ?? '');
?>
<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('77'));?><?php echo iN_HelpSecure($LANG['payout_methods']);?></div>
       <div class="i_moda_header_nt"><?php echo iN_HelpSecure($payoutMethodsText);?></div>
    </div>
    <div class="i_settings_wrapper_items">
    <div class="payouts_form_container">
   <div class="i_payout_methods_form_container">
       <form id="bankForm">
       <?php foreach ($enabledPayoutMethods as $methodKey => $methodData) {
           $radioId = 'payout_method_' . str_replace('-', '_', $methodKey);
       ?>
        <div class="i_set_subscription_fee_box">
            <div class="i_sub_not"><?php echo iN_HelpSecure($methodData['label']);?></div>
            <?php if (!empty($methodData['note'])) {?>
            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($methodData['note']);?></div>
            <?php }?>
            <div class="i_sub_not_check">
            <?php echo iN_HelpSecure($methodKey === 'paypal' ? $LANG['if_default_not'] : $LANG['if_default_not_method']);?>
            <div class="i_sub_not_check_box pyot">
                <div class="el-radio el-radio-yellow">
                    <input type="radio" name="default" id="<?php echo iN_HelpSecure($radioId);?>" value="<?php echo iN_HelpSecure($methodKey);?>" <?php echo $selectedPayoutMethod === $methodKey ? "checked='checked'" : ""; ?>>
                    <label class="el-radio-style" for="<?php echo iN_HelpSecure($radioId);?>"></label>
			    </div>
            </div>
            </div>
            <div class="payout_method_fields" data-method="<?php echo iN_HelpSecure($methodKey);?>" style="<?php echo $selectedPayoutMethod === $methodKey ? '' : 'display:none;'; ?>">
                <?php if ($methodKey === 'paypal') {?>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('80'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="paypale" placeholder="<?php echo iN_HelpSecure($LANG['paypal_email']);?>" value="<?php echo iN_HelpSecure($safePaypalEmail);?>"></div>
                    </div>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('80'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="paypalere" placeholder="<?php echo iN_HelpSecure($LANG['confirm_paypal_email']);?>" value="<?php echo iN_HelpSecure($safePaypalEmail);?>"></div>
                    </div>
                <?php } elseif ($methodKey === 'bank') {?>
                    <?php include __DIR__ . '/../layouts/widgets/bank_transfer_form.php'; ?>
                <?php } elseif ($methodKey === 'payoneer') {?>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('196'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="payoneer_email" placeholder="<?php echo iN_HelpSecure($LANG['email_payoneer']);?>" value="<?php echo iN_HelpSecure($safePayoneerEmail);?>"></div>
                    </div>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('196'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="payoneer_email_re" placeholder="<?php echo iN_HelpSecure($LANG['confirm_email_payoneer']);?>" value="<?php echo iN_HelpSecure($safePayoneerEmail);?>"></div>
                    </div>
                <?php } elseif ($methodKey === 'zelle') {?>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('71'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="zelle_email" placeholder="<?php echo iN_HelpSecure($LANG['email_zelle']);?>" value="<?php echo iN_HelpSecure($safeZelleEmail);?>"></div>
                    </div>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('71'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="zelle_email_re" placeholder="<?php echo iN_HelpSecure($LANG['confirm_email_zelle']);?>" value="<?php echo iN_HelpSecure($safeZelleEmail);?>"></div>
                    </div>
                <?php } elseif ($methodKey === 'western-union') {?>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('122'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="western_union_full_name" placeholder="<?php echo iN_HelpSecure($LANG['full_name']);?>" value="<?php echo iN_HelpSecure($safeWesternUnionFullName);?>"></div>
                    </div>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('70'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="western_union_document_id" placeholder="<?php echo iN_HelpSecure($LANG['document_id']);?>" value="<?php echo iN_HelpSecure($safeWesternUnionDocumentId);?>"></div>
                    </div>
                <?php } elseif ($methodKey === 'bitcoin') {?>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('197'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="bitcoin_wallet" placeholder="<?php echo iN_HelpSecure($LANG['bitcoin_wallet']);?>" value="<?php echo iN_HelpSecure($safeBitcoinWalletAddress);?>"></div>
                    </div>
                <?php } elseif ($methodKey === 'mercadopago') {?>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('122'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="mercadopago_alias" placeholder="<?php echo iN_HelpSecure($LANG['alias_mp']);?>" value="<?php echo iN_HelpSecure($safeMercadoPagoAlias);?>"></div>
                    </div>
                    <div class="i_set_subscription_fee margin-bottom-ten">
                        <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('198'));?></div>
                        <div class="i_payout_"><input type="text" class="transition aval" id="mercadopago_cvu" placeholder="<?php echo iN_HelpSecure($LANG['nro_cvu']);?>" value="<?php echo iN_HelpSecure($safeMercadoPagoCvu);?>"></div>
                    </div>
                <?php }?>
            </div>
        </div>
       <?php }?>
            <div class="i_t_warning" id="setWarning"><?php echo iN_HelpSecure($LANG['payout_required_fields_warning']);?></div>
            <div class="i_t_warning" id="notMatch"><?php echo iN_HelpSecure($LANG['payout_emails_not_match']);?></div>
            <div class="i_t_warning" id="notValidE"><?php echo iN_HelpSecure($LANG['invalid_email_address']);?></div>
            <div class="i_t_warning" id="setBankWarning"><?php echo iN_HelpSecure($LANG['payout_details_warning']);?></div>
       </form>
   </div>
</div>
    </div>
    <div class="i_settings_wrapper_item successNot">
        <?php echo iN_HelpSecure($LANG['payment_settings_updated_success'])?>
    </div>
     <div class="i_become_creator_box_footer tabing">
        <div class="i_nex_btn pyot_sNext transition"><?php echo iN_HelpSecure($LANG['save_edit']);?></div>
     </div>
  </div>
</div>
<link rel="stylesheet" href="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/css/bank_transfer_form.css?v=<?php echo iN_HelpSecure($version); ?>">
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/bank_transfer_form.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
