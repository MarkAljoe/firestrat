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
    <div class="brand">
      <div class="mark" style="display:flex;align-items:center;justify-content:center;background:none;box-shadow:none;border:none;border-radius:0;padding:0;">
        <img src="assets/img/logo.png" alt="FireStrat Logo" style="width:40px;height:40px;object-fit:contain;">
      </div>
      <div class="brand-text">
        <div class="title">FireStrat</div>
        <div class="sub">BFP Virac, Catanduanes</div>
      </div>
    </div>
    <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
      <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
    </button>
  </div>

  <div class="comm-hero">
    <h1>Stay safe, Virac.</h1>
    <p>Check fire risk near you, find the nearest hydrant, and report a hazard in seconds — no jargon, no login required.</p>
  </div>

  <div class="comm-actions">
    <button class="comm-btn" id="commReportBtn">
      <div class="ic" style="background:var(--ember)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          <path d="M12 9v4M12 17h.01"/>
        </svg>
      </div>
      <h4>Report a incident</h4>
      <p>See smoke, a fire risk, or a broken hydrant? Tell the fire station right away.</p>
    </button>
  </div>

  <div class="panel" style="margin-bottom:16px;">
    <div class="panel-head">
      <h3>Map</h3>
    </div>
    <div id="map"></div>
  </div>

  <div class="comm-section">
    <h3>Risk near your barangay</h3>
    <div id="commZones"></div>
  </div>

</div>

<!-- ── Report Modal ── -->
<div id="commReportModal" style="
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
    max-height:92vh;
    display:flex;flex-direction:column;
    box-shadow:0 8px 32px rgba(0,0,0,0.6);
  ">
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
        <strong style="font-size:15px;color:var(--ink,#fff);">Report an Incident</strong>
      </div>
      <button id="closeCommModal" style="
        background:none;border:none;
        color:var(--ink-dim,#888);
        font-size:22px;cursor:pointer;line-height:1;
        padding:4px 8px;border-radius:6px;
      ">×</button>
    </div>

    <div style="overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Your barangay</label>
        <select id="cBrgy" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;"></select>
      </div>
      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Your name <span style="opacity:0.5;">(full name)</span></label>
        <input type="text" id="cName" placeholder="e.g. Juan Dela Cruz"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Contact number <span style="opacity:0.5;">(required)</span></label>
        <input type="tel" id="cPhone" placeholder="e.g. 0917 123 4567"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:12px;color:var(--ink-dim,#888);margin-bottom:6px;font-weight:500;">Purok # and What's happening? <span style="opacity:0.5;">(next to what?)</span></label>
        <textarea id="cNotes" rows="3" placeholder="e.g. Gas smell near the market, exposed wiring, small fire…"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line,#2a2a2a);background:var(--panel,#111);color:var(--ink,#fff);font-size:14px;resize:vertical;box-sizing:border-box;font-family:inherit;"></textarea>
      </div>
      <label style="display:flex;align-items:flex-start;gap:10px;font-size:12px;color:var(--ink-dim,#888);cursor:pointer;line-height:1.5;">
        <input type="checkbox" id="privacyConsent" style="margin-top:2px;flex-shrink:0;accent-color:var(--ember,#e03e1a);">
        <span>I understand the information I provide here will be used by BFP Virac officials to respond to this report.
          <a href="#" onclick="document.getElementById('privacyModal').style.display='flex';return false;"
             style="color:var(--ember,#e03e1a);text-decoration:none;">Read the Privacy Notice</a>
        </span>
      </label>
      <div id="commMsg" class="form-msg" style="display:none;"></div>
    </div>

    <div style="
      padding:14px 20px;
      border-top:1px solid var(--line,#2a2a2a);
      display:flex;gap:10px;justify-content:flex-end;
      flex-shrink:0;
    ">
      <button id="cancelCommModal" style="
        padding:9px 18px;border-radius:8px;
        background:var(--panel,#111);
        border:1px solid var(--line,#2a2a2a);
        color:var(--ink-dim,#888);
        font-size:14px;font-weight:500;cursor:pointer;
      ">Cancel</button>
      <button id="commSubmit" style="
        padding:9px 18px;border-radius:8px;
        background:var(--ember);
        border:none;color:#fff;
        font-size:14px;font-weight:600;cursor:pointer;
      ">Send report to BFP Virac</button>
    </div>
  </div>
</div>

<!-- ── Privacy Notice Modal ── -->
<div id="privacyModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;padding:16px;">
  <div style="background:var(--panel-2,#1a1a1a);border:1px solid var(--line,#2a2a2a);border-radius:14px;max-width:560px;width:100%;max-height:80vh;display:flex;flex-direction:column;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line,#2a2a2a);">
      <strong style="font-size:15px;color:var(--ink,#fff);">Privacy Notice</strong>
      <button onclick="document.getElementById('privacyModal').style.display='none'" style="background:none;border:none;color:var(--ink-dim,#888);font-size:20px;cursor:pointer;line-height:1;">×</button>
    </div>
    <div style="overflow-y:auto;padding:20px;font-size:13px;color:var(--ink,#ccc);line-height:1.7;">
      <p style="color:var(--ink-dim,#888);font-style:italic;margin:0 0 16px;">This notice explains how FireStrat — BFP Virac's fire incident management system — collects and uses personal information, in line with the Philippine Data Privacy Act of 2012 (RA 10173).</p>
      <h4 style="font-size:13px;color:var(--ink,#fff);margin:0 0 8px;">What we collect</h4>
      <p style="margin:0 0 16px;">When you submit an incident report, we collect your name, contact number, a description of the incident, your barangay location, and any other details you choose to provide.</p>
      <h4 style="font-size:13px;color:var(--ink,#fff);margin:0 0 8px;">Why we collect it</h4>
      <p style="margin:0 0 16px;">To allow BFP Virac fire officers to respond to and coordinate on reported incidents. Your report helps protect lives and property in your community.</p>
      <h4 style="font-size:13px;color:var(--ink,#fff);margin:0 0 8px;">Who can see it</h4>
      <p style="margin:0 0 16px;">Incident reports are visible only to logged-in BFP Virac fire officers. Reports are reviewed and approved before any action is taken. Your personal contact details are never shared publicly.</p>
      <h4 style="font-size:13px;color:var(--ink,#fff);margin:0 0 8px;">Your rights</h4>
      <p style="margin:0;">Under RA 10173, you have the right to access, correct, or request deletion of your personal information. Contact BFP Virac station directly for any privacy concerns.</p>
    </div>
  </div>
</div>

<!-- ── Barangay Info Modal ── -->
<div id="brgy-info-modal" style="
  display:none;position:fixed;inset:0;z-index:10001;
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>window.FIRESTRAT_MODE = 'community';</script>
<script src="assets/js/app.js"></script>
<script>
  document.getElementById('commReportBtn').onclick = function() {
    document.getElementById('commReportModal').style.display = 'flex';
  };

  function closeCommModal() {
    document.getElementById('commReportModal').style.display = 'none';
    document.getElementById('cNotes').value  = '';
    document.getElementById('cName').value   = '';
    document.getElementById('cPhone').value  = '';
    document.getElementById('privacyConsent').checked = false;
    const msg = document.getElementById('commMsg');
    msg.style.display = 'none'; msg.textContent = '';
  }

  document.getElementById('closeCommModal').onclick  = closeCommModal;
  document.getElementById('cancelCommModal').onclick = closeCommModal;

  document.getElementById('commReportModal').onclick = function(e) {
    if (e.target === this) closeCommModal();
  };
</script>
</body>
</html>