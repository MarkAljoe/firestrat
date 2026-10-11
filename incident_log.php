<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'incidentlog';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT i.id, i.ticket_number, i.risk_level, i.notes, i.reporter_name,
               i.reporter_contact, i.reported_by, i.status, i.created_at,
               b.name AS barangay_name
        FROM incidents i JOIN barangays b ON b.id = i.barangay_id
        WHERE i.approval_status = 'approved'";
$params = [];
if ($q !== '') {
    $sql .= " AND (b.name LIKE ? OR i.notes LIKE ? OR i.ticket_number LIKE ?)";
    $params = ["%$q%", "%$q%", "%$q%"];
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
  .incident-header { display: flex; align-items: flex-start; gap: 12px; }
  .incident-meta { flex: 1; min-width: 0; }
  .incident-barangay {
    font-family: 'Oswald', sans-serif;
    font-size: 15px; font-weight: 600;
    color: var(--ink, #fff);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .incident-ticket {
    font-size: 11px; font-family: 'IBM Plex Mono', monospace;
    color: var(--ember, #e03e1a); margin-top: 2px; letter-spacing: 0.04em;
  }
  .incident-notes {
    font-size: 13px; color: var(--ink-dim, #888);
    margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
  .incident-time { font-size: 11px; color: var(--ink-dim, #888); margin-top: 3px; }
  .incident-actions {
    display: flex; align-items: center; gap: 6px;
    margin-top: 12px; flex-wrap: wrap;
  }
  .status-pill {
    border: none; border-radius: 6px; padding: 5px 12px;
    font-size: 11px; font-weight: 600; cursor: pointer;
    background: var(--panel-3, #242424); color: var(--ink-dim, #888);
    transition: background 0.15s, color 0.15s; letter-spacing: 0.02em;
  }
  .status-pill.active-state  { background: var(--ember, #e03e1a); color: #fff; }
  .status-pill.fireout-state { background: var(--green, #22c55e); color: #fff; }
  .action-btn {
    border: 1px solid var(--line, #2a2a2a); border-radius: 6px;
    padding: 5px 12px; font-size: 11px; font-weight: 500;
    cursor: pointer; background: transparent; color: var(--ink, #ccc);
    transition: background 0.15s, border-color 0.15s; white-space: nowrap;
  }
  .action-btn:hover { background: var(--panel-3, #242424); border-color: var(--ink-dim, #555); }
  .spacer { flex: 1; }

  /* Source badge */
  .source-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600; padding: 3px 8px;
    border-radius: 5px; letter-spacing: 0.02em;
  }
  .source-badge.community { background: rgba(56,139,253,0.15); color: #58a6ff; }
  .source-badge.hotline   { background: rgba(232,150,62,0.15); color: var(--amber, #E8963E); }
  .source-badge.admin     { background: rgba(214,71,46,0.15);  color: var(--ember, #e03e1a); }

  /* View reports panel */
  .view-reports-panel {
    display: none; margin-top: 14px; padding: 16px 20px;
    background: var(--panel-3, #1f1f1f);
    border: 1px solid var(--line, #2a2a2a); border-radius: 10px;
  }
  .report-section-label {
    font-size: 11px; font-weight: 600; color: var(--ink-dim, #888);
    letter-spacing: 0.04em; text-transform: uppercase; margin: 0 0 10px;
  }
  .report-divider { height: 1px; background: var(--line, #2a2a2a); margin: 18px 0; }

  /* Community report card */
  .comm-report-card {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 8px; padding: 12px 14px; margin-bottom: 8px;
  }
  .comm-report-row {
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: 10px;
  }
  .comm-report-body { flex: 1; min-width: 0; }
  .comm-report-notes { font-size: 13px; color: var(--ink, #ddd); line-height: 1.5; }
  .comm-report-meta  { font-size: 11px; color: var(--ink-dim, #888); margin-top: 5px; }

  .report-table-wrap { overflow-x: auto; }
  .report-table {
    width: 100%; border-collapse: collapse; font-size: 12px;
    table-layout: fixed; min-width: 620px;
  }
  .report-table th {
    text-align: left; padding: 7px 10px;
    background: var(--panel-2, #161616); color: var(--ink-dim, #888);
    font-size: 11px; font-weight: 500; letter-spacing: 0.03em;
    border-bottom: 1px solid var(--line, #2a2a2a); white-space: nowrap;
  }
  .report-table td {
    padding: 8px 10px; border-bottom: 1px solid var(--line, #222);
    color: var(--ink, #ddd); vertical-align: top;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
  .report-table tr:last-child td { border-bottom: none; }
  .report-table tr:hover td { background: var(--panel-2, #1a1a1a); }
  .report-extras {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; margin-top: 12px;
  }
  .report-extras .full { grid-column: 1 / -1; }
  .report-extra-label {
    font-size: 11px; font-weight: 600; color: var(--ink-dim, #888);
    letter-spacing: 0.03em; text-transform: uppercase; margin: 0 0 3px;
  }
  .report-extra-val { font-size: 12px; color: var(--ink, #ddd); margin: 0; line-height: 1.5; }

  /* Note form */
  .note-form { margin-top: 14px; }
  .note-form textarea {
    width: 100%; padding: 9px 12px; border-radius: 8px;
    border: 1px solid var(--line, #2a2a2a);
    background: var(--panel-2, #1a1a1a); color: var(--ink, #fff);
    font-size: 13px; font-family: 'Inter', sans-serif;
    resize: vertical; box-sizing: border-box;
  }
  .note-form textarea:focus { outline: none; border-color: var(--ember); }
  .note-item {
    padding: 10px 12px; background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a); border-radius: 8px;
    margin-bottom: 8px; font-size: 13px; color: var(--ink, #ddd); line-height: 1.5;
  }
  .note-item-meta { font-size: 11px; color: var(--ink-dim, #888); margin-top: 4px; }

  /* Modal */
  .firestrat-modal {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.65);
    align-items: center; justify-content: center; padding: 16px;
  }
  .firestrat-modal.open { display: flex; }
  .modal-box {
    background: var(--panel-2, #1a1a1a);
    border: 1px solid var(--line, #2a2a2a);
    border-radius: 14px; width: 100%; max-width: 600px;
    max-height: 92vh; display: flex; flex-direction: column;
    box-shadow: 0 8px 32px rgba(0,0,0,0.6);
  }
  .modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px; border-bottom: 1px solid var(--line, #2a2a2a); flex-shrink: 0;
  }
  .modal-head-left { display: flex; align-items: center; gap: 10px; }
  .modal-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--ember); display: flex; align-items: center; justify-content: center;
  }
  .modal-icon.edit-icon { background: #2471a3; }
  .modal-title { font-size: 15px; font-weight: 600; color: var(--ink, #fff); }
  .modal-ticket {
    font-size: 11px; font-family: 'IBM Plex Mono', monospace;
    color: var(--ember); margin-top: 2px; letter-spacing: 0.04em;
  }
  .modal-close {
    background: none; border: none; color: var(--ink-dim, #888);
    font-size: 22px; cursor: pointer; line-height: 1; padding: 4px 8px; border-radius: 6px;
  }
  .modal-body { overflow-y: auto; padding: 20px; }
  .modal-footer {
    padding: 14px 20px; border-top: 1px solid var(--line, #2a2a2a);
    display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
  }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; }
  .form-grid .full-width { grid-column: 1 / -1; }
  .form-field { display: flex; flex-direction: column; gap: 5px; }
  .form-field label {
    font-size: 11px; font-weight: 600; color: var(--ink-dim, #888);
    letter-spacing: 0.03em; text-transform: uppercase;
  }
  .form-field input, .form-field select, .form-field textarea {
    background: var(--panel-2, #1a1a1a); border: 1px solid var(--line, #333);
    border-radius: 6px; padding: 8px 10px; font-size: 13px; color: var(--ink, #fff);
    font-family: 'Inter', sans-serif; width: 100%; box-sizing: border-box;
    transition: border-color 0.15s;
  }
  .form-field input:focus, .form-field select:focus, .form-field textarea:focus {
    outline: none; border-color: var(--ember, #e03e1a);
  }
  .form-field textarea { resize: vertical; }
  .btn-submit {
    padding: 9px 20px; background: var(--ember, #e03e1a); color: #fff;
    border: none; border-radius: 7px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: opacity 0.15s;
  }
  .btn-submit:hover { opacity: 0.85; }
  .btn-submit.blue { background: #2471a3; }
  .btn-cancel {
    padding: 9px 20px; background: transparent; color: var(--ink-dim, #888);
    border: 1px solid var(--line, #333); border-radius: 7px;
    font-size: 13px; font-weight: 500; cursor: pointer;
  }
  .btn-cancel:hover { background: var(--panel-2, #1a1a1a); }
  .edit-btn {
    border: 1px solid var(--line, #2a2a2a); border-radius: 5px;
    padding: 4px 10px; font-size: 11px; font-weight: 500;
    cursor: pointer; background: transparent; color: #58a6ff;
    border-color: rgba(56,139,253,0.3);
    transition: background 0.15s; white-space: nowrap; flex-shrink: 0;
  }
  .edit-btn:hover { background: rgba(56,139,253,0.1); }

  @media (max-width: 540px) {
    .incident-card { padding: 14px; }
    .incident-actions { flex-wrap: wrap; row-gap: 8px; }
    .spacer { display: none; }
    .action-btn { flex: 1 1 calc(50% - 3px); text-align: center; }
    .form-grid { grid-template-columns: 1fr; }
    .form-grid .full-width { grid-column: 1; }
    .modal-footer { flex-direction: column; }
    .btn-submit, .btn-cancel { width: 100%; text-align: center; }
    .report-extras { grid-template-columns: 1fr; }
    .report-extras .full { grid-column: 1; }
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
      <div class="page-title" style="flex:1;text-align:center;">Incidents & Reports</div>
      <div style="flex-shrink:0;">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="search" style="margin-bottom:16px;width:100%;box-sizing:border-box;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>
      <form method="get" style="display:contents;">
        <input name="q" placeholder="Search by barangay, notes, or ticket number…" value="<?= htmlspecialchars($q) ?>">
      </form>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head"><h3>All incidents & reports</h3></div>
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

            $notesStmt = $db->prepare('SELECT * FROM incident_notes WHERE incident_id = ? ORDER BY created_at ASC');
            $notesStmt->execute([$i['id']]);
            $incidentNotes = $notesStmt->fetchAll();

            $reportCount = count($onspotReports) + count($postReports);
            $hasOnspot   = count($onspotReports) > 0;
            $hasPost     = count($postReports) > 0;

            $ticket = $i['ticket_number'] ?? ('TKT-' . str_pad($i['id'], 5, '0', STR_PAD_LEFT));

            $riskColor = match($i['risk_level']) {
              'high'     => 'var(--ember)',
              'moderate' => 'var(--amber)',
              default    => 'var(--green)',
            };
            $statusActive  = $i['status'] === 'active'  ? 'active-state'  : '';
            $statusFireout = $i['status'] === 'fire_out' ? 'fireout-state' : '';

            // Source label
            $reportedBy = $i['reported_by'] ?? 'admin';
            if ($reportedBy === 'community') {
              $sourceLabel = '🌐 Community report';
              $sourceClass = 'community';
            } elseif ($reportedBy === 'hotline') {
              $sourceLabel = '📞 Hotline report';
              $sourceClass = 'hotline';
            } else {
              $sourceLabel = '🛡️ Officer logged';
              $sourceClass = 'admin';
            }
          ?>

          <div class="incident-card">
            <div class="incident-header">
              <div class="incident-meta">
                <div class="incident-barangay"><?= htmlspecialchars($i['barangay_name']) ?></div>
                <div class="incident-ticket">
                  <?= htmlspecialchars($ticket) ?>
                  <span class="source-badge <?= $sourceClass ?>" style="margin-left:6px;"><?= $sourceLabel ?></span>
                </div>
                <?php if ($i['notes']): ?>
                  <div class="incident-notes"><?= htmlspecialchars($i['notes']) ?></div>
                <?php endif; ?>
                <div class="incident-time">
                  <?= date('M j, Y · g:i A', strtotime($i['created_at'])) ?>
                  <?php if ($i['reporter_name']): ?>
                    · Reported by <strong style="color:var(--ink)"><?= htmlspecialchars($i['reporter_name']) ?></strong>
                  <?php endif; ?>
                  <?php if ($i['reporter_contact']): ?>
                    · <span style="color:var(--ink-dim)"><?= htmlspecialchars($i['reporter_contact']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="badge" style="background:<?= $riskColor ?>;flex-shrink:0;margin-top:2px;">
                <?= ucfirst($i['risk_level']) ?>
              </div>
            </div>

            <div class="incident-actions">
              <button class="status-pill <?= $statusActive ?>"
                onclick="setIncidentStatus(<?= $i['id'] ?>, 'active')">● Active</button>
              <button class="status-pill <?= $statusFireout ?>"
                onclick="setIncidentStatus(<?= $i['id'] ?>, 'fire_out')">✓ Fire Out</button>
              <div class="spacer"></div>
              <?php if (!$hasOnspot): ?>
                <button class="action-btn" onclick="openModal('onspot-modal-<?= $i['id'] ?>')">Add Report</button>
              <?php endif; ?>
              <?php if (!$hasPost): ?>
                <button class="action-btn" onclick="openModal('post-modal-<?= $i['id'] ?>')">Post Incident Report</button>
              <?php endif; ?>
              <button class="action-btn" onclick="toggleViewReports(<?= $i['id'] ?>)">
                View Reports<?= $reportCount > 0 ? " ($reportCount)" : '' ?>
              </button>
            </div>

            <!-- ── View Reports Panel ── -->
            <div class="view-reports-panel" id="view-reports-<?= $i['id'] ?>">

              <!-- Community/Hotline original report -->
              <p class="report-section-label">Original report</p>
              <div class="comm-report-card">
                <div class="comm-report-row">
                  <div class="comm-report-body">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                      <span class="source-badge <?= $sourceClass ?>"><?= $sourceLabel ?></span>
                    </div>
                    <div class="comm-report-notes"><?= htmlspecialchars($i['notes'] ?: 'No details provided.') ?></div>
                    <div class="comm-report-meta">
                      <?= date('M j, Y · g:i A', strtotime($i['created_at'])) ?>
                      <?php if ($i['reporter_name']): ?>
                        · <strong><?= htmlspecialchars($i['reporter_name']) ?></strong>
                      <?php endif; ?>
                      <?php if ($i['reporter_contact']): ?>
                        · <?= htmlspecialchars($i['reporter_contact']) ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <button class="edit-btn" onclick="openEditIncidentModal(
                    <?= $i['id'] ?>,
                    '<?= htmlspecialchars(addslashes($i['notes'] ?? ''), ENT_QUOTES) ?>',
                    '<?= htmlspecialchars(addslashes($i['reporter_name'] ?? ''), ENT_QUOTES) ?>',
                    '<?= htmlspecialchars(addslashes($i['reporter_contact'] ?? ''), ENT_QUOTES) ?>',
                    '<?= $i['risk_level'] ?>',
                    '<?= $reportedBy ?>'
                  )">✏ Edit</button>
                </div>
              </div>

              <div class="report-divider"></div>

              <!-- Notes -->
              <p class="report-section-label">Notes</p>
              <?php if ($incidentNotes): ?>
                <?php foreach ($incidentNotes as $n): ?>
                  <div class="note-item">
                    <?= htmlspecialchars($n['note']) ?>
                    <div class="note-item-meta"><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="color:var(--ink-dim);font-size:13px;margin:0 0 10px;">No notes yet.</p>
              <?php endif; ?>
              <div class="note-form">
                <textarea id="note-text-<?= $i['id'] ?>" rows="2" placeholder="Add a note…"></textarea>
                <div style="margin-top:8px;">
                  <button class="btn-submit" style="font-size:12px;padding:7px 14px;"
                    onclick="submitNote(<?= $i['id'] ?>)">Add note</button>
                </div>
              </div>

              <?php if ($onspotReports || $postReports): ?>
                <div class="report-divider"></div>
              <?php endif; ?>

              <!-- On-spot reports -->
              <?php if ($onspotReports): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                  <p class="report-section-label" style="margin:0;">On-spot investigation reports</p>
                  <?php foreach ($onspotReports as $r): ?>
                    <button class="edit-btn" onclick="openEditOnspotModal(<?= $r['id'] ?>, <?= $i['id'] ?>)">✏ Edit</button>
                  <?php endforeach; ?>
                </div>
                <div class="report-table-wrap">
                  <table class="report-table">
                    <thead><tr>
                      <th style="width:110px;">By / Date</th>
                      <th style="width:130px;">Location</th>
                      <th style="width:80px;">Risk</th>
                      <th style="width:100px;">Cost damage</th>
                      <th style="width:100px;">Building</th>
                      <th style="width:70px;">Deployed</th>
                      <th style="width:70px;">Inj. pers.</th>
                      <th style="width:70px;">Inj. civ.</th>
                      <th style="width:60px;">Deaths</th>
                    </tr></thead>
                    <tbody>
                      <?php foreach ($onspotReports as $r): ?>
                      <tr>
                        <td><?= htmlspecialchars($r['investigator_name'] ?? '—') ?><br>
                          <span style="font-size:11px;color:var(--ink-dim);"><?= htmlspecialchars($r['report_date'] ?? '') ?> <?= htmlspecialchars($r['report_time'] ?? '') ?></span>
                        </td>
                        <td><?= htmlspecialchars($r['location'] ?? '—') ?></td>
                        <td><?= ucfirst($r['risk_level'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['estimated_cost_damage'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['building_type'] ?? '—') ?></td>
                        <td><?= $r['personnel_deployed'] ?? 0 ?></td>
                        <td><?= $r['personnel_injured'] ?? 0 ?></td>
                        <td><?= $r['civilians_injured'] ?? 0 ?></td>
                        <td><?= $r['deaths'] ?? 0 ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php foreach ($onspotReports as $r): ?>
                <div class="report-extras">
                  <?php if ($r['witness_name']): ?><div><p class="report-extra-label">Witness</p><p class="report-extra-val"><?= htmlspecialchars($r['witness_name']) ?> — <?= htmlspecialchars($r['witness_address'] ?? '') ?></p></div><?php endif; ?>
                  <?php if ($r['testimony']): ?><div><p class="report-extra-label">Testimony</p><p class="report-extra-val"><?= htmlspecialchars($r['testimony']) ?></p></div><?php endif; ?>
                  <?php if ($r['conclusion_of_origin']): ?><div class="full"><p class="report-extra-label">Conclusion of origin</p><p class="report-extra-val"><?= htmlspecialchars($r['conclusion_of_origin']) ?></p></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if ($onspotReports && $postReports): ?><div class="report-divider"></div><?php endif; ?>

              <!-- Post incident reports -->
              <?php if ($postReports): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                  <p class="report-section-label" style="margin:0;">Post incident reports</p>
                  <?php foreach ($postReports as $r): ?>
                    <button class="edit-btn" onclick="openEditPostModal(<?= $r['id'] ?>, <?= $i['id'] ?>)">✏ Edit</button>
                  <?php endforeach; ?>
                </div>
                <div class="report-table-wrap">
                  <table class="report-table">
                    <thead><tr>
                      <th style="width:110px;">By / Date</th>
                      <th style="width:120px;">Location</th>
                      <th style="width:80px;">Risk</th>
                      <th style="width:55px;">Scale</th>
                      <th style="width:110px;">Final cost dmg.</th>
                      <th style="width:100px;">Building</th>
                      <th style="width:70px;">Deployed</th>
                      <th style="width:70px;">Inj. pers.</th>
                      <th style="width:70px;">Inj. civ.</th>
                      <th style="width:60px;">Deaths</th>
                    </tr></thead>
                    <tbody>
                      <?php foreach ($postReports as $r): ?>
                      <tr>
                        <td><?= htmlspecialchars($r['investigator_name'] ?? '—') ?><br>
                          <span style="font-size:11px;color:var(--ink-dim);"><?= htmlspecialchars($r['report_date'] ?? '') ?> <?= htmlspecialchars($r['report_time'] ?? '') ?></span>
                        </td>
                        <td><?= htmlspecialchars($r['location'] ?? '—') ?></td>
                        <td><?= ucfirst($r['risk_level'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['incident_scale'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['estimated_final_cost_damage'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['building_type'] ?? '—') ?></td>
                        <td><?= $r['personnel_deployed'] ?? 0 ?></td>
                        <td><?= $r['personnel_injured'] ?? 0 ?></td>
                        <td><?= $r['civilians_injured'] ?? 0 ?></td>
                        <td><?= $r['deaths'] ?? 0 ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php foreach ($postReports as $r): ?>
                <div class="report-extras">
                  <?php if ($r['descriptive_narration']): ?><div><p class="report-extra-label">Narration</p><p class="report-extra-val"><?= htmlspecialchars($r['descriptive_narration']) ?></p></div><?php endif; ?>
                  <?php if ($r['zone_documentation_path']): ?><div><p class="report-extra-label">Zone documentation</p><p class="report-extra-val"><a href="<?= htmlspecialchars($r['zone_documentation_path']) ?>" target="_blank" style="color:var(--ember);">View zone photo</a></p></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if (!$onspotReports && !$postReports): ?>
                <p style="color:var(--ink-dim);font-size:13px;margin:8px 0 0;">No investigation reports filed yet.</p>
              <?php endif; ?>
            </div><!-- /.view-reports-panel -->
          </div><!-- /.incident-card -->

          <!-- ── On-Spot Add Modal ── -->
          <div class="firestrat-modal" id="onspot-modal-<?= $i['id'] ?>">
            <div class="modal-box">
              <div class="modal-head">
                <div class="modal-head-left">
                  <div class="modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                  </div>
                  <div>
                    <div class="modal-title">On-Spot Investigation Report</div>
                    <div class="modal-ticket"><?= htmlspecialchars($ticket) ?> · <?= htmlspecialchars($i['barangay_name']) ?></div>
                  </div>
                </div>
                <button class="modal-close" onclick="closeModal('onspot-modal-<?= $i['id'] ?>')">×</button>
              </div>
              <div class="modal-body">
                <div class="form-grid">
                  <div class="form-field"><label>Investigator's Name</label><input type="text" id="inv_name_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Position</label><input type="text" id="inv_position_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Date</label><input type="date" id="inv_date_<?= $i['id'] ?>" value="<?= date('Y-m-d') ?>"></div>
                  <div class="form-field"><label>Time</label><input type="time" id="inv_time_<?= $i['id'] ?>" value="<?= date('H:i') ?>"></div>
                  <div class="form-field"><label>Location</label><input type="text" id="inv_location_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Risk Level</label>
                    <select id="inv_risk_<?= $i['id'] ?>"><option value="high">High</option><option value="moderate">Moderate</option><option value="low">Low</option></select>
                  </div>
                  <div class="form-field"><label>Estimated Cost Damage</label><input type="text" id="inv_cost_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Building Type</label><input type="text" id="inv_building_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Deployed Personnel</label><input type="number" id="inv_deployed_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Injured Personnel</label><input type="number" id="inv_inj_pers_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Injured Civilians</label><input type="number" id="inv_inj_civ_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Deaths</label><input type="number" id="inv_deaths_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Witness Name</label><input type="text" id="inv_witness_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Witness Address</label><input type="text" id="inv_wit_addr_<?= $i['id'] ?>"></div>
                  <div class="form-field full-width"><label>Testimony</label><textarea id="inv_testimony_<?= $i['id'] ?>" rows="3"></textarea></div>
                  <div class="form-field full-width"><label>Conclusion of Origin</label><textarea id="inv_conclusion_<?= $i['id'] ?>" rows="3"></textarea></div>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('onspot-modal-<?= $i['id'] ?>')">Cancel</button>
                <button class="btn-submit" onclick="submitOnspotReport(<?= $i['id'] ?>)">Submit Report</button>
              </div>
            </div>
          </div>

          <!-- ── Post Incident Add Modal ── -->
          <div class="firestrat-modal" id="post-modal-<?= $i['id'] ?>">
            <div class="modal-box">
              <div class="modal-head">
                <div class="modal-head-left">
                  <div class="modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                  </div>
                  <div>
                    <div class="modal-title">Post Incident Report</div>
                    <div class="modal-ticket"><?= htmlspecialchars($ticket) ?> · <?= htmlspecialchars($i['barangay_name']) ?></div>
                  </div>
                </div>
                <button class="modal-close" onclick="closeModal('post-modal-<?= $i['id'] ?>')">×</button>
              </div>
              <div class="modal-body">
                <div class="form-grid">
                  <div class="form-field"><label>Investigator's Name</label><input type="text" id="post_name_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Position</label><input type="text" id="post_position_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Date</label><input type="date" id="post_date_<?= $i['id'] ?>" value="<?= date('Y-m-d') ?>"></div>
                  <div class="form-field"><label>Time</label><input type="time" id="post_time_<?= $i['id'] ?>" value="<?= date('H:i') ?>"></div>
                  <div class="form-field"><label>Location</label><input type="text" id="post_location_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Risk Level</label>
                    <select id="post_risk_<?= $i['id'] ?>"><option value="high">High</option><option value="moderate">Moderate</option><option value="low">Low</option></select>
                  </div>
                  <div class="form-field"><label>Estimated Final Cost Damage</label><input type="text" id="post_cost_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Building Type</label><input type="text" id="post_building_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Incident Scale</label><input type="text" id="post_scale_<?= $i['id'] ?>"></div>
                  <div class="form-field"><label>Deployed Personnel</label><input type="number" id="post_deployed_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Injured Personnel</label><input type="number" id="post_inj_pers_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Injured Civilians</label><input type="number" id="post_inj_civ_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field"><label>Deaths</label><input type="number" id="post_deaths_<?= $i['id'] ?>" min="0"></div>
                  <div class="form-field full-width"><label>Descriptive Narration</label><textarea id="post_narration_<?= $i['id'] ?>" rows="4"></textarea></div>
                  <div class="form-field full-width"><label>Zone Documentation (photo)</label><input type="file" id="post_photo_<?= $i['id'] ?>" accept="image/*"></div>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('post-modal-<?= $i['id'] ?>')">Cancel</button>
                <button class="btn-submit" onclick="submitPostReport(<?= $i['id'] ?>)">Submit Report</button>
              </div>
            </div>
          </div>

        <?php endforeach; endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- ── Edit Incident Modal (shared) ── -->
<div class="firestrat-modal" id="edit-incident-modal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-icon edit-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div><div class="modal-title">Edit Incident Report</div><div class="modal-ticket" id="edit-incident-ticket"></div></div>
      </div>
      <button class="modal-close" onclick="closeModal('edit-incident-modal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-incident-id">
      <div class="form-grid">
        <div class="form-field"><label>Risk Level</label>
          <select id="edit-incident-risk"><option value="high">High</option><option value="moderate">Moderate</option><option value="low">Low</option></select>
        </div>
        <div class="form-field"><label>Source</label>
          <select id="edit-incident-source">
            <option value="community">🌐 Community report</option>
            <option value="hotline">📞 Hotline report</option>
            <option value="admin">🛡️ Officer logged</option>
          </select>
        </div>
        <div class="form-field"><label>Reporter Name</label><input type="text" id="edit-incident-name"></div>
        <div class="form-field"><label>Contact Number</label><input type="text" id="edit-incident-contact"></div>
        <div class="form-field full-width"><label>Notes / Details</label><textarea id="edit-incident-notes" rows="3"></textarea></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('edit-incident-modal')">Cancel</button>
      <button class="btn-submit blue" onclick="saveEditIncident()">Save changes</button>
    </div>
  </div>
</div>

<!-- ── Edit On-Spot Modal (shared) ── -->
<div class="firestrat-modal" id="edit-onspot-modal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-icon edit-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div><div class="modal-title">Edit On-Spot Report</div></div>
      </div>
      <button class="modal-close" onclick="closeModal('edit-onspot-modal')">×</button>
    </div>
    <div class="modal-body" id="edit-onspot-body">
      <p style="color:var(--ink-dim);font-size:13px;">Loading…</p>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('edit-onspot-modal')">Cancel</button>
      <button class="btn-submit blue" onclick="saveEditOnspot()">Save changes</button>
    </div>
  </div>
</div>

<!-- ── Edit Post Modal (shared) ── -->
<div class="firestrat-modal" id="edit-post-modal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-icon edit-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div><div class="modal-title">Edit Post Incident Report</div></div>
      </div>
      <button class="modal-close" onclick="closeModal('edit-post-modal')">×</button>
    </div>
    <div class="modal-body" id="edit-post-body">
      <p style="color:var(--ink-dim);font-size:13px;">Loading…</p>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('edit-post-modal')">Cancel</button>
      <button class="btn-submit blue" onclick="saveEditPost()">Save changes</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
  function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
  }
  document.querySelectorAll('.firestrat-modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
  });

  window.submitNote = async function(id) {
    const ta = document.getElementById('note-text-' + id);
    const txt = ta.value.trim();
    if (!txt) return;
    try {
      await fetch('api/incident_notes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ incident_id: id, note: txt })
      });
      location.reload();
    } catch (e) { alert('Could not save note: ' + e.message); }
  };

  window.toggleOnspotForm = function(id) { openModal('onspot-modal-' + id); };
  window.togglePostForm   = function(id) { openModal('post-modal-' + id); };

  // ── Edit Incident ──
  window.openEditIncidentModal = function(id, notes, name, contact, risk, source) {
    document.getElementById('edit-incident-id').value      = id;
    document.getElementById('edit-incident-notes').value   = notes;
    document.getElementById('edit-incident-name').value    = name;
    document.getElementById('edit-incident-contact').value = contact;
    document.getElementById('edit-incident-risk').value    = risk;
    document.getElementById('edit-incident-source').value  = source;
    openModal('edit-incident-modal');
  };

  window.saveEditIncident = async function() {
    const id      = document.getElementById('edit-incident-id').value;
    const notes   = document.getElementById('edit-incident-notes').value.trim();
    const name    = document.getElementById('edit-incident-name').value.trim();
    const contact = document.getElementById('edit-incident-contact').value.trim();
    const risk    = document.getElementById('edit-incident-risk').value;
    const source  = document.getElementById('edit-incident-source').value;
    try {
      const res = await fetch('api/edit_incident.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, notes, reporter_name: name, reporter_contact: contact, risk_level: risk, reported_by: source })
      });
      if (!res.ok) throw new Error('Failed');
      location.reload();
    } catch(e) { alert('Could not save: ' + e.message); }
  };

  // ── Edit On-Spot ──
  window.openEditOnspotModal = async function(reportId, incidentId) {
    document.getElementById('edit-onspot-body').innerHTML = '<p style="color:var(--ink-dim);font-size:13px;">Loading…</p>';
    openModal('edit-onspot-modal');
    try {
      const res  = await fetch('api/get_onspot.php?id=' + reportId);
      const r    = await res.json();
      document.getElementById('edit-onspot-body').innerHTML = `
        <input type="hidden" id="edit-onspot-id" value="${r.id}">
        <div class="form-grid">
          <div class="form-field"><label>Investigator's Name</label><input type="text" id="eo_name" value="${r.investigator_name||''}"></div>
          <div class="form-field"><label>Position</label><input type="text" id="eo_position" value="${r.position||''}"></div>
          <div class="form-field"><label>Date</label><input type="date" id="eo_date" value="${r.report_date||''}"></div>
          <div class="form-field"><label>Time</label><input type="time" id="eo_time" value="${r.report_time||''}"></div>
          <div class="form-field"><label>Location</label><input type="text" id="eo_location" value="${r.location||''}"></div>
          <div class="form-field"><label>Risk Level</label>
            <select id="eo_risk">
              <option value="high" ${r.risk_level==='high'?'selected':''}>High</option>
              <option value="moderate" ${r.risk_level==='moderate'?'selected':''}>Moderate</option>
              <option value="low" ${r.risk_level==='low'?'selected':''}>Low</option>
            </select>
          </div>
          <div class="form-field"><label>Cost Damage</label><input type="text" id="eo_cost" value="${r.estimated_cost_damage||''}"></div>
          <div class="form-field"><label>Building Type</label><input type="text" id="eo_building" value="${r.building_type||''}"></div>
          <div class="form-field"><label>Deployed</label><input type="number" id="eo_deployed" value="${r.personnel_deployed||0}" min="0"></div>
          <div class="form-field"><label>Inj. Personnel</label><input type="number" id="eo_inj_pers" value="${r.personnel_injured||0}" min="0"></div>
          <div class="form-field"><label>Inj. Civilians</label><input type="number" id="eo_inj_civ" value="${r.civilians_injured||0}" min="0"></div>
          <div class="form-field"><label>Deaths</label><input type="number" id="eo_deaths" value="${r.deaths||0}" min="0"></div>
          <div class="form-field"><label>Witness Name</label><input type="text" id="eo_witness" value="${r.witness_name||''}"></div>
          <div class="form-field"><label>Witness Address</label><input type="text" id="eo_wit_addr" value="${r.witness_address||''}"></div>
          <div class="form-field full-width"><label>Testimony</label><textarea id="eo_testimony" rows="3">${r.testimony||''}</textarea></div>
          <div class="form-field full-width"><label>Conclusion of Origin</label><textarea id="eo_conclusion" rows="3">${r.conclusion_of_origin||''}</textarea></div>
        </div>`;
    } catch(e) { document.getElementById('edit-onspot-body').innerHTML = '<p style="color:red;">Failed to load report.</p>'; }
  };

  window.saveEditOnspot = async function() {
    const id = document.getElementById('edit-onspot-id').value;
    const payload = {
      id,
      investigator_name:    document.getElementById('eo_name').value,
      position:             document.getElementById('eo_position').value,
      report_date:          document.getElementById('eo_date').value,
      report_time:          document.getElementById('eo_time').value,
      location:             document.getElementById('eo_location').value,
      risk_level:           document.getElementById('eo_risk').value,
      estimated_cost_damage:document.getElementById('eo_cost').value,
      building_type:        document.getElementById('eo_building').value,
      personnel_deployed:   document.getElementById('eo_deployed').value,
      personnel_injured:    document.getElementById('eo_inj_pers').value,
      civilians_injured:    document.getElementById('eo_inj_civ').value,
      deaths:               document.getElementById('eo_deaths').value,
      witness_name:         document.getElementById('eo_witness').value,
      witness_address:      document.getElementById('eo_wit_addr').value,
      testimony:            document.getElementById('eo_testimony').value,
      conclusion_of_origin: document.getElementById('eo_conclusion').value,
    };
    try {
      const res = await fetch('api/edit_onspot.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error('Failed');
      location.reload();
    } catch(e) { alert('Could not save: ' + e.message); }
  };

  // ── Edit Post ──
  window.openEditPostModal = async function(reportId, incidentId) {
    document.getElementById('edit-post-body').innerHTML = '<p style="color:var(--ink-dim);font-size:13px;">Loading…</p>';
    openModal('edit-post-modal');
    try {
      const res = await fetch('api/get_post.php?id=' + reportId);
      const r   = await res.json();
      document.getElementById('edit-post-body').innerHTML = `
        <input type="hidden" id="edit-post-id" value="${r.id}">
        <div class="form-grid">
          <div class="form-field"><label>Investigator's Name</label><input type="text" id="ep_name" value="${r.investigator_name||''}"></div>
          <div class="form-field"><label>Position</label><input type="text" id="ep_position" value="${r.position||''}"></div>
          <div class="form-field"><label>Date</label><input type="date" id="ep_date" value="${r.report_date||''}"></div>
          <div class="form-field"><label>Time</label><input type="time" id="ep_time" value="${r.report_time||''}"></div>
          <div class="form-field"><label>Location</label><input type="text" id="ep_location" value="${r.location||''}"></div>
          <div class="form-field"><label>Risk Level</label>
            <select id="ep_risk">
              <option value="high" ${r.risk_level==='high'?'selected':''}>High</option>
              <option value="moderate" ${r.risk_level==='moderate'?'selected':''}>Moderate</option>
              <option value="low" ${r.risk_level==='low'?'selected':''}>Low</option>
            </select>
          </div>
          <div class="form-field"><label>Final Cost Damage</label><input type="text" id="ep_cost" value="${r.estimated_final_cost_damage||''}"></div>
          <div class="form-field"><label>Building Type</label><input type="text" id="ep_building" value="${r.building_type||''}"></div>
          <div class="form-field"><label>Incident Scale</label><input type="text" id="ep_scale" value="${r.incident_scale||''}"></div>
          <div class="form-field"><label>Deployed</label><input type="number" id="ep_deployed" value="${r.personnel_deployed||0}" min="0"></div>
          <div class="form-field"><label>Inj. Personnel</label><input type="number" id="ep_inj_pers" value="${r.personnel_injured||0}" min="0"></div>
          <div class="form-field"><label>Inj. Civilians</label><input type="number" id="ep_inj_civ" value="${r.civilians_injured||0}" min="0"></div>
          <div class="form-field"><label>Deaths</label><input type="number" id="ep_deaths" value="${r.deaths||0}" min="0"></div>
          <div class="form-field full-width"><label>Descriptive Narration</label><textarea id="ep_narration" rows="4">${r.descriptive_narration||''}</textarea></div>
        </div>`;
    } catch(e) { document.getElementById('edit-post-body').innerHTML = '<p style="color:red;">Failed to load report.</p>'; }
  };

  window.saveEditPost = async function() {
    const id = document.getElementById('edit-post-id').value;
    const payload = {
      id,
      investigator_name:             document.getElementById('ep_name').value,
      position:                      document.getElementById('ep_position').value,
      report_date:                   document.getElementById('ep_date').value,
      report_time:                   document.getElementById('ep_time').value,
      location:                      document.getElementById('ep_location').value,
      risk_level:                    document.getElementById('ep_risk').value,
      estimated_final_cost_damage:   document.getElementById('ep_cost').value,
      building_type:                 document.getElementById('ep_building').value,
      incident_scale:                document.getElementById('ep_scale').value,
      personnel_deployed:            document.getElementById('ep_deployed').value,
      personnel_injured:             document.getElementById('ep_inj_pers').value,
      civilians_injured:             document.getElementById('ep_inj_civ').value,
      deaths:                        document.getElementById('ep_deaths').value,
      descriptive_narration:         document.getElementById('ep_narration').value,
    };
    try {
      const res = await fetch('api/edit_post.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error('Failed');
      location.reload();
    } catch(e) { alert('Could not save: ' + e.message); }
  };

  window.setIncidentStatus = async function(id, status) {
    try {
      const res = await fetch('api/incident_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed');
      location.reload();
    } catch(e) { alert('Could not update status: ' + e.message); }
  };
</script>
</body>
</html>