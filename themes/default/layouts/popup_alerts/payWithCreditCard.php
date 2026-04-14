
<?php if ($subscriptionType == '1') {
    $stripeBetaFlag = isset($stripePaymentBeta) ? (string)$stripePaymentBeta : '0';
    $ccbillBetaFlag = isset($ccbillBeta) ? (string)$ccbillBeta : '0';
    $flutterwaveBetaFlag = isset($flutterWavePaymentBeta) ? (string)$flutterWavePaymentBeta : '0';
    $iyzicoBetaFlag = isset($iyziCoPaymentBeta) ? (string)$iyziCoPaymentBeta : '0';
    $epochBetaFlag = isset($epochBeta) ? (string)$epochBeta : '0';
    $isAdminUser = isset($userType) && $userType == '2';
    $stripeEnabled = (in_array((string)$stripeStatus, ['1','2'], true) && !empty($stripePublicKey));
    $ccbillEnabled = ($ccbill_Status == '1');
    $flutterwaveEnabled = (isset($flutterWavePaymentStatus) && (int)$flutterWavePaymentStatus === 1);
    $iyzicoEnabled = (isset($iyziCoPaymentStatus) && (int)$iyziCoPaymentStatus === 1);
    $iyzicoTestMode = !isset($iyziCoPaymentMode) || (int)$iyziCoPaymentMode !== 1;
    $iyzicoApiKey = $iyzicoTestMode ? (string)$iyziCoPaymentTestApiKey : (string)$iyziCoPaymentLiveApiKey;
    $iyzicoSecretKey = $iyzicoTestMode ? (string)$iyziCoPaymentTestSecretKey : (string)$iyziCoPaymentLiveApiSecret;
    $iyzicoKeysReady = ($iyzicoApiKey !== '' && $iyzicoSecretKey !== '');
    $yookassaBetaFlag = isset($yookassaPaymentBeta) ? (string)$yookassaPaymentBeta : '0';
    $yookassaEnabled = (isset($yookassaPaymentStatus) && (int)$yookassaPaymentStatus === 1);
    $yookassaTestMode = !isset($yookassaPaymentMode) || (int)$yookassaPaymentMode !== 1;
    $yookassaShopId = $yookassaTestMode ? (string)$yookassaTestShopId : (string)$yookassaLiveShopId;
    $yookassaSecretKey = $yookassaTestMode ? (string)$yookassaTestSecretKey : (string)$yookassaLiveSecretKey;
    $yookassaKeysReady = ($yookassaShopId !== '' && $yookassaSecretKey !== '');
    $epochEnabled = (isset($epochPaymentStatus) && (int)$epochPaymentStatus === 1);
    $epochTestMode = !isset($epochPaymentMode) || (int)$epochPaymentMode !== 1;
    $epochEndpoint = $epochTestMode ? (string)$epochTestEndpoint : (string)$epochLiveEndpoint;
    $epochPiCodeReady = trim((string)$epochPiCode) !== '';
    $epochEndpointReady = trim((string)$epochEndpoint) !== '';
    $ccbillHasCredentials = isset($ccbillActiveAndReady) ? (bool)$ccbillActiveAndReady : (!empty($ccbill_AccountNumber) && !empty($ccbill_SubAccountNumber) && !empty($ccbill_FlexID) && !empty($ccbill_SaltKey));
    $stripeReady = $stripeEnabled && ($isAdminUser || $stripeBetaFlag !== '1');
    $ccbillVisible = $ccbillEnabled && ($isAdminUser || $ccbillBetaFlag !== '1');
    $ccbillReady = $ccbillVisible && $ccbillHasCredentials;
    $flutterwaveVisible = $flutterwaveEnabled && ($isAdminUser || $flutterwaveBetaFlag !== '1');
    $flutterwaveKeysReady = !empty($flutterWaveLivePublicKey) || !empty($flutterWaveTestPublicKey);
    $flutterwaveReady = $flutterwaveVisible && $flutterwaveKeysReady;
    $iyzicoVisible = $iyzicoEnabled && ($isAdminUser || $iyzicoBetaFlag !== '1');
    $iyzicoReady = $iyzicoVisible && $iyzicoKeysReady;
    $yookassaVisible = $yookassaEnabled && ($isAdminUser || $yookassaBetaFlag !== '1');
    $yookassaReady = $yookassaVisible && $yookassaKeysReady;
    $epochVisible = $epochEnabled && ($isAdminUser || $epochBetaFlag !== '1');
    $epochReady = $epochVisible && $epochPiCodeReady && $epochEndpointReady;
    $hasGateway = ($stripeReady || $ccbillReady || $flutterwaveReady || $iyzicoReady || $yookassaReady || $epochReady);
    if ($stripeReady) {
        $defaultGateway = 'stripe';
    } elseif ($ccbillReady) {
        $defaultGateway = 'ccbill';
    } elseif ($flutterwaveReady) {
        $defaultGateway = 'flutterwave';
    } elseif ($iyzicoReady) {
        $defaultGateway = 'iyzico';
    } elseif ($epochReady) {
        $defaultGateway = 'epoch';
    } elseif ($yookassaReady) {
        $defaultGateway = 'yookassa';
    } else {
        $defaultGateway = $stripeReady
            ? 'stripe'
            : ($ccbillVisible ? 'ccbill' : ($flutterwaveVisible ? 'flutterwave' : ($iyzicoVisible ? 'iyzico' : ($epochVisible ? 'epoch' : 'yookassa'))));
    }
    $stripeInitialAttr = $defaultGateway === 'stripe'
        ? ' data-display="block" style="display:block;"'
        : ' data-display="block" hidden aria-hidden="true" style="display:none;"';
    $ccbillInitialAttr = $defaultGateway === 'ccbill'
        ? ' data-display="flex" style="display:flex;"'
        : ' data-display="flex" hidden aria-hidden="true" style="display:none;"';
    $flutterwaveInitialAttr = $defaultGateway === 'flutterwave'
        ? ' data-display="block" style="display:block;"'
        : ' data-display="block" hidden aria-hidden="true" style="display:none;"';
    $iyzicoInitialAttr = $defaultGateway === 'iyzico'
        ? ' data-display="block" style="display:block;"'
        : ' data-display="block" hidden aria-hidden="true" style="display:none;"';
    $epochInitialAttr = $defaultGateway === 'epoch'
        ? ' data-display="block" style="display:block;"'
        : ' data-display="block" hidden aria-hidden="true" style="display:none;"';
    $yookassaInitialAttr = $defaultGateway === 'yookassa'
        ? ' data-display="block" style="display:block;"'
        : ' data-display="block" hidden aria-hidden="true" style="display:none;"';
?>
<div class="i_modal_bg_in i_subs_modal pay_zindex">
    <div class="i_modal_in_in i_payment_pop_box">
        <div class="i_modal_content">
            <div class="payClose transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?></div>
            <div class="i_subscribing" style="background-image:url(<?php echo iN_HelpSecure($f_profileAvatar); ?>);"></div>
            <div class="i_subscribing_note" id="pln" data-p="<?php echo iN_HelpSecure($planID); ?>">
                <?php echo preg_replace('/{.*?}/', $f_userfullname, $LANG['subscription_payment']); ?>
            </div>
            <?php if (isset($taxStatus) && (string)$taxStatus === '1') { ?>
                <div class="tax_notice"><?php echo iN_HelpSecure($LANG['tax_settings_note']); ?></div>
            <?php } ?>
            <?php if (!$hasGateway) { ?>
                <div class="i_credit_card_form">
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['no_payment_method_available']); ?></div>
                    </div>
                </div>
            <?php } else { ?>
                <?php
                    $gatewayButtons = [];
                    if ($stripeReady) {
                        $gatewayButtons[] = ['id' => 'stripe', 'label' => $LANG['pay_with_card'] ?? 'Pay with card'];
                    }
                    if ($ccbillVisible) {
                        $gatewayButtons[] = ['id' => 'ccbill', 'label' => $LANG['pay_with_ccbill'] ?? 'Pay with CCBill'];
                    }
                    if ($flutterwaveVisible) {
                        $gatewayButtons[] = ['id' => 'flutterwave', 'label' => $LANG['pay_with_flutterwave'] ?? 'Pay with Flutterwave'];
                    }
                    if ($iyzicoVisible) {
                        $gatewayButtons[] = ['id' => 'iyzico', 'label' => $LANG['iyzico_payment'] ?? 'IyziCo'];
                    }
                    if ($epochVisible) {
                        $gatewayButtons[] = ['id' => 'epoch', 'label' => $LANG['pay_with_epoch'] ?? 'Pay with EPOCH'];
                    }
                    if ($yookassaVisible) {
                        $gatewayButtons[] = ['id' => 'yookassa', 'label' => $LANG['pay_with_yookassa'] ?? 'Pay with YooKassa'];
                    }
                    if (count($gatewayButtons) > 1) {
                ?>
                    <div class="pay_method_selector flex_ tabing_non_justify">
                        <?php foreach ($gatewayButtons as $button) { ?>
                            <button type="button" class="i_nex_btn_btn transition pay_provider_toggle <?php echo $defaultGateway === $button['id'] ? 'active' : ''; ?>" data-target="<?php echo iN_HelpSecure($button['id']); ?>">
                                <?php echo iN_HelpSecure($button['label']); ?>
                            </button>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($stripeReady) { ?>
                <form id="paymentFrm" class="pay_gateway_section" data-provider="stripe"<?php echo $stripeInitialAttr; ?> novalidate>
                    <div class="i_credit_card_form">
                        <div id="paymentResponse"></div>
                        <div class="pay_form_group">
                            <label for="name" class="form_label"><?php echo iN_HelpSecure($LANG['card_holder']); ?></label>
                            <div class="form-control">
                                <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('70')); ?></div>
                                <input type="text" id="name" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_holder']); ?>" autocomplete="cc-name" inputmode="text" maxlength="64" required>
                            </div>
                        </div>
                        <div class="pay_form_group">
                            <label for="email" class="form_label"><?php echo iN_HelpSecure($LANG['email']); ?></label>
                            <div class="form-control">
                                <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('71')); ?></div>
                                <input type="email" id="email" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['email']); ?>" autocomplete="email" inputmode="email" maxlength="120" required>
                            </div>
                        </div>
                        <div class="pay_form_group">
                            <label class="form_label" for="card_number"><?php echo iN_HelpSecure($LANG['card_number']); ?></label>
                            <div class="form-control">
                                <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('72')); ?></div>
                                <div id="card_number" class="inora_user_input" aria-label="Card number"></div>
                            </div>
                        </div>
                        <div class="pay_form_group_plus">
                            <div class="i_form_group_plus">
                                <label class="form_label" for="card_expiry"><?php echo iN_HelpSecure($LANG['expiration_date']); ?></label>
                                <div class="form-control">
                                    <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73')); ?></div>
                                    <div id="card_expiry" class="inora_user_input" aria-label="Card expiry"></div>
                                </div>
                            </div>
                            <div class="i_form_group_plus">
                                <label class="form_label" for="card_cvc"><?php echo iN_HelpSecure($LANG['ccv_code']); ?></label>
                                <div class="form-control">
                                    <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('74')); ?></div>
                                    <div id="card_cvc" class="inora_user_input" aria-label="Card CVC"></div>
                                </div>
                            </div>
                        </div>
                        <div class="pay_form_group">
                            <button type="button" class="pay_subscription transition" aria-live="polite" data-label-pay="<?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($f_PlanAmount, $stripeCurrency)); ?>" data-label-processing="<?php echo iN_HelpSecure($LANG['processing'] ?? 'Processing...'); ?>">
                                <?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($f_PlanAmount, $stripeCurrency)); ?>
                            </button>
                        </div>
                        <div class="pay_form_group">
                            <div class="i_pay_note">
                                <?php echo iN_HelpSecure($LANG['subscription_renew']); ?>
                            </div>
                        </div>
                    </div>
                </form>
                <?php } ?>

                <?php if ($ccbillVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="ccbill"<?php echo $ccbillInitialAttr; ?>>
                    <?php if ($ccbillReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note">
                            <?php echo iN_HelpSecure($LANG['ccbill_redirect_note']); ?>
                        </div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_ccbill transition" data-plan="<?php echo iN_HelpSecure($planID); ?>" data-creator="<?php echo iN_HelpSecure($f_userID); ?>" data-amount="<?php echo iN_HelpSecure($f_PlanAmount); ?>">
                            <?php echo iN_HelpSecure($LANG['continue']); ?>
                        </button>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note">
                            <?php echo iN_HelpSecure($LANG['ccbill_credentials_missing']); ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($flutterwaveVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="flutterwave"<?php echo $flutterwaveInitialAttr; ?>>
                    <?php if ($flutterwaveReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note">
                            <?php echo iN_HelpSecure($LANG['flutterwave_redirect_note'] ?? 'You will be redirected to Flutterwave to complete this payment.'); ?>
                        </div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_flutterwave transition" data-plan="<?php echo iN_HelpSecure($planID); ?>" data-creator="<?php echo iN_HelpSecure($f_userID); ?>">
                            <?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($f_PlanAmount, $flutterWaveCurrency ?? $defaultCurrency ?? 'USD')); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note">
                            <?php echo iN_HelpSecure($LANG['flutterwave_not_available'] ?? 'Flutterwave payment is not available.'); ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($iyzicoVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="iyzico"<?php echo $iyzicoInitialAttr; ?>>
                    <?php if ($iyzicoReady) { ?>
                    <div class="pay_form_group">
                        <label class="form_label"><?php echo iN_HelpSecure($LANG['card_holder']); ?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('70')); ?></div>
                            <input type="text" class="inora_user_input iyzico_card_name" maxlength="64" autocomplete="cc-name" inputmode="text" placeholder="<?php echo iN_HelpSecure($LANG['card_holder']); ?>">
                        </div>
                    </div>
                    <div class="pay_form_group">
                        <label class="form_label"><?php echo iN_HelpSecure($LANG['card_number']); ?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('72')); ?></div>
                            <input type="text" class="inora_user_input iyzico_card_number" maxlength="19" autocomplete="cc-number" inputmode="numeric" placeholder="<?php echo iN_HelpSecure($LANG['card_number']); ?>">
                        </div>
                    </div>
                    <div class="pay_form_group_plus">
                        <div class="i_form_group_plus">
                            <label class="form_label"><?php echo iN_HelpSecure($LANG['expiration_date']); ?></label>
                            <div class="form-control">
                                <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73')); ?></div>
                                <input type="text" class="inora_user_input iyzico_card_expiry" maxlength="5" autocomplete="cc-exp" inputmode="numeric" placeholder="00/00">
                            </div>
                        </div>
                        <div class="i_form_group_plus">
                            <label class="form_label"><?php echo iN_HelpSecure($LANG['ccv_code']); ?></label>
                            <div class="form-control">
                                <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('74')); ?></div>
                                <input type="text" class="inora_user_input iyzico_card_cvc" maxlength="4" autocomplete="cc-csc" inputmode="numeric" placeholder="<?php echo iN_HelpSecure($LANG['ccv_code']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_iyzico transition" data-plan="<?php echo iN_HelpSecure($planID); ?>" data-creator="<?php echo iN_HelpSecure($f_userID); ?>">
                            <?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($f_PlanAmount, $iyziCoPaymentCurrency ?? $defaultCurrency ?? 'USD')); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note">
                            <?php echo iN_HelpSecure($LANG['no_payment_method_available']); ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($yookassaVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="yookassa"<?php echo $yookassaInitialAttr; ?>>
                    <?php if ($yookassaReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note">
                            <?php echo iN_HelpSecure($LANG['yookassa_redirect_note']); ?>
                        </div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_yookassa transition" data-plan="<?php echo iN_HelpSecure($planID); ?>" data-creator="<?php echo iN_HelpSecure($f_userID); ?>">
                            <?php echo iN_HelpSecure($LANG['continue']); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note">
                            <?php echo iN_HelpSecure($LANG['yookassa_not_available']); ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($epochVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="epoch"<?php echo $epochInitialAttr; ?>>
                    <?php if ($epochReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note">
                            <?php echo iN_HelpSecure($LANG['epoch_redirect_note'] ?? 'You will be redirected to EPOCH secure checkout to complete this payment.'); ?>
                        </div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_epoch transition" data-plan="<?php echo iN_HelpSecure($planID); ?>" data-creator="<?php echo iN_HelpSecure($f_userID); ?>">
                            <?php echo iN_HelpSecure($LANG['continue']); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note">
                            <?php echo iN_HelpSecure($LANG['epoch_not_available'] ?? ($LANG['no_payment_method_available'] ?? 'No payment method available.')); ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <script>
        window.payWithCardData = {
            siteurl: "<?php echo iN_HelpSecure($base_url); ?>",
            planID: "<?php echo iN_HelpSecure($planID); ?>",
            userID: "<?php echo iN_HelpSecure($f_userID); ?>",
            subscriberID: "<?php echo iN_HelpSecure($userID); ?>",
            subscriberName: "<?php echo iN_HelpSecure($userFullName); ?>",
            subscriberEmail: "<?php echo iN_HelpSecure($userEmail); ?>",
            planAmount: "<?php echo iN_HelpSecure($f_PlanAmount); ?>",
            lightDark: "<?php echo iN_HelpSecure($lightDark); ?>",
            csrfToken: "<?php echo iN_HelpSecure(csrf_get_token()); ?>",
            request: {
                stripe: "subscribeMe",
                ccbill: "subscribeWithCcbill",
                flutterwave: "subscribeWithFlutterwave",
                iyzico: "subscribeWithIyzico",
                epoch: "subscribeWithEpoch",
                yookassa: "subscribeWithYookassa"
            },
            stripe: {
                enabled: <?php echo $stripeReady ? 'true' : 'false'; ?>,
                publicKey: "<?php echo iN_HelpSecure($stripePublicKey); ?>",
                currencySymbol: "<?php echo iN_HelpSecure($currencys[$stripeCurrency]); ?>"
            },
            ccbill: {
                enabled: <?php echo $ccbillEnabled ? 'true' : 'false'; ?>,
                currency: "<?php echo iN_HelpSecure($ccbill_Currency); ?>"
            },
            flutterwave: {
                enabled: <?php echo $flutterwaveReady ? 'true' : 'false'; ?>,
                testMode: <?php echo ($flutterWavePaymentMode !== '1') ? 'true' : 'false'; ?>,
                testPublicKey: "<?php echo iN_HelpSecure($flutterWaveTestPublicKey); ?>",
                livePublicKey: "<?php echo iN_HelpSecure($flutterWaveLivePublicKey); ?>",
                currency: "<?php echo iN_HelpSecure($flutterWaveCurrency ?? $defaultCurrency ?? 'USD'); ?>",
                currencySymbol: "<?php echo iN_HelpSecure($currencys[$flutterWaveCurrency ?? $defaultCurrency ?? 'USD']); ?>",
                amount: "<?php echo iN_HelpSecure($f_PlanAmount); ?>",
                customerName: "<?php echo iN_HelpSecure($userFullName); ?>",
                customerEmail: "<?php echo iN_HelpSecure($userEmail); ?>"
            },
            iyzico: {
                enabled: <?php echo $iyzicoReady ? 'true' : 'false'; ?>,
                currency: "<?php echo iN_HelpSecure($iyziCoPaymentCurrency ?? $defaultCurrency ?? 'USD'); ?>",
                amount: "<?php echo iN_HelpSecure($f_PlanAmount); ?>"
            },
            epoch: {
                enabled: <?php echo $epochReady ? 'true' : 'false'; ?>
            },
            yookassa: {
                enabled: <?php echo $yookassaReady ? 'true' : 'false'; ?>
            },
            defaultGateway: "<?php echo iN_HelpSecure($defaultGateway); ?>"
        };
    </script>
    <?php if ($stripeEnabled) { ?>
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
    <?php } ?>
    <?php if ($flutterwaveReady) { ?>
        <script src="https://checkout.flutterwave.com/v3.js"></script>
    <?php } ?>
    <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/payWithCreditCard.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
</div>
<?php } ?>
