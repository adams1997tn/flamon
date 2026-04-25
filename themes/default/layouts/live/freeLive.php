<?php
$isLiveCreator = ((int)$userID === (int)$liveCreator);
$pinnedProduct = $iN->iN_GetLivePinnedProduct($liveID);
$isLivePollEnabled = (isset($livePollStatus) ? (string)$livePollStatus : '1') === '1';
$isLiveGiftEnabled = (isset($liveGiftStatus) ? (string)$liveGiftStatus : '1') === '1';
$isLiveQAEnabled = (isset($liveQAStatus) ? (string)$liveQAStatus : '1') === '1';
$isLiveChatEnabled = (isset($liveChatStatus) ? (string)$liveChatStatus : '1') === '1';
$hasLiveInteractions = $isLivePollEnabled || $isLiveQAEnabled;
$interactionTitleParts = [];
if ($isLivePollEnabled) {
    $interactionTitleParts[] = $LANG['live_poll_title'];
}
if ($isLiveQAEnabled) {
    $interactionTitleParts[] = $LANG['live_qa_title'];
}
$interactionsLabel = implode(' & ', $interactionTitleParts);
$pollData = $isLivePollEnabled ? $iN->iN_GetLivePollDetails($liveID, $userID) : null;
$questions = $isLiveQAEnabled ? $iN->iN_GetLiveQuestions($liveID, $isLiveCreator) : [];
?>
<div class="live_wrapper_tik" id="<?php echo iN_HelpSecure($liveID);?>">
    <div class="live_left">
        <!---->
        <div class="live_left_in_wrapper">
            <div class="live_left_in_holder">
                <!--Menu-->
                <a href="<?php echo iN_HelpSecure($base_url);?>">
                 <div class="i_left_menu_box transition">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('99'));?> <div class="m_tit"><?php echo iN_HelpSecure($LANG['home_page']);?></div>
                 </div>
                 </a> 
                 <div class="i_left_menu_box transition g_feed" data-get="friends" data-type="moreposts">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('7'));?> <div class="m_tit"><?php echo iN_HelpSecure($LANG['newsfeed']);?></div>
                 </div> 
                 <div class="i_left_menu_box transition g_feed" data-get="allPosts" data-type="moreexplore">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('8'));?> <div class="m_tit"><?php echo iN_HelpSecure($LANG['explore']);?></div>
                 </div> 
                 <div class="i_left_menu_box transition g_feed" data-get="premiums" data-type="morepremium">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('9'));?> <div class="m_tit"><?php echo iN_HelpSecure($LANG['premium']);?></div>
                 </div> 
                 <a href="<?php echo iN_HelpSecure($base_url);?>creators">
                  <div class="i_left_menu_box transition">
                     <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95'));?> <div class="m_tit"><?php echo iN_HelpSecure($LANG['our_creators']);?></div>
                  </div>
                 </a>
                 <!--/Menu-->
                 <!---->
                 <div class="live_suggested_lives_wrapper">
                     <?php include "live_list_widget.php";?>
                 </div>
                 <!---->
            </div>
        </div>
        <!---->
    </div>
    <div class="live_right">
       <div class="live_right_in_wrapper">
           <!---->
           <div class="live_right_in_left">
               <!---->
               <div class="live_video_header">
                  <div class="live_creator_avatar_live flex_ tabing"><a class="flex_ alignItem" href="<?php echo $base_url.$liveCreatorUserName;?>" target="blank_"><img src="<?php echo iN_HelpSecure($liveCreatorAvatar);?>"></a></div>
                  <div class="live_creator_live_name_live_username">
                      <div class="live_creator_live_username"><a class="flex_ alignItem exen loi" href="<?php echo $base_url.$liveCreatorUserName;?>" target="blank_"><?php echo iN_HelpSecure($liveCreatorFullname);?></a></div>
                      <div class="live_creator_live_name flex_ tabing"><?php echo $siteTitle;?>  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15'));?><span class="sumonline">0</span></div>
                  </div>
                  <div class="live_header_in_right flex_ tabing">
                      <div class="live_owner_flw_btn">
                        <?php if ($p_friend_status != 'subscriber' && $p_friend_status != 'me' && $p_friend_status != 'flwr') {?>
                            <div class="i_fw<?php echo iN_HelpSecure($liveCreator); ?> transition <?php echo iN_HelpSecure($flwrBtn); ?>" id="i_btn_like_item" data-u="<?php echo iN_HelpSecure($liveCreator); ?>">
                            <?php echo html_entity_decode($flwBtnIconText); ?>
                            </div>
                        <?php }?>
                      </div>
                      <div class="live_mics_cameras flex_ tabing">
                          <?php if($userID == $liveCreator){ ?>
                            <div class="i_header_btn_item live_header_btn_camera topPoints transition cameli">
                              <div class="i_h_in_live camera_chs">
                                 <div class="camList cam-list" id="camera-list"></div>
                                 <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('137'));?>
                              </div>
                            </div>
                            <div class="i_header_btn_item live_header_btn_mic topPoints transition cameli">
                                <div class="i_h_in_live mick_chs">
                                    <div class="micList mic-list" id="camera-list"></div>
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('152'));?>
                                </div>
                            </div>
                            <div class="i_header_btn_item live_header_btn_end topPoints transition">
                                <div class="i_h_in_live camcloseCall">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('172'));?>
                                </div>
                            </div>
                          <?php }?>
                      </div>
                  </div>
               </div>
               <!---->
               <!---->
               <div class="live__live_video_holder">
                    <div class="live_vide__holder">
                       <div class="filtvid flex_ player" id="<?php echo 'local-player';?>">
                       <?php if($userID != $liveCreator){ ?>
                       <div class="col cola" stlye="display:none;">
                            <p id="local-player-name" class="player-name nonePoint"></p>
                        </div>
                       <div class="w-100" stlye="display:none;"></div>
                        <div class="col">
                            <div id="remote-playerlist"></div>
                        </div>
                        <?php }?>
                    </div>
                       <div class="live_holder_plus_in">
                         <div class="holder_l_in flex_ tabing">
                             <div class="live_pulse">LIVE</div>
                             <!---->
                             <div class="live_like_t">
                                <div class="like_live flex_ <?php echo iN_HelpSecure($likeClass);?>" id="p_l_l_<?php echo iN_HelpSecure($liveID);?>" data-id="<?php echo iN_HelpSecure($liveID);?>">
                                    <?php echo html_entity_decode($likeIcon);?>
                                </div>
                                <div class="lp_sum_l flex_ tabing" id="lp_sum_l_<?php echo iN_HelpSecure($liveID);?>"><?php echo iN_HelpSecure($likeSum);?></div>
                             </div>
                             <!---->
                             <?php if($userID == $liveCreator){ ?>
                             <button type="button" class="live_action_menu_btn flex_ tabing" aria-expanded="false">
                                 <span class="live_action_menu_dot"></span>
                                 <span class="live_action_menu_dot"></span>
                                 <span class="live_action_menu_dot"></span>
                             </button>
                             <div class="live_creator_actions_stack">
                                 <div class="button-group">
                                    <button id="mute-audio" type="button" class="flex_ tabing"><?php echo iN_HelpSecure($LANG['mute_audio']);?></button>
                                    <button id="mute-video" type="button" class="flex_ tabing"><?php echo iN_HelpSecure($LANG['mute_video']);?></button>
                                 </div>
                                 <div class="live_pin_product_btn flex_ tabing" data-live-id="<?php echo iN_HelpSecure($liveID);?>">
                                     <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158'));?>
                                     <?php echo iN_HelpSecure($LANG['live_pin_product']);?>
                                 </div>
                                 <?php if ($hasLiveInteractions) { ?>
                                 <button type="button"
                                         class="live_interactions_toggle_btn flex_ tabing"
                                         aria-controls="liveInteractionsModules_<?php echo iN_HelpSecure($liveID); ?>"
                                         aria-expanded="false">
                                     <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?>
                                     <span><?php echo iN_HelpSecure($interactionsLabel); ?></span>
                                 </button>
                                 <?php } ?>
                             </div>
                             <?php } else { ?>
                             <?php if ($hasLiveInteractions) { ?>
                             <button type="button"
                                     class="live_interactions_toggle_btn flex_ tabing"
                                     aria-controls="liveInteractionsModules_<?php echo iN_HelpSecure($liveID); ?>"
                                     aria-expanded="false">
                                 <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?>
                                 <span><?php echo iN_HelpSecure($interactionsLabel); ?></span>
                             </button>
                             <?php } ?>
                             <?php } ?>
                             <?php if($userID != $liveCreator && $isLiveGiftEnabled){ ?>
                             <div class="live_gift_call flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('145'));?></div>
                             <?php }?>
                         </div>
                       </div>
                    </div>
                    <div class="live_pinned_product_slot" data-live-id="<?php echo iN_HelpSecure($liveID); ?>" data-pinned-id="<?php echo $pinnedProduct ? iN_HelpSecure($pinnedProduct['pr_id'] ?? '') : ''; ?>">
                        <button type="button" class="live_pinned_product_toggle" aria-expanded="false">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?>
                            <?php echo iN_HelpSecure($LANG['live_offer']); ?>
                        </button>
                        <div class="live_pinned_product_inner">
                            <?php
                            if ($pinnedProduct) {
                                $isLiveCreator = $isLiveCreator;
                                include "live_pinned_product.php";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="live_footer_holder">
                        <?php  if($p_friend_status != 'me' && $isLiveGiftEnabled){?>
                        <?php include "liveCoinList.php";?>
                        <div class="live_coin_current_balance">
                            <div class="current_balance_box flex_ tabing_non_justify"><?php echo iN_HelpSecure($LANG['wallet_balance'] ?? $LANG['point_balance']);?> <span class="crnblnc"><?php echo iN_HelpSecure(formatCurrency($userCurrentPoints, $defaultCurrency));?></span> <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40'));?><a href="<?php echo $base_url.'purchase/purchase_point';?>" target="blank_" class="transitions"><?php echo iN_HelpSecure($LANG['add_funds'] ?? $LANG["get_points"]);?></a></div>
                        </div>
                        <?php }?>
                        <div class="currentt_live_streamings_list_container tabing">
                            <?php include "sugLiveStreams.php";?>
                        </div>
                    </div>
               </div>
               <!---->

           </div>
           <!---->
           <!---->
           <div class="live_right_in_right relativePosition">
                <div class="live_right_in_right_in">
                       <?php if ($hasLiveInteractions) { ?>
                       <div class="live_interaction_modules" id="liveInteractionsModules_<?php echo iN_HelpSecure($liveID); ?>">
                           <?php if ($isLivePollEnabled) { ?>
                           <?php include "live_poll_module.php";?>
                           <?php } ?>
                           <?php if ($isLiveQAEnabled) { ?>
                           <?php include "live_qa_module.php";?>
                           <?php } ?>
                       </div>
                       <?php } ?>
                       <?php if ($isLiveChatEnabled) { ?>
                       <?php include "liveChat.php";?>
                       <?php } ?>
                </div>
                <?php if ($isLiveChatEnabled) { ?>
                <div class="live_send_message_box_wrapper">
                   <div class="nanos transition"></div>
                   <div class="tabing_non_justify flex_ optional_width">
                       <div class="message_form_items flex_ tabing">
                           <div class="message_send_text flex_ tabing">
                               <div class="message_text_textarea flex_">
                                   <textarea class="lmSize"></textarea>
                                   <!---->
                                    <div class="message_smiley getMEmojisa">
                                        <div class="message_form_smiley_plus transition">
                                            <div class="message_pls flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('25')); ?></div>
                                        </div>
                                    </div>
                                    <!---->
                               </div>
                           </div>
                           <div class="message_form_plus transition livesendmes">
                               <div class="message_pls flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?></div>
                           </div>
                       </div>
                   </div>
                </div>
                <?php } ?>
           </div>
           <!---->
       </div>
    </div>
</div>
<?php if($userID != $liveCreator){$iN->iN_InsertMyOnlineStatus($userID, $liveID);}?>
<div id="mic-list"></div>
<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
    window.siteurl = "<?php echo iN_HelpSecure($base_url); ?>";
    window.liveAppID = "<?php echo $agoraAppID; ?>";
    window.liveChannel = "<?php echo $liveChannel; ?>";
    window.liveUserID = "<?php echo $userID; ?>";
    window.liveCreator = "<?php echo $liveCreator; ?>";
    window.theLiveID = "<?php echo iN_HelpSecure($theLiveID); ?>";
    window.liveProvider = "<?php echo iN_HelpSecure($rtProvider); ?>";
    window.livekitWSUrl = "<?php echo iN_HelpSecure($livekitWSUrl); ?>";
    window.livePollEnabled = "<?php echo $isLivePollEnabled ? '1' : '0'; ?>";
    window.liveGiftEnabled = "<?php echo $isLiveGiftEnabled ? '1' : '0'; ?>";
    window.liveQaEnabled = "<?php echo $isLiveQAEnabled ? '1' : '0'; ?>";
    window.liveChatEnabled = "<?php echo $isLiveChatEnabled ? '1' : '0'; ?>";
    window.LANG_MUTE_AUDIO = "<?php echo iN_HelpSecure($LANG['mute_audio']); ?>";
    window.LANG_UNMUTE_AUDIO = "<?php echo iN_HelpSecure($LANG['unmute_audio']); ?>";
    window.LANG_MUTE_VIDEO = "<?php echo iN_HelpSecure($LANG['mute_video']); ?>";
    window.LANG_UNMUTE_VIDEO = "<?php echo iN_HelpSecure($LANG['unmute_video']); ?>";
    window.LANG_LIVE_REQUIRES_HTTPS = "<?php echo iN_HelpSecure($LANG['live_media_requires_https']); ?>";
    window.LANG_LIVE_PERMISSION_DENIED = "<?php echo iN_HelpSecure($LANG['live_media_permission_denied']); ?>";
    window.LANG_LIVE_DEVICE_NOT_FOUND = "<?php echo iN_HelpSecure($LANG['live_media_not_found']); ?>";
    window.LANG_LIVE_DEVICE_IN_USE = "<?php echo iN_HelpSecure($LANG['live_media_in_use']); ?>";
    window.LANG_LIVE_GENERIC_ERROR = "<?php echo iN_HelpSecure($LANG['live_media_generic_error']); ?>";
</script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/js/freeLiveStreaming.js?v=<?php echo iN_HelpSecure($version);?>"></script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/js/liveEngagement.js?v=<?php echo iN_HelpSecure($version);?>"></script>
