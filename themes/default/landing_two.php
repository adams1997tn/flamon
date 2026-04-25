<?php
$themeAssetBase = iN_HelpSecure($base_url) . 'themes/' . iN_HelpSecure($currentTheme);
$page = 'moreposts';

$formatNumber = static function ($number) {
    if ($number === null || $number === '') {
        return '0';
    }
    $number = (int)$number;
    if ($number >= 1000000) {
        return number_format($number / 1000000, 1) . 'M';
    }
    if ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K';
    }
    return number_format($number);
};

$landingTwoCreatorsLimit = 15;
if ($logedIn != 0 && !empty($userID)) {
    $landingTwoCreatorsRaw = $iN->iN_SuggestionCreatorsList($userID, $landingTwoCreatorsLimit, $userID ?? null, $viewerCountryCode ?? null);
    if (!$landingTwoCreatorsRaw) {
        $landingTwoCreatorsRaw = $iN->iN_SuggestionCreatorsListOut($landingTwoCreatorsLimit, $userID ?? null, $viewerCountryCode ?? null);
    }
} else {
    $landingTwoCreatorsRaw = $iN->iN_SuggestionCreatorsListOut($landingTwoCreatorsLimit, $userID ?? null, $viewerCountryCode ?? null);
}

$landingTwoCreators = [];
$landingTwoCreatorSeen = [];
$landingTwoCreatorIds = [];
if ($landingTwoCreatorsRaw) {
    foreach ($landingTwoCreatorsRaw as $creatorRow) {
        $creatorID = isset($creatorRow['iuid']) ? (int)$creatorRow['iuid'] : (isset($creatorRow['post_owner_id']) ? (int)$creatorRow['post_owner_id'] : 0);
        if ($creatorID > 0) {
            $landingTwoCreatorIds[$creatorID] = $creatorID;
        }
    }
}
$landingTwoCreatorIds = array_values($landingTwoCreatorIds);
if (!empty($landingTwoCreatorIds)) {
    $iN->iN_PreloadUserMediaPathMaps($landingTwoCreatorIds);
}
$landingTwoProfileMap = !empty($landingTwoCreatorIds) ? $iN->iN_GetUsersBasicProfileMap($landingTwoCreatorIds) : [];
$landingTwoPostsMap = !empty($landingTwoCreatorIds) ? $iN->iN_TotalPostsMap($landingTwoCreatorIds) : [];
$landingTwoFollowersMap = !empty($landingTwoCreatorIds) ? $iN->iN_UserTotalFollowerUsersMap($landingTwoCreatorIds) : [];
$landingTwoFollowingMap = !empty($landingTwoCreatorIds) ? $iN->iN_UserTotalFollowingUsersMap($landingTwoCreatorIds) : [];

if ($landingTwoCreatorsRaw) {
    foreach ($landingTwoCreatorsRaw as $creatorRow) {
        $creatorID = $creatorRow['iuid'] ?? ($creatorRow['post_owner_id'] ?? null);
        if (!$creatorID) {
            continue;
        }
        if (isset($landingTwoCreatorSeen[(int)$creatorID])) {
            continue;
        }
        $landingTwoCreatorSeen[(int)$creatorID] = true;
        $profileUsername = $creatorRow['i_username'] ?? '';
        if ($profileUsername === '') {
            continue;
        }
        $profileFullName = $creatorRow['i_user_fullname'] ?? $profileUsername;
        $userVerified = isset($creatorRow['user_verified_status']) ? $creatorRow['user_verified_status'] : null;
        $creatorIntID = (int)$creatorID;
        $profileDetails = isset($landingTwoProfileMap[$creatorIntID]) ? $landingTwoProfileMap[$creatorIntID] : [];
        $hasCustomAvatar = !empty($profileDetails['user_avatar']);
        $hasCustomCover = !empty($profileDetails['user_cover']);
        $profileBio = isset($profileDetails['u_bio']) ? trim(strip_tags($profileDetails['u_bio'])) : '';
        if ($profileBio !== '') {
            $profileBio = mb_substr($profileBio, 0, 220);
        }
        $cover = $iN->iN_UserCover($creatorID, $base_url);
        $avatar = $iN->iN_UserAvatar($creatorID, $base_url);
        $totalPosts = isset($landingTwoPostsMap[$creatorIntID]) ? (int)$landingTwoPostsMap[$creatorIntID] : (int)$iN->iN_TotalPosts($creatorID);
        $totalFollowers = isset($landingTwoFollowersMap[$creatorIntID]) ? (int)$landingTwoFollowersMap[$creatorIntID] : (int)$iN->iN_UserTotalFollowerUsers($creatorID);
        $totalFollowing = isset($landingTwoFollowingMap[$creatorIntID]) ? (int)$landingTwoFollowingMap[$creatorIntID] : (int)$iN->iN_UserTotalFollowingUsers($creatorID);

        $landingTwoCreators[] = [
            'username' => $profileUsername,
            'fullName' => $profileFullName,
            'verified' => $userVerified == '1',
            'cover' => $cover,
            'avatar' => $avatar,
            'bio' => $profileBio,
            'posts' => (int)$totalPosts,
            'followers' => (int)$totalFollowers,
            'following' => (int)$totalFollowing,
            'profileUrl' => $base_url . $profileUsername,
            'hasCustomAvatar' => $hasCustomAvatar,
            'hasCustomCover' => $hasCustomCover,
        ];
        if (count($landingTwoCreators) >= $landingTwoCreatorsLimit) {
            break;
        }
    }
}

