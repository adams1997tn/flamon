(function ($) {
    "use strict";
    $(document).ready(function () {
        if ($('.commenta').length > 0 && $.fn.autoResize) {
          $('.commenta').autoResize();
        }

        if (typeof ClipboardJS !== "undefined") {
          new ClipboardJS('.copyUrl');
        }

        if ($('#newPostT').length > 0 && typeof $.fn.characterCounter === "function") {
          $("#newPostT").characterCounter({
            limit: typeof availableLength !== "undefined" ? availableLength : 250
          });
        }
    });
    document.addEventListener("DOMContentLoaded", function () {
      const mappings = [
        { selector: '.i_profile_cover_blur', attr: 'data-background' },
        { selector: '.i_profile_avatar', attr: 'data-avatar' }
      ];

      mappings.forEach(function (map) {
        document.querySelectorAll(map.selector).forEach(function (el) {
          const val = el.getAttribute(map.attr);
          if (val) {
            el.style.backgroundImage = 'url(' + val + ')';
          }
        });
      });
    });
    document.querySelectorAll('.hshCl[data-color]').forEach(function(el) {
      el.style.color = el.getAttribute('data-color');
    });
    const allowMediaPopup = typeof window.userIsLoggedIn === "boolean"
        ? window.userIsLoggedIn
        : false;
    const initializedGalleries = new Set();
    const lightGalleryOptions = {
        mode: 'lg-fade',
        cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
        download: false,
        share: false
    };
    const lightGalleryVideoJsOptions = {
        controls: true,
        autoplay: false,
        preload: 'auto',
        fluid: true,
        responsive: true,
        controlBar: {
            remainingTimeDisplay: false,
            pictureInPictureToggle: true,
            volumePanel: { inline: false }
        }
    };

    function galleryNeedsVideoJs($gallery) {
        if (!$gallery || !$gallery.length) {
            return false;
        }
        if (window.dizzyVideoJsLoader && typeof window.dizzyVideoJsLoader.galleryNeedsVideoJs === 'function') {
            return window.dizzyVideoJsLoader.galleryNeedsVideoJs($gallery.get(0));
        }
        return $gallery.find('video, .lg-html5, .video-js').length > 0;
    }

    function tryAutoPlayCurrentLgVideo() {
        const $video = $('.lg-outer .lg-current .lg-html5').first();
        if (!$video.length) {
            return;
        }
        const videoEl = $video.get(0);
        if (!videoEl || typeof videoEl.play !== 'function') {
            return;
        }
        const playAttempt = videoEl.play();
        if (playAttempt && typeof playAttempt.catch === 'function') {
            playAttempt.catch(function () {
                if (!videoEl.muted) {
                    videoEl.muted = true;
                    const mutedRetry = videoEl.play();
                    if (mutedRetry && typeof mutedRetry.catch === 'function') {
                        mutedRetry.catch(function () {});
                    }
                }
            });
        }
    }

    function bindGalleryAutoPlayHooks($gallery) {
        if (!$gallery || !$gallery.length || $gallery.data('lgAutoPlayHooked')) {
            return;
        }
        $gallery.data('lgAutoPlayHooked', true);
        $gallery.on('onAfterOpen.lg.dizzyAutoplay onAferAppendSlide.lg.dizzyAutoplay onAfterSlide.lg.dizzyAutoplay', function () {
            window.setTimeout(tryAutoPlayCurrentLgVideo, 40);
        });
    }

    function initLightGallerySafe($gallery, options) {
        if (
            !$gallery ||
            !$gallery.length ||
            $gallery.hasClass('lg-initialized') ||
            $gallery.data('lgInitPending')
        ) {
            return;
        }
        bindGalleryAutoPlayHooks($gallery);

        const mountGallery = function (forceNativePlayer) {
            const config = $.extend({}, options, {
                videojs: !forceNativePlayer && !!window.videojs,
                videojsOptions: lightGalleryVideoJsOptions
            });
            $gallery.lightGallery(config);
        };

        if (
            galleryNeedsVideoJs($gallery) &&
            window.dizzyVideoJsLoader &&
            typeof window.dizzyVideoJsLoader.ensure === 'function'
        ) {
            $gallery.data('lgInitPending', true);
            let mounted = false;
            const safeMount = function (forceNativePlayer) {
                if (mounted || $gallery.hasClass('lg-initialized')) {
                    $gallery.removeData('lgInitPending');
                    return;
                }
                mounted = true;
                mountGallery(forceNativePlayer);
                $gallery.removeData('lgInitPending');
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

        mountGallery(false);
    }

    function syncPlayerAccentToken() {
        if (!window.getComputedStyle) {
            return;
        }

        const nodes = document.querySelectorAll('.publish_btn, .alertBtnRight, .send_tip_btn, .form_btn');
        if (!nodes || !nodes.length) {
            return;
        }

        const isUsableColor = function (colorValue) {
            if (!colorValue) {
                return false;
            }
            const normalized = String(colorValue).trim().toLowerCase();
            if (!normalized || normalized === 'transparent') {
                return false;
            }
            const rgbaMatch = normalized.match(/^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*([0-9.]+)\s*\)$/);
            if (rgbaMatch && parseFloat(rgbaMatch[1]) <= 0.05) {
                return false;
            }
            return true;
        };

        let pickedColor = '';
        nodes.forEach(function (node) {
            if (pickedColor) {
                return;
            }
            const computed = window.getComputedStyle(node);
            const bg = computed ? computed.backgroundColor : '';
            if (isUsableColor(bg)) {
                pickedColor = bg;
            }
        });

        if (pickedColor) {
            document.documentElement.style.setProperty('--dizzy-player-accent', pickedColor);
        }
    }

    function normalizeInlineVideoSelector(rawSelector) {
        if (typeof rawSelector !== 'string') {
            return '';
        }
        let selector = rawSelector.trim();
        if (!selector) {
            return '';
        }
        if (selector.indexOf('&') !== -1 && typeof document !== 'undefined') {
            const decoder = document.createElement('textarea');
            decoder.innerHTML = selector;
            selector = (decoder.value || selector).trim();
        }
        selector = selector.replace(/^['"]+|['"]+$/g, '').trim();
        return selector;
    }

    function getSingleInlineVideoContext($playButton) {
        if (!$playButton || !$playButton.length) {
            return null;
        }
        const $wrapper = $playButton.closest('.i_post_u_images .i_post_image_swip_wrapper[data-html]');
        if (
            !$wrapper.length ||
            $wrapper.hasClass('inline_video_mode') ||
            $wrapper.attr('data-inline-video-active') === '1'
        ) {
            return null;
        }
        const videoSelector = normalizeInlineVideoSelector($wrapper.attr('data-html') || '');
        if (!videoSelector || videoSelector.charAt(0) !== '#') {
            return null;
        }
        const $gallery = $wrapper.parent('[id^="lightgallery"]');
        if (!$gallery.length) {
            return null;
        }
        const $galleryItems = $gallery.children('.i_post_image_swip_wrapper');
        const $realGalleryItems = $galleryItems.filter(function () {
            return !$(this).hasClass('swiper-slide-duplicate');
        });
        const itemCount = ($realGalleryItems.length || $galleryItems.length);
        if (itemCount !== 1) {
            return null;
        }
        return {
            $wrapper: $wrapper,
            videoSelector: videoSelector
        };
    }

    function createInlineVideoFromTemplate(videoSelector, posterUrl) {
        const $videoTemplate = $(videoSelector).first().find('video').first();
        if (!$videoTemplate.length) {
            return null;
        }

        const $inlineVideo = $('<video class="i_inline_video_player video-js vjs-default-skin dizzy-pro-video" controls preload="metadata" playsinline webkit-playsinline></video>');
        if (posterUrl) {
            $inlineVideo.attr('poster', posterUrl);
        }

        const $sources = $videoTemplate.find('source');
        if ($sources.length) {
            $sources.each(function () {
                const src = $(this).attr('src');
                if (!src) {
                    return;
                }
                const $source = $('<source>');
                $source.attr('src', src);
                const type = $(this).attr('type');
                if (type) {
                    $source.attr('type', type);
                }
                $inlineVideo.append($source);
            });
        } else {
            const src = $videoTemplate.attr('src');
            if (!src) {
                return null;
            }
            $inlineVideo.attr('src', src);
        }

        return $inlineVideo;
    }

    function autoPlayMediaElement(videoEl) {
        if (!videoEl || typeof videoEl.play !== 'function') {
            return;
        }
        const playAttempt = videoEl.play();
        if (playAttempt && typeof playAttempt.catch === 'function') {
            playAttempt.catch(function () {
                if (!videoEl.muted) {
                    videoEl.muted = true;
                    const mutedRetry = videoEl.play();
                    if (mutedRetry && typeof mutedRetry.catch === 'function') {
                        mutedRetry.catch(function () {});
                    }
                }
            });
        }
    }

    function pauseMediaElement(videoEl, reason) {
        if (!videoEl || typeof videoEl.pause !== 'function') {
            return;
        }
        if (reason === 'out_of_view' || reason === 'other_video_started' || reason === 'tab_hidden') {
            videoEl.__dizzyAutoResumeOnVisible = '1';
        } else if (reason === 'manual') {
            videoEl.__dizzyAutoResumeOnVisible = '0';
        }
        videoEl.__dizzyPauseReason = reason || '';
        try {
            videoEl.pause();
        } catch (e) {}
    }

    function resolveInlinePlaybackVideo(videoEl) {
        if (!videoEl || typeof videoEl.closest !== 'function') {
            return null;
        }
        if (videoEl.matches && videoEl.matches('video.i_inline_video_player')) {
            return videoEl;
        }
        const $wrapper = videoEl.closest('.i_post_image_swip_wrapper.inline_video_mode');
        if (!$wrapper) {
            return null;
        }
        return $wrapper.querySelector('video.i_inline_video_player') || $wrapper.querySelector('video');
    }

    function getInlinePlaybackVideos() {
        return Array.prototype.slice.call(
            document.querySelectorAll('.i_post_image_swip_wrapper.inline_video_mode video')
        );
    }

    function getInlineGlobalMutedState() {
        if (typeof window.__dizzyInlineGlobalMuted !== 'boolean') {
            window.__dizzyInlineGlobalMuted = false;
        }
        return window.__dizzyInlineGlobalMuted;
    }

    function getInlineAudioLabels() {
        const lang = window.storyLang || {};
        const muteLabel = (typeof lang.audioMute === 'string') ? lang.audioMute.trim() : '';
        const unmuteLabel = (typeof lang.audioUnmute === 'string') ? lang.audioUnmute.trim() : '';
        return {
            mute: muteLabel || unmuteLabel || '',
            unmute: unmuteLabel || muteLabel || ''
        };
    }

    function getInlinePlayLabels() {
        const lang = window.storyLang || {};
        const playLabel = (typeof lang.audioPlay === 'string') ? lang.audioPlay.trim() : '';
        const pauseLabel = (typeof lang.audioPause === 'string') ? lang.audioPause.trim() : '';
        return {
            play: playLabel || pauseLabel || '',
            pause: pauseLabel || playLabel || ''
        };
    }

    function getInlineExpandLabels() {
        const lang = window.storyLang || {};
        const expandLabel = (typeof lang.videoExpand === 'string') ? lang.videoExpand.trim() : '';
        const closeLabel = (typeof lang.videoClose === 'string') ? lang.videoClose.trim() : '';
        return {
            expand: expandLabel || closeLabel || '',
            close: closeLabel || expandLabel || ''
        };
    }

    function getInlineExpandToggleIcon() {
        const lang = window.storyLang || {};
        const iconMarkup = (typeof lang.videoExpandIcon === 'string') ? lang.videoExpandIcon.trim() : '';
        if (iconMarkup) {
            return iconMarkup;
        }
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9V3h6v2H5v4H3Zm16-4h-4V3h6v6h-2V5ZM5 15v4h4v2H3v-6h2Zm16 0h2v6h-6v-2h4v-4Z"></path></svg>';
    }

    function getInlineAudioToggleIcon(isMuted) {
        if (isMuted) {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3.2v17.6c0 .8-.9 1.2-1.5.7L7.8 17H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h3.8l4.7-4.5c.6-.5 1.5-.1 1.5.7ZM17.3 9.3a1 1 0 0 1 1.4 0L20 10.6l1.3-1.3a1 1 0 1 1 1.4 1.4L21.4 12l1.3 1.3a1 1 0 0 1-1.4 1.4L20 13.4l-1.3 1.3a1 1 0 0 1-1.4-1.4l1.3-1.3-1.3-1.3a1 1 0 0 1 0-1.4Z"></path></svg>';
        }
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3.2v17.6c0 .8-.9 1.2-1.5.7L7.8 17H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h3.8l4.7-4.5c.6-.5 1.5-.1 1.5.7Zm3.8 2.4a1 1 0 0 1 1.4 0A9 9 0 0 1 22 12a9 9 0 0 1-2.8 6.4 1 1 0 1 1-1.4-1.4A7 7 0 0 0 20 12a7 7 0 0 0-2.2-5 1 1 0 0 1 0-1.4Zm-3 3a1 1 0 0 1 1.4 0A4.9 4.9 0 0 1 17.8 12a4.9 4.9 0 0 1-1.6 3.4 1 1 0 1 1-1.4-1.4 2.9 2.9 0 0 0 1-2 2.9 2.9 0 0 0-1-2 1 1 0 0 1 0-1.4Z"></path></svg>';
    }

    function syncInlineAudioToggleButton(buttonEl, isMuted) {
        if (!buttonEl) {
            return;
        }
        const labels = getInlineAudioLabels();
        const muted = !!isMuted;
        const nextLabel = muted ? labels.unmute : labels.mute;
        buttonEl.setAttribute('data-muted', muted ? '1' : '0');
        if (nextLabel) {
            buttonEl.setAttribute('aria-label', nextLabel);
            buttonEl.setAttribute('title', nextLabel);
        } else {
            buttonEl.removeAttribute('aria-label');
            buttonEl.removeAttribute('title');
        }
        buttonEl.innerHTML = getInlineAudioToggleIcon(muted);
    }

    function applyInlineVideoMutedState(videoEl, isMuted) {
        if (!videoEl) {
            return;
        }
        const muted = !!isMuted;
        videoEl.muted = muted;
        videoEl.defaultMuted = muted;
        const player = videoEl.__dizzyVjsPlayer;
        if (player && typeof player.muted === 'function') {
            try {
                player.muted(muted);
            } catch (e) {}
        }
    }

    function setInlineGlobalMutedState(isMuted) {
        const muted = !!isMuted;
        window.__dizzyInlineGlobalMuted = muted;
        getInlinePlaybackVideos().forEach(function (videoNode) {
            applyInlineVideoMutedState(videoNode, muted);
        });
        document.querySelectorAll('.i_inline_audio_toggle').forEach(function (buttonNode) {
            syncInlineAudioToggleButton(buttonNode, muted);
        });
    }

    function syncInlineExpandToggleButton(buttonEl, isExpanded) {
        if (!buttonEl) {
            return;
        }
        const labels = getInlineExpandLabels();
        const expanded = !!isExpanded;
        const label = expanded ? labels.close : labels.expand;
        buttonEl.setAttribute('data-expanded', expanded ? '1' : '0');
        if (label) {
            buttonEl.setAttribute('aria-label', label);
            buttonEl.setAttribute('title', label);
        } else {
            buttonEl.removeAttribute('aria-label');
            buttonEl.removeAttribute('title');
        }
        buttonEl.innerHTML = getInlineExpandToggleIcon();
    }

    function refreshInlineExpandedBodyLock() {
        const hasExpanded = !!document.querySelector('.i_inline_video_shell.is-inline-expanded');
        if (hasExpanded) {
            document.documentElement.classList.add('i_inline_video_modal_open');
            document.body.classList.add('i_inline_video_modal_open');
        } else {
            document.documentElement.classList.remove('i_inline_video_modal_open');
            document.body.classList.remove('i_inline_video_modal_open');
        }
    }

    function getInlineNativeFullscreenElement() {
        return document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement ||
            null;
    }

    function isInlineShellNativeFullscreen(shellEl) {
        if (!shellEl || !shellEl.classList || !shellEl.classList.contains('i_inline_video_shell')) {
            return false;
        }
        const fullscreenEl = getInlineNativeFullscreenElement();
        if (!fullscreenEl) {
            return false;
        }
        return fullscreenEl === shellEl || shellEl.contains(fullscreenEl);
    }

    function requestInlineNativeFullscreen(targetEl) {
        if (!targetEl) {
            return false;
        }
        const requestFullscreen = targetEl.requestFullscreen ||
            targetEl.webkitRequestFullscreen ||
            targetEl.webkitEnterFullscreen ||
            targetEl.mozRequestFullScreen ||
            targetEl.msRequestFullscreen;
        if (typeof requestFullscreen !== 'function') {
            return false;
        }
        try {
            requestFullscreen.call(targetEl);
            return true;
        } catch (e) {
            return false;
        }
    }

    function exitInlineNativeFullscreen() {
        const exitFullscreen = document.exitFullscreen ||
            document.webkitExitFullscreen ||
            document.webkitCancelFullScreen ||
            document.mozCancelFullScreen ||
            document.msExitFullscreen;
        if (typeof exitFullscreen !== 'function') {
            return false;
        }
        try {
            exitFullscreen.call(document);
            return true;
        } catch (e) {
            return false;
        }
    }

    function refreshInlineExpandButtons() {
        document.querySelectorAll('.i_inline_video_shell').forEach(function (shellNode) {
            const buttonNode = shellNode.querySelector('.i_inline_expand_toggle');
            if (!buttonNode) {
                return;
            }
            const isExpanded = shellNode.classList.contains('is-inline-expanded') || isInlineShellNativeFullscreen(shellNode);
            syncInlineExpandToggleButton(buttonNode, isExpanded);
        });
    }

    function isInlineControlsStickyEnabled() {
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        if (viewportWidth < 992) {
            return false;
        }
        if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
            return false;
        }
        return true;
    }

    function clearInlineControlsSticky(shellEl) {
        if (!shellEl) {
            return;
        }
        shellEl.classList.remove('i_inline_controls_fixed');
        shellEl.style.removeProperty('--i-inline-controls-fixed-top');
        shellEl.style.removeProperty('--i-inline-audio-fixed-top');
        shellEl.style.removeProperty('--i-inline-audio-fixed-left');
        shellEl.style.removeProperty('--i-inline-expand-fixed-top');
        shellEl.style.removeProperty('--i-inline-expand-fixed-left');
    }

    function updateInlineStickyControls() {
        const shellNodes = document.querySelectorAll('.i_post_image_swip_wrapper.inline_video_mode .i_inline_video_shell');
        if (!shellNodes.length) {
            return;
        }

        const stickyEnabled = isInlineControlsStickyEnabled();
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const clampValue = function (value, min, max) {
            return Math.min(Math.max(value, min), max);
        };
        let stickyTop = 90;
        const headerNode = document.querySelector('.header');
        if (headerNode && typeof headerNode.getBoundingClientRect === 'function') {
            const headerRect = headerNode.getBoundingClientRect();
            stickyTop = Math.max(12, Math.round((headerRect.bottom || 0) + 8));
        }

        shellNodes.forEach(function (shellEl) {
            if (
                !stickyEnabled ||
                !shellEl ||
                shellEl.classList.contains('is-inline-expanded') ||
                isInlineShellNativeFullscreen(shellEl)
            ) {
                clearInlineControlsSticky(shellEl);
                return;
            }

            const wrapper = shellEl.closest('.i_post_image_swip_wrapper.inline_video_mode');
            if (!wrapper || typeof wrapper.getBoundingClientRect !== 'function') {
                clearInlineControlsSticky(shellEl);
                return;
            }

            const rect = wrapper.getBoundingClientRect();
            const inViewport = (
                rect.bottom > 0 &&
                rect.top < viewportHeight &&
                rect.right > 0 &&
                rect.left < viewportWidth
            );
            const controlsHeight = 96;
            const shouldFix = inViewport && rect.top < stickyTop && (rect.bottom - 12) > (stickyTop + controlsHeight);
            if (!shouldFix) {
                clearInlineControlsSticky(shellEl);
                return;
            }

            const alreadyFixed = shellEl.classList.contains('i_inline_controls_fixed');
            if (!alreadyFixed) {
                const audioButton = shellEl.querySelector('.i_inline_audio_toggle');
                const expandButton = shellEl.querySelector('.i_inline_expand_toggle');
                if (audioButton && typeof audioButton.getBoundingClientRect === 'function') {
                    const audioRect = audioButton.getBoundingClientRect();
                    const minTop = Math.max(12, stickyTop);
                    const maxTop = Math.max(minTop, viewportHeight - controlsHeight);
                    const maxLeft = Math.max(0, viewportWidth - 52);
                    const audioFixedTop = clampValue(Math.round(audioRect.top), minTop, maxTop);
                    const audioFixedLeft = clampValue(Math.round(audioRect.left), 0, maxLeft);
                    shellEl.style.setProperty('--i-inline-controls-fixed-top', audioFixedTop + 'px');
                    shellEl.style.setProperty('--i-inline-audio-fixed-top', audioFixedTop + 'px');
                    shellEl.style.setProperty('--i-inline-audio-fixed-left', audioFixedLeft + 'px');

                    if (expandButton && typeof expandButton.getBoundingClientRect === 'function') {
                        const expandRect = expandButton.getBoundingClientRect();
                        const expandFixedTop = clampValue(Math.round(expandRect.top), (minTop + 46), (maxTop + 46));
                        const expandFixedLeft = clampValue(Math.round(expandRect.left), 0, maxLeft);
                        shellEl.style.setProperty('--i-inline-expand-fixed-top', expandFixedTop + 'px');
                        shellEl.style.setProperty('--i-inline-expand-fixed-left', expandFixedLeft + 'px');
                    } else {
                        shellEl.style.setProperty('--i-inline-expand-fixed-top', (audioFixedTop + 46) + 'px');
                        shellEl.style.setProperty('--i-inline-expand-fixed-left', audioFixedLeft + 'px');
                    }
                } else {
                    const fallbackLeft = Math.max(0, Math.round(rect.right - 52));
                    shellEl.style.setProperty('--i-inline-controls-fixed-top', stickyTop + 'px');
                    shellEl.style.setProperty('--i-inline-audio-fixed-top', stickyTop + 'px');
                    shellEl.style.setProperty('--i-inline-audio-fixed-left', fallbackLeft + 'px');
                    shellEl.style.setProperty('--i-inline-expand-fixed-top', (stickyTop + 46) + 'px');
                    shellEl.style.setProperty('--i-inline-expand-fixed-left', fallbackLeft + 'px');
                }
            }
            shellEl.classList.add('i_inline_controls_fixed');
        });
    }

    function setInlineShellExpandedState(shellEl, shouldExpand) {
        const expand = !!shouldExpand;
        const hasTargetShell = !!(
            shellEl &&
            shellEl.classList &&
            shellEl.classList.contains('i_inline_video_shell')
        );
        document.querySelectorAll('.i_inline_video_shell.is-inline-expanded').forEach(function (shellNode) {
            if (hasTargetShell && shellNode === shellEl && expand) {
                return;
            }
            shellNode.classList.remove('is-inline-expanded');
            const buttonNode = shellNode.querySelector('.i_inline_expand_toggle');
            if (buttonNode) {
                syncInlineExpandToggleButton(buttonNode, false);
            }
        });
        if (!hasTargetShell) {
            refreshInlineExpandedBodyLock();
            updateInlineStickyControls();
            return;
        }
        shellEl.classList.toggle('is-inline-expanded', expand);
        const toggleButton = shellEl.querySelector('.i_inline_expand_toggle');
        if (toggleButton) {
            syncInlineExpandToggleButton(toggleButton, expand);
        }
        refreshInlineExpandedBodyLock();
        updateInlineStickyControls();
    }

    function pauseOtherInlineVideos(activeVideoEl) {
        const activeVideo = resolveInlinePlaybackVideo(activeVideoEl) || activeVideoEl;
        if (!activeVideo) {
            return;
        }
        const activeWrapper = activeVideo.closest
            ? activeVideo.closest('.i_post_image_swip_wrapper.inline_video_mode')
            : null;
        getInlinePlaybackVideos().forEach(function (videoNode) {
            const wrapper = videoNode && videoNode.closest
                ? videoNode.closest('.i_post_image_swip_wrapper.inline_video_mode')
                : null;
            if (activeWrapper && wrapper && wrapper === activeWrapper) {
                return;
            }
            pauseMediaElement(videoNode, 'other_video_started');
            const syncTarget = resolveInlinePlaybackVideo(videoNode) || videoNode;
            if (typeof syncTarget.__dizzyInlineSyncOverlay === 'function') {
                syncTarget.__dizzyInlineSyncOverlay();
            }
        });
    }

    function hasOtherPlayingInlineVideos(activeVideoEl) {
        const activeVideo = resolveInlinePlaybackVideo(activeVideoEl) || activeVideoEl;
        if (!activeVideo) {
            return false;
        }
        const activeWrapper = activeVideo.closest
            ? activeVideo.closest('.i_post_image_swip_wrapper.inline_video_mode')
            : null;
        return getInlinePlaybackVideos().some(function (videoNode) {
            if (!videoNode || videoNode.paused || videoNode.ended) {
                return false;
            }
            const wrapper = videoNode.closest
                ? videoNode.closest('.i_post_image_swip_wrapper.inline_video_mode')
                : null;
            if (activeWrapper && wrapper && wrapper === activeWrapper) {
                return false;
            }
            return true;
        });
    }

    function isInlineVideoVisibleEnough(videoEl, minRatio) {
        if (!videoEl || typeof videoEl.getBoundingClientRect !== 'function') {
            return true;
        }
        const rect = videoEl.getBoundingClientRect();
        const width = Number(rect.width) || Math.max(0, Number(rect.right) - Number(rect.left));
        const height = Number(rect.height) || Math.max(0, Number(rect.bottom) - Number(rect.top));
        if (!width || !height) {
            return false;
        }
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const visibleWidth = Math.max(0, Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0));
        const visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
        const visibleArea = visibleWidth * visibleHeight;
        const totalArea = width * height;
        if (!totalArea) {
            return false;
        }
        const ratio = visibleArea / totalArea;
        return ratio >= (Number(minRatio) || 0.35);
    }

    function getInlineVideoVisibleRatio(videoEl) {
        if (!videoEl || typeof videoEl.getBoundingClientRect !== 'function') {
            return 0;
        }
        const rect = videoEl.getBoundingClientRect();
        const width = Number(rect.width) || Math.max(0, Number(rect.right) - Number(rect.left));
        const height = Number(rect.height) || Math.max(0, Number(rect.bottom) - Number(rect.top));
        if (!width || !height) {
            return 0;
        }
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const visibleWidth = Math.max(0, Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0));
        const visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
        const visibleArea = visibleWidth * visibleHeight;
        const totalArea = width * height;
        if (!totalArea) {
            return 0;
        }
        return visibleArea / totalArea;
    }

    function pickPrimaryVisibleInlineVideo(videos) {
        if (!videos || !videos.length) {
            return null;
        }
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const viewportCenterY = viewportHeight / 2;
        let bestVideo = null;
        let bestRatio = 0;
        let bestCenterDistance = Number.POSITIVE_INFINITY;
        videos.forEach(function (videoNode) {
            const ratio = getInlineVideoVisibleRatio(videoNode);
            if (ratio <= 0) {
                return;
            }
            const rect = videoNode.getBoundingClientRect();
            const centerY = (rect.top + rect.bottom) / 2;
            const centerDistance = Math.abs(centerY - viewportCenterY);
            if (
                ratio > bestRatio + 0.01 ||
                (Math.abs(ratio - bestRatio) <= 0.01 && centerDistance < bestCenterDistance)
            ) {
                bestVideo = videoNode;
                bestRatio = ratio;
                bestCenterDistance = centerDistance;
            }
        });
        if (!bestVideo || bestRatio < 0.15) {
            return null;
        }
        return bestVideo;
    }

    function pauseOutOfViewInlineVideos() {
        const videos = getInlinePlaybackVideos().filter(function (videoNode) {
            return !!videoNode && !videoNode.ended;
        });
        if (!videos.length) {
            return;
        }

        const primaryVideo = pickPrimaryVisibleInlineVideo(videos);
        videos.forEach(function (videoNode) {
            if (primaryVideo && videoNode === primaryVideo) {
                return;
            }
            if (!videoNode.paused) {
                pauseMediaElement(videoNode, 'out_of_view');
            }
        });

        if (
            primaryVideo &&
            primaryVideo.paused &&
            primaryVideo.__dizzyAutoResumeOnVisible === '1'
        ) {
            applyInlineVideoMutedState(primaryVideo, getInlineGlobalMutedState());
            autoPlayMediaElement(primaryVideo);
        }

        videos.forEach(function (videoNode) {
            const syncTarget = resolveInlinePlaybackVideo(videoNode) || videoNode;
            if (typeof syncTarget.__dizzyInlineSyncOverlay === 'function') {
                syncTarget.__dizzyInlineSyncOverlay();
            }
        });
    }

    function bindInlineVideoGuards() {
        if (window.__dizzyInlineVideoGuardsBound) {
            return;
        }
        window.__dizzyInlineVideoGuardsBound = true;
        let scheduled = false;
        const scheduleVisibilityCheck = function () {
            if (scheduled) {
                return;
            }
            scheduled = true;
            const run = function () {
                scheduled = false;
                pauseOutOfViewInlineVideos();
                updateInlineStickyControls();
            };
            if (typeof window.requestAnimationFrame === 'function') {
                window.requestAnimationFrame(run);
            } else {
                window.setTimeout(run, 16);
            }
        };
        document.addEventListener('play', function (event) {
            const activeVideo = resolveInlinePlaybackVideo(event && event.target);
            if (!activeVideo) {
                return;
            }
            pauseOtherInlineVideos(activeVideo);
            if (typeof activeVideo.__dizzyInlineSyncOverlay === 'function') {
                activeVideo.__dizzyInlineSyncOverlay();
            }
        }, true);
        window.addEventListener('scroll', scheduleVisibilityCheck, { passive: true });
        window.addEventListener('resize', scheduleVisibilityCheck, { passive: true });
        window.addEventListener('orientationchange', scheduleVisibilityCheck);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                getInlinePlaybackVideos().forEach(function (videoNode) {
                    if (!videoNode || videoNode.paused || videoNode.ended) {
                        return;
                    }
                    pauseMediaElement(videoNode, 'tab_hidden');
                    const syncTarget = resolveInlinePlaybackVideo(videoNode) || videoNode;
                    if (typeof syncTarget.__dizzyInlineSyncOverlay === 'function') {
                        syncTarget.__dizzyInlineSyncOverlay();
                    }
                });
            } else {
                scheduleVisibilityCheck();
            }
        });
        document.addEventListener('keydown', function (event) {
            const key = event && (event.key || event.code || '');
            if (key !== 'Escape' && key !== 'Esc') {
                return;
            }
            const hasInlineExpanded = !!document.querySelector('.i_inline_video_shell.is-inline-expanded');
            const nativeFullscreenEl = getInlineNativeFullscreenElement();
            const isInlineNativeFullscreen = !!(
                nativeFullscreenEl &&
                typeof nativeFullscreenEl.closest === 'function' &&
                nativeFullscreenEl.closest('.i_inline_video_shell')
            );
            if (!hasInlineExpanded && !isInlineNativeFullscreen) {
                return;
            }
            exitInlineNativeFullscreen();
            setInlineShellExpandedState(null, false);
        });
        document.addEventListener('fullscreenchange', refreshInlineExpandButtons);
        document.addEventListener('webkitfullscreenchange', refreshInlineExpandButtons);
        document.addEventListener('fullscreenchange', updateInlineStickyControls);
        document.addEventListener('webkitfullscreenchange', updateInlineStickyControls);
    }

    function ensureInlineVideoVisibilityObserver() {
        if (window.__dizzyInlineVideoVisibilityObserver) {
            return window.__dizzyInlineVideoVisibilityObserver;
        }
        if (typeof window.IntersectionObserver !== 'function') {
            return null;
        }
        window.__dizzyInlineVideoVisibilityObserver = new window.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                const targetVideo = entry && entry.target;
                if (!targetVideo || !targetVideo.classList || !targetVideo.classList.contains('i_inline_video_player')) {
                    return;
                }
                const visibleEnough = !!entry.isIntersecting && entry.intersectionRatio >= 0.35;
                if (visibleEnough || targetVideo.paused || targetVideo.ended) {
                    return;
                }
                pauseMediaElement(targetVideo, 'out_of_view');
                if (typeof targetVideo.__dizzyInlineSyncOverlay === 'function') {
                    targetVideo.__dizzyInlineSyncOverlay();
                }
            });
        }, {
            threshold: [0, 0.1, 0.35, 0.65]
        });
        return window.__dizzyInlineVideoVisibilityObserver;
    }

    function observeInlineVideoVisibility(videoEl) {
        if (!videoEl || videoEl.__dizzyInlineObserved === '1') {
            return;
        }
        const observer = ensureInlineVideoVisibilityObserver();
        if (!observer) {
            return;
        }
        try {
            observer.observe(videoEl);
            videoEl.__dizzyInlineObserved = '1';
        } catch (e) {}
    }

    function initVideoJsInlinePlayer(videoEl) {
        if (
            !videoEl ||
            videoEl.getAttribute('data-dizzy-vjs-ready') === '1' ||
            typeof window.videojs !== 'function'
        ) {
            return false;
        }

        videoEl.setAttribute('data-dizzy-vjs-ready', '1');
        let currentTime = 0;
        let wasPaused = true;
        let wasMuted = false;

        try {
            currentTime = videoEl.currentTime || 0;
            wasPaused = videoEl.paused;
            wasMuted = !!videoEl.muted;
        } catch (e) {}

        try {
            const player = window.videojs(videoEl, {
                controls: true,
                autoplay: false,
                preload: 'auto',
                fluid: false,
                responsive: false,
                fill: true,
                controlBar: {
                    remainingTimeDisplay: false,
                    pictureInPictureToggle: true,
                    volumePanel: { inline: false }
                }
            }, function () {
                if (currentTime > 0) {
                    try { this.currentTime(currentTime); } catch (e) {}
                }
                if (typeof this.muted === 'function') {
                    this.muted(wasMuted);
                }
                if (!wasPaused) {
                    const resume = this.play();
                    if (resume && typeof resume.catch === 'function') {
                        resume.catch(function () {});
                    }
                }
            });
            if (player && typeof player.addClass === 'function') {
                player.addClass('dizzy-pro-video');
            }
            videoEl.__dizzyVjsPlayer = player || null;
            return true;
        } catch (e) {
            videoEl.removeAttribute('data-dizzy-vjs-ready');
            return false;
        }
    }

    function activateSingleInlineVideo(context) {
        if (!context || !context.$wrapper || !context.$wrapper.length) {
            return false;
        }
        bindInlineVideoGuards();

        const $wrapper = context.$wrapper;
        const isFullSingleLayout = $wrapper.closest('.i_image_one_full').length > 0;
        const posterUrl = ($wrapper.attr('data-bg') || $wrapper.attr('data-img') || '').trim();
        const previewImageEl = $wrapper.find('.i_p_image').get(0) || null;
        const originalPlayButtonMarkup = (($wrapper.find('.playbutton').first().html() || '') + '').trim();
        const $inlineVideo = createInlineVideoFromTemplate(context.videoSelector, posterUrl);
        if (!$inlineVideo || !$inlineVideo.length) {
            return false;
        }

        const resolveRatioPercent = function (width, height) {
            if (!isFullSingleLayout) {
                return 0;
            }
            const w = Number(width) || 0;
            const h = Number(height) || 0;
            if (!w || !h) {
                return 0;
            }
            const ratioPercent = (h / w) * 100;
            return isFinite(ratioPercent) && ratioPercent > 0 ? ratioPercent : 0;
        };
        const applyInlineRatio = function (width, height) {
            const ratioPercent = resolveRatioPercent(width, height);
            if (ratioPercent > 0) {
                $wrapper.css('--inline-video-padding', ratioPercent.toFixed(4) + '%');
            }
            return ratioPercent;
        };
        let lockedPreviewRatio = 0;
        if (previewImageEl) {
            lockedPreviewRatio = applyInlineRatio(
                previewImageEl.naturalWidth || previewImageEl.videoWidth || previewImageEl.width,
                previewImageEl.naturalHeight || previewImageEl.videoHeight || previewImageEl.height
            );
        }

        $wrapper.attr('data-inline-video-active', '1').addClass('inline_video_mode');
        if (isFullSingleLayout) {
            $wrapper.addClass('inline_video_full_mode');
        }
        $wrapper.attr('data-inline-video-source', context.videoSelector);
        $wrapper.removeAttr('data-html').removeAttr('data-poster').removeAttr('data-src');
        $wrapper.find('.playbutton, .i_p_image').remove();
        $wrapper.off('click.lgcustom click.lg');
        const $shell = $('<div class="i_inline_video_shell"></div>').append($inlineVideo);
        const $inlineAudioToggle = $('<button type="button" class="i_inline_audio_toggle"></button>');
        const $inlineExpandToggle = $('<button type="button" class="i_inline_expand_toggle"></button>');
        const $inlinePlayOverlay = $('<div class="i_inline_play_overlay is-visible" role="button" tabindex="0"></div>');
        const playLabels = getInlinePlayLabels();
        if (originalPlayButtonMarkup) {
            $inlinePlayOverlay.html(originalPlayButtonMarkup);
        } else {
            $inlinePlayOverlay.html('<span class="i_inline_play_fallback" aria-hidden="true">&#9658;</span>');
        }
        $shell.append($inlineAudioToggle);
        $shell.append($inlineExpandToggle);
        $shell.append($inlinePlayOverlay);
        $wrapper.append($shell);

        const videoEl = $inlineVideo.get(0);
        if (!videoEl || typeof videoEl.play !== 'function') {
            return true;
        }
        videoEl.__dizzyAutoResumeOnVisible = '0';
        applyInlineVideoMutedState(videoEl, getInlineGlobalMutedState());
        syncInlineAudioToggleButton($inlineAudioToggle.get(0), getInlineGlobalMutedState());
        syncInlineExpandToggleButton($inlineExpandToggle.get(0), false);
        const syncInlinePlayOverlay = function () {
            const overlayLabel = (videoEl.paused || videoEl.ended) ? playLabels.play : playLabels.pause;
            if (overlayLabel) {
                $inlinePlayOverlay.attr('aria-label', overlayLabel);
                $inlinePlayOverlay.attr('title', overlayLabel);
            } else {
                $inlinePlayOverlay.removeAttr('aria-label');
                $inlinePlayOverlay.removeAttr('title');
            }
            if (videoEl.paused || videoEl.ended) {
                $inlinePlayOverlay.addClass('is-visible');
            } else {
                $inlinePlayOverlay.removeClass('is-visible');
            }
        };
        const triggerInlineAudioToggle = function (event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const nextMuted = !getInlineGlobalMutedState();
            setInlineGlobalMutedState(nextMuted);
            applyInlineVideoMutedState(videoEl, nextMuted);
        };
        const triggerInlineExpandToggle = function (event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const shellEl = $shell.get(0);
            if (!shellEl) {
                return;
            }
            const nativeExpanded = isInlineShellNativeFullscreen(shellEl);
            if (nativeExpanded) {
                exitInlineNativeFullscreen();
                syncInlineExpandToggleButton($inlineExpandToggle.get(0), false);
                updateInlineStickyControls();
                return;
            }
            const cssExpanded = shellEl.classList.contains('is-inline-expanded');
            if (cssExpanded) {
                setInlineShellExpandedState(shellEl, false);
                return;
            }
            const requestedVideoFullscreen = requestInlineNativeFullscreen(videoEl);
            const requestedShellFullscreen = requestedVideoFullscreen
                ? false
                : requestInlineNativeFullscreen(shellEl);
            if (!requestedVideoFullscreen && !requestedShellFullscreen) {
                setInlineShellExpandedState(shellEl, true);
            } else {
                syncInlineExpandToggleButton($inlineExpandToggle.get(0), true);
                updateInlineStickyControls();
                window.setTimeout(function () {
                    if (!isInlineShellNativeFullscreen(shellEl)) {
                        setInlineShellExpandedState(shellEl, true);
                    }
                }, 260);
            }
        };
        const triggerInlinePlay = function (event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            updateInlineStickyControls();
            applyInlineVideoMutedState(videoEl, getInlineGlobalMutedState());
            pauseOtherInlineVideos(videoEl);
            autoPlayMediaElement(videoEl);
        };
        const handleInlineVideoPlay = function () {
            videoEl.__dizzyAutoResumeOnVisible = '1';
            syncInlineAudioToggleButton($inlineAudioToggle.get(0), getInlineGlobalMutedState());
            pauseOtherInlineVideos(videoEl);
            syncInlinePlayOverlay();
            updateInlineStickyControls();
        };
        const handleInlineVideoPause = function () {
            if (!videoEl.__dizzyPauseReason) {
                videoEl.__dizzyAutoResumeOnVisible = '0';
            }
            videoEl.__dizzyPauseReason = '';
            syncInlinePlayOverlay();
        };
        $inlineAudioToggle.on('click', triggerInlineAudioToggle);
        $inlineAudioToggle.on('keydown', function (event) {
            const key = event && (event.key || event.code || '');
            if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 'Space') {
                triggerInlineAudioToggle(event);
            }
        });
        $inlineExpandToggle.on('click', triggerInlineExpandToggle);
        $inlineExpandToggle.on('keydown', function (event) {
            const key = event && (event.key || event.code || '');
            if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 'Space') {
                triggerInlineExpandToggle(event);
            }
        });
        $inlinePlayOverlay.on('click', triggerInlinePlay);
        $inlinePlayOverlay.on('keydown', function (event) {
            const key = event && (event.key || event.code || '');
            if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 'Space') {
                triggerInlinePlay(event);
            }
        });
        videoEl.__dizzyInlineSyncOverlay = syncInlinePlayOverlay;
        observeInlineVideoVisibility(videoEl);
        videoEl.addEventListener('play', handleInlineVideoPlay);
        videoEl.addEventListener('pause', handleInlineVideoPause);
        videoEl.addEventListener('ended', syncInlinePlayOverlay);
        videoEl.addEventListener('webkitbeginfullscreen', function () {
            syncInlineExpandToggleButton($inlineExpandToggle.get(0), true);
            updateInlineStickyControls();
        });
        videoEl.addEventListener('webkitendfullscreen', function () {
            syncInlineExpandToggleButton(
                $inlineExpandToggle.get(0),
                $shell.hasClass('is-inline-expanded')
            );
            updateInlineStickyControls();
        });
        videoEl.addEventListener('loadedmetadata', function () {
            const metadataRatio = resolveRatioPercent(videoEl.videoWidth, videoEl.videoHeight);
            if (!metadataRatio) {
                return;
            }
            if (lockedPreviewRatio > 0) {
                // Keep poster ratio for rotated portrait videos where metadata may report landscape dimensions.
                const ratioDelta = Math.abs(metadataRatio - lockedPreviewRatio);
                if (ratioDelta > (lockedPreviewRatio * 0.2)) {
                    return;
                }
            }
            applyInlineRatio(videoEl.videoWidth, videoEl.videoHeight);
        }, { once: true });

        syncPlayerAccentToken();

        applyInlineVideoMutedState(videoEl, getInlineGlobalMutedState());
        pauseOtherInlineVideos(videoEl);
        autoPlayMediaElement(videoEl);
        syncInlinePlayOverlay();
        updateInlineStickyControls();
        if (
            window.dizzyVideoJsLoader &&
            typeof window.dizzyVideoJsLoader.ensure === 'function'
        ) {
            window.dizzyVideoJsLoader.ensure().then(function (vjs) {
                if (vjs) {
                    initVideoJsInlinePlayer(videoEl);
                }
            }).catch(function () {});
        } else if (window.videojs) {
            initVideoJsInlinePlayer(videoEl);
        }
        return true;
    }

    function bindSingleVideoInlinePlayback() {
        if (window.__dizzySingleInlineVideoBound) {
            return;
        }
        window.__dizzySingleInlineVideoBound = true;

        const captureHandler = function (event) {
            const target = event && event.target;
            if (!target) {
                return;
            }
            const targetEl = target.nodeType === 1 ? target : target.parentElement;
            if (!targetEl || typeof targetEl.closest !== 'function') {
                return;
            }

            const playButton = targetEl.closest('.i_post_u_images .i_post_image_swip_wrapper .playbutton');
            if (playButton) {
                const context = getSingleInlineVideoContext($(playButton));
                if (context) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }
                    activateSingleInlineVideo(context);
                }
                return;
            }
        };

        document.addEventListener('click', captureHandler, true);
        document.addEventListener('touchend', captureHandler, true);
    }

    function initGalleriesInDOM(scope = $(document)) {
        if (!allowMediaPopup) {
          return;
        }
        const shouldSkipFeedVideoGallery = function ($gallery) {
          if (
            !$gallery ||
            !$gallery.length ||
            !$gallery.closest('.i_post_u_images').length
          ) {
            return false;
          }
          const $galleryItems = $gallery.children('.i_post_image_swip_wrapper');
          if (!$galleryItems.length) {
            return false;
          }
          const $realGalleryItems = $galleryItems.filter(function () {
            return !$(this).hasClass('swiper-slide-duplicate');
          });
          const $items = $realGalleryItems.length ? $realGalleryItems : $galleryItems;
          if ($items.length !== 1) {
            return false;
          }
          return $items.filter('[data-html]').length === 1;
        };
        const disableFeedVideoGalleryPopup = function ($gallery) {
          if (!$gallery || !$gallery.length) {
            return;
          }
          try {
            const galleryInstance = $gallery.data('lightGallery');
            if (galleryInstance && typeof galleryInstance.destroy === 'function') {
              galleryInstance.destroy(true);
            }
          } catch (e) {}
          $gallery.removeClass('lg-initialized');
          $gallery.removeData('lightGallery');
          $gallery.removeData('lgInitPending');
          $gallery.find('.i_post_image_swip_wrapper').off('click.lgcustom click.lg');
        };

        scope.find(".gallery_trigger").each(function () {
          const galleryID = $(this).data("gallery-id");
          if (galleryID && !initializedGalleries.has(galleryID)) {
            const $gallery = $("#" + galleryID);
            if ($gallery.length > 0) {
              if (shouldSkipFeedVideoGallery($gallery)) {
                disableFeedVideoGalleryPopup($gallery);
                initializedGalleries.add(galleryID);
                return;
              }
              initLightGallerySafe($gallery, lightGalleryOptions);
              initializedGalleries.add(galleryID);
            }
          }
        });
    }

    function reInitPostPlugins(scope) {
        if (!scope) return;
        initGalleriesInDOM(scope);

        if (allowMediaPopup) {
          scope.find('[id^="lightgallery"]').each(function () {
            const $this = $(this);
            if ((
              function ($gallery) {
                if (
                  !$gallery ||
                  !$gallery.length ||
                  !$gallery.closest('.i_post_u_images').length
                ) {
                  return false;
                }
                const $galleryItems = $gallery.children('.i_post_image_swip_wrapper');
                if (!$galleryItems.length) {
                  return false;
                }
                const $realGalleryItems = $galleryItems.filter(function () {
                  return !$(this).hasClass('swiper-slide-duplicate');
                });
                const $items = $realGalleryItems.length ? $realGalleryItems : $galleryItems;
                if ($items.length !== 1) {
                  return false;
                }
                return $items.filter('[data-html]').length === 1;
              }
            )($this)) {
              try {
                const galleryInstance = $this.data('lightGallery');
                if (galleryInstance && typeof galleryInstance.destroy === 'function') {
                  galleryInstance.destroy(true);
                }
              } catch (e) {}
              $this.removeClass('lg-initialized');
              $this.removeData('lightGallery');
              $this.removeData('lgInitPending');
              $this.find('.i_post_image_swip_wrapper').off('click.lgcustom click.lg');
              return;
            }
            initLightGallerySafe($this, lightGalleryOptions);
          });
        }

        scope.find('[id^="play_po_"]').each(function () {
          const $this = $(this);
          if (!$this.hasClass('green-audio-player-loaded')) {
            new GreenAudioPlayer($this[0], {
              stopOthersOnPlay: true,
              showTooltips: true,
              showDownloadButton: false,
              enableKeystrokes: true
            });
            $this.addClass('green-audio-player-loaded');
          }
        });

        initExpandableText(scope);
    }

    function getReadMoreLabels() {
        var more = 'Read more';
        var less = 'Show less';
        if (window.readMoreLang) {
            if (window.readMoreLang.more) {
                more = window.readMoreLang.more;
            }
            if (window.readMoreLang.less) {
                less = window.readMoreLang.less;
            }
        }
        return { more: more, less: less };
    }

    function applyExpandableText($content) {
        if (!$content || !$content.length) {
            return;
        }
        var maxLines = parseInt($content.attr('data-max-lines') || '0', 10);
        if (!maxLines) {
            return;
        }

        var labels = getReadMoreLabels();
        var node = $content.get(0);
        var style = window.getComputedStyle(node);
        var lineHeight = parseFloat(style.lineHeight);
        if (isNaN(lineHeight)) {
            var fontSize = parseFloat(style.fontSize) || 14;
            lineHeight = fontSize * 1.4;
        }
        var maxHeight = Math.ceil(lineHeight * maxLines);

        $content.removeClass('is-expanded is-truncated');
        $content.css('max-height', '');
        var $toggle = $content.next('.i_text_toggle');
        if ($toggle.length) {
            $toggle.remove();
        }

        if (node.scrollHeight <= (maxHeight + 2)) {
            return;
        }

        $content.addClass('is-truncated').css('max-height', maxHeight + 'px');
        $toggle = $('<div class="i_text_toggle" role="button" tabindex="0" aria-expanded="false"></div>');
        $toggle.text(labels.more);
        $toggle.insertAfter($content);
        $toggle.on('click keydown', function (e) {
            if (e.type === 'keydown') {
                var key = e.key || '';
                var code = e.which || e.keyCode || 0;
                if (key !== 'Enter' && key !== ' ' && code !== 13 && code !== 32) {
                    return;
                }
            }
            e.preventDefault();
            var isExpanded = $content.hasClass('is-expanded');
            if (isExpanded) {
                $content.removeClass('is-expanded').addClass('is-truncated').css('max-height', maxHeight + 'px');
                $toggle.text(labels.more).attr('aria-expanded', 'false');
            } else {
                $content.addClass('is-expanded').removeClass('is-truncated').css('max-height', 'none');
                $toggle.text(labels.less).attr('aria-expanded', 'true');
            }
        });
    }

    function initExpandableText(scope) {
        var $scope = scope ? $(scope) : $(document);
        $scope.find('.js-text-truncate').each(function () {
            applyExpandableText($(this));
        });
    }

    window.initImageBackgrounds = function (targetSelector = '.i_post_image_swip_wrapper', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };

    window.initImageSuggestedBackgrounds = function (targetSelector = '.i_sub_u_cov', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };


 const initializedStandaloneSwiper = new Set();

