<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['website_settings']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['show_full_name']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="fullnamestatus">
                            <input type="checkbox" name="fullnamestatus" class="chmdPost" id="fullnamestatus" <?php echo iN_HelpSecure($fullnameorusername) == 'yes' ? 'value="no" checked="checked"' : 'value="yes"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <input type="hidden" name="fullnamestatus" class="fullnamestatus" value="<?php echo iN_HelpSecure($fullnameorusername); ?>">
                        <div class="success_tick tabing flex_ sec_one fullnamestatus"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['age_confirm_setting_label']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="ageConfirmStatus">
                            <input type="checkbox" name="ageConfirmStatus" class="chmdPost" id="ageConfirmStatus" <?php echo iN_HelpSecure($ageConfirmStatus) == 'yes' ? 'value="no" checked="checked"' : 'value="yes"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <input type="hidden" name="ageConfirmStatus" class="ageConfirmStatus" value="<?php echo iN_HelpSecure($ageConfirmStatus); ?>">
                        <div class="success_tick tabing flex_ sec_one ageConfirmStatus"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['link_watermark_status']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="lwatermarkStatus">
                            <input type="checkbox" name="lwatermarkStatus" class="chmdPost" id="lwatermarkStatus" <?php echo iN_HelpSecure($LinkWatermarkStatus) == 'yes' ? 'value="no" checked="checked"' : 'value="yes"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <input type="hidden" name="lwatermarkStatus" class="lwatermarkStatus" value="<?php echo iN_HelpSecure($LinkWatermarkStatus); ?>">
                        <div class="success_tick tabing flex_ sec_one lwatermarkStatus"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['watermark_status']); ?></div>
                <div class="irow_box_right">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="watermarkStatus">
                            <input type="checkbox" name="watermarkStatus" class="chmdPost" id="watermarkStatus" <?php echo iN_HelpSecure($watermarkStatus) == 'yes' ? 'value="no" checked="checked"' : 'value="yes"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <input type="hidden" name="watermarkStatus" class="watermarkStatus" value="<?php echo iN_HelpSecure($watermarkStatus); ?>">
                        <div class="success_tick tabing flex_ sec_one watermarkStatus"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_logo">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['site_logo']); ?></div>
                <div class="irow_box_right">
                    <div class="certification_file_box">
                        <form id="lUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/request/request.php">
                            <label for="id_logo">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')) . iN_HelpSecure($LANG['upload_logo']); ?>
                                <input type="file" id="id_logo" name="uploading[]" data-id="logoFile" data-type="sec_one" class="editAds_file">
                            </label>
                        </form>
                        <div class="success_tick tabing flex_ sec_one"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                    <?php if (!empty($logo)) { ?>
                        <div class="rec_not" style="margin-top:8px;">
                            <img id="light_logo_preview" src="<?php echo iN_HelpSecure($base_url . $logo); ?>?v=<?php echo time(); ?>" alt="" style="max-height:48px;background:#fff;padding:6px;border-radius:6px;">
                        </div>
                    <?php } else { ?>
                        <div class="rec_not" style="margin-top:8px;">
                            <img id="light_logo_preview" src="" alt="" style="display:none;max-height:48px;background:#fff;padding:6px;border-radius:6px;">
                        </div>
                    <?php } ?>
                    <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']); ?></div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_logo_night">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['site_logo_night'] ?? 'Site Logo (Night Mode)'); ?></div>
                <div class="irow_box_right">
                    <div class="certification_file_box">
                        <form id="nlUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/request/request.php">
                            <label for="id_night_logo">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')) . iN_HelpSecure($LANG['upload_night_logo'] ?? 'Upload Night Mode Logo'); ?>
                                <input type="file" id="id_night_logo" name="uploading[]" data-id="nightLogoFile" data-type="sec_four" class="editAds_file">
                            </label>
                        </form>
                        <div class="success_tick tabing flex_ sec_four"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                    <?php if (!empty($nightLogo)) { ?>
                        <div class="rec_not" style="margin-top:8px;">
                            <img id="night_logo_preview" src="<?php echo iN_HelpSecure($base_url . $nightLogo); ?>?v=<?php echo time(); ?>" alt="" style="max-height:48px;background:#111;padding:6px;border-radius:6px;">
                        </div>
                    <?php } else { ?>
                        <div class="rec_not" style="margin-top:8px;">
                            <img id="night_logo_preview" src="" alt="" style="display:none;max-height:48px;background:#111;padding:6px;border-radius:6px;">
                        </div>
                    <?php } ?>
                    <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_logo_sizes']); ?></div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_fav">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['favicon']); ?></div>
                <div class="irow_box_right">
                    <div class="certification_file_box">
                        <form id="lfUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/request/request.php">
                            <label for="id_fav">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')) . iN_HelpSecure($LANG['upload_favicon']); ?>
                                <input type="file" id="id_fav" name="uploading[]" data-id="faviconFile" data-type="sec_two" class="editAds_file">
                            </label>
                        </form>
                        <div class="success_tick tabing flex_ sec_two"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                    <div class="rec_not"><?php echo iN_HelpSecure($LANG['favicon_size_must_be']); ?></div>
                </div>
            </div>

            <div class="i_general_row_box_item flex_ tabing_non_justify" id="sec_logo">
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['watermark_image']); ?></div>
                <div class="irow_box_right">
                    <div class="certification_file_box">
                        <form id="waUploadForm" class="options-form" method="post" enctype="multipart/form-data" action="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/request/request.php">
                            <label for="id_watermark" class="max_width_settings">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('79')) . iN_HelpSecure($LANG['upload_watermark']); ?>
                                <input type="file" id="id_watermark" name="uploading[]" data-id="WatlogoFile" data-type="sec_tree" class="editAds_file">
                            </label>
                        </form>
                        <div class="success_tick tabing flex_ sec_tree"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')); ?></div>
                    </div>
                    <div class="rec_not"><?php echo iN_HelpSecure($LANG['recommended_watermark_sizes']); ?></div>
                </div>
            </div>

            <form enctype="multipart/form-data" method="post" id="myProfileForm">
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['site_name']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="site_name" class="i_input flex_" value="<?php echo iN_HelpSecure($siteName); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['site_title']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="site_title" class="i_input flex_" value="<?php echo iN_HelpSecure($siteTitle); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['site_description']); ?></div>
                    <div class="irow_box_right">
                        <textarea name="site_description" class="i_textarea flex_ border_one"><?php echo iN_HelpSecure($siteDescription); ?></textarea>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['site_keywords']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="site_keywords" class="i_input flex_" value="<?php echo iN_HelpSecure($siteKeyWords); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['watermark_position']); ?></div>
                    <div class="irow_box_right">
                        <select name="watermark_position" class="i_input flex_">
                            <option value="bottom_right" <?php echo iN_HelpSecure($watermarkPosition) === 'bottom_right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['watermark_position_bottom_right']); ?></option>
                            <option value="bottom_left" <?php echo iN_HelpSecure($watermarkPosition) === 'bottom_left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['watermark_position_bottom_left']); ?></option>
                            <option value="top_right" <?php echo iN_HelpSecure($watermarkPosition) === 'top_right' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['watermark_position_top_right']); ?></option>
                            <option value="top_left" <?php echo iN_HelpSecure($watermarkPosition) === 'top_left' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['watermark_position_top_left']); ?></option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['watermark_opacity']); ?></div>
                    <div class="irow_box_right">
                        <input type="number" min="10" max="100" step="1" name="watermark_opacity" class="i_input flex_" value="<?php echo iN_HelpSecure($watermarkOpacity); ?>">
                        <div class="rec_not"><?php echo iN_HelpSecure($LANG['watermark_opacity_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['watermark_size']); ?></div>
                    <div class="irow_box_right">
                        <input type="number" min="8" max="40" step="1" name="watermark_size" class="i_input flex_" value="<?php echo iN_HelpSecure($watermarkSize); ?>">
                        <div class="rec_not"><?php echo iN_HelpSecure($LANG['watermark_size_note']); ?></div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <input type="hidden" name="logo" id="logo" value="<?php echo iN_HelpSecure($logo); ?>">
                    <input type="hidden" name="night_logo" id="night_logo" value="<?php echo iN_HelpSecure($nightLogo ?? ''); ?>">
                    <input type="hidden" name="walogo" id="watlogo" value="<?php echo iN_HelpSecure($siteWatermarkLogo); ?>">
                    <input type="hidden" name="favicon" id="favicon" value="<?php echo iN_HelpSecure($favicon); ?>">
                    <input type="hidden" name="f" value="updateGeneral">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateGeneralSettings"><?php echo iN_HelpSecure($LANG['save_edit']); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
