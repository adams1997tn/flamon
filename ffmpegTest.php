<?php include_once "includes/inc.php";?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>FFMPEG Tester</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Ubuntu+Condensed|Ubuntu|PT+Sans+Narrow|Open+Sans:600,700&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?php echo iN_HelpSecure($base_url);?>src/ffmpeg-tester.css">
</head>
<body>
    <div class="fftester-container">
        <h1 class="fftester-title">FFMPEG Tester</h1>
        <hr class="fftester-hr">
        <div class="fftester-image-center">
            <img class="fftester-logo" src="//upload.wikimedia.org/wikipedia/commons/thumb/5/5f/FFmpeg_Logo_new.svg/1280px-FFmpeg_Logo_new.svg.png" alt="FFMPEG Logo">
        </div>
        <hr class="fftester-hr">

        <h3>This script has the function of testing if you have FFMPEG!</h3>
        <h3>Includes PHP version check for compatibility.</h3>
        <h3>If compatible, you can proceed with video conversion features.</h3>

        <?php
        $error = 0;

        function getDataFromUrl($url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15
            ]);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }

        echo "<h2 class='fftester-subtitle'>Basic Requirements</h2>";

        if (phpversion() < 5.5) {
            echo "<div class='fftester-msg fftester-warning'>Error: PHP version is below 5.5 (Current: " . phpversion() . ")</div>";
            echo "<div class='fftester-msg fftester-note'>To ensure security, update your PHP version.</div>";
            $error++;
        } else {
            echo "<div class='fftester-msg fftester-success'>PHP version is OK: " . phpversion() . "</div>";
        }

        echo "<h2 class='fftester-subtitle'>FFMPEG Requirements</h2>";
        echo "<h4 class='fftester-note'>These features are necessary for video conversion, thumbnail generation, etc.</h4>";

        if (function_exists('exec')) {
            echo "<div class='fftester-msg fftester-success'>exec() is Enabled</div>";
        } else {
            echo "<div class='fftester-msg fftester-warning'>exec() is Disabled. This is required by FFMPEG.</div>";
            $error++;
        }

        if (function_exists('shell_exec')) {
            echo "<div class='fftester-msg fftester-success'>shell_exec() is Enabled</div>";
        } else {
            echo "<div class='fftester-msg fftester-warning'>shell_exec() is Disabled. This is required by FFMPEG.</div>";
            $error++;
        }

        if (function_exists('shell_exec')) {
            $isWindows = stripos(PHP_OS_FAMILY ?? PHP_OS, 'Windows') !== false;

            // --- Sanitize admin-configured paths (PHP 8.1+ safe) ---
            $configuredFfmpeg  = isset($ffmpegPath)  ? trim((string)$ffmpegPath)  : '';
            $configuredFfprobe = isset($ffprobePath)  ? trim((string)$ffprobePath) : '';

            // Show configured path for debugging
            if ($configuredFfmpeg !== '') {
                echo "<div class='fftester-msg fftester-info'>Configured ffmpeg_path (Admin &gt; Settings): <strong>" . htmlspecialchars($configuredFfmpeg) . "</strong></div>";
                // Warn if it looks like a macOS/Linux path on Windows
                if ($isWindows && (strpos($configuredFfmpeg, '/opt/') === 0 || strpos($configuredFfmpeg, '/usr/') === 0)) {
                    echo "<div class='fftester-msg fftester-warning'>&#9888; The configured path looks like a macOS/Linux path. On Windows, use something like: <code>C:\\ffmpeg\\bin\\ffmpeg.exe</code></div>";
                }
            }

            // --- Step 1: Try the admin-configured path first ---
            $ffmpeg  = '';
            $ffprobe = '';

            if ($configuredFfmpeg !== '' && is_file($configuredFfmpeg)) {
                $ffmpeg = $configuredFfmpeg;
            }
            if ($configuredFfprobe !== '' && is_file($configuredFfprobe)) {
                $ffprobe = $configuredFfprobe;
            }

            // --- Step 2: If configured path is a directory, look for binaries inside it ---
            if ($ffmpeg === '' && $configuredFfmpeg !== '' && is_dir($configuredFfmpeg)) {
                $suffix = $isWindows ? '.exe' : '';
                $candidate = rtrim($configuredFfmpeg, '/\\') . DIRECTORY_SEPARATOR . 'ffmpeg' . $suffix;
                if (is_file($candidate)) { $ffmpeg = $candidate; }
            }

            // --- Step 3: OS-native PATH lookup ---
            if ($ffmpeg === '') {
                if ($isWindows) {
                    $ffmpeg = trim((string)@shell_exec('where ffmpeg 2>NUL'));
                } else {
                    $ffmpeg = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null || which ffmpeg 2>/dev/null'));
                }
                // 'where' on Windows can return multiple lines; take the first
                if ($ffmpeg !== '' && strpos($ffmpeg, "\n") !== false) {
                    $ffmpeg = trim(strtok($ffmpeg, "\n"));
                }
            }
            if ($ffprobe === '') {
                if ($isWindows) {
                    $ffprobe = trim((string)@shell_exec('where ffprobe 2>NUL'));
                } else {
                    $ffprobe = trim((string)@shell_exec('command -v ffprobe 2>/dev/null || which ffprobe 2>/dev/null'));
                }
                if ($ffprobe !== '' && strpos($ffprobe, "\n") !== false) {
                    $ffprobe = trim(strtok($ffprobe, "\n"));
                }
            }

            // --- Step 4: Derive ffprobe from ffmpeg path if still missing ---
            if ($ffprobe === '' && $ffmpeg !== '') {
                if ($isWindows) {
                    $derivedProbe = str_replace('ffmpeg.exe', 'ffprobe.exe', $ffmpeg);
                } else {
                    $derivedProbe = preg_replace('/ffmpeg$/', 'ffprobe', $ffmpeg);
                }
                if ($derivedProbe !== $ffmpeg && is_file($derivedProbe)) {
                    $ffprobe = $derivedProbe;
                }
            }

            // --- Display results: FFMPEG ---
            if ($ffmpeg === '') {
                echo "<div class='fftester-msg fftester-warning'>FFMPEG not found in PATH or configured path.</div>";
                if ($isWindows) {
                    echo "<div class='fftester-msg fftester-note'>Install FFmpeg to <code>C:\\ffmpeg\\bin\\</code> and add <code>C:\\ffmpeg\\bin</code> to your system PATH, then restart Laragon.</div>";
                }
                $error++;
            } else {
                echo "<div class='fftester-msg fftester-success'>FFMPEG found at: <strong>" . htmlspecialchars($ffmpeg) . "</strong></div>";
                $versionOutput = [];
                @exec(escapeshellarg($ffmpeg) . ' -version', $versionOutput);
                if (!empty($versionOutput)) {
                    echo "<pre class='fftester-pre'>" . htmlspecialchars(implode("\n", $versionOutput)) . "</pre>";
                }
            }

            // --- Display results: FFPROBE ---
            echo "<h2 class='fftester-subtitle'>FFPROBE</h2>";
            echo "<h4 class='fftester-note'>FFprobe is required for reading video metadata (duration, codecs, etc.).</h4>";
            if ($ffprobe === '') {
                echo "<div class='fftester-msg fftester-warning'>FFPROBE not found in PATH or configured path.</div>";
                echo "<div class='fftester-msg fftester-note'>FFprobe ships with FFmpeg. If you installed FFmpeg, ffprobe should be in the same <code>bin</code> folder.</div>";
                $error++;
            } else {
                echo "<div class='fftester-msg fftester-success'>FFPROBE found at: <strong>" . htmlspecialchars($ffprobe) . "</strong></div>";
                $probeOutput = [];
                @exec(escapeshellarg($ffprobe) . ' -version', $probeOutput);
                if (!empty($probeOutput)) {
                    echo "<pre class='fftester-pre'>" . htmlspecialchars(implode("\n", $probeOutput)) . "</pre>";
                }
            }
        }

        echo "<h2 class='fftester-subtitle'>Test Completed</h2>";

        if ($error > 0) {
            echo "<div class='fftester-msg fftester-warning'>Found $error issues. Please resolve them before continuing.</div>";
        } else {
            echo "<div class='fftester-msg fftester-success'>All Requirements Met!</div>";
            echo "<div class='fftester-msg fftester-info'>Your system is fully compatible with FFMPEG.</div>";
        }
        ?>
    </div>
</body>
</html>
