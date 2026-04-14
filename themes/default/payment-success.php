<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle);?></title>
    <?php
       include("layouts/header/meta.php");
       include("layouts/header/css.php");
       include("layouts/header/javascripts.php");
    ?>
</head>
<body>
<?php if($logedIn == 0){ include('layouts/login_form.php'); }?>
<?php include("layouts/header/header.php");?>
    <div class="wrapper">
           <?php include("layouts/left_menu.php"); ?>
              <div class="th_middle">
                 <div class="pageMiddle flex_ column payment_successfully">
                     <div class="success_icon">
                        <?php echo isset($successIcon) ? $successIcon : html_entity_decode($iN->iN_SelectedMenuIcon('131'));?>
                     </div>
	                     <div class="payment_not"><?php echo iN_HelpSecure($successMessage ?? $LANG['your_payment_successfull']);?></div>
	                     <?php if (!empty($successDescription)) { ?>
	                        <div class="payment_desc"><?php echo iN_HelpSecure($successDescription);?></div>
	                     <?php } ?>
	                     <?php if (!empty($paymentSummaryItems) && is_array($paymentSummaryItems)) { ?>
	                        <div class="payment_summary_block">
	                            <div class="payment_desc"><?php echo iN_HelpSecure($LANG['payment_details']); ?></div>
	                            <?php foreach ($paymentSummaryItems as $summaryItem) {
	                                $summaryLabel = isset($summaryItem['label']) ? (string)$summaryItem['label'] : '';
	                                $summaryValue = isset($summaryItem['value']) ? (string)$summaryItem['value'] : '';
	                                if ($summaryLabel === '' || $summaryValue === '') {
	                                    continue;
	                                }
	                            ?>
	                                <div class="payment_desc">
	                                    <strong><?php echo iN_HelpSecure($summaryLabel);?>:</strong>
	                                    <?php echo iN_HelpSecure($summaryValue);?>
	                                </div>
	                            <?php } ?>
	                        </div>
	                     <?php } ?>
		                     <?php if (!empty($invoiceToken)) { ?>
		                        <a class="invoice_download_btn transition" target="_blank" href="<?php echo iN_HelpSecure($base_url).'invoice?token='.iN_HelpSecure($invoiceToken);?>">
		                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('33')).iN_HelpSecure($LANG['download_invoice']);?>
	                        </a>
	                     <?php } ?>
	                     <?php if (!empty($tipPaymentData)) { ?>
	                        <div class="tip_payment_result"
                             data-order-id="<?php echo iN_HelpSecure($tipPaymentData['order_id']); ?>"
                             data-payed-user-id="<?php echo iN_HelpSecure($tipPaymentData['payed_user_id']); ?>"
                             data-payed-post-id="<?php echo iN_HelpSecure($tipPaymentData['payed_post_id']); ?>"
                             data-payment-type="<?php echo iN_HelpSecure($tipPaymentData['payment_type'] ?? 'tips'); ?>"></div>
                     <?php } ?>
                 </div>
              </div>
           <?php include("layouts/page_right.php"); ?>
    </div>
</body>
</html>
