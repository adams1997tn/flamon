<?php

/**
 * Converts an audio or video file to MP3 format using FFmpeg.
 *
 * @param string $ffmpegPath            Full path to the ffmpeg binary.
 * @param string $sourcePath            Full path to the input file.
 * @param string $outputDir             Directory where the MP3 will be saved.
 * @param string $filenameWithoutExt    Name for the output file, without extension.
 * @return string|null                  Returns the output path on success, or null on failure.
 */
function convertToMp3Format(
    string $ffmpegPath,
    string $sourcePath,
    string $outputDir,
    string $filenameWithoutExt
): ?string {
    $outputPath = rtrim($outputDir, '/') . '/' . $filenameWithoutExt . '.mp3';

    $samePath = (realpath($sourcePath) && realpath($outputPath))
        ? (realpath($sourcePath) === realpath($outputPath))
        : ($sourcePath === $outputPath);
    $targetPath = $samePath ? (rtrim($outputDir, '/') . '/' . $filenameWithoutExt . '__tmp__.mp3') : $outputPath;

    $ffmpegBin = $ffmpegPath ?: 'ffmpeg';
    $ffmpegCmd = escapeshellcmd($ffmpegBin);

    $cmd = $ffmpegCmd
        . ' -hide_banner -loglevel error -y'
        . ' -i ' . escapeshellarg($sourcePath)
        . ' -vn -c:a libmp3lame -b:a 192k -ar 44100 -ac 2'
        . ' ' . escapeshellarg($targetPath) . ' 2>&1';

    $output = (string)@shell_exec($cmd);
    if (file_exists($targetPath) && filesize($targetPath) > 1024) {
        if ($samePath) {
            @rename($targetPath, $outputPath);
            return $outputPath;
        }
        return $targetPath;
    }

    if (file_exists($targetPath)) {
        @unlink($targetPath);
    }
    if (!empty($output)) {
        error_log('convertToMp3Format failed for ' . $sourcePath . ' => ' . $outputPath . ' :: ' . substr($output, 0, 2000));
    }

    return null;
}
?>
