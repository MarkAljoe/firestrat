<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$db = getDB();
$row = $db->prepare('SELECT * FROM post_incident_reports WHERE id = ?');
$row->execute([(int)$_GET['id']]);
echo json_encode($row->fetch());