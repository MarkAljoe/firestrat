<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'incidentlog';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT i.id, i.risk_level, i.notes, i.reporter_name, i.status, i.created_at, b.name AS barangay_name
        FROM incidents i JOIN barangays b ON b.id = i.barangay_id
        WHERE i.approval_status = 'approved'";
$params = [];
if ($q !== '') {
    $sql .= " AND (b.name LIKE ? OR i.notes LIKE ?)";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY i.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$incidents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Incidents — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  /* ── Incident card ─────────────────────────────────── */
  .incident-card {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  /* ── Card header row ───────────────────────────────── */
  .incident-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }
  .incident-meta {
    flex: 1;
    min-width: 0;
  }
  .incident-barangay {
    font-family: 'Oswald', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: var(--ink, #fff);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .incident-notes {
    font-size: 13px;
    color: var(--ink-dim, #888);
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .incident-time {
    font-size: 11px;
    color: var(--ink-dim, #888);
    margin-top: 3px;
  }

  /* ── Status pills row ──────────────────────────────── */
  .incident-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    flex-wrap: wrap;
  }
  .status-pill {
    border: none;
    border-radius: 6px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    background: var(--panel-3, #242424);
    color: var(--ink-dim, #888);
    transition: background 0.15s, color 0.15s;
    letter-spacing: 0.02em;
  }
  .status-pill.active-state   { background: var(--ember, #e03e1a); color: #fff; }
  .status-pill.contained-state { background: var(--amber, #e09a1a); color: #fff; }
  .status-pill.resolved-state  { background: var(--green, #22c55e); color: #fff; }

  .action-btn {
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 6px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    background: transparent;
    color: var(--ink, #ccc);
    transition: background 0.15s, border-color 0.15s;
    white-space: nowrap;
  }
  .action-btn:hover {
    background: var(--panel-3, #242424);
    border-color: var(--ink-dim, #555);
  }
  .spacer { flex: 1; }

  @media (max-width: 540px) {
    .incident-actions {
      flex-wrap: wrap;
      row-gap: 8px;
    }
    .spacer { display: none; }
    .action-btn {
      flex: 1 1 calc(50% - 3px);
      text-align: center;
    }
  }

  /* ── Inline report forms ───────────────────────────── */
  .report-form {
    display: none;
    margin-top: 14px;
    padding: 20px;
    background: var(--panel-3, #1f1f1f);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 10px;
  }
  .report-form h4 {
    margin: 0 0 16px;
    font-family: 'Oswald', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--ink, #fff);
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 16px;
  }
  .form-grid .full-width { grid-column: 1 / -1; }

  @media (max-width: 540px) {
    .form-grid {
      grid-template-columns: 1fr;
    }
    .form-grid .full-width { grid-column: 1; }
  }
  .form-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  .form-field label {
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-dim, #888);
    letter-spacing: 0.03em;
    text-transform: uppercase;
  }
  .form-field input,
  .form-field select,
  .form-field textarea {
    background: var(--panel-2, #1a1a1a);
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
  .form-field select:focus,
  .form-field textarea:focus {
    outline: none;
    border-color: var(--ember, #e03e1a);
  }
  .form-field textarea { resize: vertical; }

  .form-actions {
    display: flex;
    gap: 8px;
    margin-top: 18px;
  }
  .btn-submit {
    padding: 9px 20px;
    background: var(--ember, #e03e1a);
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
  }
  .btn-submit:hover { opacity: 0.85; }
  .btn-cancel {
    padding: 9px 20px;
    background: transparent;
    color: var(--ink-dim, #888);
    border: 1px solid var(--line, #333);
    border-radius: 7px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-cancel:hover { background: var(--panel-2, #1a1a1a); }

  @media (max-width: 540px) {
    .report-form,
    .view-reports-panel {
      padding: 14px 12px;
    }
    .incident-card {
      padding: 14px 14px;
    }
    .form-actions {
      flex-direction: column;
    }
    .btn-submit, .btn-cancel {
      width: 100%;
      text-align: center;
    }
  }

  /* ── View reports panel ────────────────────────────── */
  .view-reports-panel {
    display: none;
    margin-top: 14px;
    padding: 16px 20px;
    background: var(--panel-3, #1f1f1f);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 10px;
  }
  .report-entry {
    padding: 12px 0;
    border-bottom: 1px solid var(--line, #2a2a2a);
  }
  .report-entry:last-child { border-bottom: none; }
  .report-entry-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink, #fff);
    margin-bottom: 4px;
  }
  .report-entry .zd {
    font-size: 12px;
    color: var(--ink-dim, #888);
    margin-top: 2px;
    line-height: 1.5;
  }
</style>
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <div class="page-title">Incidents</div>
      <div class="topbar-right">
        <div class="search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>
          <form method="get" style="display:contents;">
            <input name="q" placeholder="Search incidents…" value="<?= htmlspecialchars($q) ?>">
          </form>
        </div>
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head">
        <h3>All incidents</h3>
      </div>

      <div style="padding:12px 18px 18px;">
        <?php if (!$incidents): ?>
          <p style="color:var(--ink-dim);margin:8px 0 0;">No incidents found.</p>
        <?php else: foreach ($incidents as $i): ?>
          <?php
            $onspotStmt = $db->prepare('SELECT * FROM onspot_reports WHERE incident_id = ? ORDER BY created_at DESC');
            $onspotStmt->execute([$i['id']]);
            $onspotReports = $onspotStmt->fetchAll();

            $postStmt = $db->prepare('SELECT * FROM post_incident_reports WHERE incident_id = ? ORDER BY created_at DESC');
            $postStmt->execute([$i['id']]);
            $postReports = $postStmt->fetchAll();

            $reportCount = count($onspotReports) + count($postReports);

            $riskColor = match($i['risk_level']) {
              'high'     => 'var(--ember)',
              'moderate' => 'var(--amber)',
              default    => 'var(--green)',
            };
            $statusActive    = $i['status'] === 'active'    ? 'active-state'    : '';
            $statusContained = $i['status'] === 'contained' ? 'contained-state' : '';
            $statusResolved  = $i['status'] === 'resolved'  ? 'resolved-state'  : '';
          ?>

          <div class="incident-card">

            <!-- Header: barangay name + meta + risk badge -->
            <div class="incident-header">
              <div class="incident-meta">
                <div class="incident-barangay"><?= htmlspecialchars($i['barangay_name']) ?></div>
                <?php if ($i['notes']): ?>
                  <div class="incident-notes"><?= htmlspecialchars($i['notes']) ?></div>
                <?php endif; ?>
                <div class="incident-time">
                  <?= date('M j, Y · g:i A', strtotime($i['created_at'])) ?>
                  <?php if ($i['reporter_name']): ?>
                    · Reported by <strong style="color:var(--ink)"><?= htmlspecialchars($i['reporter_name']) ?></strong>
                  <?php endif; ?>
                </div>
              </div>
              <div class="badge" style="background:<?= $riskColor ?>;flex-shrink:0;margin-top:2px;">
                <?= ucfirst($i['risk_level']) ?>
              </div>
            </div>

            <!-- Action row: status pills + action buttons -->
            <div class="incident-actions">
              <button class="status-pill <?= $statusActive ?>"
                onclick="setIncidentStatus(<?= $i['id'] ?>, 'active')">Active</button>
              <button class="status-pill <?= $statusContained ?>"
                onclick="setIncidentStatus(<?= $i['id'] ?>, 'contained')">Contained</button>
              <button class="status-pill <?= $statusResolved ?>"
                onclick="setIncidentStatus(<?= $i['id'] ?>, 'resolved')">Resolved</button>

              <div class="spacer"></div>

              <button class="action-btn" onclick="toggleOnspotForm(<?= $i['id'] ?>)">Add Report</button>
              <button class="action-btn" onclick="togglePostForm(<?= $i['id'] ?>)">Post Incident Report</button>
              <button class="action-btn" onclick="toggleViewReports(<?= $i['id'] ?>)">
                View Reports<?= $reportCount > 0 ? " ($reportCount)" : '' ?>
              </button>
            </div>

            <!-- On-Spot Investigation Form -->
            <div class="report-form" id="onspot-form-<?= $i['id'] ?>">
              <h4>On-Spot Investigation Report</h4>
              <div class="form-grid">
                <div class="form-field">
                  <label>Investigator's Name</label>
                  <input type="text" id="inv_name_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Position</label>
                  <input type="text" id="inv_position_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Date</label>
                  <input type="date" id="inv_date_<?= $i['id'] ?>" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-field">
                  <label>Time</label>
                  <input type="time" id="inv_time_<?= $i['id'] ?>" value="<?= date('H:i') ?>">
                </div>
                <div class="form-field">
                  <label>Location</label>
                  <input type="text" id="inv_location_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Risk Level</label>
                  <select id="inv_risk_<?= $i['id'] ?>">
                    <option value="high">High</option>
                    <option value="moderate">Moderate</option>
                    <option value="low">Low</option>
                  </select>
                </div>
                <div class="form-field">
                  <label>Estimated Cost Damage</label>
                  <input type="text" id="inv_cost_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Building Type</label>
                  <input type="text" id="inv_building_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Deployed Personnel</label>
                  <input type="number" id="inv_deployed_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Injured Personnel</label>
                  <input type="number" id="inv_inj_pers_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Injured Civilians</label>
                  <input type="number" id="inv_inj_civ_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Deaths</label>
                  <input type="number" id="inv_deaths_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Witness Name</label>
                  <input type="text" id="inv_witness_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Witness Address</label>
                  <input type="text" id="inv_wit_addr_<?= $i['id'] ?>">
                </div>
                <div class="form-field full-width">
                  <label>Testimony</label>
                  <textarea id="inv_testimony_<?= $i['id'] ?>" rows="3"></textarea>
                </div>
                <div class="form-field full-width">
                  <label>Conclusion of Origin</label>
                  <textarea id="inv_conclusion_<?= $i['id'] ?>" rows="3"></textarea>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn-submit" onclick="submitOnspotReport(<?= $i['id'] ?>)">Submit Report</button>
                <button class="btn-cancel" onclick="toggleOnspotForm(<?= $i['id'] ?>)">Cancel</button>
              </div>
            </div>

            <!-- Post Incident Form -->
            <div class="report-form" id="post-form-<?= $i['id'] ?>">
              <h4>Post Incident Report</h4>
              <div class="form-grid">
                <div class="form-field">
                  <label>Investigator's Name</label>
                  <input type="text" id="post_name_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Position</label>
                  <input type="text" id="post_position_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Date</label>
                  <input type="date" id="post_date_<?= $i['id'] ?>" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-field">
                  <label>Time</label>
                  <input type="time" id="post_time_<?= $i['id'] ?>" value="<?= date('H:i') ?>">
                </div>
                <div class="form-field">
                  <label>Location</label>
                  <input type="text" id="post_location_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Risk Level</label>
                  <select id="post_risk_<?= $i['id'] ?>">
                    <option value="high">High</option>
                    <option value="moderate">Moderate</option>
                    <option value="low">Low</option>
                  </select>
                </div>
                <div class="form-field">
                  <label>Estimated Final Cost Damage</label>
                  <input type="text" id="post_cost_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Building Type</label>
                  <input type="text" id="post_building_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Incident Scale</label>
                  <input type="text" id="post_scale_<?= $i['id'] ?>">
                </div>
                <div class="form-field">
                  <label>Deployed Personnel</label>
                  <input type="number" id="post_deployed_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Injured Personnel</label>
                  <input type="number" id="post_inj_pers_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Injured Civilians</label>
                  <input type="number" id="post_inj_civ_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field">
                  <label>Deaths</label>
                  <input type="number" id="post_deaths_<?= $i['id'] ?>" min="0">
                </div>
                <div class="form-field full-width">
                  <label>Descriptive Narration</label>
                  <textarea id="post_narration_<?= $i['id'] ?>" rows="4"></textarea>
                </div>
                <div class="form-field full-width">
                  <label>Zone Documentation (photo)</label>
                  <input type="file" id="post_photo_<?= $i['id'] ?>" accept="image/*">
                </div>
              </div>
              <div class="form-actions">
                <button class="btn-submit" onclick="submitPostReport(<?= $i['id'] ?>)">Submit Report</button>
                <button class="btn-cancel" onclick="togglePostForm(<?= $i['id'] ?>)">Cancel</button>
              </div>
            </div>

            <!-- View Reports Panel -->
            <div class="view-reports-panel" id="view-reports-<?= $i['id'] ?>">
              <?php if (!$onspotReports && !$postReports): ?>
                <p style="color:var(--ink-dim);margin:0;font-size:13px;">No reports filed yet.</p>
              <?php endif; ?>

              <?php foreach ($onspotReports as $r): ?>
                <div class="report-entry">
                  <div class="report-entry-title">On-Spot Investigation Report</div>
                  <div class="zd">By <?= htmlspecialchars($r['investigator_name'] ?? '—') ?> · <?= htmlspecialchars($r['report_date'] ?? '') ?> <?= htmlspecialchars($r['report_time'] ?? '') ?></div>
                  <div class="zd">Location: <?= htmlspecialchars($r['location'] ?? '—') ?> · Risk: <?= ucfirst($r['risk_level'] ?? '—') ?></div>
                  <div class="zd">Est. Cost Damage: <?= htmlspecialchars($r['estimated_cost_damage'] ?? '—') ?> · Building: <?= htmlspecialchars($r['building_type'] ?? '—') ?></div>
                  <div class="zd">Deployed: <?= $r['personnel_deployed'] ?? 0 ?> · Injured Personnel: <?= $r['personnel_injured'] ?? 0 ?> · Injured Civilians: <?= $r['civilians_injured'] ?? 0 ?> · Deaths: <?= $r['deaths'] ?? 0 ?></div>
                  <?php if ($r['witness_name']): ?><div class="zd">Witness: <?= htmlspecialchars($r['witness_name']) ?> (<?= htmlspecialchars($r['witness_address'] ?? '') ?>)</div><?php endif; ?>
                  <?php if ($r['testimony']): ?><div class="zd">Testimony: <?= htmlspecialchars($r['testimony']) ?></div><?php endif; ?>
                  <?php if ($r['conclusion_of_origin']): ?><div class="zd">Conclusion of Origin: <?= htmlspecialchars($r['conclusion_of_origin']) ?></div><?php endif; ?>
                </div>
              <?php endforeach; ?>

              <?php foreach ($postReports as $r): ?>
                <div class="report-entry">
                  <div class="report-entry-title">Post Incident Report</div>
                  <div class="zd">By <?= htmlspecialchars($r['investigator_name'] ?? '—') ?> · <?= htmlspecialchars($r['report_date'] ?? '') ?> <?= htmlspecialchars($r['report_time'] ?? '') ?></div>
                  <div class="zd">Location: <?= htmlspecialchars($r['location'] ?? '—') ?> · Risk: <?= ucfirst($r['risk_level'] ?? '—') ?> · Scale: <?= htmlspecialchars($r['incident_scale'] ?? '—') ?></div>
                  <div class="zd">Est. Final Cost Damage: <?= htmlspecialchars($r['estimated_final_cost_damage'] ?? '—') ?> · Building: <?= htmlspecialchars($r['building_type'] ?? '—') ?></div>
                  <div class="zd">Deployed: <?= $r['personnel_deployed'] ?? 0 ?> · Injured Personnel: <?= $r['personnel_injured'] ?? 0 ?> · Injured Civilians: <?= $r['civilians_injured'] ?? 0 ?> · Deaths: <?= $r['deaths'] ?? 0 ?></div>
                  <?php if ($r['descriptive_narration']): ?><div class="zd">Narration: <?= htmlspecialchars($r['descriptive_narration']) ?></div><?php endif; ?>
                  <?php if ($r['zone_documentation_path']): ?><div class="zd"><a href="<?= htmlspecialchars($r['zone_documentation_path']) ?>" target="_blank">View zone photo</a></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>

          </div><!-- /.incident-card -->

        <?php endforeach; endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>