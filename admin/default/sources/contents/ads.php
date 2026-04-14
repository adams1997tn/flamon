<?php
$placements = $iN->iN_ListAdPlacements();
$selectedPlacementId = isset($_GET['placement']) ? (int)$_GET['placement'] : 0;
$selectedPlacement = null;
if ($selectedPlacementId > 0) {
    $selectedPlacement = $iN->iN_GetAdPlacementById($selectedPlacementId);
}
if (!$selectedPlacement && !empty($placements)) {
    $selectedPlacement = $placements[0];
    $selectedPlacementId = (int)$selectedPlacement['placement_id'];
}
$placementCodes = $selectedPlacement ? $iN->iN_GetAdCodesByPlacementId((int)$selectedPlacement['placement_id']) : [];
$placementPresets = [
    'header_top' => $LANG['adsense_header_slot'] ?? 'Header / Top',
    'inline_feed' => $LANG['adsense_inline_slot'] ?? 'Inline (between posts)',
    'sidebar' => $LANG['adsense_sidebar_slot'] ?? 'Sidebar',
    'footer' => $LANG['adsense_footer_slot'] ?? 'Footer',
    'profile_header' => 'Profile header',
    'profile_footer' => 'Profile footer',
    'video_player' => 'Video player',
    'custom_slot' => 'Custom slot'
];
$inlineFrequencyOptions = [0,1,2,3,4,5,6,8,10,12,15,20];
$inlineOffsetOptions = [0,1,2,3,4,5,6,8,10,12,15];
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '');
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify ads_manager" id="general_conf">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['ads_manager'] ?? 'Ads Manager'); ?>
            <div class="ads_manager_desc"><?php echo iN_HelpSecure($LANG['ads_manager_desc'] ?? 'Create placements, attach ad codes (AdSense, Ezoic, Media.net, MGID, Propeller, custom) and choose the active code per placement.'); ?></div>
        </div>

        <div class="ads_manager_grid">
            <div class="ads_panel">
                <div class="panel_header"><?php echo iN_HelpSecure($LANG['ads_placements'] ?? 'Placements'); ?></div>
                <div class="ads_table_header">
                    <div><?php echo iN_HelpSecure($LANG['key'] ?? 'Key'); ?></div>
                    <div><?php echo iN_HelpSecure($LANG['status']); ?></div>
                    <div><?php echo iN_HelpSecure($LANG['inline_rules'] ?? 'Inline rules'); ?></div>
                    <div><?php echo iN_HelpSecure($LANG['actions']); ?></div>
                </div>
                <div class="ads_table_body">
                    <?php if (!empty($placements)) { foreach ($placements as $pl) { ?>
                        <div class="ads_table_row <?php echo $selectedPlacementId == $pl['placement_id'] ? 'active_row' : ''; ?>">
                            <div class="ads_cell">
                                <div class="ads_key"><?php echo iN_HelpSecure($pl['placement_key']); ?></div>
                                <div class="ads_small"><?php echo iN_HelpSecure($pl['placement_title']); ?></div>
                            </div>
                            <div class="ads_cell">
                                <span class="status_badge <?php echo ((int)$pl['placement_status'] === 1 ? 'on' : 'off'); ?>">
                                    <?php echo (int)$pl['placement_status'] === 1 ? iN_HelpSecure($LANG['enable'] ?? 'Enable') : iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?>
                                </span>
                            </div>
                            <div class="ads_cell">
                                <?php
                                $freqTxt = (int)$pl['inline_frequency'] > 0 ? '1/' . (int)$pl['inline_frequency'] : '-';
                                $offsetTxt = (int)$pl['inline_offset'] > 0 ? (int)$pl['inline_offset'] : '-';
                                ?>
                                <span class="ads_small"><?php echo iN_HelpSecure($LANG['frequency'] ?? 'Frequency'); ?>: <?php echo iN_HelpSecure($freqTxt); ?></span><br>
                                <span class="ads_small"><?php echo iN_HelpSecure($LANG['offset'] ?? 'Offset'); ?>: <?php echo iN_HelpSecure($offsetTxt); ?></span>
                            </div>
                            <div class="ads_cell ads_actions">
                                <a class="ads_link" href="<?php echo iN_HelpSecure($base_url . 'admin/ads?placement=' . $pl['placement_id']); ?>"><?php echo iN_HelpSecure($LANG['edit'] ?? 'Edit'); ?></a>
                            </div>
                        </div>
                    <?php } } else { ?>
                        <div class="ads_empty"><?php echo iN_HelpSecure($LANG['no_placement_found'] ?? 'No placement found.'); ?></div>
                    <?php } ?>
                </div>

                <div class="panel_header mt20"><?php echo iN_HelpSecure($LANG['add_new_placement'] ?? 'Add new placement'); ?></div>
                <form id="adsCreatePlacement" class="ads_form">
                    <input type="hidden" name="f" value="ads_create_placement">
                    <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                    <div class="ads_form_grid">
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['placement_key'] ?? 'Placement key'); ?></label>
                            <select name="placement_key" class="i_input" required>
                                <?php foreach ($placementPresets as $pKey => $pLabel) { ?>
                                    <option value="<?php echo iN_HelpSecure($pKey); ?>"><?php echo iN_HelpSecure($pLabel); ?></option>
                                <?php } ?>
                            </select>
                            <div class="rec_not"><?php echo iN_HelpSecure($LANG['placement_key_desc'] ?? 'Pick a position. Keys can be renamed later in Edit.'); ?></div>
                        </div>
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['placement_title'] ?? 'Title'); ?></label>
                            <input type="text" name="placement_title" class="i_input" placeholder="<?php echo iN_HelpSecure($LANG['placement_title_ph'] ?? 'Sidebar ads'); ?>">
                        </div>
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['placement_status'] ?? 'Status'); ?></label>
                            <select name="placement_status" class="i_input">
                                <option value="1"><?php echo iN_HelpSecure($LANG['enable'] ?? 'Enable'); ?></option>
                                <option value="0"><?php echo iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ads_form_grid">
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['placement_desc'] ?? 'Description'); ?></label>
                            <textarea name="placement_desc" class="i_textarea" rows="2" placeholder="<?php echo iN_HelpSecure($LANG['placement_desc_ph'] ?? 'Where will this ad appear?'); ?>"></textarea>
                        </div>
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['inline_frequency'] ?? 'Inline frequency'); ?></label>
                            <select name="inline_frequency" class="i_input">
                                <?php foreach ($inlineFrequencyOptions as $freq) { ?>
                                    <option value="<?php echo (int)$freq; ?>"><?php echo (int)$freq; ?></option>
                                <?php } ?>
                            </select>
                            <div class="rec_not"><?php echo iN_HelpSecure($LANG['inline_frequency_desc'] ?? 'For feed inline ads. 0 = disabled.'); ?></div>
                        </div>
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['inline_offset'] ?? 'Inline offset'); ?></label>
                            <select name="inline_offset" class="i_input">
                                <?php foreach ($inlineOffsetOptions as $off) { ?>
                                    <option value="<?php echo (int)$off; ?>"><?php echo (int)$off; ?></option>
                                <?php } ?>
                            </select>
                            <div class="rec_not"><?php echo iN_HelpSecure($LANG['inline_offset_desc'] ?? 'Start after N posts. 0 = no offset.'); ?></div>
                        </div>
                    </div>
                    <div class="ads_form_actions">
                        <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['add']); ?></button>
                        <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully'] ?? 'Updated successfully'); ?></div>
                        <div class="warning_wrapper warning_one"><?php echo iN_HelpSecure($LANG['all_fields_must_be_filled'] ?? 'Please fill required fields.'); ?></div>
                    </div>
                </form>
            </div>

            <div class="ads_panel">
                <div class="panel_header"><?php echo iN_HelpSecure($LANG['edit_placement'] ?? 'Edit placement'); ?></div>
                <?php if ($selectedPlacement) { ?>
                    <form id="adsUpdatePlacement" class="ads_form">
                        <input type="hidden" name="f" value="ads_update_placement">
                        <input type="hidden" name="placement_id" value="<?php echo (int)$selectedPlacement['placement_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                        <div class="ads_form_grid">
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['placement_key'] ?? 'Placement key'); ?></label>
                                <input type="text" name="placement_key" class="i_input" value="<?php echo iN_HelpSecure($selectedPlacement['placement_key']); ?>">
                            </div>
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['placement_title'] ?? 'Title'); ?></label>
                                <input type="text" name="placement_title" class="i_input" value="<?php echo iN_HelpSecure($selectedPlacement['placement_title']); ?>">
                            </div>
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['placement_status'] ?? 'Status'); ?></label>
                                <select name="placement_status" class="i_input">
                                    <option value="1" <?php echo (int)$selectedPlacement['placement_status'] === 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['enable'] ?? 'Enable'); ?></option>
                                    <option value="0" <?php echo (int)$selectedPlacement['placement_status'] !== 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="ads_form_grid">
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['placement_desc'] ?? 'Description'); ?></label>
                                <textarea name="placement_desc" class="i_textarea" rows="2"><?php echo iN_HelpSecure($selectedPlacement['placement_desc']); ?></textarea>
                            </div>
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['inline_frequency'] ?? 'Inline frequency'); ?></label>
                                <input type="number" name="inline_frequency" class="i_input" min="0" max="100" value="<?php echo iN_HelpSecure($selectedPlacement['inline_frequency']); ?>">
                            </div>
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['inline_offset'] ?? 'Inline offset'); ?></label>
                                <input type="number" name="inline_offset" class="i_input" min="0" max="100" value="<?php echo iN_HelpSecure($selectedPlacement['inline_offset']); ?>">
                            </div>
                        </div>
                        <div class="ads_form_actions">
                            <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['save_edit']);?></button>
                            <button type="button" class="ghost_btn adsDeletePlacement" data-placement="<?php echo (int)$selectedPlacement['placement_id']; ?>"><?php echo iN_HelpSecure($LANG['delete'] ?? 'Delete'); ?></button>
                            <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully'] ?? 'Updated successfully'); ?></div>
                            <div class="warning_wrapper warning_one"><?php echo iN_HelpSecure($LANG['all_fields_must_be_filled'] ?? 'Please fill required fields.'); ?></div>
                        </div>
                    </form>

                    <div class="panel_header mt20"><?php echo iN_HelpSecure($LANG['ad_codes'] ?? 'Ad codes'); ?></div>
                    <form id="adsCreateCode" class="ads_form">
                        <input type="hidden" name="f" value="ads_create_code">
                        <input type="hidden" name="placement_id" value="<?php echo (int)$selectedPlacement['placement_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                        <div class="ads_form_grid">
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['provider_name'] ?? 'Provider'); ?></label>
                                <input type="text" name="provider_name" class="i_input" placeholder="Google AdSense / Ezoic / Media.net">
                            </div>
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['status']); ?></label>
                                <select name="status" class="i_input">
                                    <option value="1"><?php echo iN_HelpSecure($LANG['enable'] ?? 'Enable'); ?></option>
                                    <option value="0"><?php echo iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?></option>
                                </select>
                            </div>
                            <div class="ads_form_item">
                                <label><?php echo iN_HelpSecure($LANG['set_as_active'] ?? 'Set as active'); ?></label>
                                <select name="is_default" class="i_input">
                                    <option value="1"><?php echo iN_HelpSecure($LANG['yes'] ?? 'Yes'); ?></option>
                                    <option value="0"><?php echo iN_HelpSecure($LANG['no'] ?? 'No'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="ads_form_item">
                            <label><?php echo iN_HelpSecure($LANG['ad_code'] ?? 'Ad code'); ?></label>
                            <textarea name="code_snippet" class="i_textarea" rows="5" placeholder="<script>/* ad network code */</script>" required></textarea>
                            <div class="rec_not"><?php echo iN_HelpSecure($LANG['ad_code_desc'] ?? 'Paste the full HTML/JS snippet provided by the network. PHP code is stripped.'); ?></div>
                        </div>
                        <div class="ads_form_actions">
                            <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['add']); ?></button>
                            <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully'] ?? 'Updated successfully'); ?></div>
                            <div class="warning_wrapper warning_one"><?php echo iN_HelpSecure($LANG['all_fields_must_be_filled'] ?? 'Please fill required fields.'); ?></div>
                        </div>
                    </form>

                    <?php if (!empty($placementCodes)) { ?>
                        <div class="ads_codes_list">
                            <?php foreach ($placementCodes as $code) { ?>
                                <div class="ads_code_card">
                                    <div class="ads_code_header">
                                        <div>
                                            <div class="ads_key"><?php echo iN_HelpSecure($code['provider_name']); ?></div>
                                            <div class="ads_small"><?php echo iN_HelpSecure($LANG['updated'] ?? 'Updated'); ?>: <?php echo date('M d, Y H:i', (int)$code['updated_at']); ?></div>
                                        </div>
                                        <div class="ads_code_badges">
                                            <span class="status_badge <?php echo (int)$code['status'] === 1 ? 'on' : 'off'; ?>">
                                                <?php echo (int)$code['status'] === 1 ? iN_HelpSecure($LANG['enable'] ?? 'Enable') : iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?>
                                            </span>
                                            <?php if ((int)$code['is_default'] === 1) { ?>
                                                <span class="status_badge on"><?php echo iN_HelpSecure($LANG['active'] ?? 'Active'); ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <form class="ads_form ads_code_form" data-code="<?php echo (int)$code['code_id']; ?>">
                                        <input type="hidden" name="f" value="ads_update_code">
                                        <input type="hidden" name="code_id" value="<?php echo (int)$code['code_id']; ?>">
                                        <input type="hidden" name="placement_id" value="<?php echo (int)$selectedPlacement['placement_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
                                        <div class="ads_form_grid">
                                            <div class="ads_form_item">
                                                <label><?php echo iN_HelpSecure($LANG['provider_name'] ?? 'Provider'); ?></label>
                                                <input type="text" name="provider_name" class="i_input" value="<?php echo iN_HelpSecure($code['provider_name']); ?>">
                                            </div>
                                            <div class="ads_form_item">
                                                <label><?php echo iN_HelpSecure($LANG['status']); ?></label>
                                                <select name="status" class="i_input">
                                                    <option value="1" <?php echo (int)$code['status'] === 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['enable'] ?? 'Enable'); ?></option>
                                                    <option value="0" <?php echo (int)$code['status'] !== 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['disable'] ?? 'Disable'); ?></option>
                                                </select>
                                            </div>
                                            <div class="ads_form_item">
                                                <label><?php echo iN_HelpSecure($LANG['set_as_active'] ?? 'Set as active'); ?></label>
                                                <select name="is_default" class="i_input">
                                                    <option value="1" <?php echo (int)$code['is_default'] === 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['yes'] ?? 'Yes'); ?></option>
                                                    <option value="0" <?php echo (int)$code['is_default'] !== 1 ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['no'] ?? 'No'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="ads_form_item">
                                            <label><?php echo iN_HelpSecure($LANG['ad_code'] ?? 'Ad code'); ?></label>
                                            <textarea name="code_snippet" class="i_textarea" rows="4"><?php echo htmlspecialchars($code['code_snippet'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                        <div class="ads_form_actions">
                                            <button type="submit" class="i_nex_btn_btn transition adsUpdateCodeBtn"><?php echo iN_HelpSecure($LANG['save_edit']);?></button>
                                            <button type="button" class="ghost_btn adsSetActiveCode" data-code="<?php echo (int)$code['code_id']; ?>"><?php echo iN_HelpSecure($LANG['set_as_active'] ?? 'Set active'); ?></button>
                                            <button type="button" class="ghost_btn danger adsDeleteCode" data-code="<?php echo (int)$code['code_id']; ?>"><?php echo iN_HelpSecure($LANG['delete'] ?? 'Delete'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="ads_empty"><?php echo iN_HelpSecure($LANG['no_ad_codes'] ?? 'No ad codes yet. Add one above.'); ?></div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="ads_empty"><?php echo iN_HelpSecure($LANG['select_placement'] ?? 'Select a placement to edit ad codes.'); ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
