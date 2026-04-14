<?php
$genderConfigLines = [];
$genderOptionsList = isset($genderOptionsFull) && is_array($genderOptionsFull) ? $genderOptionsFull : $genderOptions;
foreach ($genderOptionsList as $option) {
    $key = $option['key'] ?? '';
    $label = $option['label'] ?? ucfirst($key);
    $icon = $option['icon'] ?? '';
    $status = isset($option['status']) ? (string)$option['status'] : '1';
    $status = ($status === '0') ? '0' : '1';
    $genderConfigLines[] = $key . '|' . $label . '|' . $icon . '|' . $status;
}
$genderConfigText = implode("\n", $genderConfigLines);
$genderPlaceholder = htmlspecialchars($LANG['gender_settings_placeholder'] ?? 'female|Female|13', ENT_QUOTES, 'UTF-8');
?>
<div class="i_contents_container gender-settings-page">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['gender_settings']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <form method="post" id="genderOptionsForm" class="gender-settings-form">
                <div class="gender-settings-shell">
                    <div class="i_general_row_box_item flex_ column tabing__justify gender-settings-editor">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['gender_settings']); ?></div>
                        <div class="irow_box_right">
                            <textarea name="gender_options" class="i_textarea gender-settings-textarea" rows="10" placeholder="<?php echo $genderPlaceholder; ?>"><?php echo iN_SecureTextareaOutput($genderConfigText); ?></textarea>
                            <div class="gender-settings-meta">
                                <div class="rec_not box_not_padding_left"><?php echo html_entity_decode($LANG['gender_settings_desc']); ?></div>
                                <div class="rec_not box_not_padding_left"><?php echo html_entity_decode($LANG['gender_settings_hint']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($genderOptionsList)) { ?>
                    <div class="i_general_row_box_item flex_ column tabing__justify gender-settings-preview">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['gender_settings']); ?></div>
                        <div class="irow_box_right irow_box_right_style">
                            <div class="gender-option-grid">
                                <?php foreach ($genderOptionsList as $option) {
                                    $optionKey = trim((string)($option['key'] ?? ''));
                                    $optionLabel = trim((string)($option['label'] ?? ucfirst($optionKey)));
                                    $optionIcon = trim((string)($option['icon'] ?? ''));
                                    $optionStatus = isset($option['status']) && (string)$option['status'] === '0' ? '0' : '1';
                                    $optionItemClass = $optionStatus === '1' ? 'gender-option-item is-enabled' : 'gender-option-item is-disabled';
                                    ?>
                                <div class="<?php echo iN_HelpSecure($optionItemClass); ?>">
                                    <div class="gender-option-item__icon">
                                        <?php
                                        if ($optionIcon !== '' && ctype_digit($optionIcon)) {
                                            echo html_entity_decode($iN->iN_SelectedMenuIcon($optionIcon));
                                        } else {
                                            $fallbackChar = strtoupper(substr($optionLabel !== '' ? $optionLabel : $optionKey, 0, 1));
                                            ?>
                                            <span class="gender-option-item__icon-txt"><?php echo iN_HelpSecure($fallbackChar); ?></span>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="gender-option-item__content">
                                        <div class="gender-option-item__label"><?php echo iN_HelpSecure($optionLabel); ?></div>
                                        <div class="gender-option-item__key"><?php echo iN_HelpSecure($optionKey); ?></div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="warning_wrapper warning_invalid_genders"><?php echo iN_HelpSecure($LANG['gender_settings_invalid']); ?></div>
                <div class="i_settings_wrapper_item successNot"><?php echo iN_HelpSecure($LANG['updated_successfully']); ?></div>
                <div class="i_general_row_box_item flex_ tabing_non_justify gender-settings-actions">
                    <input type="hidden" name="f" value="updateGenderOptions">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateGeneralSettings">
                        <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
