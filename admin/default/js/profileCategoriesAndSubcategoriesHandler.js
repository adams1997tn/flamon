(function($) {
    "use strict";
    var profilePageSelector = ".profile-categories-page";

    if (!$(profilePageSelector).length) {
        return;
    }

    function getCsrfToken() {
        var csrfFromPage = $(profilePageSelector).find('input[name="csrf_token"]').first().val();
        var csrfFallback = $('input[name="csrf_token"]').first().val();
        return csrfFromPage || csrfFallback || "";
    }

    function normalizeKey(value) {
        return $.trim(value).replace(/\s+/g, "_");
    }

    function parseActionFromData(data) {
        if (typeof data !== "string") {
            return "";
        }
        var match = data.match(/(?:^|&)f=([^&]+)/);
        if (!match || !match[1]) {
            return "";
        }
        return decodeURIComponent(match[1].replace(/\+/g, " "));
    }

    $.ajaxPrefilter(function(options, originalOptions) {
        var csrfToken = getCsrfToken();
        var allowedActions = {
            addNewSubCat: true,
            subCatMod: true,
            upSubKey: true,
            delSubCat: true,
            catModStatus: true,
            upCatKey: true,
            delCatt: true,
            cNewCatP: true,
            newProfileCategory: true
        };
        var requestUrl = options.url || "";
        var requestData = (originalOptions && originalOptions.data) || options.data || "";
        var action = parseActionFromData(requestData);

        if (!csrfToken || !allowedActions[action]) {
            return;
        }
        if (requestUrl.indexOf("request/request.php") === -1 && requestUrl.indexOf("request/popup.php") === -1) {
            return;
        }
        if (typeof requestData === "string" && requestData.indexOf("csrf_token=") === -1) {
            requestData += "&csrf_token=" + encodeURIComponent(csrfToken);
            options.data = requestData;
            if (originalOptions) {
                originalOptions.data = requestData;
            }
        }
    });

    $(document).on("click", ".sbEd, .sceEd", function() {
        var ID = $(this).attr("id");
        $(".se_" + ID).toggle();
        $(".sc_" + ID).toggle();
        $(".sc_e_" + ID).toggle();
        $(".sc_ed_" + ID).toggle();
    });

    $(document).on("click", ".newSubC", function() {
        var ID = $(this).attr("data-id");
        var inputSelector = "#n_" + ID;
        var newRow = $(".n_s_c_" + ID);
        if (newRow.is(":visible")) {
            newRow.hide();
        } else {
            newRow.css("display", "flex");
        }
        if ($(inputSelector).is(":visible")) {
            $(inputSelector).trigger("focus");
        }
    });

    $(document).on("click", ".toggleSubCategories", function() {
        var categoryID = $(this).attr("data-id");
        var targetWrap = $("#sub_wrap_" + categoryID);
        var icon = $(this).find(".toggleSubCategoriesIcon");
        var isVisible = targetWrap.is(":visible");

        if (isVisible) {
            targetWrap.slideUp(150);
            icon.text("+");
            $(this).attr("aria-expanded", "false");
        } else {
            targetWrap.slideDown(150, function() {
                targetWrap.css("display", "flex");
            });
            icon.text("-");
            $(this).attr("aria-expanded", "true");
        }
    });

    $(document).on("click", ".edittCat, .svcEdt", function() {
        var ID = $(this).attr("id");
        $(".cse_" + ID).toggle();
        $(".cse_i_" + ID).toggle();
        $(".s_h_" + ID).toggle();
        $(".edtt_cat_" + ID).toggle();
    });

    $(document).on("blur", ".keyFormatInput", function() {
        $(this).val(normalizeKey($(this).val()));
    });
})(jQuery);
