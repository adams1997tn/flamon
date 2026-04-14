(function($) {
  "use strict";

  function getCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || $("#poll_csrf_token").val() || '';
  }

  function formatCountdown(seconds) {
    var s = Math.max(0, seconds);
    var h = Math.floor(s / 3600);
    var m = Math.floor((s % 3600) / 60);
    var sec = s % 60;
    return [h, m, sec].map(function(val) {
      return val < 10 ? "0" + val : "" + val;
    }).join(":");
  }

  function updateCountdowns() {
    var now = Math.floor(Date.now() / 1000);
    $(".scheduled_live_countdown").each(function() {
      var $countdown = $(this);
      var startAt = parseInt($countdown.data("start") || "0", 10);
      var $value = $countdown.find(".scheduled_live_countdown_value");
      if (!startAt || !$value.length) {
        return;
      }
      var diff = startAt - now;
      if (diff <= 0) {
        $value.text(window.LANG_LIVE_STARTS_SOON || "Starting soon");
      } else {
        $value.text(formatCountdown(diff));
      }
    });
  }

  function renderEmpty($container) {
    var emptyText = window.LANG_LIVE_SCHEDULED_EMPTY || "No scheduled live streams yet.";
    if ($container.find(".scheduled_live_empty").length) {
      return;
    }
    $container.append(
      '<div class="noPost scheduled_live_empty">' +
        '<div class="noPostNote">' + emptyText + '</div>' +
      '</div>'
    );
  }

  $(document).on("click", ".scheduled_live_delete_btn", function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $btn = $(this);
    var liveId = $btn.data("live-id");
    if (!liveId) {
      return;
    }
    var confirmText = $btn.data("confirm") || window.LANG_LIVE_SCHEDULED_DELETE_CONFIRM || "Delete this scheduled live?";
    if (!window.confirm(confirmText)) {
      return;
    }
    $.post(window.siteurl + "requests/request.php", {
      f: "deleteScheduledLive",
      live_id: liveId,
      csrf_token: getCsrfToken()
    }, function(res) {
      if (res && res.status === '200') {
        var $card = $btn.closest(".scheduled_live_card");
        var $container = $card.parent();
        $card.remove();
        if (!$container.find(".scheduled_live_card").length) {
          renderEmpty($container);
        }
      }
    }, "json");
  });

  updateCountdowns();
  setInterval(updateCountdowns, 1000);
})(jQuery);
