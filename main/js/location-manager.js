/**
 * Add Location manager: lets admins and managers add, rename and delete
 * provinces, regencies / cities, districts and villages. Everything is saved
 * through /main/api/locations.php, so new locations appear for every user.
 */
(() => {
  const dialog = document.querySelector("[data-location-dialog]");
  const openButtons = document.querySelectorAll("[data-open-location-manager]");
  if (!(dialog instanceof HTMLDialogElement) || openButtons.length === 0) {
    return;
  }

  const API_URL = "/main/api/locations.php";
  const LEVEL_ORDER = ["province", "regency", "district", "village"];
  const LEVELS = {
    province: {
      title: "Province",
      plural: "provinces",
      nameLabel: "Province name",
      placeholder: "e.g. Nusa Tenggara Barat",
      newChip: "[New province]",
      submit: "Add Province",
      manage: () => "Manage provinces",
      search: "Search provinces",
    },
    regency: {
      title: "Regency / City",
      plural: "regencies & cities",
      nameLabel: "Regency / City name",
      placeholder: "Enter name",
      newChip: "[New regency / city]",
      submit: "Add Regency / City",
      manage: (parent) => `Manage regencies & cities in ${parent}`,
      search: "Search regencies / cities",
      types: [
        ["regency", "Regency (Kabupaten)"],
        ["city", "City (Kota)"],
      ],
    },
    district: {
      title: "District (Kecamatan)",
      plural: "districts",
      nameLabel: "District name",
      placeholder: "Enter district name",
      newChip: "[New district]",
      submit: "Add District",
      manage: (parent) => `Manage districts in ${parent}`,
      search: "Search districts",
    },
    village: {
      title: "Village / Kelurahan",
      plural: "villages",
      nameLabel: "Village / Kelurahan name",
      placeholder: "Enter village name",
      newChip: "[New village]",
      submit: "Add Village",
      manage: (parent) => `Manage villages in ${parent} District`,
      search: "Search villages",
      types: [
        ["desa", "Village (Desa)"],
        ["kelurahan", "Kelurahan"],
      ],
    },
  };
  const TYPE_LABELS = {
    regency: "Regency",
    city: "City",
    desa: "Village (Desa)",
    kelurahan: "Kelurahan",
  };

  const form = dialog.querySelector("[data-location-form]");
  const formTitle = dialog.querySelector("[data-location-form-title]");
  const nameInput = dialog.querySelector("[data-location-name]");
  const nameLabel = dialog.querySelector("[data-location-name-label]");
  const typeSelect = dialog.querySelector("[data-location-type]");
  const submitButton = dialog.querySelector("[data-location-submit]");
  const clearButton = dialog.querySelector("[data-location-clear]");
  const message = dialog.querySelector("[data-location-message]");
  const hint = dialog.querySelector("[data-location-hint]");
  const preview = dialog.querySelector("[data-location-preview]");
  const manageTitle = dialog.querySelector("[data-location-manage-title]");
  const searchInput = dialog.querySelector("[data-location-search]");
  const thead = dialog.querySelector("[data-location-thead]");
  const tbody = dialog.querySelector("[data-location-tbody]");
  const iconTemplate = dialog.querySelector("[data-location-icons]");
  const tabs = Array.from(dialog.querySelectorAll("[data-location-tab]"));
  const parentSelects = {
    province: dialog.querySelector('[data-location-select="province"]'),
    regency: dialog.querySelector('[data-location-select="regency"]'),
    district: dialog.querySelector('[data-location-select="district"]'),
  };

  const state = {
    level: "province",
    selection: { province: null, regency: null, district: null },
    cache: new Map(),
    items: [],
    editing: null,
    newIds: { province: new Set(), regency: new Set(), district: new Set(), village: new Set() },
    changed: false,
    renderToken: 0,
  };

  /* ---------- data ---------- */

  const cacheKey = (level, parentId) => `${level}:${parentId ?? ""}`;

  async function request(options = {}) {
    const response = await fetch(options.url ?? API_URL, {
      method: options.method ?? "GET",
            headers: options.body
        ? {
            "Content-Type": "application/json",
            "X-Requested-With": "fetch",
            "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
          }
        : { "X-Requested-With": "fetch" },
      body: options.body ? JSON.stringify(options.body) : undefined,
      credentials: "same-origin",
    });
    let data = null;
    try {
      data = await response.json();
    } catch {
      throw new Error("The server sent an unexpected response. Check that PHP is running.");
    }
    if (!response.ok || !data.success) {
      const error = new Error(data?.message ?? "Something went wrong. Try again.");
      error.field = data?.field;
      throw error;
    }
    return data;
  }

  async function loadLevel(level, parentId) {
    const key = cacheKey(level, parentId);
    if (!state.cache.has(key)) {
      const params = new URLSearchParams({ level });
      if (parentId !== null && parentId !== undefined) {
        params.set("parent_id", String(parentId));
      }
      const data = await request({ url: `${API_URL}?${params}` });
      state.cache.set(key, data.items);
    }
    return state.cache.get(key);
  }

  function parentLevelOf(level) {
    const index = LEVEL_ORDER.indexOf(level);
    return index > 0 ? LEVEL_ORDER[index - 1] : null;
  }

  function parentIdFor(level) {
    const parentLevel = parentLevelOf(level);
    return parentLevel ? state.selection[parentLevel] : null;
  }

  /** After a change, drop the edited list and the parent list (its child counts changed). */
  function invalidate(level) {
    state.cache.delete(cacheKey(level, parentIdFor(level)));
    const parentLevel = parentLevelOf(level);
    if (parentLevel) {
      state.cache.delete(cacheKey(parentLevel, parentIdFor(parentLevel)));
    }
  }

  function findCached(level, id) {
    if (id === null) {
      return null;
    }
    const list = state.cache.get(cacheKey(level, parentIdFor(level))) ?? [];
    return list.find((item) => item.id === id) ?? null;
  }

  /* ---------- rendering helpers ---------- */

  function el(tag, attributes = {}, text) {
    const node = document.createElement(tag);
    Object.entries(attributes).forEach(([key, value]) => {
      if (key === "className") {
        node.className = value;
      } else {
        node.setAttribute(key, value);
      }
    });
    if (text !== undefined) {
      node.textContent = text;
    }
    return node;
  }

  function icon(name) {
    const source = iconTemplate?.content.querySelector(`[data-icon="${name}"] svg`);
    return source ? source.cloneNode(true) : document.createTextNode(name === "edit" ? "✎" : "×");
  }

  function showMessage(text, tone = "success") {
    message.textContent = text;
    message.dataset.tone = tone;
    message.hidden = false;
  }

  function clearMessage() {
    message.hidden = true;
    message.textContent = "";
    dialog.querySelectorAll("[aria-invalid]").forEach((field) => field.removeAttribute("aria-invalid"));
  }

  function selectedName(level) {
    return findCached(level, state.selection[level])?.name ?? null;
  }

  /* ---------- parent selects ---------- */

  function fillSelect(select, items, placeholder, selectedId, enabled) {
    select.textContent = "";
    select.append(el("option", { value: "" }, placeholder));
    items.forEach((item) => {
      const label = item.type === "city" ? `${item.name} (City)` : item.name;
      const option = el("option", { value: String(item.id) }, label);
      if (item.id === selectedId) {
        option.selected = true;
      }
      select.append(option);
    });
    select.disabled = !enabled;
  }

  async function renderParentSelects(token) {
    const needed = LEVEL_ORDER.slice(0, LEVEL_ORDER.indexOf(state.level));
    let lockedLabel = null;

    for (const level of needed) {
      const select = parentSelects[level];
      const parentLevel = parentLevelOf(level);
      const parentId = parentLevel ? state.selection[parentLevel] : null;

      if (parentLevel && parentId === null) {
        fillSelect(select, [], `Choose a ${LEVELS[parentLevel].title.toLowerCase()} first`, null, false);
        state.selection[level] = null;
        lockedLabel ??= { locked: LEVELS[level].title, unlockedBy: LEVELS[parentLevel].title };
        continue;
      }

      const items = await loadLevel(level, parentId);
      if (token !== state.renderToken) {
        return false;
      }
      if (!items.some((item) => item.id === state.selection[level])) {
        if (level === "province") {
          const bali = items.find((item) => item.name.trim().toUpperCase() === "BALI");
          state.selection[level] = bali?.id ?? null;
        } else {
          state.selection[level] = null;
        }
      }
      const placeholder = items.length ? `Select ${LEVELS[level].title.toLowerCase()}` : `No ${LEVELS[level].plural} yet`;
      fillSelect(select, items, placeholder, state.selection[level], state.editing === null && items.length > 0);
    }

    if (lockedLabel) {
      hint.textContent = `${lockedLabel.locked} list unlocks after a ${lockedLabel.unlockedBy} is chosen.`;
      hint.hidden = false;
    } else {
      hint.hidden = true;
    }
    return true;
  }

  /* ---------- preview chips ---------- */

  function renderPreview() {
    preview.textContent = "";
    const parents = LEVEL_ORDER.slice(0, LEVEL_ORDER.indexOf(state.level));
    parents.forEach((level) => {
      const name = selectedName(level);
      preview.append(el("span", { className: "location-chip" }, name ?? `[${LEVELS[level].title}]`));
      preview.append(el("span", { className: "location-chip__sep", "aria-hidden": "true" }, "›"));
    });
    const typed = nameInput.value.trim();
    preview.append(
      el("span", { className: "location-chip location-chip--current" }, typed || LEVELS[state.level].newChip),
    );
  }

  /* ---------- table ---------- */

  function columnsFor(level) {
    const provinceName = selectedName("province") ?? "";
    const regencyName = selectedName("regency") ?? "";
    const districtName = selectedName("district") ?? "";
    switch (level) {
      case "province":
        return [
          ["Province", (item) => item.name],
          ["Regencies / Cities", (item) => String(item.childCount)],
        ];
      case "regency":
        return [
          ["Regency / City", (item) => item.name],
          ["Type", (item) => TYPE_LABELS[item.type] ?? "—"],
          ["Province", () => provinceName],
        ];
      case "district":
        return [
          ["District", (item) => item.name],
          ["Regency / City", () => regencyName],
          ["Province", () => provinceName],
        ];
      default:
        return [
          ["Village / Kelurahan", (item) => item.name],
          ["Type", (item) => TYPE_LABELS[item.type] ?? "—"],
          ["District", () => districtName],
          ["Regency / City", () => regencyName],
        ];
    }
  }

  function renderTableMessage(text, columnCount) {
    tbody.textContent = "";
    const row = el("tr", { className: "location-table__empty" });
    const cell = el("td", { colspan: String(columnCount) }, text);
    row.append(cell);
    tbody.append(row);
  }

  function renderTable() {
    const columns = columnsFor(state.level);
    const columnCount = columns.length + 1;

    thead.textContent = "";
    const headRow = el("tr");
    columns.forEach(([label]) => headRow.append(el("th", { scope: "col" }, label)));
    headRow.append(el("th", { scope: "col", className: "location-table__actions" }, "Edit / Delete"));
    thead.append(headRow);

    const parentLevel = parentLevelOf(state.level);
    if (parentLevel && parentIdFor(state.level) === null) {
      renderTableMessage(
        `Choose a ${LEVELS[parentLevel].title.toLowerCase()} above to see its ${LEVELS[state.level].plural}.`,
        columnCount,
      );
      return;
    }

    const query = searchInput.value.trim().toLowerCase();
    const newIds = state.newIds[state.level];
    const items = state.items
      .filter((item) => !query || item.name.toLowerCase().includes(query))
      .sort((a, b) => Number(newIds.has(b.id)) - Number(newIds.has(a.id)));

    if (state.items.length === 0) {
      const where = parentLevel ? ` in ${selectedName(parentLevel)}` : "";
      renderTableMessage(`No ${LEVELS[state.level].plural}${where} yet. Add the first one above.`, columnCount);
      return;
    }
    if (items.length === 0) {
      renderTableMessage(`No ${LEVELS[state.level].plural} match “${searchInput.value.trim()}”.`, columnCount);
      return;
    }

    tbody.textContent = "";
    items.forEach((item) => {
      const row = el("tr");
      if (state.editing?.id === item.id) {
        row.classList.add("is-editing");
      }
      columns.forEach(([, value], index) => {
        const cell = el("td", {}, value(item));
        if (index === 0 && newIds.has(item.id)) {
          cell.append(el("span", { className: "location-badge" }, "New"));
        }
        row.append(cell);
      });

      const actions = el("td", { className: "location-table__actions" });
      const editButton = el("button", {
        type: "button",
        className: "location-icon-button",
        "aria-label": `Edit ${item.name}`,
        title: "Edit",
      });
      editButton.append(icon("edit"));
      editButton.addEventListener("click", () => startEdit(item));

      const deleteButton = el("button", {
        type: "button",
        className: "location-icon-button location-icon-button--danger",
        "aria-label": `Delete ${item.name}`,
        title: "Delete",
      });
      deleteButton.append(icon("delete"));
      deleteButton.addEventListener("click", () => deleteItem(item));

      actions.append(editButton, deleteButton);
      row.append(actions);
      tbody.append(row);
    });
  }

  /* ---------- main refresh ---------- */

  async function refresh() {
    const token = ++state.renderToken;
    const columnCount = columnsFor(state.level).length + 1;
    try {
      const ready = await renderParentSelects(token);
      if (!ready) {
        return;
      }
      renderPreview();

      const parentLevel = parentLevelOf(state.level);
      if (parentLevel && parentIdFor(state.level) === null) {
        state.items = [];
        renderTable();
        manageTitle.textContent = `Manage ${LEVELS[state.level].plural}`;
        return;
      }

      if (!state.cache.has(cacheKey(state.level, parentIdFor(state.level)))) {
        renderTableMessage("Loading…", columnCount);
      }
      const items = await loadLevel(state.level, parentIdFor(state.level));
      if (token !== state.renderToken) {
        return;
      }
      state.items = items;
      manageTitle.textContent = LEVELS[state.level].manage(parentLevel ? selectedName(parentLevel) : "");
      renderTable();
    } catch (error) {
      if (token === state.renderToken) {
        renderTableMessage(error.message, columnCount);
      }
    }
  }

  /* ---------- level / mode ---------- */

  function setLevel(level) {
    state.level = level;
    state.editing = null;
    const config = LEVELS[level];

    tabs.forEach((tab) => {
      const active = tab.dataset.locationTab === level;
      tab.setAttribute("aria-selected", String(active));
      tab.tabIndex = active ? 0 : -1;
    });

    form.dataset.level = level;
    nameLabel.firstChild.textContent = `${config.nameLabel} `;
    nameInput.placeholder = config.placeholder;
    nameInput.value = "";
    searchInput.value = "";
    searchInput.placeholder = config.search;

    typeSelect.textContent = "";
    (config.types ?? []).forEach(([value, label]) => typeSelect.append(el("option", { value }, label)));

    applyMode();
    clearMessage();
    refresh();
  }

  function applyMode() {
    const config = LEVELS[state.level];
    const editing = state.editing !== null;
    formTitle.textContent = editing ? `Edit ${config.title}` : `Add ${config.title}`;
    submitButton.textContent = editing ? "Save changes" : config.submit;
    clearButton.textContent = editing ? "Cancel" : "Clear";
    form.classList.toggle("is-editing", editing);
    Object.values(parentSelects).forEach((select) => {
      if (editing) {
        select.disabled = true;
      }
    });
  }

  function startEdit(item) {
    state.editing = item;
    nameInput.value = item.name;
    if (item.type) {
      typeSelect.value = item.type;
    }
    applyMode();
    clearMessage();
    showMessage(`Editing ${item.name}. Change the name${LEVELS[state.level].types ? " or type" : ""}, then save.`, "info");
    renderPreview();
    renderTable();
    nameInput.focus();
    nameInput.select();
  }

  function resetForm() {
    state.editing = null;
    nameInput.value = "";
    if (typeSelect.options.length) {
      typeSelect.selectedIndex = 0;
    }
    applyMode();
    clearMessage();
    refresh();
  }

  /* ---------- actions ---------- */

  async function submitForm(event) {
    event.preventDefault();
    clearMessage();

    const level = state.level;
    const name = nameInput.value.trim().replace(/\s+/g, " ");
    const parentLevel = parentLevelOf(level);

    if (!state.editing) {
      const missing = LEVEL_ORDER.slice(0, LEVEL_ORDER.indexOf(level)).find((lvl) => state.selection[lvl] === null);
      if (missing) {
        parentSelects[missing].setAttribute("aria-invalid", "true");
        showMessage(`Choose a ${LEVELS[missing].title.toLowerCase()} first.`, "error");
        parentSelects[missing].focus();
        return;
      }
    }
    if (!name) {
      nameInput.setAttribute("aria-invalid", "true");
      showMessage(`Enter a ${LEVELS[level].nameLabel.toLowerCase()}.`, "error");
      nameInput.focus();
      return;
    }

    const body = state.editing
      ? { action: "update", level, id: state.editing.id, name }
      : { action: "create", level, parentId: parentLevel ? state.selection[parentLevel] : null, name };
    if (LEVELS[level].types) {
      body.type = typeSelect.value;
    }

    submitButton.disabled = true;
    const idleLabel = submitButton.textContent;
    submitButton.textContent = "Saving…";
    try {
      const result = await request({ method: "POST", body });
      state.changed = true;
      invalidate(level);
      if (!state.editing) {
        state.newIds[level].add(result.id);
        // Pre-select the new entry so the next tab is ready to use it.
        if (level !== "village") {
          state.selection[level] = result.id;
          LEVEL_ORDER.slice(LEVEL_ORDER.indexOf(level) + 1).forEach((lower) => {
            if (lower !== "village") {
              state.selection[lower] = null;
            }
          });
        }
      }
      state.editing = null;
      nameInput.value = "";
      applyMode();
      await refresh();
      showMessage(result.message, "success");
      nameInput.focus();
    } catch (error) {
      if (error.field === "name") {
        nameInput.setAttribute("aria-invalid", "true");
      }
      showMessage(error.message, "error");
    } finally {
      submitButton.disabled = false;
      if (submitButton.textContent === "Saving…") {
        submitButton.textContent = idleLabel;
      }
    }
  }

  async function deleteItem(item) {
    clearMessage();
    const level = state.level;
    if (item.childCount > 0) {
      const childNoun = { province: "regencies / cities", regency: "districts", district: "villages", village: "registered wells" }[level];
      showMessage(`${item.name} still has ${item.childCount} ${childNoun}. Remove those first, then delete ${item.name}.`, "error");
      return;
    }
    if (!window.confirm(`Delete ${item.name}? It will be removed for everyone.`)) {
      return;
    }
    try {
      const result = await request({ method: "POST", body: { action: "delete", level, id: item.id } });
      state.changed = true;
      state.newIds[level].delete(item.id);
      if (state.editing?.id === item.id) {
        state.editing = null;
        nameInput.value = "";
        applyMode();
      }
      if (state.selection[level] === item.id) {
        state.selection[level] = null;
      }
      invalidate(level);
      await refresh();
      showMessage(result.message, "success");
    } catch (error) {
      showMessage(error.message, "error");
    }
  }

  /* ---------- events ---------- */

  Object.entries(parentSelects).forEach(([level, select]) => {
    select.addEventListener("change", () => {
      state.selection[level] = select.value ? Number(select.value) : null;
      LEVEL_ORDER.slice(LEVEL_ORDER.indexOf(level) + 1).forEach((lower) => {
        if (lower in state.selection) {
          state.selection[lower] = null;
        }
      });
      searchInput.value = "";
      clearMessage();
      refresh();
    });
  });

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => setLevel(tab.dataset.locationTab));
    tab.addEventListener("keydown", (event) => {
      if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") {
        return;
      }
      const next = tabs[(index + (event.key === "ArrowRight" ? 1 : tabs.length - 1)) % tabs.length];
      next.focus();
      setLevel(next.dataset.locationTab);
    });
  });

  nameInput.addEventListener("input", () => {
    nameInput.removeAttribute("aria-invalid");
    renderPreview();
  });
  searchInput.addEventListener("input", renderTable);
  form.addEventListener("submit", submitForm);
  clearButton.addEventListener("click", resetForm);
  dialog.querySelector("[data-location-close]")?.addEventListener("click", () => dialog.close());

  // Close when clicking the backdrop.
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });

  dialog.addEventListener("close", () => {
    if (state.changed) {
      state.changed = false;
      document.dispatchEvent(new CustomEvent("idep:locations-changed"));
    }
  });

  openButtons.forEach((button) => {
    button.addEventListener("click", () => {
      state.cache.clear(); // pick up anything other users added since last time
      dialog.showModal();
      setLevel(state.level);
      nameInput.focus();
    });
  });
})();
