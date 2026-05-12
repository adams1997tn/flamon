<?php
/**
 * Music CDN proxy.
 * Streams audio from approved providers (currently Jamendo) so the browser
 * receives proper CORS headers — required for WaveSurfer waveform decoding
 * and avoids breakage when CDN sets a foreign Access-Control-Allow-Origin.
 *
 * Usage: /requests/music_proxy.php?u=<url-encoded MP3 url>
 */

include "../includes/inc.php";

// Reels feature must be enabled.
if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus !== '1') {
    http_response_code(403);
    exit('reels_disabled');
}
// Require login.
if (empty($logedIn) || (int)$logedIn !== 1) {
    http_response_code(401);
    exit('auth_required');
}

$u = isset($_GET['u']) ? (string)$_GET['u'] : '';
if ($u === '') { http_response_code(400); exit('missing_url'); }

// Allow only known-safe audio CDNs.
$allowedHosts = [
    'prod-1.storage.jamendo.com',
    'storage-new.newjamendo.com',
    'mp3l.jamendo.com',
    'mp3d.jamendo.com',
    'www.soundhelix.com',
];
$parsed = parse_url($u);
if (!$parsed || empty($parsed['host']) || empty($parsed['scheme'])
    || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)
    || !in_array(strtolower($parsed['host']), $allowedHosts, true)) {
    http_response_code(400);
    exit('host_not_allowed');
}

// CORS for our own origin (always our same-origin, so * is safe).
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Range');
header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
header('Cache-Control: public, max-age=3600');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$range = isset($_SERVER['HTTP_RANGE']) ? (string)$_SERVER['HTTP_RANGE'] : '';

if (!function_exists('curl_init')) {
    http_response_code(500);
    exit('curl_missing');
}

$ch = curl_init($u);
$reqHeaders = ['User-Agent: DizzyMusic/1.0', 'Accept: */*'];
if ($range !== '') { $reqHeaders[] = 'Range: ' . $range; }

$headersSent = false;
curl_setopt_array($ch, [
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 6,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => $reqHeaders,
    CURLOPT_HEADERFUNCTION => function ($curl, $hdr) use (&$headersSent) {
        $line = trim($hdr);
        if ($line === '') { return strlen($hdr); }
        // Forward HTTP/1.1 200 OK / 206 etc.
        if (stripos($line, 'HTTP/') === 0) {
            $parts = explode(' ', $line, 3);
            if (isset($parts[1])) { http_response_code((int)$parts[1]); }
            return strlen($hdr);
        }
        // Forward only safe streaming headers.
        $allowed = ['content-type', 'content-length', 'content-range', 'accept-ranges', 'last-modified', 'etag'];
        $name = strtolower(strtok($line, ':'));
        if (in_array($name, $allowed, true)) {
            header($line);
        }
        return strlen($hdr);
    },
    CURLOPT_WRITEFUNCTION => function ($curl, $data) use (&$headersSent) {
        echo $data;
        @ob_flush(); @flush();
        return strlen($data);
    },
]);

@ob_end_clean(); // disable output buffering for streaming
curl_exec($ch);
$err = curl_errno($ch);
curl_close($ch);
if ($err) {
    error_log('[music_proxy] curl error ' . $err);
}
