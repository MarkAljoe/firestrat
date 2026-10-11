<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$db = getDB();
$d = json_decode(file_get_contents('php://input'), true);
$db->prepare('UPDATE post_incident_reports SET investigator_name=?,position=?,report_date=?,report_time=?,location=?,risk_level=?,estimated_final_cost_damage=?,building_type=?,incident_scale=?,personnel_deployed=?,personnel_injured=?,civilians_injured=?,deaths=?,descriptive_narration=? WHERE id=?')
   ->execute([$d['investigator_name'],$d['position'],$d['report_date'],$d['report_time'],$d['location'],$d['risk_level'],$d['estimated_final_cost_damage'],$d['building_type'],$d['incident_scale'],$d['personnel_deployed'],$d['personnel_injured'],$d['civilians_injured'],$d['deaths'],$d['descriptive_narration'],$d['id']]);
echo json_encode(['ok' => true]);