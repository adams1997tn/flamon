<?php
//include_once "../../inc.php";
// Include Header file


/*
 * Use PaymentProcess Class
 * Use PaytmService Class
 * Use InstamojoService Class
 * Use IyzicoService Class
 * Use PaypalService Class
 * Use PaystackService Class
 * Use RazorpayService Class
 * Use StripeService Class
 * Use AuthorizeNetService Class
 */
use App\Components\Payment\PaymentProcess;
use App\Service\AuthorizeNetService;
use App\Service\BitPayService;
use App\Service\IyzicoService;
use App\Service\PaypalService;
use App\Service\PaystackService;
use App\Service\PaytmService;
use App\Service\RazorpayService;
use App\Service\StripeService;
use App\Service\MercadopagoService;
use App\Service\MonerooService;
use App\Service\CcbillService;
use App\Service\FlutterwaveService;
use App\Service\NowPaymentsService;
use App\Service\PaysafecardService;
use App\Service\YooKassaService;
use App\Service\EpochService;

/*
 * Get instance of paytm service
 */
$paytmService = new PaytmService();

/*
 * Get instance of iyzico service
 */
$iyzicoService = new IyzicoService();

/*
 * Get instance of paypal service
 */
$paypalService = new PaypalService();

/*
 * Get instance of paystack service
 */
$paystackService = new PaystackService();

/*
 * Get instance of razorpay service
 */
$razorpayService = new RazorpayService();

/*
 * Get instance of stripe service
 */
$stripeService = new StripeService();

/*
 * Get instance of authorize service
 */
$authorizeNetService = new AuthorizeNetService();

/*
 * Get instance of BitPay service
 */
$bitPayService = new BitPayService();

/**
 * Get instance of Mercadopago service
 */
$mercadopagoService = new MercadopagoService();

/**
 * Get instance of Moneroo service
 */
$monerooService = new MonerooService();

/**
 * Get instance of CCBill service
 */
$ccbillService = new CcbillService();

/**
 * Get instance of Flutterwave service
 */
$flutterwaveService = new FlutterwaveService();

/**
 * Get instance of NowPayments service
 */
$nowPaymentsService = new NowPaymentsService();

/*
 * Get instance of Paysafecard service
 */
$paysafecardService = new PaysafecardService();

/*
 * Get instance of YooKassa service
 */
$yookassaService = new YooKassaService();

/*
 * Get instance of Epoch service
 */
$epochService = new EpochService();

/*
 * Process a payment with anyone service
 */
$paymentProcess = new PaymentProcess(
	$paytmService,
	$iyzicoService,
	$paypalService,
	$paystackService,
	$razorpayService,
	$stripeService,
	$authorizeNetService,
    $bitPayService,
    $mercadopagoService,
    $monerooService,
    $ccbillService,
    $flutterwaveService,
    $nowPaymentsService,
    $paysafecardService,
    $yookassaService,
    $epochService
);
/*
 * Get instance of GUMP, its a validation library for PHP
 */
$gump = new GUMP();

if (!function_exists('iN_EnsurePaymentOptionValue')) {
    /**
     * Ensure payment_option enum contains the incoming value to avoid truncation errors.
     * It preserves any existing enum options instead of replacing them.
     *
     * @param string $requiredValue
     * @return void
     */
    function iN_EnsurePaymentOptionValue(string $requiredValue): void
    {
        static $checked = [];
        $requiredValue = trim($requiredValue);
        if ($requiredValue === '') {
            return;
        }
        if (isset($checked[$requiredValue])) {
            return;
        }
        $checked[$requiredValue] = true;

        try {
            $column = DB::one("SHOW COLUMNS FROM i_user_payments LIKE 'payment_option'");
            if (!$column || !isset($column['Type'])) {
                return;
            }

            $type = (string) $column['Type'];
            if (stripos($type, 'enum(') !== 0) {
                return;
            }

            $needle = "'" . str_replace("'", "''", $requiredValue) . "'";
            if (strpos($type, $needle) !== false) {
                return;
            }

            preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
            $options = $matches[1] ?? [];
            $options[] = $requiredValue;
            $options = array_values(array_unique($options));
            $escapedOptions = array_map(static function ($opt) {
                return str_replace("'", "\\'", $opt);
            }, $options);
            $enumList = "'" . implode("','", $escapedOptions) . "'";

            DB::exec("ALTER TABLE i_user_payments MODIFY `payment_option` enum($enumList) COLLATE utf8mb4_general_ci NOT NULL");
        } catch (\Throwable $th) {
        }
    }
}

