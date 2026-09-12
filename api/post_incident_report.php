<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();

$incidentId = (int)($_POST['incident_id'] ?? 0);
if (!$incidentId) {
    http_response_code(422);
    echo json_encode(['error' => 'A valid incident id is required.']);
    exit;
}

$photoPath = null;
if (!empty($_FILES['zone_photo']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($_FILES['zone_photo']['type'], $allowed, true)) {
        http_response_code(422);
        echo json_encode(['error' => 'Only JPG, PNG, or WEBP images are allowed.']);
        exit;
    }
    if ($_FILES['zone_photo']['size'] > 5 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode(['error' => 'Photo must be under 5MB.']);
        exit;
    }
    $ext = pathinfo($_FILES['zone_photo']['name'], PATHINFO_EXTENSION);
    $filename = 'zone_' . $incidentId . '_' . time() . '.' . $ext;
    $destination = __DIR__ . '/../uploads/zone_docs/' . $filename;
    if (move_uploaded_file($_FILES['zone_photo']['tmp_name'], $destination)) {
        $photoPath = 'uploads/zone_docs/' . $filename;
    }
}

$stmt = $db->prepare(
    'INSERT INTO post_incident_reports
     (incident_id, investigator_name, location, report_time, report_date, position, risk_level,
      estimated_final_cost_damage, building_type, incident_scale, personnel_deployed, personnel_injured,
      civilians_injured, deaths, descriptive_narration, zone_documentation_path)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmt->execute([
    $incidentId,
    $_POST['investigator_name'] ?? null,
    $_POST['location'] ?? null,
    $_POST['report_time'] ?? null,
    $_POST['report_date'] ?? null,
    $_POST['position'] ?? null,
    $_POST['risk_level'] ?? null,
    $_POST['estimated_final_cost_damage'] ?? null,
    $_POST['building_type'] ?? null,
    $_POST['incident_scale'] ?? null,
    $_POST['personnel_deployed'] ?: null,
    $_POST['personnel_injured'] ?: null,
    $_POST['civilians_injured'] ?: null,
    $_POST['deaths'] ?: null,
    $_POST['descriptive_narration'] ?? null,
    $photoPath,
]);

echo json_encode(['ok' => true]);   