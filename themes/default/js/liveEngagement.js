(function($) {
  "use strict";

  function getCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || $("#poll_csrf_token").val() || '';
  }

  function updateLivePinnedProduct(html, pinnedId) {
    var $slot = $(".live_pinned_product_slot");
    if (!$slot.length) {
      return;
    }
    var currentId = $slot.attr("data-pinned-id") || '';
    var nextId = pinnedId ? pinnedId.toString() : '';
    if (currentId === nextId && html) {
      return;
    }
    $slot.attr("data-pinned-id", nextId);
    var $inner = $slot.find(".live_pinned_product_inner");
    if (!$inner.length) {
      $inner = $slot;
    }
    $inner.html(html || '');
    if (!nextId) {
      $slot.removeClass("is-open");
      $slot.find(".live_pinned_product_toggle").attr("aria-expanded", "false");
    }
  }

  window.updateLivePinnedProduct = updateLivePinnedProduct;

  function hasLivePollDraft($module) {
    var $createForm = $module.find(".live_poll_create");
    if (!$createForm.length) {
      return false;
    }
    if ($createForm.find(":focus").length) {
      return true;
    }
    var question = $.trim($createForm.find(".live_poll_question_input").val() || '');
    if (question.length) {
      return true;
    }
    var hasOptionValue = false;
    $createForm.find(".live_poll_option_input").each(function() {
      if ($.trim($(this).val() || '').length) {
        hasOptionValue = true;
        return false;
      }
    });
    return hasOptionValue;
  }

  function refreshLivePoll(forceRefresh) {
    var $module = $(".live_poll_module");
    if (!$module.length) {
      return;
    }
    if (!forceRefresh && hasLivePollDraft($module)) {
      return;
    }
    var liveId = $module.data("live-id");
    $.post(window.siteurl + "requests/request.php", {
      f: "livePollFetch",
      live_id: liveId,
      csrf_token: getCsrfToken()
    }, function(res) {
      if (res && res !== 'csrf' && res !== '404') {
        $module.replaceWith(res);
      }
    });
  }

  function refreshLiveQuestions() {
    var $module = $(".live_qa_module");
    if (!$module.length) {
      return;
    }
    var liveId = $module.data("live-id");
    $.post(window.siteurl + "requests/request.php", {
      f: "liveQuestionFetch",
      live_id: liveId,
      csrf_token: getCsrfToken()
    }, function(res) {
      if (res && res !== 'csrf' && res !== '404') {
        $module.replaceWith(res);
      }
    });
  }

  $(document).on("click", ".live_module_toggle", function() {
    $(this).closest(".live_module").toggleClass("is-open");
  });

  $(document).on("click", ".live_action_menu_btn", function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $holder = $(this).closest(".live_holder_plus_in");
    if (!$holder.length) {
      return;
    }
    var shouldOpen = !$holder.hasClass("live_actions_open");
    $holder.toggleClass("live_actions_open", shouldOpen);
    $(this).attr("aria-expanded", shouldOpen ? "true" : "false");
    if (!shouldOpen) {
      $holder.find(".live_interactions_toggle_btn").removeClass("is-active").attr("aria-expanded", "false");
      $(".live_right_in_right").removeClass("live_interactions_open");
    }
  });

  $(document).on("click touchstart", function(e) {
    var $target = $(e.target);
    if ($target.closest(".live_holder_plus_in").length || $target.closest(".live_right_in_right").length) {
      return;
    }
    $(".live_holder_plus_in").removeClass("live_actions_open");
    $(".live_action_menu_btn").attr("aria-expanded", "false");
  });

  $(document).on("click", ".live_interactions_toggle_btn", function() {
    var $panel = $(".live_right_in_right");
    if (!$panel.length) {
      return;
    }
    var shouldOpen = !$panel.hasClass("live_interactions_open");
    $panel.toggleClass("live_interactions_open", shouldOpen);
    $(".live_interactions_toggle_btn")
      .toggleClass("is-active", shouldOpen)
      .attr("aria-expanded", shouldOpen ? "true" : "false");
  });

  $(document).on("click", ".live_pin_product_btn", function() {
    var liveId = $(this).data("live-id");
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      data: {
        f: "livePinProductModal",
        live_id: liveId,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res !== '404' && res !== 'csrf') {
          $("body").append(res);
          setTimeout(function() {
            $(".i_modal_bg_in").addClass("i_modal_display_in");
          }, 150);
        }
      }
    });
  });

  $(document).on("click", ".live_pinned_product_toggle", function() {
    var $slot = $(this).closest(".live_pinned_product_slot");
    var pinnedId = $slot.attr("data-pinned-id") || '';
    if (!pinnedId) {
      return;
    }
    $slot.toggleClass("is-open");
    $(this).attr("aria-expanded", $slot.hasClass("is-open") ? "true" : "false");
  });

  $(document).on("click", ".live_pin_select", function() {
    var liveId = $(this).data("live-id");
    var productId = $(this).data("product-id");
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "livePinProduct",
        live_id: liveId,
        product_id: productId,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          updateLivePinnedProduct(res.pinned || '', res.pinnedId || '');
          $(".i_modal_bg_in").remove();
        }
      }
    });
  });

  $(document).on("click", ".live_unpin_product_btn", function() {
    var liveId = $(this).data("live-id");
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "liveUnpinProduct",
        live_id: liveId,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          updateLivePinnedProduct('', '');
        }
      }
    });
  });

  $(document).on("click", ".live_poll_add_option", function() {
    var $module = $(this).closest(".live_poll_module");
    var maxOptions = parseInt($module.data("max-options") || "6", 10);
    var $inputs = $module.find(".live_poll_option_input");
    if ($inputs.length >= maxOptions) {
      return;
    }
    $module.find(".live_poll_options_inputs").append(
      '<input type="text" class="live_poll_option_input" placeholder="' + ($module.data("option-placeholder") || "Option") + '">'
    );
  });

  $(document).on("click", ".live_poll_submit", function() {
    var $module = $(this).closest(".live_poll_module");
    var liveId = $(this).data("live-id");
    var question = $module.find(".live_poll_question_input").val() || '';
    var options = [];
    $module.find(".live_poll_option_input").each(function() {
      var val = $(this).val();
      if (val && $.trim(val).length) {
        options.push(val);
      }
    });
    var $warning = $module.find(".live_poll_warning");
    $warning.text("");
    var errorMap = {
      question_required: $module.data("error-question") || "Question required.",
      options_min: $module.data("error-options-min") || "Add at least two options.",
      options_max: $module.data("error-options-max") || "Too many options.",
      not_allowed: $module.data("error-not-allowed") || "Not allowed.",
      feature_disabled: $module.data("error-feature-disabled") || $module.data("error-not-allowed") || "Not allowed.",
      create_failed: $module.data("error-create-failed") || "Unable to create poll."
    };
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "livePollCreate",
        live_id: liveId,
        question: question,
        options: options,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          refreshLivePoll(true);
        } else if (res && res.status === 'error') {
          $warning.text(errorMap[res.error] || errorMap.create_failed);
        }
      }
    });
  });

  $(document).on("click", ".live_poll_option", function() {
    var $btn = $(this);
    if ($btn.hasClass("is-locked")) {
      return;
    }
    var pollId = $btn.data("poll-id");
    var optionId = $btn.data("option-id");
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "livePollVote",
        poll_id: pollId,
        option_id: optionId,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          refreshLivePoll(true);
        }
      }
    });
  });

  $(document).on("click", ".live_poll_close_btn", function() {
    var pollId = $(this).data("poll-id");
    var liveId = $(this).data("live-id");
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "livePollClose",
        poll_id: pollId,
        live_id: liveId,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          refreshLivePoll(true);
        }
      }
    });
  });

  $(document).on("click", ".live_qa_send", function() {
    var liveId = $(this).data("live-id");
    var $module = $(this).closest(".live_qa_module");
    var question = $module.find(".live_qa_textarea").val() || '';
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "liveQuestionAsk",
        live_id: liveId,
        question: question,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          $module.find(".live_qa_textarea").val('');
          refreshLiveQuestions();
        }
      }
    });
  });

  $(document).on("click", ".live_qa_action", function() {
    var liveId = $(this).data("live-id");
    var questionId = $(this).data("question-id");
    var status = $(this).data("status");
    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      dataType: "json",
      data: {
        f: "liveQuestionUpdate",
        live_id: liveId,
        question_id: questionId,
        status: status,
        csrf_token: getCsrfToken()
      },
      success: function(res) {
        if (res && res.status === '200') {
          refreshLiveQuestions();
        }
      }
    });
  });

  if (window.theLiveID) {
    setInterval(refreshLivePoll, 12000);
    setInterval(refreshLiveQuestions, 12000);
  }
})(jQuery);
