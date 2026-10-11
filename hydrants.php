<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'hydrants';

$hydrants = $db->query("
    SELECT h.*, b.name AS barangay_name 
    FROM hydrants h 
    LEFT JOIN barangays b ON b.id = h.barangay_id 
    ORDER BY h.code
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hydrants — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .hydrant-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
  }
  @media (max-width: 540px) { .hydrant-stats { grid-template-columns: 1fr 1fr; } }

  .stat-card {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    padding: 16px 18px;
  }
  .stat-card .stat-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-dim, #888);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
  }
  .stat-card .stat-val {
    font-family: 'Oswald', sans-serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--ink, #fff);
    line-height: 1;
  }
  .stat-card .stat-sub {
    font-size: 11px;
    color: var(--ink-dim, #888);
    margin-top: 4px;
  }

  #hydrant-map {
    height: 340px;
    width: 100%;
    border-radius: 0 0 10px 10px;
  }

  .hydrant-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }
  .hydrant-table th {
    text-align: left;
    padding: 9px 14px;
    background: var(--panel-3, #1f1f1f);
    color: var(--ink-dim, #888);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.03em;
    border-bottom: 1px solid var(--line, #2a2a2a);
    white-space: nowrap;
  }
  .hydrant-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--line, #222);
    color: var(--ink, #ddd);
    vertical-align: middle;
  }
  .hydrant-table tr:last-child td { border-bottom: none; }
  .hydrant-table tr:hover td { background: var(--panel-2, #1a1a1a); }

  .status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
  }
  .status-badge.active   { background: #16532a; color: #4ade80; }
  .status-badge.inactive { background: #2a2a2a; color: #888; }

  .code-tag {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 12px;
    background: var(--panel-3, #242424);
    padding: 3px 8px;
    border-radius: 5px;
    color: var(--ember, #e03e1a);
  }

  .toggle-btn {
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 6px;
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    background: transparent;
    color: var(--ink, #ccc);
    transition: background 0.15s;
    white-space: nowrap;
  }
  .toggle-btn:hover { background: var(--panel-3, #242424); }
  .toggle-btn.active-btn  { border-color: #22c55e44; color: #4ade80; }
  .toggle-btn.inactive-btn { border-color: #ef444433; color: #f87171; }

  .search-wrap {
    padding: 12px 18px;
    border-bottom: 1px solid var(--line, #2a2a2a);
  }
  .search-wrap input {
    width: 100%;
    box-sizing: border-box;
    background: var(--panel-3, #242424);
    border: 1px solid var(--line, #333);
    border-radius: 7px;
    padding: 8px 12px;
    font-size: 13px;
    color: var(--ink, #fff);
    font-family: 'Inter', sans-serif;
  }
  .search-wrap input:focus { outline: none; border-color: var(--ember, #e03e1a); }
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
      <div class="page-title" style="flex:1;text-align:center;">Hydrants</div>
      <div style="flex-shrink:0;">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <?php
      $total    = count($hydrants);
      $active   = count(array_filter($hydrants, fn($h) => $h['status'] === 'active'));
      $inactive = $total - $active;
    ?>

    <!-- Stats -->
    <div class="hydrant-stats">
      <div class="stat-card">
        <div class="stat-label">Total</div>
        <div class="stat-val"><?= $total ?></div>
        <div class="stat-sub">hydrants mapped</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active</div>
        <div class="stat-val" style="color:#4ade80;"><?= $active ?></div>
        <div class="stat-sub">operational</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Inactive</div>
        <div class="stat-val" style="color:#f87171;"><?= $inactive ?></div>
        <div class="stat-sub">need attention</div>
      </div>
    </div>

    <!-- Map -->
    <div class="panel" style="margin-bottom:20px;">
      <div class="panel-head">
        <h3>Hydrant Map</h3>
        <div class="legend">
          <span><i style="background:#22c55e;border-radius:50%"></i>Active</span>
          <span><i style="background:#888;border-radius:50%"></i>Inactive</span>
        </div>
      </div>
      <div id="hydrant-map"></div>
    </div>

    <!-- List -->
    <div class="panel">
      <div class="panel-head">
        <h3>All Hydrants</h3>
      </div>
      <div class="search-wrap">
        <input type="text" id="hydrantSearch" placeholder="Search by code or barangay…">
      </div>
      <div style="overflow-x:auto;">
        <table class="hydrant-table" id="hydrantTable">
          <thead>
            <tr>
              <th>Code</th>
              <th>Barangay</th>
              <th>Coordinates</th>
              <th>Last Checked</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hydrants as $h): ?>
            <tr data-code="<?= strtolower($h['code']) ?>" data-barangay="<?= strtolower($h['barangay_name'] ?? '') ?>">
              <td><span class="code-tag"><?= htmlspecialchars($h['code']) ?></span></td>
              <td><?= htmlspecialchars($h['barangay_name'] ?? '—') ?></td>
              <td style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:var(--ink-dim);">
                <?= $h['lat'] ? $h['lat'].', '.$h['lng'] : '—' ?>
              </td>
              <td style="font-size:12px;color:var(--ink-dim);">
                <?= $h['last_checked'] ? date('M j, Y', strtotime($h['last_checked'])) : '—' ?>
              </td>
              <td>
                <span class="status-badge <?= $h['status'] ?>">
                  <?= ucfirst($h['status']) ?>
                </span>
              </td>
              <td>
                <button class="toggle-btn <?= $h['status'] === 'active' ? 'inactive-btn' : 'active-btn' ?>"
                  onclick="toggleHydrant(<?= $h['id'] ?>, '<?= $h['status'] === 'active' ? 'inactive' : 'active' ?>')">
                  <?= $h['status'] === 'active' ? 'Set Inactive' : 'Set Active' ?>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!$hydrants): ?>
          <p style="color:var(--ink-dim);padding:16px 18px;margin:0;font-size:13px;">No hydrants found. Add some via <a href="manage_locations.php" style="color:var(--ember);">Manage Locations</a>.</p>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<script>
const hydrantData = <?= json_encode(array_map(fn($h) => [
  'id'            => $h['id'],
  'code'          => $h['code'],
  'lat'           => (float)$h['lat'],
  'lng'           => (float)$h['lng'],
  'status'        => $h['status'],
  'barangay_name' => $h['barangay_name'] ?? '—'
], array_filter($hydrants, fn($h) => $h['lat'] && $h['lng']))) ?>;

// ── Map ────────────────────────────────────────────────
const map = L.map('hydrant-map').setView([13.5796, 124.2422], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

hydrantData.forEach(h => {
  const color = h.status === 'active' ? '#22c55e' : '#888';
  L.circleMarker([h.lat, h.lng], {
    radius: 7, color, fillColor: color, fillOpacity: 0.9, weight: 2
  }).addTo(map).bindPopup(
    `<strong>Hydrant ${h.code}</strong><br>${h.barangay_name}<br>
     <span style="color:${color}">${h.status}</span>`
  );
});

// ── Search ─────────────────────────────────────────────
document.getElementById('hydrantSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#hydrantTable tbody tr').forEach(row => {
    const code = row.dataset.code ?? '';
    const brgy = row.dataset.barangay ?? '';
    row.style.display = (code.includes(q) || brgy.includes(q)) ? '' : 'none';
  });
});

// ── Toggle status ──────────────────────────────────────
async function toggleHydrant(id, newStatus) {
  try {
    const res = await fetch('api/hydrants.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status: newStatus })
    });
    if (!res.ok) throw new Error('Failed');
    location.reload();
  } catch(e) {
    alert('Could not update hydrant status.');
  }
}
</script>
</body>
</html>