/* ============================================================
 * Reels full-screen editor v3 (TikTok-style, advanced).
 * Tools: Music | Text | Emoji | GIF | Animate | Layers | Filter | Speed
 * Per-overlay: drag, pinch/wheel resize, two-finger rotate, delete
 * Animations: fade, slide-up/down/left/right, bounce, zoom,
 *             pulse, spin, shake, neon, typewriter (in / out)
 * Timing: per-overlay start/end window driven by video currentTime
 * Layer mgmt: bring front/back, send forward/backward (z-index)
 * Timeline: bottom strip with selectable element bars + handles
 * Music tool is unchanged (delegates to musicLibrary).
 * ============================================================ */
(function () {
    'use strict';

    var siteRoot = (window.siteurl || '/');

    var EMOJI_GROUPS = [
        { label: 'Smiles', emojis: '😀😃😄😁😆😅😂🤣🥲😊😇🙂🙃😉😌😍🥰😘😗😙😚😋😛😜🤪😝🤑🤗🤭🤫🤔🤐🤨😐😑😶😏😒🙄😬🤥😌😔😪🤤😴'.match(/./gu) },
        { label: 'Hearts', emojis: '❤️🧡💛💚💙💜🖤🤍🤎💔❣️💕💞💓💗💖💘💝💟♥️💌💋'.match(/./gu) },
        { label: 'Hands',  emojis: '👋🤚🖐✋🖖👌🤌🤏✌️🤞🤟🤘🤙👈👉👆🖕👇☝️👍👎✊👊🤛🤜👏🙌👐🤲🤝🙏💪'.match(/./gu) },
        { label: 'Animals',emojis: '🐶🐱🐭🐹🐰🦊🐻🐼🐨🐯🦁🐮🐷🐸🐵🐔🐧🐦🐤🦄🦋🐝🐞🦂🦄🦓🦒🦘🦛🦏'.match(/./gu) },
        { label: 'Food',   emojis: '🍎🍊🍋🍌🍉🍇🍓🍒🍑🥭🍍🥝🍅🍆🥑🥦🌽🍕🍔🌭🌮🌯🍿🍩🍪🎂🍰🍦🍫🍬🍭🍡🍯☕🍵🍺🍷🍸🍹'.match(/./gu) },
        { label: 'Travel', emojis: '🚗🚕🚙🚌🚎🏎🚓🚑🚒🚜✈️🚀🛸🚁🚉🚂🚊🚞🚇🛴🛵🏍🛺⛵🛶🚤🛳⛴🚢🚧🗺🗽🏖🏔'.match(/./gu) },
        { label: 'Activity', emojis: '⚽🏀🏈⚾🥎🎾🏐🏉🎱🏓🏸🥊🥋🥅⛳🏹🎣🥌🎿⛷🏂🏋️🤼🤸⛹️🚴🚵🏆🥇🥈🥉🎖🏅🎯🎲🧩🎮🎰🎨🎭🎬🎤🎧🎼🎵🎶'.match(/./gu) },
        { label: 'Symbols',emojis: '🔥💯✨⭐🌟💫⚡☀️🌈🎉🎊🎁🎈💎🏆💰🚀🌹🌺🌻🌷🌸💐⌛⏰📅🔒🔓🔑📌📍🚩💡🔔'.match(/./gu) },
    ];

    var TEXT_COLORS = ['#ffffff', '#000000', '#ff3b30', '#ff9500', '#ffcc00', '#34c759', '#5ac8fa', '#007aff', '#af52de', '#ff2d55'];
    var FILTERS = [
        { id: 'none',     label: 'Original', icon: '🎬' },
        { id: 'vivid',    label: 'Vivid',    icon: '🌈' },
        { id: 'warm',     label: 'Warm',     icon: '🔥' },
        { id: 'cool',     label: 'Cool',     icon: '❄️' },
        { id: 'bw',       label: 'B&W',      icon: '⚪' },
        { id: 'vintage',  label: 'Vintage',  icon: '📷' },
        { id: 'dramatic', label: 'Dramatic', icon: '🎭' },
    ];
    var SPEEDS = [
        { v: 0.5, label: '0.5x' },
        { v: 0.75, label: '0.75x' },
        { v: 1, label: '1x' },
        { v: 1.5, label: '1.5x' },
        { v: 2, label: '2x' },
    ];

    // Animation presets. Each preset has an "in" and "out" CSS class. The
    // preset value is what gets stored on the overlay (`anim`).
    var ANIM_PRESETS = [
        { id: 'none',        label: 'None',        icon: '∅'  },
        { id: 'fade',        label: 'Fade',        icon: '🌫️' },
        { id: 'zoom',        label: 'Zoom',        icon: '🔍' },
        { id: 'bounce',      label: 'Bounce',      icon: '🏀' },
        { id: 'slide-up',    label: 'Slide Up',    icon: '⬆️' },
        { id: 'slide-down',  label: 'Slide Down',  icon: '⬇️' },
        { id: 'slide-left',  label: 'Slide Left',  icon: '⬅️' },
        { id: 'slide-right', label: 'Slide Right', icon: '➡️' },
        { id: 'pulse',       label: 'Pulse',       icon: '💓' },
        { id: 'spin',        label: 'Spin',        icon: '🌀' },
        { id: 'shake',       label: 'Shake',       icon: '〰️' },
        { id: 'neon',        label: 'Neon',        icon: '💡' },
        { id: 'typewriter',  label: 'Type',        icon: '⌨️' },
    ];

    // Curated GIF/sticker library (small, free PNG/animated content).
    // Users can also paste any URL (.gif/.png/.webp).
    var GIF_LIBRARY = [
        { url: 'https://media.tenor.com/EM8HpvEXNk8AAAAi/heart-love.gif',         label: 'Heart' },
        { url: 'https://media.tenor.com/3w_xsgI5_dEAAAAi/sparkles-sparkle.gif',   label: 'Sparkle' },
        { url: 'https://media.tenor.com/x8v1oNUOmg4AAAAi/fire-flame.gif',         label: 'Fire' },
        { url: 'https://media.tenor.com/1Pvy2nKdqWUAAAAi/lol-laugh.gif',          label: 'LOL' },
        { url: 'https://media.tenor.com/8Tn9-6VYssIAAAAi/wow-omg.gif',            label: 'Wow' },
        { url: 'https://media.tenor.com/4_-VJxprGIYAAAAi/yes-cool.gif',           label: 'Yes' },
        { url: 'https://media.tenor.com/wL0WnkdXKAAAAAi/clap-applause.gif',       label: 'Clap' },
        { url: 'https://media.tenor.com/HtRdyHLi_8AAAAAi/cute-kawaii.gif',        label: 'Cute' },
        { url: 'https://media.tenor.com/V0Mu9cWX-1MAAAAi/100-emoji.gif',          label: '100' },
        { url: 'https://media.tenor.com/qLxBLG8b6sgAAAAi/yay-celebration.gif',    label: 'Party' },
        { url: 'https://media.tenor.com/A_q3i3X3R5gAAAAi/thumbs-up.gif',          label: 'Thumbs' },
        { url: 'https://media.tenor.com/dGRzj7iE_4cAAAAi/kiss-heart.gif',         label: 'Kiss' },
    ];

    var state = {
        history: [],
        overlays: [],
        nextId: 1,
        selected: null,
        editor: null,
        canvas: null,
        video: null,
        layer: null,
        filter: 'none',
        speed: 1,
        videoVolume: 0.5,
        duration: 0,
    };

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        var form = document.getElementById('uploadReelsform');
        if (!form) return;
        injectEditor();
        wireGlobalKeyboard();

        var host = document.querySelector('.edit_created_stories');
        if (host) {
            if (host.querySelector('video.lg-video-object')) openEditor();
            new MutationObserver(function () {
                if (host.querySelector('video.lg-video-object') && !state.editor.classList.contains('is-open')) {
                    openEditor();
                }
            }).observe(host, { childList: true, subtree: true });
        }
    }

    function buildEmojiHtml() {
        var tabs = EMOJI_GROUPS.map(function (g, i) {
            return '<button type="button" class="re-emoji-tab' + (i === 0 ? ' is-active' : '') + '" data-tab="' + i + '">' + g.label + '</button>';
        }).join('');
        var grids = EMOJI_GROUPS.map(function (g, i) {
            var cells = (g.emojis || []).map(function (e) {
                return '<button type="button" data-emoji="' + e + '">' + e + '</button>';
            }).join('');
            return '<div class="re-emoji-grid' + (i === 0 ? '' : ' is-hidden') + '" data-grid="' + i + '" ' + (i === 0 ? '' : 'style="display:none"') + '>' + cells + '</div>';
        }).join('');
        return '<div class="re-emoji-tabs">' + tabs + '</div>' + grids;
    }

    function buildFilterHtml() {
        return '<div class="re-filter-row">' + FILTERS.map(function (f) {
            return '<div class="re-filter-thumb' + (f.id === 'none' ? ' is-active' : '') + '" data-filter="' + f.id + '"><div class="re-fp">' + f.icon + '</div><span>' + f.label + '</span></div>';
        }).join('') + '</div>';
    }

    function buildSpeedHtml() {
        return '<div class="re-speed-row">' + SPEEDS.map(function (s) {
            return '<button type="button" data-speed="' + s.v + '"' + (s.v === 1 ? ' class="is-active"' : '') + '>' + s.label + '</button>';
        }).join('') + '</div>'
        + '<div class="re-vol-block"><label>Video volume</label><input type="range" min="0" max="100" value="50" data-vol="video"></div>';
    }

    function buildGifHtml() {
        var grid = GIF_LIBRARY.map(function (g) {
            return '<button type="button" class="re-gif-cell" data-gif="' + escAttr(g.url) + '" title="' + escAttr(g.label) + '">'
                 + '<img loading="lazy" src="' + escAttr(g.url) + '" alt="' + escAttr(g.label) + '"></button>';
        }).join('');
        return ''
            + '<div class="re-gif-search">'
            + '  <input type="text" class="re-gif-url" placeholder="Paste GIF / PNG / WebP URL..." />'
            + '  <button type="button" class="re-gif-add">Add</button>'
            + '</div>'
            + '<div class="re-gif-grid">' + grid + '</div>';
    }

    function buildAnimHtml() {
        var cells = ANIM_PRESETS.map(function (a) {
            return '<button type="button" class="re-anim-cell" data-anim="' + a.id + '">'
                 + '<span class="re-anim-icon re-anim-demo re-anim-in-' + a.id + '">' + a.icon + '</span>'
                 + '<span class="re-anim-label">' + a.label + '</span></button>';
        }).join('');
        return ''
            + '<div class="re-anim-help">Pick an entrance animation for the selected element.</div>'
            + '<div class="re-anim-grid">' + cells + '</div>'
            + '<div class="re-anim-help" style="margin-top:14px">Exit animation</div>'
            + '<div class="re-anim-grid re-anim-grid-out">' + ANIM_PRESETS.map(function (a) {
                return '<button type="button" class="re-anim-cell" data-anim-out="' + a.id + '">'
                     + '<span class="re-anim-icon">' + a.icon + '</span>'
                     + '<span class="re-anim-label">' + a.label + '</span></button>';
              }).join('') + '</div>';
    }

    function buildLayersHtml() {
        return ''
            + '<div class="re-layer-actions">'
            + '  <button type="button" data-layer="front">⬆ Bring to front</button>'
            + '  <button type="button" data-layer="forward">▲ Forward</button>'
            + '  <button type="button" data-layer="backward">▼ Backward</button>'
            + '  <button type="button" data-layer="back">⬇ Send to back</button>'
            + '  <button type="button" data-layer="duplicate">⎘ Duplicate</button>'
            + '  <button type="button" class="is-danger" data-layer="delete">🗑 Delete</button>'
            + '</div>'
            + '<div class="re-layer-list" data-list></div>';
    }

    function escAttr(s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

    function injectEditor() {
        if (document.getElementById('reelEditor')) {
            state.editor = document.getElementById('reelEditor');
            return;
        }
        var html = ''
            + '<div id="reelEditor" aria-hidden="true">'
            + '  <div class="re-stage">'
            + '    <div class="re-canvas" data-filter="none">'
            + '      <video class="re-video" playsinline muted loop autoplay></video>'
            + '      <div class="re-progress"><div class="re-progress-fill"></div></div>'
            + '      <div class="re-play-indicator"><svg viewBox="0 0 24 24"><path class="re-play-path" d="M8 5v14l11-7z"/></svg></div>'
            + '      <div class="re-overlay-layer"></div>'
            + '      <div class="re-music-chip"><div class="re-music-cover"></div>'
            + '        <div class="re-music-info"><strong></strong><small></small></div>'
            + '        <button type="button" class="re-music-remove" aria-label="Remove music">&times;</button>'
            + '      </div>'
            + '      <div class="re-toast"></div>'
            + '      <div class="re-upload-overlay">'
            + '        <div class="re-upload-ring"></div>'
            + '        <div class="re-upload-text">Publishing reel...</div>'
            + '        <div class="re-upload-bar"><div></div></div>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '  <div class="re-top-bar">'
            + '    <button type="button" class="re-icon-btn re-close" aria-label="Close">'
            + '      <svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg>'
            + '    </button>'
            + '    <div class="re-title">Edit reel</div>'
            + '    <button type="button" class="re-icon-btn re-mute" aria-label="Mute">'
            + '      <svg viewBox="0 0 24 24" class="re-mute-icon-on"><path d="M3 10v4h4l5 5V5l-5 5H3zm13.5 2A4.5 4.5 0 0 0 14 8.03v7.95A4.5 4.5 0 0 0 16.5 12zM14 3.23v2.06A6 6 0 0 1 14 18.71v2.06A8 8 0 0 0 14 3.23z"/></svg>'
            + '      <svg viewBox="0 0 24 24" class="re-mute-icon-off" style="display:none"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.42.05-.63zM19 12c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06a8.94 8.94 0 0 0 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>'
            + '    </button>'
            + '    <button type="button" class="re-icon-btn re-undo" aria-label="Undo">'
            + '      <svg viewBox="0 0 24 24"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62A6.97 6.97 0 0 1 12.5 11c3.04 0 5.7 1.74 7 4.27L21.4 14C19.85 10.46 16.46 8 12.5 8z"/></svg>'
            + '    </button>'
            + '  </div>'
            + '  <div class="re-toolbar">'
            + '    <button type="button" class="re-tool" data-action="music"><svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1 0 14 17V7h4V3z"/></svg>Music<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="text"><svg viewBox="0 0 24 24"><path d="M5 4v3h5.5v12h3V7H19V4z"/></svg>Text<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="emoji"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zM8.5 11a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM12 17.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>Emoji<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="gif"><svg viewBox="0 0 24 24"><path d="M11.5 9H13v6h-1.5zM9 9H6.5C5.67 9 5 9.67 5 10.5v3c0 .83.67 1.5 1.5 1.5H9c.55 0 1-.45 1-1v-2.5H8.5v2H6.5v-3H10V10c0-.55-.45-1-1-1zm10 1.5V9h-4.5v6H16v-2h2v-1.5h-2v-1z"/></svg>GIF<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="animate"><svg viewBox="0 0 24 24"><path d="M12 2L9 9l-7 .75L7.5 14l-2 8 6.5-4 6.5 4-2-8 5.5-4.25L17 9z"/></svg>Animate<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="layers"><svg viewBox="0 0 24 24"><path d="M12 2 1 9l11 7 11-7zm0 11.07L4.04 9 12 4.93 19.96 9zM1 14l11 7 11-7-2-1.27-9 5.73-9-5.73z"/></svg>Layers<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="filter"><svg viewBox="0 0 24 24"><path d="M5 7h14v2H5zm0 4h14v2H5zm0 4h14v2H5z"/></svg>Filter<span class="re-tool-badge"></span></button>'
            + '    <button type="button" class="re-tool" data-action="speed"><svg viewBox="0 0 24 24"><path d="M13 2.05V5.08A7.001 7.001 0 0 1 19 12a7 7 0 1 1-12.92-3.66l-2.16-2.16A9.96 9.96 0 0 0 2 12c0 5.52 4.48 10 10 10s10-4.48 10-10c0-5.18-3.95-9.45-9-9.95zM7.41 7.41L6 8.83l3.59 3.58L11 11l-3.59-3.59z"/></svg>Speed<span class="re-tool-badge"></span></button>'
            + '  </div>'
            + '  <div class="re-timeline" hidden>'
            + '    <div class="re-timeline-head">'
            + '      <div class="re-timeline-time"><span class="re-tcur">0:00</span> / <span class="re-tdur">0:00</span></div>'
            + '      <div class="re-timeline-actions">'
            + '        <button type="button" class="re-tl-toggle" aria-label="Hide timeline">×</button>'
            + '      </div>'
            + '    </div>'
            + '    <div class="re-timeline-tracks"></div>'
            + '    <div class="re-timeline-cursor"></div>'
            + '  </div>'
            + '  <div class="re-bottom">'
            + '    <button type="button" class="re-tl-show" aria-label="Show timeline" title="Timeline"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M3 4h18v2H3zm2 4h14v2H5zm-2 4h18v2H3zm2 4h14v2H5z"/></svg></button>'
            + '    <button type="button" class="re-publish-btn re-publish-secondary" data-mode="story">'
            + '      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3"/></svg>Reel + Story</button>'
            + '    <button type="button" class="re-publish-btn re-publish-primary" data-mode="reel">'
            + '      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>Upload Reel</button>'
            + '  </div>'
            + '  <div class="re-panel-backdrop"></div>'
            + '  <div class="re-panel re-panel-text" data-panel="text">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Add text</h4>'
            + '    <input type="text" class="re-text-input" placeholder="Type something..." maxlength="120">'
            + '    <div class="re-color-row">'
            +        TEXT_COLORS.map(function (c, i) { return '<span class="re-color-swatch' + (i === 0 ? ' is-active' : '') + '" data-color="' + c + '" style="background:' + c + '"></span>'; }).join('')
            + '    </div>'
            + '    <div class="re-bg-row">'
            + '      <button type="button" data-bg="0" class="is-active">No bg</button>'
            + '      <button type="button" data-bg="1">Dark</button>'
            + '      <button type="button" data-bg="2">White</button>'
            + '      <button type="button" data-bg="3">Pink</button>'
            + '    </div>'
            + '    <div class="re-font-row">'
            + '      <button type="button" data-font="" class="is-active">Default</button>'
            + '      <button type="button" data-font="serif">Serif</button>'
            + '      <button type="button" data-font="mono">Mono</button>'
            + '      <button type="button" data-font="cursive">Cursive</button>'
            + '    </div>'
            + '    <button type="button" class="re-publish-btn re-publish-primary re-text-add" style="height:42px">Add text</button>'
            + '  </div>'
            + '  <div class="re-panel re-panel-emoji" data-panel="emoji">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Pick an emoji</h4>'
            +      buildEmojiHtml()
            + '  </div>'
            + '  <div class="re-panel re-panel-gif" data-panel="gif">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Stickers &amp; GIFs</h4>'
            +      buildGifHtml()
            + '  </div>'
            + '  <div class="re-panel re-panel-animate" data-panel="animate">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Animation</h4>'
            +      buildAnimHtml()
            + '  </div>'
            + '  <div class="re-panel re-panel-layers" data-panel="layers">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Layers</h4>'
            +      buildLayersHtml()
            + '  </div>'
            + '  <div class="re-panel re-panel-filter" data-panel="filter">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Filters</h4>'
            +      buildFilterHtml()
            + '  </div>'
            + '  <div class="re-panel re-panel-speed" data-panel="speed">'
            + '    <button type="button" class="re-panel-close" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.4 6.3 6.3-6.3 6.29 1.4 1.42L12 14.83l6.3 6.29 1.4-1.42-6.3-6.29 6.3-6.3z"/></svg></button>'
            + '    <h4>Speed &amp; Volume</h4>'
            +      buildSpeedHtml()
            + '  </div>'
            + '</div>';

        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        document.body.appendChild(wrap.firstChild);

        state.editor = document.getElementById('reelEditor');
        state.canvas = state.editor.querySelector('.re-canvas');
        state.video  = state.editor.querySelector('.re-video');
        state.layer  = state.editor.querySelector('.re-overlay-layer');

        wireEditor();
    }

    function openEditor() {
        var src = document.querySelector('.uploaded_storie_image video');
        if (!src) return;
        var url = src.getAttribute('src') || (src.querySelector('source') && src.querySelector('source').src);
        if (!url) return;
        state.video.src = url;
        state.video.poster = src.getAttribute('poster') || '';
        state.video.muted = true;
        state.editor.classList.add('is-open');
        state.editor.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        requestAnimationFrame(function () { state.editor.classList.add('is-visible'); });
        try { state.video.play(); } catch (e) {}
        state.history = [];
        state.overlays = [];
        state.layer.innerHTML = '';
        state.canvas.setAttribute('data-filter', state.filter = 'none');
        state.video.playbackRate = state.speed = 1;
        state.video.volume = state.videoVolume = 0.5;
        syncMusicChip();
        updateToolBadges();
        renderTimeline();
    }

    function closeEditor() {
        state.editor.classList.remove('is-visible');
        setTimeout(function () {
            state.editor.classList.remove('is-open');
            state.editor.setAttribute('aria-hidden', 'true');
        }, 280);
        document.documentElement.style.overflow = '';
        try { state.video.pause(); } catch (e) {}
    }

    function wireEditor() {
        // Top-level click delegate
        state.editor.addEventListener('click', function (e) {
            var t = e.target;
            if (t.closest('.re-close')) { closeEditor(); return; }
            if (t.closest('.re-undo'))  { undoLast(); return; }
            if (t.closest('.re-mute'))  { toggleMute(); return; }
            if (t.closest('.re-panel-backdrop')) { closePanels(); return; }
            if (t.closest('.re-panel-close')) { closePanels(); return; }
            if (t.closest('.re-tl-show'))   { toggleTimeline(true);  return; }
            if (t.closest('.re-tl-toggle')) { toggleTimeline(false); return; }
            var tool = t.closest('.re-tool'); if (tool) { handleTool(tool.dataset.action); return; }
            if (t === state.layer || t === state.canvas) deselect();
            var pubBtn = t.closest('.re-publish-btn[data-mode]'); if (pubBtn) { publish(pubBtn.dataset.mode, pubBtn); return; }
        });

        // Tap video to play/pause
        state.video.addEventListener('click', function () {
            if (state.video.paused) { state.video.play(); flashIcon('pause'); }
            else { state.video.pause(); flashIcon('play'); }
        });

        // Progress bar + time-window visibility for overlays + timeline cursor
        var fill = state.editor.querySelector('.re-progress-fill');
        state.video.addEventListener('loadedmetadata', function () {
            state.duration = state.video.duration || 0;
            renderTimeline();
        });
        state.video.addEventListener('timeupdate', function () {
            var dur = state.video.duration || 0;
            var cur = state.video.currentTime || 0;
            if (dur > 0) fill.style.width = ((cur / dur) * 100) + '%';
            applyTimeVisibility(cur, dur);
            updateTimelineCursor(cur, dur);
        });

        // Text panel
        var textPanel = state.editor.querySelector('.re-panel-text');
        var textInput = textPanel.querySelector('.re-text-input');
        var activeColor = '#ffffff';
        var activeBg = '0';
        var activeFont = '';
        textPanel.addEventListener('click', function (e) {
            var sw = e.target.closest('.re-color-swatch');
            if (sw) {
                activeColor = sw.dataset.color;
                textPanel.querySelectorAll('.re-color-swatch').forEach(function (n) { n.classList.toggle('is-active', n === sw); });
            }
            var bg = e.target.closest('.re-bg-row button');
            if (bg) {
                activeBg = bg.dataset.bg;
                textPanel.querySelectorAll('.re-bg-row button').forEach(function (n) { n.classList.toggle('is-active', n === bg); });
            }
            var fn = e.target.closest('.re-font-row button');
            if (fn) {
                activeFont = fn.dataset.font || '';
                textPanel.querySelectorAll('.re-font-row button').forEach(function (n) { n.classList.toggle('is-active', n === fn); });
            }
            if (e.target.closest('.re-text-add')) {
                var v = (textInput.value || '').trim();
                if (!v) { toast('Type something first'); return; }
                snapshot();
                addOverlay({ type: 'text', text: v, color: activeColor, bg: activeBg, font: activeFont });
                textInput.value = '';
                closePanels();
            }
        });
        textInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                textPanel.querySelector('.re-text-add').click();
            }
        });

        // Emoji panel
        var emojiPanel = state.editor.querySelector('.re-panel-emoji');
        emojiPanel.addEventListener('click', function (e) {
            var tab = e.target.closest('.re-emoji-tab');
            if (tab) {
                var idx = tab.dataset.tab;
                emojiPanel.querySelectorAll('.re-emoji-tab').forEach(function (n) { n.classList.toggle('is-active', n === tab); });
                emojiPanel.querySelectorAll('.re-emoji-grid').forEach(function (n) { n.style.display = (n.dataset.grid === idx) ? '' : 'none'; });
                return;
            }
            var b = e.target.closest('button[data-emoji]');
            if (!b) return;
            snapshot();
            addOverlay({ type: 'emoji', text: b.dataset.emoji });
            closePanels();
        });

        // GIF panel
        var gifPanel = state.editor.querySelector('.re-panel-gif');
        gifPanel.addEventListener('click', function (e) {
            var cell = e.target.closest('.re-gif-cell');
            if (cell) {
                snapshot();
                addOverlay({ type: 'gif', src: cell.dataset.gif });
                closePanels();
                return;
            }
            if (e.target.closest('.re-gif-add')) {
                var input = gifPanel.querySelector('.re-gif-url');
                var u = (input.value || '').trim();
                if (!/^https?:\/\/.+\.(gif|png|webp|jpg|jpeg)(\?.*)?$/i.test(u)) {
                    toast('Paste a direct GIF/PNG/WebP URL');
                    return;
                }
                snapshot();
                addOverlay({ type: 'gif', src: u });
                input.value = '';
                closePanels();
            }
        });

        // Animate panel
        var animPanel = state.editor.querySelector('.re-panel-animate');
        animPanel.addEventListener('click', function (e) {
            var b1 = e.target.closest('[data-anim]');
            var b2 = e.target.closest('[data-anim-out]');
            if (b1) {
                if (state.selected == null) { toast('Select an element first'); return; }
                var ov = findOverlay(state.selected);
                if (!ov) return;
                snapshot();
                ov.anim = b1.dataset.anim;
                animPanel.querySelectorAll('[data-anim]').forEach(function (n) { n.classList.toggle('is-active', n === b1); });
                replayAnimationFor(ov);
                updateToolBadges();
            } else if (b2) {
                if (state.selected == null) { toast('Select an element first'); return; }
                var ov2 = findOverlay(state.selected);
                if (!ov2) return;
                snapshot();
                ov2.animOut = b2.dataset.animOut;
                animPanel.querySelectorAll('[data-anim-out]').forEach(function (n) { n.classList.toggle('is-active', n === b2); });
                updateToolBadges();
            }
        });

        // Layers panel
        var layerPanel = state.editor.querySelector('.re-panel-layers');
        layerPanel.addEventListener('click', function (e) {
            var b = e.target.closest('[data-layer]');
            if (b) {
                if (state.selected == null) { toast('Select an element first'); return; }
                snapshot();
                applyLayerAction(state.selected, b.dataset.layer);
                renderLayerList();
                if (b.dataset.layer === 'delete') closePanels();
                return;
            }
            var li = e.target.closest('[data-pick-id]');
            if (li) {
                selectOverlay(parseInt(li.dataset.pickId, 10));
                renderLayerList();
            }
        });

        // Filter panel
        var filterPanel = state.editor.querySelector('.re-panel-filter');
        filterPanel.addEventListener('click', function (e) {
            var th = e.target.closest('.re-filter-thumb');
            if (!th) return;
            filterPanel.querySelectorAll('.re-filter-thumb').forEach(function (n) { n.classList.toggle('is-active', n === th); });
            state.filter = th.dataset.filter;
            state.canvas.setAttribute('data-filter', state.filter);
            updateToolBadges();
            toast('Filter: ' + th.querySelector('span').textContent);
        });

        // Speed panel
        var speedPanel = state.editor.querySelector('.re-panel-speed');
        speedPanel.addEventListener('click', function (e) {
            var b = e.target.closest('button[data-speed]');
            if (!b) return;
            speedPanel.querySelectorAll('button[data-speed]').forEach(function (n) { n.classList.toggle('is-active', n === b); });
            state.speed = parseFloat(b.dataset.speed) || 1;
            try { state.video.playbackRate = state.speed; } catch (e2) {}
            updateToolBadges();
            toast('Speed: ' + b.textContent);
        });
        speedPanel.addEventListener('input', function (e) {
            if (e.target.matches('input[data-vol="video"]')) {
                state.videoVolume = (parseInt(e.target.value, 10) || 0) / 100;
                try { state.video.volume = state.videoVolume; } catch (e2) {}
            }
        });

        // Music chip remove
        state.editor.querySelector('.re-music-remove').addEventListener('click', function () {
            var meta = document.getElementById('reelMusicMeta');
            if (meta) {
                meta.value = '';
                try { meta.dispatchEvent(new CustomEvent('reel-music-change', { detail: null, bubbles: true })); } catch (e) {}
            }
            var holder = document.getElementById('rmlSelectedHolder');
            if (holder) { holder.style.display = 'none'; holder.innerHTML = ''; }
            syncMusicChip();
            updateToolBadges();
            toast('Music removed');
        });

        var meta = document.getElementById('reelMusicMeta');
        if (meta) {
            meta.addEventListener('reel-music-change', function () { syncMusicChip(); updateToolBadges(); });
            document.addEventListener('click', function (e) {
                if (e.target.closest('.rml-confirm')) setTimeout(function () { syncMusicChip(); updateToolBadges(); }, 80);
            });
        }

        // Wire timeline interactions
        wireTimeline();
    }

    function flashIcon(state) {
        var ind = document.querySelector('.re-play-indicator');
        if (!ind) return;
        var path = ind.querySelector('.re-play-path');
        if (path) {
            path.setAttribute('d', state === 'play' ? 'M8 5v14l11-7z' : 'M6 5h4v14H6zm8 0h4v14h-4z');
        }
        ind.classList.add('is-show');
        clearTimeout(flashIcon._t);
        flashIcon._t = setTimeout(function () { ind.classList.remove('is-show'); }, 500);
    }

    function toast(msg) {
        var t = state.editor.querySelector('.re-toast');
        if (!t) return;
        t.textContent = msg;
        t.classList.add('is-show');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { t.classList.remove('is-show'); }, 1400);
    }

    function toggleMute() {
        state.video.muted = !state.video.muted;
        var on  = state.editor.querySelector('.re-mute-icon-on');
        var off = state.editor.querySelector('.re-mute-icon-off');
        if (state.video.muted) { on.style.display = 'none'; off.style.display = ''; }
        else                   { on.style.display = '';     off.style.display = 'none'; }
    }

    function handleTool(action) {
        if (action === 'music') {
            var btn = document.querySelector('.rml-open');
            if (btn) btn.click();
            return;
        }
        if (action === 'animate' || action === 'layers') {
            // these need a selected overlay
            if (state.selected == null) {
                if (state.overlays.length === 0) {
                    toast('Add a text/emoji/GIF first');
                    return;
                }
                selectOverlay(state.overlays[state.overlays.length - 1].id);
            }
            if (action === 'layers') renderLayerList();
            if (action === 'animate') syncAnimPanel();
        }
        openPanel(action);
    }

    function openPanel(name) {
        closePanels();
        var p = state.editor.querySelector('.re-panel-' + name);
        if (p) p.classList.add('is-open');
        state.editor.querySelector('.re-panel-backdrop').classList.add('is-show');
        if (name === 'text') {
            var i = p.querySelector('.re-text-input');
            setTimeout(function () { i && i.focus(); }, 80);
        }
    }
    function closePanels() {
        state.editor.querySelectorAll('.re-panel').forEach(function (p) { p.classList.remove('is-open'); });
        state.editor.querySelector('.re-panel-backdrop').classList.remove('is-show');
    }

    function snapshot() {
        state.history.push(JSON.stringify(state.overlays));
        if (state.history.length > 30) state.history.shift();
    }

    function findOverlay(id) {
        for (var i = 0; i < state.overlays.length; i++) if (state.overlays[i].id === id) return state.overlays[i];
        return null;
    }

    function nextZ() {
        var max = 0;
        state.overlays.forEach(function (o) { if ((o.z || 0) > max) max = o.z || 0; });
        return max + 1;
    }

    function addOverlay(opts) {
        var o = Object.assign({
            id: state.nextId++,
            x: 0.5, y: 0.5,
            scale: 1, rot: 0,
            color: '#ffffff', bg: '0', font: '',
            anim: 'fade', animOut: 'none',
            start: 0, end: state.duration || 0,
            z: nextZ(),
            src: '',
        }, opts);
        if (!o.end || o.end <= 0) o.end = Math.max(o.start + 1, state.duration || 5);
        state.overlays.push(o);
        renderOverlay(o, true);
        selectOverlay(o.id);
        updateToolBadges();
        renderTimeline();
    }

    function renderOverlay(o, animate) {
        var el = document.createElement('div');
        el.className = 're-overlay re-overlay-' + o.type;
        if (animate && o.anim && o.anim !== 'none') el.classList.add('re-anim-in-' + o.anim);
        el.dataset.id = o.id;
        el.style.left = (o.x * 100) + '%';
        el.style.top  = (o.y * 100) + '%';
        el.style.zIndex = String(o.z || 1);
        el.style.setProperty('--rot', (o.rot || 0) + 'deg');
        el.style.setProperty('--scale', String(o.scale || 1));

        var inner;
        if (o.type === 'gif') {
            inner = document.createElement('img');
            inner.className = 're-overlay-gif';
            inner.src = o.src;
            inner.draggable = false;
            inner.alt = '';
            inner.style.width = (140 * (o.scale || 1)) + 'px';
        } else {
            inner = document.createElement('div');
            inner.className = 're-overlay-' + o.type;
            inner.textContent = o.text;
            if (o.type === 'text') {
                inner.style.color = o.color || '#fff';
                inner.dataset.bg = o.bg || '0';
                if (o.font) inner.dataset.font = o.font;
                inner.style.fontSize = (26 * o.scale) + 'px';
            } else {
                inner.style.fontSize = (64 * o.scale) + 'px';
            }
        }
        el.appendChild(inner);

        var del = document.createElement('button');
        del.type = 'button'; del.className = 're-delete'; del.innerHTML = '&times;';
        del.addEventListener('click', function (e) { e.stopPropagation(); snapshot(); removeOverlay(o.id); });
        el.appendChild(del);

        var rs = document.createElement('div'); rs.className = 're-resize'; el.appendChild(rs);
        var rt = document.createElement('div'); rt.className = 're-rotate'; el.appendChild(rt);

        state.layer.appendChild(el);
        wireOverlay(el, o, rs, rt);
    }

    function replayAnimationFor(o) {
        var el = state.layer.querySelector('[data-id="' + o.id + '"]');
        if (!el) return;
        // Strip prior in-classes
        ANIM_PRESETS.forEach(function (a) { el.classList.remove('re-anim-in-' + a.id); });
        // Force reflow then re-add
        void el.offsetWidth;
        if (o.anim && o.anim !== 'none') el.classList.add('re-anim-in-' + o.anim);
    }

    function wireOverlay(el, o, rsHandle, rtHandle) {
        // === Multi-touch state for the element body ===
        var dragging = false;
        var startPoint = null, startBase = null;
        var pinching = false;
        var pinchStartDist = 0, pinchStartAngle = 0, pinchBaseScale = 1, pinchBaseRot = 0;

        var onDown = function (e) {
            if (e.target === rsHandle || e.target === rtHandle) return;
            if (e.target.classList && e.target.classList.contains('re-delete')) return;
            e.preventDefault();
            selectOverlay(o.id);

            if (e.touches && e.touches.length === 2) {
                pinching = true;
                pinchStartDist  = touchDist(e);
                pinchStartAngle = touchAngle(e);
                pinchBaseScale  = o.scale || 1;
                pinchBaseRot    = o.rot || 0;
            } else {
                dragging = true;
                el.classList.add('is-dragging');
                var p = pointer(e);
                startPoint = p;
                startBase = { x: o.x, y: o.y };
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('mouseup', onUp);
            document.addEventListener('touchend', onUp);
            snapshot();
        };
        var onMove = function (e) {
            e.preventDefault();
            if (e.touches && e.touches.length === 2) {
                if (!pinching) {
                    pinching = true;
                    pinchStartDist  = touchDist(e);
                    pinchStartAngle = touchAngle(e);
                    pinchBaseScale  = o.scale || 1;
                    pinchBaseRot    = o.rot || 0;
                    return;
                }
                var d = touchDist(e);
                var a = touchAngle(e);
                if (pinchStartDist > 0) {
                    var nextScale = clamp(pinchBaseScale * (d / pinchStartDist), 0.3, 6);
                    o.scale = nextScale;
                    applyOverlayScale(el, o);
                }
                var deg = (a - pinchStartAngle) * 180 / Math.PI;
                o.rot = (pinchBaseRot + deg) % 360;
                el.style.setProperty('--rot', o.rot + 'deg');
                return;
            }
            if (!dragging) return;
            var p = pointer(e);
            var rect = state.canvas.getBoundingClientRect();
            o.x = clamp(startBase.x + (p.x - startPoint.x) / rect.width,  0.02, 0.98);
            o.y = clamp(startBase.y + (p.y - startPoint.y) / rect.height, 0.05, 0.95);
            el.style.left = (o.x * 100) + '%';
            el.style.top  = (o.y * 100) + '%';
        };
        var onUp = function () {
            dragging = false;
            pinching = false;
            el.classList.remove('is-dragging');
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('mouseup', onUp);
            document.removeEventListener('touchend', onUp);
        };
        el.addEventListener('mousedown', onDown);
        el.addEventListener('touchstart', onDown, { passive: false });

        // Wheel = scale
        el.addEventListener('wheel', function (e) {
            e.preventDefault();
            o.scale = clamp((o.scale || 1) + (e.deltaY < 0 ? 0.08 : -0.08), 0.3, 6);
            applyOverlayScale(el, o);
        }, { passive: false });

        // Resize handle
        var rsStart, rsBaseScale;
        rsHandle.addEventListener('mousedown', rsDown);
        rsHandle.addEventListener('touchstart', rsDown, { passive: false });
        function rsDown(e) {
            e.preventDefault(); e.stopPropagation();
            snapshot();
            rsStart = pointer(e);
            rsBaseScale = o.scale || 1;
            document.addEventListener('mousemove', rsMove);
            document.addEventListener('touchmove', rsMove, { passive: false });
            document.addEventListener('mouseup', rsUp);
            document.addEventListener('touchend', rsUp);
        }
        function rsMove(e) {
            e.preventDefault();
            var p = pointer(e);
            o.scale = clamp(rsBaseScale + (p.y - rsStart.y) / 120, 0.3, 6);
            applyOverlayScale(el, o);
        }
        function rsUp() {
            document.removeEventListener('mousemove', rsMove);
            document.removeEventListener('touchmove', rsMove);
            document.removeEventListener('mouseup', rsUp);
            document.removeEventListener('touchend', rsUp);
        }

        // Rotate handle
        var rtStart, rtBaseRot;
        rtHandle.addEventListener('mousedown', rtDown);
        rtHandle.addEventListener('touchstart', rtDown, { passive: false });
        function rtDown(e) {
            e.preventDefault(); e.stopPropagation();
            snapshot();
            rtStart = pointer(e);
            rtBaseRot = o.rot || 0;
            document.addEventListener('mousemove', rtMove);
            document.addEventListener('touchmove', rtMove, { passive: false });
            document.addEventListener('mouseup', rtUp);
            document.addEventListener('touchend', rtUp);
        }
        function rtMove(e) {
            e.preventDefault();
            var p = pointer(e);
            o.rot = (rtBaseRot + (p.x - rtStart.x)) % 360;
            el.style.setProperty('--rot', o.rot + 'deg');
        }
        function rtUp() {
            document.removeEventListener('mousemove', rtMove);
            document.removeEventListener('touchmove', rtMove);
            document.removeEventListener('mouseup', rtUp);
            document.removeEventListener('touchend', rtUp);
        }
    }

    function applyOverlayScale(el, o) {
        el.style.setProperty('--scale', String(o.scale || 1));
        if (o.type === 'gif') {
            var img = el.querySelector('.re-overlay-gif');
            if (img) img.style.width = (140 * (o.scale || 1)) + 'px';
        } else {
            var inner = el.querySelector('.re-overlay-' + o.type);
            if (inner) inner.style.fontSize = ((o.type === 'emoji' ? 64 : 26) * (o.scale || 1)) + 'px';
        }
    }

    function pointer(e) {
        if (e.touches && e.touches.length) return { x: e.touches[0].clientX, y: e.touches[0].clientY };
        if (e.changedTouches && e.changedTouches.length) return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
        return { x: e.clientX, y: e.clientY };
    }
    function touchDist(e) {
        var a = e.touches[0], b = e.touches[1];
        return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
    }
    function touchAngle(e) {
        var a = e.touches[0], b = e.touches[1];
        return Math.atan2(b.clientY - a.clientY, b.clientX - a.clientX);
    }
    function clamp(v, lo, hi) { return Math.min(hi, Math.max(lo, v)); }

    function selectOverlay(id) {
        state.selected = id;
        state.layer.querySelectorAll('.re-overlay').forEach(function (n) { n.classList.toggle('is-selected', String(n.dataset.id) === String(id)); });
        renderTimelineSelection();
        syncAnimPanel();
    }
    function deselect() {
        state.selected = null;
        state.layer.querySelectorAll('.re-overlay').forEach(function (n) { n.classList.remove('is-selected'); });
        renderTimelineSelection();
    }
    function removeOverlay(id) {
        state.overlays = state.overlays.filter(function (o) { return o.id !== id; });
        var n = state.layer.querySelector('[data-id="' + id + '"]');
        if (n) n.remove();
        state.selected = null;
        updateToolBadges();
        renderTimeline();
    }

    function applyLayerAction(id, action) {
        var ov = findOverlay(id);
        if (!ov) return;
        var sorted = state.overlays.slice().sort(function (a, b) { return (a.z || 0) - (b.z || 0); });
        var idx = sorted.indexOf(ov);
        if (action === 'front')    ov.z = nextZ();
        else if (action === 'back')    ov.z = (sorted[0] && sorted[0].z ? sorted[0].z - 1 : 0);
        else if (action === 'forward') ov.z = (ov.z || 0) + 1;
        else if (action === 'backward') ov.z = Math.max(0, (ov.z || 0) - 1);
        else if (action === 'duplicate') {
            var clone = JSON.parse(JSON.stringify(ov));
            clone.id = state.nextId++;
            clone.x = clamp((clone.x || 0.5) + 0.05, 0.05, 0.95);
            clone.y = clamp((clone.y || 0.5) + 0.05, 0.05, 0.95);
            clone.z = nextZ();
            state.overlays.push(clone);
            renderOverlay(clone, true);
            selectOverlay(clone.id);
            return;
        }
        else if (action === 'delete') { removeOverlay(id); return; }
        var el = state.layer.querySelector('[data-id="' + id + '"]');
        if (el) el.style.zIndex = String(ov.z || 1);
    }

    function renderLayerList() {
        var box = state.editor.querySelector('.re-layer-list');
        if (!box) return;
        var sorted = state.overlays.slice().sort(function (a, b) { return (b.z || 0) - (a.z || 0); });
        if (!sorted.length) { box.innerHTML = '<div class="re-layer-empty">No elements yet</div>'; return; }
        box.innerHTML = sorted.map(function (o) {
            var label = o.type === 'gif' ? 'GIF' : (o.type === 'emoji' ? (o.text || '😀') : (o.text || 'Text'));
            var icon  = o.type === 'gif' ? '🖼️' : (o.type === 'emoji' ? o.text : '🅣');
            return '<div class="re-layer-row' + (state.selected === o.id ? ' is-active' : '') + '" data-pick-id="' + o.id + '">'
                 + '<span class="re-layer-ico">' + icon + '</span>'
                 + '<span class="re-layer-name">' + escAttr(label) + '</span>'
                 + '<span class="re-layer-z">z=' + (o.z || 0) + '</span></div>';
        }).join('');
    }

    function syncAnimPanel() {
        var p = state.editor && state.editor.querySelector('.re-panel-animate');
        if (!p) return;
        var ov = state.selected != null ? findOverlay(state.selected) : null;
        var inAnim  = ov && ov.anim    || 'fade';
        var outAnim = ov && ov.animOut || 'none';
        p.querySelectorAll('[data-anim]').forEach(function (n) { n.classList.toggle('is-active', n.dataset.anim === inAnim); });
        p.querySelectorAll('[data-anim-out]').forEach(function (n) { n.classList.toggle('is-active', n.dataset.animOut === outAnim); });
    }

    function applyTimeVisibility(cur, dur) {
        if (!dur || dur <= 0) return;
        state.overlays.forEach(function (o) {
            var el = state.layer.querySelector('[data-id="' + o.id + '"]');
            if (!el) return;
            var s = Math.max(0, +o.start || 0);
            var e = Math.min(dur, +o.end || dur);
            var visible = cur >= s && cur <= e;
            el.classList.toggle('is-out-of-window', !visible);
        });
    }

    function undoLast() {
        if (!state.history.length) {
            if (state.overlays.length) {
                removeOverlay(state.overlays[state.overlays.length - 1].id);
            }
            return;
        }
        var prev = state.history.pop();
        try {
            state.overlays = JSON.parse(prev) || [];
            state.layer.innerHTML = '';
            state.overlays.forEach(function (o) { renderOverlay(o, false); });
            state.selected = null;
            updateToolBadges();
            renderTimeline();
            toast('Undone');
        } catch (e) {}
    }

    function syncMusicChip() {
        var chip = state.editor.querySelector('.re-music-chip');
        var meta = document.getElementById('reelMusicMeta');
        if (!chip || !meta || !meta.value) {
            chip && chip.classList.remove('is-visible');
            return;
        }
        try {
            var m = JSON.parse(meta.value);
            chip.querySelector('.re-music-cover').style.backgroundImage = m.cover_url ? 'url(' + m.cover_url + ')' : '';
            chip.querySelector('strong').textContent = m.title || '';
            chip.querySelector('small').textContent  = m.artist || '';
            chip.classList.add('is-visible');
        } catch (e) {
            chip.classList.remove('is-visible');
        }
    }

    function updateToolBadges() {
        var tools = state.editor.querySelectorAll('.re-tool');
        var meta  = document.getElementById('reelMusicMeta');
        var hasMusic = !!(meta && meta.value);
        var hasText  = state.overlays.some(function (o) { return o.type === 'text'; });
        var hasEmoji = state.overlays.some(function (o) { return o.type === 'emoji'; });
        var hasGif   = state.overlays.some(function (o) { return o.type === 'gif'; });
        var hasAnim  = state.overlays.some(function (o) { return (o.anim && o.anim !== 'none' && o.anim !== 'fade') || (o.animOut && o.animOut !== 'none'); });
        var hasLayers = state.overlays.length > 0;
        var hasFilter = state.filter && state.filter !== 'none';
        var hasSpeed  = state.speed && state.speed !== 1;
        tools.forEach(function (t) {
            var a = t.dataset.action;
            t.classList.toggle('has-value',
                (a === 'music' && hasMusic) ||
                (a === 'text' && hasText) ||
                (a === 'emoji' && hasEmoji) ||
                (a === 'gif' && hasGif) ||
                (a === 'animate' && hasAnim) ||
                (a === 'layers' && hasLayers) ||
                (a === 'filter' && hasFilter) ||
                (a === 'speed' && hasSpeed)
            );
        });
    }

    function wireGlobalKeyboard() {
        document.addEventListener('keydown', function (e) {
            if (!state.editor || !state.editor.classList.contains('is-open')) return;
            var tag = (e.target && e.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA') return;
            if (e.key === 'Escape') { closeEditor(); }
            else if (e.key === 'Delete' || e.key === 'Backspace') {
                if (state.selected != null) { snapshot(); removeOverlay(state.selected); }
            } else if ((e.key === 'z' || e.key === 'Z') && (e.ctrlKey || e.metaKey)) {
                e.preventDefault(); undoLast();
            } else if (e.key === ']') {
                if (state.selected != null) { snapshot(); applyLayerAction(state.selected, 'forward'); }
            } else if (e.key === '[') {
                if (state.selected != null) { snapshot(); applyLayerAction(state.selected, 'backward'); }
            }
        });
    }

    /* =================== TIMELINE =================== */
    function fmtTime(s) {
        s = Math.max(0, Math.floor(s || 0));
        var m = Math.floor(s / 60); var r = s % 60;
        return m + ':' + (r < 10 ? '0' + r : r);
    }
    function toggleTimeline(show) {
        var tl = state.editor.querySelector('.re-timeline');
        if (!tl) return;
        if (show === undefined) show = tl.hasAttribute('hidden');
        if (show) { tl.removeAttribute('hidden'); renderTimeline(); }
        else      { tl.setAttribute('hidden', ''); }
    }
    function renderTimeline() {
        var tl = state.editor && state.editor.querySelector('.re-timeline');
        if (!tl) return;
        var dur = state.duration || state.video.duration || 0;
        tl.querySelector('.re-tdur').textContent = fmtTime(dur);
        var box = tl.querySelector('.re-timeline-tracks');
        if (!state.overlays.length) {
            box.innerHTML = '<div class="re-tl-empty">Add text, emoji or GIF — drag the bar handles to set when each appears.</div>';
            return;
        }
        box.innerHTML = state.overlays.map(function (o) {
            var s = Math.max(0, Math.min(dur || 1, +o.start || 0));
            var e = Math.max(s + 0.1, Math.min(dur || 1, +o.end || dur || 1));
            var leftPct  = dur > 0 ? (s / dur) * 100 : 0;
            var widthPct = dur > 0 ? ((e - s) / dur) * 100 : 100;
            var label = o.type === 'gif' ? '🖼 GIF' : (o.type === 'emoji' ? (o.text || '😀') : ('🅣 ' + (o.text || '').slice(0, 14)));
            return '<div class="re-tl-track' + (state.selected === o.id ? ' is-active' : '') + '" data-id="' + o.id + '">'
                 + '<div class="re-tl-bar" style="left:' + leftPct + '%;width:' + widthPct + '%">'
                 + '<span class="re-tl-handle re-tl-h-l" data-edge="l"></span>'
                 + '<span class="re-tl-label">' + escAttr(label) + '</span>'
                 + '<span class="re-tl-handle re-tl-h-r" data-edge="r"></span>'
                 + '</div></div>';
        }).join('');
        renderTimelineSelection();
        updateTimelineCursor(state.video.currentTime || 0, dur);
    }
    function renderTimelineSelection() {
        var tl = state.editor && state.editor.querySelector('.re-timeline');
        if (!tl) return;
        tl.querySelectorAll('.re-tl-track').forEach(function (n) {
            n.classList.toggle('is-active', String(n.dataset.id) === String(state.selected));
        });
    }
    function updateTimelineCursor(cur, dur) {
        var tl = state.editor && state.editor.querySelector('.re-timeline');
        if (!tl || tl.hasAttribute('hidden')) return;
        var c = tl.querySelector('.re-timeline-cursor');
        var t = tl.querySelector('.re-tcur');
        if (t) t.textContent = fmtTime(cur);
        if (c && dur > 0) c.style.left = ((cur / dur) * 100) + '%';
    }
    function wireTimeline() {
        var tl = state.editor.querySelector('.re-timeline');
        if (!tl) return;
        var box = tl.querySelector('.re-timeline-tracks');

        box.addEventListener('click', function (e) {
            var track = e.target.closest('.re-tl-track');
            if (!track) return;
            if (e.target.closest('.re-tl-handle')) return;
            selectOverlay(parseInt(track.dataset.id, 10));
        });

        // Drag handles to adjust start/end
        var dragging = null; // { id, edge, baseS, baseE, x0, dur, w }
        function onDown(e) {
            var h = e.target.closest('.re-tl-handle');
            if (!h) return;
            e.preventDefault(); e.stopPropagation();
            var track = h.closest('.re-tl-track');
            var id = parseInt(track.dataset.id, 10);
            var ov = findOverlay(id);
            if (!ov) return;
            selectOverlay(id);
            snapshot();
            var rect = box.getBoundingClientRect();
            dragging = {
                id: id,
                edge: h.dataset.edge,
                baseS: ov.start || 0,
                baseE: ov.end || state.duration || 0,
                x0: pointer(e).x,
                dur: state.duration || state.video.duration || 0,
                w: rect.width,
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('mouseup', onUp);
            document.addEventListener('touchend', onUp);
        }
        function onMove(e) {
            if (!dragging) return;
            e.preventDefault();
            var ov = findOverlay(dragging.id); if (!ov) return;
            var dx = pointer(e).x - dragging.x0;
            var deltaT = dragging.w > 0 ? (dx / dragging.w) * dragging.dur : 0;
            if (dragging.edge === 'l') {
                ov.start = clamp(dragging.baseS + deltaT, 0, (ov.end || dragging.dur) - 0.2);
            } else {
                ov.end = clamp(dragging.baseE + deltaT, (ov.start || 0) + 0.2, dragging.dur);
            }
            renderTimeline();
        }
        function onUp() {
            dragging = null;
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('mouseup', onUp);
            document.removeEventListener('touchend', onUp);
        }
        box.addEventListener('mousedown', onDown);
        box.addEventListener('touchstart', onDown, { passive: false });
    }

    /* =================== PUBLISH =================== */
    function publish(mode, btn) {
        var fileId = (document.getElementById('uploadVal') || {}).value || '';
        if (!fileId) { toast('Video missing'); return; }
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';
        var label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="re-spinner"></span> Publishing...';

        var overlay = state.editor.querySelector('.re-upload-overlay');
        var bar = overlay.querySelector('.re-upload-bar > div');
        var msg = overlay.querySelector('.re-upload-text');
        overlay.classList.add('is-show');
        bar.style.width = '5%';
        msg.textContent = mode === 'story' ? 'Publishing to Reels & Story...' : 'Publishing reel...';

        var text = (document.getElementById('newPostT') || {}).value || '';
        var point = (document.getElementById('point') || {}).value || '';

        var dur = state.duration || state.video.duration || 0;
        var overlaysOut = state.overlays.map(function (o) {
            return {
                type: o.type,
                text: String(o.text || ''),
                src:  String(o.src || ''),
                x: +(+o.x).toFixed(4),
                y: +(+o.y).toFixed(4),
                scale: +(+o.scale).toFixed(3),
                rot:   +(+(o.rot || 0)).toFixed(2),
                color: o.color || '',
                bg:    o.bg || '0',
                font:  o.font || '',
                anim:    o.anim || 'none',
                animOut: o.animOut || 'none',
                start:   +(+(o.start || 0)).toFixed(2),
                end:     +(+(o.end || dur || 0)).toFixed(2),
                z:       +(o.z || 0)
            };
        });

        var fd = new FormData();
        fd.append('f', 'insertNewReel');
        fd.append('txt', text);
        fd.append('file', fileId);
        fd.append('point', point);
        fd.append('overlays', JSON.stringify(overlaysOut));
        fd.append('filter', state.filter || 'none');
        fd.append('speed',  String(state.speed || 1));
        var meta = document.getElementById('reelMusicMeta');
        if (meta && meta.value) fd.append('music_meta', meta.value);
        if (mode === 'story') fd.append('also_story', '1');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', siteRoot + 'requests/request.php', true);
        xhr.withCredentials = true;
        xhr.upload.onprogress = function (ev) {
            if (ev.lengthComputable) {
                var pct = Math.max(5, Math.min(95, (ev.loaded / ev.total) * 100));
                bar.style.width = pct + '%';
            }
        };
        xhr.onload = function () {
            bar.style.width = '100%';
            var resp = (xhr.responseText || '').trim();
            if (xhr.status >= 200 && xhr.status < 300 && resp.indexOf('REELS_ID:') === 0) {
                msg.textContent = 'Done! Opening reel...';
                var newId = resp.replace('REELS_ID:', '').trim();
                setTimeout(function () { window.location.href = siteRoot + 'reels/' + newId; }, 350);
                return;
            }
            failPublish(btn, label, overlay, resp);
        };
        xhr.onerror = function () { failPublish(btn, label, overlay, 'Network error'); };
        xhr.send(fd);
    }

    function failPublish(btn, label, overlay, msg) {
        btn.disabled = false;
        btn.innerHTML = label;
        delete btn.dataset.busy;
        overlay.classList.remove('is-show');
        toast(msg && msg.length < 80 ? msg : 'Could not publish, please retry');
    }
})();
