<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $sql = 'SELECT i.id, i.ticket_number, i.risk_level, i.notes, i.reported_by, i.reporter_name, i.status,
               i.created_at, b.id AS barangay_id, b.name AS barangay_name,
               COALESCE(i.lat, b.lat) AS lat, COALESCE(i.lng, b.lng) AS lng
        FROM incidents i
        JOIN barangays b ON b.id = i.barangay_id';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE b.name LIKE ? OR i.notes LIKE ?';
        $params = ["%$q%", "%$q%"];
    }
    $sql .= ' ORDER BY i.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $barangayId  = (int)($data['barangay_id'] ?? 0);
    $riskLevel   = $data['risk_level'] ?? '';
    $notes       = trim($data['notes'] ?? '');
    $reportedBy  = $data['reported_by'] ?? 'community';
    $reporterName = trim($data['reporter_name'] ?? '') ?: null;
    $reporterContact = trim($data['reporter_contact'] ?? '') ?: null;

    if (!$barangayId || !in_array($riskLevel, ['high', 'moderate', 'low'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'A valid barangay and risk level are required.']);
        exit;
    }
    if (!in_array($reportedBy, ['admin', 'community'], true)) {
        $reportedBy = 'community';
    }
   // Only a signed-in staff member may file as "admin"
if ($reportedBy === 'admin' && !isLoggedIn()) {
    $reportedBy = 'community';
}
$approvalStatus = ($reportedBy === 'admin') ? 'approved' : 'pending';
    if ($notes === '') {
        $notes = 'No additional notes provided.';
    }
$db->beginTransaction();
try {
    $stmt = $db->prepare(
    'INSERT INTO incidents (barangay_id, risk_level, notes, reported_by, approval_status, reporter_name, reporter_contact, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, "active")'
);
$stmt->execute([$barangayId, $riskLevel, $notes, $reportedBy, $approvalStatus, $reporterName, $reporterContact]);
$newId = $db->lastInsertId();
$ticket = 'TKT-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
$db->prepare('UPDATE incidents SET ticket_number = ? WHERE id = ?')->execute([$ticket, $newId]);
        if ($approvalStatus === 'approved') {
            // Keep the barangay's displayed risk level in sync with its most recent incident
            $upd = $db->prepare('UPDATE barangays SET risk_level = ? WHERE id = ?');
            $upd->execute([$riskLevel, $barangayId]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Could not save the incident.']);
        exit;
    }

    echo json_encode(['ok' => true, 'id' => $newId]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