window.initStandaloneSwiperLightGallery = function (scope = $(document)) {
  scope.find('.swiper-wrapper[data-standalone-gallery="true"]').each(function () {
    const $wrapper = $(this);
    const galleryID = $wrapper.attr('id');

    if (!galleryID || initializedStandaloneSwiper.has(galleryID)) {
      return;
    }

    initLightGallerySafe($wrapper, $.extend({}, lightGalleryOptions, {
      selector: '.swiper-slide a'
    }));

    initializedStandaloneSwiper.add(galleryID);
  });

  scope.find('.product_images_container .mySwiper').each(function () {
    new Swiper(this, {
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        dynamicBullets: true,
      }
    });
  });
};

$(document).on('click', '.swiper-slide a', function (e) {
  const wrapper = $(this).closest('.swiper-wrapper');
  if (wrapper.hasClass('lg-initialized')) {
    e.preventDefault();
  }
});

    const initializedSuggestedSwipers = new Set();

  /**
   * Initialize Swiper in dynamically loaded suggested creators sections.
   * @param {jQuery|HTMLElement} scope - Optional. DOM scope to search for .mySwiper elements.
   */
  window.initSuggestedCreatorsSwiper = function (scope = $(document)) {
    scope.find('.i_postFormContainer_swiper .mySwiper').each(function () {
      const swiperElement = this;

      // Use a unique identifier to prevent double initialization
      const swiperID = $(swiperElement).data('swiper-id') || swiperElement.id || Math.random().toString(36).substr(2, 9);

      if (!initializedSuggestedSwipers.has(swiperID)) {
        new Swiper(swiperElement, {
          effect: "cards",
          grabCursor: true
        });
        initializedSuggestedSwipers.add(swiperID);
      }
    });
  };

