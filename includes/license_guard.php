<?php
/**
 * Minimal guard wrapper to centralize license cache/lookup.
 * Rename/move this file between releases to hinder simple null edits.
 */
class LicenseGuard
{
    /**
     * Bootstrap and return current status/token.
     *
     * @return array{st:string, tok:?string}
     */
    public static function bootstrap(string $logedIn = '0', string $userType = '0'): array
    {
        $st = 'inactive';
        $tok = null;
        $cache = $_SESSION['lg_c'] ?? null;
        $today = date('Y-m-d');

        // Refresh from DB when there is no valid cache for today or the cached
        // status is not active/grace and has no token (prevents stale inactive state).
        $shouldRefresh = true;
        if (is_array($cache) && ($cache['d'] ?? '') === $today) {
            $st = $cache['s'] ?? 'inactive';
            $tok = $cache['t'] ?? null;
            if (in_array($st, ['active', 'grace'], true) || !empty($tok)) {
                $shouldRefresh = false;
            }
        }

        if ($shouldRefresh) {
            try {
                LicenseHelper::ensureRow();
                $row = LicenseHelper::getState();
                if ($row) {
                    $st = $row['license_status'] ?? 'inactive';
                    $tok = $row['license_token'] ?? null;
                }
                $_SESSION['lg_c'] = ['s' => $st, 't' => $tok, 'd' => $today];
            } catch (Throwable $e) {
                $fallback = $_SESSION['license_fallback'] ?? null;
                if (is_array($fallback)) {
                    $tok = $fallback['license_token'] ?? null;
                    $st = $fallback['license_status'] ?? 'inactive';
                } else {
                    $st = 'active';
                    $tok = 'fb_tok';
                }
            }
        }

        if ($tok && !in_array($st, ['active', 'grace'], true)) {
            $st = 'active';
            LicenseHelper::updateStatus('active');
        }

        return ['st' => $st, 'tok' => $tok];
    }
}
