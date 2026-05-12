<div class="i_modal_bg_in">
    <!--SHARE-->
   <div class="i_modal_in_in i_sf_box">
       <div class="i_modal_content">
          <div class="purchase_premium_header flex_ tabing border_top_radius mp" data-p="<?php echo iN_HelpSecure($productID);?>"><?php echo iN_HelpSecure($LANG['choose_payment_method']);?></div>
          <div class="purchase_post_details tabing">
        <?php
        $methods = [
          'bitpay' => $bitPayPaymentStatus,
          'razorpay' => $razorPayPaymentStatus,
          'paypal' => $payPalPaymentStatus,
          'stripe' => $stripePaymentStatus,
          'paystack' => $payStackPaymentStatus,
          'iyzico' => $iyziCoPaymentStatus,
          'authorize-net' => $autHorizePaymentStatus,
          'coinpayment' => $coinPaymentStatus,
          'mercadopago' => $mercadoPagoPaymentStatus,
          'yookassa' => isset($yookassaPaymentStatus) ? $yookassaPaymentStatus : '0',
          'konnect' => isset($konnectPaymentStatus) ? $konnectPaymentStatus : '0',
          'epoch' => isset($epochPaymentStatus) ? $epochPaymentStatus : '0',
          'moneroo' => $monerooPaymentStatus,
          'nowpayments' => $nowPaymentsPaymentStatus,
          'flutterwave' => isset($flutterWavePaymentStatus) ? $flutterWavePaymentStatus : '0',
          'ccbill' => $ccbill_Status
        ];

        $betaFlags = [
          'bitpay' => isset($bitPayPaymentBeta) ? $bitPayPaymentBeta : '0',
          'razorpay' => isset($razorPayPaymentBeta) ? $razorPayPaymentBeta : '0',
          'paypal' => isset($payPalPaymentBeta) ? $payPalPaymentBeta : '0',
          'stripe' => isset($stripePaymentBeta) ? $stripePaymentBeta : '0',
          'paystack' => isset($payStackPaymentBeta) ? $payStackPaymentBeta : '0',
          'iyzico' => isset($iyziCoPaymentBeta) ? $iyziCoPaymentBeta : '0',
          'authorize-net' => isset($autHorizePaymentBeta) ? $autHorizePaymentBeta : '0',
          'coinpayment' => isset($coinPaymentBeta) ? $coinPaymentBeta : '0',
          'mercadopago' => isset($mercadoPagoPaymentBeta) ? $mercadoPagoPaymentBeta : '0',
          'yookassa' => isset($yookassaPaymentBeta) ? $yookassaPaymentBeta : '0',
          'konnect' => isset($konnectPaymentBeta) ? $konnectPaymentBeta : '0',
          'epoch' => isset($epochBeta) ? $epochBeta : '0',
          'moneroo' => isset($monerooPaymentBeta) ? $monerooPaymentBeta : '0',
          'nowpayments' => isset($nowPaymentsBeta) ? $nowPaymentsBeta : '0',
          'flutterwave' => isset($flutterWavePaymentBeta) ? $flutterWavePaymentBeta : '0',
          'ccbill' => isset($ccbillBeta) ? $ccbillBeta : '0'
        ];

        $methodLabels = [
          'bitpay' => $LANG['bitpay_payment'],
          'razorpay' => $LANG['razorpay_payment'],
          'paypal' => $LANG['paypal_payment'],
          'stripe' => $LANG['stripe_payment'],
          'paystack' => $LANG['paystack_payment'],
          'iyzico' => $LANG['iyzico_payment'],
          'authorize-net' => $LANG['authorizenet_payment'],
          'coinpayment' => $LANG['coinpayment_settings'],
          'mercadopago' => $LANG['mercadopago_payment'],
          'yookassa' => $LANG['yookassa_payment'],
          'konnect' => $LANG['konnect_payment'] ?? 'Konnect Network',
          'epoch' => $LANG['epoch_payment'],
          'moneroo' => $LANG['moneroo_payment'],
          'nowpayments' => $LANG['nowpayments_payment'],
          'flutterwave' => $LANG['flutterwave_payment'],
          'ccbill' => $LANG['pay_with_ccbill']
        ];

        $isAdminUser = isset($userType) && $userType == '2';

        foreach ($methods as $id => $status) {
          if ($status == '1') {
            if ($id === 'ccbill') {
              $ccbillCredentialsReady = isset($ccbillActiveAndReady) ? (bool)$ccbillActiveAndReady : (!empty($ccbill_AccountNumber) && !empty($ccbill_SubAccountNumber) && !empty($ccbill_FlexID) && !empty($ccbill_SaltKey));
              if (!$ccbillCredentialsReady && !$isAdminUser) {
                continue;
              }
            }
            $isBetaOnly = isset($betaFlags[$id]) && $betaFlags[$id] === '1';
            if ($isBetaOnly && !$isAdminUser) {
              continue;
            }
            $class = in_array($id, ['iyzico', 'authorize-net']) ? 'paywith' : (in_array($id, ['coinpayment']) ? 'paywithCrip' : 'payMethod');
            $label = isset($methodLabels[$id]) ? $methodLabels[$id] : ucfirst($id);
            echo '<div class="payment_method_box transition ' . $class . '" id="' . $id . '" data-type="' . $id . '"><div class="payment_method_item flex_"><span class="payment_method_label">' . iN_HelpSecure($label) . '</span></div></div>';
          }
        } 
        ?>
      </div>
          <div class="i_modal_g_footer">
              <div class="alertBtnLeft no-del transition"><?php echo iN_HelpSecure($LANG['cancel']);?></div>
          </div>
       </div>
   </div>
  <!--/SHARE--> 
