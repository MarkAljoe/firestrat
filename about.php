<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'about';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .about-hero {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 28px 24px;
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    margin-bottom: 20px;
  }
  .about-hero img {
    width: 64px;
    height: 64px;
    object-fit: contain;
    border-radius: 14px;
    flex-shrink: 0;
  }
  .about-hero-text .title {
    font-family: 'Oswald', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--ink, #fff);
    letter-spacing: 0.04em;
  }
  .about-hero-text .sub {
    font-size: 13px;
    color: var(--ink-dim, #888);
    margin-top: 3px;
  }
  .about-hero-text .version {
    display: inline-block;
    margin-top: 8px;
    font-size: 11px;
    font-weight: 600;
    background: var(--ember, #e03e1a);
    color: #fff;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.03em;
  }

  .about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
  }
  @media (max-width: 640px) { .about-grid { grid-template-columns: 1fr; } }

  .about-card {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    padding: 20px 22px;
  }
  .about-card h4 {
    font-family: 'Oswald', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-dim, #888);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 14px;
  }
  .info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid var(--line, #2a2a2a);
    gap: 12px;
  }
  .info-row:last-child { border-bottom: none; }
  .info-label {
    font-size: 12px;
    color: var(--ink-dim, #888);
    flex-shrink: 0;
  }
  .info-val {
    font-size: 12px;
    color: var(--ink, #ddd);
    text-align: right;
    font-family: 'IBM Plex Mono', monospace;
  }

  .about-desc {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    padding: 22px 24px;
    margin-bottom: 20px;
  }
  .about-desc h4 {
    font-family: 'Oswald', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-dim, #888);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 12px;
  }
  .about-desc p {
    font-size: 13px;
    color: var(--ink, #ccc);
    line-height: 1.7;
    margin: 0 0 10px;
  }
  .about-desc p:last-child { margin: 0; }

  .feature-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 4px;
  }
  @media (max-width: 640px) { .feature-list { grid-template-columns: 1fr; } }
  .feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--panel-3, #242424);
    border-radius: 8px;
    font-size: 12px;
    color: var(--ink, #ddd);
  }
  .feature-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--ember, #e03e1a);
    flex-shrink: 0;
  }
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
      <div class="page-title" style="flex:1;text-align:center;">About</div>
      <div style="flex-shrink:0;">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <!-- Hero -->
    <div class="about-hero">
      <img src="assets/img/logo.png" alt="FireStrat Logo">
      <div class="about-hero-text">
        <div class="title">FireStrat</div>
        <div class="sub">Fire Incident Management System</div>
        <span class="version">Version 1.0.0</span>
      </div>
    </div>

    <!-- Info cards -->
    <div class="about-grid">
      <div class="about-card">
        <h4>Station Info</h4>
        <div class="info-row">
          <span class="info-label">Station</span>
          <span class="info-val">BFP Virac</span>
        </div>
        <div class="info-row">
          <span class="info-label">Location</span>
          <span class="info-val">Virac, Catanduanes</span>
        </div>
        <div class="info-row">
          <span class="info-label">Region</span>
          <span class="info-val">Region V (Bicol)</span>
        </div>
        <div class="info-row">
          <span class="info-label">Contact</span>
          <span class="info-val">(052) 811-XXXX</span>
        </div>
        <div class="info-row">
          <span class="info-label">Emergency</span>
          <span class="info-val">911</span>
        </div>
      </div>

      <div class="about-card">
        <h4>System Info</h4>
        <div class="info-row">
          <span class="info-label">System</span>
          <span class="info-val">FireStrat v1.0.0</span>
        </div>
        <div class="info-row">
          <span class="info-label">Platform</span>
          <span class="info-val">PHP / MySQL</span>
        </div>
        <div class="info-row">
          <span class="info-label">Map</span>
          <span class="info-val">Leaflet.js + OSM</span>
        </div>
        <div class="info-row">
          <span class="info-label">Coverage</span>
          <span class="info-val">Down Town, Virac, Catanduanes</span>
        </div>
        <div class="info-row">
          <span class="info-label">Year</span>
          <span class="info-val"><?= date('Y') ?></span>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="about-desc">
      <h4>About FireStrat</h4>
      <p>FireStrat is a fire incident management and hazard mapping system developed for the Bureau of Fire Protection (BFP) station in Virac, Catanduanes. It provides real-time tools for fire officers to monitor, log, and respond to fire incidents across all barangays.</p>
      <p>The system allows community members to submit incident reports directly to the station without requiring an account, ensuring faster response times and better coordination between the public and fire personnel.</p>
    </div>

    <!-- Features -->
    <div class="about-card" style="margin-bottom:20px;">
      <h4>Key Features</h4>
      <div class="feature-list" style="margin-top:12px;">
        <div class="feature-item"><div class="feature-dot"></div>Real-time incident logging</div>
        <div class="feature-item"><div class="feature-dot"></div>Interactive hazard map</div>
        <div class="feature-item"><div class="feature-dot"></div>Community incident reporting</div>
        <div class="feature-item"><div class="feature-dot"></div>Hydrant location tracking</div>
        <div class="feature-item"><div class="feature-dot"></div>On-spot investigation reports</div>
        <div class="feature-item"><div class="feature-dot"></div>Post-incident documentation</div>
        <div class="feature-item"><div class="feature-dot"></div>Barangay risk level monitoring</div>
        <div class="feature-item"><div class="feature-dot"></div>Manage locations via map</div>
      </div>
    </div>

  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>