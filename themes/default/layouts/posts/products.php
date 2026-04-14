<?php
$productViews = $iN->iN_TotalProductSeen($ProductID);
$productSales = $iN->iN_TotalProductSell($ProductID);
?>
<div class="s_p_product_container i_post_body body_<?php echo iN_HelpSecure($ProductID); ?>" id="<?php echo iN_HelpSecure($ProductID); ?>" data-last="<?php echo iN_HelpSecure($ProductID); ?>">
    <a href="<?php echo iN_HelpSecure($SlugUrl); ?>">
        <div class="s_p_product_wrapper marketplace-card">
            <div class="product_image flex_ tabing">
                <div class="s_p_product_type image_tag <?php echo iN_HelpSecure($p__style); ?>"><?php echo iN_HelpSecure($LANG[$ProductType]); ?></div>
                <img class="timp" src="<?php echo iN_HelpSecure($productDataImage); ?>" alt="<?php echo iN_HelpSecure($ProductName); ?>">
            </div>
            <div class="s_p_details">
                <div class="s_p_title" title="<?php echo iN_HelpSecure($ProductName); ?>"><?php echo iN_HelpSecure($ProductName); ?></div>
                <div class="s_p_submeta">
                    <span class="s_p_chip soft">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10')); ?>
                        <?php echo iN_HelpSecure($productViews) . ' ' . iN_HelpSecure($LANG['product_views'] ?? 'Views'); ?>
                    </span>
                    <span class="s_p_chip soft">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('155')); ?>
                        <?php echo iN_HelpSecure($productSales) . ' ' . iN_HelpSecure($LANG['pp_sales']); ?>
                    </span>
                </div>
                <div class="s_p_meta">
                    <div class="s_p_price marketplace-price"><?php echo iN_HelpSecure(formatCurrency($ProductPrice, $defaultCurrency)); ?></div>
                </div>
            </div>
        </div>
    </a>
</div>
