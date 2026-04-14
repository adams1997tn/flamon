<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include("header/meta.php");
        include("header/css.php");
        include("header/javascripts.php");
    ?>
</head>
<body>
<?php if ($logedIn == 0) { include('login_form.php'); } ?>
<?php include("header/header.php"); ?>
<?php
$viewerID = ($logedIn == 1) ? (int)$userID : 0;
$limit = isset($showingNumberOfPost) ? (int)$showingNumberOfPost : 12;
$subscriptionTypeValue = (string)$subscriptionType;
$communities = $iN->iN_GetCommunityDirectory($viewerID, $limit, 0);
$communityCategories = $iN->iN_GetCommunityCategories(true);
$featuredTrending = null;
$featuredNew = null;
$featuredPopular = null;
if (!empty($communities)) {
    $featuredNew = $communities[0] ?? null;
    $sortedByMembers = $communities;
    usort($sortedByMembers, function ($a, $b) {
        return (int)($b['member_count'] ?? 0) <=> (int)($a['member_count'] ?? 0);
    });
    $featuredTrending = $sortedByMembers[0] ?? null;
    $featuredPopular = $sortedByMembers[1] ?? ($sortedByMembers[0] ?? null);
    if ($featuredNew && $featuredTrending && (int)$featuredNew['id'] === (int)$featuredTrending['id']) {
        $featuredNew = $communities[1] ?? $featuredNew;
    }
    if ($featuredPopular && $featuredTrending && (int)$featuredPopular['id'] === (int)$featuredTrending['id']) {
        $featuredPopular = $sortedByMembers[2] ?? $featuredPopular;
    }
    if ($featuredPopular && $featuredNew && (int)$featuredPopular['id'] === (int)$featuredNew['id']) {
        $featuredPopular = $sortedByMembers[2] ?? $featuredPopular;
    }
}
$ctaClass = $logedIn == 1 ? 'communityCreateModal' : 'loginForm';
?>
<div class="wrapper communities_wrapper">
    <input type="hidden" class="community_csrf_token" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">
    <div class="communities_hero">
        <div class="communities_hero_content">
            <div class="communities_hero_text">
                <div class="communities_hero_kicker">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?>
                    <span><?php echo iN_HelpSecure($LANG['community_directory_title']); ?></span>
                </div>
                <h1 class="communities_hero_title"><?php echo iN_HelpSecure($LANG['community_directory_title']); ?></h1>
                <p class="communities_hero_subtitle"><?php echo iN_HelpSecure($LANG['community_directory_note']); ?></p>
            </div>
            <div class="communities_hero_actions">
                <button type="button" class="i_nex_btn <?php echo iN_HelpSecure($ctaClass); ?> transition">
                    <?php echo iN_HelpSecure($LANG['create_community']); ?>
                </button>
                <div class="communities_hero_note"><?php echo iN_HelpSecure($LANG['community_create_note']); ?></div>
            </div>
        </div>
    </div>

    <div class="communities_filters">
        <div class="communities_filter_bar">
            <div class="communities_search">
                <span class="communities_search_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?></span>
                <input type="text"
                       class="communities_search_input"
                       placeholder="<?php echo iN_HelpSecure($LANG['community_search_placeholder']); ?>"
                       aria-label="<?php echo iN_HelpSecure($LANG['community_search_placeholder']); ?>">
            </div>
            <div class="communities_filter_group">
                <div class="communities_filter_label"><?php echo iN_HelpSecure($LANG['community_filter_categories']); ?></div>
                <select class="communities_category_select" id="community_category_select" aria-label="<?php echo iN_HelpSecure($LANG['community_filter_categories']); ?>">
                    <option value="all"><?php echo iN_HelpSecure($LANG['community_filter_all']); ?></option>
                    <?php if (!empty($communityCategories)) { ?>
                        <?php foreach ($communityCategories as $category) {
                            $categoryKey = $category['category_key'] ?? '';
                            if ($categoryKey === '') {
                                continue;
                            }
                            $categoryLabel = isset($LANG[$categoryKey]) ? $LANG[$categoryKey] : $categoryKey;
                        ?>
                            <option value="<?php echo iN_HelpSecure($categoryKey); ?>"><?php echo iN_HelpSecure($categoryLabel); ?></option>
                        <?php } ?>
                    <?php } ?>
                </select>
                <div class="communities_filter_chips">
                    <button type="button" class="community_filter_chip is-active" data-category="all" aria-pressed="true">
                        <?php echo iN_HelpSecure($LANG['community_filter_all']); ?>
                    </button>
                    <?php if (!empty($communityCategories)) { ?>
                        <?php foreach ($communityCategories as $category) {
                            $categoryKey = $category['category_key'] ?? '';
                            if ($categoryKey === '') {
                                continue;
                            }
                            $categoryLabel = isset($LANG[$categoryKey]) ? $LANG[$categoryKey] : $categoryKey;
                        ?>
                            <button type="button" class="community_filter_chip" data-category="<?php echo iN_HelpSecure($categoryKey); ?>" aria-pressed="false">
                                <?php echo iN_HelpSecure($categoryLabel); ?>
                            </button>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
            <div class="communities_filter_group">
                <div class="communities_filter_label"><?php echo iN_HelpSecure($LANG['community_filter_access']); ?></div>
                <div class="communities_filter_toggles">
                    <button type="button" class="community_filter_toggle is-active" data-price="all" aria-pressed="true">
                        <?php echo iN_HelpSecure($LANG['community_filter_all']); ?>
                    </button>
                    <button type="button" class="community_filter_toggle" data-price="paid" aria-pressed="false">
                        <?php echo iN_HelpSecure($LANG['community_filter_paid']); ?>
                    </button>
                    <button type="button" class="community_filter_toggle" data-price="free" aria-pressed="false">
                        <?php echo iN_HelpSecure($LANG['community_filter_free']); ?>
                    </button>
                </div>
            </div>
            <div class="communities_sort">
                <label class="communities_filter_label" for="communities_sort_select"><?php echo iN_HelpSecure($LANG['community_filter_sort']); ?></label>
                <select class="communities_sort_select" id="communities_sort_select">
                    <option value="new"><?php echo iN_HelpSecure($LANG['community_filter_sort_new']); ?></option>
                    <option value="trending"><?php echo iN_HelpSecure($LANG['community_filter_sort_trending']); ?></option>
                    <option value="popular"><?php echo iN_HelpSecure($LANG['community_filter_sort_popular']); ?></option>
                </select>
            </div>
        </div>
    </div>

    <?php if (!empty($featuredTrending) || !empty($featuredNew) || !empty($featuredPopular)) { ?>
        <div class="communities_highlights">
            <div class="communities_highlights_title"><?php echo iN_HelpSecure($LANG['community_highlights_title']); ?></div>
            <div class="communities_highlights_grid">
                <?php
                $highlightBlocks = [
                    ['label' => $LANG['community_filter_sort_trending'], 'community' => $featuredTrending],
                    ['label' => $LANG['community_filter_sort_new'], 'community' => $featuredNew],
                    ['label' => $LANG['community_filter_sort_popular'], 'community' => $featuredPopular],
                ];
                $uniqueHighlightBlocks = [];
                $seenHighlightIds = [];
                foreach ($highlightBlocks as $highlight) {
                    if (empty($highlight['community'])) {
                        continue;
                    }
                    $highlightId = (int)($highlight['community']['id'] ?? 0);
                    if ($highlightId < 1 || isset($seenHighlightIds[$highlightId])) {
                        continue;
                    }
                    $seenHighlightIds[$highlightId] = true;
                    $uniqueHighlightBlocks[] = $highlight;
                }
                ?>
                <?php foreach ($uniqueHighlightBlocks as $highlight) {
                    $community = $highlight['community'];
                    $communityID = (int)$community['id'];
                    $communityTitle = $community['title'] ?? '';
                    $communityCategory = $community['category'] ?? '';
                    $communityCategoryLabel = $communityCategory;
                    if (!empty($communityCategory) && isset($LANG[$communityCategory])) {
                        $communityCategoryLabel = $LANG[$communityCategory];
                    }
                    $communitySlug = $community['slug'] ?? '';
                    $communityCover = $community['cover_image'] ?? '';
                    $communityAvatar = $community['avatar_image'] ?? '';
                    $communityPrice = isset($community['monthly_price']) ? (float)$community['monthly_price'] : 0;
                    $communityLimit = isset($community['member_limit']) ? (int)$community['member_limit'] : 0;
                    $memberCount = isset($community['member_count']) ? (int)$community['member_count'] : 0;
                    $subscriptionRequired = (string)($community['subscription_required'] ?? '1');
                    $communityUrl = $communitySlug ? $base_url . 'community/' . $communitySlug : $base_url . 'communities';
                    $coverUrl = $base_url . 'uploads/web.png';
                    if (!empty($communityCover)) {
                        if (function_exists('storage_public_url')) {
                            $coverUrl = storage_public_url($communityCover);
                        } else {
                            $coverUrl = $base_url . $communityCover;
                        }
                    }
                    $avatarUrl = $base_url . 'uploads/avatars/no_gender.png';
                    if (!empty($communityAvatar)) {
                        if (function_exists('storage_public_url')) {
                            $avatarUrl = storage_public_url($communityAvatar);
                        } else {
                            $avatarUrl = $base_url . $communityAvatar;
                        }
                    }
                    $priceLabel = $subscriptionTypeValue === '2'
                        ? iN_HelpSecure($communityPrice) . html_entity_decode($iN->iN_SelectedMenuIcon('40'))
                        : iN_HelpSecure(formatCurrency($communityPrice, $defaultCurrency));
                    $capacityLabel = $communityLimit > 0
                        ? $memberCount . '/' . $communityLimit
                        : $memberCount;
                    $highlightLabel = $highlight['label'] ?? '';
                ?>
                    <a href="<?php echo iN_HelpSecure($communityUrl); ?>" class="community_highlight_card">
                        <div class="community_highlight_media">
                            <img src="<?php echo iN_HelpSecure($coverUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>">
                            <span class="community_highlight_label"><?php echo iN_HelpSecure($highlightLabel); ?></span>
                            <span class="community_highlight_avatar">
                                <img src="<?php echo iN_HelpSecure($avatarUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>">
                            </span>
                        </div>
                        <div class="community_highlight_body">
                            <div class="community_highlight_title"><?php echo iN_HelpSecure($communityTitle); ?></div>
                            <?php if (!empty($communityCategory)) { ?>
                                <div class="community_highlight_category"><?php echo iN_HelpSecure($communityCategoryLabel); ?></div>
                            <?php } ?>
                            <div class="community_highlight_meta">
                                <span><?php echo iN_HelpSecure($LANG['community_members']); ?>: <?php echo iN_HelpSecure($capacityLabel); ?></span>
                                <span>
                                    <?php
                                    if ($subscriptionRequired === '0') {
                                        echo iN_HelpSecure($LANG['community_free'] ?? 'Free');
                                    } else {
                                        echo $priceLabel;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="communities_grid">
        <?php if (!empty($communities)) { ?>
            <?php foreach ($communities as $community) {
                $communityID = (int)$community['id'];
                $communityTitle = $community['title'] ?? '';
                $communityCategory = $community['category'] ?? '';
                $communityCategoryLabel = $communityCategory;
                if (!empty($communityCategory) && isset($LANG[$communityCategory])) {
                    $communityCategoryLabel = $LANG[$communityCategory];
                }
                $communityDescription = $community['description'] ?? '';
                $communityDescription = trim((string)$communityDescription);
                if ($communityDescription !== '') {
                    $communityDescription = strip_tags($communityDescription);
                    $communityDescription = preg_replace('/\s+/', ' ', $communityDescription);
                }
                $communityDescriptionShort = $communityDescription;
                if ($communityDescriptionShort !== '') {
                    $maxDescriptionLength = 120;
                    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                        if (mb_strlen($communityDescriptionShort) > $maxDescriptionLength) {
                            $communityDescriptionShort = mb_substr($communityDescriptionShort, 0, $maxDescriptionLength) . '...';
                        }
                    } else if (strlen($communityDescriptionShort) > $maxDescriptionLength) {
                        $communityDescriptionShort = substr($communityDescriptionShort, 0, $maxDescriptionLength) . '...';
                    }
                }
                $communitySlug = $community['slug'] ?? '';
                $communityCover = $community['cover_image'] ?? '';
                $communityAvatar = $community['avatar_image'] ?? '';
                $communityPrice = isset($community['monthly_price']) ? (float)$community['monthly_price'] : 0;
                $communityLimit = isset($community['member_limit']) ? (int)$community['member_limit'] : 0;
                $memberCount = isset($community['member_count']) ? (int)$community['member_count'] : 0;
                $isMember = !empty($community['is_member']);
                $isModerator = !empty($community['is_moderator']);
                $isModerationListed = !empty($community['is_moderation_listed']);
                $subscriptionRequired = (string)($community['subscription_required'] ?? '1');
                $isOwner = ($logedIn == 1 && (int)$community['owner_user_id'] === (int)$userID);
                if (!$isModerator && $logedIn == 1 && !$isOwner && !$isMember) {
                    $moderatorData = $iN->iN_GetCommunityModerator($communityID, $userID);
                    $isModerator = !empty($moderatorData);
                }
                $communityUrl = $communitySlug ? $base_url . 'community/' . $communitySlug : $base_url . 'communities';
                $coverUrl = $base_url . 'uploads/web.png';
                if (!empty($communityCover)) {
                    if (function_exists('storage_public_url')) {
                        $coverUrl = storage_public_url($communityCover);
                    } else {
                        $coverUrl = $base_url . $communityCover;
                    }
                }
                $avatarUrl = $base_url . 'uploads/avatars/no_gender.png';
                if (!empty($communityAvatar)) {
                    if (function_exists('storage_public_url')) {
                        $avatarUrl = storage_public_url($communityAvatar);
                    } else {
                        $avatarUrl = $base_url . $communityAvatar;
                    }
                }
                $priceLabel = $subscriptionTypeValue === '2'
                    ? iN_HelpSecure($communityPrice) . html_entity_decode($iN->iN_SelectedMenuIcon('40'))
                    : iN_HelpSecure(formatCurrency($communityPrice, $defaultCurrency));
                $capacityLabel = $communityLimit > 0
                    ? $memberCount . '/' . $communityLimit
                    : $memberCount;
                $priceTypeLabel = $subscriptionRequired === '0' ? 'free' : 'paid';
                $createdTimestamp = 0;
                if (!empty($community['created_at'])) {
                    $createdTimestamp = strtotime((string)$community['created_at']);
                }
            ?>
            <div class="community_card"
                 data-community-card="1"
                 data-title="<?php echo iN_HelpSecure($communityTitle); ?>"
                 data-description="<?php echo iN_HelpSecure($communityDescriptionShort); ?>"
                 data-category="<?php echo iN_HelpSecure($communityCategory); ?>"
                 data-price="<?php echo iN_HelpSecure($priceTypeLabel); ?>"
                 data-members="<?php echo iN_HelpSecure($memberCount); ?>"
                 data-created="<?php echo iN_HelpSecure($createdTimestamp); ?>"
                 data-amount="<?php echo iN_HelpSecure($communityPrice); ?>">
                <a href="<?php echo iN_HelpSecure($communityUrl); ?>" class="community_card_cover">
                    <img src="<?php echo iN_HelpSecure($coverUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>">
                    <span class="community_card_price_badge">
                        <?php
                            if ($subscriptionRequired === '0') {
                                echo iN_HelpSecure($LANG['community_free'] ?? 'Free');
                            } else {
                                echo $priceLabel;
                            }
                        ?>
                    </span>
                </a>
                <div class="community_card_body">
                    <div class="community_card_head">
                        <div class="community_card_avatar">
                            <img src="<?php echo iN_HelpSecure($avatarUrl); ?>" alt="<?php echo iN_HelpSecure($communityTitle); ?>">
                        </div>
                        <div class="community_card_title">
                            <a href="<?php echo iN_HelpSecure($communityUrl); ?>">
                                <?php echo iN_HelpSecure($communityTitle); ?>
                            </a>
                        </div>
                    </div>
                    <?php if (!empty($communityCategory)) { ?>
                        <div class="community_card_category"><?php echo iN_HelpSecure($communityCategoryLabel); ?></div>
                    <?php } ?>
                    <?php if (!empty($communityDescriptionShort)) { ?>
                        <div class="community_card_description"><?php echo iN_HelpSecure($communityDescriptionShort); ?></div>
                    <?php } ?>
                    <div class="community_card_meta">
                        <span class="community_card_meta_item">
                            <?php echo iN_HelpSecure($LANG['community_members']); ?>: <?php echo iN_HelpSecure($capacityLabel); ?>
                        </span>
                        <span class="community_card_meta_item">
                            <?php echo iN_HelpSecure($LANG['community_monthly_price']); ?>:
                            <?php
                                if ($subscriptionRequired === '0') {
                                    echo iN_HelpSecure($LANG['community_free'] ?? 'Free');
                                } else {
                                    echo $priceLabel;
                                }
                            ?>
                        </span>
                    </div>
                    <div class="community_card_actions">
                        <?php if ($isOwner || $isMember || $isModerator || $isModerationListed) { ?>
                            <a href="<?php echo iN_HelpSecure($communityUrl); ?>" class="i_nex_btn community_enter_btn transition">
                                <?php echo iN_HelpSecure($LANG['enter_community']); ?>
                            </a>
                        <?php } else { ?>
                            <button type="button" class="i_nex_btn community_join_btn transition <?php echo $logedIn == 0 ? 'loginForm' : ($subscriptionRequired === '0' ? 'communityJoinFree' : 'communityJoinModal'); ?>" data-community="<?php echo (int)$communityID; ?>">
                                <?php echo iN_HelpSecure($LANG['join_community']); ?>
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        <?php } ?>
        <div class="community_empty_state<?php echo !empty($communities) ? ' is-hidden' : ''; ?>" role="status" aria-live="polite">
            <div class="community_empty_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('193')); ?></div>
            <div class="community_empty_title"><?php echo iN_HelpSecure($LANG['community_directory_empty']); ?></div>
            <div class="community_empty_note"><?php echo iN_HelpSecure($LANG['community_directory_empty_note']); ?></div>
        </div>
    </div>
    <div class="community_loading_state" role="status" aria-live="polite">
        <div class="community_loading_card">
            <div class="community_loading_spinner"></div>
            <div class="community_loading_text">
                <div class="community_loading_title"><?php echo iN_HelpSecure($LANG['community_loading_title']); ?></div>
                <div class="community_loading_note"><?php echo iN_HelpSecure($LANG['community_loading_note']); ?></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
