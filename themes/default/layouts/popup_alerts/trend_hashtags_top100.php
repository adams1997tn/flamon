<div class="i_modal_bg_in trend-top100-modal" role="dialog" aria-modal="true" aria-labelledby="trendTop100Title">
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="trend-top100-header">
                <div class="trend-top100-title" id="trendTop100Title">
                    <?php echo iN_HelpSecure($LANG['trend_hashtags_top100_title']); ?>
                </div>
                <div class="popClose transition" role="button" aria-label="Close">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5')); ?>
                </div>
            </div>
            <div class="trend-top100-body">
                <?php if (!empty($trendTags)) { ?>
                    <div class="trend-top100-list">
                        <?php
                        $rank = 1;
                        foreach ($trendTags as $tagData) {
                            $tag = (string)($tagData['tag'] ?? '');
                            if ($tag === '') { continue; }
                            $count = (int)($tagData['count'] ?? 0);
                            $tagUrl = $base_url . 'hashtag/' . urlencode($tag);
                        ?>
                            <div class="trend-top100-item">
                                <span class="trend-top100-rank"><?php echo iN_HelpSecure($rank); ?></span>
                                <a class="trend-top100-tag" href="<?php echo iN_HelpSecure($tagUrl); ?>">#<?php echo iN_HelpSecure($tag); ?></a>
                                <span class="trend-top100-count"><?php echo iN_HelpSecure(number_format($count)); ?> <?php echo iN_HelpSecure($LANG['posts']); ?></span>
                            </div>
                        <?php
                            $rank++;
                        } ?>
                    </div>
                <?php } else { ?>
                    <div class="trend-top100-empty"><?php echo iN_HelpSecure($LANG['trend_hashtags_top100_empty']); ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
