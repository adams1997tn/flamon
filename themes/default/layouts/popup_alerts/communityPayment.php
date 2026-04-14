<?php
$communityPaymentScope = isset($communityPaymentScope) ? $communityPaymentScope : 'community';
$subscriptionTypeValue = (string)$subscriptionType;
$planAmount = 0;
$communityTitle = '';
$communityCategory = '';
$communityDescription = '';
$communityCover = '';
$communityID = null;
$communityOwnerID = null;

if ($communityPaymentScope === 'community') {
    $communityID = isset($communityData['id']) ? (int)$communityData['id'] : 0;
    $communityOwnerID = isset($communityData['owner_user_id']) ? (int)$communityData['owner_user_id'] : 0;
    $communityTitle = $communityData['title'] ?? '';
    $communityCategory = $communityData['category'] ?? '';
    $communityDescription = $communityData['description'] ?? '';
    $communityCover = $communityData['cover_image'] ?? '';
    $planAmount = isset($communityData['monthly_price']) ? (float)$communityData['monthly_price'] : 0;
} else {
    $planAmount = isset($communityPlanAmount) ? (float)$communityPlanAmount : 0;
    $communityTitle = $LANG['community_plan'] ?? 'Community Plan';
}
$communityCategoryLabel = $communityCategory;
if (!empty($communityCategory) && isset($LANG[$communityCategory])) {
    $communityCategoryLabel = $LANG[$communityCategory];
}

$coverUrl = $base_url . 'uploads/web.png';
if (!empty($communityCover)) {
    if (function_exists('storage_public_url')) {
        $coverUrl = storage_public_url($communityCover);
    } else {
        $coverUrl = $base_url . $communityCover;
    }
}

$planDisplay = $subscriptionTypeValue === '2'
    ? iN_HelpSecure($planAmount) . html_entity_decode($iN->iN_SelectedMenuIcon('40'))
    : iN_HelpSecure(formatCurrency($planAmount, $defaultCurrency));

$pointValueRaw = isset($onePointEqual) ? (string)$onePointEqual : '0';
$pointValue = (float)str_replace(',', '.', $pointValueRaw);
$pointsAmount = 0;
$pointsAvailable = false;
if ($planAmount > 0) {
    if ($subscriptionTypeValue === '2') {
        $pointsAmount = (int)ceil($planAmount);
        $pointsAvailable = $pointsAmount > 0;
    } elseif ($pointValue > 0) {
        $pointsAmount = (int)ceil($planAmount / $pointValue);
        $pointsAvailable = $pointsAmount > 0;
    }
}

