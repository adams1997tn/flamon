(function ($) {
  "use strict";

  const preLoadingAnimation = '<div class="i_loading"><div class="dot-pulse"></div></div>';
  const loaderHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
  const siteurl = window.siteurl || "";

  let coverCropper;
  let avatarCropper;

  function getCommunityId() {
    const modal = $(".i_modal_bg_in[data-community-id]").last();
    return parseInt(modal.data("community-id") || 0, 10);
  }

  function isValidImageType(fileType) {
    const validTypes = ["image/gif", "image/jpeg", "image/png", "image/svg+xml", "image/jpg", "image/webp"];
    return $.inArray(fileType, validTypes) >= 0;
  }

  function setBackground($el, url) {
    if (!$el || !$el.length || !url) {
      return;
    }
    $el.attr("data-bg", url);
    $el.css("background-image", "url(" + url + ")");
  }

  function updateCommunityCover(url) {
    setBackground($(".coverImageArea"), url);
    const $wrapper = $(".community_profile_wrapper");
    $wrapper.find(".i_profile_cover_blur")
      .attr("data-background", url)
      .css("background-image", "url(" + url + ")");
    $wrapper.find(".i_profile_cover .i_im_cover img").attr("src", url);
  }

  function updateCommunityAvatar(url) {
    setBackground($(".avatarImage"), url);
    const $wrapper = $(".community_profile_wrapper");
    $wrapper.find(".i_profile_avatar")
      .attr("data-avatar", url)
      .css("background-image", "url(" + url + ")");
  }

  function initCoverCropper() {
    const $container = $(".i_modal_cover_resize_bg_in .cropier_container").first();
    const containerWidth = $container.width() || 600;
    const viewportHeight = Math.round(containerWidth * 0.46);
    coverCropper = $("#community_cover_image").croppie({
      enableExif: true,
      enableOrientation: true,
      viewport: {
        width: containerWidth,
        height: viewportHeight,
        type: "square"
      },
      boundary: {
        width: containerWidth,
        height: viewportHeight
      }
    });
  }

  function initAvatarCropper() {
    avatarCropper = $("#community_avatar_image").croppie({
      enableExif: true,
      viewport: {
        width: 200,
        height: 200,
        type: "square"
      },
      boundary: {
        width: 200,
        height: 200
      }
    });
  }

  $(document).ready(function () {
    const $modal = $(".i_modal_bg_in[data-community-id]").last();
    if (typeof window.initImageBackgrounds === "function") {
      window.initImageBackgrounds(".coverImageArea, .avatarImage", $modal);
    }

    initCoverCropper();
    initAvatarCropper();

    $("body").on("change", "#community_cover", function () {
      const file = this.files[0];
      if (!file || !isValidImageType(file.type)) {
        return;
      }
      const reader = new FileReader();
      reader.onload = function (event) {
        coverCropper.croppie("bind", {
          url: event.target.result
        });
      };
      reader.readAsDataURL(file);
      $(".i_modal_cover_resize_bg_in").addClass("i_modal_display_in");
    });

    $("body").on("change", "#community_avatar", function () {
      const file = this.files[0];
      if (!file || !isValidImageType(file.type)) {
        return;
      }
      const reader = new FileReader();
      reader.onload = function (event) {
        avatarCropper.croppie("bind", {
          url: event.target.result
        });
      };
      reader.readAsDataURL(file);
      $(".i_modal_avatar_resize_bg_in").addClass("i_modal_display_in");
    });

    $("body").on("click", ".finishCrop", function () {
      const communityId = getCommunityId();
      if (!communityId) {
        return;
      }
      coverCropper.croppie("result", {
        type: "canvas",
        size: "original",
        circle: false
      }).then(function (response) {
        $(".i_modal_content").append(loaderHTML);
        $.post(siteurl + "requests/request.php", {
          image: response,
          f: "communityCoverUpload",
          community_id: communityId
        }, function (html) {
          $(".loaderWrapper").remove();
          if (!html || html === "404") {
            if (typeof PopUPAlerts === "function") {
              PopUPAlerts("sWrong", "ialert");
            }
            return;
          }
          updateCommunityCover(html);
          $(".i_modal_cover_resize_bg_in").addClass("i_modal_in_in_out");
          setTimeout(function () {
            $("#community_cover_image").croppie("bind");
            $(".i_modal_cover_resize_bg_in").removeClass("i_modal_display_in");
          }, 200);
        });
      });
    });

    $("body").on("click", ".finishACrop", function () {
      const communityId = getCommunityId();
      if (!communityId) {
        return;
      }
      avatarCropper.croppie("result", {
        type: "canvas",
        size: "original",
        circle: false
      }).then(function (response) {
        $(".i_modal_content").append(loaderHTML);
        $.post(siteurl + "requests/request.php", {
          image: response,
          f: "communityAvatarUpload",
          community_id: communityId
        }, function (html) {
          $(".loaderWrapper").remove();
          if (!html || html === "404") {
            if (typeof PopUPAlerts === "function") {
              PopUPAlerts("sWrong", "ialert");
            }
            return;
          }
          updateCommunityAvatar(html);
          $(".i_modal_avatar_resize_bg_in").addClass("i_modal_in_in_out");
          setTimeout(function () {
            $("#community_avatar_image").croppie("bind");
            $(".i_modal_avatar_resize_bg_in").removeClass("i_modal_display_in");
          }, 200);
        });
      });
    });

    $("body").on("click", ".cnclcrp", function () {
      $(".i_modal_cover_resize_bg_in, .i_modal_avatar_resize_bg_in").addClass("i_modal_in_in_out");
      setTimeout(function () {
        $(".i_modal_cover_resize_bg_in, .i_modal_avatar_resize_bg_in").removeClass("i_modal_display_in");
      }, 200);
    });

    $("body").on("click", ".coverCropClose", function () {
      $(".i_modal_cover_resize_bg_in, .i_modal_avatar_resize_bg_in").addClass("i_modal_in_in_out");
      setTimeout(function () {
        $(".i_modal_cover_resize_bg_in, .i_modal_avatar_resize_bg_in").removeClass("i_modal_display_in");
      }, 200);
    });
  });
})(jQuery);
