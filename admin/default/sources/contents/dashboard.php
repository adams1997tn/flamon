<?php
$userID = isset($userID) ? (int)$userID : 0;
$currencySymbol = $currencys[$defaultCurrency] ?? $defaultCurrency;
$currencySymbolPos = isset($currencySymbolPosition) ? $currencySymbolPosition : 'left';
$showAdvancedDashboardCharts = true;
?>
<div class="i_contents_container">
    <div class="i_total_earning flex_ column tabing__justify">
        <div class="net_earn_title flex_ tabing_non_justify">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('76')); ?>
            <?php echo iN_HelpSecure($LANG['net_admin_earnings']); ?>
        </div>
        <div class="net_earn">
            <?php
            $totalAdminEarnings = (float)$iN->iN_TotalSubscriptionEarnings($userID)
                + (float)$iN->iN_TotalPremiumEarnings($userID)
                + (float)$iN->iN_TotalBoostEarnings($userID)
                + (float)$iN->iN_TotalPremiumEarningsNetPoint($userID);
            $totalAdminCompact = $iN->formatCompactNumber($totalAdminEarnings);
            ?>
            <?php if ($currencySymbolPos === 'right'): ?>
                <span class="count-num"><?php echo iN_HelpSecure($totalAdminCompact); ?></span>
                <?php echo iN_HelpSecure($currencySymbol); ?>
            <?php else: ?>
                <?php echo iN_HelpSecure($currencySymbol); ?>
                <span class="count-num"><?php echo iN_HelpSecure($totalAdminCompact); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="i_contents_section flex_ tabing">
        <!-- Subscription -->
        <div class="row_wrapper">
            <div class="row_item flex_ column border_one c1">
                <div class="chart_row_box_title flex_ tabing_non_justify">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')); ?>
                    <?php echo iN_HelpSecure($LANG['subscriptions']); ?>
                </div>
                <div class="chart_row_box_sum">
                    <?php
                    $subscriptionTotal = $iN->iN_TotalSubscriptionEarnings($userID);
                    $subscriptionCompact = $iN->formatCompactNumber($subscriptionTotal);
                    ?>
                    <?php if ($currencySymbolPos === 'right'): ?>
                        <span class="count-num"><?php echo iN_HelpSecure($subscriptionCompact); ?></span>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                    <?php else: ?>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                        <span class="count-num"><?php echo iN_HelpSecure($subscriptionCompact); ?></span>
                    <?php endif; ?>
                </div>
                <div class="wmore tabing_non_justify flex_">
                    <a href="<?php echo iN_HelpSecure($base_url).'admin/manage_subscriptions'; ?>">
                        <?php echo iN_HelpSecure($LANG['view_more']); ?>
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Point Earnings -->
        <div class="row_wrapper">
            <div class="row_item flex_ column border_one c2">
                <div class="chart_row_box_title flex_ tabing_non_justify">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
                    <?php echo iN_HelpSecure($LANG['all_point_earning']); ?>
                </div>
                <div class="chart_row_box_sum">
                    <?php
                        $totalPointEarnings = (float)$iN->iN_TotalPremiumEarnings($userID)
                            + (float)$iN->iN_TotalPremiumEarningsNetPoint($userID);
                        $totalPointCompact = $iN->formatCompactNumber($totalPointEarnings);
                    ?>
                    <?php if ($currencySymbolPos === 'right'): ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalPointCompact); ?></span>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                    <?php else: ?>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalPointCompact); ?></span>
                    <?php endif; ?>
                </div>
                <div class="wmore tabing_non_justify flex_">
                    <a href="<?php echo iN_HelpSecure($base_url).'admin/point_earnings'; ?>">
                        <?php echo iN_HelpSecure($LANG['view_more']); ?>
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Boost Earnings -->
        <div class="row_wrapper">
            <div class="row_item flex_ column border_one c7">
                <div class="chart_row_box_title flex_ tabing_non_justify">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('177')); ?>
                    <?php echo iN_HelpSecure($LANG['boost_earnings']); ?>
                </div>
                <div class="chart_row_box_sum">
                    <?php
                    $boostTotal = $iN->iN_TotalBoostEarnings($userID);
                    $boostCompact = $iN->formatCompactNumber($boostTotal);
                    ?>
                    <?php if ($currencySymbolPos === 'right'): ?>
                        <span class="count-num"><?php echo iN_HelpSecure($boostCompact); ?></span>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                    <?php else: ?>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                        <span class="count-num"><?php echo iN_HelpSecure($boostCompact); ?></span>
                    <?php endif; ?>
                </div>
                <div class="wmore tabing_non_justify flex_">
                    <a href="<?php echo iN_HelpSecure($base_url).'admin/manage_boosted_posts'; ?>">
                        <?php echo iN_HelpSecure($LANG['view_more']); ?>
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Users -->
        <div class="row_wrapper">
            <div class="row_item flex_ column border_one c3">
                <div class="chart_row_box_title flex_ tabing_non_justify">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?>
                    <?php echo iN_HelpSecure($LANG['users']); ?>
                </div>
                <div class="chart_row_box_sum">
                    <span class="count-num"><?php echo iN_HelpSecure($iN->formatCompactNumber((int)$iN->iN_TotalUsers())); ?></span>
                </div>
                <div class="wmore tabing_non_justify flex_">
                    <a href="<?php echo iN_HelpSecure($base_url).'admin/manage_users'; ?>">
                        <?php echo iN_HelpSecure($LANG['view_more']); ?>
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Posts -->
        <div class="row_wrapper">
            <div class="row_item flex_ column border_one c4">
                <div class="chart_row_box_title flex_ tabing_non_justify">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('110')); ?>
                    <?php echo iN_HelpSecure($LANG['posts']); ?>
                </div>
                <div class="chart_row_box_sum">
                    <span class="count-num"><?php echo iN_HelpSecure($iN->formatCompactNumber((int)$iN->iN_TotalUserPosts())); ?></span>
                </div>
                <div class="wmore tabing_non_justify flex_">
                    <a href="<?php echo iN_HelpSecure($base_url).'admin/allPosts'; ?>">
                        <?php echo iN_HelpSecure($LANG['view_more']); ?>
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('98')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard_duo">
        <!-- Monthly Statistics Chart -->
        <div class="i_contents_section_two flex_ tabing column c5 border_one">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['statistic_of_the_month']); ?></div>
            <div class="statistic_chart">
                <div class="earnings_range_controls">
                    <button type="button" class="earnings-range active" data-range="7"><?php echo iN_HelpSecure($LANG['last_7_days'] ?? 'Last 7 days'); ?></button>
                    <button type="button" class="earnings-range" data-range="30"><?php echo iN_HelpSecure($LANG['last_30_days'] ?? 'Last 30 days'); ?></button>
                </div>
                <canvas id="myChart"></canvas>
            </div>
        </div>

        <div class="i_contents_section_two flex_ tabing column c5 border_one full_chart_box">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['posts_breakdown'] ?? 'Posts breakdown'); ?></div>
            <div class="earnings_range_controls">
                <button type="button" class="earnings-range posts-range active" data-range="daily"><?php echo iN_HelpSecure($LANG['daily'] ?? 'Daily'); ?></button>
                <button type="button" class="earnings-range posts-range" data-range="weekly"><?php echo iN_HelpSecure($LANG['weekly'] ?? 'Weekly'); ?></button>
                <button type="button" class="earnings-range posts-range" data-range="monthly"><?php echo iN_HelpSecure($LANG['monthly'] ?? 'Monthly'); ?></button>
                <button type="button" class="earnings-range posts-range" data-range="yearly"><?php echo iN_HelpSecure($LANG['yearly'] ?? 'Yearly'); ?></button>
            </div>
            <div class="statistic_chart small_chart"><canvas id="postsChart"></canvas></div>
        </div>
    </div>

    <div class="revenues_panel border_one">
        <div class="revenues flex_ tabing">
            <div class="revenue_box flex_ tabing column">
                <div class="revenue_sum">
                    <?php
                    $totalEarn = $iN->iN_CurrentDayTotalPremiumEarning() + $iN->iN_CurrentDayTotalSubscriptionEarning() + $iN->iN_CurrentDayTotalBoostEarning();
                    $totalEarnCompact = $iN->formatCompactNumber($totalEarn);
                    ?>
                    <?php if ($currencySymbolPos === 'right'): ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalEarnCompact); ?></span>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                    <?php else: ?>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalEarnCompact); ?></span>
                    <?php endif; ?>
                </div>
                <div class="revenue_title"><?php echo iN_HelpSecure($LANG['revenue_today']); ?></div>
            </div>

            <div class="revenue_box flex_ tabing column">
                <div class="revenue_sum">
                    <?php
                    $totalWeeklyEarning = $iN->iN_WeeklyTotalPremiumEarning() + $iN->iN_WeeklyTotalSubscriptionEarning() + $iN->iN_WeeklyTotalBoostEarning();
                    $totalWeeklyCompact = $iN->formatCompactNumber($totalWeeklyEarning);
                    ?>
                    <?php if ($currencySymbolPos === 'right'): ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalWeeklyCompact); ?></span>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                    <?php else: ?>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalWeeklyCompact); ?></span>
                    <?php endif; ?>
                </div>
                <div class="revenue_title"><?php echo iN_HelpSecure($LANG['revenue_this_week']); ?></div>
            </div>

            <div class="revenue_box flex_ tabing column">
                <div class="revenue_sum">
                    <?php
                    $totalMonthlyEarning = $iN->iN_CurrentMonthTotalPremiumEarning() + $iN->iN_CurrentMonthTotalSubscriptionEarning() + $iN->iN_CurrentMonthTotalBoostEarning();
                    $totalMonthlyCompact = $iN->formatCompactNumber($totalMonthlyEarning);
                    ?>
                    <?php if ($currencySymbolPos === 'right'): ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalMonthlyCompact); ?></span>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                    <?php else: ?>
                        <?php echo iN_HelpSecure($currencySymbol); ?>
                        <span class="count-num"><?php echo iN_HelpSecure($totalMonthlyCompact); ?></span>
                    <?php endif; ?>
                </div>
                <div class="revenue_title"><?php echo iN_HelpSecure($LANG['revenue_this_month']); ?></div>
            </div>
        </div>
    </div>

    <!-- Gender & Earnings Breakdown -->
    <div class="small_charts_wrapper flex_ tabing">
        <div class="i_contents_section_two flex_ tabing column c5 border_one small_chart_box">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['gender_distribution'] ?? 'Gender distribution'); ?></div>
            <div class="statistic_chart small_chart"><canvas id="genderChart"></canvas></div>
            <script id="genderData" type="application/json">
                <?php
                    $maleCount = (int)$iN->iN_GetTotalUserByGender($userID, 'male');
                    $femaleCount = (int)$iN->iN_GetTotalUserByGender($userID, 'female');
                    $coupleCount = (int)$iN->iN_GetTotalUserByGender($userID, 'couple');
                    $otherCount = 0;
                    echo json_encode([
                        'labels' => [
                            iN_HelpSecure($LANG['male'] ?? 'Male'),
                            iN_HelpSecure($LANG['female'] ?? 'Female'),
                            iN_HelpSecure($LANG['couple'] ?? 'Couple'),
                            iN_HelpSecure($LANG['other'] ?? 'Other')
                        ],
                        'counts' => [$maleCount, $femaleCount, $coupleCount, $otherCount]
                    ]);
                ?>
            </script>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one small_chart_box">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['earnings_breakdown'] ?? 'Earnings breakdown'); ?></div>
            <div class="statistic_chart small_chart"><canvas id="earningsBreakdownChart"></canvas></div>
            <script id="earningsBreakdownData" type="application/json">
                <?php
                    $subTotal = (float)$iN->iN_TotalSubscriptionEarnings($userID);
                    $premiumTotal = (float)$iN->iN_TotalPremiumEarnings($userID);
                    $boostTotal = (float)$iN->iN_TotalBoostEarnings($userID);
                    echo json_encode([
                        'labels' => [
                            iN_HelpSecure($LANG['subscriptions'] ?? 'Subscriptions'),
                            iN_HelpSecure($LANG['premium_earnings'] ?? 'Premium'),
                            iN_HelpSecure($LANG['boost_earnings'] ?? 'Boost')
                        ],
                        'counts' => [$subTotal, $premiumTotal, $boostTotal]
                    ]);
                ?>
            </script>
        </div>
    </div>
    <div class="i_contents_section_three flex_ tabing">
    <!-- New Registered Users -->
    <div class="section_three_item">
        <div class="section_three_box border_one flex_ column">
            <div class="i_box_header flex_ tabing_non_justify">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?>
                <?php echo iN_HelpSecure($LANG['new_registered_users']); ?>
            </div>
            <div class="i_box_container flex_ column">
                <?php
                $latestUsers = $iN->iN_NewRegisteredUsers(); 
                if ($latestUsers) {
                    foreach ($latestUsers as $lu) {
                        $latestUserID = $lu['iuid'] ?? null;
                        $latestUserAvatar = $iN->iN_UserAvatar($latestUserID, $base_url);
                        $latestUserUserName = $lu['i_username'] ?? null;
                        $latestUserFullName = $lu['i_user_fullname'] ?? null;
                        $latestUserRegistered = $lu['registered'] ?? null;
                        $crTime = date('Y-m-d H:i:s', $latestUserRegistered);
                ?>
                <div class="i_box_item border_one transition" id="<?php echo iN_HelpSecure($latestUserID); ?>">
                    <a class="flex_ transition tabing_non_justify" href="<?php echo iN_HelpSecure($base_url) . $iN->iN_Secure($latestUserUserName); ?>">
                        <div class="i_box_item_user_avatar flex_ tabing border_two">
                            <img src="<?php echo iN_HelpSecure($latestUserAvatar); ?>">
                        </div>
                        <div class="i_box_item_user_details">
                            <div class="i_box_item_user_name truncated">
                                <?php echo html_entity_decode($iN->iN_Secure($latestUserFullName)); ?>
                            </div>
                            <div class="i_box_item_user_reg_time_unm">
                                @<?php echo iN_HelpSecure($iN->iN_Secure($latestUserUserName)); ?> &middot; <?php echo TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php } } ?>
            </div>
        </div>
    </div>

    <!-- Latest Subscriptions -->
    <div class="section_three_item">
        <div class="section_three_box border_one flex_ column">
            <div class="i_box_header flex_ tabing_non_justify">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')); ?>
                <?php echo iN_HelpSecure($LANG['latest_subscriptions']); ?>
            </div>
            <div class="i_box_container flex_ column">
                <?php
                $subscriptionUsers = $iN->iN_LatestPaymentsSubscriptionsList(); 
                if ($subscriptionUsers) {
                    foreach ($subscriptionUsers as $payedSub) {
                        $payedSubscriptionID = $payedSub['subscription_id'] ?? null;
                        $payedSubscriberUidFk = $payedSub['iuid_fk'] ?? null;
                        $payedSubscribedUidFk = $payedSub['subscribed_iuid_fk'] ?? null;
                        $payedSubscriberPlanID = $payedSub['plan_id'] ?? null;
                        $payedSubscriberAvatar = $iN->iN_UserAvatar($payedSubscriberUidFk, $base_url);
                        $payedSubscribedAvatar = $iN->iN_UserAvatar($payedSubscribedUidFk, $base_url);
                        $subscribtionStarted = $payedSub['created'] ?? null;
                        $payedAmount = $payedSub['plan_amount'] ?? null;
                        $payedCurrency = strtoupper($payedSub['plan_amount_currency']);
                        $adminEarning = $payedSub['admin_earning'] ?? null;
                        $netEarning = $payedAmount - $adminEarning;
                        $subscriptionStatus = $payedSub['status'];
                        $patedUserData = $iN->iN_GetUserDetails($payedSubscriberUidFk);
                        $payedSubscriberUserName = $patedUserData['i_username'] ?? null;
                        $payedSubscriberUserFullName = $patedUserData['i_user_fullname'] ?? null;
                        $myDateTime = date('d-m-Y', strtotime($subscribtionStarted));
                        $paUserData = $iN->iN_GetUserDetails($payedSubscribedUidFk);
                        $payerSubscriberUserName = $paUserData['i_username'] ?? null;
                        $payerSubscriberUserFullName = $paUserData['i_user_fullname'] ?? null;
                ?>
                <div class="i_box_item border_one flex_ transition tabing_non_justify" id="<?php echo iN_HelpSecure($payedSubscriptionID); ?>">
                    <div class="i_box_item_user_avatar_sub flex_ tabing">
                        <div class="i_subscriber_user_avatar flex_ tabing border_two">
                            <img src="<?php echo iN_HelpSecure($payedSubscriberAvatar); ?>">
                        </div>
                        <div class="i_subsciption_user_avatar border_two flex_ tabing">
                            <img src="<?php echo iN_HelpSecure($payedSubscribedAvatar); ?>">
                        </div>
                    </div>
                    <div class="i_box_item_user_details">
                        <div class="i_box_item_user_name">
                            <?php echo html_entity_decode($iN->iN_TextReaplacement(
                                $LANG['subscribed_u'],
                                [$payedSubscriberUserName, $payedSubscriberUserFullName, $payerSubscriberUserName, $payerSubscriberUserFullName]
                            )); ?>
                        </div>
                        <div class="i_box_item_user_reg_time_unm">&middot; <?php echo iN_HelpSecure($myDateTime); ?></div>
                    </div>
                </div>
                <?php } } ?>
            </div>
        </div>
    </div>

    <!-- Latest Content Payments -->
    <div class="section_three_item">
        <div class="section_three_box border_one flex_ column">
            <div class="i_box_header flex_ tabing_non_justify">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
                <?php echo iN_HelpSecure($LANG['latest_content_payments']); ?>
            </div>
            <div class="i_box_container flex_ column">
                <?php
                $contentPayments = $iN->iN_LatestContentPaymentsList();
                if ($contentPayments) {
                    foreach ($contentPayments as $pay) {
                        $paymentDataID = $pay['payment_id'] ?? null;
                        $paymentDataPayerUserID = $pay['payer_iuid_fk'] ?? null;
                        $paymentDataPayedUserID = $pay['payed_iuid_fk'] ?? null;
                        $paymentDataPayedPostID = $pay['payed_post_id_fk'] ?? null;
                        $postSlug = $iN->iN_GetAllPostDetails($paymentDataPayedPostID);
                        $slug = $postSlug['url_slug'] ?? null;
                        $paymentDataPayedProfileID = $pay['payed_profile_id_fk'] ?? null;
                        $paymentDataOrderKey = $pay['order_key'] ?? null;
                        $paymentDataPaymentType = $pay['payment_type'] ?? null;
                        $paymentDataPaymentOption = $pay['payment_option'] ?? null;
                        $paymentDataPaymentTime = $pay['payment_time'] ?? null;
                        $paymentDataPaymentStatus = $pay['payment_status'] ?? null;
                        $paymentDataPaymentAmount = $pay['amount'] ?? null;
                        $paymentDataPaymentFee = $pay['fee'] ?? null;
                        $paymentDataPaymentAdminEarning = $pay['admin_earning'] ?? null;
                        $paymentDataPaymentUserEarning = $pay['user_earning'] ?? null;
                        $payerUserAvatar = $iN->iN_UserAvatar($paymentDataPayerUserID, $base_url);
                        $payedUserAvatar = $iN->iN_UserAvatar($paymentDataPayedUserID, $base_url);
                        $payerUserData = $iN->iN_GetUserDetails($paymentDataPayerUserID);
                        $payerUserName = $payerUserData['i_username'] ?? null;
                        $payerUserFullName = $payerUserData['i_user_fullname'] ?? null;
                        $paUserData = $iN->iN_GetUserDetails($paymentDataPayedUserID);
                        $payedUserName = $paUserData['i_username'] ?? null;
                        $payedUserFullName = $paUserData['i_user_fullname'] ?? null;
                        $buyTime = date('Y-m-d H:i:s', $paymentDataPaymentTime);
                ?>
                <div class="i_box_item border_one flex_ transition tabing_non_justify" id="<?php echo iN_HelpSecure($paymentDataID); ?>">
                    <div class="i_box_item_user_avatar_sub flex_ tabing">
                        <div class="i_subscriber_user_avatar flex_ tabing border_two">
                            <img src="<?php echo iN_HelpSecure($payerUserAvatar); ?>">
                        </div>
                        <div class="i_subsciption_user_avatar border_two flex_ tabing">
                            <img src="<?php echo iN_HelpSecure($payedUserAvatar); ?>">
                        </div>
                    </div>
                    <div class="i_box_item_user_details">
                        <div class="i_box_item_user_name">
                            <?php echo html_entity_decode($iN->iN_TextReaplacement(
                                $LANG['payedcontent_u'],
                                [$payerUserName, $payerUserFullName, $payedUserName, $payedUserFullName]
                            )); ?>
                        </div>
                        <div class="i_box_item_user_reg_time_unm">&middot; <?php echo TimeAgo::ago($buyTime, date('Y-m-d H:i:s')); ?></div>
                    </div>
                </div>
                <?php } } ?>
            </div>
        </div>
    </div>
