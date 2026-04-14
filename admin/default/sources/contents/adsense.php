<?php
$adsenseStatus = isset($adsenseStatus) ? (string)$adsenseStatus : '0';
$adsenseClientId = $adsenseClientId ?? '';
$adsenseSlotTop = $adsenseSlotTop ?? '';
$adsenseSlotInline = $adsenseSlotInline ?? '';
$adsenseSlotFooter = $adsenseSlotFooter ?? '';
$adsenseSlotSidebar = $adsenseSlotSidebar ?? '';
$adsenseInlineFrequency = isset($adsenseInlineFrequency) ? (int)$adsenseInlineFrequency : 4;
$adsenseInlineOffset = isset($adsenseInlineOffset) ? (int)$adsenseInlineOffset : 1;
$adsenseTopSize = $adsenseTopSize ?? 'responsive';
$adsenseInlineSize = $adsenseInlineSize ?? 'responsive';
$adsenseFooterSize = $adsenseFooterSize ?? 'responsive';
$adsenseSidebarSize = $adsenseSidebarSize ?? 'responsive';
$adsenseUpdatedAt = $adsenseUpdatedAt ?? null;
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '');

$sizeOptions = [
    'responsive' => 'Responsive (auto)',
    '300x250' => '300 x 250',
    '336x280' => '336 x 280',
    '728x90' => '728 x 90',
    '970x90' => '970 x 90',
    '320x100' => '320 x 100',
];
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['google_adsense'] ?? 'Google Adsense'); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <div class="adsense_notice"><?php echo iN_HelpSecure($LANG['google_adsense_desc'] ?? 'Only admins can manage Adsense. Leave slots empty to disable.'); ?></div>
            <?php if ($adsenseUpdatedAt): ?>
                <div class="adsense_last_update"><?php echo iN_HelpSecure($LANG['last_update'] ?? 'Last update'); ?>: <?php echo date('M d, Y H:i', (int)$adsenseUpdatedAt); ?></div>
            <?php endif; ?>
            <form method="post" id="adsenseSettings">
                <input type="hidden" name="f" value="adsense_settings">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

                <div class="adsense_wrap">
                    <div class="adsense_card">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['general'] ?? 'General'); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['status']); ?></div>
                            <div class="irow_box_right">
                                <select name="adsense_status" class="i_input">
                                    <option value="0" <?php echo $adsenseStatus === '0' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?></option>
                                    <option value="1" <?php echo $adsenseStatus === '1' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['enable'] ?? 'Enable'); ?></option>
                                </select>
                                <div class="rec_not"><?php echo iN_HelpSecure($LANG['google_adsense_toggle_desc'] ?? 'Disable to hide all Adsense blocks.'); ?></div>
                            </div>
                        </div>

                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['adsense_client_id'] ?? 'Client ID'); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="adsense_client_id" class="i_input flex_" value="<?php echo iN_HelpSecure($adsenseClientId); ?>" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                                <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_client_desc'] ?? 'Use ca-pub-xxxxxxxx ID. Required for all slots.'); ?></div>
                                <ul class="adsense_hint_list">
                                    <li><?php echo iN_HelpSecure($LANG['adsense_client_step_one'] ?? 'Sign in to Google Adsense.'); ?></li>
                                    <li><?php echo iN_HelpSecure($LANG['adsense_client_step_two'] ?? 'Go to Account > Account information.'); ?></li>
                                    <li>
                                        <?php echo iN_HelpSecure($LANG['adsense_client_step_three'] ?? 'Copy your Publisher ID (starts with ca-pub-) and paste here.'); ?>
                                        <a href="https://support.google.com/adsense/answer/105516?hl=en" target="_blank" rel="noopener noreferrer"><?php echo iN_HelpSecure($LANG['adsense_client_help_link'] ?? 'See help'); ?></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="adsense_card">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['adsense_header_slot'] ?? 'Header / Top slot'); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['adsense_header_slot_id'] ?? 'Header / Top slot ID'); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="adsense_slot_top" class="i_input flex_" value="<?php echo iN_HelpSecure($adsenseSlotTop); ?>" placeholder="1234567890">
                                <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_slot_id_desc'] ?? 'Paste the Adsense slot ID for this position.'); ?></div>
                                <div class="adsense_inline_grid">
                                    <div>
                                        <label class="adsense_label"><?php echo iN_HelpSecure($LANG['adsense_slot_size'] ?? 'Size'); ?></label>
                                        <select name="adsense_top_size" class="i_input">
                                            <?php foreach ($sizeOptions as $key => $label) { ?>
                                                <option value="<?php echo iN_HelpSecure($key); ?>" <?php echo $adsenseTopSize === $key ? 'selected' : ''; ?>><?php echo iN_HelpSecure($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adsense_card">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['adsense_inline_slot'] ?? 'Inline slot (between posts)'); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['adsense_inline_slot_id'] ?? 'Inline slot ID'); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="adsense_slot_inline" class="i_input flex_" value="<?php echo iN_HelpSecure($adsenseSlotInline); ?>" placeholder="1234567890">
                                <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_slot_id_desc'] ?? 'Paste the Adsense slot ID for this position.'); ?></div>
                                <div class="adsense_inline_grid">
                                    <div>
                                        <label class="adsense_label"><?php echo iN_HelpSecure($LANG['adsense_slot_size'] ?? 'Size'); ?></label>
                                        <select name="adsense_inline_size" class="i_input">
                                            <?php foreach ($sizeOptions as $key => $label) { ?>
                                                <option value="<?php echo iN_HelpSecure($key); ?>" <?php echo $adsenseInlineSize === $key ? 'selected' : ''; ?>><?php echo iN_HelpSecure($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="adsense_label"><?php echo iN_HelpSecure($LANG['adsense_frequency'] ?? 'Frequency'); ?></label>
                                        <input type="number" min="1" max="20" name="adsense_inline_frequency" class="i_input" value="<?php echo iN_HelpSecure($adsenseInlineFrequency); ?>">
                                        <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_frequency_desc'] ?? 'Show after every N posts.'); ?></div>
                                    </div>
                                    <div>
                                        <label class="adsense_label"><?php echo iN_HelpSecure($LANG['adsense_offset'] ?? 'Offset'); ?></label>
                                        <input type="number" min="1" max="20" name="adsense_inline_offset" class="i_input" value="<?php echo iN_HelpSecure($adsenseInlineOffset); ?>">
                                        <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_offset_desc'] ?? 'Start showing after N posts.'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adsense_card">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['adsense_footer_slot'] ?? 'Footer slot'); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['adsense_footer_slot_id'] ?? 'Footer slot ID'); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="adsense_slot_footer" class="i_input flex_" value="<?php echo iN_HelpSecure($adsenseSlotFooter); ?>" placeholder="1234567890">
                                <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_slot_id_desc'] ?? 'Paste the Adsense slot ID for this position.'); ?></div>
                                <div class="adsense_inline_grid">
                                    <div>
                                        <label class="adsense_label"><?php echo iN_HelpSecure($LANG['adsense_slot_size'] ?? 'Size'); ?></label>
                                        <select name="adsense_footer_size" class="i_input">
                                            <?php foreach ($sizeOptions as $key => $label) { ?>
                                                <option value="<?php echo iN_HelpSecure($key); ?>" <?php echo $adsenseFooterSize === $key ? 'selected' : ''; ?>><?php echo iN_HelpSecure($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adsense_card">
                        <div class="adsense_subtitle"><?php echo iN_HelpSecure($LANG['adsense_sidebar_slot'] ?? 'Sidebar slot'); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['adsense_sidebar_slot_id'] ?? 'Sidebar slot ID'); ?></div>
                            <div class="irow_box_right">
                                <input type="text" name="adsense_slot_sidebar" class="i_input flex_" value="<?php echo iN_HelpSecure($adsenseSlotSidebar); ?>" placeholder="1234567890">
                                <div class="rec_not"><?php echo iN_HelpSecure($LANG['adsense_slot_id_desc'] ?? 'Paste the Adsense slot ID for this position.'); ?></div>
                                <div class="adsense_inline_grid">
                                    <div>
                                        <label class="adsense_label"><?php echo iN_HelpSecure($LANG['adsense_slot_size'] ?? 'Size'); ?></label>
                                        <select name="adsense_sidebar_size" class="i_input">
                                            <?php foreach ($sizeOptions as $key => $label) { ?>
                                                <option value="<?php echo iN_HelpSecure($key); ?>" <?php echo $adsenseSidebarSize === $key ? 'selected' : ''; ?>><?php echo iN_HelpSecure($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adsense_card adsense_actions">
                        <div class="warning_wrapper warning_one"><?php echo iN_HelpSecure($LANG['all_fields_must_be_filled'] ?? 'Please fill required fields.'); ?></div>
                        <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully'] ?? 'Updated successfully'); ?></div>
                        <div class="i_general_row_box_item flex_ tabing_non_justify">
                            <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateAdsenseSettings"><?php echo iN_HelpSecure($LANG['save_edit']);?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
