<?php
$inviteImagePath = rtrim($base_url, '/') . '/img/user_invite_image.png';
?>
<div class="sp_wrp_plus">
    <div class="suggested_products_plus inviteemail">
        <div class="invite_card">
            <div class="invite_visual">
                <img src="<?php echo iN_HelpSecure($inviteImagePath); ?>" alt="<?php echo iN_HelpSecure($LANG['invite_your_friends_title']); ?>">
            </div>
            <div class="invite_title_text center"><?php echo iN_HelpSecure($LANG['invite_your_friends_title']); ?></div>
            <div class="invite_sub_text center"><?php echo iN_HelpSecure($LANG['send_invite_not']); ?></div>
            <div class="i_e_warnings">
                <div class="already_in_use"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('60')); ?><?php echo iN_HelpSecure($LANG['this_email_already_in_use']); ?></div>
            </div>
            <div class="invite_card_form flex_ tabing_non_justify">
                <div class="invite_input_wrap">
                    <input type="email" name="i-email" id="inv_email" class="inviteemail_input" placeholder="<?php echo iN_HelpSecure($LANG['email']); ?>"/>
                </div>
                <div class="send_invitation_btn transition inv_btn">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?><div class="pbtn"><?php echo iN_HelpSecure($LANG['send_invite']); ?></div>
                </div>
            </div>
            <div class="invite_meta_chip center">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?>
                <span><?php echo $iN->getTotalCurrentOnlineUsers(); ?></span>
                <?php echo iN_HelpSecure($LANG['online_users']); ?>
            </div>
        </div>
    </div>
</div>
