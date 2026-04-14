<div class="scheduled_live_page">
    <div class="scheduled_live_header">
        <?php if ($logedIn === '1') { ?>
            <div class="scheduled_live_page_actions">
                <?php if ($paidLiveStreamingStatus === '1' && $feesStatus === '2') { ?>
                    <div class="new_s_one new_s_first cNLive" data-type="paidLive">
                        <div class="flex_ alignItem">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91')); ?>
                            <?php echo iN_HelpSecure($LANG['start_new_paid_live_stream']); ?>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($freeLiveStreamingStatus === '1') { ?>
                    <div class="new_s_one new_s_second cNLive" data-type="freeLive">
                        <div class="flex_ alignItem">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('91')); ?>
                            <?php echo iN_HelpSecure($LANG['start_new_free_live_stream']); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="scheduled_live_title_card">
            <div class="live_title_page flex_">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('133')); ?>
                <?php echo iN_HelpSecure($LANG['live_scheduled_list_title']); ?>
            </div>
        </div>
    </div>

    <div class="scheduled_live_streamings_list_container">
        <?php include __DIR__ . '/scheduled_live_list.php'; ?>
    </div>
</div>

<script>
    window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
    window.LANG_LIVE_STARTS_SOON = "<?php echo iN_HelpSecure($LANG['live_starting_soon']); ?>";
    window.LANG_LIVE_SCHEDULED_EMPTY = "<?php echo iN_HelpSecure($LANG['live_scheduled_empty']); ?>";
    window.LANG_LIVE_SCHEDULED_DELETE_CONFIRM = "<?php echo iN_HelpSecure($LANG['live_scheduled_delete_confirm']); ?>";
</script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/liveScheduledList.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
