<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db   = getDB();
$type = $_POST['type'] ?? '';
$id   = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    if ($type === 'barangay') {
        $db->prepare("UPDATE barangays SET lat = NULL, lng = NULL WHERE id = ?")->execute([$id]);
    } elseif ($type === 'hydrant') {
        $db->prepare("DELETE FROM hydrants WHERE id = ?")->execute([$id]);
    }
}

header('Location: manage_locations.php');
exit;