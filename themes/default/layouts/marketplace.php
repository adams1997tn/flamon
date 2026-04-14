<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo iN_HelpSecure($siteTitle);?></title>
    <?php
       include("header/meta.php");
       include("header/css.php");
       include("header/javascripts.php");
    ?>
</head>
<?php
$marketplaceUserId = isset($userID) ? (int) $userID : 0;
switch ($pageCategory) {
    case 'all':
        $pageIcon = $iN->iN_SelectedMenuIcon('158');
        break;
    case 'bookazoom':
        $pageIcon = $iN->iN_SelectedMenuIcon('160');
        break;
    case 'digitaldownload':
        $pageIcon = $iN->iN_SelectedMenuIcon('161');
        break;
    case 'liveeventticket':
        $pageIcon = $iN->iN_SelectedMenuIcon('162');
        break;
    case 'artcommission':
        $pageIcon = $iN->iN_SelectedMenuIcon('163');
        break;
    case 'joininstagramclosefriends':
        $pageIcon = $iN->iN_SelectedMenuIcon('164');
        break;
    default:
        $pageIcon = $iN->iN_SelectedMenuIcon('158'); // Default icon
        break;
}
$categoryData = ($pageCategory !== 'all') ? $pageCategory : '';
?>
<body>
<?php if($logedIn == 0){ include('login_form.php'); }?>
<?php include("header/header.php");?>
    <div class="wrapper shop_menu_wrapper">
        <!--Market Left Menu-->
         <div class="shopping_left_menu">
             <!---->
             <div class="settings_mobile_ope_menu">
                <div class="settings_mobile_menu_container transition flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('100')).iN_HelpSecure($LANG['shop_categories']);?></div>
             </div>
             <!---->
             <div class="i_shopping_menu_wrapper">
                 <div class="i_shop_title"><?php echo iN_HelpSecure($LANG['shop_categories']);?></div>
                    <div class="i_sh_menus">
                        <div class="i_sh_menu_wrapper">
                            <a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=all">
                                <div class="i_sp_menu_box transition <?php echo iN_HelpSecure($pageCategory) == 'all' ? "active_p" : ""; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158'));?> <?php echo iN_HelpSecure($LANG['all_products']);?>
                                </div>
                            </a>
                            <?php if($iN->iN_ShopData($marketplaceUserId, 3) == 'yes'){?>
                            <a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=bookazoom">
                                <div class="i_sp_menu_box transition <?php echo iN_HelpSecure($pageCategory) == 'bookazoom' ? "active_p" : ""; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('160'));?> <?php echo iN_HelpSecure($LANG['bookazoom']);?>
                                </div>
                            </a>
                            <?php }?>
                            <?php if($iN->iN_ShopData($marketplaceUserId, 4) == 'yes'){?>
                            <a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=digitaldownload">
                                <div class="i_sp_menu_box transition <?php echo iN_HelpSecure($pageCategory) == 'digitaldownload' ? "active_p" : ""; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('161'));?> <?php echo iN_HelpSecure($LANG['digitaldownload']);?>
                                </div>
                            </a>
                            <?php }?>
                            <?php if($iN->iN_ShopData($marketplaceUserId, 5) == 'yes'){?>
                            <a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=liveeventticket">
                                <div class="i_sp_menu_box transition <?php echo iN_HelpSecure($pageCategory) == 'liveeventticket' ? "active_p" : ""; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('162'));?> <?php echo iN_HelpSecure($LANG['liveeventticket']);?>
                                </div>
                            </a>
                            <?php }?>
                            <?php if($iN->iN_ShopData($marketplaceUserId, 6) == 'yes'){?>
                            <a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=artcommission">
                                <div class="i_sp_menu_box transition <?php echo iN_HelpSecure($pageCategory) == 'artcommission' ? "active_p" : ""; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('163'));?> <?php echo iN_HelpSecure($LANG['artcommission']);?>
                                </div>
                            </a>
                            <?php }?>
                            <?php if($iN->iN_ShopData($marketplaceUserId, 7) == 'yes'){?>
                            <a href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=joininstagramclosefriends">
                                <div class="i_sp_menu_box transition <?php echo iN_HelpSecure($pageCategory) == 'joininstagramclosefriends' ? "active_p" : ""; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('164'));?> <?php echo iN_HelpSecure($LANG['joininstagramclosefriends']);?>
                                </div>
                            </a>
                            <?php }?>
                        </div>
                    </div>
             </div>
         </div>
        <!--/Market Left Menu-->
        <!--Shopping Container-->
        <div class="shop_main_wrapper">
            <div class="marketplace-hero">
                <div class="marketplace-hero__content">
                    <div class="marketplace-hero__icon">
                        <?php echo html_entity_decode($pageIcon);?>
                    </div>
                    <div class="marketplace-hero__text">
                        <div class="marketplace-hero__eyebrow"><?php echo iN_HelpSecure($LANG['marketplace']);?></div>
                        <div class="marketplace-hero__title"><?php echo $pageCategory == 'all' ? iN_HelpSecure($LANG['all_products']) : iN_HelpSecure($LANG[$pageCategory]);?></div>
                        <div class="marketplace-hero__subtitle"><?php echo iN_HelpSecure($LANG['shop_categories']);?> · <?php echo iN_HelpSecure($LANG['marketplace']);?></div>
                    </div>
                </div>
                <div class="marketplace-hero__highlights">
                    <div class="hero-highlight">
                        <span class="pulse-dot"></span>
                        <?php echo iN_HelpSecure($LANG['all_products']);?>
                    </div>
                    <div class="hero-highlight alt">
                        <span class="pulse-dot alt"></span>
                        <?php echo iN_HelpSecure($LANG['shop_categories']);?>
                    </div>
                </div>
            </div>
            <div class="shop_main_wrapper_container">
                <div class="nonePoint" id="moreTypeContainer" data-moretype="<?php echo iN_HelpSecure($categoryData); ?>"></div>
                <div class="ishopping_wrapper_in flex_ tabing marketplace-grid" id="moreType">
                    <?php
                    $lastPostID = isset($_POST['last']) ? $_POST['last'] : '';
                    $productData = $iN->iN_AllUserProductPosts($categoryData, $lastPostID, $showingNumberOfPost);
                    $rootPath = realpath(__DIR__ . '/../../..');
                    if($productData){
                        foreach($productData as $oprod){
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
                            if($spfData){
                                $sfileUploadID = $spfData['upload_id'];
                                $sfileExtension = $spfData['uploaded_file_ext'];
                                $sfilePath = $spfData['uploaded_file_path'];
                                $sfileThumb = $spfData['upload_tumbnail_file_path'];
                                // Prefer thumbnail; fallback to derived thumb names, then original
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
                    <div class="s_p_product_container mor inline_block" data-last="<?php echo iN_HelpSecure($sProductID);?>" id="<?php echo iN_HelpSecure($sProductID);?>">
                        <a href="<?php echo iN_HelpSecure($sSlugUrl);?>">
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
                    <?php }}else{ ?>
                        <div class="s_p_product_container nmr dcontent">
                            <div class="marketplace-card marketplace-empty-card">
                                <div class="marketplace-empty-card__badge"><?php echo iN_HelpSecure($LANG['marketplace']);?></div>
                                <div class="marketplace-empty-card__body">
                                    <div class="marketplace-empty-card__icon">
                                        <?php echo html_entity_decode($pageIcon);?>
                                    </div>
                                    <div class="marketplace-empty-card__title"><?php echo iN_HelpSecure($LANG['no_product_in_this_category']);?></div>
                                    <div class="marketplace-empty-card__subtitle"><?php echo iN_HelpSecure($LANG['shop_categories']);?> · <?php echo iN_HelpSecure($LANG['marketplace']);?></div>
                                    <a class="marketplace-empty-card__cta" href="<?php echo iN_HelpSecure($base_url);?>marketplace?cat=all">
                                        <?php echo iN_HelpSecure($LANG['all_products']);?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php }?>
                </div>
                <div id="marketplaceSentinel" class="marketplace-sentinel" aria-hidden="true"></div>
            </div>
        </div>
        <!--/Shopping Container-->
    </div>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/shopPageHandler.js"></script>
</body>
</html>
