<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); echo json_encode(['error' => 'Invalid ID']); exit; }

// Barangay base info
$brgy = $db->prepare('SELECT * FROM barangays WHERE id = ?');
$brgy->execute([$id]);
$b = $brgy->fetch();
if (!$b) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }

// Incident count (approved only)
$cntStmt = $db->prepare("SELECT COUNT(*) c FROM incidents WHERE barangay_id = ? AND approval_status = 'approved'");
$cntStmt->execute([$id]);
$incidentCount = (int)$cntStmt->fetch()['c'];

// Status breakdown
$activeStmt = $db->prepare("SELECT COUNT(*) c FROM incidents WHERE barangay_id = ? AND approval_status = 'approved' AND status = 'active'");
$activeStmt->execute([$id]);
$activeCount = (int)$activeStmt->fetch()['c'];

$fireoutStmt = $db->prepare("SELECT COUNT(*) c FROM incidents WHERE barangay_id = ? AND approval_status = 'approved' AND status = 'fire_out'");
$fireoutStmt->execute([$id]);
$fireoutCount = (int)$fireoutStmt->fetch()['c'];

// Latest incident
$latestStmt = $db->prepare("SELECT i.id, i.ticket_number, i.risk_level, i.notes, i.status, i.created_at,
                                    i.reporter_name
                             FROM incidents i
                             WHERE i.barangay_id = ? AND i.approval_status = 'approved'
                             ORDER BY i.created_at DESC LIMIT 1");
$latestStmt->execute([$id]);
$latest = $latestStmt->fetch() ?: null;

// Hydrant counts
$hydrantStmt = $db->prepare('SELECT COUNT(*) c FROM hydrants WHERE barangay_id = ?');
$hydrantStmt->execute([$id]);
$totalHydrants = (int)$hydrantStmt->fetch()['c'];

$activeHydrantStmt = $db->prepare("SELECT COUNT(*) c FROM hydrants WHERE barangay_id = ? AND status = 'active'");
$activeHydrantStmt->execute([$id]);
$activeHydrants = (int)$activeHydrantStmt->fetch()['c'];

// Ever had high risk?
$highStmt = $db->prepare("SELECT COUNT(*) c FROM incidents WHERE barangay_id = ? AND risk_level = 'high' AND approval_status = 'approved'");
$highStmt->execute([$id]);
$everHighRisk = (int)$highStmt->fetch()['c'] > 0;

echo json_encode([
  'id'              => (int)$b['id'],
  'name'            => $b['name'],
  'risk_level'      => $b['risk_level'],
  'population'      => $b['population'],
  'lat'             => $b['lat'],
  'lng'             => $b['lng'],
  'incident_count'  => $incidentCount,
  'active_count'    => $activeCount,
  'fireout_count'   => $fireoutCount,
  'total_hydrants'  => $totalHydrants,
  'active_hydrants' => $activeHydrants,
  'ever_high_risk'  => $everHighRisk,
  'latest_incident' => $latest,
]);