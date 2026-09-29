const searchInput = document.querySelector("#well-search");
const wellRows = Array.from(document.querySelectorAll("[data-well-row]"));
const mapMarkers = Array.from(document.querySelectorAll(".map-marker[data-well]"));
const filterInputs = Array.from(document.querySelectorAll("[data-filter]"));
const noResults = document.querySelector("[data-no-results]");

const liveSensor = document.querySelector("[data-live-sensor]");
const liveOnlineSensors = document.querySelector("[data-live-online-sensors]");
const liveSensorStatus = document.querySelector("[data-live-sensor-status]");

function setLiveSensorText(selector, value) {
  const element = document.querySelector(selector);
  if (element) {
    element.textContent = String(value ?? "—");
  }
}

function setLiveSensorConnection(status, label) {
  const statusDot = document.querySelector("[data-live-status-dot]");
  if (statusDot) {
    statusDot.className = `status-dot status-dot--${status}`;
  }

  setLiveSensorText("[data-live-connection]", label);
  if (liveOnlineSensors) {
    liveOnlineSensors.textContent = status === "online" ? "1 / 1" : "0 / 1";
  }
}

function publishLiveSensor(detail) {
  window.LATEST_SENSOR_DATA = detail;
  window.dispatchEvent(new CustomEvent("idep:sensor-update", { detail }));
}

async function refreshLiveSensor() {
  try {
    const response = await fetch("/main/api/v1/sensor.php", {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });
    const payload = await response.json();

    if (!response.ok || !payload.status || !payload.data) {
      throw new Error(payload.message ?? "Sensor data unavailable");
    }

    const receivedAt = String(payload.received_at ?? "");
    const receivedDate = new Date(`${receivedAt.replace(" ", "T")}+08:00`);
    const ageMilliseconds = Date.now() - receivedDate.getTime();
    const isOnline = Number.isFinite(ageMilliseconds)
      && ageMilliseconds >= 0
      && ageMilliseconds <= 3 * 60 * 1000;

    setLiveSensorText("[data-live-device]", payload.data.id_device);
    setLiveSensorText("[data-live-h1]", payload.data.h1);
    setLiveSensorText("[data-live-h2]", payload.data.h2);
    setLiveSensorText("[data-live-hasil]", payload.data.hasil);
    setLiveSensorText("[data-live-received]", `${receivedAt} WITA`);
    const sensorStatus = isOnline ? "online" : "no-signal";
    const sensorStatusLabel = isOnline ? "Online" : "No recent signal";
    setLiveSensorConnection(sensorStatus, sensorStatusLabel);

    publishLiveSensor({
      deviceId: payload.data.id_device,
      status: sensorStatus,
      statusLabel: sensorStatusLabel,
      h1: payload.data.h1,
      h2: payload.data.h2,
      hasil: payload.data.hasil,
      receivedAt,
    });

    if (liveSensorStatus) {
      liveSensorStatus.textContent = isOnline
        ? `${payload.data.id_device} transmitting normally`
        : `${payload.data.id_device} has not transmitted recently`;
    }
    liveSensor?.classList.remove("is-error");
  } catch {
    setLiveSensorConnection("offline", "Unavailable");
    publishLiveSensor({
      deviceId: "device_1",
      status: "offline",
      statusLabel: "Unavailable",
      h1: "—",
      h2: "—",
      hasil: "—",
      receivedAt: "—",
    });
    if (liveSensorStatus) {
      liveSensorStatus.textContent = "Unable to load device_1";
    }
    liveSensor?.classList.add("is-error");
  }
}

refreshLiveSensor();
window.setInterval(refreshLiveSensor, 60_000);

function readWell(element) {
  try {
    return JSON.parse(element.dataset.well ?? "{}");
  } catch {
    return {};
  }
}

function activeFilter(name) {
  const control = document.querySelector(`[data-filter="${name}"]`);
  return control instanceof HTMLInputElement || control instanceof HTMLSelectElement
    ? control.value.trim().toLowerCase()
    : "";
}

function wellMatches(well, searchableText = "") {
  const query = searchInput?.value.trim().toLowerCase() ?? "";
  const city = activeFilter("city");
  const id = activeFilter("id");
  const status = activeFilter("status");
  const startDate = activeFilter("start-date");
  const endDate = activeFilter("end-date");
  const searchHaystack = `${well.id ?? ""} ${well.name ?? ""} ${well.city ?? ""} ${well.statusLabel ?? ""} ${searchableText}`.toLowerCase();

  // The well's monitoring window (startDateISO –> endDateISO) overlaps the requested range as long as 
  // it doesn't end before the range starts, or start after the range ends.
  const wellStart = well.startDateISO ?? "";
  const wellEnd = well.endDateISO ?? "";
  const withinStart = !startDate || !wellEnd || wellEnd >= startDate;
  const withinEnd = !endDate || !wellStart || wellStart <= endDate;

  return (
    (!query || searchHaystack.includes(query)) &&
    (!city || String(well.city ?? "").toLowerCase() === city) &&
    (!id || String(well.id ?? "").toLowerCase() === id) &&
    (!status || well.status === status) &&
    withinStart &&
    withinEnd
  );
}

