const searchInput = document.querySelector("#well-search");
const wellRows = Array.from(document.querySelectorAll("[data-well-row]"));
const mapMarkers = Array.from(document.querySelectorAll(".map-marker[data-well]"));
const filterInputs = Array.from(document.querySelectorAll("[data-filter]"));
const noResults = document.querySelector("[data-no-results]");

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
  const searchHaystack = `${well.id ?? ""} ${well.name ?? ""} ${well.city ?? ""} ${well.statusLabel ?? ""} ${searchableText}`.toLowerCase();

  return (
    (!query || searchHaystack.includes(query)) &&
    (!city || String(well.city ?? "").toLowerCase() === city) &&
    (!id || String(well.id ?? "").toLowerCase() === id) &&
    (!status || well.status === status)
  );
}

function applyFilters() {
  let visibleRows = 0;

  wellRows.forEach((row) => {
    const visible = wellMatches(readWell(row), row.dataset.search ?? "");
    row.hidden = !visible;
    visibleRows += visible ? 1 : 0;
  });

  mapMarkers.forEach((marker) => {
    marker.hidden = !wellMatches(readWell(marker));
  });

  if (noResults) {
    noResults.hidden = visibleRows > 0 || wellRows.length === 0;
  }
}

searchInput?.addEventListener("input", applyFilters);
filterInputs.forEach((control) => control.addEventListener("change", applyFilters));

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

const operationalSearchRows = Array.from(document.querySelectorAll("[data-history-row], .alert-row[data-search]"));

