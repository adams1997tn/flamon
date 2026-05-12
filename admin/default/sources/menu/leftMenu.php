<?php $userID = isset($userID) ? (int)$userID : 0; ?>
<div class="i_admin_left">
  <div class="i_admin_left_menu_header flex_ tabing_non_justify">
    <div class="ad_le_i flex_ tabing border_two clps"><div class="cl_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('102'));?></div></div>
    <a class="flex_ tabing_non_justify" href="<?php echo iN_HelpSecure($base_url);?>"><img src="<?php echo iN_HelpSecure($siteLogoUrl);?>"><div class="dash_title flex_ tabing lm"><?php echo iN_HelpSecure($LANG['admin_dashboard']);?></div></a>
  </div>
  <?php
  $cmrl = true;
  if (!function_exists('curl_init')) {
      $cmrl = false;
  }

  $buildAdminUrl = static function (string $slug) use ($base_url): string {
      return iN_HelpSecure($base_url) . 'admin/' . $slug;
  };

  $isActive = static function (string $target) use ($pageFor): bool {
      return iN_HelpSecure($pageFor) == $target;
  };

  $isGroupActive = static function (array $targets) use ($pageFor): bool {
      return in_array($pageFor, $targets);
  };

  $reportedPostsTotal = (int)$iN->iN_GetTotalReportedPost($userID);
  $reportedCommentsTotal = (int)$iN->iN_GetTotalReportedComment($userID);
  $reportedMessagesTotal = (int)$iN->iN_GetTotalReportedMessage($userID);

	  $menuSections = [
      [
          'title' => $LANG['admin_menu_overview'],
          'items' => [
              [
                  'type' => 'link',
                  'slug' => 'index',
                  'label' => $LANG['dashboard'],
                  'icon' => '107',
                  'active' => 'index',
              ],
          ],
      ],
      [
          'title' => $LANG['admin_menu_configuration'],
          'items' => [
              [
                  'type' => 'group',
                  'id' => 'settings',
                  'label' => $LANG['settings'],
                  'icon' => '108',
                  'active' => [
                      'website_settings',
                      'general',
                      'limits',
                      'gender_settings',
                      'billing_informations',
                      'affiliate_settings',
                      'creator_bulk_settings',
                      'obs_overlay_settings',
                      'profile_categories_and_subcategories',
                      'boost_package_settings',
                      'robots_txt',
                  ],
                  'children' => [
                      [
                          'slug' => 'website_settings',
                          'label' => $LANG['website_settings'],
                          'active' => 'website_settings',
                      ],
                      [
                          'slug' => 'general',
                          'label' => $LANG['general'],
                          'active' => 'general',
                      ],
                      [
                          'slug' => 'limits',
                          'label' => $LANG['limits'],
                          'active' => 'limits',
                      ],
                      [
                          'slug' => 'gender_settings',
                          'label' => $LANG['gender_settings'],
                          'active' => 'gender_settings',
                      ],
                      [
                          'slug' => 'billing_informations',
                          'label' => $LANG['billing_informations'],
                          'active' => 'billing_informations',
                      ],
                      [
                          'slug' => 'affiliate_settings',
                          'label' => $LANG['affiliate_settings'],
                          'active' => 'affiliate_settings',
                      ],
                      [
                          'slug' => 'creator_bulk_settings',
                          'label' => $LANG['admin_creator_bulk_settings_title'],
                          'active' => 'creator_bulk_settings',
                      ],
                      [
                          'slug' => 'obs_overlay_settings',
                          'label' => $LANG['obs_overlay_admin_settings_menu'],
                          'active' => 'obs_overlay_settings',
                      ],
                      [
                          'slug' => 'profile_categories_and_subcategories',
                          'label' => $LANG['profile_categories_and_subcategories'],
                          'active' => 'profile_categories_and_subcategories',
                      ],
                      [
                          'slug' => 'boost_package_settings',
                          'label' => $LANG['boost_package_settings'],
                          'active' => 'boost_package_settings',
                      ],
                      [
                          'slug' => 'robots_txt',
                          'label' => $LANG['robots_txt'],
                          'active' => 'robots_txt',
                      ],
                  ],
              ],
              [
                  'type' => 'group',
                  'id' => 'design',
                  'label' => $LANG['design'],
                  'icon' => '117',
                  'active' => [
                      'customcolors',
                      'customcssjs',
                      'svgicons',
                      'manage_landing_page',
                      'landing_question_answer',
                      'text_story_backgrounds',
                      'story_audios',
                  ],
                  'children' => [
                      [
                          'slug' => 'customcolors',
                          'label' => $LANG['custom_colors'],
                          'active' => 'customcolors',
                      ],
                      [
                          'slug' => 'customcssjs',
                          'label' => $LANG['custom_css_js'],
                          'active' => 'customcssjs',
                      ],
                      [
                          'slug' => 'svgicons',
                          'label' => $LANG['manageicons'],
                          'active' => 'svgicons',
                      ],
                      [
                          'slug' => 'manage_landing_page',
                          'label' => $LANG['manage_landing_page'],
                          'active' => 'manage_landing_page',
                      ],
                      [
                          'slug' => 'landing_question_answer',
                          'label' => $LANG['landing_question_answer'],
                          'active' => 'landing_question_answer',
                      ],
                      [
                          'slug' => 'text_story_backgrounds',
                          'label' => $LANG['text_story_backgrounds'],
                          'active' => 'text_story_backgrounds',
                      ],
                      [
                          'slug' => 'story_audios',
                          'label' => $LANG['story_audio_library'],
                          'active' => 'story_audios',
                      ],
                  ],
              ],
              [
                  'type' => 'group',
                  'id' => 'storage_setting',
                  'label' => $LANG['storage'],
                  'icon' => '109',
                  'active' => [
                      'storage_settings',
                      'oceansettings',
                      'wasabi_settings',
                      'minio_settings',
                      'bunny_cdn_settings',
                  ],
                  'children' => [
                      [
                          'slug' => 'storage_settings',
                          'label' => $LANG['s3_storage_settings'],
                          'active' => 'storage_settings',
                      ],
                      [
                          'slug' => 'oceansettings',
                          'label' => $LANG['digital_ocean_settings'],
                          'active' => 'oceansettings',
                      ],
                      [
                          'slug' => 'wasabi_settings',
                          'label' => $LANG['wasabi_settings'],
                          'active' => 'wasabi_settings',
                      ],
                      [
                          'slug' => 'minio_settings',
                          'label' => $LANG['minio_settings'],
                          'active' => 'minio_settings',
                      ],
                      [
                          'slug' => 'bunny_cdn_settings',
                          'label' => $LANG['bunny_cdn'],
                          'active' => 'bunny_cdn_settings',
                      ],
                  ],
              ],
          ],
      ],
      [
          'title' => $LANG['admin_menu_content'],
          'items' => [
              [
                  'type' => 'group',
                  'id' => 'manage_posts',
                  'label' => $LANG['manage_posts'],
                  'icon' => '110',
                  'active' => [
                      'awaiting_approval',
                      'allPosts',
                      'premiumPosts',
                      'for_subscribers',
                      'storiePosts',
                      'manage_polls',
                      'scheduled_posts',
                      'manage_campaigns',
                  ],
                  'pulse' => static function () use ($iN): string {
                      return ($iN->iN_CalculateNonApprovedPosts() > 0) ? '<div class="pulse_not"></div>' : '';
                  },
                  'children' => [
                      [
                          'slug' => 'awaiting_approval',
                          'label' => $LANG['awaiting_approval_posts'],
                          'active' => 'awaiting_approval',
                          'counter' => static function () use ($iN): string {
                              if ($iN->iN_CalculateNonApprovedPosts() > 0) {
                                  return '<div class="flex_ tabing counterLeft">' . $iN->iN_CalculateNonApprovedPosts() . '</div>';
                              }

                              return '';
                          },
                          'counterPosition' => 'outside_label',
                      ],
                      [
                          'slug' => 'allPosts',
                          'label' => $LANG['posts'],
                          'active' => 'allPosts',
                      ],
                      [
                          'slug' => 'premiumPosts',
                          'label' => $LANG['premium_posts'],
                          'active' => 'premiumPosts',
                      ],
                      [
                          'slug' => 'for_subscribers',
                          'label' => $LANG['for_subscribers'],
                          'active' => 'for_subscribers',
                      ],
                      [
                          'slug' => 'storiePosts',
                          'label' => $LANG['manage_storie_posts'],
                          'active' => 'storiePosts',
                      ],
                      [
                          'slug' => 'manage_polls',
                          'label' => $LANG['poll_system'],
                          'active' => 'manage_polls',
                      ],
                      [
                          'slug' => 'scheduled_posts',
                          'label' => $LANG['scheduled_badge'],
                          'active' => 'scheduled_posts',
                      ],
                      [
                          'slug' => 'manage_campaigns',
                          'label' => $LANG['campaign_settings'] ?? 'Campaigns',
                          'active' => 'manage_campaigns',
                      ],
                  ],
              ],
              [
                  'type' => 'link',
                  'slug' => 'obs_overlay_list',
                  'label' => $LANG['obs_overlay_admin_list_menu'],
                  'icon' => '194',
                  'active' => 'obs_overlay_list',
              ],
              [
                  'type' => 'link',
                  'slug' => 'blog_posts',
                  'label' => $LANG['blog_management'],
                  'icon' => '110',
                  'active' => 'blog_posts',
              ],
              [
                  'type' => 'link',
                  'slug' => 'pages',
                  'label' => $LANG['pages'],
                  'icon' => '124',
                  'active' => 'pages',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_announcement',
                  'label' => $LANG['manage_announcement'],
                  'icon' => '171',
                  'active' => 'manage_announcement',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_stickers',
                  'label' => $LANG['manage_stickers'],
                  'icon' => '24',
                  'active' => 'manage_stickers',
              ],
              [
                  'type' => 'link',
                  'slug' => 'giphy_settings',
                  'label' => $LANG['giphy_settings'],
                  'icon' => '23',
                  'active' => 'giphy',
              ],
          ],
      ],
      [
          'title' => $LANG['admin_menu_moderation'],
          'items' => [
              [
                  'type' => 'group',
                  'id' => 'reported_posts',
                  'label' => $LANG['reports'],
                  'icon' => '32',
                  'active' => [
                      'reported_posts',
                      'reported_comments',
                      'reported_messages',
                  ],
                  'pulse' => static function () use ($reportedPostsTotal, $reportedCommentsTotal, $reportedMessagesTotal): string {
                      return ($reportedPostsTotal > 0 || $reportedCommentsTotal > 0 || $reportedMessagesTotal > 0) ? '<div class="pulse_not"></div>' : '';
                  },
                  'children' => [
                      [
                          'slug' => 'reported_posts',
                          'label' => $LANG['reported_posts'],
                          'active' => 'reported_posts',
                          'counter' => static function () use ($reportedPostsTotal): string {
                              if ($reportedPostsTotal > 0) {
                                  return '<div class="flex_ tabing counterLeft">' . iN_HelpSecure((string)$reportedPostsTotal) . '</div>';
                              }

                              return '';
                          },
                          'counterPosition' => 'inside_label',
                      ],
                      [
                          'slug' => 'reported_comments',
                          'label' => $LANG['reported_comments'],
                          'active' => 'reported_comments',
                          'counter' => static function () use ($reportedCommentsTotal): string {
                              if ($reportedCommentsTotal > 0) {
                                  return '<div class="flex_ tabing counterLeft">' . iN_HelpSecure((string)$reportedCommentsTotal) . '</div>';
                              }

                              return '';
                          },
                          'counterPosition' => 'inside_label',
                      ],
                      [
                          'slug' => 'reported_messages',
                          'label' => $LANG['reported_messages'],
                          'active' => 'reported_messages',
                          'counter' => static function () use ($reportedMessagesTotal): string {
                              if ($reportedMessagesTotal > 0) {
                                  return '<div class="flex_ tabing counterLeft">' . iN_HelpSecure((string)$reportedMessagesTotal) . '</div>';
                              }

                              return '';
                          },
                          'counterPosition' => 'inside_label',
                      ],
                  ],
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_chats',
                  'label' => $LANG['manage_chats'] ?? $LANG['messages'],
                  'icon' => '92',
                  'active' => 'manage_chats',
              ],
              [
                  'type' => 'link',
                  'slug' => 'community_management',
                  'label' => $LANG['community_management_title'] ?? 'Community Management',
                  'icon' => '193',
                  'active' => 'community_management',
              ],
              [
                  'type' => 'link',
                  'slug' => 'contact_mails',
                  'label' => $LANG['questions_from_users'],
                  'icon' => '96',
                  'active' => 'contact_mails',
                  'counter' => static function () use ($iN): string {
                      if ($iN->iN_CalculateAllUnreadQuestions() != '0') {
                          return '<div class="flex_ tabing counterLeft">' . iN_HelpSecure($iN->iN_CalculateAllUnreadQuestions()) . '</div>';
                      }

                      return '';
                  },
                  'counterPosition' => 'outside_label',
              ],
          ],
      ],
      [
          'title' => $LANG['admin_menu_users_creators'],
          'items' => [
              [
                  'type' => 'group',
                  'id' => 'user',
                  'label' => $LANG['users'],
                  'icon' => '15',
                  'active' => [
                      'manage_users',
                      'creator_requests',
                      'fake_user_generator',
                  ],
                  'pulse' => static function () use ($iN): string {
                      return $iN->iN_TotalVerificationRequests() > 1 ? '<div class="pulse_not"></div>' : '';
                  },
                  'children' => [
                      [
                          'slug' => 'manage_users',
                          'label' => $LANG['manage_users'],
                          'active' => 'manage_users',
                      ],
                      [
                          'slug' => 'creator_requests',
                          'label' => $LANG['creator_requests'],
                          'active' => 'creator_requests',
                          'counter' => static function () use ($iN): string {
                              if ($iN->iN_TotalVerificationRequests() != '0') {
                                  return '<div class="flex_ tabing counterLeft">' . iN_HelpSecure($iN->iN_TotalVerificationRequests()) . '</div>';
                              }

                              return '';
                          },
                          'counterPosition' => 'outside_label',
                      ],
                      [
                          'slug' => 'fake_user_generator',
                          'label' => $LANG['fake_user_generator'],
                          'active' => 'fake_user_generator',
                      ],
                  ],
              ],
              [
                  'type' => 'link',
                  'slug' => 'agencies',
                  'label' => $LANG['agency_module_title'],
                  'icon' => '15',
                  'active' => 'agencies',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_social_profiles',
                  'label' => $LANG['manage_social_profiles'],
                  'icon' => '126',
                  'active' => 'manage_social_profiles',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_website_social_profiles',
                  'label' => $LANG['manage_website_social_profiles'],
                  'icon' => '126',
                  'active' => 'manage_website_social_profiles',
              ],
              [
                  'type' => 'link',
                  'slug' => 'bulk_messages',
                  'label' => $LANG['bulk_messages'],
                  'icon' => '92',
                  'active' => 'bulk_messages',
              ],
          ],
      ],
      [
          'title' => $LANG['admin_menu_monetization'],
          'items' => [
              [
                  'type' => 'link',
                  'slug' => 'transactions',
                  'label' => $LANG['transactions'],
                  'icon' => '179',
                  'active' => 'transactions',
              ],
              [
                  'type' => 'link',
                  'slug' => 'campaign_donations',
                  'label' => $LANG['campaign_donations'],
                  'icon' => '56',
                  'active' => 'campaign_donations',
              ],
              [
                  'type' => 'link',
                  'slug' => 'point_earnings',
                  'label' => $LANG['all_point_earning'],
                  'icon' => '40',
                  'active' => 'point_earnings',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_subscriptions',
                  'label' => $LANG['manage_subscriptions'],
                  'icon' => '51',
                  'active' => 'manage_subscriptions',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_community_subscriptions',
                  'label' => $LANG['manage_community_subscriptions'] ?? 'Community Subscriptions',
                  'icon' => '193',
                  'active' => 'manage_community_subscriptions',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_community_payments',
                  'label' => $LANG['manage_community_payments'] ?? 'Community Payments',
                  'icon' => '42',
                  'active' => 'manage_community_payments',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_products',
                  'label' => $LANG['u_products'],
                  'icon' => '158',
                  'active' => 'manage_products',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_boosted_posts',
                  'label' => $LANG['manage_boosted_posts'],
                  'icon' => '178',
                  'active' => 'manage_boosted_posts',
              ],
              [
                  'type' => 'link',
                  'slug' => 'manage_agency_boosts',
                  'label' => $LANG['manage_agency_boosts'],
                  'icon' => '195',
                  'active' => 'manage_agency_boosts',
              ],
              [
                  'type' => 'group',
                  'id' => 'point',
                  'label' => $LANG['manage_point_feature'],
                  'icon' => '40',
                  'active' => [
                      'manage_point_settings',
                      'manage_point_packages',
                      'manage_point_packages_live',
                      'manage_frame_packages',
                  ],
                  'children' => [
                      [
                          'slug' => 'manage_point_settings',
                          'label' => $LANG['manage_point_settings'],
                          'active' => 'manage_point_settings',
                      ],
                      [
                          'slug' => 'manage_point_packages',
                          'label' => $LANG['point_packages_settings'],
                          'active' => 'manage_point_packages',
                      ],
                      [
                          'slug' => 'manage_point_packages_live',
                          'label' => $LANG['live_point_packages_settings'],
                          'active' => 'manage_point_packages_live',
                      ],
                      [
                          'slug' => 'manage_frame_packages',
                          'label' => $LANG['frame_package_settings'],
                          'active' => 'manage_frame_packages',
                      ],
                  ],
              ],
              [
                  'type' => 'group',
                  'id' => 'ads',
                  'label' => $LANG['advertisement_'],
                  'icon' => '132',
                  'active' => [
                      'create_advertisement',
                      'managa_advertisements',
                      'ads',
                      'adsense',
                  ],
                  'children' => [
                      [
                          'slug' => 'ads',
                          'label' => $LANG['ads_manager'] ?? 'Ads Manager',
                          'active' => 'ads',
                      ],
                      [
                          'slug' => 'create_advertisement',
                          'label' => $LANG['create_advertisement'],
                          'active' => 'create_advertisement',
                      ],
                      [
                          'slug' => 'managa_advertisements',
                          'label' => $LANG['managa_advertisements'],
                          'active' => 'managa_advertisements',
                      ],
                      [
                          'slug' => 'adsense',
                          'label' => $LANG['google_adsense'] ?? 'Google Adsense',
                          'active' => 'adsense',
                      ],
                  ],
              ],
              [
                  'type' => 'group',
                  'id' => 'payments',
                  'label' => $LANG['payment_methods'],
                  'icon' => '42',
                  'active' => [
                      'payment_settings',
                      'paypal',
                      'bitpay',
                      'stripe_subscribtion_settings',
                      'stripe',
                      'authorizenet',
                      'iyzico',
                      'razorpay',
                      'paystack',
                      'flutterwave',
                      'ccbill_subscription_settings',
                      'coinpayment_settings',
                      'mercadopago',
                      'yookassa',
                      'konnect',
                      'epoch',
                      'moneroo',
                      'nowpayments',
                      'paysafecard',
                      'bankpayment',
                      'tax_settings',
                      'invoices',
                  ],
                  'children' => [
                      [
                          'slug' => 'payment_settings',
                          'label' => $LANG['payment_settings'],
                          'active' => 'payment_settings',
                      ],
                      [
                          'slug' => 'tax_settings',
                          'label' => $LANG['tax_settings'],
                          'active' => 'tax_settings',
                      ],
                      [
                          'slug' => 'invoices',
                          'label' => $LANG['manage_invoices'],
                          'active' => 'invoices',
                      ],
                      [
                          'slug' => 'paypal',
                          'label' => $LANG['paypal_payment'],
                          'active' => 'paypal',
                      ],
                      [
                          'slug' => 'stripe_subscribtion_settings',
                          'label' => $LANG['stripe_payment_subs'],
                          'active' => 'stripe_subscribtion_settings',
                      ],
                      [
                          'slug' => 'stripe',
                          'label' => $LANG['stripe_payment'],
                          'active' => 'stripe',
                      ],
                      [
                          'slug' => 'coinpayment_settings',
                          'label' => $LANG['coinpayment_settings'],
                          'active' => 'coinpayment_settings',
                      ],
                      [
                          'slug' => 'authorizenet',
                          'label' => $LANG['authorizenet_payment'],
                          'active' => 'authorizenet',
                      ],
                      [
                          'slug' => 'iyzico',
                          'label' => $LANG['iyzico_payment'],
                          'active' => 'iyzico',
                      ],
                      [
                          'slug' => 'razorpay',
                          'label' => $LANG['razorpay_payment'],
                          'active' => 'razorpay',
                      ],
                      [
                          'slug' => 'paystack',
                          'label' => $LANG['paystack_payment'],
                          'active' => 'paystack',
                      ],
                      [
                          'slug' => 'flutterwave',
                          'label' => $LANG['flutterwave_payment'],
                          'active' => 'flutterwave',
                      ],
                      [
                          'slug' => 'mercadopago',
                          'label' => $LANG['mercadopago_payment'],
                          'active' => 'mercadopago',
                      ],
                      [
                          'slug' => 'yookassa',
                          'label' => $LANG['yookassa_payment'],
                          'active' => 'yookassa',
                      ],
                      [
                          'slug' => 'konnect',
                          'label' => $LANG['konnect_payment'] ?? 'Konnect Network',
                          'active' => 'konnect',
                      ],
                      [
                          'slug' => 'epoch',
                          'label' => $LANG['epoch_payment'],
                          'active' => 'epoch',
                      ],
                      [
                          'slug' => 'moneroo',
                          'label' => $LANG['moneroo_payment'],
                          'active' => 'moneroo',
                      ],
                      [
                          'slug' => 'nowpayments',
                          'label' => $LANG['nowpayments_payment'],
                          'active' => 'nowpayments',
                      ],
                      [
                          'slug' => 'paysafecard',
                          'label' => $LANG['paysafecard_payment'],
                          'active' => 'paysafecard',
                      ],
                      [
                          'slug' => 'ccbill_subscription_settings',
                          'label' => $LANG['ccbill_subscription_settings'],
                          'active' => 'ccbill_subscription_settings',
                      ],
                      [
                          'slug' => 'bankpayment',
                          'label' => $LANG['bankpayment'],
                          'active' => 'bankpayment',
                      ],
                  ],
              ],
              [
                  'type' => 'group',
                  'id' => 'wspayments',
                  'label' => $LANG['manage_payments'],
                  'icon' => '127',
                  'active' => [
                      'manage_withdrawals',
                      'manage_subscription_payments',
                  ],
                  'pulse' => static function () use ($iN): string {
                      return $iN->iN_TotalUsersWithdrawals() > 1 ? '<div class="pulse_not"></div>' : '';
                  },
                  'children' => [
                      [
                          'slug' => 'manage_withdrawals',
                          'label' => $LANG['manage_withdrawals'],
                          'active' => 'manage_withdrawals',
                          'counter' => static function () use ($iN): string {
                              return '<div class="flex_ tabing counterLeft">' . $iN->iN_TotalUsersWithdrawals() . '</div>';
                          },
                          'counterPosition' => 'inside_label',
                      ],
                  ],
              ],
          ],
      ],
      [
          'title' => $LANG['admin_menu_system'],
          'items' => [
              [
                  'type' => 'link',
                  'slug' => 'email_settings',
                  'label' => $LANG['email_settings'],
                  'icon' => '71',
                  'active' => 'email_settings',
              ],
              [
                  'type' => 'link',
                  'slug' => 'live_streaming_settings',
                  'label' => $LANG['live_streaming_settings'],
                  'icon' => '52',
                  'active' => 'live_streaming_settings',
              ],
              [
                  'type' => 'link',
                  'slug' => 'social_logins',
                  'label' => $LANG['social_logins'],
                  'icon' => '126',
                  'active' => 'social_logins',
              ],
              [
                  'type' => 'link',
                  'slug' => 'ai_generator',
                  'label' => $LANG['manage_generate_ai_content'],
                  'icon' => '184',
                  'active' => 'ai_generator',
              ],
              [
                  'type' => 'link',
                  'slug' => 'languages',
                  'label' => $LANG['languages'],
                  'icon' => '1',
                  'active' => 'languages',
              ],
              [
                  'type' => 'link',
                  'slug' => 'age_verification',
                  'label' => $LANG['age_verification_settings_title'],
                  'icon' => '14',
                  'active' => 'age_verification',
              ],
              [
                  'type' => 'link',
                  'slug' => 'license_activation',
                  'label' => $LANG['license_activation'],
                  'active' => 'license_activation',
                  'classes' => 'sub_menu_item transition flex_ tabing_non_justify border_one',
              ],
          ],
	      ],
	  ];

	  if ((string)$userType === '3') {
	      $filteredSections = [];
	      foreach ($menuSections as $section) {
	          $filteredItems = [];
	          foreach ($section['items'] as $item) {
	              if (($item['type'] ?? '') === 'group') {
	                  $children = $item['children'] ?? [];
	                  $filteredChildren = [];
	                  foreach ($children as $child) {
	                      $childSlug = isset($child['slug']) ? (string)$child['slug'] : '';
	                      if ($childSlug !== '' && $iN->iN_CanModeratorAccessAdminPage($userID, $childSlug)) {
	                          $filteredChildren[] = $child;
	                      }
	                  }
	                  if (!empty($filteredChildren)) {
	                      $item['children'] = $filteredChildren;
	                      $item['active'] = array_values(array_map(static function ($child) {
	                          return isset($child['active']) ? (string)$child['active'] : '';
	                      }, $filteredChildren));
	                      $filteredItems[] = $item;
	                  }
	                  continue;
	              }
	              $slug = isset($item['slug']) ? (string)$item['slug'] : '';
	              if ($slug !== '' && $iN->iN_CanModeratorAccessAdminPage($userID, $slug)) {
	                  $filteredItems[] = $item;
	              }
	          }
	          if (!empty($filteredItems)) {
	              $section['items'] = $filteredItems;
	              $filteredSections[] = $section;
	          }
	      }
	      $menuSections = $filteredSections;
	  }

    $menuSearchSuggestions = [];
    $normalizeMenuSearchKey = static function (string $label): string {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($label, 'UTF-8');
        }

        return strtolower($label);
    };
    foreach ($menuSections as $section) {
        foreach ($section['items'] as $item) {
            $itemLabel = trim((string)($item['label'] ?? ''));
            if ($itemLabel !== '') {
                $menuSearchSuggestions[$normalizeMenuSearchKey($itemLabel)] = $itemLabel;
            }
            if (($item['type'] ?? '') === 'group' && !empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    $childLabel = trim((string)($child['label'] ?? ''));
                    if ($childLabel !== '') {
                        $menuSearchSuggestions[$normalizeMenuSearchKey($childLabel)] = $childLabel;
                    }
                }
            }
        }
    }
    $menuSearchSuggestions = array_values($menuSearchSuggestions);
    sort($menuSearchSuggestions, SORT_NATURAL | SORT_FLAG_CASE);

	  $renderLink = static function (array $item, string $classes, bool $withIcon) use ($buildAdminUrl, $isActive, $iN): void {
      $isActiveItem = $isActive($item['active']);
      $classList = $classes . ($isActiveItem ? ' active_p' : '');
      $counterHtml = '';

      if (isset($item['counter']) && is_callable($item['counter'])) {
          $counterHtml = (string) $item['counter']();
      }

      echo '<a href="' . $buildAdminUrl($item['slug']) . '" data-menu-link="1" data-menu-label="' . iN_HelpSecure($item['label']) . '">';
      echo '<div class="' . $classList . '">';

      if ($withIcon && !empty($item['icon'])) {
          echo '<div class="flex_ tabing menu_svg">' . html_entity_decode($iN->iN_SelectedMenuIcon($item['icon'])) . '</div>';
      }

      if ($counterHtml !== '' && ($item['counterPosition'] ?? '') === 'inside_label') {
          echo '<div class="flex_ lm">' . iN_HelpSecure($item['label']) . $counterHtml . '</div>';
      } else {
          echo '<div class="flex_ lm">' . iN_HelpSecure($item['label']) . '</div>';
          echo $counterHtml;
      }

      echo '</div>';
      echo '</a>';
  };

  $renderMenuItem = static function (array $item) use ($renderLink, $isGroupActive, $iN): void {
      if ($item['type'] === 'group') {
          $groupActive = $isGroupActive($item['active']);
          $classes = 'menu_item subCaller flex_ tabing_non_justify transition border_one';

          if ($groupActive) {
              $classes .= ' active_p';
          }

          $pulseHtml = '';
          if (isset($item['pulse']) && is_callable($item['pulse'])) {
              $pulseHtml = (string) $item['pulse']();
          }

          echo '<div class="' . $classes . '" data-id="' . iN_HelpSecure($item['id']) . '" data-menu-group="1" data-menu-label="' . iN_HelpSecure($item['label']) . '">';
          echo '<div class="flex_ tabing menu_svg">' . html_entity_decode($iN->iN_SelectedMenuIcon($item['icon'])) . '</div><div class="flex_ lm">' . iN_HelpSecure($item['label']) . '</div>';
          echo '<div class="sub_menu_arrow">' . html_entity_decode($iN->iN_SelectedMenuIcon('36')) . '</div>';
          echo $pulseHtml;
          echo '</div>';

          $wrapperClasses = 'sub_menu_wrapper border_one flex_ column';
          if ($groupActive) {
              $wrapperClasses .= ' sub_in';
          }

          echo '<div class="' . $wrapperClasses . '" id="' . iN_HelpSecure($item['id']) . '">';
          foreach ($item['children'] as $child) {
              $renderLink($child, 'sub_menu_item transition flex_ tabing_non_justify border_one', false);
          }
          echo '</div>';

          return;
      }

      $classes = $item['classes'] ?? 'menu_item flex_ tabing_non_justify transition border_one';
      $renderLink($item, $classes, !empty($item['icon']));
  };
  ?>
  <div class="admin_menu_search_wrapper">
      <div class="admin_menu_search_field">
          <input
              type="text"
              id="adminMenuSearchInput"
              class="admin_menu_search_input"
              placeholder="<?php echo iN_HelpSecure($LANG['search']); ?>"
              autocomplete="off"
          >
          <button type="button" class="admin_menu_search_clear" id="adminMenuSearchClear" aria-label="<?php echo iN_HelpSecure($LANG['search']); ?>">×</button>
          <div class="admin_menu_autocomplete" id="adminMenuAutocomplete">
              <?php foreach (array_slice($menuSearchSuggestions, 0, 10) as $suggestion) { ?>
                  <button type="button" class="admin_menu_autocomplete_item" data-value="<?php echo iN_HelpSecure($suggestion); ?>">
                      <?php echo iN_HelpSecure($suggestion); ?>
                  </button>
              <?php } ?>
          </div>
      </div>
  </div>
  <div class="i_admin_menu_wrapper flex_ column">
    <?php foreach ($menuSections as $section) { ?>
      <div class="menu_section_title"><?php echo iN_HelpSecure($section['title']); ?></div>
      <?php foreach ($section['items'] as $item) { ?>
        <?php $renderMenuItem($item); ?>
      <?php } ?>
    <?php } ?>
  </div>

  <div class="legal">
    <div class="copyright">
        Copyright © <?php echo date("Y"); ?>   <a href="javascript:void(0);"> <?php echo $siteName; ?> - </a> <?php echo $scriptVersion; ?>
    </div>
</div>
</div>
