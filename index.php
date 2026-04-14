<title>index.php</title>

<?php
if (!function_exists('dizzy_static_file_passthrough')) {
    /**
     * Serve non-PHP static files directly when requests are rewritten to index.php.
     */
    function dizzy_static_file_passthrough(string $requestedPath): void
    {
        $requestedRelativeFile = ltrim(urldecode($requestedPath), '/');
        if ($requestedRelativeFile === '') {
            return;
        }

        $appRootPath = realpath(__DIR__);
        if ($appRootPath === false) {
            return;
        }

        $candidatePath = realpath($appRootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $requestedRelativeFile));
        $isInsideApp = $candidatePath !== false && strpos((string)$candidatePath, $appRootPath . DIRECTORY_SEPARATOR) === 0;
        $isAllowedFile = $isInsideApp && is_file($candidatePath) && !preg_match('/\.php$/i', (string)$candidatePath);
        if (!$isAllowedFile) {
            return;
        }

        $extension = strtolower((string)pathinfo((string)$candidatePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'map' => 'application/json; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'xml' => 'application/xml; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
        ];
        $contentType = $mimeMap[$extension] ?? (function_exists('mime_content_type') ? mime_content_type((string)$candidatePath) : 'application/octet-stream');
        if ($contentType) {
            header('Content-Type: ' . $contentType);
        }

        header('Content-Length: ' . (string)filesize((string)$candidatePath));
        readfile((string)$candidatePath);
        exit();
    }
}

// Pre-bootstrap static file passthrough.
// Ensures CSS/JS/assets are served directly even if all requests are rewritten to index.php.
$__rawRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$__pathOnly = parse_url($__rawRequestUri, PHP_URL_PATH) ?? '/';
$__scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$__scriptDirNorm = '/' . trim((string)$__scriptDir, '/');
if ($__scriptDirNorm === '//') {
    $__scriptDirNorm = '/';
}
$__relativePath = $__pathOnly;
if ($__scriptDirNorm !== '/' && strpos((string)$__pathOnly, $__scriptDirNorm) === 0) {
    $__relativePath = substr((string)$__pathOnly, strlen($__scriptDirNorm));
    if ($__relativePath === false || $__relativePath === '') {
        $__relativePath = '/';
    } elseif ($__relativePath[0] !== '/') {
        $__relativePath = '/' . $__relativePath;
    }
}
dizzy_static_file_passthrough((string)$__relativePath);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Load required core files and application configuration
include_once "includes/inc.php";

// Parse the current request URI and make it BASE-URL relative
$rawRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$pathOnly      = parse_url($rawRequestUri, PHP_URL_PATH) ?? '/';
$queryOnly     = parse_url($rawRequestUri, PHP_URL_QUERY);

// Normalize paths to ensure consistent leading/trailing slashes
$pathOnlyNorm = '/' . ltrim($pathOnly, '/');
$basePath     = parse_url($base_url, PHP_URL_PATH) ?? '/';
$basePathNorm = '/' . trim($basePath, '/');

// Remove the app base path (e.g., /dizzyv5.3) so routing works in subfolders
if ($basePathNorm !== '/' && strpos($pathOnlyNorm, $basePathNorm) === 0) {
    $relativePath = substr($pathOnlyNorm, strlen($basePathNorm));
    if ($relativePath === '') { $relativePath = '/'; }
    elseif ($relativePath[0] !== '/') { $relativePath = '/' . $relativePath; }
} else {
    $relativePath = $pathOnlyNorm;
}

// Normalize to support /index.php/... form (common on Nginx without rewrites)
$normalizedPath = preg_replace('~^/index\.php(?=/|$)~', '', $relativePath);
$normalizedUri  = $normalizedPath . ($queryOnly ? ('?' . $queryOnly) : '');

$requestUrl  = explode('/', $normalizedUri);
$activePage  = end($requestUrl);
$requestUri  = $normalizedUri;

// Initialize path and parameters
$paramsOffset  = strpos($requestUri, '?');
$requestPath   = $page = '';
$requestParams = [];

// Capture GET parameters if present
if ($paramsOffset > -1) {
    $requestPath = substr($requestUri, 0, $paramsOffset);
    $params      = explode('&', substr($requestUri, $paramsOffset + 1));

    foreach ($params as $value) {
        $keyValue = explode('=', $value);
        $requestParams[$keyValue[0]] = isset($keyValue[1]) ? $keyValue[1] : '';
    }
} else {
    $requestPath = $requestUri;
}

