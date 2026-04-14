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
if (!in_array($statusFilter, ['all', 'active', 'inactive', 'pending', 'declined'], true)) {
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

$subscriptionFilterParams = [];
if ($scopeFilter !== 'all') {
    $subscriptionFilterParams['fs'] = $scopeFilter;
}
if ($statusFilter !== 'all') {
    $subscriptionFilterParams['st'] = $statusFilter;
}
if ($dateFromFilter !== '') {
    $subscriptionFilterParams['fd'] = $dateFromFilter;
}
if ($dateToFilter !== '') {
    $subscriptionFilterParams['td'] = $dateToFilter;
}
$subscriptionFilterQuery = http_build_query($subscriptionFilterParams);
$buildSubscriptionPageUrl = static function ($baseUrl, $pageNumber, $filterQueryString): string {
    $url = $baseUrl . 'admin/manage_community_subscriptions?page-id=' . (int)$pageNumber;
    if ($filterQueryString !== '') {
        $url .= '&' . $filterQueryString;
    }
    return $url;
};

$totalCommunitySubscriptions = (int)$iN->iN_CalculateAllCommunitySubscriptions($userID, $scopeFilter, $statusFilter, $dateFromFilter, $dateToFilter);
$totalPages = (int)ceil($totalCommunitySubscriptions / $paginationLimit);

$activeCommunitySubscriptions = (int)$iN->iN_CalculateAllCommunitySubscriptionsByStatus($userID, 'active', $scopeFilter, $dateFromFilter, $dateToFilter);
$inactiveCommunitySubscriptions = (int)$iN->iN_CalculateAllCommunitySubscriptionsByStatus($userID, 'inactive', $scopeFilter, $dateFromFilter, $dateToFilter);
$declinedCommunitySubscriptions = (int)$iN->iN_CalculateAllCommunitySubscriptionsByStatus($userID, 'declined', $scopeFilter, $dateFromFilter, $dateToFilter);
$communitySubscriptions = $iN->iN_CommunitySubscriptionsListData($userID, $paginationLimit, $pagep, $scopeFilter, $statusFilter, $dateFromFilter, $dateToFilter);
$defaultCurrencySymbol = isset($currencys[$defaultCurrency]) ? $currencys[$defaultCurrency] : '$';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_community_subscriptions'] ?? 'Community Subscriptions') . '(' . $totalCommunitySubscriptions . ')'; ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="i_contents_section flex_ tabing manage_margin_bottom">
                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c1">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['all_community_subscriptions'] ?? 'All Community Subscriptions'); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($totalCommunitySubscriptions); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c2">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['active_community_subscriptions'] ?? 'Active Community Subscriptions'); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($activeCommunitySubscriptions); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c3">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['inactive_community_subscriptions'] ?? 'Inactive Community Subscriptions'); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($inactiveCommunitySubscriptions); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c4">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . iN_HelpSecure($LANG['declined_community_subscriptions'] ?? 'Declined Community Subscriptions'); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num"><?php echo iN_HelpSecure($declinedCommunitySubscriptions); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="GET" action="<?php echo iN_HelpSecure($base_url); ?>admin/manage_community_subscriptions" class="i_contents_section flex_ manage_margin_bottom" style="width:100%;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 220px;min-width:200px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['subscription_scope']); ?></div>
                    <select name="fs" class="i_input flex_" style="min-width:0;width:100%;max-width:100%;">
                        <option value="all" <?php echo $scopeFilter === 'all' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['all'] ?? 'All'); ?></option>
                        <option value="community" <?php echo $scopeFilter === 'community' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['subscription_scope_community'] ?? 'Community'); ?></option>
                        <option value="community_plan" <?php echo $scopeFilter === 'community_plan' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['subscription_scope_community_plan'] ?? ($LANG['community_plan'] ?? 'Community Plan')); ?></option>
                    </select>
                </div>
                <div class="irow_box_right irow_box_right_style" style="flex:1 1 220px;min-width:200px;">
                    <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['subscription_status']); ?></div>
                    <select name="st" class="i_input flex_" style="min-width:0;width:100%;max-width:100%;">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['all'] ?? 'All'); ?></option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['active']); ?></option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['inactive']); ?></option>
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

            <?php if ($communitySubscriptions) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['subscription_scope']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['community_reference'] ?? 'Community'); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['community_subscription_member'] ?? $LANG['subscriber']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['community_subscription_owner'] ?? $LANG['subscribed_creator']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['subscription_date']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['reneval_date']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['subscription_type']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['subscription_status']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['amount']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['admin_earned']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['user_earned']); ?></th>
                        </tr>
                        <?php
                        foreach ($communitySubscriptions as $subscriptionRow) {
                            $subscriptionID = (int)($subscriptionRow['subscription_id'] ?? 0);
                            $subscriberId = (int)($subscriptionRow['iuid_fk'] ?? 0);
                            $ownerId = (int)($subscriptionRow['subscribed_iuid_fk'] ?? 0);
                            $subscriptionScope = (string)($subscriptionRow['subscription_scope'] ?? 'community');
                            $subscriptionRefId = (int)($subscriptionRow['subscription_ref_id'] ?? 0);
                            $subscriptionStatus = (string)($subscriptionRow['status'] ?? 'inactive');
                            $subscriptionCreated = isset($subscriptionRow['created']) ? strtotime((string)$subscriptionRow['created']) : false;
                            $subscriptionRenewal = isset($subscriptionRow['plan_period_end']) ? strtotime((string)$subscriptionRow['plan_period_end']) : false;
                            $subscriptionPlanInterval = strtolower((string)($subscriptionRow['plan_interval'] ?? 'month'));
                            $subscriptionAmount = (float)($subscriptionRow['plan_amount'] ?? 0);
                            $subscriptionCurrency = strtoupper((string)($subscriptionRow['plan_amount_currency'] ?? $defaultCurrency));
                            $subscriptionAdminEarned = (float)($subscriptionRow['admin_earning'] ?? 0);
                            $subscriptionUserEarned = (float)($subscriptionRow['user_net_earning'] ?? 0);

                            $subscriberAvatar = $iN->iN_UserAvatar($subscriberId, $base_url);
                            $ownerAvatar = $iN->iN_UserAvatar($ownerId, $base_url);

                            $subscriberUserName = (string)($subscriptionRow['subscriber_username'] ?? '');
                            $subscriberFullName = (string)($subscriptionRow['subscriber_fullname'] ?? '');
                            if ($subscriberUserName === '' || $subscriberFullName === '') {
                                $subscriberData = $iN->iN_GetUserDetails($subscriberId);
                                if ($subscriberData) {
                                    $subscriberUserName = (string)($subscriberData['i_username'] ?? $subscriberUserName);
                                    $subscriberFullName = (string)($subscriberData['i_user_fullname'] ?? $subscriberFullName);
                                }
                            }

                            $ownerUserName = (string)($subscriptionRow['owner_username'] ?? '');
                            $ownerFullName = (string)($subscriptionRow['owner_fullname'] ?? '');
                            if ($ownerUserName === '' || $ownerFullName === '') {
                                $ownerData = $iN->iN_GetUserDetails($ownerId);
                                if ($ownerData) {
                                    $ownerUserName = (string)($ownerData['i_username'] ?? $ownerUserName);
                                    $ownerFullName = (string)($ownerData['i_user_fullname'] ?? $ownerFullName);
                                }
                            }

                            if ($subscriptionScope === 'community_plan') {
                                $scopeLabel = $LANG['subscription_scope_community_plan'] ?? ($LANG['community_plan'] ?? 'Community Plan');
                                $communityLabel = $LANG['community_plan'] ?? 'Community Plan';
                            } else {
                                $scopeLabel = $LANG['subscription_scope_community'] ?? 'Community';
                                $communityTitle = trim((string)($subscriptionRow['community_title'] ?? ''));
                                if ($communityTitle === '' && $subscriptionRefId > 0) {
                                    $communityTitle = '#' . $subscriptionRefId;
                                }
                                $communityLabel = $communityTitle !== '' ? $communityTitle : '-';
                            }

                            if ($subscriptionPlanInterval === 'week' || $subscriptionPlanInterval === 'weekly') {
                                $intervalLabel = '<div class="sWeekly c2">' . ($LANG['weekly'] ?? 'Weekly') . '</div>';
                            } elseif ($subscriptionPlanInterval === 'month' || $subscriptionPlanInterval === 'monthly') {
                                $intervalLabel = '<div class="sWeekly c3">' . ($LANG['monthly'] ?? 'Monthly') . '</div>';
                            } else {
                                $intervalLabel = '<div class="sWeekly c4">' . ($LANG['yearly'] ?? 'Yearly') . '</div>';
                            }

                            if ($subscriptionStatus === 'active') {
                                $statusLabel = '<div class="sWeekly c8">' . ($LANG['active'] ?? 'Active') . '</div>';
                            } elseif ($subscriptionStatus === 'inactive') {
                                $statusLabel = '<div class="sWeekly c7">' . ($LANG['inactive'] ?? 'Inactive') . '</div>';
                            } elseif ($subscriptionStatus === 'pending') {
                                $statusLabel = '<div class="sWeekly c3">' . ($LANG['pending'] ?? 'Pending') . '</div>';
                            } else {
                                $statusLabel = '<div class="sWeekly c4">' . ($LANG['declined'] ?? 'Declined') . '</div>';
                            }

                            $currencySymbol = isset($currencys[$subscriptionCurrency]) ? $currencys[$subscriptionCurrency] : $defaultCurrencySymbol;
                            $createdLabel = $subscriptionCreated ? date('d/m/Y', $subscriptionCreated) : '-';
                            $renewalLabel = $subscriptionRenewal ? date('d/m/Y', $subscriptionRenewal) : '-';
                        ?>
                            <tr class="transition trhover">
                                <td><?php echo iN_HelpSecure($subscriptionID); ?></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($scopeLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($communityLabel); ?></div></td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_">
                                            <img src="<?php echo iN_HelpSecure($subscriberAvatar); ?>">
                                        </div>
                                        <div class="t_owner_user tabing flex_">
                                            <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($subscriberUserName); ?>">
                                                <?php echo iN_HelpSecure($subscriberFullName ?: ('#' . $subscriberId)); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="t_od flex_ c6">
                                        <div class="t_owner_avatar border_two tabing flex_">
                                            <img src="<?php echo iN_HelpSecure($ownerAvatar); ?>">
                                        </div>
                                        <div class="t_owner_user tabing flex_">
                                            <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($ownerUserName); ?>">
                                                <?php echo iN_HelpSecure($ownerFullName ?: ('#' . $ownerId)); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($createdLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($renewalLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo html_entity_decode($intervalLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo html_entity_decode($statusLabel); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($currencySymbol) . iN_HelpSecure(number_format($subscriptionAmount, 2, '.', '')); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($currencySymbol) . iN_HelpSecure(number_format($subscriptionAdminEarned, 2, '.', '')); ?></div></td>
                                <td class="see_post_details"><div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($currencySymbol) . iN_HelpSecure(number_format($subscriptionUserEarned, 2, '.', '')); ?></div></td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else {
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . iN_HelpSecure($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['no_community_subscriptions_found'] ?? 'No community subscriptions found.') . '</div></div>';
            } ?>
        </div>

        <div class="i_become_creator_box_footer tabing">
            <?php if ($totalPages > 1): ?>
                <ul class="pagination">
                    <?php if ($pagep > 1): ?>
                        <li class="prev">
                            <a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep - 1, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a>
                        </li>
                    <?php endif; ?>

                    <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, 1, $subscriptionFilterQuery)); ?>">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($pagep - 2 > 0): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep - 2, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) - 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep - 1 > 0): ?>
                        <li class="page"><a href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep - 1, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) - 1; ?></a></li>
                    <?php endif; ?>

                    <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                    <?php if ($pagep + 1 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep + 1, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) + 1; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep + 2 < $totalPages + 1): ?>
                        <li class="page"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep + 2, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($pagep) + 2; ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages - 2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $totalPages, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($totalPages); ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagep < $totalPages): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($buildSubscriptionPageUrl($base_url, $pagep + 1, $subscriptionFilterQuery)); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
