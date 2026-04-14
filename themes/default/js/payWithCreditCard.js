
(function ($) {
  "use strict";

  function decodeBase64Unicode(value) {
    if (!value) {
      return "";
    }
    try {
      return decodeURIComponent(atob(value).split("").map(function (c) {
        return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
      }).join(""));
    } catch (err) {
      try {
        return atob(value);
      } catch (err2) {
        return "";
      }
    }
  }

  function readPayConfigFromModal() {
    const modal = document.querySelector(".community_pay_modal[data-pay-config]") || document.querySelector("[data-pay-config]");
    if (!modal) {
      return null;
    }
    const encoded = modal.getAttribute("data-pay-config");
    if (!encoded) {
      return null;
    }
    const decoded = decodeBase64Unicode(encoded);
    if (!decoded) {
      return null;
    }
    try {
      return JSON.parse(decoded);
    } catch (err) {
      return null;
    }
  }

  let config = window.payWithCardData || {};
  if (!config || Object.keys(config).length === 0) {
    const modalConfig = readPayConfigFromModal();
    if (modalConfig) {
      config = modalConfig;
    }
  }
  const siteurl = config.siteurl || "";
  const scope = config.scope || "profile";
  const requestConfig = config.request || {};
  const stripeRequest = requestConfig.stripe || "subscribeMe";
  const ccbillRequest = requestConfig.ccbill || "subscribeWithCcbill";
  const flutterwaveRequest = requestConfig.flutterwave || "subscribeWithFlutterwave";
  let iyzicoRequest = requestConfig.iyzico || "";
  if (!iyzicoRequest) {
    if (scope === "community") {
      iyzicoRequest = "communitySubscribeWithIyzico";
    } else if (scope === "community_plan") {
      iyzicoRequest = "communityPlanSubscribeWithIyzico";
    } else if (scope === "profile") {
      iyzicoRequest = "subscribeWithIyzico";
    }
  }
  let yookassaRequest = requestConfig.yookassa || "";
  if (!yookassaRequest) {
    if (scope === "community") {
      yookassaRequest = "communitySubscribeWithYookassa";
    } else if (scope === "profile") {
      yookassaRequest = "subscribeWithYookassa";
    }
  }
  let epochRequest = requestConfig.epoch || "";
  if (!epochRequest) {
    if (scope === "community") {
      epochRequest = "communitySubscribeWithEpoch";
    } else if (scope === "community_plan") {
      epochRequest = "communityPlanSubscribeWithEpoch";
    } else if (scope === "profile") {
      epochRequest = "subscribeWithEpoch";
    }
  }
  const planID = config.planID || "";
  const creatorID = config.userID || "";
  const communityID = config.communityID || "";
  const theme = config.lightDark || "light";
  const stripeConfig = config.stripe || {};
  const ccbillConfig = config.ccbill || {};
  const stripeEnabled = Boolean(stripeConfig.enabled && stripeConfig.publicKey);
  const ccbillEnabled = Boolean(ccbillConfig.enabled);
  const flutterwaveConfig = config.flutterwave || {};
  const flutterwaveEnabled = Boolean(flutterwaveConfig.enabled && (flutterwaveConfig.testPublicKey || flutterwaveConfig.livePublicKey));
  const iyzicoConfig = config.iyzico || {};
  let iyzicoEnabled = Boolean(iyzicoConfig.enabled);
  if (!iyzicoEnabled) {
    iyzicoEnabled = document.querySelector('.pay_gateway_section[data-provider="iyzico"]') !== null;
  }
  const epochConfig = config.epoch || {};
  let epochEnabled = Boolean(epochConfig.enabled);
  if (!epochEnabled) {
    epochEnabled = document.querySelector('.pay_gateway_section[data-provider="epoch"]') !== null;
  }
  const yookassaConfig = config.yookassa || {};
  let yookassaEnabled = Boolean(yookassaConfig.enabled);
  if (!yookassaEnabled) {
    yookassaEnabled = document.querySelector('.pay_gateway_section[data-provider="yookassa"]') !== null;
  }
  if (scope === "community_plan") {
    yookassaEnabled = false;
  }
  const fallbackGateway = stripeEnabled
    ? "stripe"
    : (ccbillEnabled ? "ccbill" : (flutterwaveEnabled ? "flutterwave" : (iyzicoEnabled ? "iyzico" : (epochEnabled ? "epoch" : (yookassaEnabled ? "yookassa" : "stripe")))));
  const availableProviders = Array.from(document.querySelectorAll('.pay_gateway_section'))
    .map((section) => section.getAttribute('data-provider'))
    .filter(Boolean);
  let defaultGateway = config.defaultGateway || fallbackGateway;
  if (!availableProviders.includes(defaultGateway) && availableProviders.length) {
    defaultGateway = availableProviders[0];
  }
  const csrfToken = config.csrfToken || "";

  const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
  const loaderHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';

  let stripe = null;
  let cardElement = null;
  let expElement = null;
  let cvcElement = null;
  let resultContainer = null;
  let cardComplete = false;
  let expComplete = false;
  let cvcComplete = false;
  let stripeMounted = false;

  function setError(message) {
    if (resultContainer) {
      if (!message) {
        resultContainer.innerHTML = "";
        resultContainer.style.display = "none";
      } else {
        resultContainer.innerHTML = '<p>' + message + '</p>';
        resultContainer.style.display = "block";
      }
    } else if (message) {
      alert(message);
    }
  }

  function validateEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value || "");
  }

  function parseIyzicoExpiry(rawValue) {
    const normalized = String(rawValue || "").replace(/\s+/g, "");
    if (!normalized) {
      return { month: "", year: "" };
    }
    if (normalized.indexOf("/") > -1) {
      const parts = normalized.split("/");
      return {
        month: (parts[0] || "").replace(/\D+/g, ""),
        year: (parts[1] || "").replace(/\D+/g, "")
      };
    }
    const digits = normalized.replace(/\D+/g, "");
    if (digits.length >= 4) {
      return {
        month: digits.slice(0, 2),
        year: digits.slice(2, 4)
      };
    }
    return { month: "", year: "" };
  }

  function getIyzicoCardData() {
    const section = document.querySelector('.pay_gateway_section[data-provider="iyzico"]');
    if (!section) {
      return null;
    }
    const holder = section.querySelector('.iyzico_card_name')?.value?.trim() || "";
    const cardNumber = (section.querySelector('.iyzico_card_number')?.value || "").replace(/\D+/g, "");
    const expiryRaw = section.querySelector('.iyzico_card_expiry')?.value || "";
    const cvc = (section.querySelector('.iyzico_card_cvc')?.value || "").replace(/\D+/g, "");
    const expiry = parseIyzicoExpiry(expiryRaw);
    return {
      cardname: holder,
      cardnumber: cardNumber,
      expmonth: expiry.month,
      expyear: expiry.year,
      cvv: cvc
    };
  }

  function updatePayButtonState() {
    if (!stripeEnabled) {
      return;
    }
    const btn = document.querySelector('.pay_subscription');
    if (!btn) {
      return;
    }
    const name = document.getElementById('name')?.value?.trim();
    const email = document.getElementById('email')?.value?.trim();
    const valid = Boolean(
      name &&
      name.length >= 2 &&
      validateEmail(email) &&
      cardComplete &&
      expComplete &&
      cvcComplete
    );
    btn.disabled = !valid;
    btn.classList.toggle('disabled', !valid);
  }

  function toggleGateway(target) {
    if (!target) {
      return;
    }
    document.querySelectorAll('.pay_provider_toggle').forEach((button) => {
      const isActive = button.getAttribute('data-target') === target;
      button.classList.toggle('active', isActive);
    });
    document.querySelectorAll('.pay_gateway_section').forEach((section) => {
      const provider = section.getAttribute('data-provider');
      const desiredDisplay = section.dataset.display || '';
      if (provider === target) {
        section.removeAttribute('hidden');
        section.removeAttribute('aria-hidden');
        if (desiredDisplay) {
          section.style.display = desiredDisplay;
        } else {
          section.style.removeProperty('display');
        }
      } else {
        section.setAttribute('hidden', '');
        section.setAttribute('aria-hidden', 'true');
        section.style.display = 'none';
      }
    });
    if (target === 'stripe') {
      updatePayButtonState();
    }
  }

  function ensureStripeLoaded() {
    return new Promise((resolve, reject) => {
      if (typeof Stripe === "function") {
        resolve();
        return;
      }
      const existing = document.getElementById("stripe-js-v3");
      if (existing) {
        existing.addEventListener("load", resolve, { once: true });
        existing.addEventListener("error", reject, { once: true });
      }
      const script = document.createElement("script");
      script.id = "stripe-js-v3";
      script.src = "https://js.stripe.com/v3/";
      script.async = true;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  function initStripeElements() {
    if (stripeMounted) {
      return true;
    }
    if (!stripeEnabled || typeof Stripe !== "function") {
      return false;
    }
    if (!document.getElementById("card_number")) {
      return false;
    }
    stripe = Stripe(stripeConfig.publicKey);
    const elements = stripe.elements();
    const style = theme === "dark" ? { base: { color: "#ffffff" } } : {};
    cardElement = elements.create("cardNumber", { style });
    expElement = elements.create("cardExpiry", { style });
    cvcElement = elements.create("cardCvc", { style });

    cardElement.mount("#card_number");
    expElement.mount("#card_expiry");
    cvcElement.mount("#card_cvc");

    resultContainer = document.getElementById("paymentResponse");

    cardElement.on("change", function (event) {
      cardComplete = !!event.complete;
      setError(event.error ? event.error.message : "");
      updatePayButtonState();
    });

    expElement.on("change", function (event) {
      expComplete = !!event.complete;
      setError(event.error ? event.error.message : "");
      updatePayButtonState();
    });

    cvcElement.on("change", function (event) {
      cvcComplete = !!event.complete;
      setError(event.error ? event.error.message : "");
      updatePayButtonState();
    });
    stripeMounted = true;
    return true;
  }

  function retryStripeInit(attempt) {
    const initialized = initStripeElements();
    if (initialized) {
      return;
    }
    const tries = typeof attempt === "number" ? attempt : 0;
    if (tries < 10) {
      setTimeout(() => { retryStripeInit(tries + 1); }, 200);
    }
  }

  if (stripeEnabled) {
    retryStripeInit(0);
    ensureStripeLoaded()
      .then(() => { retryStripeInit(0); })
      .catch(() => { setError("Stripe failed to load."); });
  } else {
    resultContainer = document.getElementById("paymentResponse");
  }

  async function createToken() {
    if (!stripeEnabled || !stripe || !cardElement) {
      return null;
    }
    const { token, error } = await stripe.createToken(cardElement, {
      name: document.getElementById('name')?.value?.trim(),
    });
    if (error) {
      setError(error.message);
      return null;
    }
    return token;
  }

  function stripeTokenHandler(token) {
    $("#stripeTokenID").remove();
    const hiddenInput = document.createElement("input");
    hiddenInput.setAttribute("type", "hidden");
    hiddenInput.setAttribute("name", "stripeToken");
    hiddenInput.setAttribute("id", "stripeTokenID");
    hiddenInput.setAttribute("value", token.id);
    const form = document.getElementById("paymentFrm");
    if (form) {
      form.appendChild(hiddenInput);
    }
  }

  function cleanupLoader(btn, originalLabel) {
    $(".loaderWrapper").remove();
    if (btn) {
      btn.disabled = false;
      if (originalLabel) {
        btn.textContent = originalLabel;
      }
    }
  }

  function submitHostedPayment(response) {
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
  }

  function parseJsonResponse(response) {
    if (response && typeof response === "object") {
      return response;
    }
    if (typeof response !== "string") {
      return null;
    }
    const raw = response.trim();
    if (!raw) {
      return null;
    }
    if ((raw.charAt(0) === "{" && raw.charAt(raw.length - 1) === "}") || (raw.charAt(0) === "[" && raw.charAt(raw.length - 1) === "]")) {
      try {
        return JSON.parse(raw);
      } catch (err) {
        return null;
      }
    }
    return null;
  }

  function buildSubscriptionSuccessUrl(orderId) {
    const base = String(siteurl || "");
    const root = base.endsWith("/") ? base : (base + "/");
    const params = new URLSearchParams();
    params.set("payment_type", "subscription");
    if (orderId) {
      params.set("order_id", orderId);
    }
    return root + "payment-success?" + params.toString();
  }

  $("body").on("click", ".pay_subscription", async function () {
    if (!stripeEnabled) {
      return;
    }
    const btn = this;
    if (btn.disabled) {
      return;
    }
    setError("");

    const name = $("#name").val()?.trim();
    const email = $("#email").val()?.trim();
    if (!name || name.length < 2) {
      return setError("Please enter the card holder name.");
    }
    if (!validateEmail(email)) {
      return setError("Please enter a valid email address.");
    }
    if (!(cardComplete && expComplete && cvcComplete)) {
      return setError("Please complete your card details.");
    }
    if (scope === "community" && !communityID) {
      return setError("Missing subscription details.");
    }
    if (scope === "profile" && (!creatorID || !planID)) {
      return setError("Missing subscription details.");
    }

    const originalLabel = btn.textContent;
    const processingLabel = btn.dataset.labelProcessing || "Processing...";
    btn.disabled = true;
    btn.textContent = processingLabel;
    $(".i_modal_in_in").append(loaderHTML);

    try {
      const token = await createToken();
      if (!token) {
        throw new Error("Token creation failed");
      }
      stripeTokenHandler(token);
      const tokenId = $("#stripeTokenID").val();
      const params = new URLSearchParams();
      params.set("f", stripeRequest);
      if (scope === "community") {
        params.set("community_id", communityID);
      } else if (scope === "profile") {
        params.set("u", creatorID);
        params.set("pl", planID);
      }
      params.set("name", name);
      params.set("email", email);
      params.set("t", tokenId);
      if (csrfToken) {
        params.set("csrf_token", csrfToken);
      }
      const payload = params.toString();

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: payload,
        cache: false,
        success: function (response) {
          const parsed = parseJsonResponse(response);
          if (parsed && parsed.status === "success") {
            const redirectUrl = parsed.redirect || parsed.redirect_url || buildSubscriptionSuccessUrl(parsed.order_id || parsed.orderId || "");
            window.location.href = redirectUrl;
            return;
          }
          if (String(response).trim() === "200") {
            if (scope === "community" || scope === "community_plan") {
              window.location.href = buildSubscriptionSuccessUrl("");
              return;
            }
            location.reload();
          } else {
            setError(response || "Payment failed.");
            $(".loaderWrapper").remove();
            btn.disabled = false;
            btn.textContent = originalLabel;
          }
        },
        error: function () {
          setError("Network error. Please try again.");
          $(".loaderWrapper").remove();
          btn.disabled = false;
          btn.textContent = originalLabel;
        }
      });
    } catch (error) {
      setError(error?.message || "Unexpected error.");
      $(".loaderWrapper").remove();
      btn.disabled = false;
      btn.textContent = originalLabel;
    }
  });

  $("body").on("click", ".pay_subscription_ccbill", function () {
    if (!ccbillEnabled) {
      return;
    }
    const btn = this;
    if (btn.disabled) {
      return;
    }
    setError("");

    const plan = btn.getAttribute('data-plan') || planID;
    const creator = btn.getAttribute('data-creator') || creatorID;
    const communityFromBtn = btn.getAttribute('data-community') || communityID;
    if (scope === "community" && !communityFromBtn) {
      setError("Missing subscription details.");
      return;
    }
    if (scope === "profile" && (!plan || !creator)) {
      setError("Missing subscription details.");
      return;
    }

    btn.disabled = true;
    $(".i_modal_in_in").append(loaderHTML);

    const requestData = { f: ccbillRequest };
    if (scope === "community") {
      requestData.community_id = communityFromBtn;
    } else if (scope === "profile") {
      requestData.u = creator;
      requestData.pl = plan;
    }
    if (csrfToken) {
      requestData.csrf_token = csrfToken;
    }

    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      dataType: "json",
      data: requestData,
      success: function (response) {
        $(".loaderWrapper").remove();
        if (response && response.status === "success" && response.redirect_url) {
          window.location.href = response.redirect_url;
        } else {
          const message = response && response.message ? response.message : "Unable to start CCBill checkout.";
          setError(message);
          btn.disabled = false;
        }
      },
      error: function () {
        $(".loaderWrapper").remove();
        setError("Network error. Please try again.");
        btn.disabled = false;
      }
    });
  });

  $("body").on("click", ".pay_subscription_yookassa", function () {
    if (!yookassaEnabled || !yookassaRequest) {
      return;
    }
    const btn = this;
    if (btn.disabled) {
      return;
    }
    setError("");

    const plan = btn.getAttribute('data-plan') || planID;
    const creator = btn.getAttribute('data-creator') || creatorID;
    const communityFromBtn = btn.getAttribute('data-community') || communityID;
    if (scope === "community" && !communityFromBtn) {
      setError("Missing subscription details.");
      return;
    }
    if (scope === "profile" && (!plan || !creator)) {
      setError("Missing subscription details.");
      return;
    }

    btn.disabled = true;
    $(".i_modal_in_in").append(loaderHTML);

    const requestData = { f: yookassaRequest };
    if (scope === "community") {
      requestData.community_id = communityFromBtn;
    } else if (scope === "profile") {
      requestData.u = creator;
      requestData.pl = plan;
    }
    if (csrfToken) {
      requestData.csrf_token = csrfToken;
    }

    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      dataType: "json",
      data: requestData,
      success: function (response) {
        $(".loaderWrapper").remove();
        if (response && response.status === "success" && response.confirmation_url) {
          window.location.href = response.confirmation_url;
        } else {
          const message = response && response.message ? response.message : "Unable to start YooKassa checkout.";
          setError(message);
          btn.disabled = false;
        }
      },
      error: function () {
        $(".loaderWrapper").remove();
        setError("Network error. Please try again.");
        btn.disabled = false;
      }
    });
  });

  $("body").on("click", ".pay_subscription_epoch", function () {
    if (!epochEnabled || !epochRequest) {
      return;
    }
    const btn = this;
    if (btn.disabled) {
      return;
    }
    setError("");

    const plan = btn.getAttribute('data-plan') || planID;
    const creator = btn.getAttribute('data-creator') || creatorID;
    const communityFromBtn = btn.getAttribute('data-community') || communityID;
    if (scope === "community" && !communityFromBtn) {
      setError("Missing subscription details.");
      return;
    }
    if (scope === "profile" && (!plan || !creator)) {
      setError("Missing subscription details.");
      return;
    }

    btn.disabled = true;
    $(".i_modal_in_in").append(loaderHTML);

    const requestData = { f: epochRequest };
    if (scope === "community") {
      requestData.community_id = communityFromBtn;
    } else if (scope === "profile") {
      requestData.u = creator;
      requestData.pl = plan;
    }
    if (csrfToken) {
      requestData.csrf_token = csrfToken;
    }

    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      dataType: "json",
      data: requestData,
      success: function (response) {
        $(".loaderWrapper").remove();
        if (submitHostedPayment(response)) {
          return;
        }
        const message = response && response.message ? response.message : "Unable to start EPOCH checkout.";
        setError(message);
        btn.disabled = false;
      },
      error: function () {
        $(".loaderWrapper").remove();
        setError("Network error. Please try again.");
        btn.disabled = false;
      }
    });
  });

  $("body").on("click", ".pay_subscription_iyzico", function () {
    if (!iyzicoEnabled || !iyzicoRequest) {
      return;
    }
    const btn = this;
    if (btn.disabled) {
      return;
    }
    setError("");

    const plan = btn.getAttribute('data-plan') || planID;
    const creator = btn.getAttribute('data-creator') || creatorID;
    const communityFromBtn = btn.getAttribute('data-community') || communityID;
    if (scope === "community" && !communityFromBtn) {
      setError("Missing subscription details.");
      return;
    }
    if (scope === "profile" && (!plan || !creator)) {
      setError("Missing subscription details.");
      return;
    }

    const cardData = getIyzicoCardData();
    if (!cardData || !cardData.cardname || !cardData.cardnumber || !cardData.expmonth || !cardData.expyear || !cardData.cvv) {
      setError("Please complete your card details.");
      return;
    }

    btn.disabled = true;
    $(".i_modal_in_in").append(loaderHTML);

    const requestData = {
      f: iyzicoRequest,
      cardname: cardData.cardname,
      cardnumber: cardData.cardnumber,
      expmonth: cardData.expmonth,
      expyear: cardData.expyear,
      cvv: cardData.cvv
    };
    if (scope === "community") {
      requestData.community_id = communityFromBtn;
    } else if (scope === "profile") {
      requestData.u = creator;
      requestData.pl = plan;
    }
    if (csrfToken) {
      requestData.csrf_token = csrfToken;
    }

    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      dataType: "json",
      data: requestData,
      success: function (response) {
        $(".loaderWrapper").remove();
        if (response && response.status === "success" && response.htmlContent) {
          $("body").html(response.htmlContent);
        } else {
          const message = response && response.message ? response.message : "Payment failed.";
          setError(message);
          btn.disabled = false;
        }
      },
      error: function () {
        $(".loaderWrapper").remove();
        setError("Network error. Please try again.");
        btn.disabled = false;
      }
    });
  });

  $("body").on("click", ".pay_provider_toggle", function () {
    const target = $(this).attr('data-target');
    toggleGateway(target);
  });

  $("body").on("click", ".payClose", function () {
    $(".i_payment_pop_box").addClass("i_modal_in_in_out");
    setTimeout(() => {
      $(".i_subs_modal").remove();
      $("iframe").remove();
      $("strong").remove();
    }, 200);
    stripe = null;
    cardElement = null;
    expElement = null;
    cvcElement = null;
    stripeMounted = false;
  });

  toggleGateway(defaultGateway);
  if (stripeEnabled) {
    updatePayButtonState();
    $(document).on('input', '#name,#email', updatePayButtonState);
  }

  $("body").on("click", ".pay_subscription_flutterwave", function () {
    if (!flutterwaveEnabled) {
      return;
    }
    const btn = this;
    if (btn.disabled) {
      return;
    }
    const publicKey = flutterwaveConfig.testMode ? flutterwaveConfig.testPublicKey : flutterwaveConfig.livePublicKey;
    if (!publicKey || typeof FlutterwaveCheckout !== "function") {
      setError("Flutterwave is unavailable.");
      return;
    }

    const amount = parseFloat(flutterwaveConfig.amount || config.planAmount || 0);
    if (!amount || amount <= 0) {
      setError("Invalid subscription amount.");
      return;
    }

    btn.disabled = true;
    const originalLabel = btn.textContent;
    $(".i_modal_in_in").append(loaderHTML);

    const refBase = scope === "community" ? `community_${communityID}` : (scope === "community_plan" ? "community_plan" : `sub_${planID}`);
    const txRef = `${refBase}_${Date.now()}`;
    FlutterwaveCheckout({
      public_key: publicKey,
      tx_ref: txRef,
      amount: amount,
      currency: flutterwaveConfig.currency || "USD",
      customer: {
        email: flutterwaveConfig.customerEmail || config.subscriberEmail || "",
        name: flutterwaveConfig.customerName || config.subscriberName || ""
      },
      callback: function (response) {
        if (response && response.status === 'successful') {
          const requestData = {
            f: flutterwaveRequest,
            tx_ref: txRef,
            transaction_id: response.transaction_id,
            amount: amount
          };
          if (scope === "community") {
            requestData.community_id = communityID;
          } else if (scope === "profile") {
            requestData.u = creatorID;
            requestData.pl = planID;
          }
          if (csrfToken) {
            requestData.csrf_token = csrfToken;
          }
          $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: requestData,
            success: function (res) {
              cleanupLoader(btn, originalLabel);
              if (res && res.status === 'success') {
                if (scope === "community" || scope === "community_plan") {
                  const redirectUrl = res.redirect || res.redirect_url || buildSubscriptionSuccessUrl(res.order_id || res.orderId || "");
                  window.location.href = redirectUrl;
                  return;
                }
                location.reload();
              } else {
                setError((res && res.message) ? res.message : "Payment failed.");
              }
            },
            error: function () {
              cleanupLoader(btn, originalLabel);
              setError("Network error. Please try again.");
            }
          });
        } else {
          cleanupLoader(btn, originalLabel);
        }
      },
      onclose: function () {
        cleanupLoader(btn, originalLabel);
      }
    });
  });

})(jQuery);
