/**
 * Populate PSOC profession fields as native dropdowns.
 * The API token remains on the WordPress server; this script receives names only.
 */
(function () {
  "use strict";

  var form = document.getElementById("repaf-wizard-form");
  var config = window.repafPsocConfig || {};
  if (!form) return;

  var fields = form.querySelectorAll('[data-repaf-psoc="true"]');
  if (!fields.length) return;

  function setStatus(field, message, isError) {
    var wrapper = field.closest(".repaf-field");
    if (!wrapper) return;
    var status = wrapper.querySelector(".repaf-psoc-status");
    if (!status) {
      status = document.createElement("p");
      status.className = "repaf-psoc-status";
      status.setAttribute("aria-live", "polite");
      wrapper.appendChild(status);
    }
    status.textContent = message;
    status.classList.toggle("is-error", !!isError);
  }

  function clearStatus(field) {
    var wrapper = field.closest(".repaf-field");
    var status = wrapper ? wrapper.querySelector(".repaf-psoc-status") : null;
    if (status) status.remove();
  }

  function populate(professions) {
    Array.prototype.forEach.call(fields, function (field) {
      var current = field.value;
      var currentLower = current.toLocaleLowerCase();
      var hasCurrentMatch = false;
      while (field.options.length > 1) field.remove(1);
      professions.forEach(function (profession) {
        var option = document.createElement("option");
        var isCurrent = profession.toLocaleLowerCase() === currentLower;
        option.value = profession;
        option.textContent = profession;
        option.selected = isCurrent;
        if (isCurrent) hasCurrentMatch = true;
        field.appendChild(option);
      });
      if (current && !hasCurrentMatch) {
        var saved = document.createElement("option");
        saved.value = current;
        saved.textContent = current;
        saved.selected = true;
        field.appendChild(saved);
      }
      clearStatus(field);
    });
  }

  if (!config.ajaxUrl || !config.nonce || !config.configured) {
    Array.prototype.forEach.call(fields, function (field) {
      field.disabled = true;
      setStatus(field, "PSOC lookup is not configured. Contact the administrator.", true);
    });
    return;
  }

  var body = new FormData();
  body.append("action", "repaf_get_psoc_professions");
  body.append("nonce", config.nonce);

  Array.prototype.forEach.call(fields, function (field) {
    field.disabled = true;
    setStatus(field, "Loading PSOC professions...");
  });

  fetch(config.ajaxUrl, {
    method: "POST",
    credentials: "same-origin",
    body: body,
  })
    .then(function (response) { return response.json(); })
    .then(function (response) {
      if (!response.success || !Array.isArray(response.data)) {
        throw new Error(response.data && response.data.message ? response.data.message : "PSOC lookup is unavailable.");
      }
      populate(response.data);
      Array.prototype.forEach.call(fields, function (field) { field.disabled = false; });
    })
    .catch(function (error) {
      Array.prototype.forEach.call(fields, function (field) {
        field.disabled = false;
        setStatus(field, error && error.message ? error.message : "PSOC lookup is unavailable.", true);
      });
    });
})();
