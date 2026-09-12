<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query('SELECT id, name, lat, lng, risk_level, population, updated_at FROM barangays ORDER BY name');
    echo json_encode($stmt->fetchAll());
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/auth.php';
    requireLogin();

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($data['id'] ?? 0);
    $riskLevel = $data['risk_level'] ?? '';

    if (!$id || !in_array($riskLevel, ['high', 'moderate', 'low'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'A valid barangay id and risk level are required.']);
        exit;
    }

    $stmt = $db->prepare('UPDATE barangays SET risk_level = ? WHERE id = ?');
    $stmt->execute([$riskLevel, $id]);

    echo json_encode(['ok' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