// Static file passthrough fallback.
// If rewrite rules route asset requests to index.php, serve allowed files directly.
dizzy_static_file_passthrough((string)$requestPath);

// Demo/preview landing plugin override via query parameter.
// Example: /?l_theme=mosaic
$requestedLandingTheme = isset($_GET['l_theme']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['l_theme']) : '';
$requestPathKey = strtolower(trim((string)$requestPath, '/'));
$isHomeLikePath = ($requestPathKey === '' || $requestPathKey === 'index' || $requestPathKey === 'index.php');
$isGuestUser = ((string)($logedIn ?? '0') !== '1');
if ($isHomeLikePath && $isGuestUser && $requestedLandingTheme !== '') {
    $themeCandidates = array_values(array_unique([$currentTheme, 'default']));
    foreach ($themeCandidates as $themeCandidate) {
        $previewLandingFile = "themes/$themeCandidate/landing_plugins/$requestedLandingTheme/landing.php";
        if (is_file($previewLandingFile)) {
            include $previewLandingFile;
            exit();
        }
    }
}

// Allow activation callback to run even when index.php is in the URL.
if ($requestPath === '/requests/license-callback.php' || $requestPath === '/requests/license-callback') {
    include 'requests/license-callback.php';
    exit();
}

// Sitemap route
if ($requestPath === '/sitemap.xml' || $requestPath === '/sitemap.xml/') {
    include 'sources/sitemap.php';
    exit();
}

// Maintenance mode applies sitewide except for admins and moderators.
$isMaintenanceBypassUser = in_array((string)($userType ?? '0'), ['2', '3'], true);
if ($maintenanceMode == '1' && !$isMaintenanceBypassUser) {
    include 'sources/maintenance.php';
    exit();
}

// Update user activity if logged in
if ($logedIn == '1') {
    $updateLastSeen = $iN->iN_UpdateLastSeen($userID);
}

// License enforcement (grace allowed; token presence allows)
// Only enforce for authenticated sessions.
$licenseOperational = in_array(($lc_st ?? ''), ['active', 'grace'], true) || !empty($lc_tok);
$shouldEnforceLicense = ($logedIn === '1');
if ($shouldEnforceLicense && !$licenseOperational) {
    $isLicensePage = false;
    if (preg_match('~/(admin)/(license_activation|license_activation.php|license)~u', $normalizedUri)) {
        $isLicensePage = true;
    } elseif (preg_match('~/(license|license.php)$~u', $normalizedUri)) {
        $isLicensePage = true;
    }
    if (!$isLicensePage) {
        include 'sources/license.php';
        exit();
    }
}

// Force email verification if not verified
if (preg_match('~/([\w.-]+)~u', urldecode($requestUri), $match)) {
    $tag      = $match[1];
    $thePage  = trim($match[1]);

    if ($userEmailVerificationStatus == 'no' && $thePage != 'verify' && !empty($smtpEmail)) {
        if ($userType != '2' && $emailSendStatus == '1') {
            include 'sources/verifyme.php';
            exit();
        }
    }
}

// Force age verification (sitewide) after email verification gate
if ($logedIn === '1' && $ageVerificationStatus === '1' && $ageVerificationForceSitewide === '1') {
    $isAdminUser = in_array((string)$userType, ['2', '3'], true);
    $isAgeVerified = isset($userAgeVerifyStatus) && (string)$userAgeVerifyStatus === '1';
    if (!$isAdminUser && !$isAgeVerified) {
        $pathKey = strtolower(trim($requestPath, '/'));
        $ageVerifExempt = [
            'logout',
            'logout.php',
            'contact',
            'contact.php',
            'support',
            'support.php',
            'age-verification',
            'age-verification.php',
            'age-verification-callback',
            'age-verification-callback.php'
        ];
        if (!in_array($pathKey, $ageVerifExempt, true)) {
            header('Location: ' . route_url('age-verification'));
            exit();
        }
    }
}

// Special base64-decoded page
if (preg_match('~/([\w.-]+)~u', urldecode($requestUri), $match)) {
    $tag     = $match[1];
    $thePage = trim($match[1]);
    if ($thePage == base64_decode('YmVsZWdhbA==')) {
        include('sources/' . $thePage . '.php');
        exit();
    }
}

