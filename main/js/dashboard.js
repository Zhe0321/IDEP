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
