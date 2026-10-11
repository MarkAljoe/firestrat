<?php $active = $active ?? 'dashboard'; ?>
<?php
$activeIncidentCount = isset($db) ? (int)$db->query("SELECT COUNT(*) c FROM incidents WHERE approval_status='approved' AND status='active'")->fetch()['c'] : 0;
$pendingCount = isset($db) ? (int)$db->query("SELECT COUNT(*) c FROM incidents WHERE approval_status='pending'")->fetch()['c'] : 0;
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="assets/img/logo.png" alt="FireStrat Logo" style="width:34px;height:34px;object-fit:contain;border-radius:8px;flex-shrink:0;">
    <div class="brand-text"><div class="title">FireStrat</div><div class="sub">BFP Virac, Catanduanes</div></div>
  </div>

  <div class="nav-label">Main</div>
  <a class="nav-item <?= $active==='dashboard'?'active':'' ?>" href="index.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
    Dashboard
  </a>
  <a class="nav-item <?= $active==='hydrants'?'active':'' ?>" href="hydrants.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M9 2h6M8 8h8l1 4H7l1-4z"/><rect x="7" y="12" width="10" height="8" rx="1.5"/></svg>
    Hydrants
  </a>
  <a class="nav-item <?= $active==='hazardmap'?'active':'' ?>" href="hazard_map.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-6-3V4l6 3 6-3 6 3v13l-6-3-6 3z"/><path d="M9 7v13M15 4v13"/></svg>
    Hazard Map
  </a>
  <a class="nav-item <?= $active==='incidentlog'?'active':'' ?>" href="incident_log.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
    Incidents & Reports 
    <span class="nav-badge" id="incBadge" style="<?= $activeIncidentCount > 0 ? '' : 'display:none;' ?>"><?= $activeIncidentCount ?></span>
  </a>

  <div class="nav-label">Management</div>
  
  <a class="nav-item <?= $active==='reports'?'active':'' ?>" href="reports.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3v4a1 1 0 001 1h4M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>Pending Reports<span class="nav-badge" id="reportsBadge" style="<?= $pendingCount > 0 ? '' : 'display:none;' ?>"><?= $pendingCount ?></span></a>
<a class="nav-item <?= $active==='barangays'?'active':'' ?>" href="barangays.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 21s-7-6-7-11a7 7 0 0114 0c0 5-7 11-7 11z"/></svg>Barangays</a>

<a class="nav-item <?= $active==='managelocations'?'active':'' ?>" href="manage_locations.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
  Manage Locations
</a>
 
<div class="nav-label">System</div>
<a class="nav-item <?= $active==='about'?'active':'' ?>" href="about.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
  About
</a>
<a class="nav-item <?= $active==='settings'?'active':'' ?>" href="settings.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 010 2.8 2 2 0 01-2.8 0l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 01-2.8-2.8l.1-.1A1.7 1.7 0 004.6 15a1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1A1.7 1.7 0 004.6 9a1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 012.8-2.8l.1.1A1.7 1.7 0 009 4.6a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 012.8 2.8l-.1.1A1.7 1.7 0 0019.4 9a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
  Settings
</a>

  <div class="sidebar-foot">
    <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'FM', 0, 2)) ?></div>
    <div class="avatar-txt">
      <div class="n"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Fire Marshal') ?></div>
      <div class="r"><?= htmlspecialchars($_SESSION['role'] ?? 'fire_marshal') ?></div> 
    </div>
    <a class="logout-link" href="logout.php">Sign out</a>
  </div>
</aside>