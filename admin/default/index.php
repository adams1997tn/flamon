<?php

if ($logedIn == '0') {
    header('Location: ' . route_url(''));
}

if ($pageFor) {
    $normalizedPageFor = preg_replace('/\.php$/i', '', trim((string)$pageFor));
    if ((string)$userType === '3' && !$iN->iN_CanModeratorAccessAdminPage((int)$userID, (string)$normalizedPageFor)) {
        if ((string)$normalizedPageFor === 'index') {
            $fallbackPage = $iN->iN_GetFirstAccessibleModeratorAdminPage((int)$userID);
            if ($fallbackPage !== '' && $fallbackPage !== 'index') {
                header('Location: ' . rtrim((string)$base_url, '/') . '/admin/' . $fallbackPage);
                exit();
            }
        }
        include __DIR__ . '/sources/no_authority.php';
        exit();
    }
    $todayIs = date('j');

    switch ($pageFor) {
        case 'index':
        case 'index.php':
            include __DIR__ . '/sources/main.php';
            break;
        case 'limits':
        case 'limits.php':
            include __DIR__ . '/sources/limits.php';
            break;
        case 'age_verification':
        case 'age_verification.php':
            include __DIR__ . '/sources/age_verification.php';
            break;
        case 'general':
        case 'general.php':
            include __DIR__ . '/sources/general.php';
            break;
        case 'website_settings':
        case 'website_settings.php':
            include __DIR__ . '/sources/website_settings.php';
            break;
        case 'robots_txt':
        case 'robots_txt.php':
            include __DIR__ . '/sources/robots_txt.php';
            break;
        case 'creator_bulk_settings':
        case 'creator_bulk_settings.php':
            include __DIR__ . '/sources/creator_bulk_settings.php';
            break;
        case 'obs_overlay_settings':
        case 'obs_overlay_settings.php':
            include __DIR__ . '/sources/obs_overlay_settings.php';
            break;
        case 'gender_settings':
        case 'gender_settings.php':
            include __DIR__ . '/sources/gender_settings.php';
            break;
        case 'billing_informations':
        case 'billing_informations.php':
            include __DIR__ . '/sources/billing_informations.php';
            break;
        case 'affiliate_settings':
        case 'affiliate_settings.php':
            include __DIR__ . '/sources/affiliate_settings.php';
            break;
        case 'manage_point_settings':
        case 'manage_point_settings.php':
            include __DIR__ . '/sources/manage_point_settings.php';
            break;
        case 'email_settings':
        case 'email_settings.php':
            include __DIR__ . '/sources/email_settings.php';
            break;
        case 'storage_settings':
        case 'storage_settings.php':
            include __DIR__ . '/sources/storage_settings.php';
            break;
        case 'minio_settings':
        case 'minio_settings.php':
            include __DIR__ . '/sources/minio_settings.php';
            break;
        case 'oceansettings':
        case 'oceansettings.php':
            include __DIR__ . '/sources/oceansettings.php';
            break;
        case 'wasabi_settings':
        case 'wasabi_settings.php':
            include __DIR__ . '/sources/wasabi_settings.php';
            break;
        case 'bunny_cdn_settings':
        case 'bunny_cdn_settings.php':
            include __DIR__ . '/sources/bunny_cdn_settings.php';
            break;
        case 'awaiting_approval':
        case 'awaiting_approval.php':
            include __DIR__ . '/sources/awaiting_approval.php';
            break;
        case 'allPosts':
        case 'allPosts.php':
            include __DIR__ . '/sources/allPosts.php';
            break;
        case 'premiumPosts':
        case 'premiumPosts.php':
            include __DIR__ . '/sources/premiumPosts.php';
            break;
        case 'for_subscribers':
        case 'for_subscribers.php':
            include __DIR__ . '/sources/for_subscribers.php';
            break;
        case 'customcssjs':
        case 'customcssjs.php':
            include __DIR__ . '/sources/customcssjs.php';
            break;
        case 'svgicons':
        case 'svgicons.php':
            include __DIR__ . '/sources/svgicons.php';
            break;
        case 'giphy_settings':
        case 'giphy_settings.php':
            include __DIR__ . '/sources/giphy_settings.php';
            break;
        case 'manage_point_packages':
        case 'manage_point_packages.php':
            include __DIR__ . '/sources/manage_point_packages.php';
            break;
        case 'manage_point_packages_live':
        case 'manage_point_packages_live.php':
            include __DIR__ . '/sources/manage_point_packages_live.php';
            break;
        case 'storiePosts':
        case 'storiePosts.php':
            include __DIR__ . '/sources/storiePosts.php';
            break;
        case 'scheduled_posts':
        case 'scheduled_posts.php':
            include __DIR__ . '/sources/scheduled_posts.php';
            break;
        case 'manage_campaigns':
        case 'manage_campaigns.php':
            include __DIR__ . '/sources/manage_campaigns.php';
            break;
        case 'obs_overlay_list':
        case 'obs_overlay_list.php':
            include __DIR__ . '/sources/obs_overlay_list.php';
            break;
        case 'languages':
        case 'languages.php':
            include __DIR__ . '/sources/languages.php';
            break;
        case 'manage_users':
        case 'manage_users.php':
            include __DIR__ . '/sources/manage_users.php';
            break;
        case 'agencies':
        case 'agencies.php':
            include __DIR__ . '/sources/agencies.php';
            break;
        case 'manage_social_profiles':
        case 'manage_social_profiles.php':
            include __DIR__ . '/sources/manage_social_profiles.php';
            break;
        case 'manage_products':
        case 'manage_products.php':
            include __DIR__ . '/sources/manage_products.php';
            break;
        case 'fake_user_generator':
        case 'fake_user_generator.php':
            include __DIR__ . '/sources/fake_user_generator.php';
            break;
        case 'login_as_user':
        case 'login_as_user.php':
            include __DIR__ . '/sources/login_as_user.php';
            break;
        case 'creator_requests':
        case 'creator_requests.php':
            include __DIR__ . '/sources/creator_requests.php';
            break;
        case 'pages':
        case 'pages.php':
            include __DIR__ . '/sources/pages.php';
            break;
        case 'blog_posts':
        case 'blog_posts.php':
            include __DIR__ . '/sources/blog_posts.php';
            break;
        case 'manage_stickers':
        case 'manage_stickers.php':
            include __DIR__ . '/sources/manage_stickers.php';
            break;
        case 'payment_settings':
        case 'payment_settings.php':
            include __DIR__ . '/sources/payment_settings.php';
            break;
        case 'paypal':
        case 'paypal.php':
            include __DIR__ . '/sources/paypal.php';
            break;
        case 'bitpay':
        case 'bitpay.php':
            include __DIR__ . '/sources/bitpay.php';
            break;
        case 'stripe_subscribtion_settings':
        case 'stripe_subscribtion_settings.php':
            include __DIR__ . '/sources/stripe_subscribtion_settings.php';
            break;
        case 'stripe':
        case 'stripe.php':
            include __DIR__ . '/sources/stripe.php';
            break;
        case 'coinpayment_settings':
        case 'coinpayment_settings.php':
            include __DIR__ . '/sources/coinpayment_settings.php';
            break;
        case 'authorizenet':
        case 'authorizenet.php':
            include __DIR__ . '/sources/authorizenet.php';
            break;
        case 'iyzico':
        case 'iyzico.php':
            include __DIR__ . '/sources/iyzico.php';
            break;
        case 'razorpay':
        case 'razorpay.php':
            include __DIR__ . '/sources/razorpay.php';
            break;
        case 'paystack':
        case 'paystack.php':
            include __DIR__ . '/sources/paystack.php';
            break;
        case 'flutterwave':
        case 'flutterwave.php':
            include __DIR__ . '/sources/flutterwave.php';
            break;
        case 'mercadopago':
        case 'mercadopago.php':
            include __DIR__ . '/sources/mercadopago.php';
            break;
        case 'yookassa':
        case 'yookassa.php':
            include __DIR__ . '/sources/yookassa.php';
            break;
        case 'epoch':
        case 'epoch.php':
            include __DIR__ . '/sources/epoch.php';
            break;
        case 'moneroo':
        case 'moneroo.php':
            include __DIR__ . '/sources/moneroo.php';
            break;
        case 'nowpayments':
        case 'nowpayments.php':
            include __DIR__ . '/sources/nowpayments.php';
            break;
        case 'paysafecard':
        case 'paysafecard.php':
            include __DIR__ . '/sources/paysafecard.php';
            break;
        case 'bankpayment':
        case 'bankpayment.php':
            include __DIR__ . '/sources/bankpayment.php';
            break;
        case 'tax_settings':
        case 'tax_settings.php':
            include __DIR__ . '/sources/tax_settings.php';
            break;
        case 'invoices':
        case 'invoices.php':
            include __DIR__ . '/sources/invoices.php';
            break;
        case 'social_logins':
        case 'social_logins.php':
            include __DIR__ . '/sources/social_logins.php';
            break;
        case 'manage_withdrawals':
        case 'manage_withdrawals.php':
            include __DIR__ . '/sources/manage_withdrawals.php';
            break;
        case 'manage_subscription_payments':
        case 'manage_subscription_payments.php':
            include __DIR__ . '/sources/manage_subscription_payments.php';
            break;
        case 'create_advertisement':
        case 'create_advertisement.php':
            include __DIR__ . '/sources/create_advertisement.php';
            break;
        case 'managa_advertisements':
        case 'managa_advertisements.php':
            include __DIR__ . '/sources/managa_advertisements.php';
            break;
        case 'live_streaming_settings':
        case 'live_streaming_settings.php':
            include __DIR__ . '/sources/live_streaming_settings.php';
            break;
        case 'manage_landing_page':
        case 'manage_landing_page.php':
            include __DIR__ . '/sources/manage_landing_page.php';
            break;
        case 'landing_question_answer':
        case 'landing_question_answer.php':
            include __DIR__ . '/sources/landing_question_answer.php';
            break;
        case 'ccbill_subscription_settings':
        case 'ccbill_subscription_settings.php':
            include __DIR__ . '/sources/ccbill_subscription_settings.php';
            break;
        case 'contact_mails':
        case 'contact_mails.php':
            include __DIR__ . '/sources/contact_mails.php';
            break;
        case 'reported_posts':
        case 'reported_posts.php':
            include __DIR__ . '/sources/reported_posts.php';
            break;
        case 'reported_comments':
        case 'reported_comments.php':
            include __DIR__ . '/sources/reported_comments.php';
            break;
        case 'reported_messages':
        case 'reported_messages.php':
            include __DIR__ . '/sources/reported_messages.php';
            break;
        case 'manage_affilate_settings':
        case 'manage_affilate_settings.php':
            include __DIR__ . '/sources/manage_affilate_settings.php';
            break;
        case 'text_story_backgrounds':
        case 'text_story_backgrounds.php':
            include __DIR__ . '/sources/text_story_backgrounds.php';
            break;
        case 'story_audios':
        case 'story_audios.php':
            include __DIR__ . '/sources/story_audios.php';
            break;
        case 'manage_announcement':
        case 'manage_announcement.php':
            include __DIR__ . '/sources/manage_announcement.php';
            break;
        case 'ads':
        case 'ads.php':
            include __DIR__ . '/sources/ads.php';
            break;
        case 'adsense':
        case 'adsense.php':
            include __DIR__ . '/sources/adsense.php';
            break;
        case 'manage_website_social_profiles':
        case 'manage_website_social_profiles.php':
            include __DIR__ . '/sources/manage_website_social_profiles.php';
            break;
        case 'profile_categories_and_subcategories':
        case 'profile_categories_and_subcategories.php':
            include __DIR__ . '/sources/profile_categories_and_subcategories.php';
            break;
        case 'manage_boosted_posts':
        case 'manage_boosted_posts.php':
            include __DIR__ . '/sources/manage_boosted_posts.php';
            break;
        case 'manage_agency_boosts':
        case 'manage_agency_boosts.php':
            include __DIR__ . '/sources/manage_agency_boosts.php';
            break;
        case 'manage_polls':
        case 'manage_polls.php':
            include __DIR__ . '/sources/manage_polls.php';
            break;
        case 'manage_subscriptions':
        case 'manage_subscriptions.php':
            include __DIR__ . '/sources/manage_subscriptions.php';
            break;
        case 'manage_community_subscriptions':
        case 'manage_community_subscriptions.php':
            include __DIR__ . '/sources/manage_community_subscriptions.php';
            break;
        case 'manage_community_payments':
        case 'manage_community_payments.php':
            include __DIR__ . '/sources/manage_community_payments.php';
            break;
        case 'community_management':
        case 'community_management.php':
            include __DIR__ . '/sources/community_management.php';
            break;
        case 'bulk_messages':
        case 'bulk_messages.php':
            include __DIR__ . '/sources/bulk_messages.php';
            break;
        case 'manage_chats':
        case 'manage_chats.php':
            include __DIR__ . '/sources/manage_chats.php';
            break;
        case 'boost_package_settings':
        case 'boost_package_settings.php':
            include __DIR__ . '/sources/boost_package_settings.php';
            break;
        case 'transactions':
        case 'transactions.php':
            include __DIR__ . '/sources/transactions.php';
            break;
        case 'campaign_donations':
        case 'campaign_donations.php':
            include __DIR__ . '/sources/campaign_donations.php';
            break;
        case 'point_earnings':
        case 'point_earnings.php':
            include __DIR__ . '/sources/point_earnings.php';
            break;
        case 'customcolors':
        case 'customcolors.php':
            include __DIR__ . '/sources/customcolors.php';
            break;
        case 'manage_frame_packages':
        case 'manage_frame_packages.php':
            include __DIR__ . '/sources/manage_frame_packages.php';
            break;
        case 'ai_generator':
        case 'ai_generator.php':
            include __DIR__ . '/sources/ai_generator.php';
            break;
        case 'license_activation':
        case 'license_activation.php':
            include __DIR__ . '/sources/license_activation.php';
            break;
        default:
            include __DIR__ . '/sources/main.php';
            break;
    }
}
?>
