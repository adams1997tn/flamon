<div class="i_contents_container edit-post-page">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['edit_post']); ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding" id="general_conf">
            <?php $campaignSettingsLimits = $iN->iN_GetCampaignSettings(); ?>
            <?php
                $campaignGoalMinLimit = isset($campaignSettingsLimits['goal_min']) ? (float)$campaignSettingsLimits['goal_min'] : 50.00;
                if ($campaignGoalMinLimit < 50) { $campaignGoalMinLimit = 50.00; }
                $campaignGoalMaxLimit = isset($campaignSettingsLimits['goal_max']) ? (float)$campaignSettingsLimits['goal_max'] : 0.00;
                if ($campaignGoalMaxLimit < 0) { $campaignGoalMaxLimit = 0.00; }
            ?>
            <form enctype="multipart/form-data" method="post" id="editPostForm"
                  data-max-goal-msg="<?php echo iN_HelpSecure($LANG['campaign_max_exceeds_goal'] ?? 'Maximum contribution cannot be greater than the goal.'); ?>"
                  data-goal-min="<?php echo iN_HelpSecure(number_format($campaignGoalMinLimit, 2, '.', '')); ?>"
                  data-goal-max="<?php echo iN_HelpSecure(number_format($campaignGoalMaxLimit, 2, '.', '')); ?>"
                  data-goal-min-msg="<?php echo iN_HelpSecure($LANG['campaign_goal_below_min'] ?? 'Goal amount is below the minimum allowed.'); ?>"
                  data-goal-max-msg="<?php echo iN_HelpSecure($LANG['campaign_goal_above_max'] ?? 'Goal amount exceeds the maximum allowed.'); ?>"
            >
                <?php
                $postFromData = $iN->iN_GetAllPostDetails($editPostID);
                if ($postFromData) {
                    $userPostID = $postFromData['post_id'] ?? null;
                    $userPostOwnerID = $postFromData['post_owner_id'] ?? null;
                    $userPostText = $postFromData['post_text'] ?? null;
                    $userPostFile = $postFromData['post_file'] ?? null;
                    $userPostCreatedTime = $postFromData['post_created_time'] ?? null;
                    $crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
                    $userPostWhoCanSee = $postFromData['who_can_see'] ?? null;
                    $userPostWantStatus = $postFromData['post_want_status'] ?? null;
                    $userPostWantedCredit = $postFromData['post_wanted_credit'] ?? null;
                    $userPostStatus = $postFromData['post_status'] ?? null;
                    $userPostOwnerUsername = $postFromData['i_username'] ?? null;
                    $userPostOwnerUserFullName = $postFromData['i_user_fullname'] ?? null;
                    $userPostOwnerUserGender = $postFromData['user_gender'] ?? null;
                    $userPostCommentAvailableStatus = $postFromData['comment_status'] ?? null;
                    $userPostOwnerUserLastLogin = $postFromData['last_login_time'] ?? null;
                    $userPostPinStatus = $postFromData['post_pined'] ?? null;
                    $slugUrl = $base_url . 'post/' . $postFromData['url_slug'] . '_' . $userPostID;
                    $userPostSharedID = $postFromData['shared_post_id'] ?? null;
                    $userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
                    $userPostUserVerifiedStatus = $postFromData['user_verified_status'] ?? null;

                    if ($userPostOwnerUserGender == 'male') {
                        $publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
                    } elseif ($userPostOwnerUserGender == 'female') {
                        $publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
                    } elseif ($userPostOwnerUserGender == 'couple') {
                        $publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
                    }

                    $userVerifiedStatus = '';
                    if ($userPostUserVerifiedStatus == '1') {
                        $userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
                    }

                    $postStyle = '';
                    if ($userPostWhoCanSee == '1') {
                        $onlySubs = '';
                        $subPostTop = '';
                        $wCanSee = '<div class="i_plus_public" id="ipublic_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('50') . '</div>';
                    } elseif ($userPostWhoCanSee == '2') {
                        $subPostTop = '';
                        $wCanSee = '<div class="i_plus_subs" id="ipublic_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('15') . '</div>';
                        $onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">' . $iN->iN_SelectedMenuIcon('15') . '</div><div class="onlySubs_note">' . preg_replace('/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']) . '</div></div></div>';
                    } elseif ($userPostWhoCanSee == '3') {
                        $subPostTop = 'extensionPost';
                        $wCanSee = '<div class="i_plus_public" id="ipublic_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('51') . '</div>';
                        $onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">' . $iN->iN_SelectedMenuIcon('56') . '</div><div class="onlySubs_note">' . preg_replace('/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']) . '</div></div></div>';
                    } elseif ($userPostWhoCanSee == '4') {
                        $subPostTop = 'extensionPost';
                        $wCanSee = '<div class="i_plus_public" id="ipublic_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
                        $onlySubs = '<div class="onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">' . $iN->iN_SelectedMenuIcon('56') . '</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="' . $userPostID . '">' . preg_replace('/{.*?}/', $userPostWantedCredit, $LANG['post_credit']) . '</div><div class="buythistext prcsPost" id="' . $userPostID . '">' . $LANG['purchase_post'] . '</div></div><div class="fr_subs uSubsModal transition" data-u="' . $userPostOwnerID . '">' . $iN->iN_SelectedMenuIcon('51') . $LANG['free_for_subscribers'] . '</div></div></div>';
                    }
                ?>
                <!-- POST details continue below -->
                        <div class="i_post_body body_<?php echo iN_HelpSecure($userPostID); ?> <?php echo html_entity_decode($subPostTop); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>" data-last="<?php echo iN_HelpSecure($userPostID); ?>">
                    <div class="i_post_body_header">
                        <div class="i_post_user_avatar">
                            <img src="<?php echo iN_HelpSecure($userPostOwnerUserAvatar); ?>" />
                        </div>
                        <div class="i_post_i">
                            <div class="i_post_username">
                                <a class="truncated" href="<?php echo iN_HelpSecure($base_url . $userPostOwnerUsername); ?>">
                                    <?php echo iN_HelpSecure($userPostOwnerUserFullName); ?>
                                    <?php echo html_entity_decode($publisherGender); ?>
                                    <?php echo html_entity_decode($userVerifiedStatus); ?>
                                    <?php echo html_entity_decode($wCanSee); ?>
                                </a>
                            </div>
                            <div class="i_post_shared_time">
                                <?php echo TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?>
                            </div>
                        </div>
                    </div>
                    <div class="i_post_container flex_ <?php echo html_entity_decode($postStyle); ?>" id="i_post_container_<?php echo iN_HelpSecure($userPostID); ?>">
                        <textarea class="more_textarea" name="newpostDesc" id="ed_<?php echo iN_HelpSecure($userPostID); ?>" placeholder="<?php echo iN_HelpSecure($LANG['write_something_about_the_post']); ?>"><?php if (!empty($userPostText)) { echo iN_HelpSecure($iN->br2nl($userPostText)); } ?></textarea>
                    </div>
                    <?php if (isset($postFromData['post_type']) && $postFromData['post_type'] === 'campaign') {
                        $campaignAdminMeta = $iN->iN_GetCampaignByPostId((int)$userPostID);
                        $campaignTitle = $campaignAdminMeta['title'] ?? '';
                        $campaignSummary = $campaignAdminMeta['summary'] ?? '';
                        $campaignGoal = isset($campaignAdminMeta['goal_amount']) ? iN_HelpSecure($campaignAdminMeta['goal_amount']) : '';
                        $campaignMin = isset($campaignAdminMeta['min_amount']) ? iN_HelpSecure($campaignAdminMeta['min_amount']) : '';
                        $campaignMax = isset($campaignAdminMeta['max_amount']) ? iN_HelpSecure($campaignAdminMeta['max_amount']) : '';
                        $deadlineTs = isset($campaignAdminMeta['deadline_ts']) && $campaignAdminMeta['deadline_ts'] ? (int)$campaignAdminMeta['deadline_ts'] : time();
                        $campaignDeadline = date('Y-m-d\TH:i', $deadlineTs);
                        $campaignStatus = $campaignAdminMeta['status'] ?? 'pending';
                        $campaignCover = $campaignAdminMeta['cover_upload_id'] ?? '';
                        $campaignId = $campaignAdminMeta['campaign_id'] ?? 0;
                        ?>
                        <div class="admin_campaign_panel">
                            <div class="admin_campaign_header">
                                <?php echo iN_HelpSecure($LANG['campaign_settings'] ?? 'Campaign settings'); ?>
                                <div class="admin_campaign_sub"><?php echo iN_HelpSecure($LANG['campaign_settings_desc'] ?? ''); ?></div>
                            </div>
                            <div class="admin_campaign_notice nonePoint" id="adminCampaignWarn"></div>
                            <input type="hidden" name="campaign_id" value="<?php echo iN_HelpSecure($campaignId); ?>">
                            <div class="admin_campaign_field">
                                <label><?php echo iN_HelpSecure($LANG['campaign_label_title'] ?? 'Title'); ?></label>
                                <input type="text" name="campaign_title" value="<?php echo iN_HelpSecure($campaignTitle); ?>" class="admin_campaign_input">
                            </div>
                            <div class="admin_campaign_field">
                                <label><?php echo iN_HelpSecure($LANG['campaign_label_summary'] ?? 'Summary'); ?></label>
                                <textarea name="campaign_summary" class="admin_campaign_input admin_campaign_textarea" rows="3"><?php echo iN_HelpSecure($campaignSummary); ?></textarea>
                            </div>
                            <div class="admin_campaign_field">
                                <label><?php echo iN_HelpSecure($LANG['campaign_label_goal'] ?? 'Goal amount'); ?></label>
                                <input type="number" step="0.01" min="0" name="campaign_goal" value="<?php echo iN_HelpSecure($campaignGoal); ?>" class="admin_campaign_input">
                                <div class="admin_campaign_hint"><?php echo iN_HelpSecure($LANG['campaign_goal_hint'] ?? 'Total amount you want to raise.'); ?></div>
                            </div>
                            <div class="admin_campaign_grid">
                                <div class="admin_campaign_field">
                                    <label><?php echo iN_HelpSecure($LANG['campaign_label_min'] ?? 'Minimum contribution'); ?></label>
                                    <input type="number" step="0.01" min="0" name="campaign_min" value="<?php echo iN_HelpSecure($campaignMin); ?>" class="admin_campaign_input">
                                    <div class="admin_campaign_hint"><?php echo iN_HelpSecure($LANG['campaign_min_hint'] ?? 'Smallest single contribution allowed.'); ?></div>
                                </div>
                                <div class="admin_campaign_field">
                                    <label><?php echo iN_HelpSecure($LANG['campaign_label_max'] ?? 'Maximum contribution'); ?></label>
                                    <input type="number" step="0.01" min="0" name="campaign_max" value="<?php echo iN_HelpSecure($campaignMax); ?>" class="admin_campaign_input">
                                    <div class="admin_campaign_hint"><?php echo iN_HelpSecure($LANG['campaign_max_hint'] ?? 'Largest single contribution allowed.'); ?></div>
                                </div>
                            </div>
                            <?php $currentYear = (int)date('Y'); ?>
                            <div class="campaign_deadline schedule_dt_inputs admin_campaign_deadline">
                                <div class="schedule_dt_field">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['campaign_label_deadline'] ?? 'Deadline'); ?></div>
                                    <div class="schedule_dt_group">
                                        <div class="schedule_dt_item">
                                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['month'] ?? 'Month'); ?></div>
                                            <select name="campaign_deadline_month" class="i_input">
                                                <?php for ($m = 1; $m <= 12; $m++) { $val = str_pad((string)$m, 2, '0', STR_PAD_LEFT); $sel = (date('m', $deadlineTs) === $val) ? 'selected' : ''; ?>
                                                    <option value="<?php echo iN_HelpSecure($val); ?>" <?php echo $sel; ?>><?php echo iN_HelpSecure($val); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="schedule_dt_item">
                                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['day'] ?? 'Day'); ?></div>
                                            <select name="campaign_deadline_day" class="i_input">
                                                <?php for ($d = 1; $d <= 31; $d++) { $val = str_pad((string)$d, 2, '0', STR_PAD_LEFT); $sel = (date('d', $deadlineTs) === $val) ? 'selected' : ''; ?>
                                                    <option value="<?php echo iN_HelpSecure($val); ?>" <?php echo $sel; ?>><?php echo iN_HelpSecure($val); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="schedule_dt_item">
                                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['year'] ?? 'Year'); ?></div>
                                            <select name="campaign_deadline_year" class="i_input">
                                                <?php for ($y = $currentYear; $y <= $currentYear + 5; $y++) { $sel = ((int)date('Y', $deadlineTs) === $y) ? 'selected' : ''; ?>
                                                    <option value="<?php echo iN_HelpSecure($y); ?>" <?php echo $sel; ?>><?php echo iN_HelpSecure($y); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="schedule_dt_field">
                                    <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['time'] ?? 'Time'); ?></div>
                                    <div class="schedule_dt_group">
                                        <div class="schedule_dt_item">
                                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['hour'] ?? 'Hour'); ?></div>
                                            <select name="campaign_deadline_hour" class="i_input">
                                                <?php for ($h = 0; $h <= 23; $h++) { $val = str_pad((string)$h, 2, '0', STR_PAD_LEFT); $sel = (date('H', $deadlineTs) === $val) ? 'selected' : ''; ?>
                                                    <option value="<?php echo iN_HelpSecure($val); ?>" <?php echo $sel; ?>><?php echo iN_HelpSecure($val); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="schedule_dt_item">
                                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['minute'] ?? 'Minute'); ?></div>
                                            <select name="campaign_deadline_minute" class="i_input">
                                                <?php for ($mi = 0; $mi <= 59; $mi++) { $val = str_pad((string)$mi, 2, '0', STR_PAD_LEFT); $sel = (date('i', $deadlineTs) === $val) ? 'selected' : ''; ?>
                                                    <option value="<?php echo iN_HelpSecure($val); ?>" <?php echo $sel; ?>><?php echo iN_HelpSecure($val); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="schedule_dt_item">
                                            <div class="schedule_dt_label"><?php echo iN_HelpSecure($LANG['second'] ?? 'Second'); ?></div>
                                            <select name="campaign_deadline_second" class="i_input">
                                                <?php for ($s = 0; $s <= 59; $s++) { $val = str_pad((string)$s, 2, '0', STR_PAD_LEFT); $sel = (date('s', $deadlineTs) === $val) ? 'selected' : ''; ?>
                                                    <option value="<?php echo iN_HelpSecure($val); ?>" <?php echo $sel; ?>><?php echo iN_HelpSecure($val); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="campaign_hint schedule_time_note"><?php echo iN_HelpSecure($LANG['campaign_deadline_hint'] ?? 'Choose a future date and time.'); ?></div>
                                </div>
                            </div>
                            <?php
                                $coverUrl = '';
                                if (!empty($campaignCover)) {
                                    $coverFile = $iN->iN_GetUploadedFileDetails((int)$campaignCover);
                                    if ($coverFile) {
                                        $cPath = $coverFile['upload_tumbnail_file_path'] ?? ($coverFile['uploaded_file_path'] ?? '');
                                        if ($cPath) {
                                            $coverUrl = function_exists('storage_public_url') ? storage_public_url($cPath) : $base_url . $cPath;
                                        }
                                    }
                                }
                            ?>
                            <div class="campaign_cover_block column">
                                <div class="campaign_cover_header flex_ tabing_non_justify">
                                    <span class="campaign_cover_title"><?php echo iN_HelpSecure($LANG['campaign_label_cover'] ?? 'Cover image'); ?></span>
                                    <button type="button" class="form_btn transition campaignCoverBtn" id="adminCampaignCoverButton">
                                        <?php echo iN_HelpSecure($LANG['campaign_cover_upload_btn'] ?? 'Upload cover'); ?>
                                    </button>
                                </div>
                                <div class="campaign_cover_drop" id="adminCampaignCoverDrop">
                                    <div class="campaign_cover_hint"><?php echo iN_HelpSecure($LANG['campaign_cover_hint'] ?? 'Upload a cover image (JPG/PNG).'); ?></div>
                                </div>
                                <input type="hidden" name="campaign_cover" value="<?php echo iN_HelpSecure($campaignCover); ?>" id="adminCampaignCoverId">
                                <div class="campaign_cover_preview<?php echo empty($coverUrl) ? ' nonePoint' : ''; ?>" id="adminCampaignCoverPreview">
                                    <img src="<?php echo !empty($coverUrl) ? iN_HelpSecure($coverUrl, FILTER_VALIDATE_URL) : ''; ?>" alt="<?php echo iN_HelpSecure($LANG['campaign_label_cover'] ?? 'Cover'); ?>" id="adminCampaignCoverImg">
                                    <div class="campaign_cover_remove" id="adminCampaignCoverRemove">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?> <?php echo iN_HelpSecure($LANG['campaign_cover_remove'] ?? 'Remove'); ?>
                                    </div>
                                </div>
                                <input type="file" accept="image/*" id="adminCampaignCoverInput" class="nonePoint">
                                <div id="adminCampaignCoverTemp" class="nonePoint"></div>
                            </div>
                            <div class="admin_campaign_field">
                                <label><?php echo iN_HelpSecure($LANG['status'] ?? 'Status'); ?></label>
                                <select name="campaign_status" class="admin_campaign_input">
                                    <?php
                                    $allowedStatuses = array(
                                        'pending' => $LANG['pending_approve'] ?? 'pending',
                                        'active' => $LANG['active'] ?? 'active',
                                        'expired' => $LANG['expired'] ?? 'expired',
                                        'rejected' => $LANG['rejected_post'] ?? 'rejected',
                                    );
                                    foreach ($allowedStatuses as $key => $label) {
                                        $sel = $campaignStatus === $key ? 'selected' : '';
                                        echo '<option value="' . iN_HelpSecure($key) . '" ' . $sel . '>' . iN_HelpSecure($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="i_post_u_images">
                        <?php
                        $trimValue = rtrim($userPostFile, ',');
                        $explodeFiles = explode(',', $trimValue);
                        $explodeFiles = array_unique($explodeFiles);
                        $countExplodedFiles = $iN->iN_CheckCountFile($userPostFile);

                        if ($countExplodedFiles == 1) {
                            $container = 'i_image_one';
                        } elseif ($countExplodedFiles == 2) {
                            $container = 'i_image_two';
                        } elseif ($countExplodedFiles == 3) {
                            $container = 'i_image_three';
                        } elseif ($countExplodedFiles == 4) {
                            $container = 'i_image_four';
                        } elseif ($countExplodedFiles >= 5) {
                            $container = 'i_image_five';
                        }

                        foreach ($explodeFiles as $explodeVideoFile) {
                            $VideofileData = $iN->iN_GetUploadedFileDetails($explodeVideoFile);
                            if ($VideofileData) {
                                $VideofileUploadID = $VideofileData['upload_id'] ?? null;
                                $VideofileExtension = $VideofileData['uploaded_file_ext'] ?? null;
                                $VideofilePath = $VideofileData['uploaded_file_path'] ?? null;
                                $VideofilePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $VideofilePath);

                                if ($VideofileExtension == 'mp4') {
                                    if (function_exists('storage_public_url')) {
                                        $VideofilePathUrl = storage_public_url($VideofilePath);
                                    } else {
                                        $VideofilePathUrl = $base_url . $VideofilePath;
                                    }

                                    echo '
                                    <div class="nonePoint" id="video' . $VideofileUploadID . '">
                                        <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls preload="none" onended="videoEnded()">
                                            <source src="' . $VideofilePathUrl . '" type="video/mp4">
                                            Your browser does not support HTML5 video.
                                        </video>
                                    </div>';
                                }
                            }
                        }

                        echo '<div class="' . $container . '" id="lightgallery' . $userPostID . '">';

                        foreach ($explodeFiles as $dataFile) {
                            $fileData = $iN->iN_GetUploadedFileDetails($dataFile);
                            if ($fileData) {
                                $fileUploadID = $fileData['upload_id'] ?? null;
                                $fileExtension = $fileData['uploaded_file_ext'] ?? null;
                                $filePath = $fileData['uploaded_file_path'] ?? null;

                                if (function_exists('storage_public_url')) {
                                    $filePathUrl = storage_public_url($filePath);
                                } else {
                                    $filePathUrl = $base_url . $filePath;
                                }

                                $videoPlaybutton = '';
                                if ($fileExtension == 'mp4') {
                                    $videoPlaybutton = '<div class="playbutton">' . $iN->iN_SelectedMenuIcon('55') . '</div>';
                                    if (function_exists('storage_public_url')) {
                                        $filePathUrl = storage_public_url($fileData['upload_tumbnail_file_path'] ?? '');
                                    } else {
                                        $filePathUrl = $base_url . ($fileData['upload_tumbnail_file_path'] ?? '');
                                    }
                                    $fileisVideo = 'data-poster="' . $filePathUrl . '" data-html="#video' . $fileUploadID . '"';
                                } else {
                                    $fileisVideo = 'data-src="' . $filePathUrl . '"';
                                }

                                if ($fileExtension != 'mp3') {
                                    echo '
                                    <div class="i_post_image_swip_wrapper" data-style="background-image:url(\'' . iN_HelpSecure($filePathUrl) . '\');" ' . html_entity_decode($fileisVideo) . '>
                                        ' . html_entity_decode($videoPlaybutton) . '
                                        <img class="i_p_image" src="' . iN_HelpSecure($filePathUrl) . '">
                                    </div>';
                                }
                            }
                        }

                        echo '</div>';
                        ?>

                        <?php if ($logedIn) : ?>
                            <div class="lightGalleryInit" data-id="<?php echo iN_HelpSecure($userPostID); ?>"></div>
                        <?php endif; ?>
                    </div>
                    <div class="myaudio">
                        <?php
                        foreach ($explodeFiles as $dataFile) {
                            $fileAudioData = $iN->iN_GetUploadedMp3FileDetails($dataFile);
                            if ($fileAudioData) {
                                $fileUploadID = $fileAudioData['upload_id'] ?? null;
                                $fileExtension = $fileAudioData['uploaded_file_ext'] ?? null;
                                $filePath = $fileAudioData['uploaded_file_path'] ?? null;

                                if ($fileExtension == 'mp3') {
                                    if (function_exists('storage_public_url')) {
                                        $filePathUrl = storage_public_url($filePath);
                                        $filePathTumbnailUrl = storage_public_url($fileAudioData['uploaded_file_path'] ?? '');
                                    } else {
                                        $filePathUrl = $base_url . $filePath;
                                        $filePathTumbnailUrl = $base_url . ($fileAudioData['uploaded_file_path'] ?? '');
                                    }

                                    $audShowType = '<audio crossorigin="" preload="none"><source src="' . iN_HelpSecure($filePathUrl) . '" type="audio/mp3" /></audio>';

                                    $fileisVideo = 'data-src="' . $filePathTumbnailUrl . '"';

                                    echo '
                                    <div class="i_post_image_swip_wrappera" ' . html_entity_decode($fileisVideo) . '>
                                        <div id="play_po_' . iN_HelpSecure($fileUploadID) . '" class="green-audio-player">
                                            ' . html_entity_decode($audShowType) . '
                                        </div>
                                    </div>';
                                }
                            }
                        }
                        ?>
                    </div>

                    <div class="admin_approve_post_footer">
                        <div class="i_become_creator_box_footer">
                            <input type="hidden" name="f" value="editPostDetails">
                            <input type="hidden" name="postOwnerID" value="<?php echo iN_HelpSecure($userPostOwnerID); ?>">
                            <input type="hidden" name="postID" value="<?php echo iN_HelpSecure($userPostID); ?>">
                            <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_myprofile">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        </form>
    </div>
</div>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/editPostHandler.js?v=<?php echo iN_HelpSecure($version);?>" defer></script>
