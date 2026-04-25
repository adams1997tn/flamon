<?php
$canAccessAgencyOnboarding = $iN->iN_CanUserAccessAgencyOnboarding(
    $userID,
    $userSignupIntent ?? 'user',
    $registrationRoleMode ?? 'legacy'
);
if (!$canAccessAgencyOnboarding) {
    header('Location: ' . route_url('404'));
    exit;
}
if (!isset($agencyModuleStatus) || $agencyModuleStatus !== 'yes') {
    header('Location: ' . route_url('404'));
    exit;
}

$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '');

$membership = DB::one(
    "SELECT AM.*, A.agency_name, A.agency_status, A.owner_iuid_fk, A.agency_fee, U.i_username, U.i_user_fullname
     FROM i_agency_members AM
     INNER JOIN i_agencies A ON A.agency_id = AM.agency_id_fk
     LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk
     WHERE AM.creator_iuid_fk = ?
     LIMIT 1",
    [$userID]
);
$ownedAgency = DB::one(
    "SELECT A.*, U.i_username, U.i_user_fullname
     FROM i_agencies A
     LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk
     WHERE A.owner_iuid_fk = ?
     LIMIT 1",
    [$userID]
);
$ownerAgencyId = !empty($ownedAgency) ? (int)$ownedAgency['agency_id'] : 0;
$agencySocialProfiles = [];
$agencyBoostMembers = [];
$agencyBoostByCreator = [];
$agencyBoostDefaultDays = (int)$iN->iN_GetSetting('agency_boost_default_days', 7);
if ($agencyBoostDefaultDays < 1) {
    $agencyBoostDefaultDays = 7;
}
if ($agencyBoostDefaultDays > 365) {
    $agencyBoostDefaultDays = 365;
}
$agencyBoostMaxActive = (int)$iN->iN_GetSetting('agency_boost_max_active', 3);
if ($agencyBoostMaxActive < 0) {
    $agencyBoostMaxActive = 0;
}
$agencyBoostPriceSetting = (float)$iN->iN_GetSetting('agency_boost_price', isset($agencyBoostPrice) ? $agencyBoostPrice : 0.0);
if ($agencyBoostPriceSetting < 0) {
    $agencyBoostPriceSetting = 0.0;
}
$agencyBoostPointPriceSetting = (int)$iN->iN_GetSetting('agency_boost_point_price', isset($agencyBoostPointPrice) ? $agencyBoostPointPrice : 0);
if ($agencyBoostPointPriceSetting < 0) {
    $agencyBoostPointPriceSetting = 0;
}
$agencyProfileAboutEnabled = (isset($agencyProfileAboutStatus) ? $agencyProfileAboutStatus : 'yes') === 'yes';
$agencyProfileServicesEnabled = (isset($agencyProfileServicesStatus) ? $agencyProfileServicesStatus : 'yes') === 'yes';
$agencyProfileLogoEnabled = (isset($agencyProfileLogoStatus) ? $agencyProfileLogoStatus : 'yes') === 'yes';
$agencyProfileCoverEnabled = (isset($agencyProfileCoverStatus) ? $agencyProfileCoverStatus : 'yes') === 'yes';
$agencyProfileSocialEnabled = (isset($agencyProfileSocialStatus) ? $agencyProfileSocialStatus : 'yes') === 'yes';
$agencyProfileAnyEnabled = $agencyProfileAboutEnabled || $agencyProfileServicesEnabled || $agencyProfileLogoEnabled || $agencyProfileCoverEnabled || $agencyProfileSocialEnabled;
$agencyLogoUrl = '';
$agencyCoverUrl = '';
if (!empty($ownedAgency)) {
    $agencyLogoUrl = $iN->iN_AgencyLogoUrl($ownedAgency, $base_url);
    $agencyCoverUrl = $iN->iN_AgencyCoverUrl($ownedAgency, $base_url);
}
$agencyBoostActiveCount = 0;

$agencyCreateRequest = DB::one(
    "SELECT * FROM i_agency_create_requests WHERE owner_iuid_fk = ? ORDER BY acr_id DESC LIMIT 1",
    [$userID]
);
$agencyCreateStatus = (string)($agencyCreateRequest['status'] ?? '');
$hasPendingCreate = $agencyCreateStatus === 'pending';

$requests = DB::all(
    "SELECT R.*, A.agency_name, A.agency_status, A.owner_iuid_fk, U.i_username, U.i_user_fullname
     FROM i_agency_requests R
     LEFT JOIN i_agencies A ON A.agency_id = R.agency_id_fk
     LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk
     WHERE R.creator_iuid_fk = ?
     ORDER BY R.ar_id DESC",
    [$userID]
);

$invites = [];
$myRequests = [];
$requestStatuses = [];
$latestMyRequest = null;

if (!empty($requests)) {
    foreach ($requests as $request) {
        $agencyId = (int)($request['agency_id_fk'] ?? 0);
        $requestedBy = (string)($request['requested_by'] ?? '');
        if ($agencyId > 0 && !isset($requestStatuses[$agencyId])) {
            $requestStatuses[$agencyId] = (string)($request['status'] ?? '');
        }
        if ($requestedBy === 'agency') {
            $invites[] = $request;
        } elseif ($requestedBy === 'creator') {
            $myRequests[] = $request;
            if ($latestMyRequest === null) {
                $latestMyRequest = $request;
            }
        }
    }
}

