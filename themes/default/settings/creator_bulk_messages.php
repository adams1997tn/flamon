<?php
if ($iN->iN_CheckUserIsCreator($userID) != 1) {
    header('Location: ' . route_url('404'));
    exit;
}

$creatorBulkEnabled = $iN->iN_IsCreatorBulkEnabled();
$ffmpegEnabled = (isset($ffmpegStatus) && (string)$ffmpegStatus === '1' && !empty($ffmpegPath));
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');

if ($creatorBulkEnabled) {
    $totalCampaigns = (int) DB::col("SELECT COUNT(*) FROM i_bulk_campaigns WHERE created_by_iuid_fk = ?", [$userID]);
    $totalPages = $paginationLimit > 0 ? (int) ceil($totalCampaigns / $paginationLimit) : 1;
    $pagep = isset($_GET["page-id"]) ? (int) $_GET["page-id"] : 1;
    if ($pagep <= 0) { $pagep = 1; }
    $startFrom = ($pagep - 1) * $paginationLimit;
    $campaigns = DB::all(
        "SELECT * FROM i_bulk_campaigns WHERE created_by_iuid_fk = ? ORDER BY bc_id DESC LIMIT $startFrom, $paginationLimit",
        [$userID]
    );
}
?>
<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_">
         <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92'));?>
         <?php echo iN_HelpSecure($LANG['creator_bulk_messages_title']);?>
       </div>
    </div>
    <div class="i_settings_wrapper_items">
      <?php if (!$creatorBulkEnabled) { ?>
        <div class="i_settings_wrapper_item">
          <div class="box_not"><?php echo iN_HelpSecure($LANG['creator_bulk_feature_disabled']);?></div>
        </div>
      <?php } else { ?>
      <form id="creatorBulkCampaignForm" method="post" enctype="multipart/form-data" data-ffmpeg="<?php echo $ffmpegEnabled ? '1' : '0'; ?>">
        <div class="i_warning creator_bulk_form_notice"></div>
        <input type="hidden" name="f" value="creatorBulkCreateCampaign">
        <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['bulk_target_type']);?></div>
          <div class="i_settings_item_title_for">
            <select name="target_type" class="flnm creator_bulk_select">
              <option value="followers_of_creator"><?php echo iN_HelpSecure($LANG['creator_bulk_target_followers']);?></option>
              <option value="subscribers_of_creator"><?php echo iN_HelpSecure($LANG['creator_bulk_target_subscribers']);?></option>
            </select>
          </div>
        </div>

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['bulk_message_text']);?></div>
          <div class="i_settings_item_title_for">
            <textarea name="message_text" class="description_" placeholder="<?php echo iN_HelpSecure($LANG['bulk_message_text']);?>"></textarea>
          </div>
        </div>

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['bulk_attachment']);?></div>
          <div class="i_settings_item_title_for">
            <div class="creator_bulk_file_wrap">
              <label class="creator_bulk_file_label">
                <span class="creator_bulk_file_text" data-default="<?php echo iN_HelpSecure($LANG['creator_bulk_choose_file']);?>">
                  <?php echo iN_HelpSecure($LANG['creator_bulk_choose_file']);?>
                </span>
                <input type="file" name="bulk_attachment" class="creator_bulk_file_input" accept="image/*,video/*,audio/*">
              </label>
            </div>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['bulk_attachment_note']);?></div>
          </div>
        </div>

        <div class="i_settings_wrapper_item creator_bulk_thumb_field creator_bulk_thumb_hidden">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['creator_bulk_video_thumbnail']);?></div>
          <div class="i_settings_item_title_for">
            <div class="creator_bulk_file_wrap">
              <label class="creator_bulk_file_label">
                <span class="creator_bulk_file_text" data-default="<?php echo iN_HelpSecure($LANG['creator_bulk_choose_thumbnail']);?>">
                  <?php echo iN_HelpSecure($LANG['creator_bulk_choose_thumbnail']);?>
                </span>
                <input type="file" name="bulk_video_thumbnail" class="creator_bulk_file_input creator_bulk_thumb_input" accept="image/jpeg,image/jpg">
              </label>
            </div>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['creator_bulk_thumbnail_note']);?></div>
          </div>
        </div>

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['bulk_private_message']);?></div>
          <div class="i_settings_item_title_for">
            <select name="private_status" class="flnm creator_bulk_select">
              <option value="0"><?php echo iN_HelpSecure($LANG['no']);?></option>
              <option value="1"><?php echo iN_HelpSecure($LANG['yes']);?></option>
            </select>
          </div>
        </div>

        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['bulk_private_price']);?></div>
          <div class="i_settings_item_title_for">
            <input type="text" name="private_price" class="flnm creator_bulk_price_input" inputmode="decimal" placeholder="<?php echo iN_HelpSecure($LANG['bulk_private_price']);?>">
          </div>
        </div>

        <div class="i_become_creator_box_footer">
          <button type="submit" class="i_nex_btn_btn transition"><?php echo iN_HelpSecure($LANG['creator_bulk_create_button']);?></button>
        </div>
      </form>

      <?php if (!empty($campaigns)) { ?>
        <div class="i_overflow_x_auto">
          <div class="i_tab_container">
            <div class="i_tab_header flex_">
              <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['id']); ?></div>
              <div class="tab_item"><?php echo iN_HelpSecure($LANG['bulk_target_type']); ?></div>
              <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['bulk_message_text']); ?></div>
              <div class="tab_item"><?php echo iN_HelpSecure($LANG['createdat']); ?></div>
              <div class="tab_item"><?php echo iN_HelpSecure($LANG['bulk_attachment']); ?></div>
              <div class="tab_item"><?php echo iN_HelpSecure($LANG['creator_bulk_status_label']); ?></div>
              <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['bulk_campaign_counts']); ?></div>
              <div class="tab_item"><?php echo iN_HelpSecure($LANG['actions']); ?></div>
            </div>
            <div class="i_tab_list_item_container">
            <?php foreach ($campaigns as $campaign) {
              $campaignId = (int)($campaign['bc_id'] ?? 0);
              $status = (string)($campaign['status'] ?? 'draft');
              $messagePreview = substr((string)($campaign['message_text'] ?? ''), 0, 60);
              $targetType = (string)($campaign['target_type'] ?? '');
              $createdAt = (int)($campaign['created_at'] ?? 0);
              $createdAtText = $createdAt > 0 ? date('d/m/Y H:i', $createdAt) : '-';
              $attachmentPath = isset($campaign['attachment_path']) ? trim((string)$campaign['attachment_path']) : '';
              $attachmentLabel = $LANG['creator_bulk_attachment_none'];
              $attachmentClass = 'creator_bulk_attachment_none';
              if ($attachmentPath !== '') {
                $extension = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                $videoExtensions = ['mp4', 'mov', 'webm', 'mkv', 'avi', 'mpeg', 'mpg', '3gp'];
                $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
                if (in_array($extension, $imageExtensions, true)) {
                  $attachmentLabel = $LANG['creator_bulk_attachment_image'];
                  $attachmentClass = 'creator_bulk_attachment_image';
                } elseif (in_array($extension, $videoExtensions, true)) {
                  $attachmentLabel = $LANG['creator_bulk_attachment_video'];
                  $attachmentClass = 'creator_bulk_attachment_video';
                } elseif (in_array($extension, $audioExtensions, true)) {
                  $attachmentLabel = $LANG['creator_bulk_attachment_audio'];
                  $attachmentClass = 'creator_bulk_attachment_audio';
                } else {
                  $attachmentLabel = $LANG['creator_bulk_attachment_file'];
                  $attachmentClass = 'creator_bulk_attachment_file';
                }
              }
              $targetLabel = $LANG['creator_bulk_target_followers'];
              if ($targetType === 'subscribers_of_creator') {
                $targetLabel = $LANG['creator_bulk_target_subscribers'];
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
            <div class="i_tab_list_item flex_ creator_bulk_row">
              <div class="tab_detail_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($campaignId); ?></div>
              <div class="tab_detail_item truncated">
                <div class="flex_ tabing_non_justify truncated"><?php echo iN_HelpSecure($targetLabel); ?></div>
              </div>
              <div class="tab_detail_item truncated item_mobile">
                <div class="flex_ tabing_non_justify truncated"><?php echo iN_HelpSecure($messagePreview); ?></div>
              </div>
              <div class="tab_detail_item">
                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($createdAtText); ?></div>
              </div>
              <div class="tab_detail_item">
                <div class="flex_ tabing_non_justify">
                  <?php if ($attachmentPath !== '') {
                    if (function_exists('storage_public_url')) {
                      $attachmentUrl = storage_public_url($attachmentPath);
                    } else {
                      $attachmentUrl = $base_url . $attachmentPath;
                    }
                  ?>
                    <a class="creator_bulk_attachment <?php echo iN_HelpSecure($attachmentClass); ?>" href="<?php echo iN_HelpSecure($attachmentUrl); ?>" target="_blank" rel="noopener">
                      <?php echo iN_HelpSecure($attachmentLabel); ?>
                    </a>
                  <?php } else { ?>
                    <span class="creator_bulk_attachment <?php echo iN_HelpSecure($attachmentClass); ?>">
                      <?php echo iN_HelpSecure($attachmentLabel); ?>
                    </span>
                  <?php } ?>
                </div>
              </div>
              <div class="tab_detail_item">
                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($LANG['bulk_campaign_status_' . $status] ?? $status); ?></div>
              </div>
              <div class="tab_detail_item item_mobile">
                <div class="flex_ tabing_non_justify">
                  <?php echo iN_HelpSecure($LANG['bulk_queue_queued']) . ': ' . iN_HelpSecure($queuedCount); ?>,
                  <?php echo iN_HelpSecure($LANG['bulk_queue_sent']) . ': ' . iN_HelpSecure($sentCount); ?>,
                  <?php echo iN_HelpSecure($LANG['bulk_queue_skipped']) . ': ' . iN_HelpSecure($skippedCount); ?>,
                  <?php echo iN_HelpSecure($LANG['bulk_queue_failed']) . ': ' . iN_HelpSecure($failedCount); ?>
                </div>
              </div>
              <div class="tab_detail_item">
                <div class="tabing_non_justify flex_ creator_bulk_actions">
                  <?php if ($status === 'draft') { ?>
                    <div class="seePost c2 border_one transition tabing flex_ creatorBulkBuildQueue" data-id="<?php echo iN_HelpSecure($campaignId); ?>">
                      <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['bulk_build_queue']); ?>
                    </div>
                  <?php } ?>
                  <?php if ($status === 'queued' || $status === 'sending') { ?>
                    <div class="seePost c2 border_one transition tabing flex_ creatorBulkPause" data-id="<?php echo iN_HelpSecure($campaignId); ?>" data-status="paused">
                      <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')) . iN_HelpSecure($LANG['bulk_pause']); ?>
                    </div>
                  <?php } ?>
                  <?php if ($status === 'paused') { ?>
                    <div class="seePost c2 border_one transition tabing flex_ creatorBulkResume" data-id="<?php echo iN_HelpSecure($campaignId); ?>" data-status="queued">
                      <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')) . iN_HelpSecure($LANG['bulk_resume']); ?>
                    </div>
                  <?php } ?>
                  <?php if (!in_array($status, ['completed', 'canceled'], true)) { ?>
                    <div class="delu border_one transition tabing flex_ creatorBulkCancel" data-id="<?php echo iN_HelpSecure($campaignId); ?>" data-status="canceled">
                      <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['bulk_cancel']); ?>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
            <?php } ?>
            </div>
          </div>
        </div>
      <?php } else {
        echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . html_entity_decode($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . $LANG['creator_bulk_no_messages'] . '</div></div>';
      } ?>
      <?php } ?>
    </div>
    <?php if ($creatorBulkEnabled) { ?>
    <div class="i_become_creator_box_footer tabing">
      <?php if ($totalPages >= 1): ?>
        <ul class="pagination">
          <?php if ($pagep > 1): ?>
            <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
          <?php endif; ?>

          <?php if ($pagep > 3): ?>
            <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=1">1</a></li>
            <li class="dots">...</li>
          <?php endif; ?>

          <?php if ($pagep - 2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li><?php endif; ?>
          <?php if ($pagep - 1 > 0): ?><li class="page"><a href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li><?php endif; ?>

          <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

          <?php if ($pagep + 1 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li><?php endif; ?>
          <?php if ($pagep + 2 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li><?php endif; ?>

          <?php if ($pagep < $totalPages - 2): ?>
            <li class="dots">...</li>
            <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($totalPages); ?>"><?php echo iN_HelpSecure($totalPages); ?></a></li>
          <?php endif; ?>

          <?php if ($pagep < $totalPages): ?>
            <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url); ?>settings?tab=bulk_messages&page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
          <?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>
    <?php } ?>
  </div>
</div>
