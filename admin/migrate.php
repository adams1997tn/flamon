<?php
// Admin-only migration runner for safe Envato updates
include_once __DIR__ . '/../includes/inc.php';
if ($logedIn !== '1' || $userType !== '2') { http_response_code(403); exit('Forbidden'); }

header('Content-Type: text/plain; charset=utf-8');
echo "Starting migrations...\n";

function add_unique_if_missing(string $table, string $index, string $defSql): void {
    $rows = DB::all("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]);
    if (empty($rows)) {
        echo "Adding index $index on $table...\n";
        DB::exec($defSql);
    } else {
        echo "Index $index already exists on $table.\n";
    }
}

// 1) i_sessions.session_key -> VARCHAR(96) and unique index
$row = DB::one("SHOW COLUMNS FROM i_sessions LIKE 'session_key'");
if ($row) {
    if (stripos($row['Type'], 'varchar') === false) {
        echo "Altering i_sessions.session_key to VARCHAR(96)...\n";
        DB::exec("ALTER TABLE i_sessions MODIFY session_key VARCHAR(96) NULL");
    } else {
        echo "i_sessions.session_key already VARCHAR.\n";
    }
}
add_unique_if_missing('i_sessions', 'uniq_session_key', 'CREATE UNIQUE INDEX uniq_session_key ON i_sessions (session_key)');

// Discovery Feed: add discovery_feed_status column to i_configurations if missing
$discoveryCol = DB::one("SHOW COLUMNS FROM i_configurations LIKE 'discovery_feed_status'");
if (!$discoveryCol) {
    echo "Adding i_configurations.discovery_feed_status (INT default 0)...\n";
    DB::exec("ALTER TABLE i_configurations ADD COLUMN discovery_feed_status INT NOT NULL DEFAULT 0");
} else {
    echo "i_configurations.discovery_feed_status already exists.\n";
}

// 2) i_users unique indices (username/email)
add_unique_if_missing('i_users', 'uniq_username', 'CREATE UNIQUE INDEX uniq_username ON i_users (i_username)');
add_unique_if_missing('i_users', 'uniq_email', 'CREATE UNIQUE INDEX uniq_email ON i_users (i_user_email)');

// Cleanup: remove orphan reels (post_type='reels' rows whose video upload is gone).
// Such rows surface in the feed as "This video is no longer available" placeholders.
$orphanReelIds = DB::all(
    "SELECT P.post_id FROM i_posts P
     WHERE P.post_type = 'reels'
       AND (
            P.post_file IS NULL
         OR P.post_file = ''
         OR NOT EXISTS (
                SELECT 1 FROM i_user_uploads UU
                WHERE UU.upload_id = CAST(SUBSTRING_INDEX(P.post_file, ',', 1) AS UNSIGNED)
                  AND UU.uploaded_file_path IS NOT NULL
                  AND UU.uploaded_file_path <> ''
            )
       )"
);
if (!empty($orphanReelIds)) {
    $orphanCount = count($orphanReelIds);
    echo "Cleaning up {$orphanCount} orphan reel post(s) with missing video data...\n";
    foreach ($orphanReelIds as $row) {
        $pid = (int)($row['post_id'] ?? 0);
        if ($pid <= 0) { continue; }
        DB::exec("DELETE FROM i_posts WHERE post_id = ?", [$pid]);
        DB::exec("DELETE FROM i_post_comments WHERE comment_post_id_fk = ?", [$pid]);
        DB::exec("DELETE FROM i_post_likes WHERE post_id_fk = ?", [$pid]);
    }
} else {
    echo "No orphan reels found.\n";
}

echo "Done.\n";
?>
