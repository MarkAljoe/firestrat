<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$count = (int)$db->query("SELECT COUNT(*) c FROM incidents WHERE approval_status='pending'")->fetch()['c'];
echo json_encode(['count' => $count]);