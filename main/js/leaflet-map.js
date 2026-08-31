(function () {
  const mapEl = document.querySelector("#leaflet-map");
  const wells = Array.isArray(window.WELLS_GEO_DATA) ? window.WELLS_GEO_DATA : [];

  if (!mapEl || typeof L === "undefined") {
    return;
  }

  function escapeHtml(value) {
    return String(value ?? "—").replace(/[&<>"']/g, (character) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    })[character]);
  }

  // Bali's approximate geographic centre, zoomed to show the whole island.
  const map = L.map(mapEl, { scrollWheelZoom: true }).setView([-8.4095, 115.1889], 10);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  function markerIcon(status, deviceId = null) {
    const liveDevice = deviceId
      ? `<span class="leaflet-live-device">
          <span class="leaflet-well-dot leaflet-well-dot--${status} leaflet-well-dot--live"></span>
          <span class="leaflet-live-device__label">${escapeHtml(deviceId)} · LIVE</span>
        </span>`
      : `<span class="leaflet-well-dot leaflet-well-dot--${status}"></span>`;

    return L.divIcon({
      className: deviceId ? "leaflet-live-device-icon" : "",
      html: liveDevice,
      iconSize: [18, 18],
      iconAnchor: [9, 9],
      popupAnchor: [0, -9],
    });
  }

  function popupHtml(well) {
    const photo = well.photo && !well.deviceId
      ? `<img class="well-popup__photo" src="${well.photo}" alt="Recharge well photo for ${well.id}">`
      : "";

    const identity = well.deviceId
      ? `
          <div class="well-popup__live-badge"><i></i>Live sensor</div>
          <h4 class="well-popup__device-name">${escapeHtml(well.deviceId)}</h4>
          <p class="well-popup__city">Connected to ${escapeHtml(well.id)} · ${escapeHtml(well.name)}, ${escapeHtml(well.city)}</p>
        `
      : `
          <h4>${well.id} · ${well.name ?? ""}</h4>
          <p class="well-popup__city">${well.city}</p>
        `;

    const measurementRows = well.deviceId
      ? `
          <dt>Device</dt><dd>${escapeHtml(well.deviceId)}</dd>
          <dt>H1</dt><dd>${escapeHtml(well.h1)}</dd>
          <dt>H2</dt><dd>${escapeHtml(well.h2)}</dd>
          <dt>Hasil</dt><dd>${escapeHtml(well.hasil)}</dd>
        `
      : `
          <dt>Volume</dt><dd>${well.volume ?? "—"}</dd>
          <dt>Absorption</dt><dd>${well.inflow ?? "—"}</dd>
        `;

    return `
      <div class="well-popup">
        ${photo}
        ${identity}
        <dl>
          <dt>Status</dt><dd><i class="status-dot status-dot--${well.status}"></i>${well.statusLabel ?? "—"}</dd>
          ${measurementRows}
          <dt>${well.deviceId ? "Last live update" : "Last transmission"}</dt><dd>${well.lastTransmission ?? "—"}</dd>
        </dl>
        <div class="well-popup__key">
          <span><i class="status-dot status-dot--online"></i>Online</span>
          <span><i class="status-dot status-dot--offline"></i>Offline</span>
          <span><i class="status-dot status-dot--no-signal"></i>No Signal</span>
        </div>
      </div>
    `;
  }

  const markerEntries = wells
    .filter((well) => typeof well.lat === "number" && typeof well.lng === "number")
    .map((well) => {
      const marker = L.marker([well.lat, well.lng], { icon: markerIcon(well.status, well.deviceId) });
      marker.bindPopup(popupHtml(well));
      marker.addTo(map);
      return { well, marker };
    });

  function applyLiveSensorToMap(sensor) {
    if (!sensor?.deviceId) {
      return;
    }

    const entry = markerEntries.find(({ well }) => well.deviceId === sensor.deviceId);
    if (!entry) {
      return;
    }

    Object.assign(entry.well, {
      status: sensor.status,
      statusLabel: sensor.statusLabel,
      h1: sensor.h1,
      h2: sensor.h2,
      hasil: sensor.hasil,
      lastTransmission: sensor.receivedAt === "—" ? "—" : `${sensor.receivedAt} WITA`,
    });
    entry.marker.setIcon(markerIcon(entry.well.status, entry.well.deviceId));
    entry.marker.setPopupContent(popupHtml(entry.well));
  }

  window.addEventListener("idep:sensor-update", (event) => {
    applyLiveSensorToMap(event.detail);
  });

  if (window.LATEST_SENSOR_DATA) {
    applyLiveSensorToMap(window.LATEST_SENSOR_DATA);
  }

  // Called by dashboard.js's applyFilters() whenever the shared filter
  // controls (city, well ID, date range) change, so the map stays in sync
  // with the wells table on the other pages.
  window.applyLeafletMarkerFilters = function applyLeafletMarkerFilters(matchesFn) {
    if (typeof matchesFn !== "function") {
      return;
    }

    markerEntries.forEach(({ well, marker }) => {
      const visible = matchesFn(well);
      const onMap = map.hasLayer(marker);
      if (visible && !onMap) {
        marker.addTo(map);
      } else if (!visible && onMap) {
        map.removeLayer(marker);
      }
    });
  };
})();
