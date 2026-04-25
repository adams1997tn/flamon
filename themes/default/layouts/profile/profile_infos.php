<div class="i_profile_container">
   <div class="i_profile_cover_blur" data-background="<?php echo iN_HelpSecure($p_profileCover); ?>" role="img" aria-label="<?php echo iN_HelpSecure($LANG['profile_cover_image_alt'] ?? 'Profile cover image'); ?>"></div>
   <div class="i_profile_i_container">
       <div class="i_profile_infos_wrapper">
          <!--PROFILE COVER AND AVATAR-->
          <div class="i_profile_cover">
              <div class="i_im_cover">
                 <img src="<?php echo iN_HelpSecure($p_profileCover); ?>">
              </div>
              <div class="i_profile_avatar_container">
                  <div class="i_profile_avatar_wrp">
                    <?php if($p_frame){ ?>
                        <div class="i_profile_gift_frame flex_ tabing"><img src="<?php echo $base_url.$p_frame;?>"></div>
                    <?php } ?>
                    <div class="i_profile_avatar<?php echo ($p_profileID == $userID) ? ' js-profile-avatar-menu' : ''; ?>" data-avatar="<?php echo iN_HelpSecure($p_profileAvatar); ?>" role="img" aria-label="<?php echo iN_HelpSecure($LANG['profile_avatar_image_alt'] ?? 'Profile avatar image'); ?>"<?php if($p_profileID == $userID){ echo ' tabindex="0"'; } ?>>
                    <?php echo html_entity_decode($p_is_creator); ?>
                        <?php if($p_profileID == $userID){ ?>
                        <div class="frame_badge"><a class="flex_ tabing" href="<?php echo iN_HelpSecure($base_url).'settings?tab=myframes'?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('180'));?></a></div>
                        <div class="i_profile_avatar_menu" role="menu" aria-hidden="true">
                            <div class="i_profile_avatar_menu_item js-view-profile-photo" role="menuitem" tabindex="0">
                                <span class="i_pavm_ic"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')); ?></span>
                                <span><?php echo iN_HelpSecure($LANG['view_profile_photo'] ?? 'View profile photo'); ?></span>
                            </div>
                            <div class="i_profile_avatar_menu_item editAvatarCover" role="menuitem" tabindex="0">
                                <span class="i_pavm_ic"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?></span>
                                <span><?php echo iN_HelpSecure($LANG['change_profile_photo'] ?? 'Change profile photo'); ?></span>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                  </div>
              </div>
          </div>

          <style>   

            
          </style>
          <!--/PROFILE COVER AND AVATAR-->
          <!--USER PROFILE INFO-->
          <div class="i_u_profile_info">
               <div class="i_u_name">
                   <?php echo iN_HelpSecure($p_userfullname); ?><?php echo html_entity_decode($pVerifyStatus); ?> <?php echo html_entity_decode($pGender); ?>
               </div>
               <?php echo html_entity_decode($pTime); ?>
               <div class="i_p_cards">
                  <?php echo html_entity_decode($pCategory); ?>
               </div>
               <?php if ($p_friend_status != 'me') {?>
               <div class="i_p_cards">
                  <?php echo html_entity_decode($sendMessage); ?>
                  <div class="i_btn_item transition copyUrl tabing ownTooltip" data-label="<?php echo iN_HelpSecure($LANG['copy_profile_url']);?>" data-clipboard-text="<?php echo iN_HelpSecure($profileUrl); ?>">
                     <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('30')); ?>
                  </div>
                  <div class="i_btn_item <?php echo iN_HelpSecure($blockBtn); ?> transition tabing ownTooltip" data-label="<?php echo iN_HelpSecure($LANG['block_this_user']);?>" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
                     <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('64')); ?>
                  </div>
                  <?php if ($p_friend_status != 'subscriber') {?>
                    <div class="i_fw<?php echo iN_HelpSecure($p_profileID); ?> transition <?php echo iN_HelpSecure($flwrBtn); ?>" id="i_btn_like_item" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
                      <?php echo html_entity_decode($flwBtnIconText); ?>
                    </div>
                  <?php }?>
               </div>
               <?php if($pCertificationStatus == '2' && $pValidationStatus == '2' && $feesStatus == '2'){ ?>
               <div class="i_p_items_box">
                    <?php if ($p_friend_status != 'subscriber') {?>
                        <div class="i_btn_become_fun <?php echo iN_HelpSecure($subscBTN); ?> transition" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . $LANG['become_a_subscriber']; ?>
                        </div>
                    <?php } else { ?>
                        <div class="i_btn_unsubscribe transition unSubUP" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . $LANG['unsubscribe']; ?>
                        </div>
                    <?php }?>
                    <div class="i_btn_send_to_point transition sendPoint tabing flex_" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('145')) . $LANG['offer_a_tip']; ?>
                    </div>
                    <div class="i_btn_send_to_frame transition sendFrame tabing flex_" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('180')) . $LANG['gift_a_frame']; ?>
                    </div>
               </div>
               <?php }?>
               <?php }?>
                   <?php
                    $sociallinks = $iN->iN_ShowUserSocialSites($p_profileID);
                   if($sociallinks){
                        echo '<div class="i_profile_menu"><div class="i_profile_menu_middle flex_ tabing">';
                        foreach($sociallinks as $sDa){
                            $sLink = $sDa['s_link'] ?? NULL;
                            $sIcon = $sDa['social_icon'] ?? NULL;
                            echo '<div class="s_m_link flex_ tabing"><a class="flex_ tabing" href="'.iN_HelpSecure($sLink).'">'.$sIcon.'</a></div>';
                        }
                        echo '</div></div>';
                   }?>
                <?php
                $check = $iN->iN_CheckUnsubscribeStatus($userID, $p_profileID);
                if($unSubscribeStyle == 'yes' && $check && $userID != $p_profileID){ $finishTime = date('Y-m-d', strtotime($check)); ?>
                     <div class="i_p_item_box">
                       <div class="sub_finish_time"><?php echo preg_replace( '/{.*?}/', $finishTime, $LANG['subs_finish_at']); ?></div>
                     </div>
                <?php } ?>
               <?php if ($p_profileBio) {?>
               <div class="i_p_item_box">
                   <div class="i_p_bio"><?php echo html_entity_decode($p_profileBio); ?></div>
               </div>
               <?php }?>
               <?php include __DIR__ . '/highlights.php'; ?>
               <div class="i_p_item_box flex_ tabing">
                   <div class="i_p_ffs flex_ tabing <?php echo iN_HelpSecure($pCat) == 'following' ? "active_page_menu" : ""; ?>">
                       <a class="flex_ tabing" href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=following');?>">
                           <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('139'));?>
                           <?php echo iN_HelpSecure($LANG['im_following']);?> (<?php echo (int)$totalFollowingUsers; ?>)
                       </a>
                   </div>
                   <div class="i_p_ffs flex_ tabing <?php echo iN_HelpSecure($pCat) == 'followers' ? "active_page_menu" : ""; ?>">
                       <a class="flex_ tabing" href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=followers');?>">
                           <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('140'));?>
                           <?php echo iN_HelpSecure($LANG['my_followers']);?> (<?php echo (int)$totalFollowerUsers; ?>)
                       </a>
                   </div>
                   <?php if($pCertificationStatus == '2' && $pValidationStatus == '2' && $feesStatus == '2'){ ?>
                   <div class="i_p_ffs i_p_ffs_plus flex_ tabing <?php echo iN_HelpSecure($pCat) == 'subscribers' ? "active_page_menu" : ""; ?>">
                       <a class="flex_ tabing" href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=subscribers');?>">
                           <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('43'));?>
                           <?php echo iN_HelpSecure($LANG['subscribers']);?> (<?php echo (int)$totalSubscribers; ?>)
                       </a>
                   </div>
                   <?php }?>
               </div>
	               <div class="i_profile_menu">
	                   <div class="i_profile_menu_middle flex_ tabing">
	                        <!---->
	                        <div class="i_profile_menu_item <?php if (empty($pCat)) { echo 'active_page_menu'; } ?>">
	                            <a href="<?php echo iN_HelpSecure($profileUrl);?>">
	                            <div class="i_p_sum"><?php echo iN_HelpSecure($totalPost); ?></div>
	                            <div class="i_profile_menu_item_con flex_ tabing">
	                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('67')); ?></div>
	                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['profile_posts']);?></div>
	                            </div>
	                            </a>
	                        </div>
                        <!---->
                        <!---->
                        <div class="i_profile_menu_item <?php echo iN_HelpSecure($pCat) == 'polls' ? "active_page_menu" : ""; ?>">
                            <a href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=polls');?>">
                            <div class="i_p_sum"><?php echo iN_HelpSecure($iN->iN_TotalPollPostsByUser($p_profileID)); ?></div>
                            <div class="i_profile_menu_item_con flex_ tabing">
                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('110')); ?></div>
                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['profile_polls'] ?? 'Polls'); ?></div>
                            </div>
                            </a>
                        </div>
                        <!---->
                        <!---->
                        <div class="i_profile_menu_item <?php echo iN_HelpSecure($pCat) == 'photos' ? "active_page_menu" : ""; ?>">
                            <a href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=photos');?>">
                            <div class="i_p_sum"><?php echo iN_HelpSecure($totalImagePost); ?></div>
                            <div class="i_profile_menu_item_con flex_ tabing">
                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')); ?></div>
                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['profile_post_images']);?></div>
                            </div>
                            </a>
                        </div>
                        <!---->
                        <!---->
                        <div class="i_profile_menu_item <?php echo iN_HelpSecure($pCat) == 'videos' ? "active_page_menu" : ""; ?>">
                            <a href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=videos');?>">
                            <div class="i_p_sum"><?php echo iN_HelpSecure($totalVideoPosts); ?></div>
                            <div class="i_profile_menu_item_con flex_ tabing">
                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('52')); ?></div>
                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['profile_videos']);?></div>
                            </div>
                            </a>
                        </div>
                        <!---->
                        <!---->
                        <div class="i_profile_menu_item <?php echo iN_HelpSecure($pCat) == 'reels' ? "active_page_menu" : ""; ?>">
                            <a href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=reels');?>">
                            <div class="i_p_sum"><?php echo iN_HelpSecure($totalReelsPosts); ?></div>
                            <div class="i_profile_menu_item_con flex_ tabing">
                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('187')); ?></div>
                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['reels']);?></div>
                            </div>
                            </a>
                        </div>
                        <!---->
                        <!---->
                        <div class="i_profile_menu_item <?php echo iN_HelpSecure($pCat) == 'audios' ? "active_page_menu" : ""; ?>">
                            <a href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=audios');?>">
                            <div class="i_p_sum"><?php echo iN_HelpSecure($totalAudioPosts);?></div>
                            <div class="i_profile_menu_item_con flex_ tabing">
                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('152')); ?></div>
                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['profile_audios']);?></div>
                            </div>
                            </a>
                        </div>
                        <!---->
                        <?php if($iN->iN_ShopStatus(1) == 'yes'){?>
                        <!---->
                        <div class="i_profile_menu_item <?php echo iN_HelpSecure($pCat) == 'products' ? "active_page_menu" : ""; ?>">
                            <a href="<?php echo iN_HelpSecure($base_url.$p_username.'?pcat=products');?>">
                            <div class="i_p_sum"><?php echo iN_HelpSecure($totalProducts);?></div>
                            <div class="i_profile_menu_item_con flex_ tabing">
                                <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?></div>
                                <div class="i_profile_menu_item_name flex_ tabing"><?php echo iN_HelpSecure($LANG['profile_products']);?></div>
                            </div>
                            </a>
                        </div>
                        <!---->
                        <?php }?>
                    </div>
                </div>
          </div>
          <!--/USER PROFILE INFO-->
       </div>
   </div>
