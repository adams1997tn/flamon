<?php

/**
 * Converts a given video file to a blurred vertical Reels format (9:16) using FFmpeg.
 *
 * @param string $inputPath  Full path to the source video.
 * @param string $outputDir  Directory where the processed video will be saved.
 * @return string|null       Returns the output path on success, or null on failure.
 */
function convertVideoToBlurredReelsFormat(string $ffmpeg, string $inputPath, string $outputDir): ?string
{
    if (!file_exists($inputPath) || !is_readable($inputPath)) {
        return null;
    }

    if (!file_exists($outputDir) && !mkdir($outputDir, 0755, true)) {
        return null;
    }

    $hash = md5($inputPath . microtime());
    $outputPath = rtrim($outputDir, '/') . '/' . $hash . '_reels_blur.mp4';

    $escapedInput = escapeshellarg($inputPath);
    $escapedOutput = escapeshellarg($outputPath);

    // Validate ffmpeg binary — fall back to PATH if configured path is missing
    $ffmpegBin = trim($ffmpeg ?? '');
    if ($ffmpegBin !== '' && preg_match('~[\\\\/]~', $ffmpegBin) && !is_file($ffmpegBin)) {
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
    $ffmpegCmd = escapeshellcmd($ffmpegBin);

    $cmd = "{$ffmpegCmd} -hide_banner -loglevel error -y -i {$escapedInput} "
         . "-filter_complex \"[0:v]scale=1080:-1[fg];"
         . "[0:v]scale=1080:1920:force_original_aspect_ratio=increase,"
         . "crop=1080:1920,boxblur=10[bg];"
         . "[bg][fg]overlay=(W-w)/2:(H-h)/2\" "
         . "-c:v libx264 -preset medium -profile:v high -level 4.1 "
         . "-pix_fmt yuv420p -g 48 -keyint_min 48 -sc_threshold 0 "
         . "-movflags +faststart "
         . "-c:a aac -b:a 160k -ar 48000 "
         . "{$escapedOutput} 2>&1";

    shell_exec($cmd);

    if (file_exists($outputPath) && filesize($outputPath) > 100000) {
        return $outputPath;
    }

    return null;
}
?>
