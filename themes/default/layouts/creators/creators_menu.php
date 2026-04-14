<div class="creators_menu_wrapper">
    <div class="creators_menu_shell">
        <div class="creators_menu_list" id="creatorsMenuList">
            <?php
            $categories = $iN->iN_GetCategories();
            if ($categories) {
                foreach ($categories as $caData) {
                    $categoryID = $caData['c_id'] ?? NULL;
                    $categoryKey = $caData['c_key'] ?? NULL;
                    $isActive = (iN_HelpSecure($pageCreator) === iN_HelpSecure($categoryKey)) ? 'active_pc' : '';
                    $subCategories = $iN->iN_CheckAndGetSubCat($categoryID);
                    $categoryUrl = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($categoryKey);
                    $categoryName = iN_HelpSecure($PROFILE_CATEGORIES[$categoryKey] ?? ucfirst($categoryKey));
                    $hasSub = !empty($subCategories);
                    ?>
                    <div class="creator_item transition <?php echo iN_HelpSecure($isActive); ?>" data-menu-item>
                        <a href="<?php echo iN_HelpSecure($categoryUrl); ?>">
                            <span class="creator_item_label"><?php echo iN_HelpSecure($categoryName); ?></span>
                            <?php if ($hasSub) { ?>
                                <span class="creator_item_caret"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36'));?></span>
                            <?php } ?>
                        </a>
                        <?php if ($hasSub) { ?>
                            <div class="subcategoryname">
                                <?php foreach ($subCategories as $subData):
                                    $subKey = $subData['sc_key'] ?? NULL;
                                    $subUrl = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($subKey);
                                    $subName = iN_HelpSecure($PROFILE_SUBCATEGORIES[$subKey] ?? ucfirst($subKey));
                                    ?>
                                    <div class="sub_m_item">
                                        <a href="<?php echo iN_HelpSecure($subUrl); ?>"><?php echo iN_HelpSecure($subName); ?></a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php }
            } ?>
        </div>
        <div class="creator_menu_more" id="creatorsMenuMore">
            <button type="button" class="creator_menu_more_btn" aria-haspopup="true" aria-expanded="false">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16'));?>
            </button>
            <div class="creator_menu_more_dropdown" id="creatorsMenuMoreDropdown"></div>
        </div>
    </div>
</div>
