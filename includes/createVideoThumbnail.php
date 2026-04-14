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

    $directory = dirname($videoPath);
    $filenameWithoutExt = pathinfo($videoPath, PATHINFO_FILENAME);
    $thumbnailPath = $directory . '/' . $filenameWithoutExt . '.jpg';

    // Take a frame at ~3s; resize to sensible width for faster delivery (-q:v 2 keeps quality)
    $ffmpegCmd = escapeshellcmd($ffmpegPath ?: 'ffmpeg');
    $cmd = $ffmpegCmd
        . ' -hide_banner -loglevel error -y'
        . ' -ss 00:00:03.000 -i ' . escapeshellarg($videoPath)
        . ' -frames:v 1 -q:v 2 -vf "scale=640:-2:force_original_aspect_ratio=decrease" '
        . escapeshellarg($thumbnailPath) . ' 2>&1';

    @shell_exec($cmd);

    if (is_file($thumbnailPath) && filesize($thumbnailPath) > 1000) {
        return $thumbnailPath;
    }

    return null;
}

?>