$stripeBetaFlag = isset($stripePaymentBeta) ? (string)$stripePaymentBeta : '0';
$ccbillBetaFlag = isset($ccbillBeta) ? (string)$ccbillBeta : '0';
$flutterwaveBetaFlag = isset($flutterWavePaymentBeta) ? (string)$flutterWavePaymentBeta : '0';
$iyzicoBetaFlag = isset($iyziCoPaymentBeta) ? (string)$iyziCoPaymentBeta : '0';
$yookassaBetaFlag = isset($yookassaPaymentBeta) ? (string)$yookassaPaymentBeta : '0';
$epochBetaFlag = isset($epochBeta) ? (string)$epochBeta : '0';
if (in_array($communityPaymentScope, ['community', 'community_plan'], true)) {
    $stripeBetaFlag = '0';
    $ccbillBetaFlag = '0';
    $flutterwaveBetaFlag = '0';
    $iyzicoBetaFlag = '0';
    $yookassaBetaFlag = '0';
    $epochBetaFlag = '0';
}
$isAdminUser = isset($userType) && $userType == '2';
$stripeStatusValue = in_array((string)$stripeStatus, ['1','2'], true);
$stripeFallbackStatus = (!$stripeStatusValue && isset($stripePaymentStatus) && (string)$stripePaymentStatus === '1');
$stripeEnabled = (($stripeStatusValue || $stripeFallbackStatus) && !empty($stripePublicKey));
$ccbillEnabled = ($ccbill_Status == '1');
$flutterwaveEnabled = (isset($flutterWavePaymentStatus) && (int)$flutterWavePaymentStatus === 1);
$ccbillHasCredentials = isset($ccbillActiveAndReady) ? (bool)$ccbillActiveAndReady : (!empty($ccbill_AccountNumber) && !empty($ccbill_SubAccountNumber) && !empty($ccbill_FlexID) && !empty($ccbill_SaltKey));
$stripeReady = $stripeEnabled && ($isAdminUser || $stripeBetaFlag !== '1');
$ccbillVisible = $ccbillEnabled && ($isAdminUser || $ccbillBetaFlag !== '1');
$ccbillReady = $ccbillVisible && $ccbillHasCredentials;
$flutterwaveVisible = $flutterwaveEnabled && ($isAdminUser || $flutterwaveBetaFlag !== '1');
$flutterwaveKeysReady = !empty($flutterWaveLivePublicKey) || !empty($flutterWaveTestPublicKey);
$flutterwaveReady = $flutterwaveVisible && $flutterwaveKeysReady;
$iyzicoEnabled = (isset($iyziCoPaymentStatus) && (int)$iyziCoPaymentStatus === 1);
$iyzicoTestMode = !isset($iyziCoPaymentMode) || (int)$iyziCoPaymentMode !== 1;
$iyzicoApiKey = $iyzicoTestMode ? (string)$iyziCoPaymentTestApiKey : (string)$iyziCoPaymentLiveApiKey;
$iyzicoSecretKey = $iyzicoTestMode ? (string)$iyziCoPaymentTestSecretKey : (string)$iyziCoPaymentLiveApiSecret;
$iyzicoKeysReady = ($iyzicoApiKey !== '' && $iyzicoSecretKey !== '');
$iyzicoVisible = $iyzicoEnabled && ($isAdminUser || $iyzicoBetaFlag !== '1');
$iyzicoReady = $iyzicoVisible && $iyzicoKeysReady;
$yookassaEnabled = (isset($yookassaPaymentStatus) && (int)$yookassaPaymentStatus === 1);
$yookassaTestMode = !isset($yookassaPaymentMode) || (int)$yookassaPaymentMode !== 1;
$yookassaShopId = $yookassaTestMode ? (string)$yookassaTestShopId : (string)$yookassaLiveShopId;
$yookassaSecretKey = $yookassaTestMode ? (string)$yookassaTestSecretKey : (string)$yookassaLiveSecretKey;
$yookassaKeysReady = ($yookassaShopId !== '' && $yookassaSecretKey !== '');
$yookassaVisible = $yookassaEnabled && ($isAdminUser || $yookassaBetaFlag !== '1');
$yookassaReady = $yookassaVisible && $yookassaKeysReady && $communityPaymentScope === 'community';
$epochEnabled = (isset($epochPaymentStatus) && (int)$epochPaymentStatus === 1);
$epochTestMode = !isset($epochPaymentMode) || (int)$epochPaymentMode !== 1;
$epochEndpoint = $epochTestMode ? (string)$epochTestEndpoint : (string)$epochLiveEndpoint;
$epochPiCodeReady = trim((string)$epochPiCode) !== '';
$epochEndpointReady = trim((string)$epochEndpoint) !== '';
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
} elseif ($pointsAvailable) {
    $defaultGateway = 'points';
} else {
    $defaultGateway = $stripeReady ? 'stripe' : ($ccbillVisible ? 'ccbill' : ($flutterwaveVisible ? 'flutterwave' : ($epochVisible ? 'epoch' : ($yookassaVisible ? 'yookassa' : 'points'))));
}
$stripeInitialAttr = $defaultGateway === 'stripe'
    ? ' data-display="block"'
    : ' data-display="block" hidden aria-hidden="true"';
$ccbillInitialAttr = $defaultGateway === 'ccbill'
    ? ' data-display="flex"'
    : ' data-display="flex" hidden aria-hidden="true"';
$flutterwaveInitialAttr = $defaultGateway === 'flutterwave'
    ? ' data-display="block"'
    : ' data-display="block" hidden aria-hidden="true"';
$iyzicoInitialAttr = $defaultGateway === 'iyzico'
    ? ' data-display="block"'
    : ' data-display="block" hidden aria-hidden="true"';
$epochInitialAttr = $defaultGateway === 'epoch'
    ? ' data-display="block"'
    : ' data-display="block" hidden aria-hidden="true"';
$yookassaInitialAttr = $defaultGateway === 'yookassa'
    ? ' data-display="block"'
    : ' data-display="block" hidden aria-hidden="true"';
$pointsInitialAttr = $defaultGateway === 'points'
    ? ' data-display="block"'
    : ' data-display="block" hidden aria-hidden="true"';

