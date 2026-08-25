(function () {
  const mapEl = document.querySelector("#leaflet-map");
  const wells = Array.isArray(window.WELLS_GEO_DATA) ? window.WELLS_GEO_DATA : [];

  if (!mapEl || typeof L === "undefined") {
    return;
  }

  // Bali's approximate geographic centre, zoomed to show the whole island.
  const map = L.map(mapEl, { scrollWheelZoom: true }).setView([-8.4095, 115.1889], 10);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  function markerIcon(status) {
    return L.divIcon({
      className: "",
      html: `<span class="leaflet-well-dot leaflet-well-dot--${status}"></span>`,
      iconSize: [18, 18],
      iconAnchor: [9, 9],
      popupAnchor: [0, -9],
    });
  }

  function popupHtml(well) {
    const photo = well.photo
      ? `<img class="well-popup__photo" src="${well.photo}" alt="Recharge well photo for ${well.id}">`
      : "";

    return `
      <div class="well-popup">
        ${photo}
        <h4>${well.id} · ${well.name ?? ""}</h4>
        <p class="well-popup__city">${well.city}</p>
        <dl>
          <dt>Status</dt><dd><i class="status-dot status-dot--${well.status}"></i>${well.statusLabel ?? "—"}</dd>
          <dt>Volume</dt><dd>${well.volume ?? "—"}</dd>
          <dt>Absorption</dt><dd>${well.inflow ?? "—"}</dd>
          <dt>Last transmission</dt><dd>${well.lastTransmission ?? "—"}</dd>
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
      const marker = L.marker([well.lat, well.lng], { icon: markerIcon(well.status) });
      marker.bindPopup(popupHtml(well));
      marker.addTo(map);
      return { well, marker };
    });

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
