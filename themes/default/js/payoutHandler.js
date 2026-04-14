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
        if(bankAccount === ""){
          $("#setBankWarning").show();
          return null;
        }
      }

      return {
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
