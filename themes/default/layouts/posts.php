<?php include_once __DIR__ . '/widgets/ads_helper.php'; ?>
<?php
// Ensure base variables exist to avoid notices in layout
$page = isset($page) ? $page : '';
$pCat = isset($pCat) ? $pCat : null;
if ($page === '' && isset($p_profileID)) {
    $page = 'profile';
}
?>
<div class="th_middle">
    <div class="greetalert hidden"></div>
    <div class="pageMiddle">
        <?php
        // Normalize page and category early to avoid array-to-string issues
        $page = is_array($page) ? (string) reset($page) : (string) ($page ?? '');
        $pCat = is_array($pCat) ? (string) reset($pCat) : (string) ($pCat ?? '');

        // If user is not logged in, show welcome box
        if ($logedIn === '0') {
            include __DIR__ . '/posts/welcomebox.php';
        } else {
            if ($page !== 'profile') {
                // Announcement box
                include __DIR__ . '/widgets/announcement.php';

                // Show stories if enabled
                if ($iN->iN_StoryData($userID, '1') === 'yes') {
                    include __DIR__ . '/storie/stories.php';
                }

                // Show post form if allowed
                if ($normalUserCanPost === 'yes' || $feesStatus === '2') {
                    include __DIR__ . '/posts/postForm.php';
                }
            }
        }

        // Random box logic (ads or suggested users) - skip on profile page
        if ($page !== 'profile') {
            $files = ['suggestedusers', 'ads'];
            shuffle($files);
            include __DIR__ . '/random_boxs/' . iN_HelpSecure($files[0]) . '.php';
        }

        // Post category (null-safe)
        $pCat = $pCat ?? null;
        $safePageType = $page ?: (isset($p_profileID) ? 'profile' : '');
        $safePCat = $pCat ?: '';

        // Product filter (profile products tab)
        $productTypesAllowed = array('all','bookazoom','digitaldownload','liveeventticket','artcommission','joininstagramclosefriends');
        $productFilter = 'all';
        if ($safePageType === 'profile' && $safePCat === 'products') {
            $productFilterParam = isset($_GET['ptype']) ? trim($_GET['ptype']) : 'all';
            if (in_array($productFilterParam, $productTypesAllowed, true)) {
                $productFilter = $productFilterParam;
            }
        }

        // Header/Top ad placement (new Ads system with Adsense fallback)
        $legacyHeader = [
            'client' => $adsenseClientId ?? '',
            'slot' => $adsenseSlotTop ?? '',
            'size' => $adsenseTopSize ?? 'responsive'
        ];
        echo iN_render_ad_with_legacy('header_top', 'header', $legacyHeader);

        // Show pinned posts only on profile page
        if ($page === 'profile') {
            include __DIR__ . '/posts/pinedPosts.php';
        }

        // Posts output block
        if ($page === 'profile') {
            $profileDetails = $iN->iN_GetUserDetails($p_profileID);
            $profileBirthday = isset($profileDetails['birthday']) ? $profileDetails['birthday'] : null;
            $formattedBirthday = $profileBirthday ? date('d.m.Y', strtotime($profileBirthday)) : null;
            $age = null;
            $isOwner = isset($userID) && $userID == $p_profileID;
            $hasBirthday = $profileBirthday && $formattedBirthday;
            if ($profileBirthday) {
                $birthTime = strtotime($profileBirthday);
                if ($birthTime) {
                    $age = (int) floor((time() - $birthTime) / 31556926);
                }
            }
            $likesGiven = $iN->iN_TotalUserGivenLikes($p_profileID);
            $commentsGiven = $iN->iN_TotalUserGivenComments($p_profileID);
            $profileSocials = $iN->iN_ShowUserSocialSites($p_profileID);
            $mediaPreviews = $iN->iN_GetPublicMediaPreviews($p_profileID, 6);
            $reelsPreviews = $iN->iN_GetPublicReelsPreviews($p_profileID, 6);
            $followingPreviews = $iN->iN_FollowingUsersListProfilePage($p_profileID, 0, 6);
            $totalFollowingUsers = $iN->iN_UserTotalFollowingUsers($p_profileID);
            $showProfileGender = isset($profileDetails['show_profile_gender']) ? (string)$profileDetails['show_profile_gender'] : '1';
            $showProfileAge = isset($profileDetails['show_profile_age']) ? (string)$profileDetails['show_profile_age'] : '1';
            $showProfileBirthdate = isset($profileDetails['show_profile_birthdate']) ? (string)$profileDetails['show_profile_birthdate'] : '1';
            $showProfileCategory = isset($profileDetails['show_profile_category']) ? (string)$profileDetails['show_profile_category'] : '1';
            $showProfileLikes = isset($profileDetails['show_profile_likes']) ? (string)$profileDetails['show_profile_likes'] : '1';
            $showProfileComments = isset($profileDetails['show_profile_comments']) ? (string)$profileDetails['show_profile_comments'] : '1';
            $showProfileBio = isset($profileDetails['show_profile_bio']) ? (string)$profileDetails['show_profile_bio'] : '1';
            $showProfileSocial = isset($profileDetails['show_profile_social']) ? (string)$profileDetails['show_profile_social'] : '1';
            $canViewGender = $isOwner || $showProfileGender === '1';
            $canViewAge = $isOwner || $showProfileAge === '1';
            $canViewBirthdate = $isOwner || $showProfileBirthdate === '1';
            $canViewCategory = $isOwner || $showProfileCategory === '1';
            $canViewLikes = $isOwner || $showProfileLikes === '1';
            $canViewComments = $isOwner || $showProfileComments === '1';
            $canViewBio = $isOwner || $showProfileBio === '1';
            $canViewSocial = $isOwner || $showProfileSocial === '1';
            $metaIcons = [
                'gender' => $iN->iN_SelectedMenuIcon('12'),
                'age' => $iN->iN_SelectedMenuIcon('115'),
                'birthdate' => $iN->iN_SelectedMenuIcon('73'),
                'category' => $iN->iN_SelectedMenuIcon('65'),
                'likes' => $iN->iN_SelectedMenuIcon('18'),
                'comments' => $iN->iN_SelectedMenuIcon('20')
            ];
            ?>
            <div class="pageMiddleRow">
                <div class="pageMiddleInfo">
                    <?php
                    $scheduledLives = $iN->iN_GetScheduledLiveListByUser((int)$p_profileID, 0);
                    include __DIR__ . '/live/profile_scheduled_lives.php';
                    ?>
                    <div class="profile_meta_card">
                        <div class="profile_meta_header">
                            <div class="profile_meta_title"><?php echo iN_HelpSecure($LANG['profile_info_title']); ?></div>
                        </div>
                        <div class="profile_meta_list">
                            <?php if ($canViewGender) { ?>
                            <div class="profile_meta_row">
                                <span class="profile_meta_icon"><?php echo html_entity_decode($metaIcons['gender']); ?></span>
                                <div class="meta_text">
                                    <span class="label"><?php echo iN_HelpSecure($LANG['gender']); ?></span>
                                    <span class="value"><?php echo html_entity_decode($pGender); ?></span>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if (($hasBirthday || $isOwner) && $canViewAge) { ?>
                            <div class="profile_meta_row">
                                <span class="profile_meta_icon"><?php echo html_entity_decode($metaIcons['age']); ?></span>
                                <div class="meta_text">
                                    <span class="label"><?php echo iN_HelpSecure($LANG['age']); ?></span>
                                    <span class="value">
                                        <?php if ($age) { echo iN_HelpSecure($age); }
                                        elseif ($isOwner) { ?>
                                            <a class="profile_meta_missing" href="<?php echo iN_HelpSecure($base_url . 'settings'); ?>">
                                                <?php echo iN_HelpSecure($LANG['add_birthdate_for_age']); ?>
                                            </a>
                                        <?php } ?>
                                    </span>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if (($hasBirthday || $isOwner) && $canViewBirthdate) { ?>
                            <div class="profile_meta_row">
                                <span class="profile_meta_icon"><?php echo html_entity_decode($metaIcons['birthdate']); ?></span>
                                <div class="meta_text">
                                    <span class="label"><?php echo iN_HelpSecure($LANG['birthdate']); ?></span>
                                    <span class="value">
                                        <?php if ($formattedBirthday) { echo iN_HelpSecure($formattedBirthday); }
                                        elseif ($isOwner) { ?>
                                            <a class="profile_meta_missing" href="<?php echo iN_HelpSecure($base_url . 'settings'); ?>">
                                                <?php echo iN_HelpSecure($LANG['add_birthdate']); ?>
                                            </a>
                                        <?php } ?>
                                    </span>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if ($canViewCategory) { ?>
                            <div class="profile_meta_row">
                                <span class="profile_meta_icon"><?php echo html_entity_decode($metaIcons['category']); ?></span>
                                <div class="meta_text">
                                    <span class="label"><?php echo iN_HelpSecure($LANG['profile_category']); ?></span>
                                    <span class="value"><?php echo html_entity_decode($pCategory ?? '-'); ?></span>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if ($canViewLikes) { ?>
                            <div class="profile_meta_row">
                                <span class="profile_meta_icon"><?php echo html_entity_decode($metaIcons['likes']); ?></span>
                                <div class="meta_text">
                                    <span class="label"><?php echo iN_HelpSecure($LANG['likes']); ?></span>
                                    <span class="value"><?php echo (int) $likesGiven; ?></span>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if ($canViewComments) { ?>
                            <div class="profile_meta_row">
                                <span class="profile_meta_icon"><?php echo html_entity_decode($metaIcons['comments']); ?></span>
                                <div class="meta_text">
                                    <span class="label"><?php echo iN_HelpSecure($LANG['comments']); ?></span>
                                    <span class="value"><?php echo (int) $commentsGiven; ?></span>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php if (!empty($p_profileBio) && $canViewBio) { ?>
                        <div class="profile_meta_bio">
                            <div class="profile_meta_bio_title"><?php echo iN_HelpSecure($LANG['about']); ?></div>
                            <div class="profile_meta_bio_text"><?php echo html_entity_decode($p_profileBio); ?></div>
                        </div>
                        <?php } ?>
                        <?php if ($profileSocials && $canViewSocial) { ?>
                        <div class="profile_meta_social">
                            <div class="profile_meta_bio_title"><?php echo iN_HelpSecure($LANG['social_links']); ?></div>
                            <div class="profile_meta_social_list">
                                <?php foreach ($profileSocials as $sDa) {
                                    $sLink = $sDa['s_link'] ?? null;
                                    $sIcon = $sDa['social_icon'] ?? null;
                                    if (!$sLink || !$sIcon) { continue; }
                                    ?>
                                    <a class="profile_meta_social_link" href="<?php echo iN_HelpSecure($sLink, FILTER_VALIDATE_URL); ?>" target="_blank" rel="nofollow noopener">
                                        <?php echo html_entity_decode($sIcon); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php if ($mediaPreviews) { ?>
                    <div class="profile_media_section">
                        <div class="profile_media_header">
                            <div class="profile_media_title"><?php echo iN_HelpSecure($LANG['photos'] ?? 'Photos'); ?></div>
                            <a class="profile_media_link" href="<?php echo iN_HelpSecure($base_url . $p_username . '?pcat=photos'); ?>">
                                <?php echo iN_HelpSecure($LANG['see_all_photos'] ?? 'See all photos'); ?>
                            </a>
                        </div>
                        <div class="profile_media_grid">
                            <?php foreach ($mediaPreviews as $mediaItem) {
                                $postId = isset($mediaItem['post_id']) ? (int)$mediaItem['post_id'] : 0;
                                $postSlug = isset($mediaItem['url_slug']) && $mediaItem['url_slug'] ? $mediaItem['url_slug'] : $postId;
                                $postFiles = isset($mediaItem['post_file']) ? trim($mediaItem['post_file'], ',') : '';
                                $firstFileID = 0;
                                if (!empty($postFiles)) {
                                    $explodeFiles = array_unique(explode(',', $postFiles));
                                    $firstFileID = (int) $explodeFiles[0];
                                }
                                if (!$firstFileID) { continue; }
                                $uploadDetails = $iN->iN_GetUploadedFileDetails($firstFileID);
                                if (!$uploadDetails) { continue; }
                                $thumbPath = $uploadDetails['upload_tumbnail_file_path'] ?? $uploadDetails['uploaded_file_path'] ?? '';
                                if (!$thumbPath) { continue; }
                                if (function_exists('storage_public_url')) {
                                    $thumbUrl = storage_public_url($thumbPath);
                                } else {
                                    $thumbUrl = $base_url . $thumbPath;
                                }
                                $postUrl = $base_url . 'post/' . $postSlug . '_' . $postId;
                                ?>
                                <a class="profile_media_item" href="<?php echo iN_HelpSecure($postUrl, FILTER_VALIDATE_URL); ?>" aria-label="<?php echo iN_HelpSecure($LANG['photos'] ?? 'Photos'); ?>">
                                    <div class="profile_media_thumb" style="background-image:url('<?php echo iN_HelpSecure($thumbUrl, FILTER_VALIDATE_URL); ?>');"></div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if ($reelsPreviews) { ?>
                    <div class="profile_media_section profile_media_section_reels">
                        <div class="profile_media_header">
                            <div class="profile_media_title"><?php echo iN_HelpSecure($LANG['reels'] ?? 'Reels'); ?></div>
                            <a class="profile_media_link" href="<?php echo iN_HelpSecure($base_url . $p_username . '?pcat=reels'); ?>">
                                <?php echo iN_HelpSecure($LANG['see_all_reels'] ?? 'See all reels'); ?>
                            </a>
                        </div>
                        <div class="profile_media_grid">
                            <?php foreach ($reelsPreviews as $mediaItem) {
                                $postId = isset($mediaItem['post_id']) ? (int)$mediaItem['post_id'] : 0;
                                $postSlug = isset($mediaItem['url_slug']) && $mediaItem['url_slug'] ? $mediaItem['url_slug'] : $postId;
                                $postFiles = isset($mediaItem['post_file']) ? trim($mediaItem['post_file'], ',') : '';
                                $firstFileID = 0;
                                if (!empty($postFiles)) {
                                    $explodeFiles = array_unique(explode(',', $postFiles));
                                    $firstFileID = (int) $explodeFiles[0];
                                }
                                if (!$firstFileID) { continue; }
                                $uploadDetails = $iN->iN_GetUploadedFileDetails($firstFileID);
                                if (!$uploadDetails) { continue; }
                                $thumbPath = $uploadDetails['upload_tumbnail_file_path'] ?? $uploadDetails['uploaded_file_path'] ?? '';
                                if (!$thumbPath) { continue; }
                                if (function_exists('storage_public_url')) {
                                    $thumbUrl = storage_public_url($thumbPath);
                                } else {
                                    $thumbUrl = $base_url . $thumbPath;
                                }
                                $postUrl = $base_url . 'post/' . $postSlug . '_' . $postId;
                                ?>
                                <a class="profile_media_item profile_media_item_reel" href="<?php echo iN_HelpSecure($postUrl, FILTER_VALIDATE_URL); ?>" aria-label="<?php echo iN_HelpSecure($LANG['reels'] ?? 'Reels'); ?>">
                                    <div class="profile_media_thumb" style="background-image:url('<?php echo iN_HelpSecure($thumbUrl, FILTER_VALIDATE_URL); ?>');"></div>
                                    <div class="profile_media_badge"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('187')); ?></div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if ($followingPreviews) { ?>
                    <div class="profile_follow_section">
                        <div class="profile_follow_header">
                            <div class="profile_follow_title_group">
                                <div class="profile_media_title"><?php echo iN_HelpSecure($LANG['friends'] ?? 'Friends'); ?></div>
                                <div class="profile_follow_count"><?php echo iN_HelpSecure(number_format($totalFollowingUsers) . ' ' . ($LANG['friends'] ?? 'Friends')); ?></div>
                            </div>
                            <a class="profile_media_link" href="<?php echo iN_HelpSecure($base_url . $p_username . '?pcat=following'); ?>">
                                <?php echo iN_HelpSecure($LANG['see_all_friends'] ?? 'See all friends'); ?>
                            </a>
                        </div>
                        <div class="profile_follow_grid">
                            <?php foreach ($followingPreviews as $friendData) {
                                $friendId = isset($friendData['fr_two']) ? (int)$friendData['fr_two'] : 0;
                                if (!$friendId) { continue; }
                                $friendDetails = $iN->iN_GetUserDetails($friendId);
                                if (!$friendDetails) { continue; }
                                $friendUsername = $friendDetails['i_username'] ?? null;
                                if (!$friendUsername) { continue; }
                                $friendFullName = $friendDetails['i_user_fullname'] ?? $friendUsername;
                                $friendAvatar = $iN->iN_UserAvatar($friendId, $base_url);
                                $friendUrl = $base_url . $friendUsername;
                                ?>
                                <a class="profile_follow_item" href="<?php echo iN_HelpSecure($friendUrl, FILTER_VALIDATE_URL); ?>">
                                    <div class="profile_follow_thumb" style="background-image:url('<?php echo iN_HelpSecure($friendAvatar, FILTER_VALIDATE_URL); ?>');"></div>
                                    <div class="profile_follow_name"><?php echo iN_HelpSecure($friendFullName); ?></div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                    <?php
                        // Render footer blocks without polluting page context
                        $pageBackup = $page;
                        $pCatBackup = $pCat;
                    ?>
                    <div class="footer_container">
                        <?php include __DIR__ . '/footer.php'; ?>
                    </div>

                  
                    <?php
                        $page = $pageBackup;
                        $pCat = $pCatBackup;
                    ?>
                </div>
                <div class="pageMiddlePosts">
                    <?php
                    include __DIR__ . '/posts/boostedPost.php';
                    ?>
                    <?php if ($safePageType === 'profile' && $safePCat === 'products') { ?>
                        <div class="marketplace-toolbar profile-product-filter">
                            <div class="marketplace-toolbar__actions">
                                <select id="profileProductFilter"
                                    class="profile-product-select"
                                    data-base="<?php echo iN_HelpSecure($base_url); ?>"
                                    data-user="<?php echo iN_HelpSecure($p_username); ?>">
                                    <?php foreach ($productTypesAllowed as $pt) { ?>
                                        <option value="<?php echo iN_HelpSecure($pt); ?>" <?php echo $productFilter === $pt ? 'selected' : ''; ?>>
                                            <?php echo iN_HelpSecure($pt === 'all' ? $LANG['all_products'] : $LANG[$pt]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    <?php } ?>
                    <div id="moreType" data-type="<?php echo iN_HelpSecure($safePageType); ?>" data-po="<?php echo iN_HelpSecure($safePCat); ?>" data-pf="<?php echo iN_HelpSecure($productFilter); ?>">
                        <?php include __DIR__ . '/posts/htmlPosts.php'; ?>
                    </div>
                    <?php include __DIR__ . '/posts/discoverySeeMore.php'; ?>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="pageMiddlePosts">
                <?php
                // Boosted post display (feed & other pages) - force include to verify
                echo '<!-- boostedPost slot (feed context) -->';
                include __DIR__ . '/posts/boostedPost.php';
                ?>
                <?php if ($safePageType === 'profile' && $safePCat === 'products') { ?>
                    <div class="marketplace-toolbar profile-product-filter">
                        <div class="marketplace-toolbar__actions">
                            <select id="profileProductFilter"
                                class="profile-product-select"
                                data-base="<?php echo iN_HelpSecure($base_url); ?>"
                                data-user="<?php echo iN_HelpSecure($p_username); ?>">
                                <?php foreach ($productTypesAllowed as $pt) { ?>
                                    <option value="<?php echo iN_HelpSecure($pt); ?>" <?php echo $productFilter === $pt ? 'selected' : ''; ?>>
                                        <?php echo iN_HelpSecure($pt === 'all' ? $LANG['all_products'] : $LANG[$pt]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                <?php } ?>
                <div id="moreType" data-type="<?php echo iN_HelpSecure($safePageType); ?>" data-po="<?php echo iN_HelpSecure($safePCat); ?>" data-pf="<?php echo iN_HelpSecure($productFilter); ?>">
                    <?php include __DIR__ . '/posts/htmlPosts.php'; ?>
                </div>
                <?php include __DIR__ . '/posts/discoverySeeMore.php'; ?>
            </div>
            <?php
        }

        $legacyFooter = [
            'client' => $adsenseClientId ?? '',
            'slot' => $adsenseSlotFooter ?? '',
            'size' => $adsenseFooterSize ?? 'responsive'
        ];
        echo iN_render_ad_with_legacy('footer', 'footer', $legacyFooter);
        ?>
    </div>
</div>
