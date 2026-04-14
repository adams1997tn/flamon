<?php
$hasQuestions = !empty($questions);
?>
<div class="live_module live_qa_module is-open" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
    <div class="live_module_header">
        <div class="live_module_title"><?php echo iN_HelpSecure($LANG['live_qa_title']); ?></div>
        <button type="button" class="live_module_toggle" data-target="qa">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?>
        </button>
    </div>
    <div class="live_module_body">
        <div class="live_qa_input">
            <textarea class="live_qa_textarea" placeholder="<?php echo iN_HelpSecure($LANG['live_qa_placeholder']); ?>"></textarea>
            <button type="button" class="live_qa_send" data-live-id="<?php echo iN_HelpSecure($liveID); ?>">
                <?php echo iN_HelpSecure($LANG['live_qa_send']); ?>
            </button>
        </div>
        <?php if ($hasQuestions) { ?>
            <div class="live_qa_list">
                <?php foreach ($questions as $q) { ?>
                    <?php
                    $qId = (int)($q['question_id'] ?? 0);
                    $qText = $q['question_text'] ?? '';
                    $qStatus = $q['status'] ?? 'approved';
                    $qUserName = $q['i_username'] ?? '';
                    $qUserFullName = $q['i_user_fullname'] ?? '';
                    $qDisplayName = $fullnameorusername === 'no' ? $qUserName : $qUserFullName;
                    ?>
                    <div class="live_qa_item status-<?php echo iN_HelpSecure($qStatus); ?>" data-question-id="<?php echo iN_HelpSecure($qId); ?>">
                        <div class="live_qa_author"><?php echo iN_HelpSecure($qDisplayName); ?></div>
                        <div class="live_qa_text"><?php echo iN_HelpSecure($qText); ?></div>
                        <div class="live_qa_status">
                            <?php
                            if ($qStatus === 'pending') {
                                echo iN_HelpSecure($LANG['live_qa_pending']);
                            } elseif ($qStatus === 'answered') {
                                echo iN_HelpSecure($LANG['live_qa_answered']);
                            } else {
                                echo iN_HelpSecure($LANG['live_qa_approved']);
                            }
                            ?>
                        </div>
                        <?php if (!empty($isLiveCreator) && $qStatus === 'pending') { ?>
                            <div class="live_qa_actions">
                                <button type="button" class="live_qa_action" data-status="approved" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-question-id="<?php echo iN_HelpSecure($qId); ?>">
                                    <?php echo iN_HelpSecure($LANG['live_qa_approve']); ?>
                                </button>
                                <button type="button" class="live_qa_action" data-status="answered" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-question-id="<?php echo iN_HelpSecure($qId); ?>">
                                    <?php echo iN_HelpSecure($LANG['live_qa_mark_answered']); ?>
                                </button>
                                <button type="button" class="live_qa_action" data-status="rejected" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-question-id="<?php echo iN_HelpSecure($qId); ?>">
                                    <?php echo iN_HelpSecure($LANG['live_qa_reject']); ?>
                                </button>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="live_qa_empty"><?php echo iN_HelpSecure($LANG['live_qa_empty']); ?></div>
        <?php } ?>
    </div>
</div>
