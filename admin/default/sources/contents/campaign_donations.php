<?php
$searchCampaign = '';
$dateFromFilter = '';
$dateToFilter = '';
$pagep = isset($_GET["page-id"]) ? (int)$_GET["page-id"] : 1;

if ($pagep <= 0) {
    $pagep = 1;
}

if (isset($_GET['sr'])) {
    $searchCampaign = trim((string)$iN->iN_Secure($_GET['sr']));
}

$normalizeDateInput = static function ($dateValue) {
    $dateValue = trim((string)$dateValue);
    if ($dateValue === '') {
        return '';
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateValue, $matches)) {
        return '';
    }
    if (!checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
        return '';
    }
    return $dateValue;
};

if (isset($_GET['fd'])) {
    $dateFromFilter = $normalizeDateInput($_GET['fd']);
}
if (isset($_GET['td'])) {
    $dateToFilter = $normalizeDateInput($_GET['td']);
}

$campaignDonationFilterParams = [];
if ($searchCampaign !== '') {
    $campaignDonationFilterParams['sr'] = $searchCampaign;
}
if ($dateFromFilter !== '') {
    $campaignDonationFilterParams['fd'] = $dateFromFilter;
}
if ($dateToFilter !== '') {
    $campaignDonationFilterParams['td'] = $dateToFilter;
}
$campaignDonationFilterQuery = http_build_query($campaignDonationFilterParams);
$buildCampaignDonationPageUrl = static function ($baseUrl, $pageNumber, $filterQueryString) {
    $url = $baseUrl . 'admin/campaign_donations?page-id=' . (int)$pageNumber;
    if ($filterQueryString !== '') {
        $url .= '&' . $filterQueryString;
    }
    return $url;
};

