(function($) {
    "use strict";

    const loadingHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader"><div class="i_loading product_page_loading"><div class="dot-pulse"></div></div></div></div></div>';

    function showNotice(message) {
        if (!message) {
            return;
        }
        $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + message + '</div></div>');
        setTimeout(() => {
            $(".nnauthority").remove();
        }, 5000);
    }

    function handleSimpleResponse(data) {
        if (data === '200') {
            window.location.reload();
            return;
        }
        showNotice(data);
    }

    $(document).on('submit', '#agencyStatusForm', function(e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: form.serialize(),
            beforeSend: function() {
                $(".warning_two , .successNot , .warning_one").hide();
                form.append(loadingHTML);
                form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                form.find(':input[type=submit]').prop('disabled', false);
                $(".loaderWrapper").remove();
                handleSimpleResponse(data);
            }
        });
    });

    $(document).on('click', '.agencyCreateRequestAction', function() {
        const button = $(this);
        const requestId = button.data('request');
        const status = button.data('status');
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: {
                f: 'agencyCreateRequestStatus',
                request_id: requestId,
                status: status,
                csrf_token: window.csrfToken
            },
            beforeSend: function() {
                button.append(loadingHTML);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                handleSimpleResponse(data);
            }
        });
    });

    $(document).on('submit', '#bulkCampaignForm', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(form[0]);
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                form.append(loadingHTML);
                form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                form.find(':input[type=submit]').prop('disabled', false);
                $(".loaderWrapper").remove();
                handleSimpleResponse(data);
            }
        });
    });

    $(document).on('click', '.bulkBuildQueue', function() {
        const button = $(this);
        const campaignId = button.data('id');
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: {
                f: 'bulkBuildQueue',
                bc_id: campaignId,
                csrf_token: window.csrfToken
            },
            beforeSend: function() {
                button.append(loadingHTML);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                handleSimpleResponse(data);
            }
        });
    });

    $(document).on('click', '.bulkPause, .bulkResume, .bulkCancel', function() {
        const button = $(this);
        const campaignId = button.data('id');
        const status = button.data('status');
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: {
                f: 'bulkUpdateCampaignStatus',
                bc_id: campaignId,
                status: status,
                csrf_token: window.csrfToken
            },
            beforeSend: function() {
                button.append(loadingHTML);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                handleSimpleResponse(data);
            }
        });
    });
})(jQuery);
