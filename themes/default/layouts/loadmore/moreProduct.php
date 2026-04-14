<?php
if (!empty($productData)) {
    foreach ($productData as $oprod) {
        $sProductID = $oprod['pr_id'];
        $sProductName = $oprod['pr_name'];
        $sProductPrice = $oprod['pr_price'];
        $sProductFiles = $oprod['pr_files'];
        $sProductOwnerID = $oprod['iuid_fk'];
        $sProductSlug = $oprod['pr_name_slug'];
        $sProductType = $oprod['product_type'];
        $p__style = $sProductType;

        if ($sProductType === 'scratch') {
            $sProductType = 'simple_product';
            $p__style = 'scratch';
        }

        $sProductSlotsNumber = $oprod['pr_slots_number'];
        $sSlugUrl = $base_url . 'product/' . $sProductSlug . '_' . $sProductID;
        $strimValue = rtrim($sProductFiles, ',');
        $snums = preg_split('/\s*,\s*/', $strimValue);
        $slastFileID = end($snums);
        $sproductDataImage = '';

        $spfData = $iN->iN_GetUploadedFileDetails($slastFileID);
        $rootPath = isset($rootPath) ? $rootPath : realpath(__DIR__ . '/../../..');
        if ($spfData) {
            $sfileExtension = $spfData['uploaded_file_ext'];
            $sfilePath = $spfData['uploaded_file_path'];
            $sThumbPath = $spfData['upload_tumbnail_file_path'];
            // Prefer thumbnail; fallback to derived thumb names, then original
            $pathInfo = pathinfo($sfilePath);
            $dir = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
            $filename = isset($pathInfo['filename']) ? $pathInfo['filename'] : '';
            $derivedThumbs = [];
            if ($dir && $filename) {
                $derivedThumbs[] = $dir . '/' . $filename . '_thumb_' . $sProductOwnerID . '.jpg';
                $derivedThumbs[] = $dir . '/' . $filename . '_thumb_' . $sProductOwnerID . '.png';
            }
            $candidates = array_filter(array_merge([$sThumbPath], $derivedThumbs, [$sfilePath]));
            $finalPath = $sfilePath;
            foreach ($candidates as $cand) {
                $finalPath = $cand;
                if (!$rootPath) { break; }
                $abs = $rootPath . '/' . ltrim($cand, '/');
                if (is_file($abs)) { break; }
            }
            if (function_exists('storage_public_url')) {
                $sproductDataImage = storage_public_url($finalPath);
            } else {
                $sproductDataImage = $base_url . $finalPath;
            }
        }
        $sProductViews = $iN->iN_TotalProductSeen($sProductID);
        $sProductSales = $iN->iN_TotalProductSell($sProductID);
        ?>

        <div class="s_p_product_container mor inline_block" data-last="<?php echo iN_HelpSecure($sProductID); ?>" id="<?php echo iN_HelpSecure($sProductID); ?>">
            <a href="<?php echo iN_HelpSecure($sSlugUrl); ?>">
                <div class="s_p_product_wrapper marketplace-card">
                    <div class="product_image flex_ tabing">
                        <div class="s_p_product_type image_tag <?php echo iN_HelpSecure($p__style); ?>">
                            <?php echo iN_HelpSecure($LANG[$sProductType]); ?>
                        </div>
                        <img class="timp" src="<?php echo iN_HelpSecure($sproductDataImage); ?>" alt="<?php echo iN_HelpSecure($sProductName); ?>">
                    </div>
                    <div class="s_p_details">
                        <div class="s_p_title" title="<?php echo iN_HelpSecure($sProductName); ?>">
                            <?php echo iN_HelpSecure($sProductName); ?>
                        </div>
                        <div class="s_p_submeta">
                            <span class="s_p_chip soft">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10'));?>
                                <?php echo iN_HelpSecure($sProductViews) . ' ' . iN_HelpSecure($LANG['product_views'] ?? 'Views'); ?>
                            </span>
                            <span class="s_p_chip soft">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('155'));?>
                                <?php echo iN_HelpSecure($sProductSales) . ' ' . iN_HelpSecure($LANG['pp_sales']); ?>
                            </span>
                        </div>
                        <div class="s_p_meta">
                            <div class="s_p_price marketplace-price">
                                <?php echo iN_HelpSecure(formatCurrency($sProductPrice, $defaultCurrency)); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <?php
    }
} else {
    ?>
    <div class="s_p_product_container nmr i_display_content">
        <div class="marketplace-card marketplace-empty-card">
            <div class="marketplace-empty-card__badge"><?php echo iN_HelpSecure($LANG['marketplace']); ?></div>
            <div class="marketplace-empty-card__body">
                <div class="marketplace-empty-card__icon">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?>
                </div>
                <div class="marketplace-empty-card__title"><?php echo iN_HelpSecure($LANG['no_more_product_shown']); ?></div>
                <div class="marketplace-empty-card__subtitle"><?php echo iN_HelpSecure($LANG['shop_categories']); ?> · <?php echo iN_HelpSecure($LANG['marketplace']); ?></div>
                <a class="marketplace-empty-card__cta" href="<?php echo iN_HelpSecure($base_url); ?>marketplace?cat=all">
                    <?php echo iN_HelpSecure($LANG['all_products']); ?>
                </a>
            </div>
        </div>
    </div>
<?php
}
?>
