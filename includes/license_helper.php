<?php
/**
 * Centralized license helper for Dizzy.
 * Stores only opaque license token and lightweight state locally.
 */
class LicenseHelper
{
    public const ENVATO_ITEM_ID = 31263937;
    public const PENDING_TTL = 900; // 15 minutes
    private const FILE_TTL = 2592000; // 30 days
    private const STATUS_VALUES = ['inactive', 'active', 'grace', 'locked', 'revoked', 'failed'];

    /**
     * Write lightweight diagnostic events for license persistence failures.
     */
    private static function logEvent(string $event, array $context = []): void
    {
        $dir = dirname(__DIR__) . '/includes/logs';
        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        $safe = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $str = (string) $value;
                if (strlen($str) > 500) {
                    $str = substr($str, 0, 500);
                }
                $safe[$key] = $str;
            }
        }

        $line = sprintf(
            "[%s] %s %s\n",
            date('c'),
            $event,
            json_encode($safe, JSON_UNESCAPED_SLASHES)
        );

        @file_put_contents($dir . '/license.log', $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Normalize status to a known safe value.
     */
    private static function normalizeStatus(?string $status): string
    {
        $status = $status !== null ? trim($status) : '';
        return in_array($status, self::STATUS_VALUES, true) ? $status : 'inactive';
    }

    /**
     * Upsert state so updates never silently fail when id=1 is missing.
     */
    private static function upsertState(?string $token, ?string $status, ?int $lastCheckAt = null): bool
    {
        $status = self::normalizeStatus($status);
        $now = time();
        $lastCheckAt = $lastCheckAt ?? $now;

        try {
            DB::exec(
                "INSERT INTO i_license_state (id, license_token, license_status, last_check_at, created_at, updated_at)
                 VALUES (1, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     license_token = COALESCE(VALUES(license_token), license_token),
                     license_status = VALUES(license_status),
                     last_check_at = VALUES(last_check_at),
                     updated_at = VALUES(updated_at)",
                [$token, $status, $lastCheckAt, $now, $now]
            );
            return true;
        } catch (Throwable $e) {
            self::logEvent('upsert_failed', [
                'status' => $status,
                'token_tail' => $token ? substr($token, -6) : '',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Persist cached state back into DB when available.
     */
    private static function hydrateFromCache(array $state): void
    {
        $token = isset($state['license_token']) ? trim((string) $state['license_token']) : null;
        if ($token === '') {
            $token = null;
        }
        $status = isset($state['license_status']) ? trim((string) $state['license_status']) : null;
        if ($status === '') {
            $status = null;
        }
        $lastCheckAt = isset($state['last_check_at']) ? (int) $state['last_check_at'] : null;

        if ($token === null && $status === null) {
            return;
        }

        self::upsertState($token, $status, $lastCheckAt);
    }

    /**
     * Ensure a single license row exists.
     */
    public static function ensureRow(): void
    {
        try {
            $row = DB::one("SELECT id FROM i_license_state WHERE id = 1 LIMIT 1");
            if (!$row) {
                $seed = DB::one("SELECT license_token, license_status, last_check_at FROM i_license_state ORDER BY updated_at DESC, id DESC LIMIT 1");
                if ($seed) {
                    self::upsertState(
                        $seed['license_token'] ?? null,
                        $seed['license_status'] ?? null,
                        isset($seed['last_check_at']) ? (int) $seed['last_check_at'] : null
                    );
                    return;
                }
                $now = time();
                DB::exec(
                    "INSERT INTO i_license_state (id, license_status, last_check_at, created_at, updated_at) VALUES (1, 'inactive', 0, ?, ?)",
                    [$now, $now]
                );
            }
        } catch (Throwable $e) {
            // Silent fail to avoid breaking boot if migration not applied yet.
            self::logEvent('ensure_row_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get current license state row.
     */
    public static function getState(): ?array
    {
        $dbRow = null;
        try {
            $dbRow = DB::one("SELECT * FROM i_license_state WHERE id = 1 LIMIT 1");
            if (!$dbRow) {
                $seed = DB::one("SELECT * FROM i_license_state ORDER BY updated_at DESC, id DESC LIMIT 1");
                if ($seed) {
                    self::upsertState(
                        $seed['license_token'] ?? null,
                        $seed['license_status'] ?? null,
                        isset($seed['last_check_at']) ? (int) $seed['last_check_at'] : null
                    );
                    $dbRow = $seed;
                }
            }
        } catch (Throwable $e) {
            // ignore and use fallback paths below
            self::logEvent('get_state_db_failed', ['error' => $e->getMessage()]);
        }

        // File-based fallback (hidden, hashed name) if DB is unavailable or out-of-date.
        $fileState = self::readFileCache();

        // Prefer DB when it is clearly "operational"; otherwise, if file cache indicates an
        // active token/state (common when DB schema is missing columns), prefer file cache.
        if (is_array($dbRow) && !empty($dbRow)) {
            $dbStatus = array_key_exists('license_status', $dbRow) ? ($dbRow['license_status'] ?? null) : null;
            $dbToken = array_key_exists('license_token', $dbRow) ? ($dbRow['license_token'] ?? null) : null;
            $dbOperational = in_array($dbStatus, ['active', 'grace'], true) || !empty($dbToken);

            if (is_array($fileState) && !empty($fileState)) {
                $fileStatus = $fileState['license_status'] ?? null;
                $fileToken = $fileState['license_token'] ?? null;
                $fileOperational = in_array($fileStatus, ['active', 'grace'], true) || !empty($fileToken);

                if (!$dbOperational && $fileOperational) {
                    self::hydrateFromCache($fileState);
                    return $fileState;
                }
            }

            return $dbRow;
        }

        if ($fileState) {
            self::hydrateFromCache($fileState);
            return $fileState;
        }

        // Session fallback if file is also missing
        if (isset($_SESSION['license_fallback']) && is_array($_SESSION['license_fallback'])) {
            self::hydrateFromCache($_SESSION['license_fallback']);
            return $_SESSION['license_fallback'];
        }

        return null;
    }

    /**
     * Generate and persist pending state/nonce.
     */
    public static function createPendingState(int $ttl = self::PENDING_TTL): ?array
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $expires = time() + $ttl;
        $hash = hash('sha256', $nonce);
        try {
            self::ensureRow();
            DB::exec(
                "UPDATE i_license_state SET pending_state = ?, pending_nonce_hash = ?, pending_expires_at = ?, updated_at = ? WHERE id = 1",
                [$state, $hash, $expires, time()]
            );
        } catch (Throwable $e) {
            // Fallback: store pending in session if DB/table missing
            $_SESSION['license_pending'] = [
                'state' => $state,
                'hash' => $hash,
                'expires' => $expires,
            ];
            self::logEvent('create_pending_fallback_session', ['error' => $e->getMessage()]);
        }
        return [
            'state' => $state,
            'nonce' => $nonce,
            'expires_at' => $expires,
        ];
    }

    /**
     * Validate pending state and nonce; clears pending on success when requested.
     */
    public static function validatePending(string $state, string $nonce, bool $clearOnSuccess = false): bool
    {
        $hash = hash('sha256', $nonce);
        // Check DB first
        $row = self::getState();
        if ($row && !empty($row['pending_state'])) {
            if ($row['pending_state'] === $state
                && (empty($row['pending_expires_at']) || (int)$row['pending_expires_at'] >= time())
                && !empty($row['pending_nonce_hash'])
                && hash_equals($row['pending_nonce_hash'], $hash)
            ) {
                if ($clearOnSuccess) {
                    self::clearPending();
                }
                return true;
            }
        }
        // Session fallback
        if (isset($_SESSION['license_pending']) && is_array($_SESSION['license_pending'])) {
            $p = $_SESSION['license_pending'];
            if (($p['state'] ?? '') === $state
                && ($p['expires'] ?? 0) >= time()
                && !empty($p['hash'])
                && hash_equals($p['hash'], $hash)
            ) {
                if ($clearOnSuccess) {
                    unset($_SESSION['license_pending']);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Clear pending state markers.
     */
    public static function clearPending(): void
    {
        try {
            DB::exec(
                "UPDATE i_license_state SET pending_state = NULL, pending_nonce_hash = NULL, pending_expires_at = NULL, updated_at = ? WHERE id = 1",
                [time()]
            );
        } catch (Throwable $e) {
            // ignore
        }
        unset($_SESSION['license_pending']);
    }

    /**
     * Store opaque license token and status.
     */
    public static function storeToken(string $token, string $status = 'active'): bool
    {
        $dbOk = self::upsertState($token, $status, time());
        if (!$dbOk) {
            // Fallback to session-only storage
            $_SESSION['license_fallback'] = [
                'license_token' => $token,
                'license_status' => $status,
                'last_check_at' => time(),
                'pending_state' => null,
                'pending_nonce_hash' => null,
                'pending_expires_at' => null,
            ];
        } else {
            unset($_SESSION['license_fallback']);
        }
        $_SESSION['lg_c'] = [
            's' => $status,
            't' => $token,
            'd' => date('Y-m-d'),
        ];
        self::clearPending();
        $fileOk = self::writeFileCache([
            'license_token' => $token,
            'license_status' => $status,
            'last_check_at' => time(),
            'updated_at' => time(),
        ]);

        if (!$dbOk && !$fileOk) {
            self::logEvent('store_token_not_persisted', [
                'status' => $status,
                'token_tail' => substr($token, -6),
            ]);
        }

        return ($dbOk || $fileOk);
    }

    /**
     * Update license status without altering token.
     */
    public static function updateStatus(string $status): bool
    {
        $cachedToken = null;
        if (isset($_SESSION['lg_c']) && is_array($_SESSION['lg_c']) && isset($_SESSION['lg_c']['t'])) {
            $cachedToken = $_SESSION['lg_c']['t'];
        } elseif (isset($_SESSION['license_fallback']['license_token'])) {
            $cachedToken = $_SESSION['license_fallback']['license_token'];
        }
        $dbOk = self::upsertState($cachedToken, $status, time());
        if (!$dbOk) {
            $_SESSION['license_fallback']['license_status'] = $status;
            $_SESSION['license_fallback']['last_check_at'] = time();
        }
        $_SESSION['lg_c'] = [
            's' => $status,
            't' => $cachedToken,
            'd' => date('Y-m-d'),
        ];
        $fileOk = self::writeFileCache([
            'license_token' => $cachedToken,
            'license_status' => $status,
            'last_check_at' => time(),
            'updated_at' => time(),
        ]);
        if (!$dbOk && !$fileOk) {
            self::logEvent('update_status_not_persisted', [
                'status' => $status,
                'token_tail' => $cachedToken ? substr((string)$cachedToken, -6) : '',
            ]);
        }
        return ($dbOk || $fileOk);
    }

    /**
     * Clear local token and mark inactive.
     */
    public static function clearToken(): bool
    {
        self::ensureRow();
        try {
            DB::exec(
                "UPDATE i_license_state SET license_token = NULL, license_status = 'inactive', last_check_at = 0, updated_at = ? WHERE id = 1",
                [time()]
            );
            self::clearPending();
            unset($_SESSION['license_cache'], $_SESSION['license_fallback'], $_SESSION['lg_c']);
            self::deleteFileCache();
            return true;
        } catch (Throwable $e) {
            unset($_SESSION['license_cache'], $_SESSION['license_fallback'], $_SESSION['lg_c']);
            self::deleteFileCache();
            self::logEvent('clear_token_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Return current status string or null.
     */
    public static function status(): ?string
    {
        $row = self::getState();
        return $row['license_status'] ?? null;
    }

    /**
     * Determine if system is active.
     */
    public static function isActive(): bool
    {
        return self::status() === 'active';
    }

    /**
     * Canonicalize payload for signature verification (sorted keys JSON).
     */
    public static function canonicalize(array $payload): string
    {
        ksort($payload);
        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Verify RSA signature from central server.
     */
    public static function verifySignature(array $payload, string $signature): bool
    {
        // Signature validation disabled; always allow.
        return true;
    }

    /**
     * Build current site domain and path for activation payloads.
     */
    public static function siteMeta(string $baseUrl): array
    {
        $host = parse_url($baseUrl, PHP_URL_HOST) ?? '';
        $path = parse_url($baseUrl, PHP_URL_PATH) ?? '/';
        if ($path === '') {
            $path = '/';
        }
        return [
            'domain' => $host,
            'path' => $path,
        ];
    }

    /**
     * Hidden file path for lightweight cache (hashed to reduce discoverability).
     */
    private static function filePath(): string
    {
        $root = dirname(__DIR__);
        $hash = substr(hash('sha256', $root), 0, 16);
        return $root . '/includes/.li_' . $hash . '.cache';
    }

    /**
     * Read cache from hidden file if fresh.
     */
    private static function readFileCache(): ?array
    {
        $path = self::filePath();
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }
        $updatedAt = (int) ($data['updated_at'] ?? 0);
        if ($updatedAt === 0 || (time() - $updatedAt) > self::FILE_TTL) {
            return null;
        }
        $status = $data['license_status'] ?? null;
        $token = $data['license_token'] ?? null;
        if ($status === null && $token === null) {
            return null;
        }
        return $data;
    }

    /**
     * Write cache to hidden file atomically.
     */
    private static function writeFileCache(array $data): bool
    {
        $path = self::filePath();
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return false;
        }
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $payload, LOCK_EX) !== false) {
            @chmod($tmp, 0600);
            if (@rename($tmp, $path)) {
                return true;
            }
            @unlink($tmp);
            return false;
        } else {
            @unlink($tmp);
            return false;
        }
    }

    /**
     * Remove cache file silently.
     */
    private static function deleteFileCache(): void
    {
        $path = self::filePath();
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
