<?php
?>
<?php
$canModerateMemberControls = !empty($canModerateMemberControls);
$communityIDValue = isset($communityID) ? (int)$communityID : 0;
$memberIDValue = isset($memberID) ? (int)$memberID : 0;
$memberCardTag = $canModerateMemberControls ? 'button' : 'div';
$memberCardAttrs = $canModerateMemberControls
    ? 'type="button" class="community_member_card community_moderation_member_card" data-community="' . $communityIDValue . '" data-user="' . $memberIDValue . '"'
    : 'class="community_member_card"';
?>
<<?php echo $memberCardTag; ?> <?php echo $memberCardAttrs; ?>>
    <div class="community_member_avatar">
        <img src="<?php echo iN_HelpSecure($memberAvatarUrl); ?>" alt="<?php echo iN_HelpSecure($memberName); ?>">
    </div>
    <div class="community_member_name"><?php echo iN_HelpSecure($memberName); ?></div>
</<?php echo $memberCardTag; ?>>
