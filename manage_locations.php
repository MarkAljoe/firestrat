<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'managelocations';
$errors = [];
$success = '';

// ── Handle POST ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'barangay') {
        $name = trim($_POST['name'] ?? '');
        $lat  = trim($_POST['lat']  ?? '');
        $lng  = trim($_POST['lng']  ?? '');

        if (!$name)                          $errors[] = 'Barangay name is required.';
        if (!is_numeric($lat) || !is_numeric($lng)) $errors[] = 'Valid coordinates are required.';

        if (!$errors) {
            $stmt = $db->prepare("INSERT INTO barangays (name, lat, lng) VALUES (?, ?, ?)");
            $stmt->execute([$name, $lat, $lng]);
            $success = 'Barangay added successfully.';
        }
    }

    if ($type === 'hydrant') {
        $label  = trim($_POST['label']  ?? '');
        $lat    = trim($_POST['lat']    ?? '');
        $lng    = trim($_POST['lng']    ?? '');
        $status = trim($_POST['status'] ?? 'operational');

        if (!$label)                         $errors[] = 'Hydrant label is required.';
        if (!is_numeric($lat) || !is_numeric($lng)) $errors[] = 'Valid coordinates are required.';

        if (!$errors) {
            $stmt = $db->prepare("INSERT INTO hydrants (label, lat, lng, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$label, $lat, $lng, $status]);
            $success = 'Hydrant added successfully.';
        }
    }
    if ($type === 'edit_barangay') {
    $id   = (int)($_POST['id']   ?? 0);
    $name = trim($_POST['name']  ?? '');
    $lat  = trim($_POST['lat']   ?? '');
    $lng  = trim($_POST['lng']   ?? '');

    if ($id && $name && is_numeric($lat) && is_numeric($lng)) {
        $db->prepare("UPDATE barangays SET name=?, lat=?, lng=? WHERE id=?")->execute([$name, $lat, $lng, $id]);
        $success = 'Barangay updated.';
    }
}

if ($type === 'edit_hydrant') {
    $id     = (int)($_POST['id']     ?? 0);
    $code   = trim($_POST['code']    ?? '');
    $lat    = trim($_POST['lat']     ?? '');
    $lng    = trim($_POST['lng']     ?? '');
    $status = trim($_POST['status']  ?? 'active');

    if ($id && $code && is_numeric($lat) && is_numeric($lng)) {
        $db->prepare("UPDATE hydrants SET code=?, lat=?, lng=?, status=? WHERE id=?")->execute([$code, $lat, $lng, $status, $id]);
        $success = 'Hydrant updated.';
    }
}
}

