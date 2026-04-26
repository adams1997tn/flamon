/* ============================================================
 * responsive-tables.js
 * Auto-populates data-label on every cell of any responsive
 * table, so the mobile stacked-card layout shows the column
 * name on the left without per-template edits.
 *
 * Targets:
 *   1) Legacy div-based: .i_tab_container with .i_tab_header > .tab_item
 *      siblings and .i_tab_list_item > .tab_detail_item siblings.
 *   2) Native <table>: any <table> with .i_responsive_table OR
 *      sitting inside a parent with .i_responsive_table.
 *
 * Idempotent: existing data-label on a cell is never overwritten.
 * Re-runs on AJAX-loaded content via a MutationObserver.
 * ============================================================ */
(function () {
  'use strict';

  function textOf(el) {
    if (!el) return '';
    var t = (el.textContent || '').replace(/\s+/g, ' ').trim();
    return t;
  }

  function labelDivTable(container) {
    if (!container || container.dataset.rtLabeled === '1') return;
    var header = container.querySelector(':scope > .i_tab_header, .i_tab_header');
    if (!header) return;
    var headers = header.querySelectorAll(':scope > .tab_item');
    if (!headers.length) return;
    var labels = Array.prototype.map.call(headers, textOf);

    var rows = container.querySelectorAll('.i_tab_list_item');
    rows.forEach(function (row) {
      var cells = row.querySelectorAll(':scope > .tab_detail_item');
      cells.forEach(function (cell, i) {
        if (!cell.hasAttribute('data-label') && labels[i]) {
          cell.setAttribute('data-label', labels[i]);
        }
      });
    });
    container.dataset.rtLabeled = '1';
  }

  function labelNativeTable(table) {
    if (!table || table.dataset.rtLabeled === '1') return;
    var ths = table.querySelectorAll('thead th');
    if (!ths.length) return;
    var labels = Array.prototype.map.call(ths, textOf);

    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function (row) {
      var tds = row.querySelectorAll(':scope > td');
      tds.forEach(function (td, i) {
        if (!td.hasAttribute('data-label') && labels[i]) {
          td.setAttribute('data-label', labels[i]);
        }
      });
    });
    table.dataset.rtLabeled = '1';
  }

  function scan(root) {
    var scope = root && root.querySelectorAll ? root : document;
    // Div-based tables.
    scope.querySelectorAll('.i_tab_container').forEach(labelDivTable);
    // Native tables (opt-in or via parent class).
    scope.querySelectorAll('table.i_responsive_table, .i_responsive_table table')
      .forEach(labelNativeTable);
  }

  function init() {
    scan(document);
    // Observe DOM mutations so AJAX-loaded tables also get labeled.
    if (typeof MutationObserver === 'function') {
      var obs = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var added = mutations[i].addedNodes;
          for (var j = 0; j < added.length; j++) {
            var n = added[j];
            if (n.nodeType !== 1) continue;
            if (n.matches && (n.matches('.i_tab_container') ||
                              n.matches('table.i_responsive_table') ||
                              n.matches('.i_responsive_table'))) {
              scan(n.parentNode || n);
            } else if (n.querySelectorAll) {
              scan(n);
            }
          }
        }
      });
      obs.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