<?php if (isset($taxStatus) && (string)$taxStatus === '1') { ?>
  <div class="tax_notice"><?php echo iN_HelpSecure($LANG['tax_settings_note']); ?></div>
<?php } ?>
<script>
  // Define global variables for product payment handling
  window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
  window.productID = "<?php echo iN_HelpSecure($productID); ?>";
  window.userData = <?php echo json_encode($DataUserDetails); ?>;
  window.configData = <?php echo json_encode($PublicConfigs); ?>;
  window.paymentPagePath = <?php echo json_encode($paymentPagePath); ?>;
  window.stripeTestKey = "<?php echo $stripePaymentTestPublicKey; ?>";
  window.stripeLiveKey = "<?php echo $stripePaymentLivePublicKey; ?>";
  window.authorizeNetCallbackUrl = <?php echo json_encode($authorizeNetCallbackUrl); ?>;
  window.razorpayCallbackUrl = <?php echo json_encode($razorpayCallbackUrl); ?>;
  window.flutterwaveCallbackUrl = <?php echo json_encode($flutterwaveCallbackUrl); ?>;
</script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/productPaymentHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<?php
// Conditionally load payment gateway SDKs if active
if (iN_HelpSecure($stripePaymentStatus) == 1) {
    ?>
    <script>
        (function loadStripeJs() {
            if (window.Stripe) { return; }
            if (document.getElementById('stripe-js-v3')) { return; }
            var script = document.createElement('script');
            script.id = 'stripe-js-v3';
            script.src = "https://js.stripe.com/v3/";
            script.async = true;
            document.head.appendChild(script);
        })();
    </script>
    <?php
}
if (iN_HelpSecure($razorPayPaymentStatus) == 1) {
    echo '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>';
}
if (iN_HelpSecure($payStackPaymentStatus) == 1) {
    echo '<script src="https://js.paystack.co/v1/inline.js"></script>';
}
if (iN_HelpSecure(isset($flutterWavePaymentStatus) ? $flutterWavePaymentStatus : '0') == 1) {
    echo '<script src="https://checkout.flutterwave.com/v3.js"></script>';
}
?>
</div>
<!--CREDIT CARD FORM-->
<div class="i_moda_bg_in_form i_subs_modal fixed_zindex">
   <div class="i_modal_in_in i_payment_pop_box">
       <div class="i_modal_content">
           <div class="payClose transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?></div>
           <!---->
           <div class="point_purchase_not flex_"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')).' '.$iN->iN_TextReaplacement($LANG['point_buy_not'],[$planPoint, $planAmount]);?></div>
           <!---->
           <!---->
           <form id="paymentFrm">
           <div class="i_credit_card_form">
                <div id="paymentResponse"></div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['card_holder']);?></label>
                    <div class="form-control">
                        <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('70'));?></div>
                       <input type="text" id="cname" name="cardname" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_holder']);?>">
                    </div>
                </div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['email']);?></label>
                    <div class="form-control">
                       <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('71'));?></div>
                       <input type="text" id="email" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['email']);?>">
                    </div>
                </div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['card_number']);?></label>
                    <div class="form-control">
                       <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('72'));?></div>
                       <input type="text" id="cardNumber" name="cardnumber" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_number']);?>">
                    </div>
                </div>
                <div class="pay_form_group_plus">
                    <div class="i_form_group_plus_extra">
                        <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['expiration_date']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73'));?></div>
                            <input type="text" id="expmonth" name="expmonth" class="inora_user_input" placeholder="DD">
                        </div>
                    </div>
                    <div class="i_form_group_plus_extra">
                        <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['expiration_year']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73'));?></div>
                            <input type="text" id="expyear" name="expyear" class="inora_user_input" placeholder="YY">
                        </div>
                    </div>
                    <div class="i_form_group_plus_extra">
                        <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['ccv_code']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('74'));?></div>
                            <input type="text" id="cvv" name="cvv" class="inora_user_input" placeholder="123">
                        </div>
                    </div>
                </div>
                <div class="pay_form_group">
                    <div class="pay_subscription transition point_purchase payMethod" data-type="iyzico"><?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($planAmount, $stripeCurrency));?></div>
                </div>
                <div class="pay_form_group">
                   <div class="i_pay_note">
                       <?php echo iN_HelpSecure($LANG['you_can_use_instantly']);?>
                   </div>
                </div>
           </div>
           </form>
           <!---->
       </div>
   </div>
<!--/CREDIT CARD FORM-->
