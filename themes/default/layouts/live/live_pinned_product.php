<?php
$pinnedProductId = $pinnedProduct['pr_id'] ?? null;
$pinnedProductName = $pinnedProduct['pr_name'] ?? '';
$pinnedProductPrice = $pinnedProduct['pr_price'] ?? '';
$pinnedProductFiles = $pinnedProduct['pr_files'] ?? '';
$pinnedProductSlug = $pinnedProduct['pr_name_slug'] ?? '';
$pinnedProductType = $pinnedProduct['product_type'] ?? '';
$productTypeLabel = $pinnedProductType;
if ($productTypeLabel === 'scratch') {
    $productTypeLabel = 'simple_product';
}
$productUrl = $base_url . 'product/' . $pinnedProductSlug . '_' . $pinnedProductId;
$productImage = '';
if (!empty($pinnedProductFiles)) {
    $trimValue = rtrim($pinnedProductFiles, ',');
    $nums = preg_split('/\s*,\s*/', $trimValue);
    $lastFileID = end($nums);
    $pfData = $iN->iN_GetUploadedFileDetails($lastFileID);
    if ($pfData) {
        $filePath = $pfData['uploaded_file_path'] ?? null;
        if ($filePath) {
            if (function_exists('storage_public_url')) {
                $productImage = storage_public_url($filePath);
            } else {
                $productImage = $base_url . $filePath;
            }
        }
    }
}
?>
<div class="live_pinned_product_card" data-product-id="<?php echo iN_HelpSecure($pinnedProductId); ?>">
    <div class="live_pinned_product_media">
        <?php if (!empty($productImage)) { ?>
            <img src="<?php echo iN_HelpSecure($productImage); ?>" alt="<?php echo iN_HelpSecure($pinnedProductName); ?>">
        <?php } else { ?>
            <div class="live_pinned_product_placeholder"><?php echo iN_HelpSecure($LANG['live_offer']); ?></div>
        <?php } ?>
    </div>
    <div class="live_pinned_product_body">
        <div class="live_pinned_product_type">
            <?php echo iN_HelpSecure($LANG[$productTypeLabel] ?? $productTypeLabel); ?>
        </div>
        <div class="live_pinned_product_name"><?php echo iN_HelpSecure($pinnedProductName); ?></div>
        <div class="live_pinned_product_price"><?php echo iN_HelpSecure(formatCurrency($pinnedProductPrice, $defaultCurrency)); ?></div>
        <div class="live_pinned_product_actions">
            <a class="live_pinned_product_cta" href="<?php echo iN_HelpSecure($productUrl); ?>" target="_blank">
                <?php echo iN_HelpSecure($LANG['live_view_offer']); ?>
            </a>
            <?php if (!empty($isLiveCreator)) { ?>
                <button type="button" class="live_unpin_product_btn" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
                    <?php echo iN_HelpSecure($LANG['live_unpin_product']); ?>
                </button>
            <?php } ?>
        </div>
    </div>
</div>
