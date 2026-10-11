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

$barangays = $db->query('SELECT id, name FROM barangays ORDER BY name')->fetchAll();
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
      <button class="iconbtn" id="mobileMenuBtn" title="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
      </button>
      <div class="page-title" style="flex:1;text-align:center;">Pending reports</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
        <!-- Add Report Button -->
        <button class="iconbtn" id="openAddReport" title="Add report" style="color:var(--ember);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v8M8 12h8"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head">
        <h3>Awaiting approval</h3>
        <button class="iconbtn" id="openAddReport2" title="Add report"
          style="display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;
                 background:var(--ember);color:#fff;font-size:13px;font-weight:600;border:none;cursor:pointer;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add report
        </button>
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

<!-- ── Add Report Modal ── -->
<div id="addReportModal" style="
  display:none;
  position:fixed;inset:0;z-index:9999;
  background:rgba(0,0,0,0.65);
  align-items:center;justify-content:center;
  padding:16px;
">
  <div style="
    background:var(--panel-2,#1a1a1a);
    border:1px solid var(--line,#2a2a2a);
    border-radius:14px;
    width:100%;max-width:480px;
    max-height:90vh;
    display:flex;flex-direction:column;
    box-shadow:0 8px 32px rgba(0,0,0,0.6);
  ">
    <!-- Modal header -->
    <div style="
      display:flex;align-items:center;justify-content:space-between;
      padding:18px 20px 14px;
      border-bottom:1px solid var(--line,#2a2a2a);
      flex-shrink:0;
    ">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="
          width:32px;height:32px;border-radius:8px;
          background:var(--ember);
          display:flex;align-items:center;justify-content:center;
        ">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            <path d="M12 9v4M12 17h.01"/>
          </svg>
        </div>
        <strong style="font-size:15px;color:var(--ink,#fff);">Add Incident Report</strong>
      </div>
      <button id="closeAddReport" style="
        background:none;border:none;
        color:var(--ink-dim,#888);
        font-size:22px;cursor:pointer;line-height:1;
        padding:4px 8px;border-radius:6px;
      ">×</button>
    </div>

    <!-- Modal body -->
    <div style="overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;">

      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Barangay</label>
        <select id="mBrgy" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;">
          <?php foreach ($barangays as $b): ?>
            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Risk level</label>
        <select id="mRisk" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;">
          <option value="low">Low</option>
          <option value="moderate" selected>Moderate</option>
          <option value="high">High</option>
        </select>
      </div>

      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Reporter name <span style="opacity:0.5;">(optional)</span></label>
        <input type="text" id="mName" placeholder="e.g. Juan Dela Cruz"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;box-sizing:border-box;">
      </div>

      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Contact number <span style="opacity:0.5;">(optional)</span></label>
        <input type="tel" id="mPhone" placeholder="e.g. 0917 123 4567"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;box-sizing:border-box;">
      </div>

      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Details / notes</label>
        <textarea id="mNotes" rows="3" placeholder="Describe the incident or hazard…"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;resize:vertical;box-sizing:border-box;font-family:inherit;"></textarea>
      </div>

      <div id="modalMsg" class="form-msg" style="display:none;"></div>

    </div>

    <!-- Modal footer -->
    <div style="
      padding:14px 20px;
      border-top:1px solid var(--line,#2a2a2a);
      display:flex;gap:10px;justify-content:flex-end;
      flex-shrink:0;
    ">
      <button id="cancelAddReport" style="
        padding:9px 18px;border-radius:8px;
        background:var(--panel,#111);
        border:1px solid var(--line,#2a2a2a);
        color:var(--ink-dim,#888);
        font-size:14px;font-weight:500;cursor:pointer;
      ">Cancel</button>
      <button id="submitAddReport" style="
        padding:9px 18px;border-radius:8px;
        background:var(--ember);
        border:none;color:#fff;
        font-size:14px;font-weight:600;cursor:pointer;
      ">Submit report</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
  // ── Modal open/close ──
  function openModal()  { document.getElementById('addReportModal').style.display = 'flex'; }
  function closeModal() {
    document.getElementById('addReportModal').style.display = 'none';
    document.getElementById('mNotes').value  = '';
    document.getElementById('mName').value   = '';
    document.getElementById('mPhone').value  = '';
    document.getElementById('mRisk').value   = 'moderate';
    const msg = document.getElementById('modalMsg');
    msg.style.display = 'none'; msg.textContent = '';
  }

  document.getElementById('openAddReport').onclick  = openModal;
  document.getElementById('openAddReport2').onclick = openModal;
  document.getElementById('closeAddReport').onclick  = closeModal;
  document.getElementById('cancelAddReport').onclick = closeModal;

  // Close on backdrop click
  document.getElementById('addReportModal').onclick = function(e) {
    if (e.target === this) closeModal();
  };

  // ── Submit ──
  document.getElementById('submitAddReport').onclick = async function () {
    const btn         = this;
    const barangay_id = document.getElementById('mBrgy').value;
    const risk_level  = document.getElementById('mRisk').value;
    const notes       = document.getElementById('mNotes').value.trim();
    const reporter_name    = document.getElementById('mName').value.trim();
    const reporter_contact = document.getElementById('mPhone').value.trim();
    const msg = document.getElementById('modalMsg');

    if (!notes) {
      msg.className = 'form-msg err'; msg.textContent = 'Please enter details about the incident.';
      msg.style.display = 'block'; return;
    }

    btn.disabled = true; btn.textContent = 'Submitting…';
    try {
      await fetch('api/incidents.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ barangay_id, risk_level, notes, reported_by: 'admin', reporter_name, reporter_contact })
      });
      msg.className = 'form-msg ok'; msg.textContent = 'Report submitted successfully!';
      msg.style.display = 'block';
      btn.textContent = 'Submit report';
      btn.disabled = false;
      setTimeout(() => { closeModal(); location.reload(); }, 1000);
    } catch (e) {
      msg.className = 'form-msg err'; msg.textContent = 'Could not submit: ' + e.message;
      msg.style.display = 'block';
      btn.textContent = 'Submit report'; btn.disabled = false;
    }
  };
</script>
</body>
</html>