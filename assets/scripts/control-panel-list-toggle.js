(function () {
  function toArray(nodeList) {
    return Array.prototype.slice.call(nodeList || []);
  }

  function createButton(label, active) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'cp-view-toggle-btn' + (active ? ' is-active' : '');
    button.textContent = label;
    return button;
  }

  function buildCarouselFromTable(table, sectionId) {
    var headers = toArray(table.querySelectorAll('thead th')).map(function (th) {
      return (th.textContent || '').trim();
    });
    var rows = toArray(table.querySelectorAll('tbody tr'));
    var validRows = rows.filter(function (row) {
      return row.querySelectorAll('td').length > 1;
    });

    var carousel = document.createElement('div');
    carousel.className = 'cp-carousel';
    carousel.setAttribute('data-section-id', sectionId || '');

    var viewport = document.createElement('div');
    viewport.className = 'cp-carousel-viewport';

    var track = document.createElement('div');
    track.className = 'cp-carousel-track';

    validRows.forEach(function (row, rowIndex) {
      var slide = document.createElement('article');
      slide.className = 'cp-carousel-slide';

      var slideHead = document.createElement('header');
      slideHead.className = 'cp-carousel-slide-head';
      slideHead.textContent = 'Record ' + (rowIndex + 1);
      slide.appendChild(slideHead);

      var cells = toArray(row.querySelectorAll('td'));
      cells.forEach(function (cell, cellIndex) {
        var item = document.createElement('div');
        item.className = 'cp-carousel-item';

        var key = document.createElement('span');
        key.className = 'cp-carousel-key';
        key.textContent = headers[cellIndex] || 'Detail';

        var value = document.createElement('div');
        value.className = 'cp-carousel-value';
        value.innerHTML = cell.innerHTML;

        item.appendChild(key);
        item.appendChild(value);
        slide.appendChild(item);
      });

      track.appendChild(slide);
    });

    viewport.appendChild(track);

    var controls = document.createElement('div');
    controls.className = 'cp-carousel-controls';

    var prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'cp-view-toggle-btn';
    prevBtn.textContent = 'Previous';

    var nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'cp-view-toggle-btn';
    nextBtn.textContent = 'Next';

    var status = document.createElement('span');
    status.className = 'cp-carousel-status';

    var currentIndex = 0;
    var total = Math.max(validRows.length, 1);

    function sync() {
      var offset = currentIndex * 100;
      track.style.transform = 'translateX(-' + offset + '%)';
      status.textContent = currentIndex + 1 + ' / ' + total;
      prevBtn.disabled = currentIndex <= 0;
      nextBtn.disabled = currentIndex >= total - 1;
    }

    prevBtn.addEventListener('click', function () {
      if (currentIndex > 0) {
        currentIndex -= 1;
        sync();
      }
    });

    nextBtn.addEventListener('click', function () {
      if (currentIndex < total - 1) {
        currentIndex += 1;
        sync();
      }
    });

    controls.appendChild(prevBtn);
    controls.appendChild(status);
    controls.appendChild(nextBtn);

    carousel.appendChild(viewport);
    carousel.appendChild(controls);

    sync();

    return carousel;
  }

  function enhanceListSection(section) {
    if (!section || section.dataset.viewToggleEnhanced === '1') {
      return;
    }

    var tableWrap = section.querySelector('.cp-table-wrap');
    var table = section.querySelector('.cp-table');
    if (!tableWrap || !table) {
      return;
    }

    var toolbar = document.createElement('div');
    toolbar.className = 'cp-view-toggle';

    var listBtn = createButton('List View', true);
    var cardBtn = createButton('Card View', false);

    toolbar.appendChild(listBtn);
    toolbar.appendChild(cardBtn);

    var cardContainer = document.createElement('div');
    cardContainer.className = 'cp-card-view';
    cardContainer.hidden = true;

    var sectionId = section.getAttribute('id') || '';
    var carousel = buildCarouselFromTable(table, sectionId);
    cardContainer.appendChild(carousel);

    section.insertBefore(toolbar, tableWrap);
    section.insertBefore(cardContainer, tableWrap.nextSibling);

    function setMode(mode) {
      var isCardMode = mode === 'card';
      section.classList.toggle('cp-mode-card', isCardMode);
      section.classList.toggle('cp-mode-list', !isCardMode);
      listBtn.classList.toggle('is-active', !isCardMode);
      cardBtn.classList.toggle('is-active', isCardMode);
      tableWrap.hidden = isCardMode;
      cardContainer.hidden = !isCardMode;
    }

    listBtn.addEventListener('click', function () {
      setMode('list');
    });

    cardBtn.addEventListener('click', function () {
      setMode('card');
    });

    section.classList.add('cp-mode-list');
    tableWrap.classList.add('cp-table-wrap-compact');
    section.dataset.viewToggleEnhanced = '1';
  }

  function init() {
    var listSections = document.querySelectorAll('.cp-card.cp-card-list');
    listSections.forEach(enhanceListSection);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
