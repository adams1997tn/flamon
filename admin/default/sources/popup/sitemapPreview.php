<?php
$sitemapContent = function_exists('iN_BuildSitemapXml') ? iN_BuildSitemapXml() : '';
?>
<div class="i_modal_bg_in">
    <div class="i_modal_in_in">
        <div class="i_modal_content general_conf" id="sitemapPreviewContainer">
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['sitemap_preview']); ?>
                <div class="shareClose transition">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['sitemap_xml']); ?></div>
                <div class="irow_box_right column flex_">
                    <textarea class="i_textarea flex_ border_one" rows="14" readonly><?php echo iN_SecureTextareaOutput($sitemapContent); ?></textarea>
                </div>
            </div>
            <div class="i_modal_g_footer flex_">
                <div class="alertBtnLeft no-del transition">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
