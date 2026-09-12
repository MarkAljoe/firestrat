<?php require_once __DIR__ . '/includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FireStrat — Stay safe, Virac</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="comm-wrap">
  <div class="comm-top">
    <a href="login.php" class="iconbtn" title="Back to login">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M19 12H5M12 19l-7-7 7-7"/>
  </svg>
</a>
    <div class="brand"><div class="mark"></div><div class="brand-text"><div class="title">FireStrat</div><div class="sub">BFP Virac, Catanduanes</div></div></div>
    <button class="iconbtn" id="themeBtn" title="Toggle dark mode"><svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg></button>
  </div>

  <div class="comm-hero">
    <h1>Stay safe, Virac.</h1>
    <p>Check fire risk near you, find the nearest hydrant, and report a hazard in seconds — no jargon, no login required.</p>
  </div>

  <div class="comm-actions">
    <button class="comm-btn" id="commReportBtn">
      <div class="ic" style="background:var(--ember)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg></div>
      <h4>Report a incident</h4>
      <p>See smoke, a fire risk, or a broken hydrant? Tell the fire station right away.</p>
    </button>
    
  </div>

  <div class="panel" style="margin-bottom:16px;">
    <div class="panel-head">
      <h3>Map</h3>
      <div class="legend">
        <span><i style="background:var(--ember)"></i>High risk</span>
        <span><i style="background:var(--amber)"></i>Moderate</span>
        <span><i style="background:var(--green)"></i>Low risk</span>
        <span><i style="background:var(--blue);border-radius:2px"></i>Hydrants</span>
      </div>
    </div>
    <div id="map"></div>
  </div>

  <div class="comm-section">
    <h3>Risk near your barangay</h3>
    <div id="commZones"></div>
  </div>

  <div class="comm-section" id="commReportSection" style="display:none;">
    <h3>Report a incident</h3>
    <div class="report-form">
      <label>Your barangay</label>
      <select id="cBrgy"></select>
      <label>Your name </label>
      <input type="text" id="cName" placeholder="e.g. Juan Dela Cruz">
<label>Contact number </label>
<input type="tel" id="cPhone" placeholder="e.g. 0917 123 4567">
<label>What's happening?</label>
      <textarea id="cNotes" rows="3" placeholder="e.g. Gas smell near the market, exposed wiring, small fire…"></textarea>
      <div id="commMsg" class="form-msg"></div>
      <button id="commSubmit">Send report to BFP Virac</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>window.FIRESTRAT_MODE = 'community';</script>
<script src="assets/js/app.js"></script>
</body>
</html>