$payConfig = [
    'siteurl' => $base_url,
    'scope' => $communityPaymentScope,
    'communityID' => $communityID,
    'ownerID' => $communityOwnerID,
    'subscriberID' => $userID,
    'subscriberName' => $userFullName,
    'subscriberEmail' => $userEmail,
    'planAmount' => $planAmount,
    'lightDark' => $lightDark,
    'csrfToken' => csrf_get_token(),
    'request' => [
        'stripe' => $communityPaymentScope === 'community' ? 'communitySubscribe' : 'communityPlanSubscribe',
        'ccbill' => $communityPaymentScope === 'community' ? 'communitySubscribeWithCcbill' : 'communityPlanSubscribeWithCcbill',
        'flutterwave' => $communityPaymentScope === 'community' ? 'communitySubscribeWithFlutterwave' : 'communityPlanSubscribeWithFlutterwave',
        'iyzico' => $communityPaymentScope === 'community' ? 'communitySubscribeWithIyzico' : 'communityPlanSubscribeWithIyzico',
        'epoch' => $communityPaymentScope === 'community' ? 'communitySubscribeWithEpoch' : 'communityPlanSubscribeWithEpoch',
        'yookassa' => $communityPaymentScope === 'community' ? 'communitySubscribeWithYookassa' : ''
    ],
    'stripe' => [
        'enabled' => $stripeReady ? true : false,
        'publicKey' => $stripePublicKey,
        'currencySymbol' => $currencys[$stripeCurrency] ?? '$'
    ],
    'ccbill' => [
        'enabled' => $ccbillEnabled ? true : false,
        'currency' => $ccbill_Currency
    ],
    'flutterwave' => [
        'enabled' => $flutterwaveReady ? true : false,
        'testMode' => ($flutterWavePaymentMode !== '1') ? true : false,
        'testPublicKey' => $flutterWaveTestPublicKey,
        'livePublicKey' => $flutterWaveLivePublicKey,
        'currency' => $flutterWaveCurrency ?? $defaultCurrency ?? 'USD',
        'currencySymbol' => $currencys[$flutterWaveCurrency ?? $defaultCurrency ?? 'USD'] ?? '$',
        'amount' => $planAmount,
        'customerName' => $userFullName,
        'customerEmail' => $userEmail
    ],
    'iyzico' => [
        'enabled' => $iyzicoReady ? true : false,
        'currency' => $iyziCoPaymentCurrency ?? $defaultCurrency ?? 'USD',
        'amount' => $planAmount
    ],
    'epoch' => [
        'enabled' => $epochReady ? true : false
    ],
    'yookassa' => [
        'enabled' => $yookassaReady ? true : false
    ],
    'defaultGateway' => $defaultGateway
];
$payConfigEncoded = base64_encode(json_encode($payConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
<div class="i_modal_bg_in i_subs_modal pay_zindex community_pay_modal" data-pay-config="<?php echo iN_HelpSecure($payConfigEncoded); ?>" role="dialog" aria-modal="true" aria-labelledby="communityPaymentTitle">
    <div class="i_modal_in_in i_payment_pop_box">
        <div class="i_modal_content">
            <div class="payClose transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['close'] ?? 'Close'); ?>">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
            </div>
            <div class="community_pay_header">
                <?php if ($communityPaymentScope === 'community') { ?>
                    <div class="community_pay_cover">
                        <img src="<?php echo iN_HelpSecure($coverUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>">
                    </div>
                <?php } else { ?>
                    <div class="community_pay_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?>
                    </div>
                <?php } ?>
                <div class="community_pay_title" id="communityPaymentTitle">
                    <?php echo iN_HelpSecure($communityTitle); ?>
                </div>
                <?php if (!empty($communityCategory)) { ?>
                    <div class="community_pay_category">
                        <?php echo iN_HelpSecure($communityCategoryLabel); ?>
                    </div>
                <?php } ?>
                <div class="community_pay_price">
                    <?php echo iN_HelpSecure($LANG['community_monthly_price']); ?>: <?php echo $planDisplay; ?>
                </div>
            </div>

            <?php if (!$hasGateway && !$pointsAvailable) { ?>
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
                if ($pointsAvailable) {
                    $gatewayButtons[] = ['id' => 'points', 'label' => $LANG['points'] ?? 'Points'];
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
                            <button type="button" class="pay_subscription transition" aria-live="polite" data-label-pay="<?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($planAmount, $stripeCurrency)); ?>" data-label-processing="<?php echo iN_HelpSecure($LANG['processing'] ?? 'Processing...'); ?>">
                                <?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($planAmount, $stripeCurrency)); ?>
                            </button>
                        </div>
                        <div class="pay_form_group">
                            <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                        </div>
                    </div>
                </form>
                <?php } ?>

                <?php if ($ccbillVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="ccbill"<?php echo $ccbillInitialAttr; ?>>
                    <?php if ($ccbillReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['ccbill_redirect_note']); ?></div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_ccbill transition" data-community="<?php echo (int) $communityID; ?>">
                            <?php echo iN_HelpSecure($LANG['continue']); ?>
                        </button>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note"><?php echo iN_HelpSecure($LANG['ccbill_credentials_missing']); ?></div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($flutterwaveVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="flutterwave"<?php echo $flutterwaveInitialAttr; ?>>
                    <?php if ($flutterwaveReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['flutterwave_redirect_note'] ?? 'You will be redirected to Flutterwave to complete this payment.'); ?></div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_flutterwave transition">
                            <?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($planAmount, $flutterWaveCurrency ?? $defaultCurrency ?? 'USD')); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note"><?php echo iN_HelpSecure($LANG['flutterwave_not_available'] ?? 'Flutterwave payment is not available.'); ?></div>
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
                        <button type="button" class="pay_subscription_iyzico transition" data-community="<?php echo (int)$communityID; ?>">
                            <?php echo iN_HelpSecure($LANG['pay'] . ' ' . formatCurrency($planAmount, $iyziCoPaymentCurrency ?? $defaultCurrency ?? 'USD')); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note"><?php echo iN_HelpSecure($LANG['no_payment_method_available']); ?></div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($epochVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="epoch"<?php echo $epochInitialAttr; ?>>
                    <?php if ($epochReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['epoch_redirect_note'] ?? 'You will be redirected to EPOCH secure checkout to complete this payment.'); ?></div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_epoch transition" data-community="<?php echo (int) $communityID; ?>">
                            <?php echo iN_HelpSecure($LANG['continue']); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note"><?php echo iN_HelpSecure($LANG['epoch_not_available'] ?? ($LANG['no_payment_method_available'] ?? 'No payment method available.')); ?></div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($yookassaVisible) { ?>
                <div class="i_credit_card_form pay_gateway_section" data-provider="yookassa"<?php echo $yookassaInitialAttr; ?>>
                    <?php if ($yookassaReady) { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['yookassa_redirect_note']); ?></div>
                    </div>
                    <div class="pay_form_group">
                        <button type="button" class="pay_subscription_yookassa transition" data-community="<?php echo (int) $communityID; ?>">
                            <?php echo iN_HelpSecure($LANG['continue']); ?>
                        </button>
                    </div>
                    <div class="pay_form_group">
                        <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']); ?></div>
                    </div>
                    <?php } else { ?>
                    <div class="pay_form_group">
                        <div class="i_pay_note beta_note"><?php echo iN_HelpSecure($LANG['yookassa_not_available']); ?></div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($pointsAvailable) { ?>
                <div class="i_credit_card_form pay_gateway_section community_points_form" data-provider="points"<?php echo $pointsInitialAttr; ?>>
                    <div class="pay_form_group point_subs_not">
                        <?php echo iN_HelpSecure($LANG['community_points_note']); ?>
                    </div>
                    <?php if ($userCurrentPoints >= $pointsAmount) { ?>
                        <div class="pay_form_group">
                            <button type="button" class="pay_subscription_point transition communityPointSubscribe" data-community="<?php echo (int) $communityID; ?>" data-scope="<?php echo iN_HelpSecure($communityPaymentScope); ?>">
                                <?php echo iN_HelpSecure($LANG['pay']) . ' ' . iN_HelpSecure($pointsAmount) . html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
                            </button>
                        </div>
                    <?php } else { ?>
                        <div class="pay_form_group">
                            <div class="pay_subscription_point_renew transition">
                                <a href="<?php echo $base_url . 'purchase/purchase_point'; ?>">
                                    <?php echo iN_HelpSecure($LANG['you_dont_have_a_enough_point_to_subscribe']); ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php if ($stripeEnabled) { ?>
        <script src="https://js.stripe.com/v3/" id="stripe-js-v3"></script>
    <?php } ?>
    <?php if ($flutterwaveReady) { ?>
        <script src="https://checkout.flutterwave.com/v3.js"></script>
    <?php } ?>
    <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/payWithCreditCard.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
</div>
