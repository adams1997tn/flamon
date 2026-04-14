(function ($) {
    "use strict";

    $("#boostGeneralSettingsForm").ajaxForm({
        type: "POST",
        url: siteurl + "request/request.php",
        cache: false,
        beforeSubmit: function () {
            $(".boostSettingsSaved").hide();
            $("#general_conf").append(plreLoadingAnimationPlus);
        },
        success: function (response) {
            $(".loaderWrapper").remove();
            if (response === "200") {
                $(".boostSettingsSaved").show();
                setTimeout(function () {
                    $(".boostSettingsSaved").hide();
                }, 4000);
                return;
            }

            $("body").append(
                '<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' +
                    response +
                    "</div></div>"
            );
            setTimeout(function () {
                $(".nnauthority").remove();
            }, 5000);
        },
    });
})(jQuery);
