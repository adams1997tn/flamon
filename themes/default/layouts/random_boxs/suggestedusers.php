<?php
if ($logedIn != 0) {
    $suggestedCreators = $iN->iN_SuggestionCreatorsList($userID, $showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
} else {
    $suggestedCreators = $iN->iN_SuggestionCreatorsListOut($showingNumberOfSuggestedUser, $userID ?? null, $viewerCountryCode ?? null);
}
if ($suggestedCreators) {
    $suggestedCreatorIds = [];
    foreach ($suggestedCreators as $creatorRow) {
        $creatorUID = isset($creatorRow['iuid']) ? (int)$creatorRow['iuid'] : 0;
        if ($creatorUID > 0) {
            $suggestedCreatorIds[$creatorUID] = $creatorUID;
        }
    }
    $suggestedCreatorIds = array_values($suggestedCreatorIds);
    if (!empty($suggestedCreatorIds)) {
        $iN->iN_PreloadUserMediaPathMaps($suggestedCreatorIds);
    }
    $suggestedTotalPostsMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalPostsMap($suggestedCreatorIds) : [];
    $suggestedTotalImageMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalImagePostsMap($suggestedCreatorIds) : [];
    $suggestedTotalVideoMap = !empty($suggestedCreatorIds) ? $iN->iN_TotalVideoPostsMap($suggestedCreatorIds) : [];
    $scSwiperId = 'scSwiper_' . bin2hex(random_bytes(4));
?>
<style>
/* ============== Suggested Creators – Horizontal Swiper ============== */
.sc_modern_wrap { --sc-accent:#ff2e88; --sc-accent-2:#ff7ab0; --sc-card:#1b1b22; --sc-card-2:#22222b; --sc-text:#f4f4f6; --sc-muted:#9aa0a6; --sc-border:rgba(255,255,255,.06); position:relative; margin:18px 0; }
.sc_modern_header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 4px 14px; }
.sc_modern_header h3 { margin:0; font-size:18px; font-weight:700; color:var(--sc-text); letter-spacing:.2px; display:flex; align-items:center; gap:8px; }
.sc_modern_header h3::before { content:""; width:4px; height:18px; border-radius:3px; background:linear-gradient(180deg,var(--sc-accent),var(--sc-accent-2)); }
.sc_nav_group { display:flex; gap:8px; }
.sc_nav_btn { width:36px; height:36px; border-radius:50%; border:1px solid var(--sc-border); background:var(--sc-card); color:var(--sc-text); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:background .2s ease, transform .2s ease, color .2s ease, border-color .2s ease; }
.sc_nav_btn:hover:not(.swiper-button-disabled) { background:linear-gradient(135deg,var(--sc-accent),var(--sc-accent-2)); color:#fff; border-color:transparent; transform:translateY(-1px); box-shadow:0 6px 14px rgba(255,46,136,.35); }
.sc_nav_btn.swiper-button-disabled { opacity:.35; cursor:default; }
.sc_nav_btn svg { width:16px; height:16px; }

.sc_swiper_host { position:relative; padding:6px 2px 8px; }
.sc_swiper_host .swiper-slide { height:auto; width:240px; }
@media (max-width: 640px) { .sc_swiper_host .swiper-slide { width:180px; } }

.sc_card { position:relative; background:var(--sc-card); border:1px solid var(--sc-border); border-radius:18px; overflow:hidden; transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease; box-shadow:0 4px 14px rgba(0,0,0,.25); height:100%; display:flex; flex-direction:column; }
.sc_card:hover { transform:translateY(-4px); border-color:rgba(255,46,136,.35); box-shadow:0 12px 30px rgba(255,46,136,.15), 0 6px 18px rgba(0,0,0,.35); }
.sc_cover { position:relative; height:92px; background-size:cover; background-position:center; background-color:var(--sc-card-2); }
.sc_cover::after { content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,0) 30%, rgba(0,0,0,.55) 100%); }
.sc_avatar_wrap { position:absolute; left:50%; top:56px; transform:translateX(-50%); width:70px; height:70px; border-radius:50%; padding:3px; background:linear-gradient(135deg,var(--sc-accent),var(--sc-accent-2)); z-index:2; }
.sc_avatar_wrap img { width:100%; height:100%; border-radius:50%; object-fit:cover; display:block; background:#000; border:2px solid var(--sc-card); }
.sc_body { padding:40px 14px 14px; text-align:center; flex:1; display:flex; flex-direction:column; }
.sc_name { font-size:14px; font-weight:700; color:var(--sc-text); margin:0; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sc_name a { color:inherit; text-decoration:none; }
.sc_name a:hover { color:var(--sc-accent); }
.sc_handle { font-size:12px; color:var(--sc-muted); margin:4px 0 8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sc_handle a { color:inherit; text-decoration:none; }
.sc_bio { font-size:12px; color:var(--sc-muted); line-height:1.4; margin:0 0 10px; display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:32px; }
.sc_stats { display:flex; justify-content:center; gap:6px; margin:auto 0 12px; flex-wrap:wrap; }
.sc_stat { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:var(--sc-muted); background:var(--sc-card-2); padding:4px 8px; border-radius:999px; border:1px solid var(--sc-border); }
.sc_stat svg, .sc_stat img { width:12px; height:12px; }
.sc_follow_btn { display:block; width:100%; text-align:center; padding:10px 14px; border-radius:999px; font-weight:700; font-size:13px; letter-spacing:.3px; color:#fff !important; text-decoration:none !important; border:0; cursor:pointer; background:linear-gradient(135deg,var(--sc-accent) 0%, var(--sc-accent-2) 100%); box-shadow:0 6px 16px rgba(255,46,136,.35); transition:transform .2s ease, box-shadow .2s ease, filter .2s ease; }
.sc_follow_btn:hover { transform:translateY(-1px); filter:brightness(1.06); box-shadow:0 10px 22px rgba(255,46,136,.45); }

/* Light-theme overrides */
body:not(.night):not(.body_dark):not(.night-mode) .sc_modern_wrap { --sc-card:#fff; --sc-card-2:#f5f5f8; --sc-text:#1a1a1f; --sc-muted:#666; --sc-border:rgba(0,0,0,.08); }
body:not(.night):not(.body_dark):not(.night-mode) .sc_card { box-shadow:0 2px 10px rgba(0,0,0,.06); }
body:not(.night):not(.body_dark):not(.night-mode) .sc_avatar_wrap img { border-color:#fff; }
</style>

<div class="sc_modern_wrap">
    <div class="sc_modern_header">
        <h3><?php echo iN_HelpSecure($LANG['suggested_creators']); ?></h3>
        <div class="sc_nav_group">
            <button type="button" class="sc_nav_btn" data-scnav="prev" data-target="<?php echo iN_HelpSecure($scSwiperId); ?>" aria-label="Previous">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button type="button" class="sc_nav_btn" data-scnav="next" data-target="<?php echo iN_HelpSecure($scSwiperId); ?>" aria-label="Next">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </div>

    <div class="swiper sc_swiper_host" id="<?php echo iN_HelpSecure($scSwiperId); ?>">
        <div class="swiper-wrapper">
            <?php
            foreach ($suggestedCreators as $sgCreatorData) {
                $sgcreatorUserName     = $sgCreatorData['i_username'] ?? '';
                $sgCreatorUserfullName = $sgCreatorData['i_user_fullname'] ?? $sgcreatorUserName;
                $sgcreatorUserID       = $sgCreatorData['iuid'] ?? 0;
                $sgCreatorUserAvatar   = $iN->iN_UserAvatar($sgcreatorUserID, $base_url);
                $sgCreatorUserCover    = $iN->iN_UserCover($sgcreatorUserID, $base_url);
                $sgCreatorBio          = isset($sgCreatorData['u_bio']) ? trim(strip_tags((string)$sgCreatorData['u_bio'])) : '';
                if ($sgCreatorBio !== '' && function_exists('mb_strlen')) {
                    if (mb_strlen($sgCreatorBio) > 80) {
                        $sgCreatorBio = mb_substr($sgCreatorBio, 0, 80) . '…';
                    }
                }
                $sgCreatorIntID     = (int)$sgcreatorUserID;
                $sgtotalPost        = isset($suggestedTotalPostsMap[$sgCreatorIntID]) ? (int)$suggestedTotalPostsMap[$sgCreatorIntID] : (int)$iN->iN_TotalPosts($sgcreatorUserID);
                $sgtotalImagePost   = isset($suggestedTotalImageMap[$sgCreatorIntID]) ? (int)$suggestedTotalImageMap[$sgCreatorIntID] : (int)$iN->iN_TotalImagePosts($sgcreatorUserID);
                $sgtotalVideoPosts  = isset($suggestedTotalVideoMap[$sgCreatorIntID]) ? (int)$suggestedTotalVideoMap[$sgCreatorIntID] : (int)$iN->iN_TotalVideoPosts($sgcreatorUserID);
                $sgProfileUrl       = $base_url . $sgcreatorUserName;
                $sgFollowLabel      = $LANG['subscribe'] ?? ($LANG['follow'] ?? 'Subscribe');
            ?>
            <div class="swiper-slide">
                <div class="sc_card">
                    <div class="sc_cover" style="background-image:url('<?php echo iN_HelpSecure($sgCreatorUserCover); ?>');"></div>
                    <div class="sc_avatar_wrap">
                        <a href="<?php echo iN_HelpSecure($sgProfileUrl); ?>" target="_blank" title="<?php echo iN_HelpSecure($sgCreatorUserfullName); ?>">
                            <img src="<?php echo iN_HelpSecure($sgCreatorUserAvatar); ?>" alt="<?php echo iN_HelpSecure($sgCreatorUserfullName); ?>">
                        </a>
                    </div>
                    <div class="sc_body">
                        <div class="sc_name"><a href="<?php echo iN_HelpSecure($sgProfileUrl); ?>" target="_blank"><?php echo iN_HelpSecure($sgCreatorUserfullName); ?></a></div>
                        <div class="sc_handle"><a href="<?php echo iN_HelpSecure($sgProfileUrl); ?>" target="_blank">@<?php echo iN_HelpSecure($sgcreatorUserName); ?></a></div>
                        <?php if ($sgCreatorBio !== '') { ?>
                            <p class="sc_bio"><?php echo iN_HelpSecure($sgCreatorBio); ?></p>
                        <?php } ?>
                        <div class="sc_stats">
                            <span class="sc_stat"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('67')); ?><?php echo iN_HelpSecure($sgtotalPost); ?></span>
                            <span class="sc_stat"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')); ?><?php echo iN_HelpSecure($sgtotalImagePost); ?></span>
                            <span class="sc_stat"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('52')); ?><?php echo iN_HelpSecure($sgtotalVideoPosts); ?></span>
                        </div>
                        <a href="<?php echo iN_HelpSecure($sgProfileUrl); ?>" class="sc_follow_btn" target="_blank"><?php echo iN_HelpSecure($sgFollowLabel); ?></a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
(function(){
    function initSc(){
        if (typeof Swiper === 'undefined') { return setTimeout(initSc, 120); }
        var el = document.getElementById('<?php echo $scSwiperId; ?>');
        if (!el || el.__scInited) return;
        el.__scInited = true;
        var prevBtn = document.querySelector('.sc_nav_btn[data-scnav="prev"][data-target="<?php echo $scSwiperId; ?>"]');
        var nextBtn = document.querySelector('.sc_nav_btn[data-scnav="next"][data-target="<?php echo $scSwiperId; ?>"]');
        var sw = new Swiper(el, {
            slidesPerView: 'auto',
            spaceBetween: 14,
            grabCursor: true,
            freeMode: { enabled: true, momentum: true, momentumRatio: 0.6 },
            mousewheel: { forceToAxis: true, releaseOnEdges: true },
            keyboard: { enabled: true, onlyInViewport: true },
            navigation: prevBtn && nextBtn ? { prevEl: prevBtn, nextEl: nextBtn } : false,
            breakpoints: {
                0:   { spaceBetween: 10 },
                640: { spaceBetween: 14 },
                1024:{ spaceBetween: 16 }
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSc);
    } else {
        initSc();
    }
})();
</script>
<?php } ?>
