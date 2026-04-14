<div class="product_page_shell">
<?php
    $productData = $iN->iN_GetProductDetailsByID($GetTheProductIDFromUrl);
    if($productData){
        $uProductID = $productData['pr_id'];
        $checkProductPurchasedBefore = '';
        if($logedIn == 1){
            $checkProductPurchasedBefore = $iN->iN_CheckItemPurchasedBefore($userID, $uProductID);
        }
        $visitorIp = $iN->iN_GetIPAddress();
        if(!empty($visitorIp) && isset($visitorIp) && $visitorIp != ''){
            $lUserID = isset($userID) ? $userID : NULL;
            $iN->iN_InsertVisitor($visitorIp,$uProductID,$lUserID);
        }
        $uProductName = $productData['pr_name'] ?? null;
        $uProductPrice = $productData['pr_price'] ?? null;
        $uProductFiles = $productData['pr_files'] ?? null;
        $uProductDownloadableFiles = $productData['pr_downlodable_files'] ?? null;
        $uProductDescription = $productData['pr_desc'] ?? null;
        $uProductDescriptionInfo = $productData['pr_desc_info'] ?? null;
        $uProductTime = $productData['pr_created_time'] ?? null;
        $uProductOwnerID = $productData['iuid_fk'] ?? null;
        $uProductStatus = $productData['pr_status'] ?? null;
        $uProductSeenTime = $productData['pr_seen_time'] ?? null;
        $uProductNumberOfSales = $productData['pr_number_of_sales'] ?? null;
        $uProductSlug = $productData['pr_name_slug'] ?? null;
        $uProductType = $productData['product_type'] ?? null;
        $p__style = $uProductType;
        $thisProduct = $uProductType;
        if($uProductType == 'scratch'){
            $uProductType = 'simple_product';
            $p__style = 'scratch';
            $thisProduct = 'all';
        }
        $uProductSlotsNumber = $productData['pr_slots_number'] ?? null;
        $uPTime = date('Y-m-d H:i:s',$uProductTime);
        $uSlugUrl = $base_url.'product/'.$uProductSlug.'_'.$uProductID;
        $userProdocutOwnerUsername = $productData['i_username'] ?? null;
        $userPostOwnerUserFullName = $productData['i_user_fullname'] ?? null;
        $userPostOwnerUserGender = $productData['user_gender'] ?? null;
        $userProfileFrame = $productData['user_frame'] ?? null;
        if($fullnameorusername == 'no'){
            $userPostOwnerUserFullName = $userProdocutOwnerUsername;
         }
        $userPostOwnerUserAvatar = $iN->iN_UserAvatar($uProductOwnerID, $base_url);
        $userPostUserVerifiedStatus = $productData['user_verified_status'] ?? null;
        if($userPostOwnerUserGender == 'male'){
            $publisherGender = '<div class="i_plus_g">'.$iN->iN_SelectedMenuIcon('12').'</div>';
        }else if($userPostOwnerUserGender == 'female'){
            $publisherGender = '<div class="i_plus_gf">'.$iN->iN_SelectedMenuIcon('13').'</div>';
        }else if($userPostOwnerUserGender == 'couple'){
            $publisherGender = '<div class="i_plus_g">'.$iN->iN_SelectedMenuIcon('58').'</div>';
        }
        $userVerifiedStatus = '';
        if($userPostUserVerifiedStatus == '1'){
            $userVerifiedStatus = $iN->iN_SelectedMenuIcon('11');
        }
        $rootPath = realpath(__DIR__ . '/../../..');
        $buildUrl = function($path) use ($base_url) {
            if (function_exists('storage_public_url')) {
                return storage_public_url($path);
            }
            return $base_url . $path;
        };
        $heroIcon = $iN->iN_SelectedMenuIcon('158');
        switch($thisProduct){
            case 'bookazoom':
                $heroIcon = $iN->iN_SelectedMenuIcon('160');
                break;
            case 'digitaldownload':
                $heroIcon = $iN->iN_SelectedMenuIcon('161');
                break;
            case 'liveeventticket':
                $heroIcon = $iN->iN_SelectedMenuIcon('162');
                break;
            case 'artcommission':
                $heroIcon = $iN->iN_SelectedMenuIcon('163');
                break;
            case 'joininstagramclosefriends':
                $heroIcon = $iN->iN_SelectedMenuIcon('164');
                break;
            default:
                $heroIcon = $iN->iN_SelectedMenuIcon('158');
        }
    ?>
<div class="marketplace-hero product-market-hero">
    <div class="marketplace-hero__content">
        <div class="marketplace-hero__icon">
            <?php echo html_entity_decode($heroIcon); ?>
        </div>
        <div class="marketplace-hero__text">
            <div class="marketplace-hero__eyebrow"><?php echo iN_HelpSecure($LANG['marketplace'] ?? 'Marketplace'); ?></div>
            <div class="marketplace-hero__title"><?php echo iN_HelpSecure($uProductName); ?></div>
            <div class="marketplace-hero__subtitle"><?php echo iN_HelpSecure($LANG['shop_categories'] ?? 'Categories'); ?> · <?php echo iN_HelpSecure($LANG[$uProductType]); ?></div>
        </div>
    </div>
    <div class="marketplace-hero__highlights">
        <a class="s_p_chip soft" href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=all"><?php echo iN_HelpSecure($LANG['all_products']);?></a>
        <a class="s_p_chip soft highlight" href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=<?php echo iN_HelpSecure($thisProduct);?>"><?php echo iN_HelpSecure($LANG[$uProductType]);?></a>
    </div>
</div>
<div class="product_wrapper flex_ product_page_grid">
<div class="product_details_left">
    <div class="product-left-card">
    <div class="product_images_container">
        <!----->
        <?php
            $trimValue = rtrim($uProductFiles, ',');
            $explodeFiles = explode(',', $trimValue);
            $explodeFiles = array_unique($explodeFiles);
            $countExplodedFiles = $iN->iN_CheckCountFile($uProductFiles);
            foreach ($explodeFiles as $pFile) {
                $fileData = $iN->iN_GetUploadedFileDetails($pFile);
                if($fileData){
                    $fileUploadID = $fileData['upload_id'] ?? null;
                    $fileExtension = $fileData['uploaded_file_ext'] ?? null;
                    $filePath = $fileData['uploaded_file_path'] ?? null;
                    $filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? null;
                    if($fileExtension == 'mp4'){
                        $posterPath = !empty($filePathTumbnail) ? $filePathTumbnail : $filePath;
                        $tumbFile = $buildUrl($posterPath);
                        $filePathUrl = $buildUrl($filePath);
                        echo '
                        <div class="nonePoint" id="video' . $fileUploadID . '">
                            <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls preload="none">
                                <source src="' . $filePathUrl . '" type="video/mp4">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                        ';
                        $srcPath = 'data-poster="' . $tumbFile . '" data-html="#video' . $fileUploadID . '"';
                    }
                }
            }
        ?>
        <!----->
        <!-- Swiper -->
        <div class="swiper mySwiper">
            <div class="swiper-wrapper" id="swiperGallery<?php echo iN_HelpSecure($uProductID);?>" data-standalone-gallery="true">
        <?php
            $trimValue = rtrim($uProductFiles, ',');
            $explodeFiles = explode(',', $trimValue);
            $explodeFiles = array_unique($explodeFiles);
            $countExplodedFiles = $iN->iN_CheckCountFile($uProductFiles);
            foreach ($explodeFiles as $pFile) {
                $fileData = $iN->iN_GetUploadedFileDetails($pFile);
                    if($fileData){
                        $fileUploadID = $fileData['upload_id'] ?? null;
                        $fileExtension = $fileData['uploaded_file_ext'] ?? null;
                        $filePath = $fileData['uploaded_file_path'] ?? null;
                        $filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? null;
                    if($fileExtension == 'mp4'){
                        $posterPath = !empty($filePathTumbnail) ? $filePathTumbnail : $filePath;
                        $tumbFile = $buildUrl($posterPath);
                        $filePathUrl = $buildUrl($filePath);

                        $srcPath = 'data-poster="' . $tumbFile . '" data-html="#video' . $fileUploadID . '"';
                    }else{
                        // Prefer original image; if missing, fallback to thumbnail
                        $displayPath = $filePath;
                        $thumbCandidate = !empty($filePathTumbnail) ? $filePathTumbnail : null;
                        $absOriginal = $rootPath ? $rootPath . '/' . ltrim($displayPath, '/') : null;
                        $absThumb = ($rootPath && $thumbCandidate) ? $rootPath . '/' . ltrim($thumbCandidate, '/') : null;
                        if ($thumbCandidate && $absOriginal && !is_file($absOriginal) && is_file($absThumb)) {
                            $displayPath = $thumbCandidate;
                        }
                        $tumbFile = $buildUrl($displayPath);
                        $filePathUrl = $buildUrl($displayPath);
                        $srcPath = 'data-src="' . $filePathUrl . '"';
                    }
        ?>
            <div class="swiper-slide">
              <?php if ($fileExtension == 'mp4') { ?>
                <a href="#" data-html="#video<?php echo $fileUploadID; ?>" data-poster="<?php echo $tumbFile; ?>">
                  <div class="swiper-img flex_ tabing">
                    <img class="timp" src="<?php echo $tumbFile; ?>">
                  </div>
                </a>
              <?php } else { ?>
                <a href="<?php echo $filePathUrl; ?>">
                  <div class="swiper-img flex_ tabing">
                    <img class="timp" src="<?php echo $tumbFile; ?>">
                  </div>
                </a>
              <?php } ?>
            </div>
        <?php }}?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <!-- /Swiper -->
    </div>
     
    <!--Product Description-->
    <div class="product_p_description card-block">
        <div class="product__description"><?php echo iN_HelpSecure($LANG['description']);?></div>
        <div class="product__d_all">
            <?php echo $urlHighlight->highlightUrls($iN->sanitize_output($iN->iN_RemoveYoutubelink($uProductDescription), $base_url));?>
        </div>
        <div class="product-share-row flex_ tabing_non_justify">
            <div class="s_social flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('169')).iN_HelpSecure($LANG['share_on_social']);?></div>
            <div class="flex_ tabing product_margin_left">
              <div class="on_s flex_ tabing share-btn" data-social="facebook" data-url="<?php echo iN_HelpSecure($uSlugUrl); ?>" data-id="2">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('90')); ?>
              </div>
              <div class="on_s flex_ tabing share-btn" data-social="twitter" data-url="<?php echo iN_HelpSecure($uSlugUrl); ?>" data-id="2">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('34')); ?>
              </div>
              <div class="on_s flex_ tabing share-btn" data-social="whatsapp" data-url="<?php echo iN_HelpSecure($uSlugUrl); ?>" data-id="2">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('147')); ?>
              </div>
            </div>
        </div>
    </div>
    <!--/Product Description-->
    </div>
