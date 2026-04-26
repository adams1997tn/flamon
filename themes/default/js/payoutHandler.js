(function($){
  "use strict";

  // ============================================================
  //  DEBUG SWITCH — flip to false to silence all debug logging.
  //  You can also toggle at runtime from DevTools:
  //      window.DIZZY_PAYOUT_DEBUG = true;   // turn ON
  //      window.DIZZY_PAYOUT_DEBUG = false;  // turn OFF
  // ============================================================
  var DIZZY_PAYOUT_DEBUG_DEFAULT = true;
  if (typeof window.DIZZY_PAYOUT_DEBUG === "undefined") {
    window.DIZZY_PAYOUT_DEBUG = DIZZY_PAYOUT_DEBUG_DEFAULT;
  }
  function dlog() {
    if (!window.DIZZY_PAYOUT_DEBUG) return;
    try {
      var args = Array.prototype.slice.call(arguments);
      args.unshift("%c[payout]", "color:#468cef;font-weight:bold");
      console.log.apply(console, args);
      _panelAppend("log", args.slice(1));
    } catch (e) {}
  }
  function dwarn() {
    if (!window.DIZZY_PAYOUT_DEBUG) return;
    try {
      var args = Array.prototype.slice.call(arguments);
      args.unshift("%c[payout]", "color:#e67e22;font-weight:bold");
      console.warn.apply(console, args);
      _panelAppend("warn", args.slice(1));
    } catch (e) {}
  }
  function derr() {
    if (!window.DIZZY_PAYOUT_DEBUG) return;
    try {
      var args = Array.prototype.slice.call(arguments);
      args.unshift("%c[payout]", "color:#e74c3c;font-weight:bold");
      console.error.apply(console, args);
      _panelAppend("err", args.slice(1));
    } catch (e) {}
  }
  function _panelAppend(level, parts) {
    var panel = document.getElementById("pyotDebugPanel");
    var box = document.getElementById("pyotDebugLog");
    if (!panel || !box) return;
    panel.style.display = "block";
    var color = level === "err" ? "#fca5a5" : (level === "warn" ? "#fcd34d" : "#bae6fd");
    var line = document.createElement("div");
    line.style.color = color;
    line.style.borderTop = "1px dashed #1f2937";
    line.style.paddingTop = "3px";
    line.style.marginTop = "3px";
    var t = new Date().toISOString().substr(11, 12);
    var text = parts.map(function(p){
      try {
        if (typeof p === "string") return p;
        return JSON.stringify(p);
      } catch(_) { return String(p); }
    }).join(" ");
    line.textContent = "[" + t + "] " + text;
    box.appendChild(line);
    box.scrollTop = box.scrollHeight;
  }

  $(document).ready(function(){
    dlog("payoutHandler ready. Toggle: window.DIZZY_PAYOUT_DEBUG =", window.DIZZY_PAYOUT_DEBUG);
    dlog("DOM check: #pyot_next_btn=", $("#pyot_next_btn").length, " #pyot_skip_btn=", $("#pyot_skip_btn").length, " #bankForm=", $("#bankForm").length, " checked radio=", $('input[name="default"]:checked', '#bankForm').val());

    function validateEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    }

    function togglePayoutMethodFields() {
      const selectedMethod = $('input[name="default"]:checked', '#bankForm').val() || "";
      $(".payout_method_fields").hide();
      $('.payout_method_fields[data-method="' + selectedMethod + '"]').show();
    }

    function hidePayoutWarnings() {
      $("#setWarning, #notMatch, #notValidE, #setBankWarning").hide();
    }

    function buildAndValidatePayoutPayload() {
      const defaultMethod = $('input[name="default"]:checked', '#bankForm').val() || "";
      dlog("buildAndValidatePayoutPayload: defaultMethod=", JSON.stringify(defaultMethod));
      const paypalEmail = $.trim($("#paypale").val() || "");
      const repaypalEmail = $.trim($("#paypalere").val() || "");
      let bankAccount = $.trim($("#bank_transfer").val() || "");
      const payoneerEmail = $.trim($("#payoneer_email").val() || "");
      const payoneerReEmail = $.trim($("#payoneer_email_re").val() || "");
      const zelleEmail = $.trim($("#zelle_email").val() || "");
      const zelleReEmail = $.trim($("#zelle_email_re").val() || "");
      const westernUnionFullName = $.trim($("#western_union_full_name").val() || "");
      const westernUnionDocumentId = $.trim($("#western_union_document_id").val() || "");
      const bitcoinWallet = $.trim($("#bitcoin_wallet").val() || "");
      const mercadoPagoAlias = $.trim($("#mercadopago_alias").val() || "");
      const mercadoPagoCvu = $.trim($("#mercadopago_cvu").val() || "");

      hidePayoutWarnings();

      if(defaultMethod === "paypal"){
        if(paypalEmail === "" || repaypalEmail === ""){
          $("#setWarning").show();
          return null;
        }
        if(!validateEmail(paypalEmail) || !validateEmail(repaypalEmail)){
          $("#notValidE").show();
          return null;
        }
        if(paypalEmail !== repaypalEmail){
          $("#notMatch").show();
          return null;
        }
      } else if (defaultMethod === "payoneer") {
        if(payoneerEmail === "" || payoneerReEmail === ""){
          $("#setWarning").show();
          return null;
        }
        if(!validateEmail(payoneerEmail) || !validateEmail(payoneerReEmail)){
          $("#notValidE").show();
          return null;
        }
        if(payoneerEmail !== payoneerReEmail){
          $("#notMatch").show();
          return null;
        }
      } else if (defaultMethod === "zelle") {
        if(zelleEmail === "" || zelleReEmail === ""){
          $("#setWarning").show();
          return null;
        }
        if(!validateEmail(zelleEmail) || !validateEmail(zelleReEmail)){
          $("#notValidE").show();
          return null;
        }
        if(zelleEmail !== zelleReEmail){
          $("#notMatch").show();
          return null;
        }
      } else if (defaultMethod === "western-union") {
        if(westernUnionFullName === "" || westernUnionDocumentId === ""){
          $("#setBankWarning").show();
          return null;
        }
      } else if (defaultMethod === "bitcoin") {
        if(bitcoinWallet === ""){
          $("#setBankWarning").show();
          return null;
        }
      } else if (defaultMethod === "mercadopago") {
        if(mercadoPagoAlias === "" || mercadoPagoCvu === ""){
          $("#setBankWarning").show();
          return null;
        }
      } else {
        // Bank transfer – fields are optional.
        var btRoot = document.querySelector('[data-bt-form]');
        if (btRoot && typeof btRoot.btValidate === 'function') {
          if (!btRoot.btValidate()) {
            $("#setBankWarning").show();
            return null;
          }
          if (typeof btRoot.btSerialize === 'function') {
            btRoot.btSerialize();
          }
          bankAccount = $.trim($("#bank_transfer").val() || "");
        }
      }

      var payload = {
        f: "payoutSet",
        paypalEmail: encodeURIComponent(paypalEmail),
        paypalReEmail: encodeURIComponent(repaypalEmail),
        bank: bankAccount,
        method: defaultMethod,
        payoneerEmail: encodeURIComponent(payoneerEmail),
        payoneerReEmail: encodeURIComponent(payoneerReEmail),
        zelleEmail: encodeURIComponent(zelleEmail),
        zelleReEmail: encodeURIComponent(zelleReEmail),
        westernUnionFullName: westernUnionFullName,
        westernUnionDocumentId: westernUnionDocumentId,
        bitcoinWallet: bitcoinWallet,
        mercadoPagoAlias: mercadoPagoAlias,
        mercadoPagoCvu: mercadoPagoCvu
      };
      if (defaultMethod === "bank") {
        var btRoot2 = document.querySelector('[data-bt-form]');
        if (btRoot2 && typeof btRoot2.btSerialize === 'function') {
          var bt = btRoot2.btSerialize();
          payload.bank_country = bt.bank_country;
          payload.iban_number = bt.iban_number;
          payload.routing_number = bt.routing_number;
          payload.account_number = bt.account_number;
          payload.confirm_account_number = bt.confirm_account_number;
          payload.account_holder_name = bt.account_holder_name;
          payload.phone_country_code = bt.phone_country_code;
          payload.phone_number = bt.phone_number;
          payload.street_address = bt.street_address;
          payload.country = bt.country;
          payload.state = bt.state;
          payload.city = bt.city;
          payload.postal_code = bt.postal_code;
        }
      }
      return payload;
    }

    togglePayoutMethodFields();
    $("body").on("change", '#bankForm input[name="default"]', function(){
      hidePayoutWarnings();
      togglePayoutMethodFields();
    });

    $("body").on("click", ".pyot_Skip", function(){
      hidePayoutWarnings();
      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: { f: "payoutSkip" },
        cache: false,
        beforeSend: function() {
          $(".i_nex_btn").css("pointer-events", "none");
        },
        success: function(response) {
          $(".i_nex_btn").css("pointer-events", "auto");
          if (String(response).trim() === "200") {
            location.reload();
          }
        },
        error: function() {
          $(".i_nex_btn").css("pointer-events", "auto");
        }
      });
    });

    function handleNextClick(e){
      if (e && e.preventDefault) e.preventDefault();
      dlog("Next clicked");
      var data;
      try {
        data = buildAndValidatePayoutPayload();
      } catch (ex) {
        derr("buildAndValidatePayoutPayload threw:", ex && ex.message ? ex.message : ex);
        $("#setBankWarning").show();
        return false;
      }
      if(!data){
        dwarn("Validation failed (no payload returned). Aborting Next.");
        return false;
      }
      dlog("Submitting payload to", siteurl + "requests/request.php");
      dlog("Payload:", data);

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function() {
          dlog("AJAX beforeSend");
          $(".i_nex_btn").css("pointer-events", "none");
        },
        success: function(response, status, xhr) {
          $(".i_nex_btn").css("pointer-events", "auto");
          dlog("AJAX success. status=", status, " HTTP=", xhr && xhr.status, " response=", response);

          var trimmed = String(response == null ? "" : response).trim();
          if(trimmed === "200"){
            dlog("Server OK -> reloading");
            location.reload();
          } else if(trimmed === "email_warning"){
            dwarn("Server: email_warning"); $("#notMatch").show();
          } else if(trimmed === "paypal_warning"){
            dwarn("Server: paypal_warning"); $("#setWarning").show();
          } else if(trimmed === "bank_warning"){
            dwarn("Server: bank_warning"); $("#setBankWarning").show();
          } else if(trimmed === "not_valid_email"){
            dwarn("Server: not_valid_email"); $("#notValidE").show();
          } else {
            derr("Unrecognized / empty server response. Raw:", JSON.stringify(response));
            $("#setBankWarning").show();
          }
        },
        error: function(xhr, status, err) {
          $(".i_nex_btn").css("pointer-events", "auto");
          derr("AJAX error. HTTP=", xhr && xhr.status, " status=", status, " err=", err, " responseText=", xhr && xhr.responseText);
          $("#setBankWarning").show();
        }
      });
      return false;
    }

    // Delegated (works if the button is re-rendered)
    $("body").off("click.pyotNext").on("click.pyotNext", ".pyot_Next, #pyot_next_btn", handleNextClick);
    // Direct binding (defensive — bypasses any event-stopping ancestor handler)
    var nextEl = document.getElementById("pyot_next_btn");
    if (nextEl) {
      nextEl.addEventListener("click", handleNextClick, false);
      dlog("Direct click listener attached to #pyot_next_btn");
    } else {
      dwarn("#pyot_next_btn NOT FOUND in DOM at ready time.");
    }
  });

})(jQuery);
