<?php if (!empty($scheduledLives)) { ?>
    <div class="profile_scheduled_lives_card">
        <div class="profile_scheduled_lives_header">
            <div class="profile_scheduled_lives_title"><?php echo iN_HelpSecure($LANG['live_scheduled_profile_title']); ?></div>
            <a class="profile_scheduled_lives_link" href="<?php echo iN_HelpSecure($base_url); ?>live_streams?live=scheduled">
                <?php echo iN_HelpSecure($LANG['live_scheduled_view_page']); ?>
            </a>
        </div>
        <div class="profile_scheduled_lives_list">
            <?php foreach ($scheduledLives as $liData) { include __DIR__ . '/profile_scheduled_live_item.php'; } ?>
        </div>
    </div>
    <script>
        window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
        window.LANG_LIVE_STARTS_SOON = "<?php echo iN_HelpSecure($LANG['live_starting_soon']); ?>";
        window.LANG_LIVE_SCHEDULED_EMPTY = "<?php echo iN_HelpSecure($LANG['live_scheduled_empty']); ?>";
        window.LANG_LIVE_SCHEDULED_DELETE_CONFIRM = "<?php echo iN_HelpSecure($LANG['live_scheduled_delete_confirm']); ?>";
    </script>
    <script type="text/javascript" src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/liveScheduledList.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<?php } ?>
