(function($) {
    "use strict";

    const loadingHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader"><div class="i_loading product_page_loading"><div class="dot-pulse"></div></div></div></div></div>';

    function getCsrfToken() {
        const input = document.querySelector('#creatorAgencyCsrf');
        if (input && input.value) {
            return input.value;
        }
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

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

    function updateAgencyBoostRowState($row) {
        if (!$row || !$row.length) {
            return;
        }
        const canCreate = ($row.data('can-create') || '0').toString() === '1';
        const $button = $row.find('.agencyBoostCreate');
        if (!$button.length) {
            return;
        }
        if (!canCreate) {
            $button.addClass('disabled').attr('aria-disabled', 'true');
            return;
        }
        const method = $row.find('.agencyBoostPaymentMethod').val() || 'points';
        if (method === 'points') {
            const requiredPoints = parseInt($row.data('points-required') || 0, 10);
            const availablePoints = parseInt($row.data('points-available') || 0, 10);
            if (requiredPoints > 0 && availablePoints < requiredPoints) {
                $button.addClass('disabled').attr('aria-disabled', 'true');
                return;
            }
        }
        $button.removeClass('disabled').attr('aria-disabled', 'false');
    }

    $(document).on('click', '.creatorAgencyRequest', function() {
        const button = $(this);
        const agencyId = button.data('agency');
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'creatorAgencyRequest',
                agency_id: agencyId,
                csrf_token: getCsrfToken()
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

    $(document).on('submit', '#creatorAgencyCreateForm', function(e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: form.serialize(),
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

    $(document).on('click', '.creatorAgencyInviteAction', function() {
        const button = $(this);
        const requestId = button.data('request');
        const status = button.data('status');
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'creatorAgencyRequestStatus',
                request_id: requestId,
                status: status,
                csrf_token: getCsrfToken()
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

    $(document).on('click', '.agencyOwnerRequestAction', function() {
        const button = $(this);
        const requestId = button.data('request');
        const status = button.data('status');
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'agencyOwnerRequestStatus',
                request_id: requestId,
                status: status,
                csrf_token: getCsrfToken()
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

    $(document).on('change', '.agencyBoostPaymentMethod', function() {
        const $row = $(this).closest('.agency_boost_row');
        updateAgencyBoostRowState($row);
    });

    $(function() {
        const agencyProfileForm = $("#agencyProfileForm");
        if (agencyProfileForm.length) {
            agencyProfileForm.ajaxForm({
                type: "POST",
                url: siteurl + "requests/request.php",
                beforeSubmit: function() {
                    agencyProfileForm.append(loadingHTML);
                    agencyProfileForm.find(':input[type=submit]').prop('disabled', true);
                },
                success: function(data) {
                    agencyProfileForm.find(':input[type=submit]').prop('disabled', false);
                    $(".loaderWrapper").remove();
                    handleSimpleResponse(data);
                }
            });
        }
    });

    $(function() {
        $(".agency_boost_row").each(function() {
            updateAgencyBoostRowState($(this));
        });
    });

    $(document).on('click', '.agencyBoostCreate', function() {
        const button = $(this);
        if (button.hasClass('disabled') || button.attr('aria-disabled') === 'true') {
            return;
        }
        const agencyId = button.data('agency');
        const creatorId = button.data('creator');
        const row = button.closest('.agency_boost_row');
        const durationInput = row.find('.agencyBoostDuration');
        const durationDays = durationInput.length ? durationInput.val() : '';
        const method = row.find('.agencyBoostPaymentMethod').val() || 'points';
        if (method === 'points') {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: {
                    f: 'agencyBoostCreate',
                    agency_id: agencyId,
                    creator_id: creatorId,
                    duration_days: durationDays,
                    payment_method: 'points',
                    csrf_token: getCsrfToken()
                },
                beforeSend: function() {
                    button.append(loadingHTML);
                },
                success: function(data) {
                    $(".loaderWrapper").remove();
                    handleSimpleResponse(data);
                }
            });
            return;
        }

        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'agencyBoostPaymentMethods',
                agency_id: agencyId,
                creator_id: creatorId,
                duration_days: durationDays,
                csrf_token: getCsrfToken()
            },
            beforeSend: function() {
                button.append(loadingHTML);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                if (response && response !== '404') {
                    $("body").append(response);
                    setTimeout(function() {
                        $(".i_modal_bg_in").last().addClass('i_modal_display_in');
                    }, 60);
                } else {
                    handleSimpleResponse(response);
                }
            }
        });
    });

    $(document).on('click', '.agencyBoostDisable', function() {
        const button = $(this);
        const boostId = button.data('boost');
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'agencyBoostDisable',
                boost_id: boostId,
                csrf_token: getCsrfToken()
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
