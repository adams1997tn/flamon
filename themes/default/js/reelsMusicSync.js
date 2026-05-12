/* Reels music playback sync.
 * For each .reel that has a sibling <audio class="reel-music-audio">, mirror
 * the video's play / pause / seek events onto the audio element so the music
 * plays in sync with the video. The video stays muted-by-default; per-reel
 * mute/unmute (controlled by .volume-control) flips the music volume.
 *
 * Behaviour:
 *  - audio is offset by data-start (seconds) and capped to data-duration.
 *  - When unmuted, audio plays at data-volume; video at data-video-volume.
 *  - When muted (default), audio is silent so the page is quiet on load.
 */
(function () {
    'use strict';

    function setupReelAudio(reel) {
        if (!reel || reel.dataset.musicWired === '1') return;
        var audio = reel.querySelector('audio.reel-music-audio');
        var video = reel.querySelector('video');
        if (!audio || !video) return;
        reel.dataset.musicWired = '1';

        var start    = parseFloat(audio.dataset.start) || 0;
        var duration = parseFloat(audio.dataset.duration) || 0;
        var volMusic = clamp(parseFloat(audio.dataset.volume), 0, 1, 0.8);
        var volVideo = clamp(parseFloat(audio.dataset.videoVolume), 0, 1, 0.5);

        audio.loop = false;
        audio.preload = 'auto';
        // Mirror the video's muted flag onto the audio element. Browsers only
        // allow autoplay for *muted* media — setting volume=0 alone is not enough,
        // which is why the music never starts on a freshly-loaded reel.
        audio.muted = !!video.muted;
        audio.volume = volMusic;

        function applyMutedState() {
            audio.muted = !!video.muted;
            if (video.muted) {
                audio.volume = 0;
                video.volume = 0;
            } else {
                audio.volume = volMusic;
                video.volume = volVideo;
                // Resume playback after user gesture unmutes the reel.
                if (audio.paused && !video.paused) safePlayAudio();
            }
        }

        function safePlayAudio() {
            var p = audio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(function () { /* autoplay block: will start on user gesture */ });
            }
        }

        function syncToVideoTime() {
            // Loop the music clip across the reel video duration.
            var vt = video.currentTime || 0;
            var clipLen = duration > 0 ? duration : (audio.duration || 30);
            if (!isFinite(clipLen) || clipLen <= 0) clipLen = 30;
            var offset = vt % clipLen;
            var target = start + offset;
            // Avoid spamming seeks when already in sync (within 250ms).
            if (Math.abs(audio.currentTime - target) > 0.25) {
                try { audio.currentTime = target; } catch (e) {}
            }
        }

        video.addEventListener('play', function () {
            applyMutedState();
            syncToVideoTime();
            safePlayAudio();
        });
        video.addEventListener('pause', function () {
            try { audio.pause(); } catch (e) {}
        });
        video.addEventListener('seeking', syncToVideoTime);
        video.addEventListener('seeked', syncToVideoTime);
        video.addEventListener('timeupdate', function () {
            // Re-sync if drift > 0.4s (e.g., audio lagged on slow networks).
            var clipLen = duration > 0 ? duration : (audio.duration || 30);
            if (!isFinite(clipLen) || clipLen <= 0) return;
            var expected = start + ((video.currentTime || 0) % clipLen);
            if (Math.abs(audio.currentTime - expected) > 0.4) {
                try { audio.currentTime = expected; } catch (e) {}
            }
        });
        video.addEventListener('ended', function () {
            try { audio.pause(); audio.currentTime = start; } catch (e) {}
        });
        video.addEventListener('volumechange', applyMutedState);

        // Loop music if it ends before video does.
        audio.addEventListener('ended', function () {
            try { audio.currentTime = start; safePlayAudio(); } catch (e) {}
        });

        // If video is already playing when wired (active reel), kick audio off.
        if (!video.paused && !video.ended) {
            applyMutedState();
            syncToVideoTime();
            safePlayAudio();
        }
    }

    function clamp(v, lo, hi, dflt) {
        if (!isFinite(v)) return dflt;
        return Math.min(hi, Math.max(lo, v));
    }

    function wireAll() {
        document.querySelectorAll('.reel').forEach(setupReelAudio);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wireAll);
    } else {
        wireAll();
    }

    // Watch for new reels being appended (infinite scroll).
    var container = document.getElementById('reelsContainer');
    if (container && 'MutationObserver' in window) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType === 1) {
                        if (n.classList && n.classList.contains('reel')) {
                            setupReelAudio(n);
                        } else {
                            n.querySelectorAll && n.querySelectorAll('.reel').forEach(setupReelAudio);
                        }
                    }
                });
            });
        }).observe(container, { childList: true, subtree: true });
    }
})();

