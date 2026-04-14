<?php
$scheduledLives = $iN->iN_GetScheduledLiveListAll(0);
if (!empty($scheduledLives)) {
    foreach ($scheduledLives as $liData) {
        include __DIR__ . '/scheduled_live_item.php';
    }
} else {
    echo '
    <div class="noPost scheduled_live_empty">
        <div class="noPostIcon">' . $iN->iN_SelectedMenuIcon('54') . '</div>
        <div class="noPostNote">' . iN_HelpSecure($LANG['live_scheduled_empty']) . '</div>
    </div>';
}
?>
