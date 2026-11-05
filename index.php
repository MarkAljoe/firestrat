<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$monthlyData = $db->query("
  SELECT DATE_FORMAT(created_at, '%b') AS month,
         DATE_FORMAT(created_at, '%Y-%m') AS ym,
         COUNT(*) AS total
  FROM incidents
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
  GROUP BY ym, month
  ORDER BY ym ASC
")->fetchAll();

$barangayData = $db->query("
  SELECT b.name, COUNT(i.id) AS total
  FROM incidents i
  JOIN barangays b ON i.barangay_id = b.id
  GROUP BY b.id, b.name
  ORDER BY total DESC
  LIMIT 6
")->fetchAll();

$hydrantStatus = $db->query("
  SELECT status, COUNT(*) AS total
  FROM hydrants
  GROUP BY status
")->fetchAll();

$riskLevels = $db->query("
  SELECT risk_level, COUNT(*) AS total
  FROM barangays
  GROUP BY risk_level
")->fetchAll();

$monthLabels    = json_encode(array_column($monthlyData, 'month'));
$monthCounts    = json_encode(array_map('intval', array_column($monthlyData, 'total')));
$brgyLabels     = json_encode(array_column($barangayData, 'name'));
$brgyCounts     = json_encode(array_map('intval', array_column($barangayData, 'total')));
$hydrantLabels  = json_encode(array_column($hydrantStatus, 'status'));
$hydrantCounts  = json_encode(array_map('intval', array_column($hydrantStatus, 'total')));
$riskLabels     = json_encode(array_column($riskLevels, 'risk_level'));
$riskCounts     = json_encode(array_map('intval', array_column($riskLevels, 'total')));
$totalZones     = array_sum(array_column($riskLevels, 'total'));
$totalIncidents = (int)$db->query('SELECT COUNT(*) c FROM incidents')->fetch()['c'];
$highRiskZones  = (int)$db->query("SELECT COUNT(*) c FROM barangays WHERE risk_level='high'")->fetch()['c'];
$activeHydrants = (int)$db->query("SELECT COUNT(*) c FROM hydrants WHERE status='active'")->fetch()['c'];
$totalHydrants  = (int)$db->query('SELECT COUNT(*) c FROM hydrants')->fetch()['c'];
$hydrantPct     = $totalHydrants ? round($activeHydrants / $totalHydrants * 100) : 0;
$pendingReports = (int)$db->query("SELECT COUNT(*) c FROM incidents WHERE approval_status='pending'")->fetch()['c'];
$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  #mainGrid { align-items: start !important; }
</style>
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <button class="iconbtn" id="mobileMenuBtn" title="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
      </button>
      <div class="page-title" style="flex:1;text-align:center;">Dashboard overview</div>
      <div class="topbar-right">
        <div class="search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input id="searchInput" placeholder="Search incidents…">
        </div>
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
        <button class="iconbtn" id="splitBtn" title="Toggle split analytics view">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="8" height="18" rx="1.5"/>
            <rect x="13" y="3" width="8" height="18" rx="1.5"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="cards">
      <a href="incident_log.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="lbl">Total incidents</div>
        <div class="val"><?= $totalIncidents ?></div>
        <div class="delta up">Live count</div>
      </a>
      <div class="card">
        <div class="lbl">High-risk zones</div>
        <div class="val"><?= $highRiskZones ?></div>
        <div class="delta warn">barangays</div>
      </div>
      <a href="hydrants.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="lbl">Active hydrants</div>
        <div class="val"><?= $activeHydrants ?></div>
        <div class="delta ok"><?= $hydrantPct ?>% operational</div>
      </a>
      <a href="reports.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="lbl">Pending reports</div>
        <div class="val"><?= $pendingReports ?></div>
        <div class="delta warn">Awaiting approval</div>
      </a>
    </div>

    <div class="grid" id="mainGrid">
      <div class="panel map-panel" id="map" style="height:auto;">
        <div class="panel-head">
          <h3>Virac hazard map</h3>
          <div class="legend">
            <span><i style="background:var(--ember)"></i>High risk</span>
            <span><i style="background:var(--amber)"></i>Moderate</span>
            <span><i style="background:var(--green)"></i>Low risk</span>
            <span><i style="background:var(--blue);border-radius:2px"></i>Hydrants</span>
          </div>
        </div>
        <div id="map-container"></div>
      </div>

      <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="panel">
          <div class="panel-head"><h3>Recent incidents</h3></div>
          <div id="incidentList"></div>
        </div>
        <!-- <div class="panel" id="hydrants">
          <div class="panel-head"><h3>Nearby hydrants</h3></div>
          <div id="hydrantList"></div>
        </div> -->
      </div>
    </div>

    <!-- <div class="panel" id="analyticsPanel" style="display:none;margin-top:16px;">
      <div class="panel-head"><h3>Incidents by barangay</h3></div>
      <div class="bars" id="barsWrap"></div>
    </div> -->

<div style="margin-top:16px;display:grid;grid-template-columns:2fr 1fr;gap:16px;">

  <!-- Monthly incidents bar chart -->
  <div class="panel">
    <div class="panel-head">
      <h3>Fire incidents per month</h3>
      <span style="font-size:11px;color:var(--ink-dim);">Last 12 months</span>
    </div>
    <div style="padding:20px 24px;">
      <div style="position:relative;height:200px;">
        <canvas id="monthlyChart" role="img" aria-label="Monthly fire incidents bar chart">No data.</canvas>
      </div>
    </div>
  </div>

  <!-- Hazard zones donut -->
  <div class="panel">
    <div class="panel-head">
      <h3>Hazard zones by risk level</h3>
    </div>
    <div style="padding:20px 24px;display:flex;flex-direction:column;align-items:center;gap:12px;">
      <div style="position:relative;height:160px;width:160px;">
        <canvas id="riskChart" role="img" aria-label="Donut chart of hazard zones by risk level">No data.</canvas>
        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
          <span style="font-family:'Oswald';font-size:26px;font-weight:600;color:var(--ink);"><?= $totalZones ?></span>
          <span style="font-size:11px;color:var(--ink-dim);">hazard zones</span>
        </div>
      </div>
      <div style="display:flex;gap:16px;font-size:12px;color:var(--ink-dim);flex-wrap:wrap;justify-content:center;">
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:#D6472E;display:inline-block;"></span>High</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:#E8963E;display:inline-block;"></span>Moderate</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:#3FA34D;display:inline-block;"></span>Low</span>
      </div>
    </div>
  </div>

</div>

<div style="margin-top:16px;display:grid;grid-template-columns:2fr 1fr;gap:16px;">

  <!-- Incidents by barangay -->
  <div class="panel">
    <div class="panel-head">
      <h3>Incidents by barangay</h3>
      <span style="font-size:11px;color:var(--ink-dim);">Top 6</span>
    </div>
    <div style="padding:20px 24px;">
      <div style="position:relative;" id="brgyChartWrap">
        <canvas id="brgyChart" role="img" aria-label="Horizontal bar chart of incidents by barangay">No data.</canvas>
      </div>
    </div>
  </div>

  <!-- Hydrant status -->
  <div class="panel">
    <div class="panel-head">
      <h3>Hydrant status</h3>
      <span style="font-size:11px;color:var(--ink-dim);"><?= $totalHydrants ?> total</span>
    </div>
    <div style="padding:20px 24px;">
      <div style="position:relative;height:200px;">
        <canvas id="hydrantChart" role="img" aria-label="Hydrant status chart">No data.</canvas>
      </div>
      <div style="display:flex;gap:16px;font-size:12px;color:var(--ink-dim);margin-top:12px;flex-wrap:wrap;">
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:#3FA34D;display:inline-block;"></span>Active</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:#E8963E;display:inline-block;"></span>Inactive</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:#D6472E;display:inline-block;"></span>Maintenance</span>
      </div>
    </div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor   = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
  const tickColor   = isDark ? '#8B93A3' : '#6B7180';
  const labelColor  = isDark ? '#ECEFF4' : '#1B1F27';

  const monthLabels   = <?= $monthLabels ?>;
  const monthCounts   = <?= $monthCounts ?>;
  const brgyLabels    = <?= $brgyLabels ?>;
  const brgyCounts    = <?= $brgyCounts ?>;
  const hydrantLabels = <?= $hydrantLabels ?>;
  const hydrantCounts = <?= $hydrantCounts ?>;
  const riskLabels    = <?= $riskLabels ?>;
  const riskCounts    = <?= $riskCounts ?>;

  const riskColorMap = { high:'#D6472E', moderate:'#E8963E', low:'#3FA34D' };
  const riskColors = riskLabels.map(l => riskColorMap[l.toLowerCase()] || '#8B93A3');

  const hydrantColorMap = { active:'#3FA34D', inactive:'#E8963E', maintenance:'#D6472E' };
  const hydrantColors = hydrantLabels.map(l => hydrantColorMap[l.toLowerCase()] || '#8B93A3');

  const peakIdx = monthCounts.indexOf(Math.max(...monthCounts));
  const barColors = monthCounts.map((_, i) => i === peakIdx ? '#D6472E' : '#2F6FA8');

  // Monthly bar chart
  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
      labels: monthLabels,
      datasets: [{
        data: monthCounts,
        backgroundColor: barColors,
        borderRadius: 4,
        borderSkipped: 'bottom'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { display: false }, border: { display: false } },
        y: { ticks: { color: tickColor, font: { size: 11 }, stepSize: 1 }, grid: { color: gridColor }, border: { display: false }, beginAtZero: true }
      }
    }
  });

  // Risk donut
  new Chart(document.getElementById('riskChart'), {
    type: 'doughnut',
    data: {
      labels: riskLabels,
      datasets: [{ data: riskCounts, backgroundColor: riskColors, borderWidth: 0, hoverOffset: 4 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: { legend: { display: false }, tooltip: { callbacks: {
        label: ctx => ` ${ctx.label}: ${ctx.raw}`
      }}}
    }
  });

  // Barangay horizontal bar
  const brgyHeight = Math.max(160, brgyLabels.length * 40 + 40);
  document.getElementById('brgyChartWrap').style.height = brgyHeight + 'px';
  new Chart(document.getElementById('brgyChart'), {
    type: 'bar',
    data: {
      labels: brgyLabels,
      datasets: [{
        data: brgyCounts,
        backgroundColor: brgyCounts.map((_, i) => i === 0 ? '#D6472E' : '#2F6FA8'),
        borderRadius: 4,
        borderSkipped: 'left'
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, border: { display: false }, beginAtZero: true },
        y: { ticks: { color: labelColor, font: { size: 12 } }, grid: { display: false }, border: { display: false } }
      }
    }
  });

  // Hydrant stacked bar
  const hydrantDatasets = hydrantLabels.map((label, i) => ({
    label,
    data: [hydrantCounts[i]],
    backgroundColor: hydrantColorMap[label.toLowerCase()] || '#8B93A3',
    borderRadius: i === 0 ? { topLeft:4, bottomLeft:4 } : (i === hydrantLabels.length-1 ? { topRight:4, bottomRight:4 } : 0),
    borderSkipped: false
  }));

  new Chart(document.getElementById('hydrantChart'), {
    type: 'bar',
    data: { labels: ['Hydrants'], datasets: hydrantDatasets },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { stacked: true, ticks: { color: tickColor, font:{size:11} }, grid: { color: gridColor }, border: { display: false } },
        y: { stacked: true, ticks: { display: false }, grid: { display: false }, border: { display: false } }
      }
    }
  });
})();
</script>

  </main>
</div><!-- /#app -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>window.FIRESTRAT_MODE = 'admin';</script>
<script src="assets/js/app.js"></script>

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