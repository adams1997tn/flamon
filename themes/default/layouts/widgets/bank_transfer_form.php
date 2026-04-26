<?php
/**
 * Premium Bank Transfer payout form (shared partial).
 *
 * Expected context variables (all optional):
 *   $bankCountry, $ibanNumber, $routingNumber, $accountNumber,
 *   $accountHolderName, $phoneCountryCode, $phoneNumber,
 *   $streetAddress, $country, $state, $city, $postalCode
 *
 * The legacy free-text `bankAccount` column is still surfaced below the
 * structured fields for backward compatibility (hidden, auto-built on submit).
 */
if (!function_exists('iN_GetCountriesList')) {
    require_once __DIR__ . '/../../../../includes/countries_list.php';
}
$btCountries = iN_GetCountriesList();

$btValues = [
    'bank_country'         => (string)($bankCountry ?? ''),
    'iban_number'          => (string)($ibanNumber ?? ''),
    'routing_number'       => (string)($routingNumber ?? ''),
    'account_number'       => (string)($accountNumber ?? ''),
    'account_holder_name'  => (string)($accountHolderName ?? ''),
    'phone_country_code'   => (string)($phoneCountryCode ?? ''),
    'phone_number'         => (string)($phoneNumber ?? ''),
    'street_address'       => (string)($streetAddress ?? ''),
    'country'              => (string)($country ?? ''),
    'state'                => (string)($state ?? ''),
    'city'                 => (string)($city ?? ''),
    'postal_code'          => (string)($postalCode ?? ''),
];
foreach ($btValues as $k => $v) {
    $btValues[$k] = (string)$iN->iN_Secure($v);
}

