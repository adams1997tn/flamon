<?php

/**
 * Generates a thumbnail image (JPEG) from a video file using FFmpeg.
 * The thumbnail is saved as a .jpg in the same directory as the video.
 *
 * Note: Many upload flows in the app expect a .jpg thumbnail. Previously this
 * helper produced .png files which caused mismatches (e.g. stories showing
 * web.png as poster). Switching to .jpg aligns with those flows.
 *
 * @param string $ffmpegPath  Full path/binary name of the ffmpeg executable.
 * @param string $videoPath   Full path to the input video file.
 * @return string|null        Returns the thumbnail path on success, or null on failure.
 */
function createVideoThumbnailInSameDir(string $ffmpegPath, string $videoPath): ?string
{
    if (!is_file($videoPath)) {
        return null;
    }
    if (!function_exists('shell_exec')) {
        return null;
    }

    // Validate ffmpeg binary — fall back to PATH if configured path is missing
    $ffmpegBin = trim($ffmpegPath ?? '');
    if ($ffmpegBin !== '' && preg_match('~[\\\\/]~', $ffmpegBin) && !is_file($ffmpegBin)) {
        // Configured path has slashes but doesn't exist on disk — try PATH
        $ffmpegBin = '';
    }
    if ($ffmpegBin === '') {
        $isWindows = stripos(PHP_OS_FAMILY ?? PHP_OS, 'Windows') !== false;
        if ($isWindows) {
            $found = trim((string)@shell_exec('where.exe ffmpeg 2>NUL'));
            if ($found !== '' && strpos($found, "\n") !== false) {
                $found = trim(strtok($found, "\n"));
            }
        } else {
            $found = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null || which ffmpeg 2>/dev/null'));
        }
        $ffmpegBin = $found !== '' ? $found : 'ffmpeg';
    }

    $directory = dirname($videoPath);
    $filenameWithoutExt = pathinfo($videoPath, PATHINFO_FILENAME);
    $thumbnailPath = $directory . '/' . $filenameWithoutExt . '.jpg';

    // Take a frame at ~3s; resize to sensible width for faster delivery (-q:v 2 keeps quality)
    $ffmpegCmd = escapeshellcmd($ffmpegBin);
    $cmd = $ffmpegCmd
        . ' -hide_banner -loglevel error -y'
        . ' -ss 00:00:03.000 -i ' . escapeshellarg($videoPath)
        . ' -frames:v 1 -q:v 2 -vf "scale=640:-2:force_original_aspect_ratio=decrease" '
        . escapeshellarg($thumbnailPath) . ' 2>&1';

    $output = (string)@shell_exec($cmd);

    if (is_file($thumbnailPath) && filesize($thumbnailPath) > 1000) {
        return $thumbnailPath;
    }

    // Log failure for debugging
    error_log('createVideoThumbnailInSameDir FAILED: cmd=' . $cmd . ' | output=' . substr($output, 0, 500));

    return null;
}

?>
