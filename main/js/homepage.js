document.documentElement.classList.add("js");

const revealItems = document.querySelectorAll(".reveal");

if ("IntersectionObserver" in window) {
  const revealObserver = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.12 },
  );

  revealItems.forEach((item) => revealObserver.observe(item));
} else {
  revealItems.forEach((item) => item.classList.add("is-visible"));
}

const programmeMetricElements = document.querySelectorAll("[data-programme-metric]");

function renderProgrammeRows(selector, districts, valueKey, suffix = "", titleKey = "") {
  document.querySelectorAll(selector).forEach((container) => {
    const rows = districts.map((district) => {
      const row = document.createElement("p");
      const name = document.createElement("span");
      const value = document.createElement("span");
      name.textContent = district.name;
      value.textContent = `${district[valueKey]}${suffix}`;
      if (titleKey && district[titleKey]) {
        value.title = district[titleKey];
      }
      row.append(name, value);
      return row;
    });
    container.replaceChildren(...rows);
  });
}

function renderProgrammeMetrics(metrics) {
  const values = {
    "well-count": metrics.wellCount,
    "village-count": metrics.villageCount,
    "district-count": metrics.districtCount,
    "actual-litres": metrics.actualLitresLabel,
    "people-supported": metrics.peopleSupportedLabel,
    "families-supported": metrics.familiesSupportedLabel,
  };

  Object.entries(values).forEach(([name, value]) => {
    document.querySelectorAll(`[data-programme-metric="${name}"]`).forEach((element) => {
      element.textContent = String(value ?? "—");
      if (name === "actual-litres") {
        element.title = metrics.actualLitresExactLabel ?? "";
      }
    });
  });

  const districts = Array.isArray(metrics.topDistricts) ? metrics.topDistricts : [];
  const sensors = Array.isArray(metrics.topSensors) ? metrics.topSensors : [];
  renderProgrammeRows("[data-programme-top-wells]", districts, "wellCount");
  renderProgrammeRows("[data-programme-top-litres]", sensors, "litresLabel", "", "litresExactLabel");
}

async function refreshProgrammeMetrics() {
  try {
    const response = await fetch("/main/api/programme-metrics.php", {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });
    const payload = await response.json();
    if (!response.ok || !payload.success || !payload.data) {
      throw new Error(payload.message ?? "Programme statistics unavailable");
    }
    renderProgrammeMetrics(payload.data);
  } catch {
    // Keep the server-rendered figures if a background refresh fails.
  }
}

if (programmeMetricElements.length > 0) {
  refreshProgrammeMetrics();
  window.setInterval(refreshProgrammeMetrics, 15_000);
}
