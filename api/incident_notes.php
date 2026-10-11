<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$body = json_decode(file_get_contents('php://input'), true);
$db->prepare('INSERT INTO incident_notes (incident_id, note, created_at) VALUES (?, ?, NOW())')
   ->execute([$body['incident_id'], $body['note']]);
echo json_encode(['ok' => true]);