const wellsStatsPanel = document.querySelector("[data-wells-stats]");

function updateWellsStats(visibleWells) {
  if (!wellsStatsPanel) {
    return;
  }

  const counts = { total: visibleWells.length, online: 0, offline: 0, "no-signal": 0 };
  visibleWells.forEach((well) => {
    if (Object.prototype.hasOwnProperty.call(counts, well.status)) {
      counts[well.status] += 1;
    }
  });

  Object.entries(counts).forEach(([key, value]) => {
    const field = wellsStatsPanel.querySelector(`[data-stat="${key}"]`);
    if (field) {
      field.textContent = String(value);
    }
  });
}

function applyFilters() {
  let visibleRows = 0;
  const visibleWells = [];

  wellRows.forEach((row) => {
    const well = readWell(row);
    const visible = wellMatches(well, row.dataset.search ?? "");
    row.hidden = !visible;
    if (visible) {
      visibleRows += 1;
      visibleWells.push(well);
    }
  });

  mapMarkers.forEach((marker) => {
    marker.hidden = !wellMatches(readWell(marker));
  });

  if (typeof window.applyLeafletMarkerFilters === "function") {
    window.applyLeafletMarkerFilters((well) => wellMatches(well));
  }

  if (noResults) {
    noResults.hidden = visibleRows > 0 || wellRows.length === 0;
  }

  updateWellsStats(visibleWells);
}

searchInput?.addEventListener("input", applyFilters);
filterInputs.forEach((control) => control.addEventListener("change", applyFilters));

// Cascading Well ID filter: narrow the "Well ID" dropdown to only the wells in the selected city, and keep the City dropdown 
// in sync if a Well ID is picked directly. Runs on any page that has both filters (wells + map).
const cityFilterSelect = document.querySelector('[data-filter="city"]');
const wellIdFilterSelect = document.querySelector('[data-filter="id"]');

function narrowWellIdOptions() {
  if (!(cityFilterSelect instanceof HTMLSelectElement) || !(wellIdFilterSelect instanceof HTMLSelectElement)) {
    return;
  }

  const city = cityFilterSelect.value;
  let selectionStillValid = false;

  Array.from(wellIdFilterSelect.options).forEach((option) => {
    if (option.value === "") {
      return; // always keep the "All Wells in City" option visible
    }
    const matchesCity = !city || option.dataset.city === city;
    option.hidden = !matchesCity;
    if (matchesCity && option.value === wellIdFilterSelect.value) {
      selectionStillValid = true;
    }
  });

  if (!selectionStillValid) {
    wellIdFilterSelect.value = "";
  }
}

cityFilterSelect?.addEventListener("change", narrowWellIdOptions);

wellIdFilterSelect?.addEventListener("change", () => {
  const chosen = Array.from(wellIdFilterSelect.options).find((option) => option.value === wellIdFilterSelect.value);
  if (chosen?.dataset.city && cityFilterSelect instanceof HTMLSelectElement) {
    cityFilterSelect.value = chosen.dataset.city;
  }
});

narrowWellIdOptions();

function updateSelectedWell(well) {
  const selectedPanel = document.querySelector("[data-selected-well]");
  if (!selectedPanel) {
    return;
  }

  selectedPanel.querySelectorAll("[data-detail]").forEach((field) => {
    const key = field.dataset.detail;
    if (key && well[key] !== undefined) {
      field.textContent = well[key];
    }
  });

  const photo = selectedPanel.querySelector("[data-detail-photo]");
  if (photo instanceof HTMLImageElement && well.photo) {
    photo.src = well.photo;
    photo.alt = `${well.id} recharge well placeholder photo`;
  }

  selectedPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function selectWellRow(row) {
  wellRows.forEach((item) => item.classList.remove("is-selected"));
  row.classList.add("is-selected");
  updateSelectedWell(readWell(row));
}

wellRows.forEach((row) => {
  row.addEventListener("click", () => selectWellRow(row));
  row.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      selectWellRow(row);
    }
  });
});

const defaultSelectedRow = wellRows.find((row) => readWell(row).id === "RW-12");
defaultSelectedRow?.classList.add("is-selected");

function populateMapPopover(stage, marker, well) {
  const popover = stage.querySelector("[data-map-popover]");
  if (!(popover instanceof HTMLElement)) {
    return;
  }

  const values = {
    "[data-popover-name]": well.name,
    "[data-popover-id]": well.id,
    "[data-popover-city]": well.city,
    "[data-popover-status]": well.statusLabel,
    "[data-popover-volume]": well.volume,
    "[data-popover-inflow]": well.inflow,
    "[data-popover-last]": well.lastTransmission,
  };

  Object.entries(values).forEach(([selector, value]) => {
    const target = popover.querySelector(selector);
    if (target) {
      target.textContent = value ?? "—";
    }
  });

  const photo = popover.querySelector("[data-popover-photo]");
  if (photo instanceof HTMLImageElement && well.photo) {
    photo.src = well.photo;
    photo.alt = `${well.id} recharge well placeholder photo`;
  }

  stage.querySelectorAll(".map-marker").forEach((item) => item.classList.remove("is-selected"));
  marker.classList.add("is-selected");

  const stageWidth = stage.clientWidth;
  const stageHeight = stage.clientHeight;
  const markerX = (Number(well.x) / 100) * stageWidth;
  const markerY = (Number(well.y) / 100) * stageHeight;
  const popoverWidth = Math.min(280, stageWidth - 24);
  const preferredLeft = markerX > stageWidth * 0.58 ? markerX - popoverWidth - 18 : markerX + 18;
  const preferredTop = markerY > stageHeight * 0.55 ? markerY - 230 : markerY - 20;
  const left = Math.max(10, Math.min(stageWidth - popoverWidth - 10, preferredLeft));
  const top = Math.max(10, Math.min(stageHeight - 220, preferredTop));

  popover.style.setProperty("--popover-x", `${left}px`);
  popover.style.setProperty("--popover-y", `${top}px`);
  popover.hidden = false;
}

