(function () {
  function normalize(text) {
    return (text || '').replace(/\s+/g, ' ').trim();
  }

  function applyResponsiveLabels(table) {
    if (!table || table.dataset.mobileEnhanced === '1') {
      return;
    }

    var headers = Array.prototype.slice.call(table.querySelectorAll('thead th')).map(function (th) {
      return normalize(th.textContent);
    });

    var bodyRows = table.querySelectorAll('tbody tr');
    bodyRows.forEach(function (row) {
      var cells = row.querySelectorAll('td');
      cells.forEach(function (cell, index) {
        var label = headers[index] || 'Detail';
        cell.setAttribute('data-label', label);
      });
    });

    table.dataset.mobileEnhanced = '1';
    table.classList.add('cp-table-stacked-ready');
  }

  function enhanceControlPanelTables() {
    var tables = document.querySelectorAll('.cp-table');
    tables.forEach(applyResponsiveLabels);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', enhanceControlPanelTables);
  } else {
    enhanceControlPanelTables();
  }
})();
