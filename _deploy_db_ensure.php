<?php
// One-shot: ensure new columns + report counts. Init PDO from connect.php.
chdir(__DIR__);
require __DIR__ . '/includes/connect.php';
require __DIR__ . '/includes/db.php';
if (isset($pdo) && $pdo instanceof PDO) { DB::init($pdo); }
require __DIR__ . '/includes/music_helper.php';

$out = [];
$out[] = 'overlays_col_added=' . (function_exists('dizzy_ensure_overlays_column')
    ? (dizzy_ensure_overlays_column() ? 'yes' : 'no')
    : 'fn-missing');
$out[] = 'extras_cols_added=' . (function_exists('dizzy_ensure_reel_extras_columns')
    ? (dizzy_ensure_reel_extras_columns() ? 'yes' : 'no')
    : 'fn-missing');

$cols = DB::all("SHOW COLUMNS FROM i_posts WHERE Field IN ('post_overlays','post_filter','post_video_speed')");
foreach ($cols as $c) {
    $out[] = 'i_posts.' . $c['Field'] . ' = ' . $c['Type'];
}
$out[] = 'users=' . (int)DB::col('SELECT COUNT(*) FROM i_users');
$out[] = 'posts=' . (int)DB::col('SELECT COUNT(*) FROM i_posts');

echo implode("\n", $out) . "\n";