function applyOperationalSearch() {
  const query = searchInput?.value.trim().toLowerCase() ?? "";
  operationalSearchRows.forEach((row) => {
    const matchesSearch = !query || (row.dataset.search ?? "").includes(query);
    const villageFilter = document.querySelector('[data-history-filter="village"]');
    const village = villageFilter instanceof HTMLSelectElement ? villageFilter.value : "";
    const matchesVillage = !row.matches("[data-history-row]") || !village || row.dataset.village === village;
    row.hidden = !(matchesSearch && matchesVillage);
  });
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
  const values = [2.18, 2.55, 1.72, 1.43, 1.56, 2.38, 2.16, 2.64, 2.75, 2.32];
  const minimum = 1.1;
  const maximum = 3.1;
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

  const points = values.map((value, index) => ({
    x: padding.left + (plotWidth / (values.length - 1)) * index,
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

function downloadTextFile(filename, content) {
  const blob = new Blob([content], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
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

document.querySelectorAll("[data-record-export]").forEach((button) => {
  button.addEventListener("click", () => {
    const rowText = button.closest("tr")?.innerText.replaceAll("\t", ",") ?? "IDEP export";
    downloadTextFile(`idep-${button.dataset.recordExport?.toLowerCase() ?? "export"}.csv`, rowText);
  });
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

document.querySelectorAll("[data-generate-report]").forEach((button) => {
  button.addEventListener("click", () => {
    if (!(reportTable instanceof HTMLTableSectionElement)) {
      return;
    }

    const name = button.dataset.generateReport ?? "New Report";
    const row = reportTable.insertRow(0);
    [name, "Current selection", "Research Admin", "Today", "Ready"].forEach((value) => {
      row.insertCell().textContent = value;
    });
    const exportCell = row.insertCell();
    const exportButton = document.createElement("button");
    exportButton.type = "button";
    exportButton.className = "export-pill";
    exportButton.textContent = "PDF";
    exportButton.addEventListener("click", () => downloadTextFile("idep-report.csv", row.innerText.replaceAll("\t", ",")));
    exportCell.appendChild(exportButton);

    if (reportMessage) {
      reportMessage.textContent = `${name} has been generated as a prototype report.`;
      reportMessage.hidden = false;
    }
  });
});

document.querySelector("[data-export-reports]")?.addEventListener("click", () => {
  const rows = Array.from(document.querySelectorAll("[data-report-table] tr"));
  const content = ["Report Name,Period,Created By,Created Date,Status,Export", ...rows.map((row) => row.innerText.replaceAll("\t", ","))];
  downloadTextFile("idep-generated-reports.csv", content.join("\n"));
});

const settingsButtons = Array.from(document.querySelectorAll("[data-settings-section]"));
const settingsHeading = document.querySelector("[data-settings-heading]");

settingsButtons.forEach((button) => {
  button.addEventListener("click", () => {
    settingsButtons.forEach((item) => item.classList.remove("is-selected"));
    button.classList.add("is-selected");
    if (settingsHeading) {
      settingsHeading.textContent = button.dataset.settingsTitle ?? "Settings";
    }
  });
});

document.querySelector("[data-settings-form]")?.addEventListener("submit", (event) => {
  event.preventDefault();
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
  }
  editingHardwareRow = null;
  if (hardwareSubmitButton) {
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
  const count = hardwareTable?.querySelectorAll("tr").length ?? 0;
  const counter = document.querySelector("[data-hardware-count]");
  if (counter) {
    counter.textContent = count === 0 ? "No entries" : `Showing 1 to ${count} of ${count} entries`;
  }
}

function formatHardwareDate(date) {
  const parts = String(date).split("-");
  return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : String(date || "Today");
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

function startHardwareEdit(row) {
  if (!(row instanceof HTMLTableRowElement)) {
    return;
  }

  editingHardwareRow = row;
  setHardwareFormOpen(true);
  setHardwareFormValue("name", row.dataset.name);
  setHardwareFormValue("installer", row.dataset.installer);
  setHardwareFormValue("type", row.dataset.type);
  setHardwareFormValue("date", row.dataset.date);
  setHardwareFormValue("longitude", row.dataset.longitude);
  setHardwareFormValue("city", row.dataset.city);
  setHardwareFormValue("latitude", row.dataset.latitude);
  setHardwareFormValue("mac", row.dataset.mac);

  if (hardwareSubmitButton) {
    hardwareSubmitButton.textContent = "Update device";
  }
  if (hardwareMessage) {
    hardwareMessage.textContent = `Editing ${row.dataset.name ?? "device"}.`;
    hardwareMessage.hidden = false;
  }
  hardwareForm?.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function updateHardwareRow(row, values) {
  row.dataset.hardwareRow = "";
  row.dataset.name = values.name;
  row.dataset.city = values.city;
  row.dataset.mac = values.mac;
  row.dataset.date = values.date;
  row.dataset.installer = values.installer;
  row.dataset.type = values.type;
  row.dataset.longitude = values.longitude;
  row.dataset.latitude = values.latitude;

  while (row.cells.length < 5) {
    row.insertCell();
  }
  row.cells[0].textContent = `${values.name} - ${values.city}`;
  row.cells[1].textContent = `MAC: ${values.mac}`;
  row.cells[2].textContent = formatHardwareDate(values.date);
  row.cells[3].textContent = values.installer || "—";

  const actionCell = row.cells[4];
  actionCell.textContent = "";
  const editButton = document.createElement("button");
  editButton.type = "button";
  editButton.className = "row-icon-button";
  editButton.dataset.editRow = "";
  editButton.setAttribute("aria-label", `Edit ${values.name}`);
  editButton.textContent = "✎";
  const removeButton = document.createElement("button");
  removeButton.type = "button";
  removeButton.className = "row-icon-button";
  removeButton.dataset.removeRow = "";
  removeButton.setAttribute("aria-label", `Delete ${values.name}`);
  removeButton.textContent = "▣";
  actionCell.append(editButton, removeButton);
}

hardwareForm?.addEventListener("submit", (event) => {
  event.preventDefault();
  if (!(hardwareForm instanceof HTMLFormElement) || !(hardwareTable instanceof HTMLTableSectionElement)) {
    return;
  }

  const formData = new FormData(hardwareForm);
  const values = {
    name: String(formData.get("name") ?? ""),
    installer: String(formData.get("installer") ?? ""),
    type: String(formData.get("type") ?? ""),
    date: String(formData.get("date") ?? ""),
    longitude: String(formData.get("longitude") ?? ""),
    city: String(formData.get("city") ?? ""),
    latitude: String(formData.get("latitude") ?? ""),
    mac: String(formData.get("mac") ?? ""),
  };

  const wasEditing = editingHardwareRow instanceof HTMLTableRowElement;
  const row = wasEditing ? editingHardwareRow : hardwareTable.insertRow();
  updateHardwareRow(row, values);

  hardwareForm.reset();
  editingHardwareRow = null;
  if (hardwareSubmitButton) {
    hardwareSubmitButton.textContent = "Add device";
  }
  if (hardwareMessage) {
    hardwareMessage.textContent = wasEditing
      ? "Device updated temporarily. Database connection will be added later."
      : "Device added temporarily. Database connection will be added later.";
    hardwareMessage.hidden = false;
  }
  updateHardwareCount();
});

hardwareTable?.addEventListener("click", (event) => {
  const target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  const row = target.closest("tr");
  if (target.closest("[data-edit-row]")) {
    startHardwareEdit(row);
    return;
  }

  if (target.closest("[data-remove-row]")) {
    if (row === editingHardwareRow) {
      resetHardwareEditor();
    }
    row?.remove();
    updateHardwareCount();
  }
});
