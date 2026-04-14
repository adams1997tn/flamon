<?php
$totalInvoices = $iN->iN_TotalAdminInvoices($userID);
if (isset($_GET["page-id"])) {
    $pagep  = (int)$_GET["page-id"];
    if ($pagep <= 0) { $pagep = 1; }
} else {
    $pagep = 1;
}
$invoiceList = $iN->iN_AdminInvoices($userID, $paginationLimit, $pagep);
$currencySymbol = isset($currencys[$defaultCurrency]) ? $currencys[$defaultCurrency] : '$';
?>
<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['manage_invoices']); ?> (<?php echo (int)$totalInvoices; ?>)
        </div>
        <div class="i_general_row_box column flex_ white_board_padding_" id="general_conf">
            <?php if ($invoiceList) { ?>
                <div class="i_overflow_x_auto">
                    <table class="border_one">
                        <tr>
                            <th><?php echo iN_HelpSecure($LANG['id']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['user']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['payment_method']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['amount']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['invoice_tax']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['invoice_number']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['date']); ?></th>
                            <th><?php echo iN_HelpSecure($LANG['actions']); ?></th>
                        </tr>
                        <?php
                        foreach ($invoiceList as $inv) {
                            $invoiceId = $inv['payment_id'] ?? null;
                            $payerId = $inv['payer_iuid_fk'] ?? null;
                            $payerName = $inv['i_user_fullname'] ?? $inv['i_username'];
                            $payerUsername = $inv['i_username'] ?? '';
                            $payerAvatar = $iN->iN_UserAvatar($payerId, $base_url);
                            $gateway = $inv['payment_option'] ?? '';
                            $amount = isset($inv['amount']) ? (float)$inv['amount'] : 0;
                            $taxAmount = isset($inv['tax_amount']) ? (float)$inv['tax_amount'] : 0;
                            $invoiceNumber = $inv['invoice_number'] ?? '-';
                            $invoiceToken = $inv['invoice_token'] ?? '';
                            $paymentTime = isset($inv['payment_time']) ? gmdate("d/m/Y H:i", $inv['payment_time']) : '-';
                            $currency = $inv['currency'] ?? $defaultCurrency;
                            $symbol = $currencys[$currency] ?? $currencySymbol;
                        ?>
                        <tr class="transition trhover">
                            <td><?php echo iN_HelpSecure($invoiceId); ?></td>
                            <td>
                                <div class="t_od flex_ c6">
                                    <div class="t_owner_avatar border_two tabing flex_">
                                        <img src="<?php echo iN_HelpSecure($payerAvatar); ?>" loading="lazy" alt="avatar">
                                    </div>
                                    <div class="t_owner_user tabing flex_">
                                        <a class="truncated" href="<?php echo iN_HelpSecure($base_url . $payerUsername); ?>" target="_blank">
                                            <?php echo iN_HelpSecure($payerName); ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure(strtoupper($gateway)); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($symbol) . number_format($amount, 2); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($symbol) . number_format($taxAmount, 2); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($invoiceNumber); ?></div>
                            </td>
                            <td class="see_post_details">
                                <div class="flex_ tabing_non_justify"><?php echo iN_HelpSecure($paymentTime); ?></div>
                            </td>
                            <td class="see_post_details">
                                <?php if ($invoiceToken) { ?>
                                    <a class="transition invoice_download_btn" target="_blank" href="<?php echo iN_HelpSecure($base_url); ?>invoice?token=<?php echo iN_HelpSecure($invoiceToken); ?>">
                                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('170')) . $LANG['download_invoice']; ?>
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
                echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">' . html_entity_decode($iN->iN_SelectedMenuIcon('54')) . '</div><div class="n_c_t">' . iN_HelpSecure($LANG['nothing_to_show_about_payment_history']) . '</div></div>';
            } ?>
            <div class="i_become_creator_box_footer tabing">
                <?php if (ceil($totalInvoices / $paginationLimit) > 0): ?>
                    <ul class="pagination">
                        <?php if ($pagep > 1): ?>
                        <li class="prev"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep)-1 ?>"><?php echo iN_HelpSecure($LANG['preview_page']);?></a></li>
                        <?php endif; ?>

                        <?php if ($pagep > 3): ?>
                        <li class="start"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=1">1</a></li>
                        <li class="dots">...</li>
                        <?php endif; ?>

                        <?php if ($pagep-2 > 0): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep)-2; ?>"><?php echo iN_HelpSecure($pagep)-2; ?></a></li><?php endif; ?>
                        <?php if ($pagep-1 > 0): ?><li class="page"><a href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep)-1; ?>"><?php echo iN_HelpSecure($pagep)-1; ?></a></li><?php endif; ?>

                        <li class="currentpage active"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep); ?>"><?php echo iN_HelpSecure($pagep); ?></a></li>

                        <?php if ($pagep+1 < ceil($totalInvoices / $paginationLimit)+1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep)+1; ?>"><?php echo iN_HelpSecure($pagep)+1; ?></a></li><?php endif; ?>
                        <?php if ($pagep+2 < ceil($totalInvoices / $paginationLimit)+1): ?><li class="page"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep)+2; ?>"><?php echo iN_HelpSecure($pagep)+2; ?></a></li><?php endif; ?>

                        <?php if ($pagep < ceil($totalInvoices / $paginationLimit)-2): ?>
                        <li class="dots">...</li>
                        <li class="end"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo ceil($totalInvoices / $paginationLimit); ?>"><?php echo ceil($totalInvoices / $paginationLimit); ?></a></li>
                        <?php endif; ?>

                        <?php if ($pagep < ceil($totalInvoices / $paginationLimit)): ?>
                        <li class="next"><a class="transition" href="<?php echo iN_HelpSecure($base_url);?>admin/invoices?page-id=<?php echo iN_HelpSecure($pagep)+1; ?>"><?php echo iN_HelpSecure($LANG['next_page']);?></a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