$memberAgency = !empty($membership) ? $membership : $ownedAgency;
$hasMembership = !empty($memberAgency);
$hasAgencyLock = $hasMembership || $hasPendingCreate;
$noticeMessage = '';
if (!empty($latestMyRequest)) {
    $latestStatus = (string)($latestMyRequest['status'] ?? '');
    if ($latestStatus === 'pending') {
        $noticeMessage = $LANG['agency_request_received_notice'] ?? '';
    } elseif ($latestStatus === 'approved') {
        $noticeMessage = $LANG['agency_request_approved_notice'] ?? '';
    } elseif ($latestStatus === 'rejected') {
        $noticeMessage = $LANG['agency_request_rejected_notice'] ?? '';
    }
}

$adminFeeRate = isset($adminFee) ? (float)$adminFee : 0.0;
$exampleGross = 100.0;
$exampleAgencyRate = $hasMembership ? (float)($memberAgency['agency_fee'] ?? 0) : 15.0;
$exampleAdminEarning = round(($adminFeeRate * $exampleGross) / 100, 2);
$exampleAgencyEarning = round(($exampleAgencyRate * $exampleGross) / 100, 2);
$exampleNet = $exampleGross - $exampleAdminEarning - $exampleAgencyEarning;
if ($exampleNet < 0) {
    $exampleNet = 0.0;
}
$exampleGrossFormatted = formatCurrency($exampleGross, $defaultCurrency);
$exampleNetFormatted = formatCurrency($exampleNet, $defaultCurrency);
$exampleAdminRateLabel = number_format($adminFeeRate, 2, '.', '');
$exampleAgencyRateLabel = number_format($exampleAgencyRate, 2, '.', '');
$memberAgencyFeeLabel = $hasMembership ? number_format((float)($memberAgency['agency_fee'] ?? 0), 2, '.', '') : '';

