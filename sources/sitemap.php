<?php
header('Content-Type: application/xml; charset=UTF-8');

if (ob_get_length()) {
    ob_clean();
}

if (function_exists('iN_BuildSitemapXml')) {
    echo iN_BuildSitemapXml();
} else {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
}
