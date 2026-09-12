<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = 'SELECT h.id, h.code, h.lat, h.lng, h.status, h.last_checked,
                   b.id AS barangay_id, b.name AS barangay_name
            FROM hydrants h
            JOIN barangays b ON b.id = h.barangay_id
            ORDER BY b.name, h.code';
    echo json_encode($db->query($sql)->fetchAll());
    exit;
}

if ($method === 'PATCH' || $method === 'POST') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Sign in required to update hydrant status.']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!$id || !in_array($status, ['active', 'inactive'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'A valid hydrant id and status are required.']);
        exit;
    }

    $stmt = $db->prepare('UPDATE hydrants SET status = ?, last_checked = CURDATE() WHERE id = ?');
    $stmt->execute([$status, $id]);
    echo json_encode(['ok' => true, 'id' => $id, 'status' => $status]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
