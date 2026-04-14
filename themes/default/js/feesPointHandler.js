(function($){
  "use strict";

  $(document).ready(function(){

    function decimalFormat(value) {
      if (typeof window.dizzyFormatCurrency === "function") {
        return window.dizzyFormatCurrency(value);
      }
      return value;
    }

    $("body").on("keyup", ".aval", function(){
      const $input = $(this);
      const val = parseFloat($input.val());
      const min = parseFloat($input.data("min"));
      const rate = parseFloat($input.data("rate"));
      const fee = parseFloat($input.data("fee"));
      const id = $input.attr("id");

      $(".i_t_warning, .i_t_warning_earning").hide();

      if (!isNaN(val)) {
        const calculate = val * rate - (val * rate * fee);
        const output = decimalFormat(calculate);

        if (val >= min) {
          if (id === "spweek") {
            $(".weekly_earning").show();
            $("#weekly_earning").html(output);
          } else if (id === "spmonth") {
            $(".mamonthly_earning").show();
            $("#mamonthly_earning").html(output);
          } else if (id === "spyear") {
            $(".yayearly_earning").show();
            $("#yayearly_earning").html(output);
          }
        } else {
          if (id === "spweek") {
            $("#waweekly").show();
          } else if (id === "spmonth") {
            $("#mamonthly").show();
          } else if (id === "spyear") {
            $("#yayearly").show();
          }
        }
      } else {
        $(".i_t_warning_earning").hide();
      }
    });

    $("body").on("click", ".c_Next", function(){
      const weekly = $("#spweek").val() || '';
      const monthly = $("#spmonth").val() || '';
      const yearly = $("#spyear").val() || '';

      const weeklyStatus = $('input[name="weekly"]').prop("checked") ? 1 : 0;
      const monthlyStatus = $('input[name="monthly"]').prop("checked") ? 1 : 0;
      const yearlyStatus = $('input[name="yearly"]').prop("checked") ? 1 : 0;

      if (weeklyStatus && weekly.length === 0) { $("#wweekly").show(); return; }
      if (monthlyStatus && monthly.length === 0) { $("#wmonthly").show(); return; }
      if (yearlyStatus && yearly.length === 0) { $("#wyearly").show(); return; }

      const data = {
        f: "setSubscriptionPayments",
        wSubWeekAmount: weekly,
        mSubMonthAmount: monthly,
        mSubYearAmount: yearly,
        wStatus: weeklyStatus,
        mStatus: monthlyStatus,
        yStatus: yearlyStatus
      };

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function() {
          $(".i_nex_btn").css("pointer-events", "none");
        },
        success: function(response) {
          if (String(response).trim() === "200") {
            location.reload();
          } else {
            $(".i_nex_btn").css("pointer-events", "auto");
          }
        }
      });
    });

  });

})(jQuery);
