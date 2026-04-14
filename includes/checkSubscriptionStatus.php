<?php
require_once('inc.php');
require_once('stripe/vendor/autoload.php');

$stripe = [
  "secret_key"      => $stripeKey,
  "publishable_key" => $stripePublicKey,
];

\Stripe\Stripe::setApiKey($stripeKey);

$rows = DB::all("SELECT * FROM i_user_subscriptions WHERE status IN('active','inactive') AND in_status IN('1','0') AND finished = '0' AND subscription_scope = 'profile'");
if (!empty($rows)) {
    foreach ($rows as $row) {
        $stripeCustomerID = $row['customer_id'];
        $subscriptionID   = $row['payment_subscription_id'];
        $subscriberUser   = (int)$row['iuid_fk'];
        $subscribedUser   = (int)$row['subscribed_iuid_fk'];
        $subscriptionPlanID = $row['plan_id'];

        $customer = \Stripe\Subscription::retrieve($subscriptionID);
        $customerSubscriptionStatus = $customer->status ?? null;
        if (!empty($stripeCustomerID) && !empty($customerSubscriptionStatus)) {
            if ($customerSubscriptionStatus !== 'active') {
                DB::exec("UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = ? AND fr_two = ?", [$subscriberUser, $subscribedUser]);
                DB::exec("UPDATE i_user_subscriptions SET status = 'declined' WHERE subscription_id = ? AND subscription_scope = 'profile'", [(int) $row['subscription_id']]);
            }
        }
    }
} else {
    echo $LANG['something_wrong'];
}
?>
