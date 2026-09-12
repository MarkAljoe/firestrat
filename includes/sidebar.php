<?php $active = $active ?? 'dashboard'; ?>
<?php
$pendingCount = isset($db) ? (int)$db->query("SELECT COUNT(*) c FROM incidents WHERE approval_status='pending'")->fetch()['c'] : 0;
?>

<button class="iconbtn" id="mobileMenuBtn" title="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="mark"></div>
    <div class="brand-text"><div class="title">FireStrat</div><div class="sub">BFP Virac, Catanduanes</div></div>
  </div>

  <div class="nav-label">Main</div>
  <a class="nav-item <?= $active==='dashboard'?'active':'' ?>" href="index.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
    Dashboard
  </a>
  <a class="nav-item <?= $active==='hydrants'?'active':'' ?>" href="index.php#hydrants">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M9 2h6M8 8h8l1 4H7l1-4z"/><rect x="7" y="12" width="10" height="8" rx="1.5"/></svg>
    Hydrants
  </a>
  <a class="nav-item <?= $active==='hazardmap'?'active':'' ?>" href="hazard_map.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-6-3V4l6 3 6-3 6 3v13l-6-3-6 3z"/><path d="M9 7v13M15 4v13"/></svg>
    Hazard Map
  </a>
  <a class="nav-item <?= $active==='incidentlog'?'active':'' ?>" href="incident_log.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
    Incidents
    <span class="nav-badge" id="incBadge">–</span>
  </a>

  <div class="nav-label">Management</div>
  
  <a class="nav-item <?= $active==='reports'?'active':'' ?>" href="reports.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3v4a1 1 0 001 1h4M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>Reports<span class="nav-badge" id="reportsBadge" style="<?= $pendingCount > 0 ? '' : 'display:none;' ?>"><?= $pendingCount ?></span></a>
<a class="nav-item <?= $active==='barangays'?'active':'' ?>" href="barangays.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 21s-7-6-7-11a7 7 0 0114 0c0 5-7 11-7 11z"/></svg>Barangays</a>
 
<a class="nav-item <?= $active==='users'?'active':'' ?>" href="users.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">...(keep the same icon path)...</svg>Users</a>

  <div class="nav-label">System</div>
  <span class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9"/></svg>Settings</span>

  <div class="sidebar-foot">
    <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'FM', 0, 2)) ?></div>
    <div class="avatar-txt">
      <div class="n"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Fire Marshal') ?></div>
      <div class="r"><?= htmlspecialchars($_SESSION['role'] ?? 'fire_marshal') ?></div> 
    </div>
    <a class="logout-link" href="logout.php">Sign out</a>
  </div>
</aside>
