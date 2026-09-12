<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$incidentId = (int)($data['incident_id'] ?? 0);
if (!$incidentId) {
    http_response_code(422);
    echo json_encode(['error' => 'A valid incident id is required.']);
    exit;
}

$stmt = $db->prepare(
    'INSERT INTO onspot_reports
     (incident_id, investigator_name, report_time, report_date, location, position, risk_level,
      estimated_cost_damage, building_type, personnel_deployed, personnel_injured, civilians_injured,
      deaths, witness_name, witness_address, testimony, conclusion_of_origin)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmt->execute([
    $incidentId,
    $data['investigator_name'] ?? null,
    $data['report_time'] ?? null,
    $data['report_date'] ?? null,
    $data['location'] ?? null,
    $data['position'] ?? null,
    $data['risk_level'] ?? null,
    $data['estimated_cost_damage'] ?? null,
    $data['building_type'] ?? null,
    $data['personnel_deployed'] ?: null,
    $data['personnel_injured'] ?: null,
    $data['civilians_injured'] ?: null,
    $data['deaths'] ?: null,
    $data['witness_name'] ?? null,
    $data['witness_address'] ?? null,
    $data['testimony'] ?? null,
    $data['conclusion_of_origin'] ?? null,
]);

echo json_encode(['ok' => true]);