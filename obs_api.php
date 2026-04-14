<?php
include_once "includes/inc.php";

if (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_clean();
}

function obs_overlay_json(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

if (!$iN->iN_IsObsOverlayEnabled()) {
    obs_overlay_json(['ok' => 0, 'error' => $LANG['obs_overlay_feature_disabled']], 404);
}

if (!function_exists('obs_overlay_is_https_url')) {
    function obs_overlay_is_https_url(string $url): bool {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        return isset($parts['scheme']) && strtolower((string)$parts['scheme']) === 'https';
    }
}

function obs_overlay_clamp($value, float $min, float $max): float {
    $num = is_numeric($value) ? (float)$value : $min;
    if ($num < $min) {
        return $min;
    }
    if ($num > $max) {
        return $max;
    }
    return $num;
}

function obs_overlay_clean_text($value, int $maxLen): string {
    $text = trim((string)$value);
    if ($text === '') {
        return '';
    }
    $text = strip_tags($text);
    if ($maxLen > 0 && strlen($text) > $maxLen) {
        $text = substr($text, 0, $maxLen);
    }
    return $text;
}

function obs_overlay_normalize_types($value): array {
    $allowed = ['tips', 'live_gift'];
    $types = [];
    if (is_array($value)) {
        foreach ($value as $entry) {
            $entry = (string)$entry;
            if (in_array($entry, $allowed, true)) {
                $types[] = $entry;
            }
        }
    }
    if (empty($types)) {
        $types = $allowed;
    }
    return array_values(array_unique($types));
}

function obs_overlay_widgets_from_row($row): array {
    $enabled = json_decode((string)($row['enabled_widgets'] ?? ''), true);
    if (!is_array($enabled)) {
        $enabled = [];
    }
    $defaults = [
        'donation_total' => true,
        'alerts' => true,
        'milestone' => true,
        'cta' => true,
        'watermark' => true,
    ];
    $widgets = [];
    foreach ($defaults as $key => $default) {
        $widgets[$key] = array_key_exists($key, $enabled) ? (bool)$enabled[$key] : $default;
    }
    return $widgets;
}

function obs_overlay_rate_limit(int $overlayId, string $ipHash, string $bucket, int $cooldownSeconds): int {
    // Reuse the clicks table to persist lightweight per-IP rate limit markers.
    $bucket = $bucket === 'alerts' ? 'alerts' : 'state';
    $now = time();
    try {
        $last = DB::one(
            "SELECT created_at FROM i_obs_overlay_clicks WHERE obs_overlay_id_fk = ? AND ip_hash = ? AND user_agent = ? ORDER BY obs_overlay_click_id DESC LIMIT 1",
            [$overlayId, $ipHash, $bucket]
        );
        if ($last && isset($last['created_at'])) {
            $elapsed = $now - (int)$last['created_at'];
            if ($elapsed >= 0 && $elapsed < $cooldownSeconds) {
                $retryAfter = $cooldownSeconds - $elapsed;
                return $retryAfter > 0 ? $retryAfter : 1;
            }
        }
        DB::exec(
            "DELETE FROM i_obs_overlay_clicks WHERE obs_overlay_id_fk = ? AND ip_hash = ? AND user_agent = ?",
            [$overlayId, $ipHash, $bucket]
        );
        DB::exec(
            "INSERT INTO i_obs_overlay_clicks (obs_overlay_id_fk, ip_hash, user_agent, created_at) VALUES (?,?,?,?)",
            [$overlayId, $ipHash, $bucket, $now]
        );
    } catch (Throwable $e) {
        return 0;
    }
    return 0;
}

$overlayToken = $overlayToken ?? ($_GET['token'] ?? '');
$overlayAction = $overlayAction ?? ($_GET['action'] ?? '');
$overlayToken = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$overlayToken);
$overlayAction = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$overlayAction);

