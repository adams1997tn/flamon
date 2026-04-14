<?php
$linkPreviewUrl = isset($linkPreviewUrl) ? trim((string) $linkPreviewUrl) : '';
$linkPreviewTitle = isset($linkPreviewTitle) ? trim((string) $linkPreviewTitle) : '';
$linkPreviewDomain = isset($linkPreviewDomain) ? trim((string) $linkPreviewDomain) : '';
$linkPreviewDescription = isset($linkPreviewDescription) ? trim((string) $linkPreviewDescription) : '';
$linkPreviewImage = isset($linkPreviewImage) ? trim((string) $linkPreviewImage) : '';

if ($linkPreviewUrl === '' || !filter_var($linkPreviewUrl, FILTER_VALIDATE_URL)) {
    return;
}

if ($linkPreviewDomain === '') {
    $linkPreviewDomain = parse_url($linkPreviewUrl, PHP_URL_HOST) ?: '';
}
$linkPreviewDomain = $linkPreviewDomain !== '' ? strtoupper($linkPreviewDomain) : '';

if ($linkPreviewImage !== '' && !filter_var($linkPreviewImage, FILTER_VALIDATE_URL)) {
    $linkPreviewImage = '';
}

$embedCode = '';
if (class_exists('URL_Expand')) {
    $em = new Url_Expand($linkPreviewUrl);
    $embedCode = $em->get_iframe();
    if ($embedCode === '') {
        $embedCode = $em->get_embed();
    }
}
if ($embedCode !== '') {
    echo $embedCode;
    return;
}

if ($linkPreviewTitle === '' && $linkPreviewDescription === '' && $linkPreviewImage === '') {
    $parsedUrl = parse_url($linkPreviewUrl);
    $fallbackTitle = '';
    if (!empty($parsedUrl['path']) && $parsedUrl['path'] !== '/') {
        $fallbackTitle = trim($parsedUrl['path'], '/');
        $fallbackTitle = rawurldecode($fallbackTitle);
        $fallbackTitle = str_replace('-', ' ', $fallbackTitle);
        $fallbackTitle = str_replace('/', ' / ', $fallbackTitle);
        $fallbackTitle = preg_replace('/\s+/', ' ', $fallbackTitle);
    }
    if ($fallbackTitle === '') {
        $fallbackTitle = $parsedUrl['host'] ?? $linkPreviewDomain;
    }
    $linkPreviewTitle = mb_substr(trim($fallbackTitle), 0, 120);
}

if ($linkPreviewTitle === '' && $linkPreviewDescription === '' && $linkPreviewImage === '') {
    return;
}
?>
<div class="i_link_preview">
    <a class="i_link_preview_link" href="<?php echo iN_HelpSecureUrl($linkPreviewUrl); ?>" target="_blank" rel="noopener noreferrer nofollow">
        <?php if ($linkPreviewImage !== '') { ?>
            <div class="i_link_preview_image">
                <img src="<?php echo iN_HelpSecureUrl($linkPreviewImage); ?>" alt="<?php echo iN_HelpSecure($linkPreviewTitle !== '' ? $linkPreviewTitle : $linkPreviewDomain); ?>">
            </div>
        <?php } ?>
        <div class="i_link_preview_body">
            <?php if ($linkPreviewDomain !== '') { ?>
                <div class="i_link_preview_domain"><?php echo iN_HelpSecure($linkPreviewDomain); ?></div>
            <?php } ?>
            <?php if ($linkPreviewTitle !== '') { ?>
                <div class="i_link_preview_title"><?php echo iN_HelpSecure($linkPreviewTitle); ?></div>
            <?php } ?>
            <?php if ($linkPreviewDescription !== '') { ?>
                <div class="i_link_preview_desc"><?php echo iN_HelpSecure($linkPreviewDescription); ?></div>
            <?php } ?>
        </div>
    </a>
</div>
