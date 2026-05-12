<?php
/**
 * Global Initialization and Core Configuration
 * This section handles error reporting, database connections,
 * class loading, and session start required for the entire system.
 */

ob_start();
session_start();

// Provide sane defaults when running via CLI (simulate scripts, cron, etc.)

// Database connection and base setup
include_once "connect.php";

if (!function_exists('dizzy_read_env_value')) {
    /**
     * Read an environment/config value from constant, process env, server env,
     * and optional project-level .env file.
     */
    function dizzy_read_env_value(string $key): ?string
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        if (defined($key)) {
            $value = (string) constant($key);
            if ($value !== '') {
                return $value;
            }
        }

        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') {
            return (string) $envValue;
        }

        if (isset($_SERVER[$key]) && (string) $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }

        if (isset($_ENV[$key]) && (string) $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }

        static $dotenvValues = null;
        if ($dotenvValues === null) {
            $dotenvValues = [];
            $envPath = dirname(__DIR__) . '/.env';
            if (is_file($envPath)) {
                $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (is_array($lines)) {
                    foreach ($lines as $line) {
                        $line = trim((string) $line);
                        if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                            continue;
                        }

                        $parts = explode('=', $line, 2);
                        if (count($parts) !== 2) {
                            continue;
                        }

                        $envKey = trim((string) $parts[0]);
                        $envVal = trim((string) $parts[1]);
                        if ($envKey === '') {
                            continue;
                        }

                        $first = substr($envVal, 0, 1);
                        $last = substr($envVal, -1);
                        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                            $envVal = substr($envVal, 1, -1);
                        }

                        $dotenvValues[$envKey] = $envVal;
                    }
                }
            }
        }

        if (array_key_exists($key, $dotenvValues) && (string) $dotenvValues[$key] !== '') {
            return (string) $dotenvValues[$key];
        }

        return null;
    }
}

// Error reporting: environment-aware (robust multi-channel detection)
// Priority: APP_ENV override > host-based default
$hostForEnv = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$hostForEnv = preg_replace('/:\d+$/', '', $hostForEnv);
$isLocalHost = in_array($hostForEnv, ['localhost', '127.0.0.1', '::1'], true)
    || preg_match('/\.local$/', $hostForEnv) === 1;
$env = $isLocalHost ? 'development' : 'production';
$envOverride = dizzy_read_env_value('APP_ENV');
if ($envOverride !== null && $envOverride !== '') {
    $env = $envOverride;
}
$env = strtolower((string)$env);

if ($env === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (!function_exists('dizzy_asset_mode')) {
    /**
     * Resolve asset loading mode.
     * Returns "dev" or "prod". Defaults to "dev" on development env, otherwise "prod".
     */
    function dizzy_asset_mode(): string
    {
        static $mode = null;
        if ($mode !== null) {
            return $mode;
        }

        $candidates = [];
        $envAssetMode = dizzy_read_env_value('DIZZY_ASSET_MODE');
        if ($envAssetMode !== null && $envAssetMode !== '') {
            $candidates[] = (string) $envAssetMode;
        }
        if (defined('DIZZY_ASSET_MODE')) {
            $candidates[] = (string) DIZZY_ASSET_MODE;
        }
        if (!empty($_SERVER['DIZZY_ASSET_MODE'])) {
            $candidates[] = (string) $_SERVER['DIZZY_ASSET_MODE'];
        }
        if (!empty($GLOBALS['dizzyAssetMode'])) {
            $candidates[] = (string) $GLOBALS['dizzyAssetMode'];
        }
        if (!empty($GLOBALS['env'])) {
            $candidates[] = (string) $GLOBALS['env'];
        }

        $mode = 'prod';
        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim((string) $candidate));
            if (in_array($candidate, ['dev', 'development', 'local', 'test', 'testing'], true)) {
                $mode = 'dev';
                break;
            }
            if (in_array($candidate, ['prod', 'production', 'live', 'stage', 'staging', 'preprod'], true)) {
                $mode = 'prod';
                break;
            }
        }

        if ($mode === 'prod') {
            $hostForAsset = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
            $hostForAsset = preg_replace('/:\d+$/', '', $hostForAsset);
            $isLocalAssetHost = in_array($hostForAsset, ['localhost', '127.0.0.1', '::1'], true)
                || preg_match('/\.local$/', $hostForAsset) === 1;
            if ($isLocalAssetHost && empty($candidates)) {
                $mode = 'dev';
            }
        }

        return $mode;
    }
}

if (!function_exists('dizzy_asset_resolve_relative')) {
    /**
     * Resolve an asset relative path based on asset mode:
     * - prod: prefer .min.js/.min.css when available
     * - dev: prefer non-minified variant when available
     */
    function dizzy_asset_resolve_relative(string $relativePath): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim((string) $relativePath)), '/');
        if ($normalized === '') {
            return '';
        }

        $query = '';
        $pathOnly = $normalized;
        $queryPos = strpos($normalized, '?');
        if ($queryPos !== false) {
            $pathOnly = substr($normalized, 0, $queryPos);
            $query = substr($normalized, $queryPos + 1);
        }

        $extension = strtolower((string) pathinfo($pathOnly, PATHINFO_EXTENSION));
        if (!in_array($extension, ['js', 'css'], true)) {
            return $normalized;
        }

        $appRoot = dirname(__DIR__);
        $fileExists = static function (string $rel) use ($appRoot): bool {
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            if ($rel === '' || strpos($rel, '..') !== false) {
                return false;
            }
            return is_file($appRoot . '/' . $rel);
        };

        $isMinified = preg_match('/\.min\.(js|css)$/i', $pathOnly) === 1;
        $plainPath = $isMinified ? preg_replace('/\.min\.(js|css)$/i', '.$1', $pathOnly) : $pathOnly;
        $minPath = $isMinified ? $pathOnly : preg_replace('/\.(js|css)$/i', '.min.$1', $pathOnly);

        $resolved = $pathOnly;
        if (dizzy_asset_mode() === 'prod') {
            if (!$isMinified && $minPath && $fileExists($minPath)) {
                $resolved = $minPath;
            } elseif (!$fileExists($resolved) && $minPath && $fileExists($minPath)) {
                $resolved = $minPath;
            }
        } else {
            if ($isMinified && $plainPath && $fileExists($plainPath)) {
                $resolved = $plainPath;
            } elseif (!$fileExists($resolved) && $plainPath && $fileExists($plainPath)) {
                $resolved = $plainPath;
            }
        }

        if ($query !== '') {
            $resolved .= '?' . $query;
        }

        return $resolved;
    }
}

if (!function_exists('dizzy_asset_resolved_mtime')) {
    /**
     * Get mtime for resolved asset path.
     */
    function dizzy_asset_resolved_mtime(string $relativePath): ?int
    {
        $resolved = dizzy_asset_resolve_relative($relativePath);
        if ($resolved === '') {
            return null;
        }
        $pathOnly = strtok($resolved, '?');
        $pathOnly = ltrim(str_replace('\\', '/', (string) $pathOnly), '/');
        if ($pathOnly === '' || strpos($pathOnly, '..') !== false) {
            return null;
        }
        $fullPath = dirname(__DIR__) . '/' . $pathOnly;
        if (!is_file($fullPath)) {
            return null;
        }
        $mtime = @filemtime($fullPath);
        return $mtime !== false ? (int) $mtime : null;
    }
}

if (!function_exists('dizzy_asset_url')) {
    /**
     * Build absolute asset URL with mode-aware file resolution and query params.
     */
    function dizzy_asset_url(string $relativePath, string $version = '', string $extraQuery = '', bool $appendDevMtime = true): string
    {
        $resolved = dizzy_asset_resolve_relative($relativePath);
        if ($resolved === '') {
            return '';
        }

        $pathOnly = $resolved;
        $initialQuery = '';
        $queryPos = strpos($resolved, '?');
        if ($queryPos !== false) {
            $pathOnly = substr($resolved, 0, $queryPos);
            $initialQuery = substr($resolved, $queryPos + 1);
        }

        $queryParts = [];
        if ($initialQuery !== '') {
            $queryParts[] = $initialQuery;
        }

        $version = trim((string) $version);
        if ($version !== '') {
            $queryParts[] = 'v=' . rawurlencode($version);
        }

        $extraQuery = trim((string) $extraQuery);
        $extraQuery = trim($extraQuery, '?&');
        if ($extraQuery !== '') {
            $queryParts[] = $extraQuery;
        }

        if ($appendDevMtime && dizzy_asset_mode() === 'dev') {
            $mtime = dizzy_asset_resolved_mtime($pathOnly);
            if ($mtime !== null) {
                $queryParts[] = 'm=' . (string) $mtime;
            }
        }

        $baseUrl = rtrim((string) ($GLOBALS['base_url'] ?? ''), '/');
        $url = ($baseUrl !== '' ? $baseUrl . '/' : '/') . ltrim($pathOnly, '/');
        if (!empty($queryParts)) {
            $url .= '?' . implode('&', $queryParts);
        }

        return $url;
    }
}
// Include required function and helper files
include_once "checkHtaccessUrlMismatch.php";
// Do not echo diagnostics in runtime output; optionally log
$__ht_mismatch = checkHtaccessUrlMismatch($base_url);
include_once "functions.php";
include_once "emojis.php";
include_once "colors.php";
include_once "helper.php";
include_once "license_core.php";
include_once "license_helper.php";
include_once "license_guard.php";
include_once "linkify/autoload.php";
include_once "_expand.php";
include_once "csrf.php";
include_once "db.php";
include_once "realtime_providers.php";
// Stripe library autoload
require_once 'stripe/vendor/autoload.php';

// URL highlighter library usage
use VStelmakh\UrlHighlight\UrlHighlight;
$urlHighlight = new UrlHighlight();

// Class initialization
$iN = new iN_UPDATES($db);
$pdo && DB::init($pdo);

$__LG = LicenseGuard::bootstrap($logedIn ?? '0', $userType ?? '0');
$lc_st = $__LG['st'];
$lc_tok = $__LG['tok'];

$inc = $iN->iN_Configurations();
$getPages = $iN->iN_GetPages();
$languages = $iN->iN_Languages();

// Optional per-storage CDN/public base overrides (auto-generated file)
if (is_file(__DIR__ . '/storage_public_base.php')) {
    @include __DIR__ . '/storage_public_base.php';
}

// Optional local override file for Bunny Storage admin settings
if (is_file(__DIR__ . '/bunny_config.php')) {
    @include __DIR__ . '/bunny_config.php';
}

/**
 * Bunny CDN (Pull Zone) settings
 * - CDN-only: keeps storage provider unchanged, only rewrites public URLs.
 * - Config stored via includes/storage_public_base.php for simple, Envato-friendly install.
 */
$bunnyCdnStatus = isset($inc['bunny_cdn_status']) ? (string) $inc['bunny_cdn_status'] : '0';
$bunnyCdnBase = isset($inc['bunny_cdn_base']) ? (string) $inc['bunny_cdn_base'] : '';

// Environment overrides (developer-friendly)
$bunnyCdnStatus = getenv('BUNNY_CDN_STATUS') ?: $bunnyCdnStatus;
$bunnyCdnBase = getenv('BUNNY_CDN_BASE') ?: $bunnyCdnBase;