$reelPath = strtok($requestUri, '?');

if (preg_match('~^/reels(?:/(\d+))?/?$~', urldecode($reelPath), $match)) {
    // Feature gate for Reels page
    if (isset($reelsFeatureStatus) && (string)$reelsFeatureStatus !== '1') {
        header('Location: ' . route_url(''));
        exit();
    }
    $reelID = isset($match[1]) ? (int)$match[1] : null;
    include 'sources/reel_view.php';
    exit();
}

// Special sharer route
if (preg_match('~/([\w.-]+)~u', urldecode($requestUri), $match)) {
    $tag     = $match[1];
    $thePage = trim($match[1]);
    if ($thePage == 'sharer') {
        include('sources/sharer.php');
        exit();
    }
}

// Share preview route
if (preg_match('~^/share/([\w-]+)~u', urldecode($requestUri), $match)) {
    $shareToken = trim($match[1]);
    include 'sources/share.php';
    exit();
}

// OBS overlay routes
if (preg_match('~^/obs/overlay/([\w-]+)/?$~u', urldecode($requestPath), $match)) {
    $overlayToken = trim($match[1]);
    include 'obs_overlay.php';
    exit();
}
if (preg_match('~^/obs/api/([\w-]+)/([\w-]+)/?$~u', urldecode($requestPath), $match)) {
    $overlayToken = trim($match[1]);
    $overlayAction = trim($match[2]);
    include 'obs_api.php';
    exit();
}

// Blog routes
if ($requestPath === '/blog' || $requestPath === '/blog/') {
    if (isset($blogFeatureStatus) && (string)$blogFeatureStatus !== '1') {
        header('Location: ' . route_url(''));
        exit();
    }
    include 'sources/blog.php';
    exit();
}
if (preg_match('~^/blog/([\w.-]+)~u', urldecode($requestUri), $match)) {
    if (isset($blogFeatureStatus) && (string)$blogFeatureStatus !== '1') {
        header('Location: ' . route_url(''));
        exit();
    }
    $blogSlug = trim($match[1]);
    include 'sources/blog_post.php';
    exit();
}

// Community routes
if ($requestPath === '/communities' || $requestPath === '/communities/') {
    include 'sources/communities.php';
    exit();
}
if (preg_match('~^/community/([\w.-]+)/?$~u', urldecode($requestUri), $match)) {
    $communitySlug = trim($match[1]);
    include 'sources/community.php';
    exit();
}

// Agency profile route (strict numeric ID, optional slug prefix)
if (preg_match('~^/agency/(?:[\\w.-]+-)?(\\d+)/?$~u', urldecode($requestUri), $match)) {
    if (!isset($agencyModuleStatus) || $agencyModuleStatus !== 'yes') {
        header('Location: ' . route_url('404'));
        exit();
    }
    $agencyId = isset($match[1]) ? (int)$match[1] : 0;
    include 'sources/agency.php';
    exit();
}

