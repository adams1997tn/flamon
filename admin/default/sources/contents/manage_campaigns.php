<?php
if ($logedIn != '1' || $userType != '2') {
    exit('You do not have permission to access this page.');
}

$pendingCampaigns = $iN->iN_GetCampaignsByStatus('pending', 50);
$approvedCampaigns = $iN->iN_GetCampaignsByStatus('active', 50);
$rejectedCampaigns = $iN->iN_GetCampaignsByStatus('rejected', 50);
$currencySign = $currencys[$defaultCurrency] ?? '';
$pendingCount = count($pendingCampaigns);
$approvedCount = count($approvedCampaigns);
$rejectedCount = count($rejectedCampaigns);

$campaignRows = array();
foreach ($pendingCampaigns as $item) {
    $item['__status'] = 'pending';
    $campaignRows[] = $item;
}
foreach ($approvedCampaigns as $item) {
    $item['__status'] = 'approved';
    $campaignRows[] = $item;
}
foreach ($rejectedCampaigns as $item) {
    $item['__status'] = 'rejected';
    $campaignRows[] = $item;
}
$totalCampaigns = count($campaignRows);
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['campaign_settings']) . ' (' . (int) $totalCampaigns . ')'; ?>
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <div class="warning_">
                <?php echo iN_HelpSecure($LANG['campaign_settings_desc']); ?>
            </div>
            <div class="i_contents_section flex_ tabing manage_margin_bottom">
                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c1">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73')) . iN_HelpSecure($LANG['campaign_admin_pending']); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num" id="campaignPendingCount"><?php echo iN_HelpSecure($pendingCount); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c3">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')) . iN_HelpSecure($LANG['campaign_admin_approved']); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num" id="campaignApprovedCount"><?php echo iN_HelpSecure($approvedCount); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c4">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('129')) . iN_HelpSecure($LANG['campaign_admin_rejected']); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num" id="campaignRejectedCount"><?php echo iN_HelpSecure($rejectedCount); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row_wrapper">
                    <div class="row_item flex_ column border_one c2">
                        <div class="chart_row_box_title flex_ tabing_non_justify">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('121')) . iN_HelpSecure($LANG['campaign_admin_total'] ?? 'Total campaigns'); ?>
                        </div>
                        <div class="chart_row_box_sum">
                            <span class="count-num" id="campaignTotalCount"><?php echo iN_HelpSecure($totalCampaigns); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="campaign_filter_row flex_ tabing_non_justify">
                <div class="campaign_filter_label">
                    <?php echo iN_HelpSecure($LANG['status']); ?>
                </div>
                <div class="campaign_filter_select">
                    <select id="campaignStatusFilter">
                        <option value="all"><?php echo iN_HelpSecure($LANG['campaign_filter_all'] ?? 'All'); ?></option>
                        <option value="pending"><?php echo iN_HelpSecure($LANG['campaign_admin_pending']); ?></option>
                        <option value="approved"><?php echo iN_HelpSecure($LANG['campaign_admin_approved']); ?></option>
                        <option value="rejected"><?php echo iN_HelpSecure($LANG['campaign_admin_rejected']); ?></option>
                    </select>
                </div>
                <div class="campaign_filter_count border_one" id="campaignVisibleCount">
                    <?php echo (int) $totalCampaigns; ?>
                </div>
            </div>

            <div class="campaign_section_box column flex_">
                <?php if (!empty($campaignRows)) { ?>
                    <div class="campaign_table_holder">
                        <div class="campaign_empty flex_ tabing hidden" id="campaignEmptyState">
                            <div class="no_c_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?></div>
                            <div class="n_c_t">
                                <?php echo iN_HelpSecure($LANG['campaign_admin_no_pending'] ?? 'No campaigns found.'); ?>
                            </div>
                        </div>
                        <div class="i_overflow_x_auto">
                            <table class="border_one" id="campaignTable">
                                <thead>
                                <tr>
                                    <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['campaign_label_title'] ?? $LANG['title']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['username']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['campaign_card_goal']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['campaign_card_deadline']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['status']); ?></th>
                                    <th><?php echo iN_HelpSecure($LANG['approve_or_decline'] ?? $LANG['action']); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($campaignRows as $item) {
                                    $campaignId = isset($item['campaign_id']) ? (int) $item['campaign_id'] : 0;
                                    $postId = isset($item['post_id_fk']) ? (int) $item['post_id_fk'] : 0;
                                    $titleText = $item['title'] ?? ($item['summary'] ?? '—');
                                    $ownerName = $item['i_username'] ?? '';
                                    $ownerId = isset($item['owner_uid_fk']) ? (int) $item['owner_uid_fk'] : 0;
                                    $goalAmount = isset($item['goal_amount']) ? (float) $item['goal_amount'] : 0;
                                    $deadlineTs = isset($item['deadline_at']) ? (int) $item['deadline_at'] : 0;
                                    $deadlineText = $deadlineTs ? date('Y-m-d H:i', $deadlineTs) : '—';
                                    $ownerAvatar = $iN->iN_UserAvatar($ownerId, $base_url);
                                    $statusRaw = $item['__status'] ?? 'pending';
                                    ?>
                                    <tr data-campaign="<?php echo iN_HelpSecure($campaignId); ?>" data-status="<?php echo iN_HelpSecure($statusRaw); ?>">
                                        <td><?php echo iN_HelpSecure($campaignId); ?></td>
                                        <td><?php echo iN_HelpSecure($titleText); ?></td>
                                        <td>
                                            <div class="t_od flex_ c6">
                                                <div class="t_owner_avatar border_two tabing flex_">
                                                    <img src="<?php echo iN_HelpSecure($ownerAvatar); ?>" alt="Avatar">
                                                </div>
                                                <div class="t_owner_user tabing flex_">
                                                    <a class="truncated" href="<?php echo iN_HelpSecure($base_url) . iN_HelpSecure($ownerName); ?>">
                                                        <?php echo iN_HelpSecure($ownerName); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo iN_HelpSecure(number_format($goalAmount, 2) . $currencySign); ?></td>
                                        <td><?php echo iN_HelpSecure($deadlineText); ?></td>
                                        <td>
                                            <span class="campaign_status_tag status_<?php echo iN_HelpSecure($statusRaw); ?>">
                                                <?php echo iN_HelpSecure($LANG['campaign_admin_' . $statusRaw] ?? ucfirst($statusRaw)); ?>
                                            </span>
                                        </td>
                                        <td class="c_actions flex_ tabing_non_justify">
                                            <a class="campaignEditBtn transition" href="<?php echo iN_HelpSecure($base_url) . 'admin/allPosts?post=' . iN_HelpSecure($postId); ?>">
                                                <?php echo iN_HelpSecure($LANG['edit_post']); ?>
                                            </a>
                                            <button type="button" class="campaignActionBtn approve transition" data-status="active" data-id="<?php echo iN_HelpSecure($campaignId); ?>">
                                                <?php echo iN_HelpSecure($LANG['campaign_admin_approve']); ?>
                                            </button>
                                            <button type="button" class="campaignActionBtn reject transition" data-status="rejected" data-id="<?php echo iN_HelpSecure($campaignId); ?>">
                                                <?php echo iN_HelpSecure($LANG['campaign_admin_reject']); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="no_creator_f_wrap flex_ tabing">
                        <div class="no_c_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('54')); ?></div>
                        <div class="n_c_t">
                            <?php echo iN_HelpSecure($LANG['campaign_admin_no_pending'] ?? 'No campaigns found.'); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var csrfInput = document.querySelector('input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        var statusLabels = {
            pending: "<?php echo iN_HelpSecure($LANG['campaign_admin_pending']); ?>",
            approved: "<?php echo iN_HelpSecure($LANG['campaign_admin_approved']); ?>",
            rejected: "<?php echo iN_HelpSecure($LANG['campaign_admin_rejected']); ?>",
            active: "<?php echo iN_HelpSecure($LANG['campaign_admin_approved']); ?>"
        };

        function applyFilter(key) {
            var table = document.getElementById('campaignTable');
            var empty = document.getElementById('campaignEmptyState');
            var countBox = document.getElementById('campaignVisibleCount');
            if (!table) {
                return;
            }
            var rows = table.querySelectorAll('tbody tr');
            var visible = 0;
            rows.forEach(function(row) {
                var status = row.getAttribute('data-status');
                if (key === 'all' || status === key) {
                    row.classList.remove('campaign_row_hidden');
                    visible++;
                } else {
                    row.classList.add('campaign_row_hidden');
                }
            });
            if (countBox) {
                countBox.textContent = visible;
            }
            if (empty) {
                if (visible === 0) {
                    empty.classList.remove('hidden');
                } else {
                    empty.classList.add('hidden');
                }
            }
            refreshStats();
        }

        function refreshStats() {
            var table = document.getElementById('campaignTable');
            if (!table) {
                return;
            }
            var counts = {pending: 0, approved: 0, rejected: 0, total: 0};
            table.querySelectorAll('tbody tr').forEach(function(row) {
                var status = row.getAttribute('data-status');
                if (status === 'pending') { counts.pending++; }
                if (status === 'approved' || status === 'active') { counts.approved++; }
                if (status === 'rejected') { counts.rejected++; }
                counts.total++;
            });
            var pendingEl = document.getElementById('campaignPendingCount');
            var approvedEl = document.getElementById('campaignApprovedCount');
            var rejectedEl = document.getElementById('campaignRejectedCount');
            var totalEl = document.getElementById('campaignTotalCount');
            if (pendingEl) { pendingEl.textContent = counts.pending; }
            if (approvedEl) { approvedEl.textContent = counts.approved; }
            if (rejectedEl) { rejectedEl.textContent = counts.rejected; }
            if (totalEl) { totalEl.textContent = counts.total; }
        }

        function updateRowStatus(row, newStatus) {
            if (!row) { return; }
            var mapped = newStatus === 'active' ? 'approved' : newStatus;
            row.setAttribute('data-status', mapped);
            var tag = row.querySelector('.campaign_status_tag');
            if (tag) {
                tag.className = 'campaign_status_tag status_' + mapped;
                tag.textContent = statusLabels[mapped] || mapped;
            }
        }

        var statusFilter = document.getElementById('campaignStatusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                var key = this.value;
                applyFilter(key);
            });
            applyFilter(statusFilter.value || 'all');
        }

        document.addEventListener('click', function(event) {
            var actionBtn = event.target.closest('.campaignActionBtn');
            if (!actionBtn) {
                return;
            }
            var status = actionBtn.getAttribute('data-status');
            var campaignId = actionBtn.getAttribute('data-id');
            if (!status || !campaignId) {
                return;
            }
            var row = actionBtn.closest('tr');
            var section = actionBtn.closest('.campaign_section_box');
            actionBtn.disabled = true;
            actionBtn.classList.add('loading');

            var payload = new URLSearchParams();
            payload.append('f', 'updateCampaignStatus');
            payload.append('campaign_id', campaignId);
            payload.append('status', status);
            if (csrfToken) {
                payload.append('csrf_token', csrfToken);
            }

            fetch(siteurl + 'request/request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                credentials: 'same-origin',
                body: payload.toString()
            }).then(function(response) {
                if (!response.ok) {
                    throw new Error('Network error');
                }
                return response.json();
            }).then(function(resp) {
                if (resp && resp.status === 'ok') {
                    if (row) {
                        updateRowStatus(row, status);
                    }
                    applyFilter(statusFilter ? statusFilter.value : 'all');
                } else {
                    actionBtn.disabled = false;
                    actionBtn.classList.remove('loading');
                }
            }).catch(function() {
                actionBtn.disabled = false;
                actionBtn.classList.remove('loading');
            });
        });
    })();
</script>
