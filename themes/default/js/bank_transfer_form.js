/*!
 * Dizzy – Premium Bank Transfer form behavior.
 * No external dependencies. Works with or without jQuery.
 * - Custom searchable country picker (flag + dial code)
 * - Professional international phone input
 * - IBAN auto-uppercase / auto-space / basic MOD-97 validation
 * - Confirm-account match validation
 * - Collects structured fields on submit and mirrors to legacy `#bank_transfer` hidden input
 */
(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  // Build an <img> tag for a flag using flagcdn.com SVGs (reliable on Windows).
  // Falls back to the ISO2 letters in a rounded chip if the image fails.
  function flagImgHtml(iso2) {
    if (!iso2 || iso2.length !== 2) return '';
    var cc = String(iso2).toLowerCase();
    var CC = String(iso2).toUpperCase();
    return '<img class="bt-flag-img" src="https://flagcdn.com/w40/' + cc + '.png"' +
           ' srcset="https://flagcdn.com/w40/' + cc + '.png 1x, https://flagcdn.com/w80/' + cc + '.png 2x"' +
           ' alt="' + CC + '" loading="lazy" decoding="async"' +
           ' onerror="this.onerror=null;this.outerHTML=\'<span class=&quot;bt-flag-fallback&quot;>' + CC + '</span>\'">';
  }

  // Basic IBAN MOD-97 check.
  function ibanIsValid(ibanRaw) {
    if (!ibanRaw) return true; // empty is allowed here (optional for non-IBAN regions)
    var iban = String(ibanRaw).replace(/\s+/g, '').toUpperCase();
    if (!/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/.test(iban)) return false;
    var rearranged = iban.slice(4) + iban.slice(0, 4);
    var expanded = '';
    for (var i = 0; i < rearranged.length; i++) {
      var ch = rearranged.charAt(i);
      var code = ch.charCodeAt(0);
      if (code >= 65 && code <= 90) { expanded += (code - 55).toString(); }
      else { expanded += ch; }
    }
    // Chunked MOD-97
    var remainder = 0, chunk;
    for (var j = 0; j < expanded.length; j += 7) {
      chunk = remainder + expanded.substr(j, 7);
      remainder = parseInt(chunk, 10) % 97;
    }
    return remainder === 1;
  }

  function formatIban(value) {
    if (!value) return '';
    var clean = String(value).replace(/\s+/g, '').toUpperCase();
    return clean.replace(/(.{4})/g, '$1 ').trim();
  }

  function toggleErr(el, show, msg) {
    if (!el) return;
    var err = el.parentNode && el.parentNode.querySelector('[data-err-for]');
    if (err) {
      if (show) { err.textContent = msg || err.getAttribute('data-default') || ''; err.classList.add('show'); }
      else { err.classList.remove('show'); }
    }
    el.classList.toggle('bt-invalid', !!show);
  }

  // Country picker factory
  function buildPicker(container, countries, opts) {
    opts = opts || {};
    var btn = container.querySelector('.bt-picker-btn');
    var labelEl = container.querySelector('[data-picker-label]');
    var flagEl = container.querySelector('[data-flag]');
    var dialEl = container.querySelector('[data-dial]');
    var hidden = container.querySelector('input[type="hidden"]');
    var panel = null, backdrop = null, searchInput = null, listEl = null, focusIdx = -1;
    var i18n = (window.BT_I18N || {});
    var isPhone = container.hasAttribute('data-phone-dial');

    function renderBtn(country) {
      if (!country) {
        if (flagEl) flagEl.innerHTML = '';
        if (labelEl) { labelEl.textContent = i18n.selectCountry || 'Select a country'; labelEl.classList.add('bt-placeholder'); }
        if (dialEl) dialEl.textContent = '+';
        if (hidden) hidden.value = '';
        return;
      }
      if (flagEl) { flagEl.innerHTML = flagImgHtml(country.iso2); }
      if (labelEl) { labelEl.textContent = country.name; labelEl.classList.remove('bt-placeholder'); }
      if (dialEl) dialEl.textContent = '+' + country.dial;
      if (hidden) hidden.value = isPhone ? country.dial : country.iso2;
    }

    function findCountry(value) {
      if (!value) return null;
      var v = String(value).toUpperCase();
      for (var i = 0; i < countries.length; i++) {
        if (isPhone) { if (countries[i].dial === String(value)) return countries[i]; }
        else if (countries[i].iso2 === v) return countries[i];
      }
      return null;
    }

    function openPanel() {
      if (panel) return;
      btn.setAttribute('aria-expanded', 'true');
      panel = document.createElement('div');
      panel.className = 'bt-picker-panel';
      if (window.matchMedia && window.matchMedia('(max-width: 560px)').matches) {
        panel.classList.add('bt-sheet');
        backdrop = document.createElement('div');
        backdrop.className = 'bt-picker-backdrop';
        document.body.appendChild(backdrop);
        backdrop.addEventListener('click', closePanel);
        document.body.appendChild(panel);
      } else {
        container.appendChild(panel);
      }
      panel.innerHTML =
        '<div class="bt-picker-search"><input type="text" autocomplete="off" spellcheck="false" placeholder="' +
        (i18n.search || 'Search country…') + '"></div>' +
        '<div class="bt-picker-list" role="listbox"></div>';
      searchInput = panel.querySelector('input');
      listEl = panel.querySelector('.bt-picker-list');
      renderList('');
      try { searchInput.focus({ preventScroll: true }); } catch (e) { searchInput.focus(); }
      searchInput.addEventListener('input', function () { focusIdx = -1; renderList(this.value); });
      searchInput.addEventListener('keydown', onKey);
      setTimeout(function () { document.addEventListener('click', outsideClick, true); }, 0);
    }

    function closePanel() {
      if (!panel) return;
      btn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', outsideClick, true);
      if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
      backdrop = null;
      if (panel.parentNode) panel.parentNode.removeChild(panel);
      panel = null; searchInput = null; listEl = null; focusIdx = -1;
    }

    function outsideClick(e) {
      if (!panel) return;
      if (container.contains(e.target) || panel.contains(e.target)) return;
      closePanel();
    }

    function renderList(query) {
      if (!listEl) return;
      var q = (query || '').trim().toLowerCase();
      var html = '';
      var currentVal = hidden ? hidden.value : '';
      var count = 0;
      for (var i = 0; i < countries.length; i++) {
        var c = countries[i];
        var hay = c.name.toLowerCase() + ' ' + c.iso2.toLowerCase() + ' +' + c.dial;
        if (q && hay.indexOf(q) === -1) continue;
        var isSel = isPhone ? (c.dial === currentVal) : (c.iso2 === currentVal);
        html += '<div class="bt-picker-item' + (isSel ? ' bt-selected' : '') + '" role="option" data-idx="' + i + '">' +
                '<span class="bt-flag">' + flagImgHtml(c.iso2) + '</span>' +
                '<span class="bt-picker-item-name">' + escapeHtml(c.name) + '</span>' +
                '<span class="bt-picker-item-dial">+' + c.dial + '</span>' +
                '</div>';
        count++;
      }
      if (count === 0) {
        html = '<div class="bt-picker-empty">—</div>';
      }
      listEl.innerHTML = html;
      var items = listEl.querySelectorAll('.bt-picker-item');
      for (var k = 0; k < items.length; k++) {
        items[k].addEventListener('click', onItemClick);
        items[k].addEventListener('mousemove', onItemHover);
      }
    }

    function onItemClick(e) {
      var idx = parseInt(this.getAttribute('data-idx'), 10);
      var c = countries[idx]; if (!c) return;
      renderBtn(c);
      if (typeof opts.onChange === 'function') opts.onChange(c);
      closePanel();
    }
    function onItemHover() {
      var items = listEl.querySelectorAll('.bt-picker-item');
      for (var i = 0; i < items.length; i++) items[i].classList.remove('bt-focus');
      this.classList.add('bt-focus');
      focusIdx = parseInt(this.getAttribute('data-idx'), 10);
    }
    function onKey(e) {
      var items = listEl ? listEl.querySelectorAll('.bt-picker-item') : [];
      if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(1, items); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); moveFocus(-1, items); }
      else if (e.key === 'Enter') { e.preventDefault(); clickFocused(items); }
      else if (e.key === 'Escape') { closePanel(); btn.focus(); }
    }
    function moveFocus(delta, items) {
      if (!items.length) return;
      for (var i = 0; i < items.length; i++) items[i].classList.remove('bt-focus');
      var cur = 0;
      for (var j = 0; j < items.length; j++) if (items[j].classList.contains('bt-focus')) { cur = j; break; }
      var next = 0;
      for (var k = 0; k < items.length; k++) if (items[k].getAttribute('data-idx') == focusIdx) { next = k; break; }
      next = (focusIdx === -1 ? 0 : (next + delta + items.length) % items.length);
      items[next].classList.add('bt-focus');
      focusIdx = parseInt(items[next].getAttribute('data-idx'), 10);
      items[next].scrollIntoView({ block: 'nearest' });
    }
    function clickFocused(items) {
      for (var i = 0; i < items.length; i++) {
        if (items[i].classList.contains('bt-focus')) { items[i].click(); return; }
      }
      if (items.length) items[0].click();
    }

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (panel) closePanel(); else openPanel();
    });

    var initialCountry = findCountry(hidden ? hidden.value : '');
    renderBtn(initialCountry);

    return {
      getValue: function () { return hidden ? hidden.value : ''; },
      getCountry: function () { return findCountry(hidden ? hidden.value : ''); },
      setCountry: function (iso2OrDial) {
        var c = findCountry(iso2OrDial);
        if (c) renderBtn(c);
      }
    };
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function initForm(root) {
    if (!root || root.__btInit) return;
    root.__btInit = true;
    var countries = window.BT_COUNTRIES || [];

    // Pickers
    var bankCountryPicker = null, countryPicker = null, phoneDialPicker = null;
    var bankCountryEl = root.querySelector('[data-picker="bank_country"]');
    var countryEl = root.querySelector('[data-picker="country"]');
    var phoneEl = root.querySelector('[data-picker="phone_country"]');

    if (bankCountryEl) bankCountryPicker = buildPicker(bankCountryEl, countries);

    if (phoneEl) {
      phoneDialPicker = buildPicker(phoneEl, countries, {});
    }

    if (countryEl) {
      countryPicker = buildPicker(countryEl, countries, {
        onChange: function (c) {
          // Link phone dial code to user's country when user hasn't touched phone
          if (phoneDialPicker && !phoneEl.__userPicked) {
            phoneDialPicker.setCountry(c.dial);
          }
        }
      });
    }
    if (phoneEl) {
      phoneEl.addEventListener('click', function () { phoneEl.__userPicked = true; }, true);
    }

    // IBAN smart formatting
    var iban = root.querySelector('[data-iban]');
    if (iban) {
      iban.addEventListener('input', function () {
        var cursor = this.selectionStart;
        var before = this.value.slice(0, cursor).replace(/\s+/g, '').length;
        this.value = formatIban(this.value);
        var newPos = 0, seen = 0;
        while (newPos < this.value.length && seen < before) {
          if (this.value.charAt(newPos) !== ' ') seen++;
          newPos++;
        }
        try { this.setSelectionRange(newPos, newPos); } catch (e) {}
        toggleErr(this, false);
      });
      iban.addEventListener('blur', function () {
        var v = this.value.trim();
        if (v && !ibanIsValid(v)) {
          toggleErr(this, true, (window.BT_I18N || {}).ibanInvalid || 'Invalid IBAN');
        } else {
          toggleErr(this, false);
        }
      });
      if (iban.value) iban.value = formatIban(iban.value);
    }

    // Account number confirm match
    var acc = root.querySelector('#bt_account_number');
    var confirmAcc = root.querySelector('#bt_confirm_account_number');
    function checkMatch() {
      if (!acc || !confirmAcc) return true;
      if (!confirmAcc.value) { toggleErr(confirmAcc, false); return true; }
      if (acc.value !== confirmAcc.value) {
        toggleErr(confirmAcc, true, (window.BT_I18N || {}).accountMismatch || 'Numbers do not match');
        return false;
      }
      toggleErr(confirmAcc, false);
      return true;
    }
    if (acc) acc.addEventListener('input', function(){ toggleErr(acc, false); checkMatch(); });
    if (confirmAcc) confirmAcc.addEventListener('input', checkMatch);

    // Clear errors on input
    var inputs = root.querySelectorAll('.bt-input');
    for (var i = 0; i < inputs.length; i++) {
      inputs[i].addEventListener('input', function () { toggleErr(this, false); });
    }

    // Expose validation + serialization API
    root.btValidate = function () {
      var ok = true;
      var required = [
        ['bank_country', bankCountryEl],
        ['country', countryEl],
        ['account_number', acc],
        ['confirm_account_number', confirmAcc],
        ['account_holder_name', root.querySelector('#bt_account_holder_name')],
        ['phone_number', root.querySelector('#bt_phone_number')],
        ['street_address', root.querySelector('#bt_street_address')],
        ['state', root.querySelector('#bt_state')],
        ['city', root.querySelector('#bt_city')],
        ['postal_code', root.querySelector('#bt_postal_code')]
      ];
      for (var i = 0; i < required.length; i++) {
        var el = required[i][1]; if (!el) continue;
        var val = '';
        if (el.tagName === 'DIV') {
          var hid = el.querySelector('input[type="hidden"]'); val = hid ? hid.value : '';
        } else {
          val = (el.value || '').trim();
        }
        if (!val) {
          toggleErr(el.tagName === 'DIV' ? el : el, true, (window.BT_I18N || {}).required || 'Required');
          ok = false;
        }
      }
      if (iban && iban.value.trim() && !ibanIsValid(iban.value)) {
        toggleErr(iban, true, (window.BT_I18N || {}).ibanInvalid || 'Invalid IBAN');
        ok = false;
      }
      if (!checkMatch()) ok = false;
      // Phone dial selected
      if (phoneEl) {
        var hid = phoneEl.querySelector('input[type="hidden"]');
        if (!hid || !hid.value) {
          toggleErr(root.querySelector('#bt_phone_number'), true, (window.BT_I18N || {}).required || 'Required');
          ok = false;
        }
      }
      return ok;
    };

    root.btSerialize = function () {
      function v(id) { var el = root.querySelector(id); return el ? (el.value || '').trim() : ''; }
      var ibanClean = (v('#bt_iban_number') || '').replace(/\s+/g, '').toUpperCase();
      var data = {
        bank_country:        v('#bt_bank_country'),
        iban_number:         ibanClean,
        routing_number:      v('#bt_routing_number'),
        account_number:      v('#bt_account_number'),
        confirm_account_number: v('#bt_confirm_account_number'),
        account_holder_name: v('#bt_account_holder_name'),
        phone_country_code:  v('#bt_phone_country_code'),
        phone_number:        v('#bt_phone_number'),
        street_address:      v('#bt_street_address'),
        country:             v('#bt_country'),
        state:               v('#bt_state'),
        city:                v('#bt_city'),
        postal_code:         v('#bt_postal_code')
      };
      // Build legacy readable summary for the existing `bank_account` text column.
      var summary = [
        'Holder: ' + data.account_holder_name,
        'Bank Country: ' + data.bank_country,
        'IBAN: ' + data.iban_number,
        'Routing/SWIFT: ' + data.routing_number,
        'Account: ' + data.account_number,
        'Phone: +' + data.phone_country_code + ' ' + data.phone_number,
        'Address: ' + data.street_address + ', ' + data.city + ', ' + data.state + ' ' + data.postal_code + ', ' + data.country
      ].filter(function(line){ return line.replace(/^[^:]+:\s*/, '').trim() !== '' && !/^\s*[,+]*\s*$/.test(line.replace(/^[^:]+:\s*/, '')); }).join('\n');
      data._summary = summary;
      var legacy = root.querySelector('#bank_transfer');
      if (legacy) legacy.value = summary;
      return data;
    };
  }

  function initAll() {
    var forms = document.querySelectorAll('[data-bt-form]');
    for (var i = 0; i < forms.length; i++) initForm(forms[i]);
  }

  onReady(initAll);
  // Re-init when the bank panel is shown dynamically
  document.addEventListener('click', function () {
    setTimeout(initAll, 10);
  }, true);

  window.DizzyBankForm = { init: initAll };
})();