$(document).ready(function () {
  syncPlayerAccentToken();
  bindSingleVideoInlinePlayback();
  if (
    window.dizzyVideoJsLoader &&
    typeof window.dizzyVideoJsLoader.pageNeedsVideoJs === 'function' &&
    typeof window.dizzyVideoJsLoader.ensure === 'function' &&
    window.dizzyVideoJsLoader.pageNeedsVideoJs()
  ) {
    window.dizzyVideoJsLoader.ensure().catch(function () {});
  }
  initGalleriesInDOM(); // mevcut sistemin
  reInitPostPlugins($(document));
  initImageBackgrounds();
  initStandaloneSwiperLightGallery();
  initSuggestedCreatorsSwiper();
  initImageSuggestedBackgrounds();
});

    window.reInitLightGallery = function (html) {
        initGalleriesInDOM(html);
    };
    // Preloader animations
    const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    const plreLoadingAnimationPlus = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';

    /**
     * Open post action menu
     */
    $(document).on("click", ".openPostMenu", function () {
        const id = $(this).attr("id");
        $(".mnoBox" + id).addClass("dblock");
    });

    /**
     * AJAX Login Form Submit
     */
    $(document).on('submit', "#ilogin", function (e) {
        e.preventDefault();
        const form = $("#ilogin");

        $.ajax({
            type: "POST",
            url: siteurl + "requests/login.php",
            data: form.serialize(),
            success: function (response) {
                const resp = (typeof response === 'string') ? response.trim() : '';
                if (resp === "go_inside") {
                    // Refresh or redirect to ensure fresh state
                    if (window.siteurl) {
                        window.location.href = window.siteurl;
                    } else {
                        location.reload();
                    }
                } else {
                    $(".i_error").html(resp || "Unexpected response").show();
                    setTimeout(() => {
                        $(".i_error").html('').hide();
                    }, 5000);
                }
            },
            error: function () {
                $(".i_error").html("Network error. Please try again.").show();
                setTimeout(() => {
                    $(".i_error").html('').hide();
                }, 5000);
            }
        });
    });

    /**
     * AJAX Register Form Submit
     */
    $(document).on('submit', "#iregister", function (e) {
        e.preventDefault();
        const form = $("#iregister");
        const resolveRegisterRedirect = function () {
            const modeRaw = (form.find('input[name="registration_role_mode"]').first().val() || 'legacy').toString().toLowerCase();
            const allowedModes = ['legacy', 'user_agency', 'user_agency_creator'];
            const mode = allowedModes.indexOf(modeRaw) !== -1 ? modeRaw : 'legacy';
            let intent = (form.find('input[name="signup_intent"]:checked').val() || form.find('input[name="signup_intent"]').first().val() || 'user').toString().toLowerCase();
            const allowedIntents = ['user', 'agency', 'creator'];
            if (allowedIntents.indexOf(intent) === -1) {
                intent = 'user';
            }
            if (mode === 'legacy') {
                intent = 'user';
            } else if (mode === 'user_agency' && intent === 'creator') {
                intent = 'user';
            }
            if (intent === 'agency') {
                return 'settings?tab=agencies';
            }
            if (intent === 'creator') {
                return 'creator/becomeCreator';
            }
            return 'settings';
        };

        $.ajax({
            type: "POST",
            url: siteurl + "requests/register.php",
            data: form.serialize(),
            beforeSend: function () {
                $(".register_warning").hide();
                $(".i_modal_content").append(plreLoadingAnimationPlus);
            },
            success: function (response) {
                $(".loaderWrapper").remove();
                const resp = (response || '').toString().replace(/^\uFEFF/, '').trim();

                switch (resp) {
                    case '1': $(".fill_all").show(); break;
                    case '2': $(".fill_username_used").show(); break;
                    case '3': $(".fill_email_used").show(); break;
                    case '4': $(".fill_username_short").show(); break;
                    case '5': $(".fill_username_invalid").show(); break;
                    case '6': $(".fill_email_invalid").show(); break;
                    case '7': $(".fill_pass").show(); break;
                    case '8': {
                        const root = (typeof siteurl !== 'undefined' && siteurl) ? siteurl : (window.siteurl || '/');
                        window.location.href = root + resolveRegisterRedirect();
                        break;
                    }
                    case '9': {
                        $(".fill_email_not_send").show();
                        const root = (typeof siteurl !== 'undefined' && siteurl) ? siteurl : (window.siteurl || '/');
                        setTimeout(() => { window.location.href = root + resolveRegisterRedirect(); }, 1500);
                        break;
                    }
                    default:
                        // Surface unexpected response to help debugging
                        $(".i_error").text(resp || 'Unexpected response').show();
                        setTimeout(() => { $(".i_error").hide().text(''); }, 5000);
                }
            }
        });
    });

    /**
     * Modal open/close for login/forgot password
     */
    $(document).on("click", ".loginForm", function () {
        $(".i_modal_bg").addClass("i_modal_display");
    });

    $(document).on("click", ".rplyComment, .toggleReplies", function (e) {
        e.preventDefault();
        $(".i_modal_bg").addClass("i_modal_display");
    });

    $(document).on("click", ".i_modal_close", function () {
        $(".i_modal_bg").removeClass("i_modal_display");
        $(".i_modal_in").removeAttr("style");
        $(".i_modal_forgot").hide();
    });

    $(document).on("click", ".password-reset", function () {
        $(".i_modal_in").hide();
        $(".i_modal_forgot").show();
    });

    $(document).on("click", ".already-member", function () {
        $(".i_modal_in").show();
        $(".i_modal_forgot").hide();
    });

    /**
     * Open share menu
     */
	    $(document).on("click", ".openShareMenu", function (e) {
	        e.preventDefault();
	        const $button = $(this);
	        const $menu = $button.find(".mnsBox").first();
	
	        if (!$menu.length) {
	            return;
	        }
	
	        $(".mnsBox.dblock").not($menu).removeClass("dblock");
	        $menu.toggleClass("dblock");
	    });

    /**
     * Close post/share menu on outside click
     */
    $(document).on("mouseup touchend", function (e) {
        const container = $(".mnsBox, .mnoBox");
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.removeClass("dblock");
        }
    });

	    /**
	     * Creator Search
	     */
	    let creatorSearchTimer = null;
	    let creatorSearchXhr = null;
	    $(document).on("keyup", "#search_creator", function () {
	        const $input = $(this);
	        const searchValue = $input.val() || '';
	        const keyword = searchValue.trim();
	        const $searchRoot = $input.closest('.i_search, .search-bar');
	        const $container = $searchRoot.find(".i_general_box_search_container").first();
	        const $items = $searchRoot.find(".sb_items").first();

	        if (creatorSearchTimer) {
	            clearTimeout(creatorSearchTimer);
	            creatorSearchTimer = null;
	        }
	        if (creatorSearchXhr && typeof creatorSearchXhr.abort === 'function') {
	            creatorSearchXhr.abort();
	            creatorSearchXhr = null;
	        }

	        if (keyword.length >= 1) {
	            $container.css('display', 'flex');
	            const requestedQuery = searchValue;
	            creatorSearchTimer = setTimeout(() => {
	                $items.html(plreLoadingAnimationPlus);
	                creatorSearchXhr = $.ajax({
	                    type: "POST",
	                    url: siteurl + 'requests/request.php',
	                    data: 'f=searchCreator&s=' + encodeURIComponent(requestedQuery),
	                    cache: false,
	                    success: function (response) {
	                        if (($input.val() || '') !== requestedQuery) {
	                            return;
	                        }
	                        $items.html(response);
	                    },
	                    error: function () {
	                        if (($input.val() || '') !== requestedQuery) {
	                            return;
	                        }
	                        $items.html('');
	                    }
	                });
	            }, 450);
	        } else {
	            $container.css('display', 'none');
	            $items.html('');
	        }
	    });

    /**
     * Send forgot password email
     */
    $(document).on("click", ".i_forgot_button", function () {
        const email = $("#i_nora_forgot_password").val();
        const data = 'f=forgotPass&email=' + encodeURIComponent(email);

        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function () {
                $(".i_modal_forgot").append(plreLoadingAnimationPlus);
            },
            success: function (response) {
                $(".loaderWrapper").remove();

                if (String(response).trim() === '200') {
                    $(".s_e").hide();
                    $(".s_e_success").show();
                } else if (response === '2') {
                    $(".no_this_email").show();
                } else if (response === '3') {
                    $(".system_no_send").show();
                }
            }
        });
    });

    /**
     * Reset password form submission
     */
    $(document).on("submit", "#iresetpass", function (e) {
        e.preventDefault();
        const form = $('#iresetpass');

        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: form.serialize(),
            beforeSend: function () {
                $(".successNot, .warning_not_mach, .warning_not_correct, .warning_write_current_password, .no_new_password_wrote, .minimum_character_not, .not_contain_whitespace").hide();
                $(".i_become_creator_container").append(plreLoadingAnimationPlus);
                form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function (data) {
                setTimeout(() => {
                    form.find(':input[type=submit]').prop('disabled', false);
                }, 3000);

                $(".loaderWrapper").remove();

                switch (data) {
                    case '2': $(".warning_not_mach").show(); break;
                    case '4': $(".no_new_password_wrote").show(); break;
                    case '5': $(".minimum_character_not").show(); break;
                    case '200':
                        $(".i_settings_item_title_for").remove();
                        $(".warning_success").show();
                        $(".i_res_button").remove();
                        break;
                }
            }
        });
    });

    /**
     * Toggle mobile search input
     */
    $(document).on("click", ".mobile_srcbtn", function () {
        $(".i_search").toggleClass("i_search_active");
    });

    /**
     * Earnings simulator logic
     */
    $(document).ready(function () {
        /**
         * Format number with commas and currency symbol
         */
        function decimalFormat(value) {
            if (typeof window.dizzyFormatCurrency === "function") {
                return window.dizzyFormatCurrency(value);
            }
            return value;
        }

        /**
         * Calculate estimated earnings
         */
        function earnAvg() {
            const fee = parseFloat($("body").data("adminfee"));
            const monthlySubscription = parseFloat($("#rangeMonthlySubscription").val());
            const numberFollowers = parseFloat($("#rangeNumberFollowers").val());

            const estimatedFollowers = numberFollowers * 0.2;
            const total = estimatedFollowers * monthlySubscription;
            const platformCut = (total * fee) / 100;
            const result = total - platformCut;

            return decimalFormat(result);
        }

        // Initial render
        $("#estimatedEarn").html(earnAvg());

        // Update on input
        $("#rangeNumberFollowers").on("input change", function () {
            $("#numberFollowers").html($(this).val());
            $("#estimatedEarn").html(earnAvg());
        });

        $("#rangeMonthlySubscription").on("input change", function () {
            const raw = parseFloat($(this).val());
            const formatted = (typeof window.dizzyFormatCurrency === "function") ? window.dizzyFormatCurrency(raw) : raw;
            $("#monthlySubscription").html(formatted);
            $("#estimatedEarn").html(earnAvg());
        });

        // Toggle accordion
        $(".toggle").on("click", function (e) {
            e.preventDefault();
            const $this = $(this);
            const $next = $this.next();

            if ($next.hasClass("show")) {
                $next.removeClass("show").slideUp(350);
                $this.removeClass("activeTogg");
            } else {
                $this.closest("ul").find("li .inner").removeClass("show");
                $next.toggleClass("show").slideToggle(350);
                $this.addClass("activeTogg");
            }
        });

        /**
         * Claim username logic
         */
        $("body").on("click", ".claimname", function () {
            const username = $("#clName").val();
            const data = "f=claim&clnm=" + username;

            $.ajax({
                type: "POST",
                url: siteurl + "requests/request.php",
                dataType: "json",
                data: data,
                cache: false,
                beforeSend: function () {
                    $(".error_report").hide(); // Hide all errors first
                    $(".claimname").prop("disabled", true);
                },
                success: function (response) {
                    const res = response.status;

                    if (res === "200") {
                        window.location.href = siteurl + "register?claim=" + username;
                    } else if (res === "2") {
                        $(".unmexist").show();
                    } else if (res === "5") {
                        $(".invldcharctr").show();
                    } else if (res === "3") {
                        $(".unmempt").show();
                    }

                    setTimeout(() => {
                        $(".claimname").prop("disabled", false);
                    }, 1000);
                    setTimeout(() => {
                        $(".unmexist").hide();
                    }, 5000);
                }
            });
        });
    });

})(jQuery);

