<div class="i_modal_bg_in">
    <?php
    $withdrawUserId = (int)($wDet['iuid_fk'] ?? 0);
    $withdrawAmount = (string)($wDet['amount'] ?? '0');
    $withdrawMethod = (string)($wDet['method'] ?? '');
    $withdrawPayoutTime = (int)($wDet['payout_time'] ?? 0);
    $withdrawStatus = (string)($wDet['status'] ?? '');
    $withdrawPaidTime = (int)($wDet['paid_time'] ?? 0);
    $withdrawUsername = (string)($wDetUserData['i_username'] ?? '');
    $withdrawFullName = (string)($wDetUserData['i_user_fullname'] ?? $withdrawUsername);

    $withdrawMethodLabels = array(
        'paypal' => $LANG['paypal'],
        'bank' => $LANG['bank_transfer'],
        'payoneer' => $LANG['payoneer'],
        'zelle' => $LANG['zelle'],
        'western-union' => $LANG['western_union'],
        'bitcoin' => $LANG['bitcoin_payout'],
        'mercadopago' => $LANG['mercadopago_payout']
    );
    $withdrawMethodLabel = isset($withdrawMethodLabels[$withdrawMethod]) ? $withdrawMethodLabels[$withdrawMethod] : $withdrawMethod;

    $addressDataRaw = isset($wDetFull['payment_address_data']) ? (string)$wDetFull['payment_address_data'] : '';
    $addressData = array();
    if ($addressDataRaw !== '') {
        $decodedAddressData = json_decode($addressDataRaw, true);
        if (is_array($decodedAddressData)) {
            $addressData = $decodedAddressData;
        }
    }
    $paymentAddressRows = array();
    if ($withdrawMethod === 'paypal') {
        $paypalAddress = isset($addressData['paypal_email']) ? (string)$addressData['paypal_email'] : (string)($wDetUserData['paypal_email'] ?? '');
        if ($paypalAddress !== '') {
            $paymentAddressRows[] = array($LANG['paypal_email'], $paypalAddress);
        }
    } elseif ($withdrawMethod === 'bank') {
        $bankFieldMap = array(
            'bank_country' => 'Bank Country',
            'iban_number' => 'IBAN',
            'routing_number' => 'Routing / SWIFT / BIC',
            'account_number' => 'Account Number',
            'account_holder_name' => 'Account Holder',
            'street_address' => 'Street Address',
            'bank_address_city' => 'City',
            'bank_address_state' => 'State',
            'postal_code' => 'Postal Code',
            'bank_address_country' => 'Country',
        );
        foreach ($bankFieldMap as $bfKey => $bfLabel) {
            $bfVal = isset($addressData[$bfKey]) ? (string)$addressData[$bfKey] : (string)($wDetUserData[$bfKey] ?? '');
            if (trim($bfVal) !== '') {
                $paymentAddressRows[] = array($bfLabel, $bfVal);
            }
        }
        $bfPhoneCode = isset($addressData['phone_country_code']) ? (string)$addressData['phone_country_code'] : (string)($wDetUserData['phone_country_code'] ?? '');
        $bfPhone = isset($addressData['phone_number']) ? (string)$addressData['phone_number'] : (string)($wDetUserData['phone_number'] ?? '');
        if (trim($bfPhone) !== '') {
            $paymentAddressRows[] = array('Phone', trim($bfPhoneCode . ' ' . $bfPhone));
        }
        // Legacy summary fallback (kept for old records)
        $bankAddress = isset($addressData['bank_account']) ? (string)$addressData['bank_account'] : (string)($wDetUserData['bank_account'] ?? '');
        if (empty($paymentAddressRows) && $bankAddress !== '') {
            $paymentAddressRows[] = array($LANG['payout_details'], $bankAddress);
        }
    } elseif ($withdrawMethod === 'payoneer') {
        $payoneerAddress = isset($addressData['payoneer_email']) ? (string)$addressData['payoneer_email'] : (string)($wDetUserData['payoneer_email'] ?? '');
        if ($payoneerAddress !== '') {
            $paymentAddressRows[] = array($LANG['email_payoneer'], $payoneerAddress);
        }
    } elseif ($withdrawMethod === 'zelle') {
        $zelleAddress = isset($addressData['zelle_email']) ? (string)$addressData['zelle_email'] : (string)($wDetUserData['zelle_email'] ?? '');
        if ($zelleAddress !== '') {
            $paymentAddressRows[] = array($LANG['email_zelle'], $zelleAddress);
        }
    } elseif ($withdrawMethod === 'western-union') {
        $wuFullName = isset($addressData['western_union_full_name']) ? (string)$addressData['western_union_full_name'] : (string)($wDetUserData['western_union_full_name'] ?? '');
        $wuDocumentId = isset($addressData['western_union_document_id']) ? (string)$addressData['western_union_document_id'] : (string)($wDetUserData['western_union_document_id'] ?? '');
        if ($wuFullName !== '') {
            $paymentAddressRows[] = array($LANG['full_name'], $wuFullName);
        }
        if ($wuDocumentId !== '') {
            $paymentAddressRows[] = array($LANG['document_id'], $wuDocumentId);
        }
    } elseif ($withdrawMethod === 'bitcoin') {
        $bitcoinAddress = isset($addressData['bitcoin_wallet_address']) ? (string)$addressData['bitcoin_wallet_address'] : (string)($wDetUserData['bitcoin_wallet_address'] ?? '');
        if ($bitcoinAddress !== '') {
            $paymentAddressRows[] = array($LANG['bitcoin_wallet'], $bitcoinAddress);
        }
    } elseif ($withdrawMethod === 'mercadopago') {
        $mpAlias = isset($addressData['mercadopago_alias']) ? (string)$addressData['mercadopago_alias'] : (string)($wDetUserData['mercadopago_alias'] ?? '');
        $mpCvu = isset($addressData['mercadopago_cvu']) ? (string)$addressData['mercadopago_cvu'] : (string)($wDetUserData['mercadopago_cvu'] ?? '');
        if ($mpAlias !== '') {
            $paymentAddressRows[] = array($LANG['alias_mp'], $mpAlias);
        }
        if ($mpCvu !== '') {
            $paymentAddressRows[] = array($LANG['nro_cvu'], $mpCvu);
        }
    }
    ?>
    <div class="i_modal_in_in">
        <div class="i_modal_content">
            <div class="i_modal_g_header">
                <?php echo iN_HelpSecure($LANG['withdrawal_details']); ?>
                <div class="shareClose transition"><?php echo html_entity_decode((string)$iN->iN_SelectedMenuIcon('5')); ?></div>
            </div>
            <div class="i_delete_post_description column positionRelative">
                <div class="purchase_post_details">
                    <div class="wallet-debit-confirm-container flex_">
                        <div class="owner_avatar" style="background-image: url(<?php echo $iN->iN_UserAvatar($withdrawUserId, $base_url); ?>);"></div>
                        <div class="wuser">
                            <a href="<?php echo $base_url . $withdrawUsername; ?>" target="_blank">
                                <?php echo iN_HelpSecure($withdrawFullName); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="withdraw_other_details border_one flex_ column tabing">
                    <div class="withdrawl_detail_box flex_">
                        <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($LANG['amount_requested']); ?></div>
                        <div class="withdrawl_detail_box_item_ f_bold"><?php echo iN_HelpSecure($withdrawAmount) . $currencys[$defaultCurrency]; ?></div>
                    </div>
                    <div class="withdrawl_detail_box flex_">
                        <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($LANG['payment_method']); ?></div>
                        <div class="withdrawl_detail_box_item_"><?php echo iN_HelpSecure($withdrawMethodLabel); ?></div>
                    </div>
                    <?php if (!empty($paymentAddressRows)) {
                        foreach ($paymentAddressRows as $addressRow) { ?>
                            <div class="withdrawl_detail_box flex_">
                                <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($addressRow[0]); ?></div>
                                <div class="withdrawl_detail_box_item_"><?php echo iN_HelpSecure($addressRow[1]); ?></div>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="withdrawl_detail_box flex_">
                            <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($LANG['payment_address']); ?></div>
                            <div class="withdrawl_detail_box_item_">-</div>
                        </div>
                    <?php } ?>
                    <div class="withdrawl_detail_box flex_">
                        <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($LANG['requested_date']); ?></div>
                        <div class="withdrawl_detail_box_item_"><?php echo $withdrawPayoutTime > 0 ? date('d/m/Y', $withdrawPayoutTime) : '-'; ?></div>
                    </div>
                    <div class="withdrawl_detail_box flex_">
                        <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($LANG['status']); ?></div>
                        <div class="withdrawl_detail_box_item_">
                            <?php
                            if ($withdrawStatus == 'pending') {
                                echo '<span class="tc1">' . $LANG['pending'] . '</span>';
                            } elseif ($withdrawStatus == 'payed') {
                                echo '<span class="tc2">' . $LANG['paid'] . '</span>';
                            } elseif ($withdrawStatus == 'declined') {
                                echo '<span class="tc3">' . $LANG['declined'] . '</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="withdrawl_detail_box flex_">
                        <div class="withdrawl_detail_box_item"><?php echo iN_HelpSecure($LANG['payment_date']); ?></div>
                        <div class="withdrawl_detail_box_item_">
                            <?php echo $withdrawPaidTime > 0 ? date('d/m/Y', $withdrawPaidTime) : '-'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="i_modal_g_footer flex_">
                <div class="alertBtnLeft no-del transition"><?php echo iN_HelpSecure($LANG['close']); ?></div>
            </div>
        </div>
    </div>
</div>
