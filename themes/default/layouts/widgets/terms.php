<?php
$iN->iN_UpdateVerificationAnswerReadStatus($userID);
$existingInstagram = '';
$existingTiktok = '';
$uSocialData = $iN->iN_GetUserDetails($userID);
if ($uSocialData) {
    $existingInstagram = isset($uSocialData['instagram_url']) ? (string)$uSocialData['instagram_url'] : '';
    $existingTiktok = isset($uSocialData['tiktok_url']) ? (string)$uSocialData['tiktok_url'] : '';
}
?>
<div class="i_become_creator_terms_box">
<div class="certification_form_container">
   <div class="certification_form_title"><?php echo iN_HelpSecure($LANG['conditions']);?></div>
   <div class="certification_form_not"><?php echo iN_HelpSecure($LANG['readed_conditions']);?></div>
   <div class="certification_form_wrapper">
      <div class="condition_documentation"><?php echo iN_HelpSecure($creatorConditions['conditions_document']);?></div>
   </div>
</div>

<div class="certification_form_container">
   <div class="certification_form_title"><?php echo iN_HelpSecure($LANG['social_media_account'] ?? 'Social Media Account');?></div>
   <div class="certification_form_not"><?php echo iN_HelpSecure($LANG['social_media_account_desc'] ?? 'Provide at least one social account for verification');?></div>
   <div class="i_subscription_form_container">
      <div class="i_set_subscription_fee_box">
         <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['instagram_profile'] ?? 'Instagram Profile');?></div>
         <div class="i_set_subscription_fee margin-bottom-ten">
            <div class="i_subs_currency"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E1306C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></div>
            <div class="i_payout_">
               <input type="url" class="transition" id="instagram_url" placeholder="https://instagram.com/yourname" value="<?php echo iN_HelpSecure($existingInstagram);?>">
            </div>
         </div>
      </div>
      <div class="i_set_subscription_fee_box">
         <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['tiktok_profile'] ?? 'TikTok Profile');?></div>
         <div class="i_set_subscription_fee margin-bottom-ten">
            <div class="i_subs_currency"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg></div>
            <div class="i_payout_">
               <input type="url" class="transition" id="tiktok_url" placeholder="https://tiktok.com/@yourname" value="<?php echo iN_HelpSecure($existingTiktok);?>">
            </div>
         </div>
      </div>
      <div class="i_t_warning" id="social_warning"><?php echo iN_HelpSecure($LANG['social_media_required'] ?? 'Please provide at least one social media account.');?></div>
      <div class="i_t_warning" id="social_url_warning"><?php echo iN_HelpSecure($LANG['social_media_invalid_url'] ?? 'Please enter a valid URL (must start with https://).');?></div>
   </div>
</div>

</div>
<div class="i_become_creator_box_footer">
   <div class="i_nex_btn c_Next transition"><?php echo iN_HelpSecure($LANG['next']);?></div>
</div>
<script type="text/javascript">
$(document).ready(function(){
    $("body").on("click",".c_Next", function(){
        var instagramUrl = $.trim($("#instagram_url").val());
        var tiktokUrl = $.trim($("#tiktok_url").val());

        $("#social_warning, #social_url_warning").hide();

        if (instagramUrl === '' && tiktokUrl === '') {
            $("#social_warning").show();
            return false;
        }

        var urlPattern = /^https?:\/\/.+/i;
        if (instagramUrl !== '' && !urlPattern.test(instagramUrl)) {
            $("#social_url_warning").show();
            return false;
        }
        if (tiktokUrl !== '' && !urlPattern.test(tiktokUrl)) {
            $("#social_url_warning").show();
            return false;
        }

        var type = 'acceptConditions';
        var data = 'f=' + type + '&instagram_url=' + encodeURIComponent(instagramUrl) + '&tiktok_url=' + encodeURIComponent(tiktokUrl);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events","none");
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == 'social_required') {
                    $("#social_warning").show();
                    $(".i_nex_btn").css("pointer-events","auto");
                } else if (response == 'invalid_url') {
                    $("#social_url_warning").show();
                    $(".i_nex_btn").css("pointer-events","auto");
                } else {
                    $(".i_nex_btn").css("pointer-events","auto");
                }
            }
        });
    });
});
</script>