// Connections visibility guard for profile links
function showConnectionsAlert(alertKey) {
  if (!alertKey) return;
  if (document.querySelector('.i_bottom_left_alert_container')) return;

  if (typeof PopUPAlerts === 'function') {
    PopUPAlerts(alertKey, 'ialert');
    return;
  }
  if (typeof $ === 'function' && typeof siteurl !== 'undefined') {
    $.ajax({
      type: 'POST',
      url: siteurl + 'requests/request.php',
      data: { f: 'ialert', al: alertKey },
      cache: false,
      success: function (response) {
        if (response) {
          $('body').append(response);
          setTimeout(function () {
            $('.i_bottom_left_alert_container').addClass('fadeOutDown');
          }, 5000);
          setTimeout(function () {
            $('.i_bottom_left_alert_container').remove();
          }, 5000);
        }
      }
    });
  }
}

document.addEventListener('click', function (e) {
  const prw = document.getElementById('prw');
  if (!prw) return;
  const isOwner = prw.dataset.owner === '1';
  const visibility = prw.dataset.connectionsVisibility || '1';
  if (isOwner || visibility === '1') {
    return;
  }

  const anchor = e.target.closest('a');
  if (!anchor) return;
  const href = anchor.getAttribute('href') || '';
  const lower = href.toLowerCase();
  let alertKey = '';
  if (lower.indexOf('pcat=followers') !== -1) {
    alertKey = 'connections_followers_hidden';
  } else if (lower.indexOf('pcat=following') !== -1) {
    alertKey = 'connections_following_hidden';
  } else if (lower.indexOf('pcat=subscribers') !== -1) {
    alertKey = 'connections_subscribers_hidden';
  }
  if (alertKey) {
    e.preventDefault();
    showConnectionsAlert(alertKey);
  }
});
