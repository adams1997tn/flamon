(function ($) {
    "use strict";

    function galleryNeedsVideoJs($gallery) {
        if (!$gallery || !$gallery.length) {
            return false;
        }
        if (window.dizzyVideoJsLoader && typeof window.dizzyVideoJsLoader.galleryNeedsVideoJs === "function") {
            return window.dizzyVideoJsLoader.galleryNeedsVideoJs($gallery.get(0));
        }
        return $gallery.find("video, .lg-html5, .video-js").length > 0;
    }

    function tryAutoPlayCurrentLgVideo() {
        const $video = $(".lg-outer .lg-current .lg-html5").first();
        if (!$video.length) {
            return;
        }
        const videoEl = $video.get(0);
        if (!videoEl || typeof videoEl.play !== "function") {
            return;
        }
        const playAttempt = videoEl.play();
        if (playAttempt && typeof playAttempt.catch === "function") {
            playAttempt.catch(function () {
                if (!videoEl.muted) {
                    videoEl.muted = true;
                    const mutedRetry = videoEl.play();
                    if (mutedRetry && typeof mutedRetry.catch === "function") {
                        mutedRetry.catch(function () {});
                    }
                }
            });
        }
    }

    function bindGalleryAutoPlayHooks($gallery) {
        if (!$gallery || !$gallery.length || $gallery.data("lgAutoPlayHooked")) {
            return;
        }
        $gallery.data("lgAutoPlayHooked", true);
        $gallery.on("onAfterOpen.lg.dizzyAutoplay onAferAppendSlide.lg.dizzyAutoplay onAfterSlide.lg.dizzyAutoplay", function () {
            window.setTimeout(tryAutoPlayCurrentLgVideo, 40);
        });
    }

    function initLightGallerySafe($gallery) {
        if (
            !$gallery ||
            !$gallery.length ||
            $gallery.hasClass("lg-initialized") ||
            $gallery.data("lgInitPending")
        ) {
            return;
        }
        bindGalleryAutoPlayHooks($gallery);

        const mount = function (forceNativePlayer) {
            $gallery.lightGallery({
                videojs: !forceNativePlayer && !!window.videojs,
                videojsOptions: {
                    controls: true,
                    autoplay: false,
                    preload: "auto",
                    fluid: true,
                    responsive: true,
                    controlBar: {
                        remainingTimeDisplay: false,
                        pictureInPictureToggle: true,
                        volumePanel: { inline: false }
                    }
                },
                mode: "lg-fade",
                cssEasing: "cubic-bezier(0.25, 0, 0.25, 1)",
                download: false,
                share: false
            });
        };

        if (
            galleryNeedsVideoJs($gallery) &&
            window.dizzyVideoJsLoader &&
            typeof window.dizzyVideoJsLoader.ensure === "function"
        ) {
            $gallery.data("lgInitPending", true);
            let mounted = false;
            const safeMount = function (forceNativePlayer) {
                if (mounted || $gallery.hasClass("lg-initialized")) {
                    $gallery.removeData("lgInitPending");
                    return;
                }
                mounted = true;
                mount(forceNativePlayer);
                $gallery.removeData("lgInitPending");
            };
            const fallbackTimer = window.setTimeout(function () {
                safeMount(true);
            }, 1200);
            window.dizzyVideoJsLoader.ensure().then(
                function () {
                    window.clearTimeout(fallbackTimer);
                    safeMount(false);
                },
                function () {
                    window.clearTimeout(fallbackTimer);
                    safeMount(true);
                }
            );
            return;
        }

        mount(false);
    }

    $(document).ready(function () {
        $('[id^="lightgallery"]').each(function () {
            initLightGallerySafe($(this));
        });
    });
    $('.i_post_image_swip_wrapper').each(function () {
        const $this = $(this);
        const bgUrl = $this.data('bg');
        if (bgUrl) {
            const img = new Image();
            img.onload = function () {
                $this.css('background-image', 'url("' + bgUrl + '")');
                $this.removeClass('image-skeleton');
            };
            img.src = bgUrl;
        }
    });
})(jQuery);
