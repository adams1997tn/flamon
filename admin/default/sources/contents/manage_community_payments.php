<?php
$scopeFilter = 'all';
$statusFilter = 'all';
$dateFromFilter = '';
$dateToFilter = '';
$pagep = isset($_GET['page-id']) ? (int)$_GET['page-id'] : 1;
if ($pagep < 1) {
    $pagep = 1;
}

if (isset($_GET['fs'])) {
    $scopeFilter = strtolower(trim((string)$iN->iN_Secure($_GET['fs'])));
}
if (!in_array($scopeFilter, ['all', 'community', 'community_plan'], true)) {
    $scopeFilter = 'all';
}

if (isset($_GET['st'])) {
    $statusFilter = strtolower(trim((string)$iN->iN_Secure($_GET['st'])));
}
if (!in_array($statusFilter, ['all', 'ok', 'pending', 'declined'], true)) {
    $statusFilter = 'all';
}

$normalizeDateInput = static function ($dateValue): string {
    $dateValue = trim((string)$dateValue);
    if ($dateValue === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateValue, $matches)) {
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

$paymentFilterParams = [];
if ($scopeFilter !== 'all') {
    $paymentFilterParams['fs'] = $scopeFilter;
}
if ($statusFilter !== 'all') {
    $paymentFilterParams['st'] = $statusFilter;
}
if ($dateFromFilter !== '') {
    $paymentFilterParams['fd'] = $dateFromFilter;
}
if ($dateToFilter !== '') {
    $paymentFilterParams['td'] = $dateToFilter;
}
$paymentFilterQuery = http_build_query($paymentFilterParams);
$buildPaymentPageUrl = static function ($baseUrl, $pageNumber, $filterQueryString): string {
    $url = $baseUrl . 'admin/manage_community_payments?page-id=' . (int)$pageNumber;
    if ($filterQueryString !== '') {
        $url .= '&' . $filterQueryString;
    }
    return $url;
};

$totalCommunityPayments = (int)$iN->iN_TotalCommunitySubscriptionPayments($userID, $scopeFilter, $statusFilter, $dateFromFilter, $dateToFilter);
$totalPages = (int)ceil($totalCommunityPayments / $paginationLimit);

$totalSuccessful = (int)$iN->iN_CalculateCommunitySubscriptionPaymentsByStatus($userID, 'ok', $scopeFilter, $dateFromFilter, $dateToFilter);
$totalPending = (int)$iN->iN_CalculateCommunitySubscriptionPaymentsByStatus($userID, 'pending', $scopeFilter, $dateFromFilter, $dateToFilter);
$totalDeclined = (int)$iN->iN_CalculateCommunitySubscriptionPaymentsByStatus($userID, 'declined', $scopeFilter, $dateFromFilter, $dateToFilter);
$communityPayments = $iN->iN_CommunitySubscriptionPaymentsList($userID, $paginationLimit, $pagep, $scopeFilter, $statusFilter, $dateFromFilter, $dateToFilter);
$defaultCurrencySymbol = isset($currencys[$defaultCurrency]) ? $currencys[$defaultCurrency] : '$';
$communityCache = [];
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_community_payments'] ?? 'Community Payments') . '(' . $totalCommunityPayments . ')'; ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_contents_section flex_ tabing manage_margin_bottom">
                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c1">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('42')) . iN_HelpSecure($LANG['total_community_subscription_payments'] ?? 'Total Community Subscription Payments'); ?>
                        </div>
                        <div class="chart_row_box_sum"><span class="count-num"><?php echo iN_HelpSecure($totalCommunityPayments); ?></span></div>
                    </div>
                </div>
                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c2">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('42')) . iN_HelpSecure($LANG['successful_community_subscription_payments'] ?? 'Successful Payments'); ?>
                        </div>
                        <div class="chart_row_box_sum"><span class="count-num"><?php echo iN_HelpSecure($totalSuccessful); ?></span></div>
                    </div>
                </div>
                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c3">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('42')) . iN_HelpSecure($LANG['pending_community_subscription_payments'] ?? 'Pending Payments'); ?>
                        </div>
                        <div class="chart_row_box_sum"><span class="count-num"><?php echo iN_HelpSecure($totalPending); ?></span></div>
                    </div>
                </div>
                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c4">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('42')) . iN_HelpSecure($LANG['declined_community_subscription_payments'] ?? 'Declined Payments'); ?>
                        </div>
                        <div class="chart_row_box_sum"><span class="count-num"><?php echo iN_HelpSecure($totalDeclined); ?></span></div>
                    </div>
                </div>
            </div>

            <form method="GET" action="<?php echo iN_HelpSecure($base_url); ?>admin/manage_community_payments" class="i_contents_section flex_ manage_margin_bottom" style="width:100%;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 220px;min-width:200px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['subscription_scope']); ?></div>
                    <select name="fs" class="i_input flex_" style="min-width:0;width:100%;max-width:100%;">
                        <option value="all" <?php echo $scopeFilter === 'all' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['all'] ?? 'All'); ?></option>
                        <option value="community" <?php echo $scopeFilter === 'community' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['subscription_scope_community'] ?? 'Community'); ?></option>
                        <option value="community_plan" <?php echo $scopeFilter === 'community_plan' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['subscription_scope_community_plan'] ?? ($LANG['community_plan'] ?? 'Community Plan')); ?></option>
                    </select>
                </div>
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 220px;min-width:200px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['status']); ?></div>
                    <select name="st" class="i_input flex_" style="min-width:0;width:100%;max-width:100%;">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['all'] ?? 'All'); ?></option>
                        <option value="ok" <?php echo $statusFilter === 'ok' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['success']); ?></option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['pending']); ?></option>
                        <option value="declined" <?php echo $statusFilter === 'declined' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['declined']); ?></option>
                    </select>
                </div>
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 220px;min-width:200px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['campaign_filter_date_from'] ?? 'Date from'); ?></div>
                    <input type="date" class="i_input flex_" name="fd" value="<?php echo iN_HelpSecure($dateFromFilter); ?>" style="min-width:0;width:100%;max-width:100%;">
                </div>
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 220px;min-width:200px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['campaign_filter_date_to'] ?? 'Date to'); ?></div>
                    <input type="date" class="i_input flex_" name="td" value="<?php echo iN_HelpSecure($dateToFilter); ?>" style="min-width:0;width:100%;max-width:100%;">
                </div>
                <div class="irow_box_right flex_ tabing irow_box_right_styl" style="width:auto;padding-left:0;padding-top:0;flex:0 0 auto;align-items:flex-end;">
                    <button type="submit" class="i_nex_btn_btn" style="float:none;margin-left:0;padding:10px 28px;white-space:nowrap;"><?php echo iN_HelpSecure($LANG['search']); ?></button>
                </div>
            </form>

            <?php if ($communityPayments) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['subscription_scope']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['community_reference'] ?? 'Community'); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['payer']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['paid_to']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['payment_method']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['date']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['amount']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['community_payment_reference'] ?? 'Reference'); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php foreach ($communityPayments as $paymentRow) {
                            $paymentID = (int)($paymentRow['payment_id'] ?? 0);
                            $payerID = (int)($paymentRow['payer_iuid_fk'] ?? 0);
                            $payedID = (int)($paymentRow['payed_iuid_fk'] ?? 0);
                            $paymentTime = (int)($paymentRow['payment_time'] ?? 0);
                            $paymentStatus = (string)($paymentRow['payment_status'] ?? 'pending');
                            $paymentAmount = (float)($paymentRow['amount'] ?? 0);
                            $paymentCurrency = strtoupper((string)($paymentRow['currency'] ?? $defaultCurrency));
                            $paymentMethod = strtoupper((string)($paymentRow['payment_option'] ?? '-'));
                            $resolvedScope = (string)($paymentRow['resolved_subscription_scope'] ?? 'community');
                            $resolvedScopeRef = (int)($paymentRow['resolved_subscription_ref_id'] ?? 0);
                            $invoiceToken = trim((string)($paymentRow['invoice_token'] ?? ''));
                            $invoiceNumber = trim((string)($paymentRow['invoice_number'] ?? ''));
                            $orderKey = trim((string)($paymentRow['order_key'] ?? ''));

                            if ($resolvedScope === 'community_plan') {
                                $scopeLabel = $LANG['subscription_scope_community_plan'] ?? ($LANG['community_plan'] ?? 'Community Plan');
                                $communityLabel = $LANG['community_plan'] ?? 'Community Plan';
                            } else {
                                $scopeLabel = $LANG['subscription_scope_community'] ?? 'Community';
                                $communityLabel = '-';
                                if ($resolvedScopeRef > 0) {
                                    if (!isset($communityCache[$resolvedScopeRef])) {
                                        $communityCache[$resolvedScopeRef] = $iN->iN_GetCommunityById($resolvedScopeRef);
                                    }
                                    $communityData = $communityCache[$resolvedScopeRef];
                                    if ($communityData && isset($communityData['title'])) {
                                        $communityLabel = (string)$communityData['title'];
                                    } else {
                                        $communityLabel = '#' . $resolvedScopeRef;
                                    }
                                }
                            }

                            $payerAvatar = $iN->iN_UserAvatar($payerID, $base_url);
                            $payedAvatar = $iN->iN_UserAvatar($payedID, $base_url);

                            $payerUserName = (string)($paymentRow['payer_username'] ?? '');
                            $payerFullName = (string)($paymentRow['payer_fullname'] ?? '');
                            if ($payerUserName === '' || $payerFullName === '') {
                                $payerData = $iN->iN_GetUserDetails($payerID);
                                if ($payerData) {
                                    $payerUserName = (string)($payerData['i_username'] ?? $payerUserName);
                                    $payerFullName = (string)($payerData['i_user_fullname'] ?? $payerFullName);
                                }
                            }

                            $payedUserName = (string)($paymentRow['payed_username'] ?? '');
                            $payedFullName = (string)($paymentRow['payed_fullname'] ?? '');
                            if ($payedUserName === '' || $payedFullName === '') {
                                $payedData = $iN->iN_GetUserDetails($payedID);
                                if ($payedData) {
                                    $payedUserName = (string)($payedData['i_username'] ?? $payedUserName);
                                    $payedFullName = (string)($payedData['i_user_fullname'] ?? $payedFullName);
                                }
                            }

                            if ($paymentStatus === 'pending') {
                                $statusBadge = '<div class="flex_ tabing fordecs c4">' . iN_HelpSecure($LANG['pending']) . '</div>';
                            } elseif ($paymentStatus === 'ok') {
                                $statusBadge = '<div class="flex_ tabing fordecs c2">' . iN_HelpSecure($LANG['success']) . '</div>';
                            } else {
                                $statusBadge = '<div class="flex_ tabing fordecs c4">' . iN_HelpSecure($LANG['declined']) . '</div>';
                            }

                            $currencySymbol = isset($currencys[$paymentCurrency]) ? $currencys[$paymentCurrency] : $defaultCurrencySymbol;
                            $dateLabel = $paymentTime > 0 ? date('d/m/Y H:i', $paymentTime) : '-';
                            $referenceLabel = $invoiceNumber !== '' ? $invoiceNumber : ($orderKey !== '' ? $orderKey : '-');
                        ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($paymentID); ?></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($scopeLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($communityLabel); ?></div></td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_">
                                            <img src="<?php echo iN_HelpSecure($payerAvatar); ?>">
                                        </div>
                                        <div class="t_owner_user tabing flex_">
                                            <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($payerUserName); ?>">
                                                <?php echo iN_HelpSecure($payerFullName ?: ('#' . $payerID)); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($payedID > 0) { ?>
                                        <div class="t_od flex_ c6">
                                            <div class="t_owner_avatar border_two tabing flex_">
                                                <img src="<?php echo iN_HelpSecure($payedAvatar); ?>">
                                            </div>
                                            <div class="t_owner_user tabing flex_">
                                                <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($payedUserName); ?>">
                                                    <?php echo iN_HelpSecure($payedFullName ?: ('#' . $payedID)); ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="t_od flex_ c6 t_od_s"><?php echo iN_HelpSecure($LANG['paid_for_himself']); ?></div>
                                    <?php } ?>
                                </td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($paymentMethod); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($dateLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($currencySymbol) . iN_HelpSecure(number_format($paymentAmount, 2, '.', '')); ?></div></td>
                                <td class="see_post_details flex_ tabing"><?php echo html_entity_decode($statusBadge); ?></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($referenceLabel); ?></div></td>
                                <td class="see_post_details">
                                    <?php if ($invoiceToken !== '') { ?>
                                        <a class="transition invoice_download_btn" target="_blank" href="<?php echo iN_HelpSecure($base_url); ?>invoice?token=<?php echo iN_HelpSecure($invoiceToken); ?>">
                                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('170')) . iN_HelpSecure($LANG['download_invoice']); ?>
                                        </a>
                                    <?php } else { ?>
                                        <span><?php echo iN_HelpSecure($LANG['invoice_not_ready']); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . iN_HelpSecure($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['no_community_subscription_payments_found'] ?? 'No community subscription payments found.') . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages > 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep - 1, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, 1, $paymentFilterQuery)); ?>">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep - 2, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep - 1, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep + 1, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep + 2, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $totalPages, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($totalPages); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($buildPaymentPageUrl($base_url, $pagep + 1, $paymentFilterQuery)); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
