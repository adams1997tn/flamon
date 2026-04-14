<?php
$csrfToken = function_exists('csrf_get_token') ? csrf_get_token() : (isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '');
$canDeleteChats = (string)$userType === '2';
$searchTerm = isset($_GET['sr']) ? trim((string)$iN->iN_Secure($_GET['sr'])) : '';
$selectedChatID = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;
$pagep = isset($_GET['page-id']) ? (int)$_GET['page-id'] : 1;
$pageLimit = isset($paginationLimit) ? (int)$paginationLimit : 20;
if ($pageLimit <= 0) {
    $pageLimit = 20;
}
if ($pagep <= 0) {
    $pagep = 1;
}

$formatMessagePreview = static function (array $messageData) use ($LANG): string {
    $privatePrice = trim((string)($messageData['private_price'] ?? ''));
    $privateStatus = (string)($messageData['private_status'] ?? 'opened');
    $fileValue = trim((string)($messageData['file'] ?? ''));
    $stickerValue = trim((string)($messageData['sticker_url'] ?? ''));
    $gifValue = trim((string)($messageData['gifurl'] ?? ''));
    $messageText = trim(strip_tags((string)($messageData['message'] ?? '')));

    if ($fileValue !== '') {
        if ($messageText !== '') {
            if (function_exists('mb_substr') && function_exists('mb_strlen')) {
                return mb_strlen($messageText, 'UTF-8') > 90 ? mb_substr($messageText, 0, 90, 'UTF-8') . '...' : $messageText;
            }
            return strlen($messageText) > 90 ? substr($messageText, 0, 90) . '...' : $messageText;
        }
        return '';
    }
    if ($stickerValue !== '') {
        return (string)($LANG['chat_message_type_sticker'] ?? ($LANG['sticker'] ?? $LANG['messages']));
    }
    if ($gifValue !== '') {
        return (string)($LANG['chat_message_type_gif'] ?? $LANG['messages']);
    }
    if ($privatePrice !== '' && $privateStatus === 'closed') {
        return (string)($LANG['locked_message'] ?? $LANG['messages']);
    }

    if ($messageText === '') {
        return '-';
    }

    if (function_exists('mb_substr') && function_exists('mb_strlen')) {
        return mb_strlen($messageText, 'UTF-8') > 90 ? mb_substr($messageText, 0, 90, 'UTF-8') . '...' : $messageText;
    }

    return strlen($messageText) > 90 ? substr($messageText, 0, 90) . '...' : $messageText;
};

$renderAgo = static function ($unixTime): string {
    $unixTime = (int)$unixTime;
    if ($unixTime <= 0) {
        return '-';
    }

    $from = date('Y-m-d H:i:s', $unixTime);
    return TimeAgo::ago($from, date('Y-m-d H:i:s'));
};

