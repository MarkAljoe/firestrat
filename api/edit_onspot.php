<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$db = getDB();
$d = json_decode(file_get_contents('php://input'), true);
$db->prepare('UPDATE onspot_reports SET investigator_name=?,position=?,report_date=?,report_time=?,location=?,risk_level=?,estimated_cost_damage=?,building_type=?,personnel_deployed=?,personnel_injured=?,civilians_injured=?,deaths=?,witness_name=?,witness_address=?,testimony=?,conclusion_of_origin=? WHERE id=?')
   ->execute([$d['investigator_name'],$d['position'],$d['report_date'],$d['report_time'],$d['location'],$d['risk_level'],$d['estimated_cost_damage'],$d['building_type'],$d['personnel_deployed'],$d['personnel_injured'],$d['civilians_injured'],$d['deaths'],$d['witness_name'],$d['witness_address'],$d['testimony'],$d['conclusion_of_origin'],$d['id']]);
echo json_encode(['ok' => true]);