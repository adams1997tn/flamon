<?php
$communityID = isset($communityID) ? (int)$communityID : 0;
$communityCoverUrl = isset($communityCoverUrl) ? $communityCoverUrl : ($base_url . 'uploads/web.png');
$communityAvatarUrl = isset($communityAvatarUrl) ? $communityAvatarUrl : ($base_url . 'uploads/avatars/no_gender.png');
?>
<!-- Community Avatar & Cover Modal -->
<div class="i_modal_bg_in" role="dialog" aria-modal="true" data-community-id="<?php echo (int)$communityID; ?>">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_ac_header">
                <?php echo iN_HelpSecure($LANG['community_manage_title'] ?? 'Manage Community'); ?>
                <div class="shareClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>

            <div class="i_block_user_avatar_cover_wrapper">
                <div class="i_blck_in">
                    <div class="coverImageArea" data-bg="<?php echo iN_HelpSecure($communityCoverUrl); ?>">
                        <div class="newCoverUpload">
                            <label for="community_cover">
                                <input type="file" accept="image/*" class="nonePoint" id="community_cover" name="community_cover_file">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('84')); ?>
                            </label>
                        </div>
                    </div>
                    <div class="avatarImageArea flex_">
                        <div class="avatarImageWrapper">
                            <div class="avatarImage" data-bg="<?php echo iN_HelpSecure($communityAvatarUrl); ?>"></div>
                            <div class="newAvatarUpload">
                                <label for="community_avatar">
                                    <input type="file" accept="image/*" class="nonePoint" id="community_avatar" name="community_avatar_file">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('84')); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="i_block_box_footer_container">
                <div class="alertBtnRightWithIcon svAC transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['finished']); ?>">
                    <?php echo iN_HelpSecure($LANG['finished']); ?>
                </div>
                <div class="alertBtnLeft no-del transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>

            <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/crop/croppie.js?v=091<?php echo iN_HelpSecure($version); ?>"></script>
            <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/communityAvatarCoverCropHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
        </div>
    </div>
</div>

<div class="i_modal_cover_resize_bg_in" role="dialog" aria-modal="true">
    <div class="i_modal_in_in noTransition">
        <div class="i_modal_content">
            <div class="i_modal_ac_header">
                <?php echo iN_HelpSecure($LANG['cover_image_modification']); ?>
                <div class="coverCropClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="i_block_user_avatar_cover_wrapper">
                <div class="i_blck_in">
                    <div class="cropier_container">
                        <div class="crop_middle"><span id="community_cover_image"></span></div>
                    </div>
                </div>
            </div>
            <div class="i_block_box_footer_container">
                <div class="alertBtnRightWithIcon finishCrop transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['save_edit']); ?>">
                    <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                </div>
                <div class="alertBtnLeft cnclcrp transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="i_modal_avatar_resize_bg_in" role="dialog" aria-modal="true">
    <div class="i_modal_in_in noTransition">
        <div class="i_modal_content">
            <div class="i_modal_ac_header">
                <?php echo iN_HelpSecure($LANG['profile_image_modification']); ?>
                <div class="coverCropClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="i_block_user_avatar_cover_wrapper">
                <div class="i_blck_in">
                    <div class="cropier_container">
                        <div class="crop_middle"><span id="community_avatar_image"></span></div>
                    </div>
                </div>
            </div>
            <div class="i_block_box_footer_container">
                <div class="alertBtnRightWithIcon finishACrop transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['save_edit']); ?>">
                    <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                </div>
                <div class="alertBtnLeft cnclcrp transition" role="button" aria-label="<?php echo iN_HelpSecure($LANG['cancel']); ?>">
                    <?php echo iN_HelpSecure($LANG['cancel']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
