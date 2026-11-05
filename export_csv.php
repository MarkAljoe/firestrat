<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT i.ticket_number, b.name AS barangay, i.risk_level, i.status,
               i.notes, i.reported_by, i.reporter_name, i.reporter_contact,
               i.created_at
        FROM incidents i
        JOIN barangays b ON b.id = i.barangay_id
        WHERE i.approval_status = 'approved'";

if ($id > 0) {
    $sql .= " AND i.id = " . $id;
}
$sql .= " ORDER BY i.created_at DESC";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll();
$rows = $stmt->fetchAll();

$filename = 'firestrat_incidents_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['Ticket #', 'Barangay', 'Risk Level', 'Status', 'Notes', 'Reported By', 'Reporter Name', 'Reporter Contact', 'Date & Time']);

// Data rows
foreach ($rows as $r) {
    fputcsv($out, [
        $r['ticket_number'],
        $r['barangay'],
        ucfirst($r['risk_level']),
        ucfirst(str_replace('_', ' ', $r['status'])),
        $r['notes'],
        ucfirst($r['reported_by']),
        $r['reporter_name'] ?? '—',
        $r['reporter_contact'] ?? '—',
        $r['created_at']
    ]);
}

fclose($out);
exit;