$heroUsersLimit = 15;
$heroDemoAvatar = $base_url . 'uploads/avatars/no_gender.png';
$heroDemoCover = $themeAssetBase . '/img/landing_two_preview.png';
$heroUsersRaw = $iN->iN_GetSearchResult('', $heroUsersLimit * 2, 'no', $userID ?? null, $viewerCountryCode ?? null);
$heroCandidateIds = [];
if ($heroUsersRaw) {
    foreach ($heroUsersRaw as $heroUserRow) {
        $heroCandidateID = isset($heroUserRow['iuid']) ? (int)$heroUserRow['iuid'] : 0;
        if ($heroCandidateID > 0) {
            $heroCandidateIds[$heroCandidateID] = $heroCandidateID;
        }
    }
}
$heroCandidateIds = array_values($heroCandidateIds);
if (!empty($heroCandidateIds)) {
    $iN->iN_PreloadUserMediaPathMaps($heroCandidateIds);
}
$heroPostsMap = !empty($heroCandidateIds) ? $iN->iN_TotalPostsMap($heroCandidateIds) : [];
$heroFollowersMap = !empty($heroCandidateIds) ? $iN->iN_UserTotalFollowerUsersMap($heroCandidateIds) : [];
$heroDataSource = [];
$heroUserSeen = [];
if ($heroUsersRaw) {
    foreach ($heroUsersRaw as $heroUserRow) {
        $heroUserID = isset($heroUserRow['iuid']) ? (int)$heroUserRow['iuid'] : 0;
        if ($heroUserID <= 0 || isset($heroUserSeen[$heroUserID])) {
            continue;
        }
        $heroUsername = trim((string)($heroUserRow['i_username'] ?? ''));
        if ($heroUsername === '') {
            continue;
        }
        $heroUserSeen[$heroUserID] = true;
        $heroFullName = trim((string)($heroUserRow['i_user_fullname'] ?? ''));
        if ($heroFullName === '') {
            $heroFullName = $heroUsername;
        }
        $heroHasCustomAvatar = !empty($heroUserRow['user_avatar']);
        $heroHasCustomCover = !empty($heroUserRow['user_cover']);
        $heroAvatar = $iN->iN_UserAvatar($heroUserID, $base_url);
        $heroCover = $iN->iN_UserCover($heroUserID, $base_url);
        if (!$heroHasCustomAvatar) {
            $heroAvatar = $heroDemoAvatar;
        }
        if (!$heroHasCustomCover) {
            $heroCover = $heroDemoCover;
        }
        $heroDataSource[] = [
            'fullName' => $heroFullName,
            'username' => $heroUsername,
            'cover' => $heroCover,
            'avatar' => $heroAvatar,
            'posts' => isset($heroPostsMap[$heroUserID]) ? (int)$heroPostsMap[$heroUserID] : (int)$iN->iN_TotalPosts($heroUserID),
            'followers' => isset($heroFollowersMap[$heroUserID]) ? (int)$heroFollowersMap[$heroUserID] : (int)$iN->iN_UserTotalFollowerUsers($heroUserID),
        ];
        if (count($heroDataSource) >= $heroUsersLimit) {
            break;
        }
    }
}
if (!empty($heroDataSource) && count($heroDataSource) < 3) {
    $heroSeed = $heroDataSource;
    while (count($heroDataSource) < 3) {
        $heroDataSource[] = $heroSeed[(count($heroDataSource) - count($heroSeed)) % count($heroSeed)];
    }
}
$heroDataSource = array_values($heroDataSource);
$heroInitial = array_slice($heroDataSource, 0, 3);

