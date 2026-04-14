<?php
$followersList = isset($followersList) && is_array($followersList) ? $followersList : [];
$subscribersList = isset($subscribersList) && is_array($subscribersList) ? $subscribersList : [];
$selectedMap = isset($selectedMap) && is_array($selectedMap) ? $selectedMap : [];
$audienceList = [];
$audienceIds = [];

foreach ([$followersList, $subscribersList] as $sourceList) {
    if (empty($sourceList)) {
        continue;
    }
    foreach ($sourceList as $row) {
        $id = (int)($row['iuid'] ?? 0);
        if ($id <= 0 || isset($audienceIds[$id])) {
            continue;
        }
        $audienceIds[$id] = true;
        $audienceList[] = $row;
    }
}

if (!empty($audienceList)) {
    usort($audienceList, function ($left, $right) use ($fullnameorusername) {
        $leftUser = (string)($left['i_username'] ?? '');
        $leftFull = (string)($left['i_user_fullname'] ?? '');
        $leftDisplay = $fullnameorusername === 'no' ? $leftUser : ($leftFull !== '' ? $leftFull : $leftUser);

        $rightUser = (string)($right['i_username'] ?? '');
        $rightFull = (string)($right['i_user_fullname'] ?? '');
        $rightDisplay = $fullnameorusername === 'no' ? $rightUser : ($rightFull !== '' ? $rightFull : $rightUser);

        $cmp = strcasecmp($leftDisplay, $rightDisplay);
        if ($cmp !== 0) {
            return $cmp;
        }
        return strcasecmp($leftUser, $rightUser);
    });
}

$hasAudience = !empty($audienceList);
?>
<div class="i_modal_bg_in live_notify_modal" role="dialog" aria-modal="true" aria-labelledby="liveNotifyAudienceTitle">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header" id="liveNotifyAudienceTitle">
                <?php echo iN_HelpSecure($LANG['live_notify_select_title']); ?>
                <div class="live_notify_close transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['close'] ?? 'Close'); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>

            <div class="live_notify_modal_body">
                <div class="live_notify_search">
                    <span class="live_notify_search_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?></span>
                    <input type="text"
                           class="live_notify_search_input"
                           placeholder="<?php echo iN_HelpSecure($LANG['search'] ?? 'Search'); ?>"
                           aria-label="<?php echo iN_HelpSecure($LANG['search'] ?? 'Search'); ?>">
                </div>

                <?php if (!$hasAudience) { ?>
                    <div class="live_notify_empty">
                        <?php echo iN_HelpSecure($LANG['live_notify_select_empty']); ?>
                    </div>
                <?php } ?>

                <?php if ($hasAudience) { ?>
                    <div class="live_notify_grid">
                        <?php foreach ($audienceList as $person) {
                            $personId = (int)($person['iuid'] ?? 0);
                            if ($personId <= 0) {
                                continue;
                            }
                            $personUserName = (string)($person['i_username'] ?? '');
                            $personFullName = (string)($person['i_user_fullname'] ?? '');
                            $displayName = $fullnameorusername === 'no' ? $personUserName : ($personFullName !== '' ? $personFullName : $personUserName);
                            $avatar = $iN->iN_UserAvatar($personId, $base_url);
                            $checked = isset($selectedMap[$personId]) ? 'checked="checked"' : '';
                        ?>
                            <label class="live_notify_user_card"
                                   data-name="<?php echo iN_HelpSecure($displayName); ?>"
                                   data-username="<?php echo iN_HelpSecure($personUserName); ?>">
                                <input type="checkbox"
                                       class="live_notify_user_checkbox"
                                       value="<?php echo $personId; ?>"
                                       aria-label="<?php echo iN_HelpSecure($displayName); ?>"
                                       <?php echo $checked; ?>>
                                <span class="live_notify_avatar">
                                    <img src="<?php echo iN_HelpSecure($avatar); ?>" alt="<?php echo iN_HelpSecure($displayName); ?>">
                                    <span class="live_notify_check" aria-hidden="true"></span>
                                </span>
                                <span class="live_notify_user_name"><?php echo iN_HelpSecure($displayName); ?></span>
                            </label>
                        <?php } ?>
                    </div>
                    <div class="live_notify_search_empty" aria-live="polite">
                        <?php echo iN_HelpSecure($LANG['live_notify_search_empty']); ?>
                    </div>
                <?php } ?>
            </div>

            <div class="i_block_box_footer_container">
                <div class="alertBtnRightWithIcon live_notify_apply transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['live_notify_select_done']); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91')); ?>
                    <?php echo iN_HelpSecure($LANG['live_notify_select_done']); ?>
                </div>
                <div class="alertBtnLeft live_notify_cancel transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
