<?php
global $base_url;
global $payPalPaymentMode;
global $payPalPaymentStatus;
global $payPalPaymentSedboxBusinessEmail;
global $payPalPaymentProductBusinessEmail;
global $payPalCurrency;

global $bitPayPaymentMode;
global $bitPayPaymentStatus;
global $bitPayPaymentNotificationEmail;
global $bitPayPaymentPassword;
global $bitPayPaymentPairingCode;
global $bitPayPaymentLabel;
global $bitPayPaymentCurrency;

global $stripePaymentMode;
global $stripePaymentStatus;
global $stripePaymentTestSecretKey;
global $stripePaymentTestPublicKey;
global $stripePaymentLiveSecretKey;
global $stripePaymentLivePublicKey;
global $stripePaymentCurrency;

global $autHorizePaymentMode;
global $autHorizePaymentStatus;
global $autHorizePaymentTestsApID;
global $autHorizePaymentTestTransitionKey;
global $autHorizePaymentLiveApID;
global $autHorizePaymentLiveTransitionkey;
global $autHorizePaymentCurrency;

global $iyziCoPaymentMode;
global $iyziCoPaymentStatus;
global $iyziCoPaymentTestSecretKey;
global $iyziCoPaymentTestApiKey;
global $iyziCoPaymentLiveApiKey;
global $iyziCoPaymentLiveApiSecret;
global $iyziCoPaymentCurrency;

global $razorPayPaymentMode;
global $razorPayPaymentStatus;
global $razorPayPaymentTestKeyID;
global $razorPayPaymentTestSecretKey;
global $razorPayPaymentLiveKeyID;
global $razorPayPaymentLiveSecretKey;
global $razorPayPaymentCurrency;

global $payStackPaymentMode;
global $payStackPaymentStatus;
global $payStackPaymentTestSecretKey;
global $payStackPaymentTestPublicKey;
global $payStackPaymentLiveSecretKey;
global $payStackPaymentLivePublicKey;
global $payStackPaymentCurrency;
global $flutterWavePaymentMode;
global $flutterWavePaymentStatus;
global $flutterWaveTestPublicKey;
global $flutterWaveTestSecretKey;
global $flutterWaveLivePublicKey;
global $flutterWaveLiveSecretKey;
global $flutterWaveCurrency;
global $flutterWaveSecretHash;
global $flutterWaveEncryptionKey;
global $flutterWavePaymentBeta;
global $currencys;

global $mercadoPagoMode;
global $mercadoPagoPaymentStatus;
global $mercadoPagoTestAccessTokenID;
global $mercadoPagoLiveAccessTokenID;
global $mercadoPagoCurrency;
global $monerooMode;
global $monerooPaymentStatus;
global $monerooTestProjectId;
global $monerooTestApiKey;
global $monerooLiveProjectId;
global $monerooLiveApiKey;
global $monerooWebhookSecret;
global $monerooCurrency;
global $nowPaymentsMode;
global $nowPaymentsPaymentStatus;
global $nowPaymentsTestApiKey;
global $nowPaymentsLiveApiKey;
global $nowPaymentsIpnSecret;
global $nowPaymentsCurrency;
global $nowPaymentsBeta;
global $yookassaPaymentMode;
global $yookassaPaymentStatus;
global $yookassaTestShopId;
global $yookassaTestSecretKey;
global $yookassaLiveShopId;
global $yookassaLiveSecretKey;
global $yookassaWebhookSecret;
global $yookassaCurrency;
global $yookassaPaymentBeta;
global $epochPaymentMode;
global $epochPaymentStatus;
global $epochPiCode;
global $epochCurrency;
global $epochTestEndpoint;
global $epochLiveEndpoint;
global $epochPostbackEnabled;
global $epochPostbackSecret;
global $epochPostbackAllowlist;
global $epochReturnMode;
global $epochTemplate;
global $epochBeta;
global $paysafecardMode;
global $paysafecardPaymentStatus;
global $paysafecardApiKey;
global $paysafecardCurrency;
global $paysafecardPaymentBeta;
global $ccbill_AccountNumber;
global $ccbill_SubAccountNumber;
global $ccbill_FlexID;
global $ccbill_SaltKey;
global $ccbill_Status;
global $ccbill_Currency;
global $ccbill_CancelUsername;
global $ccbill_CancelPassword;

/**
 * Ensure gateway globals fall back to i_payment_methods even if inc.php has
 * not primed them (for example during CLI tests).
 */