</div>
    <?php if ($showAdvancedDashboardCharts): ?>
    <!-- Dashboard Advanced Charts Extension: start -->
    <div class="dashboard_advanced_grid">
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_payment_method_breakdown'] ?? 'Payment method breakdown'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="paymentMethodChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_subscription_health'] ?? 'Subscription health'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="subscriptionHealthChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_new_vs_paying_users'] ?? 'New users vs paying users'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="userConversionChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_revenue_by_payment_type'] ?? 'Revenue by payment type'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="revenueByTypeChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_payout_pipeline'] ?? 'Payout pipeline'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="payoutPipelineChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_content_monetization'] ?? 'Content monetization'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="contentMonetizationChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_creator_funnel'] ?? 'Creator funnel'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="creatorFunnelChart"></canvas></div>
        </div>
        <div class="i_contents_section_two flex_ tabing column c5 border_one dashboard_advanced_card">
            <div class="i_sub_not"><?php echo iN_HelpSecure($LANG['dashboard_content_radar'] ?? 'Content radar'); ?></div>
            <div class="statistic_chart dashboard_advanced_chart"><canvas id="contentRadarChart"></canvas></div>
        </div>
    </div>
    <!-- Dashboard Advanced Charts Extension: end -->
    <?php endif; ?>
