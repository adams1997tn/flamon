<?php
if (!function_exists('iN_adsense_style_from_size')) {
    function iN_adsense_style_from_size($size) {
        $style = 'display:block;';
        $size = $size ?: 'responsive';
        if ($size === 'responsive') {
            $style .= ' text-align:center;';
        } elseif (preg_match('/^(\\d{2,4})x(\\d{2,4})$/', $size, $m)) {
            $width = (int) $m[1];
            $height = (int) $m[2];
            $style .= ' width:' . $width . 'px; height:' . $height . 'px; margin:0 auto;';
        } else {
            $style .= ' text-align:center;';
        }
        return trim($style);
    }
}

if (!function_exists('iN_render_adsense_slot')) {
    function iN_render_adsense_slot($clientId, $slotId, $size = 'responsive', $position = 'inline') {
        if (empty($clientId) || empty($slotId)) {
            return '';
        }
        $style = iN_adsense_style_from_size($size);
        $attrs = 'data-ad-client="' . iN_HelpSecure($clientId) . '" data-ad-slot="' . iN_HelpSecure($slotId) . '"';
        if ($size === 'responsive') {
            $attrs .= ' data-ad-format="auto" data-full-width-responsive="true"';
        }
        $html = '<div class="adsense_block adsense_' . iN_HelpSecure($position) . '">';
        $html .= '<ins class="adsbygoogle" style="' . iN_HelpSecure($style) . '" ' . $attrs . '></ins>';
        $html .= '<script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>';
        $html .= '</div>';
        return $html;
    }
}
