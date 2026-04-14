<?php
$numberShow = rand(1, $showNumberOfAds);
 $activeAds = $iN->iN_ShowAds($numberShow);
 if($activeAds){
?>
<div class="sp_wrp">
<?php
foreach($activeAds as $aAds){
  $activeAdsTitle = $aAds['ads_title'];
  $activeAdsImage = $aAds['ads_image'];
  $activeAdsUrl = $aAds['ads_url'];
  $activeAdsDescription = $aAds['ads_desc'];
  $adsImageUrl = $activeAdsImage;
?>
<!--SPONSORED ADS-->
<a href="<?php echo html_entity_decode($activeAdsUrl);?>" target="blank_" rel="noopener" class="transition">
    <div class="i_sponsored_card">
        <div class="i_sponsored_media">
            <img src="<?php echo html_entity_decode($adsImageUrl);?>" alt="<?php echo iN_HelpSecure($activeAdsTitle); ?>"/>
        </div>
        <div class="i_sponsored_body">
            <div class="i_sponsored_title">
                <?php echo iN_HelpSecure($activeAdsTitle);?>
            </div>
            <div class="i_sponsored_desc">
                <?php echo iN_HelpSecure($activeAdsDescription);?>
            </div>
            <div class="i_sponsored_ads_link">
                <?php echo iN_HelpSecure($iN->iN_getHost($activeAdsUrl));?>
            </div>
        </div>
        <div class="i_sponsored_action">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
        </div>
    </div>
</a>
    <!--/SPONSORED ADS-->
<?php }?>
</div>
<?php }
?>
