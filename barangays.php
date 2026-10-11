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
<style>
  .brgy-card {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 0;
  }
  .brgy-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }
  .brgy-meta { flex: 1; min-width: 0; }
  .brgy-name {
    font-family: 'Oswald', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: var(--ink, #fff);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .brgy-sub {
    font-size: 12px;
    color: var(--ink-dim, #888);
    margin-top: 3px;
  }
  .brgy-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    flex-wrap: wrap;
  }
  .risk-pill {
    border: none;
    border-radius: 6px;
    padding: 5px 14px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    background: var(--panel-3, #242424);
    color: var(--ink-dim, #888);
    transition: background 0.15s, color 0.15s;
    letter-spacing: 0.02em;
  }
  .risk-pill.low-state      { background: var(--green, #22c55e); color: #fff; }
  .risk-pill.moderate-state { background: var(--amber, #e09a1a); color: #fff; }
  .risk-pill.high-state     { background: var(--ember, #e03e1a); color: #fff; }

  @media (max-width: 540px) {
    .brgy-card { padding: 14px; }
    .brgy-actions { row-gap: 8px; }
    .risk-pill { flex: 1 1 calc(33% - 4px); text-align: center; }
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
      <div class="page-title" style="flex:1;text-align:center;">Barangays</div>
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
      <div style="padding:12px 18px 18px;">
        <?php foreach ($rows as $b): ?>
          <?php
            $riskColor = match($b['risk_level']) {
              'high'     => 'var(--ember)',
              'moderate' => 'var(--amber)',
              default    => 'var(--green)',
            };
            $lowState      = $b['risk_level'] === 'low'      ? 'low-state'      : '';
            $moderateState = $b['risk_level'] === 'moderate' ? 'moderate-state' : '';
            $highState     = $b['risk_level'] === 'high'     ? 'high-state'     : '';
          ?>
          <div class="brgy-card">
            <div class="brgy-header">
              <div class="brgy-meta">
                <div class="brgy-name"><?= htmlspecialchars($b['name']) ?></div>
                <div class="brgy-sub">
                  <?= $b['incident_count'] ?> incident<?= $b['incident_count'] == 1 ? '' : 's' ?> on record
                  <?php if ($b['population']): ?>
                    · Population: <?= htmlspecialchars($b['population']) ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="badge" style="background:<?= $riskColor ?>;flex-shrink:0;margin-top:2px;">
                <?= ucfirst($b['risk_level']) ?>
              </div>
            </div>
            <div class="brgy-actions">
              <button class="risk-pill <?= $lowState ?>"
                onclick="setBarangayRisk(<?= $b['id'] ?>, 'low')">Low</button>
              <button class="risk-pill <?= $moderateState ?>"
                onclick="setBarangayRisk(<?= $b['id'] ?>, 'moderate')">Moderate</button>
              <button class="risk-pill <?= $highState ?>"
                onclick="setBarangayRisk(<?= $b['id'] ?>, 'high')">High</button>
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