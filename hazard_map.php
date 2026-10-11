<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$mapBarangays = $db->query("SELECT id, name, lat, lng, risk_level FROM barangays WHERE lat IS NOT NULL")->fetchAll();
$mapHydrants  = $db->query("SELECT code, lat, lng, status FROM hydrants WHERE lat IS NOT NULL")->fetchAll();
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
      <button class="iconbtn" id="mobileMenuBtn" title="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
      </button>
      <div class="page-title" style="flex:1;text-align:center;">Hazard map</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Virac hazard map</h3>
      </div>
      <div id="map" style="height:600px;"></div>
    </div>

  </main>
</div><!-- /#app -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>window.FIRESTRAT_MODE = 'hazardmap';</script>
<script src="assets/js/app.js"></script>
<script>
const BOUNDS = L.latLngBounds([13.35, 124.00], [14.15, 124.50]);

const hazardMap = L.map('map', {
  maxBounds: BOUNDS,
  maxBoundsViscosity: 1.0,
  minZoom: 11,
}).setView([13.5824, 124.2350], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  bounds: BOUNDS,
}).addTo(hazardMap);

const mapBarangays = <?= json_encode($mapBarangays) ?>;
const mapHydrants  = <?= json_encode($mapHydrants) ?>;

const _riskColor = r => r === 'high' ? '#D6472E' : r === 'moderate' ? '#E8963E' : '#3FA34D';

function _barangayIcon(riskLevel) {
  const color = _riskColor(riskLevel || 'low');
  return L.divIcon({
    className: '',
    html: `
      <div style="position:relative;width:32px;height:38px;filter:drop-shadow(0 2px 5px rgba(0,0,0,0.6));">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 38" width="32" height="38">
          <path d="M16 1 C8 1 2 7 2 15 C2 24 16 37 16 37 C16 37 30 24 30 15 C30 7 24 1 16 1Z"
                fill="${color}" stroke="white" stroke-width="1.5"/>
          <path d="M16 5 C16 5 21 10 21 14 C21 15.5 20.5 16.5 20 17
                   C21 15 20 12 18.5 11 C18.5 13 17.5 14.5 16 15.5
                   C14.5 14.5 13.5 13 13.5 11 C12 12 11 15 12 17
                   C11.5 16.5 11 15.5 11 14 C11 10 16 5 16 5Z"
                fill="white"/>
          <path d="M16 10 C16 10 19.5 13.5 19.5 16.5
                   C19.5 18.9 17.9 20.5 16 20.5
                   C14.1 20.5 12.5 18.9 12.5 16.5
                   C12.5 13.5 16 10 16 10Z"
                fill="white"/>
          <path d="M16 13 C16 13 18 15.5 18 17
                   C18 18.1 17.1 19 16 19
                   C14.9 19 14 18.1 14 17
                   C14 15.5 16 13 16 13Z"
                fill="${color}"/>
        </svg>
      </div>`,
    iconSize: [32, 38],
    iconAnchor: [16, 37],
    popupAnchor: [0, -38]
  });
}

function _hydrantIcon(status) {
  const isActive = status === 'active';
  const bodyColor = isActive ? '#2980b9' : '#7f8c8d';
  const darkColor = isActive ? '#1a6fcc' : '#606060';
  const midColor  = isActive ? '#2471a3' : '#6b6b6b';
  return L.divIcon({
    className: '',
    html: `
      <div style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 36" width="28" height="36">
          <rect x="8"  y="10" width="12" height="14" rx="3"   fill="${bodyColor}" stroke="white" stroke-width="1.2"/>
          <rect x="10" y="6"  width="8"  height="4"  rx="1.5" fill="${darkColor}" stroke="white" stroke-width="1"/>
          <rect x="12" y="3"  width="4"  height="3"  rx="1"   fill="${darkColor}" stroke="white" stroke-width="0.8"/>
          <rect x="3"  y="14" width="5"  height="3"  rx="1.5" fill="${midColor}"  stroke="white" stroke-width="1"/>
          <rect x="20" y="14" width="5"  height="3"  rx="1.5" fill="${midColor}"  stroke="white" stroke-width="1"/>
          <rect x="6"  y="24" width="16" height="3"  rx="1.5" fill="${darkColor}" stroke="white" stroke-width="1"/>
          <circle cx="14" cy="17" r="2" fill="white" opacity="0.3"/>
        </svg>
      </div>`,
    iconSize: [28, 36],
    iconAnchor: [14, 36],
    popupAnchor: [0, -36]
  });
}

function _fireStationIcon() {
  return L.divIcon({
    className: '',
    html: `
      <div style="filter:drop-shadow(0 2px 5px rgba(0,0,0,0.6));">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38 38" width="38" height="38">
          <circle cx="19" cy="19" r="18" fill="white" stroke="#cc2200" stroke-width="2"/>
          <rect x="7"  y="17" width="24" height="13" rx="1" fill="#cc2200"/>
          <rect x="5"  y="14" width="28" height="4"  rx="1" fill="#cc2200"/>
          <path d="M11 14 C11 9 27 9 27 14Z" fill="#cc2200"/>
          <circle cx="19" cy="11" r="4.5" fill="white" stroke="#cc2200" stroke-width="1"/>
          <path d="M19 8 C19 8 21.2 10.2 21.2 11.8 C21.2 13 20.2 14 19 14
                   C17.8 14 16.8 13 16.8 11.8 C16.8 10.2 19 8 19 8Z" fill="#cc2200"/>
          <rect x="8"  y="21" width="9" height="8" rx="1" fill="white" opacity="0.9"/>
          <rect x="21" y="21" width="9" height="8" rx="1" fill="white" opacity="0.9"/>
          <rect x="9.5"  y="23" width="6" height="4" rx="0.5" fill="#cc2200" opacity="0.6"/>
          <rect x="22.5" y="23" width="6" height="4" rx="0.5" fill="#cc2200" opacity="0.6"/>
        </svg>
      </div>`,
    iconSize: [38, 38],
    iconAnchor: [19, 19],
    popupAnchor: [0, -22]
  });
}

