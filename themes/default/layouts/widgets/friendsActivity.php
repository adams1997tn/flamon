<?php
if($logedIn == '1'){
$activityData = $iN->iN_FriendsActivity($userID, $showingActivityLimit, $showingTimeActivityLimit);
if ($activityData) {
    $activityActorIDs = [];
    $activityPostIDs = [];
    $activityRelatedUserIDs = [];
    foreach ($activityData as $activityRow) {
        $actorID = isset($activityRow['iuid']) ? (int)$activityRow['iuid'] : 0;
        if ($actorID > 0) {
            $activityActorIDs[$actorID] = $actorID;
        }
        $activityType = isset($activityRow['activity_type']) ? (string)$activityRow['activity_type'] : '';
        if ($activityType === 'postLike') {
            $postID = isset($activityRow['post_id']) ? (int)$activityRow['post_id'] : 0;
            if ($postID > 0) {
                $activityPostIDs[$postID] = $postID;
            }
        } elseif ($activityType === 'userFollow') {
            $relatedUID = isset($activityRow['fr_id']) ? (int)$activityRow['fr_id'] : 0;
            if ($relatedUID > 0) {
                $activityRelatedUserIDs[$relatedUID] = $relatedUID;
            }
        }
    }
    $activityPostOwnerMap = !empty($activityPostIDs) ? $iN->iN_GetPostOwnerIDMapForPostIDs(array_values($activityPostIDs)) : [];
    if (!empty($activityPostOwnerMap)) {
        foreach ($activityPostOwnerMap as $ownerUID) {
            $ownerUID = (int)$ownerUID;
            if ($ownerUID > 0) {
                $activityRelatedUserIDs[$ownerUID] = $ownerUID;
            }
        }
    }
    $activityAllAvatarIDs = array_values(array_unique(array_merge(array_values($activityActorIDs), array_values($activityRelatedUserIDs))));
    if (!empty($activityAllAvatarIDs)) {
        $iN->iN_PreloadUserMediaPathMaps($activityAllAvatarIDs);
    }
    $activityIdentityMap = !empty($activityRelatedUserIDs) ? $iN->iN_GetUsersIdentityMap(array_values($activityRelatedUserIDs)) : [];

    echo '
    <div class="sp_wrp">
    <div class="suggested_products">
    <div class="i_right_box_header">
     ' . iN_HelpSecure($LANG['friends_activity']) . '
    </div>
    <div class="i_topinoras_wrapper activityWrapper">
    ';
    foreach ($activityData as $activity) {
        $activityID = isset($activity['activity_id']) ? $activity['activity_id'] : NULL;
        $activityUserID = isset($activity['iuid']) ? $activity['iuid'] : NULL;
        $activityType = isset($activity['activity_type']) ? $activity['activity_type'] : NULL;
        $activityPostID = isset($activity['post_id']) ? $activity['post_id'] : NULL;
        $activityFollowUserID = isset($activity['fr_id']) ? $activity['fr_id']: NULL; 
        $popularUserName = isset($activity['i_username']) ? $activity['i_username'] : NULL;
        $popularUserFullName = isset($activity['i_user_fullname']) ? $activity['i_user_fullname'] : NULL;

        $popularUserAvatar = $iN->iN_UserAvatar($activityUserID, $base_url);
        $activityTypeText = 'This activity is no longer available';
        if($fullnameorusername == 'no'){
            $popularUserFullName = $popularUserName;
        }
        if($activityType == 'newPost'){
            if($popularUserName && $popularUserFullName){
                $activityTypeText = html_entity_decode($iN->iN_TextReaplacement($LANG['new_post_activity'],[$popularUserName,$popularUserFullName,$activityPostID]));
            }
        }else if($activityType == 'postLike'){
            $activityPostIntID = (int)$activityPostID;
            $likedPostOwnerID = isset($activityPostOwnerMap[$activityPostIntID]) ? (int)$activityPostOwnerMap[$activityPostIntID] : (int)$iN->iN_GetPostOwnerIDFromPostID($activityPostID);
            if($likedPostOwnerID){
                $uData = isset($activityIdentityMap[(int)$likedPostOwnerID]) ? $activityIdentityMap[(int)$likedPostOwnerID] : null;
                if(!empty($uData)){
                    $lpopularUserName = isset($uData['i_username']) ? $uData['i_username'] : NULL;
                    $lpopularUserFullName = isset($uData['i_user_fullname']) ? $uData['i_user_fullname'] : NULL;
                    if($fullnameorusername == 'no'){
                        $lpopularUserFullName = $lpopularUserName;
                    }
                    if($popularUserName && $popularUserFullName && $lpopularUserName && $lpopularUserFullName){
                        $activityTypeText = html_entity_decode($iN->iN_TextReaplacement($LANG['new_post_like_activity'],[$popularUserName,$popularUserFullName,$lpopularUserName,$lpopularUserFullName,$activityPostID]));
                    }
                }
            }else{
                $activityTypeText = 'This activity is no longer available';
            }
        }else if($activityType == 'userFollow'){
            $uData = isset($activityIdentityMap[(int)$activityFollowUserID]) ? $activityIdentityMap[(int)$activityFollowUserID] : null;
            if(!empty($uData)){
                $lpopularUserName = isset($uData['i_username']) ? $uData['i_username'] : NULL;
                $lpopularUserFullName = isset($uData['i_user_fullname']) ? $uData['i_user_fullname'] : NULL;
                if($fullnameorusername == 'no'){
                    $lpopularUserFullName = $lpopularUserName;
                }
                if($popularUserName && $popularUserFullName && $lpopularUserName && $lpopularUserFullName){
                    $activityTypeText = html_entity_decode($iN->iN_TextReaplacement($LANG['new_follow_activity'],[$popularUserName,$popularUserFullName,$lpopularUserName,$lpopularUserFullName,$activityPostID]));
                }
            }
        }
        $activityTypeLabelMap = array(
            'newPost' => isset($LANG['activity_label_new_post']) ? $LANG['activity_label_new_post'] : 'New Post',
            'postLike' => isset($LANG['activity_label_post_like']) ? $LANG['activity_label_post_like'] : 'Post Like',
            'userFollow' => isset($LANG['activity_label_user_follow']) ? $LANG['activity_label_user_follow'] : 'New Follow'
        );
        $activityTypeLabel = isset($activityTypeLabelMap[$activityType]) ? $activityTypeLabelMap[$activityType] : iN_HelpSecure($activityType);

?>
<div class="i_message_wrpper">
    <a class="activity_card_link transition" href="<?php echo iN_HelpSecure($base_url . $popularUserName); ?>" target="_blank" rel="noopener" title="<?php echo iN_HelpSecure($popularUserFullName); ?>">
        <div class="i_message_wrapper activity_card" data-activity="<?php echo iN_HelpSecure($activityType); ?>">
            <div class="activity_card_body">
                <div class="i_message_owner_avatar activity_avatar_ring">
                    <div class="i_message_avatar">
                        <img src="<?php echo iN_HelpSecure($popularUserAvatar); ?>" alt="<?php echo iN_HelpSecure($popularUserFullName); ?>">
                    </div>
                </div>
                <div class="activity_text_block">
                    <div class="activity_type_chip"><?php echo iN_HelpSecure($activityTypeLabel); ?></div>
                    <div class="i_activity_info_container tabing_non_justify activity_text">
                        <?php echo $activityTypeText;?>
                    </div>
                </div>
            </div>
            <div class="activity_meta_chevron" aria-hidden="true">&#8250;</div>
        </div>
    </a>
</div>
<?php
    }
    echo '
    </div>
    </div>
    </div>';
}
}
?>
