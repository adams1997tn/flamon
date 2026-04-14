<?php
$agencyModuleEnabled = (isset($agencyModuleStatus) ? $agencyModuleStatus : 'yes') === 'yes';
if (!$agencyModuleEnabled) {
    header('Location: ' . route_url('404'));
    exit;
}
$csrfToken = function_exists('csrf_get_token')
    ? csrf_get_token()
    : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
$viewerId = ($logedIn == 1) ? (int)$userID : 0;
$viewerIsCreator = ($viewerId > 0 && $iN->iN_CheckUserIsCreator($viewerId) == 1);
$viewerIsOwner = $viewerId > 0 ? $iN->iN_IsAgencyOwner($viewerId) : false;
$hasMembership = false;
$hasPendingCreate = false;
$requestStatuses = [];
if ($viewerId > 0) {
    $hasMembership = (bool)DB::col(
        "SELECT 1 FROM i_agency_members WHERE creator_iuid_fk = ? LIMIT 1",
        [$viewerId]
    );
    $hasPendingCreate = (bool)DB::col(
        "SELECT 1 FROM i_agency_create_requests WHERE owner_iuid_fk = ? AND status = 'pending' LIMIT 1",
        [$viewerId]
    );
    $requestRows = DB::all(
        "SELECT agency_id_fk, status FROM i_agency_requests WHERE creator_iuid_fk = ? ORDER BY ar_id DESC",
        [$viewerId]
    );
    if (!empty($requestRows)) {
        foreach ($requestRows as $requestRow) {
            $agencyId = (int)($requestRow['agency_id_fk'] ?? 0);
            if ($agencyId > 0 && !isset($requestStatuses[$agencyId])) {
                $requestStatuses[$agencyId] = (string)($requestRow['status'] ?? '');
            }
        }
    }
}
$viewerHasAgencyLock = $viewerIsOwner || $hasMembership || $hasPendingCreate;
$nowTime = time();
$agencies = DB::all(
    "SELECT A.*, U.i_username, U.i_user_fullname,
            (SELECT COUNT(*) FROM i_agency_members M WHERE M.agency_id_fk = A.agency_id) AS member_count,
            (SELECT COUNT(*) FROM i_agency_boosts B WHERE B.agency_id_fk = A.agency_id AND B.status = 'active' AND B.end_at > ?) AS boost_count
     FROM i_agencies A
     LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk
     WHERE A.agency_status = 'active'
     ORDER BY A.agency_id DESC",
    [$nowTime]
);
if (empty($agencies)) {
    $agencies = [];
}
$totalAgencies = count($agencies);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        include 'header/meta.php';
        include 'header/css.php';
        include 'header/javascripts.php';
    ?>
