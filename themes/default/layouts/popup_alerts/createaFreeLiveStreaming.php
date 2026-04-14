<div class="i_modal_bg_in" role="dialog" aria-modal="true" aria-labelledby="createFreeLiveTitle">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <?php
            $currentDateNumber = '1';
            $finishDateNumber = '2';
            if ($l_Time) {
                $currentDateNumber = date('d', $currentTime);
                $finishDateNumber = date('d', $l_Time);
            }

            if ($l_Time && $currentDateNumber == $finishDateNumber) {
                if ($currentTime > $l_Time) {
            ?>
                    <!-- Modal Header -->
                    <div class="i_modal_g_header" id="createFreeLiveTitle">
                        <?php echo iN_HelpSecure($LANG['create_a_free_live_streaming']); ?>
                        <div class="shareClose transition" role="button" aria-label="Close">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                        </div>
                    </div>
                    <!-- /Modal Header -->

                    <!-- Modal Body -->
                    <div class="i_more_text_wrapper">
                        <?php if ($currentDateNumber == $finishDateNumber) { ?>
                            <?php echo iN_HelpSecure($LANG['filled_daily_live_broadcast']); ?>
                        <?php } else { ?>
                            <div class="give_a_name"><?php echo iN_HelpSecure($LANG['give_this_live_stream_a_name']); ?></div>
                            <div class="i_live_c_item">
                                <input type="text" name="liveName" id="liveName" class="flnm" aria-label="<?php echo iN_HelpSecure($LANG['give_this_live_stream_a_name']); ?>">
                            </div>
                            <div class="free_live_not flex_ alignItem">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?>
                                <?php echo iN_HelpSecure($LANG['free_live_not']); ?>
                            </div>
                        <?php } ?>
                    </div>
                    <!-- /Modal Body -->

                    <!-- Modal Footer -->
                    <div class="i_block_box_footer_container">
                        <div class="alertBtnLeft no-del transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                            <?php echo iN_HelpSecure($LANG['cancel']); ?>
                        </div>
                    </div>
                    <!-- /Modal Footer -->
                <?php
                } else {
                ?>
                    <!-- Modal Header -->
                    <div class="i_modal_g_header" id="createFreeLiveTitle">
                        <?php echo iN_HelpSecure($LANG['create_a_free_live_streaming']); ?>
                        <div class="shareClose transition" role="button" aria-label="Close">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                        </div>
                    </div>
                    <!-- /Modal Header -->

                    <!-- Modal Body -->
                    <div class="i_more_text_wrapper">
                        <?php echo iN_HelpSecure($LANG['already_created_live_breadcast']); ?>
                    </div>

                    <!-- Modal Footer -->
                    <div class="i_block_box_footer_container">
                        <a href="<?php echo iN_HelpSecure($base_url . 'live/' . $userName); ?>">
                            <div class="alertBtnRightWithIcon continue transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['continue']); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                                <?php echo iN_HelpSecure($LANG['continue']); ?>
                            </div>
                        </a>
                        <div class="alertBtnLeft no-del transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                            <?php echo iN_HelpSecure($LANG['cancel']); ?>
                        </div>
                    </div>
                <?php
                }
            } else {
                ?>
                <!-- Modal Header -->
                <div class="i_modal_g_header" id="createFreeLiveTitle">
                    <?php echo iN_HelpSecure($LANG['create_a_free_live_streaming']); ?>
                    <div class="shareClose transition" role="button" aria-label="Close">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                    </div>
                </div>
                <!-- /Modal Header -->

                <!-- Modal Body -->
                <div class="i_more_text_wrapper">
                    <div class="give_a_name"><?php echo iN_HelpSecure($LANG['give_this_live_stream_a_name']); ?></div>
                    <div class="i_live_c_item">
                        <input type="text" name="liveName" id="liveName" class="flnm" aria-label="<?php echo iN_HelpSecure($LANG['give_this_live_stream_a_name']); ?>">
                    </div>
                    <div class="free_live_not flex_ alignItem">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?>
                        <?php echo iN_HelpSecure($LANG['free_live_not']); ?>
                    </div>
                    <div class="i_sub_not_check i_pref">
                        <?php echo iN_HelpSecure($LANG['live_notify_label']); ?>
                        <div class="i_sub_not_check_box">
                            <label class="el-switch el-switch-yellow">
                                <input type="checkbox" name="live_notify_toggle" class="live_notify_toggle" value="1">
                                <span class="el-switch-style"></span>
                            </label>
                        </div>
                    </div>
                    <div class="give_a_name"><?php echo iN_HelpSecure($LANG['live_notify_audience_label']); ?></div>
                    <div class="i_live_c_item">
                        <select name="live_notify_audience" class="inora_user_input live_notify_audience" disabled aria-disabled="true">
                            <option value="followers"><?php echo iN_HelpSecure($LANG['wfollowers']); ?></option>
                            <option value="subscribers"><?php echo iN_HelpSecure($LANG['wsubscribers']); ?></option>
                            <option value="selected"><?php echo iN_HelpSecure($LANG['live_notify_audience_selected']); ?></option>
                        </select>
                    </div>
                    <div class="live_notify_select_tools">
                        <button type="button" class="i_nex_btn_btn transition live_notify_select_btn" disabled>
                            <?php echo iN_HelpSecure($LANG['live_notify_select_button']); ?>
                        </button>
                        <div class="live_notify_selected_hint" data-empty-text="<?php echo iN_HelpSecure($LANG['live_notify_selected_empty']); ?>" data-count-text="<?php echo iN_HelpSecure($LANG['live_notify_selected_count']); ?>">
                            <?php echo iN_HelpSecure($LANG['live_notify_selected_empty']); ?>
                        </div>
                        <input type="hidden" name="live_notify_selected_ids" class="live_notify_selected_ids" value="">
                    </div>
                    <div class="i_sub_not_check i_pref">
                        <?php echo iN_HelpSecure($LANG['live_schedule_label']); ?>
                        <div class="i_sub_not_check_box">
                            <label class="el-switch el-switch-yellow">
                                <input type="checkbox" name="live_schedule_toggle" class="live_schedule_toggle" value="1">
                                <span class="el-switch-style"></span>
                            </label>
                        </div>
                    </div>
                    <div class="live_schedule_fields" data-max-days="<?php echo iN_HelpSecure($scheduledMaxDelayDays ?? 30); ?>">
                        <div class="schedule_dt_inputs">
                            <label class="schedule_dt_field">
                                <span><?php echo iN_HelpSecure($LANG['schedule_time']); ?></span>
                            <div class="schedule_dt_group">
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['day'] ?? 'Day'); ?></div>
                                    <select class="live_schedule_date"></select>
                                </div>
                            </div>
                            </label>
                            <label class="schedule_dt_field">
                                <span><?php echo iN_HelpSecure($LANG['time'] ?? 'Time'); ?></span>
                                <div class="schedule_dt_group">
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['hour'] ?? 'Hour'); ?></div>
                                    <select class="live_schedule_hour"></select>
                                </div>
                                <div class="schedule_dt_item">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['minute'] ?? 'Minute'); ?></div>
                                    <select class="live_schedule_minute"></select>
                                </div>
                            </div>
                                <div class="schedule_time_note"><?php echo iN_HelpSecure($LANG['time_format_hint'] ?? '24-hour format.'); ?></div>
                            </label>
                        </div>
                        <div class="schedule_info">
                            <div><?php echo iN_HelpSecure($LANG['live_schedule_note']); ?></div>
                            <div class="schedule_tz"><?php echo iN_HelpSecure($LANG['timezone'] ?? 'Timezone'); ?>: <?php echo date_default_timezone_get(); ?></div>
                        </div>
                        <div class="schedule_hint">
                            <?php echo preg_replace('/\{time\}/', iN_HelpSecure($scheduledMaxDelayDays), $LANG['scheduled_for']); ?>
                        </div>
                    </div>
                    <?php echo html_entity_decode($liveStreamNotForNonCreators); ?>
                    <div class="box_not warning_required require"><?php echo iN_HelpSecure($LANG['enter_live_stream_title']); ?></div>
                    <div class="box_not warning_required name_short"><?php echo iN_HelpSecure($LANG['stream_name_wrning']); ?></div>
                    <div class="box_not warning_required live_notify_selected_warning"><?php echo iN_HelpSecure($LANG['live_notify_selected_required']); ?></div>
                    <div class="box_not warning_required live_schedule_required"><?php echo iN_HelpSecure($LANG['live_schedule_required']); ?></div>
                    <div class="box_not warning_required live_schedule_invalid"><?php echo iN_HelpSecure($LANG['live_schedule_invalid']); ?></div>
                    <div class="box_not warning_required live_schedule_limit"><?php echo iN_HelpSecure($LANG['live_schedule_limit']); ?></div>
                    <div class="box_not warning_required live_schedule_disabled"><?php echo iN_HelpSecure($LANG['live_schedule_disabled']); ?></div>
                    <div class="box_not warning_required live_exists_warning"><?php echo iN_HelpSecure($LANG['live_exists_warning']); ?></div>
                    <div class="box_not warning_required live_csrf_warning"><?php echo iN_HelpSecure($LANG['live_stream_invalid_csrf']); ?></div>
                </div>
                <!-- /Modal Body -->

                <!-- Modal Footer -->
                <div class="i_block_box_footer_container">
                    <div class="alertBtnRightWithIcon createLiveStream transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['create']); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91')); ?>
                        <?php echo iN_HelpSecure($LANG['create']); ?>
                    </div>
                    <div class="alertBtnLeft no-del transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                        <?php echo iN_HelpSecure($LANG['cancel']); ?>
                    </div>
                </div>
                <!-- /Modal Footer -->
            <?php } ?>
        </div>
    </div>
</div>
