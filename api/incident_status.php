<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!$id || !in_array($status, ['active',  'fire_out'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'A valid incident id and status are required.']);
    exit;
}

$stmt = $db->prepare('UPDATE incidents SET status = ? WHERE id = ?');
$stmt->execute([$status, $id]);

echo json_encode(['ok' => true]);