mapMarkers.forEach((marker) => {
  marker.addEventListener("click", () => {
    const stage = marker.closest("[data-map-stage]");
    if (stage instanceof HTMLElement) {
      populateMapPopover(stage, marker, readWell(marker));
    }
  });
});

document.querySelectorAll("[data-map-close]").forEach((button) => {
  button.addEventListener("click", () => {
    const stage = button.closest("[data-map-stage]");
    const popover = button.closest("[data-map-popover]");
    if (popover instanceof HTMLElement) {
      popover.hidden = true;
    }
    stage?.querySelectorAll(".map-marker").forEach((marker) => marker.classList.remove("is-selected"));
  });
});

document.querySelectorAll(".measurement-row[data-well]").forEach((row) => {
  row.addEventListener("click", () => {
    const well = readWell(row);
    const marker = mapMarkers.find((item) => readWell(item).id === well.id);
    marker?.click();
  });
});

applyFilters();

document.querySelector("[data-export-wells]")?.addEventListener("click", () => {
  const header = ["Well ID", "City", "Start Date", "End Date", "Duration", "Total Water Absorption", "Transmission", "Status"];
  const csvRows = [header.join(",")];

  wellRows
    .filter((row) => !row.hidden)
    .forEach((row) => {
      const well = readWell(row);
      const values = [well.id, well.city, well.startDate, well.endDate, well.duration, well.absorption, well.transmission, well.statusLabel];
      csvRows.push(values.map((value) => `"${String(value ?? "").replace(/"/g, '""')}"`).join(","));
    });

  downloadTextFile("idep-monitoring-wells.csv", csvRows.join("\n"));
});

const operationalSearchRows = Array.from(document.querySelectorAll("[data-history-row], .alert-row[data-search]"));

function applyOperationalSearch() {
  const query = searchInput?.value.trim().toLowerCase() ?? "";
  operationalSearchRows.forEach((row) => {
    const matchesSearch = !query || (row.dataset.search ?? "").includes(query);
    const villageFilter = document.querySelector('[data-history-filter="village"]');
    const village = villageFilter instanceof HTMLSelectElement ? villageFilter.value : "";
    const matchesVillage = !row.matches("[data-history-row]") || !village || row.dataset.village === village;
    const typeFilter = document.querySelector('[data-history-filter="type"]');
    const wellType = typeFilter instanceof HTMLSelectElement ? typeFilter.value : "";
    const matchesType = !row.matches("[data-history-row]") || !wellType || row.dataset.wellType === wellType;
    const periodFilter = document.querySelector('[data-history-filter="period"]');
    const period = periodFilter instanceof HTMLSelectElement ? Number(periodFilter.value) : 0;
    const readingDate = row.dataset.readingDate ? new Date(row.dataset.readingDate) : null;
    const cutoff = period > 0 ? Date.now() - period * 24 * 60 * 60 * 1000 : 0;
    const matchesPeriod = !row.matches("[data-history-row]") || !period || !readingDate || readingDate.getTime() >= cutoff;
    row.hidden = !(matchesSearch && matchesVillage && matchesType && matchesPeriod);
  });

  drawHistoricalChart();
}

searchInput?.addEventListener("input", applyOperationalSearch);
document.querySelectorAll("[data-history-filter]").forEach((filter) => filter.addEventListener("change", applyOperationalSearch));

const historicalChart = document.querySelector("#historical-chart");