$__paymentMethodRow = [];
if (class_exists('DB')) {
    try {
        $__paymentMethodRow = DB::one("SELECT * FROM i_payment_methods WHERE payment_method_id = 1");
    } catch (Throwable $th) {
        $__paymentMethodRow = [];
    }
}

if (!function_exists('__pc_assign_if_empty')) {
    function __pc_assign_if_empty(&$var, array $row, string $key, $fallback = null): void
    {
        if ((isset($var) && $var !== '' && $var !== null) || !isset($row[$key])) {
            if (!isset($var)) {
                $var = $fallback;
            }
            return;
        }
        $var = $row[$key];
    }
}

if (!function_exists('__pc_currency_symbol')) {
    function __pc_currency_symbol($code, $default = 'USD')
    {
        global $currencys;
        if (is_array($currencys) && isset($currencys[$code])) {
            return $currencys[$code];
        }
        return $currencys[$default] ?? $default;
    }
}

__pc_assign_if_empty($ccbill_Status, $__paymentMethodRow, 'ccbill_status', 0);
__pc_assign_if_empty($ccbill_AccountNumber, $__paymentMethodRow, 'ccbill_account_number', '');
__pc_assign_if_empty($ccbill_SubAccountNumber, $__paymentMethodRow, 'ccbill_subaccount_number', '');
__pc_assign_if_empty($ccbill_FlexID, $__paymentMethodRow, 'ccbill_flex_form_id', '');
__pc_assign_if_empty($ccbill_SaltKey, $__paymentMethodRow, 'ccbill_salt_key', '');
__pc_assign_if_empty($ccbill_CancelUsername, $__paymentMethodRow, 'ccbill_cancel_username', '');
__pc_assign_if_empty($ccbill_CancelPassword, $__paymentMethodRow, 'ccbill_cancel_password', '');
__pc_assign_if_empty($ccbill_Currency, $__paymentMethodRow, 'ccbill_currency', $defaultCurrency ?? 'USD');