$btLang = [
    'section_account'          => $LANG['bt_section_account']          ?? 'Bank Account Details',
    'section_holder'           => $LANG['bt_section_holder']           ?? 'Details Associated with Your Bank Account',
    'bank_country'             => $LANG['bt_bank_country']             ?? 'Bank Location / Bank Country',
    'iban_number'              => $LANG['bt_iban_number']              ?? 'IBAN Number',
    'iban_hint'                => $LANG['bt_iban_hint']                ?? 'If your country does not use IBAN, leave this field empty.',
    'routing_number'           => $LANG['bt_routing_number']           ?? 'Routing / SWIFT / BIC Number',
    'account_number'           => $LANG['bt_account_number']           ?? 'Account Number',
    'confirm_account_number'   => $LANG['bt_confirm_account_number']   ?? 'Confirm Account Number',
    'account_holder_name'      => $LANG['bt_account_holder_name']      ?? 'Account Holder Full Name',
    'phone_number'             => $LANG['bt_phone_number']             ?? 'Phone Number associated with your bank account',
    'street_address'           => $LANG['bt_street_address']           ?? 'Street Address',
    'country'                  => $LANG['bt_country']                  ?? 'Country',
    'state'                    => $LANG['bt_state']                    ?? 'State / Region',
    'city'                     => $LANG['bt_city']                     ?? 'City',
    'postal_code'              => $LANG['bt_postal_code']              ?? 'Postal Code',
    'search_country'           => $LANG['bt_search_country']           ?? 'Search country…',
    'iban_invalid'             => $LANG['bt_iban_invalid']             ?? 'IBAN format looks invalid.',
    'account_mismatch'         => $LANG['bt_account_mismatch']         ?? 'Account numbers do not match.',
    'required'                 => $LANG['bt_required']                 ?? 'This field is required.',
    'select_country'           => $LANG['bt_select_country']           ?? 'Select a country',
];
?>
<style>.bt-form .bt-req{display:none !important;}</style>
<div class="bt-form" data-bt-form>
  <input type="hidden" name="bank" id="bank_transfer" value="<?php echo iN_HelpSecure($bankAccount ?? ''); ?>">

  <div class="bt-section">
    <div class="bt-section-title"><?php echo iN_HelpSecure($btLang['section_account']); ?></div>
    <div class="bt-grid">

      <div class="bt-field bt-span-2">
        <label class="bt-label" for="bt_bank_country"><?php echo iN_HelpSecure($btLang['bank_country']); ?><span class="bt-req">*</span></label>
        <div class="bt-country-picker" data-picker="bank_country">
          <button type="button" class="bt-picker-btn" aria-haspopup="listbox" aria-expanded="false">
            <span class="bt-flag" data-flag></span>
            <span class="bt-picker-label" data-picker-label><?php echo iN_HelpSecure($btLang['select_country']); ?></span>
            <svg class="bt-caret" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5l4.5 4.5 4.5-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <input type="hidden" name="bank_country" id="bt_bank_country" value="<?php echo iN_HelpSecure($btValues['bank_country']); ?>">
        </div>
        <div class="bt-err" data-err-for="bank_country"></div>
      </div>

      <div class="bt-field bt-span-2">
        <label class="bt-label" for="bt_iban_number"><?php echo iN_HelpSecure($btLang['iban_number']); ?></label>
        <input type="text" id="bt_iban_number" name="iban_number" class="bt-input bt-mono" autocomplete="off" spellcheck="false" maxlength="42" data-iban value="<?php echo iN_HelpSecure($btValues['iban_number']); ?>" placeholder="e.g. DE89 3704 0044 0532 0130 00">
        <div class="bt-hint"><?php echo iN_HelpSecure($btLang['iban_hint']); ?></div>
        <div class="bt-err" data-err-for="iban_number"></div>
      </div>

      <div class="bt-field">
        <label class="bt-label" for="bt_routing_number"><?php echo iN_HelpSecure($btLang['routing_number']); ?></label>
        <input type="text" id="bt_routing_number" name="routing_number" class="bt-input" autocomplete="off" maxlength="40" value="<?php echo iN_HelpSecure($btValues['routing_number']); ?>">
      </div>

      <div class="bt-field">
        <label class="bt-label" for="bt_account_number"><?php echo iN_HelpSecure($btLang['account_number']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_account_number" name="account_number" class="bt-input" autocomplete="off" maxlength="40" value="<?php echo iN_HelpSecure($btValues['account_number']); ?>">
        <div class="bt-err" data-err-for="account_number"></div>
      </div>

      <div class="bt-field bt-span-2">
        <label class="bt-label" for="bt_confirm_account_number"><?php echo iN_HelpSecure($btLang['confirm_account_number']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_confirm_account_number" name="confirm_account_number" class="bt-input" autocomplete="off" maxlength="40" value="<?php echo iN_HelpSecure($btValues['account_number']); ?>">
        <div class="bt-err" data-err-for="confirm_account_number"></div>
      </div>

    </div>
  </div>

  <div class="bt-section">
    <div class="bt-section-title"><?php echo iN_HelpSecure($btLang['section_holder']); ?></div>
    <div class="bt-grid">

      <div class="bt-field bt-span-2">
        <label class="bt-label" for="bt_account_holder_name"><?php echo iN_HelpSecure($btLang['account_holder_name']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_account_holder_name" name="account_holder_name" class="bt-input" autocomplete="name" maxlength="120" value="<?php echo iN_HelpSecure($btValues['account_holder_name']); ?>">
        <div class="bt-err" data-err-for="account_holder_name"></div>
      </div>

      <div class="bt-field bt-span-2">
        <label class="bt-label" for="bt_phone_number"><?php echo iN_HelpSecure($btLang['phone_number']); ?><span class="bt-req">*</span></label>
        <div class="bt-phone">
          <div class="bt-country-picker bt-phone-dial" data-picker="phone_country" data-phone-dial>
            <button type="button" class="bt-picker-btn bt-phone-btn" aria-haspopup="listbox" aria-expanded="false">
              <span class="bt-flag" data-flag></span>
              <span class="bt-dial" data-dial>+</span>
              <svg class="bt-caret" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5l4.5 4.5 4.5-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <input type="hidden" name="phone_country_code" id="bt_phone_country_code" value="<?php echo iN_HelpSecure($btValues['phone_country_code']); ?>">
          </div>
          <input type="tel" id="bt_phone_number" name="phone_number" class="bt-input bt-phone-input" autocomplete="tel-national" inputmode="tel" maxlength="20" value="<?php echo iN_HelpSecure($btValues['phone_number']); ?>" placeholder="(000) 000 00 00">
        </div>
        <div class="bt-err" data-err-for="phone_number"></div>
      </div>

      <div class="bt-field bt-span-2">
        <label class="bt-label" for="bt_street_address"><?php echo iN_HelpSecure($btLang['street_address']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_street_address" name="street_address" class="bt-input" autocomplete="street-address" maxlength="190" value="<?php echo iN_HelpSecure($btValues['street_address']); ?>">
        <div class="bt-err" data-err-for="street_address"></div>
      </div>

      <div class="bt-field">
        <label class="bt-label" for="bt_country"><?php echo iN_HelpSecure($btLang['country']); ?><span class="bt-req">*</span></label>
        <div class="bt-country-picker" data-picker="country">
          <button type="button" class="bt-picker-btn" aria-haspopup="listbox" aria-expanded="false">
            <span class="bt-flag" data-flag></span>
            <span class="bt-picker-label" data-picker-label><?php echo iN_HelpSecure($btLang['select_country']); ?></span>
            <svg class="bt-caret" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5l4.5 4.5 4.5-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <input type="hidden" name="country" id="bt_country" value="<?php echo iN_HelpSecure($btValues['country']); ?>">
        </div>
        <div class="bt-err" data-err-for="country"></div>
      </div>

      <div class="bt-field">
        <label class="bt-label" for="bt_state"><?php echo iN_HelpSecure($btLang['state']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_state" name="state" class="bt-input" autocomplete="address-level1" maxlength="80" value="<?php echo iN_HelpSecure($btValues['state']); ?>">
        <div class="bt-err" data-err-for="state"></div>
      </div>

      <div class="bt-field">
        <label class="bt-label" for="bt_city"><?php echo iN_HelpSecure($btLang['city']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_city" name="city" class="bt-input" autocomplete="address-level2" maxlength="80" value="<?php echo iN_HelpSecure($btValues['city']); ?>">
        <div class="bt-err" data-err-for="city"></div>
      </div>

      <div class="bt-field">
        <label class="bt-label" for="bt_postal_code"><?php echo iN_HelpSecure($btLang['postal_code']); ?><span class="bt-req">*</span></label>
        <input type="text" id="bt_postal_code" name="postal_code" class="bt-input" autocomplete="postal-code" maxlength="16" value="<?php echo iN_HelpSecure($btValues['postal_code']); ?>">
        <div class="bt-err" data-err-for="postal_code"></div>
      </div>

    </div>
  </div>
</div>
<script>
window.BT_COUNTRIES = window.BT_COUNTRIES || <?php echo json_encode($btCountries, JSON_UNESCAPED_UNICODE); ?>;
window.BT_I18N = window.BT_I18N || {
  search: <?php echo json_encode($btLang['search_country']); ?>,
  ibanInvalid: <?php echo json_encode($btLang['iban_invalid']); ?>,
  accountMismatch: <?php echo json_encode($btLang['account_mismatch']); ?>,
  required: <?php echo json_encode($btLang['required']); ?>,
  selectCountry: <?php echo json_encode($btLang['select_country']); ?>
};
</script>
