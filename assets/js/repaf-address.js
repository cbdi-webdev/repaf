/**
 * Philippines-first cascading address fields.
 *
 * Philippine addresses use PSGC-powered dropdowns. Foreign addresses retain
 * the original text inputs. The original field names always hold the values
 * submitted to WordPress, so PDFs, CSVs, review data, and saved submissions
 * remain backward compatible.
 */
(function () {
  "use strict";

  var form = document.getElementById("repaf-wizard-form");
  var config = window.repafAddressConfig || {};
  var countryOptions = Array.isArray(config.countries) && config.countries.length
    ? config.countries
    : ["Philippines"];

  if (!form || !config.ajaxUrl || !config.nonce) return;

  var ADDRESS_GROUPS = [
    {
      country: "buyer_permanent_country",
      province: "buyer_permanent_province_state",
      city: "buyer_permanent_city_municipality",
      barangay: "buyer_permanent_barangay",
      zip: "buyer_permanent_zip",
    },
    {
      country: "buyer_present_country",
      province: "buyer_present_province_state",
      city: "buyer_present_city_municipality",
      barangay: "buyer_present_barangay",
      zip: "buyer_present_zip",
    },
    {
      country: "rep_country",
      province: "rep_province_state",
      city: "rep_city_municipality",
      barangay: "rep_barangay",
      zip: "rep_zip",
    },
    {
      country: "co_permanent_country",
      province: "co_permanent_province_state",
      city: "co_permanent_city_municipality",
      barangay: "co_permanent_barangay",
      zip: "co_permanent_zip",
    },
    {
      country: "co_present_country",
      province: "co_present_province_state",
      city: "co_present_city_municipality",
      barangay: "co_present_barangay",
      zip: "co_present_zip",
    },
    {
      country: "co_rep_country",
      province: "co_rep_province_state",
      city: "co_rep_city_municipality",
      barangay: "co_rep_barangay",
      zip: "co_rep_zip",
    },
    {
      country: "buyer_business_country",
      province: "buyer_business_province_state",
      city: "buyer_business_city_municipality",
      barangay: "buyer_business_barangay",
      zip: "buyer_business_zip",
      preserveOrder: true,
    },
    {
      country: "spouse_business_country",
      province: "spouse_business_province_state",
      city: "spouse_business_city_municipality",
      barangay: "spouse_business_barangay",
      zip: "spouse_business_zip",
      preserveOrder: true,
    },
    {
      country: "co_business_country",
      province: "co_business_province_state",
      city: "co_business_city_municipality",
      barangay: "co_business_barangay",
      zip: "co_business_zip",
      preserveOrder: true,
    },
  ];

  var groups = [];
  var groupsByFieldName = {};
  var lookupCache = {};
  var postalHelperPromise = null;
  var browserCachePrefix = "repaf:address:v1:";
  var browserCacheLifetime = 30 * 24 * 60 * 60 * 1000;
  var addressDatabasePromise = null;
  var addressDatabaseName = "repaf-address-cache";
  var addressDatabaseStore = "lookups";

  function field(name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function clean(value) {
    return String(value || "").trim();
  }

  function sameText(left, right) {
    return clean(left).toLocaleLowerCase() === clean(right).toLocaleLowerCase();
  }

  function createOption(value, label) {
    var option = document.createElement("option");
    option.value = value;
    option.textContent = label;
    return option;
  }

  function setRequired(control, required) {
    if (!control) return;
    control.required = Boolean(required);
    if (required) {
      control.setAttribute("aria-required", "true");
    } else {
      control.removeAttribute("aria-required");
    }
  }

  function setStoredValue(group, key, value) {
    var control = group.actual[key];
    if (!control || control.value === value) return;

    group.updating = true;
    control.value = value;
    control.dispatchEvent(new Event("input", { bubbles: true }));
    control.dispatchEvent(new Event("change", { bubbles: true }));
    group.updating = false;
  }

  function showStatus(group, message, type) {
    group.status.textContent = message || "";
    group.status.className = "repaf-address-status";
    if (type) group.status.classList.add("is-" + type);
    group.status.hidden = !message;
  }

  function readBrowserCache(cacheKey) {
    try {
      var raw = window.localStorage.getItem(browserCachePrefix + cacheKey);
      if (!raw) return null;

      var cached = JSON.parse(raw);
      if (
        !cached ||
        !Array.isArray(cached.items) ||
        !cached.expires ||
        cached.expires <= Date.now()
      ) {
        window.localStorage.removeItem(browserCachePrefix + cacheKey);
        return null;
      }

      return cached.items;
    } catch (error) {
      return null;
    }
  }

  function writeBrowserCache(cacheKey, items) {
    try {
      window.localStorage.setItem(
        browserCachePrefix + cacheKey,
        JSON.stringify({
          expires: Date.now() + browserCacheLifetime,
          items: items,
        }),
      );
    } catch (error) {
      // Storage can be unavailable or full. The in-memory cache still works.
    }
  }

  function openAddressDatabase() {
    if (addressDatabasePromise) return addressDatabasePromise;
    if (!window.indexedDB) return Promise.resolve(null);

    addressDatabasePromise = new Promise(function (resolve) {
      var request;

      try {
        request = window.indexedDB.open(addressDatabaseName, 1);
      } catch (error) {
        resolve(null);
        return;
      }

      request.onupgradeneeded = function () {
        var database = request.result;
        if (!database.objectStoreNames.contains(addressDatabaseStore)) {
          database.createObjectStore(addressDatabaseStore, { keyPath: "key" });
        }
      };
      request.onsuccess = function () {
        resolve(request.result);
      };
      request.onerror = function () {
        resolve(null);
      };
      request.onblocked = function () {
        resolve(null);
      };
    });

    return addressDatabasePromise;
  }

  function readIndexedCache(cacheKey) {
    return openAddressDatabase().then(function (database) {
      if (!database) return null;

      return new Promise(function (resolve) {
        var transaction;
        try {
          transaction = database.transaction(addressDatabaseStore, "readonly");
        } catch (error) {
          resolve(null);
          return;
        }

        var request = transaction.objectStore(addressDatabaseStore).get(cacheKey);
        request.onsuccess = function () {
          var cached = request.result;
          if (
            !cached ||
            !Array.isArray(cached.items) ||
            cached.expires <= Date.now()
          ) {
            resolve(null);
            return;
          }
          resolve(cached.items);
        };
        request.onerror = function () {
          resolve(null);
        };
      });
    });
  }

  function writeIndexedCaches(records) {
    if (!records.length) return Promise.resolve(true);

    return openAddressDatabase().then(function (database) {
      if (!database) return false;

      return new Promise(function (resolve) {
        var transaction;
        try {
          transaction = database.transaction(addressDatabaseStore, "readwrite");
        } catch (error) {
          resolve(false);
          return;
        }

        var store = transaction.objectStore(addressDatabaseStore);
        records.forEach(function (record) {
          store.put({
            key: record.key,
            expires: Date.now() + browserCacheLifetime,
            items: record.items,
          });
        });
        transaction.oncomplete = function () { resolve(true); };
        transaction.onerror = function () { resolve(false); };
        transaction.onabort = function () { resolve(false); };
      });
    });
  }

  function fetchLocations(level, parentCode, cacheKey) {
    var body = new FormData();
    body.append("action", "repaf_address_lookup");
    body.append("nonce", config.nonce);
    body.append("level", level);
    if (parentCode) body.append("parent_code", parentCode);

    return fetch(config.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: body,
    })
      .then(function (response) {
        if (!response.ok) throw new Error("Address lookup failed.");
        return response.json();
      })
      .then(function (response) {
        if (!response.success || !Array.isArray(response.data)) {
          throw new Error(
            response.data && response.data.message
              ? response.data.message
              : "Address lookup failed.",
          );
        }

        if (level !== "all_cities" && level !== "region_barangays") {
          writeBrowserCache(cacheKey, response.data);
        }
        return response.data;
      });
  }

  function requestLocations(level, parentCode) {
    var cacheKey = level + ":" + (parentCode || "");
    if (lookupCache[cacheKey]) return lookupCache[cacheKey];

    var browserCachedItems = readBrowserCache(cacheKey);
    if (browserCachedItems) {
      lookupCache[cacheKey] = Promise.resolve(browserCachedItems);
      return lookupCache[cacheKey];
    }

    lookupCache[cacheKey] = readIndexedCache(cacheKey)
      .then(function (indexedItems) {
        if (indexedItems) {
          writeBrowserCache(cacheKey, indexedItems);
          return indexedItems;
        }

        return fetchLocations(level, parentCode, cacheKey);
      })
      .catch(function (error) {
        delete lookupCache[cacheKey];
        throw error;
      });

    return lookupCache[cacheKey];
  }

  function cacheCompleteHierarchy(items, childLevel) {
    var grouped = {};

    items.forEach(function (item) {
      var parentCode = clean(item.parent_code);
      if (!parentCode) return;
      if (!grouped[parentCode]) grouped[parentCode] = [];

      var cachedItem = Object.assign({}, item);
      delete cachedItem.parent_code;
      grouped[parentCode].push(cachedItem);
    });

    var records = Object.keys(grouped).map(function (parentCode) {
      var cacheKey = childLevel + ":" + parentCode;
      var itemsForParent = grouped[parentCode];
      itemsForParent.sort(function (left, right) {
        return clean(left.name).localeCompare(clean(right.name));
      });
      if (!lookupCache[cacheKey]) {
        lookupCache[cacheKey] = Promise.resolve(itemsForParent);
      }
      return { key: cacheKey, items: itemsForParent };
    });

    return writeIndexedCaches(records);
  }

  function preloadCompleteCities() {
    return readIndexedCache("complete:cities").then(function (complete) {
      if (complete) return;

      return requestLocations("all_cities")
        .then(function (items) {
          return cacheCompleteHierarchy(items, "cities");
        })
        .then(function (stored) {
          if (!stored) return false;
          return writeIndexedCaches([
            { key: "complete:cities", items: ["ready"] },
          ]);
        })
        .catch(function () {
          // Per-province requests remain available as the fallback.
        })
        .then(function () {
          delete lookupCache["all_cities:"];
        });
    });
  }

  function preloadCompleteBarangays() {
    return readIndexedCache("complete:barangays").then(function (complete) {
      if (complete) return;

      return requestLocations("regions")
        .then(function (regions) {
          var queue = regions.slice();
          var preloadFailed = false;

          function preloadNextRegion() {
            var region = queue.shift();
            var regionCode = region ? clean(region.code) : "";
            if (!regionCode) return Promise.resolve();

            var requestKey = "region_barangays:" + regionCode;
            return requestLocations("region_barangays", regionCode)
              .then(function (items) {
                return cacheCompleteHierarchy(items, "barangays");
              })
              .then(function (stored) {
                if (!stored) preloadFailed = true;
              })
              .catch(function () {
                preloadFailed = true;
              })
              .then(function () {
                delete lookupCache[requestKey];
                return preloadNextRegion();
              });
          }

          // Two background workers keep the preload moving without flooding
          // WordPress or the upstream PSGC service with simultaneous requests.
          return Promise.all([preloadNextRegion(), preloadNextRegion()])
            .then(function () {
              if (preloadFailed) return false;
              return writeIndexedCaches([
                { key: "complete:barangays", items: ["ready"] },
              ]);
            });
        })
        .catch(function () {
          // Per-city requests remain available as the fallback.
        });
    });
  }

  function canPreloadCompleteBarangays() {
    if (!window.indexedDB) return false;

    var connection = navigator.connection ||
      navigator.mozConnection ||
      navigator.webkitConnection;

    if (!connection) return true;
    if (connection.saveData) return false;
    return ["slow-2g", "2g"].indexOf(connection.effectiveType) === -1;
  }

  function resetSelect(select, placeholder) {
    select.textContent = "";
    select.appendChild(createOption("", placeholder));
    select.value = "";
    select.dataset.ready = "false";
  }

  function populateSelect(select, items, placeholder, selectedValue, isBarangay) {
    resetSelect(select, placeholder);

    items.forEach(function (item) {
      var label = item.name;
      if (isBarangay && item.location && item.location !== item.name) {
        label += " — " + item.location;
      }

      var option = createOption(item.name, label);
      option.dataset.code = item.code || "";
      option.dataset.zip = item.zip || "";
      option.dataset.location = item.location || "";
      select.appendChild(option);
    });

    var matched = Array.prototype.some.call(select.options, function (option) {
      if (!sameText(option.value, selectedValue)) return false;
      select.value = option.value;
      return true;
    });

    select.dataset.ready = "true";
    return matched || !clean(selectedValue);
  }

  function selectedOption(select) {
    return select && select.selectedIndex >= 0
      ? select.options[select.selectedIndex]
      : null;
  }

  function applyRequirement(group, key) {
    var required = Boolean(group.required[key]);
    var actual = group.actual[key];

    if (key === "country") {
      setRequired(actual, false);
      setRequired(group.countryMode, required);
      return;
    }

    if (key === "zip") {
      setRequired(actual, required);
      return;
    }

    if (group.mode === "philippines" && !group.fallback) {
      setRequired(actual, false);
      setRequired(group.selects[key], required);
    } else {
      setRequired(actual, required);
      setRequired(group.selects[key], false);
    }
  }

  function applyAllRequirements(group) {
    ["country", "province", "city", "barangay", "zip"].forEach(function (key) {
      applyRequirement(group, key);
    });
  }

  function controlIsBlocked(group, key) {
    return group.locked || Boolean(group.actual[key].disabled);
  }

  function syncControlState(group) {
    var isPhilippines = group.mode === "philippines";

    group.countryMode.disabled = controlIsBlocked(group, "country");

    ["province", "city", "barangay"].forEach(function (key) {
      var select = group.selects[key];
      var hasParent = key === "province"
        ? true
        : key === "city"
          ? Boolean(group.selects.province.value)
          : Boolean(group.selects.city.value);

      select.disabled =
        !isPhilippines ||
        group.fallback ||
        controlIsBlocked(group, key) ||
        select.dataset.ready !== "true" ||
        !hasParent;
    });

    if (group.mode === "philippines" && !group.fallback) {
      group.actual.zip.readOnly = group.locked || group.zipAutomatic;
    } else {
      group.actual.zip.readOnly = group.locked;
    }
  }

  function showPhilippineControls(group) {
    ["province", "city", "barangay"].forEach(function (key) {
      group.actual[key].hidden = true;
      group.selects[key].hidden = false;
      if (group.labels[key]) {
        group.labels[key].setAttribute("for", group.selects[key].id);
      }
    });
  }

  function showManualControls(group) {
    ["province", "city", "barangay"].forEach(function (key) {
      group.actual[key].hidden = false;
      group.selects[key].hidden = true;
      group.selects[key].disabled = true;
      if (group.labels[key]) {
        group.labels[key].setAttribute("for", group.actual[key].id);
      }
    });
    group.actual.zip.readOnly = group.locked;
    group.actual.zip.placeholder = group.originalZipPlaceholder;
  }

  function enableManualPhilippineFallback(group, message) {
    group.fallback = true;
    group.zipAutomatic = false;
    showManualControls(group);
    applyAllRequirements(group);
    syncControlState(group);
    showStatus(
      group,
      message || "Address lists are unavailable. Enter the Philippine address manually.",
      "warning",
    );
  }

  function normalizeCityName(value) {
    return clean(value)
      .replace(/^City of\s+/i, "")
      .replace(/\s+City$/i, "")
      .trim();
  }

  function loadPostalHelper() {
    if (!config.postalModuleUrl) return Promise.resolve(null);
    if (!postalHelperPromise) {
      postalHelperPromise = import(config.postalModuleUrl)
        .then(function (module) {
          return module.default();
        })
        .catch(function () {
          return null;
        });
    }
    return postalHelperPromise;
  }

  function uniquePostalCode(helper, query) {
    if (!helper) return "";
    var result = helper.fetchDataLists(query);
    var unique = [];

    (result && result.data ? result.data : []).forEach(function (item) {
      var code = clean(item.post_code);
      if (code && unique.indexOf(code) === -1) unique.push(code);
    });

    return unique.length === 1 ? unique[0] : "";
  }

  function resolveZipCode(group) {
    var requestId = ++group.zipRequestId;
    var cityOption = selectedOption(group.selects.city);
    var barangayOption = selectedOption(group.selects.barangay);
    var apiZip = clean(
      (barangayOption && barangayOption.dataset.zip) ||
        (cityOption && cityOption.dataset.zip),
    );

    if (apiZip) {
      group.zipAutomatic = true;
      setStoredValue(group, "zip", apiZip);
      group.actual.zip.placeholder = "Filled automatically";
      showStatus(group, "ZIP code filled automatically.", "success");
      syncControlState(group);
      return Promise.resolve();
    }

    if (!group.selects.city.value) {
      group.zipAutomatic = false;
      setStoredValue(group, "zip", "");
      showStatus(group, "", "");
      syncControlState(group);
      return Promise.resolve();
    }

    showStatus(group, "Finding ZIP code…", "loading");

    return loadPostalHelper().then(function (helper) {
      if (requestId !== group.zipRequestId || group.mode !== "philippines") return;

      var city = normalizeCityName(group.selects.city.value);
      var province = group.selects.province.value;
      var barangay = group.selects.barangay.value;
      var locality = barangayOption ? barangayOption.dataset.location : "";
      var zip = "";

      if (sameText(province, "Metro Manila")) {
        zip = uniquePostalCode(helper, {
          location: city,
          municipality: barangay,
        });

        if (!zip && locality) {
          zip = uniquePostalCode(helper, {
            location: city,
            municipality: locality,
          });
        }
      } else {
        zip = uniquePostalCode(helper, {
          location: province,
          municipality: city,
        });
      }

      group.zipAutomatic = Boolean(zip);
      if (zip) {
        setStoredValue(group, "zip", zip);
        group.actual.zip.placeholder = "Filled automatically";
        showStatus(group, "ZIP code filled automatically.", "success");
      } else {
        if (!clean(group.actual.zip.value)) setStoredValue(group, "zip", "");
        group.actual.zip.placeholder = "Enter ZIP code";
        showStatus(
          group,
          "This area has no single automatic ZIP match. Enter the correct ZIP code.",
          "warning",
        );
      }
      syncControlState(group);
    });
  }

  function loadBarangays(group, cityCode, savedBarangay) {
    var requestId = ++group.barangayRequestId;
    resetSelect(group.selects.barangay, "Loading barangays…");
    syncControlState(group);

    return requestLocations("barangays", cityCode)
      .then(function (items) {
        if (requestId !== group.barangayRequestId || group.mode !== "philippines") return;

        var matched = populateSelect(
          group.selects.barangay,
          items,
          "Select barangay",
          savedBarangay,
          true,
        );

        if (!matched) {
          enableManualPhilippineFallback(
            group,
            "The saved barangay could not be matched. Review this address manually.",
          );
          return;
        }

        if (group.selects.barangay.value) {
          setStoredValue(group, "barangay", group.selects.barangay.value);
        }
        syncControlState(group);
        if (group.selects.barangay.value) return resolveZipCode(group);
        showStatus(group, "Select a barangay to finish the address.", "loading");
      })
      .catch(function () {
        if (requestId !== group.barangayRequestId || group.mode !== "philippines") return;
        enableManualPhilippineFallback(group);
      });
  }

  function loadCities(group, provinceCode, savedCity, savedBarangay) {
    var requestId = ++group.cityRequestId;
    resetSelect(group.selects.city, "Loading cities / municipalities…");
    resetSelect(group.selects.barangay, "Select city / municipality first");
    syncControlState(group);

    return requestLocations("cities", provinceCode)
      .then(function (items) {
        if (requestId !== group.cityRequestId || group.mode !== "philippines") return;

        var matched = populateSelect(
          group.selects.city,
          items,
          "Select city / municipality",
          savedCity,
          false,
        );

        if (!matched) {
          enableManualPhilippineFallback(
            group,
            "The saved city or municipality could not be matched. Review this address manually.",
          );
          return;
        }

        if (group.selects.city.value) {
          setStoredValue(group, "city", group.selects.city.value);
        }
        syncControlState(group);
        if (group.selects.city.value) {
          var option = selectedOption(group.selects.city);
          return loadBarangays(group, option.dataset.code, savedBarangay);
        }
        showStatus(group, "Select a city or municipality.", "loading");
      })
      .catch(function () {
        if (requestId !== group.cityRequestId || group.mode !== "philippines") return;
        enableManualPhilippineFallback(group);
      });
  }

  function loadPhilippineHierarchy(group, saved) {
    var requestId = ++group.provinceRequestId;
    group.fallback = false;
    showPhilippineControls(group);
    resetSelect(group.selects.province, "Loading provinces / states…");
    resetSelect(group.selects.city, "Select province / state first");
    resetSelect(group.selects.barangay, "Select city / municipality first");
    applyAllRequirements(group);
    syncControlState(group);
    showStatus(group, "Loading Philippine address lists…", "loading");

    return requestLocations("provinces")
      .then(function (items) {
        if (requestId !== group.provinceRequestId || group.mode !== "philippines") return;

        var matched = populateSelect(
          group.selects.province,
          items,
          "Select province / state",
          saved.province,
          false,
        );

        if (!matched) {
          enableManualPhilippineFallback(
            group,
            "The saved province or state could not be matched. Review this address manually.",
          );
          return;
        }

        if (group.selects.province.value) {
          setStoredValue(group, "province", group.selects.province.value);
        }
        syncControlState(group);
        if (group.selects.province.value) {
          var option = selectedOption(group.selects.province);
          return loadCities(group, option.dataset.code, saved.city, saved.barangay);
        }
        showStatus(group, "Select a province or state.", "loading");
      })
      .catch(function () {
        if (requestId !== group.provinceRequestId || group.mode !== "philippines") return;
        enableManualPhilippineFallback(group);
      });
  }

  function setMode(group, mode, saved) {
    group.mode = mode;
    group.fallback = false;

    var selectedCountry = mode === "philippines"
      ? "Philippines"
      : clean(saved.country);
    var hasCountryOption = Array.prototype.some.call(
      group.countryMode.options,
      function (option) {
        return option.value === selectedCountry;
      },
    );
    if (selectedCountry && !hasCountryOption) {
      group.countryMode.appendChild(createOption(selectedCountry, selectedCountry));
    }
    group.countryMode.value = selectedCountry;

    if (mode === "foreign") {
      group.provinceRequestId++;
      group.cityRequestId++;
      group.barangayRequestId++;
      group.zipRequestId++;
      setStoredValue(group, "country", saved.country);
      showManualControls(group);
      group.zipAutomatic = false;
      applyAllRequirements(group);
      syncControlState(group);
      showStatus(group, "Foreign address: enter the location fields manually.", "info");
      return Promise.resolve();
    }

    setStoredValue(group, "country", "Philippines");
    return loadPhilippineHierarchy(group, saved);
  }

  function snapshot(group) {
    return {
      country: clean(group.actual.country.value),
      province: clean(group.actual.province.value),
      city: clean(group.actual.city.value),
      barangay: clean(group.actual.barangay.value),
      zip: clean(group.actual.zip.value),
    };
  }

  function syncFromStoredValues(group) {
    var saved = snapshot(group);
    var mode = saved.country && !sameText(saved.country, "Philippines")
      ? "foreign"
      : "philippines";
    return setMode(group, mode, saved);
  }

  function queueExternalSync(group) {
    if (group.updating || group.syncQueued) return;
    group.syncQueued = true;
    Promise.resolve().then(function () {
      group.syncQueued = false;
      if (!group.updating) syncFromStoredValues(group);
    });
  }

  function buildSelect(id, className, placeholder) {
    var select = document.createElement("select");
    select.id = id;
    select.className = className;
    select.appendChild(createOption("", placeholder));
    select.dataset.ready = "false";
    return select;
  }

  function initializeGroup(definition) {
    var actual = {
      country: field(definition.country),
      province: field(definition.province),
      city: field(definition.city),
      barangay: field(definition.barangay),
      zip: field(definition.zip),
    };

    if (!actual.country || !actual.province || !actual.city || !actual.barangay || !actual.zip) {
      return;
    }

    var group = {
      definition: definition,
      actual: actual,
      selects: {},
      labels: {},
      required: {},
      mode: "philippines",
      fallback: false,
      locked: false,
      zipAutomatic: false,
      updating: false,
      syncQueued: false,
      provinceRequestId: 0,
      cityRequestId: 0,
      barangayRequestId: 0,
      zipRequestId: 0,
      originalZipPlaceholder: actual.zip.getAttribute("placeholder") || "",
    };

    ["country", "province", "city", "barangay", "zip"].forEach(function (key) {
      group.required[key] = actual[key].required;
      groupsByFieldName[actual[key].name] = { group: group, key: key };
      actual[key].dataset.repafAddressField = key;
    });

    var countryWrapper = actual.country.closest(".repaf-field");
    var countryLabel = countryWrapper.querySelector("label");

    group.countryMode = buildSelect(
      actual.country.id + "_mode",
      "repaf-country-mode",
      "Select address country",
    );
    group.countryMode.textContent = "";
    countryOptions.forEach(function (country) {
      group.countryMode.appendChild(createOption(country, country));
    });
    group.countryMode.dataset.ready = "true";

    if (actual.country.tagName.toLocaleLowerCase() === "input") {
      actual.country.type = "hidden";
    }
    actual.country.hidden = true;
    setRequired(actual.country, false);
    countryWrapper.appendChild(group.countryMode);
    if (countryLabel) countryLabel.setAttribute("for", group.countryMode.id);

    group.selects.province = buildSelect(
      actual.province.id + "_ph",
      "repaf-address-select repaf-address-province",
      "Select province / state",
    );
    group.selects.city = buildSelect(
      actual.city.id + "_ph",
      "repaf-address-select repaf-address-city",
      "Select city / municipality",
    );
    group.selects.barangay = buildSelect(
      actual.barangay.id + "_ph",
      "repaf-address-select repaf-address-barangay",
      "Select barangay",
    );

    ["province", "city", "barangay"].forEach(function (key) {
      group.labels[key] = actual[key].closest(".repaf-field").querySelector("label");
      actual[key].insertAdjacentElement("afterend", group.selects[key]);
    });

    group.status = document.createElement("small");
    group.status.className = "repaf-address-status";
    group.status.setAttribute("aria-live", "polite");
    group.status.hidden = true;
    actual.zip.closest(".repaf-field").appendChild(group.status);

    // Residential templates are normalized here. Work/business templates keep
    // their explicit address sequence so unrelated occupation fields stay put.
    if (!definition.preserveOrder) {
      var grid = countryWrapper.closest(".repaf-form-grid");
      [actual.country, actual.province, actual.city, actual.barangay, actual.zip].forEach(
        function (control) {
          var wrapper = control.closest(".repaf-field");
          if (wrapper && wrapper.parentNode === grid) grid.appendChild(wrapper);
        },
      );
    }

    group.countryMode.addEventListener("change", function () {
      var saved = snapshot(group);
      saved.country = clean(group.countryMode.value);
      ["province", "city", "barangay", "zip"].forEach(function (key) {
        setStoredValue(group, key, "");
        saved[key] = "";
      });
      setMode(
        group,
        sameText(saved.country, "Philippines") ? "philippines" : "foreign",
        saved,
      );
    });

    group.selects.province.addEventListener("change", function () {
      var option = selectedOption(group.selects.province);
      setStoredValue(group, "province", group.selects.province.value);
      setStoredValue(group, "city", "");
      setStoredValue(group, "barangay", "");
      setStoredValue(group, "zip", "");
      group.zipAutomatic = false;
      if (option && option.dataset.code) {
        loadCities(group, option.dataset.code, "", "");
      } else {
        resetSelect(group.selects.city, "Select province / state first");
        resetSelect(group.selects.barangay, "Select city / municipality first");
        syncControlState(group);
      }
    });

    group.selects.city.addEventListener("change", function () {
      var option = selectedOption(group.selects.city);
      setStoredValue(group, "city", group.selects.city.value);
      setStoredValue(group, "barangay", "");
      setStoredValue(group, "zip", "");
      group.zipAutomatic = false;
      if (option && option.dataset.code) {
        loadBarangays(group, option.dataset.code, "");
      } else {
        resetSelect(group.selects.barangay, "Select city / municipality first");
        syncControlState(group);
      }
    });

    group.selects.barangay.addEventListener("change", function () {
      setStoredValue(group, "barangay", group.selects.barangay.value);
      setStoredValue(group, "zip", "");
      resolveZipCode(group);
    });

    ["country", "province", "city", "barangay", "zip"].forEach(function (key) {
      actual[key].addEventListener("input", function () {
        queueExternalSync(group);
      });
      actual[key].addEventListener("change", function () {
        queueExternalSync(group);
      });
    });

    // Employment and conditional-step logic can enable or disable the stored
    // controls after these visible dropdowns are created. Mirror those state
    // changes so an active work-address section never remains disabled.
    if (typeof MutationObserver !== "undefined") {
      group.disabledStateObserver = new MutationObserver(function () {
        syncControlState(group);
      });
      Object.keys(actual).forEach(function (key) {
        group.disabledStateObserver.observe(actual[key], {
          attributes: true,
          attributeFilter: ["disabled"],
        });
      });
    }

    groups.push(group);
    syncFromStoredValues(group);
  }

  // Begin the shared province request as soon as the form script starts. All
  // address groups reuse this promise, and later visits reuse localStorage.
  requestLocations("provinces");
  preloadCompleteCities();
  ADDRESS_GROUPS.forEach(initializeGroup);

  // Warm the postal-code helper while the user completes earlier fields.
  if (typeof window.requestIdleCallback === "function") {
    window.requestIdleCallback(function () {
      loadPostalHelper();
      if (canPreloadCompleteBarangays()) preloadCompleteBarangays();
    }, { timeout: 4000 });
  } else {
    window.setTimeout(function () {
      loadPostalHelper();
      if (canPreloadCompleteBarangays()) preloadCompleteBarangays();
    }, 1500);
  }

  document.addEventListener("repaf:conditional-required", function (event) {
    var detail = event.detail || {};
    var match = groupsByFieldName[detail.name];
    if (!match) return;

    match.group.required[match.key] = Boolean(detail.required);
    applyRequirement(match.group, match.key);
  });

  document.addEventListener("repaf:address-lock-change", function (event) {
    var detail = event.detail || {};
    var match = groupsByFieldName[detail.countryName];
    if (!match) return;

    match.group.locked = Boolean(detail.locked);
    syncControlState(match.group);
  });
})();
