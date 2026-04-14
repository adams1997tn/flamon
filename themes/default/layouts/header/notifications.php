<div class="i_general_box_notifications_container generalBox">
  <div class="btest">
    <div class="i_user_details">
      <!-- NOTIFICATION HEADER -->
      <div class="i_box_messages_header">
        <?php echo iN_HelpSecure($LANG['notifications']); ?>
        <div class="i_message_full_screen transition">
          <a href="<?php echo iN_HelpSecure($base_url); ?>notifications">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('48')); ?>
          </a>
        </div>
      </div>
      <!-- /NOTIFICATION HEADER -->

      <div class="i_header_others_box">
        <?php if (!empty($Notifications)) {
          $notificationCreatorIDs = [];
          foreach ($Notifications as $notificationRow) {
            $notificationCreatorID = isset($notificationRow['not_iuid']) ? (int)$notificationRow['not_iuid'] : 0;
            if ($notificationCreatorID > 0) {
              $notificationCreatorIDs[$notificationCreatorID] = $notificationCreatorID;
            }
          }
          $notificationCreatorIDs = array_values($notificationCreatorIDs);
          if (!empty($notificationCreatorIDs)) {
            $iN->iN_PreloadUserMediaPathMaps($notificationCreatorIDs);
          }
          $notificationCreatorMap = !empty($notificationCreatorIDs) ? $iN->iN_GetUsersIdentityMap($notificationCreatorIDs) : [];
          foreach ($Notifications as $notData) {
            $notificationID = $notData['not_id'];
            $notificationStatus = $notData['not_status'];
            $notPostID = $notData['not_post_id'];
            $notificationType = $notData['not_type'];
            $notificationTypeType = $notData['not_not_type'];
            $notificationPayload = $notData['not_payload'] ?? '';
            $notificationTime = $notData['not_time'];
            $notificationTimeLabel = '';
            $notCreator = $notData['not_iuid'];
            $notCreatorIntID = (int)$notCreator;
            $notCreatorIdentity = isset($notificationCreatorMap[$notCreatorIntID]) ? $notificationCreatorMap[$notCreatorIntID] : null;
            $notCreatorUserName = isset($notCreatorIdentity['i_username']) ? $notCreatorIdentity['i_username'] : '';
            if ($notCreatorUserName === '') {
              continue;
            }
            $notCreatorUserFullName = $fullnameorusername === 'no' ? $notCreatorUserName : (isset($notCreatorIdentity['i_user_fullname']) ? $notCreatorIdentity['i_user_fullname'] : $notCreatorUserName);
            $notificationCreatorAvatar = $iN->iN_UserAvatar($notCreator, $base_url);
            if (!empty($notificationTime)) {
              $notificationTimeValue = is_numeric($notificationTime)
                ? date('Y-m-d H:i:s', (int)$notificationTime)
                : (string)$notificationTime;
              $notificationTimeLabel = TimeAgo::ago($notificationTimeValue, date('Y-m-d H:i:s'));
            }

            // Default values
            $notText = '';
            $notIcon = '';
            $notUrl = '#';
            $reportPopupEnabled = false;
            $reportPopupTitle = $LANG['notifications'] ?? 'Notifications';
            $reportPopupAction = $LANG['chat_view_all_messages'] ?? ($LANG['messages'] ?? 'Messages');
            $reportPopupClose = $LANG['no'] ?? 'No';
            $reportPopupText = '';

            switch ($notificationTypeType) {
              case 'commented':
                $notText = $LANG['commented_on_your_post'];
                $notIcon = $iN->iN_SelectedMenuIcon('20');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'commentReply':
                $notText = $LANG['replied_to_your_comment'] ?? 'replied to your comment';
                $notIcon = $iN->iN_SelectedMenuIcon('20');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'verification_approved':
                $notText = $LANG['your_confirmation_accepted_email_title'];
                $notIcon = $iN->iN_SelectedMenuIcon('11');
                $notUrl = $base_url . 'creator/becomeCreator';
                break;
              case 'verification_declined':
                $notText = $LANG['your_confirmation_declined_email_title'];
                $notIcon = $iN->iN_SelectedMenuIcon('5');
                $notUrl = $base_url . 'creator/becomeCreator';
                break;
              case 'postLike':
              case 'commentLike':
                $notText = $notificationTypeType === 'postLike' ? $LANG['liked_your_post'] : $LANG['liked_your_comment'];
                $notIcon = $iN->iN_SelectedMenuIcon('18');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'story_reaction':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) { $payload = []; }
                $reaction = $payload['reaction'] ?? '';
                $notText = $LANG['story_reaction_notification'];
                if ($reaction !== '') {
                  $notText = trim($notText . ' ' . $reaction);
                }
                $notIcon = $iN->iN_SelectedMenuIcon('18');
                $notUrl = $base_url . $notCreatorUserName;
                break;
              case 'follow':
                $notText = $LANG['is_now_following_your_profile'];
                $notIcon = $iN->iN_SelectedMenuIcon('66');
                $notUrl = $base_url . $notCreatorUserName;
                break;
              case 'subscribe':
                $notText = $LANG['is_subscribed_your_profile'];
                $notIcon = $iN->iN_SelectedMenuIcon('51');
                $notUrl = $base_url . $notCreatorUserName;
                break;
              case 'agency_request':
                $notText = $LANG['agency_request_notification'] ?? 'sent a request to join your agency';
                $notIcon = $iN->iN_SelectedMenuIcon('92');
                $notUrl = $base_url . 'settings?tab=agencies';
                break;
              case 'tip':
                $notText = $LANG['send_you_a_tip'] ?? 'sent you a tip';
                $notIcon = $iN->iN_SelectedMenuIcon('40');
                $notUrl = $notPostID ? $base_url . 'post/' . $notPostID : $base_url . $notCreatorUserName;
                break;
              case 'live_started':
                $notText = $LANG['live_started_notification_text'] ?? 'started a live stream';
                $notIcon = $iN->iN_SelectedMenuIcon('133');
                $notUrl = $base_url . 'live/' . $notCreatorUserName;
                break;
              case 'message_report_update':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) {
                  $payload = [];
                }
                $action = (string)($payload['action'] ?? 'checked');
                $moderatorNote = trim((string)($payload['note'] ?? ''));
                if ($moderatorNote === '' && !in_array((string)$notificationType, ['text', 'checked', 'deleted'], true)) {
                  $moderatorNote = trim((string)$notificationType);
                }
                if ($action === 'deleted') {
                  $notText = $LANG['report_message_notify_deleted'] ?? 'Your report has been finalized. The reported message was removed by our moderation team.';
                  $notIcon = $iN->iN_SelectedMenuIcon('5');
                } else {
                  $notText = $LANG['report_message_notify_checked'] ?? 'Your report has been reviewed by our moderation team.';
                  $notIcon = $iN->iN_SelectedMenuIcon('69');
                }
                if ($moderatorNote !== '') {
                  $notText .= ' ' . ($LANG['moderator_note_prefix'] ?? 'Moderator note:') . ' ' . $moderatorNote;
                }
                $chatID = isset($payload['chat_id']) ? (int)$payload['chat_id'] : (int)$notPostID;
                $notUrl = $chatID > 0 ? ($base_url . 'chat?chat_width=' . $chatID) : ($base_url . 'notifications');
                $reportPopupEnabled = true;
                $reportPopupText = $notText;
                break;
              case 'community_subscribe':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) {
                  $payload = [];
                }
                $communityName = $payload['community_name'] ?? '';
                $communitySlug = $payload['community_slug'] ?? '';
                $communityLabel = $communityName !== '' ? $communityName : ($LANG['community'] ?? 'Community');
                $template = $LANG['community_notification_subscribe'] ?? '{community} community sayfanıza {user} kişisi abone oldu.';
                $notText = str_replace(['{community}', '{user}'], [$communityLabel, $notCreatorUserFullName], $template);
                $notIcon = $iN->iN_SelectedMenuIcon('51');
                $notUrl = $communitySlug !== '' ? $base_url . 'community/' . $communitySlug : $base_url . 'communities';
                break;
              case 'community_comment':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) {
                  $payload = [];
                }
                $communityName = $payload['community_name'] ?? '';
                $communitySlug = $payload['community_slug'] ?? '';
                $communityLabel = $communityName !== '' ? $communityName : ($LANG['community'] ?? 'Community');
                $commentText = trim((string)($payload['comment'] ?? ''));
                $template = $commentText !== ''
                  ? ($LANG['community_notification_comment'] ?? '{community} community sayfanıza {user} kişisi {comment} yorumunu yaptı.')
                  : ($LANG['community_notification_comment_simple'] ?? '{community} community sayfanıza {user} kişisi yorum yaptı.');
                $notText = str_replace(['{community}', '{user}', '{comment}'], [$communityLabel, $notCreatorUserFullName, $commentText], $template);
                $notIcon = $iN->iN_SelectedMenuIcon('20');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'community_like':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) {
                  $payload = [];
                }
                $communityName = $payload['community_name'] ?? '';
                $communitySlug = $payload['community_slug'] ?? '';
                $communityLabel = $communityName !== '' ? $communityName : ($LANG['community'] ?? 'Community');
                $template = $LANG['community_notification_like'] ?? '{community} community sayfanıza {user} kişisi beğeni yaptı.';
                $notText = str_replace(['{community}', '{user}'], [$communityLabel, $notCreatorUserFullName], $template);
                $notIcon = $iN->iN_SelectedMenuIcon('18');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'community_tip':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) {
                  $payload = [];
                }
                $communityName = $payload['community_name'] ?? '';
                $communitySlug = $payload['community_slug'] ?? '';
                $communityLabel = $communityName !== '' ? $communityName : ($LANG['community'] ?? 'Community');
                $template = $LANG['community_notification_tip'] ?? '{community} community page got a tip from {user}.';
                $notText = str_replace(['{community}', '{user}'], [$communityLabel, $notCreatorUserFullName], $template);
                $notIcon = $iN->iN_SelectedMenuIcon('40');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'community_restriction':
                $payload = json_decode($notificationPayload !== '' ? (string)$notificationPayload : (string)$notificationType, true);
                if (!is_array($payload)) {
                  $payload = [];
                }
                $communityName = $payload['community_name'] ?? '';
                $communitySlug = $payload['community_slug'] ?? '';
                $communityLabel = $communityName !== '' ? $communityName : ($LANG['community'] ?? 'Community');
                $fields = $payload['fields'] ?? [];
                $fieldLabels = [];
                if (is_array($fields)) {
                  if (!empty($fields['member_status'])) {
                    $statusLabel = $LANG['community_member_restricted'] ?? 'Restricted';
                    if ($fields['member_status'] === 'blocked') {
                      $statusLabel = $LANG['community_member_blocked'] ?? 'Blocked';
                    }
                    $fieldLabels[] = $statusLabel;
                  }
                  if (isset($fields['posts'])) {
                    $fieldLabels[] = $LANG['community_moderation_posts_disabled'] ?? 'Posts disabled';
                  }
                  if (isset($fields['comments'])) {
                    $fieldLabels[] = $LANG['community_moderation_comments_disabled'] ?? 'Comments disabled';
                  }
                  if (isset($fields['reshare'])) {
                    $fieldLabels[] = $LANG['community_moderation_reshare_disabled'] ?? 'Reshare disabled';
                  }
                  if (!empty($fields['view_timeout']) && is_array($fields['view_timeout'])) {
                    $timeoutLabel = $LANG['community_moderation_timeout_none'] ?? 'No timeout';
                    if (!empty($fields['view_timeout']['permanent']) && (string)$fields['view_timeout']['permanent'] === '1') {
                      $timeoutLabel = $LANG['community_moderation_timeout_permanent'] ?? 'Permanent';
                    } elseif (!empty($fields['view_timeout']['until'])) {
                      $timeoutLabel = str_replace('{date}', date('M d, Y', strtotime((string)$fields['view_timeout']['until'])), $LANG['community_moderation_timeout_until'] ?? 'Until {date}');
                    }
                    $fieldLabels[] = $timeoutLabel;
                  }
                }
                $fieldsText = !empty($fieldLabels) ? implode(', ', $fieldLabels) : ($LANG['community_moderation_action_generic'] ?? 'Action');
                $template = $LANG['community_notification_restriction'] ?? '{community} community sayfanızda şu alanlarda kısıtlama uygulandı: {fields}.';
                $notText = str_replace(['{community}', '{fields}'], [$communityLabel, $fieldsText], $template);
                $notIcon = $iN->iN_SelectedMenuIcon('5');
                $notUrl = $communitySlug !== '' ? $base_url . 'community/' . $communitySlug : $base_url . 'communities';
                break;
              case 'accepted_post':
                $notText = $LANG['accepted_post'];
                $notIcon = $iN->iN_SelectedMenuIcon('69');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'rejected_post':
              case 'declined_post':
                $notText = $notificationTypeType === 'rejected_post' ? $LANG['rejected_post'] : $LANG['declined_post'];
                $notIcon = $iN->iN_SelectedMenuIcon('5');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'umentioned':
                $notText = $LANG['mentioned_u'];
                $notIcon = $iN->iN_SelectedMenuIcon('37');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'purchasedYourPost':
                $notText = $LANG['congratulations_you_sold'];
                $notIcon = $iN->iN_SelectedMenuIcon('175');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'scheduled_published':
                $notText = $LANG['scheduled_published_notif'] ?? 'Your scheduled post is now live';
                $notIcon = $iN->iN_SelectedMenuIcon('183');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'campaign_approved':
                $notText = $LANG['campaign_notif_approved'] ?? 'Your campaign was approved';
                $notIcon = $iN->iN_SelectedMenuIcon('69');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'campaign_rejected':
                $notText = $LANG['campaign_notif_rejected'] ?? 'Your campaign was rejected';
                $notIcon = $iN->iN_SelectedMenuIcon('5');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              case 'campaign_donate':
                $donationAmount = 0;
                if (!empty($notificationType) && preg_match('/donated:([0-9\\.]+)/', $notificationType, $m)) {
                  $donationAmount = (float)$m[1];
                }
                $amountText = formatCurrency($donationAmount, $defaultCurrency);
                $template = $LANG['campaign_notif_donated'] ?? 'Donated {amount}';
                $notText = str_replace('{amount}', $amountText, $template);
                $notIcon = $iN->iN_SelectedMenuIcon('40');
                $notUrl = $base_url . 'post/' . $notPostID;
                break;
              default:
                continue 2;
            }
        ?>
          <!-- NOTIFICATION ITEM -->
          <div class="i_message_wrpper hidNot_<?php echo iN_HelpSecure($notificationID); ?>">
            <a
              href="<?php echo iN_HelpSecure($notUrl); ?>"
              <?php if ($reportPopupEnabled) { ?>
                class="js_report_note_notification"
                data-popup-title="<?php echo iN_HelpSecure($reportPopupTitle); ?>"
                data-popup-text="<?php echo iN_HelpSecure($reportPopupText); ?>"
                data-popup-url="<?php echo iN_HelpSecure($notUrl); ?>"
                data-popup-action="<?php echo iN_HelpSecure($reportPopupAction); ?>"
                data-popup-close="<?php echo iN_HelpSecure($reportPopupClose); ?>"
              <?php } ?>
            >
              <div class="i_message_wrapper transition">
                <div class="i_message_owner_avatar">
                  <div class="i_message_not_icon flex_ tabing"><?php echo html_entity_decode($notIcon); ?></div>
                  <div class="i_message_avatar">
                    <img src="<?php echo iN_HelpSecure($notificationCreatorAvatar); ?>" alt="<?php echo iN_HelpSecure($notCreatorUserFullName); ?>">
                  </div>
                </div>
                <div class="i_message_info_container">
                  <div class="i_message_owner_name"><?php echo iN_HelpSecure($notCreatorUserFullName); ?></div>
                  <div class="i_message_i i_notification_text"><?php echo iN_HelpSecure($notText); ?></div>
                  <?php if ($notificationTimeLabel !== '') { ?>
                    <div class="i_notification_time"><?php echo iN_HelpSecure($notificationTimeLabel); ?></div>
                  <?php } ?>
                </div>
              </div>
            </a>
          </div>
          <!-- /NOTIFICATION ITEM -->
        <?php } // endforeach 
        } else { ?>
        <div class="no_not_here tabing flex_">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('103')); ?>
        </div>
        <?php } ?>
      </div>
    </div>
    <!-- SEE ALL NOTIFICATIONS LINK -->
      <div class="footer_container messages">
        <a href="<?php echo iN_HelpSecure($base_url); ?>notifications">
          <?php echo iN_HelpSecure($LANG['see_all_notifications']); ?>
        </a>
      </div>
  </div>
</div>
