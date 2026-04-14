(function($) {
    "use strict";

    $(document).on("click", ".dmyStory_extra", function() {
        var ID = $(this).attr("id");
        if (!ID) { return; }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "delete_reel_alert", id: ID },
            cache: false,
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(function() {
                        $(".i_modal_bg_in").addClass("i_modal_display_in");
                    }, 200);
                }
            }
        });
    });

    $(document).on("click", ".yes-del-reel", function() {
        var ID = $(this).attr("id");
        if (!ID) { return; }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "deleteReelUpload", id: ID },
            cache: false,
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(function() {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response === "200") {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts("delete_success", "ialert");
                } else {
                    PopUPAlerts("delete_not_success", "ialert");
                }
            }
        });
    });

})(jQuery);