$totalCampaignDonations = $iN->iN_UserTotalCampaignDonations($userID, $searchCampaign, $dateFromFilter, $dateToFilter);
$totalPages = ceil($totalCampaignDonations / $paginationLimit);
$campaignAdminStats = $iN->iN_GetCampaignDonationAdminStats((int)$userID);
$totalCampaignsCreated = (int)($campaignAdminStats['total_campaigns'] ?? 0);
$liveCampaignsCount = (int)($campaignAdminStats['live_campaigns'] ?? 0);
$totalCampaignEarnings = (float)($campaignAdminStats['total_earnings'] ?? 0);
$currencySymbol = $currencys[$defaultCurrency] ?? '$';
$totalCampaignEarningsDisplay = $currencySymbol . number_format($totalCampaignEarnings, 2);
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['campaign_donations']) . '(' . (int)$totalCampaignDonations . ')'; ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_contents_section flex_ manage_margin_bottom" style="width:100%;display:flex;flex-wrap:wrap;">
                <div class="row_wrapper" style="flex:1 1 260px;width:auto;">
                    <div class="row_item flex_ column border_one c1">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('77')) . iN_HelpSecure($LANG['campaign_admin_total']); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($totalCampaignsCreated); ?></span>
                        </div>
                    </div>
                </div>
                <div class="row_wrapper" style="flex:1 1 260px;width:auto;">
                    <div class="row_item flex_ column border_one c3">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('190')) . iN_HelpSecure($LANG['campaign_admin_live']); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($liveCampaignsCount); ?></span>
                        </div>
                    </div>
                </div>
                <div class="row_wrapper" style="flex:1 1 260px;width:auto;">
                    <div class="row_item flex_ column border_one c2">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('86')) . iN_HelpSecure($LANG['campaign_admin_total_earnings']); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($totalCampaignEarningsDisplay); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <form method="GET" action="<?php echo iN_HelpSecure($base_url); ?>admin/campaign_donations" class="i_contents_section flex_ manage_margin_bottom" style="width:100%;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 260px;min-width:220px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['campaign_filter_campaign']); ?></div>
                    <input type="text" class="i_input flex_" name="sr" value="<?php echo iN_HelpSecure($searchCampaign); ?>" placeholder="<?php echo iN_HelpSecure($LANG['campaign_filter_campaign']); ?>" style="min-width:0;width:100%;max-width:100%;">
                </div>
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 240px;min-width:220px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['campaign_filter_date_from']); ?></div>
                    <input type="date" class="i_input flex_" name="fd" value="<?php echo iN_HelpSecure($dateFromFilter); ?>" style="min-width:0;width:100%;max-width:100%;">
                </div>
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 240px;min-width:220px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['campaign_filter_date_to']); ?></div>
                    <input type="date" class="i_input flex_" name="td" value="<?php echo iN_HelpSecure($dateToFilter); ?>" style="min-width:0;width:100%;max-width:100%;">
                </div>
                <div class="irow_box_right flex_ tabing irow_box_right_styl" style="width:auto;padding-left:0;padding-top:0;flex:0 0 auto;align-items:flex-end;">
                    <button type="submit" class="i_nex_btn_btn" style="float:none;margin-left:0;padding:10px 28px;white-space:nowrap;"><?php echo iN_HelpSecure($LANG['search']); ?></button>
                </div>
            </form>
            <div class="rec_not rec_not_style" style="padding-left:0;"><?php echo iN_HelpSecure($LANG['campaign_filter_search_hint']); ?></div>
            <?php
            $campaignDonations = $iN->iN_CampaignDonationsList($userID, $paginationLimit, $pagep, $searchCampaign, $dateFromFilter, $dateToFilter);
            if ($campaignDonations) {
            ?>
            <div class="i_overflow_x_auto">
                <table class="border_one">
                    <tr>
                        <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['payer']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['paid_to']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['campaign_label_title']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['amount']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['payment_method']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['campaign_anonymous']); ?></th>
                        <th><?php echo iN_HelpSecure($LANG['date']); ?></th>
                    </tr>
                    <?php foreach ($campaignDonations as $donation) {
                        $paymentID = isset($donation['payment_id']) ? (int)$donation['payment_id'] : 0;
                        $payerID = isset($donation['payer_iuid_fk']) ? (int)$donation['payer_iuid_fk'] : 0;
                        $ownerID = isset($donation['owner_uid_fk']) ? (int)$donation['owner_uid_fk'] : 0;
                        if ($ownerID <= 0) {
                            $ownerID = isset($donation['payed_iuid_fk']) ? (int)$donation['payed_iuid_fk'] : 0;
                        }
                        $paymentPostID = isset($donation['payed_post_id_fk']) ? (int)$donation['payed_post_id_fk'] : 0;
                        $paymentTime = isset($donation['payment_time']) ? (int)$donation['payment_time'] : 0;
                        $paymentAmount = isset($donation['amount']) ? (float)$donation['amount'] : 0.0;
                        $paymentOption = strtoupper((string)($donation['payment_option'] ?? ''));
                        $currencyCode = (string)($donation['currency'] ?? $defaultCurrency);
                        if ($currencyCode === '') {
                            $currencyCode = (string)$defaultCurrency;
                        }
                        $currencySymbolItem = $currencys[$currencyCode] ?? ($currencys[$defaultCurrency] ?? '$');
                        $payerUserName = (string)($donation['payer_username'] ?? '');
                        $payerUserFullName = (string)($donation['payer_fullname'] ?? $payerUserName);
                        $ownerUserName = (string)($donation['owner_username'] ?? '');
                        $ownerUserFullName = (string)($donation['owner_fullname'] ?? $ownerUserName);
                        $payerAvatar = $iN->iN_UserAvatar($payerID, $base_url);
                        $ownerAvatar = $ownerID > 0 ? $iN->iN_UserAvatar($ownerID, $base_url) : '';
                        $campaignID = isset($donation['campaign_id']) ? (int)$donation['campaign_id'] : 0;
                        $campaignTitle = trim((string)($donation['campaign_title'] ?? ''));
                        $campaignTitleDisplay = $campaignTitle !== '' ? $campaignTitle : '-';
                        $campaignDisplay = $campaignID > 0 ? '#' . $campaignID . ' - ' . $campaignTitleDisplay : $campaignTitleDisplay;
                        $anonymousFlag = ((int)($donation['is_anonymous'] ?? 0) === 1) ? $LANG['yes'] : $LANG['no'];
                        $paymentDate = $paymentTime > 0 ? gmdate("d/m/Y H:i", $paymentTime) : '-';
                    ?>
                    <tr class="transition trhover">
                        <td><?php echo iN_HelpSecure($paymentID); ?></td>
                        <td>
                            <div class="t_od flex_ c6">
                                <div class="t_owner_avatar border_two tabing flex_">
                                    <img src="<?php echo iN_HelpSecure($payerAvatar); ?>">
                                </div>
                                <div class="t_owner_user tabing flex_">
                                    <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($payerUserName); ?>">
                                        <?php echo iN_HelpSecure($payerUserFullName); ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($ownerID > 0 && $ownerUserName !== '') { ?>
                            <div class="t_od flex_ c6">
                                <div class="t_owner_avatar border_two tabing flex_">
                                    <img src="<?php echo iN_HelpSecure($ownerAvatar); ?>">
                                </div>
                                <div class="t_owner_user tabing flex_">
                                    <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($ownerUserName); ?>">
                                        <?php echo iN_HelpSecure($ownerUserFullName); ?>
                                    </a>
                                </div>
                            </div>
                            <?php } else { ?>
                                <div class="t_od flex_ c6 t_od_s">-</div>
                            <?php } ?>
                        </td>
                        <td class="see_post_details">
                            <div class="flex_ tabing_non_justify">
                                <?php if ($paymentPostID > 0) { ?>
                                    <a href="<?php echo iN_HelpSecure($base_url); ?>admin/allPosts?post=<?php echo iN_HelpSecure($paymentPostID); ?>">
                                        <?php echo iN_HelpSecure($campaignDisplay); ?>
                                    </a>
                                <?php } else {
                                    echo iN_HelpSecure($campaignDisplay);
                                } ?>
                            </div>
                        </td>
                        <td class="see_post_details">
                            <div class="flex_ tabing_non_justify">
                                <?php echo iN_HelpSecure($currencySymbolItem . number_format($paymentAmount, 2)); ?>
                            </div>
                        </td>
                        <td class="see_post_details">
                            <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($paymentOption); ?></div>
                        </td>
                        <td class="see_post_details">
                            <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($anonymousFlag); ?></div>
                        </td>
                        <td class="see_post_details">
                            <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($paymentDate); ?></div>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . html_entity_decode($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['no_transactions_yet']) . '</div></div>';
            } ?>
        </div>
        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages >= 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep - 1, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, 1, $campaignDonationFilterQuery)); ?>">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep - 2, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li><?php endif; ?>
                    <?php if ($pagep - 1 > 0): ?><li class="page"><a href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep - 1, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li><?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep + 1, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li><?php endif; ?>
                    <?php if ($pagep + 2 < $totalPages + 1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep + 2, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li><?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $totalPages, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($totalPages); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($buildCampaignDonationPageUrl($base_url, $pagep + 1, $campaignDonationFilterQuery)); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