// Barangay markers with More Info popup
mapBarangays.forEach(b => {
  const rc     = _riskColor(b.risk_level);
  const rLabel = b.risk_level === 'high' ? 'High Risk' : b.risk_level === 'moderate' ? 'Moderate Risk' : 'Low Risk';
  const marker = L.marker([b.lat, b.lng], { icon: _barangayIcon(b.risk_level) })
    .addTo(hazardMap)
    .bindPopup(`
      <div style="min-width:160px;">
        <div style="font-family:'Oswald',sans-serif;font-size:15px;font-weight:600;color:#fff;margin-bottom:6px;">${b.name}</div>
        <span style="display:inline-block;padding:3px 10px;border-radius:5px;background:${rc};color:#fff;font-size:11px;font-weight:700;margin-bottom:10px;">${rLabel}</span>
        <div>
          <button class="brgy-more-info-btn" data-id="${b.id}"
            style="width:100%;padding:7px 0;background:#e03e1a;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">
            More Info
          </button>
        </div>
      </div>`, { className: 'firestrat-popup' }
    );
  marker.on('popupopen', function(e) {
    document.querySelectorAll('.brgy-more-info-btn').forEach(btn => {
      btn.onclick = function() {
        window.openBarangayInfo(parseInt(this.dataset.id));
        e.target.closePopup();
      };
    });
  });
});
// Hydrant markers
mapHydrants.forEach(h => {
  L.marker([h.lat, h.lng], { icon: _hydrantIcon(h.status) })
    .addTo(hazardMap)
    .bindPopup(`
      <div class="popup-title">Hydrant ${h.code}</div>
      <div class="popup-row">Status: ${h.status}</div>`);
});

// Fire Station marker
L.marker([13.584781, 124.236493], { icon: _fireStationIcon() })
  .addTo(hazardMap)
  .bindPopup(`
    <div class="popup-title">🚒 Virac Fire Station</div>
    <div class="popup-row">BFP Virac, Catanduanes</div>`);

// Bottom-right legend
const legend = L.control({ position: 'bottomright' });
legend.onAdd = function () {
  const div = L.DomUtil.create('div');
  div.innerHTML = `
    <div style="
      background:rgba(20,20,30,0.92);
      border:1px solid rgba(255,255,255,0.12);
      border-radius:10px;
      padding:10px 14px;
      font-family:Inter,sans-serif;
      font-size:12px;
      color:#f0f0f0;
      min-width:130px;
      box-shadow:0 2px 8px rgba(0,0,0,0.5);
      margin-bottom:6px;
    ">
      <div style="font-weight:600;margin-bottom:8px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.7;">LEGEND</div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#D6472E;flex-shrink:0;"></span>High risk</div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#E8963E;flex-shrink:0;"></span>Moderate</div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#3FA34D;flex-shrink:0;"></span>Low risk</div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#2980b9;flex-shrink:0;"></span>Hydrant (active)</div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#7f8c8d;flex-shrink:0;"></span>Hydrant (inactive)</div>
      <div style="display:flex;align-items:center;gap:8px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:white;border:2px solid #cc2200;box-sizing:border-box;flex-shrink:0;"></span>Fire Station</div>
    </div>`;
  return div;
};
legend.addTo(hazardMap);
</script>

<!-- ── Barangay Info Modal ── -->
<div id="brgy-info-modal" style="
  display:none;position:fixed;inset:0;z-index:10000;
  background:rgba(0,0,0,0.65);
  align-items:center;justify-content:center;
  padding:16px;
">
  <div style="
    background:var(--panel-2,#1a1a1a);
    border:1px solid var(--line,#2a2a2a);
    border-radius:14px;
    width:100%;max-width:460px;
    max-height:90vh;
    display:flex;flex-direction:column;
    box-shadow:0 8px 32px rgba(0,0,0,0.6);
  ">
    <div style="
      display:flex;align-items:center;justify-content:space-between;
      padding:16px 20px 12px;
      border-bottom:1px solid var(--line,#2a2a2a);
      flex-shrink:0;
    ">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:32px;height:32px;border-radius:8px;background:var(--ember,#e03e1a);display:flex;align-items:center;justify-content:center;">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <strong style="font-size:15px;color:var(--ink,#fff);">Barangay Info</strong>
      </div>
      <button onclick="document.getElementById('brgy-info-modal').style.display='none'"
        style="background:none;border:none;color:var(--ink-dim,#888);font-size:22px;cursor:pointer;line-height:1;padding:4px 8px;border-radius:6px;">×</button>
    </div>
    <div id="brgy-info-body" style="overflow-y:auto;padding:20px;"></div>
  </div>
</div>

</body>
</html>