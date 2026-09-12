<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'barangays';

$rows = $db->query("
  SELECT b.id, b.name, b.risk_level, b.population,
         COUNT(i.id) AS incident_count
  FROM barangays b
  LEFT JOIN incidents i ON i.barangay_id = b.id AND i.approval_status = 'approved'
  GROUP BY b.id
  ORDER BY b.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangays — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <div class="page-title">Barangays</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head">
        <h3>All barangays</h3>
      </div>
      <div style="padding:8px 18px;">
        <?php foreach ($rows as $b): ?>
          <div class="comm-zone" style="align-items:center;">
            <div style="flex:1;">
              <div class="zn"><?= htmlspecialchars($b['name']) ?></div>
              <div class="zd">
                <?= $b['incident_count'] ?> incident<?= $b['incident_count'] == 1 ? '' : 's' ?> on record
                <?php if ($b['population']): ?> · Population: <?= htmlspecialchars($b['population']) ?><?php endif; ?>
              </div>
            </div>
            <div class="badge" style="background:<?= $b['risk_level']==='high' ? 'var(--ember)' : ($b['risk_level']==='moderate' ? 'var(--amber)' : 'var(--green)') ?>;margin-right:12px;">
              <?= ucfirst($b['risk_level']) ?>
            </div>
            <div style="display:flex;gap:6px;">
              <button class="iconbtn" style="background:var(--green);width:auto;padding:0 10px;font-size:11px;" onclick="setBarangayRisk(<?= $b['id'] ?>, 'low')">Low</button>
              <button class="iconbtn" style="background:var(--amber);width:auto;padding:0 10px;font-size:11px;" onclick="setBarangayRisk(<?= $b['id'] ?>, 'moderate')">Moderate</button>
              <button class="iconbtn" style="background:var(--ember);width:auto;padding:0 10px;font-size:11px;" onclick="setBarangayRisk(<?= $b['id'] ?>, 'high')">High</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>