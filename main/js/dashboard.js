const searchInput = document.querySelector("#well-search");
const searchableRows = document.querySelectorAll("[data-search]");
const tableRows = document.querySelectorAll("tbody [data-search]");
const noResults = document.querySelector("#no-results");

searchInput?.addEventListener("input", () => {
  const query = searchInput.value.trim().toLowerCase();

  searchableRows.forEach((row) => {
    const text = row.dataset.search ?? "";
    row.hidden = query !== "" && !text.includes(query);
  });

  const visibleTableRows = Array.from(tableRows).filter((row) => !row.hidden).length;
  if (noResults) {
    noResults.hidden = visibleTableRows !== 0;
  }
});

const chart = document.querySelector("#water-level-chart");

function drawWaterLevelChart() {
  if (!(chart instanceof HTMLCanvasElement)) {
    return;
  }

  const bounds = chart.getBoundingClientRect();
  const scale = window.devicePixelRatio || 1;
  chart.width = Math.max(1, Math.round(bounds.width * scale));
  chart.height = Math.max(1, Math.round(bounds.height * scale));

  const context = chart.getContext("2d");
  if (!context) {
    return;
  }

  context.scale(scale, scale);

  const width = bounds.width;
  const height = bounds.height;
  const padding = { top: 18, right: 18, bottom: 30, left: 22 };
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;
  const values = [2.08, 2.31, 1.74, 1.56, 2.21, 1.59, 2.26, 2.78, 2.05, 1.72, 2.11, 1.81, 2.39, 1.77];
  const labels = ["May 7", "May 8", "May 9", "May 10", "May 11", "May 12", "May 13"];
  const minimum = 1.3;
  const maximum = 3.0;

  context.clearRect(0, 0, width, height);
  context.strokeStyle = "#ded8c7";
  context.lineWidth = 1;

  for (let line = 0; line < 4; line += 1) {
    const y = padding.top + (plotHeight / 3) * line;
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
  points.forEach((point, index) => {
    if (index === 0) {
      context.moveTo(point.x, point.y);
    } else {
      context.lineTo(point.x, point.y);
    }
  });
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

  context.fillStyle = "#141a21";
  context.font = "12px Arial, sans-serif";
  context.textAlign = "center";
  labels.forEach((label, index) => {
    const x = padding.left + (plotWidth / (labels.length - 1)) * index;
    context.fillText(label, x, height - 7);
  });
}

drawWaterLevelChart();
window.addEventListener("resize", drawWaterLevelChart);
