<div class="other_items_by_owner">
    <div class="other_items_by_owner_title"><?php echo preg_replace( '/{.*?}/', "<a href='".$base_url.$userProdocutOwnerUsername."'>@".$userProductOwnerFullName."</a>", $LANG['suggest_product_title']);?></div>
    <div class="i_other_products_container flex_ tabing_non_justify">
       <!--Product-->
       <?php
          $otherProducts = $iN->iN_OtherProductsByUserID($ProductOwnerID);
          if($otherProducts){
             $rootPath = realpath(__DIR__ . '/../../../..');
             foreach($otherProducts as $oprod){
                $sProductID = $oprod['pr_id'];
                $sProductName = $oprod['pr_name'];
                $sProductPrice = $oprod['pr_price'];
                $sProductFiles = $oprod['pr_files'];
                $sProductOwnerID = $oprod['iuid_fk'];
                $sProductSlug = $oprod['pr_name_slug'];
                $sProductType = $oprod['product_type'];
                $p__style = $sProductType;
                if($sProductType == 'scratch'){
                    $sProductType = 'simple_product';
                    $p__style = 'scratch';
                }
                $sProductSlotsNumber = $oprod['pr_slots_number'];
                $sSlugUrl = $base_url.'product/'.$sProductSlug.'_'.$sProductID;
                $strimValue = rtrim($sProductFiles,',');
                $snums = preg_split('/\s*,\s*/', $strimValue);
                $slastFileID = end($snums);
                $spfData = $iN->iN_GetUploadedFileDetails($slastFileID);
                $sproductDataImage = '';
                if($spfData){
                    $sfileUploadID = $spfData['upload_id'];
                    $sfileExtension = $spfData['uploaded_file_ext'];
                    $sfilePath = $spfData['uploaded_file_path'];
                    $sfileThumb = $spfData['upload_tumbnail_file_path'];
                    $preferredPath = $sfileThumb;
                    $pathInfo = pathinfo($sfilePath);
                    $dir = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
                    $filename = isset($pathInfo['filename']) ? $pathInfo['filename'] : '';
                    $ext = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
                    $derivedThumbs = [];
                    if ($dir && $filename) {
                        $derivedThumbs[] = $dir . '/' . $filename . '_thumb_' . $sProductOwnerID . '.jpg';
                        $derivedThumbs[] = $dir . '/' . $filename . '_thumb_' . $sProductOwnerID . '.png';
                    }
                    $candidates = array_filter(array_merge([$sfileThumb], $derivedThumbs, [$sfilePath]));
                    foreach ($candidates as $cand) {
                        $preferredPath = $cand;
                        if (!$rootPath) { break; }
                        $abs = $rootPath . '/' . ltrim($cand, '/');
                        if (is_file($abs)) { break; }
                    }
                    if (function_exists('storage_public_url')) {
                        $sproductDataImage = storage_public_url($preferredPath);
                    } else {
                        $sproductDataImage = $base_url . $preferredPath;
                    }
                }
                $sProductViews = $iN->iN_TotalProductSeen($sProductID);
                $sProductSales = $iN->iN_TotalProductSell($sProductID);
        ?>
        <div class="s_p_product_container" id="<?php echo iN_HelpSecure($sProductID);?>">
            <a href="<?php echo $sSlugUrl;?>">
                <div class="s_p_product_wrapper marketplace-card">
                    <div class="product_image flex_ tabing">
                        <div class="s_p_product_type image_tag <?php echo $p__style;?>"><?php echo iN_HelpSecure($LANG[$sProductType]);?></div>
                        <img class="timp" src="<?php echo $sproductDataImage;?>">
                    </div>
                    <div class="s_p_details">
                        <div class="s_p_title" title="<?php echo iN_HelpSecure($sProductName);?>"><?php echo iN_HelpSecure($sProductName);?></div>
                        <div class="s_p_submeta">
                            <span class="s_p_chip soft">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10'));?>
                                <?php echo iN_HelpSecure($sProductViews).' '.iN_HelpSecure($LANG['product_views'] ?? 'Views'); ?>
                            </span>
                            <span class="s_p_chip soft">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('155'));?>
                                <?php echo iN_HelpSecure($sProductSales).' '.iN_HelpSecure($LANG['pp_sales']); ?>
                            </span>
                        </div>
                        <div class="s_p_meta">
                            <div class="s_p_price marketplace-price"><?php echo iN_HelpSecure(formatCurrency($sProductPrice, $defaultCurrency));?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php }
        }
        ?>
       <!--Products-->
    </div>
</div>
