<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'reports';

$pending = $db->query("
  SELECT i.id, i.notes, i.reporter_name, i.reporter_contact, i.created_at, b.name AS barangay_name
  FROM incidents i JOIN barangays b ON b.id = i.barangay_id
  WHERE i.approval_status='pending'
  ORDER BY i.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <div class="page-title">Pending reports</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head">
        <h3>Awaiting approval</h3>
      </div>
      <div id="pendingReportsList" style="padding:16px 18px;">
        <?php if (!$pending): ?>
          <p style="color:var(--ink-dim);">No pending reports right now.</p>
        <?php else: foreach ($pending as $r): ?>
          <div class="comm-zone" style="align-items:flex-start;">
            <div style="flex:1;">
              <div class="zn"><?= htmlspecialchars($r['barangay_name']) ?></div>
              <div class="zd"><?= htmlspecialchars($r['notes']) ?></div>
              <?php if ($r['reporter_name']): ?><div class="zd">From: <?= htmlspecialchars($r['reporter_name']) ?></div><?php endif; ?>
              <?php if ($r['reporter_contact']): ?><div class="zd">Contact: <?= htmlspecialchars($r['reporter_contact']) ?></div><?php endif; ?>
            </div>
            <button class="iconbtn" onclick="approveReport(<?= $r['id'] ?>)" title="Approve">✓</button>
            <button class="iconbtn" onclick="rejectReport(<?= $r['id'] ?>)" title="Reject">✕</button>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>