</div>
<div class="product_details_right">
    <div class="product_details_right_in">
        <div class="product-hero-card">
            <div class="product-hero-top flex_ tabing_non_justify">
                <div class="s_p_product_type hero_tag <?php echo $p__style;?>"><a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=<?php echo iN_HelpSecure($thisProduct);?>"><?php echo iN_HelpSecure($LANG[$uProductType]);?></a></div>
                <div class="product-price-hero marketplace-price"><?php echo iN_HelpSecure(formatCurrency($uProductPrice, $defaultCurrency));?></div>
            </div>
            <h1 class="product-hero-title"><?php echo iN_HelpSecure($uProductName);?></h1>
            <div class="product-hero-owner flex_ tabing_non_justify">
                <a class="flex_ tabing_non_justify" href="<?php echo iN_HelpSecure($base_url.$userProdocutOwnerUsername);?>">
                    <div class="hero-avatar">
                        <?php if($userProfileFrame){ ?>
                            <div class="frame_out_container_product"><div class="frame_container_product"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
                        <?php }?>
                        <img src="<?php echo iN_HelpSecure($userPostOwnerUserAvatar);?>" alt="<?php echo iN_HelpSecure($userPostOwnerUserFullName);?>">
                    </div>
                    <div class="hero-owner-meta">
                        <div class="i_unm_product flex_">
                            <?php echo iN_HelpSecure($userPostOwnerUserFullName); ?>
                            <?php echo html_entity_decode($userVerifiedStatus); ?>
                        </div>
                        <div class="i_see_prof"><?php echo TimeAgo::ago($uPTime, date('Y-m-d H:i:s')); ?></div>
                    </div>
                </a>
            </div>
            <div class="product-hero-stats">
                <span class="s_p_chip soft">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10'));?>
                    <?php echo iN_HelpSecure($iN->iN_TotalProductSeen($uProductID)).' '.iN_HelpSecure($LANG['product_views'] ?? 'Views'); ?>
                </span>
                <span class="s_p_chip soft">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('155'));?>
                    <?php echo iN_HelpSecure($iN->iN_TotalProductSell($uProductID)).' '.iN_HelpSecure($LANG['pp_sales']);?>
                </span>
            </div>
            <div class="product-hero-actions">
                <div class="buy_my_product">
                     <div class="buy__myproduct tabing flex_ <?php if($logedIn == 0){echo 'loginForm';};?>" data-id="<?php echo iN_HelpSecure($uProductID);?>"><?php echo $iN->iN_SelectedMenuIcon('155').$LANG['buy_now'];?></div>
                </div>
                <?php if($checkProductPurchasedBefore && $logedIn == 1){?>
                <div class="s_p_p_before flex_ tabing_non_justify">
                   <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('60'));?> <?php echo iN_HelpSecure($LANG['already_purchased_product']);?>
                </div>
                <?php }?>
                <?php if($checkProductPurchasedBefore && $logedIn == 1 && !empty($uProductDownloadableFiles)){?>
                    <div class="s_p_p_p_download flex_ tabing" data-id="<?php echo iN_HelpSecure($uProductID);?>">
                        <a href="<?php echo $base_url;?>product/<?php echo iN_HelpSecure($uProductSlug).'_'.$uProductID.'-'.$uProductID;?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('170')).iN_HelpSecure($LANG['download_your_file']);?>
                        </a>
                    </div>
                <?php }?>
                <?php if($checkProductPurchasedBefore && $logedIn == 1){?>
                <div class="s_p_live_not">
                    <div class="owner_not"><?php echo iN_HelpSecure($LANG['sellers_note']);?></div>
                    <div class="owner_not_text">
                        <?php echo $urlHighlight->highlightUrls($iN->sanitize_output($iN->iN_RemoveYoutubelink($uProductDescriptionInfo), $base_url));?>
                    </div>
                </div>
                <?php }?>
            </div>
        </div>
    </div>
    <?php if($logedIn == 1 && $uProductOwnerID == $userID){?>
    <!--Product Page Extra for Creator-->
    <div class="product_details_right_in_top">
        <div class="add_new_product flex_ tabing"><a class="flex_ tabing" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=createaProduct"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('39'));?><?php echo iN_HelpSecure($LANG['add_new_product']);?></a></div>
        <div class="ed_del_prod flex_ tabing_non_justify">
            <div class="edit_prod"><a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=myProducts&editProduct=<?php echo iN_HelpSecure($uProductID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27'));?><?php echo iN_HelpSecure($LANG['edit_product']);?></a></div>
            <div class="del_prod delmyprod" id="<?php echo iN_HelpSecure($uProductID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28'));?><?php echo iN_HelpSecure($LANG['delete_product']);?></div>
        </div>
    </div>
    <!--/Product Page Extra for Creator-->
<?php }?>
</div>
<?php if($logedIn == 1 && $uProductOwnerID == $userID){?>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/deleteProductHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<?php }?>
</div>
<?php }else{ ?>
    <div class="i_not_found_page transition i_centered">
        <div class="noPostIcon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54'));?></div>
        <h1><?php echo iN_HelpSecure($LANG['empty_shared_title']);?></h1>
        <?php echo iN_HelpSecure($LANG['empty_shared_desc']);?>
    </div>
<?php } ?>
</div>
