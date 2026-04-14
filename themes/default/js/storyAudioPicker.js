(function($) {
  "use strict";

  var previewAudio = new Audio();
  previewAudio.preload = "none";
  var currentPreviewUrl = "";
  var $currentItem = null;

  function setPreviewState($item, isPlaying) {
    if (!$item || !$item.length) {
      return;
    }
    var $button = $item.find(".story_audio_item_play");
    var playLabel = $button.data("play-label");
    var pauseLabel = $button.data("pause-label");
    $item.toggleClass("is-playing", isPlaying);
    $button.attr("aria-pressed", isPlaying ? "true" : "false");
    if (playLabel && pauseLabel) {
      $button.attr("aria-label", isPlaying ? pauseLabel : playLabel);
    }
  }

  function pausePreview() {
    if (!previewAudio.paused) {
      previewAudio.pause();
    }
    if ($currentItem && $currentItem.length) {
      setPreviewState($currentItem, false);
    }
  }

  function stopPreview(resetTime) {
    if (!previewAudio.paused) {
      previewAudio.pause();
    }
    if ($currentItem && $currentItem.length) {
      setPreviewState($currentItem, false);
    }
    if (resetTime) {
      previewAudio.currentTime = 0;
      $currentItem = null;
      currentPreviewUrl = "";
    }
  }

  previewAudio.addEventListener("ended", function() {
    if ($currentItem && $currentItem.length) {
      setPreviewState($currentItem, false);
    }
    previewAudio.currentTime = 0;
  });

  function loadAudios($list) {
    if ($list.data("loaded")) {
      $list.addClass("is-open");
      return;
    }
    $list.addClass("is-open is-loading");
    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      data: { f: "story_audios" },
      cache: false,
      success: function(response) {
        if (response) {
          $list.html(response).data("loaded", true);
        }
      },
      complete: function() {
        $list.removeClass("is-loading");
      }
    });
  }

  function buildLabel(title, artist) {
    var text = title || "";
    if (artist) {
      text = text ? text + " - " + artist : artist;
    }
    return text;
  }

  function updatePreview($group, audioId, audioTitle, audioArtist) {
    var $preview = $group.find(".story_audio_preview");
    var $label = $preview.find(".story_audio_selected");
    var $clear = $preview.find(".story_audio_clear");
    if (audioId) {
      $label.text(buildLabel(audioTitle, audioArtist));
      $preview.addClass("is-visible");
      $clear.addClass("is-visible");
    } else {
      $label.text("");
      $preview.removeClass("is-visible");
      $clear.removeClass("is-visible");
    }
  }

  $(document).on("click", ".story_audio_item_play", function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $item = $(this).closest(".story_audio_item");
    var audioUrl = $item.data("audio-url") || "";
    if (!audioUrl) {
      return;
    }
    if ($currentItem && $currentItem.length && $currentItem.get(0) !== $item.get(0)) {
      stopPreview(true);
    }
    $currentItem = $item;
    if (currentPreviewUrl !== audioUrl) {
      previewAudio.src = audioUrl;
      currentPreviewUrl = audioUrl;
    }
    if (previewAudio.paused) {
      var playPromise = previewAudio.play();
      if (playPromise && playPromise.catch) {
        playPromise.catch(function() {
          stopPreview(true);
        });
      }
      setPreviewState($item, true);
    } else {
      pausePreview();
    }
  });

  $(document).on("click", ".story_audio_trigger", function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $group = $(this).closest(".story_option_group");
    var $list = $group.find(".story_audio_list");
    if (!$list.length) {
      return;
    }
    if ($list.hasClass("is-open")) {
      $list.removeClass("is-open");
      return;
    }
    $(".story_audio_list").not($list).removeClass("is-open");
    loadAudios($list);
  });

  $(document).on("click", ".story_audio_item", function(e) {
    e.preventDefault();
    e.stopPropagation();
    stopPreview(true);
    var audioId = $(this).data("audio-id") || "";
    var audioTitle = $(this).data("audio-title") || "";
    var audioArtist = $(this).data("audio-artist") || "";
    var $group = $(this).closest(".story_option_group");
    var $input = $group.find(".story_overlay_audio").first();
    if ($input.length && audioId) {
      $input.val(audioId);
    }
    updatePreview($group, audioId, audioTitle, audioArtist);
    $group.find(".story_audio_list").removeClass("is-open");
  });

  $(document).on("click", ".story_audio_clear", function(e) {
    e.preventDefault();
    e.stopPropagation();
    stopPreview(true);
    var $group = $(this).closest(".story_option_group");
    var $input = $group.find(".story_overlay_audio").first();
    if ($input.length) {
      $input.val("");
    }
    updatePreview($group, "", "", "");
  });

  $(document).on("change", ".story_overlay_audio", function() {
    var $group = $(this).closest(".story_option_group");
    if (!$(this).val()) {
      updatePreview($group, "", "", "");
    }
  });

  $(document).on("click", function(e) {
    if (!$(e.target).closest(".story_option_group, .story_audio_trigger, .story_audio_list").length) {
      $(".story_audio_list").removeClass("is-open");
      stopPreview(true);
    }
  });

  $(document).on("keydown", ".story_audio_item", function(e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      $(this).trigger("click");
    }
  });
})(jQuery);
