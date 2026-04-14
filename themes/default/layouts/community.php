<?php
$communitySlugValue = isset($communitySlug) ? (string)$communitySlug : '';
$communityData = $iN->iN_GetCommunityBySlug($communitySlugValue);
$subscriptionTypeValue = (string)$subscriptionType;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include 'header/meta.php';
        include 'header/css.php';
        include 'header/javascripts.php';
    ?>
</head>
<body>
<?php if ($logedIn == 0) { ?>
    <?php include 'login_form.php'; ?>
<?php } ?>
<?php include 'header/header.php'; ?>
<?php if ($communityData) {
    $communityID = (int)$communityData['id'];
    $communityTitle = $communityData['title'] ?? '';
    $communityCategory = $communityData['category'] ?? '';
    $communityCategoryLabel = $communityCategory;
    if (!empty($communityCategory) && isset($LANG[$communityCategory])) {
        $communityCategoryLabel = $LANG[$communityCategory];
    }
    $communityDescription = $communityData['description'] ?? '';
    $communityCover = $communityData['cover_image'] ?? '';
    $communityAvatar = $communityData['avatar_image'] ?? '';
    $communityPrice = isset($communityData['monthly_price']) ? (float)$communityData['monthly_price'] : 0;
    $communityLimit = isset($communityData['member_limit']) ? (int)$communityData['member_limit'] : 0;
    $memberCount = $iN->iN_GetCommunityMembersCount($communityID, 'active');
    $subscriptionRequired = (string)($communityData['subscription_required'] ?? '1');
    $accessPolicy = (string)($communityData['access_policy'] ?? 'members_only');
    if (!in_array($accessPolicy, ['members_only', 'public'], true)) {
        $accessPolicy = 'members_only';
    }
    $postingEnabled = (string)($communityData['posting_enabled'] ?? '1');
    $postingPolicy = (string)($communityData['posting_policy'] ?? 'owner_admin');
    if (!in_array($postingPolicy, ['owner_admin', 'owner_admin_moderators', 'members'], true)) {
        $postingPolicy = 'owner_admin';
    }
    $isOwner = ($logedIn == 1 && (int)$communityData['owner_user_id'] === (int)$userID);
    $isAdmin = ($logedIn == 1 && isset($userType) && (string)$userType === '2');
    $isMember = ($logedIn == 1 && $iN->iN_IsCommunityAccessMember($communityData, $userID));
    $moderationEnabled = (string)($communityData['moderation_enabled'] ?? '0') === '1';
    $timeoutEnabled = (string)($communityData['moderation_timeout_enabled'] ?? '0') === '1';
    $moderatorData = null;
    if ($logedIn == 1 && !$isOwner && !$isAdmin) {
        $moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
    }
    $isModerator = !empty($moderatorData);
    $canModerateMembers = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_members'] ?? '0') === '1');
    $canModeratePosts = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_posts'] ?? '0') === '1');
    $canModerateComments = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_comments'] ?? '0') === '1');
    $canModerateReshare = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_reshare'] ?? '0') === '1');
    $canModerateTimeout = $timeoutEnabled && ($isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_view_timeout'] ?? '0') === '1'));
    $canManageMedia = $isOwner || $isAdmin || ($isModerator && (string)($moderatorData['can_manage_media'] ?? '0') === '1');
    $canModerateMemberControls = $canModerateMembers || $canModeratePosts || $canModerateComments || $canModerateReshare || $canModerateTimeout;
    $timeoutOptions = $timeoutEnabled ? $iN->iN_GetCommunityTimeoutOptions($communityData) : [];
    $restrictionStatus = null;
    if ($logedIn == 1) {
        $restrictionStatus = $iN->iN_GetCommunityRestrictionStatus($communityID, $userID);
    }
    $isBlocked = ($restrictionStatus === 'blocked');
    $isRestricted = ($restrictionStatus === 'restricted');
    $isViewRestricted = false;
    $memberReshareDisabled = false;
    $viewRestriction = null;
    if ($logedIn == 1 && $moderationEnabled && !$isOwner && !$isAdmin) {
        $memberReshareDisabled = $iN->iN_IsCommunityReshareDisabled($communityID, $userID);
        $viewRestriction = $iN->iN_GetCommunityViewRestriction($communityID, $userID);
        $isViewRestricted = !empty($viewRestriction['restricted']);
    }
    $isModerationListed = false;
    if ($logedIn == 1 && !$isOwner && !$isAdmin) {
        $isModerationListed = $iN->iN_IsCommunityModerationListed($communityID, $userID);
    }
    if ($isBlocked) {
        $isMember = false;
    }
    if ($isViewRestricted) {
        $isMember = false;
    }
    $canPostToCommunity = false;
    if ($postingEnabled === '1') {
        if ($isOwner || $isAdmin) {
            $canPostToCommunity = true;
        } elseif ($postingPolicy === 'owner_admin_moderators' && $isModerator) {
            $canPostToCommunity = true;
        } elseif ($postingPolicy === 'members' && $isMember) {
            $canPostToCommunity = true;
        }
    }
    $canAccessCommunity = ($logedIn == 1 && $iN->iN_CanAccessCommunity($communityData, $userID, $isAdmin, $isModerator));
    if ($accessPolicy === 'public') {
        $canViewCommunity = true;
    } else {
        $canViewCommunity = $canAccessCommunity || $isModerationListed;
    }
    if (($isBlocked || $isViewRestricted) && !$isOwner && !$isAdmin) {
        $canViewCommunity = false;
    }
    $isFull = ($communityLimit > 0 && $memberCount >= $communityLimit);
    $coverUrl = $base_url . 'uploads/web.png';
    if (!empty($communityCover)) {
        if (function_exists('storage_public_url')) {
            $coverUrl = storage_public_url($communityCover);
        } else {
            $coverUrl = $base_url . $communityCover;
        }
    }
    $priceLabel = $subscriptionTypeValue === '2'
        ? iN_HelpSecure($communityPrice) . html_entity_decode($iN->iN_SelectedMenuIcon('40'))
        : iN_HelpSecure(formatCurrency($communityPrice, $defaultCurrency));
    $capacityLabel = $communityLimit > 0 ? $memberCount . '/' . $communityLimit : $memberCount;
    $ownerData = $iN->iN_GetUserDetails((int)$communityData['owner_user_id']);
    $ownerName = '';
    $ownerUrl = '';
    if ($ownerData) {
        $ownerName = $ownerData['i_user_fullname'] ?: $ownerData['i_username'];
        $ownerUrl = $base_url . $ownerData['i_username'];
    }
    $ownerAvatar = $ownerData ? $iN->iN_UserAvatar((int)$communityData['owner_user_id'], $base_url) : ($base_url . 'uploads/avatars/no_gender.png');
    $communityAvatarUrl = $ownerAvatar;
    if (!empty($communityAvatar)) {
        if (function_exists('storage_public_url')) {
            $communityAvatarUrl = storage_public_url($communityAvatar);
        } else {
            $communityAvatarUrl = $base_url . $communityAvatar;
        }
    }
    $communityPhotos = [];
    $communityVideos = [];
    $communityMembersPreview = [];
    $canViewMembersList = $isOwner || $isAdmin || $canModerateMembers;
    if (!$canViewMembersList && $accessPolicy !== 'public') {
        $canViewMembersList = $canViewCommunity;
    }
    if ($canViewCommunity) {
        $mediaPosts = $iN->iN_GetCommunityMediaPreviews($communityID, $logedIn == 1 ? (int)$userID : 0, 24);
        if (!empty($mediaPosts)) {
            $allowedImages = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp');
            $allowedVideos = array('mp4', 'mkv', 'webm', 'mov');
            foreach ($mediaPosts as $mediaItem) {
                if (count($communityPhotos) >= 6 && count($communityVideos) >= 6) {
                    break;
                }
                $postId = isset($mediaItem['post_id']) ? (int)$mediaItem['post_id'] : 0;
                $postSlug = isset($mediaItem['url_slug']) && $mediaItem['url_slug'] ? $mediaItem['url_slug'] : $postId;
                $postFiles = isset($mediaItem['post_file']) ? trim($mediaItem['post_file'], ',') : '';
                if (!$postId || $postFiles === '') {
                    continue;
                }
                $explodeFiles = array_unique(explode(',', $postFiles));
                $firstFileID = (int) $explodeFiles[0];
                if (!$firstFileID) {
                    continue;
                }
                $uploadDetails = $iN->iN_GetUploadedFileDetails($firstFileID);
                if (!$uploadDetails) {
                    continue;
                }
                $fileExt = strtolower($uploadDetails['uploaded_file_ext'] ?? '');
                if ($fileExt === '') {
                    $fileExt = strtolower(pathinfo((string)($uploadDetails['uploaded_file_path'] ?? ''), PATHINFO_EXTENSION));
                }
                $isImage = $fileExt !== '' && in_array($fileExt, $allowedImages, true);
                $isVideo = $fileExt !== '' && in_array($fileExt, $allowedVideos, true);
                if (!$isImage && !$isVideo) {
                    continue;
                }
                $thumbPath = $uploadDetails['upload_tumbnail_file_path'] ?? $uploadDetails['uploaded_file_path'] ?? '';
                if ($thumbPath === '') {
                    continue;
                }
                if (function_exists('storage_public_url')) {
                    $thumbUrl = storage_public_url($thumbPath);
                } else {
                    $thumbUrl = $base_url . $thumbPath;
                }
                $postUrl = $base_url . 'post/' . $postSlug . '_' . $postId;
                if ($isImage && count($communityPhotos) < 6) {
                    $communityPhotos[] = ['url' => $postUrl, 'thumb' => $thumbUrl];
                }
                if ($isVideo && count($communityVideos) < 6) {
                    $communityVideos[] = ['url' => $postUrl, 'thumb' => $thumbUrl];
                }
            }
        }
        if ($canViewMembersList) {
            $communityMembersPreview = $iN->iN_GetCommunityMembers($communityID, 10);
        }
    }
?>
    <div class="profile_wrapper community_profile_wrapper">
        <input type="hidden" class="community_csrf_token" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">
        <div class="i_profile_container">
            <div class="i_profile_cover_blur" data-background="<?php echo iN_HelpSecure($coverUrl); ?>" role="img" aria-label="<?php echo iN_HelpSecure($communityTitle); ?>"></div>
            <div class="i_profile_i_container">
                <div class="i_profile_infos_wrapper">
                    <div class="i_profile_cover">
                        <div class="i_im_cover">
                            <img src="<?php echo iN_HelpSecure($coverUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>">
                        </div>
                        <div class="i_profile_avatar_container">
                            <div class="i_profile_avatar_wrp">
                                <div class="i_profile_avatar" data-avatar="<?php echo iN_HelpSecure($communityAvatarUrl); ?>" role="img" aria-label="<?php echo iN_HelpSecure($communityTitle); ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="i_u_profile_info">
                        <div class="i_u_name"><?php echo iN_HelpSecure($communityTitle); ?></div>
                        <?php if (!empty($ownerName)) { ?>
                            <div class="i_u_name_mention">
                                <a href="<?php echo iN_HelpSecure($ownerUrl); ?>">
                                    <?php echo iN_HelpSecure($LANG['community_owner']); ?>: <?php echo iN_HelpSecure($ownerName); ?>
                                    <span class="community_role_icon community_owner_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?></span>
                                </a>
                            </div>
                        <?php } ?>
                        <?php if (!empty($communityCategory)) { ?>
                            <div class="i_p_cards">
                                <div class="i_creator_category"><?php echo iN_HelpSecure($communityCategoryLabel); ?></div>
                            </div>
                        <?php } ?>
                        <div class="i_p_cards">
                            <div class="i_creator_category">
                                <?php echo iN_HelpSecure($LANG['community_monthly_price']); ?>:
                                <?php
                                    if ($subscriptionRequired === '0') {
                                        echo iN_HelpSecure($LANG['community_free'] ?? 'Free');
                                    } else {
                                        echo $priceLabel;
                                    }
                                ?>
                            </div>
                            <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['community_members']); ?>: <?php echo iN_HelpSecure($capacityLabel); ?></div>
                        </div>
                        <div class="i_p_items_box">
                            <?php if ($isBlocked) { ?>
                                <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['community_blocked'] ?? 'Community access blocked.'); ?></div>
                            <?php } elseif ($isViewRestricted) { ?>
                                <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['community_view_timeout_active'] ?? 'Community view timeout is active.'); ?></div>
                            <?php } elseif ($isOwner) { ?>
                                <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['community_owner_badge']); ?></div>
                            <?php } elseif ($isMember) { ?>
                                <button type="button" class="i_btn_unsubscribe community_unsub_btn transition communityUnsubModal" data-community="<?php echo (int)$communityID; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['unsubscribe']); ?>
                                </button>
                            <?php } elseif ($isModerator) { ?>
                                <a href="#community-content" class="i_btn_become_fun community_enter_btn transition">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['enter_community']); ?>
                                </a>
                            <?php } elseif ($isModerationListed) { ?>
                                <a href="#community-content" class="i_btn_become_fun community_enter_btn transition">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['enter_community']); ?>
                                </a>
                            <?php } elseif ($isFull) { ?>
                                <div class="i_creator_category"><?php echo iN_HelpSecure($LANG['community_full']); ?></div>
                            <?php } elseif ($subscriptionRequired === '0') { ?>
                                <button type="button" class="i_btn_become_fun community_join_btn transition <?php echo $logedIn == 0 ? 'loginForm' : 'communityJoinFree'; ?>" data-community="<?php echo (int)$communityID; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['join_community']); ?>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="i_btn_become_fun community_join_btn transition <?php echo $logedIn == 0 ? 'loginForm' : 'communityJoinModal'; ?>" data-community="<?php echo (int)$communityID; ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['join_community']); ?>
                                </button>
                            <?php } ?>
                        </div>
                        <?php if (!empty($communityDescription)) { ?>
                            <div class="i_p_item_box">
                                <div class="i_p_bio"><?php echo nl2br(iN_HelpSecure($communityDescription)); ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="th_middle">
            <div class="pageMiddle">
                <div class="pageMiddleRow">
                    <div class="pageMiddlePosts" id="community-content">
                        <?php if ($canViewCommunity) { ?>
                            <?php
                            $hasManageCards = $isOwner || $isAdmin || $canModerateMembers || $canModerateReshare || $canModerateTimeout || $canManageMedia;
                            ?>
                            <div class="community_content_layout">
                                <?php
                                $showInfoCard = $canViewCommunity;
                                $showManageColumn = $showInfoCard || $hasManageCards;
                                ?>
                                <?php if ($showManageColumn) { ?>
                                    <div class="community_manage_column">
                                        <?php if ($showInfoCard) { ?>
                                            <div class="profile_meta_card community_manage_card">
                                                <div class="profile_meta_header">
                                                    <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_manage_title'] ?? 'Manage Community'); ?></div>
                                                    <?php if ($isOwner || $isAdmin || $canManageMedia) { ?>
                                                        <button type="button" class="i_nex_btn communityEditModalBtn transition" data-community="<?php echo (int)$communityID; ?>" title="<?php echo iN_HelpSecure($LANG['community_edit'] ?? $LANG['edit'] ?? 'Edit'); ?>" aria-label="<?php echo iN_HelpSecure($LANG['community_edit'] ?? $LANG['edit'] ?? 'Edit'); ?>">
                                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?>
                                                        </button>
                                                    <?php } ?>
                                                </div>
                                                <div class="profile_meta_bio">
                                                    <div class="profile_meta_list">
                                                        <div class="profile_meta_row">
                                                            <div class="meta_text">
                                                                <span class="label"><?php echo iN_HelpSecure($LANG['community_name']); ?></span>
                                                                <span class="value"><?php echo iN_HelpSecure($communityTitle); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="profile_meta_row">
                                                            <div class="meta_text">
                                                                <span class="label"><?php echo iN_HelpSecure($LANG['community_category']); ?></span>
                                                                <span class="value"><?php echo iN_HelpSecure($communityCategoryLabel); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="profile_meta_row">
                                                            <div class="meta_text">
                                                                <span class="label"><?php echo iN_HelpSecure($LANG['community_description']); ?></span>
                                                                <span class="value"><?php echo iN_HelpSecure($communityDescription !== '' ? $communityDescription : '-'); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="profile_meta_row">
                                                            <div class="meta_text">
                                                                <span class="label"><?php echo iN_HelpSecure($LANG['community_monthly_price']); ?></span>
                                                                <span class="value">
                                                                    <?php
                                                                    echo $subscriptionRequired === '0'
                                                                        ? iN_HelpSecure($LANG['community_free'] ?? 'Free')
                                                                        : $priceLabel;
                                                                    ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="profile_meta_row">
                                                            <div class="meta_text">
                                                                <span class="label"><?php echo iN_HelpSecure($LANG['community_member_limit']); ?></span>
                                                                <span class="value"><?php echo $communityLimit > 0 ? iN_HelpSecure($communityLimit) : iN_HelpSecure($LANG['community_unlimited']); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php if ($isOwner && $subscriptionRequired === '0') { ?>
                                                        <div class="community_upgrade_cta">
                                                            <div class="community_upgrade_cta_text">
                                                                <div class="community_upgrade_cta_title"><?php echo iN_HelpSecure($LANG['community_upgrade_cta_title'] ?? 'Upgrade your community'); ?></div>
                                                                <div class="community_upgrade_cta_note"><?php echo iN_HelpSecure($LANG['community_upgrade_cta_note'] ?? 'Switch to paid to increase the member limit.'); ?></div>
                                                            </div>
                                                            <button type="button" class="i_nex_btn communityEditModalBtn transition community_upgrade_btn" data-community="<?php echo (int)$communityID; ?>">
                                                                <?php echo iN_HelpSecure($LANG['community_upgrade_cta_button'] ?? 'Upgrade to Paid / Increase limit'); ?>
                                                            </button>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if ($isOwner || $isAdmin) { ?>
                                                        <div class="community_form_actions">
                                                            <button type="button" class="i_btn_unsubscribe communityDeleteBtn transition" data-community="<?php echo (int)$communityID; ?>" data-confirm="<?php echo iN_HelpSecure($LANG['community_delete_confirm'] ?? 'Are you sure?'); ?>">
                                                                <?php echo iN_HelpSecure($LANG['community_delete'] ?? 'Delete Community'); ?>
                                                            </button>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($communityPhotos)) { ?>
                                                <div class="profile_media_section">
                                                    <div class="profile_media_header">
                                                        <div class="profile_media_title"><?php echo iN_HelpSecure($LANG['photos'] ?? 'Photos'); ?></div>
                                                    </div>
                                                    <div class="profile_media_grid">
                                                        <?php foreach ($communityPhotos as $mediaItem) { ?>
                                                            <a class="profile_media_item" href="<?php echo iN_HelpSecure($mediaItem['url'], FILTER_VALIDATE_URL); ?>" aria-label="<?php echo iN_HelpSecure($LANG['photos'] ?? 'Photos'); ?>">
                                                                <div class="profile_media_thumb">
                                                                    <img src="<?php echo iN_HelpSecure($mediaItem['thumb'], FILTER_VALIDATE_URL); ?>" alt="<?php echo iN_HelpSecure($LANG['photos'] ?? 'Photos'); ?>">
                                                                </div>
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                        <?php } ?>
                                        <?php if (!empty($communityVideos)) { ?>
                                                <div class="profile_media_section">
                                                    <div class="profile_media_header">
                                                        <div class="profile_media_title"><?php echo iN_HelpSecure($LANG['videos'] ?? 'Videos'); ?></div>
                                                    </div>
                                                    <div class="profile_media_grid">
                                                        <?php foreach ($communityVideos as $mediaItem) { ?>
                                                            <a class="profile_media_item" href="<?php echo iN_HelpSecure($mediaItem['url'], FILTER_VALIDATE_URL); ?>" aria-label="<?php echo iN_HelpSecure($LANG['videos'] ?? 'Videos'); ?>">
                                                                <div class="profile_media_thumb">
                                                                    <img src="<?php echo iN_HelpSecure($mediaItem['thumb'], FILTER_VALIDATE_URL); ?>" alt="<?php echo iN_HelpSecure($LANG['videos'] ?? 'Videos'); ?>">
                                                                </div>
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                        <?php } ?>
                                        <?php if ($canViewMembersList) { ?>
                                            <div class="profile_meta_card community_manage_card community_members_card">
                                                <div class="profile_meta_header">
                                                    <div class="profile_meta_title community_members_title">
                                                        <span><?php echo iN_HelpSecure($LANG['community_members_title'] ?? 'Community members'); ?></span>
                                                        <?php if ($memberCount > 10) { ?>
                                                            <button type="button" class="communityMembersModalBtn transition" data-community="<?php echo (int)$communityID; ?>">
                                                                <?php echo iN_HelpSecure($LANG['community_members_view_all'] ?? 'View all'); ?>
                                                            </button>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="profile_meta_bio">
                                                    <?php if (!empty($communityMembersPreview)) { ?>
                                                        <div class="community_members_grid">
                                                            <?php foreach ($communityMembersPreview as $member) {
                                                                $memberID = (int)($member['user_id'] ?? 0);
                                                                if ($memberID <= 0) {
                                                                    continue;
                                                                }
                                                                $memberName = $member['i_user_fullname'] ?: ($member['i_username'] ?? '');
                                                                $memberAvatarUrl = $iN->iN_UserAvatar($memberID, $base_url);
                                                                include __DIR__ . '/community/communityMemberCard.php';
                                                            } ?>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="community_empty_state"><?php echo iN_HelpSecure($LANG['community_members_empty'] ?? 'No members yet.'); ?></div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($logedIn == 1 && ($canModerateMembers || $canModerateReshare || $canModerateTimeout || $canModeratePosts || $canModerateComments)) {
                                            $moderationUsers = $iN->iN_GetCommunityModerationUsers($communityID, 50);
                                        ?>
                                            <div class="profile_meta_card community_manage_card community_moderation_card">
                                                <div class="profile_meta_header community_moderation_header">
                                                    <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_moderation_title'] ?? 'Community Moderation'); ?></div>
                                                    <?php if ($isOwner || $isAdmin) { ?>
                                                        <button type="button" class="communityModerationModalBtn transition" data-community="<?php echo (int)$communityID; ?>" aria-label="<?php echo iN_HelpSecure($LANG['community_moderation_add_user'] ?? 'Add User'); ?>">
                                                            <?php echo iN_HelpSecure($LANG['community_moderation_add_user'] ?? 'Add User'); ?>
                                                        </button>
                                                    <?php } ?>
                                                </div>
                                                <div class="profile_meta_bio">
                                                    <?php if (!empty($moderationUsers)) { ?>
                                                        <div class="community_moderation_grid">
                                                            <?php foreach ($moderationUsers as $member) {
                                                                $memberID = (int)($member['user_id'] ?? 0);
                                                                if ($memberID <= 0) {
                                                                    continue;
                                                                }
                                                                $memberName = $member['i_user_fullname'] ?: ($member['i_username'] ?? '');
                                                                $memberAvatarUrl = $iN->iN_UserAvatar($memberID, $base_url);
                                                                $memberStatus = $member['restriction_status'] ?? 'active';
                                                                if (!in_array($memberStatus, ['restricted', 'blocked'], true)) {
                                                                    $memberStatus = 'active';
                                                                }
                                                                $memberReshareDisabled = (string)($member['reshare_disabled'] ?? '0') === '1';
                                                                $memberViewUntil = $member['view_until'] ?? null;
                                                                $memberViewPermanent = (string)($member['view_permanent'] ?? '0') === '1';
                                                            ?>
                                                                <?php include __DIR__ . '/community/communityModerationRow.php'; ?>
                                                            <?php } ?>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="community_empty_state"><?php echo iN_HelpSecure($LANG['community_moderation_empty'] ?? 'No users found.'); ?></div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if ($isOwner || $isAdmin) {
                                            $communityModerators = $iN->iN_GetCommunityModerators($communityID, 50);
                                        ?>
                                            <div class="profile_meta_card community_manage_card community_moderators_card">
                                                <div class="profile_meta_header">
                                                    <div class="profile_meta_title community_moderator_title">
                                                        <span><?php echo iN_HelpSecure($LANG['community_moderators_title'] ?? 'Moderators'); ?></span>
                                                        <?php if ($isOwner || $isAdmin) { ?>
                                                            <button type="button" class="communityModeratorModalBtn transition" data-community="<?php echo (int)$communityID; ?>">
                                                                <?php echo iN_HelpSecure($LANG['community_moderator_add_btn'] ?? 'Add moderator'); ?>
                                                            </button>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="profile_meta_bio">
                                                    <?php if (!empty($communityModerators)) { ?>
                                                        <div class="community_moderators_grid">
                                                            <?php foreach ($communityModerators as $moderatorRow) {
                                                                $moderatorId = (int)($moderatorRow['user_id'] ?? 0);
                                                                if ($moderatorId <= 0) {
                                                                    continue;
                                                                }
                                                                $moderatorName = $moderatorRow['i_user_fullname'] ?: ($moderatorRow['i_username'] ?? '');
                                                                $moderatorAvatarUrl = $iN->iN_UserAvatar($moderatorId, $base_url);
                                                            ?>
                                                                <?php include __DIR__ . '/community/communityModeratorRow.php'; ?>
                                                            <?php } ?>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="community_empty_state"><?php echo iN_HelpSecure($LANG['community_moderator_empty'] ?? 'No moderators assigned.'); ?></div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <?php if ($isOwner) { ?>
                                                <?php
                                                $moderationActions = $iN->iN_GetCommunityModeratorActions($communityID, 50);
                                                ?>
                                                <div class="profile_meta_card community_manage_card community_moderation_actions_card">
                                                    <div class="profile_meta_header">
                                                        <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_moderation_actions_title'] ?? 'Moderation Actions'); ?></div>
                                                    </div>
                                                    <div class="profile_meta_bio">
                                                        <?php if (!empty($moderationActions)) { ?>
                                                            <div class="community_moderation_actions_list">
                                                                <?php foreach ($moderationActions as $action) {
                                                                    $actionType = $action['action_type'] ?? '';
                                                                    $actionData = [];
                                                                    if (!empty($action['action_data'])) {
                                                                        $decodedAction = json_decode($action['action_data'], true);
                                                                        if (is_array($decodedAction)) {
                                                                            $actionData = $decodedAction;
                                                                        }
                                                                    }
                                                                    $moderatorName = $action['moderator_fullname'] ?: ($action['moderator_username'] ?? '');
                                                                    $moderatorAvatarUrl = !empty($action['moderator_avatar'])
                                                                        ? $iN->iN_UserAvatar((int)($action['moderator_id'] ?? 0), $base_url)
                                                                        : $base_url . 'uploads/avatars/no_gender.png';
                                                                    $targetName = $action['target_fullname'] ?: ($action['target_username'] ?? '');
                                                                    $actionLabel = $LANG['community_moderation_action_generic'] ?? 'Action';
                                                                    $targetLabel = $targetName;
                                                                    if (in_array($actionType, ['post_hide', 'post_unhide'], true)) {
                                                                        $actionLabel = $actionType === 'post_hide'
                                                                            ? ($LANG['community_moderation_action_post_hide'] ?? 'Post hidden')
                                                                            : ($LANG['community_moderation_action_post_unhide'] ?? 'Post unhidden');
                                                                        $targetLabel = str_replace('{id}', (int)($action['target_post_id'] ?? 0), $LANG['community_moderation_target_post'] ?? 'Post #{id}');
                                                                    } elseif (in_array($actionType, ['comment_hide', 'comment_unhide'], true)) {
                                                                        $actionLabel = $actionType === 'comment_hide'
                                                                            ? ($LANG['community_moderation_action_comment_hide'] ?? 'Comment hidden')
                                                                            : ($LANG['community_moderation_action_comment_unhide'] ?? 'Comment unhidden');
                                                                        $targetLabel = str_replace('{id}', (int)($action['target_comment_id'] ?? 0), $LANG['community_moderation_target_comment'] ?? 'Comment #{id}');
                                                                    } elseif ($actionType === 'member_status') {
                                                                        $nextStatus = $actionData['next']['status'] ?? '';
                                                                        $statusLabel = $LANG['community_member_active'] ?? 'Active';
                                                                        if ($nextStatus === 'blocked') {
                                                                            $statusLabel = $LANG['community_member_blocked'] ?? 'Blocked';
                                                                        } elseif ($nextStatus === 'restricted') {
                                                                            $statusLabel = $LANG['community_member_restricted'] ?? 'Restricted';
                                                                        }
                                                                        $actionLabel = ($LANG['community_moderation_action_member_status'] ?? 'Member status updated') . ': ' . $statusLabel;
                                                                    } elseif ($actionType === 'reshare_control') {
                                                                        $reshareDisabled = (string)($actionData['next']['reshare_disabled'] ?? '0') === '1';
                                                                        $reshareLabel = $reshareDisabled
                                                                            ? ($LANG['community_moderation_reshare_disabled'] ?? 'Reshare disabled')
                                                                            : ($LANG['community_moderation_reshare_enabled'] ?? 'Reshare enabled');
                                                                        $actionLabel = ($LANG['community_moderation_action_reshare'] ?? 'Reshare updated') . ': ' . $reshareLabel;
                                                                    } elseif ($actionType === 'post_control') {
                                                                        $postDisabled = (string)($actionData['next']['post_disabled'] ?? '0') === '1';
                                                                        $postLabel = $postDisabled
                                                                            ? ($LANG['community_moderation_posts_disabled'] ?? 'Posts disabled')
                                                                            : ($LANG['community_moderation_posts_enabled'] ?? 'Posts enabled');
                                                                        $actionLabel = ($LANG['community_moderation_action_posts'] ?? 'Posts updated') . ': ' . $postLabel;
                                                                    } elseif ($actionType === 'comment_control') {
                                                                        $commentDisabled = (string)($actionData['next']['comment_disabled'] ?? '0') === '1';
                                                                        $commentLabel = $commentDisabled
                                                                            ? ($LANG['community_moderation_comments_disabled'] ?? 'Comments disabled')
                                                                            : ($LANG['community_moderation_comments_enabled'] ?? 'Comments enabled');
                                                                        $actionLabel = ($LANG['community_moderation_action_comments'] ?? 'Comments updated') . ': ' . $commentLabel;
                                                                    } elseif ($actionType === 'view_timeout') {
                                                                        $nextPermanent = (string)($actionData['next']['view_permanent'] ?? '0') === '1';
                                                                        $nextUntil = $actionData['next']['view_until'] ?? null;
                                                                        if ($nextPermanent) {
                                                                            $timeoutLabel = $LANG['community_moderation_timeout_permanent'] ?? 'Permanent';
                                                                        } elseif (!empty($nextUntil)) {
                                                                            $timeoutLabel = str_replace('{date}', date('M d, Y', strtotime((string)$nextUntil)), $LANG['community_moderation_timeout_until'] ?? 'Until {date}');
                                                                        } else {
                                                                            $timeoutLabel = $LANG['community_moderation_timeout_none'] ?? 'No timeout';
                                                                        }
                                                                        $actionLabel = ($LANG['community_moderation_action_view_timeout'] ?? 'View timeout updated') . ': ' . $timeoutLabel;
                                                                    }
                                                                    $actionTime = $action['created_at'] ?? '';
                                                                    $actionTimeLabel = $actionTime !== '' ? TimeAgo::ago($actionTime, date('Y-m-d H:i:s')) : '';
                                                                    $isReverted = !empty($action['reverted_at']);
                                                                ?>
                                                                    <?php include __DIR__ . '/community/communityModerationActionRow.php'; ?>
                                                                <?php } ?>
                                                            </div>
                                                        <?php } else { ?>
                                                            <div class="community_empty_state"><?php echo iN_HelpSecure($LANG['community_moderation_actions_empty'] ?? 'No actions yet.'); ?></div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="community_feed_column">
                                    <?php if ($canPostToCommunity) { ?>
                                        <?php
                                        $page = 'community';
                                        $disableDayMessage = true;
                                        $hideSubscriberVisibility = ($accessPolicy === 'members_only' && $subscriptionRequired === '0');
                                        if ($hideSubscriberVisibility && isset($userWhoCanSeePost) && (string)$userWhoCanSeePost === '3') {
                                            $userWhoCanSeePost = 1;
                                            $activeWhoCanSee = '<div class="form_who_see_icon_set">' .
                                                $iN->iN_SelectedMenuIcon('50') . '</div> ' . ($LANG['weveryone'] ?? 'Everyone');
                                        }
                                        include __DIR__ . '/posts/postForm.php';
                                        ?>
                                    <?php } elseif (($isOwner || $isAdmin) && $postingEnabled !== '1') { ?>
                                        <div class="profile_meta_card community_manage_card">
                                            <div class="profile_meta_header">
                                                <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_posting_disabled'] ?? 'Posting disabled.'); ?></div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <?php
                                    $page = 'community';
                                    $communityID = $communityID;
                                    $communityModCanManagePosts = $canModeratePosts;
                                    $communityModCanManageComments = $canModerateComments;
                                    $communityReshareDisabled = $memberReshareDisabled;
                                    echo '<div id="moreType" data-type="community" data-community="' . (int)$communityID . '">';
                                    include __DIR__ . '/posts/htmlPosts.php';
                                    echo '</div>';
                                    ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <?php if ($isBlocked) { ?>
                                <div class="profile_meta_card">
                                    <div class="profile_meta_header">
                                        <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_blocked'] ?? 'Community access blocked.'); ?></div>
                                    </div>
                                </div>
                            <?php } elseif ($isViewRestricted) { ?>
                                <div class="profile_meta_card">
                                    <div class="profile_meta_header">
                                        <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_view_timeout_active'] ?? 'Community view timeout is active.'); ?></div>
                                    </div>
                                </div>
                            <?php } else { ?>
                            <div class="profile_meta_card">
                                <div class="profile_meta_header">
                                    <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['community_paywall_title']); ?></div>
                                </div>
                                <div class="profile_meta_bio">
                                    <div class="profile_meta_bio_text">
                                        <?php
                                            $paywallNote = $subscriptionRequired === '0'
                                                ? ($LANG['community_free_access_note'] ?? 'Join this community to access the feed.')
                                                : $LANG['community_paywall_note'];
                                            echo iN_HelpSecure($paywallNote);
                                        ?>
                                    </div>
                                </div>
                                <?php if (!$isFull) { ?>
                                    <div class="i_p_items_box">
                                        <button type="button" class="i_btn_become_fun community_join_btn transition <?php echo $logedIn == 0 ? 'loginForm' : ($subscriptionRequired === '0' ? 'communityJoinFree' : 'communityJoinModal'); ?>" data-community="<?php echo (int)$communityID; ?>">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['join_community']); ?>
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="i_not_found_page transition i_centered">
        <div class="noPostIcon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?></div>
        <h1><?php echo iN_HelpSecure($LANG['community_not_found']); ?></h1>
        <?php echo iN_HelpSecure($LANG['community_not_found_note']); ?>
    </div>
<?php } ?>
</body>
</html>
