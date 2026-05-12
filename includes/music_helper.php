<?php
/**
 * Music integration helper.
 *
 * Provides:
 *  - Schema bootstrap (adds music_* columns to i_posts on demand).
 *  - Provider-agnostic search/trending/track lookup.
 *  - Default provider: Jamendo (https://api.jamendo.com) using JAMENDO_CLIENT_ID
 *    from constants/env/.env. Falls back to a tiny built-in CC0 sample list so
 *    the UI is functional out of the box.
 */

if (!function_exists('dizzy_music_columns_present')) {
    /**
     * Check (and cache) whether i_posts has the music_* columns.
     * Returns true once columns exist, false otherwise.
     */
    function dizzy_music_columns_present(bool $refresh = false): bool
    {
        static $present = null;
        if (!$refresh && $present !== null) {
            return $present;
        }
        try {
            $row = DB::one("SHOW COLUMNS FROM i_posts LIKE 'music_track_id'");
            $present = !empty($row);
        } catch (Throwable $e) {
            $present = false;
        }
        return $present;
    }
}

if (!function_exists('dizzy_ensure_music_columns')) {
    /**
     * Ensure the music_* columns exist on i_posts. Adds them if missing.
     * Safe to call repeatedly (no-op once columns exist).
     */
    function dizzy_ensure_music_columns(): bool
    {
        // Always make sure the admin-configurable Jamendo client id column exists.
        // (Runs even when the i_posts music_* columns already exist.)
        try {
            $col = DB::one("SHOW COLUMNS FROM i_configurations LIKE 'jamendo_client_id'");
            if (empty($col)) {
                DB::exec("ALTER TABLE i_configurations ADD COLUMN jamendo_client_id VARCHAR(64) NOT NULL DEFAULT ''");
            }
        } catch (Throwable $e) {
            error_log('[music] add jamendo_client_id column failed: ' . $e->getMessage());
        }
        if (dizzy_music_columns_present()) {
            return true;
        }
        try {
            DB::exec("ALTER TABLE i_posts
                ADD COLUMN music_track_id VARCHAR(64) DEFAULT NULL,
                ADD COLUMN music_provider VARCHAR(32) DEFAULT NULL,
                ADD COLUMN music_title VARCHAR(255) DEFAULT NULL,
                ADD COLUMN music_artist VARCHAR(255) DEFAULT NULL,
                ADD COLUMN music_url TEXT,
                ADD COLUMN music_cover_url TEXT,
                ADD COLUMN music_start_time DECIMAL(8,3) NOT NULL DEFAULT 0,
                ADD COLUMN music_duration DECIMAL(8,3) NOT NULL DEFAULT 0,
                ADD COLUMN music_volume DECIMAL(4,3) NOT NULL DEFAULT 0.800,
                ADD COLUMN music_video_volume DECIMAL(4,3) NOT NULL DEFAULT 0.500");
            return dizzy_music_columns_present(true);
        } catch (Throwable $e) {
            error_log('[music] ensure_columns failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('dizzy_ensure_overlays_column')) {
    /**
     * Ensure i_posts has a `post_overlays` JSON column (stored as TEXT for
     * cross-MySQL compatibility) used by the new full-screen reel editor for
     * text/emoji overlays.
     */
    function dizzy_ensure_overlays_column(): bool
    {
        static $ok = null;
        if ($ok !== null) return $ok;
        try {
            $col = DB::one("SHOW COLUMNS FROM i_posts LIKE 'post_overlays'");
            if (empty($col)) {
                DB::exec("ALTER TABLE i_posts ADD COLUMN post_overlays MEDIUMTEXT NULL");
            }
            $ok = true;
        } catch (Throwable $e) {
            error_log('[overlays] ensure_column failed: ' . $e->getMessage());
            $ok = false;
        }
        return $ok;
    }
}

if (!function_exists('dizzy_overlays_column_present')) {
    function dizzy_overlays_column_present(): bool
    {
        static $present = null;
        if ($present !== null) return $present;
        try {
            $row = DB::one("SHOW COLUMNS FROM i_posts LIKE 'post_overlays'");
            $present = !empty($row);
        } catch (Throwable $e) {
            $present = false;
        }
        return $present;
    }
}

if (!function_exists('dizzy_ensure_reel_extras_columns')) {
    /**
     * Ensure i_posts has post_filter (VARCHAR) and post_video_speed (DECIMAL)
     * columns used by the polished reel editor.
     */
    function dizzy_ensure_reel_extras_columns(): bool
    {
        static $ok = null;
        if ($ok !== null) return $ok;
        try {
            $col = DB::one("SHOW COLUMNS FROM i_posts LIKE 'post_filter'");
            if (empty($col)) {
                DB::exec("ALTER TABLE i_posts ADD COLUMN post_filter VARCHAR(20) NULL");
            }
            $col = DB::one("SHOW COLUMNS FROM i_posts LIKE 'post_video_speed'");
            if (empty($col)) {
                DB::exec("ALTER TABLE i_posts ADD COLUMN post_video_speed DECIMAL(4,2) NULL DEFAULT 1.00");
            }
            $ok = true;
        } catch (Throwable $e) {
            error_log('[reel-extras] ensure_columns failed: ' . $e->getMessage());
            $ok = false;
        }
        return $ok;
    }
}

if (!function_exists('dizzy_reel_extras_columns_present')) {
    function dizzy_reel_extras_columns_present(): bool
    {
        static $present = null;
        if ($present !== null) return $present;
        try {
            $a = DB::one("SHOW COLUMNS FROM i_posts LIKE 'post_filter'");
            $b = DB::one("SHOW COLUMNS FROM i_posts LIKE 'post_video_speed'");
            $present = !empty($a) && !empty($b);
        } catch (Throwable $e) {
            $present = false;
        }
        return $present;
    }
}

if (!function_exists('dizzy_render_reel_overlays')) {
    /**
     * Render the per-reel overlay layer based on the JSON stored in
     * i_posts.post_overlays. Safe to call with an empty/invalid value.
     */
    function dizzy_render_reel_overlays($json): string
    {
        if (!is_string($json) || trim($json) === '') return '';
        $arr = json_decode($json, true);
        if (!is_array($arr) || empty($arr)) return '';
        $html = '<div class="reel-overlays">';
        $allowedAnims = ['none','fade','zoom','bounce','slide-up','slide-down','slide-left','slide-right','pulse','spin','shake','neon','typewriter'];
        foreach ($arr as $o) {
            if (!is_array($o)) continue;
            $type = isset($o['type']) ? (string)$o['type'] : '';
            if (!in_array($type, ['text', 'emoji', 'gif', 'sticker'], true)) continue;
            $x  = max(0, min(1, (float)($o['x'] ?? 0.5)));
            $y  = max(0, min(1, (float)($o['y'] ?? 0.5)));
            $sc = max(0.2, min(6, (float)($o['scale'] ?? 1)));
            $text = htmlspecialchars((string)($o['text'] ?? ''), ENT_QUOTES, 'UTF-8');
            $rot = (float)($o['rot'] ?? 0);
            if ($rot < -360 || $rot > 360) $rot = 0;
            $font = in_array((string)($o['font'] ?? ''), ['', 'serif', 'mono', 'cursive'], true) ? (string)($o['font'] ?? '') : '';
            $fontAttr = $font !== '' ? ' data-font="' . htmlspecialchars($font) . '"' : '';
            $anim    = (string)($o['anim'] ?? 'none');
            $animOut = (string)($o['animOut'] ?? 'none');
            if (!in_array($anim, $allowedAnims, true))    $anim = 'none';
            if (!in_array($animOut, $allowedAnims, true)) $animOut = 'none';
            $start = max(0, (float)($o['start'] ?? 0));
            $end   = max(0, (float)($o['end'] ?? 0));
            $z     = max(0, min(9999, (int)($o['z'] ?? 0)));
            $styleExtra = '--rot:' . round($rot, 2) . 'deg;';
            if ($z > 0) $styleExtra .= 'z-index:' . $z . ';';
            $animAttr  = ' data-anim="' . htmlspecialchars($anim) . '"';
            $animOutAttr = ' data-anim-out="' . htmlspecialchars($animOut) . '"';
            $timeAttr  = ' data-start="' . round($start, 2) . '" data-end="' . round($end, 2) . '"';
            if ($type === 'text') {
                $color = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string)($o['color'] ?? '')) ? (string)$o['color'] : '#ffffff';
                $bg    = in_array((string)($o['bg'] ?? '0'), ['0','1','2','3'], true) ? (string)$o['bg'] : '0';
                $size  = round(26 * $sc, 1);
                $html .= '<div class="ro-item ro-text" data-bg="' . htmlspecialchars($bg) . '"' . $fontAttr . $animAttr . $animOutAttr . $timeAttr . ' '
                       . 'style="left:' . ($x * 100) . '%;top:' . ($y * 100) . '%;'
                       . 'color:' . $color . ';font-size:' . $size . 'px;' . $styleExtra . '">' . $text . '</div>';
            } elseif ($type === 'emoji') {
                $size = round(64 * $sc, 1);
                $html .= '<div class="ro-item ro-emoji"' . $animAttr . $animOutAttr . $timeAttr . ' '
                       . 'style="left:' . ($x * 100) . '%;top:' . ($y * 100) . '%;font-size:' . $size . 'px;' . $styleExtra . '">' . $text . '</div>';
            } else { // gif | sticker
                $src = (string)($o['src'] ?? '');
                if (!preg_match('#^https?://[^\s<>"\']{4,800}\.(gif|png|webp|jpg|jpeg)(\?[^\s<>"\']{0,200})?$#i', $src)) continue;
                $w = round(140 * $sc, 1);
                $html .= '<div class="ro-item ro-gif-wrap"' . $animAttr . $animOutAttr . $timeAttr . ' '
                       . 'style="left:' . ($x * 100) . '%;top:' . ($y * 100) . '%;' . $styleExtra . '">'
                       . '<img class="ro-gif" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy" '
                       . 'style="width:' . $w . 'px;"></div>';
            }
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('dizzy_music_proxied_url')) {
    /**
     * Produce a same-origin URL for cross-origin audio CDNs so playback works
     * even when the upstream serves restrictive CORS headers.
     */
    function dizzy_music_proxied_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') { return $url; }
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) { return $url; }
        $host = strtolower($parsed['host']);
        $crossOrigin = ['prod-1.storage.jamendo.com', 'storage-new.newjamendo.com',
                        'mp3l.jamendo.com', 'mp3d.jamendo.com', 'www.soundhelix.com'];
        if (!in_array($host, $crossOrigin, true)) { return $url; }
        $base = isset($GLOBALS['base_url']) && is_string($GLOBALS['base_url']) ? rtrim($GLOBALS['base_url'], '/') . '/' : '/';
        return $base . 'requests/music_proxy.php?u=' . rawurlencode($url);
    }
}

if (!function_exists('dizzy_music_provider_id')) {
    function dizzy_music_provider_id(): string
    {
        // 1) Admin dashboard value (loaded into $jamendoClientId by inc.php).
        if (isset($GLOBALS['jamendoClientId']) && is_string($GLOBALS['jamendoClientId']) && trim($GLOBALS['jamendoClientId']) !== '') {
            return trim($GLOBALS['jamendoClientId']);
        }
        // 2) Direct DB read in case helper is used outside the normal request lifecycle.
        try {
            $col = DB::one("SHOW COLUMNS FROM i_configurations LIKE 'jamendo_client_id'");
            if (!empty($col)) {
                $val = DB::col("SELECT jamendo_client_id FROM i_configurations WHERE configuration_id = 1");
                if (is_string($val) && trim($val) !== '') {
                    return trim($val);
                }
            }
        } catch (Throwable $e) {
            // ignore, fall through to env
        }
        // 3) Constant / env / .env fallback.
        $val = function_exists('dizzy_read_env_value') ? dizzy_read_env_value('JAMENDO_CLIENT_ID') : null;
        return is_string($val) ? trim($val) : '';
    }
}

if (!function_exists('dizzy_music_http_get')) {
    /**
     * Lightweight GET request helper. Uses curl when available, falls back to streams.
     * Returns decoded JSON or null on failure.
     */
    function dizzy_music_http_get(string $url, int $timeoutSec = 8): ?array
    {
        $body = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => $timeoutSec,
                CURLOPT_USERAGENT      => 'DizzyMusic/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
        } else {
            $ctx  = stream_context_create(['http' => ['timeout' => $timeoutSec, 'header' => "User-Agent: DizzyMusic/1.0\r\n"]]);
            $body = @file_get_contents($url, false, $ctx);
        }
        if (!is_string($body) || $body === '') {
            return null;
        }
        $json = json_decode($body, true);
        return is_array($json) ? $json : null;
    }
}

if (!function_exists('dizzy_music_normalize_jamendo')) {
    function dizzy_music_normalize_jamendo(array $tracks): array
    {
        $out = [];
        foreach ($tracks as $t) {
            if (empty($t['id']) || empty($t['audio'])) { continue; }
            $out[] = [
                'id'        => 'jam_' . $t['id'],
                'provider'  => 'jamendo',
                'title'     => (string)($t['name'] ?? ''),
                'artist'    => (string)($t['artist_name'] ?? ''),
                'duration'  => (float)($t['duration'] ?? 0),
                'audio_url' => (string)$t['audio'],
                'cover_url' => (string)($t['album_image'] ?? $t['image'] ?? ''),
                'share_url' => (string)($t['shareurl'] ?? ''),
                'license'   => (string)($t['license_ccurl'] ?? 'jamendo'),
            ];
        }
        return $out;
    }
}

if (!function_exists('dizzy_music_sample_library')) {
    /**
     * Built-in CC0 fallback library so the modal works without an API key.
     * Sourced from public-domain SoundHelix samples.
     */
    function dizzy_music_sample_library(): array
    {
        $base = 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-';
        $cover = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/04/Music-icon.svg/200px-Music-icon.svg.png';
        $tracks = [];
        for ($i = 1; $i <= 10; $i++) {
            $tracks[] = [
                'id'        => 'demo_' . $i,
                'provider'  => 'demo',
                'title'     => 'SoundHelix Song ' . $i,
                'artist'    => 'SoundHelix',
                'duration'  => 240.0,
                'audio_url' => $base . $i . '.mp3',
                'cover_url' => $cover,
                'share_url' => 'https://www.soundhelix.com/audio-examples',
                'license'   => 'cc0',
            ];
        }
        return $tracks;
    }
}

if (!function_exists('dizzy_music_search')) {
    function dizzy_music_search(string $query, int $limit = 30): array
    {
        $query = trim($query);
        $client = dizzy_music_provider_id();
        if ($client !== '' && $query !== '') {
            $url = 'https://api.jamendo.com/v3.0/tracks/?client_id=' . urlencode($client)
                 . '&format=json&limit=' . (int)$limit
                 . '&search=' . urlencode($query)
                 . '&include=musicinfo&audioformat=mp32';
            $data = dizzy_music_http_get($url);
            if (is_array($data) && !empty($data['results'])) {
                return dizzy_music_normalize_jamendo($data['results']);
            }
        }
        // Fallback: filter sample lib by query
        $lib = dizzy_music_sample_library();
        if ($query === '') { return $lib; }
        $q = mb_strtolower($query);
        return array_values(array_filter($lib, function ($t) use ($q) {
            return strpos(mb_strtolower($t['title']), $q) !== false
                || strpos(mb_strtolower($t['artist']), $q) !== false;
        }));
    }
}

if (!function_exists('dizzy_music_trending')) {
    function dizzy_music_trending(int $limit = 30, string $tag = ''): array
    {
        $client = dizzy_music_provider_id();
        if ($client !== '') {
            $params = [
                'client_id' => $client,
                'format'    => 'json',
                'limit'     => (string)$limit,
                'order'     => 'popularity_total',
                'include'   => 'musicinfo',
                'audioformat' => 'mp32',
            ];
            if ($tag !== '') { $params['tags'] = $tag; }
            $url = 'https://api.jamendo.com/v3.0/tracks/?' . http_build_query($params);
            $data = dizzy_music_http_get($url);
            if (is_array($data) && !empty($data['results'])) {
                return dizzy_music_normalize_jamendo($data['results']);
            }
        }
        return dizzy_music_sample_library();
    }
}

if (!function_exists('dizzy_music_get_track')) {
    function dizzy_music_get_track(string $trackId): ?array
    {
        if ($trackId === '') { return null; }
        if (strpos($trackId, 'jam_') === 0) {
            $client = dizzy_music_provider_id();
            if ($client === '') { return null; }
            $jid = substr($trackId, 4);
            $url = 'https://api.jamendo.com/v3.0/tracks/?client_id=' . urlencode($client)
                 . '&format=json&id=' . urlencode($jid) . '&include=musicinfo&audioformat=mp32';
            $data = dizzy_music_http_get($url);
            if (is_array($data) && !empty($data['results'])) {
                $arr = dizzy_music_normalize_jamendo($data['results']);
                return $arr[0] ?? null;
            }
            return null;
        }
        if (strpos($trackId, 'demo_') === 0) {
            foreach (dizzy_music_sample_library() as $t) {
                if ($t['id'] === $trackId) { return $t; }
            }
        }
        return null;
    }
}

if (!function_exists('dizzy_music_categories')) {
    function dizzy_music_categories(): array
    {
        // Static list of common Jamendo tags (works as filters).
        return [
            ['key' => '',           'label' => 'Trending'],
            ['key' => 'pop',        'label' => 'Pop'],
            ['key' => 'hiphop',     'label' => 'Hip-Hop'],
            ['key' => 'electronic', 'label' => 'Electronic'],
            ['key' => 'rock',       'label' => 'Rock'],
            ['key' => 'jazz',       'label' => 'Jazz'],
            ['key' => 'classical',  'label' => 'Classical'],
            ['key' => 'lounge',     'label' => 'Chill'],
            ['key' => 'cinematic',  'label' => 'Cinematic'],
            ['key' => 'acoustic',   'label' => 'Acoustic'],
        ];
    }
}