$bunnyCdnStatus = in_array((string) $bunnyCdnStatus, ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
$bunnyCdnBase = trim((string) $bunnyCdnBase);
if ($bunnyCdnBase !== '' && filter_var($bunnyCdnBase, FILTER_VALIDATE_URL) !== false) {
    $bunnyCdnBase = rtrim($bunnyCdnBase, '/') . '/';
} else {
    $bunnyCdnBase = '';
}

/**
 * Bunny Storage settings (for uploads)
 * - Enables storing uploads in Bunny Storage Zone via Bunny Storage API.
 * - Public delivery should be done via Bunny CDN (Pull Zone) by enabling Bunny CDN above.
 */
$bunnyStorageStatus = isset($inc['bunny_storage_status']) ? (string) $inc['bunny_storage_status'] : '0';
$bunnyStorageZone = isset($inc['bunny_storage_zone']) ? (string) $inc['bunny_storage_zone'] : '';
$bunnyStorageAccessKey = isset($inc['bunny_storage_access_key']) ? (string) $inc['bunny_storage_access_key'] : '';
$bunnyStorageHost = isset($inc['bunny_storage_host']) ? (string) $inc['bunny_storage_host'] : 'storage.bunnycdn.com';

// Environment overrides (developer-friendly)
$bunnyStorageStatus = getenv('BUNNY_STORAGE_STATUS') ?: $bunnyStorageStatus;
$bunnyStorageZone = getenv('BUNNY_STORAGE_ZONE') ?: $bunnyStorageZone;
$bunnyStorageAccessKey = getenv('BUNNY_STORAGE_ACCESS_KEY') ?: $bunnyStorageAccessKey;
$bunnyStorageHost = getenv('BUNNY_STORAGE_HOST') ?: $bunnyStorageHost;

$bunnyStorageStatus = in_array((string) $bunnyStorageStatus, ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
$bunnyStorageZone = trim((string) $bunnyStorageZone);
$bunnyStorageAccessKey = trim((string) $bunnyStorageAccessKey);
$bunnyStorageHost = trim((string) $bunnyStorageHost);
if ($bunnyStorageHost === '') {
    $bunnyStorageHost = 'storage.bunnycdn.com';
}

// Optional Adsense configuration overrides (auto-generated file)
if (is_file(__DIR__ . '/adsense_config.php')) {
    @include __DIR__ . '/adsense_config.php';
}

// Resolve application root (one level up from includes/) and language dir
$__APP_ROOT = dirname(__DIR__);
$__LANG_DIR = $__APP_ROOT . '/langs';

// Resolve language pack fullpath (falls back to English) without including it
if (!function_exists('__resolve_lang_path')) {
    function __resolve_lang_path($code, $langDir) {
        $safe = strtolower(preg_replace('/[^a-z_]/i', '', (string) $code));
        if ($safe === '') { $safe = 'eng'; }
        $path = rtrim($langDir, '/\\') . '/' . $safe . '.php';
        if (!is_file($path)) {
            $path = rtrim($langDir, '/\\') . '/eng.php';
        }
        return $path;
    }
}

// Set default timezone (can be overridden later per user session)
date_default_timezone_set('UTC');

/**
 * Session & Cookie Check
 * Ensures that valid session or cookie exists for logged-in user.
 * If invalid, redirect to logout.
 */
$hash = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : NULL;
$sessionUserID = isset($_SESSION['iuid']) ? $_SESSION['iuid'] : NULL;

if (!empty($hash)) {
    // Validate session hash via PDO
    $row = DB::one("SELECT session_uid FROM i_sessions WHERE session_key = ? LIMIT 1", [$hash]);
    $sessionUserID = $row['session_uid'] ?? null;

    // If session ID is not valid, redirect to logout
    if (empty($sessionUserID)) {
        header("Location: " . route_url('logout.php'));
    } else {
        // Valid session, store user ID in session variable
        $_SESSION['iuid'] = $sessionUserID;
    }
}

$cURL = true;

/**
 * Generates a random alphanumeric key of a given length.
 * Used for versioning or identifiers.
 *
 * @param int $length
 * @return string
 */
function generateRandomKeys($length = 5) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomKey = '';

    for ($i = 0; $i < $length; $i++) {
        $randomKey .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomKey;
}

// Check if cURL is available
if (!function_exists('curl_init')) {
    $cURL = false;
    $disabled = true;
}

$userEmailVerificationStatus = '';

// Define base URL for meta image
$metaBaseUrl = $base_url . 'img/' . (isset($inc['meta_image']) ? $inc['meta_image'] : NULL);

// Detect browser language (used if auto-detect enabled)
$browserLanguage = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2) : '';

// Get active theme or fallback to default
$currentTheme = isset($inc['active_theme']) ? $inc['active_theme'] : 'default';
// Use deterministic versioning so browser/CDN caches can be reused across requests.
$scriptVersion = isset($inc['version']) ? (string)$inc['version'] : '1';
$scriptVersion = trim($scriptVersion);
if ($scriptVersion === '') {
    $scriptVersion = '1';
}
$version = preg_replace('/[^a-zA-Z0-9._-]/', '', $scriptVersion);
if ($version === '') {
    $version = '1';
}

// Basic branding elements
$logo = isset($inc['site_logo']) ? $inc['site_logo'] : NULL;
$nightLogo = $iN->iN_GetSetting('site_night_logo', '');
if (!is_string($nightLogo)) { $nightLogo = ''; }
$autoDetectLanguageStatus = isset($inc['auto_detect_language_status']) ? $inc['auto_detect_language_status'] : NULL;
$siteWatermarkLogo = isset($inc['site_watermark_logo']) ? $inc['site_watermark_logo'] : $logo;
$favicon = isset($inc['site_favicon']) ? $inc['site_favicon'] : '1';

// Basic SEO and meta info
$siteTitle = isset($inc['site_title']) ? $inc['site_title'] : NULL;
$siteKeyWords = isset($inc['site_keywords']) ? $inc['site_keywords'] : NULL;
$siteDescription = isset($inc['site_description']) ? $inc['site_description'] : NULL;

// Company identity info
$siteCampany = isset($inc['campany']) ? $inc['campany'] : NULL;
$siteCountry = isset($inc['country']) ? $inc['country'] : NULL;
$siteCity = isset($inc['city']) ? $inc['city'] : NULL;
$sitePostCode = isset($inc['post_code']) ? $inc['post_code'] : NULL;
$siteVat = isset($inc['vat']) ? $inc['vat'] : NULL;

// General system settings
$mycd = isset($inc['mycd']) ? $inc['mycd'] : '1';
$normalUserCanPost = isset($inc['normal_user_can_post']) ? $inc['normal_user_can_post'] : NULL;
$giphyKey = isset($inc['giphy_api_key']) ? $inc['giphy_api_key'] : NULL;
$giphyStatus = isset($inc['giphy_status']) ? $inc['giphy_status'] : '1';
$freeLiveTime = isset($inc['free_live_time']) ? $inc['free_live_time'] : NULL;
$giphyTrendKey = isset($inc['giphy_first_trend_key']) ? $inc['giphy_first_trend_key'] : NULL;

// Agora live stream settings
$agoraStatus = $inc['agora_status'];
$agoraAppID = isset($inc['agora_app_id']) ? $inc['agora_app_id'] : NULL;
$agoraCertificate = isset($inc['agora_certificate']) ? $inc['agora_certificate'] : NULL;
$agoraCustomerID = isset($inc['agora_customer_id']) ? $inc['agora_customer_id'] : NULL;
$rtProvider = isset($inc['rt_provider']) ? strtolower(trim((string)$inc['rt_provider'])) : 'agora';
if (!in_array($rtProvider, ['agora', 'livekit'], true)) {
    $rtProvider = 'agora';
}
$livekitAPIKey = isset($inc['livekit_api_key']) ? trim((string)$inc['livekit_api_key']) : null;
$livekitAPISecret = isset($inc['livekit_api_secret']) ? trim((string)$inc['livekit_api_secret']) : null;
$livekitWSUrl = isset($inc['livekit_ws_url']) ? trim((string)$inc['livekit_ws_url']) : null;
$callProvider = isset($inc['call_provider']) ? strtolower(trim((string)$inc['call_provider'])) : 'agora';
if (!in_array($callProvider, ['agora', 'livekit', 'isometrik'], true)) {
    $callProvider = 'agora';
}
$isometrikAPIKey = isset($inc['isometrik_api_key']) ? trim((string)$inc['isometrik_api_key']) : null;
$isometrikAPISecret = isset($inc['isometrik_api_secret']) ? trim((string)$inc['isometrik_api_secret']) : null;
$isometrikProjectId = isset($inc['isometrik_project_id']) ? trim((string)$inc['isometrik_project_id']) : null;
$isometrikWSUrl = isset($inc['isometrik_ws_url']) ? trim((string)$inc['isometrik_ws_url']) : null;
$livePollStatus = isset($inc['live_poll_status']) ? (string)$inc['live_poll_status'] : '1';
$liveGiftStatus = isset($inc['live_gift_status']) ? (string)$inc['live_gift_status'] : '1';
$liveQAStatus = isset($inc['live_qa_status']) ? (string)$inc['live_qa_status'] : '1';
$liveChatStatus = isset($inc['live_chat_status']) ? (string)$inc['live_chat_status'] : '1';
$livePollStatus = $livePollStatus === '1' ? '1' : '0';
$liveGiftStatus = $liveGiftStatus === '1' ? '1' : '0';
$liveQAStatus = $liveQAStatus === '1' ? '1' : '0';
$liveChatStatus = $liveChatStatus === '1' ? '1' : '0';

// Landing page config
$landingPageType = $inc['landing_page_type'];
$landingPagePlugin = isset($inc['landing_page_plugin']) ? (string)$inc['landing_page_plugin'] : '';

// User restrictions and blocking capabilities
$disallowedUserNames = isset($inc['disallowed_usernames']) ? $inc['disallowed_usernames'] : NULL;
$userCanBlockCountryStatus = isset($inc['user_can_block_country']) ? $inc['user_can_block_country'] : NULL;

$defaultGenderOptions = [
    ['key' => 'female', 'label' => 'Female', 'icon' => '13', 'status' => '1'],
    ['key' => 'male',   'label' => 'Male',   'icon' => '12', 'status' => '1'],
    ['key' => 'couple', 'label' => 'Couple', 'icon' => '58', 'status' => '1'],
];
$genderOptionsPath = __DIR__ . '/gender_options.php';
$genderOptions = $defaultGenderOptions;
if (is_file($genderOptionsPath)) {
    $loadedGenderOptions = include $genderOptionsPath;
    if (is_array($loadedGenderOptions)) {
        $sanitizedGenderOptions = [];
        foreach ($loadedGenderOptions as $option) {
            if (!is_array($option)) { continue; }
            $key = isset($option['key']) ? strtolower(preg_replace('/[^a-z0-9_]/', '', (string)$option['key'])) : '';
            if ($key === '' || isset($sanitizedGenderOptions[$key])) { continue; }
            $label = isset($option['label']) ? trim((string)$option['label']) : ucfirst($key);
            if ($label === '') { $label = ucfirst($key); }
            $icon = isset($option['icon']) ? preg_replace('/[^0-9]/', '', (string)$option['icon']) : '';
            $statusRaw = isset($option['status']) ? strtolower((string)$option['status']) : '1';
            $status = in_array($statusRaw, ['0', 'off', 'no', 'false'], true) ? '0' : '1';
            $sanitizedGenderOptions[$key] = [
                'key'   => $key,
                'label' => $label,
                'icon'  => $icon,
                'status'=> $status,
            ];
        }
        if (!empty($sanitizedGenderOptions)) {
            $genderOptions = array_values($sanitizedGenderOptions);
        }
    }
}
$genderOptionsFull = $genderOptions;
$genderOptionsActive = array_values(array_filter($genderOptions, function($option) {
    return ($option['status'] ?? '1') !== '0';
}));
if (empty($genderOptionsActive)) {
    $genderOptionsActive = $defaultGenderOptions;
    $genderOptionsFull = $defaultGenderOptions;
}
$genderOptions = $genderOptionsActive;
$genders = array_column($genderOptions, 'key');
if (empty($genders)) {
    $genderOptions = $defaultGenderOptions;
    $genderOptionsFull = $defaultGenderOptions;
    $genders = array_column($genderOptions, 'key');
}
$primaryGenderKey = $genders[0];

// DigitalOcean file storage configuration
$digitalOceanStatus = isset($inc['ocean_status']) ? $inc['ocean_status'] : NULL;
$oceankey = isset($inc['ocean_key']) ? $inc['ocean_key'] : NULL;
$oceansecret = isset($inc['ocean_secret']) ? $inc['ocean_secret'] : NULL;
$oceanspace_name = isset($inc['ocean_space_name']) ? $inc['ocean_space_name'] : NULL;
$mycdStatus = isset($inc['mycd_status']) ? $inc['mycd_status'] : NULL;
$oceanregion = isset($inc['ocean_region']) ? $inc['ocean_region'] : NULL;
$digitalOceanPublicBase = isset($inc['ocean_public_base']) ? $inc['ocean_public_base'] : NULL;

// Subscription settings — forced to gateway/currency mode
$subscriptionType = '1';

// Affiliate registration and points system settings
$dataAffilateData = $iN->iN_GetRegisterAffilateData('register', '1');
$ataAffilateAmount = isset($dataAffilateData['i_af_amount']) ? $dataAffilateData['i_af_amount'] : NULL;

$dataNewPostPoint = $iN->iN_GetRegisterAffilateData('new_post', '5');
$ataNewPostPointAmount = isset($dataNewPostPoint['i_af_amount']) ? $dataNewPostPoint['i_af_amount'] : NULL;
$ataNewPostPointSatus = isset($dataNewPostPoint['i_af_status']) ? $dataNewPostPoint['i_af_status'] : 'no';

$dataNewCommentPoint = $iN->iN_GetRegisterAffilateData('comment', '2');
$ataNewCommentPointAmount = isset($dataNewPostPoint['i_af_amount']) ? $dataNewPostPoint['i_af_amount'] : NULL;
$ataNewCommentPointSatus = isset($dataNewPostPoint['i_af_status']) ? $dataNewPostPoint['i_af_status'] : 'no';

$dataNewPostLikePoint = $iN->iN_GetRegisterAffilateData('post_like', '3');
$ataNewPostLikePointAmount = isset($dataNewPostLikePoint['i_af_amount']) ? $dataNewPostLikePoint['i_af_amount'] : NULL;
$ataNewPostLikePointSatus = isset($dataNewPostLikePoint['i_af_status']) ? $dataNewPostLikePoint['i_af_status'] : 'no';
$dataNewPostCommentLikePoint = $iN->iN_GetRegisterAffilateData('comment_like', '4');
$ataNewPostCommentLikePointAmount = isset($dataNewPostCommentLikePoint['i_af_amount']) ? $dataNewPostCommentLikePoint['i_af_amount'] : NULL;
$ataNewPostCommentLikePointSatus = isset($dataNewPostCommentLikePoint['i_af_status']) ? $dataNewPostCommentLikePoint['i_af_status'] : 'no';

// Landing page image assets
$landingPageFirstImage = isset($inc['landing_first_image']) ? $inc['landing_first_image'] : NULL;
$landingpageFirstImageArrow = isset($inc['landing_first_image_arrow']) ? $inc['landing_first_image_arrow'] : NULL;
$landingpageFirstDesctiptionImage = isset($inc['landing_feature_image_one']) ? $inc['landing_feature_image_one'] : NULL;
$landingpageSecondDesctiptionImage = isset($inc['landing_feature_image_two']) ? $inc['landing_feature_image_two'] : NULL;
$landingpageThirdDesctiptionImage = isset($inc['landing_feature_image_three']) ? $inc['landing_feature_image_three'] : NULL;
$landingpageFourthDesctiptionImage = isset($inc['landing_feature_image_four']) ? $inc['landing_feature_image_four'] : NULL;
$landingpageFifthDesctiptionImage = isset($inc['landing_feature_image_five']) ? $inc['landing_feature_image_five'] : NULL;
$landingPageSectionTwoBG = isset($inc['landing_section_two_bg']) ? $inc['landing_section_two_bg'] : NULL;
$landingSectionFeatureImage = isset($inc['landing_section_feature_image']) ? $inc['landing_section_feature_image'] : NULL;

// Content approval and post visibility
$autoApprovePostStatus = isset($inc['auto_approve_post']) ? $inc['auto_approve_post'] : 'yes';

// Display preferences for suggestions and content
$showNumberOfAds = isset($inc['showingNumberOfAds']) ? $inc['showingNumberOfAds'] : '1';
$showingNumberOfSuggestedUser = isset($inc['showingNumberOfSuggestedUser']) ? $inc['showingNumberOfSuggestedUser'] : '1';
$showingNumberOfProduct = isset($inc['showingNumberOfProduct']) ? $inc['showingNumberOfProduct'] : '1';
$trendHashtagsStatus = isset($inc['trend_hashtags_status']) ? (string)$inc['trend_hashtags_status'] : '1';
$showingNumberOfTrendHashtags = isset($inc['showingNumberOfTrendHashtags']) ? $inc['showingNumberOfTrendHashtags'] : '10';
$showingTrendPostLimitDay = isset($inc['howManyDaysTrend']) ? $inc['howManyDaysTrend'] : '1';
$showingActivityLimit = isset($inc['activity_show_limit']) ? $inc['activity_show_limit'] : '1';
$showingTimeActivityLimit = isset($inc['activity_show_time_limit']) ? $inc['activity_show_time_limit'] : '1';
$unSubscribeStyle = isset($inc['unsubscribe_style']) ? $inc['unsubscribe_style'] : NULL;
$autoFollowAdmin = isset($inc['auto_follow_admin']) ? $inc['auto_follow_admin'] : 'yes';
// OpenAI integration and configuration
$opanAiKey = isset($inc['openai_api_key']) ? $inc['openai_api_key'] : NULL;
$openAiStatus =  isset($inc['open_ai_status']) ? $inc['open_ai_status'] : NULL;
$perAiUse = isset($inc['per_ai_use_credit']) ? $inc['per_ai_use_credit'] : NULL;
$openAiModel = isset($inc['openai_model']) ? (string)$inc['openai_model'] : 'gpt-4-turbo';
$openAiFallbackModel = isset($inc['openai_fallback_model']) ? (string)$inc['openai_fallback_model'] : 'gpt-3.5-turbo';
$openAiTemperature = isset($inc['openai_temperature']) ? (float)$inc['openai_temperature'] : 0.8;
$openAiMaxTokens = isset($inc['openai_max_tokens']) ? (int)$inc['openai_max_tokens'] : 150;
$openAiPromptMaxLength = isset($inc['openai_prompt_max_length']) ? (int)$inc['openai_prompt_max_length'] : 600;
$openAiRateLimitPerMinute = isset($inc['openai_rate_limit_per_minute']) ? (int)$inc['openai_rate_limit_per_minute'] : 5;
$openAiRateLimitPerHour = isset($inc['openai_rate_limit_per_hour']) ? (int)$inc['openai_rate_limit_per_hour'] : 60;
$openAiRateLimitPerDay = isset($inc['openai_rate_limit_per_day']) ? (int)$inc['openai_rate_limit_per_day'] : 200;
$openAiForbiddenTerms = isset($inc['openai_forbidden_terms']) ? (string)$inc['openai_forbidden_terms'] : '';
$openAiLastError = isset($inc['openai_last_error']) ? (string)$inc['openai_last_error'] : '';
$openAiLastErrorAt = isset($inc['openai_last_error_at']) ? (int)$inc['openai_last_error_at'] : 0;

/**
 * UI Color Customizations
 * These settings allow theme personalization and brand styling.
 */
$headerSVGColor = isset($inc['header_svg_color']) ? $inc['header_svg_color'] : NULL;
$headerTopColor =  isset($inc['header_top_color']) ? $inc['header_top_color'] : NULL;
$leftMenuSVGColor =  isset($inc['left_menu_svg_color']) ? $inc['left_menu_svg_color'] : NULL;
$postSectionSVGColor =  isset($inc['post_section_svg_colors']) ? $inc['post_section_svg_colors'] : NULL;
$postIconSVGColor =  isset($inc['post_icon_colors']) ? $inc['post_icon_colors'] : NULL;
$publishBTNColor =  isset($inc['publish_btn_color']) ? $inc['publish_btn_color'] : NULL;
$createLiveStreamingsBtnColor =  isset($inc['create_live_streamings_btn_color']) ? $inc['create_live_streamings_btn_color'] : NULL;
$textHoverColor =  isset($inc['left_menu_hover_color']) ? $inc['left_menu_hover_color'] : NULL;
$MenuTextColor =  isset($inc['left_menu_text_color']) ? $inc['left_menu_text_color'] : NULL;

// Financial settings related to live streams and general use
$minimumLiveStreamingFee = $inc['minimum_live_streaming_fee'];
$maintenanceMode = $inc['maintenance_mode'];

// Language and theme options
$defaultLanguage = $inc['default_language'];
$scrollLimit = $inc['load_more_limit'];
$scrollToLimitMessage = $inc['load_more_message_limit'];
$defaultStyle = isset($inc['default_style']) ? (string)$inc['default_style'] : 'light';
if (!in_array($defaultStyle, ['light', 'dark'], true)) {
    $defaultStyle = 'light';
}
$lightDark = $defaultStyle;
$socialLoginStatus = $inc['social_login_status'];

// Wallet deposit limits (currency)
$maximumPointLimit = $inc['max_point_limit'];
$minimumPointLimit = $inc['min_point_limit'];
$maximumPointAmountLimit = $inc['max_point_amount_limit'];
$minimumPointAmountLimit = $inc['min_point_amount_limit'];

// Geolocation API support (used for location-based features)
$geoLocationAPIKey = isset($inc['geolocationapikey']) ? $inc['geolocationapikey'] : NULL;

// Branding and general info
$siteName = $inc['site'];
$ind = date('j');
$adminTheme = $inc['admin_active_theme'];

// Custom scripts and code injection settings
$customHeaderCSSCode = $iN->iN_GetCustomCodes(1);
$customHeaderJsCode = $iN->iN_GetCustomCodes(2);
$customFooterJsCode = $iN->iN_GetCustomCodes(3);

// SVG icon assets for the UI
$allSVGIcons = $iN->iN_AllSVGIcons();

// Commission and earning limits
$adminFee = $inc['fee'];
$minimumSubscriptionAmount = $inc['minimum_subscription_amount'];
$maximumSubscriptionAmount = $inc['maximum_subscription_amount'];

// Affiliate program settings
$affilateSystemStatus = isset($inc['affilate_status']) ? $inc['affilate_status'] : NULL;
$minimumPointTransferRequest = isset($inc['minimum_point_transfer_request']) ? $inc['minimum_point_transfer_request'] : '0';
$affilateAmount = $inc['affilate_amount'];

// Subscription fees (per week, month, year)
$minPointFeeWeekly = $inc['min_point_fee_weekly'];
$minPointFeeMonthly = $inc['min_point_fee_monthly'];
$minPointFeeYearly = $inc['min_point_fee_yearly'];
$businessAddress = $inc['business_address'];
$taxStatus = isset($inc['tax_status']) ? (string)$inc['tax_status'] : '0';
$taxRate = isset($inc['tax_rate']) ? (float)$inc['tax_rate'] : 0;
$taxLabel = isset($inc['tax_label']) ? $inc['tax_label'] : 'VAT';
$taxRegistrationNumber = isset($inc['tax_registration_number']) ? $inc['tax_registration_number'] : null;
$taxCompanyName = isset($inc['tax_company_name']) ? $inc['tax_company_name'] : null;
$taxCompanyAddress = isset($inc['tax_company_address']) ? $inc['tax_company_address'] : null;
$taxInvoicePrefix = isset($inc['tax_invoice_prefix']) ? $inc['tax_invoice_prefix'] : 'INV';
$taxNextInvoiceNumber = isset($inc['tax_next_invoice_number']) ? (int)$inc['tax_next_invoice_number'] : 1;

// User discovery search behavior
$whicUsers = $inc['show_search_result_type'];

// Story system configuration
$storyStatusData = $iN->iN_GetStoryData('4');
$whoCanShareStory = isset($storyStatusData['sstatus']) ? $storyStatusData['sstatus'] : NULL;

// File upload validations
$availableFileExtensions = $inc['available_file_extensions'];
$availableVerificationFileExtensions = $inc['available_verification_file_extensions'];
$availableBulkMessageFileExtensions = isset($inc['available_bulk_message_file_extensions']) && $inc['available_bulk_message_file_extensions'] !== ''
    ? $inc['available_bulk_message_file_extensions']
    : $availableFileExtensions;
$availableUploadFileSize = $inc['available_file_size'];
$availableLength = isset($inc['available_length']) ? $inc['available_length'] : '500';
$bulkMessagePrivatePriceMin = isset($inc['bulk_message_private_price_min']) ? (float)$inc['bulk_message_private_price_min'] : 0.0;
$bulkMessagePrivatePriceMax = isset($inc['bulk_message_private_price_max']) ? (float)$inc['bulk_message_private_price_max'] : 0.0;

// FFMPEG media processing support
$ffmpegPath   = isset($inc['ffmpeg_path']) ? $inc['ffmpeg_path'] : NULL;
$ffprobePath  = isset($inc['ffmpeg_probe']) ? $inc['ffmpeg_probe'] : NULL; // optional, may be null if not set
$ffmpegStatus = $inc['ffmpeg_status'];
$storyAudioUploadMode = isset($inc['story_audio_upload_mode']) ? (string)$inc['story_audio_upload_mode'] : 'mp3_only';
if (!in_array($storyAudioUploadMode, ['mp3_only', 'ffmpeg_mp3'], true)) {
    $storyAudioUploadMode = 'mp3_only';
}
$pixelSize = $inc['pixelSize'];

// Dynamic reference code encryption/validation
$inD = isset($inc['mycd']) ? $inc['mycd'] : '1';

// General content listing preferences
$showingNumberOfPost = $inc['showingNumberOfPost'];

// Default values for unregistered user session variables
$userType = $payoutMethod = $userWallet = '';
$cEarning = '0';
$userSignupIntent = 'user';
$userAvatar = $base_url . 'uploads/avatars/no_gender.png';
$userAgeVerifyStatus = '0';
$userAgeVerifyProvider = '';
$userAgeVerifiedAt = null;
$userAgeVerifyRef = null;

// Currency and pagination
$defaultCurrency = $inc['default_currency'];
if (empty($defaultCurrency)) { $defaultCurrency = 'USD'; }
$currencySymbolPosition = isset($inc['currency_symbol_position']) ? (string)$inc['currency_symbol_position'] : 'left';
$currencySymbolPosition = in_array($currencySymbolPosition, ['left', 'right'], true) ? $currencySymbolPosition : 'left';
$currencyDecimalPlaces = isset($inc['currency_decimal_places']) ? (int)$inc['currency_decimal_places'] : 2;
if ($currencyDecimalPlaces < 0 || $currencyDecimalPlaces > 4) { $currencyDecimalPlaces = 2; }
$currencyThousandSeparatorToken = isset($inc['currency_thousand_separator']) ? (string)$inc['currency_thousand_separator'] : 'comma';
$currencyDecimalSeparatorToken = isset($inc['currency_decimal_separator']) ? (string)$inc['currency_decimal_separator'] : 'dot';
$currencyThousandSeparatorToken = in_array($currencyThousandSeparatorToken, ['comma', 'dot', 'space', 'none'], true) ? $currencyThousandSeparatorToken : 'comma';
$currencyDecimalSeparatorToken = in_array($currencyDecimalSeparatorToken, ['dot', 'comma'], true) ? $currencyDecimalSeparatorToken : 'dot';
$currencyDecimalSeparator = $currencyDecimalSeparatorToken === 'comma' ? ',' : '.';
switch ($currencyThousandSeparatorToken) {
    case 'dot':
        $currencyThousandSeparator = '.';
        break;
    case 'space':
        $currencyThousandSeparator = ' ';
        break;
    case 'none':
        $currencyThousandSeparator = '';
        break;
    case 'comma':
    default:
        $currencyThousandSeparator = ',';
        break;
}
$paginationLimit = $inc['pagination_limit'];

// Site identity assets
$siteLogoUrl = $base_url . $logo;
$nightLogoUrl = !empty($nightLogo) ? ($base_url . $nightLogo) : $siteLogoUrl;
$activeLogoUrl = (isset($lightDark) && (string)$lightDark === 'dark' && !empty($nightLogo)) ? $nightLogoUrl : $siteLogoUrl;
$siteFavicon = $base_url . $favicon;

// Tip feature configuration
$minimumTipAmount = isset($inc['min_tip_amount']) ? $inc['min_tip_amount'] : NULL;
/**
 * Amazon S3 Storage Configuration
 */
$s3Status = $inc['s3_status'];
$s3Bucket = isset($inc['s3_bucket']) ? $inc['s3_bucket'] : NULL;
$s3Region = isset($inc['s3_region']) ? $inc['s3_region'] : 'us-west-1';
$s3SecretKey = isset($inc['s3_secret_key']) ? $inc['s3_secret_key'] : NULL;
$s3Key = isset($inc['s3_key']) ? $inc['s3_key'] : NULL;
$s3PublicBase = isset($inc['s3_public_base']) ? $inc['s3_public_base'] : NULL;
// Environment overrides (developer-friendly)
$s3Status    = getenv('S3_STATUS')   ?: $s3Status;
$s3Bucket    = getenv('S3_BUCKET')   ?: $s3Bucket;
$s3Region    = getenv('S3_REGION')   ?: $s3Region;
$s3Key       = getenv('S3_KEY')      ?: $s3Key;
$s3SecretKey = getenv('S3_SECRET')   ?: $s3SecretKey;
$s3PublicBase = getenv('S3_PUBLIC_BASE') ?: $s3PublicBase;

/**
 * Wasabi Storage Configuration
 */
$WasStatus = isset($inc['was_status']) ? $inc['was_status'] : '0';
$WasBucket = isset($inc['was_bucket']) ? $inc['was_bucket'] : NULL;
$WasRegion = isset($inc['was_region']) ? $inc['was_region'] : 'us-west-1';
$WasSecretKey = isset($inc['was_secret_key']) ? $inc['was_secret_key'] : NULL;
$WasKey = isset($inc['was_key']) ? $inc['was_key'] : NULL;
$WasPublicBase = isset($inc['was_public_base']) ? $inc['was_public_base'] : NULL;
// Environment overrides (developer-friendly)
$WasStatus    = getenv('WASABI_STATUS') ?: $WasStatus;
$WasBucket    = getenv('WASABI_BUCKET') ?: $WasBucket;
$WasRegion    = getenv('WASABI_REGION') ?: $WasRegion;
$WasKey       = getenv('WASABI_KEY')    ?: $WasKey;
$WasSecretKey = getenv('WASABI_SECRET') ?: $WasSecretKey;
$WasPublicBase = getenv('WASABI_PUBLIC_BASE') ?: $WasPublicBase;

// DigitalOcean Spaces environment overrides
$digitalOceanStatus = getenv('SPACES_STATUS') ?: $digitalOceanStatus;
$oceanspace_name    = getenv('SPACES_NAME')   ?: $oceanspace_name;
$oceanregion        = getenv('SPACES_REGION') ?: $oceanregion;
$oceankey           = getenv('SPACES_KEY')    ?: $oceankey;
$oceansecret        = getenv('SPACES_SECRET') ?: $oceansecret;
$digitalOceanPublicBase = getenv('SPACES_PUBLIC_BASE') ?: $digitalOceanPublicBase;

// Optional local override file for MinIO admin settings
if (is_file(__DIR__ . '/minio_config.php')) {
    @include __DIR__ . '/minio_config.php';
}

/**
 * MinIO Storage Configuration (S3-compatible)
 * Supports env overrides for simple setup without DB fields.
 */
$minioStatus   = isset($inc['minio_status'])      ? $inc['minio_status']      : '0';
$minioBucket   = isset($inc['minio_bucket'])      ? $inc['minio_bucket']      : NULL;
$minioRegion   = isset($inc['minio_region'])      ? $inc['minio_region']      : 'us-east-1';
$minioKey      = isset($inc['minio_key'])         ? $inc['minio_key']         : NULL;
$minioSecret   = isset($inc['minio_secret_key'])  ? $inc['minio_secret_key']  : NULL;
$minioEndpoint = isset($inc['minio_endpoint'])    ? $inc['minio_endpoint']    : NULL; // e.g. https://minio.example.com:9000
$minioPublicBase = isset($inc['minio_public_base']) ? $inc['minio_public_base'] : NULL; // optional override
// Optional flags (string '1'/'0' expected if coming from DB)
$minioPathStyle = isset($inc['minio_path_style']) ? $inc['minio_path_style'] : '1';
$minioSslVerify = isset($inc['minio_ssl_verify']) ? $inc['minio_ssl_verify'] : '1';

// Environment overrides (developer-friendly)
$minioStatus     = getenv('MINIO_STATUS')      ?: $minioStatus;
$minioBucket     = getenv('MINIO_BUCKET')      ?: $minioBucket;
$minioRegion     = getenv('MINIO_REGION')      ?: $minioRegion;
$minioKey        = getenv('MINIO_KEY')         ?: $minioKey;
$minioSecret     = getenv('MINIO_SECRET')      ?: $minioSecret;
$minioEndpoint   = getenv('MINIO_ENDPOINT')    ?: $minioEndpoint;
$minioPublicBase = getenv('MINIO_PUBLIC_BASE') ?: $minioPublicBase;
$minioPathStyle  = getenv('MINIO_PATH_STYLE')  ?: $minioPathStyle;
$minioSslVerify  = getenv('MINIO_SSL_VERIFY')  ?: $minioSslVerify;

// Load unified object storage helpers
include_once __DIR__ . '/object_storage.php';

$iRL = true;

// Check cURL availability again (redundant fallback)
if (!function_exists('curl_init')) {
    $iRL = false;
    $disabled = true;
}

/**
 * Stripe Configuration for Subscriptions
 */
$stripeStatus = $inc['stripe_status'];
$stripeKey = $inc['stripe_secret_key'];
$stripePublicKey = $inc['stripe_public_key'];
$stripeCurrency = $inc['stripe_currency'];
if (empty($stripeCurrency)) { $stripeCurrency = $defaultCurrency ?: 'USD'; }
$stripeWebhookSecret = isset($inc['stripe_webhook_secret']) ? $inc['stripe_webhook_secret'] : '';

// Fallback to point-purchase Stripe keys if subscription-specific keys are empty
if ((empty($stripePublicKey) || empty($stripeKey)) && !empty($stripePaymentStatus) && $stripePaymentStatus === '1') {
    $isLiveMode = ($stripePaymentMode == 1);
    $fallbackPublic = $isLiveMode ? $stripePaymentLivePublicKey : $stripePaymentTestPublicKey;
    $fallbackSecret = $isLiveMode ? $stripePaymentLiveSecretKey : $stripePaymentTestSecretKey;
    if (!empty($fallbackPublic) && !empty($fallbackSecret)) {
        $stripePublicKey = $fallbackPublic;
        $stripeKey = $fallbackSecret;
        if (!empty($stripePaymentCurrency)) {
            $stripeCurrency = $stripePaymentCurrency;
        }
    }
}

$subscribeWeeklyMinimumAmount = $inc['sub_weekly_minimum_amount'];
$subscribeMonthlyMinimumAmount = $inc['sub_monthly_minimum_amount'];
$subscribeYearlyMinimumAmount = $inc['sub_yearly_minimum_amount'];
// Minimum withdrawal threshold for creators
$minimumWithdrawalAmount = $inc['minimum_withdrawal_amount'];

// Currency mode: 1 point = $1 (points ARE dollars now)
$onePointEqual = 1;

// Email configuration settings
$smtpOrMail = $inc['smtp_or_mail'];
$smtpHost = $inc['smtp_host'];
$smtpUserName = $inc['smtp_username'];
$smtpEmail = isset($inc['default_mail']) ? $inc['default_mail'] : NULL;
$smtpPassword = $inc['smtp_password'];
$smtpEncryption = $inc['smtp_encryption'];
$smtpPort = $inc['smtp_port'];

// Site email and email delivery status
$siteEmail = $inc['siteEmail'];
$emailSendStatus = $inc['emailSendStatus'];
$sendEmailForAll = $inc['send__email'];

// Registration and IP-based limits
$userCanRegister = $inc['register'];
$ipLimitStatus = $inc['ip_limit'];

// Live streaming modes
$paidLiveStreamingStatus = $inc['paid_live_streaming_status'];
$freeLiveStreamingStatus = $inc['free_live_streaming_status'];

// CAPTCHA integration (Google reCAPTCHA)
$captchaStatus = $inc['g_recaptcha_status'];
$captcha_site_key = isset($inc['g_recaptcha_site_key']) ? $inc['g_recaptcha_site_key'] : NULL;
$captcha_secret_key = isset($inc['g_recaptcha_secret_key']) ? $inc['g_recaptcha_secret_key'] : NULL;

// OneSignal push notification support
$oneSignalStatus = isset($inc['one_signal_status']) ? $inc['one_signal_status'] : NULL;
$oneSignalApi = isset($inc['one_signal_api']) ? $inc['one_signal_api'] : NULL;
$oneSignalRestApi = isset($inc['one_signal_rest_api']) ? $inc['one_signal_rest_api'] : NULL;

// Reels feature + max duration
$reelsFeatureStatus = isset($inc['reels_feature_status']) ? (string)$inc['reels_feature_status'] : '1';
$maxVideoDuration = isset($inc['max_video_duration']) ? (string)$inc['max_video_duration'] : '15';
// Jamendo (reels music library) — admin-configurable Client ID.
$jamendoClientId = isset($inc['jamendo_client_id']) ? (string)$inc['jamendo_client_id'] : '';
$blogFeatureStatus = isset($inc['blog_feature_status']) ? (string)$inc['blog_feature_status'] : '1';
$blogFeatureStatus = $blogFeatureStatus === '1' ? '1' : '0';
$blogReactionsStatus = isset($inc['blog_reactions_status']) ? (string)$inc['blog_reactions_status'] : '1';
$blogReactionsStatus = $blogReactionsStatus === '1' ? '1' : '0';
$blogShareStatus = isset($inc['blog_share_status']) ? (string)$inc['blog_share_status'] : '1';
$blogShareStatus = $blogShareStatus === '1' ? '1' : '0';
$blogSidebarAdsStatus = isset($inc['blog_sidebar_ads_status']) ? (string)$inc['blog_sidebar_ads_status'] : '1';
$blogSidebarAdsStatus = $blogSidebarAdsStatus === '1' ? '1' : '0';
// Poll feature switches and limits
$pollSystemStatus = isset($inc['poll_system_status']) ? (string)$inc['poll_system_status'] : '1';
$pollMaxOptions = isset($inc['poll_max_options']) ? (int)$inc['poll_max_options'] : 6;
$pollMinOptions = isset($inc['poll_min_options']) ? (int)$inc['poll_min_options'] : 2;
if ($pollMinOptions < 2) { $pollMinOptions = 2; }
if ($pollMaxOptions < $pollMinOptions) { $pollMaxOptions = $pollMinOptions; }
// Scheduled posts toggle and limits
$scheduledPostsStatus = isset($inc['scheduled_posts_status']) ? (string)$inc['scheduled_posts_status'] : '0';
$scheduledMaxDelayDays = isset($inc['scheduled_max_delay_days']) ? (int)$inc['scheduled_max_delay_days'] : 30;
if ($scheduledMaxDelayDays < 1) { $scheduledMaxDelayDays = 30; }
if ($scheduledMaxDelayDays > 30) { $scheduledMaxDelayDays = 30; }
// Quick actions layout preference
$quickActionsLayout = isset($inc['quick_actions_layout']) ? (string)$inc['quick_actions_layout'] : 'popup';
$quickActionsLayout = in_array($quickActionsLayout, ['popup', 'inline'], true) ? $quickActionsLayout : 'popup';

// Google Adsense configuration (admin only)
$adsenseStatus = isset($inc['adsense_status']) ? (string)$inc['adsense_status'] : '0';
$adsenseClientId = isset($inc['adsense_client_id']) ? (string)$inc['adsense_client_id'] : '';
$adsenseSlotTop = isset($inc['adsense_slot_top']) ? (string)$inc['adsense_slot_top'] : '';
$adsenseSlotInline = isset($inc['adsense_slot_inline']) ? (string)$inc['adsense_slot_inline'] : '';
$adsenseSlotFooter = isset($inc['adsense_slot_footer']) ? (string)$inc['adsense_slot_footer'] : '';
$adsenseSlotSidebar = isset($inc['adsense_slot_sidebar']) ? (string)$inc['adsense_slot_sidebar'] : '';
$adsenseInlineFrequency = isset($inc['adsense_inline_frequency']) ? (int)$inc['adsense_inline_frequency'] : 4;
$adsenseInlineOffset = isset($inc['adsense_inline_offset']) ? (int)$inc['adsense_inline_offset'] : 1;
if ($adsenseInlineFrequency < 1) { $adsenseInlineFrequency = 4; }
if ($adsenseInlineOffset < 1) { $adsenseInlineOffset = 1; }
$adsenseTopSize = isset($inc['adsense_top_size']) ? (string)$inc['adsense_top_size'] : 'responsive';
$adsenseInlineSize = isset($inc['adsense_inline_size']) ? (string)$inc['adsense_inline_size'] : 'responsive';
$adsenseFooterSize = isset($inc['adsense_footer_size']) ? (string)$inc['adsense_footer_size'] : 'responsive';
$adsenseSidebarSize = isset($inc['adsense_sidebar_size']) ? (string)$inc['adsense_sidebar_size'] : 'responsive';
$adsenseUpdatedAt = isset($inc['adsense_updated_at']) ? (int)$inc['adsense_updated_at'] : null;
// Centralized storage config and directory bootstrap (needs $maxVideoDuration)
include_once "storage_config.php";
ensureUploadDirectories();
// Subscription type statuses
$subWeekStatus = isset($inc['sub_weekly_status']) ? $inc['sub_weekly_status'] : 'no';
$subMontlyStatus = isset($inc['sub_mountly_status']) ? $inc['sub_mountly_status'] : 'no';
$subYearlyStatus = isset($inc['sub_yearly_status']) ? $inc['sub_yearly_status'] : 'no';

// Watermark visibility options
$watermarkStatus = isset($inc['watermark_status']) ? $inc['watermark_status'] : 'no';
$LinkWatermarkStatus = isset($inc['watermark_text_status']) ? $inc['watermark_text_status'] : 'no';
$watermarkPositionDefault = isset($inc['watermark_position']) ? strtolower(trim((string)$inc['watermark_position'])) : 'bottom_right';
$watermarkPosition = isset($iN) ? strtolower(trim((string)$iN->iN_GetSetting('watermark_position', $watermarkPositionDefault))) : $watermarkPositionDefault;
$allowedWatermarkPositions = array('top_left', 'top_right', 'bottom_left', 'bottom_right');
if (!in_array($watermarkPosition, $allowedWatermarkPositions, true)) {
    $watermarkPosition = 'bottom_right';
}
$watermarkOpacityDefault = isset($inc['watermark_opacity']) ? (int)$inc['watermark_opacity'] : 78;
$watermarkOpacity = isset($iN) ? (int)$iN->iN_GetSetting('watermark_opacity', $watermarkOpacityDefault) : $watermarkOpacityDefault;
if ($watermarkOpacity < 10) {
    $watermarkOpacity = 10;
}
if ($watermarkOpacity > 100) {
    $watermarkOpacity = 100;
}
$watermarkSizeDefault = isset($inc['watermark_size']) ? (int)$inc['watermark_size'] : 19;
$watermarkSize = isset($iN) ? (int)$iN->iN_GetSetting('watermark_size', $watermarkSizeDefault) : $watermarkSizeDefault;
if ($watermarkSize < 8) {
    $watermarkSize = 8;
}
if ($watermarkSize > 40) {
    $watermarkSize = 40;
}

// Name display configuration
$fullnameorusername = isset($inc['use_fullname_or_username']) ? $inc['use_fullname_or_username'] : 'no';

// Age confirmation popup toggle
$ageConfirm = isset($inc['age_confirm']) ? (string)$inc['age_confirm'] : '1';
$ageConfirmStatus = $ageConfirm === '1' ? 'yes' : 'no';

// Age verification providers (AgeVerif, Yoti, Didit)
$ageVerificationProviders = ['ageverif', 'yoti', 'didit'];
$ageVerifProvider = isset($inc['age_verif_provider']) ? strtolower(trim((string)$inc['age_verif_provider'])) : 'ageverif';
if (!in_array($ageVerifProvider, $ageVerificationProviders, true)) {
    $ageVerifProvider = 'ageverif';
}

// Age verification (AgeVerif OAuth2)
$ageVerifStatus = isset($inc['age_verif_status']) ? (string)$inc['age_verif_status'] : '0';
$ageVerifStatus = $ageVerifStatus === '1' ? '1' : '0';
$ageVerifForceSitewide = isset($inc['age_verif_force_sitewide']) ? (string)$inc['age_verif_force_sitewide'] : '0';
$ageVerifForceSitewide = $ageVerifForceSitewide === '1' ? '1' : '0';
$ageVerifEnvironment = isset($inc['age_verif_environment']) ? (string)$inc['age_verif_environment'] : 'live';
$ageVerifEnvironment = $ageVerifEnvironment === 'test' ? 'test' : 'live';
$ageVerifClientIdLive = isset($inc['age_verif_client_id']) ? (string)$inc['age_verif_client_id'] : '';
$ageVerifClientSecretLive = isset($inc['age_verif_client_secret']) ? (string)$inc['age_verif_client_secret'] : '';
$ageVerifAuthorizeUrlLive = isset($inc['age_verif_authorize_url']) ? (string)$inc['age_verif_authorize_url'] : '';
$ageVerifTokenUrlLive = isset($inc['age_verif_token_url']) ? (string)$inc['age_verif_token_url'] : '';
$ageVerifVerifyUrlLive = isset($inc['age_verif_verify_url']) ? (string)$inc['age_verif_verify_url'] : '';
$ageVerifScopeLive = isset($inc['age_verif_scope']) ? (string)$inc['age_verif_scope'] : '';
$ageVerifClientIdTest = isset($inc['age_verif_client_id_test']) ? (string)$inc['age_verif_client_id_test'] : '';
$ageVerifClientSecretTest = isset($inc['age_verif_client_secret_test']) ? (string)$inc['age_verif_client_secret_test'] : '';
$ageVerifAuthorizeUrlTest = isset($inc['age_verif_authorize_url_test']) ? (string)$inc['age_verif_authorize_url_test'] : '';
$ageVerifTokenUrlTest = isset($inc['age_verif_token_url_test']) ? (string)$inc['age_verif_token_url_test'] : '';
$ageVerifVerifyUrlTest = isset($inc['age_verif_verify_url_test']) ? (string)$inc['age_verif_verify_url_test'] : '';
$ageVerifScopeTest = isset($inc['age_verif_scope_test']) ? (string)$inc['age_verif_scope_test'] : '';
if ($ageVerifEnvironment === 'test') {
    $ageVerifClientId = $ageVerifClientIdTest;
    $ageVerifClientSecret = $ageVerifClientSecretTest;
    $ageVerifAuthorizeUrl = $ageVerifAuthorizeUrlTest;
    $ageVerifTokenUrl = $ageVerifTokenUrlTest;
    $ageVerifVerifyUrl = $ageVerifVerifyUrlTest;
    $ageVerifScope = $ageVerifScopeTest;
} else {
    $ageVerifClientId = $ageVerifClientIdLive;
    $ageVerifClientSecret = $ageVerifClientSecretLive;
    $ageVerifAuthorizeUrl = $ageVerifAuthorizeUrlLive;
    $ageVerifTokenUrl = $ageVerifTokenUrlLive;
    $ageVerifVerifyUrl = $ageVerifVerifyUrlLive;
    $ageVerifScope = $ageVerifScopeLive;
}
$ageVerifMinAge = isset($inc['age_verif_min_age']) ? (int)$inc['age_verif_min_age'] : 18;
if ($ageVerifMinAge < 18) {
    $ageVerifMinAge = 18;
}

// Yoti age verification (provider-configurable OAuth2)
$yotiAgeVerifStatus = isset($inc['yoti_age_verif_status']) ? (string)$inc['yoti_age_verif_status'] : '0';
$yotiAgeVerifStatus = $yotiAgeVerifStatus === '1' ? '1' : '0';
$yotiAgeVerifForceSitewide = isset($inc['yoti_age_verif_force_sitewide']) ? (string)$inc['yoti_age_verif_force_sitewide'] : '0';
$yotiAgeVerifForceSitewide = $yotiAgeVerifForceSitewide === '1' ? '1' : '0';
$yotiAgeVerifEnvironment = 'live';
$yotiAgeVerifClientIdLive = isset($inc['yoti_age_verif_client_id']) ? (string)$inc['yoti_age_verif_client_id'] : '';
$yotiAgeVerifClientSecretLive = isset($inc['yoti_age_verif_client_secret']) ? (string)$inc['yoti_age_verif_client_secret'] : '';
$yotiAgeVerifAuthorizeUrlLive = isset($inc['yoti_age_verif_authorize_url']) ? (string)$inc['yoti_age_verif_authorize_url'] : '';
$yotiAgeVerifTokenUrlLive = isset($inc['yoti_age_verif_token_url']) ? (string)$inc['yoti_age_verif_token_url'] : '';
$yotiAgeVerifVerifyUrlLive = isset($inc['yoti_age_verif_verify_url']) ? (string)$inc['yoti_age_verif_verify_url'] : '';
$yotiAgeVerifScopeLive = isset($inc['yoti_age_verif_scope']) ? (string)$inc['yoti_age_verif_scope'] : '';
$yotiAgeVerifClientIdTest = isset($inc['yoti_age_verif_client_id_test']) ? (string)$inc['yoti_age_verif_client_id_test'] : '';
$yotiAgeVerifClientSecretTest = isset($inc['yoti_age_verif_client_secret_test']) ? (string)$inc['yoti_age_verif_client_secret_test'] : '';
$yotiAgeVerifAuthorizeUrlTest = isset($inc['yoti_age_verif_authorize_url_test']) ? (string)$inc['yoti_age_verif_authorize_url_test'] : '';
$yotiAgeVerifTokenUrlTest = isset($inc['yoti_age_verif_token_url_test']) ? (string)$inc['yoti_age_verif_token_url_test'] : '';
$yotiAgeVerifVerifyUrlTest = isset($inc['yoti_age_verif_verify_url_test']) ? (string)$inc['yoti_age_verif_verify_url_test'] : '';
$yotiAgeVerifScopeTest = isset($inc['yoti_age_verif_scope_test']) ? (string)$inc['yoti_age_verif_scope_test'] : '';
if ($yotiAgeVerifEnvironment === 'test') {
    $yotiAgeVerifClientId = $yotiAgeVerifClientIdTest;
    $yotiAgeVerifClientSecret = $yotiAgeVerifClientSecretTest;
    $yotiAgeVerifAuthorizeUrl = $yotiAgeVerifAuthorizeUrlTest;
    $yotiAgeVerifTokenUrl = $yotiAgeVerifTokenUrlTest;
    $yotiAgeVerifVerifyUrl = $yotiAgeVerifVerifyUrlTest;
    $yotiAgeVerifScope = $yotiAgeVerifScopeTest;
} else {
    $yotiAgeVerifClientId = $yotiAgeVerifClientIdLive;
    $yotiAgeVerifClientSecret = $yotiAgeVerifClientSecretLive;
    $yotiAgeVerifAuthorizeUrl = $yotiAgeVerifAuthorizeUrlLive;
    $yotiAgeVerifTokenUrl = $yotiAgeVerifTokenUrlLive;
    $yotiAgeVerifVerifyUrl = $yotiAgeVerifVerifyUrlLive;
    $yotiAgeVerifScope = $yotiAgeVerifScopeLive;
}
$yotiAgeVerifMinAge = isset($inc['yoti_age_verif_min_age']) ? (int)$inc['yoti_age_verif_min_age'] : 19;
if ($yotiAgeVerifMinAge < 19) {
    $yotiAgeVerifMinAge = 19;
} elseif ($yotiAgeVerifMinAge > 25) {
    $yotiAgeVerifMinAge = 25;
}

// Didit age verification (hosted flow)
$diditAgeVerifStatus = isset($inc['didit_age_verif_status']) ? (string)$inc['didit_age_verif_status'] : '0';
$diditAgeVerifStatus = $diditAgeVerifStatus === '1' ? '1' : '0';
$diditAgeVerifForceSitewide = isset($inc['didit_age_verif_force_sitewide']) ? (string)$inc['didit_age_verif_force_sitewide'] : '0';
$diditAgeVerifForceSitewide = $diditAgeVerifForceSitewide === '1' ? '1' : '0';
$diditAgeVerifEnvironment = isset($inc['didit_age_verif_environment']) ? (string)$inc['didit_age_verif_environment'] : 'live';
$diditAgeVerifEnvironment = $diditAgeVerifEnvironment === 'test' ? 'test' : 'live';
$diditAgeVerifClientIdLive = isset($inc['didit_age_verif_client_id']) ? (string)$inc['didit_age_verif_client_id'] : '';
$diditAgeVerifClientSecretLive = isset($inc['didit_age_verif_client_secret']) ? (string)$inc['didit_age_verif_client_secret'] : '';
$diditAgeVerifAuthorizeUrlLive = isset($inc['didit_age_verif_authorize_url']) ? (string)$inc['didit_age_verif_authorize_url'] : '';
$diditAgeVerifTokenUrlLive = isset($inc['didit_age_verif_token_url']) ? (string)$inc['didit_age_verif_token_url'] : '';
$diditAgeVerifVerifyUrlLive = isset($inc['didit_age_verif_verify_url']) ? (string)$inc['didit_age_verif_verify_url'] : '';
$diditAgeVerifScopeLive = isset($inc['didit_age_verif_scope']) ? (string)$inc['didit_age_verif_scope'] : '';
$diditAgeVerifClientIdTest = isset($inc['didit_age_verif_client_id_test']) ? (string)$inc['didit_age_verif_client_id_test'] : '';
$diditAgeVerifClientSecretTest = isset($inc['didit_age_verif_client_secret_test']) ? (string)$inc['didit_age_verif_client_secret_test'] : '';
$diditAgeVerifAuthorizeUrlTest = isset($inc['didit_age_verif_authorize_url_test']) ? (string)$inc['didit_age_verif_authorize_url_test'] : '';
$diditAgeVerifTokenUrlTest = isset($inc['didit_age_verif_token_url_test']) ? (string)$inc['didit_age_verif_token_url_test'] : '';
$diditAgeVerifVerifyUrlTest = isset($inc['didit_age_verif_verify_url_test']) ? (string)$inc['didit_age_verif_verify_url_test'] : '';
$diditAgeVerifScopeTest = isset($inc['didit_age_verif_scope_test']) ? (string)$inc['didit_age_verif_scope_test'] : '';
if ($diditAgeVerifEnvironment === 'test') {
    $diditAgeVerifClientId = $diditAgeVerifClientIdTest;
    $diditAgeVerifClientSecret = $diditAgeVerifClientSecretTest;
    $diditAgeVerifAuthorizeUrl = $diditAgeVerifAuthorizeUrlTest;
    $diditAgeVerifTokenUrl = $diditAgeVerifTokenUrlTest;
    $diditAgeVerifVerifyUrl = $diditAgeVerifVerifyUrlTest;
    $diditAgeVerifScope = $diditAgeVerifScopeTest;
} else {
    $diditAgeVerifClientId = $diditAgeVerifClientIdLive;
    $diditAgeVerifClientSecret = $diditAgeVerifClientSecretLive;
    $diditAgeVerifAuthorizeUrl = $diditAgeVerifAuthorizeUrlLive;
    $diditAgeVerifTokenUrl = $diditAgeVerifTokenUrlLive;
    $diditAgeVerifVerifyUrl = $diditAgeVerifVerifyUrlLive;
    $diditAgeVerifScope = $diditAgeVerifScopeLive;
}
$diditAgeVerifMinAge = isset($inc['didit_age_verif_min_age']) ? (int)$inc['didit_age_verif_min_age'] : 18;
if ($diditAgeVerifMinAge < 18) {
    $diditAgeVerifMinAge = 18;
}

// Selected provider configuration (single active flow)
$ageVerificationProvider = $ageVerifProvider;
$ageVerificationStatus = '0';
$ageVerificationForceSitewide = '0';
$ageVerificationEnvironment = 'live';
$ageVerificationClientId = '';
$ageVerificationClientSecret = '';
$ageVerificationAuthorizeUrl = '';
$ageVerificationTokenUrl = '';
$ageVerificationVerifyUrl = '';
$ageVerificationScope = '';
$ageVerificationMinAge = 18;
if ($ageVerificationProvider === 'yoti') {
    $ageVerificationStatus = $yotiAgeVerifStatus;
    $ageVerificationForceSitewide = $yotiAgeVerifForceSitewide;
    $ageVerificationEnvironment = $yotiAgeVerifEnvironment;
    $ageVerificationClientId = $yotiAgeVerifClientId;
    $ageVerificationClientSecret = $yotiAgeVerifClientSecret;
    $ageVerificationAuthorizeUrl = $yotiAgeVerifAuthorizeUrl;
    $ageVerificationTokenUrl = $yotiAgeVerifTokenUrl;
    $ageVerificationVerifyUrl = $yotiAgeVerifVerifyUrl;
    $ageVerificationScope = $yotiAgeVerifScope;
    $ageVerificationMinAge = $yotiAgeVerifMinAge;
} elseif ($ageVerificationProvider === 'didit') {
    $ageVerificationStatus = $diditAgeVerifStatus;
    $ageVerificationForceSitewide = $diditAgeVerifForceSitewide;
    $ageVerificationEnvironment = $diditAgeVerifEnvironment;
    $ageVerificationClientId = $diditAgeVerifClientId;
    $ageVerificationClientSecret = $diditAgeVerifClientSecret;
    $ageVerificationAuthorizeUrl = $diditAgeVerifAuthorizeUrl;
    $ageVerificationTokenUrl = $diditAgeVerifTokenUrl;
    $ageVerificationVerifyUrl = $diditAgeVerifVerifyUrl;
    $ageVerificationScope = $diditAgeVerifScope;
    $ageVerificationMinAge = $diditAgeVerifMinAge;
} else {
    $ageVerificationStatus = $ageVerifStatus;
    $ageVerificationForceSitewide = $ageVerifForceSitewide;
    $ageVerificationEnvironment = $ageVerifEnvironment;
    $ageVerificationClientId = $ageVerifClientId;
    $ageVerificationClientSecret = $ageVerifClientSecret;
    $ageVerificationAuthorizeUrl = $ageVerifAuthorizeUrl;
    $ageVerificationTokenUrl = $ageVerifTokenUrl;
    $ageVerificationVerifyUrl = $ageVerifVerifyUrl;
    $ageVerificationScope = $ageVerifScope;
    $ageVerificationMinAge = $ageVerifMinAge;
}

// Agency module status
$agencyModuleStatus = isset($inc['agency_module_status']) ? $inc['agency_module_status'] : 'yes';
$registrationRoleMode = $iN->iN_NormalizeRegistrationRoleMode(
    isset($inc['registration_role_mode'])
        ? (string)$inc['registration_role_mode']
        : (string)$iN->iN_GetSetting('registration_role_mode', 'legacy')
);
$agencyAutoCreatorOnApprovalRaw = isset($inc['agency_auto_creator_on_approval'])
    ? strtolower(trim((string)$inc['agency_auto_creator_on_approval']))
    : strtolower(trim((string)$iN->iN_GetSetting('agency_auto_creator_on_approval', 'yes')));
$agencyAutoCreatorOnApproval = in_array($agencyAutoCreatorOnApprovalRaw, ['yes', 'no'], true)
    ? $agencyAutoCreatorOnApprovalRaw
    : 'yes';

// Agency profile field controls
$agencyProfileAboutStatus = isset($inc['agency_profile_about_status']) ? $inc['agency_profile_about_status'] : 'yes';
$agencyProfileServicesStatus = isset($inc['agency_profile_services_status']) ? $inc['agency_profile_services_status'] : 'yes';
$agencyProfileLogoStatus = isset($inc['agency_profile_logo_status']) ? $inc['agency_profile_logo_status'] : 'yes';
$agencyProfileCoverStatus = isset($inc['agency_profile_cover_status']) ? $inc['agency_profile_cover_status'] : 'yes';
$agencyProfileSocialStatus = isset($inc['agency_profile_social_status']) ? $inc['agency_profile_social_status'] : 'yes';

// Agency boost pricing defaults
$agencyBoostPrice = isset($inc['agency_boost_price']) ? (float)$inc['agency_boost_price'] : 0.0;
$agencyBoostPointPrice = isset($inc['agency_boost_point_price']) ? (int)$inc['agency_boost_point_price'] : 0;
$agencyBoostDefaultDays = isset($inc['agency_boost_default_days']) ? (int)$inc['agency_boost_default_days'] : 7;

// Earn point system toggle
$earnPointSystemStatus = isset($inc['earn_point_status']) ? $inc['earn_point_status'] : 'no';

// Creator permission toggle
$beaCreatorStatus = isset($inc['be_a_creator_status']) ? $inc['be_a_creator_status'] : NULL;

// Video call feature configurations
$videoCallFeatureStatus = isset($inc['video_call_feature_status']) ? $inc['video_call_feature_status'] : NULL;
$whoCanCreateVideoCall = isset($inc['who_can_careate_video_call']) ? $inc['who_can_careate_video_call'] : NULL;
$isVideoCallFree = isset($inc['is_video_call_free']) ? $inc['is_video_call_free'] : NULL;

// Point limit enforcement
$maximumPointInADay = isset($inc['max_point_in_a_day']) ? $inc['max_point_in_a_day'] : '1';

// Draw text option in editor or image tools
$drawTextStatus = isset($inc['enable_disable_drawtext']) ? $inc['enable_disable_drawtext'] : '0';

// Boosted post visibility toggle
$boostedPostEnableDisable = isset($inc['boosted_post_status']) ? $inc['boosted_post_status'] : 'no';

// Boost expiration days (in addition to view-based expiration). Default: 30 days.
$boostPostExpireDays = isset($inc['boost_post_expire_days']) ? (int) $inc['boost_post_expire_days'] : 30;
if ($boostPostExpireDays < 1) {
    $boostPostExpireDays = 30;
}
// Load creator types for categorization or display
$creatorTYpes = $iN->iN_CreatorTypes();

/**
 * Utility function to get file extension
 *
 * @param string $str
 * @return string
 */
function getExtension($str) {
    $i = strrpos($str, ".");
    if (!$i) {
        return "";
    }
    $l = strlen($str) - $i;
    $ext = substr($str, $i + 1, $l);
    return $ext;
}

/**
 * Validate encryption key (obfuscated license or dynamic ID)
 * Redirects to obfuscated path if invalid.
 */
function inSub($mycd, $mycdStatus) {
    $check = preg_match('/(.*)-(.*)-(.*)-(.*)-(.*)/', $mycd);
    if ($check == 0 && ($mycdStatus == 1 || $mycdStatus == '' || empty($mycdStatus))) {
        header('Location: ' . route_url(base64_decode('YmVsZWdhbA==')));
        exit();
    }
}

/**
 * Convert bytes to MB with formatting
 *
 * @param int $size
 * @return string
 */
function convert_to_mb($size) {
    $mb_size = $size / 1048576;
    $format_size = number_format($mb_size, 2);
    return $format_size;
}
/**
 * Format number with comma for thousands and dot for decimal
 *
 * @param float|int|string $number
 * @return string
 */
function addCommasAndDots($number) {
    // Treat null/empty/false as zero to avoid blank outputs in dashboards
    if ($number === null || $number === '' || $number === false) {
        $number = 0;
    }
    if (is_numeric($number)) {
        return number_format((float)$number, 2, ',', '.');
    }
    // For non-numeric strings (like 'N/A'), return as-is
    return $number;
}

/**
 * Format number with thousand separators only
 *
 * @param float|int|string $number
 * @return string
 */
function addCommasNoDot($number) {
    if (is_numeric($number)) {
        $number = number_format((float)$number, 0, '', '.');
        return $number;
    } else {
        return $number;
    }
}

/**
 * Format a monetary amount using the configured currency rules.
 *
 * @param float|int|string $amount
 * @param string|null      $currencyCode
 * @param int|null         $decimals Override decimal places (falls back to config)
 *
 * @return string
 */
function formatCurrency($amount, $currencyCode = null, $decimals = null) {
    global $currencys, $defaultCurrency, $currencySymbolPosition, $currencyDecimalSeparator, $currencyThousandSeparator, $currencyDecimalPlaces;
    $code = $currencyCode ?: ($defaultCurrency ?? 'USD');
    $symbol = $currencys[$code] ?? $code;
    $value = is_numeric($amount) ? (float)$amount : 0.0;
    $places = $decimals !== null ? (int)$decimals : (int)($currencyDecimalPlaces ?? 2);
    if ($places < 0) { $places = 0; }
    if ($places > 4) { $places = 4; }
    $formattedNumber = number_format($value, $places, $currencyDecimalSeparator, $currencyThousandSeparator);
    if ($currencySymbolPosition === 'right') {
        return trim($formattedNumber . ' ' . $symbol);
    }
    return $symbol . $formattedNumber;
}

/**
 * Validate encryption key without redirection
 * Used silently to validate without user disruption
 */
function inSen($mycd, $mycdStatus) {
    $check = preg_match('/(.*)-(.*)-(.*)-(.*)-(.*)/', $mycd);
    if ($check == 0 && ($mycdStatus == 1 || $mycdStatus == '' || empty($mycdStatus))) {
        exit();
    }
}
// Premium and live gift plan data for UI and pricing logic
$purchasePointPlanTable = $iN->iN_PremiumPlans();
$planTableList = $iN->iN_PremiumPlansListFromAdmin();
$planLiveGifTableList = $iN->iN_LiveGifPlansListFromAdmin();
$sendCoinList = $iN->iN_LiveGiftSendList();

// Check session for logged-in user
if (isset($_COOKIE[$cookieName])) {
    $logedIn = '1';
    $sessionKey = isset($_COOKIE[$cookieName]) ? $iN->iN_Secure($_COOKIE[$cookieName]) : NULL;
    $user_id = $iN->iN_GetUserIDFromSessionKey($sessionKey);

    if ($user_id) {
        $userData = $iN->iN_GetUserDetails($user_id);
        $userFullName = $iN->sanitize_output($userData['i_user_fullname'], $base_url);
        $userName = $userData['i_username'];
        $userEmail = $userData['i_user_email'];
        $userID = $userData['iuid'];

        // Notification counts
        $totalNotifications = $iN->iN_GetNewNotificationSum($userID);
        $totalMessageNotifications = $iN->iN_GetNewMessageNotificationSum($userID);

        // Language and type
        $userLang = $userData['lang'];
        $userType = $userData['userType'];
        $rawSignupIntent = isset($userData['signup_intent']) ? (string)$userData['signup_intent'] : 'user';
        $userSignupIntent = $iN->iN_GetEffectiveSignupIntentForMode($rawSignupIntent, $registrationRoleMode);
        // Display user full name or username based on setting
        if ($fullnameorusername == 'no') {
            $userFullName = $userName;
        }

        $userBio = isset($userData['u_bio']) ? $userData['u_bio'] : NULL;
        $userBirthDay = isset($userData['birthday']) ? $userData['birthday'] : NULL;
        $userQrCode = isset($userData['qr_image']) ? $userData['qr_image'] : NULL;

        // Format birthday to dd/mm/yyyy if set
        if ($userBirthDay) {
            $userBirthDay = DateTime::createFromFormat('Y-m-d', $userBirthDay)->format('d/m/Y');
        }

        $verifData = $iN->iN_CheckUserHasVerificationRequest($userID);
        $verStatus = '';
        $userGender = $userData['user_gender'];
        $userWhoCanSeePost = $userData['post_who_can_see'];
        $userProfileCategory = isset($userData['profile_category']) ? $userData['profile_category'] : NULL;
        if (empty($userProfileCategory)) {
            DB::exec("UPDATE i_users SET profile_category = 'normal_user' WHERE iuid = ?", [(int)$userID]);
            $userProfileCategory = 'normal_user';
        }

        // Get notifications for UI rendering
        $Notifications = $iN->iN_GetAllNotificationList($userID, $scrollLimit);
        $userProfileUrl = $base_url . $userName;
        $userAvatar = $iN->iN_UserAvatar($userID, $base_url);
        $userCover = $iN->iN_UserCover($userID, $base_url);
        // User statistics for dashboard
        $totalSubscribers = $iN->iN_UserTotalSubscribers($userID);
        $totalPointPayments = $iN->iN_UserTotalPointPayments($userID);
        $totalSubscriptions = $iN->iN_UserTotalSubscribtions($userID);
        $totalFollowingUsers = $iN->iN_UserTotalFollowingUsers($userID);
        $totalFollowerUsers = $iN->iN_UserTotalFollowerUsers($userID);
        $totalBlockedUsers = $iN->iN_UserTotalBlocks($userID);
        $totalPurchasedPoints = $iN->iN_UserTotalPointPurchase($userID);

        // Verification and account statuses
        $certificationStatus = $userData['certification_status'];
        $validationStatus = $userData['validation_status'];
        $conditionStatus = $userData['condition_status'];
        $feesStatus = $userData['fees_status'];
        $payoutStatus = $userData['payout_status'];

        // UI preferences
        $lightDark = $userData['light_dark'];
        if (!in_array((string)$lightDark, ['light', 'dark'], true)) {
            $lightDark = $defaultStyle;
        }

        // Optional data
        $deviceKey = isset($userData['device_key']) ? $userData['device_key'] : NULL;
        $lastLoginTime = $userData['last_login_time'];
        $countryCode = isset($userData['countryCode']) ? $userData['countryCode'] : NULL;
        $notificationEmailStatus = $userData['email_notification_status'];
        $showHidePostOnlineOffline = $userData['show_hide_posts'];
        $connectionsVisibility = isset($userData['connections_visibility']) ? (string)$userData['connections_visibility'] : '1';
        $p_connectionsVisibility = $connectionsVisibility;
        $messageSendStatus = $userData['message_status'];
        $showProfileGender = isset($userData['show_profile_gender']) ? (string)$userData['show_profile_gender'] : '1';
        $showProfileAge = isset($userData['show_profile_age']) ? (string)$userData['show_profile_age'] : '1';
        $showProfileBirthdate = isset($userData['show_profile_birthdate']) ? (string)$userData['show_profile_birthdate'] : '1';
        $showProfileCategory = isset($userData['show_profile_category']) ? (string)$userData['show_profile_category'] : '1';
        $showProfileLikes = isset($userData['show_profile_likes']) ? (string)$userData['show_profile_likes'] : '1';
        $showProfileComments = isset($userData['show_profile_comments']) ? (string)$userData['show_profile_comments'] : '1';
        $showProfileBio = isset($userData['show_profile_bio']) ? (string)$userData['show_profile_bio'] : '1';
        $showProfileSocial = isset($userData['show_profile_social']) ? (string)$userData['show_profile_social'] : '1';
        $loginWith = isset($userData['login_with']) ? $userData['login_with'] : NULL;
        $userAgeVerifyStatus = isset($userData['age_verify_status']) ? (string)$userData['age_verify_status'] : '0';
        $userAgeVerifyProvider = isset($userData['age_verify_provider']) ? (string)$userData['age_verify_provider'] : '';
        $userAgeVerifiedAt = isset($userData['age_verified_at']) ? (int)$userData['age_verified_at'] : null;
        $userAgeVerifyRef = isset($userData['age_verify_ref']) ? (string)$userData['age_verify_ref'] : null;
        // Email verification logic based on login method
        if (!empty($loginWith)) {
            $userEmailVerificationStatus = $userData['email_verify_status'];
        } else {
            $userEmailVerificationStatus = $userData['email_verify_status'];
        }

        $thanksNOtForTip = isset($userData['thanks_for_tip']) ? $userData['thanks_for_tip'] : NULL;
        $userTimeZone = isset($userData['u_timezone']) ? $userData['u_timezone'] : NULL;
        $myVideoCallPrice = isset($userData['video_call_price']) ? $userData['video_call_price'] : NULL;

        // Apply user-specific timezone if available
        if ($userTimeZone) {
            date_default_timezone_set($userTimeZone);
        }

        // Payout details
        $payoutMethod = isset($userData['payout_method']) ? $userData['payout_method'] : NULL;
        $paypalEmail = isset($userData['paypal_email']) ? $userData['paypal_email'] : NULL;
        $bankAccount = isset($userData['bank_account']) ? $userData['bank_account'] : NULL;
        $payoneerEmail = isset($userData['payoneer_email']) ? $userData['payoneer_email'] : NULL;
        $zelleEmail = isset($userData['zelle_email']) ? $userData['zelle_email'] : NULL;
        $westernUnionFullName = isset($userData['western_union_full_name']) ? $userData['western_union_full_name'] : NULL;
        $westernUnionDocumentId = isset($userData['western_union_document_id']) ? $userData['western_union_document_id'] : NULL;
        $bitcoinWalletAddress = isset($userData['bitcoin_wallet_address']) ? $userData['bitcoin_wallet_address'] : NULL;
        $mercadoPagoAlias = isset($userData['mercadopago_alias']) ? $userData['mercadopago_alias'] : NULL;
        $mercadoPagoCvu = isset($userData['mercadopago_cvu']) ? $userData['mercadopago_cvu'] : NULL;

        // Structured bank-transfer payout details
        $bankCountry        = isset($userData['bank_country']) ? $userData['bank_country'] : NULL;
        $ibanNumber         = isset($userData['iban_number']) ? $userData['iban_number'] : NULL;
        $routingNumber      = isset($userData['routing_number']) ? $userData['routing_number'] : NULL;
        $accountNumber      = isset($userData['account_number']) ? $userData['account_number'] : NULL;
        $accountHolderName  = isset($userData['account_holder_name']) ? $userData['account_holder_name'] : NULL;
        $phoneCountryCode   = isset($userData['phone_country_code']) ? $userData['phone_country_code'] : NULL;
        $phoneNumber        = isset($userData['phone_number']) ? $userData['phone_number'] : NULL;
        $streetAddress      = isset($userData['street_address']) ? $userData['street_address'] : NULL;
        $country            = isset($userData['bank_address_country']) ? $userData['bank_address_country'] : NULL;
        $state              = isset($userData['bank_address_state']) ? $userData['bank_address_state'] : NULL;
        $city               = isset($userData['bank_address_city']) ? $userData['bank_address_city'] : NULL;
        $postalCode         = isset($userData['postal_code']) ? $userData['postal_code'] : NULL;

        // Subscription plans
        $WeeklySubDetail = $iN->iN_GetUserSubscriptionPlanDetails($userID, 'weekly');
        $MonthlySubDetail = $iN->iN_GetUserSubscriptionPlanDetails($userID, 'monthly');
        $YearlySubDetail = $iN->iN_GetUserSubscriptionPlanDetails($userID, 'yearly');

        // Monthly earnings calculation
        $calculateCurrentEarning = $iN->iN_CalculateCurrentMonthEarning($userID);
        $cEarning = isset($calculateCurrentEarning['calculate']) ? $calculateCurrentEarning['calculate'] : '0';
        // Wallet data (wallet_points now stores currency balance)
        $userCurrentPoints = isset($userData['wallet_points']) ? $userData['wallet_points'] : '0';
        $userWallet = isset($userData['wallet_money']) ? $userData['wallet_money'] : '0';

        // Message permission
        $whoCanSendYouMessage = isset($userData['who_can_send_message']) ? $userData['who_can_send_message'] : '0';

        /**
         * Format numbers based on user or site preference
         * @param float|int $number
         * @param int $dec
         * @param bool $trim
         * @return string
         */
        function format_number($number, $dec = 0, $trim = false) {
            if ($trim) {
                $parts = explode(".", (round($number, $dec) * 1));
                $dec = isset($parts[1]) ? strlen($parts[1]) : 0;
            }
            $formatted = number_format($number, $dec);
            return $formatted;
        }

        // Load user language pack (subfolder-safe)
        $___lang_path = __resolve_lang_path($userLang, $__LANG_DIR);
        include $___lang_path;

        // Determine visibility setting for posts
        if ($userWhoCanSeePost == 1) {
            $activeWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('50') . '</div> ' . $LANG['weveryone'];
        } else if ($userWhoCanSeePost == 2) {
            $activeWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('15') . '</div> ' . $LANG['wfollowers'];
        } else if ($userWhoCanSeePost == 3) {
            $activeWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('51') . '</div> ' . $LANG['wsubscribers'];
        } else if ($userWhoCanSeePost == 4) {
            $activeWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('9') . '</div> ' . $LANG['wpremium'];
        }
        /**
         * Retrieve all payment methods configuration
         */
        $method = $iN->iN_PaymentMethods();

        /**
         * CCBill payment configuration
         */
        $ccbill_AccountNumber = $method['ccbill_account_number'];
        $ccbill_SubAccountNumber = $method['ccbill_subaccount_number'];
        $ccbill_FlexID = $method['ccbill_flex_form_id'];
        $ccbill_SaltKey = $method['ccbill_salt_key'];
$ccbill_Status = $method['ccbill_status'];
$ccbill_Currency = $method['ccbill_currency'];
$ccbill_CancelUsername = isset($method['ccbill_cancel_username']) ? $method['ccbill_cancel_username'] : '';
$ccbill_CancelPassword = isset($method['ccbill_cancel_password']) ? $method['ccbill_cancel_password'] : '';
$ccbillHasCredentials = !empty($ccbill_AccountNumber) && !empty($ccbill_SubAccountNumber) && !empty($ccbill_FlexID) && !empty($ccbill_SaltKey);
$ccbillActiveAndReady = ($ccbill_Status == '1' && $ccbillHasCredentials);
$ccbillBeta = isset($method['ccbill_beta']) ? $method['ccbill_beta'] : '0';

        /**
         * PayPal payment configuration
         */
$payPalPaymentMode = $method['paypal_payment_mode'];
$payPalPaymentStatus = $method['paypal_active_pasive'];
$payPalPaymentSedboxBusinessEmail = $method['paypal_sendbox_business_email'];
$payPalPaymentProductBusinessEmail = $method['paypal_product_business_email'];
$payPalCurrency = $method['paypal_crncy'];
$payPalPaymentBeta = isset($method['paypal_beta']) ? $method['paypal_beta'] : '0';
        /**
         * BitPay payment configuration
         */
$bitPayPaymentMode = $method['bitpay_payment_mode'];
$bitPayPaymentStatus = $method['bitpay_active_pasive'];
$bitPayPaymentNotificationEmail = $method['bitpay_notification_email'];
$bitPayPaymentPassword = $method['bitpay_password'];
$bitPayPaymentPairingCode = $method['bitpay_pairing_code'];
$bitPayPaymentLabel = $method['bitpay_label'];
$bitPayPaymentCurrency = $method['bitpay_crncy'];
$bitPayPaymentBeta = isset($method['bitpay_beta']) ? $method['bitpay_beta'] : '0';

        /**
         * Stripe payment configuration
         */
        $stripePaymentMode = $method['stripe_payment_mode'];
        $stripePaymentStatus = $method['stripe_active_pasive'];
$stripePaymentTestSecretKey = $method['stripe_test_secret_key'];
$stripePaymentTestPublicKey = $method['stripe_test_public_key'];
$stripePaymentLiveSecretKey = $method['stripe_live_secret_key'];
$stripePaymentLivePublicKey = $method['stripe_live_public_key'];
$stripePaymentCurrency = $method['stripe_crncy'];
$stripePaymentBeta = isset($method['stripe_beta']) ? $method['stripe_beta'] : '0';

        /**
         * Authorize.Net configuration
         */
        $autHorizePaymentMode = $method['authorize_payment_mode'];
        $autHorizePaymentStatus = $method['authorizenet_active_pasive'];
        $autHorizePaymentTestsApID = $method['authorizenet_test_ap_id'];
$autHorizePaymentTestTransitionKey = $method['authorizenet_test_transaction_key'];
$autHorizePaymentLiveApID = $method['authorizenet_live_api_id'];
$autHorizePaymentLiveTransitionkey = $method['authorizenet_live_transaction_key'];
$autHorizePaymentCurrency = $method['authorize_crncy'];
$autHorizePaymentBeta = isset($method['authorizenet_beta']) ? $method['authorizenet_beta'] : '0';
        // Select appropriate Authorize.Net keys based on payment mode
        if ($autHorizePaymentMode == '0') {
            $autName = $method['authorizenet_test_ap_id'];
            $autKey = $method['authorizenet_test_transaction_key'];
        } else {
            $autName = $method['authorizenet_live_api_id'];
            $autKey = $method['authorizenet_live_transaction_key'];
        }

        /**
         * iyziCo payment configuration
         */
        $iyziCoPaymentMode = $method['iyzico_payment_mode'];
        $iyziCoPaymentStatus = $method['iyzico_active_pasive'];
$iyziCoPaymentTestSecretKey = $method['iyzico_testing_secret_key'];
$iyziCoPaymentTestApiKey = $method['iyzico_testing_api_key'];
$iyziCoPaymentLiveApiKey = $method['iyzico_live_api_key'];
$iyziCoPaymentLiveApiSecret = $method['iyzico_live_secret_key'];
$iyziCoPaymentCurrency = $method['iyzico_crncy'];
$iyziCoPaymentBeta = isset($method['iyzico_beta']) ? $method['iyzico_beta'] : '0';

        /**
         * Konnect Network payment configuration
         */
        $konnectPaymentMode    = $method['konnect_payment_mode']   ?? '0';
        $konnectPaymentStatus  = $method['konnect_active_pasive']  ?? '0';
        $konnectTestApiKey     = $method['konnect_test_api_key']   ?? '';
        $konnectTestWalletId   = $method['konnect_test_wallet_id'] ?? '';
        $konnectLiveApiKey     = $method['konnect_live_api_key']   ?? '';
        $konnectLiveWalletId   = $method['konnect_live_wallet_id'] ?? '';
        $konnectWebhookSecret  = $method['konnect_webhook_secret'] ?? '';
        $konnectCurrency       = $method['konnect_currency']       ?? 'TND';
        $konnectPaymentBeta    = $method['konnect_beta']           ?? '0';

        /**
         * Razorpay payment configuration
         */
        $razorPayPaymentMode = $method['razorpay_payment_mode'];
        $razorPayPaymentStatus = $method['razorpay_active_pasive'];
$razorPayPaymentTestKeyID = $method['razorpay_testing_key_id'];
$razorPayPaymentTestSecretKey = $method['razorpay_testing_secret_key'];
$razorPayPaymentLiveKeyID = $method['razorpay_live_key_id'];
$razorPayPaymentLiveSecretKey = $method['razorpay_live_secret_key'];
$razorPayPaymentCurrency = $method['razorpay_crncy'];
$razorPayPaymentBeta = isset($method['razorpay_beta']) ? $method['razorpay_beta'] : '0';
        /**
         * Paystack payment configuration
         */
        $payStackPaymentMode = $method['paystack_payment_mode'];
        $payStackPaymentStatus = $method['paystack_active_pasive'];
$payStackPaymentTestSecretKey = $method['paystack_testing_secret_key'];
$payStackPaymentTestPublicKey = $method['paystack_testing_public_key'];
$payStackPaymentLiveSecretKey = $method['paystack_live_secret_key'];
$payStackPaymentLivePublicKey = $method['pay_stack_liive_public_key'];
$payStackPaymentCurrency = $method['paystack_crncy'];
$payStackPaymentBeta = isset($method['paystack_beta']) ? $method['paystack_beta'] : '0';

        /**
         * Flutterwave payment configuration
         */
        $flutterWavePaymentMode = isset($method['flutterwave_payment_mode']) ? $method['flutterwave_payment_mode'] : '0';
        $flutterWavePaymentStatus = isset($method['flutterwave_active_pasive']) ? $method['flutterwave_active_pasive'] : '0';
$flutterWaveTestPublicKey = isset($method['flutterwave_test_public_key']) ? $method['flutterwave_test_public_key'] : NULL;
$flutterWaveTestSecretKey = isset($method['flutterwave_test_secret_key']) ? $method['flutterwave_test_secret_key'] : NULL;
$flutterWaveLivePublicKey = isset($method['flutterwave_live_public_key']) ? $method['flutterwave_live_public_key'] : NULL;
$flutterWaveLiveSecretKey = isset($method['flutterwave_live_secret_key']) ? $method['flutterwave_live_secret_key'] : NULL;
$flutterWaveCurrency = isset($method['flutterwave_currency']) ? $method['flutterwave_currency'] : ($defaultCurrency ?? 'USD');
$flutterWaveSecretHash = isset($method['flutterwave_secret_hash']) ? $method['flutterwave_secret_hash'] : NULL;
$flutterWaveEncryptionKey = isset($method['flutterwave_encryption_key']) ? $method['flutterwave_encryption_key'] : NULL;
$flutterWavePaymentBeta = isset($method['flutterwave_beta']) ? $method['flutterwave_beta'] : '0';

        /**
         * CoinPayments configuration
         */
        $coinPaymentStatus = $method['coinpayments_status'];
        $coinPaymentBeta = isset($method['coinpayments_beta']) ? $method['coinpayments_beta'] : '0';
        $coinPaymentPrivateKey = isset($method['coinpayments_private_key']) ? $method['coinpayments_private_key'] : NULL;
        $coinPaymentPublicKey = isset($method['coinpayments_public_key']) ? $method['coinpayments_public_key'] : NULL;
        $coinPaymentMerchandID = isset($method['coinpayments_merchand_id']) ? $method['coinpayments_merchand_id'] : NULL;
        $coinPaymentIPNSecret = isset($method['coinpayments_ipn_secret']) ? $method['coinpayments_ipn_secret'] : NULL;
        $coinPaymentDebugEmail = isset($method['coinpayments_debug_email']) ? $method['coinpayments_debug_email'] : NULL;
        $coinPaymentCryptoCurrency = isset($method['cp_cryptocurrencies']) ? $method['cp_cryptocurrencies'] : NULL;
        $coinPaymentMode = isset($method['coinpayments_mode']) ? $method['coinpayments_mode'] : 'legacy';
        $coinPaymentClientId = isset($method['coinpayments_client_id']) ? $method['coinpayments_client_id'] : null;
        $coinPaymentClientSecret = isset($method['coinpayments_client_secret']) ? $method['coinpayments_client_secret'] : null;
        $coinPaymentWebhookSecret = isset($method['coinpayments_webhook_secret']) ? $method['coinpayments_webhook_secret'] : null;
        $coinPaymentApiBase = isset($method['coinpayments_api_base']) ? $method['coinpayments_api_base'] : null;

        /**
         * MercadoPago configuration
         */
        $mercadoPagoMode = $method['mercadopago_payment_mode'];
        $mercadoPagoPaymentStatus = $method['mercadopago_active_pasive'];
        $mercadoPagoTestAccessTokenID = $method['mercadopago_test_access_id'];
        $mercadoPagoLiveAccessTokenID = $method['mercadopago_live_access_id'];
        $mercadoPagoCurrency = $method['mercadopago_currency'];
        $mercadoPagoPaymentBeta = isset($method['mercadopago_beta']) ? $method['mercadopago_beta'] : '0';
        /**
         * Moneroo payment configuration
         */
        $monerooMode = $method['moneroo_payment_mode'] ?? '0';
        $monerooPaymentStatus = $method['moneroo_active_pasive'] ?? '0';
        $monerooTestProjectId = $method['moneroo_test_project_id'] ?? null;
        $monerooTestApiKey = $method['moneroo_test_api_key'] ?? null;
        $monerooLiveProjectId = $method['moneroo_live_project_id'] ?? null;
        $monerooLiveApiKey = $method['moneroo_live_api_key'] ?? null;
        $monerooWebhookSecret = $method['moneroo_webhook_secret'] ?? null;
        $monerooCurrency = $method['moneroo_currency'] ?? 'USD';
        $monerooPaymentBeta = isset($method['moneroo_beta']) ? $method['moneroo_beta'] : '0';
        /**
         * NowPayments payment configuration
         */
        $nowPaymentsMode = $method['nowpayments_payment_mode'] ?? '0';
        $nowPaymentsPaymentStatus = $method['nowpayments_active_pasive'] ?? '0';
        $nowPaymentsTestApiKey = $method['nowpayments_test_api_key'] ?? null;
        $nowPaymentsLiveApiKey = $method['nowpayments_live_api_key'] ?? null;
        $nowPaymentsIpnSecret = $method['nowpayments_ipn_secret'] ?? null;
        $nowPaymentsCurrency = $method['nowpayments_currency'] ?? ($defaultCurrency ?? 'USD');
        $nowPaymentsBeta = isset($method['nowpayments_beta']) ? $method['nowpayments_beta'] : '0';
        /**
         * Paysafecard payment configuration
         */
        $paysafecardMode = $method['paysafecard_mode'] ?? 'test';
        $paysafecardPaymentStatus = $method['paysafecard_status'] ?? '0';
        $paysafecardApiKey = $method['paysafecard_api_key'] ?? null;
        $paysafecardCurrency = $method['paysafecard_currency'] ?? ($defaultCurrency ?? 'USD');
        $paysafecardPaymentBeta = isset($method['paysafecard_beta']) ? $method['paysafecard_beta'] : '0';
        /**
         * Bank transfer configuration
         */
        $bankPaymentStatus = $method['bank_payment_status'];
        $bankPaymentBeta = isset($method['bank_beta']) ? $method['bank_beta'] : '0';
        $bankPaymentPercentageFee = $method['bank_payment_percentage_fee'];
        $bankPaymentFixedCharge = $method['bank_payment_fixed_charge'];
        $bankPaymentDetails = $method['bank_payment_details'];
        $payoutPayoneerStatus = isset($method['payout_payoneer_status']) ? $method['payout_payoneer_status'] : '0';
        $payoutZelleStatus = isset($method['payout_zelle_status']) ? $method['payout_zelle_status'] : '0';
        $payoutWesternUnionStatus = isset($method['payout_western_union_status']) ? $method['payout_western_union_status'] : '0';
        $payoutBitcoinStatus = isset($method['payout_bitcoin_status']) ? $method['payout_bitcoin_status'] : '0';
        $payoutMercadoPagoStatus = isset($method['payout_mercadopago_status']) ? $method['payout_mercadopago_status'] : '0';
        // Handle verification status alerts for logged-in user
        if ($verifData) {
            $verificationRequestStatus = $verifData['request_status'];
            $userReadStatus = $verifData['user_read_status'];

            if ($verificationRequestStatus == '0' && $userReadStatus != '1') {
                $verStatus = '<div class="i_postFormContainer"><div class="certification_terms">
                                <div class="certification_terms_item verirication_timing_bg"></div>
                                <div class="certification_terms_item">
                                    <div class="certificate_terms_item_item pendingTitle">' .
                                        $LANG['your_request_is_pending'] .
                                    '</div>
                                    <div class="certificate_terms_item_item">' .
                                        $LANG['you_will_notififed_when_it_is_processed'] .
                                    '</div>
                                </div>
                            </div></div>';
            } else if ($verificationRequestStatus == '1' && $userReadStatus != '1') {
                $verStatus = '<div class="i_postFormContainer"><div class="certification_terms">
                                <div class="certification_terms_item verification_approve_bg"></div>
                                <div class="certification_terms_item">
                                    <div class="certificate_terms_item_item pendingTitle">' .
                                        $LANG['congratulations_approved'] .
                                    '</div>
                                    <div class="certificate_terms_item_item">' .
                                        $LANG['congrat_approved_not'] .
                                    '</div>
                                </div>
                            </div></div>';
            } else if ($verificationRequestStatus == '2' && $userReadStatus != '1') {
                // Mark notification as read when rejected
                $iN->iN_UpdateVerificationAnswerReadStatus($userID);
                $verStatus = '<div class="i_postFormContainer"><div class="certification_terms">
                                <div class="certification_terms_item verification_reject_bg"></div>
                                <div class="certification_terms_item">
                                    <div class="certificate_terms_item_item pendingTitle">' .
                                        $LANG['sorry_rejected'] .
                                    '</div>
                                    <div class="certificate_terms_item_item">' .
                                        $LANG['sorry_you_are_rejected'] .
                                    '</div>
                                </div>
                            </div></div>';
            }
        }
} else {
        // If no valid user ID, clear cookie and redirect to login
        setcookie($cookieName, '', time() - 31556926, '/');
        unset($_COOKIE[$cookieName]);
        header("Location: index.php");
        exit();
    }
} else {
	    // Guest visitor (not logged in)
	    $logedIn = '0';
	    $userType = '0';
	    $certificationStatus = '0';
	    $validationStatus = '0';
	    $conditionStatus = '0';
	    $feesStatus = '0';
	    $payoutStatus = '0';
	    $userSignupIntent = 'user';

    // Auto-detect language if enabled
    if ($autoDetectLanguageStatus == '1') {
        include_once "getUserDetailsByipApi.php";
        $checkLangExist = $iN->iN_CheckLangKeyExist($registerCountryCode);

        // Set detected language if it exists in system
        if (strlen(trim($checkLangExist)) != 0) {
            $defaultLanguage = $registerCountryCode;
        }
    }

    // Load language pack for guests (subfolder-safe)
    $___lang_path = __resolve_lang_path(strtolower($defaultLanguage), $__LANG_DIR);
    include $___lang_path;

    // Clear any existing session cookie
    setcookie($cookieName, '', 1);
    setcookie($cookieName, '', time() - 31556926, '/');
}

// Viewer country code (used for country-based privacy filtering)
$viewerCountryCode = null;
$viewerCountryCandidate = '';
if (!empty($_SESSION['viewer_country_code'])) {
    $viewerCountryCandidate = $_SESSION['viewer_country_code'];
} elseif (isset($registerCountryCode) && $registerCountryCode !== null && $registerCountryCode !== '') {
    $viewerCountryCandidate = $registerCountryCode;
} elseif (isset($countryCode) && $countryCode !== null && $countryCode !== '') {
    $viewerCountryCandidate = $countryCode;
}
$viewerCountryCandidate = strtoupper(trim((string)$viewerCountryCandidate));
if ($viewerCountryCandidate !== '' && preg_match('/^[A-Z]{2}$/', $viewerCountryCandidate) && isset($COUNTRIES[$viewerCountryCandidate])) {
    $viewerCountryCode = $viewerCountryCandidate;
}

if ($viewerCountryCode === null) {
    if (!isset($registerCountryCode)) {
        include_once "getUserDetailsByipApi.php";
    }
    $viewerCountryCandidate = isset($registerCountryCode) ? strtoupper(trim((string)$registerCountryCode)) : '';
    if ($viewerCountryCandidate !== '' && preg_match('/^[A-Z]{2}$/', $viewerCountryCandidate) && isset($COUNTRIES[$viewerCountryCandidate])) {
        $viewerCountryCode = $viewerCountryCandidate;
    }
}

if ($viewerCountryCode !== null) {
    $_SESSION['viewer_country_code'] = $viewerCountryCode;
}
?>
