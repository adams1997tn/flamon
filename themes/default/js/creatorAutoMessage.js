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

    function showFormNotice(message) {
        if (!message) {
            return;
        }
        const $notice = $('.creator_auto_message_notice');
        if ($notice.length) {
            $notice.text(message).show();
            return;
        }
        showNotice(message);
    }

    function clearFormNotice() {
        const $notice = $('.creator_auto_message_notice');
        if ($notice.length) {
            $notice.text('').hide();
        }
    }

    function updateFileLabel(input) {
        const $input = $(input);
        const $label = $input.closest('.creator_bulk_file_label');
        const $text = $label.find('.creator_bulk_file_text');
        if (!$text.length) {
            return;
        }
        const defaultText = $text.data('default') || '';
        const fileName = input.files && input.files.length ? input.files[0].name : '';
        $text.text(fileName || defaultText);
    }

    function resetFileLabel($input) {
        const $label = $input.closest('.creator_bulk_file_label');
        const $text = $label.find('.creator_bulk_file_text');
        if (!$text.length) {
            return;
        }
        const defaultText = $text.data('default') || '';
        $text.text(defaultText);
    }

    function toggleThumbnailField(fileInput) {
        const $form = $('#creatorAutoMessageForm');
        const $thumbField = $('.creator_bulk_thumb_field');
        const $thumbInput = $('.creator_bulk_thumb_input');
        if (!$form.length || !$thumbField.length) {
            return;
        }
        const ffmpegEnabled = String($form.data('ffmpeg')) === '1';
        if (ffmpegEnabled) {
            $thumbField.addClass('creator_bulk_thumb_hidden');
            $thumbInput.val('');
            resetFileLabel($thumbInput);
            return;
        }
        const file = fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
        const ext = file && file.name ? file.name.split('.').pop().toLowerCase() : '';
        if (ext === 'mp4') {
            $thumbField.removeClass('creator_bulk_thumb_hidden');
        } else {
            $thumbField.addClass('creator_bulk_thumb_hidden');
            $thumbInput.val('');
            resetFileLabel($thumbInput);
        }
    }

    $(document).on('change', '.creator_bulk_file_input', function() {
        updateFileLabel(this);
    });

    $(document).on('change', 'input[name="auto_attachment"]', function() {
        toggleThumbnailField(this);
    });

    $(document).on('submit', '#creatorAutoMessageForm', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(form[0]);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                clearFormNotice();
                form.append(loadingHTML);
                form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                form.find(':input[type=submit]').prop('disabled', false);
                $(".loaderWrapper").remove();
                if (data === '200') {
                    window.location.reload();
                    return;
                }
                showFormNotice(data);
            }
        });
    });
})(jQuery);