// Admin panel route
if (preg_match('~/(admin)/([\w.-]+)~u', urldecode($requestUri), $match)) {
    if ($userType == '1') {
        header('Location: ' . route_url(''));
        exit();
    } else {
        $tag      = $match[1];
        $pageFor  = trim($match[2]);
        include 'admin/' . $adminTheme . '/index.php';
    }

// Routes with slugs (posts, products, etc.)
} else if (preg_match('~/(photos|videos|albums|post|product)/([\w.-]+)~u', urldecode($requestUri), $match)) {
    $urlMatch   = trim($match[1]);
    $slugyUrl   = trim($match[2]);
    $checkUser  = $iN->iN_CheckUserName($urlMatch);

    if ($urlMatch == 'post') {
        include 'sources/post.php';
    } else if ($urlMatch == 'product') {
        include 'sources/product.php';
    }

// Hashtag, explore, live, creator, etc.
} else if (preg_match('~/(hashtag|explore|creator|purchase|live)/([\w.-_]+)~u', urldecode($requestUri), $match)) {
    $tag           = $match[1];
    $urlMatch      = trim($match[1]);
    $pageFor       = $iN->iN_Secure($iN->url_Hash($match[2]));
    $pageForPage   = trim($match[2]);
    $hst           = null;

    if ($urlMatch != 'live') {
        $hst = $iN->iN_GetHashTagsSearch($pageFor, null, $showingNumberOfPost);
    }

    if ($pageForPage == 'becomeCreator') {
        include 'sources/becomeCreator.php';
    } else if ($pageForPage == 'purchase_point') {
        include 'sources/purchase_point.php';
    } else if ($hst) {
        include 'sources/hashtag.php';
    } else {
        $pageFor = preg_replace('/[ ,]+/', '_', trim($pageFor));
        $checkUsername = $iN->iN_CheckUserName($pageFor);

        if ($checkUsername) {
            $getUserID    = $iN->iN_GetUserDetailsFromUsername($pageFor);
            $lUserID      = $getUserID['iuid'];
            $liveDetails  = $iN->iN_GetLiveStreamingDetails($lUserID);

            if ($liveDetails) {
                include 'sources/live.php';
            } else {
                header('Location: ' . route_url('404'));
            }
        } else {
            header('Location: ' . route_url('404'));
        }
    }

// Static routes & fallback
} else if (preg_match('~/([\w.-]+)~u', $requestUri, $match)) {
    $urlMatch     = trim($match[1]);
    $pageGet      = $_GET['tab']      ?? '';
    $pageCategory = $_GET['cat']      ?? '';
    $pageCreator  = $_GET['creator']  ?? '';
    $checkUsername = $iN->iN_CheckUserName($urlMatch);

    if ($pageGet) {
        include 'sources/settings.php';
    } else if ($pageCreator) {
        include 'sources/creators.php';
    } else if ($checkUsername) {
        include 'sources/profile.php';
    } else if ($pageCategory) {
        include 'sources/marketplace.php';
    } else {
        switch ($match[1]) {
            case 'index':
            case 'index.php':
                include 'sources/home.php';
                break;
            case 'settings':
                include 'sources/settings.php';
                break;
            case 'chat':
            case 'chat.php':
                include 'sources/chat.php';
                break;
            case 'notifications':
                include 'sources/notifications.php';
                break;
            case 'payment-success':
            case 'payment-success.php':
                include 'sources/payment-success.php';
                break;
            case 'invoice':
            case 'invoice.php':
                include 'sources/invoice.php';
                break;
            case 'payment-failed':
            case 'payment-failed.php':
                include 'sources/payment-failed.php';
                break;
            case 'payment-response':
            case 'payment-response.php':
                include 'sources/payment-response.php';
                break;
            case 'creators':
            case 'creators.php':
                include 'sources/creators.php';
                break;
            case 'agencies':
            case 'agencies.php':
                include 'sources/agencies.php';
                break;
            case 'marketplace':
            case 'marketplace.php':
                include 'sources/marketplace.php';
                break;
            case 'saved':
            case 'saved.php':
                include 'sources/saved.php';
                break;
            case 'googleLogin':
            case 'googleLogin.php':
                include 'sources/googleLogin.php';
                break;
            case 'twitterLogin':
            case 'twitterLogin.php':
                include 'sources/twitterLogin.php';
                break;
            case 'register':
            case 'register.php':
                include 'sources/register.php';
                break;
            case 'reset_password':
            case 'reset_password.php':
                include 'sources/reset_password.php';
                break;
            case 'live_streams':
            case 'live_streams.php':
                include 'sources/live_streams.php';
                break;
            case 'verify':
            case 'verify.php':
                include 'sources/verify.php';
                break;
            case 'age-verification':
            case 'age-verification.php':
                include 'sources/age_verification.php';
                break;
            case 'age-verification-callback':
            case 'age-verification-callback.php':
                include 'sources/age_verification_callback.php';
                break;
            case 'createStory':
            case 'createStory.php':
                include 'sources/createStory.php';
                break;
            case 'createReels':
            case 'createReels.php':
                include 'sources/createReels.php';
                break;
            case 'friends_stories':
            case 'friends_stories.php':
                include 'sources/friends_stories.php';
                break;
            default:
                include 'sources/page.php';
        }
    }
} else if ($requestPath == '/' || $requestPath === '') {
    include "sources/home.php";
    exit();
} else {
    header('HTTP/1.0 404 Not Found');
    echo "<h1>" . iN_HelpSecure($LANG['page-not-found']) . "</h1>";
    echo iN_HelpSecure($LANG['sorry-this-page-not-available']);
}
?>
