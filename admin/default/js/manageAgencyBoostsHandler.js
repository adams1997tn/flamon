(function($) {
    "use strict";

    function getCsrfToken() {
        const input = $('input[name=csrf_token]').first();
        return input.length ? input.val() : '';
    }

    $(document).on("click", ".search_vl", function() {
        const value = $("#srcMe").val();
        const filter = $("#agencyBoostFilter").val() || "all";
        if (value) {
            const url = window.location.origin + '/admin/manage_agency_boosts?page-id=1&sr=' + encodeURIComponent(value) + '&st=' + encodeURIComponent(filter);
            window.location.href = url;
        } else {
            const url = window.location.origin + '/admin/manage_agency_boosts?page-id=1&st=' + encodeURIComponent(filter);
            window.location.href = url;
        }
    });

    $(document).on("change", "#agencyBoostFilter", function() {
        $(".search_vl").trigger("click");
    });

    $(document).on("click", ".cleanup_agency_boosts", function() {
        var data = "f=cleanupAgencyBoosts";
        var csrf = getCsrfToken();
        if (csrf) {
            data += "&csrf_token=" + encodeURIComponent(csrf);
        }
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: data,
            cache: false,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                try {
                    var parsed = typeof response === "string" ? JSON.parse(response) : response;
                    if (parsed && parsed.status === "200") {
                        location.reload();
                        return;
                    }
                } catch (e) {}
                $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                setTimeout(() => {
                    $(".nnauthority").remove();
                }, 5000);
            }
        });
    });

    $(document).on("click", ".agencyBoostAdminDisable", function() {
        var boostId = $(this).data("boost");
        if (!boostId) {
            return;
        }
        var data = "f=agencyBoostAdminDisable&boost_id=" + encodeURIComponent(boostId);
        var csrf = getCsrfToken();
        if (csrf) {
            data += "&csrf_token=" + encodeURIComponent(csrf);
        }
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: data,
            cache: false,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                if (response === "200") {
                    location.reload();
                    return;
                }
                $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                setTimeout(() => {
                    $(".nnauthority").remove();
                }, 5000);
            }
        });
    });
})(jQuery);
