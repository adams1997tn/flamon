<?php 
namespace App\Components\Payment;

class PaymentProcess
{
    /** @var object|null */
    protected $paytmService = null;
    /** @var object|null */
    protected $instamojoService = null;
    /** @var object|null */
    protected $iyzicoService = null;
    /** @var object|null */
    protected $paypalService = null;
    /** @var object|null */
    protected $paystackService = null;
    /** @var object|null */
    protected $razorpayService = null;
    /** @var object|null */
    protected $stripeService = null;
    /** @var object|null */
    protected $authorizeNetService = null;
    /** @var object|null */
    protected $bitPayService = null;
    /** @var object|null */
    protected $mollieService = null;
    /** @var object|null */
    protected $mercadopagoService = null;
    /** @var object|null */
    protected $monerooService = null;
    /** @var object|null */
    protected $ccbillService = null;
    /** @var object|null */
    protected $flutterwaveService = null;
    /** @var object|null */
    protected $nowPaymentsService = null;
    /** @var object|null */
    protected $paysafecardService = null;
    /** @var object|null */
    protected $yookassaService = null;
    /** @var object|null */
    protected $epochService = null;
 

    public function __construct(...$services)
    {
        foreach ($services as $service) {
            if (!$service) {
                continue;
            }
            if ($service instanceof \App\Service\PaytmService) {
                $this->paytmService = $service;
            } elseif ($service instanceof \App\Service\InstamojoService) {
                $this->instamojoService = $service;
            } elseif ($service instanceof \App\Service\IyzicoService) {
                $this->iyzicoService = $service;
            } elseif ($service instanceof \App\Service\PaypalService) {
                $this->paypalService = $service;
            } elseif ($service instanceof \App\Service\PaystackService) {
                $this->paystackService = $service;
            } elseif ($service instanceof \App\Service\RazorpayService) {
                $this->razorpayService = $service;
            } elseif ($service instanceof \App\Service\StripeService) {
                $this->stripeService = $service;
            } elseif ($service instanceof \App\Service\AuthorizeNetService) {
                $this->authorizeNetService = $service;
            } elseif ($service instanceof \App\Service\BitPayService) {
                $this->bitPayService = $service;
            } elseif ($service instanceof \App\Service\MollieService) {
                $this->mollieService = $service;
            } elseif ($service instanceof \App\Service\MercadopagoService) {
                $this->mercadopagoService = $service;
            } elseif ($service instanceof \App\Service\MonerooService) {
                $this->monerooService = $service;
            } elseif ($service instanceof \App\Service\CcbillService) {
                $this->ccbillService = $service;
            } elseif ($service instanceof \App\Service\FlutterwaveService) {
                $this->flutterwaveService = $service;
            } elseif ($service instanceof \App\Service\NowPaymentsService) {
                $this->nowPaymentsService = $service;
            } elseif ($service instanceof \App\Service\PaysafecardService) {
                $this->paysafecardService = $service;
            } elseif ($service instanceof \App\Service\YooKassaService) {
                $this->yookassaService = $service;
            } elseif ($service instanceof \App\Service\EpochService) {
                $this->epochService = $service;
            }
        }
    }

    public function getPaymentData($request)
    {
        $processResponse = [];
        if ($request['paymentOption'] == 'instamojo') {
            if (!$this->instamojoService) {
                return ['status' => 'error', 'message' => 'Instamojo service is not configured'];
            }
            $processResponse = $this->instamojoService->processInstamojoRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'iyzico') {
            if (!$this->iyzicoService) {
                return ['status' => 'error', 'message' => 'Iyzico service is not configured'];
            }
            $processResponse = $this->iyzicoService->processIyzicoRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'paypal') {
            if (!$this->paypalService) {
                return ['status' => 'error', 'message' => 'Paypal service is not configured'];
            }
            $processResponse = $this->paypalService->processPaypalRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'stripe') {
            if (!$this->stripeService) {
                return ['status' => 'error', 'message' => 'Stripe service is not configured'];
            }
            $processResponse = $this->stripeService->processStripeRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'paystack') {
            if (!$this->paystackService) {
                return ['status' => 'error', 'message' => 'Paystack service is not configured'];
            }
            $processResponse = $this->paystackService->processPaystackRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'razorpay') {
            if (!$this->razorpayService) {
                return ['status' => 'error', 'message' => 'Razorpay service is not configured'];
            }
            $processResponse = $this->razorpayService->processRazorpayRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'authorize-net') {
            if (!$this->authorizeNetService) {
                return ['status' => 'error', 'message' => 'Authorize.Net service is not configured'];
            }
            $processResponse = $this->authorizeNetService->processAuthorizeNetRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'bitpay') {
            if (!$this->bitPayService) {
                return ['status' => 'error', 'message' => 'BitPay service is not configured'];
            }
            $processResponse = $this->bitPayService->processBitPayRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'mercadopago') {
            if (!$this->mercadopagoService) {
                return ['status' => 'error', 'message' => 'MercadoPago service is not configured'];
            }
            $processResponse = $this->mercadopagoService->processMercadopagoRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'moneroo') {
            if (!$this->monerooService) {
                return ['status' => 'error', 'message' => 'Moneroo service is not configured'];
            }
            $processResponse = $this->monerooService->processMonerooRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'ccbill') {
            if (!$this->ccbillService) {
                return ['status' => 'error', 'message' => 'CCBill service is not configured'];
            }
            $processResponse = $this->ccbillService->processCcbillRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'flutterwave') {
            if (!$this->flutterwaveService) {
                return ['status' => 'error', 'message' => 'Flutterwave service is not configured'];
            }
            $processResponse = $this->flutterwaveService->processFlutterwaveRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'nowpayments') {
            if (!$this->nowPaymentsService) {
                return ['status' => 'error', 'message' => 'NowPayments service is not configured'];
            }
            $processResponse = $this->nowPaymentsService->processNowPaymentsRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'paysafecard') {
            if (!$this->paysafecardService) {
                return ['status' => 'error', 'message' => 'Paysafecard service is not configured'];
            }
            $processResponse = $this->paysafecardService->processPaysafecardRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'yookassa') {
            if (!$this->yookassaService) {
                return ['status' => 'error', 'message' => 'YooKassa service is not configured'];
            }
            $processResponse = $this->yookassaService->processYooKassaRequest($request);
            return $processResponse;
        } elseif ($request['paymentOption'] == 'epoch') {
            if (!$this->epochService) {
                return ['status' => 'error', 'message' => 'Epoch service is not configured'];
            }
            if (($request['payment_type'] ?? '') === 'subscription') {
                $processResponse = $this->epochService->processEpochSubscription($request);
            } else {
                $processResponse = $this->epochService->processEpochRequest($request);
            }
            return $processResponse;
        }
    }
}
