<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$db = getDB();
$d = json_decode(file_get_contents('php://input'), true);
$db->prepare('UPDATE incidents SET notes=?, reporter_name=?, reporter_contact=?, risk_level=?, reported_by=? WHERE id=?')
   ->execute([$d['notes'], $d['reporter_name'], $d['reporter_contact'], $d['risk_level'], $d['reported_by'], $d['id']]);
echo json_encode(['ok' => true]);