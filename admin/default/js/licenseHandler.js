$(document).on('submit', '#licenseActivationForm', function(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('button[type=submit]');
    var notice = $('.license_notice_area');
    notice.text('').removeClass('error success');
    $.ajax({
        type: 'POST',
        url: siteurl + 'request/request.php',
        dataType: 'json',
        data: $form.serialize() + '&f=license_init',
        beforeSend: function() {
            $btn.prop('disabled', true);
            notice.text($form.data('redirecting')).removeClass('error').addClass('success');
        },
        success: function(resp) {
            if (resp && resp.status === 'ok' && resp.url) {
                window.location.href = resp.url;
            } else {
                notice.text($form.data('error')).addClass('error').removeClass('success');
                $btn.prop('disabled', false);
            }
        },
        error: function() {
            notice.text($form.data('error')).addClass('error').removeClass('success');
            $btn.prop('disabled', false);
        }
    });
});

$(document).on('click', '#licenseDeactivate', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var notice = $('.license_notice_area');
    notice.text('').removeClass('error success');
    $.ajax({
        type: 'POST',
        url: siteurl + 'request/request.php',
        data: {
            f: 'license_deactivate',
            csrf_token: $btn.data('csrf')
        },
        success: function(resp) {
            if (resp === '200') {
                window.location.reload();
            } else {
                notice.text($btn.data('error')).addClass('error').removeClass('success');
            }
        },
        error: function() {
            notice.text($btn.data('error')).addClass('error').removeClass('success');
        }
    });
});