<?php  
// Prepare empty arrays for 31 days
$yearMonthTotalySubscriptions = array_fill(0, 31, 0);
$yearMonthTotalPointEarnings  = array_fill(0, 31, 0);

// Query: Daily subscription earnings (ONLY_FULL_GROUP_BY compatible)
$sqlSubs = "
    SELECT 
        DAY(FROM_UNIXTIME(created)) - 1 AS dayIndex, 
        SUM(admin_earning) AS daily_total
    FROM i_user_subscriptions
    WHERE 
        MONTH(FROM_UNIXTIME(created)) = MONTH(CURDATE()) AND
        YEAR(FROM_UNIXTIME(created)) = YEAR(CURDATE()) AND
        status IN('active', 'inactive') AND
        in_status IN('1', '0') AND
        finished = '0' AND
        subscription_scope = 'profile'
    GROUP BY dayIndex
    ORDER BY dayIndex
";

$rowsSubs = DB::all($sqlSubs);
foreach ($rowsSubs as $row) {
    $dayIndex = (int) $row['dayIndex'];
    $dailyTotal = (float) $row['daily_total'];
    $yearMonthTotalySubscriptions[$dayIndex] = $dailyTotal;
}

// Query: Daily point-based earnings (ONLY_FULL_GROUP_BY compatible)
$sqlPoints = "
    SELECT 
        DAY(FROM_UNIXTIME(payment_time)) - 1 AS dayIndex, 
        SUM(admin_earning) AS daily_total
    FROM i_user_payments
    WHERE 
        MONTH(FROM_UNIXTIME(payment_time)) = MONTH(CURDATE()) AND
        YEAR(FROM_UNIXTIME(payment_time)) = YEAR(CURDATE()) AND
        payment_status = 'ok' AND
        admin_earning IS NOT NULL
    GROUP BY dayIndex
    ORDER BY dayIndex
