<?php
// "See more posts" CTA for the Global Discovery Feed.
// Renders only when:
//  - user is logged in
//  - the current feed is the home/moreposts feed
//  - the admin has enabled discovery_feed_status
//
// While rendered, infinite scroll is disabled and the user fetches the
// next page on demand via a click.

if (!isset($iN) || !isset($page)) { return; }
if (!in_array($page, ['moreposts', 'friends'], true)) { return; }
if (!isset($logedIn) || (string)$logedIn !== '1') { return; }

$discoveryFeedActive = (string)$iN->iN_GetSetting('discovery_feed_status', '0') === '1';
if (!$discoveryFeedActive) { return; }

$seeMoreLabel = $LANG['discovery_see_more_label'] ?? 'See more posts';
$seeMoreLoading = $LANG['discovery_see_more_loading'] ?? 'Loading more...';
$seeMoreEnd = $LANG['discovery_see_more_end'] ?? "You're all caught up";
?>
<style>
.discover-seemore-wrap{display:flex;justify-content:center;margin:18px 0 28px;padding:0 12px;}
.discover-seemore-btn{position:relative;display:inline-flex;align-items:center;gap:10px;padding:12px 26px;font-size:15px;font-weight:600;line-height:1;color:#fff;background:linear-gradient(120deg,#f65169 0%,#b06ab3 50%,#4568dc 100%);background-size:200% 200%;border:none;border-radius:999px;cursor:pointer;letter-spacing:.3px;box-shadow:0 6px 18px rgba(86,86,200,.18);transition:transform .15s ease,box-shadow .2s ease,opacity .2s ease;animation:discoverGradient 6s ease infinite;}
.discover-seemore-btn:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(86,86,200,.28);}
.discover-seemore-btn:active{transform:translateY(0);}
.discover-seemore-btn[disabled]{cursor:default;opacity:.85;}
.discover-seemore-btn .dsm-arrow{display:inline-block;transition:transform .25s ease;}
.discover-seemore-btn:hover .dsm-arrow{transform:translateY(2px);}
.discover-seemore-btn .dsm-spinner{width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:dsmSpin .7s linear infinite;display:none;}
.discover-seemore-btn.is-loading .dsm-spinner{display:inline-block;}
.discover-seemore-btn.is-loading .dsm-arrow{display:none;}
.discover-seemore-end{display:none;text-align:center;font-size:13px;color:#999;padding:14px 0 6px;letter-spacing:.2px;}
.discover-seemore-end.is-visible{display:block;}
@keyframes discoverGradient{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
@keyframes dsmSpin{to{transform:rotate(360deg);}}
@media (prefers-reduced-motion: reduce){.discover-seemore-btn{animation:none;}.discover-seemore-btn .dsm-spinner{animation:none;}}
</style>
<div class="discover-seemore-wrap" id="discoverSeeMoreWrap">
    <button type="button" class="discover-seemore-btn" id="discoverSeeMoreBtn">
        <span class="dsm-label"><?php echo iN_HelpSecure($seeMoreLabel); ?></span>
        <span class="dsm-arrow" aria-hidden="true">&darr;</span>
        <span class="dsm-spinner" aria-hidden="true"></span>
    </button>
</div>
<div class="discover-seemore-end" id="discoverSeeMoreEnd"><?php echo iN_HelpSecure($seeMoreEnd); ?></div>
<script>
(function(){
    // Disable the global infinite-scroll loader; the See-more button drives pagination.
    window.iN_DisableInfiniteScroll = true;

    var $ = window.jQuery;
    if (!$) { return; }

    var requestUrl = (typeof siteurl !== 'undefined' && siteurl) ? (siteurl + 'requests/request.php') : '<?php echo iN_HelpSecure($base_url); ?>requests/request.php';
    var loadingLabel = <?php echo json_encode($seeMoreLoading); ?>;
    var defaultLabel = <?php echo json_encode($seeMoreLabel); ?>;

    $(document).on('click', '#discoverSeeMoreBtn', function(){
        var $btn = $(this);
        if ($btn.prop('disabled')) { return; }

        var $moreType = $('#moreType');
        if (!$moreType.length) { return; }

        var moreType = $moreType.attr('data-type') || 'moreposts';
        var moreCat  = $moreType.attr('data-po') || '';
        var moreFilter = $moreType.attr('data-pf') || '';
        var lastID = $moreType.children('.i_post_body').last().attr('data-last') || '';

        var data = 'f=' + encodeURIComponent(moreType) + '&last=' + encodeURIComponent(lastID) + '&p=';
        if (moreCat)    { data += '&pcat=' + encodeURIComponent(moreCat); }
        if (moreFilter) { data += '&pf=' + encodeURIComponent(moreFilter); }

        $btn.addClass('is-loading').prop('disabled', true)
            .find('.dsm-label').text(loadingLabel);

        $.ajax({
            type: 'POST',
            url: requestUrl,
            data: data,
            cache: false,
            success: function(response){
                $btn.removeClass('is-loading').prop('disabled', false)
                    .find('.dsm-label').text(defaultLabel);

                if (!response || !$.trim(response)) {
                    finish();
                    return;
                }

                var $new = $(response);
                var hasPosts = $new.filter('.i_post_body').length > 0 || $new.find('.i_post_body').length > 0;
                var hasNomore = $new.filter('.nomore').length > 0 || $new.find('.nomore').length > 0;

                if (hasPosts) {
                    $moreType.append($new);
                    if (typeof reInitLightGallery === 'function')      { reInitLightGallery($new); }
                    if (typeof reInitPostPlugins === 'function')        { reInitPostPlugins($new); }
                    if (typeof initImageBackgrounds === 'function')     { initImageBackgrounds('.i_post_image_swip_wrapper', $new); }
                    if (typeof initSuggestedCreatorsSwiper === 'function'){ initSuggestedCreatorsSwiper($new); }
                    if (typeof initImageSuggestedBackgrounds === 'function'){ initImageSuggestedBackgrounds(); }
                }

                if (hasNomore || !hasPosts) {
                    finish();
                }
            },
            error: function(){
                $btn.removeClass('is-loading').prop('disabled', false)
                    .find('.dsm-label').text(defaultLabel);
            }
        });

        function finish(){
            $('#discoverSeeMoreWrap').hide();
            $('#discoverSeeMoreEnd').addClass('is-visible');
        }
    });
})();
</script>