$buildParticipant = static function (array $row, string $prefix) use ($base_url, $iN): array {
    $userId = (int)($row[$prefix . '_id'] ?? 0);
    $username = (string)($row[$prefix . '_username'] ?? '');
    $fullname = trim((string)($row[$prefix . '_fullname'] ?? ''));
    if ($fullname === '') {
        $fullname = $username !== '' ? $username : '#' . $userId;
    }
    $profileUrl = $username !== '' ? $base_url . $username : '#';
    $avatarUrl = $userId > 0 ? $iN->iN_UserAvatar($userId, $base_url) : '';

    return [
        'id' => $userId,
        'username' => $username,
        'fullname' => $fullname,
        'profile_url' => $profileUrl,
        'avatar_url' => (string)$avatarUrl,
    ];
};
$participantHandle = static function (array $participant): string {
    $username = trim((string)($participant['username'] ?? ''));
    if ($username !== '') {
        return '@' . $username;
    }
    return '#' . (int)($participant['id'] ?? 0);
};
$truncateParticipantText = static function (string $text, int $limit = 10): string {
    $text = trim($text);
    if ($text === '') {
        return '-';
    }
    if ($limit < 1) {
        return $text;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
};
$participantDisplayName = static function (array $participant) use ($truncateParticipantText): string {
    return $truncateParticipantText((string)($participant['fullname'] ?? ''), 10);
};
$participantDisplayHandle = static function (array $participant) use ($truncateParticipantText): string {
    $username = trim((string)($participant['username'] ?? ''));
    if ($username !== '') {
        return '@' . $truncateParticipantText($username, 10);
    }
    return '#' . (int)($participant['id'] ?? 0);
};
$extractMessageFileIds = static function (string $fileValue): array {
    $trimmed = trim($fileValue, ", \t\n\r\0\x0B");
    if ($trimmed === '') {
        return [];
    }
    $parts = array_unique(array_map('trim', explode(',', $trimmed)));
    $ids = [];
    foreach ($parts as $part) {
        if ($part !== '' && ctype_digit($part) && (int)$part > 0) {
            $ids[] = (int)$part;
        }
    }
    return $ids;
};
$getUploadedMessageFile = static function (int $uploadId) use ($iN): ?array {
    static $cache = [];
    if ($uploadId <= 0) {
        return null;
    }
    if (!array_key_exists($uploadId, $cache)) {
        $fileData = $iN->iN_GetUploadedMessageFileDetails($uploadId);
        $cache[$uploadId] = is_array($fileData) ? $fileData : null;
    }
    return $cache[$uploadId];
};
$buildUploadedFileUrl = static function (string $filePath) use ($base_url): string {
    $filePath = trim($filePath);
    if ($filePath === '') {
        return '';
    }
    if (function_exists('storage_public_url')) {
        return (string)storage_public_url($filePath);
    }
    return rtrim((string)$base_url, '/') . '/' . ltrim($filePath, '/');
};
$renderMessageAttachments = static function (string $fileValue, int $limit = 3) use ($extractMessageFileIds, $getUploadedMessageFile, $buildUploadedFileUrl, $LANG): string {
    $ids = $extractMessageFileIds($fileValue);
    if (!$ids) {
        return '';
    }

    $attachments = [];
    foreach ($ids as $uploadId) {
        $fileData = $getUploadedMessageFile($uploadId);
        if (!$fileData) {
            continue;
        }
        $filePath = trim((string)($fileData['uploaded_file_path'] ?? ''));
        if ($filePath === '') {
            continue;
        }
        $fileUrl = $buildUploadedFileUrl($filePath);
        if ($fileUrl === '') {
            continue;
        }
        $fileExt = strtolower(trim((string)($fileData['uploaded_file_ext'] ?? '')));
        $fileType = 'file';
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif'], true)) {
            $fileType = 'image';
        } elseif (in_array($fileExt, ['mp4', 'webm', 'mov', 'm4v', 'ogg'], true)) {
            $fileType = 'video';
        }
        $attachments[] = [
            'id' => $uploadId,
            'url' => $fileUrl,
            'ext' => $fileExt,
            'type' => $fileType,
        ];
    }

    if (!$attachments) {
        return '';
    }

    $limit = $limit > 0 ? $limit : 1;
    $visible = array_slice($attachments, 0, $limit);
    $remaining = count($attachments) - count($visible);

    ob_start();
    ?>
    <div class="manage_chats_attachments">
        <?php foreach ($visible as $attachment) {
            $extLabel = $attachment['ext'] !== '' ? strtoupper((string)$attachment['ext']) : '';
            $fileText = '#' . (int)$attachment['id'] . ($extLabel !== '' ? (' ' . $extLabel) : '');
            if ($attachment['type'] === 'image') { ?>
                <a class="manage_chats_attachment manage_chats_attachment_image" href="<?php echo iN_HelpSecure($attachment['url']); ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo iN_HelpSecure($attachment['url']); ?>" alt="<?php echo iN_HelpSecure($fileText); ?>">
                </a>
            <?php } else { ?>
                <a class="manage_chats_attachment manage_chats_attachment_file<?php echo $attachment['type'] === 'video' ? ' manage_chats_attachment_video' : ''; ?>" href="<?php echo iN_HelpSecure($attachment['url']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo iN_HelpSecure($fileText); ?>
                </a>
            <?php }
        } ?>
        <?php if ($remaining > 0) { ?>
            <span class="manage_chats_attachment_more">+<?php echo iN_HelpSecure($remaining); ?></span>
        <?php } ?>
    </div>
    <?php
    return (string)ob_get_clean();
};