if ($overlayToken === '' || $overlayAction === '') {
    obs_overlay_json(['ok' => 0, 'error' => $LANG['obs_overlay_invalid']], 404);
}

$overlay = DB::one(
    "SELECT * FROM i_obs_overlays WHERE overlay_token = ? AND is_active = '1' LIMIT 1",
    [$overlayToken]
);
if (!$overlay) {
    obs_overlay_json(['ok' => 0, 'error' => $LANG['obs_overlay_invalid']], 404);
}

$overlayId = (int)($overlay['obs_overlay_id'] ?? 0);
$creatorId = (int)($overlay['creator_id_fk'] ?? 0);
if ($overlayId <= 0 || $creatorId <= 0) {
    obs_overlay_json(['ok' => 0, 'error' => $LANG['obs_overlay_invalid']], 404);
}

$ip = '';
if (isset($iN) && method_exists($iN, 'iN_GetIPAddress')) {
    $ip = (string)$iN->iN_GetIPAddress();
} else {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
}
if ($ip === '') {
    $ip = '0.0.0.0';
}
$ipHash = hash('sha256', $ip);

if ($overlayAction === 'state') {
    try {
        $retryAfter = obs_overlay_rate_limit($overlayId, $ipHash, 'state', 2);
        if ($retryAfter > 0) {
            obs_overlay_json(['ok' => false, 'retry_after' => $retryAfter], 429);
        }

        $now = time();
        $settingsRaw = json_decode((string)($overlay['settings_json'] ?? ''), true);
        if (!is_array($settingsRaw)) {
            $settingsRaw = [];
        }
        $activeLive = DB::one(
            "SELECT live_id, started_at, finish_time, extended_seconds FROM i_live WHERE live_uid_fk = ? AND finish_time >= ? ORDER BY live_id DESC LIMIT 1",
            [$creatorId, $now]
        );

        $donationMode = (string)($overlay['donation_mode'] ?? 'last24h');
        $donationMode = $donationMode === 'alltime' ? 'alltime' : 'last24h';
        $params = [$creatorId];
        $sql = "SELECT SUM(CAST(amount AS DECIMAL(12,2))) FROM i_user_payments WHERE payed_iuid_fk = ? AND payment_status = 'ok' AND payment_type = 'tips'";
        if ($donationMode === 'last24h') {
            $since = $now - 86400;
            $sql .= " AND payment_time >= ?";
            $params[] = $since;
        }
        $donationTotal = (float) DB::col($sql, $params);
        if (!is_finite($donationTotal)) {
            $donationTotal = 0.0;
        }

        $goalAmount = (float)($overlay['milestone_goal_amount'] ?? 0);
        if (!is_finite($goalAmount) || $goalAmount < 0) {
            $goalAmount = 0.0;
        }
        $progress = $donationTotal;
        $percent = $goalAmount > 0 ? min(100, ($progress / $goalAmount) * 100) : 0;

        $milestoneTitle = trim((string)($overlay['milestone_title'] ?? ''));
        if ($milestoneTitle === '') {
            $milestoneTitle = $LANG['obs_overlay_milestone_default_title'];
        }

        $subscriberCount = 0;
        try {
            $subscriberCount = (int) DB::col(
                "SELECT COUNT(*) FROM i_user_subscriptions WHERE subscribed_iuid_fk = ? AND status = 'active' AND finished = '0' AND subscription_scope = 'profile'",
                [$creatorId]
            );
        } catch (Throwable $e) {
            $subscriberCount = 0;
        }

        $payload = [
            'ok' => 1,
            'donation_total_last24h' => round($donationTotal, 2),
            'milestone' => [
                'title' => $milestoneTitle,
                'goal' => round($goalAmount, 2),
                'progress' => round($progress, 2),
                'percent' => round($percent, 2),
            ],
            'subscriber_count' => $subscriberCount,
        ];

        $sessionStart = $activeLive && isset($activeLive['started_at']) ? (int)$activeLive['started_at'] : 0;
        $buildEventFilter = function (string $mode, array $types) use ($overlayId, $now, $sessionStart): array {
            $mode = in_array($mode, ['last24h', 'alltime', 'session'], true) ? $mode : 'last24h';
            if ($mode === 'session' && $sessionStart <= 0) {
                $mode = 'last24h';
            }
            $params = [$overlayId];
            $where = "overlay_id = ?";
            if (!empty($types)) {
                $placeholders = implode(',', array_fill(0, count($types), '?'));
                $where .= " AND payment_type IN ($placeholders)";
                $params = array_merge($params, $types);
            }
            if ($mode === 'last24h') {
                $where .= " AND created_at >= ?";
                $params[] = $now - 86400;
            } elseif ($mode === 'session' && $sessionStart > 0) {
                $where .= " AND created_at >= ?";
                $params[] = $sessionStart;
            }
            return [$where, $params, $mode];
        };

        $leaderboardConfig = isset($settingsRaw['leaderboard']) && is_array($settingsRaw['leaderboard'])
            ? $settingsRaw['leaderboard']
            : null;
        $leaderboardEntries = [];
        if ($leaderboardConfig !== null) {
            $leaderboardMode = (string)($leaderboardConfig['mode'] ?? 'last24h');
            $leaderboardLimit = (int)obs_overlay_clamp($leaderboardConfig['limit'] ?? 5, 1, 10);
            $leaderboardTypes = obs_overlay_normalize_types($leaderboardConfig['include_types'] ?? []);
            [$where, $params, $leaderboardMode] = $buildEventFilter($leaderboardMode, $leaderboardTypes);
            $rows = DB::all(
                "SELECT payer_id, payer_name, SUM(CAST(amount AS DECIMAL(12,2))) AS total_amount
                 FROM i_obs_support_events
                 WHERE $where
                 GROUP BY payer_id, payer_name
                 ORDER BY total_amount DESC
                 LIMIT $leaderboardLimit",
                $params
            );
            foreach ($rows as $row) {
                $total = (float)($row['total_amount'] ?? 0);
                if (!is_finite($total) || $total <= 0) {
                    continue;
                }
                $name = obs_overlay_clean_text($row['payer_name'] ?? '', 120);
                if ($name === '') {
                    $name = obs_overlay_clean_text($LANG['campaign_anonymous'] ?? 'Anonymous', 120);
                }
                $leaderboardEntries[] = [
                    'name' => $name,
                    'amount' => round($total, 2),
                ];
            }
            $payload['leaderboard'] = [
                'mode' => $leaderboardMode,
                'entries' => $leaderboardEntries,
            ];
        }

        $lastSupporterConfig = isset($settingsRaw['last_supporter']) && is_array($settingsRaw['last_supporter'])
            ? $settingsRaw['last_supporter']
            : null;
        $lastSupporterRow = null;
        if ($lastSupporterConfig !== null) {
            $lastMode = (string)($lastSupporterConfig['mode'] ?? 'last24h');
            $lastTypes = obs_overlay_normalize_types($lastSupporterConfig['include_types'] ?? []);
            [$where, $params, $lastMode] = $buildEventFilter($lastMode, $lastTypes);
            $lastSupporterRow = DB::one(
                "SELECT payer_name, amount, payment_type, created_at FROM i_obs_support_events WHERE $where ORDER BY id DESC LIMIT 1",
                $params
            );
            $lastLabel = obs_overlay_clean_text($lastSupporterConfig['label'] ?? '', 120);
            $showAmount = !empty($lastSupporterConfig['show_amount']);
            $lastName = $lastSupporterRow ? obs_overlay_clean_text($lastSupporterRow['payer_name'] ?? '', 120) : '';
            if ($lastName === '' && $lastSupporterRow) {
                $lastName = obs_overlay_clean_text($LANG['campaign_anonymous'] ?? 'Anonymous', 120);
            }
            $payload['last_supporter'] = [
                'label' => $lastLabel,
                'show_amount' => $showAmount,
                'name' => $lastName,
                'amount' => $lastSupporterRow ? round((float)($lastSupporterRow['amount'] ?? 0), 2) : 0,
                'type' => $lastSupporterRow ? (string)($lastSupporterRow['payment_type'] ?? '') : '',
            ];
        }

        $targetGoalConfig = isset($settingsRaw['target_goal']) && is_array($settingsRaw['target_goal'])
            ? $settingsRaw['target_goal']
            : null;
        if ($targetGoalConfig !== null) {
            $targetMode = (string)($targetGoalConfig['mode'] ?? 'last24h');
            $targetTypes = obs_overlay_normalize_types($targetGoalConfig['include_types'] ?? []);
            [$where, $params, $targetMode] = $buildEventFilter($targetMode, $targetTypes);
            $targetGoal = obs_overlay_clamp($targetGoalConfig['goal_amount'] ?? 0, 0, 9999999);
            $targetTitle = obs_overlay_clean_text($targetGoalConfig['title'] ?? '', 140);
            $sum = (float) DB::col("SELECT SUM(CAST(amount AS DECIMAL(12,2))) FROM i_obs_support_events WHERE $where", $params);
            if (!is_finite($sum)) {
                $sum = 0.0;
            }
            $targetPercent = $targetGoal > 0 ? min(100, ($sum / $targetGoal) * 100) : 0;
            $payload['target_goal'] = [
                'title' => $targetTitle,
                'goal' => round($targetGoal, 2),
                'progress' => round($sum, 2),
                'percent' => round($targetPercent, 2),
            ];
        }

        $runningTextConfig = isset($settingsRaw['running_text']) && is_array($settingsRaw['running_text'])
            ? $settingsRaw['running_text']
            : null;
        if ($runningTextConfig !== null) {
            $runningMode = (string)($runningTextConfig['mode'] ?? 'custom');
            $runningMode = in_array($runningMode, ['custom', 'recent', 'leaderboard'], true) ? $runningMode : 'custom';
            $template = obs_overlay_clean_text($runningTextConfig['template'] ?? '', 200);
            $customText = obs_overlay_clean_text($runningTextConfig['custom_text'] ?? '', 220);
            $speed = (int)obs_overlay_clamp($runningTextConfig['speed'] ?? 30, 5, 120);
            $text = '';
            if ($runningMode === 'custom') {
                $text = $customText;
            } elseif ($runningMode === 'recent') {
                if ($lastSupporterRow === null) {
                    $lastSupporterRow = DB::one(
                        "SELECT payer_name, amount FROM i_obs_support_events WHERE overlay_id = ? ORDER BY id DESC LIMIT 1",
                        [$overlayId]
                    );
                }
                if ($lastSupporterRow) {
                    $name = obs_overlay_clean_text($lastSupporterRow['payer_name'] ?? '', 120);
                    if ($name === '') {
                        $name = obs_overlay_clean_text($LANG['campaign_anonymous'] ?? 'Anonymous', 120);
                    }
                    $amount = formatCurrency((float)($lastSupporterRow['amount'] ?? 0));
                    $base = $template !== '' ? $template : '{name} {amount}';
                    $text = str_replace(['{name}', '{amount}'], [$name, $amount], $base);
                }
            } elseif ($runningMode === 'leaderboard') {
                $entry = !empty($leaderboardEntries) ? $leaderboardEntries[0] : null;
                if ($entry === null) {
                    [$where, $params, $unusedMode] = $buildEventFilter('last24h', ['tips', 'live_gift']);
                    $row = DB::one(
                        "SELECT payer_name, SUM(CAST(amount AS DECIMAL(12,2))) AS total_amount
                         FROM i_obs_support_events
                         WHERE $where
                         GROUP BY payer_name
                         ORDER BY total_amount DESC
                         LIMIT 1",
                        $params
                    );
                    if ($row) {
                        $entry = [
                            'name' => $row['payer_name'] ?? '',
                            'amount' => (float)($row['total_amount'] ?? 0)
                        ];
                    }
                }
                if ($entry) {
                    $name = obs_overlay_clean_text($entry['name'] ?? '', 120);
                    if ($name === '') {
                        $name = obs_overlay_clean_text($LANG['campaign_anonymous'] ?? 'Anonymous', 120);
                    }
                    $amount = formatCurrency((float)($entry['amount'] ?? 0));
                    $base = $template !== '' ? $template : '{name} {amount}';
                    $text = str_replace(['{name}', '{amount}'], [$name, $amount], $base);
                }
            }
            $payload['running_text'] = [
                'text' => obs_overlay_clean_text($text, 240),
                'speed' => $speed,
            ];
        }

        $liveExtenderConfig = isset($settingsRaw['live_extender']) && is_array($settingsRaw['live_extender'])
            ? $settingsRaw['live_extender']
            : null;
        $liveDurationRow = $activeLive;
        if ($liveExtenderConfig !== null) {
            $unitAmount = (float)obs_overlay_clamp($liveExtenderConfig['unit_amount'] ?? 0, 0, 999999);
            $secondsPerUnit = (int)obs_overlay_clamp($liveExtenderConfig['seconds_per_unit'] ?? 0, 5, 600);
            $maxSeconds = (int)obs_overlay_clamp($liveExtenderConfig['max_seconds'] ?? 0, 0, 21600);
            $extendTypes = obs_overlay_normalize_types($liveExtenderConfig['include_types'] ?? []);
            if ($unitAmount > 0 && $secondsPerUnit > 0) {
                try {
                    DB::begin();
                    $liveRow = DB::one(
                        "SELECT live_id, finish_time, extended_seconds FROM i_live WHERE live_uid_fk = ? AND finish_time >= ? ORDER BY live_id DESC LIMIT 1 FOR UPDATE",
                        [$creatorId, $now]
                    );
                    if ($liveRow) {
                        $placeholders = implode(',', array_fill(0, count($extendTypes), '?'));
                        $params = array_merge([$overlayId], $extendTypes);
                        $events = DB::all(
                            "SELECT id, amount FROM i_obs_support_events
                             WHERE overlay_id = ? AND processed_for_extension = 0 AND payment_type IN ($placeholders)
                             ORDER BY id ASC LIMIT 50 FOR UPDATE",
                            $params
                        );
                        $processIds = [];
                        $totalAdded = 0;
                        foreach ($events as $eventRow) {
                            $eventId = (int)($eventRow['id'] ?? 0);
                            if ($eventId > 0) {
                                $processIds[] = $eventId;
                            }
                            $amount = (float)($eventRow['amount'] ?? 0);
                            if ($amount <= 0 || $unitAmount <= 0) {
                                continue;
                            }
                            $units = (int)floor($amount / $unitAmount);
                            if ($units <= 0) {
                                continue;
                            }
                            $totalAdded += $units * $secondsPerUnit;
                        }
                        $currentExtended = (int)($liveRow['extended_seconds'] ?? 0);
                        if ($totalAdded > 0) {
                            $newExtended = $currentExtended + $totalAdded;
                            if ($maxSeconds > 0 && $newExtended > $maxSeconds) {
                                $totalAdded = max(0, $maxSeconds - $currentExtended);
                                $newExtended = $currentExtended + $totalAdded;
                            }
                            if ($totalAdded > 0) {
                                DB::exec(
                                    "UPDATE i_live SET finish_time = finish_time + ?, extended_seconds = ? WHERE live_id = ?",
                                    [$totalAdded, $newExtended, (int)$liveRow['live_id']]
                                );
                                $liveRow['finish_time'] = (int)$liveRow['finish_time'] + $totalAdded;
                                $liveRow['extended_seconds'] = $newExtended;
                            }
                        }
                        if (!empty($processIds)) {
                            $placeholders = implode(',', array_fill(0, count($processIds), '?'));
                            DB::exec(
                                "UPDATE i_obs_support_events SET processed_for_extension = 1, processed_at = ? WHERE id IN ($placeholders)",
                                array_merge([$now], $processIds)
                            );
                        }
                        $liveDurationRow = $liveRow;
                    }
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                }
            }
        }

        if ($liveDurationRow) {
            $finishTime = (int)($liveDurationRow['finish_time'] ?? 0);
            $remaining = $finishTime > 0 ? max(0, $finishTime - $now) : 0;
            $payload['live_duration'] = [
                'finish_time' => $finishTime,
                'remaining_seconds' => $remaining,
                'extended_seconds' => (int)($liveDurationRow['extended_seconds'] ?? 0),
            ];
        }

        obs_overlay_json($payload);
    } catch (Throwable $e) {
        obs_overlay_json(['ok' => false], 200);
    }
}

