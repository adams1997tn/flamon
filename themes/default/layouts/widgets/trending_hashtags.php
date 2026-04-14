<?php
$trendHashtagsEnabled = isset($trendHashtagsStatus) ? (string)$trendHashtagsStatus : '1';
$trendHashtagsLimit = isset($showingNumberOfTrendHashtags) ? (int)$showingNumberOfTrendHashtags : 10;
$trendHashtagsDays = isset($showingTrendPostLimitDay) ? (int)$showingTrendPostLimitDay : 1;

if ($trendHashtagsLimit < 1) { $trendHashtagsLimit = 1; }
if ($trendHashtagsLimit > 30) { $trendHashtagsLimit = 30; }
if ($trendHashtagsDays < 1) { $trendHashtagsDays = 1; }

if ($trendHashtagsEnabled === '1') {
    $trendTags = $iN->iN_GetTrendingHashtags($trendHashtagsLimit, $trendHashtagsDays);
    if (!empty($trendTags)) {
        $colorClasses = [
            'trend-hashtag--coral',
            'trend-hashtag--amber',
            'trend-hashtag--mint',
            'trend-hashtag--sky',
            'trend-hashtag--violet',
            'trend-hashtag--orange',
            'trend-hashtag--teal',
            'trend-hashtag--rose'
        ];
        $colorCount = count($colorClasses);
?>
        <div class="sp_wrp trend-hashtags-widget">
            <div class="suggested_products">
                <div class="i_right_box_header trend-hashtags-header">
                    <span class="trend-hashtags-header__title"><?php echo iN_HelpSecure($LANG['trend_hashtags_title']); ?></span>
                    <button type="button" class="trend-hashtags-header__btn trendHashtagsTopBtn">
                        <?php echo iN_HelpSecure($LANG['trend_hashtags_top100']); ?>
                    </button>
                </div>
                <div class="trend-hashtags">
                    <div class="trend-hashtags__list">
                        <?php foreach ($trendTags as $tagData) {
                            $tag = (string)($tagData['tag'] ?? '');
                            if ($tag === '') { continue; }
                            $hashValue = (int)sprintf('%u', crc32($tag));
                            $classIndex = $colorCount > 0 ? ($hashValue % $colorCount) : 0;
                            $tagClass = $colorClasses[$classIndex] ?? 'trend-hashtag--coral';
                            $tagUrl = $base_url . 'hashtag/' . urlencode($tag);
                        ?>
                            <a class="trend-hashtags__tag <?php echo iN_HelpSecure($tagClass); ?>" href="<?php echo iN_HelpSecure($tagUrl); ?>">
                                #<?php echo iN_HelpSecure($tag); ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}
?>