$agencies = [];
$totalPages = 0;
$pagep = 1;
if (!empty($ownedAgency)) {
    $ownerRequests = DB::all(
        "SELECT R.*, U.i_username, U.i_user_fullname
         FROM i_agency_requests R
         LEFT JOIN i_users U ON U.iuid = R.creator_iuid_fk
         WHERE R.agency_id_fk = ? AND R.requested_by = 'creator'
         ORDER BY R.ar_id DESC",
        [(int)$ownedAgency['agency_id']]
    );
}
$nowTime = time();
if (!empty($ownedAgency)) {
    if ($agencyProfileSocialEnabled) {
        $agencySocialProfiles = $iN->iN_GetAgencySocialLinksForEdit($ownerAgencyId);
    }
    $iN->iN_ExpireAgencyBoosts($ownerAgencyId);
    $agencyBoostActiveCount = (int) DB::col(
        "SELECT COUNT(*) FROM i_agency_boosts WHERE agency_id_fk = ? AND status = 'active' AND end_at > ?",
        [$ownerAgencyId, $nowTime]
    );
    $agencyBoostMembers = DB::all(
        "SELECT M.creator_iuid_fk, M.status, U.i_username, U.i_user_fullname
         FROM i_agency_members M
         INNER JOIN i_users U ON U.iuid = M.creator_iuid_fk AND U.uStatus IN('1','3')
         WHERE M.agency_id_fk = ?
         ORDER BY M.am_id DESC",
        [$ownerAgencyId]
    );
    $agencyBoostRows = DB::all(
        "SELECT B.*, COUNT(C.abc_id) AS click_count
         FROM i_agency_boosts B
         LEFT JOIN i_agency_boost_clicks C ON C.ab_id_fk = B.ab_id
         WHERE B.agency_id_fk = ?
         GROUP BY B.ab_id
         ORDER BY B.ab_id DESC",
        [$ownerAgencyId]
    );
    if (!empty($agencyBoostRows)) {
        foreach ($agencyBoostRows as $boostRow) {
            $creatorId = (int)($boostRow['creator_iuid_fk'] ?? 0);
            if ($creatorId > 0 && !isset($agencyBoostByCreator[$creatorId])) {
                $agencyBoostByCreator[$creatorId] = $boostRow;
            }
        }
    }
}
$agencyReportTotal = 0.0;
$agencyReportTransactions = [];
if (!empty($ownedAgency)) {
    $paymentTotals = DB::one(
        "SELECT SUM(agency_earning) AS total_agency
         FROM i_user_payments
         WHERE agency_id_fk = ? AND payment_status = 'ok' AND payment_type <> 'point'",
        [$ownerAgencyId]
    );
    $subscriptionTotals = DB::one(
        "SELECT SUM(agency_earning) AS total_agency
         FROM i_user_subscriptions
         WHERE agency_id_fk = ? AND status IN('active','inactive') AND in_status IN('1','0') AND finished IN('0','1')
           AND subscription_scope IN ('profile','community')",
        [$ownerAgencyId]
    );
    $agencyReportTotal = (float)($paymentTotals['total_agency'] ?? 0) + (float)($subscriptionTotals['total_agency'] ?? 0);
    $agencyReportTransactions = DB::all(
        "(SELECT 'payment' AS record_type,
                 P.payment_id AS record_id,
                 P.payed_iuid_fk AS creator_id,
                 P.payment_type AS item_type,
                 P.payment_time AS created_ts,
                 P.admin_earning,
                 P.agency_earning,
                 P.user_earning AS creator_net,
                 P.currency AS currency,
                 U.i_username,
                 U.i_user_fullname
          FROM i_user_payments P
          INNER JOIN i_users U ON U.iuid = P.payed_iuid_fk AND U.uStatus IN('1','3')
          WHERE P.agency_id_fk = ? AND P.payment_status = 'ok' AND P.payment_type <> 'point')
         UNION ALL
         (SELECT 'subscription' AS record_type,
                 S.subscription_id AS record_id,
                 S.subscribed_iuid_fk AS creator_id,
                 S.subscription_scope AS item_type,
                 UNIX_TIMESTAMP(S.created) AS created_ts,
                 S.admin_earning,
                 S.agency_earning,
                 S.user_net_earning AS creator_net,
                 S.plan_amount_currency AS currency,
                 U.i_username,
                 U.i_user_fullname
          FROM i_user_subscriptions S
          INNER JOIN i_users U ON U.iuid = S.subscribed_iuid_fk AND U.uStatus IN('1','3')
          WHERE S.agency_id_fk = ? AND S.status IN('active','inactive') AND S.in_status IN('1','0') AND S.finished IN('0','1')
            AND S.subscription_scope IN ('profile','community'))
         ORDER BY created_ts DESC
         LIMIT 20",
        [$ownerAgencyId, $ownerAgencyId]
    );
}
if (!$hasAgencyLock) {
    $perPage = $paginationLimit > 0 ? (int)$paginationLimit : 10;
    $totalAgencies = (int)DB::col("SELECT COUNT(*) FROM i_agencies WHERE agency_status = 'active'");
    $totalPages = ($perPage > 0 && $totalAgencies > 0) ? (int)ceil($totalAgencies / $perPage) : 0;
    if (isset($_GET["page-id"])) {
        $pagep = (int)$_GET["page-id"];
        if ($pagep <= 0) {
            $pagep = 1;
        }
    }
    $startFrom = ($pagep - 1) * $perPage;
    $agencies = DB::all(
        "SELECT A.*, U.i_username, U.i_user_fullname FROM i_agencies A
         LEFT JOIN i_users U ON U.iuid = A.owner_iuid_fk
         WHERE A.agency_status = 'active'
         ORDER BY A.agency_id DESC
         LIMIT $startFrom, $perPage"
    );
}
?>
<div class="settings_main_wrapper agencies_settings">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_">
         <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('195'));?>
         <?php echo iN_HelpSecure($LANG['agency_module_title']);?>
       </div>
    </div>
    <div class="i_settings_wrapper_items">
      <?php if ($noticeMessage !== '') { ?>
        <div class="i_settings_wrapper_item">
          <div class="box_not"><?php echo iN_HelpSecure($noticeMessage); ?></div>
        </div>
      <?php } ?>

      <input type="hidden" id="creatorAgencyCsrf" value="<?php echo iN_HelpSecure($csrfToken); ?>">

      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_onboarding_title']);?></div>
        <div class="i_settings_item_title_for">
          <ul class="i_block_not_list">
            <li><?php echo iN_HelpSecure($LANG['agency_onboarding_bullet_growth']);?></li>
            <li><?php echo iN_HelpSecure($LANG['agency_onboarding_bullet_split']);?></li>
            <li><?php echo iN_HelpSecure($LANG['agency_onboarding_bullet_wallet']);?></li>
            <li><?php echo iN_HelpSecure($LANG['agency_onboarding_bullet_breakdown']);?></li>
            <li><?php echo iN_HelpSecure($LANG['agency_onboarding_bullet_change']);?></li>
          </ul>
          <?php if ($hasMembership) { ?>
            <div class="box_not"><?php echo iN_HelpSecure(sprintf($LANG['agency_onboarding_fee_current'], $memberAgencyFeeLabel));?></div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_onboarding_fee_future']);?></div>
          <?php } ?>
          <div class="box_not"><?php echo iN_HelpSecure(sprintf($LANG['agency_onboarding_example'], $exampleGrossFormatted, $exampleAdminRateLabel, $exampleAgencyRateLabel, $exampleNetFormatted));?></div>
        </div>
      </div>

      <?php if (!$hasMembership) { ?>
      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_create_request_title']);?></div>
        <div class="i_settings_item_title_for">
          <?php if ($hasPendingCreate) { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_create_request_pending']);?></div>
          <?php } else { ?>
            <?php if ($agencyCreateStatus === 'rejected') { ?>
              <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_create_request_rejected']);?></div>
            <?php } ?>
            <form id="creatorAgencyCreateForm" method="post">
              <input type="hidden" name="f" value="creatorAgencyCreate">
              <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
              <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                  <?php echo iN_HelpSecure($LANG['agency_name']); ?>
                </div>
                <div class="irow_box_right">
                  <input type="text" name="agency_name" class="i_input flex_" placeholder="<?php echo iN_HelpSecure($LANG['agency_name']); ?>">
                </div>
              </div>
              <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_">
                  <?php echo iN_HelpSecure($LANG['agency_fee_rate']); ?>
                </div>
                <div class="irow_box_right">
                  <input type="number" name="agency_fee" class="i_input flex_" min="0" max="100" step="0.01" placeholder="0.00">
                  <div class="rec_not"><?php echo iN_HelpSecure($LANG['agency_fee_note']); ?></div>
                </div>
              </div>
              <div class="i_general_row_box_item flex_ tabing_non_justify">
                <div class="irow_box_left tabing flex_"></div>
                <div class="irow_box_right">
                  <button type="submit" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['agency_create_submit']); ?></button>
                </div>
              </div>
            </form>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_membership_status']);?></div>
        <div class="i_settings_item_title_for">
          <?php if ($hasMembership) {
              $agencyName = $memberAgency['agency_name'] ?? '';
              $agencyName = $agencyName !== '' ? $agencyName : ($LANG['agency_not_found'] ?? '');
              $memberAgencyId = (int)($memberAgency['agency_id_fk'] ?? $memberAgency['agency_id'] ?? 0);
              $memberAgencyUrl = $memberAgencyId > 0 ? ($base_url . 'agency/' . $memberAgencyId) : '';
              $agencyStatus = $memberAgency['agency_status'] ?? '';
              $ownerId = (int)($memberAgency['owner_iuid_fk'] ?? 0);
              $ownerName = $memberAgency['i_user_fullname'] ?? $memberAgency['i_username'] ?? '';
              $ownerLabel = $ownerName !== '' ? $ownerName . ' (#' . $ownerId . ')' : '#' . $ownerId;
              $statusLabel = $agencyStatus === 'active' ? $LANG['agency_status_active'] : $LANG['agency_status_inactive'];
          ?>
            <div class="i_overflow_x_auto agency_boost_scroll agency_table_wrap">
              <div class="agency_table_scroll">
                <div class="i_tab_container agency_boost_table agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['agency_name']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_owner']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_fee_rate']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_status']);?></div>
                </div>
                <div class="i_tab_list_item_container">
                <div class="i_tab_list_item flex_">
                  <div class="tab_detail_item tab_detail_item_maxwidth">
                      <div class="flex_ tabing_non_justify truncated">
                        <?php if ($memberAgencyUrl !== '') { ?>
                          <a href="<?php echo iN_HelpSecure($memberAgencyUrl);?>"><?php echo iN_HelpSecure($agencyName);?></a>
                        <?php } else { ?>
                          <?php echo iN_HelpSecure($agencyName);?>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($ownerLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure(number_format((float)($memberAgency['agency_fee'] ?? 0), 2, '.', '') . '%');?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel);?></div>
                    </div>
                  </div>
                </div>
                </div>
              </div>
            </div>
            <?php if ($agencyStatus !== 'active') { ?>
              <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_inactive']);?></div>
            <?php } ?>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_membership_none']);?></div>
          <?php } ?>
        </div>
      </div>

      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_invites_title']);?></div>
        <div class="i_settings_item_title_for">
          <?php if (!empty($invites)) { ?>
            <div class="i_overflow_x_auto agency_table_wrap">
              <div class="agency_table_scroll">
                <div class="i_tab_container agency_boost_table agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['agency_name']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_owner']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_request_status']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['actions']);?></div>
                </div>
                <div class="i_tab_list_item_container">
                <?php foreach ($invites as $invite) {
                    $agencyId = (int)($invite['agency_id_fk'] ?? 0);
                    $agencyName = $invite['agency_name'] ?? '';
                    $agencyName = $agencyName !== '' ? $agencyName : ($LANG['agency_not_found'] ?? '');
                    $agencyUrl = $agencyId > 0 ? ($base_url . 'agency/' . $agencyId) : '';
                    $agencyStatus = $invite['agency_status'] ?? '';
                    $ownerId = (int)($invite['owner_iuid_fk'] ?? 0);
                    $ownerName = $invite['i_user_fullname'] ?? $invite['i_username'] ?? '';
                    $ownerLabel = $ownerName !== '' ? $ownerName . ' (#' . $ownerId . ')' : '#' . $ownerId;
                    $requestId = (int)($invite['ar_id'] ?? 0);
                    $requestStatus = (string)($invite['status'] ?? '');
                    $statusLabel = $LANG['agency_request_status_' . $requestStatus] ?? $requestStatus;
                    $agencyActive = $agencyStatus === 'active';
                ?>
                  <div class="i_tab_list_item flex_">
                    <div class="tab_detail_item tab_detail_item_maxwidth">
                      <div class="flex_ tabing_non_justify truncated">
                        <?php if ($agencyUrl !== '') { ?>
                          <a href="<?php echo iN_HelpSecure($agencyUrl);?>"><?php echo iN_HelpSecure($agencyName);?></a>
                        <?php } else { ?>
                          <?php echo iN_HelpSecure($agencyName);?>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($ownerLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="tabing_non_justify flex_ agency_actions">
                        <?php if ($requestStatus === 'pending') { ?>
                          <?php if ($hasMembership) { ?>
                            <span class="box_not"><?php echo iN_HelpSecure($LANG['agency_already_member']);?></span>
                          <?php } elseif (!$agencyActive) { ?>
                            <span class="box_not"><?php echo iN_HelpSecure($LANG['agency_inactive']);?></span>
                          <?php } else { ?>
                            <div class="seePost c2 border_one transition tabing flex_ creatorAgencyInviteAction" data-request="<?php echo iN_HelpSecure($requestId); ?>" data-status="approved">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')) . iN_HelpSecure($LANG['approve']); ?>
                            </div>
                            <div class="delu border_one transition tabing flex_ creatorAgencyInviteAction" data-request="<?php echo iN_HelpSecure($requestId); ?>" data-status="rejected">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['reject']); ?>
                            </div>
                          <?php } ?>
                        <?php } else { ?>
                          <span class="box_not"><?php echo iN_HelpSecure($LANG['no_actions']);?></span>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                </div>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_no_requests']);?></div>
          <?php } ?>
        </div>
      </div>

      <?php if (!empty($ownedAgency)) { ?>
      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_requests']);?></div>
        <div class="i_settings_item_title_for">
          <?php if (!empty($ownerRequests)) { ?>
            <div class="i_overflow_x_auto agency_table_wrap">
              <div class="agency_table_scroll">
                <div class="i_tab_container agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['agency_requested_by']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_request_status']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['createdat']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['actions']);?></div>
                </div>
                <div class="i_tab_list_item_container">
                <?php foreach ($ownerRequests as $ownerRequest) {
                    $creatorId = (int)($ownerRequest['creator_iuid_fk'] ?? 0);
                    $creatorName = $ownerRequest['i_user_fullname'] ?? $ownerRequest['i_username'] ?? '';
                    $creatorLabel = $creatorName !== '' ? $creatorName . ' (#' . $creatorId . ')' : '#' . $creatorId;
                    $requestId = (int)($ownerRequest['ar_id'] ?? 0);
                    $requestStatus = (string)($ownerRequest['status'] ?? '');
                    $statusLabel = $LANG['agency_request_status_' . $requestStatus] ?? $requestStatus;
                    $createdAt = (int)($ownerRequest['created_at'] ?? 0);
                    $createdLabel = $createdAt > 0 ? date('d/m/Y H:i', $createdAt) : '-';
                    $agencyActive = ($ownedAgency['agency_status'] ?? '') === 'active';
                ?>
                  <div class="i_tab_list_item flex_">
                    <div class="tab_detail_item tab_detail_item_maxwidth">
                      <div class="flex_ tabing_non_justify truncated"><?php echo iN_HelpSecure($creatorLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($createdLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="tabing_non_justify flex_ agency_actions">
                        <?php if ($requestStatus === 'pending') { ?>
                          <?php if (!$agencyActive) { ?>
                            <span class="box_not"><?php echo iN_HelpSecure($LANG['agency_inactive']);?></span>
                          <?php } else { ?>
                            <div class="seePost c2 border_one transition tabing flex_ agencyOwnerRequestAction" data-request="<?php echo iN_HelpSecure($requestId); ?>" data-status="approved">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69')) . iN_HelpSecure($LANG['approve']); ?>
                            </div>
                            <div class="delu border_one transition tabing flex_ agencyOwnerRequestAction" data-request="<?php echo iN_HelpSecure($requestId); ?>" data-status="rejected">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['reject']); ?>
                            </div>
                          <?php } ?>
                        <?php } else { ?>
                          <span class="box_not"><?php echo iN_HelpSecure($LANG['no_actions']);?></span>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                </div>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_no_requests']);?></div>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <?php if (!empty($ownedAgency)) { ?>
      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_profile']);?></div>
        <div class="i_settings_item_title_for">
          <?php if ($agencyProfileAnyEnabled) { ?>
          <form id="agencyProfileForm" class="agency_profile_form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="f" value="agencyProfileUpdate">
            <input type="hidden" name="agency_id" value="<?php echo iN_HelpSecure($ownerAgencyId); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo iN_HelpSecure($csrfToken); ?>">
            <?php if ($agencyProfileAboutEnabled) { ?>
            <div class="i_general_row_box_item flex_ tabing_non_justify agency_profile_row">
              <div class="irow_box_left tabing flex_">
                <?php echo iN_HelpSecure($LANG['agency_about']); ?>
              </div>
              <div class="irow_box_right">
                <textarea name="agency_about" class="i_input flex_" rows="4" placeholder="<?php echo iN_HelpSecure($LANG['agency_about']); ?>"><?php echo iN_HelpSecure($ownedAgency['agency_about'] ?? ''); ?></textarea>
              </div>
            </div>
            <?php } ?>
            <?php if ($agencyProfileServicesEnabled) { ?>
            <div class="i_general_row_box_item flex_ tabing_non_justify agency_profile_row">
              <div class="irow_box_left tabing flex_">
                <?php echo iN_HelpSecure($LANG['agency_services']); ?>
              </div>
              <div class="irow_box_right">
                <textarea name="agency_services" class="i_input flex_" rows="4" placeholder="<?php echo iN_HelpSecure($LANG['agency_services']); ?>"><?php echo iN_HelpSecure($ownedAgency['agency_services'] ?? ''); ?></textarea>
              </div>
            </div>
            <?php } ?>
            <?php if ($agencyProfileLogoEnabled) { ?>
            <div class="i_general_row_box_item flex_ tabing_non_justify agency_profile_row">
              <div class="irow_box_left tabing flex_">
                <?php echo iN_HelpSecure($LANG['agency_logo']); ?>
              </div>
              <div class="irow_box_right">
                <div class="agency_media_row">
                  <?php if ($agencyLogoUrl !== '') { ?>
                    <div class="agency_media_preview t_owner_avatar border_two tabing flex_">
                      <img src="<?php echo iN_HelpSecure($agencyLogoUrl); ?>" alt="<?php echo iN_HelpSecure($LANG['agency_logo']); ?>">
                    </div>
                  <?php } ?>
                  <div class="agency_media_input">
                    <input type="file" name="agency_logo" class="i_input flex_" accept="image/*">
                  </div>
                </div>
              </div>
            </div>
            <?php } ?>
            <?php if ($agencyProfileCoverEnabled) { ?>
            <div class="i_general_row_box_item flex_ tabing_non_justify agency_profile_row">
              <div class="irow_box_left tabing flex_">
                <?php echo iN_HelpSecure($LANG['agency_cover']); ?>
              </div>
              <div class="irow_box_right">
                <div class="agency_media_row">
                  <?php if ($agencyCoverUrl !== '') { ?>
                    <div class="agency_media_preview t_owner_avatar border_two tabing flex_">
                      <img src="<?php echo iN_HelpSecure($agencyCoverUrl); ?>" alt="<?php echo iN_HelpSecure($LANG['agency_cover']); ?>">
                    </div>
                  <?php } ?>
                  <div class="agency_media_input">
                    <input type="file" name="agency_cover" class="i_input flex_" accept="image/*">
                  </div>
                </div>
              </div>
            </div>
            <?php } ?>
            <?php if ($agencyProfileSocialEnabled && !empty($agencySocialProfiles)) { ?>
              <div class="agency_profile_section_title"><?php echo iN_HelpSecure($LANG['social_links']); ?></div>
              <div class="agency_profile_socials">
                <?php foreach ($agencySocialProfiles as $socialProfile) {
                    $socialId = (int)($socialProfile['id'] ?? 0);
                    $socialKey = trim((string)($socialProfile['skey'] ?? ''));
                    $labelBase = $socialKey !== '' ? $socialKey : (string)($socialProfile['place_holder'] ?? '');
                    $labelBase = str_replace(['_', '-'], ' ', $labelBase);
                    $socialLabel = $labelBase !== '' ? ucfirst($labelBase) : ($LANG['social_links'] ?? 'Social link');
                    $socialValue = $socialProfile['s_link'] ?? '';
                    $socialPlaceholder = $socialProfile['place_holder'] ?? $socialLabel;
                ?>
                  <div class="i_general_row_box_item flex_ tabing_non_justify agency_profile_row agency_profile_social_row">
                    <div class="irow_box_left tabing flex_">
                      <?php echo iN_HelpSecure($socialLabel); ?>
                    </div>
                    <div class="irow_box_right">
                      <input type="text" name="social_link[<?php echo iN_HelpSecure($socialId); ?>]" class="i_input flex_" value="<?php echo iN_HelpSecure($socialValue); ?>" placeholder="<?php echo iN_HelpSecure($socialPlaceholder); ?>">
                    </div>
                  </div>
                <?php } ?>
              </div>
            <?php } ?>
            <div class="i_general_row_box_item flex_ tabing_non_justify agency_profile_row agency_profile_action">
              <div class="irow_box_left tabing flex_"></div>
              <div class="irow_box_right">
                <button type="submit" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['save_changes']); ?></button>
              </div>
            </div>
          </form>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_profile_fields_disabled']); ?></div>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <?php if (!empty($ownedAgency)) { ?>
      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_boosted_creators_manage']);?></div>
        <div class="i_settings_item_title_for">
          <?php if ($agencyBoostMaxActive > 0) { ?>
            <div class="box_not"><?php echo iN_HelpSecure(sprintf($LANG['agency_boost_active_limit'], $agencyBoostActiveCount, $agencyBoostMaxActive)); ?></div>
          <?php } ?>
          <?php if (!empty($agencyBoostMembers)) { ?>
            <div class="i_overflow_x_auto agency_boosted_table_wrap agency_table_wrap">
              <div class="agency_boosted_table_scroll agency_table_scroll">
                <div class="i_tab_container agency_boosted_table agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['creator']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['status']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['clicks']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_boost_price_display']);?></div>
                  <div class="tab_item"><span class="agency_boost_header_label"><?php echo iN_HelpSecure($LANG['duration_days']);?></span></div>
                  <div class="tab_item"><span class="agency_boost_header_label"><?php echo iN_HelpSecure($LANG['agency_boost_payment_method']);?></span></div>
                  <div class="tab_item"><span class="agency_boost_header_label"><?php echo iN_HelpSecure($LANG['actions']);?></span></div>
                </div>
                <div class="i_tab_list_item_container">
                <?php foreach ($agencyBoostMembers as $member) {
                    $creatorId = (int)($member['creator_iuid_fk'] ?? 0);
                    $creatorName = $member['i_user_fullname'] ?? $member['i_username'] ?? '';
                    $creatorLabel = $creatorName !== '' ? $creatorName . ' (#' . $creatorId . ')' : '#' . $creatorId;
                    $memberStatus = (string)($member['status'] ?? 'active');
                    $isMemberActive = $memberStatus === 'active';
                    $boostData = $agencyBoostByCreator[$creatorId] ?? null;
                    $boostStatus = $boostData ? (string)($boostData['status'] ?? '') : 'none';
                    $boostEndAt = $boostData ? (int)($boostData['end_at'] ?? 0) : 0;
                    $isActive = ($boostStatus === 'active' && $boostEndAt > $nowTime);
                    if ($boostStatus === 'active' && !$isActive) {
                        $boostStatus = 'expired';
                    }
                    $statusLabel = $LANG['agency_boost_status_' . $boostStatus] ?? $boostStatus;
                    $clickCount = $boostData ? (int)($boostData['click_count'] ?? 0) : 0;
                    $boostId = $boostData ? (int)($boostData['ab_id'] ?? 0) : 0;
                    $limitReached = ($agencyBoostMaxActive > 0 && $agencyBoostActiveCount >= $agencyBoostMaxActive);
                    $canCreate = !$isActive && !$limitReached && $isMemberActive;
                    $pointsInsufficient = $agencyBoostPointPriceSetting > 0 && isset($userCurrentPoints) && (float)$userCurrentPoints < $agencyBoostPointPriceSetting;
                    $defaultMethod = (!$pointsInsufficient && $agencyBoostPointPriceSetting > 0) ? 'points' : 'card';
                    $priceDisplay = formatCurrency($agencyBoostPriceSetting, $defaultCurrency);
                ?>
                  <div class="i_tab_list_item flex_ agency_boost_row" data-can-create="<?php echo $canCreate ? '1' : '0'; ?>" data-points-required="<?php echo iN_HelpSecure((string)$agencyBoostPointPriceSetting); ?>" data-points-available="<?php echo iN_HelpSecure(isset($userCurrentPoints) ? (string)$userCurrentPoints : '0'); ?>">
                    <div class="tab_detail_item tab_detail_item_maxwidth">
                      <div class="flex_ tabing_non_justify truncated"><?php echo iN_HelpSecure($creatorLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($clickCount);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify column">
                        <span><?php echo iN_HelpSecure($priceDisplay);?></span>
                        <span><?php echo iN_HelpSecure(number_format($agencyBoostPointPriceSetting)) . ' ' . iN_HelpSecure($LANG['agency_boost_points_display']);?></span>
                      </div>
                    </div>
                    <div class="tab_detail_item">
                      <input type="number" class="i_input flex_ agencyBoostDuration" min="1" max="365" step="1" value="<?php echo iN_HelpSecure((string)$agencyBoostDefaultDays); ?>" <?php echo $canCreate ? '' : 'disabled="disabled"'; ?>>
                    </div>
                    <div class="tab_detail_item">
                      <select class="i_input flex_ agencyBoostPaymentMethod" <?php echo $canCreate ? '' : 'disabled="disabled"'; ?>>
                        <option value="points" <?php echo $defaultMethod === 'points' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['pay_with_points']);?></option>
                        <option value="card" <?php echo $defaultMethod === 'card' ? 'selected="selected"' : ''; ?>><?php echo iN_HelpSecure($LANG['pay_with_card_bank']);?></option>
                      </select>
                    </div>
                    <div class="tab_detail_item">
                      <div class="tabing_non_justify flex_ agency_actions">
                        <?php if ($isActive && $boostId > 0) { ?>
                          <div class="delu border_one transition tabing flex_ agencyBoostDisable" data-boost="<?php echo iN_HelpSecure($boostId); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['disable']); ?>
                          </div>
                        <?php } else { ?>
                          <div class="seePost c2 border_one transition tabing flex_ agencyBoostCreate<?php echo $canCreate ? '' : ' disabled'; ?>" data-agency="<?php echo iN_HelpSecure($ownerAgencyId); ?>" data-creator="<?php echo iN_HelpSecure($creatorId); ?>" aria-disabled="<?php echo $canCreate ? 'false' : 'true'; ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['agency_boost_create']); ?>
                          </div>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                </div>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_no_members']);?></div>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <?php if (!empty($ownedAgency)) { ?>
      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_earnings_report']);?></div>
        <div class="i_settings_item_title_for">
          <div class="i_overflow_x_auto agency_table_wrap">
            <div class="agency_table_scroll">
              <div class="i_tab_container agency_table">
              <div class="i_tab_header flex_">
                <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_total_earnings']);?></div>
              </div>
              <div class="i_tab_list_item_container">
                <div class="i_tab_list_item flex_">
                  <div class="tab_detail_item"><?php echo iN_HelpSecure(formatCurrency($agencyReportTotal, $defaultCurrency));?></div>
                </div>
              </div>
              </div>
            </div>
          </div>
          <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['agency_recent_transactions']);?></div>
          <?php if (!empty($agencyReportTransactions)) { ?>
            <div class="i_overflow_x_auto agency_table_wrap">
              <div class="agency_table_scroll">
                <div class="i_tab_container agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['creator']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['transaction_type']);?></div>
                  <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['gross_amount']);?></div>
                  <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['admin_cut']);?></div>
                  <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['agency_cut']);?></div>
                  <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['creator_net']);?></div>
                  <div class="tab_item item_mobile"><?php echo iN_HelpSecure($LANG['createdat']);?></div>
                </div>
                <div class="i_tab_list_item_container">
                  <?php foreach ($agencyReportTransactions as $transaction) {
                      $creatorId = (int)($transaction['creator_id'] ?? 0);
                      $creatorName = $transaction['i_user_fullname'] ?? $transaction['i_username'] ?? '';
                      $creatorLabel = $creatorName !== '' ? $creatorName . ' (#' . $creatorId . ')' : '#' . $creatorId;
                      $recordType = $transaction['record_type'] ?? 'payment';
                      $typeLabel = $recordType === 'subscription' ? $LANG['transaction_type_subscription'] : $LANG['transaction_type_payment'];
                      $adminEarning = (float)($transaction['admin_earning'] ?? 0);
                      $agencyEarning = (float)($transaction['agency_earning'] ?? 0);
                      $creatorNet = (float)($transaction['creator_net'] ?? 0);
                      $gross = $adminEarning + $agencyEarning + $creatorNet;
                      $currency = $transaction['currency'] ?? $defaultCurrency;
                      $createdTs = (int)($transaction['created_ts'] ?? 0);
                      $createdLabel = $createdTs > 0 ? date('d/m/Y H:i', $createdTs) : '-';
                      $creatorProfile = $transaction['i_username'] ?? '';
                  ?>
                  <div class="i_tab_list_item flex_">
                    <div class="tab_detail_item tab_detail_item_maxwidth">
                      <?php if ($creatorProfile !== '') { ?>
                        <a href="<?php echo iN_HelpSecure($base_url . $creatorProfile);?>">
                          <div class="flex_ tabing_non_justify truncated"><?php echo iN_HelpSecure($creatorLabel);?></div>
                        </a>
                      <?php } else { ?>
                        <div class="flex_ tabing_non_justify truncated"><?php echo iN_HelpSecure($creatorLabel);?></div>
                      <?php } ?>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($typeLabel);?></div>
                    </div>
                    <div class="tab_detail_item item_mobile"><?php echo iN_HelpSecure(formatCurrency($gross, $currency));?></div>
                    <div class="tab_detail_item item_mobile"><?php echo iN_HelpSecure(formatCurrency($adminEarning, $currency));?></div>
                    <div class="tab_detail_item item_mobile"><?php echo iN_HelpSecure(formatCurrency($agencyEarning, $currency));?></div>
                    <div class="tab_detail_item item_mobile"><?php echo iN_HelpSecure(formatCurrency($creatorNet, $currency));?></div>
                    <div class="tab_detail_item item_mobile"><?php echo iN_HelpSecure($createdLabel);?></div>
                  </div>
                  <?php } ?>
                </div>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_no_earnings']);?></div>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_my_requests_title']);?></div>
        <div class="i_settings_item_title_for">
          <?php if (!empty($myRequests)) { ?>
            <div class="i_overflow_x_auto agency_table_wrap">
              <div class="agency_table_scroll">
                <div class="i_tab_container agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['agency_name']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_request_status']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['createdat']);?></div>
                </div>
                <div class="i_tab_list_item_container">
                <?php foreach ($myRequests as $request) {
                    $requestAgencyId = (int)($request['agency_id_fk'] ?? 0);
                    $agencyName = $request['agency_name'] ?? '';
                    $agencyName = $agencyName !== '' ? $agencyName : ($LANG['agency_not_found'] ?? '');
                    $requestAgencyUrl = $requestAgencyId > 0 ? ($base_url . 'agency/' . $requestAgencyId) : '';
                    $requestStatus = (string)($request['status'] ?? '');
                    $statusLabel = $LANG['agency_request_status_' . $requestStatus] ?? $requestStatus;
                    $updatedAt = (int)($request['updated_at'] ?? 0);
                    $updatedLabel = $updatedAt > 0 ? date('d/m/Y H:i', $updatedAt) : '-';
                ?>
                  <div class="i_tab_list_item flex_">
                    <div class="tab_detail_item tab_detail_item_maxwidth">
                      <div class="flex_ tabing_non_justify truncated">
                        <?php if ($requestAgencyUrl !== '') { ?>
                          <a href="<?php echo iN_HelpSecure($requestAgencyUrl);?>"><?php echo iN_HelpSecure($agencyName);?></a>
                        <?php } else { ?>
                          <?php echo iN_HelpSecure($agencyName);?>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($updatedLabel);?></div>
                    </div>
                  </div>
                <?php } ?>
                </div>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_no_requests']);?></div>
          <?php } ?>
        </div>
      </div>

      <?php if (!$hasMembership && !$hasPendingCreate) { ?>
      <div class="i_settings_wrapper_item">
        <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['agency_available_title']);?></div>
        <div class="i_settings_item_title_for">
          <?php if (!empty($agencies)) { ?>
            <div class="i_overflow_x_auto agency_table_wrap">
              <div class="agency_table_scroll">
                <div class="i_tab_container agency_table">
                <div class="i_tab_header flex_">
                  <div class="tab_item tab_detail_item_maxwidth"><?php echo iN_HelpSecure($LANG['agency_name']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_owner']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['agency_request_status']);?></div>
                  <div class="tab_item"><?php echo iN_HelpSecure($LANG['actions']);?></div>
                </div>
                <div class="i_tab_list_item_container">
                <?php foreach ($agencies as $agency) {
                    $agencyId = (int)($agency['agency_id'] ?? 0);
                    $agencyName = $agency['agency_name'] ?? '';
                    $agencyPublicUrl = $agencyId > 0 ? ($base_url . 'agency/' . $agencyId) : '';
                    $ownerId = (int)($agency['owner_iuid_fk'] ?? 0);
                    $ownerName = $agency['i_user_fullname'] ?? $agency['i_username'] ?? '';
                    $ownerLabel = $ownerName !== '' ? $ownerName . ' (#' . $ownerId . ')' : '#' . $ownerId;
                    $status = $requestStatuses[$agencyId] ?? '';
                    $statusLabel = $status !== '' ? ($LANG['agency_request_status_' . $status] ?? $status) : $LANG['agency_request_status_none'];
                    $canRequest = $status === '' || $status === 'rejected' || $status === 'canceled';
                ?>
                  <div class="i_tab_list_item flex_">
                    <div class="tab_detail_item tab_detail_item_maxwidth">
                      <div class="flex_ tabing_non_justify truncated">
                        <?php if ($agencyPublicUrl !== '') { ?>
                          <a href="<?php echo iN_HelpSecure($agencyPublicUrl);?>"><?php echo iN_HelpSecure($agencyName);?></a>
                        <?php } else { ?>
                          <?php echo iN_HelpSecure($agencyName);?>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($ownerLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($statusLabel);?></div>
                    </div>
                    <div class="tab_detail_item">
                      <div class="tabing_non_justify flex_">
                        <?php if ($canRequest) { ?>
                          <div class="seePost c2 border_one transition tabing flex_ creatorAgencyRequest" data-agency="<?php echo iN_HelpSecure($agencyId); ?>">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('92')) . iN_HelpSecure($LANG['agency_join_request']); ?>
                          </div>
                        <?php } else { ?>
                          <span class="box_not"><?php echo iN_HelpSecure($LANG['no_actions']);?></span>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                </div>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="box_not"><?php echo iN_HelpSecure($LANG['agency_no_agencies']);?></div>
          <?php } ?>
        </div>
      </div>
      <?php } ?>
    </div>
    <?php if (!$hasAgencyLock && $totalPages >= 1) { ?>
      <div class="i_become_creator_box_footer tabing">
        <ul class="pagination">
          <?php if ($pagep > 1) { ?>
            <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
          <?php } ?>

          <?php if ($pagep > 3) { ?>
            <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=1">1</a></li>
            <li class="dots">...</li>
          <?php } ?>

          <?php if ($pagep - 2 > 0) { ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep) - 2; ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li><?php } ?>
          <?php if ($pagep - 1 > 0) { ?><li class="page"><a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep) - 1; ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li><?php } ?>

          <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

          <?php if ($pagep + 1 < $totalPages + 1) { ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li><?php } ?>
          <?php if ($pagep + 2 < $totalPages + 1) { ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep) + 2; ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li><?php } ?>

          <?php if ($pagep < $totalPages - 2) { ?>
            <li class="dots">...</li>
            <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($totalPages); ?>"><?php echo iN_HelpSecure($totalPages); ?></a></li>
          <?php } ?>

          <?php if ($pagep < $totalPages) { ?>
            <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>settings?tab=agencies&page-id=<?php echo iN_HelpSecure($pagep) + 1; ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>
  </div>
</div>
