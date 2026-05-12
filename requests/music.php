<?php
/**
 * Music library endpoint for the Reels music feature.
 * Returns JSON for search / trending / track / categories actions.
 */

include "../includes/inc.php";
include_once "../includes/music_helper.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Reels feature must be enabled.
if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus !== '1') {
    echo json_encode(['ok' => false, 'error' => 'reels_disabled']);
    exit;
}

// Require login.
if (empty($logedIn) || (int)$logedIn !== 1 || !isset($userID) || (int)$userID <= 0) {
    echo json_encode(['ok' => false, 'error' => 'auth_required']);
    exit;
}

dizzy_ensure_music_columns();

$action = isset($_GET['action']) ? (string)$_GET['action']
        : (isset($_POST['action']) ? (string)$_POST['action'] : 'trending');

try {
    switch ($action) {
        case 'search': {
            $q = isset($_GET['q']) ? (string)$_GET['q'] : (string)($_POST['q'] ?? '');
            $tracks = dizzy_music_search($q, 30);
            echo json_encode(['ok' => true, 'tracks' => $tracks, 'provider' => dizzy_music_provider_id() !== '' ? 'jamendo' : 'demo']);
            break;
        }
        case 'trending': {
            $tag = isset($_GET['tag']) ? (string)$_GET['tag'] : (string)($_POST['tag'] ?? '');
            $tracks = dizzy_music_trending(30, $tag);
            echo json_encode(['ok' => true, 'tracks' => $tracks, 'provider' => dizzy_music_provider_id() !== '' ? 'jamendo' : 'demo']);
            break;
        }
        case 'track': {
            $id = isset($_GET['id']) ? (string)$_GET['id'] : (string)($_POST['id'] ?? '');
            $track = dizzy_music_get_track($id);
            if ($track) {
                echo json_encode(['ok' => true, 'track' => $track]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'not_found']);
            }
            break;
        }
        case 'categories': {
            echo json_encode(['ok' => true, 'categories' => dizzy_music_categories()]);
            break;
        }
        default:
            echo json_encode(['ok' => false, 'error' => 'unknown_action']);
    }
} catch (Throwable $e) {
    error_log('[music.php] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode(['ok' => false, 'error' => 'server_error', 'detail' => $e->getMessage()]);
}