function drawHistoricalChart() {
  if (!(historicalChart instanceof HTMLCanvasElement)) {
    return;
  }

  const bounds = historicalChart.getBoundingClientRect();
  const scale = window.devicePixelRatio || 1;
  historicalChart.width = Math.max(1, Math.round(bounds.width * scale));
  historicalChart.height = Math.max(1, Math.round(bounds.height * scale));

  const context = historicalChart.getContext("2d");
  if (!context) {
    return;
  }

  context.scale(scale, scale);
  const width = bounds.width;
  const height = bounds.height;
  const padding = { top: 20, right: 22, bottom: 20, left: 22 };
  const visibleRows = Array.from(document.querySelectorAll("[data-history-row]"))
    .filter((row) => !row.hidden)
    .slice(0, 20)
    .reverse();
  const values = visibleRows
    .map((row) => Number(row.dataset.waterValue))
    .filter((value) => Number.isFinite(value));
  const chartValues = values.length > 0 ? values : [0];
  const dataMinimum = Math.min(...chartValues);
  const dataMaximum = Math.max(...chartValues);
  const range = Math.max(0.5, dataMaximum - dataMinimum);
  const minimum = Math.max(0, dataMinimum - range * 0.18);
  const maximum = dataMaximum + range * 0.18;
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;

  context.clearRect(0, 0, width, height);
  context.strokeStyle = "#ded8c7";
  context.lineWidth = 1;

  for (let line = 0; line < 5; line += 1) {
    const y = padding.top + (plotHeight / 4) * line;
    context.beginPath();
    context.moveTo(padding.left, y);
    context.lineTo(width - padding.right, y);
    context.stroke();
  }

  const points = chartValues.map((value, index) => ({
    x: chartValues.length === 1
      ? padding.left + plotWidth / 2
      : padding.left + (plotWidth / (chartValues.length - 1)) * index,
    y: padding.top + ((maximum - value) / (maximum - minimum)) * plotHeight,
  }));

  context.strokeStyle = "#619bbb";
  context.lineWidth = 3;
  context.lineJoin = "round";
  context.beginPath();
  points.forEach((point, index) => index === 0 ? context.moveTo(point.x, point.y) : context.lineTo(point.x, point.y));
  context.stroke();

  points.forEach((point) => {
    context.beginPath();
    context.arc(point.x, point.y, 4, 0, Math.PI * 2);
    context.fillStyle = "#ffffff";
    context.fill();
    context.strokeStyle = "#619bbb";
    context.lineWidth = 1.5;
    context.stroke();
  });
}

drawHistoricalChart();
window.addEventListener("resize", drawHistoricalChart);

