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

  function updateCountdown() {
    var startAt = parseInt(window.liveScheduledAt || "0", 10);
    if (!startAt) {
      return;
    }
    var now = Math.floor(Date.now() / 1000);
    var diff = startAt - now;
    var $value = $(".live_countdown_value");
    if (!$value.length) {
      return;
    }
    if (diff <= 0) {
      $value.text(window.LANG_LIVE_STARTS_SOON || "Starting soon");
    } else {
      $value.text(formatCountdown(diff));
    }
  }

  function checkLiveStatus() {
    var liveId = window.theLiveID;
    if (!liveId) {
      return;
    }
    $.post(window.siteurl + "requests/request.php", {
      f: "liveScheduleStatus",
      live_id: liveId,
      csrf_token: getCsrfToken()
    }, function(res) {
      if (!res || typeof res !== "object") {
        return;
      }
      if (res.status === 'live') {
        window.location.reload();
      }
    }, "json");
  }

  $(document).on("click", ".live_start_now_btn", function() {
    var liveId = $(this).data("live-id");
    $.post(window.siteurl + "requests/request.php", {
      f: "startScheduledLive",
      live_id: liveId,
      csrf_token: getCsrfToken()
    }, function(res) {
      if (res && res.status === '200' && res.start) {
        window.location.href = res.start;
      }
    }, "json");
  });

  $(document).on("click", ".live_reminder_btn", function() {
    var $btn = $(this);
    var liveId = $btn.data("live-id");
    var enabled = $btn.attr("data-enabled") === "1";
    $.post(window.siteurl + "requests/request.php", {
      f: "liveReminderToggle",
      live_id: liveId,
      enabled: enabled ? 0 : 1,
      csrf_token: getCsrfToken()
    }, function(res) {
      if (res && res.status === '200') {
        var nextEnabled = res.enabled === 1;
        $btn.attr("data-enabled", nextEnabled ? "1" : "0");
        $btn.toggleClass("is-active", nextEnabled);
        $btn.text(nextEnabled ? (window.LANG_LIVE_REMINDER_SET || "Reminder set") : (window.LANG_LIVE_REMIND_ME || "Remind me"));
      }
    }, "json");
  });

  updateCountdown();
  setInterval(updateCountdown, 1000);
  setInterval(checkLiveStatus, 12000);
})(jQuery);
