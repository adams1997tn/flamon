<div class="i_contents_container manage-users-edit-page">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box flex_ tabing_non_justify">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('83')) . $LANG['edit_user_profile_details']; ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <form enctype="multipart/form-data" method="post" id="editUserDetails">
                <?php
                $editUserData = $iN->iN_GetUserDetails($editUserID);
                $validationStatus = $editUserData['validation_status'] ?? null;
                $editUserType = $editUserData['userType'] ?? null;
                $editUserProfileUserName = $editUserData['i_username'] ?? null;
                $moderatorPermissionDefinitions = $iN->iN_GetModeratorPermissionDefinitions();
                $moderatorPermissionValues = $iN->iN_GetModeratorPermissions($editUserID);
                $moderatorPermissionPackages = $iN->iN_GetModeratorPermissionPackages();
                $canSubmitUserUpdate = $iN->iN_CheckIsSuperAdmin((int)$userID) == 1;
                $canDeleteUsers = (string)$userType !== '3' || $iN->iN_CanModeratorRunAdminAction((int)$userID, 'deleteUser');
                $canLoginAsUser = (string)$userType !== '3' || $iN->iN_CanModeratorAccessAdminPage((int)$userID, 'login_as_user');
                $selectAllLabel = $LANG['moderator_permission_select_all'] ?? ($LANG['all'] ?? 'All');
                $selectNoneLabel = $LANG['moderator_permission_select_none'] ?? 'None';

                if ($validationStatus == '2') {
                    $validStatus = $LANG['premium_user'];
                } elseif ($validationStatus == '1') {
                    $validStatus = $LANG['verification_pending'];
                } else {
                    $validStatus = $LANG['not_verified'];
                }

                if ($editUserType == '1') {
                    $userRole = $LANG['normal_user'];
                } elseif ($editUserType == '2') {
                    $userRole = $LANG['admin'];
                } else {
                    $userRole = $LANG['moderator'];
                }

                $editUserWallet = $editUserData['wallet_points'] ?? null;
                $edit_ProfileUrl = $base_url . $editUserProfileUserName;
                ?>

                <div class="i_p_e_body editAds_padding zero_margin_bottom">
                    <?php echo csrf_token_field(); ?>
                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['verified_status']); ?>
                        </div>
                        <div class="irow_box_right">
                            <div class="i_box_limit flex_ column">
                                <div class="i_limit" data-type="verification">
                                    <span class="lct"><?php echo iN_HelpSecure($validStatus); ?></span>
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                                </div>
                                <div class="i_limit_list_ch_container">
                                    <div class="i_countries_list border_one column flex_">
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($validationStatus) == '2' ? 'choosed' : ''; ?>" id="2" data-c="<?php echo iN_HelpSecure($LANG['premium_user']); ?>" data-type="verfUser"><?php echo iN_HelpSecure($LANG['premium_user']); ?></div>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($validationStatus) == '1' ? 'choosed' : ''; ?>" id="1" data-c="<?php echo iN_HelpSecure($LANG['verification_pending']); ?>" data-type="verfUser"><?php echo iN_HelpSecure($LANG['verification_pending']); ?></div>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($validationStatus) == '0' ? 'choosed' : ''; ?>" id="0" data-c="<?php echo iN_HelpSecure($LANG['not_verified']); ?>" data-type="verfUser"><?php echo iN_HelpSecure($LANG['not_verified']); ?></div>
                                    </div>
                                    <input type="hidden" name="verification" id="verification" value="<?php echo iN_HelpSecure($validationStatus); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['role']); ?>
                        </div>
                        <div class="irow_box_right">
                            <div class="i_box_limit flex_ column">
                                <div class="i_limit" data-type="usertype">
                                    <span class="lut"><?php echo iN_HelpSecure($userRole); ?></span>
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                                </div>
                                <div class="i_limit_list_cp_container">
                                    <div class="i_countries_list border_one column flex_">
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($editUserType) == '1' ? 'choosed' : ''; ?>" id="1" data-c="<?php echo iN_HelpSecure($LANG['normal_user']); ?>" data-type="usrtyp"><?php echo iN_HelpSecure($LANG['normal_user']); ?></div>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($editUserType) == '2' ? 'choosed' : ''; ?>" id="2" data-c="<?php echo iN_HelpSecure($LANG['admin']); ?>" data-type="usrtyp"><?php echo iN_HelpSecure($LANG['admin']); ?></div>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($editUserType) == '3' ? 'choosed' : ''; ?>" id="3" data-c="<?php echo iN_HelpSecure($LANG['moderator']); ?>" data-type="usrtyp"><?php echo iN_HelpSecure($LANG['moderator']); ?></div>
                                    </div>
                                    <input type="hidden" name="usertype" id="usertype" value="<?php echo iN_HelpSecure($editUserType); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="i_general_row_box_item flex_ tabing_non_justify moderator_permission_row" style="<?php echo (string)$editUserType === '3' ? '' : 'display:none;'; ?>">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['moderator']); ?>
                        </div>
                        <div class="irow_box_right">
                            <div class="moderator_permission_panel">
                                <?php foreach ($moderatorPermissionPackages as $packageKey => $packageData) {
                                    $packageLabel = isset($packageData['label']) ? (string)$packageData['label'] : '';
                                    $packagePages = isset($packageData['pages']) && is_array($packageData['pages']) ? $packageData['pages'] : [];
                                    if (empty($packagePages)) {
                                        continue;
                                    }
                                    $packageId = 'modpack_' . preg_replace('/[^a-z0-9_]+/i', '_', (string)$packageKey);
                                ?>
                                    <div class="mod_perm_package" data-package="<?php echo iN_HelpSecure($packageId); ?>">
                                        <div class="mod_perm_package_head flex_ tabing_non_justify">
                                            <div class="mod_perm_package_title flex_ tabing_non_justify">
                                                <span class="mod_perm_package_dot"></span>
                                                <span><?php echo iN_HelpSecure($packageLabel); ?></span>
                                            </div>
                                            <button
                                                type="button"
                                                class="mod_perm_package_toggle"
                                                data-target-package="<?php echo iN_HelpSecure($packageId); ?>"
                                                data-text-all="<?php echo iN_HelpSecure($selectAllLabel); ?>"
                                                data-text-none="<?php echo iN_HelpSecure($selectNoneLabel); ?>"
                                            >
                                                <?php echo iN_HelpSecure($selectAllLabel); ?>
                                            </button>
                                        </div>
                                        <div class="mod_perm_package_count" data-package-count="<?php echo iN_HelpSecure($packageId); ?>">0/0</div>
                                        <div class="mod_perm_package_items">
                                            <?php foreach ($packagePages as $pageData) {
                                                $pageLabel = isset($pageData['label']) ? (string)$pageData['label'] : '';
                                                $pageFeatures = isset($pageData['features']) && is_array($pageData['features']) ? $pageData['features'] : [];
                                                if (empty($pageFeatures)) {
                                                    continue;
                                                }
                                            ?>
                                                <div class="mod_perm_page">
                                                    <div class="mod_perm_page_title"><?php echo iN_HelpSecure($pageLabel); ?></div>
                                                    <div class="mod_perm_page_features">
                                                        <?php foreach ($pageFeatures as $featureData) {
                                                            $permissionKey = isset($featureData['key']) ? (string)$featureData['key'] : '';
                                                            $permissionLabel = isset($featureData['label']) ? (string)$featureData['label'] : '';
                                                            $permissionDescription = isset($featureData['description']) ? (string)$featureData['description'] : '';
                                                            if ($permissionKey === '' || !isset($moderatorPermissionDefinitions[$permissionKey])) {
                                                                continue;
                                                            }
                                                            $isGranted = (string)($moderatorPermissionValues[$permissionKey] ?? '0') === '1';
                                                        ?>
                                                            <label class="mod_perm_chip <?php echo $isGranted ? 'is_checked' : ''; ?>">
                                                                <input
                                                                    type="checkbox"
                                                                    class="mod_perm_checkbox"
                                                                    data-package="<?php echo iN_HelpSecure($packageId); ?>"
                                                                    name="mod_perm[]"
                                                                    value="<?php echo iN_HelpSecure($permissionKey); ?>"
                                                                    <?php echo $isGranted ? 'checked' : ''; ?>
                                                                >
                                                                <span class="mod_perm_chip_icon">&#10003;</span>
                                                                <span class="mod_perm_chip_text">
                                                                    <span class="mod_perm_chip_title"><?php echo iN_HelpSecure($permissionLabel); ?></span>
                                                                    <span class="mod_perm_chip_desc"><?php echo iN_HelpSecure($permissionDescription); ?></span>
                                                                </span>
                                                            </label>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                </div>
                        </div>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify">
                        <div class="irow_box_left tabing flex_">
                            <?php echo iN_HelpSecure($LANG['wallet']); ?>
                        </div>
                        <div class="irow_box_right">
                            <input type="text" name="uwallet" class="i_input flex_" onkeypress="return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)" value="<?php echo iN_HelpSecure($editUserWallet); ?>">
                        </div>
                    </div>

                    <div class="i_settings_wrapper_item successNot">
                        <?php echo iN_HelpSecure($LANG['updated_successfully']); ?>
                    </div>

                    <div class="i_general_row_box_item flex_ tabing_non_justify zero_margin_bottom">
                        <input type="hidden" name="f" value="editUserDetails">
                        <input type="hidden" name="u" value="<?php echo iN_HelpSecure($editUserID); ?>">
                        <?php if ($canSubmitUserUpdate) { ?>
                            <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateGeneralSettings">
                                <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                            </button>
                        <?php } else { ?>
                            <div class="warning_"><?php echo iN_HelpSecure($LANG['do_not_have_this_authority']); ?></div>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="i_edit_u_wrapper border_one column flex_ tabing__justify white_board_style mTop">
        <div class="i_edit_u_cont flex_ tabing">
            <div class="i_edit_btn_items tabing flex_">
                <a href="<?php echo iN_HelpSecure($edit_ProfileUrl); ?>" target="blank_">
                    <div class="ed_btn tabing flex_ border_one c3">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('83')) . $LANG['see_profile']; ?>
                    </div>
                </a>
            </div>
            <?php if ($canLoginAsUser) { ?>
                <div class="i_edit_btn_items tabing flex_">
                    <a href="<?php echo iN_HelpSecure($base_url) . 'admin/login_as_user?user=' . $editUserID; ?>">
                        <div class="ed_btn tabing flex_ border_one c2">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('7')) . $LANG['login_as_user']; ?>
                        </div>
                    </a>
                </div>
            <?php } ?>
            <?php if ($canDeleteUsers) { ?>
                <div class="i_edit_btn_items tabing flex_">
                    <div class="ed_btn del_us tabing flex_ border_one c7" id="<?php echo iN_HelpSecure($editUserID); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . $LANG['delete_user']; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="i_edit_u_cont tabing box_not_padding_top_package">
            <?php echo iN_HelpSecure($LANG['important_for_login_as_user']); ?>
        </div>
    </div>
</div>