__pc_assign_if_empty($payPalPaymentMode, $__paymentMethodRow, 'paypal_payment_mode', 0);
__pc_assign_if_empty($payPalPaymentStatus, $__paymentMethodRow, 'paypal_active_pasive', 0);
__pc_assign_if_empty($payPalPaymentSedboxBusinessEmail, $__paymentMethodRow, 'paypal_sendbox_business_email', '');
__pc_assign_if_empty($payPalPaymentProductBusinessEmail, $__paymentMethodRow, 'paypal_product_business_email', '');
__pc_assign_if_empty($payPalCurrency, $__paymentMethodRow, 'paypal_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($payPalPaymentBeta, $__paymentMethodRow, 'paypal_beta', '0');

__pc_assign_if_empty($payStackPaymentMode, $__paymentMethodRow, 'paystack_payment_mode', 0);
__pc_assign_if_empty($payStackPaymentStatus, $__paymentMethodRow, 'paystack_active_pasive', 0);
__pc_assign_if_empty($payStackPaymentTestSecretKey, $__paymentMethodRow, 'paystack_test_secret_key', '');
__pc_assign_if_empty($payStackPaymentTestPublicKey, $__paymentMethodRow, 'paystack_test_public_key', '');
__pc_assign_if_empty($payStackPaymentLiveSecretKey, $__paymentMethodRow, 'paystack_live_secret_key', '');
__pc_assign_if_empty($payStackPaymentLivePublicKey, $__paymentMethodRow, 'paystack_live_public_key', '');
__pc_assign_if_empty($payStackPaymentCurrency, $__paymentMethodRow, 'paystack_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($payStackPaymentBeta, $__paymentMethodRow, 'paystack_beta', '0');
__pc_assign_if_empty($flutterWavePaymentMode, $__paymentMethodRow, 'flutterwave_payment_mode', 0);
__pc_assign_if_empty($flutterWavePaymentStatus, $__paymentMethodRow, 'flutterwave_active_pasive', 0);
__pc_assign_if_empty($flutterWaveTestPublicKey, $__paymentMethodRow, 'flutterwave_test_public_key', '');
__pc_assign_if_empty($flutterWaveTestSecretKey, $__paymentMethodRow, 'flutterwave_test_secret_key', '');
__pc_assign_if_empty($flutterWaveLivePublicKey, $__paymentMethodRow, 'flutterwave_live_public_key', '');
__pc_assign_if_empty($flutterWaveLiveSecretKey, $__paymentMethodRow, 'flutterwave_live_secret_key', '');
__pc_assign_if_empty($flutterWaveCurrency, $__paymentMethodRow, 'flutterwave_currency', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($flutterWaveSecretHash, $__paymentMethodRow, 'flutterwave_secret_hash', '');
__pc_assign_if_empty($flutterWaveEncryptionKey, $__paymentMethodRow, 'flutterwave_encryption_key', '');
__pc_assign_if_empty($flutterWavePaymentBeta, $__paymentMethodRow, 'flutterwave_beta', '0');

__pc_assign_if_empty($stripePaymentMode, $__paymentMethodRow, 'stripe_payment_mode', 0);
__pc_assign_if_empty($stripePaymentStatus, $__paymentMethodRow, 'stripe_active_pasive', 0);
__pc_assign_if_empty($stripePaymentTestSecretKey, $__paymentMethodRow, 'stripe_test_secret_key', '');
__pc_assign_if_empty($stripePaymentTestPublicKey, $__paymentMethodRow, 'stripe_test_public_key', '');
__pc_assign_if_empty($stripePaymentLiveSecretKey, $__paymentMethodRow, 'stripe_live_secret_key', '');
__pc_assign_if_empty($stripePaymentLivePublicKey, $__paymentMethodRow, 'stripe_live_public_key', '');
__pc_assign_if_empty($stripePaymentCurrency, $__paymentMethodRow, 'stripe_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($stripePaymentBeta, $__paymentMethodRow, 'stripe_beta', '0');

__pc_assign_if_empty($razorPayPaymentMode, $__paymentMethodRow, 'razorpay_payment_mode', 0);
__pc_assign_if_empty($razorPayPaymentStatus, $__paymentMethodRow, 'razorpay_active_pasive', 0);
__pc_assign_if_empty($razorPayPaymentTestKeyID, $__paymentMethodRow, 'razorpay_test_key_id', '');
__pc_assign_if_empty($razorPayPaymentTestSecretKey, $__paymentMethodRow, 'razorpay_test_secret_key', '');
__pc_assign_if_empty($razorPayPaymentLiveKeyID, $__paymentMethodRow, 'razorpay_live_key_id', '');
__pc_assign_if_empty($razorPayPaymentLiveSecretKey, $__paymentMethodRow, 'razorpay_live_secret_key', '');
__pc_assign_if_empty($razorPayPaymentCurrency, $__paymentMethodRow, 'razorpay_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($razorPayPaymentBeta, $__paymentMethodRow, 'razorpay_beta', '0');

__pc_assign_if_empty($iyziCoPaymentMode, $__paymentMethodRow, 'iyzico_payment_mode', 0);
__pc_assign_if_empty($iyziCoPaymentStatus, $__paymentMethodRow, 'iyzico_active_pasive', 0);
__pc_assign_if_empty($iyziCoPaymentTestSecretKey, $__paymentMethodRow, 'iyzico_test_secret_key', '');
__pc_assign_if_empty($iyziCoPaymentTestApiKey, $__paymentMethodRow, 'iyzico_test_api_key', '');
__pc_assign_if_empty($iyziCoPaymentLiveApiSecret, $__paymentMethodRow, 'iyzico_live_secret_key', '');
__pc_assign_if_empty($iyziCoPaymentLiveApiKey, $__paymentMethodRow, 'iyzico_live_api_key', '');
__pc_assign_if_empty($iyziCoPaymentCurrency, $__paymentMethodRow, 'iyzico_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($iyziCoPaymentBeta, $__paymentMethodRow, 'iyzico_beta', '0');

__pc_assign_if_empty($autHorizePaymentMode, $__paymentMethodRow, 'authorize_payment_mode', 0);
__pc_assign_if_empty($autHorizePaymentStatus, $__paymentMethodRow, 'authorize_active_pasive', 0);
__pc_assign_if_empty($autHorizePaymentTestsApID, $__paymentMethodRow, 'authorize_test_api_login_id', '');
__pc_assign_if_empty($autHorizePaymentTestTransitionKey, $__paymentMethodRow, 'authorize_test_transaction_key', '');
__pc_assign_if_empty($autHorizePaymentLiveApID, $__paymentMethodRow, 'authorize_live_api_login_id', '');
__pc_assign_if_empty($autHorizePaymentLiveTransitionkey, $__paymentMethodRow, 'authorize_live_transaction_key', '');
__pc_assign_if_empty($autHorizePaymentCurrency, $__paymentMethodRow, 'authorize_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($autHorizePaymentBeta, $__paymentMethodRow, 'authorize_beta', '0');

__pc_assign_if_empty($bitPayPaymentMode, $__paymentMethodRow, 'bitpay_payment_mode', 0);
__pc_assign_if_empty($bitPayPaymentStatus, $__paymentMethodRow, 'bitpay_active_pasive', 0);
__pc_assign_if_empty($bitPayPaymentNotificationEmail, $__paymentMethodRow, 'bitpay_notification_email', '');
__pc_assign_if_empty($bitPayPaymentPassword, $__paymentMethodRow, 'bitpay_password', '');
__pc_assign_if_empty($bitPayPaymentPairingCode, $__paymentMethodRow, 'bitpay_pairing_code', '');
__pc_assign_if_empty($bitPayPaymentLabel, $__paymentMethodRow, 'bitpay_label', '');
__pc_assign_if_empty($bitPayPaymentCurrency, $__paymentMethodRow, 'bitpay_crncy', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($bitPayPaymentBeta, $__paymentMethodRow, 'bitpay_beta', '0');

__pc_assign_if_empty($nowPaymentsMode, $__paymentMethodRow, 'nowpayments_payment_mode', 0);
__pc_assign_if_empty($nowPaymentsPaymentStatus, $__paymentMethodRow, 'nowpayments_active_pasive', 0);
__pc_assign_if_empty($nowPaymentsTestApiKey, $__paymentMethodRow, 'nowpayments_test_api_key', '');
__pc_assign_if_empty($nowPaymentsLiveApiKey, $__paymentMethodRow, 'nowpayments_live_api_key', '');
__pc_assign_if_empty($nowPaymentsIpnSecret, $__paymentMethodRow, 'nowpayments_ipn_secret', '');
__pc_assign_if_empty($nowPaymentsCurrency, $__paymentMethodRow, 'nowpayments_currency', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($nowPaymentsBeta, $__paymentMethodRow, 'nowpayments_beta', '0');
__pc_assign_if_empty($yookassaPaymentMode, $__paymentMethodRow, 'yookassa_payment_mode', 0);
__pc_assign_if_empty($yookassaPaymentStatus, $__paymentMethodRow, 'yookassa_active_pasive', 0);
__pc_assign_if_empty($yookassaTestShopId, $__paymentMethodRow, 'yookassa_test_shop_id', '');
__pc_assign_if_empty($yookassaTestSecretKey, $__paymentMethodRow, 'yookassa_test_secret_key', '');
__pc_assign_if_empty($yookassaLiveShopId, $__paymentMethodRow, 'yookassa_live_shop_id', '');
__pc_assign_if_empty($yookassaLiveSecretKey, $__paymentMethodRow, 'yookassa_live_secret_key', '');
__pc_assign_if_empty($yookassaWebhookSecret, $__paymentMethodRow, 'yookassa_webhook_secret', '');
__pc_assign_if_empty($yookassaCurrency, $__paymentMethodRow, 'yookassa_currency', 'RUB');
__pc_assign_if_empty($yookassaPaymentBeta, $__paymentMethodRow, 'yookassa_beta', '0');
__pc_assign_if_empty($epochPaymentMode, $__paymentMethodRow, 'epoch_payment_mode', '0');
__pc_assign_if_empty($epochPaymentStatus, $__paymentMethodRow, 'epoch_active_pasive', '0');
__pc_assign_if_empty($epochPiCode, $__paymentMethodRow, 'epoch_pi_code', '');
__pc_assign_if_empty($epochCurrency, $__paymentMethodRow, 'epoch_currency', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($epochTestEndpoint, $__paymentMethodRow, 'epoch_test_endpoint', 'https://wnu.com/secure/services/');
__pc_assign_if_empty($epochLiveEndpoint, $__paymentMethodRow, 'epoch_live_endpoint', 'https://wnu.com/secure/services/');
__pc_assign_if_empty($epochPostbackEnabled, $__paymentMethodRow, 'epoch_postback_enabled', '1');
__pc_assign_if_empty($epochPostbackSecret, $__paymentMethodRow, 'epoch_postback_secret', '');
__pc_assign_if_empty($epochPostbackAllowlist, $__paymentMethodRow, 'epoch_postback_allowlist', '');
__pc_assign_if_empty($epochReturnMode, $__paymentMethodRow, 'epoch_return_mode', 'pending');
__pc_assign_if_empty($epochTemplate, $__paymentMethodRow, 'epoch_template', '');
__pc_assign_if_empty($epochBeta, $__paymentMethodRow, 'epoch_beta', '0');
__pc_assign_if_empty($paysafecardPaymentStatus, $__paymentMethodRow, 'paysafecard_status', 0);
__pc_assign_if_empty($paysafecardMode, $__paymentMethodRow, 'paysafecard_mode', 'test');
__pc_assign_if_empty($paysafecardApiKey, $__paymentMethodRow, 'paysafecard_api_key', '');
__pc_assign_if_empty($paysafecardCurrency, $__paymentMethodRow, 'paysafecard_currency', $defaultCurrency ?? 'USD');
__pc_assign_if_empty($paysafecardPaymentBeta, $__paymentMethodRow, 'paysafecard_beta', '0');
// Config Paths
$inoraPaymentConfig = [

    /* Base Path of app
    ------------------------------------------------------------------------- */
    'base_url' =>  $base_url,

    'payments' => [
        /* Gateway Configuration key
        ------------------------------------------------------------------------- */
        'gateway_configuration' => [
            'paypal' => [
                'enable'                        => $payPalPaymentStatus === 1 ? true : false,
                'testMode'                      => ($payPalPaymentMode != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                       => 'Paypal', //payment gateway name
                'paypalSandboxBusinessEmail'        => $payPalPaymentSedboxBusinessEmail, //paypal sandbox business email
                'paypalProductionBusinessEmail'     => $payPalPaymentProductBusinessEmail, //paypal production business email
                'currency'                  => $payPalCurrency, //currency
                'currencySymbol'              => __pc_currency_symbol($payPalCurrency),
                'paypalSandboxUrl'          => 'https://www.sandbox.paypal.com/cgi-bin/webscr', //paypal sandbox test mode Url
                'paypalProdUrl'             => 'https://www.paypal.com/cgi-bin/webscr', //paypal production mode Url
                'notifyIpnURl'              => 'payment-response.php', //paypal ipn request notify Url
                'cancelReturn'              => 'payment-response.php', //cancel payment Url
                'callbackUrl'               => 'payment-response.php', //callback Url after payment successful
                'privateItems'              => []
            ],
            'paystack' => [
                'enable'                    => $payStackPaymentMode === 1 ? true : false,
                'testMode'                  => ($payStackPaymentStatus != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                   => 'Paystack', //payment gateway name
                'currency'                  => $payStackPaymentCurrency, //currency
                'currencySymbol'              => __pc_currency_symbol($payStackPaymentCurrency),
                'paystackTestingSecretKey'         => $payStackPaymentTestSecretKey, //paystack testing secret key
                'paystackTestingPublicKey'         => $payStackPaymentTestPublicKey, //paystack testing public key
                'paystackLiveSecretKey'         => $payStackPaymentLiveSecretKey, //paystack live secret key
                'paystackLivePublicKey'         => $payStackPaymentLivePublicKey, //paystack live public key
                'callbackUrl'               => $base_url.'payment-response.php', //callback Url after payment successful
                'privateItems'              => [
                                                $payStackPaymentTestSecretKey,
                                                $payStackPaymentTestPublicKey
                                            ]
            ],
            'flutterwave' => [
                'enable'                    => ((int)$flutterWavePaymentStatus === 1),
                'testMode'                  => ($flutterWavePaymentMode != 1 ? true : false),
                'gateway'                   => 'Flutterwave',
                'currency'                  => $flutterWaveCurrency,
                'currencySymbol'            => __pc_currency_symbol($flutterWaveCurrency),
                'testPublicKey'             => $flutterWaveTestPublicKey,
                'testSecretKey'             => $flutterWaveTestSecretKey,
                'livePublicKey'             => $flutterWaveLivePublicKey,
                'liveSecretKey'             => $flutterWaveLiveSecretKey,
                'secretHash'                => $flutterWaveSecretHash,
                'encryptionKey'             => $flutterWaveEncryptionKey,
                'sandboxVerifyUrl'          => 'https://api.flutterwave.com/v3/transactions/%s/verify',
                'productionVerifyUrl'       => 'https://api.flutterwave.com/v3/transactions/%s/verify',
                'callbackUrl'               => $base_url.'payment-response.php',
                'privateItems'              => [
                    'testSecretKey',
                    'liveSecretKey',
                    'secretHash',
                    'encryptionKey'
                ]
            ],
            'stripe'    => [
                'enable'                    => $stripePaymentStatus === 1 ? true : false,
                'testMode'                  => ($stripePaymentMode != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                   => 'Stripe', //payment gateway name
                'locale'                    => 'auto', //set local as auto
                'allowRememberMe'           => false, //set remember me ( true or false)
                'currency'                  => $stripePaymentCurrency, //currency
                'currencySymbol'              => __pc_currency_symbol($stripePaymentCurrency),
                'paymentMethodTypes'         => [
                    // before activating additional payment methods
                    // make sure that these methods are enabled in your stripe account
                    // https://dashboard.stripe.com/settings/payments
                    'card',
                    //'ideal',
                    // 'bancontact',
                    // 'giropay',
                    // 'p24',
                    // 'eps'
                ],
                'stripeTestingSecretKey'    => $stripePaymentTestSecretKey, //Stripe testing Secret Key
                'stripeTestingPublishKey'   => $stripePaymentTestPublicKey, //Stripe testing Publish Key
                'stripeLiveSecretKey'       => $stripePaymentLiveSecretKey, //Stripe Secret live Key
                'stripeLivePublishKey'      => $stripePaymentLivePublicKey, //Stripe live Publish Key
                'callbackUrl'               => 'payment-response.php', //callback Url after payment successful
                'privateItems'              => [
                    'stripeTestingPublishKey',
                    'stripeLivePublishKey'
                ]
            ],
            'razorpay'    => [
                'enable'                    => $razorPayPaymentMode === 1 ? true : false,
                'testMode'                  => ($razorPayPaymentStatus != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                   => 'Razorpay', //payment gateway name
                'merchantname'              => 'John', //merchant name
                'themeColor'                => '#1e88e5', //set razorpay widget theme color
                'currency'                  => $razorPayPaymentCurrency, //currency
                'currencySymbol'              => __pc_currency_symbol($razorPayPaymentCurrency),
                'razorpayTestingkeyId'      => $razorPayPaymentTestKeyID, //razorpay testing Api Key
                'razorpayTestingSecretkey'  => $razorPayPaymentTestSecretKey, //razorpay testing Api Secret Key
                'razorpayLivekeyId'         => $razorPayPaymentLiveKeyID, //razorpay live Api Key
                'razorpayLiveSecretkey'     => $razorPayPaymentLiveSecretKey, //razorpay live Api Secret Key
                'callbackUrl'               => $base_url.'payment-response.php', //callback Url after payment successful
                'privateItems'              => [
                                                'razorpayTestingSecretkey',
                                                'razorpayLiveSecretkey'
                                            ]
            ],
            'iyzico'    => [
                'enable'                    => $iyziCoPaymentMode === 1 ? true : false,
                'testMode'                  => ($iyziCoPaymentStatus != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                   => 'Iyzico', //payment gateway name
                'conversation_id'           => 'CONVERS' . uniqid(), //generate random conversation id
                'currency'                  => $iyziCoPaymentCurrency, //currency
                'currencySymbol'              => __pc_currency_symbol($iyziCoPaymentCurrency),
                'subjectType'               => 1, // credit
                'txnType'                   => 2, // renewal
                'subscriptionPlanType'      => 1, //txn status
                'iyzicoTestingSecretkey'    => $iyziCoPaymentTestSecretKey, //iyzico testing Secret Key
                'iyzicoTestingApiKey'       => $iyziCoPaymentTestApiKey, //iyzico live Api Key
                'iyzicoLiveApiKey'          => $iyziCoPaymentLiveApiSecret, //iyzico live Api Key
                'iyzicoLiveSecretkey'       => $iyziCoPaymentLiveApiKey, //iyzico live Secret Key
                'iyzicoSandboxModeUrl'      => 'https://sandbox-api.iyzipay.com', //iyzico Sandbox test mode Url
                'iyzicoProductionModeUrl'   => 'https://api.iyzipay.com', //iyzico production mode Url
                'callbackUrl'               => 'payment-response.php', //callback Url after payment successful
                'privateItems'              => [
                                                'iyzicoTestingApiKey',
                                                'iyzicoTestingSecretkey',
                                                'iyzicoLiveApiKey',
                                                'iyzicoLiveSecretkey'
                                            ]
            ],
            'authorize-net'    => [
                'enable'                         => $autHorizePaymentMode === 1 ? true : false,
                'testMode'                       => ($autHorizePaymentStatus  != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                        => 'Authorize.net', //payment gateway name
                'reference_id'                   => 'REF' . uniqid(), //generate random conversation id
                'currency'                       => $autHorizePaymentCurrency, //currency
                'currencySymbol'                 => __pc_currency_symbol($autHorizePaymentCurrency),
                'type'                           => 'individual',
                'txnType'                        => 'authCaptureTransaction',
                'authorizeNetTestApiLoginId'     => $autHorizePaymentTestsApID, //authorize-net testing Api login id
                'authorizeNetTestTransactionKey' => $autHorizePaymentTestTransitionKey, //Authorize.net testing transaction key
                'authorizeNetLiveApiLoginId'     => $autHorizePaymentLiveApID, //Authorize.net live Api login id
                'authorizeNetLiveTransactionKey' => $autHorizePaymentLiveTransitionkey, //Authorize.net live transaction key
                'callbackUrl'                    => 'payment-response.php', //callback Url after payment successful
                'privateItems'                  => [
                                                    'authorizeNetTestApiLoginId',
                                                    'authorizeNetTestTransactionKey',
                                                    'authorizeNetLiveApiLoginId',
                                                    'authorizeNetLiveTransactionKey'
                                                ]
            ],
            'bitpay'    => [
                'enable'                        => $bitPayPaymentMode === 1 ? true : false,
                'testMode'                      => ($bitPayPaymentStatus != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'notificationEmail'             => $bitPayPaymentNotificationEmail, // Merchant Email
                'gateway'                       => 'BitPay', //payment gateway name
                'currency'                      => $bitPayPaymentCurrency, //currency
                'currencySymbol'                => __pc_currency_symbol($bitPayPaymentCurrency), //currency Symbol
                'password'                      => $bitPayPaymentPassword, // Password for "EncryptedFilesystemStorage"
                'pairingCode'                   => $bitPayPaymentPairingCode, // Your pairing Code
                'pairinglabel'                  => $bitPayPaymentLabel, // Your Pairing Label
                'callbackUrl'                   => 'payment-response.php', //callback Url after payment successful
                'privateItems'                  => ['pairingCode', 'pairinglabel', 'password']
            ],
            'mercadopago' => [
                'enable'                        => $mercadoPagoPaymentStatus === 1 ? true : false,
                'testMode'                      => ($mercadoPagoMode != 1 ? true : false), //test mode or product mode (boolean, true or false)
                'gateway'                       => 'Mercado Pago', //payment gateway name
                'currency'                      => $mercadoPagoCurrency, //currency
                'currencySymbol'                => __pc_currency_symbol($mercadoPagoCurrency), //currency Symbol
                'testAccessToken'               => $mercadoPagoTestAccessTokenID,
                'liveAccessToken'               => $mercadoPagoLiveAccessTokenID,
                'callbackUrl'                   => 'payment-response.php', //callback Url after payment successful
                'privateItems'                  => ['testAccessToken', 'liveAccessToken']
            ],
            'moneroo' => [
                'enable'                        => ((int) $monerooPaymentStatus === 1),
                'testMode'                      => ((int) $monerooMode !== 1),
                'gateway'                       => 'Moneroo',
                'currency'                      => $monerooCurrency,
                'currencySymbol'                => __pc_currency_symbol($monerooCurrency, $monerooCurrency),
                'testProjectId'                 => $monerooTestProjectId,
                'testApiKey'                    => $monerooTestApiKey,
                'liveProjectId'                 => $monerooLiveProjectId,
                'liveApiKey'                    => $monerooLiveApiKey,
                'webhookSecret'                 => $monerooWebhookSecret,
                'apiBaseUrl'                    => 'https://api.moneroo.io',
                'callbackUrl'                   => 'payment-response.php',
                'privateItems'                  => ['testApiKey', 'liveApiKey', 'webhookSecret']
            ],
            'paysafecard' => [
                'enable'                        => ((int) $paysafecardPaymentStatus === 1),
                'testMode'                      => ($paysafecardMode !== 'live'),
                'gateway'                       => 'Paysafecard',
                'currency'                      => $paysafecardCurrency,
                'currencySymbol'                => __pc_currency_symbol($paysafecardCurrency, $paysafecardCurrency),
                'apiKey'                        => $paysafecardApiKey,
                'mode'                          => $paysafecardMode,
                'sandboxApiBaseUrl'             => 'https://apitest.paysafecard.com',
                'productionApiBaseUrl'          => 'https://api.paysafecard.com',
                'callbackUrl'                   => 'payment-response.php',
                'successUrl'                    => 'payment-response.php',
                'cancelUrl'                     => 'payment-response.php',
                'privateItems'                  => ['apiKey']
            ],
            'ccbill' => [
                'enable'                        => true,
                'gateway'                       => 'CCBill',
                'accountNumber'                 => $ccbill_AccountNumber,
                'subAccountNumber'              => $ccbill_SubAccountNumber,
                'flexFormId'                    => $ccbill_FlexID,
                'saltKey'                       => $ccbill_SaltKey,
                'currency'                      => $ccbill_Currency,
                'cancelUsername'                => $ccbill_CancelUsername,
                'cancelPassword'                => $ccbill_CancelPassword,
                'apiBaseUrl'                    => 'https://api.ccbill.com/wap-frontflex/flexforms',
                'managementUrl'                 => 'https://datalink.ccbill.com/utils/subscriptionManagement.cgi',
                'callbackUrl'                   => 'payment-response.php',
                'successUrl'                    => 'payment-response.php',
                'cancelUrl'                     => 'payment-response.php',
                'privateItems'                  => ['saltKey']
            ],
            'nowpayments' => [
                'enable'                        => ((int) $nowPaymentsPaymentStatus === 1),
                'testMode'                      => ((int) $nowPaymentsMode !== 1),
                'gateway'                       => 'NowPayments',
                'currency'                      => $nowPaymentsCurrency,
                'currencySymbol'                => __pc_currency_symbol($nowPaymentsCurrency, $nowPaymentsCurrency),
                'testApiKey'                    => $nowPaymentsTestApiKey,
                'liveApiKey'                    => $nowPaymentsLiveApiKey,
                'ipnSecret'                     => $nowPaymentsIpnSecret,
                'sandboxApiBaseUrl'             => 'https://api-sandbox.nowpayments.io',
                'productionApiBaseUrl'          => 'https://api.nowpayments.io',
                'callbackUrl'                   => 'payment-response.php',
                'privateItems'                  => ['testApiKey', 'liveApiKey', 'ipnSecret']
            ],
            'yookassa' => [
                'enable'                        => ((int) $yookassaPaymentStatus === 1),
                'testMode'                      => ((int) $yookassaPaymentMode !== 1),
                'gateway'                       => 'YooKassa',
                'currency'                      => $yookassaCurrency,
                'currencySymbol'                => __pc_currency_symbol($yookassaCurrency, $yookassaCurrency),
                'testShopId'                    => $yookassaTestShopId,
                'testSecretKey'                 => $yookassaTestSecretKey,
                'liveShopId'                    => $yookassaLiveShopId,
                'liveSecretKey'                 => $yookassaLiveSecretKey,
                'webhookSecret'                 => $yookassaWebhookSecret,
                'apiBaseUrl'                    => 'https://api.yookassa.ru',
                'callbackUrl'                   => 'payment-response.php',
                'privateItems'                  => ['testSecretKey', 'liveSecretKey', 'webhookSecret']
            ],
            'epoch' => [
                'enable'                        => ((int) $epochPaymentStatus === 1),
                'testMode'                      => ((int) $epochPaymentMode !== 1),
                'gateway'                       => 'EPOCH',
                'currency'                      => $epochCurrency,
                'currencySymbol'                => __pc_currency_symbol($epochCurrency, $epochCurrency),
                'piCode'                        => $epochPiCode,
                'testEndpoint'                  => $epochTestEndpoint,
                'liveEndpoint'                  => $epochLiveEndpoint,
                'defaultEndpoint'               => 'https://wnu.com/secure/services/',
                'postbackEnabled'               => ((int) $epochPostbackEnabled === 1),
                'postbackSecret'                => $epochPostbackSecret,
                'postbackAllowlist'             => $epochPostbackAllowlist,
                'returnMode'                    => $epochReturnMode,
                'template'                      => $epochTemplate,
                'callbackUrl'                   => 'payment-response.php',
                'successUrl'                    => 'payment-response.php',
                'cancelUrl'                     => 'payment-response.php',
                'postbackUrl'                   => 'epoch_webhook.php',
                'privateItems'                  => ['postbackSecret']
            ]
        ],
    ],

];

return compact("inoraPaymentConfig");