if ($overlayAction === 'alerts') {
    try {
        $retryAfter = obs_overlay_rate_limit($overlayId, $ipHash, 'alerts', 2);
        if ($retryAfter > 0) {
            obs_overlay_json(['ok' => false, 'retry_after' => $retryAfter], 429);
        }

        $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        $events = [];

        $rows = DB::all(
            "SELECT id, payment_type, amount, payer_name, created_at
             FROM i_obs_support_events
             WHERE overlay_id = ? AND id > ?
             ORDER BY id ASC
             LIMIT 20",
            [$overlayId, $lastId]
        );
        $maxId = $lastId;
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $maxId = max($maxId, $id);
            $name = obs_overlay_clean_text($row['payer_name'] ?? '', 120);
            $events[] = [
                'id' => $id,
                'type' => (string)($row['payment_type'] ?? ''),
                'amount' => round((float)($row['amount'] ?? 0), 2),
                'name' => $name,
                'time' => (int)($row['created_at'] ?? 0),
            ];
        }

        obs_overlay_json([
            'ok' => 1,
            'events' => $events,
            'new_last_id' => $maxId,
        ]);
    } catch (Throwable $e) {
        obs_overlay_json(['ok' => false], 200);
    }
}

if ($overlayAction === 'click') {
    $widgets = obs_overlay_widgets_from_row($overlay);
    $ctaLabel = trim((string)($overlay['cta_label'] ?? ''));
    $ctaUrl = trim((string)($overlay['cta_url'] ?? ''));
    if (!$widgets['cta'] || $ctaLabel === '' || $ctaUrl === '' || !obs_overlay_is_https_url($ctaUrl)) {
        obs_overlay_json(['ok' => 0, 'error' => $LANG['obs_overlay_cta_disabled']], 400);
    }

    $now = time();
    $existing = DB::col(
        "SELECT 1 FROM i_obs_overlay_clicks
         WHERE obs_overlay_id_fk = ? AND ip_hash = ? AND user_agent LIKE 'click%' AND created_at >= ?
         LIMIT 1",
        [$overlayId, $ipHash, $now - 60]
    );
    if (!$existing) {
        $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (strlen($userAgent) > 200) {
            $userAgent = substr($userAgent, 0, 200);
        }
        $userAgentTag = $userAgent !== '' ? 'click|' . $userAgent : 'click';
        DB::exec(
            "INSERT INTO i_obs_overlay_clicks (obs_overlay_id_fk, ip_hash, user_agent, created_at) VALUES (?,?,?,?)",
            [$overlayId, $ipHash, $userAgentTag, $now]
        );
    }

    obs_overlay_json(['ok' => 1, 'redirect' => $ctaUrl]);
}

obs_overlay_json(['ok' => 0, 'error' => $LANG['obs_overlay_invalid']], 404);
