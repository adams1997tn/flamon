(function($) {
  "use strict";

  $(function() {
    // LightGallery initialization
    $('.lightGalleryInit').each(function() {
      const postID = $(this).data('id');
      const $galleryEl = $('#lightgallery' + postID);
      if ($galleryEl.length) {
        $galleryEl.lightGallery({
          videojs: true,
          mode: 'lg-fade',
          cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
          download: false,
          share: false
        });
      }
    });

    // Apply background styles from data-style attributes
    $('[data-style]').each(function() {
      $(this).attr('style', $(this).data('style'));
    });

    // GreenAudioPlayer initialization (safe delay)
    setTimeout(() => {
      $('.green-audio-player').each(function() {
        const id = $(this).attr('id');
        if ($('#' + id + ' audio').length) {
          new GreenAudioPlayer('#' + id, {
            stopOthersOnPlay: true,
            showTooltips: true,
            showDownloadButton: false,
            enableKeystrokes: true
          });
        }
      });
    }, 300);

    /* Admin campaign cover upload */
    const adminCoverBtn = $('#adminCampaignCoverButton');
    const adminCoverDrop = $('#adminCampaignCoverDrop');
    const adminCoverInput = $('#adminCampaignCoverInput');
    const adminCoverPreview = $('#adminCampaignCoverPreview');
    const adminCoverImg = $('#adminCampaignCoverImg');
    const adminCoverId = $('#adminCampaignCoverId');
    const adminCoverRemove = $('#adminCampaignCoverRemove');

    function adminExtractCoverFromHtml(html) {
      if (!html) { return null; }
      const $wrap = $('<div>').html(html);
      const $item = $wrap.find('.i_uploaded_item').first();
      if (!$item.length) { return null; }
      const id = $item.attr('id') || '';
      let imgSrc = '';
      const $img = $item.find('img.i_file').first();
      if ($img.length) {
        imgSrc = $img.attr('src') || '';
      } else {
        const bg = $item.find('.i_uploaded_file').first().css('background-image') || '';
        if (bg) {
          imgSrc = bg.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
        }
      }
      return { id: id, url: imgSrc };
    }

    function adminSetCampaignCover(id, url) {
      if (adminCoverId.length) { adminCoverId.val(id); }
      if (adminCoverImg.length && url) {
        adminCoverImg.attr('src', url);
        adminCoverPreview.removeClass('nonePoint').show();
      }
    }

    function adminHandleCoverUpload(file) {
      if (!file) { return; }
      const formData = new FormData();
      formData.append('f', 'upload');
      formData.append('uploading[]', file);
      $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        beforeSend: function() {
          if (adminCoverBtn.length) { adminCoverBtn.prop('disabled', true).addClass('loading'); }
        },
        success: function(response) {
          const parsed = adminExtractCoverFromHtml(response);
          if (parsed && parsed.id) {
            adminSetCampaignCover(parsed.id, parsed.url);
          } else {
            alert('Cover upload failed.');
          }
        },
        error: function() {
          alert('Cover upload failed.');
        },
        complete: function() {
          if (adminCoverBtn.length) { adminCoverBtn.prop('disabled', false).removeClass('loading'); }
          if (adminCoverInput.length) { adminCoverInput.val(''); }
        }
      });
    }

    if (adminCoverBtn.length) {
      adminCoverBtn.on('click', function(e) {
        e.preventDefault();
        if (adminCoverInput.length) {
          adminCoverInput.trigger('click');
        }
      });
    }
    if (adminCoverDrop.length) {
      adminCoverDrop.on('click', function() {
        if (adminCoverInput.length) {
          adminCoverInput.trigger('click');
        }
      });
    }
    if (adminCoverInput.length) {
      adminCoverInput.on('change', function() {
        const file = this.files && this.files[0] ? this.files[0] : null;
        adminHandleCoverUpload(file);
      });
    }
    if (adminCoverRemove.length) {
      adminCoverRemove.on('click', function(e) {
        e.preventDefault();
        if (adminCoverId.length) { adminCoverId.val(''); }
        if (adminCoverImg.length) { adminCoverImg.attr('src', ''); }
        adminCoverPreview.addClass('nonePoint').hide();
      });
    }
    // Validate max <= goal on admin campaign save
    const editPostForm = $('#editPostForm');
    const adminWarn = $('#adminCampaignWarn');
    if (editPostForm.length) {
      editPostForm.on('submit', function(e) {
        if (!adminWarn.length) { return; }
        const msgMaxGoal = editPostForm.data('max-goal-msg') || 'Maximum contribution cannot be greater than the goal.';
        const goalVal = parseFloat($('input[name=\"campaign_goal\"]').val());
        const maxVal = parseFloat($('input[name=\"campaign_max\"]').val());
        const goalMinLimit = parseFloat(editPostForm.data('goal-min')) || 0;
        const goalMaxLimit = parseFloat(editPostForm.data('goal-max')) || 0;
        const msgGoalMin = editPostForm.data('goal-min-msg') || 'Goal amount is below the minimum allowed.';
        const msgGoalMax = editPostForm.data('goal-max-msg') || 'Goal amount exceeds the maximum allowed.';

        if (goalVal && goalMinLimit && goalVal < goalMinLimit) {
          e.preventDefault(); e.stopImmediatePropagation();
          adminWarn.text(msgGoalMin).removeClass('nonePoint');
          $('html, body').animate({ scrollTop: adminWarn.offset().top - 80 }, 200);
          return false;
        }
        if (goalVal && goalMaxLimit && goalVal > goalMaxLimit) {
          e.preventDefault(); e.stopImmediatePropagation();
          adminWarn.text(msgGoalMax).removeClass('nonePoint');
          $('html, body').animate({ scrollTop: adminWarn.offset().top - 80 }, 200);
          return false;
        }
        if (goalVal && maxVal && maxVal > goalVal) {
          e.preventDefault();
          e.stopImmediatePropagation();
          adminWarn.text(msgMaxGoal).removeClass('nonePoint');
          $('html, body').animate({ scrollTop: adminWarn.offset().top - 80 }, 200);
          return false;
        }
        adminWarn.addClass('nonePoint').text('');
      });
    }
  });
})(jQuery);
