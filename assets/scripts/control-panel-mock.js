(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.querySelector('[data-toggle-list]');
    var shell = document.querySelector('.mock-design-shell');
    var body = document.body;

    if (toggleBtn && shell) {
      toggleBtn.addEventListener('click', function () {
        var isOpen = shell.classList.toggle('is-open');
        toggleBtn.textContent = isOpen ? 'Hide cost breakdown' : 'Toggle cost breakdown';
      });
    }

    if (body && body.getAttribute('data-mock-design') === '2' && shell) {
      shell.classList.add('is-open');
    }
  });
})();