</head>
<body>
<?php if ($logedIn == 0) { include 'login_form.php'; } ?>
<?php include 'header/header.php'; ?>
<div class="wrapper agencies_wrapper">
    <input type="hidden" id="creatorAgencyCsrf" value="<?php echo iN_HelpSecure($csrfToken); ?>">
    <div class="agencies_hero">
        <div class="agencies_hero_content">
            <div class="agencies_hero_text">
                <div class="agencies_hero_kicker">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('195')); ?>
                    <span><?php echo iN_HelpSecure($LANG['agency_module_title']); ?></span>
                </div>
                <h1 class="agencies_hero_title"><?php echo iN_HelpSecure($LANG['agency_module_title']); ?></h1>
                <p class="agencies_hero_subtitle"><?php echo iN_HelpSecure($LANG['agency_directory_subtitle']); ?></p>
            </div>
            <div class="agencies_hero_highlights">
                <div class="agencies_hero_chip">
                    <div class="agencies_hero_chip_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('195')); ?>
                    </div>
                    <div>
                        <div class="agencies_hero_chip_label"><?php echo iN_HelpSecure($LANG['agency_directory_metric_agencies']); ?></div>
                        <div class="agencies_hero_chip_value"><?php echo iN_HelpSecure($totalAgencies); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="agencies_filters">
        <div class="agencies_filter_bar">
            <div class="agencies_search">
                <span class="agencies_search_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?></span>
                <input type="text"
                       class="agencies_search_input"
                       placeholder="<?php echo iN_HelpSecure($LANG['agency_search_placeholder']); ?>"
                       aria-label="<?php echo iN_HelpSecure($LANG['agency_search_placeholder']); ?>">
            </div>
            <div class="agencies_sort">
                <label class="agencies_filter_label" for="agencies_sort_select"><?php echo iN_HelpSecure($LANG['agency_sort_label']); ?></label>
                <select class="agencies_sort_select" id="agencies_sort_select">
                    <option value="new"><?php echo iN_HelpSecure($LANG['agency_sort_newest']); ?></option>
                    <option value="name"><?php echo iN_HelpSecure($LANG['agency_sort_name']); ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="agencies_grid">
        <?php if (!empty($agencies)) { ?>
            <?php foreach ($agencies as $agency) {
                $agencyId = (int)($agency['agency_id'] ?? 0);
                $agencyName = $agency['agency_name'] ?? '';
                $agencyUrl = $agencyId > 0 ? ($base_url . 'agency/' . $agencyId) : '#';
                $agencyLogo = $iN->iN_AgencyLogoUrl($agency, $base_url);
                $agencyCover = $iN->iN_AgencyCoverUrl($agency, $base_url);
                $ownerId = (int)($agency['owner_iuid_fk'] ?? 0);
                $ownerName = $agency['i_user_fullname'] ?? $agency['i_username'] ?? '';
                $ownerUsername = $agency['i_username'] ?? '';
                $ownerUrl = $ownerUsername !== '' ? ($base_url . $ownerUsername) : '';
                $ownerLabel = $ownerName !== '' ? $ownerName : ('#' . $ownerId);
                $memberCount = (int)($agency['member_count'] ?? 0);
                $boostCount = (int)($agency['boost_count'] ?? 0);
                $createdAt = (int)($agency['agency_created_at'] ?? 0);
                $agencyAbout = trim((string)($agency['agency_about'] ?? ''));
                $agencyServices = trim((string)($agency['agency_services'] ?? ''));
                $agencyBioRaw = $agencyAbout !== '' ? $agencyAbout : $agencyServices;
                $agencyBio = trim(strip_tags($agencyBioRaw));
                if ($agencyBio !== '' && mb_strlen($agencyBio) > 140) {
                    $agencyBio = mb_substr($agencyBio, 0, 140) . '...';
                }
                $requestStatus = $requestStatuses[$agencyId] ?? '';
                $requestStatusLabel = $requestStatus !== '' ? ($LANG['agency_request_status_' . $requestStatus] ?? $requestStatus) : '';
                $canRequest = $viewerIsCreator && !$viewerHasAgencyLock && ($requestStatus === '' || $requestStatus === 'rejected' || $requestStatus === 'canceled');
            ?>
                <div class="agency_card"
                     data-agency-card
                     data-title="<?php echo iN_HelpSecure($agencyName); ?>"
                     data-owner="<?php echo iN_HelpSecure($ownerLabel); ?>"
                     data-fee="<?php echo iN_HelpSecure((string)($agency['agency_fee'] ?? '0')); ?>"
                     data-created="<?php echo iN_HelpSecure($createdAt); ?>">
                    <a href="<?php echo iN_HelpSecure($agencyUrl); ?>" class="agency_card_cover">
                        <img src="<?php echo iN_HelpSecure($agencyCover); ?>" alt="<?php echo iN_HelpSecure($agencyName); ?>">
                    </a>
                    <div class="agency_card_body">
                        <div class="agency_card_head">
                            <div class="agency_card_avatar">
                                <img src="<?php echo iN_HelpSecure($agencyLogo); ?>" alt="<?php echo iN_HelpSecure($agencyName); ?>">
                            </div>
                            <div class="agency_card_title">
                                <a href="<?php echo iN_HelpSecure($agencyUrl); ?>">
                                    <?php echo iN_HelpSecure($agencyName); ?>
                                </a>
                            </div>
                        </div>
                        <div class="agency_card_owner">
                            <?php echo iN_HelpSecure($LANG['agency_owner']); ?>:
                            <?php if ($ownerUrl !== '') { ?>
                                <a href="<?php echo iN_HelpSecure($ownerUrl); ?>"><?php echo iN_HelpSecure($ownerLabel); ?></a>
                            <?php } else { ?>
                                <?php echo iN_HelpSecure($ownerLabel); ?>
                            <?php } ?>
                        </div>
                        <?php if ($agencyBio !== '') { ?>
                            <div class="agency_card_description"><?php echo iN_HelpSecure($agencyBio); ?></div>
                        <?php } ?>
                        <div class="agency_card_meta">
                            <span class="agency_card_meta_item">
                                <?php echo iN_HelpSecure($LANG['agency_members']); ?>: <?php echo iN_HelpSecure($memberCount); ?>
                            </span>
                            <span class="agency_card_meta_item">
                                <?php echo iN_HelpSecure($LANG['agency_boosted_creators']); ?>: <?php echo iN_HelpSecure($boostCount); ?>
                            </span>
                        </div>
                        <div class="agency_card_actions">
                            <?php if ($logedIn == 0) { ?>
                                <button type="button" class="i_nex_btn agency_join_btn transition loginForm">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['agency_join_request']); ?>
                                </button>
                            <?php } elseif (!$viewerIsCreator) { ?>
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['no_actions']); ?></div>
                            <?php } elseif ($hasPendingCreate) { ?>
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_create_pending']); ?></div>
                            <?php } elseif ($viewerIsOwner || $hasMembership) { ?>
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_already_member']); ?></div>
                            <?php } elseif ($requestStatus === 'pending') { ?>
                                <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_request_pending']); ?></div>
                            <?php } elseif (!$canRequest && $requestStatusLabel !== '') { ?>
                                <div class="box_not"><?php echo iN_HelpSecure($requestStatusLabel); ?></div>
                            <?php } else { ?>
                                <button type="button" class="i_nex_btn agency_join_btn transition creatorAgencyRequest" data-agency="<?php echo iN_HelpSecure($agencyId); ?>">
                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['agency_join_request']); ?>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
        <div class="agency_empty_state<?php echo !empty($agencies) ? ' is-hidden' : ''; ?>" role="status" aria-live="polite">
            <div class="agency_empty_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('195')); ?></div>
            <div class="agency_empty_title"><?php echo iN_HelpSecure($LANG['agency_no_agencies']); ?></div>
            <div class="agency_empty_note"><?php echo iN_HelpSecure($LANG['agency_directory_empty_note']); ?></div>
        </div>
    </div>
</div>
</body>
</html>
