(function ($) {
    "use strict";

    $(document).on("submit", ".community_admin_ajax", function (e) {
        e.preventDefault();
        const $form = $(this);
        const action = $form.attr("action");
        if (!action) {
            return;
        }
        const hasFiles = $form.attr("enctype") === "multipart/form-data";
        let payload = null;
        let ajaxOptions = {};

        if (hasFiles) {
            payload = new FormData(this);
            ajaxOptions = {
                processData: false,
                contentType: false
            };
        } else {
            payload = $form.serialize();
        }

        $.ajax({
            type: "POST",
            url: action,
            data: payload,
            cache: false,
            ...ajaxOptions,
            success: function (response) {
                if (String(response).trim() === "200") {
                    location.reload();
                    return;
                }
                if (response) {
                    alert(response);
                    return;
                }
                if (typeof window.PopUPAlerts === "function") {
                    window.PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                if (typeof window.PopUPAlerts === "function") {
                    window.PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });
})(jQuery);
