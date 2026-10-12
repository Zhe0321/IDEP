<?php
declare(strict_types=1);

/**
 * "Add Location" dialog for admins and managers.
 * Included by pages/registration-page.php; behaviour lives in js/location-manager.js.
 */

$locationManagerJsVersion = (string) filemtime(__DIR__ . '/../js/location-manager.js');
$pencilIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>';
$trashIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>';
?>
<dialog class="location-dialog" data-location-dialog aria-labelledby="location-dialog-title">
  <div class="location-dialog__inner">
    <header class="location-dialog__header">
      <div>
        <h2 id="location-dialog-title">Add Location</h2>
        <p>Province → Regency / City → District (Kecamatan) → Village / Kelurahan. Select each parent in order.</p>
      </div>
      <button class="location-dialog__close" type="button" data-location-close aria-label="Close">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>

    <nav class="location-tabs" role="tablist" aria-label="Location level">
      <button type="button" role="tab" data-location-tab="province" aria-selected="true">1 · Province</button>
      <button type="button" role="tab" data-location-tab="regency" aria-selected="false">2 · Regency / City</button>
      <button type="button" role="tab" data-location-tab="district" aria-selected="false">3 · District (Kecamatan)</button>
      <button type="button" role="tab" data-location-tab="village" aria-selected="false">4 · Village / Kelurahan</button>
    </nav>

    <div class="location-dialog__body" role="tabpanel">
      <form class="location-form" data-location-form data-level="province" novalidate>
        <h3 data-location-form-title>Add Province</h3>

        <div class="location-form__grid">
          <label class="location-field" data-field="province">
            <span>Province <b aria-hidden="true">*</b></span>
            <select data-location-select="province"></select>
          </label>
          <label class="location-field" data-field="regency">
            <span>Regency / City <b aria-hidden="true">*</b></span>
            <select data-location-select="regency"></select>
          </label>
          <label class="location-field" data-field="district">
            <span>District (Kecamatan) <b aria-hidden="true">*</b></span>
            <select data-location-select="district"></select>
          </label>
          <label class="location-field" data-field="type">
            <span>Type <b aria-hidden="true">*</b></span>
            <select name="type" data-location-type></select>
          </label>
          <label class="location-field" data-field="name">
            <span data-location-name-label>Province name <b aria-hidden="true">*</b></span>
            <input name="name" autocomplete="off" maxlength="100" data-location-name>
          </label>
        </div>

        <p class="location-form__hint" data-location-hint hidden></p>

        <div class="location-form__footer">
          <p class="location-preview" aria-live="polite">
            <span>Will be saved as</span>
            <span class="location-preview__chips" data-location-preview></span>
          </p>
          <div class="location-form__actions">
            <button class="location-button location-button--quiet" type="button" data-location-clear>Clear</button>
            <button class="location-button location-button--primary" type="submit" data-location-submit>Add Province</button>
          </div>
        </div>
        <p class="location-message" data-location-message role="status" hidden></p>
      </form>

      <section class="location-manage" aria-labelledby="location-manage-title">
        <div class="location-manage__header">
          <h3 id="location-manage-title" data-location-manage-title>Manage provinces</h3>
          <label class="location-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
            <span class="visually-hidden">Search</span>
            <input type="search" autocomplete="off" data-location-search placeholder="Search provinces">
          </label>
        </div>

        <div class="location-table-wrap">
          <table class="location-table">
            <thead data-location-thead></thead>
            <tbody data-location-tbody></tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <template data-location-icons>
    <span data-icon="edit"><?= $pencilIcon ?></span>
    <span data-icon="delete"><?= $trashIcon ?></span>
  </template>
</dialog>
<script src="/main/js/location-manager.js?v=<?= $locationManagerJsVersion ?>" defer></script>
