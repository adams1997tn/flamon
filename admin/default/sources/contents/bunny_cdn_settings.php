<?php
$bunnyCdnStatus = isset($bunnyCdnStatus) ? (string) $bunnyCdnStatus : '0';
$bunnyCdnBase = isset($bunnyCdnBase) ? (string) $bunnyCdnBase : '';
$bunnyStorageStatus = isset($bunnyStorageStatus) ? (string) $bunnyStorageStatus : '0';
$bunnyStorageZone = isset($bunnyStorageZone) ? (string) $bunnyStorageZone : '';
$bunnyStorageHost = isset($bunnyStorageHost) ? (string) $bunnyStorageHost : 'storage.bunnycdn.com';
$bunnyStorageAccessKey = isset($bunnyStorageAccessKey) ? (string) $bunnyStorageAccessKey : '';
$bunnyStorageAccessKeySaved = $bunnyStorageAccessKey !== '';
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['bunny_cdn_settings']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <form enctype="multipart/form-data" method="post" id="storageSettings">
                <input type="hidden" name="f" value="BunnyCdnSettings">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

                <div class="i_general_title_box" style="margin-top: 10px;">
                    <?php echo iN_HelpSecure($LANG['bunny_cdn']); ?>
                </div>
                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="bunnyCdnStatus">
                            <input type="hidden" name="bunnyCdnStatus" value="0">
                            <input
                                type="checkbox"
                                name="bunnyCdnStatus"
                                class="sstat"
                                id="bunnyCdnStatus"
                                value="1"
                                <?php echo $bunnyCdnStatus === '1' ? 'checked="checked"' : ''; ?>
                            >
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['bunny_cdn_status']); ?></div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['bunny_cdn_help']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['bunny_cdn_url']); ?></div>
                    <div class="irow_box_right column flex_">
                        <input
                            type="text"
                            name="bunnyCdnBase"
                            class="i_input flex_"
                            placeholder="https://xxxx.b-cdn.net/"
                            value="<?php echo iN_HelpSecure($bunnyCdnBase); ?>"
                        >
                        <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['bunny_cdn_url_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_title_box" style="margin-top: 20px;">
                    <?php echo iN_HelpSecure($LANG['bunny_storage_settings']); ?>
                </div>
                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="bunnyStorageStatus">
                            <input type="hidden" name="bunnyStorageStatus" value="0">
                            <input
                                type="checkbox"
                                name="bunnyStorageStatus"
                                class="sstat"
                                id="bunnyStorageStatus"
                                value="1"
                                <?php echo $bunnyStorageStatus === '1' ? 'checked="checked"' : ''; ?>
                            >
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['bunny_storage_status']); ?></div>
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['bunny_storage_help']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['bunny_storage_zone']); ?></div>
                    <div class="irow_box_right">
                        <input
                            type="text"
                            name="bunnyStorageZone"
                            class="i_input flex_"
                            value="<?php echo iN_HelpSecure($bunnyStorageZone); ?>"
                            placeholder="my-storage-zone"
                        >
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['bunny_storage_access_key']); ?></div>
                    <div class="irow_box_right column flex_">
                        <input
                            type="password"
                            name="bunnyStorageAccessKey"
                            class="i_input flex_"
                            value=""
                            autocomplete="new-password"
                            placeholder="<?php echo iN_HelpSecure($LANG['bunny_storage_access_key_placeholder']); ?>"
                        >
                        <?php if ($bunnyStorageAccessKeySaved) { ?>
                            <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['bunny_storage_access_key_saved']); ?></div>
                        <?php } else { ?>
                            <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['bunny_storage_access_key_note']); ?></div>
                        <?php } ?>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['bunny_storage_host']); ?></div>
                    <div class="irow_box_right column flex_">
                        <input
                            type="text"
                            name="bunnyStorageHost"
                            class="i_input flex_"
                            value="<?php echo iN_HelpSecure($bunnyStorageHost); ?>"
                            placeholder="storage.bunnycdn.com"
                        >
                        <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['bunny_storage_host_note']); ?></div>
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
                <div class="warning_"><?php echo iN_HelpSecure($LANG['save_failed']); ?></div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateGeneralSettings">
                        <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
