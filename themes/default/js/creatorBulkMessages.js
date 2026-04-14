(function($) {
    "use strict";

    const loadingHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader"><div class="i_loading product_page_loading"><div class="dot-pulse"></div></div></div></div></div>';

    function getCsrfToken() {
        const input = document.querySelector('#creatorBulkCampaignForm input[name="csrf_token"]');
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

    function showFormNotice(message) {
        if (!message) {
            return;
        }
        const $notice = $('.creator_bulk_form_notice');
        if ($notice.length) {
            $notice.text(message).show();
            return;
        }
        showNotice(message);
    }

    function clearFormNotice() {
        const $notice = $('.creator_bulk_form_notice');
        if ($notice.length) {
            $notice.text('').hide();
        }
    }

    function sanitizePriceInput(value) {
        let cleaned = String(value || '').replace(/[^0-9.]/g, '');
        const parts = cleaned.split('.');
        if (parts.length > 2) {
            cleaned = parts.shift() + '.' + parts.join('');
        }
        if (cleaned.startsWith('.')) {
            cleaned = '0' + cleaned;
        }
        return cleaned;
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
        const $form = $('#creatorBulkCampaignForm');
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

    $(document).on('change', 'input[name="bulk_attachment"]', function() {
        toggleThumbnailField(this);
    });

    $(document).on('input', '.creator_bulk_price_input', function() {
        const cleaned = sanitizePriceInput(this.value);
        if (this.value !== cleaned) {
            this.value = cleaned;
        }
    });

    $(document).on('submit', '#creatorBulkCampaignForm', function(e) {
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

    $(document).on('click', '.creatorBulkBuildQueue', function() {
        const button = $(this);
        const campaignId = button.data('id');
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'creatorBulkBuildQueue',
                bc_id: campaignId,
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

    $(document).on('click', '.creatorBulkPause, .creatorBulkResume, .creatorBulkCancel', function() {
        const button = $(this);
        const campaignId = button.data('id');
        const status = button.data('status');
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: 'creatorBulkUpdateCampaignStatus',
                bc_id: campaignId,
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
})(jQuery);
