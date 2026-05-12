/* Reels Music Library
 * - Loads trending / search results from /requests/music.php
 * - Provides a waveform-based trim editor (uses WaveSurfer.js v7 from CDN)
 * - Stores selection on hidden inputs that the publish flow reads.
 */
(function () {
    'use strict';

    var siteRoot = (typeof siteurl !== 'undefined' && siteurl)
        ? siteurl.replace(/\/+$/, '') + '/'
        : '/';
    var WAVESURFER_CDN = 'https://unpkg.com/wavesurfer.js@7.8.6/dist/wavesurfer.min.js';
    var WAVESURFER_REGIONS_CDN = 'https://unpkg.com/wavesurfer.js@7.8.6/dist/plugins/regions.min.js';

    var modal, searchInput, listEl, catsEl, loadingEl, emptyEl;
    var trimView, listView, trimPlayBtn, trimStartEl, trimLenEl, trimTitleEl, trimArtistEl, trimCoverEl;
    var volMusicEl, volVideoEl;
    var ws = null, wsRegions = null, currentRegion = null;
    var searchTimer = null;
    var currentTracks = [];
    var currentTrack = null;
    var previewAudio = null;
    var previewingId = null;
    var REEL_MAX_LEN = 60; // seconds, default cap
    var clipDuration = 15;
    var clipStart = 0;
    var initialized = false;

    // Run init immediately if DOM already parsed (handles AJAX-loaded pages); otherwise wait.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Always-on global click delegate. Works even if init() bailed out because the
    // modal hadn't been injected yet — we lazy-init on first open click.
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.rml-open');
        if (trigger) {
            e.preventDefault();
            if (!initialized) { init(); }
            if (initialized) { openLibrary(); }
            return;
        }
        var rm = e.target.closest('.rml-chip-remove');
        if (rm) {
            e.preventDefault();
            if (initialized) { clearSelection(); }
            return;
        }
        var chip = e.target.closest('.rml-selected-chip');
        if (chip && !rm) {
            if (!initialized) { init(); }
            if (initialized) { openLibrary(); }
        }
    });

    // If the modal is added after initial load (e.g., AJAX), retry init when it appears.
    if (!document.getElementById('reelsMusicModal') && 'MutationObserver' in window) {
        var mo = new MutationObserver(function () {
            if (!initialized && document.getElementById('reelsMusicModal')) {
                init();
                if (initialized) { mo.disconnect(); }
            }
        });
        mo.observe(document.documentElement, { childList: true, subtree: true });
    }

    function init() {
        if (initialized) return;
        modal = document.getElementById('reelsMusicModal');
        if (!modal) return;
        searchInput = document.getElementById('rmlSearchInput');
        listEl = document.getElementById('rmlList');
        catsEl = document.getElementById('rmlCats');
        loadingEl = document.getElementById('rmlLoading');
        emptyEl = document.getElementById('rmlEmpty');
        trimView = document.getElementById('rmlTrim');
        listView = modal.querySelector('.rml-body');
        trimPlayBtn = document.getElementById('rmlTrimPlay');
        trimStartEl = document.getElementById('rmlTrimStart');
        trimLenEl = document.getElementById('rmlTrimLen');
        trimTitleEl = document.getElementById('rmlTrimTitle');
        trimArtistEl = document.getElementById('rmlTrimArtist');
        trimCoverEl = document.getElementById('rmlTrimCover');
        volMusicEl = document.getElementById('rmlVolMusic');
        volVideoEl = document.getElementById('rmlVolVideo');

        modal.addEventListener('click', function (e) {
            if (e.target.closest('[data-rml-close="1"]')) {
                closeLibrary();
            }
        });

        var clearBtn = document.getElementById('rmlClearSearch');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                loadTracks('', currentTag);
            });
        }
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                if (searchTimer) clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    loadTracks(searchInput.value.trim(), '');
                }, 300);
            });
        }

        var trimBack = document.getElementById('rmlTrimBack');
        if (trimBack) trimBack.addEventListener('click', backToList);
        var confirmBtn = document.getElementById('rmlConfirm');
        if (confirmBtn) confirmBtn.addEventListener('click', confirmSelection);
        if (trimPlayBtn) trimPlayBtn.addEventListener('click', toggleTrimPlayback);

        initialized = true;
        loadCategories();
    }


    var currentTag = '';

    function openLibrary() {
        // Determine reel duration cap from preview video (if available).
        var videoEl = document.querySelector('.uploaded_storie_image video');
        if (videoEl && isFinite(videoEl.duration) && videoEl.duration > 1) {
            REEL_MAX_LEN = Math.min(90, Math.max(5, videoEl.duration));
        }
        modal.classList.add('rml-open');
        document.body.classList.add('rml-no-scroll');
        modal.setAttribute('aria-hidden', 'false');
        if (!currentTracks.length) {
            loadTracks('', '');
        }
    }
    function closeLibrary() {
        stopPreview();
        if (ws) { try { ws.pause(); } catch (e) {} }
        modal.classList.remove('rml-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('rml-no-scroll');
        backToList(true);
    }

    function loadCategories() {
        if (!catsEl) return;
        fetch(siteRoot + 'requests/music.php?action=categories', { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.text();
            })
            .then(function (txt) {
                var j;
                try { j = JSON.parse(txt); } catch (e) {
                    console.error('[music] categories: invalid JSON', txt.slice(0, 200));
                    return;
                }
                if (!j || !j.ok) {
                    console.warn('[music] categories error', j && j.error);
                    return;
                }
                catsEl.innerHTML = '';
                (j.categories || []).forEach(function (c, idx) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'rml-cat' + (idx === 0 ? ' active' : '');
                    b.textContent = c.label;
                    b.dataset.tag = c.key;
                    b.addEventListener('click', function () {
                        catsEl.querySelectorAll('.rml-cat').forEach(function (x) { x.classList.remove('active'); });
                        b.classList.add('active');
                        currentTag = c.key;
                        if (searchInput) searchInput.value = '';
                        loadTracks('', c.key);
                    });
                    catsEl.appendChild(b);
                });
            })
            .catch(function (err) {
                console.warn('[music] categories fetch failed:', err && err.message);
            });
    }

    function loadTracks(query, tag) {
        if (!listEl) return;
        if (loadingEl) loadingEl.style.display = 'flex';
        if (emptyEl) {
            emptyEl.style.display = 'none';
            emptyEl.textContent = (window.LANG && window.LANG.music_no_results) || 'No tracks found.';
        }
        listEl.innerHTML = '';
        var url;
        if (query) {
            url = siteRoot + 'requests/music.php?action=search&q=' + encodeURIComponent(query);
        } else {
            url = siteRoot + 'requests/music.php?action=trending&tag=' + encodeURIComponent(tag || '');
        }
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (txt) { return { status: r.status, ok: r.ok, body: txt }; });
            })
            .then(function (resp) {
                if (loadingEl) loadingEl.style.display = 'none';
                var j;
                try { j = JSON.parse(resp.body); } catch (e) {
                    console.error('[music] tracks: invalid JSON (HTTP ' + resp.status + ')', resp.body.slice(0, 300));
                    if (emptyEl) {
                        emptyEl.style.display = 'block';
                        emptyEl.textContent = 'Server error (HTTP ' + resp.status + '). See browser console.';
                    }
                    return;
                }
                if (!j.ok) {
                    if (emptyEl) {
                        emptyEl.style.display = 'block';
                        if (j.error === 'auth_required') {
                            emptyEl.textContent = 'Please sign in to use the music library.';
                        } else if (j.error === 'reels_disabled') {
                            emptyEl.textContent = 'Reels feature is disabled by the admin.';
                        } else {
                            emptyEl.textContent = 'Error: ' + (j.error || 'unknown');
                        }
                    }
                    currentTracks = [];
                    return;
                }
                if (!j.tracks || !j.tracks.length) {
                    if (emptyEl) emptyEl.style.display = 'block';
                    currentTracks = [];
                    return;
                }
                currentTracks = j.tracks;
                renderList(j.tracks);
            })
            .catch(function (err) {
                if (loadingEl) loadingEl.style.display = 'none';
                console.error('[music] tracks fetch failed:', err);
                if (emptyEl) {
                    emptyEl.style.display = 'block';
                    emptyEl.textContent = 'Network error: ' + (err && err.message ? err.message : 'unable to reach music endpoint');
                }
            });
    }

    function renderList(tracks) {
        var frag = document.createDocumentFragment();
        tracks.forEach(function (t) {
            var li = document.createElement('li');
            li.className = 'rml-item';
            li.dataset.trackId = t.id;
            li.innerHTML =
                '<div class="rml-item-cover">' +
                    '<img src="' + escAttr(t.cover_url || '') + '" alt="" onerror="this.style.visibility=\'hidden\'">' +
                    '<button type="button" class="rml-item-play" aria-label="Preview">' +
                        '<svg class="rml-icon-play" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>' +
                        '<svg class="rml-icon-pause" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="display:none"><path d="M6 19h4V5H6zM14 5v14h4V5z"/></svg>' +
                    '</button>' +
                    '<span class="rml-eq" hidden><i></i><i></i><i></i><i></i></span>' +
                '</div>' +
                '<div class="rml-item-meta">' +
                    '<div class="rml-item-title">' + escHtml(t.title || 'Untitled') + '</div>' +
                    '<div class="rml-item-artist">' + escHtml(t.artist || '') + ' · ' + fmtTime(t.duration || 0) + '</div>' +
                '</div>' +
                '<button type="button" class="rml-item-use">' + getLang('music_use', 'Use') + '</button>';
            li.querySelector('.rml-item-play').addEventListener('click', function (e) {
                e.stopPropagation();
                togglePreview(t, li);
            });
            li.querySelector('.rml-item-use').addEventListener('click', function (e) {
                e.stopPropagation();
                openTrim(t);
            });
            li.addEventListener('click', function () { openTrim(t); });
            frag.appendChild(li);
        });
        listEl.appendChild(frag);
    }

    function togglePreview(track, li) {
        if (previewingId === track.id && previewAudio && !previewAudio.paused) {
            stopPreview();
            return;
        }
        stopPreview();
        previewAudio = new Audio(proxiedAudioUrl(track.audio_url));
        previewAudio.preload = 'auto';
        // No crossOrigin: Jamendo CDN sets a fixed Access-Control-Allow-Origin
        // that breaks browsers in CORS mode. Plain audio playback works fine.
        previewAudio.play().catch(function (err) {
            console.warn('[music] preview play failed:', err && err.message);
        });
        previewingId = track.id;
        li.classList.add('rml-playing');
        li.querySelector('.rml-icon-play').style.display = 'none';
        li.querySelector('.rml-icon-pause').style.display = '';
        var eq = li.querySelector('.rml-eq');
        if (eq) eq.hidden = false;
        previewAudio.addEventListener('ended', stopPreview);
    }
    function stopPreview() {
        if (previewAudio) {
            try { previewAudio.pause(); } catch (e) {}
            previewAudio = null;
        }
        listEl.querySelectorAll('.rml-item.rml-playing').forEach(function (li) {
            li.classList.remove('rml-playing');
            li.querySelector('.rml-icon-play').style.display = '';
            li.querySelector('.rml-icon-pause').style.display = 'none';
            var eq = li.querySelector('.rml-eq');
            if (eq) eq.hidden = true;
        });
        previewingId = null;
    }

    /* Trim editor */
    function openTrim(track) {
        stopPreview();
        currentTrack = track;
        trimTitleEl.textContent = track.title || '';
        trimArtistEl.textContent = track.artist || '';
        trimCoverEl.src = track.cover_url || '';
        trimView.hidden = false;
        listView.style.display = 'none';
        catsEl.style.display = 'none';
        document.querySelector('.rml-search').style.display = 'none';
        clipStart = 0;
        clipDuration = Math.min(REEL_MAX_LEN, Math.min(60, track.duration || 60));
        ensureWaveSurfer().then(function () {
            initWaveSurfer(track);
        });
    }
    function backToList(force) {
        if (ws) {
            try { ws.pause(); } catch (e) {}
        }
        trimView.hidden = true;
        listView.style.display = '';
        catsEl.style.display = '';
        document.querySelector('.rml-search').style.display = '';
        // Reset play icon
        document.querySelector('#rmlTrimPlay .rml-icon-play').style.display = '';
        document.querySelector('#rmlTrimPlay .rml-icon-pause').style.display = 'none';
        if (!force) {
            // ensure not capturing
        }
    }

    function ensureWaveSurfer() {
        if (window.WaveSurfer && window.WaveSurfer.Regions) return Promise.resolve();
        return loadScript(WAVESURFER_CDN).then(function () {
            return loadScript(WAVESURFER_REGIONS_CDN);
        });
    }
    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[data-src="' + src + '"]');
            if (existing) { existing.addEventListener('load', resolve); return; }
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.dataset.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function initWaveSurfer(track) {
        if (ws) {
            try { ws.destroy(); } catch (e) {}
            ws = null; wsRegions = null; currentRegion = null;
        }
        var container = document.getElementById('rmlWave');
        container.innerHTML = '';
        ws = WaveSurfer.create({
            container: container,
            waveColor: 'rgba(255,255,255,0.45)',
            progressColor: '#ff3b6b',
            cursorColor: '#fff',
            barWidth: 2,
            barRadius: 2,
            barGap: 2,
            height: 64,
            normalize: true,
            // Route through our proxy so the audio response carries CORS
            // headers (required by WaveSurfer to decode PCM via WebAudio).
            url: proxiedAudioUrl(track.audio_url),
        });
        wsRegions = ws.registerPlugin(WaveSurfer.Regions.create());
        ws.on('decode', function () {
            var dur = ws.getDuration();
            if (clipDuration > dur) clipDuration = Math.max(5, Math.min(dur, REEL_MAX_LEN));
            currentRegion = wsRegions.addRegion({
                start: 0,
                end: Math.min(clipDuration, dur),
                color: 'rgba(255, 59, 107, 0.25)',
                drag: true,
                resize: true,
            });
            currentRegion.on('update-end', function () {
                clipStart = currentRegion.start;
                clipDuration = Math.min(REEL_MAX_LEN, currentRegion.end - currentRegion.start);
                if (currentRegion.end - currentRegion.start > REEL_MAX_LEN) {
                    currentRegion.setOptions({ start: clipStart, end: clipStart + REEL_MAX_LEN });
                }
                updateTrimDisplay();
            });
            updateTrimDisplay();
        });
        ws.on('audioprocess', function () {
            if (!currentRegion) return;
            if (ws.getCurrentTime() >= currentRegion.end) {
                ws.setTime(currentRegion.start);
            }
        });
        ws.on('finish', function () {
            document.querySelector('#rmlTrimPlay .rml-icon-play').style.display = '';
            document.querySelector('#rmlTrimPlay .rml-icon-pause').style.display = 'none';
        });
    }
    function toggleTrimPlayback() {
        if (!ws) return;
        if (ws.isPlaying()) {
            ws.pause();
            document.querySelector('#rmlTrimPlay .rml-icon-play').style.display = '';
            document.querySelector('#rmlTrimPlay .rml-icon-pause').style.display = 'none';
        } else {
            if (currentRegion && (ws.getCurrentTime() < currentRegion.start || ws.getCurrentTime() >= currentRegion.end)) {
                ws.setTime(currentRegion.start);
            }
            ws.play();
            document.querySelector('#rmlTrimPlay .rml-icon-play').style.display = 'none';
            document.querySelector('#rmlTrimPlay .rml-icon-pause').style.display = '';
        }
    }
    function updateTrimDisplay() {
        trimStartEl.textContent = fmtTime(clipStart);
        trimLenEl.textContent = fmtTime(clipDuration);
    }

    function confirmSelection() {
        if (!currentTrack) return;
        var data = {
            id: currentTrack.id,
            provider: currentTrack.provider,
            title: currentTrack.title || '',
            artist: currentTrack.artist || '',
            cover: currentTrack.cover_url || '',
            url: currentTrack.audio_url || '',
            start: clipStart || 0,
            duration: clipDuration || REEL_MAX_LEN,
            volume: ((parseInt(volMusicEl.value, 10) || 80) / 100),
            video_volume: ((parseInt(volVideoEl.value, 10) || 50) / 100),
        };
        // Hidden form storage
        ensureHiddenInputs();
        document.getElementById('reelMusicMeta').value = JSON.stringify(data);
        try {
            document.getElementById('reelMusicMeta').dispatchEvent(new CustomEvent('reel-music-change', { detail: data, bubbles: true }));
        } catch (e) {}
        renderSelectedChip(data);
        closeLibrary();
    }

    function ensureHiddenInputs() {
        var form = document.getElementById('uploadReelsform') || document.body;
        if (!document.getElementById('reelMusicMeta')) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.id = 'reelMusicMeta';
            inp.name = 'music_meta';
            (form.appendChild ? form : document.body).appendChild(inp);
        }
    }

    function renderSelectedChip(data) {
        var holder = document.getElementById('rmlSelectedHolder');
        if (!holder) return;
        if (!data) {
            holder.innerHTML = '';
            holder.style.display = 'none';
            return;
        }
        holder.style.display = '';
        holder.innerHTML =
            '<div class="rml-selected-chip" title="' + escAttr((data.title || '') + ' — ' + (data.artist || '')) + '">' +
                '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 3v10.55A4 4 0 1 0 14 17V7h4V3z"/></svg>' +
                '<span class="rml-selected-text"><strong>' + escHtml(data.title || '') + '</strong>' +
                    (data.artist ? ' · ' + escHtml(data.artist) : '') +
                '</span>' +
                '<button type="button" class="rml-chip-remove" aria-label="Remove">×</button>' +
            '</div>';
    }
    function clearSelection() {
        var meta = document.getElementById('reelMusicMeta');
        if (meta) {
            meta.value = '';
            try { meta.dispatchEvent(new CustomEvent('reel-music-change', { detail: null, bubbles: true })); } catch (e) {}
        }
        renderSelectedChip(null);
    }

    /* Helpers */
    function fmtTime(sec) {
        sec = Math.max(0, Math.floor(sec));
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        return m + ':' + (s < 10 ? '0' + s : s);
    }
    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }
    function escAttr(s) { return escHtml(s); }
    function getLang(key, fallback) {
        if (window.LANG && window.LANG[key]) return window.LANG[key];
        return fallback;
    }
    // Build a same-origin proxy URL for cross-origin audio CDNs (used by WaveSurfer).
    function proxiedAudioUrl(u) {
        if (!u) return u;
        try {
            var url = new URL(u, window.location.href);
            if (url.origin === window.location.origin) return u;
        } catch (e) {}
        return siteRoot + 'requests/music_proxy.php?u=' + encodeURIComponent(u);
    }

    /* Hook into the publish flow:
     * - The existing handler in inora.js POSTs f=insertNewReel.
     * - We append the music_meta JSON so the server saves it in the same request.
     */
    if (window.jQuery) {
        jQuery(document).ajaxSend(function (event, xhr, settings) {
            try {
                if (!settings || typeof settings.data !== 'string') return;
                if (settings.data.indexOf('f=insertNewReel') === -1) return;
                var metaInput = document.getElementById('reelMusicMeta');
                if (!metaInput || !metaInput.value) return;
                if (settings.data.indexOf('music_meta=') !== -1) return; // already set
                settings.data += '&music_meta=' + encodeURIComponent(metaInput.value);
            } catch (e) { /* swallow */ }
        });
    }
})();
