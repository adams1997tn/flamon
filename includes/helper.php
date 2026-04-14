<?php
/**
 * Remove encoded HTML entities from the given string.
 *
 * @param string $string
 * @return string
 */
function cleanString($string) {
    return preg_replace("/&#?[a-z0-9]+;/i", "", $string);
}

/**
 * Sanitize and secure a string output for display.
 *
 * @param string  $string           The input string to sanitize.
 * @param int     $censored_words   Not used in current version, reserved for future content moderation.
 * @param bool    $br               Whether to convert new lines to <br> tags.
 * @param int     $strip            Whether to apply stripslashes (1 = yes).
 * @param bool    $cleanString      Whether to remove encoded HTML entities.
 * @param bool    $validate_url     Whether to validate string as a URL.
 * @param string  $allowed_tags     List of allowed HTML tags.
 *
 * @return string|false             Sanitized string or false if URL validation fails.
 */
function iN_HelpSecure($string, $censored_words = 0, $br = true, $strip = 0, $cleanString = true, $validate_url = false, $allowed_tags = '<br><span><strong><b><i>') {
    if (!is_string($string)) {
        $string = (string) $string;
    }

    $string = trim($string);

    if ($validate_url && !filter_var($string, FILTER_VALIDATE_URL)) {
        return false;
    }

    if ($cleanString) {
        $string = preg_replace("/&#?[a-z0-9]+;/i", "", $string);
    }

    $string = strip_tags($string, $allowed_tags);

    if ($br) {
        $string = str_replace(["\r\n", "\n\r", "\r", "\n"], " <br>", $string);
    } else {
        $string = str_replace(["\r\n", "\n\r", "\r", "\n"], "", $string);
    }

    if ($strip == 1) {
        $string = stripslashes($string);
    }

    $string = str_replace('&amp;#', '&#', $string);

    return $string;
}

/**
 * Prepare content safely for display inside a <textarea> element.
 *
 * Converts HTML <br> tags to newline characters and escapes special characters.
 *
 * @param string $string The raw input string (e.g. from database)
 * @return string Sanitized and formatted string for textarea
 */