?>
<div class="i_contents_container manage-chats-page">
    <input type="hidden" id="adminChatsCsrf" value="<?php echo iN_HelpSecure($csrfToken); ?>">
    <input type="hidden" id="adminDeleteChatConfirm" value="<?php echo iN_HelpSecure($LANG['are_you_sure_to_delete_conversation'] ?? $LANG['delete_conversation']); ?>">
    <input type="hidden" id="adminDeleteChatMessageConfirm" value="<?php echo iN_HelpSecure($LANG['are_you_sure_to_delete_message'] ?? $LANG['delete_message']); ?>">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_chats'] ?? $LANG['messages']); ?>
        </div>

        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <?php if ($selectedChatID > 0) {
                $conversation = DB::one(
                    "SELECT C.chat_id,
                            U1.iuid AS user_one_id, U1.i_username AS user_one_username, U1.i_user_fullname AS user_one_fullname,
                            U2.iuid AS user_two_id, U2.i_username AS user_two_username, U2.i_user_fullname AS user_two_fullname
                     FROM i_chat_users C
                     LEFT JOIN i_users U1 ON U1.iuid = C.user_one
                     LEFT JOIN i_users U2 ON U2.iuid = C.user_two
                     WHERE C.chat_id = ?
                     LIMIT 1",
                    [$selectedChatID]
                );

                $backUrl = iN_HelpSecure($base_url) . 'admin/manage_chats';
                if ($searchTerm !== '') {
                    $backUrl .= '?sr=' . rawurlencode($searchTerm);
                }

                if (!$conversation) { ?>
                    <div class="warning_"><?php echo iN_HelpSecure($LANG['chat_no_conversations_found'] ?? ($LANG['messages'] . ' - 0')); ?></div>
                    <div class="i_contents_section flex_ manage_margin_bottom">
                        <div class="seePost c2 border_one transition tabing flex_">
                            <a class="tabing flex_" href="<?php echo iN_HelpSecure($backUrl); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('102')) . iN_HelpSecure($LANG['preview_page']); ?>
                            </a>
                        </div>
                    </div>
                <?php } else {
                    $participantOne = $buildParticipant($conversation, 'user_one');
                    $participantTwo = $buildParticipant($conversation, 'user_two');

                    $messages = DB::all(
                        "SELECT * FROM (
                            SELECT M.con_id, M.chat_id_fk, M.user_one, M.user_two, M.message, M.sticker_url, M.gifurl, M.file,
                                   M.private_price, M.private_status, M.gifMoney, M.seen_status, M.time,
                                   U1.i_username AS sender_username, U1.i_user_fullname AS sender_fullname,
                                   U2.i_username AS receiver_username, U2.i_user_fullname AS receiver_fullname
                            FROM i_chat_conversations M
                            LEFT JOIN i_users U1 ON U1.iuid = M.user_one
                            LEFT JOIN i_users U2 ON U2.iuid = M.user_two
                            WHERE M.chat_id_fk = ?
                            ORDER BY M.con_id DESC
                            LIMIT 100
                        ) t
                        ORDER BY con_id ASC",
                        [$selectedChatID]
                    );
                    ?>

                    <div class="i_contents_section flex_ manage_margin_bottom tabing_non_justify">
                        <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['chat_participants'] ?? ($LANG['user'] . ' / ' . $LANG['user'])); ?></div>
                        <div class="irow_box_right">
                            <div class="manage_chats_participants">
                                <a class="manage_chats_participant_item" href="<?php echo iN_HelpSecure($participantOne['profile_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <span class="manage_chats_participant_avatar"><img src="<?php echo iN_HelpSecure($participantOne['avatar_url']); ?>" alt="<?php echo iN_HelpSecure($participantOne['fullname']); ?>"></span>
                                    <span class="manage_chats_participant_meta">
                                        <span class="manage_chats_participant_name" title="<?php echo iN_HelpSecure($participantOne['fullname']); ?>"><?php echo iN_HelpSecure($participantDisplayName($participantOne)); ?></span>
                                        <span class="manage_chats_participant_handle" title="<?php echo iN_HelpSecure($participantHandle($participantOne)); ?>"><?php echo iN_HelpSecure($participantDisplayHandle($participantOne)); ?></span>
                                    </span>
                                </a>
                                <span class="manage_chats_participant_separator">&harr;</span>
                                <a class="manage_chats_participant_item" href="<?php echo iN_HelpSecure($participantTwo['profile_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <span class="manage_chats_participant_avatar"><img src="<?php echo iN_HelpSecure($participantTwo['avatar_url']); ?>" alt="<?php echo iN_HelpSecure($participantTwo['fullname']); ?>"></span>
                                    <span class="manage_chats_participant_meta">
                                        <span class="manage_chats_participant_name" title="<?php echo iN_HelpSecure($participantTwo['fullname']); ?>"><?php echo iN_HelpSecure($participantDisplayName($participantTwo)); ?></span>
                                        <span class="manage_chats_participant_handle" title="<?php echo iN_HelpSecure($participantHandle($participantTwo)); ?>"><?php echo iN_HelpSecure($participantDisplayHandle($participantTwo)); ?></span>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="i_contents_section flex_ manage_margin_bottom">
                        <div class="seePost c2 border_one transition tabing flex_">
                            <a class="tabing flex_" href="<?php echo iN_HelpSecure($backUrl); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('102')) . iN_HelpSecure($LANG['preview_page']); ?>
                            </a>
                        </div>
                        <?php if ($canDeleteChats) { ?>
                            <div class="delu border_one transition tabing flex_ delete_admin_chat" data-chat-id="<?php echo iN_HelpSecure($selectedChatID); ?>">
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['delete']); ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>

                    <?php if ($messages) { ?>
                        <div class="i_overflow_x_auto">
                            <table class="border_one">
                                <tr>
                                    <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['chat_sender'] ?? $LANG['user']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['chat_receiver'] ?? $LANG['user']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['chat_last_message'] ?? $LANG['messages']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['chat_sent_time'] ?? $LANG['registered_time']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                                </tr>
                                <?php foreach ($messages as $messageRow) {
                                    $messageId = (int)($messageRow['con_id'] ?? 0);
                                    $senderName = trim((string)($messageRow['sender_fullname'] ?? ''));
                                    if ($senderName === '') {
                                        $senderName = (string)($messageRow['sender_username'] ?? ('#' . (int)($messageRow['user_one'] ?? 0)));
                                    }
                                    $receiverName = trim((string)($messageRow['receiver_fullname'] ?? ''));
                                    if ($receiverName === '') {
                                        $receiverName = (string)($messageRow['receiver_username'] ?? ('#' . (int)($messageRow['user_two'] ?? 0)));
                                    }
                                    $messagePreview = $formatMessagePreview($messageRow);
                                    $messageAttachments = $renderMessageAttachments((string)($messageRow['file'] ?? ''), 4);
                                    if ($messagePreview === '' && $messageAttachments === '') {
                                        $messagePreview = '-';
                                    }
                                    ?>
                                    <tr class="transition trhover">
                                        <td><?php echo iN_HelpSecure($messageId); ?></td>
                                        <td class="see_post_details"><div class="sim"><?php echo iN_HelpSecure($senderName); ?></div></td>
                                        <td class="see_post_details"><div class="sim"><?php echo iN_HelpSecure($receiverName); ?></div></td>
                                        <td class="see_post_details">
                                            <div class="manage_chats_message_cell">
                                                <?php if ($messagePreview !== '') { ?>
                                                    <div class="sim"><?php echo iN_HelpSecure($messagePreview); ?></div>
                                                <?php } ?>
                                                <?php if ($messageAttachments !== '') {
                                                    echo $messageAttachments;
                                                } ?>
                                            </div>
                                        </td>
                                        <td class="see_post_details"><div class="sim"><?php echo iN_HelpSecure($renderAgo($messageRow['time'] ?? 0)); ?></div></td>
                                        <td class="flex_ tabing_non_justify">
                                            <?php if ($canDeleteChats) { ?>
                                                <div class="delu border_one transition tabing flex_ delete_admin_chat_message" data-chat-id="<?php echo iN_HelpSecure($selectedChatID); ?>" data-message-id="<?php echo iN_HelpSecure($messageId); ?>">
                                                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['delete']); ?>
                                                </div>
                                            <?php } else { ?>
                                                -
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    <?php } else {
                        echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . $iN->iN_SelectedMenuIcon('54') . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['chat_no_messages_found'] ?? ($LANG['messages'] . ' - 0')) . '</div></div>';
                    } ?>
                <?php } ?>
            <?php } else {
                $params = [];
                $whereSql = '';
                if ($searchTerm !== '') {
                    $searchLike = '%' . $searchTerm . '%';
                    $whereSql = " WHERE (CAST(C.chat_id AS CHAR) LIKE ?
                                OR U1.i_username LIKE ? OR U1.i_user_fullname LIKE ?
                                OR U2.i_username LIKE ? OR U2.i_user_fullname LIKE ?)";
                    $params = [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike];
                }

                $totalConversations = (int)DB::col(
                    "SELECT COUNT(*)
                     FROM i_chat_users C
                     LEFT JOIN i_users U1 ON U1.iuid = C.user_one
                     LEFT JOIN i_users U2 ON U2.iuid = C.user_two" . $whereSql,
                    $params
                );
                $totalPages = $pageLimit > 0 ? (int)ceil($totalConversations / $pageLimit) : 1;
                if ($totalPages <= 0) {
                    $totalPages = 1;
                }
                if ($pagep > $totalPages) {
                    $pagep = $totalPages;
                }
                $startFrom = ($pagep - 1) * $pageLimit;

                $listSql = "SELECT C.chat_id, C.user_one, C.user_two, C.last_message_time,
                                   U1.iuid AS user_one_id, U1.i_username AS user_one_username, U1.i_user_fullname AS user_one_fullname,
                                   U2.iuid AS user_two_id, U2.i_username AS user_two_username, U2.i_user_fullname AS user_two_fullname,
                                   LM.con_id AS last_con_id, LM.message AS message, LM.file AS file, LM.sticker_url AS sticker_url,
                                   LM.gifurl AS gifurl, LM.private_price AS private_price, LM.private_status AS private_status,
                                   LM.time AS last_message_at,
                                   (SELECT COUNT(*) FROM i_chat_conversations CC WHERE CC.chat_id_fk = C.chat_id) AS total_message_count
                            FROM i_chat_users C
                            LEFT JOIN i_users U1 ON U1.iuid = C.user_one
                            LEFT JOIN i_users U2 ON U2.iuid = C.user_two
                            LEFT JOIN i_chat_conversations LM ON LM.con_id = (
                                SELECT MAX(LMX.con_id) FROM i_chat_conversations LMX WHERE LMX.chat_id_fk = C.chat_id
                            )" . $whereSql . "
                            ORDER BY COALESCE(LM.con_id, 0) DESC, C.chat_id DESC
                            LIMIT {$startFrom}, {$pageLimit}";
                $conversationRows = DB::all($listSql, $params);
                ?>

                <div class="i_general_title_box">
                    <?php echo iN_HelpSecure(($LANG['chat_conversations'] ?? $LANG['messages']) . ' (' . $totalConversations . ')'); ?>
                </div>

                <div class="i_contents_section flex_ manage_margin_bottom">
                    <form method="get" action="<?php echo iN_HelpSecure($base_url); ?>admin/manage_chats" class="flex_ tabing_non_justify" style="width:100%;gap:10px;">
                        <div class="irow_box_right irow_box_right_style" style="max-width:460px;">
                            <div class="rec_not rec_not_style"><?php echo iN_HelpSecure($LANG['search']); ?></div>
                            <input type="text" class="i_input flex_" name="sr" value="<?php echo iN_HelpSecure($searchTerm); ?>">
                        </div>
                        <div class="irow_box_right flex_ tabing irow_box_right_styl">
                            <button type="submit" class="i_nex_btn_btn"><?php echo iN_HelpSecure($LANG['search']); ?></button>
                        </div>
                    </form>
                </div>

                <div class="warning_"><?php echo iN_HelpSecure($LANG['noway_desc']); ?></div>

                <?php if ($conversationRows) { ?>
                    <div class="i_overflow_x_auto">
                        <table class="border_one">
                            <tr>
                                <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                                <th><?php echo iN_HelpSecure($LANG['chat_participants'] ?? $LANG['user']); ?></th>
                                <th><?php echo iN_HelpSecure($LANG['chat_last_message'] ?? $LANG['messages']); ?></th>
                                <th><?php echo iN_HelpSecure($LANG['messages']); ?></th>
                                <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                                <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                            </tr>
                            <?php foreach ($conversationRows as $row) {
                                $chatID = (int)($row['chat_id'] ?? 0);
                                $participantOne = $buildParticipant($row, 'user_one');
                                $participantTwo = $buildParticipant($row, 'user_two');
                                $chatUrl = iN_HelpSecure($base_url) . 'admin/manage_chats?chat=' . $chatID;
                                if ($searchTerm !== '') {
                                    $chatUrl .= '&sr=' . rawurlencode($searchTerm);
                                }

                                $lastMessagePreview = $formatMessagePreview($row);
                                $lastMessageAttachments = $renderMessageAttachments((string)($row['file'] ?? ''), 2);
                                if ($lastMessagePreview === '' && $lastMessageAttachments === '') {
                                    $lastMessagePreview = '-';
                                }
                                $lastMessageAt = (int)($row['last_message_at'] ?? 0);
                                if ($lastMessageAt <= 0) {
                                    $lastMessageAt = (int)($row['last_message_time'] ?? 0);
                                }
                                $totalMessageCount = (int)($row['total_message_count'] ?? 0);
                                ?>
                                <tr class="transition trhover">
                                    <td><?php echo iN_HelpSecure($chatID); ?></td>
                                    <td class="see_post_details">
                                        <div class="manage_chats_participants">
                                            <a class="manage_chats_participant_item" href="<?php echo iN_HelpSecure($participantOne['profile_url']); ?>" target="_blank" rel="noopener noreferrer">
                                                <span class="manage_chats_participant_avatar"><img src="<?php echo iN_HelpSecure($participantOne['avatar_url']); ?>" alt="<?php echo iN_HelpSecure($participantOne['fullname']); ?>"></span>
                                                <span class="manage_chats_participant_meta">
                                                    <span class="manage_chats_participant_name" title="<?php echo iN_HelpSecure($participantOne['fullname']); ?>"><?php echo iN_HelpSecure($participantDisplayName($participantOne)); ?></span>
                                                    <span class="manage_chats_participant_handle" title="<?php echo iN_HelpSecure($participantHandle($participantOne)); ?>"><?php echo iN_HelpSecure($participantDisplayHandle($participantOne)); ?></span>
                                                </span>
                                            </a>
                                            <span class="manage_chats_participant_separator">&harr;</span>
                                            <a class="manage_chats_participant_item" href="<?php echo iN_HelpSecure($participantTwo['profile_url']); ?>" target="_blank" rel="noopener noreferrer">
                                                <span class="manage_chats_participant_avatar"><img src="<?php echo iN_HelpSecure($participantTwo['avatar_url']); ?>" alt="<?php echo iN_HelpSecure($participantTwo['fullname']); ?>"></span>
                                                <span class="manage_chats_participant_meta">
                                                    <span class="manage_chats_participant_name" title="<?php echo iN_HelpSecure($participantTwo['fullname']); ?>"><?php echo iN_HelpSecure($participantDisplayName($participantTwo)); ?></span>
                                                    <span class="manage_chats_participant_handle" title="<?php echo iN_HelpSecure($participantHandle($participantTwo)); ?>"><?php echo iN_HelpSecure($participantDisplayHandle($participantTwo)); ?></span>
                                                </span>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="see_post_details">
                                        <div class="manage_chats_message_cell">
                                            <?php if ($lastMessagePreview !== '') { ?>
                                                <div class="sim"><?php echo iN_HelpSecure($lastMessagePreview); ?></div>
                                            <?php } ?>
                                            <?php if ($lastMessageAttachments !== '') {
                                                echo $lastMessageAttachments;
                                            } ?>
                                        </div>
                                    </td>
                                    <td class="see_post_details"><div class="sim"><?php echo iN_HelpSecure($totalMessageCount); ?></div></td>
                                    <td class="see_post_details"><div class="sim"><?php echo iN_HelpSecure($renderAgo($lastMessageAt)); ?></div></td>
                                    <td class="flex_ tabing_non_justify">
                                        <div class="seePost c2 border_one transition tabing flex_">
                                            <a class="tabing flex_" href="<?php echo iN_HelpSecure($chatUrl); ?>">
                                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10')) . iN_HelpSecure($LANG['chat_open_conversation'] ?? $LANG['chat']); ?>
                                            </a>
                                        </div>
                                        <?php if ($canDeleteChats) { ?>
                                            <div class="delu border_one transition tabing flex_ delete_admin_chat" data-chat-id="<?php echo iN_HelpSecure($chatID); ?>">
                                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')) . iN_HelpSecure($LANG['delete']); ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                <?php } else {
                    echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . $iN->iN_SelectedMenuIcon('54') . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['chat_no_conversations_found'] ?? $LANG['messages']) . '</div></div>';
                } ?>

                <div class="i_become_creator_box_footer tabing">
                    <?php if ($totalPages > 1) { ?>
                        <ul class="pagination">
                            <?php if ($pagep > 1) {
                                $prevUrl = iN_HelpSecure($base_url, FILTER_VALIDATE_URL) . 'admin/manage_chats?page-id=' . ($pagep - 1);
                                if ($searchTerm !== '') {
                                    $prevUrl .= '&sr=' . rawurlencode($searchTerm);
                                }
                                ?>
                                <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($prevUrl); ?>"><?php echo iN_HelpSecure($LANG['preview_page']); ?></a></li>
                            <?php } ?>

                            <li class="currentpage active">
                                <a class="transition" href="<?php echo iN_HelpSecure($base_url, FILTER_VALIDATE_URL) . 'admin/manage_chats?page-id=' . $pagep . ($searchTerm !== '' ? '&sr=' . rawurlencode($searchTerm) : ''); ?>">
                                    <?php echo iN_HelpSecure($pagep); ?>
                                </a>
                            </li>

                            <?php if ($pagep < $totalPages) {
                                $nextUrl = iN_HelpSecure($base_url, FILTER_VALIDATE_URL) . 'admin/manage_chats?page-id=' . ($pagep + 1);
                                if ($searchTerm !== '') {
                                    $nextUrl .= '&sr=' . rawurlencode($searchTerm);
                                }
                                ?>
                                <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($nextUrl); ?>"><?php echo iN_HelpSecure($LANG['next_page']); ?></a></li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
