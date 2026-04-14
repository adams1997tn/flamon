<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');
$totalCampaigns = (int) DB::col("SELECT COUNT(*) FROM i_bulk_campaigns");
$totalPages = $paginationLimit > 0 ? (int) ceil($totalCampaigns / $paginationLimit) : 1;

if (isset($_GET["page-id"])) {
    $pagep = (int)$_GET["page-id"];
    if ($pagep <= 0) { $pagep = 1; }
} else {
    $pagep = 1;
}
$startFrom = ($pagep - 1) * $paginationLimit;

$campaigns = DB::all("SELECT * FROM i_bulk_campaigns ORDER BY bc_id DESC LIMIT $startFrom, $paginationLimit");
$workerBase = defined('APP_ROOT_PATH') ? APP_ROOT_PATH : dirname(__DIR__, 3);
$workerPath = rtrim((string)$workerBase, '/') . '/includes/bulk_campaign_worker.php';
$cliCmd = 'php ' . $workerPath;
$cronCmd = '* * * * * ' . $cliCmd . ' >/dev/null 2>&1';
$workerUrl = $base_url . 'includes/bulk_campaign_worker.php?token=YOUR_TOKEN';
$httpCmd = 'curl -fsS "' . $workerUrl . '" >/dev/null 2>&1';
$localCmd = 'while true; do ' . $cliCmd . '; sleep 15; done';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['bulk_messages']) . '(' . iN_HelpSecure($totalCampaigns) . ')'; ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <form id="bulkCampaignForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="f" value="bulkCreateCampaign">
                <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_target_type']); ?>
                    </div>
                    <div class="irow_box_right">
                        <select name="target_type" class="i_input">
                            <option value="all_users"><?php echo iN_HelpSecure($LANG['bulk_target_all_users']); ?></option>
                            <option value="subscribers_of_creator"><?php echo iN_HelpSecure($LANG['bulk_target_subscribers_creator']); ?></option>
                            <option value="subscribers_of_agency_creators"><?php echo iN_HelpSecure($LANG['bulk_target_subscribers_agency']); ?></option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_target_creator_id']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" name="target_creator_iuid_fk" class="i_input" placeholder="<?php echo iN_HelpSecure($LANG['bulk_target_creator_id']); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_target_agency_id']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" name="target_agency_id_fk" class="i_input" placeholder="<?php echo iN_HelpSecure($LANG['bulk_target_agency_id']); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_message_text']); ?>
                    </div>
                    <div class="irow_box_right">
                        <textarea name="message_text" class="i_textarea" placeholder="<?php echo iN_HelpSecure($LANG['bulk_message_text']); ?>"></textarea>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_attachment']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="file" name="bulk_attachment" class="i_input">
                        <div class="rec_not"><?php echo iN_HelpSecure($LANG['bulk_attachment_note']); ?></div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_private_message']); ?>
                    </div>
                    <div class="irow_box_right">
                        <select name="private_status" class="i_input">
                            <option value="0"><?php echo iN_HelpSecure($LANG['no']); ?></option>
                            <option value="1"><?php echo iN_HelpSecure($LANG['yes']); ?></option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_private_price']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="text" name="private_price" class="i_input" placeholder="<?php echo iN_HelpSecure($LANG['bulk_private_price']); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_">
                        <?php echo iN_HelpSecure($LANG['bulk_rate_limit']); ?>
                    </div>
                    <div class="irow_box_right">
                        <input type="number" name="rate_limit_per_run" min="1" max="500" class="i_input" value="25">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"></div>
                    <div class="irow_box_right">
                        <button type="submit" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['bulk_campaign_create']); ?></button>
                    </div>
                </div>
            </form>

            <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>

            <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                    <?php echo iN_HelpSecure($LANG['bulk_worker_title']); ?>
                </div>
                <div class="irow_box_right">
                    <div class="rec_not box_not_padding_top">
                        <div><strong><?php echo iN_HelpSecure($LANG['bulk_worker_cron_cli']); ?></strong></div>
                        <code><?php echo iN_HelpSecure($cronCmd); ?></code>
                    </div>
                    <div class="rec_not box_not_padding_top">
                        <div><strong><?php echo iN_HelpSecure($LANG['bulk_worker_cron_http']); ?></strong></div>
                        <code><?php echo iN_HelpSecure($httpCmd); ?></code>
                        <div><?php echo iN_HelpSecure($LANG['bulk_worker_token_note']); ?></div>
                    </div>
                    <div class="rec_not box_not_padding_top">
                        <div><strong><?php echo iN_HelpSecure($LANG['bulk_worker_local']); ?></strong></div>
                        <code><?php echo iN_HelpSecure($localCmd); ?></code>
                        <div><?php echo iN_HelpSecure($LANG['bulk_worker_local_note']); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($campaigns)) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['bulk_target_type']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['bulk_message_text']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['bulk_campaign_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['bulk_campaign_counts']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php foreach ($campaigns as $campaign) {
                            $campaignId = (int)($campaign['bc_id'] ?? 0);
                            $status = (string)($campaign['status'] ?? 'draft');
                            $messagePreview = substr((string)($campaign['message_text'] ?? ''), 0, 60);
                            $targetType = (string)($campaign['target_type'] ?? '');
                            $targetLabel = $LANG['bulk_target_all_users'];
                            if ($targetType === 'subscribers_of_creator') {
                                $targetLabel = $LANG['bulk_target_subscribers_creator'] . ' #' . (int)($campaign['target_creator_iuid_fk'] ?? 0);
                            } elseif ($targetType === 'subscribers_of_agency_creators') {
                                $targetLabel = $LANG['bulk_target_subscribers_agency'] . ' #' . (int)($campaign['target_agency_id_fk'] ?? 0);
                            }
                            $counts = DB::one(
                                "SELECT SUM(status = 'queued') AS queued_count, SUM(status = 'sent') AS sent_count, SUM(status = 'skipped') AS skipped_count, SUM(status = 'failed') AS failed_count FROM i_bulk_campaign_queue WHERE bc_id_fk = ?",
                                [$campaignId]
                            );
                            $queuedCount = (int)($counts['queued_count'] ?? 0);
                            $sentCount = (int)($counts['sent_count'] ?? 0);
                            $skippedCount = (int)($counts['skipped_count'] ?? 0);
                            $failedCount = (int)($counts['failed_count'] ?? 0);
                        ?>
                        <tr class="transition trhover">
                            <td><?php echo iN_HelpSecure($campaignId); ?></td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify sim truncated"><?php echo iN_HelpSecure($targetLabel); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify sim truncated"><?php echo iN_HelpSecure($messagePreview); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($LANG['bulk_campaign_status_' . $status] ?? $status); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify">
                                    <?php echo iN_HelpSecure($LANG['bulk_queue_queued']) . ': ' . iN_HelpSecure($queuedCount); ?>,
                                    <?php echo iN_HelpSecure($LANG['bulk_queue_sent']) . ': ' . iN_HelpSecure($sentCount); ?>,
                                    <?php echo iN_HelpSecure($LANG['bulk_queue_skipped']) . ': ' . iN_HelpSecure($skippedCount); ?>,
                                    <?php echo iN_HelpSecure($LANG['bulk_queue_failed']) . ': ' . iN_HelpSecure($failedCount); ?>
                                </div>
                            </td>
                            <td class="flex_ tabing_non_justify">
                                <?php if ($status === 'draft' || $status === 'paused') { ?>
                                    <div class="seePost c2 border_one transition tabing flex_ bulkBuildQueue" data-id="<?php echo iN_HelpSecure($campaignId); ?>">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['bulk_build_queue']); ?>
                                    </div>
                                <?php } ?>
                                <?php if ($status === 'queued' || $status === 'sending') { ?>
                                    <div class="seePost c2 border_one transition tabing flex_ bulkPause" data-id="<?php echo iN_HelpSecure($campaignId); ?>" data-status="paused">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')) . iN_HelpSecure($LANG['bulk_pause']); ?>
                                    </div>
                                <?php } ?>
                                <?php if (!in_array($status, ['completed', 'canceled'], true)) { ?>
                                    <div class="delu border_one transition tabing flex_ bulkCancel" data-id="<?php echo iN_HelpSecure($campaignId); ?>" data-status="canceled">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['bulk_cancel']); ?>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . html_entity_decode($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['bulk_no_campaigns'] . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages >= 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>admin/bulk_messages?page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