// ── Fetch existing ────────────────────────────────────
$barangays = $db->query("SELECT * FROM barangays WHERE lat IS NOT NULL ORDER BY name")->fetchAll();
$hydrants = $db->query("SELECT * FROM hydrants WHERE lat IS NOT NULL ORDER BY code")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Locations — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .loc-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 16px;
  }
  @media (max-width: 768px) { .loc-grid { grid-template-columns: 1fr; } }

  .loc-card {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    padding: 20px;
  }
  .loc-card h3 {
    font-family: 'Oswald', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: var(--ink, #fff);
    margin: 0 0 16px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .form-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 12px;
  }
  .form-field label {
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-dim, #888);
    letter-spacing: 0.03em;
    text-transform: uppercase;
  }
  .form-field input,
  .form-field select {
    background: var(--panel-3, #242424);
    border: 1px solid var(--line, #333);
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 13px;
    color: var(--ink, #fff);
    font-family: 'Inter', sans-serif;
    width: 100%;
    box-sizing: border-box;
    transition: border-color 0.15s;
  }
  .form-field input:focus,
  .form-field select:focus {
    outline: none;
    border-color: var(--ember, #e03e1a);
  }
  .coord-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }
  .btn-submit {
    width: 100%;
    padding: 10px;
    background: var(--ember, #e03e1a);
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 4px;
    transition: opacity 0.15s;
  }
  .btn-submit:hover { opacity: 0.85; }

  .alert {
    padding: 10px 14px;
    border-radius: 7px;
    font-size: 13px;
    margin-bottom: 16px;
  }
  .alert-success { background: #16532a; color: #4ade80; border: 1px solid #22c55e44; }
  .alert-error   { background: #4a1515; color: #f87171; border: 1px solid #ef444444; }

  /* ── Map preview ── */
  #location-map {
    width: 100%;
    height: 340px;
    border-radius: 10px;
    border: 1px solid var(--line, #2a2a2a);
    margin-top: 20px;
  }

  /* ── Existing entries table ── */
  .entries-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-top: 16px;
  }
  .entries-table th {
    text-align: left;
    padding: 7px 10px;
    background: var(--panel-3, #1f1f1f);
    color: var(--ink-dim, #888);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.03em;
    border-bottom: 1px solid var(--line, #2a2a2a);
  }
  .entries-table td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--line, #222);
    color: var(--ink, #ddd);
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11px;
  }
  .entries-table tr:last-child td { border-bottom: none; }
  .entries-table tr:hover td { background: var(--panel-2, #1a1a1a); }

  .delete-btn {
    background: transparent;
    border: 1px solid #ef444433;
    color: #f87171;
    border-radius: 5px;
    padding: 3px 9px;
    font-size: 11px;
    cursor: pointer;
    transition: background 0.15s;
  }
  .delete-btn:hover { background: #4a1515; }

  .pick-btn {
    padding: 8px 14px;
    background: var(--panel-3, #242424);
    border: 1px solid var(--line, #333);
    color: var(--ink, #ccc);
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    margin-bottom: 12px;
    transition: background 0.15s;
    width: 100%;
  }
  .pick-btn:hover { background: var(--panel-2, #1a1a1a); }
  .pick-btn.active { border-color: var(--ember, #e03e1a); color: var(--ember, #e03e1a); }
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
      <div class="page-title" style="flex:1;text-align:center;">Manage Locations</div>
      <div style="flex-shrink:0;">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <!-- ── Add forms ── -->
    <div class="loc-grid">

      <!-- Barangay form -->
      <div class="loc-card">
        <h3>Add Barangay</h3>
        <form method="post">
          <input type="hidden" name="type" value="barangay">
          <div class="form-field">
            <label>Barangay Name</label>
            <input type="text" name="name" placeholder="e.g. Barangay San Juan" required>
          </div>
          <div class="coord-row">
            <div class="form-field">
              <label>Latitude</label>
              <input type="text" name="lat" id="brgy_lat" placeholder="e.g. 13.5796" required>
            </div>
            <div class="form-field">
              <label>Longitude</label>
              <input type="text" name="lng" id="brgy_lng" placeholder="e.g. 124.2422" required>
            </div>
          </div>
          <button type="button" class="pick-btn" id="pickBrgyBtn" onclick="startPick('brgy')">
            📍 Pick from map instead
          </button>
          <button type="submit" class="btn-submit">Add Barangay</button>
        </form>

        <!-- Existing barangays -->
        <?php if ($barangays): ?>
        <div style="overflow-x:auto;margin-top:20px;">
          <table class="entries-table">
            <thead><tr><th>Name</th><th>Lat</th><th>Lng</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($barangays as $b): ?>
              <tr>
                <td style="font-family:'Inter',sans-serif;"><?= htmlspecialchars($b['name']) ?></td>
                <td><?= $b['lat'] ?></td>
                <td><?= $b['lng'] ?></td>
                <td style="display:flex;gap:6px;">
                  <button class="delete-btn" style="color:#60a5fa;border-color:#60a5fa33;" 
                    onclick="openEditBarangay(<?= $b['id'] ?>, '<?= addslashes($b['name']) ?>', '<?= $b['lat'] ?>', '<?= $b['lng'] ?>')">Edit</button>
                  <form method="post" action="delete_location.php" onsubmit="return confirm('Delete this barangay?')" style="margin:0;">
                    <input type="hidden" name="type" value="barangay">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button class="delete-btn" type="submit">Remove</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Hydrant form -->
      <div class="loc-card">
        <h3>Add Hydrant</h3>
        <form method="post">
          <input type="hidden" name="type" value="hydrant">
          <div class="form-field">
            <label>Hydrant Label</label>
            <input type="text" name="label" placeholder="e.g. Hydrant #12" required>
          </div>
          <div class="form-field">
            <label>Status</label>
            <select name="status">
              <option value="operational">Operational</option>
              <option value="maintenance">Maintenance</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="coord-row">
            <div class="form-field">
              <label>Latitude</label>
              <input type="text" name="lat" id="hyd_lat" placeholder="e.g. 13.5801" required>
            </div>
            <div class="form-field">
              <label>Longitude</label>
              <input type="text" name="lng" id="hyd_lng" placeholder="e.g. 124.2435" required>
            </div>
          </div>
          <button type="button" class="pick-btn" id="pickHydBtn" onclick="startPick('hyd')">
            📍 Pick from map instead
          </button>
          <button type="submit" class="btn-submit">Add Hydrant</button>
        </form>

        <!-- Existing hydrants -->
        <?php if ($hydrants): ?>
        <div style="overflow-x:auto;margin-top:20px;">
          <table class="entries-table">
            <thead><tr><th>Code</th><th>Lat</th><th>Lng</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($hydrants as $h): ?>
              <tr>
                <td style="font-family:'Inter',sans-serif;"><?= htmlspecialchars($h['code']) ?></td>
                <td><?= $h['lat'] ?></td>
                <td><?= $h['lng'] ?></td>
                <td><?= htmlspecialchars($h['status'] ?? '—') ?></td>
                <td style="display:flex;gap:6px;">
                  <button class="delete-btn" style="color:#60a5fa;border-color:#60a5fa33;"
                    onclick="openEditHydrant(<?= $h['id'] ?>, '<?= addslashes($h['code']) ?>', '<?= $h['lat'] ?>', '<?= $h['lng'] ?>', '<?= $h['status'] ?>')">Edit</button>
                  <form method="post" action="delete_location.php" onsubmit="return confirm('Delete this hydrant?')" style="margin:0;">
                    <input type="hidden" name="type" value="hydrant">
                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                    <button class="delete-btn" type="submit">Remove</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /.loc-grid -->

    <!-- ── Map preview ── -->
    <div class="panel" style="margin-top:20px;">
      <div class="panel-head"><h3>Location Preview</h3></div>
      <div style="padding:12px 18px 18px;">
        <p style="font-size:12px;color:var(--ink-dim);margin:0 0 10px;" id="pickHint">Click a "Pick from map" button above, then click the map to drop a pin.</p>
        <div id="location-map"></div>
      </div>
    </div>

  </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<script>
// ── Existing data from PHP ─────────────────────────────
const existingBarangays = <?= json_encode(array_map(fn($b) => [
  'name' => $b['name'], 'lat' => (float)$b['lat'], 'lng' => (float)$b['lng']
], $barangays)) ?>;

const existingHydrants = <?= json_encode(array_map(fn($h) => [
  'label' => $h['code'], 'lat' => (float)$h['lat'], 'lng' => (float)$h['lng'], 'status' => $h['status']
], $hydrants)) ?>;

// ── Init map (centred on Virac, Catanduanes) ───────────
const map = L.map('location-map').setView([13.5796, 124.2422], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

// ── Plot existing barangays ────────────────────────────
existingBarangays.forEach(b => {
  L.circleMarker([b.lat, b.lng], { radius: 8, color: '#e03e1a', fillColor: '#e03e1a', fillOpacity: 0.7 })
    .addTo(map).bindPopup(`<strong>${b.name}</strong>`);
});

// ── Plot existing hydrants ─────────────────────────────
const hydrantColor = { operational: '#22c55e', maintenance: '#e09a1a', inactive: '#888' };
existingHydrants.forEach(h => {
  const color = hydrantColor[h.status] ?? '#888';
  L.circleMarker([h.lat, h.lng], { radius: 6, color, fillColor: color, fillOpacity: 0.9 })
    .addTo(map).bindPopup(`<strong>${h.label}</strong><br>${h.status}`);
});

// ── Pick-from-map logic ────────────────────────────────
let pickMode = null;
let pickMarker = null;

function startPick(type) {
  pickMode = type;
  document.getElementById('pickHint').textContent = '🔴 Click anywhere on the map to place the pin.';
  document.getElementById('pickBrgyBtn').classList.toggle('active', type === 'brgy');
  document.getElementById('pickHydBtn').classList.toggle('active', type === 'hyd');
  map.getContainer().style.cursor = 'crosshair';
}

map.on('click', function(e) {
  if (!pickMode) return;
  const { lat, lng } = e.latlng;
  const latFixed = lat.toFixed(7);
  const lngFixed = lng.toFixed(7);

  if (pickMarker) map.removeLayer(pickMarker);
  pickMarker = L.marker([lat, lng]).addTo(map)
    .bindPopup(`Lat: ${latFixed}<br>Lng: ${lngFixed}`).openPopup();

  if (pickMode === 'brgy') {
    document.getElementById('brgy_lat').value = latFixed;
    document.getElementById('brgy_lng').value = lngFixed;
  } else {
    document.getElementById('hyd_lat').value = latFixed;
    document.getElementById('hyd_lng').value = lngFixed;
  }

  document.getElementById('pickHint').textContent = '✅ Coordinates filled. You can adjust manually or pick again.';
  map.getContainer().style.cursor = '';
  document.getElementById('pickBrgyBtn').classList.remove('active');
  document.getElementById('pickHydBtn').classList.remove('active');
  pickMode = null;
});
</script>
<!-- Edit Barangay Modal -->
<div id="editBrgyModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;padding:16px;">
  <div style="background:var(--panel-2,#1a1a1a);border:1px solid var(--line,#2a2a2a);border-radius:14px;max-width:440px;width:100%;padding:24px;">
    <h3 style="font-family:'Oswald',sans-serif;font-size:15px;font-weight:600;color:var(--ink,#fff);margin:0 0 18px;text-transform:uppercase;letter-spacing:0.04em;">Edit Barangay</h3>
    <form method="post">
      <input type="hidden" name="type" value="edit_barangay">
      <input type="hidden" name="id" id="edit_brgy_id">
      <div class="form-field">
        <label>Barangay Name</label>
        <input type="text" name="name" id="edit_brgy_name" required>
      </div>
      <div class="coord-row">
        <div class="form-field">
          <label>Latitude</label>
          <input type="text" name="lat" id="edit_brgy_lat" required>
        </div>
        <div class="form-field">
          <label>Longitude</label>
          <input type="text" name="lng" id="edit_brgy_lng" required>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:16px;">
        <button type="submit" class="btn-submit" style="flex:1;">Save Changes</button>
        <button type="button" onclick="document.getElementById('editBrgyModal').style.display='none'" 
          style="flex:1;padding:10px;background:transparent;color:var(--ink-dim,#888);border:1px solid var(--line,#333);border-radius:7px;font-size:13px;cursor:pointer;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Hydrant Modal -->
<div id="editHydModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;padding:16px;">
  <div style="background:var(--panel-2,#1a1a1a);border:1px solid var(--line,#2a2a2a);border-radius:14px;max-width:440px;width:100%;padding:24px;">
    <h3 style="font-family:'Oswald',sans-serif;font-size:15px;font-weight:600;color:var(--ink,#fff);margin:0 0 18px;text-transform:uppercase;letter-spacing:0.04em;">Edit Hydrant</h3>
    <form method="post">
      <input type="hidden" name="type" value="edit_hydrant">
      <input type="hidden" name="id" id="edit_hyd_id">
      <div class="form-field">
        <label>Hydrant Code</label>
        <input type="text" name="code" id="edit_hyd_code" required>
      </div>
      <div class="form-field">
        <label>Status</label>
        <select name="status" id="edit_hyd_status">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="coord-row">
        <div class="form-field">
          <label>Latitude</label>
          <input type="text" name="lat" id="edit_hyd_lat" required>
        </div>
        <div class="form-field">
          <label>Longitude</label>
          <input type="text" name="lng" id="edit_hyd_lng" required>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:16px;">
        <button type="submit" class="btn-submit" style="flex:1;">Save Changes</button>
        <button type="button" onclick="document.getElementById('editHydModal').style.display='none'"
          style="flex:1;padding:10px;background:transparent;color:var(--ink-dim,#888);border:1px solid var(--line,#333);border-radius:7px;font-size:13px;cursor:pointer;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditBarangay(id, name, lat, lng) {
  document.getElementById('edit_brgy_id').value  = id;
  document.getElementById('edit_brgy_name').value = name;
  document.getElementById('edit_brgy_lat').value  = lat;
  document.getElementById('edit_brgy_lng').value  = lng;
  document.getElementById('editBrgyModal').style.display = 'flex';
}
function openEditHydrant(id, code, lat, lng, status) {
  document.getElementById('edit_hyd_id').value     = id;
  document.getElementById('edit_hyd_code').value   = code;
  document.getElementById('edit_hyd_lat').value    = lat;
  document.getElementById('edit_hyd_lng').value    = lng;
  document.getElementById('edit_hyd_status').value = status;
  document.getElementById('editHydModal').style.display = 'flex';
}
</script>
</body>
</html>