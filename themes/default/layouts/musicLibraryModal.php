<?php /**
 * Reels Music Library modal.
 * Rendered once per page; controlled by themes/default/js/musicLibrary.js
 */ ?>
<div id="reelsMusicModal" class="rml-modal" aria-hidden="true">
  <div class="rml-overlay" data-rml-close="1"></div>
  <div class="rml-sheet" role="dialog" aria-modal="true" aria-labelledby="rmlTitle">
    <header class="rml-head">
      <button type="button" class="rml-back" aria-label="<?php echo iN_HelpSecure($LANG['close'] ?? 'Close'); ?>" data-rml-close="1">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>
      </button>
      <h2 id="rmlTitle" class="rml-title"><?php echo iN_HelpSecure($LANG['music_add_music'] ?? 'Add music'); ?></h2>
      <div class="rml-head-spacer"></div>
    </header>

    <div class="rml-search">
      <svg class="rml-search-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
      <input type="text" id="rmlSearchInput" class="rml-search-input" placeholder="<?php echo iN_HelpSecure($LANG['music_search_placeholder'] ?? 'Search songs, artists, sounds'); ?>" autocomplete="off">
      <button type="button" id="rmlClearSearch" class="rml-clear" aria-label="Clear">×</button>
    </div>

    <div class="rml-cats" id="rmlCats"></div>

    <div class="rml-body">
      <div class="rml-loading" id="rmlLoading"><span class="rml-spinner"></span></div>
      <ul class="rml-list" id="rmlList"></ul>
      <div class="rml-empty" id="rmlEmpty" style="display:none;">
        <?php echo iN_HelpSecure($LANG['music_no_results'] ?? 'No tracks found.'); ?>
      </div>
    </div>

    <!-- Trim editor view -->
    <section class="rml-trim" id="rmlTrim" hidden>
      <div class="rml-trim-head">
        <button type="button" class="rml-back" id="rmlTrimBack" aria-label="Back">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>
        </button>
        <div class="rml-trim-meta">
          <div class="rml-trim-title" id="rmlTrimTitle"></div>
          <div class="rml-trim-artist" id="rmlTrimArtist"></div>
        </div>
        <button type="button" class="rml-use-btn" id="rmlConfirm"><?php echo iN_HelpSecure($LANG['music_use'] ?? 'Use'); ?></button>
      </div>

      <div class="rml-cover-wrap">
        <img class="rml-cover" id="rmlTrimCover" alt="" />
        <div class="rml-disc"></div>
      </div>

      <div class="rml-wave-wrap">
        <div class="rml-wave" id="rmlWave"></div>
        <div class="rml-wave-region" id="rmlWaveRegion"></div>
      </div>

      <div class="rml-trim-controls">
        <button type="button" class="rml-play-btn" id="rmlTrimPlay" aria-label="Play">
          <svg class="rml-icon-play" viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
          <svg class="rml-icon-pause" viewBox="0 0 24 24" width="22" height="22" fill="currentColor" style="display:none"><path d="M6 19h4V5H6zM14 5v14h4V5z"/></svg>
        </button>
        <div class="rml-trim-time">
          <span id="rmlTrimStart">0:00</span> · <span id="rmlTrimLen">0:15</span>
        </div>
      </div>

      <div class="rml-mix">
        <label class="rml-mix-row">
          <span><?php echo iN_HelpSecure($LANG['music_volume_music'] ?? 'Music volume'); ?></span>
          <input type="range" id="rmlVolMusic" min="0" max="100" value="80">
        </label>
        <label class="rml-mix-row">
          <span><?php echo iN_HelpSecure($LANG['music_volume_video'] ?? 'Original sound'); ?></span>
          <input type="range" id="rmlVolVideo" min="0" max="100" value="50">
        </label>
      </div>
    </section>
  </div>
</div>
