<?php
declare(strict_types=1);

// Shares $wells from the including page.
// Real coordinates today come from the regency town centre a well's city
// belongs to (see well-data.php). Once individual site-registration GPS
// pins are stored per well, swap those in here without touching the JS.
$mapMode = $mapMode ?? 'large';
$mapClass = $mapMode === 'compact' ? ' leaflet-stage--compact' : '';
?>
<div class="leaflet-stage<?= $mapClass ?>">
  <div id="leaflet-map" class="leaflet-canvas" role="img" aria-label="OpenStreetMap of Bali showing monitoring well locations"></div>
</div>

<script>
  window.WELLS_GEO_DATA = <?= json_encode(array_map(static function (array $well): array {
      return [
          'id' => $well['id'],
          'name' => $well['name'],
          'city' => $well['city'],
          'lat' => $well['lat'],
          'lng' => $well['lng'],
          'status' => $well['status'],
          'statusLabel' => $well['statusLabel'],
          'volume' => $well['volume'],
          'inflow' => $well['inflow'],
          'lastTransmission' => $well['lastTransmission'],
          'photo' => $well['photo'],
      ];
  }, $wells), JSON_UNESCAPED_SLASHES) ?>;
</script>
