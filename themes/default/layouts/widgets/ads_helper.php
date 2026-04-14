<?php
include_once __DIR__ . '/adsense_helper.php';

if (!function_exists('iN_get_ad_slot')) {
    function iN_get_ad_slot(string $placementKey): array {
        global $iN;
        if (!isset($iN) || !method_exists($iN, 'iN_GetActiveAdByPlacementKey')) {
            return [];
        }
        $ad = $iN->iN_GetActiveAdByPlacementKey($placementKey);
        if (!$ad || empty($ad['code_snippet'])) {
            return [];
        }
        if ((int)($ad['placement_status'] ?? 0) !== 1) {
            return [];
        }
        if (isset($ad['code_status']) && (int)$ad['code_status'] !== 1) {
            return [];
        }
        return $ad;
    }
}

if (!function_exists('iN_render_ad_placement')) {
    function iN_render_ad_placement(string $placementKey, string $position = 'generic'): string {
        $ad = iN_get_ad_slot($placementKey);
        if (empty($ad)) {
            return '';
        }
        $class = 'adsense_block adsense_' . $position . ' ad_slot_' . $placementKey;
        return '<div class="' . iN_HelpSecure($class) . '" data-placement="' . iN_HelpSecure($placementKey) . '">' . $ad['code_snippet'] . '</div>';
    }
}

if (!function_exists('iN_render_ad_with_legacy')) {
    function iN_render_ad_with_legacy(string $placementKey, string $position, array $legacy = []): string {
        $rendered = iN_render_ad_placement($placementKey, $position);
        if ($rendered !== '') {
            return $rendered;
        }
        $client = $legacy['client'] ?? '';
        $slot = $legacy['slot'] ?? '';
        $size = $legacy['size'] ?? 'responsive';
        if (!empty($client) && !empty($slot)) {
            return iN_render_adsense_slot($client, $slot, $size, $position);
        }
        return '';
    }
}

if (!function_exists('iN_get_inline_ad_rules')) {
    function iN_get_inline_ad_rules(array $legacy = []): array {
        $rendered = iN_render_ad_placement('inline_feed', 'inline');
        $ad = iN_get_ad_slot('inline_feed');
        if (!empty($rendered) && !empty($ad)) {
            return [
                'rendered' => $rendered,
                'frequency' => isset($ad['inline_frequency']) ? (int)$ad['inline_frequency'] : 0,
                'offset' => isset($ad['inline_offset']) ? (int)$ad['inline_offset'] : 0,
                'provider' => $ad['code_provider'] ?? ''
            ];
        }
        if (!empty($legacy['client']) && !empty($legacy['slot'])) {
            return [
                'rendered' => iN_render_adsense_slot($legacy['client'], $legacy['slot'], $legacy['size'] ?? 'responsive', 'inline'),
                'frequency' => isset($legacy['frequency']) ? (int)$legacy['frequency'] : 0,
                'offset' => isset($legacy['offset']) ? (int)$legacy['offset'] : 0,
                'provider' => 'Google AdSense'
            ];
        }
        return ['rendered' => '', 'frequency' => 0, 'offset' => 0, 'provider' => ''];
    }
}