function downloadTextFile(filename, content, mimeType = "text/csv;charset=utf-8") {
  const blob = new Blob([content], { type: mimeType });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

function csvEscape(value) {
  return `"${String(value ?? "").replace(/"/g, '""')}"`;
}

function htmlEscape(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function rowValues(row) {
  return Array.from(row?.querySelectorAll("td") ?? []).map((cell) => cell.textContent.trim());
}

function exportRecord(button) {
  const row = button.closest("tr");
  const values = rowValues(row).slice(0, -1);
  const format = (button.dataset.recordExport ?? "CSV").toLowerCase();
  const safeName = values[0]?.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") || "record";

  if (format === "pdf") {
    const printWindow = window.open("", "_blank");
    if (!printWindow) {
      return;
    }
    printWindow.opener = null;
    printWindow.document.write(`<!doctype html><html><head><title>${htmlEscape(values[0] ?? "IDEP Report")}</title><style>body{font-family:Arial,sans-serif;padding:36px;color:#141a21}h1{font-size:24px}dl{display:grid;grid-template-columns:180px 1fr;gap:10px}dt{font-weight:700}dd{margin:0}</style></head><body><h1>${htmlEscape(values[0] ?? "IDEP Report")}</h1><dl>${values.map((value, index) => `<dt>Field ${index + 1}</dt><dd>${htmlEscape(value)}</dd>`).join("")}</dl><script>window.print()<\/script></body></html>`);
    printWindow.document.close();
    return;
  }

  if (format === "excel") {
    const tableCells = values.map((value) => `<td>${value}</td>`).join("");
    downloadTextFile(`${safeName}.xls`, `<table><tr>${tableCells}</tr></table>`, "application/vnd.ms-excel;charset=utf-8");
    return;
  }

  downloadTextFile(`${safeName}.csv`, values.map(csvEscape).join(","));
}

document.querySelector("[data-export-measurements]")?.addEventListener("click", () => {
  const rows = Array.from(document.querySelectorAll("[data-history-row]")).filter((row) => !row.hidden);
  const csvRows = ["Date,Well ID,Village,Water Level,Data Quality"];
  rows.forEach((row) => {
    const cells = Array.from(row.querySelectorAll("td")).slice(0, 5).map((cell) => `"${cell.textContent.trim()}"`);
    csvRows.push(cells.join(","));
  });
  downloadTextFile("idep-historical-measurements.csv", csvRows.join("\n"));
});

document.addEventListener("click", (event) => {
  const button = event.target instanceof Element ? event.target.closest("[data-record-export]") : null;
  if (button instanceof HTMLButtonElement) {
    exportRecord(button);
  }
});

const alertRows = Array.from(document.querySelectorAll(".alert-row[data-alert]"));

alertRows.forEach((row) => {
  row.addEventListener("click", () => {
    let alert = {};
    try {
      alert = JSON.parse(row.dataset.alert ?? "{}");
    } catch {
      alert = {};
    }

    alertRows.forEach((item) => item.classList.remove("is-selected"));
    row.classList.add("is-selected");
    document.querySelectorAll("[data-alert-field]").forEach((field) => {
      const key = field.dataset.alertField;
      if (key && alert[key] !== undefined) {
        field.textContent = alert[key];
      }
    });
  });
});

const reportTable = document.querySelector("[data-report-table]");
const reportMessage = document.querySelector("[data-report-message]");
const reportStorageKey = "idep-generated-reports";

function reportFilterValue(name, fallback) {
  const field = document.querySelector(`[data-report-filter="${name}"]`);
  return field instanceof HTMLSelectElement ? field.value || fallback : fallback;
}

function addGeneratedReportRow(report, prepend = true) {
  if (!(reportTable instanceof HTMLTableSectionElement)) {
    return null;
  }

  const row = prepend ? reportTable.insertRow(0) : reportTable.insertRow();
  row.dataset.generatedReport = "true";
  [report.name, report.period, report.createdBy, report.date, report.status].forEach((value) => {
    row.insertCell().textContent = value;
  });
  const exportCell = row.insertCell();
  const exportButton = document.createElement("button");
  exportButton.type = "button";
  exportButton.className = "export-pill";
  exportButton.dataset.recordExport = report.export;
  exportButton.textContent = report.export;
  exportCell.appendChild(exportButton);
  return row;
}

function savedGeneratedReports() {
  if (!(reportTable instanceof HTMLTableSectionElement)) {
    return [];
  }
  return Array.from(reportTable.querySelectorAll("tr[data-generated-report]"))
    .map((row) => {
      const cells = rowValues(row);
      return {
        name: cells[0],
        period: cells[1],
        createdBy: cells[2],
        date: cells[3],
        status: cells[4],
        export: row.querySelector("[data-record-export]")?.dataset.recordExport ?? "PDF",
      };
    });
}

function persistGeneratedReports() {
  try {
    localStorage.setItem(reportStorageKey, JSON.stringify(savedGeneratedReports()));
  } catch {
    // The report remains available for the current page if storage is unavailable.
  }
}

if (reportTable instanceof HTMLTableSectionElement) {
  try {
    const savedReports = JSON.parse(localStorage.getItem(reportStorageKey) ?? "[]");
    if (Array.isArray(savedReports)) {
      savedReports.slice().reverse().forEach((report) => addGeneratedReportRow(report, true));
    }
  } catch {
    // Ignore malformed browser storage and keep the server-provided rows.
  }
}

document.querySelectorAll("[data-generate-report]").forEach((button) => {
  button.addEventListener("click", () => {
    if (!(reportTable instanceof HTMLTableSectionElement)) {
      return;
    }

    const template = button.dataset.generateReport ?? reportFilterValue("template", "New Report");
    const village = reportFilterValue("village", "All Bali");
    const period = reportFilterValue("period", "Current selection");
    const name = village === "All Bali" ? template : `${village} · ${template}`;
    addGeneratedReportRow({
      name,
      period,
      createdBy: "Field Team",
      date: new Intl.DateTimeFormat("en-GB", { day: "2-digit", month: "short", timeZone: "Asia/Makassar" }).format(new Date()),
      status: "Ready",
      export: "PDF",
    });
    persistGeneratedReports();

    if (reportMessage) {
      reportMessage.textContent = `${name} has been generated and saved in this browser.`;
      reportMessage.hidden = false;
    }
  });
});

document.querySelector("[data-export-reports]")?.addEventListener("click", () => {
  const rows = Array.from(document.querySelectorAll("[data-report-table] tr"));
  const content = [
    "Report Name,Period,Created By,Created Date,Status,Export",
    ...rows.map((row) => rowValues(row).map(csvEscape).join(",")),
  ];
  downloadTextFile("idep-generated-reports.csv", content.join("\n"));
});

const settingsButtons = Array.from(document.querySelectorAll("[data-settings-section]"));
const settingsHeading = document.querySelector("[data-settings-heading]");
const settingsForm = document.querySelector("[data-settings-form]");
const settingsStorageKey = "idep-dashboard-settings";

if (settingsForm instanceof HTMLFormElement) {
  try {
    const savedSettings = JSON.parse(localStorage.getItem(settingsStorageKey) ?? "{}");
    Object.entries(savedSettings).forEach(([name, value]) => {
      const field = settingsForm.elements.namedItem(name);
      if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
        field.value = String(value);
      }
    });
  } catch {
    // Keep the server defaults when browser storage is unavailable or malformed.
  }
}

settingsButtons.forEach((button) => {
  button.addEventListener("click", () => {
    settingsButtons.forEach((item) => item.classList.remove("is-selected"));
    button.classList.add("is-selected");
    if (settingsHeading) {
      settingsHeading.textContent = button.dataset.settingsTitle ?? "Settings";
    }
  });
});

settingsForm?.addEventListener("submit", (event) => {
  event.preventDefault();
  if (settingsForm instanceof HTMLFormElement) {
    const values = Object.fromEntries(new FormData(settingsForm).entries());
    try {
      localStorage.setItem(settingsStorageKey, JSON.stringify(values));
    } catch {
      // The visible values remain usable for the current session.
    }
  }
  const message = document.querySelector("[data-settings-message]");
  if (message) {
    message.hidden = false;
  }
});

const hardwareForm = document.querySelector("[data-hardware-form]");
const openHardwareButton = document.querySelector("[data-open-hardware-form]");
const closeHardwareButton = document.querySelector("[data-close-hardware-form]");
const hardwareTable = document.querySelector("[data-hardware-table]");
const hardwareSubmitButton = document.querySelector("[data-hardware-submit]");
const hardwareMessage = document.querySelector("[data-hardware-message]");
const provinceSelect = document.querySelector("#province_id");
const districtSelect = document.querySelector("#district_id");
const subDistrictSelect = document.querySelector("#sub_district_id");
const villageSelect = document.querySelector("#village_id");
let editingHardwareRow = null;

function setHardwareFormOpen(open) {
  if (hardwareForm) {
    hardwareForm.hidden = !open;
  }
  if (openHardwareButton) {
    openHardwareButton.hidden = open;
  }
  if (closeHardwareButton) {
    closeHardwareButton.hidden = !open;
  }
}

function resetHardwareEditor() {
  if (hardwareForm instanceof HTMLFormElement) {
    hardwareForm.reset();
    setHardwareFormValue("well_id", "");
  }
  resetLocationSelect(districtSelect, "Select province first");
  resetLocationSelect(subDistrictSelect, "Select district first");
  resetLocationSelect(villageSelect, "Select sub-district first");
  editingHardwareRow = null;
  if (hardwareSubmitButton) {
    hardwareSubmitButton.disabled = false;
    hardwareSubmitButton.textContent = "Add device";
  }
  if (hardwareMessage) {
    hardwareMessage.hidden = true;
  }
}

openHardwareButton?.addEventListener("click", () => {
  resetHardwareEditor();
  setHardwareFormOpen(true);
});

closeHardwareButton?.addEventListener("click", () => {
  resetHardwareEditor();
  setHardwareFormOpen(false);
});

function updateHardwareCount() {
  const rows = Array.from(hardwareTable?.querySelectorAll("[data-hardware-row]") ?? []);
  const count = rows.length;
  const visibleCount = rows.filter((row) => !row.hidden).length;
  const counter = document.querySelector("[data-hardware-count]");
  if (counter) {
    counter.textContent = count === 0 ? "No entries" : `Showing ${visibleCount} of ${count} entries`;
  }
}

function setHardwareFormValue(name, value) {
  if (!(hardwareForm instanceof HTMLFormElement)) {
    return;
  }
  const field = hardwareForm.elements.namedItem(name);
  if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
    field.value = value ?? "";
  }
}

