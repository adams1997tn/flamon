<?php
// Keep this file dependency-light: compute base URL and load route_url() without bootstrapping the whole app.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $scheme;
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$rootPath = preg_replace('~/(admin)$~u', '', rtrim($scriptDir, '/'));
$base_url = rtrim($scheme . '://' . $host . $rootPath, '/') . '/';
$GLOBALS['base_url'] = $base_url;

require_once __DIR__ . '/../includes/helper.php';

header('Location: ' . route_url('index'));
exit();
?>
