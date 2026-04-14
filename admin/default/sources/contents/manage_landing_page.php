<?php
$landingPlugins = [];
$pluginRoot = defined('APP_ROOT_PATH') ? APP_ROOT_PATH : dirname(__DIR__, 4);
$pluginRoot = rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $currentTheme . DIRECTORY_SEPARATOR . 'landing_plugins';

if (is_dir($pluginRoot)) {
    $pluginDirs = glob($pluginRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    if ($pluginDirs) {
        foreach ($pluginDirs as $pluginDir) {
            $manifestFile = $pluginDir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!is_file($manifestFile)) {
                continue;
            }
            $manifestRaw = file_get_contents($manifestFile);
            if ($manifestRaw === false) {
                continue;
            }
            $manifest = json_decode($manifestRaw, true);
            if (!is_array($manifest)) {
                continue;
            }
            $slug = basename($pluginDir);
            if (!preg_match('/^[a-z0-9_-]+$/i', $slug)) {
                continue;
            }
            $name = $manifest['name'] ?? $slug;
            $version = $manifest['version'] ?? '';
            $preview = $manifest['preview'] ?? 'preview.png';
            if (strpos($preview, '..') !== false || substr((string)$preview, 0, 1) === '/') {
                $preview = 'preview.png';
            }
            $previewPath = $pluginDir . DIRECTORY_SEPARATOR . $preview;
            if (!is_file($previewPath)) {
                $preview = 'preview.png';
            }
            $previewUrl = $base_url . 'themes/' . $currentTheme . '/landing_plugins/' . $slug . '/' . $preview;
            $landingPlugins[] = [
                'slug' => $slug,
                'name' => $name,
                'version' => $version,
                'preview' => $previewUrl,
            ];
        }
    }
}
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <!---->
        <div class="i_general_title_box flex_ tabing_non_justify">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('137'));?><?php echo iN_HelpSecure($LANG['manage_landing_page']);?>
        </div>
        <!---->
        <!---->
        <div class="i_general_row_box column flex_" id="general_conf">
            <!---->
               <div class="buyCreditWrapper flex_ tabing landing_margin_top">
                  <!---->
                  <div class="credit_plan_box">
                     <div class="plan_box tabing flex_ column plbox1">
                        <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url;?>img/landingImages/default.png">
                           <img class="a-item-img" src="<?php echo $base_url;?>img/landingImages/default.png">
                        </div>
                        <div class="tabing flex_ edit_active_delete landing_top_padding">
                            <div class="ecd_item">
                            <div class="el-radio el-radio-yellow">
                                <input type="radio" name="mTheme" id="1" value="1" <?php echo iN_HelpSecure($landingPageType) == '1' ? 'checked="checked"' : '';?>>
                                <label class="el-radio-style mTheme" for="1"></label>
                            </div>
                            </div>
                        </div>
                     </div>
                  </div>
                  <!---->
                  <!---->
                  <div class="credit_plan_box">
                     <div class="plan_box tabing flex_ column plbox2">
                        <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url;?>img/landingImages/landing.png">
                           <img class="a-item-img" src="<?php echo $base_url;?>img/landingImages/landing.png">
                        </div>
                        <div class="tabing flex_ edit_active_delete landing_top_padding">
                            <div class="ecd_item">
                            <div class="el-radio el-radio-yellow">
                                <input type="radio" name="mTheme" id="2" value="2" <?php echo iN_HelpSecure($landingPageType) == '2' ? 'checked="checked"' : '';?>>
                                <label class="el-radio-style mTheme" for="2"></label>
                            </div>
                            </div>
                        </div>
                     </div>
                  </div>
                  <!---->
                  <!---->
                  <div class="credit_plan_box">
                     <div class="plan_box tabing flex_ column plbox3">
                        <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/img/landing_two_preview.png">
                           <img class="a-item-img" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/img/landing_two_preview.png">
                        </div>
                        <div class="tabing flex_ edit_active_delete landing_top_padding">
                           <div class="ecd_item">
                           <div class="el-radio el-radio-yellow">
                              <input type="radio" name="mTheme" id="3" value="3" <?php echo iN_HelpSecure($landingPageType) == '3' ? 'checked="checked"' : '';?>>
                              <label class="el-radio-style mTheme" for="3"></label>
                           </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <!---->
                  <?php if (!empty($landingPlugins)) { ?>
                      <?php foreach ($landingPlugins as $plugin) { ?>
                          <div class="credit_plan_box">
                              <div class="plan_box tabing flex_ column">
                                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo iN_HelpSecure($plugin['preview']); ?>">
                                      <img class="a-item-img" src="<?php echo iN_HelpSecure($plugin['preview']); ?>" alt="<?php echo iN_HelpSecure($plugin['name']); ?>">
                                  </div>
                                  <div class="tabing flex_ edit_active_delete landing_top_padding">
                                      <div class="ecd_item">
                                          <div class="el-radio el-radio-yellow">
                                              <input type="radio" name="mTheme" id="plugin_<?php echo iN_HelpSecure($plugin['slug']); ?>" value="plugin:<?php echo iN_HelpSecure($plugin['slug']); ?>" <?php echo $landingPageType == '4' && $landingPagePlugin == $plugin['slug'] ? 'checked="checked"' : '';?>>
                                              <label class="el-radio-style mTheme" for="plugin_<?php echo iN_HelpSecure($plugin['slug']); ?>"></label>
                                          </div>
                                      </div>
                                  </div>
                                  <div class="tabing flex_ landing_top_padding">
                                      <div><?php echo iN_HelpSecure($plugin['name']); ?><?php echo $plugin['version'] ? ' v' . iN_HelpSecure($plugin['version']) : ''; ?></div>
                                  </div>
                              </div>
                          </div>
                      <?php } ?>
                  <?php } ?>
               </div>
            <!---->
        </div>
        <!---->
        <!---->
        <div class="i_general_title_box flex_ tabing_non_justify">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('124'));?><?php echo iN_HelpSecure($LANG['update_landing_page_images']);?>
        </div>
        <!---->
        <!---->
        <div class="i_general_row_box column flex_" id="general_conf">
           <!---->
           <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_one">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_one']);?></div>
               <div class="irow_box_right">
                <div class="landing_img_preview">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingPageFirstImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingPageFirstImage;?>">
                  </div>
                </div>
                <div class="certification_file_box">
                <form id="lUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                    <label for="id_landing_first">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_one']);?>
                        <input type="file" id="id_landing_first" name="uploading[]" data-id="imageOne" data-type="sec_one" class="editAds_file">
                    </label>
                </form>
                <div class="success_tick tabing flex_ sec_one"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
                </div>
                <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_two">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_two']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage"  data-bg="<?php echo $base_url.$landingpageFirstImageArrow;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingpageFirstImageArrow;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="lsUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_second">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_two']);?>
                        <input type="file" id="id_landing_second" name="uploading[]" data-id="imageTwo" data-type="sec_two" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_two"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_three">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_three']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingpageFirstDesctiptionImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingpageFirstDesctiptionImage;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="ltUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_thirth">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_three']);?>
                        <input type="file" id="id_landing_thirth" name="uploading[]" data-id="imageThree" data-type="sec_three" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_three"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_four">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_four']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage"  data-bg="<?php echo $base_url.$landingpageSecondDesctiptionImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingpageSecondDesctiptionImage;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="lfUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_four">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_four']);?>
                        <input type="file" id="id_landing_four" name="uploading[]" data-id="imageFour" data-type="sec_four" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_four"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_five">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_five']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingpageThirdDesctiptionImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingpageThirdDesctiptionImage;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="lfiUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_five">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_five']);?>
                        <input type="file" id="id_landing_five" name="uploading[]" data-id="imageFive" data-type="sec_five" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_five"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_six">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_six']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingpageFourthDesctiptionImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingpageFourthDesctiptionImage;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="lsiUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_six">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_six']);?>
                        <input type="file" id="id_landing_six" name="uploading[]" data-id="imageSix" data-type="sec_six" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_six"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_seventh">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_seventh']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingpageFifthDesctiptionImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingpageFifthDesctiptionImage;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="lsevUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_seventh">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_seventh']);?>
                        <input type="file" id="id_landing_seventh" name="uploading[]" data-id="imageSeventh" data-type="sec_seventh" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_seventh"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_bg">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_bg']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingPageSectionTwoBG;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingPageSectionTwoBG;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="bgUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_bg">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_bg']);?>
                        <input type="file" id="id_landing_bg" name="uploading[]" data-id="imageBg" data-type="sec_bg" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_bg"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
            <!---->
            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_frnt">
               <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['image_frnt']);?></div>
               <div class="irow_box_right">
               <div class="landing_img_preview landing_height">
                  <div class="a_image_area flex_ tabing border_one theaImage" data-bg="<?php echo $base_url.$landingSectionFeatureImage;?>">
                     <img class="a-item-img" src="<?php echo $base_url.$landingSectionFeatureImage;?>">
                  </div>
               </div>
               <div class="certification_file_box">
               <form id="frntUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/request/request.php">
                  <label for="id_landing_frnt">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')); echo iN_HelpSecure($LANG['update_image_frnt']);?>
                        <input type="file" id="id_landing_frnt" name="uploading[]" data-id="imageFrnt" data-type="sec_frnt" class="editAds_file">
                  </label>
               </form>
               <div class="success_tick tabing flex_ sec_frnt"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></div>
               </div>
               <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']);?></div>
               </div>
            </div>
            <!---->
        </div>
        <!---->
    </div>
</div>

<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/landingImageBackgroundInit.js?v=<?php echo iN_HelpSecure($version);?>" defer></script>
