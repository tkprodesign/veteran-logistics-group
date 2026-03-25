document.addEventListener('DOMContentLoaded', function () {
  var buttons = document.querySelectorAll('.value-nav button[data-tab]');
  var panels = document.querySelectorAll('.value-panel');
  var serviceTabs = document.querySelectorAll('.service-tabs a[data-service-panel]');
  var servicePanels = document.querySelectorAll('.service-panel');

  if (serviceTabs.length && servicePanels.length) {
    var activateServicePanel = function (panelId) {
      serviceTabs.forEach(function (t) {
        t.classList.remove('active');
      });
      servicePanels.forEach(function (p) {
        p.classList.remove('active');
      });

      var matchTab = document.querySelector('.service-tabs a[data-service-panel="' + panelId + '"]');
      var targetPanel = document.getElementById(panelId);

      if (matchTab && targetPanel) {
        matchTab.classList.add('active');
        targetPanel.classList.add('active');
      }
    };

    serviceTabs.forEach(function (tab) {
      tab.addEventListener('click', function (e) {
        e.preventDefault();
        var panelId = tab.getAttribute('data-service-panel');
        activateServicePanel(panelId);
      });
    });

    var current = document.querySelector('.service-tabs a.active[data-service-panel]');
    activateServicePanel(current ? current.getAttribute('data-service-panel') : 'panel-domestic');
  }

  if (!buttons.length || !panels.length) return;

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      var tab = button.getAttribute('data-tab');

      buttons.forEach(function (b) {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });

      panels.forEach(function (p) {
        p.classList.remove('active');
      });

      button.classList.add('active');
      button.setAttribute('aria-selected', 'true');

      var panel = document.getElementById(tab);
      if (panel) panel.classList.add('active');
    });
  });
});
