/**
 * REPAF Multi-Step Wizard
 */

(function () {
  "use strict";

  /**
   * =========================================================
   * HTML escape helper
   * =========================================================
   *
   * Converts the five characters that have special meaning in
   * HTML (&, <, >, ", ') into their safe entity equivalents.
   *
   * Use this on EVERY value that originates from user input
   * before inserting it into innerHTML. This prevents XSS
   * attacks where a user enters something like:
   *   <img src=x onerror="alert(document.cookie)">
   * as their name or address.
   *
   * @param  {*}      value  Any value (will be cast to string).
   * @return {string}        HTML-safe string.
   */
  function esc(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function parseMoney(v) {
    return parseFloat(String(v).replace(/,/g, "")) || 0;
  }

  function formatMoney(v) {
    var s = String(v).replace(/[^0-9.]/g, "");
    var dot = s.indexOf(".");
    if (dot !== -1) {
      s = s.slice(0, dot + 1) + s.slice(dot + 1).replace(/\./g, "");
    }
    var parts = s.split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.length > 1 ? parts[0] + "." + parts[1].slice(0, 2) : parts[0];
  }

  var form = document.getElementById("repaf-wizard-form");

  // Stop if shortcode form is not on this page.
  if (!form) return;

  var currentStep = 1;
  var totalSteps = 10;

  var editMode = form.dataset.repafMode === "edit";

  var steps = form.querySelectorAll(".repaf-step");
  var indicators = form.querySelectorAll(".repaf-step-indicator");
  var errorMessage = form.querySelector(".repaf-error-message");

  /**
   * Mandatory privacy consent gate (buyer mode only).
   * The server repeats this validation, so removing the modal in developer
   * tools cannot produce an accepted submission without both consent values.
   */
  function initializePrivacyConsentGate() {
    var modal = document.getElementById("repaf-privacy-consent-modal");
    if (editMode || !modal) return;

    var privacyConsent = modal.querySelector("#repaf_privacy_policy_consent");
    var dataConsent = modal.querySelector("#repaf_data_privacy_consent");
    var continueButton = modal.querySelector(
      ".repaf-privacy-consent-modal__continue",
    );
    var helpText = modal.querySelector(".repaf-privacy-consent-modal__help");
    var gateOpen = true;

    if (!privacyConsent || !dataConsent || !continueButton) return;

    document.body.classList.add("repaf-privacy-consent-open");
    form.setAttribute("inert", "");
    form.setAttribute("aria-hidden", "true");

    function updateConsentState() {
      var ready = privacyConsent.checked && dataConsent.checked;
      continueButton.disabled = !ready;
      if (helpText) {
        helpText.textContent = ready
          ? "You may now continue to the form."
          : "Both boxes must be selected to continue.";
      }
    }

    privacyConsent.addEventListener("change", updateConsentState);
    dataConsent.addEventListener("change", updateConsentState);

    continueButton.addEventListener("click", function () {
      if (!privacyConsent.checked || !dataConsent.checked) {
        updateConsentState();
        return;
      }

      gateOpen = false;
      modal.classList.remove("is-visible");
      modal.setAttribute("aria-hidden", "true");
      form.removeAttribute("inert");
      form.removeAttribute("aria-hidden");
      document.body.classList.remove("repaf-privacy-consent-open");

      var firstField = form.querySelector(
        'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])',
      );
      if (firstField) firstField.focus();
    });

    modal.addEventListener("keydown", function (event) {
      if (!gateOpen || event.key !== "Tab") return;

      var focusable = Array.prototype.slice.call(
        modal.querySelectorAll(
          'a[href], input:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
      );
      if (!focusable.length) return;

      var first = focusable[0];
      var last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    document.addEventListener("focusin", function keepFocusInConsentModal(event) {
      if (!gateOpen) {
        document.removeEventListener("focusin", keepFocusInConsentModal);
        return;
      }

      if (!modal.contains(event.target)) {
        privacyConsent.focus();
      }
    });

    updateConsentState();
    window.setTimeout(function () {
      privacyConsent.focus();
    }, 0);
  }

  initializePrivacyConsentGate();

  /**
   * Get selected buyer civil status.
   */
  function getBuyerCivilStatus() {
    var checked = form.querySelector('[name="buyer_civil_status"]:checked');
    return checked ? checked.value : "";
  }

  /**
   * Check if buyer is married.
   */
  function isBuyerMarried() {
    return getBuyerCivilStatus() === "Married";
  }

  /**
   * Check if the co-owner/co-borrower is married.
   */
  function isCoOwnerMarried() {
    var checked = form.querySelector('[name="co_civil_status"]:checked');
    return checked ? checked.value === "Married" : false;
  }

  /**
   * Show validation message.
   */
  function showError(message) {
    if (!errorMessage) {
      alert(message);
      return;
    }

    errorMessage.textContent = message;
    errorMessage.classList.add("is-visible");
  }

  /**
   * Clear validation message.
   */
  function clearError() {
    if (!errorMessage) return;

    errorMessage.textContent = "";
    errorMessage.classList.remove("is-visible");
  }

  /**
   * Remove invalid field styling.
   */
  function clearInvalidFields() {
    form.querySelectorAll(".repaf-field.is-invalid").forEach(function (field) {
      field.classList.remove("is-invalid");
    });
  }

  function cleanValidationText(value) {
    return String(value || "")
      .replace(/\*/g, "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function getFieldValidationLabel(field) {
    var label = cleanValidationText(field.dataset.validationLabel);
    var wrapper = field.closest(".repaf-field");

    if (!label && field.id) {
      var explicitLabel = form.querySelector('label[for="' + field.id + '"]');
      if (explicitLabel) label = cleanValidationText(explicitLabel.textContent);
    }

    if (!label && wrapper) {
      var wrapperLabel = wrapper.querySelector("label, .repaf-radio-label");
      if (wrapperLabel) label = cleanValidationText(wrapperLabel.textContent);
    }

    if (!label) {
      label = cleanValidationText(
        field.getAttribute("aria-label") ||
          field.getAttribute("placeholder") ||
          field.name,
      );
    }

    var section = field.closest(".repaf-fin-section, .repaf-step");
    var sectionTitle = "";

    if (section) {
      var heading = section.querySelector(
        ".repaf-fin-heading, h3, .repaf-section-title",
      );
      if (heading) sectionTitle = cleanValidationText(heading.textContent);
    }

    if (sectionTitle && label.indexOf(sectionTitle) === -1) {
      return label + " (" + sectionTitle + ")";
    }

    return label;
  }

  function showValidationSummary(invalidFields) {
    if (!errorMessage) {
      alert("Please complete: " + invalidFields.map(getFieldValidationLabel).join(", "));
      return;
    }

    var labels = [];
    invalidFields.forEach(function (field) {
      var label = getFieldValidationLabel(field);
      if (label && labels.indexOf(label) === -1) {
        labels.push(label);
      }
    });

    errorMessage.textContent = "";

    var title = document.createElement("strong");
    title.className = "repaf-error-message__title";
    title.textContent = "Please fill out the highlighted required field" +
      (labels.length === 1 ? "" : "s") +
      " before continuing:";
    errorMessage.appendChild(title);

    var list = document.createElement("ul");
    list.className = "repaf-error-message__list";

    labels.slice(0, 6).forEach(function (label) {
      var item = document.createElement("li");
      item.textContent = label;
      list.appendChild(item);
    });

    if (labels.length > 6) {
      var remaining = document.createElement("li");
      remaining.textContent = "And " + (labels.length - 6) + " more field" +
        (labels.length - 6 === 1 ? "" : "s") +
        ".";
      list.appendChild(remaining);
    }

    errorMessage.appendChild(list);
    errorMessage.classList.add("is-visible");
  }

  /**
   * Validate required fields inside current step only.
   */
  function validateCurrentStep() {
    clearError();
    clearInvalidFields();

    var activeStep = form.querySelector(
      '.repaf-step[data-step="' + currentStep + '"]',
    );

    if (!activeStep) return true;

    var requiredFields = activeStep.querySelectorAll("[required]");
    var invalidFields = [];
    var checkedRadioGroups = {};

    requiredFields.forEach(function (field) {
      if (field.disabled) return;

      if (field.type === "radio") {
        if (checkedRadioGroups[field.name]) return;

        checkedRadioGroups[field.name] = true;

        if (!activeStep.querySelector('[name="' + field.name + '"]:checked')) {
          invalidFields.push(field);
        }

        return;
      }

      if (!field.value.trim()) {
        invalidFields.push(field);
        return;
      }

      // Use explicit RegExp for pattern attributes — more reliable than
      // checkValidity() for type="tel" which skips patternMismatch in some browsers.
      var pattern = field.getAttribute("pattern");
      if (pattern && !new RegExp(pattern).test(field.value)) {
        invalidFields.push(field);
        return;
      }

      if (!field.checkValidity()) {
        invalidFields.push(field);
      }
    });

    // Validate optional fields with a pattern (TIN, Home Tel, etc.) and
    // optional email fields. Skips disabled fields (hidden conditional sections).
    activeStep
      .querySelectorAll(
        "[pattern]:not([required]), input[type='email']:not([required]), [data-repaf-restricted-list]:not([required])",
      )
      .forEach(function (field) {
        if (field.disabled) return;
        if (!field.value.trim()) return;
        if (invalidFields.indexOf(field) !== -1) return;
        var pattern = field.getAttribute("pattern");
        if (pattern) {
          if (!new RegExp(pattern).test(field.value)) {
            invalidFields.push(field);
          }
        } else if (!field.checkValidity()) {
          invalidFields.push(field);
        }
      });

    if (invalidFields.length === 0) {
      return true;
    }

    invalidFields.forEach(function (field) {
      var wrapper = field.closest(".repaf-field");
      if (wrapper) wrapper.classList.add("is-invalid");
    });

    showValidationSummary(invalidFields);

    invalidFields[0].scrollIntoView({
      behavior: "smooth",
      block: "center",
    });

    return false;
  }

  /**
   * Check whether the user said Yes to having a co-owner.
   */
  function hasCoOwner() {
    var checked = form.querySelector('[name="has_co_owner"]:checked');
    return checked ? checked.value === "yes" : true; // default to showing steps if unanswered
  }

  /**
   * Check whether the buyer has an Authorized Representative.
   */
  function hasRepresentative() {
    var checked = form.querySelector('[name="has_representative"]:checked');
    return checked ? checked.value === "yes" : false; // default to hidden until answered
  }

  /**
   * Keep required attributes, accessibility metadata, and the visible
   * asterisk in sync for fields whose requirement is conditional.
   */
  function setConditionalRequired(field, shouldRequire) {
    if (!field) return;

    var wrapper = field.closest(".repaf-field");
    var label = wrapper ? wrapper.querySelector("label") : null;
    var marker = label
      ? label.querySelector(".repaf-conditional-required")
      : null;

    if (shouldRequire) {
      field.setAttribute("required", "required");
      field.setAttribute("aria-required", "true");

      if (label && !marker) {
        marker = document.createElement("span");
        marker.className = "repaf-required repaf-conditional-required";
        marker.textContent = " *";
        label.appendChild(marker);
      }
    } else {
      field.removeAttribute("required");
      field.removeAttribute("aria-required");
      if (marker) marker.remove();
    }

    document.dispatchEvent(
      new CustomEvent("repaf:conditional-required", {
        detail: { name: field.name, required: shouldRequire },
      }),
    );
  }

  function syncCitizenshipFields() {
    form.querySelectorAll("[data-citizenship-select]").forEach(function (select) {
      var otherField = form.querySelector(
        '[data-citizenship-other-for="' + select.name + '"]',
      );
      if (!otherField) return;

      var shouldShow = select.value === "Others";
      var wrapper = otherField.closest(".repaf-field");
      if (wrapper) {
        wrapper.hidden = !shouldShow;
        wrapper.classList.toggle("is-hidden", !shouldShow);
      }
      otherField.disabled = select.disabled || !shouldShow;
      setConditionalRequired(otherField, shouldShow && !select.disabled);

      if (!shouldShow) otherField.value = "";
    });
  }

  form.addEventListener("change", function (event) {
    if (event.target.matches("[data-citizenship-select]")) {
      syncCitizenshipFields();
    }
  });

  /**
   * The co-owner representative subsection is optional. Treat a supplied
   * name as confirmation that this person exists and require their permanent
   * address only in that case.
   */
  function updateCoRepresentativeRequiredState() {
    var representativeNameFields = [
      "co_rep_last_name",
      "co_rep_first_name",
      "co_rep_middle_name",
      "co_rep_suffix",
    ];
    var hasRepresentativeName = representativeNameFields.some(function (fieldName) {
      var field = form.querySelector('[name="' + fieldName + '"]');
      return Boolean(field && field.value.trim());
    });
    var shouldRequire = Boolean(hasCoOwner() && hasRepresentativeName);
    var addressFields = [
      "co_rep_barangay",
      "co_rep_city_municipality",
      "co_rep_province_state",
      "co_rep_country",
      "co_rep_zip",
    ];

    addressFields.forEach(function (fieldName) {
      setConditionalRequired(
        form.querySelector('[name="' + fieldName + '"]'),
        shouldRequire,
      );
    });
  }

  /**
   * Spouse and Attorney-in-Fact are independent sections. A married
   * co-owner's spouse must never be assumed to be their representative.
   */
  function updateCoOwnerAdditionalFieldsState() {
    var spouseFields = document.getElementById("repaf-co-spouse-fields");
    var representativeFields = document.getElementById(
      "repaf-co-representative-fields",
    );
    var coOwnerEnabled = hasCoOwner();
    var coOwnerMarried = coOwnerEnabled && isCoOwnerMarried();
    var representativeHome = document.getElementById(
      "repaf-co-representative-home",
    );
    var representativeAfterSpouse = document.getElementById(
      "repaf-co-representative-after-spouse",
    );

    if (representativeFields) {
      var representativeDestination = coOwnerMarried
        ? representativeAfterSpouse
        : representativeHome;
      if (
        representativeDestination &&
        representativeFields.parentElement !== representativeDestination
      ) {
        representativeDestination.appendChild(representativeFields);
      }
    }

    if (spouseFields) {
      spouseFields.style.display = coOwnerMarried ? "" : "none";
      spouseFields
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !coOwnerMarried;
        });
    }

    if (representativeFields) {
      representativeFields.style.display = coOwnerEnabled ? "" : "none";
      representativeFields
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !coOwnerEnabled;
        });
    }

    updateCoSpouseRepresentativeCopyState();
  }

  function syncSpouseRepresentativeSelection() {
    var yesRadio = form.querySelector('[name="has_representative"][value="yes"][type="radio"]');
    var noRadio = form.querySelector('[name="has_representative"][value="no"][type="radio"]');

    [yesRadio, noRadio].forEach(function (radio) {
      if (!radio) return;
      radio.disabled = false;
      radio.removeAttribute("aria-disabled");
    });
  }

  function isStepSkipped(stepNumber) {
    // Buyer mode does not render salesperson-only sections. Treat any absent
    // intermediate section as skipped so navigation moves straight to review.
    if (!form.querySelector('.repaf-step[data-step="' + stepNumber + '"]')) {
      return true;
    }
    if (stepNumber === 2 && !isBuyerMarried()) return true;
    if (stepNumber === 5 && (!hasCoOwner() || !isCoOwnerMarried())) return true;
    return false;
  }

  function getNextStep() {
    var nextStep = Math.min(currentStep + 1, totalSteps);
    while (nextStep < totalSteps && isStepSkipped(nextStep)) {
      nextStep += 1;
    }
    return nextStep;
  }

  /**
   * Go back correctly when skipped steps are involved.
   */
  function getPreviousStep() {
    var previousStep = Math.max(currentStep - 1, 1);
    while (previousStep > 1 && isStepSkipped(previousStep)) {
      previousStep -= 1;
    }
    return previousStep;
  }

  /**
   * Disable spouse fields only when the buyer
   * is NOT married.
   *
   * Hide and disable the inline co-owner fields when the user chooses No,
   * and disable the related co-owner spouse step as well.
   */
  function updateConditionalFieldsState() {
    syncSpouseRepresentativeSelection();

    var spouseStep = form.querySelector('.repaf-step[data-step="2"]');
    var spouseWorkFields = document.getElementById("repaf-spouse-work-fields");

    if (spouseStep) {
      var shouldEnableSpouse = isBuyerMarried();
      spouseStep
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !shouldEnableSpouse;
        });
    }

    if (spouseWorkFields) {
      var shouldEnableSpouseWork = isBuyerMarried();
      spouseWorkFields.style.display = shouldEnableSpouseWork ? "" : "none";
      spouseWorkFields
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !shouldEnableSpouseWork;
        });
    }

    var shouldEnableCoOwner = hasCoOwner();
    var coOwnerFields = document.getElementById("repaf-co-owner-fields");
    var coOwnerSpouseStep = form.querySelector('.repaf-step[data-step="5"]');

    if (coOwnerFields) {
      coOwnerFields.hidden = !shouldEnableCoOwner;
      coOwnerFields.style.display = shouldEnableCoOwner ? "" : "none";
      coOwnerFields
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !shouldEnableCoOwner;
        });
    }

    if (coOwnerSpouseStep) {
      coOwnerSpouseStep
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !shouldEnableCoOwner;
        });
    }

    var coWorkFields = document.getElementById("repaf-co-work-fields");
    if (coWorkFields) {
      coWorkFields.style.display = shouldEnableCoOwner ? "" : "none";
      coWorkFields
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !shouldEnableCoOwner;
        });
    }

    updateCoOwnerAdditionalFieldsState();

    // Representative fields: show/hide based on has_representative.
    var repFieldsDiv = document.getElementById("repaf-rep-fields");
    var shouldShowRep = hasRepresentative();
    var repRequiredFields = [
      "rep_last_name",
      "rep_first_name",
      "rep_mobile",
      "rep_relationship",
      "rep_barangay",
      "rep_city_municipality",
      "rep_province_state",
      "rep_country",
      "rep_zip",
    ];

    if (repFieldsDiv) {
      repFieldsDiv.style.display = shouldShowRep ? "" : "none";
      repFieldsDiv
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
          field.disabled = !shouldShowRep;
          if (repRequiredFields.indexOf(field.name) !== -1) {
            setConditionalRequired(field, shouldShowRep);
          }
        });
    }

    updateCoRepresentativeRequiredState();
    ["buyer", "spouse", "co"].forEach(updateEmploymentBusinessFields);
    updateSpouseRepresentativeCopyState();
    updateCoPresentAddressLockState();
    syncCitizenshipFields();
  }
  /**
   * Income row definitions for total auto-calculation.
   * Each entry maps buyer/spouse/co inputs to the readonly total.
   */
  var FIN_INCOME_ROWS = [
    { buyer: "fin_buyer_salary",       spouse: "fin_spouse_salary",       co: "fin_co_salary",       total: "fin_total_salary" },
    { buyer: "fin_buyer_allowance",    spouse: "fin_spouse_allowance",    co: "fin_co_allowance",    total: "fin_total_allowance" },
    { buyer: "fin_buyer_commission",   spouse: "fin_spouse_commission",   co: "fin_co_commission",   total: "fin_total_commission" },
    { buyer: "fin_buyer_other_amount", spouse: "fin_spouse_other_amount", co: "fin_co_other_amount", total: "fin_total_other" },
  ];

  /**
   * Recalculate all income total fields by summing
   * enabled buyer + spouse + co columns per row.
   */
  function recalcFinancialTotals() {
    var personTotals = { buyer: 0, spouse: 0, co: 0 };

    FIN_INCOME_ROWS.forEach(function (row) {
      var bField = form.querySelector('[name="' + row.buyer + '"]');
      var sField = form.querySelector('[name="' + row.spouse + '"]');
      var cField = form.querySelector('[name="' + row.co + '"]');
      var tField = form.querySelector('[name="' + row.total + '"]');

      var buyerAmount = parseMoney(bField ? bField.value : "");
      var spouseAmount = sField && !sField.disabled ? parseMoney(sField.value) : 0;
      var coAmount = cField && !cField.disabled ? parseMoney(cField.value) : 0;

      personTotals.buyer += buyerAmount;
      personTotals.spouse += spouseAmount;
      personTotals.co += coAmount;

      if (tField) {
        var rowTotal = buyerAmount + spouseAmount + coAmount;
        tField.value = rowTotal > 0 ? formatMoney(rowTotal.toFixed(2)) : "";
      }
    });

    Object.keys(personTotals).forEach(function (person) {
      var totalField = form.querySelector('[name="fin_' + person + '_total"]');
      if (totalField) {
        totalField.value = personTotals[person] > 0
          ? formatMoney(personTotals[person].toFixed(2))
          : "";
      }
    });
  }

  /**
   * Show/hide and enable/disable the Spouse and Co-Borrower
   * income columns in the Financial Information step based on
   * whether the buyer is married and has a co-owner.
   */
  function updateFinancialColumnState() {
    var finStep = form.querySelector('.repaf-step[data-step="7"]');
    if (!finStep) return;

    var married  = isBuyerMarried();
    var coOwner  = hasCoOwner();

    finStep.querySelectorAll(".repaf-fin-col-spouse").forEach(function (cell) {
      cell.style.display = married ? "" : "none";
    });
    finStep.querySelectorAll('[data-fin-col="spouse"]').forEach(function (inp) {
      inp.disabled = !married;
      if (!married) inp.value = "";
    });

    finStep.querySelectorAll(".repaf-fin-col-co").forEach(function (cell) {
      cell.style.display = coOwner ? "" : "none";
    });
    finStep.querySelectorAll('[data-fin-col="co"]').forEach(function (inp) {
      inp.disabled = !coOwner;
      if (!coOwner) inp.value = "";
    });

    recalcFinancialTotals();
  }

  /**
   * Generic dynamic-row factory.
   * Handles Add / Remove and renumbers name attributes after removal.
   *
   * @param {string}   containerId   ID of the rows container element.
   * @param {string}   addBtnId      ID of the "Add" button.
   * @param {number}   maxRows       Maximum allowed rows.
   * @param {Function} buildRowHtml  Called with (index) → returns row HTML string.
   */
  function repafMakeDynamicRows(containerId, addBtnId, maxRows, buildRowHtml, onRowAdded, onRowsChanged) {
    var container = document.getElementById(containerId);
    var addBtn    = document.getElementById(addBtnId);
    if (!container || !addBtn) return;

    function getRowCount() {
      return container.querySelectorAll(".repaf-fin-row").length;
    }

    function refreshAddBtn() {
      addBtn.disabled = getRowCount() >= maxRows;
    }

    function renumberRows() {
      var rows = container.querySelectorAll(".repaf-fin-row");
      rows.forEach(function (row, i) {
        var newIndex = i + 1;
        row.dataset.rowIndex = newIndex;
        row.querySelectorAll("[name]").forEach(function (inp) {
          inp.name = inp.name.replace(/\[\d+\]/, "[" + newIndex + "]");
        });
      });
    }

    addBtn.addEventListener("click", function () {
      if (getRowCount() >= maxRows) return;
      var newIndex = getRowCount() + 1;
      var wrapper  = document.createElement("div");
      wrapper.innerHTML = buildRowHtml(newIndex);
      var newRow = wrapper.firstChild;
      container.appendChild(newRow);
      if (onRowAdded) onRowAdded(newRow);
      if (onRowsChanged) onRowsChanged(container);
      refreshAddBtn();
    });

    container.addEventListener("click", function (e) {
      if (e.target.classList.contains("repaf-fin-remove")) {
        var row = e.target.closest(".repaf-fin-row");
        if (row) {
          row.parentNode.removeChild(row);
          renumberRows();
          if (onRowsChanged) onRowsChanged(container);
          refreshAddBtn();
        }
      }
    });

    if (onRowsChanged) onRowsChanged(container);
    refreshAddBtn();
  }

  /**
   * Show active step and update progress circles.
   */
  function updateStepVisibility() {
    steps.forEach(function (step) {
      var stepNumber = parseInt(step.dataset.step, 10);
      step.classList.toggle("is-active", stepNumber === currentStep);
    });

    var visibleStepNumber = 0;

    indicators.forEach(function (indicator) {
      var stepNumber = parseInt(indicator.dataset.step, 10);
      var skipped = isStepSkipped(stepNumber);

      indicator.classList.toggle("is-active", stepNumber === currentStep);
      indicator.classList.toggle("is-complete", stepNumber < currentStep);
      indicator.style.display = skipped ? "none" : "";

      if (!skipped) {
        visibleStepNumber += 1;
        var stepTitle = indicator.dataset.stepTitle || "Form section";
        indicator.textContent = String(visibleStepNumber);
        indicator.setAttribute(
          "aria-label",
          "Step " + visibleStepNumber + ": " + stepTitle,
        );
      }
    });

    updateConditionalFieldsState();
    updateFinancialColumnState();

    clearError();
    clearInvalidFields();
  }

  /**
   * Move to a specific step.
   */
  function goToStep(stepNumber) {
    currentStep = stepNumber;

    updateStepVisibility();

    /**
     * Auto-generate review screen
     * before entering Step 7.
     */
    if (currentStep === 10) {
      populateReviewScreen();
    }

    form.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }

  // Admin/salesperson edit mode may jump directly to any visible section.
  if (editMode) {
    indicators.forEach(function (indicator) {
      indicator.addEventListener("click", function () {
        var stepNumber = parseInt(indicator.dataset.step, 10);
        if (!isNaN(stepNumber) && !isStepSkipped(stepNumber)) {
          goToStep(stepNumber);
        }
      });
    });
  }

  var addressFieldPairs = [
    ["buyer_permanent_address_line", "buyer_present_address_line"],
    ["buyer_permanent_barangay", "buyer_present_barangay"],
    ["buyer_permanent_city_municipality", "buyer_present_city_municipality"],
    ["buyer_permanent_province_state", "buyer_present_province_state"],
    ["buyer_permanent_country", "buyer_present_country"],
    ["buyer_permanent_zip", "buyer_present_zip"],
  ];

  var coAddressFieldPairs = [
    ["co_permanent_address_line", "co_present_address_line"],
    ["co_permanent_barangay", "co_present_barangay"],
    ["co_permanent_city_municipality", "co_present_city_municipality"],
    ["co_permanent_province_state", "co_present_province_state"],
    ["co_permanent_country", "co_present_country"],
    ["co_permanent_zip", "co_present_zip"],
  ];

  var spouseRepresentativeFieldPairs = [
    ["spouse_last_name", "rep_last_name"],
    ["spouse_first_name", "rep_first_name"],
    ["spouse_middle_name", "rep_middle_name"],
    ["spouse_suffix", "rep_suffix"],
    ["spouse_dob", "rep_dob"],
    ["spouse_age", "rep_age"],
    ["spouse_home_tel", "rep_home_tel"],
    ["spouse_mobile", "rep_mobile"],
    ["spouse_email", "rep_email"],
    ["spouse_tin", "rep_tin"],
    ["buyer_permanent_address_line", "rep_address_line"],
    ["buyer_permanent_barangay", "rep_barangay"],
    ["buyer_permanent_city_municipality", "rep_city_municipality"],
    ["buyer_permanent_province_state", "rep_province_state"],
    ["buyer_permanent_country", "rep_country"],
    ["buyer_permanent_zip", "rep_zip"],
  ];

  var coSpouseRepresentativeFieldPairs = [
    ["co_spouse_last_name", "co_rep_last_name"],
    ["co_spouse_first_name", "co_rep_first_name"],
    ["co_spouse_middle_name", "co_rep_middle_name"],
    ["co_spouse_suffix", "co_rep_suffix"],
    ["co_spouse_dob", "co_rep_dob"],
    ["co_spouse_age", "co_rep_age"],
    ["co_spouse_home_office_number", "co_rep_home_tel"],
    ["co_spouse_mobile", "co_rep_mobile"],
    ["co_spouse_email", "co_rep_email"],
    ["co_spouse_tin", "co_rep_tin"],
    ["co_permanent_address_line", "co_rep_address_line"],
    ["co_permanent_barangay", "co_rep_barangay"],
    ["co_permanent_city_municipality", "co_rep_city_municipality"],
    ["co_permanent_province_state", "co_rep_province_state"],
    ["co_permanent_country", "co_rep_country"],
    ["co_permanent_zip", "co_rep_zip"],
  ];

  function copyFieldValue(sourceName, targetName) {
    var source = form.querySelector('[name="' + sourceName + '"]');

    if (!source) return;

    setFieldValue(targetName, source.value);
  }

  function setFieldValue(targetName, value) {
    var target = form.querySelector('[name="' + targetName + '"]');

    if (!target) return;

    target.value = value;
    target.dispatchEvent(new Event("input", { bubbles: true }));
    target.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function copyPermanentAddressToPresent() {
    addressFieldPairs.forEach(function (fieldPair) {
      copyFieldValue(fieldPair[0], fieldPair[1]);
    });
  }

  function copyCoPermanentAddressToPresent() {
    coAddressFieldPairs.forEach(function (fieldPair) {
      copyFieldValue(fieldPair[0], fieldPair[1]);
    });
  }

  function copySpouseDetailsToRepresentative() {
    spouseRepresentativeFieldPairs.forEach(function (fieldPair) {
      copyFieldValue(fieldPair[0], fieldPair[1]);
    });
    setFieldValue("rep_relationship", "Spouse");
  }

  function copyCoSpouseDetailsToRepresentative() {
    coSpouseRepresentativeFieldPairs.forEach(function (fieldPair) {
      copyFieldValue(fieldPair[0], fieldPair[1]);
    });
    setFieldValue("co_rep_relationship", "Spouse");
  }

  function updatePresentAddressLockState() {
    var shouldLockPresentAddress = isSameAddressChecked();

    addressFieldPairs.forEach(function (fieldPair) {
      var presentField = form.querySelector('[name="' + fieldPair[1] + '"]');

      if (!presentField) {
        return;
      }

      presentField.readOnly = shouldLockPresentAddress;
    });

    document.dispatchEvent(
      new CustomEvent("repaf:address-lock-change", {
        detail: {
          countryName: "buyer_present_country",
          locked: shouldLockPresentAddress,
        },
      }),
    );

    if (shouldLockPresentAddress) {
      copyPermanentAddressToPresent();
    }
  }

  function isSameAddressChecked() {
    var sameAddressCheckbox = document.getElementById("repaf_same_address");

    return sameAddressCheckbox ? sameAddressCheckbox.checked : false;
  }

  function isCoSameAddressChecked() {
    var sameAddressCheckbox = document.getElementById("repaf_co_same_address");

    return sameAddressCheckbox ? sameAddressCheckbox.checked : false;
  }

  function updateCoPresentAddressLockState() {
    var checkbox = document.getElementById("repaf_co_same_address");
    var shouldLockPresentAddress = Boolean(
      checkbox && checkbox.checked && hasCoOwner(),
    );

    if (checkbox) checkbox.disabled = !hasCoOwner();

    coAddressFieldPairs.forEach(function (fieldPair) {
      var presentField = form.querySelector('[name="' + fieldPair[1] + '"]');
      if (presentField) presentField.readOnly = shouldLockPresentAddress;
    });

    document.dispatchEvent(
      new CustomEvent("repaf:address-lock-change", {
        detail: {
          countryName: "co_present_country",
          locked: shouldLockPresentAddress,
        },
      }),
    );

    if (shouldLockPresentAddress) copyCoPermanentAddressToPresent();
  }

  function isSameAsSpouseRepresentativeChecked() {
    var sameAsSpouseCheckbox = document.getElementById(
      "repaf_same_as_spouse_representative",
    );

    return sameAsSpouseCheckbox ? sameAsSpouseCheckbox.checked : false;
  }

  function updateSpouseRepresentativeCopyState() {
    var sameAsSpouseCheckbox = document.getElementById(
      "repaf_same_as_spouse_representative",
    );
    var sameAsSpouseRow = document.getElementById(
      "repaf_spouse_rep_copy_row",
    );
    var shouldShowCopy = isBuyerMarried() && hasRepresentative();
    var shouldUseSpouse = shouldShowCopy && sameAsSpouseCheckbox.checked;

    if (!sameAsSpouseCheckbox) {
      return;
    }

    if (sameAsSpouseRow) {
      sameAsSpouseRow.style.display = shouldShowCopy ? "" : "none";
    }

    sameAsSpouseCheckbox.disabled = !shouldShowCopy;

    if (!shouldShowCopy) {
      sameAsSpouseCheckbox.checked = false;
    }

    spouseRepresentativeFieldPairs.forEach(function (fieldPair) {
      var repField = form.querySelector('[name="' + fieldPair[1] + '"]');

      if (!repField) {
        return;
      }

      repField.readOnly = shouldUseSpouse;
      repField.setAttribute("aria-readonly", shouldUseSpouse ? "true" : "false");
    });

    var relationshipField = form.querySelector('[name="rep_relationship"]');

    if (relationshipField) {
      relationshipField.readOnly = shouldUseSpouse;
      relationshipField.setAttribute("aria-readonly", shouldUseSpouse ? "true" : "false");
    }

    document.dispatchEvent(
      new CustomEvent("repaf:address-lock-change", {
        detail: {
          countryName: "rep_country",
          locked: shouldUseSpouse,
        },
      }),
    );

    if (shouldUseSpouse) {
      copySpouseDetailsToRepresentative();
    }
  }

  function isSameAsCoSpouseRepresentativeChecked() {
    var checkbox = document.getElementById(
      "repaf_same_as_co_spouse_representative",
    );

    return checkbox ? checkbox.checked : false;
  }

  function updateCoSpouseRepresentativeCopyState() {
    var checkbox = document.getElementById(
      "repaf_same_as_co_spouse_representative",
    );
    var copyRow = document.getElementById("repaf_co_spouse_rep_copy_row");
    var shouldShowCopy = hasCoOwner() && isCoOwnerMarried();
    var shouldUseSpouse = Boolean(
      checkbox && shouldShowCopy && checkbox.checked,
    );

    if (!checkbox) return;

    if (copyRow) copyRow.style.display = shouldShowCopy ? "" : "none";

    checkbox.disabled = !shouldShowCopy;
    if (!shouldShowCopy) checkbox.checked = false;

    coSpouseRepresentativeFieldPairs.forEach(function (fieldPair) {
      var representativeField = form.querySelector(
        '[name="' + fieldPair[1] + '"]',
      );
      if (!representativeField) return;

      representativeField.readOnly = shouldUseSpouse;
      representativeField.setAttribute(
        "aria-readonly",
        shouldUseSpouse ? "true" : "false",
      );
    });

    var relationshipField = form.querySelector('[name="co_rep_relationship"]');
    if (relationshipField) {
      relationshipField.readOnly = shouldUseSpouse;
      relationshipField.setAttribute(
        "aria-readonly",
        shouldUseSpouse ? "true" : "false",
      );
    }

    document.dispatchEvent(
      new CustomEvent("repaf:address-lock-change", {
        detail: {
          countryName: "co_rep_country",
          locked: shouldUseSpouse,
        },
      }),
    );

    if (shouldUseSpouse) copyCoSpouseDetailsToRepresentative();
  }

  /**
   * Handle Next and Back buttons.
   */
  form.addEventListener("click", function (event) {
    if (event.target.classList.contains("repaf-next")) {
      if (!validateCurrentStep()) return;
      goToStep(getNextStep());
    }

    if (event.target.classList.contains("repaf-back")) {
      goToStep(getPreviousStep());
    }
  });

  /**
   * Prevent invalid final submit.
   */
  form.addEventListener("submit", function (event) {
    if (!validateCurrentStep()) {
      event.preventDefault();
    }
  });

  /**
   * Recalculate spouse-step visibility when civil status changes.
   */
  form
    .querySelectorAll('[name="buyer_civil_status"]')
    .forEach(function (radio) {
      radio.addEventListener("change", updateStepVisibility);
    });

  /**
   * Recalculate co-owner spouse/representative visibility when the
   * co-owner civil status changes.
   */
  form
    .querySelectorAll('[name="co_civil_status"]')
    .forEach(function (radio) {
      radio.addEventListener("change", updateStepVisibility);
    });

  /**
   * Recalculate co-owner-step visibility when the
   * has_co_owner selection changes.
   */
  form.querySelectorAll('[name="has_co_owner"]').forEach(function (radio) {
    radio.addEventListener("change", updateStepVisibility);
  });

  /**
   * Show/hide representative fields when has_representative changes.
   */
  form
    .querySelectorAll('[name="has_representative"]')
    .forEach(function (radio) {
      radio.addEventListener("change", updateConditionalFieldsState);
    });

  ["co_rep_last_name", "co_rep_first_name", "co_rep_middle_name", "co_rep_suffix"]
    .forEach(function (fieldName) {
      var coRepresentativeName = form.querySelector('[name="' + fieldName + '"]');
      if (coRepresentativeName) {
        coRepresentativeName.addEventListener(
          "input",
          updateCoRepresentativeRequiredState,
        );
      }
    });

  /**
   * Same as Permanent Address checkbox.
   */
  var sameAddressCheckbox = document.getElementById("repaf_same_address");

  if (sameAddressCheckbox) {
    sameAddressCheckbox.addEventListener(
      "change",
      updatePresentAddressLockState,
    );
    updatePresentAddressLockState();
  }

  var coSameAddressCheckbox = document.getElementById("repaf_co_same_address");

  if (coSameAddressCheckbox) {
    coSameAddressCheckbox.addEventListener(
      "change",
      updateCoPresentAddressLockState,
    );
    updateCoPresentAddressLockState();
  }

  var sameAsSpouseRepresentativeCheckbox = document.getElementById(
    "repaf_same_as_spouse_representative",
  );

  if (sameAsSpouseRepresentativeCheckbox) {
    sameAsSpouseRepresentativeCheckbox.addEventListener(
      "change",
      updateSpouseRepresentativeCopyState,
    );
    updateSpouseRepresentativeCopyState();
  }

  var sameAsCoSpouseRepresentativeCheckbox = document.getElementById(
    "repaf_same_as_co_spouse_representative",
  );

  if (sameAsCoSpouseRepresentativeCheckbox) {
    sameAsCoSpouseRepresentativeCheckbox.addEventListener(
      "change",
      updateCoSpouseRepresentativeCopyState,
    );
    updateCoSpouseRepresentativeCopyState();
  }

  addressFieldPairs.forEach(function (fieldPair) {
    var source = form.querySelector('[name="' + fieldPair[0] + '"]');

    if (!source) {
      return;
    }

    source.addEventListener("input", function () {
      if (isSameAddressChecked()) {
        copyFieldValue(fieldPair[0], fieldPair[1]);
      }
    });
  });

  coAddressFieldPairs.forEach(function (fieldPair) {
    var source = form.querySelector('[name="' + fieldPair[0] + '"]');

    if (!source) return;

    ["input", "change"].forEach(function (eventName) {
      source.addEventListener(eventName, function () {
        if (isCoSameAddressChecked()) {
          copyFieldValue(fieldPair[0], fieldPair[1]);
        }
      });
    });
  });

  spouseRepresentativeFieldPairs.forEach(function (fieldPair) {
    var source = form.querySelector('[name="' + fieldPair[0] + '"]');

    if (!source) {
      return;
    }

    ["input", "change"].forEach(function (eventName) {
      source.addEventListener(eventName, function () {
        if (isSameAsSpouseRepresentativeChecked()) {
          copyFieldValue(fieldPair[0], fieldPair[1]);
        }
      });
    });
  });

  coSpouseRepresentativeFieldPairs.forEach(function (fieldPair) {
    var source = form.querySelector('[name="' + fieldPair[0] + '"]');
    if (!source) return;

    ["input", "change"].forEach(function (eventName) {
      source.addEventListener(eventName, function () {
        if (isSameAsCoSpouseRepresentativeChecked()) {
          copyFieldValue(fieldPair[0], fieldPair[1]);
        }
      });
    });
  });

  /**
   * Enables/disables Monthly Rent Amount
   * depending on Home Ownership selection.
   */
  function updateRentAmountStateForPrefix(fieldPrefix) {
    var rentRadio = form.querySelector(
      '[name="' + fieldPrefix + '_home_ownership"][value="Rented"]',
    );

    var rentAmountInput = form.querySelector(
      '[name="' + fieldPrefix + '_rent_amount"]',
    );

    if (!rentRadio || !rentAmountInput) {
      return;
    }

    var isEnabled = rentRadio.checked;

    rentAmountInput.disabled = !isEnabled;

    if (!isEnabled) {
      rentAmountInput.value = "";
    }
  }

  function updateRentAmountState() {
    ["buyer", "co"].forEach(function (fieldPrefix) {
      updateRentAmountStateForPrefix(fieldPrefix);
    });
  }

  function updateOccupationOtherState(fieldPrefix) {
    var otherRadio = form.querySelector(
      '[name="' + fieldPrefix + '_occupation"][value="Others"]',
    );

    var otherInput = form.querySelector(
      '[name="' + fieldPrefix + '_occupation_other"]',
    );

    if (!otherRadio || !otherInput) {
      return;
    }

    var otherLabel = otherRadio.closest("label");
    var otherWrapper = otherInput.closest(".repaf-field");

    if (otherLabel && !otherInput.classList.contains("repaf-occupation-other-input")) {
      var inlineWrapper = document.createElement("span");
      inlineWrapper.className = "repaf-occupation-option--other";
      otherLabel.parentNode.insertBefore(inlineWrapper, otherLabel);
      inlineWrapper.appendChild(otherLabel);
      otherInput.classList.add("repaf-occupation-other-input");
      otherInput.placeholder = "Please specify";
      otherInput.setAttribute("aria-label", "Other employment status");
      inlineWrapper.appendChild(otherInput);
      if (otherWrapper) otherWrapper.remove();
    }

    var isEnabled = otherRadio.checked;

    otherInput.hidden = !isEnabled;
    otherInput.disabled = !isEnabled;
    otherInput.required = isEnabled;
    otherInput.setAttribute("aria-hidden", isEnabled ? "false" : "true");
    otherInput.setAttribute("aria-required", isEnabled ? "true" : "false");

    if (!isEnabled) {
      otherInput.value = "";
    }
  }

  /**
   * Enables/disables Other Occupation fields
   * depending on Occupation selection.
   */
  function updateOtherOccupationState() {
    ["buyer", "spouse", "co"].forEach(function (fieldPrefix) {
      updateOccupationOtherState(fieldPrefix);
    });
  }

  /**
   * Match the buyer employment section heading to the selected occupation.
   */
  function getEmploymentInformationTitle(occupation) {
    if (occupation === "Self Employed / Professional") {
      return "Business Information";
    }

    if (
      occupation === "Locally Employed - Private" ||
      occupation === "Locally Employed - Government" ||
      occupation === "OFW" ||
      occupation === "Others"
    ) {
      return "Company Information";
    }

    return "Employment / Business Information";
  }

  function updateEmploymentInformationTitle(fieldPrefix) {
    var title = form.querySelector(
      "#repaf-" + fieldPrefix + "-work-information-title",
    );
    var selected = form.querySelector(
      '[name="' + fieldPrefix + '_occupation"]:checked',
    );

    if (!title) return;

    var value = selected ? selected.value : "";
    title.textContent = getEmploymentInformationTitle(value);
  }

  function updateFieldLabel(fieldName, labelText, isRequired) {
    var label = form.querySelector('label[for="' + fieldName + '"]');
    if (!label) return;

    label.textContent = labelText;
    if (isRequired) {
      var requiredMark = document.createElement("span");
      requiredMark.className = "repaf-required repaf-conditional-required";
      requiredMark.textContent = "*";
      label.appendChild(document.createTextNode(" "));
      label.appendChild(requiredMark);
    }
  }

  /**
   * Toggle occupation-specific employment fields and relabel the employer
   * field. Hidden inputs are disabled and cleared so stale values are not
   * submitted when the occupation changes.
   */
  function updateEmploymentBusinessFields(fieldPrefix) {
    var selected = form.querySelector(
      '[name="' + fieldPrefix + '_occupation"]:checked',
    );
    var isSelfEmployed = Boolean(
      selected && selected.value === "Self Employed / Professional",
    );
    var isCompanyEmployee = Boolean(
      selected &&
        (selected.value === "Locally Employed - Private" ||
          selected.value === "Locally Employed - Government" ||
          selected.value === "OFW" ||
          selected.value === "Others"),
    );
    var isOtherOccupation = Boolean(selected && selected.value === "Others");
    var isWorkPersonEnabled =
      fieldPrefix === "buyer" ||
      (fieldPrefix === "spouse" && isBuyerMarried()) ||
      (fieldPrefix === "co" && hasCoOwner());
    var hasWorkDetails = isSelfEmployed || isCompanyEmployee;
    var requiresCompanyInformation =
      isWorkPersonEnabled && hasWorkDetails && !isOtherOccupation;

    updateEmploymentInformationTitle(fieldPrefix);

    updateFieldLabel(
      fieldPrefix + "_employer_name",
      isSelfEmployed
        ? "Business Name"
        : isCompanyEmployee
          ? "Company Name"
          : "Present Employer / Business Name",
      requiresCompanyInformation,
    );

    form
      .querySelectorAll('[data-work-detail-field="' + fieldPrefix + '"]')
      .forEach(function (field) {
        var wrapper = field.closest(".repaf-field--work-details");
        var shouldShow = isWorkPersonEnabled && hasWorkDetails;
        var isRequiredWhenActive =
          field.getAttribute("data-required-when-active") === "true";

        if (wrapper) wrapper.classList.toggle("is-hidden", !shouldShow);
        field.disabled = !shouldShow;
        setConditionalRequired(
          field,
          requiresCompanyInformation && isRequiredWhenActive,
        );
        if (!shouldShow) field.value = "";
      });

    form
      .querySelectorAll(
        '[data-self-employed-field="' + fieldPrefix + '"]',
      )
      .forEach(function (field) {
        var wrapper = field.closest(".repaf-field--self-employed");
        var shouldShow = isWorkPersonEnabled && isSelfEmployed;
        var isRequiredWhenSelfEmployed =
          field.getAttribute("data-required-when-self-employed") === "true";

        if (wrapper) wrapper.classList.toggle("is-visible", shouldShow);

        field.disabled = !shouldShow;
        setConditionalRequired(
          field,
          shouldShow && isRequiredWhenSelfEmployed,
        );

        if (!shouldShow) field.value = "";
      });

    form
      .querySelectorAll(
        '[data-employee-only-field="' + fieldPrefix + '"]',
      )
      .forEach(function (field) {
        var wrapper = field.closest(".repaf-field--employee-only");
        var isRequiredWhenEmployed =
          field.getAttribute("data-required-when-employed") === "true";
        var shouldShow = isWorkPersonEnabled && isCompanyEmployee;

        if (wrapper) {
          wrapper.classList.toggle("is-hidden", !shouldShow);
          wrapper.setAttribute("aria-hidden", shouldShow ? "false" : "true");
        }

        field.disabled = !shouldShow;
        setConditionalRequired(
          field,
          requiresCompanyInformation && isRequiredWhenEmployed,
        );

        if (!shouldShow) field.value = "";
      });
  }

  /**
   * Home Ownership listeners.
   */
  form
    .querySelectorAll(
      '[name="buyer_home_ownership"], [name="co_home_ownership"]',
    )
    .forEach(function (radio) {
      radio.addEventListener("change", updateRentAmountState);
    });

  /**
   * Occupation listeners.
   */
  ["buyer", "spouse", "co"].forEach(function (fieldPrefix) {
    form
      .querySelectorAll('[name="' + fieldPrefix + '_occupation"]')
      .forEach(function (radio) {
        radio.addEventListener("change", function () {
          updateOtherOccupationState();
          updateEmploymentBusinessFields(fieldPrefix);
        });
      });
  });

  /**
   * Keep searchable datalist inputs restricted to their configured options.
   */
  function updateRestrictedDatalistValidity(field) {
    var listId = field.getAttribute("list");
    var list = listId ? document.getElementById(listId) : null;
    var value = field.value.trim();

    if (!list || !value) {
      field.setCustomValidity("");
      return;
    }

    var matchingOption = Array.prototype.find.call(
      list.options,
      function (option) {
        return option.value.toLowerCase() === value.toLowerCase();
      },
    );

    if (matchingOption) {
      field.value = matchingOption.value;
      field.setCustomValidity("");
      return;
    }

    field.setCustomValidity("Please select an industry from the list.");
  }

  form.querySelectorAll("[data-repaf-restricted-list]").forEach(function (field) {
    ["input", "change", "blur"].forEach(function (eventName) {
      field.addEventListener(eventName, function () {
        updateRestrictedDatalistValidity(field);
      });
    });
    updateRestrictedDatalistValidity(field);
  });

  /**
   * Review edit buttons.
   */
  form.addEventListener("click", function (event) {
    if (event.target.classList.contains("repaf-review-edit")) {
      var step = parseInt(event.target.dataset.editStep, 10);

      if (!isNaN(step)) {
        goToStep(step);
      }
    }
  });

  /**
   * Collect all review values from the form.
   */
  function collectReviewData() {
    return {
      buyer: {
        "Account Code": getFieldValue("account_code"),
        "Last Name": getFieldValue("buyer_last_name"),
        "First Name": getFieldValue("buyer_first_name"),
        "Middle Name": getFieldValue("buyer_middle_name"),
        Suffix: getFieldValue("buyer_suffix"),
        "Date of Birth": getFieldValue("buyer_dob"),
        Age: getFieldValue("buyer_age"),
        Gender: getRadioValue("buyer_gender"),
        "Civil Status": getRadioValue("buyer_civil_status"),
        Citizenship: getFieldValue("buyer_citizenship"),
        TIN: getFieldValue("buyer_tin"),
        "Home Tel. Number": getFieldValue("buyer_home_tel"),
        "Mobile Number": getFieldValue("buyer_mobile"),
        "Email Address": getFieldValue("buyer_email"),
        "Pag-IBIG MID No.": getFieldValue("buyer_pagibig_mid"),
      },

      address: {
        "Permanent Address Line": getFieldValue("buyer_permanent_address_line"),
        "Permanent Barangay": getFieldValue("buyer_permanent_barangay"),
        "Permanent City / Municipality": getFieldValue(
          "buyer_permanent_city_municipality",
        ),
        "Permanent Province / State": getFieldValue(
          "buyer_permanent_province_state",
        ),
        "Permanent Country": getFieldValue("buyer_permanent_country"),
        "Permanent Zip Code": getFieldValue("buyer_permanent_zip"),

        "Present Address Line": getFieldValue("buyer_present_address_line"),
        "Present Barangay": getFieldValue("buyer_present_barangay"),
        "Present City / Municipality": getFieldValue(
          "buyer_present_city_municipality",
        ),
        "Present Province / State": getFieldValue(
          "buyer_present_province_state",
        ),
        "Present Country": getFieldValue("buyer_present_country"),
        "Present Zip Code": getFieldValue("buyer_present_zip"),

        "Home Ownership": getRadioValue("buyer_home_ownership"),
        "Monthly Rent Amount": getFieldValue("buyer_rent_amount"),
        Occupation: getRadioValue("buyer_occupation"),
        "Other Occupation": getFieldValue("buyer_occupation_other"),
      },

      spouse: {
        "Last Name": getFieldValue("spouse_last_name"),
        "First Name": getFieldValue("spouse_first_name"),
        "Middle Name": getFieldValue("spouse_middle_name"),
        Suffix: getFieldValue("spouse_suffix"),
        "Date of Birth": getFieldValue("spouse_dob"),
        Age: getFieldValue("spouse_age"),
        Citizenship: getFieldValue("spouse_citizenship"),
        TIN: getFieldValue("spouse_tin"),
        "No. of Dependents": getFieldValue("spouse_dependents"),
        "Home Tel. Number": getFieldValue("spouse_home_tel"),
        "Mobile Number": getFieldValue("spouse_mobile"),
        "Email Address": getFieldValue("spouse_email"),
      },

      employment: {
        "Present Employer / Business Name": getFieldValue(
          "buyer_employer_name",
        ),
        "Business/Company Type": getFieldValue("buyer_business_type"),
        "Date of Business Established": getFieldValue(
          "buyer_business_established_date",
        ),
        "No. of Years in Business": getFieldValue("buyer_years_in_business"),
        "Date Employed": getFieldValue("buyer_employed_date"),
        "No. of Years Employed": getFieldValue("buyer_years_employed"),
        "Position / Department": getFieldValue("buyer_position_department"),
        "Employment Contract Status": getFieldValue("buyer_contract_status"),
        "Position / Level": getFieldValue("buyer_position_level"),
        "Nature of Business / Industry": getFieldValue("buyer_industry"),
        "Business Address Line": getFieldValue("buyer_business_address_line"),
        Barangay: getFieldValue("buyer_business_barangay"),
        Country: getFieldValue("buyer_business_country"),
        "Province / State": getFieldValue("buyer_business_province_state"),
        "City / Municipality": getFieldValue("buyer_business_city_municipality"),
        "Zip Code": getFieldValue("buyer_business_zip"),
        "Office Number": getFieldValue("buyer_office_number"),
        "Office Email Address": getFieldValue("buyer_employment_email"),
        "Office Mobile Number": getFieldValue("buyer_employment_mobile"),
        Occupation: getRadioValue("buyer_occupation"),
        "Other Employment Status": getFieldValue("buyer_occupation_other"),
      },

      spouseEmployment: {
        Occupation: getRadioValue("spouse_occupation"),
        "Other Employment Status": getFieldValue("spouse_occupation_other"),
        "Company / Business Name": getFieldValue("spouse_employer_name"),
        "Industry / Type of Work": getFieldValue("spouse_industry"),
        "No. of Years in Business": getFieldValue("spouse_years_in_business"),
        "No. of Years Employed": getFieldValue("spouse_years_employed"),
        "Date Employed": getFieldValue("spouse_employed_date"),
        "Position / Department": getFieldValue("spouse_position_department"),
        "Employment Contract Status": getFieldValue("spouse_contract_status"),
        "Position / Level": getFieldValue("spouse_position_level"),
        "Business Type": getFieldValue("spouse_business_type"),
        "Date of Business Established": getFieldValue("spouse_business_established_date"),
        "Business Address Line": getFieldValue("spouse_business_address_line"),
        Barangay: getFieldValue("spouse_business_barangay"),
        "City / Municipality": getFieldValue("spouse_business_city_municipality"),
        "Province / State": getFieldValue("spouse_business_province_state"),
        Country: getFieldValue("spouse_business_country"),
        "Zip Code": getFieldValue("spouse_business_zip"),
        "Office Number": getFieldValue("spouse_office_number"),
        "Office Email Address": getFieldValue("spouse_employment_email"),
        "Office Mobile Number": getFieldValue("spouse_employment_mobile"),
      },

      representative: hasRepresentative()
        ? {
            "Has Representative": "Yes",
            "Last Name": getFieldValue("rep_last_name"),
            "First Name": getFieldValue("rep_first_name"),
            "Middle Name": getFieldValue("rep_middle_name"),
            Suffix: getFieldValue("rep_suffix"),
            Birthdate: getFieldValue("rep_dob"),
            Age: getFieldValue("rep_age"),
            "Home Tel. No.": getFieldValue("rep_home_tel"),
            "Mobile No.": getFieldValue("rep_mobile"),
            Email: getFieldValue("rep_email"),
            TIN: getFieldValue("rep_tin"),
            "Relationship to Buyer": getFieldValue("rep_relationship"),
            "Address Line": getFieldValue("rep_address_line"),
            Barangay: getFieldValue("rep_barangay"),
            "City / Municipality": getFieldValue("rep_city_municipality"),
            "Province / State": getFieldValue("rep_province_state"),
            Country: getFieldValue("rep_country"),
            "Zip Code": getFieldValue("rep_zip"),
          }
        : { "Has Representative / Attorney-In-Fact": "No" },

      coowner: {
        Role: getRadioValue("co_owner_role"),
        "Last Name": getFieldValue("co_last_name"),
        "First Name": getFieldValue("co_first_name"),
        "Middle Name": getFieldValue("co_middle_name"),
        Suffix: getFieldValue("co_suffix"),
        "Date of Birth": getFieldValue("co_dob"),
        Age: getFieldValue("co_age"),
        Gender: getRadioValue("co_gender"),
        "Civil Status": getRadioValue("co_civil_status"),
        Citizenship: getFieldValue("co_citizenship"),
        TIN: getFieldValue("co_tin"),
        "Home Telephone Number": getFieldValue("co_home_tel"),
        "Mobile Number": getFieldValue("co_mobile"),
        "Email Address": getFieldValue("co_email"),
        "Pag-IBIG MID Number": getFieldValue("co_pagibig_mid"),

        "Permanent Address Line": getFieldValue("co_permanent_address_line"),
        "Permanent Barangay": getFieldValue("co_permanent_barangay"),
        "Permanent City / Municipality": getFieldValue(
          "co_permanent_city_municipality",
        ),
        "Permanent Province / State": getFieldValue(
          "co_permanent_province_state",
        ),
        "Permanent Country": getFieldValue("co_permanent_country"),
        "Permanent Zip Code": getFieldValue("co_permanent_zip"),

        "Present Address Line": getFieldValue("co_present_address_line"),
        "Present Barangay": getFieldValue("co_present_barangay"),
        "Present City / Municipality": getFieldValue(
          "co_present_city_municipality",
        ),
        "Present Province / State": getFieldValue("co_present_province_state"),
        "Present Country": getFieldValue("co_present_country"),
        "Present Zip Code": getFieldValue("co_present_zip"),

        "Home Ownership": getRadioValue("co_home_ownership"),
        "Monthly Rental Amount": getFieldValue("co_rent_amount"),
      },

      coSpouse: {
        "Last Name": getFieldValue("co_spouse_last_name"),
        "First Name": getFieldValue("co_spouse_first_name"),
        "Middle Name": getFieldValue("co_spouse_middle_name"),
        Suffix: getFieldValue("co_spouse_suffix"),
        Citizenship: getFieldValue("co_spouse_citizenship"),
        "Date of Birth": getFieldValue("co_spouse_dob"),
        Age: getFieldValue("co_spouse_age"),
        TIN: getFieldValue("co_spouse_tin"),
        "No. of Dependents": getFieldValue("co_spouse_dependents"),
        "Home / Office Number": getFieldValue("co_spouse_home_office_number"),
        "Mobile Number": getFieldValue("co_spouse_mobile"),
        "Email Address": getFieldValue("co_spouse_email"),
      },

      coEmployment: {
        "Present Employer / Business Name": getFieldValue("co_employer_name"),
        "Business/Company Type": getFieldValue("co_business_type"),
        "Date of Business Established": getFieldValue(
          "co_business_established_date",
        ),
        "No. of Years in Business": getFieldValue("co_years_in_business"),
        "Date Employed": getFieldValue("co_employed_date"),
        "No. of Years Employed": getFieldValue("co_years_employed"),
        "Position / Department": getFieldValue("co_position_department"),
        "Employment Contract Status": getFieldValue("co_contract_status"),
        "Position / Level": getFieldValue("co_position_level"),
        "Nature of Business / Industry": getFieldValue("co_industry"),
        "Business Address Line": getFieldValue("co_business_address_line"),
        "Business Barangay": getFieldValue("co_business_barangay"),
        "Business Country": getFieldValue("co_business_country"),
        "Business Province / State": getFieldValue(
          "co_business_province_state",
        ),
        "Business City / Municipality": getFieldValue(
          "co_business_city_municipality",
        ),
        "Business Zip Code": getFieldValue("co_business_zip"),
        "Office Number": getFieldValue("co_office_number"),
        "Office Email Address": getFieldValue("co_employment_email"),
        "Office Mobile Number": getFieldValue("co_employment_mobile"),
        Occupation: getRadioValue("co_occupation"),
        "Other Employment Status": getFieldValue("co_occupation_other"),
      },

      coRepresentative: {
        "Last Name": getFieldValue("co_rep_last_name"),
        "First Name": getFieldValue("co_rep_first_name"),
        "Middle Name": getFieldValue("co_rep_middle_name"),
        Suffix: getFieldValue("co_rep_suffix"),
        "Address Line": getFieldValue("co_rep_address_line"),
        Barangay: getFieldValue("co_rep_barangay"),
        "City / Municipality": getFieldValue("co_rep_city_municipality"),
        "Province / State": getFieldValue("co_rep_province_state"),
        Country: getFieldValue("co_rep_country"),
        "Zip Code": getFieldValue("co_rep_zip"),
        Birthdate: getFieldValue("co_rep_dob"),
        Age: getFieldValue("co_rep_age"),
        "Home Telephone Number": getFieldValue("co_rep_home_tel"),
        "Mobile Number": getFieldValue("co_rep_mobile"),
        Email: getFieldValue("co_rep_email"),
        TIN: getFieldValue("co_rep_tin"),
        "Relationship to Buyer": getFieldValue("co_rep_relationship"),
      },

      financial: (function () {
        var fd = {
          "Buyer Salary / Income": getFieldValue("fin_buyer_salary"),
          "Buyer Allowance (COLA/PERA)": getFieldValue("fin_buyer_allowance"),
          "Buyer Commissions": getFieldValue("fin_buyer_commission"),
          "Buyer Other Income": getFieldValue("fin_buyer_other_amount"),
          "Buyer Total Monthly Income": getFieldValue("fin_buyer_total"),
        };

        if (isBuyerMarried()) {
          fd["Spouse Salary / Income"]      = getFieldValue("fin_spouse_salary");
          fd["Spouse Allowance (COLA/PERA)"] = getFieldValue("fin_spouse_allowance");
          fd["Spouse Commissions"]          = getFieldValue("fin_spouse_commission");
          fd["Spouse Other Income"]         = getFieldValue("fin_spouse_other_amount");
          fd["Spouse Total Monthly Income"] = getFieldValue("fin_spouse_total");
        }

        if (hasCoOwner()) {
          fd["Co-Borrower Salary / Income"]      = getFieldValue("fin_co_salary");
          fd["Co-Borrower Allowance (COLA/PERA)"] = getFieldValue("fin_co_allowance");
          fd["Co-Borrower Commissions"]          = getFieldValue("fin_co_commission");
          fd["Co-Borrower Other Income"]         = getFieldValue("fin_co_other_amount");
          fd["Co-Borrower Total Monthly Income"] = getFieldValue("fin_co_total");
        }

        var bankCount = document.querySelectorAll("#repaf-fin-banks .repaf-fin-row").length;
        fd["Bank / Credit Card Accounts"] = bankCount > 0 ? bankCount + " entr" + (bankCount === 1 ? "y" : "ies") + " entered" : "-";

        var loanCount = document.querySelectorAll("#repaf-fin-loans .repaf-fin-row").length;
        fd["Loans"] = loanCount > 0 ? loanCount + " entr" + (loanCount === 1 ? "y" : "ies") + " entered" : "-";

        var refCount = document.querySelectorAll("#repaf-fin-refs .repaf-fin-row").length;
        fd["Character References"] = refCount > 0 ? refCount + " entr" + (refCount === 1 ? "y" : "ies") + " entered" : "-";

        return fd;
      })(),
    };
  }

  /**
   * Get input value safely.
   */
  function getFieldValue(name) {
    var field = form.querySelector('[name="' + name + '"]');

    return field && field.value ? field.value : "-";
  }

  /**
   * Get selected radio value safely.
   */
  function getRadioValue(name) {
    var checked = form.querySelector('[name="' + name + '"]:checked');

    return checked ? checked.value : "-";
  }

  /**
   * Render one review card section.
   */
  function renderReviewCard(title, data, stepNumber) {
    var html = `
      <div class="repaf-review-card">

        <div class="repaf-review-card__header">
          <h4>${esc(title)}</h4>

          <button
            type="button"
            class="repaf-review-edit"
            data-edit-step="${esc(stepNumber)}"
          >
            Edit
          </button>
        </div>

        <div class="repaf-review-card__content">
          <div class="repaf-review-grid">
    `;

    Object.keys(data).forEach(function (key) {
      html += `
        <div class="repaf-review-item">
          <span class="repaf-review-label">${esc(key)}</span>
          <span class="repaf-review-value">${esc(data[key])}</span>
        </div>
      `;
    });

    html += `
          </div>
        </div>
      </div>
    `;

    return html;
  }

  /**
   * Populate the final review screen.
   */
  function populateReviewScreen() {
    var reviewContainer = document.getElementById("repaf-review-summary");

    if (!reviewContainer) {
      return;
    }

    var data = collectReviewData();

    var html = "";

    html += renderReviewCard("Buyer Information", data.buyer, 1);

    html += renderReviewCard("Address Details", data.address, 1);

    if (isBuyerMarried()) {
      html += renderReviewCard("Spouse Information", data.spouse, 2);
      html += renderReviewCard("Spouse Work Information", data.spouseEmployment, 6);
    }

    if (hasCoOwner()) {
      html += renderReviewCard("Co-Owner / Co-Borrower", data.coowner, 4);
      if (isCoOwnerMarried()) {
        html += renderReviewCard("Co-Owner Spouse", data.coSpouse, 5);
      }
      html += renderReviewCard("Co-Owner Work Information", data.coEmployment, 6);
      var coRepresentativeReviewName = [
        "co_rep_last_name",
        "co_rep_first_name",
        "co_rep_middle_name",
        "co_rep_suffix",
      ].some(function (fieldName) {
        var field = form.querySelector('[name="' + fieldName + '"]');
        return Boolean(field && field.value.trim());
      });
      if (coRepresentativeReviewName) {
        html += renderReviewCard(
          "Co-Owner Representative",
          data.coRepresentative,
          isCoOwnerMarried() ? 5 : 4,
        );
      }
    }

    html += renderReviewCard("Representative Details", data.representative, 3);
    html += renderReviewCard("Your Work Information", data.employment, 6);
    html += renderReviewCard("Financial Information", data.financial, 7);

    reviewContainer.innerHTML = html;
  }

  /**
   * =========================================================
   * Test PDF Generator
   * =========================================================
   */
  var testPdfButton = document.getElementById("repaf-generate-test-pdf");

  if (testPdfButton) {
    testPdfButton.addEventListener("click", function () {
      if (typeof repafPdfTest === "undefined" || !repafPdfTest.url) {
        return;
      }

      /**
       * Open test PDF endpoint.
       */
      window.open(repafPdfTest.url, "_blank");
    });
  }

  /**
   * =========================================================
   * Auto-calculate Age from Date of Birth
   * =========================================================
   */

  /**
   * DOB → Age field pairs.
   * Each entry: [dob field name, age field name]
   */
  var dobAgePairs = [
    ["buyer_dob", "buyer_age"],
    ["spouse_dob", "spouse_age"],
    ["co_dob", "co_age"],
    ["co_spouse_dob", "co_spouse_age"],
    ["rep_dob", "rep_age"],
    ["co_rep_dob", "co_rep_age"],
  ];

  /**
   * Calculate age in whole years from a date string.
   * Returns null if the date is invalid or in the future.
   */
  function calculateAge(dobValue) {
    if (!dobValue) return null;

    var dob = new Date(dobValue);

    if (isNaN(dob.getTime())) return null;

    var today = new Date();

    if (dob > today) return null;

    var age = today.getFullYear() - dob.getFullYear();
    var monthDiff = today.getMonth() - dob.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
      age--;
    }

    return age;
  }

  /**
   * Attach a change listener to each DOB field that
   * auto-fills the paired age field.
   */
  dobAgePairs.forEach(function (pair) {
    var dobName = pair[0];
    var ageName = pair[1];

    var dobField = form.querySelector('[name="' + dobName + '"]');
    var ageField = form.querySelector('[name="' + ageName + '"]');

    if (!dobField || !ageField) return;

    dobField.addEventListener("change", function () {
      var age = calculateAge(dobField.value);

      if (age !== null) {
        ageField.value = age;
        ageField.dispatchEvent(new Event("input", { bubbles: true }));
      } else {
        ageField.value = "";
      }
    });

    /**
     * Run on load in case a DOB is already filled
     * (e.g. browser autofill or returning to a step).
     */
    if (dobField.value) {
      var age = calculateAge(dobField.value);
      if (age !== null) ageField.value = age;
    }
  });

  /**
   * Form submit — show loading overlay while the file uploads and
   * the server processes the PDF. Disables the submit button so the
   * user cannot double-submit.
   */
  var loadingOverlay = document.getElementById("repaf-loading-overlay");
  var submitBtn = form.querySelector('[type="submit"]');

  if (editMode && submitBtn) {
    submitBtn.textContent = "Save Changes";
  }

  form.addEventListener("submit", function () {
    if (loadingOverlay) {
      loadingOverlay.classList.add("is-visible");
      loadingOverlay.setAttribute("aria-hidden", "false");
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = editMode ? "Saving…" : "Submitting…";
    }
  });

  /**
   * Initialize conditional field states.
   */
  updateRentAmountState();
  updateOtherOccupationState();
  ["buyer", "spouse", "co"].forEach(updateEmploymentBusinessFields);

  /**
   * Property Information — conditional visibility driven by data-show-when attributes.
   * Handles prop_financing (In-House sections) and conditional
   * "Others" text inputs.
   */
  function updateCashPaymentFinancingState() {
    if (!editMode) return;

    var propertyStep = form.querySelector('.repaf-step[data-step="8"]');
    if (!propertyStep) return;

    var paymentType = propertyStep.querySelector("[data-payment-type-select]");
    var financing = propertyStep.querySelector("[data-financing-select]");
    var hiddenFinancing = propertyStep.querySelector("[data-cash-payment-financing]");
    if (!paymentType || !financing || !hiddenFinancing) return;

    var locksFinancing = ["Spot Cash", "Deferred Cash"].indexOf(paymentType.value) !== -1;
    if (locksFinancing) {
      financing.value = "Others";
      financing.disabled = true;
      financing.setAttribute("aria-disabled", "true");
      hiddenFinancing.disabled = false;
    } else {
      financing.disabled = false;
      financing.removeAttribute("aria-disabled");
      hiddenFinancing.disabled = true;
    }
  }

  function updatePropertyConditionals() {
    var step9 = form.querySelector('.repaf-step[data-step="8"]');
    if (!step9) return;
    step9.querySelectorAll("[data-show-when]").forEach(function (el) {
      var rule    = el.getAttribute("data-show-when");
      var eqIdx   = rule.indexOf("=");
      var srcName = rule.slice(0, eqIdx);
      var srcVal  = rule.slice(eqIdx + 1);
      var srcEl   = step9.querySelector('[name="' + srcName + '"]');
      var isVisible = srcEl && srcEl.value === srcVal;

      if (isVisible) {
        el.classList.add("is-visible");
      } else {
        el.classList.remove("is-visible");
      }

      el.querySelectorAll("[data-required-when-visible]").forEach(function (input) {
        input.required = isVisible;
        input.setAttribute("aria-required", isVisible ? "true" : "false");
      });
    });
  }

  ["prop_house_finish", "prop_financing", "prop_place_of_reservation", "prop_product_type", "prop_mode_of_payment"].forEach(function (name) {
    var el = form.querySelector('[name="' + name + '"]');
    if (el) el.addEventListener("change", updatePropertyConditionals);
  });
  var propertyPaymentType = form.querySelector("[data-payment-type-select]");
  if (propertyPaymentType) {
    propertyPaymentType.addEventListener("change", function () {
      updateCashPaymentFinancingState();
      updatePropertyConditionals();
    });
  }
  updateCashPaymentFinancingState();
  updatePropertyConditionals();

  /**
   * Financial Information — income total auto-calculation.
   * Attach input listeners to all buyer/spouse/co income fields.
   */
  FIN_INCOME_ROWS.forEach(function (row) {
    [row.buyer, row.spouse, row.co].forEach(function (name) {
      var inp = form.querySelector('[name="' + name + '"]');
      if (inp) {
        inp.addEventListener("input", recalcFinancialTotals);
      }
    });
  });

  /**
   * Initialize optional dynamic rows for Bank Accounts and Loans (max 5).
   * Character References are three fixed required rows rendered by PHP.
   */
  repafMakeDynamicRows(
    "repaf-fin-banks",
    "repaf-fin-add-bank",
    5,
    function (n) {
      return (
        '<div class="repaf-fin-row repaf-fin-bank-row" data-row-index="' + n + '">' +
          '<div class="repaf-field repaf-fin-col-bank-name">' +
            '<input type="text" name="fin_bank[' + n + '][name]" placeholder="Bank name">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-bank-type">' +
            '<select name="fin_bank[' + n + '][type]">' +
              '<option value="">Select account type</option>' +
              '<option value="Savings Account">Savings Account</option>' +
              '<option value="Current / Checking Account">Current / Checking Account</option>' +
              '<option value="Time Deposit">Time Deposit</option>' +
              '<option value="Payroll Account">Payroll Account</option>' +
              '<option value="Credit Card">Credit Card</option>' +
              '<option value="Other">Other</option>' +
            "</select>" +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-bank-date">' +
            '<input type="date" name="fin_bank[' + n + '][date_opened]">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-bank-balance">' +
            '<input type="text" inputmode="decimal" name="fin_bank[' + n + '][balance]" placeholder="0.00" class="repaf-money-input">' +
          "</div>" +
          '<div class="repaf-fin-col-remove">' +
            '<button type="button" class="repaf-fin-remove" aria-label="Remove row">&times;</button>' +
          "</div>" +
        "</div>"
      );
    },
    function (row) {
      row.querySelectorAll(".repaf-money-input").forEach(attachMoneyFormatter);
    }
  );

  repafMakeDynamicRows(
    "repaf-fin-loans",
    "repaf-fin-add-loan",
    5,
    function (n) {
      return (
        '<div class="repaf-fin-row repaf-fin-loan-row" data-row-index="' + n + '">' +
          '<div class="repaf-field repaf-fin-col-loan-type">' +
            '<input type="text" name="fin_loan[' + n + '][type]" placeholder="Type of credit">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-loan-bank">' +
            '<input type="text" name="fin_loan[' + n + '][bank]" placeholder="Bank / institution">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-loan-contact">' +
            '<input type="tel" name="fin_loan[' + n + '][contact]" placeholder="Contact number" pattern="^(09|\\+639|\\+63[2-9]|0[2-9])[0-9]{7,10}$">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-loan-original">' +
            '<input type="text" inputmode="decimal" name="fin_loan[' + n + '][original]" placeholder="0.00" class="repaf-money-input">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-loan-outstanding">' +
            '<input type="text" inputmode="decimal" name="fin_loan[' + n + '][outstanding]" placeholder="0.00" class="repaf-money-input">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-loan-amort">' +
            '<input type="text" inputmode="decimal" name="fin_loan[' + n + '][amortization]" placeholder="0.00" class="repaf-money-input">' +
          "</div>" +
          '<div class="repaf-field repaf-fin-col-loan-collateral">' +
            '<input type="text" name="fin_loan[' + n + '][collateral]" placeholder="Collateral">' +
          "</div>" +
          '<div class="repaf-fin-col-remove">' +
            '<button type="button" class="repaf-fin-remove" aria-label="Remove row">&times;</button>' +
          "</div>" +
        "</div>"
      );
    },
    function (row) {
      row.querySelectorAll(".repaf-money-input").forEach(attachMoneyFormatter);
    }
  );

  /**
   * Money input live formatter — adds comma separators as the user types.
   * Cursor is kept at the same offset from the end of the string so
   * inserting a comma mid-number doesn't jump the caret.
   */
  function attachMoneyFormatter(inp) {
    inp.addEventListener("input", function () {
      var fromEnd = inp.value.length - (inp.selectionEnd !== null ? inp.selectionEnd : inp.value.length);
      inp.value = formatMoney(inp.value);
      var newPos = Math.max(0, inp.value.length - fromEnd);
      try { inp.setSelectionRange(newPos, newPos); } catch (e) {}
      recalcFinancialTotals();
    });
  }

  // Attach to all static money inputs and format any pre-filled values.
  form.querySelectorAll(".repaf-money-input").forEach(function (inp) {
    attachMoneyFormatter(inp);
    if (inp.value && !inp.readOnly) inp.value = formatMoney(inp.value);
  });

  /**
   * Reservation date picker (Property Information)
   * Splits the value into separate day/month/year hidden fields
   * so the PHP sanitizer receives each component individually.
   */
  var resDateInput = form.querySelector("[data-reservation-date]");
  if (resDateInput) {
    resDateInput.addEventListener("change", function () {
      var val = resDateInput.value;
      var parts = val ? val.split("-") : [];
      var dayField   = form.querySelector('[name="prop_reservation_day"]');
      var monthField = form.querySelector('[name="prop_reservation_month"]');
      var yearField  = form.querySelector('[name="prop_reservation_year"]');
      if (dayField)   dayField.value   = parts[2] ? parseInt(parts[2], 10)  : "";
      if (monthField) monthField.value = parts[1] ? parseInt(parts[1], 10)  : "";
      if (yearField)  yearField.value  = parts[0] ? parseInt(parts[0], 10)  : "";
    });
  }

  /**
   * Property Computation
   * Derives: Insurance Amount, DP percentage/amount, Balance on DP,
   *          Monthly DP, and amortization from the entered loanable amount.
   */
  function recalcPropertyFields() {
    var step = form.querySelector('.repaf-step[data-step="8"]');
    if (!step) return;

    function pf(name) { return step.querySelector('[name="' + name + '"]'); }
    function money(name) { var el = pf(name); return el ? parseMoney(el.value) : 0; }
    function plain(name) { var el = pf(name); return el ? (parseFloat(el.value) || 0) : 0; }
    function setMoney(name, val) {
      var el = pf(name);
      if (el && !el.disabled) el.value = val > 0 ? formatMoney(val.toFixed(2)) : "";
    }
    function clearField(name) {
      var el = pf(name);
      if (el && !el.disabled) el.value = "";
    }

    // Derive TCP from Total Selling Price, Misc Fee%, and optional VAT.
    var vatToggle = step.querySelector('[name="prop_is_vatable"][type="checkbox"]');
    var vatPercentField = pf("prop_vat_percent");
    var isVatable = Boolean(vatToggle && vatToggle.checked);

    if (vatPercentField) {
      vatPercentField.disabled = !isVatable || Boolean(vatToggle && vatToggle.disabled);
      vatPercentField.required = isVatable && !vatPercentField.disabled;
      vatPercentField.setAttribute("aria-required", vatPercentField.required ? "true" : "false");
      if (!isVatable) vatPercentField.value = "";
    }

    var tsp     = money("prop_total_selling_price");
    var miscFee = tsp * plain("prop_misc_fee_percent") / 100;
    var vatPct  = isVatable ? plain("prop_vat_percent") : 0;
    var vat     = tsp * vatPct / 100;
    setMoney("prop_misc_fee", miscFee);
    setMoney("prop_vat", vat);
    // Total Contract Price is entered manually and drives downstream amounts.
    var tcp = money("prop_total_contract_price");

    // Loanable Amount is entered manually. Down Payment is the remainder.
    var loanableField = pf("prop_loanable_amount");
    var hasLoanable = Boolean(loanableField && String(loanableField.value).trim() !== "");
    var enteredLoanable = hasLoanable ? money("prop_loanable_amount") : 0;
    var validLoanable = hasLoanable && tcp > 0 && enteredLoanable >= 0 && enteredLoanable <= tcp;
    var loanable = validLoanable ? enteredLoanable : 0;

    if (loanableField) {
      loanableField.setCustomValidity(
        hasLoanable && enteredLoanable < 0
          ? "Loanable Amount cannot be negative."
          : hasLoanable && tcp > 0 && enteredLoanable > tcp
            ? "Loanable Amount cannot exceed the Total Contract Price."
            : ""
      );
    }

    var dpAmt = validLoanable ? tcp - loanable : 0;
    var dpPct = validLoanable && tcp > 0 ? dpAmt / tcp * 100 : 0;
    var dpPctEl = pf("prop_dp_percent");
    if (dpPctEl && !dpPctEl.disabled) {
      dpPctEl.value = validLoanable ? dpPct.toFixed(2) : "";
    }
    if (validLoanable) {
      setMoney("prop_dp_amount", dpAmt);
    } else {
      clearField("prop_dp_amount");
    }

    // Balance on DP = DP Amount − Reservation Fee
    var resFee = money("prop_reservation_fee");
    var balDp  = validLoanable ? dpAmt - resFee : 0;
    setMoney("prop_balance_on_dp", balDp > 0 ? balDp : 0);

    // Monthly DP = Balance on DP ÷ No. of Months
    var months = plain("prop_dp_months");
    setMoney("prop_dp_monthly", months > 0 ? (balDp > 0 ? balDp : 0) / months : 0);

    // The start date is payment month 1, so add one less than the term.
    var dpStartEl = pf("prop_dp_starts_on");
    var dpEndEl   = pf("prop_dp_ends_on");
    if (dpStartEl && dpEndEl && !dpEndEl.disabled) {
      var startVal = dpStartEl.value;
      if (startVal && months > 0) {
        var d = new Date(startVal + "T00:00:00");
        var paymentOffset = Math.max(0, months - 1);
        var paymentDay = d.getDate();
        d.setDate(1);
        d.setMonth(d.getMonth() + paymentOffset);
        var lastDayOfPaymentMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        d.setDate(Math.min(paymentDay, lastDayOfPaymentMonth));
        var y  = d.getFullYear();
        var mo = String(d.getMonth() + 1).padStart(2, "0");
        var dy = String(d.getDate()).padStart(2, "0");
        dpEndEl.value = y + "-" + mo + "-" + dy;
      } else {
        dpEndEl.value = "";
      }
    }

    // Excel equivalent: PMT(annual interest / 12, loan years * 12, -loanable amount).
    // Interest is entered as a percentage, for example 6 for 6%.
    var interestField = pf("prop_inhouse_interest_rate");
    var hasInterestRate = Boolean(interestField && String(interestField.value).trim() !== "");
    var annualInterest = hasInterestRate ? plain("prop_inhouse_interest_rate") / 100 : 0;
    var monthlyRate = annualInterest / 12;
    var paymentMonths = plain("prop_inhouse_loan_terms") * 12;
    var monthlyAmortization = 0;

    if (loanable > 0 && paymentMonths > 0 && hasInterestRate) {
      monthlyAmortization = monthlyRate === 0
        ? loanable / paymentMonths
        : loanable * monthlyRate / (1 - Math.pow(1 + monthlyRate, -paymentMonths));
    }
    var amortizationField = pf("prop_inhouse_monthly_amort");
    if (amortizationField && !amortizationField.disabled) {
      amortizationField.value = monthlyAmortization > 0
        ? formatMoney(Math.round(monthlyAmortization).toFixed(2))
        : "";
    }

    // % Balance / Loanable = (Loanable ÷ TCP) × 100
    var loanPctEl = pf("prop_loan_percent");
    if (loanPctEl && !loanPctEl.disabled && tcp > 0) {
      loanPctEl.value = validLoanable ? (loanable / tcp * 100).toFixed(2) : "";
    }
  }

  // Attach computation triggers to all property source fields.
  [
    "prop_mri", "prop_fi",
    "prop_total_selling_price", "prop_misc_fee_percent", "prop_vat_percent",
    "prop_total_contract_price",
    "prop_loanable_amount", "prop_inhouse_interest_rate", "prop_inhouse_loan_terms",
    "prop_reservation_fee", "prop_dp_months", "prop_dp_starts_on",
  ].forEach(function (name) {
    var el = form.querySelector('[name="' + name + '"]');
    if (el) el.addEventListener("input", recalcPropertyFields);
  });

  var propertyVatToggle = form.querySelector('[name="prop_is_vatable"][type="checkbox"]');
  if (propertyVatToggle) propertyVatToggle.addEventListener("change", recalcPropertyFields);

  /**
   * In edit mode, recalculate financial totals from pre-filled values.
   */
  if (editMode) {
    recalcFinancialTotals();
    recalcPropertyFields();
  }

  /**
   * Initialize wizard UI.
   */
  updateStepVisibility();

  /**
   * Success modal — auto-show when the page loads after a successful
   * submission redirect (?repaf_status=success).
   */
  var successModal = document.getElementById("repaf-success-modal");
  if (successModal) {
    successModal.classList.add("is-visible");
    successModal.setAttribute("aria-hidden", "false");
    // Keep the form visible underneath but blur it so focus stays in the modal
    successModal.querySelector(".repaf-success-modal__btn").focus();
  }
})();
