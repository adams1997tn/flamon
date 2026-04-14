(function($) {
  "use strict";

  function loadStickers($list) {
    if ($list.data("loaded")) {
      $list.addClass("is-open");
      return;
    }
    $list.addClass("is-open is-loading");
    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      data: { f: "story_stickers" },
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

  function updatePreview($group, stickerUrl, hasSticker) {
    const $preview = $group.find(".story_sticker_preview");
    const $img = $preview.find(".story_sticker_img");
    const $clear = $preview.find(".story_sticker_clear");
    if (hasSticker && stickerUrl) {
      $img.attr("src", stickerUrl);
      $preview.addClass("is-visible");
      $clear.addClass("is-visible");
    } else {
      $img.attr("src", "");
      $preview.removeClass("is-visible");
      $clear.removeClass("is-visible");
    }
  }

  $(document).on("click", ".story_sticker_trigger", function(e) {
    e.preventDefault();
    e.stopPropagation();
    const $group = $(this).closest(".story_option_group");
    const $list = $group.find(".story_sticker_list");
    if (!$list.length) {
      return;
    }
    if ($list.hasClass("is-open")) {
      $list.removeClass("is-open");
      return;
    }
    $(".story_sticker_list").not($list).removeClass("is-open");
    loadStickers($list);
  });

  $(document).on("click", ".story_sticker_item", function(e) {
    e.preventDefault();
    e.stopPropagation();
    const stickerId = $(this).data("sticker-id") || "";
    const stickerUrl = $(this).data("sticker-url") || "";
    const $group = $(this).closest(".story_option_group");
    const $input = $group.find(".story_overlay_sticker").first();
    if ($input.length && stickerId) {
      $input.val(stickerId);
    }
    updatePreview($group, stickerUrl, Boolean(stickerUrl));
    $group.find(".story_sticker_list").removeClass("is-open");
  });

  $(document).on("click", ".story_sticker_clear", function(e) {
    e.preventDefault();
    e.stopPropagation();
    const $group = $(this).closest(".story_option_group");
    const $input = $group.find(".story_overlay_sticker").first();
    if ($input.length) {
      $input.val("");
    }
    updatePreview($group, "", false);
  });

  $(document).on("change", ".story_overlay_sticker", function() {
    const $group = $(this).closest(".story_option_group");
    if (!$(this).val()) {
      updatePreview($group, "", false);
    }
  });

  $(document).on("click", function(e) {
    if (!$(e.target).closest(".story_option_group, .story_sticker_trigger, .story_sticker_list").length) {
      $(".story_sticker_list").removeClass("is-open");
    }
  });
})(jQuery);