</div>

<style>
.i_profile_avatar.js-profile-avatar-menu { cursor: pointer; position: relative; }
.i_profile_avatar_menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    min-width: 220px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 6px rgba(0,0,0,0.08);
    padding: 6px;
    z-index: 50;
    display: none;
    font-family: system-ui, -apple-system, sans-serif;
}
.i_profile_avatar_menu.is-open { display: block; }
.i_profile_avatar_menu_item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #1f232d;
    user-select: none;
    transition: background 0.15s ease;
}
.i_profile_avatar_menu_item:hover,
.i_profile_avatar_menu_item:focus { background: #f0f2f5; outline: none; }
.i_profile_avatar_menu_item .i_pavm_ic svg { width: 18px; height: 18px; fill: #1f232d; display: block; }
.body_dark .i_profile_avatar_menu,
body.night .i_profile_avatar_menu { background: #242526; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.body_dark .i_profile_avatar_menu_item,
body.night .i_profile_avatar_menu_item { color: #e7e9ed; }
.body_dark .i_profile_avatar_menu_item:hover,
.body_dark .i_profile_avatar_menu_item:focus,
body.night .i_profile_avatar_menu_item:hover,
body.night .i_profile_avatar_menu_item:focus { background: #3a3b3c; }
.body_dark .i_profile_avatar_menu_item .i_pavm_ic svg,
body.night .i_profile_avatar_menu_item .i_pavm_ic svg { fill: #e7e9ed; }

.i_profile_photo_viewer {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}
.i_profile_photo_viewer img {
    max-width: 92vw;
    max-height: 92vh;
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.i_profile_photo_viewer_close {
    position: absolute;
    top: 18px;
    right: 22px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    color: #ffffff;
    font-size: 22px;
    line-height: 38px;
    text-align: center;
    cursor: pointer;
    user-select: none;
}
.i_profile_photo_viewer_close:hover { background: rgba(255,255,255,0.22); }
</style>

<script>
(function(){
    if (window.__profileAvatarMenuBound) { return; }
    window.__profileAvatarMenuBound = true;

    function closeMenu() {
        document.querySelectorAll('.i_profile_avatar_menu.is-open').forEach(function(el){
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
        });
    }

    document.addEventListener('click', function(e){
        var avatar = e.target.closest('.i_profile_avatar.js-profile-avatar-menu');
        if (avatar) {
            if (e.target.closest('.frame_badge') || e.target.closest('.i_profile_avatar_menu_item')) {
                return;
            }
            e.stopPropagation();
            var menu = avatar.querySelector('.i_profile_avatar_menu');
            if (!menu) return;
            var isOpen = menu.classList.contains('is-open');
            closeMenu();
            if (!isOpen) {
                menu.classList.add('is-open');
                menu.setAttribute('aria-hidden', 'false');
            }
            return;
        }
        if (!e.target.closest('.i_profile_avatar_menu')) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') { closeMenu(); }
    });

    document.addEventListener('click', function(e){
        var viewBtn = e.target.closest('.js-view-profile-photo');
        if (!viewBtn) return;
        e.preventDefault();
        e.stopPropagation();
        closeMenu();
        var avatarEl = viewBtn.closest('.i_profile_avatar');
        if (!avatarEl) return;
        var url = avatarEl.getAttribute('data-avatar') || '';
        if (!url) return;
        var viewer = document.createElement('div');
        viewer.className = 'i_profile_photo_viewer';
        viewer.innerHTML = '<div class="i_profile_photo_viewer_close" aria-label="Close">&times;</div><img alt="profile photo">';
        viewer.querySelector('img').src = url;
        viewer.addEventListener('click', function(ev){
            if (ev.target === viewer || ev.target.classList.contains('i_profile_photo_viewer_close')) {
                viewer.remove();
            }
        });
        document.body.appendChild(viewer);
    });
})();
</script>
