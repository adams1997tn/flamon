<div class="i_modal_bg_in" role="dialog" aria-modal="true" aria-labelledby="livePinProductTitle">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="livePinProductTitle">
                <?php echo iN_HelpSecure($LANG['live_pin_product']); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="i_more_text_wrapper">
                <div class="live_pin_product_list">
                    <?php if (!empty($products)) { ?>
                        <?php foreach ($products as $product) { ?>
                            <?php
                            $productId = $product['pr_id'] ?? null;
                            $productName = $product['pr_name'] ?? '';
                            $productPrice = $product['pr_price'] ?? '';
                            $productFiles = $product['pr_files'] ?? '';
                            $productSlug = $product['pr_name_slug'] ?? '';
                            $productType = $product['product_type'] ?? '';
                            $productTypeLabel = $productType === 'scratch' ? 'simple_product' : $productType;
                            $productImage = '';
                            if (!empty($productFiles)) {
                                $trimValue = rtrim($productFiles, ',');
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
                            <div class="live_pin_product_item">
                                <div class="live_pin_product_media">
                                    <?php if (!empty($productImage)) { ?>
                                        <img src="<?php echo iN_HelpSecure($productImage); ?>" alt="<?php echo iN_HelpSecure($productName); ?>">
                                    <?php } else { ?>
                                        <div class="live_pin_product_placeholder"><?php echo iN_HelpSecure($LANG['live_offer']); ?></div>
                                    <?php } ?>
                                </div>
                                <div class="live_pin_product_meta">
                                    <div class="live_pin_product_type">
                                        <?php echo iN_HelpSecure($LANG[$productTypeLabel] ?? $productTypeLabel); ?>
                                    </div>
                                    <div class="live_pin_product_name"><?php echo iN_HelpSecure($productName); ?></div>
                                    <div class="live_pin_product_price"><?php echo iN_HelpSecure(formatCurrency($productPrice, $defaultCurrency)); ?></div>
                                </div>
                                <div class="live_pin_product_actions">
                                    <button type="button" class="live_pin_select transition" data-product-id="<?php echo iN_HelpSecure($productId); ?>" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
                                        <?php echo iN_HelpSecure($LANG['live_pin_product_action']); ?>
                                    </button>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="live_pin_product_empty"><?php echo iN_HelpSecure($LANG['live_pin_product_empty']); ?></div>
                    <?php } ?>
                </div>
            </div>
            <div class="i_block_box_footer_container">
                <div class="alertBtnLeft no-del transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
