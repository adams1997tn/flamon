<?php
$iN->iN_UpdateVerificationAnswerReadStatus($userID);
$existingInstagram = '';
$existingTiktok = '';
$existingFacebook = '';
$existingYoutube = '';
$existingOtherLinks = array();
$uSocialData = $iN->iN_GetUserDetails($userID);
if ($uSocialData) {
    $existingInstagram = isset($uSocialData['instagram_url']) ? (string)$uSocialData['instagram_url'] : '';
    $existingTiktok = isset($uSocialData['tiktok_url']) ? (string)$uSocialData['tiktok_url'] : '';
    $existingFacebook = isset($uSocialData['facebook_url']) ? (string)$uSocialData['facebook_url'] : '';
    $existingYoutube = isset($uSocialData['youtube_url']) ? (string)$uSocialData['youtube_url'] : '';
    if (isset($uSocialData['other_social_links']) && trim((string)$uSocialData['other_social_links']) !== '') {
        $decoded = json_decode((string)$uSocialData['other_social_links'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $lnk) {
                $lnk = trim((string)$lnk);
                if ($lnk !== '') { $existingOtherLinks[] = $lnk; }
            }
        }
    }
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
      <div class="i_set_subscription_fee_box">
         <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['facebook_page'] ?? 'Facebook Page');?></div>
         <div class="i_set_subscription_fee margin-bottom-ten">
            <div class="i_subs_currency"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1877F2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></div>
            <div class="i_payout_">
               <input type="url" class="transition" id="facebook_url" placeholder="https://facebook.com/yourpage" value="<?php echo iN_HelpSecure($existingFacebook);?>">
            </div>
         </div>
      </div>
      <div class="i_set_subscription_fee_box">
         <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['youtube_channel'] ?? 'YouTube Channel');?></div>
         <div class="i_set_subscription_fee margin-bottom-ten">
            <div class="i_subs_currency"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FF0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg></div>
            <div class="i_payout_">
               <input type="url" class="transition" id="youtube_url" placeholder="https://youtube.com/@yourchannel" value="<?php echo iN_HelpSecure($existingYoutube);?>">
            </div>
         </div>
      </div>
      <div class="i_set_subscription_fee_box">
         <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['other_social_links'] ?? 'Other Social Links');?></div>
         <div id="other_social_links_wrapper">
            <?php if (!empty($existingOtherLinks)) { foreach ($existingOtherLinks as $oLnk) { ?>
            <div class="i_set_subscription_fee margin-bottom-ten other_social_row" style="position:relative;">
               <div class="i_subs_currency"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div>
               <div class="i_payout_">
                  <input type="url" class="transition other_social_input" placeholder="https://..." value="<?php echo iN_HelpSecure($oLnk);?>">
               </div>
               <span class="remove_other_social" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;color:#d33;font-size:20px;line-height:1;padding:0 6px;" title="Remove">&times;</span>
            </div>
            <?php } } ?>
         </div>
         <div id="add_other_social_link" class="transition" style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;color:#1e88e5;font-weight:600;font-size:13px;margin-top:4px;user-select:none;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border:1px solid #1e88e5;border-radius:50%;font-size:18px;line-height:1;">+</span>
            <span><?php echo iN_HelpSecure($LANG['add_another_link'] ?? 'Add another link');?></span>
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
    // Template for an extra link row
    var otherSocialRowTpl = '<div class="i_set_subscription_fee margin-bottom-ten other_social_row" style="position:relative;">' +
        '<div class="i_subs_currency"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div>' +
        '<div class="i_payout_"><input type="url" class="transition other_social_input" placeholder="https://..." value=""></div>' +
        '<span class="remove_other_social" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;color:#d33;font-size:20px;line-height:1;padding:0 6px;" title="Remove">&times;</span>' +
        '</div>';

    $("body").on("click", "#add_other_social_link", function(){
        $("#other_social_links_wrapper").append(otherSocialRowTpl);
    });

    $("body").on("click", ".remove_other_social", function(){
        $(this).closest('.other_social_row').remove();
    });

    $("body").on("click",".c_Next", function(){
        var instagramUrl = $.trim($("#instagram_url").val());
        var tiktokUrl = $.trim($("#tiktok_url").val());
        var facebookUrl = $.trim($("#facebook_url").val());
        var youtubeUrl = $.trim($("#youtube_url").val());

        var otherLinks = [];
        $(".other_social_input").each(function(){
            var v = $.trim($(this).val());
            if (v !== '') { otherLinks.push(v); }
        });

        $("#social_warning, #social_url_warning").hide();

        var urlPattern = /^https?:\/\/.+/i;
        var hasAny = (instagramUrl !== '' || tiktokUrl !== '' || facebookUrl !== '' || youtubeUrl !== '' || otherLinks.length > 0);
        if (!hasAny) {
            $("#social_warning").show();
            return false;
        }

        var checks = [instagramUrl, tiktokUrl, facebookUrl, youtubeUrl].concat(otherLinks);
        for (var i = 0; i < checks.length; i++) {
            if (checks[i] !== '' && !urlPattern.test(checks[i])) {
                $("#social_url_warning").show();
                return false;
            }
        }

        var type = 'acceptConditions';
        var data = {
            f: type,
            instagram_url: instagramUrl,
            tiktok_url: tiktokUrl,
            facebook_url: facebookUrl,
            youtube_url: youtubeUrl,
            other_social_links: otherLinks
        };
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