function resetLocationSelect(select, placeholder) {
  if (!(select instanceof HTMLSelectElement)) {
    return;
  }
  select.replaceChildren(new Option(placeholder, ""));
  select.disabled = true;
}

function populateLocationSelect(select, items, placeholder, selectedValue = "") {
  if (!(select instanceof HTMLSelectElement)) {
    return;
  }
  select.replaceChildren(new Option(placeholder, ""));
  items.forEach((item) => select.add(new Option(item.name, String(item.id))));
  select.disabled = false;
  select.value = String(selectedValue || "");
}

async function requestJson(url, options = {}) {
  const response = await fetch(url, options);
  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(result.message || "The request could not be completed.");
  }
  return result;
}

async function loadDistricts(provinceId, selectedValue = "") {
  resetLocationSelect(districtSelect, "Loading districts...");
  resetLocationSelect(subDistrictSelect, "Select district first");
  resetLocationSelect(villageSelect, "Select sub-district first");
  if (!provinceId) return;
  const items = await requestJson(`/main/ajax-get-districts.php?province_id=${encodeURIComponent(provinceId)}`);
  populateLocationSelect(districtSelect, items, "Select district/city", selectedValue);
}

async function loadSubDistricts(districtId, selectedValue = "") {
  resetLocationSelect(subDistrictSelect, "Loading sub-districts...");
  resetLocationSelect(villageSelect, "Select sub-district first");
  if (!districtId) return;
  const items = await requestJson(`/main/ajax-get-subdistricts.php?district_id=${encodeURIComponent(districtId)}`);
  populateLocationSelect(subDistrictSelect, items, "Select sub-district", selectedValue);
}

async function loadVillages(subDistrictId, selectedValue = "") {
  resetLocationSelect(villageSelect, "Loading villages...");
  if (!subDistrictId) return;
  const items = await requestJson(`/main/ajax-get-villages.php?sub_district_id=${encodeURIComponent(subDistrictId)}`);
  populateLocationSelect(villageSelect, items, "Select village", selectedValue);
}

provinceSelect?.addEventListener("change", async () => {
  try {
    await loadDistricts(provinceSelect.value);
  } catch (error) {
    if (hardwareMessage) {
      hardwareMessage.textContent = error.message;
      hardwareMessage.hidden = false;
    }
  }
});

districtSelect?.addEventListener("change", async () => {
  try {
    await loadSubDistricts(districtSelect.value);
  } catch (error) {
    if (hardwareMessage) {
      hardwareMessage.textContent = error.message;
      hardwareMessage.hidden = false;
    }
  }
});

subDistrictSelect?.addEventListener("change", async () => {
  try {
    await loadVillages(subDistrictSelect.value);
  } catch (error) {
    if (hardwareMessage) {
      hardwareMessage.textContent = error.message;
      hardwareMessage.hidden = false;
    }
  }
});

