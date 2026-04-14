<?php
$pollMaxOptions = isset($pollMaxOptions) ? (int)$pollMaxOptions : (isset($GLOBALS['pollMaxOptions']) ? (int)$GLOBALS['pollMaxOptions'] : 6);
$pollMinOptions = isset($pollMinOptions) ? (int)$pollMinOptions : (isset($GLOBALS['pollMinOptions']) ? (int)$GLOBALS['pollMinOptions'] : 2);
if ($pollMinOptions < 2) { $pollMinOptions = 2; }
if ($pollMaxOptions < $pollMinOptions) { $pollMaxOptions = $pollMinOptions; }
$pollId = $pollData['poll_id'] ?? 0;
$userVoted = !empty($pollData['user_vote']);
?>
<div class="live_module live_poll_module is-open"
     data-live-id="<?php echo iN_HelpSecure($liveID); ?>"
     data-max-options="<?php echo iN_HelpSecure($pollMaxOptions); ?>"
     data-option-placeholder="<?php echo iN_HelpSecure($LANG['live_poll_option_placeholder']); ?>"
     data-error-question="<?php echo iN_HelpSecure($LANG['live_poll_question_required']); ?>"
     data-error-options-min="<?php echo iN_HelpSecure($LANG['live_poll_options_min']); ?>"
     data-error-options-max="<?php echo iN_HelpSecure($LANG['live_poll_options_max']); ?>"
     data-error-not-allowed="<?php echo iN_HelpSecure($LANG['live_poll_not_allowed']); ?>"
     data-error-feature-disabled="<?php echo iN_HelpSecure($LANG['live_feature_disabled']); ?>"
     data-error-create-failed="<?php echo iN_HelpSecure($LANG['live_poll_create_failed']); ?>">
    <div class="live_module_header">
        <div class="live_module_title"><?php echo iN_HelpSecure($LANG['live_poll_title']); ?></div>
        <button type="button" class="live_module_toggle" data-target="poll">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?>
        </button>
    </div>
    <div class="live_module_body">
        <?php if (!empty($pollData)) { ?>
            <div class="live_poll_question"><?php echo iN_HelpSecure($pollData['question'] ?? ''); ?></div>
            <div class="live_poll_options">
                <?php foreach ($pollData['options'] as $option) { ?>
                    <?php
                    $isSelected = $userVoted && (int)$pollData['user_vote'] === (int)$option['option_id'];
                    $percent = (int)($option['percent'] ?? 0);
                    ?>
                    <button type="button"
                            class="live_poll_option<?php echo $isSelected ? ' is-selected' : ''; ?><?php echo $userVoted ? ' is-locked' : ''; ?>"
                            data-poll-id="<?php echo iN_HelpSecure($pollId); ?>"
                            data-option-id="<?php echo iN_HelpSecure($option['option_id']); ?>"
                            <?php echo $userVoted ? 'disabled' : ''; ?>>
                        <span class="live_poll_option_text"><?php echo iN_HelpSecure($option['option_text']); ?></span>
                        <span class="live_poll_option_percent"><?php echo iN_HelpSecure($percent); ?>%</span>
                        <span class="live_poll_option_bar" style="width: <?php echo iN_HelpSecure($percent); ?>%;"></span>
                    </button>
                <?php } ?>
            </div>
            <div class="live_poll_meta">
                <?php echo iN_HelpSecure($pollData['total_votes'] ?? 0); ?> <?php echo iN_HelpSecure($LANG['live_poll_votes']); ?>
            </div>
            <?php if (!empty($isLiveCreator)) { ?>
                <button type="button" class="live_poll_close_btn" data-poll-id="<?php echo iN_HelpSecure($pollId); ?>" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
                    <?php echo iN_HelpSecure($LANG['live_poll_close']); ?>
                </button>
            <?php } ?>
        <?php } elseif (!empty($isLiveCreator)) { ?>
            <div class="live_poll_create">
                <div class="live_poll_create_title"><?php echo iN_HelpSecure($LANG['live_poll_create']); ?></div>
                <input type="text" class="live_poll_question_input" placeholder="<?php echo iN_HelpSecure($LANG['live_poll_question_placeholder']); ?>">
                <div class="live_poll_options_inputs">
                    <input type="text" class="live_poll_option_input" placeholder="<?php echo iN_HelpSecure($LANG['live_poll_option_placeholder']); ?>">
                    <input type="text" class="live_poll_option_input" placeholder="<?php echo iN_HelpSecure($LANG['live_poll_option_placeholder']); ?>">
                </div>
                <div class="live_poll_create_actions">
                    <button type="button" class="live_poll_add_option"><?php echo iN_HelpSecure($LANG['live_poll_add_option']); ?></button>
                    <button type="button" class="live_poll_submit" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
                        <?php echo iN_HelpSecure($LANG['live_poll_publish']); ?>
                    </button>
                </div>
                <div class="live_poll_warning"></div>
            </div>
        <?php } else { ?>
            <div class="live_poll_empty"><?php echo iN_HelpSecure($LANG['live_poll_empty']); ?></div>
        <?php } ?>
    </div>
</div>
