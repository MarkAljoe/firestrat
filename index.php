<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
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
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <div class="page-title">Dashboard overview</div>
      <div class="topbar-right">
        <div class="search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input id="searchInput" placeholder="Search incidents…">
        </div>
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode"><svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg></button>
        <button class="iconbtn" id="splitBtn" title="Toggle split analytics view">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="18" rx="1.5"/><rect x="13" y="3" width="8" height="18" rx="1.5"/></svg>
        </button>
      </div>
    </div>

    <div class="cards">
      <div class="card"><div class="lbl">Total incidents</div><div class="val"><?= $totalIncidents ?></div><div class="delta up">Live count</div></div>
      <div class="card"><div class="lbl">High-risk zones</div><div class="val"><?= $highRiskZones ?></div><div class="delta warn">barangays</div></div>
      <div class="card"><div class="lbl">Active hydrants</div><div class="val"><?= $activeHydrants ?></div><div class="delta ok"><?= $hydrantPct ?>% operational</div></div>
      <a href="reports.php" class="card" style="text-decoration:none;color:inherit;">
  <div class="lbl">Pending reports</div>
  <div class="val"><?= $pendingReports ?></div>
  <div class="delta warn">Awaiting approval</div>
</a>
    </div>

    <div class="grid" id="mainGrid">
    
      <div class="panel map-panel" id="map">
        <div class="panel-head">
          <h3>Virac hazard map</h3>
          <div class="legend">
            <span><i style="background:var(--ember)"></i>High risk</span>
            <span><i style="background:var(--amber)"></i>Moderate</span>
            <span><i style="background:var(--green)"></i>Low risk</span>
            <span><i style="background:var(--blue);border-radius:2px"></i>Hydrants</span>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="panel">
          <div class="panel-head"><h3>Recent incidents</h3></div>
          <div id="incidentList"></div>
        </div>
        <div class="panel" id="hydrants">
          <div class="panel-head"><h3>Nearby hydrants</h3></div>
          <div id="hydrantList"></div>
        </div>
      </div>
    </div>

    <div class="panel" id="analyticsPanel" style="display:none;margin-top:16px;">
      <div class="panel-head"><h3>Incidents by barangay</h3></div>
      <div class="bars" id="barsWrap"></div>
    </div>

    <!-- <div class="panel" id="incidents" style="margin-top:16px;">
      <div class="panel-head"><h3>Log a new incident</h3></div>
      <div class="report-form">
        <label>Barangay</label>
        <select id="fBrgy"></select>
        <label>Risk level</label>
        <select id="fRisk"><option value="high">High</option><option value="moderate">Moderate</option><option value="low">Low</option></select>
        <label>Notes</label>
        <textarea id="fNotes" rows="2" placeholder="Short description…"></textarea>
        <div id="formMsg" class="form-msg"></div>
        <button id="submitIncident">Add incident</button>
      </div>
    </div>
  </main>
</div> -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>window.FIRESTRAT_MODE = 'admin';</script>
<script src="assets/js/app.js"></script>
</body>
</html>