/* Apply data-speed playbackRate to reel videos */
(function () {
    'use strict';
    function applySpeed(v) {
        if (!v || v.dataset.speedApplied === '1') return;
        var s = parseFloat(v.getAttribute('data-speed') || '0');
        if (!s || s <= 0) return;
        v.dataset.speedApplied = '1';
        var setRate = function () { try { v.playbackRate = s; } catch (e) {} };
        setRate();
        v.addEventListener('loadedmetadata', setRate);
        v.addEventListener('play', setRate);
        v.addEventListener('ratechange', function () {
            // Some browsers reset rate; force back if mismatched.
            if (Math.abs(v.playbackRate - s) > 0.01) setRate();
        });
    }
    function scan(root) {
        (root || document).querySelectorAll('.reel video[data-speed]').forEach(applySpeed);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(); });
    } else { scan(); }
    var c = document.getElementById('reelsContainer');
    if (c && 'MutationObserver' in window) {
        new MutationObserver(function (ms) {
            ms.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType === 1) {
                        if (n.matches && n.matches('video[data-speed]')) applySpeed(n);
                        else if (n.querySelectorAll) n.querySelectorAll('video[data-speed]').forEach(applySpeed);
                    }
                });
            });
        }).observe(c, { childList: true, subtree: true });
    }
})();


/* ============================================================
 * Reel overlay time-window + animation re-trigger.
 * Activates per-overlay visibility windows (data-start / data-end)
 * and replays entrance animation each time the overlay enters its
 * window, so timeline-based effects work in the feed.
 * ============================================================ */
(function () {
    'use strict';
    function bindOverlayWindow(reel) {
        if (!reel || reel.dataset.roBound === '1') return;
        var v = reel.querySelector('video');
        var box = reel.querySelector('.reel-overlays');
        if (!v || !box) return;
        var items = box.querySelectorAll('.ro-item');
        if (!items.length) return;
        reel.dataset.roBound = '1';

        var data = [];
        items.forEach(function (el) {
            var s = parseFloat(el.getAttribute('data-start') || '0') || 0;
            var e = parseFloat(el.getAttribute('data-end') || '0') || 0;
            data.push({ el: el, s: s, e: e, anim: el.getAttribute('data-anim') || '', visible: null });
        });

        function tick() {
            var dur = v.duration || 0;
            var cur = v.currentTime || 0;
            data.forEach(function (d) {
                var endT = d.e > 0 ? d.e : dur;
                var visible = (cur >= d.s && cur <= endT);
                if (visible !== d.visible) {
                    d.visible = visible;
                    d.el.classList.toggle('is-out-of-window', !visible);
                    if (visible && d.anim && d.anim !== 'none' && !/^(pulse|spin|neon)$/.test(d.anim)) {
                        // re-trigger entrance animation
                        var a = d.el.style.animation;
                        d.el.style.animation = 'none';
                        void d.el.offsetWidth;
                        d.el.style.animation = '';
                    }
                }
            });
        }
        v.addEventListener('timeupdate', tick);
        v.addEventListener('loadedmetadata', tick);
        v.addEventListener('seeked', tick);
        tick();
    }

    function scan(root) {
        (root || document).querySelectorAll('.reel').forEach(bindOverlayWindow);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(); });
    } else { scan(); }
    var c = document.getElementById('reelsContainer');
    if (c && 'MutationObserver' in window) {
        new MutationObserver(function (ms) {
            ms.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.classList && n.classList.contains('reel')) bindOverlayWindow(n);
                    else if (n.querySelectorAll) n.querySelectorAll('.reel').forEach(bindOverlayWindow);
                });
            });
        }).observe(c, { childList: true, subtree: true });
    }
})();
