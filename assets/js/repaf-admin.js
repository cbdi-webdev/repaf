(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {

    // =========================================================
    // Reservation Date — single date picker, split into
    // prop_reservation_day / _month / _year hidden fields
    // =========================================================

    var reservationDateField = document.querySelector('[data-reservation-date]');

    if (reservationDateField) {
      var resDayHidden   = document.querySelector('[name="prop_reservation_day"]');
      var resMonthHidden = document.querySelector('[name="prop_reservation_month"]');
      var resYearHidden  = document.querySelector('[name="prop_reservation_year"]');

      reservationDateField.addEventListener("change", function () {
        var parts = reservationDateField.value ? reservationDateField.value.split("-") : [];
        var year  = parts[0] || "";
        var month = parts[1] ? parseInt(parts[1], 10) : "";
        var day   = parts[2] ? parseInt(parts[2], 10) : "";

        if (resYearHidden) resYearHidden.value = year;
        if (resMonthHidden) resMonthHidden.value = month;
        if (resDayHidden) resDayHidden.value = day;
      });
    }

    // =========================================================
    // Tab Switching
    // =========================================================
    var tabs    = document.querySelectorAll(".repaf-admin-tab");
    var panels  = document.querySelectorAll(".repaf-admin-tab-panel");

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var target = tab.getAttribute("data-tab");

        tabs.forEach(function (t) {
          t.classList.remove("is-active");
          t.setAttribute("aria-selected", "false");
        });

        panels.forEach(function (p) {
          p.classList.remove("is-active");
        });

        tab.classList.add("is-active");
        tab.setAttribute("aria-selected", "true");

        var panel = document.querySelector('[data-panel="' + target + '"]');
        if (panel) {
          panel.classList.add("is-active");
        }
      });
    });

    // Reveal the first invalid field before native validation tries to focus
    // it, including fields inside inactive tabs or collapsed sections.
    var editForm = document.querySelector(".repaf-admin-wrap form");
    var showingInvalidField = false;
    if (editForm) {
      editForm.addEventListener("invalid", function (event) {
        if (showingInvalidField) return;
        showingInvalidField = true;

        var panel = event.target.closest(".repaf-admin-tab-panel");
        if (panel) {
          var tab = document.querySelector('.repaf-admin-tab[data-tab="' + panel.getAttribute("data-panel") + '"]');
          if (tab) tab.click();
        }
        var group = event.target.closest(".repaf-admin-field-group");
        if (group) group.classList.remove("is-collapsed");

        window.setTimeout(function () { showingInvalidField = false; }, 0);
      }, true);
    }

    // =========================================================
    // Conditional Field Visibility
    // =========================================================

    function updateConditionals() {
      var conditionals = document.querySelectorAll(".repaf-admin-conditional");

      conditionals.forEach(function (field) {
        var rule  = field.getAttribute("data-show-when");
        if (!rule) return;

        var parts   = rule.split("=");
        var name    = parts[0];
        var expected = parts[1];

        var control = document.querySelector('[name="' + name + '"]');
        if (!control) {
          field.classList.remove("is-visible");
          return;
        }

        var currentVal = control.tagName === "SELECT" ? control.value : control.value;
        var isVisible = currentVal === expected;

        if (isVisible) {
          field.classList.add("is-visible");
        } else {
          field.classList.remove("is-visible");
        }

        field.querySelectorAll("[data-required-when-visible]").forEach(function (input) {
          input.required = isVisible;
          input.setAttribute("aria-required", isVisible ? "true" : "false");
        });
      });
    }

    // Attach change listeners to all select and input controls that may trigger conditionals
    var triggerNames = ["prop_house_finish", "prop_place_of_reservation", "prop_product_type", "prop_mode_of_payment", "prop_financing"];

    triggerNames.forEach(function (name) {
      var ctrl = document.querySelector('[name="' + name + '"]');
      if (ctrl) {
        ctrl.addEventListener("change", updateConditionals);
      }
    });

    var paymentTypeField = document.querySelector('[name="prop_payment_type"]');
    var financingField = document.querySelector('select[name="prop_financing"]');
    var cashPaymentFinancingField = document.querySelector("[data-cash-payment-financing]");

    function updateCashPaymentFinancingState() {
      if (!paymentTypeField || !financingField || !cashPaymentFinancingField) return;

      var locksFinancing = ["Spot Cash", "Deferred Cash"].indexOf(paymentTypeField.value) !== -1;
      if (locksFinancing) {
        financingField.value = "Others";
        financingField.disabled = true;
        financingField.setAttribute("aria-disabled", "true");
        cashPaymentFinancingField.disabled = false;
      } else {
        financingField.disabled = false;
        financingField.removeAttribute("aria-disabled");
        cashPaymentFinancingField.disabled = true;
      }
    }

    if (paymentTypeField) {
      paymentTypeField.addEventListener("change", function () {
        updateCashPaymentFinancingState();
        updateConditionals();
      });
    }

    // Run on load
    updateCashPaymentFinancingState();
    updateConditionals();

    // =========================================================
    // Recalculate optional Miscellaneous Fee and VAT. Total Contract Price is
    // entered manually and drives the downstream payment calculations.
    // =========================================================

    var sellingPriceField = document.querySelector('[name="prop_total_selling_price"]');
    var miscFeePctField    = document.querySelector('[name="prop_misc_fee_percent"]');
    var miscFeeField       = document.querySelector('[name="prop_misc_fee"]');
    var vatToggleField     = document.querySelector('[name="prop_is_vatable"][type="checkbox"]');
    var vatPctField        = document.querySelector('[name="prop_vat_percent"]');
    var vatField           = document.querySelector('[name="prop_vat"]');
    var tcpHidden          = document.querySelector('[name="prop_total_contract_price"]');

    function updateTotalContractPrice() {
      var sellingPrice = sellingPriceField ? parseMoneyValue(sellingPriceField.value) : NaN;

      // Recompute Misc Fee from percentage.
      if (miscFeePctField && miscFeeField) {
        var pct = parseFloat(miscFeePctField.value) || 0;
        var fee = (!isNaN(sellingPrice) && sellingPrice > 0) ? sellingPrice * pct / 100 : 0;
        miscFeeField.value = fee > 0 ? formatMoneyNumber(fee) : "";
      }

      var isVatable = Boolean(vatToggleField && vatToggleField.checked);
      if (vatPctField) {
        vatPctField.disabled = !isVatable;
        vatPctField.required = isVatable;
        vatPctField.setAttribute("aria-required", isVatable ? "true" : "false");
        if (!isVatable) vatPctField.value = "";
      }

      // VAT is calculated only when VATable is checked and a rate is entered.
      if (vatField) {
        var vatPct = isVatable && vatPctField ? (parseFloat(vatPctField.value) || 0) : 0;
        var vatAmt = (!isNaN(sellingPrice) && sellingPrice > 0) ? sellingPrice * vatPct / 100 : 0;
        vatField.value = vatAmt > 0 ? formatMoneyNumber(vatAmt) : "";
      }

      var miscFee = miscFeeField ? parseMoneyValue(miscFeeField.value) : 0;
      var vat     = vatField ? parseMoneyValue(vatField.value) : 0;

      if (isNaN(miscFee)) miscFee = 0;
      if (isNaN(vat)) vat = 0;

      var tcp = tcpHidden ? parseMoneyValue(tcpHidden.value) : NaN;
      if (isNaN(tcp)) return;

      updateDpAmount();
    }

    if (sellingPriceField) {
      sellingPriceField.addEventListener("input", updateTotalContractPrice);
      sellingPriceField.addEventListener("blur", updateTotalContractPrice);
    }
    if (miscFeePctField) {
      miscFeePctField.addEventListener("input", updateTotalContractPrice);
      miscFeePctField.addEventListener("change", updateTotalContractPrice);
    }
    if (vatToggleField) {
      vatToggleField.addEventListener("change", updateTotalContractPrice);
    }
    if (vatPctField) {
      vatPctField.addEventListener("input", updateTotalContractPrice);
      vatPctField.addEventListener("change", updateTotalContractPrice);
    }
    if (tcpHidden) {
      tcpHidden.addEventListener("input", updateTotalContractPrice);
      tcpHidden.addEventListener("blur", updateTotalContractPrice);
    }

    // =========================================================
    // Auto-calculate Down Payment and Balance from the entered Loanable Amount
    // DP Amount             = Total Contract Price - Loanable Amount
    // Required DP %         = DP Amount / Total Contract Price x 100
    // Balance on DP         = DP Amount - Reservation Fee
    // % Balance / Loanable  = Loanable Amount / Total Contract Price x 100
    // =========================================================

    var tcpField            = document.querySelector('[name="prop_total_contract_price"]');
    var dpPctHidden          = document.querySelector('[name="prop_dp_percent"]');
    var dpPctDisplay         = document.querySelector('[data-display-for="prop_dp_percent"]');
    var reservationFeeField  = document.querySelector('[name="prop_reservation_fee"]');
    var dpAmtHidden          = document.querySelector('[name="prop_dp_amount"]');
    var dpAmtDisplay         = document.querySelector('[data-display-for="prop_dp_amount"]');
    var balanceOnDpHidden    = document.querySelector('[name="prop_balance_on_dp"]');
    var balanceOnDpDisplay   = document.querySelector('[data-display-for="prop_balance_on_dp"]');
    var loanPctHidden        = document.querySelector('[name="prop_loan_percent"]');
    var loanPctDisplay       = document.querySelector('[data-display-for="prop_loan_percent"]');
    var loanableAmtField     = document.querySelector('[name="prop_loanable_amount"]');
    var amortizationField    = document.querySelector('[name="prop_inhouse_monthly_amort"]');
    var loanTermField        = document.querySelector('[name="prop_inhouse_loan_terms"]');
    var interestRateField    = document.querySelector('[name="prop_inhouse_interest_rate"]');

    function updateMonthlyAmortization() {
      if (!amortizationField) return;

      var principal = loanableAmtField ? parseMoneyValue(loanableAmtField.value) : NaN;
      var loanYears = loanTermField ? parseFloat(loanTermField.value) : NaN;
      var annualInterest = interestRateField ? parseFloat(interestRateField.value) / 100 : NaN;
      var paymentMonths = loanYears * 12;

      if (isNaN(principal) || principal <= 0 || isNaN(paymentMonths) || paymentMonths <= 0 || isNaN(annualInterest) || annualInterest < 0) {
        amortizationField.value = "";
        return;
      }

      var monthlyRate = annualInterest / 12;
      var payment = monthlyRate === 0
        ? principal / paymentMonths
        : principal * monthlyRate / (1 - Math.pow(1 + monthlyRate, -paymentMonths));

      amortizationField.value = Math.round(payment).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    }

    function updateDpAmount() {
      if (!tcpField || !loanableAmtField) return;

      var tcp = parseMoneyValue(tcpField.value);
      var loanableAmount = parseMoneyValue(loanableAmtField.value);
      var reservationFee = reservationFeeField ? parseMoneyValue(reservationFeeField.value) : 0;
      if (isNaN(reservationFee)) reservationFee = 0;

      loanableAmtField.setCustomValidity(
        !isNaN(loanableAmount) && loanableAmount < 0
          ? "Loanable Amount cannot be negative."
          : !isNaN(tcp) && !isNaN(loanableAmount) && loanableAmount > tcp
            ? "Loanable Amount cannot exceed the Total Contract Price."
            : ""
      );

      if (isNaN(tcp) || tcp <= 0 || isNaN(loanableAmount) || loanableAmount < 0 || loanableAmount > tcp) {
        if (dpPctHidden) dpPctHidden.value = "";
        if (dpPctDisplay) dpPctDisplay.value = "";
        if (dpAmtHidden) dpAmtHidden.value = "";
        if (dpAmtDisplay) dpAmtDisplay.value = "";
        if (balanceOnDpHidden) balanceOnDpHidden.value = "";
        if (balanceOnDpDisplay) balanceOnDpDisplay.value = "";
        if (loanPctHidden) loanPctHidden.value = "";
        if (loanPctDisplay) loanPctDisplay.value = "";
        if (amortizationField) amortizationField.value = "";
        return;
      }

      var dpAmount = tcp - loanableAmount;
      var dpPct = tcp > 0 ? (dpAmount / tcp) * 100 : 0;
      if (dpPctHidden) dpPctHidden.value = dpPct.toFixed(2);
      if (dpPctDisplay) dpPctDisplay.value = dpPct.toFixed(2);
      if (dpAmtHidden) dpAmtHidden.value = dpAmount.toFixed(2);
      if (dpAmtDisplay) dpAmtDisplay.value = formatMoneyNumber(dpAmount);

      var balance = Math.max(0, dpAmount - reservationFee);
      if (balanceOnDpHidden) balanceOnDpHidden.value = balance.toFixed(2);
      if (balanceOnDpDisplay) balanceOnDpDisplay.value = formatMoneyNumber(balance);

      var loanPct = tcp > 0 ? (loanableAmount / tcp) * 100 : 0;
      if (loanPctHidden) loanPctHidden.value = loanPct.toFixed(2);
      if (loanPctDisplay) loanPctDisplay.value = loanPct.toFixed(2);
      updateMonthlyAmortization();
    }

    if (loanableAmtField) {
      loanableAmtField.addEventListener("input", updateDpAmount);
      loanableAmtField.addEventListener("blur", updateDpAmount);
    }

    if (reservationFeeField) {
      reservationFeeField.addEventListener("input", updateDpAmount);
      reservationFeeField.addEventListener("blur", updateDpAmount);
    }
    if (loanTermField) {
      loanTermField.addEventListener("input", updateMonthlyAmortization);
      loanTermField.addEventListener("change", updateMonthlyAmortization);
    }
    if (interestRateField) {
      interestRateField.addEventListener("input", updateMonthlyAmortization);
      interestRateField.addEventListener("change", updateMonthlyAmortization);
    }

    updateTotalContractPrice();

    // =========================================================
    // Auto-calculate DP Ends On
    // The start date is payment month 1, so add one less than the term.
    // =========================================================

    var dpStartsOnField = document.querySelector('[name="prop_dp_starts_on"]');
    var dpMonthsField    = document.querySelector('[name="prop_dp_months"]');
    var dpEndsOnHidden   = document.querySelector('[name="prop_dp_ends_on"]');
    var dpEndsOnDisplay  = document.querySelector('[data-display-for="prop_dp_ends_on"]');

    function formatDateForInput(date) {
      var year  = date.getFullYear();
      var month = String(date.getMonth() + 1).padStart(2, "0");
      var day   = String(date.getDate()).padStart(2, "0");
      return year + "-" + month + "-" + day;
    }

    function updateDpEndsOn() {
      if (!dpStartsOnField || !dpMonthsField) return;
      if (!dpStartsOnField.value) return;

      var months = parseInt(dpMonthsField.value, 10);
      if (isNaN(months)) return;

      var startDate = new Date(dpStartsOnField.value + "T00:00:00");
      if (isNaN(startDate.getTime())) return;

      var paymentOffset = Math.max(0, months - 1);
      var paymentDay = startDate.getDate();
      startDate.setDate(1);
      startDate.setMonth(startDate.getMonth() + paymentOffset);
      var lastDayOfPaymentMonth = new Date(startDate.getFullYear(), startDate.getMonth() + 1, 0).getDate();
      startDate.setDate(Math.min(paymentDay, lastDayOfPaymentMonth));
      var endsOnStr = formatDateForInput(startDate);

      if (dpEndsOnHidden) dpEndsOnHidden.value = endsOnStr;
      if (dpEndsOnDisplay) dpEndsOnDisplay.value = endsOnStr;
    }

    if (dpStartsOnField) {
      dpStartsOnField.addEventListener("input", updateDpEndsOn);
      dpStartsOnField.addEventListener("change", updateDpEndsOn);
    }
    if (dpMonthsField) {
      dpMonthsField.addEventListener("input", updateDpEndsOn);
    }

    // =========================================================
    // Auto-calculate Total Salary
    // Total Salary = Buyer Salary + Spouse Salary + Co-Borrower Salary
    // =========================================================

    function makeFinancialTotalUpdater(buyerKey, spouseKey, coKey, totalKey) {
      var buyerField   = document.querySelector('[name="' + buyerKey + '"]');
      var spouseField  = document.querySelector('[name="' + spouseKey + '"]');
      var coField      = document.querySelector('[name="' + coKey + '"]');
      var totalHidden  = document.querySelector('[name="' + totalKey + '"]');
      var totalDisplay = document.querySelector('[data-display-for="' + totalKey + '"]');

      function update() {
        var buyer  = buyerField  ? parseMoneyValue(buyerField.value)  : 0;
        var spouse = spouseField ? parseMoneyValue(spouseField.value) : 0;
        var co     = coField     ? parseMoneyValue(coField.value)     : 0;
        if (isNaN(buyer))  buyer  = 0;
        if (isNaN(spouse)) spouse = 0;
        if (isNaN(co))     co     = 0;
        var total = buyer + spouse + co;
        if (totalHidden)  totalHidden.value  = total.toFixed(2);
        if (totalDisplay) totalDisplay.value = formatMoneyNumber(total);
      }

      [buyerField, spouseField, coField].forEach(function (f) {
        if (!f) return;
        f.addEventListener("input", update);
        f.addEventListener("blur",  update);
      });
    }

    makeFinancialTotalUpdater("fin_buyer_salary",     "fin_spouse_salary",     "fin_co_salary",     "fin_total_salary");
    makeFinancialTotalUpdater("fin_buyer_allowance",  "fin_spouse_allowance",  "fin_co_allowance",  "fin_total_allowance");
    makeFinancialTotalUpdater("fin_buyer_commission", "fin_spouse_commission", "fin_co_commission", "fin_total_commission");
    makeFinancialTotalUpdater("fin_buyer_other_amount", "fin_spouse_other_amount", "fin_co_other_amount", "fin_total_other");

    function makeFinancialPersonTotalUpdater(person, totalKey) {
      var fields = ["salary", "allowance", "commission", "other_amount"].map(function (type) {
        return document.querySelector('[name="fin_' + person + '_' + type + '"]');
      });
      var totalHidden  = document.querySelector('[name="' + totalKey + '"]');
      var totalDisplay = document.querySelector('[data-display-for="' + totalKey + '"]');

      function update() {
        var total = fields.reduce(function (sum, field) {
          var amount = field ? parseMoneyValue(field.value) : 0;
          return sum + (isNaN(amount) ? 0 : amount);
        }, 0);
        if (totalHidden) totalHidden.value = total.toFixed(2);
        if (totalDisplay) totalDisplay.value = formatMoneyNumber(total);
      }

      fields.forEach(function (field) {
        if (!field) return;
        field.addEventListener("input", update);
        field.addEventListener("blur", update);
      });
    }

    makeFinancialPersonTotalUpdater("buyer", "fin_buyer_total");
    makeFinancialPersonTotalUpdater("spouse", "fin_spouse_total");
    makeFinancialPersonTotalUpdater("co", "fin_co_total");

    // =========================================================
    // Money Inputs — live thousands-separator formatting
    // (e.g. 1000000 -> 1,000,000.00)
    // =========================================================

    function parseMoneyValue(str) {
      return parseFloat(String(str).replace(/,/g, ""));
    }

    function formatMoneyNumber(num) {
      return num.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    var moneyInputs = document.querySelectorAll(".repaf-money-input");

    moneyInputs.forEach(function (input) {
      // Editing: strip commas, allow only digits and a single decimal point
      input.addEventListener("focus", function () {
        var num = parseMoneyValue(input.value);
        input.value = isNaN(num) ? "" : String(num);
      });

      input.addEventListener("input", function () {
        var cleaned = input.value.replace(/[^0-9.]/g, "");
        var firstDot = cleaned.indexOf(".");
        if (firstDot !== -1) {
          cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, "");
        }
        input.value = cleaned;
      });

      // Display: reformat with thousands separators
      input.addEventListener("blur", function () {
        var num = parseMoneyValue(input.value);
        input.value = isNaN(num)
          ? ""
          : input.hasAttribute("data-whole-money")
            ? Math.round(num).toLocaleString("en-US", { maximumFractionDigits: 0 })
            : formatMoneyNumber(num);
      });

      // Initial render
      var initial = parseMoneyValue(input.value);
      if (!isNaN(initial)) {
        input.value = input.hasAttribute("data-whole-money")
          ? Math.round(initial).toLocaleString("en-US", { maximumFractionDigits: 0 })
          : formatMoneyNumber(initial);
      }
    });

    // =========================================================
    // Collapsible field groups
    // Clicking the h3 header toggles the section open/closed.
    // =========================================================

    document.querySelectorAll(".repaf-admin-field-group").forEach(function (group) {
      var h3 = group.querySelector("h3");
      if (!h3) return;

      // Wrap all siblings after h3 into a collapsible body div
      var body = document.createElement("div");
      body.className = "repaf-admin-section-body";

      Array.from(group.children).forEach(function (child) {
        if (child !== h3) {
          body.appendChild(child);
        }
      });
      group.appendChild(body);

      h3.addEventListener("click", function () {
        group.classList.toggle("is-collapsed");
      });
    });

  });
})();
