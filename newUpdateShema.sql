-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 02, 2026 at 12:33 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flamon`
--

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `id` int NOT NULL,
  `owner_user_id` int NOT NULL,
  `title` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `monthly_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `member_limit` int DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `avatar_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `posting_enabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `posting_policy` enum('owner_admin','owner_admin_moderators','members') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'owner_admin',
  `comment_policy` enum('owner_admin','owner_admin_moderators','members') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'members',
  `subscription_required` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `access_policy` enum('members_only','public') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'members_only',
  `moderation_enabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `moderation_timeout_enabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `moderation_timeout_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_categories`
--

CREATE TABLE `community_categories` (
  `id` int NOT NULL,
  `category_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_categories`
--

INSERT INTO `community_categories` (`id`, `category_key`, `status`) VALUES
(1, 'community_cat_art', '1'),
(2, 'community_cat_fitness', '1'),
(3, 'community_cat_music', '1'),
(4, 'community_cat_gaming', '1'),
(5, 'community_cat_education', '1'),
(6, 'community_cat_business', '1'),
(7, 'community_cat_photography', '1'),
(8, 'community_cat_lifestyle', '1'),
(9, 'community_cat_technology', '1'),
(10, 'community_cat_wellness', '1');

-- --------------------------------------------------------

--
-- Table structure for table `community_memberships`
--

CREATE TABLE `community_memberships` (
  `id` int NOT NULL,
  `community_id` int NOT NULL,
  `user_id` int NOT NULL,
  `subscription_id` int DEFAULT NULL,
  `status` enum('active','canceled','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_member_controls`
--

CREATE TABLE `community_member_controls` (
  `id` int NOT NULL,
  `community_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reshare_disabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `post_disabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `comment_disabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `view_until` datetime DEFAULT NULL,
  `view_permanent` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_member_restrictions`
--

CREATE TABLE `community_member_restrictions` (
  `id` int NOT NULL,
  `community_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('blocked','restricted') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_moderators`
--

CREATE TABLE `community_moderators` (
  `id` int NOT NULL,
  `community_id` int NOT NULL,
  `user_id` int NOT NULL,
  `assigned_by` int DEFAULT NULL,
  `can_manage_members` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `can_manage_posts` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `can_manage_comments` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `can_manage_reshare` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `can_manage_view_timeout` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `can_manage_media` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_moderator_actions`
--

CREATE TABLE `community_moderator_actions` (
  `id` int NOT NULL,
  `community_id` int NOT NULL,
  `moderator_id` int NOT NULL,
  `action_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_user_id` int DEFAULT NULL,
  `target_post_id` int DEFAULT NULL,
  `target_comment_id` int DEFAULT NULL,
  `action_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL,
  `reverted_by` int DEFAULT NULL,
  `reverted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int NOT NULL,
  `community_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL,
  `is_hidden` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `hidden_by` int DEFAULT NULL,
  `hidden_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_ads_codes`
--

CREATE TABLE `i_ads_codes` (
  `code_id` int UNSIGNED NOT NULL,
  `placement_id` int UNSIGNED NOT NULL,
  `provider_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_snippet` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` int UNSIGNED NOT NULL,
  `updated_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_ads_placements`
--

CREATE TABLE `i_ads_placements` (
  `placement_id` int UNSIGNED NOT NULL,
  `placement_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placement_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placement_desc` text COLLATE utf8mb4_unicode_ci,
  `placement_status` tinyint(1) NOT NULL DEFAULT '1',
  `inline_frequency` int UNSIGNED NOT NULL DEFAULT '0',
  `inline_offset` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` int UNSIGNED NOT NULL,
  `updated_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_advertisements`
--

CREATE TABLE `i_advertisements` (
  `ads_id` int NOT NULL,
  `ads_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ads_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ads_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ads_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ads_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `ads_type` varchar(85) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ads_location` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_advertisements`
--

INSERT INTO `i_advertisements` (`ads_id`, `ads_image`, `ads_title`, `ads_desc`, `ads_url`, `ads_status`, `ads_type`, `ads_location`) VALUES
(11, 'https://dizzy.dizzyscripts.com/uploads/spImages/2023-03-07/image_1678219051097333000_1.png', 'dizzy - Support Creators Content Script', 'dizzy - Support Creators Content Script', 'https://codecanyon.net/item/dizzy-support-creators-content-script/31263937', '0', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_agencies`
--

CREATE TABLE `i_agencies` (
  `agency_id` int NOT NULL,
  `owner_iuid_fk` int NOT NULL,
  `agency_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `agency_fee` decimal(5,2) NOT NULL DEFAULT '0.00',
  `agency_status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `agency_created_at` int NOT NULL,
  `agency_about` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `agency_services` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `agency_logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agency_cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_boosts`
--

CREATE TABLE `i_agency_boosts` (
  `ab_id` int NOT NULL,
  `agency_id_fk` int NOT NULL,
  `creator_iuid_fk` int NOT NULL,
  `created_by_iuid_fk` int NOT NULL,
  `duration_days` int NOT NULL DEFAULT '0',
  `start_at` int NOT NULL,
  `end_at` int NOT NULL,
  `status` enum('active','expired','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_boost_clicks`
--

CREATE TABLE `i_agency_boost_clicks` (
  `abc_id` int NOT NULL,
  `ab_id_fk` int NOT NULL,
  `agency_id_fk` int NOT NULL,
  `creator_iuid_fk` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clicked_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_create_requests`
--

CREATE TABLE `i_agency_create_requests` (
  `acr_id` int NOT NULL,
  `owner_iuid_fk` int NOT NULL,
  `agency_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `agency_fee` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL,
  `reviewed_by_iuid_fk` int DEFAULT NULL,
  `reviewed_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_members`
--

CREATE TABLE `i_agency_members` (
  `am_id` int NOT NULL,
  `agency_id_fk` int NOT NULL,
  `creator_iuid_fk` int NOT NULL,
  `status` enum('active') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_payouts`
--

CREATE TABLE `i_agency_payouts` (
  `ap_id` int NOT NULL,
  `agency_id_fk` int NOT NULL,
  `owner_iuid_fk` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','rejected','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_requests`
--

CREATE TABLE `i_agency_requests` (
  `ar_id` int NOT NULL,
  `agency_id_fk` int NOT NULL,
  `creator_iuid_fk` int NOT NULL,
  `requested_by` enum('agency','creator') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','approved','rejected','canceled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_agency_social_profiles`
--

CREATE TABLE `i_agency_social_profiles` (
  `asp_id` int NOT NULL,
  `agency_id_fk` int NOT NULL,
  `isw_id_fk` int NOT NULL,
  `s_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_ai_usage`
--

CREATE TABLE `i_ai_usage` (
  `ai_usage_id` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `prompt_length` int NOT NULL DEFAULT '0',
  `model` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temperature` decimal(3,2) NOT NULL DEFAULT '0.80',
  `max_tokens` int NOT NULL DEFAULT '150',
  `variants` int NOT NULL DEFAULT '1',
  `tokens_used` int NOT NULL DEFAULT '0',
  `credit_used` int NOT NULL DEFAULT '0',
  `status` enum('success','error','blocked','rate_limited') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'success',
  `error_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `error_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_announcement`
--

CREATE TABLE `i_announcement` (
  `a_id` int NOT NULL,
  `a_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `a_who_see` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'creators',
  `a_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `a_created_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_announcement_seen`
--

CREATE TABLE `i_announcement_seen` (
  `aid` int NOT NULL,
  `a_id_fk` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `a_seen_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_approve_post_notification`
--

CREATE TABLE `i_approve_post_notification` (
  `approve_id` int NOT NULL,
  `approved_post_id` int DEFAULT NULL,
  `approved_post_owner_id` int DEFAULT NULL,
  `approve_status` enum('1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `approve_not` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `appprove_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_audit_log`
--

CREATE TABLE `i_audit_log` (
  `audit_id` int NOT NULL,
  `actor_iuid_fk` int NOT NULL,
  `action` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_id` int DEFAULT NULL,
  `metadata` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_bank_payments`
--

CREATE TABLE `i_bank_payments` (
  `id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `screen_photo` int DEFAULT NULL,
  `request_time` int DEFAULT NULL,
  `request_status` enum('no','yes','waiting') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_blog_posts`
--

CREATE TABLE `i_blog_posts` (
  `blog_id` int NOT NULL,
  `author_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cover_upload_id` int DEFAULT NULL,
  `cover_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('draft','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `is_featured` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `published_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `allow_reactions` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `view_count` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_blog_reactions`
--

CREATE TABLE `i_blog_reactions` (
  `reaction_id` int NOT NULL,
  `blog_id` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `reaction` enum('like','dislike') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_blog_views`
--

CREATE TABLE `i_blog_views` (
  `view_id` int NOT NULL,
  `blog_id` int NOT NULL,
  `iuid_fk` int NOT NULL DEFAULT '0',
  `ip_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_boosted_posts`
--

CREATE TABLE `i_boosted_posts` (
  `boost_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `post_id_fk` int DEFAULT NULL,
  `boost_type` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `started_at` int DEFAULT NULL,
  `end_at` int DEFAULT NULL,
  `status` enum('no','yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `view_count` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_boosted_post_seen_counter`
--

CREATE TABLE `i_boosted_post_seen_counter` (
  `bp_seen_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `ip` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bp_id_fk` int DEFAULT NULL,
  `bp_seen_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_boost_post_plans`
--

CREATE TABLE `i_boost_post_plans` (
  `plan_id` int NOT NULL,
  `plan_name_key` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_amount` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_icon` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `plan_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `plan_discount` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `view_time` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_boost_post_plans`
--

INSERT INTO `i_boost_post_plans` (`plan_id`, `plan_name_key`, `plan_amount`, `plan_icon`, `plan_status`, `plan_discount`, `amount`, `view_time`) VALUES
(1, 'star', '5', '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n<!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->\n<svg height=\"800px\" width=\"800px\" version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" \n   viewBox=\"0 0 512 512\" xml:space=\"preserve\">\n<path style=\"fill:#D7EBFF;\" d=\"M256,512C114.837,512,0,397.157,0,256S114.837,0,256,0s256,114.843,256,256S397.163,512,256,512z\"/>\n<path style=\"fill:#C4E2FF;\" d=\"M512,256C512,114.843,397.163,0,256,0v512C397.163,512,512,397.157,512,256z\"/>\n<path style=\"fill:#FF9F19;\" d=\"M256,478.609c-122.75,0-222.609-99.864-222.609-222.609S133.25,33.391,256,33.391\n  S478.609,133.256,478.609,256S378.75,478.609,256,478.609z\"/>\n<path style=\"fill:#F28618;\" d=\"M478.609,256c0-122.744-99.859-222.609-222.609-222.609v445.217\n C378.75,478.609,478.609,378.744,478.609,256z\"/>\n<path style=\"fill:#FFFFFF;\" d=\"M326.739,378.831L256,341.646l-70.739,37.185c-5.62,2.951-12.435,2.473-17.587-1.272\n c-5.141-3.733-7.718-10.065-6.642-16.332l13.521-78.761l-57.238-55.781c-4.555-4.434-6.185-11.07-4.228-17.114\n  c1.968-6.044,7.196-10.451,13.478-11.364l79.097-11.494l35.369-71.663c2.806-5.701,8.611-9.309,14.969-9.309\n  s12.163,3.608,14.967,9.309l35.369,71.663l79.097,11.494c6.283,0.913,11.511,5.32,13.478,11.364\n  c1.957,6.044,0.326,12.68-4.228,17.114l-57.239,55.782l13.521,78.761c1.076,6.266-1.5,12.597-6.642,16.332\n  C339.278,381.224,332.481,381.843,326.739,378.831z\"/>\n<path style=\"fill:#EDF0F2;\" d=\"M326.739,378.831c5.742,3.011,12.538,2.392,17.587-1.272c5.141-3.733,7.718-10.065,6.642-16.332\n l-13.521-78.761l57.239-55.782c4.555-4.434,6.185-11.07,4.228-17.114c-1.968-6.044-7.196-10.451-13.478-11.364l-79.097-11.494\n l-35.369-71.663c-2.807-5.7-8.612-9.308-14.97-9.308v235.907L326.739,378.831z\"/>\n</svg>', '1', NULL, '10', '10'),
(2, 'hot', '49', '<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\"><!-- Uploaded to: SVG Repo, www.svgrepo.com, Transformed by: SVG Repo Mixer Tools --><svg height=\"800px\" width=\"800px\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 64 64\" xml:space=\"preserve\" fill=\"#000000\"><g id=\"SVGRepo_bgCarrier\" stroke-width=\"0\"/><g id=\"SVGRepo_tracerCarrier\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><g id=\"SVGRepo_iconCarrier\"> <style type=\"text/css\"> .st0{fill:#e2491c;} .st1{opacity:0.2;} .st2{fill:#231F20;} .st3{fill:#ffc700;} </style> <g id=\"Layer_1\"> <g> <circle class=\"st0\" cx=\"32\" cy=\"32\" r=\"32\"/> </g> <g class=\"st1\"> <path class=\"st2\" d=\"M28.1,58.1c0,0-16.1-2.4-16.1-16.1s21-15,16.4-32c0,0,15.7,4.9,11.9,20.2c0,0,2.1-1.3,3.7-3.9 c0,0,8,6.2,8,15.5s-11,16.2-16.3,16.2c0,0,5.6-7.6,0.5-12.5c-7.3-7-4.2-11.4-4.2-11.4S14.2,42.8,28.1,58.1z\"/> </g> <g> <path class=\"st3\" d=\"M28.1,56.1c0,0-16.1-2.4-16.1-16.1s21-15,16.4-32c0,0,15.7,4.9,11.9,20.2c0,0,2.1-1.3,3.7-3.9 c0,0,8,6.2,8,15.5s-11,16.2-16.3,16.2c0,0,5.6-7.6,0.5-12.5c-7.3-7-4.2-11.4-4.2-11.4S14.2,40.8,28.1,56.1z\"/> </g> </g> <g id=\"Layer_2\"> </g> </g></svg>', '1', NULL, '20', '60'),
(3, 'vip', '99', '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n<!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->\n<svg height=\"800px\" width=\"800px\" version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" \n   viewBox=\"0 0 473.931 473.931\" xml:space=\"preserve\">\n<circle style=\"fill:#D62127;\" cx=\"236.966\" cy=\"236.966\" r=\"236.966\"/>\n<g>\n  <polygon style=\"fill:#CA842A;\" points=\"193.247,188.854 67.464,167.384 162.089,235.08   \"/>\n  <polygon style=\"fill:#CA842A;\" points=\"278.623,188.854 404.409,167.384 309.777,235.08  \"/>\n</g>\n<path style=\"fill:#EDB20F;\" d=\"M404.293,167.384c0,0-88.53,52.115-94.629,46.02c-6.78-6.773-65.922-115.104-74.424-115.141\n  c-8.494,0.041-66.371,108.373-73.155,115.141c-6.092,6.095-94.622-46.02-94.622-46.02C55.576,173.895,130,358.644,130,358.644\n c0,14.784,11.985,26.769,26.765,26.769h78.48h79.748c14.78,0,26.765-11.985,26.765-26.769\n  C341.754,358.644,416.177,173.895,404.293,167.384z\"/>\n<g>\n  <path style=\"fill:#FFC50B;\" d=\"M235.241,98.262c-8.494,0.041-66.371,108.373-73.155,115.141\n    c-6.092,6.095-94.622-46.02-94.622-46.02C55.576,173.895,130,358.644,130,358.644c0,14.784,11.985,26.769,26.765,26.769h78.48\n   V98.262H235.241z\"/>\n  <circle style=\"fill:#FFC50B;\" cx=\"234.597\" cy=\"98.262\" r=\"25.706\"/>\n</g>\n<path style=\"fill:#B8972F;\" d=\"M342.113,358.644c0,14.784-11.985,26.769-26.772,26.769H156.765\n  c-14.78,0-26.765-11.985-26.765-26.769c0,0,61.694-3.674,105.896-3.674C280.101,354.97,342.113,358.644,342.113,358.644z\"/>\n<path style=\"fill:#C4A12E;\" d=\"M235.241,385.416h-78.476c-14.78,0-26.765-11.985-26.765-26.769c0,0,61.694-3.674,105.896-3.674\n  L235.241,385.416z\"/>\n<g style=\"opacity:0.4;\">\n <path style=\"fill:#F6E568;\" d=\"M213.763,103.359c-4.516-4.516,0.438-17.021,4.962-21.541c4.516-4.513,15.45-8.801,19.966-4.284\n    C243.207,82.049,218.283,107.879,213.763,103.359z\"/>\n</g>\n<g>\n <circle style=\"fill:#FFC50B;\" cx=\"404.746\" cy=\"168.679\" r=\"16.898\"/>\n  <path style=\"fill:#FFC50B;\" d=\"M391.051,172.046c-2.967-2.971,0.288-11.195,3.259-14.163c2.967-2.967,10.159-5.785,13.126-2.818\n   C410.408,158.037,394.026,175.017,391.051,172.046z\"/>\n <circle style=\"fill:#FFC50B;\" cx=\"67.165\" cy=\"168.679\" r=\"16.898\"/>\n</g>\n<g style=\"opacity:0.4;\">\n <path style=\"fill:#F6E568;\" d=\"M53.459,172.046c-2.967-2.971,0.292-11.195,3.259-14.163c2.975-2.967,10.159-5.785,13.134-2.818\n    C72.818,158.037,56.433,175.017,53.459,172.046z\"/>\n</g>\n<polygon style=\"fill:#A8213B;\" points=\"234.908,210.298 205.441,262.514 234.908,314.731 264.367,262.514 \"/>\n<polygon style=\"fill:#951D38;\" points=\"205.441,262.514 234.908,314.731 264.367,262.514 \"/>\n<polygon style=\"fill:#C0355A;\" points=\"234.908,210.298 205.441,262.514 234.908,314.731 \"/>\n<polygon style=\"fill:#B72D4C;\" points=\"234.908,262.514 205.441,262.514 234.908,314.731 \"/>\n</svg>', '1', NULL, '500', '280'),
(4, 'royal', '299', '<?xml version=\"1.0\" encoding=\"utf-8\"?><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->\n<svg width=\"800px\" height=\"800px\" viewBox=\"0 0 512 512\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" aria-hidden=\"true\" role=\"img\" class=\"iconify iconify--fxemoji\" preserveAspectRatio=\"xMidYMid meet\"><path fill=\"#FFB636\" d=\"M204.854 309.75l.668 1.748c.208.564.522 1.32.791 2.115l.971 2.795c.38 1.069.689 2.149 1.094 3.4c.386 1.234.843 2.615 1.201 3.973l1.25 4.47c.412 1.558.758 3.122 1.175 4.826a162.186 162.186 0 0 1 3.798 22.366c.426 4.01.607 8.007.696 12.117c.006 4.028-.091 8.159-.468 12.194a101.15 101.15 0 0 1-1.825 11.94a84.231 84.231 0 0 1-3.351 11.379c-2.757 7.354-6.624 14.15-11.647 20.157c-2.485 3.031-5.389 5.734-8.266 8.533c-2.918 2.758-5.904 5.517-9.021 8.191a238.747 238.747 0 0 1-19.729 15.385c-6.908 4.858-14.174 9.358-21.627 13.487a259.059 259.059 0 0 1-11.347 5.865a260.232 260.232 0 0 1-11.582 5.217c-7.784 3.247-15.627 6.066-23.403 8.401a238.5 238.5 0 0 1-22.815 5.681c-14.714 2.934-28.219 4.137-38.257 4.394l-3.589.062l-3.223-.003l-5.218-.139l-3.345-.196l-1.182-.102l-.102-1.182l-.196-3.345l-.139-5.218l-.003-3.223l.062-3.589c.257-10.038 1.459-23.543 4.394-38.257a238.936 238.936 0 0 1 5.68-22.815c2.335-7.776 5.154-15.62 8.401-23.403a260.75 260.75 0 0 1 5.217-11.582c1.86-3.816 3.81-7.611 5.865-11.347c4.129-7.453 8.629-14.719 13.487-21.627a238.532 238.532 0 0 1 15.385-19.729c2.674-3.117 5.432-6.104 8.191-9.021c2.799-2.877 5.502-5.781 8.533-8.266c6.008-5.023 12.803-8.89 20.157-11.647a84.376 84.376 0 0 1 11.379-3.351a101.15 101.15 0 0 1 11.94-1.825c4.035-.377 8.167-.474 12.194-.468c4.11.088 8.106.27 12.117.696a162.035 162.035 0 0 1 22.366 3.798c1.703.416 3.267.763 4.826 1.175l4.47 1.25c1.358.359 2.74.815 3.973 1.201c1.252.404 2.331.714 3.4 1.094l2.795.971c.795.269 1.551.583 2.115.791c1.149.435 1.744.663 1.744.663z\"></path><path fill=\"#FF473E\" d=\"M76.86 181.752L4.346 254.266c-5.012 5.012-1.462 13.582 5.626 13.582h60.945c2.11 0 4.134.838 5.626 2.33l39.572 39.572l64.271-130.328h-97.9a7.952 7.952 0 0 0-5.626 2.33z\"></path><path fill=\"#FF473E\" d=\"M216.161 392.592l39.572 39.572a7.957 7.957 0 0 1 2.33 5.626v60.945c0 7.088 8.57 10.638 13.582 5.626l72.514-72.514a7.957 7.957 0 0 0 2.33-5.626v-97.9l-130.328 64.271z\"></path><path fill=\"#ADBBBC\" d=\"M505.647 17.875l.077.637c.047.419.139 1.065.205 1.862c.131 1.593.428 4.061.569 7.033c.452 6.114.756 14.721.543 25.076c-.207 10.361-.937 22.464-2.519 35.602c-.354 3.326-.859 6.619-1.347 10.041c-.453 3.457-1.098 6.834-1.687 10.373c-1.21 7.047-2.72 14.218-4.463 21.533a361.335 361.335 0 0 1-13.805 44.697c-5.738 15.021-12.624 30.025-20.695 44.6c-8.083 14.563-17.278 28.768-27.555 42.27c-10.263 13.515-21.594 26.34-33.751 38.338c-12.157 11.999-25.124 23.187-38.748 33.341c-3.407 2.537-6.834 5.032-10.338 7.419c-3.466 2.426-7.007 4.746-10.536 7.044c-7.092 4.562-14.334 8.831-21.651 12.837a361.966 361.966 0 0 1-44.669 20.626a372.522 372.522 0 0 1-44.644 13.858c-7.299 1.759-14.432 3.307-21.467 4.528l-5.179.933c-1.716.294-3.457.536-5.147.801l-5.015.765l-4.974.633c-13.067 1.653-25.09 2.463-35.375 2.746c-10.274.294-18.832.04-24.876-.343c-2.942-.111-5.394-.393-6.968-.504c-.789-.058-1.43-.145-1.844-.187l-.631-.07c-4.782-.533-8.409-4.329-8.919-8.919l-.07-.63c-.042-.414-.129-1.055-.187-1.844c-.111-1.574-.393-4.025-.504-6.968c-.382-6.044-.636-14.601-.343-24.876c.283-10.285 1.093-22.308 2.746-35.375l.633-4.974l.765-5.015c.265-1.69.508-3.43.801-5.147l.933-5.179c1.221-7.035 2.769-14.168 4.528-21.467a372.104 372.104 0 0 1 13.858-44.644a362.26 362.26 0 0 1 20.626-44.669c4.007-7.316 8.276-14.559 12.837-21.651c2.298-3.529 4.618-7.07 7.044-10.536c2.387-3.505 4.882-6.931 7.419-10.338c10.154-13.624 21.342-26.592 33.341-38.748c11.998-12.157 24.823-23.488 38.338-33.751c13.502-10.276 27.707-19.472 42.27-27.555c14.575-8.071 29.58-14.957 44.6-20.695a361.335 361.335 0 0 1 44.697-13.805c7.315-1.743 14.486-3.253 21.533-4.463c3.539-.589 6.916-1.234 10.373-1.687c3.422-.488 6.714-.994 10.041-1.347c13.138-1.582 25.241-2.312 35.602-2.519c10.355-.213 18.962.09 25.076.543c2.972.141 5.44.439 7.033.569c.797.066 1.443.157 1.862.205l.637.077c4.769.575 8.37 4.354 8.92 8.919z\"></path><path fill=\"#FF473E\" d=\"M243.106 271.497l.583 1.577c.325.981.763 2.392 1.065 3.964c.716 3.255.953 7.217-.341 10.663c-.638 1.732-1.818 3.176-2.929 4.922l-3.693 5.555l-8.865 13.312a1631.323 1631.323 0 0 1-10.652 15.587a1262.693 1262.693 0 0 1-12.115 17.172l-6.51 8.959l-6.764 9.085c-4.589 6.076-9.285 12.215-14.06 18.274a1161.386 1161.386 0 0 1-14.364 17.97c-4.781 5.885-9.469 11.692-14.083 17.235l-6.769 8.192c-2.242 2.646-4.281 5.389-6.649 7.677c-2.353 2.302-5.068 4.116-8.188 5.376c-3.093 1.287-6.479 2.133-9.753 2.923a195.747 195.747 0 0 1-18.695 3.482c-2.904.397-5.681.709-8.304.944c-2.609.248-5.133.347-7.345.506c-2.229.141-4.357.129-6.135.192c-1.805.036-3.43-.023-4.702-.025c-1.299-.029-2.337-.094-3.016-.117l-1.066-.072l-.072-1.066c-.023-.679-.088-1.718-.117-3.016c-.002-1.272-.061-2.896-.025-4.702c.062-1.779.051-3.906.192-6.135c.158-2.212.258-4.736.506-7.345c.234-2.622.546-5.4.944-8.304a195.747 195.747 0 0 1 3.482-18.695c.79-3.273 1.636-6.659 2.923-9.753c1.26-3.121 3.074-5.835 5.376-8.188c2.288-2.367 5.031-4.406 7.678-6.648l8.192-6.769c5.543-4.614 11.35-9.303 17.235-14.083c5.875-4.79 11.893-9.607 17.97-14.364c6.059-4.776 12.198-9.471 18.274-14.06l9.085-6.764l8.959-6.51a1271.61 1271.61 0 0 1 17.172-12.115c5.5-3.811 10.739-7.375 15.587-10.652l13.312-8.865l5.555-3.693c1.745-1.111 3.189-2.291 4.922-2.929c3.446-1.294 7.408-1.057 10.663-.341c1.572.302 2.983.74 3.964 1.065l1.573.579z\"></path><path fill=\"#2B3B47\" d=\"M362.113 206.66c0 29.917-24.253 54.17-54.17 54.17s-54.17-24.253-54.17-54.17s24.253-54.17 54.17-54.17s54.17 24.253 54.17 54.17zm27.229-115.881c-19.044 0-34.482 15.438-34.482 34.482s15.438 34.482 34.482 34.482s34.482-15.438 34.482-34.482s-15.438-34.482-34.482-34.482z\"></path></svg>', '1', NULL, '1000', '600');

-- --------------------------------------------------------

--
-- Table structure for table `i_bulk_campaigns`
--

CREATE TABLE `i_bulk_campaigns` (
  `bc_id` int NOT NULL,
  `created_by_iuid_fk` int NOT NULL,
  `target_type` enum('all_users','subscribers_of_creator','subscribers_of_agency_creators','followers_of_creator') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_creator_iuid_fk` int DEFAULT NULL,
  `target_agency_id_fk` int DEFAULT NULL,
  `message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `attachment_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `private_price` decimal(10,2) DEFAULT NULL,
  `private_status` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','queued','sending','paused','completed','canceled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `rate_limit_per_run` int NOT NULL DEFAULT '25',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_bulk_campaign_queue`
--

CREATE TABLE `i_bulk_campaign_queue` (
  `bq_id` int NOT NULL,
  `bc_id_fk` int NOT NULL,
  `recipient_iuid_fk` int NOT NULL,
  `status` enum('queued','sent','skipped','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'queued',
  `attempts` int NOT NULL DEFAULT '0',
  `last_error` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_campaigns`
--

CREATE TABLE `i_campaigns` (
  `campaign_id` int NOT NULL,
  `post_id_fk` int NOT NULL,
  `owner_uid_fk` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `goal_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `min_amount` decimal(12,2) DEFAULT NULL,
  `max_amount` decimal(12,2) DEFAULT NULL,
  `raised_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deadline_at` int DEFAULT NULL,
  `cover_upload_id` int DEFAULT NULL,
  `status` enum('pending','active','expired','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_ccbill`
--

CREATE TABLE `i_ccbill` (
  `id` int NOT NULL,
  `subid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_chat_conversations`
--

CREATE TABLE `i_chat_conversations` (
  `con_id` int NOT NULL,
  `chat_id_fk` int DEFAULT NULL,
  `user_one` int DEFAULT NULL,
  `user_two` int DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sticker_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gifurl` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time` int DEFAULT NULL,
  `seen_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `private_price` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `private_status` enum('opened','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'opened',
  `gifMoney` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_chat_users`
--

CREATE TABLE `i_chat_users` (
  `chat_id` int NOT NULL,
  `user_one` int DEFAULT NULL,
  `user_two` int DEFAULT NULL,
  `typing_user_one` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `typing_user_two` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_message_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_comment_reports`
--

CREATE TABLE `i_comment_reports` (
  `p_report_id` int NOT NULL,
  `reported_comment` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `report_time` int NOT NULL DEFAULT '1608301750',
  `report_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `comment_post_id_fk` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_configurations`
--

CREATE TABLE `i_configurations` (
  `configuration_id` int NOT NULL,
  `site` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `site_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `campany` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `post_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `business_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tax_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `tax_rate` decimal(6,4) NOT NULL DEFAULT '0.0000',
  `tax_label` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'VAT',
  `tax_registration_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_company_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tax_invoice_prefix` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'INV',
  `tax_next_invoice_number` bigint NOT NULL DEFAULT '1',
  `site_logo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `site_favicon` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `site_watermark_logo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `active_theme` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'default',
  `admin_active_theme` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'default',
  `version` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `s3_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `s3_bucket` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `s3_region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `s3_secret_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `s3_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ocean_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ocean_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ocean_space_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ocean_region` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ocean_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `minio_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `minio_bucket` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `minio_region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'us-east-1',
  `minio_secret_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `minio_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `minio_endpoint` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `minio_public_base` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `minio_path_style` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '1',
  `minio_ssl_verify` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '1',
  `default_language` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'eng',
  `load_more_limit` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '10',
  `default_currency` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `currency_symbol_position` enum('left','right') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'left',
  `currency_decimal_places` tinyint(1) NOT NULL DEFAULT '2',
  `currency_thousand_separator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'comma',
  `currency_decimal_separator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dot',
  `available_file_extensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `available_verification_file_extensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `available_file_size` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `available_length` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ffmpeg_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `ffmpeg_path` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ffmpeg_probe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pixelSize` int DEFAULT '35',
  `showingNumberOfPost` int DEFAULT '8',
  `showingNumberOfAds` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '1',
  `showingNumberOfSuggestedUser` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `showingNumberOfProduct` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `giphy_api_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `giphy_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `giphy_first_trend_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mycd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mycd_status` int DEFAULT NULL,
  `stripe_status` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '2',
  `stripe_secret_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stripe_public_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stripe_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'USD',
  `stripe_webhook_secret` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sub_weekly_minimum_amount` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sub_monthly_minimum_amount` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sub_yearly_minimum_amount` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pagination_limit` int NOT NULL DEFAULT '30',
  `minimum_withdrawal_amount` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '50',
  `one_point` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0.1',
  `fee` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '20',
  `smtp_or_mail` enum('smtp','mail') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'smtp',
  `smtp_host` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `default_mail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smtp_username` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `smtp_password` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smtp_encryption` enum('tls','ssl') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tls',
  `smtp_port` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '587',
  `siteEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `emailSendStatus` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `default_style` enum('light','dark') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'light',
  `geolocationapikey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_mode` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `register` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `ip_limit` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `max_point_limit` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `min_point_limit` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_point_amount_limit` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `min_point_amount_limit` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `minimum_subscription_amount` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `maximum_subscription_amount` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_login_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `meta_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `free_live_time` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agora_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `agora_app_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `agora_certificate` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `agora_customer_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `rt_provider` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'agora',
  `call_provider` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'agora',
  `isometrik_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `isometrik_api_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `isometrik_project_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isometrik_ws_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `livekit_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `livekit_api_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `livekit_ws_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `minimum_live_streaming_fee` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `landing_page_type` enum('1','2','3','4','5') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `landing_page_plugin` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `landing_first_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_first_image_arrow` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_feature_image_one` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_feature_image_two` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_feature_image_three` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_feature_image_four` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_feature_image_five` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_section_two_bg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `landing_section_feature_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `disallowed_usernames` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `normal_user_can_post` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `user_can_block_country` enum('no','yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `subscription_type` tinyint(1) NOT NULL DEFAULT '1',
  `min_point_fee_weekly` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `min_point_fee_monthly` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `min_point_fee_yearly` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `min_tip_amount` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `free_live_streaming_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `paid_live_streaming_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `g_recaptcha_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'no',
  `g_recaptcha_site_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `g_recaptcha_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `one_signal_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'close',
  `one_signal_api` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `one_signal_rest_api` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `affilate_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'no',
  `minimum_point_transfer_request` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `affilate_amount` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0.10',
  `sub_weekly_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `sub_mountly_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `sub_yearly_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `watermark_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `watermark_text_status` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `use_fullname_or_username` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `auto_approve_post` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `earn_point_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'no',
  `be_a_creator_status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'request',
  `video_call_feature_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'no',
  `who_can_careate_video_call` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `is_video_call_free` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `show_search_result_type` enum('no','yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `load_more_message_limit` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `was_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `was_bucket` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `was_region` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `was_secret_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `was_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bunny_cdn_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bunny_cdn_base` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bunny_storage_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bunny_storage_zone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bunny_storage_access_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bunny_storage_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'storage.bunnycdn.com',
  `enable_disable_drawtext` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `boosted_post_status` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `boost_post_expire_days` int NOT NULL DEFAULT '30',
  `auto_detect_language_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `send__email` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `header_svg_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `header_top_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `left_menu_svg_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `post_section_svg_colors` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `post_icon_colors` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `publish_btn_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `create_live_streamings_btn_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `left_menu_hover_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `left_menu_text_color` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `howManyDaysTrend` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `activity_show_limit` int NOT NULL DEFAULT '1',
  `activity_show_time_limit` int NOT NULL DEFAULT '1',
  `unsubscribe_style` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `auto_follow_admin` enum('no','yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `openai_api_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `open_ai_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `per_ai_use_credit` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `age_confirm` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `reels_feature_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `max_video_duration` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '15',
  `max_point_in_a_day` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '1',
  `poll_system_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `poll_max_options` int NOT NULL DEFAULT '6',
  `poll_min_options` int NOT NULL DEFAULT '2',
  `scheduled_posts_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `scheduled_max_delay_days` int NOT NULL DEFAULT '30',
  `campaign_feature_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `campaign_title_required` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `campaign_goal_required` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `campaign_deadline_required` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `campaign_creator_policy` enum('all','verified','admin_only') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `campaign_goal_min` decimal(12,2) NOT NULL DEFAULT '50.00',
  `campaign_goal_max` decimal(12,2) NOT NULL DEFAULT '100000.00',
  `blog_feature_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `blog_reactions_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `blog_share_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `blog_sidebar_ads_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `story_audio_upload_mode` enum('mp3_only','ffmpeg_mp3') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'mp3_only',
  `creator_bulk_messaging_enabled` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `creator_bulk_daily_limit` int NOT NULL DEFAULT '3',
  `available_bulk_message_file_extensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bulk_message_private_price_min` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bulk_message_private_price_max` decimal(10,2) NOT NULL DEFAULT '0.00',
  `community_creation_policy` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'paid_only',
  `community_free_member_limit` int NOT NULL DEFAULT '10',
  `openai_model` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'gpt-4-turbo',
  `openai_fallback_model` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'gpt-3.5-turbo',
  `openai_temperature` decimal(3,2) NOT NULL DEFAULT '0.80',
  `openai_max_tokens` int NOT NULL DEFAULT '150',
  `openai_prompt_max_length` int NOT NULL DEFAULT '600',
  `openai_rate_limit_per_minute` int NOT NULL DEFAULT '5',
  `openai_rate_limit_per_hour` int NOT NULL DEFAULT '60',
  `openai_rate_limit_per_day` int NOT NULL DEFAULT '200',
  `openai_forbidden_terms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `openai_last_error` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `openai_last_error_at` int DEFAULT NULL,
  `agency_boost_max_active` int NOT NULL DEFAULT '3',
  `agency_boost_default_days` int NOT NULL DEFAULT '7',
  `agency_module_status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'yes',
  `agency_profile_about_status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'yes',
  `agency_profile_services_status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'yes',
  `agency_profile_logo_status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'yes',
  `agency_profile_cover_status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'yes',
  `agency_profile_social_status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'yes',
  `agency_boost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `agency_boost_point_price` int NOT NULL DEFAULT '0',
  `agency_boost_cron_token` varchar(128) COLLATE utf8mb4_general_ci DEFAULT '',
  `story_default_reactions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `age_verif_provider` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ageverif',
  `age_verif_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `age_verif_force_sitewide` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `age_verif_client_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_client_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_authorize_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_token_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_verify_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_scope` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_environment` enum('live','test') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'live',
  `age_verif_client_id_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_client_secret_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_authorize_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_token_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_verify_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_scope_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verif_min_age` int NOT NULL DEFAULT '18',
  `yoti_age_verif_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `yoti_age_verif_force_sitewide` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `yoti_age_verif_client_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_client_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_authorize_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_token_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_verify_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_scope` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_environment` enum('live','test') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'live',
  `yoti_age_verif_client_id_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_client_secret_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_authorize_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_token_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_verify_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_scope_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yoti_age_verif_min_age` int NOT NULL DEFAULT '18',
  `didit_age_verif_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `didit_age_verif_force_sitewide` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `didit_age_verif_client_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_client_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_authorize_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_token_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_verify_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_scope` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_environment` enum('live','test') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'live',
  `didit_age_verif_client_id_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_client_secret_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_authorize_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_token_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_verify_url_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_scope_test` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `didit_age_verif_min_age` int NOT NULL DEFAULT '18',
  `quick_actions_layout` enum('popup','inline') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'popup',
  `trend_hashtags_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `showingNumberOfTrendHashtags` int NOT NULL DEFAULT '10',
  `discovery_feed_status` int NOT NULL DEFAULT '0',
  `jamendo_client_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_configurations`
--

INSERT INTO `i_configurations` (`configuration_id`, `site`, `site_title`, `site_keywords`, `site_description`, `campany`, `country`, `city`, `post_code`, `vat`, `business_address`, `tax_status`, `tax_rate`, `tax_label`, `tax_registration_number`, `tax_company_name`, `tax_company_address`, `tax_invoice_prefix`, `tax_next_invoice_number`, `site_logo`, `site_favicon`, `site_watermark_logo`, `active_theme`, `admin_active_theme`, `version`, `s3_status`, `s3_bucket`, `s3_region`, `s3_secret_key`, `s3_key`, `ocean_key`, `ocean_secret`, `ocean_space_name`, `ocean_region`, `ocean_status`, `minio_status`, `minio_bucket`, `minio_region`, `minio_secret_key`, `minio_key`, `minio_endpoint`, `minio_public_base`, `minio_path_style`, `minio_ssl_verify`, `default_language`, `load_more_limit`, `default_currency`, `currency_symbol_position`, `currency_decimal_places`, `currency_thousand_separator`, `currency_decimal_separator`, `available_file_extensions`, `available_verification_file_extensions`, `available_file_size`, `available_length`, `ffmpeg_status`, `ffmpeg_path`, `ffmpeg_probe`, `pixelSize`, `showingNumberOfPost`, `showingNumberOfAds`, `showingNumberOfSuggestedUser`, `showingNumberOfProduct`, `giphy_api_key`, `giphy_status`, `giphy_first_trend_key`, `mycd`, `mycd_status`, `stripe_status`, `stripe_secret_key`, `stripe_public_key`, `stripe_currency`, `stripe_webhook_secret`, `sub_weekly_minimum_amount`, `sub_monthly_minimum_amount`, `sub_yearly_minimum_amount`, `pagination_limit`, `minimum_withdrawal_amount`, `one_point`, `fee`, `smtp_or_mail`, `smtp_host`, `default_mail`, `smtp_username`, `smtp_password`, `smtp_encryption`, `smtp_port`, `siteEmail`, `emailSendStatus`, `default_style`, `geolocationapikey`, `maintenance_mode`, `register`, `ip_limit`, `max_point_limit`, `min_point_limit`, `max_point_amount_limit`, `min_point_amount_limit`, `minimum_subscription_amount`, `maximum_subscription_amount`, `social_login_status`, `meta_image`, `free_live_time`, `agora_status`, `agora_app_id`, `agora_certificate`, `agora_customer_id`, `rt_provider`, `call_provider`, `isometrik_api_key`, `isometrik_api_secret`, `isometrik_project_id`, `isometrik_ws_url`, `livekit_api_key`, `livekit_api_secret`, `livekit_ws_url`, `minimum_live_streaming_fee`, `landing_page_type`, `landing_page_plugin`, `landing_first_image`, `landing_first_image_arrow`, `landing_feature_image_one`, `landing_feature_image_two`, `landing_feature_image_three`, `landing_feature_image_four`, `landing_feature_image_five`, `landing_section_two_bg`, `landing_section_feature_image`, `disallowed_usernames`, `normal_user_can_post`, `user_can_block_country`, `subscription_type`, `min_point_fee_weekly`, `min_point_fee_monthly`, `min_point_fee_yearly`, `min_tip_amount`, `free_live_streaming_status`, `paid_live_streaming_status`, `g_recaptcha_status`, `g_recaptcha_site_key`, `g_recaptcha_secret_key`, `one_signal_status`, `one_signal_api`, `one_signal_rest_api`, `affilate_status`, `minimum_point_transfer_request`, `affilate_amount`, `sub_weekly_status`, `sub_mountly_status`, `sub_yearly_status`, `watermark_status`, `watermark_text_status`, `use_fullname_or_username`, `auto_approve_post`, `earn_point_status`, `be_a_creator_status`, `video_call_feature_status`, `who_can_careate_video_call`, `is_video_call_free`, `show_search_result_type`, `load_more_message_limit`, `was_status`, `was_bucket`, `was_region`, `was_secret_key`, `was_key`, `bunny_cdn_status`, `bunny_cdn_base`, `bunny_storage_status`, `bunny_storage_zone`, `bunny_storage_access_key`, `bunny_storage_host`, `enable_disable_drawtext`, `boosted_post_status`, `boost_post_expire_days`, `auto_detect_language_status`, `send__email`, `header_svg_color`, `header_top_color`, `left_menu_svg_color`, `post_section_svg_colors`, `post_icon_colors`, `publish_btn_color`, `create_live_streamings_btn_color`, `left_menu_hover_color`, `left_menu_text_color`, `howManyDaysTrend`, `activity_show_limit`, `activity_show_time_limit`, `unsubscribe_style`, `auto_follow_admin`, `openai_api_key`, `open_ai_status`, `per_ai_use_credit`, `age_confirm`, `reels_feature_status`, `max_video_duration`, `max_point_in_a_day`, `poll_system_status`, `poll_max_options`, `poll_min_options`, `scheduled_posts_status`, `scheduled_max_delay_days`, `campaign_feature_status`, `campaign_title_required`, `campaign_goal_required`, `campaign_deadline_required`, `campaign_creator_policy`, `campaign_goal_min`, `campaign_goal_max`, `blog_feature_status`, `blog_reactions_status`, `blog_share_status`, `blog_sidebar_ads_status`, `story_audio_upload_mode`, `creator_bulk_messaging_enabled`, `creator_bulk_daily_limit`, `available_bulk_message_file_extensions`, `bulk_message_private_price_min`, `bulk_message_private_price_max`, `community_creation_policy`, `community_free_member_limit`, `openai_model`, `openai_fallback_model`, `openai_temperature`, `openai_max_tokens`, `openai_prompt_max_length`, `openai_rate_limit_per_minute`, `openai_rate_limit_per_hour`, `openai_rate_limit_per_day`, `openai_forbidden_terms`, `openai_last_error`, `openai_last_error_at`, `agency_boost_max_active`, `agency_boost_default_days`, `agency_module_status`, `agency_profile_about_status`, `agency_profile_services_status`, `agency_profile_logo_status`, `agency_profile_cover_status`, `agency_profile_social_status`, `agency_boost_price`, `agency_boost_point_price`, `agency_boost_cron_token`, `story_default_reactions`, `age_verif_provider`, `age_verif_status`, `age_verif_force_sitewide`, `age_verif_client_id`, `age_verif_client_secret`, `age_verif_authorize_url`, `age_verif_token_url`, `age_verif_verify_url`, `age_verif_scope`, `age_verif_environment`, `age_verif_client_id_test`, `age_verif_client_secret_test`, `age_verif_authorize_url_test`, `age_verif_token_url_test`, `age_verif_verify_url_test`, `age_verif_scope_test`, `age_verif_min_age`, `yoti_age_verif_status`, `yoti_age_verif_force_sitewide`, `yoti_age_verif_client_id`, `yoti_age_verif_client_secret`, `yoti_age_verif_authorize_url`, `yoti_age_verif_token_url`, `yoti_age_verif_verify_url`, `yoti_age_verif_scope`, `yoti_age_verif_environment`, `yoti_age_verif_client_id_test`, `yoti_age_verif_client_secret_test`, `yoti_age_verif_authorize_url_test`, `yoti_age_verif_token_url_test`, `yoti_age_verif_verify_url_test`, `yoti_age_verif_scope_test`, `yoti_age_verif_min_age`, `didit_age_verif_status`, `didit_age_verif_force_sitewide`, `didit_age_verif_client_id`, `didit_age_verif_client_secret`, `didit_age_verif_authorize_url`, `didit_age_verif_token_url`, `didit_age_verif_verify_url`, `didit_age_verif_scope`, `didit_age_verif_environment`, `didit_age_verif_client_id_test`, `didit_age_verif_client_secret_test`, `didit_age_verif_authorize_url_test`, `didit_age_verif_token_url_test`, `didit_age_verif_verify_url_test`, `didit_age_verif_scope_test`, `didit_age_verif_min_age`, `quick_actions_layout`, `trend_hashtags_status`, `showingNumberOfTrendHashtags`, `discovery_feed_status`, `jamendo_client_id`) VALUES
(1, 'FlamonFans', 'FlamonFans- Support Creators Content Script', 'FlamonFans  , creators, content creators, dizzy content creator script, dizzy creators', 'FlamonFans is Creators Content Script. It is best way to start your online Creators platform. It is so fun for all your fans. Just create your contents and start to earn money.', 'dizzy Web', 'TR', 'İstanbul', '2332', 'TR43171', 'Büyük çekmece Istanbul / Turkey', '0', 0.0000, 'VAT', NULL, NULL, NULL, 'INV', 3, 'img/2026-04-24/image_1776988888086759100_1.png', 'img/2026-04-24/image_1776986163087730000_1.png', 'img/2021-03-25/image_1616654975027810500_1.png', 'default', 'default', '5.3.9', '0', '', 'us-west-1', '', '', NULL, NULL, 'dizzyspace', 'nyc3', '0', '0', NULL, 'us-east-1', NULL, NULL, NULL, NULL, '1', '1', 'eng', '30', 'USD', 'left', 2, 'comma', 'dot', 'mp4,MP4,mp3,MP3,mpg,mov,m4v,avi,flv,mpeg,MPEG,webm,mkv,MKV,gif,GIF,jpg,jpeg,JPEG,JPG,PNG,png', 'gif,GIF,jpg,jpeg,JPEG,JPG,PNG,png', '819200', '300', '1', 'C:\\ffmpeg\\bin\\ffmpeg.exe', 'C:\\ffmpeg\\bin\\ffprobe.exe', 35, 3, '4', '4', '4', NULL, '1', 'covid', NULL, 0, '2', 'hjhlkjhlkjhljkhlkjgfdtyrdut', 'gljkhhkuhljljkgujhjlglhbkjhlhkl', 'USD', 'gfkhjgkhjgkkjşjşlkjşkljşjk', '3', '20', '30', 10, '50', '0.1', '20', 'smtp', 'smtp.gmail.com', NULL, NULL, NULL, 'tls', '587', 'youremail@hotmail.com', '0', 'light', NULL, '0', '1', '1', '30000', '5', '5000', '1', '2', '400', '1', 'web.png', '30', '1', NULL, NULL, NULL, 'agora', 'agora', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', '3', NULL, 'img/landingImages/2021-04-26/image_1619434375021155200_1.png', 'img/landingImages/2021-04-26/image_1619435581055944100_1.svg', 'img/landingImages/2021-04-26/image_1619437053087473200_1.png', 'img/landingImages/2021-04-26/image_1619439137001211900_1.png', 'img/landingImages/2021-04-26/image_1619439190051795200_1.png', 'img/landingImages/2021-04-26/image_1619457142083265300_1.png', 'img/landingImages/2021-04-26/image_1619457162024772400_1.png', 'img/landingImages/2021-04-26/image_1619459032043012700_1.png', 'img/landingImages/2021-04-26/image_1619459046047096200_1.png', 'admin, testuser, sex, fuck', 'yes', 'yes', 1, '30', '80', '200', '13', '1', '1', 'no', '', '', 'close', '', '', 'yes', '50', '0.10', 'yes', 'yes', 'yes', 'no', 'no', 'yes', 'no', 'yes', 'request', 'yes', 'yes', 'yes', 'no', '5', '0', 'dizzy', 'eu-west-1', '', '', '0', NULL, '0', NULL, NULL, 'storage.bunnycdn.com', '1', 'yes', 30, '1', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'f8bbd0', '10', '10', 7, 18, 'no', 'yes', NULL, '0', '1', '1', '1', '100', '1', '1', 6, 2, '0', 30, '0', '1', '1', '1', 'all', 50.00, 100000.00, '1', '1', '1', '1', 'ffmpeg_mp3', '1', 3, 'mp4,MP4,mp3,MP3,mpg,mov,m4v,avi,flv,mpeg,MPEG,webm,mkv,MKV,gif,GIF,jpg,jpeg,JPEG,JPG,PNG,png', 0.00, 0.00, 'paid_only', 10, 'gpt-4-turbo', 'gpt-3.5-turbo', 0.80, 150, 600, 5, 60, 200, NULL, NULL, NULL, 3, 7, 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 0.00, 0, '510dc4d75c79574ecd5e2764178eaddf', '❤️,🔥,😂,😮,👏', 'ageverif', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, 'live', NULL, NULL, NULL, NULL, NULL, NULL, 18, '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, 'live', NULL, NULL, NULL, NULL, NULL, NULL, 18, '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, 'live', NULL, NULL, NULL, NULL, NULL, NULL, 18, 'popup', '0', 10, 1, '7a6be82b');

-- --------------------------------------------------------

--
-- Table structure for table `i_configuration_affilate`
--

CREATE TABLE `i_configuration_affilate` (
  `i_af_id` int NOT NULL,
  `i_af_type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `i_af_amount` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `i_af_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes',
  `ica_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_configuration_affilate`
--

INSERT INTO `i_configuration_affilate` (`i_af_id`, `i_af_type`, `i_af_amount`, `i_af_status`, `ica_type`) VALUES
(1, 'register', '54', 'yes', 'af'),
(2, 'comment', '0.1', 'yes', 'po'),
(3, 'post_like', '0.1', 'yes', 'po'),
(4, 'comment_like', '0.1', 'yes', 'po'),
(5, 'new_post', '0.1', 'yes', 'po');

-- --------------------------------------------------------

--
-- Table structure for table `i_contacts`
--

CREATE TABLE `i_contacts` (
  `contact_id` int NOT NULL,
  `contact_full_name` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_email` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contact_time` int DEFAULT NULL,
  `contact_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_read_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_creators`
--

CREATE TABLE `i_creators` (
  `cr_id` int NOT NULL,
  `creator_value` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `creator_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_creators`
--

INSERT INTO `i_creators` (`cr_id`, `creator_value`, `creator_status`) VALUES
(1, 'instagramer', '1'),
(2, 'youtuber', '1'),
(3, 'influencer', '1'),
(4, 'financial_tipper', '1'),
(5, 'designer', '1'),
(6, 'developer', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_creator_auto_messages`
--

CREATE TABLE `i_creator_auto_messages` (
  `cam_id` int NOT NULL,
  `creator_iuid_fk` int NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `attachment_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_creator_auto_message_log`
--

CREATE TABLE `i_creator_auto_message_log` (
  `cml_id` int NOT NULL,
  `creator_iuid_fk` int NOT NULL,
  `visitor_iuid_fk` int NOT NULL,
  `sent_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_custom_codes`
--

CREATE TABLE `i_custom_codes` (
  `custom_id` int NOT NULL,
  `custom_code` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_custom_codes`
--

INSERT INTO `i_custom_codes` (`custom_id`, `custom_code`) VALUES
(1, '/* 1. تفعيل التمرير الناعم السلس (Smooth Scrolling) */\r\nhtml {\r\n  scroll-behavior: smooth;\r\n  /* منع الاهتزاز عند التمرير في الآيفون */\r\n  -webkit-overflow-scrolling: touch;\r\n}\r\n\r\n/* 2. كود شريط التمرير &quot;المختفي والمضيء&quot; (أكثر عصرية) */\r\nbody::-webkit-scrollbar {\r\n  width: 4px !important; /* رفيع جداً كأنه خيط */\r\n}\r\n\r\nbody::-webkit-scrollbar-track {\r\n  background: transparent !important; /* خلفية شفافة تماماً */\r\n}\r\n\r\nbody::-webkit-scrollbar-thumb {\r\n  /* تدرج نيون مضيء يناسب الهوية */\r\n  background: linear-gradient(to bottom, #f65169, #7a22cc) !important;\r\n  border-radius: 20px !important;\r\n  /* إضافة توهج خفيف (Glow) */\r\n  box-shadow: 0 0 10px rgba(246, 81, 105, 0.8);\r\n}\r\n\r\n/* 3. تحسين اللمس للمساحات الكبيرة (Cards &amp; Images) */\r\n* {\r\n  /* منع ظهور اللون الأزرق عند النقر على الأزرار في الأندرويد */\r\n  -webkit-tap-highlight-color: transparent;\r\n  /* منع اختيار النصوص بالخطأ أثناء المسح (Swipe) */\r\n  -webkit-touch-callout: none;\r\n}\r\n\r\n/* 4. كود يجعل الصور والمنشورات تتوقف في مكانها عند المسح (Snap Effect) */\r\n/* طبقه على حاوية المنشورات (Container) */\r\n.posts-container {\r\n  display: flex;\r\n  overflow-x: auto;\r\n  scroll-snap-type: x mandatory; /* يجعله يقفز للمنشور التالي بدقة */\r\n}\r\n\r\n.post-card {\r\n  scroll-snap-align: start;\r\n}\r\n\r\n/* 5. إضافة تأثير &quot;الزجاج&quot; للمساحات الفارغة (Glassmorphism) */\r\n.glass-effect {\r\n  background: rgba(255, 255, 255, 0.03) !important;\r\n  backdrop-filter: blur(15px) !important;\r\n  -webkit-backdrop-filter: blur(15px) !important;\r\n  border: 1px solid rgba(255, 255, 255, 0.1);\r\n}'),
(2, '/* \r\nThis file is intentionally left blank.\r\nIf the admin adds custom JS via the panel, it will be written here dynamically.\r\nThis helps maintain separation of logic and avoid inline scripts.\r\nAdd here your JavaScript Code. Note. the code entered here will be added in  tag  Example:  var x, y, z;  x = 5;  y = 6;  z = x + y;\r\n*/'),
(3, '/* The code entered here will be added in  tag */');

-- --------------------------------------------------------

--
-- Table structure for table `i_epoch_webhook_events`
--

CREATE TABLE `i_epoch_webhook_events` (
  `event_id` int NOT NULL,
  `event_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `order_key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verification_status` enum('verified','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'verified',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_frames`
--

CREATE TABLE `i_frames` (
  `f_id` int NOT NULL,
  `f_file` text,
  `f_price` varchar(55) NOT NULL DEFAULT '0',
  `f_frame_status` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `i_frames`
--

INSERT INTO `i_frames` (`f_id`, `f_file`, `f_price`, `f_frame_status`) VALUES
(1, 'img/frames/2023-10-18/image_1697633975025466700_1.png', '1011', '1'),
(2, 'img/frames/frame2.png', '10', '1'),
(3, 'img/frames/frame3.png', '20', '1'),
(4, 'img/frames/frame4.png', '20', '1'),
(5, 'img/frames/frame5.png', '10', '1'),
(6, 'img/frames/frame6.png', '10', '1'),
(7, 'img/frames/frame7.png', '40', '1'),
(8, 'img/frames/frame8.png', '30', '1'),
(9, 'img/frames/frame9.png', '10', '1'),
(11, 'img/frames/frame11.png', '10', '1'),
(12, 'img/frames/frame12.png', '10', '1'),
(13, 'img/frames/2023-10-19/image_1697706828069483200_1.png', '300', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_friends`
--

CREATE TABLE `i_friends` (
  `fr_id` int NOT NULL,
  `fr_one` int DEFAULT NULL,
  `fr_two` int DEFAULT NULL,
  `fr_time` int DEFAULT NULL,
  `fr_status` enum('me','flwr','subscriber') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'me'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_friends`
--

INSERT INTO `i_friends` (`fr_id`, `fr_one`, `fr_two`, `fr_time`, `fr_status`) VALUES
(1, 1, 1, 1618053393, 'me'),
(2, 2, 2, 1776617939, 'me'),
(3, 3, 3, 1776617939, 'me'),
(4, 4, 4, 1776617939, 'me'),
(5, 5, 5, 1776617939, 'me'),
(6, 6, 6, 1776617939, 'me'),
(7, 7, 7, 1776617939, 'me'),
(8, 8, 8, 1776617939, 'me'),
(9, 9, 9, 1776617939, 'me'),
(10, 10, 10, 1776617939, 'me'),
(11, 11, 11, 1776617939, 'me'),
(13, 12, 12, 1776990821, 'me'),
(14, 13, 13, 1776990821, 'me'),
(15, 14, 14, 1776990821, 'me'),
(16, 15, 15, 1776990821, 'me'),
(17, 16, 16, 1776990821, 'me'),
(18, 17, 17, 1776990821, 'me'),
(19, 18, 18, 1776990821, 'me'),
(20, 19, 19, 1776990821, 'me'),
(21, 20, 20, 1776990821, 'me'),
(22, 21, 21, 1776990821, 'me'),
(23, 22, 22, 1776990827, 'me'),
(24, 23, 23, 1776990827, 'me'),
(26, 1, 3, 1777660744, 'flwr'),
(28, 1, 21, 1777665788, 'flwr');

-- --------------------------------------------------------

--
-- Table structure for table `i_landing_qa`
--

CREATE TABLE `i_landing_qa` (
  `qa_id` int NOT NULL,
  `qa_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `qa_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `qa_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_landing_qa`
--

INSERT INTO `i_landing_qa` (`qa_id`, `qa_title`, `qa_description`, `qa_status`) VALUES
(1, 'How much can I earn?', 'Start making an income on dizzy regardless of how big your audience is. Thousands of creators make their first money on dizzy and some have earned more than $100k in donations alone!', '1'),
(2, 'What currencies do you offer?', 'You can take donations or payments in most currencies, but you can save on any currency conversion fees and receive support directly by choosing a base currency of USD, CAD, EUR, GBP, AUS, IRD, YEN or THB.', '1'),
(3, 'How do I get paid?', 'To be paid, your earnings must be at least $50. You can claim money when you earn $50. Payments will be transferred to your requested account within 3 business days.', '1'),
(4, 'How can I get in touch with you?', 'You can reach us from the contact page.', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_langs`
--

CREATE TABLE `i_langs` (
  `lang_id` int NOT NULL,
  `lang_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lang_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_langs`
--

INSERT INTO `i_langs` (`lang_id`, `lang_name`, `lang_status`) VALUES
(1, 'eng', '1'),
(2, 'tr', '0'),
(3, 'de', '0'),
(4, 'fr', '1'),
(5, 'es', '0');

-- --------------------------------------------------------

--
-- Table structure for table `i_license_state`
--

CREATE TABLE `i_license_state` (
  `id` int NOT NULL,
  `license_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license_status` enum('inactive','active','grace','locked','revoked','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'inactive',
  `last_check_at` int NOT NULL DEFAULT '0',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL,
  `pending_state` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pending_nonce_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pending_expires_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_license_state`
--

INSERT INTO `i_license_state` (`id`, `license_token`, `license_status`, `last_check_at`, `created_at`, `updated_at`, `pending_state`, `pending_nonce_hash`, `pending_expires_at`) VALUES
(1, 'dsqdqsdsqdsqdsq', 'active', 0, 1776207580, 1776207580, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_live`
--

CREATE TABLE `i_live` (
  `live_id` int NOT NULL,
  `live_uid_fk` int DEFAULT NULL,
  `live_channel` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `live_time` int DEFAULT NULL,
  `live_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `started_at` int DEFAULT NULL,
  `finish_time` int DEFAULT NULL,
  `scheduled_at` int DEFAULT NULL,
  `scheduled_status` enum('scheduled','live','cancelled','finished') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'live',
  `notify_live` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `notify_audience` enum('followers','subscribers','selected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'followers',
  `notify_selected_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `scheduled_post_id` int DEFAULT NULL,
  `a_resource_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `a_sid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `live_credit` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `live_type` enum('free','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'free',
  `live_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `extended_seconds` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_live`
--

INSERT INTO `i_live` (`live_id`, `live_uid_fk`, `live_channel`, `live_time`, `live_status`, `started_at`, `finish_time`, `scheduled_at`, `scheduled_status`, `notify_live`, `notify_audience`, `notify_selected_ids`, `scheduled_post_id`, `a_resource_id`, `a_sid`, `live_credit`, `live_type`, `live_name`, `extended_seconds`) VALUES
(1, 8, 'stream_8_2591352', NULL, '0', 1776621914, 1776623714, NULL, 'live', '0', 'followers', NULL, NULL, NULL, NULL, NULL, 'free', 'cvxvxw', 0);

-- --------------------------------------------------------

--
-- Table structure for table `i_live_chat`
--

CREATE TABLE `i_live_chat` (
  `cm_id` int NOT NULL,
  `cm_live_id` int DEFAULT NULL,
  `cm_iuid_fk` int DEFAULT NULL,
  `cm_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cm_gift_type` int DEFAULT NULL,
  `cm_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_gift_point`
--

CREATE TABLE `i_live_gift_point` (
  `gift_id` int NOT NULL,
  `gift_name` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gift_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gift_point` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gift_money_equal` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gift_money_animation_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gift_status` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `gift_created_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_live_gift_point`
--

INSERT INTO `i_live_gift_point` (`gift_id`, `gift_name`, `gift_image`, `gift_point`, `gift_money_equal`, `gift_money_animation_image`, `gift_status`, `gift_created_time`) VALUES
(1, 'Summer Sun', 'img/gifts/2022-01-05/image_1641373275058067900_1.png', '50', '5', 'img/gifts/2022-01-05/image_1641373278045449300_1.png', '1', NULL),
(2, 'Footboll', 'img/gifts/2022-01-05/image_1641373292099579300_1.png', '2', '0.2', 'img/gifts/2022-01-05/image_1641373295080007800_1.png', '1', NULL),
(3, 'Hi', 'img/gifts/2022-01-05/image_1641373313099910200_1.png', '20', '2', 'img/gifts/2022-01-05/image_1641373316058482100_1.png', '1', NULL),
(4, 'Hand Wave', 'img/gifts/2022-01-05/image_1641373332091235800_1.png', '100', '10', 'img/gifts/2022-01-05/image_1641373335021366100_1.png', '1', NULL),
(5, 'GG', 'img/gifts/2022-01-05/image_1641373357063519200_1.png', '1', '0.1', 'img/gifts/2022-01-05/image_1641373361016990100_1.png', '1', NULL),
(6, 'Gamepad', 'img/gifts/2022-01-05/image_1641373392087261200_1.png', '100', '10', 'img/gifts/2022-01-05/image_1641373411065573600_1.png', '1', NULL),
(7, 'Mini Speaker', 'img/gifts/2022-01-05/image_1641373424084594500_1.png', '70', '7', 'img/gifts/2022-01-05/image_1641373427038688900_1.png', '1', NULL),
(8, 'Doughnut', 'img/gifts/2022-01-05/image_1641373451014749900_1.png', '300', '30', 'img/gifts/2022-01-05/image_1641373453098567700_1.png', '1', NULL),
(9, 'Ice Cream Cone', 'img/gifts/2022-01-05/image_1641373494065582500_1.png', '500', '50', 'img/gifts/2022-01-05/image_1641373498022567400_1.png', '1', NULL),
(10, 'Weights', 'img/gifts/2022-01-05/image_1641373514055374800_1.png', '30', '3', 'img/gifts/2022-01-05/image_1641373517022073800_1.png', '1', NULL),
(11, 'Rose', 'img/gifts/2022-01-05/image_1641373552028718600_1.png', '700', '70', 'img/gifts/2022-01-05/image_1641373554087286800_1.png', '1', NULL),
(12, 'Lollipop', 'img/gifts/2022-01-05/image_1641373586005712900_1.png', '9000', '900', 'img/gifts/2022-01-05/image_1641373589010489100_1.png', '1', NULL),
(13, 'Finger Heart', 'img/gifts/2022-01-05/image_1641373602047560700_1.png', '1000', '100', 'img/gifts/2022-01-05/image_1641373605061950500_1.png', '1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_live_likes`
--

CREATE TABLE `i_live_likes` (
  `like_id` int NOT NULL,
  `live_id_fk` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `like_time` int DEFAULT NULL,
  `user_ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_pinned_products`
--

CREATE TABLE `i_live_pinned_products` (
  `id` int NOT NULL,
  `live_id` int NOT NULL,
  `product_id` int NOT NULL,
  `pinned_by` int NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_polls`
--

CREATE TABLE `i_live_polls` (
  `poll_id` int NOT NULL,
  `live_id` int NOT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('active','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_by` int NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_poll_options`
--

CREATE TABLE `i_live_poll_options` (
  `option_id` int NOT NULL,
  `poll_id_fk` int NOT NULL,
  `option_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `option_order` int NOT NULL DEFAULT '0',
  `votes_count` int NOT NULL DEFAULT '0',
  `status` enum('active','deleted') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_poll_votes`
--

CREATE TABLE `i_live_poll_votes` (
  `vote_id` int NOT NULL,
  `poll_id_fk` int NOT NULL,
  `option_id_fk` int NOT NULL,
  `voter_uid_fk` int NOT NULL,
  `voter_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vote_time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_questions`
--

CREATE TABLE `i_live_questions` (
  `question_id` int NOT NULL,
  `live_id` int NOT NULL,
  `user_id` int NOT NULL,
  `question_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('pending','approved','answered','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL,
  `answered_by` int DEFAULT NULL,
  `answered_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_reminders`
--

CREATE TABLE `i_live_reminders` (
  `id` int NOT NULL,
  `live_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL,
  `email_sent_at` int DEFAULT NULL,
  `email_attempts` int NOT NULL DEFAULT '0',
  `email_last_attempt` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_live_video_users`
--

CREATE TABLE `i_live_video_users` (
  `live_user_id` int NOT NULL,
  `live_user_uid_fk` int DEFAULT NULL,
  `live_time` int DEFAULT NULL,
  `live_video_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `i_mentions`
--

CREATE TABLE `i_mentions` (
  `m_id` int NOT NULL,
  `m_uid_fk` int DEFAULT NULL,
  `m_user_owner` int DEFAULT NULL,
  `m_post_id_fk` int DEFAULT NULL,
  `m_type` varchar(25) DEFAULT NULL,
  `mention_type` enum('comment','post','chat') NOT NULL,
  `m_status` enum('0','1') NOT NULL DEFAULT '0',
  `m_time` int DEFAULT '1524910573'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `i_message_reports`
--

CREATE TABLE `i_message_reports` (
  `p_report_id` int NOT NULL,
  `reported_message` int DEFAULT NULL,
  `reported_chat_id_fk` int DEFAULT NULL,
  `reported_message_owner_id` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `report_time` int NOT NULL DEFAULT '1608301750',
  `report_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `admin_action_time` int DEFAULT NULL,
  `admin_action_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_moderator_permissions`
--

CREATE TABLE `i_moderator_permissions` (
  `id` int NOT NULL,
  `moderator_id` int NOT NULL,
  `permission_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `permission_value` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `updated_by` int DEFAULT NULL,
  `created_at` int NOT NULL DEFAULT '0',
  `updated_at` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_obs_overlays`
--

CREATE TABLE `i_obs_overlays` (
  `obs_overlay_id` int NOT NULL,
  `creator_id_fk` int NOT NULL,
  `overlay_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `enabled_widgets` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `donation_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'last24h',
  `milestone_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `milestone_goal_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cta_label` varchar(140) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cta_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `watermark_text` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL,
  `layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `styles_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_obs_overlay_clicks`
--

CREATE TABLE `i_obs_overlay_clicks` (
  `obs_overlay_click_id` int NOT NULL,
  `obs_overlay_id_fk` int NOT NULL,
  `ip_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_obs_support_events`
--

CREATE TABLE `i_obs_support_events` (
  `id` int NOT NULL,
  `overlay_id` int NOT NULL,
  `creator_id` int NOT NULL,
  `payer_id` int DEFAULT NULL,
  `payer_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_id` int DEFAULT NULL,
  `payment_type` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` int NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `processed_for_extension` tinyint NOT NULL DEFAULT '0',
  `processed_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_pages`
--

CREATE TABLE `i_pages` (
  `page_id` int NOT NULL,
  `page_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `page_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `page_created_time` int DEFAULT NULL,
  `page_inside` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `page_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_pages`
--

INSERT INTO `i_pages` (`page_id`, `page_title`, `page_name`, `page_created_time`, `page_inside`, `page_status`) VALUES
(1, 'About Us', 'about-us', 1605744000, '<p><strong>1- Write about your website here.</strong></p>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. </p>\r\n\r\n<p><strong>2- Random title</strong></p>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dxzcolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>\r\n', '1'),
(2, 'Privacy Policies', 'privacy-policies', 1605744000, '<h2>Who we are?</h2>\r\n\r\n<p>Provide name and contact details of the data controller. This will typically be your business or you, if you are a sole trader. Where applicable, you should include the identity and contact details of the controller&rsquo;s representative and/or the data protection officer.</p>\r\n\r\n<h2>What information do we collect?</h2>\r\n\r\n<p>Specify the types of personal information you collect, eg names, addresses, user names, etc. You should include specific details on: how you collect data (eg when a user registers, purchases or uses your services, completes a contact form, signs up to a newsletter, etc) what specific data you collect through each of the data collection method if you collect data from third parties, you must specify categories of data and source if you process sensitive personal data or financial information, and how you handle this&nbsp;<br />\r\n<br />\r\nYou may want to provide the user with relevant definitions in relation to personal data and sensitive personal data.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h2>How do we use personal information?</h2>\r\n\r\n<p>Describe in detail all the service- and business-related purposes for which you will process data. For example, this may include things like: personalisation of content, business information or user experience account set up and administration delivering marketing and events communication carrying out polls and surveys internal research and development purposes providing goods and services legal obligations (eg prevention of fraud) meeting internal audit requirements&nbsp;<br />\r\n<br />\r\nPlease note this list is not exhaustive. You will need to record all purposes for which you process personal data.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h2>What legal basis do we have for processing your personal data?</h2>\r\n\r\n<p>Describe the relevant processing conditions contained within the GDPR. There are six possible legal grounds: consent contract legitimate interests vital interests public task legal obligation&nbsp;<br />\r\n<br />\r\nProvide detailed information on all grounds that apply to your processing, and why. If you rely on consent, explain how individuals can withdraw and manage their consent. If you rely on legitimate interests, explain clearly what these are.&nbsp;<br />\r\n<br />\r\nIf you&rsquo;re processing special category personal data, you will have to satisfy at least one of the six processing conditions, as well as additional requirements for processing under the GDPR. Provide information on all additional grounds that apply.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h2>When do we share personal data?</h2>\r\n\r\n<p>Explain that you will treat personal data confidentially and describe the circumstances when you might disclose or share it. Eg, when necessary to provide your services or conduct your business operations, as outlined in your purposes for processing. You should provide information on: how you will share the data what safeguards you will have in place what parties you may share the data with and why</p>\r\n\r\n<h2>Where do we store and process personal data?</h2>\r\n\r\n<p>If applicable, explain if you intend to store and process data outside of the data subject&rsquo;s home country. Outline the steps you will take to ensure the data is processed according to your privacy policy and the applicable law of the country where data is located. If you transfer data outside the European Economic Area, outline the measures you will put in place to provide an appropriate level of data privacy protection. Eg contractual clauses, data transfer agreements, etc.</p>\r\n\r\n<h2>How do we secure personal data?</h2>\r\n\r\n<p>Describe your approach to data security and the technologies and procedures you use to protect personal information. For example, these may be measures: to protect data against accidental loss to prevent unauthorised access, use, destruction or disclosure to ensure business continuity and disaster recovery to restrict access to personal information to conduct privacy impact assessments in accordance with the law and your business policies to train staff and contractors on data security to manage third party risks, through use of contracts and security reviews&nbsp;<br />\r\n<br />\r\nPlease note this list is not exhaustive. You should record all mechanisms you rely on to protect personal data. You should also state if your organisation adheres to certain accepted standards or regulatory requirements.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h2>How long do we keep your personal data for?</h2>\r\n\r\n<p>Provide specific information on the length of time you will keep the information for in relation to each processing purpose. The GDPR requires you to retain data for no longer than reasonably necessary. Include details of your data or records retention schedules, or link to additional resources where these are published.&nbsp;<br />\r\n<br />\r\nIf you cannot state a specific period, you need to set out the criteria you will apply to determine how long to keep the data for (eg local laws, contractual obligations, etc)&nbsp;<br />\r\n<br />\r\nYou should also outline how you securely dispose of data after you no longer need it.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h2>Your rights in relation to personal data</h2>\r\n\r\n<p>Under the GDPR, you must respect the right of data subjects to access and control their personal data. In your privacy notice, you must outline their rights in respect of: access to personal information correction and deletion withdrawal of consent (if processing data on condition of consent) data portability restriction of processing and objection lodging a complaint with the Information Commissioner&rsquo;s Office You should explain how individuals can exercise their rights, and how you plan to respond to subject data requests. State if any relevant exemptions may apply and set out any identity verifications procedures you may rely on. Include details of the circumstances where data subject rights may be limited, eg if fulfilling the data subject request may expose personal data about another person, or if you&rsquo;re asked to delete data which you are required to keep by law.</p>\r\n\r\n<h2>Use of automated decision-making and profiling</h2>\r\n\r\n<p>Where you use profiling or other automated decision-making, you must disclose this in your privacy policy. In such cases, you must provide details on existence of any automated decision-making, together with information about the logic involved, and the likely significance and consequences of the processing of the individual.</p>\r\n\r\n<h2>How to contact us?</h2>\r\n\r\n<p>Explain how data subject can get in touch if they have questions or concerns about your privacy practices, their personal information, or if they wish to file a complaint. Describe all ways in which they can contact you &ndash; eg online, by email or postal mail.&nbsp;<br />\r\n<br />\r\nIf applicable, you may also include information on:&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h2>Use of cookies and other technologies</h2>\r\n\r\n<p>You may include a link to further information, or describe within the policy if you intend to set and use cookies, tracking and similar technologies to store and manage user preferences on your website, advertise, enable content or otherwise analyse user and usage data. Provide information on what types of cookies and technologies you use, why you use them and how an individual can control and manage them.&nbsp;<br />\r\n<br />\r\nLinking to other websites / third party content If you link to external sites and resources from your website, be specific on whether this constitutes endorsement, and if you take any responsibility for the content (or information contained within) any linked website.&nbsp;<br />\r\n<br />\r\nYou may wish to consider adding other optional clauses to your privacy policy, depending on your business&rsquo; circumstances.</p>\r\n', '1'),
(3, 'Contact', 'contact', 1605744000, NULL, '1'),
(4, 'Terms of Use', 'terms-of-use', 1605744000, '<h4><strong>1- Write your Terms Of Use here.</strong></h4>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis sdnostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h4><strong>2- Random title</strong></h4>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>\r\n', '1'),
(5, 'Cookies', 'cookies', 1605744000, '<h4><strong>1- Write about your website here.</strong></h4>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h4><strong>2- Random title</strong></h4>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dxzcolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>\r\n', '1'),
(6, 'Terms of sales', 'terms-of-sales', 1615740005, '<h4><strong>1- Write about your website here.</strong></h4>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.&nbsp;<br />\r\n&nbsp;</p>\r\n\r\n<h4><strong>2- Random title</strong></h4>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dxzcolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>\r\n', '0'),
(7, 'My page', 'mypage', 1749740481, '<p>Merhaba nasılsın bu bir denemdir. </p>', '0');

-- --------------------------------------------------------

--
-- Table structure for table `i_payment_methods`
--

CREATE TABLE `i_payment_methods` (
  `payment_method_id` int NOT NULL,
  `paypal_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paypal_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paypal_sendbox_business_email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paypal_product_business_email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paypal_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `paypal_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bitpay_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bitpay_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bitpay_notification_email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bitpay_password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bitpay_pairing_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bitpay_label` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bitpay_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `bitpay_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `stripe_payment_mode` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `stripe_test_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stripe_test_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stripe_live_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stripe_live_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stripe_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `stripe_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `authorize_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `authorizenet_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `authorizenet_test_ap_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `authorizenet_test_transaction_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `authorizenet_live_api_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `authorizenet_live_transaction_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `authorize_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `authorizenet_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `iyzico_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `iyzico_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `iyzico_testing_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `iyzico_testing_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `iyzico_live_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `iyzico_live_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `iyzico_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'USD',
  `iyzico_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `razorpay_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `razorpay_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `razorpay_testing_key_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `razorpay_testing_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `razorpay_live_key_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `razorpay_live_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `razorpay_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `razorpay_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paystack_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paystack_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paystack_testing_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paystack_testing_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paystack_live_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pay_stack_liive_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paystack_crncy` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NGN',
  `paystack_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `flutterwave_payment_mode` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `flutterwave_active_pasive` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `flutterwave_test_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `flutterwave_test_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `flutterwave_live_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `flutterwave_live_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `flutterwave_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `flutterwave_secret_hash` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `flutterwave_encryption_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `flutterwave_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `ccbill_account_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ccbill_subaccount_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ccbill_flex_form_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ccbill_salt_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ccbill_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `ccbill_currency` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ccbill_cancel_username` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ccbill_cancel_password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ccbill_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `coinpayments_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `coinpayments_private_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_merchand_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_ipn_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_debug_email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `coinpayments_mode` enum('legacy','v2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'legacy',
  `coinpayments_client_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_client_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_webhook_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `coinpayments_api_base` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cp_cryptocurrencies` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'LTCT',
  `mercadopago_payment_mode` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `mercadopago_active_pasive` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `mercadopago_test_access_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mercadopago_live_access_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mercadopago_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `mercadopago_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bank_payment_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bank_payment_percentage_fee` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '2.0',
  `bank_payment_fixed_charge` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0.00',
  `bank_payment_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bank_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `moneroo_payment_mode` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `moneroo_active_pasive` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `moneroo_test_project_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `moneroo_test_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `moneroo_live_project_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `moneroo_live_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `moneroo_webhook_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `moneroo_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `moneroo_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `nowpayments_payment_mode` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `nowpayments_active_pasive` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `nowpayments_test_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nowpayments_live_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nowpayments_ipn_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nowpayments_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `nowpayments_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paysafecard_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `paysafecard_mode` enum('test','live') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'test',
  `paysafecard_api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paysafecard_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD',
  `paysafecard_beta` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `konnect_payment_mode` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `konnect_active_pasive` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `konnect_test_api_key` text COLLATE utf8mb4_general_ci,
  `konnect_test_wallet_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `konnect_live_api_key` text COLLATE utf8mb4_general_ci,
  `konnect_live_wallet_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `konnect_webhook_secret` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `konnect_currency` varchar(5) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'TND',
  `konnect_beta` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_payment_methods`
--

INSERT INTO `i_payment_methods` (`payment_method_id`, `paypal_payment_mode`, `paypal_active_pasive`, `paypal_sendbox_business_email`, `paypal_product_business_email`, `paypal_crncy`, `paypal_beta`, `bitpay_payment_mode`, `bitpay_active_pasive`, `bitpay_notification_email`, `bitpay_password`, `bitpay_pairing_code`, `bitpay_label`, `bitpay_crncy`, `bitpay_beta`, `stripe_payment_mode`, `stripe_active_pasive`, `stripe_test_secret_key`, `stripe_test_public_key`, `stripe_live_secret_key`, `stripe_live_public_key`, `stripe_crncy`, `stripe_beta`, `authorize_payment_mode`, `authorizenet_active_pasive`, `authorizenet_test_ap_id`, `authorizenet_test_transaction_key`, `authorizenet_live_api_id`, `authorizenet_live_transaction_key`, `authorize_crncy`, `authorizenet_beta`, `iyzico_payment_mode`, `iyzico_active_pasive`, `iyzico_testing_secret_key`, `iyzico_testing_api_key`, `iyzico_live_api_key`, `iyzico_live_secret_key`, `iyzico_crncy`, `iyzico_beta`, `razorpay_payment_mode`, `razorpay_active_pasive`, `razorpay_testing_key_id`, `razorpay_testing_secret_key`, `razorpay_live_key_id`, `razorpay_live_secret_key`, `razorpay_crncy`, `razorpay_beta`, `paystack_payment_mode`, `paystack_active_pasive`, `paystack_testing_secret_key`, `paystack_testing_public_key`, `paystack_live_secret_key`, `pay_stack_liive_public_key`, `paystack_crncy`, `paystack_beta`, `flutterwave_payment_mode`, `flutterwave_active_pasive`, `flutterwave_test_public_key`, `flutterwave_test_secret_key`, `flutterwave_live_public_key`, `flutterwave_live_secret_key`, `flutterwave_currency`, `flutterwave_secret_hash`, `flutterwave_encryption_key`, `flutterwave_beta`, `ccbill_account_number`, `ccbill_subaccount_number`, `ccbill_flex_form_id`, `ccbill_salt_key`, `ccbill_status`, `ccbill_currency`, `ccbill_cancel_username`, `ccbill_cancel_password`, `ccbill_beta`, `coinpayments_status`, `coinpayments_private_key`, `coinpayments_public_key`, `coinpayments_merchand_id`, `coinpayments_ipn_secret`, `coinpayments_debug_email`, `coinpayments_beta`, `coinpayments_mode`, `coinpayments_client_id`, `coinpayments_client_secret`, `coinpayments_webhook_secret`, `coinpayments_api_base`, `cp_cryptocurrencies`, `mercadopago_payment_mode`, `mercadopago_active_pasive`, `mercadopago_test_access_id`, `mercadopago_live_access_id`, `mercadopago_currency`, `mercadopago_beta`, `bank_payment_status`, `bank_payment_percentage_fee`, `bank_payment_fixed_charge`, `bank_payment_details`, `bank_beta`, `moneroo_payment_mode`, `moneroo_active_pasive`, `moneroo_test_project_id`, `moneroo_test_api_key`, `moneroo_live_project_id`, `moneroo_live_api_key`, `moneroo_webhook_secret`, `moneroo_currency`, `moneroo_beta`, `nowpayments_payment_mode`, `nowpayments_active_pasive`, `nowpayments_test_api_key`, `nowpayments_live_api_key`, `nowpayments_ipn_secret`, `nowpayments_currency`, `nowpayments_beta`, `paysafecard_status`, `paysafecard_mode`, `paysafecard_api_key`, `paysafecard_currency`, `paysafecard_beta`, `konnect_payment_mode`, `konnect_active_pasive`, `konnect_test_api_key`, `konnect_test_wallet_id`, `konnect_live_api_key`, `konnect_live_wallet_id`, `konnect_webhook_secret`, `konnect_currency`, `konnect_beta`) VALUES
(1, '0', '0', NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, NULL, 'USD', '0', 1, '0', NULL, NULL, NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, NULL, 'USD', NULL, NULL, '0', NULL, NULL, NULL, NULL, '0', 'USD', NULL, NULL, '0', '0', NULL, NULL, NULL, NULL, NULL, '0', 'legacy', NULL, NULL, NULL, NULL, 'LTCT', '0', '0', NULL, NULL, 'USD', '0', '1', '2.0', '0.00', 'sqdqfdsfqsfdqfqsd', '1', '0', '0', NULL, NULL, NULL, NULL, NULL, 'USD', '0', '0', '0', NULL, NULL, NULL, 'USD', '0', '0', 'test', NULL, 'USD', '0', '1', '1', 'PLACEHOLDER_TEST_API_KEY', 'PLACEHOLDER_TEST_WALLET_ID', NULL, NULL, 'd471d0a703b531fb6877a5752675f9f7', 'TND', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_polls`
--

CREATE TABLE `i_polls` (
  `poll_id` int NOT NULL,
  `post_id_fk` int NOT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('active','closed','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_poll_options`
--

CREATE TABLE `i_poll_options` (
  `option_id` int NOT NULL,
  `poll_id_fk` int NOT NULL,
  `option_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `option_order` smallint NOT NULL DEFAULT '1',
  `votes_count` int NOT NULL DEFAULT '0',
  `status` enum('active','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_poll_votes`
--

CREATE TABLE `i_poll_votes` (
  `vote_id` int NOT NULL,
  `poll_id_fk` int NOT NULL,
  `option_id_fk` int NOT NULL,
  `voter_uid_fk` int NOT NULL,
  `voter_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vote_time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_posts`
--

CREATE TABLE `i_posts` (
  `post_id` int NOT NULL,
  `post_owner_id` int DEFAULT NULL,
  `post_type` enum('normal','reels','story','poll','campaign') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal',
  `post_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `url_slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `post_file` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `post_created_time` int DEFAULT NULL,
  `scheduled_at` int DEFAULT NULL,
  `post_creator_ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `who_can_see` enum('1','2','3','4') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `post_want_status` enum('normal','secret','credit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal',
  `post_wanted_credit` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `post_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `scheduled_status` enum('pending','queued','published','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'published',
  `comment_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `shared_post_id` int DEFAULT NULL,
  `post_pined` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `hashtags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `boost_id_fk` int DEFAULT NULL,
  `boosted_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `link_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `link_domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `link_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `link_fetched_at` int DEFAULT NULL,
  `music_track_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `music_provider` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `music_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `music_artist` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `music_url` text COLLATE utf8mb4_general_ci,
  `music_cover_url` text COLLATE utf8mb4_general_ci,
  `music_start_time` decimal(8,3) NOT NULL DEFAULT '0.000',
  `music_duration` decimal(8,3) NOT NULL DEFAULT '0.000',
  `music_volume` decimal(4,3) NOT NULL DEFAULT '0.800',
  `music_video_volume` decimal(4,3) NOT NULL DEFAULT '0.500',
  `post_overlays` mediumtext COLLATE utf8mb4_general_ci,
  `post_filter` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `post_video_speed` decimal(4,2) DEFAULT '1.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_posts`
--

INSERT INTO `i_posts` (`post_id`, `post_owner_id`, `post_type`, `post_text`, `url_slug`, `post_file`, `post_created_time`, `scheduled_at`, `post_creator_ip`, `who_can_see`, `post_want_status`, `post_wanted_credit`, `post_status`, `scheduled_status`, `comment_status`, `shared_post_id`, `post_pined`, `hashtags`, `boost_id_fk`, `boosted_status`, `link_url`, `link_domain`, `link_title`, `link_description`, `link_image`, `link_fetched_at`, `music_track_id`, `music_provider`, `music_title`, `music_artist`, `music_url`, `music_cover_url`, `music_start_time`, `music_duration`, `music_volume`, `music_video_volume`, `post_overlays`, `post_filter`, `post_video_speed`) VALUES
(18, 1, 'normal', 'hello', 'hello', '20,', 1776613893, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(20, 1, 'normal', 'sqdqsd', 'sqdqsd', '22,', 1776617580, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(21, 10, 'normal', '', '1n09f043', '27,', 1776621365, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(22, 2, 'normal', '', '3vppghvw', '33,', 1776639119, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(23, 2, 'normal', 'dsqdq', 'dsqdq', '34,', 1776643024, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(24, 3, 'normal', 'zee', 'zee', '37,', 1776727634, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(25, 1, 'normal', 'sd', 'sd', '38,39,40,41,', 1776993796, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(26, 19, 'normal', 'dsqdsqdsq', 'dsqdsqdsq', '51,', 1777660789, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(27, 23, 'reels', '3icha zbar', '52', '52', 1777661265, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(30, 23, 'reels', 'admin 3', '55', '55', 1777661642, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(31, 23, 'reels', 'tips', '56', '56', 1777663108, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(32, 21, 'reels', 'creator', '57', '57', 1777663686, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000, 0.000, 0.800, 0.500, NULL, NULL, 1.00),
(33, 23, 'reels', '', '61', '61', 1777669762, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_1204669', 'jamendo', 'What Is Love', 'Melanie Ungar', 'https://prod-1.storage.jamendo.com/?trackid=1204669&format=mp32&from=FfguWmJBf5Viv%2FVcuQgG7w%3D%3D%7CbZ%2BeYLhMJR7gN%2BwV8%2BWLVw%3D%3D', 'https://usercontent.jamendo.com?type=album&id=144705&width=300&trackid=1204669', 0.000, 60.000, 1.000, 0.500, NULL, NULL, 1.00),
(34, 23, 'reels', '', '63', '63', 1777670718, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_1932670', 'jamendo', 'RED LIGHT', 'Egor Budennyy', 'https://prod-1.storage.jamendo.com/?trackid=1932670&format=mp32&from=si37VKjcLTBq3kc92Tboyw%3D%3D%7CFSSmHPalcdMv5NALoj01tQ%3D%3D', 'https://usercontent.jamendo.com?type=album&id=477294&width=300&trackid=1932670', 0.000, 60.000, 1.000, 0.500, NULL, NULL, 1.00),
(35, 23, 'reels', '', '64', '64', 1777671065, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_131169', 'jamendo', '3ALAMET_ESSE3A.BALTI', 'Baltiroshima', 'https://prod-1.storage.jamendo.com/?trackid=131169&format=mp32&from=USNJqk5%2BJmvB3BQVURFnJA%3D%3D%7CxTqaZFwvbtiEu8yEOMfyzg%3D%3D', 'https://usercontent.jamendo.com?type=album&id=18489&width=300&trackid=131169', 0.000, 60.000, 0.800, 0.500, NULL, NULL, 1.00),
(36, 1, 'reels', '', '62', '62', 1777671383, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_1141572', 'jamendo', 'Love Will Come Back Again', 'Chaz Robinson', 'https://prod-1.storage.jamendo.com/?trackid=1141572&format=mp32&from=%2F6s%2BxTFQlcwgPNcojdL4kg%3D%3D%7C07Q3oJVR3JtTiFR0fDuzlw%3D%3D', 'https://usercontent.jamendo.com?type=album&id=136930&width=300&trackid=1141572', 0.000, 60.000, 0.800, 0.500, NULL, NULL, 1.00),
(37, 1, 'reels', '', '65', '65', 1777672481, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_1932670', 'jamendo', 'RED LIGHT', 'Egor Budennyy', 'https://prod-1.storage.jamendo.com/?trackid=1932670&format=mp32&from=Mf7GogCvb8LwRf0hp9ueFA%3D%3D%7C1XALSHJgI83IZ9e9EEWalQ%3D%3D', 'https://usercontent.jamendo.com?type=album&id=477294&width=300&trackid=1932670', 0.000, 60.000, 0.800, 0.500, '[{\"type\":\"text\",\"text\":\"Hello Bro\",\"x\":0.2411,\"y\":0.7806,\"scale\":1.083,\"color\":\"#000000\",\"bg\":\"1\"},{\"type\":\"emoji\",\"text\":\"😀\",\"x\":0.233,\"y\":0.2118,\"scale\":1.556,\"color\":\"#ffffff\",\"bg\":\"0\"}]', NULL, 1.00),
(38, 1, 'reels', '', '69', '69', 1777674067, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_1932670', 'jamendo', 'RED LIGHT', 'Egor Budennyy', 'https://prod-1.storage.jamendo.com/?trackid=1932670&format=mp32&from=g1lPyBcf1Cc3L2v3rn7oAw%3D%3D%7CDFPBiDfliIjDGWexWpKpAQ%3D%3D', 'https://usercontent.jamendo.com?type=album&id=477294&width=300&trackid=1932670', 0.000, 60.000, 0.800, 0.500, '[{\"type\":\"text\",\"text\":\"hamdi\",\"x\":0.3274,\"y\":0.5971,\"scale\":1.315,\"rot\":0,\"color\":\"#000000\",\"bg\":\"0\",\"font\":\"serif\"},{\"type\":\"emoji\",\"text\":\"😄\",\"x\":0.4218,\"y\":0.459,\"scale\":1,\"rot\":0,\"color\":\"#ffffff\",\"bg\":\"0\",\"font\":\"\"}]', 'dramatic', 1.00),
(39, 1, 'reels', '', '70', '70', 1777681813, NULL, '::1', '1', 'normal', '', '1', 'published', '1', NULL, '0', '', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'jam_1214935', 'jamendo', 'Wish You Were Here', 'The.madpix.project', 'https://prod-1.storage.jamendo.com/?trackid=1214935&format=mp32&from=KNtTB5AK%2BHH%2Fi1gE57kZug%3D%3D%7C05RSBV5TdT92hcX%2F81Tnmg%3D%3D', 'https://usercontent.jamendo.com?type=album&id=145774&width=300&trackid=1214935', 0.000, 60.000, 0.800, 0.500, '[{\"type\":\"text\",\"text\":\"52526\",\"src\":\"\",\"x\":0.2438,\"y\":0.6305,\"scale\":1.259,\"rot\":-35.56,\"color\":\"#000000\",\"bg\":\"1\",\"font\":\"cursive\",\"anim\":\"none\",\"animOut\":\"none\",\"start\":0,\"end\":0,\"z\":0},{\"type\":\"emoji\",\"text\":\"😃\",\"src\":\"\",\"x\":0.2735,\"y\":0.3877,\"scale\":1,\"rot\":0,\"color\":\"#ffffff\",\"bg\":\"0\",\"font\":\"\",\"anim\":\"none\",\"animOut\":\"none\",\"start\":0,\"end\":0,\"z\":0},{\"type\":\"emoji\",\"text\":\"🧡\",\"src\":\"\",\"x\":0.206,\"y\":0.2178,\"scale\":1,\"rot\":0,\"color\":\"#ffffff\",\"bg\":\"0\",\"font\":\"\",\"anim\":\"none\",\"animOut\":\"none\",\"start\":0,\"end\":0,\"z\":0}]', 'warm', 0.50);

-- --------------------------------------------------------

--
-- Table structure for table `i_post_comments`
--

CREATE TABLE `i_post_comments` (
  `com_id` int NOT NULL,
  `comment_post_id_fk` int DEFAULT NULL,
  `comment_uid_fk` int DEFAULT NULL,
  `parent_comment_id` int DEFAULT NULL,
  `comment_time` int DEFAULT NULL,
  `comment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment_file` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sticker_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gif_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_hidden` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `hidden_by` int DEFAULT NULL,
  `hidden_at` datetime DEFAULT NULL,
  `link_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `link_domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `link_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `link_fetched_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_post_comments`
--

INSERT INTO `i_post_comments` (`com_id`, `comment_post_id_fk`, `comment_uid_fk`, `parent_comment_id`, `comment_time`, `comment`, `comment_file`, `sticker_url`, `gif_url`, `is_hidden`, `hidden_by`, `hidden_at`, `link_url`, `link_domain`, `link_title`, `link_description`, `link_image`, `link_fetched_at`) VALUES
(1, 23, 1, NULL, 1776982889, 'tyf', NULL, '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 23, 1, NULL, 1776982892, 'gvgh', NULL, '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_post_comment_likes`
--

CREATE TABLE `i_post_comment_likes` (
  `c_like_id` int NOT NULL,
  `c_like_iuid_fk` int DEFAULT NULL,
  `c_like_comment_id` int DEFAULT NULL,
  `c_like_post_id` int DEFAULT NULL,
  `c_like_time` int DEFAULT '1608467477'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_post_likes`
--

CREATE TABLE `i_post_likes` (
  `like_id` int NOT NULL,
  `post_id_fk` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `like_time` int DEFAULT NULL,
  `user_ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_post_likes`
--

INSERT INTO `i_post_likes` (`like_id`, `post_id_fk`, `iuid_fk`, `like_time`, `user_ip`) VALUES
(1, 30, 23, 1777662965, '::1'),
(2, 31, 23, 1777663269, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `i_post_reports`
--

CREATE TABLE `i_post_reports` (
  `p_report_id` int NOT NULL,
  `reported_post` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `report_time` int NOT NULL DEFAULT '1608301750',
  `report_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_premium_plans`
--

CREATE TABLE `i_premium_plans` (
  `plan_id` int NOT NULL,
  `plan_name_key` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_amount` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `plan_discount` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_premium_plans`
--

INSERT INTO `i_premium_plans` (`plan_id`, `plan_name_key`, `plan_amount`, `plan_status`, `plan_discount`, `amount`) VALUES
(1, 'discovery', '130', '1', NULL, '10'),
(2, 'naughy', '286', '1', NULL, '20'),
(3, 'amateur', '715', '1', NULL, '50'),
(4, 'confirmed', '1430', '1', NULL, '100'),
(5, 'expert', '7150', '1', NULL, '500'),
(6, 'royal', '14300', '1', NULL, '1000');

-- --------------------------------------------------------

--
-- Table structure for table `i_product_seen_time`
--

CREATE TABLE `i_product_seen_time` (
  `p_s_id` int NOT NULL,
  `p_uid_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `p_id` int DEFAULT NULL,
  `p_seen_time` int DEFAULT NULL,
  `p_s_iuid_fk` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_profile_categories`
--

CREATE TABLE `i_profile_categories` (
  `c_id` int NOT NULL,
  `c_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `c_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_profile_categories`
--

INSERT INTO `i_profile_categories` (`c_id`, `c_key`, `c_status`) VALUES
(1, 'instagramer', '1'),
(2, 'youtuber', '1'),
(3, 'influencer', '1'),
(4, 'financial_tipper', '1'),
(5, 'designer', '1'),
(6, 'developer', '1'),
(7, 'normal_user', '1'),
(8, 'music_producer', '1'),
(9, 'artists', '1'),
(10, 'athletes', '1'),
(11, 'actors', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_profile_sub_categories`
--

CREATE TABLE `i_profile_sub_categories` (
  `sc_id` int NOT NULL,
  `sc_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `c_fk` int DEFAULT NULL,
  `sc_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_profile_sub_categories`
--

INSERT INTO `i_profile_sub_categories` (`sc_id`, `sc_key`, `c_fk`, `sc_status`) VALUES
(3, 'youtuber_1', 2, '1'),
(4, 'youtuber_2', 2, '1'),
(5, 'youtuber_3', 2, '1'),
(6, 'designer_1', 5, '1'),
(7, 'designer_2', 5, '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_refusers`
--

CREATE TABLE `i_refusers` (
  `i_ref_id` int NOT NULL,
  `ref_owner_user_id` int NOT NULL,
  `ref_user_id` int NOT NULL,
  `ref_type` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'reg',
  `time` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_saved_posts`
--

CREATE TABLE `i_saved_posts` (
  `save_id` int NOT NULL,
  `saved_post_id` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `saved_time` int NOT NULL DEFAULT '1608363432'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_scheduled_queue`
--

CREATE TABLE `i_scheduled_queue` (
  `id` int NOT NULL,
  `post_id_fk` int NOT NULL,
  `run_at` int NOT NULL,
  `status` enum('pending','processing','done','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `attempts` int NOT NULL DEFAULT '0',
  `last_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_sessions`
--

CREATE TABLE `i_sessions` (
  `session_id` int NOT NULL,
  `session_uid` int DEFAULT NULL,
  `session_key` varchar(96) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `session_time` int NOT NULL DEFAULT '1605484800'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_sessions`
--

INSERT INTO `i_sessions` (`session_id`, `session_uid`, `session_key`, `session_time`) VALUES
(1, 1, 'ad5d21508ec6c07a792ec05ce87274ff4d46679ed06e6da9db304a95fa42a0df', 1776209064),
(5, 2, '02099c075ba2251c8d57fcbb3c64f7d25221480771723fe43391dc6b74fb2f86', 1776638979),
(7, 3, '8c8f1a5261852d80ce24887e3432430bffa3cf1fcebb405348219ea37a1c1348', 1776713474),
(9, 1, '9ebd46b57a6145c0cf7f40f754038bd5858de6b5966e3bbf0df42cd983166672', 1776990208),
(17, 23, '0ad481096702085639162bc18089fa2403260edb99776f9075b939cf2fc32e25', 1777661167),
(18, 22, '91d9822f760c702ada7b808bf3d5d1b60f69c61d626e153c510e1b89315e3a60', 1777661318),
(19, 21, 'fa1f3d5bb60a9fb83616dafd46faebb502bbc3541d7b05074fe88f22d50ce52b', 1777663645),
(20, 1, 'd1ea6d3d2686baf6146fd66c56424cc828c05475eddd587a24d7fe4ddbc7825e', 1777675948);

-- --------------------------------------------------------

--
-- Table structure for table `i_settings_kv`
--

CREATE TABLE `i_settings_kv` (
  `setting_key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_settings_kv`
--

INSERT INTO `i_settings_kv` (`setting_key`, `setting_value`, `updated_at`) VALUES
('account_deletion_worker_status', '1', 1776207504),
('account_deletion_worker_token', '3ed54326ebdf66cb97b0c562d6e32a21', 1776612167),
('agency_auto_creator_on_approval', 'yes', 1776207504),
('obs_overlay_auto_revoke_oldest', '1', 1776207504),
('obs_overlay_max_active', '5', 1776207504),
('obs_overlay_max_total', '5', 1776207504),
('obs_overlay_module_status', 'yes', 1776207504),
('registration_role_mode', 'legacy', 1776207504),
('site_night_logo', 'img/2026-04-24/image_1776989496045096900_1.png', 1777658235),
('watermark_opacity', '78', 1777658235),
('watermark_position', 'bottom_right', 1777658235),
('watermark_size', '19', 1777658235),
('web_push_event_announcement', '1', 1776207504),
('web_push_event_comment', '1', 1776207504),
('web_push_event_follow', '1', 1776207504),
('web_push_event_live_started', '1', 1776207504),
('web_push_event_post_like', '1', 1776207504),
('web_push_event_subscription', '1', 1776207504),
('web_push_event_tip_received', '1', 1776207504),
('web_push_status', '0', 1776207504),
('web_push_ttl', '60', 1776207504),
('web_push_vapid_private', '', 1776207504),
('web_push_vapid_public', '', 1776207504),
('web_push_vapid_subject', 'mailto:admin@localhost', 1776207504);

-- --------------------------------------------------------

--
-- Table structure for table `i_share_tokens`
--

CREATE TABLE `i_share_tokens` (
  `token_id` int UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `post_id` int UNSIGNED NOT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` int UNSIGNED NOT NULL,
  `expires_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_share_tokens`
--

INSERT INTO `i_share_tokens` (`token_id`, `token`, `post_id`, `created_by`, `created_at`, `expires_at`) VALUES
(26, '200feda2b78a24a89a7edb856b151929', 25, 1, 1777657778, 1778262578),
(27, 'a24969773f06fd52d26305fc52608706', 23, 1, 1777659260, 1778264060),
(28, '987f6a082792722cf6c173b19755c04d', 21, 1, 1777659268, 1778264068),
(29, 'f8cc6834873375579f33aa6e2bfda445', 22, 1, 1777660719, 1778265519),
(30, '83fa01be7dd9c175a4b77ce447b9fa4a', 24, 1, 1777660754, 1778265554),
(31, 'bd3aeceab9d182d67b86e85d518c9207', 18, 19, 1777660766, 1778265566),
(32, '2e3917e7ec00859c90f1ebd658836bc3', 26, 19, 1777660789, 1778265589),
(33, 'df07c3ae32acfe9ae38044b3231935b0', 20, 22, 1777661365, 1778266165),
(34, 'af51c710a911e58b84fc20edac14d14e', 29, 22, 1777661881, 1778266681),
(35, '0ee589112191d336ddec3ffce2120719', 32, 1, 1777665729, 1778270529);

-- --------------------------------------------------------

--
-- Table structure for table `i_shop_configuration`
--

CREATE TABLE `i_shop_configuration` (
  `id` int NOT NULL,
  `shop_key` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('no','yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_shop_configuration`
--

INSERT INTO `i_shop_configuration` (`id`, `shop_key`, `status`) VALUES
(1, 'shop_feature', 'yes'),
(2, 'scratch', 'yes'),
(3, 'book_a_zoom', 'yes'),
(4, 'digital_download', 'yes'),
(5, 'live_event_ticket', 'yes'),
(6, 'art_commission', 'yes'),
(7, 'instagram_close_friends', 'yes'),
(8, 'who_can_create', 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `i_social_logins`
--

CREATE TABLE `i_social_logins` (
  `s_id` int NOT NULL,
  `s_key` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `s_key_one` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `s_key_two` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `s_first_active` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `s_icon` int DEFAULT NULL,
  `s_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_social_logins`
--

INSERT INTO `i_social_logins` (`s_id`, `s_key`, `s_key_one`, `s_key_two`, `s_first_active`, `s_icon`, `s_status`) VALUES
(1, 'google', NULL, NULL, '1', 4, '0'),
(2, 'twitter', NULL, NULL, '0', 3, '0');

-- --------------------------------------------------------

--
-- Table structure for table `i_social_networks`
--

CREATE TABLE `i_social_networks` (
  `id` int NOT NULL,
  `social_icon` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `skey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `place_holder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_social_networks`
--

INSERT INTO `i_social_networks` (`id`, `social_icon`, `skey`, `place_holder`, `status`) VALUES
(1, '<svg id=\"Capa_34\" data-name=\"Capa 34\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 490.66 403.26\"><defs><style>.cls-34{fill:#03a9f4;}</style></defs><path class=\"cls-34\" d=\"M487.84,92.93A10.66,10.66,0,0,0,475,90.67a91.62,91.62,0,0,1-13.63,4.93,95.94,95.94,0,0,0,16.21-31.21A10.66,10.66,0,0,0,461.2,52.91,224.34,224.34,0,0,1,409.87,73,107.35,107.35,0,0,0,275.36,62.6a105.84,105.84,0,0,0-46.08,97.81A252.59,252.59,0,0,1,52.38,65.13a11.07,11.07,0,0,0-9.06-4,10.72,10.72,0,0,0-8.54,5.21A103.24,103.24,0,0,0,23,146.5a114.07,114.07,0,0,0,18.54,38.76,56.7,56.7,0,0,1-10.84-6.93,10.67,10.67,0,0,0-17.39,8.28,110.33,110.33,0,0,0,56.84,94.72,78.57,78.57,0,0,1-13.74-2.95A10.66,10.66,0,0,0,43.62,293a116.4,116.4,0,0,0,80.81,66.14A188,188,0,0,1,11.89,382.28,10.51,10.51,0,0,0,.52,389.57a10.69,10.69,0,0,0,4.91,12.59A319.69,319.69,0,0,0,163.08,447,270.8,270.8,0,0,0,312.72,401.2c84.54-56.11,137.23-156.84,129.89-247A199.1,199.1,0,0,0,489,105.88,10.67,10.67,0,0,0,487.84,92.93Z\" transform=\"translate(0 -43.7)\"/></svg>', 'twitter', 'https://www.twitter.com/username', 'yes'),
(2, '<svg id=\"Layer_88\" data-name=\"Layer 88\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 513.48 513.58\"><defs><style>.cls-1-88{fill:url(#linear-gradient);}.cls-2-88{fill:#fff;}</style><linearGradient id=\"linear-gradient\" x1=\"-30.96\" y1=\"578.55\" x2=\"-20.41\" y2=\"589.89\" gradientTransform=\"translate(-22726.5 -829.95) rotate(-90) scale(42.28 39.34)\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#fd5\"/><stop offset=\"0.5\" stop-color=\"#ff543e\"/><stop offset=\"1\" stop-color=\"#c837ab\"/></linearGradient></defs><path class=\"cls-1-88\" d=\"M32,34.84C-8.23,76.63,0,121,0,255.89c0,112-19.54,224.28,82.73,250.71,31.94,8.22,314.9,8.22,346.8,0,42.58-11,77.22-45.53,82-105.75.66-8.4.66-281.28,0-289.86-5-64.14-44.53-101.12-96.56-108.6C403,.62,400.6.11,339.41,0,122.39.11,74.82-9.56,32,34.84Z\" transform=\"translate(1.49 0.82)\"/><path class=\"cls-2-88\" d=\"M256,67c-77.46,0-151-6.9-179.12,65.21C65.24,162,66.92,200.64,66.92,256c0,48.6-1.55,94.27,9.92,123.82,28,72.15,102.19,65.24,179.07,65.24,74.18,0,150.66,7.72,179.1-65.24,11.62-30.08,9.92-68.18,9.92-123.82,0-73.85,4.07-121.53-31.75-157.33C376.92,62.42,327.87,67,255.87,67ZM239,101c161.58-.25,182.14-18.21,170.79,231.32-4,88.26-71.23,78.57-153.83,78.57-150.61,0-154.95-4.31-154.95-155C101,103.51,113,101.12,239,101Zm117.84,31.39a22.68,22.68,0,1,0,22.68,22.67A22.67,22.67,0,0,0,356.86,132.42ZM256,158.93A97.08,97.08,0,1,0,353,256a97.09,97.09,0,0,0-97-97.09ZM256,193c83.3,0,83.41,126,0,126S172.54,193,256,193Z\" transform=\"translate(1.49 0.82)\"/></svg>', 'instagram', 'https://www.instagram.com/username', 'yes'),
(4, '<svg id=\"Layer_1k\" data-name=\"Layer 1k\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 461 326.22\"><defs><style>.cls-1k{fill:#f61c0d;}</style></defs><path class=\"cls-1k\" d=\"M365.26,67.39H95.74A95.75,95.75,0,0,0,0,163.14V297.86a95.75,95.75,0,0,0,95.74,95.75H365.26A95.74,95.74,0,0,0,461,297.86V163.14A95.74,95.74,0,0,0,365.26,67.39ZM300.51,237.06,174.45,297.18a5.06,5.06,0,0,1-7.24-4.57v-124a5.06,5.06,0,0,1,7.35-4.52L300.62,228A5.07,5.07,0,0,1,300.51,237.06Z\" transform=\"translate(0 -67.39)\"/></svg>', 'youtube', 'https://youtube.com/username', 'yes'),
(5, '<svg id=\"Capa_1pi\" data-name=\"Capa 1pi\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 112.2 112.2\"><defs><style>.cls-1pi{fill:#cb2027;}.cls-2pi{fill:#f1f2f2;}</style></defs><circle class=\"cls-1pi\" cx=\"56.1\" cy=\"56.1\" r=\"56.1\"/><path class=\"cls-2pi\" d=\"M60.63,75.12c-4.24-.33-6-2.43-9.35-4.45-1.83,9.59-4.06,18.79-10.68,23.59-2.05-14.5,3-25.38,5.34-36.94-4-6.72.48-20.25,8.9-16.91,10.36,4.09-9,25,4,27.59C72.4,70.73,77.93,44.49,69.53,36,57.38,23.63,34.18,35.67,37,53.31c.69,4.31,5.15,5.62,1.78,11.57C31,63.16,28.73,57,29,48.86c.49-13.37,12-22.74,23.59-24C67.25,23.19,81,30.2,82.88,44,85,59.5,76.28,76.33,60.63,75.12Z\" transform=\"translate(0 0)\"/></svg>', 'pinterest', 'https://pinterest.com/username', 'yes'),
(6, '<svg id=\"Layer_1g\" data-name=\"Layer 1g\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 496 485.67\"><defs><style>.cls-1g{fill:#3e77bf;}.cls-2g{fill:#114c82;}.cls-3g{fill:#6ca9ea;}.cls-4g{fill:#083e66;}</style></defs><path id=\"SVGCleanerId_0\" data-name=\"SVGCleanerId 0\" class=\"cls-1g\" d=\"M248,5.16c-136.94,0-248,111.47-248,249A248.92,248.92,0,0,0,169.63,490.4c12.39,2.3,16.91-5.39,16.91-12,0-5.9-.21-21.56-.33-42.34-69,15.06-83.55-33.38-83.55-33.38-11.27-28.76-27.52-36.4-27.52-36.4-22.53-15.46,1.69-15.16,1.69-15.16,24.88,1.75,38,25.67,38,25.67,22.14,38,58.06,27,72.19,20.67,2.23-16.08,8.66-27.06,15.74-33.28-55.06-6.3-112.95-27.64-112.95-123,0-27.18,9.66-49.41,25.52-66.83-2.57-6.3-11.07-31.6,2.42-65.87,0,0,20.83-6.69,68.2,25.52a233.71,233.71,0,0,1,124.19,0c47.33-32.21,68.13-25.53,68.13-25.53,13.52,34.28,5,59.58,2.45,65.88,15.91,17.42,25.51,39.65,25.51,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,6.67,4.47,14.43,17.05,12A249,249,0,0,0,496,254.16C496,116.63,385,5.16,248,5.16Z\" transform=\"translate(0 -5.16)\"/><path id=\"SVGCleanerId_0-2\" data-name=\"SVGCleanerId 0\" class=\"cls-1g\" d=\"M248,5.16c-136.94,0-248,111.47-248,249A248.92,248.92,0,0,0,169.63,490.4c12.39,2.3,16.91-5.39,16.91-12,0-5.9-.21-21.56-.33-42.34-69,15.06-83.55-33.38-83.55-33.38-11.27-28.76-27.52-36.4-27.52-36.4-22.53-15.46,1.69-15.16,1.69-15.16,24.88,1.75,38,25.67,38,25.67,22.14,38,58.06,27,72.19,20.67,2.23-16.08,8.66-27.06,15.74-33.28-55.06-6.3-112.95-27.64-112.95-123,0-27.18,9.66-49.41,25.52-66.83-2.57-6.3-11.07-31.6,2.42-65.87,0,0,20.83-6.69,68.2,25.52a233.71,233.71,0,0,1,124.19,0c47.33-32.21,68.13-25.53,68.13-25.53,13.52,34.28,5,59.58,2.45,65.88,15.91,17.42,25.51,39.65,25.51,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,6.67,4.47,14.43,17.05,12A249,249,0,0,0,496,254.16C496,116.63,385,5.16,248,5.16Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-2g\" d=\"M378.5,109.13c13.12,33.91,4.75,58.93,2.2,65.18,15.9,17.42,25.5,39.65,25.5,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,6.67,4.48,14.43,17.05,12A249.6,249.6,0,0,0,481.43,170C447.24,134,409.18,110,378.5,109.13Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-2g\" d=\"M193,377.32a42.66,42.66,0,0,1,9.76-13.14c-30.18-3.46-61.17-11.51-83.05-33.75C136.52,358.45,163.93,371.2,193,377.32Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-3g\" d=\"M12.91,271c0-137.54,111.05-249,248-249a247,247,0,0,1,189.54,88.48C405.52,46.76,331.62,5.16,248,5.16c-136.94,0-248,111.47-248,249A248.36,248.36,0,0,0,58.75,415,248.26,248.26,0,0,1,12.91,271Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-4g\" d=\"M322.71,426.9c0-23.23-7.93-38.44-16.83-46.13,55.25-6.16,113.24-27.22,113.24-122.85,0-27.18-9.6-49.4-25.52-66.82,2.58-6.3,11.1-31.61-2.46-65.87a24.58,24.58,0,0,0-7.89-.49c5.81,25.84-.42,44.32-2.55,49.57,15.9,17.42,25.5,39.65,25.5,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,5.87,3.5,12.57,12.92,12.41C322.49,479.55,322.71,455.66,322.71,426.9Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-2g\" d=\"M117.46,424c5.21,10.72,22.72,36.4,68.89,31-.06-5.65-.11-12-.14-18.93C150.58,443.83,129.55,434.67,117.46,424Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-3g\" d=\"M126.37,391.39a61.07,61.07,0,0,0-8.91-10.55A60,60,0,0,0,126.37,391.39Z\" transform=\"translate(0 -5.16)\"/></svg>', 'github', 'https://github.com/username', 'yes'),
(7, '<svg id=\"Layer_1sn\" data-name=\"Layer 1sn\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><defs><style>.cls-1sn{fill:#fffc00;}.cls-2sn{fill:#fff;stroke:#000;stroke-width:7px;}</style></defs><rect class=\"cls-1sn\" width=\"512\" height=\"512\" rx=\"76.8\"/><path class=\"cls-2sn\" d=\"M203,414c-18-13-32-20-66-14-5,1-14,4-15-5-2-7-2-18-7-19-31-4-42-10-47-16-2-2-3-7,1-8,53-10,78-61,83-71,6-15-4-24-23-30-9-3-24-7-24-17,0-5,5-8,10-10,4-1,8-2,12,0,12,5,23,7,29,2,0-27-7-61,4-89,13-31,45-59,96-59s83,28,96,59c11,28,4,62,4,89,6,5,17,3,29-2,4-2,8-1,12,0,5,2,10,5,10,10,0,10-15,14-24,17-19,6-29,15-23,30,5,10,30,61,83,71,4,1,3,6,1,8-5,6-16,12-47,16-5,1-5,12-7,19-1,9-10,6-15,5-34-6-48,1-66,14a82,82,0,0,1-53,20C235,435,218,426,203,414Z\"/></svg>', 'snapchat', 'https://www.snapchat.com/add/username', 'yes'),
(8, '<svg id=\"Layer_1a\" data-name=\"Layer 1a\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 82.59 92.8\"><defs><style>.cls-1a{fill:#69c9d0;}</style></defs><g id=\"Layer_2a\" data-name=\"Layer 2a\"><path class=\"cls-1a\" d=\"M74.09,21.25A25.47,25.47,0,0,1,68,4L52.58,3.6V66.89C52.8,86.43,21.93,86,24.37,64.36A14.38,14.38,0,0,1,43.29,53.48V37.77C25.44,34.66,8.53,48.86,8.71,67c1.56,39.25,57.85,39.26,59.41,0-.39-1.4-.17-27.56-.22-29.77a41.58,41.58,0,0,0,23.4,6.26V27.22C83.67,27.22,77.85,25.2,74.09,21.25Z\" transform=\"translate(-8.7 -3.6)\"/></g></svg>', 'tiktok', 'https://www.tiktok.com/@username', 'yes'),
(9, '<svg id=\"Layer_11\" data-name=\"Layer 11\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 256 256\"><defs><style>.cls-11{fill:#40b3e0;}.cls-21{fill:#fff;}.cls-31{fill:#d2e5f1;}.cls-41{fill:#b5cfe4;}</style></defs><path class=\"cls-11\" d=\"M128,0A128,128,0,0,0,0,128H0A128,128,0,0,0,128,256h0A128,128,0,0,0,256,128h0A128,128,0,0,0,128,0Z\"/><path class=\"cls-21\" d=\"M190.28,73.63,167.42,188.9s-3.2,8-12,4.15l-52.76-40.44-19.18-9.28-32.3-10.87s-4.95-1.76-5.43-5.59S51.35,121,51.35,121L179.73,70.59s10.55-4.63,10.55,3\"/><path class=\"cls-31\" d=\"M98.62,187.6s-1.54-.14-3.46-6.22-11.67-38-11.67-38L161,94.09s4.47-2.71,4.31,0c0,0,.8.48-1.6,2.72s-60.91,54.84-60.91,54.84\"/><path class=\"cls-41\" d=\"M122.9,168.12l-20.87,19a3.64,3.64,0,0,1-3.41.46l4-35.34\"/></svg>', 'telegram', 'https://t.me/username', 'yes'),
(10, '<svg id=\"Layer_12\" data-name=\"Layer 12\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1024 1024\"><defs><style>.cls-12{fill:#9146ff;}.cls-22{fill:#fff;}</style></defs><circle class=\"cls-12\" cx=\"512\" cy=\"512\" r=\"512\"/><path class=\"cls-22\" d=\"M692.9,535,617,607H541l-66.5,63V607H389V337.2H692.9ZM370,301.2l-95,89.9V714.9H389v89.9l95-89.9h76L730.9,553V301.2ZM636,403H598V510.9h38Zm-142.5-.5h38V510.4h-38Z\"/></svg>', 'twitch', 'https://www.twitch.tv/username', 'yes'),
(11, '<svg id=\"Layer_13\" data-name=\"Layer 13\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1024 1024\"><defs><style>.cls-13{fill:#5865f2;}.cls-23{fill:#fff;}</style></defs><circle class=\"cls-13\" cx=\"512\" cy=\"512\" r=\"512\"/><path class=\"cls-23\" d=\"M689.43,349a422.12,422.12,0,0,0-104.22-32.32,1.58,1.58,0,0,0-1.68.79,292.91,292.91,0,0,0-13,26.66,389.89,389.89,0,0,0-117,0,270.38,270.38,0,0,0-13.18-26.66,1.63,1.63,0,0,0-1.68-.79A420.85,420.85,0,0,0,334.44,349a1.47,1.47,0,0,0-.69.59C267.38,448.76,249.2,545.49,258.12,641a1.75,1.75,0,0,0,.67,1.2,424.67,424.67,0,0,0,127.85,64.63,1.67,1.67,0,0,0,1.8-.59,303.6,303.6,0,0,0,26.15-42.54,1.63,1.63,0,0,0-.72-2.18l-.17-.07a279.7,279.7,0,0,1-39.94-19,1.64,1.64,0,0,1-.58-2.25,1.71,1.71,0,0,1,.42-.47c2.68-2,5.37-4.1,7.93-6.22a1.57,1.57,0,0,1,1.65-.22c83.79,38.26,174.51,38.26,257.31,0a1.58,1.58,0,0,1,1.68.2c2.56,2.11,5.25,4.23,8,6.24a1.64,1.64,0,0,1,.33,2.3,1.71,1.71,0,0,1-.47.42,261.88,261.88,0,0,1-40,19,1.63,1.63,0,0,0-.95,2.1,1.42,1.42,0,0,0,.08.18,341.13,341.13,0,0,0,26.13,42.52,1.62,1.62,0,0,0,1.8.61,423,423,0,0,0,128-64.63,1.65,1.65,0,0,0,.67-1.18c10.68-110.44-17.88-206.38-75.7-291.42a1.29,1.29,0,0,0-.63-.63ZM427.09,582.85c-25.23,0-46-23.16-46-51.6s20.38-51.6,46-51.6c25.83,0,46.42,23.36,46,51.6C473.11,559.69,452.72,582.85,427.09,582.85Zm170.13,0c-25.23,0-46-23.16-46-51.6s20.38-51.6,46-51.6c25.83,0,46.42,23.36,46,51.6C643.23,559.69,623.05,582.85,597.22,582.85Z\"/></svg>', 'discord', 'https://discord.gg/username', 'yes'),
(12, '<svg id=\"Layer_14\" data-name=\"Layer 14\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1024 1024\"><defs><style>.cls-14{fill:#2787f5;}.cls-24{fill:#fff;}</style></defs><circle class=\"cls-14\" cx=\"512\" cy=\"512\" r=\"512\"/><path class=\"cls-24\" d=\"M585.83,271.5H438.17c-134.76,0-166.67,31.91-166.67,166.67V585.83c0,134.76,31.91,166.67,166.67,166.67H585.83c134.76,0,166.67-31.91,166.67-166.67V438.17c0-134.76-32.25-166.67-166.67-166.67Zm74,343.18h-35c-13.24,0-17.31-10.52-41.07-34.62-20.71-20-29.87-22.74-35-22.74-7.13,0-9.17,2-9.17,11.88v31.57c0,8.49-2.72,13.58-25.12,13.58-37,0-78.07-22.4-106.93-64.16-43.45-61.1-55.33-106.93-55.33-116.43,0-5.09,2-9.84,11.88-9.84h35c8.83,0,12.22,4.07,15.61,13.58,17.31,49.9,46.17,93.69,58,93.69,4.41,0,6.45-2,6.45-13.24v-51.6c-1.36-23.76-13.92-25.8-13.92-34.28,0-4.07,3.39-8.15,8.83-8.15h55c7.47,0,10.18,4.07,10.18,12.9V506.4c0,7.47,3.39,10.18,5.43,10.18,4.41,0,8.15-2.72,16.29-10.86,25.12-28.17,43.11-71.62,43.11-71.62,2.38-5.09,6.45-9.84,15.28-9.84h35c10.52,0,12.9,5.43,10.52,12.9C660.46,457.53,617.69,518,617.69,518c-3.73,6.11-5.09,8.83,0,15.61,3.73,5.09,16,15.61,24.1,25.12,14.94,17,26.48,31.23,29.53,41.07,3.45,9.84-1.65,14.93-11.49,14.93Z\"/></svg>', 'vk', 'https://vk.com/username', 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `i_social_user_profiles`
--

CREATE TABLE `i_social_user_profiles` (
  `ius_id` int NOT NULL,
  `isw_id_fk` int NOT NULL,
  `s_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `uid_fk` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_stickers`
--

CREATE TABLE `i_stickers` (
  `sticker_id` int NOT NULL,
  `sticker_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sticker_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_stickers`
--

INSERT INTO `i_stickers` (`sticker_id`, `sticker_url`, `sticker_status`) VALUES
(1, 'https://media4.giphy.com/media/W3CLbW0KY3RtjsqtYO/giphy.gif', '1'),
(2, 'https://media3.giphy.com/media/h2TL9kJUgFoaeuEVR9/200w.gif', '1'),
(3, 'https://media3.giphy.com/media/h86PVmFNq6f2l3gMWI/200w.gif', '1'),
(4, 'https://media3.giphy.com/media/Y00c0w6xxtLn067SUi/200w.gif', '1'),
(5, 'https://media0.giphy.com/media/h4OGa0npayrJX2NRPT/200w.gif', '1'),
(6, 'https://media4.giphy.com/media/j4l0mCdcTFRyY4Bc5s/200w.gif', '1'),
(7, 'https://media0.giphy.com/media/hp3dmEypS0FaoyzWLR/200w.gif', '1'),
(8, 'https://media2.giphy.com/media/MAcTx2rdS1qTprIScT/200w.gif', '1'),
(9, 'https://media2.giphy.com/media/USUIWSteF8DJoc5Snd/200w.gif', '1'),
(11, 'https://media3.giphy.com/media/h2TL9kJUgFoaeuEVR9/200w.gif', '1'),
(12, 'https://media1.giphy.com/media/YNDLZBTq8hGPDJkmYo/200w.gif', '1'),
(13, 'https://media2.giphy.com/media/ehC4SqtNcEeLAiu66w/200w.gif', '1'),
(14, 'https://media3.giphy.com/media/RlktKWfBX1RAwSTPxz/200w.gif', '1'),
(15, 'https://media2.giphy.com/media/XHdW0gCDj6KiFmKFCZ/200w.gif', '1'),
(16, 'https://media1.giphy.com/media/PmuLLvty3SDOIaEh77/200w.gif', '1'),
(17, 'https://media3.giphy.com/media/h86PVmFNq6f2l3gMWI/200w.gif', '1'),
(18, 'https://media0.giphy.com/media/h4OGa0npayrJX2NRPT/200w.gif', '1'),
(19, 'https://media0.giphy.com/media/jqkipjwTMxNRgCKzad/200w.gif', '1'),
(20, 'https://media0.giphy.com/media/U1sneBtWxluPUlEOiF/200w.gif', '1'),
(21, 'https://media1.giphy.com/media/4tSHBpzJw7R3rrKUeo/200w.gif', '1'),
(22, 'https://media3.giphy.com/media/cNqBzFAC3aU2gDuD4k/200w.gif', '1'),
(23, 'https://media2.giphy.com/media/fvSnAaFUjIqh6XXIFp/200w.gif', '1'),
(24, 'https://media0.giphy.com/media/ViHbdDMcIOeLeblrbq/200w.gif', '1'),
(25, 'https://media2.giphy.com/media/LOnt6uqjD9OexmQJRB/200w.gif', '1'),
(26, 'https://media1.giphy.com/media/gfI2SFqrtxwf2Aw9x5/200w.gif', '1'),
(27, 'https://media3.giphy.com/media/KEexxjxK16lBoE162o/200w.gif', '1'),
(28, 'https://media4.giphy.com/media/QBSPma5jP9ReSAdxKw/200w.gif', '1'),
(29, 'https://media0.giphy.com/media/IbaaxVxgaZAZx9ddJ4/200w.gif', '1'),
(30, 'https://media3.giphy.com/media/UQDSBzfyiBKvgFcSTw/200w.gif', '1'),
(31, 'https://media0.giphy.com/media/TF11M0XrowTQWfAgUB/200w.gif', '1'),
(32, 'https://media2.giphy.com/media/Ss0X9wziu1NHEPXe5c/200w.gif', '1'),
(33, 'https://media3.giphy.com/media/Wm8h2gyEY8VnJeru6f/200w.gif', '1'),
(34, 'https://media3.giphy.com/media/iD6QiXTTAYrU5C3c89/200w.gif', '1'),
(35, 'https://media3.giphy.com/media/WprjTWyCWtfbJ11WEM/200w.gif', '1'),
(36, 'https://media2.giphy.com/media/QTlmH8hEoVoi83mdJC/200w.gif', '1'),
(37, 'https://media2.giphy.com/media/KGe942jhOF493O5a4U/200w.gif', '1'),
(38, 'https://media4.giphy.com/media/dalJ0CpF7hwmN1nZXe/200w.gif', '1'),
(39, 'https://media1.giphy.com/media/kyzzHEoaLAAr9nX4fy/200w.gif', '1'),
(40, 'https://media3.giphy.com/media/TjdjGJc9ti9RIFk0IJ/200w.gif', '1'),
(41, 'https://media1.giphy.com/media/YondZW6AMjgTEHevF0/200w.gif', '1'),
(42, 'https://media2.giphy.com/media/SpnN24fEeWGLPlhs8b/200w.gif', '1'),
(43, 'https://media2.giphy.com/media/S3nZ8V9uemShxiWX8g/200w.gif', '1'),
(44, 'https://media3.giphy.com/media/jVIKa3erp2SqgmmrAK/200w.gif', '1'),
(45, 'https://media2.giphy.com/media/L4ZI0w4waQ2vSkMgU9/200w.gif', '1'),
(46, 'https://media1.giphy.com/media/J2awouDsf23R2vo2p5/200w.gif', '1'),
(47, 'https://media3.giphy.com/media/J3MPB75dHsHQ1ZSFnK/200w.gif', '1'),
(48, 'https://media1.giphy.com/media/ZdNlmHHr7czumQPvNE/200w.gif', '1'),
(49, 'https://media1.giphy.com/media/UsLzFcO1wZCgnAFFvi/200w.gif', '1'),
(50, 'https://media2.giphy.com/media/TGXoYOYmVQ9v6M3g1q/200w.gif', '1'),
(51, 'https://media2.giphy.com/media/QWvra259h4LCvdJnxP/200w.gif', '1'),
(52, 'https://media2.giphy.com/media/kZil2V0A3W7ksWTuQn/200w.gif', '1'),
(53, 'https://media0.giphy.com/media/L3X2BTnSL6kGpPmJtr/200w.gif', '1'),
(54, 'https://media4.giphy.com/media/lRXY41yFFi9RfNXyPN/200w.gif', '1'),
(55, 'https://media2.giphy.com/media/SqYhwWASzIrjjq07u8/200w.gif', '1'),
(56, 'https://media1.giphy.com/media/iigp4VDyf5dCLRlGkm/200w.gif', '1'),
(57, 'https://media0.giphy.com/media/McVAzihiFCpV5N5HZE/200w.gif', '1'),
(58, 'https://media0.giphy.com/media/VDNDX5BhKKz0YsJkl0/200w.gif', '1'),
(59, 'https://media4.giphy.com/media/Iblv3w6yvj9DTMwCsJ/200w.gif', '1'),
(60, 'https://media2.giphy.com/media/QX1vLPZxlUh1bzbgbq/200w.gif', '1'),
(61, 'https://media3.giphy.com/media/hVlZnRT6QW1DeYj6We/200w.gif', '1'),
(62, 'https://media1.giphy.com/media/XEyXIfu7IRQivZl1Mw/200w.gif', '1'),
(63, 'https://media3.giphy.com/media/IzcFv6WJ4310bDeGjo/200w.gif', '1'),
(64, 'https://media1.giphy.com/media/KzzcDujlIFIavC8knA/200w.gif', '1'),
(65, 'https://media4.giphy.com/media/l4RS2PG61HIYiukdoT/200w.gif', '1'),
(66, 'https://media3.giphy.com/media/dxyawae0djPD2CTNyS/200w.gif', '1'),
(67, 'https://media2.giphy.com/media/ehz3LfVj7NvpY8jYUY/200w.gif', '1'),
(68, 'https://media3.giphy.com/media/QU3wZZG8x351iQAbfm/200w.gif', '1'),
(69, 'https://media2.giphy.com/media/baTgA2UrV2GZuHTqUq/200w.gif', '1'),
(70, 'https://media1.giphy.com/media/QTxCEgcvorSGCSXxKE/200w.gif', '1'),
(71, 'https://media3.giphy.com/media/YqFACC5oHsyy3l31k1/200w.gif', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_stories_seen`
--

CREATE TABLE `i_stories_seen` (
  `i_seen_id` int NOT NULL,
  `i_seen_uid_fk` int DEFAULT NULL,
  `i_seen_storie_id` int DEFAULT NULL,
  `seen_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_stories_seen`
--

INSERT INTO `i_stories_seen` (`i_seen_id`, `i_seen_uid_fk`, `i_seen_storie_id`, `seen_time`) VALUES
(1, 1, 1, 1777681851);

-- --------------------------------------------------------

--
-- Table structure for table `i_story_audio`
--

CREATE TABLE `i_story_audio` (
  `id` int NOT NULL,
  `title` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `artist` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `audio_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `duration` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_story_configuration`
--

CREATE TABLE `i_story_configuration` (
  `id` int NOT NULL,
  `story_fk_key` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sstatus` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_story_configuration`
--

INSERT INTO `i_story_configuration` (`id`, `story_fk_key`, `sstatus`) VALUES
(1, 'story_status', 'yes'),
(2, 'story_image_status', 'yes'),
(3, 'story_text_status', 'yes'),
(4, 'who_can_create_story', 'no');

-- --------------------------------------------------------

--
-- Table structure for table `i_story_highlights`
--

CREATE TABLE `i_story_highlights` (
  `id` int NOT NULL,
  `owner_id` int NOT NULL,
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cover_story_id` int DEFAULT NULL,
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `privacy` enum('followers','subscribers','everyone') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('active','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_story_highlight_items`
--

CREATE TABLE `i_story_highlight_items` (
  `id` int NOT NULL,
  `highlight_id` int NOT NULL,
  `story_id` int NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `added_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_story_reactions`
--

CREATE TABLE `i_story_reactions` (
  `reaction_id` int NOT NULL,
  `story_id_fk` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `reaction` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_story_replies`
--

CREATE TABLE `i_story_replies` (
  `sr_id` int NOT NULL,
  `story_id_fk` int NOT NULL,
  `sender_iuid_fk` int NOT NULL,
  `owner_iuid_fk` int NOT NULL,
  `sent_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_story_text_bg`
--

CREATE TABLE `i_story_text_bg` (
  `st_bg_id` int NOT NULL,
  `st_bg_img_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `st_bg_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `choosed_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_story_text_bg`
--

INSERT INTO `i_story_text_bg` (`st_bg_id`, `st_bg_img_url`, `st_bg_status`, `choosed_status`) VALUES
(1, 'uploads/files/2022-04-07/image_1649346422018445700_1.jpg', '1', 'ok'),
(2, 'uploads/files/2022-04-07/image_1649345318076964800_1.jpg', '1', NULL),
(3, 'uploads/files/2022-04-07/image_1649345326039550800_1.jpg', '1', NULL),
(4, 'uploads/files/2022-04-07/image_1649345333035742500_1.jpg', '1', NULL),
(5, 'uploads/files/2022-04-07/image_1649345340031957300_1.jpg', '1', NULL),
(6, 'uploads/files/2022-04-07/image_1649345346027801300_1.jpg', '1', NULL),
(7, 'uploads/files/2022-04-07/image_1649345352009644300_1.jpg', '1', NULL),
(8, 'uploads/files/2022-04-07/image_1649345385057953200_1.jpg', '1', NULL),
(9, 'uploads/files/2022-04-07/image_1649345390090665800_1.jpg', '1', NULL),
(10, 'uploads/files/2022-04-07/image_1649345397013104700_1.jpg', '1', NULL),
(11, 'uploads/files/2022-04-07/image_1649345402078257600_1.jpg', '1', NULL),
(12, 'uploads/files/2022-04-07/image_1649345411069262000_1.png', '1', NULL),
(13, 'uploads/files/2022-04-07/image_1649345420094367200_1.png', '1', NULL),
(14, 'uploads/files/2022-04-07/image_1649345426074487000_1.jpg', '1', NULL),
(15, 'uploads/files/2022-04-07/image_1649345432068402000_1.png', '1', NULL),
(16, 'uploads/files/2022-04-07/image_1649345437080379100_1.jpg', '1', NULL),
(17, 'uploads/files/2022-04-07/image_1649345443023129600_1.png', '1', NULL),
(18, 'uploads/files/2022-04-07/image_1649345448014653300_1.jpg', '1', NULL),
(19, 'uploads/files/2022-04-07/image_1649345454038246900_1.jpg', '1', NULL),
(20, 'uploads/files/2022-04-07/image_1649345459056842800_1.jpg', '1', NULL),
(21, 'uploads/files/2022-04-07/image_1649345466048181300_1.png', '1', NULL),
(22, 'uploads/files/2022-04-07/image_1649345475008384700_1.jpg', '1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_svg_icons`
--

CREATE TABLE `i_svg_icons` (
  `icon_id` int NOT NULL,
  `icon_code` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `icon_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_svg_icons`
--

INSERT INTO `i_svg_icons` (`icon_id`, `icon_code`, `icon_status`) VALUES
(1, '<svg id=\"Layer_1\" data-name=\"Layer 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><polygon points=\"138.71 137 132.29 137 120.29 197 150.71 197 138.71 137\"/><path d=\"M381.37,257A157,157,0,0,0,406,300.47c9.53-12,19.15-26.07,25.63-43.47Z\"/><path d=\"M467,91H280.72l38.84,311.68c.69,12.75-2.8,24.75-11.12,34.14L242.66,512H467a45.05,45.05,0,0,0,45-45V137C512,112.19,491.81,91,467,91Zm0,166h-4a190.35,190.35,0,0,1-36.13,65.7c11,10.08,22.8,18.34,34.51,27.6a15,15,0,1,1-18.75,23.4c-12.72-10-24.67-18.45-36.62-29.42-11.95,11-22.9,19.38-35.63,29.42a15,15,0,0,1-18.75-23.4c11.72-9.26,22.5-17.52,33.52-27.6-14.06-16.89-26.6-38.32-35.13-65.7h-4a15,15,0,0,1,0-30h45V212a15,15,0,0,1,30,0v15h46a15,15,0,0,1,0,30Z\"/><path d=\"M244.16,39.42A45.05,45.05,0,0,0,199.52,0H45A45.05,45.05,0,0,0,0,45V377a45.05,45.05,0,0,0,45,45H281.55c4.38-5,8.05-8.13,8.2-14.66C289.79,405.7,244.37,41,244.16,39.42ZM183.94,286.71a15,15,0,0,1-17.65-11.77L156.71,227H114.29l-9.58,47.94a15,15,0,1,1-29.42-5.88l30-150A15,15,0,0,1,120,107h31a15,15,0,0,1,14.71,12.06l30,150A15,15,0,0,1,183.94,286.71Z\"/><path d=\"M175.26,452l2.58,20.58c1.71,13.78,10.87,27.84,25.93,34.86L254.13,452Z\"/></svg>', '1'),
(2, '<svg id=\"Layer_2\" data-name=\"Layer 2\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 511.71\"><path d=\"M273.21,78.76l25,13.68,13.67,25a15,15,0,0,0,26.31,0l13.67-25,25-13.68a15,15,0,0,0,0-26.3l-25-13.67-13.67-25a15,15,0,0,0-26.31,0l-13.67,25-25,13.67a15,15,0,0,0,0,26.3ZM316.45,63a15.05,15.05,0,0,0,6-6l2.63-4.8,2.62,4.8a15.12,15.12,0,0,0,6,6l4.8,2.62-4.8,2.63a15,15,0,0,0-6,6L325,79l-2.63-4.8a14.93,14.93,0,0,0-6-6l-4.8-2.63Z\" transform=\"translate(0 -0.15)\"/><circle cx=\"248.35\" cy=\"130.4\" r=\"14.99\" transform=\"translate(79.86 354.51) rotate(-80.78)\"/><path d=\"M392.56,305.86a15,15,0,1,0,21.2,0A15,15,0,0,0,392.56,305.86Z\" transform=\"translate(0 -0.15)\"/><circle cx=\"397.93\" cy=\"136.81\" r=\"14.99\"/><path d=\"M159.41,92.11A15,15,0,0,0,178.56,83l15.75-44.32a15,15,0,0,0-28.25-10L150.31,73A15,15,0,0,0,159.41,92.11Z\" transform=\"translate(0 -0.15)\"/><path d=\"M500.67,149.86a15,15,0,0,0-18.2,10.87l-10.38,41.16a220.82,220.82,0,0,1-23.61-16.51A20.66,20.66,0,0,0,415,199.57c-1.08,11.69-1.93,23.52-2.54,35.31-8-4.08-15.94-8.46-23.71-12.74-5.73-3.16-11.66-6.43-17.56-9.56a20.66,20.66,0,0,0-30.33,18.09c-.11,13.52-.12,27.24,0,40.78a15,15,0,0,0,15,14.89h.1a15,15,0,0,0,14.89-15.08c0-8.23-.07-16.53,0-24.8l3.52,1.93c12.31,6.79,25,13.8,38.25,19.86a20.65,20.65,0,0,0,29.28-18c.36-10.36.92-20.8,1.68-31.2a247.31,247.31,0,0,0,25,15.3,19.5,19.5,0,0,0,15.8,1.32c5.8-2,10.54-6.71,13.35-13.2a15.18,15.18,0,0,0,.78-2.3l13.13-52.08a15,15,0,0,0-10.87-18.2Z\" transform=\"translate(0 -0.15)\"/><path d=\"M466.51,90.54a45.2,45.2,0,1,0-45.2-45.2A45.25,45.25,0,0,0,466.51,90.54Zm0-60.41a15.22,15.22,0,1,1-15.22,15.21A15.22,15.22,0,0,1,466.51,30.13Z\" transform=\"translate(0 -0.15)\"/><path d=\"M279.77,202.56a14.89,14.89,0,0,0,5.43-1l43.83-17a15,15,0,1,0-10.86-28l-43.84,17.05a15,15,0,0,0,5.44,29Z\" transform=\"translate(0 -0.15)\"/><path d=\"M172.41,147.27a47.43,47.43,0,1,0-67.08,67.08l6.36,6.36C78.83,301.06,34.07,410.53,1.12,491.19a15,15,0,0,0,19.54,19.55L291.18,400.21l9.35,9.34a47.43,47.43,0,0,0,67.08-67.08Zm76.38,210.55L115.23,291.29l19.45-47.59Zm-82.28,60.94-87-40,24.38-59.64L242,387.92Zm-98.4-12.23,60.39,27.76L42.38,469.48Zm278.3-18.18a17.45,17.45,0,0,1-24.68,0L140.08,206.71l-.26-.27-13.29-13.29a17.45,17.45,0,0,1,24.68-24.68l195.2,195.2a17.45,17.45,0,0,1,0,24.68Z\" transform=\"translate(0 -0.15)\"/></svg>', '1'),
(3, '<svg id=\"Layer_3\" data-name=\"Layer 3\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 419.19\"><path class=\"cls-1\" d=\"M500.4,94.78a194.35,194.35,0,0,1-24.76,9,109.48,109.48,0,0,0,22.28-39.2A8.26,8.26,0,0,0,485.84,55a194.55,194.55,0,0,1-57.6,22.77,110,110,0,0,0-186.85,78.56,111.75,111.75,0,0,0,.9,14.2A281.4,281.4,0,0,1,49.16,68.05a8.26,8.26,0,0,0-13.54,1.07A110.06,110.06,0,0,0,46.89,195.63a93.1,93.1,0,0,1-14.71-6.57,8.26,8.26,0,0,0-12.27,7c0,.49,0,1,0,1.47a110.31,110.31,0,0,0,53.81,94.52q-4.2-.42-8.37-1.22a8.26,8.26,0,0,0-9.41,10.63,109.86,109.86,0,0,0,80.52,73.67A194.46,194.46,0,0,1,32.51,404.83a198.06,198.06,0,0,1-23.28-1.37A8.26,8.26,0,0,0,3.8,418.62a296.46,296.46,0,0,0,160.28,47C276,465.6,346,412.83,385,368.56c48.66-55.2,76.56-128.26,76.56-200.45,0-3,0-6.06-.14-9.1a213.72,213.72,0,0,0,49.18-52.08A8.26,8.26,0,0,0,500.4,94.78Z\" transform=\"translate(0 -46.41)\"/></svg>', '1'),
(4, '<svg id=\"Capa_4\" data-name=\"Capa 4\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><defs><style>.g-1{fill:#fbbd00;}.g-2{fill:#0f9d58;}.g-3{fill:#31aa52;}.g-4{fill:#3c79e6;}.g-5{fill:#cf2d48;}.g-6{fill:#eb4132;}</style></defs><path class=\"g-1\" d=\"M120,256a135.19,135.19,0,0,1,19.13-69.48v-86.3H52.82a256.14,256.14,0,0,0,0,311.57h86.31V325.48A135.19,135.19,0,0,1,120,256Z\" transform=\"translate(0 0)\"/><path class=\"g-2\" d=\"M256,392l-60,60,60,60a254.11,254.11,0,0,0,155.79-52.82V373H325.57A136,136,0,0,1,256,392Z\" transform=\"translate(0 0)\"/><path class=\"g-3\" d=\"M139.13,325.48l-86.31,86.3A259.55,259.55,0,0,0,75,437a254.33,254.33,0,0,0,181,75V392a136.06,136.06,0,0,1-116.87-66.52Z\" transform=\"translate(0 0)\"/><path class=\"g-4\" d=\"M512,256a258.51,258.51,0,0,0-4.19-46.38l-2.25-12.3H256v120H377.45A135.3,135.3,0,0,1,325.57,373l86.21,86.22A259.55,259.55,0,0,0,437,437,254.33,254.33,0,0,0,512,256Z\" transform=\"translate(0 0)\"/><path class=\"g-5\" d=\"M352.17,159.83l10.6,10.61,84.86-84.85L437,75A254.33,254.33,0,0,0,256,0L196,60l60,60A135.14,135.14,0,0,1,352.17,159.83Z\" transform=\"translate(0 0)\"/><path class=\"g-6\" d=\"M256,120V0A254.33,254.33,0,0,0,75,75a259.55,259.55,0,0,0-22.16,25.24l86.31,86.3A136.06,136.06,0,0,1,256,120Z\" transform=\"translate(0 0)\"/></svg>', '1'),
(5, '<svg id=\"Layer_5\" data-name=\"Layer 6\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 329 328.99\"><path d=\"M194.64,164.77,322.75,36.66A21.31,21.31,0,0,0,292.61,6.52L164.5,134.63,36.39,6.52A21.31,21.31,0,0,0,6.25,36.66L134.36,164.77,6.25,292.88A21.31,21.31,0,0,0,36.39,323L164.5,194.91,292.61,323a21.31,21.31,0,0,0,30.14-30.14Z\" transform=\"translate(0 -0.27)\"/></svg>', '1'),
(6, '<svg id=\"Capa_6\" data-name=\"Capa 6\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 482 512\"><circle cx=\"256.09\" cy=\"236.5\" r=\"13.79\" transform=\"translate(-53.31 423.17) rotate(-75.1)\"/><path d=\"M256,359.67a107.25,107.25,0,0,0,86.68-170.4V86.68a86.68,86.68,0,0,0-173.36,0V189.27A107.25,107.25,0,0,0,256,359.67Zm15-82.17v20.05a15,15,0,0,1-30,0V277.5a43.79,43.79,0,1,1,30,0ZM199.32,86.68a56.68,56.68,0,0,1,113.36,0v74.69a107.31,107.31,0,0,0-113.36,0Z\" transform=\"translate(-15 0)\"/><path d=\"M435.92,389.84H76.08a61.08,61.08,0,0,0,0,122.16H435.92a61.08,61.08,0,0,0,0-122.16ZM97.36,467a16,16,0,1,1,16-16A16,16,0,0,1,97.36,467Zm79.32,0a16,16,0,1,1,16-16A16,16,0,0,1,176.68,467ZM256,467a16,16,0,1,1,16-16A16,16,0,0,1,256,467Zm79.32,0a16,16,0,1,1,16-16A16,16,0,0,1,335.32,467Zm79.32,0a16,16,0,1,1,16-16A16,16,0,0,1,414.64,467Z\" transform=\"translate(-15 0)\"/></svg>', '1'),
(7, '<svg id=\"Capa_7\" data-name=\"Capa 7\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 514.34 511.78\"><path d=\"M40,197.86,9.12,189.42a260.25,260.25,0,0,0-6.85,33.4L34,227.09A221.88,221.88,0,0,1,40,197.86Z\" transform=\"translate(0 -1.28)\"/><path d=\"M138.37,307.5A126.45,126.45,0,0,1,128,257.06,128,128,0,0,1,186.06,150l38.48-25.29L125.34,116,133,29.86l-28.58,21A256.94,256.94,0,0,0,20.27,157.14L49.7,169.66A224.21,224.21,0,0,1,94.4,102l-3.76,43L138,149.2a159.8,159.8,0,0,0-31.12,165.73L94.4,361.14,52,349.76a225.19,225.19,0,0,1-20-92.91l-32,.21A256.82,256.82,0,0,0,26.45,370.38l3.2,6.48,87.41,23.49,23.5-87.73Z\" transform=\"translate(0 -1.28)\"/><path d=\"M438.08,77.26a255.5,255.5,0,0,0-25.6-22.6L393,80a223.81,223.81,0,0,1,22.4,19.79Z\" transform=\"translate(0 -1.28)\"/><path d=\"M271.28,130.06A127.07,127.07,0,0,1,383.7,249.9l2.46,46.32,57.3-81.71,70.88,49.43-3.94-35A255.16,255.16,0,0,0,460.3,103l-25.45,19.28a223.83,223.83,0,0,1,36.37,72.56L435.58,170l-27,38.54A158.83,158.83,0,0,0,280.54,99l-33.92-34,30.84-30.84A221,221,0,0,1,368.21,63.3l16-27.7A253.09,253.09,0,0,0,272.37,1.73l-7.22-.45L201.38,65.06l64.36,64.36Z\" transform=\"translate(0 -1.28)\"/><path d=\"M361.71,329.26l-3.33,4.44A128.54,128.54,0,0,1,256,385.06a126.22,126.22,0,0,1-57.7-13.78l-41.5-21.09,42.35,90.48-78.4,36.42L153,491.38a253.08,253.08,0,0,0,103,21.68,260.9,260.9,0,0,0,37.15-2.69l-4.62-31.65a226.75,226.75,0,0,1-86.29-4.18l39.54-18.38-20-42.77a160.14,160.14,0,0,0,158.79-56L426.93,345l11.37,42.24a227.26,227.26,0,0,1-66.06,61.36l16.62,27.34A258.86,258.86,0,0,0,469,399.12l4-6.05L449.6,305.79Z\" transform=\"translate(0 -1.28)\"/><path d=\"M317.7,472.43l8.81,30.79a257.45,257.45,0,0,0,32.13-11.59l-12.8-29.31A217.86,217.86,0,0,1,317.7,472.43Z\" transform=\"translate(0 -1.28)\"/></svg>', '1'),
(8, '<svg id=\"Capa_8\" data-name=\"Capa 8\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 426.67 426.67\"><path d=\"M213.33,236.8a23.47,23.47,0,1,0-23.46-23.47A23.47,23.47,0,0,0,213.33,236.8Z\"/><path d=\"M213.33,0C95.47,0,0,95.47,0,213.33S95.47,426.67,213.33,426.67s213.34-95.58,213.34-213.34S331.2,0,213.33,0Zm46.72,260.05L85.33,341.33l81.28-174.72L341.33,85.33Z\"/></svg>', '1'),
(9, '<svg id=\"Capa_9\" data-name=\"Capa 9\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 450 512\"><path d=\"M398.44,108.68a15,15,0,0,0-10.88,12.1C380.8,162.82,366.71,181,361,181c-23.61,0-65.93-88.55-90.64-170.34C268.45,4.34,263.09.28,256,0a15,15,0,0,0-14.31,10.52C216.6,90.67,173.18,181,151,181c-5.71,0-19.8-18.18-26.56-60.22a15,15,0,0,0-26.38-7.16C51.68,169.9,31,223.06,31,286c0,126.17,98.83,226,225,226s225-99.83,225-226c0-62.08-21.31-116.85-67.06-172.38A15,15,0,0,0,398.44,108.68ZM260.75,450.22a14.85,14.85,0,0,1-9.5,0C247.17,448.86,151,415.83,151,331a60,60,0,0,1,105-39.64A60,60,0,0,1,361,331C361,415.83,264.83,448.86,260.75,450.22Z\" transform=\"translate(-31 0)\"/><path d=\"M301,301a30,30,0,0,0-30,30,15,15,0,0,1-30,0,30,30,0,0,0-60,0c0,54.29,57.77,81.93,75,88.95,17.18-7.08,75-34.93,75-88.95A30,30,0,0,0,301,301Z\" transform=\"translate(-31 0)\"/></svg>', '1'),
(10, '<svg id=\"Capa_10\" data-name=\"Capa 10\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 572.1 314.96\"><path d=\"M286.05,128.57A338.21,338.21,0,0,0,13,268.1L0,286.05l13,18a336.92,336.92,0,0,0,546.08,0l13-17.95-13-18A338.23,338.23,0,0,0,286.05,128.57Zm84.07,115.58a30.82,30.82,0,1,1-30.82-30.82A30.82,30.82,0,0,1,370.12,244.15ZM286.05,382.33A276.83,276.83,0,0,1,76.76,286.05a277.31,277.31,0,0,1,107-76.43,112.2,112.2,0,1,0,204.68,0,277.31,277.31,0,0,1,107,76.43A276.84,276.84,0,0,1,286.05,382.33Z\" transform=\"translate(0 -128.57)\"/></svg>', '1'),
(11, '<svg id=\"Capa_11\" data-name=\"Capa 11\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 509.61 486.16\"><path d=\"M34.26,333.28c8.12,6.75,14.8,15.23,14.14,21a95.54,95.54,0,0,0-.59,10.5c0,52.39,41.3,94.86,92.24,94.86a90.49,90.49,0,0,0,18.37-1.88c10-2.08,21.81,1.94,28.89,9.79a90.4,90.4,0,0,0,137.83-3.18,27,27,0,0,1,28.22-8.5,89.56,89.56,0,0,0,25.76,3.77c50.93,0,92.24-42.48,92.24-94.86a95.68,95.68,0,0,0-1.55-17.14c-1.72-9.38,2.85-21.05,10.47-28.37a96.39,96.39,0,0,0-4.94-142.94c-8.12-6.75-14.79-15.22-14.14-21a95.54,95.54,0,0,0,.59-10.5c0-52.39-41.3-94.86-92.24-94.86a89.79,89.79,0,0,0-18.37,1.89c-10,2.07-21.81-1.94-28.89-9.8a90.23,90.23,0,0,0-135,0c-7.08,7.85-18.89,11.87-28.89,9.8A89.79,89.79,0,0,0,140.05,50c-50.94,0-92.24,42.48-92.24,94.86A95.33,95.33,0,0,0,49,159.66c1.26,8.13-4.45,18.15-12.75,24.69a96.33,96.33,0,0,0-2,148.93ZM131.48,263c2-3.63,7.26-3.68,12,.47l48.12,33.92a14.5,14.5,0,0,0,7.51,2.4c5,0,8.89-3.57,12.69-7l154-154.14c9.63-10.47,19-14.16,25.1-10.14a11.41,11.41,0,0,1,5.14,7.87c.58,3.82-.76,7.94-3.77,11.6L207.14,372.89c-2.68,3.26-6.11,5.06-9.63,5.06-4.18,0-8-2.52-10.4-6.9l-54.34-98C130.52,269.42,130,265.62,131.48,263Z\" transform=\"translate(0 -11.72)\"/></svg>', '1'),
(12, '<svg id=\"Capa_12\" data-name=\"Capa 12\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 220 512\"><path d=\"M256,100a50,50,0,1,1,50-50A50.06,50.06,0,0,1,256,100Z\" transform=\"translate(-146)\"/><path d=\"M318,100.46a79.88,79.88,0,0,1-124,0A55.07,55.07,0,0,0,146,155V275a45.06,45.06,0,0,0,40,44.72V477a35,35,0,0,0,35,35h70a35,35,0,0,0,35-35V319.72A45.06,45.06,0,0,0,366,275V155A55.07,55.07,0,0,0,318,100.46Z\" transform=\"translate(-146)\"/></svg>', '1'),
(13, '<svg id=\"Layer_13\" data-name=\"Layer 13\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 260.41 512\"><path d=\"M385,276.11c-.33-.9-33.15-90.69-47.61-132.74-9.32-27.14-25-40.9-46.47-40.9H221.1c-21.51,0-37.15,13.76-46.47,40.9-14.46,42-47.28,131.84-47.61,132.74a20.1,20.1,0,0,0,37.76,13.81c.27-.75,22.77-62.32,38.82-107.65a22.8,22.8,0,0,0,8.23,11.63l0,29.45-34.57,87.18a8.39,8.39,0,0,0,5.83,11.26c5.16,1.24,12,2.93,19.41,4.78V487.88a24.12,24.12,0,0,0,48.24,0V338a59.45,59.45,0,0,0,10.42,0V487.88a24.12,24.12,0,0,0,48.24,0V326.55c7.4-1.85,14.23-3.54,19.39-4.78a8.39,8.39,0,0,0,5.84-11.26l-34.56-87.16.05-29.45a22.8,22.8,0,0,0,8.23-11.63c16.05,45.33,38.55,106.9,38.82,107.65A20.1,20.1,0,0,0,385,276.11Z\" transform=\"translate(-125.8)\"/><circle cx=\"130.2\" cy=\"43.9\" r=\"43.9\"/></svg>', '1'),
(14, '<svg id=\"Capa_14\" data-name=\"Capa 14\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 566.8 870.3\"><path d=\"M561.05,288.9a322,322,0,0,1,77.1,46.2V201.5C638.15,90.4,547.75,0,436.65,0h-3.1c-111,0-201.4,90.4-201.4,201.5V335.1a322,322,0,0,1,77.1-46.2c7.5-3.2,15.2-6.1,22.9-8.7V201.5A101.57,101.57,0,0,1,433.65,100h3.1a101.57,101.57,0,0,1,101.5,101.5v78.8C545.85,282.8,553.55,285.7,561.05,288.9Z\" transform=\"translate(-151.75)\"/><path d=\"M151.75,586.9c0,156.5,126.9,283.4,283.4,283.4s283.4-126.8,283.4-283.4a283.4,283.4,0,1,0-566.8,0Zm210.4-57.2A73,73,0,1,1,474,593.4a19.86,19.86,0,0,0-8.9,21l21.7,102.9a10,10,0,0,1-9.8,12.1h-83.6a10,10,0,0,1-9.8-12.1l21.7-102.9a20.1,20.1,0,0,0-8.9-21.1,72.85,72.85,0,0,1-33.1-49.3A74.66,74.66,0,0,1,362.15,529.7Z\" transform=\"translate(-151.75)\"/></svg>', '1'),
(15, '<svg id=\"Capa_15\" data-name=\"Capa 15\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 388.23 330\"><path d=\"M194.11,190.51c36.69,0,66.44-36.13,66.44-80.7s-9.77-80.7-66.44-80.7-66.43,36.13-66.43,80.7S157.42,190.51,194.11,190.51Z\" transform=\"translate(0 -29.11)\"/><path d=\"M319.45,310.46c-1.22-77.64-11.36-99.76-88.95-113.76,0,0-10.93,13.91-36.39,13.91s-36.37-13.91-36.37-13.91C81,210.55,70.23,232.34,68.82,307.94c-.12,6.17-.17,6.49-.19,5.78,0,1.34,0,3.82,0,8.15,0,0,18.47,37.24,125.48,37.24s125.47-37.24,125.47-37.24c0-2.78,0-4.72,0-6C319.58,316.28,319.53,315.42,319.45,310.46Z\" transform=\"translate(0 -29.11)\"/><path d=\"M286.31,176.1c29.8,0,54-29.35,54-65.54S332.34,45,286.31,45a76.68,76.68,0,0,0-20.13,2.39c10.63,19.59,12.09,43.4,12.09,62.4a110.67,110.67,0,0,1-16.58,59.06A46.25,46.25,0,0,0,286.31,176.1Z\" transform=\"translate(0 -29.11)\"/><path d=\"M388.11,273.52c-1-63-9.23-81-72.25-92.4,0,0-8.87,11.31-29.55,11.31-.85,0-1.68,0-2.5-.06,13.14,5.92,25.09,14.17,33.89,26.23,15.22,20.87,18.71,48.89,19.44,90.07,42.39-8.38,51.08-25.88,51.08-25.88,0-2.28,0-3.84,0-4.91C388.21,278.26,388.17,277.58,388.11,273.52Z\" transform=\"translate(0 -29.11)\"/><path d=\"M101.91,176.1a46.2,46.2,0,0,0,24.62-7.23c-10.74-17-16.57-37.55-16.57-59.06,0-19,1.46-42.81,12.09-62.4A76.71,76.71,0,0,0,101.91,45C55.89,45,48,74.36,48,110.56S72.11,176.1,101.91,176.1Z\" transform=\"translate(0 -29.11)\"/><path d=\"M104.41,192.37c-.81,0-1.64.06-2.5.06-20.67,0-29.54-11.31-29.54-11.31C9.35,192.5,1.11,210.46.12,273.52A36.71,36.71,0,0,1,0,277.88v4.91s8.69,17.5,51.09,25.87c.72-41.17,4.22-69.19,19.44-90.06C79.33,206.54,91.28,198.29,104.41,192.37Z\" transform=\"translate(0 -29.11)\"/></svg>', '1'),
(16, '<svg id=\"Capa_16\" data-name=\"Capa 16\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 426.67 85.33\"><circle cx=\"42.67\" cy=\"42.67\" r=\"42.67\"/><circle cx=\"213.33\" cy=\"42.67\" r=\"42.67\"/><circle cx=\"384\" cy=\"42.67\" r=\"42.67\"/></svg>', '1'),
(17, '<svg id=\"Layer_17\" data-name=\"Layer 17\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 455.52\"><path d=\"M256,483.52a30,30,0,0,1-19.79-7.44C215.52,458,195.58,441,178,426l-.09-.07C126.32,382,81.77,344,50.78,306.61,16.14,264.81,0,225.17,0,181.87,0,139.8,14.43,101,40.62,72.58,67.12,43.83,103.49,28,143,28c29.56,0,56.62,9.34,80.45,27.77a164.53,164.53,0,0,1,32.52,34,164.57,164.57,0,0,1,32.53-34C312.35,37.34,339.42,28,369,28c39.54,0,75.91,15.83,102.42,44.58C497.58,101,512,139.8,512,181.87c0,43.3-16.13,82.94-50.78,124.74-31,37.4-75.53,75.35-127.1,119.31-17.63,15-37.6,32-58.33,50.17A30,30,0,0,1,256,483.52ZM143,58C112,58,83.43,70.39,62.66,92.91,41.59,115.76,30,147.36,30,181.87c0,36.42,13.53,69,43.88,105.61,29.33,35.39,73,72.57,123.48,115.62l.09.08c17.66,15.05,37.68,32.11,58.52,50.33,21-18.25,41-35.34,58.7-50.42,50.52-43,94.14-80.22,123.47-115.61C468.48,250.86,482,218.29,482,181.87c0-34.51-11.6-66.11-32.67-89C428.58,70.39,400,58,369,58c-22.76,0-43.65,7.24-62.1,21.5-16.44,12.72-27.89,28.8-34.61,40.05a18.93,18.93,0,0,1-32.52,0c-6.71-11.25-18.17-27.33-34.61-40.05C186.68,65.23,165.79,58,143,58Z\" transform=\"translate(0 -28)\"/></svg>', '1'),
(18, '<svg id=\"Layer_18\" data-name=\"Layer 18\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 455.52\"><path d=\"M471.38,72.58C444.88,43.83,408.51,28,369,28c-29.55,0-56.62,9.34-80.45,27.77a164.79,164.79,0,0,0-32.52,34,164.31,164.31,0,0,0-32.53-34C199.65,37.34,172.58,28,143,28,103.49,28,67.12,43.83,40.61,72.58,14.43,101,0,139.8,0,181.87c0,43.3,16.14,82.94,50.78,124.74C81.77,344,126.32,382,177.9,425.93c17.61,15,37.58,32,58.31,50.15a30.06,30.06,0,0,0,39.58,0c20.73-18.13,40.7-35.16,58.32-50.18,51.58-44,96.12-81.9,127.11-119.3C495.87,264.81,512,225.17,512,181.87,512,139.8,497.57,101,471.38,72.58Z\" transform=\"translate(0 -28)\"/></svg>', '1'),
(19, '<svg id=\"Layer_19\" data-name=\"Layer 19\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 511.82 512\"><path d=\"M511.82,255.86,278.49,0V153.27H251.38A251.37,251.37,0,0,0,0,404.65V512l44.78-49.07A321.31,321.31,0,0,1,278.49,358.48V511.73ZM30,434.87V404.65A221.38,221.38,0,0,1,251.38,183.27h57.11V77.43L471.22,255.86,308.49,434.3V328.46H281.57A351.42,351.42,0,0,0,30,434.87Z\"/></svg>', '1'),
(20, '<svg id=\"Capa_20\" data-name=\"Capa 20\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 503.98\"><path d=\"M510.16,401.84,480.42,315.3A216,216,0,0,0,440.27,68.83,216.14,216.14,0,0,0,69.87,217.46,157.58,157.58,0,0,0,22,411.08L1.55,470.69A28.17,28.17,0,0,0,28.08,508a28.46,28.46,0,0,0,9.22-1.55L96.91,486a158.16,158.16,0,0,0,67.37,15.22h.25a157.39,157.39,0,0,0,127.24-64.71,216.81,216.81,0,0,0,89.36-21.87l86.55,29.74a33.62,33.62,0,0,0,11,1.84,33.48,33.48,0,0,0,31.52-44.33ZM164.53,470.69h-.2a127.4,127.4,0,0,1-59.2-14.7,15.25,15.25,0,0,0-12.07-.94l-61.13,21,21-61.13A15.26,15.26,0,0,0,52,402.86,127.09,127.09,0,0,1,72.7,255.54a217.12,217.12,0,0,0,181,178.58A126.73,126.73,0,0,1,164.53,470.69Zm316.1-55.89a2.77,2.77,0,0,1-3.05.7l-92.65-31.83a15.22,15.22,0,0,0-12.08.94,186.22,186.22,0,0,1-86.45,21.46h-.28c-100.87,0-184.18-81.93-185.78-182.77A185.8,185.8,0,0,1,289.13,34.51c100.94,1.6,182.93,85.07,182.77,186.06A186,186,0,0,1,450.44,307a15.23,15.23,0,0,0-.94,12.08l31.84,92.65A2.79,2.79,0,0,1,480.63,414.8Z\" transform=\"translate(0 -4.01)\"/><path d=\"M376.89,139.51H195.33a15.24,15.24,0,0,0,0,30.48H376.89a15.24,15.24,0,0,0,0-30.48Z\" transform=\"translate(0 -4.01)\"/><path d=\"M376.89,202.18H195.33a15.24,15.24,0,0,0,0,30.48H376.89a15.24,15.24,0,1,0,0-30.48Z\" transform=\"translate(0 -4.01)\"/><path d=\"M307,264.85H195.33a15.24,15.24,0,0,0,0,30.48H307a15.24,15.24,0,1,0,0-30.48Z\" transform=\"translate(0 -4.01)\"/></svg>', '1'),
(21, '<svg id=\"Layer_21\" data-name=\"Layer 21\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 469.33 512\"><path d=\"M410.33,160a80,80,0,1,1,80-80A80.1,80.1,0,0,1,410.33,160Zm0-128a48,48,0,1,0,48,48A48.06,48.06,0,0,0,410.33,32Z\" transform=\"translate(-21)\"/><path d=\"M410.33,512a80,80,0,1,1,80-80A80.1,80.1,0,0,1,410.33,512Zm0-128a48,48,0,1,0,48,48A48.06,48.06,0,0,0,410.33,384Z\" transform=\"translate(-21)\"/><path d=\"M101,336a80,80,0,1,1,80-80A80.09,80.09,0,0,1,101,336Zm0-128a48,48,0,1,0,48,48A48.06,48.06,0,0,0,101,208Z\" transform=\"translate(-21)\"/><path d=\"M156.7,240.43a16,16,0,0,1-7.93-29.89l198-112.86a16,16,0,0,1,15.83,27.8l-198,112.86A16.07,16.07,0,0,1,156.7,240.43Z\" transform=\"translate(-21)\"/><path d=\"M354.63,416.43a15.91,15.91,0,0,1-7.89-2.11l-198-112.86a16,16,0,0,1,15.83-27.8l198,112.86a16,16,0,0,1-7.94,29.91Z\" transform=\"translate(-21)\"/></svg>', '1'),
(22, '<svg id=\"Capa_22\" data-name=\"Capa 22\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 512 512\"><path class=\"cls-1\" d=\"M256,0C115,0,0,115.05,0,256S115.05,512,256,512,512,397,512,256,397,0,256,0Zm0,482C131.38,482,30,380.62,30,256S131.38,30,256,30,482,131.38,482,256,380.62,482,256,482ZM400.82,194.8,310,181.61,269.45,99.36a15,15,0,0,0-26.9,0L202,181.61,111.18,194.8a15,15,0,0,0-8.31,25.59l65.68,64-15.5,90.41a15,15,0,0,0,21.76,15.81L256,348l81.19,42.68A15,15,0,0,0,359,374.82l-15.5-90.41,65.68-64a15,15,0,0,0-8.32-25.59Zm-84,73.63a15,15,0,0,0-4.31,13.28l11.7,68.22L263,317.72a15,15,0,0,0-14,0l-61.27,32.21,11.71-68.22a15,15,0,0,0-4.32-13.28l-49.56-48.31,68.49-10a15,15,0,0,0,11.3-8.21L256,139.89,286.63,202a15,15,0,0,0,11.3,8.21l68.49,10Z\"/></svg>', '1'),
(23, '<svg id=\"Layer_23\" data-name=\"Layer 23\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 405.89 512\"><path d=\"M368.89,379v32h48a20,20,0,0,1,0,40h-48v41a20,20,0,0,1-40,0V379a50.06,50.06,0,0,1,50-50h38a20,20,0,0,1,0,40h-38A10,10,0,0,0,368.89,379ZM442.32,95.14,364.75,17.57A59.63,59.63,0,0,0,322.32,0H134A80.09,80.09,0,0,0,54,80V270a20,20,0,0,0,40,0V80a40.05,40.05,0,0,1,40-40H322.32a19.86,19.86,0,0,1,14.14,5.86L414,123.43a19.85,19.85,0,0,1,5.86,14.14V270a20,20,0,0,0,40,0V137.57A59.63,59.63,0,0,0,442.32,95.14ZM188.89,412h-35a20,20,0,0,0,0,40h14c-4.53,12-16.22,20-30,20a32,32,0,0,1-32-32V402a32,32,0,0,1,59.36-16.6,20,20,0,0,0,34.17-20.8A72,72,0,0,0,65.89,402v38a72.08,72.08,0,0,0,72,72,73,73,0,0,0,45.75-16,70.65,70.65,0,0,0,24.86-41.1,19.62,19.62,0,0,0,.39-3.9V432A20,20,0,0,0,188.89,412Zm80-83a20,20,0,0,0-20,20V492a20,20,0,0,0,40,0V349A20,20,0,0,0,268.89,329Z\" transform=\"translate(-54)\"/></svg>', '1'),
(24, '<svg id=\"Capa_24\" data-name=\"Capa 24\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 510\"><path d=\"M348.74,161.06a23.16,23.16,0,0,1,26.81,18.77,7.5,7.5,0,1,0,14.77-2.61,38.13,38.13,0,0,0-75.11,13.25,7.49,7.49,0,0,0,7.37,6.2,7.37,7.37,0,0,0,1.32-.12,7.5,7.5,0,0,0,6.08-8.69A23.15,23.15,0,0,1,348.74,161.06Z\" transform=\"translate(0 -1)\"/><path d=\"M506.55,204.6C492.92,139.22,454.71,82.52,399,45A255.57,255.57,0,0,0,54.82,98.69,7.5,7.5,0,0,0,66.61,108a240.57,240.57,0,0,1,324-50.57C443.06,92.77,479,146.13,491.87,207.67A243.22,243.22,0,0,1,497,256.83a2.83,2.83,0,0,0,0,.29,6.93,6.93,0,0,1-2.25,5,13.9,13.9,0,0,1-9.72,3.66c-.25,0-.51,0-.76,0a258,258,0,0,0-71.06,12c.19-3.82.24-7.64.12-11.45A21.14,21.14,0,0,0,404,249.48a20.76,20.76,0,0,0-19-2.26c-20.56,7.63-64.14,22-124.95,32.76s-106.71,12.09-128.63,11.95h-.14a20.75,20.75,0,0,0-17,8.63,21.13,21.13,0,0,0-3,19,139.47,139.47,0,0,0,20.2,39,7.72,7.72,0,0,0,.94,1.27,147.21,147.21,0,0,0,38.85,35.75,158.63,158.63,0,0,0,84.59,24.2H256a255.41,255.41,0,0,0-18.51,65.49c-1.74,8.59-13.83,10.49-16.31,10.24A241.06,241.06,0,0,1,47.1,136.81a7.5,7.5,0,0,0-13-7.49A256.08,256.08,0,0,0,219.49,510.45a54.5,54.5,0,0,0,5.81.52l1.91,0a54.24,54.24,0,0,0,34-12.07l.15-.11c.65-.53,1.29-1.08,1.92-1.64L445.39,335.51a7.5,7.5,0,0,0-10-11.22L253.33,486h0c-.27.24-.55.46-.83.69a239.92,239.92,0,0,1,21.76-70.54,7.41,7.41,0,0,0,1-1.95,242.25,242.25,0,0,1,41.41-58.86q6.83-7.15,14.14-13.69l.26-.23a238,238,0,0,1,75.59-45.5,8.2,8.2,0,0,0,1.17-.43,243.43,243.43,0,0,1,76.63-14.66l-22.56,20a7.5,7.5,0,1,0,10,11.22l25.89-23a42.46,42.46,0,0,0,14.21-29.53c.05-.84.08-1.68.08-2.53A257.9,257.9,0,0,0,506.55,204.6Zm-243.31,200a144,144,0,0,1-83.89-21.68,133.55,133.55,0,0,1-24.15-19.58c7.5.32,15.07.5,22.72.5a557.69,557.69,0,0,0,95.46-8.21c9.17-1.62,18.45-3.52,27.76-5.68A257,257,0,0,0,263.24,404.62Zm134.48-121.4A252.74,252.74,0,0,0,322.4,329c-17.26,4.87-34.62,8.87-51.63,11.87a538.63,538.63,0,0,1-128.67,6.7,123.8,123.8,0,0,1-16.51-32.69,6,6,0,0,1,.85-5.49,5.9,5.9,0,0,1,4.83-2.46h0c22.44.13,69.34-1.25,131.33-12.18s106.53-25.66,127.57-33.47a5.93,5.93,0,0,1,5.42.64,6,6,0,0,1,2.68,4.87A125.14,125.14,0,0,1,397.72,283.22Z\" transform=\"translate(0 -1)\"/><path d=\"M136,198.57a23.12,23.12,0,0,1,26.8,18.77,7.5,7.5,0,1,0,14.77-2.61A38.13,38.13,0,1,0,102.46,228a7.49,7.49,0,0,0,7.37,6.2,8.27,8.27,0,0,0,1.31-.11,7.5,7.5,0,0,0,6.09-8.69A23.14,23.14,0,0,1,136,198.57Z\" transform=\"translate(0 -1)\"/></svg>', '1'),
(25, '<svg id=\"Capa_25\" data-name=\"Capa 25\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 477.87 477.87\"><path d=\"M238.93,0C107,0,0,107,0,238.93S107,477.87,238.93,477.87s238.94-107,238.94-238.94S370.83.14,238.93,0Zm0,443.73c-113.11,0-204.8-91.69-204.8-204.8s91.69-204.8,204.8-204.8,204.8,91.69,204.8,204.8S352,443.61,238.93,443.73Z\"/><circle cx=\"153.6\" cy=\"204.8\" r=\"34.13\"/><circle cx=\"324.27\" cy=\"204.8\" r=\"34.13\"/><path d=\"M304.29,296.61a17.07,17.07,0,0,0-23.89-3.47c-.33.24-.64.5-.95.77h0a68.71,68.71,0,0,1-81,0,17.06,17.06,0,1,0-22.32,25.82c.3.27.62.52,1,.77a101.92,101.92,0,0,0,123.77,0A17.08,17.08,0,0,0,304.29,296.61Z\"/></svg>', '1'),
(26, '<svg id=\"Layer_26\" data-name=\"Layer 26\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M501.44,10.56A35.78,35.78,0,0,0,467.8,1l-.16,0L65.56,99.4A85.81,85.81,0,0,0,0,182.93V236a36,36,0,0,0,34.46,36L185.7,297.71a10,10,0,0,0,8.75-2.79L372.69,116.69a16,16,0,0,1,22.62,22.62L217.08,317.55a10,10,0,0,0-2.79,8.75L240,477.54A36,36,0,0,0,276,512h53.07a85.81,85.81,0,0,0,83.53-65.56L511,44.36a.94.94,0,0,0,0-.16,35.78,35.78,0,0,0-9.6-33.64Z\" transform=\"translate(0 0)\"/></svg>', '1'),
(27, '<svg id=\"Capa_27\" data-name=\"Capa 27\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 383.95 383.95\"><polygon points=\"0 303.95 0 383.95 80 383.95 316.05 147.89 236.05 67.89 0 303.95\"/><path d=\"M377.71,56.05,327.89,6.24a21.37,21.37,0,0,0-30.18,0l-39,39,80,80,39-39A21.38,21.38,0,0,0,377.71,56.05Z\"/></svg>', '1'),
(28, '<svg id=\"Layer_28\" data-name=\"Layer 28\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 416 512\"><path d=\"M424,64H336V48A48,48,0,0,0,288,0H224a48,48,0,0,0-48,48V64H88a40,40,0,0,0-40,40v32a16,16,0,0,0,16,16H448a16,16,0,0,0,16-16V104A40,40,0,0,0,424,64ZM208,48a16,16,0,0,1,16-16h64a16,16,0,0,1,16,16V64H208Z\" transform=\"translate(-48)\"/><path d=\"M78.36,184a5,5,0,0,0-5,5.24l13.2,277A47.94,47.94,0,0,0,134.51,512h243a47.94,47.94,0,0,0,47.94-45.72l13.2-277a5,5,0,0,0-5-5.24ZM320,224a16,16,0,0,1,32,0V432a16,16,0,0,1-32,0Zm-80,0a16,16,0,0,1,32,0V432a16,16,0,0,1-32,0Zm-80,0a16,16,0,0,1,32,0V432a16,16,0,0,1-32,0Z\" transform=\"translate(-48)\"/></svg>', '1'),
(29, '<svg id=\"Capa_29\" data-name=\"Capa 29\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 328.91 475.08\"><path d=\"M379.58,247.4q-22.43-28.12-50.68-28.12V73.09a35.07,35.07,0,0,0,25.69-10.85,35.1,35.1,0,0,0,10.86-25.69,35.1,35.1,0,0,0-10.86-25.7A35.09,35.09,0,0,0,328.9,0H146.18a35.13,35.13,0,0,0-25.7,10.85,35.13,35.13,0,0,0-10.85,25.7,35.11,35.11,0,0,0,10.85,25.69,35.11,35.11,0,0,0,25.7,10.85V219.27q-28.28,0-50.68,28.12T73.09,310.64A18.51,18.51,0,0,0,91.36,328.9H206.71l21.7,138.48q1.43,7.71,9.13,7.7h.29a8,8,0,0,0,5.85-2.42,10.52,10.52,0,0,0,3-5.85L261.24,328.9H383.72A18.55,18.55,0,0,0,402,310.64Q402,275.51,379.58,247.4ZM210.13,210.14a9.14,9.14,0,1,1-18.27,0V82.23a9.14,9.14,0,1,1,18.27,0Z\" transform=\"translate(-73.09)\"/></svg>', '1'),
(30, '<svg id=\"Capa_30\" data-name=\"Capa 30\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M476.85,35.15a120,120,0,0,0-169.7,0L206.85,135.44c-48.47,48.48-44,125.72,0,169.71A114.53,114.53,0,0,0,231,323.45l18.31-18.3c12-12,7.78-26,7.56-36.18a58.3,58.3,0,0,1-7.56-6.25c-22.57-22.57-23.59-61.27,0-84.85L349.57,77.57a60,60,0,0,1,84.85,84.86l-66.29,66.29c1.92,10.61,13.42,35.73,7.51,77.18.28-.28.63-.47.92-.75l100.29-100.3A120,120,0,0,0,476.85,35.15Z\" transform=\"translate(0 0)\"/><path d=\"M312.92,199.08a114.19,114.19,0,0,0-24.12-18.3l-18.31,18.3c-12,12-7.78,26-7.56,36.17a57.72,57.72,0,0,1,7.56,6.26c22.58,22.57,23.59,61.26,0,84.85L162.43,434.43a60,60,0,0,1-84.86-84.86l74.07-74.06c-1.92-10.61-13.42-35.74-7.5-77.19-.29.29-.64.47-.93.76L35.15,307.15a120,120,0,0,0,169.7,169.7L312.92,368.79C360.49,321.21,357.89,244.06,312.92,199.08Z\" transform=\"translate(0 0)\"/></svg>', '1'),
(31, '<svg id=\"Capa_31\" data-name=\"Capa 31\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M436.3,75.7C388,27.4,324.1,0,256,0,115.34,0,0,115.12,0,256S115.07,512,256,512c140.31,0,256-114.59,256-256C512,187.9,484.6,124,436.3,75.7ZM256,451A194.95,194.95,0,0,1,98.5,141.1L370.9,413.2A191.19,191.19,0,0,1,256,451Zm157.2-80.1L141.1,98.5A194.95,194.95,0,0,1,451,256,191.19,191.19,0,0,1,413.2,370.9Z\"/></svg>', '1'),
(32, '<svg id=\"Layer_32\" data-name=\"Layer 32\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 286.05 286.05\"><path class=\"cls-1\" d=\"M143,0a143,143,0,1,0,143,143A143,143,0,0,0,143,0Zm0,259.24A116.21,116.21,0,1,1,259.24,143,116.2,116.2,0,0,1,143,259.24Zm0-196.51c-10.25,0-18,5.34-18,14v79.2c0,8.64,7.75,14,18,14,10,0,18-5.55,18-14V76.71C161,68.28,153,62.73,143,62.73Zm0,125a17.86,17.86,0,1,0,17.84,17.86A17.88,17.88,0,0,0,143,187.72Z\"/></svg>', '1'),
(33, '<svg id=\"Layer_33\" data-name=\"Layer 33\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 256.02 512\"><defs><style>.cls-33{fill:#3b5999;}</style></defs><path class=\"cls-33\" d=\"M341.27,85H388V3.61C380,2.5,352.21,0,319.91,0,172,0,212.27,167.47,206.38,192H132v91h74.37V512h91.18V283H368.9l11.32-91H297.51c4-60.24-16.23-107,43.76-107Z\" transform=\"translate(-131.99 0)\"/></svg>', '1'),
(34, '<svg id=\"Capa_34\" data-name=\"Capa 34\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 490.66 403.26\"><defs><style>.cls-34{fill:#03a9f4;}</style></defs><path class=\"cls-34\" d=\"M487.84,92.93A10.66,10.66,0,0,0,475,90.67a91.62,91.62,0,0,1-13.63,4.93,95.94,95.94,0,0,0,16.21-31.21A10.66,10.66,0,0,0,461.2,52.91,224.34,224.34,0,0,1,409.87,73,107.35,107.35,0,0,0,275.36,62.6a105.84,105.84,0,0,0-46.08,97.81A252.59,252.59,0,0,1,52.38,65.13a11.07,11.07,0,0,0-9.06-4,10.72,10.72,0,0,0-8.54,5.21A103.24,103.24,0,0,0,23,146.5a114.07,114.07,0,0,0,18.54,38.76,56.7,56.7,0,0,1-10.84-6.93,10.67,10.67,0,0,0-17.39,8.28,110.33,110.33,0,0,0,56.84,94.72,78.57,78.57,0,0,1-13.74-2.95A10.66,10.66,0,0,0,43.62,293a116.4,116.4,0,0,0,80.81,66.14A188,188,0,0,1,11.89,382.28,10.51,10.51,0,0,0,.52,389.57a10.69,10.69,0,0,0,4.91,12.59A319.69,319.69,0,0,0,163.08,447,270.8,270.8,0,0,0,312.72,401.2c84.54-56.11,137.23-156.84,129.89-247A199.1,199.1,0,0,0,489,105.88,10.67,10.67,0,0,0,487.84,92.93Z\" transform=\"translate(0 -43.7)\"/></svg>', '1'),
(35, '<svg id=\"Layer_35\" data-name=\"Layer 35\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M197.33,0h-160A37.38,37.38,0,0,0,0,37.33v96a37.38,37.38,0,0,0,37.33,37.34h160a37.38,37.38,0,0,0,37.34-37.34v-96A37.38,37.38,0,0,0,197.33,0Z\"/><path d=\"M197.33,213.33h-160A37.38,37.38,0,0,0,0,250.67v224A37.38,37.38,0,0,0,37.33,512h160a37.38,37.38,0,0,0,37.34-37.33v-224A37.38,37.38,0,0,0,197.33,213.33Z\"/><path d=\"M474.67,341.33h-160a37.38,37.38,0,0,0-37.34,37.34v96A37.38,37.38,0,0,0,314.67,512h160A37.38,37.38,0,0,0,512,474.67v-96A37.38,37.38,0,0,0,474.67,341.33Z\"/><path d=\"M474.67,0h-160a37.38,37.38,0,0,0-37.34,37.33v224a37.38,37.38,0,0,0,37.34,37.34h160A37.38,37.38,0,0,0,512,261.33v-224A37.38,37.38,0,0,0,474.67,0Z\"/></svg>', '1'),
(36, '<svg id=\"Capa_36\" data-name=\"Capa 36\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 292.36 164.46\"><path d=\"M286.93,69.38A17.52,17.52,0,0,0,274.09,64H18.27A17.56,17.56,0,0,0,5.42,69.38a17.93,17.93,0,0,0,0,25.69L133.33,223a17.92,17.92,0,0,0,25.7,0L286.93,95.07a17.91,17.91,0,0,0,0-25.69Z\" transform=\"translate(0 -63.95)\"/></svg>', '1'),
(37, '<svg id=\"Capa_37\" data-name=\"Capa 37\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 41.75 46.24\"><path d=\"M38.3,27.18v-9.4a15.18,15.18,0,0,0-9.5-14h.06a6.27,6.27,0,0,0-11.48,0h.05a15.18,15.18,0,0,0-9.49,14v9.4A5.77,5.77,0,0,0,2.24,33v1.25A5.78,5.78,0,0,0,8,40h30.2A5.78,5.78,0,0,0,44,34.22V33A5.78,5.78,0,0,0,38.3,27.18Z\" transform=\"translate(-2.24)\"/><path d=\"M17.57,46.24a6.3,6.3,0,0,0,5.74-3.75H11.83A6.29,6.29,0,0,0,17.57,46.24Z\" transform=\"translate(-2.24)\"/></svg>', '1'),
(38, '<svg id=\"Capa_38\" data-name=\"Capa 38\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C114.62,0,0,106.11,0,237c0,74.6,37.22,141.12,95.39,184.58V512l87.17-47.84A275.35,275.35,0,0,0,256,474.08c141.38,0,256-106.11,256-237S397.38,0,256,0Zm25.44,319.2-65.18-69.54L89.06,319.2,229,170.66l66.79,69.53,125.6-69.53Z\"/></svg>', '1'),
(39, '<svg id=\"Layer_39\" data-name=\"Layer 39\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 426.67 426.67\"><path d=\"M405.33,192H234.67V21.33a21.34,21.34,0,0,0-42.67,0V192H21.33a21.34,21.34,0,0,0,0,42.67H192V405.33a21.34,21.34,0,1,0,42.67,0V234.67H405.33a21.34,21.34,0,1,0,0-42.67Z\"/></svg>', '1'),
(40, '&lt;svg fill=&quot;#000000&quot;  id=&quot;Capa_40&quot; data-name=&quot;Capa 40&quot; width=&quot;800px&quot; height=&quot;800px&quot; viewBox=&quot;-6 0 24 24&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;path d=&quot;m12.496 15.87c.001.027.001.06.001.092 0 1.326-.507 2.534-1.337 3.441l.003-.004c-.881.967-2.081 1.63-3.432 1.824l-.031.004v2.344.016c0 .228-.185.413-.413.413-.006 0-.011 0-.017 0h.001-1.807c-.236-.003-.426-.193-.429-.429v-2.344c-.64-.088-1.216-.233-1.766-.434l.059.019c-.898-.305-1.678-.726-2.37-1.254l.02.015c-.233-.169-.435-.334-.629-.509l.007.006q-.167-.16-.234-.24c-.074-.074-.12-.176-.12-.289 0-.099.036-.19.095-.261l-.001.001 1.379-1.808c.073-.093.183-.154.307-.16h.001c.018-.003.038-.005.06-.005.105 0 .199.049.26.126l.001.001.026.026c.885.81 1.987 1.398 3.208 1.666l.046.009c.298.068.64.107.991.107.018 0 .04.001.062.001.691 0 1.331-.216 1.858-.584l-.011.007c.501-.342.826-.91.826-1.554 0-.028-.001-.056-.002-.083v.004c0-.263-.075-.508-.204-.715l.003.006c-.127-.214-.276-.398-.447-.559l-.001-.001c-.227-.191-.485-.359-.761-.493l-.022-.01q-.536-.274-.88-.429t-1.071-.435q-.522-.214-.824-.335t-.824-.355-.837-.415-.757-.475c-.27-.175-.506-.363-.721-.57l.001.001c-.2-.203-.391-.416-.57-.64l-.013-.016c-.185-.224-.345-.48-.467-.756l-.008-.021c-.107-.25-.204-.547-.275-.854l-.007-.036c-.072-.307-.114-.66-.114-1.022 0-.008 0-.016 0-.024v.001c0-.009 0-.02 0-.031 0-1.25.501-2.384 1.314-3.21l-.001.001c.89-.922 2.063-1.566 3.379-1.789l.036-.005v-2.415c0-.001 0-.003 0-.004 0-.117.049-.223.127-.298.075-.078.181-.127.298-.127h.003 1.808.016c.228 0 .413.185.413.413v.017-.001 2.357c.55.058 1.052.167 1.533.323l-.053-.015c.459.14.845.293 1.216.471l-.052-.022c.328.163.607.33.871.516l-.02-.014q.4.282.522.388t.201.187c.082.071.133.176.133.292 0 .081-.025.156-.067.218l.001-.001-1.082 1.956c-.055.121-.171.205-.307.214h-.001c-.025.005-.055.009-.085.009-.106 0-.203-.039-.277-.103h.001q-.04-.04-.194-.16t-.522-.355c-.219-.142-.475-.284-.741-.411l-.042-.018c-.28-.131-.612-.25-.956-.339l-.042-.009c-.343-.096-.737-.153-1.144-.154h-.001c-.046-.002-.099-.003-.153-.003-.716 0-1.382.216-1.935.587l.013-.008c-.487.327-.804.876-.804 1.499 0 .226.042.443.118.643l-.004-.012c.092.22.226.406.394.555l.002.001c.161.151.333.296.513.431l.016.011c.215.148.462.287.721.403l.03.012q.502.234.81.362t.938.368q.71.268 1.085.422t1.018.469c.394.187.725.376 1.041.587l-.029-.018c.31.22.58.44.837.675l-.006-.006c.277.242.513.522.701.835l.009.016c.166.293.309.634.413.991l.009.034c.109.365.171.785.171 1.219v.042-.002z&quot;/&gt;&lt;/svg&gt;', '1'),
(41, '<svg id=\"Capa_41\" data-name=\"Capa 41\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 358.89\"><path d=\"M337.92,89.85C161.28,33.53,0,164.09,0,333.05c0,23,2.56,66.56,10.24,92.16,0,5.12,5.12,10.24,10.24,10.24H489c5.12,0,10.24-5.12,12.8-10.24,5.12-25.6,10.24-69.12,10.24-92.16C512,223,442.88,123.12,337.92,89.85ZM256,128.24c15.36,0,25.6,10.25,25.6,25.61s-10.24,25.6-25.6,25.6-25.6-10.24-25.6-25.6S240.64,128.24,256,128.24ZM76.8,358.64c-15.36,0-25.6-10.24-25.6-25.6s10.24-25.6,25.6-25.6,25.6,10.24,25.6,25.6S92.16,358.64,76.8,358.64Zm71.68-133.12c-10.24,10.24-25.6,10.24-35.84,0s-10.24-28.16-2.56-38.4c10.24-10.24,25.6-10.24,35.84,0S156.16,215.29,148.48,225.52ZM256,384.24c-28.16,0-53.76-25.6-46.08-51.2S256,230.64,256,230.64s38.4,76.81,46.08,102.4S284.16,384.24,256,384.24ZM401.92,225.52c-10.24,10.24-25.6,10.24-35.84,0s-10.24-25.6,0-35.84,25.6-10.24,35.84,0C409.6,197.37,409.6,215.29,401.92,225.52ZM435.2,358.64c-15.36,0-25.6-10.24-25.6-25.6s10.24-25.6,25.6-25.6,25.6,10.24,25.6,25.6S450.56,358.64,435.2,358.64Z\" transform=\"translate(0 -76.56)\"/></svg>', '1'),
(42, '<svg id=\"Capa_42\" data-name=\"Capa 42\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 447.98\"><path d=\"M224,160V128H32A32.06,32.06,0,0,0,0,160v64H230.75A286.4,286.4,0,0,1,224,160Z\" transform=\"translate(0 -32.01)\"/><path d=\"M510.69,288c-21.83,33.63-55.11,62.24-102.79,89.63a48.08,48.08,0,0,1-47.71.07c-47.68-27.46-81-56.1-102.81-89.7H0V448a32,32,0,0,0,32,32H480a32,32,0,0,0,32-32V288ZM144,384H80a16,16,0,0,1,0-32h64a16,16,0,0,1,0,32Z\" transform=\"translate(0 -32.01)\"/><path d=\"M502.3,81.3l-112-48a16.13,16.13,0,0,0-12.64,0l-112,48A16,16,0,0,0,256,96v64c0,88,32.54,139.49,120,189.89a16.11,16.11,0,0,0,15.94,0C479.46,299.61,512,248.15,512,160V96A16,16,0,0,0,502.3,81.3ZM444.51,154l-64,80A16.18,16.18,0,0,1,368,240h-.67a16.06,16.06,0,0,1-12.64-7.1l-32-48a16,16,0,1,1,26.62-17.73L369.18,197,419.49,134a16,16,0,0,1,25,20Z\" transform=\"translate(0 -32.01)\"/></svg>', '1'),
(43, '<svg id=\"Capa_43\" data-name=\"Capa 43\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 511.12 512\"><path d=\"M234.07,471.13H60.39a20,20,0,0,1-19-26.09c19.73-61.34,79.91-104.19,146.34-104.19a149.32,149.32,0,0,1,85.84,26.92A20,20,0,0,0,296.4,335a189.62,189.62,0,0,0-39.82-21.26,101.61,101.61,0,0,0,33.05-67,150.31,150.31,0,0,1,190.54-15.57A20,20,0,1,0,503,198.4a189.62,189.62,0,0,0-39.82-21.26,101.81,101.81,0,1,0-137.1-.22c-2.78,1.07-5.55,2.21-8.29,3.42a188.79,188.79,0,0,0-35.17,20.18A101.8,101.8,0,1,0,119.3,313.38c-54.15,20.29-98,63.87-115.93,119.44a59.91,59.91,0,0,0,57,78.24H234.07a20,20,0,0,0,0-39.93Zm160.7-431.2a61.89,61.89,0,1,1-61.88,61.88A62,62,0,0,1,394.77,39.93ZM188.15,176.55a61.89,61.89,0,1,1-61.88,61.89A62,62,0,0,1,188.15,176.55Z\" transform=\"translate(-0.44)\"/><path d=\"M503.22,326.08a20,20,0,0,0-27.86,4.61L377,468.14a11.39,11.39,0,0,1-16.41.85l-63.7-61.17a20,20,0,0,0-27.66,28.8L333,497.85A51.48,51.48,0,0,0,368.37,512c1.13,0,2.26,0,3.39-.11a51.46,51.46,0,0,0,36.6-19.06c.23-.29.45-.59.67-.89l98.8-138A20,20,0,0,0,503.22,326.08Z\" transform=\"translate(-0.44)\"/></svg>', '1'),
(44, '<svg id=\"Layer_44\" data-name=\"Layer 44\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 511.98 512\"><path d=\"M483.9,200.41l-40.1-5.1A201.93,201.93,0,0,0,431.66,166l24.77-31.89a31.72,31.72,0,0,0-2.61-42.22l-33.6-33.6a31.8,31.8,0,0,0-42.34-2.71L346,80.34A201.25,201.25,0,0,0,316.69,68.2l-5.1-40A32,32,0,0,0,279.89,0H232.11a32,32,0,0,0-31.7,28.1l-5.1,40.1A199.06,199.06,0,0,0,166,80.34L134.1,55.57a31.74,31.74,0,0,0-42.22,2.61L58.28,91.75a31.82,31.82,0,0,0-2.71,42.37L80.34,166A199.72,199.72,0,0,0,68.2,195.31l-40,5.1A32,32,0,0,0,0,232.11v47.78a32,32,0,0,0,28.1,31.7l40.1,5.1A201.93,201.93,0,0,0,80.34,346L55.57,377.9a31.72,31.72,0,0,0,2.61,42.22l33.6,33.6a31.82,31.82,0,0,0,42.34,2.69L166,431.64a197.56,197.56,0,0,0,29.32,12.14l5.1,40a32,32,0,0,0,31.7,28.2h47.78a32,32,0,0,0,31.7-28.1l5.1-40.1A201.93,201.93,0,0,0,346,431.66l31.89,24.77a31.72,31.72,0,0,0,42.22-2.61l33.6-33.6a31.78,31.78,0,0,0,2.71-42.34L431.66,346a197.56,197.56,0,0,0,12.14-29.32l40-5.1A32,32,0,0,0,512,279.89V232.11a31.92,31.92,0,0,0-28.08-31.7ZM256,362.67A106.67,106.67,0,1,1,362.67,256,106.79,106.79,0,0,1,256,362.67Z\" transform=\"translate(0 0)\"/></svg>', '1'),
(45, '<svg id=\"Layer_45\" data-name=\"Layer 45\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 511.99\"><path d=\"M320,277.34a21.33,21.33,0,0,0-21.34,21.33V384a21.34,21.34,0,0,1-21.33,21.33h-64v-320a43,43,0,0,0-29.05-40.56L178,42.67h99.37A21.35,21.35,0,0,1,298.66,64v64a21.34,21.34,0,0,0,42.67,0V64a64.08,64.08,0,0,0-64-64H48a16.18,16.18,0,0,0-2.28.47c-1-.09-2-.47-3-.47A42.71,42.71,0,0,0,0,42.67v384a43,43,0,0,0,29.05,40.55L157.44,510a44.28,44.28,0,0,0,13.23,2,42.71,42.71,0,0,0,42.66-42.67V448h64a64.08,64.08,0,0,0,64-64V298.67A21.32,21.32,0,0,0,320,277.34Z\" transform=\"translate(0 -0.01)\"/><path d=\"M505.74,198.25l-85.33-85.33A21.32,21.32,0,0,0,384,128v64H298.66a21.34,21.34,0,0,0,0,42.67H384v64a21.32,21.32,0,0,0,36.41,15.08l85.33-85.33A21.32,21.32,0,0,0,505.74,198.25Z\" transform=\"translate(0 -0.01)\"/></svg>', '1'),
(46, '<svg id=\"Layer_46\" data-name=\"Layer 46\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 455.4 480\"><path d=\"M126.34,117.66a8,8,0,0,0,11.32-11.32l-48-48A8,8,0,0,0,78.34,69.66Z\" transform=\"translate(-12)\"/><path d=\"M84,248a8,8,0,0,0,0-16H20a8,8,0,0,0,0,16Z\" transform=\"translate(-12)\"/><path d=\"M126.34,362.34l-48,48a8,8,0,1,0,11.32,11.32l48-48a8,8,0,0,0-11.32-11.32Z\" transform=\"translate(-12)\"/><path d=\"M244,375V105a135.92,135.92,0,0,0,0,270Z\" transform=\"translate(-12)\"/><path d=\"M444,306.59a109.67,109.67,0,0,1-37.41,6,111.28,111.28,0,0,1-110-112.28,116.8,116.8,0,0,1,51.56-95.63c-6.54-.52-13.13-.64-19.29-.64A129.87,129.87,0,0,0,260,123.78V356.22a130,130,0,0,0,184-49.6Z\" transform=\"translate(-12)\"/><path d=\"M467.4,178.22l-17.78-2.72a8,8,0,0,1-6-4.5L436,154.83,428.41,171a8,8,0,0,1-6,4.5l-17.78,2.72,13.13,13.46a8,8,0,0,1,2.16,6.87l-3,18.48,15.26-8.43a8,8,0,0,1,7.72,0L455.12,217l-3-18.48a8,8,0,0,1,2.16-6.87Z\" transform=\"translate(-12)\"/><path d=\"M252,480a8,8,0,0,0,8-8V392a152.85,152.85,0,0,1-16-.86V472A8,8,0,0,0,252,480Z\" transform=\"translate(-12)\"/><path d=\"M260,8a8,8,0,0,0-16,0V88.86A152.85,152.85,0,0,1,260,88Z\" transform=\"translate(-12)\"/></svg>', '1'),
(47, '<svg id=\"Layer_47\" data-name=\"Layer 47\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 376\"><path d=\"M504.5,75.5a25.59,25.59,0,0,0-36.2,0L161.59,382.2,43.7,264.31a25.6,25.6,0,1,0-36.2,36.2l136,136a25.62,25.62,0,0,0,36.21,0L504.5,111.7A25.59,25.59,0,0,0,504.5,75.5Z\" transform=\"translate(0 -68)\"/></svg>', '1'),
(48, '<svg id=\"Capa_48\" data-name=\"Capa 48\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 403 399.03\"><path d=\"M393,2H258.33a10,10,0,0,0-10,10V31a10,10,0,0,0,10,10h78.05L225,152.43a10,10,0,0,0,0,14.14L238.41,180a10,10,0,0,0,14.14,0L364,68.6v78.05a10,10,0,0,0,10,10h19a10,10,0,0,0,10-10V12A10,10,0,0,0,393,2Z\" transform=\"translate(0 -1.98)\"/><path d=\"M66.62,41h78a10,10,0,0,0,10-10V12a10,10,0,0,0-10-10H10A10,10,0,0,0,0,12V146.65a10,10,0,0,0,10,10H29a10,10,0,0,0,10-10V68.6L150.45,180a10,10,0,0,0,14.14,0L178,166.57a10,10,0,0,0,0-14.14Z\" transform=\"translate(0 -1.98)\"/><path d=\"M393,246.34H374a10,10,0,0,0-10,10v78.05L252.55,223a10,10,0,0,0-14.14,0L225,236.42a10,10,0,0,0,0,14.15L336.38,362H258.33a10,10,0,0,0-10,10v19a10,10,0,0,0,10,10H393a10,10,0,0,0,10-10V256.34A10,10,0,0,0,393,246.34Z\" transform=\"translate(0 -1.98)\"/><path d=\"M164.59,223a10,10,0,0,0-14.14,0L39,334.39v-78a10,10,0,0,0-10-10H10a10,10,0,0,0-10,10V391a10,10,0,0,0,10,10H144.67a10,10,0,0,0,10-10V372a10,10,0,0,0-10-10h-78L178,250.57a10,10,0,0,0,0-14.15Z\" transform=\"translate(0 -1.98)\"/></svg>', '1'),
(49, '<svg id=\"Capa_49\" data-name=\"Capa 49\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 480.01\"><path d=\"M464,80H396.48L384,28.16A16,16,0,0,0,368,16H144a16,16,0,0,0-16,12.16L115.52,80H48A48,48,0,0,0,0,128V416a48,48,0,0,0,48,48h64V432H48a16,16,0,0,1-16-16V128a16,16,0,0,1,16-16h80a16,16,0,0,0,16-12.16L156.48,48h199L368,99.84A16,16,0,0,0,384,112h80a16,16,0,0,1,16,16v32h32V128A48,48,0,0,0,464,80Z\" transform=\"translate(0 -16)\"/><rect x=\"192\" y=\"64.01\" width=\"128\" height=\"32\"/><path d=\"M464,208H208a48,48,0,0,0-48,48V448a48,48,0,0,0,48,48H464a48,48,0,0,0,48-48V256A48,48,0,0,0,464,208Zm16,153.44L427.36,308.8a16,16,0,0,0-22.56,0L352,361.44,299.36,308.8a16,16,0,0,0-22.56,0L192,393.44V256a16,16,0,0,1,16-16H464a16,16,0,0,1,16,16Z\" transform=\"translate(0 -16)\"/></svg>', '1'),
(50, '<svg id=\"Capa_50\" data-name=\"Capa 50\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M333.61,12.1a6.11,6.11,0,0,0-.87-.32A254.84,254.84,0,0,0,256,0C140.93,0,43.39,76.33,11.26,181c-.1.37-.19.71-.27,1.07A254.64,254.64,0,0,0,0,256c0,66.13,25.43,126.29,66.73,171.78A10.53,10.53,0,0,0,69.61,431C116.35,480.7,182.51,512,256,512c129.75,0,237-97.09,253.52-222.38a9.65,9.65,0,0,0,.39-2.5A253.45,253.45,0,0,0,512,256C512,141.91,436.93,45.06,333.61,12.1ZM256,490.67c-66.41,0-126.36-27.86-169.09-72.37,1.65-16.32,5.74-66-2.07-90.83-3.86-12.27-12.2-20.63-19.56-28-5.27-5.27-10.26-10.24-13-16-6.21-13.21-15.21-65-20-98.18C62.36,90.35,151.25,21.33,256,21.33a233.27,233.27,0,0,1,51.52,5.89A122.11,122.11,0,0,0,293,50.33c-12.42-4.57-29.84-9.75-39.55-7.34-16.09,4-32.83,24.7-48.75,46.78-2.39,3.31-5.74,10-8.28,15.51-12-.92-24.1,4.5-33.92,15-9.62,10.3-16.93,26.24-11.58,38.57,3.29,7.49,10.48,11.8,19.78,11.8,9.77,0,18.94-5.36,28.67-11,8.55-5,17.41-10.18,24.81-10.29,38.55-.83,58.22,8.88,64,17.84a9.4,9.4,0,0,1,1.6,7.27c-7.74,1.45-29.7-.79-44.65-2.3-25.22-2.52-51.29-5.14-66.5-1.13-18.88,5-29.86,18-48.89,47.14-8.54,13.1-6.17,40.43-.86,52.72,3.65,8.47,16.64,23.68,27.89,27.26,9.17,2.88,21,1.26,33.6-.47,9.47-1.34,23.78-3.28,27.92-.68,9.79,6.27,16.39,32.66,16.39,65.6-.13,1.77-2.67,6.93-4.16,10-3.91,7.93-7.92,16.17-6.28,24.19,6.6,32.45,31.13,51.28,51.29,51.28.94,0,1.86,0,2.75-.12,29.48-2.63,78-40.6,84.2-72.6,1.52-7.87.82-19.71,0-32.24-.71-11.62-1.5-24.81-.09-31.59s12.57-27.09,20-39.3a10.64,10.64,0,0,0-2-13.48c-6.68-6-17.24-16.06-19.82-19.65-2.43-3.39-6.48-11.18-10.52-19.58a44,44,0,0,1,6.94,2.9c12,7.42,40.76,39.49,50.86,51.2a10.59,10.59,0,0,0,10.15,3.52c15.87-3.11,17.92-19.48,19.24-30.31.49-3.91,1.39-11.14,2.54-12.46,8.71-2,20,15.63,29,29.93,6.23,9.9,12.25,19.2,19,26C471.17,403.46,373.7,490.67,256,490.67Z\"/></svg>', '1'),
(51, '<svg id=\"Layer_51\" data-name=\"Layer 51\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 511\"><path d=\"M82.88,311.12a20,20,0,0,0,27.74-5.5,173.95,173.95,0,0,1,288.78-.35,20,20,0,1,0,33.19-22.33A213.46,213.46,0,0,0,327.37,201a113,113,0,1,0-143.29-.45,213.43,213.43,0,0,0-106.7,82.79A20,20,0,0,0,82.88,311.12ZM256,40.5a73,73,0,1,1-73,73A73.09,73.09,0,0,1,256,40.5Zm79.76,314.13-29.82,21.51a12.53,12.53,0,0,0-4.6,14.05l12,34.8a12.69,12.69,0,0,1,.64,4.51c-.43,9.35-12.14,15.74-20.73,9.54l-29.82-21.51a12.73,12.73,0,0,0-14.88,0L218.75,439c-8.6,6.2-20.37-.19-20.74-9.54a12.71,12.71,0,0,1,.65-4.51l12-34.8a12.53,12.53,0,0,0-4.6-14.05l-29.82-21.51c-9.92-7.16-4.82-24.13,7.44-24.13h36.86c5.48,0,10.34-2.11,12-7.29l11.39-35a12.69,12.69,0,0,1,24.08,0l11.39,35c1.69,5.18,6.55,7.29,12,7.29h36.86C340.58,330.5,345.68,347.47,335.76,354.63Zm171,70-29.82,21.51a12.53,12.53,0,0,0-4.6,14.05l12,34.8a12.69,12.69,0,0,1,.64,4.51c-.43,9.35-12.14,15.74-20.73,9.54l-29.82-21.51a12.73,12.73,0,0,0-14.88,0L389.75,509c-8.6,6.2-20.37-.19-20.74-9.54a12.71,12.71,0,0,1,.65-4.51l12-34.8a12.53,12.53,0,0,0-4.6-14.05l-29.82-21.51c-9.92-7.16-4.82-24.13,7.44-24.13h36.86c5.48,0,10.34-2.11,12-7.29l11.39-35a12.69,12.69,0,0,1,24.08,0l11.39,35c1.69,5.18,6.55,7.29,12,7.29h36.86C511.58,400.5,516.68,417.47,506.76,424.63Zm-342,0-29.82,21.51a12.53,12.53,0,0,0-4.6,14.05l12,34.8a12.69,12.69,0,0,1,.64,4.51c-.43,9.35-12.14,15.74-20.73,9.54L92.44,487.53a12.73,12.73,0,0,0-14.88,0L47.74,509c-8.59,6.2-20.36-.19-20.73-9.54a12.71,12.71,0,0,1,.65-4.51l12-34.8a12.53,12.53,0,0,0-4.6-14.05L5.24,424.63c-9.92-7.16-4.82-24.13,7.44-24.13H49.54c5.48,0,10.34-2.11,12-7.29l11.39-35a12.69,12.69,0,0,1,24.08,0l11.39,35c1.69,5.18,6.55,7.29,12,7.29h36.86C169.58,400.5,174.68,417.47,164.76,424.63Z\" transform=\"translate(0 -0.5)\"/></svg>', '1'),
(52, '<svg id=\"Capa_52\" data-name=\"Capa 52\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 467.97 274.94\"><path d=\"M264.7,96.51H51.2A51.35,51.35,0,0,0,0,147.71V320.26a51.35,51.35,0,0,0,51.2,51.2H264.7a51.35,51.35,0,0,0,51.2-51.2V147.71A51,51,0,0,0,264.7,96.51Z\" transform=\"translate(0 -96.51)\"/><path d=\"M430.08,124.67a26,26,0,0,0-8.7,3.59L341.5,174.34V293.12l80.39,46.08c14.85,8.7,33.28,3.58,42-11.26a31.29,31.29,0,0,0,4.1-15.36V154.37A30.81,30.81,0,0,0,430.08,124.67Z\" transform=\"translate(0 -96.51)\"/></svg>', '1'),
(53, '<svg id=\"Layer_53\" data-name=\"Layer 53\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 511 468.43\"><path d=\"M133.06,394.56a79.73,79.73,0,0,1-75.41-54.32l-.75-2.45a77.89,77.89,0,0,1-3.68-23.08V169.54L1.56,342a48.36,48.36,0,0,0,33.9,58.66L364.7,488.8A49.05,49.05,0,0,0,377,490.37c21.2,0,40.58-14.07,46-34.81l19.18-61Z\" transform=\"translate(0 -21.94)\"/><path d=\"M191.62,171A42.59,42.59,0,1,0,149,128.41,42.63,42.63,0,0,0,191.62,171Z\" transform=\"translate(0 -21.94)\"/><path d=\"M457.77,21.94H138.38A53.3,53.3,0,0,0,85.16,75.18V309.39a53.3,53.3,0,0,0,53.22,53.23H457.77A53.3,53.3,0,0,0,511,309.39V75.18A53.31,53.31,0,0,0,457.77,21.94ZM138.38,64.53H457.77a10.65,10.65,0,0,1,10.65,10.65V226.33l-67.27-78.49C394,139.48,383.69,135,372.6,134.75a37.21,37.21,0,0,0-28.44,13.44l-79.09,94.92-25.76-25.7a37.35,37.35,0,0,0-52.8,0l-58.77,58.74v-201A10.65,10.65,0,0,1,138.38,64.53Z\" transform=\"translate(0 -21.94)\"/></svg>', '1');
INSERT INTO `i_svg_icons` (`icon_id`, `icon_code`, `icon_status`) VALUES
(54, '<svg id=\"Capa_54\" data-name=\"Capa 54\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 450\"><circle cx=\"256\" cy=\"270\" r=\"60\"/><path d=\"M256,211a60.06,60.06,0,0,0,60-60c0-27.39-39.08-99-46.92-112.9-5.3-9.47-20.86-9.47-26.16,0C235.08,52,196,123.61,196,151A60.06,60.06,0,0,0,256,211Z\" transform=\"translate(0 -31)\"/><path d=\"M363.52,280.26a15,15,0,1,0-5.39,29.5C442.55,325.17,482,354.91,482,376c0,35.4-97.23,75-226,75S30,411.4,30,376c0-21.09,39.45-50.83,123.86-66.24a15,15,0,0,0-5.39-29.5C56.12,297.1,0,332.9,0,376c0,68.2,132.38,105,256,105s256-36.8,256-105c0-43.1-56.14-78.9-148.48-95.74Z\" transform=\"translate(0 -31)\"/><path d=\"M179.2,349.94a15.12,15.12,0,0,0-17.13-12.52C112.93,345,91,356.93,91,376c0,41.75,126.3,45,165,45s165-3.25,165-45c0-19.07-21.93-31-71.07-38.58a15,15,0,1,0-4.6,29.64c19.48,3,31.2,6.35,38.07,9C365,382.75,321.3,391,256,391s-109-8.25-127.4-15c6.87-2.62,18.59-5.95,38.07-9a15,15,0,0,0,12.53-17.12Z\" transform=\"translate(0 -31)\"/></svg>', '1'),
(55, '<svg id=\"Capa_55\" data-name=\"Capa 55\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 147.65 163.86\"><path d=\"M34.86,3.61C20.08-4.86,8.11,2.08,8.11,19.11V144.74c0,17,12,24,26.75,15.51l109.81-63c14.78-8.48,14.78-22.22,0-30.69Z\" transform=\"translate(-8.11 0)\"/></svg>', '1'),
(56, '<svg id=\"Capa_56\" data-name=\"Capa 56\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 328.9 402\"><path d=\"M357.45,190.72a26.43,26.43,0,0,0-19.42-8H328.9V127.91q0-52.54-37.68-90.22T201,0q-52.55,0-90.22,37.69T73.09,127.91v54.82H64a27.28,27.28,0,0,0-27.41,27.41V374.59A27.28,27.28,0,0,0,64,402H338a27.28,27.28,0,0,0,27.41-27.41V210.13A26.43,26.43,0,0,0,357.45,190.72Zm-83.36-8H127.91V127.91q0-30.27,21.41-51.68T201,54.82q30.27,0,51.68,21.41t21.41,51.68Z\" transform=\"translate(-36.55)\"/></svg>', '1'),
(57, '<svg id=\"Layer_57\" data-name=\"Layer 57\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 69.82 99.53\"><path d=\"M19.76,61.77H16.28a1.82,1.82,0,0,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M41.31,61.6H28.62a4.21,4.21,0,0,0-4.2,3.92v11a4.22,4.22,0,0,0,4.2,3.92H41.31a4.21,4.21,0,0,0,4.08-3.18V64.77A4.2,4.2,0,0,0,41.31,61.6Z\" transform=\"translate(0 -0.35)\"/><path d=\"M19.76,55H16.28a1.82,1.82,0,0,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M19.76,82H16.28a1.82,1.82,0,0,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M19.76,75.22H16.28a1.82,1.82,0,0,0,0,3.64h3.48a1.82,1.82,0,0,0,0-3.64Z\" transform=\"translate(0 -0.35)\"/><path d=\"M19.76,68.5H16.28a1.82,1.82,0,0,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M53.22,81.93H49.74a1.82,1.82,0,1,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M53.22,75.19H49.74a1.82,1.82,0,0,0,0,3.64h3.48a1.82,1.82,0,0,0,0-3.64Z\" transform=\"translate(0 -0.35)\"/><path d=\"M53.22,55H49.74a1.82,1.82,0,1,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M53.22,61.74H49.74a1.82,1.82,0,1,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M53.22,68.47H49.74a1.82,1.82,0,1,0,0,3.63h3.48a1.82,1.82,0,1,0,0-3.63Z\" transform=\"translate(0 -0.35)\"/><path d=\"M64.29,40.72H5.53A5.53,5.53,0,0,0,0,46.25v48.1a5.54,5.54,0,0,0,5.53,5.53H64.29a5.54,5.54,0,0,0,5.53-5.53V46.25A5.53,5.53,0,0,0,64.29,40.72ZM57.88,85.77A6.24,6.24,0,0,1,45.41,86a4.21,4.21,0,0,0-4.1-3.24H28.62a4.29,4.29,0,0,0-4.28,4h0a6.24,6.24,0,0,1-12.4-1v-31a6.24,6.24,0,1,1,12.48,0v.51h0a4.22,4.22,0,0,0,4.2,3.91H41.31a4.22,4.22,0,0,0,4.09-3.19V54.79a6.24,6.24,0,0,1,12.48,0v31Z\" transform=\"translate(0 -0.35)\"/><path d=\"M34.91.35a25.42,25.42,0,0,0-25.4,25.4v2.4h0a4.89,4.89,0,0,0,9.77,0h0v0h0V25.75a15.63,15.63,0,0,1,31.25,0V38.29h9.78V25.75A25.42,25.42,0,0,0,34.91.35Z\" transform=\"translate(0 -0.35)\"/></svg>', '1'),
(58, '<svg id=\"Capa_58\" data-name=\"Capa 58\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 244.64 512\"><defs><style>.cls-58-1{fill:#badb47;}.cls-58-2{fill:#ffdfcf;}.cls-58-3{fill:#1fc979;}.cls-58-4{fill:#79e1f5;}.cls-58-5{fill:#f5afaa;}.cls-58-6{fill:#ffcebf;}.cls-58-7{fill:#fd445f;}</style></defs><path class=\"cls-58-1\" d=\"M241.15,103.71c-7.52,0-14.86-4.69-21.94-3.22a107.48,107.48,0,0,0-85.53,105.19v75.93l107.54,24.8,15-92.75-15-109.95Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-2\" d=\"M186.3,281.61H133.68v14.84l43.69,34.76Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-3\" d=\"M177.38,184.52h29.53v97.09H177.38Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-4\" d=\"M177.38,281.61h63.84V512H177.38Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-5\" d=\"M263.24,100.52c-7.1-1.49-14.47,3.16-22,3.18v58.38l64,169.13,43.39-34.76V205.68a107.26,107.26,0,0,0-43.39-86.25c-4.22-3.14-8.68-.18-13.35-2.68-5.31-2.85-10.89-11.07-16.69-13a105.79,105.79,0,0,0-12-3.23Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-2\" d=\"M186.2,55a55.07,55.07,0,0,0,55,55l11-55-11-55A55.08,55.08,0,0,0,186.2,55Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-6\" d=\"M241.22,0V110a55,55,0,1,0,0-110Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-5\" d=\"M256.22,383.84h43V512h-43Z\" transform=\"translate(-133.68)\"/><path class=\"cls-58-7\" d=\"M305.23,281.61V119.44a107.09,107.09,0,0,0-30-15.67v58.32H240.94v239H378.32Z\" transform=\"translate(-133.68)\"/></svg>', '1'),
(59, '<svg id=\"Capa_59\" data-name=\"Capa 59\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 341.33 512\"><defs><style>.cls-59-1{fill:#455a64;}.cls-59-2{fill:#ffc107;}</style></defs><path class=\"cls-59-1\" d=\"M256,0A149.49,149.49,0,0,0,106.67,149.33v96A10.67,10.67,0,0,0,117.33,256H160a10.67,10.67,0,0,0,10.67-10.67v-96a85.33,85.33,0,1,1,170.66,0v96A10.67,10.67,0,0,0,352,256h42.67a10.67,10.67,0,0,0,10.66-10.67v-96A149.49,149.49,0,0,0,256,0Z\" transform=\"translate(-85.33)\"/><path class=\"cls-59-2\" d=\"M394.67,234.67H117.33a32,32,0,0,0-32,32v192A53.34,53.34,0,0,0,138.67,512H373.33a53.34,53.34,0,0,0,53.34-53.33v-192A32,32,0,0,0,394.67,234.67Z\" transform=\"translate(-85.33)\"/><path class=\"cls-59-1\" d=\"M284.82,372.69a42.07,42.07,0,0,0,13.85-31.36,42.67,42.67,0,0,0-85.34,0,42.07,42.07,0,0,0,13.85,31.36l-13.31,39.94a10.66,10.66,0,0,0,6.74,13.49,10.89,10.89,0,0,0,3.39.55h64A10.68,10.68,0,0,0,298.68,416a10.5,10.5,0,0,0-.55-3.38Z\" transform=\"translate(-85.33)\"/></svg>', '1'),
(60, '<svg id=\"Capa_60\" data-name=\"Capa 60\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C114.61,0,0,114.62,0,256S114.61,512,256,512,512,397.39,512,256,397.39,0,256,0Zm0,384a32,32,0,1,1,32-32A32,32,0,0,1,256,384Zm32-112H224L208,144a16,16,0,0,1,16-16h64a16,16,0,0,1,16,16Z\"/></svg>', '1'),
(61, '<svg id=\"Capa_61\" data-name=\"Capa 61\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 491.28\"><path d=\"M479.06,206.51c-6-4.85-16-12.93-17.2-16.76s2-16.78,4-24.39c4.72-18.39,10.6-41.26-2.58-59.27s-36.71-19.44-55.66-20.71c-7.85-.55-21-1.42-24.33-3.83s-8.43-14.9-11.45-22.36c-7.47-18.52-16-39.51-36.73-46.19S295.56,18.2,279,28.72c-7.32,4.61-18.35,11.59-23,11.59-4.42,0-15.32-7.09-22.55-11.78-16-10.39-35.95-23.26-57-16.1-20.72,7-29.14,27.9-36.6,46.27-3,7.4-8,19.76-11.43,22.28s-16.74,3.36-24.73,3.89c-20,1.35-42.66,2.88-55.39,20.9-12.51,17.71-6.9,39.55-1.95,58.82,1.95,7.66,5.25,20.48,4.06,24.31-1.27,3.66-11.13,11.77-17,16.61C18.51,217.79,0,233,0,255.89c0,22.7,18.15,37.79,32.73,49.89C39.06,311,48.6,319,49.89,322.9s-2,16.54-4,24.1C41.05,365.55,35,388.66,48.7,406.81c13.36,17.65,36.84,19.21,55.73,20.48,7.85.55,21,1.4,24.34,3.83s8.38,14.88,11.4,22.34c7.49,18.52,16,39.51,36.75,46.21a39,39,0,0,0,12.32,2c15.91,0,30.59-9.31,43.83-17.64,7.3-4.62,18.31-11.6,22.92-11.6s15.34,6.9,22.49,11.47c15.87,10.09,35.67,22.74,56.68,15.55,20.69-7.05,29.12-27.89,36.58-46.29,3-7.39,8-19.76,11.43-22.28s16.67-3.37,24.63-3.9c19.84-1.35,42.35-2.85,55.28-20.6s7.43-39.91,2.5-59.35c-1.92-7.6-5.14-20.27-4-24.08s11.13-11.76,17-16.61C493.49,294,512,278.76,512,255.91,512,233.25,493.72,218.42,479.06,206.51ZM451.7,273.69c-12.38,10.17-25.18,20.73-30.21,35.86-5.14,15.4-1,31.91,3.09,47.86,2,7.78,5.26,20.8,4.82,23.48-3.22,2.41-16.48,3.32-24.42,3.83-15.72,1.06-33.53,2.25-46.8,12S338.67,422,332.52,437.24c-3,7.49-8.08,20-10.18,21.94-3.85,0-14.62-6.92-21.07-11-13.31-8.46-28.4-18.08-45.26-18.08s-32.1,9.64-45.55,18.15c-6.52,4.1-17.44,11-19.76,11.55-3.28-2.35-8.28-14.75-11.28-22.15-6.22-15.38-12.65-31.27-25.92-40.82s-30-10.62-46.25-11.7c-7.64-.5-21.88-1.46-24.46-3.21-1.31-3.75,2-16.55,4-24.16,3.92-15.09,8.38-32.18,3.34-47.8s-18.49-26.79-30.36-36.64c-5.79-4.83-16.61-13.82-17.52-17.08,1-4,12.06-13.14,18-18C72.68,228,85.48,217.43,90.51,202.3c5.19-15.51.91-32.14-3.19-48.22-2-7.76-5.33-20.75-5.08-23.23,3.24-2.33,16.4-3.22,24.27-3.74,16.52-1.1,33.64-2.27,46.91-12s19.5-25.33,25.66-40.52c3-7.51,8.1-20,10-21.92,3.91.07,14.79,7.13,21.29,11.35,13.41,8.71,28.64,18.59,45.59,18.59S288.13,73,301.54,64.5c6.52-4.12,17.44-11,19.76-11.55,3.26,2.35,8.28,14.75,11.28,22.15,6.22,15.36,12.65,31.25,25.92,40.79s30,10.65,46.29,11.72c8,.55,21.52,1.46,24,3,1.29,3.72-2,16.58-4,24.24-3.87,15.15-8.27,32.31-3.22,48s18.75,26.83,30.79,36.58c5.5,4.49,15.64,12.67,17.2,16.61C468.12,260.12,457.48,268.9,451.7,273.69Z\" transform=\"translate(0 -10.36)\"/><path d=\"M350.61,176.19a21.12,21.12,0,0,0-29.79,2.65L226.47,291.63l-40.13-40.12a21.16,21.16,0,0,0-29.92,29.92l56.49,56.49a21,21,0,0,0,15,6.2,6.85,6.85,0,0,0,.91,0,21.07,21.07,0,0,0,15.3-7.55L353.25,206A21.14,21.14,0,0,0,350.61,176.19Z\" transform=\"translate(0 -10.36)\"/></svg>', '1'),
(62, '<svg id=\"Capa_62\" data-name=\"Capa 62\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512.02\"><defs><style>.cls-1-62{fill:#cfd8dc;}.cls-2-62{fill:#d32f2f;}.cls-3-62{fill:#f44336;}</style></defs><path class=\"cls-1-62\" d=\"M178.23,376.47,135.56,333.8a10.45,10.45,0,0,0-8.36-3.12,10.69,10.69,0,0,0-7.79,4.27L2.14,495a10.67,10.67,0,0,0,14.94,14.94l160-117.34a10.67,10.67,0,0,0,2.3-14.91,10.45,10.45,0,0,0-1.07-1.24Z\" transform=\"translate(-0.01 0)\"/><path class=\"cls-2-62\" d=\"M316.89,301.8,210.23,195.13a95.1,95.1,0,0,0-63-19.94A110,110,0,0,0,67.13,205.8c-17.33,17.32-15.54,44.1,5.33,79.6a467.15,467.15,0,0,0,69.33,84.82c42.43,42.41,96,86.48,135,86.48a39.94,39.94,0,0,0,29.38-11.82C350.62,400.49,339.55,324.48,316.89,301.8Z\" transform=\"translate(-0.01 0)\"/><path class=\"cls-3-62\" d=\"M402.23,216.47,295.56,109.8a10.67,10.67,0,0,0-15.08,0l-85.33,85.33c-14,14-12.8,44.8,32,89.75,29.24,29.25,52.56,39.89,69.12,39.89a28.23,28.23,0,0,0,20.62-7.89l85.34-85.33A10.67,10.67,0,0,0,402.23,216.47Z\" transform=\"translate(-0.01 0)\"/><path class=\"cls-2-62\" d=\"M511.2,187.94a10.68,10.68,0,0,0-9.86-6.59c-23.12,0-73.9-25-109.78-60.88S330.68,33.83,330.68,10.69a10.67,10.67,0,0,0-18.22-7.56C268.89,46.72,255.28,121,323.12,188.88A142.64,142.64,0,0,0,423.82,236a120.69,120.69,0,0,0,85.05-36.46A10.66,10.66,0,0,0,511.2,187.94Z\" transform=\"translate(-0.01 0)\"/><path class=\"cls-3-62\" d=\"M444.89,67.13C399.71,22,358.86,0,320,0a10.67,10.67,0,0,0-10.67,10.67c0,31.85,30.13,87.87,67.12,124.88s93.05,67.12,124.88,67.12A10.67,10.67,0,0,0,512,192C512,153.15,490.06,112.3,444.89,67.13Z\" transform=\"translate(-0.01 0)\"/></svg>', '1'),
(63, '<svg id=\"Capa_63\" enable-background=\"new 0 0 512 512\" height=\"512\" viewBox=\"0 0 512 512\" width=\"512\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\"><linearGradient id=\"SVGID_1_63\" gradientUnits=\"userSpaceOnUse\" x1=\"256\" x2=\"256\" y1=\"512\" y2=\"0\"><stop offset=\"0\" stop-color=\"#fd5900\"/><stop offset=\"1\" stop-color=\"#ffde00\"/></linearGradient><g><g><path d=\"m256 0c-140.959 0-256 115.049-256 256 0 140.959 115.05 256 256 256 140.96 0 256-115.049 256-256 0-140.96-115.049-256-256-256zm0 482c-124.617 0-226-101.383-226-226s101.383-226 226-226 226 101.383 226 226-101.383 226-226 226zm144.815-287.197-90.771-13.19-40.594-82.252c-2.526-5.12-7.741-8.361-13.45-8.361s-10.924 3.241-13.451 8.362l-40.594 82.252-90.771 13.19c-5.65.821-10.345 4.779-12.109 10.209s-.292 11.391 3.796 15.376l65.683 64.024-15.506 90.404c-.965 5.627 1.348 11.315 5.967 14.671 4.62 3.356 10.743 3.799 15.797 1.142l81.188-42.683 81.188 42.683c5.095 2.678 11.215 2.188 15.797-1.142 4.619-3.356 6.933-9.043 5.968-14.671l-15.506-90.404 65.682-64.024c4.089-3.985 5.561-9.946 3.796-15.376s-6.459-9.388-12.11-10.21zm-83.956 73.632c-3.536 3.446-5.149 8.411-4.314 13.277l11.701 68.22-61.266-32.209c-2.185-1.149-4.583-1.723-6.98-1.723s-4.795.574-6.98 1.723l-61.266 32.209 11.701-68.22c.834-4.866-.779-9.831-4.314-13.277l-49.565-48.314 68.498-9.953c4.885-.71 9.109-3.778 11.294-8.206l30.632-62.069 30.633 62.069c2.185 4.427 6.408 7.496 11.294 8.206l68.497 9.953z\" fill=\"url(#SVGID_1_63)\"/></g></g></svg>', '1'),
(64, '<svg id=\"64\" enable-background=\"new 0 0 24 24\" height=\"512\" viewBox=\"0 0 24 24\" width=\"512\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"m16.68 3.03-8-3c-.12-.04-.24-.04-.36 0l-8 3c-.19.07-.32.26-.32.47v6.43c0 9.12 8.31 11.04 8.39 11.06.04.01.07.01.11.01s.07 0 .11-.01c.02 0 .41-.09 1.01-.32-.4-.98-.62-2.05-.62-3.17 0-4.52 3.54-8.23 8-8.49v-5.51c0-.21-.13-.4-.32-.47z\"/><path d=\"m17.5 11c-3.584 0-6.5 2.916-6.5 6.5s2.916 6.5 6.5 6.5 6.5-2.916 6.5-6.5-2.916-6.5-6.5-6.5zm0 2c.879 0 1.693.263 2.386.7l-6.186 6.186c-.437-.694-.7-1.508-.7-2.386 0-2.481 2.019-4.5 4.5-4.5zm0 9c-.879 0-1.693-.263-2.386-.7l6.187-6.186c.437.693.7 1.507.7 2.386-.001 2.481-2.02 4.5-4.501 4.5z\"/></svg>', '1'),
(65, '<svg id=\"Capa_65\" data-name=\"Capa 65\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 436.38 389.93\"><path d=\"M340.38,23.22h-134a32,32,0,0,0-22.63,9.38L9.37,207a32,32,0,0,0,0,45.26l134,134a32,32,0,0,0,45.24,0L363,211.85a32.1,32.1,0,0,0,9.38-22.66v-134A32,32,0,0,0,340.38,23.22Zm-56,112a24,24,0,1,1,24-24A24,24,0,0,1,284.38,135.22Z\" transform=\"translate(0 -23.22)\"/><path d=\"M404.38,55.22l0,149a27.77,27.77,0,0,1-8.16,19.68L219,401.06l2.72,2.72a32,32,0,0,0,45.25,0l160-160a32,32,0,0,0,9.39-22.62V87.22A32,32,0,0,0,404.38,55.22Z\" transform=\"translate(0 -23.22)\"/></svg>', '1'),
(66, '<svg id=\"Capa_66\" data-name=\"Capa 66\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 59.89 40.12\"><path d=\"M49.35,32.15c-1.61-2.83-3.85-4.32-6-4.6a68.49,68.49,0,0,1-3.21,14,50.74,50.74,0,0,0,4.78,7s13-4.74,14.66-10.8S55.49,28.57,49.35,32.15Z\" transform=\"translate(0 -9.88)\"/><path d=\"M39.16,15.9C45.11,26.2,34,50,34,50S7.82,47.73,1.88,37.43s3-18.88,16-15.33C21.31,9.07,33.21,5.61,39.16,15.9Z\" transform=\"translate(0 -9.88)\"/></svg>', '1'),
(67, '<svg id=\"Layer_67\" data-name=\"Layer 67\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M75,90H317v92H75Z\"/><path d=\"M75,272h92v90H75Z\"/><path d=\"M407.38,512A74.25,74.25,0,0,1,392,467V15A15,15,0,0,0,377,0H15A15,15,0,0,0,0,15V437a75.09,75.09,0,0,0,75,75ZM332,332H242a15,15,0,0,1,0-30h90a15,15,0,0,1,0,30Zm15,45a15,15,0,0,1-15,15H242a15,15,0,0,1,0-30h90A15,15,0,0,1,347,377ZM332,272H242a15,15,0,0,1,0-30h90a15,15,0,0,1,0,30ZM45,75A15,15,0,0,1,60,60H332a15,15,0,0,1,15,15V197a15,15,0,0,1-15,15H60a15,15,0,0,1-15-15Zm0,182a15,15,0,0,1,15-15H182a15,15,0,0,1,15,15V377a15,15,0,0,1-15,15H60a15,15,0,0,1-15-15Zm0,180a15,15,0,0,1,15-15H332a15,15,0,0,1,0,30H60A15,15,0,0,1,45,437Z\"/><path d=\"M497,302H422V467a45,45,0,0,0,90,0V317A15,15,0,0,0,497,302Z\"/></svg>', '1'),
(68, '<svg id=\"Layer_68\" data-name=\"Layer 68\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 511 468.43\"><path d=\"M133.06,394.56a79.73,79.73,0,0,1-75.41-54.32l-.75-2.45a77.89,77.89,0,0,1-3.68-23.08V169.54L1.56,342a48.36,48.36,0,0,0,33.9,58.66L364.7,488.8A49.05,49.05,0,0,0,377,490.37c21.2,0,40.58-14.07,46-34.81l19.18-61Z\" transform=\"translate(0 -21.94)\"/><path d=\"M191.62,171A42.59,42.59,0,1,0,149,128.41,42.63,42.63,0,0,0,191.62,171Z\" transform=\"translate(0 -21.94)\"/><path d=\"M457.77,21.94H138.38A53.3,53.3,0,0,0,85.16,75.18V309.39a53.3,53.3,0,0,0,53.22,53.23H457.77A53.3,53.3,0,0,0,511,309.39V75.18A53.31,53.31,0,0,0,457.77,21.94ZM138.38,64.53H457.77a10.65,10.65,0,0,1,10.65,10.65V226.33l-67.27-78.49C394,139.48,383.69,135,372.6,134.75a37.21,37.21,0,0,0-28.44,13.44l-79.09,94.92-25.76-25.7a37.35,37.35,0,0,0-52.8,0l-58.77,58.74v-201A10.65,10.65,0,0,1,138.38,64.53Z\" transform=\"translate(0 -21.94)\"/></svg>', '1'),
(69, '<svg id=\"Capa_69\" data-name=\"Capa 69\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 352.62 311.49\"><path d=\"M337.22,23c-15.91-8.57-33.66,8-44.06,17.75C269.29,64,249.09,90.88,226.45,115.36c-25.09,26.93-48.35,53.86-74,80.18-14.69,14.68-30.6,30.6-40.39,49-22-21.42-41-44.68-65.49-63.65C28.77,167.38-.6,157.59,0,190c1.22,42.23,38.56,87.52,66.1,116.28C77.73,318.55,93,331.4,110.78,332c21.42,1.23,43.45-24.48,56.31-38.55,22.64-24.48,41-52,61.81-77.11,26.93-33,54.47-65.49,80.78-99.15C326.21,96.39,378.23,45,337.22,23ZM26.94,187.58a4.64,4.64,0,0,0-2.45.61,29.27,29.27,0,0,1-6.73-2.45h0Q20.51,183.91,26.94,187.58Z\" transform=\"translate(0 -20.57)\"/></svg>', '1'),
(70, '<svg id=\"bold_70\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 384\"><path d=\"M453.33,64H58.67A58.71,58.71,0,0,0,0,122.67V389.33A58.71,58.71,0,0,0,58.67,448H453.33A58.71,58.71,0,0,0,512,389.33V122.67A58.71,58.71,0,0,0,453.33,64ZM160,149.33a53.34,53.34,0,1,1-53.33,53.34A53.41,53.41,0,0,1,160,149.33Zm96,197.34a16,16,0,0,1-16,16H80a16,16,0,0,1-16-16V336a58.72,58.72,0,0,1,58.67-58.67h74.66A58.72,58.72,0,0,1,256,336Zm176,16H314.67a16,16,0,0,1,0-32H432a16,16,0,0,1,0,32Zm0-85.34H314.67a16,16,0,0,1,0-32H432a16,16,0,1,1,0,32ZM432,192H314.67a16,16,0,0,1,0-32H432a16,16,0,0,1,0,32Z\" transform=\"translate(0 -64)\"/></svg>', '1'),
(71, '<svg id=\"Capa_71\" data-name=\"Capa 71\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M257,210a45,45,0,1,0,45,45A45.05,45.05,0,0,0,257,210Z\"/><path d=\"M255,0C114.39,0,0,114.39,0,255S114.39,512,255,512,512,395.61,512,255,395.61,0,255,0ZM362,330a59.89,59.89,0,0,1-49-25.6,75,75,0,1,1-11-109V195a15,15,0,0,1,30,0v75a30,30,0,0,0,60,0c0-100.39-66.43-150-135-150-74.44,0-135,60.56-135,135s60.56,135,135,135a133.58,133.58,0,0,0,82.06-27.8C354.88,350.12,373,374,357.34,386A163.37,163.37,0,0,1,257,420c-91,0-165-74-165-165S166,90,257,90c82.9,0,165,61.13,165,180A60.06,60.06,0,0,1,362,330Z\"/></svg>', '1'),
(72, '<svg id=\"Layer_72\" data-name=\"Layer 72\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 352\"><path d=\"M512,163V136a56,56,0,0,0-56-56H56A56,56,0,0,0,0,136v27a5,5,0,0,0,5,5H507A5,5,0,0,0,512,163Z\" transform=\"translate(0 -80)\"/><path d=\"M0,205V376a56,56,0,0,0,56,56H456a56,56,0,0,0,56-56V205a5,5,0,0,0-5-5H5A5,5,0,0,0,0,205ZM128,336a16,16,0,0,1-16,16H96a16,16,0,0,1-16-16V320a16,16,0,0,1,16-16h16a16,16,0,0,1,16,16Z\" transform=\"translate(0 -80)\"/></svg>', '1'),
(73, '<svg id=\"Capa_73\" data-name=\"Capa 73\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 510 512\"><rect x=\"240\" y=\"272\" width=\"30\" height=\"30\"/><rect x=\"360\" y=\"392\" width=\"30\" height=\"30\"/><rect x=\"120\" y=\"272\" width=\"30\" height=\"30\"/><rect x=\"120\" y=\"392\" width=\"30\" height=\"30\"/><rect x=\"240\" y=\"392\" width=\"30\" height=\"30\"/><rect x=\"360\" y=\"272\" width=\"30\" height=\"30\"/><path d=\"M1,212V467a45.05,45.05,0,0,0,45,45H466a45.05,45.05,0,0,0,45-45V212ZM181,437a15,15,0,0,1-15,15H106a15,15,0,0,1-15-15V377a15,15,0,0,1,15-15h60a15,15,0,0,1,15,15Zm0-120a15,15,0,0,1-15,15H106a15,15,0,0,1-15-15V257a15,15,0,0,1,15-15h60a15,15,0,0,1,15,15ZM301,437a15,15,0,0,1-15,15H226a15,15,0,0,1-15-15V377a15,15,0,0,1,15-15h60a15,15,0,0,1,15,15Zm0-120a15,15,0,0,1-15,15H226a15,15,0,0,1-15-15V257a15,15,0,0,1,15-15h60a15,15,0,0,1,15,15ZM421,437a15,15,0,0,1-15,15H346a15,15,0,0,1-15-15V377a15,15,0,0,1,15-15h60a15,15,0,0,1,15,15Zm0-120a15,15,0,0,1-15,15H346a15,15,0,0,1-15-15V257a15,15,0,0,1,15-15h60a15,15,0,0,1,15,15Z\" transform=\"translate(-1)\"/><path d=\"M406,0a30,30,0,0,0-30,30V90a30,30,0,0,0,60,0V30A30,30,0,0,0,406,0Z\" transform=\"translate(-1)\"/><path d=\"M466,60V90a60,60,0,0,1-120,0V60H166V90A60,60,0,0,1,46,90V60A45.05,45.05,0,0,0,1,105v77H511V105A45.05,45.05,0,0,0,466,60Z\" transform=\"translate(-1)\"/><path d=\"M106,0A30,30,0,0,0,76,30V90a30,30,0,0,0,60,0V30A30,30,0,0,0,106,0Z\" transform=\"translate(-1)\"/></svg>', '1'),
(74, '<svg id=\"Capa_74\" data-name=\"Capa 74\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 458.28 314.74\"><path d=\"M273.26,254.84a34.09,34.09,0,0,0,4-.24,9,9,0,0,0,3.12-.72c1.92-1,1.94-3,1.64-4.86a3.73,3.73,0,0,0-4.42-3.08,10.9,10.9,0,0,1-6.71-.89c-4-2.17-5-7.74-3.22-11.67a7.67,7.67,0,0,1,5.77-4.35,13.68,13.68,0,0,1,4,0,3.83,3.83,0,0,0,3.47-1,5.51,5.51,0,0,0,1-5.19c-1.15-2.64-4.84-2.53-7.26-2.53a20.66,20.66,0,0,0-12.26,3.66c-6.73,4.94-8.52,14.89-4.85,22.24C260.49,252.12,266.85,254.84,273.26,254.84Z\" transform=\"translate(0 -71.77)\"/><path d=\"M294.59,251.81c1.17,3.24,5.68,2.37,8.38,2.37,1.73,0,3.36-.58,4-2.35.15-.34.26-.69.39-1L313.26,235q1.38-3.68,2.74-7.35c.81-2.18,1.63-4.8-1.05-6.2-1.28-.67-3-.41-4.42-.41-1.82,0-3.57.45-4.27,2.37s-1.21,4.31-1.8,6.44c-1.16,4.2-2.35,8.38-3.24,12.66-1.22-6.05-3.22-12-4.94-17.9a5,5,0,0,0-1.47-2.77c-1.25-1-2.88-.8-4.38-.8s-3.26-.18-4.5,1.09a3.74,3.74,0,0,0-.71,4Q289.9,239,294.59,251.81Z\" transform=\"translate(0 -71.77)\"/><path d=\"M329.5,251.81c1.19,3.24,5.69,2.37,8.39,2.37,1.73,0,3.35-.58,4-2.35.14-.34.26-.69.39-1L348.18,235l2.74-7.35c.81-2.18,1.63-4.8-1.05-6.2-1.28-.67-3-.41-4.42-.41-1.82,0-3.57.45-4.27,2.37-.76,2.08-1.21,4.31-1.8,6.44-1.16,4.2-2.35,8.38-3.24,12.66-1.23-6.05-3.22-12-4.94-17.9a5,5,0,0,0-1.47-2.77c-1.25-1-2.88-.8-4.38-.8s-3.26-.18-4.5,1.09a3.71,3.71,0,0,0-.71,4Z\" transform=\"translate(0 -71.77)\"/><path d=\"M304.05,339a103.84,103.84,0,0,1-58.37-17.88H49.45a22.12,22.12,0,0,1-22.09-22.09V179.88H215.12a105.13,105.13,0,0,1,42.9-39l-230.66,0V121.22A22.12,22.12,0,0,1,49.45,99.13H364.24a22.12,22.12,0,0,1,22.09,22.09v19.62H350.07A104.45,104.45,0,0,1,399.52,277l14.17,13.17V121.22a49.5,49.5,0,0,0-49.45-49.45H49.45A49.5,49.5,0,0,0,0,121.22V299.05A49.5,49.5,0,0,0,49.45,348.5h314l-19-17.63A104.11,104.11,0,0,1,304.05,339Z\" transform=\"translate(0 -71.77)\"/><path d=\"M68.19,220a7.2,7.2,0,0,0-7.2,7.2v21.44a7.2,7.2,0,0,0,7.2,7.2h133.6a104,104,0,0,1-2.18-21.24,105.12,105.12,0,0,1,1-14.61Z\" transform=\"translate(0 -71.77)\"/><path d=\"M452.56,355.46,379.1,287.18a91.87,91.87,0,1,0-25.87,24.88l74.93,69.65a17.92,17.92,0,0,0,24.4-26.25ZM303.72,174A60.68,60.68,0,1,1,243,234.66,60.75,60.75,0,0,1,303.72,174Z\" transform=\"translate(0 -71.77)\"/></svg>', '1'),
(75, '<svg id=\"Capa_75\" data-name=\"Capa 75\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 480.02 480.01\"><path d=\"M96,304Z\" transform=\"translate(0 -0.01)\"/><polygon points=\"352.02 0 352.02 128 480.02 128 352.02 0\"/><path d=\"M159.57,460.14,135.06,361.8A80,80,0,1,0,25,361.8L.47,460.14a16,16,0,0,0,22.69,18.2L80,449.9l56.83,28.44A16.69,16.69,0,0,0,144,480a16,16,0,0,0,15.55-19.87ZM80,352a48,48,0,1,1,48-48A48.06,48.06,0,0,1,80,352Z\" transform=\"translate(0 -0.01)\"/><path d=\"M352,160a32.06,32.06,0,0,1-32-32V0H128A32.06,32.06,0,0,0,96,32V193.64c54.15,7.84,96,54.08,96,110.37a110.43,110.43,0,0,1-21.89,66.27L189.53,448H448a32,32,0,0,0,32-32V160ZM176,128a16,16,0,0,1,0-32h96a16,16,0,1,1,0,32ZM400,320H240a16,16,0,1,1,0-32H400a16,16,0,0,1,0,32Zm0-96H208a16,16,0,1,1,0-32H400a16,16,0,0,1,0,32Z\" transform=\"translate(0 -0.01)\"/></svg>', '1'),
(76, '<svg id=\"Capa_76\" data-name=\"Capa 76\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 490.67 320.82\"><path d=\"M256,258.93v38.46c12.27-3,21.33-10.9,21.33-20C277.33,269,270.21,263,256,258.93Z\" transform=\"translate(0 -84.93)\"/><path d=\"M213.33,213.42c0,8.49,7.13,14.4,21.34,18.5V193.45C222.4,196.46,213.33,204.35,213.33,213.42Z\" transform=\"translate(0 -84.93)\"/><path d=\"M486.87,98.5a10.21,10.21,0,0,0-8.71-2.33l-9.94,1.73a614.46,614.46,0,0,1-220.9-1.71A634.48,634.48,0,0,0,18.77,94.45L8.83,96.17A10.66,10.66,0,0,0,0,106.67V384a10.68,10.68,0,0,0,3.8,8.17,10.38,10.38,0,0,0,8.7,2.33l9.94-1.73a614.06,614.06,0,0,1,220.91,1.71,636.64,636.64,0,0,0,119.06,11.26,635.59,635.59,0,0,0,109.48-9.51l9.94-1.73a10.66,10.66,0,0,0,8.84-10.5V106.67A10.7,10.7,0,0,0,486.87,98.5ZM138.67,160h-.17a619.55,619.55,0,0,0-62.94,2.18c-.32,0-.62,0-.94,0a10.66,10.66,0,0,1-.89-21.29,620.67,620.67,0,0,1,65.11-2.26,10.67,10.67,0,0,1-.17,21.33ZM256,319.21V320a10.67,10.67,0,1,1-21.33,0v-.74c-23.34-3.65-41-19.16-42.63-38.9a10.66,10.66,0,0,1,9.73-11.51c5.27-.45,11,3.83,11.52,9.72.73,8.66,9.62,16,21.38,18.86v-43.5C206,246.91,192,233.45,192,213.42c0-20.59,18.35-37.82,42.67-41.79v-1a10.67,10.67,0,1,1,21.33,0v.92c23.34,3.65,41,19.15,42.62,38.89A10.66,10.66,0,0,1,288.9,222c-5.42.25-11-3.86-11.52-9.73-.73-8.66-9.62-16-21.38-18.86v43.48c28.69,7.08,42.67,20.54,42.67,40.53C298.67,298,280.32,315.25,256,319.21Zm160.66,30.47c-18.15,1.56-36.44,2.32-54.51,2.32-3.45,0-6.89,0-10.34-.08a10.68,10.68,0,0,1-10.48-10.86c.11-5.87,5.38-10.11,10.86-10.48a609.35,609.35,0,0,0,62.66-2.17,10.67,10.67,0,1,1,1.81,21.27Z\" transform=\"translate(0 -84.93)\"/></svg>', '1'),
(77, '<svg id=\"Layer_77\" data-name=\"Layer 77\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 469.33\"><path d=\"M368.43,106.86,323.14,29.27a16,16,0,0,0-22-5.68L162.65,106.67Z\" transform=\"translate(0 -21.34)\"/><path d=\"M64,128a21.34,21.34,0,0,1,0-42.67h71.89l71.26-42.66H64A64,64,0,0,0,1.07,96,21.36,21.36,0,0,0,0,101.33V426.67a64.19,64.19,0,0,0,64,64H426.67A42.79,42.79,0,0,0,469.33,448V405.33H416a96,96,0,0,1,0-192h53.33V170.67A42.79,42.79,0,0,0,426.67,128ZM405.33,85.33a42.61,42.61,0,0,0-36.9-42.24l36.9,63.58Z\" transform=\"translate(0 -21.34)\"/><path d=\"M496,245.33H416a64,64,0,0,0,0,128h80a16,16,0,0,0,16-16v-96A16,16,0,0,0,496,245.33Zm-80,85.34a21.34,21.34,0,1,1,21.33-21.34A21.34,21.34,0,0,1,416,330.67Z\" transform=\"translate(0 -21.34)\"/></svg>', '1'),
(78, '<svg id=\"Capa_78\" data-name=\"Capa 78\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512.01 453.17\"><defs><style>.cls-1-78{fill:#ff9800;}</style></defs><path class=\"cls-1-78\" d=\"M306.22,45.8a55.92,55.92,0,0,0-79.08,79.08l77.78,77.8H53.33a53.34,53.34,0,0,0,0,106.67H304.92l-77.78,77.78a55.92,55.92,0,0,0,79.08,79.08L508.89,263.55a10.68,10.68,0,0,0,0-15.09Z\" transform=\"translate(0 -29.42)\"/></svg>', '1'),
(79, '<svg id=\"Capa_79\" data-name=\"Capa 79\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 417.65 373.37\"><defs></defs><path class=\"cls-1\" d=\"M84.22,167.73h53V275.2A35.82,35.82,0,0,0,173,311h71.65a35.82,35.82,0,0,0,35.82-35.82V167.73h53c19.78,0,24.48-11.34,10.49-25.33L234.15,32.63a35.82,35.82,0,0,0-50.66,0L73.73,142.4C59.74,156.39,64.43,167.73,84.22,167.73Z\" transform=\"translate(0 -22.14)\"/><path class=\"cls-1\" d=\"M24.39,395.51H393.26a24.39,24.39,0,1,0,0-48.77H24.39a24.39,24.39,0,1,0,0,48.77Z\" transform=\"translate(0 -22.14)\"/></svg>', '1'),
(80, '<svg id=\"Capa_80\" data-name=\"Capa 80\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 437.3 512\"><path d=\"M409.71,38.62C385.84,11.58,342.64,0,287.38,0H127A23,23,0,0,0,104.3,19.2L37.52,439.39a13.7,13.7,0,0,0,13.6,15.78h99L175,298.69l-.76,4.93a22.85,22.85,0,0,1,22.62-19.2h47c92.45,0,164.8-37.25,186-145,.64-3.2,1.63-9.34,1.63-9.34C437.49,90.21,431.44,63.17,409.71,38.62Z\" transform=\"translate(-37.35)\"/><path d=\"M456.53,150.5c-23,106.08-96.29,162.2-212.64,162.2H201.71L170.23,512h68.41a20,20,0,0,0,19.84-16.83l.8-4.23L275,392l1-5.44a20,20,0,0,1,19.81-16.83h12.51c80.86,0,144.16-32.58,162.65-126.82C478.45,205.12,474.86,173.41,456.53,150.5Z\" transform=\"translate(-37.35)\"/></svg>', '1'),
(81, '<svg id=\"Capa_81\" data-name=\"Capa 81\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 47 46.98\"><g id=\"Layer_1\" data-name=\"Layer 1\"><path d=\"M44.85,42.72H2.14a2.14,2.14,0,1,0,0,4.27h42.7a2.14,2.14,0,0,0,0-4.27Z\" transform=\"translate(0 -0.01)\"/><path d=\"M4.8,37.17a2.14,2.14,0,0,0,0,4.27H42.17a2.14,2.14,0,1,0,0-4.27h-.53V17.94h.53a1.06,1.06,0,0,0,1.07-1.06,1.07,1.07,0,0,0-1.07-1.07H4.8a1.07,1.07,0,0,0,0,2.13h.54V37.16H4.8ZM37.37,17.94V37.16H31V17.94Zm-10.68,0V37.16h-6.4V17.94Zm-17.08,0H16V37.16H9.61Z\" transform=\"translate(0 -0.01)\"/><path d=\"M2.14,13.89H44.87a2.14,2.14,0,0,0,.69-4.16L24.37.2a2.14,2.14,0,0,0-1.75,0L1.26,9.81a2.13,2.13,0,0,0,.88,4.08Z\" transform=\"translate(0 -0.01)\"/></g></svg>', '1'),
(82, '<svg id=\"flat-82\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 470.8 470.77\"><defs><style>.cls-82-1{fill:#d69651;}.cls-82-2{fill:#c78640;}.cls-82-3{fill:#ffc431;}.cls-82-4{fill:#d1e4ff;}.cls-82-5{fill:#fb6a83;}.cls-82-6{fill:#6173e8;}</style></defs><path class=\"cls-82-1\" d=\"M360,368l-16,16-11.16,3.82-75.3,25.76-75.69,25.89L106.94,465.1,40,488H24V472l13.69-32.86,19.46-46.71,19.46-46.7L97.5,295.6l18-43.27L135,205.63,144,184l16-16,75.2,75.2,86.39,86.39Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-2\" d=\"M181.85,439.47,106.94,465.1l-69.25-26,19.46-46.71Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-2\" d=\"M332.84,387.82l-75.3,25.76L76.61,345.73,97.5,295.6Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-2\" d=\"M321.59,329.59,115.53,252.33,135,205.63,235.2,243.2Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-3\" d=\"M200,160l33.45-33.45a22.36,22.36,0,0,0-7-36.36l-20.83-8.92a88,88,0,0,1-35.73-28.09L160,40l24-16a84.09,84.09,0,0,0,43.36,33.12l9.53,3.18a51.34,51.34,0,0,1,22.75,82.12L224,184Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-4\" d=\"M312,120l-8-48,48,16Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-5\" d=\"M262.18,39.81l3.89-15.52,32,8-3.89,15.52Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-3\" d=\"M339.55,33.24l24-16,8.88,13.31-24,16Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-6\" d=\"M152.26,89.86,167.78,86l8,32-15.52,3.89Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-6\" d=\"M352,312l33.45-33.45a22.36,22.36,0,0,1,36.36,7l8.92,20.83a88,88,0,0,0,28.09,35.73L472,352l16-24a84.09,84.09,0,0,1-33.12-43.36l-3.18-9.53a51.34,51.34,0,0,0-82.12-22.75L328,288Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-3\" d=\"M416,208l48,8-16-48Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-5\" d=\"M464.28,241.69l15.52-3.89,8,32-15.52,3.88Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-4\" d=\"M465.48,163.63l16-24,13.32,8.88-16,24Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-6\" d=\"M390.2,352l3.89-15.52,32,8L422.19,360Z\" transform=\"translate(-24 -17.23)\"/><path class=\"cls-82-5\" d=\"M256,224l31.86-37.2a62.74,62.74,0,0,1,40.7-21.53l.91-.1a65.35,65.35,0,0,0,56.66-50.74h0a44.84,44.84,0,0,1,32-33.55L480,64l8,32-43.26,9.61a45,45,0,0,0-34.42,35.28L410.1,142a68.58,68.58,0,0,1-60.45,55l-.95.09a70.15,70.15,0,0,0-47.11,24.94C291.11,234.67,280,248,280,248Z\" transform=\"translate(-24 -17.23)\"/></svg>', '1'),
(83, '<svg id=\"Capa_83\" data-name=\"Capa 83\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M437,75A256,256,0,0,0,75,437,256,256,0,0,0,437,75ZM256,446.67C150.87,446.67,65.33,361.13,65.33,256S150.87,65.33,256,65.33,446.67,150.87,446.67,256,361.13,446.67,256,446.67Z\" transform=\"translate(0)\"/><path d=\"M256,95.33C167.41,95.33,95.33,167.41,95.33,256a159.94,159.94,0,0,0,40,106,125.15,125.15,0,0,1,69.16-82.28,80,80,0,1,1,103,0A125.15,125.15,0,0,1,376.65,362a159.94,159.94,0,0,0,40-106c0-88.59-72.08-160.67-160.67-160.67Z\" transform=\"translate(0)\"/><circle cx=\"256\" cy=\"218.48\" r=\"50.04\"/><path d=\"M256,298.55A94.72,94.72,0,0,0,161.67,386a160.27,160.27,0,0,0,188.66,0A94.72,94.72,0,0,0,256,298.55Z\" transform=\"translate(0)\"/></svg>', '1'),
(84, '<svg id=\"Capa_84\" data-name=\"Capa 84\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 36.17 29.45\"><path d=\"M23.92,20.53a5.84,5.84,0,1,1-5.83-5.84A5.84,5.84,0,0,1,23.92,20.53Zm12.25-8.29V28.81a4,4,0,0,1-4,4H4a4,4,0,0,1-4-4V12.24a4,4,0,0,1,4-4H8.92V6.86a3.5,3.5,0,0,1,3.5-3.5H23.75a3.5,3.5,0,0,1,3.5,3.5V8.24h4.92A4,4,0,0,1,36.17,12.24Zm-9.25,8.29a8.84,8.84,0,1,0-8.83,8.83A8.85,8.85,0,0,0,26.92,20.53Z\" transform=\"translate(0 -3.36)\"/></svg>', '1'),
(85, '<svg id=\"Capa_85\" data-name=\"Capa 85\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 452.92\"><path d=\"M443.08,379.08a44.27,44.27,0,0,1-36.51-19.21l-21.69-31.54A147,147,0,0,1,256,403.69c-76.45,0-139.52-58.39-147-132.92h14a14.77,14.77,0,0,0,12.17-23.14l-21.53-31.31L81.09,168.86a14.77,14.77,0,0,0-12.17-6.4,15.39,15.39,0,0,0-1.8.11,12.72,12.72,0,0,0-1.76.33,14.74,14.74,0,0,0-8.61,6L2.6,247.63a14.77,14.77,0,0,0,12.17,23.14H30A226.47,226.47,0,0,0,416.13,416.13,228.37,228.37,0,0,0,446.22,379C445.18,379,444.13,379.08,443.08,379.08Z\" transform=\"translate(0 -29.54)\"/><path d=\"M267.26,241.23H244.75a7.75,7.75,0,0,1-7.74-7.74V222.24a7.74,7.74,0,0,1,7.74-7.73h22.51a7.74,7.74,0,0,1,7.73,7.73,14.77,14.77,0,0,0,29.54,0,37.33,37.33,0,0,0-33.76-37.1v-7.91a14.77,14.77,0,1,0-29.54,0v7.91a37.33,37.33,0,0,0-33.76,37.1v11.25a37.32,37.32,0,0,0,37.28,37.28h22.5a7.75,7.75,0,0,1,7.74,7.74v11.25a7.74,7.74,0,0,1-7.74,7.73h-22.5a7.74,7.74,0,0,1-7.74-7.73,14.77,14.77,0,1,0-29.54,0,37.32,37.32,0,0,0,33.76,37.1v7.91a14.77,14.77,0,1,0,29.54,0v-7.91a37.33,37.33,0,0,0,33.76-37.1V278.51A37.32,37.32,0,0,0,267.26,241.23Z\" transform=\"translate(0 -29.54)\"/><path d=\"M510.3,249.12a14.76,14.76,0,0,0-13.07-7.89H482A226.47,226.47,0,0,0,95.87,95.87a228,228,0,0,0-30.09,37.18c1-.08,2.09-.13,3.14-.13a44.3,44.3,0,0,1,36.52,19.21l21.68,31.54A147,147,0,0,1,256,108.31c76.45,0,139.52,58.39,147,132.92h-14a14.77,14.77,0,0,0-12.17,23.14l21.53,31.31,32.63,47.46a14.94,14.94,0,0,0,2.38,2.69,14.77,14.77,0,0,0,22-2.69L474.8,314.7l34.6-50.33A14.76,14.76,0,0,0,510.3,249.12Z\" transform=\"translate(0 -29.54)\"/></svg>', '1'),
(86, '<svg id=\"Capa_86\" data-name=\"Capa 86\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 510.15 512\"><path d=\"M369,220.92h94V387H369Z\" transform=\"translate(-0.93 0)\"/><path d=\"M209,220.92h94V387H209Z\" transform=\"translate(-0.93 0)\"/><path d=\"M481.06,417H30.94a30,30,0,0,0-30,30v35a30,30,0,0,0,30,30H481.06a30,30,0,0,0,30-30V447A30,30,0,0,0,481.06,417Z\" transform=\"translate(-0.93 0)\"/><path d=\"M492.27,94.21l-225.12-92a29.83,29.83,0,0,0-22.4,0l-225,92A29.85,29.85,0,0,0,.93,122V160.9a30,30,0,0,0,30,30H481.06a30,30,0,0,0,30-30V122A29.85,29.85,0,0,0,492.27,94.21ZM271,111.89a15,15,0,0,1-30,0v-32a15,15,0,1,1,30,0Z\" transform=\"translate(-0.93 0)\"/><path d=\"M48.94,220.92h94V387h-94Z\" transform=\"translate(-0.93 0)\"/></svg>', '1'),
(87, '<svg id=\"Capa_87\" data-name=\"Capa 87\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 392.18\"><defs><style>.cls-1-87{fill:#9dc6fb;}.cls-2-87{fill:#80b4fb;}.cls-3-87{fill:#407093;}.cls-4-87{fill:#365e7d;}.cls-5-87{fill:#ecbe6b;}.cls-6-87{fill:#e7a561;}.cls-7-87{fill:#e28086;}.cls-8-87{fill:#dd636e;}.cls-9-87{fill:#f9f6f9;}</style></defs><path class=\"cls-1-87\" d=\"M438,363.49H15.82A15.82,15.82,0,0,1,0,347.67V75.73A15.82,15.82,0,0,1,15.82,59.91H438a15.82,15.82,0,0,1,15.82,15.82V347.67A15.83,15.83,0,0,1,438,363.49Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-2-87\" d=\"M438,59.91H408.53a15.82,15.82,0,0,1,15.82,15.82V347.67a15.82,15.82,0,0,1-15.82,15.82H438a15.82,15.82,0,0,0,15.82-15.82V75.73A15.82,15.82,0,0,0,438,59.91Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-3-87\" d=\"M0,125.08H453.83v41.77H0Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-4-87\" d=\"M424.35,125.08h29.48v41.77H424.35Z\" transform=\"translate(0 -59.91)\"/><ellipse class=\"cls-5-87\" cx=\"110.37\" cy=\"229.38\" rx=\"64.67\" ry=\"30.98\"/><path class=\"cls-6-87\" d=\"M110.37,258.31a130.63,130.63,0,0,0-17.09,1.11c27.43,3.59,47.59,15.6,47.59,29.87s-20.16,26.29-47.59,29.88a132.43,132.43,0,0,0,17.09,1.1c35.71,0,64.67-13.87,64.67-31s-29-31-64.67-31Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-4-87\" d=\"M360.28,231.37H210.15a7.73,7.73,0,1,1,0-15.45H360.28a7.73,7.73,0,0,1,0,15.45Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-4-87\" d=\"M360.28,277.57H285.56a7.73,7.73,0,1,1,0-15.45h74.72a7.73,7.73,0,0,1,0,15.45Z\" transform=\"translate(0 -59.91)\"/><circle class=\"cls-7-87\" cx=\"407.25\" cy=\"287.43\" r=\"104.75\"/><path class=\"cls-8-87\" d=\"M407.25,242.58a106.78,106.78,0,0,0-15.06,1.08,104.76,104.76,0,0,1,0,207.35,104.76,104.76,0,1,0,15.06-208.43Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-9-87\" d=\"M445.31,393.13a7.69,7.69,0,0,1-5.46-2.27l-76.13-76.12a7.73,7.73,0,1,1,10.93-10.93l76.12,76.13a7.73,7.73,0,0,1-5.46,13.19Z\" transform=\"translate(0 -59.91)\"/><path class=\"cls-9-87\" d=\"M369.18,393.13a7.73,7.73,0,0,1-5.46-13.19l76.13-76.13a7.73,7.73,0,1,1,10.93,10.93l-76.13,76.12a7.72,7.72,0,0,1-5.47,2.27Z\" transform=\"translate(0 -59.91)\"/></svg>', '1'),
(88, '<svg id=\"Layer_88\" data-name=\"Layer 88\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 513.48 513.58\"><defs><style>.cls-1-88{fill:url(#linear-gradient);}.cls-2-88{fill:#fff;}</style><linearGradient id=\"linear-gradient\" x1=\"-30.96\" y1=\"578.55\" x2=\"-20.41\" y2=\"589.89\" gradientTransform=\"translate(-22726.5 -829.95) rotate(-90) scale(42.28 39.34)\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#fd5\"/><stop offset=\"0.5\" stop-color=\"#ff543e\"/><stop offset=\"1\" stop-color=\"#c837ab\"/></linearGradient></defs><path class=\"cls-1-88\" d=\"M32,34.84C-8.23,76.63,0,121,0,255.89c0,112-19.54,224.28,82.73,250.71,31.94,8.22,314.9,8.22,346.8,0,42.58-11,77.22-45.53,82-105.75.66-8.4.66-281.28,0-289.86-5-64.14-44.53-101.12-96.56-108.6C403,.62,400.6.11,339.41,0,122.39.11,74.82-9.56,32,34.84Z\" transform=\"translate(1.49 0.82)\"/><path class=\"cls-2-88\" d=\"M256,67c-77.46,0-151-6.9-179.12,65.21C65.24,162,66.92,200.64,66.92,256c0,48.6-1.55,94.27,9.92,123.82,28,72.15,102.19,65.24,179.07,65.24,74.18,0,150.66,7.72,179.1-65.24,11.62-30.08,9.92-68.18,9.92-123.82,0-73.85,4.07-121.53-31.75-157.33C376.92,62.42,327.87,67,255.87,67ZM239,101c161.58-.25,182.14-18.21,170.79,231.32-4,88.26-71.23,78.57-153.83,78.57-150.61,0-154.95-4.31-154.95-155C101,103.51,113,101.12,239,101Zm117.84,31.39a22.68,22.68,0,1,0,22.68,22.67A22.67,22.67,0,0,0,356.86,132.42ZM256,158.93A97.08,97.08,0,1,0,353,256a97.09,97.09,0,0,0-97-97.09ZM256,193c83.3,0,83.41,126,0,126S172.54,193,256,193Z\" transform=\"translate(1.49 0.82)\"/></svg>', '1'),
(89, '<svg id=\"Layer_89\" data-name=\"Layer 89\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 382 382\"><defs><style>.cls-1-89{fill:#0077b7;}</style></defs><path class=\"cls-1-89\" d=\"M347.45,0H34.56A34.56,34.56,0,0,0,0,34.56V347.44A34.56,34.56,0,0,0,34.56,382H347.44A34.56,34.56,0,0,0,382,347.44V34.56A34.55,34.55,0,0,0,347.45,0ZM118.21,329.84a10.05,10.05,0,0,1-10.06,10.06H65.35a10.05,10.05,0,0,1-10.06-10.06V150.4a10.05,10.05,0,0,1,10.06-10.05h42.8a10.05,10.05,0,0,1,10.06,10.05ZM86.75,123.43a40.67,40.67,0,1,1,40.66-40.66A40.67,40.67,0,0,1,86.75,123.43ZM341.91,330.65a9.25,9.25,0,0,1-9.25,9.25H286.73a9.25,9.25,0,0,1-9.25-9.25V246.49c0-12.56,3.69-55-32.81-55-28.31,0-34,29.07-35.2,42.11v97.08a9.25,9.25,0,0,1-9.25,9.25H155.8a9.25,9.25,0,0,1-9.25-9.25V149.59a9.25,9.25,0,0,1,9.25-9.24h44.42a9.25,9.25,0,0,1,9.25,9.24v15.66c10.49-15.76,26.09-27.91,59.31-27.91,73.55,0,73.13,68.71,73.13,106.47v86.84Z\"/></svg>', '1'),
(90, '<svg id=\"Capa_90\" data-name=\"Capa 90\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 112.2 112.2\"><defs><style>.cls-1-90{fill:#3b5998;}.cls-2-90{fill:#fff;}</style></defs><circle class=\"cls-1-90\" cx=\"56.1\" cy=\"56.1\" r=\"56.1\"/><path class=\"cls-2-90\" d=\"M70.2,58.29h-10V95H45V58.29H37.81V45.41H45V37.07c0-6,2.83-15.31,15.3-15.31l11.23,0V34.32H63.41c-1.34,0-3.22.67-3.22,3.51v7.59H71.53Z\"/></svg>', '1'),
(91, '<svg id=\"Layer_91\" data-name=\"Layer 91\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C114.84,0,0,114.84,0,256S114.84,512,256,512,512,397.16,512,256,397.16,0,256,0ZM368,277.33H277.33V368a21.33,21.33,0,1,1-42.66,0V277.33H144a21.33,21.33,0,0,1,0-42.66h90.67V144a21.33,21.33,0,1,1,42.66,0v90.67H368a21.33,21.33,0,0,1,0,42.66Z\"/></svg>', '1'),
(92, '<svg id=\"Capa_92\" data-name=\"Capa 92\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 481.2 511.99\"><path d=\"M490.3,186.46a21.34,21.34,0,0,0-30.17.08L203.69,444.38A85.33,85.33,0,0,1,83.05,323.66l264-265.43a53.33,53.33,0,0,1,75.37,75.47L203.73,352.43l0,.05a21.33,21.33,0,0,1-30.12-30.2L279.15,216.67A21.33,21.33,0,1,0,249,186.51L143.4,292.11a64,64,0,0,0,90.5,90.5l.08-.09L452.63,163.87A96,96,0,0,0,316.88,28.1l-264,265.43a128,128,0,0,0,181.06,181L490.39,216.63A21.33,21.33,0,0,0,490.3,186.46Z\" transform=\"translate(-15.39 0)\"/></svg>', '1'),
(93, '<svg id=\"Capa_93\" data-name=\"Capa 93\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 375.47 477.87\"><path d=\"M324.27,119.47A17.07,17.07,0,0,1,307.2,102.4V0H102.4A51.2,51.2,0,0,0,51.2,51.2V426.67a51.2,51.2,0,0,0,51.2,51.2H375.47a51.21,51.21,0,0,0,51.2-51.2V119.47Z\" transform=\"translate(-51.2)\"/><polygon points=\"290.13 10 290.13 85.33 365.47 85.33 290.13 10\"/></svg>', '1'),
(94, '<svg id=\"Capa_94\" data-name=\"Capa 94\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 495.79 286.19\"><rect x=\"53.32\" y=\"232.07\" width=\"42.69\" height=\"168.5\" transform=\"translate(-201.8 40.64) rotate(-45)\"/><polygon points=\"375.15 30.19 344.96 0 209.71 135.36 239.89 165.55 375.15 30.19\"/><polygon points=\"465.71 0 239.79 225.81 150.83 136.85 120.64 167.04 239.79 286.19 495.79 30.19 465.71 0\"/></svg>', '1'),
(95, '<svg id=\"Layer_95\" data-name=\"Layer 95\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M475.08,205.3a222.65,222.65,0,0,0-28.31-68.38l19-31.74-59-59-31.74,19A222.65,222.65,0,0,0,306.7,36.92L297.72,0H214.28l-9,36.92a222.65,222.65,0,0,0-68.38,28.31l-31.74-19-59,59,19,31.74A222.65,222.65,0,0,0,36.92,205.3L0,214.28v83.44l36.92,9a222.65,222.65,0,0,0,28.31,68.38l-19,31.74,59,59,31.74-19a222.65,222.65,0,0,0,68.38,28.31l9,36.92h83.44l9-36.92a222.65,222.65,0,0,0,68.38-28.31l31.74,19,59-59-19-31.74a222.65,222.65,0,0,0,28.31-68.38l36.92-9V214.28ZM256,421c-91,0-165-74-165-165S165,91,256,91s165,74,165,165S347,421,256,421Z\"/><path d=\"M271,121.92,153.18,301H241v89.08L358.82,211H271Z\"/></svg>', '1'),
(96, '<svg id=\"Capa_96\" data-name=\"Capa 96\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C115.39,0,0,115.39,0,256A254,254,0,0,0,35.86,386L.78,492.25a15,15,0,0,0,19,19L126,476.14A254,254,0,0,0,256,512c140.61,0,256-115.39,256-256S396.61,0,256,0Zm0,451a45,45,0,1,1,45-45A45.05,45.05,0,0,1,256,451Zm63.24-215.82L286,268.42V301a30,30,0,0,1-60,0V268.42A59.59,59.59,0,0,1,243.58,226l33.24-33.24c7.68-7.68,9.18-15.46,9.18-25.38A31.42,31.42,0,0,0,254.62,136,28.67,28.67,0,0,0,226,164.62a30,30,0,0,1-60,0A88.72,88.72,0,0,1,254.62,76,91.48,91.48,0,0,1,346,167.38C346,195.73,336.72,217.7,319.24,235.18Z\" transform=\"translate(0)\"/></svg>', '1'),
(97, '<svg id=\"Layer_97\" data-name=\"Layer 97\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C114.84,0,0,114.84,0,256S114.84,512,256,512,512,397.16,512,256,397.16,0,256,0Zm0,405.33A21.33,21.33,0,1,1,277.33,384,21.33,21.33,0,0,1,256,405.33ZM289.77,269.7a21.42,21.42,0,0,0-12.44,19.39v9.58a21.33,21.33,0,1,1-42.66,0v-9.58a64.28,64.28,0,0,1,37.22-58.16c21.74-10,37.44-36.56,37.44-49.6a53.33,53.33,0,1,0-106.66,0,21.34,21.34,0,1,1-42.67,0,96,96,0,1,1,192,0C352,210.16,327,252.52,289.77,269.7Z\"/></svg>', '1'),
(98, '<svg id=\"Capa98\" data-name=\"Capa 98\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 257.57 451.85\"><path d=\"M345.44,248.29,151.15,442.57a31.64,31.64,0,0,1-44.75-44.74L278.32,225.92,106.41,54A31.64,31.64,0,0,1,151.16,9.27L345.45,203.55a31.64,31.64,0,0,1,0,44.74Z\" transform=\"translate(-97.14 0)\"/></svg>', '1'),
(99, '<svg id=\"Capa_99\" data-name=\"Capa 99\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 59.46 48.25\"><path d=\"M58.86,33.89,45,20.07V11a2.06,2.06,0,1,0-4.11,0v5L31.18,6.21a2.05,2.05,0,0,0-2.9,0L.6,33.89a2.05,2.05,0,1,0,2.91,2.9L29.73,10.57,56,36.79a2.06,2.06,0,0,0,1.45.61,2.06,2.06,0,0,0,1.45-3.51Z\" transform=\"translate(0 -5.61)\"/><path d=\"M53,36.24,31.18,14.45a2.05,2.05,0,0,0-2.9,0L6.48,36.24a2.06,2.06,0,0,0-.56,1.07,5,5,0,0,0-.09.94V48.89a5,5,0,0,0,5,5H21.43a5,5,0,0,0,5-5V41.07a3.68,3.68,0,0,1,3.33-3.65,3.67,3.67,0,0,1,3.33,3.65v7.82a5,5,0,0,0,5,5H48.67a5,5,0,0,0,5-5V38.25a5,5,0,0,0-.09-.94A2.07,2.07,0,0,0,53,36.24Z\" transform=\"translate(0 -5.61)\"/></svg>', '1'),
(100, '<svg id=\"Capa_100\" data-name=\"Capa 100\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 390\"><path d=\"M467,61H165a45,45,0,0,0,0,90H467a45,45,0,0,0,0-90Z\" transform=\"translate(0 -61)\"/><path d=\"M467,211H165a45,45,0,0,0,0,90H467a45,45,0,0,0,0-90Z\" transform=\"translate(0 -61)\"/><path d=\"M467,361H165a45,45,0,0,0,0,90H467a45,45,0,0,0,0-90Z\" transform=\"translate(0 -61)\"/><path d=\"M45,61a45,45,0,1,0,45,45A45,45,0,0,0,45,61Z\" transform=\"translate(0 -61)\"/><path d=\"M45,211a45,45,0,1,0,45,45A45,45,0,0,0,45,211Z\" transform=\"translate(0 -61)\"/><path d=\"M45,361a45,45,0,1,0,45,45A45,45,0,0,0,45,361Z\" transform=\"translate(0 -61)\"/></svg>', '1'),
(101, '<svg id=\"Capa_101\" data-name=\"Capa 101\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512.54 511.26\"><path d=\"M481.28,377,409.6,323.2c17.92-30.72,25.6-66.56,25.6-105a217.6,217.6,0,1,0-217.6,217.6c38.4,0,71.68-10.24,105-25.6l53.76,71.68c28.16,35.84,81.92,41,115.2,7.68C522.24,458.88,519.68,405.12,481.28,377ZM217.6,384.64A166.4,166.4,0,1,1,384,218.24,166.07,166.07,0,0,1,217.6,384.64Z\" transform=\"translate(0 -0.64)\"/></svg>', '1'),
(102, '<svg id=\"Capa_102\" data-name=\"Capa 101\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 447.24 383.56\"><path d=\"M420.36,192.23a31.07,31.07,0,0,0-5.53-.41H99.31l6.87-3.2a63.87,63.87,0,0,0,18.08-12.8l88.48-88.48a33.1,33.1,0,0,0,4.64-42.4A32,32,0,0,0,172.66,38a33.26,33.26,0,0,0-3.28,2.76l-160,160a32,32,0,0,0,0,45.25l0,0,160,160a32,32,0,0,0,45.26-.06,32.59,32.59,0,0,0,2.74-3.14,33.1,33.1,0,0,0-4.64-42.4l-88.32-88.64a63.88,63.88,0,0,0-16-11.68l-9.6-4.32H413.07a33.13,33.13,0,0,0,33.76-26.88A32,32,0,0,0,420.36,192.23Z\" transform=\"translate(0 -31.84)\"/></svg>', '1'),
(103, '<svg id=\"Capa_103\" data-name=\"Capa 103\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 459 510\"><g id=\"notifications-off\"><path d=\"M242.25,510a51.15,51.15,0,0,0,51-51h-102A51.15,51.15,0,0,0,242.25,510ZM408,216.75C408,137.7,354.45,74,280.5,56.1V38.25C280.5,17.85,262.65,0,242.25,0S204,17.85,204,38.25V56.1c-12.75,2.55-25.5,7.65-35.7,12.75L408,308.55ZM400.35,433.5l51,51,33.15-33.15L58.65,25.5,25.5,58.65l74,73.95q-23,38.25-23,84.15V357l-51,51v25.5Z\" transform=\"translate(-25.5)\"/></g></svg>', '1'),
(104, '<svg id=\"Capa_104\" data-name=\"Capa 104\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M29.77,72.7A47.72,47.72,0,0,0,0,116.84V377a47.75,47.75,0,0,0,47.73,47.65h71.93V485a26.65,26.65,0,0,0,15.78,24.51A27.24,27.24,0,0,0,146.73,512a26.77,26.77,0,0,0,17.59-6.63l93.2-80.69H382.35Z\" transform=\"translate(0 0)\"/><path d=\"M507.62,465.74,466.4,424.59A47.74,47.74,0,0,0,511.3,377V116.84a47.75,47.75,0,0,0-47.73-47.66H110.39L45.47,4.37a14.94,14.94,0,0,0-23,18.79L486.48,486.84a14.93,14.93,0,0,0,21.14-21.1Z\" transform=\"translate(0 0)\"/></svg>', '1'),
(105, '<svg id=\"bold\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><circle cx=\"256\" cy=\"256\" r=\"53.33\"/><path d=\"M279.21,426.67H232.79a34.6,34.6,0,0,1-34-26.65l-4.52-19.16a138.49,138.49,0,0,1-19.86-11L153.73,376c-15.77,4.65-32.39-1.7-40.47-15.44l-23-37.93a33.93,33.93,0,0,1,6.59-43.08l15.38-13.44c-.25-3.45-.38-6.82-.38-10.15s.13-6.7.38-10.13L97,232.58c-12.52-11.2-15.29-28.8-6.91-43l23.34-38.42c8-13.51,24.51-19.82,40.23-15.23l20.74,6.23a139.36,139.36,0,0,1,19.86-11l4.57-19.27a34.51,34.51,0,0,1,34-26.54h46.42a34.6,34.6,0,0,1,34,26.65l4.52,19.16a138.49,138.49,0,0,1,19.86,11L358.25,136c15.77-4.67,32.41,1.7,40.49,15.42l23,38a33.92,33.92,0,0,1-6.59,43.08l-15.38,13.44c.25,3.43.38,6.8.38,10.13s-.13,6.7-.38,10.13L415,279.4l.19.17a33.85,33.85,0,0,1,6.72,42.84l-23.34,38.4c-8,13.53-24.62,19.9-40.28,15.21l-20.71-6.21a139.36,139.36,0,0,1-19.86,11l-4.57,19.27a34.46,34.46,0,0,1-33.94,26.56Zm-7.55-36.38a.15.15,0,0,1,0,.09Zm-31.34-.08v0ZM238.83,384h34.32l5.63-23.7a21.27,21.27,0,0,1,13.68-15.21,98.29,98.29,0,0,0,28-15.49A21.27,21.27,0,0,1,339.78,326l25.43,7.64,16.25-26.77-18.2-15.9a21.34,21.34,0,0,1-7-19.37,94.85,94.85,0,0,0,0-31.06,21.34,21.34,0,0,1,7-19.37l18.22-15.91-16.25-26.8-25.41,7.64a21.32,21.32,0,0,1-19.31-3.65,98.55,98.55,0,0,0-28-15.49,21.28,21.28,0,0,1-13.67-15.21L273.17,128H238.85l-5.63,23.7a21.27,21.27,0,0,1-13.68,15.21,98.29,98.29,0,0,0-28,15.49,21.33,21.33,0,0,1-19.33,3.65l-25.41-7.64-16.27,26.8,18.21,15.89a21.29,21.29,0,0,1,7,19.37,94.85,94.85,0,0,0,0,31.06,21.29,21.29,0,0,1-7,19.37l-18.19,15.89,16.25,26.78,25.43-7.64a21.37,21.37,0,0,1,19.29,3.65,98.55,98.55,0,0,0,28,15.49,21.26,21.26,0,0,1,13.67,15.21Zm123.28-45.29-.13.19Zm-212.33-.21.11.17A.62.62,0,0,1,149.78,338.5ZM362.13,173.31a.62.62,0,0,0,.11.17ZM150,173.1l-.13.19Zm121.66-51.39v0Zm-31.32-.11,0,.09A.16.16,0,0,1,240.36,121.6Z\"/><path d=\"M256,512C114.84,512,0,397.16,0,256S114.84,0,256,0,512,114.84,512,256,397.16,512,256,512Zm0-469.33C138.37,42.67,42.67,138.37,42.67,256S138.37,469.33,256,469.33,469.33,373.63,469.33,256,373.63,42.67,256,42.67Z\"/></svg>', '1');
INSERT INTO `i_svg_icons` (`icon_id`, `icon_code`, `icon_status`) VALUES
(106, '<svg id=\"Layer_106\" data-name=\"Layer 106\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 384\"><path d=\"M405.33,213.87V106.67A42.71,42.71,0,0,0,362.67,64h-320A42.72,42.72,0,0,0,0,106.67V320a42.72,42.72,0,0,0,42.67,42.67H281.88c14,49.17,59.19,85.33,112.79,85.33a117.27,117.27,0,0,0,58.67-15.71,10.66,10.66,0,0,0-10.68-18.46,96,96,0,1,1,48-83.16v10.66a21.34,21.34,0,1,1-42.67,0V298.67A10.63,10.63,0,0,0,430,291a53.32,53.32,0,1,0,4.51,74.8A42.6,42.6,0,0,0,512,341.33V330.67C512,269.57,465,219.29,405.33,213.87ZM42.67,85.33h320c.44,0,.81.23,1.25.26L211.44,210.75c-5.43,3.42-13.29,2.71-16.82.54L41.43,85.58C41.86,85.56,42.23,85.33,42.67,85.33ZM384,213.87c-59.71,5.42-106.67,55.7-106.67,116.8,0,3.6.22,7.15.54,10.66H42.67A21.36,21.36,0,0,1,21.33,320V106.67a21.06,21.06,0,0,1,1.81-8.48L182,228.48a37.53,37.53,0,0,0,40.58.46,10.1,10.1,0,0,0,1.71-1.16L382.19,98.18a21.15,21.15,0,0,1,1.81,8.49v107.2Zm10.67,148.8a32,32,0,1,1,32-32A32,32,0,0,1,394.67,362.67Z\" transform=\"translate(0 -64)\"/></svg>', '1'),
(107, '<svg id=\"Layer_107\" data-name=\"Layer 107\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 639.37 543.22\"><path d=\"M520.27,192.63a18.71,18.71,0,0,0-20.06.18L334.89,294.72c-40.59,3.13-78,35.42-78,83.1a83.4,83.4,0,0,0,83.61,83.35c47.84,0,80.17-37.18,83.35-77.59L526.3,218.44A18.74,18.74,0,0,0,520.27,192.63Z\" transform=\"translate(-20.98 -66.44)\"/><path d=\"M321.94,66.44A320.37,320.37,0,0,0,131.69,143l134.6,134.36a132.06,132.06,0,0,1,55.65-21.55Z\" transform=\"translate(-20.98 -66.44)\"/><path d=\"M104.67,169a318.09,318.09,0,0,0-83.5,186.61,44.76,44.76,0,0,0,44.61,48.88H166a45,45,0,0,0,44.3-38.18A130.67,130.67,0,0,1,238.6,302.7Z\" transform=\"translate(-20.98 -66.44)\"/><path d=\"M535.65,535.44h-390a18.76,18.76,0,0,1,0-37.51h390a18.76,18.76,0,0,1,0,37.51Z\" transform=\"translate(-20.98 -66.44)\"/><path d=\"M438.08,609.66H243a18.76,18.76,0,0,1,0-37.51H438.08a18.76,18.76,0,0,1,0,37.51Z\" transform=\"translate(-20.98 -66.44)\"/><path d=\"M359.4,66.44V244.58l178.7-111A320.46,320.46,0,0,0,359.4,66.44Z\" transform=\"translate(-20.98 -66.44)\"/><path d=\"M660.16,355.61a317.87,317.87,0,0,0-77.56-180L471.05,366.36a45,45,0,0,0,44.29,38.13H615.56a44.74,44.74,0,0,0,44.6-48.88Z\" transform=\"translate(-20.98 -66.44)\"/></svg>', '1'),
(108, '<svg id=\"Capa_108\" data-name=\"Capa 108\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M464.62,256a210.77,210.77,0,0,0-1.22-22.6L512,191.79a264.51,264.51,0,0,0-29.46-71.12l-63.7,4.93a209.88,209.88,0,0,0-32.17-32.22l4.95-63.8A263.9,263.9,0,0,0,320.49.12L279,48.64a209.79,209.79,0,0,0-45.55,0L191.79,0a264.51,264.51,0,0,0-71.12,29.46l4.93,63.7a209.88,209.88,0,0,0-32.22,32.17l-63.8-5A263.9,263.9,0,0,0,.12,191.51l48.52,41.54a209.79,209.79,0,0,0,0,45.55L0,320.21a264.51,264.51,0,0,0,29.46,71.12l63.7-4.93a209.88,209.88,0,0,0,32.17,32.22l-5,63.8a263.9,263.9,0,0,0,71.13,29.46l41.54-48.52a209.79,209.79,0,0,0,45.55,0L320.21,512a263.66,263.66,0,0,0,71.12-29.46l-4.93-63.7a209.88,209.88,0,0,0,32.22-32.17l63.8,4.95a263.9,263.9,0,0,0,29.46-71.13L463.36,279A209.59,209.59,0,0,0,464.62,256ZM256,406c-82.71,0-150-67.29-150-150s67.29-150,150-150,150,67.29,150,150S338.71,406,256,406Z\" transform=\"translate(0 0)\"/><path d=\"M256,136A120,120,0,1,0,376,256,120.13,120.13,0,0,0,256,136Zm0,210a90,90,0,1,1,90-90A90.1,90.1,0,0,1,256,346Z\" transform=\"translate(0 0)\"/><circle cx=\"256\" cy=\"256\" r=\"60\"/></svg>', '1'),
(109, '<svg id=\"bold\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><ellipse cx=\"256\" cy=\"64\" rx=\"256\" ry=\"64\"/><path d=\"M256,170.67c-141.38,0-256-28.65-256-64V256c0,35.35,114.62,64,256,64s256-28.65,256-64V106.67C512,142,397.38,170.67,256,170.67ZM74.67,256a32,32,0,1,1,32-32A32,32,0,0,1,74.67,256Z\"/><path d=\"M256,362.67c-141.38,0-256-28.65-256-64V448c0,35.35,114.62,64,256,64s256-28.65,256-64V298.67C512,334,397.38,362.67,256,362.67ZM74.67,448a32,32,0,1,1,32-32A32,32,0,0,1,74.67,448Z\"/></svg>', '1'),
(110, '<svg id=\"Capa_110\" data-name=\"Capa 110\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 469.33 512\"><path d=\"M480,0C263.14,0,84.78,166.83,65.88,378.84c36-50.82,80.17-99.72,135.27-155.93a10.67,10.67,0,1,1,15.23,14.94c-8.3,8.44-16.72,17.21-25.19,26.09C186.35,269,181.61,274,177,279c-.85.91-1.71,1.83-2.56,2.77C106.3,354.67,57.62,418.13,22.27,497a10.68,10.68,0,0,0,19.48,8.75A585.53,585.53,0,0,1,85.37,426.3a415.66,415.66,0,0,0,355.7-218.58A10.66,10.66,0,0,0,431.66,192H392.15L463,151.51a10.73,10.73,0,0,0,4.8-5.78A414.75,414.75,0,0,0,490.66,10.67,10.66,10.66,0,0,0,480,0Z\" transform=\"translate(-21.33)\"/></svg>', '1'),
(111, '<svg id=\"Capa_111\" data-name=\"Capa 111\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 388.2\"><defs><style>.cls-1-111{fill:#eaeaea;}.cls-2-111{fill:#fcc63d;}.cls-3-111{fill:#f9572b;}.cls-4-111{fill:#bcbcbc;}.cls-5-111{fill:#45b8c4;}.cls-6-111{fill:#424c59;}.cls-7-111{fill:#239999;}</style></defs><path class=\"cls-1-111\" d=\"M512,95.39V416.61a33.5,33.5,0,0,1-33.5,33.49h-445A33.49,33.49,0,0,1,0,416.61V95.39A33.48,33.48,0,0,1,33.49,61.9h445A33.49,33.49,0,0,1,512,95.39Z\" transform=\"translate(0 -61.9)\"/><path class=\"cls-2-111\" d=\"M512,95.39V148H0V95.39A33.48,33.48,0,0,1,33.49,61.9h445A33.49,33.49,0,0,1,512,95.39Z\" transform=\"translate(0 -61.9)\"/><circle class=\"cls-3-111\" cx=\"53.35\" cy=\"42.19\" r=\"17.25\"/><circle class=\"cls-4-111\" cx=\"111.43\" cy=\"42.19\" r=\"17.25\"/><circle class=\"cls-5-111\" cx=\"169.49\" cy=\"42.19\" r=\"17.25\"/><path class=\"cls-6-111\" d=\"M69,426.34a7.84,7.84,0,0,1-7.84-7.84V176.08a7.84,7.84,0,0,1,15.67,0V418.5A7.84,7.84,0,0,1,69,426.34Z\" transform=\"translate(0 -61.9)\"/><circle class=\"cls-3-111\" cx=\"68.96\" cy=\"194.64\" r=\"22.99\"/><path class=\"cls-6-111\" d=\"M139.5,426.34a7.84,7.84,0,0,1-7.84-7.84V176.08a7.84,7.84,0,0,1,15.67,0V418.5A7.84,7.84,0,0,1,139.5,426.34Z\" transform=\"translate(0 -61.9)\"/><circle class=\"cls-3\" cx=\"139.49\" cy=\"261.86\" r=\"22.99\"/><path class=\"cls-6-111\" d=\"M210,426.34a7.84,7.84,0,0,1-7.84-7.84V176.08a7.84,7.84,0,0,1,15.68,0V418.5A7.84,7.84,0,0,1,210,426.34Z\" transform=\"translate(0 -61.9)\"/><circle class=\"cls-3\" cx=\"210.02\" cy=\"304.36\" r=\"22.99\"/><path class=\"cls-6-111\" d=\"M467.07,213.18H288.4a7.84,7.84,0,1,1,0-15.68H467.08a7.84,7.84,0,0,1,0,15.68Z\" transform=\"translate(0 -61.9)\"/><path class=\"cls-5-111\" d=\"M376.17,223.1H321a7,7,0,0,1-7-7V194.61a7,7,0,0,1,7-7h55.21a7,7,0,0,1,7,7v21.46A7,7,0,0,1,376.17,223.1Z\" transform=\"translate(0 -61.9)\"/><path class=\"cls-6-111\" d=\"M467.07,275.87H288.4a7.84,7.84,0,1,1,0-15.67H467.08a7.84,7.84,0,1,1,0,15.67Z\" transform=\"translate(0 -61.9)\"/><path class=\"cls-5-111\" d=\"M438.86,285.8h-55.2a7,7,0,0,1-7-7V257.31a7,7,0,0,1,7-7h55.2a7,7,0,0,1,7,7v21.45A7,7,0,0,1,438.86,285.8Z\" transform=\"translate(0 -61.9)\"/><rect class=\"cls-2-111\" x=\"294.66\" y=\"263.61\" width=\"178.68\" height=\"87.96\"/><path class=\"cls-3-111\" d=\"M341.68,400.21a7.82,7.82,0,0,1-6.57-3.57l-9.18-14.19H294.67a7.84,7.84,0,1,1,0-15.67h35.52a7.83,7.83,0,0,1,6.58,3.58l4,6.24,14.77-29.52a7.83,7.83,0,0,1,14.24.49l11.63,27.92L396.3,363.4a7.85,7.85,0,0,1,10.09.17l16.08,14,11-34.6a7.84,7.84,0,0,1,14.65-.77L460.71,371h12.63a7.84,7.84,0,1,1,0,15.67H455.58a7.83,7.83,0,0,1-7.18-4.7l-6.29-14.37-8.32,26.15a7.84,7.84,0,0,1-12.62,3.53l-20.1-17.51L383.2,394.27A7.83,7.83,0,0,1,371,391.21l-9.08-21.81-13.25,26.48a7.8,7.8,0,0,1-6.57,4.32Z\" transform=\"translate(0 -61.9)\"/><path class=\"cls-7-111\" d=\"M473.34,421.3H294.67a7.84,7.84,0,0,1-7.84-7.84v-88a7.83,7.83,0,0,1,7.84-7.83H473.34a7.83,7.83,0,0,1,7.84,7.83v88A7.84,7.84,0,0,1,473.34,421.3ZM302.5,405.62h163V333.34h-163Z\" transform=\"translate(0 -61.9)\"/></svg>', '1'),
(112, '<svg id=\"Layer_112\" data-name=\"Layer 112\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C114.61,0,0,114.61,0,256S114.61,512,256,512,512,397.39,512,256,397.39,0,256,0ZM386.59,226.66,252.75,360.51a40.28,40.28,0,0,1-56.95,0l-70.39-70.39a40.27,40.27,0,1,1,57-57l41.91,41.92L329.64,169.72a40.27,40.27,0,1,1,56.95,56.94Z\"/></svg>', '1'),
(113, '<svg id=\"Capa_113\" data-name=\"Capa 113\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M256,0C114.84,0,0,114.84,0,256S114.84,512,256,512,512,397.16,512,256,397.16,0,256,0Zm0,448C150.14,448,64,361.86,64,256A189.56,189.56,0,0,1,99.54,144.8L367.2,412.46A189.56,189.56,0,0,1,256,448Zm156.46-80.8L144.8,99.54A189.56,189.56,0,0,1,256,64c105.86,0,192,86.14,192,192A189.56,189.56,0,0,1,412.46,367.2Z\"/></svg>', '1'),
(114, '<svg id=\"Layer_114\" data-name=\"Layer 114\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 511.66\"><defs><style>.cls-114{fill:#e04f5f;}.cls-214{fill:#fff;}</style></defs><ellipse class=\"cls-114\" cx=\"256\" cy=\"255.83\" rx=\"256\" ry=\"255.83\"/><rect class=\"cls-214\" x=\"228.02\" y=\"113.14\" width=\"55.99\" height=\"285.67\" transform=\"translate(-106.02 255.84) rotate(-45)\"/><rect class=\"cls-214\" x=\"113.19\" y=\"228.03\" width=\"285.67\" height=\"55.99\" transform=\"translate(-106.05 255.86) rotate(-45)\"/></svg>', '1'),
(115, '<svg id=\"Capa_115\" data-name=\"Capa 115\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 47 43.45\"><g id=\"Layer_115\" data-name=\"Layer 115\"><path d=\"M46.91,20.12a1,1,0,0,0-.9-.57H43.08a21.71,21.71,0,1,0-2.54,14.82,2,2,0,0,0-3.42-2A17.79,17.79,0,1,1,39,19.55h-2.9a1,1,0,0,0-.9.57,1,1,0,0,0,.14,1.05l4.94,5.93a1,1,0,0,0,.76.35,1,1,0,0,0,.75-.35l4.94-5.93A1,1,0,0,0,46.91,20.12Z\" transform=\"translate(0 -1.77)\"/><path d=\"M21.73,6.71a2,2,0,0,0-2,2v12a3.46,3.46,0,0,0,2,6.29,3.59,3.59,0,0,0,1.46-.33l6.87,4a1.93,1.93,0,0,0,1,.27,2,2,0,0,0,1-3.69l-6.88-4a3.43,3.43,0,0,0-1.45-2.53v-12A2,2,0,0,0,21.73,6.71Zm0,18.27a1.48,1.48,0,1,1,1.48-1.48A1.49,1.49,0,0,1,21.73,25Z\" transform=\"translate(0 -1.77)\"/></g></svg>', '1'),
(116, '<svg id=\"Capa_116\" data-name=\"Capa 116\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 466 512\"><path d=\"M447.72,97.79,297.21,11a82.78,82.78,0,0,0-82.42,0L64.29,97.79A82.74,82.74,0,0,0,23,169.27V342.73a82.75,82.75,0,0,0,41.28,71.48L214.79,501a82.75,82.75,0,0,0,82.42,0l150.5-86.8A82.74,82.74,0,0,0,489,342.73V169.27A82.75,82.75,0,0,0,447.72,97.79ZM449,342.73a42.62,42.62,0,0,1-21.27,36.82l-150.5,86.8a42.63,42.63,0,0,1-42.46,0L84.26,379.55A42.63,42.63,0,0,1,63,342.73V169.27a42.62,42.62,0,0,1,21.27-36.82l150.5-86.8a42.63,42.63,0,0,1,42.46,0l150.5,86.8A42.62,42.62,0,0,1,449,169.27Z\" transform=\"translate(-23 0)\"/><path d=\"M237,240.73l-74.28-62.86a20,20,0,1,0-25.83,30.54L193.11,256l-56.23,47.59a20,20,0,0,0,25.83,30.54L237,271.27A20,20,0,0,0,237,240.73Z\" transform=\"translate(-23 0)\"/><path d=\"M362.21,298.86h-90a20,20,0,1,0,0,40h90a20,20,0,0,0,0-40Z\" transform=\"translate(-23 0)\"/></svg>', '1'),
(117, '<svg id=\"Capa_117\" data-name=\"Capa 117\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 511.99\"><path d=\"M151,421a60,60,0,0,0-120,0v30c0,16.54-14.46,31-31,31v30H61c49.63,0,90-41.37,90-91Z\" transform=\"translate(0 0)\"/><path d=\"M251.06,125.6a15,15,0,1,0,18.37,10.61A15,15,0,0,0,251.06,125.6Z\" transform=\"translate(0 0)\"/><path d=\"M166.2,239.43a15,15,0,0,0,7.78-29h0c-4.26-1.15-5.41-.28-8.42.7q.25,13.56.35,28.13C166,239.32,166.09,239.4,166.2,239.43Z\" transform=\"translate(0 0)\"/><path d=\"M331.54,368a15,15,0,1,0-10.61,18.39A15,15,0,0,0,331.54,368Z\" transform=\"translate(0 0)\"/><path d=\"M381.46,181.76a15,15,0,1,0-21.21,0A15,15,0,0,0,381.46,181.76Z\" transform=\"translate(0 0)\"/><path d=\"M286.88,30.21C237.6,32,192.37,51.47,156.31,76.2c4.05,27.5,6.9,61.74,8.42,104.09A45,45,0,1,1,170.16,270c-1.44,0-2.88-.43-4.32-.57-.18,24-.73,47.73-1.76,70.4,15.36,6.38,27.37,14.88,35.21,25.9,15.88,22.31,11.65,48.47,10,56-5.56,24.91,9.12,50.27,34.29,55.12a227.34,227.34,0,0,0,42.71,4.07C409.64,480.85,513,380.64,512,254.2,510.94,126,400,26,286.88,30.21ZM339,139.33A45,45,0,1,1,402.67,203v0A45,45,0,0,1,339,139.33ZM211.48,151.72a45,45,0,1,1,55.11,31.83,45,45,0,0,1-55.11-31.83ZM328.7,415.37a45,45,0,1,1,31.81-55.09A45,45,0,0,1,328.7,415.37ZM445.37,298.7a45,45,0,1,1-31.82-55.11,45,45,0,0,1,31.82,55.11Z\" transform=\"translate(0 0)\"/><path d=\"M405.79,272.57a15,15,0,1,0,10.61,18.37,15,15,0,0,0-10.61-18.37Z\" transform=\"translate(0 0)\"/><path d=\"M135.82,271c0-5,.18-10,.18-15C136,139.68,128.19,0,91,0S46,139.68,46,256c0,5,.14,10,.18,15Z\" transform=\"translate(0 0)\"/><path d=\"M91,331a88.82,88.82,0,0,1,42.94,11.4c.65-13.46,1.14-27.33,1.49-41.4H46.57c.34,14.07.84,27.94,1.49,41.4A88.82,88.82,0,0,1,91,331Z\" transform=\"translate(0 0)\"/></svg>', '1'),
(118, '<svg id=\"Capa_118\" data-name=\"Capa 118\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 487.79 550.8\"><path d=\"M488.43,197H475.2V133.2a8.13,8.13,0,0,0-.12-1.2,10.6,10.6,0,0,0-2.56-7L366.33,3.69s-.07,0-.09-.07a10.59,10.59,0,0,0-2.15-1.81c-.23-.15-.46-.28-.71-.42A11.4,11.4,0,0,0,361.25.5c-.2-.06-.38-.14-.58-.19A10.73,10.73,0,0,0,358.19,0H97.2A21.62,21.62,0,0,0,75.6,21.6V197H62.38A30.87,30.87,0,0,0,31.5,227.89V388.43A30.88,30.88,0,0,0,62.38,419.3H75.6V529.2a21.62,21.62,0,0,0,21.6,21.6H453.6a21.62,21.62,0,0,0,21.6-21.6V419.3h13.23a30.88,30.88,0,0,0,30.87-30.87V227.89A30.88,30.88,0,0,0,488.43,197ZM97.2,21.6H347.39V132.12a10.81,10.81,0,0,0,10.8,10.8H453.6V197H97.2ZM263.59,318.18c-26.79-9.33-44.27-24.16-44.27-47.6,0-27.53,23-48.59,61-48.59,18.17,0,31.57,3.83,41.15,8.14l-8.13,29.44a76.57,76.57,0,0,0-33.75-7.67c-15.79,0-23.45,7.18-23.45,15.56,0,10.29,9.1,14.84,29.91,22.74C314.56,300.73,328,315.55,328,338.29c0,27-20.8,50-65.08,50-18.42,0-36.61-4.8-45.7-9.82l7.42-30.14c9.81,5,24.89,10,40.44,10,16.75,0,25.6-6.93,25.6-17.46C290.63,330.87,283,325.13,263.59,318.18Zm-190-10.52c0-55,39.26-85.67,88.07-85.67,18.9,0,33.26,3.83,39.72,7.18l-7.42,29a79,79,0,0,0-30.63-6c-28.95,0-51.44,17.47-51.44,53.35,0,32.31,19.14,52.65,51.69,52.65,11,0,23.2-2.38,30.39-5.27l5.49,28.48c-6.7,3.35-21.77,6.94-41.39,6.94C102.3,388.3,73.57,353.6,73.57,307.66Zm380,215.69H97.2v-104H453.6V523.35Zm-61.5-135c-18.42,0-36.6-4.81-45.7-9.82l7.43-30.14c9.8,5,24.88,10,40.42,10,16.76,0,25.61-6.93,25.61-17.45,0-10.05-7.65-15.8-27-22.75-26.8-9.33-44.28-24.16-44.28-47.6,0-27.53,23-48.59,61-48.59,18.19,0,31.59,3.83,41.16,8.14l-8.14,29.44a76.44,76.44,0,0,0-33.74-7.67c-15.79,0-23.44,7.18-23.44,15.56,0,10.29,9.09,14.84,29.91,22.74,28.47,10.53,41.87,25.35,41.87,48.09C457.2,365.33,436.38,388.3,392.1,388.3Z\" transform=\"translate(-31.5)\"/></svg>', '1'),
(119, '<svg id=\"Capa_119\" data-name=\"Capa 119\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 45 58\"><path d=\"M6.5,41V56A2.42,2.42,0,0,0,9,58H49a2.42,2.42,0,0,0,2.46-2V41ZM27,51.57a2.52,2.52,0,0,1-.26,1.19,2,2,0,0,1-.7.78,2.71,2.71,0,0,1-1,.41,5.66,5.66,0,0,1-1.17.12,4.72,4.72,0,0,1-.7-.06,7.25,7.25,0,0,1-.84-.2,8.15,8.15,0,0,1-.83-.29,3.25,3.25,0,0,1-.67-.37l.7-1.11a1.74,1.74,0,0,0,.39.2c.17.07.36.13.56.19l.62.16a2.81,2.81,0,0,0,.62.07,2.19,2.19,0,0,0,1.16-.28,1.13,1.13,0,0,0,.45-1V43.72H27ZM35,52.3a2.7,2.7,0,0,1-.64.9,2.92,2.92,0,0,1-1,.62,3.68,3.68,0,0,1-1.37.23l-.67,0a4.39,4.39,0,0,1-.71-.12,3.86,3.86,0,0,1-.68-.22,2.35,2.35,0,0,1-.55-.35l.29-1.18a2.42,2.42,0,0,0,.48.22l.61.19c.21.06.42.1.63.14a3.11,3.11,0,0,0,.59.06,1.9,1.9,0,0,0,1.28-.39,1.49,1.49,0,0,0,.44-1.16,1.14,1.14,0,0,0-.31-.79,3.56,3.56,0,0,0-.79-.6c-.31-.18-.65-.35-1-.53a6.21,6.21,0,0,1-1-.63,3.39,3.39,0,0,1-.8-.85,2.25,2.25,0,0,1-.31-1.23,2.64,2.64,0,0,1,.25-1.19,2.52,2.52,0,0,1,.66-.88,2.84,2.84,0,0,1,1-.56,3.5,3.5,0,0,1,1.17-.19,6.91,6.91,0,0,1,1.27.12,2.76,2.76,0,0,1,1,.37l-.19.39c-.08.14-.14.28-.21.4s-.12.22-.16.31l-.08.13a.77.77,0,0,1-.19-.11,1.63,1.63,0,0,0-.29-.13,2,2,0,0,0-.51-.1,5.62,5.62,0,0,0-.81,0,1.43,1.43,0,0,0-.52.16,2,2,0,0,0-.43.32,1.62,1.62,0,0,0-.31.43,1.16,1.16,0,0,0-.1.46,1.21,1.21,0,0,0,.31.88,3,3,0,0,0,.78.59,9.51,9.51,0,0,0,1,.49,5.76,5.76,0,0,1,1,.59,2.8,2.8,0,0,1,.79.85,2.58,2.58,0,0,1,.31,1.32A2.62,2.62,0,0,1,35,52.3Z\" transform=\"translate(-6.5)\"/><path d=\"M51.5,39V14A2.28,2.28,0,0,0,51,12.19L39.31.55A1.88,1.88,0,0,0,38,0H9C7.78,0,6.5.92,6.5,2.93V39Zm-22-7a1,1,0,0,1-2,0V29a1,1,0,0,1,2,0Zm8-28.61A.51.51,0,0,1,38.38,3L48.47,13.12a.51.51,0,0,1-.36.88H37.5ZM36.5,23V19a1,1,0,0,0-1-1,1,1,0,0,1,0-2,3,3,0,0,1,3,3v4a2,2,0,0,0,2,2,1,1,0,0,1,0,2,2,2,0,0,0-2,2v4a3,3,0,0,1-3,3,1,1,0,0,1,0-2,1,1,0,0,0,1-1V29a4,4,0,0,1,1.38-3A4,4,0,0,1,36.5,23Zm-8-2A1.5,1.5,0,1,1,27,22.5,1.5,1.5,0,0,1,28.5,21Zm-12,4a2,2,0,0,0,2-2V19a3,3,0,0,1,3-3,1,1,0,0,1,0,2,1,1,0,0,0-1,1v4a4,4,0,0,1-1.38,3,4,4,0,0,1,1.38,3v4a1,1,0,0,0,1,1,1,1,0,0,1,0,2,3,3,0,0,1-3-3V29a2,2,0,0,0-2-2,1,1,0,0,1,0-2Z\" transform=\"translate(-6.5)\"/></svg>', '1'),
(120, '<svg id=\"Layer_120\" data-name=\"Layer 120\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 465.01 512\"><path d=\"M450.5,296a20,20,0,0,1-20-20V120a80.09,80.09,0,0,0-80-80h-184a80.09,80.09,0,0,0-80,80V276c-1,26.54-38.95,26.52-40,0V120A120.13,120.13,0,0,1,166.5,0h184a120.13,120.13,0,0,1,120,120V276A20,20,0,0,1,450.5,296Zm-61-112a20,20,0,0,0-20-20h-34a30,30,0,0,1-30-30V100c-1.05-26.54-38.95-26.52-40,0v34a70.08,70.08,0,0,0,70,70h34A20,20,0,0,0,389.5,184ZM94.26,512c-13.29,0-36.46-3-58-11.36a20,20,0,0,1,14.51-37.28c17.52,6.82,35.59,8.64,43.5,8.64,9.18,0,17.24-7.24,17.24-15.5S101.67,441,90,441c-36.8,0-64.55-23.86-64.55-55.5,0-30.6,25.16-55.5,56.09-55.5,16.6,0,33.77,3.59,54,11.31a20,20,0,0,1-14.23,37.38C100.91,370.9,88.86,370,81.57,370c-8.87,0-16.09,6.95-16.09,15.5C65.48,394.34,76,401,90,401c53.25-.22,82.24,58.61,44.08,95.15A57.85,57.85,0,0,1,94.26,512ZM488.5,445.5A36.54,36.54,0,0,0,452,409H435.5C409,410.06,409,448,435.5,449h12.77c-5.28,32.42-51.73,29.54-52.77-3.5v-50c.32-25.9,32.42-35.6,48.31-16.1a20,20,0,0,0,31.38-24.8A68.24,68.24,0,0,0,422,329a66.57,66.57,0,0,0-66.5,66.5v50a66.5,66.5,0,0,0,133,0ZM297,479c.14-.44,29.64-124.36,29.64-124.36a20,20,0,0,0-38.92-9.26L258.65,467.56c-1,2.92-2.77,4.45-5,4.44s-4-1.61-5-4.52l-27.93-122a20,20,0,1,0-39,8.94S210,478.16,210.16,478.61c6.15,20.2,23.15,33.31,43.3,33.39h.19c20.06,0,37.06-12.94,43.34-33Z\" transform=\"translate(-23.5)\"/></svg>', '1'),
(121, '<svg id=\"Capa_121\" data-name=\"Capa 121\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 13.36 17.53\"><defs></defs><circle class=\"cls-121\" cx=\"6.85\" cy=\"3.04\" r=\"3.03\"/><path class=\"cls-121\" d=\"M11.61,6.35H9.44c.19.18,0,.81-.21,1.16a2.43,2.43,0,0,0-.93,0c-.16-.35-.4-1-.21-1.17H6c-4,.07-3.89,3-3.89,3V16.3h0v.05a1.19,1.19,0,0,0,2.37,0V16.3h0V9.9H5.2v7.36h7.11V9.87h.77V16.3h0a1.19,1.19,0,1,0,2.37,0h0V9.38C15.18,6.3,11.61,6.35,11.61,6.35ZM8.79,14.28,8,13.3,8.4,8.16a2.55,2.55,0,0,1,.73,0v.11l.18,2.49.2,2.55Z\" transform=\"translate(-2.08)\"/></svg>', '1'),
(122, '<svg id=\"Capa_122\" data-name=\"Capa 122\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 32\"><defs></defs><g id=\"user\"><path class=\"cls-122\" d=\"M16,16A12,12,0,0,0,4,28a4,4,0,0,0,4,4H24a4,4,0,0,0,4-4A12,12,0,0,0,16,16Z\" transform=\"translate(-4)\"/><circle class=\"cls-122\" cx=\"12\" cy=\"6\" r=\"6\"/></g></svg>', '1'),
(123, '<svg id=\"Layer_123\" data-name=\"Layer 123\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 511 473.24\"><g id=\"surface1\"><path d=\"M375.17,222.53c-74.36,0-134.84,60.49-134.84,134.84s60.48,134.84,134.84,134.84S510,431.72,510,357.37,449.52,222.53,375.17,222.53ZM431.91,374H374.12a15.58,15.58,0,0,1-15.58-15.58V300.62a15.59,15.59,0,0,1,31.17,0v42.2h42.2a15.59,15.59,0,1,1,0,31.17Z\" transform=\"translate(1 -18.96)\"/><path d=\"M172.76,140.31l31.59-13.22a15.52,15.52,0,0,1,12,0L248,140.31V19h-75.2Z\" transform=\"translate(1 -18.96)\"/><path d=\"M375.17,191.36A165.4,165.4,0,0,1,421.73,198V57.25A38.34,38.34,0,0,0,383.43,19H279.13V163.73a15.58,15.58,0,0,1-21.6,14.38l-47.17-19.75L163.2,178.11a15.59,15.59,0,0,1-21.61-14.38V19H37.29A38.34,38.34,0,0,0-1,57.25V403.39a38.34,38.34,0,0,0,38.29,38.3H232.22a165,165,0,0,1-23.06-84.32C209.16,265.83,283.63,191.36,375.17,191.36Z\" transform=\"translate(1 -18.96)\"/></g></svg>', '1'),
(124, '<svg id=\"Capa_124\" data-name=\"Capa 124\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 511.59\"><path d=\"M0,131.92V496.8a15,15,0,0,0,15,15H497a15,15,0,0,0,15-15V131.92Zm318.4,67.4H423.11a15,15,0,0,1,0,30H318.4a15,15,0,0,1,0-30Zm0,72.36H423.11a15,15,0,0,1,0,30H318.4a15,15,0,0,1,0-30Zm0,72.36H423.11a15,15,0,0,1,0,30H318.4a15,15,0,0,1,0-30ZM73.89,214.32a15,15,0,0,1,15-15H256a15,15,0,0,1,15,15V364.94a15,15,0,0,1-15,15H88.89a15,15,0,0,1-15-15Zm15,202.07H423.11a15,15,0,0,1,0,30H88.89a15,15,0,1,1,0-30Z\" transform=\"translate(0 -0.2)\"/><path d=\"M103.89,229.32H241V349.94H103.89Z\" transform=\"translate(0 -0.2)\"/><path d=\"M497,.2H15a15,15,0,0,0-15,15v86.72H512V15.2A15,15,0,0,0,497,.2ZM388.77,66.06c-8.41,0-14.64-6.87-15-15s7.15-15,15-15c8.41,0,14.63,6.88,15,15S396.61,66.06,388.77,66.06Zm56.46,0c-8.41,0-14.63-6.87-15-15s7.16-15,15-15c8.41,0,14.64,6.88,15,15S453.08,66.06,445.23,66.06Z\" transform=\"translate(0 -0.2)\"/></svg>', '1'),
(125, '<svg id=\"Layer_125\" data-name=\"Layer 125\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 420 369.77\"><path d=\"M90.62,268.23a6.64,6.64,0,0,1-2.05-.32C36.42,251.5,0,201.14,0,145.44,0,78.61,54.26,26.25,123.52,26.25c34.25,0,65.91,12.7,89.14,35.76a6.82,6.82,0,0,1-9.61,9.68c-20.66-20.51-48.9-31.8-79.53-31.8C61.9,39.89,13.64,86.25,13.64,145.45c0,49.8,32.49,94.81,79,109.45a6.82,6.82,0,0,1-2,13.33Z\" transform=\"translate(0 -25.46)\"/><path d=\"M191.7,108.85a13.64,13.64,0,1,1-13.63-13.63A13.63,13.63,0,0,1,191.7,108.85Z\" transform=\"translate(0 -25.46)\"/><path d=\"M82.61,108.85A13.64,13.64,0,1,1,69,95.22,13.64,13.64,0,0,1,82.61,108.85Z\" transform=\"translate(0 -25.46)\"/><path d=\"M326.78,268.22a6.82,6.82,0,0,1-1.88-13.37c47.2-13.59,81.46-59.93,81.46-110.19,0-59.19-48.26-105.56-109.88-105.56S186.6,85.47,186.6,144.66q0,4.92.43,9.77a6.82,6.82,0,0,1-13.59,1.18c-.32-3.65-.48-7.33-.48-11,0-66.84,54.26-119.2,123.52-119.2S420,77.82,420,144.66c0,56.2-38.41,108.06-91.33,123.3A6.84,6.84,0,0,1,326.78,268.22Z\" transform=\"translate(0 -25.46)\"/><path d=\"M207.84,395.24c-67,0-123.52-58.55-123.52-127.84,0-66.84,54.26-119.2,123.52-119.2s123.52,52.36,123.52,119.2C331.36,336.69,274.8,395.24,207.84,395.24Zm0-233.4C146.23,161.84,98,208.21,98,267.4c0,61.9,50.32,114.2,109.88,114.2s109.88-52.3,109.88-114.2C317.72,208.21,269.46,161.84,207.84,161.84Z\" transform=\"translate(0 -25.46)\"/><path d=\"M339.38,203.53a6.82,6.82,0,0,1-6.2-4A41,41,0,0,0,279,179.38a6.82,6.82,0,1,1-5.66-12.4,54.58,54.58,0,0,1,72.22,26.89,6.81,6.81,0,0,1-6.19,9.66Z\" transform=\"translate(0 -25.46)\"/><path d=\"M364.67,108.07A13.64,13.64,0,1,1,351,94.44,13.64,13.64,0,0,1,364.67,108.07Z\" transform=\"translate(0 -25.46)\"/><path d=\"M255.57,108.07a13.64,13.64,0,1,1-13.64-13.63A13.64,13.64,0,0,1,255.57,108.07Z\" transform=\"translate(0 -25.46)\"/><path d=\"M149.3,169.76C144,157.36,134.06,149,122.61,149c-17,0-30.68,18.31-30.68,40.9,0,11.83,3.78,22.45,9.79,29.92A111,111,0,0,1,149.3,169.76Z\" transform=\"translate(0 -25.46)\"/><path d=\"M207.84,346.73a54.73,54.73,0,0,1-49.59-31.8,6.82,6.82,0,0,1,12.4-5.7,40.92,40.92,0,0,0,74,.9,6.82,6.82,0,1,1,12.25,6A54.21,54.21,0,0,1,207.84,346.73Z\" transform=\"translate(0 -25.46)\"/><path d=\"M276,230.81a13.64,13.64,0,1,1-13.64-13.64A13.64,13.64,0,0,1,276,230.81Z\" transform=\"translate(0 -25.46)\"/><path d=\"M166.93,230.81a13.64,13.64,0,1,1-13.64-13.64A13.64,13.64,0,0,1,166.93,230.81Z\" transform=\"translate(0 -25.46)\"/></svg>', '1'),
(126, '<svg id=\"Capa_126\" data-name=\"Capa 126\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 430\"><circle cx=\"387\" cy=\"290\" r=\"25\"/><path d=\"M442,441a55,55,0,0,0-110,0v17.23a124.83,124.83,0,0,0,110,0Z\" transform=\"translate(0 -41)\"/><path d=\"M387,221a124.94,124.94,0,0,0-84.92,216.64,85.06,85.06,0,0,1,43.32-70.72,55,55,0,1,1,83.2,0,85.06,85.06,0,0,1,43.32,70.72A124.94,124.94,0,0,0,387,221Z\" transform=\"translate(0 -41)\"/><path d=\"M50,246a50,50,0,1,1,50-50A50.06,50.06,0,0,1,50,246Z\" transform=\"translate(0 -41)\"/><path d=\"M119.37,156.23a79.34,79.34,0,0,1,9.81,28.44l131.13-56.52A69.93,69.93,0,0,1,245.22,102Z\" transform=\"translate(0 -41)\"/><path d=\"M129.18,207.33a79.34,79.34,0,0,1-9.81,28.44L242.86,289a154.85,154.85,0,0,1,13.76-26.74Z\" transform=\"translate(0 -41)\"/><path d=\"M312,121a40,40,0,1,1,40-40A40,40,0,0,1,312,121Z\" transform=\"translate(0 -41)\"/></svg>', '1'),
(127, '<svg id=\"Layer_127\" data-name=\"Layer 127\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 411.57\"><path d=\"M120,224c7.26,0,13.17,4.94,13.17,11a10,10,0,0,0,20,0c0-13.85-9.75-25.6-23.17-29.58V204a10,10,0,0,0-20,0v1.39c-13.42,4-23.17,15.73-23.17,29.58,0,17.11,14.88,31,33.17,31,7.26,0,13.17,4.95,13.17,11s-5.91,11-13.17,11-13.17-4.94-13.17-11a10,10,0,0,0-20,0c0,13.85,9.75,25.6,23.17,29.58V308a10,10,0,0,0,20,0v-1.39c13.42-4,23.17-15.73,23.17-29.58,0-17.11-14.88-31-33.17-31-7.26,0-13.17-4.95-13.17-11s5.91-11,13.17-11Z\" transform=\"translate(0 -50.21)\"/><path d=\"M240,256a119.53,119.53,0,0,0-8.34-44,10,10,0,0,0-18.6,7.34,99.89,99.89,0,1,1-56.57-56.44,10,10,0,1,0,7.3-18.62A120,120,0,1,0,240,256Z\" transform=\"translate(0 -50.21)\"/><path d=\"M392,224c7.26,0,13.17,4.94,13.17,11a10,10,0,0,0,20,0c0-13.85-9.75-25.6-23.17-29.58V204a10,10,0,0,0-20,0v1.39c-13.42,4-23.17,15.73-23.17,29.58,0,17.11,14.88,31,33.17,31,7.26,0,13.17,4.95,13.17,11s-5.91,11-13.17,11-13.17-4.94-13.17-11a10,10,0,0,0-20,0c0,13.85,9.75,25.6,23.17,29.58V308a10,10,0,0,0,20,0v-1.39c13.42-4,23.17-15.73,23.17-29.58,0-17.11-14.88-31-33.17-31-7.26,0-13.17-4.95-13.17-11s5.91-11,13.17-11Z\" transform=\"translate(0 -50.21)\"/><path d=\"M392,136A120,120,0,1,0,512,256,120.13,120.13,0,0,0,392,136Zm0,220A100,100,0,1,1,492,256,100.11,100.11,0,0,1,392,356Z\" transform=\"translate(0 -50.21)\"/><path d=\"M341.62,402.56a10,10,0,0,0-14.15,14.14l4,4H156.38a10,10,0,1,0,0,20h175.1l-4,4a10,10,0,0,0,14.15,14.15l21.07-21.08a10,10,0,0,0,0-14.14Z\" transform=\"translate(0 -50.21)\"/><path d=\"M170.38,109.44A10,10,0,0,0,184.53,95.3l-4-4h175.1a10,10,0,0,0,0-20H180.52l4-4a10,10,0,0,0-14.15-14.15L149.31,74.22a10,10,0,0,0,0,14.14Z\" transform=\"translate(0 -50.21)\"/><path d=\"M190.71,171.15a10,10,0,1,0,7.07-2.93A10.08,10.08,0,0,0,190.71,171.15Z\" transform=\"translate(0 -50.21)\"/></svg>', '1'),
(128, '<svg id=\"Capa_128\" data-name=\"Capa 128\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 405.1 512\"><path d=\"M450,73.22a77.87,77.87,0,0,1-66.3-64.67A10,10,0,0,0,373.77,0H138.23a10.06,10.06,0,0,0-9.89,8.55A77.85,77.85,0,0,1,62.05,73.22a10,10,0,0,0-8.6,10V373.58a85.3,85.3,0,0,0,50,77.51l117.44,53.3a85.09,85.09,0,0,0,70.31,0l117.48-53.32a85.25,85.25,0,0,0,49.92-77.47V83.2a10,10,0,0,0-8.6-10ZM409.83,373.61a36.43,36.43,0,0,1-21.33,33.11L271,460A36.33,36.33,0,0,1,241,460L123.53,406.73a36.47,36.47,0,0,1-21.36-33.15V121.5a15,15,0,0,1,8-13.28A126.67,126.67,0,0,0,162.7,56.53a15,15,0,0,1,13.17-7.82H336.13a15,15,0,0,1,13.17,7.82,126.73,126.73,0,0,0,52.51,51.69,15,15,0,0,1,8,13.28V373.61Z\" transform=\"translate(-53.45 0)\"/><path d=\"M327.52,78.71h-143a156.59,156.59,0,0,1-52.31,51.56V373.58a6.42,6.42,0,0,0,3.76,5.84l117.44,53.3a6.29,6.29,0,0,0,5.25,0L376.1,379.4a6.36,6.36,0,0,0,3.73-5.79V130.27a156.5,156.5,0,0,1-52.31-51.56Zm-2.31,156.08L237.5,301.13a15,15,0,0,1-20.86-2.71L184,256.82a15,15,0,0,1,23.62-18.51l23.51,30,76-57.44a15,15,0,0,1,18.1,23.92Z\" transform=\"translate(-53.45 0)\"/></svg>', '1'),
(129, '<svg id=\"Layer_128\" data-name=\"Layer 128\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 490.54 512\"><path d=\"M377,233.68q1.28,0,2.55,0h.22q7.17.14,14.21.91V60.41A60.41,60.41,0,0,0,333.53,0H71.41A60.41,60.41,0,0,0,11,60.41V407.88a60.41,60.41,0,0,0,60.41,60.41h175a152.27,152.27,0,0,1-23-81C223.39,302.61,292.28,233.68,377,233.68Zm-149.3-135h89.52a15,15,0,1,1,0,30H227.66a15,15,0,0,1,0-30Zm0,60h89.52a15,15,0,0,1,0,30H227.66a15,15,0,1,1,0-30Zm-50.38,211H87.76a15,15,0,1,1,0-30h89.52a15,15,0,1,1,0,30Zm0-61H87.76a15,15,0,1,1,0-30h89.52a15,15,0,0,1,0,30Zm0-60H87.76a15,15,0,1,1,0-30h89.52a15,15,0,0,1,0,30Zm0-60H87.76a15,15,0,1,1,0-30h89.52a15,15,0,0,1,0,30Zm0-60H87.76a15,15,0,0,1,0-30h89.52a15,15,0,1,1,0,30Zm35.38,105a15,15,0,0,1,15-15h43.11a15,15,0,0,1,0,30H227.66A15,15,0,0,1,212.66,233.64Z\" transform=\"translate(-11)\"/><path d=\"M465.79,299.94a123.8,123.8,0,0,0-86.59-37.23H379l-2,0c-68.69,0-124.57,55.92-124.57,124.66A124.62,124.62,0,0,0,377,512c68.69,0,124.58-55.92,124.58-124.66A124,124,0,0,0,465.79,299.94ZM419.46,408a15,15,0,1,1-21,21.43L377,408.33l-21.51,21.06a15,15,0,1,1-21-21.43l21.06-20.62-21.06-20.62a15,15,0,1,1,21-21.44L377,366.35l21.51-21.07a15,15,0,1,1,21,21.44L398.4,387.34Z\" transform=\"translate(-11)\"/></svg>', '1'),
(130, '<svg id=\"Layer_130\" data-name=\"Layer 130\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 391.93 512\"><path d=\"M157,453.38H129.67a10,10,0,1,1,0-20H157a10,10,0,0,1,0,20Z\" transform=\"translate(-60.04 0)\"/><path d=\"M232.11,453.38H204.78a10,10,0,1,1,0-20h27.33a10,10,0,0,1,0,20Z\" transform=\"translate(-60.04 0)\"/><path d=\"M307.22,453.38H279.89a10,10,0,0,1,0-20h27.33a10,10,0,0,1,0,20Z\" transform=\"translate(-60.04 0)\"/><path d=\"M382.33,453.38H355a10,10,0,0,1,0-20h27.33a10,10,0,1,1,0,20Z\" transform=\"translate(-60.04 0)\"/><path d=\"M255.82,512a10,10,0,1,1,9.8-8.05A10.11,10.11,0,0,1,255.82,512Z\" transform=\"translate(-60.04 0)\"/><path d=\"M442,512H301.21a10,10,0,0,1,0-20H432V394.76H80V492H210.79a10,10,0,0,1,0,20H70a10,10,0,0,1-10-10V384.76a10,10,0,0,1,10-10H442a10,10,0,0,1,10,10V502A10,10,0,0,1,442,512Z\" transform=\"translate(-60.04 0)\"/><path d=\"M314.62,173.43a96.64,96.64,0,1,0-117.24,0A84.84,84.84,0,0,0,117.67,258V317.6a10,10,0,0,0,10,10H384.33a10,10,0,0,0,10-10V258a84.84,84.84,0,0,0-79.71-84.59ZM179.36,96.64A76.64,76.64,0,1,1,256,173.28a76.73,76.73,0,0,1-76.64-76.64Zm195,211H137.67V258a64.81,64.81,0,0,1,64.73-64.74H309.6A64.81,64.81,0,0,1,374.33,258V307.6Z\" transform=\"translate(-60.04 0)\"/></svg>', '1'),
(131, '<svg id=\"Capa_131\" data-name=\"Capa 131\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M446,35V30A30,30,0,0,0,416,0H30A30,30,0,0,0,0,30V303a30,30,0,0,0,30,30h6V75A40,40,0,0,1,76,35Z\"/><path d=\"M482,65H96A30,30,0,0,0,66,95v49h63V127a15,15,0,0,1,15-15H256a15,15,0,0,1,15,15v17H512V95A30,30,0,0,0,482,65Z\"/><path d=\"M271,191a15,15,0,0,1-15,15H144a15,15,0,0,1-15-15V174H66V368a30,30,0,0,0,30,30H260.43c-3-18.21-2.43-28.15-2.43-63H228a15,15,0,0,1,0-30h30v-9.4a45.05,45.05,0,0,1,45-45,105.56,105.56,0,0,0,68.2-25,45,45,0,0,1,57.61,0c20.6,17,44,25,68.19,25a44.63,44.63,0,0,1,15,2.58V174H271ZM164,335H132a15,15,0,0,1,0-30h32a15,15,0,0,1,0,30Z\"/><path d=\"M159,142h82v34H159Z\"/><path d=\"M497,280.6c-30.72,0-61.11-10.21-87.4-32a15,15,0,0,0-19.2,0c-26.32,21.82-56.71,32-87.4,32a15,15,0,0,0-15,15v72.8a148.73,148.73,0,0,0,107.88,143,15,15,0,0,0,8.24,0A148.75,148.75,0,0,0,512,368.4V295.6a15,15,0,0,0-15-15Zm-24.39,69.61-62,62a15,15,0,0,1-21.22,0l-32-32A15,15,0,0,1,378.61,359L400,380.39,451.39,329a15,15,0,0,1,21.22,21.22Z\"/></svg>', '1'),
(132, '<svg id=\"Layer_132\" data-name=\"Layer 132\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 459.56\"><g id=\"surface1\"><path d=\"M511.84,412l-29-223.47a19.33,19.33,0,0,0-21.64-16.65l-10.69,1.38a19.32,19.32,0,0,0-16.66,21.64l.57,4.34-14.17,7.16A211.28,211.28,0,0,0,211.25,26h0A211.26,211.26,0,0,0,33.56,123.11,7.49,7.49,0,0,0,32.35,125a210.78,210.78,0,0,0,8.32,236.67,7.63,7.63,0,0,0,1.27,1.74A211.09,211.09,0,0,0,211.25,448.5h0a214,214,0,0,0,26-1.61l2,14a28.95,28.95,0,0,0,32.92,24.33L287,483a15.89,15.89,0,0,0,13.42-17.88V465c-1.35-8.95-5.89-13.24-9.2-16.37-2.64-2.49-4.09-3.86-4.67-7.68a9.72,9.72,0,0,1,1.11-6.72,210.1,210.1,0,0,0,65.77-40.76l109.23,23.21.21,1.67A19.31,19.31,0,0,0,484.48,435l10.7-1.39A19.33,19.33,0,0,0,511.84,412ZM406.06,213.55,374,229.75H318.33A393.78,393.78,0,0,0,308.82,149a651.53,651.53,0,0,0,70.91-12.42A195.05,195.05,0,0,1,406.06,213.55Zm-61.78,31.2-26.4,13.33c.21-4.43.37-8.87.45-13.33ZM343.7,92.44A197.09,197.09,0,0,1,371,123.22a647.67,647.67,0,0,1-65.89,11.12,261.67,261.67,0,0,0-16.64-44.46c-8.85-18.1-19.11-32.62-30.34-43.22A195.61,195.61,0,0,1,343.7,92.44ZM218.75,41.68c20.86,3.71,40.59,22.83,56.23,54.79A245.52,245.52,0,0,1,290,136c-23.13,2.26-47,3.51-71.23,3.73Zm0,113c25.54-.22,50.71-1.59,75.07-4.05a377.19,377.19,0,0,1,9.51,79.08H218.75Zm0,90h84.58c-.13,7.09-.45,14.17-.94,21.16l-21.11,10.65-62.53,8.12ZM164.37,46.67c-11.23,10.6-21.48,25.12-30.32,43.21a261.68,261.68,0,0,0-16.65,44.46,646.19,646.19,0,0,1-65.79-11.1A196.78,196.78,0,0,1,164.37,46.67ZM42.84,136.6A653.13,653.13,0,0,0,113.68,149a393,393,0,0,0-9.51,80.75h-89A194.9,194.9,0,0,1,42.84,136.6ZM15.16,244.75h89c.58,33.21,5,65.33,12.87,94a665.75,665.75,0,0,0-66.48,11.06A195.09,195.09,0,0,1,15.16,244.75ZM60.66,363c19.41-4,39.72-7.26,60.77-9.64a259.18,259.18,0,0,0,9.86,25.46c2.87,6.32,6,12.37,9.2,18,2.83,4.88,5.82,9.51,8.89,13.76a122.28,122.28,0,0,0,15.05,17.35A196.64,196.64,0,0,1,60.66,363Zm143.09,69.87c-18.49-3.31-32.95-18.3-42.21-31.11-2.78-3.85-5.49-8-8.07-12.5-3-5.16-5.85-10.76-8.51-16.63-3-6.6-5.74-13.56-8.23-20.83q14.16-1.31,28.72-2.11L166.93,361a19.51,19.51,0,0,0,19.31,17,19.76,19.76,0,0,0,2.53-.16l12-1.56a23.73,23.73,0,0,0,3,4.8ZM178.58,324.08a4.49,4.49,0,0,1,3-1.72l10.79-1.4,5.27,40.6L186.83,363a4.5,4.5,0,0,1-5-3.87l-4.11-31.7A4.46,4.46,0,0,1,178.58,324.08Zm25.17-35.77A24.42,24.42,0,0,0,191.37,306l-11.74,1.53a19.5,19.5,0,0,0-16.82,21.83l.71,5.4q-15.92.9-31.37,2.39c-7.9-27.92-12.39-59.54-13-92.36h84.58v43.56Zm0-58.56H119.17a376.53,376.53,0,0,1,9.51-79.08c24.36,2.46,49.53,3.82,75.07,4.05Zm0-90c-24.23-.22-48.11-1.47-71.23-3.73a245.52,245.52,0,0,1,15-39.52c15.63-32,35.37-51.08,56.23-54.79Zm15,293.1V389.87a24.37,24.37,0,0,0,7.14.11l3.22-.42,5.33,37.4A49.2,49.2,0,0,1,218.75,432.82Zm66.83,34.48a.89.89,0,0,1-.81.88L270,470.41a14,14,0,0,1-15.85-11.67L244,387.63l2.21-.28,17.65-2.3a26.57,26.57,0,0,0,.18,7c1.34,9,5.88,13.24,9.2,16.38,2.64,2.48,4.09,3.85,4.67,7.68s-.42,5.56-2.21,8.71c-2.25,4-5.33,9.4-4,18.35s5.89,13.24,9.2,16.37c2.64,2.49,4.09,3.86,4.67,7.69ZM293,415.73c-.06-.6-.13-1.21-.22-1.84-1.34-9-5.89-13.25-9.2-16.38-2.64-2.49-4.1-3.86-4.67-7.69a9.84,9.84,0,0,1,1.19-6.87l14.8-1.92L335,389.56A195.19,195.19,0,0,1,293,415.73Zm203.16,1.38a4.31,4.31,0,0,1-2.86,1.64l-10.7,1.39a4.31,4.31,0,0,1-4.82-3.72L457.46,260.31a7.5,7.5,0,0,0-14.88,1.94l18,138.66L301.91,367.19l-.85-6.51a7.5,7.5,0,1,0-14.87,1.93l.56,4.34L224,375.11a9.37,9.37,0,0,1-10.49-8h0l0-.2-7.18-55.28-.09-.7a9.38,9.38,0,0,1,8.09-10.5l62.79-8.15,4.59,35.39a7.5,7.5,0,0,0,7.43,6.53,8.18,8.18,0,0,0,1-.06,7.51,7.51,0,0,0,6.47-8.4l-4.88-37.56L436.45,215l1.65,12.71A7.5,7.5,0,0,0,453,225.79L448.71,193a4.3,4.3,0,0,1,3.72-4.83l10.69-1.39a4.31,4.31,0,0,1,4.82,3.72l29,223.47A4.27,4.27,0,0,1,496.11,417.11Z\" transform=\"translate(0 -26)\"/></g></svg>', '1'),
(133, '<svg id=\"Capa_133\" enable-background=\"new 0 0 512 512\" height=\"512\" viewBox=\"0 0 512 512\" width=\"512\" xmlns=\"http://www.w3.org/2000/svg\"><g><path d=\"m429.419 173.419h-249.485l-56.063 157.329h305.548l49.55-8.684c20.056-15.066 33.031-39.048 33.031-66.064 0-45.608-36.973-82.581-82.581-82.581z\" fill=\"#ff5f7a\"/><path d=\"m233.214 322.065c-16.796 0-28.456-16.27-23.536-32.329 3.268-10.668 5.032-21.992 5.032-33.735 0-32.369-13.339-61.594-34.776-82.581h-56.063v165.161h305.548c18.55 0 35.764-6.191 49.55-16.516z\" fill=\"#fb4455\"/><path d=\"m198.194 256c0 24.212-8.683 46.396-23.105 63.608-18.178 21.694-45.474 30.254-75.991 30.254-54.73 0-91.593-39.132-91.593-93.862 0-30.526 6.298-57.827 28.002-76.006 17.209-14.414 39.387-23.091 63.591-23.091 54.728 0 99.096 44.367 99.096 99.097z\" fill=\"#ffdbde\"/><g fill=\"#fff\"><path d=\"m279.637 281.435c-.016 0-.032 0-.048 0-5.274.028-10.927.048-15.331.056v-59.241c0-4.56-3.694-8.258-8.258-8.258s-8.258 3.698-8.258 8.258v67.444c0 4.032 2.911 7.472 6.887 8.145.589.097 1.04.173 8.46.173 3.613 0 8.871-.02 16.597-.06 4.556-.024 8.234-3.742 8.21-8.302-.025-4.545-3.719-8.215-8.259-8.215z\"/><path d=\"m310.073 213.992c-4.565 0-8.258 3.698-8.258 8.258v67.5c0 4.56 3.694 8.258 8.258 8.258s8.258-3.698 8.258-8.258v-67.5c0-4.56-3.694-8.258-8.258-8.258z\"/><path d=\"m444.492 281.181h-19.919v-16.923h17.847c4.565 0 8.258-3.698 8.258-8.258s-3.694-8.258-8.258-8.258h-17.847v-16.923h19.919c4.565 0 8.258-3.698 8.258-8.258s-3.694-8.258-8.258-8.258h-28.177c-4.565 0-8.258 3.698-8.258 8.258v66.879c0 4.56 3.694 8.258 8.258 8.258h28.177c4.565 0 8.258-3.698 8.258-8.258s-3.694-8.259-8.258-8.259z\"/><path d=\"m390.919 214.536c-4.331-1.444-9 .903-10.444 5.238l-15.774 47.512-16.581-47.746c-1.5-4.306-6.194-6.589-10.508-5.093-4.306 1.496-6.589 6.202-5.089 10.512l23.097 66.536c1.387 3.903 5.081 6.512 9.218 6.512h.056c4.161-.024 7.855-2.681 9.226-6.69l22.032-66.343c1.437-4.325-.91-8.998-5.233-10.438z\"/></g><path d=\"m140.137 248.468-57.556-28.778-10.571 5.426v65.666l69.937-32.359z\" fill=\"#ff7e92\"/><path d=\"m111.484 342.71c-54.73 0-99.097-44.367-99.097-99.097 0-24.211 8.699-46.407 23.119-63.618-21.696 18.178-35.506 45.486-35.506 76.005 0 54.729 44.367 99.097 99.097 99.097 30.519 0 57.813-13.793 75.991-35.489-17.211 14.42-39.393 23.102-63.604 23.102z\" fill=\"#ffb9c0\"/><path d=\"m143.97 250.385-3.833-1.916-45.317 22.658c-5.623 2.811-12.24-1.278-12.24-7.565v-43.872l-4.276-2.138c-5.623-2.812-12.24 1.277-12.24 7.565v65.665c0 6.287 6.616 10.376 12.24 7.565l65.665-32.833c6.235-3.117 6.235-12.012.001-15.129z\" fill=\"#ff5f7a\"/></g></svg>', '1'),
(134, '<svg height=\"512\" viewBox=\"0 0 64 64\" width=\"512\" xmlns=\"http://www.w3.org/2000/svg\"><circle cx=\"23\" cy=\"30\" fill=\"#f2ca04\" r=\"20\"/><path d=\"m32 40a19.993 19.993 0 0 1 -18.494-27.6 20 20 0 1 0 27.988 25.2 19.905 19.905 0 0 1 -9.494 2.4z\" fill=\"#f5af0b\"/><circle cx=\"23\" cy=\"30\" fill=\"#f5af0b\" r=\"16\"/><path d=\"m23 14a15.99 15.99 0 0 0 -14.146 8.534 15.991 15.991 0 0 1 25.292 18.932 15.991 15.991 0 0 0 -11.146-27.466z\" fill=\"#f28704\"/><path d=\"m34.314 41.314a16 16 0 0 1 -22.628-22.628z\" fill=\"#f28704\"/><path d=\"m49 31a1 1 0 0 0 2 0 4 4 0 0 0 -4-4 1 1 0 0 0 0 2 2 2 0 0 1 2 2z\" fill=\"#f2ca04\"/><path d=\"m47 23a1 1 0 0 0 0 2 6.006 6.006 0 0 1 6 6 1 1 0 0 0 2 0 8.009 8.009 0 0 0 -8-8z\" fill=\"#f2ca04\"/><path d=\"m47 19a1 1 0 0 0 0 2 10.011 10.011 0 0 1 10 10 1 1 0 0 0 2 0 12.013 12.013 0 0 0 -12-12z\" fill=\"#f2ca04\"/><path d=\"m23 17a1 1 0 0 0 -1 1v11h-7a1 1 0 0 0 0 2h8a1 1 0 0 0 1-1v-12a1 1 0 0 0 -1-1z\" fill=\"#bf037d\"/><path d=\"m31 16.144a1 1 0 0 0 1.366-.367l1.481-2.565q-.839-.543-1.732-1l-1.481 2.565a1 1 0 0 0 .366 1.367z\" fill=\"#f48bbc\"/><path d=\"m15 43.856a1 1 0 0 0 -1.366.367l-1.481 2.565q.838.543 1.732 1l1.481-2.565a1 1 0 0 0 -.366-1.367z\" fill=\"#f24193\"/><path d=\"m36.856 22a1 1 0 0 0 1.367.366l2.565-1.481q-.459-.893-1-1.732l-2.565 1.481a1 1 0 0 0 -.367 1.366z\" fill=\"#f48bbc\"/><path d=\"m9.144 38a1 1 0 0 0 -1.367-.366l-2.565 1.481q.459.892 1 1.732l2.565-1.481a1 1 0 0 0 .367-1.366z\" fill=\"#f24193\"/><path d=\"m42.95 29h-2.95a1 1 0 0 0 0 2h2.95c.016-.333.05-.662.05-1s-.034-.667-.05-1z\" fill=\"#f48bbc\"/><path d=\"m7 30a1 1 0 0 0 -1-1h-2.95c-.016.333-.05.662-.05 1s.034.667.05 1h2.95a1 1 0 0 0 1-1z\" fill=\"#f24193\"/><path d=\"m8.276 22.5a1 1 0 0 0 .5-1.866l-2.564-1.481q-.543.839-1 1.732l2.565 1.481a1 1 0 0 0 .499.134z\" fill=\"#f24193\"/><path d=\"m14.5 16.277a1 1 0 0 0 .865-1.5l-1.481-2.565q-.893.459-1.732 1l1.481 2.565a1 1 0 0 0 .867.5z\" fill=\"#f48bbc\"/><path d=\"m23 14a1 1 0 0 0 1-1v-2.95c-.333-.016-.662-.05-1-.05s-.667.034-1 .05v2.95a1 1 0 0 0 1 1z\" fill=\"#f48bbc\"/><path d=\"m12.82 14.367a19.729 19.729 0 0 1 .67-1.926c-.454.245-.906.492-1.337.771z\" fill=\"#f24193\"/><path d=\"m24 18.524a15.879 15.879 0 0 0 -2-.386v10.862l1.707 1.707a1 1 0 0 0 .293-.707z\" fill=\"#f24193\"/><path d=\"m19 36h42v18h-42z\" fill=\"#e5c1f4\"/><path d=\"m26 37v-1h-7v18h42v-5h-23a12 12 0 0 1 -12-12z\" fill=\"#cd76f2\"/><path d=\"m30 48h-3v-7a1 1 0 0 0 -2 0v8a1 1 0 0 0 1 1h4a1 1 0 0 0 0-2z\" fill=\"#f24193\"/><path d=\"m34 40a1 1 0 0 0 -1 1v8a1 1 0 0 0 2 0v-8a1 1 0 0 0 -1-1z\" fill=\"#f24193\"/><path d=\"m44.351 40.063a1 1 0 0 0 -1.288.586l-2.063 5.503-2.063-5.5a1 1 0 1 0 -1.874.7l3 8a1 1 0 0 0 1.874 0l3-8a1 1 0 0 0 -.586-1.289z\" fill=\"#f24193\"/><path d=\"m52 46a1 1 0 0 0 0-2h-3v-2h3a1 1 0 0 0 0-2h-4a1 1 0 0 0 -1 1v8a1 1 0 0 0 1 1h4a1 1 0 0 0 0-2h-3v-2z\" fill=\"#f24193\"/><g fill=\"#bf037d\"><path d=\"m25 41v8a1 1 0 0 0 1 1h4a1 1 0 0 0 0-2h-3v-6.211a11.828 11.828 0 0 1 -.581-1.691.99.99 0 0 0 -1.419.902z\"/><path d=\"m34 50a1 1 0 0 0 1-1v-.393a11.894 11.894 0 0 1 -2-.709v1.102a1 1 0 0 0 1 1z\"/><path d=\"m41 50a1 1 0 0 0 .937-.649l.131-.351h-2.136l.131.351a1 1 0 0 0 .937.649z\"/><path d=\"m48 50h4a1 1 0 0 0 1-1h-6a1 1 0 0 0 1 1z\"/></g></svg>', '1'),
(136, '<svg id=\"Capa_136\" enable-background=\"new 0 0 511.261 511.261\" height=\"512\" viewBox=\"0 0 511.261 511.261\" width=\"512\" xmlns=\"http://www.w3.org/2000/svg\"><g><ellipse cx=\"288.43\" cy=\"156.63\" fill=\"#f66\" rx=\"156.63\" ry=\"156.63\" transform=\"matrix(.707 -.707 .707 .707 -26.275 249.827)\"/><path d=\"m289.825.018v313.225c85.725-.754 155.235-70.713 155.235-156.612 0-85.901-69.51-155.86-155.235-156.613z\" fill=\"#e62e6b\"/><g><path d=\"m244.044 132.706c10.087 0 19.751-8.87 19.284-19.284-.468-10.448-8.473-19.284-19.284-19.284-10.087 0-19.751 8.87-19.284 19.284.468 10.448 8.473 19.284 19.284 19.284z\" fill=\"#ffe666\"/></g><g><path d=\"m332.816 220.506c10.087 0 19.751-8.87 19.284-19.284-.468-10.448-8.473-19.284-19.284-19.284-10.087 0-19.751 8.87-19.284 19.284.467 10.448 8.473 19.284 19.284 19.284z\" fill=\"#ffe666\"/></g><path d=\"m328.162 63.499c-7.639-3.205-16.429.387-19.636 8.026l-65.73 156.62c-3.206 7.639.388 16.43 8.026 19.636 1.896.796 3.863 1.173 5.798 1.173 5.861 0 11.428-3.457 13.838-9.199l65.73-156.62c3.207-7.639-.387-16.43-8.026-19.636z\" fill=\"#ffe666\"/><path d=\"m328.162 63.499c-7.639-3.205-16.429.387-19.636 8.026l-18.701 44.561v77.521l46.364-110.473c3.206-7.638-.388-16.429-8.027-19.635z\" fill=\"#fabe2c\"/><path d=\"m93.605 363.837h-63.133c-8.284 0-15 6.716-15 15v117.424c0 8.284 6.716 15 15 15h48.133l30-15v-117.424c0-8.284-6.716-15-15-15z\" fill=\"#9ee2f8\"/><g><path d=\"m491.395 362.813c-28.074-28.075-73.756-28.076-101.829 0l-44.87 44.869h.001l-18.521 18.521c-7.35 7.35-17.319 11.479-27.714 11.479h-109.609c-8.077 0-15.027-6.207-15.407-14.275-.406-8.614 6.458-15.725 14.983-15.725h105.373c23.397 0 43.498-19.125 43.882-42.518.007-.406.01-.812.01-1.219.001-8.287-6.713-15.012-15-15.012h-49.806c-16.277 0-31.908-7.384-48.457-15.202-17.358-8.199-35.307-16.679-56.294-18.074-18.355-1.223-36.754.787-54.685 5.965-20.456 5.906-34.785 24.883-34.847 46.192v143.446h258.04c17.743 0 34.425-6.91 46.974-19.457l107.777-107.777c5.857-5.857 5.857-15.355-.001-21.213z\" fill=\"#ffbda9\"/></g><g fill=\"#f9a58d\"><path d=\"m389.566 362.813-44.87 44.869h.001l-18.521 18.521c-7.35 7.35-17.319 11.479-27.714 11.479h-10.052v73.578h48.234c17.743 0 34.425-6.91 46.974-19.457l107.777-107.777c5.858-5.857 5.858-15.355 0-21.213-28.074-28.075-73.756-28.076-101.829 0z\"/><path d=\"m337.685 365.165c.007-.406.01-.812.01-1.219.001-8.287-6.713-15.012-15-15.012h-34.284v58.749h5.392c23.707 0 43.492-18.814 43.882-42.518z\"/></g></g></svg>', '1'),
(137, '<svg height=\"512pt\" viewBox=\"0 -25 512 512\" width=\"512pt\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"m351 253h-51c-11.046875 0-20-8.953125-20-20s8.953125-20 20-20h23.351562c-15.082031-25.140625-42.308593-41-72.8125-41-31.21875 0-59.871093 17.066406-74.773437 44.539062-5.269531 9.707032-17.410156 13.308594-27.117187 8.039063-9.710938-5.265625-13.308594-17.40625-8.042969-27.113281 21.90625-40.378906 64.03125-65.464844 109.929687-65.464844 27.335938 0 53.304688 8.664062 75.09375 25.0625 9.664063 7.269531 18.203125 15.964844 25.371094 25.65625v-19.71875c0-11.046875 8.953125-20 20-20s20 8.953125 20 20v50c0 22.054688-17.945312 40-40 40zm141 49c11.046875 0 20-8.953125 20-20v-130c0-44.113281-35.886719-80-80-80h-30.359375c-8.566406 0-16.175781-5.449219-18.9375-13.554688l-6.050781-17.78125c-8.277344-24.320312-31.101563-40.664062-56.796875-40.664062h-127.769531c-25.199219 0-47.878907 15.921875-56.433594 39.621094l-6.925782 19.171875c-2.851562 7.898437-10.410156 13.207031-18.8125 13.207031h-29.914062c-44.113281 0-80 35.886719-80 80v230c0 44.113281 35.886719 80 80 80h352c44.113281 0 80-35.886719 80-80 0-11.046875-8.953125-20-20-20s-20 8.953125-20 20c0 22.054688-17.945312 40-40 40h-352c-22.054688 0-40-17.945312-40-40v-230c0-22.054688 17.945312-40 40-40h29.914062c25.199219 0 47.878907-15.921875 56.433594-39.621094l6.925782-19.171875c2.851562-7.898437 10.410156-13.207031 18.8125-13.207031h127.769531c8.5625 0 16.171875 5.445312 18.933593 13.554688l6.050782 17.78125c8.28125 24.320312 31.105468 40.664062 56.800781 40.664062h30.359375c22.054688 0 40 17.945312 40 40v130c0 11.046875 8.953125 20 20 20zm-263-17c0-11.046875-8.953125-20-20-20h-51c-22.054688 0-40 17.945312-40 40v50c0 11.046875 8.953125 20 20 20s20-8.953125 20-20v-19.96875c7.207031 9.792969 15.808594 18.574219 25.554688 25.90625 21.792968 16.398438 47.757812 25.0625 75.089843 25.0625 45.902344 0 88.027344-25.085938 109.933594-65.460938 5.269531-9.710937 1.667969-21.851562-8.042969-27.117187-9.707031-5.269531-21.847656-1.667969-27.113281 8.042969-14.90625 27.46875-43.558594 44.535156-74.777344 44.535156-30.503906 0-57.730469-15.859375-72.8125-41h23.167969c11.046875 0 20-8.953125 20-20zm0 0\"/></svg>', '1'),
(138, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?><svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 512 512\" style=\"enable-background:new 0 0 512 512;\" xml:space=\"preserve\"> <g> <g> <g> <path d=\"M496.375,139.958L372.042,15.625C361.958,5.552,348.583,0,334.333,0H177.667c-14.25,0-27.625,5.552-37.708,15.625 L15.625,139.958C5.542,150.031,0,163.427,0,177.677v156.646c0,14.25,5.542,27.646,15.625,37.719l124.333,124.333 C150.042,506.448,163.417,512,177.667,512h156.667c14.25,0,27.625-5.552,37.708-15.625l124.333-124.333 C506.458,361.969,512,348.573,512,334.323V177.677C512,163.427,506.458,150.031,496.375,139.958z M490.667,334.323 c0,8.552-3.333,16.583-9.375,22.635L356.958,481.292c-5.958,5.958-14.208,9.375-22.625,9.375H177.667 c-8.417,0-16.667-3.417-22.625-9.375L30.708,356.958c-6.042-6.052-9.375-14.083-9.375-22.635V177.677 c0-8.552,3.333-16.583,9.375-22.635L155.042,30.708c5.958-5.958,14.208-9.375,22.625-9.375h156.667 c8.417,0,16.667,3.417,22.625,9.375l124.333,124.333c6.042,6.052,9.375,14.083,9.375,22.635V334.323z\" /> <path d=\"M400.583,210.74c-10.896-7.26-23.896-9.76-36.75-6.99c-12.792,2.75-23.667,10.385-30.771,21.802L320,248.177V117.333 c0-17.646-14.354-32-32-32c-4.212,0-8.215,0.863-11.901,2.349C272.41,74.082,260.085,64,245.333,64 c-11.365,0-21.299,5.997-26.978,14.949c-4.656-2.647-9.96-4.283-15.689-4.283c-17.646,0-32,14.354-32,32v23.298 C167.314,128.77,163.758,128,160,128c-17.646,0-32,14.354-32,32v167.24C128,393.823,182.167,448,248.75,448 c53.729,0,100.229-34.583,115.708-86.146l40.458-139.26C406.208,218.125,404.458,213.323,400.583,210.74z M344,355.813 c-12.729,42.375-51,70.854-95.25,70.854c-54.813,0-99.417-44.604-99.417-99.427V160c0-5.885,4.792-10.667,10.667-10.667 s10.667,4.781,10.667,10.667v64c0,5.896,4.771,10.667,10.667,10.667c5.896,0,10.667-4.771,10.667-10.667V106.667 C192,100.781,196.792,96,202.667,96s10.667,4.781,10.667,10.667v106.667c0,5.896,4.771,10.667,10.667,10.667 s10.667-4.771,10.667-10.667V96c0-5.885,4.792-10.667,10.667-10.667S256,90.115,256,96v128c0,5.896,4.771,10.667,10.667,10.667 c5.896,0,10.667-4.771,10.667-10.667V117.333c0-5.885,4.792-10.667,10.667-10.667s10.667,4.781,10.667,10.667V288 c0,4.833,3.25,9.052,7.917,10.302c4.688,1.292,9.563-0.792,11.979-4.969l32.771-56.802c3.854-6.156,9.896-10.396,17-11.927 c4.583-0.979,9.229-0.771,13.604,0.604L344,355.813z\" /> </g> </g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> </svg>', '1'),
(139, '<svg height=\"512\" viewBox=\"0 0 192 192\" width=\"512\" ><circle cx=\"72\" cy=\"32\" r=\"32\"/><path d=\"m118.745 94.043a40.071 40.071 0 0 0 -38.745-30.043h-16a40.045 40.045 0 0 0 -40 40v32a8 8 0 0 0 8 8h56a56.047 56.047 0 0 1 30.745-49.957z\"/><path d=\"m144 96a48 48 0 1 0 48 48 48.055 48.055 0 0 0 -48-48zm27.578 52.789c-6.446 12.89-24.986 22.382-25.772 22.78-.452.229-1.311.914-1.806.914s-1.354-.685-1.806-.914c-.786-.4-19.326-9.89-25.772-22.78-4.685-9.37-2.7-20.054 4.728-25.406 5.313-3.828 13.871-4.98 22.85 2.241 8.979-7.223 17.537-6.07 22.85-2.241 7.424 5.352 9.413 16.036 4.728 25.406z\"/></svg>', '1');
INSERT INTO `i_svg_icons` (`icon_id`, `icon_code`, `icon_status`) VALUES
(140, '<svg id=\"Capa_1\" enable-background=\"new 0 0 512 512\" height=\"512\" viewBox=\"0 0 512 512\" width=\"512\" xmlns=\"http://www.w3.org/2000/svg\"><g><g><path d=\"m226.53 402.35c-4.179 0-8.006-2.709-9.415-6.635-1.39-3.873-.195-8.328 2.946-10.986 3.291-2.785 8.083-3.128 11.746-.864 3.493 2.159 5.334 6.41 4.523 10.436-.929 4.612-5.082 8.049-9.8 8.049z\"/><path d=\"m395.097 389.69c-17.035 0-30.894-13.859-30.894-30.894v-7.968h-7.968c-17.035 0-30.894-13.859-30.894-30.893 0-17.035 13.858-30.894 30.894-30.894h7.968v-7.968c0-17.035 13.858-30.894 30.894-30.894 17.035 0 30.893 13.859 30.893 30.894v7.968h7.968c17.035 0 30.894 13.859 30.894 30.894s-13.858 30.893-30.894 30.893h-7.968v7.968c0 17.035-13.858 30.894-30.893 30.894zm-38.862-80.649c-6.007 0-10.894 4.887-10.894 10.894s4.887 10.893 10.894 10.893h17.968c5.522 0 10 4.477 10 10v17.968c0 6.007 4.887 10.894 10.894 10.894s10.893-4.887 10.893-10.894v-17.968c0-5.523 4.478-10 10-10h17.968c6.007 0 10.894-4.887 10.894-10.893 0-6.007-4.887-10.894-10.894-10.894h-17.968c-5.522 0-10-4.477-10-10v-17.968c0-6.007-4.887-10.894-10.893-10.894-6.007 0-10.894 4.887-10.894 10.894v17.968c0 5.523-4.478 10-10 10z\"/><path d=\"m395.097 202.863c-25.753 0-49.588 8.383-68.927 22.567-.031 0-.062-.001-.094-.001h-20.424c18.591-15.645 30.424-39.075 30.424-65.217 0-46.989-38.229-85.218-85.218-85.218-36.865 0-68.338 23.53-80.188 56.36-12.01-27.139-39.196-46.123-70.733-46.123-42.625 0-77.303 34.678-77.303 77.302 0 22.703 9.838 43.151 25.474 57.306-26.578.298-48.108 22.01-48.108 48.657v93.854c0 5.523 4.477 10 10 10h130.345v20c0 5.523 4.478 10 10 10h30.794c5.522 0 10-4.477 10-10s-4.478-10-10-10h-20.794v-103.715c0-18.31 14.896-33.206 33.206-33.206h111.438c-16.73 20.257-26.795 46.226-26.795 74.506 0 22.928 6.616 44.337 18.036 62.415h-24.677c-5.522 0-10 4.477-10 10s4.478 10 10 10h40.594c21.194 21.391 50.554 34.657 82.951 34.657 64.461 0 116.903-52.518 116.903-117.072-.001-64.554-52.443-117.072-116.904-117.072zm-144.239-107.869c35.961 0 65.218 29.256 65.218 65.218 0 35.961-29.257 65.217-65.218 65.217s-65.218-29.256-65.218-65.217c.001-35.962 29.258-65.218 65.218-65.218zm-208.224 67.54c0-31.597 25.706-57.302 57.303-57.302s57.303 25.706 57.303 57.302-25.706 57.303-57.303 57.303-57.303-25.707-57.303-57.303zm-22.634 189.816v-83.854c0-15.803 12.856-28.66 28.659-28.66h102.555c1.984 0 3.826.129 5.549.394-10.113 9.691-16.418 23.325-16.418 38.404v73.715h-120.345zm173.551-126.921c-5.341 0-10.5.791-15.368 2.262-7.479-5.186-16.168-7.763-26.417-7.852 8.579-7.767 15.412-17.428 19.833-28.316 5.251 13.246 13.732 24.874 24.465 33.906zm201.546 191.577c-53.433 0-96.903-43.546-96.903-97.072s43.471-97.071 96.903-97.071 96.903 43.546 96.903 97.072-43.471 97.071-96.903 97.071z\"/></g></g></svg>', '1'),
(141, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?><svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 507.312 507.312\" style=\"enable-background:new 0 0 507.312 507.312;\" xml:space=\"preserve\"> <g> <g> <g> <path d=\"M301.656,75.312c0-26.51-21.49-48-48-48c-26.51,0-48,21.49-48,48v160c-44.161,0.053-79.947,35.839-80,80v85.616l64,80 v26.384h32v-37.616l-64-80v-74.384c0-26.51,21.49-48,48-48v112h32v-304c0-8.837,7.163-16,16-16s16,7.163,16,16v140.224 l52.32,14.272l8.416-30.88l-28.8-7.84L301.656,75.312z\" /> <rect x=\"356.49\" y=\"211.066\" transform=\"matrix(-0.9648 -0.2629 0.2629 -0.9648 672.1706 544.0817)\" width=\"32\" height=\"32\" /> <polygon points=\"414.6,255.056 445.656,263.536 445.656,405.984 397.656,469.984 397.656,507.312 429.656,507.312 429.656,480.64 477.656,416.64 477.656,239.088 423.016,224.192      \" /> <polygon points=\"88.968,22.624 66.344,0 7.032,59.312 66.344,118.624 88.968,96 68.28,75.312 109.656,75.312 109.656,43.312 68.28,43.312      \" /> <rect x=\"141.656\" y=\"43.312\" width=\"32\" height=\"32\" /> <polygon points=\"418.344,96 440.968,118.624 500.28,59.312 440.968,0 418.344,22.624 439.032,43.312 397.656,43.312 397.656,75.312 439.032,75.312      \" /> <rect x=\"333.656\" y=\"43.312\" width=\"32\" height=\"32\" /> </g> </g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> </svg>', '1'),
(142, '<svg id=\"Layer_142\" enable-background=\"new 0 0 256 256\" height=\"512\" viewBox=\"0 0 256 256\" width=\"512\" ><path d=\"m188.786 124.612v22.95 22.95l42.513 20.575c2.296 1.111 4.908-.666 4.908-3.34v-40.185-40.185c0-2.674-2.612-4.451-4.908-3.34z\" fill=\"#f9a7a7\"/><path d=\"m232.742 193.42c-.787 0-1.577-.177-2.314-.534l-42.513-20.574c-.69-.334-1.129-1.033-1.129-1.8v-45.9c0-.767.438-1.466 1.129-1.8l42.514-20.574c1.655-.803 3.576-.696 5.138.284 1.653 1.036 2.641 2.852 2.641 4.855v80.369c0 2.004-.987 3.819-2.641 4.855-.867.545-1.844.819-2.825.819zm-41.956-24.162 41.385 20.028c.566.273 1.04.072 1.271-.073.479-.301.765-.849.765-1.467v-80.369c0-.618-.286-1.166-.765-1.467-.23-.146-.704-.346-1.271-.073l-41.385 20.028z\" fill=\"#3762cc\"/><path d=\"m181.345 213.123h-150.39c-6.164 0-11.162-4.997-11.162-11.162v-104.643c0-6.164 4.997-11.162 11.162-11.162h150.39c6.164 0 11.162 4.997 11.162 11.162v104.643c-.001 6.164-4.998 11.162-11.162 11.162z\" fill=\"#a4c9ff\"/><path d=\"m181.345 215.123h-150.39c-7.257 0-13.162-5.904-13.162-13.162v-104.644c0-7.257 5.904-13.161 13.162-13.161h150.39c7.257 0 13.162 5.904 13.162 13.161v104.644c-.001 7.258-5.905 13.162-13.162 13.162zm-150.39-126.967c-5.052 0-9.162 4.109-9.162 9.161v104.644c0 5.052 4.11 9.162 9.162 9.162h150.39c5.052 0 9.162-4.11 9.162-9.162v-104.644c0-5.052-4.11-9.161-9.162-9.161z\" fill=\"#3762cc\"/><circle cx=\"104.79\" cy=\"90.815\" fill=\"#6bdddd\" r=\"47.938\"/><path d=\"m104.79 125.059c-18.882 0-34.245-15.362-34.245-34.244s15.362-34.244 34.245-34.244c18.882 0 34.244 15.362 34.244 34.244s-15.362 34.244-34.244 34.244z\" fill=\"#4671c6\"/><path d=\"m104.79 140.753c-27.536 0-49.938-22.402-49.938-49.938s22.402-49.938 49.938-49.938 49.938 22.402 49.938 49.938-22.402 49.938-49.938 49.938zm0-95.876c-25.33 0-45.938 20.608-45.938 45.938s20.607 45.938 45.938 45.938 45.938-20.607 45.938-45.938-20.608-45.938-45.938-45.938z\" fill=\"#3762cc\"/><path d=\"m125.277 82.363h-11.982v-11.983c0-1.369-1.11-2.479-2.479-2.479h-11.569c-1.369 0-2.479 1.11-2.479 2.479v11.982h-11.983c-1.369 0-2.479 1.11-2.479 2.479v11.569c0 1.369 1.11 2.479 2.479 2.479h11.982v11.982c0 1.369 1.11 2.479 2.479 2.479h11.569c1.369 0 2.479-1.11 2.479-2.479v-11.981h11.982c1.369 0 2.479-1.11 2.479-2.479v-11.569c.002-1.369-1.108-2.479-2.478-2.479z\" fill=\"#ffea92\"/></svg>', '1'),
(143, '<svg height=\"512\" viewBox=\"0 0 58 40\" width=\"512\" ><g id=\"025---Live-Recording\" fill=\"none\" transform=\"translate(0 -1)\"><path id=\"Shape\" d=\"m40.769 22.925c.2964684-.0680556.6078105.0016839.8469181.1897069.2391077.1880229.380303.4741372.3840819.7782931v12.107c-.0193712 1.6332826-1.0165695 3.0955773-2.53 3.71-.952.417.683.29-35.47.29-2.20323738-.0141832-3.9858168-1.7967626-4-4v-15c0-2.209139 1.790861-4 4-4h31.107c.3041559.0037789.5902702.1449742.7782931.3840819.188023.2391076.2577625.5504497.1897069.8469181-.2627086 1.3080806.1462892 2.6608665 1.0897113 3.6042887.9434222.9434221 2.2962081 1.3524199 3.6042887 1.0897113z\" fill=\"#e64c3c\"/><g fill=\"#fff\"><path id=\"Shape\" d=\"m14 36c-.5522847 0-1-.4477153-1-1v-13c0-.5522847.4477153-1 1-1s1 .4477153 1 1v13c0 .5522847-.4477153 1-1 1z\"/><path id=\"Shape\" d=\"m22 36h-1c-.4658418.0002987-.8701857-.3211028-.975-.775l-3-13c-.1242641-.5384776.2115224-1.0757359.75-1.2s1.0757359.2115224 1.2.75l2.525 10.944 2.525-10.944c.0803848-.348334.3404973-.627323.6823557-.7318748.3418584-.1045517.7135263-.0187822.975.225s.3730291.6085408.2926443.9568748l-3 13c-.1048143.4538972-.5091582.7752987-.975.775z\"/><path id=\"Shape\" d=\"m34 36h-5c-.5522847 0-1-.4477153-1-1v-13c0-.5522847.4477153-1 1-1s1 .4477153 1 1v12h4c.5522847 0 1 .4477153 1 1s-.4477153 1-1 1z\"/><path id=\"Shape\" d=\"m10 36h-5c-.55228475 0-1-.4477153-1-1v-13c0-.5522847.44771525-1 1-1s1 .4477153 1 1v12h4c.5522847 0 1 .4477153 1 1s-.4477153 1-1 1z\"/><path id=\"Shape\" d=\"m34 30h-5c-.5522847 0-1-.4477153-1-1s.4477153-1 1-1h5c.5522847 0 1 .4477153 1 1s-.4477153 1-1 1z\"/><path id=\"Shape\" d=\"m34 23h-5c-.5522847 0-1-.4477153-1-1s.4477153-1 1-1h5c.5522847 0 1 .4477153 1 1s-.4477153 1-1 1z\"/></g><path id=\"Shape\" d=\"m29.337 9.94c3.3780934-3.989024 8.6979002-5.76260526 13.7939123-4.59878055 5.0960121 1.1638247 9.1184542 5.07098415 10.4299301 10.13100965 1.311476 5.0600255-.3067068 10.4291427-4.1958424 13.9217709-.5791942.4617158-.8557781 1.2057318-.7188308 1.9336692s.664997 1.3205773 1.3723809 1.5402484c.707384.219671 1.4782494.0303964 2.0034499-.4919176 5.0030014-4.4881465 7.0865489-11.3910607 5.4021687-17.8976992-1.6843803-6.50663857-6.8556791-11.53148318-13.4080112-13.02828852-6.5523321-1.49680535-13.392556.78414424-17.7351575 5.91398772-.4945364.5416244-.6529423 1.31048769-.4127695 2.00348137.2401728.69299363.8403924 1.19893793 1.5640346 1.31837473.7236423.1194368 1.4546102-.1667957 1.9047349-.7458561z\" fill=\"#84b5cb\"/><path id=\"Shape\" d=\"m36.494 14.129c2.3969688-1.7184461 5.6874269-1.440015 7.7615598.6567657 2.074133 2.0967807 2.3168149 5.3900683.5724402 7.7682343-.4550265.6330904-.4903941 1.4760178-.09 2.145.3346867.5791293.9367272.9526088 1.6042534.9952083.6675263.0425995 1.3121582-.2513211 1.7177466-.7832083 2.9116097-3.9619339 2.5093232-9.4530339-.9486143-12.9483249-3.4579375-3.49529099-8.9443984-3.9565501-12.9373857-1.0876751-.5513071.4163884-.8470492 1.089036-.7811912 1.7767725.065858.6877366.4838808 1.2920337 1.1041912 1.5962275.6363204.3333027 1.4047647.2875116 1.997-.119z\" fill=\"#3b97d3\"/><circle id=\"Oval\" cx=\"40\" cy=\"19\" fill=\"#2980ba\" r=\"2\"/></g></svg>', '1'),
(144, '&lt;svg fill=&quot;#000000&quot; id=&quot;Capa_1&quot; width=&quot;800px&quot; height=&quot;800px&quot; viewBox=&quot;0 0 36 36&quot; version=&quot;1.1&quot;  preserveAspectRatio=&quot;xMidYMid meet&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot; xmlns:xlink=&quot;http://www.w3.org/1999/xlink&quot;&gt;\r\n    &lt;title&gt;dollar-solid&lt;/title&gt;\r\n    &lt;path class=&quot;clr-i-solid clr-i-solid-path-1&quot; d=&quot;M18,2A16,16,0,1,0,34,18,16,16,0,0,0,18,2Zm7.65,21.59c-1,3-3.61,3.84-5.9,4v2a1.25,1.25,0,0,1-2.5,0V27.59A11.47,11.47,0,0,1,11,25a1.25,1.25,0,1,1,1.71-1.83,9.11,9.11,0,0,0,4.55,1.94V18.83a9.63,9.63,0,0,1-3.73-1.41,4.8,4.8,0,0,1-1.91-5.84c.59-1.51,2.42-3.23,5.64-3.51V6.25a1.25,1.25,0,0,1,2.5,0V8.11a9.67,9.67,0,0,1,4.9,2A1.25,1.25,0,0,1,23,11.95a7.14,7.14,0,0,0-3.24-1.31v6.13c.6.13,1.24.27,1.91.48a5.85,5.85,0,0,1,3.69,2.82A4.64,4.64,0,0,1,25.65,23.59Z&quot;&gt;&lt;/path&gt;&lt;path class=&quot;clr-i-solid clr-i-solid-path-2&quot; d=&quot;M20.92,19.64c-.4-.12-.79-.22-1.17-.3v5.76c2-.2,3.07-.9,3.53-2.3a2.15,2.15,0,0,0-.15-1.58A3.49,3.49,0,0,0,20.92,19.64Z&quot;&gt;&lt;/path&gt;&lt;path class=&quot;clr-i-solid clr-i-solid-path-3&quot; d=&quot;M13.94,12.48a2.31,2.31,0,0,0,1,2.87,6.53,6.53,0,0,0,2.32.92V10.55C15.16,10.8,14.19,11.84,13.94,12.48Z&quot;&gt;&lt;/path&gt;\r\n    &lt;rect x=&quot;0&quot; y=&quot;0&quot; width=&quot;36&quot; height=&quot;36&quot; fill-opacity=&quot;0&quot;/&gt;\r\n&lt;/svg&gt;', '1'),
(145, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<svg enable-background=\"new 0 0 512 512\" version=\"1.1\" viewBox=\"0 0 512 512\" xml:space=\"preserve\" xmlns=\"http://www.w3.org/2000/svg\">\r\n<path d=\"m256 133.6c-56.397 0-109.47-22.02-149.46-62-6.521-6.515-6.521-17.086 0-23.607 6.511-6.521 17.086-6.521 23.607 0 33.682 33.671 78.373 52.218 125.85 52.218s92.171-18.547 125.85-52.218c6.521-6.521 17.096-6.521 23.607 0 6.521 6.521 6.521 17.09 0 23.607-39.988 39.98-93.064 62-149.46 62z\" fill=\"#FFC033\"/>\r\n<path d=\"m405.46 71.6c6.521-6.515 6.521-17.086 0-23.607-6.511-6.521-17.086-6.521-23.607 0-33.682 33.671-78.373 52.218-125.85 52.218v33.389c56.397 0 109.47-22.02 149.46-62z\" fill=\"#F9A926\"/>\r\n<path d=\"m428.51 512h-345.01c-27.617 0-50.082-22.466-50.082-50.082v-278.24c0-9.222 7.472-16.694 16.694-16.694h411.79c9.222 0 16.694 7.472 16.694 16.694v278.24c0 27.618-22.464 50.083-50.082 50.083z\" fill=\"#F26D76\"/>\r\n<path d=\"m461.89 166.99h-205.89v345.01h172.51c27.617 0 50.082-22.466 50.082-50.082v-278.24c0-9.222-7.472-16.694-16.694-16.694z\" fill=\"#E65C64\"/>\r\n<rect x=\"33.41\" y=\"200.37\" width=\"445.18\" height=\"33.388\" fill=\"#D5565D\"/>\r\n<rect x=\"256\" y=\"200.37\" width=\"222.59\" height=\"33.388\" fill=\"#BC4B52\"/>\r\n<path d=\"m318.67 9.524c-19.14-13.786-44.817-12.561-62.673 3.475-17.785-15.97-43.446-17.34-62.674-3.475-28.103 20.268-27.95 64.848 0.75 84.764l52.414 36.339c2.284 1.588 6.138 2.973 9.51 2.973 2.856 0 6.68-1.005 9.51-2.973l52.42-36.344c28.835-20.007 28.706-64.591 0.743-84.759zm-105.82 27.091c11.049-7.966 26.455-0.036 26.455 13.542v34.861l-26.204-18.167c-9.839-6.82-9.644-23.473-0.251-30.236zm86.052 30.23-26.21 18.172v-34.86c0-13.603 15.426-21.492 26.455-13.542 9.597 6.91 9.357 23.577-0.245 30.23z\" fill=\"#FFC033\"/>\r\n<path d=\"m265.51 130.63 52.42-36.344c28.836-20.007 28.707-64.592 0.745-84.758-19.14-13.786-44.817-12.561-62.673 3.475v120.6c2.854 0 6.678-1.004 9.508-2.973zm7.184-80.47c0-13.603 15.426-21.492 26.455-13.542 9.597 6.911 9.358 23.578-0.245 30.231l-26.21 18.172v-34.861z\" fill=\"#F9A926\"/>\r\n<path d=\"m495.28 200.38h-478.56c-9.22 0-16.694-7.475-16.694-16.694v-50.082c0-18.44 14.948-33.388 33.388-33.388h445.18c18.44 0 33.388 14.948 33.388 33.388v50.082c1e-3 9.221-7.474 16.694-16.694 16.694z\" fill=\"#36D9D9\"/>\r\n<path d=\"m478.59 100.21h-222.59v100.16h239.28c9.22 0 16.694-7.475 16.694-16.694v-50.082c0-18.44-14.948-33.389-33.389-33.389z\" fill=\"#43BFBF\"/>\r\n<path d=\"m222.61 97.985v414.01h66.777v-414.01h-66.777z\" fill=\"#FFC033\"/>\r\n<path d=\"m289.39 97.985h-33.388v414.01h33.388v-414.01z\" fill=\"#F9A926\"/> \r\n</svg>\r\n', '1'),
(146, '<svg ><path d=\"M19 2c1.654 0 3 1.346 3 3v14c0 1.654-1.346 3-3 3h-14c-1.654 0-3-1.346-3-3v-14c0-1.654 1.346-3 3-3h14zm0-2h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-8 8h-1v-2h1v1h2v1h-1v1h-1v-1zm2 12v-1h-1v1h1zm-1-15v-1h-2v1h1v1h1v-1zm8-1v6h-1v-1h-4v-5h5zm-1 4v-3h-3v3h3zm-14 2h-1v1h2v-1h-1zm0 3h1v1h1v-3h-1v1h-2v2h1v-1zm5 1v2h1v-2h-1zm4-10h-1v3h1v-3zm0 5v-1h-1v1h1zm3-2h1v-1h-1v1zm-10-1h-1v1h1v-1zm2-2v5h-5v-5h5zm-1 1h-3v3h3v-3zm9 5v1h-1v-1h-2v1h-1v-1h-3v-1h-1v1h-1v1h1v2h1v-1h1v2h1v-2h3v1h-2v1h2v1h1v-3h1v1h1v2h1v-1h1v-1h-1v-1h-1v-1h1v-1h-2zm-11 8h1v-1h-1v1zm-2-3h5v5h-5v-5zm1 4h3v-3h-3v3zm12-3v-1h-1v1h1zm0 1h-1v1h-1v-1h-1v-1h1v-1h-2v-1h-1v2h-1v1h-1v3h1v-1h1v-1h2v2h1v-1h1v1h2v-1h1v-1h-2v-1zm-9-3h1v-1h-1v1zm10 2v1h1v1h1v-3h-1v1h-1zm2 4v-1h-1v1h1zm0-8v-1h-1v1h1z\"/></svg>', '1'),
(147, '<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" version=\"1.1\" width=\"256\" height=\"256\" viewBox=\"0 0 256 256\" xml:space=\"preserve\">\r\n<desc>Created with Fabric.js 1.7.22</desc>\r\n<defs>\r\n</defs>\r\n<g transform=\"translate(128 128) scale(0.72 0.72)\" style=\"\">\r\n <g style=\"stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;\" transform=\"translate(-175.05 -175.05000000000004) scale(3.89 3.89)\" >\r\n  <path d=\"M 76.735 13.079 C 68.315 4.649 57.117 0.005 45.187 0 C 20.605 0 0.599 20.005 0.589 44.594 c -0.003 7.86 2.05 15.532 5.953 22.296 L 0.215 90 l 23.642 -6.202 c 6.514 3.553 13.848 5.426 21.312 5.428 h 0.018 c 0.001 0 -0.001 0 0 0 c 24.579 0 44.587 -20.007 44.597 -44.597 C 89.789 32.713 85.155 21.509 76.735 13.079 z M 27.076 46.217 c -0.557 -0.744 -4.55 -6.042 -4.55 -11.527 c 0 -5.485 2.879 -8.181 3.9 -9.296 c 1.021 -1.115 2.229 -1.394 2.972 -1.394 s 1.487 0.007 2.136 0.039 c 0.684 0.035 1.603 -0.26 2.507 1.913 c 0.929 2.231 3.157 7.717 3.436 8.274 c 0.279 0.558 0.464 1.208 0.093 1.952 c -0.371 0.743 -0.557 1.208 -1.114 1.859 c -0.557 0.651 -1.17 1.453 -1.672 1.952 c -0.558 0.556 -1.139 1.159 -0.489 2.274 c 0.65 1.116 2.886 4.765 6.199 7.72 c 4.256 3.797 7.847 4.973 8.961 5.531 c 1.114 0.558 1.764 0.465 2.414 -0.279 c 0.65 -0.744 2.786 -3.254 3.529 -4.369 c 0.743 -1.115 1.486 -0.929 2.507 -0.558 c 1.022 0.372 6.5 3.068 7.614 3.625 c 1.114 0.558 1.857 0.837 2.136 1.302 c 0.279 0.465 0.279 2.696 -0.65 5.299 c -0.929 2.603 -5.381 4.979 -7.522 5.298 c -1.92 0.287 -4.349 0.407 -7.019 -0.442 c -1.618 -0.513 -3.694 -1.199 -6.353 -2.347 C 34.934 58.216 27.634 46.961 27.076 46.217 z\" style=\"stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(61,198,0); fill-rule: evenodd; opacity: 1;\" transform=\" matrix(1 0 0 1 0 0) \" stroke-linecap=\"round\" />\r\n</g>\r\n</g>\r\n</svg>', '1'),
(148, '<svg id=\"Layer_148\" data-name=\"Layer 148\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 122.88 80.19\"><defs><style>.cls-148{fill:#262626;}.cls-148,.cls-248{fill-rule:evenodd;}.cls-248{fill:#d8453e;}</style></defs><path class=\"cls-1\" d=\"M23.12,45.55c3.35,9.84,17.33,10.21,20.47,0,3.7,3.33,12.28,4,16.36,6.27a10.41,10.41,0,0,1,3.38,2.87,13.75,13.75,0,0,1,2.53,7.43l.24,1.94-1.53.8a17.2,17.2,0,0,1-10.13,1.56,6.32,6.32,0,0,0-7,5.54c0,.2,0,.41-.05.61H3.91C1.45,72.38.2,71.11,0,68.91l.85-6.79a13.66,13.66,0,0,1,2.52-7.43,10.6,10.6,0,0,1,3.4-2.87c4.85-2.7,12.31-2.63,16.35-6.27ZM74.2,33.29l3.22-.09,2.68-.07C77,23.52,78,14.69,85.54,7.19c1.27,4.11,4.13,7.52,9,10a46.71,46.71,0,0,1,6.75,6.21c.39-1.58-1.08-3.51-2.87-5.5a8.65,8.65,0,0,1,4.25,4.16c1.25,2.54,1.24,4.7.82,7.46a15.94,15.94,0,0,1-1,3.58H107c4.69-10,1.71-25-7.88-31.27C96.15-.07,94,0,90.58,0c-4,0-6,.12-9.36,2.36-5,3.3-8.06,9-9.35,17-.26,3.95-.44,10.79,2.33,14ZM101.11,37c5.06,2,10.23,3.89,14.06,6.3,2.44,1.52,3.7,2.67,4.68,4.52,2.56,4.81,2.5,9.13,3,14.44H102.56l0-7.81a6.3,6.3,0,0,0-6.28-6.28L71.91,48a6.32,6.32,0,0,0-2.46.49,17.53,17.53,0,0,0-4.26-3.31c-.39-.22-.8-.42-1.23-.61.55-.42,1.19-.85,1.92-1.31,3.84-2.41,9-4.34,14.07-6.3,1.48,8.81,21,7.33,21.16,0ZM20.23,27.51a2.53,2.53,0,0,0-1.18.36,1.15,1.15,0,0,0-.41.47,1.63,1.63,0,0,0-.14.78A7.45,7.45,0,0,0,20,32.71l0,0h0l3.16,5a27,27,0,0,0,4.24,5.56,8.51,8.51,0,0,0,6,2.4,8.76,8.76,0,0,0,6.32-2.52A28,28,0,0,0,44,37.27l3.56-5.86c.73-1.64.94-2.66.7-3.14-.13-.27-.62-.38-1.42-.33a3.55,3.55,0,0,1-2-.07l1.44-4.05c-6.52-.08-11-1.21-16.25-4.59-1.73-1.1-2.25-2.37-4-2.24a5.83,5.83,0,0,0-3.28,1.77A8.49,8.49,0,0,0,21,22.47L22,27.54a3.58,3.58,0,0,1-1.8,0Zm28.32-1.46a2.46,2.46,0,0,1,1.61,1.31c.54,1.08.32,2.67-.68,5h0l-.06.12-3.61,5.94a29.61,29.61,0,0,1-4.71,6.38,10.76,10.76,0,0,1-7.75,3.07,10.55,10.55,0,0,1-7.37-3,28.25,28.25,0,0,1-4.58-6l-3.16-5a9.25,9.25,0,0,1-1.82-4.67,3.69,3.69,0,0,1,.33-1.71,3,3,0,0,1,1.14-1.33,3.67,3.67,0,0,1,.84-.42,53.61,53.61,0,0,1-.13-7.41A12.42,12.42,0,0,1,19,16.26a12.21,12.21,0,0,1,5.35-6.82A14.77,14.77,0,0,1,27.21,8c6.08-2.19,14.14-1,18.45,3.67a12.24,12.24,0,0,1,3.09,7.76l-.2,6.58Z\"/><path class=\"cls-248\" d=\"M96.21,54.42,71.88,54.3,78,60.39C71.91,69.63,64.11,73.93,53.7,72.7c12.77,11.17,26.72,8.77,36.42-.16l6.21,6.21-.12-24.33Z\"/></svg>', '1'),
(149, '<svg enable-background=\"new 0 0 503.2 503.2\" version=\"1.1\" viewBox=\"0 0 503.2 503.2\" xml:space=\"preserve\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"m404.6 299.2c-26.105 0-49.776 10.221-67.415 26.785l-65.185-48.884v-74.664c46.335-8.071 81.6-48.389 81.6-97.036 0-54.454-44.146-98.6-98.6-98.6-54.455 0-98.601 44.146-98.601 98.6 0 48.647 35.265 88.965 81.6 97.036v74.439l-70.101 50.286c-12.886-12.642-29.267-21.732-47.58-25.643-68.305-14.579-128.01 41.65-119.51 109.1 5.536 43.949 41.024 79.432 84.973 84.974 67.449 8.5 123.68-51.204 109.1-119.52-1.584-7.432-4.06-14.531-7.242-21.229l67.137-48.164 61.981 46.484c-6.827 13.417-10.757 28.554-10.757 44.636 0 54.454 44.146 98.6 98.6 98.6s98.6-44.146 98.6-98.6c-1e-3 -54.455-44.146-98.601-98.6-98.601zm-306 34.014c16.395 0 29.689 13.294 29.689 29.689s-13.294 29.688-29.689 29.688-29.689-13.294-29.689-29.688c0-16.402 13.294-29.689 29.689-29.689zm44.2 115.59h-88.401v-13.328c0-18.217 12.784-34.272 31.008-34.272h26.391c18.217 0 31.001 16.056 31.001 34.272v13.328zm112.2-407.99c16.395 0 29.688 13.294 29.688 29.688 0 16.395-13.294 29.689-29.688 29.689-16.395 0-29.689-13.294-29.689-29.689 0-16.4 13.294-29.688 29.689-29.688zm-44.2 115.59v-13.328c0-18.217 12.784-34.272 31.008-34.272h26.391c18.218 0 31.001 16.055 31.001 34.272v13.328h-88.4zm193.8 176.81c16.395 0 29.688 13.294 29.688 29.689s-13.294 29.688-29.688 29.688-29.688-13.294-29.688-29.688c-1e-3 -16.402 13.293-29.689 29.688-29.689zm44.2 115.59h-88.4v-13.328c0-18.217 12.784-34.272 31.008-34.272h26.391c18.217 0 31.001 16.056 31.001 34.272v13.328z\"/></svg>', '1'),
(150, '<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\"> \r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\"><path d=\"M3866.8,4549.2c-55.5-42.1-1271.6-2052.9-1296.4-2144.8c-9.6-34.5-9.6-78.5,1.9-113c24.9-68.9,3489.1-3335.9,3588.7-3383.8c63.2-30.6,70.9-30.6,134,0c99.6,47.9,3563.8,3314.9,3588.7,3383.8c11.5,34.5,11.5,78.5,1.9,113c-24.9,91.9-1240.9,2102.7-1296.5,2144.8c-49.8,40.2-55.5,40.2-2361.2,40.2C3922.4,4589.5,3916.6,4589.5,3866.8,4549.2z M5883.3,4195c0-32.5-614.7-1114.5-781.3-1375l-95.8-151.3l-153.2,304.5c-168.5,335.1-559.2,1185.4-559.2,1214.1c0,11.5,298.7,19.2,794.7,19.2C5525.2,4206.5,5883.3,4200.7,5883.3,4195z M8143,4196.9c0-19.2-699-1407.5-708.6-1407.5c-11.5,0-842.6,1382.6-842.6,1403.7c0,7.7,348.5,13.4,775.6,13.4C7794.5,4206.5,8143,4202.6,8143,4196.9z M6685.7,3252.8c222.1-371.5,404.1-679.8,404.1-685.6c0-3.8-375.3-7.7-833-7.7c-457.7,0-833,5.7-833,11.5c0,5.7,59.4,107.2,130.2,224c72.8,118.7,233.6,396.4,358.1,616.6c122.6,222.1,247,440.4,271.9,486.4c40.2,68.9,51.7,78.5,72.8,57.5C6270.2,3940.3,6461.7,3624.3,6685.7,3252.8z M8924.3,3264.3l421.3-704.7h-783.2h-783.2l356.2,710.5c195.3,390.7,358.1,708.5,360,704.7C8499.2,3970.9,8692.6,3651.1,8924.3,3264.3z M4447.1,2890.9l162.8-331.3h-750.7h-750.7l392.6,656.8c216.4,360,406,676,423.2,700.9c28.7,44.1,34.5,34.5,193.4-325.5C4209.6,3386.9,4357.1,3070.9,4447.1,2890.9z M4914.3,2029.1c302.6-802.4,813.9-2177.4,810-2183.1c-7.7-7.7-2434,2273.1-2441.6,2296.1c-3.8,7.7,350.4,15.3,787.1,15.3h794.7L4914.3,2029.1z M7120.4,2038.7c-24.9-67-231.7-622.4-461.5-1235.2C6429.1,188.8,6235.7-312.9,6228-312.9c-11.5,0-612.8,1583.7-882.8,2322.9l-55.5,147.5H6228h938.3L7120.4,2038.7z M9173.3,2142.1c-7.6-23-2434-2301.8-2441.6-2296.1c-3.8,5.7,467.3,1273.5,810,2183.1l49.8,128.3h794.7C8822.9,2157.4,9177.1,2149.8,9173.3,2142.1z\"/><path d=\"M218.8,79.6C96.2,26,100,93.1,100-1730c0-1834.6-5.7-1756.1,126.4-1811.6c57.4-24.9,178.1-28.7,781.3-23c677.9,5.7,714.3,7.7,798.6,46c107.2,49.8,231.7,159,289.2,252.8c23,40.2,44,74.7,46,78.5c3.8,3.8,172.3-68.9,375.3-160.9c1057.1-480.7,1949.5-813.9,2575.7-959.4c283.4-65.1,609-80.4,838.8-38.3c450,84.3,1068.6,308.3,1915,691.3c256.6,116.8,1725.4,821.5,1943.7,932.6c26.8,15.3,63.2,57.5,84.3,97.7c30.6,65.1,32.5,88.1,13.4,181.9c-30.6,155.1-90,264.3-204.9,377.2c-243.2,239.4-735.4,369.6-1189.2,316c-300.7-36.4-903.9-168.5-1315.6-289.1c-147.4-44.1-153.2-42.1-172.4,78.5c-17.2,105.3-145.5,285.4-273.8,381.1c-229.8,172.3-551.5,291.1-1154.7,425.1c-402.1,88.1-587.9,153.2-978.6,339C4056.4-558,3901.3-515.9,3508.7-517.8c-283.4-1.9-557.3-34.5-838.8-101.5c-88.1-21.1-166.6-38.3-176.2-38.3s-44,51.7-78.5,116.8c-70.8,132.1-139.8,197.3-275.8,258.5c-59.4,26.8-427,109.2-967.1,216.4C693.7,29.9,297.3,108.4,291.5,108.4C285.8,106.5,253.2,95,218.8,79.6z M1277.8-492.9C1971-630.8,1986.3-636.6,2038-694c38.3-42.1,53.6-80.4,53.6-128.3c0-90-281.5-2083.5-306.4-2175.4c-11.5-38.3-46-91.9-78.5-118.7l-59.4-49.8h-572.6H502.2v1407.5c0,1338.6,1.9,1407.5,34.5,1407.5C555.8-351.2,889-414.4,1277.8-492.9z M3958.8-971.7c84.3-28.7,300.7-122.5,478.7-206.8c388.7-183.8,658.8-275.7,1082-369.6c704.7-157,1043.7-316,1110.7-518.9c97.7-294.9-459.6-365.8-1417.1-178.1c-239.4,46-735.4,168.5-1007.3,248.9c-243.2,68.9-279.6,68.9-356.2,0c-49.8-44-63.2-70.8-63.2-130.2c0-124.5,46-170.4,222.1-226c235.5-74.7,781.3-212.6,1051.3-266.2c384.9-78.5,605.1-107.2,890.5-120.6c400.2-17.2,664.5,30.6,852.2,155.1c120.6,78.5,1064.7,331.3,1553.1,415.5c360,61.3,683.7,30.6,915.4-88.1c103.4-51.7,208.7-155.1,181.9-178.1c-57.5-51.7-1689-827.3-2200.3-1047.5c-905.8-388.7-1440.1-532.4-1802-488.3c-448.1,55.5-1246.7,323.6-2269.3,762.2c-630,270-955.6,427-953.7,461.5c0,17.2,53.6,396.4,118.7,844.5l118.7,813.9l99.6,32.6c266.2,90,697.1,149.4,1011.1,141.7C3765.3-920,3832.4-929.6,3958.8-971.7z\"/></g></g>\r\n</svg>', '1'),
(151, '<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\"> \r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\"><path d=\"M3866.8,4549.2c-55.5-42.1-1271.6-2052.9-1296.4-2144.8c-9.6-34.5-9.6-78.5,1.9-113c24.9-68.9,3489.1-3335.9,3588.7-3383.8c63.2-30.6,70.9-30.6,134,0c99.6,47.9,3563.8,3314.9,3588.7,3383.8c11.5,34.5,11.5,78.5,1.9,113c-24.9,91.9-1240.9,2102.7-1296.5,2144.8c-49.8,40.2-55.5,40.2-2361.2,40.2C3922.4,4589.5,3916.6,4589.5,3866.8,4549.2z M5883.3,4195c0-32.5-614.7-1114.5-781.3-1375l-95.8-151.3l-153.2,304.5c-168.5,335.1-559.2,1185.4-559.2,1214.1c0,11.5,298.7,19.2,794.7,19.2C5525.2,4206.5,5883.3,4200.7,5883.3,4195z M8143,4196.9c0-19.2-699-1407.5-708.6-1407.5c-11.5,0-842.6,1382.6-842.6,1403.7c0,7.7,348.5,13.4,775.6,13.4C7794.5,4206.5,8143,4202.6,8143,4196.9z M6685.7,3252.8c222.1-371.5,404.1-679.8,404.1-685.6c0-3.8-375.3-7.7-833-7.7c-457.7,0-833,5.7-833,11.5c0,5.7,59.4,107.2,130.2,224c72.8,118.7,233.6,396.4,358.1,616.6c122.6,222.1,247,440.4,271.9,486.4c40.2,68.9,51.7,78.5,72.8,57.5C6270.2,3940.3,6461.7,3624.3,6685.7,3252.8z M8924.3,3264.3l421.3-704.7h-783.2h-783.2l356.2,710.5c195.3,390.7,358.1,708.5,360,704.7C8499.2,3970.9,8692.6,3651.1,8924.3,3264.3z M4447.1,2890.9l162.8-331.3h-750.7h-750.7l392.6,656.8c216.4,360,406,676,423.2,700.9c28.7,44.1,34.5,34.5,193.4-325.5C4209.6,3386.9,4357.1,3070.9,4447.1,2890.9z M4914.3,2029.1c302.6-802.4,813.9-2177.4,810-2183.1c-7.7-7.7-2434,2273.1-2441.6,2296.1c-3.8,7.7,350.4,15.3,787.1,15.3h794.7L4914.3,2029.1z M7120.4,2038.7c-24.9-67-231.7-622.4-461.5-1235.2C6429.1,188.8,6235.7-312.9,6228-312.9c-11.5,0-612.8,1583.7-882.8,2322.9l-55.5,147.5H6228h938.3L7120.4,2038.7z M9173.3,2142.1c-7.6-23-2434-2301.8-2441.6-2296.1c-3.8,5.7,467.3,1273.5,810,2183.1l49.8,128.3h794.7C8822.9,2157.4,9177.1,2149.8,9173.3,2142.1z\"/><path d=\"M218.8,79.6C96.2,26,100,93.1,100-1730c0-1834.6-5.7-1756.1,126.4-1811.6c57.4-24.9,178.1-28.7,781.3-23c677.9,5.7,714.3,7.7,798.6,46c107.2,49.8,231.7,159,289.2,252.8c23,40.2,44,74.7,46,78.5c3.8,3.8,172.3-68.9,375.3-160.9c1057.1-480.7,1949.5-813.9,2575.7-959.4c283.4-65.1,609-80.4,838.8-38.3c450,84.3,1068.6,308.3,1915,691.3c256.6,116.8,1725.4,821.5,1943.7,932.6c26.8,15.3,63.2,57.5,84.3,97.7c30.6,65.1,32.5,88.1,13.4,181.9c-30.6,155.1-90,264.3-204.9,377.2c-243.2,239.4-735.4,369.6-1189.2,316c-300.7-36.4-903.9-168.5-1315.6-289.1c-147.4-44.1-153.2-42.1-172.4,78.5c-17.2,105.3-145.5,285.4-273.8,381.1c-229.8,172.3-551.5,291.1-1154.7,425.1c-402.1,88.1-587.9,153.2-978.6,339C4056.4-558,3901.3-515.9,3508.7-517.8c-283.4-1.9-557.3-34.5-838.8-101.5c-88.1-21.1-166.6-38.3-176.2-38.3s-44,51.7-78.5,116.8c-70.8,132.1-139.8,197.3-275.8,258.5c-59.4,26.8-427,109.2-967.1,216.4C693.7,29.9,297.3,108.4,291.5,108.4C285.8,106.5,253.2,95,218.8,79.6z M1277.8-492.9C1971-630.8,1986.3-636.6,2038-694c38.3-42.1,53.6-80.4,53.6-128.3c0-90-281.5-2083.5-306.4-2175.4c-11.5-38.3-46-91.9-78.5-118.7l-59.4-49.8h-572.6H502.2v1407.5c0,1338.6,1.9,1407.5,34.5,1407.5C555.8-351.2,889-414.4,1277.8-492.9z M3958.8-971.7c84.3-28.7,300.7-122.5,478.7-206.8c388.7-183.8,658.8-275.7,1082-369.6c704.7-157,1043.7-316,1110.7-518.9c97.7-294.9-459.6-365.8-1417.1-178.1c-239.4,46-735.4,168.5-1007.3,248.9c-243.2,68.9-279.6,68.9-356.2,0c-49.8-44-63.2-70.8-63.2-130.2c0-124.5,46-170.4,222.1-226c235.5-74.7,781.3-212.6,1051.3-266.2c384.9-78.5,605.1-107.2,890.5-120.6c400.2-17.2,664.5,30.6,852.2,155.1c120.6,78.5,1064.7,331.3,1553.1,415.5c360,61.3,683.7,30.6,915.4-88.1c103.4-51.7,208.7-155.1,181.9-178.1c-57.5-51.7-1689-827.3-2200.3-1047.5c-905.8-388.7-1440.1-532.4-1802-488.3c-448.1,55.5-1246.7,323.6-2269.3,762.2c-630,270-955.6,427-953.7,461.5c0,17.2,53.6,396.4,118.7,844.5l118.7,813.9l99.6,32.6c266.2,90,697.1,149.4,1011.1,141.7C3765.3-920,3832.4-929.6,3958.8-971.7z\"/></g></g>\r\n</svg>', '1'),
(152, '<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 478 478\" style=\"enable-background:new 0 0 478 478\" xml:space=\"preserve\"><g><g id=\"XMLID_708_\"><path id=\"XMLID_2131_\" d=\"M394,196.1c0-11.9-9.7-21.6-21.6-21.6s-21.6,9.7-21.6,21.6c0,61.7-50.2,111.8-111.8,111.8s-111.8-50.2-111.8-111.8c0-11.9-9.7-21.6-21.6-21.6S84,184.2,84,196.1c0,78.2,58.1,143,133.4,153.5v85.2h-55.6c-11.9,0-21.6,9.7-21.6,21.6s9.7,21.6,21.6,21.6h154.5c11.9,0,21.6-9.7,21.6-21.6s-9.7-21.6-21.6-21.6h-55.6v-85.2C335.9,339.1,394,274.2,394,196.1z\"/><g id=\"XMLID_2124_\"><path id=\"XMLID_2050_\" d=\"M239,0L239,0c-46.4,0-84,37.6-84,84v110.7c0,46.4,37.6,84,84,84l0,0c46.4,0,84-37.6,84-84V84C323,37.6,285.4,0,239,0z M239.1,55.9c-15.1,0-27.4,12.3-27.4,27.4c0,6.9-5.6,12.5-12.5,12.5s-12.5-5.6-12.5-12.5c0-28.9,23.5-52.4,52.4-52.4c6.9,0,12.5,5.6,12.5,12.5S246,55.9,239.1,55.9z\"/></g></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(153, '<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\"viewBox=\"0 0 369.946 369.946\" style=\"enable-background:new 0 0 369.946 369.946;\" xml:space=\"preserve\"><g><path d=\"M184.973,0C82.975,0,0,82.975,0,184.973s82.975,184.973,184.973,184.973s184.973-82.975,184.973-184.973S286.971,0,184.973,0z M256.575,190.94H190.94v65.636h-11.934V190.94h-65.636v-11.934h65.636v-65.636h11.934v65.636h65.636V190.94z\"/></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>\r\n', '1'),
(154, '<svg id=\"Layer_1\" data-name=\"Layer 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 122.67 122.9\"><path d=\"M104,31.15c2.31,5.65,5.11,11.94,6.52,17.55a51.83,51.83,0,0,1,1.54,15.24,51,51,0,0,1-3,15,50,50,0,0,1-7.24,13.23A49.29,49.29,0,0,1,91,102.49h0a55.63,55.63,0,0,1-4.74,3,52.89,52.89,0,0,1-5,2.49,52,52,0,0,1-11.38,3.56,49.62,49.62,0,0,1-11.67.82L57,112.28l-2.76,10.28,1.89.15A60.62,60.62,0,0,0,71.05,122a62.88,62.88,0,0,0,14.51-4.42c2-.87,3.9-1.84,5.83-2.93s3.75-2.25,5.62-3.55a59.66,59.66,0,0,0,22-28.62,61.63,61.63,0,0,0,1.7-36.38c-2-5.77-4.18-13.39-8.31-19.22-2.73-3.86-10.73-1.57-8.35,4.27ZM56,44.74a5.34,5.34,0,1,1,10.67-.46,3.53,3.53,0,0,1,0,.46V56.1H78a5.34,5.34,0,1,1,0,10.68H66.66V78.14A5.34,5.34,0,1,1,56,78.6a3.53,3.53,0,0,1,0-.46V66.78H44.62a5.34,5.34,0,1,1,0-10.68H56ZM24.25,110.36a60,60,0,0,0,11.46,7l1.69.79,2.75-10.25L39,107.35a49,49,0,0,1-8.38-5.28,50,50,0,0,1-7.25-6.93l-.85-1-9.17,5.29,1.18,1.43a61.38,61.38,0,0,0,9.72,9.48ZM.57,69.53A59.55,59.55,0,0,0,4,82.67l.65,1.72,9.17-5.29-.44-1.24A49.33,49.33,0,0,1,11,68.14a48.46,48.46,0,0,1-.37-10l.07-1.29L.41,54.15.24,56A58.88,58.88,0,0,0,.57,69.53ZM8.38,31c-.92,1.55-1.75,3.11-2.51,4.7l-.81,1.7,10.25,2.75L15.89,39c.58-1.13,1.19-2.23,1.83-3.29s1.35-2.13,2.1-3.17q1.83-2.57,3.9-4.93A53.73,53.73,0,0,1,28,23.22l.93-.85-5.31-9.21-1.44,1.25a66.54,66.54,0,0,0-5.74,5.65,71.86,71.86,0,0,0-5.15,6.41c-1,1.48-2,3-2.92,4.5ZM50.44.78A54.18,54.18,0,0,0,40.27,3.52l-1.74.65,5.3,9.18,1.22-.43a44.41,44.41,0,0,1,15.71-2.43,33.7,33.7,0,0,1,3.79.26c1.31.15,2.51.33,3.74.58s2.55.56,3.8.93,2.5.8,3.79,1.3h0a5.24,5.24,0,0,0,3.9-9.73l-.11,0Q77.39,2.91,75,2.2c-1.59-.47-3.12-.84-4.65-1.15S67.28.5,65.76.33,62.63.05,61,0A56.59,56.59,0,0,0,50.44.78Z\" transform=\"translate(0 0.01)\"/></svg>', '1'),
(155, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 486.569 486.569\" style=\"enable-background:new 0 0 486.569 486.569\" xml:space=\"preserve\"><g><path d=\"M146.069,320.369h268.1c30.4,0,55.2-24.8,55.2-55.2v-112.8c0-0.1,0-0.3,0-0.4c0-0.3,0-0.5,0-0.8c0-0.2,0-0.4-0.1-0.6\r\n    c0-0.2-0.1-0.5-0.1-0.7s-0.1-0.4-0.1-0.6c-0.1-0.2-0.1-0.4-0.2-0.7c-0.1-0.2-0.1-0.4-0.2-0.6c-0.1-0.2-0.1-0.4-0.2-0.6\r\n    c-0.1-0.2-0.2-0.4-0.3-0.7c-0.1-0.2-0.2-0.4-0.3-0.5c-0.1-0.2-0.2-0.4-0.3-0.6c-0.1-0.2-0.2-0.3-0.3-0.5c-0.1-0.2-0.3-0.4-0.4-0.6\r\n   c-0.1-0.2-0.2-0.3-0.4-0.5c-0.1-0.2-0.3-0.3-0.4-0.5s-0.3-0.3-0.4-0.5s-0.3-0.3-0.4-0.4c-0.2-0.2-0.3-0.3-0.5-0.5\r\n   c-0.2-0.1-0.3-0.3-0.5-0.4c-0.2-0.1-0.4-0.3-0.6-0.4c-0.2-0.1-0.3-0.2-0.5-0.3s-0.4-0.2-0.6-0.4c-0.2-0.1-0.4-0.2-0.6-0.3\r\n   s-0.4-0.2-0.6-0.3s-0.4-0.2-0.6-0.3s-0.4-0.1-0.6-0.2c-0.2-0.1-0.5-0.2-0.7-0.2s-0.4-0.1-0.5-0.1c-0.3-0.1-0.5-0.1-0.8-0.1\r\n    c-0.1,0-0.2-0.1-0.4-0.1l-339.8-46.9v-47.4c0-0.5,0-1-0.1-1.4c0-0.1,0-0.2-0.1-0.4c0-0.3-0.1-0.6-0.1-0.9c-0.1-0.3-0.1-0.5-0.2-0.8\r\n    c0-0.2-0.1-0.3-0.1-0.5c-0.1-0.3-0.2-0.6-0.3-0.9c0-0.1-0.1-0.3-0.1-0.4c-0.1-0.3-0.2-0.5-0.4-0.8c-0.1-0.1-0.1-0.3-0.2-0.4\r\n   c-0.1-0.2-0.2-0.4-0.4-0.6c-0.1-0.2-0.2-0.3-0.3-0.5s-0.2-0.3-0.3-0.5s-0.3-0.4-0.4-0.6c-0.1-0.1-0.2-0.2-0.3-0.3\r\n   c-0.2-0.2-0.4-0.4-0.6-0.6c-0.1-0.1-0.2-0.2-0.3-0.3c-0.2-0.2-0.4-0.4-0.7-0.6c-0.1-0.1-0.3-0.2-0.4-0.3c-0.2-0.2-0.4-0.3-0.6-0.5\r\n   c-0.3-0.2-0.6-0.4-0.8-0.5c-0.1-0.1-0.2-0.1-0.3-0.2c-0.4-0.2-0.9-0.4-1.3-0.6l-73.7-31c-6.9-2.9-14.8,0.3-17.7,7.2\r\n   s0.3,14.8,7.2,17.7l65.4,27.6v61.2v9.7v74.4v66.5v84c0,28,21,51.2,48.1,54.7c-4.9,8.2-7.8,17.8-7.8,28c0,30.1,24.5,54.5,54.5,54.5\r\n   s54.5-24.5,54.5-54.5c0-10-2.7-19.5-7.5-27.5h121.4c-4.8,8.1-7.5,17.5-7.5,27.5c0,30.1,24.5,54.5,54.5,54.5s54.5-24.5,54.5-54.5\r\n   s-24.5-54.5-54.5-54.5h-255c-15.6,0-28.2-12.7-28.2-28.2v-36.6C126.069,317.569,135.769,320.369,146.069,320.369z M213.269,431.969\r\n    c0,15.2-12.4,27.5-27.5,27.5s-27.5-12.4-27.5-27.5s12.4-27.5,27.5-27.5S213.269,416.769,213.269,431.969z M428.669,431.969\r\n    c0,15.2-12.4,27.5-27.5,27.5s-27.5-12.4-27.5-27.5s12.4-27.5,27.5-27.5S428.669,416.769,428.669,431.969z M414.169,293.369h-268.1\r\n   c-15.6,0-28.2-12.7-28.2-28.2v-66.5v-74.4v-5l324.5,44.7v101.1C442.369,280.769,429.669,293.369,414.169,293.369z\"/></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(156, '<svg id=\"Layer_1\" data-name=\"Layer 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 300.01\"><path d=\"M149.35,146.64a7.78,7.78,0,0,0-7.78,7.78V209a7.79,7.79,0,0,0,15.57,0V154.42A7.79,7.79,0,0,0,149.35,146.64Z\" transform=\"translate(0)\"/><path d=\"M111.7,146.64a7.78,7.78,0,0,0-7.78,7.78V209a7.78,7.78,0,1,0,15.56,0V154.42A7.78,7.78,0,0,0,111.7,146.64Z\" transform=\"translate(0)\"/><path d=\"M187,146.64a7.78,7.78,0,0,0-7.79,7.78V209a7.79,7.79,0,0,0,15.57,0V154.42A7.78,7.78,0,0,0,187,146.64Z\" transform=\"translate(0)\"/><path d=\"M150,0A150,150,0,1,0,300,150,150,150,0,0,0,150,0Zm81.34,153.21-11.49,68a12,12,0,0,1-12,12h-117a12,12,0,0,1-12-12l-11.49-68A19.43,19.43,0,0,1,78.13,118.6l33.09-48.39A7.78,7.78,0,0,1,124.07,79L97,118.53H201.68L174.64,79a7.78,7.78,0,0,1,12.85-8.78l33.09,48.39a19.43,19.43,0,0,1,10.76,34.61Z\" transform=\"translate(0)\"/></svg>', '1'),
(157, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <svg version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 442.688 442.688\" style=\"enable-background:new 0 0 442.688 442.688\" xml:space=\"preserve\"><g><g><path d=\"M442.666,178.822l-4.004-145.078c-0.447-16.222-13.504-29.279-29.727-29.728l-145.08-4.004\r\n     c-8.475-0.237-16.493,2.97-22.468,8.945L8.954,241.391c-11.924,11.924-11.924,31.325,0,43.249l149.083,149.082\r\n      c11.951,11.953,31.296,11.956,43.25,0.001L433.721,201.29C439.636,195.374,442.897,187.184,442.666,178.822z M376.238,139.979\r\n     c-20.323,20.322-53.215,20.324-73.539,0c-20.275-20.275-20.275-53.265,0-73.539c20.323-20.323,53.215-20.324,73.539,0\r\n     C396.512,86.714,396.512,119.704,376.238,139.979z\"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(158, '<svg id=\"Layer_1\" data-name=\"Layer 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 300.01\"><path d=\"M150,0A150,150,0,1,0,300,150,150,150,0,0,0,150,0Zm82.17,222.64h0a14.74,14.74,0,0,1-14.74,14.74h-9.11V173.62H165.25v63.76H81.08a14.74,14.74,0,0,1-14.73-14.74V146.17a28.71,28.71,0,0,0,18.75-9,28.75,28.75,0,0,0,42,0,28.75,28.75,0,0,0,42,0,28.75,28.75,0,0,0,42,0,28.71,28.71,0,0,0,21,9.13v76.35Zm0-91.91A13.26,13.26,0,0,1,219,117.49a7.78,7.78,0,1,0-15.56,0,13.24,13.24,0,1,1-26.47,0,7.78,7.78,0,0,0-15.56,0,13.24,13.24,0,1,1-26.47,0,7.78,7.78,0,1,0-15.56,0,13.23,13.23,0,1,1-26.46,0,7.79,7.79,0,0,0-15.57,0,13.23,13.23,0,0,1-26.26,2.3L91.21,65.35H207.73l37.59,53.76A13.24,13.24,0,0,1,232.2,130.73Z\" transform=\"translate(0)\"/><rect x=\"89.04\" y=\"174.16\" width=\"47.62\" height=\"33.26\"/></svg>', '1'),
(159, '<svg id=\"Layer_1\" data-name=\"Layer 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 56 60\"><path d=\"M68.4,27.1h0\" transform=\"translate(-22 -20)\"/><path d=\"M39,32H61a2,2,0,0,0,2-2V26a6,6,0,0,0-6-6H43a6,6,0,0,0-6,6v4A2,2,0,0,0,39,32Z\" transform=\"translate(-22 -20)\"/><path d=\"M72,25H70a.94.94,0,0,0-1,1v4a8,8,0,0,1-8,8H39a8,8,0,0,1-8-8V26a.94.94,0,0,0-1-1H28a6,6,0,0,0-6,6V74a6,6,0,0,0,6,6H72a6,6,0,0,0,6-6V31A6,6,0,0,0,72,25ZM64.7,60.2a1.08,1.08,0,0,1-1,1h-11a.68.68,0,0,0-.7.7v11a1.08,1.08,0,0,1-1,1H49a1.08,1.08,0,0,1-1-1v-11a.68.68,0,0,0-.7-.7h-11a1.08,1.08,0,0,1-1-1v-2a1.08,1.08,0,0,1,1-1h11a.68.68,0,0,0,.7-.7v-11a1.08,1.08,0,0,1,1-1h2a1.08,1.08,0,0,1,1,1v11a.68.68,0,0,0,.7.7h11a1.08,1.08,0,0,1,1,1Z\" transform=\"translate(-22 -20)\"/></svg>', '1'),
(160, '<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 297 297\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" enable-background=\"new 0 0 297 297\">\r\n  <g>\r\n    <path d=\"m129.411,77.855v-52.154h-95.818c-7.428,0-13.471,6.043-13.471,13.472v54.265c0,6.664 4.981,12.396 11.587,13.334l4.201,.596 1.457,3.986c1.903,5.209 6.912,8.71 12.463,8.71 5.484,0 10.473-3.447 12.411-8.577l1.731-4.578h66.502c3.449,0 6.596-1.305 8.979-3.445-6.227-6.736-10.042-15.734-10.042-25.609z\"/>\r\n    <path d=\"m167.168,101.445h59.289l2.113,3.041c4.401,6.333 11.595,10.114 19.241,10.114 8.158,0 15.847-4.358 20.066-11.374l1.33-2.211 2.438-.838c9.565-3.287 15.993-12.258 15.993-22.322v-54.264c0.001-13.008-10.582-23.591-23.59-23.591h-96.881c-13.008,0-23.59,10.583-23.59,23.591v54.265c0.001,13.007 10.583,23.589 23.591,23.589zm69.579-60.341c1.893-1.883 4.492-2.966 7.153-2.966 2.673,0 5.272,1.083 7.165,2.966 1.882,1.882 2.965,4.492 2.965,7.153 0,2.662-1.083,5.263-2.965,7.155-1.893,1.882-4.492,2.964-7.165,2.964-2.661,0-5.261-1.082-7.153-2.964-1.883-1.883-2.955-4.493-2.955-7.155 0-2.67 1.072-5.271 2.955-7.153zm-27.13,0c1.882-1.883 4.493-2.966 7.154-2.966s5.273,1.083 7.154,2.966c1.893,1.882 2.965,4.492 2.965,7.153 0,2.662-1.072,5.263-2.965,7.155-1.882,1.882-4.493,2.964-7.154,2.964s-5.272-1.082-7.154-2.964c-1.882-1.883-2.965-4.493-2.965-7.155 0-2.67 1.083-5.271 2.965-7.153zm-27.13,0c1.883-1.883 4.492-2.966 7.154-2.966s5.273,1.083 7.154,2.966c1.883,1.882 2.966,4.492 2.966,7.153 0,2.662-1.083,5.263-2.966,7.155-1.882,1.882-4.492,2.964-7.154,2.964s-5.271-1.082-7.154-2.964c-1.883-1.883-2.965-4.493-2.965-7.155 0-2.67 1.082-5.271 2.965-7.153z\"/>\r\n    <path d=\"m67.545,226.037c23.993,0 43.513-19.52 43.513-43.514 0-23.992-19.519-43.512-43.513-43.512-23.992,0-43.513,19.52-43.513,43.512 0.001,23.994 19.521,43.514 43.513,43.514z\"/>\r\n    <path d=\"m121.066,258.879c-4.515-18.061-22.635-32.208-41.251-32.208h-24.539c-18.616,0-36.735,14.148-41.251,32.208l-6.387,25.547c-0.756,3.024-0.077,6.227 1.841,8.683 1.918,2.456 4.86,3.892 7.977,3.892h100.181c3.115,0 6.059-1.435 7.976-3.892 1.918-2.456 2.597-5.658 1.841-8.683l-6.388-25.547z\"/>\r\n    <path d=\"m289.362,284.427l-5.741-22.968c-4.168-16.67-20.893-29.729-38.077-29.729h-22.061c-17.184,0-33.909,13.059-38.077,29.729l-5.741,22.967c-0.756,3.023-0.077,6.226 1.841,8.682 1.917,2.456 4.86,3.892 7.977,3.892h90.063c3.116,0 6.06-1.435 7.977-3.892 1.916-2.456 2.595-5.658 1.839-8.681z\"/>\r\n    <path d=\"m234.513,144.577c-20.925,0-37.947,17.022-37.947,37.946 0,20.925 17.022,37.947 37.947,37.947s37.947-17.023 37.947-37.947c0.001-20.923-17.022-37.946-37.947-37.946z\"/>\r\n    <path d=\"m185.526,179.653c1.044-18.009 11.841-33.435 27.176-41.077-8.637-6.073-30.703-18.754-52.037-7.779-10.518,5.412-18.33,18.124-20.898,34.005-3.518,21.747 2.97,43.503 17.357,58.195 1.948,1.989 4.57,3.04 7.233,3.04 1.474,0 2.959-0.321 4.35-0.983 3.903-1.86 6.194-5.995 5.701-10.29-0.745-6.483 0.22-27.206 11.118-35.111z\"/>\r\n  </g>\r\n</svg>', '1'),
(161, '<svg id=\"Capa_1\" data-name=\"Capa 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 330.6 489.9\"><path d=\"M131.75,409.1c-28.8,0-52.1,18.3-52.1,40.4,0,22.6,23.3,40.4,52.1,40.4h278.4c-22.6-24.1-22.6-56.8,0-80.9H131.75Z\" transform=\"translate(-79.65)\"/><path d=\"M120.15,1.9c-23.3,7-40.4,32.7-40.4,63.8V407.9c10.5-9.3,24.9-15.9,40.4-17.9Z\" transform=\"translate(-79.65)\"/><polygon points=\"165.3 0 165.3 99.2 125.2 73.9 85.2 99.2 85.2 0 60.3 0 60.3 388.9 330.6 388.9 330.6 0 165.3 0\"/></svg>', '1'),
(162, '<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 297 297\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" enable-background=\"new 0 0 297 297\">\r\n  <g>\r\n    <path d=\"m291.894,231.116h-286.788c-2.815,0-5.106,2.291-5.106,5.106v12.634c0,2.815 2.291,5.106 5.106,5.106h286.788c2.815,0 5.106-2.291 5.106-5.106v-12.634c0-2.815-2.291-5.106-5.106-5.106z\"/>\r\n    <path d=\"m282.462,50.307c0-4.015-3.254-7.269-7.269-7.269h-253.385c-4.015,0-7.269,3.254-7.269,7.269v13.828h267.923v-13.828zm-128.451,9.255c-1.453,1.454-3.458,2.285-5.514,2.285-2.047,0-4.061-0.831-5.504-2.285-1.454-1.443-2.285-3.458-2.285-5.504 0-2.057 0.831-4.061 2.285-5.504 1.443-1.454 3.457-2.285 5.504-2.285 2.056,0 4.061,0.831 5.514,2.285 1.444,1.443 2.275,3.447 2.275,5.504-0.001,2.046-0.831,4.061-2.275,5.504z\"/>\r\n    <path d=\"m170.58,175.687l-18.772,10.066c-2.064,1.107-4.551,1.107-6.615,0l-18.772-10.066-4.871,1.494c-5.968,2.023-9.971,7.604-9.971,13.909v28.603h73.844v-28.603c0-6.306-4.003-11.886-9.971-13.909l-4.872-1.494z\"/>\r\n    <path d=\"m148.5,142.838c8.249,0 14.961-6.712 14.961-14.961s-6.712-14.961-14.961-14.961-14.961,6.712-14.961,14.961 6.712,14.961 14.961,14.961z\"/>\r\n    <path d=\"m97.578,191.09c0-12.367 7.883-23.304 19.615-27.215 0.054-0.018 0.107-0.035 0.161-0.052l7.749-2.377c1.783-0.548 3.715-0.359 5.36,0.523l18.036,9.671 18.036-9.671c1.646-0.883 3.577-1.071 5.36-0.523l7.749,2.377c0.054,0.017 0.107,0.034 0.161,0.052 11.732,3.911 19.615,14.848 19.615,27.215v28.603h82.95v-144.135h-267.832v144.135h83.04v-28.603zm50.922-92.174c15.969,0 28.961,12.992 28.961,28.961s-12.992,28.961-28.961,28.961-28.961-12.992-28.961-28.961 12.992-28.961 28.961-28.961z\"/>\r\n  </g>\r\n</svg>\r\n', '1'),
(163, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\"><svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 325.04 325.04\" style=\"enable-background:new 0 0 325.04 325.04\" xml:space=\"preserve\"><g><path d=\"M117.866,234.088c-2.956,14.532-4.875,21.558-16.092,22.458c-2.764,0.222-5.015,2.308-5.446,5.047\r\n    c-0.432,2.738,1.069,5.416,3.631,6.477c0.721,0.298,17.877,7.308,37.921,7.309c0.003,0,0.005,0,0.007,0\r\n   c13.968,0,25.95-3.386,35.612-10.063c11.45-7.912,19.344-20.294,23.541-36.788l-38.572-38.88\r\n   C125.871,194.924,121.253,217.436,117.866,234.088z\"/><path d=\"M322.745,63.336c-1.037-1.046-2.887-2.293-5.806-2.293c-3.423,0-12.516,0-67.74,46.992\r\n    c-25.509,21.706-54.92,48.559-78.314,71.41l36.603,36.894c24.061-25.009,52.129-56.355,74.451-83.258\r\n   c14.096-16.986,24.935-31.002,32.216-41.657C323.799,77.311,328.023,68.655,322.745,63.336z\"/><path d=\"M182.595,278.479c-12.387,8.56-27.429,12.899-44.716,12.899c-22.753-0.001-41.919-7.649-44.046-8.527\r\n   c-9.425-3.906-14.898-13.673-13.31-23.749c1.555-9.871,9.463-17.373,19.341-18.446c0.861-2.571,1.813-7.254,2.323-9.758\r\n   c1.878-9.23,4.449-21.873,12.358-33.126c8.637-12.287,21.656-20.165,38.751-23.466c9.811-9.737,21.005-20.443,32.686-31.308\r\n   c-5.905-1.281-11.185-5.127-14.017-10.944c-4.875-10.02-0.623-22.073,9.484-26.895c10.133-4.834,22.287-0.612,27.155,9.423\r\n    c0.961,1.978,1.555,4.033,1.832,6.096c9.688-8.677,19.309-17.099,28.392-24.828c0.054-0.046,0.105-0.09,0.16-0.136\r\n    c-10.209-19.536-24.849-36.845-42.687-50.098c-25.614-19.031-56.114-29.096-88.2-29.104c-0.01,0-0.017,0-0.025,0\r\n    c-21.654,0-47.976,7.566-68.697,19.749C13.981,51.193-0.005,71.163,0,92.49c0.008,25.748,14.53,36.518,26.199,45.171\r\n    c9.515,7.057,17.03,12.63,17.034,24.844c0.003,12.213-7.508,17.781-17.018,24.831c-11.665,8.648-26.184,19.412-26.176,45.163\r\n    c0.006,21.324,14.001,41.299,39.406,56.244c20.736,12.198,47.072,19.78,68.73,19.786c0.015,0,0.028,0,0.042,0\r\n   c39.305,0,76.254-15.171,104.044-42.72c20.837-20.655,34.656-46.416,40.273-74.442c-13.952,15.471-27.997,30.493-40.563,43.322\r\n    C206.641,253.965,196.773,268.682,182.595,278.479z M111.054,77.103c2.498-10.871,13.4-17.657,24.354-15.167\r\n    c10.939,2.478,17.793,13.282,15.313,24.138c-2.499,10.844-13.407,17.631-24.362,15.154\r\n   C115.411,98.764,108.554,87.947,111.054,77.103z M45.054,114.152c-7.005-8.716-5.565-21.401,3.216-28.339\r\n   c8.78-6.925,21.571-5.505,28.589,3.195c6.99,8.703,5.545,21.388-3.229,28.34C64.869,124.288,52.058,122.853,45.054,114.152z\r\n    M55.746,247.168c-8.786-6.944-10.231-19.629-3.226-28.342c7-8.696,19.796-10.122,28.581-3.18\r\n    c8.778,6.943,10.224,19.629,3.225,28.327C77.327,252.686,64.53,254.111,55.746,247.168z\"/></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(164, '<svg id=\"Capa_ 164\" data-name=\"Capa 164\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 351.3 331.54\"><g id=\"Artwork\"><g id=\"Layer_5\" data-name=\"Layer 5\"><path d=\"M336.33,178.53c20-22.29,20.21-57.08-1-78.27-20.85-20.85-57.32-10.84-60.45-14-2.74-2.74,7.81-38.67-14-60.46-21.2-21.19-56-21-78.28-1C170.05,36.16,164.8,49.11,162,62.78c-3.55,17.26-1.8,58,2.54,88.44.1.72.33,2.56-.64,3.54-16.74,16.73-38.35,38.34-48.7,48.7-1.54,1.54-3.35-.15-3.35-.15L91.45,182.9a14.79,14.79,0,0,0-20.92,20.91l20.34,20.34s2.16,1.48.06,3.58l-9.13,9.13a1.8,1.8,0,0,1-2.61,0L58.34,216a14.79,14.79,0,1,0-20.91,20.92l20.71,20.71a2,2,0,0,1,0,2.92l-9.56,9.56c-1.19,1.19-2.55-.25-2.55-.25L25.24,249.11A14.79,14.79,0,0,0,4.33,270L25,290.7c1.6,1.61.17,2.77.17,2.77-11.74,11.74-2.51,21.54,9.23,33.28S56,347.73,67.71,336L206.25,197.45a3.64,3.64,0,0,1,3.27-.68c30.38,4.32,71.44,6,88.87,2.41C312.11,196.35,325,191.13,336.33,178.53Zm-124.8-28.89a32.63,32.63,0,1,1,46.15,0A32.64,32.64,0,0,1,211.53,149.64Z\" transform=\"translate(0 -9.88)\"/></g></g></svg>', '1'),
(165, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\"><svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" width=\"337.578px\" height=\"337.579px\" viewBox=\"0 0 337.578 337.579\" style=\"enable-background:new 0 0 337.578 337.579\" xml:space=\"preserve\"><g><g><g><path d=\"M274.107,130.68c-14.646-26.151-43.487-41.154-74.424-36.215c-12.147-25.487-37.536-41.673-66.098-41.673\r\n        c-40.421,0-73.318,32.897-73.318,73.334c0,1.666,0.066,3.386,0.216,5.17C25.593,139.254,0,170.75,0,207.025\r\n       c0,42.88,34.879,77.756,77.753,77.756h57.604h3.849h14.382v-86.235l-10.863,10.863c-5.702,5.705-15.646,5.705-21.344,0\r\n        c-2.846-2.847-4.428-6.642-4.428-10.671c0-4.035,1.576-7.812,4.435-10.658l36.635-36.653c2.798-2.801,6.689-4.407,10.661-4.407\r\n        l1.465,0.048c3.54,0.285,6.836,1.802,9.395,4.341l36.659,36.671c2.852,2.847,4.425,6.63,4.425,10.665\r\n       c0,4.022-1.573,7.83-4.432,10.676c-5.68,5.699-15.642,5.699-21.341,0l-11.085-11.103v86.469h76.057\r\n       c42.875,0,77.751-34.882,77.751-77.756C337.578,169.717,310.515,137.471,274.107,130.68z\"/></g></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(166, '<svg id=\"Layer_1\" data-name=\"Layer 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 270.61 309.27\"><defs><style>.cls-1{fill:#e2574c;}.cls-2{fill:#b53629;}.cls-3{fill:#fff;}</style></defs><path class=\"cls-1\" d=\"M38.66,0H202.89l87.05,86.71V289.94a19.33,19.33,0,0,1-19.33,19.33H38.66a19.33,19.33,0,0,1-19.33-19.33V19.33A19.33,19.33,0,0,1,38.66,0Z\" transform=\"translate(-19.33)\"/><path class=\"cls-2\" d=\"M289.66,87H222.29A19.34,19.34,0,0,1,203,67.65V.19Z\" transform=\"translate(-19.33)\"/><path class=\"cls-3\" d=\"M217.43,146.54c3.24,0,4.83-2.82,4.83-5.55s-1.66-5.57-4.83-5.57H199c-3.6,0-5.61,3-5.61,6.28V187c0,4,2.3,6.28,5.41,6.28s5.4-2.24,5.4-6.28V174.58h11.16c3.46,0,5.19-2.83,5.19-5.64s-1.73-5.49-5.19-5.49H204.19V146.54Zm-62.32-11.12h-13.5a6,6,0,0,0-6.26,6.24v45.4c0,4.63,3.74,6.08,6.42,6.08h14.16c16.76,0,27.82-11,27.82-28C183.74,147.1,173.32,135.42,155.11,135.42Zm.65,46.53h-8.23V146.61h7.41c11.22,0,16.1,7.53,16.1,17.92C171,174.25,166.25,182,155.76,182Zm-49.43-46.53H93c-3.78,0-5.88,2.49-5.88,6.28V187c0,4,2.41,6.28,5.66,6.28s5.66-2.24,5.66-6.28V173.79h8.38c10.34,0,18.88-7.33,18.88-19.11C125.66,143.15,117.43,135.42,106.33,135.42Zm-.22,27.74H98.4v-17.1h7.71c4.75,0,7.78,3.71,7.78,8.55S110.86,163.16,106.11,163.16Z\" transform=\"translate(-19.33)\"/></svg>', '1');
INSERT INTO `i_svg_icons` (`icon_id`, `icon_code`, `icon_status`) VALUES
(167, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\"><svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 56 56\" style=\"enable-background:new 0 0 56 56\" xml:space=\"preserve\"><g><path style=\"fill:#e9e9e0\" d=\"M36.985,0H7.963C7.155,0,6.5,0.655,6.5,1.926V55c0,0.345,0.655,1,1.463,1h40.074\r\n    c0.808,0,1.463-0.655,1.463-1V12.978c0-0.696-0.093-0.92-0.257-1.085L37.607,0.257C37.442,0.093,37.218,0,36.985,0z\"/><polygon style=\"fill:#d9d7ca\" points=\"37.5,0.151 37.5,12 49.349,12  \"/><path style=\"fill:#556080\" d=\"M48.037,56H7.963C7.155,56,6.5,55.345,6.5,54.537V39h43v15.537C49.5,55.345,48.845,56,48.037,56z\"/><g><path style=\"fill:#fff\" d=\"M25.266,42.924v1.326l-4.799,7.205l-0.273,0.219h5.072V53h-6.699v-1.326l4.799-7.205l0.287-0.219\r\n      h-5.086v-1.326H25.266z\"/><path style=\"fill:#fff\" d=\"M29.271,53h-1.668V42.924h1.668V53z\"/><path style=\"fill:#fff\" d=\"M33.414,53h-1.641V42.924h2.898c0.428,0,0.852,0.068,1.271,0.205\r\n      c0.419,0.137,0.795,0.342,1.128,0.615c0.333,0.273,0.602,0.604,0.807,0.991s0.308,0.822,0.308,1.306\r\n      c0,0.511-0.087,0.973-0.26,1.388c-0.173,0.415-0.415,0.764-0.725,1.046c-0.31,0.282-0.684,0.501-1.121,0.656\r\n      s-0.921,0.232-1.449,0.232h-1.217V53z M33.414,44.168v3.992h1.504c0.2,0,0.398-0.034,0.595-0.103\r\n     c0.196-0.068,0.376-0.18,0.54-0.335s0.296-0.371,0.396-0.649c0.1-0.278,0.15-0.622,0.15-1.032c0-0.164-0.023-0.354-0.068-0.567\r\n      c-0.046-0.214-0.139-0.419-0.28-0.615c-0.142-0.196-0.34-0.36-0.595-0.492c-0.255-0.132-0.593-0.198-1.012-0.198H33.414z\"/></g><g><path style=\"fill:#c8bdb8\" d=\"M28.5,24v-2h2v-2h-2v-2h2v-2h-2v-2h2v-2h-2v-2h2V8h-2V6h-2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2\r\n      h-4v5c0,2.757,2.243,5,5,5s5-2.243,5-5v-5H28.5z M30.5,29c0,1.654-1.346,3-3,3s-3-1.346-3-3v-3h6V29z\"/><path style=\"fill:#c8bdb8\" d=\"M26.5,30h2c0.552,0,1-0.447,1-1s-0.448-1-1-1h-2c-0.552,0-1,0.447-1,1S25.948,30,26.5,30z\"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(168, '<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 496.188 496.188\" style=\"enable-background:new 0 0 496.188 496.188\" xml:space=\"preserve\"><g><path d=\"M196.698,108.246c-5.006-16.197-19.999-27.241-36.96-27.241h-41.996c-17.006,0-32.037,11.116-37.01,27.396L1.629,367.257\r\n    c-3.45,11.247-1.355,23.452,5.657,32.902c6.992,9.447,18.05,15.025,29.811,15.025c16.67,0,31.287-11.116,35.747-27.167\r\n    l16.263-58.612h95.689l17.514,58.246c4.91,16.345,19.953,27.533,37.051,27.533c12.307,0,23.871-5.846,31.156-15.742\r\n   c7.301-9.904,9.448-22.679,5.809-34.42L196.698,108.246z M100.018,272.891l19.808-70.913c5.446-19.337,10.426-44.609,15.398-64.451\r\n    h0.946c4.974,19.842,10.959,44.625,16.881,64.451l20.832,70.913H100.018z\"/><path d=\"M495.093,362.394v-69.026c0-46.676-21.128-85.658-87.331-85.658c-24.577,0-45.017,4.506-59.917,9.814\r\n   c-9.902,3.531-15.35,14.109-12.473,24.217l0.197,0.665c1.511,5.319,5.156,9.783,10.065,12.352c4.895,2.571,10.62,3.025,15.87,1.253\r\n    c10.85-3.661,23.292-6.082,35.309-6.082c30.462,0,36.154,15.028,36.154,25.582v2.853c-70.256-0.415-116.538,24.346-116.538,75.915\r\n   c0,31.68,23.953,60.897,64.171,60.897c23.534,0,43.813-8.538,56.824-24.37h1.198l1.596,8.685\r\n   c1.207,6.496,6.864,11.221,13.482,11.221h28.767c3.824,0,7.467-1.587,10.054-4.384c2.602-2.797,3.919-6.554,3.626-10.358\r\n    C495.4,386.195,495.093,374.461,495.093,362.394z M434.967,338.44c0,3.649-0.389,7.311-1.218,10.554\r\n    c-4.053,12.588-16.639,22.736-32.072,22.736c-13.792,0-24.36-7.71-24.36-23.541c0-23.955,25.188-31.664,57.65-31.267V338.44z\"/></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(169, '<svg id=\"Capa_1\" data-name=\"Capa 1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 471.2 436.7\"><path d=\"M457.7,230.15a13.44,13.44,0,0,0-13.5,13.5v122.8A60.58,60.58,0,0,1,383.7,427H87.5A60.58,60.58,0,0,1,27,366.45V241.65a13.5,13.5,0,0,0-27,0v124.8A87.6,87.6,0,0,0,87.5,454H383.7a87.6,87.6,0,0,0,87.5-87.5V243.65A13.51,13.51,0,0,0,457.7,230.15Z\" transform=\"translate(0 -17.25)\"/><path d=\"M159.3,126.15l62.8-62.8v273.9a13.5,13.5,0,0,0,27,0V63.35l62.8,62.8a13.59,13.59,0,0,0,9.5,4,13,13,0,0,0,9.5-4,13.46,13.46,0,0,0,0-19.1l-85.8-85.8a13.66,13.66,0,0,0-9.5-4,13.07,13.07,0,0,0-9.5,4l-85.8,85.8a13.47,13.47,0,0,0,19,19.1Z\" transform=\"translate(0 -17.25)\"/></svg>', '1'),
(170, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 489 489\" style=\"enable-background:new 0 0 489 489\" xml:space=\"preserve\"><g><g><path d=\"M329.2,327.2c-4.5,0-8.1,3.4-8.6,7.9c-3.9,38.6-36.5,68.7-76.2,68.7c-39.6,0-72.2-30.1-76.2-68.7\r\n      c-0.5-4.4-4.1-7.9-8.6-7.9h-104c-21.8,0-39.5,17.7-39.5,39.5v82.8c0,21.8,17.7,39.5,39.5,39.5h377.8c21.8,0,39.5-17.7,39.5-39.5\r\n     v-82.7c0-21.8-17.7-39.5-39.5-39.5H329.2V327.2z\"/><path d=\"M303.5,198.6l-30.9,30.9V28.1C272.6,12.6,260,0,244.5,0l0,0c-15.5,0-28.1,12.6-28.1,28.1v201.4l-30.9-30.9\r\n      c-9.5-9.5-24.7-11.9-35.9-4.4c-15.3,10.2-16.8,31.1-4.5,43.4l82.8,82.8c9.2,9.2,24.1,9.2,33.3,0l82.8-82.8\r\n      c12.3-12.3,10.8-33.2-4.5-43.4C328.2,186.6,313,189,303.5,198.6z\"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(171, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <svg version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 300.005 300.005\" style=\"enable-background:new 0 0 300.005 300.005\" xml:space=\"preserve\"><g><g><path d=\"M150,0C67.159,0,0.002,67.162,0.002,150S67.159,300.005,150,300.005c82.843,0,150.003-67.165,150.003-150.005\r\n     S232.843,0,150,0z M135.499,177.77l-16.809-9.228h-10.019v-0.003l-10.105,35.525H72.181l10.105-35.525h-0.973\r\n     c-16.086,0-29.126-13.038-29.126-29.129c0-16.086,13.041-29.124,29.126-29.124h37.377l16.809-9.228V177.77z M168.163,195.695\r\n      l-17.102-9.386V93.602h-1.982l19.081-10.476c6.995,0,12.665,5.67,12.665,12.665v87.239h0.003\r\n     C180.828,190.025,175.158,195.695,168.163,195.695z M202.404,167.139c4.682-6.481,7.472-14.415,7.472-23\r\n      c0-8.585-2.791-16.519-7.472-23l9.057-9.057c6.935,8.847,11.077,19.974,11.077,32.057c0,12.083-4.145,23.21-11.077,32.057\r\n     L202.404,167.139z M227.581,192.315l-8.982-8.982c8.722-10.701,13.967-24.346,13.967-39.195s-5.242-28.494-13.967-39.195\r\n      l8.982-8.982c11.002,13.023,17.655,29.835,17.655,48.177S238.583,179.295,227.581,192.315z\"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(172, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?> <svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 473.806 473.806\" style=\"enable-background:new 0 0 473.806 473.806\" xml:space=\"preserve\"><g><g><path d=\"M374.456,293.506c-9.7-10.1-21.4-15.5-33.8-15.5c-12.3,0-24.1,5.3-34.2,15.4l-31.6,31.5c-2.6-1.4-5.2-2.7-7.7-4\r\n      c-3.6-1.8-7-3.5-9.9-5.3c-29.6-18.8-56.5-43.3-82.3-75c-12.5-15.8-20.9-29.1-27-42.6c8.2-7.5,15.8-15.3,23.2-22.8\r\n     c2.8-2.8,5.6-5.7,8.4-8.5c21-21,21-48.2,0-69.2l-27.3-27.3c-3.1-3.1-6.3-6.3-9.3-9.5c-6-6.2-12.3-12.6-18.8-18.6\r\n      c-9.7-9.6-21.3-14.7-33.5-14.7s-24,5.1-34,14.7c-0.1,0.1-0.1,0.1-0.2,0.2l-34,34.3c-12.8,12.8-20.1,28.4-21.7,46.5\r\n      c-2.4,29.2,6.2,56.4,12.8,74.2c16.2,43.7,40.4,84.2,76.5,127.6c43.8,52.3,96.5,93.6,156.7,122.7c23,10.9,53.7,23.8,88,26\r\n      c2.1,0.1,4.3,0.2,6.3,0.2c23.1,0,42.5-8.3,57.7-24.8c0.1-0.2,0.3-0.3,0.4-0.5c5.2-6.3,11.2-12,17.5-18.1c4.3-4.1,8.7-8.4,13-12.9\r\n      c9.9-10.3,15.1-22.3,15.1-34.6c0-12.4-5.3-24.3-15.4-34.3L374.456,293.506z M410.256,398.806\r\n     C410.156,398.806,410.156,398.906,410.256,398.806c-3.9,4.2-7.9,8-12.2,12.2c-6.5,6.2-13.1,12.7-19.3,20\r\n      c-10.1,10.8-22,15.9-37.6,15.9c-1.5,0-3.1,0-4.6-0.1c-29.7-1.9-57.3-13.5-78-23.4c-56.6-27.4-106.3-66.3-147.6-115.6\r\n      c-34.1-41.1-56.9-79.1-72-119.9c-9.3-24.9-12.7-44.3-11.2-62.6c1-11.7,5.5-21.4,13.8-29.7l34.1-34.1c4.9-4.6,10.1-7.1,15.2-7.1\r\n      c6.3,0,11.4,3.8,14.6,7c0.1,0.1,0.2,0.2,0.3,0.3c6.1,5.7,11.9,11.6,18,17.9c3.1,3.2,6.3,6.4,9.5,9.7l27.3,27.3\r\n      c10.6,10.6,10.6,20.4,0,31c-2.9,2.9-5.7,5.8-8.6,8.6c-8.4,8.6-16.4,16.6-25.1,24.4c-0.2,0.2-0.4,0.3-0.5,0.5\r\n      c-8.6,8.6-7,17-5.2,22.7c0.1,0.3,0.2,0.6,0.3,0.9c7.1,17.2,17.1,33.4,32.3,52.7l0.1,0.1c27.6,34,56.7,60.5,88.8,80.8\r\n      c4.1,2.6,8.3,4.7,12.3,6.7c3.6,1.8,7,3.5,9.9,5.3c0.4,0.2,0.8,0.5,1.2,0.7c3.4,1.7,6.6,2.5,9.9,2.5c8.3,0,13.5-5.2,15.2-6.9\r\n     l34.2-34.2c3.4-3.4,8.8-7.5,15.1-7.5c6.2,0,11.3,3.9,14.4,7.3c0.1,0.1,0.1,0.1,0.2,0.2l55.1,55.1\r\n     C420.456,377.706,420.456,388.206,410.256,398.806z\"/><path d=\"M256.056,112.706c26.2,4.4,50,16.8,69,35.8s31.3,42.8,35.8,69c1.1,6.6,6.8,11.2,13.3,11.2c0.8,0,1.5-0.1,2.3-0.2\r\n     c7.4-1.2,12.3-8.2,11.1-15.6c-5.4-31.7-20.4-60.6-43.3-83.5s-51.8-37.9-83.5-43.3c-7.4-1.2-14.3,3.7-15.6,11\r\n      S248.656,111.506,256.056,112.706z\"/><path d=\"M473.256,209.006c-8.9-52.2-33.5-99.7-71.3-137.5s-85.3-62.4-137.5-71.3c-7.3-1.3-14.2,3.7-15.5,11\r\n      c-1.2,7.4,3.7,14.3,11.1,15.6c46.6,7.9,89.1,30,122.9,63.7c33.8,33.8,55.8,76.3,63.7,122.9c1.1,6.6,6.8,11.2,13.3,11.2\r\n      c0.8,0,1.5-0.1,2.3-0.2C469.556,223.306,474.556,216.306,473.256,209.006z\"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(173, '<svg id=\"Layer_173\" data-name=\"Layer 173\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 36 36\"><defs><style>.cls-173{fill-opacity:0;}</style></defs><path d=\"M30,17H28a9.66,9.66,0,0,1-1.4,5l1.5,1.5A13.62,13.62,0,0,0,30,17Z\"/><path d=\"M25,17V9A7,7,0,0,0,11.4,6.8l13,13A7.06,7.06,0,0,0,25,17Z\"/><path d=\"M25.2,26.6l6.9,6.9,1.4-1.4L4,2.6,2.6,4,11,12.4V17a7,7,0,0,0,7,7,6.53,6.53,0,0,0,3.6-1l2.2,2.2A10,10,0,0,1,18,27,9.71,9.71,0,0,1,8,17.6V17H6A12.22,12.22,0,0,0,17,29v3H14a1,1,0,0,0,0,2h8a1,1,0,0,0,0-2H19V29A11.9,11.9,0,0,0,25.2,26.6Z\"/><rect class=\"cls-173\" width=\"36\" height=\"36\"/></svg>', '1'),
(174, '<svg id=\"Layer_174\" data-name=\"Layer 174\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 20 18.97\"><path d=\"M11.33,13.89l-7.21,7.2a1,1,0,0,1-1.41,0,1,1,0,0,1,0-1.42l0,0L10,12.35A2,2,0,0,1,11.65,10a2.06,2.06,0,0,1,.7,0l7.33-7.32a1,1,0,0,1,1.41,1.41l-7.2,7.21a2,2,0,0,1-1.22,2.56,2.05,2.05,0,0,1-1.34,0Zm7.54-6.13C20.75,8.84,22,10.34,22,12c0,3.31-5,6-10,6A14.83,14.83,0,0,1,9,17.68l1.86-1.86a4,4,0,0,0,5-2.62,4.06,4.06,0,0,0,0-2.39Zm-4.16-1.5L12.88,8.1A4,4,0,0,0,8.1,12.88L4.87,16.1C3.13,15,2,13.59,2,12,2,8.69,7,6,12,6A14.39,14.39,0,0,1,14.71,6.26Z\" transform=\"translate(-2 -2.43)\"/></svg>', '1'),
(175, '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?><svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 235.517 235.517\" style=\"enable-background:new 0 0 235.517 235.517;\" xml:space=\"preserve\"><g><path d=\"M118.1,235.517c7.898,0,14.31-6.032,14.31-13.483c0-7.441,0-13.473,0-13.473c39.069-3.579,64.932-24.215,64.932-57.785v-0.549c0-34.119-22.012-49.8-65.758-59.977V58.334c6.298,1.539,12.82,3.72,19.194,6.549c10.258,4.547,22.724,1.697,28.952-8.485c6.233-10.176,2.866-24.47-8.681-29.654c-11.498-5.156-24.117-8.708-38.095-10.236V8.251c0-4.552-6.402-8.251-14.305-8.251c-7.903,0-14.31,3.514-14.31,7.832c0,4.335,0,7.843,0,7.843c-42.104,3.03-65.764,25.591-65.764,58.057v0.555c0,34.114,22.561,49.256,66.862,59.427v33.021c-10.628-1.713-21.033-5.243-31.623-10.65c-11.281-5.755-25.101-3.72-31.938,6.385c-6.842,10.1-4.079,24.449,7.294,30.029c16.709,8.208,35.593,13.57,54.614,15.518v13.755C103.79,229.36,110.197,235.517,118.1,235.517z M131.301,138.12c14.316,4.123,18.438,8.257,18.438,15.681v0.555c0,7.979-5.776,12.651-18.438,14.033V138.12z M86.999,70.153v-0.549c0-7.152,5.232-12.657,18.71-13.755v29.719C90.856,81.439,86.999,77.305,86.999,70.153z\"/></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>', '1'),
(176, '<svg width=\"512px\" height=\"512px\" viewBox=\"0 0 512 512\" style=\"enable-background:new 0 0 512 512;\" version=\"1.1\" xml:space=\"preserve\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\"><g id=\"Layer_1\"/><g id=\"Layer_2\"><g><path class=\"st0\" d=\"M392.35,243.06c-5.61-18.87-24.15-37.59-43.07-44.09v-68.19c0-24.92-9.71-48.35-27.32-65.95    C304.35,47.2,280.92,37.5,256,37.5c-51.44,0-93.28,41.85-93.28,93.28c0,8.84,7.16,16,16,16s16-7.16,16-16    c0-33.79,27.49-61.28,61.28-61.28c16.37,0,31.76,6.37,43.33,17.96c11.58,11.57,17.95,26.95,17.95,43.32v61.59    c-46.31-7.37-92.11-6.42-138.56,2.87c-4.42,0.88-8.85,1.84-13.29,2.87c-19.83,5.56-39.9,25.18-45.78,44.95    c-14.15,57.29-14.15,112.56,0,169.85c5.88,19.76,25.95,39.39,45.78,44.95c60.82,14.19,120.32,14.19,181.14,0    c19.83-5.56,39.9-25.19,45.78-44.95C406.5,355.62,406.5,300.35,392.35,243.06z M280.34,341.72v41.58    c0,13.44-10.9,24.34-24.34,24.34s-24.34-10.9-24.34-24.34v-41.58c-15.23-8.54-25.53-24.82-25.53-43.53    c0-27.54,22.33-49.87,49.87-49.87s49.87,22.33,49.87,49.87C305.87,316.9,295.57,333.19,280.34,341.72z\"/></g></g></svg>', '1'),
(177, '<?xml version=\"1.0\" encoding=\"utf-8\"?><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->\r\n<svg width=\"800px\" height=\"800px\" viewBox=\"0 0 512 512\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" aria-hidden=\"true\" role=\"img\" class=\"iconify iconify--fxemoji\" preserveAspectRatio=\"xMidYMid meet\"><path fill=\"#FFB636\" d=\"M204.854 309.75l.668 1.748c.208.564.522 1.32.791 2.115l.971 2.795c.38 1.069.689 2.149 1.094 3.4c.386 1.234.843 2.615 1.201 3.973l1.25 4.47c.412 1.558.758 3.122 1.175 4.826a162.186 162.186 0 0 1 3.798 22.366c.426 4.01.607 8.007.696 12.117c.006 4.028-.091 8.159-.468 12.194a101.15 101.15 0 0 1-1.825 11.94a84.231 84.231 0 0 1-3.351 11.379c-2.757 7.354-6.624 14.15-11.647 20.157c-2.485 3.031-5.389 5.734-8.266 8.533c-2.918 2.758-5.904 5.517-9.021 8.191a238.747 238.747 0 0 1-19.729 15.385c-6.908 4.858-14.174 9.358-21.627 13.487a259.059 259.059 0 0 1-11.347 5.865a260.232 260.232 0 0 1-11.582 5.217c-7.784 3.247-15.627 6.066-23.403 8.401a238.5 238.5 0 0 1-22.815 5.681c-14.714 2.934-28.219 4.137-38.257 4.394l-3.589.062l-3.223-.003l-5.218-.139l-3.345-.196l-1.182-.102l-.102-1.182l-.196-3.345l-.139-5.218l-.003-3.223l.062-3.589c.257-10.038 1.459-23.543 4.394-38.257a238.936 238.936 0 0 1 5.68-22.815c2.335-7.776 5.154-15.62 8.401-23.403a260.75 260.75 0 0 1 5.217-11.582c1.86-3.816 3.81-7.611 5.865-11.347c4.129-7.453 8.629-14.719 13.487-21.627a238.532 238.532 0 0 1 15.385-19.729c2.674-3.117 5.432-6.104 8.191-9.021c2.799-2.877 5.502-5.781 8.533-8.266c6.008-5.023 12.803-8.89 20.157-11.647a84.376 84.376 0 0 1 11.379-3.351a101.15 101.15 0 0 1 11.94-1.825c4.035-.377 8.167-.474 12.194-.468c4.11.088 8.106.27 12.117.696a162.035 162.035 0 0 1 22.366 3.798c1.703.416 3.267.763 4.826 1.175l4.47 1.25c1.358.359 2.74.815 3.973 1.201c1.252.404 2.331.714 3.4 1.094l2.795.971c.795.269 1.551.583 2.115.791c1.149.435 1.744.663 1.744.663z\"></path><path fill=\"#FF473E\" d=\"M76.86 181.752L4.346 254.266c-5.012 5.012-1.462 13.582 5.626 13.582h60.945c2.11 0 4.134.838 5.626 2.33l39.572 39.572l64.271-130.328h-97.9a7.952 7.952 0 0 0-5.626 2.33z\"></path><path fill=\"#FF473E\" d=\"M216.161 392.592l39.572 39.572a7.957 7.957 0 0 1 2.33 5.626v60.945c0 7.088 8.57 10.638 13.582 5.626l72.514-72.514a7.957 7.957 0 0 0 2.33-5.626v-97.9l-130.328 64.271z\"></path><path fill=\"#ADBBBC\" d=\"M505.647 17.875l.077.637c.047.419.139 1.065.205 1.862c.131 1.593.428 4.061.569 7.033c.452 6.114.756 14.721.543 25.076c-.207 10.361-.937 22.464-2.519 35.602c-.354 3.326-.859 6.619-1.347 10.041c-.453 3.457-1.098 6.834-1.687 10.373c-1.21 7.047-2.72 14.218-4.463 21.533a361.335 361.335 0 0 1-13.805 44.697c-5.738 15.021-12.624 30.025-20.695 44.6c-8.083 14.563-17.278 28.768-27.555 42.27c-10.263 13.515-21.594 26.34-33.751 38.338c-12.157 11.999-25.124 23.187-38.748 33.341c-3.407 2.537-6.834 5.032-10.338 7.419c-3.466 2.426-7.007 4.746-10.536 7.044c-7.092 4.562-14.334 8.831-21.651 12.837a361.966 361.966 0 0 1-44.669 20.626a372.522 372.522 0 0 1-44.644 13.858c-7.299 1.759-14.432 3.307-21.467 4.528l-5.179.933c-1.716.294-3.457.536-5.147.801l-5.015.765l-4.974.633c-13.067 1.653-25.09 2.463-35.375 2.746c-10.274.294-18.832.04-24.876-.343c-2.942-.111-5.394-.393-6.968-.504c-.789-.058-1.43-.145-1.844-.187l-.631-.07c-4.782-.533-8.409-4.329-8.919-8.919l-.07-.63c-.042-.414-.129-1.055-.187-1.844c-.111-1.574-.393-4.025-.504-6.968c-.382-6.044-.636-14.601-.343-24.876c.283-10.285 1.093-22.308 2.746-35.375l.633-4.974l.765-5.015c.265-1.69.508-3.43.801-5.147l.933-5.179c1.221-7.035 2.769-14.168 4.528-21.467a372.104 372.104 0 0 1 13.858-44.644a362.26 362.26 0 0 1 20.626-44.669c4.007-7.316 8.276-14.559 12.837-21.651c2.298-3.529 4.618-7.07 7.044-10.536c2.387-3.505 4.882-6.931 7.419-10.338c10.154-13.624 21.342-26.592 33.341-38.748c11.998-12.157 24.823-23.488 38.338-33.751c13.502-10.276 27.707-19.472 42.27-27.555c14.575-8.071 29.58-14.957 44.6-20.695a361.335 361.335 0 0 1 44.697-13.805c7.315-1.743 14.486-3.253 21.533-4.463c3.539-.589 6.916-1.234 10.373-1.687c3.422-.488 6.714-.994 10.041-1.347c13.138-1.582 25.241-2.312 35.602-2.519c10.355-.213 18.962.09 25.076.543c2.972.141 5.44.439 7.033.569c.797.066 1.443.157 1.862.205l.637.077c4.769.575 8.37 4.354 8.92 8.919z\"></path><path fill=\"#FF473E\" d=\"M243.106 271.497l.583 1.577c.325.981.763 2.392 1.065 3.964c.716 3.255.953 7.217-.341 10.663c-.638 1.732-1.818 3.176-2.929 4.922l-3.693 5.555l-8.865 13.312a1631.323 1631.323 0 0 1-10.652 15.587a1262.693 1262.693 0 0 1-12.115 17.172l-6.51 8.959l-6.764 9.085c-4.589 6.076-9.285 12.215-14.06 18.274a1161.386 1161.386 0 0 1-14.364 17.97c-4.781 5.885-9.469 11.692-14.083 17.235l-6.769 8.192c-2.242 2.646-4.281 5.389-6.649 7.677c-2.353 2.302-5.068 4.116-8.188 5.376c-3.093 1.287-6.479 2.133-9.753 2.923a195.747 195.747 0 0 1-18.695 3.482c-2.904.397-5.681.709-8.304.944c-2.609.248-5.133.347-7.345.506c-2.229.141-4.357.129-6.135.192c-1.805.036-3.43-.023-4.702-.025c-1.299-.029-2.337-.094-3.016-.117l-1.066-.072l-.072-1.066c-.023-.679-.088-1.718-.117-3.016c-.002-1.272-.061-2.896-.025-4.702c.062-1.779.051-3.906.192-6.135c.158-2.212.258-4.736.506-7.345c.234-2.622.546-5.4.944-8.304a195.747 195.747 0 0 1 3.482-18.695c.79-3.273 1.636-6.659 2.923-9.753c1.26-3.121 3.074-5.835 5.376-8.188c2.288-2.367 5.031-4.406 7.678-6.648l8.192-6.769c5.543-4.614 11.35-9.303 17.235-14.083c5.875-4.79 11.893-9.607 17.97-14.364c6.059-4.776 12.198-9.471 18.274-14.06l9.085-6.764l8.959-6.51a1271.61 1271.61 0 0 1 17.172-12.115c5.5-3.811 10.739-7.375 15.587-10.652l13.312-8.865l5.555-3.693c1.745-1.111 3.189-2.291 4.922-2.929c3.446-1.294 7.408-1.057 10.663-.341c1.572.302 2.983.74 3.964 1.065l1.573.579z\"></path><path fill=\"#2B3B47\" d=\"M362.113 206.66c0 29.917-24.253 54.17-54.17 54.17s-54.17-24.253-54.17-54.17s24.253-54.17 54.17-54.17s54.17 24.253 54.17 54.17zm27.229-115.881c-19.044 0-34.482 15.438-34.482 34.482s15.438 34.482 34.482 34.482s34.482-15.438 34.482-34.482s-15.438-34.482-34.482-34.482z\"></path></svg>', '1'),
(178, '<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">\r\n<svg version=\"1.1\" id=\"_x32_\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" \r\n  width=\"800px\" height=\"800px\" viewBox=\"0 0 512 512\"  xml:space=\"preserve\">\r\n<g>\r\n <path d=\"M127.083,247.824l50.031-76.906c0,0-74.734-29.688-109.547-3.078C32.755,194.465,0.005,268.184,0.005,268.184\r\n   l37.109,21.516C37.114,289.699,84.083,198.684,127.083,247.824z\"/>\r\n <path d=\"M264.177,384.918l76.906-50.031c0,0,29.688,74.734,3.078,109.547\r\n    c-26.625,34.797-100.344,67.563-100.344,67.563l-21.5-37.109C222.317,474.887,313.333,427.918,264.177,384.918z\"/>\r\n <path d=\"M206.692,362.887l-13.203-13.188c-24,62.375-80.375,49.188-80.375,49.188s-13.188-56.375,49.188-80.375\r\n   l-13.188-13.188c-34.797-6-79.188,35.984-86.391,76.766c-7.188,40.781-8.391,75.563-8.391,75.563s34.781-1.188,75.578-8.391\r\n   S212.692,397.684,206.692,362.887z\"/>\r\n <path d=\"M505.224,6.777C450.786-18.738,312.927,28.98,236.255,130.668c-58.422,77.453-89.688,129.641-89.688,129.641\r\n    l46.406,46.406l12.313,12.313l46.391,46.391c0,0,52.219-31.25,129.672-89.656C483.005,199.074,530.739,61.215,505.224,6.777z\r\n     M274.63,237.371c-12.813-12.813-12.813-33.594,0-46.406s33.578-12.813,46.406,0.016c12.813,12.813,12.813,33.578,0,46.391\r\n    C308.208,250.184,287.442,250.184,274.63,237.371z M351.552,160.465c-16.563-16.578-16.563-43.422,0-59.984\r\n   c16.547-16.563,43.406-16.563,59.969,0s16.563,43.406,0,59.984C394.958,177.012,368.099,177.012,351.552,160.465z\"/>\r\n</g>\r\n</svg>', '1'),
(179, '<svg fill=\"#000000\" height=\"800px\" width=\"800px\" version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" \r\n   viewBox=\"0 0 511.999 511.999\" xml:space=\"preserve\">\r\n<g>\r\n <g>\r\n   <path d=\"M256.298,101.846c-92.85,0-206.983,143.686-206.983,260.579c0,44.352,15.783,79.881,46.928,105.612\r\n     c35.323,29.174,89.169,43.962,160.054,43.962c70.625,0,124.319-14.906,159.567-44.319c31.069-25.916,46.82-61.673,46.82-106.262\r\n     C462.685,244.98,348.887,101.846,256.298,101.846z M264.276,302.614c23.697,12.525,53.196,28.124,53.196,59.042\r\n     c0,27.843-18.793,51.339-44.341,58.603v7.908c0,9.18-7.448,16.628-16.628,16.628c-9.18,0-16.628-7.448-16.628-16.628v-7.908\r\n     c-25.548-7.264-44.341-30.76-44.341-58.603c0-9.18,7.448-16.628,16.628-16.628c9.18,0,16.628,7.448,16.628,16.628\r\n     c0,15.285,12.428,27.713,27.713,27.713s27.713-12.428,27.713-27.713c0-10.89-18.036-20.417-35.486-29.64\r\n      c-23.697-12.525-53.196-28.124-53.196-59.042c0-27.843,18.793-51.339,44.341-58.603v-7.908c0-9.18,7.448-16.628,16.628-16.628\r\n     c9.18,0,16.628,7.448,16.628,16.628v7.908c25.548,7.264,44.341,30.76,44.341,58.603c0,9.18-7.448,16.628-16.628,16.628\r\n      c-9.18,0-16.628-7.448-16.628-16.628c0-15.285-12.428-27.713-27.713-27.713s-27.713,12.428-27.713,27.713\r\n     C228.791,283.864,246.825,293.391,264.276,302.614z\"/>\r\n </g>\r\n</g>\r\n<g>\r\n <g>\r\n   <path d=\"M347.037,20.547c-7.686-3.941-17.126-1.354-21.705,5.976c-6.95,11.14-16.639,13.932-23.545,14.311\r\n      c-12.016,0.855-24.087-5.25-32.454-15.816C256.752,9.115,236.844,0,214.728,0c-22.116,0-42.024,9.115-54.604,25.017\r\n     c-3.746,4.72-4.634,11.085-2.338,16.66c1.859,4.508,10.991,25.543,26.151,46.511c23.911-12.465,48.487-19.6,72.36-19.6\r\n      c23.868,0,48.444,7.139,72.347,19.615c15.169-20.974,24.306-42.019,26.166-46.528C358.1,33.678,354.722,24.498,347.037,20.547z\"/>\r\n  </g>\r\n</g>\r\n</svg>', '1'),
(180, '<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n<svg version=\"1.1\" id=\"designs\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" \r\n  width=\"800px\" height=\"800px\" viewBox=\"0 0 32 32\" xml:space=\"preserve\">\r\n<path class=\"sketchy_een\" d=\"M25.153,19.112c0.032,1.663-0.024,3.326,0.02,4.99c0.012,0.433-0.369,0.796-0.794,0.796\r\n c-0.421,0-0.812-0.363-0.796-0.796c0.032-0.812,0.03-1.623,0.026-2.435c-0.004-0.804-0.01-1.609-0.042-2.411\r\n  c-0.034-0.847-0.078-1.689-0.082-2.537c-0.004-0.867-0.016-1.733-0.02-2.601c-0.004-1.827-0.049-3.651-0.083-5.478\r\n  c-1.721-0.162-3.453-0.144-5.18-0.175c-0.83-0.014-1.659-0.044-2.489-0.07c-0.838-0.026-1.675-0.066-2.513-0.058\r\n  c-0.818,0.006-1.637,0.044-2.453,0.092C10.102,8.466,9.458,8.51,8.816,8.574c0.064,1.769,0.042,3.537,0.047,5.309\r\n c0.004,1.719,0.006,3.438,0.006,5.157c0.002,0.816,0.014,1.629,0.018,2.445c0.003,0.561-0.006,1.124-0.003,1.685\r\n  c0.904,0.001,1.808,0,2.713-0.003c0.291-0.001,0.581-0.003,0.872-0.003c0.515,0,1.031,0.007,1.547,0.038\r\n  c0.844,0.05,1.687,0.092,2.533,0.104c0.788,0.01,1.576-0.002,2.363-0.002c0.798-0.002,1.597-0.004,2.395-0.036\r\n  c0.421-0.016,0.772,0.365,0.772,0.772c0,0.429-0.351,0.762-0.772,0.772c-0.523,0.014-1.047,0.054-1.57,0.044\r\n  c-0.463-0.008-0.923-0.018-1.386-0.02c-0.828-0.004-1.655-0.01-2.485-0.022c-0.818-0.012-1.633-0.072-2.453-0.098\r\n c-1.697-0.05-3.392,0.086-5.088,0.092c-0.188,0-0.356-0.077-0.495-0.187c-0.327-0.107-0.576-0.407-0.574-0.768\r\n  c0.002-0.419,0.02-0.84,0.022-1.26c0-0.469,0-0.939,0.004-1.41C7.296,20.229,7.3,19.276,7.3,18.321\r\n c0.004-1.653,0.012-3.309,0.006-4.964c-0.002-0.85-0.008-1.699-0.034-2.547C7.244,9.896,7.186,8.985,7.134,8.074\r\n  C7.126,7.919,7.181,7.782,7.259,7.657C7.277,7.57,7.299,7.482,7.345,7.404c0.098-0.167,0.297-0.353,0.499-0.383\r\n c0.608-0.088,1.215-0.14,1.829-0.154c0.53-0.012,1.061-0.014,1.589-0.022c0.693-0.012,1.386-0.034,2.079-0.034\r\n  c0.328,0,0.655,0.005,0.983,0.018c1.677,0.066,3.354,0.146,5.032,0.158c0.323,0.004,0.646,0.002,0.969,0.002\r\n  c0.469,0,0.935-0.002,1.402,0.006c0.409,0.008,0.816,0.026,1.223,0.042c0.355,0.014,0.71,0.002,1.067,0.018\r\n c0.47,0.019,0.821,0.408,0.798,0.866c0.193,0.162,0.325,0.397,0.327,0.665c0.016,1.801-0.036,3.6-0.044,5.399\r\n c-0.002,0.874-0.002,1.747,0,2.621C25.099,17.443,25.137,18.276,25.153,19.112z M29,27.435c0,0.219-0.088,0.431-0.241,0.584\r\n c-0.156,0.154-0.367,0.241-0.584,0.241c-0.035,0-0.074-0.016-0.112-0.021c-0.124-0.005-0.248-0.027-0.365-0.088\r\n c-0.255-0.132-0.493-0.292-0.751-0.415c-0.171-0.064-0.341-0.125-0.518-0.169c-0.344-0.025-0.689-0.008-1.033,0.021\r\n c-0.352,0.061-0.694,0.153-1.029,0.279c-0.644,0.298-1.272,0.682-1.988,0.757c-0.239,0.026-0.481,0.002-0.72-0.022\r\n  c-0.237-0.026-0.463-0.064-0.688-0.144c-0.132-0.046-0.257-0.11-0.381-0.174c-0.095-0.05-0.195-0.1-0.289-0.158\r\n c-0.049,0.045-0.098,0.088-0.15,0.13c-0.168,0.138-0.355,0.295-0.548,0.397c-0.349,0.183-0.74,0.287-1.135,0.289\r\n  c-0.656,0.002-1.328-0.189-1.815-0.648c-0.028-0.026-0.054-0.054-0.08-0.082c-0.071,0.057-0.144,0.111-0.219,0.162\r\n  c-0.193,0.134-0.423,0.257-0.652,0.309c-0.09,0.02-0.177,0.036-0.267,0.05c-0.16,0.026-0.369,0.068-0.533,0.048\r\n c-0.337-0.038-0.674-0.092-0.989-0.221c-0.15-0.06-0.309-0.112-0.447-0.199c-0.15-0.098-0.323-0.216-0.439-0.355\r\n  c-0.004-0.004-0.007-0.009-0.011-0.013c-0.189,0.113-0.382,0.218-0.604,0.268c-0.263,0.06-0.538,0.102-0.81,0.076\r\n c-0.13-0.01-0.255-0.026-0.385-0.044c-0.154-0.024-0.305-0.052-0.457-0.092c-0.327-0.084-0.65-0.193-0.963-0.319\r\n  c-0.344-0.136-0.699-0.294-1.063-0.366c-0.204-0.016-0.409-0.011-0.613,0.007c-0.151,0.028-0.296,0.066-0.44,0.118\r\n  c-0.266,0.135-0.507,0.306-0.745,0.482c-0.167,0.137-0.333,0.275-0.498,0.414c-0.17,0.142-0.341,0.235-0.568,0.235\r\n  c-0.197,0-0.384-0.081-0.532-0.211c-0.152-0.009-0.302-0.041-0.436-0.119c-0.453-0.265-0.614-0.869-0.347-1.324\r\n c0.046-0.077,0.088-0.154,0.126-0.233c0.079-0.207,0.146-0.417,0.193-0.634c0.006-0.103,0.005-0.204-0.002-0.307\r\n  c-0.04-0.198-0.094-0.39-0.162-0.58c-0.158-0.308-0.367-0.588-0.535-0.894c-0.255-0.465-0.513-0.947-0.626-1.47\r\n c-0.106-0.487-0.15-0.965-0.046-1.456c0.072-0.341,0.179-0.664,0.359-0.965c0.057-0.095,0.119-0.183,0.187-0.267\r\n  c-0.104-0.132-0.202-0.271-0.29-0.414c-0.169-0.277-0.331-0.586-0.377-0.909c-0.028-0.2-0.076-0.413-0.056-0.616\r\n  c0.02-0.217,0.044-0.435,0.106-0.644c0.102-0.347,0.269-0.652,0.495-0.933c0.096-0.12,0.202-0.225,0.316-0.321\r\n  c-0.036-0.045-0.074-0.089-0.113-0.131c-0.333-0.361-0.688-0.714-0.87-1.181c-0.235-0.606-0.167-1.33,0.096-1.913\r\n c0.161-0.353,0.404-0.633,0.692-0.875c-0.149-0.164-0.294-0.343-0.401-0.535c-0.126-0.221-0.221-0.455-0.315-0.688\r\n  c-0.108-0.261-0.156-0.564-0.179-0.844c-0.04-0.512,0.114-1.043,0.389-1.476C3.75,8.5,3.996,8.189,4.177,7.842\r\n  c0.036-0.103,0.064-0.206,0.086-0.314c0.008-0.131,0.012-0.261,0.006-0.393c-0.051-0.218-0.126-0.429-0.206-0.64\r\n  C3.784,5.907,3.416,5.364,3.111,4.79C2.89,4.375,3.003,3.855,3.42,3.609c0.061-0.035,0.127-0.06,0.195-0.079\r\n  c0.091-0.075,0.194-0.135,0.309-0.166c0.199-0.056,0.477-0.042,0.65,0.084c0.271,0.199,0.55,0.389,0.83,0.576\r\n c0.189,0.128,0.38,0.247,0.583,0.348C6.057,4.396,6.128,4.418,6.2,4.437c0.042,0.002,0.083,0.001,0.125,0\r\n C6.447,4.406,6.567,4.365,6.685,4.32c0.383-0.189,0.73-0.426,1.098-0.643c0.235-0.14,0.489-0.24,0.74-0.341\r\n C8.842,3.21,9.169,3.163,9.51,3.123c0.169-0.02,0.337-0.03,0.509-0.024c0.311,0.01,0.608,0.116,0.887,0.25\r\n  c0.197,0.094,0.393,0.193,0.576,0.313c0.229,0.149,0.445,0.325,0.674,0.479c0.171-0.153,0.344-0.303,0.539-0.434\r\n  c0.146-0.098,0.297-0.189,0.449-0.279c0.239-0.142,0.497-0.291,0.776-0.329c0.166-0.022,0.321-0.041,0.477-0.041\r\n  c0.114,0,0.229,0.01,0.35,0.037c0.096,0.02,0.191,0.042,0.283,0.074c0.15,0.048,0.293,0.106,0.439,0.167\r\n  c0.122,0.052,0.245,0.102,0.353,0.182c0.187,0.135,0.367,0.277,0.536,0.435c0.042,0.038,0.083,0.076,0.124,0.115\r\n  c0.158-0.115,0.31-0.238,0.472-0.346c0.433-0.287,0.929-0.375,1.432-0.441c0.076-0.01,0.158-0.024,0.231-0.024\r\n  c0.14,0.002,0.321,0.006,0.453,0.046c0.172,0.052,0.341,0.118,0.501,0.201c0.134,0.07,0.269,0.142,0.389,0.233\r\n  c0.129,0.098,0.255,0.195,0.369,0.306c0.053-0.033,0.106-0.065,0.16-0.097c0.146-0.084,0.309-0.154,0.465-0.213\r\n c0.124-0.048,0.251-0.098,0.383-0.122c0.243-0.046,0.499-0.09,0.746-0.074c0.247,0.014,0.505,0.032,0.74,0.112\r\n  c0.283,0.094,0.538,0.221,0.776,0.399c0.084,0.063,0.166,0.128,0.249,0.194c0.186,0.133,0.381,0.243,0.587,0.34\r\n c0.13,0.048,0.26,0.091,0.393,0.126c0.151,0.009,0.302,0.001,0.452-0.013c0.22-0.045,0.424-0.117,0.631-0.2\r\n c0.303-0.126,0.605-0.252,0.908-0.378c0.274-0.12,0.538-0.251,0.795-0.409c0.369-0.225,0.87-0.082,1.085,0.285\r\n  c0.079,0.136,0.099,0.297,0.088,0.457c0.097,0.247,0.101,0.529-0.047,0.763c-0.282,0.444-0.559,0.891-0.787,1.365\r\n c-0.057,0.157-0.099,0.316-0.129,0.48c-0.006,0.093-0.008,0.185-0.005,0.278c0.017,0.073,0.038,0.144,0.064,0.216\r\n c0.332,0.709,0.826,1.361,0.987,2.14c0.096,0.462,0.136,0.947,0.002,1.41c-0.086,0.303-0.227,0.586-0.409,0.844\r\n c-0.085,0.119-0.173,0.235-0.264,0.348c0.131,0.163,0.262,0.328,0.362,0.514c0.142,0.269,0.273,0.552,0.321,0.858\r\n c0.064,0.407,0.038,0.82-0.07,1.219c-0.108,0.401-0.257,0.792-0.493,1.137c-0.009,0.013-0.018,0.025-0.026,0.038\r\n  c0.105,0.113,0.198,0.237,0.28,0.373c0.231,0.387,0.313,0.818,0.367,1.256c0.036,0.303,0.038,0.63-0.068,0.919\r\n  c-0.13,0.351-0.303,0.658-0.556,0.935c-0.052,0.056-0.106,0.11-0.159,0.165c0.122,0.166,0.225,0.332,0.311,0.525\r\n  c0.1,0.225,0.189,0.451,0.255,0.688c0.116,0.421,0.088,0.865-0.01,1.286c-0.185,0.799-0.57,1.533-0.723,2.336\r\n c-0.005,0.074-0.005,0.146-0.002,0.22c0.056,0.284,0.141,0.56,0.224,0.836c0.075,0.246,0.154,0.494,0.245,0.734\r\n c0.107,0.22,0.232,0.425,0.381,0.622c-0.006-0.008-0.013-0.016-0.019-0.024C28.927,27.082,29,27.232,29,27.435z M16.231,4.226\r\n c0.007,0,0.013-0.001,0.019-0.002c0,0,0.001,0,0.001,0C16.244,4.224,16.238,4.225,16.231,4.226z M26.56,25.981\r\n  c-0.12-0.367-0.223-0.743-0.251-1.123c-0.05-0.65,0.193-1.284,0.415-1.885c0.155-0.418,0.292-0.833,0.373-1.271\r\n c0.006-0.087,0.007-0.171,0.003-0.257c-0.029-0.111-0.064-0.218-0.103-0.326c-0.061-0.13-0.128-0.254-0.205-0.375\r\n c-0.086-0.091-0.179-0.17-0.27-0.26c-0.197-0.198-0.319-0.435-0.331-0.718c-0.014-0.285,0.114-0.522,0.291-0.734\r\n  c0.205-0.243,0.456-0.443,0.666-0.683c0.017-0.028,0.034-0.056,0.049-0.085c0.014-0.043,0.026-0.087,0.037-0.132\r\n  c0.001-0.042,0.001-0.083-0.001-0.125c-0.036-0.185-0.082-0.365-0.143-0.542c-0.024-0.046-0.05-0.09-0.078-0.133\r\n  c-0.018-0.019-0.036-0.037-0.055-0.055c-0.119-0.069-0.245-0.118-0.364-0.19c-0.223-0.137-0.373-0.343-0.447-0.592\r\n  c-0.1-0.333,0.026-0.656,0.241-0.906c0.082-0.096,0.181-0.179,0.269-0.273c0.071-0.078,0.14-0.156,0.209-0.237\r\n  c0.071-0.104,0.134-0.212,0.191-0.326c0.056-0.155,0.103-0.313,0.134-0.476c0.006-0.096,0.008-0.192,0.004-0.289\r\n  c-0.015-0.068-0.034-0.134-0.056-0.199c-0.055-0.111-0.116-0.219-0.185-0.323c-0.062-0.069-0.128-0.134-0.198-0.196\r\n c-0.162-0.115-0.319-0.233-0.43-0.404c-0.239-0.367-0.162-0.84,0.128-1.153c0.1-0.108,0.225-0.192,0.333-0.293\r\n  c0.076-0.069,0.144-0.141,0.211-0.216c0.097-0.134,0.185-0.273,0.262-0.42c0.019-0.06,0.034-0.119,0.047-0.179\r\n  c0.005-0.092,0.006-0.181,0.003-0.272c-0.033-0.16-0.077-0.315-0.131-0.472C27.04,9.57,26.88,9.288,26.715,9.009\r\n  c-0.353-0.592-0.698-1.232-0.632-1.948c0.034-0.355,0.088-0.698,0.225-1.029c0.009-0.022,0.02-0.043,0.029-0.064\r\n  c-0.149,0.061-0.299,0.119-0.453,0.161c-0.197,0.054-0.399,0.08-0.602,0.104c-0.203,0.026-0.399,0.038-0.604,0.018\r\n  c-0.417-0.044-0.872-0.183-1.24-0.383c-0.263-0.142-0.499-0.318-0.731-0.504c-0.074-0.052-0.15-0.099-0.23-0.143\r\n  c-0.077-0.027-0.154-0.051-0.234-0.069c-0.132-0.009-0.262-0.01-0.395,0.001c-0.089,0.019-0.175,0.043-0.261,0.073\r\n  c-0.135,0.065-0.263,0.139-0.388,0.225c-0.06,0.049-0.119,0.099-0.179,0.149c-0.168,0.138-0.321,0.259-0.533,0.319\r\n  c-0.183,0.052-0.365,0.062-0.55,0.018c-0.383-0.092-0.574-0.394-0.782-0.688c-0.047-0.052-0.095-0.102-0.146-0.149\r\n  c-0.106-0.07-0.215-0.132-0.33-0.187c-0.008-0.003-0.016-0.005-0.024-0.007c-0.023,0-0.046,0.001-0.069,0.002\r\n c-0.19,0.033-0.378,0.074-0.562,0.132c-0.141,0.073-0.271,0.158-0.399,0.251c-0.16,0.126-0.319,0.253-0.485,0.374\r\n c-0.397,0.289-0.917,0.335-1.336,0.054c-0.211-0.142-0.397-0.303-0.574-0.481c-0.104-0.104-0.207-0.205-0.318-0.301\r\n c-0.035-0.022-0.071-0.043-0.107-0.062c-0.137-0.052-0.276-0.094-0.419-0.128c-0.032-0.001-0.065,0-0.097,0.001\r\n c-0.048,0.011-0.094,0.025-0.141,0.04c-0.215,0.115-0.421,0.244-0.623,0.383c-0.11,0.09-0.216,0.186-0.317,0.287\r\n  c-0.066,0.066-0.132,0.132-0.201,0.193c-0.195,0.176-0.483,0.335-0.756,0.315c-0.249-0.018-0.469-0.078-0.682-0.209\r\n c-0.082-0.052-0.162-0.106-0.237-0.161c-0.146-0.108-0.286-0.225-0.428-0.338c-0.249-0.184-0.503-0.348-0.781-0.48\r\n  c-0.056-0.02-0.112-0.035-0.168-0.048c-0.158,0-0.312,0.015-0.467,0.034C9.292,4.795,9.111,4.85,8.93,4.917\r\n C8.598,5.076,8.302,5.281,7.988,5.474c-0.463,0.285-0.981,0.55-1.528,0.61C6.177,6.115,5.9,6.068,5.632,5.984\r\n c0.068,0.173,0.136,0.345,0.189,0.525c0.215,0.736,0.144,1.617-0.253,2.283C5.354,9.154,5.086,9.482,4.884,9.851\r\n  C4.859,9.924,4.84,9.997,4.823,10.074c-0.002,0.052-0.002,0.103,0,0.155c0.032,0.168,0.074,0.331,0.134,0.493\r\n c0.054,0.113,0.114,0.222,0.184,0.327c0.101,0.116,0.211,0.223,0.327,0.327c0.106,0.096,0.223,0.185,0.311,0.297\r\n  c0.074,0.092,0.152,0.182,0.207,0.287c0.06,0.116,0.142,0.327,0.124,0.465c-0.01,0.088-0.022,0.174-0.034,0.261\r\n c-0.03,0.08-0.064,0.158-0.102,0.233c-0.038,0.08-0.126,0.174-0.199,0.233c-0.208,0.168-0.447,0.278-0.67,0.421\r\n c-0.072,0.062-0.138,0.127-0.203,0.198c-0.017,0.027-0.033,0.053-0.048,0.081c-0.027,0.076-0.051,0.152-0.07,0.231\r\n  c-0.005,0.094-0.004,0.186,0.003,0.28c0.012,0.062,0.027,0.123,0.045,0.184c0.029,0.053,0.062,0.104,0.096,0.155\r\n  c0.15,0.175,0.315,0.338,0.46,0.516c0.087,0.108,0.171,0.215,0.264,0.313c0.057,0.018,0.114,0.042,0.171,0.074\r\n  c0.054,0.032,0.11,0.062,0.164,0.096c0.158,0.096,0.293,0.229,0.385,0.389c0.168,0.289,0.148,0.614,0.006,0.907\r\n c-0.116,0.235-0.355,0.369-0.568,0.501c-0.094,0.057-0.187,0.116-0.277,0.179c-0.067,0.06-0.13,0.121-0.19,0.187\r\n  c-0.033,0.054-0.064,0.108-0.092,0.165c-0.023,0.072-0.042,0.144-0.057,0.217c-0.003,0.073-0.002,0.146,0.003,0.219\r\n c0.013,0.061,0.028,0.121,0.046,0.18c0.079,0.147,0.166,0.289,0.262,0.426c0.123,0.144,0.254,0.272,0.398,0.393\r\n c0.082,0.059,0.164,0.117,0.241,0.183c0.339,0.289,0.399,0.82,0.18,1.197c-0.163,0.279-0.464,0.405-0.726,0.571\r\n c-0.016,0.016-0.032,0.031-0.048,0.048c-0.028,0.046-0.055,0.092-0.08,0.139c-0.033,0.107-0.059,0.215-0.08,0.325\r\n c-0.005,0.091-0.004,0.181,0.001,0.272c0.036,0.187,0.083,0.372,0.148,0.55c0.151,0.317,0.324,0.628,0.509,0.926\r\n  c0.104,0.166,0.229,0.323,0.323,0.495c0.124,0.223,0.231,0.459,0.313,0.702c0.154,0.459,0.234,0.952,0.193,1.436\r\n  c0.482-0.226,1.017-0.365,1.554-0.365c0.137,0,0.273,0.009,0.408,0.026c0.245,0.034,0.477,0.072,0.71,0.15\r\n  c0.102,0.034,0.203,0.068,0.305,0.104c0.197,0.076,0.393,0.156,0.594,0.225c0.361,0.126,0.72,0.235,1.095,0.301\r\n c0.112,0.009,0.223,0.013,0.335,0.009c0.055-0.012,0.109-0.026,0.164-0.042c0.199-0.103,0.381-0.231,0.578-0.338\r\n  c0.13-0.07,0.261-0.128,0.401-0.179c0.205-0.074,0.497-0.012,0.684,0.098c0.214,0.125,0.338,0.331,0.468,0.531\r\n  c0.034,0.035,0.068,0.068,0.104,0.1c0.043,0.025,0.085,0.048,0.129,0.07c0.162,0.06,0.328,0.108,0.498,0.146\r\n  c0.082,0.004,0.162,0.003,0.244-0.003c0.077-0.015,0.151-0.032,0.226-0.056c0.066-0.036,0.13-0.074,0.193-0.115\r\n c0.096-0.089,0.18-0.188,0.268-0.288c0.317-0.361,0.818-0.475,1.244-0.243c0.311,0.167,0.513,0.47,0.769,0.708\r\n  c0.045,0.028,0.09,0.055,0.137,0.08c0.112,0.04,0.226,0.07,0.343,0.094c0.088,0.005,0.173,0.005,0.261,0\r\n  c0.072-0.016,0.141-0.034,0.211-0.057c0.104-0.058,0.204-0.123,0.302-0.19c0.162-0.137,0.305-0.288,0.454-0.442\r\n c0.249-0.257,0.6-0.399,0.955-0.301c0.208,0.056,0.363,0.195,0.52,0.332c0.135,0.09,0.276,0.169,0.422,0.239\r\n  c0.124,0.039,0.246,0.067,0.374,0.09c0.145,0.015,0.291,0.025,0.436,0.019c0.21-0.05,0.41-0.13,0.61-0.212\r\n  c0.389-0.173,0.76-0.381,1.164-0.519c0.427-0.146,0.875-0.255,1.328-0.291c0.219-0.018,0.439-0.026,0.66-0.028\r\n  C26.198,25.959,26.381,25.964,26.56,25.981z\"/>\r\n</svg>', '1'),
(181, '<svg preserveAspectRatio=\"xMidYMid\" viewBox=\"-33 0 255 255\"><defs><linearGradient id=\"a\" x1=\"94.141\" x2=\"94.141\" y1=\"255\" y2=\".188\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#ff4c0d\"/><stop offset=\"1\" stop-color=\"#fc9502\"/></linearGradient></defs><g fill-rule=\"evenodd\"><path fill=\"url(#a)\" d=\"M187.899 164.809c-2.096 50.059-43.325 90.003-93.899 90.003-51.915 0-94-43.5-94-94 0-6.75-.121-20.24 10-43 6.057-13.621 9.856-22.178 12-30 1.178-4.299 3.469-11.129 10 0 3.851 6.562 4 16 4 16s14.328-10.995 24-32c14.179-30.793 2.866-49.2-1-62-1.338-4.428-2.178-12.386 7-9 9.352 3.451 34.076 20.758 47 39 18.445 26.035 25 51 25 51s5.906-7.33 8-15c2.365-8.661 2.4-17.239 9.999-7.999 7.227 8.787 17.96 25.3 24.001 40.999 10.969 28.509 7.899 55.997 7.899 55.997z\"/><path fill=\"#fc9502\" d=\"M94 254.812c-35.899 0-65-29.101-65-65 0-21.661 8.729-34.812 26.896-52.646 11.632-11.419 22.519-25.444 27.146-34.994.911-1.88 2.984-11.677 10.977-.206 4.193 6.016 10.766 16.715 14.981 25.846 7.266 15.743 9 31 9 31s7.121-4.196 12-15c1.573-3.482 4.753-16.664 13.643-3.484C150.166 150 159.127 167.39 159 189.812c0 35.899-29.102 65-65 65z\"/><path fill=\"#fce202\" d=\"M95 183.812c9.25 0 9.25 17.129 21 40 7.824 15.229-3.879 31-21 31s-26-13.879-26-31c0-17.12 16.75-40 26-40z\"/></g></svg>', '1'),
(182, '<?xml version=\"1.0\" encoding=\"utf-8\"?><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->\r\n<svg width=\"800px\" height=\"800px\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\r\n<path d=\"M10.5 15L13.5 12M13.5 15L10.5 12\" stroke=\"#1C274C\" stroke-width=\"1.5\" stroke-linecap=\"round\"/>\r\n<path d=\"M22 11.7979C22 9.16554 22 7.84935 21.2305 6.99383C21.1598 6.91514 21.0849 6.84024 21.0062 6.76946C20.1506 6 18.8345 6 16.2021 6H15.8284C14.6747 6 14.0979 6 13.5604 5.84678C13.2651 5.7626 12.9804 5.64471 12.7121 5.49543C12.2237 5.22367 11.8158 4.81578 11 4L10.4497 3.44975C10.1763 3.17633 10.0396 3.03961 9.89594 2.92051C9.27652 2.40704 8.51665 2.09229 7.71557 2.01738C7.52976 2 7.33642 2 6.94975 2C6.06722 2 5.62595 2 5.25839 2.06935C3.64031 2.37464 2.37464 3.64031 2.06935 5.25839C2 5.62595 2 6.06722 2 6.94975M21.9913 16C21.9554 18.4796 21.7715 19.8853 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V11\" stroke=\"#1C274C\" stroke-width=\"1.5\" stroke-linecap=\"round\"/>\r\n</svg>', '1'),
(183, '<?xml version=\"1.0\" encoding=\"utf-8\"?><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->\r\n<svg width=\"800px\" height=\"800px\" viewBox=\"0 0 20 20\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\r\n<path d=\"M3 5.5C3 4.11929 4.11929 3 5.5 3H8V5.5C8 6.32843 8.67157 7 9.5 7H17V14.5C17 15.8807 15.8807 17 14.5 17H5.5C4.11929 17 3 15.8807 3 14.5V5.5ZM8 11C8 10.4477 7.55228 10 7 10C6.44772 10 6 10.4477 6 11C6 11.5523 6.44772 12 7 12C7.55228 12 8 11.5523 8 11ZM10 12C10.5523 12 11 11.5523 11 11C11 10.4477 10.5523 10 10 10C9.44772 10 9 10.4477 9 11C9 11.5523 9.44772 12 10 12ZM14 11C14 10.4477 13.5523 10 13 10C12.4477 10 12 10.4477 12 11C12 11.5523 12.4477 12 13 12C13.5523 12 14 11.5523 14 11Z\"/>\r\n<path d=\"M17 6V5.5C17 4.11929 15.8807 3 14.5 3H9V5.5C9 5.77614 9.22386 6 9.5 6H17Z\"/>\r\n</svg>', '1'),
(184, '<svg height=\"200px\" width=\"200px\" version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 512 512\" xml:space=\"preserve\"><g id=\"SVGRepo_bgCarrier\" stroke-width=\"0\"></g><g id=\"SVGRepo_tracerCarrier\" stroke-linecap=\"round\" stroke-linejoin=\"round\"></g><g id=\"SVGRepo_iconCarrier\"> <g> <g> <path d=\"M434.863,126.093V77.137h-48.956V0h-33.391v77.137h-42.083V0h-33.391v77.137h-42.083V0h-33.391v77.137h-42.083V0h-33.391 v77.137H77.137v48.956H0v33.391h77.137v42.083H0v33.391h77.137v42.083H0v33.391h77.137v42.083H0v33.391h77.137v48.956h48.956V512 h33.391v-77.137h42.083V512h33.391v-77.137h42.083V512h33.391v-77.137h42.083V512h33.391v-77.137h48.956v-48.956H512v-33.391 h-77.137v-42.083H512v-33.391h-77.137v-42.083H512v-33.391h-77.137v-42.083H512v-33.39H434.863z M375.773,282.469 c0,23.125-14.251,42.974-34.428,51.253c-1.38,26.928-23.721,48.411-50.986,48.411c-13.221,0-25.283-5.052-34.36-13.325 c-9.077,8.273-21.139,13.325-34.36,13.325c-27.265,0-49.606-21.483-50.986-48.411c-20.177-8.28-34.428-28.129-34.428-51.253 c0-9.366,2.351-18.428,6.742-26.478c-4.296-7.867-6.742-16.883-6.742-26.459c0-22.913,14.194-42.903,34.426-51.239 c1.373-26.935,23.718-48.426,50.987-48.426c13.221,0,25.283,5.052,34.36,13.325c9.077-8.273,21.139-13.325,34.36-13.325 c27.27,0,49.615,21.491,50.987,48.426c20.234,8.336,34.426,28.326,34.426,51.239c0,9.577-2.445,18.593-6.742,26.459 C373.423,264.042,375.773,273.104,375.773,282.469z\"></path> </g> </g> <g> <g> <path d=\"M221.64,163.258c-9.739,0-17.664,7.924-17.664,17.664c0,7.327,4.606,13.978,11.461,16.549l-11.727,31.264 c-9.613-3.606-17.688-9.991-23.463-18.021c-6.38,3.879-10.631,10.911-10.631,18.817c0,12.127,9.866,21.993,21.992,21.993v33.391 c-7.773,0-15.174-1.617-21.895-4.521c-0.065,0.687-0.098,1.379-0.098,2.076c0,12.127,9.865,21.992,21.992,21.992 c6.29,0,12.081-2.57,16.308-7.237l24.749,22.417c-7.484,8.263-17.258,13.992-27.914,16.604 c2.215,7.227,8.947,12.497,16.889,12.497c9.74,0,17.664-7.924,17.664-17.664V180.922 C239.304,171.182,231.381,163.258,221.64,163.258z\"></path> </g> </g> <g> <g> <path d=\"M320.391,284.915v-33.391c12.127,0,21.993-9.866,21.993-21.993c0-7.907-4.251-14.939-10.631-18.817 c-5.774,8.031-13.85,14.415-23.463,18.021l-11.727-31.264c6.855-2.571,11.461-9.222,11.461-16.549 c0-9.74-7.924-17.664-17.664-17.664c-9.74,0-17.664,7.924-17.664,17.664v150.156c0,9.74,7.924,17.664,17.664,17.664 c7.943,0,14.673-5.271,16.889-12.497c-10.656-2.612-20.43-8.341-27.914-16.604l24.749-22.417 c4.226,4.667,10.018,7.237,16.307,7.237c12.127,0,21.993-9.866,21.993-21.992c0-0.697-0.033-1.389-0.098-2.076 C335.565,283.297,328.164,284.915,320.391,284.915z\"></path> </g> </g> </g></svg>', '1'),
(185, '<svg class=\"icon-volume\" viewBox=\"0 0 24 24\" version=\"1.1\" xml:space=\"preserve\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" fill=\"#ffffff\"><g id=\"SVGRepo_bgCarrier\" stroke-width=\"0\"></g><g id=\"SVGRepo_tracerCarrier\" stroke-linecap=\"round\" stroke-linejoin=\"round\"></g><g id=\"SVGRepo_iconCarrier\"> <style type=\"text/css\"> .st0{display:none;} .st1{display:inline;} .st2{opacity:0.2;fill:none;stroke:#ffffff;stroke-width:5.000000e-02;stroke-miterlimit:10;} </style> <g class=\"st0\" id=\"grid_system\"></g> <g id=\"_icons\"> <g> <path d=\"M14.6,4.6c-0.9-0.6-2.1-0.8-3.1-0.4c-1.1,0.4-2.1,0.9-2.9,1.6L6.2,7.6H5c-1.7,0-3,1.3-3,3v2.9c0,1.7,1.3,3,3,3h1.2 l2.3,1.8c0.9,0.7,1.9,1.2,2.9,1.6c0.4,0.1,0.8,0.2,1.2,0.2c0.7,0,1.4-0.2,2-0.6c0.9-0.6,1.4-1.6,1.4-2.7V7.3 C16,6.2,15.5,5.2,14.6,4.6z M5.5,14.4H5c-0.6,0-1-0.4-1-1v-2.9c0-0.6,0.4-1,1-1h0.5V14.4z M14,16.7c0,0.4-0.2,0.8-0.6,1 c-0.4,0.3-0.9,0.3-1.3,0.2c-0.9-0.3-1.7-0.8-2.4-1.3l-2.2-1.7V9.1l2.2-1.7c0.7-0.6,1.5-1,2.4-1.3c0.4-0.2,0.9-0.1,1.3,0.2 c0.3,0.2,0.6,0.6,0.6,1V16.7z\"></path> <path d=\"M21.4,12l0.7-0.7c0.4-0.4,0.4-1,0-1.4s-1-0.4-1.4,0L20,10.6l-0.7-0.7c-0.4-0.4-1-0.4-1.4,0s-0.4,1,0,1.4l0.7,0.7l-0.7,0.7 c-0.4,0.4-0.4,1,0,1.4c0.2,0.2,0.5,0.3,0.7,0.3s0.5-0.1,0.7-0.3l0.7-0.7l0.7,0.7c0.2,0.2,0.5,0.3,0.7,0.3s0.5-0.1,0.7-0.3 c0.4-0.4,0.4-1,0-1.4L21.4,12z\"></path> </g> </g> </g></svg>', '1'),
(186, '<svg  class=\"icon-mute\" viewBox=\"0 0 24 24\" version=\"1.1\" xml:space=\"preserve\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" fill=\"#ffffff\"><g id=\"SVGRepo_bgCarrier\" stroke-width=\"0\"></g><g id=\"SVGRepo_tracerCarrier\" stroke-linecap=\"round\" stroke-linejoin=\"round\"></g><g id=\"SVGRepo_iconCarrier\"> <style type=\"text/css\"> .st0{display:none;} .st1{display:inline;} .st2{opacity:0.2;fill:none;stroke:#000000;stroke-width:5.000000e-02;stroke-miterlimit:10;} </style> <g class=\"st0\" id=\"grid_system\"></g> <g id=\"_icons\"> <path d=\"M3,13.4c0,1.7,1.3,3,3,3h1.2l2.3,1.8c0.9,0.7,1.9,1.2,2.9,1.6c0.4,0.1,0.8,0.2,1.2,0.2c0.7,0,1.4-0.2,2-0.6 c0.9-0.6,1.4-1.6,1.4-2.7V7.3c0-1.1-0.5-2-1.4-2.7c-0.9-0.6-2.1-0.8-3.1-0.4c-1.1,0.4-2.1,0.9-2.9,1.6L7.2,7.6H6c-1.7,0-3,1.3-3,3 V13.4z M8.5,9.1l2.2-1.7c0.7-0.6,1.5-1,2.4-1.3c0.4-0.2,0.9-0.1,1.3,0.2c0.3,0.2,0.5,0.6,0.5,1v9.4c0,0.4-0.2,0.8-0.6,1 c-0.4,0.3-0.9,0.3-1.3,0.2c-0.9-0.3-1.7-0.8-2.4-1.3l-2.2-1.7V9.1z M5,10.6c0-0.6,0.4-1,1-1h0.5v4.9H6c-0.6,0-1-0.4-1-1V10.6z\"></path> <path d=\"M19.7,9.3c-0.4-0.4-1-0.4-1.4,0c-0.4,0.4-0.4,1,0,1.4c0.3,0.3,0.5,0.8,0.5,1.3s-0.2,1-0.5,1.3c-0.4,0.4-0.4,1,0,1.4 c0.2,0.2,0.4,0.3,0.7,0.3c0.3,0,0.5-0.1,0.7-0.3c0.7-0.7,1-1.6,1-2.7S20.4,10,19.7,9.3z\"></path> </g> </g></svg>', '1'),
(187, '<?xml version=\"1.0\" encoding=\"utf-8\"?><svg version=\"1.1\" id=\"Layer_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 122.14 122.88\" style=\"enable-background:new 0 0 122.14 122.88\" xml:space=\"preserve\"><g><path d=\"M35.14,0H87c9.65,0,18.43,3.96,24.8,10.32c6.38,6.37,10.34,15.16,10.34,24.82v52.61c0,9.64-3.96,18.42-10.32,24.79 l-0.02,0.02c-6.38,6.37-15.16,10.32-24.79,10.32H35.14c-9.66,0-18.45-3.96-24.82-10.32l-0.24-0.27C3.86,105.95,0,97.27,0,87.74 V35.14c0-9.67,3.95-18.45,10.32-24.82S25.47,0,35.14,0L35.14,0z M91.51,31.02l0.07,0.11h21.6c-0.87-5.68-3.58-10.78-7.48-14.69 C100.9,11.64,94.28,8.66,87,8.66h-8.87L91.51,31.02L91.51,31.02z M81.52,31.13L68.07,8.66H38.57l13.61,22.47H81.52L81.52,31.13z M42.11,31.13L28.95,9.39c-4.81,1.16-9.12,3.65-12.51,7.05c-3.9,3.9-6.6,9.01-7.48,14.69H42.11L42.11,31.13z M113.48,39.79H8.66 v47.96c0,7.17,2.89,13.7,7.56,18.48l0.22,0.21c4.8,4.8,11.43,7.79,18.7,7.79H87c7.28,0,13.9-2.98,18.69-7.77l0.02-0.02 c4.79-4.79,7.77-11.41,7.77-18.69V39.79L113.48,39.79z M50.95,54.95l26.83,17.45c0.43,0.28,0.82,0.64,1.13,1.08 c1.22,1.77,0.77,4.2-1,5.42L51.19,94.67c-0.67,0.55-1.53,0.88-2.48,0.88c-2.16,0-3.91-1.75-3.91-3.91V58.15h0.02 c0-0.77,0.23-1.55,0.7-2.23C46.76,54.15,49.19,53.72,50.95,54.95L50.95,54.95L50.95,54.95z\"/></g></svg>', '1'),
(188, '<svg  class=\"icon-mute\" viewBox=\"0 0 24 24\" version=\"1.1\" xml:space=\"preserve\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" fill=\"#ffffff\"><g id=\"SVGRepo_bgCarrier\" stroke-width=\"0\"></g><g id=\"SVGRepo_tracerCarrier\" stroke-linecap=\"round\" stroke-linejoin=\"round\"></g><g id=\"SVGRepo_iconCarrier\"> <style type=\"text/css\"> .st0{display:none;} .st1{display:inline;} .st2{opacity:0.2;fill:none;stroke:#000000;stroke-width:5.000000e-02;stroke-miterlimit:10;} </style> <g class=\"st0\" id=\"grid_system\"></g> <g id=\"_icons\"> <path d=\"M3,13.4c0,1.7,1.3,3,3,3h1.2l2.3,1.8c0.9,0.7,1.9,1.2,2.9,1.6c0.4,0.1,0.8,0.2,1.2,0.2c0.7,0,1.4-0.2,2-0.6 c0.9-0.6,1.4-1.6,1.4-2.7V7.3c0-1.1-0.5-2-1.4-2.7c-0.9-0.6-2.1-0.8-3.1-0.4c-1.1,0.4-2.1,0.9-2.9,1.6L7.2,7.6H6 c-1.7,0-3,1.3-3,3 V13.4z M8.5,9.1l2.2-1.7c0.7-0.6,1.5-1,2.4-1.3c0.4-0.2,0.9-0.1,1.3,0.2c0.3,0.2,0.5,0.6,0.5,1v9.4c0,0.4-0.2,0.8-0.6,1 c-0.4,0.3-0.9,0.3-1.3,0.2c-0.9-0.3-1.7-0.8-2.4-1.3l-2.2-1.7V9.1z M5,10.6c0-0.6,0.4-1,1-1h0.5v4.9H6c-0.6,0-1-0.4-1-1V10.6z\"></path> <path d=\"M19.7,9.3c-0.4-0.4-1-0.4-1.4,0c-0.4,0.4-0.4,1,0,1.4c0.3,0.3,0.5,0.8,0.5,1.3s-0.2,1-0.5,1.3c-0.4,0.4-0.4,1,0,1.4 c0.2,0.2,0.4,0.3,0.7,0.3c0.3,0,0.5-0.1,0.7-0.3c0.7-0.7,1-1.6,1-2.7S20.4,10,19.7,9.3z\"></path> </g> </g></svg>', '1');
INSERT INTO `i_svg_icons` (`icon_id`, `icon_code`, `icon_status`) VALUES
(190, '<?xml version=\"1.0\" encoding=\"utf-8\"?><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools --><svg fill=\"#000000\" height=\"800px\" width=\"800px\" version=\"1.2\" baseProfile=\"tiny\" id=\"inventory\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 256 230\" xml:space=\"preserve\"><path d=\"M180,26.6c-40.4,0-72.8,32.8-72.8,73.2c0,40.2,32.6,72.8,72.8,72.8c40.2,0,73-32.6,73-73C253,59.2,220.4,26.6,180,26.6z M185.5,139.5v11.4h-10.1v-11.1c-8.7,0-17.5-2.3-21.7-4.7l3.7-14.8c4.7,2.3,12,5,19.8,5c8.4,0,12.5-3.7,12.5-8.7 c0-5-4-7.1-13.6-10.9c-13.4-4.7-21.7-11.4-21.7-23.5c0-11.1,7.3-19.8,20.8-22.5v-12h10.1v11.4c8.4,0,14.4,1.7,18.9,4.1l-4.1,14.4 c-3.3-1.4-8.7-3.7-16.1-3.7s-11.4,3.7-11.4,7.3c0,5,4.7,7,14.8,11.1c13.8,5,20.2,12,20.2,23.5C207.2,127.4,200.2,136.1,185.5,139.5z M205.7,213c0,7-5.7,12.6-12.6,12.6h-85.8C97,225.9,85.9,222,78,214.2l-32.3-32.3c-4.1-4.1-9.4-6.2-14.7-6.2H2v-50h54v-0.1 c10,0,20,3.9,27.8,11.5l46.3,46.3c4.9,4.9,4.9,12.8,0,17.7s-12.8,4.9-17.7,0L75,163.9c-1.5-1.7-4.1-1.7-5.7,0 c-1.7,1.7-1.7,4.3,0,5.9l37.2,37.2c3.9,4.1,9.2,6.2,14.7,6.2s10.9-2.3,14.7-6.2c1.9-1.7,3.4-4.1,4.5-6.4H193 C200,200.6,205.7,206.2,205.7,213z\"/></svg>', '1'),
(191, '&lt;!DOCTYPE svg PUBLIC &quot;-//W3C//DTD SVG 1.1//EN&quot; &quot;http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd&quot;&gt;\r\n&lt;!-- Uploaded to: SVG Repo, www.svgrepo.com, Transformed by: SVG Repo Mixer Tools --&gt;\r\n&lt;svg width=&quot;800px&quot; height=&quot;800px&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;\r\n&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;/&gt;\r\n&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;/&gt;\r\n&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;path opacity=&quot;0.5&quot; d=&quot;M21.8382 11.1263L21.609 13.5616C21.2313 17.5742 21.0425 19.5805 19.8599 20.7902C18.6773 22 16.9048 22 13.3599 22H10.6401C7.09517 22 5.32271 22 4.14009 20.7902C2.95748 19.5805 2.76865 17.5742 2.391 13.5616L2.16181 11.1263C1.9818 9.2137 1.8918 8.25739 2.21899 7.86207C2.39598 7.64823 2.63666 7.5172 2.89399 7.4946C3.36968 7.45282 3.96708 8.1329 5.16187 9.49307C5.77977 10.1965 6.08872 10.5482 6.43337 10.6027C6.62434 10.6328 6.81892 10.6018 6.99526 10.5131C7.31351 10.3529 7.5257 9.91812 7.95007 9.04852L10.1869 4.46486C10.9888 2.82162 11.3898 2 12 2C12.6102 2 13.0112 2.82162 13.8131 4.46485L16.0499 9.04851C16.4743 9.91812 16.6865 10.3529 17.0047 10.5131C17.1811 10.6018 17.3757 10.6328 17.5666 10.6027C17.9113 10.5482 18.2202 10.1965 18.8381 9.49307C20.0329 8.1329 20.6303 7.45282 21.106 7.4946C21.3633 7.5172 21.604 7.64823 21.781 7.86207C22.1082 8.25739 22.0182 9.2137 21.8382 11.1263Z&quot; fill=&quot;#ff6a00&quot;/&gt; &lt;path d=&quot;M12.9524 12.6989L12.8541 12.5225C12.4741 11.8408 12.2841 11.5 12 11.5C11.7159 11.5 11.5259 11.8408 11.1459 12.5225L11.0476 12.6989C10.9397 12.8926 10.8857 12.9894 10.8015 13.0533C10.7173 13.1172 10.6125 13.141 10.4028 13.1884L10.2119 13.2316C9.47396 13.3986 9.10501 13.482 9.01723 13.7643C8.92945 14.0466 9.18097 14.3407 9.68403 14.929L9.81418 15.0812C9.95713 15.2483 10.0286 15.3319 10.0608 15.4353C10.0929 15.5387 10.0821 15.6502 10.0605 15.8733L10.0408 16.0763C9.96476 16.8612 9.92674 17.2536 10.1565 17.4281C10.3864 17.6025 10.7318 17.4435 11.4227 17.1254L11.4227 17.1253L11.6014 17.0431L11.6015 17.043C11.7978 16.9527 11.8959 16.9075 12 16.9075C12.1041 16.9075 12.2022 16.9527 12.3986 17.0431L12.3986 17.0431L12.5773 17.1254C13.2682 17.4435 13.6136 17.6025 13.8435 17.4281C14.0733 17.2536 14.0352 16.8612 13.9592 16.0763L13.9395 15.8733C13.9179 15.6502 13.9071 15.5387 13.9392 15.4353C13.9714 15.3319 14.0429 15.2483 14.1858 15.0812L14.316 14.929L14.316 14.929C14.819 14.3407 15.0706 14.0466 14.9828 13.7643C14.895 13.482 14.526 13.3986 13.7881 13.2316L13.5972 13.1884C13.3875 13.141 13.2827 13.1172 13.1985 13.0533C13.1143 12.9894 13.0603 12.8926 12.9524 12.6989Z&quot; fill=&quot;#ff6a00&quot;/&gt; &lt;/g&gt;\r\n&lt;/svg&gt;', '1'),
(192, '&lt;!DOCTYPE svg PUBLIC &quot;-//W3C//DTD SVG 1.1//EN&quot; &quot;http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd&quot;&gt;\r\n&lt;!-- Uploaded to: SVG Repo, www.svgrepo.com, Transformed by: SVG Repo Mixer Tools --&gt;\r\n&lt;svg width=&quot;800px&quot; height=&quot;800px&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;\r\n&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;/&gt;\r\n&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;/&gt;\r\n&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;path opacity=&quot;0.5&quot; fill-rule=&quot;evenodd&quot; clip-rule=&quot;evenodd&quot; d=&quot;M19.6872 14.0932L19.8706 12.3885C19.9684 11.479 20.033 10.8784 19.9823 10.5C19.5357 10.4948 19.1359 10.2944 18.8645 9.98019C18.5384 10.1814 18.1122 10.6061 17.4705 11.2452C16.9762 11.7376 16.729 11.9838 16.4533 12.0219C16.3005 12.0431 16.1449 12.0213 16.0038 11.9592C15.7492 11.8471 15.5794 11.5427 15.2399 10.934L13.4505 7.72546C13.241 7.3499 13.0657 7.03565 12.9077 6.78271C12.6353 6.92169 12.3268 7.00006 12 7.00006C11.6732 7.00006 11.3647 6.92169 11.0923 6.78272C10.9343 7.03566 10.759 7.34991 10.5495 7.72547L8.76006 10.934C8.42056 11.5427 8.25081 11.8471 7.99621 11.9592C7.85514 12.0213 7.69947 12.0431 7.5467 12.0219C7.27097 11.9838 7.02381 11.7376 6.5295 11.2452C5.88785 10.6061 5.46157 10.1814 5.13553 9.98019C4.86406 10.2944 4.46434 10.4948 4.01771 10.5C3.96702 10.8784 4.03162 11.479 4.12945 12.3885L4.3128 14.0932C4.34376 14.3809 4.37312 14.6645 4.40192 14.9425C4.65422 17.3783 4.86292 19.3932 5.71208 20.1532C6.65817 21.0001 8.07613 21.0001 10.9121 21.0001H13.0879C15.9239 21.0001 17.3418 21.0001 18.2879 20.1532C19.1371 19.3932 19.3458 17.3783 19.5981 14.9426C19.6269 14.6645 19.6562 14.3809 19.6872 14.0932Z&quot; fill=&quot;#e32400&quot;/&gt; &lt;path d=&quot;M20 10.5C20.8284 10.5 21.5 9.82843 21.5 9C21.5 8.17157 20.8284 7.5 20 7.5C19.1716 7.5 18.5 8.17157 18.5 9C18.5 9.37466 18.6374 9.71724 18.8645 9.98013C19.1359 10.2944 19.5357 10.4947 19.9823 10.4999L20 10.5Z&quot; fill=&quot;#e32400&quot;/&gt; &lt;path d=&quot;M12 3C10.8954 3 10 3.89543 10 5C10 5.77778 10.444 6.45187 11.0923 6.78265C11.3647 6.92163 11.6732 7 12 7C12.3268 7 12.6353 6.92163 12.9077 6.78265C13.556 6.45187 14 5.77778 14 5C14 3.89543 13.1046 3 12 3Z&quot; fill=&quot;#e32400&quot;/&gt; &lt;path d=&quot;M2.5 9C2.5 9.82843 3.17157 10.5 4 10.5L4.01771 10.4999C4.46434 10.4947 4.86406 10.2944 5.13553 9.98012C5.36264 9.71724 5.5 9.37466 5.5 9C5.5 8.17157 4.82843 7.5 4 7.5C3.17157 7.5 2.5 8.17157 2.5 9Z&quot; fill=&quot;#e32400&quot;/&gt; &lt;path d=&quot;M4.84862 18.25C4.75064 17.7997 4.67228 17.2952 4.60254 16.75H19.3968C19.327 17.2952 19.2487 17.7997 19.1507 18.25H4.84862Z&quot; fill=&quot;#e32400&quot;/&gt; &lt;/g&gt;\r\n&lt;/svg&gt;', '1'),
(193, '&lt;svg fill=&quot;#000000&quot; viewBox=&quot;0 0 128 128&quot; id=&quot;Layer_1&quot; version=&quot;1.1&quot; xml:space=&quot;preserve&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot; xmlns:xlink=&quot;http://www.w3.org/1999/xlink&quot;&gt;&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;g&gt; &lt;path d=&quot;M64,42c-13.2,0-24,10.8-24,24s10.8,24,24,24s24-10.8,24-24S77.2,42,64,42z M64,82c-8.8,0-16-7.2-16-16s7.2-16,16-16 s16,7.2,16,16S72.8,82,64,82z&quot;&gt;&lt;/path&gt; &lt;path d=&quot;M64,100.8c-14.9,0-29.2,6.2-39.4,17.1l-2.7,2.9l5.8,5.5l2.7-2.9c8.8-9.4,20.7-14.6,33.6-14.6s24.8,5.2,33.6,14.6l2.7,2.9 l5.8-5.5l-2.7-2.9C93.2,107.1,78.9,100.8,64,100.8z&quot;&gt;&lt;/path&gt; &lt;path d=&quot;M97,47.9v8c9.4,0,18.1,3.8,24.6,10.7l5.8-5.5C119.6,52.7,108.5,47.9,97,47.9z&quot;&gt;&lt;/path&gt; &lt;path d=&quot;M116.1,20c0-10.5-8.6-19.1-19.1-19.1S77.9,9.5,77.9,20S86.5,39.1,97,39.1S116.1,30.5,116.1,20z M85.9,20 c0-6.1,5-11.1,11.1-11.1s11.1,5,11.1,11.1s-5,11.1-11.1,11.1S85.9,26.1,85.9,20z&quot;&gt;&lt;/path&gt; &lt;path d=&quot;M31,47.9c-11.5,0-22.6,4.8-30.4,13.2l5.8,5.5c6.4-6.9,15.2-10.7,24.6-10.7V47.9z&quot;&gt;&lt;/path&gt; &lt;path d=&quot;M50.1,20C50.1,9.5,41.5,0.9,31,0.9S11.9,9.5,11.9,20S20.5,39.1,31,39.1S50.1,30.5,50.1,20z M31,31.1 c-6.1,0-11.1-5-11.1-11.1S24.9,8.9,31,8.9s11.1,5,11.1,11.1S37.1,31.1,31,31.1z&quot;&gt;&lt;/path&gt; &lt;/g&gt; &lt;/g&gt;&lt;/svg&gt;', '1'),
(194, '&lt;svg fill=&quot;#000000&quot; viewBox=&quot;0 -2.21 87.952 87.952&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;g id=&quot;OBS&quot; transform=&quot;translate(-554.285 -408.958)&quot;&gt; &lt;g id=&quot;Group_26&quot; data-name=&quot;Group 26&quot;&gt; &lt;path id=&quot;Path_26&quot; data-name=&quot;Path 26&quot; d=&quot;M640.373,438.048a5.871,5.871,0,0,0,.29-8.29l-10.78-11.56a5.9,5.9,0,0,0-4.3-1.87,5.833,5.833,0,0,0-4,1.58l-6.83,6.37a7.516,7.516,0,0,0-2.39,4.86,31.755,31.755,0,0,0-13.77-3.12H598a32.036,32.036,0,0,0-32,32v7.15a7.781,7.781,0,0,0-5.17,7.32v3.2a7.781,7.781,0,0,0,5.17,7.32v6.74a2.755,2.755,0,0,0,2.75,2.75h59.09a2.748,2.748,0,0,0,2.75-2.75v-7.18a7.76,7.76,0,0,0,4.17-6.88v-3.2a7.733,7.733,0,0,0-4.17-6.87v-7.6a31.861,31.861,0,0,0-2.15-11.55,7.586,7.586,0,0,0,5.1-2.04ZM615.1,430.3c.01.11.03.22.05.34-.11-.07-.23-.13-.34-.2A2.594,2.594,0,0,1,615.1,430.3Zm.15,7.13.73-.68,5.21,5.58-.76.7-2.9,2.71-2.58-2.77c-.33-.38-.67-.74-1.03-1.09l-1.6-1.72Zm-43.75,20.59a26.526,26.526,0,0,1,26.5-26.5h.59a26.218,26.218,0,0,1,11.89,2.83l-3.15,2.94a24.46,24.46,0,0,0-6.11-1.91,2.747,2.747,0,1,0-.96,5.41,18.133,18.133,0,0,1,9.61,4.8,15.123,15.123,0,0,1,1.39,1.49,21.018,21.018,0,0,1,4.46,10.55,2.752,2.752,0,0,0,2.72,2.36,2.563,2.563,0,0,0,.39-.03,2.743,2.743,0,0,0,2.33-3.11,27.832,27.832,0,0,0-1.4-5.67l3.37-3.14a26.113,26.113,0,0,1,1.96,9.98v6.71H571.5ZM625.093,487H571.5v-3.55h53.59Zm4.17-14.51v3.2a2.264,2.264,0,0,1-2.26,2.26h-58.41a2.258,2.258,0,0,1-2.26-2.26v-3.2a2.264,2.264,0,0,1,2.26-2.26H627A2.271,2.271,0,0,1,629.263,472.488Zm-2.44-32.19-4.21-4.51-4.22-4.52c-.02-.02-.03-.04-.05-.06a2.1,2.1,0,0,1,.16-2.91l6.83-6.37a.363.363,0,0,1,.52.02l10.79,11.56a.354.354,0,0,1,.1.26.407.407,0,0,1-.12.26l-6.83,6.37A2.148,2.148,0,0,1,626.823,440.3Z&quot;&gt;&lt;/path&gt; &lt;/g&gt; &lt;g id=&quot;Group_27&quot; data-name=&quot;Group 27&quot;&gt; &lt;path id=&quot;Path_27&quot; data-name=&quot;Path 27&quot; d=&quot;M608.583,416.028a4.134,4.134,0,0,1-2.93,1.22,4.145,4.145,0,1,1,2.91-7.08A4.14,4.14,0,0,1,608.583,416.028Z&quot;&gt;&lt;/path&gt; &lt;/g&gt; &lt;g id=&quot;Group_28&quot; data-name=&quot;Group 28&quot;&gt; &lt;path id=&quot;Path_28&quot; data-name=&quot;Path 28&quot; d=&quot;M561.363,448.028a4.145,4.145,0,1,1-.02-5.85A4.125,4.125,0,0,1,561.363,448.028Z&quot;&gt;&lt;/path&gt; &lt;/g&gt; &lt;g id=&quot;Group_29&quot; data-name=&quot;Group 29&quot;&gt; &lt;path id=&quot;Path_29&quot; data-name=&quot;Path 29&quot; d=&quot;M572.983,416.678a4.493,4.493,0,0,1-3.22,1.34,4.518,4.518,0,0,1-3.21-7.71,4.526,4.526,0,1,1,6.43,6.37Z&quot;&gt;&lt;/path&gt; &lt;/g&gt; &lt;/g&gt; &lt;/g&gt;&lt;/svg&gt;', '1'),
(195, '&lt;svg fill=&quot;#000000&quot; viewBox=&quot;-1 0 19 19&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot; class=&quot;cf-icon-svg&quot;&gt;&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt;&lt;path d=&quot;M16.417 9.583A7.917 7.917 0 1 1 8.5 1.666a7.917 7.917 0 0 1 7.917 7.917zm-13.099.331h2.775a2.22 2.22 0 0 1 .163-.632 2.244 2.244 0 0 1 .168-.32H3.318a.318.318 0 0 0-.316.316v.32a.318.318 0 0 0 .316.316zm10.77-1.375a.318.318 0 0 0-.318-.317h-1.15A3.482 3.482 0 0 0 9.528 6.03H7.56a.318.318 0 0 0-.317.317v.404a.318.318 0 0 0 .316.316h.87l-.001.001h.779a.25.25 0 1 1 0 .502h-5.47a.318.318 0 0 0-.316.317v.32a.318.318 0 0 0 .316.317h3.047a2.246 2.246 0 1 1-.046 3.219H4.81a.318.318 0 0 0-.317.316v.32a.318.318 0 0 0 .317.317h6.394a1.954 1.954 0 0 0 1.8-.994h.765a.318.318 0 0 0 .317-.316zm-10.35 2.766h2.658a2.26 2.26 0 0 1-.14-.276 2.223 2.223 0 0 1-.167-.677H3.737a.318.318 0 0 0-.316.316v.32a.318.318 0 0 0 .316.317zM8.563 8.79v-.287a.238.238 0 0 0-.475 0v.28a1.174 1.174 0 0 0-.397.153.754.754 0 0 0-.376.637.744.744 0 0 0 .171.466.89.89 0 0 0 .291.234 1.113 1.113 0 0 0 .31.1 1.438 1.438 0 0 0 .242.02.76.76 0 0 1 .16.015.66.66 0 0 1 .164.06.51.51 0 0 1 .137.11.256.256 0 0 1 .063.165c0 .058-.016.135-.142.217a.769.769 0 0 1-.397.11 1.208 1.208 0 0 1-.17-.02.829.829 0 0 1-.161-.052.353.353 0 0 1-.131-.1.237.237 0 1 0-.36.31.816.816 0 0 0 .314.23 1.25 1.25 0 0 0 .256.08l.026.004v.287a.237.237 0 1 0 .475 0v-.29a1.197 1.197 0 0 0 .406-.16.718.718 0 0 0 .359-.616.728.728 0 0 0-.18-.479.984.984 0 0 0-.274-.217 1.151 1.151 0 0 0-.284-.103 1.247 1.247 0 0 0-.261-.027.983.983 0 0 1-.165-.013.637.637 0 0 1-.175-.056.42.42 0 0 1-.134-.108.276.276 0 0 1-.065-.167.279.279 0 0 1 .155-.235.755.755 0 0 1 .388-.105.768.768 0 0 1 .138.023l.016.004a.87.87 0 0 1 .164.057.467.467 0 0 1 .137.094.237.237 0 1 0 .336-.336.946.946 0 0 0-.278-.19 1.311 1.311 0 0 0-.258-.09l-.016-.003z&quot;&gt;&lt;/path&gt;&lt;/g&gt;&lt;/svg&gt;', '1'),
(196, '&lt;svg fill=&quot;#000000&quot; viewBox=&quot;0 0 32 32&quot; version=&quot;1.1&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;title&gt;payoneer&lt;/title&gt; &lt;path d=&quot;M30.132 3.312c0.387 0.28 1.732 1.693 0 1.907-2.479 0.292-7.198 0.466-15.023 7.011-5.491 4.759-9.928 10.574-13.021 17.147l-0.134 0.316c-0.035 0.083-0.115 0.141-0.209 0.141-0.022 0-0.042-0.003-0.062-0.009l0.002 0c-0.319-0.092-0.892-0.44-0.6-1.772 2.806-7.801 7.459-14.36 13.448-19.378l0.070-0.057c3.105-2.693 10.517-9.037 15.528-5.305zM2.846 5.141c0.292 2.252 1.293 7.050 1.747 9.076 0.077 0.475 0.207 0.9 0.386 1.299l-0.013-0.033c0.626 1.081 1.133-0.359 1.133-0.359 0.421-1.076 0.665-2.323 0.665-3.626 0-2.717-1.061-5.186-2.79-7.016l0.005 0.005c-0.205-0.219-0.46-0.389-0.747-0.491l-0.013-0.004c-0.4-0.119-0.467 0.107-0.467 0.295 0.013 0.185 0.081 0.706 0.094 0.852z&quot;&gt;&lt;/path&gt; &lt;/g&gt;&lt;/svg&gt;', '1'),
(197, '&lt;svg viewBox=&quot;0 -0.5 34 34&quot; fill=&quot;none&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;path fill-rule=&quot;evenodd&quot; clip-rule=&quot;evenodd&quot; d=&quot;M33.2538 16.1292C33.2538 25.0371 26.0329 32.2584 17.1255 32.2584C8.21799 32.2584 0.99707 25.0371 0.99707 16.1292C0.99707 7.22128 8.21799 0 17.1255 0C26.0329 0 33.2538 7.22128 33.2538 16.1292ZM21.0002 10.1366C23.2438 10.9071 24.8849 12.0607 24.5629 14.2077C24.3291 15.7799 23.4543 16.5403 22.2921 16.8065C23.8866 17.6335 24.4301 19.2029 23.9251 21.1005C22.9664 23.8314 20.6874 24.0613 17.6562 23.4905L16.9202 26.4261L15.1434 25.9844L15.8693 23.0882C15.4087 22.9742 14.9379 22.8522 14.4529 22.7221L13.724 25.6325L11.9492 25.1908L12.6842 22.2491L9.10534 21.3496L9.98817 19.3226C9.98817 19.3226 11.2982 19.6685 11.28 19.6433C11.7832 19.7673 12.0069 19.4406 12.095 19.2238L14.0895 11.256C14.1117 10.8798 13.9811 10.4059 13.2613 10.2264C13.2886 10.2072 11.9705 9.90669 11.9705 9.90669L12.4433 8.01585L16.0272 8.90026L16.7562 5.99188L18.532 6.43358L17.8182 9.28448C18.2961 9.39238 18.776 9.5023 19.2427 9.61828L19.9514 6.78553L21.7282 7.22724L21.0002 10.1366ZM16.7488 14.9882C17.9591 15.3091 20.5928 16.0074 21.0519 14.1765C21.5202 12.3033 18.9615 11.7376 17.7087 11.4606L17.7086 11.4606L17.7085 11.4606C17.5666 11.4292 17.4414 11.4015 17.3393 11.3761L16.4545 14.9117C16.5388 14.9325 16.6378 14.9588 16.7488 14.9882ZM15.3775 20.6807C16.8271 21.0626 19.9976 21.8977 20.5021 19.8803C21.0185 17.8175 17.9445 17.1305 16.4446 16.7952L16.4441 16.7951C16.2767 16.7577 16.129 16.7247 16.008 16.6946L15.032 20.5913C15.1311 20.6158 15.2472 20.6464 15.3771 20.6806L15.3775 20.6807Z&quot; fill=&quot;#F7931A&quot;&gt;&lt;/path&gt; &lt;/g&gt;&lt;/svg&gt;', '1'),
(198, '&lt;svg fill=&quot;#000000&quot; version=&quot;1.1&quot; id=&quot;Capa_1&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot; xmlns:xlink=&quot;http://www.w3.org/1999/xlink&quot; viewBox=&quot;0 0 94.294 94.294&quot; xml:space=&quot;preserve&quot;&gt;&lt;g id=&quot;SVGRepo_bgCarrier&quot; stroke-width=&quot;0&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_tracerCarrier&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;&gt;&lt;/g&gt;&lt;g id=&quot;SVGRepo_iconCarrier&quot;&gt; &lt;g&gt; &lt;path d=&quot;M94.21,19.806l-0.597-2.911c-0.448-2.19-2.59-3.604-4.771-3.168l-0.465,0.09c-0.574,0.109-1.224,0.237-1.898,0.422 c-0.695,0.167-1.34,0.371-1.912,0.554l-0.391,0.123c-0.045,0.015-0.09,0.029-0.135,0.045c-1.499,0.527-3,1.163-4.513,1.911 c-1.382,0.706-2.763,1.501-4.13,2.379c-1.271,0.834-2.539,1.74-3.789,2.711c-1.07,0.845-2.12,1.733-3.147,2.655h-7.819 l-2.176-2.362c-0.742-0.803-1.773-1.276-2.865-1.309c-1.104-0.03-2.152,0.374-2.939,1.132l-2.639,2.539H0v56.029h77.196V33.895 c0.802-0.854,1.625-1.675,2.465-2.455c0.882-0.81,1.808-1.594,2.726-2.309c0.916-0.701,1.873-1.361,2.849-1.97l0.304-0.178 c0.367-0.216,0.734-0.433,1.115-0.623c0.05-0.026,0.101-0.052,0.152-0.08c0.334-0.185,0.681-0.341,1.025-0.502l0.756-0.331 c0.34-0.14,0.68-0.283,1.028-0.393c0.063-0.021,0.127-0.044,0.19-0.067c0.326-0.122,0.662-0.212,0.996-0.306l0.575-0.164 C93.404,23.92,94.635,21.874,94.21,19.806z M16.95,72.829h-4.472l0.016-16.451l-3.879,3.415l-2.156-2.691l6.815-5.428h3.676V72.829 z M45.297,72.829H30.508v-3.109l5.311-5.369c1.572-1.609,2.6-2.729,3.082-3.35c0.483-0.622,0.83-1.197,1.043-1.729 c0.212-0.53,0.318-1.08,0.318-1.65c0-0.848-0.234-1.479-0.703-1.896c-0.468-0.414-1.092-0.621-1.873-0.621 c-0.821,0-1.616,0.188-2.389,0.563c-0.771,0.377-1.576,0.911-2.416,1.605l-2.431-2.879c1.042-0.89,1.905-1.517,2.59-1.881 c0.684-0.366,1.432-0.648,2.243-0.848c0.81-0.196,1.716-0.298,2.72-0.298c1.321,0,2.489,0.241,3.501,0.724 c1.014,0.483,1.8,1.159,2.359,2.026c0.559,0.869,0.838,1.862,0.838,2.981c0,0.974-0.17,1.889-0.513,2.741 c-0.343,0.854-0.873,1.729-1.592,2.625c-0.719,0.897-1.984,2.179-3.798,3.835l-2.721,2.562v0.202h9.218v3.763H45.297z M67.494,71.418c-1.477,1.135-3.583,1.7-6.324,1.7c-2.295,0-4.33-0.381-6.105-1.143v-3.809c0.82,0.415,1.722,0.755,2.705,1.016 c0.986,0.26,1.959,0.39,2.924,0.39c1.476,0,2.566-0.251,3.27-0.753c0.705-0.502,1.057-1.307,1.057-2.416 c0-0.993-0.405-1.698-1.215-2.112c-0.811-0.415-2.104-0.622-3.879-0.622H58.32v-3.431h1.635c1.641,0,2.839-0.214,3.596-0.642 c0.758-0.431,1.137-1.167,1.137-2.209c0-1.602-1.004-2.4-3.01-2.4c-0.695,0-1.401,0.115-2.121,0.347 c-0.717,0.231-1.516,0.631-2.394,1.201l-2.069-3.083c1.928-1.389,4.229-2.084,6.901-2.084c2.19,0,3.919,0.445,5.188,1.332 c1.268,0.888,1.902,2.123,1.902,3.704c0,1.321-0.4,2.445-1.201,3.373c-0.801,0.925-1.925,1.562-3.371,1.909v0.088 c1.707,0.21,3,0.729,3.877,1.555c0.877,0.825,1.316,1.937,1.316,3.335C69.708,68.701,68.971,70.285,67.494,71.418z M88.145,20.888 c-0.61,0.195-1.205,0.459-1.803,0.697c-0.584,0.277-1.176,0.533-1.744,0.85c-0.58,0.289-1.138,0.626-1.699,0.953 c-1.106,0.685-2.182,1.428-3.221,2.223c-1.033,0.806-2.037,1.653-3.004,2.543c-1.93,1.791-3.738,3.73-5.408,5.78 c-0.836,1.025-1.642,2.074-2.412,3.146c-0.381,0.534-0.762,1.073-1.123,1.619c-0.355,0.532-0.736,1.114-1.021,1.601l-1.827,3.007 l-2.593-2.58L50.643,29.131l4.596-4.42l8.925,9.688c0.167-0.202,0.331-0.41,0.499-0.61c0.932-1.088,1.887-2.146,2.876-3.174 c1.976-2.056,4.093-3.982,6.345-5.756c1.129-0.879,2.301-1.719,3.502-2.505c1.213-0.779,2.463-1.501,3.754-2.163 c1.299-0.642,2.638-1.212,4.01-1.694c0.697-0.22,1.388-0.454,2.102-0.62c0.702-0.198,1.428-0.318,2.145-0.461l0.597,2.911 C89.373,20.51,88.75,20.663,88.145,20.888z&quot;&gt;&lt;/path&gt; &lt;/g&gt; &lt;/g&gt;&lt;/svg&gt;', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_users`
--

CREATE TABLE `i_users` (
  `iuid` int NOT NULL,
  `userType` enum('1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `i_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `i_user_fullname` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `i_user_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `i_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_avatar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_gender` varchar(85) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registered` int DEFAULT NULL,
  `last_login_time` int DEFAULT NULL,
  `online_offline_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `uStatus` enum('1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '3',
  `lang` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'eng',
  `notification_read_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `message_notification_read_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `post_who_can_see` enum('1','2','3','4') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `user_verified_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `profile_status` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `profile_category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `u_bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `certification_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `validation_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `condition_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `fees_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `payout_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `payout_method` enum('paypal','bank') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paypal_email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bank_account` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `wallet_points` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `wallet_money` decimal(10,2) DEFAULT '0.00',
  `email_notification_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `light_dark` enum('light','dark') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'light',
  `show_hide_posts` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `connections_visibility` tinyint(1) NOT NULL DEFAULT '1',
  `message_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '1',
  `countryCode` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `u_timezone` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lon` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `forgot_pass_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `login_with` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verify_status` enum('no','yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `verify_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `thanks_for_tip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `device_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `qr_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `affilate_earnings` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fake_user_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `video_call_price` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '50',
  `who_can_call` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `who_can_message` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `who_can_send_message` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `user_frame` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `show_profile_gender` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_age` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_birthdate` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_category` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_likes` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_comments` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_bio` tinyint(1) NOT NULL DEFAULT '1',
  `show_profile_social` tinyint(1) NOT NULL DEFAULT '1',
  `age_verify_status` enum('0','1') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `age_verify_provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age_verified_at` int DEFAULT NULL,
  `age_verify_ref` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tiktok_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_country` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iban_number` varchar(42) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `routing_number` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_holder_name` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_country_code` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `street_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_address_country` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_address_state` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_address_city` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` varchar(24) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_users`
--

INSERT INTO `i_users` (`iuid`, `userType`, `i_username`, `i_user_fullname`, `i_user_email`, `birthday`, `i_password`, `user_avatar`, `user_cover`, `user_gender`, `registered`, `last_login_time`, `online_offline_status`, `uStatus`, `lang`, `notification_read_status`, `message_notification_read_status`, `post_who_can_see`, `user_verified_status`, `profile_status`, `profile_category`, `u_bio`, `certification_status`, `validation_status`, `condition_status`, `fees_status`, `payout_status`, `payout_method`, `paypal_email`, `bank_account`, `wallet_points`, `wallet_money`, `email_notification_status`, `light_dark`, `show_hide_posts`, `connections_visibility`, `message_status`, `countryCode`, `u_timezone`, `lat`, `lon`, `forgot_pass_code`, `login_with`, `email_verify_status`, `verify_key`, `thanks_for_tip`, `device_key`, `qr_image`, `affilate_earnings`, `fake_user_status`, `video_call_price`, `who_can_call`, `who_can_message`, `who_can_send_message`, `user_frame`, `show_profile_gender`, `show_profile_age`, `show_profile_birthdate`, `show_profile_category`, `show_profile_likes`, `show_profile_comments`, `show_profile_bio`, `show_profile_social`, `age_verify_status`, `age_verify_provider`, `age_verified_at`, `age_verify_ref`, `instagram_url`, `tiktok_url`, `bank_country`, `iban_number`, `routing_number`, `account_number`, `account_holder_name`, `phone_country_code`, `phone_number`, `street_address`, `bank_address_country`, `bank_address_state`, `bank_address_city`, `postal_code`) VALUES
(1, '2', 'adminstaff', 'admin', 'admin@dizzy.com', '1982-08-03', '$2y$10$9E3LnE3lk1XFUxC1dl2dPeLOn.rrzKv1Ro0PpV2ozMjQfAj.s5kju', '3', '2', 'male', 1618053393, 1777681907, '1', '3', 'eng', '0', '0', '1', '1', '1', 'normal_user', '', '2', '2', '2', '2', '2', 'bank', '', NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'TR', 'Europe/Istanbul', NULL, NULL, NULL, NULL, 'no', '', NULL, NULL, '', '0', '0', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '1', 'maybell50_327', 'Jairo Mayert', 'burdette16_911@yahoo.com', '2018-08-13', '$2y$10$0nyfvlz9u2cvdH4/YG9YKeuKVcoEcyQwuDZdlOnwVt136MqZNFipm', NULL, NULL, 'male', 1776617939, 1776713427, '1', '3', 'eng', '1', '0', '1', '1', '1', 'artists', NULL, '2', '2', '2', '2', '2', 'bank', NULL, '51465166561651561561616', '0', 0.00, '1', 'light', '0', 1, '1', 'SI', NULL, '-37.397278', '172.207718', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, '1', 'matilda52_622', 'Kennith Mills', 'krajcik.evert_191@yahoo.com', '1994-05-12', '$2y$10$8wnqfsQGx2qzhizfmbWi/O0g7H/AeGJdEWJvrN3vFNSt/P0SoHBym', NULL, NULL, 'male', 1776617939, 1776730324, '1', '3', 'eng', '1', '0', '1', '0', '1', 'youtuber', NULL, '2', '0', '2', '2', '0', NULL, NULL, NULL, '0', 0.00, '1', 'dark', '0', 1, '1', 'GH', NULL, '-40.720926', '39.82817', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, '1', 'lavonne.hegmann_889', 'Marguerite Hills', 'kreiger.pat_715@yahoo.com', '2014-06-21', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'female', 1776617939, 1776617939, '1', '3', 'eng', '0', '0', '1', '0', '1', 'developer', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'MZ', NULL, '83.865573', '74.31199', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '1', 'ottis.mueller_526', 'Ryan Ullrich', 'ghickle_678@yahoo.com', '2002-02-26', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776617939, 1776617939, '1', '3', 'eng', '0', '0', '1', '0', '1', 'artists', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'VN', NULL, '-73.111201', '102.215293', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '1', 'wlesch_595', 'Adela Jenkins', 'jannie23_355@yahoo.com', '2001-03-24', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'female', 1776617939, 1776617939, '1', '3', 'eng', '0', '0', '1', '0', '1', 'athletes', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'KW', NULL, '-72.315468', '-26.826833', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '1', 'ashly.bechtelar_515', 'Llewellyn Bogisich', 'ethyl.hand_234@yahoo.com', '1987-09-15', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776617939, 1776617939, '1', '3', 'eng', '0', '0', '1', '0', '1', 'financial_tipper', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'CH', NULL, '-26.740733', '89.311642', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, '1', 'nitzsche.ewell_710', 'Allie Powlowski', 'kemmer.buddy_375@yahoo.com', '2006-10-30', '$2y$10$4HGgWfXJInJwD1ybztcsVuwqkTgPgh4nXxyvyHyc/vaU0seyHBWA6', NULL, NULL, 'female', 1776617939, 1776638926, '1', '3', 'eng', '1', '0', '1', '1', '1', 'developer', NULL, '2', '2', '2', '2', '2', 'bank', NULL, 'ssdqsdqs', '0', 0.00, '1', 'light', '0', 1, '1', 'CI', NULL, '72.040687', '-85.787135', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, '1', 'pfannerstill.vito_853', 'Amiya Wisoky', 'streutel_670@yahoo.com', '2023-03-05', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'female', 1776617939, 1776617939, '1', '3', 'eng', '0', '0', '1', '0', '1', 'artists', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'IT', NULL, '29.675207', '-127.263054', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '1', 'kwyman_489', 'Delpha Borer', 'kamryn79_881@yahoo.com', '2025-05-05', '$2y$10$yd8kJmBnDVh6me/wlRJGa.CEWvOrbrDUGhfbzBPAgEljdR14DFTYC', NULL, NULL, 'female', 1776617939, 1776621354, '1', '3', 'eng', '1', '0', '1', '1', '1', 'first_new_category', NULL, '2', '2', '2', '2', '2', 'bank', NULL, 'sqsqsqsqsq', '0', 0.00, '1', 'light', '0', 1, '1', 'JM', NULL, '41.235014', '153.354425', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, '1', 'rylan.schuppe_586', 'Larry Larson', 'wuckert.kenton_261@yahoo.com', '2006-06-08', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776617939, 1776617939, '1', '3', 'eng', '0', '0', '1', '0', '1', 'youtuber', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'NG', NULL, '-73.592344', '-99.321671', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '1', 'orenner_947', 'Jaylan Bechtelar', 'sonia32_713@yahoo.com', '1982-12-04', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776990821, 1776990821, '1', '3', 'eng', '0', '0', '1', '0', '1', 'first_new_category', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'PW', NULL, '82.566165', '-11.579566', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, '1', 'marlin78_298', 'Justice Strosin', 'beier.obie_288@yahoo.com', '1986-12-26', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776990821, 1776990821, '1', '3', 'eng', '0', '0', '1', '0', '1', 'artists', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'KP', NULL, '20.907838', '-169.745345', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, '1', 'toconner_587', 'Reece Haag', 'margie.olson_181@yahoo.com', '2023-10-14', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776990821, 1776990821, '1', '3', 'eng', '0', '0', '1', '0', '1', 'normal_user', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'PH', NULL, '-56.506929', '-99.876329', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, '1', 'lolita21_115', 'Nolan Kovacek', 'rau.anais_906@yahoo.com', '1972-11-20', '$2y$10$MEeCtBPwOADekIuXzGh1V.v8biAxiIb7jVQVE6EjqG8xzc7BNZ71m', NULL, NULL, 'male', 1776990821, 1777167294, '1', '3', 'eng', '0', '0', '1', '0', '1', 'designer', NULL, '2', '1', '2', '2', '2', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'BJ', NULL, '-56.014915', '122.043507', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, '1', 'lucious.murazik_263', 'Raina Nienow', 'damore.eladio_719@yahoo.com', '1980-03-12', '$2y$10$/iNh6Ft/MQXdPnTv1QUfaOjkRUI9Eb9PzfDurpEozRipTXwh5CWJO', NULL, NULL, 'female', 1776990821, 1777167680, '1', '3', 'eng', '0', '0', '1', '0', '1', 'influencer', NULL, '2', '1', '2', '2', '2', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'FM', NULL, '50.393151', '-44.189564', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, '1', 'morgan.pagac_696', 'Edgar Fay', 'clarabelle.rolfson_830@yahoo.com', '1984-06-24', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776990821, 1776990821, '1', '3', 'eng', '0', '0', '1', '0', '1', 'financial_tipper', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'LI', NULL, '30.911959', '-104.206569', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, '1', 'hauck.sabrina_152', 'Valentin Wyman', 'beaulah.daniel_828@yahoo.com', '1997-02-17', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776990821, 1776990821, '1', '3', 'eng', '0', '0', '1', '0', '1', 'artists', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'IR', NULL, '54.685498', '34.974917', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, '1', 'dcasper_128', 'Alexandria Mann', 'omari60_958@yahoo.com', '1988-10-05', '$2y$10$HTiumC8ROZYSAiEr3ilP8.TmqF0oRWnK2RG66dpn4h6.37GjePiyC', NULL, NULL, 'female', 1776990821, 1777660779, '1', '3', 'eng', '0', '0', '1', '1', '1', 'first_new_category', NULL, '2', '2', '2', '2', '2', 'bank', NULL, 'Address: , ,  ,', '50', 0.00, '1', 'light', '0', 1, '1', 'BN', NULL, '30.330137', '-142.121207', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', '', '', '', '', '', '', '', '', '', '', '', ''),
(20, '1', 'kruecker_476', 'Orland Klocko', 'kmclaughlin_979@yahoo.com', '2024-01-19', 'a346bc80408d9b2a5063fd1bddb20e2d5586ec30', NULL, NULL, 'male', 1776990821, 1776990821, '1', '3', 'eng', '0', '0', '1', '0', '1', 'youtuber', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'SH', NULL, '58.802572', '-139.748583', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, '1', 'graham16_558', 'Janiya Gleason', 'vandervort.ernie_304@yahoo.com', '2020-10-26', '$2y$10$4zxHFCP86Lrez7fhH7.6hObfSTITqJm3XKCI018EUqHEttfB01fs6', NULL, NULL, 'female', 1776990821, 1777663686, '1', '3', 'eng', '1', '0', '1', '1', '1', 'youtuber', NULL, '2', '2', '2', '2', '2', 'bank', NULL, 'Holder: adams bgh\nBank Country: GB\nRouting/SWIFT: REVOGB21\nAccount: GB15REVO00996928314866\nPhone: +216 959484851\nAddress: 6248 Jakayla Flats Apt. 251, Aidastad, CA 74918, TN', '500', 20.00, '1', 'light', '0', 1, '1', 'SM', NULL, '-58.150776', '57.814009', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, 'http://localhost/creator/becomeCreator', 'http://localhost/creator/becomeCreator', 'GB', '', 'REVOGB21', 'GB15REVO00996928314866', 'adams bgh', '216', '959484851', '6248 Jakayla Flats Apt. 251', 'TN', 'CA', 'Aidastad', '74918'),
(22, '1', 'zhaley_494', 'Elva Hermiston', 'qrussel_149@yahoo.com', '1988-11-02', '$2y$10$xx1KTiHyqGcUG6Ij8Tn48.eGLVlyAHSMvCsZCgQKst90ijcyExo2q', NULL, NULL, 'female', 1776990827, 1777663470, '1', '3', 'eng', '0', '0', '1', '0', '1', 'financial_tipper', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'EG', NULL, '16.83625', '-133.942618', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, '1', 'rbogisich_455', 'Selmer Kulas', 'imonahan_669@yahoo.com', '2012-07-21', '$2y$10$L508djImytM7UCOwKrugTOlG149NbY1nvThv7sFEEAzvBUU./bKha', NULL, NULL, 'male', 1776990827, 1777675958, '1', '3', 'eng', '0', '0', '1', '0', '1', 'designer', NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', 0.00, '1', 'light', '0', 1, '1', 'BF', NULL, '-76.344644', '14.41458', NULL, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, '1', '50', '0', '0', '0', NULL, 1, 1, 1, 1, 1, 1, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_user_activity`
--

CREATE TABLE `i_user_activity` (
  `activity_id` int NOT NULL,
  `uid_fk` int DEFAULT NULL,
  `post_id` int DEFAULT NULL,
  `comment_id` int DEFAULT NULL,
  `fr_id` int DEFAULT NULL,
  `like_id` int DEFAULT NULL,
  `comment_like_id` int DEFAULT NULL,
  `activity_type` varchar(55) DEFAULT NULL,
  `activity_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `i_user_activity`
--

INSERT INTO `i_user_activity` (`activity_id`, `uid_fk`, `post_id`, `comment_id`, `fr_id`, `like_id`, `comment_like_id`, `activity_type`, `activity_time`) VALUES
(18, 1, 18, NULL, NULL, NULL, NULL, 'newPost', 1776613893),
(20, 1, 20, NULL, NULL, NULL, NULL, 'newPost', 1776617580),
(21, 10, 21, NULL, NULL, NULL, NULL, 'newPost', 1776621365),
(22, 2, 22, NULL, NULL, NULL, NULL, 'newPost', 1776639119),
(23, 2, 23, NULL, NULL, NULL, NULL, 'newPost', 1776643024),
(24, 3, 24, NULL, NULL, NULL, NULL, 'newPost', 1776727634),
(26, 1, 25, NULL, NULL, NULL, NULL, 'newPost', 1776993796),
(28, 1, NULL, NULL, 3, NULL, NULL, 'userFollow', 1777660744),
(29, 19, 26, NULL, NULL, NULL, NULL, 'newPost', 1777660789),
(30, 23, 27, NULL, NULL, NULL, NULL, 'newPost', 1777661265),
(33, 23, 30, NULL, NULL, NULL, NULL, 'newPost', 1777661642),
(34, 23, 30, NULL, NULL, NULL, NULL, 'postLike', 1777662965),
(35, 23, 31, NULL, NULL, NULL, NULL, 'newPost', 1777663108),
(36, 23, 31, NULL, NULL, NULL, NULL, 'postLike', 1777663269),
(37, 21, 32, NULL, NULL, NULL, NULL, 'newPost', 1777663686),
(39, 1, NULL, NULL, 21, NULL, NULL, 'userFollow', 1777665788),
(40, 23, 33, NULL, NULL, NULL, NULL, 'newPost', 1777669762),
(41, 23, 34, NULL, NULL, NULL, NULL, 'newPost', 1777670718),
(42, 23, 35, NULL, NULL, NULL, NULL, 'newPost', 1777671065),
(43, 1, 36, NULL, NULL, NULL, NULL, 'newPost', 1777671383),
(44, 1, 37, NULL, NULL, NULL, NULL, 'newPost', 1777672481),
(45, 1, 38, NULL, NULL, NULL, NULL, 'newPost', 1777674067),
(46, 1, 39, NULL, NULL, NULL, NULL, 'newPost', 1777681813);

-- --------------------------------------------------------

--
-- Table structure for table `i_user_avatars`
--

CREATE TABLE `i_user_avatars` (
  `avatar_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `avatar_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `avatar_upload_time` int NOT NULL DEFAULT '1609459200',
  `ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_avatars`
--

INSERT INTO `i_user_avatars` (`avatar_id`, `iuid_fk`, `avatar_path`, `avatar_upload_time`, `ip`) VALUES
(3, 1, 'uploads/avatars/2026-04-24/avatar_1776983671025663200_1.png', 1776983671, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `i_user_blocked_countries`
--

CREATE TABLE `i_user_blocked_countries` (
  `b_c_id` int NOT NULL,
  `b_iuid_fk` int DEFAULT NULL,
  `b_country` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `b_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_blocks`
--

CREATE TABLE `i_user_blocks` (
  `block_id` int NOT NULL,
  `blocker_iuid` int NOT NULL,
  `blocked_iuid` int NOT NULL,
  `block_type` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blocked_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_conversation_uploads`
--

CREATE TABLE `i_user_conversation_uploads` (
  `upload_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `con_id_fk` int DEFAULT NULL,
  `uploaded_file_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `uploaded_x_file_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `uploaded_file_ext` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `upload_time` int DEFAULT NULL,
  `ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_covers`
--

CREATE TABLE `i_user_covers` (
  `cover_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `cover_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cover_upload_time` int NOT NULL DEFAULT '1609459200',
  `ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_covers`
--

INSERT INTO `i_user_covers` (`cover_id`, `iuid_fk`, `cover_path`, `cover_upload_time`, `ip`) VALUES
(2, 1, 'uploads/covers/2026-04-24/cover_1776983676065484300_1.png', 1776983676, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `i_user_deletion_requests`
--

CREATE TABLE `i_user_deletion_requests` (
  `request_id` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `request_status` enum('pending','cancelled','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `requested_at` int NOT NULL DEFAULT '0',
  `delete_after` int NOT NULL DEFAULT '0',
  `canceled_at` int DEFAULT NULL,
  `completed_at` int DEFAULT NULL,
  `previous_u_status` enum('1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '3',
  `cancel_reason` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `request_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_exports`
--

CREATE TABLE `i_user_exports` (
  `export_id` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `export_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_size` bigint UNSIGNED NOT NULL DEFAULT '0',
  `generation_seconds` smallint UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('ready','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ready',
  `created_at` int NOT NULL DEFAULT '0',
  `expires_at` int NOT NULL DEFAULT '0',
  `downloaded_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_frames`
--

CREATE TABLE `i_user_frames` (
  `f_id` int NOT NULL,
  `f_puchased_user_id` int DEFAULT NULL,
  `f_purchaser_user_id` int DEFAULT NULL,
  `f_purchased_time` int DEFAULT NULL,
  `f_purchase_price` varchar(11) DEFAULT NULL,
  `f_purchased_frame_id` int DEFAULT NULL,
  `f_total_purchased_this_frame` varchar(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_notifications`
--

CREATE TABLE `i_user_notifications` (
  `not_id` int NOT NULL,
  `not_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `not_post_id` int DEFAULT NULL,
  `not_comment_id` int DEFAULT NULL,
  `not_type` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `not_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `not_not_type` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `not_time` int NOT NULL DEFAULT '1605830400',
  `not_iuid` int DEFAULT NULL,
  `not_own_iuid` int DEFAULT NULL,
  `not_show_hide` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_notifications`
--

INSERT INTO `i_user_notifications` (`not_id`, `not_status`, `not_post_id`, `not_comment_id`, `not_type`, `not_payload`, `not_not_type`, `not_time`, `not_iuid`, `not_own_iuid`, `not_show_hide`) VALUES
(1, '1', NULL, NULL, NULL, NULL, 'verification_declined', 1776619593, 1, 10, '0'),
(2, '1', NULL, NULL, NULL, NULL, 'verification_approved', 1776621339, 1, 10, '0'),
(3, '1', NULL, NULL, NULL, NULL, 'verification_approved', 1776639081, 1, 2, '0'),
(4, '1', NULL, NULL, NULL, NULL, 'verification_approved', 1776639326, 1, 2, '0'),
(5, '0', NULL, NULL, NULL, NULL, 'verification_approved', 1776639336, 1, 8, '0'),
(6, '0', NULL, NULL, NULL, NULL, 'verification_approved', 1776639343, 1, 10, '0'),
(7, '0', 23, NULL, 'image', NULL, 'commented', 1776982889, 1, 2, '0'),
(8, '0', 23, NULL, 'image', NULL, 'commented', 1776982892, 1, 2, '0'),
(11, '1', NULL, NULL, NULL, NULL, 'verification_approved', 1777168430, 1, 21, '0'),
(12, '1', NULL, NULL, NULL, NULL, 'verification_approved', 1777168575, 1, 19, '0'),
(13, '1', 0, NULL, 'text', NULL, 'tip', 1777168627, 19, 21, '0'),
(14, '1', 0, NULL, 'text', NULL, 'tip', 1777168639, 19, 21, '0'),
(15, '0', NULL, NULL, NULL, NULL, 'follow', 1777660744, 1, 3, '0'),
(17, '0', NULL, NULL, NULL, NULL, 'follow', 1777665788, 1, 21, '0');

-- --------------------------------------------------------

--
-- Table structure for table `i_user_payments`
--

CREATE TABLE `i_user_payments` (
  `payment_id` int NOT NULL,
  `payer_iuid_fk` int DEFAULT NULL,
  `payed_iuid_fk` int DEFAULT NULL,
  `agency_id_fk` int DEFAULT NULL,
  `payed_live_stream_id_fk` int DEFAULT NULL,
  `payed_post_id_fk` int DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `paymet_product_id` int DEFAULT NULL,
  `payed_profile_id_fk` int DEFAULT NULL,
  `order_key` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `payment_type` enum('point','product','subscription','campaign_donate','tips','post','live_stream','live_gift','videoCall','boostPost','profile','frame','unlockmessage','agency_boost') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payment_option` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_time` int NOT NULL DEFAULT '1609459200',
  `payment_status` enum('ok','declined','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ok',
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fee` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agency_fee` decimal(5,2) NOT NULL DEFAULT '0.00',
  `admin_earning` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agency_earning` decimal(10,2) NOT NULL DEFAULT '0.00',
  `user_earning` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_rate` decimal(6,4) DEFAULT '0.0000',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `invoice_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billing_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billing_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billing_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `credit_plan_id` int DEFAULT NULL,
  `purchased_frame_id` int DEFAULT NULL,
  `unlocked_message_id` int DEFAULT NULL,
  `bank_payment_image` int DEFAULT NULL,
  `agency_boost_duration_days` int DEFAULT NULL,
  `konnect_payment_ref` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_payments`
--

INSERT INTO `i_user_payments` (`payment_id`, `payer_iuid_fk`, `payed_iuid_fk`, `agency_id_fk`, `payed_live_stream_id_fk`, `payed_post_id_fk`, `is_anonymous`, `paymet_product_id`, `payed_profile_id_fk`, `order_key`, `payment_type`, `payment_option`, `payment_time`, `payment_status`, `amount`, `fee`, `agency_fee`, `admin_earning`, `agency_earning`, `user_earning`, `currency`, `tax_rate`, `tax_amount`, `invoice_number`, `invoice_token`, `billing_name`, `billing_email`, `billing_address`, `credit_plan_id`, `purchased_frame_id`, `unlocked_message_id`, `bank_payment_image`, `agency_boost_duration_days`, `konnect_payment_ref`, `updated_at`) VALUES
(1, 19, 21, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'tips', 'wallet', 1777168627, 'ok', '50', '20', 0.00, '10', 0.00, '40', 'USD', 0.0000, 0.00, 'INV000001', '1e227c19b13088e8e3750041d49b186a77c6', 'Alexandria Mann', 'omari60_958@yahoo.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 19, 21, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'tips', 'wallet', 1777168639, 'ok', '100', '20', 0.00, '20', 0.00, '80', 'USD', 0.0000, 0.00, 'INV000002', '99280ce7442a359220c1ce6f0f0e14bdddeb', 'Alexandria Mann', 'omari60_958@yahoo.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_user_payouts`
--

CREATE TABLE `i_user_payouts` (
  `payout_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `amount` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `method` enum('paypal','bank') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'paypal',
  `payment_type` enum('withdrawal','subscription') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payout_time` int NOT NULL DEFAULT '1609459200',
  `paid_time` int DEFAULT NULL,
  `status` enum('pending','payed','declined') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_payouts`
--

INSERT INTO `i_user_payouts` (`payout_id`, `iuid_fk`, `amount`, `method`, `payment_type`, `payout_time`, `paid_time`, `status`) VALUES
(1, 21, '100', 'bank', 'withdrawal', 1777168741, NULL, 'payed');

-- --------------------------------------------------------

--
-- Table structure for table `i_user_point_earnings`
--

CREATE TABLE `i_user_point_earnings` (
  `point_id` int NOT NULL,
  `poninted_post_id` int DEFAULT NULL,
  `poninted_user_id` int DEFAULT NULL,
  `pointed_time` int NOT NULL DEFAULT '1524910573',
  `pointed_type` varchar(35) DEFAULT NULL,
  `calculated_point` enum('0','1') NOT NULL DEFAULT '0',
  `point` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `i_user_point_earnings`
--

INSERT INTO `i_user_point_earnings` (`point_id`, `poninted_post_id`, `poninted_user_id`, `pointed_time`, `pointed_type`, `calculated_point`, `point`) VALUES
(18, 18, 1, 1776613893, 'new_post', '0', '0.1'),
(20, 20, 1, 1776617580, 'new_post', '0', '0.1'),
(21, 21, 10, 1776621365, 'new_post', '0', '0.1'),
(22, 22, 2, 1776639119, 'new_post', '0', '0.1'),
(23, 23, 2, 1776643024, 'new_post', '0', '0.1'),
(24, 24, 3, 1776727634, 'new_post', '0', '0.1'),
(25, 23, 1, 1776982889, 'comment', '0', '0.1'),
(26, 25, 1, 1776993796, 'new_post', '0', '0.1'),
(27, 26, 19, 1777660789, 'new_post', '0', '0.1');

-- --------------------------------------------------------

--
-- Table structure for table `i_user_product_posts`
--

CREATE TABLE `i_user_product_posts` (
  `pr_id` int NOT NULL,
  `pr_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pr_price` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pr_files` varchar(155) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pr_downlodable_files` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pr_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pr_desc_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pr_created_time` int DEFAULT NULL,
  `iuid_fk` int DEFAULT NULL,
  `pr_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  `pr_seen_time` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `pr_number_of_sales` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `pr_name_slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `product_type` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pr_slots_number` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pr_question_answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_stories`
--

CREATE TABLE `i_user_stories` (
  `s_id` int NOT NULL,
  `uid_fk` int DEFAULT NULL,
  `uploaded_file_path` varchar(250) DEFAULT NULL,
  `upload_tumbnail_file_path` text,
  `uploaded_x_file_path` text,
  `uploaded_file_ext` text,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `text_style` varchar(35) DEFAULT NULL,
  `created` int NOT NULL DEFAULT '1524910573',
  `status` enum('1','2') NOT NULL DEFAULT '1',
  `story_type` varchar(25) DEFAULT NULL,
  `story_privacy` enum('followers','subscribers','everyone') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'followers',
  `story_quick_replies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `story_overlay_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `story_overlay_mention` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `story_overlay_sticker_id` int NOT NULL DEFAULT '0',
  `story_audio_id` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `i_user_stories`
--

INSERT INTO `i_user_stories` (`s_id`, `uid_fk`, `uploaded_file_path`, `upload_tumbnail_file_path`, `uploaded_x_file_path`, `uploaded_file_ext`, `text`, `text_style`, `created`, `status`, `story_type`, `story_privacy`, `story_quick_replies`, `story_overlay_link`, `story_overlay_mention`, `story_overlay_sticker_id`, `story_audio_id`) VALUES
(1, 1, 'uploads/reels/2026-05-02/dbb67c395046c2f77cc5f31dc759263c_reels_blur.mp4', 'uploads/reels/2026-05-02/dbb67c395046c2f77cc5f31dc759263c_reels_blur.jpg', 'uploads/reels/2026-05-02/dbb67c395046c2f77cc5f31dc759263c_reels_blur.jpg', 'mp4', NULL, NULL, 1777681814, '1', NULL, 'followers', NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `i_user_subscribe_plans`
--

CREATE TABLE `i_user_subscribe_plans` (
  `plan_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `amount` int DEFAULT NULL,
  `plan_type` enum('weekly','monthly','yearly') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'monthly',
  `plan_created_time` int DEFAULT NULL,
  `plan_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `stripe_plan_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stripe_product_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_subscribe_plans`
--

INSERT INTO `i_user_subscribe_plans` (`plan_id`, `iuid_fk`, `amount`, `plan_type`, `plan_created_time`, `plan_status`, `stripe_plan_id`, `stripe_product_id`) VALUES
(1, 10, 3, 'weekly', 1776619400, '1', NULL, NULL),
(2, 10, 55, 'monthly', 1776619400, '1', NULL, NULL),
(3, 10, 55, 'yearly', 1776619400, '1', NULL, NULL),
(4, 8, 3, 'weekly', 1776621438, '0', NULL, NULL),
(5, 8, 55, 'monthly', 1776621438, '0', NULL, NULL),
(6, 8, 55, 'yearly', 1776621438, '0', NULL, NULL),
(7, 2, 3, 'weekly', 1776639030, '0', NULL, NULL),
(8, 2, 55, 'monthly', 1776639030, '0', NULL, NULL),
(9, 2, 55, 'yearly', 1776639030, '0', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `i_user_subscriptions`
--

CREATE TABLE `i_user_subscriptions` (
  `subscription_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `subscribed_iuid_fk` int DEFAULT NULL,
  `agency_id_fk` int DEFAULT NULL,
  `subscriber_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_method` enum('stripe','point','ccbill') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'stripe',
  `payment_subscription_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `customer_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `plan_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_amount` float(10,2) DEFAULT NULL,
  `tax_rate` decimal(6,4) DEFAULT '0.0000',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `fee` decimal(5,2) NOT NULL DEFAULT '0.00',
  `agency_fee` decimal(5,2) NOT NULL DEFAULT '0.00',
  `admin_earning` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agency_earning` decimal(10,2) NOT NULL DEFAULT '0.00',
  `user_net_earning` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_amount_currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_interval` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_interval_count` tinyint DEFAULT NULL,
  `payer_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `plan_period_start` datetime DEFAULT NULL,
  `plan_period_end` datetime DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `in_status` int DEFAULT '0',
  `finished` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `subscription_scope` enum('profile','community','community_plan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'profile',
  `subscription_ref_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_subscription_intents`
--

CREATE TABLE `i_user_subscription_intents` (
  `intent_id` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `subscribed_iuid_fk` int NOT NULL,
  `subscriber_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subscriber_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subscription_scope` enum('profile','community','community_plan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'profile',
  `subscription_ref_id` int DEFAULT NULL,
  `plan_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_amount` decimal(10,2) DEFAULT NULL,
  `plan_amount_currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_interval` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_interval_count` tinyint DEFAULT NULL,
  `tax_rate` decimal(6,4) DEFAULT '0.0000',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `order_key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_option` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'yookassa',
  `yookassa_payment_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yookassa_idempotence_key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `epoch_nonce` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `epoch_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `epoch_env` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','ok','declined') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_user_uploads`
--

CREATE TABLE `i_user_uploads` (
  `upload_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `uploaded_file_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `upload_tumbnail_file_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `uploaded_x_file_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `uploaded_file_ext` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `upload_time` int DEFAULT NULL,
  `ip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `upload_type` enum('wall','profile','product','verification','bankPayment','reels') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'wall',
  `upload_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_user_uploads`
--

INSERT INTO `i_user_uploads` (`upload_id`, `iuid_fk`, `uploaded_file_path`, `upload_tumbnail_file_path`, `uploaded_x_file_path`, `uploaded_file_ext`, `upload_time`, `ip`, `upload_type`, `upload_status`) VALUES
(4, 1, 'uploads/files/2026-04-15/image_1776209671021861100_1.mp4', 'uploads/web.png', 'uploads/files/2026-04-15/image_1776209671021861100_1.mp4', 'mp4', 1776209671, '::1', 'wall', '0'),
(10, 1, 'uploads/files/2026-04-15/image_1776211300035029500_1.mp4', 'uploads/web.png', 'uploads/files/2026-04-15/image_1776211300035029500_1.mp4', 'mp4', 1776211300, '::1', 'wall', '0'),
(20, 1, 'uploads/files/2026-04-19/image_1776613886020628600_1.jpg', 'uploads/files/2026-04-19/image_1776613886020628600_1__1.jpg', 'uploads/pixel/2026-04-19/image_1776613886020628600_1.jpg', 'jpg', 1776613886, '::1', 'wall', '1'),
(22, 1, 'uploads/files/2026-04-19/image_1776617576004300800_1.mp4', 'uploads/files/2026-04-19/image_1776617576004300800_1.jpg', 'uploads/xvideos/2026-04-19/image_1776617576004300800_1.mp4', 'mp4', 1776617576, '::1', 'wall', '1'),
(23, 10, 'uploads/files/2026-04-19/image_1776619341066766500_10.jpg', '', 'uploads/pixel/2026-04-19/image_1776619341066766500_10.jpg', 'jpg', 1776619341, '::1', 'verification', '0'),
(24, 10, 'uploads/files/2026-04-19/image_1776619347080038200_10.jpg', '', 'uploads/pixel/2026-04-19/image_1776619347080038200_10.jpg', 'jpg', 1776619347, '::1', 'verification', '0'),
(25, 10, 'uploads/files/2026-04-19/image_1776620005089105500_10.jpg', '', 'uploads/pixel/2026-04-19/image_1776620005089105500_10.jpg', 'jpg', 1776620005, '::1', 'verification', '0'),
(26, 10, 'uploads/files/2026-04-19/image_1776620010005459400_10.jpg', '', 'uploads/pixel/2026-04-19/image_1776620010005459400_10.jpg', 'jpg', 1776620010, '::1', 'verification', '0'),
(27, 10, 'uploads/files/2026-04-19/image_1776621361077844100_10.png', 'uploads/files/2026-04-19/image_1776621361077844100_10__10.png', 'uploads/pixel/2026-04-19/image_1776621361077844100_10.png', 'png', 1776621362, '::1', 'wall', '1'),
(28, 8, 'uploads/files/2026-04-19/image_1776621413064312000_8.jpg', '', 'uploads/pixel/2026-04-19/image_1776621413064312000_8.jpg', 'jpg', 1776621413, '::1', 'verification', '0'),
(29, 8, 'uploads/files/2026-04-19/image_1776621418009317800_8.png', '', 'uploads/pixel/2026-04-19/image_1776621418009317800_8.png', 'png', 1776621418, '::1', 'verification', '0'),
(30, 2, 'uploads/files/2026-04-19/image_1776638991073138200_2.png', '', 'uploads/pixel/2026-04-19/image_1776638991073138200_2.png', 'png', 1776638991, '::1', 'verification', '0'),
(31, 2, 'uploads/files/2026-04-19/image_1776639001092760700_2.png', '', 'uploads/pixel/2026-04-19/image_1776639001092760700_2.png', 'png', 1776639001, '::1', 'verification', '0'),
(32, 2, 'uploads/files/2026-04-19/image_1776639006076509100_2.png', '', 'uploads/pixel/2026-04-19/image_1776639006076509100_2.png', 'png', 1776639006, '::1', 'verification', '0'),
(33, 2, 'uploads/files/2026-04-19/image_1776639114006195400_2.mp4', 'uploads/files/2026-04-19/image_1776639114006195400_2.jpg', 'uploads/xvideos/2026-04-19/image_1776639114006195400_2.mp4', 'mp4', 1776639114, '::1', 'wall', '1'),
(34, 2, 'uploads/files/2026-04-19/image_1776643019042196100_2.mp4', 'uploads/files/2026-04-19/image_1776643019042196100_2.jpg', 'uploads/xvideos/2026-04-19/image_1776643019042196100_2.mp4', 'mp4', 1776643019, '::1', 'wall', '1'),
(35, 3, 'uploads/files/2026-04-20/image_1776713481056632500_3.jpg', '', 'uploads/pixel/2026-04-20/image_1776713481056632500_3.jpg', 'jpg', 1776713481, '::1', 'verification', '0'),
(36, 3, 'uploads/files/2026-04-20/image_1776713484035701700_3.jpg', '', 'uploads/pixel/2026-04-20/image_1776713484035701700_3.jpg', 'jpg', 1776713484, '::1', 'verification', '0'),
(37, 3, 'uploads/files/2026-04-20/image_1776727629033612300_3.jpg', 'uploads/files/2026-04-20/image_1776727629033612300_3__3.jpg', 'uploads/pixel/2026-04-20/image_1776727629033612300_3.jpg', 'jpg', 1776727629, '::1', 'wall', '1'),
(38, 1, 'uploads/files/2026-04-24/image_1776993790095616600_1.png', 'uploads/files/2026-04-24/image_1776993790095616600_1__1.png', 'uploads/pixel/2026-04-24/image_1776993790095616600_1.png', 'png', 1776993791, '::1', 'wall', '1'),
(39, 1, 'uploads/files/2026-04-24/image_1776993791023014500_1.png', 'uploads/files/2026-04-24/image_1776993791023014500_1__1.png', 'uploads/pixel/2026-04-24/image_1776993791023014500_1.png', 'png', 1776993791, '::1', 'wall', '1'),
(40, 1, 'uploads/files/2026-04-24/image_1776993791053464500_1.jpg', 'uploads/files/2026-04-24/image_1776993791053464500_1__1.jpg', 'uploads/pixel/2026-04-24/image_1776993791053464500_1.jpg', 'jpg', 1776993791, '::1', 'wall', '1'),
(41, 1, 'uploads/files/2026-04-24/image_1776993791070148400_1.png', 'uploads/files/2026-04-24/image_1776993791070148400_1__1.png', 'uploads/pixel/2026-04-24/image_1776993791070148400_1.png', 'png', 1776993792, '::1', 'wall', '1'),
(42, 15, 'uploads/files/2026-04-26/image_1777165891065632300_15.jpg', '', 'uploads/pixel/2026-04-26/image_1777165891065632300_15.jpg', 'jpg', 1777165891, '::1', 'verification', '0'),
(43, 15, 'uploads/files/2026-04-26/image_1777165901096827900_15.png', '', 'uploads/pixel/2026-04-26/image_1777165901096827900_15.png', 'png', 1777165902, '::1', 'verification', '0'),
(44, 16, 'uploads/files/2026-04-26/image_1777167344036458200_16.png', '', 'uploads/pixel/2026-04-26/image_1777167344036458200_16.png', 'png', 1777167344, '::1', 'verification', '0'),
(45, 16, 'uploads/files/2026-04-26/image_1777167348092152600_16.jpg', '', 'uploads/pixel/2026-04-26/image_1777167348092152600_16.jpg', 'jpg', 1777167348, '::1', 'verification', '0'),
(46, 19, 'uploads/files/2026-04-26/image_1777167717001916000_19.png', '', 'uploads/pixel/2026-04-26/image_1777167717001916000_19.png', 'png', 1777167717, '::1', 'verification', '0'),
(47, 19, 'uploads/files/2026-04-26/image_1777167726035409500_19.jpg', '', 'uploads/pixel/2026-04-26/image_1777167726035409500_19.jpg', 'jpg', 1777167726, '::1', 'verification', '0'),
(48, 19, 'uploads/files/2026-04-26/image_1777167735011480300_19.png', '', 'uploads/pixel/2026-04-26/image_1777167735011480300_19.png', 'png', 1777167735, '::1', 'verification', '0'),
(49, 21, 'uploads/files/2026-04-26/image_1777168329029939800_21.jpg', '', 'uploads/pixel/2026-04-26/image_1777168329029939800_21.jpg', 'jpg', 1777168329, '::1', 'verification', '0'),
(50, 21, 'uploads/files/2026-04-26/image_1777168334091365200_21.png', '', 'uploads/pixel/2026-04-26/image_1777168334091365200_21.png', 'png', 1777168334, '::1', 'verification', '0'),
(51, 19, 'uploads/files/2026-05-01/image_1777660785057783200_19.jpg', 'uploads/files/2026-05-01/image_1777660785057783200_19__19.jpg', 'uploads/pixel/2026-05-01/image_1777660785057783200_19.jpg', 'jpg', 1777660785, '::1', 'wall', '1'),
(52, 23, 'uploads/reels/2026-05-01/e851b33b844f646b43959e73f026be2d_reels_blur.mp4', 'uploads/reels/2026-05-01/e851b33b844f646b43959e73f026be2d_reels_blur.jpg', 'uploads/reels/2026-05-01/e851b33b844f646b43959e73f026be2d_reels_blur.jpg', 'mp4', 1777661251, '::1', 'reels', '1'),
(55, 23, 'uploads/reels/2026-05-01/ab682583b82bd875988dc5849cb38811_reels_blur.mp4', 'uploads/reels/2026-05-01/ab682583b82bd875988dc5849cb38811_reels_blur.jpg', 'uploads/reels/2026-05-01/ab682583b82bd875988dc5849cb38811_reels_blur.jpg', 'mp4', 1777661634, '::1', 'reels', '1'),
(56, 23, 'uploads/reels/2026-05-01/fb35d1acef20560a39b4fa9ed3a0a817_reels_blur.mp4', 'uploads/reels/2026-05-01/fb35d1acef20560a39b4fa9ed3a0a817_reels_blur.jpg', 'uploads/reels/2026-05-01/fb35d1acef20560a39b4fa9ed3a0a817_reels_blur.jpg', 'mp4', 1777663098, '::1', 'reels', '1'),
(57, 21, 'uploads/reels/2026-05-01/cd7d13a7bdae5bb1ba0cda17e4015e3a_reels_blur.mp4', 'uploads/reels/2026-05-01/cd7d13a7bdae5bb1ba0cda17e4015e3a_reels_blur.jpg', 'uploads/reels/2026-05-01/cd7d13a7bdae5bb1ba0cda17e4015e3a_reels_blur.jpg', 'mp4', 1777663675, '::1', 'reels', '1'),
(58, 1, 'uploads/reels/2026-05-01/5f2b48cc1505d6f7a59e06631c8dec4c_reels_blur.mp4', 'uploads/reels/2026-05-01/5f2b48cc1505d6f7a59e06631c8dec4c_reels_blur.jpg', 'uploads/reels/2026-05-01/5f2b48cc1505d6f7a59e06631c8dec4c_reels_blur.jpg', 'mp4', 1777667299, '::1', 'reels', '0'),
(59, 1, 'uploads/reels/2026-05-01/c3517068cd4bd1ace88a5a6f148ec8b5_reels_blur.mp4', 'uploads/reels/2026-05-01/c3517068cd4bd1ace88a5a6f148ec8b5_reels_blur.jpg', 'uploads/reels/2026-05-01/c3517068cd4bd1ace88a5a6f148ec8b5_reels_blur.jpg', 'mp4', 1777668535, '::1', 'reels', '0'),
(60, 1, 'uploads/reels/2026-05-02/5e06f22dbaf3e8a9c31c1bb8a4cb5fbc_reels_blur.mp4', 'uploads/reels/2026-05-02/5e06f22dbaf3e8a9c31c1bb8a4cb5fbc_reels_blur.jpg', 'uploads/reels/2026-05-02/5e06f22dbaf3e8a9c31c1bb8a4cb5fbc_reels_blur.jpg', 'mp4', 1777669376, '::1', 'reels', '0'),
(61, 23, 'uploads/reels/2026-05-01/b8c0b1fcf146ab9d4f0af38daa0e91e6_reels_blur.mp4', 'uploads/reels/2026-05-01/b8c0b1fcf146ab9d4f0af38daa0e91e6_reels_blur.jpg', 'uploads/reels/2026-05-01/b8c0b1fcf146ab9d4f0af38daa0e91e6_reels_blur.jpg', 'mp4', 1777669497, '::1', 'reels', '1'),
(62, 1, 'uploads/reels/2026-05-02/3d145fe698e593738fa1c7aee34a61b2_reels_blur.mp4', 'uploads/reels/2026-05-02/3d145fe698e593738fa1c7aee34a61b2_reels_blur.jpg', 'uploads/reels/2026-05-02/3d145fe698e593738fa1c7aee34a61b2_reels_blur.jpg', 'mp4', 1777670350, '::1', 'reels', '1'),
(63, 23, 'uploads/reels/2026-05-01/928620b6d41488cbcd77e6fc1e8163fe_reels_blur.mp4', 'uploads/reels/2026-05-01/928620b6d41488cbcd77e6fc1e8163fe_reels_blur.jpg', 'uploads/reels/2026-05-01/928620b6d41488cbcd77e6fc1e8163fe_reels_blur.jpg', 'mp4', 1777670694, '::1', 'reels', '1'),
(64, 23, 'uploads/reels/2026-05-01/372cd65e7b2cfd7845d959997f533864_reels_blur.mp4', 'uploads/reels/2026-05-01/372cd65e7b2cfd7845d959997f533864_reels_blur.jpg', 'uploads/reels/2026-05-01/372cd65e7b2cfd7845d959997f533864_reels_blur.jpg', 'mp4', 1777671008, '::1', 'reels', '1'),
(65, 1, 'uploads/reels/2026-05-02/1e259855a38cd9227ecf401dd55787b4_reels_blur.mp4', 'uploads/reels/2026-05-02/1e259855a38cd9227ecf401dd55787b4_reels_blur.jpg', 'uploads/reels/2026-05-02/1e259855a38cd9227ecf401dd55787b4_reels_blur.jpg', 'mp4', 1777672438, '::1', 'reels', '1'),
(66, 1, 'uploads/reels/2026-05-02/8ad88a31f39c2ce5707782e95ba735cc_reels_blur.mp4', 'uploads/reels/2026-05-02/8ad88a31f39c2ce5707782e95ba735cc_reels_blur.jpg', 'uploads/reels/2026-05-02/8ad88a31f39c2ce5707782e95ba735cc_reels_blur.jpg', 'mp4', 1777672512, '::1', 'reels', '0'),
(67, 1, 'uploads/reels/2026-05-02/bdccdfaf5283e601761cc8194c8c1bce_reels_blur.mp4', 'uploads/reels/2026-05-02/bdccdfaf5283e601761cc8194c8c1bce_reels_blur.jpg', 'uploads/reels/2026-05-02/bdccdfaf5283e601761cc8194c8c1bce_reels_blur.jpg', 'mp4', 1777673253, '::1', 'reels', '0'),
(68, 1, 'uploads/reels/2026-05-02/6a08cd16e55ffd1cefb5e988d112dee0_reels_blur.mp4', 'uploads/reels/2026-05-02/6a08cd16e55ffd1cefb5e988d112dee0_reels_blur.jpg', 'uploads/reels/2026-05-02/6a08cd16e55ffd1cefb5e988d112dee0_reels_blur.jpg', 'mp4', 1777673977, '::1', 'reels', '0'),
(69, 1, 'uploads/reels/2026-05-02/e620795757cf395a6f0f2cd6cb63da6a_reels_blur.mp4', 'uploads/reels/2026-05-02/e620795757cf395a6f0f2cd6cb63da6a_reels_blur.jpg', 'uploads/reels/2026-05-02/e620795757cf395a6f0f2cd6cb63da6a_reels_blur.jpg', 'mp4', 1777674008, '::1', 'reels', '1'),
(70, 1, 'uploads/reels/2026-05-02/dbb67c395046c2f77cc5f31dc759263c_reels_blur.mp4', 'uploads/reels/2026-05-02/dbb67c395046c2f77cc5f31dc759263c_reels_blur.jpg', 'uploads/reels/2026-05-02/dbb67c395046c2f77cc5f31dc759263c_reels_blur.jpg', 'mp4', 1777681704, '::1', 'reels', '1');

-- --------------------------------------------------------

--
-- Table structure for table `i_user_web_push_subscriptions`
--

CREATE TABLE `i_user_web_push_subscriptions` (
  `subscription_id` int NOT NULL,
  `iuid_fk` int NOT NULL,
  `endpoint` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `endpoint_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `p256dh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `auth` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content_encoding` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aes128gcm',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `device_label` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive','invalid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `failure_count` int NOT NULL DEFAULT '0',
  `last_error` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` int NOT NULL DEFAULT '0',
  `updated_at` int NOT NULL DEFAULT '0',
  `last_seen_at` int NOT NULL DEFAULT '0',
  `last_success_at` int DEFAULT NULL,
  `last_failure_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_verification_requests`
--

CREATE TABLE `i_verification_requests` (
  `request_id` int NOT NULL,
  `iuid_fk` int DEFAULT NULL,
  `id_card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo_of_card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `request_status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `request_time` int NOT NULL DEFAULT '1609891200',
  `user_read_status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `request_not` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_verification_requests`
--

INSERT INTO `i_verification_requests` (`request_id`, `iuid_fk`, `id_card`, `photo_of_card`, `request_status`, `request_time`, `user_read_status`, `request_not`) VALUES
(1, 10, '24', '23', '2', 1776619388, '1', 'wW'),
(2, 10, '26', '25', '1', 1776620012, '1', ''),
(3, 8, '29', '28', '1', 1776621420, '1', ''),
(4, 2, '32', '31', '1', 1776639009, '1', ''),
(5, 3, '36', '35', '0', 1776713485, '1', NULL),
(6, 15, '43', '42', '0', 1777165905, '1', NULL),
(7, 16, '45', '44', '0', 1777167351, '1', NULL),
(8, 19, '48', '47', '1', 1777167737, '1', ''),
(9, 21, '50', '49', '1', 1777168337, '1', '');

-- --------------------------------------------------------

--
-- Table structure for table `i_video_call`
--

CREATE TABLE `i_video_call` (
  `vc_id` int NOT NULL,
  `voice_call_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `chat_id_fk` int NOT NULL,
  `called_uid_fk` int NOT NULL,
  `caller_uid_fk` int NOT NULL,
  `accept_status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `called_time` int NOT NULL,
  `video_muted` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i_website_social_networks`
--

CREATE TABLE `i_website_social_networks` (
  `id` int NOT NULL,
  `social_icon` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `skey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `place_holder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `i_website_social_networks`
--

INSERT INTO `i_website_social_networks` (`id`, `social_icon`, `skey`, `place_holder`, `status`) VALUES
(1, '<svg id=\"Capa_34\" data-name=\"Capa 34\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 490.66 403.26\"><defs><style>.cls-34{fill:#03a9f4;}</style></defs><path class=\"cls-34\" d=\"M487.84,92.93A10.66,10.66,0,0,0,475,90.67a91.62,91.62,0,0,1-13.63,4.93,95.94,95.94,0,0,0,16.21-31.21A10.66,10.66,0,0,0,461.2,52.91,224.34,224.34,0,0,1,409.87,73,107.35,107.35,0,0,0,275.36,62.6a105.84,105.84,0,0,0-46.08,97.81A252.59,252.59,0,0,1,52.38,65.13a11.07,11.07,0,0,0-9.06-4,10.72,10.72,0,0,0-8.54,5.21A103.24,103.24,0,0,0,23,146.5a114.07,114.07,0,0,0,18.54,38.76,56.7,56.7,0,0,1-10.84-6.93,10.67,10.67,0,0,0-17.39,8.28,110.33,110.33,0,0,0,56.84,94.72,78.57,78.57,0,0,1-13.74-2.95A10.66,10.66,0,0,0,43.62,293a116.4,116.4,0,0,0,80.81,66.14A188,188,0,0,1,11.89,382.28,10.51,10.51,0,0,0,.52,389.57a10.69,10.69,0,0,0,4.91,12.59A319.69,319.69,0,0,0,163.08,447,270.8,270.8,0,0,0,312.72,401.2c84.54-56.11,137.23-156.84,129.89-247A199.1,199.1,0,0,0,489,105.88,10.67,10.67,0,0,0,487.84,92.93Z\" transform=\"translate(0 -43.7)\"/></svg>', 'twitter', 'https://www.imyourfun.com', 'yes'),
(2, '<svg id=\"Layer_88\" data-name=\"Layer 88\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 513.48 513.58\"><defs><style>.cls-1-88{fill:url(#linear-gradient);}.cls-2-88{fill:#fff;}</style><linearGradient id=\"linear-gradient\" x1=\"-30.96\" y1=\"578.55\" x2=\"-20.41\" y2=\"589.89\" gradientTransform=\"translate(-22726.5 -829.95) rotate(-90) scale(42.28 39.34)\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#fd5\"/><stop offset=\"0.5\" stop-color=\"#ff543e\"/><stop offset=\"1\" stop-color=\"#c837ab\"/></linearGradient></defs><path class=\"cls-1-88\" d=\"M32,34.84C-8.23,76.63,0,121,0,255.89c0,112-19.54,224.28,82.73,250.71,31.94,8.22,314.9,8.22,346.8,0,42.58-11,77.22-45.53,82-105.75.66-8.4.66-281.28,0-289.86-5-64.14-44.53-101.12-96.56-108.6C403,.62,400.6.11,339.41,0,122.39.11,74.82-9.56,32,34.84Z\" transform=\"translate(1.49 0.82)\"/><path class=\"cls-2-88\" d=\"M256,67c-77.46,0-151-6.9-179.12,65.21C65.24,162,66.92,200.64,66.92,256c0,48.6-1.55,94.27,9.92,123.82,28,72.15,102.19,65.24,179.07,65.24,74.18,0,150.66,7.72,179.1-65.24,11.62-30.08,9.92-68.18,9.92-123.82,0-73.85,4.07-121.53-31.75-157.33C376.92,62.42,327.87,67,255.87,67ZM239,101c161.58-.25,182.14-18.21,170.79,231.32-4,88.26-71.23,78.57-153.83,78.57-150.61,0-154.95-4.31-154.95-155C101,103.51,113,101.12,239,101Zm117.84,31.39a22.68,22.68,0,1,0,22.68,22.67A22.67,22.67,0,0,0,356.86,132.42ZM256,158.93A97.08,97.08,0,1,0,353,256a97.09,97.09,0,0,0-97-97.09ZM256,193c83.3,0,83.41,126,0,126S172.54,193,256,193Z\" transform=\"translate(1.49 0.82)\"/></svg>', 'instagram', 'https://www.imyourfun.com', 'yes'),
(4, '<svg id=\"Layer_1k\" data-name=\"Layer 1k\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 461 326.22\"><defs><style>.cls-1k{fill:#f61c0d;}</style></defs><path class=\"cls-1k\" d=\"M365.26,67.39H95.74A95.75,95.75,0,0,0,0,163.14V297.86a95.75,95.75,0,0,0,95.74,95.75H365.26A95.74,95.74,0,0,0,461,297.86V163.14A95.74,95.74,0,0,0,365.26,67.39ZM300.51,237.06,174.45,297.18a5.06,5.06,0,0,1-7.24-4.57v-124a5.06,5.06,0,0,1,7.35-4.52L300.62,228A5.07,5.07,0,0,1,300.51,237.06Z\" transform=\"translate(0 -67.39)\"/></svg>', 'youtube', 'https://www.imyourfun.com', 'yes'),
(5, '<svg id=\"Capa_1pi\" data-name=\"Capa 1pi\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 112.2 112.2\"><defs><style>.cls-1pi{fill:#cb2027;}.cls-2pi{fill:#f1f2f2;}</style></defs><circle class=\"cls-1pi\" cx=\"56.1\" cy=\"56.1\" r=\"56.1\"/><path class=\"cls-2pi\" d=\"M60.63,75.12c-4.24-.33-6-2.43-9.35-4.45-1.83,9.59-4.06,18.79-10.68,23.59-2.05-14.5,3-25.38,5.34-36.94-4-6.72.48-20.25,8.9-16.91,10.36,4.09-9,25,4,27.59C72.4,70.73,77.93,44.49,69.53,36,57.38,23.63,34.18,35.67,37,53.31c.69,4.31,5.15,5.62,1.78,11.57C31,63.16,28.73,57,29,48.86c.49-13.37,12-22.74,23.59-24C67.25,23.19,81,30.2,82.88,44,85,59.5,76.28,76.33,60.63,75.12Z\" transform=\"translate(0 0)\"/></svg>', 'pinterest', 'https://www.imyourfun.com', 'yes'),
(6, '<svg id=\"Layer_1g\" data-name=\"Layer 1g\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 496 485.67\"><defs><style>.cls-1g{fill:#3e77bf;}.cls-2g{fill:#114c82;}.cls-3g{fill:#6ca9ea;}.cls-4g{fill:#083e66;}</style></defs><path id=\"SVGCleanerId_0\" data-name=\"SVGCleanerId 0\" class=\"cls-1g\" d=\"M248,5.16c-136.94,0-248,111.47-248,249A248.92,248.92,0,0,0,169.63,490.4c12.39,2.3,16.91-5.39,16.91-12,0-5.9-.21-21.56-.33-42.34-69,15.06-83.55-33.38-83.55-33.38-11.27-28.76-27.52-36.4-27.52-36.4-22.53-15.46,1.69-15.16,1.69-15.16,24.88,1.75,38,25.67,38,25.67,22.14,38,58.06,27,72.19,20.67,2.23-16.08,8.66-27.06,15.74-33.28-55.06-6.3-112.95-27.64-112.95-123,0-27.18,9.66-49.41,25.52-66.83-2.57-6.3-11.07-31.6,2.42-65.87,0,0,20.83-6.69,68.2,25.52a233.71,233.71,0,0,1,124.19,0c47.33-32.21,68.13-25.53,68.13-25.53,13.52,34.28,5,59.58,2.45,65.88,15.91,17.42,25.51,39.65,25.51,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,6.67,4.47,14.43,17.05,12A249,249,0,0,0,496,254.16C496,116.63,385,5.16,248,5.16Z\" transform=\"translate(0 -5.16)\"/><path id=\"SVGCleanerId_0-2\" data-name=\"SVGCleanerId 0\" class=\"cls-1g\" d=\"M248,5.16c-136.94,0-248,111.47-248,249A248.92,248.92,0,0,0,169.63,490.4c12.39,2.3,16.91-5.39,16.91-12,0-5.9-.21-21.56-.33-42.34-69,15.06-83.55-33.38-83.55-33.38-11.27-28.76-27.52-36.4-27.52-36.4-22.53-15.46,1.69-15.16,1.69-15.16,24.88,1.75,38,25.67,38,25.67,22.14,38,58.06,27,72.19,20.67,2.23-16.08,8.66-27.06,15.74-33.28-55.06-6.3-112.95-27.64-112.95-123,0-27.18,9.66-49.41,25.52-66.83-2.57-6.3-11.07-31.6,2.42-65.87,0,0,20.83-6.69,68.2,25.52a233.71,233.71,0,0,1,124.19,0c47.33-32.21,68.13-25.53,68.13-25.53,13.52,34.28,5,59.58,2.45,65.88,15.91,17.42,25.51,39.65,25.51,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,6.67,4.47,14.43,17.05,12A249,249,0,0,0,496,254.16C496,116.63,385,5.16,248,5.16Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-2g\" d=\"M378.5,109.13c13.12,33.91,4.75,58.93,2.2,65.18,15.9,17.42,25.5,39.65,25.5,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,6.67,4.48,14.43,17.05,12A249.6,249.6,0,0,0,481.43,170C447.24,134,409.18,110,378.5,109.13Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-2g\" d=\"M193,377.32a42.66,42.66,0,0,1,9.76-13.14c-30.18-3.46-61.17-11.51-83.05-33.75C136.52,358.45,163.93,371.2,193,377.32Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-3g\" d=\"M12.91,271c0-137.54,111.05-249,248-249a247,247,0,0,1,189.54,88.48C405.52,46.76,331.62,5.16,248,5.16c-136.94,0-248,111.47-248,249A248.36,248.36,0,0,0,58.75,415,248.26,248.26,0,0,1,12.91,271Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-4g\" d=\"M322.71,426.9c0-23.23-7.93-38.44-16.83-46.13,55.25-6.16,113.24-27.22,113.24-122.85,0-27.18-9.6-49.4-25.52-66.82,2.58-6.3,11.1-31.61-2.46-65.87a24.58,24.58,0,0,0-7.89-.49c5.81,25.84-.42,44.32-2.55,49.57,15.9,17.42,25.5,39.65,25.5,66.83,0,95.64-58,116.68-113.23,122.85,8.9,7.69,16.83,22.88,16.83,46.12,0,33.29-.3,60.12-.3,68.29,0,5.87,3.5,12.57,12.92,12.41C322.49,479.55,322.71,455.66,322.71,426.9Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-2g\" d=\"M117.46,424c5.21,10.72,22.72,36.4,68.89,31-.06-5.65-.11-12-.14-18.93C150.58,443.83,129.55,434.67,117.46,424Z\" transform=\"translate(0 -5.16)\"/><path class=\"cls-3g\" d=\"M126.37,391.39a61.07,61.07,0,0,0-8.91-10.55A60,60,0,0,0,126.37,391.39Z\" transform=\"translate(0 -5.16)\"/></svg>', 'github', 'https://www.imyourfun.com', 'yes'),
(7, '<svg id=\"Layer_1sn\" data-name=\"Layer 1sn\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><defs><style>.cls-1sn{fill:#fffc00;}.cls-2sn{fill:#fff;stroke:#000;stroke-width:7px;}</style></defs><rect class=\"cls-1sn\" width=\"512\" height=\"512\" rx=\"76.8\"/><path class=\"cls-2sn\" d=\"M203,414c-18-13-32-20-66-14-5,1-14,4-15-5-2-7-2-18-7-19-31-4-42-10-47-16-2-2-3-7,1-8,53-10,78-61,83-71,6-15-4-24-23-30-9-3-24-7-24-17,0-5,5-8,10-10,4-1,8-2,12,0,12,5,23,7,29,2,0-27-7-61,4-89,13-31,45-59,96-59s83,28,96,59c11,28,4,62,4,89,6,5,17,3,29-2,4-2,8-1,12,0,5,2,10,5,10,10,0,10-15,14-24,17-19,6-29,15-23,30,5,10,30,61,83,71,4,1,3,6,1,8-5,6-16,12-47,16-5,1-5,12-7,19-1,9-10,6-15,5-34-6-48,1-66,14a82,82,0,0,1-53,20C235,435,218,426,203,414Z\"/></svg>', 'snapchat', 'https://www.imyourfun.com', 'yes'),
(8, '<svg id=\"Layer_1a\" data-name=\"Layer 1a\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 82.59 92.8\"><defs><style>.cls-1a{fill:#69c9d0;}</style></defs><g id=\"Layer_2a\" data-name=\"Layer 2a\"><path class=\"cls-1a\" d=\"M74.09,21.25A25.47,25.47,0,0,1,68,4L52.58,3.6V66.89C52.8,86.43,21.93,86,24.37,64.36A14.38,14.38,0,0,1,43.29,53.48V37.77C25.44,34.66,8.53,48.86,8.71,67c1.56,39.25,57.85,39.26,59.41,0-.39-1.4-.17-27.56-.22-29.77a41.58,41.58,0,0,0,23.4,6.26V27.22C83.67,27.22,77.85,25.2,74.09,21.25Z\" transform=\"translate(-8.7 -3.6)\"/></g></svg>', 'tiktok', 'https://www.imyourfun.com', 'yes'),
(9, '<svg id=\"Layer_11\" data-name=\"Layer 11\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 256 256\"><defs><style>.cls-11{fill:#40b3e0;}.cls-21{fill:#fff;}.cls-31{fill:#d2e5f1;}.cls-41{fill:#b5cfe4;}</style></defs><path class=\"cls-11\" d=\"M128,0A128,128,0,0,0,0,128H0A128,128,0,0,0,128,256h0A128,128,0,0,0,256,128h0A128,128,0,0,0,128,0Z\"/><path class=\"cls-21\" d=\"M190.28,73.63,167.42,188.9s-3.2,8-12,4.15l-52.76-40.44-19.18-9.28-32.3-10.87s-4.95-1.76-5.43-5.59S51.35,121,51.35,121L179.73,70.59s10.55-4.63,10.55,3\"/><path class=\"cls-31\" d=\"M98.62,187.6s-1.54-.14-3.46-6.22-11.67-38-11.67-38L161,94.09s4.47-2.71,4.31,0c0,0,.8.48-1.6,2.72s-60.91,54.84-60.91,54.84\"/><path class=\"cls-41\" d=\"M122.9,168.12l-20.87,19a3.64,3.64,0,0,1-3.41.46l4-35.34\"/></svg>', 'telegram', 'https://www.imyourfun.com', 'yes'),
(10, '<svg id=\"Layer_12\" data-name=\"Layer 12\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1024 1024\"><defs><style>.cls-12{fill:#9146ff;}.cls-22{fill:#fff;}</style></defs><circle class=\"cls-12\" cx=\"512\" cy=\"512\" r=\"512\"/><path class=\"cls-22\" d=\"M692.9,535,617,607H541l-66.5,63V607H389V337.2H692.9ZM370,301.2l-95,89.9V714.9H389v89.9l95-89.9h76L730.9,553V301.2ZM636,403H598V510.9h38Zm-142.5-.5h38V510.4h-38Z\"/></svg>', 'twitch', 'https://www.imyourfun.com', 'yes'),
(11, '<svg id=\"Layer_13\" data-name=\"Layer 13\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1024 1024\"><defs><style>.cls-13{fill:#5865f2;}.cls-23{fill:#fff;}</style></defs><circle class=\"cls-13\" cx=\"512\" cy=\"512\" r=\"512\"/><path class=\"cls-23\" d=\"M689.43,349a422.12,422.12,0,0,0-104.22-32.32,1.58,1.58,0,0,0-1.68.79,292.91,292.91,0,0,0-13,26.66,389.89,389.89,0,0,0-117,0,270.38,270.38,0,0,0-13.18-26.66,1.63,1.63,0,0,0-1.68-.79A420.85,420.85,0,0,0,334.44,349a1.47,1.47,0,0,0-.69.59C267.38,448.76,249.2,545.49,258.12,641a1.75,1.75,0,0,0,.67,1.2,424.67,424.67,0,0,0,127.85,64.63,1.67,1.67,0,0,0,1.8-.59,303.6,303.6,0,0,0,26.15-42.54,1.63,1.63,0,0,0-.72-2.18l-.17-.07a279.7,279.7,0,0,1-39.94-19,1.64,1.64,0,0,1-.58-2.25,1.71,1.71,0,0,1,.42-.47c2.68-2,5.37-4.1,7.93-6.22a1.57,1.57,0,0,1,1.65-.22c83.79,38.26,174.51,38.26,257.31,0a1.58,1.58,0,0,1,1.68.2c2.56,2.11,5.25,4.23,8,6.24a1.64,1.64,0,0,1,.33,2.3,1.71,1.71,0,0,1-.47.42,261.88,261.88,0,0,1-40,19,1.63,1.63,0,0,0-.95,2.1,1.42,1.42,0,0,0,.08.18,341.13,341.13,0,0,0,26.13,42.52,1.62,1.62,0,0,0,1.8.61,423,423,0,0,0,128-64.63,1.65,1.65,0,0,0,.67-1.18c10.68-110.44-17.88-206.38-75.7-291.42a1.29,1.29,0,0,0-.63-.63ZM427.09,582.85c-25.23,0-46-23.16-46-51.6s20.38-51.6,46-51.6c25.83,0,46.42,23.36,46,51.6C473.11,559.69,452.72,582.85,427.09,582.85Zm170.13,0c-25.23,0-46-23.16-46-51.6s20.38-51.6,46-51.6c25.83,0,46.42,23.36,46,51.6C643.23,559.69,623.05,582.85,597.22,582.85Z\"/></svg>', 'discord', 'https://www.imyourfun.com', 'yes'),
(12, '<svg id=\"Layer_14\" data-name=\"Layer 14\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1024 1024\"><defs><style>.cls-14{fill:#2787f5;}.cls-24{fill:#fff;}</style></defs><circle class=\"cls-14\" cx=\"512\" cy=\"512\" r=\"512\"/><path class=\"cls-24\" d=\"M585.83,271.5H438.17c-134.76,0-166.67,31.91-166.67,166.67V585.83c0,134.76,31.91,166.67,166.67,166.67H585.83c134.76,0,166.67-31.91,166.67-166.67V438.17c0-134.76-32.25-166.67-166.67-166.67Zm74,343.18h-35c-13.24,0-17.31-10.52-41.07-34.62-20.71-20-29.87-22.74-35-22.74-7.13,0-9.17,2-9.17,11.88v31.57c0,8.49-2.72,13.58-25.12,13.58-37,0-78.07-22.4-106.93-64.16-43.45-61.1-55.33-106.93-55.33-116.43,0-5.09,2-9.84,11.88-9.84h35c8.83,0,12.22,4.07,15.61,13.58,17.31,49.9,46.17,93.69,58,93.69,4.41,0,6.45-2,6.45-13.24v-51.6c-1.36-23.76-13.92-25.8-13.92-34.28,0-4.07,3.39-8.15,8.83-8.15h55c7.47,0,10.18,4.07,10.18,12.9V506.4c0,7.47,3.39,10.18,5.43,10.18,4.41,0,8.15-2.72,16.29-10.86,25.12-28.17,43.11-71.62,43.11-71.62,2.38-5.09,6.45-9.84,15.28-9.84h35c10.52,0,12.9,5.43,10.52,12.9C660.46,457.53,617.69,518,617.69,518c-3.73,6.11-5.09,8.83,0,15.61,3.73,5.09,16,15.61,24.1,25.12,14.94,17,26.48,31.23,29.53,41.07,3.45,9.84-1.65,14.93-11.49,14.93Z\"/></svg>', 'vk', 'https://www.imyourfun.com', 'yes');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_slug` (`slug`),
  ADD KEY `ix_community_owner` (`owner_user_id`),
  ADD KEY `ix_community_status` (`status`);

--
-- Indexes for table `community_categories`
--
ALTER TABLE `community_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_category_key` (`category_key`),
  ADD KEY `ix_community_category_status` (`status`);

--
-- Indexes for table `community_memberships`
--
ALTER TABLE `community_memberships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_member` (`community_id`,`user_id`),
  ADD KEY `ix_community_member_community` (`community_id`),
  ADD KEY `ix_community_member_user` (`user_id`),
  ADD KEY `ix_community_member_status` (`status`);

--
-- Indexes for table `community_member_controls`
--
ALTER TABLE `community_member_controls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_member_controls` (`community_id`,`user_id`),
  ADD KEY `ix_community_member_controls_user` (`user_id`),
  ADD KEY `ix_community_member_controls_community` (`community_id`);

--
-- Indexes for table `community_member_restrictions`
--
ALTER TABLE `community_member_restrictions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_member_restriction` (`community_id`,`user_id`),
  ADD KEY `ix_community_restriction_status` (`status`),
  ADD KEY `ix_community_restriction_user` (`user_id`);

--
-- Indexes for table `community_moderators`
--
ALTER TABLE `community_moderators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_moderator` (`community_id`,`user_id`),
  ADD KEY `ix_community_moderator_user` (`user_id`),
  ADD KEY `ix_community_moderator_community` (`community_id`);

--
-- Indexes for table `community_moderator_actions`
--
ALTER TABLE `community_moderator_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_community_action_community` (`community_id`),
  ADD KEY `ix_community_action_moderator` (`moderator_id`),
  ADD KEY `ix_community_action_target_user` (`target_user_id`),
  ADD KEY `ix_community_action_target_post` (`target_post_id`),
  ADD KEY `ix_community_action_target_comment` (`target_comment_id`),
  ADD KEY `ix_community_action_type` (`action_type`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_community_post` (`post_id`),
  ADD KEY `ix_community_posts_community` (`community_id`),
  ADD KEY `ix_community_posts_creator` (`created_by`);

--
-- Indexes for table `i_ads_codes`
--
ALTER TABLE `i_ads_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD KEY `idx_placement_default` (`placement_id`,`is_default`),
  ADD KEY `idx_placement_status` (`placement_id`,`status`);

--
-- Indexes for table `i_ads_placements`
--
ALTER TABLE `i_ads_placements`
  ADD PRIMARY KEY (`placement_id`),
  ADD UNIQUE KEY `placement_key` (`placement_key`),
  ADD KEY `idx_status` (`placement_status`);

--
-- Indexes for table `i_advertisements`
--
ALTER TABLE `i_advertisements`
  ADD PRIMARY KEY (`ads_id`);

--
-- Indexes for table `i_agencies`
--
ALTER TABLE `i_agencies`
  ADD PRIMARY KEY (`agency_id`),
  ADD UNIQUE KEY `ux_agency_owner_unique` (`owner_iuid_fk`),
  ADD KEY `ix_agency_owner` (`owner_iuid_fk`),
  ADD KEY `ix_agency_status` (`agency_status`);

--
-- Indexes for table `i_agency_boosts`
--
ALTER TABLE `i_agency_boosts`
  ADD PRIMARY KEY (`ab_id`),
  ADD KEY `ix_agency_boost_agency` (`agency_id_fk`),
  ADD KEY `ix_agency_boost_creator` (`creator_iuid_fk`),
  ADD KEY `ix_agency_boost_end_at` (`end_at`),
  ADD KEY `ix_agency_boost_status` (`status`);

--
-- Indexes for table `i_agency_boost_clicks`
--
ALTER TABLE `i_agency_boost_clicks`
  ADD PRIMARY KEY (`abc_id`),
  ADD UNIQUE KEY `ux_agency_boost_user` (`ab_id_fk`,`iuid_fk`),
  ADD UNIQUE KEY `ux_agency_boost_ip` (`ab_id_fk`,`ip`),
  ADD KEY `ix_agency_boost_click_agency` (`agency_id_fk`),
  ADD KEY `ix_agency_boost_click_creator` (`creator_iuid_fk`),
  ADD KEY `ix_agency_boost_click_boost` (`ab_id_fk`);

--
-- Indexes for table `i_agency_create_requests`
--
ALTER TABLE `i_agency_create_requests`
  ADD PRIMARY KEY (`acr_id`),
  ADD KEY `ix_agency_create_owner` (`owner_iuid_fk`),
  ADD KEY `ix_agency_create_status` (`status`);

--
-- Indexes for table `i_agency_members`
--
ALTER TABLE `i_agency_members`
  ADD PRIMARY KEY (`am_id`),
  ADD UNIQUE KEY `ux_agency_member_creator` (`creator_iuid_fk`),
  ADD KEY `ix_agency_member_agency` (`agency_id_fk`),
  ADD KEY `ix_agency_member_creator` (`creator_iuid_fk`);

--
-- Indexes for table `i_agency_payouts`
--
ALTER TABLE `i_agency_payouts`
  ADD PRIMARY KEY (`ap_id`),
  ADD KEY `ix_agency_payout_agency` (`agency_id_fk`),
  ADD KEY `ix_agency_payout_owner` (`owner_iuid_fk`),
  ADD KEY `ix_agency_payout_status` (`status`);

--
-- Indexes for table `i_agency_requests`
--
ALTER TABLE `i_agency_requests`
  ADD PRIMARY KEY (`ar_id`),
  ADD UNIQUE KEY `ux_agency_request` (`agency_id_fk`,`creator_iuid_fk`,`status`),
  ADD KEY `ix_agency_request_agency` (`agency_id_fk`),
  ADD KEY `ix_agency_request_creator` (`creator_iuid_fk`),
  ADD KEY `ix_agency_request_status` (`status`);

--
-- Indexes for table `i_agency_social_profiles`
--
ALTER TABLE `i_agency_social_profiles`
  ADD PRIMARY KEY (`asp_id`),
  ADD UNIQUE KEY `ux_agency_social` (`agency_id_fk`,`isw_id_fk`),
  ADD KEY `ix_agency_social_agency` (`agency_id_fk`),
  ADD KEY `ix_agency_social_network` (`isw_id_fk`);

--
-- Indexes for table `i_ai_usage`
--
ALTER TABLE `i_ai_usage`
  ADD PRIMARY KEY (`ai_usage_id`),
  ADD KEY `ix_ai_usage_user` (`iuid_fk`),
  ADD KEY `ix_ai_usage_time` (`created_at`),
  ADD KEY `ix_ai_usage_status` (`status`);

--
-- Indexes for table `i_announcement`
--
ALTER TABLE `i_announcement`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `i_announcement_seen`
--
ALTER TABLE `i_announcement_seen`
  ADD PRIMARY KEY (`aid`);

--
-- Indexes for table `i_approve_post_notification`
--
ALTER TABLE `i_approve_post_notification`
  ADD PRIMARY KEY (`approve_id`);

--
-- Indexes for table `i_audit_log`
--
ALTER TABLE `i_audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `ix_audit_actor` (`actor_iuid_fk`),
  ADD KEY `ix_audit_action` (`action`),
  ADD KEY `ix_audit_target` (`target_type`,`target_id`);

--
-- Indexes for table `i_bank_payments`
--
ALTER TABLE `i_bank_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `i_blog_posts`
--
ALTER TABLE `i_blog_posts`
  ADD PRIMARY KEY (`blog_id`),
  ADD UNIQUE KEY `ix_blog_slug` (`slug`(191)),
  ADD KEY `ix_blog_status` (`status`,`published_at`),
  ADD KEY `ix_blog_author` (`author_id`),
  ADD KEY `ix_blog_featured` (`is_featured`,`status`);

--
-- Indexes for table `i_blog_reactions`
--
ALTER TABLE `i_blog_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `ix_blog_reaction_unique` (`blog_id`,`iuid_fk`),
  ADD KEY `ix_blog_reaction_type` (`blog_id`,`reaction`);

--
-- Indexes for table `i_blog_views`
--
ALTER TABLE `i_blog_views`
  ADD PRIMARY KEY (`view_id`),
  ADD UNIQUE KEY `ix_blog_view_user` (`blog_id`,`iuid_fk`),
  ADD UNIQUE KEY `ix_blog_view_ip` (`blog_id`,`ip_hash`),
  ADD KEY `ix_blog_view_blog` (`blog_id`);

--
-- Indexes for table `i_boosted_posts`
--
ALTER TABLE `i_boosted_posts`
  ADD PRIMARY KEY (`boost_id`),
  ADD UNIQUE KEY `ixBoosted` (`boost_id`,`view_count`,`iuid_fk`,`status`) USING BTREE;

--
-- Indexes for table `i_boosted_post_seen_counter`
--
ALTER TABLE `i_boosted_post_seen_counter`
  ADD PRIMARY KEY (`bp_seen_id`);

--
-- Indexes for table `i_boost_post_plans`
--
ALTER TABLE `i_boost_post_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `i_bulk_campaigns`
--
ALTER TABLE `i_bulk_campaigns`
  ADD PRIMARY KEY (`bc_id`),
  ADD KEY `ix_bulk_campaign_creator` (`created_by_iuid_fk`),
  ADD KEY `ix_bulk_campaign_status` (`status`),
  ADD KEY `ix_bulk_campaign_target_creator` (`target_creator_iuid_fk`),
  ADD KEY `ix_bulk_campaign_target_agency` (`target_agency_id_fk`);

--
-- Indexes for table `i_bulk_campaign_queue`
--
ALTER TABLE `i_bulk_campaign_queue`
  ADD PRIMARY KEY (`bq_id`),
  ADD UNIQUE KEY `ux_bulk_queue_recipient` (`bc_id_fk`,`recipient_iuid_fk`),
  ADD KEY `ix_bulk_queue_campaign` (`bc_id_fk`),
  ADD KEY `ix_bulk_queue_recipient` (`recipient_iuid_fk`),
  ADD KEY `ix_bulk_queue_status` (`status`);

--
-- Indexes for table `i_campaigns`
--
ALTER TABLE `i_campaigns`
  ADD PRIMARY KEY (`campaign_id`),
  ADD UNIQUE KEY `ux_campaign_post` (`post_id_fk`),
  ADD KEY `idx_campaign_status` (`status`);

--
-- Indexes for table `i_ccbill`
--
ALTER TABLE `i_ccbill`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `i_chat_conversations`
--
ALTER TABLE `i_chat_conversations`
  ADD PRIMARY KEY (`con_id`),
  ADD KEY `ixChat` (`chat_id_fk`,`user_one`,`user_two`);

--
-- Indexes for table `i_chat_users`
--
ALTER TABLE `i_chat_users`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `ixUserChat` (`user_one`,`user_two`);

--
-- Indexes for table `i_comment_reports`
--
ALTER TABLE `i_comment_reports`
  ADD PRIMARY KEY (`p_report_id`);

--
-- Indexes for table `i_configurations`
--
ALTER TABLE `i_configurations`
  ADD PRIMARY KEY (`configuration_id`);

--
-- Indexes for table `i_configuration_affilate`
--
ALTER TABLE `i_configuration_affilate`
  ADD PRIMARY KEY (`i_af_id`);

--
-- Indexes for table `i_contacts`
--
ALTER TABLE `i_contacts`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `i_creators`
--
ALTER TABLE `i_creators`
  ADD PRIMARY KEY (`cr_id`);

--
-- Indexes for table `i_creator_auto_messages`
--
ALTER TABLE `i_creator_auto_messages`
  ADD PRIMARY KEY (`cam_id`),
  ADD UNIQUE KEY `ux_creator_auto_message` (`creator_iuid_fk`),
  ADD KEY `ix_creator_auto_message_creator` (`creator_iuid_fk`);

--
-- Indexes for table `i_creator_auto_message_log`
--
ALTER TABLE `i_creator_auto_message_log`
  ADD PRIMARY KEY (`cml_id`),
  ADD UNIQUE KEY `ux_creator_auto_message_sent` (`creator_iuid_fk`,`visitor_iuid_fk`),
  ADD KEY `ix_creator_auto_message_creator` (`creator_iuid_fk`),
  ADD KEY `ix_creator_auto_message_visitor` (`visitor_iuid_fk`);

--
-- Indexes for table `i_custom_codes`
--
ALTER TABLE `i_custom_codes`
  ADD PRIMARY KEY (`custom_id`);

--
-- Indexes for table `i_epoch_webhook_events`
--
ALTER TABLE `i_epoch_webhook_events`
  ADD PRIMARY KEY (`event_id`),
  ADD UNIQUE KEY `ux_epoch_event_hash` (`event_hash`),
  ADD KEY `ix_epoch_order_key` (`order_key`),
  ADD KEY `ix_epoch_transaction` (`transaction_id`),
  ADD KEY `ix_epoch_event_type` (`event_type`);

--
-- Indexes for table `i_frames`
--
ALTER TABLE `i_frames`
  ADD PRIMARY KEY (`f_id`);

--
-- Indexes for table `i_friends`
--
ALTER TABLE `i_friends`
  ADD PRIMARY KEY (`fr_id`),
  ADD KEY `ixFriend` (`fr_one`,`fr_two`,`fr_status`);

--
-- Indexes for table `i_landing_qa`
--
ALTER TABLE `i_landing_qa`
  ADD PRIMARY KEY (`qa_id`);

--
-- Indexes for table `i_langs`
--
ALTER TABLE `i_langs`
  ADD PRIMARY KEY (`lang_id`);

--
-- Indexes for table `i_license_state`
--
ALTER TABLE `i_license_state`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_status_idx` (`license_status`);

--
-- Indexes for table `i_live`
--
ALTER TABLE `i_live`
  ADD PRIMARY KEY (`live_id`),
  ADD KEY `ix_Live` (`live_uid_fk`,`live_type`);

--
-- Indexes for table `i_live_chat`
--
ALTER TABLE `i_live_chat`
  ADD PRIMARY KEY (`cm_id`),
  ADD KEY `LiveChatIndex` (`cm_live_id`,`cm_iuid_fk`) USING BTREE;

--
-- Indexes for table `i_live_gift_point`
--
ALTER TABLE `i_live_gift_point`
  ADD PRIMARY KEY (`gift_id`);

--
-- Indexes for table `i_live_likes`
--
ALTER TABLE `i_live_likes`
  ADD PRIMARY KEY (`like_id`);

--
-- Indexes for table `i_live_pinned_products`
--
ALTER TABLE `i_live_pinned_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_live_pinned_product` (`live_id`),
  ADD KEY `ix_live_pinned_product_id` (`product_id`);

--
-- Indexes for table `i_live_polls`
--
ALTER TABLE `i_live_polls`
  ADD PRIMARY KEY (`poll_id`),
  ADD KEY `ix_live_poll_live` (`live_id`,`status`);

--
-- Indexes for table `i_live_poll_options`
--
ALTER TABLE `i_live_poll_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `ix_live_poll_option` (`poll_id_fk`,`status`);

--
-- Indexes for table `i_live_poll_votes`
--
ALTER TABLE `i_live_poll_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `ux_live_poll_vote` (`poll_id_fk`,`voter_uid_fk`),
  ADD KEY `ix_live_poll_vote_option` (`option_id_fk`);

--
-- Indexes for table `i_live_questions`
--
ALTER TABLE `i_live_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `ix_live_question_live` (`live_id`,`status`),
  ADD KEY `ix_live_question_user` (`user_id`);

--
-- Indexes for table `i_live_reminders`
--
ALTER TABLE `i_live_reminders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_live_reminder` (`live_id`,`user_id`),
  ADD KEY `ix_live_reminder_user` (`user_id`,`live_id`);

--
-- Indexes for table `i_live_video_users`
--
ALTER TABLE `i_live_video_users`
  ADD PRIMARY KEY (`live_user_id`);

--
-- Indexes for table `i_mentions`
--
ALTER TABLE `i_mentions`
  ADD PRIMARY KEY (`m_id`);

--
-- Indexes for table `i_message_reports`
--
ALTER TABLE `i_message_reports`
  ADD PRIMARY KEY (`p_report_id`),
  ADD KEY `ix_message_reports_message` (`reported_message`),
  ADD KEY `ix_message_reports_chat` (`reported_chat_id_fk`),
  ADD KEY `ix_message_reports_user` (`iuid_fk`),
  ADD KEY `ix_message_reports_status` (`report_status`);

--
-- Indexes for table `i_moderator_permissions`
--
ALTER TABLE `i_moderator_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_moderator_permission` (`moderator_id`,`permission_key`),
  ADD KEY `ix_moderator_permission_moderator` (`moderator_id`),
  ADD KEY `ix_moderator_permission_key` (`permission_key`);

--
-- Indexes for table `i_obs_overlays`
--
ALTER TABLE `i_obs_overlays`
  ADD PRIMARY KEY (`obs_overlay_id`),
  ADD UNIQUE KEY `ux_obs_overlay_token` (`overlay_token`),
  ADD KEY `ix_obs_overlay_creator` (`creator_id_fk`),
  ADD KEY `ix_obs_overlay_active` (`is_active`);

--
-- Indexes for table `i_obs_overlay_clicks`
--
ALTER TABLE `i_obs_overlay_clicks`
  ADD PRIMARY KEY (`obs_overlay_click_id`),
  ADD KEY `ix_obs_overlay_click_overlay` (`obs_overlay_id_fk`),
  ADD KEY `ix_obs_overlay_click_ip` (`ip_hash`),
  ADD KEY `ix_obs_overlay_click_time` (`created_at`);

--
-- Indexes for table `i_obs_support_events`
--
ALTER TABLE `i_obs_support_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_obs_support_overlay_payment` (`overlay_id`,`payment_id`),
  ADD KEY `ix_obs_support_overlay_id` (`overlay_id`,`id`),
  ADD KEY `ix_obs_support_creator_time` (`creator_id`,`created_at`),
  ADD KEY `ix_obs_support_extension` (`processed_for_extension`,`id`);

--
-- Indexes for table `i_pages`
--
ALTER TABLE `i_pages`
  ADD PRIMARY KEY (`page_id`);

--
-- Indexes for table `i_payment_methods`
--
ALTER TABLE `i_payment_methods`
  ADD PRIMARY KEY (`payment_method_id`);

--
-- Indexes for table `i_polls`
--
ALTER TABLE `i_polls`
  ADD PRIMARY KEY (`poll_id`),
  ADD UNIQUE KEY `ux_post_poll` (`post_id_fk`);

--
-- Indexes for table `i_poll_options`
--
ALTER TABLE `i_poll_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `poll_option_idx` (`poll_id_fk`,`status`);

--
-- Indexes for table `i_poll_votes`
--
ALTER TABLE `i_poll_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `uniq_vote_per_user` (`poll_id_fk`,`voter_uid_fk`),
  ADD KEY `poll_option_idx` (`option_id_fk`);

--
-- Indexes for table `i_posts`
--
ALTER TABLE `i_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `ixForcePostOwner` (`post_owner_id`,`post_id`,`post_status`) USING BTREE;

--
-- Indexes for table `i_post_comments`
--
ALTER TABLE `i_post_comments`
  ADD PRIMARY KEY (`com_id`),
  ADD KEY `ixComment` (`comment_post_id_fk`,`comment_uid_fk`),
  ADD KEY `ixCommentParent` (`comment_post_id_fk`,`parent_comment_id`);

--
-- Indexes for table `i_post_comment_likes`
--
ALTER TABLE `i_post_comment_likes`
  ADD PRIMARY KEY (`c_like_id`);

--
-- Indexes for table `i_post_likes`
--
ALTER TABLE `i_post_likes`
  ADD PRIMARY KEY (`like_id`);

--
-- Indexes for table `i_post_reports`
--
ALTER TABLE `i_post_reports`
  ADD PRIMARY KEY (`p_report_id`);

--
-- Indexes for table `i_premium_plans`
--
ALTER TABLE `i_premium_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `i_product_seen_time`
--
ALTER TABLE `i_product_seen_time`
  ADD PRIMARY KEY (`p_s_id`);

--
-- Indexes for table `i_profile_categories`
--
ALTER TABLE `i_profile_categories`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `i_profile_sub_categories`
--
ALTER TABLE `i_profile_sub_categories`
  ADD PRIMARY KEY (`sc_id`);

--
-- Indexes for table `i_refusers`
--
ALTER TABLE `i_refusers`
  ADD PRIMARY KEY (`i_ref_id`);

--
-- Indexes for table `i_saved_posts`
--
ALTER TABLE `i_saved_posts`
  ADD PRIMARY KEY (`save_id`),
  ADD KEY `ixSaved` (`saved_post_id`,`iuid_fk`);

--
-- Indexes for table `i_scheduled_queue`
--
ALTER TABLE `i_scheduled_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_scheduled_post` (`post_id_fk`),
  ADD KEY `idx_schedule_status_run` (`status`,`run_at`);

--
-- Indexes for table `i_sessions`
--
ALTER TABLE `i_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `uniq_session_key` (`session_key`);

--
-- Indexes for table `i_settings_kv`
--
ALTER TABLE `i_settings_kv`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `i_share_tokens`
--
ALTER TABLE `i_share_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_post_exp` (`post_id`,`expires_at`);

--
-- Indexes for table `i_shop_configuration`
--
ALTER TABLE `i_shop_configuration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `i_social_logins`
--
ALTER TABLE `i_social_logins`
  ADD PRIMARY KEY (`s_id`);

--
-- Indexes for table `i_social_networks`
--
ALTER TABLE `i_social_networks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ixLinks` (`id`) USING BTREE;

--
-- Indexes for table `i_social_user_profiles`
--
ALTER TABLE `i_social_user_profiles`
  ADD PRIMARY KEY (`ius_id`),
  ADD KEY `ixSocialLink` (`ius_id`,`isw_id_fk`,`uid_fk`) USING BTREE;

--
-- Indexes for table `i_stickers`
--
ALTER TABLE `i_stickers`
  ADD PRIMARY KEY (`sticker_id`);

--
-- Indexes for table `i_stories_seen`
--
ALTER TABLE `i_stories_seen`
  ADD PRIMARY KEY (`i_seen_id`);

--
-- Indexes for table `i_story_audio`
--
ALTER TABLE `i_story_audio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_story_audio_status` (`status`);

--
-- Indexes for table `i_story_configuration`
--
ALTER TABLE `i_story_configuration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `i_story_highlights`
--
ALTER TABLE `i_story_highlights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_story_highlights_owner` (`owner_id`),
  ADD KEY `ix_story_highlights_status` (`status`);

--
-- Indexes for table `i_story_highlight_items`
--
ALTER TABLE `i_story_highlight_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_story_highlight_story` (`highlight_id`,`story_id`),
  ADD KEY `ix_story_highlight` (`highlight_id`),
  ADD KEY `ix_story_highlight_story` (`story_id`);

--
-- Indexes for table `i_story_reactions`
--
ALTER TABLE `i_story_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `ux_story_reaction` (`story_id_fk`,`iuid_fk`),
  ADD KEY `ix_story_reaction_story` (`story_id_fk`),
  ADD KEY `ix_story_reaction_user` (`iuid_fk`);

--
-- Indexes for table `i_story_replies`
--
ALTER TABLE `i_story_replies`
  ADD PRIMARY KEY (`sr_id`),
  ADD KEY `ix_story_reply_story` (`story_id_fk`),
  ADD KEY `ix_story_reply_sender` (`sender_iuid_fk`),
  ADD KEY `ix_story_reply_owner` (`owner_iuid_fk`);

--
-- Indexes for table `i_story_text_bg`
--
ALTER TABLE `i_story_text_bg`
  ADD PRIMARY KEY (`st_bg_id`);

--
-- Indexes for table `i_svg_icons`
--
ALTER TABLE `i_svg_icons`
  ADD PRIMARY KEY (`icon_id`);

--
-- Indexes for table `i_users`
--
ALTER TABLE `i_users`
  ADD PRIMARY KEY (`iuid`),
  ADD UNIQUE KEY `uniq_username` (`i_username`),
  ADD UNIQUE KEY `uniq_email` (`i_user_email`),
  ADD KEY `ixForceUser` (`iuid`,`uStatus`,`notification_read_status`),
  ADD KEY `idx_age_verify_status` (`age_verify_status`);

--
-- Indexes for table `i_user_activity`
--
ALTER TABLE `i_user_activity`
  ADD PRIMARY KEY (`activity_id`);

--
-- Indexes for table `i_user_avatars`
--
ALTER TABLE `i_user_avatars`
  ADD PRIMARY KEY (`avatar_id`);

--
-- Indexes for table `i_user_blocked_countries`
--
ALTER TABLE `i_user_blocked_countries`
  ADD PRIMARY KEY (`b_c_id`);

--
-- Indexes for table `i_user_blocks`
--
ALTER TABLE `i_user_blocks`
  ADD PRIMARY KEY (`block_id`),
  ADD KEY `ixBlocked` (`blocker_iuid`,`blocked_iuid`,`block_type`);

--
-- Indexes for table `i_user_conversation_uploads`
--
ALTER TABLE `i_user_conversation_uploads`
  ADD PRIMARY KEY (`upload_id`),
  ADD KEY `iuxConversation` (`iuid_fk`,`con_id_fk`);

--
-- Indexes for table `i_user_covers`
--
ALTER TABLE `i_user_covers`
  ADD PRIMARY KEY (`cover_id`);

--
-- Indexes for table `i_user_deletion_requests`
--
ALTER TABLE `i_user_deletion_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `ix_user_delete_iuid` (`iuid_fk`),
  ADD KEY `ix_user_delete_status` (`request_status`),
  ADD KEY `ix_user_delete_after` (`delete_after`),
  ADD KEY `ix_user_delete_status_after` (`request_status`,`delete_after`),
  ADD KEY `ix_user_delete_token` (`request_token`);

--
-- Indexes for table `i_user_exports`
--
ALTER TABLE `i_user_exports`
  ADD PRIMARY KEY (`export_id`),
  ADD UNIQUE KEY `ux_user_export_token` (`export_token`),
  ADD KEY `ix_user_export_iuid` (`iuid_fk`),
  ADD KEY `ix_user_export_status` (`status`),
  ADD KEY `ix_user_export_expire` (`expires_at`);

--
-- Indexes for table `i_user_frames`
--
ALTER TABLE `i_user_frames`
  ADD PRIMARY KEY (`f_id`);

--
-- Indexes for table `i_user_notifications`
--
ALTER TABLE `i_user_notifications`
  ADD PRIMARY KEY (`not_id`),
  ADD KEY `ixForceID` (`not_status`,`not_post_id`,`not_type`,`not_iuid`,`not_own_iuid`);

--
-- Indexes for table `i_user_payments`
--
ALTER TABLE `i_user_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `ixPayment` (`payer_iuid_fk`,`payed_iuid_fk`,`payed_post_id_fk`,`payed_profile_id_fk`,`payment_type`,`payment_option`,`payment_status`),
  ADD KEY `ix_payment_agency` (`agency_id_fk`);

--
-- Indexes for table `i_user_payouts`
--
ALTER TABLE `i_user_payouts`
  ADD PRIMARY KEY (`payout_id`),
  ADD KEY `ix_PayoutUser` (`iuid_fk`,`method`);

--
-- Indexes for table `i_user_point_earnings`
--
ALTER TABLE `i_user_point_earnings`
  ADD PRIMARY KEY (`point_id`);

--
-- Indexes for table `i_user_product_posts`
--
ALTER TABLE `i_user_product_posts`
  ADD PRIMARY KEY (`pr_id`),
  ADD KEY `ixProduct` (`pr_id`,`iuid_fk`);

--
-- Indexes for table `i_user_stories`
--
ALTER TABLE `i_user_stories`
  ADD PRIMARY KEY (`s_id`),
  ADD KEY `ixUserStories` (`uid_fk`);

--
-- Indexes for table `i_user_subscribe_plans`
--
ALTER TABLE `i_user_subscribe_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `i_user_subscriptions`
--
ALTER TABLE `i_user_subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `ix_Subscribe` (`iuid_fk`,`subscribed_iuid_fk`,`status`),
  ADD KEY `ix_subscription_agency` (`agency_id_fk`),
  ADD KEY `ix_subscription_scope` (`subscription_scope`),
  ADD KEY `ix_subscription_scope_ref` (`subscription_scope`,`subscription_ref_id`);

--
-- Indexes for table `i_user_subscription_intents`
--
ALTER TABLE `i_user_subscription_intents`
  ADD PRIMARY KEY (`intent_id`),
  ADD KEY `ix_sub_intent_user` (`iuid_fk`),
  ADD KEY `ix_sub_intent_target` (`subscribed_iuid_fk`),
  ADD KEY `ix_sub_intent_scope` (`subscription_scope`),
  ADD KEY `ix_sub_intent_scope_ref` (`subscription_scope`,`subscription_ref_id`),
  ADD KEY `ix_sub_intent_order` (`order_key`),
  ADD KEY `ix_sub_intent_yookassa` (`yookassa_payment_id`),
  ADD KEY `ix_sub_intent_status` (`status`);

--
-- Indexes for table `i_user_uploads`
--
ALTER TABLE `i_user_uploads`
  ADD PRIMARY KEY (`upload_id`),
  ADD KEY `iuPostOwner` (`iuid_fk`);

--
-- Indexes for table `i_user_web_push_subscriptions`
--
ALTER TABLE `i_user_web_push_subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD UNIQUE KEY `ux_web_push_endpoint_hash` (`endpoint_hash`),
  ADD KEY `ix_web_push_user_status` (`iuid_fk`,`status`),
  ADD KEY `ix_web_push_updated_at` (`updated_at`);

--
-- Indexes for table `i_verification_requests`
--
ALTER TABLE `i_verification_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `i_video_call`
--
ALTER TABLE `i_video_call`
  ADD PRIMARY KEY (`vc_id`);

--
-- Indexes for table `i_website_social_networks`
--
ALTER TABLE `i_website_social_networks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ixLinks` (`id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_categories`
--
ALTER TABLE `community_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `community_memberships`
--
ALTER TABLE `community_memberships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_member_controls`
--
ALTER TABLE `community_member_controls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_member_restrictions`
--
ALTER TABLE `community_member_restrictions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_moderators`
--
ALTER TABLE `community_moderators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_moderator_actions`
--
ALTER TABLE `community_moderator_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_ads_codes`
--
ALTER TABLE `i_ads_codes`
  MODIFY `code_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_ads_placements`
--
ALTER TABLE `i_ads_placements`
  MODIFY `placement_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_advertisements`
--
ALTER TABLE `i_advertisements`
  MODIFY `ads_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `i_agencies`
--
ALTER TABLE `i_agencies`
  MODIFY `agency_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_boosts`
--
ALTER TABLE `i_agency_boosts`
  MODIFY `ab_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_boost_clicks`
--
ALTER TABLE `i_agency_boost_clicks`
  MODIFY `abc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_create_requests`
--
ALTER TABLE `i_agency_create_requests`
  MODIFY `acr_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_members`
--
ALTER TABLE `i_agency_members`
  MODIFY `am_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_payouts`
--
ALTER TABLE `i_agency_payouts`
  MODIFY `ap_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_requests`
--
ALTER TABLE `i_agency_requests`
  MODIFY `ar_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_agency_social_profiles`
--
ALTER TABLE `i_agency_social_profiles`
  MODIFY `asp_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_ai_usage`
--
ALTER TABLE `i_ai_usage`
  MODIFY `ai_usage_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_announcement`
--
ALTER TABLE `i_announcement`
  MODIFY `a_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_announcement_seen`
--
ALTER TABLE `i_announcement_seen`
  MODIFY `aid` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_approve_post_notification`
--
ALTER TABLE `i_approve_post_notification`
  MODIFY `approve_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_audit_log`
--
ALTER TABLE `i_audit_log`
  MODIFY `audit_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_bank_payments`
--
ALTER TABLE `i_bank_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_blog_posts`
--
ALTER TABLE `i_blog_posts`
  MODIFY `blog_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_blog_reactions`
--
ALTER TABLE `i_blog_reactions`
  MODIFY `reaction_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_blog_views`
--
ALTER TABLE `i_blog_views`
  MODIFY `view_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_boosted_posts`
--
ALTER TABLE `i_boosted_posts`
  MODIFY `boost_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_boosted_post_seen_counter`
--
ALTER TABLE `i_boosted_post_seen_counter`
  MODIFY `bp_seen_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_boost_post_plans`
--
ALTER TABLE `i_boost_post_plans`
  MODIFY `plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `i_bulk_campaigns`
--
ALTER TABLE `i_bulk_campaigns`
  MODIFY `bc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_bulk_campaign_queue`
--
ALTER TABLE `i_bulk_campaign_queue`
  MODIFY `bq_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_campaigns`
--
ALTER TABLE `i_campaigns`
  MODIFY `campaign_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_ccbill`
--
ALTER TABLE `i_ccbill`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_chat_conversations`
--
ALTER TABLE `i_chat_conversations`
  MODIFY `con_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_chat_users`
--
ALTER TABLE `i_chat_users`
  MODIFY `chat_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_comment_reports`
--
ALTER TABLE `i_comment_reports`
  MODIFY `p_report_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_configurations`
--
ALTER TABLE `i_configurations`
  MODIFY `configuration_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_configuration_affilate`
--
ALTER TABLE `i_configuration_affilate`
  MODIFY `i_af_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `i_contacts`
--
ALTER TABLE `i_contacts`
  MODIFY `contact_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_creators`
--
ALTER TABLE `i_creators`
  MODIFY `cr_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `i_creator_auto_messages`
--
ALTER TABLE `i_creator_auto_messages`
  MODIFY `cam_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_creator_auto_message_log`
--
ALTER TABLE `i_creator_auto_message_log`
  MODIFY `cml_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_custom_codes`
--
ALTER TABLE `i_custom_codes`
  MODIFY `custom_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `i_epoch_webhook_events`
--
ALTER TABLE `i_epoch_webhook_events`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_frames`
--
ALTER TABLE `i_frames`
  MODIFY `f_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `i_friends`
--
ALTER TABLE `i_friends`
  MODIFY `fr_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `i_landing_qa`
--
ALTER TABLE `i_landing_qa`
  MODIFY `qa_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `i_langs`
--
ALTER TABLE `i_langs`
  MODIFY `lang_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `i_license_state`
--
ALTER TABLE `i_license_state`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_live`
--
ALTER TABLE `i_live`
  MODIFY `live_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_live_chat`
--
ALTER TABLE `i_live_chat`
  MODIFY `cm_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_gift_point`
--
ALTER TABLE `i_live_gift_point`
  MODIFY `gift_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `i_live_likes`
--
ALTER TABLE `i_live_likes`
  MODIFY `like_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_pinned_products`
--
ALTER TABLE `i_live_pinned_products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_polls`
--
ALTER TABLE `i_live_polls`
  MODIFY `poll_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_poll_options`
--
ALTER TABLE `i_live_poll_options`
  MODIFY `option_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_poll_votes`
--
ALTER TABLE `i_live_poll_votes`
  MODIFY `vote_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_questions`
--
ALTER TABLE `i_live_questions`
  MODIFY `question_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_reminders`
--
ALTER TABLE `i_live_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_live_video_users`
--
ALTER TABLE `i_live_video_users`
  MODIFY `live_user_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_mentions`
--
ALTER TABLE `i_mentions`
  MODIFY `m_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_message_reports`
--
ALTER TABLE `i_message_reports`
  MODIFY `p_report_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_moderator_permissions`
--
ALTER TABLE `i_moderator_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_obs_overlays`
--
ALTER TABLE `i_obs_overlays`
  MODIFY `obs_overlay_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_obs_overlay_clicks`
--
ALTER TABLE `i_obs_overlay_clicks`
  MODIFY `obs_overlay_click_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_obs_support_events`
--
ALTER TABLE `i_obs_support_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_pages`
--
ALTER TABLE `i_pages`
  MODIFY `page_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `i_payment_methods`
--
ALTER TABLE `i_payment_methods`
  MODIFY `payment_method_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_polls`
--
ALTER TABLE `i_polls`
  MODIFY `poll_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_poll_options`
--
ALTER TABLE `i_poll_options`
  MODIFY `option_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_poll_votes`
--
ALTER TABLE `i_poll_votes`
  MODIFY `vote_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_posts`
--
ALTER TABLE `i_posts`
  MODIFY `post_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `i_post_comments`
--
ALTER TABLE `i_post_comments`
  MODIFY `com_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `i_post_comment_likes`
--
ALTER TABLE `i_post_comment_likes`
  MODIFY `c_like_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_post_likes`
--
ALTER TABLE `i_post_likes`
  MODIFY `like_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `i_post_reports`
--
ALTER TABLE `i_post_reports`
  MODIFY `p_report_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_premium_plans`
--
ALTER TABLE `i_premium_plans`
  MODIFY `plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `i_product_seen_time`
--
ALTER TABLE `i_product_seen_time`
  MODIFY `p_s_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_profile_categories`
--
ALTER TABLE `i_profile_categories`
  MODIFY `c_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `i_profile_sub_categories`
--
ALTER TABLE `i_profile_sub_categories`
  MODIFY `sc_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `i_refusers`
--
ALTER TABLE `i_refusers`
  MODIFY `i_ref_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_saved_posts`
--
ALTER TABLE `i_saved_posts`
  MODIFY `save_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_scheduled_queue`
--
ALTER TABLE `i_scheduled_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_sessions`
--
ALTER TABLE `i_sessions`
  MODIFY `session_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `i_share_tokens`
--
ALTER TABLE `i_share_tokens`
  MODIFY `token_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `i_shop_configuration`
--
ALTER TABLE `i_shop_configuration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `i_social_logins`
--
ALTER TABLE `i_social_logins`
  MODIFY `s_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `i_social_networks`
--
ALTER TABLE `i_social_networks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `i_social_user_profiles`
--
ALTER TABLE `i_social_user_profiles`
  MODIFY `ius_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_stickers`
--
ALTER TABLE `i_stickers`
  MODIFY `sticker_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `i_stories_seen`
--
ALTER TABLE `i_stories_seen`
  MODIFY `i_seen_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_story_audio`
--
ALTER TABLE `i_story_audio`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_story_configuration`
--
ALTER TABLE `i_story_configuration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `i_story_highlights`
--
ALTER TABLE `i_story_highlights`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_story_highlight_items`
--
ALTER TABLE `i_story_highlight_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_story_reactions`
--
ALTER TABLE `i_story_reactions`
  MODIFY `reaction_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_story_replies`
--
ALTER TABLE `i_story_replies`
  MODIFY `sr_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_story_text_bg`
--
ALTER TABLE `i_story_text_bg`
  MODIFY `st_bg_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `i_svg_icons`
--
ALTER TABLE `i_svg_icons`
  MODIFY `icon_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `i_users`
--
ALTER TABLE `i_users`
  MODIFY `iuid` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `i_user_activity`
--
ALTER TABLE `i_user_activity`
  MODIFY `activity_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `i_user_avatars`
--
ALTER TABLE `i_user_avatars`
  MODIFY `avatar_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `i_user_blocked_countries`
--
ALTER TABLE `i_user_blocked_countries`
  MODIFY `b_c_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_blocks`
--
ALTER TABLE `i_user_blocks`
  MODIFY `block_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_conversation_uploads`
--
ALTER TABLE `i_user_conversation_uploads`
  MODIFY `upload_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_covers`
--
ALTER TABLE `i_user_covers`
  MODIFY `cover_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `i_user_deletion_requests`
--
ALTER TABLE `i_user_deletion_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_exports`
--
ALTER TABLE `i_user_exports`
  MODIFY `export_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_frames`
--
ALTER TABLE `i_user_frames`
  MODIFY `f_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_notifications`
--
ALTER TABLE `i_user_notifications`
  MODIFY `not_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `i_user_payments`
--
ALTER TABLE `i_user_payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `i_user_payouts`
--
ALTER TABLE `i_user_payouts`
  MODIFY `payout_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_user_point_earnings`
--
ALTER TABLE `i_user_point_earnings`
  MODIFY `point_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `i_user_product_posts`
--
ALTER TABLE `i_user_product_posts`
  MODIFY `pr_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_stories`
--
ALTER TABLE `i_user_stories`
  MODIFY `s_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `i_user_subscribe_plans`
--
ALTER TABLE `i_user_subscribe_plans`
  MODIFY `plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `i_user_subscriptions`
--
ALTER TABLE `i_user_subscriptions`
  MODIFY `subscription_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_subscription_intents`
--
ALTER TABLE `i_user_subscription_intents`
  MODIFY `intent_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_user_uploads`
--
ALTER TABLE `i_user_uploads`
  MODIFY `upload_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `i_user_web_push_subscriptions`
--
ALTER TABLE `i_user_web_push_subscriptions`
  MODIFY `subscription_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_verification_requests`
--
ALTER TABLE `i_verification_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `i_video_call`
--
ALTER TABLE `i_video_call`
  MODIFY `vc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i_website_social_networks`
--
ALTER TABLE `i_website_social_networks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `i_ads_codes`
--
ALTER TABLE `i_ads_codes`
  ADD CONSTRAINT `fk_ads_codes_placement` FOREIGN KEY (`placement_id`) REFERENCES `i_ads_placements` (`placement_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