";

$rowsPoints = DB::all($sqlPoints);
foreach ($rowsPoints as $row) {
    $dayIndex = (int) $row['dayIndex'];
    $dailyTotal = (float) $row['daily_total'];
    $yearMonthTotalPointEarnings[$dayIndex] = $dailyTotal;
}

// Helper: Calculate number of days in given month/year
function days_in_month($month, $year)
{
    return $month == 2 
        ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29)))
        : (($month - 1) % 7 % 2 ? 30 : 31);
}
?>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/countNumber/jquery.waypoints.js?v=<?php echo iN_HelpSecure($version);?>"></script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/countNumber/jquery.countup.js?v=<?php echo iN_HelpSecure($version);?>"></script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>admin/<?php echo iN_HelpSecure($adminTheme);?>/js/chartJs/chart.js?v=<?php echo iN_HelpSecure($version);?>"></script>
<input type="hidden" id="earningsCsrf" value="<?php echo iN_HelpSecure(csrf_get_token()); ?>">
<?php
$adminDashboardChartRelative = 'admin/' . $adminTheme . '/js/adminDashboardChart.js';
$adminDashboardChartMtime = dizzy_asset_resolved_mtime($adminDashboardChartRelative);
?>
<script type="text/javascript" src="<?php echo iN_HelpSecure(dizzy_asset_url($adminDashboardChartRelative, (string)$version, $adminDashboardChartMtime !== null ? ('m=' . (string)$adminDashboardChartMtime) : '', false)); ?>"></script>
