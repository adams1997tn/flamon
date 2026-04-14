<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($LANG['invoice'] ?? 'Invoice'); ?> - <?php echo iN_HelpSecure($siteTitle); ?></title>
    <style>
        body {
            font-family: "Segoe UI", Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background: #f5f6fb;
            color: #1f2025;
        }
        .invoice_wrapper {
            max-width: 840px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(15,24,44,0.08);
        }
        .invoice_header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
            border-bottom: 1px solid #e3e7f2;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }
        .invoice_title {
            font-size: 28px;
            margin: 0;
        }
        .invoice_meta {
            text-align: right;
            font-size: 14px;
            color: #6b7280;
        }
        .invoice_section {
            margin-bottom: 30px;
        }
        .invoice_section h4 {
            margin: 0 0 8px 0;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
        }
        .invoice_section p {
            margin: 0;
            font-size: 15px;
            color: #1f2937;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table thead tr {
            background: #f8f9fb;
        }
        table th, table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eceff5;
        }
        .totals {
            margin-left: auto;
            max-width: 360px;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 15px;
        }
        .totals div.total {
            font-size: 18px;
            font-weight: 600;
        }
        .invoice_actions {
            text-align: right;
            margin-top: 25px;
        }
        .invoice_actions button {
            border: none;
            background: #468cef;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice_wrapper { box-shadow: none; border: none; }
            .invoice_actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice_wrapper">
        <div class="invoice_header">
            <div>
                <h1 class="invoice_title"><?php echo iN_HelpSecure($LANG['invoice'] ?? 'Invoice'); ?></h1>
                <p><?php echo iN_HelpSecure($siteName); ?></p>
            </div>
            <div class="invoice_meta">
                <div><strong><?php echo iN_HelpSecure($LANG['invoice_number'] ?? 'Invoice No'); ?>:</strong> <?php echo iN_HelpSecure($invoiceNumber); ?></div>
                <div><strong><?php echo iN_HelpSecure($LANG['date'] ?? 'Date'); ?>:</strong> <?php echo iN_HelpSecure($invoiceDate); ?></div>
            </div>
        </div>

        <div class="invoice_section">
            <h4><?php echo iN_HelpSecure($LANG['seller_details'] ?? 'Seller'); ?></h4>
            <p>
                <?php echo iN_HelpSecure($taxSettings['company_name'] ?? $siteName); ?><br>
                <?php if (!empty($taxSettings['company_address'])) { echo nl2br(iN_HelpSecure($taxSettings['company_address'])) . '<br>'; } ?>
                <?php if (!empty($taxSettings['registration_number'])) { echo iN_HelpSecure($taxSettings['registration_number']); } ?>
            </p>
        </div>

        <div class="invoice_section">
            <h4><?php echo iN_HelpSecure($LANG['billing_details'] ?? 'Billing to'); ?></h4>
            <p>
                <?php echo iN_HelpSecure($billingName); ?><br>
                <?php echo iN_HelpSecure($billingEmail); ?><br>
                <?php if (!empty($billingAddress)) { echo nl2br(iN_HelpSecure($billingAddress)); } ?>
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th><?php echo iN_HelpSecure($LANG['pay_type'] ?? 'Type'); ?></th>
                    <th><?php echo iN_HelpSecure($LANG['payment_method'] ?? 'Method'); ?></th>
                    <th><?php echo iN_HelpSecure($LANG['amount'] ?? 'Amount'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo iN_HelpSecure($PAYMENTTYPES[$invoiceData['payment_type']] ?? ucfirst($invoiceData['payment_type'])); ?></td>
                    <td><?php echo iN_HelpSecure(strtoupper($invoiceData['payment_option'] ?? '-')); ?></td>
                    <td><?php echo iN_HelpSecure(formatCurrency($grossAmount, $currencyCode)); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div>
                <span><?php echo iN_HelpSecure($LANG['subtotal'] ?? 'Subtotal'); ?></span>
                <span><?php echo iN_HelpSecure(formatCurrency($netAmount, $currencyCode)); ?></span>
            </div>
            <div>
                <span><?php echo iN_HelpSecure(($taxSettings['label'] ?? 'Tax') . ' (' . ($invoiceData['tax_rate'] ?? 0) . '%)'); ?></span>
                <span><?php echo iN_HelpSecure(formatCurrency($taxAmount, $currencyCode)); ?></span>
            </div>
            <div class="total">
                <span><?php echo iN_HelpSecure($LANG['total'] ?? 'Total'); ?></span>
                <span><?php echo iN_HelpSecure(formatCurrency($grossAmount, $currencyCode)); ?></span>
            </div>
        </div>

        <div class="invoice_actions">
            <button type="button" onclick="window.print();"><?php echo iN_HelpSecure($LANG['print_invoice'] ?? 'Print Invoice'); ?></button>
        </div>
    </div>
</body>
</html>
