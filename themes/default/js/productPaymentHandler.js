(function ($) {
  "use strict";
 
  const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
  const loaderWrapper = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
  const buildHostedFormAndSubmit = function (response) {
    if (!response || response.status !== "success" || !response.post_url || !response.form_fields) {
      return false;
    }
    const form = document.createElement("form");
    form.method = "post";
    form.action = response.post_url;
    form.style.display = "none";
    const fields = response.form_fields;
    Object.keys(fields).forEach(function (key) {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = key;
      input.value = fields[key] == null ? "" : String(fields[key]);
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    return true;
  };

  $(document).ready(function () {
 
    $("body").on("click", ".paywith", function () {
      const type = $(this).data("type");
      if (type === "iyzico" || type === "authorize-net") {
        $(".point_purchase").attr("data-type", type);
      }
      setTimeout(() => {
        $(".i_moda_bg_in_form").addClass("i_modal_display_in");
      }, 200);
    });
 
    $("body").on("click", ".payClose", function () {
      $(".i_moda_bg_in_form").removeClass("i_modal_display_in");
    });
 
    $("body").on("click", ".payMethod", function (e) {
      e.preventDefault();
      const payWidth = $(this).data("type");
      const planID = window.productID;
      const type = "processProduct";
      const configData = window.configData;
      const configItem = configData.payments.gateway_configuration[payWidth] || {};
      const userDetails = window.userData;

      $(`#${payWidth}`).append(loaderWrapper);
      $(".payment_method_box").css("pointer-events", "none");
 
      if (["paypal", "iyzico", "authorize-net", "bitpay", "mercadopago", "moneroo", "yookassa", "konnect", "epoch", "ccbill", "nowpayments"].includes(payWidth)) {
        const requestData = `f=${type}&paymentOption=${payWidth}&creditPlan=${planID}&` + $("#paymentFrm").serialize() + "&" + $.param(userDetails);

        $.ajax({
          type: "POST",
          url: window.siteurl + "requests/request.php",
          dataType: "JSON",
          data: requestData,
          success: function (response) {
            $(".payment_method_box").css("pointer-events", "auto");
            $(".loaderWrapper").remove();

            if (response.validationMessage) {
              $.each(response.validationMessage, function (_, message) { 
              });
            }

            if (payWidth === "paypal") {
              $(".lw-show-till-loading").show();
              window.location.href = response.paypalUrl;
            } else if (payWidth === "bitpay") {
              if (response.status === "success") {
                window.location.href = response.invoiceUrl;
              }
            } else if (payWidth === "iyzico") {
              if (response.status === "success") {
                $("body").html(response.htmlContent);
              }
            } else if (payWidth === "authorize-net") {
              if (response.status === "success") {
                $("body").html(`<form action='${window.authorizeNetCallbackUrl}' method='post'><input type='hidden' name='response' value='${JSON.stringify(response)}'><input type='hidden' name='paymentOption' value='authorize-net'></form>`);
                $("body form").submit();
              }
            } else if (payWidth === "mercadopago") {
              if (response.status === "success") {
                window.location.href = response.redirect_url;
              } else {
                $(".lw-show-till-loading").hide(); 
              }
            } else if (payWidth === "moneroo") {
              if (response.status === "success") {
                window.location.href = response.redirect_url;
              } else {
                $(".lw-show-till-loading").hide();
              }
            } else if (payWidth === "yookassa") {
              if (response.status === "success" && response.confirmation_url) {
                window.location.href = response.confirmation_url;
              } else {
                $(".lw-show-till-loading").hide();
              }
            } else if (payWidth === "konnect") {
              if (response.status === "success" && response.redirect_url) {
                window.location.href = response.redirect_url;
              } else {
                $(".lw-show-till-loading").hide();
                var __knMsg = (response && response.message)
                  ? response.message
                  : "Konnect payment is not available. Please verify the gateway configuration.";
                console.error("[Konnect]", response);
                if (typeof window !== "undefined") { window.alert(__knMsg); }
              }
            } else if (payWidth === "epoch") {
              if (!buildHostedFormAndSubmit(response)) {
                $(".lw-show-till-loading").hide();
              }
            } else if (payWidth === "ccbill") {
              if (response.status === "success") {
                window.location.href = response.redirect_url;
              } else {
                $(".lw-show-till-loading").hide();
              }
            } else if (payWidth === "nowpayments") {
              if (response.status === "success") {
                window.location.href = response.redirect_url;
              } else {
                $(".lw-show-till-loading").hide();
              }
            }
          }
        });
 
      } else if (payWidth === "stripe") {
        $(".payment_method_box").css("pointer-events", "auto");
        $(".loaderWrapper").remove();

        const isTestMode = configItem.testMode === true || configItem.testMode === 'true' || configItem.testMode === 1 || configItem.testMode === '1';
        const stripeKey = isTestMode ? window.stripeTestKey : window.stripeLiveKey;

        if (!stripeKey) {
          console.error("[Stripe] Missing publishable key for %s mode.", isTestMode ? "test" : "live");
          return;
        }

        const stripe = Stripe(stripeKey);
        userDetails.paymentOption = payWidth;
        userDetails.f = type;
        userDetails.creditPlan = planID;

        $.ajax({
          type: "POST",
          url: window.siteurl + "requests/request.php",
          dataType: "JSON",
          data: userDetails,
          success: function (response) {
            stripe.redirectToCheckout({ sessionId: response.id })
              .then(function (result) {
                if (result && result.error) {
                  console.error("[Stripe] Redirect warning:", result.error.message);
                }
              })
              .catch(function (error) {
                console.error("Stripe redirect error:", error);
             });
          }
        });
 
      } else if (payWidth === "paystack") {
        $(".payment_method_box").css("pointer-events", "auto");
        $(".loaderWrapper").remove();

        const amount = userDetails.amounts[configItem.currency];
        const paystackPublicKey = configItem.testMode ? configItem.paystackTestingPublicKey : configItem.paystackLivePublicKey;
        const paystackAmount = amount * 100;

        userDetails.paymentOption = payWidth;
        userDetails.f = type;
        userDetails.creditPlan = planID;

        const handler = PaystackPop.setup({
          key: paystackPublicKey,
          email: userDetails.payer_email,
          amount: paystackAmount,
          currency: configItem.currency,
          callback: function (response) {
            $(".lw-show-till-loading").show();

            const paystackData = {
              paystackReferenceId: response.reference,
              paystackAmount: paystackAmount
            };

            const requestData = $('#lwPaymentForm').serialize() + '&' + $.param(userDetails) + '&' + $.param(paystackData);

            $.ajax({
              type: "POST",
              url: window.siteurl + "requests/request.php",
              dataType: "JSON",
              data: requestData,
              success: function (response) {
                if (response.status === true) {
                  const callbackUrl = configItem.callbackUrl + '?orderId=' + userDetails.order_id + '&paymentOption=' + payWidth;
                  $("body").html(`<form action='${callbackUrl}' method='post'><input type='hidden' name='response' value='${JSON.stringify(response)}'><input type='hidden' name='paymentOption' value='paystack'></form>`);
                  $("body form").submit();
                }
              }
            });
          },
          onClose: function () {
            window.location.href = window.paymentPagePath;
          }
        });

        handler.openIframe();
 
      } else if (payWidth === "razorpay") {
        $(".payment_method_box").css("pointer-events", "auto");
        $(".loaderWrapper").remove();

        const amount = userDetails.amounts[configItem.currency];
        const razorpayAmount = amount * 100;
        const razorpayKeyId = configItem.testMode ? configItem.razorpayTestingkeyId : configItem.razorpayLivekeyId;

        userDetails.paymentOption = payWidth;
        userDetails.f = type;
        userDetails.creditPlan = planID;

        const options = {
          key: razorpayKeyId,
          amount: razorpayAmount,
          currency: configItem.currency,
          name: configItem.merchantname,
          handler: function (response) {
            const razorpayData = {
              razorpayPaymentId: response.razorpay_payment_id,
              razorpayAmount: window.btoa(razorpayAmount)
            };

            const requestData = $('#lwPaymentForm').serialize() + '&' + $.param(userDetails) + '&' + $.param(razorpayData);

            $.ajax({
              type: "POST",
              url: window.siteurl + "requests/request.php",
              dataType: "JSON",
              data: requestData,
              success: function (response) {
                if (response.status === "captured") {
                  const callbackUrl = configItem.callbackUrl + '?orderId=' + userDetails.order_id + '&paymentOption=' + payWidth;
                  $("body").html(`<form action='${callbackUrl}' method='post'><input type='hidden' name='response' value='${JSON.stringify(response)}'><input type='hidden' name='paymentOption' value='razorpay'></form>`);
                  $("body form").submit();
                }
              }
            });
          },
          prefill: {
            name: userDetails.payer_name,
            email: userDetails.payer_email
          },
          theme: {
            color: configItem.themeColor
          },
          modal: {
            ondismiss: function () {
              window.location.href = window.paymentPagePath;
            }
          }
        };

        const rzp1 = new Razorpay(options);
        rzp1.open();
      } else if (payWidth === "flutterwave") {
        $(".payment_method_box").css("pointer-events", "auto");
        $(".loaderWrapper").remove();

        if (typeof FlutterwaveCheckout !== "function") {
          console.error("[Flutterwave] Checkout script is not loaded.");
          return;
        }

        const isTestMode = configItem.testMode === true || configItem.testMode === 'true' || configItem.testMode === 1 || configItem.testMode === '1';
        const publicKey = isTestMode ? configItem.testPublicKey : configItem.livePublicKey;
        const currency = configItem.currency;
        const amount = userDetails.amounts[currency];

        if (!publicKey || !amount) {
          console.error("[Flutterwave] Missing publishable key or amount.");
          return;
        }

        userDetails.paymentOption = payWidth;
        userDetails.f = type;
        userDetails.creditPlan = planID;

        FlutterwaveCheckout({
          public_key: publicKey,
          tx_ref: userDetails.order_id,
          amount: amount,
          currency: currency,
          customer: {
            email: userDetails.payer_email,
            name: userDetails.payer_name
          },
          callback: function (response) {
            if (response && response.status === 'successful') {
              $(".lw-show-till-loading").show();

              const flutterwaveData = {
                flutterwaveTxRef: response.tx_ref,
                flutterwaveTransactionId: response.transaction_id,
                flutterwaveAmount: amount
              };

              const requestData = $('#lwPaymentForm').serialize() + '&' + $.param(userDetails) + '&' + $.param(flutterwaveData);

              $.ajax({
                type: "POST",
                url: window.siteurl + "requests/request.php",
                dataType: "JSON",
                data: requestData,
                success: function (serverResponse) {
                  if (serverResponse.status === true) {
                    const callbackUrl = configItem.callbackUrl + '?orderId=' + userDetails.order_id + '&paymentOption=' + payWidth;
                    $("body").html(
                      `<form action='${callbackUrl}' method='post'>
                        <input type='hidden' name='response' value='${JSON.stringify(serverResponse)}'>
                        <input type='hidden' name='paymentOption' value='${payWidth}'>
                      </form>`
                    );
                    $("body form").submit();
                  } else {
                    $(".lw-show-till-loading").hide();
                  }
                },
                error: function () {
                  $(".lw-show-till-loading").hide();
                }
              });
            }
          },
          onclose: function () {
            window.location.href = window.paymentPagePath;
          }
        });
      }
    });
 
    $("body").on("click", ".paywithCrip", function () {
      const planID = window.productID;
      const payWidth = $(this).data("type");
      const data = `f=cop&p=${planID}`;

      $.ajax({
        type: "POST",
        url: window.siteurl + "requests/request.php",
        dataType: "JSON",
        data: data,
        beforeSend: function () {
          $(`#${payWidth}`).append(loaderWrapper);
          $(".payment_method_box").css("pointer-events", "none");
        },
        success: function (response) {
          const redirect = response.redirect;
          const status = response.status;
          if (redirect && status === '200') {
            window.location.href = redirect;
          } 
        }
      });
    });

  });
})(jQuery);
