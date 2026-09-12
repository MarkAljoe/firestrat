<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);
$action = $data['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'A valid report id and action are required.']);
    exit;
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';

$db->beginTransaction();
try {
    $stmt = $db->prepare('UPDATE incidents SET approval_status = ? WHERE id = ?');
    $stmt->execute([$newStatus, $id]);

    if ($newStatus === 'approved') {
        // Keep the barangay's displayed risk level in sync
        $row = $db->prepare('SELECT barangay_id, risk_level FROM incidents WHERE id = ?');
        $row->execute([$id]);
        $inc = $row->fetch();
        if ($inc) {
            $upd = $db->prepare('UPDATE barangays SET risk_level = ? WHERE id = ?');
            $upd->execute([$inc['risk_level'], $inc['barangay_id']]);
        }
    }

    $db->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Could not update the report.']);
}