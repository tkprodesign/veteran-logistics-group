(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var debugStatusEl = document.getElementById('service-process-status');
    if (debugStatusEl) {
      debugStatusEl.textContent = 'DEBUG: shipments.js loaded.';
    }

    window.addEventListener('error', function (evt) {
      var errStatus = document.getElementById('service-process-status');
      if (!errStatus) return;
      var msg = (evt && evt.message) ? evt.message : 'Unknown script error';
      errStatus.textContent = 'DEBUG JS ERROR: ' + msg;
      errStatus.classList.add('is-error');
      errStatus.classList.add('is-emphasis');
    });

    var cfg = window.shippingCreateConfig || {};
    var standardPickupDate = String(cfg.standardPickupDate || '');
    var pickupInstructionLimit = Number(cfg.pickupInstructionLimit || 240);
    var freePickupModeCopy = String(cfg.freePickupModeCopy || '');
    var freePickupHelpCopy = String(cfg.freePickupHelpCopy || '');
    var customPickupModeCopy = String(cfg.customPickupModeCopy || '');
    var customPickupHelpCopy = String(cfg.customPickupHelpCopy || '');
    var currentStep = Number(cfg.currentStep || 0);
    var serviceQuoteReady = !!cfg.serviceQuoteReady;
    var serviceTotalBase = Number(cfg.serviceTotal || 0);
    var pickupFeeCustom = Number(cfg.pickupFeeCustom || 15.75);
    var optionsTotalBase = Number(cfg.optionsTotal || 0);
    var carbonFeeBase = Number(cfg.carbonFeeAmount || 0);
    var promoDiscountBase = Number(cfg.promoDiscountAmount || 0);
    var selectedServiceLevelBase = String(cfg.selectedServiceLevel || '').toLowerCase();
    var selectedPickupOptionBase = String(cfg.selectedPickupOption || 'dropoff').toLowerCase();

    var countriesSource = [];
    if (typeof country_list !== 'undefined' && Array.isArray(country_list)) {
      countriesSource = country_list;
    } else if (Array.isArray(window.country_list)) {
      countriesSource = window.country_list;
    }

    function populateCountrySelect(selectEl, selectedValue) {
      if (!selectEl || !Array.isArray(countriesSource) || !countriesSource.length) return;

      var preferred = 'United States';
      var targetValue = (selectedValue && selectedValue.trim()) ? selectedValue.trim() : preferred;
      var seen = new Set();
      var options = [];

      options.push({ name: preferred, code: 'US' });
      seen.add(preferred.toLowerCase());

      countriesSource.forEach(function (country) {
        if (!country || typeof country.name !== 'string') return;
        var name = country.name.trim();
        var code = (typeof country.code === 'string') ? country.code.trim().toUpperCase() : '';
        if (!name) return;
        var key = name.toLowerCase();
        if (seen.has(key)) return;
        seen.add(key);
        options.push({ name: name, code: code });
      });

      selectEl.innerHTML = '';
      options.forEach(function (entry) {
        var opt = document.createElement('option');
        opt.value = entry.name;
        opt.textContent = entry.name;
        if (entry.code) opt.setAttribute('data-code', entry.code);
        if (entry.name === targetValue) opt.selected = true;
        selectEl.appendChild(opt);
      });

      if (![...selectEl.options].some(function (o) { return o.value === targetValue; })) {
        selectEl.value = preferred;
      }
    }

    var shipFromSelect = document.querySelector('select[name="ship_from_country"]');
    var shipFromCodeInput = document.getElementById('ship-from-country-code');
    var shipToSelect = document.querySelector('select[name="ship_to_country"]');

    if (shipFromSelect) {
      populateCountrySelect(shipFromSelect, shipFromSelect.value || 'United States');
      var syncShipFromCode = function () {
        if (!shipFromCodeInput) return;
        var opt = shipFromSelect.options[shipFromSelect.selectedIndex];
        var code = opt ? (opt.getAttribute('data-code') || 'US') : 'US';
        shipFromCodeInput.value = code;
      };
      shipFromSelect.addEventListener('change', syncShipFromCode);
      syncShipFromCode();
    }
    if (shipToSelect) {
      populateCountrySelect(shipToSelect, shipToSelect.value || 'United States');
    }

    document.querySelectorAll('.js-address-group').forEach(function (group) {
      var streetInput = group.querySelector('.js-street-trigger');
      var extraBlock = group.querySelector('.js-address-extra');
      if (!streetInput || !extraBlock) return;

      function updateAddressExtraVisibility() {
        var hasStreetValue = (streetInput.value || '').replace(/\s+/g, '').length > 0;
        extraBlock.classList.toggle('is-visible', hasStreetValue);
      }

      streetInput.addEventListener('input', updateAddressExtraVisibility);
      streetInput.addEventListener('blur', updateAddressExtraVisibility);
      updateAddressExtraVisibility();
    });

    function syncRadioCardState(groupName) {
      var radios = document.querySelectorAll('input[type="radio"][name="' + groupName + '"]');
      if (!radios.length) return;
      radios.forEach(function (radio) {
        var card = radio.closest('.service-option');
        if (!card) return;
        card.classList.toggle('active', radio.checked);
      });
    }

    ['pickup_option', 'service_type'].forEach(function (groupName) {
      var radios = document.querySelectorAll('input[type="radio"][name="' + groupName + '"]');
      if (!radios.length) return;

      radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
          syncRadioCardState(groupName);
        });
      });

      syncRadioCardState(groupName);
    });

    var pickupDateBox = document.getElementById('pickup-date-box');
    var pickupDateInput = document.getElementById('pickup-date-input');
    var pickupDateLabel = document.getElementById('pickup-date-label');
    var pickupDateTitle = document.getElementById('pickup-date-title');
    var pickupModeCopy = document.getElementById('pickup-mode-copy');
    var pickupHelpLink = document.getElementById('pickup-help-link');
    var pickupInstructionsPanel = document.getElementById('pickup-instructions-panel');
    var pickupInstructionsInput = document.getElementById('pickup-instructions-input');
    var pickupInstructionsCount = document.getElementById('pickup-instructions-count');
    var pickupOptionRadios = document.querySelectorAll('input[type="radio"][name="pickup_option"]');
    var pickupHelpCopyEl = document.getElementById('pickup-help-copy');

    function formatPickupDate(ymd) {
      var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(ymd || ''));
      if (!m) return ymd || '';
      var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      var year = parseInt(m[1], 10);
      var monthIndex = parseInt(m[2], 10) - 1;
      var day = parseInt(m[3], 10);
      if (monthIndex < 0 || monthIndex > 11) return ymd || '';
      return months[monthIndex] + ' ' + day + ', ' + year;
    }

    function currentPickupOption() {
      var selected = document.querySelector('input[type="radio"][name="pickup_option"]:checked');
      return selected ? selected.value : selectedPickupOptionBase;
    }

    function syncPickupUiForOption() {
      if (!pickupDateBox || !pickupDateInput || !pickupDateLabel) return;
      var mode = currentPickupOption();
      var isCustom = mode === 'pickup';

      if (!isCustom) {
        pickupDateInput.value = standardPickupDate;
        pickupDateInput.disabled = true;
        pickupDateBox.classList.remove('is-selectable');
        pickupDateLabel.textContent = formatPickupDate(standardPickupDate);
        if (pickupDateTitle) pickupDateTitle.textContent = 'Earliest Pickup Date*';
        if (pickupModeCopy) pickupModeCopy.textContent = freePickupModeCopy;
        if (pickupHelpCopyEl) pickupHelpCopyEl.innerHTML = '<strong>Postman Pickup:</strong> ' + freePickupHelpCopy.replace(/^Postman Pickup:\s*/i, '');
        if (pickupHelpLink) pickupHelpLink.textContent = 'View Pickup Instructions';
        return;
      }

      pickupDateInput.disabled = false;
      pickupDateBox.classList.add('is-selectable');
      if (!pickupDateInput.value) pickupDateInput.value = standardPickupDate;
      pickupDateLabel.textContent = formatPickupDate(pickupDateInput.value);
      if (pickupDateTitle) pickupDateTitle.textContent = 'Selected Pickup Date*';
      if (pickupModeCopy) pickupModeCopy.textContent = customPickupModeCopy;
      if (pickupHelpCopyEl) pickupHelpCopyEl.innerHTML = '<strong>Postman Pickup:</strong> ' + customPickupHelpCopy.replace(/^Postman Pickup:\s*/i, '');
      if (pickupHelpLink) pickupHelpLink.textContent = 'Set Pickup Instructions';
    }

    if (pickupDateBox && pickupDateInput) {
      pickupDateBox.addEventListener('click', function () {
        if (pickupDateInput.disabled) return;
        pickupDateInput.showPicker ? pickupDateInput.showPicker() : pickupDateInput.focus();
      });

      pickupDateInput.addEventListener('change', function () {
        if (pickupDateLabel) pickupDateLabel.textContent = formatPickupDate(pickupDateInput.value);
      });
    }

    pickupOptionRadios.forEach(function (radio) {
      radio.addEventListener('change', syncPickupUiForOption);
      radio.addEventListener('change', syncPickupSummary);
    });
    syncPickupUiForOption();
    syncPickupSummary();

    function syncPickupInstructionCount() {
      if (!pickupInstructionsInput || !pickupInstructionsCount) return;
      var currentLen = (pickupInstructionsInput.value || '').length;
      pickupInstructionsCount.textContent = currentLen + '/' + pickupInstructionLimit;
    }

    if (pickupHelpLink && pickupInstructionsPanel) {
      pickupHelpLink.addEventListener('click', function (e) {
        e.preventDefault();
        var willOpen = !pickupInstructionsPanel.classList.contains('is-open');
        pickupInstructionsPanel.classList.toggle('is-open', willOpen);
        if (willOpen && pickupInstructionsInput) pickupInstructionsInput.focus();
      });
    }

    if (pickupInstructionsInput) {
      pickupInstructionsInput.addEventListener('input', syncPickupInstructionCount);
      syncPickupInstructionCount();
    }

    var createForm = document.querySelector('.create-form');

    var measureFields = ['weight', 'length', 'width', 'height']
      .map(function (name) { return createForm ? createForm.querySelector('input[name="' + name + '"]') : null; })
      .filter(Boolean);

    function sanitizeNumericValue(raw) {
      var cleaned = String(raw || '').replace(/[^0-9.]/g, '');
      var firstDot = cleaned.indexOf('.');
      if (firstDot !== -1) {
        cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
      }
      return cleaned;
    }

    measureFields.forEach(function (field) {
      field.addEventListener('input', function () {
        var next = sanitizeNumericValue(field.value);
        if (field.value !== next) field.value = next;
      });
      field.addEventListener('paste', function () {
        requestAnimationFrame(function () {
          var next = sanitizeNumericValue(field.value);
          if (field.value !== next) field.value = next;
        });
      });
      field.addEventListener('keydown', function (e) {
        if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault();
      });
    });

    if (createForm) {
      var currentStepInput = createForm.querySelector('input[name="current_step"]');
      var isStepOne = currentStepInput && currentStepInput.value === '1';
      if (isStepOne) {
        var requiredWrappers = createForm.querySelectorAll('.js-validate-required');

        function setFieldError(wrapper, message) {
          var errorEl = wrapper.querySelector('.field-error');
          var inputEl = wrapper.querySelector('input');
          if (!errorEl || !inputEl) return;

          if (message) {
            wrapper.classList.add('has-error');
            inputEl.setAttribute('aria-invalid', 'true');
            errorEl.textContent = '';
            var icon = document.createElement('span');
            icon.className = 'material-symbols-outlined';
            icon.textContent = 'warning';
            errorEl.appendChild(icon);
            errorEl.appendChild(document.createTextNode(' ' + message));
          } else {
            wrapper.classList.remove('has-error');
            inputEl.removeAttribute('aria-invalid');
            errorEl.textContent = '';
          }
        }

        function validateRequiredField(wrapper) {
          var inputEl = wrapper.querySelector('input');
          if (!inputEl) return true;

          var fieldLabel = wrapper.getAttribute('data-field-label') || 'This field';
          var fieldType = wrapper.getAttribute('data-field-type') || inputEl.getAttribute('type') || 'text';
          var raw = inputEl.value || '';
          var cleaned = raw.replace(/\s+/g, ' ').trim();
          var message = '';

          if (!cleaned) message = fieldLabel + ' is required.';
          else if (fieldType === 'email') {
            var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned);
            if (!emailOk) message = 'Enter a valid email address.';
          }

          setFieldError(wrapper, message);
          return message === '';
        }

        requiredWrappers.forEach(function (wrapper) {
          var inputEl = wrapper.querySelector('input');
          if (!inputEl) return;
          inputEl.addEventListener('blur', function () { validateRequiredField(wrapper); });
          inputEl.addEventListener('input', function () {
            if (wrapper.classList.contains('has-error')) validateRequiredField(wrapper);
          });
        });

        createForm.addEventListener('submit', function (e) {
          var allValid = true;
          requiredWrappers.forEach(function (wrapper) {
            if (!validateRequiredField(wrapper)) allValid = false;
          });
          if (!allValid) e.preventDefault();
        });
      }
    }

    var promoInput = document.getElementById('promo-code-input');
    var promoApplyBtn = document.getElementById('promo-apply-btn');
    var promoClearBtn = document.getElementById('promo-clear-btn');
    var promoFeedback = document.getElementById('promo-feedback');
    var promoRow = document.getElementById('summary-promo-row');
    var promoValueEl = document.getElementById('summary-promo-value');
    var promoCodeEl = document.getElementById('summary-promo-code');
    var totalChargeEl = document.getElementById('summary-total-charge');
    var mobileTotalEl = document.getElementById('mobile-summary-total');
    var summaryServiceRow = document.getElementById('summary-service-row');
    var summaryServiceLabel = document.getElementById('summary-service-label');
    var summaryServiceCharge = document.getElementById('summary-service-charge');
    var summaryPickupRow = document.getElementById('summary-pickup-row');
    var summaryPickupValue = document.getElementById('summary-pickup-value');
    var summaryPickupLabel = summaryPickupRow ? summaryPickupRow.querySelector('span') : null;
    var summaryOptionsRow = document.getElementById('summary-options-row');
    var summaryOptionsValue = document.getElementById('summary-options-value');
    var summaryCarbonRow = document.getElementById('summary-carbon-row');
    var summaryCarbonValue = document.getElementById('summary-carbon-value');

    function formatUsd(amount) { return '$' + Number(amount || 0).toFixed(2); }

    function currentPromoDiscount() {
      if (promoValueEl && promoRow && !promoRow.hidden) {
        var raw = (promoValueEl.textContent || '').replace(/[^0-9.-]/g, '');
        var parsed = Number(raw);
        return isNaN(parsed) ? promoDiscountBase : Math.abs(parsed);
      }
      return promoDiscountBase;
    }

    function humanizeServiceLevel(level) {
      var value = String(level || '').toLowerCase();
      if (value === 'priority') return 'Priority';
      if (value === 'express') return 'Express';
      if (value === 'economy') return 'Economy';
      return 'Service';
    }

    function currentServiceLevelValue() {
      var checked = createForm ? createForm.querySelector('input[type="radio"][name="service_type"]:checked') : null;
      return checked ? String(checked.value || '').toLowerCase() : selectedServiceLevelBase;
    }

    function selectedQuotedServicePrice() {
      if (typeof processedByLevel !== 'object' || !processedByLevel) return null;
      var currentLevel = currentServiceLevelValue();
      if (!currentLevel || !processedByLevel[currentLevel] || !processedByLevel[currentLevel].ready) return null;
      var price = Number(processedByLevel[currentLevel].price || 0);
      return isNaN(price) || price <= 0 ? null : price;
    }

    function syncPickupSummary() {
      if (currentStep !== 2 || !totalChargeEl || !summaryPickupRow) return;

      var isCustomPickup = currentPickupOption() === 'pickup';
      var pickupAmount = isCustomPickup ? pickupFeeCustom : 0;
      var promoAmount = currentPromoDiscount();
      var quotedServicePrice = selectedQuotedServicePrice();
      var serviceAmount = quotedServicePrice !== null ? quotedServicePrice : (serviceQuoteReady ? serviceTotalBase : 0);
      var subtotal = serviceAmount + optionsTotalBase + pickupAmount;
      var total = Math.max(0, subtotal - promoAmount);

      if (summaryPickupLabel) summaryPickupLabel.textContent = isCustomPickup ? 'Custom Pickup Fee' : 'Postman Pickup';
      if (summaryPickupValue) summaryPickupValue.textContent = isCustomPickup ? formatUsd(pickupAmount) : 'Free';
      if (summaryServiceRow) summaryServiceRow.hidden = quotedServicePrice === null && !serviceQuoteReady;
      if (summaryServiceLabel) {
        var currentLevelLabel = humanizeServiceLevel(currentServiceLevelValue());
        summaryServiceLabel.textContent = currentLevelLabel;
      }
      if (summaryServiceCharge) summaryServiceCharge.textContent = formatUsd(serviceAmount);
      totalChargeEl.textContent = formatUsd(total);

      if (mobileTotalEl) {
        mobileTotalEl.textContent = (serviceAmount > 0 || pickupAmount > 0 || promoAmount > 0) ? formatUsd(total) : 'Generate quote';
      }
    }

    function currentShipmentOptionsTotal() {
      var total = 0;
      document.querySelectorAll('.js-shipment-option').forEach(function (input) {
        if (!input.checked) return;
        if ((input.getAttribute('data-summary-group') || '') !== 'shipment_option') return;
        var fee = Number(input.getAttribute('data-option-fee') || 0);
        if (!isNaN(fee) && fee > 0) total += fee;
      });
      return total;
    }

    function currentCarbonAmount() {
      var total = 0;
      document.querySelectorAll('.js-shipment-option').forEach(function (input) {
        if (!input.checked) return;
        if ((input.getAttribute('data-summary-group') || '') !== 'carbon') return;
        var fee = Number(input.getAttribute('data-option-fee') || 0);
        if (!isNaN(fee) && fee > 0) total += fee;
      });
      return total;
    }

    function syncAdditionalDetailsSummary() {
      if ((currentStep !== 3 && currentStep !== 4) || !totalChargeEl) return;

      var promoAmount = currentPromoDiscount();
      var pickupAmount = currentPickupOption() === 'pickup' ? pickupFeeCustom : 0;
      var serviceAmount = selectedQuotedServicePrice();
      if (serviceAmount === null) {
        serviceAmount = serviceTotalBase;
      }
      var optionsAmount = currentShipmentOptionsTotal();
      if (currentStep === 4 && optionsAmount <= 0) optionsAmount = optionsTotalBase;
      var carbonAmount = currentCarbonAmount();
      if (currentStep === 4 && carbonAmount <= 0) carbonAmount = carbonFeeBase;

      var subtotal = serviceAmount + pickupAmount + optionsAmount + carbonAmount;
      var total = Math.max(0, subtotal - promoAmount);

      if (summaryServiceCharge) summaryServiceCharge.textContent = formatUsd(serviceAmount);
      if (summaryServiceLabel) summaryServiceLabel.textContent = humanizeServiceLevel(currentServiceLevelValue());
      if (summaryPickupLabel) summaryPickupLabel.textContent = pickupAmount > 0 ? 'Custom Pickup Fee' : 'Postman Pickup';
      if (summaryPickupValue) summaryPickupValue.textContent = pickupAmount > 0 ? formatUsd(pickupAmount) : 'Free';

      if (summaryOptionsRow && summaryOptionsValue) {
        summaryOptionsRow.hidden = optionsAmount <= 0;
        summaryOptionsValue.textContent = formatUsd(optionsAmount);
      }

      if (summaryCarbonRow && summaryCarbonValue) {
        summaryCarbonRow.hidden = carbonAmount <= 0;
        summaryCarbonValue.textContent = formatUsd(carbonAmount);
      }

      totalChargeEl.textContent = formatUsd(total);
      if (mobileTotalEl) mobileTotalEl.textContent = formatUsd(total);
    }

    function setPromoFeedback(message, mode) {
      if (!promoFeedback) return;
      promoFeedback.textContent = message || '';
      promoFeedback.classList.remove('success', 'error');
      if (mode === 'success' || mode === 'error') promoFeedback.classList.add(mode);
    }

    function setPromoLoading(isLoading) {
      if (promoApplyBtn) promoApplyBtn.disabled = isLoading;
      if (promoClearBtn) promoClearBtn.disabled = isLoading;
    }

    function applyPromoResponse(data) {
      if (!data) return;
      if (totalChargeEl && typeof data.total !== 'undefined') totalChargeEl.textContent = formatUsd(data.total);
      if (mobileTotalEl && typeof data.total !== 'undefined') mobileTotalEl.textContent = formatUsd(data.total);

      var hasDiscount = Number(data.discount_amount || 0) > 0;
      if (promoRow) promoRow.hidden = !hasDiscount;
      if (promoValueEl && hasDiscount) promoValueEl.textContent = '-' + formatUsd(data.discount_amount || 0);
      if (promoCodeEl) promoCodeEl.textContent = data.promo_code || (promoInput ? promoInput.value.trim() : '');
      if (promoClearBtn) promoClearBtn.hidden = !hasDiscount;
      promoDiscountBase = Number(data.discount_amount || 0);
      syncPickupSummary();
      syncAdditionalDetailsSummary();
    }

    function callPromoApi(params) {
      return fetch('/shipping/create/promo-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: params.toString()
      }).then(function (res) { return res.json(); });
    }

    if (promoApplyBtn && promoInput) {
      promoApplyBtn.addEventListener('click', function () {
        var code = promoInput.value.trim();
        if (!code) { setPromoFeedback('Enter a promo code.', 'error'); return; }
        setPromoLoading(true);
        setPromoFeedback('Checking promo code...', '');

        var params = new URLSearchParams();
        params.set('action', 'apply');
        params.set('code', code);
        callPromoApi(params)
          .then(function (data) {
            if (!data || !data.ok) {
              var msg = (data && data.message) ? data.message : 'Could not apply promo code.';
              setPromoFeedback(msg, 'error');
              return;
            }
            applyPromoResponse(data);
            setPromoFeedback(data.message || 'Promo code applied successfully.', 'success');
          })
          .catch(function () { setPromoFeedback('Network error while validating promo code.', 'error'); })
          .finally(function () { setPromoLoading(false); });
      });

      promoInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          promoApplyBtn.click();
        }
      });
    }

    if (promoClearBtn) {
      promoClearBtn.addEventListener('click', function () {
        setPromoLoading(true);
        var params = new URLSearchParams();
        params.set('action', 'clear');
        callPromoApi(params)
          .then(function (data) {
            if (!data || !data.ok) {
              setPromoFeedback((data && data.message) ? data.message : 'Could not remove promo code.', 'error');
              return;
            }
            if (promoInput) promoInput.value = '';
            applyPromoResponse(data);
            syncAdditionalDetailsSummary();
            setPromoFeedback('Promo code removed.', '');
          })
          .catch(function () { setPromoFeedback('Network error while removing promo code.', 'error'); })
          .finally(function () { setPromoLoading(false); });
      });
    }

    document.querySelectorAll('.js-shipment-option').forEach(function (input) {
      input.addEventListener('change', syncAdditionalDetailsSummary);
    });
    syncAdditionalDetailsSummary();

    var serviceCard = document.getElementById('service-level-card');
    var serviceQuoteHint = document.getElementById('service-quote-hint');
    var processStatusEl = document.getElementById('service-process-status');
    var processingOverlay = document.getElementById('service-processing-overlay');
    var quoteRequestIdInput = document.getElementById('quote-request-id');
    var quoteServiceLevelInput = document.getElementById('quote-service-level');
    var serviceRadios = createForm ? createForm.querySelectorAll('input[type="radio"][name="service_type"]') : [];
    var serviceGenerateBtns = createForm ? createForm.querySelectorAll('.service-generate-btn') : [];
    var runServiceProcessing = null;

    window.startServiceQuote = function (btnOrLevel) {
      var level = '';
      if (typeof btnOrLevel === 'string') level = btnOrLevel;
      else if (btnOrLevel && btnOrLevel.getAttribute) level = btnOrLevel.getAttribute('data-service-level') || '';
      if (!level && createForm) {
        var checked = createForm.querySelector('input[type="radio"][name="service_type"]:checked');
        if (checked) level = checked.value;
      }

      if (!level) level = 'economy';

      if (!runServiceProcessing) {
        var missing = [];
        if (!createForm) missing.push('createForm');
        if (!serviceCard) missing.push('service-level-card');
        if (!processingOverlay) missing.push('service-processing-overlay');
        if (!serviceRadios || !serviceRadios.length) missing.push('service_type radios');

        var blockedStatus = document.getElementById('service-process-status');
        if (blockedStatus) {
          blockedStatus.textContent = 'DEBUG: quote init blocked. Missing: ' + (missing.length ? missing.join(', ') : 'unknown');
          blockedStatus.classList.add('is-error');
          blockedStatus.classList.add('is-emphasis');
        }
        return false;
      }

      var radio = createForm ? createForm.querySelector('input[type="radio"][name="service_type"][value="' + level + '"]') : null;
      if (radio) {
        radio.checked = true;
        syncRadioCardState('service_type');
      }
      runServiceProcessing(level);
      return false;
    };

    if (createForm && processingOverlay && serviceRadios.length) {
      var processedByLevel = {};
      var pollDelays = [5000, 10000, 15000, 30000];
      var processingInFlight = false;
      var serviceDebugLogEl = document.getElementById('service-debug-log');
      var serviceDebugClearBtn = document.getElementById('service-debug-clear');
      var serviceDebugLines = [];

      function appendServiceDebugLine(message) {
        if (!serviceDebugLogEl) return;
        var stamp = new Date().toLocaleTimeString();
        serviceDebugLines.push('[' + stamp + '] ' + message);
        if (serviceDebugLines.length > 120) {
          serviceDebugLines = serviceDebugLines.slice(serviceDebugLines.length - 120);
        }
        serviceDebugLogEl.textContent = serviceDebugLines.join('\n');
      }

      if (serviceDebugClearBtn && serviceDebugLogEl) {
        serviceDebugClearBtn.addEventListener('click', function () {
          serviceDebugLines = [];
          serviceDebugLogEl.textContent = 'cleared.';
        });
      }

      function mapUiToApiLevel(value) { return value === 'standard' ? 'economy' : value; }
      function selectedServiceLevel() {
        var checked = createForm.querySelector('input[type="radio"][name="service_type"]:checked');
        return checked ? mapUiToApiLevel(checked.value) : 'economy';
      }
      function mapApiToUiLevel(level) {
        return (level === 'priority' || level === 'express' || level === 'economy') ? level : 'economy';
      }

      function setServiceStatus(message, isError, emphasize) {
        if (!processStatusEl) return;
        processStatusEl.textContent = message || '';
        processStatusEl.classList.toggle('is-error', !!isError);
        processStatusEl.classList.toggle('is-emphasis', !!emphasize);
      }

      function debugQuote(msg, data) {
        var logPayload = '';
        if (typeof data !== 'undefined') {
          try {
            logPayload = ' | ' + JSON.stringify(data);
          } catch (_e) {
            logPayload = ' | [unserializable payload]';
          }
        }
        appendServiceDebugLine(msg + logPayload);
        try {
          if (typeof data !== 'undefined') {
            console.log('[shipments][quote]', msg, data);
          } else {
            console.log('[shipments][quote]', msg);
          }
        } catch (e) {}
      }

      var activeProcessingCard = null;

      function clearProcessingCardUi() {
        if (!activeProcessingCard) return;
        activeProcessingCard.classList.remove('is-quoting');
        activeProcessingCard.style.minHeight = '';
        var loader = activeProcessingCard.querySelector('.service-item-processing');
        if (loader) loader.remove();
        activeProcessingCard = null;
      }

      function setProcessingUi(isProcessing, level) {
        processingInFlight = isProcessing;
        if (serviceCard) serviceCard.classList.toggle('is-processing', false);
        if (processingOverlay) processingOverlay.setAttribute('aria-hidden', 'true');
        serviceGenerateBtns.forEach(function (btn) { btn.disabled = isProcessing; });

        if (!isProcessing) {
          clearProcessingCardUi();
          return;
        }

        var targetCard = createForm ? createForm.querySelector('.service-level-item[data-service-level="' + level + '"]') : null;
        if (!targetCard) return;

        clearProcessingCardUi();
        targetCard.style.minHeight = targetCard.offsetHeight + 'px';
        targetCard.classList.add('is-quoting');

        var loader = document.createElement('div');
        loader.className = 'service-item-processing';
        loader.innerHTML =
          '<dotlottie-wc class="service-item-processing-lottie" src="https://lottie.host/ac0927e0-074a-4a7e-a1c1-28b874073eb6/ASb2VUE7W2.lottie" autoplay loop></dotlottie-wc>' +
          '<p>Generating quote...</p>';
        targetCard.appendChild(loader);
        activeProcessingCard = targetCard;
      }

      function renderServiceMeta() {
        ['priority', 'express', 'economy'].forEach(function (level) {
          var metaEl = document.getElementById('service-meta-' + level);
          var btnEl = createForm.querySelector('.service-generate-btn[data-service-level="' + level + '"]');
          var ctaEl = btnEl ? btnEl.closest('.service-cta') : null;
          if (!metaEl) return;
          var record = processedByLevel[level];
          if (!record || !record.ready) {
            metaEl.innerHTML = '';
            if (btnEl) {
              btnEl.hidden = false;
              btnEl.style.display = '';
            }
            if (ctaEl) ctaEl.style.display = '';
            return;
          }
          var price = (record.price !== null && typeof record.price !== 'undefined') ? ('$' + Number(record.price).toFixed(2)) : '';
          var durationDays = (record.duration !== null && typeof record.duration !== 'undefined' && String(record.duration) !== '')
            ? Number(record.duration)
            : null;
          var duration = (durationDays !== null && !isNaN(durationDays) && durationDays > 0)
            ? (durationDays + ' day' + (durationDays === 1 ? '' : 's'))
            : '';
          var description = record.description_text ? String(record.description_text) : '';
          var pieces = [];
          if (duration) pieces.push('<div class="q-meta-item"><span class="q-meta-label">Expected Delivery</span><span class="q-meta-val">' + duration + '</span></div>');
          if (price) pieces.push('<div class="q-meta-item"><span class="q-meta-label">Quoted Cost</span><strong>' + price + '</strong></div>');
          if (description) pieces.push('<span class="q-meta-desc">' + description + '</span>');
          metaEl.innerHTML = pieces.join('');
          if (btnEl) {
            btnEl.hidden = true;
            btnEl.style.display = 'none';
          }
          if (ctaEl) ctaEl.style.display = 'none';
        });
      }

      function syncQuoteHiddenFields() {
        var current = selectedServiceLevel();
        var record = processedByLevel[current];
        if (record && record.ready) {
          if (quoteRequestIdInput) quoteRequestIdInput.value = String(record.id || 0);
          if (quoteServiceLevelInput) quoteServiceLevelInput.value = current;
        } else {
          if (quoteRequestIdInput) quoteRequestIdInput.value = '0';
          if (quoteServiceLevelInput) quoteServiceLevelInput.value = '';
        }
      }

      function enforceServiceSelectionRules() {
        var readyLevels = Object.keys(processedByLevel).filter(function (k) {
          return processedByLevel[k] && processedByLevel[k].ready;
        });
        var readySet = new Set(readyLevels);

        serviceRadios.forEach(function (radio) {
          var level = mapUiToApiLevel(radio.value);
          var ready = readySet.has(level);
          radio.disabled = !ready;
          var card = radio.closest('.service-option');
          if (card) card.classList.toggle('is-disabled', !ready);
          if (!ready && radio.checked) radio.checked = false;
        });

        var activeChecked = createForm.querySelector('input[type="radio"][name="service_type"]:checked:not(:disabled)');
        if (!activeChecked && readyLevels.length > 0) {
          var preferredLevel = (quoteServiceLevelInput && quoteServiceLevelInput.value) ? mapApiToUiLevel(quoteServiceLevelInput.value) : '';
          var preferredRadio = preferredLevel ? createForm.querySelector('input[type="radio"][name="service_type"][value="' + preferredLevel + '"]:not(:disabled)') : null;
          if (preferredRadio) preferredRadio.checked = true;
          else {
            var firstReadyLevel = mapApiToUiLevel(readyLevels[0]);
            var firstReadyRadio = createForm.querySelector('input[type="radio"][name="service_type"][value="' + firstReadyLevel + '"]:not(:disabled)');
            if (firstReadyRadio) firstReadyRadio.checked = true;
          }
        }

        syncRadioCardState('service_type');
        syncQuoteHiddenFields();
        syncPickupSummary();
      }

      function loadProcessedList() {
        return fetch('/shipping/create/quote-api.php?action=list', { credentials: 'same-origin' })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (!data || !data.ok || !Array.isArray(data.records)) return;
            processedByLevel = {};
            data.records.forEach(function (record) {
              if (!record || !record.service_level) return;
              if (processedByLevel[record.service_level]) return;
              processedByLevel[record.service_level] = record;
            });
            renderServiceMeta();
            syncQuoteHiddenFields();
            enforceServiceSelectionRules();
            syncPickupSummary();
          });
      }

      function requestServiceProcessing(level) {
        var body = new URLSearchParams();
        body.set('action', 'request');
        body.set('service_level', level);
        return fetch('/shipping/create/quote-api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body.toString(),
          credentials: 'same-origin'
        }).then(function (res) { return res.json(); });
      }

      function fetchServiceStatus(requestId) {
        return fetch('/shipping/create/quote-api.php?action=status&request_id=' + encodeURIComponent(requestId), {
          credentials: 'same-origin'
        }).then(function (res) { return res.json(); });
      }

      function finalizeProcessedRecord(record) {
        if (!record || !record.service_level) return;
        processedByLevel[record.service_level] = record;
        renderServiceMeta();
        syncQuoteHiddenFields();
        enforceServiceSelectionRules();
        syncPickupSummary();
        setServiceStatus('Quote generated successfully. You can select this service and continue.', false, false);
      }

      runServiceProcessing = function (level) {
        if (!level || processingInFlight) return;
        debugQuote('runServiceProcessing start', { level: level });
        setProcessingUi(true, level);
        setServiceStatus('Generating quote for selected service...', false, false);

        requestServiceProcessing(level)
          .then(function (data) {
            debugQuote('request response', data);
            debugger;
            if (!data || !data.ok) throw new Error((data && data.message) ? data.message : 'Could not start service processing.');

            if (data.already_exists) {
              debugQuote('request reused existing quote record', {
                requestId: data.request_id,
                ready: !!data.ready,
                serviceLevel: level
              });
            }
            if (data.email_dispatched === false) {
              debugQuote('admin email dispatch failed', {
                requestId: data.request_id,
                error: data.email_error || null,
                httpCode: data.email_http_code || null
              });
            }

            if (data.already_exists && data.ready && data.record) {
              finalizeProcessedRecord(data.record);
              setProcessingUi(false);
              return;
            }

            var requestId = Number(data.request_id || (data.record ? data.record.id : 0) || 0);
            if (!requestId) throw new Error('Invalid processing request id.');

            var pollIndex = 0;
            var pollOnce = function () {
              if (pollIndex >= pollDelays.length) {
                debugQuote('poll exhausted; forcing reload');
                setServiceStatus('Service still processing. Reloading to retry...', true, false);
                setTimeout(function () { window.location.reload(); }, 400);
                return;
              }
              var waitMs = pollDelays[pollIndex++];
              debugQuote('poll scheduled', { requestId: requestId, waitMs: waitMs, attempt: pollIndex });
              setTimeout(function () {
                fetchServiceStatus(requestId)
                  .then(function (statusData) {
                    debugQuote('status response', statusData);
                    if (statusData && statusData.ok && statusData.ready && statusData.record) {
                      finalizeProcessedRecord(statusData.record);
                      window.location.reload();
                      return;
                    }
                    pollOnce();
                  })
                  .catch(function () { pollOnce(); });
              }, waitMs);
            };
            pollOnce();
          })
          .catch(function (err) {
            debugQuote('runServiceProcessing error', err);
            setServiceStatus(err && err.message ? err.message : 'Quote generation failed.', true, false);
            setProcessingUi(false);
          });
      };

      document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('.service-generate-btn') : null;
        if (!btn) return;
        if (!createForm || !createForm.contains(btn)) return;
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        window.startServiceQuote(btn);
      }, true);

      createForm.querySelectorAll('.service-level-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
          if (!item.classList.contains('is-disabled')) return;
          if (e.target && e.target.closest('.service-generate-btn')) return;
          e.preventDefault();
          e.stopPropagation();
        });
      });

      if (serviceQuoteHint) {
        serviceQuoteHint.addEventListener('click', function (e) {
          e.preventDefault();
          var selected = createForm.querySelector('input[type="radio"][name="service_type"]:checked');
          var selectedLevel = selected ? mapUiToApiLevel(selected.value) : '';
          var targetBtn = null;
          if (selectedLevel) targetBtn = createForm.querySelector('.service-generate-btn[data-service-level="' + selectedLevel + '"]:not([hidden])');
          if (!targetBtn) targetBtn = createForm.querySelector('.service-generate-btn:not([hidden])');
          if (targetBtn) targetBtn.click();
        });
      }

      serviceRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
          if (radio.disabled) radio.checked = false;
          syncQuoteHiddenFields();
          syncPickupSummary();
        });
      });

      if (createForm) {
        createForm.addEventListener('submit', function (e) {
          var stepInput = createForm.querySelector('input[name="current_step"]');
          if (!stepInput || stepInput.value !== '2') return;

          var selectedRadio = createForm.querySelector('input[type="radio"][name="service_type"]:checked');
          var selectedLevel = selectedRadio ? mapUiToApiLevel(selectedRadio.value) : '';
          var quoteId = quoteRequestIdInput ? Number(quoteRequestIdInput.value || 0) : 0;
          var quoteLevel = quoteServiceLevelInput ? String(quoteServiceLevelInput.value || '').toLowerCase() : '';

          if (!selectedRadio || selectedRadio.disabled || !selectedLevel || quoteId <= 0 || quoteLevel !== selectedLevel) {
            e.preventDefault();
            setServiceStatus('No service has been selected. Select a quoted service and continue.', true, true);
            var scrollTarget = serviceCard || (processingOverlay ? processingOverlay.closest('.ship-card') : null);
            if (scrollTarget) scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
      }

      loadProcessedList()
        .then(function () {
          setServiceStatus('Get Quote for a service to unlock and select it for the next step.', false, false);
        })
        .catch(function () {
          enforceServiceSelectionRules();
          setServiceStatus('Get Quote for a service to unlock and select it for the next step.', false, false);
        });
    }

    document.querySelectorAll('.service-generate-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (window.startServiceQuote) return;
        e.preventDefault();
      });
    });

    var packagingToggle = document.querySelector('.js-packaging-toggle');
    if (packagingToggle) {
      var hiddenInput = packagingToggle.querySelector('.js-packaging-input');
      var buttons = packagingToggle.querySelectorAll('.seg-btn');
      if (hiddenInput && buttons.length) {
        function setActive(value) {
          hiddenInput.value = value;
          buttons.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-packaging-value') === value);
          });
        }

        buttons.forEach(function (btn) {
          btn.addEventListener('click', function () {
            var value = btn.getAttribute('data-packaging-value');
            if (value) setActive(value);
          });
        });
      }
    }

    var paymentToggle = document.querySelector('.js-payment-toggle');
    if (paymentToggle) {
      var paymentInput = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.js-payment-method-input') : null;
      var cardPane = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.payment-mode-card') : null;
      var cryptoPane = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.payment-mode-crypto') : null;
      var toggleBtns = paymentToggle.querySelectorAll('.seg-btn[data-payment-value]');
      var cardFields = paymentToggle.parentElement ? paymentToggle.parentElement.querySelectorAll('select[name="card_type"], input[name="card_number"], input[name="card_expiry"], input[name="card_cvv"], input[name="cardholder_name"]') : [];
      var cryptoAssetSelect = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.js-crypto-asset') : null;
      var cryptoWalletInput = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.js-crypto-wallet') : null;
      var cryptoCopyBtn = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.js-crypto-copy') : null;
      var cryptoProofInput = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('input[name="crypto_payment_proof"]') : null;

      function setCardFieldsEnabled(enabled) {
        cardFields.forEach(function (field) {
          field.disabled = !enabled;
          if (enabled) {
            field.setAttribute('required', 'required');
          } else {
            field.removeAttribute('required');
          }
        });
      }

      function setCryptoFieldsEnabled(enabled) {
        if (cryptoAssetSelect) cryptoAssetSelect.disabled = !enabled;
        if (cryptoWalletInput) {
          cryptoWalletInput.disabled = !enabled;
          if (enabled) cryptoWalletInput.setAttribute('required', 'required');
          else cryptoWalletInput.removeAttribute('required');
        }
        if (cryptoProofInput) {
          cryptoProofInput.disabled = !enabled;
          var hasExistingProof = cryptoProofInput.getAttribute('data-has-existing-proof') === '1';
          if (enabled && !hasExistingProof) {
            cryptoProofInput.setAttribute('required', 'required');
          } else {
            cryptoProofInput.removeAttribute('required');
          }
        }
      }

      function syncCryptoWalletFromSelect() {
        if (!cryptoAssetSelect || !cryptoWalletInput) return;
        var selectedOption = cryptoAssetSelect.options[cryptoAssetSelect.selectedIndex];
        var wallet = selectedOption ? (selectedOption.getAttribute('data-wallet') || '') : '';
        cryptoWalletInput.value = wallet;
      }

      function setPaymentMode(mode) {
        var cardBtn = paymentToggle.querySelector('.seg-btn[data-payment-value="card"]');
        var activeMode = (mode === 'crypto') ? 'crypto' : 'card';
        if (activeMode === 'card' && cardBtn && cardBtn.disabled) {
          activeMode = 'crypto';
        }
        if (paymentInput) paymentInput.value = activeMode;
        toggleBtns.forEach(function (btn) {
          btn.classList.toggle('active', btn.getAttribute('data-payment-value') === activeMode);
        });
        if (cardPane) cardPane.classList.toggle('is-hidden', activeMode !== 'card');
        if (cryptoPane) cryptoPane.classList.toggle('is-hidden', activeMode !== 'crypto');
        setCardFieldsEnabled(activeMode === 'card');
        setCryptoFieldsEnabled(activeMode === 'crypto');
      }

      var cvvHelpTrigger = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.js-cvv-help-trigger') : null;
      var cvvHelpPopover = paymentToggle.parentElement ? paymentToggle.parentElement.querySelector('.js-cvv-help-popover') : null;
      var cardValidateFields = paymentToggle.parentElement ? paymentToggle.parentElement.querySelectorAll('.js-card-validate') : [];

      function ensureCardErrorNode(field) {
        if (!field) return null;
        var existing = field.parentElement ? field.parentElement.querySelector('.card-field-error[data-for="' + field.name + '"]') : null;
        if (existing) return existing;
        var errorEl = document.createElement('p');
        errorEl.className = 'card-field-error';
        errorEl.setAttribute('data-for', field.name || '');
        if (field.parentElement) field.parentElement.appendChild(errorEl);
        return errorEl;
      }

      function setCardFieldError(field, message) {
        var errorEl = ensureCardErrorNode(field);
        if (!errorEl) return;
        if (message) {
          field.classList.add('card-input-invalid');
          field.setAttribute('aria-invalid', 'true');
          errorEl.textContent = message;
          errorEl.classList.add('is-visible');
        } else {
          field.classList.remove('card-input-invalid');
          field.removeAttribute('aria-invalid');
          errorEl.textContent = '';
          errorEl.classList.remove('is-visible');
        }
      }

      function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
      }

      function validateCardField(field) {
        if (!field || field.disabled) {
          setCardFieldError(field, '');
          return true;
        }

        var label = field.getAttribute('data-card-label') || field.name || 'This field';
        var rule = field.getAttribute('data-card-rule') || 'required';
        var value = String(field.value || '').trim();
        var message = '';

        if (rule === 'required') {
          if (!value) message = label + ' is required.';
        } else if (rule === 'card_number') {
          var digits = onlyDigits(value);
          if (!digits) message = label + ' is required.';
          else if (digits.length < 13 || digits.length > 19) message = 'Enter a valid card number.';
        } else if (rule === 'expiry') {
          if (!value) message = label + ' is required.';
          else {
            var m = /^(\d{2})\/(\d{2})$/.exec(value);
            if (!m) message = 'Use MM/YY format.';
            else {
              var mm = Number(m[1]);
              var yy = Number(m[2]);
              var now = new Date();
              var currentYY = now.getFullYear() % 100;
              var currentMM = now.getMonth() + 1;
              if (mm < 1 || mm > 12) message = 'Enter a valid month.';
              else if (yy < currentYY || (yy === currentYY && mm < currentMM)) message = 'Card expiry date is in the past.';
            }
          }
        } else if (rule === 'cvv') {
          var cvvDigits = onlyDigits(value);
          if (!cvvDigits) message = label + ' is required.';
          else if (cvvDigits.length < 3 || cvvDigits.length > 4) message = 'CVV must be 3 or 4 digits.';
        } else if (rule === 'name') {
          if (!value) message = label + ' is required.';
          else if (value.length < 2) message = 'Enter a valid cardholder name.';
        }

        setCardFieldError(field, message);
        return message === '';
      }

      function closeCvvPopover() {
        if (!cvvHelpPopover) return;
        cvvHelpPopover.classList.remove('is-open');
        cvvHelpPopover.setAttribute('aria-hidden', 'true');
      }

      if (cvvHelpTrigger && cvvHelpPopover) {
        cvvHelpTrigger.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var willOpen = !cvvHelpPopover.classList.contains('is-open');
          if (willOpen) {
            cvvHelpPopover.classList.add('is-open');
            cvvHelpPopover.setAttribute('aria-hidden', 'false');
          } else {
            closeCvvPopover();
          }
        });

        document.addEventListener('click', function (e) {
          if (!cvvHelpPopover.classList.contains('is-open')) return;
          if (cvvHelpPopover.contains(e.target)) return;
          if (cvvHelpTrigger.contains(e.target)) return;
          closeCvvPopover();
        });
      }

      if (cardValidateFields && cardValidateFields.length) {
        cardValidateFields.forEach(function (field) {
          field.addEventListener('blur', function () {
            validateCardField(field);
          });
          field.addEventListener('input', function () {
            if (field.classList.contains('card-input-invalid')) validateCardField(field);
          });
        });

        if (createForm) {
          createForm.addEventListener('submit', function (e) {
            var stepInput = createForm.querySelector('input[name="current_step"]');
            if (!stepInput || stepInput.value !== '4') return;
            var currentMode = paymentInput ? paymentInput.value : 'card';
            if (currentMode !== 'card') return;

            var allValid = true;
            cardValidateFields.forEach(function (field) {
              if (!validateCardField(field)) allValid = false;
            });
            if (!allValid) {
              e.preventDefault();
              var firstInvalid = createForm.querySelector('.card-input-invalid');
              if (firstInvalid && firstInvalid.scrollIntoView) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
            }
          });
        }
      }

      toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (btn.disabled) return;
          setPaymentMode(btn.getAttribute('data-payment-value'));
        });
      });

      if (cryptoAssetSelect) {
        cryptoAssetSelect.addEventListener('change', syncCryptoWalletFromSelect);
        syncCryptoWalletFromSelect();
      }

      if (cryptoCopyBtn && cryptoWalletInput) {
        cryptoCopyBtn.addEventListener('click', function () {
          var text = cryptoWalletInput.value || '';
          if (!text) return;
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
              cryptoCopyBtn.classList.add('is-copied');
              setTimeout(function () { cryptoCopyBtn.classList.remove('is-copied'); }, 1200);
            });
            return;
          }
          cryptoWalletInput.focus();
          cryptoWalletInput.select();
          try {
            document.execCommand('copy');
          } catch (e) {}
        });
      }

      setPaymentMode(paymentInput ? paymentInput.value : 'card');
    }
  });
})();
