<div class="i_general_box_container generalBox extensionPost">
  <div class="btest">
    <div class="i_user_details">
      <!-- MESSAGE HEADER -->
      <div class="i_box_messages_header">
        <?php echo iN_HelpSecure($LANG['your_balance'] ?? $LANG['your_points']); ?>
      </div>
      <!-- /MESSAGE HEADER -->

      <div class="i_header_others_box">
        <div class="crnt_points">
          <?php echo iN_HelpSecure(formatCurrency($userCurrentPoints, $defaultCurrency)); ?>
        </div>
      </div>

      <div class="point_box_BG">
        <div class="pbg flex_ tabing">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
        </div>
      </div>

    </div> 
      <div class="footer_container">
        <div class="point_pr tabing flex_">
          <a class="tabing flex_ transition" href="<?php echo iN_HelpSecure($base_url); ?>purchase/purchase_point">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')) . iN_HelpSecure($LANG['add_funds'] ?? $LANG['purchase_point']); ?>
          </a>
        </div>
      </div> 
  </div>
</div>