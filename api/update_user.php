<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$id       = (int)($data['id'] ?? 0);
$fullName = trim($data['full_name'] ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$role     = $data['role'] ?? '';

if (!$id || !$fullName || !$username) {
  echo json_encode(['ok' => false, 'error' => 'Missing required fields.']);
  exit;
}

$allowed = ['fire_marshal', 'admin', 'officer'];
if (!in_array($role, $allowed)) {
  echo json_encode(['ok' => false, 'error' => 'Invalid role.']);
  exit;
}

$db = getDB();

// Check username not taken by another user
$existing = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
$existing->execute([$username, $id]);
if ($existing->fetch()) {
  echo json_encode(['ok' => false, 'error' => 'Username already taken.']);
  exit;
}

if ($password !== '') {
  if (strlen($password) < 8) {
    echo json_encode(['ok' => false, 'error' => 'Password too short.']);
    exit;
  }
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $db->prepare('UPDATE users SET full_name=?, username=?, password=?, role=? WHERE id=?');
  $stmt->execute([$fullName, $username, $hash, $role, $id]);
} else {
  $stmt = $db->prepare('UPDATE users SET full_name=?, username=?, role=? WHERE id=?');
  $stmt->execute([$fullName, $username, $role, $id]);
}

echo json_encode(['ok' => true]);