$heroDataPayload = [];
foreach ($heroDataSource as $cardData) {
    $heroDataPayload[] = [
        'fullName' => $cardData['fullName'],
        'username' => $cardData['username'],
        'cover' => $cardData['cover'],
        'avatar' => $cardData['avatar'],
        'statOneValue' => $cardData['posts'],
        'statTwoValue' => $cardData['followers'],
    ];
}
$heroDataJson = htmlspecialchars(json_encode($heroDataPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');

$galleryRows = [];
if (!empty($landingTwoCreators)) {
    $chunkSize = (int)ceil(count($landingTwoCreators) / 2);
    $chunkSize = $chunkSize > 0 ? $chunkSize : count($landingTwoCreators);
    $galleryRows = array_chunk($landingTwoCreators, $chunkSize ?: 1);
    if (count($galleryRows) === 1) {
        $galleryRows[] = $galleryRows[0];
    } elseif (count($galleryRows) > 2) {
        $galleryRows = array_slice($galleryRows, 0, 2);
    }
} else {
    $galleryRows = [];
}

$featureCards = [
    [
        'image' => $landingpageFirstDesctiptionImage,
        'title' => $LANG['l_premium'],
        'desc' => $LANG['l_exlusive_contents'],
    ],
    [
        'image' => $landingpageSecondDesctiptionImage,
        'title' => $LANG['fan_club'],
        'desc' => $LANG['fan_club_desc'],
    ],
    [
        'image' => $landingpageThirdDesctiptionImage,
        'title' => $LANG['l_live_streamings'],
        'desc' => $LANG['l_live_streamings_desc'],
    ],
    [
        'image' => $landingpageFourthDesctiptionImage,
        'title' => $LANG['l_private_content'],
        'desc' => $LANG['l_private_content_desc'],
    ],
    [
        'image' => $landingpageFifthDesctiptionImage,
        'title' => $LANG['l_private_messages'],
        'desc' => $LANG['l_private_messages_desc'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle);?></title>
    <?php
        include __DIR__ . "/layouts/header/meta.php";
        include __DIR__ . "/layouts/header/css.php";
        include __DIR__ . "/layouts/header/javascripts.php";
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $themeAssetBase; ?>/css/landing_two.css?v=<?php echo iN_HelpSecure($version); ?>">
</head>
<body data-adminfee="<?php echo $adminFee;?>" data-currencyleft="<?php echo $currencys[$defaultCurrency];?>">
<?php if($logedIn == 0){ include __DIR__ . '/layouts/login_form.php'; }?>
<header class="site-header">
    <a class="logo" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL); ?>">
        <img src="<?php echo iN_HelpSecure($siteLogoUrl); ?>" alt="<?php echo iN_HelpSecure($siteName); ?>">
    </a>
    <div class="header-center">
        <div class="search-bar">
            <input type="search" id="search_creator" placeholder="<?php echo iN_HelpSecure($LANG['search_creators'] ?? 'Search Creators'); ?>" autocomplete="off">
            <div class="i_general_box_search_container generalBox search_cont_style">
                <div class="btest">
                    <div class="i_user_details">
                        <div class="i_box_messages_header">
                            <?php echo iN_HelpSecure($LANG['search']); ?>
                        </div>
                        <div class="i_header_others_box sb_items"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="header-actions" id="headerActions">
        <div class="header-actions__items" id="headerActionsMenu">
            <?php if($logedIn == 1){ ?>
                <a class="btn login" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>posts"><?php echo iN_HelpSecure($LANG['dashboard']);?></a>
            <?php } else { ?>
                <button type="button" class="btn login loginForm"><?php echo iN_HelpSecure($LANG['login']);?></button>
                <a class="btn signup" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>register"><?php echo iN_HelpSecure($LANG['sign_up']);?></a>
            <?php } ?>
            <a class="i_language" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>creators"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95')); ?></a>
        </div>
        <button type="button" class="header-toggle" aria-expanded="false" aria-controls="headerActionsMenu">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('100'));?>
        </button>
    </div>
</header>
<?php if ($logedIn == 0 && (string)$ageConfirm === '1') { ?>
    <?php include __DIR__ . "/layouts/header/age_confirm.php"; ?>
<?php } ?>
<main class="page landing-two-page">
    <div class="hero-wrapper">
        <section class="hero">
            <div class="hero-text">
                <p class="tagline"><?php echo strtolower(iN_HelpSecure($siteName));?></p>
                <h1><?php echo iN_HelpSecure($LANG['landing_title']);?></h1>
                <p class="lede"><?php echo iN_HelpSecure($LANG['landing_desc']);?></p>
                <div class="hero-claim">
                    <div class="hero-claim__prefix"><?php echo preg_replace( "#^[^:/.]*[:/]+#i", "", $base_url );?></div>
                    <input type="text" id="clName" placeholder="<?php echo iN_HelpSecure($LANG['username']);?>" autocomplete="off">
                    <button type="button" class="hero-claim__btn claimname"><?php echo iN_HelpSecure($LANG['claim']);?></button>
                </div>
                <div class="hero-claim__errors">
                    <div class="error_report unmempt"><?php echo iN_HelpSecure($LANG['username_should_not_be_empty']);?></div>
                    <div class="error_report unmexist"><?php echo iN_HelpSecure($LANG['try_different_username']);?></div>
                    <div class="error_report invldcharctr"><?php echo iN_HelpSecure($LANG['invalid_username']);?></div>
                </div>
            </div>
            <?php if (!empty($heroInitial)) { ?>
            <div class="hero-cards" aria-label="Avatar cards" data-cards="<?php echo $heroDataJson; ?>">
                <span class="floating-label label-top-left"><?php echo iN_HelpSecure($LANG['animation_box_subscriptions']);?></span>
                <span class="floating-label label-top-right"><?php echo iN_HelpSecure($LANG['animation_box_comissions']);?></span>
                <span class="floating-label label-bottom-left"><?php echo iN_HelpSecure($LANG['animation_box_premium_content']);?></span>
                <span class="floating-label label-bottom-right"><?php echo iN_HelpSecure($LANG['animation_box_live_streaming']);?></span>
                <?php
                    $cardClasses = ['card card--back', 'card card--front', 'card card--right'];
                    foreach ($cardClasses as $index => $cardClass) {
                        $cardData = $heroInitial[$index];
                ?>
                <div class="<?php echo $cardClass; ?>">
                    <img class="card-image" data-hero-cover src="<?php echo iN_HelpSecure($cardData['cover']);?>" alt="<?php echo iN_HelpSecure($cardData['fullName']);?>">
                    <div class="card-body">
                        <div>
                            <h3 class="card-title" data-hero-title><?php echo iN_HelpSecure($cardData['fullName']);?></h3>
                            <p class="card-artist" data-hero-artist>@<?php echo iN_HelpSecure($cardData['username']);?></p>
                        </div>
                        <div class="avatar-thumb">
                            <img class="card-avatar" data-hero-avatar src="<?php echo iN_HelpSecure($cardData['avatar']);?>" alt="<?php echo iN_HelpSecure($cardData['fullName']);?>">
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="card-date" data-hero-stat-one><?php echo iN_HelpSecure($formatNumber($cardData['posts'] ?? 0));?></span>
                        <span class="card-time" data-hero-stat-two><?php echo iN_HelpSecure($formatNumber($cardData['followers'] ?? 0));?></span>
                        <span class="icons">
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </span>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </section>
    </div>

 






  <section class="earnings-sim">
        <div class="earnings-sim__inner">
            <div class="earnings-sim__head">
                <p class="earnings-sim__eyebrow"><?php echo iN_HelpSecure($LANG['creators_earning_simulator']);?></p>
                <h2><?php echo strip_tags($LANG['per_month_calculate_earn']);?></h2>
                <p><?php echo iN_HelpSecure($LANG['calculate_how_much_can_earn']);?></p>
            </div>
            <div class="earnings-sim__controls">
                <div class="earnings-sim__control">
                    <label for="rangeNumberFollowers">
                        <?php echo iN_HelpSecure($LANG['l_number_of_followers']);?> <span aria-hidden="true">👥</span>
                    </label>
                    <div class="earnings-sim__value" data-follower-value><span id="numberFollowers"><?php echo number_format(1000);?></span></div>
                    <input id="rangeNumberFollowers" type="range" min="1000" max="1000000" step="1000" value="1000">
                </div>
                <div class="earnings-sim__control">
                    <label for="rangeMonthlySubscription">
                        <?php echo iN_HelpSecure($LANG['l_monthly_subscription_price']);?> <span aria-hidden="true">💸</span>
                    </label>
                    <div class="earnings-sim__value">
                        <span data-price-value id="monthlySubscription"><?php echo iN_HelpSecure(formatCurrency(2, $defaultCurrency));?></span>
                    </div>
                    <input id="rangeMonthlySubscription" type="range" min="2" max="100" step="1" value="2">
                </div>
            </div>
            <div class="earnings-sim__result">
                <p><?php echo iN_HelpSecure($LANG['calculate_how_much_can_earn']);?></p>
                <div class="earnings-sim__amount" data-estimate-value><span id="estimatedEarn"><?php echo iN_HelpSecure(formatCurrency(95, $defaultCurrency));?></span></div>
                <p class="earnings-sim__hint"><?php echo iN_HelpSecure($LANG['not_for_calculate']);?></p>
            </div>
        </div>
    </section>












    <section class="movement-blurb">
        <div class="movement-blurb__title"><?php echo iN_HelpSecure($LANG['landing_two_spotlight_label']);?></div>
        <div class="movement-blurb__headline">
            <?php echo nl2br(iN_HelpSecure($LANG['landing_two_spotlight_headline']));?>
        </div>
        <div class="movement-blurb__text">
            <p><?php echo $LANG['l_join_our_community_now_and_start_growing_users'];?></p>
            <p><?php echo iN_HelpSecure($LANG['landing_desc']);?></p>
            <a class="movement-blurb__btn" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL);?>register"><?php echo iN_HelpSecure($LANG['l_becomea_creator']);?> →</a>
        </div>
    </section>

    <section class="cp-gallery" data-framer-name="Gallery">
        <div class="cp-gallery-intro">
            <h2><?php echo iN_HelpSecure($LANG['suggested_creators'] ?? ($LANG['our_creators'] ?? ($LANG['best_creators_of_last_week'] ?? 'Creators')));?></h2>
            <p><?php echo iN_HelpSecure($LANG['landing_two_creators_subtitle']);?></p>
        </div>
        <?php foreach ($galleryRows as $rowIndex => $rowCards) {
            $rowClass = $rowIndex % 2 === 0 ? 'cp-gallery-row cp-gallery-right' : 'cp-gallery-row cp-gallery-left';
        ?>
        <div class="<?php echo $rowClass; ?>">
            <div class="cp-gallery-track">
                <div class="cp-gallery-group">
                    <?php foreach ($rowCards as $creator) {
                        $profileUrl = iN_HelpSecure($creator['profileUrl']);
                        $fullName = iN_HelpSecure($creator['fullName']);
                        $username = iN_HelpSecure($creator['username']);
                    ?>
                    <div class="cp-gallery-card">
                        <img class="cp-gallery-card__image" src="<?php echo iN_HelpSecure($creator['cover']);?>" alt="Showcase from <?php echo $fullName;?>">
                        <div class="cp-gallery-card__info">
                            <div class="cp-gallery-card__header">
                                <img class="cp-gallery-card__avatar" src="<?php echo iN_HelpSecure($creator['avatar']);?>" alt="<?php echo $fullName;?> profile image">
                                <div>
                                    <p class="cp-gallery-card__fullname">
                                        <?php echo $fullName;?>
                                        <?php if (!empty($creator['verified'])) { ?>
                                        <span class="cp-gallery-card__badge" aria-label="Verified">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M9.55 17.53a1 1 0 0 1-.7-.29l-3.1-3.1a1 1 0 0 1 1.42-1.42l2.4 2.39 7.26-7.25a1 1 0 1 1 1.42 1.41l-7.96 7.95a1 1 0 0 1-.74.31z"/>
                                            </svg>
                                        </span>
                                        <?php } ?>
                                    </p>
                                    <p class="cp-gallery-card__username">@<?php echo $username;?></p>
                                </div>
                            </div>
                            <?php if(!empty($creator['bio'])) { ?>
                            <p class="cp-gallery-card__bio"><?php echo iN_HelpSecure($creator['bio']);?></p>
                            <?php } ?>
                            <div class="cp-gallery-card__stats">
                                <div class="cp-gallery-card__stat">
                                    <span class="cp-gallery-card__stat-value"><?php echo iN_HelpSecure($formatNumber($creator['posts']));?></span>
                                    <span class="cp-gallery-card__stat-label"><?php echo iN_HelpSecure($LANG['posts']);?></span>
                                </div>
                                <div class="cp-gallery-card__stat">
                                    <span class="cp-gallery-card__stat-value"><?php echo iN_HelpSecure($formatNumber($creator['followers']));?></span>
                                    <span class="cp-gallery-card__stat-label"><?php echo iN_HelpSecure($LANG['my_followers']);?></span>
                                </div>
                                <div class="cp-gallery-card__stat">
                                    <span class="cp-gallery-card__stat-value"><?php echo iN_HelpSecure($formatNumber($creator['following']));?></span>
                                    <span class="cp-gallery-card__stat-label"><?php echo iN_HelpSecure($LANG['im_following']);?></span>
                                </div>
                            </div>
                            <div class="cp-gallery-card__actions">
                                <a class="cp-gallery-card__button" href="<?php echo $profileUrl;?>"><?php echo iN_HelpSecure($LANG['see_profile']);?></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="cp-gallery-group clone"></div>
            </div>
        </div>
        <?php } ?>
    </section>

  
    <section class="faq" aria-labelledby="faq-title">
        <div class="faq__container">
            <div class="faq__content">
                <div class="faq__headline">
                    <h2 class="faq__title" id="faq-title"><?php echo iN_HelpSecure($LANG['landing_two_faq_title']);?></h2>
                    <p class="faq__description"><?php echo iN_HelpSecure($LANG['landing_two_faq_desc']);?></p>
                </div>
                <div class="faq__cards" role="list">
                    <?php
                    $qaList = $iN->iN_ListQuestionAnswerFromLanding();
                    if($qaList){
                        $counter = 1;
                        foreach($qaList as $qaData){
                            $qaTitle = $qaData['qa_title'];
                            $qaDesc = $qaData['qa_description'];
                            $questionID = 'faq-question-' . $counter;
                            $answerID = 'faq-answer-' . $counter;
                    ?>
                    <article class="faq__card" data-faq>
                        <button class="faq__card-button" type="button" aria-expanded="<?php echo $counter === 1 ? 'true' : 'false';?>" aria-controls="<?php echo $answerID; ?>" id="<?php echo $questionID; ?>">
                            <span class="faq__card-icon" aria-hidden="true">
                                <span class="faq__card-line"></span>
                                <span class="faq__card-line faq__card-line--vertical"></span>
                            </span>
                            <span class="faq__card-text"><?php echo iN_HelpSecure($iN->iN_Secure($qaTitle));?></span>
                        </button>
                        <div class="faq__card-panel" id="<?php echo $answerID; ?>" role="region" aria-labelledby="<?php echo $questionID; ?>" <?php echo $counter === 1 ? '' : 'hidden';?>>
                            <p><?php echo iN_HelpSecure($iN->iN_Secure($qaDesc));?></p>
                        </div>
                    </article>
                    <?php $counter++; } } ?>
                </div>
            </div>
        </div>
    </section>
</main>
<div class="footer_container_out"><?php include __DIR__ . "/layouts/footer.php";?></div>
<script src="<?php echo $themeAssetBase; ?>/js/landing_two.js?v=<?php echo iN_HelpSecure($version); ?>" defer></script>
</body>
</html>