function iN_SecureTextareaOutput($string) {
    if (!is_string($string)) {
        $string = (string) $string;
    }

    // Convert <br> to actual newline characters
    $string = str_replace(['<br>', '<br/>', '<br />'], "\n", $string);

    // Convert special characters to HTML entities to prevent XSS
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
function iN_HelpSecureUrl($url) {
    return iN_HelpSecure($url, 0, false, 0, true, true, '');
}

/**
 * Build an application URL that works on both Apache and Nginx.
 * On Nginx servers where .htaccess is ignored, we prefix routes with index.php/.
 * Assets should continue to use $base_url directly.
 */
function route_url(string $path = ''): string {
    $base = rtrim($GLOBALS['base_url'] ?? '/', '/');
    $force = getenv('FORCE_INDEX_IN_URLS');
    $preferIndex = false;
    if ($force !== false) {
        $preferIndex = in_array(strtolower((string)$force), ['1','true','yes','on'], true);
    } else {
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $preferIndex = stripos($server, 'nginx') !== false;
    }
    $path = ltrim($path, '/');
    if ($preferIndex) {
        return $base . '/index.php' . ($path !== '' ? '/' . $path : '/');
    }
    return $base . '/' . $path;
}

if (!function_exists('iN_BuildSitemapXml')) {
    function iN_BuildSitemapXml(array $options = []): string {
        $baseUrl = rtrim((string) ($options['base_url'] ?? ($GLOBALS['base_url'] ?? '')), '/');
        $disallowedRaw = (string) ($options['disallowed_usernames'] ?? ($GLOBALS['disallowedUserNames'] ?? ''));
        $batchSize = (int) ($options['batch_size'] ?? 500);
        if ($batchSize < 1) {
            $batchSize = 500;
        }

        $includeBlog = $options['include_blog'] ?? null;
        if ($includeBlog === null) {
            $includeBlog = !isset($GLOBALS['blogFeatureStatus']) || (string) $GLOBALS['blogFeatureStatus'] === '1';
        }
        $includeAgencies = $options['include_agencies'] ?? null;
        if ($includeAgencies === null) {
            $includeAgencies = isset($GLOBALS['agencyModuleStatus']) && (string) $GLOBALS['agencyModuleStatus'] === 'yes';
        }
        $includeExplore = $options['include_explore'] ?? null;
        if ($includeExplore === null) {
            $includeExplore = isset($GLOBALS['iN']) && method_exists($GLOBALS['iN'], 'iN_CheckpageExist')
                && $GLOBALS['iN']->iN_CheckpageExist('explore');
        }

        $disallowedList = [];
        if ($disallowedRaw !== '') {
            $parts = array_map('trim', explode(',', $disallowedRaw));
            foreach ($parts as $name) {
                if ($name !== '') {
                    $disallowedList[] = strtolower($name);
                }
            }
            $disallowedList = array_values(array_unique($disallowedList));
        }
        $disallowedSql = '';
        if (!empty($disallowedList)) {
            $disallowedSql = ' AND LOWER(U.i_username) NOT IN (' . implode(',', array_fill(0, count($disallowedList), '?')) . ')';
        }

        $escape = static function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        };
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $emit = static function (string $loc, ?int $lastmod = null) use (&$xml, $escape): void {
            $xml .= '<url><loc>' . $escape($loc) . '</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>' . $escape(date('c', $lastmod)) . '</lastmod>';
            }
            $xml .= '</url>';
        };

        $staticPaths = [
            '' => true,
            'creators' => true,
            'marketplace' => true,
            'communities' => true,
        ];
        if ($includeBlog) {
            $staticPaths['blog'] = true;
        }
        if ($includeAgencies) {
            $staticPaths['agencies'] = true;
        }
        if ($includeExplore) {
            $staticPaths['explore'] = true;
        }

        foreach (array_keys($staticPaths) as $path) {
            $loc = $path === '' ? ($baseUrl . '/') : ($baseUrl . '/' . ltrim($path, '/'));
            $emit($loc);
        }

        if (class_exists('DB')) {
            try {
                $lastId = 0;
                $profileWhere = "U.uStatus IN('1','3')
                    AND U.profile_status = '1'
                    AND U.userType <> '2'
                    AND U.fake_user_status = '0'
                    AND U.i_username IS NOT NULL
                    AND U.i_username <> ''" . $disallowedSql;
                while (true) {
                    $params = $disallowedList;
                    $params[] = $lastId;
                    $rows = DB::all(
                        "SELECT U.iuid, U.i_username, U.registered
                         FROM i_users U
                         WHERE $profileWhere AND U.iuid > ?
                         ORDER BY U.iuid ASC
                         LIMIT $batchSize",
                        $params
                    );
                    if (empty($rows)) {
                        break;
                    }
                    foreach ($rows as $row) {
                        $username = (string) ($row['i_username'] ?? '');
                        if ($username === '') {
                            continue;
                        }
                        $loc = $baseUrl . '/' . $username;
                        $lastmod = isset($row['registered']) ? (int) $row['registered'] : null;
                        $emit($loc, $lastmod ?: null);
                    }
                    if (count($rows) < $batchSize) {
                        break;
                    }
                    $lastRow = end($rows);
                    $lastId = (int) ($lastRow['iuid'] ?? $lastId);
                }

                $lastId = 0;
                $postWhere = "P.post_status = '1'
                    AND P.scheduled_status = 'published'
                    AND P.who_can_see = '1'
                    AND P.post_type <> 'story'
                    AND P.url_slug IS NOT NULL
                    AND P.url_slug <> ''
                    AND U.uStatus IN('1','3')
                    AND U.profile_status = '1'
                    AND U.userType <> '2'
                    AND U.fake_user_status = '0'
                    AND U.i_username IS NOT NULL
                    AND U.i_username <> ''" . $disallowedSql;
                while (true) {
                    $params = $disallowedList;
                    $params[] = $lastId;
                    $rows = DB::all(
                        "SELECT P.post_id, P.url_slug, P.post_created_time
                         FROM i_posts P
                         INNER JOIN i_users U ON U.iuid = P.post_owner_id
                         WHERE $postWhere AND P.post_id > ?
                         ORDER BY P.post_id ASC
                         LIMIT $batchSize",
                        $params
                    );
                    if (empty($rows)) {
                        break;
                    }
                    foreach ($rows as $row) {
                        $slug = (string) ($row['url_slug'] ?? '');
                        $postId = (int) ($row['post_id'] ?? 0);
                        if ($slug === '' || $postId < 1) {
                            continue;
                        }
                        $loc = $baseUrl . '/post/' . $slug . '_' . $postId;
                        $lastmod = isset($row['post_created_time']) ? (int) $row['post_created_time'] : null;
                        $emit($loc, $lastmod ?: null);
                    }
                    if (count($rows) < $batchSize) {
                        break;
                    }
                    $lastRow = end($rows);
                    $lastId = (int) ($lastRow['post_id'] ?? $lastId);
                }

                if ($includeBlog) {
                    $lastId = 0;
                    while (true) {
                        $rows = DB::all(
                            "SELECT blog_id, slug, updated_at
                             FROM i_blog_posts
                             WHERE status = 'published' AND slug <> '' AND blog_id > ?
                             ORDER BY blog_id ASC
                             LIMIT $batchSize",
                            [$lastId]
                        );
                        if (empty($rows)) {
                            break;
                        }
                        foreach ($rows as $row) {
                            $slug = (string) ($row['slug'] ?? '');
                            $blogId = (int) ($row['blog_id'] ?? 0);
                            if ($slug === '' || $blogId < 1) {
                                continue;
                            }
                            $loc = $baseUrl . '/blog/' . $slug;
                            $lastmod = isset($row['updated_at']) ? (int) $row['updated_at'] : null;
                            $emit($loc, $lastmod ?: null);
                        }
                        if (count($rows) < $batchSize) {
                            break;
                        }
                        $lastRow = end($rows);
                        $lastId = (int) ($lastRow['blog_id'] ?? $lastId);
                    }
                }

                $lastId = 0;
                $productWhere = "P.pr_status IN('1')
                    AND P.pr_name_slug IS NOT NULL
                    AND P.pr_name_slug <> ''
                    AND U.uStatus IN('1','3')
                    AND U.profile_status = '1'
                    AND U.userType <> '2'
                    AND U.fake_user_status = '0'
                    AND U.i_username IS NOT NULL
                    AND U.i_username <> ''" . $disallowedSql;
                while (true) {
                    $params = $disallowedList;
                    $params[] = $lastId;
                    $rows = DB::all(
                        "SELECT P.pr_id, P.pr_name_slug, P.pr_created_time
                         FROM i_user_product_posts P
                         INNER JOIN i_users U ON U.iuid = P.iuid_fk
                         WHERE $productWhere AND P.pr_id > ?
                         ORDER BY P.pr_id ASC
                         LIMIT $batchSize",
                        $params
                    );
                    if (empty($rows)) {
                        break;
                    }
                    foreach ($rows as $row) {
                        $slug = (string) ($row['pr_name_slug'] ?? '');
                        $productId = (int) ($row['pr_id'] ?? 0);
                        if ($slug === '' || $productId < 1) {
                            continue;
                        }
                        $loc = $baseUrl . '/product/' . $slug . '_' . $productId;
                        $lastmod = isset($row['pr_created_time']) ? (int) $row['pr_created_time'] : null;
                        $emit($loc, $lastmod ?: null);
                    }
                    if (count($rows) < $batchSize) {
                        break;
                    }
                    $lastRow = end($rows);
                    $lastId = (int) ($lastRow['pr_id'] ?? $lastId);
                }
            } catch (Throwable $e) {
                $xml .= '';
            }
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
?>
