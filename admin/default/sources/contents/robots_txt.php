<?php
$robotsPath = rtrim(APP_ROOT_PATH, '/\\') . '/robots.txt';
$robotsContent = '';
if (is_file($robotsPath) && is_readable($robotsPath)) {
    $robotsContent = (string) file_get_contents($robotsPath);
}
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['robots_txt']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <form enctype="multipart/form-data" method="post" id="robotsTxtForm">
                <input type="hidden" name="f" value="robotsTxt">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                <div class="adsense_wrap">
                    <div class="adsense_card" style="grid-column: 1 / -1;">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['robots_txt_file']); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_right column flex_">
                                <textarea name="robots_txt" class="i_textarea flex_ border_one" rows="12" style="width: 100%;"><?php echo iN_SecureTextareaOutput($robotsContent); ?></textarea>
                                <div class="rec_not">
                                    <?php echo iN_HelpSecure($LANG['robots_txt_help']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="adsense_card">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['sitemap_xml']); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_right column flex_">
                                <button type="button" class="ghost_btn transition showSitemapPopup">
                                    <?php echo iN_HelpSecure($LANG['view_sitemap']); ?>
                                </button>
                                <div class="rec_not">
                                    <?php echo iN_HelpSecure(route_url('sitemap.xml')); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="successNot"><?php echo iN_HelpSecure($LANG['saved_successfully']); ?></div>
                <div class="warning_"><?php echo iN_HelpSecure($LANG['save_failed']); ?></div>
                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateRobotsTxt">
                        <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
