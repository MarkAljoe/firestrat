<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'hazardmap';
$barangays = $db->query('SELECT id, name FROM barangays ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hazard Map — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <div class="page-title">Hazard map</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-head">
        <h3>Filter</h3>
      </div>
      <div style="display:flex;gap:12px;padding:14px 18px;flex-wrap:wrap;">
        <select id="filterBarangay">
          <option value="">All barangays</option>
          <?php foreach ($barangays as $b): ?>
            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="filterRisk">
          <option value="">All risk levels</option>
          <option value="high">High risk</option>
          <option value="moderate">Moderate</option>
          <option value="low">Low risk</option>
        </select>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Virac hazard map</h3>
        <div class="legend">
          <span><i style="background:var(--ember)"></i>High risk</span>
          <span><i style="background:var(--amber)"></i>Moderate</span>
          <span><i style="background:var(--green)"></i>Low risk</span>
          <span><i style="background:var(--blue);border-radius:2px"></i>Hydrants</span>
        </div>
      </div>
      <div id="map" style="height:600px;"></div>
    </div>
  </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<script>
const BOUNDS = L.latLngBounds(
  [13.35, 124.00],
  [14.15, 124.50]
);

const map = L.map('map', {
  maxBounds: BOUNDS,
  maxBoundsViscosity: 1.0,
  minZoom: 11,
}).fitBounds(BOUNDS);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  bounds: BOUNDS,
}).addTo(map);
</script>

</body>
</html>