async function startHardwareEdit(row) {
  if (!(row instanceof HTMLTableRowElement)) {
    return;
  }

  editingHardwareRow = row;
  setHardwareFormOpen(true);
  setHardwareFormValue("well_id", row.dataset.wellId);
  setHardwareFormValue("name", row.dataset.name);
  setHardwareFormValue("sensor_id", row.dataset.sensorId);
  setHardwareFormValue("installer", row.dataset.installer);
  setHardwareFormValue("type", row.dataset.type);
  setHardwareFormValue("date", row.dataset.date);
  setHardwareFormValue("longitude", row.dataset.longitude);
  setHardwareFormValue("latitude", row.dataset.latitude);
  setHardwareFormValue("province_id", row.dataset.provinceId);

  try {
    await loadDistricts(row.dataset.provinceId, row.dataset.districtId);
    await loadSubDistricts(row.dataset.districtId, row.dataset.subDistrictId);
    await loadVillages(row.dataset.subDistrictId, row.dataset.villageId);
  } catch (error) {
    if (hardwareMessage) {
      hardwareMessage.textContent = error.message;
      hardwareMessage.hidden = false;
    }
  }

  if (hardwareSubmitButton) {
    hardwareSubmitButton.textContent = "Update device";
  }
  if (hardwareMessage) {
    hardwareMessage.textContent = `Editing ${row.dataset.name ?? "device"}.`;
    hardwareMessage.hidden = false;
  }
  hardwareForm?.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function refreshHardwareNameOptions() {
  const select = document.querySelector('[data-hardware-filter="name"]');
  if (!(select instanceof HTMLSelectElement) || !(hardwareTable instanceof HTMLTableSectionElement)) {
    return;
  }
  const selected = select.value;
  select.replaceChildren(new Option("All Wells in City", ""));
  Array.from(hardwareTable.querySelectorAll("[data-hardware-row]")).forEach((row) => {
    select.add(new Option(`${row.dataset.name} · ${row.dataset.city}`, row.dataset.name));
  });
  if (Array.from(select.options).some((option) => option.value === selected)) {
    select.value = selected;
  }
}

function applyHardwareFilters() {
  if (!(hardwareTable instanceof HTMLTableSectionElement)) {
    return;
  }
  const valueFor = (name) => {
    const field = document.querySelector(`[data-hardware-filter="${name}"]`);
    return field instanceof HTMLInputElement || field instanceof HTMLSelectElement ? field.value : "";
  };
  const city = valueFor("city");
  const name = valueFor("name");
  const startDate = valueFor("start-date");
  const endDate = valueFor("end-date");
  const query = searchInput?.value.trim().toLowerCase() ?? "";

  Array.from(hardwareTable.querySelectorAll("[data-hardware-row]")).forEach((row) => {
    const searchable = `${row.dataset.name} ${row.dataset.city} ${row.dataset.mac} ${row.dataset.installer}`.toLowerCase();
    const matches = (!city || row.dataset.city === city)
      && (!name || row.dataset.name === name)
      && (!startDate || !row.dataset.date || row.dataset.date >= startDate)
      && (!endDate || !row.dataset.date || row.dataset.date <= endDate)
      && (!query || searchable.includes(query));
    row.hidden = !matches;
  });
  updateHardwareCount();
}

if (hardwareTable instanceof HTMLTableSectionElement) {
  refreshHardwareNameOptions();
  applyHardwareFilters();
}

document.querySelectorAll("[data-hardware-filter]").forEach((field) => {
  field.addEventListener("change", applyHardwareFilters);
});
searchInput?.addEventListener("input", applyHardwareFilters);

hardwareForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!(hardwareForm instanceof HTMLFormElement)) {
    return;
  }

  const formData = new FormData(hardwareForm);
  if (hardwareSubmitButton) {
    hardwareSubmitButton.disabled = true;
    hardwareSubmitButton.textContent = "Saving...";
  }
  if (hardwareMessage) {
    hardwareMessage.hidden = true;
  }

  try {
    await requestJson("/main/admin-site-registration-save.php", {
      method: "POST",
      body: formData,
    });
    if (hardwareMessage) {
      hardwareMessage.textContent = editingHardwareRow
        ? "Site updated in the database. Reloading..."
        : "Site saved to the database. Reloading...";
      hardwareMessage.hidden = false;
    }
    window.setTimeout(() => window.location.reload(), 500);
  } catch (error) {
    if (hardwareSubmitButton) {
      hardwareSubmitButton.disabled = false;
      hardwareSubmitButton.textContent = editingHardwareRow ? "Update device" : "Add device";
    }
    if (hardwareMessage) {
      hardwareMessage.textContent = error.message;
      hardwareMessage.hidden = false;
    }
  }
});

