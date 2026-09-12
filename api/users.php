<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $fullName = trim($data['full_name'] ?? '');
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'responder';

    if (!$fullName || !$username || strlen($password) < 6) {
        http_response_code(422);
        echo json_encode(['error' => 'Full name, username, and a password (6+ characters) are required.']);
        exit;
    }

    $check = $db->prepare('SELECT id FROM users WHERE username = ?');
    $check->execute([$username]);
    if ($check->fetch()) {
        http_response_code(422);
        echo json_encode(['error' => 'That username is already taken.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $hash, $fullName, $role]);

    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($data['id'] ?? 0);

    if (!$id) {
        http_response_code(422);
        echo json_encode(['error' => 'A valid user id is required.']);
        exit;
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);