//check post data is not empty
if (isset($_POST) && count($_POST) > 0) {
	// Sanitize form input data, remove tags for security purpose
	$insertData = $gump->sanitize($_POST);

	// Apply validation rule for post request.
	$validation = GUMP::is_valid($insertData, array(
		//'amount'        => 'required|numeric|min_numeric,0',
		'paymentOption' => 'required',
	));

	$paymentOption = $insertData['paymentOption'];
	if ($paymentOption === 'paysafecard') {
		$insertData['order_id'] = isset($insertData['order_id']) && $insertData['order_id'] !== '' ? $insertData['order_id'] : ('PSC' . uniqid());
	}
	// Check if iyzico or authorize-net payment method is used then check iyzico or authorize-net form data like
	// amount, option, cardname, card number, expiry month, expiry year, cvv etc and validate it
	if ($paymentOption == 'iyzico' or $paymentOption == 'authorize-net') {
		$validation = GUMP::is_valid($insertData, array(
			//'amount'        => 'required|numeric',
			'paymentOption' => 'required',
			'cardname' => 'required',
			'cardnumber' => 'required',
			'expmonth' => 'required',
			'expyear' => 'required',
			'cvv' => 'required',
		));
	}

	// Check server side validation success then process for next step
	if ($validation === true) {
		$time = time();
		$insertData['payment_type'] = isset($insertData['payment_type']) && $insertData['payment_type'] !== '' ? $insertData['payment_type'] : 'product';
        iN_EnsurePaymentOptionValue((string) ($insertData['paymentOption'] ?? ''));
		$insertData['payer_id'] = $userID;
		$yookassaIdempotenceKey = null;
		$epochNonce = null;
		$epochSignature = null;
		if ($paymentOption === 'yookassa') {
			try {
				$yookassaIdempotenceKey = bin2hex(random_bytes(16));
			} catch (\Throwable $th) {
				$yookassaIdempotenceKey = 'yookassa_' . uniqid('', true);
			}
			$insertData['yookassa_idempotence_key'] = $yookassaIdempotenceKey;
		} elseif ($paymentOption === 'epoch') {
			try {
				$epochNonce = bin2hex(random_bytes(16));
			} catch (\Throwable $th) {
				$epochNonce = 'epoch_' . uniqid('', true);
			}
			$epochEnvMarker = (isset($epochPaymentMode) && (int) $epochPaymentMode === 1) ? 'live' : 'test';
			$epochSignSecret = isset($epochPostbackSecret) ? trim((string) $epochPostbackSecret) : '';
			if ($epochSignSecret === '') {
				$epochSignSecret = sha1((string) $insertData['order_id'] . ':' . $epochNonce);
			}
			$epochSignature = hash_hmac(
				'sha256',
				implode('|', [(string) $insertData['order_id'], $epochNonce, (string) $userID, (string) $insertData['creditPlan'], $epochEnvMarker]),
				$epochSignSecret
			);
			$insertData['epoch_nonce'] = $epochNonce;
			$insertData['epoch_signature'] = $epochSignature;
		}
    DB::exec(
        "INSERT INTO i_user_payments(payer_iuid_fk,order_key,payment_type,payment_option,payment_time, payment_status,paymet_product_id) VALUES (?,?,?,?,?,'pending',?)",
        [
            (int)$userID,
            (string)$insertData['order_id'],
            'product',
            (string)$insertData['paymentOption'],
            $time,
            (string)$insertData['creditPlan']
        ]
    );

		// Then send data to payment process service for process payment
		// This service will return payment data
		$paymentData = $paymentProcess->getPaymentData($insertData);

		if ($paymentOption === 'paysafecard' && isset($paymentData['status']) && $paymentData['status'] === 'success') {
			$gatewayPaymentId = isset($paymentData['payment_id']) ? (string)$paymentData['payment_id'] : null;
			$paysafecardCurrencyCode = isset($paymentData['currency']) ? (string)$paymentData['currency'] : (isset($paysafecardCurrency) ? $paysafecardCurrency : null);
			$updateFields = [];
			$params = [];
			if ($gatewayPaymentId) {
				$updateFields[] = "invoice_number = ?";
				$params[] = $gatewayPaymentId;
			}
			if ($paysafecardCurrencyCode) {
				$updateFields[] = "currency = ?";
				$params[] = $paysafecardCurrencyCode;
			}
			if (!empty($updateFields)) {
				$params[] = (string)$insertData['order_id'];
				$params[] = (int)$userID;
				DB::exec("UPDATE i_user_payments SET " . implode(', ', $updateFields) . " WHERE order_key = ? AND payer_iuid_fk = ?", $params);
			}
		} elseif ($paymentOption === 'paysafecard' && (!isset($paymentData['status']) || $paymentData['status'] !== 'success')) {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$insertData['order_id'], (int)$userID]);
		} elseif ($paymentOption === 'yookassa' && isset($paymentData['status']) && $paymentData['status'] === 'success') {
			$gatewayPaymentId = isset($paymentData['payment_id']) ? (string) $paymentData['payment_id'] : null;
			$idempotenceKey = isset($paymentData['idempotence_key']) ? (string) $paymentData['idempotence_key'] : $yookassaIdempotenceKey;
			$updateFields = [];
			$params = [];
			if ($gatewayPaymentId) {
				$updateFields[] = "yookassa_payment_id = ?";
				$params[] = $gatewayPaymentId;
			}
			if ($idempotenceKey) {
				$updateFields[] = "yookassa_idempotence_key = ?";
				$params[] = $idempotenceKey;
			}
			if (!empty($updateFields)) {
				$params[] = (string) $insertData['order_id'];
				$params[] = (int) $userID;
				DB::exec("UPDATE i_user_payments SET " . implode(', ', $updateFields) . " WHERE order_key = ? AND payer_iuid_fk = ?", $params);
			}
		} elseif ($paymentOption === 'yookassa' && (!isset($paymentData['status']) || $paymentData['status'] !== 'success')) {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$insertData['order_id'], (int)$userID]);
		} elseif ($paymentOption === 'epoch' && isset($paymentData['status']) && $paymentData['status'] === 'success') {
			$gatewayTransactionId = isset($paymentData['epoch_transaction_id']) ? (string) $paymentData['epoch_transaction_id'] : null;
			$epochNonceValue = isset($paymentData['epoch_nonce']) ? (string) $paymentData['epoch_nonce'] : $epochNonce;
			$epochSignatureValue = isset($paymentData['epoch_signature']) ? (string) $paymentData['epoch_signature'] : $epochSignature;
			$updateFields = [];
			$params = [];
			if ($gatewayTransactionId) {
				$updateFields[] = "epoch_transaction_id = ?";
				$params[] = $gatewayTransactionId;
			}
			if ($epochNonceValue) {
				$updateFields[] = "epoch_nonce = ?";
				$params[] = $epochNonceValue;
			}
			if ($epochSignatureValue) {
				$updateFields[] = "epoch_signature = ?";
				$params[] = $epochSignatureValue;
			}
			if (!empty($updateFields)) {
				$params[] = (string) $insertData['order_id'];
				$params[] = (int) $userID;
				try {
					DB::exec("UPDATE i_user_payments SET " . implode(', ', $updateFields) . " WHERE order_key = ? AND payer_iuid_fk = ?", $params);
				} catch (\Throwable $th) {
				}
			}
		} elseif ($paymentOption === 'epoch' && (!isset($paymentData['status']) || $paymentData['status'] !== 'success')) {
			DB::exec("DELETE FROM i_user_payments WHERE order_key = ? AND payer_iuid_fk = ?", [(string)$insertData['order_id'], (int)$userID]);
		}

		// set select payment option in return paymentData array
		$paymentData['paymentOption'] = $paymentOption;

		//on success paytm response
		if ($paymentOption == 'paytm') {

			// If paytm payment method are selected then get payment merchant form
			$paymentData['merchantForm'] = getPaytmMerchantForm($paymentData);

			// return payment array on ajax request
			echo json_encode($paymentData);

			// on success instamojo, paystack, stripe, razorpay, iyzico & paypal response
			//} else if () {

		} elseif (in_array($paymentOption, ['paystack','iyzico','paypal','stripe','authorize-net','bitpay','mercadopago','moneroo','nowpayments','paysafecard','yookassa','epoch'], true)) {

			// return payment array on ajax request
			echo json_encode($paymentData);

		} elseif ($paymentOption == 'razorpay') {
			echo json_encode(array_values($paymentData)[0]);
		}

	} else {
		// If Validation errors occurred then show it on the form
		$validationMessage = [];

		// get collection of validation messages
		foreach ($validation as $valid) {
			$validationMessage['validationMessage'][] = strip_tags($valid);
		}

		// return validation array on ajax request
		echo json_encode($validationMessage);

		exit();
	}
}
