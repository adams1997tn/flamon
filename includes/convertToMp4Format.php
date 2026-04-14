<?php

/**
 * Converts a video file to MP4 format using FFmpeg (H.264 + AAC).
 *
 * @param string $ffmpegPath            Full path to the ffmpeg binary.
 * @param string $sourcePath            Full path to the input video file.
 * @param string $outputDir             Directory where the MP4 will be saved.
 * @param string $filenameWithoutExt    Name for the output file, without extension.
 * @return string|null                  Returns the output path on success, or null on failure.
 */
function convertToMp4Format(
    string $ffmpegPath,
    string $sourcePath,
    string $outputDir,
    string $filenameWithoutExt
): ?string {
    if (!function_exists('shell_exec')) {
        return null;
    }
    $outputPath = rtrim($outputDir, '/') . '/' . $filenameWithoutExt . '.mp4';

    $srcExt = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: '');
    $samePath = (realpath($sourcePath) && realpath($outputPath))
        ? (realpath($sourcePath) === realpath($outputPath))
        : ($sourcePath === $outputPath);
    // When input and output are the same path, write to a temp then rename.
    $targetPath = $samePath ? (rtrim($outputDir, '/') . '/' . $filenameWithoutExt . '__tmp__.mp4') : $outputPath;
    $ffmpegBin = $ffmpegPath ?: 'ffmpeg';
    $ffmpegCmd = escapeshellcmd($ffmpegBin);

    $remuxCandidates = ['mp4', 'm4v', 'mov'];
    $attempts = [];

    if (in_array($srcExt, $remuxCandidates, true)) {
        // Fast remux path (copy existing streams, move moov atom to the front)
        $attempts[] = [
            'type' => 'remux',
            'cmd'  => $ffmpegCmd
                . ' -hide_banner -loglevel error -y'
                . ' -i ' . escapeshellarg($sourcePath)
                . ' -c copy -movflags +faststart -avoid_negative_ts make_zero'
                . ' ' . escapeshellarg($targetPath) . ' 2>&1',
        ];
    }

    // Full transcode fallback (guarantee H.264 + AAC + streaming friendly GOP)
    $attempts[] = [
        'type' => 'transcode',
        'cmd'  => $ffmpegCmd
            . ' -hide_banner -loglevel error -y'
            . ' -i ' . escapeshellarg($sourcePath)
            . ' -c:v libx264 -preset medium -profile:v high -level 4.1'
            . ' -pix_fmt yuv420p -g 48 -keyint_min 48 -sc_threshold 0'
            . ' -b:v 4500k -maxrate 5000k -bufsize 10000k'
            . ' -movflags +faststart -vsync 1'
            . ' -c:a aac -b:a 160k -ar 48000'
            . ' ' . escapeshellarg($targetPath) . ' 2>&1',
    ];

    $lastOutput = '';
    foreach ($attempts as $attempt) {
        $lastOutput = (string)@shell_exec($attempt['cmd']);
        if (file_exists($targetPath) && filesize($targetPath) > 1024) {
            if ($samePath) {
                // Move temp file into place; source path already replaced by rename.
                @rename($targetPath, $outputPath);
                return $outputPath;
            }
            @unlink($sourcePath);
            return $targetPath;
        }
        if (file_exists($targetPath)) {
            @unlink($targetPath);
        }
    }

    if (!empty($lastOutput)) {
        error_log('convertToMp4Format failed for ' . $sourcePath . ' => ' . $outputPath . ' :: ' . substr($lastOutput, 0, 2000));
    }

    return null;
}
?>