hardwareTable?.addEventListener("click", async (event) => {
  const target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  const row = target.closest("tr");
  if (target.closest("[data-edit-row]")) {
    await startHardwareEdit(row);
    return;
  }

  if (target.closest("[data-remove-row]")) {
    if (!(row instanceof HTMLTableRowElement) || !row.dataset.wellId) {
      return;
    }
    if (!window.confirm(`Delete ${row.dataset.name || "this site"}?`)) {
      return;
    }
    const formData = new FormData();
    formData.set("well_id", row.dataset.wellId);
    try {
      await requestJson("/main/admin-site-registration-delete.php", {
        method: "POST",
        body: formData,
      });
      row.remove();
      if (row === editingHardwareRow) {
        resetHardwareEditor();
      }
      refreshHardwareNameOptions();
      applyHardwareFilters();
    } catch (error) {
      if (hardwareMessage) {
        hardwareMessage.textContent = error.message;
        hardwareMessage.hidden = false;
      }
    }
  }
});

const userForm = document.querySelector("[data-user-form]");
const userTable = document.querySelector("[data-user-table]");
const userFormTitle = document.querySelector("[data-user-form-title]");
const userSubmitButton = document.querySelector("[data-user-submit]");
const userCancelButton = document.querySelector("[data-user-cancel]");
const userMessage = document.querySelector("[data-user-message]");
const passwordHelp = document.querySelector("[data-password-help]");

function setUserFormMessage(message, isError = false) {
  if (!userMessage) return;
  userMessage.textContent = message;
  userMessage.classList.toggle("is-error", isError);
  userMessage.hidden = false;
}

function resetUserForm() {
  if (!(userForm instanceof HTMLFormElement)) return;
  userForm.reset();
  const userId = userForm.elements.namedItem("user_id");
  if (userId instanceof HTMLInputElement) userId.value = "";
  if (userFormTitle) userFormTitle.textContent = "Create user";
  if (userSubmitButton) userSubmitButton.textContent = "Save user";
  if (userCancelButton) userCancelButton.hidden = true;
  if (passwordHelp) passwordHelp.textContent = "Required for a new user.";
  if (userMessage) userMessage.hidden = true;
}

userCancelButton?.addEventListener("click", resetUserForm);

userTable?.addEventListener("click", async (event) => {
  const target = event.target;
  if (!(target instanceof HTMLElement) || !(userForm instanceof HTMLFormElement)) return;
  const row = target.closest("[data-user-row]");
  if (!(row instanceof HTMLTableRowElement)) return;

  if (target.closest("[data-user-edit]")) {
    const setValue = (name, value) => {
      const field = userForm.elements.namedItem(name);
      if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
        field.value = value ?? "";
      }
    };
    setValue("user_id", row.dataset.userId);
    setValue("name", row.dataset.name);
    setValue("username", row.dataset.username);
    setValue("email", row.dataset.email);
    setValue("status", row.dataset.role);
    setValue("password", "");
    if (userFormTitle) userFormTitle.textContent = `Edit ${row.dataset.name || "user"}`;
    if (userSubmitButton) userSubmitButton.textContent = "Update user";
    if (userCancelButton) userCancelButton.hidden = false;
    if (passwordHelp) passwordHelp.textContent = "Leave blank to keep the current password.";
    if (userMessage) userMessage.hidden = true;
    userForm.scrollIntoView({ behavior: "smooth", block: "start" });
    return;
  }

  const deleteButton = target.closest("[data-user-delete]");
  if (deleteButton instanceof HTMLButtonElement && !deleteButton.disabled) {
    if (!window.confirm(`Permanently delete ${row.dataset.name || "this user"}? This cannot be undone.`)) return;
    const formData = new FormData();
    formData.set("user_id", row.dataset.userId ?? "");
    deleteButton.disabled = true;
    try {
      await requestJson("/main/admin-user-delete.php", { method: "POST", body: formData });
      row.remove();
      setUserFormMessage("User permanently deleted from the database.");
    } catch (error) {
      deleteButton.disabled = false;
      setUserFormMessage(error.message, true);
    }
    return;
  }

  const toggleButton = target.closest("[data-user-toggle]");
  if (!(toggleButton instanceof HTMLButtonElement) || toggleButton.disabled) return;
  const action = toggleButton.dataset.action;
  if (!window.confirm(`${action === "activate" ? "Activate" : "Deactivate"} ${row.dataset.name || "this user"}?`)) return;

  const formData = new FormData();
  formData.set("user_id", row.dataset.userId ?? "");
  formData.set("action", action ?? "");
  toggleButton.disabled = true;
  try {
    await requestJson("/main/admin-user-toggle.php", { method: "POST", body: formData });
    window.location.reload();
  } catch (error) {
    toggleButton.disabled = false;
    setUserFormMessage(error.message, true);
  }
});

userForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!(userForm instanceof HTMLFormElement)) return;

  const formData = new FormData(userForm);
  if (userSubmitButton) {
    userSubmitButton.disabled = true;
    userSubmitButton.textContent = "Saving...";
  }
  if (userMessage) userMessage.hidden = true;

  try {
    await requestJson("/main/admin-user-save.php", { method: "POST", body: formData });
    setUserFormMessage("User saved in the database. Reloading...");
    window.setTimeout(() => window.location.reload(), 450);
  } catch (error) {
    if (userSubmitButton) {
      userSubmitButton.disabled = false;
      userSubmitButton.textContent = formData.get("user_id") ? "Update user" : "Save user";
    }
    setUserFormMessage(error.message, true);
  }
});
