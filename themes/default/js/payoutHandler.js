(function($){
  "use strict";

  $(document).ready(function(){
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
      const paypalEmail = $.trim($("#paypale").val() || "");
      const repaypalEmail = $.trim($("#paypalere").val() || "");
      const bankAccount = $.trim($("#bank_transfer").val() || "");
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
        // Bank transfer – premium structured form.
        var btRoot = document.querySelector('[data-bt-form]');
        if (btRoot && typeof btRoot.btValidate === 'function') {
          if (!btRoot.btValidate()) {
            $("#setBankWarning").show();
            return null;
          }
          btRoot.btSerialize();
          bankAccount = $.trim($("#bank_transfer").val() || "");
        }
        if(bankAccount === ""){
          $("#setBankWarning").show();
          return null;
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

    $("body").on("click", ".pyot_Next", function(){
      const data = buildAndValidatePayoutPayload();
      if(!data){
        return false;
      }

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function() {
          $(".i_nex_btn").css("pointer-events", "none");
        },
        success: function(response) {
          $(".i_nex_btn").css("pointer-events", "auto");

          if(String(response).trim() === "200"){
            location.reload();
          } else if(response === "email_warning"){
            $("#notMatch").show();
          } else if(response === "paypal_warning"){
            $("#setWarning").show();
          } else if(response === "bank_warning"){
            $("#setBankWarning").show();
          } else if(response === "not_valid_email"){
            $("#notValidE").show();
          }
        }
      });
    });
  });

})(jQuery);
